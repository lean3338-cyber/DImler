<?php
require_once __DIR__ . '/includes/bootstrap.php';

$esLocal = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
if (!$esLocal) {
    http_response_code(404);
    exit('Página no encontrada.');
}

$errores = [];
$email = '';
try {
    $conexion = db();
} catch (RuntimeException $exception) {
    http_response_code(503);
    $pageTitle = 'Base de datos no disponible';
    require __DIR__ . '/includes/head.php';
    ?>
    <main class="principal">
        <section class="estado-vacio">
            <h2>No se puede habilitar el administrador todavía</h2>
            <p>Iniciá MySQL desde el panel de XAMPP y confirmá que la base <strong>teamdimler</strong> esté creada e importada. Después recargá esta página.</p>
            <a class="boton-principal" href="admin-demo.html">Ver panel de demostración</a>
        </section>
    </main>
    <?php
    exit;
}
$tablaExiste = (bool) $conexion->query(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'"
)->fetchColumn();

if (!$tablaExiste) {
    http_response_code(503);
    exit('Primero importá database/schema.sql en phpMyAdmin.');
}

$columnaRolExiste = (bool) $conexion->query(
    "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios' AND COLUMN_NAME = 'rol'"
)->fetchColumn();

if (!$columnaRolExiste) {
    $conexion->exec("ALTER TABLE usuarios ADD COLUMN rol ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente' AFTER password_hash");
}

$cantidadAdmins = (int) $conexion->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'admin'")->fetchColumn();
if ($cantidadAdmins > 0) {
    http_response_code(403);
    exit('La configuración inicial ya fue completada. Iniciá sesión con una cuenta administradora.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_valid_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $contrasena = (string) ($_POST['contrasena'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $contrasena === '') {
        $errores[] = 'Ingresá el correo y la contraseña de tu cuenta existente.';
    } else {
        $statement = $conexion->prepare('SELECT id, nombre, email, password_hash FROM usuarios WHERE email = ?');
        $statement->execute([$email]);
        $usuario = $statement->fetch();

        if (!$usuario || !password_verify($contrasena, $usuario['password_hash'])) {
            $errores[] = 'El correo o la contraseña no coinciden con una cuenta existente.';
        } else {
            $statement = $conexion->prepare("UPDATE usuarios SET rol = 'admin' WHERE id = ?");
            $statement->execute([$usuario['id']]);
            session_regenerate_id(true);
            $_SESSION['usuario'] = [
                'id' => (int) $usuario['id'],
                'nombre' => $usuario['nombre'],
                'email' => $usuario['email'],
                'rol' => 'admin',
            ];
            set_flash('Tu cuenta ya tiene acceso de administración.');
            redirect('admin.php');
        }
    }
}

$pageTitle = 'Habilitar administración';
require __DIR__ . '/includes/head.php';
require __DIR__ . '/includes/header.php';
?>
<main class="principal">
    <h2 class="titulo-pagina">Habilitar administración</h2>
    <section class="formulario-panel">
        <p>Ingresá las credenciales de una cuenta ya creada en Team DimLer. Se habilitará esa cuenta como administradora; la contraseña no se modifica.</p>
        <?php foreach ($errores as $error): ?>
            <p class="aviso aviso-error" role="alert"><?= h($error) ?></p>
        <?php endforeach; ?>
        <form class="formulario-demo" method="post" action="admin-inicializar.php">
            <?= csrf_field() ?>
            <label for="email">Correo electrónico de la cuenta</label>
            <input id="email" name="email" type="email" autocomplete="username" value="<?= h($email) ?>" required>
            <label for="contrasena">Contraseña actual de la cuenta</label>
            <div class="campo-contrasena">
                <input id="contrasena" name="contrasena" type="password" autocomplete="current-password" required>
                <button class="toggle-password" type="button" aria-label="Mostrar contraseña" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                </button>
            </div>
            <button class="boton-principal" type="submit">Habilitar mi cuenta como admin</button>
        </form>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
