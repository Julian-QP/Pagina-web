<!--
Author: W3layouts
Author URL: http://w3layouts.com
-->
<?php
require_once __DIR__ . '/admin/conexion.php';

$conexionPublica = new conexion();
$reportajesPublicados = array_values(array_filter(
    $conexionPublica->obtenerReportajes(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$decoracionReportajes = $conexionPublica->obtenerDecoracionReportajes();
$conexionPublica->close();
$porPagina = 9;
$totalPaginas = max(1, (int) ceil(count($reportajesPublicados) / $porPagina));
$paginaActual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$paginaActual = max(1, min($paginaActual, $totalPaginas));
$reportajesPagina = array_slice($reportajesPublicados, ($paginaActual - 1) * $porPagina, $porPagina);

function escaparReportajePublico(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaReportajePublico(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha ? $fecha->format('d/m/Y') : $valor;
}

function imagenReportajePublico(?string $ruta): string
{
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return 'assets/images/reportaje-18-08-26.jpg';
    }
    if (str_starts_with($ruta, 'config/')) {
        return 'admin/' . $ruta;
    }
    if (str_starts_with($ruta, 'image/')) {
        return 'admin/config/' . $ruta;
    }
    return $ruta;
}

function decoracionPortadaReportaje(?string $valor): array
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

$decoracionPortada = decoracionPortadaReportaje($decoracionReportajes);
$clipDecoracionPublica = "path('M 0,20 C 0,9 9,0 25,0 L {$decoracionPortada['rojo']},0 C 700,0 715,280 715,150 C 715,220 " . ($decoracionPortada['azul'] + 160) . ",420 {$decoracionPortada['azul']},450 L 20,450 C 9,450 0,441 0,430 Z')";
$rutaDecoracionSvg = "M 0 20 C 0 9 9 0 25 0 L {$decoracionPortada['rojo']} 0 C 700 0 715 280 715 150 C 715 220 " . ($decoracionPortada['azul'] + 160) . " 420 {$decoracionPortada['azul']} 450 L 20 450 C 9 450 0 441 0 430 Z";
$decoracionRojoPorcentaje = round($decoracionPortada['rojo'] / 7, 2);
$decoracionAzulPorcentaje = round($decoracionPortada['azul'] / 7, 2);
?>
<!doctype html>
<html lang="en">
  <head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>DDP Noticias - Diálogo y Desarrollo Perú</title>

    <!-- Google fonts -->
    
	<link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    
    <!-- Template CSS -->
	
    <link rel="stylesheet" href="assets/css/style-starter.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
      .reportaje-list-title { overflow-wrap: anywhere; word-break: break-word; }
      .reportaje-public-cover { background: linear-gradient(to bottom, var(--decoracion-rojo) 0 50%, var(--decoracion-azul) 50% 100%); display: block; height: 220px; overflow: hidden; }
      .reportaje-public-cover svg { display: block; height: 250px; width: 100%; }
      .grids-block-5 .row { align-items: stretch; }
      .grids-block-5 .grids5-info { display: flex; flex-direction: column; height: auto; }
      .grids-block-5 .grids5-info > a:first-child { display: block; flex: 0 0 250px; height: 250px; overflow: hidden; }
      .grids-block-5 .grids5-info > a:first-child > img { display: block; height: 250px; object-fit: cover; width: 100%; }
      .grids-block-5 .grids5-info .blog-info { display: flex; flex: 1; flex-direction: column; }
      .grids-block-5 .grids5-info .blog-info .btn { margin-top: auto !important; }
      .grids-block-5 .grids5-info > a:first-child { border-radius: 14px 14px 0 0; }
      .grids-block-5 .grids5-info .blog-info { border-radius: 0 0 14px 14px; }
      .destacado-badge { color: #c62828; display: block; font-size: .75rem; font-weight: 700; margin: .4rem 0; }
    </style>
  </head>
  <body>

<!-- header -->
<header id="site-header" class="fixed-top">
  <div class="container">
      <nav class="navbar navbar-expand-lg stroke">
          <!--<a class="navbar-brand" href="index.html">
              <span class="fa fa-video-camera"></span> V-Conference
          </a>
           if logo is image enable this   -->
      <a class="navbar-brand" href="#index.html">
          <img src="assets/images/logo.png" alt="Your logo" title="Your logo" style="height:75px;" />
      </a> 
          <button class="navbar-toggler  collapsed bg-gradient" type="button" data-toggle="collapse"
              data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false"
              aria-label="Toggle navigation">
              <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
              <span class="navbar-toggler-icon fa icon-close fa-times"></span>
              </span>
          </button>

          <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
              <ul class="navbar-nav ml-auto">
                  <li class="nav-item">
                      <a class="nav-link" href="index.php">Inicio <span class="sr-only">(current)</span></a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="index.php#actualidad">Actualidad</a>
                  </li>
				  <li class="nav-item active">
                      <a class="nav-link" href="reportajes-1.php">Reportajes</a>
                  </li>
				  <li class="nav-item @@about__active">
                      <a class="nav-link" href="podcast.php">Podcast</a>
                  </li>
				  <li class="nav-item @@about__active">
                      <a class="nav-link" href="boletines.php">Boletín NTEP</a>
                  </li>
				  <li class="nav-item @@about__active">
                      <a class="nav-link" href="about.html">Alianzas</a>
                  </li>
                  <li class="nav-item @@contact__active">
                      <a class="nav-link" href="contact.html">Sobre D&D</a>
                  </li>				  
                  <li class="ml-2">
                      <a href="#btn" class="btn btn-style btn-outline-secondary">Contacto</a>
                  </li>
              </ul>
          </div>
          <!-- toggle switch for light and dark theme --
          <div class="mobile-position">
              <nav class="navigation">
                  <div class="theme-switch-wrapper">
                      <label class="theme-switch" for="checkbox">
                          <input type="checkbox" id="checkbox">
                          <div class="mode-container">
                              <i class="gg-sun"></i>
                              <i class="gg-moon"></i>
                          </div>
                      </label>
                  </div>
              </nav>
          </div>
          <!-- //toggle switch for light and dark theme -->
      </nav>
  </div>
</header>
<!-- //header -->
<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Reportajes</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li>
                                <a href="index.html">Inicio</a>
                            </li>
                            <li class="active">
                                 Reportajes
                            </li>
                        </ul>
                    </div>
                </div>
            </div><!-- end .col-md-12 -->
        </div><!-- end .row -->
    </div><!-- end .container -->
</section>
<div class="grids-block-5 py-5">
    <!-- grids block 5 -->
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php foreach ($reportajesPagina as $reportaje): ?>
                <?php
                $fotos = $reportaje['fotos'] ? explode('||', $reportaje['fotos']) : [];
                $imagen = imagenReportajePublico($reportaje['foto_principal'] ?: ($fotos[0] ?? ''));
                $enlace = '/conte_reportaje.php?id=' . (int) $reportaje['id'];
                ?>
                <div class="col-lg-4 col-md-6 grids5-info mt-5 reportajes-secundarios">
                    <a href="<?= $enlace ?>" class="d-block reportaje-public-cover" style="--decoracion-rojo: <?= escaparReportajePublico($decoracionPortada['color_rojo']) ?>; --decoracion-azul: <?= escaparReportajePublico($decoracionPortada['color_azul']) ?>;"><svg viewBox="0 0 700 450" preserveAspectRatio="none" role="img" aria-label="<?= escaparReportajePublico($reportaje['titulo']) ?>"><defs><clipPath id="decoracion-portada-<?= (int) $reportaje['id'] ?>"><path d="<?= escaparReportajePublico($rutaDecoracionSvg) ?>"></path></clipPath></defs><image href="<?= escaparReportajePublico($imagen) ?>" x="0" y="0" width="700" height="450" preserveAspectRatio="xMidYMid slice" clip-path="url(#decoracion-portada-<?= (int) $reportaje['id'] ?>)"></image></svg></a>
                    <div class="blog-info">
                        <h5><?= escaparReportajePublico(fechaReportajePublico($reportaje['fecha_publicacion'])) ?></h5>
                        <?php if (!empty($reportaje['es_destacado'])): ?><span class="destacado-badge"><i class="bi bi-star-fill" aria-hidden="true"></i> Reportaje destacado</span><?php endif; ?>
                        <h4 class="reportaje-list-title"><a href="<?= $enlace ?>" class="d-block"><?= escaparReportajePublico($reportaje['titulo']) ?></a></h4>
                        <a href="<?= $enlace ?>" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($reportajesPagina === []): ?><div class="col-12"><p>No hay reportajes publicados.</p></div><?php endif; ?>
                <?php if (false): ?>
                <div class="col-lg-4 col-md-6 grids5-info">
                    <a href="mas-de-730-mineros-con-reinfo-vigente-o-suspendido-participan-en-las-elecciones-regionales-y-municipales.html" class="d-block"><img src="assets/images/reportaje-28-08-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 28, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="mas-de-730-mineros-con-reinfo-vigente-o-suspendido-participan-en-las-elecciones-regionales-y-municipales.html" class="d-block">Más de 730 mineros con Reinfo vigente o suspendido participan en las elecciones regionales y municipales</a></h4>
                        <a href="mas-de-730-mineros-con-reinfo-vigente-o-suspendido-participan-en-las-elecciones-regionales-y-municipales.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info">
                    <a href="quiruvilca-el-pueblo-perforado-por-la-mineria-ilegal.html" class="d-block"><img src="assets/images/reportaje-18-08-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 18, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="quiruvilca-el-pueblo-perforado-por-la-mineria-ilegal.html" class="d-block">Quiruvilca: el pueblo perforado por la minería ilegal</a></h4>
                        <a href="quiruvilca-el-pueblo-perforado-por-la-mineria-ilegal.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info">
                    <a href="como-evitar-que-el-canon-del-boom-minero-termine-en-obras-de-poco-impacto.html" class="d-block"><img src="assets/images/reportaje-12-08-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 12, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="como-evitar-que-el-canon-del-boom-minero-termine-en-obras-de-poco-impacto.html" class="d-block">Cómo evitar que el canon del boom minero termine en obras de poco impacto</a></h4>
                        <a href="como-evitar-que-el-canon-del-boom-minero-termine-en-obras-de-poco-impacto.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="mineria-ilegal-la-brecha-sigue-abierta-a-una-semana-del-nuevo-gobierno.html" class="d-block"><img src="assets/images/reportaje-05-08-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 05, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="mineria-ilegal-la-brecha-sigue-abierta-a-una-semana-del-nuevo-gobierno.html" class="d-block">Minería ilegal: la brecha sigue abierta a una semana del nuevo gobierno</a></h4>
                        <a href="mineria-ilegal-la-brecha-sigue-abierta-a-una-semana-del-nuevo-gobierno.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="asi-lavan-el-oro-ilegal-plantas-procesadoras-y-mineros-con-reinfo.html" class="d-block"><img src="assets/images/reportaje-30-07-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Jul 30, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="asi-lavan-el-oro-ilegal-plantas-procesadoras-y-mineros-con-reinfo.html" class="d-block">Así lavan el oro ilegal plantas procesadoras y mineros con Reinfo</a></h4>
                        <a href="asi-lavan-el-oro-ilegal-plantas-procesadoras-y-mineros-con-reinfo.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="medidas-que-el-nuevo-gobierno-debe-tomar-para-frenar-la-mineria-ilegal.html" class="d-block"><img src="assets/images/reportaje-24-07-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Jul 24, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="medidas-que-el-nuevo-gobierno-debe-tomar-para-frenar-la-mineria-ilegal.html" class="d-block">Medidas que el nuevo gobierno debe tomar para frenar la minería ilegal</a></h4>
                        <a href="medidas-que-el-nuevo-gobierno-debe-tomar-para-frenar-la-mineria-ilegal.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="por-que-algunas-comunidades-respaldan-actividades-de-mineria-ilegal.html" class="d-block"><img src="assets/images/reportaje-16-07-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Jul 16, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="por-que-algunas-comunidades-respaldan-actividades-de-mineria-ilegal.html" class="d-block">Por qué algunas comunidades respaldan actividades de minería ilegal</a></h4>
                        <a href="por-que-algunas-comunidades-respaldan-actividades-de-mineria-ilegal.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="mas-reservas-cuencas-y-zonas-protegidas-afectadas-por-la-mineria-ilegal.html" class="d-block"><img src="assets/images/reportaje-07-07-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Jul 07, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="mas-reservas-cuencas-y-zonas-protegidas-afectadas-por-la-mineria-ilegal.html" class="d-block">Más reservas, cuencas y zonas protegidas afectadas por la minería ilegal</a></h4>
                        <a href="mas-reservas-cuencas-y-zonas-protegidas-afectadas-por-la-mineria-ilegal.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="aportes-mineros-para-las-regiones-y-el-gobierno-central-crecieron-62-porciento-en-2026.html" class="d-block"><img src="assets/images/reportaje-02-07-26.jpg" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Jul 02, 2026</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a href="aportes-mineros-para-las-regiones-y-el-gobierno-central-crecieron-62-porciento-en-2026.html" class="d-block">Aportes mineros para las regiones y el gobierno central crecieron 62% en 2026</a></h4>
                        <a href="aportes-mineros-para-las-regiones-y-el-gobierno-central-crecieron-62-porciento-en-2026.html" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="pagination">
                <ul>
                    <?php if ($paginaActual > 1): ?><li class="prev"><a href="reportajes-1.php?page=<?= $paginaActual - 1 ?>"> Ant</a></li><?php endif; ?>
                    <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
                    <li><a href="reportajes-1.php?page=<?= $pagina ?>" class="<?= $pagina === $paginaActual ? 'active' : '' ?>"><?= $pagina ?></a></li>
                    <?php endfor; ?>
                    <?php if ($paginaActual < $totalPaginas): ?><li class="next"><a href="reportajes-1.php?page=<?= $paginaActual + 1 ?>"> Sig </a></li><?php endif; ?>
                </ul>
            </div>
        </div>
</div>
<!-- // grids block 5 -->
<!-- footer block -->
<section class="w3l-footer-29-main py-5" id="footer">
  <div class="footer-29 py-md-3">
    <div class="container">
      <div class="row footer-top-29">
        <div class="col-lg-6 col-md-6 footer-list-29 footer-1">
          <h6 class="footer-title-29">Quiénes Somos</h6>
          <p>Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
          <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokp.png"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram"></span></a>
            <!--<a href="#youtube" class="youtube"><span class="fa fa-youtube"></span></a>
            <a href="#linkedin" class="linkedin"><span class="fa fa-linkedin"></span></a>-->
          </div>
        </div>
        <div class="col-lg-3 col-md-6 footer-list-29 footer-2 mt-md-0 mt-5">
          <ul>
            <h6 class="footer-title-29">Contenido</h6>
            <li><a href="#url">Noticias</a></li>
            <li><a href="#url">Videos</a></li>
            <li><a href="#url">Posdcast.</a></li>
          </ul>
        </div>
        <div class="col-lg-3 col-md-6 mt-lg-0 mt-5 footer-list-29 footer-3">
          <div class="properties">
            <h6 class="footer-title-29">Contacto</h6>
            <ul>
            <!--<!--<li><a href="#url">Celulares</a></li>-->
            <!--<li><a href="#url">Celulares</a></li>-->
            <li><a href="#url">info@dialogoydesarrollo.com.pe</a></li>
          </ul>
          </div>
        </div>
      </div>
		<div class="bottom-copies text-center">
			<p class="copy-footer-29">© 2026 Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
		</div>
    </div>
  </div>
  <!-- move top -->
  <button onclick="topFunction()" id="movetop" title="Go to top">
    <span class="fa fa-angle-up"></span>
  </button>
  <script>
    // When the user scrolls down 20px from the top of the document, show the button
    window.onscroll = function () {
      scrollFunction()
    };

    function scrollFunction() {
      if (document.body.scrollTop > 20 || document.documentElement.scrollTop > 20) {
        document.getElementById("movetop").style.display = "block";
      } else {
        document.getElementById("movetop").style.display = "none";
      }
    }

    // When the user clicks on the button, scroll to the top of the document
    function topFunction() {
      document.body.scrollTop = 0;
      document.documentElement.scrollTop = 0;
    }
  </script>
  <!-- /move top -->
</section>
<!-- //footer block -->

<!-- Template JavaScript -->
<script src="assets/js/jquery-3.3.1.min.js"></script>

<script src="assets/js/theme-change.js"></script><!-- theme switch js (light and dark)-->

<!-- js for portfolio lightbox -->
<script src="assets/js/lightbox-plus-jquery.min.js"></script>

<!-- responsive tabs -->
<script src="assets/js/easyResponsiveTabs.js"></script>
<!--Plug-in Initialisation-->
<script type="text/javascript">
  $(document).ready(function () {
    //Horizontal Tab
    $('#parentHorizontalTab').easyResponsiveTabs({
      type: 'default', //Types: default, vertical, accordion
      width: 'auto', //auto or any width like 600px
      fit: true, // 100% fit in a container
      tabidentify: 'hor_1', // The tab groups identifier
      activate: function (event) { // Callback function if tab is switched
        var $tab = $(this);
        var $info = $('#nested-tabInfo');
        var $name = $('span', $info);
        $name.text($tab.text());
        $info.show();
      }
    });
  });
</script>


<script src="assets/js/owl.carousel.js"></script>
<!-- logos for customers -->
<script>
  $(document).ready(function () {
    $('.owl-logos').owlCarousel({
      loop: true,
      margin: 0,
      nav: false,
      responsiveClass: true,
      autoplay: true,
      autoplayTimeout: 5000,
      autoplaySpeed: 1000,
      autoplayHoverPause: false,
      responsive: {
        0: {
          items: 2,
          nav: false
        },
        480: {
          items: 2,
          nav: false
        },
        568: {
          items: 3,
          nav: false
        },
        1000: {
          items: 5,
          nav: false
        }
      }
    })
  })
</script>
<!-- //logos owlcarousel -->
<!-- for tesimonials carousel slider -->
<script>
  $(document).ready(function () {
    $("#owl-demo1").owlCarousel({
      loop: true,
      margin: 20,
      responsiveClass: true,
      responsive: {
        0: {
          items: 1,
          nav: true
        },
        768: {
          items: 2,
          nav: false
        },
        1000: {
          items: 3,
          nav: true,
          loop: false
        }
      }
    })
  })
</script>
<!-- //script -->

<!-- script for teams -->
<script>
  $(document).ready(function () {
    $('.owl-carousel').owlCarousel({
      loop: true,
      margin: 0,
      responsiveClass: true,
      responsive: {
        0: {
          items: 1,
          nav: true
        },
        400: {
          items: 2,
          nav: true,
          margin: 20
        },
        768: {
          items: 3,
          nav: true,
          margin: 20
        },
        1000: {
          items: 4,
          nav: true,
          loop: true,
          margin: 25
        }
      }
    })
  })
</script>
<!-- //script for teams-->

<!-- Script for counter -->
<script>
  (() => {
    // Specify the deadline date
    const deadlineDate = new Date('January 27, 2025 23:59:59').getTime();

    // Cache all countdown boxes into consts
    const countdownDays = document.querySelector('.countdown__days .number');
    const countdownHours = document.querySelector('.countdown__hours .number');
    const countdownMinutes = document.querySelector('.countdown__minutes .number');
    const countdownSeconds = document.querySelector('.countdown__seconds .number');

    // Update the count down every 1 second (1000 milliseconds)
    setInterval(() => {
      // Get current date and time
      const currentDate = new Date().getTime();

      // Calculate the distance between current date and time and the deadline date and time
      const distance = deadlineDate - currentDate;

      // Calculations the data for remaining days, hours, minutes and seconds
      const days = Math.floor(distance / (1000 * 60 * 60 * 24));
      const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
      const seconds = Math.floor((distance % (1000 * 60)) / 1000);

      // Insert the result data into individual countdown boxes
      countdownDays.innerHTML = days;
      countdownHours.innerHTML = hours;
      countdownMinutes.innerHTML = minutes;
      countdownSeconds.innerHTML = seconds;
    }, 1000);
  })();
</script>
<!-- //Script for counter -->

<script src="assets/js/jquery.magnific-popup.min.js"></script>
<script>
  $(document).ready(function () {
    $('.popup-with-zoom-anim').magnificPopup({
      type: 'inline',

      fixedContentPos: false,
      fixedBgPos: true,

      overflowY: 'auto',

      closeBtnInside: true,
      preloader: false,

      midClick: true,
      removalDelay: 300,
      mainClass: 'my-mfp-zoom-in'
    });

    $('.popup-with-move-anim').magnificPopup({
      type: 'inline',

      fixedContentPos: false,
      fixedBgPos: true,

      overflowY: 'auto',

      closeBtnInside: true,
      preloader: false,

      midClick: true,
      removalDelay: 300,
      mainClass: 'my-mfp-slide-bottom'
    });
  });
</script>

<!-- disable body scroll which navbar is in active -->
<script>
  $(function () {
    $('.navbar-toggler').click(function () {
      $('body').toggleClass('noscroll');
    })
  });
</script>
<!-- disable body scroll which navbar is in active -->

<!--/MENU-JS-->
<script>
  $(window).on("scroll", function () {
    var scroll = $(window).scrollTop();

    if (scroll >= 80) {
      $("#site-header").addClass("nav-fixed");
    } else {
      $("#site-header").removeClass("nav-fixed");
    }
  });

  //Main navigation Active Class Add Remove
  $(".navbar-toggler").on("click", function () {
    $("header").toggleClass("active");
  });
  $(document).on("ready", function () {
    if ($(window).width() > 991) {
      $("header").removeClass("active");
    }
    $(window).on("resize", function () {
      if ($(window).width() > 991) {
        $("header").removeClass("active");
      }
    });
  });
</script>
<!--//MENU-JS-->

<script src="assets/js/bootstrap.min.js"></script>


</body>

</html>