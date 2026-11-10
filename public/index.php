<?php
/**
 * Ferretería El Tornillo — catálogo público y buscador
 *
 * Página de entrada del sitio. No requiere sesión: cualquier visitante
 * puede ver el catálogo y buscar productos. La barra de navegación
 * cambia sola si hay una sesión iniciada.
 *
 * ---------------------------------------------------------------------
 * CONTIENE LA VULNERABILIDAD V1 (INYECCIÓN SQL) — NO CORREGIR
 * ---------------------------------------------------------------------
 * El término de búsqueda se concatena en la consulta. Está documentado
 * en la sección 5 del README.md. La otra mitad de V1 está en login.php.
 */

require_once __DIR__ . '/../includes/db.php';

$q = $_GET['q'] ?? '';

$categoria_id = (int) ($_GET['cat'] ?? 0);

// ---------------------------------------------------------------------
// Consulta del catálogo
// ---------------------------------------------------------------------
// La entrada $q se mete directamente dentro de la cadena SQL y se
// ejecuta con mysqli_query(), sin mysqli_prepare() ni bind_param() y sin
// escapar los caracteres especiales.
//
// Mitigación correcta (deliberadamente NO aplicada):
//     $stmt = mysqli_prepare($conn, "... WHERE p.nombre LIKE ?");
//     mysqli_stmt_bind_param($stmt, 's', "%$q%");
$sql = "SELECT p.id, p.nombre, p.descripcion, p.precio, p.stock, p.imagen,
               c.nombre AS categoria
        FROM productos p
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE p.nombre LIKE '%$q%'";

if ($categoria_id > 0) {
    $sql .= " AND p.categoria_id = $categoria_id";
}

$sql .= ' ORDER BY p.id';

$resultado = mysqli_query($conn, $sql);

// Si la consulta falla, el error de MySQL se muestra en pantalla más
// abajo. Es parte del diseño de V1: facilita que el atacante identifique
// la inyección durante la etapa de ataque.
$consulta_fallo = ($resultado === false);
$productos = $consulta_fallo ? [] : mysqli_fetch_all($resultado, MYSQLI_ASSOC);

$categorias = [];
$res_cat = mysqli_query($conn, 'SELECT id, nombre FROM categorias ORDER BY nombre');

if ($res_cat !== false) {
    $categorias = mysqli_fetch_all($res_cat, MYSQLI_ASSOC);
}

function clase_stock(int $stock): string
{
    if ($stock <= 0) {
        return 'stock-agotado';
    }

    return $stock <= 10 ? 'stock-bajo' : 'stock-ok';
}

function texto_stock(int $stock): string
{
    if ($stock <= 0) {
        return 'Agotado';
    }

    return $stock <= 10 ? "Últimas $stock unidades" : "$stock en existencia";
}

$titulo_pagina = 'Catálogo';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="p-4 p-md-5 mb-4 rounded-3 bg-white border">
    <div class="row align-items-center g-3">
        <div class="col-md-7">
            <h1 class="titulo-seccion h2 mb-2">Catálogo de productos</h1>
            <p class="text-secondary mb-0"><?= e(APP_LEMA) ?></p>
        </div>
        <div class="col-md-5">
            <form class="d-flex gap-2" method="get" action="<?= url('index.php') ?>" role="search">
                <input class="form-control" type="search" name="q"
                       value="<?= e($q) ?>"
                       placeholder="Buscar producto..." aria-label="Buscar producto">
                <?php if ($categoria_id > 0): ?>
                    <input type="hidden" name="cat" value="<?= (int) $categoria_id ?>">
                <?php endif; ?>
                <button class="btn btn-naranja flex-shrink-0" type="submit">Buscar</button>
            </form>
        </div>
    </div>
</div>

<div class="d-flex flex-wrap gap-2 mb-4">
    <a class="btn btn-sm <?= $categoria_id === 0 ? 'btn-naranja' : 'btn-outline-naranja' ?>"
       href="<?= url('index.php') . ($q !== '' ? '?q=' . urlencode($q) : '') ?>">
        Todas
    </a>
    <?php foreach ($categorias as $c): ?>
        <?php
        $enlace = url('index.php') . '?cat=' . (int) $c['id']
            . ($q !== '' ? '&q=' . urlencode($q) : '');
        ?>
        <a class="btn btn-sm <?= $categoria_id === (int) $c['id'] ? 'btn-naranja' : 'btn-outline-naranja' ?>"
           href="<?= e($enlace) ?>">
            <?= e($c['nombre']) ?>
        </a>
    <?php endforeach; ?>
</div>

<?php if ($consulta_fallo): ?>
    <?php error_sql($conn, $sql); ?>

<?php elseif ($q !== '' || $categoria_id > 0): ?>
    <p class="text-secondary">
        <?= count($productos) ?>
        <?= count($productos) === 1 ? 'producto encontrado' : 'productos encontrados' ?>
        <?php if ($q !== ''): ?>
            para <strong><?= e($q) ?></strong>
        <?php endif; ?>
    </p>
<?php endif; ?>

<?php if (!$consulta_fallo && count($productos) === 0): ?>
    <div class="alert alert-light border text-center py-5">
        <p class="h5 mb-2">No se encontraron productos</p>
        <p class="text-secondary mb-3">Pruebe con otro término de búsqueda u otra categoría.</p>
        <a class="btn btn-outline-naranja" href="<?= url('index.php') ?>">Ver todo el catálogo</a>
    </div>
<?php endif; ?>

<div class="row g-3 g-lg-4">
    <?php foreach ($productos as $p): ?>
        <?php $stock = (int) $p['stock']; ?>
        <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
            <div class="card card-producto">
                <div class="caja-imagen">
                    <img src="<?= e(imagen_producto($p['imagen'])) ?>"
                         alt="<?= e($p['nombre']) ?>" loading="lazy">
                </div>
                <div class="card-body d-flex flex-column">
                    <?php if (!empty($p['categoria'])): ?>
                        <p class="small text-secondary text-uppercase mb-1"><?= e($p['categoria']) ?></p>
                    <?php endif; ?>

                    <h2 class="h6 mb-2"><?= e($p['nombre']) ?></h2>

                    <p class="small text-secondary flex-grow-1">
                        <?php
                        $desc = (string) $p['descripcion'];
                        echo e(mb_strlen($desc) > 95 ? mb_substr($desc, 0, 95) . '…' : $desc);
                        ?>
                    </p>

                    <div class="d-flex align-items-end justify-content-between mt-2">
                        <span class="precio"><?= e(dinero($p['precio'])) ?></span>
                        <span class="small <?= clase_stock($stock) ?>"><?= e(texto_stock($stock)) ?></span>
                    </div>

                    <?php if (!esta_autenticado()): ?>
                        <a class="btn btn-sm btn-outline-naranja mt-3" href="<?= url('login.php') ?>">
                            Inicie sesión para comprar
                        </a>
                    <?php elseif (rol_actual() === 'comprador'): ?>
                        <a class="btn btn-sm btn-naranja mt-3" href="<?= url('comprador/catalogo.php') ?>">
                            Comprar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
