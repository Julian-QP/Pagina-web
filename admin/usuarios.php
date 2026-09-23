<?php
declare(strict_types=1);

session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
require_once __DIR__ . '/autorizacion.php';
if (empty($_SESSION['admin_logueado'])) {
    header('Location: login.php');
    exit();
}
exigirGestionUsuarios();

require_once __DIR__ . '/conexion.php';
$conexion = new conexion();
$usuarios = $conexion->obtenerUsuarios();
$conexion->close();

function escaparUsuario(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaUsuario(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d H:i:s', $valor)
        ?: DateTime::createFromFormat('Y-m-d', $valor);
    if (!$fecha) {
        return $valor;
    }
    $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
    return $meses[(int) $fecha->format('n')] . ' ' . $fecha->format('j, Y');
}
?>
<!DOCTYPE html>
<html class="loading" lang="en" data-textdirection="ltr">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Chameleon Admin is a modern Bootstrap 4 webapp &amp; admin dashboard html template with a large number of components, elegant design, clean and organized code.">
    <meta name="keywords" content="admin template, Chameleon admin template, dashboard template, gradient admin template, responsive admin template, webapp, eCommerce dashboard, analytic dashboard">
    <meta name="author" content="ThemeSelect">
    <title>Panel de Administrador de PDD</title>
    <link rel="apple-touch-icon" href="theme-assets/images/ico/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="theme-assets/images/ico/logo.ico">
    <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
    <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
    <!-- BEGIN VENDOR CSS-->
    <link rel="stylesheet" type="text/css" href="theme-assets/css/vendors.css">
    <link rel="stylesheet" type="text/css" href="theme-assets/vendors/css/charts/chartist.css">
    <!-- END VENDOR CSS-->
    <!-- BEGIN CHAMELEON  CSS-->
    <link rel="stylesheet" type="text/css" href="theme-assets/css/app-lite.css">
    <!-- END CHAMELEON  CSS-->
    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" type="text/css" href="theme-assets/css/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="theme-assets/css/core/colors/palette-gradient.css">
    <link rel="stylesheet" type="text/css" href="theme-assets/css/pages/dashboard-ecommerce.css">
    <link rel="stylesheet" type="text/css" href="assets/css/estilos.css">
    <link rel="stylesheet" type="text/css" href="assets/css/usuario.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN Custom CSS-->
    <!-- END Custom CSS-->
    <link rel="stylesheet" type="text/css" href="assets/css/paleta-roja.css">
  </head>
  <body class="vertical-layout vertical-menu 2-columns   menu-expanded fixed-navbar" data-open="click" data-menu="vertical-menu" data-color="bg-chartbg" data-col="2-columns">

    <!-- fixed-top-->
    <nav class="header-navbar navbar-expand-md navbar navbar-with-menu navbar-without-dd-arrow fixed-top navbar-semi-light">
      <div class="navbar-wrapper">
        <div class="navbar-container content">
          <div class="collapse navbar-collapse show" id="navbar-mobile">
            <ul class="nav navbar-nav mr-auto float-left">
              </li>
            </ul>
            <ul class="nav navbar-nav float-right">
              <li class="dropdown dropdown-user nav-item"><a class="dropdown-toggle nav-link dropdown-user-link" href="index.php">             
                <span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar"><i></i></span></a>
                <div class="dropdown-menu dropdown-menu-right">
                  <div class="arrow_box_right"><a class="dropdown-item" href="#"><span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar">
                  <span class="user-name text-bold-700 ml-1">Juan</span></span></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="logout.php"><i class="ft-power"></i> Cerrar Sesion</a>
                  </div>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <script src="assets/js/menu.js"></script>
    <!-- ////////////////////////////////////////////////////////////////////////////-->


    <div class="main-menu menu-fixed menu-light menu-accordion    menu-shadow " data-scroll-to-active="true" data-img="theme-assets/images/backgrounds/02.jpg">
      <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">       
          <li class="nav-item mr-auto"><a class="navbar-brand" href="index.php"><img class="brand-logo" alt="Chameleon admin logo" src="theme-assets/images/logo/logo.png"/>
              <h3 class="brand-text">DDP</h3></a></li>
          <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
        </ul>
      </div>
      <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
          <li class="active"><a href="index.php"><i class="ft-home"></i><span class="menu-title" data-i18n="">Inicio</span></a>
          </li>
          <li class=" nav-item"><a href="Reportaje.php"><i class="ft-book"></i><span class="menu-title" data-i18n="">Reportaje</span></a>
          </li>
          <li class=" nav-item"><a href="Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title" data-i18n="">Noticias</span></a>
          </li>
          <li class=" nav-item"><a href="Podcast.php"><i class="ft-music"></i><span class="menu-title" data-i18n="">Podcast</span></a>
          </li>
          <li class=" nav-item"><a href="video.php"><i class="ft-play"></i><span class="menu-title" data-i18n="">videos</span></a>
          </li>
          <li class=" nav-item"><a href="boletines.php"><i class="la la-leanpub"></i><span class="menu-title" data-i18n="">Boletines</span></a>
          </li>
          <li class=" nav-item"><a href="usuarios.php"><i class="ft-user"></i><span class="menu-title" data-i18n="">usuarios</span></a>
          </li>
        </ul>
      </div>
      <div class="navigation-background"></div>
    </div>

    <div class="app-content content">
      <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body usuarios-content">
          <div class="usuarios-heading">
            <div>
              <span class="usuarios-eyebrow">ADMINISTRACIÓN</span>
              <h1>Usuarios</h1>
              <p>Gestiona las cuentas y permisos del panel.</p>
            </div>
            <a class="usuarios-button" href="config/usuario.php">+ Nuevo usuario</a>
          </div>
          <?php if ($usuarios === []): ?>
            <div class="usuarios-empty">
              <i class="ft-users"></i>
              <h2>Aún no hay usuarios</h2>
              <p>Crea el primer usuario para comenzar a administrar el panel.</p>
              <a class="usuarios-button" href="config/usuario.php">Crear usuario</a>
            </div>
          <?php else: ?>
            <div class="usuarios-table-wrap">
              <table class="usuarios-table">
                <thead><tr><th>Usuario</th><th>Correo</th><th>Rol</th><th>Registro</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($usuarios as $usuario): ?>
                  <tr>
                    <td><strong><?= escaparUsuario(trim($usuario['nombres'] . ' ' . $usuario['ap_paterno'])) ?></strong><small><?= escaparUsuario($usuario['ap_materno']) ?></small></td>
                    <td><?= escaparUsuario($usuario['email']) ?></td>
                    <td><span class="usuario-rol <?= escaparUsuario($usuario['rol']) ?>"><?= ucfirst(escaparUsuario($usuario['rol'])) ?></span></td>
                    <td><?= escaparUsuario(fechaUsuario($usuario['created_at'])) ?></td>
                    <td><a class="usuario-edit" href="config/usuario.php?id=<?= (int) $usuario['id'] ?>">Editar</a></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>