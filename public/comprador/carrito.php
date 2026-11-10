<?php
/**
 * Ferretería El Tornillo — carrito de compras y generación de facturas
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('comprador');

if (!isset($_SESSION['carrito']) || !is_array($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

$error = '';
$exito = '';

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'agregar') {
        $pid = (int) ($_POST['producto_id'] ?? 0);
        $cantidad = max(1, (int) ($_POST['cantidad'] ?? 1));

        if ($pid > 0) {
            // Verificar stock
            $stmt = mysqli_prepare($conn, "SELECT stock FROM productos WHERE id = ?");
            mysqli_stmt_bind_param($stmt, 'i', $pid);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            $prod = mysqli_fetch_assoc($res);

            if ($prod) {
                $actual = $_SESSION['carrito'][$pid] ?? 0;
                $nuevo_total = min((int)$prod['stock'], $actual + $cantidad);
                $_SESSION['carrito'][$pid] = $nuevo_total;
                redirigir(url('comprador/catalogo.php?agregado=1'));
            }
        }
    }

    if ($accion === 'actualizar') {
        $cantidades = $_POST['cantidades'] ?? [];
        foreach ($cantidades as $pid => $cant) {
            $pid = (int) $pid;
            $cant = (int) $cant;
            if ($cant <= 0) {
                unset($_SESSION['carrito'][$pid]);
            } else {
                // Limitar al stock real
                $stmt = mysqli_prepare($conn, "SELECT stock FROM productos WHERE id = ?");
                mysqli_stmt_bind_param($stmt, 'i', $pid);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                $prod = mysqli_fetch_assoc($res);
                if ($prod) {
                    $_SESSION['carrito'][$pid] = min((int)$prod['stock'], $cant);
                }
            }
        }
        $exito = 'Carrito actualizado correctamente.';
    }

    if ($accion === 'eliminar') {
        $pid = (int) ($_POST['producto_id'] ?? 0);
        unset($_SESSION['carrito'][$pid]);
        $exito = 'Producto eliminado del carrito.';
    }

    if ($accion === 'vaciar') {
        $_SESSION['carrito'] = [];
        $exito = 'El carrito fue vaciado.';
    }

    if ($accion === 'comprar') {
        if (empty($_SESSION['carrito'])) {
            $error = 'El carrito está vacío.';
        } else {
            // Validar stock antes de procesar
            $ids = array_map('intval', array_keys($_SESSION['carrito']));
            $ids_str = implode(',', $ids);
            $res = mysqli_query($conn, "SELECT id, nombre, precio, stock FROM productos WHERE id IN ($ids_str)");
            $productos_db = [];
            while ($row = mysqli_fetch_assoc($res)) {
                $productos_db[(int)$row['id']] = $row;
            }

            $hay_stock = true;
            $total_factura = 0.0;
            $detalles = [];

            foreach ($_SESSION['carrito'] as $pid => $cantidad) {
                if (!isset($productos_db[$pid]) || $productos_db[$pid]['stock'] < $cantidad) {
                    $nombre = $productos_db[$pid]['nombre'] ?? "ID #$pid";
                    $error = "No hay suficiente stock para '$nombre'.";
                    $hay_stock = false;
                    break;
                }
                $precio = (float) $productos_db[$pid]['precio'];
                $subtotal = $precio * $cantidad;
                $total_factura += $subtotal;
                $detalles[] = [
                    'producto_id'     => $pid,
                    'cantidad'        => $cantidad,
                    'precio_unitario' => $precio,
                    'subtotal'        => $subtotal,
                ];
            }

            if ($hay_stock) {
                // Iniciar transacción
                mysqli_begin_transaction($conn);
                try {
                    $comprador_id = usuario_id();
                    $insert_fac = "INSERT INTO facturas (comprador_id, fecha, total) VALUES ($comprador_id, NOW(), $total_factura)";
                    if (!mysqli_query($conn, $insert_fac)) {
                        throw new Exception("Error al crear factura: " . mysqli_error($conn));
                    }
                    $factura_id = mysqli_insert_id($conn);

                    foreach ($detalles as $det) {
                        $p_id  = $det['producto_id'];
                        $cant  = $det['cantidad'];
                        $pu    = $det['precio_unitario'];
                        $sub   = $det['subtotal'];

                        $insert_det = "INSERT INTO detalle_factura (factura_id, producto_id, cantidad, precio_unitario, subtotal)
                                       VALUES ($factura_id, $p_id, $cant, $pu, $sub)";
                        if (!mysqli_query($conn, $insert_det)) {
                            throw new Exception("Error al registrar detalle: " . mysqli_error($conn));
                        }

                        $upd_stock = "UPDATE productos SET stock = stock - $cant WHERE id = $p_id";
                        if (!mysqli_query($conn, $upd_stock)) {
                            throw new Exception("Error al descontar stock: " . mysqli_error($conn));
                        }
                    }

                    mysqli_commit($conn);
                    $_SESSION['carrito'] = [];
                    redirigir(url("comprador/factura.php?id=$factura_id&nueva=1"));
                } catch (Exception $ex) {
                    mysqli_rollback($conn);
                    $error = "No se pudo procesar la compra: " . $ex->getMessage();
                }
            }
        }
    }
}

// Cargar productos actuales del carrito
$items = [];
$total_compra = 0.0;
if (!empty($_SESSION['carrito'])) {
    $ids = array_map('intval', array_keys($_SESSION['carrito']));
    $ids_str = implode(',', $ids);
    $res = mysqli_query($conn, "SELECT p.id, p.nombre, p.precio, p.stock, p.imagen, c.nombre AS categoria
                                FROM productos p
                                LEFT JOIN categorias c ON c.id = p.categoria_id
                                WHERE p.id IN ($ids_str)");
    if ($res !== false) {
        while ($row = mysqli_fetch_assoc($res)) {
            $pid = (int) $row['id'];
            $cantidad = $_SESSION['carrito'][$pid] ?? 1;
            $subtotal = ((float) $row['precio']) * $cantidad;
            $total_compra += $subtotal;
            $items[] = [
                'id'        => $pid,
                'nombre'    => $row['nombre'],
                'precio'    => (float) $row['precio'],
                'stock'     => (int) $row['stock'],
                'imagen'    => $row['imagen'],
                'categoria' => $row['categoria'],
                'cantidad'  => $cantidad,
                'subtotal'  => $subtotal,
            ];
        }
    }
}

$titulo_pagina = 'Carrito de compras';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Carrito de Compras</h1>
        <p class="text-secondary mb-0">Revise sus artículos antes de finalizar la compra.</p>
    </div>
    <a href="<?= url('comprador/catalogo.php') ?>" class="btn btn-outline-secondary">
        &larr; Continuar comprando
    </a>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
<?php endif; ?>

<?php if ($exito !== ''): ?>
    <div class="alert alert-success" role="alert"><?= e($exito) ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>
    <div class="card p-5 text-center shadow-sm border-0 my-4">
        <div class="mb-3">
            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" fill="#6c757d" class="bi bi-cart-x" viewBox="0 0 16 16">
              <path d="M7.354 5.646a.5.5 0 1 0-.708.708L7.793 7.5 6.646 8.646a.5.5 0 1 0 .708.708L8.5 8.207l1.146 1.147a.5.5 0 0 0 .708-.708L9.207 7.5l1.147-1.146a.5.5 0 0 0-.708-.708L8.5 6.793z"/>
              <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
        </div>
        <h4>Su carrito está vacío</h4>
        <p class="text-muted">Aún no ha agregado productos para comprar.</p>
        <div>
            <a href="<?= url('comprador/catalogo.php') ?>" class="btn btn-naranja">Explorar catálogo</a>
        </div>
    </div>
<?php else: ?>
    <form method="post" action="<?= url('comprador/carrito.php') ?>">
        <input type="hidden" name="accion" value="actualizar">
        
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 80px;"></th>
                                    <th>Producto</th>
                                    <th class="text-end">Precio</th>
                                    <th style="width: 130px;" class="text-center">Cantidad</th>
                                    <th class="text-end">Subtotal</th>
                                    <th style="width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <img src="<?= imagen_producto($item['imagen']) ?>" alt="" width="56" height="56" style="object-fit: contain;" class="rounded border bg-white p-1">
                                        </td>
                                        <td>
                                            <div class="fw-semibold"><?= e($item['nombre']) ?></div>
                                            <small class="text-muted"><?= e($item['categoria'] ?? '') ?></small>
                                        </td>
                                        <td class="text-end"><?= dinero($item['precio']) ?></td>
                                        <td>
                                            <input type="number" name="cantidades[<?= $item['id'] ?>]" class="form-control form-control-sm text-center" value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock'] ?>">
                                            <small class="text-muted d-block text-center mt-1">(Max: <?= $item['stock'] ?>)</small>
                                        </td>
                                        <td class="text-end fw-bold"><?= dinero($item['subtotal']) ?></td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-link text-danger p-0" onclick="if(confirm('¿Eliminar <?= e(addslashes($item['nombre'])) ?> del carrito?')) document.getElementById('form-del-<?= $item['id'] ?>').submit();" title="Eliminar">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5m3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0z"/>
                                                  <path d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4zM2.5 3h11V2h-11z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between p-3">
                        <button type="submit" class="btn btn-sm btn-outline-secondary">Actualizar cantidades</button>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="if(confirm('¿Desea vaciar el carrito?')) document.getElementById('form-vaciar').submit();">Vaciar carrito</button>
                    </div>
                </div>
            </div>

            <!-- Resumen de compra -->
            <div class="col-lg-4">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-oscuro text-white py-3">
                        <h2 class="h5 mb-0">Resumen del pedido</h2>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span><?= dinero($total_compra) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Impuestos (IVA incl.)</span>
                            <span>$0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-4">
                            <span class="fs-5 fw-bold">Total</span>
                            <span class="fs-4 fw-bold text-naranja"><?= dinero($total_compra) ?></span>
                        </div>
                        <button type="button" class="btn btn-naranja w-100 py-2 fs-6 fw-bold" onclick="if(confirm('¿Desea confirmar la compra por <?= dinero($total_compra) ?>?')) document.getElementById('form-comprar').submit();">
                            Generar Factura y Pagar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>

    <!-- Formularios auxiliares ocultos -->
    <?php foreach ($items as $item): ?>
        <form id="form-del-<?= $item['id'] ?>" method="post" action="<?= url('comprador/carrito.php') ?>" style="display:none;">
            <input type="hidden" name="accion" value="eliminar">
            <input type="hidden" name="producto_id" value="<?= $item['id'] ?>">
        </form>
    <?php endforeach; ?>

    <form id="form-vaciar" method="post" action="<?= url('comprador/carrito.php') ?>" style="display:none;">
        <input type="hidden" name="accion" value="vaciar">
    </form>

    <form id="form-comprar" method="post" action="<?= url('comprador/carrito.php') ?>" style="display:none;">
        <input type="hidden" name="accion" value="comprar">
    </form>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
