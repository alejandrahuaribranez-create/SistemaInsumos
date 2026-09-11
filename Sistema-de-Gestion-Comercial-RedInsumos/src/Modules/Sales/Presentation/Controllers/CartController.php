<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Sales\Presentation\Controllers;

use RedInsumos\Modules\Sales\Application\ManageCart;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;
use Throwable;

final class CartController
{
    public function __construct(private ManageCart $cart, private Session $session, private ViewRenderer $views) {}

    public function clientIndex(Request $request): Response
    {
        return $this->render(false);
    }

    public function sellerIndex(Request $request): Response
    {
        return $this->render(true);
    }

    public function addClient(Request $request): Response
    {
        return $this->mutate($request, false, fn (int $userId) => $this->cart->addClient($userId, (int) $request->input('id_producto', 0), (int) $request->input('cantidad', 1)), 'Producto agregado al carrito.');
    }

    public function addSeller(Request $request): Response
    {
        return $this->mutate($request, true, fn (int $userId) => $this->cart->addSeller($userId, (int) $request->input('id_producto', 0), (int) $request->input('cantidad', 1)), 'Producto agregado a la venta asistida.');
    }

    public function updateClient(Request $request): Response
    {
        return $this->mutate($request, false, fn (int $userId) => $this->cart->updateClient($userId, (int) $request->route('id', 0), (int) $request->input('cantidad', 0)), 'Cantidad actualizada.');
    }

    public function updateSeller(Request $request): Response
    {
        return $this->mutate($request, true, fn (int $userId) => $this->cart->updateSeller($userId, (int) $request->route('id', 0), (int) $request->input('cantidad', 0)), 'Cantidad actualizada.');
    }

    public function removeClient(Request $request): Response
    {
        return $this->mutate($request, false, fn (int $userId) => $this->cart->removeClient($userId, (int) $request->route('id', 0)), 'Producto retirado.');
    }

    public function removeSeller(Request $request): Response
    {
        return $this->mutate($request, true, fn (int $userId) => $this->cart->removeSeller($userId, (int) $request->route('id', 0)), 'Producto retirado.');
    }

    public function checkoutClient(Request $request): Response
    {
        return $this->checkout($request, false);
    }

    public function checkoutSeller(Request $request): Response
    {
        return $this->checkout($request, true);
    }

    private function render(bool $seller): Response
    {
        $userId = (int) $this->session->user()['id'];
        return $this->views->render('src/Modules/Sales/Presentation/Views/cart.php', [
            'title' => $seller ? 'Venta asistida' : 'Mi carrito',
            'sellerMode' => $seller,
            'items' => $seller ? $this->cart->sellerItems($userId) : $this->cart->clientItems($userId),
            'products' => $this->cart->availableProducts(),
            'clients' => $seller ? $this->cart->activeClients() : [],
            'deliveryMethods' => $this->cart->deliveryMethods(),
            'checkoutToken' => $this->checkoutToken($seller),
        ]);
    }

    private function mutate(Request $request, bool $seller, callable $operation, string $message): Response
    {
        try {
            $this->assertCsrf($request);
            $operation((int) $this->session->user()['id']);
            $this->session->remove($this->tokenKey($seller));
            $this->session->flash('success', $message);
        } catch (Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect($seller ? '/vendedor/carrito' : '/carrito');
    }

    private function checkout(Request $request, bool $seller): Response
    {
        try {
            $this->assertCsrf($request);
            $sentToken = (string) $request->input('checkout_token', '');
            $sessionToken = (string) $this->session->get($this->tokenKey($seller), '');
            if ($sentToken === '' || $sessionToken === '' || !hash_equals($sessionToken, $sentToken)) {
                throw new \InvalidArgumentException('La confirmación expiró. Recarga el carrito.');
            }
            $userId = (int) $this->session->user()['id'];
            $delivery = (int) $request->input('id_metodo_entrega', 0);
            $sale = $seller
                ? $this->cart->checkoutSeller($userId, (int) $request->input('id_cliente', 0), $delivery, $sentToken)
                : $this->cart->checkoutClient($userId, $delivery, $sentToken);
            $this->session->flash('success', sprintf('Pedido %s confirmado por Bs %.2f.', $sale['codigo_pedido'], (float) $sale['total']));
        } catch (Throwable $exception) {
            $this->session->flash('danger', $exception->getMessage());
        }
        return Response::redirect($seller ? '/vendedor/carrito' : '/carrito');
    }

    private function assertCsrf(Request $request): void
    {
        if (!$this->session->isValidCsrf($request->input('_csrf'))) {
            throw new \InvalidArgumentException('La sesión del formulario expiró.');
        }
    }

    private function checkoutToken(bool $seller): string
    {
        $key = $this->tokenKey($seller);
        $token = $this->session->get($key);
        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->session->put($key, $token);
        }
        return $token;
    }

    private function tokenKey(bool $seller): string
    {
        return $seller ? '_checkout_seller' : '_checkout_client';
    }
}
