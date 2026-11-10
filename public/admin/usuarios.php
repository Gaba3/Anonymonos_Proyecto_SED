<?php
/**
 * Ferretería El Tornillo — CRUD de usuarios (administrador)
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('admin');

$mensaje = '';
$error = '';
$admin_actual_id = usuario_id();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Crear usuario
    if ($accion === 'crear') {
        $nombre   = trim($_POST['nombre'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $password = $_POST['password'] ?? '';
        $rol      = $_POST['rol'] ?? 'comprador';

        if ($nombre === '' || $correo === '' || $password === '') {
            $error = 'Todos los campos son obligatorios.';
        } elseif (!in_array($rol, ROLES_VALIDOS, true)) {
            $error = 'El rol seleccionado no es válido.';
        } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $error = 'El correo electrónico no tiene un formato válido.';
        } else {
            // Verificar si el correo ya existe
            $check = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE correo = ?");
            mysqli_stmt_bind_param($check, 's', $correo);
            mysqli_stmt_execute($check);
            if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
                $error = "Ya existe un usuario registrado con el correo '$correo'.";
            } else {
                $pass_md5 = md5($password);
                $stmt = mysqli_prepare($conn, "INSERT INTO usuarios (nombre, correo, password, rol, creado_en) VALUES (?, ?, ?, ?, NOW())");
                mysqli_stmt_bind_param($stmt, 'ssss', $nombre, $correo, $pass_md5, $rol);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario '$nombre' registrado exitosamente.";
                } else {
                    $error = "Error al registrar usuario: " . mysqli_error($conn);
                }
            }
        }
    }

    // Editar usuario
    if ($accion === 'editar') {
        $uid      = (int) ($_POST['usuario_id'] ?? 0);
        $nombre   = trim($_POST['nombre'] ?? '');
        $correo   = trim($_POST['correo'] ?? '');
        $rol      = $_POST['rol'] ?? 'comprador';
        $password = $_POST['password'] ?? '';

        if ($uid > 0 && $nombre !== '' && $correo !== '') {
            if (!in_array($rol, ROLES_VALIDOS, true)) {
                $error = 'El rol seleccionado no es válido.';
            } else {
                // Verificar correo duplicado en otro usuario
                $check = mysqli_prepare($conn, "SELECT id FROM usuarios WHERE correo = ? AND id != ?");
                mysqli_stmt_bind_param($check, 'si', $correo, $uid);
                mysqli_stmt_execute($check);
                if (mysqli_fetch_assoc(mysqli_stmt_get_result($check))) {
                    $error = "El correo '$correo' ya está en uso por otro usuario.";
                } else {
                    if ($password !== '') {
                        $pass_md5 = md5($password);
                        $stmt = mysqli_prepare($conn, "UPDATE usuarios SET nombre = ?, correo = ?, rol = ?, password = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, 'ssssi', $nombre, $correo, $rol, $pass_md5, $uid);
                    } else {
                        $stmt = mysqli_prepare($conn, "UPDATE usuarios SET nombre = ?, correo = ?, rol = ? WHERE id = ?");
                        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $correo, $rol, $uid);
                    }

                    if (mysqli_stmt_execute($stmt)) {
                        $mensaje = "Datos del usuario actualizados correctamente.";
                    } else {
                        $error = "Error al actualizar: " . mysqli_error($conn);
                    }
                }
            }
        }
    }

    // Eliminar usuario
    if ($accion === 'eliminar') {
        $uid = (int) ($_POST['usuario_id'] ?? 0);
        if ($uid === $admin_actual_id) {
            $error = 'No puede eliminar su propia cuenta de administrador.';
        } elseif ($uid > 0) {
            // Verificar si tiene facturas (ON DELETE RESTRICT en MySQL)
            $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM facturas WHERE comprador_id = $uid");
            $total_fac = mysqli_fetch_assoc($check)['total'] ?? 0;

            if ($total_fac > 0) {
                $error = "No se puede eliminar el usuario porque tiene $total_fac factura(s) asociadas (la base de datos restringe la eliminación histórica).";
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM usuarios WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $uid);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Usuario eliminado del sistema.";
                } else {
                    $error = "Error al eliminar usuario: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Búsqueda y listado
$q = trim($_GET['q'] ?? '');
$sql = "SELECT u.id, u.nombre, u.correo, u.rol, u.creado_en,
               (SELECT COUNT(*) FROM facturas f WHERE f.comprador_id = u.id) AS facturas_comprador,
               (SELECT COUNT(*) FROM productos p WHERE p.vendedor_id = u.id) AS productos_vendedor
        FROM usuarios u
        WHERE u.nombre LIKE '%$q%' OR u.correo LIKE '%$q%'
        ORDER BY u.id ASC";

$res = mysqli_query($conn, $sql);
$usuarios = ($res !== false) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

$titulo_pagina = 'Administración de Usuarios';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Gestión de Usuarios</h1>
        <p class="text-secondary mb-0">Control de cuentas, asignación de roles y permisos de acceso.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/panel.php') ?>" class="btn btn-outline-secondary">&larr; Panel</a>
        <button class="btn btn-naranja" data-bs-toggle="modal" data-bs-target="#modalCrearUsuario">
            + Nuevo Usuario
        </button>
    </div>
</div>

<?php if ($mensaje !== ''): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= e($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<!-- Buscador -->
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body p-3">
        <form method="get" action="<?= url('admin/usuarios.php') ?>" class="d-flex gap-2">
            <input type="search" name="q" class="form-control" placeholder="Buscar por nombre o correo electrónico..." value="<?= e($q) ?>">
            <button type="submit" class="btn btn-naranja">Buscar</button>
            <?php if ($q !== ''): ?>
                <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla de usuarios -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-oscuro text-white">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th>Nombre</th>
                        <th>Correo electrónico</th>
                        <th class="text-center">Rol</th>
                        <th class="text-center">Actividad</th>
                        <th>Fecha de registro</th>
                        <th class="text-center" style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($usuarios)): ?>
                        <tr><td colspan="7" class="text-center py-4 text-muted">No se encontraron usuarios.</td></tr>
                    <?php else: ?>
                        <?php foreach ($usuarios as $u): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= $u['id'] ?></td>
                                <td class="fw-bold">
                                    <?= e($u['nombre']) ?>
                                    <?php if ((int)$u['id'] === $admin_actual_id): ?>
                                        <span class="badge bg-secondary ms-1">Tú</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($u['correo']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= $u['rol'] === 'admin' ? 'badge-subtle-admin' : ($u['rol'] === 'vendedor' ? 'badge-subtle-vendedor' : 'badge-subtle-comprador') ?> px-2 py-1">
                                        <?= e($u['rol']) ?>
                                    </span>
                                </td>
                                <td class="text-center small text-muted">
                                    <?php if ($u['rol'] === 'comprador'): ?>
                                        <?= (int)$u['facturas_comprador'] ?> compras
                                    <?php elseif ($u['rol'] === 'vendedor'): ?>
                                        <?= (int)$u['productos_vendedor'] ?> productos
                                    <?php else: ?>
                                        Admin
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted"><?= fecha_legible($u['creado_en']) ?></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $u['id'] ?>">
                                            Editar
                                        </button>
                                        <?php if ((int)$u['id'] !== $admin_actual_id): ?>
                                            <button class="btn btn-outline-danger" onclick="if(confirm('¿Eliminar usuario #<?= $u['id'] ?> (<?= e(addslashes($u['nombre'])) ?>)?')) document.getElementById('del-u-<?= $u['id'] ?>').submit();">
                                                Borrar
                                            </button>
                                        <?php else: ?>
                                            <button class="btn btn-outline-secondary" disabled>Borrar</button>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ((int)$u['id'] !== $admin_actual_id): ?>
                                        <form id="del-u-<?= $u['id'] ?>" method="post" action="<?= url('admin/usuarios.php') ?>" style="display:none;">
                                            <input type="hidden" name="accion" value="eliminar">
                                            <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                        </form>
                                    <?php endif; ?>

                                    <!-- Modal de edición -->
                                    <div class="modal fade" id="modalEdit<?= $u['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog text-start">
                                            <div class="modal-content">
                                                <form method="post" action="<?= url('admin/usuarios.php') ?>">
                                                    <input type="hidden" name="accion" value="editar">
                                                    <input type="hidden" name="usuario_id" value="<?= $u['id'] ?>">
                                                    <div class="modal-header bg-oscuro text-white">
                                                        <h5 class="modal-title">Editar Usuario #<?= $u['id'] ?></h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Nombre</label>
                                                            <input type="text" name="nombre" class="form-control" value="<?= e($u['nombre']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Correo electrónico</label>
                                                            <input type="email" name="correo" class="form-control" value="<?= e($u['correo']) ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Rol del usuario</label>
                                                            <select name="rol" class="form-select" required>
                                                                <option value="comprador" <?= $u['rol'] === 'comprador' ? 'selected' : '' ?>>Comprador</option>
                                                                <option value="vendedor" <?= $u['rol'] === 'vendedor' ? 'selected' : '' ?>>Vendedor</option>
                                                                <option value="admin" <?= $u['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option>
                                                            </select>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Cambiar Contraseña (opcional)</label>
                                                            <input type="password" name="password" class="form-control" placeholder="Dejar en blanco para mantener la actual">
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-naranja">Guardar Cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear Usuario -->
<div class="modal fade" id="modalCrearUsuario" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog text-start">
        <div class="modal-content">
            <form method="post" action="<?= url('admin/usuarios.php') ?>">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header bg-oscuro text-white">
                    <h5 class="modal-title">Registrar Nuevo Usuario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre completo *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Correo electrónico *</label>
                        <input type="email" name="correo" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rol *</label>
                        <select name="rol" class="form-select" required>
                            <option value="comprador">Comprador</option>
                            <option value="vendedor">Vendedor</option>
                            <option value="admin">Administrador</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Contraseña *</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-naranja">Crear Usuario</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
