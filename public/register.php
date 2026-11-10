<?php
/**
 * Ferretería El Tornillo — registro de usuarios
 *
 * Curso: Seguridad en entornos de desarrollo (UCA).
 *
 * Pantalla pública. Crea una cuenta con rol 'comprador' (el valor por
 * defecto de la columna rol en database/01_schema.sql) y manda al login.
 * El cambio de rol lo hace el administrador desde su panel, según la
 * tabla de roles de la sección 1 del README.md.
 *
 * ---------------------------------------------------------------------
 * ESTA PANTALLA NO ES VULNERABLE — ES A PROPÓSITO
 * ---------------------------------------------------------------------
 * Las tres vulnerabilidades del proyecto están acotadas y documentadas
 * en la sección 5 del README.md: V1 vive en login.php e index.php, V2 en
 * docker-compose.yml y V3 en comprador/factura.php. Aquí las consultas
 * usan mysqli_prepare() y bind_param(), que es justamente la mitigación
 * que V1 no aplica. Sirve de contraste al comparar las dos pantallas.
 *
 * La contraseña sí se guarda con MD5 porque la columna usuarios.password
 * es CHAR(32) y los usuarios de prueba de 02_seed.sql se crearon así. Es
 * la debilidad de almacenamiento que el README documenta junto a V1; la
 * forma correcta sería password_hash() con una columna más larga.
 */

require_once __DIR__ . '/../includes/auth.php';

// Si la sesión ya está abierta no tiene sentido registrarse de nuevo.
if (esta_autenticado()) {
    redirigir(destino_tras_login(rol_actual()));
}

const LARGO_MINIMO_PASSWORD = 8;

$errores = [];
$nombre  = '';
$correo  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre    = trim($_POST['nombre'] ?? '');
    $correo    = trim($_POST['correo'] ?? '');
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // -----------------------------------------------------------------
    // Validación de los datos del formulario
    // -----------------------------------------------------------------
    if ($nombre === '') {
        $errores[] = 'Escriba su nombre.';
    } elseif (mb_strlen($nombre) > 100) {
        $errores[] = 'El nombre no puede pasar de 100 caracteres.';
    }

    if ($correo === '') {
        $errores[] = 'Escriba su correo.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'El correo no tiene un formato válido.';
    } elseif (mb_strlen($correo) > 150) {
        $errores[] = 'El correo no puede pasar de 150 caracteres.';
    }

    if ($password === '') {
        $errores[] = 'Escriba una contraseña.';
    } elseif (mb_strlen($password) < LARGO_MINIMO_PASSWORD) {
        $errores[] = 'La contraseña debe tener al menos ' . LARGO_MINIMO_PASSWORD . ' caracteres.';
    }

    if ($password !== $password2) {
        $errores[] = 'Las dos contraseñas no coinciden.';
    }

    // -----------------------------------------------------------------
    // El correo es UNIQUE en la tabla (uk_usuarios_correo)
    // -----------------------------------------------------------------
    if ($errores === []) {
        $stmt = mysqli_prepare($conn, 'SELECT id FROM usuarios WHERE correo = ?');

        if ($stmt === false) {
            $errores[] = 'No se pudo verificar el correo. Intente de nuevo.';
        } else {
            mysqli_stmt_bind_param($stmt, 's', $correo);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $errores[] = 'Ese correo ya está registrado.';
            }

            mysqli_stmt_close($stmt);
        }
    }

    // -----------------------------------------------------------------
    // Alta del usuario
    // -----------------------------------------------------------------
    if ($errores === []) {
        $sql = "INSERT INTO usuarios (nombre, correo, password, rol)
                VALUES (?, ?, MD5(?), 'comprador')";

        $stmt = mysqli_prepare($conn, $sql);

        if ($stmt === false) {
            $errores[] = 'No se pudo crear la cuenta. Intente de nuevo.';
        } else {
            mysqli_stmt_bind_param($stmt, 'sss', $nombre, $correo, $password);
            $alta_ok = mysqli_stmt_execute($stmt);

            // 1062 = entrada duplicada. Puede pasar si dos registros con
            // el mismo correo llegan entre la verificación y el INSERT.
            if (!$alta_ok && mysqli_stmt_errno($stmt) === 1062) {
                $errores[] = 'Ese correo ya está registrado.';
            } elseif (!$alta_ok) {
                $errores[] = 'No se pudo crear la cuenta. Intente de nuevo.';
            }

            mysqli_stmt_close($stmt);

            if ($alta_ok) {
                // El flujo del README (sección 1) es Register -> Login.
                redirigir(url('login.php?registro=ok'));
            }
        }
    }
}

$titulo_pagina = 'Registrarse';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="card tarjeta-sesion shadow-sm">
    <div class="card-header py-3">
        <h1 class="h5 mb-0">Crear una cuenta</h1>
    </div>

    <div class="card-body p-4">

        <?php if ($errores !== []): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0 ps-3">
                    <?php foreach ($errores as $mensaje): ?>
                        <li><?= e($mensaje) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= url('register.php') ?>">
            <div class="mb-3">
                <label class="form-label" for="nombre">Nombre completo</label>
                <input class="form-control" type="text" id="nombre" name="nombre"
                       value="<?= e($nombre) ?>" maxlength="100"
                       autocomplete="name" autofocus required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="correo">Correo</label>
                <input class="form-control" type="email" id="correo" name="correo"
                       value="<?= e($correo) ?>" maxlength="150"
                       autocomplete="email" required>
            </div>

            <div class="mb-3">
                <label class="form-label" for="password">Contraseña</label>
                <input class="form-control" type="password" id="password" name="password"
                       minlength="<?= LARGO_MINIMO_PASSWORD ?>"
                       autocomplete="new-password" required>
                <div class="form-text">Mínimo <?= LARGO_MINIMO_PASSWORD ?> caracteres.</div>
            </div>

            <div class="mb-4">
                <label class="form-label" for="password2">Repetir la contraseña</label>
                <input class="form-control" type="password" id="password2" name="password2"
                       minlength="<?= LARGO_MINIMO_PASSWORD ?>"
                       autocomplete="new-password" required>
            </div>

            <button class="btn btn-naranja w-100" type="submit">Crear cuenta</button>
        </form>

        <p class="text-center text-secondary small mt-3 mb-0">
            ¿Ya tiene cuenta?
            <a class="text-naranja" href="<?= url('login.php') ?>">Iniciar sesión</a>
        </p>
    </div>

    <div class="card-footer bg-white border-top">
        <p class="small text-secondary mb-0">
            Las cuentas nuevas se crean como <strong>comprador</strong>.
            El administrador puede cambiar el rol desde su panel.
        </p>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
