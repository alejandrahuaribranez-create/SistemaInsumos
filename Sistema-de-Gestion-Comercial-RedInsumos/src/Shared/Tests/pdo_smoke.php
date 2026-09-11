<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__, 3);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');
$pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
$database = $pdo->query('SELECT DATABASE()')->fetchColumn();

if ($database !== 'red_insumos') {
    throw new RuntimeException('La conexión PDO no apunta a red_insumos.');
}

echo "PDO conectado a red_insumos." . PHP_EOL;
