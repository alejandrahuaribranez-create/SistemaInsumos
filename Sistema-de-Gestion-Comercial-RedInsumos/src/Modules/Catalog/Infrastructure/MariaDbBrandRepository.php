<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Infrastructure;

use PDO;
use RedInsumos\Modules\Catalog\Domain\Brand;
use RedInsumos\Modules\Catalog\Domain\BrandRepository;

final class MariaDbBrandRepository implements BrandRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function allForAdmin(): array
    {
        $statement = $this->pdo->query(
            "SELECT id_marca, nombre, estado
             FROM marcas
             ORDER BY nombre"
        );

        return array_map(
            fn (array $row): Brand => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?Brand
    {
        $statement = $this->pdo->prepare(
            "SELECT id_marca, nombre, estado
             FROM marcas
             WHERE id_marca = :id
             LIMIT 1"
        );

        $statement->execute(['id' => $id]);

        $row = $statement->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    public function existsName(string $name, ?int $excludeId = null): bool
    {
        $sql = "SELECT COUNT(*)
                FROM marcas
                WHERE LOWER(nombre) = LOWER(:nombre)";

        $parameters = ['nombre' => $name];

        if ($excludeId !== null) {
            $sql .= ' AND id_marca <> :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return (int) $statement->fetchColumn() > 0;
    }

    public function create(string $name): int
    {
        $statement = $this->pdo->prepare(
            "INSERT INTO marcas (nombre, estado)
             VALUES (:nombre, 'activo')"
        );

        $statement->execute(['nombre' => $name]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, string $name): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE marcas
             SET nombre = :nombre
             WHERE id_marca = :id"
        );

        $statement->execute([
            'id' => $id,
            'nombre' => $name,
        ]);
    }

    public function toggleStatus(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE marcas
             SET estado = CASE
                 WHEN estado = 'activo' THEN 'inactivo'
                 ELSE 'activo'
             END
             WHERE id_marca = :id"
        );

        $statement->execute(['id' => $id]);
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Brand
    {
        return new Brand(
            (int) $row['id_marca'],
            (string) $row['nombre'],
            (string) $row['estado']
        );
    }
}