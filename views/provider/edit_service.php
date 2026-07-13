<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
require_once __DIR__ . '/../../models/Category.php';
requireLogin(); requireRole('proveedor');
$serviceId = (int) ($_GET['id'] ?? 0);
$service = ServiceController::getOwnedService($serviceId, (int) currentUser()['id']);
if (!$service) { redirect('/GoldenHoursEvents/views/errors/403.php'); }
$message = ''; $messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { requireValidCsrfToken(); $result = ServiceController::updateService($serviceId, (int) currentUser()['id'], $_POST, $_FILES); $message = $result['message']; $messageType = $result['success'] ? 'success' : 'error'; $service = ServiceController::getOwnedService($serviceId, (int) currentUser()['id']); }
$categories = Category::allActive();
$pageTitle = 'Editar servicio'; require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Editar servicio</h1><p class="page-subtitle">Al guardar, el servicio vuelve a revisión administrativa.</p><?php if ($message): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form class="form-container" method="POST" enctype="multipart/form-data" action="/GoldenHoursEvents/views/provider/edit_service.php?id=<?php echo $serviceId; ?>"><?php echo csrfField(); ?>
<div class="form-group"><label for="categoria_id">Categoría</label><select id="categoria_id" name="categoria_id" required><?php foreach ($categories as $category): ?><option value="<?php echo (int) $category['id']; ?>" <?php echo (int) $service['categoria_id'] === (int) $category['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['nombre'], ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div>
<div class="grid-2"><div class="form-group"><label for="nombre">Nombre</label><input id="nombre" name="nombre" value="<?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="form-group"><label for="precio">Precio</label><input id="precio" name="precio" type="number" min="0" step="0.01" value="<?php echo htmlspecialchars($service['precio'], ENT_QUOTES, 'UTF-8'); ?>" required></div></div>
<div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($service['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
<div class="grid-2"><div class="form-group"><label for="capacidad">Capacidad</label><input id="capacidad" name="capacidad" type="number" min="1" value="<?php echo htmlspecialchars($service['capacidad'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div><div class="form-group"><label for="ubicacion">Ubicación</label><input id="ubicacion" name="ubicacion" value="<?php echo htmlspecialchars($service['ubicacion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div></div>
<div class="form-group"><label for="imagen">Reemplazar imagen</label><input id="imagen" name="imagen" type="file" accept=".jpg,.jpeg,.png,.webp"></div><label><input type="checkbox" name="disponibilidad" value="1" <?php echo (int) $service['disponibilidad'] === 1 ? 'checked' : ''; ?>> Disponible</label><button class="btn btn-primary" type="submit">Guardar y enviar a revisión</button>
</form></section></main><?php require_once __DIR__ . '/../../includes/footer.php'; ?>
