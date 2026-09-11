<?php

declare(strict_types=1);

$isEditing = $editingSupplier !== null;
?>

<?php require dirname(__DIR__, 5) . '/Shared/Presentation/Views/components/page-header.php'; ?>

<div class="row g-4">

    <div class="col-xl-5">
        <section class="bento-card p-4">

            <h2 class="h4 fw-bold mb-4">
                <?= $isEditing
                    ? 'Editar proveedor'
                    : 'Nuevo proveedor' ?>
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
                              ? '/admin/proveedores/'
                                . $editingSupplier->id
                              : '/admin/proveedores'
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

                <div class="mb-3">
                    <label class="form-label" for="nit">
                        NIT
                    </label>

                    <input
                        class="form-control"
                        id="nit"
                        name="nit"
                        maxlength="20"
                        value="<?= htmlspecialchars(
                            (string) ($form['nit'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="razon_social">
                        Razón social
                    </label>

                    <input
                        class="form-control"
                        id="razon_social"
                        name="razon_social"
                        maxlength="150"
                        required
                        value="<?= htmlspecialchars(
                            (string) ($form['razon_social'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="nombre_comercial">
                        Nombre comercial
                    </label>

                    <input
                        class="form-control"
                        id="nombre_comercial"
                        name="nombre_comercial"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            (string) ($form['nombre_comercial'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label" for="contacto">
                            Contacto
                        </label>

                        <input
                            class="form-control"
                            id="contacto"
                            name="contacto"
                            maxlength="100"
                            value="<?= htmlspecialchars(
                                (string) ($form['contacto'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="telefono">
                            Teléfono
                        </label>

                        <input
                            class="form-control"
                            id="telefono"
                            name="telefono"
                            maxlength="20"
                            value="<?= htmlspecialchars(
                                (string) ($form['telefono'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>

                </div>

                <div class="mb-3 mt-3">
                    <label class="form-label" for="correo">
                        Correo
                    </label>

                    <input
                        class="form-control"
                        id="correo"
                        name="correo"
                        type="email"
                        maxlength="150"
                        value="<?= htmlspecialchars(
                            (string) ($form['correo'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>">
                </div>

                <div class="mb-3">
                    <label class="form-label" for="direccion">
                        Dirección
                    </label>

                    <textarea
                        class="form-control"
                        id="direccion"
                        name="direccion"
                        rows="2"><?= htmlspecialchars(
                            (string) ($form['direccion'] ?? ''),
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?></textarea>
                </div>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label" for="municipio">
                            Municipio
                        </label>

                        <input
                            class="form-control"
                            id="municipio"
                            name="municipio"
                            maxlength="100"
                            value="<?= htmlspecialchars(
                                (string) ($form['municipio'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label" for="departamento">
                            Departamento
                        </label>

                        <input
                            class="form-control"
                            id="departamento"
                            name="departamento"
                            maxlength="100"
                            value="<?= htmlspecialchars(
                                (string) ($form['departamento'] ?? ''),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>">
                    </div>

                </div>

                <div class="mb-4 mt-3">
                    <label class="form-label" for="tiempo_entrega_dias">
                        Tiempo de entrega (días)
                    </label>

                    <input
                        class="form-control"
                        id="tiempo_entrega_dias"
                        name="tiempo_entrega_dias"
                        type="number"
                        min="0"
                        value="<?= htmlspecialchars(
                            (string) (
                                $form['tiempo_entrega_dias'] ?? ''
                            ),
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
                            : 'Crear proveedor' ?>

                    </button>

                    <?php if ($isEditing): ?>

                        <a
                            class="btn btn-outline-secondary"
                            href="<?= htmlspecialchars(
                                $url->to('/admin/proveedores'),
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

    <div class="col-xl-7">
        <section class="bento-card p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">

                <div>
                    <p class="eyebrow">Catálogo</p>
                    <h2 class="h4 fw-bold mb-0">
                        Proveedores registrados
                    </h2>
                </div>

                <span class="badge text-bg-light">
                    <?= count($suppliers) ?> registrados
                </span>

            </div>

            <?php if ($suppliers === []): ?>

                <div class="alert alert-info">
                    Todavía no existen proveedores registrados.
                </div>

            <?php else: ?>

                <div class="table-responsive">

                    <table class="table align-middle">

                        <thead>
                        <tr>
                            <th>Proveedor</th>
                            <th>Contacto</th>
                            <th>Entrega</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                        </thead>

                        <tbody>

                        <?php foreach ($suppliers as $supplier): ?>

                            <tr>

                                <td>
                                    <strong>
                                        <?= htmlspecialchars(
                                            $supplier->businessName,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </strong>

                                    <?php if ($supplier->commercialName): ?>
                                        <small class="d-block text-muted">
                                            <?= htmlspecialchars(
                                                $supplier->commercialName,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>
                                    <?php endif; ?>

                                    <?php if ($supplier->nit): ?>
                                        <small class="d-block text-muted">
                                            NIT:
                                            <?= htmlspecialchars(
                                                $supplier->nit,
                                                ENT_QUOTES,
                                                'UTF-8'
                                            ) ?>
                                        </small>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <?= htmlspecialchars(
                                        $supplier->phone
                                            ?? 'Sin teléfono',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </td>

                                <td>
                                    <?= $supplier->deliveryDays !== null
                                        ? $supplier->deliveryDays . ' días'
                                        : 'No definido' ?>
                                </td>

                                <td>
                                    <?php if ($supplier->status === 'activo'): ?>
                                        <span class="badge text-bg-success">
                                            Activo
                                        </span>
                                    <?php else: ?>
                                        <span class="badge text-bg-secondary">
                                            Inactivo
                                        </span>
                                    <?php endif; ?>
                                </td>

                                <td class="text-end">

                                    <div class="d-flex justify-content-end gap-2">

                                        <a
                                            class="btn btn-sm btn-outline-primary"
                                            href="<?= htmlspecialchars(
                                                $url->to(
                                                    '/admin/proveedores/'
                                                    . $supplier->id
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
                                                    '/admin/proveedores/'
                                                    . $supplier->id
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
                                                class="btn btn-sm <?= $supplier->status === 'activo'
                                                    ? 'btn-outline-danger'
                                                    : 'btn-outline-success' ?>">

                                                <?= $supplier->status === 'activo'
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