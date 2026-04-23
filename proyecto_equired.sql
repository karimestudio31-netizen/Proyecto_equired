-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 21-04-2026 a las 23:14:54
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
-- Base de datos: `proyecto_equired`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asesorias`
--

CREATE TABLE `asesorias` (
  `id` int(11) NOT NULL,
  `profesional_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_donacion`
--

CREATE TABLE `comentarios_donacion` (
  `id` int(11) NOT NULL,
  `donacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `comentarios_donacion`
--

INSERT INTO `comentarios_donacion` (`id`, `donacion_id`, `usuario_id`, `comentario`, `fecha`) VALUES
(1, 2, 1, 'bellas', '2026-04-21 02:57:01'),
(2, 2, 2, 'las quiero', '2026-04-21 03:12:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `comentarios_publicacion`
--

CREATE TABLE `comentarios_publicacion` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `donaciones`
--

CREATE TABLE `donaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `titulo` varchar(200) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `ciudad` varchar(100) DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `likes` int(11) DEFAULT 0,
  `monto` decimal(10,2) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `donaciones`
--

INSERT INTO `donaciones` (`id`, `usuario_id`, `titulo`, `descripcion`, `ciudad`, `imagen`, `likes`, `monto`, `mensaje`, `fecha`) VALUES
(2, 1, 'Tarjetas para emprendimientos', 'especial para accesorios, 50 unidades', 'Montería', 'don_69e6e756f17ac.jpg', 0, 0.00, NULL, '2026-04-21 02:56:22');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleos`
--

CREATE TABLE `empleos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleos`
--

INSERT INTO `empleos` (`id`, `empresa_id`, `titulo`, `descripcion`, `fecha`) VALUES
(1, 3, 'Administrador', 'Experiencia en accesorios y bisuteria.', '2026-04-21 13:04:19'),
(2, 3, 'Administrador', 'Exp. en accesorios y bisuteria.', '2026-04-21 13:05:31');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `horarios`
--

CREATE TABLE `horarios` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `dia_hora` datetime NOT NULL,
  `disponible` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_donacion`
--

CREATE TABLE `likes_donacion` (
  `id` int(11) NOT NULL,
  `donacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `likes_donacion`
--

INSERT INTO `likes_donacion` (`id`, `donacion_id`, `usuario_id`, `fecha`) VALUES
(2, 2, 1, '2026-04-21 02:56:51'),
(3, 2, 2, '2026-04-21 03:11:58');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `likes_publicacion`
--

CREATE TABLE `likes_publicacion` (
  `id` int(11) NOT NULL,
  `publicacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `postulaciones`
--

CREATE TABLE `postulaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empleo_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','aceptado','rechazado') DEFAULT 'pendiente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `postulaciones`
--

INSERT INTO `postulaciones` (`id`, `usuario_id`, `empleo_id`, `fecha`, `estado`) VALUES
(1, 1, 2, '2026-04-21 13:06:28', 'pendiente');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `publicaciones`
--

CREATE TABLE `publicaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `contenido` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `publicaciones`
--

INSERT INTO `publicaciones` (`id`, `usuario_id`, `contenido`, `imagen`, `fecha`) VALUES
(1, 1, 'Hola, mi nombre es Karime Gómez, y este mensaje es una prueba para esta maravilla comunidad.', NULL, '2026-04-19 19:25:23'),
(2, 2, 'La ODS 10: Reducción de las desigualdades nos invita a reflexionar sobre una realidad que muchas veces se normaliza: no todas las personas parten desde las mismas oportunidades. Mientras algunos tienen acceso a educación, empleo y servicios básicos, otros enfrentan barreras por su origen, condición económica, género o discapacidad. Esta desigualdad no solo limita el desarrollo individual, sino que también frena el progreso de toda la sociedad.\r\n\r\nReflexionar sobre este objetivo implica reconocer que la equidad no significa tratar a todos igual, sino brindar a cada persona lo que necesita para alcanzar su potencial. También nos lleva a cuestionar nuestras acciones cotidianas: cómo tratamos a los demás, qué oportunidades apoyamos y qué tipo de sociedad queremos construir. Promover la inclusión, la empatía y el respeto no es solo tarea de gobiernos o instituciones, sino de cada individuo.', NULL, '2026-04-19 23:34:09');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `servicios`
--

CREATE TABLE `servicios` (
  `id` int(11) NOT NULL,
  `profesional_id` int(11) NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `especialidad` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `tipo` enum('psicologica','juridica') NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_cita`
--

CREATE TABLE `solicitudes_cita` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `horario_id` int(11) NOT NULL,
  `nombre_solicitante` varchar(100) NOT NULL,
  `celular` varchar(20) NOT NULL,
  `cedula` varchar(20) NOT NULL,
  `edad` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `estado` enum('pendiente','aceptada','rechazada') DEFAULT 'pendiente',
  `usuario_id` int(11) DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes_donacion`
--

CREATE TABLE `solicitudes_donacion` (
  `id` int(11) NOT NULL,
  `donacion_id` int(11) NOT NULL,
  `solicitante_id` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `solicitudes_donacion`
--

INSERT INTO `solicitudes_donacion` (`id`, `donacion_id`, `solicitante_id`, `mensaje`, `fecha`) VALUES
(1, 2, 2, 'xq tengo un emprendimiento de accesorios', '2026-04-21 03:13:00');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('beneficiario','empresa','profesional') NOT NULL,
  `foto_perfil` varchar(255) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `foto_perfil`, `descripcion`, `fecha`) VALUES
(1, 'karime', 'karimestudio31@gmail.com', '$2y$10$Lr/bZ5PMdgKACy/UPEEpue9ERDp5BiFukP2XHzG7swwVsK/Ly0RK6', 'beneficiario', NULL, NULL, '2026-04-19 19:23:56'),
(2, 'Jose', 'Jose123@gmail.com', '$2y$10$w8.jL68ZFeLCKGIw2peCeuaCpXRBece0V2aEfe.XVFKYaV8CXwp9S', 'beneficiario', NULL, NULL, '2026-04-19 23:31:40'),
(3, 'Accesorios de amor', 'accesoriosdeamor1@gmail.com', '$2y$10$2i3G2J9PMl3r2UvY.vCOb.pRzHzZWha.urE58BTPCUuzFdcaroC9u', 'empresa', NULL, NULL, '2026-04-19 23:37:25'),
(4, 'Psicologa Ana Milena', 'psicologa@gmail.com', '$2y$10$0ioHXUw95FETP3IaEHY4au.WkkbbR/mFaIPCNpmsNXFzrBaf2mvHi', 'profesional', NULL, NULL, '2026-04-21 13:33:44');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asesorias`
--
ALTER TABLE `asesorias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesional_id` (`profesional_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `comentarios_donacion`
--
ALTER TABLE `comentarios_donacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donacion_id` (`donacion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `comentarios_publicacion`
--
ALTER TABLE `comentarios_publicacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publicacion_id` (`publicacion_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `empleos`
--
ALTER TABLE `empleos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empresa_id` (`empresa_id`);

--
-- Indices de la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `servicio_id` (`servicio_id`);

--
-- Indices de la tabla `likes_donacion`
--
ALTER TABLE `likes_donacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_like` (`donacion_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unico_like` (`publicacion_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`),
  ADD KEY `empleo_id` (`empleo_id`);

--
-- Indices de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `profesional_id` (`profesional_id`);

--
-- Indices de la tabla `solicitudes_cita`
--
ALTER TABLE `solicitudes_cita`
  ADD PRIMARY KEY (`id`),
  ADD KEY `servicio_id` (`servicio_id`),
  ADD KEY `horario_id` (`horario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `solicitudes_donacion`
--
ALTER TABLE `solicitudes_donacion`
  ADD PRIMARY KEY (`id`),
  ADD KEY `donacion_id` (`donacion_id`),
  ADD KEY `solicitante_id` (`solicitante_id`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asesorias`
--
ALTER TABLE `asesorias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `comentarios_donacion`
--
ALTER TABLE `comentarios_donacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `comentarios_publicacion`
--
ALTER TABLE `comentarios_publicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `donaciones`
--
ALTER TABLE `donaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `empleos`
--
ALTER TABLE `empleos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `likes_donacion`
--
ALTER TABLE `likes_donacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_cita`
--
ALTER TABLE `solicitudes_cita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `solicitudes_donacion`
--
ALTER TABLE `solicitudes_donacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asesorias`
--
ALTER TABLE `asesorias`
  ADD CONSTRAINT `asesorias_ibfk_1` FOREIGN KEY (`profesional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `asesorias_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentarios_donacion`
--
ALTER TABLE `comentarios_donacion`
  ADD CONSTRAINT `comentarios_donacion_ibfk_1` FOREIGN KEY (`donacion_id`) REFERENCES `donaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_donacion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `comentarios_publicacion`
--
ALTER TABLE `comentarios_publicacion`
  ADD CONSTRAINT `comentarios_publicacion_ibfk_1` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `comentarios_publicacion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `donaciones`
--
ALTER TABLE `donaciones`
  ADD CONSTRAINT `donaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `empleos`
--
ALTER TABLE `empleos`
  ADD CONSTRAINT `empleos_ibfk_1` FOREIGN KEY (`empresa_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `horarios`
--
ALTER TABLE `horarios`
  ADD CONSTRAINT `horarios_ibfk_1` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes_donacion`
--
ALTER TABLE `likes_donacion`
  ADD CONSTRAINT `likes_donacion_ibfk_1` FOREIGN KEY (`donacion_id`) REFERENCES `donaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_donacion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  ADD CONSTRAINT `likes_publicacion_ibfk_1` FOREIGN KEY (`publicacion_id`) REFERENCES `publicaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `likes_publicacion_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  ADD CONSTRAINT `postulaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `postulaciones_ibfk_2` FOREIGN KEY (`empleo_id`) REFERENCES `empleos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  ADD CONSTRAINT `publicaciones_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `servicios`
--
ALTER TABLE `servicios`
  ADD CONSTRAINT `servicios_ibfk_1` FOREIGN KEY (`profesional_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes_cita`
--
ALTER TABLE `solicitudes_cita`
  ADD CONSTRAINT `solicitudes_cita_ibfk_1` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_cita_ibfk_2` FOREIGN KEY (`horario_id`) REFERENCES `horarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_cita_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `solicitudes_donacion`
--
ALTER TABLE `solicitudes_donacion`
  ADD CONSTRAINT `solicitudes_donacion_ibfk_1` FOREIGN KEY (`donacion_id`) REFERENCES `donaciones` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `solicitudes_donacion_ibfk_2` FOREIGN KEY (`solicitante_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
