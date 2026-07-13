<?php
// Panel del organizador protegido por rol.
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('organizador');

$user = currentUser();
$pageTitle = 'Panel del Organizador';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="page-bg bg-dashboard dashboard-page">
    <div class="container dashboard">
    <section class="section">
        <div class="glass-panel dashboard-header">
            <h1 class="page-title">Panel del Organizador</h1>
            <p class="page-subtitle">Bienvenido, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?>. Crea y administra tus eventos para aprobación.</p>
        </div>

        <div class="grid-3">
            <a class="dashboard-card" href="/GoldenHoursEvents/views/organizer/create_event.php">
                <span class="icon">🗓️</span>
                <h2>Crear evento</h2>
                <p>Registra un evento para revisión administrativa.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/organizer/my_events.php">
                <span class="icon">📋</span>
                <h2>Mis eventos</h2>
                <p>Consulta y administra tus eventos internos.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/client/services.php">
                <span class="icon">🎉</span>
                <h2>Ver servicios publicos</h2>
                <p>Explora el catalogo del marketplace.</p>
            </a>
        </div>
    </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
