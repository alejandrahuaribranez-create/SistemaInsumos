<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Payments\Presentation\Controllers;

use RedInsumos\Modules\Payments\Application\ManageAccounting;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class AccountingController
{
    public function __construct(private ManageAccounting $accounting, private Session $session, private ViewRenderer $views) {}

    public function index(Request $request): Response
    {
        return $this->views->render('src/Modules/Payments/Presentation/Views/dashboard.php', [
            'title' => 'Contabilidad', 'sales' => $this->accounting->sales(),
            'purchases' => $this->accounting->purchases(), 'payments' => $this->accounting->payments(),
            'suppliers' => $this->accounting->suppliers(), 'products' => $this->accounting->products(),
        ]);
    }

    public function createPurchase(Request $request): Response
    {
        return $this->write($request, fn () => $this->accounting->createPurchase((int) $this->session->user()['id'], $request->body()), 'Compra registrada como pendiente; aún no modificó stock.');
    }

    public function cancelPurchase(Request $request): Response
    {
        return $this->write($request, fn () => $this->accounting->cancelPurchase((int) $request->route('id', 0), (string) $request->input('observacion', '')), 'Compra cancelada sin generar movimientos de stock.');
    }

    public function reconcilePayment(Request $request): Response
    {
        return $this->write($request, fn () => $this->accounting->reconcilePayment((int) $request->route('id', 0), (string) $request->input('estado', ''), (string) $request->input('observacion', '')), 'Pago actualizado y total de la venta recalculado.');
    }

    private function write(Request $request, callable $operation, string $message): Response
    {
        try {
            if (!$this->session->isValidCsrf($request->input('_csrf'))) {
                throw new \InvalidArgumentException('La sesión del formulario expiró.');
            }
            $operation();
            $this->session->flash('success', $message);
        } catch (\Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect('/contabilidad');
    }
}
