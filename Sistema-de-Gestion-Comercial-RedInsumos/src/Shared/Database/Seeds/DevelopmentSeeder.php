<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Database\Seeds;

use PDO;
use RedInsumos\Shared\Config\Environment;
use RuntimeException;
use Throwable;

final class DevelopmentSeeder
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return array{users: int, products: int} */
    public function run(): array
    {
        if (Environment::get('APP_ENV') !== 'local') {
            throw new RuntimeException('El seeder de demostración solo puede ejecutarse en APP_ENV=local.');
        }

        $this->pdo->beginTransaction();

        try {
            $users = $this->seedUsers(Environment::required('DEMO_PASSWORD'));
            $products = $this->seedCatalogWhenEmpty();
            $this->pdo->commit();

            return ['users' => $users, 'products' => $products];
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function seedUsers(string $password): int
    {
        $accounts = [
            ['ADMIN', 'Administrador Demo', 'admin@redinsumos.local'],
            ['VENDEDOR', 'Vendedor Demo', 'vendedor@redinsumos.local'],
            ['ALMACENERO', 'Almacenero Demo', 'almacenero@redinsumos.local'],
            ['CONTABLE', 'Contable Demo', 'contable@redinsumos.local'],
        ];
        $roleStatement = $this->pdo->prepare(
            "SELECT id_rol FROM roles WHERE codigo = :role AND estado = 'activo' LIMIT 1"
        );
        $userStatement = $this->pdo->prepare(
            "INSERT INTO usuarios (id_rol, nombre, email, password_hash, estado)
             VALUES (:role_id, :name, :email, :password_hash, 'activo')
             ON DUPLICATE KEY UPDATE
                 id_rol = VALUES(id_rol),
                 nombre = VALUES(nombre),
                 password_hash = VALUES(password_hash),
                 estado = 'activo'"
        );

        foreach ($accounts as [$role, $name, $email]) {
            $roleStatement->execute(['role' => $role]);
            $roleId = $roleStatement->fetchColumn();

            if ($roleId === false) {
                throw new RuntimeException(sprintf('No existe el rol requerido %s.', $role));
            }

            $userStatement->execute([
                'role_id' => (int) $roleId,
                'name' => $name,
                'email' => $email,
                'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            ]);
        }

        return count($accounts);
    }

    private function seedCatalogWhenEmpty(): int
    {
        if ((int) $this->pdo->query('SELECT COUNT(*) FROM productos')->fetchColumn() > 0) {
            return 0;
        }

        $cableado = $this->ensureCategory('Cableado', 'Cables y conectividad para redes.');
        $routers = $this->ensureCategory('Routers', 'Routers para redes empresariales y domésticas.');
        $mikrotik = $this->ensureBrand('MikroTik');
        $ubiquiti = $this->ensureBrand('Ubiquiti');
        $providerOne = $this->ensureSupplier('Redes Bolivia SRL', 'Redes Bolivia');
        $providerTwo = $this->ensureSupplier('Conectividad Andina SRL', 'Conectividad Andina');

        $products = [
            [$cableado, $ubiquiti, $providerOne, 'CBL-UTP-C6-305', 'Cable UTP Cat6 305 m', 'Bobina de cable UTP categoría 6 para instalaciones de red.', 650.00, 890.00, 12],
            [$routers, $mikrotik, $providerOne, 'RTR-HAP-AX2', 'Router MikroTik hAP ax2', 'Router WiFi 6 de doble banda para oficina y hogar.', 780.00, 1050.00, 8],
            [$cableado, $ubiquiti, $providerTwo, 'SW-USW-LITE-8', 'Switch UniFi Lite 8 PoE', 'Switch administrable de ocho puertos con soporte PoE.', 1250.00, 1590.00, 5],
            [$cableado, $ubiquiti, $providerTwo, 'CON-RJ45-C6-100', 'Conectores RJ45 Cat6 x100', 'Paquete de conectores RJ45 para cable categoría 6.', 120.00, 180.00, 25],
            [$cableado, $mikrotik, $providerOne, 'TOOL-CRIMP-PRO', 'Crimpadora profesional RJ45', 'Herramienta de crimpado para conectores de red.', 95.00, 145.00, 15],
        ];
        $statement = $this->pdo->prepare(
            "INSERT INTO productos
                (id_categoria, id_marca, id_proveedor, codigo, nombre, descripcion,
                 precio_compra, precio_venta, stock_actual, stock_minimo, stock_maximo)
             VALUES
                (:category_id, :brand_id, :supplier_id, :code, :name, :description,
                 :purchase_price, :sale_price, :stock, 2, 100)"
        );

        foreach ($products as $product) {
            $statement->execute([
                'category_id' => $product[0],
                'brand_id' => $product[1],
                'supplier_id' => $product[2],
                'code' => $product[3],
                'name' => $product[4],
                'description' => $product[5],
                'purchase_price' => $product[6],
                'sale_price' => $product[7],
                'stock' => $product[8],
            ]);
        }

        return count($products);
    }

    private function ensureCategory(string $name, string $description): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO categorias (nombre, descripcion, estado)
             VALUES (:name, :description, 'activo')
             ON DUPLICATE KEY UPDATE estado = 'activo'"
        );
        $statement->execute(['name' => $name, 'description' => $description]);

        return $this->idByName('categorias', 'id_categoria', $name);
    }

    private function ensureBrand(string $name): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO marcas (nombre, estado) VALUES (:name, 'activo')
             ON DUPLICATE KEY UPDATE estado = 'activo'"
        );
        $statement->execute(['name' => $name]);

        return $this->idByName('marcas', 'id_marca', $name);
    }

    private function ensureSupplier(string $legalName, string $commercialName): int
    {
        $select = $this->pdo->prepare('SELECT id_proveedor FROM proveedores WHERE razon_social = :name LIMIT 1');
        $select->execute(['name' => $legalName]);
        $existing = $select->fetchColumn();

        if ($existing !== false) {
            return (int) $existing;
        }

        $insert = $this->pdo->prepare(
            "INSERT INTO proveedores (razon_social, nombre_comercial, estado)
             VALUES (:legal_name, :commercial_name, 'activo')"
        );
        $insert->execute(['legal_name' => $legalName, 'commercial_name' => $commercialName]);

        return (int) $this->pdo->lastInsertId();
    }

    private function idByName(string $table, string $idColumn, string $name): int
    {
        $allowed = [
            'categorias.id_categoria',
            'marcas.id_marca',
        ];

        if (!in_array($table . '.' . $idColumn, $allowed, true)) {
            throw new RuntimeException('Entidad de seeder no permitida.');
        }

        $statement = $this->pdo->prepare(
            sprintf('SELECT %s FROM %s WHERE nombre = :name LIMIT 1', $idColumn, $table)
        );
        $statement->execute(['name' => $name]);
        $id = $statement->fetchColumn();

        if ($id === false) {
            throw new RuntimeException(sprintf('No se pudo preparar el dato %s.', $name));
        }

        return (int) $id;
    }
}
