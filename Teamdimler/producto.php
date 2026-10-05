<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$producto = null;
if ($id) {
    $statement = db()->prepare('SELECT * FROM productos WHERE id = ? AND activo = 1');
    $statement->execute([$id]);
    $producto = $statement->fetch() ?: null;
}

if (!$producto) {
    http_response_code(404);
}

$esFavorito = false;
if ($producto && is_logged_in() && (bool) $producto['permite_favorito']) {
    $favoriteStatement = db()->prepare('SELECT 1 FROM favoritos WHERE usuario_id = ? AND producto_id = ?');
    $favoriteStatement->execute([current_user()['id'], $producto['id']]);
    $esFavorito = (bool) $favoriteStatement->fetchColumn();
}

$pageTitle = $producto ? $producto['nombre'] : 'Producto no encontrado';
$esPorPedido = $producto && in_array((string) $producto['categoria'], ['crochet', 'tejidos'], true);
$estaDisponible = $producto && ($esPorPedido
    ? (int) $producto['disponible'] === 1
    : ($producto['stock'] !== null && (int) $producto['stock'] > 0));
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <?php if ($producto): ?>
        <h2 class="titulo-pagina">Detalle del producto</h2>
        <article class="detalle-producto">
            <button class="producto-imagen-ampliar detalle-imagen-ampliar" type="button" data-imagen="<?= h($producto['imagen']) ?>" data-texto-alternativo="<?= h($producto['nombre']) ?>" aria-label="Ampliar foto de <?= h($producto['nombre']) ?>">
                <img src="<?= h($producto['imagen']) ?>" alt="<?= h($producto['nombre']) ?>">
            </button>
            <div class="detalle-info">
                <p class="detalle-categoria"><?= h(ucfirst((string) $producto['categoria'])) ?></p>
                <h3><?= h($producto['nombre']) ?></h3>
                <p><?= h($producto['descripcion']) ?></p>
                <?php if ($esPorPedido && !$estaDisponible): ?>
                    <p class="producto-no-disponible">Este producto no está disponible para pedidos en este momento.</p>
                <?php elseif ($esPorPedido): ?>
                    <p class="producto-por-pedido">Se prepara por pedido; no se cuenta stock.</p>
                <?php elseif ($producto['stock'] === null): ?>
                    <p class="producto-stock-pendiente">Disponibilidad pendiente de confirmar.</p>
                <?php elseif ($estaDisponible): ?>
                    <p class="producto-stock-disponible">Disponible</p>
                <?php else: ?>
                    <p class="producto-no-disponible">No disponible.</p>
                <?php endif; ?>
                <?php if (!empty($producto['medida']) || !empty($producto['color']) || !empty($producto['material'])): ?>
                    <ul class="producto-caracteristicas">
                        <?php if (!empty($producto['medida'])): ?><li><strong>Medida:</strong> <?= h($producto['medida']) ?></li><?php endif; ?>
                        <?php if (!empty($producto['color'])): ?><li><strong>Color:</strong> <?= h($producto['color']) ?></li><?php endif; ?>
                        <?php if (!empty($producto['material'])): ?><li><strong>Material:</strong> <?= h($producto['material']) ?></li><?php endif; ?>
                    </ul>
                <?php endif; ?>
                <p class="producto-precio">$<?= number_format((float) $producto['precio'], 2, ',', '.') ?></p>
                <?php if (in_array((string) $producto['categoria'], ['crochet', 'tejidos', 'manualidades'], true)): ?>
                    <p class="precio-provisional-producto">Precio ficticio/provisorio</p>
                <?php endif; ?>
                <?php if ($estaDisponible): ?>
                    <form action="carrito.php" method="post" class="formulario-cantidad">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                        <label for="cantidad">Cantidad</label>
                        <input id="cantidad" name="cantidad" type="number" min="1" max="20" value="1" required>
                        <button class="boton-principal" type="submit"><?= $esPorPedido ? 'Agregar al pedido' : 'Agregar al carrito' ?></button>
                    </form>
                <?php endif; ?>
                <?php if ((bool) $producto['permite_favorito']): ?>
                    <?php if (is_logged_in()): ?>
                        <form action="favoritos.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                            <input type="hidden" name="accion" value="<?= $esFavorito ? 'quitar' : 'agregar' ?>">
                            <button class="boton-favorito" type="submit"><?= $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?></button>
                        </form>
                    <?php else: ?>
                        <a class="boton-favorito" href="login.php">Iniciá sesión para guardar favoritos</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </article>
    <?php else: ?>
        <section class="estado-vacio">
            <h2>Producto no encontrado</h2>
            <a class="boton-principal" href="index.php">Volver al catálogo</a>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
