<?php
/**
 * Ferretería El Tornillo — cargar nuevo producto (vendedor)
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('vendedor');

$vendedor_id = usuario_id();
$error = '';
$exito = '';

$nombre      = '';
$descripcion = '';
$categoria_id = 0;
$precio      = '';
$stock       = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre       = trim($_POST['nombre'] ?? '');
    $descripcion  = trim($_POST['descripcion'] ?? '');
    $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
    $precio       = (float) ($_POST['precio'] ?? 0.0);
    $stock        = (int) ($_POST['stock'] ?? 0);

    if ($nombre === '') {
        $error = 'El nombre del producto es obligatorio.';
    } elseif ($categoria_id <= 0) {
        $error = 'Seleccione una categoría válida.';
    } elseif ($precio < 0) {
        $error = 'El precio no puede ser negativo.';
    } elseif ($stock < 0) {
        $error = 'El stock no puede ser negativo.';
    } else {
        $imagen_nombre = null;

        // Procesar subida de archivo si existe
        if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['imagen']['tmp_name'];
            $orig_name = basename($_FILES['imagen']['name']);
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            $permitidas = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
            if (!in_array($ext, $permitidas, true)) {
                $error = 'El archivo subido no tiene una extensión de imagen permitida (jpg, png, gif, webp, svg).';
            } else {
                $nuevo_nombre = 'prod_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = RUTA_UPLOADS . '/' . $nuevo_nombre;

                if (!is_dir(RUTA_UPLOADS)) {
                    @mkdir(RUTA_UPLOADS, 0755, true);
                }

                if (move_uploaded_file($tmp_name, $destino)) {
                    $imagen_nombre = $nuevo_nombre;
                } else {
                    $error = 'No se pudo guardar la imagen en el servidor.';
                }
            }
        }

        if ($error === '') {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO productos (vendedor_id, categoria_id, nombre, descripcion, precio, stock, imagen, creado_en)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())"
            );
            mysqli_stmt_bind_param($stmt, 'iissdis', $vendedor_id, $categoria_id, $nombre, $descripcion, $precio, $stock, $imagen_nombre);

            if (mysqli_stmt_execute($stmt)) {
                redirigir(url('vendedor/mis-productos.php?creado=1'));
            } else {
                $error = 'Error al registrar el producto: ' . mysqli_error($conn);
            }
        }
    }
}

// Obtener categorías
$categorias = [];
$res_cat = mysqli_query($conn, "SELECT id, nombre FROM categorias ORDER BY nombre");
if ($res_cat !== false) {
    $categorias = mysqli_fetch_all($res_cat, MYSQLI_ASSOC);
}

$titulo_pagina = 'Cargar nuevo producto';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="titulo-seccion h2 mb-1">Cargar Producto</h1>
                <p class="text-secondary mb-0">Publique un nuevo artículo en la ferretería.</p>
            </div>
            <a href="<?= url('vendedor/mis-productos.php') ?>" class="btn btn-outline-secondary">
                &larr; Mis productos
            </a>
        </div>

        <?php if ($error !== ''): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
        <?php endif; ?>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-oscuro text-white py-3">
                <h2 class="h5 mb-0">Datos del producto</h2>
            </div>
            <div class="card-body p-4">
                <form method="post" action="<?= url('vendedor/cargar-producto.php') ?>" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="nombre" class="form-label fw-semibold">Nombre del producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= e($nombre) ?>" placeholder="Ej. Taladro de impacto 1/2 pulgada" required>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label for="categoria_id" class="form-label fw-semibold">Categoría <span class="text-danger">*</span></label>
                            <select class="form-select" id="categoria_id" name="categoria_id" required>
                                <option value="">Seleccione una categoría</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int)$c['id'] ?>" <?= $categoria_id === (int)$c['id'] ? 'selected' : '' ?>>
                                        <?= e($c['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="precio" class="form-label fw-semibold">Precio ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" id="precio" name="precio" value="<?= e((string)$precio) ?>" placeholder="0.00" required>
                        </div>
                        <div class="col-md-3">
                            <label for="stock" class="form-label fw-semibold">Stock inicial <span class="text-danger">*</span></label>
                            <input type="number" min="0" class="form-control" id="stock" name="stock" value="<?= e((string)$stock) ?>" placeholder="10" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label fw-semibold">Descripción del artículo</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="4" placeholder="Detalles de fabricación, uso recomendado, especificaciones técnicas..."><?= e($descripcion) ?></textarea>
                    </div>

                    <div class="mb-4">
                        <label for="imagen" class="form-label fw-semibold">Fotografía del producto</label>
                        <input class="form-control" type="file" id="imagen" name="imagen" accept="image/*">
                        <small class="text-muted">Formatos aceptados: JPG, PNG, WEBP, GIF, SVG. Máximo 5 MB.</small>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <a href="<?= url('vendedor/mis-productos.php') ?>" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-naranja px-4">Publicar producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
