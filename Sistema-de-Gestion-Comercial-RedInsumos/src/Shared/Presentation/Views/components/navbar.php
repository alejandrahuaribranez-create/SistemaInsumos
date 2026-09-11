<?php

declare(strict_types=1);

$dashboardUrl = $user === null ? null : match ($user['role']) {
    'ADMIN' => '/admin',
    'VENDEDOR' => '/vendedor',
    'ALMACENERO' => '/almacen',
    'CONTABLE' => '/contabilidad',
    default => '/mi-cuenta',
};
$currentPath = (string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/');
$isHome = rtrim($currentPath, '/') === rtrim($url->to('/'), '/');
$isCatalog = str_starts_with($currentPath, rtrim($url->to('/catalogo'), '/'))
    || str_starts_with($currentPath, rtrim($url->to('/productos'), '/'));
?>
<header class="app-header-wrap">
    <nav class="navbar navbar-expand-lg app-header" aria-label="Navegación principal">
        <div class="container-fluid app-header__inner">
            <a class="navbar-brand" href="<?= htmlspecialchars($url->to('/'), ENT_QUOTES, 'UTF-8') ?>" aria-label="RedInsumos, ir al inicio">
                <?php require __DIR__ . '/brand-mark.php'; ?>
            </a>
            <button class="navbar-toggler app-header__toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Abrir menú">
                <?= redinsumos_icon('menu') ?>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav app-nav">
                    <li class="nav-item"><a class="nav-link <?= $isHome ? 'active' : '' ?>" <?= $isHome ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars($url->to('/'), ENT_QUOTES, 'UTF-8') ?>">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link <?= $isCatalog ? 'active' : '' ?>" <?= $isCatalog ? 'aria-current="page"' : '' ?> href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Catálogo</a></li>
                </ul>
                <div class="app-header__actions">
                    <a class="header-icon-button" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>" aria-label="Buscar en el catálogo"><?= redinsumos_icon('search') ?></a>
                    <?php if ($user === null): ?>
                        <a class="btn btn-ghost" href="<?= htmlspecialchars($url->to('/login'), ENT_QUOTES, 'UTF-8') ?>">Iniciar sesión</a>
                        <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/register'), ENT_QUOTES, 'UTF-8') ?>">Registrarse</a>
                    <?php else: ?>
                        <?php if ($user['role'] === 'CLIENTE'): ?><a class="btn btn-ghost" href="<?= htmlspecialchars($url->to('/carrito'), ENT_QUOTES, 'UTF-8') ?>">Carrito</a><?php endif; ?>
                        <a class="account-link" href="<?= htmlspecialchars($url->to((string) $dashboardUrl), ENT_QUOTES, 'UTF-8') ?>">
                            <?= redinsumos_icon('user') ?>
                            <span><small>Mi espacio</small><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></span>
                        </a>
                        <form method="post" action="<?= htmlspecialchars($url->to('/logout'), ENT_QUOTES, 'UTF-8') ?>" class="m-0">
                            <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button class="btn btn-ghost" type="submit">Salir</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>
</header>
