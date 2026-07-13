<?php
// Resultado privado de una cotizacion registrada.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/QuoteController.php';

requireLogin();

$quoteId = (int) ($_GET['id'] ?? 0);
$user = currentUser();
$data = QuoteController::getQuote($quoteId, (int) $user['id'], $user['rol'] === 'admin');
$quote = $data['quote'];
$details = $data['details'];

if (!$quote) {
    redirect('/GoldenHoursEvents/views/errors/404.php');
}

$pageTitle = 'Cotizacion registrada';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <div class="quote-card">
            <div class="success-icon">✅</div>
            <h1 class="page-title">Solicitud de cotizacion registrada correctamente.</h1>
            <p class="page-subtitle">El equipo de Golden Hour Events se comunicara contigo para confirmar disponibilidad y detalles.</p>

            <div class="grid-2">
                <div>
                    <p><strong>Numero:</strong> #<?php echo (int) $quote['id']; ?></p>
                    <p><strong>Cliente:</strong> <?php echo htmlspecialchars($quote['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Tipo de evento:</strong> <?php echo htmlspecialchars($quote['tipo_evento'], ENT_QUOTES, 'UTF-8'); ?></p>
                    <p><strong>Invitados:</strong> <?php echo (int) $quote['cantidad_invitados']; ?></p>
                </div>
                <div class="total-box">
                    <span>Total estimado</span>
                    <strong>S/ <?php echo number_format((float) $quote['total_estimado'], 2); ?></strong>
                    <span class="badge badge-<?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?></span>
                </div>
            </div>
        </div>

        <h2 class="page-title">Servicios seleccionados</h2>
        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>Categoria</th>
                        <th>Servicio</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($details as $detail): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($detail['categoria_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($detail['servicio_nombre'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>S/ <?php echo number_format((float) $detail['precio_unitario'], 2); ?></td>
                            <td><?php echo (int) $detail['cantidad']; ?></td>
                            <td>S/ <?php echo number_format((float) $detail['subtotal'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="actions">
            <a class="btn btn-outline" href="/GoldenHoursEvents/views/client/services.php">Ver servicios</a>
            <a class="btn btn-primary" href="/GoldenHoursEvents/views/client/build_event.php">Armar otro evento</a>
            <a class="btn btn-secondary" href="/GoldenHoursEvents/index.php">Inicio</a>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
