<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errores = [];
$nombre = '';
$email = '';
$destinoPermitido = ['index.php', 'carrito.php'];
$destino = (string) ($_POST['destino'] ?? $_GET['destino'] ?? 'index.php');
if (!in_array($destino, $destinoPermitido, true)) {
    $destino = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $nombre = trim((string) ($_POST['nombre'] ?? ''));
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $contrasena = (string) ($_POST['contrasena'] ?? '');
    $confirmacion = (string) ($_POST['confirmacion'] ?? '');

    if ($nombre === '' || mb_strlen($nombre) > 120) {
        $errores[] = 'Ingresá un nombre válido.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errores[] = 'Ingresá un correo válido.';
    }
    if (strlen($contrasena) < 8) {
        $errores[] = 'La contraseña debe tener al menos 8 caracteres.';
    }
    if ($contrasena !== $confirmacion) {
        $errores[] = 'Las contraseñas no coinciden.';
    }

    if (!$errores) {
        $statement = db()->prepare('SELECT id FROM usuarios WHERE email = ?');
        $statement->execute([$email]);
        if ($statement->fetch()) {
            $errores[] = 'Ya existe una cuenta con ese correo.';
        } else {
            $statement = db()->prepare('INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)');
            $statement->execute([$nombre, $email, password_hash($contrasena, PASSWORD_DEFAULT)]);
            set_flash('La cuenta se creó correctamente. Iniciá sesión para continuar.');
            redirect('login.php?destino=' . rawurlencode($destino));
        }
    }
}

$pageTitle = 'Registro';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Crear cuenta</h2>
    <section class="formulario-panel">
        <?php foreach ($errores as $error): ?>
            <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
        <?php endforeach; ?>
        <form class="formulario-demo" method="post" action="registro.php">
            <?= csrf_field() ?>
            <input type="hidden" name="destino" value="<?= h($destino) ?>">
            <label for="nombre">Nombre</label>
            <input id="nombre" name="nombre" type="text" autocomplete="name" maxlength="120" value="<?= h($nombre) ?>" required>
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="190" value="<?= h($email) ?>" required>
            <label for="contrasena">Contraseña</label>
            <div class="campo-contrasena">
                <input id="contrasena" name="contrasena" type="password" autocomplete="new-password" minlength="8" required>
                <button class="toggle-password" type="button" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <label for="confirmacion">Confirmar contraseña</label>
            <div class="campo-contrasena">
                <input id="confirmacion" name="confirmacion" type="password" autocomplete="new-password" minlength="8" required>
                <button class="toggle-password" type="button" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <label class="aceptar-terminos"><input type="checkbox" required> Acepto los términos y condiciones</label>
            <button class="boton-principal" type="submit">Crear cuenta</button>
        </form>
        <p class="enlace-formulario">¿Ya tenés cuenta? <a href="login.php?destino=<?= h(rawurlencode($destino)) ?>">Iniciá sesión</a></p>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
