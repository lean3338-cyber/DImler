CREATE DATABASE IF NOT EXISTS teamdimler
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE teamdimler;

CREATE TABLE IF NOT EXISTS usuarios (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS productos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    categoria ENUM('crochet', 'tejidos', 'manualidades', 'libreria', 'costura') NOT NULL,
    nombre VARCHAR(160) NOT NULL,
    descripcion TEXT NOT NULL,
    medida VARCHAR(120) NULL,
    color VARCHAR(100) NULL,
    material VARCHAR(160) NULL,
    stock INT UNSIGNED NULL DEFAULT NULL,
    disponible TINYINT(1) NOT NULL DEFAULT 1,
    precio DECIMAL(10, 2) UNSIGNED NOT NULL,
    imagen VARCHAR(255) NOT NULL,
    permite_favorito TINYINT(1) NOT NULL DEFAULT 1,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_producto_categoria_nombre (categoria, nombre),
    INDEX idx_productos_categoria_activo (categoria, activo)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS favoritos (
    usuario_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NOT NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (usuario_id, producto_id),
    CONSTRAINT fk_favoritos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE CASCADE,
    CONSTRAINT fk_favoritos_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    usuario_id BIGINT UNSIGNED NOT NULL,
    total DECIMAL(10, 2) UNSIGNED NOT NULL,
    estado ENUM('pendiente', 'preparando', 'entregado', 'cancelado') NOT NULL DEFAULT 'pendiente',
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pedidos_usuario_fecha (usuario_id, creado_en),
    CONSTRAINT fk_pedidos_usuario FOREIGN KEY (usuario_id)
        REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedido_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pedido_id BIGINT UNSIGNED NOT NULL,
    producto_id BIGINT UNSIGNED NULL,
    nombre_producto VARCHAR(160) NOT NULL,
    precio_unitario DECIMAL(10, 2) UNSIGNED NOT NULL,
    cantidad INT UNSIGNED NOT NULL,
    subtotal DECIMAL(10, 2) UNSIGNED NOT NULL,
    CONSTRAINT fk_pedido_items_pedido FOREIGN KEY (pedido_id)
        REFERENCES pedidos(id) ON DELETE CASCADE,
    CONSTRAINT fk_pedido_items_producto FOREIGN KEY (producto_id)
        REFERENCES productos(id) ON DELETE SET NULL
) ENGINE=InnoDB;

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'libreria', 'Lápices Skycolor', 'Útiles escolares incluidos en el afiche de ofertas.', 1000.00, 'img/gener - ofert.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'libreria' AND nombre = 'Lápices Skycolor');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'libreria', 'Colores Maped', 'Set escolar incluido en el afiche de ofertas.', 3900.00, 'img/gener - ofert.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'libreria' AND nombre = 'Colores Maped');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'libreria', 'Marcadores Sharpie', 'Set de marcadores incluido en el afiche de ofertas.', 3900.00, 'img/gener - ofert.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'libreria' AND nombre = 'Marcadores Sharpie');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'libreria', 'Colores BIC Kids', 'Set escolar incluido en el afiche de ofertas.', 2900.00, 'img/gener - ofert.webp', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'libreria' AND nombre = 'Colores BIC Kids');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Vincha tejida con estrellas', 'Vincha tejida en tonos celeste y blanco, decorada con estrellas amarillas.', 5000.00, 'img/vincha-estrellas-crochet.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Vincha tejida con estrellas');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Bolso de jean con detalles tejidos', 'Bolso de jean con asas y bordes tejidos, y flores decorativas amarillas.', 18000.00, 'img/bolso-jean-crochet.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Bolso de jean con detalles tejidos');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'tejidos', 'Tejido artesanal celeste', 'Pieza artesanal tejida en tonos celeste, blanco y amarillo. Consultá las medidas disponibles.', 16000.00, 'img/prendas-accesorios-tejidos.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'tejidos' AND nombre = 'Tejido artesanal celeste');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Bolso artesanal personalizado', 'Bolso de jean con detalles tejidos, flor amarilla y letras decorativas. El diseño de la foto dice “Vamos Argentina 2026”.', 18000.00, 'img/bolso-tejido-personalizado.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Bolso artesanal personalizado');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Conejo amigurumi', 'Muñeco conejo tejido a crochet, vestido en rojo y amarillo.', 14000.00, 'img/amigurumi-crochet.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Conejo amigurumi');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Grupo de amigurumis', 'Tres muñecos tejidos a crochet: un conejo, un personaje con vestido violeta y un insecto rojo.', 22000.00, 'img/grupo-amigurumis.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Grupo de amigurumis');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Amigurumi naranja', 'Muñeco tejido a crochet en color naranja, con detalles oscuros.', 12000.00, 'img/amigurumi-naranja.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Amigurumi naranja');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Pulseras con nombres', 'Pulseras artesanales ajustables con nombres y dijes decorativos.', 3000.00, 'img/pulseras-con-nombres.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Pulseras con nombres');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Pulseras de mostacillas', 'Pulseras artesanales con mostacillas, cuentas y dijes de colores.', 3500.00, 'img/pulseras-mostacillas.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Pulseras de mostacillas');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Llaveros decorados', 'Llaveros artesanales decorados con cuentas, dijes y letras.', 4000.00, 'img/llaveros-artesanales.jpg', 1
WHERE NOT EXISTS (SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Llaveros decorados');
