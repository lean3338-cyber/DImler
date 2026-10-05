<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/product-list.php';

$pageTitle = 'Tejidos';
$statement = db()->prepare('SELECT * FROM productos WHERE categoria = ? AND activo = 1 ORDER BY creado_en DESC');
$statement->execute(['tejidos']);
$productos = $statement->fetchAll();
$favoritos = [];
if (is_logged_in()) {
    $favoriteStatement = db()->prepare('SELECT producto_id FROM favoritos WHERE usuario_id = ?');
    $favoriteStatement->execute([current_user()['id']]);
    $favoritos = array_map('intval', $favoriteStatement->fetchAll(PDO::FETCH_COLUMN));
}

require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <a class="boton-secundario boton-volver-catalogo" href="index.php#catalogo">&larr; Volver atrás</a>
    <h2 class="titulo-pagina">Tejidos</h2>
    <p class="introduccion-pagina">Prendas y accesorios tejidos a mano de Creaciones DimLer.</p>
    <p class="precio-provisional-nota">Los precios de Tejidos son ficticios y provisorios; se actualizarán cuando compartas la lista definitiva.</p>
    <?php render_product_list($productos, $favoritos); ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
