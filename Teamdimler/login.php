<?php
require_once __DIR__ . '/includes/bootstrap.php';

$errores = [];
$email = '';
$destinoPermitido = ['index.php', 'carrito.php', 'favoritos.php', 'mis-pedidos.php'];
$destino = (string) ($_POST['destino'] ?? $_GET['destino'] ?? 'index.php');
if (!in_array($destino, $destinoPermitido, true)) {
    $destino = 'index.php';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $contrasena = (string) ($_POST['contrasena'] ?? '');
    $statement = db()->prepare('SELECT id, nombre, email, password_hash, rol FROM usuarios WHERE email = ?');
    $statement->execute([$email]);
    $usuario = $statement->fetch();

    if (!$usuario || !password_verify($contrasena, $usuario['password_hash'])) {
        $errores[] = 'Correo o contraseña incorrectos.';
    } else {
        session_regenerate_id(true);
        $_SESSION['usuario'] = [
            'id' => (int) $usuario['id'],
            'nombre' => $usuario['nombre'],
            'email' => $usuario['email'],
            'rol' => $usuario['rol'],
        ];
        set_flash('Iniciaste sesión correctamente.');
        redirect(is_admin() ? 'admin.php' : $destino);
    }
}

$pageTitle = 'Iniciar sesión';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Iniciar sesión</h2>
    <section class="formulario-panel">
        <?php foreach ($errores as $error): ?>
            <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
        <?php endforeach; ?>
        <form class="formulario-demo" method="post" action="login.php">
            <?= csrf_field() ?>
            <input type="hidden" name="destino" value="<?= h($destino) ?>">
            <label for="email">Correo electrónico</label>
            <input id="email" name="email" type="email" autocomplete="email" value="<?= h($email) ?>" required>
            <label for="contrasena">Contraseña</label>
            <div class="campo-contrasena">
                <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
                <button class="toggle-password" type="button" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <button class="boton-principal" type="submit">Iniciar sesión</button>
        </form>
        <p class="enlace-formulario">¿Todavía no tenés cuenta? <a href="registro.php">Registrate</a></p>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
