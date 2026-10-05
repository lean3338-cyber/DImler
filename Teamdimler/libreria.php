<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/product-list.php';

$pageTitle = 'Librería';
$statement = db()->prepare('SELECT * FROM productos WHERE categoria = ? AND activo = 1 ORDER BY creado_en DESC');
$statement->execute(['libreria']);
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
    <h2 class="titulo-pagina">Librería</h2>
    <p class="introduccion-pagina">Útiles escolares y artículos de librería.</p>
    <?php render_product_list($productos, $favoritos); ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
