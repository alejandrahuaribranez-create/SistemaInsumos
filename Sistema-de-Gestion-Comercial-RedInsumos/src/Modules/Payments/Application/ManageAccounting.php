<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Payments\Application;

use DateTimeImmutable;
use InvalidArgumentException;
use PDO;
use Throwable;

final class ManageAccounting
{
    public function __construct(private PDO $pdo) {}

    public function sales(): array
    {
        $sales = $this->pdo->query('SELECT v.*, c.nombre AS contraparte, u.nombre AS responsable, me.nombre AS entrega FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.id_cliente LEFT JOIN usuarios u ON u.id_usuario = v.id_usuario INNER JOIN metodos_entrega me ON me.id_metodo_entrega = v.id_metodo_entrega ORDER BY v.fecha_venta DESC, v.id_venta DESC LIMIT 100')->fetchAll();
        $details = $this->pdo->prepare('SELECT codigo_producto, nombre_producto, cantidad, precio_unitario, subtotal FROM detalle_ventas WHERE id_venta = :id ORDER BY id_detalle_venta');
        foreach ($sales as &$sale) {
            $details->execute(['id' => $sale['id_venta']]);
            $sale['details'] = $details->fetchAll();
        }
        unset($sale);
        return $sales;
    }

    public function purchases(): array
    {
        $purchases = $this->pdo->query('SELECT c.*, COALESCE(p.nombre_comercial, p.razon_social) AS contraparte, u.nombre AS responsable FROM compras c INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor INNER JOIN usuarios u ON u.id_usuario = c.id_usuario ORDER BY c.fecha_compra DESC, c.id_compra DESC LIMIT 100')->fetchAll();
        $details = $this->pdo->prepare('SELECT codigo_producto, nombre_producto, cantidad, precio_unitario, subtotal FROM detalle_compras WHERE id_compra = :id ORDER BY id_detalle_compra');
        foreach ($purchases as &$purchase) {
            $details->execute(['id' => $purchase['id_compra']]);
            $purchase['details'] = $details->fetchAll();
        }
        unset($purchase);
        return $purchases;
    }

    public function suppliers(): array
    {
        return $this->pdo->query("SELECT id_proveedor, COALESCE(nombre_comercial, razon_social) AS nombre FROM proveedores WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }

    public function products(): array
    {
        return $this->pdo->query("SELECT id_producto, codigo, nombre, precio_compra FROM productos WHERE estado = 'activo' ORDER BY nombre")->fetchAll();
    }

    public function payments(): array
    {
        return $this->pdo->query('SELECT vp.*, v.codigo_pedido, mp.nombre AS metodo FROM venta_pago vp INNER JOIN ventas v ON v.id_venta = vp.id_venta INNER JOIN metodos_pago mp ON mp.id_metodo_pago = vp.id_metodo_pago ORDER BY vp.fecha_pago DESC, vp.id_venta_pago DESC LIMIT 100')->fetchAll();
    }

    public function createPurchase(int $userId, array $data): int
    {
        $supplierId = (int) ($data['id_proveedor'] ?? 0);
        $date = trim((string) ($data['fecha_compra'] ?? ''));
        $observation = trim((string) ($data['observacion'] ?? ''));
        $dateObject = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
        if ($dateObject === false || $dateObject->format('Y-m-d') !== $date) {
            throw new InvalidArgumentException('La fecha de compra no es válida.');
        }
        if (mb_strlen($observation) > 500) {
            throw new InvalidArgumentException('La observación supera 500 caracteres.');
        }
        $supplier = $this->pdo->prepare("SELECT 1 FROM proveedores WHERE id_proveedor = :id AND estado = 'activo'");
        $supplier->execute(['id' => $supplierId]);
        if ($supplier->fetchColumn() === false) {
            throw new InvalidArgumentException('El proveedor no está disponible.');
        }

        $ids = is_array($data['id_producto'] ?? null) ? $data['id_producto'] : [];
        $quantities = is_array($data['cantidad'] ?? null) ? $data['cantidad'] : [];
        $prices = is_array($data['precio_unitario'] ?? null) ? $data['precio_unitario'] : [];
        $lines = [];
        foreach ($ids as $index => $rawId) {
            $productId = (int) $rawId;
            if ($productId <= 0) {
                continue;
            }
            if (isset($lines[$productId])) {
                throw new InvalidArgumentException('No repitas un producto en la misma compra.');
            }
            $quantity = filter_var($quantities[$index] ?? null, FILTER_VALIDATE_INT);
            $priceText = str_replace(',', '.', trim((string) ($prices[$index] ?? '')));
            if ($quantity === false || $quantity <= 0 || !is_numeric($priceText) || (float) $priceText < 0) {
                throw new InvalidArgumentException('Cantidad o precio inválido en el detalle de compra.');
            }
            $product = $this->pdo->prepare("SELECT codigo, nombre FROM productos WHERE id_producto = :id AND estado = 'activo'");
            $product->execute(['id' => $productId]);
            $row = $product->fetch();
            if (!is_array($row)) {
                throw new InvalidArgumentException('Uno de los productos no existe.');
            }
            $lines[$productId] = ['id' => $productId, 'code' => $row['codigo'], 'name' => $row['nombre'], 'quantity' => (int) $quantity, 'price' => number_format((float) $priceText, 2, '.', '')];
        }
        if ($lines === []) {
            throw new InvalidArgumentException('Agrega al menos un producto a la compra.');
        }
        $subtotal = array_reduce($lines, static fn (float $sum, array $line): float => $sum + $line['quantity'] * (float) $line['price'], 0.0);

        $this->pdo->beginTransaction();
        try {
            $header = $this->pdo->prepare("INSERT INTO compras (id_proveedor, id_usuario, fecha_compra, subtotal, impuesto, total, estado, observacion) VALUES (:supplier, :user, :date, :subtotal, 0, :total, 'pendiente', :observation)");
            $header->execute(['supplier' => $supplierId, 'user' => $userId, 'date' => $date, 'subtotal' => number_format($subtotal, 2, '.', ''), 'total' => number_format($subtotal, 2, '.', ''), 'observation' => $observation !== '' ? $observation : null]);
            $purchaseId = (int) $this->pdo->lastInsertId();
            $detail = $this->pdo->prepare('INSERT INTO detalle_compras (id_compra, id_producto, codigo_producto, nombre_producto, cantidad, precio_unitario) VALUES (:purchase, :product, :code, :name, :quantity, :price)');
            foreach ($lines as $line) {
                $detail->execute(['purchase' => $purchaseId, 'product' => $line['id'], 'code' => $line['code'], 'name' => $line['name'], 'quantity' => $line['quantity'], 'price' => $line['price']]);
            }
            $this->pdo->commit();
            return $purchaseId;
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }

    public function cancelPurchase(int $id, string $observation): void
    {
        $statement = $this->pdo->prepare("UPDATE compras SET estado = 'cancelado', observacion = :observation WHERE id_compra = :id AND estado = 'pendiente'");
        $statement->execute(['id' => $id, 'observation' => trim($observation) !== '' ? mb_substr(trim($observation), 0, 500) : 'Cancelada por contabilidad']);
        if ($statement->rowCount() !== 1) {
            throw new InvalidArgumentException('Solo puede cancelarse una compra pendiente.');
        }
    }

    public function reconcilePayment(int $paymentId, string $status, string $observation): void
    {
        if (!in_array($status, ['conciliado', 'rechazado'], true)) {
            throw new InvalidArgumentException('El estado de conciliación no es válido.');
        }
        $statement = $this->pdo->prepare('CALL sp_conciliar_pago(:payment, :status, :observation)');
        $statement->execute(['payment' => $paymentId, 'status' => $status, 'observation' => trim($observation) !== '' ? mb_substr(trim($observation), 0, 500) : null]);
        $statement->closeCursor();
    }
}
