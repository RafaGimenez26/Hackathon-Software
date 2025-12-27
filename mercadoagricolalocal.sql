-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 27-12-2025 a las 16:53:23
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
-- Base de datos: `mercadoagricolalocal`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogodias`
--

CREATE TABLE `catalogodias` (
  `DiaID` int(11) NOT NULL,
  `NombreDia` varchar(15) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `catalogodias`
--

INSERT INTO `catalogodias` (`DiaID`, `NombreDia`) VALUES
(7, 'Domingo'),
(4, 'Jueves'),
(1, 'Lunes'),
(2, 'Martes'),
(3, 'Miércoles'),
(6, 'Sábado'),
(5, 'Viernes');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogometodospago`
--

CREATE TABLE `catalogometodospago` (
  `MetodoPagoID` int(11) NOT NULL,
  `NombreMetodo` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `catalogometodospago`
--

INSERT INTO `catalogometodospago` (`MetodoPagoID`, `NombreMetodo`) VALUES
(1, 'Efectivo'),
(3, 'MercadoPago'),
(4, 'Tarjetas'),
(5, 'Todos los métodos de pago'),
(2, 'Transferencia');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `catalogozonas`
--

CREATE TABLE `catalogozonas` (
  `ZonaID` int(11) NOT NULL,
  `NombreZona` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `catalogozonas`
--

INSERT INTO `catalogozonas` (`ZonaID`, `NombreZona`) VALUES
(6, 'Centro'),
(3, 'Itaembe Mini'),
(4, 'Santa Rita'),
(7, 'Todas las zonas'),
(2, 'Villa Cabello'),
(1, 'Villa Sarita'),
(5, 'Villa Urquiza');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `diasdisponibilidad`
--

CREATE TABLE `diasdisponibilidad` (
  `ProductorID` int(11) NOT NULL,
  `DiaID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `diasdisponibilidad`
--

INSERT INTO `diasdisponibilidad` (`ProductorID`, `DiaID`) VALUES
(0, 1),
(0, 2),
(0, 3),
(0, 4),
(0, 5),
(1, 1),
(1, 2),
(1, 3),
(1, 4),
(1, 5),
(1, 6),
(4, 3),
(4, 6),
(4, 7),
(5, 1),
(5, 2),
(5, 3),
(5, 4),
(5, 5),
(5, 6),
(5, 7),
(7, 1),
(7, 2),
(7, 3),
(7, 4),
(7, 5),
(7, 6),
(8, 1),
(8, 4),
(8, 5),
(8, 6),
(15, 1),
(15, 2),
(15, 3),
(15, 4),
(15, 5),
(15, 6),
(18, 1),
(18, 2),
(18, 3),
(18, 4),
(18, 5),
(18, 6),
(19, 1),
(19, 2),
(19, 3),
(19, 4),
(19, 5),
(19, 6);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodospagoaceptados`
--

CREATE TABLE `metodospagoaceptados` (
  `ProductorID` int(11) NOT NULL,
  `MetodoPagoID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `metodospagoaceptados`
--

INSERT INTO `metodospagoaceptados` (`ProductorID`, `MetodoPagoID`) VALUES
(0, 5),
(1, 5),
(4, 5),
(5, 5),
(7, 5),
(8, 5),
(15, 5),
(18, 5),
(19, 5);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productores`
--

CREATE TABLE `productores` (
  `ProductorID` int(11) NOT NULL,
  `NombreRazonSocial` varchar(255) NOT NULL,
  `CorreoElectronico` varchar(100) NOT NULL,
  `PasswordHash` varchar(255) NOT NULL,
  `TelefonoContacto` varchar(30) DEFAULT NULL,
  `CUIT_CUIL` varchar(15) DEFAULT NULL,
  `DireccionEstablecimiento` text DEFAULT NULL,
  `TipoProduccionPrincipalID` int(11) NOT NULL,
  `Certificaciones` varchar(255) DEFAULT NULL,
  `TamanoHectareas` decimal(10,2) DEFAULT NULL,
  `RangoEmpleados` varchar(50) DEFAULT NULL,
  `HorarioAtencionDesde` time DEFAULT NULL,
  `HorarioAtencionHasta` time DEFAULT NULL,
  `DescripcionProduccion` text DEFAULT NULL,
  `FechaRegistro` timestamp NOT NULL DEFAULT current_timestamp(),
  `Activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productores`
--

INSERT INTO `productores` (`ProductorID`, `NombreRazonSocial`, `CorreoElectronico`, `PasswordHash`, `TelefonoContacto`, `CUIT_CUIL`, `DireccionEstablecimiento`, `TipoProduccionPrincipalID`, `Certificaciones`, `TamanoHectareas`, `RangoEmpleados`, `HorarioAtencionDesde`, `HorarioAtencionHasta`, `DescripcionProduccion`, `FechaRegistro`, `Activo`) VALUES
(1, 'Don Fresco Huevos', 'themartinpro43@gmail.com', '$2y$10$hAbQwnOzONqG8Vy9SjaeVuCrfNfHyUiIPzWoZuEL5L5qXCkgHR77K', '+543764399836', '21430717481', 'Barrio Los Lapachos Mz \"A\" 11', 3, 'SENASA', 2.50, '', '08:00:00', '18:00:00', '', '2025-10-07 00:11:38', 1),
(3, 'Granja San Isidro', 'sanisidro@gmail.com', '$2y$10$3tBYp4XJelG4hj2tr2mnZ.kigY1ad0GrYlZnbYdWoAF/eUeg7zD.e', '3764098762', '20-34876678-9', 'Av los Lapachos 789', 5, NULL, NULL, NULL, '08:00:00', '12:00:00', 'Productos Lacteos y derivados de la carne', '2025-11-18 11:16:46', 1),
(7, 'maria fernendez', 'mf@gmial.com', '$2y$10$VfUb0HHV4VOUChbMZP3mruLc4rUURRhKpFMOlecGEpYbSSbQTievW', '', '23457894560', '', 4, NULL, NULL, NULL, '08:00:00', '18:00:00', '', '2025-11-18 11:16:47', 1),
(8, 'Don Fresco Huevos', 'nicolaspayes2@gmail.com', '$2y$10$82JGZ1ovskWz6zIc/EApLeq4Kz/6KtW2638oOtToOyNcfEPweV4nG', '3764568923', '214589673221', '', 1, NULL, NULL, NULL, '08:00:00', '18:00:00', '', '2025-11-18 11:16:47', 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposproduccion`
--

CREATE TABLE `tiposproduccion` (
  `TipoProduccionID` int(11) NOT NULL,
  `NombreTipo` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `tiposproduccion`
--

INSERT INTO `tiposproduccion` (`TipoProduccionID`, `NombreTipo`) VALUES
(6, 'Carnes y derivados'),
(4, 'Cereales y legumbres'),
(3, 'Frutas de estación'),
(2, 'Hortalizas (tomate, pimiento, cebolla)'),
(7, 'Producción mixta'),
(5, 'Productos lácteos'),
(1, 'Verduras de hoja (lechuga, espinaca, acelga)');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(10) UNSIGNED NOT NULL,
  `nombre_usuario` varchar(50) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `contrasena` varchar(255) NOT NULL,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre_usuario`, `correo`, `telefono`, `contrasena`, `fecha_registro`) VALUES
(1, 'Rafael Gimenez', 'gimenez55@gmail.com', '3764565443', '$2y$10$qOwfIyMUWBAoNhNmd9BZqeGN9XvNJt0hWjfLc6APx0v5.vBXzxNSW', '2025-10-11 22:51:27'),
(2, 'ponce_agustin', 'ap@gmail.com', '3764855585', '$2y$10$cxPjKKEHZUJdrBDVih8zW.phsNhsjbJlwPsF4/8e7L7ZjWHg2owoC', '2025-10-11 23:04:14');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `zonasdistribucion`
--

CREATE TABLE `zonasdistribucion` (
  `ProductorID` int(11) NOT NULL,
  `ZonaID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `zonasdistribucion`
--

INSERT INTO `zonasdistribucion` (`ProductorID`, `ZonaID`) VALUES
(7, 7);

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `catalogodias`
--
ALTER TABLE `catalogodias`
  ADD PRIMARY KEY (`DiaID`),
  ADD UNIQUE KEY `NombreDia` (`NombreDia`);

--
-- Indices de la tabla `catalogometodospago`
--
ALTER TABLE `catalogometodospago`
  ADD PRIMARY KEY (`MetodoPagoID`),
  ADD UNIQUE KEY `NombreMetodo` (`NombreMetodo`);

--
-- Indices de la tabla `catalogozonas`
--
ALTER TABLE `catalogozonas`
  ADD PRIMARY KEY (`ZonaID`),
  ADD UNIQUE KEY `NombreZona` (`NombreZona`);

--
-- Indices de la tabla `diasdisponibilidad`
--
ALTER TABLE `diasdisponibilidad`
  ADD PRIMARY KEY (`ProductorID`,`DiaID`),
  ADD KEY `DiaID` (`DiaID`);

--
-- Indices de la tabla `metodospagoaceptados`
--
ALTER TABLE `metodospagoaceptados`
  ADD PRIMARY KEY (`ProductorID`,`MetodoPagoID`),
  ADD KEY `MetodoPagoID` (`MetodoPagoID`);

--
-- Indices de la tabla `productores`
--
ALTER TABLE `productores`
  ADD PRIMARY KEY (`ProductorID`),
  ADD UNIQUE KEY `CorreoElectronico` (`CorreoElectronico`),
  ADD UNIQUE KEY `CUIT_CUIL` (`CUIT_CUIL`),
  ADD KEY `fk_tipo_produccion` (`TipoProduccionPrincipalID`);

--
-- Indices de la tabla `tiposproduccion`
--
ALTER TABLE `tiposproduccion`
  ADD PRIMARY KEY (`TipoProduccionID`),
  ADD UNIQUE KEY `NombreTipo` (`NombreTipo`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `zonasdistribucion`
--
ALTER TABLE `zonasdistribucion`
  ADD PRIMARY KEY (`ProductorID`,`ZonaID`),
  ADD KEY `ZonaID` (`ZonaID`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `catalogometodospago`
--
ALTER TABLE `catalogometodospago`
  MODIFY `MetodoPagoID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `catalogozonas`
--
ALTER TABLE `catalogozonas`
  MODIFY `ZonaID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `productores`
--
ALTER TABLE `productores`
  MODIFY `ProductorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `tiposproduccion`
--
ALTER TABLE `tiposproduccion`
  MODIFY `TipoProduccionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `productores`
--
ALTER TABLE `productores`
  ADD CONSTRAINT `fk_tipo_produccion` FOREIGN KEY (`TipoProduccionPrincipalID`) REFERENCES `tiposproduccion` (`TipoProduccionID`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `zonasdistribucion`
--
ALTER TABLE `zonasdistribucion`
  ADD CONSTRAINT `fk_zonas_productor` FOREIGN KEY (`ProductorID`) REFERENCES `productores` (`ProductorID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_zonas_zona` FOREIGN KEY (`ZonaID`) REFERENCES `catalogozonas` (`ZonaID`) ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
