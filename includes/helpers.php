<?php
// Funciones auxiliares generales del proyecto.

function base_url($path = '')
{
    return '/GoldenHoursEvents/' . ltrim($path, '/');
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
    return 'TXN-' . date('Ymd') . '-' . strtoupper(uniqid());
}

function isFragmentRequest()
{
    return isset($_GET['fragment']) && $_GET['fragment'] == '1';
}
