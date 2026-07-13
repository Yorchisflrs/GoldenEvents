<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/EventController.php';
requireLogin(); requireRole('organizador');
$message = ''; $messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { requireValidCsrfToken(); $result = EventController::createEvent((int) currentUser()['id'], $_POST, $_FILES); $message = $result['message']; $messageType = $result['success'] ? 'success' : 'error'; }
$categories = EventController::categories();
$pageTitle = 'Crear evento'; require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Crear evento</h1><p class="page-subtitle">El evento se enviará a aprobación administrativa antes de aparecer en el catálogo.</p>
<?php if ($message): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<form class="form-container" method="POST" enctype="multipart/form-data" action="/GoldenHoursEvents/views/organizer/create_event.php"><?php echo csrfField(); ?>
<div class="grid-2"><div class="form-group"><label for="titulo">Título</label><input id="titulo" name="titulo" required></div><div class="form-group"><label for="categoria">Categoría</label><select id="categoria" name="categoria" required><option value="">Selecciona</option><?php foreach ($categories as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
<div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" required></textarea></div>
<div class="grid-2"><div class="form-group"><label for="fecha_inicio">Fecha inicial</label><input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required></div><div class="form-group"><label for="fecha_fin">Fecha final</label><input type="datetime-local" id="fecha_fin" name="fecha_fin"></div></div>
<div class="grid-2"><div class="form-group"><label for="lugar">Lugar</label><input id="lugar" name="lugar" required></div><div class="form-group"><label for="direccion">Dirección</label><input id="direccion" name="direccion"></div></div>
<div class="grid-2"><div class="form-group"><label for="cupo_total">Cupo total</label><input type="number" id="cupo_total" name="cupo_total" min="1" required></div><div class="form-group"><label for="precio">Precio</label><input type="number" id="precio" name="precio" min="0" step="0.01" required></div></div>
<div class="form-group"><label for="imagen">Imagen</label><input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp"></div><button class="btn btn-primary" type="submit">Crear y enviar a aprobación</button>
</form></section></main><?php require_once __DIR__ . '/../../includes/footer.php'; ?>
