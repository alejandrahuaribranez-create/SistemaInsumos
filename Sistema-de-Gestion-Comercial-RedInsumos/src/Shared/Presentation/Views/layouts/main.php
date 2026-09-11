<?php

declare(strict_types=1);

$pageTitle = isset($title) ? (string) $title . ' | RedInsumos' : 'RedInsumos';
$flashType = isset($flash['type']) && in_array($flash['type'], ['success', 'danger', 'warning', 'info'], true)
    ? $flash['type']
    : 'info';
require_once dirname(__DIR__) . '/components/icon.php';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#075aed">
    <title><?= htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="<?= htmlspecialchars($url->to('/assets/css/app.css'), ENT_QUOTES, 'UTF-8') ?>" rel="stylesheet">
</head>
<body class="app-shell">
<a class="skip-link" href="#main-content">Saltar al contenido principal</a>
<?php require dirname(__DIR__) . '/components/navbar.php'; ?>
<main class="app-main" id="main-content" tabindex="-1">
    <?php if ($flash !== null): ?>
        <div class="app-alert alert alert-<?= $flashType ?> alert-dismissible fade show" role="status">
            <span class="app-alert__icon"><?= redinsumos_icon($flashType === 'success' ? 'check' : 'info') ?></span>
            <span><?= htmlspecialchars($flash['message'], ENT_QUOTES, 'UTF-8') ?></span>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
        </div>
    <?php endif; ?>
    <?= $content ?>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
