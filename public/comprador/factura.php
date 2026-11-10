<?php
/**
 * Ferretería El Tornillo — detalle de factura
 *
 * Curso: Seguridad en entornos de desarrollo (UCA).
 *
 * Muestra el detalle de una factura: comprador, fecha, productos y total.
 *
 * ---------------------------------------------------------------------
 * CONTIENE LA VULNERABILIDAD V3 (IDOR) — NO CORREGIR
 * ---------------------------------------------------------------------
 * La vista comprueba que el usuario tenga rol de 'comprador', pero NO
 * valida que la factura solicitada le pertenezca. Cualquier comprador
 * puede acceder a las facturas de otros cambiando el parámetro id en la
 * URL (?id=X). Documentado en la sección 5 del README.md.
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('comprador'); // Solo valida el rol, no la propiedad del recurso (VULNERABILIDAD V3)

$id = (int) ($_GET['id'] ?? 0);

// ---------------------------------------------------------------------
// Consulta de la factura — VULNERABILIDAD V3 (IDOR)
// ---------------------------------------------------------------------
// Falta deliberadamente: AND f.comprador_id = usuario_id()
$sql = "SELECT f.id, f.fecha, f.total, u.nombre, u.correo
        FROM facturas f
        JOIN usuarios u ON u.id = f.comprador_id
        WHERE f.id = $id";

$resultado = mysqli_query($conn, $sql);
$factura = ($resultado !== false) ? mysqli_fetch_assoc($resultado) : null;

$detalles = [];
if ($factura !== null) {
    $sql_det = "SELECT d.cantidad, d.precio_unitario, d.subtotal, p.nombre, p.imagen, c.nombre AS categoria
                FROM detalle_factura d
                JOIN productos p ON p.id = d.producto_id
                LEFT JOIN categorias c ON c.id = p.categoria_id
                WHERE d.factura_id = $id";
    $res_det = mysqli_query($conn, $sql_det);
    if ($res_det !== false) {
        $detalles = mysqli_fetch_all($res_det, MYSQLI_ASSOC);
    }
}

$es_nueva = isset($_GET['nueva']);

$titulo_pagina = $factura ? "Factura #{$factura['id']}" : "Factura no encontrada";
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if ($es_nueva): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <strong>¡Compra realizada con éxito!</strong> Su factura ha sido generada correctamente.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<?php if (!$factura): ?>
    <div class="alert alert-warning text-center py-5 shadow-sm">
        <h3 class="h4">Factura no encontrada</h3>
        <p class="text-muted">La factura solicitada (#<?= $id ?>) no existe en el sistema.</p>
        <a href="<?= url('comprador/historial.php') ?>" class="btn btn-naranja">Ver mi historial de facturas</a>
    </div>
<?php else: ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="<?= url('comprador/historial.php') ?>" class="text-decoration-none text-secondary">
                &larr; Volver a Mis Facturas
            </a>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-outline-secondary" onclick="window.print();">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-printer me-1" viewBox="0 0 16 16">
                  <path d="M2.5 8a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1"/>
                  <path d="M5 1a2 2 0 0 0-2 2v2H2a2 2 0 0 0-2 2v3a2 2 0 0 0 2 2h1v1a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2v-1h1a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-1V3a2 2 0 0 0-2-2zM4 3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2H4zm1 5a2 2 0 0 0-2 2v1H2a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v3a1 1 0 0 1-1 1h-1v-1a2 2 0 0 0-2-2zm7 2v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-3a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1"/>
                </svg>
                Imprimir
            </button>
            <a href="<?= url('comprador/catalogo.php') ?>" class="btn btn-naranja">Seguir comprando</a>
        </div>
    </div>

    <div class="card shadow border-0 p-4 p-md-5 bg-white">
        <!-- Encabezado de Factura -->
        <div class="row align-items-center pb-4 border-bottom mb-4">
            <div class="col-sm-6">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="40" height="40">
                    <h2 class="h3 fw-bold mb-0 text-naranja"><?= e(APP_NOMBRE) ?></h2>
                </div>
                <p class="text-muted small mb-0"><?= e(APP_LEMA) ?></p>
                <p class="text-muted small mb-0">San Salvador, El Salvador &bull; Tel: (503) 2210-0000</p>
            </div>
            <div class="col-sm-6 text-sm-end mt-3 mt-sm-0">
                <span class="badge bg-oscuro fs-6 px-3 py-2">FACTURA COMERCIAL</span>
                <div class="mt-2">
                    <span class="text-muted">Número:</span>
                    <strong class="fs-5">#<?= str_pad((string)$factura['id'], 6, '0', STR_PAD_LEFT) ?></strong>
                </div>
                <div class="text-muted small">
                    Fecha: <strong><?= fecha_legible($factura['fecha']) ?></strong>
                </div>
            </div>
        </div>

        <!-- Datos del cliente -->
        <div class="card bg-light border-0 mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <small class="text-muted text-uppercase fw-semibold d-block">Facturado a:</small>
                        <span class="fs-5 fw-bold"><?= e($factura['nombre']) ?></span>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <small class="text-muted text-uppercase fw-semibold d-block">Correo de contacto:</small>
                        <span class="fs-6"><?= e($factura['correo']) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detalle de productos -->
        <div class="table-responsive mb-4">
            <table class="table table-bordered align-middle">
                <thead class="bg-oscuro text-white">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Descripción del Producto</th>
                        <th style="width: 120px;" class="text-center">Cantidad</th>
                        <th style="width: 140px;" class="text-end">Precio Unitario</th>
                        <th style="width: 140px;" class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($detalles)): ?>
                        <tr>
                            <td colspan="5" class="text-center text-muted py-3">No hay renglones registrados para esta factura.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($detalles as $idx => $item): ?>
                            <tr>
                                <td class="text-center"><?= $idx + 1 ?></td>
                                <td>
                                    <span class="fw-semibold"><?= e($item['nombre']) ?></span>
                                    <?php if (!empty($item['categoria'])): ?>
                                        <small class="text-muted d-block"><?= e($item['categoria']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center"><?= (int) $item['cantidad'] ?></td>
                                <td class="text-end"><?= dinero($item['precio_unitario']) ?></td>
                                <td class="text-end fw-semibold"><?= dinero($item['subtotal']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Subtotal:</th>
                        <td class="text-end"><?= dinero($factura['total']) ?></td>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-end">IVA (13% incluido):</th>
                        <td class="text-end"><?= dinero((float)$factura['total'] * 0.13) ?></td>
                    </tr>
                    <tr class="table-light">
                        <th colspan="4" class="text-end fs-5">TOTAL:</th>
                        <td class="text-end fs-5 fw-bold text-naranja"><?= dinero($factura['total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <div class="row pt-4 text-muted small border-top">
            <div class="col-md-8">
                <p class="mb-1"><strong>Términos y condiciones:</strong> Gracias por su compra en Ferretería El Tornillo.</p>
                <p class="mb-0">Para reclamos o garantías, conserve esta factura digital.</p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge text-bg-success px-3 py-2">PAGADA</span>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
