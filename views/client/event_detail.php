<?php
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/EventController.php';
$event = EventController::getEvent((int) ($_GET['id'] ?? 0));
if (!$event) { redirect('/GoldenHoursEvents/views/errors/404.php'); }
$isFragment = isFragmentRequest();
$pageTitle = $event['titulo'];
if (!$isFragment) { require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php'; echo '<main id="app-content" class="page-transition">'; }
?>
<section class="container"><div class="section"><div class="detail-layout"><div class="detail-media"><div class="detail-image">
<?php if (!empty($event['imagen'])): ?><img src="/GoldenHoursEvents/<?php echo htmlspecialchars($event['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?>"><?php else: ?><div class="image-placeholder category-otro" aria-hidden="true">📅</div><?php endif; ?>
</div></div><div class="detail-info"><span class="badge badge-info"><?php echo htmlspecialchars($event['categoria'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></span><h1 class="page-title"><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1><p><?php echo nl2br(htmlspecialchars($event['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
<div class="price-panel"><span>Precio por entrada</span><strong><?php echo (float) $event['precio'] == 0.0 ? 'Gratuito' : 'S/ ' . number_format((float) $event['precio'], 2); ?></strong></div>
<ul class="meta-list"><li>Organizador: <?php echo htmlspecialchars($event['organizador'], ENT_QUOTES, 'UTF-8'); ?></li><li>Inicio: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($event['fecha_inicio'])), ENT_QUOTES, 'UTF-8'); ?></li><li>Final: <?php echo htmlspecialchars($event['fecha_fin'] ? date('d/m/Y H:i', strtotime($event['fecha_fin'])) : 'No especificado', ENT_QUOTES, 'UTF-8'); ?></li><li>Lugar: <?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?></li><li>Dirección: <?php echo htmlspecialchars($event['direccion'] ?? 'No especificada', ENT_QUOTES, 'UTF-8'); ?></li><li>Aforo total: <?php echo (int) $event['cupo_total']; ?></li><li>Cupos disponibles: <?php echo (int) $event['cupos_disponibles']; ?></li></ul>
<p class="alert alert-info">Reservas disponibles próximamente.</p><a class="btn btn-outline" href="/GoldenHoursEvents/views/client/events.php">Volver a eventos</a>
</div></div></div></section>
<?php if (!$isFragment) { echo '</main>'; require_once __DIR__ . '/../../includes/footer.php'; } ?>
