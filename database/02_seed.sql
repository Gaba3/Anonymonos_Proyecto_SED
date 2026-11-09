-- =====================================================================
-- Ferretería El Tornillo — datos de prueba
--
-- Credenciales de prueba: sección 4 del README.md.
-- =====================================================================

USE ferreteria;

-- Imprescindible: sin esto los acentos de los nombres de productos y
-- usuarios se guardan con doble codificacion. Ver 01_schema.sql.
SET NAMES utf8mb4;

-- ---------------------------------------------------------------------
-- usuarios
-- ---------------------------------------------------------------------
-- Las contraseñas se guardan como MD5, que es lo que compara la consulta
-- de login. Se usa la función MD5() de MySQL para que la contraseña en 
-- claro quede visible aquí y no haya que calcular el hash a mano.
INSERT INTO usuarios (id, nombre, correo, password, rol) VALUES
    (1, 'Administrador El Tornillo', 'admin@eltornillo.local',      MD5('Admin123'),     'admin'),
    (2, 'Mario Alvarenga',           'vendedor@eltornillo.local',   MD5('Vendedor123'),  'vendedor'),
    (3, 'Ana Martínez',              'comprador@eltornillo.local',  MD5('Comprador123'), 'comprador'),
    (4, 'José Ramírez',              'comprador2@eltornillo.local', MD5('Comprador123'), 'comprador');

-- ---------------------------------------------------------------------
-- categorias
-- ---------------------------------------------------------------------
INSERT INTO categorias (id, nombre) VALUES
    (1, 'Herramientas'),
    (2, 'Material eléctrico'),
    (3, 'Plomería'),
    (4, 'Pinturas'),
    (5, 'Tornillería');

-- ---------------------------------------------------------------------
-- productos
-- ---------------------------------------------------------------------
-- Todos los publica el vendedor de prueba (id = 2). Precios en dólares.
-- `imagen` queda en NULL: las vistas deben mostrar una imagen de
-- reemplazo mientras no se suban fotos desde la vista de vendedor.
INSERT INTO productos (id, vendedor_id, categoria_id, nombre, descripcion, precio, stock, imagen) VALUES
    -- Herramientas
    ( 1, 2, 1, 'Martillo de uña 16 oz',                 'Martillo de carpintero con cabeza de acero forjado y mango de fibra de vidrio antideslizante.',       8.50,  40, NULL),
    ( 2, 2, 1, 'Juego de desarmadores 6 piezas',         'Tres desarmadores planos y tres de cruz, con mango ergonómico y punta magnetizada.',                 12.75,  25, NULL),
    ( 3, 2, 1, 'Alicate universal 8 pulgadas',           'Alicate de acero al cromo vanadio con mango aislado y zona de corte para alambre.',                   7.90,  30, NULL),
    ( 4, 2, 1, 'Cinta métrica 5 m',                      'Cinta métrica con carcasa de goma, freno automático y gancho imantado.',                              4.25,  60, NULL),
    ( 5, 2, 1, 'Taladro percutor 650 W',                 'Taladro eléctrico con portabrocas de 1/2 pulgada, percusión, velocidad variable y reversa.',         68.00,  10, NULL),
    -- Material eléctrico
    ( 6, 2, 2, 'Rollo de cable THHN #12 (100 m)',        'Cable de cobre calibre 12 con aislamiento termoplástico, para instalaciones residenciales.',         54.00,  15, NULL),
    ( 7, 2, 2, 'Interruptor sencillo con placa',         'Interruptor de pared de 15 A con placa plástica blanca incluida.',                                    3.10,  80, NULL),
    ( 8, 2, 2, 'Tomacorriente doble polarizado',         'Tomacorriente doble de 15 A con conexión a tierra y placa blanca.',                                   3.95,  75, NULL),
    ( 9, 2, 2, 'Foco LED 9 W luz blanca',                'Bombillo LED rosca E27, equivalente a 60 W incandescente. Bajo consumo.',                             2.40, 120, NULL),
    (10, 2, 2, 'Cinta aislante 18 m',                    'Cinta adhesiva de PVC de 3/4 de pulgada para aislamiento eléctrico hasta 600 V.',                      1.35, 150, NULL),
    -- Plomería
    (11, 2, 3, 'Tubo PVC de 1/2 pulgada x 6 m',          'Tubo de PVC para agua a presión, unión por cementado.',                                               5.60,  45, NULL),
    (12, 2, 3, 'Llave de chorro de bronce',              'Llave de jardín de bronce de 1/2 pulgada con manija de aluminio.',                                    9.25,  35, NULL),
    (13, 2, 3, 'Pegamento para PVC 118 ml',              'Cemento solvente para tubería de PVC, secado rápido. Incluye aplicador.',                             4.80,  50, NULL),
    (14, 2, 3, 'Cinta teflón',                           'Cinta selladora de roscas de 1/2 pulgada para tubería de agua y gas.',                                0.75, 200, NULL),
    -- Pinturas
    (15, 2, 4, 'Pintura látex blanca 1 galón',           'Pintura látex mate para interiores y exteriores, rendimiento aproximado de 35 m2 por galón.',        24.50,  20, NULL),
    (16, 2, 4, 'Brocha de cerda 3 pulgadas',             'Brocha de cerda natural con mango de madera, para pintura de aceite y látex.',                        3.60,  55, NULL),
    (17, 2, 4, 'Rodillo de felpa 9 pulgadas con mango',  'Rodillo de felpa con mango metálico, para superficies lisas.',                                       6.40,  40, NULL),
    (18, 2, 4, 'Thinner estándar 1/4 de galón',          'Diluyente para pinturas de aceite y limpieza de herramientas.',                                      5.95,  30, NULL),
    -- Tornillería
    (19, 2, 5, 'Tornillo autorroscante (caja de 100)',   'Caja de 100 tornillos autorroscantes de 1 pulgada, cabeza plana con ranura de cruz.',                 6.80,  25, NULL),
    (20, 2, 5, 'Clavo de acero 2 1/2 pulgadas (libra)',  'Clavo de acero con cabeza, vendido por libra.',                                                      1.20, 100, NULL),
    (21, 2, 5, 'Perno hexagonal 3/8 x 2 (10 unidades)',  'Juego de 10 pernos hexagonales galvanizados con tuerca y arandela.',                                 4.50,  40, NULL),
    (22, 2, 5, 'Tarugo plástico #8 (caja de 100)',       'Caja de 100 tarugos de expansión para pared de concreto o ladrillo.',                                3.25,  60, NULL);

-- ---------------------------------------------------------------------
-- facturas y detalle_factura
-- ---------------------------------------------------------------------
-- Se cargan facturas de DOS compradores distintos (id 3 y id 4) a
-- propósito: así V3 (IDOR en public/comprador/factura.php) se puede
-- demostrar desde el momento de la instalación. Iniciando sesión como
-- comprador@eltornillo.local (id = 3) y abriendo factura.php?id=2 se ven
-- los datos de José Ramírez (id = 4).
INSERT INTO facturas (id, comprador_id, fecha, total) VALUES
    (1, 3, '2026-11-03 10:15:00', 30.85),
    (2, 4, '2026-11-05 16:40:00', 81.60),
    (3, 3, '2026-11-07 09:05:00', 66.20),
    (4, 4, '2026-11-08 14:20:00', 35.80);

INSERT INTO detalle_factura (factura_id, producto_id, cantidad, precio_unitario, subtotal) VALUES
    -- Factura 1 — Ana Martínez — 30.85
    (1,  1, 2,  8.50, 17.00),
    (1,  4, 1,  4.25,  4.25),
    (1,  9, 4,  2.40,  9.60),
    -- Factura 2 — José Ramírez — 81.60
    (2,  5, 1, 68.00, 68.00),
    (2, 19, 2,  6.80, 13.60),
    -- Factura 3 — Ana Martínez — 66.20
    (3, 15, 2, 24.50, 49.00),
    (3, 16, 3,  3.60, 10.80),
    (3, 17, 1,  6.40,  6.40),
    -- Factura 4 — José Ramírez — 35.80
    (4, 11, 5,  5.60, 28.00),
    (4, 13, 1,  4.80,  4.80),
    (4, 14, 4,  0.75,  3.00);
