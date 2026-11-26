<?php
/**
 * Ferretería El Tornillo — historial de facturas del comprador
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('comprador');

$comprador_id = usuario_id();

// Consultar facturas del usuario autenticado
$sql = "SELECT f.id, f.fecha, f.total,
               (SELECT COUNT(*) FROM detalle_factura df WHERE df.factura_id = f.id) AS num_articulos
        FROM facturas f
        WHERE f.comprador_id = $comprador_id
        ORDER BY f.fecha DESC";

$resultado = mysqli_query($conn, $sql);
$facturas = ($resultado !== false) ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];

$titulo_pagina = 'Mis facturas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Mis Facturas</h1>
        <p class="text-secondary mb-0">Consulte el registro de todas sus compras realizadas.</p>
    </div>
    <a href="<?= url('comprador/catalogo.php') ?>" class="btn btn-naranja">
        Comprar más productos
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($facturas)): ?>
            <div class="text-center py-5">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="#6c757d" class="bi bi-receipt mb-3" viewBox="0 0 16 16">
                  <path d="M1.92.506a.5.5 0 0 1 .434.14L3 1.293l.646-.647a.5.5 0 0 1 .708 0L5 1.293l.646-.647a.5.5 0 0 1 .708 0L7 1.293l.646-.647a.5.5 0 0 1 .708 0L9 1.293l.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .801.13l.5 1A.5.5 0 0 1 15 2v12a.5.5 0 0 1-.053.224l-.5 1a.5.5 0 0 1-.8.13L13 14.707l-.646.647a.5.5 0 0 1-.708 0L11 14.707l-.646.647a.5.5 0 0 1-.708 0L9 14.707l-.646.647a.5.5 0 0 1-.708 0L7 14.707l-.646.647a.5.5 0 0 1-.708 0L5 14.707l-.646.647a.5.5 0 0 1-.708 0L3 14.707l-.646.647a.5.5 0 0 1-.801-.13l-.5-1A.5.5 0 0 1 1 14V2a.5.5 0 0 1 .053-.224l.5-1a.5.5 0 0 1 .367-.27m.216 1.439L2 2.17v11.66l.136.273.4-.4a.5.5 0 0 1 .707 0l.647.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.646.647.646-.647a.5.5 0 0 1 .708 0l.4.4.136-.273V2.17l-.136-.273-.4.4a.5.5 0 0 1-.707 0L11.5 1.65l-.646.647a.5.5 0 0 1-.708 0L9.5 1.65l-.646.647a.5.5 0 0 1-.708 0L7.5 1.65l-.646.647a.5.5 0 0 1-.708 0L5.5 1.65l-.646.647a.5.5 0 0 1-.708 0L3.5 1.65l-.646.647a.5.5 0 0 1-.708 0z"/>
                  <path d="M3 4.5a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h6a.5.5 0 1 1 0 1h-6a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h4a.5.5 0 0 1 0 1h-4a.5.5 0 0 1-.5-.5m8-6a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5m0 2a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 0 1h-1a.5.5 0 0 1-.5-.5"/>
                </svg>
                <h4 class="h5">No tiene compras registradas</h4>
                <p class="text-muted small">Cuando realice un pedido aparecerá en esta lista.</p>
                <a href="<?= url('comprador/catalogo.php') ?>" class="btn btn-sm btn-naranja">Ver catálogo</a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-oscuro text-white">
                        <tr>
                            <th style="width: 120px;">N° Factura</th>
                            <th>Fecha</th>
                            <th class="text-center">Artículos</th>
                            <th class="text-end">Total</th>
                            <th class="text-center" style="width: 140px;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($facturas as $f): ?>
                            <tr>
                                <td class="fw-bold">#<?= str_pad((string)$f['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                <td><?= fecha_legible($f['fecha']) ?></td>
                                <td class="text-center">
                                    <span class="badge bg-light text-dark border">
                                        <?= (int) $f['num_articulos'] ?> artículo<?= (int) $f['num_articulos'] === 1 ? '' : 's' ?>
                                    </span>
                                </td>
                                <td class="text-end fw-bold text-naranja"><?= dinero($f['total']) ?></td>
                                <td class="text-center">
                                    <a href="<?= url('comprador/factura.php?id=' . (int)$f['id']) ?>" class="btn btn-sm btn-outline-naranja">
                                        Ver detalle
                                    </a>
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
