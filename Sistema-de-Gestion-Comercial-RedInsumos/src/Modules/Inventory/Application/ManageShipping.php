<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Inventory\Application;

use InvalidArgumentException;
use PDO;
use Throwable;

final class ManageShipping
{
    private const TRANSITIONS = [
        '' => ['pendiente'],
        'pendiente' => ['pendiente', 'preparando'],
        'preparando' => ['preparando', 'despachado'],
        'despachado' => ['despachado', 'entregado'],
        'entregado' => ['entregado'],
    ];

    private const SALE_STATUS = [
        'pendiente' => 'pendiente',
        'preparando' => 'empacado',
        'despachado' => 'enviado',
        'entregado' => 'entregado',
    ];

    public function __construct(private PDO $pdo) {}

    public function orders(): array
    {
        return $this->pdo->query("SELECT v.id_venta, v.codigo_pedido, v.fecha_venta, v.total, v.estado_logistico,
                    c.nombre AS cliente, c.telefono AS cliente_telefono,
                    e.id_envio, e.destinatario, e.telefono, e.direccion, e.transportista,
                    e.numero_guia, e.estado AS estado_envio, e.observacion
             FROM ventas v
             INNER JOIN clientes c ON c.id_cliente = v.id_cliente
             INNER JOIN metodos_entrega me ON me.id_metodo_entrega = v.id_metodo_entrega
             LEFT JOIN envios e ON e.id_venta = v.id_venta
             WHERE me.tipo = 'despacho' AND v.estado_logistico <> 'cancelado'
             ORDER BY v.fecha_venta DESC, v.id_venta DESC")->fetchAll();
    }

    public function save(int $saleId, int $userId, array $data): void
    {
        $recipient = trim((string) ($data['destinatario'] ?? ''));
        $phone = trim((string) ($data['telefono'] ?? ''));
        $address = trim((string) ($data['direccion'] ?? ''));
        $carrier = trim((string) ($data['transportista'] ?? ''));
        $tracking = trim((string) ($data['numero_guia'] ?? ''));
        $status = (string) ($data['estado'] ?? 'pendiente');
        $observation = trim((string) ($data['observacion'] ?? ''));
        if ($recipient === '' || mb_strlen($recipient) > 150 || $address === '' || mb_strlen($address) > 255) {
            throw new InvalidArgumentException('Destinatario y dirección son obligatorios.');
        }
        if (($phone !== '' && mb_strlen($phone) > 20) || mb_strlen($carrier) > 100 || mb_strlen($tracking) > 100 || mb_strlen($observation) > 500) {
            throw new InvalidArgumentException('Uno de los datos de envío supera la longitud permitida.');
        }

        $this->pdo->beginTransaction();
        try {
            $sale = $this->pdo->prepare("SELECT v.estado_logistico FROM ventas v INNER JOIN metodos_entrega me ON me.id_metodo_entrega = v.id_metodo_entrega WHERE v.id_venta = :id AND me.tipo = 'despacho' FOR UPDATE");
            $sale->execute(['id' => $saleId]);
            $saleStatus = $sale->fetchColumn();
            if ($saleStatus === false) {
                throw new InvalidArgumentException('El pedido no existe o no requiere despacho.');
            }
            if ($saleStatus === 'cancelado') {
                throw new InvalidArgumentException('Un pedido cancelado no puede despacharse.');
            }
            $current = $this->pdo->prepare('SELECT estado FROM envios WHERE id_venta = :sale FOR UPDATE');
            $current->execute(['sale' => $saleId]);
            $currentStatus = (string) ($current->fetchColumn() ?: '');
            if ($currentStatus === '' && !in_array($saleStatus, ['pendiente', 'procesando'], true)) {
                throw new InvalidArgumentException('El estado actual del pedido no permite iniciar un envío.');
            }
            if (!isset(self::TRANSITIONS[$currentStatus]) || !in_array($status, self::TRANSITIONS[$currentStatus], true)) {
                throw new InvalidArgumentException('La transición de envío solicitada no es válida.');
            }
            $statement = $this->pdo->prepare("INSERT INTO envios (id_venta, id_usuario, destinatario, telefono, direccion, transportista, numero_guia, estado, observacion) VALUES (:sale, :user, :recipient, :phone, :address, :carrier, :tracking, :status, :observation) ON DUPLICATE KEY UPDATE id_usuario = VALUES(id_usuario), destinatario = VALUES(destinatario), telefono = VALUES(telefono), direccion = VALUES(direccion), transportista = VALUES(transportista), numero_guia = VALUES(numero_guia), estado = VALUES(estado), observacion = VALUES(observacion)");
            $statement->execute(['sale' => $saleId, 'user' => $userId, 'recipient' => $recipient, 'phone' => $phone !== '' ? $phone : null, 'address' => $address, 'carrier' => $carrier !== '' ? $carrier : null, 'tracking' => $tracking !== '' ? $tracking : null, 'status' => $status, 'observation' => $observation !== '' ? $observation : null]);
            $update = $this->pdo->prepare('UPDATE ventas SET estado_logistico = :status WHERE id_venta = :id');
            $update->execute(['status' => self::SALE_STATUS[$status], 'id' => $saleId]);
            $this->pdo->commit();
        } catch (Throwable $exception) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $exception;
        }
    }
}
