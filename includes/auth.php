<?php
// Funciones de autenticacion basadas en sesion.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

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
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
    header('Location: /GoldenHoursEvents/views/auth/login.php');
    exit;
}


