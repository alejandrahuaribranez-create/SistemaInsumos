<?php

declare(strict_types=1);

use RedInsumos\Modules\Sales\Presentation\Controllers\SellerController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new SellerController($views);
    $roles = new RoleMiddleware($session, $views);
    $router->get('/vendedor', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $controller->index($request));
};
