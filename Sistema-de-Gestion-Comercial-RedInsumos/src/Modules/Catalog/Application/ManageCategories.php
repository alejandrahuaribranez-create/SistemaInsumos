<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use RedInsumos\Modules\Catalog\Domain\CategoryRepository;
use InvalidArgumentException;

final class ManageCategories
{
    public function __construct(
        private CategoryRepository $categories
    ) {
    }

    public function list(): array
    {
        return $this->categories->all();
    }

    public function find(int $id)
    {
        return $id > 0
            ? $this->categories->findById($id)
            : null;
    }

    /** @param array<string, mixed> $data */
    public function create(array $data): int
    {
        $name = trim((string) ($data['nombre'] ?? ''));
        $description = trim((string) ($data['descripcion'] ?? ''));
        $parentId = $this->normalizeParentId($data['id_padre'] ?? null);

        $this->validateName($name);
        $this->validateUniqueName($name);
        $this->validateParent($parentId);

        return $this->categories->create(
            $name,
            $description !== '' ? $description : null,
            $parentId
        );
    }

    /** @param array<string, mixed> $data */
    public function update(int $id, array $data): void
    {
        if ($id <= 0) {
            throw new InvalidArgumentException('Categoría inválida.');
        }

        $name = trim((string) ($data['nombre'] ?? ''));
        $description = trim((string) ($data['descripcion'] ?? ''));
        $parentId = $this->normalizeParentId($data['id_padre'] ?? null);

        if ($parentId === $id) {
            throw new InvalidArgumentException(
                'Una categoría no puede ser su propia categoría padre.'
            );
        }

        $this->validateName($name);
        $this->validateUniqueName($name, $id);
        $this->validateParent($parentId, $id);

        $this->categories->update(
            $id,
            $name,
            $description !== '' ? $description : null,
            $parentId
        );
    }

    public function toggleStatus(int $id): void
    {
        $category = $this->find($id);

        if ($category === null) {
            throw new InvalidArgumentException('La categoría no existe.');
        }

        $newStatus = $category->status === 'activo'
            ? 'inactivo'
            : 'activo';

        $this->categories->setStatus($id, $newStatus);
    }

    public function delete(int $id): void
    {
        $category = $this->find($id);

        if ($category === null) {
            throw new InvalidArgumentException('La categoría no existe.');
        }

        $references = $this->categories->referenceCounts($id);

        if ($references['products'] > 0 || $references['children'] > 0) {
            throw new InvalidArgumentException(sprintf(
                'No se puede eliminar: tiene %d producto(s) y %d subcategoría(s). Desactívala para conservar las relaciones.',
                $references['products'],
                $references['children']
            ));
        }

        $this->categories->delete($id);
    }

    private function validateName(string $name): void
    {
        if ($name === '') {
            throw new InvalidArgumentException(
                'El nombre de la categoría es obligatorio.'
            );
        }

        if (mb_strlen($name) > 100) {
            throw new InvalidArgumentException(
                'El nombre de la categoría no puede superar los 100 caracteres.'
            );
        }
    }

    private function validateUniqueName(string $name, ?int $excludeId = null): void
    {
        if ($this->categories->existsByName($name, $excludeId)) {
            throw new InvalidArgumentException('Ya existe una categoría con ese nombre.');
        }
    }

    private function validateParent(?int $parentId, ?int $categoryId = null): void
    {
        if ($parentId === null) {
            return;
        }

        $parent = $this->categories->findById($parentId);
        if ($parent === null) {
            throw new InvalidArgumentException('La categoría padre no existe.');
        }

        $visited = [];
        while ($parent !== null) {
            if ($categoryId !== null && $parent->id === $categoryId) {
                throw new InvalidArgumentException('La jerarquía de categorías no puede contener ciclos.');
            }
            if (isset($visited[$parent->id])) {
                throw new InvalidArgumentException('La jerarquía de categorías existente contiene un ciclo.');
            }
            $visited[$parent->id] = true;
            $parent = $parent->parentId !== null ? $this->categories->findById($parent->parentId) : null;
        }
    }

    private function normalizeParentId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }
}
