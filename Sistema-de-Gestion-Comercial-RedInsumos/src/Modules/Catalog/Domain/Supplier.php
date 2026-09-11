<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Domain;

final class Supplier
{
    public function __construct(
        public readonly int $id,
        public readonly ?string $nit,
        public readonly string $businessName,
        public readonly ?string $commercialName,
        public readonly ?string $contact,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly ?string $address,
        public readonly ?string $municipality,
        public readonly ?string $department,
        public readonly ?int $deliveryDays,
        public readonly string $status
    ) {
    }
}