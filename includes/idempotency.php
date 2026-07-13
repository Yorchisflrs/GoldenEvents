<?php
// Tokens de operación de un solo uso para evitar reservas duplicadas.

require_once __DIR__ . '/session.php';

function reservationOperationToken($eventId)
{
    $eventId = (int) $eventId;
    $now = time();
    if (!isset($_SESSION['reservation_tokens']) || !is_array($_SESSION['reservation_tokens'])) {
        $_SESSION['reservation_tokens'] = [];
    }
    foreach ($_SESSION['reservation_tokens'] as $hash => $data) {
        if (!is_array($data) || (int) ($data['created_at'] ?? 0) < $now - 3600) {
            unset($_SESSION['reservation_tokens'][$hash]);
        }
    }
    $token = bin2hex(random_bytes(32));
    $_SESSION['reservation_tokens'][hash('sha256', $token)] = [
        'event_id' => $eventId,
        'created_at' => $now,
    ];
    return $token;
}
function reservationOperationTokenIsValid($token, $eventId)
{
    if (!is_string($token) || strlen($token) !== 64 || !ctype_xdigit($token)) {
        return false;
    }
    $hash = hash('sha256', strtolower($token));
    $data = $_SESSION['reservation_tokens'][$hash] ?? null;
    return is_array($data)
        && (int) ($data['event_id'] ?? 0) === (int) $eventId
        && (int) ($data['created_at'] ?? 0) >= time() - 3600;
}

function consumeReservationOperationToken($token)
{
    if (!is_string($token)) {
        return false;
    }
    $hash = hash('sha256', strtolower($token));
    if (!isset($_SESSION['reservation_tokens'][$hash])) {
        return false;
    }
    unset($_SESSION['reservation_tokens'][$hash]);
    return true;
}
