<?php
// Catalogo publico de servicios.
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';

$categoriaId = isset($_GET['categoria']) ? (int) $_GET['categoria'] : null;
$categories = Category::allPublicAvailable();
$services = ServiceController::listServices($categoriaId);
$isFragment = isFragmentRequest();

if (!function_exists('serviceCategoryClass')) {
    function serviceCategoryClass($category)
    {
        $key = strtolower((string) $category);
        if (str_contains($key, 'local')) return 'category-local';
        if (str_contains($key, 'decor')) return 'category-decoracion';
        if (str_contains($key, 'dj') || str_contains($key, 'musica')) return 'category-dj_musica';
        if (str_contains($key, 'anim')) return 'category-animador';
        if (str_contains($key, 'torta')) return 'category-torta';
        if (str_contains($key, 'catering')) return 'category-catering';
        if (str_contains($key, 'foto') || str_contains($key, 'video')) return 'category-fotografia_video';
        if (str_contains($key, 'mesa') || str_contains($key, 'silla')) return 'category-mesas_sillas';
        if (str_contains($key, 'seguridad')) return 'category-seguridad';
        return 'category-otro';
    }
}

$pageTitle = 'Servicios para tu evento';
if (!$isFragment) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/navbar.php';
    echo '<main id="app-content" class="page-transition">';
}
?>

<section class="page-bg bg-services section-with-bg services-page">
    <div class="container">
        <div class="section">
            <div class="glass-panel page-header-panel reveal">
                <h1 class="page-title">Servicios para tu evento</h1>
                <p class="page-subtitle">Explora proveedores de animación, catering, decoración, DJ y música, fotografía y video, seguridad y tortas para armar una cotización a tu medida.</p>

                <div class="filter-bar">
                    <a class="filter-pill js-page-link <?php echo $categoriaId === null ? 'active' : ''; ?>" href="/GoldenHoursEvents/views/client/services.php">Todos</a>
                    <?php foreach ($categories as $category): ?>
                        <a class="filter-pill js-page-link <?php echo $categoriaId === (int) $category['id'] ? 'active' : ''; ?>" href="/GoldenHoursEvents/views/client/services.php?categoria=<?php echo (int) $category['id']; ?>">
                            <?php echo htmlspecialchars($category['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php if (empty($services)): ?>
            <div class="empty-state reveal">
                <h2>No hay servicios disponibles</h2>
                <p>Prueba con otra categoría o vuelve más tarde.</p>
            </div>
        <?php else: ?>
            <section class="service-grid">
                <?php foreach ($services as $service): ?>
                    <?php $serviceImage = serviceDisplayImagePath($service); ?>
                    <article class="service-card reveal">
                        <div class="service-image-wrap">
                            <?php if ($serviceImage !== null): ?>
                                <img class="service-img" src="/GoldenHoursEvents/<?php echo htmlspecialchars($serviceImage, ENT_QUOTES, 'UTF-8'); ?>" loading="lazy" decoding="async" width="1200" height="800" alt="Fotografía representativa del servicio <?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                            <?php else: ?>
                                <div class="image-placeholder <?php echo serviceCategoryClass($service['categoria'] ?? ''); ?>" role="img" aria-label="Imagen no disponible para este servicio">
                                    <span>Imagen no disponible</span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="service-content">
                            <span class="service-category"><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoria', ENT_QUOTES, 'UTF-8'); ?></span>
                            <h3><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                            <p class="service-description"><?php echo htmlspecialchars($service['descripcion'] ?? '', ENT_QUOTES, 'UTF-8'); ?></p>
                            <div class="service-meta">
                                <span><strong>Ubicación:</strong> <?php echo htmlspecialchars($service['ubicacion'] ?? 'Puno', ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php if (!empty($service['capacidad'])): ?>
                                    <span><strong>Capacidad:</strong> <?php echo (int) $service['capacidad']; ?> personas</span>
                                <?php endif; ?>
                                <span><strong>Proveedor:</strong> <?php echo htmlspecialchars($service['proveedor'], ENT_QUOTES, 'UTF-8'); ?></span>
                            </div>
                            <div class="service-footer">
                                <strong class="service-price">S/ <?php echo number_format((float) $service['precio'], 2); ?></strong>
                                <a class="btn btn-primary js-page-link" href="/GoldenHoursEvents/views/client/service_detail.php?id=<?php echo (int) $service['id']; ?>">Ver detalle</a>
                            </div>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>
        <?php endif; ?>
        </div>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/../../includes/footer.php';
}
?>
