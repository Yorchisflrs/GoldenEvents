<?php
// Cierre de sesion.
require_once __DIR__ . '/../../controllers/AuthController.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Metodo no permitido.';
    exit;
}

requireValidCsrfToken();
AuthController::logout();
