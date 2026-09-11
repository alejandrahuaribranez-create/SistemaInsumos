<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Inventory\Presentation\Controllers;

use RedInsumos\Modules\Inventory\Application\ManageShipping;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class ShippingController
{
    public function __construct(private ManageShipping $shipping, private Session $session, private ViewRenderer $views) {}

    public function index(Request $request): Response
    {
        return $this->views->render('src/Modules/Inventory/Presentation/Views/shipping.php', ['title' => 'Despachos', 'orders' => $this->shipping->orders()]);
    }

    public function update(Request $request): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión del formulario expiró.');
            }
            $this->shipping->save((int) $request->route('id', 0), (int) $this->session->user()['id'], $request->body());
            $this->session->flash('success', 'Datos y estado de envío actualizados.');
        } catch (\Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect('/almacen/envios');
    }
}
