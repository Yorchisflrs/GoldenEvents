<?php
// Menu principal reutilizable.
require_once __DIR__ . '/auth.php';

$panelUrl = '/GoldenHoursEvents/views/client/dashboard.php';
$navUser = currentUser();
if ($navUser) {
    if ($navUser['rol'] === 'organizador') {
        $panelUrl = '/GoldenHoursEvents/views/organizer/dashboard.php';
    } elseif ($navUser['rol'] === 'proveedor') {
        $panelUrl = '/GoldenHoursEvents/views/provider/dashboard.php';
    } elseif ($navUser['rol'] === 'admin') {
        $panelUrl = '/GoldenHoursEvents/views/admin/dashboard.php';
    }
}

$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH);
function navCurrent($path, $currentPath)
{
    return $currentPath === $path ? ' aria-current="page"' : '';
}
?>
<nav class="navbar">
    <div class="navbar-container">
        <a class="navbar-brand js-page-link" href="/GoldenHoursEvents/index.php"<?php echo navCurrent('/GoldenHoursEvents/index.php', $currentPath); ?>>✨ Golden Hour Events</a>

        <button class="menu-toggle" type="button" aria-label="Abrir menu" aria-expanded="false">
            ☰
        </button>

        <ul class="navbar-links">
            <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/index.php"<?php echo navCurrent('/GoldenHoursEvents/index.php', $currentPath); ?>>🏠 Inicio</a></li>
            <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/views/client/events.php"<?php echo navCurrent('/GoldenHoursEvents/views/client/events.php', $currentPath); ?>>Eventos</a></li>
            <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/views/client/services.php"<?php echo navCurrent('/GoldenHoursEvents/views/client/services.php', $currentPath); ?>>🎉 Servicios</a></li>
            <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/views/client/build_event.php"<?php echo navCurrent('/GoldenHoursEvents/views/client/build_event.php', $currentPath); ?>>🧩 Armar evento / cotización</a></li>
            <?php if ($navUser): ?>
                <li><a class="nav-link nav-user" href="<?php echo htmlspecialchars($panelUrl, ENT_QUOTES, 'UTF-8'); ?>">📊 Panel</a></li>
                <li>
                    <form class="nav-logout-form" method="POST" action="/GoldenHoursEvents/views/auth/logout.php" data-no-transition>
                        <?php echo csrfField(); ?>
                        <button class="nav-link nav-logout-button" type="submit">🚪 Cerrar sesion</button>
                    </form>
                </li>
            <?php else: ?>
                <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/views/auth/login.php"<?php echo navCurrent('/GoldenHoursEvents/views/auth/login.php', $currentPath); ?>>🔐 Login</a></li>
                <li><a class="nav-link js-page-link" href="/GoldenHoursEvents/views/auth/register.php"<?php echo navCurrent('/GoldenHoursEvents/views/auth/register.php', $currentPath); ?>>📝 Registro</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
