<?php
// Configuracion central con variables de entorno y valores seguros para XAMPP local.

function environmentValue($key, $default = null)
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function environmentList($key, $defaults)
{
    $value = trim((string) environmentValue($key, ''));
    if ($value === '') {
        return $defaults;
    }

    $items = array_values(array_filter(array_map('trim', explode(',', $value))));
    return $items ?: $defaults;
}

function environmentBool($key, $default = false)
{
    $value = environmentValue($key, $default ? 'true' : 'false');
    $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    return $parsed === null ? (bool) $default : $parsed;
}

$configuredTimezone = (string) environmentValue('APP_TIMEZONE', 'America/Lima');
if (!@date_default_timezone_set($configuredTimezone)) {
    date_default_timezone_set('America/Lima');
}

$basePath = '/' . trim((string) environmentValue('APP_BASE_PATH', '/GoldenHoursEvents'), '/');

$applicationConfig = [
    'environment' => strtolower((string) environmentValue('APP_ENV', 'development')),
    'timezone' => date_default_timezone_get(),
    'base_path' => $basePath === '/' ? '' : $basePath,
    'database' => [
        'host' => (string) environmentValue('DB_HOST', 'localhost'),
        'name' => (string) environmentValue('DB_NAME', 'golden_hour_events'),
        'user' => (string) environmentValue('DB_USER', 'root'),
        'pass' => (string) environmentValue('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'payments' => [
        'yape_enabled' => environmentBool('YAPE_ENABLED', true),
        'yape_display_name' => (string) environmentValue('YAPE_DISPLAY_NAME', 'Jorge Luis Flores Turpo'),
        'yape_phone' => trim((string) environmentValue('YAPE_PHONE', '')),
        'yape_qr_image' => trim((string) environmentValue('YAPE_QR_IMAGE', 'public/img/payments/yape-qr-jorge-flores.jpg')),
        'yape_max_amount' => trim((string) environmentValue('YAPE_MAX_AMOUNT', '500.00')),
        'proof_max_bytes' => max(1, (int) environmentValue('PAYMENT_PROOF_MAX_SIZE', 2097152)),
        'proof_allowed_mimes' => environmentList('PAYMENT_PROOF_ALLOWED_MIME', ['image/jpeg', 'image/png', 'image/webp']),
        'proof_allowed_extensions' => ['jpg', 'jpeg', 'png', 'webp'],
    ],
    'reservations' => [
        'expiration_minutes' => max(1, (int) environmentValue('RESERVATION_EXPIRATION_MINUTES', 30)),
        'max_tickets' => max(1, (int) environmentValue('MAX_TICKETS_PER_RESERVATION', 10)),
    ],
    'uploads' => [
        'max_bytes' => max(1, (int) environmentValue('UPLOAD_MAX_BYTES', 2 * 1024 * 1024)),
        'allowed_extensions' => environmentList('UPLOAD_ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']),
        'allowed_mimes' => environmentList('UPLOAD_ALLOWED_MIMES', ['image/jpeg', 'image/png', 'image/webp']),
    ],
    'events' => [
        'categories' => [
            'Boda',
            'Cumpleaños',
            'Concierto',
            'Conferencia',
            'Corporativo',
            'Fiesta escolar',
            'Graduación',
            'Matrimonio',
            'Otro',
        ],
    ],
];

function appConfig($key = null, $default = null)
{
    global $applicationConfig;

    if ($key === null || $key === '') {
        return $applicationConfig;
    }

    $value = $applicationConfig;
    foreach (explode('.', $key) as $segment) {
        if (!is_array($value) || !array_key_exists($segment, $value)) {
            return $default;
        }
        $value = $value[$segment];
    }

    return $value;
}

function appBasePath()
{
    return (string) appConfig('base_path', '/GoldenHoursEvents');
}

function appUrl($path = '')
{
    $path = ltrim((string) $path, '/');
    return appBasePath() . ($path === '' ? '/' : '/' . $path);
}
