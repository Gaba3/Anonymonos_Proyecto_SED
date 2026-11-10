<?php
// Ferretería El Tornillo — pie de página común
?>
</main>

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
