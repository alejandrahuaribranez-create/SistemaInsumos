<?php
declare(strict_types=1);
$pageEyebrow = 'Espacio personal';
$pageHeading = 'Mi cuenta';
$pageDescription = 'Consulta la información asociada a tu sesión.';
require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php';
?>
<div class="account-bento">
    <section class="bento-card profile-card" aria-labelledby="profile-title">
        <div class="profile-card__heading"><span><?= redinsumos_icon('user') ?></span><div><p class="eyebrow">Cliente</p><h2 id="profile-title"><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></h2></div></div>
        <dl class="profile-data">
            <div><dt>Nombre</dt><dd><?= htmlspecialchars($user['name'], ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Correo</dt><dd><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></dd></div>
        </dl>
        <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Ver catálogo <?= redinsumos_icon('arrow-right') ?></a>
    </section>
    <article class="bento-card pending-card">
        <span class="pending-card__icon"><?= redinsumos_icon('box') ?></span>
        <p class="eyebrow">Próxima iteración</p>
        <h2>Historial de pedidos</h2>
        <p>Esta función todavía no está disponible en la aplicación.</p>
    </article>
</div>
