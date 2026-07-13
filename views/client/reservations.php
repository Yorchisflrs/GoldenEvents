<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';
requireLogin(); requireRole('cliente'); $user = currentUser();
$reservations = ReservationController::myReservations($user['id']);
$pageTitle = 'Mis reservas'; require __DIR__ . '/../../includes/header.php'; require __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Mis reservas</h1><p class="page-subtitle">Consulta el estado, pago e historial de tus entradas.</p>
<?php if (!$reservations): ?><p class="alert alert-info">Todavía no tienes reservas.</p><?php else: ?><div class="table-wrapper"><table><thead><tr><th>Código</th><th>Evento</th><th>Fecha</th><th>Cantidad</th><th>Total</th><th>Estado</th><th>Pago</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($reservations as $reservation): $status = ReservationController::effectiveStatus($reservation); ?>
<tr><td><?php echo htmlspecialchars($reservation['codigo_reserva'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($reservation['evento'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars(date('d/m/Y H:i', strtotime($reservation['fecha_inicio'])), ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo (int) $reservation['cantidad']; ?></td><td>S/ <?php echo formatMoney($reservation['monto_total']); ?></td><td><span class="badge badge-<?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?></span></td><td><?php echo htmlspecialchars($reservation['pago_estado'] ?? 'sin pago', ENT_QUOTES, 'UTF-8'); ?></td><td><a class="btn btn-small btn-outline" href="<?php echo htmlspecialchars(base_url('views/client/reservation_detail.php?id=' . (int) $reservation['id']), ENT_QUOTES, 'UTF-8'); ?>">Ver</a><?php if ($status === 'pendiente_pago'): ?> <a class="btn btn-small btn-primary" href="<?php echo htmlspecialchars(base_url('views/client/reservation_payment.php?id=' . (int) $reservation['id']), ENT_QUOTES, 'UTF-8'); ?>">Pagar</a><?php endif; ?></td></tr>
<?php endforeach; ?></tbody></table></div><?php endif; ?></section></main><?php require __DIR__ . '/../../includes/footer.php'; ?>
