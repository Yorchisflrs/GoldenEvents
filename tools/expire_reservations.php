<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__ . '/../models/Reservation.php';
echo 'Golden Hour Events - vencimiento de reservas' . PHP_EOL;
echo 'Fecha: ' . date('Y-m-d H:i:s P') . PHP_EOL;
try {
    $result = Reservation::expirePending();
    echo 'Pendientes evaluadas: ' . (int) $result['evaluated'] . PHP_EOL;
    echo 'Reservas vencidas: ' . (int) $result['expired'] . PHP_EOL;
    exit(0);
} catch (Throwable $e) {
    error_log('[GoldenHourEvents] Falló el vencimiento de reservas: ' . $e->getMessage());
    fwrite(STDERR, 'No se pudo completar el vencimiento. Revisa el registro del servidor.' . PHP_EOL);
    exit(1);
}
