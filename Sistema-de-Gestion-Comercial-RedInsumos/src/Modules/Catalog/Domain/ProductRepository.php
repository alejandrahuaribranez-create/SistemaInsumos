<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

interface ProductRepository
{
    /** @return list<Product> */
    public function search(string $term): array;

    public function findById(int $id): ?Product;
}
