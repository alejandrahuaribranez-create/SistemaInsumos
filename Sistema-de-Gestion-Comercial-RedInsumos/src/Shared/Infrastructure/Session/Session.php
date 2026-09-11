<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Infrastructure\Session;

final class Session
{
    public function __construct(private ?string $savePath = null)
    {
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        if ($this->savePath !== null) {
            ini_set('session.save_path', $this->savePath);
        }

        session_set_cookie_params([
            'httponly' => true,
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public function put(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    public function destroy(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $parameters = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $parameters['path'],
                $parameters['domain'],
                $parameters['secure'],
                $parameters['httponly']
            );
        }

        session_destroy();
    }

    /** @return null|array{id: int, name: string, email: string, role: string} */
    public function user(): ?array
    {
        $user = $this->get('user');

        return is_array($user) ? $user : null;
    }

    public function flash(string $type, string $message): void
    {
        $this->put('_flash', ['type' => $type, 'message' => $message]);
    }

    /** @return null|array{type: string, message: string} */
    public function consumeFlash(): ?array
    {
        $flash = $this->get('_flash');
        $this->remove('_flash');

        return is_array($flash) ? $flash : null;
    }

    public function csrfToken(): string
    {
        $token = $this->get('_csrf_token');

        if (!is_string($token) || $token === '') {
            $token = bin2hex(random_bytes(32));
            $this->put('_csrf_token', $token);
        }

        return $token;
    }

    public function isValidCsrf(mixed $token): bool
    {
        return is_string($token) && hash_equals($this->csrfToken(), $token);
    }
}
