<?php

declare(strict_types=1);

$pageEyebrow = 'Rol ' . $user['role'];
$pageHeading = 'Panel de Administración';
$pageDescription = 'Bienvenido, ' . $user['name'] . '.';
?>

<?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php'; ?>

<section class="dashboard-grid" aria-label="Módulos de administración">

    <article class="bento-card dashboard-card">

        <span class="dashboard-card__icon">
            <?= redinsumos_icon('box') ?>
        </span>

        <div>
            <h2>Productos</h2>

            <p>
                Administra los productos disponibles en el catálogo.
            </p>

            <a
                class="btn btn-primary"
                href="<?= htmlspecialchars(
                    $url->to('/catalogo'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
                Ver catálogo
            </a>
        </div>

    </article>

    <article class="bento-card dashboard-card">

        <span class="dashboard-card__icon">
            <?= redinsumos_icon('box') ?>
        </span>

        <div>
            <h2>Categorías</h2>

            <p>
                Crea, modifica y activa o desactiva categorías.
            </p>

            <a
                class="btn btn-primary"
                href="<?= htmlspecialchars(
                    $url->to('/admin/categorias'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
                Gestionar categorías
            </a>
        </div>

    </article>

    <article class="bento-card dashboard-card">

        <span class="dashboard-card__icon">
            <?= redinsumos_icon('info') ?>
        </span>

        <div>
            <h2>Marcas</h2>

            <p>
                Administra las marcas utilizadas por los productos.
            </p>

            <a
                class="btn btn-primary"
                href="<?= htmlspecialchars(
                    $url->to('/admin/marcas'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
                Gestionar marcas
            </a>
        </div>

    </article>

    <article class="bento-card dashboard-card">

        <span class="dashboard-card__icon">
            <?= redinsumos_icon('info') ?>
        </span>

        <div>
            <h2>Proveedores</h2>

            <p>
                Registra y administra los proveedores del catálogo.
            </p>

            <a
                class="btn btn-primary"
                href="<?= htmlspecialchars(
                    $url->to('/admin/proveedores'),
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>">
                Gestionar proveedores
            </a>
        </div>

    </article>

    <article class="bento-card dashboard-card">

        <span class="dashboard-card__icon">
            <?= redinsumos_icon('user') ?>
        </span>

        <div>
            <h2>Usuarios</h2>

            <p>
                Crea, modifica, desactiva y asigna responsabilidades a los usuarios.
            </p>

            <a class="btn btn-primary" href="<?= htmlspecialchars($url->to('/admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>">Gestionar usuarios</a>
        </div>

    </article>

</section>
