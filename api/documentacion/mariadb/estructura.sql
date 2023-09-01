-- phpMyAdmin SQL Dump
-- version 5.1.1deb5ubuntu1
-- https://www.phpmyadmin.net/
--
-- Servidor: localhost
-- Tiempo de generación: 28-06-2023 a las 10:14:11
-- Versión del servidor: 10.6.12-MariaDB-0ubuntu0.22.04.1
-- Versión de PHP: 8.1.2-1ubuntu2.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `orfeong_db`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgActividadEconomicaPqrs`
--

CREATE TABLE `cgActividadEconomicaPqrs` (
  `idCgActividadEconomicaPqrs` int(11) NOT NULL,
  `nombreCgActividadEconomicaPqrs` varchar(250) NOT NULL,
  `estadoCgActividadEconomicaPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgActividadEconomicaPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgClasificacionPqrs`
--

CREATE TABLE `cgClasificacionPqrs` (
  `idCgClasificacionPqrs` int(11) NOT NULL,
  `nombreCgClasificacionPqrs` varchar(80) NOT NULL,
  `creacionCgClasificacionPqrs` datetime NOT NULL DEFAULT current_timestamp(),
  `estadoCgClasificacionPqrs` int(11) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgCondicionDiscapacidadPqrs`
--

CREATE TABLE `cgCondicionDiscapacidadPqrs` (
  `idCgCondicionDiscapacidadPqrs` int(11) NOT NULL,
  `nombreCgCondicionDiscapacidadPqrs` varchar(250) NOT NULL COMMENT 'nombre de la condición de discapacidad asociado al cliente',
  `estadoCgCondicionDiscapacidadPqrs` int(11) NOT NULL DEFAULT 10 COMMENT 'estado activo = 10, inactivo = 0',
  `creacionCgCondicionDiscapacidadPqrs` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha en la que se crea la condición de discapacidad'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgConsecutivosRadicados`
--

CREATE TABLE `cgConsecutivosRadicados` (
  `idCgConsecutivoRadicado` int(11) NOT NULL COMMENT 'Clave primaria de la tabla cgConsecutivosRadicados',
  `idCgTipoRadicado` int(11) DEFAULT NULL COMMENT 'Identificador único para la tabla cgTiposRadicados',
  `idCgRegional` int(11) DEFAULT NULL COMMENT 'Identificador único para la tabla cgReionales',
  `anioCgConsecutivoRadicado` int(11) NOT NULL COMMENT 'Año del consecutivo',
  `cgConsecutivoRadicado` int(11) NOT NULL COMMENT 'Consecutivo de la combinación del radicado',
  `creacionCgConsecutivoRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en que se creó el consecutivo',
  `estadoCgConsecutivoRadicado` int(11) NOT NULL DEFAULT 10 COMMENT '10 activo, 0 Inactivo',
  `isTemporal` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si es un consecutivo de radicado temporal 0: false, 1: true'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla que almacena los consecutivos de los radicados segun la configuración establecida';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgDiasNoLaborados`
--

CREATE TABLE `cgDiasNoLaborados` (
  `idCgDiaNoLaborado` int(11) NOT NULL COMMENT 'Identificador único del día no laborado',
  `fechaCgDiaNoLaborado` date NOT NULL COMMENT 'Fecha del día no laborado',
  `estadoCgDiaNoLaborado` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del  del día no laborado 10 activo 0 Inactivo',
  `creacionCgDiaNoLaborado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del expediente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEncuestaPreguntas`
--

CREATE TABLE `cgEncuestaPreguntas` (
  `idCgEncuestaPregunta` int(11) NOT NULL COMMENT 'Identificador único de la tabla cgEncuestaPreguntas',
  `idCgEncuesta` int(11) NOT NULL COMMENT 'Identificador único de la tabla cgEncuestas',
  `preguntaCgEncuestaPregunta` text NOT NULL COMMENT 'Pregunta de la encuesta',
  `creacionEncuestaPregunta` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Estado de la pregunta de la encuesta 10 activo 0 Inactivo',
  `estadoEncuestaPregunta` int(11) NOT NULL DEFAULT 10 COMMENT 'Fecha de creación de la pregunta de la encuesta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEncuestas`
--

CREATE TABLE `cgEncuestas` (
  `idCgEncuesta` int(11) NOT NULL COMMENT 'Identificador único de la tabla cgEncuestas',
  `nombreCgEncuesta` varchar(80) NOT NULL COMMENT 'Nombre de la encuesta',
  `creacionCgEncuesta` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación la encuesta',
  `estadoCgEncuesta` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la encuesta del expediente 10 activo 0 Inactivo',
  `idUserCreador` int(11) NOT NULL COMMENT 'Identificador de usuario creador'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEnvioServicios`
--

CREATE TABLE `cgEnvioServicios` (
  `idCgEnvioServicio` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `nombreCgEnvioServicio` varchar(255) NOT NULL DEFAULT '' COMMENT 'Nombre del envió del servicio',
  `estadoCgEnvioServicio` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de servicios 0 inactivo 10 activo',
  `creacionCgEnvioServicio` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del servicio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEscolaridadPqrs`
--

CREATE TABLE `cgEscolaridadPqrs` (
  `idCgEscolaridadPqrs` int(11) NOT NULL,
  `nombreCgEscolaridadPqrs` varchar(250) NOT NULL,
  `estadoCgEscolaridadPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgEscolaridadPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEstratoPqrs`
--

CREATE TABLE `cgEstratoPqrs` (
  `idCgEstratoPqrs` int(11) NOT NULL,
  `nombreCgEstratoPqrs` varchar(250) NOT NULL,
  `estadoCgEstratoPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgEstratoPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgEtiquetaRadicacion`
--

CREATE TABLE `cgEtiquetaRadicacion` (
  `idCgEtiquetaRadicacion` int(11) NOT NULL COMMENT 'Id de la configuración de etiqueta radicación',
  `etiquetaCgEtiquetaRadicacion` varchar(80) NOT NULL COMMENT 'Nombre de etiqueta',
  `estadoCgEtiquetaRadicacion` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de etiqueta',
  `creacionCgEtiquetaRadicacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación de la etiqueta',
  `descripcionCgEtiquetaRadicacion` varchar(255) NOT NULL COMMENT 'Descripción de la variable etiqueta'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgFirmasDocs`
--

CREATE TABLE `cgFirmasDocs` (
  `idCgFirmaDoc` int(11) NOT NULL COMMENT 'Número unico de la tabla',
  `nombreCgFirmaDoc` varchar(20) NOT NULL COMMENT 'Nombre firma',
  `estadoCgFirmaDoc` int(11) NOT NULL DEFAULT 0 COMMENT 'Estado firma',
  `creacionCgFirmaDoc` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación firma'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgFormulariosPqrs`
--

CREATE TABLE `cgFormulariosPqrs` (
  `idCgFormulariosPqrs` int(11) NOT NULL,
  `nombreCgFormulariosPqrs` varchar(150) NOT NULL,
  `estadoCgFormulariosPqrs` smallint(6) NOT NULL DEFAULT 10,
  `creacionCgFormulariosPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgFormulariosPqrsDetalle`
--

CREATE TABLE `cgFormulariosPqrsDetalle` (
  `idCgFormulariosPqrsDetalle` int(11) NOT NULL,
  `idCgFormulariosPqrs` int(11) NOT NULL,
  `nombreCgFormulariosPqrsDetalle` varchar(80) NOT NULL,
  `descripcionCgFormulariosPqrsDetalle` text NOT NULL,
  `adjuntarDocsCgFormulariosPqrsDetalle` text NOT NULL,
  `terminosCgFormulariosPqrsDetalle` text NOT NULL,
  `datosSelectorPrestacionCgFormulariosPqrsDetalle` varchar(80) NOT NULL,
  `datosSelectorBeneficiarioCgFormulariosPqrsDetalle` varchar(80) NOT NULL,
  `activarBeneficiarioCgFormulariosPqrsDetalle` varchar(500) NOT NULL,
  `idTipoDocumentalCgFormulariosPqrsDetalle` int(11) NOT NULL,
  `idSerieCgFormulariosPqrsDetalle` int(11) NOT NULL,
  `idSubserieCgFormulariosPqrsDetalle` int(11) NOT NULL,
  `estadoCgFormulariosPqrsDetalle` smallint(6) NOT NULL DEFAULT 10,
  `creacionCgFormulariosPqrsDetalle` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgFormulariosPqrsDetalleDocumentos`
--

CREATE TABLE `cgFormulariosPqrsDetalleDocumentos` (
  `idCgFormulariosPqrsDetalleDocumentos` int(11) NOT NULL,
  `idCgFormulariosPqrsDetalle` int(11) NOT NULL,
  `combiCgFormulariosPqrsDetalleDocumentos` varchar(500) NOT NULL,
  `nameFileCgFormulariosPqrsDetalleDocumentos` varchar(100) NOT NULL,
  `nombreCgFormulariosPqrsDetalleDocumentos` varchar(500) NOT NULL,
  `descripcionCgFormulariosPqrsDetalleDocumentos` text NOT NULL,
  `requeridoCgFormulariosPqrsDetalleDocumentos` smallint(6) NOT NULL DEFAULT 10,
  `estadoCgFormulariosPqrsDetalleDocumentos` smallint(6) NOT NULL DEFAULT 10,
  `creacionCgFormulariosPqrsDetalleDocumentos` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGeneral`
--

CREATE TABLE `cgGeneral` (
  `idCgGeneral` int(11) NOT NULL COMMENT 'Id configuración general',
  `tamanoArchivoCgGeneral` varchar(20) NOT NULL COMMENT 'Tamaño del archivo',
  `diasLimiteCgGeneral` int(11) NOT NULL COMMENT 'Días límite de cambio contraseña',
  `correoNotificadorAdminCgGeneral` varchar(80) NOT NULL COMMENT 'Correo de administrador de transferencias',
  `correoNotificadorPqrsCgGeneral` varchar(80) NOT NULL COMMENT 'Correo de administrador Pqrs',
  `diasNotificacionCgGeneral` int(11) NOT NULL COMMENT 'Días de notificación',
  `terminoCondicionCgGeneral` text NOT NULL COMMENT 'Términos y condiciones',
  `diaRespuestaPqrsCgGeneral` int(11) NOT NULL COMMENT 'Días de respuesta del ciudadano Pqrs',
  `idDependenciaPqrsCgGeneral` int(11) NOT NULL COMMENT 'Id dependencia del usuario Pqrs',
  `estadoCgGeneral` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado configuración general',
  `creacionCgGeneral` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación',
  `tiempoInactividadCgGeneral` int(11) DEFAULT 10 COMMENT 'Tiempo de inactividad de sesión en minutos',
  `iniConsClienteCgGeneral` varchar(15) NOT NULL DEFAULT '4000001251' COMMENT 'Consecutivo donde va a iniciar el nit de los clientes (remitentes) en caso de no tener el dato. inicia en 4000001251, por el cliente',
  `resolucionesCgGeneral` tinyint(1) NOT NULL DEFAULT 0,
  `resolucionesIdCgGeneral` smallint(6) NOT NULL,
  `resolucionesNameCgGeneral` varchar(80) NOT NULL,
  `codDepePrestaEconomicasCgGeneral` varchar(6) NOT NULL,
  `idPrestaPazYsalvoCgGeneral` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGeneralBasesDatos`
--

CREATE TABLE `cgGeneralBasesDatos` (
  `idCgGeneralBasesDatos` int(11) NOT NULL,
  `nombreCgGeneralBasesDatos` varchar(80) NOT NULL,
  `dnsCgGeneralBasesDatos` varchar(10) NOT NULL,
  `hostCgGeneralBasesDatos` varchar(20) NOT NULL,
  `portCgGeneralBasesDatos` varchar(20) NOT NULL,
  `dbnameCgGeneralBasesDatos` varchar(20) NOT NULL,
  `usernameCgGeneralBasesDatos` varchar(80) NOT NULL,
  `passCgGeneralBasesDatos` varchar(80) NOT NULL,
  `creacionCgGeneralBasesDatos` datetime NOT NULL DEFAULT current_timestamp(),
  `estadoCgGeneralBasesDatos` smallint(6) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGeneroPqrs`
--

CREATE TABLE `cgGeneroPqrs` (
  `idCgGeneroPqrs` int(11) NOT NULL,
  `nombreCgGeneroPqrs` varchar(250) NOT NULL,
  `estadoCgGeneroPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgGeneroPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGrupoEtnicoPqrs`
--

CREATE TABLE `cgGrupoEtnicoPqrs` (
  `idCgGrupoEtnicoPqrs` int(11) NOT NULL,
  `nombreCgGrupoEtnicoPqrs` varchar(250) NOT NULL,
  `estadoCgGrupoEtnicoPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgGrupoEtnicoPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGrupoInteresPqrs`
--

CREATE TABLE `cgGrupoInteresPqrs` (
  `idCgGrupoInteresPqrs` int(11) NOT NULL,
  `nombreCgGrupoInteresPqrs` varchar(250) NOT NULL COMMENT 'Nombre del grupo de interes asociado al radicado',
  `creacionCgGrupoInteresPqrs` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del grupo de interes',
  `estadoCgGrupoInteresPqrs` int(10) NOT NULL DEFAULT 10 COMMENT 'Estado activo =10, inactivo 0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGrupoSisbenPqrs`
--

CREATE TABLE `cgGrupoSisbenPqrs` (
  `idCgGrupoSisbenPqrs` int(11) NOT NULL,
  `nombreCgGrupoSisbenPqrs` varchar(250) NOT NULL,
  `estadoCgGrupoSisbenPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgGrupoSisbenPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgGruposUsuarios`
--

CREATE TABLE `cgGruposUsuarios` (
  `idCgGrupoUsuarios` int(11) NOT NULL,
  `nombreCgGrupoUsuarios` varchar(80) NOT NULL,
  `estadoCgGrupoUsuarios` int(11) NOT NULL DEFAULT 10,
  `creacionCgGrupoUsuarios` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgHorarioLaboral`
--

CREATE TABLE `cgHorarioLaboral` (
  `idCgHorarioLaboral` int(11) NOT NULL,
  `diaInicioCgHorarioLaboral` int(11) NOT NULL COMMENT 'Representación numérica del día de la semana 0 (para domingo) hasta 6 (para sábado)',
  `diaFinCgHorarioLaboral` int(11) NOT NULL COMMENT 'Representación numérica del día de la semana 0 (para domingo) hasta 6 (para sábado)',
  `horaInicioCgHorarioLaboral` varchar(10) NOT NULL COMMENT 'Formato de 24hr',
  `horaFinCgHorarioLaboral` varchar(10) NOT NULL COMMENT 'Formato de 24hr',
  `estadoCgHorarioLaboral` int(11) NOT NULL COMMENT 'estado 10 para habilitado 0 para inhabilitado',
  `fechaCgHorarioLaboral` datetime NOT NULL COMMENT 'fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='configuración tiempos de respuesta ';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgMediosRecepcion`
--

CREATE TABLE `cgMediosRecepcion` (
  `idCgMedioRecepcion` int(11) NOT NULL COMMENT 'identificación de la tabla este valor es unico',
  `nombreCgMedioRecepcion` varchar(45) NOT NULL COMMENT 'nombre del medio de recepción para el radicado que se recibe',
  `estadoCgMedioRecepcion` int(11) NOT NULL DEFAULT 10 COMMENT 'esto del medio de recepción 10: activo o 0: inactivo',
  `creacionCgMedioRecepcion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha de creación del medio de recepción del radicado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgMotivosDevolucion`
--

CREATE TABLE `cgMotivosDevolucion` (
  `idCgMotivoDevolucion` int(11) NOT NULL COMMENT 'Número único de la tabla de motivos',
  `nombreCgMotivoDevolucion` varchar(80) NOT NULL COMMENT 'Nombre de los motivos de devolución',
  `estadoCgMotivoDevolucion` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de motivos de devolución 0 inactivo 10 activo',
  `creacionCgMotivoDevolucion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del motivo de devolución'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgNumeroRadicado`
--

CREATE TABLE `cgNumeroRadicado` (
  `idCgNumeroRadicado` int(11) NOT NULL COMMENT 'Indentificador único de la tabla cgNumeroRadicado',
  `estructuraCgNumeroRadicado` varchar(255) NOT NULL COMMENT 'Estructura que se sigue para crearse el número de radicado',
  `longitudDependenciaCgNumeroRadicado` int(11) NOT NULL COMMENT 'Longitud de la dependencia',
  `longitudConsecutivoCgNumeroRadicado` int(11) NOT NULL COMMENT 'Longitud del consecutivo',
  `estadoCgNumeroRadicado` int(11) NOT NULL DEFAULT 10 COMMENT '0: Inactivo - 10: Activo',
  `creacionCgNumeroRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgPlantillas`
--

CREATE TABLE `cgPlantillas` (
  `idCgPlantilla` int(11) NOT NULL,
  `idUser` int(11) NOT NULL COMMENT 'Identificador del usuario que cargo el documento',
  `nombreCgPlantilla` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nombre que se le da al documento que se carga ',
  `rutaCgPlantilla` varchar(255) NOT NULL COMMENT 'Ruta donde se encuentra el documento ',
  `extencionCgPlantilla` varchar(20) NOT NULL COMMENT 'Guarda la extención del documento que se esta cargando',
  `estadoCgPlantilla` int(11) NOT NULL DEFAULT 10 COMMENT '	Estado que se le aplica al documento',
  `creacionCgPlantilla` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en la que se crea el documento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla Gestión de Plantillas ';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgPlantillaVariables`
--

CREATE TABLE `cgPlantillaVariables` (
  `idCgPlantillaVariable` int(11) NOT NULL,
  `nombreCgPlantillaVariable` varchar(80) NOT NULL COMMENT 'nombre de la variable de la plantilla',
  `descripcionCgPlantillaVariable` varchar(80) NOT NULL,
  `estadoCgPlantillaVariable` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado que se le aplica  por defecto 10 activo',
  `creacionCgPlantillaVariable` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Tabla de configuración de plantillas';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgProveedores`
--

CREATE TABLE `cgProveedores` (
  `idCgProveedor` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `nombreCgProveedor` varchar(80) NOT NULL DEFAULT '' COMMENT 'Nombre de la empresa que hace el envio de correspondencia',
  `estadoCgProveedor` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del proveedor  10 activo 0 Inactivo',
  `creacionCgProveedor` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del proveedor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgProveedoresExternos`
--

CREATE TABLE `cgProveedoresExternos` (
  `idCgProveedorExterno` int(11) NOT NULL,
  `nombreCgProveedorExterno` varchar(50) NOT NULL,
  `tokenCgProveedorExterno` varchar(255) NOT NULL,
  `userCgCreadorProveedorExterno` int(11) NOT NULL,
  `userCgProveedorExterno` int(11) NOT NULL,
  `creacionCgProveedorExterno` date NOT NULL,
  `estadoCgProveedorExterno` smallint(6) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgProveedoresRegional`
--

CREATE TABLE `cgProveedoresRegional` (
  `idCgProveedorRegional` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `idCgRegional` int(11) NOT NULL COMMENT 'Id de la Regional',
  `idCgProveedor` int(11) NOT NULL COMMENT 'Id del Proveedor'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgProveedoresServicios`
--

CREATE TABLE `cgProveedoresServicios` (
  `idCgProveedoresServicios` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `idCgProveedor` int(11) NOT NULL COMMENT 'Id del Proveedor',
  `idCgEnvioServicios` int(11) NOT NULL COMMENT 'Id del envío servicio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgRangoEdadPqrs`
--

CREATE TABLE `cgRangoEdadPqrs` (
  `idCgRangoEdadPqrs` int(11) NOT NULL,
  `nombreCgRangoEdadPqrs` varchar(250) NOT NULL,
  `estadoCgRangoEdadPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgRangoEdadPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgRegionales`
--

CREATE TABLE `cgRegionales` (
  `idCgRegional` int(11) NOT NULL COMMENT 'Identificador de la tabla',
  `nombreCgRegional` varchar(80) NOT NULL COMMENT 'Nombre de la regional, esta se le asigna a la dependencia',
  `estadoCgRegional` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la regional 10:Activo 0:Inactivo',
  `creacionCgRegional` datetime DEFAULT current_timestamp() COMMENT 'Fecha en la que se crea la regional',
  `siglaCgRegional` varchar(5) DEFAULT NULL COMMENT 'Sigla de la regional',
  `idNivelGeografico3` int(11) DEFAULT NULL COMMENT 'Relación con la tabla nivelGeografico3 (ciudad)'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgReportes`
--

CREATE TABLE `cgReportes` (
  `idCgReporte` int(11) NOT NULL COMMENT 'Identificador único en el sistema de la tabla cgReportes',
  `nombreCgReporte` varchar(255) NOT NULL COMMENT 'Nombre del reporte',
  `descripcionCgReporte` varchar(500) DEFAULT NULL COMMENT 'Descripción del reporte',
  `actionCgReporte` varchar(255) NOT NULL COMMENT 'Action que existe para cada reporte',
  `creacionCgReporte` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro',
  `estadoCgReporte` int(11) NOT NULL DEFAULT 10 COMMENT '10: Activo; 0: Inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTipoRadicadoDocumental`
--

CREATE TABLE `cgTipoRadicadoDocumental` (
  `idCgTipoRadicadoDocumental` int(11) NOT NULL COMMENT 'Id relación tipo radicado y documental',
  `idCgTipoRadicado` int(11) NOT NULL COMMENT 'Id tipo radicado',
  `idGdTrdTipoDocumental` int(11) NOT NULL COMMENT 'Id tipo documental',
  `estadoCgTipoRadicadoDocumental` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado',
  `creacionCgTipoRadicadoDocumental` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTiposGruposUsuarios`
--

CREATE TABLE `cgTiposGruposUsuarios` (
  `idCgTipoGrupoUsuarios` int(11) NOT NULL,
  `idCgGrupoUsuarios` int(11) NOT NULL,
  `idUser` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTiposRadicados`
--

CREATE TABLE `cgTiposRadicados` (
  `idCgTipoRadicado` int(11) NOT NULL,
  `codigoCgTipoRadicado` varchar(8) NOT NULL COMMENT 'Código que identifica el tipo de radicado que se esta realizando, es varchar porque pueden ser letras, esto es sgun el cliente lo defina.',
  `nombreCgTipoRadicado` varchar(45) NOT NULL COMMENT 'Nombre del tipo de radicado que se esta generando ej: Entrada, Salida, Pqrs, Resoluciones, Memorandos, esto es segun el cliente',
  `estadoCgTipoRadicado` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del tipo de radicado 10: activo 0: inactivo',
  `creacionCgTipoRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del tipo de radicado.',
  `unicoRadiCgTipoRadicado` int(11) NOT NULL DEFAULT 0 COMMENT 'Único radicado = 10, múltiple radicado = 0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTiposRadicadosResoluciones`
--

CREATE TABLE `cgTiposRadicadosResoluciones` (
  `idCgTiposRadicadosResoluciones` int(11) NOT NULL,
  `idCgTipoRadicado` int(11) NOT NULL,
  `numeracionCgTiposRadicadosResoluciones` bigint(20) NOT NULL,
  `creacionCgTiposRadicadosResoluciones` datetime NOT NULL DEFAULT current_timestamp(),
  `estadoCgTiposRadicadosResoluciones` smallint(6) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTiposRadicadosTransacciones`
--

CREATE TABLE `cgTiposRadicadosTransacciones` (
  `idCgTiposRadicadosTransacciones` int(11) NOT NULL,
  `idCgTransaccionRadicado` int(11) NOT NULL,
  `idCgTipoRadicado` int(11) NOT NULL,
  `orderCgTipoRadicado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTramites`
--

CREATE TABLE `cgTramites` (
  `idCgTramite` int(11) NOT NULL,
  `nombreCgTramite` varchar(200) NOT NULL,
  `estadoCgTramite` int(11) NOT NULL DEFAULT 10,
  `creacionCgTramite` datetime NOT NULL DEFAULT current_timestamp(),
  `tiempoRespuestaCgTramite` int(11) NOT NULL DEFAULT 15 COMMENT 'tiempo de respuesta para cada uno de los tramites',
  `mostrarCgTramite` int(11) NOT NULL DEFAULT 2 COMMENT 'campo que indica cuales aplican a nivel general y cuales aplican a nivel de PQRS 1 = PQRS y Entrada y 2 = Nivel general'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTransaccionesRadicados`
--

CREATE TABLE `cgTransaccionesRadicados` (
  `idCgTransaccionRadicado` int(11) NOT NULL,
  `rutaAccionCgTransaccionRadicado` varchar(80) NOT NULL COMMENT 'Ruta de la acción que se ejecuta en backend',
  `descripcionCgTransaccionRadicado` varchar(45) NOT NULL COMMENT 'Descripcion de la operación que se puede realizar con el radicado',
  `titleCgTransaccionRadicado` varchar(80) NOT NULL COMMENT 'Titulo que se muestra en el boton flotante',
  `iconCgTransaccionRadicado` varchar(30) NOT NULL COMMENT 'icono del boton flotante',
  `actionCgTransaccionRadicado` varchar(30) NOT NULL COMMENT 'action del boton flotante',
  `estadoCgTransaccionRadicado` int(11) NOT NULL DEFAULT 10 COMMENT 'estado de la operación asociado al radicado',
  `creacionCgTransaccionRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha de creación de la operación del radicado',
  `mostrarBotonCgTransaccionRadicado` int(11) NOT NULL DEFAULT 0 COMMENT 'Mostrar botón en la aplicación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTrd`
--

CREATE TABLE `cgTrd` (
  `idCgTrd` int(11) NOT NULL COMMENT 'Numero único de la cg TRD',
  `idMascaraCgTrd` int(11) NOT NULL COMMENT 'Número del id de la máscara',
  `cellDependenciaCgTrd` varchar(5) NOT NULL COMMENT 'Celda donde esta ubicada el código de la dependencia',
  `cellTituloDependCgTrd` varchar(5) NOT NULL COMMENT 'Celda del nombre del titulo de la dependencia TRD',
  `cellRegionalCgTrd` varchar(5) DEFAULT NULL COMMENT 'Celda donde esta ubicado el nombre de la regional',
  `cellDatosCgTrd` varchar(5) NOT NULL COMMENT 'Celda del inicio de los datos de la TRD',
  `columnCodigoCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del código de la trd, este código es (dependencia-serie-subserie) puede estar solo en 1 columna o puede estar en varios',
  `column2CodigoCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación del código de la trd, ubicación de la serie',
  `column3CodigoCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación del código de la trd, ubicación de la subserie',
  `columnNombreCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación de los nombres de la serie, subserie y tipos documentales',
  `columnAgCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem A.G',
  `columnAcCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem A.C',
  `columnCtCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem C.T',
  `columnECgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem E',
  `columnSCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem S',
  `columnMCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del ítem M',
  `columnProcessCgTrd` varchar(2) NOT NULL COMMENT 'Columna de ubicación del procedimiento',
  `columnTipoDocCgTrd` varchar(4) DEFAULT NULL COMMENT 'Días del tipo documental',
  `estadoCgTrd` smallint(6) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionCgTrd` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación de la cgTrd',
  `columnPSoporteCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación del soporte de la subserie (papel)',
  `columnESoporteCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación del soporte de la subserie (electrónico)',
  `columnOsoporteCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación del soporte de la subserie (otros)',
  `columnNormaCgTrd` varchar(2) DEFAULT NULL COMMENT 'Columna de ubicación de la norma',
  `cellDependenciaPadreCgTrd` varchar(5) DEFAULT NULL COMMENT 'Celda donde esta ubicada la unidad administrativa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgTrdMascaras`
--

CREATE TABLE `cgTrdMascaras` (
  `idCgTrdMascara` int(11) NOT NULL COMMENT 'Numero único de la TRD Mascara',
  `nombreCgTrdMascara` varchar(20) NOT NULL COMMENT 'Nombre único de la TRD Mascara (dd = dependencia, ss = series, sb= subseries)',
  `separadorCgTrdMascara` varchar(5) DEFAULT NULL COMMENT 'Separador de la máscara',
  `estadoCgTrdMascara` smallint(6) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionCgTrdMascara` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación de la TRD Mascara'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `cgVulnerabilidadPqrs`
--

CREATE TABLE `cgVulnerabilidadPqrs` (
  `idCgVulnerabilidadPqrs` int(11) NOT NULL,
  `nombreCgVulnerabilidadPqrs` varchar(250) NOT NULL,
  `estadoCgVulnerabilidadPqrs` int(11) NOT NULL DEFAULT 10,
  `creacionCgVulnerabilidadPqrs` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clienteEncuestas`
--

CREATE TABLE `clienteEncuestas` (
  `idClienteEncuesta` int(11) NOT NULL COMMENT 'Identificador único del registro en el sistema',
  `idCgEncuesta` int(11) NOT NULL COMMENT 'Identificador único de la encuesta',
  `tokenClienteEncuesta` varchar(255) NOT NULL COMMENT 'Token para permitir realizar la encuesta al ciudadano',
  `fechaClienteEncuesta` datetime DEFAULT NULL COMMENT 'Fecha en que el ciudadano realizo la encuesta',
  `estadoClienteEncuesta` int(11) NOT NULL DEFAULT 10 COMMENT '0 : Inactivo - 10 : Activo',
  `creacionClienteEncuesta` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro',
  `email` varchar(255) NOT NULL COMMENT 'email del cliente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientes`
--

CREATE TABLE `clientes` (
  `idCliente` int(11) NOT NULL,
  `idTipoPersona` int(11) NOT NULL,
  `nombreCliente` varchar(150) NOT NULL,
  `numeroDocumentoCliente` varchar(15) NOT NULL,
  `correoElectronicoCliente` varchar(80) NOT NULL,
  `direccionCliente` varchar(150) NOT NULL,
  `telefonoCliente` varchar(15) DEFAULT NULL,
  `estadoCliente` int(11) NOT NULL DEFAULT 10,
  `creacionCliente` datetime NOT NULL DEFAULT current_timestamp(),
  `idNivelGeografico3` int(11) NOT NULL,
  `idNivelGeografico2` int(11) NOT NULL,
  `idNivelGeografico1` int(11) NOT NULL,
  `codigoSap` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `clientesCiudadanosDetalles`
--

CREATE TABLE `clientesCiudadanosDetalles` (
  `idClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Identificador único de la tabla ClientesCiudadanosDetalles',
  `idCliente` int(11) NOT NULL COMMENT 'Identificador único de la tabla clientes asociada',
  `idUser` int(11) NOT NULL COMMENT 'relación con la tabla user',
  `idTipoIdentificacion` int(11) NOT NULL,
  `generoClienteCiudadanoDetalle` int(11) NOT NULL COMMENT '1: Hombre | 2: Mujer | 3: Otro',
  `rangoEdadClienteCiudadanoDetalle` int(11) NOT NULL COMMENT '1: 7-18 | 2: 19-29 | 3: 30-58 | 4: 59-',
  `vulnerabilidadClienteCiudadanoDetalle` int(11) NOT NULL COMMENT '1: Niño-Niña-Adolecente | 2: Desplazado | 3:  VictimaConflictoArmado | 4: Discapacidad | 5: Migrante | 6: Ninguno',
  `etniaClienteCiudadanoDetalle` int(11) NOT NULL COMMENT '1: Indigina | 2: Negro-Mulato-Afro | 3: Palanquero | 4: ROM | 5: Ninguno',
  `actEcomicaClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda la actividad economica del peticionario',
  `condDiscapacidadClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda la discapacidad del peticionario',
  `estratoClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda el estrato  del peticionario',
  `grupoInteresClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda el grupo de interes  del peticionario',
  `grupoSisbenClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda el nivel del sisben  del peticionario',
  `escolaridadClienteCiudadanoDetalle` int(11) NOT NULL COMMENT 'Guarda el nivel de escolaridad  del peticionario',
  `estadoClienteCiudadanoDetalle` int(11) NOT NULL DEFAULT 10 COMMENT '0: Inactivo | 10: Activo',
  `creacionClienteCiudadanoDetalle` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación de registro',
  `representanteCliente` varchar(120) DEFAULT NULL,
  `barrioClientesCiudadanoDetalle` varchar(80) DEFAULT NULL,
  `telefonoFijoClienteCiudadanoDetalle` varchar(25) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `csInicial`
--

CREATE TABLE `csInicial` (
  `idCsInicial` int(11) NOT NULL,
  `llaveCsInicial` varchar(50) NOT NULL,
  `valorCsInicial` varchar(100) NOT NULL,
  `creacionCsInicial` datetime NOT NULL DEFAULT current_timestamp(),
  `estadoCsInicial` smallint(6) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `csParams`
--

CREATE TABLE `csParams` (
  `idCsParams` int(11) NOT NULL,
  `llaveCsParams` varchar(50) NOT NULL,
  `valorCsParams` varchar(100) NOT NULL,
  `creacionCsParams` datetime NOT NULL DEFAULT current_timestamp(),
  `estadoCsParams` smallint(6) NOT NULL DEFAULT 10
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `encuestaCalificaciones`
--

CREATE TABLE `encuestaCalificaciones` (
  `idEncuestaCalificaciones` int(11) NOT NULL COMMENT 'Identificador único del registro',
  `idCgEncuestaPregunta` int(11) NOT NULL COMMENT 'Identificador único de la pregunta de una encuesta',
  `calificacionEncuestaPregunta` int(11) NOT NULL COMMENT '1: Deficiente - 2 : Regular - 3: Buena - 4: Excelente',
  `estadoEncuestaPregunta` int(11) NOT NULL DEFAULT 10 COMMENT '0 : Inactivo - 10 : Activo',
  `creacionEncuestaPregunta` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro',
  `idClienteEncuesta` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaArchivo`
--

CREATE TABLE `gaArchivo` (
  `idgaArchivo` int(11) NOT NULL,
  `idGdExpediente` int(11) NOT NULL COMMENT 'identificador de la tabla gdExpedientes',
  `idGaEdificio` int(11) NOT NULL COMMENT 'identificador de la tabla gaEdificio',
  `idGaPiso` int(11) NOT NULL COMMENT 'identificador de la tabla gaPiso',
  `idGaBodega` int(11) NOT NULL COMMENT 'identificador de la tabla gaBodega',
  `rackGaArchivo` int(11) NOT NULL COMMENT 'describe el numero del rack donde se encuentra el documento',
  `entrepanoGaArchivo` int(11) NOT NULL COMMENT 'describe el numero de entrepaño donde se encuentra el documento',
  `cajaGaArchivo` int(11) NOT NULL COMMENT 'describe el numero de la caja donde se encuentra el documento',
  `cuerpoGaArchivo` varchar(2) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Cuerpo de la bodega',
  `unidadConservacionGaArchivo` int(11) NOT NULL COMMENT 'Unidad de conservación  del documentos o clasificación del documento',
  `unidadCampoGaArchivo` varchar(20) NOT NULL COMMENT 'indica el identificador de la unidad de conservación ',
  `consecutivoGaArchivo` bigint(20) NOT NULL DEFAULT 0,
  `estadoGaArchivo` int(11) NOT NULL DEFAULT 10 COMMENT 'estado del archivo activo por defecto',
  `creacionGaArchivo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'creación del archivo',
  `estanteGaArchivo` int(11) NOT NULL COMMENT 'Describe el número del estante donde se encuentra el documento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Gestión de archivo tabla de documentos archivados';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaBodega`
--

CREATE TABLE `gaBodega` (
  `idGaBodega` int(11) NOT NULL COMMENT 'Id de la bodega',
  `nombreGaBodega` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Descripción de la bodega',
  `idGaPiso` int(11) NOT NULL COMMENT 'Id del piso',
  `estadoGaBodega` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la bodega',
  `creacionGaBodega` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaBodegaContenido`
--

CREATE TABLE `gaBodegaContenido` (
  `idGaBodegaContenido` int(11) NOT NULL COMMENT 'Id del contenido de la bodega',
  `cantidadRackGaBodegaContenido` int(11) NOT NULL COMMENT 'Cantidad del rack',
  `cantidadEntrepanoGaBodegaContenido` int(11) NOT NULL COMMENT 'Cantidad del entrepaño',
  `cantidadCajaGaBodegaContenido` int(11) NOT NULL COMMENT 'Cantidad de la caja',
  `cuerpoGaBodegaContenido` varchar(2) DEFAULT NULL COMMENT 'Cuerpo de la bodega',
  `idGaBodega` int(11) NOT NULL COMMENT 'Id de la bodega',
  `estadoGaBodegaContenido` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del contenido de la bodega',
  `creacionGaBodegaContenido` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación',
  `cantidadEstanteGaBodegaContenido` int(11) NOT NULL COMMENT 'Estante de la bodega'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaEdificio`
--

CREATE TABLE `gaEdificio` (
  `idGaEdificio` int(11) NOT NULL COMMENT 'Id edificio',
  `nombreGaEdificio` varchar(80) NOT NULL COMMENT 'Nombre del edificio',
  `idDepartamentoGaEdificio` int(11) NOT NULL COMMENT 'Id departamento',
  `idMunicipioGaEdificio` int(11) NOT NULL COMMENT 'Id municipio',
  `estadoGaEdificio` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del edificio',
  `creacionGaEdificio` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaHistoricoPrestamo`
--

CREATE TABLE `gaHistoricoPrestamo` (
  `idGaHistoricoPrestamo` int(11) NOT NULL COMMENT 'Id del préstamo histórico',
  `idGaPrestamo` int(11) NOT NULL COMMENT 'Id del préstamo documental',
  `idUser` int(11) NOT NULL,
  `idGdTrdDependencia` int(11) NOT NULL,
  `fechaGaHistoricoPrestamo` date NOT NULL COMMENT 'Fecha histórica',
  `observacionGaHistoricoPrestamo` varchar(500) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Observación del préstamo',
  `estadoGaHistoricoPrestamo` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado histórico',
  `creacionGaHistoricoPrestamo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaHistoricoPrestamoExpediente`
--

CREATE TABLE `gaHistoricoPrestamoExpediente` (
  `idGaHistoricoPrestamoExpediente` int(11) NOT NULL COMMENT 'Id del préstamo hist\r\nórico',
  `idGaPrestamoExpediente` int(11) NOT NULL COMMENT 'Id del préstamo documental',
  `idUser` int(11) NOT NULL,
  `idGdTrdDependencia` int(11) NOT NULL,
  `fechaGaHistoricoPrestamoExpediente` date NOT NULL COMMENT 'Fecha histórica',
  `observacionGaHistoricoPrestamoExpediente` varchar(500) NOT NULL COMMENT 'Observación del préstamo',
  `estadoGaHistoricoPrestamoExpediente` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado histórico',
  `creacionGaHistoricoPrestamoExpediente` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaPiso`
--

CREATE TABLE `gaPiso` (
  `idGaPiso` int(11) NOT NULL COMMENT 'Id del piso',
  `numeroGaPiso` int(11) NOT NULL COMMENT 'Número del piso',
  `idGaEdificio` int(11) NOT NULL COMMENT 'Id del edificio',
  `estadoGaPiso` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del piso',
  `creacionGaPiso` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaPrestamos`
--

CREATE TABLE `gaPrestamos` (
  `idGaPrestamo` int(11) NOT NULL COMMENT 'Id préstamo documental',
  `idGdExpedienteInclusion` int(11) NOT NULL COMMENT 'Id expediente inclusión',
  `idUser` int(11) NOT NULL COMMENT 'Id usuario logueado',
  `idGdTrdDependencia` int(11) NOT NULL COMMENT 'Id dependencia del usuario logueado',
  `fechaSolicitudGaPrestamo` date NOT NULL COMMENT 'Fecha de solicitud del préstamo',
  `idTipoPrestamoGaPrestamo` int(11) NOT NULL COMMENT 'Tipo préstamo',
  `idRequerimientoGaPrestamo` int(11) NOT NULL COMMENT 'Requerimiento',
  `observacionGaPrestamo` varchar(255) NOT NULL COMMENT 'Observación',
  `estadoGaPrestamo` int(11) NOT NULL DEFAULT 18 COMMENT 'Estado del préstamo',
  `creacionGaPrestamo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gaPrestamosExpedientes`
--

CREATE TABLE `gaPrestamosExpedientes` (
  `idGaPrestamoExpediente` int(11) NOT NULL COMMENT 'Id préstamo documental',
  `idGdExpediente` int(11) NOT NULL COMMENT 'Id expediente inclusión',
  `idUser` int(11) NOT NULL COMMENT 'Id usuario logueado',
  `idGdTrdDependencia` int(11) NOT NULL COMMENT 'Id dependencia del usuario logueado',
  `fechaSolicitudGaPrestamoExpediente` date NOT NULL COMMENT 'Fecha de solicitud del préstamo',
  `idTipoPrestamoGaPrestamoExpediente` int(11) NOT NULL COMMENT 'Tipo préstamo',
  `idRequerimientoGaPrestamoExpediente` int(11) NOT NULL COMMENT 'Requerimiento',
  `observacionGaPrestamoExpediente` varchar(255) NOT NULL COMMENT 'Observación',
  `estadoGaPrestamoExpediente` int(11) NOT NULL DEFAULT 18 COMMENT 'Estado del préstamo',
  `creacionGaPrestamoExpediente` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdExpedienteDocumentos`
--

CREATE TABLE `gdExpedienteDocumentos` (
  `idGdExpedienteDocumento` int(11) NOT NULL,
  `idGdExpediente` int(11) NOT NULL,
  `numeroGdExpedienteDocumento` varchar(80) NOT NULL,
  `rutaGdExpedienteDocumento` varchar(255) NOT NULL,
  `extensionGdExpedienteDocumento` varchar(80) NOT NULL,
  `tamanoGdExpedienteDocumento` varchar(20) NOT NULL,
  `idGdTrdTipoDocumental` int(11) NOT NULL,
  `isPublicoGdExpedienteDocumento` int(11) NOT NULL,
  `idUser` int(11) NOT NULL,
  `nombreGdExpedienteDocumento` varchar(250) DEFAULT NULL,
  `observacionGdExpedienteDocumento` text NOT NULL,
  `estadoGdExpedienteDocumento` int(11) NOT NULL,
  `creacionGdExpedienteDocumento` datetime NOT NULL,
  `fechaDocGdExpedienteDocumento` datetime DEFAULT NULL COMMENT 'Fecha del documento cargado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdExpedientes`
--

CREATE TABLE `gdExpedientes` (
  `idGdExpediente` int(11) NOT NULL COMMENT 'Identificador único del expediente',
  `numeroGdExpediente` varchar(20) NOT NULL COMMENT 'Número del expediente asignado por el sistema',
  `nombreGdExpediente` varchar(250) NOT NULL COMMENT 'Nombre del expediente',
  `idUser` int(11) NOT NULL COMMENT 'Identificador único que se le asigna al usuario creador',
  `idGdTrdDependencia` int(11) NOT NULL COMMENT 'Identificador único que se le asigna a la dependencia del usuario creador',
  `idGdTrdSerie` int(11) NOT NULL COMMENT 'Identificador único que se le asigna a la serie',
  `idGdTrdSubserie` int(11) NOT NULL COMMENT 'Identificador único que se le asigna a la subserie',
  `estadoGdExpediente` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del expediente  10 activo 0 Inactivo',
  `ubicacionGdExpediente` int(11) DEFAULT 1 COMMENT 'ubicacion actual del expediente (gestion/central/historico)',
  `creacionGdExpediente` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del expediente',
  `tiempoGestionGdExpedientes` datetime NOT NULL COMMENT 'fecha de vencimiento para la gestión asignada segun la subserie',
  `tiempoCentralGdExpedientes` datetime NOT NULL COMMENT 'fecha de vencimiento para la central asignada segun la subserie',
  `fechaProcesoGdExpediente` varchar(10) NOT NULL COMMENT 'Fecha de proceso',
  `descripcionGdExpediente` varchar(500) NOT NULL COMMENT 'Descripción de expediente',
  `numeracionGdExpediente` int(11) NOT NULL DEFAULT 0,
  `rutaCerrarGdExpediente` varchar(255) DEFAULT NULL COMMENT 'Ruta del documento de cierre del expediente',
  `existeFisicamente` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el expediente existe físicamente 0: false, 1: true',
  `existeFisicamenteGdExpediente` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si el expediente existe físicamente 0: false, 1: true'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdExpedientesDependencias`
--

CREATE TABLE `gdExpedientesDependencias` (
  `idGdExpedientesDependencias` int(11) NOT NULL,
  `idGdExpediente` int(11) NOT NULL,
  `idGdTrdDependencia` int(11) NOT NULL,
  `estadoGdExpedientesDependencias` smallint(6) NOT NULL DEFAULT 10,
  `creacionGdExpedientesDependencias` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdExpedientesInclusion`
--

CREATE TABLE `gdExpedientesInclusion` (
  `idGdExpedienteInclusion` int(11) NOT NULL COMMENT 'Identificador único de la inclusión del expediente',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'Identificador único de la inclusión del radicado',
  `idGdExpediente` int(11) NOT NULL COMMENT 'Identificador único de la inclusión del expediente',
  `estadoGdExpedienteInclusion` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la inclusión del expediente 10 activo 0 Inactivo',
  `creacionGdExpedienteInclusion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación de la inclusión del expediente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdFirmasMultiples`
--

CREATE TABLE `gdFirmasMultiples` (
  `idGdFirmaMultiple` int(11) NOT NULL,
  `idradiDocPrincipal` int(11) NOT NULL COMMENT 'Id del documento a firmar',
  `firmaGdFirmaMultiple` varchar(80) NOT NULL COMMENT 'Identificador {firma#}',
  `userGdFirmaMultiple` int(11) DEFAULT NULL COMMENT 'Usuario firmante',
  `estadoGdFirmaMultiple` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado',
  `creacionGdFirmaMultiple` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación '
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdFirmasQr`
--

CREATE TABLE `gdFirmasQr` (
  `idGdFirmasQr` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL COMMENT 'id de la tabla radiRadicados',
  `idUser` int(11) NOT NULL COMMENT 'id de la tabla user',
  `estadoGdFirmasQr` int(11) NOT NULL DEFAULT 10 COMMENT 'indica el permiso que tenia el usuario al momento de firmar',
  `creacionGdFirmasQr` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de generación de la firma',
  `idDocumento` int(11) NOT NULL COMMENT 'Id del documento principal'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='información de firmas QR ';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdHistoricoExpedientes`
--

CREATE TABLE `gdHistoricoExpedientes` (
  `idGdHistoricoExpediente` int(11) NOT NULL COMMENT 'Identificador único del historico de expediente',
  `idGdExpediente` int(11) NOT NULL COMMENT 'Identificador único del expediente',
  `idUser` int(11) NOT NULL COMMENT 'Identificador único que se le asigna al usuario que hizo la transacción',
  `idGdTrdDependencia` int(11) NOT NULL COMMENT 'Identificador único que se le asigna al usuario que hizo la inclusión',
  `operacionGdHistoricoExpediente` int(11) NOT NULL COMMENT '1 CrearExpediente 2 IncluirExpediente 3 CambiarEstado 4 Archivar Expediente 5 Subir documento',
  `observacionGdHistoricoExpediente` varchar(500) NOT NULL COMMENT 'Observación que especifica el motivo de la transacción',
  `estadoGdHistoricoExpediente` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del gistorico del expediente 10 activo 0 Inactivo',
  `creacionGdHistoricoExpediente` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación de la inclusión del expediente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdIndices`
--

CREATE TABLE `gdIndices` (
  `idGdIndice` int(11) NOT NULL COMMENT 'Identificador único en el sisitema',
  `indiceContenidoGdIndice` varchar(20) NOT NULL COMMENT 'Indice del contenido',
  `idGdExpedienteInclusion` int(11) DEFAULT NULL COMMENT 'Identificador único de la tabla expedienteInclusion',
  `valorHuellaGdIndice` varchar(2000) NOT NULL COMMENT 'Valor huella',
  `funcionResumenGdIndice` varchar(20) NOT NULL COMMENT 'Función resumen',
  `ordenDocumentoGdIndice` int(11) NOT NULL COMMENT 'Ordel del documento en el indice',
  `paginaInicioGdIndice` int(11) DEFAULT NULL COMMENT 'Página inicial',
  `paginaFinalGdIndice` int(11) DEFAULT NULL COMMENT 'Página final',
  `tamanoGdIndice` varchar(20) NOT NULL COMMENT 'Tamaño del documento',
  `rutaXmlGdIndice` varchar(500) NOT NULL COMMENT 'Ruta en donde se carga el XML del indice',
  `estadoGdIndice` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de gdIndice 10: Activo | 0: Inactivo',
  `CreacionGdIndice` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creacion de gdIndice',
  `nombreDocumentoGdIndice` varchar(255) NOT NULL COMMENT 'Nombre del documento',
  `idGdTrdTipoDocumental` int(11) NOT NULL COMMENT 'Identificador de Gd TRd Tipo Documental',
  `creacionDocumentoGdIndice` datetime NOT NULL COMMENT 'Fecha de creación del documento',
  `formatoDocumentoGdIndice` varchar(20) NOT NULL COMMENT 'Extenxión del documento',
  `descripcionGdIndice` varchar(500) NOT NULL COMMENT 'Descripción del documento',
  `usuarioGdIndice` varchar(255) NOT NULL COMMENT 'Usuario que anexó el documento',
  `idGdExpedienteDocumento` int(11) DEFAULT NULL COMMENT 'Identificador de la tabla gdExpedienteDocumento',
  `origenGdIndice` int(11) DEFAULT NULL COMMENT 'Número correspondiente del tipo de documento',
  `fechaFirmaGdIndice` date DEFAULT NULL COMMENT 'Fecha firma',
  `idGdReferenciaCruzada` int(11) DEFAULT NULL COMMENT 'Identificador de la tabla gdReferenciaCruzada'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdReferenciasCruzadas`
--

CREATE TABLE `gdReferenciasCruzadas` (
  `idGdReferenciaCruzada` int(11) NOT NULL COMMENT 'Campo primario de la tabla',
  `nombreGdReferenciaCruzada` varchar(80) NOT NULL COMMENT 'Nombre del medio ',
  `cantidadGdReferenciaCruzada` int(11) NOT NULL COMMENT 'Cantidad de medios de la referencia cruzada',
  `ubicacionGdReferenciaCruzada` varchar(255) NOT NULL COMMENT 'Ubicación',
  `idGdExpediente` int(11) NOT NULL COMMENT 'Identificador del expediente',
  `idUserGdReferenciaCruzada` int(11) NOT NULL COMMENT 'Usuario que elabora la referencia cruzada',
  `tipoAnexoGdReferenciaCruzada` varchar(80) DEFAULT NULL COMMENT 'Descripción del tipo de anexo de la referencia cruzada',
  `rutaGdReferenciasCruzada` varchar(255) NOT NULL COMMENT 'Ruta del archivo generado',
  `nombreArchivoGdReferenciasCruzada` varchar(80) DEFAULT NULL COMMENT 'Nombre del archivo generado',
  `idGdTrdTipoDocumental` int(11) NOT NULL COMMENT 'Tipo documental',
  `creacionGdReferenciaCruzada` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro',
  `estadoGdReferenciaCruzada` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la referencia cruzada 0: Inactivo, 10: Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tabla que almacena las referencias cruzadas de los expedientes híbridos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdReferenciaTiposAnexos`
--

CREATE TABLE `gdReferenciaTiposAnexos` (
  `idGdReferenciaTipoAnexo` int(11) NOT NULL COMMENT 'Campo primario de la tabla',
  `idGdReferenciaCruzada` int(11) NOT NULL COMMENT 'Identificador de la referencia cruzada',
  `idGdTipoAnexoFisico` int(11) NOT NULL COMMENT 'Identificador del tipo de anexo',
  `estadoGdReferenciaTipoAnexo` int(11) NOT NULL DEFAULT 10 COMMENT 'estado del registro 0: Inactivo, 10: Activo',
  `creacionGdReferenciaTipoAnexo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tabla que almacena los tipos de anexos físicos de los expedientes híbridos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTiposAnexosFisicos`
--

CREATE TABLE `gdTiposAnexosFisicos` (
  `idGdTipoAnexoFisico` int(11) NOT NULL COMMENT 'Campo primario de la tabla',
  `nombreGdTipoAnexoFisico` varchar(80) NOT NULL COMMENT 'Nombre tipo de anexo ',
  `estadoGdTipoAnexoFisico` int(11) NOT NULL DEFAULT 10 COMMENT 'estado del registro 0: Inactivo, 10: Activo',
  `creacionGdTipoAnexoFisico` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Tabla que almacena los tipos de anexos físicos de los expedientes híbridos';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrd`
--

CREATE TABLE `gdTrd` (
  `idGdTrd` int(11) NOT NULL COMMENT 'Número único para identificar la tabla',
  `idGdTrdDependencia` int(11) DEFAULT NULL,
  `idGdTrdSerie` int(11) DEFAULT NULL,
  `idGdTrdSubserie` int(11) DEFAULT NULL,
  `idGdTrdTipoDocumental` int(11) DEFAULT NULL,
  `estadoGdTrd` int(11) DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrd` datetime DEFAULT current_timestamp() COMMENT 'Creación del registro',
  `versionGdTrd` int(11) DEFAULT NULL COMMENT 'campo que guarda el número de la versión de la trd que se ha cargado para la dependencia',
  `identificadorUnicoGdTrd` varchar(13) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdDependencias`
--

CREATE TABLE `gdTrdDependencias` (
  `idGdTrdDependencia` int(11) NOT NULL COMMENT 'Identificador de la tabla',
  `nombreGdTrdDependencia` varchar(255) NOT NULL COMMENT 'Nombre de la dependencia o area funcional según organigrama del cliente - Oficina productora',
  `codigoGdTrdDependencia` varchar(6) NOT NULL COMMENT 'Código de la dependencia o centro de costos acorde al organigrama del cliente - Oficiona productora ',
  `codigoGdTrdDepePadre` int(11) DEFAULT 0 COMMENT 'Código de la dependencia o centro de costos acorde al organigrama del cliente - Oficiona productora',
  `estadoGdTrdDependencia` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la dependencia o área funcional',
  `creacionGdTrdDependencia` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación de la dependencia',
  `idCgRegional` int(11) NOT NULL COMMENT 'Idenntificador de la tabla regional, relación entre la tabla',
  `observacionGdTrdDependencia` varchar(500) DEFAULT NULL COMMENT 'Observación que se indica cuando se aprueba una Versión de la TRD',
  `consecExpedienteGdTrdDependencia` int(11) NOT NULL DEFAULT 1 COMMENT 'Consecutivo para el expediente'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdDependenciasTmp`
--

CREATE TABLE `gdTrdDependenciasTmp` (
  `idGdTrdDependenciaTmp` int(11) NOT NULL COMMENT 'idGdTrdDependencia',
  `nombreGdTrdDependenciaTmp` varchar(255) NOT NULL COMMENT 'Nombre de la dependencia o area funcional según organigrama del cliente - Oficina productora',
  `codigoGdTrdDependenciaTmp` varchar(6) NOT NULL COMMENT 'Código de la dependencia o centro de costos acorde al organigrama del cliente - Oficiona productora ',
  `codigoGdTrdDepePadreTmp` int(11) DEFAULT 0 COMMENT 'Código de la dependencia o área funcional, dependencia principal - Unidad administrativa',
  `estadoGdTrdDependenciaTmp` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la dependencia o área funcional',
  `creacionGdTrdDependenciaTmp` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación de la dependencia',
  `idCgRegionalTmp` int(11) NOT NULL COMMENT 'Identificador de la tabla regional, relación entre la tabla'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdSeries`
--

CREATE TABLE `gdTrdSeries` (
  `idGdTrdSerie` int(11) NOT NULL COMMENT 'Número único para identificar la serie',
  `nombreGdTrdSerie` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `codigoGdTrdSerie` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL,
  `estadoGdTrdSerie` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrdSerie` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdSeriesTmp`
--

CREATE TABLE `gdTrdSeriesTmp` (
  `idGdTrdSerieTmp` int(11) NOT NULL COMMENT 'Número único para identificar la serie',
  `nombreGdTrdSerieTmp` varchar(255) NOT NULL,
  `codigoGdTrdSerieTmp` varchar(20) NOT NULL,
  `estadoGdTrdSerieTmp` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrdSerieTmp` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdSubseries`
--

CREATE TABLE `gdTrdSubseries` (
  `idGdTrdSubserie` int(11) NOT NULL COMMENT 'Número único para identificar la subserie',
  `nombreGdTrdSubserie` varchar(255) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nombre de la subserie documental',
  `codigoGdTrdSubserie` varchar(20) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Código asociado a la subserie documetal este va a sociado a la dependencia',
  `tiempoGestionGdTrdSubserie` int(11) NOT NULL COMMENT 'Tiempo en años de gestión asignados a la subserie',
  `tiempoCentralGdTrdSubserie` int(11) NOT NULL COMMENT 'Tiempo en años de central asignados a la subserie',
  `pSoporteGdTrdSubserie` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (papel)',
  `eSoporteGdTrdSubserie` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (electronico)',
  `oSoporteGdTrdSubserie` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (otro)',
  `ctDisposicionFinalGdTrdSubserie` smallint(6) DEFAULT NULL,
  `eDisposicionFinalGdTrdSubserie` smallint(6) DEFAULT NULL,
  `sDisposicionFinalGdTrdSubserie` smallint(6) DEFAULT NULL,
  `mDisposicionFinalGdTrdSubserie` smallint(6) DEFAULT NULL,
  `procedimientoGdTrdSubserie` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Procedimiento correspondiente a la subserie',
  `estadoGdTrdSubserie` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrdSubserie` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Este campo guarda la información respecto al soporte de la subserie (electronico)',
  `normaGdTrdSubserie` text CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Guarda la norma que justifica la subserie',
  `idGdTrdSerie` int(11) NOT NULL DEFAULT 0 COMMENT 'Código que relaciona el id de la serie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdSubseriesTmp`
--

CREATE TABLE `gdTrdSubseriesTmp` (
  `idGdTrdSubserieTmp` int(11) NOT NULL COMMENT 'Número único para identificar la subserie',
  `nombreGdTrdSubserieTmp` varchar(255) NOT NULL COMMENT 'Nombre de la subserie documental',
  `codigoGdTrdSubserieTmp` varchar(45) NOT NULL COMMENT 'Código asociado a la subserie documetal este va a sociado a la dependencia',
  `tiempoGestionGdTrdSubserieTmp` int(11) NOT NULL COMMENT 'Tiempo en años de gestión asignados a la subserie',
  `tiempoCentralGdTrdSubserieTmp` int(11) NOT NULL COMMENT 'Tiempo en años de central asignados a la subserie',
  `pSoporteGdTrdSubserieTmp` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (papel)',
  `ctDisposicionFinalGdTrdSubserieTmp` smallint(6) DEFAULT NULL,
  `eDisposicionFinalGdTrdSubserieTmp` smallint(6) DEFAULT NULL,
  `sDisposicionFinalGdTrdSubserieTmp` smallint(6) DEFAULT NULL,
  `mDisposicionFinalGdTrdSubserieTmp` smallint(6) DEFAULT NULL,
  `eSoporteGdTrdSubserieTmp` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (electronico)',
  `oSoporteGdTrdSubserieTmp` smallint(6) DEFAULT NULL COMMENT 'Este campo guarda la información respecto al soporte de la subserie (otro)',
  `procedimientoGdTrdSubserieTmp` text NOT NULL COMMENT 'Procedimiento correspondiente a la subserie',
  `estadoGdTrdSubserieTmp` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrdSubserieTmp` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro',
  `normaGdTrdSubserieTmp` text DEFAULT NULL,
  `idGdTrdSerieTmp` int(11) DEFAULT NULL COMMENT 'Código que relaciona el id de la serie'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdTiposDocumentales`
--

CREATE TABLE `gdTrdTiposDocumentales` (
  `idGdTrdTipoDocumental` int(11) NOT NULL COMMENT 'Numero único de tipo documental',
  `nombreTipoDocumental` varchar(255) NOT NULL,
  `diasTramiteTipoDocumental` smallint(6) NOT NULL DEFAULT 15 COMMENT 'Son los días de tramite',
  `estadoTipoDocumental` smallint(6) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionTipoDocumental` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del tipo documental'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdTiposDocumentalesTmp`
--

CREATE TABLE `gdTrdTiposDocumentalesTmp` (
  `idGdTrdTipoDocumentalTmp` int(11) NOT NULL COMMENT 'Numero único de tipo documental',
  `nombreTipoDocumentalTmp` varchar(255) NOT NULL COMMENT 'nombreTipoDocumental',
  `diasTramiteTipoDocumentalTmp` int(11) DEFAULT 15 COMMENT 'Nombre único del tipo documental',
  `estadoTipoDocumentalTmp` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionTipoDocumentalTmp` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del tipo documental'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `gdTrdTmp`
--

CREATE TABLE `gdTrdTmp` (
  `idGdTrdTmp` int(11) NOT NULL COMMENT 'Número único para identificar la tabla',
  `idGdTrdDependenciaTmp` int(11) DEFAULT NULL,
  `idGdTrdSerieTmp` int(11) DEFAULT NULL,
  `idGdTrdSubserieTmp` int(11) DEFAULT NULL,
  `idGdTrdTipoDocumentalTmp` int(11) DEFAULT NULL,
  `estadoGdTrdTmp` int(11) DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionGdTrdTmp` datetime DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `infoFirmaDigitalDocPrincipales`
--

CREATE TABLE `infoFirmaDigitalDocPrincipales` (
  `idFirmaDigital` int(11) NOT NULL COMMENT 'Id de la firma digital',
  `firmante` varchar(100) DEFAULT NULL COMMENT 'Persona que puso la firma digital',
  `fechaFirmado` datetime DEFAULT NULL COMMENT 'Fecha en la que se firmo el documento',
  `fechaVencimiento` datetime DEFAULT NULL COMMENT 'Fecha en la que se vence la firma digital',
  `idUser` int(11) DEFAULT NULL COMMENT 'Usuario logueado que carga el archivo firmado digitalmente al sistema',
  `idradiDocumentoPrincipal` int(11) DEFAULT NULL COMMENT 'Documento principal al cual pertenece la firma digital contiene la información de la firma digital que subio el usuario logueado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `initCgConfigFirmas`
--

CREATE TABLE `initCgConfigFirmas` (
  `idInitCgConfigFirma` int(11) NOT NULL,
  `idInitCgEntidadFirma` int(11) NOT NULL COMMENT 'Relación con tabla initCgEntidadFirma',
  `idInitCgParamFirma` int(11) NOT NULL COMMENT 'Relación con tabla  initCgParamFirma',
  `valorInitCgConfigFirma` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci DEFAULT NULL COMMENT 'Valor de lo que contendra dicho campo',
  `creacionInitCgConfigFirma` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creción de la configuración firma',
  `estadoInitCgConfigFirma` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la configuración'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `initCgCordenadasFirma`
--

CREATE TABLE `initCgCordenadasFirma` (
  `idInitCgCordenadaFirma` int(11) NOT NULL,
  `nombreInitCgCordenadaFirma` varchar(250) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nombre de la firma del documento',
  `cordenadasInitCgCordenadaFirma` varchar(80) NOT NULL COMMENT 'Coordenadas de la firma x, y',
  `estadoInitCgCordenadaFirma` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de las cordenadas de firma',
  `creacionInitCgCordenadaFirma` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha creación de las coordenadas de firma'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `initCgEntidadesFirma`
--

CREATE TABLE `initCgEntidadesFirma` (
  `idInitCgEntidadFirma` int(11) NOT NULL COMMENT 'Identificador unico de la tabla',
  `nombreInitCgEntidadFirma` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nombre de la entidad que ofrece el servicio de firmas cetificadas digitalmente',
  `creacionInitCgEntidadFirma` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación de la entidad que ofrece el servicio',
  `estadoInitCgEntidadFirma` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la entidad'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `initCgParamsFirma`
--

CREATE TABLE `initCgParamsFirma` (
  `idInitCgParamFirma` int(11) NOT NULL COMMENT 'Identificador unico de la tabla',
  `variableInitCgParamFirma` varchar(80) CHARACTER SET utf8mb3 COLLATE utf8mb3_general_ci NOT NULL COMMENT 'Nombre de la variable a ultilizar para la configuración',
  `descripcionInitCgParamsFirma` varchar(250) NOT NULL COMMENT 'Descripción de para que se usa este campo	',
  `creacionInitCgParamFirma` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación de la variable',
  `estadoInitCgParamFirma` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado de la variable'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `log`
--

CREATE TABLE `log` (
  `idLog` int(11) NOT NULL,
  `idUser` int(11) NOT NULL,
  `userNameLog` varchar(80) NOT NULL,
  `fechaLog` datetime NOT NULL DEFAULT current_timestamp(),
  `ipLog` varchar(40) NOT NULL,
  `moduloLog` varchar(80) NOT NULL,
  `eventoLog` text NOT NULL,
  `antesLog` text DEFAULT NULL,
  `despuesLog` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivelGeografico1`
--

CREATE TABLE `nivelGeografico1` (
  `nivelGeografico1` int(11) NOT NULL COMMENT 'Identificador único del país',
  `nomNivelGeografico1` varchar(50) NOT NULL COMMENT 'Nombre del país',
  `cdi` varchar(20) NOT NULL COMMENT 'CDI',
  `estadoNivelGeografico1` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionNivelGeografico1` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivelGeografico2`
--

CREATE TABLE `nivelGeografico2` (
  `nivelGeografico2` int(11) NOT NULL COMMENT 'Identificador único del nivel 2',
  `idNivelGeografico1` int(11) NOT NULL COMMENT 'Número que indica el país al que pertenece',
  `nomNivelGeografico2` varchar(180) NOT NULL COMMENT 'Nombre del nivel geografico 2 ( Departamento )',
  `estadoNivelGeografico2` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionNivelGeografico2` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `nivelGeografico3`
--

CREATE TABLE `nivelGeografico3` (
  `nivelGeografico3` int(11) NOT NULL COMMENT 'Número único de la tabla',
  `idNivelGeografico2` int(11) NOT NULL COMMENT 'Número del departmaneto al que pertenece',
  `nomNivelGeografico3` varchar(50) NOT NULL COMMENT 'Nombre del nivel geografico 3 ( Ciudad )',
  `estadoNivelGeografico3` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionNivelGeografico3` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificacion`
--

CREATE TABLE `notificacion` (
  `idNotificacion` int(11) NOT NULL COMMENT 'Id de notificación',
  `idUserCreador` int(11) NOT NULL COMMENT 'Id del usuario creador',
  `idUserNotificado` int(11) NOT NULL COMMENT 'Id del usuario notificado',
  `notificacion` varchar(80) NOT NULL COMMENT 'Texto de la notificación',
  `urlNotificacion` varchar(80) NOT NULL COMMENT 'Url de redirección',
  `estadoNotificacion` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado',
  `creacionNotificacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ocrDatos`
--

CREATE TABLE `ocrDatos` (
  `idOcrDatos` int(11) NOT NULL,
  `idDocumentoOcrDatos` int(11) NOT NULL COMMENT 'id registro del documento segun la tabla',
  `tablaAfectadaOcrDatos` int(11) NOT NULL COMMENT '1:radiDocumentos, 2:radiDocumentosPrincipales, 3:gdExpedienteDocumentos',
  `textoExtraidoOcrDatos` longtext NOT NULL COMMENT 'contenido del documento',
  `creacionOcrDatos` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'creación registro',
  `estadoOcrDatos` int(11) NOT NULL DEFAULT 10 COMMENT 'estado estandar'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='Guarda directamente todo el texto que se extrae de los docum';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ocrSphinxIndexMeta`
--

CREATE TABLE `ocrSphinxIndexMeta` (
  `idOcrSphinxIndexMeta` int(11) NOT NULL,
  `nombreOcrSphinxIndexMeta` text NOT NULL,
  `idMaxOcrSphinxIndexMeta` int(11) NOT NULL,
  `fechaActualizaOcrSphinxIndexMeta` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Marca de tiempo cada vez que se ejecuta el proceso de indeza';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `ocrSphinxIndexRemove`
--

CREATE TABLE `ocrSphinxIndexRemove` (
  `idOcrSphinxIndexRemove` int(11) NOT NULL,
  `indiceOcrSphinxIndexRemove` int(11) NOT NULL,
  `estadoOcrSphinxIndexRemove` int(11) NOT NULL DEFAULT 10 COMMENT 'estado estandar',
  `creacionOcrSphinxIndexRemove` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha de creacion',
  `ejecucionOcrSphinxIndexRemove` int(11) NOT NULL,
  `identiOcrSphinxIndexRemove` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='sphinx_index_remove se usa para indicarle a sphinx que regis';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiAgendaRadicados`
--

CREATE TABLE `radiAgendaRadicados` (
  `idRadiAgendaRadicados` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL COMMENT 'id radicado',
  `fechaProgramadaRadiAgenda` date NOT NULL COMMENT 'fecha programada para el evento del radicado',
  `descripcionRadiAgenda` varchar(500) NOT NULL COMMENT 'descripcion del evento programado',
  `estadoRadiAgenda` int(11) NOT NULL COMMENT '0 // inactiva - 10 // Activa'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Programar gestión (Agendar)';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiCorreosRadicados`
--

CREATE TABLE `radiCorreosRadicados` (
  `idRadiCorreosRadicados` int(11) NOT NULL COMMENT 'Indice unico de la tabla',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'Id del radicado',
  `bandeja` varchar(255) NOT NULL COMMENT 'Nombre de la bandeja',
  `idCorreo` int(11) NOT NULL COMMENT 'Identificador del correo',
  `email` varchar(255) NOT NULL COMMENT 'Correo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiDetallePqrsAnonimo`
--

CREATE TABLE `radiDetallePqrsAnonimo` (
  `idRadiDetallePqrsAnonimo` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL,
  `idNivelGeografico1` int(11) NOT NULL,
  `idNivelGeografico2` int(11) NOT NULL,
  `idNivelGeografico3` int(11) NOT NULL,
  `direccionRadiDetallePqrsAnonimo` varchar(80) NOT NULL,
  `estadoRadiDetallePqrsAnonimo` int(11) NOT NULL DEFAULT 10,
  `creacionRadiDetallePqrsAnonimo` datetime NOT NULL DEFAULT current_timestamp(),
  `emailRadiDetallePqrsAnonimo` varchar(80) DEFAULT NULL COMMENT 'correo que registra el ciudadono de forma anonima al que desea que le notifiquen el tramite de la solicitud'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiDocumentos`
--

CREATE TABLE `radiDocumentos` (
  `idRadiDocumento` int(11) NOT NULL,
  `numeroRadiDocumento` int(3) NOT NULL COMMENT 'Este campo hace referencia a la cantidad de documentos que hay para un radicado.',
  `nombreRadiDocumento` varchar(255) NOT NULL COMMENT 'Nombre que se le da al documento que se carga al radicado este nombre debe ser el número de radicado + la columna numeroRadiDocumento',
  `rutaRadiDocumento` varchar(255) NOT NULL COMMENT 'Ruta donde se encuentra el documento que se cargo para el radicado',
  `extencionRadiDocumento` varchar(20) NOT NULL COMMENT 'Guarda la extención del documento que se esta cargando',
  `descripcionRadiDocumento` varchar(500) NOT NULL COMMENT 'Descripción u obsevación de los que es el documento',
  `estadoRadiDocumento` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado que se le aplica al documento',
  `creacionRadiDocumento` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en la que se crea el documento',
  `idRadiRadicado` int(11) NOT NULL,
  `idGdTrdTipoDocumental` int(11) NOT NULL,
  `idUser` int(11) NOT NULL COMMENT 'Identificador del usuario que cargo el documento',
  `isPublicoRadiDocumento` int(11) NOT NULL DEFAULT 0 COMMENT 'Indica si el anexo está marcado como público 0: No, 10: Si',
  `tamanoRadiDocumento` varchar(20) NOT NULL COMMENT 'Tamaño del archivo del documento',
  `publicoPagina` int(11) DEFAULT 0 COMMENT 'Público página 10 y 0 no público'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiDocumentosPrincipales`
--

CREATE TABLE `radiDocumentosPrincipales` (
  `idradiDocumentoPrincipal` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL COMMENT '	identificador de la tabla radiRadicados',
  `idUser` int(11) NOT NULL COMMENT 'Identificador del usuario que cargo el documento',
  `nombreRadiDocumentoPrincipal` varchar(80) NOT NULL COMMENT 'Nombre que se le da al documento que se carga',
  `rutaRadiDocumentoPrincipal` varchar(255) NOT NULL COMMENT 'Ruta donde se encuentra el documento que se cargo para el radicado',
  `extensionRadiDocumentoPrincipal` varchar(20) NOT NULL COMMENT 'Guarda la extensión del documento que se esta cargando	',
  `imagenPrincipalRadiDocumento` int(11) NOT NULL DEFAULT 0 COMMENT 'identificador imagen principal del radicado // 10 Activo 0 Inactivo',
  `estadoRadiDocumentoPrincipal` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado que se le aplica al documento defecto activo = 10',
  `creacionRadiDocumentoPrincipal` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en la que se crea el documento',
  `tamanoRadiDocumentoPrincipal` varchar(20) NOT NULL COMMENT 'Tamaño del archivo del documento',
  `publicoPagina` int(11) DEFAULT 0 COMMENT 'Público página 10 y 0 no público',
  `idRadiRespuesta` int(11) DEFAULT NULL COMMENT 'Id radicado de respuesta',
  `fechaEpochCreacion` int(12) DEFAULT NULL COMMENT 'Fecha EPOCH en la que se le agrego la firma digital de orfeo para proceso de firma digital',
  `hash` varchar(100) DEFAULT NULL COMMENT 'Hash del documento que se descargo para proceso de firma digital',
  `fechaEpochCreacionArchivo` int(12) DEFAULT NULL COMMENT 'Fecha EPOCH cuando se creo el archivo',
  `paginas` int(11) DEFAULT NULL COMMENT 'Numero de páginas del archivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiDocumentosRadicados`
--

CREATE TABLE `radiDocumentosRadicados` (
  `idRadiDocumentosRadicados` int(11) NOT NULL,
  `numeroGuiaRadiDocumento` varchar(50) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL COMMENT 'identificador de la tabla radiRadicados',
  `idCgRegional` int(11) NOT NULL COMMENT 'identificador de la tabla cgRegionales',
  `idCgProveedores` int(11) NOT NULL COMMENT 'identificador de la tabla cgProveedores',
  `idCgEnvioServicio` int(11) NOT NULL COMMENT 'identificador de la tabla cgEnviosServicios',
  `pathFileRadiDocumento` varchar(500) NOT NULL COMMENT 'ruta del documento',
  `estadoRadiDocumento` int(11) NOT NULL DEFAULT 10 COMMENT 'estado 10 activo, 0 inactivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiEnvios`
--

CREATE TABLE `radiEnvios` (
  `idRadiEnvio` int(11) NOT NULL,
  `numeroGuiaRadiEnvio` varchar(20) DEFAULT NULL,
  `observacionRadiEnvio` varchar(500) NOT NULL COMMENT 'Observación ingresada en la correspondencia',
  `idUser` int(11) NOT NULL COMMENT '	Identificador del usuario que cargo el documento',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'identificador de la tabla radiRadicados',
  `idCgRegional` int(11) NOT NULL COMMENT 'identificador de la tabla cgRegionales',
  `idCgProveedores` int(11) NOT NULL COMMENT 'identificador de la tabla cgProveedores',
  `idCgEnvioServicio` int(11) NOT NULL COMMENT 'identificador de la tabla cgEnviosServicios',
  `rutaRadiEnvio` varchar(250) DEFAULT NULL COMMENT '	Ruta donde se encuentra el documento que se cargo para el radicado',
  `extensionRadiEnvio` varchar(20) DEFAULT NULL COMMENT ' Guarda la extención del documento que se esta cargando',
  `estadoRadiEnvio` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado que se le aplica al documento',
  `creacionRadiEnvio` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha en la que se crea el documento'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci COMMENT='transacción envió de documentos en gestión de correspondencia';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiEnviosDevolucion`
--

CREATE TABLE `radiEnviosDevolucion` (
  `idRadiEnvioDevolucion` int(11) NOT NULL COMMENT 'Número único de la tabla de relación',
  `idCgMotivoDevolucion` int(11) NOT NULL COMMENT 'Id de los motivos de devolución',
  `idRadiEnvio` int(11) NOT NULL COMMENT 'Id de los radi envios',
  `fechaDevolucion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de devolución'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiEstadosAnulacion`
--

CREATE TABLE `radiEstadosAnulacion` (
  `idRadiEstadoAnulacion` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `nombreRadiEstadoAnulacion` varchar(80) NOT NULL COMMENT 'Nombre del estado de radicado anulado',
  `estadoRadiEstadosAnulacion` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionRadiEstadosAnulacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del estado '
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiInformadoCliente`
--

CREATE TABLE `radiInformadoCliente` (
  `idRadiInformadoCliente` int(11) NOT NULL COMMENT 'Id de radi informado cliente',
  `idCliente` int(11) NOT NULL COMMENT 'Id cliente',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'Id radicado',
  `estadoRadiInformadoCliente` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado informado cliente',
  `creacionRadiInformadoCliente` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiInformados`
--

CREATE TABLE `radiInformados` (
  `idRadiInformado` int(11) NOT NULL COMMENT 'Número único que identifica la radiInformados',
  `idUser` int(11) DEFAULT NULL COMMENT 'Identificador único para la tabla radiRadicados',
  `idRadiRadicado` int(11) DEFAULT NULL COMMENT 'Identificador único de usuario que es informado',
  `estadoRadiInformado` int(11) DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionRadiInformado` datetime DEFAULT current_timestamp() COMMENT 'Fecha de creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiLogRadicados`
--

CREATE TABLE `radiLogRadicados` (
  `idRadiLogRadicado` int(11) NOT NULL COMMENT 'identificador unico de la tabla',
  `idUser` int(11) NOT NULL COMMENT 'identificador del usuario que esta realizando la transacción con el radicado',
  `idDependencia` int(11) NOT NULL COMMENT 'identificador de la dependencia perteneciente al usuario que esta realizando la transacción con el radicado',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'identificador de la tabla de radicados para saber cual radicado se vio afectado con  la transacción realizada',
  `idTransaccion` int(11) NOT NULL COMMENT 'identificador de la tabla de transacciones de los radicados, ',
  `fechaRadiLogRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha en la que se realizo la transacción del radicado',
  `observacionRadiLogRadicado` varchar(500) NOT NULL COMMENT 'observación de la transacciones que se efectuan con el radicado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRadicadoAnulado`
--

CREATE TABLE `radiRadicadoAnulado` (
  `idRadiRadicadoAnulado` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `rutaActaRadiRadicadoAnulado` varchar(255) DEFAULT NULL COMMENT 'Ruta del archivo del acta',
  `codigoActaRadiRadicadoAnulado` int(11) DEFAULT NULL COMMENT 'Código del archivo del acta',
  `observacionRadiRadicadoAnulado` varchar(255) NOT NULL DEFAULT '' COMMENT 'Observación de la anulación del radicado',
  `idRadicado` int(11) NOT NULL COMMENT 'Id del radicado',
  `fechaRadiRadicadoAnulado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de solicitud de la anulación',
  `idResponsable` int(11) NOT NULL COMMENT 'Id del usuario',
  `idEstado` int(11) NOT NULL DEFAULT 2 COMMENT 'Id del estado radicado',
  `estadoRadiRadicadoAnulado` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo 10 Activo',
  `creacionRadiRadicadoAnulado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del radicado anulado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRadicados`
--

CREATE TABLE `radiRadicados` (
  `idRadiRadicado` int(11) NOT NULL,
  `numeroRadiRadicado` varchar(80) NOT NULL COMMENT 'numero de radicado asignado por el sistema para el tramite de ese documento',
  `asuntoRadiRadicado` varchar(4000) NOT NULL COMMENT 'asunto asignado al radicado que se esta ingresando al sistema',
  `descripcionAnexoRadiRadicado` varchar(80) DEFAULT NULL COMMENT 'Descripción del anexo',
  `foliosRadiRadicado` varchar(20) DEFAULT NULL COMMENT 'guarda la información de los folios que estan asociados al radicado que se esta generando',
  `fechaVencimientoRadiRadicados` date NOT NULL,
  `estadoRadiRadicado` int(11) NOT NULL DEFAULT 10 COMMENT 'estado del radicado',
  `creacionRadiRadicado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'fecha de creación del radicado',
  `idTrdTipoDocumental` int(11) NOT NULL COMMENT 'identificador del tipo docuemntal que se asigna al radicado',
  `user_idCreador` int(11) NOT NULL COMMENT 'Identificador de la tabla user pero asociado al usuario que esta creando el radicado',
  `idTrdDepeUserTramitador` int(11) NOT NULL COMMENT 'Identificador de la tabla de dependencias haciendo referencia a la dependencia a la que pertenece el usuario en cargado de dar tramite al radicado',
  `user_idTramitador` int(11) NOT NULL COMMENT 'Identificador de la tabla user pero haciedo referencia al usuario encargado de tramitar el radicado',
  `user_idTramitadorOld` int(11) DEFAULT NULL COMMENT 'Id del usuario anteriormente encargado de tramitar el radicado',
  `PrioridadRadiRadicados` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'prioridad true = alta, false = baja',
  `idCgTipoRadicado` int(11) NOT NULL COMMENT 'identificador de la tabla tipo de radicado el que nos identifica cual es el tipo de radicado.',
  `idCgMedioRecepcion` int(11) NOT NULL COMMENT 'identificador de la tabla medio de recepción del radicado',
  `idTrdDepeUserCreador` int(11) DEFAULT NULL COMMENT 'identificador de la dependencia del usuario creador del radicado',
  `cantidadCorreosRadicado` int(11) NOT NULL DEFAULT 0 COMMENT 'Conteo de correos del radicado',
  `radicadoOrigen` varchar(80) DEFAULT NULL COMMENT 'Número de radicado origen',
  `fechaDocumentoRadiRadicado` date DEFAULT NULL COMMENT 'Fecha del documento',
  `observacionRadiRadicado` text DEFAULT NULL COMMENT 'Observación',
  `autorizacionRadiRadicados` int(11) DEFAULT 0 COMMENT 'Autoriza envío de respuesta 0: físico - 10: correo electrónico',
  `idRadiRadicadoPadre` int(11) DEFAULT NULL COMMENT 'Radicado padre en la combinación de correspondencia',
  `isRadicado` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Indica si se encuentra radicado o en proceso',
  `numeroFactura` varchar(100) DEFAULT NULL COMMENT 'Número de factura',
  `numeroContrato` varchar(100) DEFAULT NULL COMMENT 'Número de contrato',
  `valorFactura` int(17) DEFAULT NULL COMMENT 'Valor de la factura',
  `numeroCuenta` int(11) DEFAULT NULL,
  `firmaDigital` int(11) DEFAULT 0 COMMENT 'Muestra si se firmo digitalmente',
  `idGdTrdSerie` int(11) DEFAULT NULL COMMENT 'Identificador unico que se le asigna a la serie',
  `idGdTrdSubserie` int(11) DEFAULT NULL COMMENT 'Identificador unico que se le asigna a la subserie',
  `cargoFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL,
  `medioRespuestaFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL,
  `calidadCausanteFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL,
  `empleadorFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL,
  `categoriaPrestacionFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL,
  `categoriaBeneficiarioFonpreconFormRadiRadicado` varchar(500) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRadicadosAsociados`
--

CREATE TABLE `radiRadicadosAsociados` (
  `idRadicadoAsociado` int(11) NOT NULL COMMENT 'Numero unico de la tabla',
  `idRadiAsociado` int(11) NOT NULL COMMENT 'Ids de los radicados asociados al radicado creado',
  `idRadiCreado` int(11) NOT NULL COMMENT 'Id del radicado creado',
  `estadoRadicadoAsociado` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado del radicado asociado',
  `creacionRadicadoAsociado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creacion del radicado asociado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRadicadosDetallePqrs`
--

CREATE TABLE `radiRadicadosDetallePqrs` (
  `idRadiRadicadoDetallePqrs` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL,
  `idCgClasificacionPqrs` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRadicadosResoluciones`
--

CREATE TABLE `radiRadicadosResoluciones` (
  `idRadiRadicadoResoluciones` int(11) NOT NULL,
  `idRadiRadicado` int(11) NOT NULL,
  `numeroRadiRadicadoResolucion` int(11) NOT NULL,
  `fechaRadiRadicadoResolucion` date NOT NULL,
  `valorRadiRadicadoResolucion` double NOT NULL DEFAULT 0,
  `creacionRadiRadicadoResolucion` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `radiRemitentes`
--

CREATE TABLE `radiRemitentes` (
  `idRadiRemitente` int(11) NOT NULL COMMENT 'Identificador de la tabla',
  `idRadiRadicado` int(11) NOT NULL COMMENT 'Este campo referencia el id del radicado que se esta creando y asociando a una persona (cliente o usuario)',
  `idRadiPersona` int(11) NOT NULL COMMENT 'Este campo referencia el id del cliente o del usuarios segun sea el caso cuando se radica.',
  `idTipoPersona` int(11) NOT NULL COMMENT 'Este campo identifica que tipo de persona es la que se esta seleccionando para el radicado',
  `crearRadiRemitente` datetime DEFAULT current_timestamp() COMMENT 'Guarda la fecha en la que se ejecuta la operación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reportesPersonalizados`
--

CREATE TABLE `reportesPersonalizados` (
  `idReportePersonalizado` int(11) NOT NULL COMMENT 'Campo primario de la tabla',
  `nombreReportePersonalizado` varchar(100) NOT NULL COMMENT 'Nombre del reporte',
  `descripcionReportePersonalizado` varchar(500) NOT NULL COMMENT 'Descripción del reporte',
  `jsonReportePersonalizado` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL COMMENT 'Cadena de propiedades del reporte en formato json',
  `estadoReportePersonalizado` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado 10 para habilitado 0 para inhabilitado',
  `creacionReportePersonalizado` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación del reporte',
  `idUserCreadorReportePersonalizado` int(11) NOT NULL COMMENT 'Usuario creador del reporte'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `idRol` int(11) NOT NULL COMMENT 'Número único que identifica la cantida de roles en el sistema',
  `nombreRol` varchar(50) NOT NULL COMMENT 'nombreRol',
  `estadoRol` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionRol` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación',
  `idRolNivelBusqueda` int(11) DEFAULT NULL COMMENT 'Id del nivel de búsqueda'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesModulosOperaciones`
--

CREATE TABLE `rolesModulosOperaciones` (
  `idRolModuloOperacion` int(11) NOT NULL COMMENT 'Número único de la tabla',
  `nombreRolModuloOperacion` varchar(45) NOT NULL COMMENT 'Nombre del modulo o menu a visualizar',
  `classRolModuloOperacion` varchar(40) NOT NULL COMMENT 'Icono del modulo en el menú',
  `rutaRolModuloOperacion` varchar(40) NOT NULL COMMENT 'Ruta donde se dirije el modulo en frontend',
  `ordenModuloOperacion` int(11) DEFAULT 999 COMMENT 'Orden del menú del sistema',
  `estadoRolModuloOperacion` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionModuloOperaciones` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'creacionModuloOperaciones'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesNivelesBusqueda`
--

CREATE TABLE `rolesNivelesBusqueda` (
  `idRolNivelBusqueda` int(11) NOT NULL COMMENT 'Id rol nivel de búsqueda',
  `nombreRolNivelBusqueda` varchar(80) NOT NULL COMMENT 'Nombre nivel de búsqueda',
  `estadoRolNivelBusqueda` int(11) NOT NULL DEFAULT 10 COMMENT 'Estado 0 Inactivo - 10 Activo',
  `creacionRolNivelBusqueda` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesOperaciones`
--

CREATE TABLE `rolesOperaciones` (
  `idRolOperacion` int(11) NOT NULL COMMENT 'Número único de la tabla',
  `nombreRolOperacion` varchar(80) NOT NULL COMMENT 'Ruta de la acción que se ejecuta en backend',
  `aliasRolOperacion` varchar(80) NOT NULL COMMENT 'Nombre de la acción o proceso a realizar',
  `moduloRolOperacion` varchar(80) NOT NULL COMMENT 'Grupo o modulo donde se ejecuta la acción',
  `estadoRolOperacion` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactiva - 10 Activa',
  `creacionRolOperacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha creación de la operación',
  `idRolModuloOperacion` int(11) NOT NULL COMMENT 'Número que indica el modulo de la operación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesTipoDocumental`
--

CREATE TABLE `rolesTipoDocumental` (
  `idRolTipoDocumental` int(11) NOT NULL COMMENT 'Número único de la tabla',
  `idRol` int(11) NOT NULL COMMENT 'Número que indica el rol',
  `idGdTrdTipoDocumental` int(11) NOT NULL COMMENT 'Número que indica el tipo documental'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesTipoRadicado`
--

CREATE TABLE `rolesTipoRadicado` (
  `idRolTipoRadicado` int(11) NOT NULL,
  `idRol` int(11) NOT NULL,
  `idCgTipoRadicado` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci COMMENT='Relación Roles y Tipo de Radicado';

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `rolesTiposOperaciones`
--

CREATE TABLE `rolesTiposOperaciones` (
  `idRolTipoOperacion` int(11) NOT NULL COMMENT 'Número único de la tabla',
  `idRol` int(11) NOT NULL COMMENT 'Número que indica el rol',
  `idRolOperacion` int(11) NOT NULL COMMENT 'Número que indica la operación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposArchivos`
--

CREATE TABLE `tiposArchivos` (
  `idTipoArchivo` int(11) NOT NULL COMMENT 'Campo primario de la tabla',
  `tipoArchivo` varchar(80) DEFAULT NULL COMMENT 'Nombre del tipo de archivo',
  `estadoTipoArchivo` int(11) DEFAULT NULL COMMENT 'status del registro',
  `creacionTipoArchivo` datetime DEFAULT NULL COMMENT 'creacionTipoArchivo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposIdentificacion`
--

CREATE TABLE `tiposIdentificacion` (
  `idTipoIdentificacion` int(11) NOT NULL COMMENT 'Número único para identificar los tipos de identificación',
  `nombreTipoIdentificacion` varchar(50) NOT NULL COMMENT 'Nombre del tipo de identificación',
  `estadoTipoIdentificacion` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionTipoIdentificacion` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `tiposPersonas`
--

CREATE TABLE `tiposPersonas` (
  `idTipoPersona` int(11) NOT NULL COMMENT 'ID primario de la tabla tiposPersonas',
  `tipoPersona` varchar(50) NOT NULL COMMENT 'Tipo de persona',
  `estadoPersona` smallint(2) NOT NULL DEFAULT 10 COMMENT '0 Inactivo 10 Activo',
  `creacionPersona` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Creación del registro'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `user`
--

CREATE TABLE `user` (
  `id` int(11) NOT NULL COMMENT 'Número único que inca la cantidad de usuarios registrados en el sistema',
  `username` varchar(255) NOT NULL COMMENT 'Usuario o correo con que el usuario se registra',
  `auth_key` varchar(32) NOT NULL COMMENT 'auth_key',
  `password_hash` varchar(255) NOT NULL COMMENT 'Contraseña definida por el usuario',
  `password_reset_token` varchar(255) DEFAULT NULL COMMENT 'almacena codigo de restauración de contraseña',
  `email` varchar(255) NOT NULL COMMENT 'Correo que ingresan al sistema',
  `status` smallint(6) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `created_at` int(11) NOT NULL COMMENT 'Fecha de creación convertida en número',
  `updated_at` int(11) NOT NULL COMMENT 'Fecha de modificación convertida en número',
  `fechaVenceToken` datetime NOT NULL COMMENT 'Fecha en la que se vence el token para el usuario',
  `idRol` int(11) NOT NULL COMMENT 'Número que indica el rol del usuario',
  `idUserTipo` int(11) NOT NULL COMMENT 'Número que indica el tipo de usuario',
  `accessToken` varchar(255) NOT NULL COMMENT 'accessToken',
  `intentos` int(11) NOT NULL DEFAULT 0 COMMENT 'Intentos',
  `ldap` int(11) NOT NULL DEFAULT 0 COMMENT '0 false y 10 true',
  `verification_token` varchar(255) DEFAULT NULL,
  `idGdTrdDependencia` int(11) NOT NULL,
  `licenciaAceptada` smallint(6) NOT NULL DEFAULT 0 COMMENT 'Indica si el usuario acepta o no la licencia que ofrece el software'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `userDetalles`
--

CREATE TABLE `userDetalles` (
  `idUserDetalles` int(11) NOT NULL COMMENT 'Número único que identifica la cantidad de usuarios registrados en el sistema',
  `idUser` int(11) NOT NULL COMMENT 'Número que indica el usuario',
  `nombreUserDetalles` varchar(80) NOT NULL COMMENT 'Nombres del usuario',
  `apellidoUserDetalles` varchar(80) NOT NULL COMMENT 'Apellidos del usuario',
  `cargoUserDetalles` varchar(80) NOT NULL COMMENT 'Cargo del usuario',
  `creacionUserDetalles` int(11) NOT NULL COMMENT 'Fecha en que se creo el usuario',
  `idTipoIdentificacion` int(11) NOT NULL COMMENT 'Número que indica el tipo de identificación',
  `documento` varchar(20) NOT NULL COMMENT 'Número de identificación o documeto',
  `firma` varchar(255) DEFAULT NULL COMMENT 'ruta de la firma',
  `estadoUserDetalles` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `userHistoryPassword`
--

CREATE TABLE `userHistoryPassword` (
  `idUserHistoryPassword` int(11) NOT NULL COMMENT 'número único de la tabla',
  `hashUserHistoryPassword` varchar(255) DEFAULT NULL COMMENT 'Contraseña definida por el usuario',
  `idUser` int(11) DEFAULT NULL COMMENT 'ID del usuario',
  `creacionUserHistoryPassword` datetime DEFAULT NULL COMMENT 'Fecha de creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `userTipo`
--

CREATE TABLE `userTipo` (
  `idUserTipo` int(11) NOT NULL COMMENT 'Número único que indica la cantidad de tipos de usuario registrados en el sistema',
  `nombreUserTipo` varchar(80) NOT NULL COMMENT 'Nombre del tipo de usuario',
  `estadoUserTipo` int(11) NOT NULL DEFAULT 10 COMMENT '0 Inactivo - 10 Activo',
  `creacionUserTipo` datetime NOT NULL DEFAULT current_timestamp() COMMENT 'Fecha de creación'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `viewRadiCountByUser`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `viewRadiCountByUser` (
`idUser` int(11)
,`idGdTrdDependencia` int(11)
,`nombreGdTrdDependencia` varchar(255)
,`idUserDetalles` int(11)
,`nombreUserDetalles` varchar(80)
,`apellidoUserDetalles` varchar(80)
,`countRadicados` bigint(21)
,`countSalida` decimal(22,0)
,`countEntrada` decimal(22,0)
,`countPqr` decimal(22,0)
,`countComunicacionInterna` decimal(22,0)
,`countVencidos` decimal(22,0)
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `viewRadiResponseTime`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `viewRadiResponseTime` (
`idTrdDepeUserTramitador` int(11)
,`codigoGdTrdDependencia` varchar(6)
,`nombreGdTrdDependencia` varchar(255)
,`idCgRegional` int(11)
,`user_idTramitador` int(11)
,`idCgTipoRadicado` int(11)
,`idTrdTipoDocumental` int(11)
,`idRadiRadicado` int(11)
,`creacionRadiRadicado` datetime
,`fechaRespuesta` datetime
);

-- --------------------------------------------------------

--
-- Estructura Stand-in para la vista `viewUnsignedDocuments`
-- (Véase abajo para la vista actual)
--
CREATE TABLE `viewUnsignedDocuments` (
`idRadiRadicado` int(11)
,`numeroRadiRadicado` varchar(80)
,`asuntoRadiRadicado` varchar(4000)
,`idTrdDepeUserTramitador` int(11)
,`estadoRadiRadicado` int(11)
);

-- --------------------------------------------------------

--
-- Estructura para la vista `viewRadiCountByUser`
--
DROP TABLE IF EXISTS `viewRadiCountByUser`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `viewRadiCountByUser`  AS SELECT `U`.`id` AS `idUser`, `D`.`idGdTrdDependencia` AS `idGdTrdDependencia`, `D`.`nombreGdTrdDependencia` AS `nombreGdTrdDependencia`, `UD`.`idUserDetalles` AS `idUserDetalles`, `UD`.`nombreUserDetalles` AS `nombreUserDetalles`, `UD`.`apellidoUserDetalles` AS `apellidoUserDetalles`, count(`R`.`idRadiRadicado`) AS `countRadicados`, sum(case when `R`.`idCgTipoRadicado` = 1 then 1 else 0 end) AS `countSalida`, sum(case when `R`.`idCgTipoRadicado` = 2 then 1 else 0 end) AS `countEntrada`, sum(case when `R`.`idCgTipoRadicado` = 3 then 1 else 0 end) AS `countPqr`, sum(case when `R`.`idCgTipoRadicado` = 4 then 1 else 0 end) AS `countComunicacionInterna`, sum(case when `R`.`estadoRadiRadicado` <> 11 and `R`.`estadoRadiRadicado` <> 12 and `R`.`fechaVencimientoRadiRadicados` < curdate() then 1 else 0 end) AS `countVencidos` FROM (((`radiRadicados` `R` join `gdTrdDependencias` `D` on(`D`.`idGdTrdDependencia` = `R`.`idTrdDepeUserTramitador`)) join `user` `U` on(`U`.`id` = `R`.`user_idTramitador`)) join `userDetalles` `UD` on(`UD`.`idUser` = `U`.`id`)) GROUP BY `U`.`id`, `D`.`idGdTrdDependencia`, `D`.`nombreGdTrdDependencia`, `UD`.`idUserDetalles`, `UD`.`nombreUserDetalles`, `UD`.`apellidoUserDetalles` ORDER BY `D`.`idGdTrdDependencia` ASC, `UD`.`idUserDetalles` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `viewRadiResponseTime`
--
DROP TABLE IF EXISTS `viewRadiResponseTime`;

CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `viewRadiResponseTime`  AS SELECT `rr`.`idTrdDepeUserTramitador` AS `idTrdDepeUserTramitador`, `gtd`.`codigoGdTrdDependencia` AS `codigoGdTrdDependencia`, `gtd`.`nombreGdTrdDependencia` AS `nombreGdTrdDependencia`, `gtd`.`idCgRegional` AS `idCgRegional`, `rr`.`user_idTramitador` AS `user_idTramitador`, `rr`.`idCgTipoRadicado` AS `idCgTipoRadicado`, `rr`.`idTrdTipoDocumental` AS `idTrdTipoDocumental`, `rr`.`idRadiRadicado` AS `idRadiRadicado`, `rr`.`creacionRadiRadicado` AS `creacionRadiRadicado`, `rlr`.`fechaRadiLogRadicado` AS `fechaRespuesta` FROM ((`radiRadicados` `rr` join `gdTrdDependencias` `gtd` on(`rr`.`idTrdDepeUserTramitador` = `gtd`.`idGdTrdDependencia`)) left join `radiLogRadicados` `rlr` on(`rr`.`idRadiRadicado` = `rlr`.`idRadiRadicado` and `rlr`.`idTransaccion` = 45)) WHERE `rr`.`idRadiRadicadoPadre` is null AND (`rlr`.`idTransaccion` = 45 OR `rlr`.`idTransaccion` is null) ORDER BY `rr`.`idRadiRadicado` ASC ;

-- --------------------------------------------------------

--
-- Estructura para la vista `viewUnsignedDocuments`
--
DROP TABLE IF EXISTS `viewUnsignedDocuments`;

CREATE ALGORITHM=UNDEFINED  SQL SECURITY DEFINER VIEW `viewUnsignedDocuments`  AS SELECT `RR`.`idRadiRadicado` AS `idRadiRadicado`, `RR`.`numeroRadiRadicado` AS `numeroRadiRadicado`, `RR`.`asuntoRadiRadicado` AS `asuntoRadiRadicado`, `RR`.`idTrdDepeUserTramitador` AS `idTrdDepeUserTramitador`, `RR`.`estadoRadiRadicado` AS `estadoRadiRadicado` FROM (`radiRadicados` `RR` join `radiDocumentosPrincipales` `RDP` on(`RDP`.`idRadiRadicado` = `RR`.`idRadiRadicado`)) WHERE `RDP`.`estadoRadiDocumentoPrincipal` in (7,8) AND !(`RR`.`idRadiRadicado` in (select `radiDocumentosPrincipales`.`idRadiRadicado` from `radiDocumentosPrincipales` where `radiDocumentosPrincipales`.`estadoRadiDocumentoPrincipal` = 9)) GROUP BY `RR`.`idRadiRadicado`, `RR`.`numeroRadiRadicado`, `RR`.`asuntoRadiRadicado`, `RR`.`idTrdDepeUserTramitador` ;

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `cgActividadEconomicaPqrs`
--
ALTER TABLE `cgActividadEconomicaPqrs`
  ADD PRIMARY KEY (`idCgActividadEconomicaPqrs`);

--
-- Indices de la tabla `cgClasificacionPqrs`
--
ALTER TABLE `cgClasificacionPqrs`
  ADD PRIMARY KEY (`idCgClasificacionPqrs`);

--
-- Indices de la tabla `cgCondicionDiscapacidadPqrs`
--
ALTER TABLE `cgCondicionDiscapacidadPqrs`
  ADD PRIMARY KEY (`idCgCondicionDiscapacidadPqrs`);

--
-- Indices de la tabla `cgConsecutivosRadicados`
--
ALTER TABLE `cgConsecutivosRadicados`
  ADD PRIMARY KEY (`idCgConsecutivoRadicado`),
  ADD KEY `cgConsecutivosRadicados_FK` (`idCgTipoRadicado`),
  ADD KEY `cgConsecutivosRadicados_FK_1` (`idCgRegional`);

--
-- Indices de la tabla `cgDiasNoLaborados`
--
ALTER TABLE `cgDiasNoLaborados`
  ADD PRIMARY KEY (`idCgDiaNoLaborado`);

--
-- Indices de la tabla `cgEncuestaPreguntas`
--
ALTER TABLE `cgEncuestaPreguntas`
  ADD PRIMARY KEY (`idCgEncuestaPregunta`),
  ADD KEY `fk_CgEncuestaPreguntas_idCgEncuestaPregunta` (`idCgEncuesta`);

--
-- Indices de la tabla `cgEncuestas`
--
ALTER TABLE `cgEncuestas`
  ADD PRIMARY KEY (`idCgEncuesta`),
  ADD KEY `fk_CgEncuestas_idUserCgEncuesta` (`idUserCreador`);

--
-- Indices de la tabla `cgEnvioServicios`
--
ALTER TABLE `cgEnvioServicios`
  ADD PRIMARY KEY (`idCgEnvioServicio`);

--
-- Indices de la tabla `cgEscolaridadPqrs`
--
ALTER TABLE `cgEscolaridadPqrs`
  ADD PRIMARY KEY (`idCgEscolaridadPqrs`);

--
-- Indices de la tabla `cgEstratoPqrs`
--
ALTER TABLE `cgEstratoPqrs`
  ADD PRIMARY KEY (`idCgEstratoPqrs`);

--
-- Indices de la tabla `cgEtiquetaRadicacion`
--
ALTER TABLE `cgEtiquetaRadicacion`
  ADD PRIMARY KEY (`idCgEtiquetaRadicacion`);

--
-- Indices de la tabla `cgFirmasDocs`
--
ALTER TABLE `cgFirmasDocs`
  ADD PRIMARY KEY (`idCgFirmaDoc`);

--
-- Indices de la tabla `cgFormulariosPqrs`
--
ALTER TABLE `cgFormulariosPqrs`
  ADD PRIMARY KEY (`idCgFormulariosPqrs`);

--
-- Indices de la tabla `cgFormulariosPqrsDetalle`
--
ALTER TABLE `cgFormulariosPqrsDetalle`
  ADD PRIMARY KEY (`idCgFormulariosPqrsDetalle`),
  ADD KEY `cgformulariospqrsdetalle_ibfk_1` (`idCgFormulariosPqrs`);

--
-- Indices de la tabla `cgFormulariosPqrsDetalleDocumentos`
--
ALTER TABLE `cgFormulariosPqrsDetalleDocumentos`
  ADD PRIMARY KEY (`idCgFormulariosPqrsDetalleDocumentos`),
  ADD KEY `cgformulariospqrsdetalledocumentos_ibfk_1` (`idCgFormulariosPqrsDetalle`);

--
-- Indices de la tabla `cgGeneral`
--
ALTER TABLE `cgGeneral`
  ADD PRIMARY KEY (`idCgGeneral`),
  ADD KEY `fk_dependencia_cgGeneral` (`idDependenciaPqrsCgGeneral`);

--
-- Indices de la tabla `cgGeneralBasesDatos`
--
ALTER TABLE `cgGeneralBasesDatos`
  ADD PRIMARY KEY (`idCgGeneralBasesDatos`);

--
-- Indices de la tabla `cgGeneroPqrs`
--
ALTER TABLE `cgGeneroPqrs`
  ADD PRIMARY KEY (`idCgGeneroPqrs`);

--
-- Indices de la tabla `cgGrupoEtnicoPqrs`
--
ALTER TABLE `cgGrupoEtnicoPqrs`
  ADD PRIMARY KEY (`idCgGrupoEtnicoPqrs`);

--
-- Indices de la tabla `cgGrupoInteresPqrs`
--
ALTER TABLE `cgGrupoInteresPqrs`
  ADD PRIMARY KEY (`idCgGrupoInteresPqrs`);

--
-- Indices de la tabla `cgGrupoSisbenPqrs`
--
ALTER TABLE `cgGrupoSisbenPqrs`
  ADD PRIMARY KEY (`idCgGrupoSisbenPqrs`);

--
-- Indices de la tabla `cgGruposUsuarios`
--
ALTER TABLE `cgGruposUsuarios`
  ADD PRIMARY KEY (`idCgGrupoUsuarios`),
  ADD UNIQUE KEY `cgGruposUsuarios_nombre` (`nombreCgGrupoUsuarios`);

--
-- Indices de la tabla `cgHorarioLaboral`
--
ALTER TABLE `cgHorarioLaboral`
  ADD PRIMARY KEY (`idCgHorarioLaboral`);

--
-- Indices de la tabla `cgMediosRecepcion`
--
ALTER TABLE `cgMediosRecepcion`
  ADD PRIMARY KEY (`idCgMedioRecepcion`),
  ADD UNIQUE KEY `nombreCgMedioRecepcion_UNIQUE` (`nombreCgMedioRecepcion`);

--
-- Indices de la tabla `cgMotivosDevolucion`
--
ALTER TABLE `cgMotivosDevolucion`
  ADD PRIMARY KEY (`idCgMotivoDevolucion`);

--
-- Indices de la tabla `cgNumeroRadicado`
--
ALTER TABLE `cgNumeroRadicado`
  ADD PRIMARY KEY (`idCgNumeroRadicado`),
  ADD UNIQUE KEY `estructuraCgNumeroRadicado_UNIQUE` (`estructuraCgNumeroRadicado`);

--
-- Indices de la tabla `cgPlantillas`
--
ALTER TABLE `cgPlantillas`
  ADD PRIMARY KEY (`idCgPlantilla`),
  ADD UNIQUE KEY `cgPlantillas_UN` (`nombreCgPlantilla`),
  ADD KEY `idUser` (`idUser`);

--
-- Indices de la tabla `cgPlantillaVariables`
--
ALTER TABLE `cgPlantillaVariables`
  ADD PRIMARY KEY (`idCgPlantillaVariable`);

--
-- Indices de la tabla `cgProveedores`
--
ALTER TABLE `cgProveedores`
  ADD PRIMARY KEY (`idCgProveedor`),
  ADD UNIQUE KEY `nombreCgProveedor` (`nombreCgProveedor`);

--
-- Indices de la tabla `cgProveedoresExternos`
--
ALTER TABLE `cgProveedoresExternos`
  ADD PRIMARY KEY (`idCgProveedorExterno`),
  ADD KEY `userCreadorProveedorExterno` (`userCgCreadorProveedorExterno`),
  ADD KEY `userCgProveedorExterno` (`userCgProveedorExterno`);

--
-- Indices de la tabla `cgProveedoresRegional`
--
ALTER TABLE `cgProveedoresRegional`
  ADD PRIMARY KEY (`idCgProveedorRegional`),
  ADD KEY `fk_cgRegional_cgProveedorRegional` (`idCgRegional`),
  ADD KEY `fk_cgProveedor_cgProveedorRegional` (`idCgProveedor`);

--
-- Indices de la tabla `cgProveedoresServicios`
--
ALTER TABLE `cgProveedoresServicios`
  ADD PRIMARY KEY (`idCgProveedoresServicios`),
  ADD KEY `fk_cgProveedores_cgProveedorServicio` (`idCgProveedor`),
  ADD KEY `fk_cgEnvioServicios_cgProveedorServicio` (`idCgEnvioServicios`);

--
-- Indices de la tabla `cgRangoEdadPqrs`
--
ALTER TABLE `cgRangoEdadPqrs`
  ADD PRIMARY KEY (`idCgRangoEdadPqrs`);

--
-- Indices de la tabla `cgRegionales`
--
ALTER TABLE `cgRegionales`
  ADD PRIMARY KEY (`idCgRegional`),
  ADD UNIQUE KEY `nombreCgRegional` (`nombreCgRegional`),
  ADD KEY `fk_cgRegionales_nivelGeografico3` (`idNivelGeografico3`);

--
-- Indices de la tabla `cgReportes`
--
ALTER TABLE `cgReportes`
  ADD PRIMARY KEY (`idCgReporte`);

--
-- Indices de la tabla `cgTipoRadicadoDocumental`
--
ALTER TABLE `cgTipoRadicadoDocumental`
  ADD PRIMARY KEY (`idCgTipoRadicadoDocumental`),
  ADD KEY `fk_tipoRadicado_cgTipoRadicadoDocumental` (`idCgTipoRadicado`),
  ADD KEY `fk_tipoDocumental_cgTipoRadicadoDocumental` (`idGdTrdTipoDocumental`);

--
-- Indices de la tabla `cgTiposGruposUsuarios`
--
ALTER TABLE `cgTiposGruposUsuarios`
  ADD PRIMARY KEY (`idCgTipoGrupoUsuarios`),
  ADD KEY `FK_cgTiposGrupoUsuarios_idGrupoUsuario` (`idCgGrupoUsuarios`),
  ADD KEY `FK_cgTiposGrupoUsuarios_idUser` (`idUser`);

--
-- Indices de la tabla `cgTiposRadicados`
--
ALTER TABLE `cgTiposRadicados`
  ADD PRIMARY KEY (`idCgTipoRadicado`);

--
-- Indices de la tabla `cgTiposRadicadosResoluciones`
--
ALTER TABLE `cgTiposRadicadosResoluciones`
  ADD PRIMARY KEY (`idCgTiposRadicadosResoluciones`),
  ADD KEY `idCgTipoRadicado` (`idCgTipoRadicado`);

--
-- Indices de la tabla `cgTiposRadicadosTransacciones`
--
ALTER TABLE `cgTiposRadicadosTransacciones`
  ADD PRIMARY KEY (`idCgTiposRadicadosTransacciones`),
  ADD KEY `fk_cgTiposRadicadosTransacciones_idTipoRadicado_idx` (`idCgTipoRadicado`),
  ADD KEY `fk_cgTiposRadicadosTransacciones_idTransaccion_idx` (`idCgTransaccionRadicado`);

--
-- Indices de la tabla `cgTramites`
--
ALTER TABLE `cgTramites`
  ADD PRIMARY KEY (`idCgTramite`);

--
-- Indices de la tabla `cgTransaccionesRadicados`
--
ALTER TABLE `cgTransaccionesRadicados`
  ADD PRIMARY KEY (`idCgTransaccionRadicado`);

--
-- Indices de la tabla `cgTrd`
--
ALTER TABLE `cgTrd`
  ADD PRIMARY KEY (`idCgTrd`),
  ADD KEY `fk_cgTrd_cgTrdMascaras` (`idMascaraCgTrd`);

--
-- Indices de la tabla `cgTrdMascaras`
--
ALTER TABLE `cgTrdMascaras`
  ADD PRIMARY KEY (`idCgTrdMascara`);

--
-- Indices de la tabla `cgVulnerabilidadPqrs`
--
ALTER TABLE `cgVulnerabilidadPqrs`
  ADD PRIMARY KEY (`idCgVulnerabilidadPqrs`);

--
-- Indices de la tabla `clienteEncuestas`
--
ALTER TABLE `clienteEncuestas`
  ADD PRIMARY KEY (`idClienteEncuesta`),
  ADD KEY `fk_clienteEncuestas_idCgEncuesta` (`idCgEncuesta`);

--
-- Indices de la tabla `clientes`
--
ALTER TABLE `clientes`
  ADD PRIMARY KEY (`idCliente`) USING BTREE,
  ADD KEY `fk_clientes_tipoPersona1_idx` (`idTipoPersona`),
  ADD KEY `fk_clientes_nivelGeografico1_idx` (`idNivelGeografico1`),
  ADD KEY `fk_clientes_nivelGeografico3_idx` (`idNivelGeografico3`) USING BTREE,
  ADD KEY `fk_clientes_idNivelGeografico2_idx` (`idNivelGeografico2`) USING BTREE;

--
-- Indices de la tabla `clientesCiudadanosDetalles`
--
ALTER TABLE `clientesCiudadanosDetalles`
  ADD PRIMARY KEY (`idClienteCiudadanoDetalle`) USING BTREE,
  ADD KEY `fk_clientesCiudadanosDetalles_idCliente` (`idCliente`),
  ADD KEY `idUser` (`idUser`),
  ADD KEY `idTipoIdentificacion` (`idTipoIdentificacion`),
  ADD KEY `fk_actividad_economica` (`actEcomicaClienteCiudadanoDetalle`),
  ADD KEY `fk_condicion_discapacidad` (`condDiscapacidadClienteCiudadanoDetalle`),
  ADD KEY `fk_estrato` (`estratoClienteCiudadanoDetalle`),
  ADD KEY `fk_grupo_interes` (`grupoInteresClienteCiudadanoDetalle`),
  ADD KEY `fk_grupo_sisben` (`grupoSisbenClienteCiudadanoDetalle`),
  ADD KEY `fk_escolaridad` (`escolaridadClienteCiudadanoDetalle`);

--
-- Indices de la tabla `csInicial`
--
ALTER TABLE `csInicial`
  ADD PRIMARY KEY (`idCsInicial`);

--
-- Indices de la tabla `csParams`
--
ALTER TABLE `csParams`
  ADD PRIMARY KEY (`idCsParams`);

--
-- Indices de la tabla `encuestaCalificaciones`
--
ALTER TABLE `encuestaCalificaciones`
  ADD PRIMARY KEY (`idEncuestaCalificaciones`),
  ADD KEY `fk_encuestaCalificaciones_idCgEncuestaPregunta` (`idCgEncuestaPregunta`),
  ADD KEY `fk_encuestaCalificaciones_idClienteEncuesta` (`idClienteEncuesta`);

--
-- Indices de la tabla `gaArchivo`
--
ALTER TABLE `gaArchivo`
  ADD PRIMARY KEY (`idgaArchivo`),
  ADD KEY `idGaEdificio` (`idGaEdificio`),
  ADD KEY `idGaPiso` (`idGaPiso`),
  ADD KEY `idGaBodega` (`idGaBodega`),
  ADD KEY `gaArchivo_ibfk_1` (`idGdExpediente`);

--
-- Indices de la tabla `gaBodega`
--
ALTER TABLE `gaBodega`
  ADD PRIMARY KEY (`idGaBodega`),
  ADD KEY `fk_gaBodega_gaPiso` (`idGaPiso`);

--
-- Indices de la tabla `gaBodegaContenido`
--
ALTER TABLE `gaBodegaContenido`
  ADD PRIMARY KEY (`idGaBodegaContenido`),
  ADD KEY `fk_gaBodegaContenido_gaBodega` (`idGaBodega`);

--
-- Indices de la tabla `gaEdificio`
--
ALTER TABLE `gaEdificio`
  ADD PRIMARY KEY (`idGaEdificio`),
  ADD KEY `fk_gaEdificio_nivelGeografico2` (`idDepartamentoGaEdificio`),
  ADD KEY `fk_gaEdificio_nivelGeografico3` (`idMunicipioGaEdificio`);

--
-- Indices de la tabla `gaHistoricoPrestamo`
--
ALTER TABLE `gaHistoricoPrestamo`
  ADD PRIMARY KEY (`idGaHistoricoPrestamo`),
  ADD KEY `fk_gaPrestamoHistorico_gaPrestamo` (`idGaPrestamo`),
  ADD KEY `idUser` (`idUser`),
  ADD KEY `idGdTrdDependencia` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gaHistoricoPrestamoExpediente`
--
ALTER TABLE `gaHistoricoPrestamoExpediente`
  ADD PRIMARY KEY (`idGaHistoricoPrestamoExpediente`),
  ADD KEY `fk_gaPrestamoHistorico_gaPrestamoExpediente` (`idGaPrestamoExpediente`),
  ADD KEY `idUser` (`idUser`),
  ADD KEY `idGdTrdDependencia` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gaPiso`
--
ALTER TABLE `gaPiso`
  ADD PRIMARY KEY (`idGaPiso`),
  ADD KEY `fk_gaPiso_gaEdificio` (`idGaEdificio`);

--
-- Indices de la tabla `gaPrestamos`
--
ALTER TABLE `gaPrestamos`
  ADD PRIMARY KEY (`idGaPrestamo`),
  ADD KEY `fk_gaPrestamo_gdExpedientesInclusion` (`idGdExpedienteInclusion`),
  ADD KEY `fk_gaPrestamo_user` (`idUser`),
  ADD KEY `fk_gaPrestamo_gdTrdDependencias` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gaPrestamosExpedientes`
--
ALTER TABLE `gaPrestamosExpedientes`
  ADD PRIMARY KEY (`idGaPrestamoExpediente`),
  ADD KEY `fk_gaPrestamoExpedientes_gdExpedientes` (`idGdExpediente`),
  ADD KEY `fk_gaPrestamoExpedientes_user` (`idUser`),
  ADD KEY `fk_gaPrestamoExpedientes_gdTrdDependencias` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gdExpedienteDocumentos`
--
ALTER TABLE `gdExpedienteDocumentos`
  ADD PRIMARY KEY (`idGdExpedienteDocumento`),
  ADD KEY `fk_gdexpedientedocumentos_idGdExpediente` (`idGdExpediente`),
  ADD KEY `fk_gdexpedientedocumentos_idGdTrdTipodocumental` (`idGdTrdTipoDocumental`),
  ADD KEY `fk_gdexpedientedocumentos_idUser` (`idUser`);

--
-- Indices de la tabla `gdExpedientes`
--
ALTER TABLE `gdExpedientes`
  ADD PRIMARY KEY (`idGdExpediente`),
  ADD UNIQUE KEY `un_gdExpedientes_numeroGdExpediente` (`numeroGdExpediente`),
  ADD UNIQUE KEY `un_gdExpedientes_nombregdExpediente` (`nombreGdExpediente`,`idGdTrdDependencia`),
  ADD KEY `fk_gdExpedientes_idUser` (`idUser`),
  ADD KEY `fk_gdExpedientes_idGdTrdSerie` (`idGdTrdSerie`),
  ADD KEY `fk_gdExpedientes_idGdTrdSubserie` (`idGdTrdSubserie`),
  ADD KEY `fk_gdExpedientes_idGdTrdDependencia` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gdExpedientesDependencias`
--
ALTER TABLE `gdExpedientesDependencias`
  ADD PRIMARY KEY (`idGdExpedientesDependencias`),
  ADD KEY `idGdExpediente` (`idGdExpediente`),
  ADD KEY `idGdTrdDependencia` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gdExpedientesInclusion`
--
ALTER TABLE `gdExpedientesInclusion`
  ADD PRIMARY KEY (`idGdExpedienteInclusion`),
  ADD KEY `fk_gdExpedientesInclusion_idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `fk_gdExpedientesInclusion_idGdExpediente` (`idGdExpediente`);

--
-- Indices de la tabla `gdFirmasMultiples`
--
ALTER TABLE `gdFirmasMultiples`
  ADD PRIMARY KEY (`idGdFirmaMultiple`),
  ADD KEY `idradiDocPrincipal` (`idradiDocPrincipal`);

--
-- Indices de la tabla `gdFirmasQr`
--
ALTER TABLE `gdFirmasQr`
  ADD PRIMARY KEY (`idGdFirmasQr`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `idUser` (`idUser`),
  ADD KEY `gdFirmasQr_FK` (`idDocumento`);

--
-- Indices de la tabla `gdHistoricoExpedientes`
--
ALTER TABLE `gdHistoricoExpedientes`
  ADD PRIMARY KEY (`idGdHistoricoExpediente`),
  ADD KEY `fk_gdHistoricoExpedientes_idGdExpedienteInclusion` (`idGdExpediente`),
  ADD KEY `fk_gdHistoricoExpedientes_idUser` (`idUser`),
  ADD KEY `fk_gdHistoricoExpedientesa_idGdTrdDependencia` (`idGdTrdDependencia`);

--
-- Indices de la tabla `gdIndices`
--
ALTER TABLE `gdIndices`
  ADD PRIMARY KEY (`idGdIndice`),
  ADD KEY `fk_gdIndices_idGdExpedienteInclusion` (`idGdExpedienteInclusion`),
  ADD KEY `fk_gdIndice_idGdTrdTipoDocumental` (`idGdTrdTipoDocumental`),
  ADD KEY `fk_gdIndices_idGdReferenciaCruzada` (`idGdReferenciaCruzada`);

--
-- Indices de la tabla `gdReferenciasCruzadas`
--
ALTER TABLE `gdReferenciasCruzadas`
  ADD PRIMARY KEY (`idGdReferenciaCruzada`),
  ADD KEY `gdReferenciasCruzadas_FK` (`idUserGdReferenciaCruzada`),
  ADD KEY `gdReferenciasCruzadas_FK_1` (`idGdExpediente`),
  ADD KEY `gdReferenciasCruzadas_FK_2` (`idGdTrdTipoDocumental`);

--
-- Indices de la tabla `gdReferenciaTiposAnexos`
--
ALTER TABLE `gdReferenciaTiposAnexos`
  ADD PRIMARY KEY (`idGdReferenciaTipoAnexo`),
  ADD KEY `gdReferenciaTiposAnexos_FK` (`idGdReferenciaCruzada`),
  ADD KEY `gdReferenciaTiposAnexos_FK_1` (`idGdTipoAnexoFisico`);

--
-- Indices de la tabla `gdTiposAnexosFisicos`
--
ALTER TABLE `gdTiposAnexosFisicos`
  ADD PRIMARY KEY (`idGdTipoAnexoFisico`);

--
-- Indices de la tabla `gdTrd`
--
ALTER TABLE `gdTrd`
  ADD PRIMARY KEY (`idGdTrd`),
  ADD KEY `fk_gdTrd_gdTrdDependencias` (`idGdTrdDependencia`),
  ADD KEY `fk_gdTrd_gdTrdSeries` (`idGdTrdSerie`),
  ADD KEY `fk_gdTrd_gdTrdSubseries` (`idGdTrdSubserie`),
  ADD KEY `fk_gdTrd_gdTrdTiposDocumentales` (`idGdTrdTipoDocumental`);

--
-- Indices de la tabla `gdTrdDependencias`
--
ALTER TABLE `gdTrdDependencias`
  ADD PRIMARY KEY (`idGdTrdDependencia`),
  ADD KEY `fk_gdTrdDependencias_cgRegionales` (`idCgRegional`);

--
-- Indices de la tabla `gdTrdDependenciasTmp`
--
ALTER TABLE `gdTrdDependenciasTmp`
  ADD PRIMARY KEY (`idGdTrdDependenciaTmp`);

--
-- Indices de la tabla `gdTrdSeries`
--
ALTER TABLE `gdTrdSeries`
  ADD PRIMARY KEY (`idGdTrdSerie`);

--
-- Indices de la tabla `gdTrdSeriesTmp`
--
ALTER TABLE `gdTrdSeriesTmp`
  ADD PRIMARY KEY (`idGdTrdSerieTmp`);

--
-- Indices de la tabla `gdTrdSubseries`
--
ALTER TABLE `gdTrdSubseries`
  ADD PRIMARY KEY (`idGdTrdSubserie`);

--
-- Indices de la tabla `gdTrdSubseriesTmp`
--
ALTER TABLE `gdTrdSubseriesTmp`
  ADD PRIMARY KEY (`idGdTrdSubserieTmp`);

--
-- Indices de la tabla `gdTrdTiposDocumentales`
--
ALTER TABLE `gdTrdTiposDocumentales`
  ADD PRIMARY KEY (`idGdTrdTipoDocumental`);

--
-- Indices de la tabla `gdTrdTiposDocumentalesTmp`
--
ALTER TABLE `gdTrdTiposDocumentalesTmp`
  ADD PRIMARY KEY (`idGdTrdTipoDocumentalTmp`);

--
-- Indices de la tabla `gdTrdTmp`
--
ALTER TABLE `gdTrdTmp`
  ADD PRIMARY KEY (`idGdTrdTmp`),
  ADD KEY `fk_gdTrdTmp_gdTrdDependenciasTmp` (`idGdTrdDependenciaTmp`),
  ADD KEY `fk_gdTrdTmp_gdTrdSeriesTmp` (`idGdTrdSerieTmp`),
  ADD KEY `fk_gdTrdTmp_gdTrdSubseriesTmp` (`idGdTrdSubserieTmp`),
  ADD KEY `fk_gdTrdTmp_gdTrdTiposDocumentalesTmp` (`idGdTrdTipoDocumentalTmp`);

--
-- Indices de la tabla `infoFirmaDigitalDocPrincipales`
--
ALTER TABLE `infoFirmaDigitalDocPrincipales`
  ADD PRIMARY KEY (`idFirmaDigital`),
  ADD KEY `fk_infoFirmaDigitalDocPrincipales_user_idx` (`idUser`),
  ADD KEY `fk_infoFirmaDigitalDocPrincipales_doc_info` (`idradiDocumentoPrincipal`);

--
-- Indices de la tabla `initCgConfigFirmas`
--
ALTER TABLE `initCgConfigFirmas`
  ADD PRIMARY KEY (`idInitCgConfigFirma`),
  ADD KEY `fk_InitCgParamFirma` (`idInitCgParamFirma`),
  ADD KEY `fk_initCgEntidadFirma` (`idInitCgEntidadFirma`);

--
-- Indices de la tabla `initCgCordenadasFirma`
--
ALTER TABLE `initCgCordenadasFirma`
  ADD PRIMARY KEY (`idInitCgCordenadaFirma`);

--
-- Indices de la tabla `initCgEntidadesFirma`
--
ALTER TABLE `initCgEntidadesFirma`
  ADD PRIMARY KEY (`idInitCgEntidadFirma`);

--
-- Indices de la tabla `initCgParamsFirma`
--
ALTER TABLE `initCgParamsFirma`
  ADD PRIMARY KEY (`idInitCgParamFirma`);

--
-- Indices de la tabla `log`
--
ALTER TABLE `log`
  ADD PRIMARY KEY (`idLog`),
  ADD KEY `fk_log_user` (`idUser`);

--
-- Indices de la tabla `nivelGeografico1`
--
ALTER TABLE `nivelGeografico1`
  ADD PRIMARY KEY (`nivelGeografico1`);

--
-- Indices de la tabla `nivelGeografico2`
--
ALTER TABLE `nivelGeografico2`
  ADD PRIMARY KEY (`nivelGeografico2`),
  ADD KEY `fk_nivelGeografico2_nivelGeografico1` (`idNivelGeografico1`);

--
-- Indices de la tabla `nivelGeografico3`
--
ALTER TABLE `nivelGeografico3`
  ADD PRIMARY KEY (`nivelGeografico3`),
  ADD KEY `fk_nivelGeografico3_nivelGeografico2` (`idNivelGeografico2`);

--
-- Indices de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  ADD PRIMARY KEY (`idNotificacion`),
  ADD KEY `fk_userCreador_user` (`idUserCreador`),
  ADD KEY `fk_userNotificado_user` (`idUserNotificado`);

--
-- Indices de la tabla `ocrDatos`
--
ALTER TABLE `ocrDatos`
  ADD PRIMARY KEY (`idOcrDatos`);

--
-- Indices de la tabla `ocrSphinxIndexMeta`
--
ALTER TABLE `ocrSphinxIndexMeta`
  ADD PRIMARY KEY (`idOcrSphinxIndexMeta`);

--
-- Indices de la tabla `ocrSphinxIndexRemove`
--
ALTER TABLE `ocrSphinxIndexRemove`
  ADD PRIMARY KEY (`idOcrSphinxIndexRemove`);

--
-- Indices de la tabla `radiAgendaRadicados`
--
ALTER TABLE `radiAgendaRadicados`
  ADD PRIMARY KEY (`idRadiAgendaRadicados`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`);

--
-- Indices de la tabla `radiCorreosRadicados`
--
ALTER TABLE `radiCorreosRadicados`
  ADD PRIMARY KEY (`idRadiCorreosRadicados`),
  ADD KEY `radiCorreosRadicados_FK` (`idRadiRadicado`);

--
-- Indices de la tabla `radiDetallePqrsAnonimo`
--
ALTER TABLE `radiDetallePqrsAnonimo`
  ADD PRIMARY KEY (`idRadiDetallePqrsAnonimo`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `idNivelGeografico1` (`idNivelGeografico1`),
  ADD KEY `idNivelGeografico2` (`idNivelGeografico2`),
  ADD KEY `idNivelGeografico3` (`idNivelGeografico3`);

--
-- Indices de la tabla `radiDocumentos`
--
ALTER TABLE `radiDocumentos`
  ADD PRIMARY KEY (`idRadiDocumento`),
  ADD KEY `fk_radiDocumentos_idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `fk_radiDocumentos_tipoDocumental_idx` (`idGdTrdTipoDocumental`),
  ADD KEY `fk_radiDocumentos_user_idx` (`idUser`);

--
-- Indices de la tabla `radiDocumentosPrincipales`
--
ALTER TABLE `radiDocumentosPrincipales`
  ADD PRIMARY KEY (`idradiDocumentoPrincipal`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `idUser` (`idUser`),
  ADD KEY `radiDocumentosPrincipales_idRadiRespuesta` (`idRadiRespuesta`);

--
-- Indices de la tabla `radiDocumentosRadicados`
--
ALTER TABLE `radiDocumentosRadicados`
  ADD PRIMARY KEY (`idRadiDocumentosRadicados`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `idCgEnvioServicio` (`idCgEnvioServicio`),
  ADD KEY `idCgProveedores` (`idCgProveedores`),
  ADD KEY `idCgRegional` (`idCgRegional`);

--
-- Indices de la tabla `radiEnvios`
--
ALTER TABLE `radiEnvios`
  ADD PRIMARY KEY (`idRadiEnvio`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `idCgEnvioServicio` (`idCgEnvioServicio`),
  ADD KEY `idCgProveedores` (`idCgProveedores`),
  ADD KEY `radiEnvios_ibfk_4` (`idCgRegional`),
  ADD KEY `idUser` (`idUser`);

--
-- Indices de la tabla `radiEnviosDevolucion`
--
ALTER TABLE `radiEnviosDevolucion`
  ADD PRIMARY KEY (`idRadiEnvioDevolucion`),
  ADD KEY `fk_radiEnvios_cgMotivosEnvios` (`idRadiEnvio`),
  ADD KEY `fk_cgMotivoDevolucion_cgMotivosEnvios` (`idCgMotivoDevolucion`);

--
-- Indices de la tabla `radiEstadosAnulacion`
--
ALTER TABLE `radiEstadosAnulacion`
  ADD PRIMARY KEY (`idRadiEstadoAnulacion`);

--
-- Indices de la tabla `radiInformadoCliente`
--
ALTER TABLE `radiInformadoCliente`
  ADD PRIMARY KEY (`idRadiInformadoCliente`),
  ADD KEY `fk_cliente_radiInformadoCliente` (`idCliente`),
  ADD KEY `fk_radicado_radiInformadoCliente` (`idRadiRadicado`);

--
-- Indices de la tabla `radiInformados`
--
ALTER TABLE `radiInformados`
  ADD PRIMARY KEY (`idRadiInformado`),
  ADD KEY `radiInformados_FK` (`idUser`),
  ADD KEY `radiInformados_FK_1` (`idRadiRadicado`);

--
-- Indices de la tabla `radiLogRadicados`
--
ALTER TABLE `radiLogRadicados`
  ADD PRIMARY KEY (`idRadiLogRadicado`),
  ADD KEY `fk_radiLogRadicados_idUser_idx` (`idUser`),
  ADD KEY `fk_radiLogRadicados_idDependencia_idx` (`idDependencia`),
  ADD KEY `fk_radiLogRadicados_idTransaccion_idx` (`idTransaccion`),
  ADD KEY `fk_radiLogRadicados_idRadicado_idx` (`idRadiRadicado`);

--
-- Indices de la tabla `radiRadicadoAnulado`
--
ALTER TABLE `radiRadicadoAnulado`
  ADD PRIMARY KEY (`idRadiRadicadoAnulado`),
  ADD KEY `fk_radiRadicadoAnulado_radiRadicados` (`idRadicado`),
  ADD KEY `fk_radiRadicadoAnulado_user` (`idResponsable`),
  ADD KEY `fk_radiRadicadoAnulado_estado` (`idEstado`);

--
-- Indices de la tabla `radiRadicados`
--
ALTER TABLE `radiRadicados`
  ADD PRIMARY KEY (`idRadiRadicado`),
  ADD UNIQUE KEY `idRadiRadicado_UNIQUE` (`idRadiRadicado`),
  ADD UNIQUE KEY `numeroRadiRadicado_UNIQUE` (`numeroRadiRadicado`),
  ADD KEY `fk_radiRadicados_trdTiposDocumentales_idx` (`idTrdTipoDocumental`),
  ADD KEY `fk_radiRadicados_user_idx` (`user_idCreador`),
  ADD KEY `fk_radiRadicados_radiPrioridades_idx` (`PrioridadRadiRadicados`),
  ADD KEY `fk_radiRadicados_cgTiposRadicados_idx` (`idCgTipoRadicado`),
  ADD KEY `fk_radiRadicados_cgMediosRecepcion_idx` (`idCgMedioRecepcion`),
  ADD KEY `fk_radiRadicados_dependencia_idx` (`idTrdDepeUserCreador`),
  ADD KEY `fk_radiRadicados_dependenciaTramitador` (`idTrdDepeUserTramitador`),
  ADD KEY `radiRadicados_FK` (`user_idTramitador`),
  ADD KEY `radiRadicados_FK_1` (`user_idTramitadorOld`),
  ADD KEY `fk_radiRadicados_idGdTrdSerie` (`idGdTrdSerie`),
  ADD KEY `fk_radiRadicados_idGdTrdSubserie` (`idGdTrdSubserie`);

--
-- Indices de la tabla `radiRadicadosAsociados`
--
ALTER TABLE `radiRadicadosAsociados`
  ADD PRIMARY KEY (`idRadicadoAsociado`),
  ADD KEY `fk_radiRadicadoAsociado_radiRadicado` (`idRadiAsociado`),
  ADD KEY `fk_radiCreado_radiRadicado` (`idRadiCreado`);

--
-- Indices de la tabla `radiRadicadosDetallePqrs`
--
ALTER TABLE `radiRadicadosDetallePqrs`
  ADD PRIMARY KEY (`idRadiRadicadoDetallePqrs`),
  ADD KEY `fk_idRadiRadicado` (`idRadiRadicado`),
  ADD KEY `fk_idCgClasificacionPqrs` (`idCgClasificacionPqrs`);

--
-- Indices de la tabla `radiRadicadosResoluciones`
--
ALTER TABLE `radiRadicadosResoluciones`
  ADD PRIMARY KEY (`idRadiRadicadoResoluciones`),
  ADD KEY `idRadiRadicado` (`idRadiRadicado`) USING BTREE;

--
-- Indices de la tabla `radiRemitentes`
--
ALTER TABLE `radiRemitentes`
  ADD PRIMARY KEY (`idRadiRemitente`),
  ADD KEY `fk_radiRemitentes_idRadicado_idx` (`idRadiRadicado`),
  ADD KEY `fk_radiRemitentes_idTipoPersona_idx` (`idTipoPersona`);

--
-- Indices de la tabla `reportesPersonalizados`
--
ALTER TABLE `reportesPersonalizados`
  ADD PRIMARY KEY (`idReportePersonalizado`),
  ADD UNIQUE KEY `nombreReportePersonalizado` (`nombreReportePersonalizado`),
  ADD KEY `reportesPersonalizados_FK` (`idUserCreadorReportePersonalizado`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`idRol`),
  ADD UNIQUE KEY `nombreRol` (`nombreRol`),
  ADD KEY `fk_roles_rolesNivelBusqueda` (`idRolNivelBusqueda`);

--
-- Indices de la tabla `rolesModulosOperaciones`
--
ALTER TABLE `rolesModulosOperaciones`
  ADD PRIMARY KEY (`idRolModuloOperacion`);

--
-- Indices de la tabla `rolesNivelesBusqueda`
--
ALTER TABLE `rolesNivelesBusqueda`
  ADD PRIMARY KEY (`idRolNivelBusqueda`);

--
-- Indices de la tabla `rolesOperaciones`
--
ALTER TABLE `rolesOperaciones`
  ADD PRIMARY KEY (`idRolOperacion`),
  ADD UNIQUE KEY `nombreRolOperacion` (`nombreRolOperacion`),
  ADD KEY `fk_rolesOperaciones_roles` (`idRolModuloOperacion`);

--
-- Indices de la tabla `rolesTipoDocumental`
--
ALTER TABLE `rolesTipoDocumental`
  ADD PRIMARY KEY (`idRolTipoDocumental`),
  ADD KEY `fk_rolesTiposDocumental_roles1_idx` (`idRol`) USING BTREE,
  ADD KEY `fk_rolesTiposDocumental_gdTrdTiposDocumental1_idx` (`idGdTrdTipoDocumental`) USING BTREE;

--
-- Indices de la tabla `rolesTipoRadicado`
--
ALTER TABLE `rolesTipoRadicado`
  ADD PRIMARY KEY (`idRolTipoRadicado`),
  ADD KEY `idRol` (`idRol`),
  ADD KEY `idCgTipoRadicado` (`idCgTipoRadicado`);

--
-- Indices de la tabla `rolesTiposOperaciones`
--
ALTER TABLE `rolesTiposOperaciones`
  ADD PRIMARY KEY (`idRolTipoOperacion`);

--
-- Indices de la tabla `tiposArchivos`
--
ALTER TABLE `tiposArchivos`
  ADD PRIMARY KEY (`idTipoArchivo`);

--
-- Indices de la tabla `tiposIdentificacion`
--
ALTER TABLE `tiposIdentificacion`
  ADD PRIMARY KEY (`idTipoIdentificacion`),
  ADD UNIQUE KEY `nombreTipoIdentificacion` (`nombreTipoIdentificacion`);

--
-- Indices de la tabla `tiposPersonas`
--
ALTER TABLE `tiposPersonas`
  ADD PRIMARY KEY (`idTipoPersona`);

--
-- Indices de la tabla `user`
--
ALTER TABLE `user`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `password_reset_token` (`password_reset_token`),
  ADD KEY `fk_user_roles` (`idRol`),
  ADD KEY `fk_user_userTipo` (`idUserTipo`),
  ADD KEY `fk_user_gdTrdDependencias` (`idGdTrdDependencia`);

--
-- Indices de la tabla `userDetalles`
--
ALTER TABLE `userDetalles`
  ADD PRIMARY KEY (`idUserDetalles`),
  ADD UNIQUE KEY `idUser` (`idUser`),
  ADD UNIQUE KEY `documento` (`documento`),
  ADD KEY `fk_userDetalles_tiposIdentificacion` (`idTipoIdentificacion`);

--
-- Indices de la tabla `userHistoryPassword`
--
ALTER TABLE `userHistoryPassword`
  ADD PRIMARY KEY (`idUserHistoryPassword`),
  ADD KEY `fk_userHistoryPassword_user` (`idUser`);

--
-- Indices de la tabla `userTipo`
--
ALTER TABLE `userTipo`
  ADD PRIMARY KEY (`idUserTipo`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `cgActividadEconomicaPqrs`
--
ALTER TABLE `cgActividadEconomicaPqrs`
  MODIFY `idCgActividadEconomicaPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgClasificacionPqrs`
--
ALTER TABLE `cgClasificacionPqrs`
  MODIFY `idCgClasificacionPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgCondicionDiscapacidadPqrs`
--
ALTER TABLE `cgCondicionDiscapacidadPqrs`
  MODIFY `idCgCondicionDiscapacidadPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgConsecutivosRadicados`
--
ALTER TABLE `cgConsecutivosRadicados`
  MODIFY `idCgConsecutivoRadicado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Clave primaria de la tabla cgConsecutivosRadicados';

--
-- AUTO_INCREMENT de la tabla `cgDiasNoLaborados`
--
ALTER TABLE `cgDiasNoLaborados`
  MODIFY `idCgDiaNoLaborado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del día no laborado';

--
-- AUTO_INCREMENT de la tabla `cgEncuestaPreguntas`
--
ALTER TABLE `cgEncuestaPreguntas`
  MODIFY `idCgEncuestaPregunta` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la tabla cgEncuestaPreguntas';

--
-- AUTO_INCREMENT de la tabla `cgEncuestas`
--
ALTER TABLE `cgEncuestas`
  MODIFY `idCgEncuesta` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la tabla cgEncuestas';

--
-- AUTO_INCREMENT de la tabla `cgEnvioServicios`
--
ALTER TABLE `cgEnvioServicios`
  MODIFY `idCgEnvioServicio` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgEscolaridadPqrs`
--
ALTER TABLE `cgEscolaridadPqrs`
  MODIFY `idCgEscolaridadPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgEstratoPqrs`
--
ALTER TABLE `cgEstratoPqrs`
  MODIFY `idCgEstratoPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgEtiquetaRadicacion`
--
ALTER TABLE `cgEtiquetaRadicacion`
  MODIFY `idCgEtiquetaRadicacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id de la configuración de etiqueta radicación';

--
-- AUTO_INCREMENT de la tabla `cgFirmasDocs`
--
ALTER TABLE `cgFirmasDocs`
  MODIFY `idCgFirmaDoc` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgFormulariosPqrs`
--
ALTER TABLE `cgFormulariosPqrs`
  MODIFY `idCgFormulariosPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgFormulariosPqrsDetalle`
--
ALTER TABLE `cgFormulariosPqrsDetalle`
  MODIFY `idCgFormulariosPqrsDetalle` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgFormulariosPqrsDetalleDocumentos`
--
ALTER TABLE `cgFormulariosPqrsDetalleDocumentos`
  MODIFY `idCgFormulariosPqrsDetalleDocumentos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGeneral`
--
ALTER TABLE `cgGeneral`
  MODIFY `idCgGeneral` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id configuración general';

--
-- AUTO_INCREMENT de la tabla `cgGeneralBasesDatos`
--
ALTER TABLE `cgGeneralBasesDatos`
  MODIFY `idCgGeneralBasesDatos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGeneroPqrs`
--
ALTER TABLE `cgGeneroPqrs`
  MODIFY `idCgGeneroPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGrupoEtnicoPqrs`
--
ALTER TABLE `cgGrupoEtnicoPqrs`
  MODIFY `idCgGrupoEtnicoPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGrupoInteresPqrs`
--
ALTER TABLE `cgGrupoInteresPqrs`
  MODIFY `idCgGrupoInteresPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGrupoSisbenPqrs`
--
ALTER TABLE `cgGrupoSisbenPqrs`
  MODIFY `idCgGrupoSisbenPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgGruposUsuarios`
--
ALTER TABLE `cgGruposUsuarios`
  MODIFY `idCgGrupoUsuarios` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgHorarioLaboral`
--
ALTER TABLE `cgHorarioLaboral`
  MODIFY `idCgHorarioLaboral` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgMediosRecepcion`
--
ALTER TABLE `cgMediosRecepcion`
  MODIFY `idCgMedioRecepcion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificación de la tabla este valor es unico';

--
-- AUTO_INCREMENT de la tabla `cgMotivosDevolucion`
--
ALTER TABLE `cgMotivosDevolucion`
  MODIFY `idCgMotivoDevolucion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla de motivos';

--
-- AUTO_INCREMENT de la tabla `cgNumeroRadicado`
--
ALTER TABLE `cgNumeroRadicado`
  MODIFY `idCgNumeroRadicado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Indentificador único de la tabla cgNumeroRadicado';

--
-- AUTO_INCREMENT de la tabla `cgPlantillas`
--
ALTER TABLE `cgPlantillas`
  MODIFY `idCgPlantilla` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgPlantillaVariables`
--
ALTER TABLE `cgPlantillaVariables`
  MODIFY `idCgPlantillaVariable` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgProveedores`
--
ALTER TABLE `cgProveedores`
  MODIFY `idCgProveedor` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgProveedoresExternos`
--
ALTER TABLE `cgProveedoresExternos`
  MODIFY `idCgProveedorExterno` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgProveedoresRegional`
--
ALTER TABLE `cgProveedoresRegional`
  MODIFY `idCgProveedorRegional` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgProveedoresServicios`
--
ALTER TABLE `cgProveedoresServicios`
  MODIFY `idCgProveedoresServicios` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgRangoEdadPqrs`
--
ALTER TABLE `cgRangoEdadPqrs`
  MODIFY `idCgRangoEdadPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgRegionales`
--
ALTER TABLE `cgRegionales`
  MODIFY `idCgRegional` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la tabla';

--
-- AUTO_INCREMENT de la tabla `cgReportes`
--
ALTER TABLE `cgReportes`
  MODIFY `idCgReporte` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único en el sistema de la tabla cgReportes';

--
-- AUTO_INCREMENT de la tabla `cgTipoRadicadoDocumental`
--
ALTER TABLE `cgTipoRadicadoDocumental`
  MODIFY `idCgTipoRadicadoDocumental` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id relación tipo radicado y documental';

--
-- AUTO_INCREMENT de la tabla `cgTiposGruposUsuarios`
--
ALTER TABLE `cgTiposGruposUsuarios`
  MODIFY `idCgTipoGrupoUsuarios` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTiposRadicados`
--
ALTER TABLE `cgTiposRadicados`
  MODIFY `idCgTipoRadicado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTiposRadicadosResoluciones`
--
ALTER TABLE `cgTiposRadicadosResoluciones`
  MODIFY `idCgTiposRadicadosResoluciones` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTiposRadicadosTransacciones`
--
ALTER TABLE `cgTiposRadicadosTransacciones`
  MODIFY `idCgTiposRadicadosTransacciones` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTramites`
--
ALTER TABLE `cgTramites`
  MODIFY `idCgTramite` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTransaccionesRadicados`
--
ALTER TABLE `cgTransaccionesRadicados`
  MODIFY `idCgTransaccionRadicado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `cgTrd`
--
ALTER TABLE `cgTrd`
  MODIFY `idCgTrd` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero único de la cg TRD';

--
-- AUTO_INCREMENT de la tabla `cgTrdMascaras`
--
ALTER TABLE `cgTrdMascaras`
  MODIFY `idCgTrdMascara` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero único de la TRD Mascara';

--
-- AUTO_INCREMENT de la tabla `cgVulnerabilidadPqrs`
--
ALTER TABLE `cgVulnerabilidadPqrs`
  MODIFY `idCgVulnerabilidadPqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clienteEncuestas`
--
ALTER TABLE `clienteEncuestas`
  MODIFY `idClienteEncuesta` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro en el sistema';

--
-- AUTO_INCREMENT de la tabla `clientes`
--
ALTER TABLE `clientes`
  MODIFY `idCliente` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `clientesCiudadanosDetalles`
--
ALTER TABLE `clientesCiudadanosDetalles`
  MODIFY `idClienteCiudadanoDetalle` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la tabla ClientesCiudadanosDetalles';

--
-- AUTO_INCREMENT de la tabla `csInicial`
--
ALTER TABLE `csInicial`
  MODIFY `idCsInicial` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `csParams`
--
ALTER TABLE `csParams`
  MODIFY `idCsParams` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `encuestaCalificaciones`
--
ALTER TABLE `encuestaCalificaciones`
  MODIFY `idEncuestaCalificaciones` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del registro';

--
-- AUTO_INCREMENT de la tabla `gaArchivo`
--
ALTER TABLE `gaArchivo`
  MODIFY `idgaArchivo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gaBodega`
--
ALTER TABLE `gaBodega`
  MODIFY `idGaBodega` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id de la bodega';

--
-- AUTO_INCREMENT de la tabla `gaBodegaContenido`
--
ALTER TABLE `gaBodegaContenido`
  MODIFY `idGaBodegaContenido` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id del contenido de la bodega';

--
-- AUTO_INCREMENT de la tabla `gaEdificio`
--
ALTER TABLE `gaEdificio`
  MODIFY `idGaEdificio` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id edificio';

--
-- AUTO_INCREMENT de la tabla `gaHistoricoPrestamo`
--
ALTER TABLE `gaHistoricoPrestamo`
  MODIFY `idGaHistoricoPrestamo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id del préstamo histórico';

--
-- AUTO_INCREMENT de la tabla `gaHistoricoPrestamoExpediente`
--
ALTER TABLE `gaHistoricoPrestamoExpediente`
  MODIFY `idGaHistoricoPrestamoExpediente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id del préstamo hist\r\nórico';

--
-- AUTO_INCREMENT de la tabla `gaPiso`
--
ALTER TABLE `gaPiso`
  MODIFY `idGaPiso` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id del piso';

--
-- AUTO_INCREMENT de la tabla `gaPrestamos`
--
ALTER TABLE `gaPrestamos`
  MODIFY `idGaPrestamo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id préstamo documental';

--
-- AUTO_INCREMENT de la tabla `gaPrestamosExpedientes`
--
ALTER TABLE `gaPrestamosExpedientes`
  MODIFY `idGaPrestamoExpediente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id préstamo documental';

--
-- AUTO_INCREMENT de la tabla `gdExpedienteDocumentos`
--
ALTER TABLE `gdExpedienteDocumentos`
  MODIFY `idGdExpedienteDocumento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gdExpedientes`
--
ALTER TABLE `gdExpedientes`
  MODIFY `idGdExpediente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del expediente';

--
-- AUTO_INCREMENT de la tabla `gdExpedientesDependencias`
--
ALTER TABLE `gdExpedientesDependencias`
  MODIFY `idGdExpedientesDependencias` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gdExpedientesInclusion`
--
ALTER TABLE `gdExpedientesInclusion`
  MODIFY `idGdExpedienteInclusion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único de la inclusión del expediente';

--
-- AUTO_INCREMENT de la tabla `gdFirmasMultiples`
--
ALTER TABLE `gdFirmasMultiples`
  MODIFY `idGdFirmaMultiple` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gdFirmasQr`
--
ALTER TABLE `gdFirmasQr`
  MODIFY `idGdFirmasQr` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `gdHistoricoExpedientes`
--
ALTER TABLE `gdHistoricoExpedientes`
  MODIFY `idGdHistoricoExpediente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del historico de expediente';

--
-- AUTO_INCREMENT de la tabla `gdIndices`
--
ALTER TABLE `gdIndices`
  MODIFY `idGdIndice` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único en el sisitema';

--
-- AUTO_INCREMENT de la tabla `gdReferenciasCruzadas`
--
ALTER TABLE `gdReferenciasCruzadas`
  MODIFY `idGdReferenciaCruzada` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campo primario de la tabla';

--
-- AUTO_INCREMENT de la tabla `gdReferenciaTiposAnexos`
--
ALTER TABLE `gdReferenciaTiposAnexos`
  MODIFY `idGdReferenciaTipoAnexo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campo primario de la tabla';

--
-- AUTO_INCREMENT de la tabla `gdTiposAnexosFisicos`
--
ALTER TABLE `gdTiposAnexosFisicos`
  MODIFY `idGdTipoAnexoFisico` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campo primario de la tabla';

--
-- AUTO_INCREMENT de la tabla `gdTrd`
--
ALTER TABLE `gdTrd`
  MODIFY `idGdTrd` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la tabla';

--
-- AUTO_INCREMENT de la tabla `gdTrdDependencias`
--
ALTER TABLE `gdTrdDependencias`
  MODIFY `idGdTrdDependencia` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la tabla';

--
-- AUTO_INCREMENT de la tabla `gdTrdDependenciasTmp`
--
ALTER TABLE `gdTrdDependenciasTmp`
  MODIFY `idGdTrdDependenciaTmp` int(11) NOT NULL AUTO_INCREMENT COMMENT 'idGdTrdDependencia';

--
-- AUTO_INCREMENT de la tabla `gdTrdSeries`
--
ALTER TABLE `gdTrdSeries`
  MODIFY `idGdTrdSerie` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la serie';

--
-- AUTO_INCREMENT de la tabla `gdTrdSeriesTmp`
--
ALTER TABLE `gdTrdSeriesTmp`
  MODIFY `idGdTrdSerieTmp` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la serie';

--
-- AUTO_INCREMENT de la tabla `gdTrdSubseries`
--
ALTER TABLE `gdTrdSubseries`
  MODIFY `idGdTrdSubserie` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la subserie';

--
-- AUTO_INCREMENT de la tabla `gdTrdSubseriesTmp`
--
ALTER TABLE `gdTrdSubseriesTmp`
  MODIFY `idGdTrdSubserieTmp` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la subserie';

--
-- AUTO_INCREMENT de la tabla `gdTrdTiposDocumentales`
--
ALTER TABLE `gdTrdTiposDocumentales`
  MODIFY `idGdTrdTipoDocumental` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero único de tipo documental';

--
-- AUTO_INCREMENT de la tabla `gdTrdTiposDocumentalesTmp`
--
ALTER TABLE `gdTrdTiposDocumentalesTmp`
  MODIFY `idGdTrdTipoDocumentalTmp` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero único de tipo documental';

--
-- AUTO_INCREMENT de la tabla `gdTrdTmp`
--
ALTER TABLE `gdTrdTmp`
  MODIFY `idGdTrdTmp` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar la tabla';

--
-- AUTO_INCREMENT de la tabla `infoFirmaDigitalDocPrincipales`
--
ALTER TABLE `infoFirmaDigitalDocPrincipales`
  MODIFY `idFirmaDigital` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id de la firma digital';

--
-- AUTO_INCREMENT de la tabla `initCgConfigFirmas`
--
ALTER TABLE `initCgConfigFirmas`
  MODIFY `idInitCgConfigFirma` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `initCgCordenadasFirma`
--
ALTER TABLE `initCgCordenadasFirma`
  MODIFY `idInitCgCordenadaFirma` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `initCgEntidadesFirma`
--
ALTER TABLE `initCgEntidadesFirma`
  MODIFY `idInitCgEntidadFirma` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `initCgParamsFirma`
--
ALTER TABLE `initCgParamsFirma`
  MODIFY `idInitCgParamFirma` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `log`
--
ALTER TABLE `log`
  MODIFY `idLog` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `nivelGeografico1`
--
ALTER TABLE `nivelGeografico1`
  MODIFY `nivelGeografico1` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del país';

--
-- AUTO_INCREMENT de la tabla `nivelGeografico2`
--
ALTER TABLE `nivelGeografico2`
  MODIFY `nivelGeografico2` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador único del nivel 2';

--
-- AUTO_INCREMENT de la tabla `nivelGeografico3`
--
ALTER TABLE `nivelGeografico3`
  MODIFY `nivelGeografico3` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `notificacion`
--
ALTER TABLE `notificacion`
  MODIFY `idNotificacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id de notificación';

--
-- AUTO_INCREMENT de la tabla `ocrDatos`
--
ALTER TABLE `ocrDatos`
  MODIFY `idOcrDatos` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ocrSphinxIndexMeta`
--
ALTER TABLE `ocrSphinxIndexMeta`
  MODIFY `idOcrSphinxIndexMeta` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `ocrSphinxIndexRemove`
--
ALTER TABLE `ocrSphinxIndexRemove`
  MODIFY `idOcrSphinxIndexRemove` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiAgendaRadicados`
--
ALTER TABLE `radiAgendaRadicados`
  MODIFY `idRadiAgendaRadicados` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiCorreosRadicados`
--
ALTER TABLE `radiCorreosRadicados`
  MODIFY `idRadiCorreosRadicados` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Indice unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `radiDetallePqrsAnonimo`
--
ALTER TABLE `radiDetallePqrsAnonimo`
  MODIFY `idRadiDetallePqrsAnonimo` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiDocumentos`
--
ALTER TABLE `radiDocumentos`
  MODIFY `idRadiDocumento` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiDocumentosPrincipales`
--
ALTER TABLE `radiDocumentosPrincipales`
  MODIFY `idradiDocumentoPrincipal` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiDocumentosRadicados`
--
ALTER TABLE `radiDocumentosRadicados`
  MODIFY `idRadiDocumentosRadicados` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiEnvios`
--
ALTER TABLE `radiEnvios`
  MODIFY `idRadiEnvio` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiEnviosDevolucion`
--
ALTER TABLE `radiEnviosDevolucion`
  MODIFY `idRadiEnvioDevolucion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla de relación';

--
-- AUTO_INCREMENT de la tabla `radiEstadosAnulacion`
--
ALTER TABLE `radiEstadosAnulacion`
  MODIFY `idRadiEstadoAnulacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `radiInformadoCliente`
--
ALTER TABLE `radiInformadoCliente`
  MODIFY `idRadiInformadoCliente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id de radi informado cliente';

--
-- AUTO_INCREMENT de la tabla `radiInformados`
--
ALTER TABLE `radiInformados`
  MODIFY `idRadiInformado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único que identifica la radiInformados';

--
-- AUTO_INCREMENT de la tabla `radiLogRadicados`
--
ALTER TABLE `radiLogRadicados`
  MODIFY `idRadiLogRadicado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `radiRadicadoAnulado`
--
ALTER TABLE `radiRadicadoAnulado`
  MODIFY `idRadiRadicadoAnulado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `radiRadicados`
--
ALTER TABLE `radiRadicados`
  MODIFY `idRadiRadicado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiRadicadosAsociados`
--
ALTER TABLE `radiRadicadosAsociados`
  MODIFY `idRadicadoAsociado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Numero unico de la tabla';

--
-- AUTO_INCREMENT de la tabla `radiRadicadosDetallePqrs`
--
ALTER TABLE `radiRadicadosDetallePqrs`
  MODIFY `idRadiRadicadoDetallePqrs` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiRadicadosResoluciones`
--
ALTER TABLE `radiRadicadosResoluciones`
  MODIFY `idRadiRadicadoResoluciones` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `radiRemitentes`
--
ALTER TABLE `radiRemitentes`
  MODIFY `idRadiRemitente` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Identificador de la tabla';

--
-- AUTO_INCREMENT de la tabla `reportesPersonalizados`
--
ALTER TABLE `reportesPersonalizados`
  MODIFY `idReportePersonalizado` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campo primario de la tabla';

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `idRol` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único que identifica la cantida de roles en el sistema';

--
-- AUTO_INCREMENT de la tabla `rolesModulosOperaciones`
--
ALTER TABLE `rolesModulosOperaciones`
  MODIFY `idRolModuloOperacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `rolesNivelesBusqueda`
--
ALTER TABLE `rolesNivelesBusqueda`
  MODIFY `idRolNivelBusqueda` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Id rol nivel de búsqueda';

--
-- AUTO_INCREMENT de la tabla `rolesOperaciones`
--
ALTER TABLE `rolesOperaciones`
  MODIFY `idRolOperacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `rolesTipoDocumental`
--
ALTER TABLE `rolesTipoDocumental`
  MODIFY `idRolTipoDocumental` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `rolesTipoRadicado`
--
ALTER TABLE `rolesTipoRadicado`
  MODIFY `idRolTipoRadicado` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `rolesTiposOperaciones`
--
ALTER TABLE `rolesTiposOperaciones`
  MODIFY `idRolTipoOperacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `tiposArchivos`
--
ALTER TABLE `tiposArchivos`
  MODIFY `idTipoArchivo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Campo primario de la tabla';

--
-- AUTO_INCREMENT de la tabla `tiposIdentificacion`
--
ALTER TABLE `tiposIdentificacion`
  MODIFY `idTipoIdentificacion` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único para identificar los tipos de identificación';

--
-- AUTO_INCREMENT de la tabla `tiposPersonas`
--
ALTER TABLE `tiposPersonas`
  MODIFY `idTipoPersona` int(11) NOT NULL AUTO_INCREMENT COMMENT 'ID primario de la tabla tiposPersonas';

--
-- AUTO_INCREMENT de la tabla `user`
--
ALTER TABLE `user`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único que inca la cantidad de usuarios registrados en el sistema';

--
-- AUTO_INCREMENT de la tabla `userDetalles`
--
ALTER TABLE `userDetalles`
  MODIFY `idUserDetalles` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único que identifica la cantidad de usuarios registrados en el sistema';

--
-- AUTO_INCREMENT de la tabla `userHistoryPassword`
--
ALTER TABLE `userHistoryPassword`
  MODIFY `idUserHistoryPassword` int(11) NOT NULL AUTO_INCREMENT COMMENT 'número único de la tabla';

--
-- AUTO_INCREMENT de la tabla `userTipo`
--
ALTER TABLE `userTipo`
  MODIFY `idUserTipo` int(11) NOT NULL AUTO_INCREMENT COMMENT 'Número único que indica la cantidad de tipos de usuario registrados en el sistema';

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `cgFormulariosPqrsDetalle`
--
ALTER TABLE `cgFormulariosPqrsDetalle`
  ADD CONSTRAINT `cgformulariospqrsdetalle_ibfk_1` FOREIGN KEY (`idCgFormulariosPqrs`) REFERENCES `cgFormulariosPqrs` (`idCgFormulariosPqrs`);

--
-- Filtros para la tabla `cgFormulariosPqrsDetalleDocumentos`
--
ALTER TABLE `cgFormulariosPqrsDetalleDocumentos`
  ADD CONSTRAINT `cgformulariospqrsdetalledocumentos_ibfk_1` FOREIGN KEY (`idCgFormulariosPqrsDetalle`) REFERENCES `cgFormulariosPqrsDetalle` (`idCgFormulariosPqrsDetalle`);

--
-- Filtros para la tabla `cgTiposRadicadosResoluciones`
--
ALTER TABLE `cgTiposRadicadosResoluciones`
  ADD CONSTRAINT `cgtiposradicadosresoluciones_ibfk_1` FOREIGN KEY (`idCgTipoRadicado`) REFERENCES `cgTiposRadicados` (`idCgTipoRadicado`);

--
-- Filtros para la tabla `gaHistoricoPrestamo`
--
ALTER TABLE `gaHistoricoPrestamo`
  ADD CONSTRAINT `gaHistoricoPrestamo_ibfk_1` FOREIGN KEY (`idUser`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `gaHistoricoPrestamo_ibfk_2` FOREIGN KEY (`idGdTrdDependencia`) REFERENCES `gdTrdDependencias` (`idGdTrdDependencia`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `gaHistoricoPrestamoExpediente`
--
ALTER TABLE `gaHistoricoPrestamoExpediente`
  ADD CONSTRAINT `gaHistoricoPrestamoExpediente_ibfk_2` FOREIGN KEY (`idUser`) REFERENCES `user` (`id`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  ADD CONSTRAINT `gaHistoricoPrestamoExpediente_ibfk_3` FOREIGN KEY (`idGdTrdDependencia`) REFERENCES `gdTrdDependencias` (`idGdTrdDependencia`) ON DELETE NO ACTION ON UPDATE NO ACTION;

--
-- Filtros para la tabla `gdExpedientesDependencias`
--
ALTER TABLE `gdExpedientesDependencias`
  ADD CONSTRAINT `gdexpedientesdependencias_ibfk_1` FOREIGN KEY (`idGdExpediente`) REFERENCES `gdExpedientes` (`idGdExpediente`),
  ADD CONSTRAINT `gdexpedientesdependencias_ibfk_2` FOREIGN KEY (`idGdTrdDependencia`) REFERENCES `gdTrdDependencias` (`idGdTrdDependencia`);

--
-- Filtros para la tabla `radiRadicadosResoluciones`
--
ALTER TABLE `radiRadicadosResoluciones`
  ADD CONSTRAINT `radiRadicadosResoluciones_ibfk_1` FOREIGN KEY (`idRadiRadicado`) REFERENCES `radiRadicados` (`idRadiRadicado`);
COMMIT;



--
-- Table structure for table `ng_configs`
--

CREATE TABLE `ng_configs` (
  `id` int(11) NOT NULL ,
  `context` varchar(150) DEFAULT NULL,
  `name` varchar(150) DEFAULT NULL,
  `value` text 
) ;

ALTER TABLE `ng_configs`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unicity` (`context`,`name`);

ALTER TABLE `ng_configs`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;



/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
