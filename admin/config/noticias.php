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
$existente = $id ? $conexion->obtenerNoticia($id, esRedactor() ? usuarioActualId() : null) : null;

if ($id && $existente === null) {
    $conexion->close();
    http_response_code(404);
    exit('Noticia no encontrada.');
}

$formulario = [
    'titulo' => $existente['titulo'] ?? '',
    'foto' => $existente['foto'] ?? '',
    'link_externo' => $existente['link_externo'] ?? '',
    'estado' => $existente['estado'] ?? 'borrador',
    'fecha_publicacion' => $existente['fecha_publicacion'] ?? date('Y-m-d'),
];
$error = '';

function escaparNoticia(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function rutaImagenNoticia(?string $valor): string
{
    return $valor !== '' ? '../' . ltrim((string) $valor, '/') : '';
}

function guardarFotoNoticia(array $archivo): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('La imagen no pudo cargarse.');
    }
    $extension = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        throw new RuntimeException('La imagen debe ser JPG, PNG o WEBP.');
    }
    $directorio = __DIR__ . '/image';
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
    }
    $nombre = 'noticia-' . bin2hex(random_bytes(8)) . '.' . $extension;
    if (!move_uploaded_file((string) $archivo['tmp_name'], $directorio . DIRECTORY_SEPARATOR . $nombre)) {
        throw new RuntimeException('No se pudo guardar la imagen.');
    }
    return 'config/image/' . $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['titulo', 'link_externo', 'estado', 'fecha_publicacion'] as $campo) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $formulario['estado'] = estadoContenidoPermitido($formulario['estado']);
    if ($formulario['titulo'] === '' ||
        !in_array($formulario['estado'], ['borrador', 'publicado'], true) ||
        $formulario['fecha_publicacion'] === '' ||
        ($formulario['link_externo'] !== '' && !filter_var($formulario['link_externo'], FILTER_VALIDATE_URL))) {
        $error = 'Completa el título, la fecha y los campos con formato válido.';
    } else {
        try {
            $foto = guardarFotoNoticia($_FILES['foto'] ?? []);
            $formulario['foto'] = $foto !== ''
                ? $foto
                : (!empty($_POST['eliminar_foto']) ? '' : ($existente['foto'] ?? ''));
            $guardadoId = $conexion->guardarNoticia(
                $formulario,
                (int) ($_SESSION['usuario_id'] ?? 0),
                $id
            );
            $conexion->close();
            header('Location: noticias.php?id=' . $guardadoId . '&guardado=1');
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
  <title><?= $id ? 'Editar' : 'Nueva' ?> noticia | DDP</title>
  <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/vendors.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/app-lite.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/menu/menu-types/vertical-menu.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/responsive-admin.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/noticias.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/paleta-roja.css">
  </head>
<body class="vertical-layout vertical-menu 2-columns menu-expanded fixed-navbar" data-open="click" data-menu="vertical-menu" data-color="bg-chartbg" data-col="2-columns">
  <div class="main-menu menu-fixed menu-light menu-accordion menu-shadow" data-scroll-to-active="true" data-img="../theme-assets/images/backgrounds/02.jpg">
    <div class="navbar-header">
      <ul class="nav navbar-nav flex-row">
        <li class="nav-item mr-auto"><a class="navbar-brand" href="../index.php"><img class="brand-logo" alt="Chameleon admin logo" src="../theme-assets/images/logo/logo.png"><h3 class="brand-text">DDP</h3></a></li>
        <li class="nav-item d-md-none"><a class="nav-link close-navbar"><i class="ft-x"></i></a></li>
      </ul>
    </div>
    <div class="main-menu-content">
      <ul class="navigation navigation-main" id="main-menu-navigation" data-menu="menu-navigation">
        <li class="active"><a href="../index.php"><i class="ft-home"></i><span class="menu-title">Inicio</span></a></li>
        <li class="nav-item"><a href="../Reportaje.php"><i class="ft-book"></i><span class="menu-title">Reportaje</span></a></li>
        <li class="nav-item active"><a href="../Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title">Noticias</span></a></li>
        <li class="nav-item"><a href="../Podcast.php"><i class="ft-music"></i><span class="menu-title">Podcast</span></a></li>
        <li class="nav-item"><a href="../video.php"><i class="ft-play"></i><span class="menu-title">videos</span></a></li>
        <li class="nav-item"><a href="../boletines.php"><i class="la la-leanpub"></i><span class="menu-title">Boletines</span></a></li>
        <?php if (!esEditor() && !esRedactor()): ?><li class="nav-item"><a href="../usuarios.php"><i class="ft-user"></i><span class="menu-title">usuarios</span></a></li><?php endif; ?>
        <li class="nav-item"><a href="../logout.php"><i class="ft-power"></i><span class="menu-title">Cerrar Sesion</span></a></li>
      </ul>
    </div>
    <div class="navigation-background"></div>
  </div>
  <div class="app-content content">
    <div class="content-wrapper">
      <div class="content-body noticias-content noticias-editor-content">
        <div class="noticias-heading">
          <div><span class="noticias-eyebrow">CONTENIDO INFORMATIVO</span><h1><?= $id ? 'Editar noticia' : 'Nueva noticia' ?></h1><p>Completa la información de la noticia.</p></div>
          <a class="noticias-button noticias-button-secondary" href="../Noticias.php">Volver a noticias</a>
        </div>
        <?php if ($guardado): ?><div class="noticias-alert success">La noticia se guardó correctamente.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="noticias-alert error"><?= escaparNoticia($error) ?></div><?php endif; ?>
        <form class="noticias-form" method="post" enctype="multipart/form-data">
          <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
          <label>Título *
            <input type="text" name="titulo" maxlength="255" value="<?= escaparNoticia($formulario['titulo']) ?>" required>
          </label>
          <label>Enlace externo
            <input type="url" name="link_externo" maxlength="500" value="<?= escaparNoticia($formulario['link_externo']) ?>" placeholder="https://...">
          </label>
          <div class="noticias-form-grid">
            <label>Estado
              <select name="estado"><option value="borrador" <?= $formulario['estado'] === 'borrador' || esRedactor() ? 'selected' : '' ?>>Borrador</option><?php if (!esRedactor()): ?><option value="publicado" <?= $formulario['estado'] === 'publicado' ? 'selected' : '' ?>>Publicado</option><?php endif; ?></select>
            </label>
            <label>Fecha de publicación *
              <input type="date" name="fecha_publicacion" value="<?= escaparNoticia($formulario['fecha_publicacion']) ?>" required>
            </label>
          </div>
          <label>Imagen de la noticia
            <?php if ($formulario['foto']): ?>
              <span class="noticias-image-preview-wrap">
                <img class="noticias-image-preview" id="noticia-image-preview" src="<?= escaparNoticia(rutaImagenNoticia($formulario['foto'])) ?>" alt="Imagen actual de la noticia">
                <span class="noticias-image-actions">
                  <button class="noticias-button noticias-button-secondary noticias-file-button" id="reemplazar-noticia" type="button">Reemplazar imagen</button>
                  <input class="noticias-file-input" id="noticia-file-input" type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
                  <input type="hidden" name="eliminar_foto" value="0">
                  <button class="noticias-button noticias-delete-image" type="button">Eliminar imagen</button>
                </span>
              </span>
            <?php else: ?>
              <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp">
            <?php endif; ?>
            <small>La imagen se guardará en <code>config/image</code>.</small>
          </label>
          <button class="noticias-button" type="submit"><?= $id ? 'Guardar cambios' : 'Crear noticia' ?></button>
        </form>
      </div>
    </div>
  </div>
  <script src="../assets/js/menu.js"></script>
  <script>
    const imagenNoticia = document.querySelector('input[name="foto"]');
    const vistaNoticia = document.getElementById('noticia-image-preview');
    const reemplazarNoticia = document.getElementById('reemplazar-noticia');
    if (reemplazarNoticia && imagenNoticia) {
      reemplazarNoticia.addEventListener('click', () => imagenNoticia.click());
    }
    if (imagenNoticia && vistaNoticia) {
      imagenNoticia.addEventListener('change', () => {
        if (imagenNoticia.files[0]) {
          vistaNoticia.src = URL.createObjectURL(imagenNoticia.files[0]);
        }
      });
    }
    const eliminarNoticia = document.querySelector('.noticias-delete-image');
    if (eliminarNoticia) {
      eliminarNoticia.addEventListener('click', () => {
        const campo = document.querySelector('input[name="eliminar_foto"]');
        campo.value = campo.value === '1' ? '0' : '1';
        eliminarNoticia.classList.toggle('is-selected', campo.value === '1');
        if (vistaNoticia) vistaNoticia.classList.toggle('is-marked-delete', campo.value === '1');
      });
    }
  </script>
</body>
</html>