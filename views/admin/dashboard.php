<?php
// Panel de administracion protegido por rol.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$stats = AdminController::dashboardStats();
$pageTitle = 'Panel de Administracion';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="page-bg bg-dashboard dashboard-page">
    <div class="container dashboard">
    <section class="section">
        <div class="glass-panel dashboard-header">
            <h1 class="page-title">Panel de Administracion</h1>
            <p class="page-subtitle">Supervisa servicios, cotizaciones y usuarios desde un solo lugar.</p>
        </div>

        <section class="stats-grid">
            <article class="stat-card"><span class="icon">👤</span><h2>Usuarios</h2><p><?php echo (int) $stats['usuarios']; ?></p></article>
            <article class="stat-card"><span class="icon">🎉</span><h2>Servicios</h2><p><?php echo (int) $stats['servicios']; ?></p></article>
            <article class="stat-card"><span class="icon">📋</span><h2>Cotizaciones</h2><p><?php echo (int) $stats['cotizaciones']; ?></p></article>
            <article class="stat-card"><span class="icon">⏳</span><h2>Pendientes</h2><p><?php echo (int) $stats['cotizaciones_pendientes']; ?></p></article>
        </section>

        <section class="grid-3 section">
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/users.php"><span class="icon">👥</span><h2>Usuarios</h2><p>Gestiona cuentas y roles.</p></a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/services.php"><span class="icon">🎉</span><h2>Servicios</h2><p>Revisa el catalogo publicado.</p></a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/quotes.php"><span class="icon">📋</span><h2>Cotizaciones</h2><p>Da seguimiento a solicitudes.</p></a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/events.php"><span class="icon">🗓️</span><h2>Eventos internos</h2><p>Modulo heredado de eventos.</p></a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/reservations.php"><span class="icon">🧾</span><h2>Reservas futuras</h2><p>Modulo reservado para futuras fases.</p></a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/admin/reports.php"><span class="icon">📊</span><h2>Reportes</h2><p>Resumen general del sistema.</p></a>
        </section>
    </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
