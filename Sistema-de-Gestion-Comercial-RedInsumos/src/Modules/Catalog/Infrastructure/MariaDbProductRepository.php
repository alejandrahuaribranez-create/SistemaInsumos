<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Infrastructure;

use PDO;
use RedInsumos\Modules\Catalog\Domain\Product;
use RedInsumos\Modules\Catalog\Domain\ProductRepository;

final class MariaDbProductRepository implements ProductRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function search(string $term): array
    {
        $sql = $this->baseQuery() . " WHERE p.estado = 'activo'";
        $parameters = [];

        if ($term !== '') {
            $sql .= ' AND (p.nombre LIKE :name_term OR p.codigo LIKE :code_term)';
            $parameters = [
                'name_term' => '%' . $term . '%',
                'code_term' => '%' . $term . '%',
            ];
        }

        $sql .= ' ORDER BY p.nombre LIMIT 100';
        $statement = $this->pdo->prepare($sql);
        $statement->execute($parameters);

        return array_map(
            fn (array $row): Product => $this->map($row),
            $statement->fetchAll()
        );
    }

    public function findById(int $id): ?Product
    {
        $statement = $this->pdo->prepare(
            $this->baseQuery() . " WHERE p.id_producto = :id AND p.estado = 'activo' LIMIT 1"
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->map($row) : null;
    }

    private function baseQuery(): string
    {
        return 'SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio_venta,
                       p.stock_actual, p.imagenes, c.nombre AS categoria, m.nombre AS marca,
                       COALESCE(pr.nombre_comercial, pr.razon_social) AS proveedor
                FROM productos p
                INNER JOIN categorias c ON c.id_categoria = p.id_categoria
                LEFT JOIN marcas m ON m.id_marca = p.id_marca
                LEFT JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor';
    }

    /** @param array<string, mixed> $row */
    private function map(array $row): Product
    {
        return new Product(
            (int) $row['id_producto'],
            (string) $row['codigo'],
            (string) $row['nombre'],
            $row['descripcion'] !== null ? (string) $row['descripcion'] : null,
            (float) $row['precio_venta'],
            (int) $row['stock_actual'],
            (string) $row['categoria'],
            $row['marca'] !== null ? (string) $row['marca'] : null,
            $row['proveedor'] !== null ? (string) $row['proveedor'] : null,
            $this->mapImages($row['imagenes'] ?? null)
        );
    }

    /** @return array<string|int, mixed> */
    private function mapImages(mixed $value): array
    {
        if (!is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }
}
