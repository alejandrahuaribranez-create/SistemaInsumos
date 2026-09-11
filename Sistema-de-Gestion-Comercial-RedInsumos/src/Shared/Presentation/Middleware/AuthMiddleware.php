<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Middleware;

use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Response;

final class AuthMiddleware
{
    public function __construct(private Session $session)
    {
    }

    public function handle(): ?Response
    {
        if ($this->session->user() !== null) {
            return null;
        }

        $this->session->flash('warning', 'Debes iniciar sesión para continuar.');

        return Response::redirect('/login');
    }
}
