<?php
// Utilidades seguras para carga de imagenes.
require_once __DIR__ . '/../config/app.php';

function uploadImage($file, $targetDir, $prefix = 'img')
{
    if (!isset($file) || !is_array($file)) {
        return ['success' => false, 'message' => 'No se recibio ningun archivo.'];
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['success' => false, 'message' => 'No se selecciono ninguna imagen.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'No se pudo subir la imagen.'];
    }

    $size = (int) ($file['size'] ?? 0);
    $maxBytes = (int) appConfig('uploads.max_bytes', 2 * 1024 * 1024);
    if ($size <= 0 || $size > $maxBytes) {
        return ['success' => false, 'message' => 'La imagen supera el tamano maximo permitido.'];
    }

    $temporaryPath = $file['tmp_name'] ?? '';
    if ($temporaryPath === '' || !is_uploaded_file($temporaryPath)) {
        return ['success' => false, 'message' => 'El archivo recibido no es una carga valida.'];
    }

    $providedExtension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
    $allowedExtensions = array_map('strtolower', (array) appConfig('uploads.allowed_extensions', []));
    if (!in_array($providedExtension, $allowedExtensions, true)) {
        return ['success' => false, 'message' => 'Formato de imagen no permitido.'];
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($temporaryPath);
    $configuredMimes = (array) appConfig('uploads.allowed_mimes', []);
    $knownMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $allowedMimes = array_intersect_key($knownMimes, array_flip($configuredMimes));

    if (!isset($allowedMimes[$mime]) || @getimagesize($temporaryPath) === false) {
        return ['success' => false, 'message' => 'El contenido del archivo no es una imagen permitida.'];
    }

    $relativeDir = trim(str_replace('\\', '/', $targetDir), '/');
    if ($relativeDir === '' || str_contains($relativeDir, '..')) {
        return ['success' => false, 'message' => 'La carpeta de destino no es valida.'];
    }

    $projectRoot = realpath(dirname(__DIR__));
    if ($projectRoot === false) {
        error_log('[GoldenHourEvents] No se pudo resolver la raiz del proyecto para una carga.');
        return ['success' => false, 'message' => 'No se pudo preparar la carga de la imagen.'];
    }

    $absoluteDir = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativeDir);

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        error_log('[GoldenHourEvents] No se pudo crear el directorio de cargas: ' . $absoluteDir);
        return ['success' => false, 'message' => 'No se pudo crear la carpeta de destino.'];
    }

    $resolvedDir = realpath($absoluteDir);
    $rootPrefix = rtrim(strtolower(str_replace('\\', '/', $projectRoot)), '/') . '/';
    $resolvedPrefix = $resolvedDir === false
        ? ''
        : rtrim(strtolower(str_replace('\\', '/', $resolvedDir)), '/') . '/';

    if ($resolvedPrefix === '' || !str_starts_with($resolvedPrefix, $rootPrefix)) {
        error_log('[GoldenHourEvents] Se rechazo una carpeta de carga fuera del proyecto.');
        return ['success' => false, 'message' => 'La carpeta de destino no es valida.'];
    }

    $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) ?: 'img';
    $extension = $allowedMimes[$mime];

    do {
        $filename = $safePrefix . '_' . bin2hex(random_bytes(16)) . '.' . $extension;
        $absolutePath = $resolvedDir . DIRECTORY_SEPARATOR . $filename;
    } while (file_exists($absolutePath));

    $relativePath = $relativeDir . '/' . $filename;

    if (!move_uploaded_file($temporaryPath, $absolutePath)) {
        error_log('[GoldenHourEvents] move_uploaded_file fallo para una imagen validada.');
        return ['success' => false, 'message' => 'No se pudo guardar la imagen.'];
    }

    return ['success' => true, 'path' => $relativePath];
}
