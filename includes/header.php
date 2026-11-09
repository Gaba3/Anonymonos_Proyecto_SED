<?php
// Ferretería El Tornillo — cabecera común

require_once __DIR__ . '/auth.php';

$titulo_pagina = $titulo_pagina ?? '';
$usuario       = usuario_actual();
$rol           = $usuario['rol'] ?? null;

$pagina_actual = basename($_SERVER['SCRIPT_NAME'] ?? '');

function clase_activa(string $archivo): string
{
    global $pagina_actual;

    return $pagina_actual === $archivo ? ' active' : '';
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $titulo_pagina !== '' ? e($titulo_pagina) . ' · ' : '' ?><?= e(APP_NOMBRE) ?></title>
    <link rel="stylesheet" href="<?= url('assets/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= url('assets/css/estilos.css') ?>">
    <link rel="icon" href="<?= url('assets/img/logo.svg') ?>" type="image/svg+xml">
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar navbar-expand-lg navbar-dark bg-oscuro shadow-sm">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
            <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="32" height="32">
            <span class="fw-bold">El Tornillo</span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#menuPrincipal" aria-controls="menuPrincipal"
                aria-expanded="false" aria-label="Mostrar o esconder el menú">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="menuPrincipal">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link<?= clase_activa('index.php') ?>" href="<?= url('index.php') ?>">Catálogo</a>
                </li>

                <?php if ($rol === 'comprador'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('catalogo.php') ?>" href="<?= url('comprador/catalogo.php') ?>">Comprar</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('carrito.php') ?>" href="<?= url('comprador/carrito.php') ?>">Carrito</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('historial.php') ?>" href="<?= url('comprador/historial.php') ?>">Mis facturas</a>
                    </li>

                <?php elseif ($rol === 'vendedor'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('mis-productos.php') ?>" href="<?= url('vendedor/mis-productos.php') ?>">Mis productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('cargar-producto.php') ?>" href="<?= url('vendedor/cargar-producto.php') ?>">Cargar producto</a>
                    </li>

                <?php elseif ($rol === 'admin'): ?>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('index.php') ?>" href="<?= url('admin/index.php') ?>">Panel</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('productos.php') ?>" href="<?= url('admin/productos.php') ?>">Productos</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link<?= clase_activa('usuarios.php') ?>" href="<?= url('admin/usuarios.php') ?>">Usuarios</a>
                    </li>
                <?php endif; ?>
            </ul>

            <ul class="navbar-nav mb-2 mb-lg-0">
                <?php if ($usuario !== null): ?>
                    <li class="nav-item d-flex align-items-center me-lg-2">
                        <span class="navbar-text small">
                            <?= e($usuario['nombre']) ?>
                            <span class="badge text-bg-naranja ms-1"><?= e($usuario['rol']) ?></span>
                        </span>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-outline-light" href="<?= url('logout.php') ?>">Cerrar sesión</a>
                    </li>
                <?php else: ?>
                    <li class="nav-item me-lg-2">
                        <a class="btn btn-sm btn-outline-light" href="<?= url('login.php') ?>">Iniciar sesión</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-sm btn-naranja" href="<?= url('register.php') ?>">Registrarse</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="container flex-grow-1 py-4">
