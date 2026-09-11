<?php

declare(strict_types=1);

use RedInsumos\Modules\Payments\Presentation\Controllers\AccountingController;
use RedInsumos\Modules\Payments\Application\ManageAccounting;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new AccountingController(new ManageAccounting($pdo), $session, $views);
    $roles = new RoleMiddleware($session, $views, $pdo);
    $router->get('/contabilidad', static fn ($request) => $roles->handle(['CONTABLE']) ?? $controller->index($request));
    $router->post('/contabilidad/compras', static fn ($request) => $roles->handle(['CONTABLE']) ?? $controller->createPurchase($request));
    $router->post('/contabilidad/compras/{id}/cancelar', static fn ($request) => $roles->handle(['CONTABLE']) ?? $controller->cancelPurchase($request));
    $router->post('/contabilidad/pagos/{id}/conciliar', static fn ($request) => $roles->handle(['CONTABLE']) ?? $controller->reconcilePayment($request));
};
