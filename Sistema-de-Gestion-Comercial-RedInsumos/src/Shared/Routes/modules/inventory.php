<?php

declare(strict_types=1);

use RedInsumos\Modules\Inventory\Presentation\Controllers\WarehouseController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new WarehouseController($views);
    $roles = new RoleMiddleware($session, $views);
    $router->get('/almacen', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $controller->index($request));
};
