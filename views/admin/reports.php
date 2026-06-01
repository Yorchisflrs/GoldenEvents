<?php
// Reportes generales para administrador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$stats = AdminController::dashboardStats();
$pageTitle = 'Reportes generales';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Reportes generales</h1>
        <p class="page-subtitle">Resumen ejecutivo del marketplace y cotizador.</p>

        <section class="stats-grid">
            <article class="stat-card"><span class="icon">👤</span><h2>Total usuarios</h2><p><?php echo (int) $stats['usuarios']; ?></p></article>
            <article class="stat-card"><span class="icon">🎉</span><h2>Total servicios</h2><p><?php echo (int) $stats['servicios']; ?></p></article>
            <article class="stat-card"><span class="icon">📋</span><h2>Total cotizaciones</h2><p><?php echo (int) $stats['cotizaciones']; ?></p></article>
            <article class="stat-card"><span class="icon">⏳</span><h2>Pendientes</h2><p><?php echo (int) $stats['cotizaciones_pendientes']; ?></p></article>
        </section>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
