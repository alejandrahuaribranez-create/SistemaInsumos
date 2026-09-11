<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Middleware;

use PDO;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class RoleMiddleware
{
    private AuthMiddleware $auth;

    public function __construct(
        private Session $session,
        private ViewRenderer $views,
        private ?PDO $pdo = null
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

        if ($user !== null && $this->pdo !== null) {
            $statement = $this->pdo->prepare(
                "SELECT r.codigo FROM usuarios u INNER JOIN roles r ON r.id_rol = u.id_rol
                 WHERE u.id_usuario = :id AND u.estado = 'activo' AND r.estado = 'activo' LIMIT 1"
            );
            $statement->execute(['id' => $user['id']]);
            $currentRole = $statement->fetchColumn();
            if (!is_string($currentRole) || $currentRole !== $user['role']) {
                $this->session->remove('user');
                $this->session->flash('warning', 'Tu sesión cambió o la cuenta fue desactivada. Inicia sesión nuevamente.');
                return Response::redirect('/login');
            }
        }

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
