<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
requireLogin();
requireRole('proveedor');
$services = ServiceController::myServices(currentUser()['id']);
$message = trim((string) ($_GET['message'] ?? ''));
$pageTitle = 'Mis servicios';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Mis servicios</h1><p class="page-subtitle">Gestiona información, disponibilidad y estado de moderación.</p><p><a class="btn btn-primary" href="/GoldenHoursEvents/views/provider/create_service.php">Crear servicio</a></p>
<?php if ($message): ?><p class="alert alert-info"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if (!$services): ?><div class="empty-state"><h2>Todavía no registraste servicios.</h2></div><?php else: ?><div class="table-wrapper"><table><thead><tr><th>Servicio</th><th>Categoría</th><th>Precio</th><th>Disponibilidad</th><th>Moderación</th><th>Acciones</th></tr></thead><tbody>
<?php foreach ($services as $service): ?><tr><td><?php if (!empty($service['imagen'])): ?><img class="thumb" src="/GoldenHoursEvents/<?php echo htmlspecialchars($service['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?> <?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></td><td><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoría', ENT_QUOTES, 'UTF-8'); ?></td><td>S/ <?php echo number_format((float) $service['precio'], 2); ?></td><td><?php echo (int) $service['disponibilidad'] === 1 ? 'Disponible' : 'No disponible'; ?></td><td><span class="badge badge-<?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?></span><?php if (!empty($service['motivo_rechazo'])): ?><br><?php echo htmlspecialchars($service['motivo_rechazo'], ENT_QUOTES, 'UTF-8'); ?><?php endif; ?></td><td>
<a href="/GoldenHoursEvents/views/provider/service_detail.php?id=<?php echo (int) $service['id']; ?>">Ver</a> | <a href="/GoldenHoursEvents/views/provider/edit_service.php?id=<?php echo (int) $service['id']; ?>">Editar</a>
<form class="inline-form" method="POST" action="/GoldenHoursEvents/views/provider/service_action.php"><?php echo csrfField(); ?><input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>"><input type="hidden" name="action" value="availability"><input type="hidden" name="availability" value="<?php echo (int) $service['disponibilidad'] === 1 ? '0' : '1'; ?>"><button class="btn btn-outline" type="submit"><?php echo (int) $service['disponibilidad'] === 1 ? 'No disponible' : 'Disponible'; ?></button></form>
<?php if ($service['estado'] !== 'inactivo'): ?><form class="inline-form" method="POST" action="/GoldenHoursEvents/views/provider/service_action.php"><?php echo csrfField(); ?><input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>"><input type="hidden" name="action" value="disable"><button class="btn btn-outline" type="submit">Desactivar</button></form><?php endif; ?>
<?php if (in_array($service['estado'], ['rechazado','inactivo'], true)): ?><form class="inline-form" method="POST" action="/GoldenHoursEvents/views/provider/service_action.php"><?php echo csrfField(); ?><input type="hidden" name="service_id" value="<?php echo (int) $service['id']; ?>"><input type="hidden" name="action" value="review"><button class="btn btn-primary" type="submit">Enviar a revisión</button></form><?php endif; ?>
</td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section></main><?php require_once __DIR__ . '/../../includes/footer.php'; ?>
