<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Presentation\Controllers;

use PDO;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class AccountController
{
    public function __construct(private PDO $pdo, private Session $session, private ViewRenderer $views) {}

    public function show(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Identity/Presentation/Views/account.php',
            ['title' => 'Mi cuenta', 'orders' => $this->orders()]
        );
    }

    private function orders(): array
    {
        $statement = $this->pdo->prepare('SELECT v.id_venta, v.codigo_pedido, v.fecha_venta, v.total, v.estado_pago, v.estado_logistico FROM ventas v INNER JOIN clientes c ON c.id_cliente = v.id_cliente WHERE c.id_usuario = :user ORDER BY v.fecha_venta DESC, v.id_venta DESC LIMIT 50');
        $statement->execute(['user' => $this->session->user()['id']]);
        return $statement->fetchAll();
    }
}
