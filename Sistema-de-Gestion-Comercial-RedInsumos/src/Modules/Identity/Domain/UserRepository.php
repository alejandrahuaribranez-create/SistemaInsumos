<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Domain;

interface UserRepository
{
    public function findByEmail(string $email): ?User;

    public function emailExists(string $email): bool;

    public function registerClient(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        string $passwordHash
    ): int;

    public function recordAccess(int $userId): void;
}
