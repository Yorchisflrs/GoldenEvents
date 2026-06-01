<?php
// Controlador para catalogo y gestion de servicios.

require_once __DIR__ . '/../models/Provider.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../includes/upload.php';

class ServiceController
{
    public static function listServices($categoriaId = null)
    {
        return Service::allAvailable($categoriaId);
    }

    public static function getService($id)
    {
        return Service::findById($id);
    }

    public static function createService($providerUserId, $post, $files)
    {
        $provider = Provider::createIfNotExists($providerUserId, 'general', 'Proveedor registrado automaticamente.');
        $categoriaId = (int) ($post['categoria_id'] ?? 0);
        $nombre = trim($post['nombre'] ?? '');
        $descripcion = trim($post['descripcion'] ?? '');
        $precio = (float) ($post['precio'] ?? -1);

        if ($categoriaId <= 0 || $nombre === '' || $descripcion === '' || $precio < 0) {
            return ['success' => false, 'message' => 'Completa categoria, nombre, descripcion y precio valido.'];
        }

        $imagePath = null;
        if (isset($files['imagen']) && ($files['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadImage($files['imagen'], 'public/uploads/services', 'service');
            if (!$upload['success']) {
                return $upload;
            }
            $imagePath = $upload['path'];
        }

        $created = Service::create([
            'proveedor_id' => $provider['id'],
            'categoria_id' => $categoriaId,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'precio' => $precio,
            'capacidad' => (int) ($post['capacidad'] ?? 0),
            'ubicacion' => trim($post['ubicacion'] ?? ''),
            'imagen' => $imagePath,
            'disponibilidad' => isset($post['disponibilidad']) ? 1 : 0,
            'estado' => 'activo',
        ]);

        return [
            'success' => $created,
            'message' => $created ? 'Servicio registrado correctamente.' : 'No se pudo registrar el servicio.',
        ];
    }

    public static function myServices($providerUserId)
    {
        $provider = Provider::createIfNotExists($providerUserId, 'general', 'Proveedor registrado automaticamente.');
        return Service::byProvider($provider['id']);
    }
}
