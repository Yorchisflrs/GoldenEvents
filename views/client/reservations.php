<?php
// Ruta antigua: las solicitudes del cliente ahora se gestionan como cotizaciones.
require_once __DIR__ . '/../../includes/helpers.php';

redirect('/GoldenHoursEvents/views/client/my_quotes.php');
