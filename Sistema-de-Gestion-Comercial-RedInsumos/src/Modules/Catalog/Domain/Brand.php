<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

final class Brand
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $status
    ) {
    }
}