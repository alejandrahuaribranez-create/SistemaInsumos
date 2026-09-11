<?php
declare(strict_types=1);
$image = $productImages->product($product, 'large');
$imageAlt = 'Imagen de ' . $product->name;
$pictureClass = 'product-detail__media';
$picturePriority = true;
?>
<nav class="breadcrumb-nav" aria-label="Migas de pan">
    <a href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Catálogo</a>
    <span aria-hidden="true">/</span>
    <span aria-current="page"><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></span>
</nav>

<article class="product-detail">
    <section class="bento-card product-detail__visual" aria-label="Imagen del producto">
        <?php require __DIR__ . '/components/product-picture.php'; ?>
    </section>
    <section class="bento-card product-detail__content">
        <div class="product-detail__heading">
            <p class="eyebrow"><?= htmlspecialchars($product->category, ENT_QUOTES, 'UTF-8') ?></p>
            <span class="product-code"><?= htmlspecialchars($product->code, ENT_QUOTES, 'UTF-8') ?></span>
            <h1><?= htmlspecialchars($product->name, ENT_QUOTES, 'UTF-8') ?></h1>
            <p class="product-description"><?= nl2br(htmlspecialchars($product->description ?? 'Sin descripción disponible.', ENT_QUOTES, 'UTF-8')) ?></p>
        </div>

        <div class="product-detail__price-panel">
            <div>
                <span class="price-label">Precio de venta</span>
                <strong>Bs <?= number_format($product->price, 2) ?></strong>
            </div>
            <span class="availability <?= $product->stock > 0 ? 'is-available' : 'is-empty' ?>">
                <span aria-hidden="true"></span><?= $product->stock > 0 ? 'Disponible' : 'Agotado' ?>
            </span>
        </div>

        <dl class="product-specs">
            <div><dt>Categoría</dt><dd><?= htmlspecialchars($product->category, ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Marca</dt><dd><?= htmlspecialchars($product->brand ?? 'Sin marca', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Proveedor</dt><dd><?= htmlspecialchars($product->supplier ?? 'No asignado', ENT_QUOTES, 'UTF-8') ?></dd></div>
            <div><dt>Existencias</dt><dd><?= $product->stock ?> unidades</dd></div>
        </dl>

        <a class="btn btn-primary product-detail__back" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">
            Volver al catálogo <?= redinsumos_icon('arrow-right') ?>
        </a>
    </section>
</article>
