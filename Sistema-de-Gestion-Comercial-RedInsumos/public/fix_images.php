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
    $action = $input['action'] ?? 'assign';
    
    $response = ['success' => false, 'message' => '', 'details' => ''];
    
    if ($action === 'assign') {
        $productos = $pdo->query(
            "SELECT id_producto, codigo, nombre FROM productos ORDER BY id_producto"
        )->fetchAll(\PDO::FETCH_ASSOC);

        // Add explicit equivalences when a file cannot use the product code.
        $manualMap = [
            // 'TALADRO-001' => 'crimpadora-rj45.webp',
        ];

        $imageDirs = [
            'thumb' => $projectRoot . '/public/assets/images/products/320',
            'large' => $projectRoot . '/public/assets/images/products/800',
        ];

        $available = [];
        foreach ($imageDirs as $variant => $directory) {
            foreach (glob($directory . '/*.webp') ?: [] as $file) {
                $available[$variant][strtolower(basename($file))] = basename($file);
            }
        }

        $updateStmt = $pdo->prepare(
            "UPDATE productos SET imagenes = ? WHERE id_producto = ?"
        );
        $actualizados = 0;
        $pendientes = [];

        foreach ($productos as $prod) {
            $codigo = strtoupper(trim((string) $prod['codigo']));
            $imagen = $manualMap[$codigo] ?? ($codigo . '.webp');
            $imageName = strtolower($imagen);

            if (isset($available['thumb'][$imageName], $available['large'][$imageName])) {
                $imagenes = [
                    'thumb' => 'assets/images/products/320/' . $available['thumb'][$imageName],
                    '320' => 'assets/images/products/320/' . $available['thumb'][$imageName],
                    'large' => 'assets/images/products/800/' . $available['large'][$imageName],
                    '800' => 'assets/images/products/800/' . $available['large'][$imageName],
                ];
                $updateStmt->execute([json_encode($imagenes), $prod['id_producto']]);
                $actualizados++;
                continue;
            }

            $updateStmt->execute([null, $prod['id_producto']]);
            $pendientes[] = $codigo . ' - ' . $prod['nombre'];
        }

        $response['success'] = true;
        $response['message'] = "$actualizados productos vinculados correctamente";
        $response['details'] = empty($pendientes)
            ? 'Todos los productos tienen una imagen cuyo nombre coincide con su código.'
            : 'Pendientes: renombra cada archivo a CODIGO.webp o agrega una equivalencia en $manualMap:\n' . implode("\n", $pendientes);
        
    } elseif ($action === 'generate') {
        // Generar imágenes placeholder
        $outputDir320 = $projectRoot . '/public/assets/images/products/320';
        $outputDir800 = $projectRoot . '/public/assets/images/products/800';
        
        if (!is_dir($outputDir320)) mkdir($outputDir320, 0755, true);
        if (!is_dir($outputDir800)) mkdir($outputDir800, 0755, true);
        
        $imagenes = ['router', 'switch', 'cableado', 'fibra', 'conectores'];
        $generadas = 0;
        
        foreach ($imagenes as $img) {
            // Generar imagen placeholder 320x320
            $image320 = imagecreatetruecolor(320, 320);
            $color = imagecolorallocate($image320, rand(50, 200), rand(50, 200), rand(50, 200));
            imagefilledrectangle($image320, 0, 0, 320, 320, $color);
            
            $textColor = imagecolorallocate($image320, 255, 255, 255);
            imagestring($image320, 5, 100, 150, strtoupper($img), $textColor);
            
            // Crear WebP
            imagewebp($image320, "$outputDir320/$img.webp", 80);
            imagedestroy($image320);
            $generadas++;
            
            // Generar imagen placeholder 800x800
            $image800 = imagecreatetruecolor(800, 800);
            $color = imagecolorallocate($image800, rand(50, 200), rand(50, 200), rand(50, 200));
            imagefilledrectangle($image800, 0, 0, 800, 800, $color);
            
            $textColor = imagecolorallocate($image800, 255, 255, 255);
            imagestring($image800, 5, 300, 400, strtoupper($img), $textColor);
            
            // Crear WebP
            imagewebp($image800, "$outputDir800/$img.webp", 80);
            imagedestroy($image800);
        }
        
        $response['success'] = true;
        $response['message'] = "$generadas sets de imágenes generadas (320px y 800px)";
        $response['details'] = "Se crearon imágenes WebP placeholder en:\n- $outputDir320/\n- $outputDir800/";
        
    } elseif ($action === 'check') {
        // Verificar estado
        $sinImagenes = $pdo->query(
            "SELECT COUNT(*) FROM productos WHERE imagenes IS NULL OR imagenes = 'null' OR imagenes = '[]'"
        )->fetchColumn();
        
        $conImagenes = $pdo->query(
            "SELECT COUNT(*) FROM productos WHERE imagenes IS NOT NULL AND imagenes != 'null' AND imagenes != '[]'"
        )->fetchColumn();
        
        $archivos320 = count(glob($projectRoot . '/public/assets/images/products/320/*.webp'));
        $archivos800 = count(glob($projectRoot . '/public/assets/images/products/800/*.webp'));
        
        $response['success'] = true;
        $response['message'] = 'Estado de imágenes verificado';
        $response['details'] = 
            "Productos sin imágenes: $sinImagenes\n" .
            "Productos con imágenes: $conImagenes\n" .
            "Archivos 320px: $archivos320\n" .
            "Archivos 800px: $archivos800";
    }
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['details'] = $e->getTraceAsString();
}

echo json_encode($response);
