<?php

declare(strict_types=1);

use RedInsumos\Modules\Administration\Application\ManageUsers;
use RedInsumos\Modules\Catalog\Application\ManageCategories;
use RedInsumos\Modules\Catalog\Application\ManageProducts;
use RedInsumos\Modules\Catalog\Infrastructure\MariaDbCategoryRepository;
use RedInsumos\Modules\Inventory\Application\ManageInventory;
use RedInsumos\Modules\Inventory\Application\ManageShipping;
use RedInsumos\Modules\Payments\Application\ManageAccounting;
use RedInsumos\Modules\Sales\Application\ManageCart;
use RedInsumos\Modules\Sales\Presentation\Controllers\CartController;
use RedInsumos\Shared\Config\DatabaseConfig;
use RedInsumos\Shared\Config\Environment;
use RedInsumos\Shared\Infrastructure\Database\PdoConnectionFactory;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Middleware\RoleMiddleware;

$projectRoot = dirname(__DIR__, 3);
require $projectRoot . '/vendor/autoload.php';
Environment::load($projectRoot . '/.env');
if (Environment::get('APP_ENV') !== 'local') {
    throw new RuntimeException('Esta prueba solo se ejecuta en APP_ENV=local.');
}
$pdo = (new PdoConnectionFactory(DatabaseConfig::fromEnvironment()))->create();
$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException('FALLO: ' . $message);
    }
    echo 'OK: ' . $message . PHP_EOL;
};
$expectFailure = static function (callable $operation, string $message) use ($assert): void {
    try {
        $operation();
    } catch (Throwable) {
        $assert(true, $message);
        return;
    }
    $assert(false, $message);
};

$suffix = bin2hex(random_bytes(5));
$userIds = [];
$clientIds = [];
$productId = null;
$saleId = null;
$purchaseId = null;
$testCategoryId = null;

try {
    $role = $pdo->prepare('SELECT id_rol FROM roles WHERE codigo = :code');
    $createUser = $pdo->prepare("INSERT INTO usuarios (id_rol, nombre, email, password_hash, estado) VALUES (:role, :name, :email, :password, 'activo')");
    foreach (['CLIENTE', 'CLIENTE', 'VENDEDOR', 'ALMACENERO', 'CONTABLE'] as $index => $roleCode) {
        $role->execute(['code' => $roleCode]);
        $roleId = $role->fetchColumn();
        if ($roleId === false) {
            throw new RuntimeException("Falta el rol $roleCode.");
        }
        $createUser->execute(['role' => $roleId, 'name' => "Prueba RF $index", 'email' => "rf-$suffix-$index@example.invalid", 'password' => password_hash('PruebaSegura123!', PASSWORD_DEFAULT)]);
        $userIds[] = (int) $pdo->lastInsertId();
    }
    $createClient = $pdo->prepare("INSERT INTO clientes (id_usuario, nombre, estado) VALUES (:user, :name, 'activo')");
    foreach (array_slice($userIds, 0, 2) as $index => $userId) {
        $createClient->execute(['user' => $userId, 'name' => "Cliente prueba RF $index"]);
        $clientIds[] = (int) $pdo->lastInsertId();
    }
    $categories = new ManageCategories(new MariaDbCategoryRepository($pdo));
    $testCategoryName = 'Categoría prueba RF ' . $suffix;
    $testCategoryId = $categories->create(['nombre' => $testCategoryName, 'descripcion' => 'Temporal']);
    $expectFailure(fn () => $categories->create(['nombre' => $testCategoryName]), 'se rechazan categorías duplicadas');
    $categoryId = $testCategoryId;
    $deliveryId = (int) $pdo->query("SELECT id_metodo_entrega FROM metodos_entrega WHERE estado = 'activo' AND tipo = 'despacho' ORDER BY id_metodo_entrega LIMIT 1")->fetchColumn();
    if ($categoryId <= 0 || $deliveryId <= 0) {
        throw new RuntimeException('Faltan catálogos base para ejecutar la prueba.');
    }
    $productData = ['id_categoria' => $categoryId, 'codigo' => 'TEST-RF-' . strtoupper($suffix), 'nombre' => 'Producto temporal prueba RF', 'precio_compra' => '5.00', 'precio_venta' => '10.00', 'stock_minimo' => 0, 'stock_maximo' => 10, 'estado' => 'activo'];
    $products = new ManageProducts($pdo);
    $productId = $products->create($productData, null);
    $assert((int) $products->find($productId)['stock_actual'] === 0, 'un vendedor crea productos sin modificar stock');
    (new ManageInventory($pdo))->record($productId, $userIds[3], 'ENTRADA', 2, 'Stock temporal prueba RF');
    $products->update($productId, array_merge($productData, ['nombre' => 'Producto temporal modificado']), null);
    $assert((int) $products->find($productId)['stock_actual'] === 2, 'editar un producto conserva su stock');
    $expectFailure(fn () => $categories->delete($testCategoryId), 'una categoría con productos no puede eliminarse');

    $cart = new ManageCart($pdo);
    $cart->addClient($userIds[0], $productId, 1);
    $cart->addClient($userIds[1], $productId, 1);
    $cart->addSeller($userIds[2], $productId, 1);
    $assert(count($cart->clientItems($userIds[0])) === 1 && count($cart->clientItems($userIds[1])) === 1, 'los carritos de clientes están aislados');
    $assert(count($cart->sellerItems($userIds[2])) === 1, 'el carrito del vendedor es independiente');
    $expectFailure(fn () => $cart->updateClient($userIds[0], $productId, 3), 'se rechaza cantidad superior al stock');

    $_SESSION = [];
    $session = new Session();
    $session->put('user', ['id' => $userIds[0], 'name' => 'Cliente prueba', 'email' => "rf-$suffix-0@example.invalid", 'role' => 'CLIENTE']);
    $middleware = new RoleMiddleware($session, new ViewRenderer($projectRoot, $session), $pdo);
    $assert($middleware->handle(['CLIENTE']) === null, 'el cliente accede a su responsabilidad');
    $assert($middleware->handle(['VENDEDOR'])?->status() === 403, 'el cliente no obtiene permisos de vendedor');
    $cartPage = (new CartController($cart, $session, new ViewRenderer($projectRoot, $session)))->clientIndex(new Request('GET', '/carrito'));
    $assert($cartPage->status() === 200 && str_contains($cartPage->body(), 'Mi carrito'), 'la interfaz del carrito de cliente se renderiza');
    $users = new ManageUsers($pdo);
    $expectFailure(fn () => $users->update($userIds[0], $userIds[0], ['nombre' => 'Prueba RF 0', 'email' => "rf-$suffix-0@example.invalid", 'rol' => 'VENDEDOR', 'estado' => 'activo', 'password' => '']), 'un usuario no puede elevar su propio rol');

    $token = bin2hex(random_bytes(32));
    $first = $cart->checkoutClient($userIds[0], $deliveryId, $token);
    $second = $cart->checkoutClient($userIds[0], $deliveryId, $token);
    $saleId = (int) $first['id_venta'];
    $assert($saleId === (int) $second['id_venta'], 'la confirmación repetida devuelve la misma venta');
    $stock = (int) $pdo->query("SELECT stock_actual FROM productos WHERE id_producto = $productId")->fetchColumn();
    $assert($stock === 1, 'la confirmación descuenta stock una sola vez');

    $shipping = new ManageShipping($pdo);
    $shippingData = ['destinatario' => 'Cliente prueba', 'telefono' => '70000000', 'direccion' => 'Dirección temporal de prueba', 'transportista' => 'Prueba', 'numero_guia' => 'TEST-' . $suffix, 'observacion' => 'Dato temporal'];
    foreach (['pendiente', 'preparando', 'despachado', 'entregado'] as $shippingStatus) {
        $shipping->save($saleId, $userIds[3], $shippingData + ['estado' => $shippingStatus]);
    }
    $expectFailure(fn () => $shipping->save($saleId, $userIds[3], $shippingData + ['estado' => 'pendiente']), 'se rechaza una transición logística regresiva');

    $supplierId = (int) $pdo->query("SELECT id_proveedor FROM proveedores WHERE estado = 'activo' ORDER BY id_proveedor LIMIT 1")->fetchColumn();
    if ($supplierId <= 0) {
        throw new RuntimeException('Falta un proveedor activo para la prueba.');
    }
    $accounting = new ManageAccounting($pdo);
    $purchaseId = $accounting->createPurchase($userIds[4], ['id_proveedor' => $supplierId, 'fecha_compra' => date('Y-m-d'), 'observacion' => 'Compra temporal prueba RF', 'id_producto' => [$productId], 'cantidad' => [2], 'precio_unitario' => ['5.00']]);
    $inventory = new ManageInventory($pdo);
    $inventory->receivePurchase($purchaseId, $userIds[3], 'Recepción temporal prueba RF');
    $expectFailure(fn () => $inventory->receivePurchase($purchaseId, $userIds[3], 'Recepción duplicada'), 'una compra no puede incrementar stock dos veces');
    $stock = (int) $pdo->query("SELECT stock_actual FROM productos WHERE id_producto = $productId")->fetchColumn();
    $assert($stock === 3, 'la recepción de compra incrementa stock exactamente una vez');
    $expectFailure(fn () => $inventory->record($productId, $userIds[3], 'SALIDA', 4, 'Prueba de stock insuficiente'), 'una salida no puede producir stock negativo');
    $assert(array_keys(ManageUsers::PERMISSIONS) === ['ADMIN', 'VENDEDOR', 'ALMACENERO', 'CONTABLE', 'CLIENTE'], 'los permisos usan exclusivamente los roles autorizados');
} finally {
    if ($saleId !== null) {
        $statement = $pdo->prepare('DELETE FROM envios WHERE id_venta = :id');
        $statement->execute(['id' => $saleId]);
        $statement = $pdo->prepare('DELETE FROM movimientos_stock WHERE id_venta_referencia = :id');
        $statement->execute(['id' => $saleId]);
        $statement = $pdo->prepare('DELETE FROM detalle_ventas WHERE id_venta = :id');
        $statement->execute(['id' => $saleId]);
        $statement = $pdo->prepare('DELETE FROM ventas WHERE id_venta = :id');
        $statement->execute(['id' => $saleId]);
    }
    if ($purchaseId !== null) {
        $statement = $pdo->prepare('DELETE FROM movimientos_stock WHERE id_compra_referencia = :id');
        $statement->execute(['id' => $purchaseId]);
        $statement = $pdo->prepare('DELETE FROM detalle_compras WHERE id_compra = :id');
        $statement->execute(['id' => $purchaseId]);
        $statement = $pdo->prepare('DELETE FROM compras WHERE id_compra = :id');
        $statement->execute(['id' => $purchaseId]);
    }
    if ($productId !== null) {
        $statement = $pdo->prepare('DELETE FROM vendedor_carrito_items WHERE id_producto = :id');
        $statement->execute(['id' => $productId]);
        $statement = $pdo->prepare('DELETE FROM carrito_items WHERE id_producto = :id');
        $statement->execute(['id' => $productId]);
        $statement = $pdo->prepare('DELETE FROM movimientos_stock WHERE id_producto = :id');
        $statement->execute(['id' => $productId]);
        $statement = $pdo->prepare('DELETE FROM productos WHERE id_producto = :id');
        $statement->execute(['id' => $productId]);
    }
    if ($testCategoryId !== null) {
        $statement = $pdo->prepare('DELETE FROM categorias WHERE id_categoria = :id');
        $statement->execute(['id' => $testCategoryId]);
    }
    foreach (array_reverse($clientIds) as $id) {
        $statement = $pdo->prepare('DELETE FROM clientes WHERE id_cliente = :id');
        $statement->execute(['id' => $id]);
    }
    foreach (array_reverse($userIds) as $id) {
        $statement = $pdo->prepare('DELETE FROM usuarios WHERE id_usuario = :id');
        $statement->execute(['id' => $id]);
    }
}
echo 'Pruebas RF completadas y datos temporales eliminados.' . PHP_EOL;
