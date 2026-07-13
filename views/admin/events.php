<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../controllers/EventController.php';
requireLogin();
requireRole('admin');
$message = '';
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $result = AdminController::moderateEvent((int) ($_POST['event_id'] ?? 0), trim((string) ($_POST['action'] ?? '')), trim((string) ($_POST['motivo'] ?? '')), (int) currentUser()['id']);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
$filters = ['estado' => trim((string) ($_GET['estado'] ?? '')), 'categoria' => trim((string) ($_GET['categoria'] ?? '')), 'organizador_id' => (int) ($_GET['organizador_id'] ?? 0)];
$events = AdminController::getEvents($filters);
$categories = EventController::categories();
$pageTitle = 'Moderación de eventos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section">
<h1 class="page-title">Moderación de eventos</h1><p class="page-subtitle">Solo administración puede publicar, rechazar, cancelar o desactivar eventos.</p>
<?php if ($message !== ''): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form class="form-container" method="GET"><div class="grid-3">
<div class="form-group"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach (['borrador','pendiente_aprobacion','publicado','rechazado','cancelado','finalizado','inactivo'] as $state): ?><option value="<?php echo $state; ?>" <?php echo $filters['estado'] === $state ? 'selected' : ''; ?>><?php echo $state; ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="categoria">Categoría</label><select id="categoria" name="categoria"><option value="">Todas</option><?php foreach ($categories as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $filters['categoria'] === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
<div class="form-group"><label for="organizador_id">ID organizador</label><input id="organizador_id" name="organizador_id" type="number" min="1" value="<?php echo $filters['organizador_id'] ?: ''; ?>"></div>
</div><button class="btn btn-outline" type="submit">Filtrar</button></form>
<div class="table-wrapper"><table><thead><tr><th>Evento</th><th>Organizador</th><th>Fecha/lugar</th><th>Aforo/precio</th><th>Moderación</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($events as $event): ?><tr>
<td>#<?php echo (int) $event['id']; ?> — <?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($event['categoria'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($event['descripcion'], ENT_QUOTES, 'UTF-8'); ?></small></td>
<td><?php echo htmlspecialchars($event['organizador'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($event['organizador_email'], ENT_QUOTES, 'UTF-8'); ?><br>ID: <?php echo (int) $event['organizador_id']; ?></td>
<td><?php echo htmlspecialchars($event['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($event['fecha_fin'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($event['direccion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
<td><?php echo (int) $event['cupo_total']; ?> cupos<br>S/ <?php echo number_format((float) $event['precio'], 2); ?></td>
<td><span class="badge badge-<?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?></span><br><?php echo htmlspecialchars($event['motivo_rechazo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($event['aprobador_nombre'] ?? '-', ENT_QUOTES, 'UTF-8'); ?> <?php echo htmlspecialchars($event['aprobado_en'] ?? '', ENT_QUOTES, 'UTF-8'); ?></small></td>
<td><form method="POST" action="/GoldenHoursEvents/views/admin/events.php"><?php echo csrfField(); ?><input type="hidden" name="event_id" value="<?php echo (int) $event['id']; ?>">
<select name="action" required><option value="">Seleccionar</option>
<?php if ($event['estado'] === 'pendiente_aprobacion'): ?><option value="approve">Aprobar</option><option value="reject">Rechazar</option><?php endif; ?>
<?php if ($event['estado'] === 'publicado'): ?><option value="disable">Desactivar</option><option value="cancel">Cancelar</option><?php endif; ?>
<?php if (in_array($event['estado'], ['borrador','rechazado','inactivo'], true)): ?><option value="review">Devolver a revisión</option><option value="cancel">Cancelar</option><?php endif; ?>
</select><input type="text" name="motivo" maxlength="500" placeholder="Motivo de rechazo"><button class="btn btn-primary" type="submit">Aplicar</button></form></td>
</tr><?php endforeach; ?>
</tbody></table></div>
</section></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
