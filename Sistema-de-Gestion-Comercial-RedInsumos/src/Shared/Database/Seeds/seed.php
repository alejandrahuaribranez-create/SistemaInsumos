<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Database\Seeds\DevelopmentSeeder;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');
$pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
$result = (new DevelopmentSeeder($pdo))->run();

echo sprintf(
    "Seeder local completado: %d usuarios preparados, %d productos creados.%s",
    $result['users'],
    $result['products'],
    PHP_EOL
);
