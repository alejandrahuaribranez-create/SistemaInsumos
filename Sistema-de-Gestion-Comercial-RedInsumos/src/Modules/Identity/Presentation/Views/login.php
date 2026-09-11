<?php declare(strict_types=1); ?>
<div class="auth-layout">
    <section class="auth-intro" aria-labelledby="auth-intro-title">
        <span class="auth-intro__icon"><?= redinsumos_icon('network') ?></span>
        <p class="eyebrow">Acceso RedInsumos</p>
        <h2 id="auth-intro-title">Tu operación, en un solo lugar</h2>
        <p>Ingresa con tu cuenta para acceder al espacio asignado a tu rol.</p>
    </section>
    <section class="bento-card auth-card" aria-labelledby="login-title">
                <div class="auth-card__heading">
                    <span class="auth-card__icon"><?= redinsumos_icon('lock') ?></span>
                    <div><p class="eyebrow">Bienvenido</p><h1 id="login-title">Iniciar sesión</h1></div>
                </div>
                <?php if ($error !== null): ?>
                    <div class="app-alert alert alert-danger" role="alert"><?= redinsumos_icon('alert') ?><span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span></div>
                <?php endif; ?>
                <form method="post" action="<?= htmlspecialchars($url->to('/login'), ENT_QUOTES, 'UTF-8') ?>" class="auth-form">
                    <input type="hidden" name="_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="form-field">
                        <label class="form-label" for="email">Correo electrónico</label>
                        <input class="form-control" id="email" name="email" type="email"
                               value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required autofocus>
                    </div>
                    <div class="form-field">
                        <label class="form-label" for="password">Contraseña</label>
                        <input class="form-control" id="password" name="password" type="password" autocomplete="current-password" required>
                    </div>
                    <button class="btn btn-primary w-100" type="submit">Ingresar</button>
                </form>
                <p class="auth-card__footer">¿Aún no tienes cuenta? <a href="<?= htmlspecialchars($url->to('/register'), ENT_QUOTES, 'UTF-8') ?>">Regístrate</a></p>
    </section>
</div>
