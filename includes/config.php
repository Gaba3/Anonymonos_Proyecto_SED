<?php
// Ferretería El Tornillo — configuración general

define('DB_HOST', '127.0.0.1');
define('DB_PORT', 3306);
define('DB_NAME', 'ferreteria');
define('DB_USER', 'ferreteria_app');
define('DB_PASS', 'App.Tornillo.2026');

// ---------------------------------------------------------------------
// Datos del sitio
// ---------------------------------------------------------------------
define('APP_NOMBRE', 'Ferretería El Tornillo');
define('APP_LEMA', 'Herramienta, material eléctrico y ferretería en general');

define('BASE_URL', '/');

define('RUTA_UPLOADS', __DIR__ . '/../public/uploads');
define('URL_UPLOADS', BASE_URL . 'uploads/');

define('IMG_SIN_FOTO', BASE_URL . 'assets/img/sin-imagen.svg');

// ---------------------------------------------------------------------
// Visibilidad de los errores — DECISIÓN INTENCIONAL
// ---------------------------------------------------------------------
define('MOSTRAR_ERRORES', true);

if (MOSTRAR_ERRORES) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

date_default_timezone_set('America/El_Salvador');

function e(?string $texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $ruta = ''): string
{
    return BASE_URL . ltrim($ruta, '/');
}


function dinero($monto): string
{
    return '$' . number_format((float) $monto, 2, '.', ',');
}

function imagen_producto(?string $archivo): string
{
    if ($archivo === null || trim($archivo) === '') {
        return IMG_SIN_FOTO;
    }

    return URL_UPLOADS . rawurlencode($archivo);
}

function fecha_legible(?string $fecha): string
{
    if (empty($fecha)) {
        return '';
    }

    $ts = strtotime($fecha);

    return $ts === false ? $fecha : date('d/m/Y H:i', $ts);
}
