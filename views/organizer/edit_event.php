<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/EventController.php';
require_once __DIR__ . '/../../models/Event.php';
requireLogin(); requireRole('organizador');
$eventId = (int) ($_GET['id'] ?? 0); $userId = (int) currentUser()['id']; $event = Event::findOwnedById($eventId, $userId);
if (!$event) { redirect('/GoldenHoursEvents/views/errors/403.php'); }
$message = ''; $messageType = 'error';
if ($_SERVER['REQUEST_METHOD'] === 'POST') { requireValidCsrfToken(); $result = EventController::updateEvent($eventId, $userId, $_POST, $_FILES); $message = $result['message']; $messageType = $result['success'] ? 'success' : 'error'; $event = Event::findOwnedById($eventId, $userId); }
$categories = EventController::categories();
function organizerDateTimeLocal($value) { return $value ? date('Y-m-d\TH:i', strtotime($value)) : ''; }
$pageTitle = 'Editar evento'; require_once __DIR__ . '/../../includes/header.php'; require_once __DIR__ . '/../../includes/navbar.php';
?>
<main class="container"><section class="section"><h1 class="page-title">Editar evento</h1><p class="page-subtitle">Un evento publicado vuelve a pendiente de aprobación al guardar cambios.</p>
<?php if ($message): ?><p class="alert alert-<?php echo $messageType; ?>"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p><?php endif; ?>
<?php if (in_array($event['estado'], ['cancelado','finalizado'], true)): ?><p class="alert alert-error">Este evento ya no puede editarse.</p><?php else: ?>
<form class="form-container" method="POST" enctype="multipart/form-data" action="/GoldenHoursEvents/views/organizer/edit_event.php?id=<?php echo $eventId; ?>"><?php echo csrfField(); ?>
<div class="grid-2"><div class="form-group"><label for="titulo">Título</label><input id="titulo" name="titulo" value="<?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="form-group"><label for="categoria">Categoría</label><select id="categoria" name="categoria" required><?php foreach ($categories as $category): ?><option value="<?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $event['categoria'] === $category ? 'selected' : ''; ?>><?php echo htmlspecialchars($category, ENT_QUOTES, 'UTF-8'); ?></option><?php endforeach; ?></select></div></div>
<div class="form-group"><label for="descripcion">Descripción</label><textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($event['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea></div>
<div class="grid-2"><div class="form-group"><label for="fecha_inicio">Inicio</label><input type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars(organizerDateTimeLocal($event['fecha_inicio']), ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="form-group"><label for="fecha_fin">Final</label><input type="datetime-local" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars(organizerDateTimeLocal($event['fecha_fin']), ENT_QUOTES, 'UTF-8'); ?>"></div></div>
<div class="grid-2"><div class="form-group"><label for="lugar">Lugar</label><input id="lugar" name="lugar" value="<?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?>" required></div><div class="form-group"><label for="direccion">Dirección</label><input id="direccion" name="direccion" value="<?php echo htmlspecialchars($event['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"></div></div>
<div class="grid-2"><div class="form-group"><label for="cupo_total">Cupo</label><input type="number" id="cupo_total" name="cupo_total" min="1" value="<?php echo (int) $event['cupo_total']; ?>" required></div><div class="form-group"><label for="precio">Precio</label><input type="number" id="precio" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($event['precio'], ENT_QUOTES, 'UTF-8'); ?>" required></div></div>
<div class="form-group"><label for="imagen">Reemplazar imagen</label><input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp"></div><button class="btn btn-primary" type="submit">Guardar y enviar a aprobación</button>
</form><?php endif; ?></section></main><?php require_once __DIR__ . '/../../includes/footer.php'; ?>
