<?php
// Manejo central de errores: registra detalles tecnicos y muestra respuestas seguras.

function registerApplicationErrorHandler()
{
    static $registered = false;

    if ($registered) {
        return;
    }

    $registered = true;
    ini_set('log_errors', '1');

    if (strtolower((string) (getenv('APP_ENV') ?: 'development')) === 'production') {
        ini_set('display_errors', '0');
    }

    set_exception_handler(function (Throwable $exception) {
        error_log(sprintf(
            "[GoldenHourEvents] %s: %s in %s:%d\n%s",
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getTraceAsString()
        ));

        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo 'Ocurrio un error inesperado. Revisa el registro del servidor para obtener detalles.';
    });
}

registerApplicationErrorHandler();

