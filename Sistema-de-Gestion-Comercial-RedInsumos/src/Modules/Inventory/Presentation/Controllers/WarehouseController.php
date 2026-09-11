<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Inventory\Presentation\Controllers;

use RedInsumos\Modules\Inventory\Application\ManageInventory;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class WarehouseController
{
    public function __construct(private ManageInventory $inventory, private Session $session, private ViewRenderer $views) {}

    public function index(Request $request): Response
    {
        return $this->views->render('src/Modules/Inventory/Presentation/Views/dashboard.php', [
            'title' => 'Almacén',
            'products' => $this->inventory->products(),
            'movements' => $this->inventory->movements(),
            'pendingPurchases' => $this->inventory->pendingPurchases(),
        ]);
    }

    public function movement(Request $request): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión del formulario expiró.');
            }
            $this->inventory->record(
                (int) $request->input('id_producto', 0),
                (int) $this->session->user()['id'],
                (string) $request->input('tipo_movimiento', ''),
                (int) $request->input('cantidad', -1),
                (string) $request->input('motivo', '')
            );
            $this->session->flash('success', 'Movimiento registrado con trazabilidad.');
        } catch (\Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect('/almacen');
    }

    public function receivePurchase(Request $request): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión del formulario expiró.');
            }
            $this->inventory->receivePurchase((int) $request->route('id', 0), (int) $this->session->user()['id'], (string) $request->input('motivo', ''));
            $this->session->flash('success', 'Compra recibida y stock incrementado una sola vez.');
        } catch (\Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect('/almacen');
    }
}
