<?php
// Utilidades seguras para carga de imagenes.

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

    if (($file['size'] ?? 0) > 2 * 1024 * 1024) {
        return ['success' => false, 'message' => 'La imagen no debe superar 2MB.'];
    }

    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $blocked = ['php', 'js', 'svg', 'exe', 'bat', 'html'];

    if (in_array($extension, $blocked, true) || !in_array($extension, $allowed, true)) {
        return ['success' => false, 'message' => 'Formato de imagen no permitido.'];
    }

    $relativeDir = trim(str_replace('\\', '/', $targetDir), '/');
    $absoluteDir = dirname(__DIR__) . '/' . $relativeDir;

    if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true)) {
        return ['success' => false, 'message' => 'No se pudo crear la carpeta de destino.'];
    }

    $filename = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $extension;
    $absolutePath = $absoluteDir . '/' . $filename;
    $relativePath = $relativeDir . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $absolutePath)) {
        return ['success' => false, 'message' => 'No se pudo guardar la imagen.'];
    }

    return ['success' => true, 'path' => $relativePath];
}
