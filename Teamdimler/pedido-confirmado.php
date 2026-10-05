<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$pedidoId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$pedido = null;
$items = [];
if ($pedidoId) {
    $pedidoStatement = db()->prepare('SELECT id, total, estado, creado_en FROM pedidos WHERE id = ? AND usuario_id = ?');
    $pedidoStatement->execute([$pedidoId, current_user()['id']]);
    $pedido = $pedidoStatement->fetch() ?: null;
    if ($pedido) {
        $itemsStatement = db()->prepare('SELECT nombre_producto, cantidad, precio_unitario, subtotal FROM pedido_items WHERE pedido_id = ? ORDER BY id');
        $itemsStatement->execute([$pedidoId]);
        $items = $itemsStatement->fetchAll();
    }
}

if (!$pedido) {
    http_response_code(404);
}

$pageTitle = $pedido ? 'Pedido registrado' : 'Pedido no encontrado';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <?php if ($pedido): ?>
        <?php
        $mensajeWhatsApp = "Hola, registré el pedido #{$pedidoId} en Team DimLer:\n";
        foreach ($items as $item) {
            $mensajeWhatsApp .= "- {$item['nombre_producto']} x {$item['cantidad']}\n";
        }
        $mensajeWhatsApp .= 'Total: $' . number_format((float) $pedido['total'], 2, ',', '.');
        $enlaceWhatsApp = 'https://wa.me/543764575838?text=' . rawurlencode($mensajeWhatsApp);
        ?>
        <section class="estado-vacio pedido-confirmacion">
            <h2>¡Tu pedido quedó registrado!</h2>
            <p>Pedido #<?= (int) $pedido['id'] ?> · <?= h(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></p>
            <ul class="pedido-confirmacion-items">
                <?php foreach ($items as $item): ?>
                    <li><?= h($item['nombre_producto']) ?> × <?= (int) $item['cantidad'] ?> — $<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></li>
                <?php endforeach; ?>
            </ul>
            <p class="producto-precio">Total: $<?= number_format((float) $pedido['total'], 2, ',', '.') ?></p>
            <p>El pago y la disponibilidad de productos por encargo se coordinan por separado.</p>
            <a class="boton-principal" href="<?= h($enlaceWhatsApp) ?>" target="_blank" rel="noopener noreferrer">Consultar el pedido por WhatsApp</a>
            <p><a class="enlace-volver" href="mis-pedidos.php">Ver mis pedidos</a></p>
        </section>
    <?php else: ?>
        <section class="estado-vacio">
            <h2>No encontramos ese pedido</h2>
            <a class="boton-principal" href="mis-pedidos.php">Volver a mis pedidos</a>
        </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
