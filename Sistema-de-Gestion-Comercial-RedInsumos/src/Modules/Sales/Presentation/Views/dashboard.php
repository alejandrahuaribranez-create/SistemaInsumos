<?php declare(strict_types=1); ?>
<div class="container py-4">
    <p class="text-primary fw-semibold mb-1">Rol <?= htmlspecialchars($user['role'], ENT_QUOTES, 'UTF-8') ?></p>
    <h1 class="h2">Panel de ventas</h1><p>Bienvenido, <?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?>.</p>
    <div class="row g-4 mt-2">
        <div class="col-md-6"><article class="bento-card p-4 h-100"><h2>Productos</h2><p>Registra y modifica productos sin alterar existencias.</p><a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/vendedor/productos'), ENT_QUOTES, 'UTF-8') ?>">Gestionar productos</a></article></div>
        <div class="col-md-6"><article class="bento-card p-4 h-100"><h2>Venta asistida</h2><p>Prepara una operación en un carrito independiente de los clientes.</p><a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/vendedor/carrito'), ENT_QUOTES, 'UTF-8') ?>">Abrir carrito</a></article></div>
    </div>
</div>
