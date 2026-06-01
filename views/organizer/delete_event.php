<?php
// Cancelacion logica de eventos propios del organizador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/EventController.php';
require_once __DIR__ . '/../../models/Event.php';
requireLogin();
requireRole('organizador');

$user = currentUser();
$eventId = (int) ($_GET['id'] ?? 0);
$event = Event::findById($eventId);

if (!$event || (int) $event['organizador_id'] !== (int) $user['id']) {
    redirect('/GoldenHoursEvents/views/errors/403.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    EventController::cancelEvent($eventId, $user['id']);
    redirect('/GoldenHoursEvents/views/organizer/my_events.php');
}

$pageTitle = 'Cancelar evento';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <h1>Cancelar evento</h1>
    <p>Estas seguro de cancelar el evento <strong><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></strong>?</p>

    <form method="POST" action="/GoldenHoursEvents/views/organizer/delete_event.php?id=<?php echo $eventId; ?>">
        <button class="btn" type="submit">Si, cancelar evento</button>
        <a class="btn btn-outline" href="/GoldenHoursEvents/views/organizer/my_events.php">Volver</a>
    </form>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
