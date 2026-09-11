<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__, 4);
require $projectRoot . '/vendor/autoload.php';
Environment::load($projectRoot . '/.env');
$pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
$pdo->exec('CREATE TABLE IF NOT EXISTS migraciones_sistema (nombre VARCHAR(190) NOT NULL PRIMARY KEY, aplicada_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$files = glob(__DIR__ . '/[0-9]*.php') ?: [];
sort($files);
$check = $pdo->prepare('SELECT 1 FROM migraciones_sistema WHERE nombre = :name');
$mark = $pdo->prepare('INSERT INTO migraciones_sistema (nombre) VALUES (:name)');
foreach ($files as $file) {
    $name = basename($file);
    $check->execute(['name' => $name]);
    if ($check->fetchColumn() !== false) {
        echo "Omitida: $name" . PHP_EOL;
        continue;
    }
    $migration = require $file;
    if (!is_callable($migration)) {
        throw new RuntimeException("La migración $name no es ejecutable.");
    }
    $migration($pdo);
    $mark->execute(['name' => $name]);
    echo "Aplicada: $name" . PHP_EOL;
}
