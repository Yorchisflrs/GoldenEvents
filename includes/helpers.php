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
    $cents = moneyToCents($value);
    if ($cents === null) {
        return null;
    }
    return centsToMoney($cents);
}

function moneyToCents($value)
{
    $value = str_replace(',', '.', trim((string) $value));
    if (!preg_match('/^(?:0|[1-9]\d{0,7})(?:\.\d{1,2})?$/', $value)) {
        return null;
    }
    [$whole, $decimals] = array_pad(explode('.', $value, 2), 2, '');
    return ((int) $whole * 100) + (int) str_pad($decimals, 2, '0');
}

function centsToMoney($cents)
{
    $cents = (int) $cents;
    if ($cents < 0) {
        return null;
    }
    return intdiv($cents, 100) . '.' . str_pad((string) ($cents % 100), 2, '0', STR_PAD_LEFT);
}

function formatMoney($value)
{
    $cents = moneyToCents($value);
    return $cents === null ? '0.00' : number_format($cents / 100, 2, '.', ',');
}

function requestIpAddress()
{
    $ip = $_SERVER['REMOTE_ADDR'] ?? null;
    return is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP) ? $ip : null;
}

function generateReservationCode()
{
    return 'GHE-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(8)));
}

function safeInternalReturnUrl($value, $default = null)
{
    $value = trim((string) $value);
    if ($value === '' || preg_match('/[\r\n]/', $value)) {
        return $default;
    }
    $parts = parse_url($value);
    if ($parts === false || isset($parts['scheme']) || isset($parts['host']) || str_starts_with($value, '//')) {
        return $default;
    }
    $path = $parts['path'] ?? '';
    $base = appBasePath();
    if ($path === '' || !str_starts_with($path, $base . '/')) {
        return $default;
    }
    return $value;
}

function isFragmentRequest()
{
    return isset($_GET['fragment']) && $_GET['fragment'] == '1';
}
