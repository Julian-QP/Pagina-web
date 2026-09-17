# Tech stack

- PHP 8.2.12 and MariaDB 10.4.32 are recorded in `ddp/prueba.sql`.
- Database access is procedural `mysqli`, configured in `ddp/clases/conexion.php`.
- The connection targets host `localhost`, user `root`, an empty local password, and database `prueba`.
- The admin interface is a static Bootstrap/Chameleon template with PHP page extensions.

## Migration blockers for the requested feature

- `conexion::query*` accepts raw SQL strings, which prevents safe credential lookup without prepared statements.
- Connection errors are printed directly and terminate execution; production-safe error handling is not present.
- No session lifecycle, password verification, CSRF protection, logout, or role guard was found.
- The schema stores `usuarios.password_hash`, so the login must use `password_verify()` and account creation must use `password_hash()`.
