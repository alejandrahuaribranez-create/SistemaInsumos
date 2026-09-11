<?php

declare(strict_types=1);

use RedInsumos\Modules\Payments\Presentation\Controllers\AccountingController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new AccountingController($views);
    $roles = new RoleMiddleware($session, $views);
    $router->get('/contabilidad', static fn ($request) => $roles->handle(['CONTABLE']) ?? $controller->index($request));
};
