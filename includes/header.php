<?php
// Encabezado HTML reutilizable para todas las vistas.
if (!isset($pageTitle)) {
    $pageTitle = 'Golden Hour Events';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Golden Hour Events: plataforma para planificar eventos personalizados con locales, decoracion, DJ, catering, fotografia y mas servicios.">
    <meta name="theme-color" content="#ED8F03">
    <meta property="og:title" content="Golden Hour Events">
    <meta property="og:description" content="Arma tu evento ideal en un solo lugar.">
    <meta property="og:type" content="website">
    <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="stylesheet" href="/GoldenHoursEvents/public/css/style.css">
</head>
<body class="app-body">
<div id="pageLoader" class="page-loader" aria-hidden="true"></div>
