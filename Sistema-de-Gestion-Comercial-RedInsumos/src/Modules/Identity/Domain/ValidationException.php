<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Domain;

use RuntimeException;

final class ValidationException extends RuntimeException
{
    /** @param array<string, string> $errors */
    public function __construct(private array $errors)
    {
        parent::__construct('Los datos proporcionados no son válidos.');
    }

    /** @return array<string, string> */
    public function errors(): array
    {
        return $this->errors;
    }
}
