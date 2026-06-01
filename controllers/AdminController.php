<?php
// Controlador para administracion.

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Event.php';
require_once __DIR__ . '/../models/Reservation.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Service.php';
require_once __DIR__ . '/../models/Quote.php';

class AdminController
{
    public static function dashboardStats()
    {
        return [
            'usuarios' => User::countAll(),
            'eventos' => Event::countAll(),
            'reservas' => Reservation::countAll(),
            'ingresos' => Payment::totalRevenue(),
            'servicios' => Service::countAll(),
            'cotizaciones' => Quote::countAll(),
            'cotizaciones_pendientes' => Quote::countPending(),
        ];
    }

    public static function getUsers()
    {
        return User::all();
    }

    public static function getEvents()
    {
        return Event::all();
    }

    public static function getReservations()
    {
        return Reservation::all();
    }

    public static function getPayments()
    {
        return Payment::all();
    }

    public static function getServices()
    {
        return Service::allForAdmin();
    }

    public static function getQuotes()
    {
        return Quote::all();
    }
}
