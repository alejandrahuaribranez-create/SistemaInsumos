<?php

declare(strict_types=1);

use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

return static function (
    Router $router,
    \PDO $pdo,
    Session $session,
    ViewRenderer $views
): void {
    $router->notFound(
        static fn () => $views->render(
            'src/Shared/Presentation/Views/errors/404.php',
            ['title' => 'Página no encontrada'],
            404
        )
    );

    $moduleRouteFiles = glob(__DIR__ . '/modules/*.php') ?: [];

    foreach ($moduleRouteFiles as $moduleRouteFile) {
        $registerModuleRoutes = require $moduleRouteFile;

        if (!is_callable($registerModuleRoutes)) {
            throw new RuntimeException(
                sprintf('El archivo de rutas %s debe devolver un callable.', $moduleRouteFile)
            );
        }

        $registerModuleRoutes($router, $pdo, $session, $views);
    }
};
