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


namespace api\components;
use Yii;
use api\components\HelperQueryDb;
use api\components\HelperOtherConnectionsDb;

use api\models\UserTipo;
use api\models\Roles;
use api\models\GdTrdDependencias;
use api\models\CgTiposRadicados;
use api\models\GdTrdTiposDocumentales;
use api\models\RadiEstadosAnulacion;
use api\models\RadiRadicados;
use api\models\UserDetalles;
use api\models\RadiRemitentes;
use api\models\Clientes;
use api\models\CgMediosRecepcion;
use api\models\CgProveedores; 
use api\models\CgEnvioServicios; 
use api\models\CgMotivosDevolucion; 
use api\models\CgRegionales;
use api\models\CgTransaccionesRadicados;
use api\models\GaBodega;
use api\models\GaEdificio;
use api\models\GaPiso;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\GdTrdSeriesTmp;
use api\models\GdTrdSubseriesTmp;
use api\models\GdTrdTiposDocumentalesTmp;
use api\models\GdTrdDependenciasTmp;
use api\models\User;
use api\models\GdTrd;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\RolesModulosOperaciones;
use api\models\TiposPersonas;
use api\models\CgFirmasCertificadas;

/**
 * Clase para enviar data al front que sirve para crear formularios
 */
class HelperDynamicForms
{

    public static function createDataForForm($formType)
    {

        // $strJsonFileContents = file_get_contents( Yii::getAlias('@webroot').'/dynamicForms/es/anexos7A.json');
        // $varAA = str_replace(array( '"*', '*"') , '', $strJsonFileContents);
        // $array = json_decode($varAA, true);

        /*** Filtos de anexo ***/


        // Index User
        $form['indexUser'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'user/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de roles
        $form['indexRol'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'roles/roles/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de roles operaciones
        $form['indexRolOperation'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'roles/roles-operaciones/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
        
        // Index de Radicados
        $form['indexRadicado'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de Radicados Correspondencia
        
        $form['indexRadicadoCorrespondencia'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

         // Index de Radicados Correspondencia
        
         $form['indexArchivoRadicado'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de cg trd  
        $form['indexCgTrd'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-trd/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de Gd trd  
        $form['indexGdTrd'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/trd-tmp/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        $form['indexGdTrdTmp'] = [
            'configGlobal' => [
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/trd-tmp/index',
            ],
             // schema
             'schema' => Yii::t('app', $formType),
        ];

        $form['indexDependencias'] = [
            'configGlobal' => [
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/trd-dependencias/index',
            ],
             // schema
             'schema' => Yii::t('app', $formType),
        ];
   

        // Index de proveedores
        $form['indexCgProveedores'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-proveedores/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de proveedores externos
        $form['indexCgProveedoresExternos'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-proveedores-externos/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de Firmas Certificadas
        $form['indexCgFirmasCertificadas'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-firmas-certificadas/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // indexReasignacionRadicado
        $form['indexReasignacionRadicado'] = array(
            'configGlobal' => array(
                'routeChange' => 'user/index-list-by-depe-filter',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/reasignacionRadicados/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // index de CgTipoRadicado
        $form['indexCgTiposRadicados'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-tipos-radicados/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
        
        
        // Index de anulación de radicados
        $form['indexAnulacion'] = array(
            'configGlobal' => array(
                'routeChange' => 'user/index-list-by-depe-filter',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/anulacion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );


         // Index de radicación email
         $form['indexRadicacionEmail'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/radicacion-email/receiving-mail',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

         // Index de Log de auditoria
         $form['indexLogAuditoria'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/log/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );


        // Index de Motivos devolucion
        $form['indexCgMotivos'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-motivos-devolucion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de grupos de usuarios
        $form['indexCgGuposUsuarios'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/grupos-usuarios/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de usuarios pertenecientes a un grupo
        $form['indexCgGuposUsuariosView'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/grupos-usuarios/view',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de Horario Laborable 
        $form['indexCgHorarioLaboral'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-tiempos-respuesta/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

          // Index de Horario Laborable 
          $form['indexCgDiasNoLaborados'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/cg-dias-no-laborados/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de expediente
        $form['indexExpediente'] = array(
            'configGlobal' => array(
                'routeChange' => 'gestionDocumental/trd-subseries/index-list-by-serie-filter',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/expedientes/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index  
        $form['indexExpedienteTransferencia'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/trd-transferencia/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // View de expediente
        $form['viewExpediente'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionDocumental/expedientes/view',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de espacio fisico
        $form['indexEspacioFisico'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/espacio-fisico/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index prestamo documental
        $form['indexPrestamoDocumental'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index administrar prestamo
        $form['indexAdministrarPrestamo'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index-administrar-prestamo',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
        $form['indexAdministrarPrestamoCentral'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index-administrar-prestamo',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados vencidos del funcionario
        $form['indexFileExpiredOfficial'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-file-expired-official',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados informados del funcionario
        $form['indexFileInformedOfficial'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-file-informed-official',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados devueltos del funcionario
        $form['indexReturnFileOfficial'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-return-file-official',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados vencidos del jefe
        $form['indexFileExpiredBoss'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-file-expired-boss',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados en solicitud VOBO del jefe
        $form['indexVoboRequestBoss'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-vobo-request-boss',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // sguarin Index documentos pendientes firma
        $form['indexUnsignedDocumentsBoss'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-unsigned-documents',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
   
        // Index de prestamos aprobados
        $form['indexApprovalLoan'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-approval-loan',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de prestamos devueltos
        $form['indexReturnLoan'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-return-loan',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de transferencias aceptadas
        $form['indexTransfersAccepted'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-transfers-accepted',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de usuarios por dependencia
        $form['indexUserByDependency'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-user-by-dependency',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de usuarios por perfil
        $form['indexUserByProfile'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-user-by-profile',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del log de auditoria
        $form['indexLastLogEntries'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-last-log-entries',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index radicados creados en el dia por usuario ventanilla
        $form['indexFileCreateWindow'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'dashboard/index-files-created-window',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de las notificaciones
        $form['indexNotificacion'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'notificacion/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        $form['indexCgEncuestas'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/Encuestas',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );  

        // Index de la terceros
        $form['indexCgTerceros'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/terceros/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );


        // Index de la regional
        $form['indexRegional'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/regionales/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de la configuración general
        $form['indexCgGeneral'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/configuracion-general/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index de la configuración de etiqueta
        $form['indexEtiquetaRadicacion'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'configuracionApp/configuracion-general/index-label',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporteador
        $form['indexCustomReport'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-multi-function',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-productivity',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporte de productividad
        $form['indexReporteProductivo'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-productivity',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index del reporte de radicados PQRS
        $form['indexReportePqrs'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-pqrs',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index del reporte de distribución de correspondencia
        $form['indexReporteCorrespondencia'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-mail-distribution',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporte de expediente electrónico
        $form['indexReporteExpediente'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-electronic-records',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
                
        // Index del reporte de prestamos documentales
        $form['indexReportePrestamos'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-documentary-loans',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporte del formulario de caracterización
        $form['indexReporteCaracterizacion'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-form',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );     
        
        // Index del reporte general
        $form['indexReporteGeneral'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-general',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );   
        
        // Index del reporte de trazabilidad del radicado
        $form['indexReporteTrazabilidad'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-filing-traceability',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );   

        // Index del reporte de usuarios
        $form['indexReporteUsuarios'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-users',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de reportes de radicados por tiempo de respuesta y dependencia
        $form['indexReporteRadiResponseTime'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-users',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporte de respuesta y tiempo de contestación
        $form['indexReporteRespuesta'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-response-and-times',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );  
        
        // Index del reporte de documentos pendientes
        $form['indexReporteDocumentos'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-pending-documents',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );    

        
        // Index del reporte de usuarios activos
        $form['indexReporteUsuariosActivos'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-active-user',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index del reporte de usuarios con permiso de firma
        $form['indexReporteConPermiso'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-user-permission-signature',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index del reporte de devolucion
        $form['indexReporteDevolucion'] =  array(
            'configGlobal' => array(
                'routeChange' => 'dynamicForms/dynamic-forms/list-general-nivel-geo',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-returns',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 
        
        // Index del reporte remitente destinatario
        $form['indexReporteRemitente'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-sender-recipient',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index del reporte permiso por perfil
        $form['indexReporteRolPermiso'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'reportes/index-permission-role',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        ); 

        // Index de la consulta ADI
        $form['indexConsultaAdi'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'consultas/consultas-adi/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index del reporte del formulario de consulta documentos digitalizados
        $form['indexConsultaDocumentos'] =  array(
            'configGlobal' => array(
                'routeChange' => 'user/index-list-by-depe-filter',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/consultas/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index de la consulta ADI
        $form['indexExpedientesAdi'] =  array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'consultas/expedientes-adi/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index préstamo de expendientes
        $form['indexPrestamoExpedientes'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index-loan-files',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        // Index administrador préstamo de expendientes
        $form['indexAdminPrestamoExpedientes'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index-manage-loan-files',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );
        $form['indexAdminPrestamoExpedientesCentral'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'gestionArchivo/prestamo-documental/index-manage-loan-files',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        $form['indexConsultaOrfeoAntiguo'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/consultas-orfeo-antiguo/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        $form['indexDocumentosConsultaOrfeoAntiguo'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/consultas-orfeo-antiguo/index-documentos',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        $form['indexConsultaFondoAcumulado'] = array(
            'configGlobal' => array(
                'routeChange' => '',
                'icon' => 'pageview',
                'titleCard' => 'Filtros de búsqueda',
                'botonSubmitIcon' => 'search',
                'routeSubmit' => 'radicacion/consultas-fondo-acumulado/index',
            ),
            // schema
            'schema' => Yii::t('app', $formType),
        );

        if ( isset($form[$formType]) ) {
            return $form[$formType];
        } else {
            return null;
        }
    
    }

    //$relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiInformados', ['radiInformados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);

    /*** Funcion que construye las listas desplegables ***/
    public static function setListadoBD($formType)
    {
        $nombreFormType = $formType;

        $formType = self::createDataForForm($formType);

        if ($nombreFormType == 'indexUser') {

            // Asignación de valores para la listas para UserTipo
            $listUserTipo = UserTipo::find()
                ->select(["idUserTipo as value", "nombreUserTipo as label"])
                ->where(['<>', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo']])
                ->andWhere(['estadoUserTipo' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreUserTipo'=>SORT_ASC])
                ->asArray()->all();

            $listUseTipo = [];
            foreach ($listUserTipo as $key => $value) {
                $listUseTipo[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listUseTipo;

            // Asignación de valores para la listas para Rol
            $roles = Roles::find()
                ->select(["idRol as value", "nombreRol as label"])
                ->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreRol' => SORT_ASC])
                ->asArray()->all();

            $listRol = [];
            foreach ($roles as $key => $value) {
                $listRol[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listRol;

            // Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listDepe;
        }

        if ($nombreFormType == 'indexDependencias') {
            // Asignación de valores para la listas para las dependencias unidad administrativa
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDepe;

            // Asignación de valores para la listas para las dependencias Principales
            $depePrincipal = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepePrincipal = [];
            foreach ($depePrincipal as $key => $value) {
                $listDepePrincipal[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listDepePrincipal;

        }

        #Listado del index radicado
        if ($nombreFormType == 'indexRadicado') {

            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoRadi;                 


            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

                $listTipoDocu = [];
                foreach ($tipoDocu as $key => $value) {
                    $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoDocu;

            
            # Asignación de valores para la listas para los medios de recepción
            $medios = CgMediosRecepcion::find()
                ->select(["idCgMedioRecepcion as value", "nombreCgMedioRecepcion as label"])
                ->orderBy(['nombreCgMedioRecepcion' => SORT_ASC])
                ->asArray()->all();

                $listMedios = [];
                foreach ($medios as $key => $value) {
                    $listMedios[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listMedios;


            #Nivel de busqueda del logueado
            $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

            if ($idNivelBusqueda != Yii::$app->params['searchLevelText']['Avanzado']) {

                # Listado de dependencias según la dependencia del usuario logueado 
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }

            } else {

                # Listado de dependencias activas
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][9]['templateOptions']['options'] = $listDependencia;
        }

        #Listado de index radicacion/correspondencia
        if($nombreFormType == 'indexRadicadoCorrespondencia'){

            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoRadi;

            // Asignación de valores para la listas para los remitentes
            $listRemitente = [];
            // Consulta de clientes
            $clientes = Clientes::find()->all();
            // Consulta de usuarios
            $userDetalles = UserDetalles::find()->all();

            $RadiRemitentes = RadiRemitentes::find()
            ->select(["idRadiPersona", "idTipoPersona"])
            ->groupBy(['idRadiPersona', 'idTipoPersona'])
            ->all();

            foreach ($RadiRemitentes as $key => $modelRemi ) {

                if( $modelRemi['idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario'] ){
                    // Busca en usuarios
                    foreach ($userDetalles as $key => $modelUser) {
                        if( $modelRemi['idRadiPersona'] == $modelUser['idUser'] ){
                            $listRemitente[] = array('label' => $modelUser['apellidoUserDetalles'].' '.$modelUser['nombreUserDetalles'].' - ('.Yii::t('app', 'Funcionario').')' , 'value' => intval($modelUser['idUser']) );
                        }
                    }
                }else{
                    // Busca en clientes
                    foreach ($clientes as $key => $modelCli) {
                        if( $modelRemi['idRadiPersona'] == $modelCli['idCliente'] ){
                            $listRemitente[] = array('label' => $modelCli['nombreCliente'].' - ('.Yii::t('app', 'Cliente').')' , 'value' => intval($modelCli['idCliente']) );
                        }
                    }
                }
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listRemitente;

            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

            $listTipoDocu = [];
            foreach ($tipoDocu as $key => $value) {
                $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoDocu;

            // Asignación de valores para la listas para los tipos de proveedores
            $Proveedores = CgProveedores::find()
            ->select(["idCgProveedor as value", "nombreCgProveedor as label"])
            ->orderBy(['nombreCgProveedor' => SORT_ASC])
            ->asArray()->all();

            $listProveedores = [];
            foreach ($Proveedores as $key => $value) {
                $listProveedores[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listProveedores;

            // Asignación de valores para la listas para los CgEnvioServicio
            $CgEnvioServicio = CgEnvioServicios::find()
            ->select(["idCgEnvioServicio as value", "nombreCgEnvioServicio as label"])
            ->orderBy(['nombreCgEnvioServicio' => SORT_ASC])
            ->asArray()->all();

            $listServicio = [];
            foreach ($CgEnvioServicio as $key => $value) {
                $listServicio [] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listServicio;

            // Asignación de valores para la listas para los CgMotivosDevolucion
            $CgMotivosDevolucion = CgMotivosDevolucion::find()
            ->select(["idCgMotivoDevolucion as value", "nombreCgMotivoDevolucion as label"])
            ->orderBy(['nombreCgMotivoDevolucion' => SORT_ASC])
            ->asArray()->all();

            $listMotivosDevolucion = [];
            foreach ($CgMotivosDevolucion as $key => $value) {
                $listMotivosDevolucion [] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listMotivosDevolucion;

        }

        #Lista de index radicados finalizados
        if($nombreFormType == 'indexArchivoRadicado' ){

                  
            # Asignación de valores para la listas para los remitentes
            $listRemitente = [];
            // Consulta de clientes
            $clientes = Clientes::find()->all();
            // Consulta de usuarios
            $userDetalles = UserDetalles::find()->all();

            $RadiRemitentes = RadiRemitentes::find()
            ->select(["idRadiPersona", "idTipoPersona"])
            ->groupBy(['idRadiPersona', 'idTipoPersona'])
            ->all();

            foreach ($RadiRemitentes as $key => $modelRemi ) {

                if( $modelRemi['idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario'] ){
                    // Busca en usuarios
                    foreach ($userDetalles as $key => $modelUser) {
                        if( $modelRemi['idRadiPersona'] == $modelUser['idUser'] ){
                            $listRemitente[] = array('label' => $modelUser['apellidoUserDetalles'].' '.$modelUser['nombreUserDetalles'].' - ('.Yii::t('app', 'Funcionario').')' , 'value' => intval($modelUser['idUser']) );
                        }
                    }
                }else{
                    // Busca en clientes
                    foreach ($clientes as $key => $modelCli) {
                        if( $modelRemi['idRadiPersona'] == $modelCli['idCliente'] ){
                            $listRemitente[] = array('label' => $modelCli['nombreCliente'].' - ('.Yii::t('app', 'Cliente').')' , 'value' => intval($modelCli['idCliente']) );
                        }
                    }
                }
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listRemitente;
            #Nivel de busqueda del logueado
            $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
            ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
            ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreTipoDocumental' => SORT_ASC])
            ->asArray()->all();

            $listTipoDocu = [];
            foreach ($tipoDocu as $key => $value) {
                $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
           // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
           $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoDocu;

            ###################### list SERIE
            $gdTrdSeries = GdExpedientesInclusion::find();
            $gdTrdSeries = HelperQueryDb::getQuery('innerJoin', $gdTrdSeries, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);
            $gdTrdSeries = HelperQueryDb::getQuery('innerJoin', $gdTrdSeries, 'gdTrdSeries', ['gdTrdSeries' => 'idGdTrdSerie', 'gdExpedientes' => 'idGdTrdSerie']);
            $gdTrdSeries = HelperQueryDb::getQuery('innerJoin', $gdTrdSeries, 'gdTrd', ['gdTrd' => 'idGdTrdSerie', 'gdTrdSeries' => 'idGdTrdSerie']);

            $gdTrdSeries = $gdTrdSeries->select(['gdTrdSeries.nombreGdTrdSerie  as label', 'gdTrdSeries.codigoGdTrdSerie as code', 'gdTrdSeries.idGdTrdSerie as value', 'gdTrd.versionGdTrd as version'])
            // ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['gdTrdSeries.nombreGdTrdSerie', 'gdTrdSeries.codigoGdTrdSerie', 'gdTrdSeries.idGdTrdSerie', 'gdTrd.versionGdTrd'])
            ->orderBy(['gdTrdSeries.nombreGdTrdSerie' => SORT_ASC])
            ->asArray()->all();


            # Arrgupar IDs de series por (versión TRD, código y nombre de serie)
            $listGdTrdSeriesGroup = [];
            foreach ($gdTrdSeries as $row) {
                $label = 'V'.$row['version'].' - '.$row['code'].' - '.$row['label'];
                if ( isset($listGdTrdSeriesGroup[$label]) ) {
                    array_push($listGdTrdSeriesGroup[$label], intval($row['value']));
                } else {
                    $listGdTrdSeriesGroup[$label] = [ intval($row['value']) ];
                }
            }

            # Armando array para la lista del front
            $listGdTrdSeries = [];
            foreach ($listGdTrdSeriesGroup as $key => $value) {
                $listGdTrdSeries[] = array('label' => $key , 'value' => $value );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listGdTrdSeries; 

            ###################### list SUB-SERIE
            $gdTrdSubseries = GdExpedientesInclusion::find();
            $gdTrdSubseries = HelperQueryDb::getQuery('innerJoin', $gdTrdSubseries, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);
            $gdTrdSubseries = HelperQueryDb::getQuery('innerJoin', $gdTrdSubseries, 'gdTrdSubseries', ['gdTrdSubseries' => 'idGdTrdSubserie', 'gdExpedientes' => 'idGdTrdSubserie']);
            $gdTrdSubseries = HelperQueryDb::getQuery('innerJoin', $gdTrdSubseries, 'gdTrd', ['gdTrd' => 'idGdTrdSubserie', 'gdTrdSubseries' => 'idGdTrdSubserie']);

            $gdTrdSubseries = $gdTrdSubseries->select(['gdTrdSubseries.nombreGdTrdSubserie  as label', 'gdTrdSubseries.codigoGdTrdSubserie as code', 'gdTrdSubseries.idGdTrdSubserie as value', 'gdTrd.versionGdTrd as version'])
            // ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['gdTrdSubseries.nombreGdTrdSubserie', 'gdTrdSubseries.codigoGdTrdSubserie', 'gdTrdSubseries.idGdTrdSubserie', 'gdTrd.versionGdTrd'])
            ->orderBy(['gdTrdSubseries.nombreGdTrdSubserie' => SORT_ASC])
            ->asArray()->all();

            # Arrgupar IDs de subseries por (versión TRD, código y nombre de subserie)
            $listGdTrdSubSeriesGroup = [];
            foreach ($gdTrdSubseries as $row) {
                $label = 'V'.$row['version'].' - '.$row['code'].' - '.$row['label'];
                if ( isset($listGdTrdSubSeriesGroup[$label]) ) {
                    array_push($listGdTrdSubSeriesGroup[$label], intval($row['value']));
                } else {
                    $listGdTrdSubSeriesGroup[$label] = [ intval($row['value']) ];
                }
            }

            # Armando array para la lista del front
            $listGdTrdSubseries = [];
            foreach ($listGdTrdSubSeriesGroup as $key => $value) {
                $listGdTrdSubseries[] = array('label' => $key , 'value' => $value );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listGdTrdSubseries; 

            ###################### list GAEDIFICIO
            $GaEdificio = GaEdificio::find()
            ->select(["idGaEdificio as value", "nombreGaEdificio as label"])
            ->where(['estadoGaEdificio' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreGaEdificio' => SORT_ASC])
            ->asArray()->all();

            $listGaEdificio = [];
            foreach ($GaEdificio as $key => $value) {
                $listGaEdificio[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listGaEdificio;      
            
            # Asignacion de valores para la lista de los GaPiso
            $GaPiso = GaPiso::find()
            ->select(["idGaPiso as value", "numeroGaPiso as label"])
            ->where(['estadoGaPiso' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['numeroGaPiso' => SORT_ASC])
            ->asArray()->all();

            $listGaPiso = [];
            foreach ($GaPiso as $key => $value) {
                $listGaPiso[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listGaPiso;  
            
            # Asignacion de valores para la lista de los GaBodega
            $GaBodega = GaBodega::find()
            ->select(["idGaBodega as value", "nombreGaBodega as label"])
            ->where(['estadoGaBodega' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreGaBodega' => SORT_ASC])
            ->asArray()->all();

            $listGaBodega = [];
            foreach ($GaBodega as $key => $value) {
                $listGaBodega[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][8]['templateOptions']['options'] = $listGaBodega;  


            # Asignacion de valores para la lista del cuerpo de archivo
            $cuerpo = Yii::$app->params['cuerpoArchivoNumber'];

                $listCuerpo = [];
                foreach ($cuerpo as $key => $value) {

                    $listCuerpo[] = array('label' => $value , 'value' => $value );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][12]['templateOptions']['options'] = $listCuerpo; 


            # Asignacion de valores para la lista de la unidadConservacionGaArchivo
            $unidadConservacion = Yii::$app->params['unidadConservacionGaArchivo'];

                $listUnidadConservacion = [];
                foreach ($unidadConservacion as $key => $value) {
                    $listUnidadConservacion[] = array('label' => $key , 'value' => intval($value) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][13]['templateOptions']['options'] = $listUnidadConservacion; 
        }

        #Listado del index anulación
        if ($nombreFormType == 'indexAnulacion') {

            # Asignación de valores para la lista de tipos de radicados
            $tipoRadicado = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

            $listTipoRadicado = [];
            foreach ($tipoRadicado as $key => $value) {
                $listTipoRadicado[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listTipoRadicado;


            # Asignación de valores para la lista de estados de anulación
            $modelEstado = RadiEstadosAnulacion::find()
                ->select(["idRadiEstadoAnulacion as value", "nombreRadiEstadoAnulacion as label"])
                ->orderBy(['nombreRadiEstadoAnulacion' => SORT_ASC])
                ->asArray()->all();

            $listEstados = [];
            foreach ($modelEstado as $key => $value) {
                $listEstados[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listEstados;


            # Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listDepe;

        }

        #Listado del index Reasignacion
        if ($nombreFormType == 'indexReasignacionRadicado') {

            // Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;

            // Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

            $listTipoRadi = [];
            foreach ($tipoRadi as $key => $value) {
                $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoRadi;

            // Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

            $listTipoDocu = [];
            foreach ($tipoDocu as $key => $value) {
                $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listTipoDocu;

        }

        if( $nombreFormType == 'indexLogAuditoria'){

            // Consulta de usuarios
            $userDetalles = UserDetalles::find()
            ->select(["idUser as value", "nombreUserDetalles", "apellidoUserDetalles"])
            ->orderBy(['apellidoUserDetalles' => SORT_ASC])
            ->asArray()->all();

            $listUser = [];
            foreach ($userDetalles as $key => $value) {
                $listUser[] = array('label' => $value['nombreUserDetalles'].' '.$value['apellidoUserDetalles'], 'value' => intval($value['value']) );

            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listUser;

        }

        if( $nombreFormType == 'indexCgProveedores'){

            // Consulta de servicios
            $servicios = CgEnvioServicios::find()
                ->select(["idCgEnvioServicio as value", "nombreCgEnvioServicio as label"])
                ->orderBy(['nombreCgEnvioServicio' => SORT_ASC])
                ->asArray()->all();

            $listSer = [];
            foreach ($servicios as $key => $value) {
                $listSer[] = array('label' => $value['label'], 'value' => intval($value['value']) );

            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listSer;

            // Consulta de regional
            $regional = CgRegionales::find()
                ->select(["idCgRegional as value", "nombreCgRegional as label"])
                ->orderBy(['nombreCgRegional' => SORT_ASC])
                ->asArray()->all();

            $listReg = [];
            foreach ($regional as $key => $value) {
                $listReg[] = array('label' => $value['label'], 'value' => intval($value['value']) );

            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listReg;

        }

        if ($nombreFormType == 'indexGdTrdTmp') {
            # Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependenciasTmp::find()
                ->select(["codigoGdTrdDependenciaTmp as value", "nombreGdTrdDependenciaTmp as label", "codigoGdTrdDependenciaTmp as codigo"])
                ->distinct()
                ->where(['estadoGdTrdDependenciaTmp' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependenciaTmp' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => $value['value'] );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;

            // Asignación de valores para la listas para las series
            $series = GdTrdSeriesTmp::find()
                ->select(["codigoGdTrdSerieTmp as value", "nombreGdTrdSerieTmp as label", "codigoGdTrdSerieTmp as codigo"])
                ->distinct()
                ->where(['estadoGdTrdSerieTmp' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdSerieTmp' => SORT_ASC])
                ->asArray()->all();

            $listSeries = [];
            foreach ($series as $key => $value) {
                $listSeries[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => $value['value']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listSeries;

            // Asignación de valores para la listas para las subseries
            $subseries = GdTrdSubseriesTmp::find()
                ->select(["codigoGdTrdSubserieTmp as value", "nombreGdTrdSubserieTmp as label", "codigoGdTrdSubserieTmp as codigo"])
                ->distinct()
                ->where(['estadoGdTrdSubserieTmp' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreGdTrdSubserieTmp' => SORT_ASC])
                ->asArray()->all();

            $listSubseries = [];
            foreach ($subseries as $key => $value) {
                $listSubseries[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => $value['value']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $subseries;

            // Asignación de valores para la listas para las tipos documentales
            $tipoDocumental = GdTrdTiposDocumentalesTmp::find()
                ->select(["nombreTipoDocumentalTmp as value", "nombreTipoDocumentalTmp as label"])
                ->distinct()
                ->where(['estadoTipoDocumentalTmp' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumentalTmp' => SORT_ASC])
                ->asArray()->all();
        
            $listTipoDocumental = [];
            foreach ($tipoDocumental as $key => $value) {
                $listTipoDocumental[] = array('label' => $value['label'], 'value' => $value['value']);
            }
            
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $tipoDocumental;

        }

        #Listado del index espacio fisico
        if ($nombreFormType == 'indexEspacioFisico') {

            # Asignación de valores para la lista de los edificios
            $modelBuilding = GaEdificio::find()
                ->select(["idGaEdificio as value", "nombreGaEdificio as label"])
                ->where(['estadoGaEdificio' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreGaEdificio' => SORT_ASC])
                ->asArray()->all();

                $listBuilding = [];
                foreach ($modelBuilding as $key => $value) {
                    $listBuilding[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
                
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listBuilding;  
        }

        #Listado del index préstamo documental
        if ($nombreFormType == 'indexPrestamoDocumental') {

            # Asignación de valores para la lista de tipos documentales
            $tipoDoc = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

            $listTipoDocGroup = [];
            foreach ($tipoDoc as $key => $value) {
                $listTipoDocGroup[$value['label']][] = $value['value'];
            }

            $listTipoDoc = [];
            foreach ($listTipoDocGroup as $key => $value) {
                $listTipoDoc[] = array('label' => $key , 'value' => $value);
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoDoc;

            // consulta de las dependencias
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDependencia = [];
            foreach ($dependencia as $key => $value) {
                $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listDependencia;
        }

        #Listado del index administrar préstamo
        if ($nombreFormType == 'indexAdministrarPrestamo' || $nombreFormType == 'indexAdministrarPrestamoCentral') {

            # Asignación de valores para la lista de tipos documentales
            $tipoDoc = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

            $listTipoDocGroup = [];
            foreach ($tipoDoc as $key => $value) {
                $listTipoDocGroup[$value['label']][] = $value['value'];
            }

            $listTipoDoc = [];
            foreach ($listTipoDocGroup as $key => $value) {
                $listTipoDoc[] = array('label' => $key , 'value' => $value);
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoDoc;


            # Asignación de valores para la lista de las dependencias
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($dependencia as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listDepe;


            # Asignación de valores para la lista de tipos de prestamo
            $listTipoPrestamo = [];
            foreach ($listDepe as $i => $tipo) {                
                $listTipoPrestamo[] = array('label' => $tipo , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listTipoPrestamo;            
        }


        #Listado del index regional
        if ($nombreFormType == 'indexRegional') {

            # Asignación de valores para la lista de paises
            $country = NivelGeografico1::find()
                ->select(["nivelGeografico1 as value", "nomNivelGeografico1 as label"])
                ->where(['estadoNivelGeografico1' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nomNivelGeografico1' => SORT_ASC])
            ->asArray()->all();

                $listCountry = [];
                foreach ($country as $key => $value) {
                    $listCountry[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listCountry;

        }


        #Listado del index de reportes de productividad
        if ($nombreFormType == 'indexReporteProductivo') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDependencia;
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoRadi;                 


            # Asignación de valores para la listas para los medios de recepción
            $medios = CgMediosRecepcion::find()
                ->select(["idCgMedioRecepcion as value", "nombreCgMedioRecepcion as label"])
                ->orderBy(['nombreCgMedioRecepcion' => SORT_ASC])
                ->asArray()->all();

                $listMedios = [];
                foreach ($medios as $key => $value) {
                    $listMedios[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listMedios;


            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

                $listTipoDocu = [];
                foreach ($tipoDocu as $key => $value) {
                    $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoDocu;

            
            # Asignación de valores para la listas de las transacciones
            $transaccion = CgTransaccionesRadicados::find()
                ->select(["idCgTransaccionRadicado as value", "titleCgTransaccionRadicado as label"])
                ->where(['estadoCgTransaccionRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['titleCgTransaccionRadicado' => SORT_ASC])
                ->asArray()->all();

                $listTransaccion = [];
                foreach ($transaccion as $key => $value) {
                    $listTransaccion[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listTransaccion;
        }


        #Listado del index de reportes de radicados de PQRS
        if ($nombreFormType == 'indexReportePqrs') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDependencia;          


            # Asignación de valores para la listas para los medios de recepción
            $medios = CgMediosRecepcion::find()
                ->select(["idCgMedioRecepcion as value", "nombreCgMedioRecepcion as label"])
                ->orderBy(['nombreCgMedioRecepcion' => SORT_ASC])
                ->asArray()->all();

                $listMedios = [];
                foreach ($medios as $key => $value) {
                    $listMedios[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listMedios;


            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

                $listTipoDocu = [];
                foreach ($tipoDocu as $key => $value) {
                    $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listTipoDocu;

            
            # Asignación de valores para la listas de las transacciones
            $transaccion = CgTransaccionesRadicados::find()
                ->select(["idCgTransaccionRadicado as value", "titleCgTransaccionRadicado as label"])
                ->where(['estadoCgTransaccionRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['titleCgTransaccionRadicado' => SORT_ASC])
                ->asArray()->all();

                $listTransaccion = [];
                foreach ($transaccion as $key => $value) {
                    $listTransaccion[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTransaccion;
        }


        #Listado de index de reportes de distribución de correspondencia
        if($nombreFormType == 'indexReporteCorrespondencia'){

            # Asignación de valores para la listas los nombre de proveedores
            $modelProveedor = CgProveedores::find()
                ->select(["idCgProveedor as value", "nombreCgProveedor as label"])
                ->orderBy(['nombreCgProveedor' => SORT_ASC])
            ->asArray()->all();

            $listProveedores = [];
            foreach ($modelProveedor as $key => $value) {
                $listProveedores[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listProveedores;


            # Asignación de valores para la listas de motivos de devolución
            $modelMotivosDevolucion = CgMotivosDevolucion::find()
                ->select(["idCgMotivoDevolucion as value", "nombreCgMotivoDevolucion as label"])
                ->orderBy(['nombreCgMotivoDevolucion' => SORT_ASC])
            ->asArray()->all();
 
                $listMotivosDevolucion = [];
                foreach ($modelMotivosDevolucion as $key => $value) {
                    $listMotivosDevolucion [] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listMotivosDevolucion;


            # Asignación de valores para la listas de tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listTipoRadi;
        }


        #Listado del index de reportes de prestamo documental
        if ($nombreFormType == 'indexReportePrestamos') {

            # Asignación de valores para la lista de las dependencias
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($dependencia as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;


            # Asignación de valores para la lista de tipos de prestamo
            $listTipoPrestamo = [];
            foreach ($listDepe as $i => $tipo) {                
                $listTipoPrestamo[] = array('label' => $tipo , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoPrestamo;            
        }


        #Listado de index de reportes de expediente electrónico
        if ($nombreFormType == 'indexReporteExpediente') {

            # Listado de dependencias del creador
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la lista de series
            $series = GdTrdSeries::find()
                ->select(["idGdTrdSerie as value", "nombreGdTrdSerie as label", "codigoGdTrdSerie as codigo"])
                ->distinct()
                ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdSerie' => SORT_ASC])
            ->asArray()->all();

                $listSeries = [];
                foreach ($series as $key => $value) {
                    $listSeries[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => $value['value']);
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listSeries;
        }


        #Listado del index de reporte de caracterización
        if ($nombreFormType == 'indexReporteCaracterizacion') {

            # Asignación de valores para la lista de género
            $listGeneroCliente = [];
            foreach (Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'] as $i => $genero) {                
                $listGeneroCliente[] = array('label' => $genero , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listGeneroCliente;

            # Asignación de valores para la lista de rango de edad
            $listRangoEdad = [];
            foreach (Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'] as $i => $edades) {                
                $listRangoEdad[] = array('label' => $edades , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listRangoEdad; 

            # Asignación de valores para la lista de vulnerabilidad
            $listVulnerabilidad = [];
            foreach (Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'] as $i => $vulnerabilidad) {                
                $listVulnerabilidad[] = array('label' => $vulnerabilidad , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listVulnerabilidad; 

            # Asignación de valores para la lista de etnias
            $listEtnia = [];
            foreach (Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'] as $i => $etnia) {                
                $listEtnia[] = array('label' => $etnia , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listEtnia;    

            # Asignación de valores para la lista de paises
            $country = NivelGeografico1::find()
                ->select(["nivelGeografico1 as value", "nomNivelGeografico1 as label"])
                ->where(['estadoNivelGeografico1' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nomNivelGeografico1' => SORT_ASC])
            ->asArray()->all();

                $listCountry = [];
                foreach ($country as $key => $value) {
                    $listCountry[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listCountry;
        }


        #Listado del index de reporte general
        if ($nombreFormType == 'indexReporteGeneral') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoRadi;


            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

                $listTipoDocu = [];
                foreach ($tipoDocu as $key => $value) {
                    $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listTipoDocu;


            # Asignación de valores para la listas para los medios de recepción
            $medios = CgMediosRecepcion::find()
                ->select(["idCgMedioRecepcion as value", "nombreCgMedioRecepcion as label"])
                ->orderBy(['nombreCgMedioRecepcion' => SORT_ASC])
                ->asArray()->all();

                $listMedios = [];
                foreach ($medios as $key => $value) {
                    $listMedios[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listMedios;


            # Asignación de valores para la listas para los medios de recepción
            $regional = CgRegionales::find()
                ->select(["idCgRegional as value", "nombreCgRegional as label"])
                ->orderBy(['nombreCgRegional' => SORT_ASC])
            ->asArray()->all();

                $listRegional = [];
                foreach ($regional as $key => $value) {
                    $listRegional[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][8]['templateOptions']['options'] = $listRegional;            
        }   
        

        #Listado del index de reporte de trazabilidad del radicado
        if ($nombreFormType == 'indexReporteTrazabilidad') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoRadi;
        }           
        

        #Listado del index de reporte de trazabilidad del radicado
        if ($nombreFormType == 'indexReporteUsuarios') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDependencia;
        }


        # Listados del index de reportes de radicados por tiempo de respuesta y dependencia
        if ($nombreFormType == 'indexReporteRadiResponseTime') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

            $listDependencia = [];
            foreach ($dependencia as $key => $value) {
                $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDependencia;

            # Asignación de valores para la listas de tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                ->asArray()->all();

            $listTipoRadi = [];
            foreach ($tipoRadi as $key => $value) {
                $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoRadi;

            # Asignación de valores para la listas de regional
            $regional = CgRegionales::find()
                ->select(["idCgRegional as value", "nombreCgRegional as label"])
                ->orderBy(['nombreCgRegional' => SORT_ASC])
                ->asArray()->all();

            $listReg = [];
            foreach ($regional as $key => $value) {
                $listReg[] = array('label' => $value['label'], 'value' => intval($value['value']) );

            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listReg;

            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

            $listTipoDocu = [];
            foreach ($tipoDocu as $key => $value) {
                $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoDocu;
        }


        #Listado del index de reporte de tiempo de contestación
        if ($nombreFormType == 'indexReporteRespuesta') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoRadi;

        } 
        

        #Listado del index de reporte de documentos pendientes
        if ($nombreFormType == 'indexReporteDocumentos') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoRadi;
            
        }  
        

        #Listado del index de reporte de devolución
        if ($nombreFormType == 'indexReporteDevolucion') {

            # Listado de dependencias activas
            $dependencia = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDependencia = [];
                foreach ($dependencia as $key => $value) {
                    $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDependencia;


            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoRadi;
        }


        #Listado del index de reporte de usuarios activos
        if ($nombreFormType == 'indexReporteUsuariosActivos') { 
            
            # Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDepe = [];
                foreach ($depe as $key => $value) {
                    $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;


            # Asignación de valores para la listas para Rol
            $roles = Roles::find()
                ->select(["idRol as value", "nombreRol as label"])
                ->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreRol' => SORT_ASC])
            ->asArray()->all();

                $listRol = [];
                foreach ($roles as $key => $value) {
                    $listRol[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listRol;           
        }


        #Listado del index de reporte de usuarios activos
        if ($nombreFormType == 'indexReporteConPermiso') { 
            
            # Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

                $listDepe = [];
                foreach ($depe as $key => $value) {
                    $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;         
        }


        #Listado del index de reporte de roles con permiso
        if ($nombreFormType == 'indexReporteRolPermiso') { 
            
            # Asignación de valores para la listas para Rol
            $roles = Roles::find()
                ->select(["idRol as value", "nombreRol as label"])
                ->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreRol' => SORT_ASC])
            ->asArray()->all();

                $listRol = [];
                foreach ($roles as $key => $value) {
                    $listRol[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listRol; 
            

            # Asignación de valores para la listas de los modulos
            $modulos = RolesModulosOperaciones::find()
                ->select(["idRolModuloOperacion as value", "nombreRolModuloOperacion as label"])
                ->where(['estadoRolModuloOperacion' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreRolModuloOperacion' => SORT_ASC])
            ->asArray()->all();

            $listModulo = [];
            foreach ($modulos as $key => $value) {
                $listModulo[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listModulo; 
        }


        #Listado de index Expediente
        if ($nombreFormType == 'indexExpediente') { 

            #Nivel de busqueda del logueado
            $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

            # asignación para las listas usuario creador
            $modelUserDetalle = UserDetalles::find()
                ->select(['idUser as value', 'nombreUserDetalles as label1', 'apellidoUserDetalles as label2'])
                ->where(['estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']]);        
            
            $modelTrd = GdTrd::find()->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']]);

            # Se visualiza todos los expedientes del logueado
            if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
                $modelTrd->andWhere(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);
                
                $idUser = Yii::$app->user->identity->id;
                $modelUserDetalle->andWhere(['idUser' => $idUser]); 
            }  
            
            # Cuando es nivel intermedio se busca todos los expedientes asociados a la dependencia del usuario logueado
            if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {               
                $modelTrd->andWhere(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);

                $modelUser = User::find()
                    ->select(['id'])
                    ->where(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
                ->all();

                $idUsers = [];
                foreach($modelUser as $value){
                    $idUsers[] = $value->id;
                }               

                $modelUserDetalle->andWhere(['IN', 'idUser', $idUsers]); 
            }

            $modelUserDetalle =  $modelUserDetalle->asArray()->all();
            $modelTrd = $modelTrd->all();

            $listUsers = [];
            foreach($modelUserDetalle as $key => $value){
                $listUsers[] = array('label' => $value['label1'] . ' ' . $value['label2'], 'value' => $value['value']);
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listUsers;  


            # Listado de dependencias
            if ($idNivelBusqueda != Yii::$app->params['searchLevelText']['Avanzado']) {

                # Listado de dependencias según la dependencia del usuario logueado 
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }

            } else {

                # Listado de dependencias activas
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listDependencia;
                     

            $idsSerie = [];
            $listSeries = [];
            foreach($modelTrd as $trd){

                //Solo incluir una serie ya que se repiten en las trd
                if(!in_array($trd->idGdTrdSerie,  $idsSerie)){
                    $listSeries[] = [
                        'value' =>  $trd->idGdTrdSerie,
                        'label' => $trd->gdTrdSerie->codigoGdTrdSerie.' - '.$trd->gdTrdSerie->nombreGdTrdSerie.' - TRD Vr'.$trd->versionGdTrd
                    ];

                    $idsSerie[] =  $trd->idGdTrdSerie;
                }           
            }    

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listSeries; 
        }


        # Listado de Index Expedientes Transferencia Gestion/Central
        if($nombreFormType == 'indexExpedienteTransferencia'){

            ###################### list DEPENDENCIAS
            $depe = GdTrdDependencias::find();
            // ->innerJoin('gdExpedientes', 'gdExpedientes.idGdTrdDependencia = gdTrdDependencias.idGdTrdDependencia')
            $depe = HelperQueryDb::getQuery('innerJoin', $depe, 'gdExpedientes', ['gdExpedientes' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
            $depe = $depe->select(["gdTrdDependencias.idGdTrdDependencia as value", "gdTrdDependencias.nombreGdTrdDependencia  as label", "gdTrdDependencias.codigoGdTrdDependencia  as codigo"])
            ->where(['gdTrdDependencias.estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['gdTrdDependencias.codigoGdTrdDependencia', 'gdTrdDependencias.idGdTrdDependencia', 'gdTrdDependencias.nombreGdTrdDependencia'])
            ->orderBy(['gdTrdDependencias.codigoGdTrdDependencia' => SORT_ASC])
            ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listDepe;
            

            ###################### list USER
            $modelUserDetalle = UserDetalles::find();
            // ->innerJoin('gdExpedientes', 'gdExpedientes.idUser = userDetalles.idUser')
            $modelUserDetalle = HelperQueryDb::getQuery('innerJoin', $modelUserDetalle, 'gdExpedientes', ['gdExpedientes' => 'idUser', 'userDetalles' => 'idUser']);
            $modelUserDetalle = $modelUserDetalle->select(['userDetalles.idUser  as value', 'userDetalles.nombreUserDetalles as label1', 'userDetalles.apellidoUserDetalles as label2'])
            ->groupBy(['userDetalles.idUser', 'userDetalles.nombreUserDetalles', 'userDetalles.apellidoUserDetalles'])
            ->where(['userDetalles.estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']])
            ->asArray()->all();
            
            $listUsers = [];
            foreach($modelUserDetalle as $key => $value){
                $listUsers[] = array('label' => $value['label1'] . ' ' . $value['label2'], 'value' => $value['value']);
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listUsers;  
        
            ###################### list SERIE
            $gdTrdSeries = GdExpedientes::find();
            // ->innerJoin('gdTrdSeries', 'gdTrdSeries.idGdTrdSerie = gdExpedientes.idGdTrdSerie')
            $gdTrdSeries = HelperQueryDb::getQuery('innerJoin', $gdTrdSeries, 'gdTrdSeries', ['gdTrdSeries' => 'idGdTrdSerie', 'gdExpedientes' => 'idGdTrdSerie']);
            $gdTrdSeries = $gdTrdSeries->select(['gdTrdSeries.nombreGdTrdSerie  as label', 'gdTrdSeries.codigoGdTrdSerie as code', 'gdTrdSeries.idGdTrdSerie as value'])
                ->where(['gdTrdSeries.estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
                ->groupBy(['gdTrdSeries.nombreGdTrdSerie', 'gdTrdSeries.codigoGdTrdSerie', 'gdTrdSeries.idGdTrdSerie'])
                ->orderBy(['gdTrdSeries.nombreGdTrdSerie' => SORT_ASC])
            ->asArray()->all();

            $listGdTrdSeries = [];
            foreach ($gdTrdSeries as $key => $value) {
                $listGdTrdSeries[] = array('label' => $value['code'].'-'.$value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listGdTrdSeries; 

            ###################### list SUB-SERIE
            $gdTrdSubseries = GdExpedientes::find();
            // ->innerJoin('gdTrdSubseries', 'gdTrdSubseries.idGdTrdSubserie = gdExpedientes.idGdTrdSubserie')
            $gdTrdSubseries = HelperQueryDb::getQuery('innerJoin', $gdTrdSubseries, 'gdTrdSubseries', ['gdTrdSubseries' => 'idGdTrdSubserie', 'gdExpedientes' => 'idGdTrdSubserie']);
            $gdTrdSubseries = $gdTrdSubseries->select(['gdTrdSubseries.nombreGdTrdSubserie  as label' , 'gdTrdSubseries.codigoGdTrdSubserie as code', 'gdTrdSubseries.idGdTrdSubserie as value'])
            ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['gdTrdSubseries.nombreGdTrdSubserie', 'gdTrdSubseries.codigoGdTrdSubserie', 'gdTrdSubseries.idGdTrdSubserie'])
            ->orderBy(['gdTrdSubseries.nombreGdTrdSubserie' => SORT_ASC])
            ->asArray()->all();

            $listGdTrdSubseries = [];
            foreach ($gdTrdSubseries as $key => $value) {
                $listGdTrdSubseries[] = array('label' => $value['code'].'-'.$value['label'] , 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listGdTrdSubseries; 
             
        }

        #Listado de view Expediente
        if ($nombreFormType == 'viewExpediente') {  

            # Asignación de valores para la listas para los tipos documentales
            $tipoDocu = GdTrdTiposDocumentales::find()
                ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                ->asArray()->all();

                $listTipoDocu = [];
                foreach ($tipoDocu as $key => $value) {
                    $listTipoDocu[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][2]['templateOptions']['options'] = $listTipoDocu;
        } 

        # Listado de CgTerceros
        if($nombreFormType == 'indexCgTerceros'){

            # Asignación de valores para la lista de tipos de personas
            $listTiposPersonas = [];
            $modelTiposP = TiposPersonas::find(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']]);
            $modelTiposP->andWhere(['NOT IN', 'idTipoPersona', Yii::$app->params['tipoPersonaText']['funcionario'] ]);
            $modelTiposP = $modelTiposP->all();

            foreach($modelTiposP as $dataTipoPersona){
                $listTiposPersonas[] =  [
                    'label' => $dataTipoPersona->tipoPersona,
                    'value' => $dataTipoPersona->idTipoPersona
                ];
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listTiposPersonas; 

            /*$listMunicipal = [];
            $modelMunicipal = NivelGeografico3::findAll(['estadoNivelGeografico3' =>  Yii::$app->params['statusTodoText']['Activo']]);
            foreach($modelMunicipal as $dataMuni){
                $listMunicipal[] =  [
                    'label' => $dataMuni->nomNivelGeografico3,
                    'value' => $dataMuni->nivelGeografico3
                ];
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listMunicipal; */

            # Asignación de valores para la lista de paises
            $country = NivelGeografico1::find()
                ->select(["nivelGeografico1 as value", "nomNivelGeografico1 as label"])
                ->where(['estadoNivelGeografico1' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nomNivelGeografico1' => SORT_ASC])
            ->asArray()->all();

                $listCountry = [];
                foreach ($country as $key => $value) {
                    $listCountry[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listCountry;
        }


        if($nombreFormType == 'indexConsultaDocumentos'){

            # Asignación de valores para la listas para los tipos radicados
            $tipoRadi = CgTiposRadicados::find()
                ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
            ->asArray()->all();

                $listTipoRadi = [];
                foreach ($tipoRadi as $key => $value) {
                    $listTipoRadi[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listTipoRadi;     
            
            
            #Nivel de busqueda del logueado
            $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

            if ($idNivelBusqueda != Yii::$app->params['searchLevelText']['Avanzado']) {

                # Listado de dependencias según la dependencia del usuario logueado 
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }

            } else {

                # Listado de dependencias activas
                $dependencia = GdTrdDependencias::find()
                    ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                    ->asArray()->all();

                    $listDependencia = [];
                    foreach ($dependencia as $key => $value) {
                        $listDependencia[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                    }
            }

            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listDependencia;
            $formType['schema'][0]['fieldArray']['fieldGroup'][9]['templateOptions']['options'] = $listDependencia;
           
        }
        
        if ($nombreFormType == 'indexCgGuposUsuariosView') {
            // Asignación de valores para la listas para las dependencias
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listDepe;
        }

        if ($nombreFormType == 'indexPrestamoExpedientes') {
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listDepe;
        }

        if ($nombreFormType == 'indexAdminPrestamoExpedientes' || $nombreFormType == 'indexAdminPrestamoExpedientesCentral') {
            $depe = GdTrdDependencias::find()
                ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                ->asArray()->all();

            $listDepe = [];
            foreach ($depe as $key => $value) {
                $listDepe[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][5]['templateOptions']['options'] = $listDepe;

            # Asignación de valores para la lista de tipos de prestamo
            $listTipoPrestamo = [];
            foreach ($listDepe as $i => $tipo) {
                $listTipoPrestamo[] = array('label' => $tipo , 'value' => intval($i) );
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listTipoPrestamo;
        }

        if ($nombreFormType == 'indexConsultaOrfeoAntiguo') {
            $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
            $consultaOrfeo38Dependencias = $conexionOrfeo38->createCommand("SELECT depe_codi, depe_nomb FROM dependencia");
            $resultConsultaOrfeo38Dependencias = $consultaOrfeo38Dependencias->queryAll();
            $listDepe = [];
            foreach ($resultConsultaOrfeo38Dependencias as $row) {
                $listDepe[] = array('label' => $row['depe_codi'] .' '. $row['depe_nomb'], 'value' => $row['depe_codi']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listDepe;
            $formType['schema'][0]['fieldArray']['fieldGroup'][9]['templateOptions']['options'] = $listDepe;

            $consultaOrfeo38Usuarios = $conexionOrfeo38->createCommand("SELECT usua_codi, usua_nomb FROM usuario");
            $resultConsultaOrfeo38Usuarios = $consultaOrfeo38Usuarios->queryAll();
            $listUsuarios = [];
            foreach ($resultConsultaOrfeo38Usuarios as $row) {
                $listUsuarios[] = array('label' => $row['usua_nomb'], 'value' => $row['usua_codi']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][6]['templateOptions']['options'] = $listUsuarios;
            $formType['schema'][0]['fieldArray']['fieldGroup'][8]['templateOptions']['options'] = $listUsuarios;

            $consultaOrfeo38TiposDocumentales = $conexionOrfeo38->createCommand("SELECT sgd_tpr_codigo, sgd_tpr_descrip FROM sgd_tpr_tpdcumento ORDER BY sgd_tpr_descrip");
            $resultConsultaOrfeo38TiposDocumentales = $consultaOrfeo38TiposDocumentales->queryAll();
            $listTiposDocumentales = [];
            foreach ($resultConsultaOrfeo38TiposDocumentales as $row) {
                $listTiposDocumentales[] = array('label' => $row['sgd_tpr_descrip'], 'value' => $row['sgd_tpr_codigo']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][10]['templateOptions']['options'] = $listTiposDocumentales;
        }

        if ($nombreFormType == 'indexDocumentosConsultaOrfeoAntiguo') {
            $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
            $consultaOrfeo38Usuarios = $conexionOrfeo38->createCommand("SELECT usua_codi, usua_nomb FROM usuario");
            $resultConsultaOrfeo38Usuarios = $consultaOrfeo38Usuarios->queryAll();
            $listUsuarios = [];
            foreach ($resultConsultaOrfeo38Usuarios as $row) {
                $listUsuarios[] = array('label' => $row['usua_nomb'], 'value' => $row['usua_codi']);
            }
            // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listUsuarios;
        }

        // if ($nombreFormType == 'indexConsultaFondoAcumulado') {
        //     $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
        //     $consultaOrfeo38Entrega = $conexionOrfeo38->createCommand("SELECT entrega FROM almarchivos GROUP BY entrega");
        //     $resultConsultaOrfeo38Entrega = $consultaOrfeo38Entrega->queryAll();
        //     $listEntrega = [];
        //     foreach ($resultConsultaOrfeo38Entrega as $row) {
        //         $listEntrega[] = array('label' => $row['entrega'], 'value' => $row['entrega']);
        //     }
        //     // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
        //     $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $listEntrega;

        //     $consultaOrfeo38serie = $conexionOrfeo38->createCommand("SELECT serie FROM almarchivos GROUP BY serie");
        //     $resultConsultaOrfeo38serie = $consultaOrfeo38serie->queryAll();
        //     $listserie = [];
        //     foreach ($resultConsultaOrfeo38serie as $row) {
        //         $listserie[] = array('label' => $row['serie'], 'value' => $row['serie']);
        //     }
        //     // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
        //     $formType['schema'][0]['fieldArray']['fieldGroup'][11]['templateOptions']['options'] = $listserie;

        //     $consultaOrfeo38subserie = $conexionOrfeo38->createCommand("SELECT subserie FROM almarchivos GROUP BY subserie");
        //     $resultConsultaOrfeo38subserie = $consultaOrfeo38subserie->queryAll();
        //     $listSubserie = [];
        //     foreach ($resultConsultaOrfeo38subserie as $row) {
        //         $listSubserie[] = array('label' => $row['subserie'], 'value' => $row['subserie']);
        //     }
        //     // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
        //     $formType['schema'][0]['fieldArray']['fieldGroup'][12]['templateOptions']['options'] = $listSubserie;

        //     $consultaOrfeo38TipoDocumental = $conexionOrfeo38->createCommand("SELECT tipo_documental FROM almarchivos GROUP BY tipo_documental");
        //     $resultConsultaOrfeo38TipoDocumental = $consultaOrfeo38TipoDocumental->queryAll();
        //     $listTipoDocumental = [];
        //     foreach ($resultConsultaOrfeo38TipoDocumental as $row) {
        //         $listTipoDocumental[] = array('label' => $row['tipo_documental'], 'value' => $row['tipo_documental']);
        //     }
        //     // Se le asigna la variable en la posicion que se encuentra el campo de lista en app
        //     $formType['schema'][0]['fieldArray']['fieldGroup'][13]['templateOptions']['options'] = $listTipoDocumental;
        // }

        return $formType;
    }

    /*** Funcion que agrega el campo para controlar el límite de los registos de la consulta ***/
    public static function setInputLimit($filtersData, $limitRecords)
    {
        if ($filtersData == null) {
            return $filtersData;
        }

        $fieldGroup = $filtersData['schema'][0]['fieldArray']['fieldGroup'];

        $inputFilterLimit = [
            'key' => 'inputFilterLimit',
            'keyInput' => 'inputFilterLimit',
            'type' => 'select',
            'className' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12',
            'defaultValue' => $limitRecords,
            'templateOptions' => [
                'label' => Yii::t('app', 'labelInputFilterLimit'),
                'placeholder' => Yii::t('app', 'labelInputFilterLimit'),
                'attributes' => [
                    'title' => Yii::t('app', 'labelInputFilterLimit'),
                ],
                'options' => [
                    0 => ['label' => '10', 'value' => 10],
                    1 => ['label' => '100', 'value' => 100],
                    2 => ['label' => '1000', 'value' => 1000],
                    4 => ['label' => Yii::t('app', 'AllRowsInputFilterLimit'), 'value' => 5000],
                ],
                'required' => true,
                'changeExpr' => '',
            ],
            "validation" => [
                "messages" => [
                    "required" => "The field is mandatory",
                ],
            ],
        ];

        array_unshift($fieldGroup, $inputFilterLimit);

        $filtersData['schema'][0]['fieldArray']['fieldGroup'] = $fieldGroup;
        return $filtersData;
    }

    /*** Funcion que agrega el campo para controlar el límite de los registos de la consulta hasta 1000 ***/
    public static function setInputLimit1000($filtersData, $limitRecords)
    {
        if ($filtersData == null) {
            return $filtersData;
        }

        $fieldGroup = $filtersData['schema'][0]['fieldArray']['fieldGroup'];

        $inputFilterLimit = [
            'key' => 'inputFilterLimit',
            'keyInput' => 'inputFilterLimit',
            'type' => 'select',
            'className' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12',
            'defaultValue' => $limitRecords,
            'templateOptions' => [
                'label' => Yii::t('app', 'labelInputFilterLimit'),
                'placeholder' => Yii::t('app', 'labelInputFilterLimit'),
                'attributes' => [
                    'title' => Yii::t('app', 'labelInputFilterLimit'),
                ],
                'options' => [
                    0 => ['label' => '10', 'value' => 10],
                    1 => ['label' => '100', 'value' => 100],
                    2 => ['label' => '1000', 'value' => 1000],
                    4 => ['label' => Yii::t('app', 'AllRowsInputFilterLimit'), 'value' => 5000],
                ],
                'required' => true,
                'changeExpr' => '',
            ],
            "validation" => [
                "messages" => [
                    "required" => "The field is mandatory",
                ],
            ],
        ];

        array_unshift($fieldGroup, $inputFilterLimit);

        $filtersData['schema'][0]['fieldArray']['fieldGroup'] = $fieldGroup;
        return $filtersData;
    }

    public static function setInputLimit100($filtersData, $limitRecords)
    {
        if ($filtersData == null) {
            return $filtersData;
        }

        $fieldGroup = $filtersData['schema'][0]['fieldArray']['fieldGroup'];

        $inputFilterLimit = [
            'key' => 'inputFilterLimit',
            'keyInput' => 'inputFilterLimit',
            'type' => 'select',
            'className' => 'col-xs-12 col-sm-12 col-md-12 col-lg-12',
            'defaultValue' => $limitRecords,
            'templateOptions' => [
                'label' => Yii::t('app', 'labelInputFilterLimit'),
                'placeholder' => Yii::t('app', 'labelInputFilterLimit'),
                'attributes' => [
                    'title' => Yii::t('app', 'labelInputFilterLimit'),
                ],
                'options' => [
                    0 => ['label' => '10', 'value' => 10],
                    1 => ['label' => '100', 'value' => 100],
                    2 => ['label' => '1000', 'value' => 1000],
                    3 => ['label' => Yii::t('app', 'AllRowsInputFilterLimit'), 'value' => 5000],
                ],
                'required' => true,
                'changeExpr' => '',
            ],
            "validation" => [
                "messages" => [
                    "required" => "The field is mandatory",
                ],
            ],
        ];

        array_unshift($fieldGroup, $inputFilterLimit);

        $filtersData['schema'][0]['fieldArray']['fieldGroup'] = $fieldGroup;
        return $filtersData;
    }
}
