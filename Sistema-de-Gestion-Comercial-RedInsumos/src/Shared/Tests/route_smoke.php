<?php

declare(strict_types=1);

use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Router;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

$projectRoot = dirname(__DIR__, 3);
require $projectRoot . '/vendor/autoload.php';
$pdo = new PDO('sqlite::memory:');
$session = new Session();
$views = new ViewRenderer($projectRoot, $session);
$router = new Router();
$register = require $projectRoot . '/src/Shared/Routes/web.php';
$register($router, $pdo, $session, $views);
echo 'Registro de rutas: OK' . PHP_EOL;
