<!--
Author: W3layouts
Author URL: http://w3layouts.com
-->
<?php
require_once __DIR__ . '/admin/conexion.php';

$conexionPublica = new conexion();
$reportajesPublicos = array_values(array_filter(
    $conexionPublica->obtenerReportajes(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$noticiasPublicas = array_values(array_filter(
    $conexionPublica->obtenerNoticias(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$podcastsPublicos = array_values(array_filter(
    $conexionPublica->obtenerPodcasts(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$boletinesPublicos = array_values(array_filter(
    $conexionPublica->obtenerBoletines(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$videosPublicos = array_values(array_filter(
    $conexionPublica->obtenerVideos(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$decoracionReportajes = $conexionPublica->obtenerDecoracionReportajes();
$conexionPublica->close();

function escaparPublico(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaPublica(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha ? $fecha->format('d/m/Y') : $valor;
}

function rutaImagenPublica(?string $ruta, string $predeterminada): string
{
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return $predeterminada;
    }

    if (str_starts_with($ruta, 'config/')) {
        return 'admin/' . $ruta;
    }
    if (str_starts_with($ruta, 'image/')) {
        return 'admin/config/' . $ruta;
    }
    return $ruta;
}

function decoracionPortadaPublica(?string $valor): array
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

$decoracionPortada = decoracionPortadaPublica($decoracionReportajes);
$clipDecoracionPublica = "path('M 0,20 C 0,9 9,0 25,0 L {$decoracionPortada['rojo']},0 C 700,0 715,280 715,150 C 715,220 " . ($decoracionPortada['azul'] + 160) . ",420 {$decoracionPortada['azul']},450 L 20,450 C 9,450 0,441 0,430 Z')";
$rutaDecoracionSvg = "M 0 20 C 0 9 9 0 25 0 L {$decoracionPortada['rojo']} 0 C 700 0 715 280 715 150 C 715 220 " . ($decoracionPortada['azul'] + 160) . " 420 {$decoracionPortada['azul']} 450 L 20 450 C 9 450 0 441 0 430 Z";
$decoracionRojoPorcentaje = round($decoracionPortada['rojo'] / 7, 2);
$decoracionAzulPorcentaje = round($decoracionPortada['azul'] / 7, 2);

function tituloEspecial(?string $titulo, ?string $palabrasMarcadas, ?string $color): string
{
    $tokens = preg_split('/(\s+)/u', trim((string) $titulo), -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
    $indicesPalabras = [];
    foreach ($tokens as $indice => $token) {
        if (!preg_match('/^\s+$/u', $token)) {
            $indicesPalabras[] = $indice;
        }
    }
    $frases = array_values(array_filter(array_map(
        static fn (string $frase): array => array_values(array_filter(
            preg_split('/\s+/u', trim($frase)) ?: [],
            static fn (string $palabra): bool => $palabra !== ''
        )),
        explode(',', (string) $palabrasMarcadas)
    )));
    $marcados = [];
    $normalizar = static fn (string $palabra): string => mb_strtolower(
        trim($palabra, " \t\n\r\0\x0B.,;:!?¿¡()[]{}\"'"),
        'UTF-8'
    );

    foreach ($frases as $frase) {
        $longitud = count($frase);
        if ($longitud === 0) {
            continue;
        }
        for ($inicio = 0; $inicio <= count($indicesPalabras) - $longitud; $inicio++) {
            $coincide = true;
            for ($offset = 0; $offset < $longitud; $offset++) {
                if ($normalizar($tokens[$indicesPalabras[$inicio + $offset]]) !== $normalizar($frase[$offset])) {
                    $coincide = false;
                    break;
                }
            }
            if ($coincide) {
                for ($offset = 0; $offset < $longitud; $offset++) {
                    $marcados[$indicesPalabras[$inicio + $offset]] = true;
                }
            }
        }
    }

    $colorSeguro = preg_match('/^#[0-9a-fA-F]{6}$/', (string) $color) === 1 ? $color : '#facc15';
    $resultado = '';

    for ($indice = 0; $indice < count($tokens); $indice++) {
        if (!isset($marcados[$indice])) {
            $resultado .= escaparPublico($tokens[$indice]);
            continue;
        }
        $contenido = escaparPublico($tokens[$indice]);
        while ($indice + 2 < count($tokens) && isset($marcados[$indice + 2])) {
            $contenido .= escaparPublico($tokens[$indice + 1]) . escaparPublico($tokens[$indice + 2]);
            $indice += 2;
        }
        $resultado .= '<span class="especiales-highlight" style="background-color: ' . escaparPublico($colorSeguro) . ';">' . $contenido . '</span>';
    }

    return $resultado;
}

$reportajesDestacados = array_values(array_filter(
    $reportajesPublicos,
    static fn (array $item): bool => (int) ($item['es_destacado'] ?? 0) === 1
));
$reportajeReciente = $reportajesDestacados[0] ?? ($reportajesPublicos[0] ?? null);
$reportajesSecundarios = array_values(array_filter(
    $reportajesPublicos,
    static fn (array $item): bool => !$reportajeReciente || (int) $item['id'] !== (int) $reportajeReciente['id']
));
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
      .reportaje-title-safe { overflow-wrap: anywhere; word-break: break-word; }
      .destacado-badge { color: #c62828; display: block; font-size: .78rem; font-weight: 700; margin: .45rem 0; }
      .boletin-number { color: #c62828; font-size: 1.35rem; font-weight: 700; margin-top: 1rem; }
      .boletines-section { background: #fff; }
      .boletin-feature-card { align-items: center; display: grid; gap: 52px; grid-template-columns: minmax(0, 1fr) minmax(300px, 390px); margin: 0 auto; max-width: 1180px; padding: 18px 10px 28px; }
      .boletin-feature-content { min-width: 0; }
      .boletin-feature-card .boletin-title { color: #253858; font-size: clamp(2rem, 4vw, 2.45rem); font-weight: 600; line-height: 1.2; margin-bottom: 18px; }
      .boletin-feature-card .boletin-summary { color: #718096; font-size: 1.08rem; line-height: 0.95; margin: 0; white-space: pre-line; }
      .boletin-number-row { align-items: center; display: flex; gap: 70px; margin-top: 32px; }
      .boletin-feature-card .boletin-number { color: #e30613; font-size: 3.7rem; font-weight: 500; line-height: 1; margin: 0; }
      .boletin-download-block { text-align: center; }
      .boletin-meta { align-items: flex-start; display: flex; justify-content: flex-start; margin-top: 18px; }
      .boletin-meta h4, .boletin-meta a { color: #111827; font-size: 1.2rem; line-height: 1.4; margin: 0; }
      .boletin-download { color: #e30613 !important; display: block; font-size: 3rem !important; line-height: 1; margin-bottom: 10px; }
      .boletin-view-link { color: #111827 !important; font-size: 1.2rem !important; text-decoration: none; }
      .boletin-view-link:hover { color: #e30613 !important; }
      .boletin-all-link { background: #e30613; border-radius: 10px; color: #fff !important; display: inline-block; font-size: 1rem; font-weight: 600; margin-top: 48px; padding: 15px 34px; text-decoration: none; }
      .boletin-all-link:hover { background: #c70510; color: #fff !important; }
      .boletin-cover { border-radius: 10px; display: block; max-height: 540px; object-fit: contain; width: 100%; }
      @media (max-width: 575px) {
        .boletin-feature-card { gap: 28px; grid-template-columns: 1fr; padding: 12px 18px 24px; }
        .boletin-feature-card .boletin-number { font-size: 3rem; }
        .boletin-meta h4, .boletin-meta a, .boletin-view-link { font-size: 1rem !important; }
        .boletin-number-row { gap: 28px; }
      }
      @media (min-width: 576px) and (max-width: 900px) {
        .boletin-feature-card { gap: 28px; grid-template-columns: minmax(0, 1fr) minmax(240px, 320px); }
      }
      .podcast-card-button { background: none; border: 0; color: inherit; cursor: pointer; display: block; font: inherit; padding: 0; text-align: center; width: 100%; }
      .podcast-card-button:focus-visible { outline: 3px solid #c62828; outline-offset: 4px; }
      .podcast-player-backdrop { align-items: center; background: rgba(15, 23, 42, .72); backdrop-filter: blur(8px); display: none; inset: 0; justify-content: center; padding: 1rem; position: fixed; z-index: 1080; }
      .podcast-player-backdrop.is-open { display: flex; }
      .podcast-player { animation: podcast-player-in .25s ease-out; background: #fff; border: 1px solid rgba(255,255,255,.45); border-radius: 20px; box-shadow: 0 24px 70px rgba(0, 0, 0, .35); max-width: 620px; overflow: hidden; width: 100%; }
      .podcast-player-header { align-items: flex-start; background: linear-gradient(135deg, #991b1b, #dc2626); color: #fff; display: flex; gap: 1rem; justify-content: space-between; padding: 1.35rem 1.4rem; }
      .podcast-player-heading { min-width: 0; }
      .podcast-player-eyebrow { color: rgba(255,255,255,.72); display: block; font-size: .68rem; font-weight: 700; letter-spacing: .14em; margin-bottom: .35rem; text-transform: uppercase; }
      .podcast-player-title { font-size: clamp(1.05rem, 2.5vw, 1.45rem); font-weight: 700; line-height: 1.25; margin: 0; overflow-wrap: anywhere; }
      .podcast-player-actions { display: flex; flex-shrink: 0; gap: .45rem; }
      .podcast-player-action { align-items: center; background: rgba(255,255,255,.13); border: 1px solid rgba(255,255,255,.42); border-radius: 999px; color: #fff; cursor: pointer; display: inline-flex; font-size: .76rem; font-weight: 600; gap: .3rem; line-height: 1; padding: .58rem .75rem; text-decoration: none; transition: background .2s ease, transform .2s ease; }
      .podcast-player-action:hover, .podcast-player-action:focus-visible { background: #fff; color: #991b1b; text-decoration: none; transform: translateY(-1px); }
      .podcast-player-content { background: #f8fafc; padding: 1.25rem; }
      .podcast-player-content iframe { border: 0; border-radius: 12px; box-shadow: 0 6px 18px rgba(15,23,42,.12); display: block; height: 152px; width: 100%; }
      .podcast-player-content audio { display: block; width: 100%; }
      .podcast-player-backdrop.is-minimized { align-items: flex-end; background: transparent; bottom: 1rem; inset: auto 1rem 1rem auto; justify-content: flex-end; padding: 0; pointer-events: none; }
      .podcast-player-backdrop.is-minimized .podcast-player { border-radius: 16px; max-width: 430px; pointer-events: auto; }
      .podcast-player-backdrop.is-minimized .podcast-player-header { align-items: center; padding: .85rem 1rem; }
      .podcast-player-backdrop.is-minimized .podcast-player-eyebrow { display: none; }
      .podcast-player-backdrop.is-minimized .podcast-player-title { font-size: .9rem; }
      .podcast-player-backdrop.is-minimized .podcast-player-content { padding: .75rem; }
      .podcast-player-backdrop.is-minimized .podcast-player-content iframe { height: 80px; }
      .visual-player-content iframe { background: #000; border: 0; border-radius: 12px; display: block; height: min(55vw, 430px); width: 100%; }
      .especiales-card-button { background: none; border: 0; color: inherit; cursor: pointer; display: block; font: inherit; padding: 0; text-align: inherit; width: 100%; }
      @keyframes podcast-player-in { from { opacity: 0; transform: translateY(14px) scale(.98); } to { opacity: 1; transform: translateY(0) scale(1); } }
      @media (max-width: 575px) {
        .podcast-player-backdrop.is-minimized { bottom: .5rem; left: .5rem; right: .5rem; }
        .podcast-player-backdrop.is-minimized .podcast-player { max-width: none; }
      }
      @media (max-width: 640px) {
        .podcast-player-header { flex-direction: column; padding: 1.15rem; }
        .podcast-player-actions { width: 100%; }
        .podcast-player-action { justify-content: center; flex: 1; }
      }
      .especiales-section { background: #f8fafc; }
      .especiales-heading { margin-bottom: 2.5rem; }
      .especiales-kicker { color: #b91c1c; display: block; font-size: .72rem; font-weight: 700; letter-spacing: .16em; margin-bottom: .45rem; text-transform: uppercase; }
      .especiales-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; box-shadow: 0 8px 24px rgba(15,23,42,.07); height: 100%; overflow: hidden; }
      .especiales-card:hover { box-shadow: 0 12px 28px rgba(15,23,42,.1); }
      .especiales-media { align-items: center; aspect-ratio: 16 / 10; background: #050505; display: flex; justify-content: center; overflow: hidden; position: relative; transition: transform .3s ease; }
      .especiales-media img { display: none; }
      .especiales-media::after { border: 1px solid rgba(255,255,255,.12); content: ""; inset: 12px; pointer-events: none; position: absolute; }
      .especiales-card:hover .especiales-media { transform: scale(1.04); }
      .especiales-overlay-title { color: #fff; font-size: clamp(1rem, 2vw, 1.3rem); font-weight: 700; left: 1.5rem; line-height: 1.3; margin: 0; max-width: 82%; position: absolute; right: 1.5rem; text-align: center; top: 50%; transform: translateY(-50%); z-index: 1; }
      .especiales-highlight { background: #facc15; box-decoration-break: clone; -webkit-box-decoration-break: clone; color: #fff !important; padding: .08rem .3rem; }
      .especiales-card-caption { color: #64748b; font-size: 1.1rem; font-weight: 600; line-height: 1.5; margin: 0; padding: .9rem 1rem 1.1rem; }
      .reportaje-public-cover { background: linear-gradient(to bottom, var(--decoracion-rojo) 0 50%, var(--decoracion-azul) 50% 100%); display: block; height: 220px; overflow: hidden; }
      .reportaje-public-cover svg { display: block; height: 250px; width: 100%; }
      .grids-block-5 .row { align-items: stretch; }
      .grids-block-5 .grids5-info { display: flex; flex-direction: column; height: auto; }
      .grids-block-5 .grids5-info > a:first-child { display: block; flex: 0 0 250px; height: 250px; overflow: hidden; }
      .grids-block-5 .grids5-info > a:first-child > img { display: block; height: 250px; object-fit: cover; width: 100%; }
      .grids-block-5 .grids5-info .blog-info { display: flex; flex: 1; flex-direction: column; }
      .grids-block-5 .grids5-info .blog-info .btn { margin-top: auto !important; }
      .reportajes-secundarios .blog-info .btn { align-self: flex-start; display: block; margin-left: 0; margin-right: auto; text-align: left; }
      .reportajes-secundarios .reportaje-public-cover { height: 250px; }
      .grids-block-5 .grids5-info > a:first-child { border-radius: 14px 14px 0 0; }
      .grids-block-5 .grids5-info .blog-info { border-radius: 0 0 14px 14px; }
      .news-section .news-card { background: #f5f8fc; border-radius: 10px; overflow: hidden; }
      .news-section .row { display: grid; gap: 38px; grid-template-columns: repeat(3, minmax(0, 1fr)); }
      .news-section .news-card { margin: 0; max-width: none; padding: 0; box-shadow: 0 6px 18px rgba(31, 41, 55, .08); }
      .news-section .news-card > a:first-child { border-radius: 10px 10px 0 0; flex-basis: auto; height: auto; }
      .news-section .news-card > a:first-child > img { height: 370px; object-fit: cover; }
      .news-section .news-card .blog-info { background: #f5f8fc; border: 0; border-radius: 0 0 10px 10px; min-height: 220px; padding: 30px 20px 28px; }
      .news-section .news-card .blog-info h5 { color: #68758a; font-size: 16px; margin-bottom: 18px; }
      .news-section .news-card .blog-info h4 { margin: 0; }
      .news-section .news-card .blog-info h4 a { color: #111827; font-size: 24px; line-height: 30px; margin: 0; }
      .news-section .news-card .blog-info .btn { align-self: flex-start; color: #e11d2e; display: block; font-size: 18px; margin-left: 0; margin-right: auto; margin-top: 28px !important; text-align: left; }
      @media (max-width: 767px) {
        .news-section .news-card > a:first-child > img { height: 300px; }
        .news-section .row { gap: 28px; grid-template-columns: 1fr; }
      }
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
                  <li class="nav-item active">
                      <a class="nav-link" href="index.php">Inicio <span class="sr-only">(current)</span></a>
                  </li>
                  <li class="nav-item @@about__active">
                      <a class="nav-link" href="#actualidad">Actualidad</a>
                  </li>
				  <li class="nav-item @@about__active">
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
                    <!--<div class="breadcrumb">
                        <ul>
                            <li>
                                <a href="index.html">Home</a>
                            </li>
                            <li class="active">
                                 Blog posts
                            </li>
                        </ul>
                    </div>-->
                </div>
            </div><!-- end .col-md-12 -->
        </div><!-- end .row -->
    </div><!-- end .container -->
</section>
<section class="w3l-video w3l-homeblock3 " id="video">
    <!-- /video-6-->
    <div class="container-fluid">
        <div class="video-grids-info row">
            <div class="video-gd-right col-lg-6 p-0">
                <div class="position-relative">
                    <a href="<?= $reportajeReciente ? '../conte_reportaje.php?id=' . (int) $reportajeReciente['id'] : '#actualidad' ?>"><img src="<?= escaparPublico(rutaImagenPublica($reportajeReciente['foto_principal'] ?? '', 'assets/images/video.jpg')) ?>" alt="<?= escaparPublico($reportajeReciente['titulo'] ?? 'Publicación reciente') ?>" class="img-fluid" style="width: 100%; height: 560px; object-fit: cover;"></a>
                    <a href="#small-dialog" class="popup-with-zoom-anim play-view text-center position-absolute">
                        <!--<span class="video-play-icon">
                            <span class="fa fa-play"></span>
                        </span>-->
                    </a>
                    <!-- dialog itself, mfp-hide class is required to make dialog hidden -->
                    <div id="small-dialog" class="zoom-anim-dialog mfp-hide">
                        <iframe src="#" allow="autoplay; fullscreen"allowfullscreen=""></iframe>
                    </div>
                </div>
            </div>
            <div class="video-gd-left col-lg-6 p-lg-5 p-4 align-self">
                <div class="p-xl-4 p-0 video-wrap">
                    <h5><?= escaparPublico(fechaPublica($reportajeReciente['fecha_publicacion'] ?? null)) ?></h5>
                    <?php if (!empty($reportajeReciente['es_destacado'])): ?><span class="destacado-badge"><i class="bi bi-star-fill" aria-hidden="true"></i> Reportaje destacado</span><?php endif; ?>
					<h3 class="title-big text-left mb-4 reportaje-title-safe"><a href="<?= $reportajeReciente ? '../conte_reportaje.php?id=' . (int) $reportajeReciente['id'] : '#actualidad' ?>"><?= escaparPublico($reportajeReciente['titulo'] ?? 'No hay publicaciones recientes') ?></a></h3>
                    <p><?= escaparPublico($reportajeReciente['resumen_corto'] ?? '') ?></p>
					<a href="<?= $reportajeReciente ? '../conte_reportaje.php?id=' . (int) $reportajeReciente['id'] : '#actualidad' ?>" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    <!--<a href="#start" class="btn btn-style btn-primary mt-md-5 mt-4"> Más Videos </a>-->
                </div>
            </div>
        </div>
    </div>
</section>

<div class="grids-block-5 py-1">
    <!-- grids block 5 -->
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php foreach (array_slice($reportajesSecundarios, 0, 3) as $reportaje): ?>
                <?php
                $fotosReportaje = $reportaje['fotos'] ? explode('||', $reportaje['fotos']) : [];
                $imagenReportaje = rutaImagenPublica($reportaje['foto_principal'] ?: ($fotosReportaje[0] ?? ''), 'assets/images/reportaje-18-08-26.jpg');
                $enlaceReportaje = '../conte_reportaje.php?id=' . (int) $reportaje['id'];
                ?>
                <div class="col-lg-4 col-md-6 grids5-info mt-5 reportajes-secundarios">
                    <a href="<?= $enlaceReportaje ?>" class="d-block reportaje-public-cover" style="--decoracion-rojo: <?= escaparPublico($decoracionPortada['color_rojo']) ?>; --decoracion-azul: <?= escaparPublico($decoracionPortada['color_azul']) ?>;"><svg viewBox="0 0 700 450" preserveAspectRatio="none" role="img" aria-label="<?= escaparPublico($reportaje['titulo']) ?>"><defs><clipPath id="decoracion-portada-<?= (int) $reportaje['id'] ?>"><path d="<?= escaparPublico($rutaDecoracionSvg) ?>"></path></clipPath></defs><image href="<?= escaparPublico($imagenReportaje) ?>" x="0" y="0" width="700" height="450" preserveAspectRatio="xMidYMid slice" clip-path="url(#decoracion-portada-<?= (int) $reportaje['id'] ?>)"></image></svg></a>
                    <div class="blog-info">
                        <h5><?= escaparPublico(fechaPublica($reportaje['fecha_publicacion'])) ?></h5>
                        <h4 class="reportaje-title-safe"><a href="<?= $enlaceReportaje ?>" class="d-block"><?= escaparPublico($reportaje['titulo']) ?></a></h4>
                        <a href="<?= $enlaceReportaje ?>" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($reportajesSecundarios === []): ?><div class="col-12"><p>No hay más reportajes publicados.</p></div><?php endif; ?>
            </div>
            <div class="pagination">
                <ul>
                    <li><a href="reportajes-1.php">Ver todos</a></li>
                </ul>
            </div>
        </div>
    </section>
</div>
<!--<section class="w3l-homeblock5 py-0">
    <div class="container py-lg-5 py-4">
        <div class="row">
            <div class="col-lg-8 align-self">
                <h5 class="title-small mb-2">DDP Noticias</h5>
                    <h3 class="title-banner">La minería ilegal no genera desarrollo para los territorios donde opera</h3>
                    <p class="mt-4">Informe del instituto VIDENZA analiza y compara el Índice de Desarrollo Humano en distritos del país, con presencia de minería informal e ilegal y las localidades sin presencia de actividad minera y los que tienen presencia de minería formal...</p>
                        <a href="mapa.html" class="btn btn-style btn-primary mt-md-5 mt-4">Ver Mapa</a>
            </div>
            <div class="col-lg-4 mt-lg-0 mt-4">
                <img src="assets/images/mapa-interactivo.png" class="img-fluid radius-image" alt="">
            </div>
        </div>
    </div>
</section>-->
<section class="breadcrumb-area py-sm-5 py-1">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="breadcrumb-contents">
                    <h2 class="title-big">Noticias Recientes</h2><a class="anchor" id="actualidad"></a>
                    <!--<div class="breadcrumb">
                        <ul>
                            <li>
                                <a href="index.html">Home</a>
                            </li>
                            <li class="active">
                                 Blog posts
                            </li>
                        </ul>
                    </div>-->
                </div>
            </div><!-- end .col-md-12 -->
        </div><!-- end .row -->
    </div><!-- end .container -->
</section>
<div class="grids-block-5 py-5 news-section">
    <!-- grids block 5 -->
    <section class="py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <?php foreach (array_slice($noticiasPublicas, 0, 3) as $noticia): ?>
                <?php
                $enlaceNoticia = trim((string) ($noticia['link_externo'] ?? ''));
                $enlaceNoticia = $enlaceNoticia !== '' ? $enlaceNoticia : '#actualidad';
                ?>
                <div class="col-lg-4 col-md-6 grids5-info news-card">
                    <a target="_blank" href="<?= escaparPublico($enlaceNoticia) ?>" class="d-block"><img src="<?= escaparPublico(rutaImagenPublica($noticia['foto'], 'assets/images/blog.jpg')) ?>" alt="<?= escaparPublico($noticia['titulo']) ?>" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5><?= escaparPublico(fechaPublica($noticia['fecha_publicacion'])) ?></h5>
                        <h4><a target="_blank" href="<?= escaparPublico($enlaceNoticia) ?>" class="d-block"><?= escaparPublico($noticia['titulo']) ?></a></h4>
                        <a target="_blank" href="<?= escaparPublico($enlaceNoticia) ?>" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($noticiasPublicas === []): ?><div class="col-12"><p>No hay noticias publicadas.</p></div><?php endif; ?>
                <?php if (false): ?>
                <div class="col-lg-4 col-md-6 grids5-info">
                    <a target="_blank" href="https://minart.pe/2025/11/07/gold-fields-y-empresas-locales-apuestan-por-el-talento-hualgayoquino-capacitando-a-pobladores-en-manejo-de-camiones-mineros-en-hualgayoc/?fbclid=IwY2xjawON3L1leHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHpzyjGhuFRl3v4LIaB0ks6cftrL-zGT73DnNcsALEsgAQZRUufUc4iTcrLEc_aem_hYAx1MhXJflxvlIajOunzg" class="d-block"><img src="assets/images/nota-facebook-21-11-25.png" alt=""class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Noviembre 21, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a target="_blank" href="https://minart.pe/2025/11/07/gold-fields-y-empresas-locales-apuestan-por-el-talento-hualgayoquino-capacitando-a-pobladores-en-manejo-de-camiones-mineros-en-hualgayoc/?fbclid=IwY2xjawON3L1leHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHpzyjGhuFRl3v4LIaB0ks6cftrL-zGT73DnNcsALEsgAQZRUufUc4iTcrLEc_aem_hYAx1MhXJflxvlIajOunzg" class="d-block">Impulsan talento local en Hualgayoc</a></h4>
                        <a target="_blank" href="https://minart.pe/2025/11/07/gold-fields-y-empresas-locales-apuestan-por-el-talento-hualgayoquino-capacitando-a-pobladores-en-manejo-de-camiones-mineros-en-hualgayoc/?fbclid=IwY2xjawON3L1leHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHpzyjGhuFRl3v4LIaB0ks6cftrL-zGT73DnNcsALEsgAQZRUufUc4iTcrLEc_aem_hYAx1MhXJflxvlIajOunzg" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-lg-0 mt-5">
                    <a target="_blank" href="https://andina.pe/agencia/noticia-canete-inauguran-moderno-local-colegio-construido-inversion-s30-millones-1051936.aspx" class="d-block"><img src="assets/images/nota-facebook-21-11-25b.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Noviembre 21, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a target="_blank" href="https://andina.pe/agencia/noticia-canete-inauguran-moderno-local-colegio-construido-inversion-s30-millones-1051936.aspx" class="d-block">Inauguran moderno colegio en Cerro Azul</a></h4>
                        <a target="_blank" href="https://andina.pe/agencia/noticia-canete-inauguran-moderno-local-colegio-construido-inversion-s30-millones-1051936.aspx" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="https://diarioelnoticiero.com/ministerio-de-vivienda-llego-a-juliaca-para-reafirmar-que-el-proyecto-de-agua-potable-y-alcantarillado-no-se-detiene-2/?fbclid=IwY2xjawON3QpleHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHneyK2etohVG6KyvDXFJM_GtKA_gWI85gaZ5yLBuK66R0SW80sBUUdbwRCjt_aem_ByzW0vi0q7VetPB26LXGBg" class="d-block"><img src="assets/images/nota-facebook-20-11-25.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Noviembre 20, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <h4><a target="_blank" href="https://diarioelnoticiero.com/ministerio-de-vivienda-llego-a-juliaca-para-reafirmar-que-el-proyecto-de-agua-potable-y-alcantarillado-no-se-detiene-2/?fbclid=IwY2xjawON3QpleHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHneyK2etohVG6KyvDXFJM_GtKA_gWI85gaZ5yLBuK66R0SW80sBUUdbwRCjt_aem_ByzW0vi0q7VetPB26LXGBg" class="d-block">Megaproyecto de saneamiento en Juliaca</a></h4>
                        <a target="_blank" href="https://diarioelnoticiero.com/ministerio-de-vivienda-llego-a-juliaca-para-reafirmar-que-el-proyecto-de-agua-potable-y-alcantarillado-no-se-detiene-2/?fbclid=IwY2xjawON3QpleHRuA2FlbQIxMABicmlkETFjbkNQNVZ0NVN4WVhmekpIc3J0YwZhcHBfaWQQMjIyMDM5MTc4ODIwMDg5MgABHneyK2etohVG6KyvDXFJM_GtKA_gWI85gaZ5yLBuK66R0SW80sBUUdbwRCjt_aem_ByzW0vi0q7VetPB26LXGBg" class="btn mt-4 p-0">Leer <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <!--<div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog8.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">Experience the breathtaking views and perspectives</a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog9.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">The absolute best foods for getting that youthful glow</a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">Our quiet not heart along scale sense timed practice</a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>

                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog1.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">Increasing your advantage by aligning strategy.</a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog2.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">Business performance, Design incubator </a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-5">
                    <a href="blog-single.html" class="d-block"><img src="assets/images/blog4.jpg" alt=""
                            class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Posted on May 20, 2022</h5>
                        <ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>
                        <h4><a href="blog-single.html" class="d-block">Preparing for a new global economy</a>
                        </h4>
                        <a href="blog-single.html" class="btn mt-4 p-0">Read More <i class="bi bi-arrow-right" aria-hidden="true"></i> </a>
                    </div>
                </div>-->
                <?php endif; ?>
            </div>
            <div class="pagination">
                <ul>
                    <li><a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru">Ver todos</a></li>
                </ul>
            </div>
        </div>
</div>
<!-- // grids block 5 --

<!-- middle grid -->
<section class="w3l-homeblock5 py-0 boletines-section">
    <div class="container py-lg-5 py-4">
        <div class="row">
            <div class="col-12">
              <div class="boletin-feature-card">
                <div class="boletin-feature-content">
                <?php
                $boletinReciente = $boletinesPublicos[0] ?? null;
                $numeroBoletinReciente = trim((string) ($boletinReciente['numero_boletin'] ?? ''));
                $tituloBoletinReciente = trim((string) ($boletinReciente['titulo_boletin'] ?? ''));
                $resumenBoletinReciente = trim((string) ($boletinReciente['resumen'] ?? ''));
                if ($tituloBoletinReciente !== '' &&
                    str_starts_with(
                        mb_strtolower($resumenBoletinReciente),
                        mb_strtolower($tituloBoletinReciente)
                    )) {
                    $resumenBoletinReciente = trim(substr($resumenBoletinReciente, strlen($tituloBoletinReciente)));
                    $resumenBoletinReciente = ltrim($resumenBoletinReciente, ". \r\n\t");
                }
                $resumenBoletinReciente = preg_replace(
                    '/^\s*Bolet[ií]n\s+NTEP(?:\s+A[ñn]o\s+\d{4})?\s*\.?\s*/iu',
                    '',
                    $resumenBoletinReciente
                );
                ?>
                <?php if ($tituloBoletinReciente !== ''): ?><h3 class="boletin-title"><?= escaparPublico($tituloBoletinReciente) ?></h3><?php endif; ?>
                <p class="boletin-summary"><?= nl2br(escaparPublico($resumenBoletinReciente !== '' ? $resumenBoletinReciente : 'No hay boletines publicados.')) ?></p>
                <div class="boletin-number-row">
                  <?php if ($numeroBoletinReciente !== ''): ?><h4 class="boletin-number">N.º <?= escaparPublico($numeroBoletinReciente) ?></h4><?php endif; ?>
                  <div class="boletin-download-block">
                    <a target="_blank" href="<?= escaparPublico(rutaImagenPublica($boletinReciente['archivo_pdf'] ?? '', '#')) ?>" class="boletin-download" aria-label="Descargar boletín"><i class="bi bi-download" aria-hidden="true"></i></a>
                    <h4><a class="boletin-view-link" target="_blank" href="<?= escaparPublico(rutaImagenPublica($boletinReciente['archivo_pdf'] ?? '', '#')) ?>">Ver boletín</a></h4>
                  </div>
                </div>
                <div class="boletin-meta">
                    <div>
                        <h4><?= escaparPublico(fechaPublica($boletinReciente['fecha_publicacion'] ?? null)) ?></h4>
                    </div>
                </div>
                <a href="boletines.php" class="boletin-all-link">Ver todos</a>
                </div>
                <img src="<?= escaparPublico(rutaImagenPublica($boletinReciente['foto_portada'] ?? '', 'assets/images/boletin-ntep-45.png')) ?>" class="boletin-cover" alt="<?= escaparPublico($tituloBoletinReciente ?: 'Portada del boletín') ?>">
              </div>
                </div>
        </div>
    </div>
</section>
<!-- //middle grid -->
<section class="w3l-homeblock3 py-5">
    <div class="container py-lg-5 py-md-4">
        <!--<h5 class="title-small mb-1 text-center">12 speakers and 20 fun events.</h5>-->
        <h3 class="title-big mb-5 text-center">Podcast</h3>
        <div class="row">
            <?php foreach (array_slice($podcastsPublicos, 0, 4) as $podcast): ?>
            <div class="col-lg-3 col-sm-6">
                <div class="area-box">
                    <button type="button" class="podcast-card-button" data-podcast-url="<?= escaparPublico($podcast['url_embed']) ?>" data-podcast-title="<?= escaparPublico($podcast['titulo']) ?>">
                        <img src="assets/images/podcast.png" alt="<?= escaparPublico($podcast['titulo']) ?>" style="width: 96px; height: 96px; object-fit: cover;">
                        <p><?= escaparPublico($podcast['titulo']) ?></p>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
            <?php if ($podcastsPublicos === []): ?><div class="col-12"><p>No hay podcasts publicados.</p></div><?php endif; ?>
        </div>
		<center><a href="#btn" class="btn btn-style btn-primary mt-md-5 mt-4">Ver todos</a></center>
    </div>
</section>

<div class="podcast-player-backdrop" id="podcast-player-backdrop" aria-hidden="true">
  <div class="podcast-player" role="dialog" aria-modal="true" aria-labelledby="podcast-player-title">
    <div class="podcast-player-header">
      <div class="podcast-player-heading">
        <span class="podcast-player-eyebrow">Reproduciendo ahora</span>
        <h2 class="podcast-player-title" id="podcast-player-title"></h2>
      </div>
      <div class="podcast-player-actions">
        <a class="podcast-player-action" id="podcast-player-redirect" href="#" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir</a>
        <button type="button" class="podcast-player-action" id="podcast-player-minimize"><i class="bi bi-dash-lg" aria-hidden="true"></i> Minimizar</button>
        <button type="button" class="podcast-player-action" id="podcast-player-close"><i class="bi bi-x-lg" aria-hidden="true"></i> Cerrar</button>
      </div>
    </div>
    <div class="podcast-player-content">
      <iframe id="podcast-player-iframe" title="Reproductor de podcast" allow="autoplay; encrypted-media" allowfullscreen hidden></iframe>
      <audio id="podcast-player-audio" controls preload="metadata" hidden></audio>
    </div>
  </div>
</div>

<div class="podcast-player-backdrop" id="visual-player-backdrop" aria-hidden="true">
  <div class="podcast-player" role="dialog" aria-modal="true" aria-labelledby="visual-player-title">
    <div class="podcast-player-header">
      <div class="podcast-player-heading">
        <span class="podcast-player-eyebrow">Contenido audiovisual</span>
        <h2 class="podcast-player-title" id="visual-player-title"></h2>
      </div>
      <div class="podcast-player-actions">
        <a class="podcast-player-action" id="visual-player-redirect" href="#" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right" aria-hidden="true"></i> Abrir enlace original</a>
        <button type="button" class="podcast-player-action" id="visual-player-close"><i class="bi bi-x-lg" aria-hidden="true"></i> Cerrar</button>
      </div>
    </div>
    <div class="podcast-player-content visual-player-content">
      <iframe id="visual-player-iframe" title="Reproductor de video" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
    </div>
  </div>
</div>


<!-- logos Section --
<section class="w3l-logos w3l-homeblock3 py-5">
    <div class="container py-lg-3">
        <h5 class="title-small mb-1 text-center">DyD Perú</h5>
        <h3 class="title-big mb-md-5 mb-4 text-center">Alianzas</h3>
        <div class="row">
            <div class="col-lg-12 mx-auto">
                <div class="owl-logos owl-carousel owl-theme logo-view">
                    <div class="item">
                        <img src="assets/images/logo1.png" alt="company-logo radius-image" class="img-fluid">
                    </div>
                    <div class="item">
                        <img src="assets/images/logo2.png" alt="company-logo radius-image" class="img-fluid">
                    </div>
                    <div class="item">
                        <img src="assets/images/logo3.png" alt="company-logo radius-image" class="img-fluid">
                    </div>
                    <div class="item">
                        <img src="assets/images/logo4.png" alt="company-logo radius-image" class="img-fluid">

                    </div>
                    <div class="item">
                        <img src="assets/images/logo5.png" alt="company-logo radius-image" class="img-fluid">

                    </div>
                    <div class="item">
                        <img src="assets/images/logo6.png" alt="company-logo radius-image" class="img-fluid">

                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- //logos Section -->

<section class="w3l-team especiales-section" id="team">
	<div class="teams1 py-5 mb-3">
		<div class="container py-lg-3 pb-lg-5 pb-4">
			<div class="teams1-content">
                <div class="especiales-heading text-center">
                    <span class="especiales-kicker">Contenido audiovisual</span>
                    <h3 class="title-big mb-2">Especiales</h3>
                    <p class="mb-0">Historias que merecen verse y escucharse.</p>
                </div>
					<div class="owl-carousel owl-theme text-center">
                        <?php foreach (array_slice($videosPublicos, 0, 8) as $video): ?>
						<div class="item">
							<div class="especiales-card text-left">
								<button type="button" class="especiales-card-button" data-video-url="<?= escaparPublico($video['url_embed']) ?>" data-video-title="<?= escaparPublico($video['titulo']) ?>">
                                    <div class="especiales-media">
                                        <img src="<?= escaparPublico(rutaImagenPublica($video['portada'] ?? '', 'assets/images/video.jpg')) ?>" alt="<?= escaparPublico($video['titulo']) ?>">
                                        <h4 class="especiales-overlay-title"><?= tituloEspecial($video['titulo'], $video['palabras_resaltadas'] ?? '', $video['color_resaltado'] ?? '') ?></h4>
                                    </div>
								</button>
							</div>
                            <p class="especiales-card-caption"><?= escaparPublico($video['titulo']) ?></p>
						</div>
                        <?php endforeach; ?>
						<!--<div class="item">
							<div class="d-grid team-info">
								<div class="column position-relative">
									<a href="#url"><img src="assets/images/team6.jpg" alt="" class="img-fluid rounded team-image" /></a>
								</div>
								<div class="column">
									<h3 class="name-pos"><a href="#url">Amber kinsa</a></h3>
									<p>CEO of company</p>
									<div class="social">
										<a href="#facebook" class="facebook"><span class="fa fa-facebook" aria-hidden="true"></span></a>
										<a href="#twitter" class="twitter"><span class="fa fa-twitter" aria-hidden="true"></span></a>
										<a href="#linkedin" class="linkedin"><span class="fa fa-linkedin" aria-hidden="true"></span></a>
									</div>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="d-grid team-info">
								<div class="column position-relative">
									<a href="#url"><img src="assets/images/team7.jpg" alt="" class="img-fluid rounded team-image" /></a>
								</div>
								<div class="column">
									<h3 class="name-pos"><a href="#url">Edward wood</a></h3>
									<p>Manager & Chief</p>
									<div class="social">
										<a href="#facebook" class="facebook"><span class="fa fa-facebook" aria-hidden="true"></span></a>
										<a href="#twitter" class="twitter"><span class="fa fa-twitter" aria-hidden="true"></span></a>
										<a href="#linkedin" class="linkedin"><span class="fa fa-linkedin" aria-hidden="true"></span></a>
									</div>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="d-grid team-info">
								<div class="column position-relative">
									<a href="#url"><img src="assets/images/team8.jpg" alt="" class="img-fluid rounded team-image" /></a>
								</div>
								<div class="column">
									<h3 class="name-pos"><a href="#url">Jonarthan parks</a></h3>
									<p>Manager and Officer</p>
									<div class="social">
										<a href="#facebook" class="facebook"><span class="fa fa-facebook" aria-hidden="true"></span></a>
										<a href="#twitter" class="twitter"><span class="fa fa-twitter" aria-hidden="true"></span></a>
										<a href="#linkedin" class="linkedin"><span class="fa fa-linkedin" aria-hidden="true"></span></a>
									</div>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="d-grid team-info">
								<div class="column position-relative">
									<a href="#url"><img src="assets/images/s1.jpg" alt="" class="img-fluid rounded team-image" /></a>
								</div>
								<div class="column">
									<h3 class="name-pos"><a href="#url">Leroy bell</a></h3>
									<p>CEO of company</p>
									<div class="social">
										<a href="#facebook" class="facebook"><span class="fa fa-facebook" aria-hidden="true"></span></a>
										<a href="#twitter" class="twitter"><span class="fa fa-twitter" aria-hidden="true"></span></a>
										<a href="#linkedin" class="linkedin"><span class="fa fa-linkedin" aria-hidden="true"></span></a>
									</div>
								</div>
							</div>
						</div>
						<div class="item">
							<div class="d-grid team-info">
								<div class="column position-relative">
									<a href="#url"><img src="assets/images/team1.jpg" alt="" class="img-fluid rounded team-image" /></a>
								</div>
								<div class="column">
									<h3 class="name-pos"><a href="#url">Bradley</a></h3>
									<p>Founder of Company</p>
									<div class="social">
										<a href="#facebook" class="facebook"><span class="fa fa-facebook" aria-hidden="true"></span></a>
										<a href="#twitter" class="twitter"><span class="fa fa-twitter" aria-hidden="true"></span></a>
										<a href="#linkedin" class="linkedin"><span class="fa fa-linkedin" aria-hidden="true"></span></a>
									</div>
								</div>
							</div>
						</div>-->
					</div>
			</div>
		</div>
	</div>
</section>
<section class="w3l-banner py-0" id="work">
    <div class="midd-w3 py-lg-4 py-md-3">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mt-lg-0 mt-lg-5 about-right-faq align-self">
                    <h5 class="title-small mb-2">DDP Noticias</h5>
                    <h3 class="title-banner">Diálogo y Desarrollo Perú</h3>
                    <p class="mt-4">Somos un espacio de periodismo independiente que busca visibilizar las acciones de diálogo en el país desde una mirada constructiva.</p>
                        <a href="#btn" class="btn btn-style btn-primary mt-md-5 mt-4">Nosotros</a>
                 </div>
                <div class="col-md-6 left-wthree-img mt-lg-0 mt-4">
                    <div class="position-relative">
                        <img src="assets/images/bannerimg.jpg" alt="" class="img-fluid">
                        <!--<a href="#small-dialog" class="popup-with-zoom-anim play-view text-center position-absolute">
                            <span class="video-play-icon">
                                <span class="fa fa-play"></span>
                            </span>
                        </a>
                         dialog itself, mfp-hide class is required to make dialog hidden -->
                        <div id="small-dialog" class="zoom-anim-dialog mfp-hide">
                            <iframe src="https://www.youtube.com/embed/2jI6fHBtRJU" allow="autoplay; fullscreen" allowfullscreen=""></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
<!-- middle grid --
<section class="w3l-homeblock5 py-5">
    <div class="container py-lg-5 py-4">
        <div class="row">
            <div class="col-lg-6 align-self">
                <h3 class="title-big mb-4"> Don’t miss out on the fun and join the community! </h3>
                <p class="">Lorem ipsum dolor sit amet consectetur adipisicing elit. Voluptates maiores ipsum quos
                    voluptate, cumque perspiciatis dolorem tempora fugit facere ducimus?.</p>
                <div class="row mt-sm-4 mt-2 px-3">
                    <div class="col-6 p-0">
                        <span>80+</span>
                        <h4>Speakers</h4>
                    </div>
                    <div class="col-6 p-0">
                        <span>50+</span>
                        <h4>Workshops</h4>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mt-lg-0 mt-4">
                <img src="assets/images/stats.jpg" class="img-fluid radius-image" alt="">
            </div>
        </div>
    </div>
</section>
<!-- //middle grid -->
<!-- middle -->
<div class="middle py-5">
    <div class="container py-xl-5 py-lg-3">
        <div class="welcome-left text-center py-md-5 py-3">
            <h3 class="title-big">Síguenos en nuestras Redes Sociales</h3>
            <div class="main-social-footer-29">
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fa fa-facebook-square fa-2x"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokg.png"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fa fa-instagram fa-2x"></span></a>
            <!--<a href="#youtube" class="youtube"><span class="fa fa-youtube fa-2x"></span></a>
            <a href="#linkedin" class="linkedin"><span class="fa fa-linkedin fa-2x"></span></a>-->
          </div>
        </div>
    </div>
</div>
<!-- //middle -->


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

<script>
  (() => {
    const backdrop = document.getElementById('podcast-player-backdrop');
    const title = document.getElementById('podcast-player-title');
    const iframe = document.getElementById('podcast-player-iframe');
    const audio = document.getElementById('podcast-player-audio');
    const redirect = document.getElementById('podcast-player-redirect');
    const minimize = document.getElementById('podcast-player-minimize');
    const close = document.getElementById('podcast-player-close');

    if (!backdrop || !title || !iframe || !audio || !redirect || !minimize || !close) {
      return;
    }

    const detenerReproduccion = () => {
      audio.pause();
      audio.removeAttribute('src');
      audio.load();
      iframe.src = 'about:blank';
      iframe.hidden = true;
      audio.hidden = true;
    };

    const cerrarReproductor = () => {
      detenerReproduccion();
      backdrop.classList.remove('is-open', 'is-minimized');
      backdrop.setAttribute('aria-hidden', 'true');
      minimize.textContent = 'Minimizar';
    };

    const normalizarSpotify = (url) => {
      const coincidencia = url.match(/spotify\.com\/(?:embed\/)?(?:[^/]+\/)?(track|episode|show|playlist|album|artist)\/([^/?#]+)/i);
      if (!coincidencia) {
        return null;
      }
      return {
        embed: `https://open.spotify.com/embed/${coincidencia[1]}/${coincidencia[2]}`,
        public: `https://open.spotify.com/${coincidencia[1]}/${coincidencia[2]}`
      };
    };

    document.querySelectorAll('[data-podcast-url]').forEach((boton) => {
      boton.addEventListener('click', () => {
        const url = boton.dataset.podcastUrl || '';
        const nombre = boton.dataset.podcastTitle || 'Podcast';
        const spotify = normalizarSpotify(url);

        detenerReproduccion();
        title.textContent = nombre;
        redirect.href = spotify ? spotify.public : url;
        backdrop.classList.add('is-open');
        backdrop.classList.remove('is-minimized');
        backdrop.setAttribute('aria-hidden', 'false');

        if (spotify) {
          iframe.src = `${spotify.embed}?autoplay=1`;
          iframe.hidden = false;
        } else {
          audio.src = url;
          audio.hidden = false;
          void audio.play();
        }
      });
    });

    minimize.addEventListener('click', () => {
      const minimizado = backdrop.classList.toggle('is-minimized');
      minimize.textContent = minimizado ? 'Ampliar' : 'Minimizar';
    });

    close.addEventListener('click', cerrarReproductor);
    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) {
        cerrarReproductor();
      }
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && backdrop.classList.contains('is-open')) {
        cerrarReproductor();
      }
    });
  })();
</script>
<script>
  (() => {
    const backdrop = document.getElementById('visual-player-backdrop');
    const title = document.getElementById('visual-player-title');
    const iframe = document.getElementById('visual-player-iframe');
    const redirect = document.getElementById('visual-player-redirect');
    const close = document.getElementById('visual-player-close');
    if (!backdrop || !title || !iframe || !redirect || !close) return;

    const videoEmbedUrl = (value) => {
      try {
        const url = new URL(value, window.location.href);
        if (url.hostname.includes('youtu.be')) {
          return `https://www.youtube.com/embed/${url.pathname.slice(1)}?autoplay=1`;
        }
        if (url.hostname.includes('youtube.com')) {
          const id = url.searchParams.get('v');
          if (id) return `https://www.youtube.com/embed/${id}?autoplay=1`;
        }
        return url.href;
      } catch {
        return value;
      }
    };

    const closeVisualPlayer = () => {
      iframe.src = 'about:blank';
      backdrop.classList.remove('is-open');
      backdrop.setAttribute('aria-hidden', 'true');
    };

    document.querySelectorAll('[data-video-url]').forEach((button) => {
      button.addEventListener('click', () => {
        const originalUrl = button.dataset.videoUrl || '';
        title.textContent = button.dataset.videoTitle || 'Video';
        redirect.href = originalUrl;
        iframe.src = videoEmbedUrl(originalUrl);
        backdrop.classList.add('is-open');
        backdrop.setAttribute('aria-hidden', 'false');
      });
    });

    close.addEventListener('click', closeVisualPlayer);
    backdrop.addEventListener('click', (event) => {
      if (event.target === backdrop) closeVisualPlayer();
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && backdrop.classList.contains('is-open')) closeVisualPlayer();
    });
  })();
</script>

</body>

</html>