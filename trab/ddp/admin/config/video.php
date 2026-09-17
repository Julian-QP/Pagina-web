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
$existente = $id ? $conexion->obtenerVideo($id, esRedactor() ? usuarioActualId() : null) : null;

if ($id && $existente === null) {
    $conexion->close();
    http_response_code(404);
    exit('Video no encontrado.');
}

$formulario = [
    'titulo' => $existente['titulo'] ?? '',
    'url_embed' => $existente['url_embed'] ?? '',
    'palabras_resaltadas' => $existente['palabras_resaltadas'] ?? '',
    'color_resaltado' => $existente['color_resaltado'] ?? '#facc15',
    'estado' => $existente['estado'] ?? 'borrador',
    'fecha_publicacion' => $existente['fecha_publicacion'] ?? date('Y-m-d'),
];
$error = '';

function escaparVideo(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function normalizarUrlVideo(string $url): string
{
    $partes = parse_url($url);
    $host = strtolower((string) ($partes['host'] ?? ''));
    $ruta = (string) ($partes['path'] ?? '');
    $consulta = [];
    parse_str((string) ($partes['query'] ?? ''), $consulta);

    if (str_contains($host, 'youtube.com') && !str_starts_with($ruta, '/embed/')) {
        $videoId = $consulta['v'] ?? '';
        return is_string($videoId) && $videoId !== ''
            ? 'https://www.youtube.com/embed/' . rawurlencode($videoId)
            : $url;
    }
    if ($host === 'youtu.be') {
        $videoId = trim($ruta, '/');
        return $videoId !== '' ? 'https://www.youtube.com/embed/' . rawurlencode($videoId) : $url;
    }
    return $url;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['titulo', 'url_embed', 'palabras_resaltadas', 'color_resaltado', 'estado', 'fecha_publicacion'] as $campo) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $formulario['url_embed'] = normalizarUrlVideo($formulario['url_embed']);
    $formulario['estado'] = estadoContenidoPermitido($formulario['estado']);

    if ($formulario['titulo'] === '' || $formulario['url_embed'] === '' ||
        !filter_var($formulario['url_embed'], FILTER_VALIDATE_URL) ||
        !preg_match('/^#[0-9a-fA-F]{6}$/', $formulario['color_resaltado']) ||
        !in_array($formulario['estado'], ['borrador', 'publicado'], true) ||
        $formulario['fecha_publicacion'] === '') {
        $error = 'Completa los campos correctamente e indica una URL válida.';
    } else {
        try {
            $formulario['portada'] = $existente['portada'] ?? '';
            $guardadoId = $conexion->guardarVideo(
                $formulario,
                (int) ($_SESSION['usuario_id'] ?? 0),
                $id
            );
            $conexion->close();
            header('Location: video.php?id=' . $guardadoId . '&guardado=1');
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
  <title><?= $id ? 'Editar' : 'Nuevo' ?> video | DDP</title>
  <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
  <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/vendors.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/app-lite.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/menu/menu-types/vertical-menu.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/responsive-admin.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/video.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/paleta-roja.css">
  </head>
<body class="vertical-layout vertical-menu 2-columns menu-expanded fixed-navbar" data-open="click" data-menu="vertical-menu" data-color="bg-chartbg" data-col="2-columns">
  <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true" data-img="../theme-assets/images/backgrounds/02.jpg">
    <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
        <li class="nav-item mr-auto"><a class="navbar-brand" href="../index.php"><img class="brand-logo" alt="admin logo" src="../theme-assets/images/logo/logo.png"><h3 class="brand-text">DDP</h3></a></li>
        <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
      </ul>
    </div>
    <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
        <li class="active"><a href="../index.php"><i class="ft-home"></i><span class="menu-title">Inicio</span></a></li>
        <li class="nav-item"><a href="../Reportaje.php"><i class="ft-book"></i><span class="menu-title">Reportaje</span></a></li>
        <li class="nav-item"><a href="../Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title">Noticias</span></a></li>
        <li class="nav-item"><a href="../Podcast.php"><i class="ft-music"></i><span class="menu-title">Podcast</span></a></li>
        <li class="nav-item active"><a href="../video.php"><i class="ft-play"></i><span class="menu-title">videos</span></a></li>
        <li class="nav-item"><a href="../boletines.php"><i class="la la-leanpub"></i><span class="menu-title">Boletines</span></a></li>
        <?php if (!esEditor() && !esRedactor()): ?><li class="nav-item"><a href="../usuarios.php"><i class="ft-user"></i><span class="menu-title">usuarios</span></a></li><?php endif; ?>
        <li class="nav-item"><a href="../logout.php"><i class="ft-power"></i><span class="menu-title">Cerrar Sesion</span></a></li>
      </ul>
    </div>
    <div class="navigation-background"></div>
  </div>
  <div class="app-content content">
    <div class="content-wrapper">
      <div class="content-body videos-content videos-editor-content">
        <div class="videos-heading">
          <div><span class="videos-eyebrow">CONTENIDO MULTIMEDIA</span><h1><?= $id ? 'Editar video' : 'Nuevo video' ?></h1><p>Agrega el enlace de inserción y define su publicación.</p></div>
          <a class="videos-button videos-button-secondary" href="../video.php">Volver a videos</a>
        </div>
        <?php if ($guardado): ?><div class="videos-alert success">El video se guardó correctamente.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="videos-alert error"><?= escaparVideo($error) ?></div><?php endif; ?>
        <form class="videos-form" method="post">
          <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
          <label>Título del video *
            <input type="text" name="titulo" maxlength="255" value="<?= escaparVideo($formulario['titulo']) ?>" required>
          </label>
          <label>URL de inserción *
            <input type="url" name="url_embed" maxlength="500" value="<?= escaparVideo($formulario['url_embed']) ?>" placeholder="https://www.youtube.com/embed/..." required>
            <small>Usa la URL de inserción proporcionada por YouTube u otra plataforma compatible. El enlace se abrirá en una pestaña nueva desde el panel.</small>
          </label>
          <?php if (filter_var($formulario['url_embed'], FILTER_VALIDATE_URL)): ?>
            <div class="video-preview">
              <strong>Vista previa del enlace</strong>
              <iframe src="<?= escaparVideo($formulario['url_embed']) ?>" title="Vista previa de <?= escaparVideo($formulario['titulo']) ?>" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen></iframe>
              <a class="videos-button videos-button-secondary" href="<?= escaparVideo($formulario['url_embed']) ?>" target="_blank" rel="noopener noreferrer">Abrir enlace original</a>
            </div>
          <?php endif; ?>
          <label>Palabras enmarcadas
            <input type="text" name="palabras_resaltadas" maxlength="500" value="<?= escaparVideo($formulario['palabras_resaltadas']) ?>" placeholder="Ejemplo: minería, artesanal, segura">
            <small>Escribe las palabras separadas por comas. Aparecerán resaltadas en la portada negra.</small>
          </label>
          <label>Color del marco
            <input type="color" name="color_resaltado" value="<?= escaparVideo($formulario['color_resaltado']) ?>" required>
            <small>Selecciona el color que tendrán las palabras enmarcadas.</small>
          </label>
          <div>
            <strong class="video-preview-label">Vista previa de la portada</strong>
            <div class="video-highlight-editor" id="video-highlight-preview">
              <strong><?= escaparVideo($formulario['titulo']) ?></strong>
            </div>
          </div>
          <div class="videos-form-grid">
            <label>Estado
              <select name="estado"><option value="borrador" <?= $formulario['estado'] === 'borrador' || esRedactor() ? 'selected' : '' ?>>Borrador</option><?php if (!esRedactor()): ?><option value="publicado" <?= $formulario['estado'] === 'publicado' ? 'selected' : '' ?>>Publicado</option><?php endif; ?></select>
            </label>
            <label>Fecha de publicación *
              <input type="date" name="fecha_publicacion" value="<?= escaparVideo($formulario['fecha_publicacion']) ?>" required>
            </label>
          </div>
          <button class="videos-button" type="submit"><?= $id ? 'Guardar cambios' : 'Crear video' ?></button>
        </form>
      </div>
    </div>
  </div>
  <script src="../assets/js/menu.js"></script>
  <script>
    (() => {
      const title = document.querySelector('input[name="titulo"]');
      const words = document.querySelector('input[name="palabras_resaltadas"]');
      const color = document.querySelector('input[name="color_resaltado"]');
      const preview = document.getElementById('video-highlight-preview');
      if (!title || !words || !color || !preview) return;
      const escapeHtml = (value) => value.replace(/[&<>"']/g, (character) => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
      }[character]));
      const render = () => {
        const tokens = title.value.trim().split(/(\s+)/).filter(Boolean);
        const wordIndexes = tokens.map((token, index) => /^\s+$/.test(token) ? -1 : index).filter((index) => index >= 0);
        const normalize = (value) => value.trim().toLocaleLowerCase().replace(/[.,;:!?¿¡()[\]{}"']/g, '');
        const phrases = words.value.split(',').map((phrase) => phrase.trim().split(/\s+/).filter(Boolean)).filter((phrase) => phrase.length);
        const markedIndexes = new Set();
        phrases.forEach((phrase) => {
          for (let start = 0; start <= wordIndexes.length - phrase.length; start += 1) {
            const matches = phrase.every((word, offset) => normalize(tokens[wordIndexes[start + offset]]) === normalize(word));
            if (matches) {
              phrase.forEach((_, offset) => markedIndexes.add(wordIndexes[start + offset]));
            }
          }
        });
        let content = '';
        for (let index = 0; index < tokens.length; index += 1) {
          if (!markedIndexes.has(index)) {
            content += escapeHtml(tokens[index]);
            continue;
          }
          let markedText = escapeHtml(tokens[index]);
          while (markedIndexes.has(index + 2)) {
            markedText += escapeHtml(tokens[index + 1]) + escapeHtml(tokens[index + 2]);
            index += 2;
          }
          content += `<span style="background-color:${color.value};color:#fff">${markedText}</span>`;
        }
        preview.innerHTML = `<strong>${content || 'Escribe un título para ver la portada'}</strong>`;
      };
      [title, words, color].forEach((field) => field.addEventListener('input', render));
      render();
    })();
  </script>
</body>
</html>