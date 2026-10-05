<?php
require_once __DIR__ . '/product-card.php';

function render_product_list(array $productos, array $favoritos = []): void
{
    if (!$productos) {
        echo '<section class="estado-vacio"><h2>Próximamente</h2><p>Todavía no hay productos publicados en esta categoría.</p></section>';
        return;
    }

    echo '<section class="tds catalogo" aria-label="Productos">';
    foreach ($productos as $producto) {
        render_product_card($producto, $favoritos);
    }
    echo '</section>';
}
