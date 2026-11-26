<?php
/**
 * Ferretería El Tornillo — inicio de sesión
 *
 * Curso: Seguridad en entornos de desarrollo (UCA).
 *
 * Pantalla pública. Valida las credenciales contra la tabla usuarios y,
 * si son correctas, abre la sesión y manda al usuario a la vista que le
 * toca según su rol.
 *
 * ---------------------------------------------------------------------
 * CONTIENE LA VULNERABILIDAD V1 (INYECCIÓN SQL) — NO CORREGIR
 * ---------------------------------------------------------------------
 * El correo y la contraseña se concatenan dentro de la consulta de
 * autenticación. Está documentado en la sección 5 del README.md. La otra
 * mitad de V1 está en el buscador de index.php.
 */

require_once __DIR__ . '/../includes/auth.php';

// Si la sesión ya está abierta no tiene sentido volver a entrar.
if (esta_autenticado()) {
    redirigir(destino_tras_login(rol_actual()));
}

$error          = '';
$consulta_fallo = false;
$sql            = '';
$correo_previo  = '';
$registro_ok    = (($_GET['registro'] ?? '') === 'ok');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo        = $_POST['correo'] ?? '';
    $password      = $_POST['password'] ?? '';
    $correo_previo = $correo;

    if (trim($correo) === '' || $password === '') {
        $error = 'Escriba su correo y su contraseña.';
    } else {
        // -------------------------------------------------------------
        // Consulta de autenticación — VULNERABILIDAD V1 (NO CORREGIR)
        // -------------------------------------------------------------
        // $correo y $password entran tal cual en la cadena SQL y se
        // ejecuta con mysqli_query(), sin mysqli_prepare() ni
        // bind_param() y sin escapar los caracteres especiales. Un valor
        // como   ' OR '1'='1   cierra la comilla y altera la condición,
        // de modo que la consulta devuelve la primera fila de la tabla.
        //
        // Mitigación correcta (deliberadamente NO aplicada):
        //     $stmt = mysqli_prepare($conn,
        //         'SELECT id, nombre, rol, password FROM usuarios WHERE correo = ?');
        //     mysqli_stmt_bind_param($stmt, 's', $correo);
        //     ... y password_verify($password, $hash) en lugar de MD5()
        $sql = "SELECT id, nombre, rol FROM usuarios
                WHERE correo = '$correo' AND password = MD5('$password')";

        $resultado = mysqli_query($conn, $sql);

        if ($resultado === false) {
            // El error de MySQL se imprime dentro del formulario. Es
            // parte del diseño de V1: facilita identificar la inyección
            // durante la etapa de ataque.
            $consulta_fallo = true;
        } else {
            $fila = mysqli_fetch_assoc($resultado);

            if ($fila === null) {
                $error = mensaje_error_login('credenciales');
            } else {
                iniciar_sesion_usuario(
                    (int) $fila['id'],
                    (string) $fila['nombre'],
                    (string) $fila['rol']
                );

                redirigir(destino_tras_login((string) $fila['rol']));
            }
        }
    }
} else {
    // Avisos que mandan requerir_sesion() y requerir_rol() por la URL.
    $error = mensaje_error_login($_GET['error'] ?? null);
}

$titulo_pagina = 'Iniciar sesión';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card tarjeta-sesion shadow-sm">
    <div class="card-header py-3">
        <h1 class="h5 mb-0">Iniciar sesión</h1>
    </div>

    <div class="card-body p-4">

        <?php if ($registro_ok): ?>
            <div class="alert alert-success" role="alert">
                Su cuenta fue creada. Ya puede iniciar sesión.
            </div>
        <?php endif; ?>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <?php if ($consulta_fallo): ?>
            <?php error_sql($conn, $sql); ?>
        <?php endif; ?>

        <form method="post" action="<?= url('login.php') ?>" novalidate>
            <div class="mb-3">
                <label class="form-label" for="correo">Correo</label>
                <?php
                // type="text" y no type="email" a propósito: el navegador
                // rechazaría las comillas de una inyección antes de enviar
                // el formulario y no se podría demostrar V1.
                ?>
                <input class="form-control" type="text" id="correo" name="correo"
                       value="<?= e($correo_previo) ?>" autocomplete="username"
                       autofocus required>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password"
                       autocomplete="current-password" required>
            </div>

            <button class="btn btn-naranja w-100" type="submit">Entrar</button>
        </form>

        <p class="text-center text-secondary small mt-3 mb-0">
            ¿No tiene cuenta?
            <a class="text-naranja" href="<?= url('register.php') ?>">Registrarse</a>
        </p>
    </div>

    <div class="card-footer bg-white border-top">
        <p class="small text-secondary mb-1 fw-semibold">Usuarios de prueba</p>
        <ul class="small text-secondary mb-0 ps-3">
            <li><code>admin@eltornillo.local</code> · <code>Admin123</code></li>
            <li><code>vendedor@eltornillo.local</code> · <code>Vendedor123</code></li>
            <li><code>comprador@eltornillo.local</code> · <code>Comprador123</code></li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
