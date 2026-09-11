<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__FILE__);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    
    echo "Conectado a la base de datos.\n";
    
    // Obtener contraseña demo del .env
    $password = Environment::required('DEMO_PASSWORD');
    echo "Contraseña configurada: $password\n";
    
    // Generar hash correcto
    $correctHash = password_hash($password, PASSWORD_DEFAULT);
    echo "Hash generado: $correctHash\n\n";
    
    // Actualizar usuarios con el hash correcto
    $emails = [
        'admin@redinsumos.local',
        'vendedor@redinsumos.local',
        'almacenero@redinsumos.local',
        'contable@redinsumos.local'
    ];
    
    $updateStmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
    
    foreach ($emails as $email) {
        $updateStmt->execute([$correctHash, $email]);
        echo "✓ Contraseña actualizada para: $email\n";
    }
    
    // Verificar que se actualizó correctamente
    echo "\n=== VERIFICACIÓN ===\n";
    $checkStmt = $pdo->prepare(
        "SELECT u.email, u.nombre, r.codigo AS rol, u.estado 
         FROM usuarios u 
         INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.email IN (?, ?, ?, ?)
         ORDER BY r.codigo"
    );
    $checkStmt->execute($emails);
    
    foreach ($checkStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        echo sprintf(
            "Email: %s | Nombre: %s | Rol: %s | Estado: %s\n",
            $row['email'],
            $row['nombre'],
            $row['rol'],
            $row['estado']
        );
    }
    
    echo "\n✓ Contraseñas actualizadas correctamente.\n";
    echo "Puedes ingresar con: $password\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
