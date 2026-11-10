<?php
/**
 * Ferretería El Tornillo — panel de administración
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('admin');

// Estadísticas generales
$r_users = mysqli_query($conn, "SELECT COUNT(*) AS total,
                                       SUM(CASE WHEN rol = 'admin' THEN 1 ELSE 0 END) AS total_admin,
                                       SUM(CASE WHEN rol = 'vendedor' THEN 1 ELSE 0 END) AS total_vendedor,
                                       SUM(CASE WHEN rol = 'comprador' THEN 1 ELSE 0 END) AS total_comprador
                                FROM usuarios");
$users_stat = mysqli_fetch_assoc($r_users);
$total_users      = (int) ($users_stat['total'] ?? 0);
$total_admin      = (int) ($users_stat['total_admin'] ?? 0);
$total_vendedor   = (int) ($users_stat['total_vendedor'] ?? 0);
$total_comprador  = (int) ($users_stat['total_comprador'] ?? 0);

$r_prods = mysqli_query($conn, "SELECT COUNT(*) AS total, COALESCE(SUM(stock), 0) AS total_stock FROM productos");
$prod_stat = mysqli_fetch_assoc($r_prods);
$total_prods = (int) ($prod_stat['total'] ?? 0);
$total_stock = (int) ($prod_stat['total_stock'] ?? 0);

$r_fac = mysqli_query($conn, "SELECT COUNT(*) AS total, COALESCE(SUM(total), 0) AS total_ventas FROM facturas");
$fac_stat = mysqli_fetch_assoc($r_fac);
$total_facturas = (int) ($fac_stat['total'] ?? 0);
$total_ventas   = (float) ($fac_stat['total_ventas'] ?? 0.0);

// Últimos 5 usuarios registrados
$r_last_users = mysqli_query($conn, "SELECT id, nombre, correo, rol, creado_en FROM usuarios ORDER BY creado_en DESC LIMIT 5");
$last_users = ($r_last_users !== false) ? mysqli_fetch_all($r_last_users, MYSQLI_ASSOC) : [];

// Últimas 5 facturas
$r_last_fac = mysqli_query($conn, "SELECT f.id, f.fecha, f.total, u.nombre AS comprador
                                   FROM facturas f
                                   JOIN usuarios u ON u.id = f.comprador_id
                                   ORDER BY f.fecha DESC LIMIT 5");
$last_fac = ($r_last_fac !== false) ? mysqli_fetch_all($r_last_fac, MYSQLI_ASSOC) : [];

$titulo_pagina = 'Panel de Administración';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Panel de Control</h1>
        <p class="text-secondary mb-0">Resumen administrativo general del sistema.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('admin/productos.php') ?>" class="btn btn-outline-secondary">Gestionar Productos</a>
        <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-naranja">Gestionar Usuarios</a>
    </div>
</div>

<!-- Tarjetas de métricas -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid var(--naranja) !important;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Usuarios registrados</div>
                <div class="fs-2 fw-bold text-dark"><?= $total_users ?></div>
                <div class="small text-muted mt-1">
                    <?= $total_comprador ?> compradores &bull; <?= $total_vendedor ?> vendedores
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="<?= url('admin/usuarios.php') ?>" class="small text-decoration-none text-naranja fw-semibold">
                    Ver usuarios &rarr;
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #198754 !important;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Productos en catálogo</div>
                <div class="fs-2 fw-bold text-dark"><?= $total_prods ?></div>
                <div class="small text-muted mt-1">
                    <?= $total_stock ?> unidades en stock global
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="<?= url('admin/productos.php') ?>" class="small text-decoration-none text-success fw-semibold">
                    Ver inventario &rarr;
                </a>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #0d6efd !important;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Total Facturas</div>
                <div class="fs-2 fw-bold text-dark"><?= $total_facturas ?></div>
                <div class="small text-muted mt-1">Órdenes procesadas</div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <span class="small text-muted">Historial global</span>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card shadow-sm border-0 h-100" style="border-left: 4px solid #ffc107 !important;">
            <div class="card-body">
                <div class="text-muted small text-uppercase fw-semibold mb-1">Ventas Totales</div>
                <div class="fs-2 fw-bold text-naranja"><?= dinero($total_ventas) ?></div>
                <div class="small text-muted mt-1">Facturación acumulada</div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <span class="small text-muted">Ingresos del sistema</span>
            </div>
        </div>
    </div>
</div>

<!-- Tablas de actividad reciente -->
<div class="row g-4">
    <!-- Últimos Usuarios -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold">Últimos usuarios registrados</h2>
                <a href="<?= url('admin/usuarios.php') ?>" class="btn btn-sm btn-outline-secondary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nombre / Correo</th>
                                <th>Rol</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($last_users)): ?>
                                <tr><td colspan="3" class="text-center text-muted py-3">No hay usuarios.</td></tr>
                            <?php else: ?>
                                <?php foreach ($last_users as $u): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?= e($u['nombre']) ?></div>
                                            <small class="text-muted"><?= e($u['correo']) ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?= $u['rol'] === 'admin' ? 'bg-danger' : ($u['rol'] === 'vendedor' ? 'bg-primary' : 'bg-secondary') ?>">
                                                <?= e($u['rol']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?= fecha_legible($u['creado_en']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Últimas Facturas -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0 fw-bold">Últimas facturas emitidas</h2>
                <span class="badge bg-light text-dark border">Historial</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th># Factura</th>
                                <th>Comprador</th>
                                <th>Fecha</th>
                                <th class="text-end">Monto</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($last_fac)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-3">No hay facturas.</td></tr>
                            <?php else: ?>
                                <?php foreach ($last_fac as $fac): ?>
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
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
