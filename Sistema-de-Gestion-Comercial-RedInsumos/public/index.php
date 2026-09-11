<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\ErrorHandler;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

if (PHP_SAPI === 'cli-server') {
    $urlPath = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
    $publicRoot = realpath(__DIR__);
    $requestedFile = realpath(__DIR__ . str_replace('/', DIRECTORY_SEPARATOR, $urlPath));

    if (
        $publicRoot !== false
        && $requestedFile !== false
        && str_starts_with($requestedFile, $publicRoot . DIRECTORY_SEPARATOR)
        && is_file($requestedFile)
        && strtolower((string) pathinfo($requestedFile, PATHINFO_EXTENSION)) !== 'php'
    ) {
        return false;
    }
}

$projectRoot = dirname(__DIR__);
$autoloadPath = $projectRoot . '/vendor/autoload.php';

if (!is_file($autoloadPath)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Autoload no disponible. Ejecuta composer dump-autoload.';
    exit;
}

require $autoloadPath;

Environment::load($projectRoot . '/.env');
ErrorHandler::register(Environment::boolean('APP_DEBUG', false));

$session = new Session($projectRoot . '/src/Shared/Storage/sessions');
$session->start();
$pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
$views = new ViewRenderer($projectRoot, $session);
$router = new Router();
$registerRoutes = require $projectRoot . '/src/Shared/Routes/web.php';
$registerRoutes($router, $pdo, $session, $views);

$basePath = (string) parse_url(Environment::get('APP_URL', ''), PHP_URL_PATH);
$request = Request::fromGlobals($basePath);
$router->dispatch($request)->send();
