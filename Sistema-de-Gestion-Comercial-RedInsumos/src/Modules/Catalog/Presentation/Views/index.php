<?php declare(strict_types=1); ?>
<section class="catalog-page">
    <header class="page-heading catalog-heading">
        <div>
            <p class="eyebrow">Productos activos</p>
            <h1>Catálogo</h1>
            <p>Consulta precios, existencias y detalles actualizados de RedInsumos.</p>
        </div>
        <form class="catalog-search" method="get" action="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>" role="search">
            <label class="visually-hidden" for="catalog-search">Buscar por nombre o código</label>
            <span class="catalog-search__icon"><?= redinsumos_icon('search') ?></span>
            <input class="form-control" id="catalog-search" type="search" name="q" placeholder="Nombre o código"
                   value="<?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8') ?>">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>
    </header>

    <?php if ($term !== ''): ?>
        <div class="search-summary" role="status">
            <?= count($products) ?> <?= count($products) === 1 ? 'resultado' : 'resultados' ?> para
            <strong>“<?= htmlspecialchars($term, ENT_QUOTES, 'UTF-8') ?>”</strong>
            <a href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">Limpiar búsqueda</a>
        </div>
    <?php endif; ?>

    <?php if ($products === []): ?>
        <?php
        $emptyIcon = 'search';
        $emptyTitle = 'No encontramos productos';
        $emptyMessage = $term === ''
            ? 'El catálogo no contiene productos activos en este momento.'
            : 'Prueba con otro nombre o con el código del producto.';
        $emptyActionHref = $term === '' ? null : $url->to('/catalogo');
        $emptyActionLabel = $term === '' ? null : 'Ver todo el catálogo';
        require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/empty-state.php';
        ?>
    <?php else: ?>
        <div class="product-grid">
        <?php foreach ($products as $product): ?>
            <?php require __DIR__ . '/components/product-card.php'; ?>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
