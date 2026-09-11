<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use InvalidArgumentException;
use RedInsumos\Modules\Catalog\Domain\Brand;
use RedInsumos\Modules\Catalog\Domain\BrandRepository;

final class ManageBrands
{
    public function __construct(
        private BrandRepository $brands
    ) {
    }

    /** @return list<Brand> */
    public function list(): array
    {
        return $this->brands->allForAdmin();
    }

    public function find(int $id): ?Brand
    {
        return $this->brands->findById($id);
    }

    public function create(string $name): void
    {
        $name = trim($name);

        $this->validateName($name);

        if ($this->brands->existsName($name)) {
            throw new InvalidArgumentException(
                'Ya existe una marca con ese nombre.'
            );
        }

        $this->brands->create($name);
    }

    public function update(int $id, string $name): void
    {
        if ($this->brands->findById($id) === null) {
            throw new InvalidArgumentException(
                'La marca no existe.'
            );
        }

        $name = trim($name);

        $this->validateName($name);

        if ($this->brands->existsName($name, $id)) {
            throw new InvalidArgumentException(
                'Ya existe otra marca con ese nombre.'
            );
        }

        $this->brands->update($id, $name);
    }

    public function toggleStatus(int $id): void
    {
        if ($this->brands->findById($id) === null) {
            throw new InvalidArgumentException(
                'La marca no existe.'
            );
        }

        $this->brands->toggleStatus($id);
    }

    private function validateName(string $name): void
    {
        $length = mb_strlen($name);

        if ($length < 2 || $length > 100) {
            throw new InvalidArgumentException(
                'El nombre de la marca debe tener entre 2 y 100 caracteres.'
            );
        }
    }
}