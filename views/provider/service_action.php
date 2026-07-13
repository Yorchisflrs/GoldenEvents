<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/ServiceController.php';
requireLogin();
requireRole('proveedor');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método no permitido.');
}
requireValidCsrfToken();
$result = ServiceController::ownerAction((int) ($_POST['service_id'] ?? 0), (int) currentUser()['id'], trim((string) ($_POST['action'] ?? '')), (int) ($_POST['availability'] ?? 0) === 1);
redirect('/GoldenHoursEvents/views/provider/my_services.php?message=' . urlencode($result['message']));
