<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/autorizacion.php';
if (empty($_SESSION['admin_logueado'])) {
    header('Location: login.php');
    exit();
}

require_once __DIR__ . '/conexion.php';
$conexion = new conexion();
$usuarioActual = $conexion->obtenerUsuario((int) ($_SESSION['usuario_id'] ?? 0)) ?? [
    'nombres' => '',
    'ap_paterno' => '',
    'email' => $_SESSION['usuario'] ?? '',
    'rol' => $_SESSION['rol'] ?? '',
];
$datosDashboard = [
    'boletines' => $conexion->obtenerBoletines(),
    'noticias' => $conexion->obtenerNoticias(),
    'reportajes' => $conexion->obtenerReportajes(),
    'podcasts' => $conexion->obtenerPodcasts(),
    'videos' => $conexion->obtenerVideos(),
    'usuarios' => $conexion->obtenerUsuarios(),
];
$conexion->close();

function escaparDashboard(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaDashboard(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    if (!$fecha) {
        return $valor;
    }
    $meses = [1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr', 5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic'];
    return $meses[(int) $fecha->format('n')] . ' ' . $fecha->format('j, Y');
}

$nombreUsuario = trim(
    (string) ($usuarioActual['nombres'] ?? '') . ' ' .
    (string) ($usuarioActual['ap_paterno'] ?? '')
);
$nombreUsuario = $nombreUsuario !== '' ? $nombreUsuario : (string) ($usuarioActual['email'] ?? 'Usuario');
$rolUsuario = ucfirst((string) ($usuarioActual['rol'] ?? ''));
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
              <li class="dropdown dropdown-user nav-item"><a class="dropdown-toggle nav-link dropdown-user-link" href="#" data-toggle="dropdown">             
                <span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar"><i></i></span></a>
                <div class="dropdown-menu dropdown-menu-right">
                  <div class="arrow_box_right"><a class="dropdown-item" href="#"><span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="<?= escaparDashboard($nombreUsuario) ?>">
                  <span class="user-name text-bold-700 ml-1"><?= escaparDashboard($nombreUsuario) ?></span></span></a>
                    <?php if ($rolUsuario !== ''): ?><small class="dropdown-item text-muted"><?= escaparDashboard($rolUsuario) ?></small><?php endif; ?>
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
          <?php if (!esEditor() && !esRedactor()): ?><li class=" nav-item"><a href="usuarios.php"><i class="ft-user"></i><span class="menu-title" data-i18n="">usuarios</span></a>
          </li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="navigation-background"></div>
    </div>

    <div class="app-content content">
      <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body"><!-- Chart -->
<div class="row match-height">
    <div class="col-12">
        <div class="">
        </div>
    </div>
</div>
<!-- Chart -->
<!-- Statistics -->
<div class="row match-height legacy-statistics">
    <!-- ESTE ES LA PRIMERA CARD EN EL PANEL DE CONTROL -->
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <h4 class="card-title">BOLETINES RECIENTES</h4>
                    <h6 class="card-subtitle text-muted">SECCION DE BOLETINES</h6>
                </div>
                <div id="carousel-area" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">
                        <li data-target="#carousel-area" data-slide-to="0" class="active"></li>
                        <li data-target="#carousel-area" data-slide-to="1"></li>
                        <li data-target="#carousel-area" data-slide-to="2"></li>
                    </ol>
                    <div class="carousel-inner" role="listbox">
                        <div class="carousel-item active">
                            <img src="theme-assets/images/carousel/08.jpg" class="d-block w-100" alt="First slide">
                        </div>
                        <div class="carousel-item">
                            <img src="theme-assets/images/carousel/03.jpg" class="d-block w-100" alt="Second slide">
                        </div>
                        <div class="carousel-item">
                            <img src="theme-assets/images/carousel/01.jpg" class="d-block w-100" alt="Third slide">
                        </div>
                    </div>
                    <a class="carousel-control-prev" href="#carousel-area" role="button" data-slide="prev">
                            <span class="la la-angle-left" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                    <a class="carousel-control-next" href="#carousel-area" role="button" data-slide="next">
                            <span class="la la-angle-right icon-next" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                </div>
            </div>
            <div class="card-footer border-top-blue-grey border-top-lighten-5 text-muted">
                <span class="tags float-right">
                    <span class="badge badge-pill badge-primary">Editar</span>
                    <span class="badge badge-pill badge-danger">eliminar</span>
                </span>
            </div>
        </div>
    </div>
    <!-- ESTE ES LA SEGUNDA CARD EN EL PANEL DE CONTROL -->
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-content">
                <div class="card-body">
                    <h4 class="card-title">NOTICIAS RECIENTES</h4>
                    <h6 class="card-subtitle text-muted">SECCION DE NOTICAS AQUI</h6>
                </div>
                <div id="carousel-area" class="carousel slide" data-ride="carousel">
                    <ol class="carousel-indicators">
                        <li data-target="#carousel-area" data-slide-to="0" class="active"></li>
                        <li data-target="#carousel-area" data-slide-to="1"></li>
                        <li data-target="#carousel-area" data-slide-to="2"></li>
                    </ol>
                    <div class="carousel-inner" role="listbox">
                        <div class="carousel-item active">
                            <img src="theme-assets/images/carousel/08.jpg" class="d-block w-100" alt="First slide">
                        </div>
                        <div class="carousel-item">
                            <img src="theme-assets/images/carousel/03.jpg" class="d-block w-100" alt="Second slide">
                        </div>
                        <div class="carousel-item">
                            <img src="theme-assets/images/carousel/01.jpg" class="d-block w-100" alt="Third slide">
                        </div>
                    </div>
                    <a class="carousel-control-prev" href="#carousel-area" role="button" data-slide="prev">
                            <span class="la la-angle-left" aria-hidden="true"></span>
                            <span class="sr-only">Previous</span>
                        </a>
                    <a class="carousel-control-next" href="#carousel-area" role="button" data-slide="next">
                            <span class="la la-angle-right icon-next" aria-hidden="true"></span>
                            <span class="sr-only">Next</span>
                        </a>
                </div>
            </div>
            <div class="card-footer border-top-blue-grey border-top-lighten-5 text-muted">
                    <a class="dropdown-item" href="#"><i class="ft-edit"></i> editar</a>
                    <a class="dropdown-item" href="#"><i class="ft-delete"></i> eliminar</a>
            </div>
        </div>
    </div>
    <div class="col-xl-4 col-lg-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">REPORTAJES</h4>
                <a class="heading-elements-toggle">
                    <i class="fa fa-ellipsis-v font-medium-3"></i>
                </a>
                <div class="heading-elements">
                    <ul class="list-inline mb-0">
                        <li>
                            <a data-action="reload">
                                <i class="ft-rotate-cw"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-content">
                <div id="recent-buyers" class="media-list">
                    <a href="#" class="media border-0">
                        <div class="media-left pr-1">
                            <span class="avatar avatar-md avatar-online">
                                <img class="media-object rounded-circle" src="theme-assets/images/portrait/small/avatar-s-7.png" alt="Generic placeholder image">
                                <i></i>
                            </span>
                        </div>
                        <div class="media-body w-100">
                            <span class="list-group-item-heading">reportaje 1

                            </span>
                            <ul class="list-unstyled users-list m-0 float-right">
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 1" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-1.jpg"
                                        alt="Avatar">
                                </li>
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 2" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-2.jpg"
                                        alt="Avatar">
                                </li>
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 3" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-4.jpg"
                                        alt="Avatar">
                                </li>
                            </ul>
                            <p class="list-group-item-text mb-0">
                                <span class="blue-grey lighten-2 font-small-3"> reportaje 2 </span>
                            </p>
                        </div>
                    </a>
                    <a href="#" class="media border-0">
                        <div class="media-left pr-1">
                            <span class="avatar avatar-md avatar-away">
                                <img class="media-object rounded-circle" src="theme-assets/images/portrait/small/avatar-s-8.png" alt="Generic placeholder image">
                                <i></i>
                            </span>
                        </div>
                        <div class="media-body w-100">
                            <span class="list-group-item-heading">reportaje 3

                            </span>
                            <ul class="list-unstyled users-list m-0 float-right">
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 1" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-5.jpg"
                                        alt="Avatar">
                                </li>
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 2" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-6.jpg"
                                        alt="Avatar">
                                </li>
                            </ul>
                            <p class="list-group-item-text mb-0">
                                <span class="blue-grey lighten-2 font-small-3"> </span>
                            </p>
                        </div>
                    </a>
                    <a href="#" class="media border-0">
                        <div class="media-left pr-1">
                            <span class="avatar avatar-md avatar-busy">
                                <img class="media-object rounded-circle" src="theme-assets/images/portrait/small/avatar-s-9.png" alt="Generic placeholder image">
                                <i></i>
                            </span>
                        </div>
                        <div class="media-body w-100">
                            <span class="list-group-item-heading">reportaje 4 

                            </span>
                            <ul class="list-unstyled users-list m-0 float-right">
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 1" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-2.jpg"
                                        alt="Avatar">
                                </li>
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 2" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-5.jpg"
                                        alt="Avatar">
                                </li>
                            </ul>
                            <p class="list-group-item-text mb-0">
                                <span class="blue-grey lighten-2 font-small-3"> reportaje 5 </span>
                            </p>
                        </div>
                    </a>
                    <a href="#" class="media border-0">
                        <div class="media-left pr-1">
                            <span class="avatar avatar-md avatar-online">
                                <img class="media-object rounded-circle" src="theme-assets/images/portrait/small/avatar-s-10.png" alt="Generic placeholder image">
                                <i></i>
                            </span>
                        </div>
                        <div class="media-body w-100">
                            <span class="list-group-item-heading">reportaje 6

                            </span>
                            <ul class="list-unstyled users-list m-0 float-right">
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 1" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-6.jpg"
                                        alt="Avatar">
                                </li>
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 2" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-1.jpg"
                                        alt="Avatar">
                                </li>
                            </ul>
                            <p class="list-group-item-text mb-0">
                                <span class="blue-grey lighten-2 font-small-3"> #INV-12335 </span>
                            </p>
                        </div>
                    </a>
                    <a href="#" class="media border-0">
                        <div class="media-left pr-1">
                            <span class="avatar avatar-md avatar-online">
                                <img class="media-object rounded-circle" src="theme-assets/images/portrait/small/avatar-s-11.png" alt="Generic placeholder image">
                                <i></i>
                            </span>
                        </div>
                        <div class="media-body w-100">
                            <span class="list-group-item-heading">reportaje 7

                            </span>
                            <ul class="list-unstyled users-list m-0 float-right">
                                <li data-toggle="tooltip" data-popup="tooltip-custom" data-original-title="Product 1" class="avatar avatar-sm pull-up">
                                    <img class="media-object rounded-circle no-border-top-radius no-border-bottom-radius" src="theme-assets/images/portfolio/portfolio-5.jpg"
                                        alt="Avatar">
                                </li>
                            </ul>
                            <p class="list-group-item-text mb-0">
                                <span class="blue-grey lighten-2 font-small-3"> #INV-12336 </span>
                            </p>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!--/ Statistics -->
<div class="dashboard-intro">
    <h2>Resumen del panel</h2>
    <p>Administra y revisa el contenido publicado desde un solo lugar.</p>
</div>
<div class="row match-height">
<?php
$tarjetasDashboard = [
    ['clave' => 'boletines', 'titulo' => 'BOLETINES', 'subtitulo' => 'Publicaciones recientes', 'enlace' => 'boletines.php', 'icono' => 'la la-leanpub', 'campo' => 'numero_boletin', 'texto' => 'resumen', 'editor' => 'boletines'],
    ['clave' => 'noticias', 'titulo' => 'NOTICIAS', 'subtitulo' => 'Contenido editorial', 'enlace' => 'Noticias.php', 'icono' => 'la la-newspaper-o', 'campo' => 'titulo', 'texto' => 'titulo', 'editor' => 'noticias'],
    ['clave' => 'podcasts', 'titulo' => 'PODCAST', 'subtitulo' => 'Episodios recientes', 'enlace' => 'Podcast.php', 'icono' => 'ft-music', 'campo' => 'titulo', 'texto' => 'titulo', 'editor' => 'podcats'],
    ['clave' => 'videos', 'titulo' => 'VIDEOS', 'subtitulo' => 'Material audiovisual', 'enlace' => 'video.php', 'icono' => 'ft-play', 'campo' => 'titulo', 'texto' => 'titulo', 'editor' => 'video'],
];
foreach ($tarjetasDashboard as $tarjeta):
    $items = $datosDashboard[$tarjeta['clave']];
    $ultimo = $items[0] ?? null;
    $imagenTarjeta = $ultimo['foto_portada'] ?? ($ultimo['foto'] ?? '');
    if ($imagenTarjeta === '') {
        $imagenTarjeta = $ultimo['portada'] ?? '';
    }
    if ($tarjeta['clave'] === 'podcasts') {
        $imagenTarjeta = '../assets/images/podcast.png';
    }
?>
    <div class="col-xl-3 col-md-6 mb-2">
      <div class="card dashboard-card">
        <div class="card-header">
          <div><h4 class="card-title"><?= $tarjeta['titulo'] ?></h4><div class="card-subtitle"><?= $tarjeta['subtitulo'] ?></div></div>
          <span class="badge badge-pill badge-primary"><?= count($items) ?> total</span>
        </div>
        <div class="card-body dashboard-summary-card">
          <?php if ($imagenTarjeta): ?>
            <img class="dashboard-summary-cover" src="<?= escaparDashboard($imagenTarjeta) ?>" alt="<?= escaparDashboard($ultimo[$tarjeta['campo']] ?? $tarjeta['titulo']) ?>">
          <?php else: ?>
            <div class="dashboard-summary-icon"><i class="<?= $tarjeta['icono'] ?>"></i></div>
          <?php endif; ?>
          <?php if ($ultimo): ?>
            <h5 class="dashboard-item-title"><?= escaparDashboard($tarjeta['clave'] === 'boletines' ? 'Boletín ' . $ultimo[$tarjeta['campo']] : $ultimo[$tarjeta['campo']]) ?></h5>
            <p class="dashboard-item-text"><?= escaparDashboard($tarjeta['clave'] === 'noticias' ? $ultimo['titulo'] : ($tarjeta['clave'] === 'boletines' ? ($ultimo['resumen'] ?: 'Documento disponible') : 'Contenido disponible')) ?></p>
            <small class="text-muted"><i class="ft-calendar"></i> <?= escaparDashboard(fechaDashboard($ultimo['fecha_publicacion'])) ?></small>
          <?php else: ?>
            <h5 class="dashboard-item-title">Sin contenido todavía</h5>
            <p class="dashboard-item-text">Agrega el primer elemento desde esta sección.</p>
          <?php endif; ?>
        </div>
        <div class="card-footer d-flex justify-content-between align-items-center">
          <small class="text-muted"><?= count($items) ?> registrados</small>
          <a href="<?= $tarjeta['enlace'] ?>" class="dashboard-link">Administrar <i class="ft-arrow-right"></i></a>
        </div>
      </div>
    </div>
<?php endforeach; ?>
</div>
<div class="row match-height">
    <div class="col-xl-8 col-md-12 mb-2">
      <div class="card dashboard-card">
        <div class="card-header">
          <div><h4 class="card-title">REPORTAJES</h4><div class="card-subtitle">Últimos trabajos publicados</div></div>
          <span class="badge badge-pill badge-success"><?= count($datosDashboard['reportajes']) ?> total</span>
        </div>
        <div class="card-body">
        <?php if ($datosDashboard['reportajes']): foreach (array_slice($datosDashboard['reportajes'], 0, 4) as $reportaje):
            $fotos = !empty($reportaje['fotos']) ? explode('||', $reportaje['fotos']) : [];
            $portada = $reportaje['foto_principal'] ?: ($fotos[0] ?? '');
        ?>
          <div class="dashboard-list-item">
            <?php if ($portada): ?><img src="<?= escaparDashboard($portada) ?>" alt="<?= escaparDashboard($reportaje['titulo']) ?>"><?php else: ?><div class="dashboard-list-placeholder"><i class="ft-book"></i></div><?php endif; ?>
            <div class="dashboard-list-item-content"><strong><?= escaparDashboard($reportaje['titulo']) ?></strong><small><i class="ft-calendar"></i> <?= escaparDashboard(fechaDashboard($reportaje['fecha_publicacion'])) ?></small></div>
            <div class="dashboard-gallery" aria-label="<?= count($fotos) ?> imágenes del reportaje">
            <?php foreach (array_slice($fotos, 0, 3) as $foto): ?><img class="gallery-thumb" src="<?= escaparDashboard($foto) ?>" alt="Imagen del reportaje"><?php endforeach; ?>
            <?php if (count($fotos) > 3): ?><span class="gallery-more">+<?= count($fotos) - 3 ?></span><?php endif; ?>
            </div>
          </div>
        <?php endforeach; else: ?>
          <p class="dashboard-empty-text">Aún no hay reportajes registrados.</p>
        <?php endif; ?>
        </div>
        <div class="card-footer text-right"><a href="Reportaje.php" class="dashboard-link">Ver reportajes <i class="ft-arrow-right"></i></a></div>
      </div>
    </div>
    <?php if (!esEditor() && !esRedactor()): ?>
    <div class="col-xl-4 col-md-12 mb-2">
      <div class="card dashboard-card">
        <div class="card-header"><div><h4 class="card-title">USUARIOS</h4><div class="card-subtitle">Accesos del panel</div></div><span class="badge badge-pill badge-secondary"><?= count($datosDashboard['usuarios']) ?> total</span></div>
        <div class="card-body">
        <?php if ($datosDashboard['usuarios']): foreach (array_slice($datosDashboard['usuarios'], 0, 4) as $usuario): ?>
          <div class="dashboard-list-item"><div class="dashboard-user-icon"><i class="ft-user"></i></div><div class="dashboard-list-item-content"><strong><?= escaparDashboard(trim($usuario['nombres'] . ' ' . $usuario['ap_paterno'])) ?></strong><small><?= escaparDashboard(ucfirst($usuario['rol'])) ?></small></div></div>
        <?php endforeach; else: ?><p class="dashboard-empty-text">Aún no hay usuarios registrados.</p><?php endif; ?>
        </div>
        <?php if (!esEditor() && !esRedactor()): ?><div class="card-footer text-right"><a href="usuarios.php" class="dashboard-link">Ver usuarios <i class="ft-arrow-right"></i></a></div><?php endif; ?>
      </div>
    </div>
    <?php endif; ?>
</div>
        </div>
      </div>
    </div>
    <!-- ////////////////////////////////////////////////////////////////////////////-->


    <footer class="footer footer-static footer-light navbar-border navbar-shadow">
      <div class="clearfix blue-grey lighten-2 text-sm-center mb-0 px-2"><span class="float-md-left d-block d-md-inline-block">2018  &copy; Copyright <a class="text-bold-800 grey darken-2" href="https://themeselection.com" target="_blank">ThemeSelection</a></span>
        <ul class="list-inline float-md-right d-block d-md-inline-blockd-none d-lg-block mb-0">
          <li class="list-inline-item"><a class="my-1" href="https://themeselection.com/" target="_blank"> More themes</a></li>
          <li class="list-inline-item"><a class="my-1" href="https://themeselection.com/support" target="_blank"> Support</a></li>
          <li class="list-inline-item"><a class="my-1" href="https://themeselection.com/products/chameleon-admin-modern-bootstrap-webapp-dashboard-html-template-ui-kit/" target="_blank"> Purchase</a></li>
        </ul>
      </div>
    </footer>

    <!-- BEGIN VENDOR JS-->
    <script src="theme-assets/vendors/js/vendors.min.js" type="text/javascript"></script>
    <!-- BEGIN VENDOR JS-->
    <!-- BEGIN PAGE VENDOR JS-->
    <script src="theme-assets/vendors/js/charts/chartist.min.js" type="text/javascript"></script>
    <!-- END PAGE VENDOR JS-->
    <!-- BEGIN CHAMELEON  JS-->
    <script src="theme-assets/js/core/app-menu-lite.js" type="text/javascript"></script>
    <script src="theme-assets/js/core/app-lite.js" type="text/javascript"></script>
    <!-- END CHAMELEON  JS-->
    <!-- BEGIN PAGE LEVEL JS-->
    <script src="theme-assets/js/scripts/pages/dashboard-lite.js" type="text/javascript"></script>
    <!-- END PAGE LEVEL JS-->
  </body>
</html>