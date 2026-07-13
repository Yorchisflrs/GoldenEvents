<?php
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../controllers/ReservationController.php';

requireLogin();
requireRole('cliente');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrfToken();
$user = currentUser();
$eventId = (int) ($_POST['event_id'] ?? 0);
$result = ReservationController::reserve($user['id'], $user['rol'], $eventId, $_POST['quantity'] ?? '', $_POST['operation_token'] ?? '');
if ($result['success']) { redirect(base_url('views/client/reservation_payment.php?id=' . (int) $result['reservation_id'])); }
redirect(base_url('views/client/event_detail.php?id=' . $eventId . '&error=' . rawurlencode($result['message'])));
