<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Inventory\Application;

use InvalidArgumentException;
use PDO;
use Throwable;

final class ManageInventory
{
    public function __construct(private PDO $pdo) {}

    public function products(): array
    {
        return $this->pdo->query('SELECT id_producto, codigo, nombre, stock_actual, stock_minimo, stock_maximo, ubicacion, estado FROM productos ORDER BY nombre')->fetchAll();
    }

    public function movements(): array
    {
        return $this->pdo->query('SELECT ms.*, p.codigo, p.nombre AS producto, u.nombre AS usuario FROM movimientos_stock ms INNER JOIN productos p ON p.id_producto = ms.id_producto INNER JOIN usuarios u ON u.id_usuario = ms.id_usuario ORDER BY ms.fecha_movimiento DESC, ms.id_movimiento DESC LIMIT 100')->fetchAll();
    }

    public function pendingPurchases(): array
    {
        return $this->pdo->query("SELECT c.id_compra, c.fecha_compra, c.total, COALESCE(p.nombre_comercial, p.razon_social) AS proveedor FROM compras c INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor WHERE c.estado = 'pendiente' ORDER BY c.fecha_compra, c.id_compra")->fetchAll();
    }

    public function receivePurchase(int $purchaseId, int $userId, string $reason): void
    {
        $reason = trim($reason);
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('El motivo de recepción es obligatorio.');
        }
        $statement = $this->pdo->prepare('CALL sp_registrar_ingreso_stock(:purchase, :user, :reason)');
        $statement->execute(['purchase' => $purchaseId, 'user' => $userId, 'reason' => $reason]);
        $statement->closeCursor();
    }

    public function record(int $productId, int $userId, string $type, int $quantity, string $reason): void
    {
        $type = strtoupper(trim($type));
        $reason = trim($reason);
        if (!in_array($type, ['ENTRADA', 'SALIDA', 'AJUSTE'], true)) {
            throw new InvalidArgumentException('El tipo de movimiento no es válido.');
        }
        if ($quantity < 0 || ($quantity === 0 && $type !== 'AJUSTE')) {
            throw new InvalidArgumentException('La cantidad debe ser mayor a cero.');
        }
        if ($reason === '' || mb_strlen($reason) > 500) {
            throw new InvalidArgumentException('El motivo es obligatorio y admite hasta 500 caracteres.');
        }

        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('SELECT stock_actual FROM productos WHERE id_producto = :id FOR UPDATE');
            $statement->execute(['id' => $productId]);
            $current = $statement->fetchColumn();
            if ($current === false) {
                throw new InvalidArgumentException('El producto no existe.');
            }
            $current = (int) $current;
            $new = match ($type) {
                'ENTRADA' => $current + $quantity,
                'SALIDA' => $current - $quantity,
                'AJUSTE' => $quantity,
            };
            if ($new < 0) {
                throw new InvalidArgumentException('El movimiento produciría stock negativo.');
            }
            $movementQuantity = abs($new - $current);
            if ($movementQuantity === 0) {
                throw new InvalidArgumentException('El ajuste no cambia el stock actual.');
            }
            $movement = $this->pdo->prepare('INSERT INTO movimientos_stock (id_producto, id_usuario, tipo_movimiento, cantidad, stock_anterior, stock_nuevo, referencia, observacion) VALUES (:product, :user, :type, :quantity, :previous, :new, :reference, :reason)');
            $movement->execute(['product' => $productId, 'user' => $userId, 'type' => $type, 'quantity' => $movementQuantity, 'previous' => $current, 'new' => $new, 'reference' => 'MANUAL-' . date('YmdHis'), 'reason' => $reason]);
            $update = $this->pdo->prepare('UPDATE productos SET stock_actual = :stock WHERE id_producto = :id');
            $update->execute(['stock' => $new, 'id' => $productId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
