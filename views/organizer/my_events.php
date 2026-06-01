<?php
// Listado de eventos del organizador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/EventController.php';
requireLogin();
requireRole('organizador');

$user = currentUser();
$events = EventController::myEvents($user['id']);

$pageTitle = 'Mis eventos internos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Mis eventos internos</h1>
        <p class="page-subtitle">Gestion secundaria de eventos. El flujo principal del sistema son servicios y cotizaciones.</p>
        <p><a class="btn btn-primary" href="/GoldenHoursEvents/views/organizer/create_event.php">Crear evento interno</a></p>

        <?php if (empty($events)): ?>
            <div class="empty-state">
                <h2>🗓️ Aun no has creado eventos.</h2>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Titulo</th>
                            <th>Fecha</th>
                            <th>Lugar</th>
                            <th>Cupo</th>
                            <th>Precio</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($event['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $event['cupo_total']; ?></td>
                                <td>S/ <?php echo number_format((float) $event['precio'], 2); ?></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td>
                                    <a href="/GoldenHoursEvents/views/organizer/edit_event.php?id=<?php echo (int) $event['id']; ?>">Editar</a>
                                    |
                                    <a data-confirm="Cancelar este evento?" href="/GoldenHoursEvents/views/organizer/delete_event.php?id=<?php echo (int) $event['id']; ?>">Cancelar</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
