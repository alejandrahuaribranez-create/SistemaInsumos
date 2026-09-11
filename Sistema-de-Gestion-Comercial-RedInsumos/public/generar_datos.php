<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generar Datos de Prueba - RedInsumos</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container { 
            max-width: 1000px; 
            margin: 0 auto; 
            background: white; 
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .section {
            margin-bottom: 30px;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            background: #fafafa;
        }
        .section h3 {
            color: #667eea;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .status {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: bold;
            margin: 5px 0;
        }
        .status.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .status.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .status.warning {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
        }
        .status.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .btn-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
            font-weight: bold;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        .stat-box {
            background: white;
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            border-left: 4px solid #667eea;
        }
        .stat-box .number {
            font-size: 2em;
            font-weight: bold;
            color: #667eea;
        }
        .stat-box .label {
            font-size: 0.85em;
            color: #666;
            margin-top: 5px;
        }
        .loading { display: none; }
        .spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🎯 Generar Datos de Prueba</h1>
        <p>Llena la base de datos con marcas, categorías, productos y más</p>
    </div>
    
    <div class="content" id="content">

<?php

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    
    // Verificar conexión
    echo '<div class="section">';
    echo '<h3>✅ Estado del Sistema</h3>';
    echo '<div class="status success">Conectado a base de datos correctamente</div>';
    echo '</div>';
    
    // Obtener estadísticas actuales
    $stats = [
        'marcas' => $pdo->query("SELECT COUNT(*) FROM marcas")->fetchColumn(),
        'categorias' => $pdo->query("SELECT COUNT(*) FROM categorias")->fetchColumn(),
        'productos' => $pdo->query("SELECT COUNT(*) FROM productos")->fetchColumn(),
        'clientes' => $pdo->query("SELECT COUNT(*) FROM clientes")->fetchColumn(),
        'ventas' => $pdo->query("SELECT COUNT(*) FROM ventas")->fetchColumn(),
        'metodos_pago' => $pdo->query("SELECT COUNT(*) FROM metodos_pago")->fetchColumn(),
    ];
    
    echo '<div class="section">';
    echo '<h3>📊 Estadísticas Actuales</h3>';
    echo '<div class="stats">';
    echo '<div class="stat-box"><div class="number">' . $stats['marcas'] . '</div><div class="label">Marcas</div></div>';
    echo '<div class="stat-box"><div class="number">' . $stats['categorias'] . '</div><div class="label">Categorías</div></div>';
    echo '<div class="stat-box"><div class="number">' . $stats['productos'] . '</div><div class="label">Productos</div></div>';
    echo '<div class="stat-box"><div class="number">' . $stats['clientes'] . '</div><div class="label">Clientes</div></div>';
    echo '<div class="stat-box"><div class="number">' . $stats['ventas'] . '</div><div class="label">Ventas</div></div>';
    echo '<div class="stat-box"><div class="number">' . $stats['metodos_pago'] . '</div><div class="label">Métodos Pago</div></div>';
    echo '</div>';
    echo '</div>';
    
    // Botones de acción
    echo '<div class="section">';
    echo '<h3>🔧 Herramientas de Generación</h3>';
    echo '<p>Selecciona qué deseas generar:</p>';
    echo '<div class="btn-group">';
    echo '<button onclick="generate(\'marcas\')">📦 Generar Marcas</button>';
    echo '<button onclick="generate(\'categorias\')">🏷️ Generar Categorías</button>';
    echo '<button onclick="generate(\'metodos\')">💳 Generar Métodos Pago/Entrega</button>';
    echo '<button onclick="generate(\'productos\')">📚 Generar 30 Productos</button>';
    echo '<button onclick="generate(\'clientes\')">👥 Generar 10 Clientes</button>';
    echo '<button onclick="generate(\'pedidos\')">📋 Generar Pedidos de Ejemplo</button>';
    echo '<button class="btn-secondary" onclick="generateAll()">⚡ Generar TODO</button>';
    echo '</div>';
    echo '</div>';
    
    echo '<div id="result" class="section" style="display:none;"></div>';
    
} catch (Exception $e) {
    echo '<div class="section">';
    echo '<h3>❌ Error</h3>';
    echo '<div class="status error">' . $e->getMessage() . '</div>';
    echo '</div>';
}

?>

    </div>
</div>

<script>
async function generate(type) {
    const result = document.getElementById('result');
    result.style.display = 'block';
    result.innerHTML = '<div class="spinner"></div> Generando ' + type + '...';
    
    try {
        const response = await fetch('generador_datos.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ type: type })
        });
        
        const data = await response.json();
        
        if (data.success) {
            result.innerHTML = '<div class="status success">✅ ' + data.message + '</div>';
            if (data.details) {
                result.innerHTML += '<pre style="background:#f4f4f4; padding:10px; border-radius:5px; margin-top:10px;">' + data.details + '</pre>';
            }
            setTimeout(() => location.reload(), 2000);
        } else {
            result.innerHTML = '<div class="status error">❌ ' + data.message + '</div>';
            if (data.details) {
                result.innerHTML += '<pre style="background:#f4f4f4; padding:10px; border-radius:5px; margin-top:10px; color:red;">' + data.details + '</pre>';
            }
        }
    } catch (error) {
        result.innerHTML = '<div class="status error">❌ Error: ' + error.message + '</div>';
    }
}

async function generateAll() {
    const types = ['marcas', 'categorias', 'metodos', 'productos', 'clientes', 'pedidos'];
    
    for (const type of types) {
        await new Promise(resolve => {
            setTimeout(() => {
                generate(type).then(resolve);
            }, 500);
        });
    }
}
</script>

</body>
</html>
