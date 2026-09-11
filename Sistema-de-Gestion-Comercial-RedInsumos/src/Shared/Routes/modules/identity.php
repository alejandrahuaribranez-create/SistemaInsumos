<?php

declare(strict_types=1);

use RedInsumos\Modules\Identity\Application\AuthenticateUser;
use RedInsumos\Modules\Identity\Application\RegisterClient;
use RedInsumos\Modules\Identity\Infrastructure\MariaDbUserRepository;
use RedInsumos\Modules\Identity\Presentation\Controllers\AccountController;
use RedInsumos\Modules\Identity\Presentation\Controllers\AuthController;
use RedInsumos\Modules\Sales\Application\ManageCart;
use RedInsumos\Modules\Sales\Presentation\Controllers\CartController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $repository = new MariaDbUserRepository($pdo);
    $authController = new AuthController(
        new RegisterClient($repository),
        new AuthenticateUser($repository),
        $session,
        $views
    );
    $accountController = new AccountController($pdo, $session, $views);
    $clientRole = new RoleMiddleware($session, $views, $pdo);
    $cartController = new CartController(new ManageCart($pdo), $session, $views);

    $router->get('/register', [$authController, 'showRegister']);
    $router->post('/register', [$authController, 'register']);
    $router->get('/login', [$authController, 'showLogin']);
    $router->post('/login', [$authController, 'login']);
    $router->post('/logout', [$authController, 'logout']);
    $router->get('/mi-cuenta', static function ($request) use ($clientRole, $accountController) {
        return $clientRole->handle(['CLIENTE']) ?? $accountController->show($request);
    });
    $router->get('/carrito', static fn ($request) => $clientRole->handle(['CLIENTE']) ?? $cartController->clientIndex($request));
    $router->post('/carrito/agregar', static fn ($request) => $clientRole->handle(['CLIENTE']) ?? $cartController->addClient($request));
    $router->post('/carrito/confirmar', static fn ($request) => $clientRole->handle(['CLIENTE']) ?? $cartController->checkoutClient($request));
    $router->post('/carrito/{id}', static fn ($request) => $clientRole->handle(['CLIENTE']) ?? $cartController->updateClient($request));
    $router->post('/carrito/{id}/eliminar', static fn ($request) => $clientRole->handle(['CLIENTE']) ?? $cartController->removeClient($request));
};
