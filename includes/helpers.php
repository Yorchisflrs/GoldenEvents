<?php
// Funciones auxiliares generales del proyecto.
require_once __DIR__ . '/../config/app.php';

function base_url($path = '')
{
    return appUrl($path);
}

function redirect($url)
{
    header('Location: ' . $url);
    exit;
}

function sanitize($data)
{
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function generateTransactionCode()
{
    return 'TXN-' . strtoupper(bin2hex(random_bytes(8)));
}

function generateReservationCode()
{
    return 'RES-' . strtoupper(bin2hex(random_bytes(12)));
}

function isFragmentRequest()
{
    return isset($_GET['fragment']) && $_GET['fragment'] == '1';
}
