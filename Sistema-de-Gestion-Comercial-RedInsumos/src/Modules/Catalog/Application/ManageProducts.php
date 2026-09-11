<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use InvalidArgumentException;
use PDO;

final class ManageProducts
{
    public function __construct(private PDO $pdo)
    {
    }

    /** @return list<array<string, mixed>> */
    public function all(): array
    {
        return $this->pdo->query(
            'SELECT p.*, c.nombre AS categoria, m.nombre AS marca,
                    COALESCE(pr.nombre_comercial, pr.razon_social) AS proveedor
             FROM productos p
             INNER JOIN categorias c ON c.id_categoria = p.id_categoria
             LEFT JOIN marcas m ON m.id_marca = p.id_marca
             LEFT JOIN proveedores pr ON pr.id_proveedor = p.id_proveedor
             ORDER BY p.nombre'
        )->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $statement = $this->pdo->prepare('SELECT * FROM productos WHERE id_producto = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    /** @return array{categories:list<array<string,mixed>>,brands:list<array<string,mixed>>,suppliers:list<array<string,mixed>>} */
    public function relations(): array
    {
        return [
            'categories' => $this->pdo->query("SELECT id_categoria, nombre FROM categorias WHERE estado = 'activo' ORDER BY nombre")->fetchAll(),
            'brands' => $this->pdo->query("SELECT id_marca, nombre FROM marcas WHERE estado = 'activo' ORDER BY nombre")->fetchAll(),
            'suppliers' => $this->pdo->query("SELECT id_proveedor, COALESCE(nombre_comercial, razon_social) AS nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre")->fetchAll(),
        ];
    }

    /** @param array<string, mixed> $data */
    public function create(array $data, ?string $imagePath): int
    {
        $values = $this->validate($data);
        $this->assertUniqueCode($values['code']);
        $statement = $this->pdo->prepare(
            'INSERT INTO productos
                (id_categoria, id_marca, id_proveedor, codigo, nombre, descripcion, modelo,
                 unidad_medida, imagenes, precio_compra, precio_venta, stock_actual,
                 stock_minimo, stock_maximo, ubicacion, estado)
             VALUES
                (:category, :brand, :supplier, :code, :name, :description, :model,
                 :unit, :images, :purchase_price, :sale_price, 0, :minimum, :maximum,
                 :location, :status)'
        );
        $statement->execute($this->parameters($values, $imagePath));
        return (int) $this->pdo->lastInsertId();
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data, ?string $imagePath): void
    {
        $existing = $this->find($id);
        if ($existing === null) {
            throw new InvalidArgumentException('El producto no existe.');
        }
        $values = $this->validate($data);
        $this->assertUniqueCode($values['code'], $id);
        $currentImages = is_string($existing['imagenes']) ? json_decode($existing['imagenes'], true) : [];
        $images = $imagePath ?? (is_array($currentImages) ? $currentImages : []);
        $parameters = $this->parameters($values, $images !== [] ? $images : null);
        $parameters['id'] = $id;
        $statement = $this->pdo->prepare(
            'UPDATE productos SET id_categoria = :category, id_marca = :brand,
                id_proveedor = :supplier, codigo = :code, nombre = :name,
                descripcion = :description, modelo = :model, unidad_medida = :unit,
                imagenes = :images, precio_compra = :purchase_price,
                precio_venta = :sale_price, stock_minimo = :minimum,
                stock_maximo = :maximum, ubicacion = :location, estado = :status
             WHERE id_producto = :id'
        );
        $statement->execute($parameters);
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function validate(array $data): array
    {
        $code = strtoupper(trim((string) ($data['codigo'] ?? '')));
        $name = trim((string) ($data['nombre'] ?? ''));
        $description = trim((string) ($data['descripcion'] ?? ''));
        $model = trim((string) ($data['modelo'] ?? ''));
        $unit = strtoupper(trim((string) ($data['unidad_medida'] ?? 'UNIDAD')));
        $category = (int) ($data['id_categoria'] ?? 0);
        $brand = $this->nullableId($data['id_marca'] ?? null);
        $supplier = $this->nullableId($data['id_proveedor'] ?? null);
        $purchasePrice = $this->money($data['precio_compra'] ?? null, 'precio de compra');
        $salePrice = $this->money($data['precio_venta'] ?? null, 'precio de venta');
        $minimum = filter_var($data['stock_minimo'] ?? null, FILTER_VALIDATE_INT);
        $maximum = filter_var($data['stock_maximo'] ?? null, FILTER_VALIDATE_INT);
        $location = trim((string) ($data['ubicacion'] ?? ''));
        $status = (string) ($data['estado'] ?? 'activo');

        if ($code === '' || mb_strlen($code) > 50 || preg_match('/^[A-Z0-9._-]+$/', $code) !== 1) {
            throw new InvalidArgumentException('El código es obligatorio y solo admite letras, números, punto, guion y guion bajo.');
        }
        if ($name === '' || mb_strlen($name) > 200) {
            throw new InvalidArgumentException('El nombre es obligatorio y admite hasta 200 caracteres.');
        }
        if (mb_strlen($description) > 5000 || mb_strlen($model) > 100 || mb_strlen($unit) > 30 || mb_strlen($location) > 100) {
            throw new InvalidArgumentException('Uno de los textos supera la longitud permitida.');
        }
        if ($minimum === false || $minimum < 0 || $maximum === false || $maximum < $minimum) {
            throw new InvalidArgumentException('Los límites de stock no son válidos.');
        }
        if (!in_array($status, ['activo', 'inactivo'], true)) {
            throw new InvalidArgumentException('El estado no es válido.');
        }
        $this->assertActiveRelation('categorias', 'id_categoria', $category, false);
        $this->assertActiveRelation('marcas', 'id_marca', $brand, true);
        $this->assertActiveRelation('proveedores', 'id_proveedor', $supplier, true);

        return compact('code', 'name', 'description', 'model', 'unit', 'category', 'brand', 'supplier', 'purchasePrice', 'salePrice', 'minimum', 'maximum', 'location', 'status');
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    private function parameters(array $values, string|array|null $images): array
    {
        return [
            'category' => $values['category'], 'brand' => $values['brand'], 'supplier' => $values['supplier'],
            'code' => $values['code'], 'name' => $values['name'],
            'description' => $values['description'] !== '' ? $values['description'] : null,
            'model' => $values['model'] !== '' ? $values['model'] : null, 'unit' => $values['unit'],
            'images' => $images !== null ? json_encode(is_string($images) ? ['primary' => $images] : $images, JSON_UNESCAPED_SLASHES) : null,
            'purchase_price' => $values['purchasePrice'], 'sale_price' => $values['salePrice'],
            'minimum' => $values['minimum'], 'maximum' => $values['maximum'],
            'location' => $values['location'] !== '' ? $values['location'] : null, 'status' => $values['status'],
        ];
    }

    private function assertUniqueCode(string $code, ?int $excludeId = null): void
    {
        $sql = 'SELECT 1 FROM productos WHERE codigo = :code';
        $parameters = ['code' => $code];
        if ($excludeId !== null) {
            $sql .= ' AND id_producto <> :id';
            $parameters['id'] = $excludeId;
        }
        $statement = $this->pdo->prepare($sql . ' LIMIT 1');
        $statement->execute($parameters);
        if ($statement->fetchColumn() !== false) {
            throw new InvalidArgumentException('Ya existe un producto con ese código.');
        }
    }

    private function assertActiveRelation(string $table, string $column, ?int $id, bool $nullable): void
    {
        $allowed = ['categorias.id_categoria', 'marcas.id_marca', 'proveedores.id_proveedor'];
        if ($id === null && $nullable) {
            return;
        }
        if ($id === null || $id <= 0 || !in_array($table . '.' . $column, $allowed, true)) {
            throw new InvalidArgumentException('Una relación del producto no es válida.');
        }
        $statement = $this->pdo->prepare("SELECT 1 FROM $table WHERE $column = :id AND estado = 'activo' LIMIT 1");
        $statement->execute(['id' => $id]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException('La categoría, marca o proveedor no existe o está inactivo.');
        }
    }

    private function nullableId(mixed $value): ?int
    {
        return $value === null || $value === '' ? null : (int) $value;
    }

    private function money(mixed $value, string $field): string
    {
        $normalized = str_replace(',', '.', trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized) || (float) $normalized < 0) {
            throw new InvalidArgumentException("El $field no es válido.");
        }
        return number_format((float) $normalized, 2, '.', '');
    }
}
