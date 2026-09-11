<?php
declare(strict_types=1);
$contextualPath = '/login';
$contextualLabel = 'Iniciar sesión';

if ($user !== null) {
    $contextualPath = match ($user['role']) {
        'ADMIN' => '/admin',
        'VENDEDOR' => '/vendedor',
        'ALMACENERO' => '/almacen',
        'CONTABLE' => '/contabilidad',
        default => '/mi-cuenta',
    };
    $contextualLabel = 'Ir a mi espacio';
}
?>
<div class="home-bento">
    <section class="hero-bento" aria-labelledby="hero-title">
        <div class="hero-bento__content">
            <p class="hero-bento__eyebrow">RedInsumos Bolivia</p>
            <h1 id="hero-title">Todo lo que tu red necesita</h1>
            <p>Encuentra insumos para instalar, ampliar y proteger tu conectividad con información real de nuestro catálogo.</p>
            <div class="hero-bento__actions">
                <a class="btn btn-light btn-lg" href="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>">
                    Ver catálogo <?= redinsumos_icon('arrow-right') ?>
                </a>
                <a class="btn btn-outline-light btn-lg" href="<?= htmlspecialchars($url->to($contextualPath), ENT_QUOTES, 'UTF-8') ?>">
                    <?= redinsumos_icon('user') ?> <?= htmlspecialchars($contextualLabel, ENT_QUOTES, 'UTF-8') ?>
                </a>
            </div>
        </div>
        <?php if ($heroDesktop !== null || $heroCompact !== null): ?>
            <picture class="hero-bento__visual">
                <?php if ($heroCompact !== null): ?>
                    <source media="(max-width: 767px)" srcset="<?= htmlspecialchars($url->to($heroCompact['path']), ENT_QUOTES, 'UTF-8') ?>">
                <?php endif; ?>
                <?php if ($heroDesktop !== null): ?>
                    <img src="<?= htmlspecialchars($url->to($heroDesktop['path']), ENT_QUOTES, 'UTF-8') ?>"
                         width="<?= (int) $heroDesktop['width'] ?>" height="<?= (int) $heroDesktop['height'] ?>"
                         alt="" fetchpriority="high" decoding="async">
                <?php endif; ?>
            </picture>
        <?php endif; ?>
    </section>

    <?php require __DIR__ . '/components/featured-product.php'; ?>

    <section class="family-highlights" aria-label="Familias del catálogo">
        <?php foreach ($familyHighlights as $index => $item): ?>
            <article class="family-feature <?= $index % 2 === 0 ? 'is-cyan' : 'is-teal' ?>">
                <div class="family-feature__copy">
                    <p class="eyebrow">Familia del catálogo</p>
                    <h2><?= htmlspecialchars($item['category']->name, ENT_QUOTES, 'UTF-8') ?></h2>
                    <p><?= htmlspecialchars($item['category']->description ?? 'Consulta los productos disponibles.', ENT_QUOTES, 'UTF-8') ?></p>
                </div>
                <img src="<?= htmlspecialchars($item['image']['src'], ENT_QUOTES, 'UTF-8') ?>"
                     width="<?= (int) $item['image']['width'] ?>" height="<?= (int) $item['image']['height'] ?>"
                     alt="" loading="lazy" decoding="async">
            </article>
        <?php endforeach; ?>
    </section>

    <section class="bento-card category-search" aria-labelledby="families-title">
        <div class="category-search__families">
            <div class="section-heading">
                <div><p class="eyebrow">Explora</p><h2 id="families-title">Familias del catálogo</h2></div>
                <span>Información de categorías activas</span>
            </div>
            <div class="category-list">
                <?php foreach ($categories as $categoryItem): ?>
                    <?php require __DIR__ . '/components/category-card.php'; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="catalog-search-card">
            <span class="catalog-search-card__icon"><?= redinsumos_icon('search') ?></span>
            <div>
                <p class="eyebrow">Búsqueda directa</p>
                <h2>¿Qué producto necesitas?</h2>
                <p>Busca por nombre o código en el catálogo disponible.</p>
            </div>
            <form method="get" action="<?= htmlspecialchars($url->to('/catalogo'), ENT_QUOTES, 'UTF-8') ?>" role="search" class="catalog-search-card__form">
                <label class="visually-hidden" for="home-search">Nombre o código del producto</label>
                <input class="form-control" id="home-search" type="search" name="q" placeholder="Nombre o código">
                <button class="btn btn-primary" type="submit">Buscar</button>
            </form>
        </div>
    </section>
</div>
