<?php
// Administracion de cotizaciones.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/AdminController.php';
require_once __DIR__ . '/../../controllers/QuoteController.php';
requireLogin();
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    QuoteController::changeStatus((int) ($_POST['quote_id'] ?? 0), trim($_POST['estado'] ?? ''));
}

$quotes = AdminController::getQuotes();
$pageTitle = 'Cotizaciones';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Cotizaciones</h1>
        <p class="page-subtitle">Solicitudes recibidas desde el armador de eventos personalizados.</p>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Telefono</th>
                        <th>Email</th>
                        <th>Tipo evento</th>
                        <th>Invitados</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Actualizar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($quotes as $quote): ?>
                        <tr>
                            <td>#<?php echo (int) $quote['id']; ?></td>
                            <td><?php echo htmlspecialchars($quote['nombre_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($quote['telefono_cliente'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($quote['email_cliente'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo htmlspecialchars($quote['tipo_evento'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?php echo (int) $quote['cantidad_invitados']; ?></td>
                            <td>S/ <?php echo number_format((float) $quote['total_estimado'], 2); ?></td>
                            <td><span class="badge badge-<?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($quote['estado'], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><?php echo htmlspecialchars($quote['created_at'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <form method="POST" action="/GoldenHoursEvents/views/admin/quotes.php">
                                    <input type="hidden" name="quote_id" value="<?php echo (int) $quote['id']; ?>">
                                    <select name="estado">
                                        <?php foreach (['pendiente', 'contactado', 'aprobado', 'rechazado', 'cancelado'] as $status): ?>
                                            <option value="<?php echo $status; ?>" <?php echo $quote['estado'] === $status ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($status, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-primary" type="submit">Guardar</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
