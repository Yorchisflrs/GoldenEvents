<?php
require_once __DIR__ . '/../../includes/auth.php'; require_once __DIR__ . '/../../models/Payment.php'; require_once __DIR__ . '/../../includes/payment_proof.php';
requireLogin(); $user = currentUser();
if (!in_array($user['rol'], ['cliente', 'admin'], true)) { http_response_code(403); exit('Acceso denegado.'); }
$payment = Payment::findAccessibleById((int) ($_GET['id'] ?? 0), $user['id'], $user['rol'] === 'admin');
if (!$payment || empty($payment['comprobante'])) { http_response_code(404); exit('Comprobante no encontrado.'); }
$root = realpath(paymentProofDirectory()); $candidate = realpath(dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $payment['comprobante']));
$rootNormalized = $root === false ? '' : rtrim(strtolower(str_replace('\\', '/', $root)), '/') . '/';
$candidateNormalized = $candidate === false ? '' : strtolower(str_replace('\\', '/', $candidate));
if ($rootNormalized === '' || $candidateNormalized === '' || !str_starts_with($candidateNormalized, $rootNormalized) || !is_file($candidate)) { http_response_code(404); exit('Comprobante no encontrado.'); }
$mime = (new finfo(FILEINFO_MIME_TYPE))->file($candidate); if (!in_array($mime, appConfig('payments.proof_allowed_mimes', []), true)) { http_response_code(404); exit('Comprobante no encontrado.'); }
header('Content-Type: ' . $mime); header('Content-Length: ' . filesize($candidate)); header('X-Content-Type-Options: nosniff'); header('Content-Disposition: inline; filename="comprobante.' . pathinfo($candidate, PATHINFO_EXTENSION) . '"'); readfile($candidate); exit;
