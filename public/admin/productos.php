<?php
/**
 * Ferretería El Tornillo — CRUD de productos (administrador)
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('admin');

$mensaje = '';
$error = '';

// Procesar acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    // Crear producto
    if ($accion === 'crear') {
        $nombre       = trim($_POST['nombre'] ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
        $vendedor_id  = (int) ($_POST['vendedor_id'] ?? 0);
        $precio       = (float) ($_POST['precio'] ?? 0.0);
        $stock        = (int) ($_POST['stock'] ?? 0);

        if ($nombre === '') {
            $error = 'El nombre del producto es obligatorio.';
        } elseif ($categoria_id <= 0) {
            $error = 'Seleccione una categoría válida.';
        } else {
            $imagen_nombre = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $orig_name = basename($_FILES['imagen']['name']);
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                if (in_array($ext, $permitidas, true)) {
                    $nuevo_nombre = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (!is_dir(RUTA_UPLOADS)) {
                        @mkdir(RUTA_UPLOADS, 0755, true);
                    }
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], RUTA_UPLOADS . '/' . $nuevo_nombre)) {
                        $imagen_nombre = $nuevo_nombre;
                    }
                }
            }

            $vendedor_param = ($vendedor_id > 0) ? $vendedor_id : null;

            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO productos (vendedor_id, categoria_id, nombre, descripcion, precio, stock, imagen, creado_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            mysqli_stmt_bind_param($stmt, 'iissdis', $vendedor_param, $categoria_id, $nombre, $descripcion, $precio, $stock, $imagen_nombre);
            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "Producto '$nombre' creado exitosamente.";
            } else {
                $error = "Error al crear producto: " . mysqli_error($conn);
            }
        }
    }

    // Editar producto
    if ($accion === 'editar') {
        $pid          = (int) ($_POST['producto_id'] ?? 0);
        $nombre       = trim($_POST['nombre'] ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
        $vendedor_id  = (int) ($_POST['vendedor_id'] ?? 0);
        $precio       = (float) ($_POST['precio'] ?? 0.0);
        $stock        = (int) ($_POST['stock'] ?? 0);

        if ($pid > 0 && $nombre !== '') {
            $vendedor_param = ($vendedor_id > 0) ? $vendedor_id : null;

            // Revisar si subió nueva imagen
            $imagen_sql = "";
            $imagen_nombre = null;
            if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
                $orig_name = basename($_FILES['imagen']['name']);
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
                if (in_array($ext, $permitidas, true)) {
                    $nuevo_nombre = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (!is_dir(RUTA_UPLOADS)) {
                        @mkdir(RUTA_UPLOADS, 0755, true);
                    }
                    if (move_uploaded_file($_FILES['imagen']['tmp_name'], RUTA_UPLOADS . '/' . $nuevo_nombre)) {
                        $imagen_nombre = $nuevo_nombre;
                    }
                }
            }

            if ($imagen_nombre !== null) {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE productos SET nombre = ?, descripcion = ?, categoria_id = ?, vendedor_id = ?, precio = ?, stock = ?, imagen = ? WHERE id = ?"
                );
                mysqli_stmt_bind_param($stmt, 'ssiidisi', $nombre, $descripcion, $categoria_id, $vendedor_param, $precio, $stock, $imagen_nombre, $pid);
            } else {
                $stmt = mysqli_prepare(
                    $conn,
                    "UPDATE productos SET nombre = ?, descripcion = ?, categoria_id = ?, vendedor_id = ?, precio = ?, stock = ? WHERE id = ?"
                );
                mysqli_stmt_bind_param($stmt, 'ssiiddi', $nombre, $descripcion, $categoria_id, $vendedor_param, $precio, $stock, $pid);
            }

            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "Producto actualizado correctamente.";
            } else {
                $error = "Error al actualizar: " . mysqli_error($conn);
            }
        }
    }

    // Eliminar producto
    if ($accion === 'eliminar') {
        $pid = (int) ($_POST['producto_id'] ?? 0);
        if ($pid > 0) {
            $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM detalle_factura WHERE producto_id = $pid");
            $ventas = mysqli_fetch_assoc($check)['total'] ?? 0;

            if ($ventas > 0) {
                $error = "No se puede eliminar este producto porque ya está registrado en $ventas factura(s).";
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM productos WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $pid);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Producto eliminado del inventario.";
                } else {
                    $error = "Error al eliminar: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Búsqueda y listado
$q = trim($_GET['q'] ?? '');
$sql = "SELECT p.*, c.nombre AS categoria, u.nombre AS vendedor
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        LEFT JOIN usuarios u ON u.id = p.vendedor_id
        WHERE p.nombre LIKE '%$q%'
        ORDER BY p.id DESC";

$res = mysqli_query($conn, $sql);
$productos = ($res !== false) ? mysqli_fetch_all($res, MYSQLI_ASSOC) : [];

// Cargar categorías y vendedores para selects
$categorias = [];
$res_c = mysqli_query($conn, "SELECT id, nombre FROM categorias ORDER BY nombre");
if ($res_c !== false) {
    $categorias = mysqli_fetch_all($res_c, MYSQLI_ASSOC);
}

$vendedores = [];
$res_v = mysqli_query($conn, "SELECT id, nombre FROM usuarios WHERE rol IN ('vendedor', 'admin') ORDER BY nombre");
if ($res_v !== false) {
    $vendedores = mysqli_fetch_all($res_v, MYSQLI_ASSOC);
}

$titulo_pagina = 'Administración de Productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Inventario de Productos</h1>
        <p class="text-secondary mb-0">Control total del catálogo: crear, actualizar y eliminar artículos.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/panel.php') ?>" class="btn btn-outline-secondary">&larr; Panel</a>
        <button class="btn btn-naranja" data-bs-toggle="modal" data-bs-target="#modalCrearProd">
            + Nuevo Producto
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
        <form method="get" action="<?= url('admin/productos.php') ?>" class="d-flex gap-2">
            <input type="search" name="q" class="form-control" placeholder="Buscar por nombre de producto..." value="<?= e($q) ?>">
            <button type="submit" class="btn btn-naranja">Buscar</button>
            <?php if ($q !== ''): ?>
                <a href="<?= url('admin/productos.php') ?>" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabla de productos -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-oscuro text-white">
                    <tr>
                        <th style="width: 50px;">ID</th>
                        <th style="width: 70px;">Foto</th>
                        <th>Producto</th>
                        <th>Categoría</th>
                        <th>Vendedor</th>
                        <th class="text-end">Precio</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center" style="width: 140px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($productos)): ?>
                        <tr><td colspan="8" class="text-center py-4 text-muted">No se encontraron productos.</td></tr>
                    <?php else: ?>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td class="text-muted fw-bold"><?= $p['id'] ?></td>
                                <td>
                                    <img src="<?= imagen_producto($p['imagen']) ?>" alt="" width="48" height="48" style="object-fit: contain;" class="rounded border bg-white p-1">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= e($p['nombre']) ?></div>
                                    <small class="text-muted text-truncate d-block" style="max-width: 250px;">
                                        <?= e($p['descripcion'] ?? '') ?>
                                    </small>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($p['categoria'] ?? 'Sin categoría') ?></span></td>
                                <td class="small"><?= e($p['vendedor'] ?? '—') ?></td>
                                <td class="text-end fw-bold"><?= dinero($p['precio']) ?></td>
                                <td class="text-center">
                                    <span class="badge <?= (int)$p['stock'] <= 0 ? 'bg-danger' : ((int)$p['stock'] <= 10 ? 'bg-warning text-dark' : 'bg-success') ?>">
                                        <?= (int)$p['stock'] ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id'] ?>">
                                            Editar
                                        </button>
                                        <button class="btn btn-outline-danger" onclick="if(confirm('¿Eliminar producto #<?= $p['id'] ?> (<?= e(addslashes($p['nombre'])) ?>)?')) document.getElementById('del-<?= $p['id'] ?>').submit();">
                                            Borrar
                                        </button>
                                    </div>

                                    <form id="del-<?= $p['id'] ?>" method="post" action="<?= url('admin/productos.php') ?>" style="display:none;">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                    </form>

                                    <!-- Modal de edición -->
                                    <div class="modal fade" id="modalEdit<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg text-start">
                                            <div class="modal-content">
                                                <form method="post" action="<?= url('admin/productos.php') ?>" enctype="multipart/form-data">
                                                    <input type="hidden" name="accion" value="editar">
                                                    <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                                    <div class="modal-header bg-oscuro text-white">
                                                        <h5 class="modal-title">Editar Producto #<?= $p['id'] ?></h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Nombre</label>
                                                            <input type="text" name="nombre" class="form-control" value="<?= e($p['nombre']) ?>" required>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Categoría</label>
                                                                <select name="categoria_id" class="form-select" required>
                                                                    <?php foreach ($categorias as $c): ?>
                                                                        <option value="<?= $c['id'] ?>" <?= (int)$p['categoria_id'] === (int)$c['id'] ? 'selected' : '' ?>>
                                                                            <?= e($c['nombre']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Vendedor Asignado</label>
                                                                <select name="vendedor_id" class="form-select">
                                                                    <option value="0">-- Ninguno --</option>
                                                                    <?php foreach ($vendedores as $v): ?>
                                                                        <option value="<?= $v['id'] ?>" <?= (int)$p['vendedor_id'] === (int)$v['id'] ? 'selected' : '' ?>>
                                                                            <?= e($v['nombre']) ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row g-3 mb-3">
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Precio ($)</label>
                                                                <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= (float)$p['precio'] ?>" required>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <label class="form-label fw-semibold">Stock</label>
                                                                <input type="number" min="0" name="stock" class="form-control" value="<?= (int)$p['stock'] ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Descripción</label>
                                                            <textarea name="descripcion" class="form-control" rows="3"><?= e($p['descripcion'] ?? '') ?></textarea>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label fw-semibold">Cambiar Imagen (opcional)</label>
                                                            <input type="file" name="imagen" class="form-control" accept="image/*">
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

<!-- Modal Crear Producto -->
<div class="modal fade" id="modalCrearProd" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg text-start">
        <div class="modal-content">
            <form method="post" action="<?= url('admin/productos.php') ?>" enctype="multipart/form-data">
                <input type="hidden" name="accion" value="crear">
                <div class="modal-header bg-oscuro text-white">
                    <h5 class="modal-title">Registrar Nuevo Producto</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Nombre del producto *</label>
                        <input type="text" name="nombre" class="form-control" required>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Categoría *</label>
                            <select name="categoria_id" class="form-select" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Vendedor</label>
                            <select name="vendedor_id" class="form-select">
                                <option value="0">-- Sin vendedor asignado --</option>
                                <?php foreach ($vendedores as $v): ?>
                                    <option value="<?= $v['id'] ?>"><?= e($v['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Precio ($) *</label>
                            <input type="number" step="0.01" min="0" name="precio" class="form-control" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold">Stock Inicial *</label>
                            <input type="number" min="0" name="stock" class="form-control" placeholder="10" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Descripción</label>
                        <textarea name="descripcion" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Fotografía</label>
                        <input type="file" name="imagen" class="form-control" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-naranja">Guardar Producto</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
