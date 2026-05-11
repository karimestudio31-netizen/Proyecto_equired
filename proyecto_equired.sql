-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 11-05-2026 a las 18:42:19
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
-- Estructura de tabla para la tabla `calificaciones`
--

CREATE TABLE `calificaciones` (
  `id` int(11) NOT NULL,
  `servicio_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `estrellas` tinyint(1) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `calificaciones`
--

INSERT INTO `calificaciones` (`id`, `servicio_id`, `usuario_id`, `estrellas`, `fecha`) VALUES
(1, 2, 1, 4, '2026-04-28 21:29:31'),
(2, 1, 1, 4, '2026-04-28 21:29:38'),
(3, 2, 2, 4, '2026-04-28 21:37:33'),
(4, 1, 2, 5, '2026-04-28 21:37:35'),
(11, 1, 3, 5, '2026-05-02 00:28:05');

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
(2, 1, 'Tarjetas para emprendimientos', 'especial para accesorios, 50 unidades', 'Montería', 'don_69e6e756f17ac.jpg', 0, 0.00, NULL, '2026-04-21 02:56:22'),
(3, 2, 'Pegatinas con frases motivacionales', '500 unidades', 'cereté', 'don_69e9f64892695.jpg', 0, 0.00, NULL, '2026-04-23 10:36:56');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `empleos`
--

CREATE TABLE `empleos` (
  `id` int(11) NOT NULL,
  `empresa_id` int(11) NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `descripcion` text NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `tipo` enum('Tiempo completo','Media jornada','Freelance') DEFAULT 'Tiempo completo',
  `ciudad` varchar(100) DEFAULT NULL,
  `salario` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `empleos`
--

INSERT INTO `empleos` (`id`, `empresa_id`, `titulo`, `descripcion`, `fecha`, `tipo`, `ciudad`, `salario`) VALUES
(3, 3, 'Atención al cliente', 'Atención de pedios', '2026-04-22 00:27:32', 'Freelance', 'Montería', '700.000'),
(4, 3, 'Administrador Página Web', 'Actualización constante de catalogo e inventario de la pagina web', '2026-05-02 00:39:38', 'Freelance', '', '2000000');

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

--
-- Volcado de datos para la tabla `horarios`
--

INSERT INTO `horarios` (`id`, `servicio_id`, `dia_hora`, `disponible`) VALUES
(1, 1, '2026-04-30 08:36:00', 0),
(2, 1, '2026-05-07 20:35:00', 0),
(3, 2, '2026-04-29 15:40:00', 1),
(4, 2, '2026-05-06 09:40:00', 1),
(5, 2, '2026-05-13 09:40:00', 1),
(6, 1, '2026-05-12 09:43:00', 1),
(7, 1, '2026-05-13 09:30:00', 1);

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
(3, 2, 2, '2026-04-21 03:11:58'),
(4, 3, 1, '2026-04-25 05:52:58'),
(5, 3, 4, '2026-04-28 21:33:25'),
(6, 3, 3, '2026-05-02 00:23:10'),
(7, 2, 3, '2026-05-02 00:23:18');

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

--
-- Volcado de datos para la tabla `likes_publicacion`
--

INSERT INTO `likes_publicacion` (`id`, `publicacion_id`, `usuario_id`, `fecha`) VALUES
(2, 2, 1, '2026-04-21 22:30:50'),
(3, 3, 1, '2026-04-23 10:18:52'),
(4, 1, 1, '2026-05-01 07:59:28'),
(5, 2, 3, '2026-05-02 00:21:15'),
(6, 1, 3, '2026-05-02 00:21:17'),
(7, 3, 3, '2026-05-02 00:24:11'),
(8, 1, 4, '2026-05-03 14:42:40'),
(9, 2, 4, '2026-05-03 14:42:42'),
(10, 3, 4, '2026-05-03 14:42:52');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mensajes`
--

CREATE TABLE `mensajes` (
  `id` int(11) NOT NULL,
  `emisor_id` int(11) NOT NULL,
  `receptor_id` int(11) NOT NULL,
  `mensaje` text DEFAULT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `leido` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `mensajes`
--

INSERT INTO `mensajes` (`id`, `emisor_id`, `receptor_id`, `mensaje`, `imagen`, `leido`, `fecha`) VALUES
(1, 1, 3, 'hola', NULL, 1, '2026-04-25 05:25:11'),
(2, 1, 3, '', 'msg_69ec505ec0ad6.png', 1, '2026-04-25 05:25:50'),
(3, 3, 1, 'holaaa', NULL, 1, '2026-04-25 05:26:37'),
(4, 3, 1, 'como estas?', NULL, 1, '2026-04-25 05:26:43'),
(5, 1, 2, 'hola', NULL, 0, '2026-04-25 05:52:07'),
(6, 1, 2, 'como estas?', NULL, 0, '2026-04-25 05:52:29'),
(7, 4, 1, 'Hola buenas noches karime, voy a cancelar la cita pendiente para que la re programes otro dia', NULL, 1, '2026-05-04 06:06:11');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` text NOT NULL,
  `leida` tinyint(1) DEFAULT 0,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `mensaje`, `leida`, `fecha`) VALUES
(1, 4, '⚠️ karime canceló una cita aceptada para el servicio \"Psicologa Ana Milena\". Motivo: \"Imprevisto de ultima hora\" El horario ya está disponible nuevamente.', 1, '2026-05-01 21:59:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `postulaciones`
--

CREATE TABLE `postulaciones` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `empleo_id` int(11) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp(),
  `estado` enum('pendiente','seleccionado_1','seleccionado_2','aceptado','proceso_finalizado','rechazado') DEFAULT 'pendiente',
  `hoja_vida` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `postulaciones`
--

INSERT INTO `postulaciones` (`id`, `usuario_id`, `empleo_id`, `fecha`, `estado`, `hoja_vida`) VALUES
(3, 1, 3, '2026-04-22 00:29:36', 'proceso_finalizado', 'hv_69e81670cb654.pdf'),
(4, 2, 3, '2026-05-02 00:34:35', 'proceso_finalizado', 'hv_69f5469b62caf.pdf'),
(5, 3, 4, '2026-05-02 03:16:56', 'rechazado', 'hv_69f56ca8c1f7b.pdf'),
(6, 1, 4, '2026-05-02 03:17:56', 'proceso_finalizado', 'hv_69f56ce4b37c4.pdf'),
(7, 2, 4, '2026-05-02 03:19:27', 'rechazado', 'hv_69f56d3f4a67a.pdf'),
(8, 4, 4, '2026-05-02 03:19:51', 'rechazado', 'hv_69f56d571c6f6.pdf');

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
(2, 2, 'La ODS 10: Reducción de las desigualdades nos invita a reflexionar sobre una realidad que muchas veces se normaliza: no todas las personas parten desde las mismas oportunidades. Mientras algunos tienen acceso a educación, empleo y servicios básicos, otros enfrentan barreras por su origen, condición económica, género o discapacidad. Esta desigualdad no solo limita el desarrollo individual, sino que también frena el progreso de toda la sociedad.\r\n\r\nReflexionar sobre este objetivo implica reconocer que la equidad no significa tratar a todos igual, sino brindar a cada persona lo que necesita para alcanzar su potencial. También nos lleva a cuestionar nuestras acciones cotidianas: cómo tratamos a los demás, qué oportunidades apoyamos y qué tipo de sociedad queremos construir. Promover la inclusión, la empatía y el respeto no es solo tarea de gobiernos o instituciones, sino de cada individuo.', NULL, '2026-04-19 23:34:09'),
(3, 3, 'Hola, este emprendimiento se encuentra presenta para ofrecer la ayuda y atención necesaria, ofreciendo empleos para quienes lo necesiten, dando prioridad a esta linda comunidad.', 'pub_69e9ee4640b36.jpeg', '2026-04-23 10:02:46');

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

--
-- Volcado de datos para la tabla `servicios`
--

INSERT INTO `servicios` (`id`, `profesional_id`, `nombre`, `especialidad`, `descripcion`, `tipo`, `fecha`) VALUES
(1, 4, 'Psicologa Ana Milena', 'Psicología Social y Comunitaria', 'Analizar como el entorno social influye en el comportamiento individual y grupal.', 'psicologica', '2026-04-22 00:36:15'),
(2, 4, 'Psicologa Ana Milena', 'Psicología clínica y de la salud', 'Diagnostico, tratamiento y prevención de trastornos mentales y problemas emocionales.', 'psicologica', '2026-04-22 00:40:14');

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

--
-- Volcado de datos para la tabla `solicitudes_cita`
--

INSERT INTO `solicitudes_cita` (`id`, `servicio_id`, `horario_id`, `nombre_solicitante`, `celular`, `cedula`, `edad`, `mensaje`, `estado`, `usuario_id`, `fecha`) VALUES
(1, 1, 1, 'karime', '311 123 4567', '1234567890', 23, 'problema familiar', 'rechazada', 1, '2026-04-22 00:42:21'),
(2, 2, 4, 'Jose', '311 123 34556', '0987654321', 25, '', 'rechazada', 2, '2026-04-28 21:38:23'),
(5, 1, 1, 'karime', '311 123 4567', '1234567890', 23, 'Problemas Laborales', 'rechazada', 1, '2026-05-01 15:14:48'),
(7, 2, 3, 'karime', '1234567890', '1234567809', 23, 'estrés', 'rechazada', 1, '2026-05-04 06:01:37'),
(8, 1, 1, 'karime', '1234567890', '1234567809', 23, '', 'pendiente', 1, '2026-05-04 06:08:58'),
(9, 1, 2, 'Jose', '1234567890', '1234567809', 34, '', 'pendiente', 2, '2026-05-04 06:10:12');

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
(1, 2, 2, 'xq tengo un emprendimiento de accesorios', '2026-04-21 03:13:00'),
(2, 3, 1, '...', '2026-04-23 10:38:06'),
(3, 3, 4, 'las necesito para regalar a mis pacientes', '2026-04-28 21:33:21'),
(4, 2, 4, 'yo las necesito', '2026-05-05 07:28:22');

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
(1, 'karime', 'karimestudio31@gmail.com', '$2y$10$Lr/bZ5PMdgKACy/UPEEpue9ERDp5BiFukP2XHzG7swwVsK/Ly0RK6', 'beneficiario', 'avatar_69e7f7b696349.jpeg', 'Estudiante de Ing. Sistemas en la UCC', '2026-04-19 19:23:56'),
(2, 'Jose', 'Jose123@gmail.com', '$2y$10$w8.jL68ZFeLCKGIw2peCeuaCpXRBece0V2aEfe.XVFKYaV8CXwp9S', 'beneficiario', NULL, NULL, '2026-04-19 23:31:40'),
(3, 'Accesorios de amor', 'accesoriosdeamor1@gmail.com', '$2y$10$2i3G2J9PMl3r2UvY.vCOb.pRzHzZWha.urE58BTPCUuzFdcaroC9u', 'empresa', 'avatar_69e812ad518e1.jpeg', 'Tienda Online de accesorios en Monteria', '2026-04-19 23:37:25'),
(4, 'Psicologa Ana Milena', 'psicologa@gmail.com', '$2y$10$0ioHXUw95FETP3IaEHY4au.WkkbbR/mFaIPCNpmsNXFzrBaf2mvHi', 'profesional', 'avatar_69f5267e645dd.jpg', 'Egresada de la universidad Cooperativa', '2026-04-21 13:33:44');

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
-- Indices de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unica_calificacion` (`servicio_id`,`usuario_id`),
  ADD KEY `cal_ibfk_2` (`usuario_id`);

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
-- Indices de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `emisor_id` (`emisor_id`),
  ADD KEY `receptor_id` (`receptor_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
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
-- AUTO_INCREMENT de la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

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
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `empleos`
--
ALTER TABLE `empleos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `horarios`
--
ALTER TABLE `horarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `likes_donacion`
--
ALTER TABLE `likes_donacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `likes_publicacion`
--
ALTER TABLE `likes_publicacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `mensajes`
--
ALTER TABLE `mensajes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de la tabla `postulaciones`
--
ALTER TABLE `postulaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `publicaciones`
--
ALTER TABLE `publicaciones`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de la tabla `servicios`
--
ALTER TABLE `servicios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `solicitudes_cita`
--
ALTER TABLE `solicitudes_cita`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT de la tabla `solicitudes_donacion`
--
ALTER TABLE `solicitudes_donacion`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

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
-- Filtros para la tabla `calificaciones`
--
ALTER TABLE `calificaciones`
  ADD CONSTRAINT `cal_ibfk_1` FOREIGN KEY (`servicio_id`) REFERENCES `servicios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `cal_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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
-- Filtros para la tabla `mensajes`
--
ALTER TABLE `mensajes`
  ADD CONSTRAINT `mensajes_ibfk_1` FOREIGN KEY (`emisor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `mensajes_ibfk_2` FOREIGN KEY (`receptor_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notif_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

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
