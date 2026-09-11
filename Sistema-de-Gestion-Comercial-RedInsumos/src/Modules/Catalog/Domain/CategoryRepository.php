<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

interface CategoryRepository
{
    /** @return list<Category> */
    public function all(): array;

    /** @return list<Category> */
    public function allActive(): array;

    public function findById(int $id): ?Category;

    public function create(
        string $name,
        ?string $description,
        ?int $parentId
    ): int;

    public function update(
        int $id,
        string $name,
        ?string $description,
        ?int $parentId
    ): void;

    public function setStatus(int $id, string $status): void;
}