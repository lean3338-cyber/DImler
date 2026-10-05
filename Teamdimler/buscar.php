<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/product-list.php';

$categorias = ['crochet', 'tejidos', 'manualidades', 'libreria', 'costura'];
$ordenes = ['recientes', 'precio_menor', 'precio_mayor', 'nombre'];
$errores = [];
$texto = trim((string) ($_GET['q'] ?? ''));
$categoria = (string) ($_GET['categoria'] ?? '');
$medida = trim((string) ($_GET['medida'] ?? ''));
$color = trim((string) ($_GET['color'] ?? ''));
$material = trim((string) ($_GET['material'] ?? ''));
$orden = (string) ($_GET['orden'] ?? 'recientes');
$precioMinimoTexto = trim((string) ($_GET['precio_min'] ?? ''));
$precioMaximoTexto = trim((string) ($_GET['precio_max'] ?? ''));
$precioMinimo = $precioMinimoTexto === '' ? null : filter_var($precioMinimoTexto, FILTER_VALIDATE_FLOAT);
$precioMaximo = $precioMaximoTexto === '' ? null : filter_var($precioMaximoTexto, FILTER_VALIDATE_FLOAT);

if (mb_strlen($texto) > 120 || mb_strlen($medida) > 120 || mb_strlen($color) > 100 || mb_strlen($material) > 160) {
    $errores[] = 'Uno de los filtros supera la longitud permitida.';
}
if ($categoria !== '' && !in_array($categoria, $categorias, true)) {
    $errores[] = 'Elegí una categoría válida.';
}
if (($precioMinimoTexto !== '' && ($precioMinimo === false || $precioMinimo < 0))
    || ($precioMaximoTexto !== '' && ($precioMaximo === false || $precioMaximo < 0))) {
    $errores[] = 'Ingresá un rango de precios válido.';
}
if ($precioMinimo !== null && $precioMinimo !== false && $precioMaximo !== null && $precioMaximo !== false && $precioMinimo > $precioMaximo) {
    $errores[] = 'El precio mínimo no puede superar al máximo.';
}
if (!in_array($orden, $ordenes, true)) {
    $errores[] = 'Elegí un orden válido.';
}

$productos = [];
if (!$errores) {
    $condiciones = ['activo = 1'];
    $parametros = [];
    if ($texto !== '') {
        $condiciones[] = 'CONCAT_WS(" ", nombre, descripcion, categoria, medida, color, material) LIKE ?';
        $parametros[] = '%' . $texto . '%';
    }
    if ($categoria !== '') {
        $condiciones[] = 'categoria = ?';
        $parametros[] = $categoria;
    }
    if ($precioMinimo !== null && $precioMinimo !== false) {
        $condiciones[] = 'precio >= ?';
        $parametros[] = $precioMinimo;
    }
    if ($precioMaximo !== null && $precioMaximo !== false) {
        $condiciones[] = 'precio <= ?';
        $parametros[] = $precioMaximo;
    }
    foreach (['medida' => $medida, 'color' => $color, 'material' => $material] as $campo => $valor) {
        if ($valor !== '') {
            $condiciones[] = "{$campo} LIKE ?";
            $parametros[] = '%' . $valor . '%';
        }
    }
    $ordenSql = match ($orden) {
        'precio_menor' => 'precio ASC, nombre ASC',
        'precio_mayor' => 'precio DESC, nombre ASC',
        'nombre' => 'nombre ASC',
        default => 'creado_en DESC, id DESC',
    };
    $statement = db()->prepare('SELECT * FROM productos WHERE ' . implode(' AND ', $condiciones) . ' ORDER BY ' . $ordenSql);
    $statement->execute($parametros);
    $productos = $statement->fetchAll();
}

$favoritos = [];
if (is_logged_in() && $productos) {
    $favoriteStatement = db()->prepare('SELECT producto_id FROM favoritos WHERE usuario_id = ?');
    $favoriteStatement->execute([current_user()['id']]);
    $favoritos = array_map('intval', $favoriteStatement->fetchAll(PDO::FETCH_COLUMN));
}

$pageTitle = 'Buscar productos';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <a class="boton-secundario boton-volver-catalogo" href="index.php#catalogo">&larr; Volver al catálogo</a>
    <h2 class="titulo-pagina">Buscar productos</h2>
    <?php foreach ($errores as $error): ?>
        <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
    <?php endforeach; ?>
    <form class="filtros-catalogo" action="buscar.php" method="get">
        <div>
            <label for="filtro-texto">Nombre o descripción</label>
            <input id="filtro-texto" type="search" name="q" maxlength="120" value="<?= h($texto) ?>">
        </div>
        <div>
            <label for="filtro-categoria">Categoría</label>
            <select id="filtro-categoria" name="categoria">
                <option value="">Todas</option>
                <?php foreach ($categorias as $opcionCategoria): ?>
                    <option value="<?= h($opcionCategoria) ?>" <?= $categoria === $opcionCategoria ? 'selected' : '' ?>><?= h($opcionCategoria === 'libreria' ? 'Librería' : ucfirst($opcionCategoria)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="filtro-precio-min">Precio desde</label>
            <input id="filtro-precio-min" type="number" name="precio_min" min="0" step="0.01" value="<?= h($precioMinimoTexto) ?>">
        </div>
        <div>
            <label for="filtro-precio-max">Precio hasta</label>
            <input id="filtro-precio-max" type="number" name="precio_max" min="0" step="0.01" value="<?= h($precioMaximoTexto) ?>">
        </div>
        <div>
            <label for="filtro-medida">Talle o medida</label>
            <input id="filtro-medida" name="medida" maxlength="120" value="<?= h($medida) ?>">
        </div>
        <div>
            <label for="filtro-color">Color</label>
            <input id="filtro-color" name="color" maxlength="100" value="<?= h($color) ?>">
        </div>
        <div>
            <label for="filtro-material">Material</label>
            <input id="filtro-material" name="material" maxlength="160" value="<?= h($material) ?>">
        </div>
        <div>
            <label for="filtro-orden">Ordenar por</label>
            <select id="filtro-orden" name="orden">
                <option value="recientes" <?= $orden === 'recientes' ? 'selected' : '' ?>>Más recientes</option>
                <option value="precio_menor" <?= $orden === 'precio_menor' ? 'selected' : '' ?>>Menor precio</option>
                <option value="precio_mayor" <?= $orden === 'precio_mayor' ? 'selected' : '' ?>>Mayor precio</option>
                <option value="nombre" <?= $orden === 'nombre' ? 'selected' : '' ?>>Nombre</option>
            </select>
        </div>
        <div class="filtros-acciones">
            <button class="boton-principal" type="submit">Aplicar filtros</button>
            <a class="boton-secundario" href="buscar.php">Limpiar</a>
        </div>
    </form>
    <?php if (!$errores): ?>
        <p class="resultados-busqueda"><?= count($productos) ?> productos encontrados</p>
        <?php if ($productos): ?>
            <?php render_product_list($productos, $favoritos); ?>
        <?php else: ?>
            <section class="estado-vacio"><h3>No encontramos productos</h3><p>Probá cambiar el texto o los filtros.</p></section>
        <?php endif; ?>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
