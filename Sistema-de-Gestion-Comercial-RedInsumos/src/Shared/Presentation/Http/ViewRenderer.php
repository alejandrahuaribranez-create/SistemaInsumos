<?php

declare(strict_types=1);

namespace RedInsumos\Shared\Presentation\Http;

use RedInsumos\Shared\Infrastructure\Session\Session;
use RuntimeException;

final class ViewRenderer
{
    public function __construct(
        private string $projectRoot,
        private Session $session
    ) {
    }

    /** @param array<string, mixed> $data */
    public function render(string $view, array $data = [], int $status = 200): Response
    {
        $viewPath = $this->projectRoot . '/' . ltrim($view, '/');

        if (!is_file($viewPath)) {
            throw new RuntimeException(sprintf('No existe la vista %s.', $view));
        }

        require_once $this->projectRoot . '/src/Shared/Presentation/Views/components/icon.php';
        extract($data, EXTR_SKIP);
        $user = $this->session->user();
        $csrfToken = $this->session->csrfToken();
        $flash = $this->session->consumeFlash();
        $url = UrlGenerator::fromEnvironment();

        ob_start();
        require $viewPath;
        $content = (string) ob_get_clean();

        ob_start();
        require $this->projectRoot . '/src/Shared/Presentation/Views/layouts/main.php';

        return Response::html((string) ob_get_clean(), $status);
    }
}
