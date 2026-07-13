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

function normalizeMoney($value)
{
    $value = str_replace(',', '.', trim((string) $value));
    if (!preg_match('/^(?:0|[1-9]\d{0,7})(?:\.\d{1,2})?$/', $value)) {
        return null;
    }

    [$whole, $decimals] = array_pad(explode('.', $value, 2), 2, '');
    return $whole . '.' . str_pad($decimals, 2, '0');
}

function requestIpAddress()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function generateReservationCode()
{
    return 'RES-' . strtoupper(bin2hex(random_bytes(12)));
}

function isFragmentRequest()
{
    return isset($_GET['fragment']) && $_GET['fragment'] == '1';
}
