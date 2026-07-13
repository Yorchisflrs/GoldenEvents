<?php
// Panel del cliente enfocado en cotizaciones.
require_once __DIR__ . '/../../includes/auth.php';
requireLogin();
requireRole('cliente');

$user = currentUser();
$pageTitle = 'Panel del Cliente';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="page-bg bg-dashboard dashboard-page">
    <div class="container dashboard">
    <section class="section">
        <div class="glass-panel dashboard-header">
            <h1 class="page-title">Bienvenido, <?php echo htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
            <p class="page-subtitle">Gestiona tus cotizaciones y empieza a planificar tu siguiente celebracion.</p>
        </div>

        <div class="grid-3">
            <a class="dashboard-card" href="/GoldenHoursEvents/views/client/events.php">
                <h2>Eventos</h2>
                <p>Explora el catálogo público.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/client/services.php">
                <span class="icon">🎉</span>
                <h2>Ver servicios</h2>
                <p>Explora opciones por categoria.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/client/build_event.php">
                <span class="icon">🧩</span>
                <h2>Armar mi evento</h2>
                <p>Selecciona servicios y calcula un estimado.</p>
            </a>
            <a class="dashboard-card" href="/GoldenHoursEvents/views/client/my_quotes.php">
                <span class="icon">📋</span>
                <h2>Mis cotizaciones</h2>
                <p>Consulta solicitudes registradas.</p>
            </a>
        </div>
    </section>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
