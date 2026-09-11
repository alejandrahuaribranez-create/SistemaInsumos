<?php declare(strict_types=1); ?>
<article class="bento-card dashboard-card">
    <span class="dashboard-card__icon"><?= redinsumos_icon($dashboardIcon ?? 'box') ?></span>
    <div>
        <h2><?= htmlspecialchars((string) $dashboardTitle, ENT_QUOTES, 'UTF-8') ?></h2>
        <p><?= htmlspecialchars((string) $dashboardDescription, ENT_QUOTES, 'UTF-8') ?></p>
        <span class="status-label"><?= redinsumos_icon('info') ?> Próxima iteración</span>
    </div>
</article>
