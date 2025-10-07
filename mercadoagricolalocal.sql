-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 07-10-2025 a las 03:07:05
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

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `metodospagoaceptados`
--

CREATE TABLE `metodospagoaceptados` (
  `ProductorID` int(11) NOT NULL,
  `MetodoPagoID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `TipoProduccionPrincipalID` int(11) DEFAULT NULL,
  `Certificaciones` varchar(255) DEFAULT NULL,
  `TamanoHectareas` decimal(10,2) DEFAULT NULL,
  `RangoEmpleados` varchar(50) DEFAULT NULL,
  `HorarioAtencionDesde` time DEFAULT NULL,
  `HorarioAtencionHasta` time DEFAULT NULL,
  `DescripcionProduccion` text DEFAULT NULL,
  `FechaRegistro` timestamp NOT NULL DEFAULT current_timestamp(),
  `Activo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productores`
--

INSERT INTO `productores` (`ProductorID`, `NombreRazonSocial`, `CorreoElectronico`, `PasswordHash`, `TelefonoContacto`, `CUIT_CUIL`, `DireccionEstablecimiento`, `TipoProduccionPrincipalID`, `Certificaciones`, `TamanoHectareas`, `RangoEmpleados`, `HorarioAtencionDesde`, `HorarioAtencionHasta`, `DescripcionProduccion`, `FechaRegistro`, `Activo`) VALUES
(1, 'Don Fresco Huevos', 'themartinpro43@gmail.com', '$2y$10$hAbQwnOzONqG8Vy9SjaeVuCrfNfHyUiIPzWoZuEL5L5qXCkgHR77K', '+543764399836', '21430717481', 'Barrio Los Lapachos Mz \"A\" 11', 3, 'SENASA', 2.50, '', '08:00:00', '18:00:00', '', '2025-10-07 00:11:38', 1);

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
-- Estructura de tabla para la tabla `zonasdistribucion`
--

CREATE TABLE `zonasdistribucion` (
  `ProductorID` int(11) NOT NULL,
  `ZonaID` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  MODIFY `ProductorID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `tiposproduccion`
--
ALTER TABLE `tiposproduccion`
  MODIFY `TipoProduccionID` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `diasdisponibilidad`
--
ALTER TABLE `diasdisponibilidad`
  ADD CONSTRAINT `diasdisponibilidad_ibfk_1` FOREIGN KEY (`ProductorID`) REFERENCES `productores` (`ProductorID`) ON DELETE CASCADE,
  ADD CONSTRAINT `diasdisponibilidad_ibfk_2` FOREIGN KEY (`DiaID`) REFERENCES `catalogodias` (`DiaID`);

--
-- Filtros para la tabla `metodospagoaceptados`
--
ALTER TABLE `metodospagoaceptados`
  ADD CONSTRAINT `metodospagoaceptados_ibfk_1` FOREIGN KEY (`ProductorID`) REFERENCES `productores` (`ProductorID`) ON DELETE CASCADE,
  ADD CONSTRAINT `metodospagoaceptados_ibfk_2` FOREIGN KEY (`MetodoPagoID`) REFERENCES `catalogometodospago` (`MetodoPagoID`);

--
-- Filtros para la tabla `productores`
--
ALTER TABLE `productores`
  ADD CONSTRAINT `fk_tipo_produccion` FOREIGN KEY (`TipoProduccionPrincipalID`) REFERENCES `tiposproduccion` (`TipoProduccionID`);

--
-- Filtros para la tabla `zonasdistribucion`
--
ALTER TABLE `zonasdistribucion`
  ADD CONSTRAINT `zonasdistribucion_ibfk_1` FOREIGN KEY (`ProductorID`) REFERENCES `productores` (`ProductorID`) ON DELETE CASCADE,
  ADD CONSTRAINT `zonasdistribucion_ibfk_2` FOREIGN KEY (`ZonaID`) REFERENCES `catalogozonas` (`ZonaID`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
