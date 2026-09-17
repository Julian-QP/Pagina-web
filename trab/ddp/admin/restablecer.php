<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/conexion.php';

$token = trim((string) ($_GET['token'] ?? $_POST['token'] ?? ''));
$error = '';
$exito = false;

if (empty($_SESSION['csrf_restablecer'])) {
    $_SESSION['csrf_restablecer'] = bin2hex(random_bytes(32));
}

if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
    $error = 'El enlace de recuperación no es válido o ya expiró.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_restablecer'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'La solicitud no es válida. Inténtalo nuevamente.';
    }
    $password = (string) ($_POST['password'] ?? '');
    $confirmacion = (string) ($_POST['password_confirmation'] ?? '');
    if ($error === '') {
        if (strlen($password) < 8 || !hash_equals($password, $confirmacion)) {
            $error = 'La contraseña debe tener al menos 8 caracteres y ambas deben coincidir.';
        }
    }
    if ($error === '') {
        try {
            $conexion = new conexion();
            $exito = $conexion->restablecerContrasena($token, $password);
            $conexion->close();
            if (!$exito) {
                $error = 'El enlace de recuperación no es válido o ya expiró.';
            }
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            $error = 'No fue posible actualizar la contraseña. Inténtalo nuevamente.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Restablecer contraseña | Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/paleta-roja.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm p-4" style="width:100%;max-width:400px">
        <h3 class="text-center mb-3">Nueva contraseña</h3>
        <?php if ($exito): ?>
            <div class="alert alert-success">Tu contraseña fue actualizada correctamente.</div>
            <a class="btn btn-primary w-100" href="login.php">Ir al inicio de sesión</a>
        <?php else: ?>
            <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
            <?php if ($error === '' || preg_match('/^[a-f0-9]{64}$/', $token)): ?>
            <form method="post">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_restablecer'], ENT_QUOTES, 'UTF-8') ?>">
                <label class="form-label" for="password">Nueva contraseña</label>
                <input class="form-control mb-3" id="password" type="password" name="password" minlength="8" required autocomplete="new-password">
                <label class="form-label" for="password_confirmation">Repite la contraseña</label>
                <input class="form-control mb-3" id="password_confirmation" type="password" name="password_confirmation" minlength="8" required autocomplete="new-password">
                <button class="btn btn-primary w-100" type="submit">Guardar contraseña</button>
            </form>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
