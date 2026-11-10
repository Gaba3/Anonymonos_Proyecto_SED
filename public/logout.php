<?php
/**
 * Ferretería El Tornillo — cerrar sesión
 *
 * Destruye la sesión y devuelve al catálogo público. La barra de
 * navegación de includes/header.php enlaza aquí cuando hay una sesión
 * iniciada, así que sin este archivo el botón «Cerrar sesión» daría 404
 * y no se podría cambiar de rol durante la demostración.
 */

require_once __DIR__ . '/../includes/auth.php';

cerrar_sesion();

redirigir(url('index.php'));
