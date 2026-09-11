<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Sales\Application;

use InvalidArgumentException;
use PDO;
use PDOException;
use Throwable;

final class ManageCart
{
    public function __construct(private PDO $pdo) {}

    public function clientIdForUser(int $userId): int
    {
        $statement = $this->pdo->prepare("SELECT id_cliente FROM clientes WHERE id_usuario = :user AND estado = 'activo' LIMIT 1");
        $statement->execute(['user' => $userId]);
        $id = $statement->fetchColumn();
        if ($id === false) {
            throw new InvalidArgumentException('No existe un perfil de cliente activo para esta cuenta.');
        }
        return (int) $id;
    }

    public function availableProducts(): array
    {
        return $this->pdo->query("SELECT id_producto, codigo, nombre, precio_venta, stock_actual FROM productos WHERE estado = 'activo' AND stock_actual > 0 ORDER BY nombre")->fetchAll();
    }

    public function activeClients(): array
    {
        return $this->pdo->query("SELECT id_cliente, nombre, numero_documento FROM clientes WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }

    public function deliveryMethods(): array
    {
        return $this->pdo->query("SELECT id_metodo_entrega, nombre, tipo FROM metodos_entrega WHERE estado = 'activo' ORDER BY id_metodo_entrega")->fetchAll();
    }

    public function clientItems(int $userId): array
    {
        return $this->items('carrito_items', 'id_cliente', $this->clientIdForUser($userId));
    }

    public function sellerItems(int $userId): array
    {
        return $this->items('vendedor_carrito_items', 'id_usuario', $userId);
    }

    public function addClient(int $userId, int $productId, int $quantity): void
    {
        $this->add('carrito_items', 'id_cliente', $this->clientIdForUser($userId), $productId, $quantity);
    }

    public function addSeller(int $userId, int $productId, int $quantity): void
    {
        $this->add('vendedor_carrito_items', 'id_usuario', $userId, $productId, $quantity);
    }

    public function updateClient(int $userId, int $productId, int $quantity): void
    {
        $this->update('carrito_items', 'id_cliente', $this->clientIdForUser($userId), $productId, $quantity);
    }

    public function updateSeller(int $userId, int $productId, int $quantity): void
    {
        $this->update('vendedor_carrito_items', 'id_usuario', $userId, $productId, $quantity);
    }

    public function removeClient(int $userId, int $productId): void
    {
        $this->remove('carrito_items', 'id_cliente', $this->clientIdForUser($userId), $productId);
    }

    public function removeSeller(int $userId, int $productId): void
    {
        $this->remove('vendedor_carrito_items', 'id_usuario', $userId, $productId);
    }

    public function checkoutClient(int $userId, int $deliveryMethodId, string $token): array
    {
        return $this->checkout('carrito_items', 'id_cliente', $this->clientIdForUser($userId), $this->clientIdForUser($userId), $userId, $deliveryMethodId, 'web', $token);
    }

    public function checkoutSeller(int $userId, int $clientId, int $deliveryMethodId, string $token): array
    {
        $this->assertClient($clientId);
        return $this->checkout('vendedor_carrito_items', 'id_usuario', $userId, $clientId, $userId, $deliveryMethodId, 'tienda', $token);
    }

    private function items(string $table, string $ownerColumn, int $ownerId): array
    {
        $this->assertCartTable($table, $ownerColumn);
        $statement = $this->pdo->prepare("SELECT c.id_producto, c.cantidad, p.codigo, p.nombre, p.precio_venta, p.stock_actual, p.estado, (c.cantidad * p.precio_venta) AS subtotal FROM $table c INNER JOIN productos p ON p.id_producto = c.id_producto WHERE c.$ownerColumn = :owner ORDER BY p.nombre");
        $statement->execute(['owner' => $ownerId]);
        return $statement->fetchAll();
    }

    private function add(string $table, string $ownerColumn, int $ownerId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
        $this->assertCartTable($table, $ownerColumn);
        $product = $this->lockableProduct($productId);
        $existing = $this->pdo->prepare("SELECT cantidad FROM $table WHERE $ownerColumn = :owner AND id_producto = :product");
        $existing->execute(['owner' => $ownerId, 'product' => $productId]);
        $newQuantity = (int) ($existing->fetchColumn() ?: 0) + $quantity;
        $this->assertAvailability($product, $newQuantity);
        $statement = $this->pdo->prepare("INSERT INTO $table ($ownerColumn, id_producto, cantidad) VALUES (:owner, :product, :quantity) ON DUPLICATE KEY UPDATE cantidad = VALUES(cantidad)");
        $statement->execute(['owner' => $ownerId, 'product' => $productId, 'quantity' => $newQuantity]);
    }

    private function update(string $table, string $ownerColumn, int $ownerId, int $productId, int $quantity): void
    {
        if ($quantity <= 0) {
            $this->remove($table, $ownerColumn, $ownerId, $productId);
            return;
        }
        $this->assertCartTable($table, $ownerColumn);
        $this->assertAvailability($this->lockableProduct($productId), $quantity);
        $statement = $this->pdo->prepare("UPDATE $table SET cantidad = :quantity WHERE $ownerColumn = :owner AND id_producto = :product");
        $statement->execute(['quantity' => $quantity, 'owner' => $ownerId, 'product' => $productId]);
    }

    private function remove(string $table, string $ownerColumn, int $ownerId, int $productId): void
    {
        $this->assertCartTable($table, $ownerColumn);
        $statement = $this->pdo->prepare("DELETE FROM $table WHERE $ownerColumn = :owner AND id_producto = :product");
        $statement->execute(['owner' => $ownerId, 'product' => $productId]);
    }

    private function checkout(string $table, string $ownerColumn, int $ownerId, int $clientId, int $userId, int $deliveryMethodId, string $origin, string $token): array
    {
        $this->assertCartTable($table, $ownerColumn);
        $tokenHash = hash('sha256', $token);
        $previous = $this->saleByToken($tokenHash);
        if ($previous !== null) {
            return $previous;
        }
        $this->assertDeliveryMethod($deliveryMethodId);
        $this->pdo->beginTransaction();
        try {
            $cart = $this->pdo->prepare("SELECT c.id_producto, c.cantidad, p.codigo, p.nombre, p.precio_venta, p.stock_actual, p.estado FROM $table c INNER JOIN productos p ON p.id_producto = c.id_producto WHERE c.$ownerColumn = :owner ORDER BY c.id_producto FOR UPDATE");
            $cart->execute(['owner' => $ownerId]);
            $items = $cart->fetchAll();
            if ($items === []) {
                throw new InvalidArgumentException('El carrito está vacío.');
            }
            $subtotal = 0.0;
            foreach ($items as $item) {
                $this->assertAvailability($item, (int) $item['cantidad']);
                $subtotal += (int) $item['cantidad'] * (float) $item['precio_venta'];
            }
            $code = 'PED-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
            $sale = $this->pdo->prepare("INSERT INTO ventas (codigo_pedido, token_confirmacion, id_cliente, id_usuario, id_metodo_entrega, origen, subtotal, total, estado_pago, estado_logistico) VALUES (:code, :token, :client, :user, :delivery, :origin, :subtotal, :total, 'pendiente', 'pendiente')");
            $sale->execute(['code' => $code, 'token' => $tokenHash, 'client' => $clientId, 'user' => $userId, 'delivery' => $deliveryMethodId, 'origin' => $origin, 'subtotal' => number_format($subtotal, 2, '.', ''), 'total' => number_format($subtotal, 2, '.', '')]);
            $saleId = (int) $this->pdo->lastInsertId();
            $detail = $this->pdo->prepare('INSERT INTO detalle_ventas (id_venta, id_producto, codigo_producto, nombre_producto, cantidad, precio_unitario) VALUES (:sale, :product, :code, :name, :quantity, :price)');
            $movement = $this->pdo->prepare("INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, referencia, observacion, id_venta_referencia) VALUES (:product, :user, 'SALIDA', :quantity, :previous, :new, :reference, 'Salida por confirmación de pedido', :sale)");
            $stock = $this->pdo->prepare('UPDATE productos SET stock_actual = :stock WHERE id_producto = :product');
            foreach ($items as $item) {
                $newStock = (int) $item['stock_actual'] - (int) $item['cantidad'];
                $detail->execute(['sale' => $saleId, 'product' => $item['id_producto'], 'code' => $item['codigo'], 'name' => $item['nombre'], 'quantity' => $item['cantidad'], 'price' => $item['precio_venta']]);
                $movement->execute(['product' => $item['id_producto'], 'user' => $userId, 'quantity' => $item['cantidad'], 'previous' => $item['stock_actual'], 'new' => $newStock, 'reference' => $code, 'sale' => $saleId]);
                $stock->execute(['stock' => $newStock, 'product' => $item['id_producto']]);
            }
            $clear = $this->pdo->prepare("DELETE FROM $table WHERE $ownerColumn = :owner");
            $clear->execute(['owner' => $ownerId]);
            $this->pdo->commit();
            return ['id_venta' => $saleId, 'codigo_pedido' => $code, 'total' => $subtotal];
        } catch (PDOException $exception) {
            $this->rollBack();
            if ($exception->getCode() === '23000' && ($previous = $this->saleByToken($tokenHash)) !== null) {
                return $previous;
            }
            throw $exception;
        } catch (Throwable $exception) {
            $this->rollBack();
            throw $exception;
        }
    }

    private function lockableProduct(int $id): array
    {
        $statement = $this->pdo->prepare('SELECT id_producto, estado, stock_actual FROM productos WHERE id_producto = :id LIMIT 1');
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();
        if (!is_array($row)) {
            throw new InvalidArgumentException('El producto no existe.');
        }
        return $row;
    }

    private function assertAvailability(array $product, int $quantity): void
    {
        if (($product['estado'] ?? '') !== 'activo') {
            throw new InvalidArgumentException('El producto ya no está disponible.');
        }
        if ($quantity <= 0 || $quantity > (int) ($product['stock_actual'] ?? 0)) {
            throw new InvalidArgumentException('La cantidad solicitada supera el stock disponible.');
        }
    }

    private function assertDeliveryMethod(int $id): void
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM metodos_entrega WHERE id_metodo_entrega = :id AND estado = 'activo'");
        $statement->execute(['id' => $id]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException('El método de entrega no está disponible.');
        }
    }

    private function assertClient(int $id): void
    {
        $statement = $this->pdo->prepare("SELECT 1 FROM clientes WHERE id_cliente = :id AND estado = 'activo'");
        $statement->execute(['id' => $id]);
        if ($statement->fetchColumn() === false) {
            throw new InvalidArgumentException('El cliente no existe o está inactivo.');
        }
    }

    private function saleByToken(string $hash): ?array
    {
        $statement = $this->pdo->prepare('SELECT id_venta, codigo_pedido, total FROM ventas WHERE token_confirmacion = :token LIMIT 1');
        $statement->execute(['token' => $hash]);
        $row = $statement->fetch();
        return is_array($row) ? $row : null;
    }

    private function assertCartTable(string $table, string $ownerColumn): void
    {
        if (!in_array($table . '.' . $ownerColumn, ['carrito_items.id_cliente', 'vendedor_carrito_items.id_usuario'], true)) {
            throw new InvalidArgumentException('Carrito no permitido.');
        }
    }

    private function rollBack(): void
    {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }
}
