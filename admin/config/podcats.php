<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../autorizacion.php';
if (empty($_SESSION['admin_logueado'])) {
    header('Location: ../login.php');
    exit();
}

require_once __DIR__ . '/../conexion.php';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$conexion = new conexion();
$existente = $id ? $conexion->obtenerPodcast($id, esRedactor() ? usuarioActualId() : null) : null;

if ($id && $existente === null) {
    $conexion->close();
    http_response_code(404);
    exit('Podcast no encontrado.');
}

$formulario = [
    'titulo' => $existente['titulo'] ?? '',
    'url_embed' => $existente['url_embed'] ?? '',
    'estado' => $existente['estado'] ?? 'borrador',
    'fecha_publicacion' => $existente['fecha_publicacion'] ?? date('Y-m-d'),
];
$error = '';

function escaparPodcast(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function normalizarUrlPodcast(string $url): string
{
    $partes = parse_url($url);
    $ruta = (string) ($partes['path'] ?? '');
    if (($partes['host'] ?? '') !== '' && str_contains((string) $partes['host'], 'spotify.com')
        && preg_match('~/(track|episode|show|playlist|album|artist)/([^/?#]+)~i', $ruta, $coincidencia) === 1) {
        return 'https://open.spotify.com/embed/' . strtolower($coincidencia[1]) . '/' . $coincidencia[2];
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['titulo', 'url_embed', 'estado', 'fecha_publicacion'] as $campo) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $formulario['estado'] = estadoContenidoPermitido($formulario['estado']);
    $formulario['url_embed'] = normalizarUrlPodcast($formulario['url_embed']);

    if ($formulario['titulo'] === '' || $formulario['url_embed'] === '' ||
        !filter_var($formulario['url_embed'], FILTER_VALIDATE_URL) ||
        !in_array($formulario['estado'], ['borrador', 'publicado'], true) ||
        $formulario['fecha_publicacion'] === '') {
        $error = 'Completa los campos correctamente e indica una URL válida.';
    } else {
        try {
            $formulario['portada'] = $existente['portada'] ?? '';
            $guardadoId = $conexion->guardarPodcast(
                $formulario,
                (int) ($_SESSION['usuario_id'] ?? 0),
                $id
            );
            $conexion->close();
            header('Location: podcats.php?id=' . $guardadoId . '&guardado=1');
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
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0, minimal-ui">
    <meta name="description" content="Chameleon Admin is a modern Bootstrap 4 webapp &amp; admin dashboard html template with a large number of components, elegant design, clean and organized code.">
    <meta name="keywords" content="admin template, Chameleon admin template, dashboard template, gradient admin template, responsive admin template, webapp, eCommerce dashboard, analytic dashboard">
    <meta name="author" content="ThemeSelect">
    <title>Panel de Administrador de PDD</title>
    <link rel="apple-touch-icon" href="../theme-assets/images/ico/logo.png">
    <link rel="shortcut icon" type="image/x-icon" href="../theme-assets/images/ico/logo.ico">
    <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
    <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
    <!-- BEGIN VENDOR CSS-->
    <link rel="stylesheet" type="text/css" href="../theme-assets/css/vendors.css">
    <link rel="stylesheet" type="text/css" href="../theme-assets/vendors/css/charts/chartist.css">
    <!-- END VENDOR CSS-->
    <!-- BEGIN CHAMELEON  CSS-->
    <link rel="stylesheet" type="text/css" href="../theme-assets/css/app-lite.css">
    <!-- END CHAMELEON  CSS-->
    <!-- BEGIN Page Level CSS-->
    <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/menu/menu-types/vertical-menu.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/responsive-admin.css">
    <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/colors/palette-gradient.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/podcast.css">
    <link rel="stylesheet" type="text/css" href="../theme-assets/css/pages/dashboard-ecommerce.css">
    <link rel="stylesheet" type="text/css" href="../assets/css/estilos.css">
    <!-- END Page Level CSS-->
    <!-- BEGIN Custom CSS-->
    <!-- END Custom CSS-->
    <link rel="stylesheet" type="text/css" href="../assets/css/paleta-roja.css">
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
              <li class="dropdown dropdown-user nav-item"><a class="dropdown-toggle nav-link dropdown-user-link" href="../index.php">             
                <span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar"><i></i></span></a>
                <div class="dropdown-menu dropdown-menu-right">
                  <div class="arrow_box_right"><a class="dropdown-item" href="#"><span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar">
                  <span class="user-name text-bold-700 ml-1">Juan</span></span></a>
                    <div class="dropdown-divider"></div>
                    <a class="dropdown-item" href="../logout.php"><i class="ft-power"></i> Cerrar Sesion</a>
                  </div>
                  <script src="../assets/js/menu.js"></script>
                </div>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </nav>

    <!-- ////////////////////////////////////////////////////////////////////////////-->


    <div class="main-menu menu-fixed menu-light menu-accordion    menu-shadow " data-scroll-to-active="true" data-img="theme-assets/images/backgrounds/02.jpg">
      <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">       
          <li class="nav-item mr-auto"><a class="navbar-brand" href="index.php"><img class="brand-logo" alt="" src="../theme-assets/images/logo/logo.png"/>
              <h3 class="brand-text">DDP</h3></a></li>
          <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
        </ul>
      </div>
      <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
          <li class="active"><a href="../index.php"><i class="ft-home"></i><span class="menu-title" data-i18n="">Inicio</span></a>
          </li>
          <li class=" nav-item"><a href="../Reportaje.php"><i class="ft-book"></i><span class="menu-title" data-i18n="">Reportaje</span></a>
          </li>
          <li class=" nav-item"><a href="../Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title" data-i18n="">Noticias</span></a>
          </li>
          <li class=" nav-item"><a href="../Podcast.php"><i class="ft-music"></i><span class="menu-title" data-i18n="">Podcast</span></a>
          </li>
          <li class=" nav-item"><a href="../video.php"><i class="ft-play"></i><span class="menu-title" data-i18n="">videos</span></a>
          </li>
          <li class=" nav-item"><a href="../boletines.php"><i class="la la-leanpub"></i><span class="menu-title" data-i18n="">Boletines</span></a>
          </li>
          <?php if (!esEditor() && !esRedactor()): ?><li class=" nav-item"><a href="../usuarios.php"><i class="ft-user"></i><span class="menu-title" data-i18n="">usuarios</span></a>
          </li>
          <?php endif; ?>
          <li class=" nav-item"><a href="../logout.php"><i class="ft-power"></i><span class="menu-title" data-i18n="">Cerrar Sesion</span></a>
          </li>
        </ul>
      </div>
      <div class="navigation-background"></div>
    </div>

    <div class="app-content content">
      <div class="content-wrapper">
        <div class="content-header row">
        </div>
        <div class="content-body podcast-content podcast-editor-content">
          <div class="podcast-heading">
            <div>
              <span class="podcast-eyebrow">CONTENIDO MULTIMEDIA</span>
              <h1><?= $id ? 'Editar podcast' : 'Nuevo podcast' ?></h1>
              <p>Agrega el enlace de inserción del episodio y define su publicación.</p>
            </div>
            <a class="podcast-button podcast-button-secondary" href="../Podcast.php">Volver a podcasts</a>
          </div>
          <?php if ($guardado): ?><div class="podcast-alert success">El podcast se guardó correctamente.</div><?php endif; ?>
          <?php if ($error !== ''): ?><div class="podcast-alert error"><?= escaparPodcast($error) ?></div><?php endif; ?>
          <form class="podcast-form" method="post">
            <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
            <label>Título del episodio *
              <input type="text" name="titulo" maxlength="255" value="<?= escaparPodcast($formulario['titulo']) ?>" required>
            </label>
            <label>URL de inserción *
              <input type="url" name="url_embed" maxlength="500" value="<?= escaparPodcast($formulario['url_embed']) ?>" placeholder="https://open.spotify.com/embed/..." required>
              <small>Usa la URL de inserción proporcionada por Spotify, YouTube u otra plataforma compatible.</small>
            </label>
            <div class="podcast-form-grid">
              <label>Estado
                <select name="estado">
                  <option value="borrador" <?= $formulario['estado'] === 'borrador' || esRedactor() ? 'selected' : '' ?>>Borrador</option>
                  <?php if (!esRedactor()): ?><option value="publicado" <?= $formulario['estado'] === 'publicado' ? 'selected' : '' ?>>Publicado</option><?php endif; ?>
                </select>
              </label>
              <label>Fecha de publicación *
                <input type="date" name="fecha_publicacion" value="<?= escaparPodcast($formulario['fecha_publicacion']) ?>" required>
              </label>
            </div>
            <button class="podcast-button" type="submit"><?= $id ? 'Guardar cambios' : 'Crear podcast' ?></button>
          </form>
        </div>