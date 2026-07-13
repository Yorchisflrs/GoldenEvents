<?php
require_once __DIR__ . '/../../includes/auth.php'; require_once __DIR__ . '/../../controllers/ReservationController.php';
requireLogin(); requireRole('cliente');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrfToken(); $id = (int) ($_POST['id'] ?? 0); $user = currentUser();
$result = ReservationController::cancelOwn($id, $user['id']);
$key = $result['success'] ? 'success' : 'error'; redirect(base_url('views/client/reservation_detail.php?id=' . $id . '&' . $key . '=' . rawurlencode($result['message'])));
