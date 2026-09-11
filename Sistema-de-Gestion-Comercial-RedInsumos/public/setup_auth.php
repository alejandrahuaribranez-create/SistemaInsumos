<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__, 2);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    
    $password = Environment::required('DEMO_PASSWORD');
    $correctHash = password_hash($password, PASSWORD_DEFAULT);
    
    // Actualizar contraseñas
    $emails = [
        'admin@redinsumos.local',
        'vendedor@redinsumos.local',
        'almacenero@redinsumos.local',
        'contable@redinsumos.local'
    ];
    
    $updateStmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
    
    echo "<h2>🔧 Actualizando contraseñas...</h2>";
    echo "<pre>";
    
    foreach ($emails as $email) {
        $result = $updateStmt->execute([$correctHash, $email]);
        echo "✓ Actualizado: $email\n";
    }
    
    echo "\n=== USUARIOS ACTUALES ===\n";
    $stmt = $pdo->query(
        "SELECT u.id_usuario, u.email, u.nombre, r.codigo AS rol, u.estado, u.password_hash 
         FROM usuarios u 
         INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.email IN ('admin@redinsumos.local', 'vendedor@redinsumos.local', 'almacenero@redinsumos.local', 'contable@redinsumos.local')
         ORDER BY r.codigo"
    );
    
    foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
        echo "\nEmail: {$row['email']}\n";
        echo "Nombre: {$row['nombre']}\n";
        echo "Rol: {$row['rol']}\n";
        echo "Estado: {$row['estado']}\n";
        echo "Hash: " . substr($row['password_hash'], 0, 20) . "...\n";
    }
    
    echo "\n=== PRUEBA DE CONTRASEÑA ===\n";
    echo "Contraseña configurada: $password\n";
    echo "Hash: $correctHash\n";
    
    // Probar que el hash funciona
    if (password_verify($password, $correctHash)) {
        echo "\n✓ ¡La contraseña funciona correctamente!\n";
    } else {
        echo "\n❌ Error: La contraseña no coincide\n";
    }
    
    echo "\n=== CREDENCIALES DE ACCESO ===\n";
    echo "Contraseña: $password\n";
    echo "Usuarios:\n";
    foreach ($emails as $email) {
        echo "  - $email\n";
    }
    
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error: " . $e->getMessage() . "</h2>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
