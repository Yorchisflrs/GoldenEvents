<?php
// Edicion de eventos propios del organizador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/EventController.php';
require_once __DIR__ . '/../../models/Event.php';
requireLogin();
requireRole('organizador');

$user = currentUser();
$eventId = (int) ($_GET['id'] ?? 0);
$event = Event::findById($eventId);
$message = '';
$messageType = 'error';

if (!$event || (int) $event['organizador_id'] !== (int) $user['id']) {
    redirect('/GoldenHoursEvents/views/errors/403.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $result = EventController::updateEvent($eventId, $user['id'], $_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
    $event = Event::findById($eventId);
}

function datetime_local_value($value)
{
    return $value ? date('Y-m-d\TH:i', strtotime($value)) : '';
}

$pageTitle = 'Editar evento';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <h1>Editar evento</h1>

    <?php if ($message !== ''): ?>
        <p class="alert <?php echo htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8'); ?>">
            <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
        </p>
    <?php endif; ?>

    <form class="form" method="POST" action="/GoldenHoursEvents/views/organizer/edit_event.php?id=<?php echo $eventId; ?>">
        <?php echo csrfField(); ?>
        <label for="titulo">Titulo</label>
        <input type="text" id="titulo" name="titulo" value="<?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="descripcion">Descripcion</label>
        <textarea id="descripcion" name="descripcion" required><?php echo htmlspecialchars($event['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea>

        <label for="categoria">Categoria</label>
        <input type="text" id="categoria" name="categoria" value="<?php echo htmlspecialchars($event['categoria'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label for="fecha_inicio">Fecha inicio</label>
        <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" value="<?php echo htmlspecialchars(datetime_local_value($event['fecha_inicio']), ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="fecha_fin">Fecha fin</label>
        <input type="datetime-local" id="fecha_fin" name="fecha_fin" value="<?php echo htmlspecialchars(datetime_local_value($event['fecha_fin']), ENT_QUOTES, 'UTF-8'); ?>">

        <label for="lugar">Lugar</label>
        <input type="text" id="lugar" name="lugar" value="<?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="direccion">Direccion</label>
        <input type="text" id="direccion" name="direccion" value="<?php echo htmlspecialchars($event['direccion'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

        <label for="cupo_total">Cupo total</label>
        <input type="number" id="cupo_total" name="cupo_total" min="1" value="<?php echo (int) $event['cupo_total']; ?>" required>

        <label for="precio">Precio</label>
        <input type="number" id="precio" name="precio" min="0" step="0.01" value="<?php echo htmlspecialchars($event['precio'], ENT_QUOTES, 'UTF-8'); ?>" required>

        <label for="estado">Estado</label>
        <select id="estado" name="estado">
            <?php foreach (['borrador', 'pendiente_aprobacion', 'publicado', 'rechazado', 'cancelado', 'finalizado', 'inactivo'] as $estado): ?>
                <option value="<?php echo $estado; ?>" <?php echo $event['estado'] === $estado ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($estado, ENT_QUOTES, 'UTF-8'); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button class="btn" type="submit">Guardar cambios</button>
    </form>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
