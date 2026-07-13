<?php
// Proteccion CSRF reutilizable para todas las operaciones que cambian estado.
require_once __DIR__ . '/session.php';

function csrfToken()
{
    if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="'
        . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8')
        . '">';
}

function csrfTokenIsValid($token)
{
    return is_string($token)
        && isset($_SESSION['csrf_token'])
        && is_string($_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $token);
}

function requireValidCsrfToken()
{
    $token = $_POST['csrf_token'] ?? null;

    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrfTokenIsValid($token)) {
        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo 'La solicitud no pudo validarse. Actualiza la pagina e intentalo nuevamente.';
        exit;
    }
}

function rotateCsrfToken()
{
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}

