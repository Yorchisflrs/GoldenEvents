<?php
// Solicitudes y consultas del flujo de reservas.

require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../includes/idempotency.php';

class ReservationController
{
    public static function operationToken($eventId)
    {
        return reservationOperationToken($eventId);
    }

    public static function reserve($userId, $role, $eventId, $quantity, $operationToken)
    {
        if ($role !== 'cliente') {
            return ['success' => false, 'message' => 'Solo los clientes pueden reservar entradas.'];
        }
        $eventId = (int) $eventId;
        $quantityValue = trim((string) $quantity);
        $maxTickets = (int) appConfig('reservations.max_tickets');
        if (!ctype_digit($quantityValue) || (int) $quantityValue < 1 || (int) $quantityValue > $maxTickets) {
            return ['success' => false, 'message' => "La cantidad debe estar entre 1 y {$maxTickets}." ];
        }
        if (!reservationOperationTokenIsValid($operationToken, $eventId)) {
            return ['success' => false, 'message' => 'El formulario ya fue utilizado o expiró. Actualiza el detalle del evento.'];
        }
        $result = Reservation::createPending($userId, $eventId, (int) $quantityValue, hash('sha256', strtolower($operationToken)));
        if ($result['success']) {
            consumeReservationOperationToken($operationToken);
        }
        return $result;
    }

    public static function myReservations($userId) { return Reservation::byUser($userId); }
    public static function getOwnedReservation($reservationId, $userId) { return Reservation::findOwnedById($reservationId, $userId); }

    public static function effectiveStatus($reservation)
    {
        if (($reservation['estado'] ?? '') === 'pendiente_pago' && empty($reservation['plazo_vigente'])) {
            return 'vencida';
        }
        return $reservation['estado'] ?? '';
    }

    public static function cancelOwn($reservationId, $userId) { return Reservation::cancelByClient($reservationId, $userId); }
    public static function organizerReservations($organizerId) { return Reservation::byOrganizer($organizerId); }
    public static function organizerStats($organizerId) { return Reservation::organizerEventStats($organizerId); }
    public static function adminReservations($filters = []) { return Reservation::all($filters); }
    public static function adminCancel($reservationId, $adminId, $reason) { return Reservation::adminCancel($reservationId, $adminId, $reason); }
}
