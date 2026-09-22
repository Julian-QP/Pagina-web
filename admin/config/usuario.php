<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../autorizacion.php';
if (empty($_SESSION['admin_logueado'])) {
    header('Location: ../login.php');
    exit();
}
exigirGestionUsuarios();

require_once __DIR__ . '/../conexion.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$conexion = new conexion();
$existente = $id ? $conexion->obtenerUsuario($id) : null;

if ($id && $existente === null) {
    $conexion->close();
    http_response_code(404);
    exit('Usuario no encontrado.');
}

$formulario = [
    'nombres' => $existente['nombres'] ?? '',
    'ap_paterno' => $existente['ap_paterno'] ?? '',
    'ap_materno' => $existente['ap_materno'] ?? '',
    'email' => $existente['email'] ?? '',
    'rol' => $existente['rol'] ?? 'redactor',
];
$error = '';

function escaparUsuario(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['nombres', 'ap_paterno', 'ap_materno', 'email', 'rol'] as $campo) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $password = (string) ($_POST['password'] ?? '');
    if ($formulario['nombres'] === '' || $formulario['ap_paterno'] === '' ||
        !filter_var($formulario['email'], FILTER_VALIDATE_EMAIL) ||
        !in_array($formulario['rol'], ['admin', 'editor', 'redactor'], true) ||
        ($id === null && strlen($password) < 8) ||
        ($password !== '' && strlen($password) < 8)) {
        $error = 'Completa los campos correctamente. La contraseña debe tener al menos 8 caracteres.';
    } else {
        try {
            $datos = $formulario;
            $datos['password_hash'] = $password === '' ? '' : password_hash($password, PASSWORD_DEFAULT);
            $guardadoId = $conexion->guardarUsuario($datos, $id);
            $conexion->close();
            header('Location: usuario.php?id=' . $guardadoId . '&guardado=1');
            exit();
        } catch (mysqli_sql_exception | RuntimeException $exception) {
            $error = $exception->getMessage();
        }
    }
}

$conexion->close();
$guardado = isset($_GET['guardado']);
?>
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $id ? 'Editar' : 'Nuevo' ?> usuario | DDP</title>
  <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
  <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/vendors.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/app-lite.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/menu/menu-types/vertical-menu.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/responsive-admin.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/usuario.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/paleta-roja.css">
  </head>
<body class="vertical-layout vertical-menu 2-columns menu-expanded fixed-navbar" data-open="click" data-menu="vertical-menu" data-color="bg-chartbg" data-col="2-columns">
  <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true" data-img="../theme-assets/images/backgrounds/02.jpg">
    <div class="navbar-header"><ul class="nav navbar-nav flex-row">
      <li class="nav-item mr-auto"><a class="navbar-brand" href="../index.php"><img class="brand-logo" alt="Chameleon admin logo" src="../theme-assets/images/logo/logo.png"><h3 class="brand-text">DDP</h3></a></li>
      <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
    </ul></div>
    <div class="main-menu-content"><ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
      <li class="active"><a href="../index.php"><i class="ft-home"></i><span class="menu-title">Inicio</span></a></li>
      <li class="nav-item"><a href="../Reportaje.php"><i class="ft-book"></i><span class="menu-title">Reportaje</span></a></li>
      <li class="nav-item"><a href="../Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title">Noticias</span></a></li>
      <li class="nav-item"><a href="../Podcast.php"><i class="ft-music"></i><span class="menu-title">Podcast</span></a></li>
      <li class="nav-item"><a href="../video.php"><i class="ft-play"></i><span class="menu-title">videos</span></a></li>
      <li class="nav-item"><a href="../boletines.php"><i class="la la-leanpub"></i><span class="menu-title">Boletines</span></a></li>
      <?php if (!esEditor()): ?><li class="nav-item active"><a href="../usuarios.php"><i class="ft-user"></i><span class="menu-title">usuarios</span></a></li><?php endif; ?>
      <li class="nav-item"><a href="../logout.php"><i class="ft-power"></i><span class="menu-title">Cerrar Sesion</span></a></li>
    </ul></div>
    <div class="navigation-background"></div>
  </div>
  <div class="app-content content"><div class="content-wrapper"><div class="content-body usuarios-content usuarios-editor-content">
    <div class="usuarios-heading">
      <div><span class="usuarios-eyebrow">ADMINISTRACIÓN</span><h1><?= $id ? 'Editar usuario' : 'Nuevo usuario' ?></h1><p>Configura los datos y permisos de acceso.</p></div>
      <?php if (!esEditor()): ?><a class="usuarios-button usuarios-button-secondary" href="../usuarios.php">Volver a usuarios</a><?php endif; ?>
    </div>
    <?php if ($guardado): ?><div class="usuarios-alert success">El usuario se guardó correctamente.</div><?php endif; ?>
    <?php if ($error !== ''): ?><div class="usuarios-alert error"><?= escaparUsuario($error) ?></div><?php endif; ?>
    <form class="usuarios-form" method="post">
      <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
      <div class="usuarios-form-grid">
        <label>Nombres *<input type="text" name="nombres" maxlength="100" value="<?= escaparUsuario($formulario['nombres']) ?>" required></label>
        <label>Apellido paterno *<input type="text" name="ap_paterno" maxlength="100" value="<?= escaparUsuario($formulario['ap_paterno']) ?>" required></label>
        <label>Apellido materno<input type="text" name="ap_materno" maxlength="100" value="<?= escaparUsuario($formulario['ap_materno']) ?>"></label>
        <label>Correo electrónico *<input type="email" name="email" maxlength="150" value="<?= escaparUsuario($formulario['email']) ?>" required></label>
        <label>Rol<select name="rol"><option value="redactor" <?= $formulario['rol'] === 'redactor' ? 'selected' : '' ?>>Redactor</option><option value="editor" <?= $formulario['rol'] === 'editor' ? 'selected' : '' ?>>Editor</option><option value="admin" <?= $formulario['rol'] === 'admin' ? 'selected' : '' ?>>Administrador</option></select></label>
        <label>Contraseña <?= $id ? '(opcional)' : '*' ?><input type="password" name="password" minlength="8" <?= $id ? '' : 'required' ?>><small><?= $id ? 'Déjala vacía para conservar la actual.' : 'Mínimo 8 caracteres.' ?></small></label>
      </div>
      <button class="usuarios-button" type="submit"><?= $id ? 'Guardar cambios' : 'Crear usuario' ?></button>
    </form>
  </div></div></div>
  <script src="../assets/js/menu.js"></script>
</body>
</html>