<?php
declare(strict_types=1);
$category = $categoryItem['category'];
$categoryImage = $categoryItem['image'];
?>
<article class="category-card">
    <div class="category-card__media">
        <?php if ($categoryImage !== null): ?>
            <img src="<?= htmlspecialchars($categoryImage['src'], ENT_QUOTES, 'UTF-8') ?>"
                 width="<?= (int) $categoryImage['width'] ?>" height="<?= (int) $categoryImage['height'] ?>"
                 alt="" loading="lazy" decoding="async">
        <?php else: ?>
            <?= redinsumos_icon('box', 'category-card__placeholder') ?>
        <?php endif; ?>
    </div>
    <h3><?= htmlspecialchars($category->name, ENT_QUOTES, 'UTF-8') ?></h3>
</article>
