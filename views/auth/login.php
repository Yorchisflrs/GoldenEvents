<?php
// Login real con usuarios guardados en MySQL.
require_once __DIR__ . '/../../controllers/AuthController.php';

if (isLoggedIn()) {
    AuthController::redirectByRole();
}

$error = '';
$success = $_GET['success'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCsrfToken();

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (AuthController::login($email, $password)) {
        AuthController::redirectByRole();
    }

    $error = 'Credenciales incorrectas o usuario inactivo.';
}

$pageTitle = 'Iniciar sesion';
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
        <h1 class="page-title">Iniciar sesion</h1>
        <p class="page-subtitle">Accede a tu cuenta para gestionar tus cotizaciones y servicios.</p>

        <?php if ($success !== ''): ?>
            <p class="alert alert-success"><?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></p>
        <?php endif; ?>

        <form class="form-container" method="POST" action="/GoldenHoursEvents/views/auth/login.php">
            <?php echo csrfField(); ?>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="password">Contrasena</label>
                <input type="password" id="password" name="password" required>
            </div>

            <button class="btn btn-primary" type="submit">🔐 Ingresar</button>
        </form>

        <p>No tienes cuenta? <a class="js-page-link" href="/GoldenHoursEvents/views/auth/register.php">Registrate aqui</a></p>
    </section>
    </div>
</section>

<?php
if (!$isFragment) {
    echo '</main>';
    require_once __DIR__ . '/../../includes/footer.php';
}
?>
