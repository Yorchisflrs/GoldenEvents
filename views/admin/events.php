<?php
// Gestion general de eventos para administrador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$events = AdminController::getEvents();
$pageTitle = 'Gestion de eventos internos';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Eventos internos</h1>
        <p class="page-subtitle">Modulo heredado; la version principal se centra en servicios y cotizaciones.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Titulo</th>
                        <th>Organizador</th>
                        <th>Fecha</th>
                        <th>Lugar</th>
                        <th>Cupo</th>
                        <th>Precio</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($event['titulo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($event['organizador'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($event['fecha_inicio'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($event['lugar'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $event['cupo_total']; ?></td>
                            <td>S/ <?php echo number_format((float) $event['precio'], 2); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($event['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
