<?php
// Funciones de autenticacion basadas en sesion.
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/csrf.php';

function isLoggedIn()
{
    return isset($_SESSION['user']);
}

function currentUser()
{
    return $_SESSION['user'] ?? null;
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


