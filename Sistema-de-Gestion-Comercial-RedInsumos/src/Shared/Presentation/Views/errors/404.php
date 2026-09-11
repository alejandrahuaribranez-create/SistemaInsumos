<?php declare(strict_types=1); ?>
<section class="bento-card error-page" aria-labelledby="error-title">
    <span class="error-page__icon"><?= redinsumos_icon('search') ?></span>
    <p class="error-page__code">404</p>
    <h1 id="error-title">Página no encontrada</h1>
    <p>La dirección solicitada no existe o ya no está disponible.</p>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a>
</section>
