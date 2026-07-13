<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/EventController.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';

$eventId = (int) ($_GET['id'] ?? 0);
$event = EventController::getEvent($eventId);
if (!$event) { redirect(base_url('views/errors/404.php')); }

$user = currentUser();
$available = max(0, (int) $event['cupos_disponibles']);
$priceCents = moneyToCents($event['precio']);
$maxAmountCents = moneyToCents(appConfig('payments.yape_max_amount'));
$maxTickets = (int) appConfig('reservations.max_tickets');
$maxByAmount = $priceCents > 0 ? intdiv($maxAmountCents, $priceCents) : $maxTickets;
$maxQuantity = max(0, min($available, $maxTickets, $maxByAmount));
$canReserve = $user && $user['rol'] === 'cliente' && $maxQuantity > 0 && appConfig('payments.yape_enabled', true);
$operationToken = $canReserve ? ReservationController::operationToken($eventId) : null;
$returnUrl = base_url('views/client/event_detail.php?id=' . $eventId);
$isFragment = isFragmentRequest();
$pageTitle = $event['titulo'];
if (!$isFragment) { require __DIR__ . '/../../includes/header.php'; require __DIR__ . '/../../includes/navbar.php'; echo '<main id="app-content" class="page-transition">'; }
?>
<section class="container"><div class="section"><div class="detail-layout">
<div class="detail-media"><div class="detail-image">
<?php if (!empty($event['imagen'])): ?><img src="<?php echo htmlspecialchars(base_url($event['imagen']), ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?>">
<?php else: ?><div class="image-placeholder category-otro" aria-hidden="true">Evento</div><?php endif; ?>
</div></div>
<div class="detail-info"><span class="badge badge-info"><?php echo htmlspecialchars($event['categoria'] ?? 'Evento', ENT_QUOTES, 'UTF-8'); ?></span>
<h1 class="page-title"><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></h1>
<p><?php echo nl2br(htmlspecialchars($event['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p>
<div class="price-panel"><span>Precio por entrada</span><strong><?php echo $priceCents === 0 ? 'Gratuito' : 'S/ ' . formatMoney($event['precio']); ?></strong></div>
<ul class="meta-list">
<li>Organizador: <?php echo htmlspecialchars($event['organizador'], ENT_QUOTES, 'UTF-8'); ?></li>
<li>Inicio: <?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($event['fecha_inicio'])), ENT_QUOTES, 'UTF-8'); ?></li>
<li>Final: <?php echo htmlspecialchars($event['fecha_fin'] ? date('d/m/Y H:i', strtotime($event['fecha_fin'])) : 'No especificado', ENT_QUOTES, 'UTF-8'); ?></li>
<li>Lugar: <?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?></li>
<li>Dirección: <?php echo htmlspecialchars($event['direccion'] ?? 'No especificada', ENT_QUOTES, 'UTF-8'); ?></li>
<li>Aforo total: <?php echo (int) $event['cupo_total']; ?></li><li>Cupos disponibles: <?php echo $available; ?></li>
</ul>
<?php if (isset($_GET['error'])): ?><p class="alert alert-error"><?php echo htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if ($available <= 0): ?>
<p class="alert alert-error"><strong>Agotado.</strong> No quedan entradas disponibles.</p>
<?php elseif (!$user): ?>
<p class="alert alert-info">Inicia sesión como cliente para reservar entradas.</p>
<a class="btn btn-primary" href="<?php echo htmlspecialchars(base_url('views/auth/login.php?return=' . rawurlencode($returnUrl)), ENT_QUOTES, 'UTF-8'); ?>">Iniciar sesión y reservar</a>
<?php elseif ($user['rol'] !== 'cliente'): ?>
<p class="alert alert-info">Solo las cuentas de cliente pueden realizar reservas.</p>
<?php elseif (!appConfig('payments.yape_enabled', true)): ?>
<p class="alert alert-info">Las reservas están temporalmente deshabilitadas.</p>
<?php elseif ($maxQuantity <= 0): ?>
<p class="alert alert-error">El importe de una entrada supera el límite configurado para Yape.</p>
<?php else: ?>
<form class="form-container reservation-form" method="POST" action="<?php echo htmlspecialchars(base_url('views/client/reserve.php'), ENT_QUOTES, 'UTF-8'); ?>" data-unit-price="<?php echo (int) $priceCents; ?>">
<?php echo csrfField(); ?><input type="hidden" name="event_id" value="<?php echo $eventId; ?>"><input type="hidden" name="operation_token" value="<?php echo htmlspecialchars($operationToken, ENT_QUOTES, 'UTF-8'); ?>">
<div class="form-group"><label for="quantity">Cantidad de entradas</label><input id="quantity" name="quantity" type="number" min="1" max="<?php echo $maxQuantity; ?>" value="1" required></div>
<p>Total estimado: <strong data-reservation-total>S/ <?php echo formatMoney($event['precio']); ?></strong></p>
<p class="form-help">Máximo <?php echo $maxQuantity; ?> en esta operación. El servidor verificará nuevamente precio, aforo y límite de pago.</p>
<button class="btn btn-primary" type="submit">Reservar y continuar al pago</button>
</form>
<?php endif; ?>
<a class="btn btn-outline" href="<?php echo htmlspecialchars(base_url('views/client/events.php'), ENT_QUOTES, 'UTF-8'); ?>">Volver a eventos</a>
</div></div></div></section>
<?php if (!$isFragment) { echo '</main>'; require __DIR__ . '/../../includes/footer.php'; } ?>
