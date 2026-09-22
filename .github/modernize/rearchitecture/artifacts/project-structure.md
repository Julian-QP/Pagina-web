# Project structure

## Scope

PHP application under `ddp/`, with public content pages and an HTML admin template under `ddp/admin/`. This analysis is scoped to the requested database connection, administrator login, and protected panel entry.

## Functional domains

- Public content: `ddp/index.php`, `ddp/boletines.php`, `ddp/reportajes-1.php`.
- Authentication: `ddp/admin/vista/login.php` (currently empty).
- Administration UI: `ddp/admin/index.php` and template pages.
- Persistence: `ddp/clases/conexion.php` plus the `prueba` MariaDB schema.

## Layers

- Presentation: PHP/HTML pages and the Bootstrap/Chameleon assets.
- Application boundary: missing login handler, session bootstrap, and authorization guard.
- Data access: `conexion` wrapper around procedural `mysqli`.
- Database: MariaDB tables in `ddp/prueba.sql`.

## Observed gaps

The public `admin` links use `href="#admin"` and do not navigate to the login. The admin pages have no observed authentication include or session check. The login view has zero lines, so credentials cannot currently be submitted or verified.
