<?php
/**
 * Ferretería El Tornillo - historial global de facturas emitidas
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('admin');

$sql = "SELECT f.id, f.fecha, f.total, u.nombre AS comprador
        FROM facturas f
        JOIN usuarios u ON u.id = f.comprador_id
        ORDER BY f.fecha DESC, f.id DESC";

$resultado = mysqli_query($conn, $sql);
$facturas = ($resultado !== false) ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];

$titulo_pagina = 'Facturas emitidas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Facturas emitidas</h1>
        <p class="text-secondary mb-0">Historial de facturas de todos los compradores, de la más reciente a la más antigua.</p>
    </div>
    <a href="<?= url('admin/panel.php') ?>" class="btn btn-outline-secondary">&larr; Panel</a>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if ($resultado === false): ?>
            <?php error_sql($conn, $sql); ?>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th># Factura</th>
                            <th>Comprador</th>
                            <th>Fecha</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($facturas)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No hay facturas emitidas.</td></tr>
                        <?php else: ?>
                            <?php foreach ($facturas as $fac): ?>
                                <tr>
                                    <td class="fw-bold">#<?= str_pad((string)$fac['id'], 6, '0', STR_PAD_LEFT) ?></td>
                                    <td><?= e($fac['comprador']) ?></td>
                                    <td class="small text-muted"><?= fecha_legible($fac['fecha']) ?></td>
                                    <td class="text-end fw-bold text-naranja"><?= dinero($fac['total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
