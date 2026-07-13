<?php
// Detalle publico de un servicio.
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';

$serviceId = (int) ($_GET['id'] ?? 0);
$service = ServiceController::getService($serviceId);
$isFragment = isFragmentRequest();

if (!$service || $service['estado'] !== 'activo' || (int) $service['disponibilidad'] !== 1) {
    redirect('/GoldenHoursEvents/views/errors/404.php');
}
$serviceImage = serviceDisplayImagePath($service);

$pageTitle = $service['nombre'];
if (!$isFragment) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/navbar.php';
    echo '<main id="app-content" class="page-transition">';
}
?>

<section class="container">
    <div class="section">
        <div class="detail-layout">
            <div class="detail-media reveal">
                <div class="detail-image">
                    <?php if ($serviceImage !== null): ?>
                        <img src="/GoldenHoursEvents/<?php echo htmlspecialchars($serviceImage, ENT_QUOTES, 'UTF-8'); ?>" loading="eager" decoding="async" width="1200" height="800" alt="Fotografía representativa del servicio <?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?>">
                    <?php else: ?>
                        <div class="image-placeholder category-<?php echo htmlspecialchars($service['categoria_slug'] ?? 'otro', ENT_QUOTES, 'UTF-8'); ?>" role="img" aria-label="Imagen no disponible para este servicio">
                            <span>Imagen no disponible</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="detail-info reveal">
                <span class="badge badge-info"><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoria', ENT_QUOTES, 'UTF-8'); ?></span>
                <h1 class="page-title"><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1>
                <p><?php echo nl2br(htmlspecialchars($service['descripcion'] ?? '', ENT_QUOTES, 'UTF-8')); ?></p>

                <div class="price-panel">
                    <span>Costo estimado</span>
                    <strong>S/ <?php echo number_format((float) $service['precio'], 2); ?></strong>
                </div>

                <ul class="meta-list">
                    <li><strong>Capacidad:</strong> <?php echo htmlspecialchars($service['capacidad'] ?? 'No aplica', ENT_QUOTES, 'UTF-8'); ?><?php echo !empty($service['capacidad']) ? ' personas' : ''; ?></li>
                    <li><strong>Ubicación:</strong> <?php echo htmlspecialchars($service['ubicacion'] ?? 'Puno', ENT_QUOTES, 'UTF-8'); ?></li>
                    <li><strong>Proveedor:</strong> <?php echo htmlspecialchars($service['proveedor'], ENT_QUOTES, 'UTF-8'); ?></li>
                </ul>

                <div class="cta-row">
                    <form class="inline-form" method="POST" action="/GoldenHoursEvents/views/client/build_event.php">
                        <?php echo csrfField(); ?>
                        <button class="btn btn-primary" type="submit" name="add_service" value="<?php echo (int) $service['id']; ?>">Agregar a mi cotización</button>
                    </form>
                    <a class="btn btn-outline js-page-link" href="/GoldenHoursEvents/views/client/services.php">Volver a servicios</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/../../includes/footer.php';
}
?>
