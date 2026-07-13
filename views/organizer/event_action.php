<?php
require_once __DIR__ . '/../../includes/auth.php'; require_once __DIR__ . '/../../includes/helpers.php'; require_once __DIR__ . '/../../controllers/EventController.php'; requireLogin(); requireRole('organizador');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); header('Allow: POST'); exit('Método no permitido.'); }
requireValidCsrfToken(); $result = EventController::ownerAction((int) ($_POST['event_id'] ?? 0), (int) currentUser()['id'], trim((string) ($_POST['action'] ?? ''))); redirect('/GoldenHoursEvents/views/organizer/my_events.php?message=' . urlencode($result['message']));
