<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/product-list.php';

$pageTitle = 'Inicio';
$productos = db()->query('SELECT * FROM productos WHERE activo = 1 ORDER BY creado_en DESC')->fetchAll();
$buscarProductoOferta = static function (string $nombre) use ($productos): ?array {
    foreach ($productos as $producto) {
        if ($producto['nombre'] === $nombre) {
            return $producto;
        }
    }

    return null;
};
$productoConejo = $buscarProductoOferta('Conejo amigurumi');
$productoVincha = $buscarProductoOferta('Vincha tejida con estrellas');
$productoBolso = $buscarProductoOferta('Bolso artesanal personalizado');
$productoPulseras = $buscarProductoOferta('Pulseras con nombres');
$productoLapices = $buscarProductoOferta('Lápices Skycolor');
$favoritos = [];
if (is_logged_in()) {
    $statement = db()->prepare('SELECT producto_id FROM favoritos WHERE usuario_id = ?');
    $statement->execute([current_user()['id']]);
    $favoritos = array_map('intval', $statement->fetchAll(PDO::FETCH_COLUMN));
}

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Ofertas destacadas</h2>
    <section id="ofertas" class="ofertas" aria-label="Ofertas destacadas">
        <div class="ofertas-viewport">
            <div class="ofertas-track">
                <article class="oferta-slide">
                    <a class="oferta-enlace" href="<?= $productoConejo ? 'producto.php?id=' . (int) $productoConejo['id'] : 'crochet.php' ?>">
                        <img src="img/amigurumi-crochet.jpg" alt="Conejo amigurumi tejido a crochet">
                        <div class="oferta-info">
                            <h4>Amigurumis a crochet</h4>
                            <p>Muñecos tejidos a mano por Creaciones DimLer.</p>
                            <span class="oferta-cta"><?= $productoConejo ? 'Ver más detalles' : 'Ver crochet' ?></span>
                        </div>
                    </a>
                </article>
                <article class="oferta-slide">
                    <a class="oferta-enlace" href="<?= $productoVincha ? 'producto.php?id=' . (int) $productoVincha['id'] : 'crochet.php' ?>">
                        <img src="img/vincha-estrellas-crochet.jpg" alt="Vincha tejida en tonos celeste y blanco con estrellas amarillas">
                        <div class="oferta-info">
                            <h4>Vincha tejida con estrellas</h4>
                            <p>Accesorio tejido a mano en tonos celeste y blanco.</p>
                            <span class="oferta-cta"><?= $productoVincha ? 'Ver más detalles' : 'Ver crochet' ?></span>
                        </div>
                    </a>
                </article>
                <article class="oferta-slide">
                    <a class="oferta-enlace" href="<?= $productoBolso ? 'producto.php?id=' . (int) $productoBolso['id'] : 'crochet.php' ?>">
                        <img src="img/bolso-tejido-personalizado.jpg" alt="Bolso personalizado con tejido celeste, blanco y amarillo">
                        <div class="oferta-info">
                            <h4>Bolso artesanal personalizado</h4>
                            <p>Bolso de jean con detalles tejidos y letras decorativas.</p>
                            <span class="oferta-cta"><?= $productoBolso ? 'Ver más detalles' : 'Ver crochet' ?></span>
                        </div>
                    </a>
                </article>
                <article class="oferta-slide">
                    <a class="oferta-enlace" href="<?= $productoPulseras ? 'producto.php?id=' . (int) $productoPulseras['id'] : 'manualidades.php' ?>">
                        <img src="img/pulseras-con-nombres.jpg" alt="Pulseras artesanales con nombres y dijes">
                        <div class="oferta-info">
                            <h4>Manualidades</h4>
                            <p>Pulseras personalizadas y llaveros decorados.</p>
                            <span class="oferta-cta"><?= $productoPulseras ? 'Ver más detalles' : 'Ver manualidades' ?></span>
                        </div>
                    </a>
                </article>
                <article class="oferta-slide">
                    <a class="oferta-enlace" href="<?= $productoLapices ? 'producto.php?id=' . (int) $productoLapices['id'] : 'libreria.php' ?>">
                        <img src="<?= h($productoLapices['imagen'] ?? 'img/gener - ofert.webp') ?>" alt="<?= h($productoLapices['nombre'] ?? 'Productos de librería publicados en la oferta') ?>">
                        <div class="oferta-info">
                            <h4><?= h($productoLapices['nombre'] ?? 'Librería') ?></h4>
                            <p><?= h($productoLapices['descripcion'] ?? 'Biromes y útiles escolares disponibles.') ?></p>
                            <span class="oferta-cta"><?= $productoLapices ? 'Ver más detalles' : 'Ver librería' ?></span>
                        </div>
                    </a>
                </article>
            </div>
        </div>
        <button class="ofertas-control ofertas-anterior" type="button" aria-label="Oferta anterior">&#8592;</button>
        <button class="ofertas-control ofertas-siguiente" type="button" aria-label="Oferta siguiente">&#8594;</button>
        <div class="ofertas-indicadores" aria-label="Elegir oferta"></div>
    </section>

    <h2 class="titulo-pagina">Ofertas y productos</h2>
    <nav class="categorias-grid" aria-label="Categorías">
        <a class="categoria-enlace" href="crochet.php">Crochet</a>
        <a class="categoria-enlace" href="tejidos.php">Tejidos</a>
        <a class="categoria-enlace" href="manualidades.php">Manualidades</a>
        <a class="categoria-enlace" href="libreria.php">Librería</a>
    </nav>
    <h2 id="catalogo" class="titulo-pagina">Catálogo</h2>
    <?php render_product_list($productos, $favoritos); ?>
</main>
<script src="js/carousel.js" defer></script>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
