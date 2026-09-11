<?php

declare(strict_types=1);

namespace RedInsumos\Modules\Identity\Presentation\Controllers;

use RedInsumos\Modules\Identity\Application\AuthenticateUser;
use RedInsumos\Modules\Identity\Application\RegisterClient;
use RedInsumos\Modules\Identity\Domain\ValidationException;
use RedInsumos\Shared\Infrastructure\Session\Session;
use RedInsumos\Shared\Presentation\Http\Request;
use RedInsumos\Shared\Presentation\Http\Response;
use RedInsumos\Shared\Presentation\Http\ViewRenderer;

final class AuthController
{
    public function __construct(
        private RegisterClient $registerClient,
        private AuthenticateUser $authenticateUser,
        private Session $session,
        private ViewRenderer $views
    ) {
    }

    public function showRegister(Request $request): Response
    {
        if ($this->session->user() !== null) {
            return Response::redirect($this->destination($this->session->user()['role']));
        }

        return $this->views->render(
            'src/Modules/Identity/Presentation/Views/register.php',
            ['title' => 'Registrarse', 'errors' => [], 'old' => []]
        );
    }

    public function register(Request $request): Response
    {
        $body = $request->body();
        $old = array_intersect_key($body, array_flip(['nombre', 'apellido', 'email', 'telefono']));

        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return $this->views->render(
                'src/Modules/Identity/Presentation/Views/register.php',
                ['title' => 'Registrarse', 'errors' => ['form' => 'La sesión del formulario expiró.'], 'old' => $old],
                419
            );
        }

        try {
            $this->registerClient->execute($body);
        } catch (ValidationException $exception) {
            return $this->views->render(
                'src/Modules/Identity/Presentation/Views/register.php',
                ['title' => 'Registrarse', 'errors' => $exception->errors(), 'old' => $old],
                422
            );
        }

        $this->session->flash('success', 'Cuenta creada. Ya puedes iniciar sesión.');

        return Response::redirect('/login');
    }

    public function showLogin(Request $request): Response
    {
        if ($this->session->user() !== null) {
            return Response::redirect($this->destination($this->session->user()['role']));
        }

        return $this->views->render(
            'src/Modules/Identity/Presentation/Views/login.php',
            ['title' => 'Iniciar sesión', 'error' => null, 'email' => '']
        );
    }

    public function login(Request $request): Response
    {
        $email = trim((string) $request->input('email', ''));

        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return $this->views->render(
                'src/Modules/Identity/Presentation/Views/login.php',
                ['title' => 'Iniciar sesión', 'error' => 'La sesión del formulario expiró.', 'email' => $email],
                419
            );
        }

        $user = $this->authenticateUser->execute($email, (string) $request->input('password', ''));

        if ($user === null) {
            return $this->views->render(
                'src/Modules/Identity/Presentation/Views/login.php',
                ['title' => 'Iniciar sesión', 'error' => 'Correo o contraseña incorrectos.', 'email' => $email],
                422
            );
        }

        $this->session->regenerate();
        $this->session->put('user', [
            'id' => $user->id(),
            'name' => $user->name(),
            'email' => $user->email(),
            'role' => $user->role(),
        ]);

        return Response::redirect($this->destination($user->role()));
    }

    public function logout(Request $request): Response
    {
        if (!$this->session->isValidCsrf($request->input('_token'))) {
            return Response::text('Token CSRF inválido.', 419);
        }

        $this->session->destroy();

        return Response::redirect('/');
    }

    private function destination(string $role): string
    {
        return match ($role) {
            'ADMIN' => '/admin',
            'VENDEDOR' => '/vendedor',
            'ALMACENERO' => '/almacen',
            'CONTABLE' => '/contabilidad',
            default => '/mi-cuenta',
        };
    }
}
