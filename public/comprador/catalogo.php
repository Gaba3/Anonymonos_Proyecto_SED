<?php
/**
 * Ferretería El Tornillo — catálogo de comprador
 */

require_once __DIR__ . '/../../includes/auth.php';
requerir_rol('comprador');

$q = $_GET['q'] ?? '';
$categoria_id = (int) ($_GET['cat'] ?? 0);

// Consulta de productos disponibles
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
$productos = ($resultado !== false) ? mysqli_fetch_all($resultado, MYSQLI_ASSOC) : [];

$categorias = [];
$res_cat = mysqli_query($conn, 'SELECT id, nombre FROM categorias ORDER BY nombre');
if ($res_cat !== false) {
    $categorias = mysqli_fetch_all($res_cat, MYSQLI_ASSOC);
}

// Cantidad en el carrito
$items_carrito = 0;
if (isset($_SESSION['carrito']) && is_array($_SESSION['carrito'])) {
    $items_carrito = array_sum($_SESSION['carrito']);
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
    return $stock <= 10 ? "Últimas $stock unidades" : "$stock disponibles";
}

$titulo_pagina = 'Comprar productos';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
    <div>
        <h1 class="titulo-seccion h2 mb-1">Catálogo de Compras</h1>
        <p class="text-secondary mb-0">Seleccione productos y agréguelos a su carrito de compras.</p>
    </div>
    <div>
        <a href="<?= url('comprador/carrito.php') ?>" class="btn btn-naranja position-relative">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" class="bi bi-cart3 me-1" viewBox="0 0 16 16">
              <path d="M0 1.5A.5.5 0 0 1 .5 1H2a.5.5 0 0 1 .485.379L2.89 3H14.5a.5.5 0 0 1 .49.598l-1 5a.5.5 0 0 1-.465.401l-9.397.472L4.415 11H13a.5.5 0 0 1 0 1H4a.5.5 0 0 1-.491-.408L2.01 3.607 1.61 2H.5a.5.5 0 0 1-.5-.5M3.102 4l.84 4.479 9.144-.459L13.89 4zM5 12a2 2 0 1 0 0 4 2 2 0 0 0 0-4m7 0a2 2 0 1 0 0 4 2 2 0 0 0 0-4m-7 1a1 1 0 1 1 0 2 1 1 0 0 1 0-2m7 0a1 1 0 1 1 0 2 1 1 0 0 1 0-2"/>
            </svg>
            Ver Carrito
            <?php if ($items_carrito > 0): ?>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $items_carrito ?>
                </span>
            <?php endif; ?>
        </a>
    </div>
</div>

<?php if (isset($_GET['agregado'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Producto agregado al carrito exitosamente.
        <a href="<?= url('comprador/carrito.php') ?>" class="alert-link ms-2">Ir al carrito</a>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
    </div>
<?php endif; ?>

<!-- Barra de búsqueda y categorías -->
<div class="card mb-4 shadow-sm border-0">
    <div class="card-body p-3">
        <form class="row g-2 align-items-center" method="get" action="<?= url('comprador/catalogo.php') ?>">
            <div class="col-md-7">
                <input class="form-control" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre...">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="cat">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categorias as $c): ?>
                        <option value="<?= (int) $c['id'] ?>" <?= $categoria_id === (int) $c['id'] ? 'selected' : '' ?>>
                            <?= e($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-naranja w-100" type="submit">Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Listado de productos -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 row-cols-xl-4 g-4">
    <?php if (empty($productos)): ?>
        <div class="col-12">
            <div class="alert alert-info text-center py-5">
                <p class="mb-0">No se encontraron productos disponibles con los criterios seleccionados.</p>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($productos as $p): ?>
            <?php $agotado = ((int) $p['stock'] <= 0); ?>
            <div class="col">
                <div class="card card-producto h-100 shadow-sm d-flex flex-column">
                    <div class="caja-imagen">
                        <img src="<?= imagen_producto($p['imagen']) ?>" alt="<?= e($p['nombre']) ?>" loading="lazy">
                    </div>
                    <div class="card-body d-flex flex-column">
                        <div class="text-muted small mb-1"><?= e($p['categoria'] ?? 'Sin categoría') ?></div>
                        <h2 class="h6 card-title mb-2"><?= e($p['nombre']) ?></h2>
                        <p class="card-text text-secondary small flex-grow-1">
                            <?= e($p['descripcion'] ?? '') ?>
                        </p>
                        <div class="d-flex justify-content-between align-items-baseline mt-2 mb-3">
                            <span class="precio"><?= dinero($p['precio']) ?></span>
                            <span class="small <?= clase_stock((int) $p['stock']) ?>">
                                <?= texto_stock((int) $p['stock']) ?>
                            </span>
                        </div>
                        <form method="post" action="<?= url('comprador/carrito.php') ?>" class="mt-auto">
                            <input type="hidden" name="accion" value="agregar">
                            <input type="hidden" name="producto_id" value="<?= (int) $p['id'] ?>">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">Cant.</span>
                                <input type="number" name="cantidad" class="form-control" value="1" min="1" max="<?= max(1, (int) $p['stock']) ?>" <?= $agotado ? 'disabled' : '' ?>>
                                <button class="btn btn-naranja" type="submit" <?= $agotado ? 'disabled' : '' ?>>
                                    Agregar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
