<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use RedInsumos\Modules\Catalog\Domain\Product;
use RedInsumos\Modules\Catalog\Domain\ProductRepository;

final class GetProduct
{
    public function __construct(private ProductRepository $products)
    {
    }

    public function execute(int $id): ?Product
    {
        return $id > 0 ? $this->products->findById($id) : null;
    }
}
