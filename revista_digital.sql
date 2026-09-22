-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 05-09-2026 a las 18:35:36
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `revista_digital`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `autores`
--

CREATE TABLE `autores` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `ap_paterno` varchar(100) DEFAULT NULL,
  `ap_materno` varchar(100) DEFAULT NULL,
  `nickname` varchar(100) DEFAULT NULL,
  `es_nickname` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `boletines`
--

CREATE TABLE `boletines` (
  `id` int(10) UNSIGNED NOT NULL,
  `numero_boletin` varchar(50) NOT NULL,
  `titulo_boletin` varchar(100) NOT NULL,
  `resumen` varchar(500) DEFAULT NULL,
  `foto_portada` varchar(255) DEFAULT NULL,
  `archivo_pdf` varchar(255) NOT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'publicado',
  `fecha_publicacion` date NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `noticias`
--

CREATE TABLE `noticias` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `link_externo` varchar(500) DEFAULT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'publicado',
  `fecha_publicacion` date NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `podcasts`
--

CREATE TABLE `podcasts` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `url_embed` varchar(500) NOT NULL,
  `portada` varchar(255) DEFAULT NULL,
  `palabras_resaltadas` varchar(500) NOT NULL DEFAULT '',
  `color_resaltado` char(7) NOT NULL DEFAULT '#facc15',
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'publicado',
  `fecha_publicacion` date NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion_sitio`
--

CREATE TABLE `configuracion_sitio` (
  `clave` varchar(100) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportajes`
--

CREATE TABLE `reportajes` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `resumen_corto` varchar(500) DEFAULT NULL,
  `desarrollo` longtext NOT NULL,
  `foto_principal` varchar(255) DEFAULT NULL,
  `decoracion_imagen` text DEFAULT NULL,
  `pdf_adjunto` varchar(255) DEFAULT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'publicado',
  `fecha_publicacion` date NOT NULL,
  `es_destacado` tinyint(1) NOT NULL DEFAULT 0,
  `autor_id` int(10) UNSIGNED NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `updated_at` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportajes_fotos`
--

CREATE TABLE `reportajes_fotos` (
  `id` int(10) UNSIGNED NOT NULL,
  `reportaje_id` int(10) UNSIGNED NOT NULL,
  `url_foto` varchar(255) NOT NULL,
  `orden` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `parrafo_despues` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `descripcion` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombres` varchar(100) NOT NULL,
  `ap_paterno` varchar(100) NOT NULL,
  `ap_materno` varchar(100) DEFAULT NULL,
  `email` varchar(150) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('admin','editor','redactor') NOT NULL DEFAULT 'redactor',
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `videos`
--

CREATE TABLE `videos` (
  `id` int(10) UNSIGNED NOT NULL,
  `titulo` varchar(255) NOT NULL,
  `url_embed` varchar(500) NOT NULL,
  `portada` varchar(255) DEFAULT NULL,
  `estado` enum('borrador','publicado') NOT NULL DEFAULT 'publicado',
  `fecha_publicacion` date NOT NULL,
  `usuario_id` int(10) UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `autores`
--
ALTER TABLE `autores`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `boletines`
--
ALTER TABLE `boletines`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_boletines_numero` (`numero_boletin`),
  ADD KEY `fk_boletines_usuario` (`usuario_id`),
  ADD KEY `idx_boletines_fecha` (`fecha_publicacion`),
  ADD KEY `idx_boletines_estado` (`estado`);

--
-- Indices de la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_noticias_usuario` (`usuario_id`),
  ADD KEY `idx_noticias_fecha` (`fecha_publicacion`),
  ADD KEY `idx_noticias_estado` (`estado`);

--
-- Indices de la tabla `podcasts`
--
ALTER TABLE `podcasts`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_podcasts_usuario` (`usuario_id`),
  ADD KEY `idx_podcasts_fecha` (`fecha_publicacion`),
  ADD KEY `idx_podcasts_estado` (`estado`);

--
-- Indices de la tabla `reportajes`
--
ALTER TABLE `reportajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_reportajes_autor` (`autor_id`),
  ADD KEY `fk_reportajes_usuario` (`usuario_id`),
  ADD KEY `idx_reportajes_fecha` (`fecha_publicacion`),
  ADD KEY `idx_reportajes_destacado` (`es_destacado`),
  ADD KEY `idx_reportajes_estado` (`estado`);

--
-- Indices de la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_reportajes_fotos_reportaje` (`reportaje_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_usuarios_email` (`email`);

--
-- Indices de la tabla `videos`
--
ALTER TABLE `videos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_videos_usuario` (`usuario_id`),
  ADD KEY `idx_videos_fecha` (`fecha_publicacion`),
  ADD KEY `idx_videos_estado` (`estado`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `autores`
--
ALTER TABLE `autores`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `boletines`
--
ALTER TABLE `boletines`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `noticias`
--
ALTER TABLE `noticias`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `podcasts`
--
ALTER TABLE `podcasts`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportajes`
--
ALTER TABLE `reportajes`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `videos`
--
ALTER TABLE `videos`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `boletines`
--
ALTER TABLE `boletines`
  ADD CONSTRAINT `fk_boletines_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `noticias`
--
ALTER TABLE `noticias`
  ADD CONSTRAINT `fk_noticias_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `podcasts`
--
ALTER TABLE `podcasts`
  ADD CONSTRAINT `fk_podcasts_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportajes`
--
ALTER TABLE `reportajes`
  ADD CONSTRAINT `fk_reportajes_autor` FOREIGN KEY (`autor_id`) REFERENCES `autores` (`id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_reportajes_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `reportajes_fotos`
--
ALTER TABLE `reportajes_fotos`
  ADD CONSTRAINT `fk_reportajes_fotos_reportaje` FOREIGN KEY (`reportaje_id`) REFERENCES `reportajes` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `videos`
--
ALTER TABLE `videos`
  ADD CONSTRAINT `fk_videos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
