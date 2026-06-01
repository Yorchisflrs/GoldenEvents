<?php
// Registro publico de clientes, organizadores y proveedores.
require_once __DIR__ . '/../../controllers/AuthController.php';
require_once __DIR__ . '/../../includes/helpers.php';

$message = '';
$messageType = 'error';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = AuthController::register(
        trim($_POST['nombre'] ?? ''),
        trim($_POST['email'] ?? ''),
        $_POST['password'] ?? '',
        trim($_POST['telefono'] ?? ''),
        trim($_POST['rol'] ?? '')
    );

    if ($result['success']) {
        redirect('/GoldenHoursEvents/views/auth/login.php?success=' . urlencode($result['message']));
    }

    $message = $result['message'];
}

$pageTitle = 'Registro de usuario';
$isFragment = isFragmentRequest();
if (!$isFragment) {
    require_once __DIR__ . '/../../includes/header.php';
    require_once __DIR__ . '/../../includes/navbar.php';
    echo '<main id="app-content" class="page-transition">';
}
?>

<section class="page-bg bg-auth auth-page">
    <div class="container">
    <section class="auth-card glass-panel">
        <h1 class="page-title">Crea tu cuenta</h1>
        <p class="page-subtitle">Registrate como cliente, organizador o proveedor para gestionar tus solicitudes y servicios.</p>

        <?php if ($message !== ''): ?>
            <p class="alert alert-error">
                <?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        <?php endif; ?>

        <form class="form-container" method="POST" action="/GoldenHoursEvents/views/auth/register.php">
            <div class="form-group">
                <label for="nombre">Nombre</label>
                <input type="text" id="nombre" name="nombre" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="telefono">Telefono</label>
                <input type="tel" id="telefono" name="telefono">
            </div>

            <div class="form-group">
                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" minlength="6" required>
            </div>

            <div class="form-group">
                <label for="rol">Rol</label>
                <select id="rol" name="rol" required>
                    <option value="cliente">Cliente</option>
                    <option value="organizador">Organizador</option>
                    <option value="proveedor">Proveedor</option>
                </select>
            </div>

            <button class="btn btn-primary" type="submit">📝 Registrarse</button>
        </form>

        <p>Ya tienes cuenta? <a class="js-page-link" href="/GoldenHoursEvents/views/auth/login.php">Inicia sesion</a></p>
    </section>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/../../includes/footer.php';
}
?>
