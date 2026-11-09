<?php
// Ferretería El Tornillo — conexión a MySQL

require_once __DIR__ . '/config.php';

mysqli_report(MYSQLI_REPORT_OFF);

$conn = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

if (!$conn) {
    http_response_code(503);

    $detalle = MOSTRAR_ERRORES
        ? mysqli_connect_error()
        : 'Servicio no disponible.';

    exit(
        '<!doctype html><html lang="es"><head><meta charset="utf-8">'
        . '<title>Error de conexión</title></head><body>'
        . '<h1>No se pudo conectar a la base de datos</h1>'
        . '<p>' . e($detalle) . '</p>'
        . '<p>Verifique que el contenedor de MySQL esté arriba: '
        . '<code>cd docker &amp;&amp; docker compose ps</code></p>'
        . '</body></html>'
    );
}

mysqli_set_charset($conn, 'utf8mb4');

function error_sql(mysqli $conn, string $sql = ''): void
{
    if (!MOSTRAR_ERRORES) {
        echo '<div class="alert alert-danger" role="alert">'
            . 'Ocurrió un error al consultar la base de datos.'
            . '</div>';

        return;
    }

    echo '<div class="alert alert-danger" role="alert">'
        . '<strong>Error de MySQL:</strong> ' . e(mysqli_error($conn));

    if ($sql !== '') {
        echo '<pre class="mt-2 mb-0 small">' . e($sql) . '</pre>';
    }

    echo '</div>';
}
