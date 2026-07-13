<?php
// Controlador para cotizaciones de eventos personalizados.

require_once __DIR__ . '/../models/Quote.php';

class QuoteController
{
    public static function createQuote($post, $userId = null)
    {
        $selected = $post['selected_services'] ?? [];
        $quantities = $post['quantities'] ?? [];

        if (trim($post['nombre_cliente'] ?? '') === '') {
            return ['success' => false, 'message' => 'Ingresa tu nombre.'];
        }

        if (trim($post['telefono_cliente'] ?? '') === '') {
            return ['success' => false, 'message' => 'Ingresa un telefono de contacto.'];
        }

        if (trim($post['tipo_evento'] ?? '') === '') {
            return ['success' => false, 'message' => 'Selecciona el tipo de evento.'];
        }

        if ((int) ($post['cantidad_invitados'] ?? 0) <= 0) {
            return ['success' => false, 'message' => 'La cantidad de invitados debe ser mayor que cero.'];
        }

        if (!is_array($selected) || count($selected) === 0) {
            return ['success' => false, 'message' => 'Selecciona al menos un servicio.'];
        }

        $services = [];
        foreach ($selected as $serviceId) {
            $serviceId = (int) $serviceId;
            if ($serviceId > 0) {
                $services[$serviceId] = max(1, (int) ($quantities[$serviceId] ?? 1));
            }
        }

        if (empty($services)) {
            return ['success' => false, 'message' => 'Selecciona al menos un servicio valido.'];
        }

        $result = Quote::createWithDetails([
            'usuario_id' => $userId,
            'nombre_cliente' => trim($post['nombre_cliente']),
            'telefono_cliente' => trim($post['telefono_cliente']),
            'email_cliente' => trim($post['email_cliente'] ?? ''),
            'tipo_evento' => trim($post['tipo_evento']),
            'fecha_evento' => trim($post['fecha_evento'] ?? ''),
            'cantidad_invitados' => (int) $post['cantidad_invitados'],
            'mensaje' => trim($post['mensaje'] ?? ''),
        ], $services);

        if (!$result['success']) {
            return $result;
        }

        return [
            'success' => true,
            'message' => 'Cotizacion registrada correctamente.',
            'quote_id' => $result['quote_id'],
            'total' => $result['total'],
            'public_token' => $result['public_token'] ?? null,
        ];
    }

    public static function getQuote($id, $userId, $isAdmin = false)
    {
        $quote = Quote::findAccessibleById($id, $userId, $isAdmin);

        return [
            'quote' => $quote,
            'details' => $quote ? Quote::details($id) : [],
        ];
    }

    public static function getPublicQuote($token)
    {
        $quote = Quote::findByPublicToken($token);

        return [
            'quote' => $quote,
            'details' => $quote ? Quote::details((int) $quote['id']) : [],
        ];
    }

    public static function myQuotes($userId)
    {
        return Quote::byUser($userId);
    }

    public static function adminQuotes()
    {
        return Quote::all();
    }

    public static function changeStatus($id, $estado)
    {
        return Quote::updateStatus($id, $estado);
    }
}
