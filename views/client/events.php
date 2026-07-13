<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/EventController.php';
$filters = [
    'categoria' => trim((string) ($_GET['categoria'] ?? '')),
    'fecha' => trim((string) ($_GET['fecha'] ?? '')),
    'ubicacion' => trim((string) ($_GET['ubicacion'] ?? '')),
    'precio' => trim((string) ($_GET['precio'] ?? '')),
    'buscar' => trim((string) ($_GET['buscar'] ?? '')),
];
$events = EventController::listAvailable($filters);
$categories = EventController::categories();
$isFragment = isFragmentRequest();
$pageTitle = 'Eventos';
if (!$isFragment) { require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php'; echo '<main id="app-content" class="page-transition">'; }
?>
<section class="page-bg bg-services section-with-bg services-page"><div class="container"><div class="section">
<div class="glass-panel page-header-panel"><h1 class="page-title">Eventos publicados</h1><p class="page-subtitle">Descubre eventos futuros con cupos disponibles.</p>
<form class="form-container" method="GET" action="/GoldenHoursEvents/views/client/events.php"><div class="grid-3">
<div class="form-group"><label for="buscar">Título</label><input id="buscar" name="buscar" maxlength="100" value="<?php echo htmlspecialchars($filters['buscar'], ENT_QUOTES, 'UTF-8'); ?>"></div>
<div class="form-group"><label for="categoria">Categoría</label><select id="categoria" name="categoria"><option value="">Todas</option><?php foreach ($categories as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['categoria'] === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="fecha">Fecha</label><input id="fecha" name="fecha" type="date" value="<?php echo htmlspecialchars($filters['fecha'], ENT_QUOTES, 'UTF-8'); ?>"></div>
<div class="form-group"><label for="ubicacion">Ubicación</label><input id="ubicacion" name="ubicacion" maxlength="100" value="<?php echo htmlspecialchars($filters['ubicacion'], ENT_QUOTES, 'UTF-8'); ?>"></div>
<div class="form-group"><label for="precio">Precio</label><select id="precio" name="precio"><option value="">Todos</option><option value="gratuito" <?php echo $filters['precio'] === 'gratuito' ? 'selected' : ''; ?>>Gratuito</option><option value="pago" <?php echo $filters['precio'] === 'pago' ? 'selected' : ''; ?>>De pago</option></select></div>
</div><button class="btn btn-primary" type="submit">Buscar eventos</button></form></div>
<?php if (!$events): ?><div class="empty-state"><h2>No hay eventos disponibles con estos filtros.</h2></div><?php else: ?><section class="service-grid">
<?php foreach ($events as $event): ?><article class="service-card"><div class="service-image-wrap">
<?php if (!empty($event['imagen'])): ?><img class="service-img" src="/GoldenHoursEvents/<?php echo htmlspecialchars($event['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?>" loading="lazy"><?php else: ?><div class="image-placeholder category-otro" aria-hidden="true">📅</div><?php endif; ?>
</div><div class="service-content"><span class="service-category"><?php echo htmlspecialchars($event['categoria'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></span><h2><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></h2>
<div class="service-meta"><span><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($event['fecha_inicio'])), ENT_QUOTES, 'UTF-8'); ?></span><span><?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?></span><span>Organiza: <?php echo htmlspecialchars($event['organizador'], ENT_QUOTES, 'UTF-8'); ?></span><span>Cupos: <?php echo (int) $event['cupos_disponibles']; ?></span></div>
<div class="service-footer"><strong class="service-price"><?php echo (float) $event['precio'] == 0.0 ? 'Gratuito' : 'S/ ' . number_format((float) $event['precio'], 2); ?></strong><a class="btn btn-primary" href="/GoldenHoursEvents/views/client/event_detail.php?id=<?php echo (int) $event['id']; ?>">Ver detalle</a></div>
</div></article><?php endforeach; ?></section><?php endif; ?>
</div></div></section>
<?php if (!$isFragment) { echo '</main>'; require_once __DIR__ . '/../../includes/footer.php'; } ?>
