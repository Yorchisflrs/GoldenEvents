<?php
// Controlador para reservas.

require_once __DIR__ . '/../models/Reservation.php';

class ReservationController
{
    public static function reserve($userId, $eventId, $quantity)
    {
        $quantity = (int) $quantity;

        if ($quantity < 1) {
            return ['success' => false, 'message' => 'La cantidad debe ser al menos 1.'];
        }

        return Reservation::createWithPayment($userId, $eventId, $quantity);
    }

    public static function myReservations($userId)
    {
        return Reservation::byUser($userId);
    }

    public static function organizerReservations($organizerId)
    {
        return Reservation::byOrganizer($organizerId);
    }
}

