<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_admin();

$errores = [];
$categorias = ['crochet', 'tejidos', 'manualidades', 'libreria', 'costura'];
$estados = ['pendiente', 'preparando', 'entregado'];
$productoEnEdicion = null;
$productoEditarId = filter_input(INPUT_GET, 'editar', FILTER_VALIDATE_INT);
if ($productoEditarId) {
    $editarStatement = db()->prepare('SELECT * FROM productos WHERE id = ?');
    $editarStatement->execute([$productoEditarId]);
    $productoEnEdicion = $editarStatement->fetch() ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $accion = (string) ($_POST['accion'] ?? '');

    try {
        if ($accion === 'guardar_producto') {
            $productoIdTexto = trim((string) ($_POST['producto_id'] ?? ''));
            $productoId = $productoIdTexto === '' ? null : filter_var($productoIdTexto, FILTER_VALIDATE_INT);
            $categoria = (string) ($_POST['categoria'] ?? '');
            $nombre = trim((string) ($_POST['nombre'] ?? ''));
            $descripcion = trim((string) ($_POST['descripcion'] ?? ''));
            $medida = trim((string) ($_POST['medida'] ?? ''));
            $color = trim((string) ($_POST['color'] ?? ''));
            $material = trim((string) ($_POST['material'] ?? ''));
            $stockTexto = trim((string) ($_POST['stock'] ?? ''));
            $stock = $stockTexto === '' ? null : filter_var($stockTexto, FILTER_VALIDATE_INT);
            $disponible = filter_var($_POST['disponible'] ?? null, FILTER_VALIDATE_INT);
            $precio = filter_var($_POST['precio'] ?? null, FILTER_VALIDATE_FLOAT);

            if ($productoIdTexto !== '' && (!$productoId || $productoId < 1)) {
                $errores[] = 'El producto que intentás editar no es válido.';
            } elseif ($productoId) {
                $editarStatement = db()->prepare('SELECT id, categoria, imagen, stock, disponible FROM productos WHERE id = ?');
                $editarStatement->execute([$productoId]);
                $productoGuardado = $editarStatement->fetch();
                if (!$productoGuardado) {
                    $errores[] = 'No se encontró el producto que intentás editar.';
                } else {
                    $productoEnEdicion = $productoGuardado;
                }
            }

            if (!in_array($categoria, $categorias, true)) {
                $errores[] = 'Elegí una categoría válida.';
            }
            if ($nombre === '' || mb_strlen($nombre) > 160) {
                $errores[] = 'El nombre debe tener entre 1 y 160 caracteres.';
            }
            if ($descripcion === '') {
                $errores[] = 'La descripción es obligatoria.';
            }
            if (mb_strlen($medida) > 120 || mb_strlen($color) > 100 || mb_strlen($material) > 160) {
                $errores[] = 'Revisá la longitud de talle/medida, color o material.';
            }
            $esPorPedido = in_array($categoria, ['crochet', 'tejidos'], true);
            if ($stockTexto !== '' && ($stock === false || $stock < 0 || $stock > 4294967295)) {
                $errores[] = 'Ingresá una cantidad de stock válida.';
            }
            if (!$esPorPedido && !$productoId && $stock === null) {
                $errores[] = 'Ingresá la cantidad inicial para este producto.';
            }
            if ($esPorPedido && !in_array($disponible, [0, 1], true)) {
                $errores[] = 'Elegí si el producto está disponible para pedidos.';
            }
            if (!$esPorPedido) {
                $disponible = $stock === null
                    ? (int) ($productoEnEdicion['disponible'] ?? 1)
                    : (int) ($stock > 0);
            }
            if ($precio === false || $precio < 0 || $precio > 99999999.99) {
                $errores[] = 'Ingresá un precio válido.';
            }

            $archivoImagen = $_FILES['imagen'] ?? null;
            $subirImagen = is_array($archivoImagen) && ($archivoImagen['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;
            if ($subirImagen) {
                if (($archivoImagen['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                    $errores[] = 'No se pudo recibir la foto. Probá con otro archivo de hasta 5 MB.';
                } elseif (($archivoImagen['size'] ?? 0) < 1 || $archivoImagen['size'] > 5 * 1024 * 1024 || !is_uploaded_file($archivoImagen['tmp_name'] ?? '')) {
                    $errores[] = 'La foto debe pesar hasta 5 MB y ser una carga válida.';
                } else {
                    $tipoArchivo = (new finfo(FILEINFO_MIME_TYPE))->file($archivoImagen['tmp_name']);
                    $tiposImagen = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
                    if (!isset($tiposImagen[$tipoArchivo]) || @getimagesize($archivoImagen['tmp_name']) === false) {
                        $errores[] = 'La foto debe ser una imagen JPG, PNG o WebP válida.';
                    }
                }
            } elseif (!$productoId) {
                $errores[] = 'Elegí una foto del producto.';
            }

            if (!$errores) {
                $imagen = $productoEnEdicion['imagen'] ?? '';
                $imagenSubida = null;
                if ($subirImagen) {
                    $directorioImagenes = __DIR__ . '/img/productos';
                    if (!is_dir($directorioImagenes) && !mkdir($directorioImagenes, 0755, true) && !is_dir($directorioImagenes)) {
                        throw new RuntimeException('No se pudo crear la carpeta para las fotos de productos.');
                    }
                    $nombreImagen = bin2hex(random_bytes(16)) . '.' . $tiposImagen[$tipoArchivo];
                    $rutaDestino = $directorioImagenes . '/' . $nombreImagen;
                    if (!move_uploaded_file($archivoImagen['tmp_name'], $rutaDestino)) {
                        throw new RuntimeException('No se pudo guardar la foto del producto.');
                    }
                    $imagenSubida = $rutaDestino;
                    $imagen = 'img/productos/' . $nombreImagen;
                }

                if ($productoId) {
                    $statement = db()->prepare('UPDATE productos SET categoria = ?, nombre = ?, descripcion = ?, medida = ?, color = ?, material = ?, stock = ?, disponible = ?, precio = ?, imagen = ? WHERE id = ?');
                    $statement->execute([$categoria, $nombre, $descripcion, $medida ?: null, $color ?: null, $material ?: null, $stock, $disponible, $precio, $imagen, $productoId]);
                    set_flash('Producto actualizado.');
                } else {
                    $statement = db()->prepare('INSERT INTO productos (categoria, nombre, descripcion, medida, color, material, stock, disponible, precio, imagen) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $statement->execute([$categoria, $nombre, $descripcion, $medida ?: null, $color ?: null, $material ?: null, $stock, $disponible, $precio, $imagen]);
                    set_flash('Producto agregado.');
                }
                redirect('admin.php');
            }
        } elseif ($accion === 'cambiar_producto') {
            $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $activo = filter_var($_POST['activo'] ?? null, FILTER_VALIDATE_INT);
            if (!$productoId || !in_array($activo, [0, 1], true)) {
                $errores[] = 'La solicitud para actualizar el producto no es válida.';
            } else {
                $statement = db()->prepare('UPDATE productos SET activo = ? WHERE id = ?');
                $statement->execute([$activo, $productoId]);
                set_flash($activo ? 'Producto publicado.' : 'Producto desactivado.');
                redirect('admin.php');
            }
        } elseif ($accion === 'cambiar_disponibilidad') {
            $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $disponible = filter_var($_POST['disponible'] ?? null, FILTER_VALIDATE_INT);
            if (!$productoId || !in_array($disponible, [0, 1], true)) {
                $errores[] = 'La solicitud para cambiar la disponibilidad no es válida.';
            } else {
                $statement = db()->prepare("UPDATE productos SET disponible = ? WHERE id = ? AND categoria IN ('crochet', 'tejidos')");
                $statement->execute([$disponible, $productoId]);
                if ($statement->rowCount() !== 1) {
                    $errores[] = 'La disponibilidad manual solo se usa para Crochet y Tejidos.';
                } else {
                    set_flash($disponible ? 'Producto disponible para pedidos.' : 'Producto marcado como no disponible.');
                    redirect('admin.php');
                }
            }
        } elseif ($accion === 'actualizar_stock') {
            $productoId = filter_var($_POST['producto_id'] ?? null, FILTER_VALIDATE_INT);
            $stockTexto = trim((string) ($_POST['stock'] ?? ''));
            $stock = filter_var($stockTexto, FILTER_VALIDATE_INT);
            if (!$productoId || $stock === false || $stock < 0 || $stock > 4294967295) {
                $errores[] = 'Ingresá una cantidad válida para el producto.';
            } else {
                $productoStatement = db()->prepare('SELECT categoria FROM productos WHERE id = ?');
                $productoStatement->execute([$productoId]);
                $categoriaProducto = $productoStatement->fetchColumn();
                if ($categoriaProducto === false) {
                    $errores[] = 'No se encontró el producto al que querés actualizarle la cantidad.';
                } else {
                    if (in_array($categoriaProducto, ['crochet', 'tejidos'], true)) {
                        $statement = db()->prepare('UPDATE productos SET stock = ? WHERE id = ?');
                        $statement->execute([$stock, $productoId]);
                    } else {
                        $statement = db()->prepare('UPDATE productos SET stock = ?, disponible = ? WHERE id = ?');
                        $statement->execute([$stock, (int) ($stock > 0), $productoId]);
                    }
                    set_flash('Cantidad actualizada: ' . $stock . ' unidades.');
                    redirect('admin.php');
                }
            }
        } elseif ($accion === 'cambiar_pedido') {
            $pedidoId = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
            $estado = (string) ($_POST['estado'] ?? '');
            if (!$pedidoId || !in_array($estado, $estados, true)) {
                $errores[] = 'La solicitud para actualizar el pedido no es válida.';
            } else {
                $statement = db()->prepare('UPDATE pedidos SET estado = ? WHERE id = ?');
                $statement->execute([$estado, $pedidoId]);
                set_flash("Estado del pedido #{$pedidoId} actualizado.");
                redirect('admin.php');
            }
        } elseif ($accion === 'cancelar_pedido') {
            $pedidoId = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
            if (!$pedidoId) {
                $errores[] = 'La solicitud para cancelar el pedido no es válida.';
            } else {
                $conexion = db();
                $conexion->beginTransaction();
                $pedidoStatement = $conexion->prepare("SELECT estado FROM pedidos WHERE id = ? FOR UPDATE");
                $pedidoStatement->execute([$pedidoId]);
                $pedidoEstado = $pedidoStatement->fetchColumn();
                if (in_array($pedidoEstado, ['pendiente', 'preparando'], true)) {
                    $itemsStatement = $conexion->prepare('SELECT i.producto_id, i.cantidad, p.categoria, p.stock FROM pedido_items i LEFT JOIN productos p ON p.id = i.producto_id WHERE i.pedido_id = ?');
                    $itemsStatement->execute([$pedidoId]);
                    $reponerStock = $conexion->prepare('UPDATE productos SET stock = stock + ?, disponible = 1 WHERE id = ? AND stock IS NOT NULL');
                    foreach ($itemsStatement->fetchAll() as $item) {
                        if ($item['producto_id'] !== null && !in_array($item['categoria'], ['crochet', 'tejidos'], true) && $item['stock'] !== null) {
                            $reponerStock->execute([$item['cantidad'], $item['producto_id']]);
                        }
                    }
                    $cancelarStatement = $conexion->prepare("UPDATE pedidos SET estado = 'cancelado' WHERE id = ?");
                    $cancelarStatement->execute([$pedidoId]);
                    $conexion->commit();
                    set_flash("Pedido #{$pedidoId} cancelado.");
                    redirect('admin.php');
                }
                $conexion->rollBack();
                $errores[] = 'Solo se pueden cancelar pedidos pendientes o en preparación.';
            }
        } else {
            $errores[] = 'La acción solicitada no es válida.';
        }
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        $errores[] = 'No se pudo guardar el cambio. Revisá que no exista otro producto con la misma categoría y nombre.';
        if (isset($imagenSubida) && $imagenSubida !== null && is_file($imagenSubida)) {
            unlink($imagenSubida);
        }
    } catch (RuntimeException $exception) {
        error_log($exception->getMessage());
        $errores[] = $exception->getMessage();
        if (isset($imagenSubida) && $imagenSubida !== null && is_file($imagenSubida)) {
            unlink($imagenSubida);
        }
    }

    if ($accion === 'guardar_producto' && $errores) {
        $productoEnEdicion = [
            'id' => $productoId ?? null,
            'categoria' => $categoria ?? '',
            'nombre' => $nombre ?? '',
            'descripcion' => $descripcion ?? '',
            'medida' => $medida ?? '',
            'color' => $color ?? '',
            'material' => $material ?? '',
            'stock' => $stockTexto ?? '',
            'disponible' => $disponible ?? 1,
            'precio' => $_POST['precio'] ?? '',
            'imagen' => $productoEnEdicion['imagen'] ?? '',
        ];
    }
}

$productos = db()->query('SELECT id, categoria, nombre, descripcion, medida, color, material, stock, disponible, precio, imagen, activo FROM productos ORDER BY creado_en DESC, id DESC')->fetchAll();
$umbralPocoStock = 3;
$productosPocoStock = array_values(array_filter($productos, static function (array $producto) use ($umbralPocoStock): bool {
    return $producto['stock'] !== null && (int) $producto['stock'] <= $umbralPocoStock;
}));
$pedidos = db()->query('SELECT p.id, p.total, p.estado, p.creado_en, u.nombre, u.email FROM pedidos p JOIN usuarios u ON u.id = p.usuario_id ORDER BY p.creado_en DESC, p.id DESC')->fetchAll();
$productosPublicados = 0;
foreach ($productos as $producto) {
    if ((int) $producto['activo'] === 1) {
        $productosPublicados++;
    }
}
$pedidosPendientes = 0;
$ventasEntregadas = 0.0;
foreach ($pedidos as $pedido) {
    if ($pedido['estado'] === 'pendiente') {
        $pedidosPendientes++;
    }
    if ($pedido['estado'] === 'entregado') {
        $ventasEntregadas += (float) $pedido['total'];
    }
}
$itemsPorPedido = [];
if ($pedidos) {
    $itemStatement = db()->query('SELECT pedido_id, nombre_producto, cantidad, subtotal FROM pedido_items ORDER BY pedido_id DESC, id');
    foreach ($itemStatement->fetchAll() as $item) {
        $itemsPorPedido[$item['pedido_id']][] = $item;
    }
}

$pageTitle = 'Administración';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Administración</h2>
    <?php foreach ($errores as $error): ?>
        <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
    <?php endforeach; ?>

    <section class="admin-dashboard" aria-labelledby="dashboard-titulo">
        <h3 id="dashboard-titulo">Resumen del negocio</h3>
        <div class="admin-dashboard-grid">
            <article class="admin-dashboard-card">
                <span>Productos</span>
                <strong><?= count($productos) ?></strong>
                <small><?= $productosPublicados ?> publicados</small>
            </article>
            <article class="admin-dashboard-card">
                <span>Pedidos pendientes</span>
                <strong><?= $pedidosPendientes ?></strong>
                <small>Requieren seguimiento</small>
            </article>
            <article class="admin-dashboard-card">
                <span>Ventas entregadas</span>
                <strong>$<?= number_format($ventasEntregadas, 2, ',', '.') ?></strong>
                <small>Total de pedidos entregados</small>
            </article>
            <article class="admin-dashboard-card<?= $productosPocoStock ? ' admin-dashboard-card-alerta' : '' ?>">
                <span>Productos con poco stock</span>
                <strong><?= count($productosPocoStock) ?></strong>
                <small><?= $productosPocoStock ? 'Con 3 unidades o menos' : 'Sin alertas de stock' ?></small>
            </article>
        </div>
        <nav class="admin-dashboard-links" aria-label="Accesos rápidos del panel">
            <a class="boton-principal" href="#form-producto-titulo">Agregar producto</a>
            <a class="boton-secundario" href="#pedidos-admin-titulo">Ver pedidos</a>
        </nav>
    </section>

    <section aria-labelledby="form-producto-titulo">
        <h3 id="form-producto-titulo"><?= $productoEnEdicion ? 'Editar producto' : 'Agregar producto' ?></h3>
        <?php if ($productoEnEdicion && !empty($productoEnEdicion['imagen'])): ?>
            <p>Foto actual: <img class="admin-producto-imagen" src="<?= h($productoEnEdicion['imagen']) ?>" alt="<?= h($productoEnEdicion['nombre'] ?? 'Foto actual del producto') ?>"></p>
        <?php endif; ?>
        <form class="formulario-panel formulario-demo" method="post" action="admin.php" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="accion" value="guardar_producto">
            <input type="hidden" name="producto_id" value="<?= h($productoEnEdicion['id'] ?? '') ?>">
            <label for="categoria">Categoría</label>
            <select id="categoria" name="categoria" required>
                <option value="">Elegí una categoría</option>
                <?php foreach ($categorias as $categoria): ?>
                    <option value="<?= h($categoria) ?>" <?= ($productoEnEdicion['categoria'] ?? '') === $categoria ? 'selected' : '' ?>><?= h($categoria === 'libreria' ? 'Librería' : ucfirst($categoria)) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" maxlength="160" value="<?= h($productoEnEdicion['nombre'] ?? '') ?>" required>
            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3" required><?= h($productoEnEdicion['descripcion'] ?? '') ?></textarea>
            <label for="medida">Talle o medida</label>
            <input id="medida" name="medida" maxlength="120" value="<?= h($productoEnEdicion['medida'] ?? '') ?>" placeholder="Ej.: talle único, 30 cm">
            <label for="color">Color</label>
            <input id="color" name="color" maxlength="100" value="<?= h($productoEnEdicion['color'] ?? '') ?>" placeholder="Ej.: celeste y blanco">
            <label for="material">Material</label>
            <input id="material" name="material" maxlength="160" value="<?= h($productoEnEdicion['material'] ?? '') ?>" placeholder="Ej.: lana, jean">
            <label for="stock">Cantidad disponible</label>
            <input id="stock" name="stock" type="number" min="0" max="4294967295" step="1" value="<?= h($productoEnEdicion['stock'] ?? '') ?>" placeholder="Ingresá las unidades disponibles">
            <small id="stock-ayuda">Para Crochet y Tejidos la cantidad es de control interno; la disponibilidad para clientes se gestiona aparte.</small>
            <div id="disponibilidad-manual">
                <label for="disponible">Pedidos de Crochet y Tejidos</label>
                <select id="disponible" name="disponible">
                    <option value="1" <?= (string) ($productoEnEdicion['disponible'] ?? '1') === '1' ? 'selected' : '' ?>>Disponible para pedidos</option>
                    <option value="0" <?= (string) ($productoEnEdicion['disponible'] ?? '1') === '0' ? 'selected' : '' ?>>No disponible para pedidos</option>
                </select>
            </div>
            <label for="precio">Precio</label>
            <input id="precio" name="precio" type="number" min="0" step="0.01" value="<?= h($productoEnEdicion['precio'] ?? '') ?>" required>
            <label for="imagen">Foto del producto (JPG, PNG o WebP; máximo 5 MB)</label>
            <input id="imagen" name="imagen" type="file" accept="image/jpeg,image/png,image/webp" <?= $productoEnEdicion ? '' : 'required' ?>>
            <button class="boton-principal" type="submit"><?= $productoEnEdicion ? 'Guardar cambios' : 'Agregar producto' ?></button>
            <?php if ($productoEnEdicion): ?><a class="boton-secundario" href="admin.php">Cancelar edición</a><?php endif; ?>
        </form>
    </section>

    <section id="productos-admin-titulo" aria-labelledby="productos-admin-heading">
        <div class="admin-seccion-titulo">
            <h3 id="productos-admin-heading">Productos</h3>
            <span class="admin-productos-total"><?= count($productos) ?> productos en total</span>
        </div>
        <?php if ($productosPocoStock): ?>
            <aside class="admin-alerta-stock" aria-labelledby="admin-alerta-stock-titulo">
                <h4 id="admin-alerta-stock-titulo">Revisar stock bajo</h4>
                <p>Estos productos tienen <?= $umbralPocoStock ?> unidades o menos. La alerta es solo para administración; no cambia cómo se muestran a los clientes.</p>
                <ul>
                    <?php foreach ($productosPocoStock as $producto): ?>
                        <li>
                            <a href="#stock-producto-<?= (int) $producto['id'] ?>"><?= h($producto['nombre']) ?></a>
                            <strong><?= (int) $producto['stock'] ?> <?= (int) $producto['stock'] === 1 ? 'unidad' : 'unidades' ?></strong>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </aside>
        <?php endif; ?>
        <?php if (!$productos): ?>
            <p>No hay productos cargados.</p>
        <?php else: ?>
            <div class="admin-filtros-productos" role="search" aria-label="Buscar y filtrar productos">
                <div>
                    <label for="admin-buscar-producto">Buscar producto</label>
                    <input id="admin-buscar-producto" type="search" placeholder="Nombre del producto">
                </div>
                <div>
                    <label for="admin-filtrar-categoria">Categoría</label>
                    <select id="admin-filtrar-categoria">
                        <option value="">Todas</option>
                        <?php foreach ($categorias as $categoria): ?>
                            <option value="<?= h($categoria) ?>"><?= h($categoria === 'libreria' ? 'Librería' : ucfirst($categoria)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label for="admin-filtrar-estado">Estado</label>
                    <select id="admin-filtrar-estado">
                        <option value="">Todos</option>
                        <option value="1">Publicados</option>
                        <option value="0">Desactivados</option>
                    </select>
                </div>
                <p id="admin-productos-resultados" aria-live="polite"><?= count($productos) ?> productos</p>
            </div>
            <div class="tabla-contenedor">
                <table class="tabla-carrito">
                    <thead><tr><th>Producto</th><th>Cantidad (admin)</th><th>Categoría</th><th>Características</th><th>Precio</th><th>Estado</th><th>Acción</th></tr></thead>
                    <tbody>
                    <?php foreach ($productos as $producto): ?>
                        <tr data-producto-nombre="<?= h($producto['nombre']) ?>" data-producto-categoria="<?= h($producto['categoria']) ?>" data-producto-activo="<?= (int) $producto['activo'] ?>">
                            <td><?= h($producto['nombre']) ?></td>
                            <td>
                                <?php if (in_array((string) $producto['categoria'], ['crochet', 'tejidos'], true)): ?>
                                    <small>Por pedido · <?= $producto['disponible'] ? 'disponible' : 'no disponible' ?></small>
                                <?php endif; ?>
                                <strong><?= $producto['stock'] === null ? 'Sin cargar' : (int) $producto['stock'] . ' unidades' ?></strong>
                                <?php if ($producto['stock'] !== null && (int) $producto['stock'] <= $umbralPocoStock): ?>
                                    <span class="admin-stock-bajo">Poco stock</span>
                                <?php endif; ?>
                                <form class="admin-stock-form" method="post" action="admin.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="actualizar_stock">
                                    <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                                    <input id="stock-producto-<?= (int) $producto['id'] ?>" name="stock" type="number" min="0" max="4294967295" step="1" value="<?= $producto['stock'] === null ? '' : (int) $producto['stock'] ?>" placeholder="Unidades" aria-label="Cantidad disponible para <?= h($producto['nombre']) ?>" required>
                                    <button class="boton-secundario" type="submit">Guardar</button>
                                </form>
                            </td>
                            <td><?= h(ucfirst($producto['categoria'])) ?></td>
                            <td><?= h(implode(' · ', array_filter([$producto['medida'], $producto['color'], $producto['material']]))) ?: '—' ?></td>
                            <td>$<?= number_format((float) $producto['precio'], 2, ',', '.') ?></td>
                            <td><?= $producto['activo'] ? 'Publicado' : 'Desactivado' ?></td>
                            <td>
                                <a class="boton-secundario" href="admin.php?editar=<?= (int) $producto['id'] ?>#form-producto-titulo">Editar</a>
                                <form method="post" action="admin.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="cambiar_producto">
                                    <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">
                                    <input type="hidden" name="activo" value="<?= $producto['activo'] ? 0 : 1 ?>">
                                    <button class="boton-secundario" type="submit"><?= $producto['activo'] ? 'Desactivar' : 'Publicar' ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <p id="admin-productos-sin-resultados" class="estado-vacio" hidden>No hay productos que coincidan con esos filtros.</p>
            </div>
        <?php endif; ?>
    </section>

    <section id="pedidos-admin-titulo" aria-labelledby="pedidos-admin-heading">
        <h3 id="pedidos-admin-heading">Pedidos</h3>
        <?php if (!$pedidos): ?>
            <p>Todavía no hay pedidos registrados.</p>
        <?php else: ?>
            <div class="lista-pedidos">
                <?php foreach ($pedidos as $pedido): ?>
                    <article class="pedido-card">
                        <h4>Pedido #<?= (int) $pedido['id'] ?> — <?= h($pedido['nombre']) ?></h4>
                        <p><?= h($pedido['email']) ?> · <?= h(date('d/m/Y H:i', strtotime($pedido['creado_en']))) ?></p>
                        <ul>
                            <?php foreach ($itemsPorPedido[$pedido['id']] ?? [] as $item): ?>
                                <li><?= h($item['nombre_producto']) ?> × <?= (int) $item['cantidad'] ?> — $<?= number_format((float) $item['subtotal'], 2, ',', '.') ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p><strong>Total:</strong> $<?= number_format((float) $pedido['total'], 2, ',', '.') ?></p>
                        <?php if ($pedido['estado'] === 'cancelado'): ?>
                            <p>Este pedido ya está cancelado.</p>
                        <?php else: ?>
                            <div class="pedido-admin-acciones">
                                <form method="post" action="admin.php">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="accion" value="cambiar_pedido">
                                    <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                                    <label for="estado-<?= (int) $pedido['id'] ?>">Estado</label>
                                    <select id="estado-<?= (int) $pedido['id'] ?>" name="estado">
                                        <?php foreach ($estados as $estado): ?>
                                            <option value="<?= h($estado) ?>" <?= $estado === $pedido['estado'] ? 'selected' : '' ?>><?= h(ucfirst($estado)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="boton-secundario" type="submit">Actualizar estado</button>
                                </form>
                                <?php if (in_array($pedido['estado'], ['pendiente', 'preparando'], true)): ?>
                                    <form method="post" action="admin.php">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="accion" value="cancelar_pedido">
                                        <input type="hidden" name="pedido_id" value="<?= (int) $pedido['id'] ?>">
                                        <button class="boton-cancelar-pedido" type="submit">Cancelar pedido</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
<script src="js/admin-product-form.js" defer></script>
<script src="js/admin-product-filters.js" defer></script>
</body>
</html>
