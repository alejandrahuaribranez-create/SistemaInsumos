<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Infrastructure;

use PDO;
use RedInsumos\Modules\Catalog\Domain\Supplier;
use RedInsumos\Modules\Catalog\Domain\SupplierRepository;

final class MariaDbSupplierRepository implements SupplierRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allForAdmin(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                id_proveedor,
                nit,
                razon_social,
                nombre_comercial,
                contacto,
                telefono,
                correo,
                direccion,
                municipio,
                departamento,
                tiempo_entrega_dias,
                estado
             FROM proveedores
             ORDER BY razon_social"
        );

        return array_map(
            fn (array $row): Supplier => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?Supplier
    {
        $statement = $this->pdo->prepare(
            "SELECT
                id_proveedor,
                nit,
                razon_social,
                nombre_comercial,
                contacto,
                telefono,
                correo,
                direccion,
                municipio,
                departamento,
                tiempo_entrega_dias,
                estado
             FROM proveedores
             WHERE id_proveedor = :id
             LIMIT 1"
        );

        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    public function existsNit(
        string $nit,
        ?int $excludeId = null
    ): bool {
        $sql = "SELECT COUNT(*)
                FROM proveedores
                WHERE nit = :nit";

        $parameters = ['nit' => $nit];

        if ($excludeId !== null) {
            $sql .= ' AND id_proveedor <> :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO proveedores (
                nit,
                razon_social,
                nombre_comercial,
                contacto,
                telefono,
                correo,
                direccion,
                municipio,
                departamento,
                tiempo_entrega_dias,
                estado
             ) VALUES (
                :nit,
                :razon_social,
                :nombre_comercial,
                :contacto,
                :telefono,
                :correo,
                :direccion,
                :municipio,
                :departamento,
                :tiempo_entrega_dias,
                'activo'
             )"
        );

        $statement->execute($data);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;

        $statement = $this->pdo->prepare(
            "UPDATE proveedores
             SET
                nit = :nit,
                razon_social = :razon_social,
                nombre_comercial = :nombre_comercial,
                contacto = :contacto,
                telefono = :telefono,
                correo = :correo,
                direccion = :direccion,
                municipio = :municipio,
                departamento = :departamento,
                tiempo_entrega_dias = :tiempo_entrega_dias
             WHERE id_proveedor = :id"
        );

        $statement->execute($data);
    }

    public function toggleStatus(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE proveedores
             SET estado = CASE
                 WHEN estado = 'activo' THEN 'inactivo'
                 ELSE 'activo'
             END
             WHERE id_proveedor = :id"
        );

        $statement->execute(['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Supplier
    {
        return new Supplier(
            (int) $row['id_proveedor'],
            $row['nit'] !== null ? (string) $row['nit'] : null,
            (string) $row['razon_social'],
            $row['nombre_comercial'] !== null
                ? (string) $row['nombre_comercial']
                : null,
            $row['contacto'] !== null
                ? (string) $row['contacto']
                : null,
            $row['telefono'] !== null
                ? (string) $row['telefono']
                : null,
            $row['correo'] !== null
                ? (string) $row['correo']
                : null,
            $row['direccion'] !== null
                ? (string) $row['direccion']
                : null,
            $row['municipio'] !== null
                ? (string) $row['municipio']
                : null,
            $row['departamento'] !== null
                ? (string) $row['departamento']
                : null,
            $row['tiempo_entrega_dias'] !== null
                ? (int) $row['tiempo_entrega_dias']
                : null,
            (string) $row['estado']
        );
    }
}