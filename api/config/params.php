<?php
/**
 * Que es este módulo o Archivo
 *
 * Descripcion Larga
 *
 * @category     Gestion Documental
 * @package      Orfeo NG 
 * @subpackage   XXXX 
 * @author       Skina Technologies SAS (http://www.skinatech.com)
 * @license      Mixta <https://orfeolibre.org/inicio/licencia-de-orfeo-ng/>
 * @license      ./LICENSE.txt
 * @link         http://www.orfeolibre.org
 * @since        Archivo disponible desde la version 1.0.0
 *
 * @copyright    2023 Skina Technologies SAS
 */


return [
    'adminEmail' => 'pruebas@skinatech.com',
    'supportedLanguages' => ['en', 'es', 'pt'],
    'aplicacion' => 'http://localhost/pruebas/ng_backend/api/web/',
    'webrootApi' => '/var/www/html/pruebas/ng_backend/api/web/',

    'motorDB' => 'MYSQL', // Motor utilizado para la basede  datos principal: ['MYSQL', 'POSTGRESQL', 'MSSQL', 'ORACLE']

    'timeStart' => ' 00:00:00', // Esta variable se debe cambiar dependiendo del motor de DB. (Espacio para oracle ' 00:00:00', y 'T00:00:00' para sqlServer, Postgres)
    'timeEnd' => ' 23:59:59', // Esta variable se debe cambiar dependiendo del motor de DB. (Espacio para oracle ' 23:59:59', y 'T23:59:59' para sqlServer, Postgres)

    'hourStart' => ':00', // Esta variable se debe cambiar dependiendo del motor de DB. (Espacio para oracle ' 00:00:00', y 'T00:00:00' para sqlServer, Postgres)
    'hourEnd' => ':00', // Esta variable se debe cambiar dependiendo del motor de DB. (Espacio para oracle ' 23:59:59', y 'T23:59:59' para sqlServer, Postgres)

    /** SphinxClient */
    'SetServer' => 'localhost',
    'SetMaxQueryTime' => 5000,
    'SetLimitsMin' => 0 ,
    'SetLimitsMax' => 200 ,
    'IndexerSphinx' => 'test1stemmed',
    'idIndexerSphinx' => 'idocrdatos',

    /** Valores por defectos de la tabla clientes ciudadanos deatlles */
    'clientesciudadanosdetalles' => [
        'idUser' => 1,
        'idTipoIdentificacion' => 1 
    ],

    /** Usuario de salida, este usuario sera el tramitador de los radicados finalizados */
    'userNameDeSalida' => 'dsalida',
    'userAnonimoPQRS' => 'anonimo',
    'idUserLogCron' => 1, // Usuario a nombre quien quedará la traza de los Cron ejecutados en el sistema

    /** Estados de respuesta json */
    'statusErrorValidacion' => 322, //Estado de error de validación
    'statusErrorAccessDenied' => 330, //Estado de error de permisos sobre acciónstatusTodoText
    'statusErrorEncrypt' => 390, //Estado de error en encriptación y desencriptación

    /** Cantidad de días en la que vencen los tokens, validado en DÍAS **/
    'TimeExpireToken' => 0,

    /*** Cantidad mínima de contraseñas diferentes anteriores (utilizado cada vez que se cambia la contraseña) */
    'cantDiferentsPasswords' => 6,

    /** Días para solicitar cambio de contraseña a los usuarios **/
    'daysChangePassword' => 100000,

    /** Columna maxima a leer en el excel */
    'highestColumn' => 'Q',

    /** Regional por defecto si no se configura en la TRD */
    'regionalDefaul' => 'Bogota D.C',
    'maskDefaul' => 'Columnas separadas',

    /** Limite establecido para los registros que se muestran en el datatable del index **/
    'limitRecords' => 100,
    /** Limite establecido para los registros que se muestran para estructuras pequeñas **/
    'limitRecordsSmall' => 20,

    /** Limite establecido para los registros que se muestran en el dashboard del index **/
    'limitDashboardRecords' => 10,

    /** Limite para los listados de reportes */
    'limitRecordsReports' => 20,

    /**
     * Limite establecido para los registros que se muestran en el datatable del index de base de datos de oracle ADI
     * routeDocumentsAdi Ruta de adi para consultar una carpeta compartida y verificar documnentos, pero debe eliminar ya que es solo para adi 
     **/
    'limitAdi' => 50,
    'routeDocumentsAdi' => '/mnt/adi',

    /** Limite de listas en Excels */
    'limitCellFormatsList' => 200,

    /** Permisos sobre acciones */
    'permissionsUpdateSapCode' => 'permissionsUpdateSapCode',
    'permissionsRolFilingTypes'=> 'version1/roles/roles/create-rol-tipo-radicado', // Roles tipos radicados
    'permissionsRadicacionEmail'=> 'version1%radicacion%radicacion-email%login-email', // Radicacion Email
    'permissionsRadiCorresDownload' => 'version1/radicacion/documentos/download-document', //Descargar documentos
    'permissionsVoBo' => 'version1%radicacion%transacciones%vobo', // Aprobación visto bueno
    'permissionsViewLocationArchived' => 'version1%gestionArchivo%gestion-archivo%view-location', 
    'permissionsArchiveExpedient' => 'version1%radicacion%transacciones%archive-filing',
    'permissionsSearchReport' => 'version1%reportes%reportes-personalizados%index',
    'permissionsCrossReference' => 'version1%gestionDocumental%expedientes%cross-reference',
    'permissionsReasignOnlyPqrsd' => 'permissionsReasignOnlyPqrsd',
    'permissionsLoanOfFilesCentralFile' => 'version1%gestionArchivo%prestamo-documental%loan-files-central-file',
    'permissionsCentralFileLoans' => 'version1%gestionArchivo%prestamo-documental%central-file-loans',

    /** Permisos tipos de firmas */
    'permissionSigned' => [
        'mechanical' => 'version1%configuracionApp%cg-firmas-docs%mechanics',
        'digital' => 'version1%configuracionApp%cg-firmas-docs%digital',
        'qr' => 'version1%configuracionApp%cg-firmas-docs%qr',
        'physical' => 'version1%configuracionApp%cg-firmas-docs%physical'
    ],

    'permissionSignedText' => [
        'mechanical' => 'mechanical',
        'digital' => 'digital',
        'qr' => 'qr',
        'physical' => 'physical'
    ],

    /** Si, no */
    'SiNoNumber' => [
        10 => 'Si',
        0 => 'No'
    ],

    'SiNoText' => [
        'Si' => 10,
        'No' => 0
    ],

    /*** Tipos de estados para los registros ***/
    'statusTodoNumber' => [
        0 => 'Inactivo',
        10 => 'Activo',
        11 => 'Finalizado',

        # Estados distribución y envio
        6 => 'Listo para enviar',       
        7 => 'Pendiente por entregar',
        8 => 'Entregado',
        9 => 'Devuelto',        

        # Estado de archivo
        12 => 'Archivado',

        # Estados de transferencia
        13 => 'Pendiente por transferir',
        14 => 'Transferencia aceptada',
        15 => 'Transferencia rechazada',

        # Estado de PQRS
        16 => 'Devuelto al ciudadano',

        # Estados de prestamos documentales
        25 => 'Préstamo por Autorizar',
        26 => 'Préstamo Autorizado',
        18 => 'Solicitud de Préstamo',
        19 => 'Préstamo Aprobado',
        20 => 'Préstamo Cancelado',
        21 => 'Préstamo Devuelto',

        # Estado de PQRS
        22 => 'Desistir Radicado',

        # Estado vacio
        100 => ''
    ],

    'statusTodoText' => [
        'Inactivo' => 0,
        'Activo' => 10,
        'Finalizado' => 11,
        
        # Estados distribución y envio
        'ListoEnvio' => 6,
        'PendienteEntrega' => 7,
        'Entregado' => 8,
        'Devuelto' => 9,

        # Estado de archivo
        'Archivado' => 12,

        # Estados de transferencia
        'PendienteTransferir' => 13,
        'TransferenciaAceptada' => 14,
        'TransferenciaRechazada' => 15,

        # Estado de PQRS
        'DevueltoAlCiudadano' => 16,

        # Estados de prestamos documentales
        'PrestamoPorAutorizar' => 25,
        'PrestamoAutorizado' => 26,
        'SolicitudPrestamo' => 18,
        'PrestamoAprobado' => 19,
        'PrestamoCancelado' => 20,
        'PrestamoDevuelto' => 21,

        # Estado de PQRS
        'DesistirRadicado' => 22,
        
        # Estado vacio
        '' => 100
    ],

    /*** Tipos de estados para la anulacion de radicados ***/
    'statusAnnulationText' => [
        'RechazoAnulacion' => 1,
        'SolicitudAnulacion' => 2,
        'AceptacionAnulacion' => 3,
    ],

    /** Estados Documentos Principales **/
    'statusDocsPrincipales' => [
        'Cargado' => 10,
        'Firmado' => 9,
        'Combinado' => 8,
        'procesandoFirma' => 7,
        'CombinadoSinFirmas' => 6,
        'Inactivo' => 0,
        'procesoFirmaFisica' => 15,
        'FirmadoFisicamente' => 20,
        'sinFirma' => 11,
        'enProcesoFirmaDigital' => 12,
        'firmadoDigitalmente' => 13,
    ],

    'statusDocsPrincipalesNumber' => [
        13 => 'Firmado digitalmente',
        12 => 'En proceso de firma digital',
        11 => 'Sin firma',
        10 => 'Cargado',
        9 => 'Firmado',
        8 => 'Combinación de correspondencia',
        7 => 'En proceso de firma',
        6 => 'Combinación de correspondencia sin firmas',
        0 => 'Inactivo',
        15 => 'En proceso de firma física',
        20 => 'Firmado físicamente'
    ],

    /* Niveles de busqueda de los radicados */
    'searchLevelText' => [
        'Avanzado' => 1,
        'Intermedio' => 2,
        'Basico' => 3,
    ],

    /** Si, No para campos tratados como boolean */
    'SiNoBooleanNumber' => [
        1 => 'Si',
        0 => 'No',
    ],
    'SiNoBooleanText' => [
        'Si' => 1,
        'No' => 0,
    ],

    /** Tipo Préstamo **/  
    'statusLoanTypeText' => [
        'ConsultaSala' => 1,
        'PrestamoFisico' => 2,
        'PrestamoDigital' => 3,
    ],
    'statusLoanTypeNumber' => [
        1 => 'Consulta en Sala',
        2 => 'Préstamo Físico',
        3 => 'Préstamo Digital',
    ],

    /** Requerimiento del Préstamo **/  
    'statusLoanRequirementText' => [
        'Carpeta' => 1,
        'Documento' => 2,
    ],
    'statusLoanRequirementNumber' => [
        1 => 'Carpeta',
        2 => 'Documento',
    ],
    
    /*** Id Roles ***/
    'idRoles' =>[
        'Externo' => 6,
    ],
    
    /*** Tipos de usuario (BD)***/
    'tipoUsuario' => [
        'Administrador del Sistema' => 1,
        'Administrador de Gestión Documental' => 2,
        'Ventanilla de Radicación' => 3,
        'Usuario Funcional' => 4,
        'Usuario Jefe' => 5,
        'Externo' => 6,
    ],
    'tipoUsuarioText' => [
        1 => 'Administrador del Sistema',
        2 => 'Administrador de Gestión Documental',
        3 => 'Ventanilla de Radicación',
        4 => 'Usuario Funcional',
        5 => 'Usuario Jefe',
        6 => 'Externo',
    ],

    /*** Tipos de personas (BD)***/
    'tipoPersonaText' => [
        'personaJuridica' => 1, // Persona Jurídica
        'personaNatural' => 2, // Persona Natural
        'funcionario' => 3, // Funcionario
        'apoderado' => 6, // Apoderado
    ],

    'tipoPersonaNumber' => [
        1 => 'Persona Jurídica', // Persona Jurídica
        2 => 'Persona Natural', // Persona Natural
        3 => 'Funcionario', // Funcionario
        6 => 'Apoderado', // Apoderado
    ],

    /*** Tipos de archivos aceptados para subir los documentos de renta ***/
    'tiposArchivosAceptadosRenta' => ['xlsx', 'xls', 'pdf'],

    'idRolDefault' => 2, // Analista
    'idUserTipoDefault' => 2, // Analista
    'idRolGestionDocumental' => 2,

    'maxIntentosLogueo' => 3, //Cantidad maxima de intentos de logueoload

    'rowsUploadExcel' => '10', //Cantidad del filas de una hoja de excel
    
    /*** Variables para el procesamiento de archivos ***/
    'routeMassive' => 'tmp_masiva', // Ruta de los archivos subidos
    'nomCarpetaUsuarios' => 'usuariosMasivos', // nombre de la carpeta de subida del archivo temporal de usuarios
    'nomRegionalFolder' => 'regionalesMasivos', // nombre de la carpeta de subida del archivo temporal de regionales
    'nomTercerosFolder' => 'tercerosMasivos',
    'nomCarpetaTrd' => 'archivoTrd', // nombre de la carpeta de subida del archivo temporal de la TRD
    'cantRegistrosFormato' => 100, // cantidad de registros que se van a generar con las listas desplegable en el archivo masivo
    'nomCarpetaDocumentos' => 'documentosCliente', // nombre de la carpeta de subida del archivos y documentos del cliente
    'routeDocuments' => 'bodega/'.date('Y'), // Ruta de los documentos
    'routeDownloads' => 'bodega/downloads', // Ruta de de carpeta de descarga
    'routeDownloadsBarCodeConsecutivo' => 'bar_code_consecutivos', // Ruta para descargar el bar code de consecutivos para expedientes
    'routeFirmaDigitalOrfeo' => 'firmaDigitalOrfeo', // Ruta de firma de Orfeo
    'routeFileZipBase' => 'base.zip', // Ruta del archivo base para comprimir
    'nomCarpetaRenta' => 'renta_', //nombre de la carpeta de rentas del cliente
    'CarpGestionPlantillas' => 'plantillas',     //Carpeta gestion de plantillas 
    'CarpFirmasUsuarios' => 'usuarios/img/',     //Carpeta gestion de plantillas 
    'guiaName' => 'no_guia_',
    'usuarioNoExiste' => 'Usuario no Existe',
    'nomRadicado' => 'Radicado',
    'routeElectronicIndex' => '/bodega/indices_xml/',
    'routeClosedExpedient' => 'bodega/cierre_expediente',

    'formUniInventario' => 'doc-formato-unico-inventario',

    // Rutas Gestion de Archivo
    'rotulosGestionArchivo' => [
        'routeCaja' => 'formato_rotulo_caja',
        'routeCarpeta' => 'formato_rotulo_carpeta',
        'viewPdfCaja' => 'pdf/rotuloCajaView.php',
        'viewPdfCarpeta' => 'pdf/rotuloCarpetaView.php',
    ],
    
    /** rutas de front */
    'routesFront' => [
        'indexRadicado' => 'filing/filing-index/false',
        'viewRadicado' => 'filing/filing-view/',
        'indexEnvio' => 'correspondenceManagement/distribution-shipping-index',
        'viewEnvio' => 'correspondenceManagement/distribution-shipping-view/',
        'viewExpediente' => 'documentManagement/folder-view/',
        'indexAdminLoan' => 'documentaryLoans/manage-loan-index', // Mostrar el index del administrador
        'indexAdminLoanFile' => 'documentaryLoans/manage-loan-index', // Mostrar el index del administrador de préstamos de expediente
        'indexLoan' => 'documentaryLoans/apply-for-loan-index', // Mostrar el index prestamo solicitante
        'indexLoanFiles' => 'documentaryLoans/loan-of-files',
        'viewLocationArchive' => 'archiveManagement/archive-location/',
        'indexTransferencia' => 'archiveManagement/documentary-transfer-index'
    ],

    /***   Log de Auditoria   ***/
    'eventosLogText' => [

        'Login' => 'Inicio de sesión',
        'Logout' => 'Cerrar sesión',
        'Signup'    => 'Se registró un nuevo usuario con los siguientes datos',
        'SignupPqrs'    => 'Se registró el ciudadano {observacion}, por medio de la página pública de PQRSD',
        'SignupPqrsUpdate'    => 'Se actualizó la información del ciudadano {observacion}, por medio de la página pública de PQRSD',
        'NuevaContrasena' => 'Petición para restablecer contraseña usuario',
        'Contrasena'    => 'Contraseña actualizada para el usuario',

        'crear' => 'Se creó un nuevo registro',
        'New' => 'Se almacenó un nuevo registro con los siguientes datos',
        'Edit' => 'Se actualizaron los datos',
        'Update' => 'Se actualizaron los datos, para',
        'Delete' => 'Se eliminó el siguiente registro',
        'ChangeStatus' => 'Se actualizó el registro, ahora está ',
        'ChangeStatus2' => 'Se actualizó estado ',
        'ChangeStatusPublish' => 'Se actualizó el registro, ahora el documento se encuentra ',
        'ChangeStatusLabel' => 'Se ha configurado la etiqueta de radicación del campo ',
        'View' => 'Se visualizó el registro de ',
        'ViewMail' => 'Se visualizó el registro ',
        'ViewVersionTRD' => 'Se visualizó la versión del código: ',
        'ViewActiveTRD' => 'Se visualizó la TRD activa con código: ',
        'ViewDocument' => 'Se visualizó el documento: ',

        'newTipoRadicado' => 'Se creó un nuevo tipo de radicado con el nombre ',
        'updateTipoRadicado' => 'Se actualizó el tipo de radicado ',
        
        'sendMail' => 'Se envió respuesta por correo del radicado ',
        'FileUpload' => 'Se cargó un archivo',
        'FileUploadMain' => 'Se cargó el documento principal',
        'NewAnnulmentCertificate' => 'Se generó una nueva acta de anulación',
        'discardConsecutive' => 'Se descartó el consecutivo temporal ',
        'shipping' => 'Se cargó un archivo',
        'returnDelivery' => 'Se realizó la devolución de correspondencia del radicado ',
        'DownloadFile' => 'Se descargó archivo',
        'DownloadTemplate' => 'Se descargó la plantilla ',
        'DownloadTemplates' => 'Se descargaron las plantillas ',
        'NewRadiMail' => 'Se generó una nueva radicación email',
        'LoginMail' => 'Inicio de sesión en radicación email',
        'includeInFile' => 'Se incluyó el radicado ',
        'excludeFileExpedient' => 'Se excluyó el N° radicado: ',
        'excludeDocumentExpedient' => 'Se excluyó el nombre del documento: ',
        'closedExpedient' => 'Se ha cerrado el expediente: ',
        'openExpedient' => 'Apertura del indice electrónico del expediente:',

        'createTemplete' => 'Se generó la plantilla de correspondencia',
        'createHL' => 'Se creó un nuevo horario laboral',

        'newSchedule' => 'Se agendó el radicado ',
        'newScheduleTmp' => 'Se agendó el consecutivo temporal ',
        'newScheduleMasive' => 'Se agendaron los radicados ',
        'newScheduleMasiveAll' => 'Se agendaron los radicados y consecutivos ',
        'newScheduleMasiveTmp' => 'Se agendaron los consecutivos ',
        'inactiveGdTrdDependencia' => 'Se inactivó la TRD completa de la dependencia ',
        'acceptanceGdTrdDependencia' => 'Se aprobó la versión temporal de la TRD asignada a la dependencia ',
        'deleteGdTrdDependencia' => 'Se eliminó una versión temporal de la TRD asignada a la dependencia ',

        'newReasign' => 'Se reasignó el radicado ',
        'FinalizeFile' => 'Se finalizó el trámite del radicado',
        'newInformado' => 'Se informó el radicado ',
        'newInformadoTmp' => 'Se informó el consecutivo temporal ',
        'returnFiling' => 'Se realizó la devolución del radicado ',
        'returnFilingTmp' => 'Se realizó la devolución del consecutivo temporal ',
        'voboRequest' => 'Se realizó solicitud Vo.Bo al radicado ',
        'voboRequestTmp' => 'Se realizó solicitud Vo.Bo al consecutivo temporal ',
        'vobo' => 'Se realizó la transacción aprobación de Vo.Bo al radicado ',
        'voboTmp' => 'Se realizó la transacción aprobación de Vo.Bo al consecutivo temporal ',

        'cargaPlantilla' => 'Se realizó la carga de la plantilla',
        'correspondenceMatch' => 'Se realizó la combinación de correspondencia al documento ',
        'firmaRadicados' => 'Se firmó el documento ',
        'procesoFirmaFisica' => 'El inicia proceso de firma física para el documento ',

        'asignacionEspacioFisico' => 'Se realizó la asignación de espacio físico ',

        'listoEnvio' => 'Se realizó el cambio de estado a listo para enviar ',
        'viewVersing' => 'Se visualizó versionamiento ',
        'viewArchived' => 'Se visualizó los detalles del expediente',

        'viewHistoricalLoan' => 'Se visualizó el histórico del préstamo de ',
        'transferenciaDocumental' => 'Se realizó la transferencia documental automáticamente de los expedientes {nomExp} de la dependencia ',
        'transferenciaDocumentalManual' => 'Se realizó el cambio de estado a pendiente por transferir de los expedientes {nomExp} de la dependencia ',
        'transferenciaDocumentalAceptada' => 'Se aceptó la transferencia del expediente N° {nomExp} de archivo Gestión a archivo Central de forma correcta.',
        'transferenciaDocumentalRechazada' => 'Se rechazó la transferencia del expediente N° {nomExp} con la siguiente observación. ',
        
        'FinalizeFilePqrWithDrawal' => 'Se marcó automáticamente el radicado de PQRSD No. {numeroRadicado} como desistimiento, ya que el ciudadano no respondió en el tiempo indicado la solicitud hecha por la entidad',

        //Configuración general
        'createConfigGeneral' => 'Se ha creado la configuración inicial del sistema',
        'updateConfigGeneral' => 'Se ha actualizado la configuración inicial del sistema',

        //Configuración del servidor
        'createConfigServer' => 'Se ha creado la configuración del servidor de correo electrónico',
        'updateConfigServer' => 'Se ha actualizado la configuración del servidor de correo electrónico',

        'printSticker' => 'Se generó la impresión del sticker del número radicado ',
        'LoadMassive' => 'Se realiza la carga masiva',

        // Reportes
        'createReportCustomer' => 'Se ha creado el reporte: ', 
        'updateReportCustomer' => 'Se ha actualizado el reporte: ',
        'sendMailRadicado' => 'Se envío correo'

    ],

    /***   Log de Auditoria   ***/
    'eventosLogTextRadicado' => [

        'New' => 'Se radicó el documento de forma correcta',
        'NewTmp' => 'Se generó el consecutivo del documento de forma correcta',
        'NewRadiMail' => 'Se radicó el documento de forma correcta mediante radicación email',
        'NewRadiMailTmp' => 'Se generó el consecutivo del documento de forma correcta mediante radicación email',
        'Edit' => 'Se actualizó el radicado con los siguientes datos',
        'EditTmp' => 'Se actualizó el consecutivo temporal con los siguientes datos',
        'Update' => 'Se actualizaron los siguientes datos, para',
        'Delete' => 'Se eliminó el siguiente registro',

        'sendMail' => 'Se envió el radicado al(los) cliente(s) con el correo registrado',
        'FileUpload' => 'Se realizó la carga del siguiente documento: ',
        'FileUploadMain' => 'Se realizó la carga del documento principal: ',
        'shipping' => 'Se realizó el envío por correspondencia con los siguientes datos',
        'returnDelivery' => 'Se realizó la devolución de correspondencia por el motivo ',
        'delivered' => 'Se realizó la entrega de correspondencia',
        'EventNew' => 'Se agregó un nuevo evento con la siguiente descripción, ',
        'createTemplete' => 'Se generó la plantilla de correspondencia',

        'includeInFile' => 'Se incluyó el radicado en el expediente: ',
        'excludeExpedient' => 'Se excluyó el radicado: ',

        'devolucion' => 'Se realizó la devolución del radicado al usuario ',
        'devolucionTmp' => 'Se realizó la devolución del consecutivo temporal al usuario ',
        'devuelto' => 'El radicado fue devuelto con la siguiente descripción',
        'voboRequest' => 'Se realizó solicitud Vo.Bo al usuario ',
        'vobo' => 'Aprobación Vo.Bo',

        'cargaPlantilla' => 'Se realizó la carga de la plantilla',
        'correspondenceMatch' => 'Se realizó la combinación de correspondencia al documento ',
        'firmaRadicados' => 'Se firmó el documento ',
        'procesoFirmaFisica' => 'El inicia proceso de firma física para el documento ',
        'procesoFirmaFisicaDescargaEtiqueta' => 'Por medio del proceso de firma física se asigno de forma manual el número de radicado ',
        'listoEnvio' => 'Se marcó el radicado ', // *** como listo para enviar
        'firmadoFisicamente' => 'Se firmó físicamente el documento ',

        'MatchAll' => 'asociado a los radicados ',
        'MatchOne' => 'asociado a el radicado ',

        'returnPqrToCitizen' => 'Se ha retornado el PQRSD al ciudadano, por motivo de ',
        'addCommentary' => 'Se ha agregado comentario al radicado ',
        'createDetailResolution' => 'Se ha generado detalle de resolución con los datos ',

    ],

    /***  Log de Expedientes ***/
    'eventosLogTextExpedientes' => [
        'archivado' => 'Se archivo el expediente N°',
        'pendienteTransferir' => 'Se cambio el estado del expediente N° {numExp}, de forma automática porque cumplió los tiempos de retención',
        'pendienteTransferirManual'=> 'Se cambio el estado del expediente N° {numExp}, a pendiente por transferir de forma manual con la siguiente observación ',
        'transferenciaAceptada' => 'Se aceptó la transferencia del expediente N° {numExp} de archivo Gestión a archivo Central de forma correcta.',
        'transferenciaRechazada' => 'Se rechazó la transferencia del expediente N° {numExp} por la siguiente observación '
    ],

    /* Nombre del encabezado del PDF para el acta de aceptación de anulaciones */
    'encabezadoPdf' => 'ACTA DE ANULACIÓN DE RADICADOS',

    // reportes custom
    'autorizacionText' => [
        0 => 'Correo Físico',
        10 => 'Correo Electrónico'
    ],

    /** Nombre de prioridad de radicados **/
    'statusPrioridadText' => [
        0 => 'Prioridad baja',
        1 => 'Prioridad alta',
    ],

    /** Nombre de prioridad de radicados **/
    'statusPrioridadText' => [
        0 => 'Prioridad baja',
        1 => 'Prioridad alta',
    ],

    /** Gestion de Plantillas  */
    'charlist' => '{-_#1234567890}',
    'word_string_first' => '{',
    'word_string_last' => '}',
    
    /*** Acciones Tipo de Radicado ***/
    'actionCgTipoRadicado' => [
        'add',
        'edit',
        'view',
        'uploadFile',
        'printStickers',
        'returnFiling',
        'attachment',
        'attachmentMain',
        'voboRequest',
        'vobo',
        'copyInformaded',
        'finalizeFiling',
        'includeInFile',
        'associateTemplate',
        'loadFormat',
        'shippingReady',
        'withdrawal',
    ],
    'actionMultiTipoRadicado' => [
        'uploadFile',
        'printStickers',
        'add' ,
        'schedule',
        'send',
        'annulationRequest',
        'discardConsecutive',
        'attachment',
        'returnFiling',
        'voboRequest',
        'vobo',
        'copyInformaded',
        'finalizeFiling',
        'includeInFile',
        'associateTemplate',
        'loadFormat',
        'shippingReady',
        'returnPqrToCitizen',
        'withdrawal',
    ],
    'actionMultiTipoRadicadoEntrada' => [
        'printStickers',  // Entradas
        'add',
        'attachment',
        'send',
        'voboRequest',
        'vobo',
        'copyInformaded',
        'finalizeFiling',
        'includeInFile',
        'associateTemplate',
        'loadFormat',
        'shippingReady',
    ],

    /**
     * Esta configuración determina qué tramites se deben mostrar por cada tipo de radicado
     * La llave hace referencia al valor de mostrarCgTramite y el valor al array de id de tramites que se debe mostrar  
     **/
    'mostrarCgTramite' => [
        1 => [2 ,3],
        2 => [1, 4, 5]
    ],

    /** Tipo de busqueda de radicados (utilizado para filtros del index de radicación) */
    'radiOptionSearchType' => [
        'byTramitadorUser' => 1,
        'byRadiInformados' => 2,
        'byRadiDependence' => 3,
        'allRadi'          => 4,
    ],

    'radiOptionSearchTypeDefault' => 1, // Usuario responsable (tramitador)
    /** Fin Tipo de busqueda de radicados (utilizado para filtros del index de radicación) */

    /** Tipos de generación o creacion de radicados (utilizado para filtros del index de radicación) */
    'radiGenerationType' => [
        'radicacion' => 1,
        'combinacionCorrespondencia' => 2,
        'todos' => 3,
    ],

    'radiGenerationTypeDefault' => 1, // radicacion
    /** Fin Tipos de generación o creacion de radicados (utilizado para filtros del index de radicación) */

    /** Indica el medio de recepción que que llega por defecto para cuando se radica
     * por correo electrónico
     */
    'CgMedioRecepcionNumber' => [
        'ventanillaFisica' => 2,
        'correoFisico' => 3,
        'ventanillaVirtual' => 4,
        'portalWeb' => 5,
        'sitiosWeb' => 6,
        'correosElectronicos' => 7,
        'telefonico' => 8,
    ],

    'CgMedioRecepcionText' => [
        2 => 'Ventanilla Fisica',
        3 => 'Correo Físico',
        4 => 'Ventanilla Virtual',
        5 => 'Portal Web',
        6 => 'Sitios Web',
        7 => 'Correos Electrónicos',
        8 => 'Telefónico',
    ],

    'nameFileQR' => 'codeQR.png',

    'cgTiposFirmas' => [
        'Manual' =>  'Firma mecánica',
        'QR'     =>  'Firma QR'
    ],

    /** Configuración de mascara el valor 5 hace referencia a todas las columnas separadas */
    'ConfiguracionMascara' => 4,

    /** Configuracion Icons transacciones para Radicacion Email**/
    'getListingFoldersIcons' => [
        0 => 'inbox',           // 'Borradores',
        1 => 'star_outline',    // 'Destacados',
        2 => 'send',            // 'Enviados',
        3 => 'more',            // 'Importantes',
        4 => 'delete_forever',  // 'Papelera',
        5 => 'report',          // 'Spam',
        6 => 'all_inbox'        // 'Todos'
    ], 
    'bandejaPrincipal' => 'Recibidos',

    'maxExecutionTimeEmail' => 60, // Tiempo maximo de ejecucion de la consulta de la bandeja de correos (segundos)

    /********** Relacion entre nombres de dominio y conexiones imap  **********/
    /** (a) Listado de dominios aceptados por el sistema y su valor clave para buscar la informacion de conexion imap  */
    'domainNamesAccepted' => [
        'gmail.com' => 'gmail',
        'hotmail.com' => 'hotmail',
        'outlook.com' => 'hotmail',
        'skinatech.com' => 'skinatech',
        'ebsa.com.co' => 'hotmail_2',
        'aerocivil.gov.co' => 'hotmail_2',
        'fonprecon.gov.co' => 'fonprecon',
    ],

    /** (b) Informacion para la conexion imap segun el dominio */
    'arrayHostnameImap' => [
        'gmail' => [
            'hostnameImap' => '{imap.gmail.com:993/imap/ssl/novalidate-cert}',
            'imapPathDefault' => '{imap.gmail.com:993/imap/ssl/novalidate-cert}INBOX',
            'isServerEncoding' => true,
            'imapPathExceptions' => [
                '{imap.gmail.com:993/imap/ssl/novalidate-cert}[Gmail]/Todos',
                '{imap.gmail.com:993/imap/ssl/novalidate-cert}[Gmail]/Borradores',
            ],
        ],
        'hotmail' => [
            'hostnameImap' => '{imap-mail.outlook.com:993/ssl}',
            'imapPathDefault' => '{imap-mail.outlook.com:993/ssl}Inbox',
            'isServerEncoding' => false,
            'imapPathExceptions' => [
                '{imap-mail.outlook.com:993/ssl}Notes',
                '{imap-mail.outlook.com:993/ssl}Outbox',
                '{imap-mail.outlook.com:993/ssl}Junk',
                '{imap-mail.outlook.com:993/ssl}Drafts',
            ],
        ],
        'skinatech' => [
            'hostnameImap' => '{kalalu4.ikatta.com:993/imap/ssl/novalidate-cert}',
            'imapPathDefault' => '{kalalu4.ikatta.com:993/imap/ssl/novalidate-cert}INBOX',
            'isServerEncoding' => true,
            'imapPathExceptions' => [
                '{kalalu4.ikatta.com:993/imap/ssl/novalidate-cert}Templates',
                '{kalalu4.ikatta.com:993/imap/ssl/novalidate-cert}Junk',
                '{kalalu4.ikatta.com:993/imap/ssl/novalidate-cert}Drafts',
            ],
        ],
        'hotmail_2' => [
            'hostnameImap' => '{outlook.office365.com:993/ssl}',
            'imapPathDefault' => '{outlook.office365.com:993/ssl}Inbox',
            'isServerEncoding' => false,
            'imapPathExceptions' => [
                '{outlook.office365.com:993/ssl}Notes',
                '{outlook.office365.com:993/ssl}Outbox',
                '{outlook.office365.com:993/ssl}Junk',
                '{outlook.office365.com:993/ssl}Drafts',
            ],
        ],
        'aerocivil' => [
            'hostnameImap' => '{bog01.aerocivil.gov.co:993/ssl}',
            'imapPathDefault' => '{bog01.aerocivil.gov.co:993/ssl}Inbox',
            'isServerEncoding' => false,
            'imapPathExceptions' => [
                '{bog01.aerocivil.gov.co:993/ssl}Notes',
                '{bog01.aerocivil.gov.co:993/ssl}Outbox',
                '{bog01.aerocivil.gov.co:993/ssl}Junk',
                '{bog01.aerocivil.gov.co:993/ssl}Drafts',
            ],
        ],
        'fonprecon' => [
            'hostnameImap' => '{mail.fonprecon.gov.co:993/ssl}',
            'imapPathDefault' => '{mail.fonprecon.gov.co:993/ssl}Inbox',
            'isServerEncoding' => true,
            'imapPathExceptions' => [
                '{mail.fonprecon.gov.co:993/ssl}Notes',
                '{mail.fonprecon.gov.co:993/ssl}Outbox',
                '{mail.fonprecon.gov.co:993/ssl}Junk',
                '{mail.fonprecon.gov.co:993/ssl}Drafts',
            ],
        ],
    ],
    /********** Relacion entre nombres de dominio y conecciones imap  **********/

    /** Parametros para los niveles geograficos por defecto para el usuario */
    'nivelGeografico' => [
        'nivelGeograficoPais' => 1,           // 'COLOMBIA',
        'nivelGeograficoDepartamento' => 1,   // 'Cundinamarca',
        'nivelGeograficoMunicipio' => 1,      // 'Municipio',
    ],

    'nivelGeograficoText' => [
        'nivelGeograficoPais' => 'COLOMBIA',           // 'COLOMBIA',
        'nivelGeograficoDepartamento' => 'Cundinamarca',   // 'Cundinamarca',
        'nivelGeograficoMunicipio' => 'Bogotá D.C.',      // 'Municipio',
    ],

    /** Transacciones disponibles en la actualizacion de radicado */
    //'transaccionesUpdateRadicado' => ['view', 'add', 'attachment', 'printStickers', 'finalizeFiling' ],
    'transaccionesUpdateRadicado' => ['view', 'add', 'attachment', 'attachmentMain', 'printStickers'],

    /** Nombre base para los archivos PDF de las actas de anulacion generadas */
    'baseNameFileActaAnulacion' => 'Acta-de-anulación-no-',

    /** Tipos documentales que deben estar en la base ya que son usados en los procesos del sistema */
    'tipoDocumentalText' => [
        'sinTipoDoc' => 0,
        'crossReference' => 1353,
    ],

    //Bodega de datos para subir documentos de radicados
    'bodegaRadicados' => 'bodega',

    /* Operaciones que se pueden realizar con un expediente  */
    'operacionExpedienteText' => [
        'CrearExpediente' => 1,
        'IncluirExpediente' => 2,
        'CambiarEstado' => 3,
        'ArchivarExpediente' => 4,
        'SubirDocumento' => 5,
        'ExcluirExpediente' => 6,
        'CerrarExpediente' => 7,
        'AbrirExpediente' => 8,
        'cambioInformacionExpediente' => 9,
        'crossReference' => 10,
    ],

    'operacionExpedienteNumber' => [
        1 => 'Creación del Expediente',
        2 => 'Inclusión en Expediente',
        3 => 'Cambio de estado del Expediente',
        4 => 'Archivar Expediente',
        5 => 'Carga documento',
        6 => 'Excluir el Expediente',
        7 => 'Cerrar el Expediente',
        8 => 'AbrirExpediente',
        9 => 'Cambio de información del expediente',
        10 => 'Referencia cruzada'
    ],

    /* Estados para el expediente */
    'statusExpedienteText' => [
        'Cerrado' => 0,
        'Abierto' => 10,
        'PendienteCerrar' => 11,
    ],

    'statusExpedienteNumber' => [
        0 => 'Cerrado',
        10 => 'Abierto',
        11 => 'Pendiente por cerrar',
        # Estado de archivo
        12 => 'Archivado',
        # Estados de transferencia
        13 => 'Pendiente por transferir',
        14 => 'Transferencia aceptada',
        15 => 'Transferencia rechazada',
    ],

    /** Cuerpo en archivo  */
    'cuerpoArchivoText' => [
        'A' => 1,       
        'B' => 2      
    ],

    'cuerpoArchivoNumber' => [
        1 => 'A',      
        2 => 'B'
    ],

    /** Unidad de Conservacion de archivo  */
    'unidadConservacionGaArchivo' => [
        'Carpetas (CAR)' => 1,      // Carpetas 
        'Aceta (A-Z)' => 2,         // Aceta
        'Libro (LB)'  => 3,         // Libro
        'Archivador (AR)'  => 4,    // Archivador
    ],

    'unidadConservacionGaArchivoNumber' => [
        1 => 'Carpetas (CAR)',     // Carpetas 
        2 => 'Aceta (A-Z)',        // Aceta
        3 => 'Libro (LB)',         // Libro
        4 => 'Archivador (AR)',    // Archivador
    ],

    'unidadConservacionFormatoUnicoInventario' => [
        'Caja'    => [],
        'Carpeta' => ['Carpetas (CAR)'],
        'Tomo'    => ['Aceta (A-Z)','Libro (LB)'],
        'Otro'    => ['Archivador (AR)']
    ],

    'ubicacionTransferenciaTRD' => [
        1  => 'Gestión - Primera ubicación', 
        2  => 'Central - Segunda ubicación', 
        3  => 'Historico - Tercera ubicación' 
    ],
    'ubicacionTransTRDNumber' => [
        'gestion' => 1,
        'central'  => 2,
        'historico'  => 3
    ],

    /** Indica si el formulario de radicación es masiva o estandar */
    'formFilingNumber' => [
        'Masiva' => '1',
        'Estandar' => '2',
    ],

    /** Operador lógico para el cambio de motor de base de datos**/
    'like' => 'LIKE',
    'orlike' => 'OR LIKE',

    /** Operador lógico solamente de prueba**/
    'ilike' => 'ILIKE',

    /* Color de filas para dashboard */
    'color' => [
        'rojo' => '#FBD2B9',
        'amarillo' => '#F6FBB9',
    ],
    /** Radicado detalle Pqrs */
    'autorizacionRadiRadicado'=> [
        'number' => [
            0 => 'fisico',
            10 => 'correo'
        ],
        'text' => [
            'fisico' => 0,
            'correo' => 10
        ]
    ],

    /** Clientes ciudadanos detalles */
    'generoClienteCiudadanoDetalle' => [
        [
            'number' => [
                1 => 'Masculino',
                2 => 'Femenino',
                3 => 'Otro',
                4 => 'N/A'
            ],
            'text' => [
                'Hombre' => 1,
                'Mujer' => 2,
                'Otro' => 3,
                'N/A' => 4,
            ]        
        ]
    ],

    'rangoEdadClienteCiudadanoDetalle' => [
        [
            'number' => [
                1 => '7-18', 
                2 => '19-29',
                3 => '30-58',
                4 => '59-',
                5 => 'N/A'
            ],
            'text' => [
                '7-18' => 1,
                '19-29' => 2,
                '30-58' => 3,
                '59-' => 4,
                'N/A' => 5
            ]
        ]
    ],

    'vulnerabilidadClienteCiudadanoDetalle' => [
        'number' => [
            1 => 'Niña - Niño - Adolescente',
            2 => 'Desplazado(a)',
            3 => 'Víctima conflicto armado',
            4 => 'Persona con discapacidad',
            5 => 'Migrante',
            6 => 'Ninguna'
        ],
        'text' => [
            'Niña-Niño-Adolescente' => 1,
            'Desplazado' => 2,
            'VictimaConflictoArmado' => 3,
            'Discapacidad' => 4,
            'Migrate' => 5,
            'Ninguna' => 6
        ]     
    ],

    'etniaClienteCiudadanoDetalle' => [
        [
            'number' => [
                1 => 'Indígena',                
                2 => 'Afrocolombiano',
                3 => 'Palenquero',
                4 => 'Raizal',
                5 => 'ROM',
                6 => 'Ninguna'                 
            ],
            'text'  => [
                'Indígena' => 1,
                'Afrocolombiano' => 2,
                'Palenquero' => 3,
                'Raizal' => 4,
                'ROM' => 5,
                'Ninguna' => 6
            ]
        ]
    ],

    'prioridadRadicado' => [
        'alta' => 1,
        'baja' => 0
    ],
    'anexosPaginaPublica' => 'Anexo cargado por el ciudadano para darle continuidad al trámite',

    'exceptiosFilePqrs' => [
        0 => 'pdf', 
        1 => 'jpg', 
        2 => 'png', 
        3 => 'docx', 
        4 => 'xlsx', 
        5 => 'doc', 
        6 => 'xls', 
        7 => 'mp3', 
        8 => 'mp4', 
        9 => 'tif', 
        10 => 'txt', 
        11 => 'odt', 
        12 => 'avi'
    ],
    'exceptiosFilePqrsFonprecon' => [
        0 => 'pdf', 
        1 => 'jpg', 
        2 => 'png', 
        3 => 'docx', 
        4 => 'xlsx', 
        5 => 'doc', 
        6 => 'xls', 
        7 => 'mp3', 
        8 => 'mp4', 
        9 => 'tif', 
        10 => 'txt', 
        11 => 'odt', 
        12 => 'avi',
    ],
    'seguimientoViaPQRS' => [
        'number' => [
            0 => 'Dirección Física',           
            10 => 'Correo Electrónico'                
        ],
        'text'  => [
            'DirecciónFísica' => 0,
            'CorreoElectrónico' => 10
        ]
    ],

    'downloadType' => [
        'principal' => 1,
        'anexo' => 2     
    ],

    /* Valores de la publicación de la pagina en ORFEO y PAGINA PUBLICA */
    'valuePageNumber' => [
        10 => 'Público',
        0 => 'No público'
    ],

    'valuePageText' => [
        'Publico' => 10,
        'NoPublico' => 0
    ],

    /** Limite establecido para los registros que se muestran en la página pública del index **/
    'limitPagePublicRecords' => 5,

    /** Variable para el proceso de OCR y extracción de texto de los documentos **/
    'tablaOcrDatos' => [
        'radiDocumentos' => 1,
        'radiDocPrincipales' => 2,
        'gdExpedienteDoc' => 3, 
    ],

    'tablaOcrDatosNumber' => [
        1 => 'radiDocumentos',
        2 => 'radiDocPrincipales',
        3 => 'gdExpedienteDoc', 
    ],

    /***** Data proveedores externos *****/

    /***** Cargo *****/
    'proveedoresExternosCargo' => 'Proveedor externo',
    'statusErrorAuthorization' => 401,
    'statusErrorAuthorizationTextProveedorExterno' => 'No autorizado, verifique que exista el token y que sea valido (Authorization: Bearer z1a...), si el problema persiste valide con el administrador del sistema, es posible que negaran su acceso.',
    'statusErrorAuthorizationText' => 'No autorizado, verifique que exista el token y que sea valido (Authorization: Bearer z1a...), si el problema persiste valide con el administrador del sistema, es posible que negaran su acceso.',
    'successProveedorExterno' => 'Éxito',
    'errorProveedorExterno' => 'Error',
    'errorProveedorExternoTextTramitadorCreadorSimilar' => 'idUserTramitador no puede ser igual al id del creador',
    'errorUserDependenciaExterno' => 'El usuario no pertenece a la dependencia seleccionada',

    /*** Estructuras Json ***/
    'radicadosCreateExternos' => [
        'idTipoDocumental' => 'idGdTrdTipoDocumental',
        'documentoIdentidadTramitador' => 'id',
        'idCliente' => 'idCliente',
        'foliosRadicado' => 'foliosRadicado'
    ],

    /*** Estructuras Json ***/
    'radicadosCreateExternosVsbd' => [
        'idTipoDocumental' => 'api\models\GdTrdTiposDocumentales|idGdTrdTipoDocumental',
        'documentoIdentidadTramitador' => 'api\models\UserDetalles|documento',
        'idCliente' => 'api\models\Clientes|idCliente',
        'idTipoRadicado' => 'api\models\CgTiposRadicados|idCgTipoRadicado',
        // 'idDependenciaUserTramitador' => 'api\models\User|id',
        'idTramite' => 'api\models\CgTramites|idCgTramite',
        'idMedioRecepcion' => 'api\models\CgMediosRecepcion|idCgMedioRecepcion',
    ],

    /***** Fin data proveedores externos *****/

    /** Configuración de la estructura del radicado*/
    'estructuraRadicado' => [

        /** Combinación solo dependncia*/
        'anioDepeConsTipoRad' => 'yyyy-depe-consecutivo-tiporad',
        'depeAnioConsTipoRad' => 'depe-yyyy-consecutivo-tiporad', 
        'tipoRadAnioDepeCons' => 'tiporad-yyyy-depe-consecutivo',
        'tipoRadDepeAnioCons' => 'tiporad-depe-yyyy-consecutivo',
        'tipoRadDepeConsAnio' => 'tiporad-depe-consecutivo-yyyy',

        /** Combinación de solo regional y año */
        'anioRegionalConsTipoRad' => 'yyyy-regional-consecutivo-tiporad',
        'regionalAnioConsTipoRad' => 'regional-yyyy-consecutivo-tiporad', 
        'tipoRadAnioRegionalCons' => 'tiporad-yyyy-regional-consecutivo',
        'tipoRadRegionalAnioCons' => 'tiporad-regional-yyyy-consecutivo',
        'tipoRadRegionalConsAnio' => 'tiporad-regional-consecutivo-yyyy',
        
        /** Combinación con regional y dependncia*/
        'RegionalTipoRadDepeConsAnio' => 'regional-tiporad-depe-consecutivo-yyyy',
        'tipoRadRegionalDepeConsAnio' => 'tiporad-regional-depe-consecutivo-yyyy',
        'tipoRadDepeRegionalConsAnio' => 'tiporad-depe-regional-consecutivo-yyyy',
        'tipoRadDepeConsRegionalAnio' => 'tiporad-depe-consecutivo-regional-yyyy',
        'tipoRadDepeConsAnioRegional' => 'tiporad-depe-consecutivo-yyyy-regional',

        /** Combinación con regional y dependencia pero con año y mes */
        'RegionalTipoRadDepeConsAnioMes' => 'regional-tiporad-depe-consecutivo-yyyymm',
        'tipoRadRegionalDepeConsAnioMes' => 'tiporad-regional-depe-consecutivo-yyyymm',
        'tipoRadDepeRegionalConsAnioMes' => 'tiporad-depe-regional-consecutivo-yyyymm',
        'tipoRadDepeConsRegionalAnioMes' => 'tiporad-depe-consecutivo-regional-yyyymm',
        'tipoRadDepeConsAnioMesRegional' => 'tiporad-depe-consecutivo-yyyymm-regional',

        /** Combinación con solo dependencia pero con año, mes y dia */
        'anioMesDiaDepeConsTipoRad' => 'yyyymmdd-depe-consecutivo-tiporad',
        'depeAnioMesDiaConsTipoRad' => 'depe-yyyymmdd-consecutivo-tiporad',
        'tipoRadAnioMesDiaDepeCons' => 'tiporad-yyyymmdd-depe-consecutivo',
        'tipoRadDepeAnioMesDiaCons' => 'tiporad-depe-yyyymmdd-consecutivo',

        /** Combinación con dependencia y reginal pero con año, mes y dia */
        'RegionalTipoRadDepeConsAnioMesDia' => 'regional-tiporad-depe-consecutivo-yyyymmdd',
        'tipoRadRegionalDepeConsAnioMesDia' => 'tiporad-regional-depe-consecutivo-yyyymmdd',
        'tipoRadDepeRegionalConsAnioMesDia' => 'tiporad-depe-regional-consecutivo-yyyymmdd',
        'tipoRadDepeConsRegionalAnioMesDia' => 'tiporad-depe-consecutivo-regional-yyyymmdd',
        'tipoRadDepeConsAnioMesDiaRegional' => 'tiporad-depe-consecutivo-yyyymmdd-regional'
    ],

    /** Campos de titulos de los reportes **/
    'cabeceraReportes' => [
        # Reporte productividad
        1 => [ 
            [ 'title' => 'Número radicado',          'data' => 'fileNumber'],
            [ 'title' => 'Fecha radicado',           'data' => 'filingDate'],
            [ 'title' => 'Fecha vencimiento',        'data' => 'expirationDate' ],
            [ 'title' => 'Remitente/Destinatario',   'data' => 'senderName' ],
            [ 'title' => 'Dependencia Tramitadora',  'data' => 'processingDependency'],
            [ 'title' => 'Usuario tramitador',       'data' => 'processingUser' ],
            [ 'title' => 'Dependencia creador',      'data' => 'creatorDependency' ],            
            [ 'title' => 'Usuario creador',          'data' => 'creatorUser' ],
            [ 'title' => 'Tipo radicado',            'data' => 'filedType' ],
            [ 'title' => 'Tipo documental',          'data' => 'documentaryType' ],
            [ 'title' => 'Medio de Recepción',       'data' => 'receptionType' ],
            [ 'title' => 'Estado',                   'data' => 'estado' ]
        ],

        # Reporte distribución de correspondencia
        2 => [
            [ 'title' => 'Tipo radicado',        'data' => 'typeFiled' ],
            [ 'title' => 'Número radicado',      'data' => 'fileNumber' ],
            [ 'title' => 'Asunto',               'data' => 'subject' ],
            [ 'title' => 'Proveedor de envíos',  'data' => 'providerName' ],
            [ 'title' => 'Fecha radicado',       'data' => 'filingDate' ],
            [ 'title' => 'Fecha envío',          'data' => 'dateSending' ],
            [ 'title' => 'Fecha de entrega',     'data' => 'dateDeliver' ],
            [ 'title' => 'Fecha devolución',     'data' => 'dateReturned' ],
            [ 'title' => 'Guia',                 'data' => 'deliveryNumber' ],
            [ 'title' => 'Motivo de devolución', 'data' => 'reasonReturn' ],
            [ 'title' => 'Estado',               'data' => 'estado' ]                   
        ],

        # Reporte de radicado PQRSD
        3 => [
            [ 'title' => 'Número radicado',          'data' => 'fileNumber' ],
            [ 'title' => 'Fecha radicado',           'data' => 'filingDate' ],
            [ 'title' => 'Fecha vencimiento',        'data' => 'expirationDate' ],
            [ 'title' => 'Asunto',                   'data' => 'subject' ],
            [ 'title' => 'Remitente/Destinatario',   'data' => 'senderName' ],
            [ 'title' => 'Dependencia Tramitadora',  'data' => 'processingDependency' ],
            [ 'title' => 'Usuario tramitador',       'data' => 'processingUser' ],
            [ 'title' => 'Tipo documental',          'data' => 'documentaryType' ],
            [ 'title' => 'Transacción',              'data' => 'transaction' ],
            [ 'title' => 'Días transcurridos',       'data' => 'daysDifference' ]         
        ],

        # Reporte expediente electrónico
        4 => [
            [ 'title' => 'Nº Expediente',           'data' => 'expedientNumber' ],
            [ 'title' => 'Serie',                   'data' => 'serieName' ],
            [ 'title' => 'Subserie',                'data' => 'subserieName' ],
            [ 'title' => 'Fecha proceso',           'data' => 'processDate' ],
            [ 'title' => 'Dependencia creador',     'data' => 'dependency'  ],
            [ 'title' => 'Usuario creador',         'data' => 'userDependency' ],
            [ 'title' => 'Número radicado',         'data' => 'fileNumber' ],
            [ 'title' => 'Tipo documental',         'data' => 'documentaryType' ]
        ],

        # Reporte prestamo documental
        5 => [
            [ 'title' => 'Número radicado',                 'data' => 'fileNumber' ],
            [ 'title' => 'Asunto',                          'data' => 'subject' ],
            [ 'title' => 'Tipo préstamo',                   'data' => 'loanType' ],
            [ 'title' => 'Fecha solicitud préstamo',        'data' => 'requestDate' ],
            [ 'title' => 'Fecha devolución préstamo',       'data' => 'returnedDate' ],
            [ 'title' => 'Fecha aprobación préstamo',       'data' => 'approvedDate' ],
            [ 'title' => 'Fecha cancelación préstamo',      'data' => 'canceledDate' ],
            [ 'title' => 'Días transcurridos entre fechas', 'data' => 'daysDifference' ],         
            [ 'title' => 'Dependencia solicitada',          'data' => 'dependencyLoan' ],
            [ 'title' => 'Usuario solicitante',             'data' => 'userLoan' ]
        ],

        # Reporte formulario de caracterización
        6 => [
            [ 'title' => 'Cliente',            'data' => 'clientName' ],
            [ 'title' => 'Direccion',          'data' => 'address' ],
            [ 'title' => 'Correo electrónico', 'data' => 'email' ],
            [ 'title' => 'Fecha de registro',  'data' => 'registrationDate' ],
            [ 'title' => 'Género',             'data' => 'gender' ],
            [ 'title' => 'Rango de edad',      'data' => 'ageRange' ],
            [ 'title' => 'Vulnerabilidad',     'data' => 'vulnerability' ],
            [ 'title' => 'Etnia',              'data' => 'ethnicity' ],
            [ 'title' => 'País',               'data' => 'country' ],
            [ 'title' => 'Departamento',       'data' => 'department' ],
            [ 'title' => 'Municipio',          'data' => 'municipality' ]            
        ],

        # Reporte general
        7 => [
            [ 'title' => 'Número radicado',            'data' => 'fileNumber' ],
            [ 'title' => 'Tipo radicado',              'data' => 'filedType' ],
            [ 'title' => 'Fecha radicado',             'data' => 'filingDate' ],
            [ 'title' => 'Fecha vencimiento',          'data' => 'expirationDate' ],
            [ 'title' => 'Asunto',                     'data' => 'subject' ],
            [ 'title' => 'Tipo documental',            'data' => 'documentaryType' ],
            [ 'title' => 'Medio de Recepción',         'data' => 'receptionType' ],
            [ 'title' => 'Remitente/Destinatario',     'data' => 'senderName' ],
            [ 'title' => 'Regional',                   'data' => 'regional' ],
            [ 'title' => 'Dependencia Tramitadora',    'data' => 'processingDependency' ],            
            [ 'title' => 'Usuario tramitador',         'data' => 'processingUser' ],            
            [ 'title' => 'Dependencia creador',        'data' => 'creatorDependency' ],            
            [ 'title' => 'Usuario creador',            'data' => 'creatorUser' ],            
            [ 'title' => 'Dependencia de informados',  'data' => 'informedDependency' ],            
            [ 'title' => 'Usuarios informados',        'data' => 'informedUser' ],
            [ 'title' => 'Último estado radicado',     'data' => 'estado' ]
        ],

        # Reporte trazabilidad del radicado
        8 => [
            [ 'title' => 'Número radicado',         'data' => 'fileNumber'],
            [ 'title' => 'Tipo radicado',           'data' => 'filedType'],
            [ 'title' => 'Fecha radicado',          'data' => 'filingDate' ],
            [ 'title' => 'Asunto',                  'data' => 'subject' ],
            [ 'title' => 'Remitente/Destinatario',  'data' => 'senderName' ],
            [ 'title' => 'Dependencia Tramitadora', 'data' => 'processingDependency' ],            
            [ 'title' => 'Usuario tramitador',      'data' => 'processingUser' ],
            [ 'title' => 'Fecha transacción',       'data' => 'transactionDate' ],
            [ 'title' => 'Observación',             'data' => 'observation' ],
            [ 'title' => 'Usuario Responsable',     'data' => 'userTransaction' ],
            [ 'title' => 'Estado transacción',      'data' => 'estado' ]
        ],

        # Reporte de usuarios
        9 => [
            [ 'title' => 'Dependencia Tramitadora',              'data' => 'processingDependency'],
            [ 'title' => 'Usuario tramitador',                   'data' => 'processingUser'],
            [ 'title' => 'Total documentos',                     'data' => 'totalDocuments' ],
            [ 'title' => 'Total por tipo salida',                'data' => 'totalFiledTypeExit' ],
            [ 'title' => 'Total por tipo entrada',               'data' => 'totalFiledTypeEntry' ],
            [ 'title' => 'Total por tipo PQRSD',                 'data' => 'totalFiledTypePqrs' ],
            [ 'title' => 'Total por tipo comunicación interna',  'data' => 'totalFiledTypeCommunication' ],
            [ 'title' => 'Radicados nuevos',                     'data' => 'totalNewFile'],
            [ 'title' => 'Radicados con respuesta',              'data' => 'totalFiledAnswer' ],
            [ 'title' => 'Radicados vencidos',                   'data' => 'totalFiledExpired' ]
        ],

        # Reporte de respuestas y tiempos de contestación
        10 => [
            [ 'title' => 'Número radicado',         'data' => 'fileNumber'],
            [ 'title' => 'Tipo radicado',           'data' => 'filedType'],
            [ 'title' => 'Fecha radicado',          'data' => 'filingDate' ],
            [ 'title' => 'Asunto',                  'data' => 'subject' ],
            [ 'title' => 'Remitente/Destinatario',  'data' => 'senderName' ],
            [ 'title' => 'Dependencia Tramitadora', 'data' => 'processingDependency' ],            
            [ 'title' => 'Usuario tramitador',      'data' => 'processingUser' ],
            [ 'title' => 'Número de respuesta',     'data' => 'numberResponse' ],
            [ 'title' => 'Fecha de respuesta',      'data' => 'dateResponse' ],
            [ 'title' => 'Tiempo de respuesta',     'data' => 'daysResponse' ],
            [ 'title' => 'Dependencia Responsable', 'data' => 'responsibleDependency' ],
            [ 'title' => 'Usuario Responsable',     'data' => 'responsibleUser' ]
        ],

        # Reporte documentos pendientes
        11 => [
            [ 'title' => 'Número radicado',         'data' => 'fileNumber'],
            [ 'title' => 'Tipo radicado',           'data' => 'filedType'],
            [ 'title' => 'Fecha radicado',          'data' => 'filingDate' ],
            [ 'title' => 'Fecha vencimiento',       'data' => 'expirationDate' ],
            [ 'title' => 'Asunto',                  'data' => 'subject' ],
            [ 'title' => 'Remitente/Destinatario',  'data' => 'senderName' ],
            [ 'title' => 'Dependencia Tramitadora', 'data' => 'processingDependency' ],            
            [ 'title' => 'Usuario tramitador',      'data' => 'processingUser' ],
            [ 'title' => 'Días transcurridos',      'data' => 'days' ]
        ],

        # Reporte usuarios activos
        12 => [
            [ 'title' => 'Dependencia',  'data' => 'dependency' ],
            [ 'title' => 'Usuario',      'data' => 'userName' ],
            [ 'title' => 'Cargo',        'data' => 'job' ],
            [ 'title' => 'Perfil',       'data' => 'role' ]     
        ],

        # Reporte con permiso de firma
        13 => [
            [ 'title' => 'Dependencia',                 'data' => 'dependency' ],
            [ 'title' => 'Usuario',                     'data' => 'userName' ],
            [ 'title' => 'Documento Identificación',    'data' => 'identification' ]  
        ],

        # Reporte de devolución
        14 => [
            [ 'title' => 'Número radicado',            'data' => 'fileNumber'],            
            [ 'title' => 'Fecha radicado',             'data' => 'filingDate' ],
            [ 'title' => 'Tipo radicado',              'data' => 'filedType'],
            [ 'title' => 'Asunto',                     'data' => 'subject' ],
            [ 'title' => 'Dependencia creador',        'data' => 'creatorDependency' ],            
            [ 'title' => 'Usuario creador',            'data' => 'creatorUser' ],
            [ 'title' => 'Dependencia Tramitadora',    'data' => 'processingDependency' ],            
            [ 'title' => 'Usuario tramitador',         'data' => 'processingUser' ],
            [ 'title' => 'Dependencia que devuelve',   'data' => 'transactionDependency' ],            
            [ 'title' => 'Usuario que devuelve',       'data' => 'transactionUser' ],
            [ 'title' => 'Observación',                'data' => 'observation' ],
            [ 'title' => 'Estado',                     'data' => 'estado' ]      
        ],

        # Reporte remitente destinatario
        15 => [
            [ 'title' => 'Documento Identificación',    'data' => 'document' ],
            [ 'title' => 'Remitente',                   'data' => 'senderName' ],
            [ 'title' => 'Correo electrónico',          'data' => 'email' ],  
            [ 'title' => 'Direccion',                   'data' => 'address' ]
        ],

        # Reporte permiso rol
        16 => [
            [ 'title' => 'Rol',         'data' => 'role' ],
            [ 'title' => 'Módulo',      'data' => 'module' ],
            [ 'title' => 'Submódulo',   'data' => 'submodule' ],  
            [ 'title' => 'Permiso',     'data' => 'operation' ]
        ],

        # Reporte permiso rol
        17 => [
            [ 'title' => 'Dependencia',         'data' => 'nombreGdTrdDependencia' ],
            [ 'title' => 'Día respuesta 0',     'data' => 'D0' ],
            [ 'title' => 'Día respuesta 1',     'data' => 'D1' ],  
            [ 'title' => 'Día respuesta 2',     'data' => 'D2' ],
            [ 'title' => 'Día respuesta 3',     'data' => 'D3' ],
            [ 'title' => 'Día respuesta 4',     'data' => 'D4' ],
            [ 'title' => 'Día respuesta 5',     'data' => 'D5' ],
            [ 'title' => 'Día respuesta 6',     'data' => 'D6' ],
            [ 'title' => 'Día respuesta 7',     'data' => 'D7' ],
            [ 'title' => 'Día respuesta 8',     'data' => 'D8' ],
            [ 'title' => 'Día respuesta 9',     'data' => 'D9' ],
            [ 'title' => 'Día respuesta 10',    'data' => 'D10' ],
            [ 'title' => 'Día respuesta 11',    'data' => 'D11' ],
            [ 'title' => 'Día respuesta 12',    'data' => 'D12' ],
            [ 'title' => 'Día respuesta 13',    'data' => 'D13' ],
            [ 'title' => 'Día respuesta 14',    'data' => 'D14' ],
            [ 'title' => 'Día respuesta 15',    'data' => 'D15' ],
            [ 'title' => 'Día respuesta +15',   'data' => 'D15+' ],
            [ 'title' => 'Pte Rta',             'data' => 'pendienteRespuesta' ],
            [ 'title' => 'Total Resultado',     'data' => 'total' ]
        ],
    ],

    /**
    * Params para el proceso de reinicio de secuencias y creación de bodega con el fin de guardar
    * en el log del sistema los registros correspondientes a cada uno de los proceso realizados
    * de forma automatica todos los inicios de años.
    **/
    'mensajesIniciales' => [
        'secuencias' => 'Se ejecutó el reinicio de secuencias automático por el año nuevo.',
        'bodega' => 'Se creó la bodega correspondiente a las dependencias acordes al año nuevo.'
    ],

    /* Nombres de soporte (P,E,O) de la TRD*/
    'soporteP' => 'Papel',
    'soporteE' => 'Electrónico',
    'soporteO' => 'Otros soportes',

    /* Nombres de la disposicion (CT, E, S, M) de la TRD */
    'disposicionCt' => 'Conservación Total',
    'disposicionE' => 'Eliminación',
    'disposicionS' => 'Selección',
    'disposicionM' => 'Medio Digital ',

    /* Campo origenGdIndice de la tabla de GdIndices */
    'origen' => [
        'Digitalizado' => 1,
        'Fisico' => 2,
        'Electronico' => 3,
    ],

    'origenNumber' => [
        1 => 'Digitalizado',
        2 => 'Físico',
        3 => 'Electrónico',
    ],

    /** 
     * Variables para saber la longitud de los nombres de las dependencias, series, subseries y tipos 
     * documentales, se utilizan en controlador de TRD
     **/
    'longitudColumnas' => [
        'nombreGdTrdDependencia' => 255,
        'nombreGdTrdSerie' => 255,
        'nombreGdTrdSubserie' => 255,
        'nombreTipoDocumental' => 255,
    ],

    /** CUADRO DE CLASIFICACION DOCUMENTAL **/
    'codigo' => '710,14,15-14', // Codigo para el cuadro de clasificacion documental
    'version' => '01', // Version para el cuadro de clasificacion documental

    /**
    * Esta configuración se esta comparando con el campo idCgTipoRadicado, el cual hace referencia a los identificadores de la
    * tabla, esto debe concordar con los sql iniciales que se carguen en la parametrización asi que esto no puede cambiar de
    * orden o de identificador. 
    **/
    'idCgTipoRadicado' => [
        'radiSalida' => 1,
        'radiEntrada' => 2,
        'radiPqrs' => 4,
        'comunicacionInterna' => 3,
        'radiFactura' => 100,
    ],

    /**
    * Esta configuración se esta comparando con el campo idCgTipoRadicado, el cual hace referencia a los identificadores de la
    * tabla, esto debe concordar con los sql iniciales que se carguen en la parametrización asi que esto no puede cambiar de
    * orden o de identificador. Ids en BD de los tipos de radicados
    **/
    'CgTiposRadicadosText' => [
        'Salida' => 1, 
        'Entrada' => 2,
        'Pqrsd' => 4, 
        'comunicacionInterna' => 3,
    ],

    /**
     * Configuración de rutas manuales para definir la data de los botones
     */
    'transaccionMostrarConfigManual' => [
        'descargarEtiquetaExternaFirmaFisica' => [
            'route'  => 'version1%radicacion%transacciones%print-external-sticker',
            'action' => 'printExternal',
            'title'  => 'Imprimir etiqueta externa',
            'icon'   => 'downloading',
            'data'   => '',
        ],
        'cargarDocumentoFirmaFisica' => [
            'route'  => 'version1%radicacion%documentos%upload-signed-document',
            'action' => 'uploadSignedDocument',
            'title'  => 'Cargar documento firmado',
            'icon'   => 'upload_file',
            'data'   => '',
        ],
        'detalleResolucion' => [
            'route'  => 'version1%radicacion%radicados-resoluciones%create-update',
            'action' => 'createResolutionDetail',
            'title'  => 'Detalle de resolución',
            'icon'   => 'wysiwyg',
            'data'   => '',
        ]
    ],

    /**
     * Eliminar transacciones
     */
    'deleteTransacciones' => [
        'procesoFirmaFisica' => [
            0 => ['route' => 'version1%radicacion%transacciones%load-format']
        ]
    ],

    /**
     * Array para las transacciones que deben ser validadas posterior a la función radicadoTransacciones
     */
    'validateTransacciones' => [
        'incluirExpediente' => [
            'route'  => 'version1%radicacion%transacciones%include-expedient',
            'action' => 'includeInFile',
            'title'  => 'Incluir en expediente',
            'icon'   => 'assignment_turned_in',
            'data'   => '',
        ],
        'cargarAnexos' => [
            'route'  => 'version1%radicacion%documentos%upload-document',
            'action' => 'attachment',
            'title'  => 'Cargar anexos',
            'icon'   => 'attachment',
            'data'   => '',
        ]
    ],

    /**
     * Array para extraer el consecutivo de ciertos tipos de radicado
     */
    'extraerConsecutivoXtipoRadicado' => [
        6, //RESOLUCIONES
        7, //ACTAS
        5, //CIRCULARES
    ],

    /**
     * Configuración del consecutivo para el módulo de gestión de arvhico
     */
    'consecutivoGaArchivo' => 50000,
    /**
     * Datos para indicar los nombres de bases de datos
     */
    'nombreCgGeneralBasesDatos' => [
        'orfeo38' => 'Orfeo38'
    ],
    /**
     * Ruta para descargar archivos de orfeos antiguo
     */
    'rutaDocsOrfeo38' => 'docs',
    'rutaDocumentosOrfeo38' => '/dk1/orfeo-3.8',
    'rutaDocumentosOrfeo38AlmaArchivos' => '/var/www/html/',

    /**
     * Nombre texto resoluciones
     */
    'nombreResoluciones' => 'RESOLUCIONES',

    /**
     * Configuración para los formularios de pqrs
     */
    'formsRegistroPqrs' => [
        '1' => 'actionIndexPension',
        '1-1' => 'actionIndexPensionInvalidez',
        '7' => 'actionIndexDefault',
    ],
    'formsRegistroPqrsText' => [
        'actionIndexPension' => '1',
        'actionIndexPensionInvalidez' => '1-1',
        'actionIndexDefault' => '7',
    ],
    'formsRegistroPqrsIdDefault' => '7',
    'medioRespuesta' => [
        'Dirección física' => 'Dirección física',
        'Correo electrónico' => 'Correo electrónico'
    ],
    'calidadCausante' => [
        'Afiliado' => 'Afiliado',
        'Pensionado' => 'Pensionado'
    ],
    'tipoEmpleador' => [
        'Senado de la república' => 'Senado de la república',
        'Cámara de representantes' => 'Cámara de representantes',
        'Fonprecon' => 'Fonprecon',
    ],
    'categoriaPrestacion' => [
        'Generales' => 'Generales',
        'Seleccione un tipo de beneficiario' => 'Seleccione un tipo de beneficiario',
    ],
    'calidadBeneficiario' => [
        'Respecto de conyuge o compañera (o) permanente' => 'Respecto de conyuge o compañera (o) permanente',
        'Respecto de los hijos' => 'Respecto de los hijos',
        'Respecto de los padres' => 'Respecto de los padres',
        'Respecto de los hermanos invalidos y hermanos menores de edad (huerfanos)' => 'Respecto de los hermanos invalidos y hermanos menores de edad (huerfanos)',
    ],
    'categoriaPrestacionDos' => [
        'Definitivas' => 'Definitivas',
        'Pos-morte' => 'Pos-morte',
        'Parciales, dependiendo del motivo del retiro debe anexar la siguiente documentación adicional:' => 'Parciales, dependiendo del motivo del retiro debe anexar la siguiente documentación adicional:',
        'Para traslado de cesantías al Fondo Nacional del Ahorro' => 'Para traslado de cesantías al Fondo Nacional del Ahorro',
    ],
    'calidadBeneficiarioDos' => [
        'Para adquisición de vivienda' => 'Para adquisición de vivienda',
        'Para reparación, ampliación y mejora de vivienda' => 'Para reparación, ampliación y mejora de vivienda',
        'Para amortización o cancelación de hipoteca' => 'Para amortización o cancelación de hipoteca',
        'Para construcción de vivienda' => 'Para construcción de vivienda',
        'Para estudios' => 'Para estudios',
    ],
    'cgFormulariosPqrsIdPqrs' => 7,

    # Configuración OrfeoExpress
    'orfeoNgExpress' => false,
    'modulosNgExpress' => [
        'administraPrestamo' => 'Administrar préstamo',
        'administraPrestamoExpediente' => 'Administrar préstamo de expedientes',
        'archivarRadicado' => 'Archivar radicado',
        'asignacionEspacioFisico' => 'Asignación de espacio físico',
        'configuracionCragaTrd' => 'Configuración carga TRD',
        'configuracionRadicado' => 'Configuración del radicado',
        'consultaAvanzada' => 'Consulta avanzada',
        'encuestaSatisfaccion' => 'Encuesta de satisfacción',
        'grupoUsuarios' => 'Grupo de usuarios',
        'prestamoExpedientes' => 'Préstamo de expedientes',
        'principalRegionales' => 'Principal y regionales',
        'reasignacionMasiva' => 'Reasignación masiva',
        'solicitarPrestamo' => 'Solicitar préstamo',
        'tiposRadicado' => 'Tipos de radicado',
        'transferenciasDocumentales' => 'Transferencias documentales',
        'usuarioInteroperabilidad' => 'Usuarios interoperabilidad',
        'versionamientoTrd' => 'Versionamiento TRD',
    ],

    /** Data para las entradas fijas(Menú del sistema) */
    'entradasMenuFija' => [
        'about' => [
            'ruta' => 'about',
            'idModulo' => 99,
            'nombre' => 'Acerca de',
            'type' => 'link',
            'icontype' => 'info',
            'collapse' => 'collapseabout',
            'children' => [],
            'statusModulo' => 10
        ]
    ],
    /** Data para las entradas fijas(Operaciones del sistema) */
    'entradasOperacionesFija' => [
        'about' => [
            'idModulo' => 99,
            'ruta' => 'version1%default%view',
        ],
    ],

    'usuarioExterno' => [
        'SkinaScan' => 'SkinaScan',
    ],
];
