<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Http;

use Closure;
use InvalidArgumentException;

final class Router
{
    /** @var array<string, list<array{path: string, regex: string, handler: Closure(Request): Response}>> */
    private array $routes = [];

    /** @var null|Closure(Request): Response */
    private ?Closure $notFoundHandler = null;

    /** @param callable(Request): Response $handler */
    public function get(string $path, callable $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    /** @param callable(Request): Response $handler */
    public function post(string $path, callable $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    /** @param callable(Request): Response $handler */
    public function add(string $method, string $path, callable $handler): void
    {
        $method = strtoupper($method);
        $path = self::normalizePath($path);

        foreach ($this->routes[$method] ?? [] as $route) {
            if ($route['path'] === $path) {
                throw new InvalidArgumentException(sprintf('La ruta %s %s ya está registrada.', $method, $path));
            }
        }

        $this->routes[$method][] = [
            'path' => $path,
            'regex' => self::compilePattern($path),
            'handler' => Closure::fromCallable($handler),
        ];
    }

    /** @param callable(Request): Response $handler */
    public function notFound(callable $handler): void
    {
        $this->notFoundHandler = Closure::fromCallable($handler);
    }

    public function dispatch(Request $request): Response
    {
        foreach ($this->routes[$request->method()] ?? [] as $route) {
            $parameters = self::match($route['regex'], $request->path());

            if ($parameters !== null) {
                return ($route['handler'])($request->withRouteParameters($parameters));
            }
        }

        foreach ($this->routes as $method => $routes) {
            if ($method === $request->method()) {
                continue;
            }

            foreach ($routes as $route) {
                if (self::match($route['regex'], $request->path()) !== null) {
                    return Response::text('Método no permitido.', 405);
                }
            }
        }

        if ($this->notFoundHandler !== null) {
            return ($this->notFoundHandler)($request);
        }

        return Response::text('Página no encontrada.', 404);
    }

    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        return $path === '//' ? '/' : $path;
    }

    private static function compilePattern(string $path): string
    {
        if ($path === '/') {
            return '#^/$#';
        }

        $segments = explode('/', trim($path, '/'));
        $patterns = [];

        foreach ($segments as $segment) {
            if (preg_match('/^\{([A-Za-z_][A-Za-z0-9_]*)\}$/', $segment, $matches) === 1) {
                $patterns[] = '(?P<' . $matches[1] . '>[^/]+)';
                continue;
            }

            $patterns[] = preg_quote($segment, '#');
        }

        return '#^/' . implode('/', $patterns) . '/?$#';
    }

    /** @return null|array<string, string> */
    private static function match(string $regex, string $path): ?array
    {
        if (preg_match($regex, $path, $matches) !== 1) {
            return null;
        }

        $parameters = [];
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $parameters[$key] = rawurldecode($value);
            }
        }

        return $parameters;
    }
}
