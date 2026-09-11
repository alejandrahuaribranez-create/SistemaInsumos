<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

interface SupplierRepository
{
    /** @return list<Supplier> */
    public function allForAdmin(): array;

    public function findById(int $id): ?Supplier;

    public function existsNit(
        string $nit,
        ?int $excludeId = null
    ): bool;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function toggleStatus(int $id): void;
}