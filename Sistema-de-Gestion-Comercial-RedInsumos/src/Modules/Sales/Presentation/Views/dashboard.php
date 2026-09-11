<?php
declare(strict_types=1);
$dashboardItems = [
    ['Ventas', 'El registro de ventas asistidas se incorporará en una próxima iteración.', 'box'],
    ['Pedidos', 'La gestión comercial de pedidos todavía no está disponible.', 'info'],
    ['Clientes', 'La consulta operativa de clientes permanece pendiente.', 'user'],
];
$pageEyebrow = 'Rol ' . $user['role'];
$pageHeading = 'Panel de Ventas';
$pageDescription = 'Bienvenido, ' . $user['name'] . '.';
?>
<?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php'; ?>
<section class="dashboard-grid" aria-label="Módulos de ventas">
    <?php foreach ($dashboardItems as [$dashboardTitle, $dashboardDescription, $dashboardIcon]): ?>
        <?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/dashboard-card.php'; ?>
    <?php endforeach; ?>
</section>
