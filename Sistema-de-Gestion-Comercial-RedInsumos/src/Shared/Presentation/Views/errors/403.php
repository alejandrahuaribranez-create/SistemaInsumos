<?php declare(strict_types=1); ?>
<section class="bento-card error-page" aria-labelledby="error-title">
    <span class="error-page__icon"><?= redinsumos_icon('lock') ?></span>
    <p class="error-page__code">403</p>
    <h1 id="error-title">Acceso denegado</h1>
    <p>Tu rol no tiene permiso para acceder a esta sección.</p>
    <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/'), ENT_QUOTES, 'UTF-8') ?>">Volver al inicio</a>
</section>
