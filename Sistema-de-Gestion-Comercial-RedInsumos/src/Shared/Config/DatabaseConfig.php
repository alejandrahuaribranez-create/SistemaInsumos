<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Config;

final class DatabaseConfig
{
    public function __construct(
        private string $host,
        private int $port,
        private string $database,
        private string $username,
        private string $password
    ) {
    }

    public static function fromEnvironment(): self
    {
        return new self(
            Environment::required('DB_HOST'),
            (int) Environment::required('DB_PORT'),
            Environment::required('DB_DATABASE'),
            Environment::required('DB_USERNAME'),
            Environment::get('DB_PASSWORD', '') ?? ''
        );
    }

    public function dsn(): string
    {
        return sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $this->host,
            $this->port,
            $this->database
        );
    }

    public function username(): string
    {
        return $this->username;
    }

    public function password(): string
    {
        return $this->password;
    }
}
