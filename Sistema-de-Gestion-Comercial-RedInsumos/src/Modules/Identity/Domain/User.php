<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Domain;

final class User
{
    public function __construct(
        private int $id,
        private string $name,
        private string $email,
        private string $passwordHash,
        private string $role,
        private string $status
    ) {
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function email(): string
    {
        return $this->email;
    }

    public function passwordHash(): string
    {
        return $this->passwordHash;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function isActive(): bool
    {
        return $this->status === 'activo';
    }
}
