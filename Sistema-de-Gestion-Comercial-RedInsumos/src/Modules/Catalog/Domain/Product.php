<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

final class Product
{
    public function __construct(
        public readonly int $id,
        public readonly string $code,
        public readonly string $name,
        public readonly ?string $description,
        public readonly float $price,
        public readonly int $stock,
        public readonly string $category,
        public readonly ?string $brand,
        public readonly ?string $supplier,
        /** @var array<string|int, mixed> */
        public readonly array $images = []
    ) {
    }
}
