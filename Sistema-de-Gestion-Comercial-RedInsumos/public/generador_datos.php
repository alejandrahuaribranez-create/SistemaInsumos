<?php

declare(strict_types=1);

use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;

$projectRoot = dirname(__DIR__);
require $projectRoot . '/vendor/autoload.php';

Environment::load($projectRoot . '/.env');

header('Content-Type: application/json');

try {
    $pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
    $input = json_decode(file_get_contents('php://input'), true);
    $type = $input['type'] ?? 'all';
    
    $response = ['success' => false, 'message' => '', 'details' => ''];
    
    if ($type === 'marcas' || $type === 'all') {
        $marcas = [
            'AEG', 'Bosch', 'DeWALT', 'Makita', 'Festool',
            'Stanley', 'Craftsman', 'Milwaukee', 'Hitachi', 'Black+Decker',
            'Ryobi', 'Metabo', 'Silverline', 'Bahco', 'Snap-on'
        ];
        
        $pdo->exec("DELETE FROM marcas");
        $stmt = $pdo->prepare("INSERT INTO marcas (nombre, estado) VALUES (?, 'activo')");
        
        foreach ($marcas as $marca) {
            $stmt->execute([$marca]);
        }
        
        $response['details'] .= "✓ " . count($marcas) . " marcas creadas\n";
    }
    
    if ($type === 'categorias' || $type === 'all') {
        $categorias = [
            ['Herramientas Eléctricas', 'Taladros, sierras, lijadoras'],
            ['Herramientas Manuales', 'Martillos, destornilladores, llaves'],
            ['Equipos de Seguridad', 'Guantes, cascos, gafas'],
            ['Cables y Conectores', 'Cables de energía, extensiones'],
            ['Accesorios', 'Brocas, sierras, discos'],
            ['Iluminación', 'Lámparas, focos LED'],
            ['Batería y Baterías', 'Pilas, acumuladores'],
            ['Pintura y Acabados', 'Pintura, barniz, selladores'],
        ];
        
        $pdo->exec("DELETE FROM categorias");
        $stmt = $pdo->prepare("INSERT INTO categorias (nombre, descripcion, estado) VALUES (?, ?, 'activo')");
        
        foreach ($categorias as [$nombre, $desc]) {
            $stmt->execute([$nombre, $desc]);
        }
        
        $response['details'] .= "✓ " . count($categorias) . " categorías creadas\n";
    }
    
    if ($type === 'metodos' || $type === 'all') {
        // Métodos de pago
        $metodos_pago = [
            ['Efectivo', 'Pago en efectivo'],
            ['Transferencia Bancaria', 'Depósito o transferencia a cuenta'],
            ['Tarjeta de Crédito', 'Pago con tarjeta de crédito'],
            ['Tarjeta de Débito', 'Pago con tarjeta de débito'],
            ['Cheque', 'Pago con cheque'],
        ];
        
        $pdo->exec("DELETE FROM metodos_pago");
        $stmt = $pdo->prepare("INSERT INTO metodos_pago (nombre, descripcion, estado) VALUES (?, ?, 'activo')");
        
        foreach ($metodos_pago as [$nombre, $desc]) {
            $stmt->execute([$nombre, $desc]);
        }
        
        // Métodos de entrega
        $metodos_entrega = [
            ['Despacho a Domicilio', 'despacho'],
            ['Retiro en Tienda', 'retiro_tienda'],
        ];
        
        $pdo->exec("DELETE FROM metodos_entrega");
        $stmt2 = $pdo->prepare("INSERT INTO metodos_entrega (nombre, tipo, estado) VALUES (?, ?, 'activo')");
        
        foreach ($metodos_entrega as [$nombre, $tipo]) {
            $stmt2->execute([$nombre, $tipo]);
        }
        
        // Tarifas de envío
        $pdo->exec("DELETE FROM tarifas_envio");
        $departamentos = [
            ['La Paz', 'La Paz', 'Centro', 25.00],
            ['La Paz', 'El Alto', 'Zona Sur', 30.00],
            ['Cochabamba', 'Cochabamba', 'Centro', 35.00],
            ['Santa Cruz', 'Santa Cruz', 'Centro', 40.00],
            ['Potosí', 'Potosí', 'Centro', 45.00],
            ['Oruro', 'Oruro', 'Centro', 35.00],
            ['Beni', 'Trinidad', 'Centro', 50.00],
            ['Pando', 'Cobija', 'Centro', 55.00],
            ['Tarija', 'Tarija', 'Centro', 40.00],
            ['Sucre', 'Sucre', 'Centro', 45.00],
        ];
        
        $stmt3 = $pdo->prepare(
            "INSERT INTO tarifas_envio (id_metodo_entrega, departamento, municipio, zona, precio, estado) 
             VALUES (1, ?, ?, ?, ?, 'activo')"
        );
        
        foreach ($departamentos as [$dept, $mun, $zona, $precio]) {
            $stmt3->execute([$dept, $mun, $zona, $precio]);
        }
        
        $response['details'] .= "✓ " . count($metodos_pago) . " métodos de pago creados\n";
        $response['details'] .= "✓ " . count($metodos_entrega) . " métodos de entrega creados\n";
        $response['details'] .= "✓ " . count($departamentos) . " tarifas de envío creadas\n";
    }
    
    if ($type === 'productos' || $type === 'all') {
        // Obtener categorías y marcas
        $categorias = $pdo->query("SELECT id_categoria FROM categorias")->fetchAll(\PDO::FETCH_COLUMN);
        $marcas = $pdo->query("SELECT id_marca FROM marcas")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($categorias)) {
            throw new Exception("No hay categorías. Primero genera categorías.");
        }
        if (empty($marcas)) {
            throw new Exception("No hay marcas. Primero genera marcas.");
        }
        
        $productos = [
            ['TALADRO-001', 'Taladro Percutor 750W', 'Taladro percutor profesional', 'DP-750', 'UNIDAD', 250.00, 350.00, 15],
            ['SIERRA-001', 'Sierra Circular 2000W', 'Sierra circular de banco', 'SC-2000', 'UNIDAD', 180.00, 280.00, 20],
            ['LIJADORA-001', 'Lijadora Orbital 300W', 'Lijadora orbital excéntrica', 'LO-300', 'UNIDAD', 120.00, 200.00, 25],
            ['COMPRESOR-001', 'Compresor de Aire 50L', 'Compresor de aire vertical', 'CA-50', 'UNIDAD', 400.00, 600.00, 8],
            ['DESTORNILLADOR-SET', 'Set 10 Destornilladores', 'Set completo de destornilladores', 'DS-10', 'UNIDAD', 30.00, 50.00, 50],
            ['MARTILLO-001', 'Martillo de Goma 2Kg', 'Martillo de cabeza goma', 'MG-2K', 'UNIDAD', 15.00, 25.00, 100],
            ['TALADRO-002', 'Taladro Inalámbrico 20V', 'Taladro inalámbrico compacto', 'DI-20V', 'UNIDAD', 280.00, 420.00, 12],
            ['CABLE-001', 'Cable Eléctrico 3x2.5mm 50m', 'Cable prolongador 50 metros', 'CE-50', 'UNIDAD', 45.00, 75.00, 30],
            ['CASCO-001', 'Casco de Seguridad', 'Casco de protección amarillo', 'CS-AM', 'UNIDAD', 20.00, 35.00, 60],
            ['GUANTES-001', 'Guantes de Trabajo Nitrilo', 'Guantes nitrilo talla L', 'GT-L', 'PAR', 8.00, 15.00, 150],
            ['GAFAS-001', 'Gafas de Seguridad', 'Gafas protectoras transparentes', 'GS-TR', 'UNIDAD', 12.00, 20.00, 80],
            ['LINTERNA-001', 'Linterna LED Recargable', 'Linterna LED 1000 lúmenes', 'LR-1000', 'UNIDAD', 35.00, 60.00, 40],
            ['PILAS-AA', 'Pilas AA (4 pack)', 'Pilas alcalinas AA', 'PA-4', 'PACK', 15.00, 25.00, 200],
            ['BATERIA-18V', 'Batería 18V 2.0Ah', 'Batería de ión litio', 'B-18', 'UNIDAD', 80.00, 120.00, 20],
            ['PINTURA-BLANCA', 'Pintura Blanca 4L', 'Pintura de agua blanca', 'PB-4L', 'LATA', 35.00, 55.00, 25],
            ['BARNIZ-MADERA', 'Barniz Poliuretano 1L', 'Barniz mate para madera', 'BM-1L', 'LATA', 28.00, 45.00, 18],
            ['CEMENTO-CONTACTO', 'Cemento Contacto 500ml', 'Adhesivo de contacto', 'CC-500', 'LATA', 15.00, 25.00, 35],
            ['BROCA-SET', 'Set 13 Brocas Metal', 'Brocas para metal acero', 'BM-13', 'UNIDAD', 18.00, 30.00, 45],
            ['DISCO-CORTE', 'Disco de Corte 125mm', 'Disco abrasivo para corte', 'DC-125', 'UNIDAD', 8.00, 15.00, 100],
            ['MUELA-ESMERIL', 'Muela Esmeril 150mm', 'Muela para afilar', 'ME-150', 'UNIDAD', 12.00, 20.00, 30],
            ['LLAVE-INGLESA', 'Llave Inglesa 300mm', 'Llave ajustable', 'LI-300', 'UNIDAD', 22.00, 38.00, 40],
            ['TUERCA-SURTIDA', 'Surtido 500 Tuercas', 'Tuercas variadas', 'TS-500', 'CAJA', 25.00, 45.00, 15],
            ['PERNO-SURTIDO', 'Surtido 500 Pernos', 'Pernos variados', 'PS-500', 'CAJA', 28.00, 48.00, 15],
            ['MASILLA-MADERA', 'Masilla para Madera 200g', 'Masilla de madera', 'MM-200', 'TUBO', 12.00, 20.00, 50],
            ['LIJA-SURTIDO', 'Surtido Lijas 10 piezas', 'Lijas variadas', 'LS-10', 'PACK', 20.00, 35.00, 35],
            ['FOCOS-LED', 'Foco LED 12W E27', 'Bombilla LED blanca', 'FL-12W', 'UNIDAD', 8.00, 15.00, 150],
            ['REGLETA-3-ENCHUFES', 'Regleta 3 Enchufes', 'Regleta de toma corriente', 'RE-3', 'UNIDAD', 18.00, 30.00, 60],
            ['MANGUERA-AIRE', 'Manguera Aire 10m', 'Manguera de aire comprimido', 'MA-10', 'UNIDAD', 35.00, 60.00, 20],
            ['NIVEL-BURBUJA', 'Nivel de Burbuja 600mm', 'Nivel de precisión', 'NB-600', 'UNIDAD', 30.00, 50.00, 25],
            ['METRO-ENROLLABLE', 'Metro Enrollable 5m', 'Cinta métrica 5 metros', 'ME-5', 'UNIDAD', 15.00, 25.00, 100],
        ];
        
        $pdo->exec("DELETE FROM productos");
        $stmt = $pdo->prepare(
            "INSERT INTO productos (id_categoria, id_marca, codigo, nombre, descripcion, modelo, unidad_medida, 
             precio_compra, precio_venta, stock_actual, stock_minimo, stock_maximo, estado) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 5, 50, 'activo')"
        );
        
        foreach ($productos as $i => $prod) {
            $cat = $categorias[$i % count($categorias)];
            $marc = $marcas[$i % count($marcas)];
            $stmt->execute([
                $cat, $marc, 
                $prod[0], $prod[1], $prod[2], $prod[3], $prod[4],
                $prod[5], $prod[6], $prod[7]
            ]);
        }
        
        $response['details'] .= "✓ " . count($productos) . " productos creados\n";
    }
    
    if ($type === 'clientes' || $type === 'all') {
        $clientes = [
            ['Juan Pérez García', 'juan.perez@email.com', 'CI', '12345678', '', '591-12345678', 'persona'],
            ['María López Martínez', 'maria.lopez@email.com', 'CI', '87654321', '', '591-87654321', 'persona'],
            ['Carlos Rodríguez Silva', 'carlos.rod@email.com', 'CI', '45678901', '', '591-45678901', 'persona'],
            ['Ana García Flores', 'ana.garcia@email.com', 'CI', '56789012', '', '591-56789012', 'persona'],
            ['Roberto Fernández Cruz', 'roberto.f@email.com', 'CI', '67890123', '', '591-67890123', 'persona'],
            ['Empresa ABC SRL', 'contacto@empresaabc.com', 'NIT', '1234567890', '', '591-2-2222222', 'empresa'],
            ['Distribuidora XYZ', 'info@distribxyz.com', 'NIT', '9876543210', '', '591-3-3333333', 'empresa'],
            ['Construcciones DEF', 'ventas@construdef.com', 'NIT', '5555555555', '', '591-4-4444444', 'empresa'],
            ['Sandra Morales López', 'sandra.m@email.com', 'CI', '11223344', '', '591-11223344', 'persona'],
            ['José Martínez Ramírez', 'jose.m@email.com', 'CI', '99887766', '', '591-99887766', 'persona'],
        ];
        
        $pdo->exec("DELETE FROM clientes");
        $stmt = $pdo->prepare(
            "INSERT INTO clientes (tipo_documento, numero_documento, complemento, nombre, 
             razon_social, telefono, tipo, estado) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')"
        );
        
        foreach ($clientes as $c) {
            $stmt->execute([
                $c[2], $c[3], $c[4], $c[0], ($c[6] === 'empresa' ? $c[0] : null), $c[5], $c[6]
            ]);
        }
        
        // Crear direcciones para cada cliente
        $direcciones = [
            ['Centro', 'envio', 'Calle Principal 123', 'Centro', 'La Paz', 'La Paz'],
            ['Oficina', 'facturacion', 'Av. Mariscal Santa Cruz 456', 'Sopocachi', 'La Paz', 'La Paz'],
            ['Casa', 'ambos', 'Calle 16 de Julio 789', 'Zona Sur', 'El Alto', 'La Paz'],
            ['Sucursal', 'envio', 'Calle Colombia 321', 'Centro', 'Cochabamba', 'Cochabamba'],
            ['Principal', 'envio', 'Av. Brasil 654', 'Centro', 'Santa Cruz', 'Santa Cruz'],
        ];
        
        $stmt_dir = $pdo->prepare(
            "INSERT INTO direcciones (id_cliente, alias, tipo, direccion_linea, zona, municipio, departamento, estado) 
             VALUES (?, ?, ?, ?, ?, ?, ?, 'activo')"
        );
        
        $cliente_ids = $pdo->query("SELECT id_cliente FROM clientes LIMIT 5")->fetchAll(\PDO::FETCH_COLUMN);
        
        foreach ($cliente_ids as $i => $cid) {
            $dir = $direcciones[$i % count($direcciones)];
            $stmt_dir->execute([$cid, $dir[0], $dir[1], $dir[2], $dir[3], $dir[4], $dir[5]]);
        }
        
        $response['details'] .= "✓ " . count($clientes) . " clientes creados\n";
        $response['details'] .= "✓ " . count($cliente_ids) . " direcciones creadas\n";
    }
    
    if ($type === 'pedidos' || $type === 'all') {
        $clientes = $pdo->query("SELECT id_cliente FROM clientes")->fetchAll(\PDO::FETCH_COLUMN);
        $productos = $pdo->query("SELECT id_producto FROM productos")->fetchAll(\PDO::FETCH_COLUMN);
        
        if (empty($clientes) || empty($productos)) {
            throw new Exception("Necesitas crear clientes y productos primero");
        }
        
        $pdo->exec("DELETE FROM ventas");
        
        $estados_pago = ['pendiente', 'pagada', 'fallida'];
        $estados_logistico = ['pendiente', 'procesando', 'empacado', 'enviado', 'entregado'];
        
        $stmt = $pdo->prepare(
            "INSERT INTO ventas (codigo_pedido, id_cliente, id_metodo_entrega, origen, fecha_venta, 
             subtotal, descuento, impuesto, costo_envio, total, estado_pago, estado_logistico) 
             VALUES (?, ?, 1, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)"
        );
        
        for ($i = 0; $i < 15; $i++) {
            $cliente = $clientes[array_rand($clientes)];
            $producto = $productos[array_rand($productos)];
            $cantidad = rand(1, 5);
            $precio = rand(100, 500);
            $subtotal = $precio * $cantidad;
            $descuento = rand(0, 10);
            $impuesto = $subtotal * 0.13;
            $costo_envio = rand(20, 60);
            $total = $subtotal - $descuento + $impuesto + $costo_envio;
            $origen = ['web', 'tienda', 'telefono'][array_rand(['web', 'tienda', 'telefono'])];
            
            $stmt->execute([
                'PED-' . str_pad($i + 1, 5, '0', STR_PAD_LEFT),
                $cliente,
                $origen,
                $subtotal,
                $descuento,
                $impuesto,
                $costo_envio,
                $total,
                $estados_pago[array_rand($estados_pago)],
                $estados_logistico[array_rand($estados_logistico)]
            ]);
        }
        
        $response['details'] .= "✓ 15 pedidos de ejemplo creados\n";
    }
    
    $response['success'] = true;
    $response['message'] = $type === 'all' ? '¡Todo generado correctamente!' : ucfirst($type) . ' generado correctamente';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['details'] = $e->getTraceAsString();
}

echo json_encode($response);
