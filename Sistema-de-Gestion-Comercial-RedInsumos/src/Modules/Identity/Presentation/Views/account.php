<?php
declare(strict_types=1);
$pageEyebrow = 'Espacio personal';
$pageHeading = 'Mi cuenta';
$pageDescription = 'Consulta tu perfil y tus pedidos.';
require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php';
?>
<div class="account-bento">
    <section class="bento-card profile-card"><div class="profile-card__heading"><span><?= redinsumos_icon('user') ?></span><div><p class="eyebrow">Cliente</p><h2><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h2></div></div><dl class="profile-data"><div><dt>Nombre</dt><dd><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></dd></div><div><dt>Correo</dt><dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd></div></dl><div class="d-flex gap-2"><a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Ver catálogo</a><a class="btn btn-outline-primary" href="<?= htmlspecialchars($url->to('/carrito'), ENT_QUOTES, 'UTF-8') ?>">Mi carrito</a></div></section>
    <article class="bento-card pending-card"><span class="pending-card__icon"><?= redinsumos_icon('box') ?></span><p class="eyebrow">Compras</p><h2>Historial de pedidos</h2><?php if ($orders === []): ?><p>Aún no tienes pedidos confirmados.</p><?php else: ?><div class="table-responsive"><table class="table"><thead><tr><th>Pedido</th><th>Fecha</th><th>Total</th><th>Estado</th></tr></thead><tbody><?php foreach ($orders as $order): ?><tr><td><?= htmlspecialchars($order['codigo_pedido'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($order['fecha_venta'], ENT_QUOTES, 'UTF-8') ?></td><td>Bs <?= number_format((float) $order['total'], 2) ?></td><td><?= htmlspecialchars($order['estado_pago'] . ' / ' . $order['estado_logistico'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?></article>
</div>
