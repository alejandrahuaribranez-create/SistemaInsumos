<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Http;

use ErrorException;
use Throwable;

final class ErrorHandler
{
    public static function register(bool $debug): void
    {
        error_reporting(E_ALL);
        ini_set('display_errors', '0');

        set_error_handler(
            static function (int $severity, string $message, string $file, int $line): bool {
                if (!(error_reporting() & $severity)) {
                    return false;
                }

                throw new ErrorException($message, 0, $severity, $file, $line);
            }
        );

        set_exception_handler(static function (Throwable $exception) use ($debug): void {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');

            if ($debug) {
                echo sprintf(
                    "Error interno: %s en %s:%d",
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                );

                return;
            }

            echo 'Ocurrió un error interno.';
        });
    }
}
