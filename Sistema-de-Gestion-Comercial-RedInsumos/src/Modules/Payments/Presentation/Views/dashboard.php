<?php
declare(strict_types=1);
$dashboardItems = [
    ['Pagos', 'La revisión operativa de pagos se incorporará en una próxima iteración.', 'box'],
    ['Conciliaciones', 'La conciliación manual todavía no está disponible en la interfaz.', 'check'],
];
$pageEyebrow = 'Rol ' . $user['role'];
$pageHeading = 'Panel de Contabilidad';
$pageDescription = 'Bienvenido, ' . $user['name'] . '.';
?>
<?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php'; ?>
<section class="dashboard-grid dashboard-grid--two" aria-label="Módulos de contabilidad">
    <?php foreach ($dashboardItems as [$dashboardTitle, $dashboardDescription, $dashboardIcon]): ?>
        <?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/dashboard-card.php'; ?>
    <?php endforeach; ?>
</section>
