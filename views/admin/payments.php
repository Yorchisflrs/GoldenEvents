<?php
// Gestion de pagos para administrador.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
requireLogin();
requireRole('admin');

$payments = AdminController::getPayments();
$pageTitle = 'Pagos futuros';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Pagos futuros</h1>
        <p class="page-subtitle">Modulo preparado para integraciones posteriores. Las cotizaciones no generan pagos reales.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Referencia</th>
                        <th>Cliente</th>
                        <th>Evento</th>
                        <th>Monto</th>
                        <th>Metodo</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($payments as $payment): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($payment['referencia'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($payment['cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($payment['evento'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>S/ <?php echo number_format((float) $payment['monto'], 2); ?></td>
                            <td><?php echo htmlspecialchars($payment['metodo'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($payment['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($payment['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($payment['fecha_pago'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
