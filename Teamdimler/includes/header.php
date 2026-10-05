<?php
require_once __DIR__ . '/bootstrap.php';
$usuarioActual = current_user();
?>
<header class="banner">
    <h1><a href="index.php">Team DimLer</a></h1>
    <nav aria-label="Navegación principal">
        <ul>
            <li><a href="index.php">Inicio</a></li>
            <li><a href="crochet.php">Crochet</a></li>
            <li><a href="tejidos.php">Tejidos</a></li>
            <li><a href="manualidades.php">Manualidades</a></li>
            <li><a href="libreria.php">Librería</a></li>
            <?php if (is_admin()): ?>
                <li><a href="admin.php">Panel de administración</a></li>
                <li><span>Admin: <?= h($usuarioActual['nombre']) ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            <?php elseif ($usuarioActual): ?>
                <li><a href="favoritos.php">Favoritos</a></li>
                <li><a href="mis-pedidos.php">Mis pedidos</a></li>
                <li><span>Hola, <?= h($usuarioActual['nombre']) ?></span></li>
                <li><a href="logout.php">Cerrar sesión</a></li>
            <?php else: ?>
                <li><a href="login.php">Iniciar sesión</a></li>
                <li><a href="registro.php">Registro</a></li>
            <?php endif; ?>
        </ul>
    </nav>
    <details class="busqueda-global">
        <summary aria-label="Abrir buscador" title="Buscar productos">
            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <circle cx="10.8" cy="10.8" r="6.8"></circle>
                <path d="m16 16 5 5"></path>
            </svg>
        </summary>
        <form action="buscar.php" method="get" role="search">
            <label class="solo-lectores" for="busqueda-global">Buscar productos</label>
            <input id="busqueda-global" type="search" name="q" placeholder="¿Qué estás buscando?" maxlength="120">
            <button type="submit" aria-label="Buscar productos">
                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <circle cx="10.8" cy="10.8" r="6.8"></circle>
                    <path d="m16 16 5 5"></path>
                </svg>
            </button>
        </form>
    </details>
    <?php if (!is_admin()): ?>
        <a class="cart-link" href="carrito.php" aria-label="Carrito con <?= cart_count() ?> productos" title="Ver carrito">
            <svg class="cart-icon" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                <path d="M3 4h2l2.2 10.1a2 2 0 0 0 2 1.6h8.7a2 2 0 0 0 1.9-1.4L22 8H6"></path>
                <circle cx="10" cy="20" r="1.4"></circle>
                <circle cx="18" cy="20" r="1.4"></circle>
            </svg>
            <span class="cart-label">Carrito</span>
            <span class="cart-badge"><?= cart_count() ?></span>
        </a>
    <?php endif; ?>
</header>
<?php if ($mensaje = take_flash()): ?>
    <p class="aviso aviso-<?= h($mensaje['type']) ?>" role="status"><?= h($mensaje['message']) ?></p>
<?php endif; ?>
