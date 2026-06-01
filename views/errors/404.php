<?php
// Pagina de recurso no encontrado.
$pageTitle = 'Pagina no encontrada';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container auth-page">
    <section class="auth-card">
        <div class="error-icon">🔎</div>
        <h1 class="page-title">Pagina no encontrada</h1>
        <p class="page-subtitle">La pagina solicitada no existe o ya no esta disponible.</p>
        <a class="btn btn-primary" href="/GoldenHoursEvents/index.php">Volver al inicio</a>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
