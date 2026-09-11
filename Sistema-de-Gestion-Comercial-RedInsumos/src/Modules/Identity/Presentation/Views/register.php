<?php declare(strict_types=1); ?>
<div class="auth-layout auth-layout--register">
    <section class="auth-intro" aria-labelledby="register-intro-title">
        <span class="auth-intro__icon"><?= redinsumos_icon('network') ?></span>
        <p class="eyebrow">Cuenta de cliente</p>
        <h2 id="register-intro-title">Empieza a explorar RedInsumos</h2>
        <p>Crea tu cuenta con datos reales y accede a tu espacio personal.</p>
    </section>
    <section class="bento-card auth-card auth-card--wide" aria-labelledby="register-title">
                <div class="auth-card__heading">
                    <span class="auth-card__icon"><?= redinsumos_icon('user') ?></span>
                    <div><p class="eyebrow">Registro</p><h1 id="register-title">Crear cuenta de cliente</h1></div>
                </div>
                <?php if (isset($errors['form'])): ?>
                    <div class="app-alert alert alert-danger" role="alert"><?= redinsumos_icon('alert') ?><span><?= htmlspecialchars($errors['form'], ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url->to('/register'), ENT_QUOTES, 'UTF-8') ?>" class="row g-3 auth-form" novalidate>
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <?php foreach ([
                        'nombre' => 'Nombre',
                        'apellido' => 'Apellido',
                        'email' => 'Correo electrónico',
                        'telefono' => 'Teléfono',
                    ] as $field => $label): ?>
                        <div class="col-md-<?= in_array($field, ['nombre', 'apellido'], true) ? '6' : '12' ?>">
                            <label class="form-label" for="<?= $field ?>"><?= $label ?></label>
                            <input class="form-control <?= isset($errors[$field]) ? 'is-invalid' : '' ?>"
                                   id="<?= $field ?>" name="<?= $field ?>"
                                   type="<?= $field === 'email' ? 'email' : 'text' ?>"
                                   autocomplete="<?= match ($field) {
                                       'nombre' => 'given-name',
                                       'apellido' => 'family-name',
                                       'email' => 'email',
                                       'telefono' => 'tel',
                                   } ?>"
                                   <?= isset($errors[$field]) ? 'aria-invalid="true" aria-describedby="' . $field . '-error"' : '' ?>
                                   value="<?= htmlspecialchars((string) ($old[$field] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                            <?php if (isset($errors[$field])): ?>
                                <div class="invalid-feedback" id="<?= $field ?>-error"><?= htmlspecialchars($errors[$field], ENT_QUOTES, 'UTF-8') ?></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>"
                               id="password" name="password" type="password" autocomplete="new-password" minlength="8"
                               <?= isset($errors['password']) ? 'aria-invalid="true" aria-describedby="password-error"' : '' ?> required>
                        <?php if (isset($errors['password'])): ?>
                            <div class="invalid-feedback" id="password-error"><?= htmlspecialchars($errors['password'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Confirmar contraseña</label>
                        <input class="form-control <?= isset($errors['password_confirmation']) ? 'is-invalid' : '' ?>"
                               id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8"
                               <?= isset($errors['password_confirmation']) ? 'aria-invalid="true" aria-describedby="password-confirmation-error"' : '' ?> required>
                        <?php if (isset($errors['password_confirmation'])): ?>
                            <div class="invalid-feedback" id="password-confirmation-error"><?= htmlspecialchars($errors['password_confirmation'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 d-grid">
                        <button class="btn btn-primary" type="submit">Crear mi cuenta</button>
                    </div>
                </form>
                <p class="auth-card__footer">¿Ya tienes una cuenta? <a href="<?= htmlspecialchars($url->to('/login'), ENT_QUOTES, 'UTF-8') ?>">Inicia sesión</a></p>
    </section>
</div>
