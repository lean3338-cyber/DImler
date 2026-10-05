<?php
require_once __DIR__ . '/includes/bootstrap.php';

function cargar_items_carrito(bool $bloquearStock = false): array
{
    $carrito = $_SESSION['carrito'] ?? [];
    $ids = array_values(array_filter(array_map('intval', array_keys($carrito))));
    if (!$ids) {
        return [];
    }

    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $bloqueo = $bloquearStock ? ' FOR UPDATE' : '';
    $statement = db()->prepare("SELECT id, categoria, nombre, precio, imagen, stock, disponible FROM productos WHERE activo = 1 AND id IN ({$marcadores}){$bloqueo}");
    $statement->execute($ids);
    $productos = $statement->fetchAll();
    $items = [];

    foreach ($productos as $producto) {
        $productoId = (int) $producto['id'];
        $cantidad = max(1, min(20, (int) ($carrito[$productoId] ?? 1)));
        $producto['cantidad'] = $cantidad;
        $producto['subtotal'] = $cantidad * (float) $producto['precio'];
        $items[] = $producto;
    }

    return $items;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $carrito = $_SESSION['carrito'] ?? [];
    $erroresCarrito = [];

    if (isset($_POST['quitar_id'])) {
        $productoId = filter_var($_POST['quitar_id'], FILTER_VALIDATE_INT);
        if ($productoId) {
            unset($carrito[$productoId]);
            $_SESSION['carrito'] = $carrito;
            set_flash('Producto quitado del carrito.');
        }
        redirect('carrito.php');
    }

    $accion = (string) ($_POST['accion'] ?? '');
    if ($accion === 'agregar') {
        $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
        $cantidad = filter_var($_POST['cantidad'] ?? 1, FILTER_VALIDATE_INT);
        $cantidad = $cantidad === false ? 1 : max(1, min(20, $cantidad));
        $statement = db()->prepare('SELECT id, categoria, stock, disponible FROM productos WHERE id = ? AND activo = 1');
        $statement->execute([$productoId]);
        $productoDisponible = $statement->fetch();

        $esPorPedido = $productoDisponible && in_array((string) $productoDisponible['categoria'], ['crochet', 'tejidos'], true);
        $hayStock = $productoDisponible && ($esPorPedido
            ? (int) $productoDisponible['disponible'] === 1
            : ($productoDisponible['stock'] !== null && (int) $productoDisponible['stock'] > 0));
        if ($productoId && $productoDisponible && $hayStock) {
            $cantidadSolicitada = (int) ($carrito[$productoId] ?? 0) + $cantidad;
            if (!$esPorPedido && $cantidadSolicitada > (int) $productoDisponible['stock']) {
                set_flash('La cantidad solicitada no está disponible.', 'error');
            } else {
                $carrito[$productoId] = min(20, $cantidadSolicitada);
                $_SESSION['carrito'] = $carrito;
                set_flash($esPorPedido ? 'Producto agregado al pedido.' : 'Producto agregado al carrito.');
            }
        } else {
            $mensaje = !$productoDisponible
                ? 'No se encontró el producto.'
                : ($productoDisponible['stock'] === null && !in_array((string) $productoDisponible['categoria'], ['crochet', 'tejidos'], true)
                    ? 'La disponibilidad de este producto está pendiente de confirmar.'
                    : 'Este producto no está disponible para pedidos.');
            set_flash($mensaje, 'error');
        }
        redirect('carrito.php');
    }

    if ($accion === 'actualizar') {
        $cantidades = $_POST['cantidades'] ?? [];
        if (!is_array($cantidades)) {
            $erroresCarrito[] = 'Las cantidades enviadas no son válidas.';
        }
        foreach (is_array($cantidades) ? $cantidades : [] as $productoId => $cantidad) {
            $productoId = filter_var($productoId, FILTER_VALIDATE_INT);
            $cantidad = filter_var($cantidad, FILTER_VALIDATE_INT);
            if (!$productoId || $cantidad === false) {
                $erroresCarrito[] = 'Una de las cantidades no es válida.';
                break;
            }
            if ($cantidad < 1) {
                unset($carrito[$productoId]);
            } else {
                $disponibilidadStatement = db()->prepare('SELECT categoria, stock, disponible FROM productos WHERE id = ? AND activo = 1');
                $disponibilidadStatement->execute([$productoId]);
                $productoActualizado = $disponibilidadStatement->fetch();
                $esPorPedido = $productoActualizado && in_array((string) $productoActualizado['categoria'], ['crochet', 'tejidos'], true);
                $cantidadDisponible = $productoActualizado && ($esPorPedido
                    ? (int) $productoActualizado['disponible'] === 1
                    : ($productoActualizado['stock'] !== null && (int) $productoActualizado['stock'] > 0));
                if (!$productoActualizado || !$cantidadDisponible) {
                    $erroresCarrito[] = 'Un producto del carrito ya no está disponible para pedidos.';
                    break;
                }
                if (!$esPorPedido && $cantidad > (int) $productoActualizado['stock']) {
                    $erroresCarrito[] = 'Una de las cantidades no está disponible.';
                    break;
                }
                if ($cantidad > 20) {
                    $erroresCarrito[] = 'La cantidad máxima por producto es 20.';
                    break;
                }
                $carrito[$productoId] = $cantidad;
            }
        }
        if (!$erroresCarrito) {
            $_SESSION['carrito'] = $carrito;
            set_flash('Carrito actualizado.');
            redirect('carrito.php');
        }
    }

    if ($accion === 'confirmar') {
        if (!is_logged_in()) {
            set_flash('Creá una cuenta para continuar con la compra.', 'error');
            redirect('registro.php?destino=carrito.php');
        }
        $conexion = db();
        try {
            $conexion->beginTransaction();
            $items = cargar_items_carrito(true);
            if (!$items) {
                $conexion->rollBack();
                set_flash('El carrito está vacío.', 'error');
                redirect('carrito.php');
            }
            if (count($items) !== count($carrito)) {
                $conexion->rollBack();
                set_flash('Uno o más productos ya no están disponibles. Revisá el carrito.', 'error');
                redirect('carrito.php');
            }
            foreach ($items as $item) {
                $esPorPedido = in_array((string) $item['categoria'], ['crochet', 'tejidos'], true);
                if (($esPorPedido && (int) $item['disponible'] !== 1)
                    || (!$esPorPedido && ($item['stock'] === null || (int) $item['cantidad'] > (int) $item['stock']))) {
                    $conexion->rollBack();
                    set_flash('La disponibilidad o cantidad cambió. Revisá el carrito antes de confirmar.', 'error');
                    redirect('carrito.php');
                }
            }

            $total = array_sum(array_column($items, 'subtotal'));
            $statement = $conexion->prepare('INSERT INTO pedidos (usuario_id, total) VALUES (?, ?)');
            $statement->execute([current_user()['id'], $total]);
            $pedidoId = (int) $conexion->lastInsertId();
            $itemStatement = $conexion->prepare('INSERT INTO pedido_items (pedido_id, producto_id, nombre_producto, precio_unitario, cantidad, subtotal) VALUES (?, ?, ?, ?, ?, ?)');
            $stockStatement = $conexion->prepare('UPDATE productos SET stock = stock - ?, disponible = IF(stock - ? = 0, 0, 1) WHERE id = ? AND stock IS NOT NULL AND stock >= ?');
            foreach ($items as $item) {
                if (!in_array((string) $item['categoria'], ['crochet', 'tejidos'], true)) {
                    $stockStatement->execute([$item['cantidad'], $item['cantidad'], $item['id'], $item['cantidad']]);
                    if ($stockStatement->rowCount() !== 1) {
                        throw new RuntimeException('La cantidad disponible cambió mientras se guardaba el pedido.');
                    }
                }
                $itemStatement->execute([
                    $pedidoId,
                    $item['id'],
                    $item['nombre'],
                    $item['precio'],
                    $item['cantidad'],
                    $item['subtotal'],
                ]);
            }
            $conexion->commit();
            unset($_SESSION['carrito']);
            redirect("pedido-confirmado.php?id={$pedidoId}");
        } catch (Throwable $exception) {
            if ($conexion->inTransaction()) {
                $conexion->rollBack();
            }
            error_log($exception->getMessage());
            $mensaje = $exception instanceof RuntimeException ? $exception->getMessage() : 'No se pudo guardar el pedido. Intentá de nuevo.';
            set_flash($mensaje, 'error');
            redirect('carrito.php');
        }
    }
}

$erroresCarrito = $erroresCarrito ?? [];
$items = cargar_items_carrito();
$total = array_sum(array_column($items, 'subtotal'));
$hayProductosNoDisponibles = (bool) array_filter($items, static function (array $item): bool {
    $esPorPedido = in_array((string) $item['categoria'], ['crochet', 'tejidos'], true);
    return $esPorPedido
        ? (int) $item['disponible'] !== 1
        : ($item['stock'] === null || (int) $item['cantidad'] > (int) $item['stock']);
});
$pageTitle = 'Carrito';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Carrito</h2>
    <?php if (!$items): ?>
        <section class="estado-vacio">
            <p>Tu carrito está vacío.</p>
            <a class="boton-principal" href="libreria.php">Ver productos</a>
        </section>
    <?php else: ?>
        <?php foreach ($erroresCarrito as $error): ?>
            <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
        <?php endforeach; ?>
        <form action="carrito.php" method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="actualizar">
            <div class="tabla-contenedor">
                <table class="tabla-carrito">
                    <thead>
                        <tr><th>Producto</th><th>Precio</th><th>Cantidad</th><th>Subtotal</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td>
                                    <?= h($item['nombre']) ?>
                                    <?php if (in_array((string) $item['categoria'], ['crochet', 'tejidos'], true) && (int) $item['disponible'] !== 1): ?>
                                        <br><small class="producto-no-disponible">No disponible; quitá el producto para continuar.</small>
                                    <?php elseif ($item['stock'] !== null && (int) $item['cantidad'] > (int) $item['stock']): ?>
                                        <br><small class="producto-no-disponible">Cantidad no disponible; actualizá el pedido.</small>
                                    <?php elseif ($item['stock'] === null && !in_array((string) $item['categoria'], ['crochet', 'tejidos'], true)): ?>
                                        <br><small class="producto-no-disponible">Disponibilidad pendiente de confirmar; quitá el producto para continuar.</small>
                                    <?php endif; ?>
                                </td>
                                <td>$<?= number_format((float) $item['precio'], 2, ',', '.') ?></td>
                                <td><input class="cantidad-carrito" type="number" min="1" max="20" name="cantidades[<?= (int) $item['id'] ?>]" value="<?= (int) $item['cantidad'] ?>" aria-label="Cantidad de <?= h($item['nombre']) ?>"></td>
                                <td>$<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></td>
                                <td><button class="boton-texto" type="submit" name="quitar_id" value="<?= (int) $item['id'] ?>">Quitar</button></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button class="boton-secundario" type="submit">Actualizar cantidades</button>
        </form>
        <div class="resumen-carrito">
            <p>Total: <strong>$<?= number_format($total, 2, ',', '.') ?></strong></p>
            <form action="carrito.php" method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="accion" value="confirmar">
                <button class="boton-principal" type="submit" <?= $hayProductosNoDisponibles ? 'disabled' : '' ?>>Registrar pedido</button>
            </form>
            <?php if ($hayProductosNoDisponibles): ?><p class="producto-no-disponible">Quitá del carrito los productos no disponibles para registrar el pedido.</p><?php endif; ?>
            <?php if (!is_logged_in()): ?>
                <p>Para registrar el pedido necesitás <a href="login.php?destino=carrito.php">iniciar sesión</a>.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
