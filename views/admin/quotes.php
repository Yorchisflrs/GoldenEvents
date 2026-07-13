<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');
$message = '';
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $result = AdminController::changeQuoteStatus((int) ($_POST['quote_id'] ?? 0), trim((string) ($_POST['estado'] ?? '')), (int) currentUser()['id']);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
$quotes = AdminController::getQuotes();
$pageTitle = 'Cotizaciones';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section">
<h1 class="page-title">Cotizaciones</h1><p class="page-subtitle">Consulta el detalle protegido y aplica únicamente transiciones de estado válidas.</p>
<?php if ($message !== ''): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<div class="table-wrapper"><table><thead><tr><th>ID</th><th>Cliente</th><th>Evento</th><th>Total</th><th>Estado</th><th>Detalle</th><th>Actualizar</th></tr></thead><tbody>
<?php foreach ($quotes as $quote): ?><tr>
<td>#<?php echo (int) $quote['id']; ?></td><td><?php echo htmlspecialchars($quote['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($quote['telefono_cliente'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($quote['email_cliente'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo htmlspecialchars($quote['tipo_evento'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo (int) $quote['cantidad_invitados']; ?> invitados<br><?php echo htmlspecialchars($quote['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
<td>S/ <?php echo number_format((float) $quote['total_estimado'], 2); ?></td><td><span class="badge badge-<?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
<td><a href="/GoldenHoursEvents/views/client/quote_result.php?id=<?php echo (int) $quote['id']; ?>">Ver servicios</a></td>
<td><?php $nextByState = ['pendiente' => ['contactado', 'rechazado', 'cancelado'], 'contactado' => ['aprobado', 'rechazado', 'cancelado'], 'aprobado' => ['cancelado'], 'rechazado' => [], 'cancelado' => []]; $nextStates = $nextByState[$quote['estado']] ?? []; ?>
<?php if ($nextStates): ?><form method="POST" action="/GoldenHoursEvents/views/admin/quotes.php"><?php echo csrfField(); ?><input type="hidden" name="quote_id" value="<?php echo (int) $quote['id']; ?>"><select name="estado" required><?php foreach ($nextStates as $state): ?><option value="<?php echo $state; ?>"><?php echo htmlspecialchars($state, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select><button class="btn btn-primary" type="submit">Aplicar</button></form><?php else: ?>Sin transiciones<?php endif; ?></td>
</tr><?php endforeach; ?></tbody></table></div>
</section></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
