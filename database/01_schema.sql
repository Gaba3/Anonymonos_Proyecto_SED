-- =====================================================================
-- Ferretería El Tornillo — esquema de la base de datos
-- =====================================================================

CREATE DATABASE IF NOT EXISTS ferreteria
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE ferreteria;

-- Orden inverso al de creación, por las llaves foráneas.
DROP TABLE IF EXISTS detalle_factura;
DROP TABLE IF EXISTS facturas;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;
DROP TABLE IF EXISTS usuarios;

-- ---------------------------------------------------------------------
-- usuarios
-- ---------------------------------------------------------------------
-- La columna `password` guarda el hash MD5 en hexadecimal (32 caracteres).
-- El uso de MD5 es parte del diseño de V1 (inyección SQL en el login):
-- la consulta vulnerable compara con MD5('$password'). La mitigación
-- correcta sería password_hash() / password_verify(), y está documentada
-- como NO aplicada en la sección 5 del README.md.
CREATE TABLE usuarios (
    id        INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre    VARCHAR(100) NOT NULL,
    correo    VARCHAR(150) NOT NULL,
    password  CHAR(32)     NOT NULL COMMENT 'MD5 hexadecimal',
    rol       ENUM('admin', 'vendedor', 'comprador') NOT NULL DEFAULT 'comprador',
    creado_en DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uk_usuarios_correo (correo)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- categorias
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
    id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80)  NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uk_categorias_nombre (nombre)
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- productos
-- ---------------------------------------------------------------------
-- vendedor_id y categoria_id admiten NULL para que el administrador pueda
-- eliminar un usuario o una categoría sin perder el producto del catálogo
-- (ON DELETE SET NULL).
--
-- `imagen` guarda solo el nombre del archivo dentro de public/uploads/.
-- Si es NULL, la vista debe mostrar una imagen de reemplazo.
CREATE TABLE productos (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    vendedor_id  INT UNSIGNED  NULL,
    categoria_id INT UNSIGNED  NULL,
    nombre       VARCHAR(150)  NOT NULL,
    descripcion  TEXT          NULL,
    precio       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    stock        INT           NOT NULL DEFAULT 0,
    imagen       VARCHAR(255)  NULL,
    creado_en    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_productos_vendedor (vendedor_id),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_nombre (nombre),
    CONSTRAINT fk_productos_vendedor
        FOREIGN KEY (vendedor_id) REFERENCES usuarios (id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- facturas
-- ---------------------------------------------------------------------
-- comprador_id usa ON DELETE RESTRICT: una factura es un registro
-- histórico y no debe quedar sin dueño. Para eliminar un comprador hay
-- que eliminar antes sus facturas (el panel de administrador debe avisar).
CREATE TABLE facturas (
    id           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    comprador_id INT UNSIGNED  NOT NULL,
    fecha        DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    total        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_facturas_comprador (comprador_id),
    CONSTRAINT fk_facturas_comprador
        FOREIGN KEY (comprador_id) REFERENCES usuarios (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- detalle_factura
-- ---------------------------------------------------------------------
-- precio_unitario y subtotal se guardan en la fila porque el precio del
-- producto puede cambiar después de la venta; la factura debe conservar
-- el monto cobrado.
--
-- producto_id usa ON DELETE RESTRICT para no romper facturas existentes:
-- un producto que ya se vendió no se puede eliminar.
CREATE TABLE detalle_factura (
    id              INT UNSIGNED  NOT NULL AUTO_INCREMENT,
    factura_id      INT UNSIGNED  NOT NULL,
    producto_id     INT UNSIGNED  NOT NULL,
    cantidad        INT           NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    subtotal        DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    PRIMARY KEY (id),
    KEY idx_detalle_factura (factura_id),
    KEY idx_detalle_producto (producto_id),
    CONSTRAINT fk_detalle_factura
        FOREIGN KEY (factura_id) REFERENCES facturas (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_detalle_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE = InnoDB
  DEFAULT CHARSET = utf8mb4
  COLLATE = utf8mb4_unicode_ci;
