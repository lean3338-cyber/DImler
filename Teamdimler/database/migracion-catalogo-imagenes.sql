USE teamdimler;

ALTER TABLE productos
    MODIFY categoria ENUM('crochet', 'tejidos', 'manualidades', 'libreria', 'costura') NOT NULL;

UPDATE productos
SET nombre = 'Vincha tejida con estrellas',
    descripcion = 'Vincha tejida en tonos celeste y blanco, decorada con estrellas amarillas.',
    imagen = 'img/vincha-estrellas-crochet.jpg',
    precio = 5000.00
WHERE categoria = 'crochet'
  AND (nombre = 'Accesorio celeste con estrellas' OR imagen = 'img/creacionesdimler/mascara-crochet.jpg');

UPDATE productos
SET descripcion = 'Bolso de jean con asas y bordes tejidos, y flores decorativas amarillas.',
    imagen = 'img/bolso-jean-crochet.jpg'
WHERE categoria = 'crochet'
  AND (nombre = 'Bolso de jean con detalles tejidos' OR imagen = 'img/creacionesdimler/bolso-crochet.jpg');

UPDATE productos
SET imagen = 'img/amigurumi-crochet.jpg'
WHERE categoria = 'crochet'
  AND (nombre = 'Conejo amigurumi' OR imagen = 'img/creacionesdimler/amigurumi-crochet.jpg');

UPDATE productos
SET nombre = 'Bolso artesanal personalizado',
    descripcion = 'Bolso de jean con detalles tejidos, flor amarilla y letras decorativas. El diseño de la foto dice “Vamos Argentina 2026”.',
    imagen = 'img/bolso-tejido-personalizado.jpg',
    precio = 18000.00
WHERE (categoria = 'crochet' AND nombre = 'Bolso artesanal personalizado')
   OR nombre = 'Bolso celeste con bolsillo'
   OR nombre = 'Accesorio celeste tejido'
   OR imagen = 'img/creacionesdimler/bolso-azul-crochet.jpg';

UPDATE productos
SET nombre = 'Grupo de amigurumis',
    descripcion = 'Tres muñecos tejidos a crochet: un conejo, un personaje con vestido violeta y un insecto rojo.',
    imagen = 'img/grupo-amigurumis.jpg'
WHERE categoria = 'crochet'
  AND (nombre = 'Pareja de muñecos amigurumi' OR imagen = 'img/creacionesdimler/munecos-amigurumi.jpg');

UPDATE productos
SET nombre = 'Amigurumi naranja',
    descripcion = 'Muñeco tejido a crochet en color naranja, con detalles oscuros.',
    imagen = 'img/amigurumi-naranja.jpg'
WHERE categoria = 'crochet'
  AND (nombre = 'Personaje naranja amigurumi' OR imagen = 'img/creacionesdimler/personaje-amigurumi.jpg');

UPDATE productos
SET descripcion = 'Muñeco conejo tejido a crochet, vestido en rojo y amarillo.'
WHERE categoria = 'crochet'
  AND nombre = 'Conejo amigurumi';

UPDATE productos
SET categoria = 'manualidades',
    nombre = 'Pulseras con nombres',
    descripcion = 'Pulseras artesanales ajustables con nombres y dijes decorativos.',
    imagen = 'img/pulseras-con-nombres.jpg',
    precio = 3000.00
WHERE nombre = 'Oveja amigurumi'
   OR imagen = 'img/creacionesdimler/oveja-amigurumi.jpg';

UPDATE productos
SET nombre = 'Tejido artesanal celeste',
    categoria = 'tejidos',
    descripcion = 'Prenda tejida artesanal en tonos celeste, blanco y amarillo. Consultá las medidas disponibles.',
    imagen = 'img/prendas-accesorios-tejidos.jpg'
WHERE categoria = 'tejidos'
  AND (nombre = 'Conjunto tejido para bebé' OR nombre = 'Tejido artesanal celeste' OR imagen = 'img/creacionesdimler/accesorios-bebe-tejidos.jpg');

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'crochet', 'Bolso artesanal personalizado', 'Bolso de jean con detalles tejidos, flor amarilla y letras decorativas. El diseño de la foto dice “Vamos Argentina 2026”.', 18000.00, 'img/bolso-tejido-personalizado.jpg', 1
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE categoria = 'crochet' AND nombre = 'Bolso artesanal personalizado'
);

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'tejidos', 'Tejido artesanal celeste', 'Pieza artesanal tejida en tonos celeste, blanco y amarillo. Consultá las medidas disponibles.', 16000.00, 'img/prendas-accesorios-tejidos.jpg', 1
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE categoria = 'tejidos' AND nombre = 'Tejido artesanal celeste'
);

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Pulseras con nombres', 'Pulseras artesanales ajustables con nombres y dijes decorativos.', 3000.00, 'img/pulseras-con-nombres.jpg', 1
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Pulseras con nombres'
);

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Pulseras de mostacillas', 'Pulseras artesanales con mostacillas, cuentas y dijes de colores.', 3500.00, 'img/pulseras-mostacillas.jpg', 1
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Pulseras de mostacillas'
);

INSERT INTO productos (categoria, nombre, descripcion, precio, imagen, permite_favorito)
SELECT 'manualidades', 'Llaveros decorados', 'Llaveros artesanales decorados con cuentas, dijes y letras.', 4000.00, 'img/llaveros-artesanales.jpg', 1
WHERE NOT EXISTS (
    SELECT 1 FROM productos WHERE categoria = 'manualidades' AND nombre = 'Llaveros decorados'
);
