# Data model

## Authentication entity

`usuarios` (`ddp/prueba.sql:127`) has:

- `id` primary key and auto-increment.
- `nombre`, `apellido_paterno`, `apellido_materno`.
- `email` unique and required.
- `password_hash` required, length 255.
- `rol` enum: `administrador`, `editor`, `colaborador`.
- `created_at` timestamp.

## Content relationships

`boletines`, `noticias`, `podcasts`, `reportajes`, and `videos` each reference `usuarios.id` through `usuario_id`. `reportajes` also references `autores.id`; `reportajes_fotos` references `reportajes.id`.

## Login implications

The administrator boundary should query one `usuarios` row by unique `email`, verify `password_hash`, and require `rol = 'administrador'` before creating the session. No user seed row is present in the visible SQL dump.
