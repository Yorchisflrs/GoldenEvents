<?php
// Creacion de servicios del proveedor.
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
requireLogin();
requireRole('proveedor');

$message = '';
$messageType = 'error';
$categories = Category::allActive();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $result = ServiceController::createService(currentUser()['id'], $_POST, $_FILES);
    $message = $result['message'];
    $messageType = $result['success'] ? 'success' : 'error';
}

$pageTitle = 'Crear servicio';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="container">
    <section class="section">
        <h1 class="page-title">Crear servicio</h1>
        <p class="page-subtitle">Agrega informacion clara para que los clientes puedan incluir tu servicio en su cotizacion.</p>

        <?php if ($message !== ''): ?>
            <p class="alert alert-<?php echo $messageType === 'success' ? 'success' : 'error'; ?>">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form class="form-container" method="POST" action="/GoldenHoursEvents/views/provider/create_service.php" enctype="multipart/form-data">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="categoria_id">Categoria de servicio</label>
                <select id="categoria_id" name="categoria_id" required>
                    <option value="">Selecciona una categoria</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?php echo (int) $category['id']; ?>">
                            <?php echo htmlspecialchars($category['nombre'], ENT_QUOTES, 'UTF-8'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="nombre">Nombre del servicio</label>
                    <input type="text" id="nombre" name="nombre" required>
                </div>
                <div class="form-group">
                    <label for="precio">Precio</label>
                    <input type="number" id="precio" name="precio" min="0" step="0.01" required>
                </div>
            </div>

            <div class="form-group">
                <label for="descripcion">Descripcion</label>
                <textarea id="descripcion" name="descripcion" required></textarea>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label for="capacidad">Capacidad</label>
                    <input type="number" id="capacidad" name="capacidad" min="0">
                </div>
                <div class="form-group">
                    <label for="ubicacion">Ubicacion</label>
                    <input type="text" id="ubicacion" name="ubicacion">
                </div>
            </div>

            <div class="form-group">
                <label for="imagen">Imagen del servicio</label>
                <input type="file" id="imagen" name="imagen" accept=".jpg,.jpeg,.png,.webp">
            </div>

            <label>
                <input type="checkbox" name="disponibilidad" value="1" checked>
                Disponible para cotizacion
            </label>

            <button class="btn btn-primary" type="submit">Guardar servicio</button>
        </form>
    </section>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
