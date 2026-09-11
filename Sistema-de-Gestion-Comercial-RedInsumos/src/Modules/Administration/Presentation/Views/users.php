<?php
declare(strict_types=1);
$editingUser = $editingUser ?? null;
$value = static fn (string $key, string $default = ''): string => htmlspecialchars((string) ($editingUser[$key] ?? $default), ENT_QUOTES, 'UTF-8');
?>
<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div><p class="text-primary fw-semibold mb-1">Administración</p><h1 class="h2 mb-0">Usuarios y roles</h1></div>
        <a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url->to('/admin'), ENT_QUOTES, 'UTF-8') ?>">Volver al panel</a>
    </div>
    <div class="row g-4">
        <div class="col-12 col-xl-4">
            <section class="bento-card p-4">
                <h2 class="h4"><?= $editingUser ? 'Modificar usuario' : 'Nuevo usuario' ?></h2>
                <form method="post" action="<?= htmlspecialchars($url->to($editingUser ? '/admin/usuarios/' . $editingUser['id_usuario'] : '/admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="col-12"><label class="form-label" for="nombre">Nombre</label><input class="form-control" id="nombre" name="nombre" maxlength="150" required value="<?= $value('nombre') ?>"></div>
                    <div class="col-12"><label class="form-label" for="email">Correo</label><input class="form-control" type="email" id="email" name="email" maxlength="150" required value="<?= $value('email') ?>"></div>
                    <div class="col-12"><label class="form-label" for="telefono">Teléfono de cliente</label><input class="form-control" id="telefono" name="telefono" maxlength="20" value="<?= $value('telefono') ?>"></div>
                    <div class="col-md-7"><label class="form-label" for="rol">Rol</label><select class="form-select" id="rol" name="rol" required><?php foreach ($roles as $role): ?><option value="<?= htmlspecialchars($role['codigo'], ENT_QUOTES, 'UTF-8') ?>" <?= ($editingUser['rol'] ?? 'CLIENTE') === $role['codigo'] ? 'selected' : '' ?>><?= htmlspecialchars($role['nombre'], ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></div>
                    <div class="col-md-5"><label class="form-label" for="estado">Estado</label><select class="form-select" id="estado" name="estado"><option value="activo" <?= ($editingUser['estado'] ?? 'activo') === 'activo' ? 'selected' : '' ?>>Activo</option><option value="inactivo" <?= ($editingUser['estado'] ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option></select></div>
                    <div class="col-12"><label class="form-label" for="password">Contraseña <?= $editingUser ? '(vacía conserva la actual)' : '' ?></label><input class="form-control" type="password" id="password" name="password" minlength="8" <?= $editingUser ? '' : 'required' ?>></div>
                    <div class="col-12"><label class="form-label" for="password_confirmation">Confirmar contraseña</label><input class="form-control" type="password" id="password_confirmation" name="password_confirmation" minlength="8" <?= $editingUser ? '' : 'required' ?>></div>
                    <div class="col-12 d-flex gap-2"><button class="btn btn-primary" type="submit"><?= $editingUser ? 'Guardar cambios' : 'Crear usuario' ?></button><?php if ($editingUser): ?><a class="btn btn-outline-secondary" href="<?= htmlspecialchars($url->to('/admin/usuarios'), ENT_QUOTES, 'UTF-8') ?>">Cancelar</a><?php endif; ?></div>
                </form>
            </section>
        </div>
        <div class="col-12 col-xl-8">
            <section class="bento-card p-4 mb-4"><h2 class="h4">Permisos por responsabilidad</h2><ul class="mb-0"><?php foreach ($permissions as $code => $description): ?><li><strong><?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>:</strong> <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></li><?php endforeach; ?></ul></section>
            <section class="bento-card p-4"><div class="table-responsive"><table class="table align-middle"><thead><tr><th>Usuario</th><th>Rol</th><th>Estado</th><th class="text-end">Acciones</th></tr></thead><tbody>
                <?php foreach ($users as $managedUser): ?><tr><td><strong><?= htmlspecialchars($managedUser['nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br><small><?= htmlspecialchars($managedUser['email'], ENT_QUOTES, 'UTF-8') ?></small></td><td><?= htmlspecialchars($managedUser['rol_nombre'], ENT_QUOTES, 'UTF-8') ?></td><td><span class="badge text-bg-<?= $managedUser['estado'] === 'activo' ? 'success' : 'secondary' ?>"><?= htmlspecialchars($managedUser['estado'], ENT_QUOTES, 'UTF-8') ?></span></td><td><div class="d-flex justify-content-end gap-2"><a class="btn btn-sm btn-outline-primary" href="<?= htmlspecialchars($url->to('/admin/usuarios/' . $managedUser['id_usuario'] . '/editar'), ENT_QUOTES, 'UTF-8') ?>">Editar</a><form method="post" action="<?= htmlspecialchars($url->to('/admin/usuarios/' . $managedUser['id_usuario'] . '/eliminar'), ENT_QUOTES, 'UTF-8') ?>" onsubmit="return confirm('¿Eliminar este usuario? Si tiene operaciones, debes desactivarlo.');"><input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"><button class="btn btn-sm btn-outline-danger" type="submit">Eliminar</button></form></div></td></tr><?php endforeach; ?>
            </tbody></table></div></section>
        </div>
    </div>
</div>
