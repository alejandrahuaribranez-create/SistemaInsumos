<?php

declare(strict_types=1);

if (!function_exists('redinsumos_icon')) {
    function redinsumos_icon(string $name, string $class = 'app-icon', ?string $title = null): string
    {
        $paths = [
            'network' => '<circle cx="12" cy="12" r="2.25"/><circle cx="4" cy="12" r="1.75"/><circle cx="12" cy="4" r="1.75"/><circle cx="20" cy="8" r="1.75"/><circle cx="20" cy="17" r="1.75"/><circle cx="12" cy="21" r="1.75"/><path d="M5.7 12h4.05M12 5.75v4M13.95 10.95l4.35-2.1M13.95 13.05l4.35 2.65M12 14.25v5"/>',
            'search' => '<circle cx="11" cy="11" r="6.5"/><path d="m16 16 4.25 4.25"/>',
            'user' => '<circle cx="12" cy="8" r="3.25"/><path d="M5.75 20c.35-4 2.45-6 6.25-6s5.9 2 6.25 6"/>',
            'arrow-right' => '<path d="M5 12h14M14 7l5 5-5 5"/>',
            'box' => '<path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7M12 11v10"/>',
            'check' => '<path d="m5 12 4 4L19 6"/>',
            'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
            'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7h.01"/>',
            'alert' => '<path d="M10.3 4.1 2.5 18a2 2 0 0 0 1.75 3h15.5a2 2 0 0 0 1.75-3L13.7 4.1a2 2 0 0 0-3.4 0Z"/><path d="M12 9v4M12 17h.01"/>',
            'menu' => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        ];
        $content = $paths[$name] ?? $paths['info'];
        $accessible = $title !== null && $title !== '';
        $attributes = $accessible
            ? 'role="img" aria-label="' . htmlspecialchars($title, ENT_QUOTES, 'UTF-8') . '"'
            : 'aria-hidden="true" focusable="false"';

        return sprintf(
            '<svg class="%s" %s viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">%s</svg>',
            htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
            $attributes,
            $content
        );
    }
}
