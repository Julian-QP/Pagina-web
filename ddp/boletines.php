<!--
Author: W3layouts
Author URL: http://w3layouts.com
-->
<?php
require_once __DIR__ . '/admin/conexion.php';

$conexionPublica = new conexion();
$boletinesPublicos = array_values(array_filter(
    $conexionPublica->obtenerBoletines(),
    static fn (array $item): bool => strtolower((string) $item['estado']) === 'publicado'
));
$conexionPublica->close();

$porPagina = 6;
$totalPaginas = max(1, (int) ceil(count($boletinesPublicos) / $porPagina));
$paginaActual = filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1;
$paginaActual = max(1, min($paginaActual, $totalPaginas));
$boletinesPagina = array_slice($boletinesPublicos, ($paginaActual - 1) * $porPagina, $porPagina);

function escaparBoletin(?string $valor): string
{
    return htmlspecialchars($valor ?? '', ENT_QUOTES, 'UTF-8');
}

function fechaBoletin(?string $valor): string
{
    if (!$valor) {
        return '';
    }
    $fecha = DateTime::createFromFormat('Y-m-d', $valor);
    return $fecha ? $fecha->format('d/m/Y') : $valor;
}

function rutaBoletin(?string $ruta, string $predeterminada = ''): string
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

function resumenBoletinPublico(?string $resumen): string
{
    return trim((string) preg_replace(
        '/^\s*Bolet[ií]n\s+NTEP(?:\s+A[ñn]o\s+\d{4})?\s*\.?\s*/iu',
        '',
        (string) $resumen
    ));
}
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
      .boletin-title-safe,
      .boletin-summary-safe { overflow-wrap: anywhere; white-space: normal; word-break: break-word; }
      .boletin-summary-safe { line-height: 1.5; margin-top: .65rem; }
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
				  <li class="nav-item @@about__active">
                      <a class="nav-link" href="reportajes-1.php">Reportajes</a>
                  </li>
				  <li class="nav-item @@about__active">
                      <a class="nav-link" href="podcast.php">Podcast</a>
                  </li>
				  <li class="nav-item active">
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
                    <h2 class="title-big">Boletines NTEP</h2>
                    <div class="breadcrumb">
                        <ul>
                            <li>
                                <a href="index.html">Inicio</a>
                            </li>
                            <li class="active">
                                 Boletines
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
                <?php foreach ($boletinesPagina as $indice => $boletin): ?>
                <?php
                $pdf = rutaBoletin($boletin['archivo_pdf'] ?? '', '#');
                $portada = rutaBoletin($boletin['foto_portada'] ?? '', 'assets/images/boletin-ntep-45.png');
                $numeroBoletin = count($boletinesPublicos) - (($paginaActual - 1) * $porPagina) - $indice;
                $resumen = resumenBoletinPublico($boletin['resumen'] ?? '');
                ?>
                  <div class="col-lg-4 col-md-6 grids5-info">
                    <a target="_blank" href="<?= escaparBoletin($pdf) ?>" class="d-block"><img src="<?= escaparBoletin($portada) ?>" alt="Boletín <?= escaparBoletin($boletin['numero_boletin']) ?>" class="img-fluid" style="width: 100%; height: 360px; object-fit: cover; object-position: top;" /></a>
                    <div class="blog-info">
                        <h5><?= escaparBoletin($boletin['titulo_boletin'] ?: 'Boletín NTEP') ?></h5>
                        <?php if ($resumen !== ''): ?><p class="boletin-summary-safe"><?= nl2br(escaparBoletin($resumen)) ?></p><?php endif; ?>
                        <h4 class="boletin-title-safe">N.º <?= $numeroBoletin ?></h4>
                        <a target="_blank" href="<?= escaparBoletin($pdf) ?>" class="btn mt-4 p-0">Ver boletín <i class="fa fa-download" aria-hidden="true"></i></a>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if ($boletinesPagina === []): ?><div class="col-12"><p>No hay boletines publicados.</p></div><?php endif; ?>
                <?php if (false): ?>
                <div class="col-lg-4 col-md-6 grids5-info">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N45-2808.pdf" class="d-block"><img src="assets/images/boletin-ntep-45.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 28, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N45-2808.pdf" class="btn mt-4 p-0">Ver boletin <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N44-2508.pdf" class="d-block"><img src="assets/images/boletin-ntep-44.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 25, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N44-2508.pdf" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N43-2108.pdf" class="d-block"><img src="assets/images/boletin-ntep-43.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 21, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N43-2108.pdf" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N42-1808.pdf" class="d-block"><img src="assets/images/boletin-ntep-42.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 18, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N42-1808.pdf" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N41-1408.pdf" class="d-block"><img src="assets/images/boletin-ntep-41.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 14, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N41-1408.pdf" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
				<div class="col-lg-4 col-md-6 grids5-info mt-md-0 mt-5">
                    <a target="_blank" href="boletines/boletin-NTEP-edicion-N40-1108.pdf" class="d-block"><img src="assets/images/boletin-ntep-40.png" alt="" class="img-fluid" /></a>
                    <div class="blog-info">
                        <h5>Ago 11, 2025</h5>
                        <!--<ul class="blog-info">
                            <li><a href="#admin"><span class="fa fa-user"></span> admin</a></li>
                            <li><a href="#comments"><span class="fa fa-comments"></span>3 comments</a></li>
                            <li><a href="#shares"><span class="fa fa-share"></span>3 shares</a></li>
                        </ul>-->
                        <a target="_blank" href="boletines/boletin-NTEP-edicion-N40-1108.pdf" class="btn mt-4 p-0">Leer <span class="fa fa-arrow-right"></span> </a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="pagination">
                <ul>
                    <?php if ($paginaActual > 1): ?><li class="prev"><a href="boletines.php?page=<?= $paginaActual - 1 ?>"> Ant</a></li><?php endif; ?>
                    <?php for ($pagina = 1; $pagina <= $totalPaginas; $pagina++): ?>
                    <li><a href="boletines.php?page=<?= $pagina ?>" class="<?= $pagina === $paginaActual ? 'active' : '' ?>"><?= $pagina ?></a></li>
                    <?php endfor; ?>
                    <?php if ($paginaActual < $totalPaginas): ?><li class="next"><a href="boletines.php?page=<?= $paginaActual + 1 ?>"> Sig </a></li><?php endif; ?>
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
            <a target="_blank" href="https://www.facebook.com/DialogoyDesarrolloPeru" class="facebook"><span class="fas fa-facebook-square"></span></a>
            <a target="_blank" href="https://www.tiktok.com/@dialogo.y.desarrollo" class="twitter"><img src="assets/images/tiktokp.png"></a>
            <a target="_blank" href="https://www.instagram.com/dialogo.y.desarrollo/" class="instagram"><span class="fas fa-instagram"></span></a>
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
			<p class="copy-footer-29">© 2025 Diálogo y Desarrollo Perú. All rights reserved | Designed by <a target="_blank" href="https://www.wsperu.info">WebSolutions</a></p>
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