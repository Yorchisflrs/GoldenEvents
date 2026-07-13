<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');
$stats = AdminController::dashboardStats();
$pageTitle = 'Panel de Administración';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="page-bg bg-dashboard dashboard-page"><div class="container dashboard"><section class="section">
<div class="glass-panel dashboard-header"><h1 class="page-title">Panel de Administración</h1><p class="page-subtitle">Moderación de usuarios, marketplace, eventos y cotizaciones.</p></div>
<section class="stats-grid">
<?php $cards = ['Usuarios pendientes' => $stats['usuarios_pendientes'], 'Organizadores pendientes' => $stats['organizadores_pendientes'], 'Proveedores pendientes' => $stats['proveedores_pendientes'], 'Servicios pendientes' => $stats['servicios_pendientes'], 'Eventos pendientes' => $stats['eventos_pendientes'], 'Total usuarios' => $stats['usuarios'], 'Total servicios' => $stats['servicios'], 'Eventos publicados' => $stats['eventos_publicados'], 'Total cotizaciones' => $stats['cotizaciones'], 'Cotizaciones pendientes' => $stats['cotizaciones_pendientes']]; foreach ($cards as $label => $value): ?><article class="stat-card"><h2><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></h2><p><?php echo (int) $value; ?></p></article><?php endforeach; ?>
</section>
<section class="grid-3 section">
<a class="dashboard-card" href="/GoldenHoursEvents/views/admin/users.php"><h2>Usuarios</h2><p>Aprobación y estados de acceso.</p></a>
<a class="dashboard-card" href="/GoldenHoursEvents/views/admin/services.php"><h2>Servicios</h2><p>Moderación del marketplace.</p></a>
<a class="dashboard-card" href="/GoldenHoursEvents/views/admin/events.php"><h2>Eventos</h2><p>Aprobación y publicación.</p></a>
<a class="dashboard-card" href="/GoldenHoursEvents/views/admin/quotes.php"><h2>Cotizaciones</h2><p>Detalle y seguimiento.</p></a>
<a class="dashboard-card" href="/GoldenHoursEvents/views/admin/audit.php"><h2>Auditoría</h2><p>Historial administrativo.</p></a>
</section></section></div></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
