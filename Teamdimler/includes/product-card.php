<?php
function render_product_card(array $producto, array $favoritos = []): void
{
    $productoId = (int) $producto['id'];
    $esFavorito = in_array($productoId, $favoritos, true);
    $esPorPedido = in_array((string) $producto['categoria'], ['crochet', 'tejidos'], true);
    $estaDisponible = $esPorPedido
        ? (int) $producto['disponible'] === 1
        : ($producto['stock'] !== null && (int) $producto['stock'] > 0);
    ?>
    <article class="producto-card">
        <button class="producto-imagen-ampliar" type="button" data-imagen="<?= h($producto['imagen']) ?>" data-texto-alternativo="<?= h($producto['nombre']) ?>" aria-label="Ampliar foto de <?= h($producto['nombre']) ?>">
            <img src="<?= h($producto['imagen']) ?>" alt="<?= h($producto['nombre']) ?>">
        </button>
        <div class="producto-info">
            <p class="producto-categoria"><?= h(ucfirst((string) $producto['categoria'])) ?></p>
            <h3><a class="producto-detalle-enlace" href="producto.php?id=<?= $productoId ?>"><?= h($producto['nombre']) ?></a></h3>
            <p><?= h($producto['descripcion']) ?></p>
            <?php if (!empty($producto['medida']) || !empty($producto['color']) || !empty($producto['material'])): ?>
                <ul class="producto-caracteristicas">
                    <?php if (!empty($producto['medida'])): ?><li><strong>Medida:</strong> <?= h($producto['medida']) ?></li><?php endif; ?>
                    <?php if (!empty($producto['color'])): ?><li><strong>Color:</strong> <?= h($producto['color']) ?></li><?php endif; ?>
                    <?php if (!empty($producto['material'])): ?><li><strong>Material:</strong> <?= h($producto['material']) ?></li><?php endif; ?>
                </ul>
            <?php endif; ?>
            <strong class="producto-precio">$<?= number_format((float) $producto['precio'], 2, ',', '.') ?></strong>
            <?php if (in_array((string) $producto['categoria'], ['crochet', 'tejidos', 'manualidades'], true)): ?>
                <small class="precio-provisional-producto">Precio ficticio/provisorio</small>
            <?php endif; ?>
            <?php if ($esPorPedido): ?>
                <?php if (!$estaDisponible): ?>
                    <small class="producto-no-disponible">No disponible para pedidos</small>
                <?php else: ?>
                    <small class="producto-por-pedido">Se prepara por pedido.</small>
                <?php endif; ?>
            <?php elseif ($producto['stock'] === null): ?>
                <small class="producto-stock-pendiente">Disponibilidad pendiente de confirmar.</small>
            <?php elseif ((int) $producto['stock'] === 0): ?>
                <small class="producto-no-disponible">No disponible</small>
            <?php else: ?>
                <small class="producto-stock-disponible">Disponible</small>
            <?php endif; ?>
            <div class="producto-acciones">
                <a class="producto-accion-detalle" href="producto.php?id=<?= $productoId ?>">Ver detalle</a>
                <?php if ($estaDisponible): ?>
                    <form action="carrito.php" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="agregar">
                        <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                        <button class="boton-principal" type="submit"><?= in_array((string) $producto['categoria'], ['crochet', 'tejidos'], true) ? 'Agregar al pedido' : 'Agregar al carrito' ?></button>
                    </form>
                <?php endif; ?>
                <?php if ((bool) $producto['permite_favorito']): ?>
                    <?php if (is_logged_in()): ?>
                        <form action="favoritos.php" method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="producto_id" value="<?= $productoId ?>">
                            <input type="hidden" name="accion" value="<?= $esFavorito ? 'quitar' : 'agregar' ?>">
                            <button class="boton-favorito" type="submit" aria-pressed="<?= $esFavorito ? 'true' : 'false' ?>">
                                <?= $esFavorito ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <a class="boton-favorito" href="login.php">Ingresá para guardar favoritos</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </article>
    <?php
}
