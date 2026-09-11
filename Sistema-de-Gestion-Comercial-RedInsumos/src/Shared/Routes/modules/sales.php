<?php

declare(strict_types=1);

use RedInsumos\Modules\Sales\Presentation\Controllers\SellerController;
use RedInsumos\Modules\Catalog\Application\ManageProducts;
use RedInsumos\Modules\Catalog\Presentation\Controllers\ProductSellerController;
use RedInsumos\Modules\Sales\Application\ManageCart;
use RedInsumos\Modules\Sales\Presentation\Controllers\CartController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new SellerController($views);
    $products = new ProductSellerController(new ManageProducts($pdo), $session, $views, dirname(__DIR__, 4) . '/public');
    $cart = new CartController(new ManageCart($pdo), $session, $views);
    $roles = new RoleMiddleware($session, $views, $pdo);
    $router->get('/vendedor', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $controller->index($request));
    $router->get('/vendedor/productos', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $products->index($request));
    $router->post('/vendedor/productos', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $products->create($request));
    $router->get('/vendedor/productos/{id}/editar', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $products->edit($request));
    $router->post('/vendedor/productos/{id}', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $products->update($request));
    $router->get('/vendedor/carrito', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $cart->sellerIndex($request));
    $router->post('/vendedor/carrito/agregar', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $cart->addSeller($request));
    $router->post('/vendedor/carrito/confirmar', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $cart->checkoutSeller($request));
    $router->post('/vendedor/carrito/{id}', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $cart->updateSeller($request));
    $router->post('/vendedor/carrito/{id}/eliminar', static fn ($request) => $roles->handle(['VENDEDOR']) ?? $cart->removeSeller($request));
};
