<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/conexion.php';
require_once dirname(__DIR__, 2) . '/PHPMailer/Exception.php';
require_once dirname(__DIR__, 2) . '/PHPMailer/PHPMailer.php';
require_once dirname(__DIR__, 2) . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

if (empty($_SESSION['csrf_recuperacion'])) {
    $_SESSION['csrf_recuperacion'] = bin2hex(random_bytes(32));
}
$mensaje = '';
$error = '';

function enviarCorreoRecuperacion(string $destinatario, string $nombre, string $enlace): void
{
    $config = static function (string $nombre, string $predeterminado = ''): string {
        $valor = getenv($nombre);
        if ($valor === false || $valor === '') {
            $valor = $_ENV[$nombre] ?? $_SERVER[$nombre] ?? $predeterminado;
        }
        return trim((string) $valor);
    };
    $usuarioSmtp = $config('DDP_SMTP_USERNAME');
    $contrasenaSmtp = $config('DDP_SMTP_PASSWORD');
    $remitente = $config('DDP_SMTP_FROM', $usuarioSmtp);
    if ($usuarioSmtp === '' || $contrasenaSmtp === '' || !filter_var($remitente, FILTER_VALIDATE_EMAIL)) {
        throw new RuntimeException('La configuración SMTP está incompleta.');
    }

    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host = $config('DDP_SMTP_HOST', 'smtp.gmail.com');
    $mail->SMTPAuth = true;
    $mail->Username = $usuarioSmtp;
    $mail->Password = $contrasenaSmtp;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = (int) $config('DDP_SMTP_PORT', '587');
    $mail->CharSet = 'UTF-8';
    $mail->setFrom($remitente, 'Sistema de recuperación DDP');
    $mail->addAddress($destinatario, $nombre);
    $mail->isHTML(true);
    $mail->Subject = 'Recuperación de contraseña - DDP';
    $mail->Body = 'Hola ' . htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8')
        . ',<br><br>Usa este enlace para establecer una nueva contraseña '
        . '(válido por 1 hora):<br><br><a href="' . htmlspecialchars($enlace, ENT_QUOTES, 'UTF-8')
        . '">Restablecer mi contraseña</a><br><br>Si no solicitaste este cambio, ignora este mensaje.';
    $mail->AltBody = "Hola {$nombre},\n\nUsa este enlace para establecer una nueva contraseña (válido por 1 hora):\n{$enlace}\n\nSi no solicitaste este cambio, ignora este mensaje.";
    $mail->send();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf_recuperacion'], (string) ($_POST['csrf'] ?? ''))) {
        $error = 'La solicitud no es válida. Inténtalo nuevamente.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Ingresa un correo electrónico válido.';
        } else {
            try {
                $conexion = new conexion();
                $recuperacion = $conexion->crearTokenRecuperacion($email);
                $conexion->close();
                if ($recuperacion !== null) {
                    $baseUrl = rtrim(getenv('DDP_APP_URL') ?: 'http://localhost/trab/ddp', '/');
                    $enlace = $baseUrl . '/admin/restablecer.php?token=' . urlencode($recuperacion['token']);
                    enviarCorreoRecuperacion($recuperacion['email'], $recuperacion['nombres'], $enlace);
                }
                $mensaje = 'Si el correo está registrado, recibirás instrucciones para recuperar tu contraseña.';
            } catch (Throwable $exception) {
                error_log('Recuperación DDP: ' . $exception->getMessage());
                $error = 'No fue posible procesar la solicitud. Inténtalo nuevamente más tarde.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Recuperar contraseña | Panel de Administración</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/paleta-roja.css">
</head>
<body class="bg-light d-flex align-items-center justify-content-center vh-100">
    <div class="card shadow-sm p-4" style="width:100%;max-width:400px">
        <h3 class="text-center mb-3">Recuperar contraseña</h3>
        <p class="text-muted">Ingresa tu correo y te enviaremos un enlace para crear una nueva contraseña.</p>
        <?php if ($mensaje !== ''): ?><div class="alert alert-success"><?= htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="alert alert-danger"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($_SESSION['csrf_recuperacion'], ENT_QUOTES, 'UTF-8') ?>">
            <label class="form-label" for="email">Correo electrónico</label>
            <input class="form-control mb-3" id="email" type="email" name="email" required autocomplete="email">
            <button class="btn btn-primary w-100" type="submit">Enviar enlace</button>
        </form>
        <a class="text-center mt-3" href="login.php">Volver al inicio de sesión</a>
    </div>
</body>
</html>
