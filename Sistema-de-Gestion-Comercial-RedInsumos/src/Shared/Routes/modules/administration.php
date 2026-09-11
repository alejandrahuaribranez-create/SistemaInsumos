<?php

declare(strict_types=1);

use RedInsumos\Modules\Administration\Presentation\Controllers\AdminController;
use RedInsumos\Modules\Administration\Application\ManageUsers;
use RedInsumos\Modules\Administration\Presentation\Controllers\UserAdminController;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

return static function (Router $router, \PDO $pdo, Session $session, ViewRenderer $views): void {
    $controller = new AdminController($views);
    $userController = new UserAdminController(new ManageUsers($pdo), $session, $views);
    $roles = new RoleMiddleware($session, $views, $pdo);
    $router->get('/admin', static fn ($request) => $roles->handle(['ADMIN']) ?? $controller->index($request));
    $router->get('/admin/usuarios', static fn ($request) => $roles->handle(['ADMIN']) ?? $userController->index($request));
    $router->post('/admin/usuarios', static fn ($request) => $roles->handle(['ADMIN']) ?? $userController->create($request));
    $router->get('/admin/usuarios/{id}/editar', static fn ($request) => $roles->handle(['ADMIN']) ?? $userController->edit($request));
    $router->post('/admin/usuarios/{id}', static fn ($request) => $roles->handle(['ADMIN']) ?? $userController->update($request));
    $router->post('/admin/usuarios/{id}/eliminar', static fn ($request) => $roles->handle(['ADMIN']) ?? $userController->delete($request));
};
