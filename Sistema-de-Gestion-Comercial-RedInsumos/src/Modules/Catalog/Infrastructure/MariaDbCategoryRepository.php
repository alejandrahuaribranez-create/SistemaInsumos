<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Infrastructure;

use PDO;
use RedInsumos\Modules\Catalog\Domain\Category;
use RedInsumos\Modules\Catalog\Domain\CategoryRepository;

final class MariaDbCategoryRepository implements CategoryRepository
{
    public function __construct(
        private PDO $pdo
    ) {
    }

    public function all(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                id_categoria,
                id_padre,
                nombre,
                descripcion,
                estado
             FROM categorias
             ORDER BY nombre"
        );

        return array_map(
            fn (array $row): Category => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function allActive(): array
    {
        $statement = $this->pdo->query(
            "SELECT
                id_categoria,
                id_padre,
                nombre,
                descripcion,
                estado
             FROM categorias
             WHERE estado = 'activo'
             ORDER BY nombre"
        );

        return array_map(
            fn (array $row): Category => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?Category
    {
        $statement = $this->pdo->prepare(
            "SELECT
                id_categoria,
                id_padre,
                nombre,
                descripcion,
                estado
             FROM categorias
             WHERE id_categoria = :id
             LIMIT 1"
        );

        $statement->execute([
            'id' => $id
        ]);

        $row = $statement->fetch();

        return is_array($row)
            ? $this->map($row)
            : null;
    }

    public function existsByName(string $name, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM categorias WHERE LOWER(nombre) = LOWER(:name)';
        $parameters = ['name' => $name];

        if ($excludeId !== null) {
            $sql .= ' AND id_categoria <> :exclude_id';
            $parameters['exclude_id'] = $excludeId;
        }

        $sql .= ' LIMIT 1';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchColumn() !== false;
    }

    public function create(
        string $name,
        ?string $description,
        ?int $parentId
    ): int {
        $statement = $this->pdo->prepare(
            "INSERT INTO categorias
                (id_padre, nombre, descripcion, estado)
             VALUES
                (:parent_id, :name, :description, 'activo')"
        );

        $statement->execute([
            'parent_id' => $parentId,
            'name' => $name,
            'description' => $description,
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $name,
        ?string $description,
        ?int $parentId
    ): void {
        $statement = $this->pdo->prepare(
            "UPDATE categorias
             SET
                id_padre = :parent_id,
                nombre = :name,
                descripcion = :description
             WHERE id_categoria = :id"
        );

        $statement->execute([
            'id' => $id,
            'parent_id' => $parentId,
            'name' => $name,
            'description' => $description,
        ]);
    }

    public function setStatus(
        int $id,
        string $status
    ): void {
        $statement = $this->pdo->prepare(
            "UPDATE categorias
             SET estado = :status
             WHERE id_categoria = :id"
        );

        $statement->execute([
            'id' => $id,
            'status' => $status,
        ]);
    }

    public function referenceCounts(int $id): array
    {
        $products = $this->pdo->prepare('SELECT COUNT(*) FROM productos WHERE id_categoria = :id');
        $products->execute(['id' => $id]);
        $children = $this->pdo->prepare('SELECT COUNT(*) FROM categorias WHERE id_padre = :id');
        $children->execute(['id' => $id]);

        return [
            'products' => (int) $products->fetchColumn(),
            'children' => (int) $children->fetchColumn(),
        ];
    }

    public function delete(int $id): void
    {
        $statement = $this->pdo->prepare('DELETE FROM categorias WHERE id_categoria = :id');
        $statement->execute(['id' => $id]);
    }

    private function map(array $row): Category
    {
        return new Category(
            (int) $row['id_categoria'],
            (string) $row['nombre'],
            $row['descripcion'] !== null
                ? (string) $row['descripcion']
                : null,
            $row['id_padre'] !== null
                ? (int) $row['id_padre']
                : null,
            (string) $row['estado']
        );
    }
}
