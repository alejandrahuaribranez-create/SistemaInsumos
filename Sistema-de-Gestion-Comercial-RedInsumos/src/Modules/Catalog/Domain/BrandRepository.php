<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

interface BrandRepository
{
    /** @return list<Brand> */
    public function allForAdmin(): array;

    public function findById(int $id): ?Brand;

    public function existsName(string $name, ?int $excludeId = null): bool;

    public function create(string $name): int;

    public function update(int $id, string $name): void;

    public function toggleStatus(int $id): void;
}