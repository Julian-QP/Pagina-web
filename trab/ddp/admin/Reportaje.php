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

require_once __DIR__ . '/conexion.php';
$conexionDecoracion = new conexion();
$decoracion = decoracionPanelReportajes($conexionDecoracion->obtenerDecoracionReportajes());
$conexionDecoracion->close();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_decoracion_reportajes'])) {
    $decoracion = decoracionPanelReportajes((string) ($_POST['decoracion_imagen'] ?? ''));
    try {
        $conexionDecoracion = new conexion();
        $conexionDecoracion->guardarDecoracionReportajes(json_encode($decoracion, JSON_THROW_ON_ERROR));
        $conexionDecoracion->close();
        header('Location: Reportaje.php?decoracion_guardada=1');
        exit();
    } catch (Throwable $exception) {
        $error = 'No fue posible guardar la decoración global.';
    }
}
$mensajeDecoracion = isset($_GET['decoracion_guardada']) ? 'La decoración global de portadas se guardó correctamente.' : '';
$controlAzulDecoracion = $decoracion['azul'] + 160;
$clipDecoracion = "path('M 0,20 C 0,9 9,0 25,0 L {$decoracion['rojo']},0 C 700,0 715,280 715,150 C 715,220 {$controlAzulDecoracion},420 {$decoracion['azul']},450 L 20,450 C 9,450 0,441 0,430 Z')";
$decoracionRojoPorcentaje = round($decoracion['rojo'] / 7, 2);
$decoracionAzulPorcentaje = round($decoracion['azul'] / 7, 2);
$mensajeEliminacion = isset($_GET['eliminado']) ? 'El reportaje se eliminó correctamente.' : '';
function decoracionPanelReportajes(?string $valor): array
{
    $predeterminada = ['rojo' => 420, 'azul' => 560, 'color_rojo' => '#ff3333', 'color_azul' => '#0000cc'];
    $datos = json_decode((string) $valor, true);
    if (!is_array($datos)) return $predeterminada;
    return [
        'rojo' => max(150, min(650, (int) ($datos['rojo'] ?? 420))),
        'azul' => max(150, min(650, (int) ($datos['azul'] ?? 560))),
        'color_rojo' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($datos['color_rojo'] ?? '')) ? $datos['color_rojo'] : '#ff3333',
        'color_azul' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($datos['color_azul'] ?? '')) ? $datos['color_azul'] : '#0000cc',
    ];
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['eliminar_id'])) {
    try {
        $conexion = new conexion();
        $conexion->eliminarContenido('reportaje', (int) $_POST['eliminar_id'], esRedactor() ? usuarioActualId() : null);
        $conexion->close();
        header('Location: Reportaje.php?eliminado=1');
        exit();
    } catch (mysqli_sql_exception | RuntimeException | InvalidArgumentException $exception) {
        $mensajeEliminacion = 'No fue posible eliminar el reportaje.';
    }
}

$reportajes = [];
$error = '';
$exito = '';
$formulario = [
    'titulo' => '',
    'resumen_corto' => '',
    'desarrollo' => '',
    'foto_principal' => '',
    'estado' => esRedactor() ? 'borrador' : 'publicado',
    'fecha_publicacion' => date('Y-m-d'),
    'es_destacado' => 0,
    'autor_nombres' => '',
    'autor_ap_paterno' => '',
    'autor_ap_materno' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($formulario as $campo => $valor) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? $valor));
    }
    $formulario['estado'] = estadoContenidoPermitido($formulario['estado']);
    $formulario['es_destacado'] = isset($_POST['es_destacado']) ? 1 : 0;

    if ($formulario['titulo'] === '' || $formulario['desarrollo'] === '' ||
        $formulario['autor_nombres'] === '' || $formulario['autor_ap_paterno'] === '') {
        $error = 'Completa el título, desarrollo y los datos obligatorios del autor.';
    } else {
        try {
            $conexion = new conexion();
            $conexion->crearReportaje($formulario, (int) ($_SESSION['usuario_id'] ?? 0));
            $conexion->close();
            $exito = 'El reportaje se guardó correctamente.';
            $formulario['titulo'] = '';
            $formulario['resumen_corto'] = '';
            $formulario['desarrollo'] = '';
            $formulario['foto_principal'] = '';
            $formulario['autor_nombres'] = '';
            $formulario['autor_ap_paterno'] = '';
            $formulario['autor_ap_materno'] = '';
        } catch (RuntimeException $exception) {
            $error = 'No fue posible guardar el reportaje.';
        }
    }
}

try {
    $conexion = new conexion();
    $reportajes = $conexion->obtenerReportajes(esRedactor() ? usuarioActualId() : null);
    $conexion->close();
} catch (mysqli_sql_exception | RuntimeException $exception) {
    $error = 'No fue posible cargar los reportajes.';
}

function escapar(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaReportaje(string $valor): string
{
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha ? $fecha->format('d/m/Y') : $valor;
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
                  <div class="arrow_box_right"><a class="dropdown-item" href="index.php"><span class="avatar avatar-online"><img src="theme-assets/images/portrait/small/avatar-s-19.png" alt="avatar">
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
    <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true" data-img="theme-assets/images/backgrounds/02.jpg">
      <div class="navbar-header">
        <ul class="nav navbar-nav flex-row">
          <li class="nav-item mr-auto"><a class="navbar-brand" href="index.php"><img class="brand-logo" alt="Chameleon admin logo" src="theme-assets/images/logo/logo.png"/><h3 class="brand-text">DDP</h3></a></li>
          <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
        </ul>
      </div>
      <div class="main-menu-content">
        <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
          <li class="active"><a href="index.php"><i class="ft-home"></i><span class="menu-title" data-i18n="">Inicio</span></a></li>
          <li class=" nav-item"><a href="Reportaje.php"><i class="ft-book"></i><span class="menu-title" data-i18n="">Reportaje</span></a></li>
          <li class=" nav-item"><a href="Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title" data-i18n="">Noticias</span></a></li>
          <li class=" nav-item"><a href="Podcast.php"><i class="ft-music"></i><span class="menu-title" data-i18n="">Podcast</span></a></li>
          <li class=" nav-item"><a href="video.php"><i class="ft-play"></i><span class="menu-title" data-i18n="">videos</span></a></li>
          <li class=" nav-item"><a href="boletines.php"><i class="la la-leanpub"></i><span class="menu-title" data-i18n="">Boletines</span></a></li>
          <?php if (!esEditor() && !esRedactor()): ?><li class=" nav-item"><a href="usuarios.php"><i class="ft-user"></i><span class="menu-title" data-i18n="">usuarios</span></a></li><?php endif; ?>
        </ul>
      </div>
      <div class="navigation-background"></div>
    </div>
    <div class="app-content content"><div class="content-wrapper"><div class="content-body">
    <link rel="stylesheet" type="text/css" href="assets/css/repostaje.css">
    <section class="reportajes-content">
      <div class="reportajes-header"><div><span class="reportajes-eyebrow">CONTENIDO EDITORIAL</span><h1>Reportajes</h1><p>Administra los reportajes, autores y galerías de imágenes.</p></div><div class="reportajes-header-actions"><button type="button" class="reportajes-button reportajes-decoration-button" id="abrir-decoracion-global">Decoración de portadas</button><a class="reportajes-button" href="config/reportaje.php">+ Nuevo reportaje</a></div></div>
      <?php if ($mensajeDecoracion !== ''): ?><div class="reportajes-message reportajes-success"><?= escapar($mensajeDecoracion) ?></div><?php endif; ?>
      <form class="reportajes-decoration-panel" id="decoracion-global-panel" method="post" hidden>
        <input type="hidden" name="guardar_decoracion_reportajes" value="1">
        <input type="hidden" name="decoracion_imagen" id="decoracion-global-value">
        <div class="reportajes-decoration-heading"><strong>Decoración global de portadas</strong><span class="reportajes-decoration-actions"><button class="reportajes-button" type="submit">Guardar decoración</button><button type="button" class="reportajes-button reportajes-button-light" id="cerrar-decoracion-global">Cerrar</button></span></div>
        <div class="reportajes-decoration-controls">
          <label>Ancho superior <output id="decoracion-global-rojo-valor"></output><input type="range" id="decoracion-global-rojo" min="150" max="650"></label>
          <label>Ancho inferior <output id="decoracion-global-azul-valor"></output><input type="range" id="decoracion-global-azul" min="150" max="650"></label>
          <label>Color superior<input type="color" id="decoracion-global-color-rojo"></label>
          <label>Color inferior<input type="color" id="decoracion-global-color-azul"></label>
        </div>
        <div class="reportajes-decoration-preview" id="decoracion-global-preview" aria-label="Vista previa de la decoración"><div class="reportajes-decoration-preview-image"></div></div>
      </form>
      <div class="content-filter" data-content-filter=".reportajes-grid"><input type="search" data-filter-name placeholder="Buscar por título..." aria-label="Buscar reportaje"><select data-filter-status aria-label="Filtrar por estado"><option value="">Todos los estados</option><option value="publicado">Publicado</option><option value="borrador">Borrador</option></select></div>
      <?php if ($exito !== ''): ?><div class="reportajes-message reportajes-success"><?= escapar($exito) ?></div><?php endif; ?><?php if ($mensajeEliminacion !== ''): ?><div class="reportajes-message reportajes-success"><?= escapar($mensajeEliminacion) ?></div><?php endif; ?>
      <?php if ($error !== ''): ?><div class="reportajes-message reportajes-error"><?= escapar($error) ?></div><?php elseif ($reportajes === []): ?><div class="reportajes-message">No hay reportajes registrados en la base de datos.</div><?php else: ?>
      <div class="reportajes-grid">
        <?php foreach ($reportajes as $reportaje): ?>
          <?php $fotos = $reportaje['fotos'] ? explode('||', $reportaje['fotos']) : []; $fotosVisibles = array_slice($fotos, 0, 3); $totalFotos = (int) $reportaje['total_fotos']; $portada = $reportaje['foto_principal'] ?: ($fotosVisibles[0] ?? ''); ?>
          <article class="reportaje-card" data-filter-card data-name="<?= escapar($reportaje['titulo']) ?>" data-status="<?= escapar($reportaje['estado']) ?>">
            <?php if ($portada !== ''): ?><div class="reportaje-cover-decoration" style="--decoracion-rojo: <?= escapar($decoracion['color_rojo']) ?>; --decoracion-azul: <?= escapar($decoracion['color_azul']) ?>; --decoracion-rojo-x: <?= $decoracionRojoPorcentaje ?>%; --decoracion-azul-x: <?= $decoracionAzulPorcentaje ?>%;"><img class="reportaje-cover" src="<?= escapar($portada) ?>" alt="Portada de <?= escapar($reportaje['titulo']) ?>"></div><?php else: ?><div class="reportaje-cover reportaje-cover-empty">Sin imagen</div><?php endif; ?>
            <div class="reportaje-card-body"><div class="reportaje-card-top"><span class="reportaje-status <?= escapar($reportaje['estado']) ?>"><?= escapar(ucfirst($reportaje['estado'])) ?></span><?php if ((int) $reportaje['es_destacado'] === 1): ?><span class="reportaje-featured">★</span><?php endif; ?></div><h2><?= escapar($reportaje['titulo']) ?></h2><p class="reportaje-summary"><?= escapar($reportaje['resumen_corto'] ?: 'Sin resumen disponible.') ?></p><div class="reportaje-meta"><span><?= escapar($reportaje['autor'] ?: 'Autor no asignado') ?></span><span><?= escapar(fechaReportaje($reportaje['fecha_publicacion'])) ?></span></div><?php if ($fotosVisibles !== []): ?><div class="reportaje-gallery" aria-label="<?= $totalFotos ?> imágenes"><?php foreach ($fotosVisibles as $foto): ?><img src="<?= escapar($foto) ?>" alt="Imagen del reportaje"><?php endforeach; ?><?php if ($totalFotos > 3): ?><span>+<?= $totalFotos - 3 ?></span><?php endif; ?></div><?php endif; ?></div>
            <div class="reportaje-actions"><a href="config/reportaje.php?id=<?= (int) $reportaje['id'] ?>">Ver detalle</a><a href="config/reportaje.php?id=<?= (int) $reportaje['id'] ?>">Editar</a><form method="post" onsubmit="return confirm('¿Estás seguro de eliminar este reportaje completo?');"><input type="hidden" name="eliminar_id" value="<?= (int) $reportaje['id'] ?>"><button type="submit">Eliminar</button></form></div>
          </article>
        <?php endforeach; ?>
      </div><?php endif; ?>
    </section>
  </div></div></div>
  <script src="assets/js/filtros.js"></script>
  <script>
    const decoracionGlobal = <?= json_encode($decoracion, JSON_THROW_ON_ERROR) ?>;
    const panelDecoracionGlobal = document.getElementById('decoracion-global-panel');
    const actualizarDecoracionGlobal = () => {
      const rojo = Number(document.getElementById('decoracion-global-rojo').value);
      const azul = Number(document.getElementById('decoracion-global-azul').value);
      const ruta = `path('M 0,20 C 0,9 9,0 25,0 L ${rojo},0 C 700,0 715,280 715,150 C 715,220 ${azul + 160},420 ${azul},450 L 20,450 C 9,450 0,441 0,430 Z')`;
      document.getElementById('decoracion-global-rojo-valor').textContent = rojo + 'px';
      document.getElementById('decoracion-global-azul-valor').textContent = azul + 'px';
      const vista = document.getElementById('decoracion-global-preview');
      vista.style.setProperty('--decoracion-rojo', document.getElementById('decoracion-global-color-rojo').value);
      vista.style.setProperty('--decoracion-azul', document.getElementById('decoracion-global-color-azul').value);
      const vistaImagen = document.querySelector('.reportajes-decoration-preview-image');
      vistaImagen.style.clipPath = ruta;
      document.getElementById('decoracion-global-value').value = JSON.stringify({
        rojo, azul,
        color_rojo: document.getElementById('decoracion-global-color-rojo').value,
        color_azul: document.getElementById('decoracion-global-color-azul').value
      });
    };
    document.getElementById('decoracion-global-rojo').value = decoracionGlobal.rojo;
    document.getElementById('decoracion-global-azul').value = decoracionGlobal.azul;
    document.getElementById('decoracion-global-color-rojo').value = decoracionGlobal.color_rojo;
    document.getElementById('decoracion-global-color-azul').value = decoracionGlobal.color_azul;
    [document.getElementById('decoracion-global-rojo'), document.getElementById('decoracion-global-azul'), document.getElementById('decoracion-global-color-rojo'), document.getElementById('decoracion-global-color-azul')].forEach((control) => control.addEventListener('input', actualizarDecoracionGlobal));
    document.getElementById('abrir-decoracion-global').addEventListener('click', () => { panelDecoracionGlobal.hidden = false; actualizarDecoracionGlobal(); });
    document.getElementById('cerrar-decoracion-global').addEventListener('click', () => { panelDecoracionGlobal.hidden = true; });
    actualizarDecoracionGlobal();
  </script>
  </body></html>