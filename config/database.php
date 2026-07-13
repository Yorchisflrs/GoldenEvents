<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/../includes/error_handler.php';

/**
 * Conexión PDO a la base de datos Golden Hour Events.
 * Base de datos: golden_hour_events
 * Servidor local: XAMPP / MySQL
 */

$host = appConfig('database.host');
$dbname = appConfig('database.name');
$username = appConfig('database.user');
$password = appConfig('database.pass');
$charset = appConfig('database.charset', 'utf8mb4');

$dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    error_log('[GoldenHourEvents][Database] Error de conexion: ' . $e->getMessage());
    http_response_code(500);
    die('No se pudo conectar con la base de datos. Revisa el registro del servidor.');
}

