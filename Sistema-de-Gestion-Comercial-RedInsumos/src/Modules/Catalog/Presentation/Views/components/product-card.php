<?php
declare(strict_types=1);
$image = $productImages->product($product, 'thumb');
$imageAlt = 'Imagen de ' . $product->name;
$pictureClass = 'product-card__media';
$picturePriority = false;
?>
<article class="product-card">
    <?php require __DIR__ . '/product-picture.php'; ?>
    <div class="product-card__body">
        <div class="product-card__meta">
            <span><?= htmlspecialchars($product->code, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="stock-badge <?= $product->stock > 0 ? 'is-available' : 'is-empty' ?>">
                <?= $product->stock > 0 ? 'Disponible' : 'Agotado' ?>
            </span>
        </div>
        <h2><a href="<?= htmlspecialchars($url->to('/productos/' . $product->id), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></a></h2>
        <p class="product-card__category"><?= htmlspecialchars($product->category, ENT_QUOTES, 'UTF-8') ?></p>
        <div class="product-card__footer">
            <strong>Bs <?= number_format($product->price, 2) ?></strong>
            <a class="icon-link" href="<?= htmlspecialchars($url->to('/productos/' . $product->id), ENT_QUOTES, 'UTF-8') ?>" aria-label="Ver detalle de <?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?>">
                <?= redinsumos_icon('arrow-right') ?>
            </a>
        </div>
    </div>
</article>
