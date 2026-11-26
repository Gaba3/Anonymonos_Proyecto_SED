<?php
// Ferretería El Tornillo — pie de página común
?>
</main>

<?php
/*
 * PISTA SUTIL para la etapa de ataque (V2 — base de datos expuesta).
 *
 * Nota de infraestructura que se dejó en el HTML como si fuera un comentario
 * olvidado de desarrollo. Un atacante que revise el código fuente de la
 * página descubre que los datos viven en un MySQL aparte (contenedor Docker)
 * y que escucha en el 3306; eso lo lleva a escanear la red y a conectarse
 * directamente al puerto expuesto. Es una pista, no una instrucción: no
 * revela ni la cuenta ni la contraseña.
 */
?>
<!-- infra lab: apache2 + php 8.3 · los datos se guardan en mysql 8 (contenedor docker, :3306) · pendiente cerrar el puerto -->

<footer class="bg-oscuro text-white-50 mt-auto py-4">
    <div class="container">
        <div class="row gy-3 align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-2 mb-1">
                    <img src="<?= url('assets/img/logo.svg') ?>" alt="" width="24" height="24">
                    <span class="fw-semibold text-white"><?= e(APP_NOMBRE) ?></span>
                </div>
                <p class="small mb-0"><?= e(APP_LEMA) ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="small mb-0">
                    &copy; <?= date('Y') ?> <?= e(APP_NOMBRE) ?>. Todos los derechos reservados.
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="<?= url('assets/js/bootstrap.bundle.min.js') ?>"></script>
</body>
</html>
