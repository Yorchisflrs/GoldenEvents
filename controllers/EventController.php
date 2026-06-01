<?php
// Controlador para eventos.

require_once __DIR__ . '/../models/Event.php';

class EventController
{
    public static function listAvailable()
    {
        return Event::allAvailable();
    }

    public static function getEvent($id)
    {
        return Event::findAvailableById($id);
    }

    public static function createEvent($userId, $post)
    {
        $validation = self::validateEventData($post);
        if (!$validation['success']) {
            return $validation;
        }

        $data = self::normalizeEventData($post);
        $data['organizador_id'] = $userId;

        return [
            'success' => Event::create($data),
            'message' => 'Evento guardado correctamente.',
        ];
    }

    public static function updateEvent($eventId, $userId, $post)
    {
        $validation = self::validateEventData($post);
        if (!$validation['success']) {
            return $validation;
        }

        $updated = Event::update($eventId, $userId, self::normalizeEventData($post));

        return [
            'success' => $updated,
            'message' => $updated ? 'Evento actualizado correctamente.' : 'No se pudo actualizar el evento.',
        ];
    }

    public static function cancelEvent($eventId, $userId)
    {
        $cancelled = Event::deleteByOrganizer($eventId, $userId);

        return [
            'success' => $cancelled,
            'message' => $cancelled ? 'Evento cancelado correctamente.' : 'No se pudo cancelar el evento.',
        ];
    }

    public static function myEvents($userId)
    {
        return Event::byOrganizer($userId);
    }

    private static function validateEventData($post)
    {
        if (trim($post['titulo'] ?? '') === '') {
            return ['success' => false, 'message' => 'El titulo es obligatorio.'];
        }

        if (trim($post['descripcion'] ?? '') === '') {
            return ['success' => false, 'message' => 'La descripcion es obligatoria.'];
        }

        if (trim($post['fecha_inicio'] ?? '') === '') {
            return ['success' => false, 'message' => 'La fecha de inicio es obligatoria.'];
        }

        if (trim($post['lugar'] ?? '') === '') {
            return ['success' => false, 'message' => 'El lugar es obligatorio.'];
        }

        if ((int) ($post['cupo_total'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'El cupo debe ser mayor que cero.'];
        }

        if ((float) ($post['precio'] ?? -1) < 0) {
            return ['success' => false, 'message' => 'El precio no puede ser negativo.'];
        }

        return ['success' => true, 'message' => 'Datos validos.'];
    }

    private static function normalizeEventData($post)
    {
        return [
            'titulo' => trim($post['titulo']),
            'descripcion' => trim($post['descripcion']),
            'categoria' => trim($post['categoria'] ?? ''),
            'fecha_inicio' => str_replace('T', ' ', trim($post['fecha_inicio'])),
            'fecha_fin' => trim($post['fecha_fin'] ?? '') !== '' ? str_replace('T', ' ', trim($post['fecha_fin'])) : null,
            'lugar' => trim($post['lugar']),
            'direccion' => trim($post['direccion'] ?? ''),
            'cupo_total' => (int) $post['cupo_total'],
            'precio' => (float) $post['precio'],
            'estado' => $post['estado'] ?? 'activo',
        ];
    }
}
