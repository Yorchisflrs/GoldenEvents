<?php
// Comando CLI idempotente para finalizar eventos vencidos sin mutaciones mediante GET.

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../models/Event.php';

try {
    $updated = Event::finalizeExpired();
    echo "Eventos finalizados: {$updated}" . PHP_EOL;
} catch (Throwable $e) {
    error_log('[GoldenHourEvents] No se pudieron finalizar eventos vencidos: ' . $e->getMessage());
    fwrite(STDERR, "No se pudieron finalizar los eventos. Revisa el registro técnico." . PHP_EOL);
    exit(1);
}
