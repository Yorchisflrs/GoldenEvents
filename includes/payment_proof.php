<?php
// Validación y almacenamiento privado de comprobantes de pago.

require_once __DIR__ . '/../config/app.php';

function paymentProofDirectory()
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'payment_proofs';
}

function savePrivatePaymentProof($file)
{
    if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Selecciona un comprobante válido.'];
    }
    $size = (int) ($file['size'] ?? 0);
    $maxBytes = (int) appConfig('payments.proof_max_bytes');
    if ($size <= 0 || $size > $maxBytes) {
        return ['success' => false, 'message' => 'El comprobante supera el tamaño máximo permitido.'];
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    $testingFile = PHP_SAPI === 'cli' && defined('GHE_TESTING') && GHE_TESTING === true && is_file($tmp);
    if ($tmp === '' || (!is_uploaded_file($tmp) && !$testingFile)) {
        return ['success' => false, 'message' => 'El comprobante recibido no es una carga válida.'];
    }
    $providedExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = (array) appConfig('payments.proof_allowed_extensions', []);
    if (!in_array($providedExtension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'El formato del comprobante no está permitido.'];
    }
    $mimeMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $configuredMimes = (array) appConfig('payments.proof_allowed_mimes', []);
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($tmp);
    if (!isset($mimeMap[$mime]) || !in_array($mime, $configuredMimes, true) || @getimagesize($tmp) === false) {
        return ['success' => false, 'message' => 'El contenido no corresponde a una imagen permitida.'];
    }
    $directory = paymentProofDirectory();
    if (!is_dir($directory) && !mkdir($directory, 0750, true)) {
        error_log('[GoldenHourEvents] No se pudo crear el directorio privado de comprobantes.');
        return ['success' => false, 'message' => 'No se pudo guardar el comprobante.'];
    }
    do {
        $filename = 'proof_' . bin2hex(random_bytes(24)) . '.' . $mimeMap[$mime];
        $absolute = $directory . DIRECTORY_SEPARATOR . $filename;
    } while (file_exists($absolute));
    $moved = $testingFile ? copy($tmp, $absolute) : move_uploaded_file($tmp, $absolute);
    if (!$moved) {
        error_log('[GoldenHourEvents] No se pudo mover un comprobante validado.');
        return ['success' => false, 'message' => 'No se pudo guardar el comprobante.'];
    }
    return [
        'success' => true,
        'path' => 'storage/uploads/payment_proofs/' . $filename,
        'mime' => $mime,
        'absolute_path' => $absolute,
    ];
}

function deletePrivatePaymentProof($relativePath)
{
    $root = realpath(paymentProofDirectory());
    if ($root === false) {
        return false;
    }
    $candidate = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, (string) $relativePath));
    $rootPrefix = rtrim(strtolower(str_replace('\\', '/', $root)), '/') . '/';
    $candidatePath = $candidate === false ? '' : strtolower(str_replace('\\', '/', $candidate));
    if ($candidatePath === '' || !str_starts_with($candidatePath, $rootPrefix) || !is_file($candidate)) {
        return false;
    }
    return unlink($candidate);
}
