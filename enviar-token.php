<?php
declare(strict_types=1);

// Este archivo solo sirve para probar SMTP manualmente; el flujo real está en ddp/admin/recuperar.php.
require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$usuarioSmtp = trim((string) getenv('DDP_SMTP_USERNAME'));
$contrasenaSmtp = (string) getenv('DDP_SMTP_PASSWORD');
$destinatario = trim((string) (getenv('DDP_SMTP_TEST_RECIPIENT') ?: getenv('DDP_SMTP_FROM')));
if ($usuarioSmtp === '' || $contrasenaSmtp === '' || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
    http_response_code(500);
    exit('Configura DDP_SMTP_USERNAME, DDP_SMTP_PASSWORD y DDP_SMTP_TEST_RECIPIENT antes de usar esta prueba.');
}

$mail = new PHPMailer(true);

try {
    // 1. Configuración del servidor SMTP de Gmail
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $usuarioSmtp;
    $mail->Password   = $contrasenaSmtp;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // 2. Remitente y Destinatario
    $mail->setFrom(getenv('DDP_SMTP_FROM') ?: $usuarioSmtp, 'Sistema de Recuperacion');
    $mail->addAddress($destinatario);

    // 3. Simulación del Token de Recuperación
    $token_ejemplo = bin2hex(random_bytes(16));
    $enlace = "http://localhost/trab/reset-password.php?token=" . $token_ejemplo;

    // 4. Contenido del correo
    $mail->isHTML(true);
    $mail->Subject = 'Recuperacion de cuenta - Proyecto Universidad';
    $mail->Body    = 'Hola! Has solicitado recuperar tu contraseña. Haz clic en el siguiente enlace:<br><br>
                      <a href="' . $enlace . '">Restablecer mi contraseña</a>';

    // 5. Enviar mensaje
    $mail->send();
    echo '¡El correo con el token de recuperación se ha enviado correctamente! Revisa tu bandeja de entrada.';
    
} catch (Exception $e) {
    echo "No se pudo enviar el correo. Error de PHPMailer: {$mail->ErrorInfo}";
}
?>