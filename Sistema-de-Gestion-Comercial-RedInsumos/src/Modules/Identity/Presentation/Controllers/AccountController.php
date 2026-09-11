<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Presentation\Controllers;

use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class AccountController
{
    public function __construct(private ViewRenderer $views)
    {
    }

    public function show(Request $request): Response
    {
        return $this->views->render(
            'src/Modules/Identity/Presentation/Views/account.php',
            ['title' => 'Mi cuenta']
        );
    }
}
