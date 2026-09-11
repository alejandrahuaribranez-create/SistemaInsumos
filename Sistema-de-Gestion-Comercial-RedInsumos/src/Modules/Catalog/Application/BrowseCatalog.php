<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use RedInsumos\Modules\Catalog\Domain\ProductRepository;

final class BrowseCatalog
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function execute(string $term = ''): array
    {
        return $this->products->search(trim($term));
    }
}
