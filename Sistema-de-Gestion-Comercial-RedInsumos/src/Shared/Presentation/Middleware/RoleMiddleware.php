<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Middleware;

use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class RoleMiddleware
{
    private AuthMiddleware $auth;

    public function __construct(
        private Session $session,
        private ViewRenderer $views
    ) {
        $this->auth = new AuthMiddleware($session);
    }

    /** @param list<string> $allowedRoles */
    public function handle(array $allowedRoles): ?Response
    {
        $authResponse = $this->auth->handle();

        if ($authResponse !== null) {
            return $authResponse;
        }

        $user = $this->session->user();

        if ($user === null || !in_array($user['role'], $allowedRoles, true)) {
            return $this->views->render(
                'src/Shared/Presentation/Views/errors/403.php',
                ['title' => 'Acceso denegado'],
                403
            );
        }

        return null;
    }
}
