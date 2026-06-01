<?php
// Armador publico de evento personalizado.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
require_once __DIR__ . '/../../controllers/QuoteController.php';

if (!isset($_SESSION['quote_services']) || !is_array($_SESSION['quote_services'])) {
    $_SESSION['quote_services'] = [];
}

if (isset($_GET['add_service'])) {
    $serviceId = (int) $_GET['add_service'];
    $service = ServiceController::getService($serviceId);
    if ($service && !in_array($serviceId, $_SESSION['quote_services'], true)) {
        $_SESSION['quote_services'][] = $serviceId;
    }
    redirect('/GoldenHoursEvents/views/client/build_event.php');
}

if (isset($_GET['remove_service'])) {
    $serviceId = (int) ($_GET['remove_service']);
    $_SESSION['quote_services'] = array_values(array_filter(
        $_SESSION['quote_services'],
        fn($id) => (int) $id !== $serviceId
    ));
    redirect('/GoldenHoursEvents/views/client/build_event.php');
}

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_POST['selected_services'] = $_SESSION['quote_services'];
    $userId = isLoggedIn() ? currentUser()['id'] : null;
    $result = QuoteController::createQuote($_POST, $userId);

    if ($result['success']) {
        $_SESSION['quote_services'] = [];
        redirect('/GoldenHoursEvents/views/client/quote_result.php?id=' . (int) $result['quote_id']);
    }

    $message = $result['message'];
}

$selectedServices = [];
$total = 0.0;
foreach ($_SESSION['quote_services'] as $serviceId) {
    $service = ServiceController::getService((int) $serviceId);
    if ($service && $service['estado'] === 'activo' && (int) $service['disponibilidad'] === 1) {
        $selectedServices[] = $service;
        $total += (float) $service['precio'];
    }
}

$pageTitle = 'Armar mi evento';
$isFragment = isFragmentRequest();
if (!$isFragment) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/navbar.php';
    echo '<main id="app-content" class="page-transition">';
}
?>

<section class="page-bg bg-builder builder-page">
    <div class="container">
        <div class="section">
            <div class="glass-panel page-header-panel reveal">
                <h1 class="page-title">Arma tu evento personalizado</h1>
                <p class="page-subtitle">Completa tus datos, revisa los servicios seleccionados y envia una solicitud de cotizacion. El total final se calcula en el servidor.</p>
            </div>

        <?php if ($message !== ''): ?>
            <p class="alert alert-error">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <div class="builder-grid">
            <form class="form-container glass-panel reveal" method="POST" action="/GoldenHoursEvents/views/client/build_event.php">
                <section class="builder-section">
                    <h2>1. Datos del cliente</h2>
                    <div class="form-group">
                        <label for="nombre_cliente">Nombre</label>
                        <input type="text" id="nombre_cliente" name="nombre_cliente" value="<?php echo isLoggedIn() ? htmlspecialchars(currentUser()['nombre'], ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="telefono_cliente">Telefono</label>
                        <input type="text" id="telefono_cliente" name="telefono_cliente" value="<?php echo isLoggedIn() ? htmlspecialchars(currentUser()['telefono'] ?? '', ENT_QUOTES, 'UTF-8') : ''; ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="email_cliente">Email</label>
                        <input type="email" id="email_cliente" name="email_cliente" value="<?php echo isLoggedIn() ? htmlspecialchars(currentUser()['email'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    </div>
                </section>

                <section class="builder-section">
                    <h2>2. Datos del evento</h2>
                    <div class="form-group">
                        <label for="tipo_evento">Tipo de evento</label>
                        <select id="tipo_evento" name="tipo_evento" required>
                            <option value="15_anios">15 anios</option>
                            <option value="promocion">Promocion</option>
                            <option value="matrimonio">Matrimonio</option>
                            <option value="cumpleanos">Cumpleanos</option>
                            <option value="grado">Grado</option>
                            <option value="fiesta_escolar">Fiesta escolar</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="fecha_evento">Fecha tentativa</label>
                        <input type="date" id="fecha_evento" name="fecha_evento">
                    </div>
                    <div class="form-group">
                        <label for="cantidad_invitados">Cantidad de invitados</label>
                        <input type="number" id="cantidad_invitados" name="cantidad_invitados" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="mensaje">Mensaje adicional</label>
                        <textarea id="mensaje" name="mensaje"></textarea>
                    </div>
                </section>

                <section class="builder-section">
                    <h2>3. Servicios seleccionados</h2>
                    <?php if (empty($selectedServices)): ?>
                        <div class="empty-state">
                            <p>Aun no seleccionaste servicios.</p>
                            <a class="btn btn-outline js-page-link" href="/GoldenHoursEvents/views/client/services.php">+ Agregar servicios</a>
                        </div>
                    <?php else: ?>
                        <div class="selected-services">
                            <?php foreach ($selectedServices as $index => $service): ?>
                                <article class="selected-service-card stagger-item" style="transition-delay: <?php echo (int) $index * 40; ?>ms">
                                    <h3><?php echo htmlspecialchars($service['nombre'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                    <p><?php echo htmlspecialchars($service['categoria'] ?? 'Sin categoria', ENT_QUOTES, 'UTF-8'); ?></p>
                                    <div class="grid-3">
                                        <span>Precio: <strong>S/ <?php echo number_format((float) $service['precio'], 2); ?></strong></span>
                                        <label>
                                            Cantidad
                                            <input type="number" name="quantities[<?php echo (int) $service['id']; ?>]" min="1" value="1">
                                        </label>
                                        <span>Subtotal: <strong>S/ <?php echo number_format((float) $service['precio'], 2); ?></strong></span>
                                    </div>
                                    <a data-confirm="Quitar este servicio de la cotizacion?" data-no-transition href="/GoldenHoursEvents/views/client/build_event.php?remove_service=<?php echo (int) $service['id']; ?>">Quitar</a>
                                </article>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </section>

                <section class="total-box glass-panel-dark">
                    <span>4. Total estimado inicial</span>
                    <strong>S/ <?php echo number_format($total, 2); ?></strong>
                    <p>El total definitivo se valida al enviar la solicitud.</p>
                </section>

                <div class="actions">
                    <a class="btn btn-outline js-page-link" href="/GoldenHoursEvents/views/client/services.php">+ Agregar mas servicios</a>
                    <button class="btn btn-primary" type="submit">Enviar solicitud de cotizacion</button>
                </div>
            </form>

            <aside class="quote-summary glass-panel reveal">
                <h2>Resumen</h2>
                <p>Servicios seleccionados: <strong><?php echo count($selectedServices); ?></strong></p>
                <p>Costo estimado: <strong>S/ <?php echo number_format($total, 2); ?></strong></p>
                <p>Despues de enviar tu solicitud, el equipo revisara disponibilidad y detalles contigo.</p>
            </aside>
        </div>
        </div>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/../../includes/footer.php';
}
?>
