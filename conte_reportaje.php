<?php
require_once __DIR__ . '/admin/conexion.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$conexion = new conexion();
$reportaje = $id ? $conexion->obtenerReportaje($id) : null;
$decoracionGlobal = $conexion->obtenerDecoracionReportajes();
$reportajesPublicados = array_values(array_filter(
    $conexion->obtenerReportajes(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$conexion->close();

if ($reportaje === null || strtolower((string) $reportaje['estado']) !== 'publicado') {
    http_response_code(404);
    exit('Reportaje no encontrado.');
}

function escaparDetalle(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function contenidoDetalle(?string $valor): string
{
    $permitidas = '<strong><b><em><i><u><br><p><div><blockquote><h1><h2><h3><h4><h5><h6><ul><ol><li><span><font><img><table><thead><tbody><tr><th><td>';
    $limpio = strip_tags((string) $valor, $permitidas);
    $limpio = preg_replace_callback('/<img\b[^>]*>/i', static function (array $coincidencia): string {
        preg_match('/\bsrc=["\'](config\/image\/[^"\']+)["\']/i', $coincidencia[0], $src);
        preg_match('/\balt=["\']([^"\']*)["\']/i', $coincidencia[0], $alt);
        return isset($src[1]) ? '<img src="ddp/admin/' . htmlspecialchars($src[1], ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars($alt[1] ?? '', ENT_QUOTES, 'UTF-8') . '">' : '';
    }, $limpio) ?? '';
    $limpio = preg_replace('/<(strong|b|em|i|u|ul|ol|li|br)\s[^>]*>/i', '<$1>', $limpio) ?? '';
    $limpio = preg_replace_callback('/<p\b([^>]*)>/i', static function (array $coincidencia): string {
        preg_match('/\bstyle=["\']([^"\']*)["\']/i', $coincidencia[1], $estilo);
        if (!isset($estilo[1])) return '<p>';
        preg_match_all('/(font-family|font-size|text-align|color)\s*:\s*([^;]+);?/i', $estilo[1], $propiedades, PREG_SET_ORDER);
        $permitidas = [];
        foreach ($propiedades as $propiedad) {
            $permitidas[] = strtolower($propiedad[1]) . ':' . trim($propiedad[2]);
        }
        return $permitidas ? '<p style="' . htmlspecialchars(implode(';', $permitidas), ENT_QUOTES, 'UTF-8') . '">' : '<p>';
    }, $limpio) ?? '';
    $limpio = preg_replace_callback('/<(span|div|blockquote|h[1-6]|table|thead|tbody|tr|th|td)\b([^>]*)>/i', static function (array $coincidencia): string {
        $etiqueta = strtolower($coincidencia[1]);
        if (!preg_match('/\bstyle=["\']([^"\']*)["\']/i', $coincidencia[2], $estilo)) {
            return '<' . $etiqueta . '>';
        }
        preg_match_all('/(font-family|font-size|text-align|color|background-color|font-weight|font-style|text-decoration)\s*:\s*([^;]+);?/i', $estilo[1], $propiedades, PREG_SET_ORDER);
        $permitidos = [];
        foreach ($propiedades as $propiedad) {
            $permitidos[] = strtolower($propiedad[1]) . ':' . trim($propiedad[2]);
        }
        return $permitidos ? '<' . $etiqueta . ' style="' . htmlspecialchars(implode(';', $permitidos), ENT_QUOTES, 'UTF-8') . '">' : '<' . $etiqueta . '>';
    }, $limpio) ?? '';
    return preg_replace_callback('/<font\b[^>]*>/i', static function (array $coincidencia): string {
        preg_match('/\bface=["\']?(Arial|Georgia|Verdana|Muli)["\']?/i', $coincidencia[0], $fuente);
        preg_match('/\bsize=["\']?([2-5])["\']?/i', $coincidencia[0], $tamano);
        $atributos = '';
        if (isset($fuente[1])) $atributos .= ' face="' . $fuente[1] . '"';
        if (isset($tamano[1])) $atributos .= ' size="' . $tamano[1] . '"';
        return '<font' . $atributos . '>';
    }, $limpio) ?? '';
}

function imagenDetalle(?string $ruta): string
{
    $ruta = trim((string) $ruta);
    if ($ruta === '') {
        return 'ddp/assets/images/reportaje-18-08-26.jpg';
    }
    if (str_starts_with($ruta, 'config/')) {
        return 'ddp/admin/' . $ruta;
    }
    if (str_starts_with($ruta, 'image/')) {
        return 'ddp/admin/config/' . $ruta;
    }
    return 'ddp/' . ltrim($ruta, '/');
}

function decoracionDetalle(?string $valor): array
{
    $predeterminada = ['rojo' => 420, 'azul' => 560, 'color_rojo' => '#ff3333', 'color_azul' => '#0000cc'];
    $datos = json_decode((string) $valor, true);
    if (!is_array($datos)) {
        return $predeterminada;
    }
    return [
        'rojo' => max(150, min(650, (int) ($datos['rojo'] ?? 420))),
        'azul' => max(150, min(650, (int) ($datos['azul'] ?? 560))),
        'color_rojo' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($datos['color_rojo'] ?? '')) ? $datos['color_rojo'] : $predeterminada['color_rojo'],
        'color_azul' => preg_match('/^#[0-9a-f]{6}$/i', (string) ($datos['color_azul'] ?? '')) ? $datos['color_azul'] : $predeterminada['color_azul'],
    ];
}

function fechaDetalle(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha ? $fecha->format('d/m/Y') : $valor;
}

$imagen = imagenDetalle($reportaje['foto_principal']);
$decoracion = decoracionDetalle($decoracionGlobal);
$controlAzulX = $decoracion['azul'] + 160;
$clipPath = "path('M 0,20 C 0,9 9,0 25,0 L {$decoracion['rojo']},0 C 700,0 715,280 715,150 C 715,220 {$controlAzulX},420 {$decoracion['azul']},450 L 20,450 C 9,450 0,441 0,430 Z')";
$decoracionRojoPorcentaje = round($decoracion['rojo'] / 7, 2);
$decoracionAzulPorcentaje = round($decoracion['azul'] / 7, 2);
$enlacePdf = $reportaje['pdf_adjunto'] ? imagenDetalle($reportaje['pdf_adjunto']) : '';
$contenido = json_decode((string) $reportaje['desarrollo'], true);
$parrafos = is_array($contenido) && isset($contenido[0]['contenido'])
    ? $contenido
    : array_map(static fn (string $parrafo): array => ['titulo' => '', 'contenido' => $parrafo], preg_split('/\R\s*\R/', trim((string) $reportaje['desarrollo'])) ?: []);
$fotosPorParrafo = [];
foreach ($reportaje['fotos'] ?? [] as $foto) {
    $fotosPorParrafo[max(0, (int) $foto['parrafo_despues'])][] = $foto;
}
$ultimos = array_values(array_filter(
    $reportajesPublicados,
    static fn (array $item): bool => (int) $item['id'] !== (int) $reportaje['id']
));
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>DyD Perú | <?= escaparDetalle($reportaje['titulo']) ?></title>
    <link href="https://fonts.googleapis.com/css?family=Cabin:400,500,600&amp;subset=latin-ext,vietnamese" rel="stylesheet">
    <link rel="stylesheet" href="ddp/assets/css/style-starter.css">
    <style>
        .reportaje-content-image {
            display: block;
            height: 420px;
            object-fit: cover;
            width: 100%;
        }

        .reportaje-cover-decoration {
            background: linear-gradient(to bottom, var(--decoracion-rojo) 50%, var(--decoracion-azul) 50%);
            border-radius: 24px;
            height: 420px;
            overflow: hidden;
            position: relative;
        }

        .reportaje-cover-decoration img {
            clip-path: polygon(0 0, var(--decoracion-rojo-x, 60%) 0, 72% .7%, 82% 3.3%, 90% 8.1%, 96% 20%, 99% 36.5%, 100% 43%, 100% 50%, 99% 63.5%, 96% 80%, 90% 91.9%, 82% 96.7%, var(--decoracion-azul-x, 80%) 100%, 0 100%);
            height: 420px;
            object-fit: cover;
            width: 100%;
        }

        .blog-single-post,
        .single-post-content {
            min-width: 0;
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .blog-single-post .title-single {
            overflow-wrap: anywhere;
            word-break: break-word;
        }

        .reportaje-paragraph-title {
            overflow-wrap: anywhere;
            font-weight: 700;
            margin-bottom: .75rem;
            max-width: 100%;
            word-break: break-word;
        }

        .reportaje-figure {
            margin-left: 0;
            margin-right: 0;
            max-width: 100%;
            overflow: hidden;
        }

        .reportaje-figure figcaption {
            color: #333;
            font-size: 1.1rem;
            font-weight: 700;
            font-style: italic;
            line-height: 1.5;
            margin: .5rem auto 0;
            max-width: 75%;
            overflow-wrap: anywhere;
            text-align: center;
            white-space: normal;
            width: 75%;
            word-break: break-word;
        }

        .reportaje-rich-content img {
            display: block;
            height: auto;
            margin: 1rem auto;
            max-width: 100%;
        }

        .reportaje-rich-content {
            font-size: 1.5rem;
            line-height: 1.75;
        }

        @media (max-width: 576px) {
            .reportaje-content-image {
                height: 260px;
            }
        }
    </style>
</head>
<body>
<header id="site-header" class="fixed-top">
    <div class="container">
        <nav class="navbar navbar-expand-lg stroke">
            <a class="navbar-brand" href="ddp/index.php"><img src="ddp/assets/images/logo.png" alt="Diálogo y Desarrollo Perú" style="height:75px;"></a>
            <button class="navbar-toggler collapsed bg-gradient" type="button" data-toggle="collapse" data-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Abrir menú">
                <span class="navbar-toggler-icon fa icon-expand fa-bars"></span>
                <span class="navbar-toggler-icon fa icon-close fa-times"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item"><a class="nav-link" href="ddp/index.php">Inicio</a></li>
                    <li class="nav-item"><a class="nav-link" href="ddp/index.php#actualidad">Actualidad</a></li>
                    <li class="nav-item active"><a class="nav-link" href="ddp/reportajes-1.php">Reportajes</a></li>
                    <li class="nav-item"><a class="nav-link" href="ddp/about.html">Podcast</a></li>
                    <li class="nav-item"><a class="nav-link" href="ddp/boletines.php">Boletín NTEP</a></li>
                    <li class="nav-item"><a class="nav-link" href="ddp/about.html">Alianzas</a></li>
                    <li class="nav-item"><a class="nav-link" href="ddp/contact.html">Sobre D&D</a></li>
                    <li class="ml-2"><a href="#footer" class="btn btn-style btn-outline-secondary">Contacto</a></li>
                </ul>
            </div>
        </nav>
    </div>
</header>

<section class="breadcrumb-area py-sm-5 py-4">
    <div class="container"><div class="row"><div class="col-md-12"><div class="breadcrumb-contents">
        <h2 class="title-big">Reportajes</h2>
        <div class="breadcrumb"><ul><li><a href="ddp/index.php">Inicio</a></li><li class="active"><a href="ddp/reportajes-1.php">Reportajes</a></li></ul></div>
    </div></div></div></div>
</section>

<section class="w3l-blog mt-lg-5">
    <div class="text-element-9 py-5 mt-lg-5">
        <div class="container py-lg-3"><div class="row grid-text-9">
            <div class="col-lg-8">
                <div class="blog-single-post">
                    <div class="post-content">
                        <h2 class="title-single mb-3"><?= escaparDetalle($reportaje['titulo']) ?></h2>
                    </div>
                    <div class="blo-singl mb-4">
                        <ul class="blog-single-author-date d-flex align-items-center">
                            <?php $autor = trim(($reportaje['autor_nombres'] ?? '') . ' ' . ($reportaje['autor_ap_paterno'] ?? '') . ' ' . ($reportaje['autor_ap_materno'] ?? '')); ?>
                            <?php if ($autor !== ''): ?><li>Por <?= escaparDetalle($autor) ?></li><?php endif; ?>
                            <li><?= escaparDetalle(fechaDetalle($reportaje['fecha_publicacion'])) ?></li>
                        </ul>
                    </div>
                    <div class="single-post-image mb-4 text-center">
                        <?php if ($enlacePdf !== ''): ?><a target="_blank" href="<?= escaparDetalle($enlacePdf) ?>"><?php endif; ?>
                        <div class="reportaje-cover-decoration" style="--decoracion-rojo: <?= escaparDetalle($decoracion['color_rojo']) ?>; --decoracion-azul: <?= escaparDetalle($decoracion['color_azul']) ?>; --decoracion-rojo-x: <?= $decoracionRojoPorcentaje ?>%; --decoracion-azul-x: <?= $decoracionAzulPorcentaje ?>%;">
                            <img src="<?= escaparDetalle($imagen) ?>" class="img-fluid reportaje-content-image radius-image" alt="<?= escaparDetalle($reportaje['titulo']) ?>">
                        </div>
                        <?php if ($enlacePdf !== ''): ?><br>Clic en la imagen para ver el PDF completo</a><?php endif; ?>
                    </div>
                    <div class="single-post-content">
                        <?php if (trim((string) $reportaje['resumen_corto']) !== ''): ?><blockquote class="blockquote my-5"><q class="mb-3 d-block"><?= escaparDetalle($reportaje['resumen_corto']) ?></q></blockquote><?php endif; ?>
                        <?php foreach ($fotosPorParrafo[0] ?? [] as $foto): ?><figure class="reportaje-figure mb-4"><img src="<?= escaparDetalle(imagenDetalle($foto['url_foto'])) ?>" alt="<?= escaparDetalle($foto['descripcion'] ?? 'Imagen del reportaje') ?>" class="img-fluid reportaje-content-image radius-image"><?php if (trim((string) ($foto['descripcion'] ?? '')) !== ''): ?><figcaption><?= escaparDetalle($foto['descripcion']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?>
                        <?php foreach ($parrafos as $indice => $parrafo): ?>
                            <?php if (trim((string) ($parrafo['titulo'] ?? '')) !== ''): ?><h3 class="reportaje-paragraph-title"><?= escaparDetalle($parrafo['titulo']) ?></h3><?php endif; ?>
                            <div class="mb-4 reportaje-rich-content"><?= contenidoDetalle($parrafo['contenido'] ?? '') ?></div>
                            <?php foreach ($fotosPorParrafo[$indice + 1] ?? [] as $foto): ?><figure class="reportaje-figure mb-4"><img src="<?= escaparDetalle(imagenDetalle($foto['url_foto'])) ?>" alt="<?= escaparDetalle($foto['descripcion'] ?? 'Imagen del reportaje') ?>" class="img-fluid reportaje-content-image radius-image"><?php if (trim((string) ($foto['descripcion'] ?? '')) !== ''): ?><figcaption><?= escaparDetalle($foto['descripcion']) ?></figcaption><?php endif; ?></figure><?php endforeach; ?>
                        <?php endforeach; ?>
                    </div>
                    <nav class="navigation posts-navigation" aria-label="Navegación de reportajes">
                        <div class="nav-links"><div class="nav-previous"><span class="nav-title"><span class="fa fa-arrow-left mr-2"></span><a href="ddp/reportajes-1.php">Reportajes</a></span></div></div>
                    </nav>
                </div>
            </div>
            <div class="col-lg-4 left-text-9 mt-lg-0 mt-5 pl-lg-4">
                <div class="left-top-9 mt-5 pt-sm-3">
                    <h6 class="heading-small-text-9 mb-3">Últimas noticias</h6>
                    <?php foreach (array_slice($ultimos, 0, 3) as $ultimo): ?><a href="conte_reportaje.php?id=<?= (int) $ultimo['id'] ?>" class="p-post d-block py-2"><h6 class="text-left-inner-9"><?= escaparDetalle($ultimo['titulo']) ?></h6><span class="sub-inner-text-9"><?= escaparDetalle(fechaDetalle($ultimo['fecha_publicacion'])) ?></span></a><?php endforeach; ?>
                </div>
                <div class="categories mt-5 pt-sm-3"><h6 class="heading-small-text-9">Archivos</h6><ul><li><a href="ddp/reportajes-1.php">Todos los reportajes</a></li></ul></div>
            </div>
        </div></div>
    </div>
</section>
<section class="w3l-footer-29-main py-5" id="footer"><div class="footer-29 py-md-3"><div class="container"><div class="bottom-copies text-center"><p class="copy-footer-29">© 2026 Diálogo y Desarrollo Perú.</p></div></div></div></section>
<script src="ddp/assets/js/jquery-3.3.1.min.js"></script>
<script src="ddp/assets/js/bootstrap.min.js"></script>
</body>
</html>