<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Infrastructure;

use PDO;
use RedInsumos\Modules\Identity\Domain\User;
use RedInsumos\Modules\Identity\Domain\UserRepository;
use RuntimeException;
use Throwable;

final class MariaDbUserRepository implements UserRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByEmail(string $email): ?User
    {
        $statement = $this->pdo->prepare(
            'SELECT u.id_usuario, u.nombre, u.email, u.password_hash, u.estado, r.codigo AS rol
             FROM usuarios u
             INNER JOIN roles r ON r.id_rol = u.id_rol
             WHERE u.email = :email
             LIMIT 1'
        );
        $statement->execute(['email' => $email]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return new User(
            (int) $row['id_usuario'],
            (string) $row['nombre'],
            (string) $row['email'],
            (string) $row['password_hash'],
            (string) $row['rol'],
            (string) $row['estado']
        );
    }

    public function emailExists(string $email): bool
    {
        $statement = $this->pdo->prepare('SELECT 1 FROM usuarios WHERE email = :email LIMIT 1');
        $statement->execute(['email' => $email]);

        return $statement->fetchColumn() !== false;
    }

    public function registerClient(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $passwordHash
    ): int {
        $this->pdo->beginTransaction();

        try {
            $roleId = $this->pdo->query("SELECT id_rol FROM roles WHERE codigo = 'CLIENTE' AND estado = 'activo'")
                ->fetchColumn();

            if ($roleId === false) {
                throw new RuntimeException('El rol CLIENTE no está disponible.');
            }

            $fullName = trim($firstName . ' ' . $lastName);
            $userStatement = $this->pdo->prepare(
                'INSERT INTO usuarios (id_rol, nombre, email, password_hash)
                 VALUES (:role_id, :name, :email, :password_hash)'
            );
            $userStatement->execute([
                'role_id' => (int) $roleId,
                'name' => $fullName,
                'email' => $email,
                'password_hash' => $passwordHash,
            ]);
            $userId = (int) $this->pdo->lastInsertId();

            $clientStatement = $this->pdo->prepare(
                'INSERT INTO clientes (id_usuario, nombre, telefono)
                 VALUES (:user_id, :name, :phone)'
            );
            $clientStatement->execute([
                'user_id' => $userId,
                'name' => $fullName,
                'phone' => $phone,
            ]);

            $this->pdo->commit();

            return $userId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    public function recordAccess(int $userId): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE usuarios SET ultimo_acceso = CURRENT_TIMESTAMP WHERE id_usuario = :user_id'
        );
        $statement->execute(['user_id' => $userId]);
    }
}
