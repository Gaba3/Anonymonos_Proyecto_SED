<?php
// Ferretería El Tornillo — sesión y control de acceso por rol

require_once __DIR__ . '/db.php';

// ---------------------------------------------------------------------
// Arranque de la sesión
// ---------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'path'     => '/',
    ]);
    session_start();
}

const ROLES_VALIDOS = ['admin', 'vendedor', 'comprador'];

function esta_autenticado(): bool
{
    return isset($_SESSION['usuario_id'], $_SESSION['rol']);
}

function usuario_actual(): ?array
{
    if (!esta_autenticado()) {
        return null;
    }

    return [
        'id'     => (int) $_SESSION['usuario_id'],
        'nombre' => (string) ($_SESSION['nombre'] ?? ''),
        'rol'    => (string) $_SESSION['rol'],
    ];
}

function usuario_id(): ?int
{
    return esta_autenticado() ? (int) $_SESSION['usuario_id'] : null;
}

function rol_actual(): ?string
{
    return esta_autenticado() ? (string) $_SESSION['rol'] : null;
}

function iniciar_sesion_usuario(int $id, string $nombre, string $rol): void
{
    session_regenerate_id(true);

    $_SESSION['usuario_id'] = $id;
    $_SESSION['nombre']     = $nombre;
    $_SESSION['rol']        = $rol;
}

function cerrar_sesion(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }

    session_destroy();
}

function inicio_por_rol(string $rol): string
{
    switch ($rol) {
        case 'admin':
            return url('admin/panel.php');
        case 'vendedor':
            return url('vendedor/mis-productos.php');
        case 'comprador':
            return url('comprador/catalogo.php');
        default:
            return url('index.php');
    }
}

function destino_tras_login(string $rol): string
{
    $destino = inicio_por_rol($rol);
    $archivo = __DIR__ . '/../public/' . ltrim(substr($destino, strlen(BASE_URL)), '/');

    return is_file($archivo) ? $destino : url('index.php');
}

function redirigir(string $destino): void
{
    header('Location: ' . $destino);
    exit;
}

function requerir_sesion(): void
{
    if (!esta_autenticado()) {
        redirigir(url('login.php?error=sesion'));
    }
}

function requerir_rol(string ...$roles): void
{
    if (!esta_autenticado()) {
        redirigir(url('login.php?error=sesion'));
    }

    if (!in_array(rol_actual(), $roles, true)) {
        redirigir(url('login.php?error=permiso'));
    }
}

function mensaje_error_login(?string $codigo): string
{
    switch ($codigo) {
        case 'sesion':
            return 'Inicie sesión para continuar.';
        case 'permiso':
            return 'Su cuenta no tiene acceso a esa página.';
        case 'credenciales':
            return 'Correo o contraseña incorrectos.';
        default:
            return '';
    }
}
