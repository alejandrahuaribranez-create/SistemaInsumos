<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Sales\Presentation\Controllers;

use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class SellerController
{
    public function __construct(private ViewRenderer $views)
    {
    }

    public function index(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Sales/Presentation/Views/dashboard.php',
            ['title' => 'Ventas']
        );
    }
}
