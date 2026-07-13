<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../models/Category.php';
requireLogin();
requireRole('admin');

$message = '';
$messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();
    $result = AdminController::moderateService((int) ($_POST['service_id'] ?? 0), trim((string) ($_POST['action'] ?? '')), trim((string) ($_POST['motivo'] ?? '')), (int) currentUser()['id']);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}
$filters = [
    'estado' => trim((string) ($_GET['estado'] ?? '')),
    'categoria_id' => (int) ($_GET['categoria_id'] ?? 0),
    'proveedor_id' => (int) ($_GET['proveedor_id'] ?? 0),
];
$services = AdminController::getServices($filters);
$categories = Category::allActive();
$pageTitle = 'Moderación de servicios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section">
<h1 class="page-title">Moderación de servicios</h1>
<p class="page-subtitle">Consulta la información completa y controla la publicación del marketplace.</p>
<?php if ($message !== ''): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form class="form-container" method="GET"><div class="grid-3">
    <div class="form-group"><label for="estado">Estado</label><select id="estado" name="estado"><option value="">Todos</option><?php foreach (['pendiente','activo','rechazado','inactivo'] as $state): ?><option value="<?php echo $state; ?>" <?php echo $filters['estado'] === $state ? 'selected' : ''; ?>><?php echo $state; ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id"><option value="0">Todas</option><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['id']; ?>" <?php echo $filters['categoria_id'] === (int) $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['nombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
    <div class="form-group"><label for="proveedor_id">ID proveedor</label><input id="proveedor_id" name="proveedor_id" type="number" min="1" value="<?php echo $filters['proveedor_id'] ?: ''; ?>"></div>
</div><button class="btn btn-outline" type="submit">Filtrar</button></form>
<div class="table-wrapper"><table><thead><tr><th>Servicio</th><th>Proveedor</th><th>Datos</th><th>Moderación</th><th>Acción</th></tr></thead><tbody>
<?php foreach ($services as $service): ?><tr>
<td>#<?php echo (int) $service['id']; ?> — <?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($service['descripcion'], ENT_QUOTES, 'UTF-8'); ?></small></td>
<td><?php echo htmlspecialchars($service['proveedor'], ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($service['proveedor_email'], ENT_QUOTES, 'UTF-8'); ?><br>ID: <?php echo (int) $service['proveedor_id']; ?></td>
<td><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8'); ?><br>S/ <?php echo number_format((float) $service['precio'], 2); ?><br>Capacidad: <?php echo htmlspecialchars($service['capacidad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><?php echo htmlspecialchars($service['ubicacion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><?php echo (int) $service['disponibilidad'] === 1 ? 'Disponible' : 'No disponible'; ?></td>
<td><span class="badge badge-<?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?></span><br><?php echo htmlspecialchars($service['motivo_rechazo'] ?? '-', ENT_QUOTES, 'UTF-8'); ?><br><small><?php echo htmlspecialchars($service['aprobado_en'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></small></td>
<td><form method="POST" action="/GoldenHoursEvents/views/admin/services.php">
<?php echo csrfField(); ?><input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>">
<select name="action" required><option value="">Seleccionar</option>
<?php if ($service['estado'] === 'pendiente'): ?><option value="approve">Aprobar</option><option value="reject">Rechazar</option><?php endif; ?>
<?php if ($service['estado'] === 'activo'): ?><option value="disable">Desactivar</option><?php endif; ?>
<?php if ($service['estado'] === 'inactivo'): ?><option value="reactivate">Reactivar</option><option value="review">Devolver a revisión</option><?php endif; ?>
<?php if ($service['estado'] === 'rechazado'): ?><option value="review">Devolver a revisión</option><?php endif; ?>
</select><input type="text" name="motivo" maxlength="500" placeholder="Motivo de rechazo"><button class="btn btn-primary" type="submit">Aplicar</button>
</form></td></tr><?php endforeach; ?>
</tbody></table></div>
</section></main>
<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
