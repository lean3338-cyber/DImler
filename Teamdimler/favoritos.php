<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $productoId = filter_input(INPUT_POST, 'producto_id', FILTER_VALIDATE_INT);
    $accion = (string) ($_POST['accion'] ?? '');

    if ($productoId && in_array($accion, ['agregar', 'quitar'], true)) {
        $statement = db()->prepare('SELECT permite_favorito FROM productos WHERE id = ? AND activo = 1');
        $statement->execute([$productoId]);
        $producto = $statement->fetch();

        if ($producto && (bool) $producto['permite_favorito']) {
            if ($accion === 'agregar') {
                $statement = db()->prepare('INSERT IGNORE INTO favoritos (usuario_id, producto_id) VALUES (?, ?)');
                $statement->execute([current_user()['id'], $productoId]);
                set_flash('Producto guardado en favoritos.');
            } else {
                $statement = db()->prepare('DELETE FROM favoritos WHERE usuario_id = ? AND producto_id = ?');
                $statement->execute([current_user()['id'], $productoId]);
                set_flash('Producto quitado de favoritos.');
            }
        } else {
            set_flash('Este producto no admite favoritos.', 'error');
        }
    }

    redirect('favoritos.php');
}

$statement = db()->prepare('SELECT p.* FROM favoritos f JOIN productos p ON p.id = f.producto_id WHERE f.usuario_id = ? AND p.activo = 1 ORDER BY f.creado_en DESC');
$statement->execute([current_user()['id']]);
$productos = $statement->fetchAll();
$favoritos = array_map('intval', array_column($productos, 'id'));

$pageTitle = 'Favoritos';
require_once __DIR__ . '/includes/product-list.php';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Mis favoritos</h2>
    <?php render_product_list($productos, $favoritos); ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
