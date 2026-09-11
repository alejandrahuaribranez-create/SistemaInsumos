<?php

declare(strict_types=1);

use RedInsumos\Modules\Inventory\Presentation\Controllers\WarehouseController;
use RedInsumos\Modules\Inventory\Application\ManageInventory;
use RedInsumos\Modules\Inventory\Application\ManageShipping;
use RedInsumos\Modules\Inventory\Presentation\Controllers\ShippingController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new WarehouseController(new ManageInventory($pdo), $session, $views);
    $shipping = new ShippingController(new ManageShipping($pdo), $session, $views);
    $roles = new RoleMiddleware($session, $views, $pdo);
    $router->get('/almacen', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $controller->index($request));
    $router->post('/almacen/movimientos', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $controller->movement($request));
    $router->post('/almacen/compras/{id}/recibir', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $controller->receivePurchase($request));
    $router->get('/almacen/envios', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $shipping->index($request));
    $router->post('/almacen/envios/{id}', static fn ($request) => $roles->handle(['ALMACENERO']) ?? $shipping->update($request));
};
