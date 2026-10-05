<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_login();

$statement = db()->prepare('SELECT id, total, estado, creado_en FROM pedidos WHERE usuario_id = ? ORDER BY creado_en DESC');
$statement->execute([current_user()['id']]);
$pedidos = $statement->fetchAll();
$itemsPorPedido = [];
if ($pedidos) {
    $itemStatement = db()->prepare('SELECT nombre_producto, precio_unitario, cantidad, subtotal FROM pedido_items WHERE pedido_id = ? ORDER BY id');
    foreach ($pedidos as $pedido) {
        $itemStatement->execute([$pedido['id']]);
        $itemsPorPedido[$pedido['id']] = $itemStatement->fetchAll();
    }
}

$pageTitle = 'Mis pedidos';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Mis pedidos</h2>
    <?php if (!$pedidos): ?>
        <section class="estado-vacio">
            <p>Todavía no registraste pedidos.</p>
            <a class="boton-principal" href="libreria.php">Ir al catálogo</a>
        </section>
    <?php else: ?>
        <div class="lista-pedidos">
            <?php foreach ($pedidos as $pedido): ?>
                <article class="pedido-card">
                    <h3>Pedido #<?= (int) $pedido['id'] ?></h3>
                    <p>Fecha: <?= h(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></p>
                    <p>Estado: <strong><?= h(ucfirst($pedido['estado'])) ?></strong></p>
                    <ul>
                        <?php foreach ($itemsPorPedido[$pedido['id']] as $item): ?>
                            <li><?= h($item['nombre_producto']) ?> × <?= (int) $item['cantidad'] ?>: $<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <p class="producto-precio">Total: $<?= number_format((float) $pedido['total'], 2, ',', '.') ?></p>
                    <p>El pago se coordina por separado.</p>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
