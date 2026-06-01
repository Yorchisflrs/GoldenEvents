<?php
// Pagina de acceso denegado.
$pageTitle = 'Acceso denegado';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container auth-page">
    <section class="auth-card">
        <div class="error-icon">🚫</div>
        <h1 class="page-title">Acceso denegado</h1>
        <p class="page-subtitle">No tienes permisos para acceder a esta seccion.</p>
        <a class="btn btn-primary" href="/GoldenHoursEvents/index.php">Volver al inicio</a>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
