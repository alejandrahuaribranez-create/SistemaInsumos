<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;
use RedInsumos\Modules\Identity\Application\AuthenticateUser;
use RedInsumos\Modules\Identity\Infrastructure\MariaDbUserRepository;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Diagnóstico RedInsumos</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .success { background: #d4edda; color: #155724; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .info { background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0; border-radius: 3px; }
        .code { background: #f4f4f4; padding: 10px; border-left: 3px solid #007bff; margin: 10px 0; font-family: monospace; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { text-align: left; padding: 10px; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:hover { background: #f5f5f5; }
        .action-btn { background: #28a745; color: white; padding: 10px 20px; border: none; border-radius: 3px; cursor: pointer; }
        .action-btn:hover { background: #218838; }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Diagnóstico de Autenticación - RedInsumos</h1>

<?php

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    
    echo "<h2>1️⃣ Verificar Conexión BD</h2>";
    echo "<div class='success'>✓ Conectado a base de datos correctamente</div>";
    
    // Obtener contraseña
    $password = Environment::required('DEMO_PASSWORD');
    echo "<h2>2️⃣ Contraseña Configurada</h2>";
    echo "<div class='code'>$password</div>";
    
    // Generar hash
    $correctHash = password_hash($password, PASSWORD_DEFAULT);
    echo "<h2>3️⃣ Hash Generado</h2>";
    echo "<div class='code'>$correctHash</div>";
    
    // Ver usuarios actuales
    echo "<h2>4️⃣ Usuarios en Base de Datos</h2>";
    $stmt = $pdo->query(
        "SELECT u.id_usuario, u.email, u.nombre, r.codigo AS rol, u.estado, u.password_hash 
         FROM usuarios u 
         INNER JOIN roles r ON r.id_rol = u.id_rol
         WHERE u.email LIKE '%redinsumos.local%'
         ORDER BY r.codigo"
    );
    
    $usuarios = $stmt->fetchAll(\PDO::FETCH_ASSOC);
    
    if (empty($usuarios)) {
        echo "<div class='error'>❌ No hay usuarios demo encontrados</div>";
    } else {
        echo "<table>";
        echo "<tr><th>Email</th><th>Nombre</th><th>Rol</th><th>Estado</th><th>Hash</th></tr>";
        foreach ($usuarios as $u) {
            $hashPreview = substr($u['password_hash'], 0, 30) . '...';
            $estado = $u['estado'] === 'activo' ? '✓ Activo' : '❌ Inactivo';
            echo "<tr><td>{$u['email']}</td><td>{$u['nombre']}</td><td>{$u['rol']}</td><td>$estado</td><td class='code' style='font-size:10px;'>$hashPreview</td></tr>";
        }
        echo "</table>";
    }
    
    // Actualizar contraseñas
    echo "<h2>5️⃣ Actualizar Contraseñas</h2>";
    $emails = [
        'admin@redinsumos.local',
        'vendedor@redinsumos.local',
        'almacenero@redinsumos.local',
        'contable@redinsumos.local'
    ];
    
    $updateStmt = $pdo->prepare("UPDATE usuarios SET password_hash = ? WHERE email = ?");
    
    foreach ($emails as $email) {
        $result = $updateStmt->execute([$correctHash, $email]);
        if ($result) {
            echo "<div class='success'>✓ Actualizado: $email</div>";
        } else {
            echo "<div class='error'>❌ Error al actualizar: $email</div>";
        }
    }
    
    // Probar autenticación
    echo "<h2>6️⃣ Prueba de Autenticación</h2>";
    
    if (password_verify($password, $correctHash)) {
        echo "<div class='success'>✓ Hash y contraseña son compatibles</div>";
    } else {
        echo "<div class='error'>❌ Hash y contraseña NO coinciden</div>";
    }
    
    // Intentar autenticar como admin
    echo "<h2>7️⃣ Simular Login como Admin</h2>";
    $repo = new MariaDbUserRepository($pdo);
    $user = $repo->findByEmail('admin@redinsumos.local');
    
    if ($user === null) {
        echo "<div class='error'>❌ Usuario admin@redinsumos.local no encontrado</div>";
    } else {
        echo "<div class='info'>Usuario encontrado: " . $user->name() . "</div>";
        echo "<div class='info'>Rol: " . $user->role() . "</div>";
        $estado = $user->isActive() ? 'Activo' : 'Inactivo';
        echo "<div class='info'>Estado: " . $estado . "</div>";
        
        // Verificar contraseña
        if (password_verify($password, $user->passwordHash())) {
            echo "<div class='success'>✓ ¡Contraseña verificada correctamente!</div>";
        } else {
            echo "<div class='error'>❌ La contraseña no coincide con el hash</div>";
            echo "<div class='error'>Hash almacenado: " . substr($user->passwordHash(), 0, 30) . "...</div>";
            echo "<div class='error'>Hash generado: " . substr($correctHash, 0, 30) . "...</div>";
        }
    }
    
    echo "<h2>8️⃣ Credenciales Finales</h2>";
    echo "<table>";
    echo "<tr><th>Email</th><th>Contraseña</th><th>Rol</th></tr>";
    echo "<tr><td>admin@redinsumos.local</td><td>$password</td><td>ADMIN</td></tr>";
    echo "<tr><td>vendedor@redinsumos.local</td><td>$password</td><td>VENDEDOR</td></tr>";
    echo "<tr><td>almacenero@redinsumos.local</td><td>$password</td><td>ALMACENERO</td></tr>";
    echo "<tr><td>contable@redinsumos.local</td><td>$password</td><td>CONTABLE</td></tr>";
    echo "</table>";
    
    echo "<h2>✅ Resumen</h2>";
    echo "<div class='success'>";
    echo "Todos los usuarios han sido actualizados con la contraseña correcta.<br>";
    echo "Prueba ingresando con cualquiera de los emails y la contraseña: <strong>$password</strong><br>";
    echo "<br><a href='/SistemaInsumos/Sistema-de-Gestion-Comercial-RedInsumos/public/login' style='color: white; text-decoration: none;'>";
    echo "<button class='action-btn'>➜ Ir a Login</button></a>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<h2>❌ Error</h2>";
    echo "<div class='error'>";
    echo "<strong>" . get_class($e) . ":</strong> " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}

?>

</div>
</body>
</html>
