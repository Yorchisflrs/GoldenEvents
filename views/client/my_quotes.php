<?php
// Cotizaciones del cliente autenticado.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/QuoteController.php';
requireLogin();
requireRole('cliente');

$quotes = QuoteController::myQuotes(currentUser()['id']);

$pageTitle = 'Mis cotizaciones';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Mis cotizaciones</h1>
        <p class="page-subtitle">Revisa el estado de tus solicitudes y el total estimado de cada evento.</p>

        <?php if (empty($quotes)): ?>
            <div class="empty-state">
                <h2>📋 No tienes cotizaciones registradas.</h2>
                <p>Empieza seleccionando servicios para tu proximo evento.</p>
                <a class="btn btn-primary" href="/GoldenHoursEvents/views/client/services.php">Ver servicios</a>
            </div>
        <?php else: ?>
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Tipo de evento</th>
                            <th>Fecha evento</th>
                            <th>Invitados</th>
                            <th>Total estimado</th>
                            <th>Estado</th>
                            <th>Fecha registro</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($quotes as $quote): ?>
                            <tr>
                                <td>#<?php echo (int) $quote['id']; ?></td>
                                <td><?php echo htmlspecialchars($quote['tipo_evento'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo htmlspecialchars($quote['fecha_evento'] ?? '-', ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo (int) $quote['cantidad_invitados']; ?></td>
                                <td><strong>S/ <?php echo number_format((float) $quote['total_estimado'], 2); ?></strong></td>
                                <td><span class="badge badge-<?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                                <td><?php echo htmlspecialchars($quote['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
