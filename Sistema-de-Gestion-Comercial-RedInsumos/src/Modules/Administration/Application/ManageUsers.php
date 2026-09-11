<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Administration\Application;

use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

final class ManageUsers
{
    /** @var array<string, string> */
    public const PERMISSIONS = [
        'ADMIN' => 'Usuarios, roles y catálogos administrativos.',
        'VENDEDOR' => 'Productos y venta asistida; no modifica stock.',
        'ALMACENERO' => 'Inventario, movimientos y despachos.',
        'CONTABLE' => 'Consulta y gestión contable de compras, ventas y pagos.',
        'CLIENTE' => 'Catálogo, carrito propio, pedidos y cuenta personal.',
    ];

    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT u.id_usuario, u.nombre, u.email, u.estado, u.fecha_registro,
                    u.ultimo_acceso, r.codigo AS rol, r.nombre AS rol_nombre
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             ORDER BY u.nombre, u.id_usuario'
        )->fetchAll();
    }

    /** @return list<array<string, mixed>> */
    public function roles(): array
    {
        $placeholders = implode(',', array_fill(0, count(self::PERMISSIONS), '?'));
        $statement = $this->pdo->prepare(
            "SELECT id_rol, codigo, nombre, estado FROM roles
             WHERE codigo IN ($placeholders) ORDER BY id_rol"
        );
        $statement->execute(array_keys(self::PERMISSIONS));

        return $statement->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id_usuario, u.nombre, u.email, u.estado, r.codigo AS rol,
                    COALESCE(c.telefono, \'\') AS telefono
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             LEFT JOIN clientes c ON c.id_usuario = u.id_usuario
             WHERE u.id_usuario = :id LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $row : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $values = $this->validate($data, true);
        $this->assertEmailAvailable($values['email']);
        $roleId = $this->roleId($values['role']);

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare(
                "INSERT INTO usuarios (id_rol, nombre, email, password_hash, estado)
                 VALUES (:role, :name, :email, :password, :status)"
            );
            $statement->execute([
                'role' => $roleId,
                'name' => $values['name'],
                'email' => $values['email'],
                'password' => password_hash($values['password'], PASSWORD_DEFAULT),
                'status' => $values['status'],
            ]);
            $userId = (int) $this->pdo->lastInsertId();
            $this->ensureClientProfile($userId, $values['role'], $values['name'], $values['phone']);
            $this->pdo->commit();

            return $userId;
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, int $actorId, array $data): void
    {
        $existing = $this->find($id);
        if ($existing === null) {
            throw new InvalidArgumentException('El usuario no existe.');
        }

        $values = $this->validate($data, false);
        $this->assertEmailAvailable($values['email'], $id);

        if ($id === $actorId && ($values['role'] !== $existing['rol'] || $values['status'] !== 'activo')) {
            throw new InvalidArgumentException('No puedes cambiar tu propio rol ni desactivar tu propia cuenta.');
        }
        $this->protectLastAdmin($existing, $values['role'], $values['status']);
        $roleId = $this->roleId($values['role']);

        $this->pdo->beginTransaction();
        try {
            $sql = 'UPDATE usuarios SET id_rol = :role, nombre = :name, email = :email, estado = :status';
            $parameters = [
                'id' => $id,
                'role' => $roleId,
                'name' => $values['name'],
                'email' => $values['email'],
                'status' => $values['status'],
            ];
            if ($values['password'] !== '') {
                $sql .= ', password_hash = :password';
                $parameters['password'] = password_hash($values['password'], PASSWORD_DEFAULT);
            }
            $sql .= ' WHERE id_usuario = :id';
            $statement = $this->pdo->prepare($sql);
            $statement->execute($parameters);
            $this->ensureClientProfile($id, $values['role'], $values['name'], $values['phone']);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    public function delete(int $id, int $actorId): void
    {
        $existing = $this->find($id);
        if ($existing === null) {
            throw new InvalidArgumentException('El usuario no existe.');
        }
        if ($id === $actorId) {
            throw new InvalidArgumentException('No puedes eliminar tu propia cuenta.');
        }
        $this->protectLastAdmin($existing, '', 'inactivo');

        $checks = [
            'ventas' => 'SELECT COUNT(*) FROM ventas WHERE id_usuario = :id',
            'compras' => 'SELECT COUNT(*) FROM compras WHERE id_usuario = :id',
            'movimientos' => 'SELECT COUNT(*) FROM movimientos_stock WHERE id_usuario = :id',
            'auditoría' => 'SELECT COUNT(*) FROM log_sistema WHERE id_usuario = :id',
            'pedidos de cliente' => 'SELECT COUNT(*) FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.id_cliente WHERE c.id_usuario = :id',
            'direcciones' => 'SELECT COUNT(*) FROM direcciones d INNER JOIN clientes c ON c.id_cliente = d.id_cliente WHERE c.id_usuario = :id',
        ];
        $references = 0;
        foreach ($checks as $sql) {
            $statement = $this->pdo->prepare($sql);
            $statement->execute(['id' => $id]);
            $references += (int) $statement->fetchColumn();
        }
        if ($references > 0) {
            throw new InvalidArgumentException('El usuario tiene operaciones relacionadas. Desactívalo para conservar la trazabilidad.');
        }

        $this->pdo->beginTransaction();
        try {
            $client = $this->pdo->prepare('DELETE FROM clientes WHERE id_usuario = :id');
            $client->execute(['id' => $id]);
            $statement = $this->pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
            $statement->execute(['id' => $id]);
            $this->pdo->commit();
        } catch (PDOException $exception) {
            $this->rollBack();
            throw new InvalidArgumentException('No se puede eliminar porque conserva relaciones. Desactiva el usuario.', 0, $exception);
        }
    }

    /** @param array<string, mixed> $data @return array{name:string,email:string,phone:string,role:string,status:string,password:string} */
    private function validate(array $data, bool $passwordRequired): array
    {
        $name = trim((string) ($data['nombre'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['telefono'] ?? ''));
        $role = strtoupper(trim((string) ($data['rol'] ?? '')));
        $status = (string) ($data['estado'] ?? 'activo');
        $password = (string) ($data['password'] ?? '');
        $confirmation = (string) ($data['password_confirmation'] ?? '');

        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('El nombre es obligatorio y admite hasta 150 caracteres.');
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 150) {
            throw new InvalidArgumentException('El correo electrónico no es válido.');
        }
        if (!array_key_exists($role, self::PERMISSIONS)) {
            throw new InvalidArgumentException('El rol seleccionado no está permitido.');
        }
        if (!in_array($status, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('El estado no es válido.');
        }
        if ($phone !== '' && (mb_strlen($phone) > 20 || preg_match('/^[0-9+()\s-]+$/', $phone) !== 1)) {
            throw new InvalidArgumentException('El teléfono no es válido.');
        }
        if ($passwordRequired && strlen($password) < 8) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres.');
        }
        if ($password !== '' && (strlen($password) < 8 || $password !== $confirmation)) {
            throw new InvalidArgumentException('La contraseña debe tener al menos 8 caracteres y coincidir con su confirmación.');
        }

        return compact('name', 'email', 'phone', 'role', 'status', 'password');
    }

    private function roleId(string $code): int
    {
        $statement = $this->pdo->prepare("SELECT id_rol FROM roles WHERE codigo = :code AND estado = 'activo' LIMIT 1");
        $statement->execute(['code' => $code]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            throw new InvalidArgumentException('El rol no existe o está inactivo.');
        }

        return (int) $id;
    }

    private function assertEmailAvailable(string $email, ?int $excludeId = null): void
    {
        $sql = 'SELECT 1 FROM usuarios WHERE email = :email';
        $parameters = ['email' => $email];
        if ($excludeId !== null) {
            $sql .= ' AND id_usuario <> :id';
            $parameters['id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);
        if ($statement->fetchColumn() !== false) {
            throw new InvalidArgumentException('El correo ya está registrado.');
        }
    }

    /** @param array<string, mixed> $existing */
    private function protectLastAdmin(array $existing, string $newRole, string $newStatus): void
    {
        if ($existing['rol'] !== 'ADMIN' || $existing['estado'] !== 'activo') {
            return;
        }
        if ($newRole === 'ADMIN' && $newStatus === 'activo') {
            return;
        }
        $count = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE r.codigo = 'ADMIN' AND u.estado = 'activo'"
        )->fetchColumn();
        if ($count <= 1) {
            throw new InvalidArgumentException('Debe conservarse al menos un administrador activo.');
        }
    }

    private function ensureClientProfile(int $userId, string $role, string $name, string $phone): void
    {
        if ($role !== 'CLIENTE') {
            return;
        }
        $statement = $this->pdo->prepare(
            'INSERT INTO clientes (id_usuario, nombre, telefono, estado)
             VALUES (:user, :name, :phone, \'activo\')
             ON DUPLICATE KEY UPDATE nombre = VALUES(nombre), telefono = VALUES(telefono), estado = VALUES(estado)'
        );
        $statement->execute(['user' => $userId, 'name' => $name, 'phone' => $phone !== '' ? $phone : null]);
    }

    private function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
