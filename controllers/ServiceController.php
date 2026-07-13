<?php
// Controlador para catálogo y gestión segura de servicios.

require_once __DIR__ . '/../models/Provider.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

class ServiceController
{
    public static function listServices($categoryId = null)
    {
        return Service::allAvailable($categoryId);
    }

    public static function getService($id)
    {
        return Service::findAvailableById($id);
    }

    public static function getOwnedService($id, $userId)
    {
        return Service::findOwnedByUser($id, $userId);
    }

    private static function validateData($post)
    {
        $categoryId = (int) ($post['categoria_id'] ?? 0);
        if ($categoryId <= 0 || !Category::findActiveById($categoryId)) {
            return ['success' => false, 'message' => 'Selecciona una categoría activa.'];
        }
        if (trim((string) ($post['nombre'] ?? '')) === '' || trim((string) ($post['descripcion'] ?? '')) === '') {
            return ['success' => false, 'message' => 'El nombre y la descripción son obligatorios.'];
        }
        $price = normalizeMoney($post['precio'] ?? '');
        if ($price === null) {
            return ['success' => false, 'message' => 'Ingresa un precio válido, no negativo y con hasta dos decimales.'];
        }
        $capacityValue = trim((string) ($post['capacidad'] ?? ''));
        if ($capacityValue !== '' && (!ctype_digit($capacityValue) || (int) $capacityValue <= 0)) {
            return ['success' => false, 'message' => 'La capacidad debe ser un número entero mayor que cero.'];
        }

        return ['success' => true, 'data' => [
            'categoria_id' => $categoryId,
            'nombre' => trim((string) $post['nombre']),
            'descripcion' => trim((string) $post['descripcion']),
            'precio' => $price,
            'capacidad' => $capacityValue === '' ? null : (int) $capacityValue,
            'ubicacion' => trim((string) ($post['ubicacion'] ?? '')),
            'disponibilidad' => isset($post['disponibilidad']) ? 1 : 0,
        ]];
    }

    public static function createService($providerUserId, $post, $files)
    {
        $validation = self::validateData($post);
        if (!$validation['success']) {
            return $validation;
        }
        try {
            $provider = Provider::createIfNotExists($providerUserId, 'general', 'Proveedor registrado automáticamente.');
        } catch (Throwable $e) {
            error_log('[GoldenHourEvents] Error al preparar proveedor: ' . $e->getMessage());
            return ['success' => false, 'message' => 'No se pudo preparar el perfil del proveedor.'];
        }
        if (!$provider) {
            return ['success' => false, 'message' => 'No se pudo preparar el perfil del proveedor.'];
        }

        $imagePath = null;
        if (isset($files['imagen']) && ($files['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadImage($files['imagen'], 'public/uploads/services', 'service');
            if (!$upload['success']) {
                return $upload;
            }
            $imagePath = $upload['path'];
        }

        $data = $validation['data'];
        $data['proveedor_id'] = (int) $provider['id'];
        $data['imagen'] = $imagePath;
        try {
            $created = Service::create($data);
        } catch (Throwable $e) {
            error_log('[GoldenHourEvents] Error al crear servicio: ' . $e->getMessage());
            $created = false;
        }
        if (!$created && $imagePath) {
            deleteUploadedImage($imagePath, 'public/uploads/services');
        }
        return [
            'success' => $created,
            'message' => $created ? 'Servicio registrado y enviado a revisión.' : 'No se pudo registrar el servicio.',
        ];
    }

    public static function updateService($serviceId, $providerUserId, $post, $files)
    {
        $current = Service::findOwnedByUser($serviceId, $providerUserId);
        if (!$current) {
            return ['success' => false, 'message' => 'No tienes permiso para modificar este servicio.'];
        }
        $validation = self::validateData($post);
        if (!$validation['success']) {
            return $validation;
        }

        $newImage = null;
        if (isset($files['imagen']) && ($files['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadImage($files['imagen'], 'public/uploads/services', 'service');
            if (!$upload['success']) {
                return $upload;
            }
            $newImage = $upload['path'];
        }

        $data = $validation['data'];
        $data['imagen'] = $newImage ?: $current['imagen'];
        try {
            $updated = Service::updateOwned($serviceId, $current['proveedor_id'], $data, 'pendiente');
        } catch (Throwable $e) {
            error_log('[GoldenHourEvents] Error al actualizar servicio: ' . $e->getMessage());
            $updated = false;
        }

        if (!$updated && $newImage) {
            deleteUploadedImage($newImage, 'public/uploads/services');
        } elseif ($updated && $newImage && !empty($current['imagen'])) {
            deleteUploadedImage($current['imagen'], 'public/uploads/services');
        }

        return [
            'success' => $updated,
            'message' => $updated ? 'Servicio actualizado y enviado nuevamente a revisión.' : 'No se pudo actualizar el servicio.',
        ];
    }

    public static function ownerAction($serviceId, $providerUserId, $action, $availability = null)
    {
        $service = Service::findOwnedByUser($serviceId, $providerUserId);
        if (!$service) {
            return ['success' => false, 'message' => 'No tienes permiso para modificar este servicio.'];
        }
        $allowed = ['availability', 'disable', 'review'];
        if (!in_array($action, $allowed, true)) {
            return ['success' => false, 'message' => 'Acción no permitida.'];
        }
        $changed = Service::ownerAction($serviceId, $service['proveedor_id'], $action, $availability);
        return [
            'success' => $changed,
            'message' => $changed ? 'Servicio actualizado correctamente.' : 'El servicio ya se encontraba en ese estado o la transición no es válida.',
        ];
    }

    public static function myServices($providerUserId)
    {
        $provider = Provider::findByUser($providerUserId);
        return $provider ? Service::byProvider($provider['id']) : [];
    }
}
