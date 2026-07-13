<?php
// Pagina principal de Golden Hour Events.
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/session.php';

$pageTitle = 'Golden Hour Events';
$isFragment = isFragmentRequest();

if (!$isFragment) {
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    echo '<main id="app-content" class="page-transition">';
}
?>

<section class="hero hero-image-bg page-bg bg-home">
    <div class="hero-panel">
        <div class="hero-content overlay-content fade-up">
            <p class="hero-subtitle">Arma tu evento ideal en un solo lugar</p>
            <h1>Golden Hour Events</h1>
            <p>Encuentra locales, decoracion, DJ, tortas, catering, fotografia y mas servicios para crear una experiencia unica.</p>

            <div class="actions">
                <a class="btn btn-primary js-page-link" href="/GoldenHoursEvents/views/client/services.php">🎉 Ver servicios</a>
                <a class="btn btn-secondary js-page-link" href="/GoldenHoursEvents/views/client/build_event.php">🧩 Armar mi evento</a>
            </div>
        </div>

        <div class="hero-visual" aria-label="Servicios destacados">
            <article class="floating-card"><span>🏛️</span><strong>Locales</strong><p>Salones y espacios para cada ocasion.</p></article>
            <article class="floating-card"><span>🎧</span><strong>DJ</strong><p>Musica, luces y sonido profesional.</p></article>
            <article class="floating-card"><span>🎀</span><strong>Decoracion</strong><p>Ambientacion tematica y elegante.</p></article>
            <article class="floating-card"><span>🍽️</span><strong>Catering</strong><p>Comida y atencion para invitados.</p></article>
        </div>
    </div>
</section>

<section class="container section">
    <h2 class="page-title reveal">¿Como funciona?</h2>
    <p class="page-subtitle reveal">Planifica tu celebracion paso a paso, con costos claros y servicios organizados por categoria.</p>

    <div class="grid-3">
        <article class="dashboard-card reveal">
            <span class="icon">🔎</span>
            <h3>Explora servicios</h3>
            <p>Revisa locales, decoracion, musica, catering y proveedores disponibles para tu evento.</p>
        </article>
        <article class="dashboard-card reveal">
            <span class="icon">✨</span>
            <h3>Elige lo que necesitas</h3>
            <p>Agrega servicios a tu seleccion y arma una propuesta segun tu estilo y presupuesto.</p>
        </article>
        <article class="dashboard-card reveal">
            <span class="icon">📋</span>
            <h3>Solicita tu cotizacion</h3>
            <p>Envia tus datos y recibe seguimiento para confirmar disponibilidad y detalles.</p>
        </article>
    </div>
</section>

<section class="container section section-soft">
    <h2 class="page-title reveal">Servicios destacados</h2>
    <p class="page-subtitle reveal">Todo lo que necesitas para fiestas de 15 anios, promociones, matrimonios y celebraciones especiales.</p>

    <div class="grid-3">
        <article class="glow-card card reveal"><h3>🏛️ Locales</h3><p>Salones y espacios para diferentes capacidades.</p></article>
        <article class="glow-card card reveal"><h3>🎀 Decoracion</h3><p>Ambientacion tematica y detalles visuales elegantes.</p></article>
        <article class="glow-card card reveal"><h3>🎧 DJ y musica</h3><p>Sonido, luces y musica para animar la celebracion.</p></article>
        <article class="glow-card card reveal"><h3>🍽️ Catering</h3><p>Bocaditos, bebidas y atencion para invitados.</p></article>
        <article class="glow-card card reveal"><h3>📸 Fotografia y video</h3><p>Cobertura audiovisual para conservar cada momento.</p></article>
        <article class="glow-card card reveal"><h3>🎂 Tortas</h3><p>Tortas personalizadas para eventos memorables.</p></article>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/includes/footer.php';
}
?>
