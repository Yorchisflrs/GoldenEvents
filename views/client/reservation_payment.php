<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';
require_once __DIR__ . '/../../controllers/PaymentController.php';
requireLogin(); requireRole('cliente');
$user = currentUser(); $reservationId = (int) ($_GET['id'] ?? $_POST['id'] ?? 0); $message = ''; $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $result = PaymentController::registerPayment($user['id'], $reservationId, $_POST, $_FILES);
    if ($result['success']) { redirect(base_url('views/client/reservation_detail.php?id=' . $reservationId . '&success=' . rawurlencode($result['message']))); }
    $error = $result['message'];
}
$reservation = ReservationController::getOwnedReservation($reservationId, $user['id']);
if (!$reservation) { redirect(base_url('views/errors/404.php')); }
$status = ReservationController::effectiveStatus($reservation);
$pageTitle = 'Pago de reserva'; require __DIR__ . '/../../includes/header.php'; require __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Pago de reserva</h1>
<?php if ($error): ?><p class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<div class="detail-layout"><div class="payment-qr-panel">
<img class="yape-qr" src="<?php echo htmlspecialchars(base_url(appConfig('payments.yape_qr_image')), ENT_QUOTES, 'UTF-8'); ?>" alt="Código QR oficial de Yape de <?php echo htmlspecialchars(appConfig('payments.yape_display_name'), ENT_QUOTES, 'UTF-8'); ?>">
<h2><?php echo htmlspecialchars(appConfig('payments.yape_display_name'), ENT_QUOTES, 'UTF-8'); ?></h2>
<?php if (appConfig('payments.yape_phone') !== ''): ?><p>Yape: <?php echo htmlspecialchars(appConfig('payments.yape_phone'), ENT_QUOTES, 'UTF-8'); ?></p><?php else: ?><p>Escanea el código QR para pagar.</p><?php endif; ?>
</div><div class="detail-info">
<h2><?php echo htmlspecialchars($reservation['evento'], ENT_QUOTES, 'UTF-8'); ?></h2>
<ul class="meta-list"><li>Código: <?php echo htmlspecialchars($reservation['codigo_reserva'], ENT_QUOTES, 'UTF-8'); ?></li><li>Cantidad: <?php echo (int) $reservation['cantidad']; ?></li><li>Precio unitario: S/ <?php echo formatMoney($reservation['precio_unitario']); ?></li><li>Total exacto: <strong>S/ <?php echo formatMoney($reservation['monto_total']); ?></strong></li><li>Vence: <?php echo htmlspecialchars(date('d/m/Y H:i:s', strtotime($reservation['fecha_expiracion'])), ENT_QUOTES, 'UTF-8'); ?></li></ul>
<?php if ($status === 'pendiente_pago'): ?>
<p class="countdown" data-countdown="<?php echo htmlspecialchars(date('c', strtotime($reservation['fecha_expiracion'])), ENT_QUOTES, 'UTF-8'); ?>">Calculando tiempo restante…</p>
<ol><li>Escanea el QR.</li><li>Paga el monto exacto.</li><li>Guarda la captura.</li><li>Registra el código de operación.</li><li>Adjunta el comprobante.</li><li>Espera la validación administrativa.</li></ol>
<form class="form-container" method="POST" enctype="multipart/form-data" action="<?php echo htmlspecialchars(base_url('views/client/reservation_payment.php'), ENT_QUOTES, 'UTF-8'); ?>">
<?php echo csrfField(); ?><input type="hidden" name="id" value="<?php echo $reservationId; ?>">
<div class="form-group"><label for="codigo_operacion">Código de operación Yape</label><input id="codigo_operacion" name="codigo_operacion" type="text" minlength="6" maxlength="60" pattern="[A-Za-z0-9 -]+" required></div>
<div class="form-group"><label for="comprobante">Comprobante (JPG, PNG o WebP; máximo <?php echo number_format(appConfig('payments.proof_max_bytes') / 1048576, 1); ?> MB)</label><input id="comprobante" name="comprobante" type="file" accept="image/jpeg,image/png,image/webp" required></div>
<button class="btn btn-primary" type="submit">Enviar pago para revisión</button></form>
<?php else: ?><p class="alert alert-info">Esta reserva está en estado <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?> y ya no admite pagos.</p><?php endif; ?>
<a class="btn btn-outline" href="<?php echo htmlspecialchars(base_url('views/client/reservation_detail.php?id=' . $reservationId), ENT_QUOTES, 'UTF-8'); ?>">Ver detalle</a>
</div></div></section></main><?php require __DIR__ . '/../../includes/footer.php'; ?>
