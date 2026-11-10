<?php
/**
 * Ferretería El Tornillo — productos publicados por el vendedor
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('vendedor');

$vendedor_id = usuario_id();
$mensaje = '';
$error = '';

// Procesar acciones (edición rápida y eliminación)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'actualizar') {
        $pid   = (int) ($_POST['producto_id'] ?? 0);
        $precio = (float) ($_POST['precio'] ?? 0.0);
        $stock  = (int) ($_POST['stock'] ?? 0);

        if ($pid > 0 && $precio >= 0 && $stock >= 0) {
            $stmt = mysqli_prepare($conn, "UPDATE productos SET precio = ?, stock = ? WHERE id = ? AND vendedor_id = ?");
            mysqli_stmt_bind_param($stmt, 'diii', $precio, $stock, $pid, $vendedor_id);
            if (mysqli_stmt_execute($stmt)) {
                $mensaje = "Producto actualizado correctamente.";
            } else {
                $error = "Error al actualizar el producto: " . mysqli_error($conn);
            }
        }
    }

    if ($accion === 'eliminar') {
        $pid = (int) ($_POST['producto_id'] ?? 0);
        if ($pid > 0) {
            // Verificar si el producto tiene ventas
            $check = mysqli_query($conn, "SELECT COUNT(*) AS total FROM detalle_factura WHERE producto_id = $pid");
            $ventas = mysqli_fetch_assoc($check)['total'] ?? 0;

            if ($ventas > 0) {
                $error = "No se puede eliminar el producto porque ya figura en facturas emitidas.";
            } else {
                $stmt = mysqli_prepare($conn, "DELETE FROM productos WHERE id = ? AND vendedor_id = ?");
                mysqli_stmt_bind_param($stmt, 'ii', $pid, $vendedor_id);
                if (mysqli_stmt_execute($stmt)) {
                    $mensaje = "Producto eliminado con éxito.";
                } else {
                    $error = "Error al eliminar: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Cargar productos del vendedor
$sql = "SELECT p.*, c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.vendedor_id = $vendedor_id
        ORDER BY p.id DESC";

$resultado = mysqli_query($conn, $sql);
$productos = ($resultado !== false) ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];

function clase_stock(int $stock): string
{
    if ($stock <= 0) return 'stock-agotado';
    return $stock <= 10 ? 'stock-bajo' : 'stock-ok';
}

$titulo_pagina = 'Mis productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Mis Productos</h1>
        <p class="text-secondary mb-0">Gestione el catálogo de artículos que ha publicado en la ferretería.</p>
    </div>
    <a href="<?= url('vendedor/cargar-producto.php') ?>" class="btn btn-naranja">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-plus-circle me-1" viewBox="0 0 16 16">
          <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14m0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16"/>
          <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4"/>
        </svg>
        Cargar nuevo producto
    </a>
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

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($productos)): ?>
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="bi bi-box-seam mb-3" viewBox="0 0 16 16">
                  <path d="M8.186 1.113a.5.5 0 0 0-.372 0L1.846 3.5l2.404.961L10.404 2zm3.564 1.426L5.596 5 8 5.961 14.154 3.5zm3.25 1.7-6.5 2.6v7.922l6.5-2.6V4.24zM7.5 14.762V6.838L1 4.239v7.923zM7.443.184a1.5 1.5 0 0 1 1.114 0l7.129 2.852A.5.5 0 0 1 16 3.5v8.662a1 1 0 0 1-.629.928l-7.185 2.874a.5.5 0 0 1-.372 0L.63 13.09a1 1 0 0 1-.63-.928V3.5a.5.5 0 0 1 .314-.464z"/>
                </svg>
                <h4 class="h5">No ha publicado productos todavía</h4>
                <p class="text-muted small">Comience publicando un producto para vender en la ferretería.</p>
                <a href="<?= url('vendedor/cargar-producto.php') ?>" class="btn btn-sm btn-naranja">Cargar primer producto</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-oscuro text-white">
                        <tr>
                            <th style="width: 70px;">Imagen</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th class="text-end" style="width: 140px;">Precio</th>
                            <th class="text-center" style="width: 130px;">Stock</th>
                            <th class="text-center" style="width: 150px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $p): ?>
                            <tr>
                                <td>
                                    <img src="<?= imagen_producto($p['imagen']) ?>" alt="" width="50" height="50" style="object-fit: contain;" class="rounded border bg-white p-1">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= e($p['nombre']) ?></div>
                                    <small class="text-muted d-block text-truncate" style="max-width: 320px;">
                                        <?= e($p['descripcion'] ?? '') ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border"><?= e($p['categoria'] ?? 'Sin categoría') ?></span>
                                </td>
                                <td class="text-end fw-bold"><?= dinero($p['precio']) ?></td>
                                <td class="text-center">
                                    <span class="fw-semibold <?= clase_stock((int)$p['stock']) ?>">
                                        <?= (int)$p['stock'] ?> uds.
                                    </span>
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEdit<?= $p['id'] ?>" title="Editar">
                                            Editar
                                        </button>
                                        <button type="button" class="btn btn-outline-danger" onclick="if(confirm('¿Eliminar <?= e(addslashes($p['nombre'])) ?>?')) document.getElementById('del-prod-<?= $p['id'] ?>').submit();" title="Eliminar">
                                            Eliminar
                                        </button>
                                    </div>

                                    <!-- Formulario oculto para eliminar -->
                                    <form id="del-prod-<?= $p['id'] ?>" method="post" action="<?= url('vendedor/mis-productos.php') ?>" style="display:none;">
                                        <input type="hidden" name="accion" value="eliminar">
                                        <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                    </form>

                                    <!-- Modal de Edición Rápida -->
                                    <div class="modal fade" id="modalEdit<?= $p['id'] ?>" tabindex="-1" aria-labelledby="modalLabel<?= $p['id'] ?>" aria-hidden="true">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content text-start">
                                                <form method="post" action="<?= url('vendedor/mis-productos.php') ?>">
                                                    <input type="hidden" name="accion" value="actualizar">
                                                    <input type="hidden" name="producto_id" value="<?= $p['id'] ?>">
                                                    <div class="modal-header bg-oscuro text-white">
                                                        <h5 class="modal-title" id="modalLabel<?= $p['id'] ?>">Editar: <?= e($p['nombre']) ?></h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="mb-3">
                                                            <label class="form-label">Precio ($)</label>
                                                            <input type="number" step="0.01" min="0" name="precio" class="form-control" value="<?= (float)$p['precio'] ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label class="form-label">Stock disponible</label>
                                                            <input type="number" min="0" name="stock" class="form-control" value="<?= (int)$p['stock'] ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-naranja">Guardar cambios</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
