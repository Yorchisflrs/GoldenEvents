<?php
// Gestion de reservas para administrador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$reservations = AdminController::getReservations();
$pageTitle = 'Reservas futuras';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Reservas futuras</h1>
        <p class="page-subtitle">Modulo heredado para fases posteriores. El flujo principal actual usa cotizaciones.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Evento</th>
                        <th>Cantidad</th>
                        <th>Monto</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($reservation['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($reservation['evento'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $reservation['cantidad']; ?></td>
                            <td>S/ <?php echo number_format((float) $reservation['monto_total'], 2); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($reservation['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($reservation['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($reservation['fecha_reserva'], ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
