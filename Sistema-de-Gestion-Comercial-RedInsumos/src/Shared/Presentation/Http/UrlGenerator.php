<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Http;

use RedInsumos\Shared\Config\Environment;

final class UrlGenerator
{
    private string $basePath;

    public function __construct(string $appUrl = '')
    {
        $path = (string) (parse_url($appUrl, PHP_URL_PATH) ?? '');
        $this->basePath = $path === '/' ? '' : rtrim('/' . trim($path, '/'), '/');
    }

    public static function fromEnvironment(): self
    {
        return new self(Environment::get('APP_URL', '') ?? '');
    }

    public function to(string $path = '/'): string
    {
        if ($path === '' || $path === '/') {
            return $this->basePath === '' ? '/' : $this->basePath . '/';
        }

        if (preg_match('#^(?:https?:)?//#i', $path) === 1 || str_starts_with($path, '#')) {
            return $path;
        }

        $normalized = '/' . ltrim($path, '/');

        return $this->basePath . $normalized;
    }
}
