<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__, 3);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    
    echo "Conectado a la base de datos.\n";
    
    // Contraseña demo: RedInsumos2026!
    $password = Environment::required('DEMO_PASSWORD');
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Eliminar usuarios existentes
    $pdo->prepare("DELETE FROM usuarios WHERE email IN (?, ?, ?, ?)")->execute([
        'admin@redinsumos.local',
        'vendedor@redinsumos.local',
        'almacenero@redinsumos.local',
        'contable@redinsumos.local'
    ]);
    echo "Usuarios anteriores eliminados.\n";
    
    // Eliminar roles existentes
    $pdo->prepare("DELETE FROM roles WHERE codigo IN (?, ?, ?, ?)")->execute([
        'ADMIN', 'VENDEDOR', 'ALMACENERO', 'CONTABLE'
    ]);
    echo "Roles anteriores eliminados.\n";
    
    // Crear roles
    $roleData = [
        ['ADMIN', 'Administrador'],
        ['VENDEDOR', 'Vendedor'],
        ['ALMACENERO', 'Almacenero'],
        ['CONTABLE', 'Contable']
    ];
    
    $roleInsert = $pdo->prepare("INSERT INTO roles (codigo, nombre, estado) VALUES (?, ?, 'activo')");
    foreach ($roleData as [$codigo, $nombre]) {
        $roleInsert->execute([$codigo, $nombre]);
    }
    echo "✓ 4 roles creados.\n";
    
    // Crear usuarios
    $userData = [
        ['ADMIN', 'Administrador Demo', 'admin@redinsumos.local'],
        ['VENDEDOR', 'Vendedor Demo', 'vendedor@redinsumos.local'],
        ['ALMACENERO', 'Almacenero Demo', 'almacenero@redinsumos.local'],
        ['CONTABLE', 'Contable Demo', 'contable@redinsumos.local']
    ];
    
    $roleStmt = $pdo->prepare("SELECT id_rol FROM roles WHERE codigo = ? LIMIT 1");
    $userInsert = $pdo->prepare(
        "INSERT INTO usuarios (id_rol, nombre, email, password_hash, estado) 
         VALUES (?, ?, ?, ?, 'activo')"
    );
    
    foreach ($userData as [$roleCodigo, $nombre, $email]) {
        $roleStmt->execute([$roleCodigo]);
        $roleId = $roleStmt->fetchColumn();
        
        if (!$roleId) {
            throw new RuntimeException("Rol $roleCodigo no encontrado.");
        }
        
        $userInsert->execute([$roleId, $nombre, $email, $passwordHash]);
    }
    echo "✓ 4 usuarios creados.\n";
    
    // Mostrar usuarios creados
    echo "\n=== USUARIOS CREADOS ===\n";
    $result = $pdo->query(
        "SELECT u.id_usuario, u.email, u.nombre, r.codigo AS rol, u.estado 
         FROM usuarios u 
         INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.email IN ('admin@redinsumos.local', 'vendedor@redinsumos.local', 'almacenero@redinsumos.local', 'contable@redinsumos.local')
         ORDER BY r.codigo"
    );
    
    foreach ($result as $row) {
        echo sprintf(
            "ID: %d | Email: %s | Nombre: %s | Rol: %s | Estado: %s\n",
            $row['id_usuario'],
            $row['email'],
            $row['nombre'],
            $row['rol'],
            $row['estado']
        );
    }
    
    echo "\n✓ Setup completado exitosamente.\n";
    echo "Contraseña para todos: $password\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
