<?php
// Funciones de autenticacion basadas en sesion.
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/../config/database.php';

function activeSessionUser()
{
    static $checkedSessionId = null;
    static $activeUser = null;

    if (!isset($_SESSION['user']['id'])) {
        $checkedSessionId = session_id();
        $activeUser = null;
        return null;
    }

    $sessionIdentity = session_id() . ':' . (int) $_SESSION['user']['id'];
    if ($checkedSessionId === $sessionIdentity) {
        return $activeUser;
    }

    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT u.id, u.nombre, u.email, u.rol_id, u.telefono, u.idioma, u.estado, r.nombre AS rol
                               FROM usuarios u INNER JOIN roles r ON r.id = u.rol_id
                               WHERE u.id = :id LIMIT 1");
        $stmt->execute(['id' => (int) $_SESSION['user']['id']]);
        $user = $stmt->fetch();
    } catch (Throwable $e) {
        error_log('[GoldenHourEvents] No se pudo validar la sesión activa: ' . $e->getMessage());
        $user = false;
    }

    $checkedSessionId = $sessionIdentity;
    if (!$user || $user['estado'] !== 'activo') {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
        $activeUser = null;
        return null;
    }

    unset($user['estado']);
    $_SESSION['user'] = $user;
    $activeUser = $user;
    return $activeUser;
}

function isLoggedIn()
{
    return activeSessionUser() !== null;
}

function currentUser()
{
    return activeSessionUser();
}

function requireLogin()
{
    if (!isLoggedIn()) {
        header('Location: /GoldenHoursEvents/views/auth/login.php');
        exit;
    }
}

function requireRole($roles)
{
    $user = currentUser();
    $allowedRoles = is_array($roles) ? $roles : [$roles];

    if (!$user || !isset($user['rol']) || !in_array($user['rol'], $allowedRoles, true)) {
        header('Location: /GoldenHoursEvents/views/errors/403.php');
        exit;
    }
}

function logoutUser()
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'] ?: '/',
            'domain' => $params['domain'],
            'secure' => (bool) $params['secure'],
            'httponly' => true,
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }

    header('Location: /GoldenHoursEvents/views/auth/login.php');
    exit;
}


