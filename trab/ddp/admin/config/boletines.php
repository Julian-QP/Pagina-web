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
$existente = $id ? $conexion->obtenerBoletin($id, esRedactor() ? usuarioActualId() : null) : null;

if ($id && $existente === null) {
    $conexion->close();
    http_response_code(404);
    exit('Boletín no encontrado.');
}

$formulario = [
    'numero_boletin' => $existente['numero_boletin'] ?? '',
    'titulo_boletin' => $existente['titulo_boletin'] ?? '',
    'resumen' => $existente['resumen'] ?? '',
    'foto_portada' => $existente['foto_portada'] ?? '',
    'archivo_pdf' => $existente['archivo_pdf'] ?? '',
    'estado' => $existente['estado'] ?? 'borrador',
    'fecha_publicacion' => $existente['fecha_publicacion'] ?? date('Y-m-d'),
];
$error = '';

function escaparBoletin(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function rutaImagenBoletin(?string $valor): string
{
    return $valor !== '' ? '../' . ltrim((string) $valor, '/') : '';
}

function guardarArchivoBoletin(array $archivo, string $directorio, string $prefijo, array $extensiones): string
{
    if (($archivo['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('El archivo no pudo cargarse.');
    }
    $extension = strtolower(pathinfo((string) $archivo['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, $extensiones, true)) {
        throw new RuntimeException('El tipo de archivo no está permitido.');
    }
    if (!is_dir($directorio) && !mkdir($directorio, 0755, true) && !is_dir($directorio)) {
        throw new RuntimeException('No se pudo preparar la carpeta de archivos.');
    }
    $nombre = $prefijo . '-' . bin2hex(random_bytes(8)) . '.' . $extension;
    if (!move_uploaded_file((string) $archivo['tmp_name'], $directorio . DIRECTORY_SEPARATOR . $nombre)) {
        throw new RuntimeException('No se pudo guardar el archivo.');
    }
    return 'config/' . basename($directorio) . '/' . $nombre;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['numero_boletin', 'titulo_boletin', 'resumen', 'estado', 'fecha_publicacion'] as $campo) {
        $formulario[$campo] = trim((string) ($_POST[$campo] ?? ''));
    }
    $formulario['estado'] = estadoContenidoPermitido($formulario['estado']);
    if ($formulario['titulo_boletin'] === '' ||
        $formulario['numero_boletin'] === '' ||
        mb_strlen($formulario['titulo_boletin']) > 100 ||
        mb_strlen($formulario['numero_boletin']) > 100 ||
        mb_strlen($formulario['resumen']) > 200 ||
        !in_array($formulario['estado'], ['borrador', 'publicado'], true) ||
        $formulario['fecha_publicacion'] === '') {
        $error = 'El título y el número no pueden superar 100 caracteres. La descripción no puede superar 200.';
    } else {
        try {
            $portada = guardarArchivoBoletin(
                $_FILES['foto_portada'] ?? [],
                __DIR__ . '/image',
                'boletin',
                ['jpg', 'jpeg', 'png', 'webp']
            );
            $pdf = guardarArchivoBoletin(
                $_FILES['archivo_pdf'] ?? [],
                __DIR__ . '/Pdf',
                'boletin',
                ['pdf']
            );
            if ($id === null && $pdf === '') {
                throw new RuntimeException('Debes seleccionar el PDF del boletín.');
            }
            $formulario['foto_portada'] = $portada !== '' ? $portada : ($existente['foto_portada'] ?? '');
            if ($portada === '' && !empty($_POST['eliminar_foto_portada'])) {
                $formulario['foto_portada'] = '';
            }
            $formulario['archivo_pdf'] = $pdf !== '' ? $pdf : ($existente['archivo_pdf'] ?? '');
            $guardadoId = $conexion->guardarBoletin(
                $formulario,
                (int) ($_SESSION['usuario_id'] ?? 0),
                $id
            );
            $conexion->close();
            header('Location: boletines.php?id=' . $guardadoId . '&guardado=1');
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
  <title><?= $id ? 'Editar' : 'Nuevo' ?> boletín | DDP</title>
  <link href="https://fonts.googleapis.com/css?family=Muli:300,300i,400,400i,600,600i,700,700i%7CComfortaa:300,400,700" rel="stylesheet">
  <link href="https://maxcdn.icons8.com/fonts/line-awesome/1.1/css/line-awesome.min.css" rel="stylesheet">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/vendors.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/app-lite.css">
  <link rel="stylesheet" type="text/css" href="../theme-assets/css/core/menu/menu-types/vertical-menu.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/responsive-admin.css">
  <link rel="stylesheet" type="text/css" href="../assets/css/boletines.css">
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
        <li class="nav-item"><a href="../Noticias.php"><i class="la la-newspaper-o"></i><span class="menu-title">Noticias</span></a></li>
        <li class="nav-item"><a href="../Podcast.php"><i class="ft-music"></i><span class="menu-title">Podcast</span></a></li>
        <li class="nav-item"><a href="../video.php"><i class="ft-play"></i><span class="menu-title">videos</span></a></li>
        <li class="nav-item active"><a href="../boletines.php"><i class="la la-leanpub"></i><span class="menu-title">Boletines</span></a></li>
        <?php if (!esEditor() && !esRedactor()): ?><li class="nav-item"><a href="../usuarios.php"><i class="ft-user"></i><span class="menu-title">usuarios</span></a></li><?php endif; ?>
        <li class="nav-item"><a href="../logout.php"><i class="ft-power"></i><span class="menu-title">Cerrar Sesion</span></a></li>
      </ul>
    </div>
    <div class="navigation-background"></div>
  </div>
  <div class="app-content content">
    <div class="content-wrapper">
      <div class="content-body boletines-content boletines-editor-content">
        <div class="boletines-heading">
          <div><span class="boletines-eyebrow">PUBLICACIONES OFICIALES</span><h1><?= $id ? 'Editar boletín' : 'Nuevo boletín' ?></h1><p>Completa los datos y administra los archivos del boletín.</p></div>
          <a class="boletines-button boletines-button-secondary" href="../boletines.php">Volver a boletines</a>
        </div>
        <?php if ($guardado): ?><div class="boletines-alert success">El boletín se guardó correctamente.</div><?php endif; ?>
        <?php if ($error !== ''): ?><div class="boletines-alert error"><?= escaparBoletin($error) ?></div><?php endif; ?>
        <form class="boletines-form" method="post" enctype="multipart/form-data">
          <?php if ($id): ?><input type="hidden" name="id" value="<?= (int) $id ?>"><?php endif; ?>
          <label>Título del boletín *
            <input type="text" name="titulo_boletin" maxlength="100" value="<?= escaparBoletin($formulario['titulo_boletin']) ?>" placeholder="Ej. Boletín NTEP Año 2025" required>
          </label>
          <label>Número de boletín *
            <input type="text" name="numero_boletin" maxlength="100" value="<?= escaparBoletin($formulario['numero_boletin']) ?>" placeholder="Ej. 01-2026" required>
          </label>
          <label>Resumen
            <textarea name="resumen" maxlength="200" rows="4" placeholder="Máximo 200 caracteres"><?= escaparBoletin($formulario['resumen']) ?></textarea>
          </label>
          <div class="boletines-form-grid">
            <label>Estado
              <select name="estado"><option value="borrador" <?= $formulario['estado'] === 'borrador' || esRedactor() ? 'selected' : '' ?>>Borrador</option><?php if (!esRedactor()): ?><option value="publicado" <?= $formulario['estado'] === 'publicado' ? 'selected' : '' ?>>Publicado</option><?php endif; ?></select>
            </label>
            <label>Fecha de publicación *
              <input type="date" name="fecha_publicacion" value="<?= escaparBoletin($formulario['fecha_publicacion']) ?>" required>
            </label>
          </div>
          <label>Portada del boletín
            <?php if ($formulario['foto_portada']): ?>
              <span class="boletines-image-preview-wrap">
                <img class="boletines-image-preview" id="boletin-image-preview" src="<?= escaparBoletin(rutaImagenBoletin($formulario['foto_portada'])) ?>" alt="Portada actual del boletín">
                <span class="boletines-image-actions">
                  <span class="boletines-button boletines-button-secondary boletines-file-button">Reemplazar imagen
                    <input type="file" name="foto_portada" accept=".jpg,.jpeg,.png,.webp">
                  </span>
                  <input type="hidden" name="eliminar_foto_portada" value="0">
                  <button class="boletines-button boletines-delete-image" type="button">Eliminar imagen</button>
                </span>
              </span>
            <?php else: ?>
              <input type="file" name="foto_portada" accept=".jpg,.jpeg,.png,.webp">
            <?php endif; ?>
            <small>Formatos permitidos: JPG, PNG y WEBP. Se guarda en <code>config/image</code>.</small>
          </label>
          <label>Archivo PDF *
            <input type="file" name="archivo_pdf" accept=".pdf" <?= $id ? '' : 'required' ?>>
            <small>El PDF se guarda en <code>config/Pdf</code>.</small>
            <?php if ($formulario['archivo_pdf']): ?><small>Actual: <?= escaparBoletin($formulario['archivo_pdf']) ?></small><?php endif; ?>
          </label>
          <button class="boletines-button" type="submit"><?= $id ? 'Guardar cambios' : 'Crear boletín' ?></button>
        </form>
      </div>
    </div>
  </div>
  <script src="../assets/js/menu.js"></script>
  <script>
    const imagenBoletin = document.querySelector('input[name="foto_portada"]');
    const vistaBoletin = document.getElementById('boletin-image-preview');
    if (imagenBoletin && vistaBoletin) {
      imagenBoletin.addEventListener('change', () => {
        if (imagenBoletin.files[0]) {
          vistaBoletin.src = URL.createObjectURL(imagenBoletin.files[0]);
        }
      });
    }
    const eliminarBoletin = document.querySelector('.boletines-delete-image');
    if (eliminarBoletin) {
      eliminarBoletin.addEventListener('click', () => {
        const campo = document.querySelector('input[name="eliminar_foto_portada"]');
        campo.value = campo.value === '1' ? '0' : '1';
        eliminarBoletin.classList.toggle('is-selected', campo.value === '1');
        if (vistaBoletin) vistaBoletin.classList.toggle('is-marked-delete', campo.value === '1');
      });
    }
  </script>
</body>
</html>