<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Application;

use RedInsumos\Modules\Identity\Domain\UserRepository;
use RedInsumos\Modules\Identity\Domain\ValidationException;

final class RegisterClient
{
    public function __construct(private UserRepository $users)
    {
    }

    /** @param array<string, mixed> $data */
    public function execute(array $data): int
    {
        $firstName = trim((string) ($data['nombre'] ?? ''));
        $lastName = trim((string) ($data['apellido'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));
        $phone = trim((string) ($data['telefono'] ?? ''));
        $password = (string) ($data['password'] ?? '');
        $confirmation = (string) ($data['password_confirmation'] ?? '');
        $errors = [];

        if ($firstName === '' || mb_strlen($firstName) > 80) {
            $errors['nombre'] = 'Ingresa un nombre válido.';
        }
        if ($lastName === '' || mb_strlen($lastName) > 80) {
            $errors['apellido'] = 'Ingresa un apellido válido.';
        }
        if (filter_var($email, FILTER_VALIDATE_EMAIL) === false || mb_strlen($email) > 150) {
            $errors['email'] = 'Ingresa un correo válido.';
        } elseif ($this->users->emailExists($email)) {
            $errors['email'] = 'El correo ya está registrado.';
        }
        if ($phone === '' || mb_strlen($phone) > 20 || preg_match('/^[0-9+()\s-]+$/', $phone) !== 1) {
            $errors['telefono'] = 'Ingresa un teléfono válido.';
        }
        if (strlen($password) < 8) {
            $errors['password'] = 'La contraseña debe tener al menos 8 caracteres.';
        } elseif ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Las contraseñas no coinciden.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }

        return $this->users->registerClient(
            $firstName,
            $lastName,
            $email,
            $phone,
            password_hash($password, PASSWORD_DEFAULT)
        );
    }
}
