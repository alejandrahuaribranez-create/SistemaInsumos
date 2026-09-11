<?php declare(strict_types=1); ?>
<section class="empty-state" aria-labelledby="empty-state-title">
    <span class="empty-state__icon"><?= redinsumos_icon($emptyIcon ?? 'box') ?></span>
    <h2 id="empty-state-title"><?= htmlspecialchars((string) $emptyTitle, ENT_QUOTES, 'UTF-8') ?></h2>
    <p><?= htmlspecialchars((string) $emptyMessage, ENT_QUOTES, 'UTF-8') ?></p>
    <?php if (isset($emptyActionHref, $emptyActionLabel)): ?>
        <a class="btn btn-primary" href="<?= htmlspecialchars((string) $emptyActionHref, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars((string) $emptyActionLabel, ENT_QUOTES, 'UTF-8') ?>
        </a>
    <?php endif; ?>
</section>
