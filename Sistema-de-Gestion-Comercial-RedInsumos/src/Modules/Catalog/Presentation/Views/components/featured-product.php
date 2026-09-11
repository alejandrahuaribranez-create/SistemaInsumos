<?php
declare(strict_types=1);
$featuredImage = $featuredProduct === null ? null : $productImages->product($featuredProduct, 'large');
?>
<article class="bento-card featured-product">
    <?php if ($featuredProduct === null): ?>
        <div class="featured-product__empty">
            <p class="eyebrow">Catálogo RedInsumos</p>
            <h2>Productos para cada conexión</h2>
            <p>El catálogo se actualizará cuando existan productos disponibles.</p>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="featured-product__heading">
            <p class="eyebrow">Producto disponible</p>
            <h2><?= htmlspecialchars($featuredProduct->name, ENT_QUOTES, 'UTF-8') ?></h2>
        </div>
        <div class="featured-product__visual">
            <?php
            $image = $featuredImage;
            $imageAlt = 'Imagen de ' . $featuredProduct->name;
            $pictureClass = 'featured-product__media';
            $picturePriority = true;
            require __DIR__ . '/product-picture.php';
            ?>
        </div>
        <div class="featured-product__summary">
            <strong>Bs <?= number_format($featuredProduct->price, 2) ?></strong>
            <span class="availability <?= $featuredProduct->stock > 0 ? 'is-available' : 'is-empty' ?>">
                <span aria-hidden="true"></span><?= $featuredProduct->stock > 0 ? 'Disponible' : 'Agotado' ?>
            </span>
            <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/productos/' . $featuredProduct->id), ENT_QUOTES, 'UTF-8') ?>">
                Ver detalle <?= redinsumos_icon('arrow-right') ?>
            </a>
        </div>
    <?php endif; ?>
</article>
