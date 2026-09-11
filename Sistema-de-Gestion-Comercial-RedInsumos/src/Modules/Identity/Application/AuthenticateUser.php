<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Application;

use RedInsumos\Modules\Identity\Domain\User;
use RedInsumos\Modules\Identity\Domain\UserRepository;

final class AuthenticateUser
{
    public function __construct(private UserRepository $users)
    {
    }

    public function execute(string $email, string $password): ?User
    {
        $user = $this->users->findByEmail(strtolower(trim($email)));

        if ($user === null || !$user->isActive() || !password_verify($password, $user->passwordHash())) {
            return null;
        }

        $this->users->recordAccess($user->id());

        return $user;
    }
}
