<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Catalog\Application;

use InvalidArgumentException;
use RedInsumos\Modules\Catalog\Domain\Supplier;
use RedInsumos\Modules\Catalog\Domain\SupplierRepository;

final class ManageSuppliers
{
    public function __construct(
        private SupplierRepository $suppliers
    ) {
    }

    /** @return list<Supplier> */
    public function list(): array
    {
        return $this->suppliers->allForAdmin();
    }

    public function find(int $id): ?Supplier
    {
        return $this->suppliers->findById($id);
    }

    public function create(array $data): void
    {
        $data = $this->normalize($data);

        $this->validate($data);

        if (
            $data['nit'] !== null
            && $this->suppliers->existsNit($data['nit'])
        ) {
            throw new InvalidArgumentException(
                'Ya existe un proveedor con ese NIT.'
            );
        }

        $this->suppliers->create($data);
    }

    public function update(int $id, array $data): void
    {
        if ($this->suppliers->findById($id) === null) {
            throw new InvalidArgumentException(
                'El proveedor no existe.'
            );
        }

        $data = $this->normalize($data);

        $this->validate($data);

        if (
            $data['nit'] !== null
            && $this->suppliers->existsNit(
                $data['nit'],
                $id
            )
        ) {
            throw new InvalidArgumentException(
                'Ya existe otro proveedor con ese NIT.'
            );
        }

        $this->suppliers->update($id, $data);
    }

    public function toggleStatus(int $id): void
    {
        if ($this->suppliers->findById($id) === null) {
            throw new InvalidArgumentException(
                'El proveedor no existe.'
            );
        }

        $this->suppliers->toggleStatus($id);
    }

    private function normalize(array $data): array
    {
        return [
            'nit' => $this->nullableString($data['nit'] ?? null),
            'razon_social' => trim(
                (string) ($data['razon_social'] ?? '')
            ),
            'nombre_comercial' => $this->nullableString(
                $data['nombre_comercial'] ?? null
            ),
            'contacto' => $this->nullableString(
                $data['contacto'] ?? null
            ),
            'telefono' => $this->nullableString(
                $data['telefono'] ?? null
            ),
            'correo' => $this->nullableString(
                $data['correo'] ?? null
            ),
            'direccion' => $this->nullableString(
                $data['direccion'] ?? null
            ),
            'municipio' => $this->nullableString(
                $data['municipio'] ?? null
            ),
            'departamento' => $this->nullableString(
                $data['departamento'] ?? null
            ),
            'tiempo_entrega_dias' => $this->nullableInt(
                $data['tiempo_entrega_dias'] ?? null
            ),
        ];
    }

    private function validate(array $data): void
    {
        if (
            mb_strlen($data['razon_social']) < 2
            || mb_strlen($data['razon_social']) > 150
        ) {
            throw new InvalidArgumentException(
                'La razón social debe tener entre 2 y 150 caracteres.'
            );
        }

        if ($data['correo'] !== null) {
            if (filter_var($data['correo'], FILTER_VALIDATE_EMAIL) === false) {
                throw new InvalidArgumentException(
                    'El correo electrónico no es válido.'
                );
            }

            if (mb_strlen($data['correo']) > 150) {
                throw new InvalidArgumentException(
                    'El correo electrónico no puede superar los 150 caracteres.'
                );
            }
        }

        if (
            $data['tiempo_entrega_dias'] !== null
            && $data['tiempo_entrega_dias'] < 0
        ) {
            throw new InvalidArgumentException(
                'El tiempo de entrega no puede ser negativo.'
            );
        }
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $result = filter_var($value, FILTER_VALIDATE_INT);

        return $result === false ? null : (int) $result;
    }
}