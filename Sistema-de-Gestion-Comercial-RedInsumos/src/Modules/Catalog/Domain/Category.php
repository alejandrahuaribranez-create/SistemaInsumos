<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

final class Category
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly ?string $description,
        public readonly ?int $parentId = null,
        public readonly string $status = 'activo'
    ) {
    }
}