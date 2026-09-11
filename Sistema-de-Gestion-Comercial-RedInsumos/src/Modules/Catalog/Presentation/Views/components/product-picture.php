<?php
declare(strict_types=1);
$pictureClass = isset($pictureClass) ? (string) $pictureClass : '';
$picturePriority = !empty($picturePriority);
?>
<div class="product-media <?= htmlspecialchars($pictureClass, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($image !== null): ?>
        <img
            src="<?= htmlspecialchars($image['src'], ENT_QUOTES, 'UTF-8') ?>"
            width="<?= (int) $image['width'] ?>"
            height="<?= (int) $image['height'] ?>"
            alt="<?= htmlspecialchars((string) $imageAlt, ENT_QUOTES, 'UTF-8') ?>"
            <?= $picturePriority ? 'fetchpriority="high"' : 'loading="lazy" decoding="async"' ?>
        >
    <?php else: ?>
        <div class="product-media__placeholder" role="img" aria-label="Sin imagen disponible para <?= htmlspecialchars((string) $imageAlt, ENT_QUOTES, 'UTF-8') ?>">
            <?= redinsumos_icon('box', 'product-media__placeholder-icon') ?>
            <span>Imagen no disponible</span>
        </div>
    <?php endif; ?>
</div>
