<?php

declare(strict_types=1);

$isEditing = $editingBrand !== null;
?>

<?php require dirname(__DIR__, 5) . '/Shared/Presentation/Views/components/page-header.php'; ?>

<div class="row g-4">

    <div class="col-lg-4">
        <section class="bento-card p-4">

            <h2 class="h4 fw-bold mb-4">
                <?= $isEditing ? 'Editar marca' : 'Nueva marca' ?>
            </h2>

            <?php if ($error !== null): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars(
                        $error,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>
                </div>
            <?php endif; ?>

            <form method="post"
                  action="<?= htmlspecialchars(
                      $url->to(
                          $isEditing
                              ? '/admin/marcas/' . $editingBrand->id
                              : '/admin/marcas'
                      ),
                      ENT_QUOTES,
                      'UTF-8'
                  ) ?>">

                <input
                    type="hidden"
                    name="_token"
                    value="<?= htmlspecialchars(
                        $csrfToken,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>">

                <div class="mb-4">
                    <label class="form-label" for="nombre">
                        Nombre de la marca
                    </label>

                    <input
                        class="form-control"
                        id="nombre"
                        name="nombre"
                        maxlength="100"
                        required
                        value="<?= htmlspecialchars(
                            (string) ($form['nombre'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                </div>

                <div class="d-flex gap-2">
                    <button
                        class="btn btn-primary"
                        type="submit">
                        <?= $isEditing
                            ? 'Guardar cambios'
                            : 'Crear marca' ?>
                    </button>

                    <?php if ($isEditing): ?>
                        <a
                            class="btn btn-outline-secondary"
                            href="<?= htmlspecialchars(
                                $url->to('/admin/marcas'),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                            Cancelar
                        </a>
                    <?php endif; ?>
                </div>
            </form>

        </section>
    </div>

    <div class="col-lg-8">
        <section class="bento-card p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <p class="eyebrow">Catálogo</p>
                    <h2 class="h4 fw-bold mb-0">
                        Marcas registradas
                    </h2>
                </div>

                <span class="badge text-bg-light">
                    <?= count($brands) ?> registradas
                </span>
            </div>

            <?php if ($brands === []): ?>

                <div class="alert alert-info">
                    Todavía no existen marcas registradas.
                </div>

            <?php else: ?>

                <div class="table-responsive">
                    <table class="table align-middle">

                        <thead>
                        <tr>
                            <th>Marca</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($brands as $brand): ?>

                            <tr>
                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $brand->name,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>
                                </td>

                                <td>
                                    <?php if ($brand->status === 'activo'): ?>
                                        <span class="badge text-bg-success">
                                            Activa
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            Inactiva
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">

                                    <div class="d-flex justify-content-end gap-2">

                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="<?= htmlspecialchars(
                                                $url->to(
                                                    '/admin/marcas/'
                                                    . $brand->id
                                                    . '/editar'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">
                                            Editar
                                        </a>

                                        <form
                                            method="post"
                                            action="<?= htmlspecialchars(
                                                $url->to(
                                                    '/admin/marcas/'
                                                    . $brand->id
                                                    . '/estado'
                                                ),
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>">

                                            <input
                                                type="hidden"
                                                name="_token"
                                                value="<?= htmlspecialchars(
                                                    $csrfToken,
                                                    ENT_QUOTES,
                                                    'UTF-8'
                                                ) ?>">

                                            <button
                                                type="submit"
                                                class="btn btn-sm <?= $brand->status === 'activo'
                                                    ? 'btn-outline-danger'
                                                    : 'btn-outline-success' ?>">

                                                <?= $brand->status === 'activo'
                                                    ? 'Desactivar'
                                                    : 'Activar' ?>

                                            </button>
                                        </form>

                                    </div>

                                </td>
                            </tr>

                        <?php endforeach; ?>

                        </tbody>
                    </table>
                </div>

            <?php endif; ?>

        </section>
    </div>
</div>