<?php
// Creacion de eventos por organizador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/EventController.php';
requireLogin();
requireRole('organizador');

$user = currentUser();
$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $result = EventController::createEvent($user['id'], $_POST);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

$pageTitle = 'Crear evento interno';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Crear evento interno</h1>
        <p class="page-subtitle">Modulo secundario para eventos de referencia. El producto principal usa cotizaciones por servicios.</p>

        <?php if ($message !== ''): ?>
            <p class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form class="form-container" method="POST" action="/GoldenHoursEvents/views/organizer/create_event.php">
            <?php echo csrfField(); ?>
            <div class="grid-2">
                <div class="form-group">
                    <label for="titulo">Titulo</label>
                    <input type="text" id="titulo" name="titulo" required>
                </div>
                <div class="form-group">
                    <label for="categoria">Categoria</label>
                    <input type="text" id="categoria" name="categoria">
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="fecha_inicio">Fecha inicio</label>
                    <input type="datetime-local" id="fecha_inicio" name="fecha_inicio" required>
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha fin</label>
                    <input type="datetime-local" id="fecha_fin" name="fecha_fin">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="lugar">Lugar</label>
                    <input type="text" id="lugar" name="lugar" required>
                </div>
                <div class="form-group">
                    <label for="direccion">Direccion</label>
                    <input type="text" id="direccion" name="direccion">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="cupo_total">Cupo total</label>
                    <input type="number" id="cupo_total" name="cupo_total" min="1" required>
                </div>
                <div class="form-group">
                    <label for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" min="0" step="0.01" required>
                </div>
            </div>

            <button class="btn btn-primary" type="submit">Guardar</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
