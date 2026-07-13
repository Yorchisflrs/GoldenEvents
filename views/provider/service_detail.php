<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
requireLogin();
requireRole('proveedor');
$service = ServiceController::getOwnedService((int) ($_GET['id'] ?? 0), (int) currentUser()['id']);
if (!$service) { redirect('/GoldenHoursEvents/views/errors/403.php'); }
$pageTitle = 'Detalle del servicio';
require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title"><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></h1><p><span class="badge badge-<?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($service['estado'], ENT_QUOTES, 'UTF-8'); ?></span></p>
<?php if (!empty($service['imagen'])): ?><img class="service-img" src="/GoldenHoursEvents/<?php echo htmlspecialchars($service['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt=""><?php endif; ?><p><?php echo nl2br(htmlspecialchars($service['descripcion'], ENT_QUOTES, 'UTF-8')); ?></p><p><strong>Categoría:</strong> <?php echo htmlspecialchars($service['categoria'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p><p><strong>Precio:</strong> S/ <?php echo number_format((float) $service['precio'], 2); ?></p><p><strong>Capacidad:</strong> <?php echo htmlspecialchars($service['capacidad'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p><p><strong>Ubicación:</strong> <?php echo htmlspecialchars($service['ubicacion'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></p><p><strong>Disponibilidad:</strong> <?php echo (int) $service['disponibilidad'] === 1 ? 'Disponible' : 'No disponible'; ?></p><?php if (!empty($service['motivo_rechazo'])): ?><p class="alert alert-error"><strong>Motivo:</strong> <?php echo htmlspecialchars($service['motivo_rechazo'], ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?><a class="btn btn-primary" href="/GoldenHoursEvents/views/provider/edit_service.php?id=<?php echo (int) $service['id']; ?>">Editar</a> <a class="btn btn-outline" href="/GoldenHoursEvents/views/provider/my_services.php">Volver</a>
</section></main><?php require_once __DIR__ . '/../../includes/footer.php'; ?>
