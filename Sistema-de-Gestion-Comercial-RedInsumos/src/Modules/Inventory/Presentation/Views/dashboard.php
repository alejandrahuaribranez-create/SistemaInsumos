<?php
declare(strict_types=1);
$dashboardItems = [
    ['Inventario', 'Los ajustes y movimientos se incorporarán en una próxima iteración.', 'box'],
    ['Compras', 'La recepción de mercadería todavía no está disponible.', 'info'],
    ['Pedidos por preparar', 'El flujo operativo de preparación permanece pendiente.', 'check'],
];
$pageEyebrow = 'Rol ' . $user['role'];
$pageHeading = 'Panel de Almacén';
$pageDescription = 'Bienvenido, ' . $user['name'] . '.';
?>
<?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/page-header.php'; ?>
<section class="dashboard-grid" aria-label="Módulos de almacén">
    <?php foreach ($dashboardItems as [$dashboardTitle, $dashboardDescription, $dashboardIcon]): ?>
        <?php require dirname(__DIR__, 4) . '/Shared/Presentation/Views/components/dashboard-card.php'; ?>
    <?php endforeach; ?>
</section>
