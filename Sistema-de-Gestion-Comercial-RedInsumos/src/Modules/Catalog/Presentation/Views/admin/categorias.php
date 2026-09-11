<?php

declare(strict_types=1);

$pageEyebrow = 'Administración';
$pageHeading = isset($editingCategory) && $editingCategory !== null
    ? 'Editar categoría'
    : 'Gestionar categorías';

$pageDescription = 'Administra las categorías utilizadas en el catálogo.';
?>

<?php require dirname(__DIR__, 5) . '/Shared/Presentation/Views/components/page-header.php'; ?>

<section class="container-fluid px-0">

    <div class="row g-4">

        <!-- =====================================================
             FORMULARIO
        ====================================================== -->

        <div class="col-12 col-lg-4">

            <article class="bento-card p-4">

                <h2 class="h4 mb-4">
                    <?= $editingCategory !== null
                        ? 'Modificar categoría'
                        : 'Nueva categoría' ?>
                </h2>

                <form
                    method="post"
                    action="<?= htmlspecialchars(
                        $url->to(
                            $editingCategory !== null
                                ? '/admin/categorias/' . $editingCategory->id
                                : '/admin/categorias'
                        ),
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                >

                    <input
                        type="hidden"
                        name="_csrf"
                        value="<?= htmlspecialchars(
                            $csrfToken,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                    >

                    <div class="mb-3">

                        <label
                            for="nombre"
                            class="form-label"
                        >
                            Nombre
                        </label>

                        <input
                            type="text"
                            class="form-control"
                            id="nombre"
                            name="nombre"
                            maxlength="100"
                            required
                            value="<?= htmlspecialchars(
                                $editingCategory?->name ?? '',
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >

                    </div>

                    <div class="mb-3">

                        <label
                            for="descripcion"
                            class="form-label"
                        >
                            Descripción
                        </label>

                        <textarea
                            class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="4"
                        ><?= htmlspecialchars(
                            $editingCategory?->description ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>

                    </div>

                    <div class="mb-4">

                        <label
                            for="id_padre"
                            class="form-label"
                        >
                            Categoría padre
                        </label>

                        <select
                            class="form-select"
                            id="id_padre"
                            name="id_padre"
                        >

                            <option value="">
                                Sin categoría padre
                            </option>

                            <?php foreach ($categories as $category): ?>

                                <?php
                                if (
                                    $editingCategory !== null
                                    && $category->id === $editingCategory->id
                                ) {
                                    continue;
                                }
                                ?>

                                <option
                                    value="<?= $category->id ?>"
                                    <?= (
                                        $editingCategory !== null
                                        && $editingCategory->parentId === $category->id
                                    ) ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars(
                                        $category->name,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </option>

                            <?php endforeach; ?>

                        </select>

                    </div>

                    <div class="d-flex gap-2">

                        <button
                            type="submit"
                            class="btn btn-primary"
                        >
                            <?= $editingCategory !== null
                                ? 'Guardar cambios'
                                : 'Crear categoría' ?>
                        </button>

                        <?php if ($editingCategory !== null): ?>

                            <a
                                href="<?= htmlspecialchars(
                                    $url->to('/admin/categorias'),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                class="btn btn-outline-secondary"
                            >
                                Cancelar
                            </a>

                        <?php endif; ?>

                    </div>

                </form>

            </article>

        </div>

        <!-- =====================================================
             LISTADO
        ====================================================== -->

        <div class="col-12 col-lg-8">

            <article class="bento-card p-4">

                <div class="d-flex justify-content-between align-items-center mb-4">

                    <div>

                        <h2 class="h4 mb-1">
                            Categorías registradas
                        </h2>

                        <p class="text-muted mb-0">
                            <?= count($categories) ?> categoría(s)
                        </p>

                    </div>

                </div>

                <?php if ($categories === []): ?>

                    <div class="text-center py-5">

                        <h3 class="h5">
                            No existen categorías
                        </h3>

                        <p class="text-muted">
                            Registra la primera categoría utilizando
                            el formulario.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table align-middle">

                            <thead>

                                <tr>

                                    <th>
                                        #
                                    </th>

                                    <th>
                                        Nombre
                                    </th>

                                    <th>
                                        Descripción
                                    </th>

                                    <th>
                                        Estado
                                    </th>

                                    <th class="text-end">
                                        Acciones
                                    </th>

                                </tr>

                            </thead>

                            <tbody>

                                <?php foreach ($categories as $category): ?>

                                    <tr>

                                        <td>
                                            <?= $category->id ?>
                                        </td>

                                        <td>
                                            <strong>
                                                <?= htmlspecialchars(
                                                    $category->name,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>
                                            </strong>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(
                                                $category->description ?? 'Sin descripción',
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </td>

                                        <td>

                                            <?php if ($category->status === 'activo'): ?>

                                                <span class="badge text-bg-success">
                                                    Activo
                                                </span>

                                            <?php else: ?>

                                                <span class="badge text-bg-secondary">
                                                    Inactivo
                                                </span>

                                            <?php endif; ?>

                                        </td>

                                        <td>

                                            <div class="d-flex justify-content-end gap-2">

                                                <a
                                                    href="<?= htmlspecialchars(
                                                        $url->to(
                                                            '/admin/categorias/' .
                                                            $category->id .
                                                            '/editar'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                    class="btn btn-sm btn-outline-primary"
                                                >
                                                    Editar
                                                </a>

                                                <form
                                                    method="post"
                                                    action="<?= htmlspecialchars(
                                                        $url->to(
                                                            '/admin/categorias/' .
                                                            $category->id .
                                                            '/estado'
                                                        ),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                >

                                                    <input
                                                        type="hidden"
                                                        name="_csrf"
                                                        value="<?= htmlspecialchars(
                                                            $csrfToken,
                                                            ENT_QUOTES,
                                                            'UTF-8'
                                                        ) ?>"
                                                    >

                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-outline-secondary"
                                                    >
                                                        <?= $category->status === 'activo'
                                                            ? 'Desactivar'
                                                            : 'Activar' ?>
                                                    </button>

                                                </form>

                                                <form
                                                    method="post"
                                                    action="<?= htmlspecialchars(
                                                        $url->to('/admin/categorias/' . $category->id . '/eliminar'),
                                                        ENT_QUOTES,
                                                        'UTF-8'
                                                    ) ?>"
                                                    onsubmit="return confirm('¿Eliminar esta categoría? Si tiene productos o subcategorías deberás desactivarla.');"
                                                >
                                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                                </form>

                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            </tbody>

                        </table>

                    </div>

                <?php endif; ?>

            </article>

        </div>

    </div>

</section>
