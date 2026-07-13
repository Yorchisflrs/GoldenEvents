<?php
// Inicio de sesion endurecido y compatible con HTTP local y HTTPS.
require_once __DIR__ . '/error_handler.php';

function requestUsesHttps()
{
    return (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);
}

function startSecureSession()
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');

    $current = session_get_cookie_params();
    session_set_cookie_params([
        'lifetime' => $current['lifetime'],
        'path' => $current['path'] ?: '/',
        'domain' => $current['domain'],
        'secure' => requestUsesHttps(),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    if (!session_start()) {
        throw new RuntimeException('No se pudo iniciar la sesion de forma segura.');
    }
}

startSecureSession();

