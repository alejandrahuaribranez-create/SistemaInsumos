<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use RedInsumos\Modules\Catalog\Domain\CategoryRepository;

final class ListActiveCategories
{
    public function __construct(private CategoryRepository $categories)
    {
    }

    public function execute(): array
    {
        return $this->categories->allActive();
    }
}
