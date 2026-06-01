<?php
// Panel del proveedor protegido por rol.
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('proveedor');

$user = currentUser();
$pageTitle = 'Panel del Proveedor';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="page-bg bg-dashboard dashboard-page">
    <div class="container dashboard">
    <section class="section">
        <div class="glass-panel dashboard-header">
            <h1 class="page-title">Bienvenido, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle">Administra los servicios que ofreces para eventos personalizados.</p>
        </div>

        <div class="grid-2">
            <a class="dashboard-card" href="/GoldenHoursEvents/views/provider/create_service.php">
                <span class="icon">➕</span>
                <h2>Crear servicio</h2>
                <p>Publica locales, decoracion, musica, catering u otros servicios.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/provider/my_services.php">
                <span class="icon">📦</span>
                <h2>Mis servicios</h2>
                <p>Revisa tus servicios registrados y su disponibilidad.</p>
            </a>
        </div>
    </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
