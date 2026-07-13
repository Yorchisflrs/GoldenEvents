<?php
// Controlador para eventos públicos y gestión del organizador.

require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';

class EventController
{
    public static function categories()
    {
        return (array) appConfig('events.categories', []);
    }

    public static function listAvailable($filters = [])
    {
        $clean = [];
        $category = trim((string) ($filters['categoria'] ?? ''));
        if ($category !== '' && in_array($category, self::categories(), true)) {
            $clean['categoria'] = $category;
        }
        $date = trim((string) ($filters['fecha'] ?? ''));
        $dateObject = DateTime::createFromFormat('!Y-m-d', $date);
        if ($date !== '' && $dateObject && $dateObject->format('Y-m-d') === $date) {
            $clean['fecha'] = $date;
        }
        $clean['ubicacion'] = mb_substr(trim((string) ($filters['ubicacion'] ?? '')), 0, 100);
        $clean['buscar'] = mb_substr(trim((string) ($filters['buscar'] ?? '')), 0, 100);
        $clean['precio'] = in_array(($filters['precio'] ?? ''), ['gratuito', 'pago'], true) ? $filters['precio'] : '';
        return Event::allAvailable($clean);
    }

    public static function getEvent($id)
    {
        return Event::findPublicById($id);
    }

    private static function validateEventData($post)
    {
        if (trim((string) ($post['titulo'] ?? '')) === '') {
            return ['success' => false, 'message' => 'El título es obligatorio.'];
        }
        if (trim((string) ($post['descripcion'] ?? '')) === '') {
            return ['success' => false, 'message' => 'La descripción es obligatoria.'];
        }
        $category = trim((string) ($post['categoria'] ?? ''));
        if (!in_array($category, self::categories(), true)) {
            return ['success' => false, 'message' => 'Selecciona una categoría válida.'];
        }
        if (trim((string) ($post['lugar'] ?? '')) === '') {
            return ['success' => false, 'message' => 'El lugar es obligatorio.'];
        }

        $start = trim((string) ($post['fecha_inicio'] ?? ''));
        $end = trim((string) ($post['fecha_fin'] ?? ''));
        $startObject = DateTime::createFromFormat('Y-m-d\TH:i', $start);
        $endObject = $end === '' ? null : DateTime::createFromFormat('Y-m-d\TH:i', $end);
        if (!$startObject || $startObject->format('Y-m-d\TH:i') !== $start || ($end !== '' && (!$endObject || $endObject->format('Y-m-d\TH:i') !== $end))) {
            return ['success' => false, 'message' => 'Ingresa fechas válidas.'];
        }
        if ($startObject->getTimestamp() <= time()) {
            return ['success' => false, 'message' => 'La fecha de inicio debe ser futura.'];
        }
        if ($endObject && $endObject <= $startObject) {
            return ['success' => false, 'message' => 'La fecha final debe ser posterior a la fecha inicial.'];
        }

        $capacity = trim((string) ($post['cupo_total'] ?? ''));
        if (!ctype_digit($capacity) || (int) $capacity <= 0) {
            return ['success' => false, 'message' => 'El cupo debe ser un número entero mayor que cero.'];
        }
        $price = normalizeMoney($post['precio'] ?? '');
        if ($price === null) {
            return ['success' => false, 'message' => 'Ingresa un precio válido, no negativo y con hasta dos decimales.'];
        }

        return ['success' => true, 'data' => [
            'titulo' => trim((string) $post['titulo']),
            'descripcion' => trim((string) $post['descripcion']),
            'categoria' => $category,
            'fecha_inicio' => $startObject->format('Y-m-d H:i:s'),
            'fecha_fin' => $endObject ? $endObject->format('Y-m-d H:i:s') : null,
            'lugar' => trim((string) $post['lugar']),
            'direccion' => trim((string) ($post['direccion'] ?? '')),
            'cupo_total' => (int) $capacity,
            'precio' => $price,
        ]];
    }

    public static function createEvent($userId, $post, $files = [])
    {
        $validation = self::validateEventData($post);
        if (!$validation['success']) {
            return $validation;
        }
        $imagePath = null;
        if (isset($files['imagen']) && ($files['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadImage($files['imagen'], 'public/uploads/events', 'event');
            if (!$upload['success']) {
                return $upload;
            }
            $imagePath = $upload['path'];
        }

        $data = $validation['data'];
        $data['organizador_id'] = (int) $userId;
        $data['imagen'] = $imagePath;
        try {
            $created = Event::create($data);
        } catch (Throwable $e) {
            error_log('[GoldenHourEvents] Error al crear evento: ' . $e->getMessage());
            $created = false;
        }
        if (!$created && $imagePath) {
            deleteUploadedImage($imagePath, 'public/uploads/events');
        }
        return [
            'success' => $created,
            'message' => $created ? 'Evento creado y enviado a aprobación.' : 'No se pudo guardar el evento.',
        ];
    }

    public static function updateEvent($eventId, $userId, $post, $files = [])
    {
        $current = Event::findOwnedById($eventId, $userId);
        if (!$current) {
            return ['success' => false, 'message' => 'No tienes permiso para editar este evento.'];
        }
        if (in_array($current['estado'], ['cancelado', 'finalizado'], true)) {
            return ['success' => false, 'message' => 'Un evento cancelado o finalizado no puede editarse.'];
        }
        $validation = self::validateEventData($post);
        if (!$validation['success']) {
            return $validation;
        }

        $newImage = null;
        if (isset($files['imagen']) && ($files['imagen']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = uploadImage($files['imagen'], 'public/uploads/events', 'event');
            if (!$upload['success']) {
                return $upload;
            }
            $newImage = $upload['path'];
        }
        $data = $validation['data'];
        $data['imagen'] = $newImage ?: $current['imagen'];
        try {
            $updated = Event::updateOwned($eventId, $userId, $data);
        } catch (Throwable $e) {
            error_log('[GoldenHourEvents] Error al actualizar evento: ' . $e->getMessage());
            $updated = false;
        }
        if (!$updated && $newImage) {
            deleteUploadedImage($newImage, 'public/uploads/events');
        } elseif ($updated && $newImage && !empty($current['imagen'])) {
            deleteUploadedImage($current['imagen'], 'public/uploads/events');
        }
        return [
            'success' => $updated,
            'message' => $updated ? 'Evento actualizado y enviado nuevamente a aprobación.' : 'No se pudo actualizar el evento.',
        ];
    }

    public static function ownerAction($eventId, $userId, $action)
    {
        $event = Event::findOwnedById($eventId, $userId);
        if (!$event) {
            return ['success' => false, 'message' => 'No tienes permiso para modificar este evento.'];
        }
        if (!in_array($action, ['cancel', 'review'], true)) {
            return ['success' => false, 'message' => 'Acción no permitida.'];
        }
        $changed = Event::ownerAction($eventId, $userId, $action);
        return [
            'success' => $changed,
            'message' => $changed ? 'Evento actualizado correctamente.' : 'La transición solicitada no es válida.',
        ];
    }

    public static function cancelEvent($eventId, $userId)
    {
        return self::ownerAction($eventId, $userId, 'cancel');
    }

    public static function myEvents($userId)
    {
        return Event::byOrganizer($userId);
    }
}
