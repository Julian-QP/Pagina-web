<?php
session_start();
require_once __DIR__ . '/conexion.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $conexion = new conexion();
        $user = $conexion->autenticarUsuario($email, $password);
        $conexion->close();
    } catch (RuntimeException $exception) {
        $user = false;
        $error = 'No fue posible conectar con la base de datos.';
    }

    if ($user !== false) {
        session_regenerate_id(true);
        $_SESSION['admin_logueado'] = true;
        $_SESSION['usuario_id'] = $user['id'];
        $_SESSION['usuario'] = $user['email'];
        $_SESSION['rol'] = $user['rol'];
        header("Location: index.php");
        exit();
    } elseif ($error === '') {
        $error = "Usuario o contraseña incorrectos.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login - Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <link rel="stylesheet" type="text/css" href="assets/css/paleta-roja.css">
  </head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm p-4" style="width: 100%; max-width: 400px;">
        <h3 class="text-center mb-4">Iniciar Sesión</h3>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="mb-3">
                <label class="form-label">Correo electrónico</label>
                <input type="email" name="email" class="form-control" required autocomplete="username">
            </div>
            <div class="mb-3">
                <label class="form-label">Contraseña</label>
                <input type="password" name="password" class="form-control" required autocomplete="current-password">
            </div>
            <button type="submit" class="btn btn-primary w-100">Entrar</button>
        </form>
        <div class="text-center mt-3">
            <a href="recuperar.php">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</body>
</html>