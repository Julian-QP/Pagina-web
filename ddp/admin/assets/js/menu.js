document.addEventListener('DOMContentLoaded', function () {
  var body = document.body;
  var navbarContainer = document.querySelector('.header-navbar .navbar-container');
  var menu = document.querySelector('.main-menu');
  if (navbarContainer && menu) {
    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'admin-menu-toggle d-md-none';
    toggle.setAttribute('aria-label', 'Abrir menú');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.innerHTML = '<i class="ft-menu"></i>';
    navbarContainer.insertBefore(toggle, navbarContainer.firstChild);

    var close = menu.querySelector('.close-navbar');
    var cerrarMenu = function () {
      body.classList.remove('admin-menu-open');
      toggle.setAttribute('aria-expanded', 'false');
    };
    toggle.addEventListener('click', function () {
      var abierto = body.classList.toggle('admin-menu-open');
      toggle.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
    close?.addEventListener('click', cerrarMenu);
    menu.querySelectorAll('a[href]').forEach(function (link) {
      link.addEventListener('click', function () {
        if (window.innerWidth < 992) {
          cerrarMenu();
        }
      });
    });
    document.addEventListener('click', function (event) {
      if (body.classList.contains('admin-menu-open')
        && !menu.contains(event.target)
        && !toggle.contains(event.target)) {
        cerrarMenu();
      }
    });
    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape') {
        cerrarMenu();
      }
    });
  }

  var current = window.location.pathname.split('/').pop().toLowerCase();
  var sectionByEditor = {
    'podcats.php': 'podcast.php',
    'usuario.php': 'usuarios.php'
  };
  var section = sectionByEditor[current] || current;
  var links = document.querySelectorAll('#main-menu-navigation > li > a');

  links.forEach(function (link) {
    var item = link.parentElement;
    var target = link.getAttribute('href').split('/').pop().split('?')[0].toLowerCase();
    item.classList.toggle('active', target === section);
    if (target === section) {
      link.setAttribute('aria-current', 'page');
    } else {
      link.removeAttribute('aria-current');
    }
  });

  var userToggle = document.querySelector('.dropdown-user-link');
  var userMenu = document.querySelector('.dropdown-user .dropdown-menu');
  var bootstrapDropdownAvailable = window.jQuery && window.jQuery.fn && window.jQuery.fn.dropdown;
  if (userToggle && userMenu && userToggle.getAttribute('href') === '#' && !bootstrapDropdownAvailable) {
    userToggle.addEventListener('click', function (event) {
      event.preventDefault();
      userMenu.classList.toggle('show');
    });
    document.addEventListener('click', function (event) {
      if (!event.target.closest('.dropdown-user')) {
        userMenu.classList.remove('show');
      }

      document.querySelectorAll('a[href$="logout.php"]').forEach(function (link) {
        link.addEventListener('click', function (event) {
          if (!window.confirm('¿Deseas cerrar la sesión?')) {
            event.preventDefault();
          }
        });
      });
    });
  }
});

window.addEventListener('pageshow', function (event) {
  if (event.persisted) {
    window.location.reload();
  }
});