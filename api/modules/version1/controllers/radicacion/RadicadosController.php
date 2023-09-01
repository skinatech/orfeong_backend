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

namespace api\modules\version1\controllers\radicacion;

use api\components\HelperDynamicForms;
use api\components\HelperConsecutivo;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperRadicacion;
use api\components\HelperNotification;
use yii\web\UploadedFile;
use api\components\HelperFiles;
use api\components\HelperExtraerTexto;

use api\models\CgMediosRecepcion;
use api\models\CgNumeroRadicado;
use api\models\CgTiposRadicados;
use api\models\Clientes;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\GdTrdDependencias;
use api\models\GdTrdTiposDocumentales;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\RadiEnvios;
use api\models\RadiRadicados;
use api\models\TiposPersonas;
use api\models\UserDetalles;
use api\models\CgTiposRadicadosTransacciones;
use api\models\CgTransaccionesRadicados;
use api\models\CgHorarioLaboral;
use api\models\RolesOperaciones;
use api\models\RadiLogRadicados;
use api\components\HelperLog;
use api\models\RadiRadicadosAsociados;
use api\models\RadiRemitentes;
use api\models\RadiTiposOperaciones;
use yii\data\ActiveDataProvider;
use api\models\RadiDocumentos;
use api\models\RadiRadicadoAnulado;
use api\models\CgDiasNoLaborados;
use api\models\GdExpedientesInclusion;
use api\models\GdFirmasQr;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiInformados;
use api\models\CgTipoRadicadoDocumental;
use api\models\FacturacionElectronica;

use api\models\ClientesCiudadanosDetalles;
use api\models\RadiEnviosDevolucion;
use api\models\CgMotivosDevolucion;
use api\models\ExpedientesInclusion;
use  api\models\CgClasificacionPqrs;
use api\models\Roles;
use api\models\CgConsecutivosRadicados;
use api\models\RadiDetallePqrsAnonimo;

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;

use yii\helpers\FileHelper;

use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use common\models\User;
use api\models\CgGeneral;

use unyii2\imap\ImapConnection;
use unyii2\imap\Mailbox;

use api\modules\version1\controllers\gestionArchivo\GestionArchivoController;
use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\configuracionApp\ClientesController;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use Symfony\Component\BrowserKit\Client;

use api\components\HelperQueryDb;
use api\models\GdExpedientes;
use api\models\RolesTiposOperaciones;

/**
 * RadicadosController implements the CRUD actions for RadiRadicados model.
 */
class RadicadosController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'only' => ['create', 'index', 'update'],
            ],
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                ],
            ],
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    HttpBasicAuth::className(),
                    HttpBearerAuth::className(),
                    QueryParamAuth::className(),
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index' => ['GET'],
                    'index-one' => ['GET'],
                    'list-radicados' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'change-status' => ['PUT'],
                    'get-remitente-by-email' => ['GET'],
                    'list-clientes' => ['GET'],
                    'comercial' => ['POST'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /**
     * Lists all RadiRadicados models.
     * @return mixed
     */
    public function actionIndex($request)
    {
        if (!empty($request)) {
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GET ***//
        }

        //Lista de usuarios
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';
        if (is_array($request)) {
            foreach ($request['filterOperation'] as $field) {
                foreach ($field as $key => $info) {

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal' || $key == 'isRadicado') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                           $dataWhere[$key] =  $info;
                        }

                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } elseif ($key == 'idGdTrdTipoDocumental') {
                        if ($info !== null) {
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') {  // O un string
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Validación del nombre y documento de los remitentes
        $idUser = [];
        if (!empty($dataSender)){

            $modelCliente = Clientes::find()
                ->where([Yii::$app->params['like'],'nombreCliente', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'numeroDocumentoCliente', $dataSender])
            ->all();
        
            foreach($modelCliente as $infoCliente){
                $idUser[] = $infoCliente->idCliente; 
            }
           
            # Validacion del nombre y apellido del usuario
            $expDataSender = explode(' ', $dataSender);

            if( count($expDataSender) > 1 ) {                
                $name = $expDataSender[0];
                $surname = $expDataSender[1];
                
            } else {                
                $name = $dataSender;
                $surname = $dataSender;
            }

            $modelUser = UserDetalles::find()
                ->where([Yii::$app->params['like'],'nombreUserDetalles', $name])
                ->orWhere([Yii::$app->params['like'], 'apellidoUserDetalles', $surname])
                ->orWhere([Yii::$app->params['like'], 'documento', $dataSender])
            ->all();

            foreach($modelUser as $infoUser){
                $idUser[] = $infoUser->idUser;
            }
        }

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Relacionamiento de los radicados
        $relacionRadicados = RadiRadicados::find();
            //->innerJoin('gdTrdTiposDocumentales', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = radiRadicados.idTrdTipoDocumental')
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdTrdTiposDocumentales', ['radiRadicados' => 'idTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
            //->innerJoin('radiRemitentes', 'radiRemitentes.idRadiRadicado = radiRadicados.idRadiRadicado');
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRemitentes', ['radiRadicados' => 'idRadiRadicado', 'radiRemitentes' => 'idRadiRadicado']);    
            $relacionRadicados = HelperQueryDb::getQuery('leftJoin', $relacionRadicados, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);

        /** Establecer valor por defecto de radiGenerationType -> forma de creación del radicado */
        if (!isset($dataWhere['radiGenerationType'])) {
            $dataWhere['radiGenerationType'] =  Yii::$app->params['radiGenerationTypeDefault'];
        }

        # Validar si en los filtros se utilizó la opción por nivel de busqueda
        if (!isset($dataWhere['opcion'])) {
            // Por defecto se deben ver los radicados asignados al usuario logueado
            $dataWhere['opcion'] = Yii::$app->params['radiOptionSearchTypeDefault'];
        }

        // **Regla: Si el usuario logueado es de tipo de usuario de ventanilla unica o administrador de gestion documental, puede ver los radicados que creó así ya no sea el tramitador actual - SIN IMPORTAR EL NIVEL DE BUSQUEDA**
        if (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Ventanilla de Radicación'] || Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Administrador de Gestión Documental']) {
            $canViewRadiCreate = true;
        }else {
            $canViewRadiCreate = false;
        }

        //Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idRadiPersona': //remitente
                    $relacionRadicados->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'user_idTramitador': 
                    //$relacionRadicados->leftJoin('userDetalles', 'userDetalles.idUser = radiRadicados.user_idTramitador')
                    $relacionRadicados = HelperQueryDb::getQuery('leftJoin', $relacionRadicados, 'userDetalles', ['radiRadicados' => 'user_idTramitador', 'userDetalles' => 'idUser']);    
                    $relacionRadicados = $relacionRadicados->andWhere([ 'or', [ Yii::$app->params['like'],'userDetalles.nombreUserDetalles', $value ], [ Yii::$app->params['like'], 'userDetalles.apellidoUserDetalles', $value ],[ Yii::$app->params['like'], 'userDetalles.documento', $value ] ] );
                break;
                case 'idGdTrdTipoDocumental':
                    $relacionRadicados->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                break;
                case 'fechaInicial':
                    $relacionRadicados->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relacionRadicados->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idCgMedioRecepcion':
                case 'idCgTipoRadicado':
                case 'estadoRadiRadicado':
                case 'idTrdDepeUserTramitador':               
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'numeroRadiRadicado':
                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, trim($value)]);
                break;
                case 'radiGenerationType':
                    if ($value == Yii::$app->params['radiGenerationType']['radicacion']) { // Radicación
                        $relacionRadicados->andWhere(['IS', 'radiRadicados.idRadiRadicadoPadre', null]);
                    } elseif ($value == Yii::$app->params['radiGenerationType']['combinacionCorrespondencia']) { // Combinacion de correspondencia
                        $relacionRadicados->andWhere(['IS NOT', 'radiRadicados.idRadiRadicadoPadre', null]);
                    }
                break;
                case 'opcion':

                    #Filtros por nivel de busqueda
                    switch($idNivelBusqueda){
                        case Yii::$app->params['searchLevelText']['Basico']:
                            if ($value == Yii::$app->params['radiOptionSearchType']['byTramitadorUser']) { //Por tramitador
                                $relacionRadicados->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiInformados']) { //Por informados
                                $relacionRadicados->andWhere(['radiInformados.idUser' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiDependence']) { //Por dependencia
                                $relacionRadicados->andWhere(['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['allRadi']) { //Por todos
                                $relacionRadicados->andWhere(['or',
                                    ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                                    ['radiInformados.idUser' => Yii::$app->user->identity->id],
                                    ['radiRadicados.user_idCreador' => Yii::$app->user->identity->id]
                                ]);
                            }
                        break;

                        case Yii::$app->params['searchLevelText']['Intermedio']:

                            if ($value == Yii::$app->params['radiOptionSearchType']['byTramitadorUser']) { //Por tramitador
                                $relacionRadicados->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiInformados']) { //Por informados
                                $relacionRadicados->andWhere(['radiInformados.idUser' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiDependence']) { //Por dependencia
                                $relacionRadicados->andWhere(['or',
                                    ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                                    ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]
                                ]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['allRadi']) { //Por todos
                                $relacionRadicados->andWhere(['or',
                                    ['radiInformados.idUser' => Yii::$app->user->identity->id],
                                    ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                                    ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                                    ['radiRadicados.user_idCreador' => Yii::$app->user->identity->id],
                                    ['radiRadicados.idTrdDepeUserCreador' => Yii::$app->user->identity->idGdTrdDependencia]
                                ]);
                            }

                        break;

                        case Yii::$app->params['searchLevelText']['Avanzado']:

                            if ($value == Yii::$app->params['radiOptionSearchType']['byTramitadorUser']) {//Por tramitador
                                $relacionRadicados->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiInformados']) { //Por informado
                                $relacionRadicados->andWhere(['radiInformados.idUser' => Yii::$app->user->identity->id]);
                            } elseif ($value == Yii::$app->params['radiOptionSearchType']['byRadiDependence']) { //Por dependencia
                                $relacionRadicados->andWhere(['or',
                                    ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                                    ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]
                                ]);
                            }
                        break;
                    }

                break;
                case 'isRadicado':
                    $relacionRadicados->andWhere(['radiRadicados.' . $field => $value]);
                break;
                default: // asunto
                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;
            }
        }

        $relacionRadicados->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relacionRadicados->orderBy([ 
            'radiRadicados.PrioridadRadiRadicados' => SORT_DESC, 
            'radiRadicados.idRadiRadicado' => SORT_DESC
        ]); 
        
        # Limite de la consulta
        $relacionRadicados->limit($limitRecords);
        $modelRelation = $relacionRadicados->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);
        
        # Transformación de formato de la fecha de vencimiento
        $expiredDate = '';

        foreach ($modelRelation as $infoRelation) {

            /** Consultar si al radicado se le puede realizar una solicitud de anulacion */
            $radiRadicadoAnulado = RadiRadicadoAnulado::find()->select(['idRadiRadicadoAnulado'])
                ->where(['idRadicado' => $infoRelation->idRadiRadicado])
                ->andWhere(['or',
                   ['idEstado' => Yii::$app->params['statusAnnulationText']['SolicitudAnulacion']],
                   ['idEstado' => Yii::$app->params['statusAnnulationText']['AceptacionAnulacion']]
                ])->limit(1)
            ->one();

            if ($radiRadicadoAnulado == null) {
                $canSolicitudAnulacion = true;
            } else {
                $canSolicitudAnulacion = false;
            }
            /** Fin Consultar si al radicado se le puede realizar una solicitud de anulacion */            

            # Validación que evidencie que se ha cargado un anexo o documento al radicado.
            $modelDocument = RadiDocumentos::findOne(['idRadiRadicado' => $infoRelation->idRadiRadicado]);

            if(!is_null($modelDocument)){
                $document = true;
            } else {
                $document = false;
            }


            # Información del remitente
            $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $infoRelation->idRadiRadicado]); 

            # Si hay más de un registro mostrará el texto de múltiple sino muestra la información del remitente
            if(count($remitentes) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

                $sendersAddress = [];
                $sendersDocument = [];
                
                foreach($remitentes as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);

                        $sendersAddress[] = $modelRemitente->direccionCliente;
                        $sendersDocument[] = $modelRemitente->numeroDocumentoCliente;
        
                    } else {
                        $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                        $getDependency = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRemitente->idGdTrdDependencia]);
                        $sendersAddress[] = $getDependency->nombreGdTrdDependencia;
                        $sendersDocument[] = $modelRemitente->userDetalles->documento;                
                    }  
                }

                $senderAddress = implode(", ",$sendersAddress);
                $senderDocument = implode(", ",$sendersDocument);

            } else {
                foreach($remitentes as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                       
                        if($modelRemitente){
                            $nombreCliente = $modelRemitente->nombreCliente;
                            $direccionCliente = $modelRemitente->direccionCliente;
                            $numeroDocumentoCliente	= $modelRemitente->numeroDocumentoCliente;
                        }else{
                            $nombreCliente = '';
                            $direccionCliente = '';
                            $numeroDocumentoCliente	= '';
                        }

                        $senderName = $nombreCliente;
                        $senderAddress = $direccionCliente;
                        $senderDocument = $numeroDocumentoCliente;
        
                    } else {
                        $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                        $getDependency = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRemitente->idGdTrdDependencia]);
                       
                        $senderName = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                        $senderAddress = $getDependency->nombreGdTrdDependencia;
                        $senderDocument = $modelRemitente->userDetalles->documento;                
                    }  
                }
            }
                     

            //Se obtiene la traza del estado actual del radicado
            $modelLogRadicados = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                ->one();

            $transaccion = '';
            if(!empty($modelLogRadicados)){
                $transaccion = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
            }

            # Se valida permisos de los radicados seleccionados, según el usuario logueado 
            $checkFile = RadiRadicados::findOne(['numeroRadiRadicado' => $infoRelation->numeroRadiRadicado, 'user_idTramitador' => Yii::$app->user->identity->id]);

            # Si es true, puede realizar cualquier acción al radicado, sino, solo puede visualizarlo en el dataTable
            if(!is_null($checkFile)){
                $modifyFile = true;
            } else {
                $modifyFile = false;
            }
            
            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($infoRelation->fechaVencimientoRadiRadicados)); 

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->numeroRadiRadicado)),
            );

            $HelperConsecutivo = new HelperConsecutivo(); 

            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $infoRelation->idRadiRadicado,
                'TipoRadicado'          => $infoRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'numeroRadiRadicado'    => $HelperConsecutivo->numeroRadicadoXTipoRadicado($infoRelation->numeroRadiRadicado, $infoRelation->idCgTipoRadicado,$infoRelation->isRadicado),
                'creacionRadiRadicado'  => $infoRelation->creacionRadiRadicado,
                'asuntoRadiRadicado'    => (strlen($infoRelation->asuntoRadiRadicado) > 150) ? mb_substr($infoRelation->asuntoRadiRadicado, 0, 150) . '...' :  $infoRelation->asuntoRadiRadicado,
                'nombreCliente'         => $senderName,
                'direccionCliente'      => $senderAddress,
                'identificacionCliente' => $senderDocument,
                'nombreTipoDocumental'  => $infoRelation->trdTipoDocumental->nombreTipoDocumental,
                'fechaVencimientoRadiRadicados' => $expiredDate,
                'prioridadRadicados'    => Yii::t('app', 'statusPrioridadText')[$infoRelation->PrioridadRadiRadicados],
                'canSolicitudAnulacion' => $canSolicitudAnulacion,
                'usuarioTramitador'     => $infoRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                'dependenciaTramitador' => $infoRelation->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'radiUpdate'            => $modifyFile,
                'validDocument'         => $document,
                'statusText'            => Yii::t('app', $transaccion) . ' - ' .  Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoRadiRadicado],               
                'status'                => $infoRelation->estadoRadiRadicado,
                'rowSelect'             => false,
                'idInitialList'         => 0,
            );
        }
      

        // Validar que el formulario exista
        $formType = HelperDynamicForms::setListadoBD('indexRadicado');
        $formType['schema'][0]['fieldArray']['fieldGroup'][12]['defaultValue'] = $dataWhere['opcion'];

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionIndexOne($request)
    {
        //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            //*** Fin desencriptación GET ***//
            $id = $request['id'];
            $model = $this->findModel($id);
            $clienteIdArr = [];  //Id del cliente o usuario

            if ($model->isRadicado == true || $model->isRadicado == 1) {
                $textNumeroRadicadoOConsecutivo = 'Número de radicado'; // Traducido en front
            } else {
                $textNumeroRadicadoOConsecutivo = 'Número de consecutivo'; // Traducido en front
            }

            $remitentesModel = RadiRemitentes::findAll(['idRadiRadicado' => $id]);

            foreach ($remitentesModel as $remitentes) {

                if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                    $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);

                    $tipoPersona = $modelRemitente->idTipoPersona;
                    $nombreRemitente = $modelRemitente->nombreCliente;
                    $numeroDocumentoCliente = $modelRemitente->numeroDocumentoCliente;
                    $direccionCliente = $modelRemitente->direccionCliente;
                    $idNivelGeografico3 = (int) $modelRemitente->idNivelGeografico3;
                    $idNivelGeografico2 = (int) $modelRemitente->idNivelGeografico2;
                    $idNivelGeografico1 = (int) $modelRemitente->idNivelGeografico1;
                    $correoElectronicoCliente = $modelRemitente->correoElectronicoCliente;
                    $telefonoCliente = $modelRemitente->telefonoCliente;
                    $codigoSap = $modelRemitente->codigoSap;
                    // $clienteId = array( 'cliente' => (int) $modelRemitente->idCliente );
                    $clienteIdArr[] = array( 'cliente' => (int) $modelRemitente->idCliente );

                    /** Obtiene los datos de cliente detalle pqrs**/
                    $modelClienteCiudadano = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelRemitente->idCliente]);

                } else {

                    $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
                    $dependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRemitente->idGdTrdDependencia]);
                    $modelNivelGeografico1 = NivelGeografico1::findOne(["nivelGeografico1" => Yii::$app->params['nivelGeografico']['nivelGeograficoPais']]);
                    $modelNivelGeografico2 = NivelGeografico2::findOne(["nivelGeografico2" => Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento']]);
                    $modelNivelGeografico3 = NivelGeografico3::findOne(["nivelGeografico3" => Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio']]);    

                    $tipoPersona = Yii::$app->params['tipoPersonaText']['funcionario'];
                    $nombreRemitente = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                    $numeroDocumentoCliente = $modelRemitente->userDetalles->documento;
                    $direccionCliente = $dependencias->nombreGdTrdDependencia;
                    $idNivelGeografico3 = (int) $modelNivelGeografico3->nivelGeografico3;
                    $idNivelGeografico2 = (int) $modelNivelGeografico2->nivelGeografico2;
                    $idNivelGeografico1 = (int) $modelNivelGeografico1->nivelGeografico1;
                    $correoElectronicoCliente = $modelRemitente->email;
                    $telefonoCliente = '';
                    $codigoSap = '';
                    // $clienteId = array( 'user' => (int) $modelRemitente->id );
                    $clienteIdArr[] = array( 'user' => (int) $modelRemitente->id );
                }
            }

            $fechaActual = Date("Y-m-d");  
            $fechaVencimiento = Date("Y-m-d",strtotime($model['fechaVencimientoRadiRadicados']));

            /** Validar dias restantes entre fecha actual y fecha de vencimiento solo si la fecha de vencimiento es mayor o igual a la actual */
            if ($fechaVencimiento >= $fechaActual) {
                $fechaDesde = $fechaActual . ' ' . Date('H:i:s');
                $fechaHasta = $fechaVencimiento . ' 23:59:59';
                $diasRestantes = HelperRadicacion::calcularDiasEntreFechas($fechaDesde, $fechaHasta);
            } else {
                $diasRestantes = 0;
            }

            $formatoFechaVencimiento = self::formatoFechaVencimiento($fechaVencimiento);
     
            $arrayRadicadosAsociados = [];
            $radiAsociados = RadiRadicadosAsociados::find()->where(['idRadiCreado' => $id])->all(); 

            foreach($radiAsociados as $key => $value){
                array_push($arrayRadicadosAsociados,$value['idRadiAsociado']);
            }

            $autorizacionRadiRadicados = null;
            if($model->autorizacionRadiRadicados === Yii::$app->params['autorizacionRadiRadicado']['text']['correo']){
                $autorizacionRadiRadicados = true;
            }else if($model->autorizacionRadiRadicados === Yii::$app->params['autorizacionRadiRadicado']['text']['fisico']){
                $autorizacionRadiRadicados = false;
            }

            $data = [
                'numeroRadiRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado),
                'RadiRadicadoHijos' => $arrayRadicadosAsociados,
                'idCliente' => $clienteIdArr,
                'idTipoPersona' => $tipoPersona,
                'nombreCliente' => $nombreRemitente,
                'numeroDocumentoCliente' => $numeroDocumentoCliente,
                'direccionCliente' => $direccionCliente,
                'idNivelGeografico3' => $idNivelGeografico3,
                'idNivelGeografico2' => $idNivelGeografico2,
                'idNivelGeografico1' => $idNivelGeografico1,
                'correoElectronicoCliente' => $correoElectronicoCliente,
                'telefonoCliente' => $telefonoCliente,
                'codigoSap' => $codigoSap,
                'idTrdTipoDocumental' => $model->idTrdTipoDocumental,
                'idCgTipoRadicado' => $model->idCgTipoRadicado,               
                'PrioridadRadiRadicados' => $model->PrioridadRadiRadicados,
                'idCgMedioRecepcion' => $model->idCgMedioRecepcion,
                'asuntoRadiRadicado' => $model->asuntoRadiRadicado,
                'fechaVencimientoRadiRadicados' => $formatoFechaVencimiento['formatoFrontend'], 
                'diasRestantes' => $diasRestantes,
                'foliosRadiRadicado' => $model->foliosRadiRadicado,
                'idTrdDepeUserTramitador' => $model->idTrdDepeUserTramitador,
                'idGdTrdSerie' => $model->idGdTrdSerie,
                'idGdTrdSubserie' => $model->idGdTrdSubserie,
                'user_idTramitador' => $model->user_idTramitador,
                'radicadoOrigen' => $model->radicadoOrigen,
                'descripcionAnexoRadiRadicado' => $model->descripcionAnexoRadiRadicado,
                'fechaDocumentoRadiRadicado' => ($model->fechaDocumentoRadiRadicado ? $model->fechaDocumentoRadiRadicado.'T05:00:00.000Z' : null),
                'observacionRadiRadicado' => $model->observacionRadiRadicado,
                'numeroFactura'  => $model->numeroFactura,
                'numeroContrato' => $model->numeroContrato,
                'valorFactura'   => $model->valorFactura,
                'autorizacionRadiRadicados' => $autorizacionRadiRadicados,
                'creacionRadiRadicado' => $model->creacionRadiRadicado
            ];

            if(!empty($modelClienteCiudadano)){
                $data['idUser'] = $modelClienteCiudadano->idUser;
                $data['idTipoIdentificacion'] = $modelClienteCiudadano->idTipoIdentificacion;
                $data['generoClienteCiudadanoDetalle'] = $modelClienteCiudadano->generoClienteCiudadanoDetalle;
                $data['rangoEdadClienteCiudadanoDetalle'] = $modelClienteCiudadano->rangoEdadClienteCiudadanoDetalle;
                $data['vulnerabilidadClienteCiudadanoDetalle'] = $modelClienteCiudadano->vulnerabilidadClienteCiudadanoDetalle;
                $data['etniaClienteCiudadanoDetalle'] = $modelClienteCiudadano->etniaClienteCiudadanoDetalle;
            }

            /** Verificar si el usuario puede modificar el radicado */
            $transacciones = [];
            if ($model->user_idTramitador == Yii::$app->user->identity->id) {
                $transacciones[] = [ 'icon' => 'save', 'title' => 'Guardar', 'action' => 'save', 'data' => '' ];
            }

            # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
            $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);
            # Se valida si el usuario logueado es de tipo JEFE
            $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

            /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
            $ignoreTransacions = self::getIgnoreTransactions([], $permissionVoBo, $isUserJefe, $model, 'update');

            // El botón flotante solo debe ver:  crear,  ver, cargar doc's y stickers **el botón de guardar no aplica para cuando yo no soy el dueño del radicado**
            //$transaccionesUpdate = Yii::$app->params['transaccionesUpdateRadicado']; // ['view', 'add', 'attachment', 'printStickers'];
            $radicadoTransacciones = $this::radicadoTransacciones($model->idCgTipoRadicado, false, $ignoreTransacions);
            $arrayCgTiposRadicados[0]['idCgTipoRadicado'] = $model->idCgTipoRadicado;
            $transaccionesValidateResoluciones = $this->validandoBotonDetalleResoluciones($arrayCgTiposRadicados, $radicadoTransacciones);

            foreach ($transaccionesValidateResoluciones as $value) {
                $transacciones[] = $value;
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'dataTransacciones' => $transacciones,
                'textNumeroRadicadoOConsecutivo' => $textNumeroRadicadoOConsecutivo,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Lists básico radicados seleccionados
     * @return mixed
     */
    public function actionListRadicados($request)
    {
        if (!empty($request)) {
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GET ***//
        }

        $dataList = [];
        $radiRadicados = [];

        if (isset($request['radicados']) && is_array($request['radicados'])) {
            $arrayWhereRadi = [];
            foreach ($request['radicados'] as $value) {
                $arrayWhereRadi[] = $value['id'];
            }

            $radiRadicados = RadiRadicados::find()
                ->where(['IN', 'idRadiRadicado', $arrayWhereRadi])
                ->all();
        }

        foreach ($radiRadicados as $radicado) {
            $dataList[] = array(
                'id'                    => $radicado->idRadiRadicado,
                'numeroRadiRadicado'    => $radicado->numeroRadiRadicado,
                'asuntoRadiRadicado'    => $radicado->asuntoRadiRadicado,
                'tipoRadicado'          => $radicado->cgTipoRadicado->nombreCgTipoRadicado,
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'count' => count($dataList),
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Displays a single User model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación GET ***//

            $id = $request['id'];
            $data = [];
            $dataUserTramitador = [];
            $dataHistorico = [];
            $posicion = 1;

            # Consulta en RadiRadicados
            $model = $this->findModel($id);

            if ($model->isRadicado == true || $model->isRadicado == 1) {
                $eventLogText = Yii::$app->params['eventosLogText']['View'].' Id: '.$model->idRadiRadicado.' del Radicado: '.$model->numeroRadiRadicado; //texto para almacenar en el evento
            } else {
                $eventLogText = Yii::$app->params['eventosLogText']['View'].' Id: '.$model->idRadiRadicado.' del Consecutivo: '.$model->numeroRadiRadicado; //texto para almacenar en el evento
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                $eventLogText,
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/


            # DETALLES DEL RADICADO

            # Información del tramitador
            $modeluser = User::findOne(['id' => $model->user_idTramitador]);
            $modeldependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $model->idTrdDepeUserTramitador]);

            # Numero radicado asociado
            $modelRadiAsociado = RadiRadicadosAsociados::find()
                ->where(['idRadiCreado' => $model->idRadiRadicado])
            ->all();

            $arrayRadicadosAsociados = []; //Agrupa los radicados asociados, según el radicado seleccionado
            foreach($modelRadiAsociado as $radiAsociado){
                $modelRadicado = RadiRadicados::find()->select(['numeroRadiRadicado'])->where(['idRadiRadicado' => $radiAsociado->idRadiAsociado])->one();

                if(!is_null($modelRadicado)) {
                    $arrayRadicadosAsociados[] = $modelRadicado->numeroRadiRadicado;
                }
            }

            # Prioridad del radicado
            if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }


            # Informacion del remitente ya sea un cliente o usuario (funcionario)
            $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $model->idRadiRadicado]); 

            # Información del remitente en caso de ser anonimo en el proceso de PQRS
            $remitenteAnonimo = RadiDetallePqrsAnonimo::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
            if($remitenteAnonimo){

                $direccionCliente = $remitenteAnonimo->direccionRadiDetallePqrsAnonimo;
                $idNivelGeografico3 = $remitenteAnonimo->idNivelGeografico3;
                $idNivelGeografico2 = $remitenteAnonimo->idNivelGeografico2;
                $idNivelGeografico1 = $remitenteAnonimo->idNivelGeografico1;
                $correoElectronicoCliente = $remitenteAnonimo->emailRadiDetallePqrsAnonimo; 

                # Consulta del País, departamento y municipio
                $modelNivelGeografico1 = NivelGeografico1::findOne(["nivelGeografico1" => $idNivelGeografico1]);
                $modelNivelGeografico2 = NivelGeografico2::findOne(["nivelGeografico2" => $idNivelGeografico2]);
                $modelNivelGeografico3 = NivelGeografico3::findOne(["nivelGeografico3" => $idNivelGeografico3]);

                $data[] = array('alias' => 'Dirección de Correspondencia', 'value' => $direccionCliente);
                $data[] = array('alias' => 'Municipio', 'value' => $modelNivelGeografico3->nomNivelGeografico3);
                $data[] = array('alias' => 'Departamento', 'value' => $modelNivelGeografico2->nomNivelGeografico2);
                $data[] = array('alias' => 'País', 'value' => $modelNivelGeografico1->nomNivelGeografico1);
                $data[] = array('alias' => 'Correo electrónico', 'value' => $correoElectronicoCliente);

            }
            else{

                foreach($remitentes as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
    
                        # Se obtiene la información del cliente
                        $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);

                        if($modelRemitente){
                            $nombreRemitente = $modelRemitente->nombreCliente;
                            $numeroDocumentoCliente = $modelRemitente->numeroDocumentoCliente;
                            $direccionCliente = $modelRemitente->direccionCliente;
                            $idNivelGeografico3 = $modelRemitente->idNivelGeografico3;
                            $idNivelGeografico2 = $modelRemitente->idNivelGeografico2;
                            $idNivelGeografico1 = $modelRemitente->idNivelGeografico1;
                            $correoElectronicoCliente = $modelRemitente->correoElectronicoCliente;
                            $telefonoCliente = $modelRemitente->telefonoCliente;
                            //$codigoSap = $modelRemitente->codigoSap;
                        }else{
                            $nombreRemitente = '';
                            $numeroDocumentoCliente = '';
                            $direccionCliente = '';
                            $idNivelGeografico3 = Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio'];
                            $idNivelGeografico2 = Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento'];
                            $idNivelGeografico1 = Yii::$app->params['nivelGeografico']['nivelGeograficoPais'];
                            $correoElectronicoCliente = '';
                            $telefonoCliente = '';
                        }   
                        
        
                    } else {
        
                        # Se obtiene la información del usuario o funcionario
                        $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                        $dependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRemitente->idGdTrdDependencia]);
        
                        $nombreRemitente = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                        $numeroDocumentoCliente = $modelRemitente->userDetalles->documento;
                        $direccionCliente = $dependencias->nombreGdTrdDependencia;
                        $idNivelGeografico3 = Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio'];
                        $idNivelGeografico2 = Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento'];
                        $idNivelGeografico1 = Yii::$app->params['nivelGeografico']['nivelGeograficoPais'];
                        $correoElectronicoCliente = $modelRemitente->email;
                        $telefonoCliente = '';
                        $codigoSap = '';
                    }
    
                    # Consulta del País, departamento y municipio
                    $modelNivelGeografico1 = NivelGeografico1::findOne(["nivelGeografico1" => $idNivelGeografico1]);
                    $modelNivelGeografico2 = NivelGeografico2::findOne(["nivelGeografico2" => $idNivelGeografico2]);
                    $modelNivelGeografico3 = NivelGeografico3::findOne(["nivelGeografico3" => $idNivelGeografico3]);
    
                    $data[] = array('alias' => 'Tipo de persona', 'value' => $dataRemitente->idTipoPersona0->tipoPersona);
                    $data[] = array('alias' => 'Nombre', 'value' => $nombreRemitente);
                    $data[] = array('alias' => 'Documento Identificación', 'value' => $numeroDocumentoCliente);
                    $data[] = array('alias' => 'Dirección de Correspondencia', 'value' => $direccionCliente);
                    $data[] = array('alias' => 'Municipio', 'value' => $modelNivelGeografico3->nomNivelGeografico3);
                    $data[] = array('alias' => 'Departamento', 'value' => $modelNivelGeografico2->nomNivelGeografico2);
                    $data[] = array('alias' => 'País', 'value' => $modelNivelGeografico1->nomNivelGeografico1);
                    $data[] = array('alias' => 'Correo electrónico', 'value' => $correoElectronicoCliente);
                    $data[] = array('alias' => 'Teléfono de Contacto', 'value' => $telefonoCliente);
                    //$data[] = array('alias' => 'Código SAP', 'value' => $codigoSap);
                }
            }

            if ($model->isRadicado == true || $model->isRadicado == 1) {

                $HelperConsecutivo = new HelperConsecutivo();
                $HelperConsecutivo->numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado);

                $textFormView = 'Detalle del radicado'; //traducido en front
                $data[] = array('alias' => 'Número radicado', 'value' => $HelperConsecutivo->numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado,$model->isRadicado));
            } else {
                $textFormView = 'Detalle del consecutivo'; //traducido en front
                $data[] = array('alias' => 'Número consecutivo', 'value' => $model->numeroRadiRadicado);
            }
            $numRadi = $model->numeroRadiRadicado;
                if (count($arrayRadicadosAsociados) == 1) {
                    $data[] = array('alias' => 'Radicado Asociado', 'value' => $arrayRadicadosAsociados);

                } elseif (count($arrayRadicadosAsociados) > 1) {
                    $data[] = array('alias' => 'Radicados Asociados', 'value' => implode(', ', $arrayRadicadosAsociados));
                }

            $data[] = array('alias' => 'Tipo documental', 'value' => $model->trdTipoDocumental->nombreTipoDocumental);
            $data[] = array('alias' => 'Tipo radicado', 'value' => $model->cgTipoRadicado->nombreCgTipoRadicado);
            
            $data[] = array('alias' => 'Prioridad', 'value' => $prioridad);
            $data[] = array('alias' => 'Medio Recepción', 'value' => $model->cgMedioRecepcion->nombreCgMedioRecepcion);
            $data[] = array('alias' => 'Fecha vencimiento', 'value' => ( $model->fechaVencimientoRadiRadicados ? date('Y-m-d', strtotime($model->fechaVencimientoRadiRadicados)) : null ));
            $data[] = array('alias' => 'Dependencia Tramitadora', 'value' => $modeldependencia->nombreGdTrdDependencia);
            if ($model->idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                $serie = GdTrdSeries::find()->where(['idGdTrdSerie' => $model->idGdTrdSerie])->one();
                $data[] = array('alias' => 'Serie', 'value' => ($serie != null) ? $serie->nombreGdTrdSerie : '');
                $subserie = GdTrdSubseries::find()->where(['idGdTrdSubserie' => $model->idGdTrdSubserie])->one();
                $data[] = array('alias' => 'Subserie', 'value' => ($subserie != null) ? $subserie->nombreGdTrdSubserie : '');
            }
            $data[] = array('alias' => 'Usuario tramitador', 'value' => $modeluser->userDetalles->nombreUserDetalles.' '.$modeluser->userDetalles->apellidoUserDetalles);
            $data[] = array('alias' => 'Cantidad de envíos por correo', 'value' => $model->cantidadCorreosRadicado);
            $data[] = array('alias' => 'Radicado origen', 'value' => $model->radicadoOrigen);
            $data[] = array('alias' => 'Descripción del anexo', 'value' => $model->descripcionAnexoRadiRadicado);
            $data[] = array('alias' => 'Fecha documento', 'value' => ( $model->fechaDocumentoRadiRadicado ? date('Y-m-d', strtotime($model->fechaDocumentoRadiRadicado)) : null ));
            $data[] = array('alias' => 'Observación', 'value' => $model->observacionRadiRadicado);
            $data[] = array('alias' => 'Autoriza envío de correo', 'value' => ($model->autorizacionRadiRadicados == Yii::$app->params['statusTodoText']['Activo']) ? 'Si' : 'No' ); // Recibe el valor seleccionado por el usuario desde el frontent 'Si' o 'No' (default 'NO')
            if ($model->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                $data[] = array('alias' => 'Número de factura',  'value' => $model->numeroFactura);
                $data[] = array('alias' => 'Número de contrato', 'value' => $model->numeroContrato);
                $data[] = array('alias' => 'Valor factura',      'value' => $model->valorFactura);
            }
            $data[] = array('statuslimitText' => true, 'limitText' => 150, 'alias' => 'Asunto', 'value' => $model->asuntoRadiRadicado);
            $data[] = array('alias' => 'Firmado digitalmente', 'value' => ($model->firmaDigital == Yii::$app->params['statusTodoText']['Activo']) ? 'Si' : 'No' );

            $modelCgGeneral = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();
            if ($model->idCgTipoRadicado === $modelCgGeneral->resolucionesIdCgGeneral && $model->radiRadicadosResoluciones) {
                $modelRadicadosResoluciones =  $model->radiRadicadosResoluciones;
                $data[] = array('alias' => 'Número resolución', 'value' => $modelRadicadosResoluciones->numeroRadiRadicadoResolucion);
                $data[] = array('alias' => 'Fecha resolución', 'value' => $modelRadicadosResoluciones->fechaRadiRadicadoResolucion);
                $data[] = array('alias' => 'Valor resolución', 'value' => $modelRadicadosResoluciones->valorRadiRadicadoResolucion);
            }

            // Data usuario tramitador
            $dataUserTramitador['usuarioTramitador'] = $modeluser->userDetalles->nombreUserDetalles.' '.$modeluser->userDetalles->apellidoUserDetalles;
            $dataUserTramitador['user_idTramitador'] = $modeluser->id;


            # TRAZABILIDAD DEL RADICADO
            /* Muestra el historico del radicado */
            $historicoRadicados = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $id])
                ->orderBy(['idRadiLogRadicado' => SORT_DESC])
                ->all();

            foreach($historicoRadicados as $historicoRadicado){
 
                $dataHistorico[] = array(
                    'iconTrans'     => $historicoRadicado->transaccion->iconCgTransaccionRadicado,
                    'transaccion'   => $historicoRadicado->transaccion->descripcionCgTransaccionRadicado,  //acción
                    'fecha'         => $historicoRadicado->fechaRadiLogRadicado,                    
                    'usuario'       => $historicoRadicado->user->userDetalles->nombreUserDetalles.' '.$historicoRadicado->user->userDetalles->apellidoUserDetalles,
                    'dependencia'   => $historicoRadicado->dependencia->nombreGdTrdDependencia,   
                    'observacion'   => $historicoRadicado->observacionRadiLogRadicado,
                );
            }

            /** Validar si el usuario logueado es tramitador o informado */
            $isUserTramitador = ($model->user_idTramitador == Yii::$app->user->identity->id);
            $isUserInformado = false;

            if ($isUserTramitador == false) {
                $radiInformados = RadiInformados::find()
                    ->select(['idRadiInformado']) // Para que la consulta pese menos
                    ->where(['idRadiRadicado' => $id])
                    ->andWhere(['idUser' => Yii::$app->user->identity->id])
                    ->limit(1) // Para que la consulta pese menos
                ->one();
                if ($radiInformados != null) {
                    $isUserInformado = true;
                }
            }
            /** Validar si el usuario logueado es tramitador o informado */
            
            /* Validación de los perfiles con permiso de visualizar documentos confidenciales */

                # Id del rol del usuario logueado.
                $idRol = Yii::$app->user->identity->idRol;

                # Se obtiene el id de la operacion para mostrar los documentos confidenciales
                $modelOperation = RolesOperaciones::findOne(['nombreRolOperacion' => 'version1%radicacion%radicados%confidential-documents']);            

                $idsRol = [];
                if(!is_null($modelOperation)){

                    # Se obtiene los roles con permiso
                    $modelTypeOperation = RolesTiposOperaciones::findAll(['idRolOperacion' => $modelOperation->idRolOperacion]);

                    foreach($modelTypeOperation as $dataType){
                        $idsRol[] = $dataType->idRol;
                    }
                }
                
            /* Fin validación de los perfiles con permiso de visualizar documentos confidenciales */

            # DOCUMENTO DEL RADICADO ASOCIADOS     
            $documentos = [];
            $radiDocumentos = RadiDocumentos::find()
                ->where(['idRadiRadicado' => $id])
                ->orderBy(['creacionRadiDocumento' => SORT_DESC])
            ->all();
            
            foreach($radiDocumentos as $key => $radiDocumento){

                /** Si el usuario logueado no es dueño del radicado pero ha sido informado, y el perfil tiene permiso de documentos confidenciales, puede ver todos los documentos */
                if ($isUserTramitador == true || $isUserInformado == true || in_array($idRol, $idsRol) || $idRol == Yii::$app->params['tipoUsuario']['Ventanilla de Radicación'] || $idRol == Yii::$app->params['tipoUsuario']['Administrador del Sistema']) {

                    $descripcion = (string) $radiDocumento['descripcionRadiDocumento'];

                    $documentos[] = [
                        'id'            => $radiDocumento->idRadiDocumento,
                        'icon'          => $radiDocumento->extencionRadiDocumento,
                        'nombre'        => $radiDocumento->nombreRadiDocumento,
                        'numDocumento'  => $radiDocumento->numeroRadiDocumento,
                        'descripcion'   => (strlen($descripcion) > 100) ? mb_convert_encoding(substr($descripcion, 0, 100), 'UTF-8', 'UTF-8') . '...' : $descripcion,
                        'descripcionTitle' => $radiDocumento->descripcionRadiDocumento,
                        'tipodocumento' => $radiDocumento->idGdTrdTipoDocumental0->nombreTipoDocumental,
                        'usuario'       => $radiDocumento->idUser0->nombreUserDetalles.' '.$radiDocumento->idUser0->apellidoUserDetalles,
                        'estado'        => $radiDocumento->estadoRadiDocumento,
                        'fecha'         => date("d-m-Y", strtotime($radiDocumento->creacionRadiDocumento)),
                        'isPdf'         => (strtolower($radiDocumento->extencionRadiDocumento) == 'pdf') ? true : false,
                        'isPublicoRadiDocumento' => Yii::$app->params['SiNoNumber'][$radiDocumento->isPublicoRadiDocumento],
                        'statusIdPublic' => $radiDocumento->publicoPagina,
                        'statusTextPublic' => Yii::t('app', 'valuePageNumber')[$radiDocumento->publicoPagina],
                    ];
                }
            }


            # CORRESPONDENCIA DEL RADICADO  
            $correspondencia = [];
            $radiCorrespondencia = RadiEnvios::findAll(['idRadiRadicado' => $id]);
            
            if(!is_null($radiCorrespondencia)){
                
                $i = 0;
                foreach($radiCorrespondencia as $key => $radiEnvios){

                    switch($radiEnvios['estadoRadiEnvio']) {
                        case 6:
                            $estado = Yii::$app->params['statusTodoNumber'][6];
                        break;
                        case 7:
                            $estado = Yii::$app->params['statusTodoNumber'][7];
                        break;
                        case 8:
                            $estado = Yii::$app->params['statusTodoNumber'][8];
                        break;
                        case 9:
                            $estado = Yii::$app->params['statusTodoNumber'][9];
                        break;
                    }
                    
                    $descripcion = (string) $radiEnvios['observacionRadiEnvio'];
                    
                    $correspondencia[$i] = [
                        'id'          => $radiEnvios['idRadiEnvio'],
                        'icon'        => $radiEnvios['extensionRadiEnvio'] ? 'description' : 'image_not_supported',
                        'guia'        => $radiEnvios['numeroGuiaRadiEnvio'],
                        'estadoEnvio' => $estado,
                        'descripcion' => (strlen($descripcion) > 100) ? substr($descripcion, 0, 100) . '...' : $descripcion,
                        'regional'    => $radiEnvios->idCgRegional0->nombreCgRegional,
                        'proveedor'   => $radiEnvios->idCgProveedores0->nombreCgProveedor,     
                        'servicio'    => $radiEnvios->idCgEnvioServicio0->nombreCgEnvioServicio,     
                        'usuario'     => $radiEnvios->idUser0->userDetalles->nombreUserDetalles.' '.$radiEnvios->idUser0->userDetalles->apellidoUserDetalles, 
                        'fecha'       => date("d-m-Y", strtotime($radiEnvios['creacionRadiEnvio'])),
                        'extension'   => $radiEnvios->extensionRadiEnvio, 
                    ];

                    /** Consultar motivos de devolución */
                    if($radiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['Devuelto']) {
                        $radiEnviosDevolucion = RadiEnviosDevolucion::find()->where(['idRadiEnvio' => $radiEnvios['idRadiEnvio']])
                            ->orderBy(['idRadiEnvioDevolucion' => SORT_DESC])
                        ->one();
                        if ($radiEnviosDevolucion != null) {
                            $motivoDevolucion = CgMotivosDevolucion::find()->select(['nombreCgMotivoDevolucion'])
                                ->where(['idCgMotivoDevolucion' => $radiEnviosDevolucion->idCgMotivoDevolucion])
                            ->one();

                            $correspondencia[$i]['motivoDevolucion'] = $motivoDevolucion->nombreCgMotivoDevolucion;
                        }

                        /** Consultar Tiempo Transcurrido entre la radicación y la devolución documento */
                        $tiempoEntrega = HelperRadicacion::calcularDiasEntreFechas($radiEnvios['creacionRadiEnvio'], $radiEnviosDevolucion['fechaDevolucion']);
                        if($tiempoEntrega == 0){
                            $correspondencia[$i]['tiempoDevuelto'] = Yii::t('app', 'hoy');
                        }else{    
                            if($tiempoEntrega == 1) {
                                $correspondencia[$i]['tiempoDevuelto'] = $tiempoEntrega . ' ' . Yii::t('app', 'día');
                            } else {
                                $correspondencia[$i]['tiempoDevuelto'] = $tiempoEntrega . ' ' . Yii::t('app', 'días');
                            }
                        }
                        /** Fin Consultar Tiempo Transcurrido entre la radicación y la devolución documento */
                    }
                    /** Fin Consultar motivos de devolución */

                    /** Consultar Tiempo Transcurrido entre la radicación y la entrega física de documento */
                    if($radiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['Entregado']) {
                        $tiempoEntrega = HelperRadicacion::calcularDiasEntreFechas($radiEnvios['creacionRadiEnvio'], $radiEnvios['creacionRadiEnvio']);
                        if($tiempoEntrega == 0){
                            $correspondencia[$i]['tiempoDevuelto'] = Yii::t('app', 'hoy');
                        }else{  
                            if($tiempoEntrega == 1) {
                                $correspondencia[$i]['tiempoEntrega'] = $tiempoEntrega . ' ' . Yii::t('app', 'día');
                            } else {
                                $correspondencia[$i]['tiempoEntrega'] = $tiempoEntrega . ' ' . Yii::t('app', 'días');
                            }
                        }
                    }
                    /** Fin Consultar Tiempo Transcurrido entre la radicación y la entrega física de documento */
                    $i++;
                }
            }

            // Busca información del expediente
            $modelExpeInclu = GdExpedientesInclusion::find()->where(['idRadiRadicado' => $id ])->one();
            $nombreExpediente = '';
            $idExpediente = '';
            if( $modelExpeInclu ){
                $nombreExpediente = $modelExpeInclu->gdExpediente->numeroGdExpediente.' - '.$modelExpeInclu->gdExpediente->nombreGdExpediente;
                $idExpediente = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($modelExpeInclu->gdExpediente->idGdExpediente)),
                );
            }


            # DOCUMENTOS PRINCIPALES
            $modelDocPrincipal = RadiDocumentosPrincipales::find()
                ->select(['nombreRadiDocumentoPrincipal'])
                ->where(['idRadiRadicado' => $id])
                ->groupBy(['nombreRadiDocumentoPrincipal'])
            ->all();

            $dataDocPrincipal = [];            
            if(!empty($modelDocPrincipal)){

                foreach($modelDocPrincipal as $key => $radiDocPrincipal){
                    $dataDocPrincipal[] = [
                        'nombreDocPrincipal' => $radiDocPrincipal->nombreRadiDocumentoPrincipal,
                    ];
                }
            }

            # RADICADOS ARCHIVADOS
            $archivados[] = []; // GestionArchivoController::viewLocation($model,$id);

            $messages = [];

            #Verifica si el tipo de radicado es pqr
            $modelTipoRadicado = CgTiposRadicados::find()
                ->select(['idCgTipoRadicado'])
                ->where(['nombreCgTipoRadicado' => 'Pqrsd'])
            ->one();


            //Si el radicado es tipo PQRS
            if(isset($modelTipoRadicado)){
                if($model->idCgTipoRadicado ==  $modelTipoRadicado->idCgTipoRadicado){                    

                    //Determinar si enviar correo a cliente ciudadano
                    if($model->autorizacionRadiRadicados == Yii::$app->params['seguimientoViaPQRS']['text']['CorreoElectrónico']
                    ){
                        $messages[] = Yii::t('app', 'emailCitizen');
                    }else if($model->autorizacionRadiRadicados == Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica']){
                        $messages[] = Yii::t('app', 'noEmailCitizen');
                    } 
                
                }
            }

            /** Validar si el radicado está finalizado */
            $isRadiFinalizado = ($model->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']);
            $onlyDownloadStatus = ($model->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Inactivo']) ? true : false;

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' =>  'OK',
                'messageNotification' => $messages,
                'textFormView' => $textFormView,
                'data' => $data,
                'dataHistorico' => $dataHistorico,
                'nombreExpediente' => $nombreExpediente,
                'idExpediente' => $idExpediente,
                'numeroRadiRadicado' => $numRadi,
                'dataDocumentos' => $documentos,
                'dataCorrespondencia' => $correspondencia,
                'dataDocPrincipal' => $dataDocPrincipal,
                'dataUserTramitador' => $dataUserTramitador,
                'dataArchivados' => $archivados,
                'isUserTramitador' => $isUserTramitador,
                'isUserInformado' => $isUserInformado,
                'isRadiFinalizado' => $isRadiFinalizado,
                'onlyDownloadStatus' => $onlyDownloadStatus,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionCiudadanoList(){

        $dataGenero = [];
        $dataRangoEdad = [];
        $dataVulnerabilidad = [];
        $dataEtnia = [];
        $dataTipoClasificacion = [];

        $modelTipoPersonal = TiposPersonas::find()
        ->where(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']])
        ->andWhere(['<>', 'idTipoPersona', Yii::$app->params['tipoPersonaText']['funcionario'] ])
        ->all();

        foreach ($modelTipoPersonal as $row) {
            $dataTipoPersonal[] = array(
                'id' => (int) $row['idTipoPersona'],
                'val' => $row['tipoPersona'],
            );
        }

        $generoClienteCiudadanoDetalle = Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'];
        foreach($generoClienteCiudadanoDetalle as $key => $value){
            $dataGenero[] = [
                'id' => $key,
                'val' => $value
            ];
        }

        $rangoEdadClienteCiudadanoDetalle = Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'];
        foreach($rangoEdadClienteCiudadanoDetalle as $key => $value){
            $dataRangoEdad[] = [
                'id' => $key,
                'val' => $value
            ];
        }

        $vulnerabilidadClienteCiudadanoDetalle = Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'];
        foreach($vulnerabilidadClienteCiudadanoDetalle as $key => $value){
            $dataVulnerabilidad[] = [
                'id' => $key,
                'val' => $value
            ];
        }

        $etniaClienteCiudadanoDetalle = Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'];
        foreach($etniaClienteCiudadanoDetalle as $key => $value){
            $dataEtnia[] = [
                'id' => $key,
                'val' => $value
            ];
        }

        $modelClasificacionPqr = CgClasificacionPqrs::find()
            ->where(['estadoCgClasificacionPqrs' => Yii::$app->params['statusTodoText']['Activo']])
            ->all()
        ;
        foreach($modelClasificacionPqr as $clasificacion){
            $dataTipoClasificacion[] = [
                'id' => $clasificacion->idCgClasificacionPqrs,
                'val' => $clasificacion->nombreCgClasificacionPqrs
            ];
        }
        

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => [],
            'dataGenero' => $dataGenero,
            'dataRangoEdad' => $dataRangoEdad,
            'dataVulnerabilidad' => $dataVulnerabilidad,
            'dataEtnia' => $dataEtnia,
            'dataTipoClasificacion' =>  $dataTipoClasificacion,
            'dataTipoPersona' => $dataTipoPersonal,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }    
  

    /**
     * Creates a new RadiRadicados model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            if (!empty($request)) {

                //*** Inicio desencriptación GET ***//
                $decrypted = HelperEncryptAes::decryptGET($request, true);
                if ($decrypted['status'] == true) {
                    $requestData = $decrypted['request'];

                } else {
                    Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                    $response = [
                        'message' => Yii::t('app', 'errorDesencriptacion'),
                        'data' => Yii::t('app', 'errorDesencriptacion'),
                        'status' => Yii::$app->params['statusErrorEncrypt'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //*** Fin desencriptación GET ***//

                ini_set('memory_limit', '3073741824');
                ini_set('max_execution_time', 900);

                # Declaración de variables
                $request = $requestData['data']; 

                $errores = [];
                $radicados = [];        // Guarda el modelo completo del radicado, esto es por cada remitente seleccionado
                $numerosRadicados = ''; // Guarda los numeros de radicados concatenados para el mensaje final del frontend
                $dataRadicados = '';    //Data para el log
                $isRadicado = false;
    
                $statusFileUpload = false;
                $fileUpload = null;

                /** Validar si cargo un archivo subido slolo para entrada y Pqrs (Por ahora ya no utilizará) **/
                // if ($request['idCgTipoRadicado']== Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                //     $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

                //     if ($fileUpload) {

                //         if(isset($fileUpload->error) && $fileUpload->error === 1){

                //             $uploadMaxFilesize = ini_get('upload_max_filesize');

                //             Yii::$app->response->statusCode = 200;
                //             $response = [
                //                 'message' => Yii::t('app','errorValidacion'),
                //                 'data' => ['error' => [Yii::t('app', 'uploadMaxFilesize', [
                //                     'uploadMaxFilesize' => $uploadMaxFilesize
                //                 ])]],
                //                 'status' => Yii::$app->params['statusErrorValidacion'],
                //             ];
                //             return HelperEncryptAes::encrypt($response, true);
                //         }

                //         /**Valida el tamaño del archivo establecido en orfeo */
                //         $resultado = HelperFiles::validateCgTamanoArchivo($fileUpload);
                //         $statusFileUpload = true;

                //         if(!$resultado['ok']){
                //             $orfeoMaxFileSize = $resultado['data']['orfeoMaxFileSize'];

                //             Yii::$app->response->statusCode = 200;
                //             $response = [
                //                 'message' => Yii::t('app','errorValidacion'),
                //                 'data' => ['error' => [Yii::t('app', 'orfeoMaxFileSize', [
                //                     'orfeoMaxFileSize' => $orfeoMaxFileSize
                //                     ])]],
                //                 'status' => Yii::$app->params['statusErrorValidacion'],
                //             ];
                //         return HelperEncryptAes::encrypt($response, true);
                //         }
                //         /** Fin de validación */
                //     } else {
                //         Yii::$app->response->statusCode = 200;
                //         $response = [
                //             'message' => Yii::t('app','errorValidacion'),
                //             //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                //             'data' => ['error' => [Yii::t('app', 'canNotUpFile')]],
                //             'status' => Yii::$app->params['statusErrorValidacion'],
                //         ];
                //         return HelperEncryptAes::encrypt($response, true);
                //     }
                // }
    
                $isRadicacionMail = false;  
                $requestEmail = $requestData['dataEmail'];
                $observacionAdicional = '';  //Observacion adicional del log radicados
                $infoMailProcess = '';               

                //Cambia el valor true - false de autorización a 0 - 10
                if(isset($request['autorizacionRadiRadicados'])){
                    if($request['autorizacionRadiRadicados']){
                        $request['autorizacionRadiRadicados'] = Yii::$app->params['autorizacionRadiRadicado']['text']['correo'];
                    }else {
                        $request['autorizacionRadiRadicados'] = Yii::$app->params['autorizacionRadiRadicado']['text']['fisico'];
                    }
                }else {
                    $request['autorizacionRadiRadicados'] = null;
                }              

                # Transacción de crear radicado
                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);
                
                # Se aplica el calculo de la fecha de vencimiento
                $calcularfechaVencimiento = self::calcularfechaVencimiento($request['idTrdTipoDocumental'], $request['diasRestantes']);
                $request['fechaVencimientoRadiRadicados'] = date("Y-m-d H:i:s", strtotime($calcularfechaVencimiento['fechaFormatoDB']));
 
                # Se consulta el id del user para obtener su nombre
                $emailUsuario = User::findOne(['id' => $request['user_idTramitador']]);
                $modelUserDetalles = UserDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
 
                # Se consulta la información de la configuración realizada para el cliente al que se le esta implementando ORFEO NG
                $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);

                $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);
  
                #Consulta el usuario y tipoDocumento para los clientes que no tienen usuario en la aplicación
                $idUserCiudadano = Yii::$app->params['clientesciudadanosdetalles']['idUser'];
                $idTipoIdentificacionCiudadano = Yii::$app->params['clientesciudadanosdetalles']['idTipoIdentificacion'];
                
                /** Comprueba si el remitente es nuevo */
                if($request['remitentes'][0]['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario'] 
                        && empty($request['remitentes'][0]['idCliente'])){
                    $request['field-validate'] = false;    
                }
               
                /** Comprueba si el radicado esta repetido */
                if(isset($request['field-validate'])){

                    if($request['field-validate'] === true) {
                        $modelRadicados = RadiRadicados::find()->select(['radiRemitentes.idRadiRadicado']);

                        if(isset($request['remitentes'])){

                            $remitente = $request['remitentes'][0];
                
                            $modelRadicados =  HelperQueryDb::getQuery('innerJoin', $modelRadicados, 'radiRemitentes', ['radiRadicados' => 'idRadiRadicado', 'radiRemitentes' => 'idRadiRadicado']);
                            
                            $modelRadicados->andWhere(['radiRemitentes.idTipoPersona' => $remitente['idTipoPersona']]);
                            
                            if($remitente['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']){
                                $modelRadicados->andWhere(['radiRemitentes.idRadiPersona' => $remitente['idCliente']['cliente']]);
                            } else {
                                $modelRadicados->andWhere(['radiRemitentes.idRadiPersona' => $remitente['idCliente']['user']]);
                            }
                            # llama función que actualiza los remitenes
                            $reply = self::updateRemitentes($request);
                            if(isset($reply['validUpdate']) && $reply['validUpdate'] == false){

                                //$transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $reply['model'],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true); 
                            }

                        }
                
                        if(isset($request['idCgTipoRadicado'])){
                            $modelRadicados->andWhere(['radiRadicados.idCgTipoRadicado' => $request['idCgTipoRadicado']]);
                        }                      
                        
                        if(isset($request['idTrdTipoDocumental'])){
                            $modelRadicados->andWhere(['radiRadicados.idTrdTipoDocumental' => $request['idTrdTipoDocumental']]);
                        }
                         
                        $fechaCreacion = date("Y-m-d");
                        $fechaCreacionInicial = $fechaCreacion .Yii::$app->params['timeStart'];
                        $fechaCreacionFinal = $fechaCreacion .Yii::$app->params['timeEnd'];
                        $modelRadicados->andWhere(['between', 'creacionRadiRadicado',  $fechaCreacionInicial,  $fechaCreacionFinal]);                       
                        $modelRadicado = $modelRadicados->all();                  
                        
                        if(!empty($modelRadicado)){

                            if ($request['idCgTipoRadicado']== Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiPqrs'] || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                                $msg = Yii::t('app', 'posibleDuplicateFiling');
                            } else {
                                $msg = Yii::t('app', 'posibleDuplicateFilingTmp');
                            }

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => $msg,
                                'data' => 'duplicate',
                                'status' => 200,
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }            
                  

                /**
                 * Si se activa el boton de unico radicado, genera 1 radicado con muchos remitentes,  
                 * sino realiza el proceso estandar del radicado 
                 **/
                if($request['unicoRadiCgTipoRadicado'] === true){

                    //Inicio de la transacción
                    $transaction = Yii::$app->db->beginTransaction();

                    # Fecha documento
                    $request['fechaDocumentoRadiRadicado'] = explode('T', $request['fechaDocumentoRadiRadicado'])[0];

                    # Funcion que genera y crea un radicado con toda la información seleccionada
                    $dataOneFile = self::oneFile($request, $modelCgNumeroRadicado, $modelDependencia, $requestEmail, $statusFileUpload, $fileUpload);

                    # Se reasigna el modelo del radicado a la variable $model.
                    $model = $dataOneFile['model'];

                    // Si no se genera ningún error en el proceso de radicación se procede a hacer commit del
                    // número de radicación e inserción de cada uno de los radicados creados por el cliente
                    if (count($dataOneFile['errores']) == 0) {

                        # Se realiza las respectivas consultas para mostrarlo en el LOG
                        $infoRadicados = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
                        $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $model->idRadiRadicado]);
                        $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idTramitador]);
                        $usuarioInfoCreador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idCreador]);

                        $nombreRemitente = [];
                        foreach($remitentes as $dataRemitente){

                            if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                                # Se obtiene la información del cliente
                                $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                            
                                $nombreRemitente[] = $modelRemitente->nombreCliente .' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';  

                            } else {
    
                                # Se obtiene la información del usuario o funcionario
                                $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
    
                                $nombreRemitente[] = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles.' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';
                            }
                        }
                       
                        # Nombres de los remitentes
                        $nombresRemitentes = implode(", ", $nombreRemitente);

                        # Prioridad del radicado
                        if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

                        # Data del radicado para el log
                        $dataRadicados = self::dataLog($model, $prioridad, $nombresRemitentes, $infoRadicados);

                        # Informacion de los radicados asociados
                        if(isset($dataOneFile['idAsociado'])){

                            if(count($dataOneFile['idAsociado']) > 0 && count($dataOneFile['numeroAsociado']) > 0){
                                $dataRadicados .= ' Ids radicados asociados: '.implode(', ', $dataOneFile['idAsociado']);
                                $dataRadicados .= ', Radicados asociados: '.implode(', ', $dataOneFile['numeroAsociado']);

                                # Observación de los asociados para el log de radicados
                                $observacionAdicional .= ', Radicado(s) asociado(s): '. implode(", ",$dataOneFile['numeroAsociado']).', ';
                            }
                        }

                        # Información de correos procesados
                        if ($dataOneFile['isRadicacionMail'] == true) {
                            $dataRadicados .= ', ' . $dataOneFile['infoMailProcess'];
                        }

                        $transaction->commit();
                        
                        $numerosRadicados .=  HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado) . ', ';

                        # Observacion para el log
                        $observation = ($dataOneFile['isRadicacionMail'] == true) ? Yii::$app->params['eventosLogText']['NewRadiMail'] : Yii::$app->params['eventosLogText']['crear'] . ', en la tabla radicados';

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            $observation, //texto para almacenar en el evento
                            '',
                            $dataRadicados, //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        # Observación para el radiLog
                        if ($dataOneFile['model']->isRadicado == true || $dataOneFile['model']->isRadicado == 1) {
                            $isRadicado = true;
                            $observationFiling = ($dataOneFile['isRadicacionMail'] == true) ? Yii::$app->params['eventosLogTextRadicado']['NewRadiMail'] : Yii::$app->params['eventosLogTextRadicado']['New'].$observacionAdicional;
                            $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFile'];
                        } else {
                            $isRadicado = false;
                            $observationFiling = ($dataOneFile['isRadicacionMail'] == true) ? Yii::$app->params['eventosLogTextRadicado']['NewRadiMailTmp'] : Yii::$app->params['eventosLogTextRadicado']['NewTmp'].$observacionAdicional;
                            $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFileTmp'];
                        }

                        $observationFiling .= ' con los siguientes datos: Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                        $observationFiling .= ', Dependencia tramitadora: '.$model->idTrdDepeUserTramitador0->nombreGdTrdDependencia;
                        $observationFiling .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;

                        /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $model->idRadiRadicado, //Id radicado
                            $idTransacion->idCgTransaccionRadicado,
                            $observationFiling, //observación
                            $model,
                            array() //No validar estos campos
                        );

                        /***  Notificacion  ***/
                        HelperNotification::addNotification(
                            Yii::$app->user->identity->id, //Id user creador
                            $model->user_idTramitador, // Id user notificado
                            $msgCreateOrReassingFile . HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado), //Notificacion
                            Yii::$app->params['routesFront']['viewRadicado'], // url
                            $model->idRadiRadicado // id radicado
                        );
                        /***  Fin Notificacion  ***/

                    } else {
                        $transaction->rollBack();
                    }

                    $transacciones = [];

                    /**
                     * Al generar un radicado con multiples remitentes, se debe redireccionar por el frontend al formulario de actualización.
                     * SOLO SI, EL ARREGLO DE RADICADOS GUARDADOS ES IGUAL 1, SE RECORRE EL ID PARA DEVOLVERLO AL INDEX-ONE (frontend)
                    **/
                    if (isset($model)) {

                        $idRadiRadicado = str_replace(array('/', '+'), array('_', '-'), base64_encode($model->idRadiRadicado));
                        $data = $model;
                        $formaRadicacion = Yii::$app->params['formFilingNumber']['Estandar'];
                
                        /* Transacciones que se pueden realizar al radicado */
                        # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
                        $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);

                        # Se valida si el usuario logueado es de tipo JEFE
                        $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

                        /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
                        $ignoreTransacions = self::getIgnoreTransactions([], $permissionVoBo, $isUserJefe, $data, 'update');

                        // El botón flotante solo debe ver:  crear,  ver, cargar doc's y stickers **el botón de guardar no aplica para cuando yo no soy el dueño del radicado**
                        //$transaccionesUpdate = Yii::$app->params['transaccionesUpdateRadicado']; // ['view', 'add', 'attachment', 'printStickers'];
                        $radicadoTransacciones = $this::radicadoTransacciones($data['idCgTipoRadicado'], false, $ignoreTransacions, 'update');

                        foreach ($radicadoTransacciones as $value) {
                            $transacciones[] = $value;
                        }
                        /* Transacciones que se pueden realizar al radicado */
                    }


                } else { // PROCESO DE RADICACIÓN ESTÁNDAR

                    if (is_array($request['remitentes']) && $request['remitentes'] != '') {
                        
                        foreach ($request['remitentes'] as $key => $remitente) {
                            
                            //Inicio de la transacción
                            $transaction = Yii::$app->db->beginTransaction();

                            # Fecha documento
                            $request['fechaDocumentoRadiRadicado'] = explode('T', $request['fechaDocumentoRadiRadicado'])[0];

                            $model = new RadiRadicados;

                            if (!isset($request['numeroRadiRadicado'])) {
                                // Se envia a la función de generar número de radicado la información de los modelos que se consultan
                                $estructura = self::generateNumberFiling($request['idCgTipoRadicado'], $modelCgNumeroRadicado, $modelDependencia);
                            }

                            $model->attributes = $request;
                            $model->numeroRadiRadicado = $estructura['numeroRadiRadicado'];

                            # Prioridad del radicado
                            if ($request['PrioridadRadiRadicados'] == true) {$prioridad = 1;} else { $prioridad = 0;}
                            $model->PrioridadRadiRadicados = $prioridad;

                            # Información del creador del radicado
                            $model->user_idCreador = Yii::$app->user->identity->id;
                            $model->idTrdDepeUserCreador = Yii::$app->user->identity->idGdTrdDependencia;

                            // Si el tipo de persona es diferente a funcionario se realiza el proceso de creación del cliente
                            // siempre y cuando este no exista.
                            if ($remitente['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']) {

                                $verificarCliente = ClientesController::verificarCliente($remitente['numeroDocumentoCliente'], $remitente['correoElectronicoCliente'], false);

                                if ($verificarCliente['status'] == 'false') {

                                    # Se valida que si llega "0000" se debe asignar un consecutivo al documento de identidad
                                    if($remitente['numeroDocumentoCliente'] == '0000'){

                                        # Configuración general para obtener el consecutivo a asignar
                                        $configGeneral = ConfiguracionGeneralController::generalConfiguration();
                                        $numeroDocConsecutivo = $configGeneral['data']['iniConsClienteCgGeneral'] + 1;
                                        $numeroDocConsecutivo = str_pad($numeroDocConsecutivo,11,'0', STR_PAD_LEFT);
                                        $remitente['numeroDocumentoCliente'] = (string) $numeroDocConsecutivo;

                                        # Una vez asignado el numero se actualiza el valor de la tabla
                                        $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);
                                        $modelCgGeneral->iniConsClienteCgGeneral = $remitente['numeroDocumentoCliente'];
                                        $modelCgGeneral->save();
                                    }

                                    // Al modelo del cliente se le asigna el valor de lo que llega por POST
                                    $modelcliente = new Clientes;
                                    $modelcliente->attributes = $remitente;

                                    if ($modelcliente->save()) {
                                        $idRemitente = $modelcliente->idCliente;
                                    } else {
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app','errorValidacion'),
                                            'data' => $modelcliente->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }

                                } else {
                                    $idRemitente = $verificarCliente['idCliente'];
                                }
                            } else {
                                if (isset($remitente['idCliente']['user'])) {
                                    $idRemitente = $remitente['idCliente']['user'];
                                }
                            }

                            // Si se guarda correctamente el radicado
                            if ($model->save()) {

                                # Se procede a guardar en la tabla de remitentes la relación del remitente con el radicado
                                $modelRemitentes = new RadiRemitentes();
                                $modelRemitentes->idRadiRadicado = $model->idRadiRadicado;
                                $modelRemitentes->idRadiPersona = $idRemitente;
                                $modelRemitentes->idTipoPersona = $remitente['idTipoPersona'];

                                if (!$modelRemitentes->save()) {
                                    $errores[] = $modelRemitentes->getErrors();
                                } 

                                #valida si es un PQRSD
                                if($request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiPqrs']){

                                    # Observación para el log de radicados
                                    $autorizacion = Yii::$app->params['SiNoNumber'][$model->autorizacionRadiRadicados];

                                    $observacionAdicional .= ', Autorización de envío de correo: '. $autorizacion;

                                    if($request['isNuevoRemitente'] == 1){

                                        $roles = Roles::find()->where([Yii::$app->params['like'],'nombreRol', Yii::$app->params['RolUserPqrs']])->one();

                                        //Generar una password aleatoria
                                        $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
                                
                                        # Configuración general del id dependencia pqrs
                                        $configGeneral = ConfiguracionGeneralController::generalConfiguration();

                                        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                                        if($configGeneral['status']){
                                            $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];

                                        } else {
                                            
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app','errorValidacion'),
                                                'data' => ['error' => [$configGeneral['message']]],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }

                                        $model_user = new User; 
                                        $model_user->username = $modelcliente->numeroDocumentoCliente;
                                        $model_user->email = $modelcliente->correoElectronicoCliente;
                                        $model_user->idRol = $roles['idRol'];
                                        $model_user->idGdTrdDependencia = $idDependenciaUserPqrs;
                                        $model_user->idUserTipo = Yii::$app->params['tipoUsuario']['Externo'];
                                        $model_user->setPassword($password);
                                        $model_user->generateAuthKey();
                                        $model_user->accessToken = $model_user->generateAccessToken();
                                        $model_user->intentos = Yii::$app->params['maxIntentosLogueo'];
                                        $model_user->fechaVenceToken = date("Y-m-d",strtotime(date("Y-m-d")."+ ".Yii::$app->params['TimeExpireToken']." days"));
                                        $model_user->ldap = false;
                                        $model_user->status = Yii::$app->params['statusTodoText']['Activo'];
                                        $model_user->generatePasswordResetToken();

                                        $token = $model_user->password_reset_token; 

                                        if($model_user->save()){

                                            $modelDetalleCiudadano = new ClientesCiudadanosDetalles();
                                            $modelDetalleCiudadano->idCliente = $modelcliente->idCliente;
                                            $modelDetalleCiudadano->idUser =  $model_user->id;
                                            $modelDetalleCiudadano->idTipoIdentificacion = $idTipoIdentificacionCiudadano;
                                            $modelDetalleCiudadano->generoClienteCiudadanoDetalle = $request['generoClienteCiudadanoDetalle'];
                                            $modelDetalleCiudadano->rangoEdadClienteCiudadanoDetalle = $request['rangoEdadClienteCiudadanoDetalle'];
                                            $modelDetalleCiudadano->vulnerabilidadClienteCiudadanoDetalle =$request['vulnerabilidadClienteCiudadanoDetalle'];
                                            $modelDetalleCiudadano->etniaClienteCiudadanoDetalle = $request['etniaClienteCiudadanoDetalle'];
                                            $modelDetalleCiudadano->estadoClienteCiudadanoDetalle = Yii::$app->params['statusTodoText']['Activo'];
                                            $modelDetalleCiudadano->creacionClienteCiudadanoDetalle = date('Y-m-d H:i:s');

                                            if ($remitente['idTipoPersona'] == Yii::$app->params['tipoPersonaText']['personaJuridica']) {
                                                $modelDetalleCiudadano->generoClienteCiudadanoDetalle = Yii::$app->params['generoClienteCiudadanoDetalle'][0]['text']['N/A'];
                                                $modelDetalleCiudadano->rangoEdadClienteCiudadanoDetalle = Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['text']['N/A'];
                                                $modelDetalleCiudadano->vulnerabilidadClienteCiudadanoDetalle = Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['text']['Ninguna'];
                                                $modelDetalleCiudadano->etniaClienteCiudadanoDetalle = Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['text']['Ninguna'];
                                            }

                                            if($modelDetalleCiudadano->save()){                                            
        
                                                $mail = $modelcliente->correoElectronicoCliente;
                                                $mailSubject = Yii::t('app', 'sendCitizenUserSubject');
                                                $textHead = Yii::t('app', 'sendCitizenUserHead');                               
                                                $textBody = Yii::t('app', 'sendCitizenUserBody', [
                                                    'documentoIdentidad' => $modelcliente->numeroDocumentoCliente
                                                ]);
                                                $bodyMail = 'radicacion-html';
                                                $buttonLink = Yii::$app->params['urlBaseApiPublic'] . '/site/reset-password?token=' . $token . '&type=confirm';
                                                $nameButton = Yii::t('app', 'sendCitizenButtonLink');
        
                                                $envioCorreo = CorreoController::sendEmail($mail, $textHead, $textBody, $bodyMail, [], $buttonLink, $mailSubject, $nameButton);

                                            
                                            
                                            } else {
                                                $errores[] = $modelDetalleCiudadano->getErrors();
                                            }

                                        }else{
                                            $errores[] = $model_user->getErrors();
                                        }

                                        # Observación del cliente pqrs para el log de radicados
                                        // $observacionAdicional .= ', Género: '. Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'][$modelDetalleCiudadano->generoClienteCiudadanoDetalle];
                                        // $observacionAdicional .= ', Rango de edad: '.Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'][$modelDetalleCiudadano->rangoEdadClienteCiudadanoDetalle];  
                                        // $observacionAdicional .= ', Vulnerabilidad: '.Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'][$modelDetalleCiudadano->vulnerabilidadClienteCiudadanoDetalle];  
                                        // $observacionAdicional .= ', Etnia: '.Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'][$modelDetalleCiudadano->etniaClienteCiudadanoDetalle]; 

                                    }                          
                                }

                                //Envio de notificación de al cliente
                                if($model->autorizacionRadiRadicados == Yii::$app->params['autorizacionRadiRadicado']['text']['correo']){

                                    # Envio de correo al usuario registrador
                                    $headMailText  = Yii::t('app', 'headMailPqrsRequest',[
                                        'numRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado)
                                    ]);

                                    $textBody  = Yii::t('app', 'textBodyPqrsRequest', [
                                        'numRadicado'  => HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado),
                                        'asunto'       => $model->asuntoRadiRadicado
                                    ]);
                                
                                    // $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelFile->idRadiRadicado));
                                    $bodyMail = 'radicacion-html'; 
                                    $subject = 'subjectPqrsRequest';

                                    $file = [];                               
                                    
                                    $link =  Yii::$app->params['urlBaseApiPublic'] . 'site/login';
                                    $nameButtonLink = Yii::t('app', 'sendCitizenEnter');

                                    // Variable donde se obtiene del número de radicado si es de tipo TMP.
                                    $varTPM = substr($model->numeroRadiRadicado, 0, 3);

                                    // Verificar si el tipo de radicado es distinto de TMP para enviar la notificación por email al destinatario.

                                        // $sendEmailR = CorreoController::sendEmail($request['correoElectronicoCliente'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);

                                        // $envioCorreo = ( $varTPM != 'TMP'  ) ?  $sendEmailR  : '' ;

                                    if( $varTPM != 'TMP'  ){

                                        // Envio de correo al destinatario.
                                        $envioCorreo = CorreoController::sendEmail($request['correoElectronicoCliente'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                                        
                                    }
                                    
                                }
                                
                                # Generacion de pdf en caso de ser una radicacion email
                                if (isset($requestEmail['mailBox']) && isset($requestEmail['dataEmail']) && isset($requestEmail['dataUserEmail'])) {
                                    $isRadicacionMail = true;

                                    $generatePdfEmail = HelperRadicacion::generatePdfEmail($model, $requestEmail['dataEmail'], $requestEmail['dataUserEmail'], $requestEmail['mailBox']);
                                    
                                    if ($generatePdfEmail['status'] == false) {
                                        $errores[] = $generatePdfEmail['errors'];
                                    } else {
                                        $infoMailProcess = $generatePdfEmail['infoMailProcess'];
                                    }
                                }

                                # Se valida si el(los) id(s) del radicado son correctos, para agregarlo a la relacion con el id radicado creado.
                                if (is_array($request['RadiRadicadoHijos']) && $request['RadiRadicadoHijos'] != '' && count($request['RadiRadicadoHijos']) > 0 ) {
                                    $IdRadicadoAsociados = [];
                                    foreach ($request['RadiRadicadoHijos'] as $radiAsociado) {

                                        $idRadiAsociado = $this->verificarRadicado($radiAsociado);

                                        if (isset($idRadiAsociado)) {
                                            $modelRadiAsociado = new RadiRadicadosAsociados();
                                            $modelRadiAsociado->idRadiAsociado = $idRadiAsociado;
                                            $modelRadiAsociado->idRadiCreado = $model->idRadiRadicado;
                                            $modelRadiAsociado->save();
                                            // Para la busqueda de los número de radicados en una sola consulta
                                            $IdRadicadoAsociados[] = $modelRadiAsociado->idRadiAsociado;       
                                        }    
                                    }

                                    /** Información para extraer o relacionar los numeros de radicados asociados al radicado principal **/
                                    $findOneAsociado = RadiRadicados::find()->where(['IN', 'idRadiRadicado', $IdRadicadoAsociados])->all(); 

                                    $idAsociadosAlRadicado = [];
                                    $numerosAsociadosAlRadicado = [];

                                    foreach ($findOneAsociado as $datoAsociados) {
                                        $idAsociadosAlRadicado[] = $datoAsociados->idRadiRadicado;
                                        $numerosAsociadosAlRadicado[] =  HelperConsecutivo::numeroRadicadoXTipoRadicado($datoAsociados->numeroRadiRadicado, $datoAsociados->idCgTipoRadicado, $datoAsociados->isRadicado);
                                    }
                                }

                                // Guarda la actualización del consecutivo del numero de radicado
                                $estructura['modelCgTiposRadicados']->save();

                                /**
                                 * Guardar y asociar el anexo subido
                                 */
                                if ($statusFileUpload == true) {
                                    $saveAnexUpload = self::saveAnexUpload($model, $fileUpload);
                                    if ($saveAnexUpload['status'] == false) {
                                        $transaction->rollBack();
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app', 'errorValidacion'),
                                            'data' => $saveAnexUpload['errors'],
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }
                                }

                            } else {
                                $errores[] = $model->getErrors();
                            }

                            // Si no se genera ningún error en el proceso de radicación se procede a hacer commit del
                            // generación en el número de radicación e inserción de cada uno de los radicados creados por el cliente
                            if (count($errores) == 0) {

                                $infoRadicados = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
                                $remitentes = RadiRemitentes::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
                                $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idTramitador]);
                                $usuarioInfoCreador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idCreador]);
                                
                                if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                                    # Se obtiene la información del cliente
                                    $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);                            
                                    $nombreRemitente = $modelRemitente->nombreCliente .' ('.Yii::$app->params['tipoPersonaNumber'][$remitentes->idTipoPersona].')';

                                } else {

                                    # Se obtiene la información del usuario o funcionario
                                    $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
                                    $nombreRemitente = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles.' ('.Yii::$app->params['tipoPersonaNumber'][$remitentes->idTipoPersona].')';
                                }
                                
                                # Prioridad del radicado
                                if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

                                $dataRadicados = 'Id radicado: '.$model->idRadiRadicado;
                                if ($model->isRadicado == true || $model->isRadicado == 1) {
                                    $dataRadicados .= ', Número radicado: '.HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado);
                                    $dataRadicados .= ', ¿Está radicado?: Si';
                                } else {
                                    $dataRadicados .= ', Número consecutivo: '.HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado);
                                    $dataRadicados .= ', ¿Está radicado?: No';
                                }
                                $dataRadicados .= ', Asunto radicado: '.$model->asuntoRadiRadicado;
                                $dataRadicados .= ', Tipo radicado: '.$model->cgTipoRadicado->nombreCgTipoRadicado;
                                $dataRadicados .= ', Tipo documental: '.$model->trdTipoDocumental->nombreTipoDocumental;
                                $dataRadicados .= ', Prioridad: '.$prioridad;
                                $dataRadicados .= ', Remitente/Destinatario: '.$nombreRemitente;
                                $dataRadicados .= ', Radicado origen: '.$model->radicadoOrigen;
                                $dataRadicados .= ', Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                                $dataRadicados .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;
                                $dataRadicados .= ', Conteo de correos del radicado: '.$infoRadicados->cantidadCorreosRadicado;
                                $dataRadicados .= ', Fecha creación: '.$infoRadicados->creacionRadiRadicado;
                                $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$infoRadicados->estadoRadiRadicado];
                                $dataRadicados .= ', Fecha documento: '.$model->fechaDocumentoRadiRadicado;
                                $dataRadicados .= ', Descripción del anexo: '.$model->descripcionAnexoRadiRadicado;
                                $dataRadicados .= ', Observación: '.$model->observacionRadiRadicado;
                                if ($model->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                                    $dataRadicados .= ', Número de factura: '.$model->numeroFactura;
                                    $dataRadicados .= ', Número de contrato: '.$model->numeroContrato;
                                    $dataRadicados .= ', Valor factura: '.$model->valorFactura;
                                }

                                if(isset($idAsociadosAlRadicado)){
                                    if(count($idAsociadosAlRadicado) > 0 && count($numerosAsociadosAlRadicado) > 0){
                                        $dataRadicados .= ', Ids radicados asociados: '.implode(', ', $idAsociadosAlRadicado);
                                        $dataRadicados .= ', Radicados asociados: '.implode(', ', $numerosAsociadosAlRadicado);

                                        # Observación de los asociados para el log de radicados
                                        $observacionAdicional .= ', Radicado(s) asociado(s): '. implode(", ",$numerosAsociadosAlRadicado).', ';
                                    }
                                }

                                # Información de correos procesados
                                if ($isRadicacionMail == true) {
                                    $dataRadicados .= ', ' . $infoMailProcess;
                                }

                                $transaction->commit();

                                $radicados[] = $model;
                                $numerosRadicados .= HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado) . ', ';

                                $observation = ($isRadicacionMail == true) ? Yii::$app->params['eventosLogText']['NewRadiMail'] : Yii::$app->params['eventosLogText']['crear'] . ', en la tabla radicados';

                                /***    Log de Auditoria  ***/
                                HelperLog::logAdd(
                                    true,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    $observation, //texto para almacenar en el evento
                                    '',
                                    $dataRadicados, //Data
                                    array() //No validar estos campos
                                );
                                /***    Fin log Auditoria   ***/

                                # Observación para el radiLog
                                if ($model->isRadicado == true || $model->isRadicado == 1) {
                                    $isRadicado = true;
                                    $observationFiling = ($isRadicacionMail == true) ? Yii::$app->params['eventosLogTextRadicado']['NewRadiMail'] : Yii::$app->params['eventosLogTextRadicado']['New'].$observacionAdicional;
                                    $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFile'];
                                } else {
                                    $isRadicado = false;
                                    $observationFiling = ($isRadicacionMail == true) ? Yii::$app->params['eventosLogTextRadicado']['NewRadiMailTmp'] : Yii::$app->params['eventosLogTextRadicado']['NewTmp'].$observacionAdicional;
                                    $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFileTmp'];
                                }
                                $observationFiling .= ' con los siguientes datos: Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                                $observationFiling .= ', Dependencia tramitadora: '.$model->idTrdDepeUserTramitador0->nombreGdTrdDependencia;
                                $observationFiling .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;

                                /***    Log de Radicados  ***/
                                HelperLog::logAddFiling(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $model->idRadiRadicado, //Id radicado
                                    $idTransacion->idCgTransaccionRadicado,
                                    $observationFiling, //observación
                                    $model,
                                    array() //No validar estos campos
                                );

                                /***  Notificacion  ***/
                                HelperNotification::addNotification(
                                    Yii::$app->user->identity->id, //Id user creador
                                    $model->user_idTramitador, // Id user notificado
                                    $msgCreateOrReassingFile . HelperConsecutivo::numeroRadicadoXTipoRadicado($model->numeroRadiRadicado, $model->idCgTipoRadicado, $model->isRadicado), //Notificacion
                                    Yii::$app->params['routesFront']['viewRadicado'], // url
                                    $model->idRadiRadicado // id radicado
                                );
                                /***  Fin Notificacion  ***/

                            } else {
                                $transaction->rollBack();
                            }
                        }
                    
                    }

                    $transacciones = [];

                    /**
                     * Si se identifica que solo se genero un radicado, se clasifica como radicación estandar, porque solo se
                     * selecciono un remitente lo que quiere decir que se debe direccinar por el frontend al formulario de actualización.
                     * SOLO SI, EL ARREGLO DE RADICADOS GUARDADOS ES IGUAL 1, SE RECORRE EL ID PARA DEVOLVERLO AL INDEX-ONE (frontend)
                     */
                    if (count($radicados) == 1) {

                        /** Se hace el foreach por si el tipo de radicado es entrada, ya que solo en ese caso se debe notificar 
                         * por correo electrónico al usuario tramitador sobre el radicado que se le asigno
                         */
                        foreach ($radicados as $keyradi => $infoRadicado) {

                            /**
                             * Si el radicado que se esta creando es de entrada se envia notificación al usuario responsable
                             * si el radicado es diferente quiere decir que el usuario logueado es el responsable y no se debe
                             * enviar la notificación de correo electrónico.
                             */
                            if ($request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {

                                $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRadicado->idRadiRadicado));

                                // Envia la notificación de correo electronico al usuario de tramitar
                                $headMailText = Yii::t('app', 'headMailTextRadicado', [
                                    'numRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($infoRadicado->numeroRadiRadicado, $infoRadicado->idCgTipoRadicado, $infoRadicado->isRadicado),
                                ]);

                                $textBody = Yii::t('app', 'textBodyAsignacionRadicado', [
                                    'numRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($infoRadicado->numeroRadiRadicado, $infoRadicado->idCgTipoRadicado, $infoRadicado->isRadicado),
                                    'asunto' => $infoRadicado->asuntoRadiRadicado,
                                    'user' => $modelUserDetalles->nombreUserDetalles . ' ' . $modelUserDetalles->apellidoUserDetalles,
                                    'nameDependencia' => $modelDependencia->nombreGdTrdDependencia,
                                    'dias' => $calcularfechaVencimiento['diasRestantes'],
                                ]);

                                $bodyMail = 'radicacion-html';
                                $envioCorreo = CorreoController::radicacion($emailUsuario->email, $headMailText, $textBody, $bodyMail, $dataBase64Params);                      
                            }

                            $idRadiRadicado = str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRadicado['idRadiRadicado']));
                            $data = $infoRadicado;
                            $formaRadicacion = Yii::$app->params['formFilingNumber']['Estandar'];

                        }

                        /* Transacciones que se pueden realizar al radicado */
                        # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
                        $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);
                        # Se valida si el usuario logueado es de tipo JEFE
                        $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

                        /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
                        $ignoreTransacions = self::getIgnoreTransactions([], $permissionVoBo, $isUserJefe, $data, 'update');

                        // El botón flotante solo debe ver:  crear,  ver, cargar doc's y stickers **el botón de guardar no aplica para cuando yo no soy el dueño del radicado**
                        //$transaccionesUpdate = Yii::$app->params['transaccionesUpdateRadicado']; // ['view', 'add', 'attachment', 'printStickers'];
                        $radicadoTransacciones = $this::radicadoTransacciones($data['idCgTipoRadicado'], false, $ignoreTransacions, 'update');

                        foreach ($radicadoTransacciones as $value) {
                            $transacciones[] = $value;
                        }
                        /* Transacciones que se pueden realizar al radicado */
                    }
                    /** 
                    * Si llega más de una posición en el modelo de los radicados guardados, quiere decir que es radicación masiva 
                    * porque llegó más de un remitente, lo que quiere decir que se debe direccinar por el frontend al Index
                    * SI, EL ARREGLO DE RADICADOS GUARDADOS ES MAYOR A 1, SE DEVUELVE AL INDEX (frontend) 
                    */
                    elseif(count($radicados) > 1) {
                        $idRadiRadicado = '';
                        $data = [];
                        $formaRadicacion = Yii::$app->params['formFilingNumber']['Masiva'];;
                    }
                }  

                # Envio de data y errores
                if(count($errores) == 0){

                    if ($isRadicado == true) {
                        $message = Yii::t('app', 'successSave') . ' ' . Yii::t('app', 'NoRadi') . $numerosRadicados;
                    } else {
                        $message = Yii::t('app', 'successSave') . ' ' . Yii::t('app', 'NoRadiTmp') . $numerosRadicados;
                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $message,
                        'idRadiRadicado' => $idRadiRadicado,
                        'data' => $data,
                        'dataTransacciones' => $transacciones,
                        'formaRadicacion' => $formaRadicacion,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    // $new_error Variable que contien el array de los errores
                    $new_error = [];
                    // Se recorren los $errores para que los muestre correctamente en el formulario ya que estan enviando arrays dentro de arrays
                    foreach ($errores as $key => $arr) {
                        // Verifica si es un array
                        if( is_array($arr) ){
                            // Recorre el array
                            foreach ($arr as $key => $value) {
                                // Asigna el valor dentro de un array pero en uno solo
                                $new_error[] = $value;    
                            }
                        }else{
                            $new_error[] = $arr;
                        }
                    }

                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $new_error,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }            

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Updates an existing RadiRadicados model.
     * @param integer $idRadiRadicado
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');            

            if (!empty($jsonSend)) {

                //*** Inicio desencriptación POST, PUT ***//
                $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];

                } else {
                    Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                    $response = [
                        'message' => Yii::t('app', 'errorDesencriptacion'),
                        'data' => Yii::t('app', 'errorDesencriptacion'),
                        'status' => Yii::$app->params['statusErrorEncrypt'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //*** Fin desencriptación POST, PUT ***//

                $id = $request['id']; 
                
                //Cambia el valor true - false de autorización a 0 - 10
                if(isset($request['autorizacionRadiRadicados'])){
                    if($request['autorizacionRadiRadicados']){
                        $request['autorizacionRadiRadicados'] = Yii::$app->params['autorizacionRadiRadicado']['text']['correo'];
                    }else {
                        $request['autorizacionRadiRadicados'] = Yii::$app->params['autorizacionRadiRadicado']['text']['fisico'];
                    }
                }else {
                    $request['autorizacionRadiRadicados'] = null;
                }  

                # Transaccion de actualizar
                $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'edit']);
                $idTransaction = $modelTransaction->idCgTransaccionRadicado;

                $modelCgTiposRadicados = CgTiposRadicados::findOne(['idCgTipoRadicado' => $request['idCgTipoRadicado']]);
                $calcularfechaVencimiento = self::calcularfechaVencimiento($request['idTrdTipoDocumental'],$request['diasRestantes']);  
                $request['fechaVencimientoRadiRadicados'] = date("Y-m-d H:i:s",strtotime($calcularfechaVencimiento['fechaFormatoDB']));

                # Fecha documento
                if (!is_null($request['fechaDocumentoRadiRadicado'])){
                    $request['fechaDocumentoRadiRadicado'] = explode('T', $request['fechaDocumentoRadiRadicado'])[0];
                }                

                $transaction = Yii::$app->db->beginTransaction();
               
                $model = $this->findModel($id);

                # Información del usuario logueado
                $modelUser = User::findOne(['id' => Yii::$app->user->identity->id]);
                
                $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $model->user_idTramitador]);
                $usuarioInfoCreador = UserDetalles::findOne(['idUser'=> $model->user_idCreador]);

                # Informacion del remitente para el log old
                $senderOld = RadiRemitentes::findAll(['idRadiRadicado' => $model->idRadiRadicado]);

                $senderName = []; // Array de los nombres de remitentes
                foreach($senderOld as $dataSenderOld){

                    if($dataSenderOld->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                        # Se obtiene la información del cliente
                        $modelRemitente = Clientes::findOne(['idCliente' => $dataSenderOld->idRadiPersona]);                            
                        $senderName[] = $modelRemitente->nombreCliente .' ('.Yii::$app->params['tipoPersonaNumber'][$dataSenderOld->idTipoPersona].')';
    
                    } else {    
                        # Se obtiene la información del usuario o funcionario
                        $modelRemitente = User::findOne(['id' => $dataSenderOld->idRadiPersona]);

                        $senderName[] = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles.' ('.Yii::$app->params['tipoPersonaNumber'][$dataSenderOld->idTipoPersona].')';
                    }
                }

                $nombresRemitentes = implode(", ",$senderName);     
                            
                # Prioridad del radicado para el log old
                if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

                //Campos antiguos
                $idTrdTipoDocumentalOld = $model->idTrdTipoDocumental;
                $fechaVencimientoRadiRadicadosOld = $model->fechaVencimientoRadiRadicados;
                $radicadoOrigenOld = $model->radicadoOrigen;
                //$numeroRadiRadicadoOld = $model->numeroRadiRadicado;
                $asuntoRadiRadicadoOld = $model->asuntoRadiRadicado;
                $descripcionAnexoRadiRadicadoOld = $model->descripcionAnexoRadiRadicado;
                $foliosRadiRadicadoOld = $model->foliosRadiRadicado;
                //$estadoRadiRadicadoOld =  $model->estadoRadiRadicado;
                $idTrdDepeUserTramitadorOld = $model->idTrdDepeUserTramitador;
                $user_idTramitadorOld = $model->user_idTramitador;
                $PrioridadRadiRadicadosOld = $model->PrioridadRadiRadicados;
                $idCgTipoRadicadoOld = $model->idCgTipoRadicado;
                $idCgMedioRecepcionOld = $model->idCgMedioRecepcion;
                //$cantidadCorreosRadicadoOld = $model->cantidadCorreosRadicado;
                $observacionRadiRadicadoOld = $model->observacionRadiRadicado;
                $autorizacionRadiRadicadosOld = $model->autorizacionRadiRadicados;

                $numeroFacturaOld  = $model->numeroFactura;
                $numeroContratoOld = $model->numeroContrato;
                $valorFacturaOld   = $model->valorFactura;

                # Data anterior
                $dataRadicadosOld = 'Id radicado: '.$model->idRadiRadicado;
                if ((boolean) $model->isRadicado == true) {
                    $dataRadicadosOld .= ', Número radicado: '.$model->numeroRadiRadicado;
                    $dataRadicadosOld .= ', Asunto radicado: '.$model->asuntoRadiRadicado;
                } else {
                    $dataRadicadosOld .= ', Número de consecutivo temporal: '.$model->numeroRadiRadicado;
                    $dataRadicadosOld .= ', Asunto: '.$model->asuntoRadiRadicado;
                }
                $dataRadicadosOld .= ', Tipo radicado: '.$model->cgTipoRadicado->nombreCgTipoRadicado;
                $dataRadicadosOld .= ', Tipo documental: '.$model->trdTipoDocumental->nombreTipoDocumental;
                $dataRadicadosOld .= ', Prioridad: '.$prioridad;
                $dataRadicadosOld .= ', Remitente/Destinatario: '.$nombresRemitentes;
                $dataRadicadosOld .= ', Radicado origen: '.$model->radicadoOrigen;
                $dataRadicadosOld .= ', Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                $dataRadicadosOld .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;
                $dataRadicadosOld .= ', Conteo de correos del radicado: '.$model->cantidadCorreosRadicado;
                $dataRadicadosOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$model->estadoRadiRadicado];
                $dataRadicadosOld .= ', Fecha documento: '.$model->fechaDocumentoRadiRadicado;
                $dataRadicadosOld .= ', Descripción del anexo: '.$model->descripcionAnexoRadiRadicado;
                $dataRadicadosOld .= ', Observación: '.$model->observacionRadiRadicado;
                if ($model->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                    $dataRadicadosOld .= ', Número de factura: '.$model->numeroFactura;
                    $dataRadicadosOld .= ', Número de contrato: '.$model->numeroContrato;
                    $dataRadicadosOld .= ', Valor factura: '.$model->valorFactura;
                }


                # Si el tipo de usuario es 'Administrador de Gestión Documental', el nivel de busqueda es diferente a BÁSICO
                # y los tipos de radicados son ENTRADA o PQRSD, puede actualizar la data. 
                if($model->user_idTramitador === Yii::$app->user->identity->id or $modelUser->idUserTipo === Yii::$app->params['tipoUsuario']['Ventanilla de Radicación']) {

                    # Función que actualiza la data del radicado.
                    $reply = self::filedEntryAndPqrsd($request, $model, $transaction, $modelCgTiposRadicados, $dataRadicadosOld, $idTransaction);

                    if($reply['validUpdate']){

                        $dataVencimiento = [];
                        if(strtotime($fechaVencimientoRadiRadicadosOld) != strtotime($request['fechaVencimientoRadiRadicados'])){
                            $dataVencimiento = Yii::t('app', 'changeExpiration', [
                                'fecha' => $request['fechaVencimientoRadiRadicados']
                            ]);                        
                        }

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successUpdate'),
                            'data' => $reply['model'],
                            'dataTransacciones' => $reply['transacciones'],
                            'dataVencimiento' => $dataVencimiento,
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $reply['model'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }                    

                } else { //Cuando no cumple con la condición inicial, genera error

                    $validPermission = true;
                }                
                #------------------- FIN de validación de radicado ENTRADA y PQRSD ---------------------#


                # Si el tipo de radicado es DIFERENTE a ENTRADA Y PQRSD, puede actualizarlo cualquier usuario que tenga permisos.
                if( $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiEntrada'] 
                   && $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiFactura']
                   && $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiPqrs'] ) 
                {

                    # Función que actualiza la data del radicado.
                    $reply = self::differentsfiles($request, $model, $transaction, $modelCgTiposRadicados, $dataRadicadosOld, $idTransaction);

                    if($reply['validUpdate']){

                        $dataVencimiento = [];
                        if(strtotime($fechaVencimientoRadiRadicadosOld) != strtotime($request['fechaVencimientoRadiRadicados'])){
                            $dataVencimiento = Yii::t('app', 'changeExpiration', [
                                'fecha' => $request['fechaVencimientoRadiRadicados']
                            ]);                        
                        }

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successUpdate'),
                            'data' => $reply['model'],
                            'dataTransacciones' => $reply['transacciones'],
                            'dataVencimiento' => $dataVencimiento,
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $reply['model'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }      
                }

                # Si el usuario es el tramitador, el tipo de radicado es IGUAL a ENTRADA Y PQRSD y el campo idTrdTipoDocumental antiguo es igual 0
                $userLogin = Yii::$app->user->identity->id;
                if($request['user_idTramitador'] == $userLogin
                        && $idTrdTipoDocumentalOld === 0
                        && ($request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] 
                        || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiFactura']
                        || $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiPqrs'])
                ){                   

                    //Si se han hecho más modificaciones además de del tipo documental
                    if($request['radicadoOrigen'] != $radicadoOrigenOld
                            //|| $request['numeroRadiRadicado'] != $numeroRadiRadicadoOld
                            || $request['asuntoRadiRadicado'] != $asuntoRadiRadicadoOld
                            || $request['descripcionAnexoRadiRadicado'] != $descripcionAnexoRadiRadicadoOld
                            || $request['foliosRadiRadicado'] != $foliosRadiRadicadoOld
                            //|| $request['estadoRadiRadicado'] != $estadoRadiRadicadoOld
                            || $request['idTrdDepeUserTramitador'] != $idTrdDepeUserTramitadorOld
                            || $request['user_idTramitador'] != $user_idTramitadorOld
                            || $request['PrioridadRadiRadicados'] != $PrioridadRadiRadicadosOld   
                            || $request['idCgTipoRadicado'] != $idCgTipoRadicadoOld
                            || $request['idCgMedioRecepcion'] != $idCgMedioRecepcionOld
                            //|| $request['cantidadCorreosRadicado'] != $cantidadCorreosRadicadoOld
                            || $request['observacionRadiRadicado'] != $observacionRadiRadicadoOld
                            || $request['autorizacionRadiRadicados'] != $autorizacionRadiRadicadosOld
                            || $request['autorizacionRadiRadicados'] != $autorizacionRadiRadicadosOld

                            || $request['numeroFactura']  != $numeroFacturaOld
                            || $request['numeroContrato'] != $numeroContratoOld
                            || $request['valorFactura']   != $valorFacturaOld
                    ){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'onlyUpdateDocumentType')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Función que actualiza la data del radicado.
                    $reply = self::differentsfiles($request, $model, $transaction, $modelCgTiposRadicados, $dataRadicadosOld, $idTransaction);

                    if($reply['validUpdate']){                      

                        $dataVencimiento = [];
                        if(strtotime($fechaVencimientoRadiRadicadosOld) != strtotime($request['fechaVencimientoRadiRadicados'])){
                            $dataVencimiento = Yii::t('app', 'changeExpiration', [
                                'fecha' => $request['fechaVencimientoRadiRadicados']
                            ]);                        
                        }

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successUpdate'),
                            'data' => $reply['model'],
                            'dataTransacciones' => $reply['transacciones'],
                            'dataVencimiento' => $dataVencimiento,
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $reply['model'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }      
                }

                #---------------- FIN de validación de radicado diferentes a ENTRADA y PQRSD ---------------#


                # Validación de permisos para el usuario que desea actualizar un radicado tipo ENTRADA o PQRS
                if($validPermission){

                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'accesoDenegado')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }                

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Función que genera el consecutivo del radicado según la configuración del sistema
     * @param $modelCgTiposRadicados [Modelo del tipo de radicado]
     * @param $modelDependencia [Modelo de la dependencia del radicado]
     * @return [array con modelo del consecutivo del radicado]
     **/
    public static function getConsecutivoRadicado($modelCgTiposRadicados, $modelDependencia, $isRadicado)
    {
        $anioActual = date('Y');

        switch (Yii::$app->params['tipoConsecutivoRadicado']) {
            case 'tipoRad':
                $idCgTipoRadicado = $modelCgTiposRadicados->idCgTipoRadicado;
                $idCgRegional     = null;
            break;
            case 'tipoRad&regional':
                $idCgTipoRadicado = $modelCgTiposRadicados->idCgTipoRadicado;
                $idCgRegional     = $modelDependencia->cgRegional->idCgRegional;
            break;
        }

        if ($isRadicado == true) {
            $isTemporal = 0;
        } else {
            $isTemporal = 1;
            $idCgTipoRadicado = null;
            $idCgRegional = null;
        }

        $modelCgConsecutivosRadicados = CgConsecutivosRadicados::find()
            ->where([
                'isTemporal'                => $isTemporal,
                'idCgTipoRadicado'          => $idCgTipoRadicado,
                'idCgRegional'              => $idCgRegional,
                'anioCgConsecutivoRadicado' => $anioActual,
            ])->one();

        if ($modelCgConsecutivosRadicados == null) {
            $modelCgConsecutivosRadicados = new CgConsecutivosRadicados;
            $modelCgConsecutivosRadicados->isTemporal                = $isTemporal;
            $modelCgConsecutivosRadicados->idCgTipoRadicado          = $idCgTipoRadicado;
            $modelCgConsecutivosRadicados->idCgRegional              = $idCgRegional;
            $modelCgConsecutivosRadicados->anioCgConsecutivoRadicado = $anioActual;
            $modelCgConsecutivosRadicados->cgConsecutivoRadicado     = 1;
        } else {
            $modelCgConsecutivosRadicados->cgConsecutivoRadicado = $modelCgConsecutivosRadicados->cgConsecutivoRadicado + 1;
        }

        return [
            'status' => true,
            'modelCgConsecutivosRadicados' => $modelCgConsecutivosRadicados
        ];
    }

    /**
     * Función para generar el número de radicado correspondiente al tipo de radicado que se esta recibiendo en el formulario
     * @param $tipoRadicado
     * @param $modelCgNumeroRadicado
     * @param $modelDependencia
     * @param $isRadicado [boolean] true: Indica si se generará un número de radicado definitivo, false: Indica si se generará un número de radicado temporal
     */
    public static function generateNumberFiling($tipoRadicado, $modelCgNumeroRadicado, $modelDependencia, $isRadicado = false)
    {
        
        $modelCgTiposRadicados = CgTiposRadicados::findOne(['idCgTipoRadicado' => $tipoRadicado]);

        if(!is_null($modelCgTiposRadicados)){

            if (Yii::$app->params['activateRadiTmp'] == false || $tipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $tipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs'] || $tipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                $isRadicado = true;
            }

            $getConsecutivoRadicado = self::getConsecutivoRadicado($modelCgTiposRadicados, $modelDependencia, $isRadicado);
            $modelCgConsecutivosRadicados = $getConsecutivoRadicado['modelCgConsecutivosRadicados'];

            $consecutivoRadi = $modelCgConsecutivosRadicados->cgConsecutivoRadicado;

            $estructura = explode('-', Yii::$app->params['estructuraRadicado'][$modelCgNumeroRadicado->estructuraCgNumeroRadicado]);

            $numeroRadiRadicado = '';
            $separador = Yii::$app->params['separadorEstructuraRadicado'];

            if ($isRadicado == false) {
                $consecutivoRadicado = str_pad($consecutivoRadi, "9", "0", STR_PAD_LEFT);
                $numeroRadiRadicado = 'TMP' . $separador . date('Y') . $separador . $consecutivoRadicado . $separador;
            } else {

                $consecutivoRadicado = str_pad($consecutivoRadi, $modelCgNumeroRadicado->longitudConsecutivoCgNumeroRadicado, "0", STR_PAD_LEFT);

                foreach($estructura as $item){
                    switch($item){
                        case 'yyyy':
                            $numeroRadiRadicado .= date('Y') . $separador;
                        break;

                        case 'yyyymm':
                            $numeroRadiRadicado .= date('Ym') . $separador;
                        break;

                        case 'yyyymmdd':
                            $numeroRadiRadicado .= date('Ymd') . $separador;
                        break;

                        case 'depe':
                            $numeroRadiRadicado .= $modelDependencia->codigoGdTrdDependencia . $separador;
                        break;
                        
                        case 'consecutivo':
                            $numeroRadiRadicado .=  $consecutivoRadicado . $separador;
                        break;

                        case 'tiporad':
                            $numeroRadiRadicado .= $modelCgTiposRadicados->codigoCgTipoRadicado . $separador;
                        break;

                        case 'regional':
                            $numeroRadiRadicado .= $modelDependencia->cgRegional->siglaCgRegional . $separador;
                        break;
                    }
                }
            }


            if ($separador != '') {
                $longitudSeparador = 0 - strlen($separador);
                $numeroRadiRadicado = substr($numeroRadiRadicado, 0, $longitudSeparador);
            }

            # Se (actualiza/crea) el consecutivo
            $modelCgConsecutivosRadicados->save();

            return [
                'modelCgTiposRadicados' => $modelCgTiposRadicados,
                'numeroRadiRadicado' => $numeroRadiRadicado,
            ];
        }
    }

    /**
     * Función que genera un único número de radicado para los tipos de radicado DIFERENTES a (ENTRADA, SALIDA Y PQRS)
     * @param $request [Array de la data de $request]
     * @param $modelCgNumeroRadicado [Modelo de la configuración del número radicado]
     * @param $requestEmail [Array de la configuración de radicacion email]
     * @return [Data que incluye los modelos, variables o errores del proceso]
     **/
    public static function oneFile($request, $modelCgNumeroRadicado, $modelDependencia, $requestEmail, $statusFileUpload, $fileUpload){

        $errores = [];
        $isRadicacionMail = false;

        if (!isset($request['numeroRadiRadicado'])) {
            // Se envia a la función de generar número de radicado la información de los modelos que se consultan
            $estructura = self::generateNumberFiling($request['idCgTipoRadicado'], $modelCgNumeroRadicado, $modelDependencia);
        }

        $model = new RadiRadicados;
        $model->attributes = $request;

        # Numero radicado unico
        $model->numeroRadiRadicado = $estructura['numeroRadiRadicado'];

        # Prioridad del radicado
        if ($request['PrioridadRadiRadicados'] == true) {$prioridad = 1;} else { $prioridad = 0;}
        $model->PrioridadRadiRadicados = $prioridad;

        # Informacion del creador
        $model->user_idCreador = Yii::$app->user->identity->id;
        $model->idTrdDepeUserCreador = Yii::$app->user->identity->idGdTrdDependencia;

        # Validación de la información del remitente/destinatario
        $dataRemitente = [];
        if (is_array($request['remitentes']) && $request['remitentes'] != '') {  
            
            foreach ($request['remitentes'] as $key => $remitente) {

                // Si el tipo de persona es diferente a funcionario se realiza el proceso de creación del cliente
                // siempre y cuando este no exista.
                if ($remitente['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']) {

                    $verificarCliente = ClientesController::verificarCliente($remitente['numeroDocumentoCliente'], $remitente['correoElectronicoCliente']);

                    if ($verificarCliente['status'] == 'false') {

                        // Al modelo del cliente se le asigna el valor de lo que llega por POST
                        $modelcliente = new Clientes;
                        $modelcliente->attributes = $remitente;

                        if ($modelcliente->save()) {
                            $dataRemitente[] = [
                                'idRemitente' => $modelcliente->idCliente,
                                'idTypePerson' => $remitente['idTipoPersona']
                            ];
                        } else {
                            $errores[] = $modelcliente->getErrors();
                        } 

                    } else {
                        $dataRemitente[] = [
                            'idRemitente' => $verificarCliente['idCliente'],
                            'idTypePerson' => $remitente['idTipoPersona']
                        ];
                    }

                } else {
                    if (isset($remitente['idCliente']['user'])) {
                        $dataRemitente[] = [
                            'idRemitente' => $remitente['idCliente']['user'],
                            'idTypePerson' => $remitente['idTipoPersona']
                        ];
                    }
                }
            }
        }

        // Si se guarda correctamente el radicado
        if ($model->save()) {
            
            $modeloRemitentes = [];            
            foreach($dataRemitente as $data) {

                # Se procede a guardar en la tabla de remitentes la relación del remitente con el radicado
                $modelRemitentes = new RadiRemitentes();
                $modelRemitentes->idRadiRadicado = $model->idRadiRadicado;
                $modelRemitentes->idRadiPersona = $data['idRemitente'];
                $modelRemitentes->idTipoPersona = $data['idTypePerson'];

                if (!$modelRemitentes->save()) {
                    $errores = array_merge($errores, $modelRemitentes->getErrors());
                    break;
                } 

                $modeloRemitentes[] = $modelRemitentes;
            }     
                  
            # Generacion de pdf en caso de ser una radicacion email
            $infoMailProcess = '';
            if (isset($requestEmail['mailBox']) && isset($requestEmail['dataEmail']) && isset($requestEmail['dataUserEmail'])) {
                $isRadicacionMail = true;

                $generatePdfEmail = HelperRadicacion::generatePdfEmail($model, $requestEmail['dataEmail'], $requestEmail['dataUserEmail'], $requestEmail['mailBox']);            
                
                if ($generatePdfEmail['status'] == false) {
                    $errores[] = $generatePdfEmail['errors'];
                } else {
                    $infoMailProcess = $generatePdfEmail['infoMailProcess'];
                }
            }

            /**
             * Guardar y asociar el anexo subido
             */
            if ($statusFileUpload == true) {
                $saveAnexUpload = self::saveAnexUpload($model, $fileUpload);
                if ($saveAnexUpload['status'] == false) {
                    $errores[] = $saveAnexUpload['errors'];
                }
            }

            # Se valida si el(los) id(s) del radicado son correctos, para agregarlo a la relacion con el id radicado creado.
            $idAsociadosAlRadicado = [];
            $numerosAsociadosAlRadicado = [];
            
            if (is_array($request['RadiRadicadoHijos']) && $request['RadiRadicadoHijos'] != '' && count($request['RadiRadicadoHijos']) > 0 ) {

                $IdRadicadoAsociados = [];
                foreach ($request['RadiRadicadoHijos'] as $radiAsociado) {

                    $idRadiAsociado = self::verificarRadicado($radiAsociado);

                    if (isset($idRadiAsociado)) {
                        $modelRadiAsociado = new RadiRadicadosAsociados();
                        $modelRadiAsociado->idRadiAsociado = $idRadiAsociado;
                        $modelRadiAsociado->idRadiCreado = $model->idRadiRadicado;
                        $modelRadiAsociado->save();
                        // Para la busqueda de los número de radicados en una sola consulta
                        $IdRadicadoAsociados[] = $modelRadiAsociado->idRadiAsociado;       
                    }    
                }

                /** Información para extraer o relacionar los numeros de radicados asociados al radicado principal **/
                $findOneAsociado = RadiRadicados::find()->where(['IN', 'idRadiRadicado', $IdRadicadoAsociados])->all(); 

                foreach ($findOneAsociado as $datoAsociados) {
                    $idAsociadosAlRadicado[] = $datoAsociados->idRadiRadicado;
                    $numerosAsociadosAlRadicado[] = $datoAsociados->numeroRadiRadicado;
                }   
            }

            // Guarda la actualización del consecutivo del numero de radicado
            $estructura['modelCgTiposRadicados']->save();

        } else {
            $errores[] = $model->getErrors();
        } 

        return [
            'model' => $model,
            'remitente' => $modeloRemitentes,
            'errores' => $errores,
            'estructura' => $estructura,
            'idAsociado' => $idAsociadosAlRadicado,
            'numeroAsociado' => $numerosAsociadosAlRadicado,
            'isRadicacionMail' => $isRadicacionMail,
            'infoMailProcess' => $infoMailProcess
        ];        

    }


    /**
     * Función que actualiza los radicados de (ENTRADA Y PQRS), cuando sea de tipo
     * 'Administrador de Gestion documental y el nivel de busqueda diferente a básico
     * @param $request [Array de la data de $request]
     * @param $model [Modelo del radicado]
     * @param $transaction [Inicialización de la transacción]
     * @param $modelCgTiposRadicados [Modelo del tipo de radicado]
     * @param $dataRadicadosOld [Información anterior para el log]
     * @param $idTransaction [Id de la transaccion de actualizar radicado]
     * @return [Data que incluye el modelo del radicado y la variable de validación]
     **/
    public static function filedEntryAndPqrsd($request, $model, $transaction, $modelCgTiposRadicados, $dataRadicadosOld, $idTransaction){

        $validRemitente = false;    //Validación del remitente actualizado
        $observacionAdicional = '';  //Observacion adicional del log radicados

        # Actualización del radicado
        $model->attributes = $request;

        # Data del creador
        $model->user_idCreador = Yii::$app->user->identity->id;
        $model->idTrdDepeUserCreador = Yii::$app->user->identity->idGdTrdDependencia;

        # Prioridad del radicado
        if($request['PrioridadRadiRadicados'] == true) { $prioridad = 1; } else { $prioridad = 0; }
        $model->PrioridadRadiRadicados = $prioridad;


        if (is_array($request['remitentes']) && $request['remitentes'] != '') {

            $dataSave = []; //Array de id del remitente y tipo persona
            
            foreach ($request['remitentes'] as $dataSender) {

                // Si el tipo de persona es diferente a funcionario se realiza el proceso de creación del cliente
                // siempre y cuando este no exista.
                if($dataSender['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']){

                    // Se valida el correo y el documento que llegan por el formulario de radicación y si no existen se procede
                    // a validar si llega el campo id, para que asi se pueda buscar el cliente y poder actualizarlo, solo aplica
                    // para los clientes.
                    $verificarCliente = ClientesController::verificarCliente($dataSender['numeroDocumentoCliente'], $dataSender['correoElectronicoCliente']);

                    if($verificarCliente['status'] == 'false'){

                        if($verificarCliente['idCliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                            $modelcliente->attributes = $dataSender;

                        } else {
                            // Al modelo del cliente se le asigna el valor de lo que llega por POST
                            $modelcliente = new Clientes;
                            $modelcliente->attributes = $dataSender;

                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                        }

                        /** 
                         * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                         * no permitir guardar dicha información y debera arrojar error.
                        */
                        $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                        if($modelClienteDetalle){

                            if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                $dataSender['nombreCliente'] != $modelClienteNombre or
                                $dataSender['direccionCliente'] != $modelClienteDireccion
                                )
                            ){
                                    
                                $transaction->rollBack();          
                                $validUpdate = false;  //Se guardo correctamente
                                return [
                                    'validUpdate' => $validUpdate,
                                    'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                ];
                            }
                        }
            
                        if($modelcliente->save()){
                            $dataSave[] = [
                                'idRemitente' => $modelcliente->idCliente,
                                'idTipoPersona' => $dataSender['idTipoPersona']
                            ];   

                        } else {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelcliente->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
    
                    } else {

                        if($dataSender['idCliente']['cliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                            $modelcliente->attributes = $dataSender;

                            /** 
                             * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                             * no permitir guardar dicha información y debera arrojar error.
                             */
                            $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                            if($modelClienteDetalle){

                                if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                    ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                    $dataSender['nombreCliente'] != $modelClienteNombre or
                                    $dataSender['direccionCliente'] != $modelClienteDireccion
                                    )
                                ){
                                        
                                    $transaction->rollBack();          
                                    $validUpdate = false;  //Se guardo correctamente
                                    return [
                                        'validUpdate' => $validUpdate,
                                        'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                    ];
                                }
                            }

                            if(!$modelcliente->save()) {

                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelcliente->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }

                        $dataSave[] = [
                            'idRemitente' => $verificarCliente['idCliente'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];                     

                        # Si es tipo PQRSD con cliente ciudadano, se agrega las actualizaciones
                        if(isset($modelcliente->clientesCiudadanosDetalles) && $modelcliente->clientesCiudadanosDetalles != null && $request['idCgTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiPqrs']){
                    
                            $modelClienteCiudadano = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
            
                            $modelClienteCiudadano->attributes = $request;
                            $modelClienteCiudadano->idCliente =  $modelcliente->idCliente;
                
                            if(!$modelClienteCiudadano->save()) {
                    
                                $transaction->rollBack();
                    
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelClienteCiudadano->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                    
                            # Observación del cliente pqrs para el log de radicados
                            $observacionAdicional = ' Género: '. Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'][$modelClienteCiudadano->generoClienteCiudadanoDetalle];
                            $observacionAdicional .= ', Rango de edad: '.Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'][$modelClienteCiudadano->rangoEdadClienteCiudadanoDetalle];  
                            $observacionAdicional .= ', Vulnerabilidad: '.Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'][$modelClienteCiudadano->vulnerabilidadClienteCiudadanoDetalle];  
                            $observacionAdicional .= ', Etnia: '.Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'][$modelClienteCiudadano->etniaClienteCiudadanoDetalle]; 
                        }
                    }            

                } else {
                    if(isset($dataSender['idCliente']['user'])){
                        $dataSave[] = [
                            'idRemitente' => $dataSender['idCliente']['user'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];   
                    }       
                }

            }
        }

        # Datos para el log de radicado
        $clonedModel = clone $model; // Modelo clonado para ver los cambios en el Helper log

        $clonedModel->fechaVencimientoRadiRadicados = date("Y-m-d",strtotime($clonedModel->fechaVencimientoRadiRadicados)); // Formato para fecha segun BD.. sera comparado con el original
        $clonedModel->idTrdTipoDocumental = $clonedModel->trdTipoDocumental->nombreTipoDocumental;
        $clonedModel->idCgMedioRecepcion = $clonedModel->cgMedioRecepcion->nombreCgMedioRecepcion;       

        # Almacenamiento de actualizaciones del radicado
        if ($model->save()) {

            # Remitente Anterior
            $radiRemitentesOld = RadiRemitentes::find()->select(['idRadiPersona','idTipoPersona'])->where(['idRadiRadicado' => (int) $model->idRadiRadicado])->all();

            # Se elimina el remitente asociado al radicado para posteriormente crearlo nuevamente
            $deleteRemitenteRadicado = RadiRemitentes::deleteAll(
                [   
                    'AND', 
                    ['idRadiRadicado' => (int) $model->idRadiRadicado]
                ]
            );                   

            # Se procede a guardar en la tabla de remitentes la relación del remitente con el radicado 
            foreach($dataSave as $ids){

                $modelRemitentes = new RadiRemitentes();
                $modelRemitentes->idRadiRadicado = $model->idRadiRadicado;
                $modelRemitentes->idRadiPersona = $ids['idRemitente'];
                $modelRemitentes->idTipoPersona = $ids['idTipoPersona'];  

                if(!$modelRemitentes->save()){
                    
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelRemitentes->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }                       
                
                foreach($radiRemitentesOld as $dataRemiOld){
                   
                    # Valida si se actualizo el remitente respecto al tipo de persona                   
                    if ($dataRemiOld != null && ($dataRemiOld['idRadiPersona'] != $modelRemitentes->idRadiPersona || $dataRemiOld['idTipoPersona'] != $modelRemitentes->idTipoPersona)) {
                        $validRemitente = true;
                    }
                }
            }


            # Se eliminan los registros anteriores de los radicados hijos que le pertenecen al radicado padre
            $deleteRadiAsociado = RadiRadicadosAsociados::deleteAll(
                [   
                    'AND', 
                    ['idRadiCreado' => (int) $model->idRadiRadicado]
                ]
            );
        
            # Se valida si llegan los radicados hijos asociados a los padres, si esto es correcto quiere decir que se 
            # deben asociar los radicados seleccionados al que se esta creando
            if(is_array($request['RadiRadicadoHijos']) && count($request['RadiRadicadoHijos']) > 0){

                $IdRadicadoAsociados = [];
                # Se actualiza los nuevos hijos del padre
                foreach ($request['RadiRadicadoHijos'] as $radiHijo){
                    $idRadicadoHijo = self::verificarRadicado($radiHijo); 

                    if(isset($idRadicadoHijo)){
                        $modelRadiAsociado = new RadiRadicadosAsociados();
                        $modelRadiAsociado->idRadiAsociado = $idRadicadoHijo;
                        $modelRadiAsociado->idRadiCreado = $model->idRadiRadicado;
                        $modelRadiAsociado->save();
                        // Para la busqueda de los número de radicados en una sola consulta
                        $IdRadicadoAsociados[] = $modelRadiAsociado->idRadiAsociado;       
                    }    
                }

                /** Información para extraer o relacionar los numeros de radicados asociados al radicado principal **/
                $findOneAsociado = RadiRadicados::find()->where(['IN', 'idRadiRadicado', $IdRadicadoAsociados])->all();

                $idAsociadosAlRadicado = [];
                $numerosAsociadosAlRadicado = [];

                foreach ($findOneAsociado as $datoAsociados) {
                    $idAsociadosAlRadicado[] = $datoAsociados->idRadiRadicado;
                    $numerosAsociadosAlRadicado[] = $datoAsociados->numeroRadiRadicado;
                }
            }

            // Guarda la actualización del consecutivo del numero de radicado
            $modelCgTiposRadicados->save(); 

            # Consultas para la data del log actual
            $infoRadicados = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
            $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $model->idRadiRadicado]);
            $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idTramitador]);
            $usuarioInfoCreador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idCreador]);
                 
            $nombreRemitente = [];
            foreach($remitentes as $dataRemitente){

                if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                    # Se obtiene la información del cliente
                    $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                            
                    $nombreRemitente[] = $modelRemitente->nombreCliente .' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';

                    # Valida si se actualizo el remitente respecto al nombre 
                    $observacionAdicional .= ', Remitente/Destinatario: '.$modelRemitente->nombreCliente.'';
                    $observacionAdicional .= ', Cuenta de correo del Remitente/Destinatario: '.$modelRemitente->correoElectronicoCliente.'';

                } else {    
                    # Se obtiene la información del usuario o funcionario
                    $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);

                    $nombreRemitente[] = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles.' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';
                }
            }

            $nombresRemitentes = implode(", ", $nombreRemitente);                    
                    
            # Prioridad del radicado
            if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

            $dataRadicados = 'Id radicado: '.$model->idRadiRadicado;
            $dataRadicados .= ', Número radicado: '.$model->numeroRadiRadicado;
            $dataRadicados .= ', Asunto radicado: '.$model->asuntoRadiRadicado;
            $dataRadicados .= ', Tipo radicado: '.$model->cgTipoRadicado->nombreCgTipoRadicado;
            $dataRadicados .= ', Tipo documental: '.$model->trdTipoDocumental->nombreTipoDocumental;
            $dataRadicados .= ', Prioridad: '.$prioridad;
            $dataRadicados .= ', Remitente/Destinatario: '.$nombresRemitentes;
            $dataRadicados .= ', Radicado origen: '.$model->radicadoOrigen;
            $dataRadicados .= ', Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
            $dataRadicados .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;
            $dataRadicados .= ', Conteo de correos del radicado: '.$infoRadicados->cantidadCorreosRadicado;
            $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$infoRadicados->estadoRadiRadicado];
            $dataRadicados .= ', Fecha documento: '.$model->fechaDocumentoRadiRadicado;
            $dataRadicados .= ', Descripción del anexo: '.$model->descripcionAnexoRadiRadicado;
            $dataRadicados .= ', Observación: '.$model->observacionRadiRadicado;
            if ($model->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                $dataRadicados .= ', Número de factura: '.$model->numeroFactura;
                $dataRadicados .= ', Número de contrato: '.$model->numeroContrato;
                $dataRadicados .= ', Valor factura: '.$model->valorFactura;
            }

            # Autorización de correo
            if($model->autorizacionRadiRadicados){
                $autorizacion = Yii::$app->params['SiNoNumber'][$model->autorizacionRadiRadicados];
            }else{
                $autorizacion = Yii::$app->params['SiNoText']['No'];
            }            
            $dataRadicados .= ', Autorización de envío de correo: '. $autorizacion;

            if(isset($idAsociadosAlRadicado)){
                if(count($idAsociadosAlRadicado) > 0 && count($numerosAsociadosAlRadicado) > 0){
                    $dataRadicados .= ', Ids radicados asociados: '.implode(', ', $idAsociadosAlRadicado);
                    $dataRadicados .= ', Radicados asociados: '.implode(', ', $numerosAsociadosAlRadicado);

                    # Observación de los asociados para el log de radicados
                    $observacionAdicional .= ', Radicado(s) asociado(s): '. implode(", ",$numerosAsociadosAlRadicado).', ';
                }
            }

            $transaction->commit();

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                true,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla radicados", //texto para almacenar en el evento
                $dataRadicadosOld, //DataOld
                $dataRadicados, //Data
                array() //No validar estos campos
            );     
            /***    Fin log Auditoria   ***/

            // # Observación adicional para el log de radicados cuando el remitente se actualizo.
            // if($validRemitente){
            //     $observacionAdicional .= ', Remitente/Destinatario: '.$nombresRemitentes.', ';
            // }

            /***    Log de Radicados  ***/
            HelperLog::logAddFiling(
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                $model->idRadiRadicado, //Id radicado
                $idTransaction,
                Yii::$app->params['eventosLogTextRadicado']['Edit'] . $observacionAdicional, //observación 
                $clonedModel,
                array() //No validar estos campos
            );

            /* Transacciones que se pueden realizar por el tipo de radicado */
            $transacciones = [];
            if ($model->user_idTramitador == Yii::$app->user->identity->id) {
                $transacciones[] = [ 'icon' => 'save', 'title' => 'Guardar', 'action' => 'save', 'data' => '' ];
            }

            # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
            $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);
            # Se valida si el usuario logueado es de tipo JEFE
            $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

            /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
            $ignoreTransacions = self::getIgnoreTransactions([], $permissionVoBo, $isUserJefe, $model);
            
            // El botón flotante solo debe ver:  crear,  ver, cargar doc's y stickers **el botón de guardar no aplica para cuando yo no soy el dueño del radicado**
            $transaccionesUpdate = Yii::$app->params['transaccionesUpdateRadicado']; // ['view', 'add', 'attachment', 'printStickers'];
            $radicadoTransacciones = self::radicadoTransacciones($model->idCgTipoRadicado, false, $ignoreTransacions, 'update');

            foreach ($radicadoTransacciones as $value) {
                //if (in_array($value['action'], $transaccionesUpdate)) {
                    $transacciones[] = $value;
                //}
            }

            $validUpdate = true;  //Se guardo correctamente

            return [
                'validUpdate' => $validUpdate,
                'model' => $model,
                'transacciones' => $transacciones
            ];

        } else {

            $validUpdate = false; //Errores en el almacenamiento
            
            return [
                'validUpdate' => $validUpdate,
                'model' => $model->getErrors(),
                'transacciones' => $transacciones ?? []
            ];
        }
    }


    /**
     * Función que actualiza los radicados DIFERENTES a (ENTRADA Y PQRS),
     * y lo puede realizar cualquier tipo de usuario que tenga permisos en el radicado
     * @param $request [Array de la data de $request]
     * @param $model [Modelo del radicado]
     * @param $transaction [Inicialización de la transacción]
     * @param $modelCgTiposRadicados [Modelo del tipo de radicado]
     * @param $dataRadicadosOld [Información anterior para el log]
     * @param $idTransaction [Id de la transaccion de actualizar radicado]
     * @return [Data que incluye el modelo del radicado y la variable de validación]
     **/
    public static function differentsfiles($request, $model, $transaction, $modelCgTiposRadicados, $dataRadicadosOld, $idTransaction){
        
        $validRemitente = false;    //Validación del remitente actualizado
        $observacionAdicional = '';  //Observacion adicional del log radicados
        
        # Actualización del radicado
        $model->attributes = $request;

        # Datos Old del radicado
        $oldRadicados = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);

        # Data del creador
        $model->user_idCreador = Yii::$app->user->identity->id;
        $model->idTrdDepeUserCreador = Yii::$app->user->identity->idGdTrdDependencia;

        # Prioridad del radicado
        if($request['PrioridadRadiRadicados'] == true) { $prioridad = 1; } else { $prioridad = 0; }
        $model->PrioridadRadiRadicados = $prioridad;


        if (is_array($request['remitentes']) && $request['remitentes'] != '') {

            $dataSave = []; //Array de id del remitente y tipo persona
            
            foreach ($request['remitentes'] as $dataSender) {

                // Si el tipo de persona es diferente a funcionario se realiza el proceso de creación del cliente
                // siempre y cuando este no exista.
                if($dataSender['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']){

                    // Se valida el correo y el documento que llegan por el formulario de radicación y si no existen se procede
                    // a validar si llega el campo id, para que asi se pueda buscar el cliente y poder actualizarlo, solo aplica
                    // para los clientes.
                    $verificarCliente = ClientesController::verificarCliente($dataSender['numeroDocumentoCliente'], $dataSender['correoElectronicoCliente']);

                    if($verificarCliente['status'] == 'false'){

                        if($verificarCliente['idCliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                            $modelcliente->attributes = $dataSender;

                        } else {
                            // Al modelo del cliente se le asigna el valor de lo que llega por POST
                            $modelcliente = new Clientes;
                            $modelcliente->attributes = $dataSender;

                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;
                        }
            
                        /** 
                         * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                         * no permitir guardar dicha información y debera arrojar error.
                        */
                        $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                        if($modelClienteDetalle){

                            if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                 $dataSender['nombreCliente'] != $modelClienteNombre or
                                 $dataSender['direccionCliente'] != $modelClienteDireccion
                                )
                            ){
                                
                                $transaction->rollBack();          
                                $validUpdate = false;  //Se guardo correctamente
                                return [
                                    'validUpdate' => $validUpdate,
                                    'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                ];
                            }
                        }

                        if($modelcliente->save()){
                            $dataSave[] = [
                                'idRemitente' => $modelcliente->idCliente,
                                'idTipoPersona' => $dataSender['idTipoPersona']
                            ];   

                        } else {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelcliente->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
    
                    } else {

                        if($dataSender['idCliente']['cliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                            $modelcliente->attributes = $dataSender;

                            /** 
                             * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                             * no permitir guardar dicha información y debera arrojar error.
                            */
                            $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                            if($modelClienteDetalle){

                                if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                    ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                    $dataSender['nombreCliente'] != $modelClienteNombre or
                                    $dataSender['direccionCliente'] != $modelClienteDireccion
                                    )
                                ){
                                    
                                    $transaction->rollBack();          
                                    $validUpdate = false;  //Se guardo correctamente
                                    return [
                                        'validUpdate' => $validUpdate,
                                        'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                    ];
                                }
                            }

                            if(!$modelcliente->save()) {

                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelcliente->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        
                       
                        $dataSave[] = [
                            'idRemitente' => $verificarCliente['idCliente'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];                     
                    }            

                } else {
                    if(isset($dataSender['idCliente']['user'])){
                        $dataSave[] = [
                            'idRemitente' => $dataSender['idCliente']['user'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];   
                    }       
                }
            }
        }


        # Datos para el log de radicado
        $clonedModel = clone $model; // Modelo clonado para ver los cambios en el Helper log

        $clonedModel->fechaVencimientoRadiRadicados = date("Y-m-d",strtotime($clonedModel->fechaVencimientoRadiRadicados)); // Formato para fecha segun BD.. sera comparado con el original
        $clonedModel->idTrdTipoDocumental = $clonedModel->trdTipoDocumental->nombreTipoDocumental;
        $clonedModel->idCgMedioRecepcion = $clonedModel->cgMedioRecepcion->nombreCgMedioRecepcion;       

        # Almacenamiento de actualizaciones del radicado
        if ($model->save()) {            

            # Remitente Anterior
            $radiRemitentesOld = RadiRemitentes::find()->select(['idRadiPersona','idTipoPersona'])->where(['idRadiRadicado' => (int) $model->idRadiRadicado])->all();

            # Se elimina el remitente asociado al radicado para posteriormente crearlo nuevamente
            $deleteRemitenteRadicado = RadiRemitentes::deleteAll(
                [   
                    'AND', 
                    ['idRadiRadicado' => (int) $model->idRadiRadicado]
                ]
            );                   

            # Se procede a guardar en la tabla de remitentes la relación del remitente con el radicado 
            foreach($dataSave as $ids){

                $modelRemitentes = new RadiRemitentes();
                $modelRemitentes->idRadiRadicado = $model->idRadiRadicado;
                $modelRemitentes->idRadiPersona = $ids['idRemitente'];
                $modelRemitentes->idTipoPersona = $ids['idTipoPersona'];  

                if(!$modelRemitentes->save()){
                    
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelRemitentes->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }                       
                
                foreach($radiRemitentesOld as $dataRemiOld){
                   
                    # Valida si se actualizo el remitente                    
                    if ($dataRemiOld != null && ($dataRemiOld['idRadiPersona'] != $modelRemitentes->idRadiPersona || $dataRemiOld['idTipoPersona'] != $modelRemitentes->idTipoPersona)) {
                        $validRemitente = true;                                
                    }
                }
            }


            # Se eliminan los registros anteriores de los radicados hijos que le pertenecen al radicado padre
            $deleteRadiAsociado = RadiRadicadosAsociados::deleteAll(
                [   
                    'AND', 
                    ['idRadiCreado' => (int) $model->idRadiRadicado]
                ]
            );
        
            # Se valida si llegan los radicados hijos asociados a los padres, si esto es correcto quiere decir que se 
            # deben asociar los radicados seleccionados al que se esta creando
            if(is_array($request['RadiRadicadoHijos']) && count($request['RadiRadicadoHijos']) > 0){

                $IdRadicadoAsociados = [];

                # Se actualiza los nuevos hijos del padre
                foreach ($request['RadiRadicadoHijos'] as $radiHijo){
                   
                    $idRadicadoHijo = self::verificarRadicado($radiHijo); 

                    if(isset($idRadicadoHijo)){
                        $modelRadiAsociado = new RadiRadicadosAsociados();
                        $modelRadiAsociado->idRadiAsociado = $idRadicadoHijo;
                        $modelRadiAsociado->idRadiCreado = $model->idRadiRadicado;
                        $modelRadiAsociado->save();
                        // Para la busqueda de los número de radicados en una sola consulta
                        $IdRadicadoAsociados[] = $modelRadiAsociado->idRadiAsociado;       
                    }    
                }

                /** Información para extraer o relacionar los numeros de radicados asociados al radicado principal **/
                $findOneAsociado = RadiRadicados::find()->where(['IN', 'idRadiRadicado', $IdRadicadoAsociados])->all();

                $idAsociadosAlRadicado = [];
                $numerosAsociadosAlRadicado = [];

                foreach ($findOneAsociado as $datoAsociados) {
                    $idAsociadosAlRadicado[] = $datoAsociados->idRadiRadicado;
                    $numerosAsociadosAlRadicado[] = $datoAsociados->numeroRadiRadicado;
                }
            }

            // Guarda la actualización del consecutivo del numero de radicado
            $modelCgTiposRadicados->save(); 

            # Consultas para la data del log actual
            $infoRadicados = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);
            $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $model->idRadiRadicado]);
            $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idTramitador]);
            $usuarioInfoCreador = UserDetalles::findOne(['idUser'=> $infoRadicados->user_idCreador]);


            $nombreRemitente = [];
            foreach($remitentes as $dataRemitente){

                if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                    # Se obtiene la información del cliente
                    $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                            
                    $nombreRemitente[] = $modelRemitente->nombreCliente .' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';

                    # Valida si se actualizo el remitente respecto al nombre 
                    $observacionAdicional .= ', Remitente/Destinatario: '.$modelRemitente->nombreCliente.'';
                    $observacionAdicional .= ', Cuenta de correo del Remitente/Destinatario: '.$modelRemitente->correoElectronicoCliente.'';

                } else {    
                    # Se obtiene la información del usuario o funcionario
                    $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);

                    $nombreRemitente[] = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles.' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';
                }
            }

            $nombresRemitentes = implode(", ", $nombreRemitente);                    
                    
            # Prioridad del radicado
            if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

            $dataRadicados = 'Id radicado: '.$model->idRadiRadicado;
            if ((boolean) $model->isRadicado == true) {
                $dataRadicados .= ', Número radicado: '.$model->numeroRadiRadicado;
                $dataRadicados .= ', Asunto radicado: '.$model->asuntoRadiRadicado;
            } else {
                $dataRadicados .= ', Número de consecutivo: '.$model->numeroRadiRadicado;
                $dataRadicados .= ', Asunto: '.$model->asuntoRadiRadicado;
            }
            $dataRadicados .= ', Tipo radicado: '.$model->cgTipoRadicado->nombreCgTipoRadicado;
            $dataRadicados .= ', Tipo documental: '.$model->trdTipoDocumental->nombreTipoDocumental;
            $dataRadicados .= ', Prioridad: '.$prioridad;
            $dataRadicados .= ', Remitente/Destinatario: '.$nombresRemitentes;
            $dataRadicados .= ', Radicado origen: '.$model->radicadoOrigen;
            $dataRadicados .= ', Usuario tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
            $dataRadicados .= ', Usuario creador: '.$usuarioInfoCreador->nombreUserDetalles.' '.$usuarioInfoCreador->apellidoUserDetalles;
            $dataRadicados .= ', Conteo de correos del radicado: '.$infoRadicados->cantidadCorreosRadicado;
            $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$infoRadicados->estadoRadiRadicado];
            $dataRadicados .= ', Fecha documento: '.$model->fechaDocumentoRadiRadicado;
            $dataRadicados .= ', Descripción del anexo: '.$model->descripcionAnexoRadiRadicado;
            $dataRadicados .= ', Observación: '.$model->observacionRadiRadicado;
            if ($model->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']) {
                $dataRadicados .= ', Número de factura: '.$model->numeroFactura;
                $dataRadicados .= ', Número de contrato: '.$model->numeroContrato;
                $dataRadicados .= ', Valor factura: '.$model->valorFactura;
            }

            if(isset($idAsociadosAlRadicado)){
                if(count($idAsociadosAlRadicado) > 0 && count($numerosAsociadosAlRadicado) > 0){
                    $dataRadicados .= ', Ids radicados asociados: '.implode(', ', $idAsociadosAlRadicado);
                    $dataRadicados .= ', Radicados asociados: '.implode(', ', $numerosAsociadosAlRadicado);

                    # Observación de los asociados para el log de radicados
                    $observacionAdicional .= ', Radicado(s) asociado(s): '. implode(", ",$numerosAsociadosAlRadicado).', ';
                }
            }


            # actualizacion tipo documental en los anexos del radicado
            if($model->idTrdTipoDocumental != $oldRadicados->idTrdTipoDocumental){

                # actualizacion de tipo documental para radi documentos
                $modelRadiDocumentos  = RadiDocumentos::find()->where(['idRadiRadicado' => $model->idRadiRadicado])->all();

                foreach($modelRadiDocumentos as $documento){

                    $documento->idGdTrdTipoDocumental = $model->idTrdTipoDocumental;

                    if(!$documento->save()){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $documento->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
            }
            
            $transaction->commit();

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                true,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla radicados", //texto para almacenar en el evento
                $dataRadicadosOld, //DataOld
                $dataRadicados, //Data
                array() //No validar estos campos
            );     
            /***    Fin log Auditoria   ***/

            # Observación adicional para el log de radicados cuando el remitente se actualizo.
            // if($validRemitente){
            //     $observacionAdicional .= ', Remitente/Destinatario: '.$nombresRemitentes.', ';
            // }

            if ((boolean) $model->isRadicado == true) {
                $eventLogTextEdit = Yii::$app->params['eventosLogTextRadicado']['Edit'];
            } else {
                $eventLogTextEdit = Yii::$app->params['eventosLogTextRadicado']['EditTmp'];
            }

            /***    Log de Radicados  ***/
            HelperLog::logAddFiling(
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                $model->idRadiRadicado, //Id radicado
                $idTransaction,
                $eventLogTextEdit . $observacionAdicional, //observación 
                $clonedModel,
                array() //No validar estos campos
            );

            /* Transacciones que se pueden realizar por el tipo de radicado */
            $transacciones = [];
            if ($model->user_idTramitador == Yii::$app->user->identity->id) {
                $transacciones[] = [ 'icon' => 'save', 'title' => 'Guardar', 'action' => 'save', 'data' => '' ];
            }

            # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
            $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);
            # Se valida si el usuario logueado es de tipo JEFE
            $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

            /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
            $ignoreTransacions = self::getIgnoreTransactions([], $permissionVoBo, $isUserJefe, $model);
            
            // El botón flotante solo debe ver:  crear,  ver, cargar doc's y stickers **el botón de guardar no aplica para cuando yo no soy el dueño del radicado**
            $transaccionesUpdate = Yii::$app->params['transaccionesUpdateRadicado']; // ['view', 'add', 'attachment', 'printStickers'];
            $radicadoTransacciones = self::radicadoTransacciones($model->idCgTipoRadicado, false, $ignoreTransacions, 'update');

            foreach ($radicadoTransacciones as $value) {
                //if (in_array($value['action'], $transaccionesUpdate)) {
                    $transacciones[] = $value;
                //}
            }

            $validUpdate = true;  //Se guardo correctamente

            return [
                'validUpdate' => $validUpdate,
                'model' => $model,
                'transacciones' => $transacciones
            ];

        } else {

            $validUpdate = false; //Errores en el almacenamiento
            
            return [
                'validUpdate' => $validUpdate,
                'model' => $model->getErrors(),
                'transacciones' => $transacciones ?? []
            ];
        }
    }


    public function actionChangeStatus()
    {
        $errors = [];
        $dataResponse = [];
        $dataExplode = "";
        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST, PUT ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST, PUT ***//

            $saveDataValid = true;
            $transaction = Yii::$app->db->beginTransaction();

            foreach ($request as $value) {
                $dataExplode = explode('|', $value);

                $model = $this->findModel($dataExplode[0]);
                if ($model->status == yii::$app->params['statusTodoText']['Activo']) {
                    $model->status = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->status = yii::$app->params['statusTodoText']['Activo'];
                }

                if($model->save()) {
                    $dataResponse[] = array('id' => $model->id, 'status' => $model->status, 'statusText' => Yii::t('app','statusTodoNumber')[$model->status], 'idInitialList' => $dataExplode[1] * 1);
                
                        /***  log Auditoria ***/     
                        if(!Yii::$app->user->isGuest) {
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['Update'].' User',// texto para almacenar en el evento
                                [],
                                [$model], //Data
                                array() //No validar estos campos
                            );
                        } 
                        /***   Fin log Auditoria   ***/
                
                } else {
                    $errs[] = $model->getErrors();
                    $saveDataValid = false;
                }
            }

            if ($saveDataValid) {

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successChangeStatus'),
                    'data' => $dataResponse,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $errs,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'emptyJsonSend'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


    /**
     * Finds the RadiRadicados model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $idRadiRadicado
     * @return RadiRadicados the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($idRadiRadicado)
    {
        if (($model = RadiRadicados::findOne($idRadiRadicado)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


    /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $model [Modelo de la tabla]
     * @return $dataLog [String] [Información del modelo]
    **/
    protected function dataLog($model, $prioridad, $nombresRemitentes, $infoRadicados){

        if(!is_null($model)){
           
            # Valores del modelo se utiliza para el log
            $labelModel = $model->attributeLabels();
            $dataLog = '';

            foreach ($model as $key => $value) {
                                
                switch ($key) {
                    case 'idRadiRadicado':
                        $dataLog .= $labelModel[$key].': '.$model->idRadiRadicado.', ';
                        $dataLog .= 'Remitente/Destinatario: '.$nombresRemitentes.', ';                        
                    break;
                    case 'numeroRadiRadicado':
                        if ($model->isRadicado == true || $model->isRadicado == 1) {
                            $dataLog .= $labelModel[$key].': '.$model->numeroRadiRadicado.', ';
                        } else {
                            $dataLog .= 'Número de consecutivo: '.$model->numeroRadiRadicado.', ';
                        }
                    break;
                    case 'estadoRadiRadicado':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$infoRadicados->estadoRadiRadicado].', ';
                    break;
                    case 'idTrdTipoDocumental':                                        
                        $dataLog .= $labelModel[$key].': '.$model->trdTipoDocumental->nombreTipoDocumental.', ';
                    break; 
                    case 'user_idCreador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->userIdCreador->userDetalles->nombreUserDetalles." ".$model->userIdCreador->userDetalles->apellidoUserDetalles.', ';
                    break; 
                    case 'idTrdDepeUserCreador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->idTrdDepeUserCreador0->nombreGdTrdDependencia.', ';
                    break; 
                    case 'user_idTramitador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->userIdTramitador->userDetalles->nombreUserDetalles." ".$model->userIdTramitador->userDetalles->apellidoUserDetalles.', ';
                    break; 
                    case 'idTrdDepeUserTramitador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->idTrdDepeUserTramitador0->nombreGdTrdDependencia.', ';
                    break; 
                    // case 'user_idTramitadorOld':                                        
                    //     $dataLog .= $labelModel[$key].': '.$model->userIdTramitadorOld->userDetalles->nombreUserDetalles." ".$model->userIdTramitadorOld->userDetalles->apellidoUserDetalles.', ';
                    // break; 
                   
                    case 'idCgTipoRadicado':                                        
                        $dataLog .= $labelModel[$key].': '.$model->cgTipoRadicado->nombreCgTipoRadicado.', ';
                    break; 
                    case 'idCgMedioRecepcion':                                        
                        $dataLog .= $labelModel[$key].': '.$model->cgMedioRecepcion->nombreCgMedioRecepcion.', ';
                    break;  
                    case 'PrioridadRadiRadicados':
                        $dataLog .= $labelModel[$key].': '.$prioridad.', ';
                    break;                   
                    case 'creacionRadiRadicado':
                        $dataLog .= $labelModel[$key].': '.$infoRadicados->creacionRadiRadicado.', ';
                    break;                   
                    case 'cantidadCorreosRadicado':
                        $dataLog .= $labelModel[$key].': '.$infoRadicados->cantidadCorreosRadicado.', ';
                    break;                   
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }

            return $dataLog;
        }
    } 


    public function actionIndexListRadicado()
    {

        $modelMedios = CgTiposRadicados::find()
            ->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        foreach ($modelMedios as $row) {
            $data[] = array(
                "id" => (int) $row['idCgTipoRadicado'],
                "val" => $row['nombreCgTipoRadicado'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data ?? [],
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionIndexListMedioRecepcion()
    {
        $data = [];

        $modelMedios = CgMediosRecepcion::find()
            ->where(['estadoCgMedioRecepcion' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        foreach ($modelMedios as $row) {
            $data[] = array(
                "id" => (int) $row['idCgMedioRecepcion'],
                "val" => $row['nombreCgMedioRecepcion'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /* Listado de series a partir de la dependencia */
    public function actionIndexListSeries($request) {        

        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        //*** Fin desencriptación GET ***//

        $idCgTipoRadicado = $request['idCgTipoRadicado'];
        $idDependencia    = $request['idTrdDepeUserTramitador'];

        $modelRelation = GdTrdSeries::find()->select(['gdTrdSeries.idGdTrdSerie', 'gdTrdSeries.nombreGdTrdSerie']);
        $modelRelation = HelperQueryDb::getQuery('innerJoin', $modelRelation, 'gdTrd', ['gdTrdSeries' => 'idGdTrdSerie', 'gdTrd' => 'idGdTrdSerie']);

        if($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiSalida']){
            $modelRelation = HelperQueryDb::getQuery('innerJoin', $modelRelation, 'gdTrdDependencias', ['gdTrd' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
            $modelRelation = $modelRelation->where(['or',
                ['gdTrdDependencias.idGdTrdDependencia' => $idDependencia],
                ['gdTrdDependencias.codigoGdTrdDepePadre' => $idDependencia],
            ]);
        } else {
            $modelRelation = $modelRelation->where(['gdTrd.idGdTrdDependencia' => $idDependencia]);
        }

        $modelRelation->groupBy(['gdTrdSeries.idGdTrdSerie', 'gdTrdSeries.nombreGdTrdSerie']);
        $modelRelation = $modelRelation->andWhere(['gdTrd.estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])->all();

        # Si la consulta no tiene configurada tipos documentales, muestra un mensaje de alerta.
        if(count($modelRelation) == 0 && ($idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) ) {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'noTrdData')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);

        }

        $dataList = [];
        foreach ($modelRelation as $row) {

            $dataList[] = [
                'id'  => (int) $row['idGdTrdSerie'],
                'val' => $row['nombreGdTrdSerie'],
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',            
            'dataList' => $dataList, // Series
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /* Listado de subseries a partir de la dependencia */
    public function actionIndexListSubseries($request) {        

        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        //*** Fin desencriptación GET ***//

        $idCgTipoRadicado = $request['idCgTipoRadicado'];
        $idDependencia    = $request['idTrdDepeUserTramitador'];
        $idGdTrdSerie     = $request['idGdTrdSerie'];

        $modelRelation = GdTrdSubseries::find()->select(['gdTrdSubseries.idGdTrdSubserie', 'gdTrdSubseries.nombreGdTrdSubserie']);
        $modelRelation = HelperQueryDb::getQuery('innerJoin', $modelRelation, 'gdTrd', ['gdTrdSubseries' => 'idGdTrdSubserie', 'gdTrd' => 'idGdTrdSubserie']);

        if($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']  || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiSalida']){
            $modelRelation = HelperQueryDb::getQuery('innerJoin', $modelRelation, 'gdTrdDependencias', ['gdTrd' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
            $modelRelation = $modelRelation->where(['or',
                ['gdTrdDependencias.idGdTrdDependencia' => $idDependencia],
                ['gdTrdDependencias.codigoGdTrdDepePadre' => $idDependencia],
            ]);
        } else {
            $modelRelation = $modelRelation->where(['gdTrd.idGdTrdDependencia' => $idDependencia]);
        }

        $modelRelation = $modelRelation->andWhere(['gdTrd.idGdTrdSerie' => $idGdTrdSerie]);
        $modelRelation->groupBy(['gdTrdSubseries.idGdTrdSubserie', 'gdTrdSubseries.nombreGdTrdSubserie']);
        $modelRelation = $modelRelation->andWhere(['gdTrd.estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])->all();

        $dataList = [];
        foreach ($modelRelation as $row) {

            $dataList[] = [
                'id'  => (int) $row['idGdTrdSubserie'],
                'val' => $row['nombreGdTrdSubserie'],
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',            
            'dataList' => $dataList, // Subseries
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /* Listado de tipos documentales dependiendo del tipo de radicado */
    public function actionIndexListTipoDocumental() {        
    
        $jsonSend = Yii::$app->request->post('jsonSend');   

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);

            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];

            } else {

                $request['idCgTipoRadicado'] = 1;

            }

        } else {

            $request['idCgTipoRadicado'] = 1;

        }      

        $idGdTrdSerie    = $request['idGdTrdSerie'];
        $idGdTrdSubserie = $request['idGdTrdSubserie'];

        $idCgTipoRadicado = $request['idCgTipoRadicado'];
        if ((int) $request['idTrdDepeUserTramitador'] != 0) {
            $idDependencia = $request['idTrdDepeUserTramitador'];
        } else {
            $idDependencia = Yii::$app->user->identity->idGdTrdDependencia;
        }


        if($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']){
            $modelDocumentales = GdTrdTiposDocumentales::find()->select(['gdTrdTiposDocumentales.idGdTrdTipoDocumental', 'gdTrdTiposDocumentales.nombreTipoDocumental']);
            $modelDocumentales = HelperQueryDb::getQuery('innerJoin', $modelDocumentales, 'cgTipoRadicadoDocumental', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'cgTipoRadicadoDocumental' => 'idGdTrdTipoDocumental']);

            $modelDocumentales->groupBy(['gdTrdTiposDocumentales.idGdTrdTipoDocumental', 'gdTrdTiposDocumentales.nombreTipoDocumental']);
            $modelDocumentales = $modelDocumentales -> all();

        }else{

            $modelDocumentales = GdTrdTiposDocumentales::find()->select(['gdTrdTiposDocumentales.idGdTrdTipoDocumental', 'gdTrdTiposDocumentales.nombreTipoDocumental']);
            $modelDocumentales = HelperQueryDb::getQuery('innerJoin', $modelDocumentales, 'gdTrd', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'gdTrd' => 'idGdTrdTipoDocumental']);
            
            if($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']  || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiSalida']){
    
                HelperQueryDb::getQuery('innerJoin', $modelDocumentales, 'gdTrdDependencias', ['gdTrd' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
    
                $modelDocumentales = $modelDocumentales->where(['or',
                ['gdTrdDependencias.idGdTrdDependencia' => $idDependencia],
                ['gdTrdDependencias.codigoGdTrdDepePadre' => $idDependencia],
                ]);
    
            }else{
    
                $modelDocumentales = $modelDocumentales->where(['gdTrd.idGdTrdDependencia' => $idDependencia]);
            }

            if($idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']){
                $modelDocumentales = $modelDocumentales->andWhere(['gdTrd.idGdTrdSerie' => $idGdTrdSerie]);
                $modelDocumentales = $modelDocumentales->andWhere(['gdTrd.idGdTrdSubserie' => $idGdTrdSubserie]);
            }
            
            $modelDocumentales->groupBy(['gdTrdTiposDocumentales.idGdTrdTipoDocumental', 'gdTrdTiposDocumentales.nombreTipoDocumental']);
            $modelDocumentales = $modelDocumentales->andWhere(['gdTrd.estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();
        }

        # Si la consulta no tiene configurada tipos documentales, muestra un mensaje de alerta.
        if(count($modelDocumentales) == 0 && ($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']) ) {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'noTrdData')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);

        }

        $dataDocumentales = [];
        foreach ($modelDocumentales as $row) {           

            if($row->idGdTrdTipoDocumental === 0 && $row->nombreTipoDocumental == 'Sin tipo documental'){
                
                if($idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura'] || $idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiSalida']){
                    $dataDocumentales[] = array(
                        "id" => (int) $row['idGdTrdTipoDocumental'],
                        "val" => $row['nombreTipoDocumental'],
                    );
                }

            } else {
                $dataDocumentales[] = array(
                    "id" => (int) $row['idGdTrdTipoDocumental'],
                    "val" => $row['nombreTipoDocumental'],
                );
            }

            
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',            
            'dataDocumentales' => $dataDocumentales, // Tipos de Documentos
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /* Listado de clientes */
    public function actionListClientes($request)
    {
        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        //*** Fin desencriptación GET ***//

        $idCgTipoRadicado = $request['idCgTipoRadicado'];
        $radicacionEmail = $request['radicacionEmail'];

        /**
         * Validar en los tipos de radicados comunicación interna, la lista de destinatarios debería contener solo funcionarios NO clientes
         * Nota: No incluir dicho cambio para radicación mail
         */

        $modelClientes = [];
        if($idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['comunicacionInterna'] || $radicacionEmail == true){
            $modelClientes = Clientes::find()->all();
        }

        $modelUser = UserDetalles::find()
            ->where(['estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();    

        # Listado de clientes y funcionarios
        foreach ($modelClientes as $row) {
            $dataClientes[] = array(
                "id" => array( 'cliente' => (int) $row['idCliente'] ),
                "val" => $row['nombreCliente'] . ' - ' . $row['numeroDocumentoCliente'].' - (Cliente)',
            );
        }
        foreach ($modelUser as $row) {
            $dataClientes[] = array(
                "id" => array( 'user' => (int) $row['idUser'] ),
                "val" => $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'] . ' - ' . $row['documento'].' - (Funcionario)',
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataClientes' => $dataClientes, // Listado de clientes
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /* Listado de clientes */
    public function actionListClientesNotEncrypt($request)
    {
        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        //*** Fin desencriptación GET ***//

        $idCgTipoRadicado = $request['idCgTipoRadicado'];
        $radicacionEmail = $request['radicacionEmail'];
        $consultarCliente = $request['searchClient'];
        $dataClientes = [];

        /**
         * Validar en los tipos de radicados comunicación interna, la lista de destinatarios debería contener solo funcionarios NO clientes
         * Nota: No incluir dicho cambio para radicación mail
         */

        $modelClientes = [];
        if($idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['comunicacionInterna'] || $radicacionEmail == true){
            $modelClientes = Clientes::find()
            ->where([Yii::$app->params['like'], 'nombreCliente', $consultarCliente])
            ->orWhere([Yii::$app->params['like'], 'numeroDocumentoCliente', $consultarCliente])
            ->limit(Yii::$app->params['limitRecordsSmall'])
            ->all();
        }

        $modelUser = UserDetalles::find()
            ->where(['estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere([Yii::$app->params['like'], 'nombreUserDetalles', $consultarCliente])
            ->orWhere([Yii::$app->params['like'], 'apellidoUserDetalles', $consultarCliente])
            ->limit(Yii::$app->params['limitRecordsSmall'])
            ->all();

        # Listado de clientes y funcionarios
        foreach ($modelClientes as $row) {
            $dataClientes[] = array(
                "id" => array( 'cliente' => (int) $row['idCliente'] ),
                "val" => $row['nombreCliente'] . ' - ' . $row['numeroDocumentoCliente'].' - (Cliente)',
            );
        }
        foreach ($modelUser as $row) {
            $dataClientes[] = array(
                "id" => array( 'user' => (int) $row['idUser'] ),
                "val" => $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'] . ' - ' . $row['documento'].' - (Funcionario)',
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataClientes' => $dataClientes, // Listado de clientes
            'status' => 200,
        ];
        return $response;

    }

    /* Listados de los select del formulario de radicación */
    public function actionIndexGeneralFilingLists()
    {
        $dataMediosRecepcion = [];
        $dataRadicado = [];
        $dataDocumentales = [];
        $dataRadiUnico = [];

        $modelNumRadicados = RadiRadicados::find()
        ->select(['numeroRadiRadicado','idRadiRadicado','asuntoRadiRadicado'])
        ->where(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Activo']])
        ->orWhere(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Finalizado']])
        ->orWhere(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Archivado']])
        ->all();

        $modelClientes = Clientes::find()->all();

        $modelUser = UserDetalles::find()
            ->where(['estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        $modelMedios = CgMediosRecepcion::find()
            ->where(['estadoCgMedioRecepcion' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();            

        $modelRadicados = CgTiposRadicados::find();
            //->innerJoin('rolesTipoRadicado', 'cgTiposRadicados.idCgTipoRadicado = rolesTipoRadicado.idCgTipoRadicado');
        $modelRadicados = HelperQueryDb::getQuery('innerJoin', $modelRadicados, 'rolesTipoRadicado', ['cgTiposRadicados' => 'idCgTipoRadicado', 'rolesTipoRadicado' => 'idCgTipoRadicado']);
        $modelRadicados =  $modelRadicados->where(['rolesTipoRadicado.idRol' => Yii::$app->user->identity->idRol])
            ->Andwhere(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])->all();

        $modelDocumentales = GdTrdTiposDocumentales::find();
            //->innerJoin('rolesTipoDocumental', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = rolesTipoDocumental.idGdTrdTipoDocumental')
        $modelDocumentales = HelperQueryDb::getQuery('innerJoin', $modelDocumentales, 'rolesTipoDocumental', ['rolesTipoDocumental' => 'idGdTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
        $modelDocumentales = $modelDocumentales->where(['rolesTipoDocumental.idRol' => Yii::$app->user->identity->idRol])->all();

        $modelNivelGeografico1 = NivelGeografico1::find()->where(["estadoNivelGeografico1" => Yii::$app->params['statusTodoText']['Activo']])->all();
        // $modelNivelGeografico2 = NivelGeografico2::find()->where(["estadoNivelGeografico2" => Yii::$app->params['statusTodoText']['Activo']])->all();
        $modelNivelGeografico2 = [];
        //$modelNivelGeografico3 = NivelGeografico3::find()->where(["estadoNivelGeografico3" => Yii::$app->params['statusTodoText']['Activo']])->all();
        $modelNivelGeografico3 = [];

        $modelTipoPersonal = TiposPersonas::find()
            ->where(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']])->all();

        foreach ($modelNumRadicados as $row) {
            $dataNumRadicados[] = array(
                "id" => (int) $row['idRadiRadicado'],
                "val" => $row['numeroRadiRadicado'] . ' - ' . $row['asuntoRadiRadicado'],
            );
        }

        foreach ($modelMedios as $row) {
            $dataMediosRecepcion[] = array(
                "id" => (int) $row['idCgMedioRecepcion'],
                "val" => $row['nombreCgMedioRecepcion'],
            );
        }    

        foreach ($modelRadicados as $row) {
            $dataRadicado[] = array(
                "id" => (int) $row['idCgTipoRadicado'],
                "val" => $row['nombreCgTipoRadicado'],
            );
        }

        foreach ($modelRadicados as $row) {
            $dataRadiUnico[$row['codigoCgTipoRadicado']] = $row['unicoRadiCgTipoRadicado'];
        }

        foreach ($modelDocumentales as $row) {
            $dataDocumentales[] = array(
                "id" => (int) $row['idGdTrdTipoDocumental'],
                "val" => $row['nombreTipoDocumental'],
            );
        }

        # Listado de clientes y funcionarios
        foreach ($modelClientes as $row) {                
            $dataClientes[] = array(
                "id" => array( 'cliente' => (int) $row['idCliente'] ),
                "val" => $row['nombreCliente'] . ' - ' . $row['numeroDocumentoCliente'].' - (Cliente)',
            );
        }
        foreach ($modelUser as $row) {                
            $dataClientes[] = array(
                "id" => array( 'user' => (int) $row['idUser'] ),
                "val" => $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'] . ' - ' . $row['documento'].' - (Funcionario)',
            );
        }
        

        foreach ($modelNivelGeografico1 as $row) {
            $dataNivelGeografico1[] = array(
                "id" => (int) $row['nivelGeografico1'],
                "val" => $row['nomNivelGeografico1'],
            );
        }

        foreach ($modelNivelGeografico2 as $row) {
            $dataNivelGeografico2[] = array(
                "id" => (int) $row['nivelGeografico2'],
                "val" => $row['nomNivelGeografico2'],
            );
        }

        foreach ($modelNivelGeografico3 as $row) {
            $dataNivelGeografico3[] = array(
                "id" => (int) $row['nivelGeografico3'],
                "val" => $row['nomNivelGeografico3'],
            );
        }

        foreach ($modelTipoPersonal as $row) {
            $dataTipoPersonal[] = array(
                "id" => (int) $row['idTipoPersona'],
                "val" => $row['tipoPersona'],
            );
        }

        $dataUserLogin = [
            'id' => Yii::$app->user->identity->id,
            'idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia,
        ];

        # Se valida si el usuario logueado tiene permisos para actualizar el código SAP del cliente en el formulario de radicadión
        $permissionsUpdateSapCode = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsUpdateSapCode'], Yii::$app->user->identity->rol->rolesTiposOperaciones);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataUserLogin' => $dataUserLogin, //Data del usuario logueado
            'dataNumRadicados' => $dataNumRadicados ?? [],// Numeros de Radicados para select multiple
            'dataClientes' => $dataClientes ?? [], // Array Informacion del Cliente
            'dataMediosRecepcion' => $dataMediosRecepcion, // Tipos de medios de Recepcion            
            'dataTipoPersonal' => $dataTipoPersonal, // Tipo de Persona
            'dataRadicado' => $dataRadicado, // Tipos de Radicado
            'dataRadiUnico' => $dataRadiUnico, // unico radicado 
            'dataDocumentales' => $dataDocumentales, // Tipos de Documentos
            'dataNivelGeografico1' => $dataNivelGeografico1 ?? [], // Paises
            "dataNivelGeografico2" => $dataNivelGeografico2 ?? [], // Departamentos
            "dataNivelGeografico3" => $dataNivelGeografico3 ?? [], // Ciudades - municipios
            'permissionsUpdateSapCode' => $permissionsUpdateSapCode, // Permiso para actualizar código SAP
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /* Listados de los select del formulario de radicación */
    public function actionIndexGeneralFilingListsNotEncrypt()
    {
        $dataMediosRecepcion = [];
        $dataRadicado = [];
        $dataDocumentales = [];
        $dataRadiUnico = [];

        $modelNumRadicados = RadiRadicados::find()
        ->select(['numeroRadiRadicado','idRadiRadicado','asuntoRadiRadicado'])
        ->where(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Activo']])
        ->orWhere(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Finalizado']])
        ->orWhere(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Archivado']])
        ->all();

        $modelClientes = Clientes::find()->all();

        $modelUser = UserDetalles::find()
            ->where(['estadoUserDetalles' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        $modelMedios = CgMediosRecepcion::find()
            ->where(['estadoCgMedioRecepcion' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        $modelRadicados = CgTiposRadicados::find();
            //->innerJoin('rolesTipoRadicado', 'cgTiposRadicados.idCgTipoRadicado = rolesTipoRadicado.idCgTipoRadicado');
        $modelRadicados = HelperQueryDb::getQuery('innerJoin', $modelRadicados, 'rolesTipoRadicado', ['cgTiposRadicados' => 'idCgTipoRadicado', 'rolesTipoRadicado' => 'idCgTipoRadicado']);
        $modelRadicados =  $modelRadicados->where(['rolesTipoRadicado.idRol' => Yii::$app->user->identity->idRol])
            ->Andwhere(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])->all();

        $modelDocumentales = GdTrdTiposDocumentales::find();
            //->innerJoin('rolesTipoDocumental', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = rolesTipoDocumental.idGdTrdTipoDocumental')
        $modelDocumentales = HelperQueryDb::getQuery('innerJoin', $modelDocumentales, 'rolesTipoDocumental', ['rolesTipoDocumental' => 'idGdTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
        $modelDocumentales = $modelDocumentales->where(['rolesTipoDocumental.idRol' => Yii::$app->user->identity->idRol])->all();

        $modelNivelGeografico1 = NivelGeografico1::find()->where(["estadoNivelGeografico1" => Yii::$app->params['statusTodoText']['Activo']])->all();
        // $modelNivelGeografico2 = NivelGeografico2::find()->where(["estadoNivelGeografico2" => Yii::$app->params['statusTodoText']['Activo']])->all();
        $modelNivelGeografico2 = [];
        //$modelNivelGeografico3 = NivelGeografico3::find()->where(["estadoNivelGeografico3" => Yii::$app->params['statusTodoText']['Activo']])->all();
        $modelNivelGeografico3 = [];

        $modelTipoPersonal = TiposPersonas::find()
            ->where(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']])->all();

        foreach ($modelNumRadicados as $row) {
            $dataNumRadicados[] = array(
                "id" => (int) $row['idRadiRadicado'],
                "val" => $row['numeroRadiRadicado'] . ' - ' . $row['asuntoRadiRadicado'],
            );
        }

        foreach ($modelMedios as $row) {
            $dataMediosRecepcion[] = array(
                "id" => (int) $row['idCgMedioRecepcion'],
                "val" => $row['nombreCgMedioRecepcion'],
            );
        }    

        foreach ($modelRadicados as $row) {
            $dataRadicado[] = array(
                "id" => (int) $row['idCgTipoRadicado'],
                "val" => $row['nombreCgTipoRadicado'],
            );
        }

        foreach ($modelRadicados as $row) {
            $dataRadiUnico[$row['codigoCgTipoRadicado']] = $row['unicoRadiCgTipoRadicado'];
        }

        foreach ($modelDocumentales as $row) {
            $dataDocumentales[] = array(
                "id" => (int) $row['idGdTrdTipoDocumental'],
                "val" => $row['nombreTipoDocumental'],
            );
        }

        # Listado de clientes y funcionarios
        foreach ($modelClientes as $row) {                
            $dataClientes[] = array(
                "id" => array( 'cliente' => (int) $row['idCliente'] ),
                "val" => $row['nombreCliente'] . ' - ' . $row['numeroDocumentoCliente'].' - (Cliente)',
            );
        }
        foreach ($modelUser as $row) {                
            $dataClientes[] = array(
                "id" => array( 'user' => (int) $row['idUser'] ),
                "val" => $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'] . ' - ' . $row['documento'].' - (Funcionario)',
            );
        }
        

        foreach ($modelNivelGeografico1 as $row) {
            $dataNivelGeografico1[] = array(
                "id" => (int) $row['nivelGeografico1'],
                "val" => $row['nomNivelGeografico1'],
            );
        }

        foreach ($modelNivelGeografico2 as $row) {
            $dataNivelGeografico2[] = array(
                "id" => (int) $row['nivelGeografico2'],
                "val" => $row['nomNivelGeografico2'],
            );
        }

        foreach ($modelNivelGeografico3 as $row) {
            $dataNivelGeografico3[] = array(
                "id" => (int) $row['nivelGeografico3'],
                "val" => $row['nomNivelGeografico3'],
            );
        }

        foreach ($modelTipoPersonal as $row) {
            $dataTipoPersonal[] = array(
                "id" => (int) $row['idTipoPersona'],
                "val" => $row['tipoPersona'],
            );
        }

        $dataUserLogin = [
            'id' => Yii::$app->user->identity->id,
            'idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia,
        ];

        # Se valida si el usuario logueado tiene permisos para actualizar el código SAP del cliente en el formulario de radicadión
        $permissionsUpdateSapCode = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsUpdateSapCode'], Yii::$app->user->identity->rol->rolesTiposOperaciones);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataUserLogin' => $dataUserLogin, //Data del usuario logueado
            'dataNumRadicados' => $dataNumRadicados ?? [],// Numeros de Radicados para select multiple
            'dataClientes' => $dataClientes ?? [], // Array Informacion del Cliente
            'dataMediosRecepcion' => $dataMediosRecepcion, // Tipos de medios de Recepcion            
            'dataTipoPersonal' => $dataTipoPersonal, // Tipo de Persona
            'dataRadicado' => $dataRadicado, // Tipos de Radicado
            'dataRadiUnico' => $dataRadiUnico, // unico radicado 
            'dataDocumentales' => $dataDocumentales, // Tipos de Documentos
            'dataNivelGeografico1' => $dataNivelGeografico1 ?? [], // Paises
            "dataNivelGeografico2" => $dataNivelGeografico2 ?? [], // Departamentos
            "dataNivelGeografico3" => $dataNivelGeografico3 ?? [], // Ciudades - municipios
            'permissionsUpdateSapCode' => $permissionsUpdateSapCode, // Permiso para actualizar código SAP
            'status' => 200,
        ];
        return $response;
    }

    public function actionVerificarCorreoCliente()
    {
        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $user = User::find()
                ->where(['email' => $request['correoElectronicoCliente']])
                ->andWhere(['<>', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo']])
                ->one();

            if (!is_null($user)) {

                $email = $user->email;

                if($email!=''){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'emailAnotherFuncionario'),
                        'data' => ['available' => false, 'correoElectronicoCliente' => ''],
                        'status' => 200,
                    ];
    
                    return HelperEncryptAes::encrypt($response, true);
                }

            }
            /*
            $cliente = Clientes::find()->where(['correoElectronicoCliente' => $request['correoElectronicoCliente']])->one();

            if (isset($cliente)) {

                $correo = $cliente->correoElectronicoCliente;

                if($correo!=''){
                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'emailAnotherClient'),
                        'data' => ['available' => false, 'correoElectronicoCliente' => $request['correoElectronicoCliente']],
                        'status' => 200,
                    ];
    
                    return HelperEncryptAes::encrypt($response, true);
                }
                
            }
            */
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => ['available' => true],
                'status' => 200,
            ];

            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionVerificarIdentificacionCliente()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $cliente = Clientes::find()->where(['numeroDocumentoCliente' => $request['numeroDocumentoCliente']])->one();

            if (isset($cliente)) {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'identificationAnother'),
                    'data' => ['available' => false, 'numeroDocumentoCliente' => $request['numeroDocumentoCliente']],
                    'status' => 200,
                ];

                return HelperEncryptAes::encrypt($response, true);
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => ['available' => true],
                'status' => 200,
            ];

            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionNivelGeografico1()
    {
        //*** Fin desencriptación POST ***//
        $dataNivelGeografico1 = [];
        $modelNivelGeografico1 = NivelGeografico1::find()
        ->where(["estadoNivelGeografico1" => yii::$app->params['statusTodoText']['Activo'] ])
        ->orderBy(['nomNivelGeografico1' => SORT_ASC])
        ->all();

        foreach ($modelNivelGeografico1 as $row) {
            $dataNivelGeografico1[] = array(
                "id" => (int) $row['nivelGeografico1'],
                "val" => $row['nomNivelGeografico1'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataNivelGeografico1' => $dataNivelGeografico1 ?? [],
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    public function actionNivelGeografico2()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//
            $dataNivelGeografico2 = [];
            $modelNivelGeografico2 = NivelGeografico2::find()
            ->where(["estadoNivelGeografico2" => yii::$app->params['statusTodoText']['Activo'] ])
            ->andWhere(['IN', 'idNivelGeografico1', $request['idNivelGeografico1'] ])
            ->orderBy(['nomNivelGeografico2' => SORT_ASC])
            ->all();

            foreach ($modelNivelGeografico2 as $row) {
                $dataNivelGeografico2[] = array(
                    "id" => (int) $row['nivelGeografico2'],
                    "val" => $row['nomNivelGeografico2'],
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'dataNivelGeografico2' => $dataNivelGeografico2 ?? [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionNivelGeografico3()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $dataNivelGeografico3 = [];
            $modelNivelGeografico3 = NivelGeografico3::find()
            ->where(["estadoNivelGeografico3" => yii::$app->params['statusTodoText']['Activo'] ])
            ->andWhere(['IN', 'idNivelGeografico2', $request['idNivelGeografico2'] ])
            ->orderBy(['nomNivelGeografico3' => SORT_ASC])
            ->all();

            foreach ($modelNivelGeografico3 as $row) {
                $dataNivelGeografico3[] = array(
                    "id" => (int) $row['nivelGeografico3'],
                    "val" => $row['nomNivelGeografico3'],
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'dataNivelGeografico3' => $dataNivelGeografico3 ?? [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionFuncionariosRadicado()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $codigoTipoRadicado = CgTiposRadicados::findOne(['idCgTipoRadicado' => $request['idCgTipoRadicado']]);

            # Si es diferente al tipo radicado Entrada y Pqrs mostrará solo el usuario logueado, de lo contrario muestra los usuarios segun la dependencia tramitadora.
            if(
                $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiEntrada'] && 
                $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiFactura'] && 
                $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiPqrs'] &&
                $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiSalida'] &&
                $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['comunicacionInterna']
            ){
               //filtro no lo hace por la dependencia si no por el usuario logueado
                $funcionarios = UserDetalles::find()
                    ->select(['userDetalles.idUser', 'userDetalles.nombreUserDetalles', 'userDetalles.apellidoUserDetalles', 'userDetalles.cargoUserDetalles']);
                    //->innerJoin('user', 'user.id = userDetalles.idUser')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'user', ['userDetalles' => 'idUser', 'user' => 'id']);
                    //->innerJoin('gdTrdDependencias', 'gdTrdDependencias.idGdTrdDependencia = user.idGdTrdDependencia')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'gdTrdDependencias', ['user' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);    
                $funcionarios = $funcionarios->where(['userDetalles.idUser' =>  Yii::$app->user->identity->id])
                    ->andWhere(['user.idGdTrdDependencia' => $request['idTrdDepeUserTramitador']])
                    ->all();

            } else { 
   
                $funcionarios = UserDetalles::find()
                    ->select(['userDetalles.idUser', 'userDetalles.nombreUserDetalles', 'userDetalles.apellidoUserDetalles', 'userDetalles.cargoUserDetalles']);
                    //->innerJoin('user', 'user.id = userDetalles.idUser')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'user', ['userDetalles' => 'idUser', 'user' => 'id']);
                    //->innerJoin('gdTrdDependencias', 'gdTrdDependencias.idGdTrdDependencia = user.idGdTrdDependencia')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'gdTrdDependencias', ['user' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
                $funcionarios =  $funcionarios->where(['user.status' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['user.idGdTrdDependencia' => $request['idTrdDepeUserTramitador']])
                ->all();
            }

            $codigoTipoRadicado = $codigoTipoRadicado['codigoCgTipoRadicado'];


            if (isset($funcionarios)) {

                foreach ($funcionarios as $row) {
                    $datafuncionarios[] = array(
                        "id" => (int) $row['idUser'],
                        "val" => $row['nombreUserDetalles'] . ' ' . $row['apellidoUserDetalles'] . ' - ' . $row['cargoUserDetalles'],
                    );
                }

            } else {

                $datafuncionarios[] = array(
                    "id" => "",
                    "val" => Yii::t('app', 'noDependenciesFound'),
                );

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'funcionarios' => $datafuncionarios ?? [],
                'codiTipoRadicado' => $codigoTipoRadicado ?? 0,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionDependenciasRadicado()
    {
        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//
            $codigoTipoRadicado = 0;    $dataGdTrdDependenciasTemp = [];

            // Si el tipo de radicado es entrada se deben traer todas las dependencias siempre y cuando exista registro
            // en la tabla trd y se encuentre activa para esa dependencia.
            if (!empty($request['idGdTrdTipoDocumental']) && !empty($request['idCgTipoRadicado'])) {

                $idGdTrdTipoDocumental = $request["idGdTrdTipoDocumental"];

                $codigoTipoRadicado = CgTiposRadicados::findOne(['idCgTipoRadicado' => $request['idCgTipoRadicado']]);

                # Si el tipo de radicado es diferente a entrada y pqrs solo se debe listar la dependencia a la que pertenece el usuario
                if($request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiEntrada']
                    && $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiFactura']
                    && $request['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiPqrs'])
                { // Diferente de entrada y pqrs

                    $GdTrdDependenciasAll =   [];
                    $relatedDependency =   [];
                    $unrelatedDependency =   [];

                    $GdTrdDependencias = GdTrdDependencias::find();
                        //->innerJoin('gdTrd', 'gdTrdDependencias.idGdTrdDependencia = gdTrd.idGdTrdDependencia')
                        $GdTrdDependencias = HelperQueryDb::getQuery('innerJoin', $GdTrdDependencias, 'gdTrd', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdTrd' => 'idGdTrdDependencia']);
                        $GdTrdDependencias =  $GdTrdDependencias->where(['gdTrdDependencias.idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
                    ->all();

                } else { 
                    # Si es tipo radicado es entrada o pqrs, muestra primero las dependencias activas que se relaciona con el tipo documental, y luego muestra las demás

                    $GdTrdDependencias =   [];                   

                    # Consulta de dependencias que se relacionan con el tipo documental
                    $relatedDependency = GdTrdDependencias::find();              
                        //->leftJoin('gdTrd', 'gdTrdDependencias.idGdTrdDependencia = gdTrd.idGdTrdDependencia')
                        $relatedDependency = HelperQueryDb::getQuery('leftJoin', $relatedDependency, 'gdTrd', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdTrd' => 'idGdTrdDependencia']);
                        $relatedDependency = $relatedDependency->where(['gdTrd.estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']]);
                        $relatedDependency = $relatedDependency->andWhere(['gdTrdDependencias.estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']]);
                        $relatedDependency = $relatedDependency->andWhere(['gdTrd.idGdTrdTipoDocumental' => $idGdTrdTipoDocumental])
                    ->all();

                    # Consulta de dependencias que no se relacionan con el tipo documental
                    $unrelatedDependency = GdTrdDependencias::find();                  
                        //->leftJoin('gdTrd', 'gdTrdDependencias.idGdTrdDependencia = gdTrd.idGdTrdDependencia')
                        $unrelatedDependency = HelperQueryDb::getQuery('leftJoin', $unrelatedDependency, 'gdTrd', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdTrd' => 'idGdTrdDependencia']);
                        $unrelatedDependency = $unrelatedDependency->where(['gdTrd.estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']]);
                        $unrelatedDependency = $unrelatedDependency->andWhere(['gdTrdDependencias.estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']]);
                        $unrelatedDependency = $unrelatedDependency->andWhere(['<>', 'gdTrd.idGdTrdTipoDocumental', $idGdTrdTipoDocumental])
                    ->all();

                    # Consulta de dependencias faltantes que no se relacionan con la TRD
                    $GdTrdDependenciasAll =   GdTrdDependencias::find()
                        ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->all();
                }

                $codigoTipoRadicado = $codigoTipoRadicado['codigoCgTipoRadicado'];
                    
                # Se valida cada consulta del modelo y se organiza el listado dependiendo del tipo radicado
                if (isset($GdTrdDependencias) || isset($relatedDependency) || isset($unrelatedDependency) ) {

                    foreach ($GdTrdDependencias as $key => $row) {
                        $dataGdTrdDependenciasTemp[$row['idGdTrdDependencia']] = array(
                            "id" => (int) $row['idGdTrdDependencia'],
                            "val" => $row['nombreGdTrdDependencia'] . ' - ' . $row['codigoGdTrdDependencia'],
                        );
                    }

                    foreach ($relatedDependency as $key => $row) {
                        $dataGdTrdDependenciasTemp[$row['idGdTrdDependencia']] = array(
                            "id" => (int) $row['idGdTrdDependencia'],
                            "val" => $row['nombreGdTrdDependencia'] . ' - ' . $row['codigoGdTrdDependencia'],
                        );
                    }

                    foreach ($unrelatedDependency as $key => $row) { 
                        $dataGdTrdDependenciasTemp[$row['idGdTrdDependencia']] = array(
                            "id" => (int) $row['idGdTrdDependencia'],
                            "val" => $row['nombreGdTrdDependencia'] . ' - ' . $row['codigoGdTrdDependencia'],
                        );
                     }

                    if(count($GdTrdDependenciasAll) > 0){ 
                       
                        foreach($GdTrdDependenciasAll as $key => $row){
    
                            $dataGdTrdDependenciasTemp[$row['idGdTrdDependencia']] = array(
                                "id" => (int) $row['idGdTrdDependencia'],
                                "val" => $row['nombreGdTrdDependencia'] . ' - ' . $row['codigoGdTrdDependencia'],
                            );
                        }
                    }

                    foreach($dataGdTrdDependenciasTemp as $key => $row){

                        $dataGdTrdDependencias[] = array(
                            "id" => (int) $row['id'],
                            "val" => $row['val']
                        );
                    }


                } else {

                    $dataGdTrdDependencias[] = array(
                        "id" => "",
                        "val" => Yii::t('app', 'noDependenciesFound'),
                    );
                }

            }else{

                if(!empty($request['idCgTipoRadicado'])){            
                    $codigoTipoRadicado = CgTiposRadicados::findOne(['idCgTipoRadicado' => $request['idCgTipoRadicado']]);
                    $codigoTipoRadicado = $codigoTipoRadicado['codigoCgTipoRadicado'];
                }

                
                # Consulta de dependencias faltantes que no se relacionan con la TRD
                $GdTrdDependenciasAll =   GdTrdDependencias::find()
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                $dataGdTrdDependencias = []; 

                foreach($GdTrdDependenciasAll as $row){
                    $dataGdTrdDependencias[] = array(
                        "id" => (int) $row['idGdTrdDependencia'],
                        "val" => $row['nombreGdTrdDependencia'] . ' - ' . $row['codigoGdTrdDependencia']
                    );
                }
                

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'dependencias' => $dataGdTrdDependencias ?? [],
                'codiTipoRadicado' => $codigoTipoRadicado,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionVencimientoRadicado()
    {        
        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//U2FsdGVkX1+xaTt20oM3tiXPBxFFakyilO1Y49nSSQzI3tPV1DbSbVqbKWAkxYKv
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            
            if(isset($request['tipoInput']) && $request['tipoInput'] == 'select'){
                $request['diasRestantes'] = '';
            }

            $fechaCreacion = null;
            if(isset($request['fechaDocumentoRadiRadicado'])){
                $fechaCreacion =  date("d-m-Y", strtotime($request['fechaDocumentoRadiRadicado']));
            }

            if(empty($request['fechaDocumentoRadiRadicado'])){
                $request['fechaDocumentoRadiRadicado'] = 0;
            }
            
            

            $calcularfechaVencimiento = self::calcularfechaVencimiento($request['idGdTrdTipoDocumental'],$request['diasRestantes'], $fechaCreacion);
            
            

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'fechaVencimientoRadiRadicados' => $calcularfechaVencimiento['fechaVencimientoRadiRadicados'] ?? Yii::t('app', 'specifyingDate'),
                'diasRestantes' => $calcularfechaVencimiento['diasRestantes'],
                'status' => 200,
            ];

            //return $response;
            return HelperEncryptAes::encrypt($response, true);
                     

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }

    /* Información de los remitentes destinatarios, si es un cliente o funcionario */
    public function actionDestinatario()
    {
        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $dataClientes = [];

            if(isset($request['idcliente'])){

                // Este foreach re estrucutra la información del $request NO ELIMINAR ya que el frontend envia al mismo servicio en formas diferentes para consultar la información
                foreach($request['idcliente'] as $key => $idRemitente){
                    // Verifica
                    if( is_string($key) ){
                        $request['idcliente'][0][$key] = $idRemitente;
                        unset($request['idcliente'][$key]);
                        break;
                    }
                }

                // Realisa la busqueda de los remitentes
                $i = 0;
                foreach($request['idcliente'] as $key => $idRemitente){

                    // Se valida si el remitente es cliente o el usuario ya segun como corresponda se va a una tabla o a otra
                    if ( isset($idRemitente['cliente']) ) {

                        $modelClientes = Clientes::find()->where(['idCliente' => $idRemitente['cliente']])->one();

                        $dataClientes[$i] = [
                            "isCiudadano" => false,

                            "nombreCliente" => $modelClientes['nombreCliente'],
                            "numeroDocumentoCliente" => $modelClientes['numeroDocumentoCliente'],
                            "direccionCliente" => $modelClientes['direccionCliente'],
                            "idNivelGeografico1" => $modelClientes['idNivelGeografico1'],
                            "idNivelGeografico2" => $modelClientes['idNivelGeografico2'],
                            "idNivelGeografico3" => $modelClientes['idNivelGeografico3'],
                            "correoElectronicoCliente" => $modelClientes['correoElectronicoCliente'],
                            "telefonoCliente" => $modelClientes['telefonoCliente'],
                            "idTipoPersona" => $modelClientes['idTipoPersona'],
                            "codigoSap" => $modelClientes['codigoSap'],
                            "idCliente" => array( 'cliente' => $modelClientes['idCliente'] ),
                        ];

                        /** Validar si es un ciudadano */
                        $modelClienteCiudadano = ClientesCiudadanosDetalles::find()->where(['idCliente' => $idRemitente['cliente']])->one();
                        if ($modelClienteCiudadano != null) {
                            $dataClientes[$i]['isCiudadano'] = true;

                            $dataClientes[$i]['generoClienteCiudadanoDetalle'] = $modelClienteCiudadano['generoClienteCiudadanoDetalle'];
                            $dataClientes[$i]['rangoEdadClienteCiudadanoDetalle'] = $modelClienteCiudadano['rangoEdadClienteCiudadanoDetalle'];
                            $dataClientes[$i]['vulnerabilidadClienteCiudadanoDetalle'] = $modelClienteCiudadano['vulnerabilidadClienteCiudadanoDetalle'];
                            $dataClientes[$i]['etniaClienteCiudadanoDetalle'] = $modelClienteCiudadano['etniaClienteCiudadanoDetalle'];
                        }
                        $i++;
                    } else if(isset($idRemitente['user'])) {
        
                        $modelUser = UserDetalles::find()->where(['idUser' => $idRemitente['user']])->one();

                        # Se obtiene los ids de cada Tabla
                        $tipoPersona = TiposPersonas::findOne(['tipoPersona' => 'Funcionario']);
                        $geografico1 = NivelGeografico1::findOne(['nomNivelGeografico1' => Yii::$app->params['nivelGeograficoText']['nivelGeograficoPais']]);
                        $geografico2 = NivelGeografico2::findOne(['nomNivelGeografico2' => Yii::$app->params['nivelGeograficoText']['nivelGeograficoDepartamento']]);
                        $geografico3 = NivelGeografico3::findOne(['nomNivelGeografico3' => Yii::$app->params['nivelGeograficoText']['nivelGeograficoMunicipio']]);
                        
                        $dataClientes[$i] = [
                            "isCiudadano" => false,

                            "nombreCliente" => $modelUser->nombreUserDetalles. ' '.$modelUser->apellidoUserDetalles,
                            "numeroDocumentoCliente" => $modelUser->documento,
                            "direccionCliente" => $modelUser->user->gdTrdDependencia->nombreGdTrdDependencia,
                            "idNivelGeografico1" => $geografico1->nivelGeografico1, //Colombia
                            "idNivelGeografico2" => $geografico2->nivelGeografico2, //'Cundinamarca'
                            "idNivelGeografico3" => $geografico3->nivelGeografico3, //'Bogota D.C'
                            "correoElectronicoCliente" => $modelUser->user->email,
                            "telefonoCliente" => ' ',
                            "codigoSap" => '',
                            "idTipoPersona" => $tipoPersona->idTipoPersona, //funcionario
                            "idCliente" => array( 'user' => $modelUser['idUser'] ),
                        ];
                        $i++;
                    }
                }                

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    "modelClientes" => $dataClientes,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            else{
                
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion') . ', ' . Yii::t('app', 'usuarioNoExiste'),
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
            }

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }

    /** Funcion para obtener las transacciones a ignorar por radicado */
    public static function getIgnoreTransactions($ignoreTransacions, $permissionVoBo, $isUserJefe, $infoRadicado, $escenario = 'unknown')
    {        
        $ignoreTransacions[] = 'correspondenceMatch';       # combinar correspondencia
        $ignoreTransacions[] = 'signDocument';              # firmar documento
        $ignoreTransacions[] = 'archiveFiling';             # archivar radicado
        $ignoreTransacions[] = 'publishDocument';           # publicar documento

        if($escenario == 'update'){
            $ignoreTransacions[] = 'edit';                  # editar
        }

        /* No mostrar el boton de excluir expediente en el index */ 
        if($escenario != 'view'){
            $ignoreTransacions[] = 'excludeTheFile';        # excluir radicado del expediente
            $ignoreTransacions[] = 'includeInFile';         # incluir radicado en expediente
        }

        /** Transacciones a ignorar cuando el radicado es temporal */
        if ($infoRadicado['isRadicado'] == false || $infoRadicado['isRadicado'] == 0) {

            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'printStickers';          # impriir etiqueta
            $ignoreTransacions[] = 'sendMail';               # respuesta por correo
            $ignoreTransacions[] = 'annulationRequest';      # solicitar anulación
            $ignoreTransacions[] = 'annulationApproval';     # aceptar anulación
            $ignoreTransacions[] = 'refusalAnnulation';      # rechazar anulación
            $ignoreTransacions[] = 'delivered';              # devolver radicado
            $ignoreTransacions[] = 'shipping';               # envio de radicado
            $ignoreTransacions[] = 'returnDelivery';         # devolver envio
            $ignoreTransacions[] = 'finalizeFiling';         # finalizar radicado
            $ignoreTransacions[] = 'correspondenceTemplate'; # plantilla correspondencia
            $ignoreTransacions[] = 'shippingReady';          # listo para enviar
            $ignoreTransacions[] = 'archiveFiling';          # archivar radicado
            $ignoreTransacions[] = 'publishDocument';        # publicar documento
        }

        /** Transacciones a ignorar cuando el radicado es inactivo */
        if ($infoRadicado['estadoRadiRadicado'] == Yii::$app->params['statusTodoText']['Inactivo']) {

            $ignoreTransacions[] = 'discardConsecutive';     # descartar radicado 
            $ignoreTransacions[] = 'edit';                   # editar
            $ignoreTransacions[] = 'printStickers';          # impriir etiqueta
            $ignoreTransacions[] = 'sendMail';               # respuesta por correo
            $ignoreTransacions[] = 'schedule';               # agendar radicado
            $ignoreTransacions[] = 'send';                   # reasignar radicado
            $ignoreTransacions[] = 'annulationRequest';      # solicitar anulación
            $ignoreTransacions[] = 'attachment';             # carga anexos
            $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            $ignoreTransacions[] = 'copyInformaded';         # copiar a informados
            $ignoreTransacions[] = 'voboRequest';            # solicitud de visto bueno
            $ignoreTransacions[] = 'vobo';                   # aprobar visto bueno
            $ignoreTransacions[] = 'returnFiling';           # devolver radicado
            $ignoreTransacions[] = 'finalizeFiling';         # finalizar radicado
            $ignoreTransacions[] = 'correspondenceTemplate'; # plantilla correspondencia
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'loadFormat';             # cargar plantilla
            $ignoreTransacions[] = 'downloadDocumentPackage';# descargar paquete de documentos
            $ignoreTransacions[] = 'shippingReady';          # listo para enviar
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
            $ignoreTransacions[] = 'withdrawal';
            $ignoreTransacions[] = 'excludeTheFile';         # excluir radicado del expediente
        }

        /** Si el radicado ya esta finalizado */
        if ($infoRadicado['estadoRadiRadicado'] == Yii::$app->params['statusTodoText']['Finalizado']) {

            $ignoreTransacions[] = 'edit';                   # editar
            $ignoreTransacions[] = 'printStickers';          # impriir etiqueta
            $ignoreTransacions[] = 'sendMail';               # respuesta por correo
            $ignoreTransacions[] = 'schedule';               # agendar radicado
            $ignoreTransacions[] = 'send';                   # reasignar radicado
            $ignoreTransacions[] = 'annulationRequest';      # solicitar anulación
            $ignoreTransacions[] = 'attachment';             # carga anexos
            $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            $ignoreTransacions[] = 'copyInformaded';         # copiar a informados
            $ignoreTransacions[] = 'voboRequest';            # solicitud de visto bueno
            $ignoreTransacions[] = 'vobo';                   # aprobar visto bueno
            $ignoreTransacions[] = 'returnFiling';           # devolver radicado
            $ignoreTransacions[] = 'finalizeFiling';         # finalizar radicado
            $ignoreTransacions[] = 'correspondenceTemplate'; # plantilla correspondencia
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'loadFormat';             # cargar plantilla
            $ignoreTransacions[] = 'downloadDocumentPackage';# descargar paquete de documentos
            $ignoreTransacions[] = 'shippingReady';          # listo para enviar
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
            $ignoreTransacions[] = 'withdrawal';
            $ignoreTransacions[] = 'excludeTheFile';         # excluir radicado del expediente
        }

        /** 
         * Si el radicado NO tiene tipo documental
         * Solo podrá editar, ver y crear.
         * Solo el raicado de tipo 
         */
        if ($infoRadicado['idTrdTipoDocumental'] == '0'){

            $ignoreTransacions[] = 'printStickers';          # impriir etiqueta
            $ignoreTransacions[] = 'sendMail';               # respuesta por correo
            $ignoreTransacions[] = 'schedule';               # agendar radicado
            $ignoreTransacions[] = 'send';                   # reasignar radicado
            $ignoreTransacions[] = 'annulationRequest';      # solicitar anulación
            $ignoreTransacions[] = 'attachment';             # carga anexos
            $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            $ignoreTransacions[] = 'copyInformaded';         # copiar a informados
            $ignoreTransacions[] = 'voboRequest';            # solicitud de visto bueno
            $ignoreTransacions[] = 'vobo';                   # aprobar visto bueno
            $ignoreTransacions[] = 'returnFiling';           # devolver radicado
            $ignoreTransacions[] = 'finalizeFiling';         # finalizar radicado
            $ignoreTransacions[] = 'correspondenceTemplate'; # plantilla correspondencia
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'loadFormat';             # cargar plantilla
            $ignoreTransacions[] = 'downloadDocumentPackage';# descargar paquete de documentos
            $ignoreTransacions[] = 'shippingReady';          # listo para enviar
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
            $ignoreTransacions[] = 'withdrawal';
            $ignoreTransacions[] = 'excludeTheFile';         # excluir radicado del expediente
        }

        /**
        * Validar si la transacción esta en estado de devuelto al ciudadano
        */
        if ($infoRadicado['estadoRadiRadicado'] == Yii::$app->params['statusTodoText']['DevueltoAlCiudadano']) {
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
        }

        /**
        * Validar si la transacción esta en estado de devuelto al ciudadano
        */
        if($infoRadicado['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiPqrs']){
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
            $ignoreTransacions[] = 'withdrawal';
        }

        /**
         * Validar transacciones a excluir si el usuario Logueado NO es el dueño del radicado
        */
        
        # Consulta la información del usuario logueado
        $modelUser = User::findOne(['id' => Yii::$app->user->identity->id]);

        if ($infoRadicado['user_idTramitador'] != Yii::$app->user->identity->id) {
            $ignoreTransacions[] = 'edit';                   # editar
            $ignoreTransacions[] = 'returnPqrToCitizen';     # devolver radicado pqrs
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
                    
            if ($escenario == 'update') {
                if ($infoRadicado['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiEntrada'] 
                    && $infoRadicado['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiFactura']
                    && $infoRadicado['idCgTipoRadicado'] != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) 
                {
                    $ignoreTransacions[] = 'attachment';
                    $ignoreTransacions[] = 'attachmentMain';
                    $ignoreTransacions[] = 'loadFormat';
                    $ignoreTransacions[] = 'printStickers';
                }

            } else {

                /** Validar si el usuario logueado es tramitador o informado */
                $radiInformados = RadiInformados::find()
                    ->select(['idRadiInformado']) // Para que la consulta pese menos
                    ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])
                    ->andWhere(['idUser' => Yii::$app->user->identity->id])
                    ->limit(1) // Para que la consulta pese menos
                ->one();

                if ($radiInformados == null) {

                    $ignoreTransacions[] = 'downloadDocumentPackage';

                    /** Validación para tipo usuario "Ventanilla" sea el dueño del radicado o no lo sea,  
                    * va aparecer el boton de imprimir sticker, en radicado 'ENTRADA Y PQRS'. 
                    **/
                    if( $modelUser->idUserTipo != Yii::$app->params['tipoUsuario']['Ventanilla de Radicación'])
                    {
                        $ignoreTransacions[] = 'printStickers';
                        $ignoreTransacions[] = 'attachment';
                        $ignoreTransacions[] = 'attachmentMain';
                        $ignoreTransacions[] = 'loadFormat';
                    }

                    //$ignoreTransacions[] = 'printStickers';
                }
                /** Validar si el usuario logueado es tramitador o informado */
            }

            $ignoreTransacions[] = 'schedule';               # agendar radicado
            $ignoreTransacions[] = 'send';                   # reasignar radicado
            $ignoreTransacions[] = 'annulationRequest';      # solicitar anulación
            $ignoreTransacions[] = 'discardConsecutive';     # descartar radicado 
            $ignoreTransacions[] = 'voboRequest';            # solicitud de visto bueno
            $ignoreTransacions[] = 'vobo';                   # aprobar visto bueno
            // $ignoreTransacions[] = 'attachment';             # carga anexos
            // $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'finalizeFiling';         # finalizar radicado
            $ignoreTransacions[] = 'returnFiling';           # devolver radicado
            $ignoreTransacions[] = 'sendMail';               # respuesta por correo
            $ignoreTransacions[] = 'shippingReady';          # listo para enviar
            $ignoreTransacions[] = 'excludeTheFile';         # excluir radicado del expediente
            $ignoreTransacions[] = 'correspondenceTemplate'; # plantilla correspondencia
            $ignoreTransacions[] = 'loadFormat';             # cargar plantilla
            $ignoreTransacions[] = 'downloadDocumentPackage';# descargar paquete de documentos
            $ignoreTransacions[] = 'withdrawal';

            if($modelUser->idUserTipo != Yii::$app->params['tipoUsuario']['Ventanilla de Radicación']){
                $ignoreTransacions[] = 'copyInformaded';         # copiar a informados
                $ignoreTransacions[] = 'printStickers';          # impriir etiqueta
                $ignoreTransacions[] = 'attachment';             # carga anexos
                $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            }
        }
        
        /** Validar si un radicado ya fue incluido en un expediente */
        $modelInclusionExpediente = GdExpedientesInclusion::find()
            ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])
        ->one();

        if(!empty($modelInclusionExpediente)){

            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente

            # Si el expediente no esta activo no aparece la acción de excluir expediente
            $modelExpedient = GdExpedientes::findOne(['estadoGdExpediente' => Yii::$app->params['statusTodoText']['Activo'], 'idGdExpediente' => $modelInclusionExpediente->idGdExpediente]);

            if(is_null($modelExpedient)){
                $ignoreTransacions[] = 'excludeTheFile';
            } 

        } else {
            $ignoreTransacions[] = 'excludeTheFile';
        }

        /**
         * Validar si el radicado posee una plantilla cargada con combinacion de correspondencia
         */
        # Plantillas con combinación de correspondencia
        $plantillaCorrespondencia = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
            ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado'], 'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
            ->limit(1)
        ->one();

        # Plantillas SIN combinación de correspondencia
        $plantillaSinCorrespondencia = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
            ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado'], 'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Inactivo']])
            ->limit(1)
        ->one();

        # Plantillas Firmadas
        $plantillaFirmada = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
            ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])
            ->andWhere(['or',
                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']],
                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente']],
            ])
            ->limit(1)
        ->one();

        # Plantillas Firmadas
        $plantillaFirmaEnProceso = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
            ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado'], 'estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesandoFirma']])
            ->limit(1)
        ->one();

        # Anexos cargados
        $anexo = (int) RadiDocumentos::find()->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])->count();

        if ($anexo > 0) {
            $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
        } elseif ($anexo == 0) {
            $ignoreTransacions[] = 'attachment';             # carga anexos
        }

        if ($plantillaCorrespondencia == null && $plantillaSinCorrespondencia == null) {
            //$ignoreTransacions[] = 'annulationRequest';
            //$ignoreTransacions[] = 'copyInformaded';
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
            $ignoreTransacions[] = 'excludeTheFile';         # excluir radicado del expediente
        }

        /** Si el radicado es nuevo */
        if ($plantillaCorrespondencia == null && $plantillaSinCorrespondencia == null && $anexo == 0) {
            $ignoreTransacions[] = 'voboRequest';            # solicitud de visto bueno
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente          
        }

        if ($plantillaFirmada == null && $anexo == 0) {
            $ignoreTransacions[] = 'downloadDocumentPackage'; // Radicado sin documentos a descargar
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente
        } 

        if ($plantillaFirmada != null || $plantillaFirmaEnProceso != null) {

            $ignoreTransacions[] = 'loadFormat';             # cargar plantilla
            $ignoreTransacions[] = 'includeInFile';          # incluir radicado en expediente

            if (in_array($infoRadicado['idCgTipoRadicado'], [Yii::$app->params['idCgTipoRadicado']['radiEntrada'], Yii::$app->params['idCgTipoRadicado']['radiSalida'], Yii::$app->params['idCgTipoRadicado']['radiFactura']])) {
                $ignoreTransacions[] = 'attachment';             # carga anexos
                $ignoreTransacions[] = 'attachmentMain';         # carga primer anexo
            }
        }

        if( in_array($infoRadicado['idCgTipoRadicado'], [Yii::$app->params['idCgTipoRadicado']['radiEntrada'], Yii::$app->params['idCgTipoRadicado']['radiPqrs'], Yii::$app->params['idCgTipoRadicado']['radiFactura']]) ) {
            if ($plantillaFirmada != null || ($plantillaFirmaEnProceso == null && $plantillaCorrespondencia == null)) {
                $ignoreTransacions[] = 'discardConsecutive';
            }
        } else {
            if ((boolean) $infoRadicado['isRadicado'] == true) {
                $ignoreTransacions[] = 'discardConsecutive';
            }
        }

        /**
         * Validar si el radicado puede enviar respuesta por correo
         ** Aplica cuando el radicado tenga anexos o plantillas Firmadas.... Si tiene plantillas, debe tener por lo menos una plantilla firmada
         ** opcion1 Solo cuando tenga anexos y no plantillas
         ** opcion2 Cuando tenga plantillas firmadas. (al menos una). y no tenga anexos
         ** opcion3 Cuando tenga plantillas firmadas. (al menos una). y tenga anexos
         */
        if (
            ($anexo == 0 && $plantillaCorrespondencia == null && $plantillaSinCorrespondencia == null) ||
            ( ($plantillaCorrespondencia != null || $plantillaSinCorrespondencia != null) &&  $plantillaFirmada == null )
        )
        {
            $ignoreTransacions[] = 'sendMail';
        }

        if (!in_array('shippingReady', $ignoreTransacions)) {
            /** Validar si el radicado tiene un documento firmado */
            if ($plantillaFirmada == null) {
                $ignoreTransacions[] = 'shippingReady';
            }
        }

        /** Validar si al radicado se le puede realizar una solicitud de anulacion */
        if (!in_array('annulationRequest', $ignoreTransacions)) {
            $radiRadicadoAnulado = RadiRadicadoAnulado::find()->select(['idRadiRadicadoAnulado'])
                ->where(['idRadicado' => $infoRadicado['idRadiRadicado']])
                ->andWhere(['or',
                    ['idEstado' => Yii::$app->params['statusAnnulationText']['SolicitudAnulacion']],
                    ['idEstado' => Yii::$app->params['statusAnnulationText']['AceptacionAnulacion']]
                ])->limit(1)
            ->one();
            if ($radiRadicadoAnulado != null) {
                $ignoreTransacions[] = 'annulationRequest';
            }
        }
        /** Fin Consultar si al radicado se le puede realizar una solicitud de anulacion */

        /**
         * Validar que el radicado Puede realizar aprobación de visto bueno
         */
        if (!in_array('vobo', $ignoreTransacions)) {
            $modelTransacionVoBo = CgTransaccionesRadicados::find()->select(['idCgTransaccionRadicado'])->where(['actionCgTransaccionRadicado' =>'voboRequest'])->one();
    
            # Consultando en el log  del radicado si tiene una solicitud de VoBo
            $modelRadiLogVoBo = RadiLogRadicados::find()
                ->select(['idRadiLogRadicado']) // Para que la consulta pese menos
                ->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])
                ->andWhere(['idTransaccion' => $modelTransacionVoBo['idCgTransaccionRadicado']])
                ->limit(1) // Para que la consulta pese menos
            ->one();
    
            if ($modelRadiLogVoBo == null || $permissionVoBo == false || $isUserJefe == false || ($plantillaCorrespondencia == null && $plantillaSinCorrespondencia == null && $anexo == 0)) {
                $ignoreTransacions[] = 'vobo';                   # aprobar visto bueno
            }
        }

        /**
         * Validar si el radicado puede finalizar el trámite
         */
        if (!in_array('finalizeFiling', $ignoreTransacions)) {
            
            $gdExpedientesInclusion = GdExpedientesInclusion::find()->select(['idGdExpedienteInclusion'])->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])->one();
            $infoRadicado = RadiRadicados::find()->select(['idRadiRadicadoPadre'])->where(['idRadiRadicado' => $infoRadicado['idRadiRadicado']])->one();

            if ($gdExpedientesInclusion == null || ($anexo == 0 && $plantillaCorrespondencia == null && $infoRadicado == null)) {
                $ignoreTransacions[] = 'finalizeFiling';
            }
        }

        /* 
        * Se valida si el usuario tiene registro de usuario anterior
        */
        if ($infoRadicado['user_idTramitadorOld'] == null && $infoRadicado['user_idTramitador']==$infoRadicado['user_idCreador']) {
            $ignoreTransacions[] = 'returnFiling';
        }

        return $ignoreTransacions;
    }

    public function actionRadiMultiAcciones(){

        $jsonSend = Yii::$app->request->post('jsonSend');

        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            # Se valida si el usuario logueado tiene permisos para dar VoBo 'version1%radicacion%transacciones%vobo'
            $permissionVoBo = HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsVoBo'], Yii::$app->user->identity->rol->rolesTiposOperaciones);
            # Se valida si el usuario logueado es de tipo JEFE
            $isUserJefe = (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']);

            $dataSelected = $request['event'];
            $arrayCgTiposRadicados = [];
            $count = 1;
            $module = 'unknown';  //Variable que determina mostrar u ocultar la transaccion en un determinado modulo

            $ignoreTransacions = []; // Array de transacciones a ignorar
            if (isset($request['module'])) {
                if ($request['module'] == 'view') {
                    $ignoreTransacions[] = 'view';
                    $module = 'view';
                }
            }

            // Información del radicado seleccionado
            $dataRadicadosSelected = [];

            foreach($dataSelected as $key  => $rowDataSelected){

                $idRadiRadicado = RadiRadicados::find()->select(['idRadiRadicado', 'user_idCreador', 'user_idTramitador', 'user_idTramitadorOld', 'idCgTipoRadicado', 'estadoRadiRadicado', 'idTrdTipoDocumental', 'numeroRadiRadicado', 'isRadicado'])->where(['idRadiRadicado' => $rowDataSelected['id']])->one();
                //$idUser = $idRadiRadicado->user_idTramitador;

                $dataRadicadosSelected[] = $idRadiRadicado;

                /** Llamado a funcion para obtener las transacciones a ignorar por radicado */
                /* Si $module != 'view' oculta la transacción */
                $ignoreTransacions = self::getIgnoreTransactions($ignoreTransacions, $permissionVoBo, $isUserJefe, $idRadiRadicado, $module);

                $CgTiposRadicados = CgTiposRadicados::find()
                    ->where(['idCgTipoRadicado' => $idRadiRadicado['idCgTipoRadicado']])
                        ->one();

                // armar array de tipos de radicados
                $arrayRadiRadicado[] = ['idCgTipoRadicado' =>  $CgTiposRadicados['idCgTipoRadicado']];

                $arrayTipoRadi[$CgTiposRadicados['idCgTipoRadicado']] = ['idCgTipoRadicado' => $CgTiposRadicados['idCgTipoRadicado'],'codigoCgTipoRadicado' =>  $CgTiposRadicados['codigoCgTipoRadicado']];

                # Se agrega mensaje alerta cuando el radicado ENTRADA y PQRSD tiene 'Sin tipo documental'
                if ($idRadiRadicado->user_idTramitador == Yii::$app->user->identity->id 
                    && $idRadiRadicado->idTrdTipoDocumental  == '0'
                    && ($idRadiRadicado->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] 
                    || $idRadiRadicado->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiFactura']
                    || $idRadiRadicado->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs'])) {

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'messageSinTipoDoc',[
                            'numFile' => $idRadiRadicado->numeroRadiRadicado,
                        ]),
                        'type' => 'danger'
                    ];
                }
            }

            // tipo de radicado y cuantas veces se repite 
            foreach($arrayTipoRadi as $keyTipo => $valueTipo){
                for($i=0; $i<count($arrayRadiRadicado)-1; $i++){

                    if($arrayTipoRadi[$keyTipo]['idCgTipoRadicado']  == $arrayRadiRadicado[$i+1]['idCgTipoRadicado']){
                        $count++;
                    }
                }

                $arrayCgTiposRadicados[$keyTipo] = [
                    'idCgTipoRadicado' => $valueTipo['idCgTipoRadicado'],
                    'codigoCgTipoRadicado' => $valueTipo['codigoCgTipoRadicado'],
                    'count' => $count
                ];
                $count = 0;
            }

            $radicadoTransacciones = self::radicadoTransacciones($arrayCgTiposRadicados,true, $ignoreTransacions);
            $radicadoTransaccionesEnd = $radicadoTransacciones;

            // Si es un radicado se valdia que tenga documentos en proceso de firma física
            // para retornar el bóton de descarga de etiqueta externa
            $procesoActivoFirmaFisica = false;
            $firmadoFisicamente = false;
            $modelRadicadoHijo = null;
            if(count($dataSelected) === 1) {
                foreach($dataSelected as $key => $rowDataSelected) {
                    // Consulta de los documentos por el id del radicado
                    $radiDocumentos = RadiDocumentosPrincipales::find()->where(['idRadiRadicado' => $rowDataSelected['id']])->all();
                    foreach($radiDocumentos as $keyDoc => $radiDocumento) {
                        // Si existe un documento en proceso de firma física
                        // Se retorna true y se detienen los ciclos de foreach
                        if($radiDocumento->estadoRadiDocumentoPrincipal === Yii::$app->params['statusDocsPrincipales']['procesoFirmaFisica']) {
                            $procesoActivoFirmaFisica = true;
                            break;
                        }

                        if($radiDocumento->estadoRadiDocumentoPrincipal === Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']) {
                            $firmadoFisicamente = true;
                            break;
                        }
                    }

                    if($procesoActivoFirmaFisica || $firmadoFisicamente) {
                        break;
                    }
                }
            }
            // Si hay un documento en procesod de firma física
            if($procesoActivoFirmaFisica or $firmadoFisicamente) {
                // Consulta de operaciones por el id del rol
                $operacionesRoles = RolesOperaciones::find();
                $operacionesRoles = HelperQueryDb::getQuery('innerJoin', $operacionesRoles, 'rolesTiposOperaciones', ['rolesOperaciones' => 'idRolOperacion', 'rolesTiposOperaciones' => 'idRolOperacion']);
                $operacionesRoles = $operacionesRoles->where(['rolesTiposOperaciones.idRol' => Yii::$app->user->identity->idRol])->all();

                foreach($operacionesRoles as $operacionRol) {
                    foreach(Yii::$app->params['transaccionMostrarConfigManual'] as $key => $transaccion) {
                        if($operacionRol->nombreRolOperacion == $transaccion['route']) {
                            array_push($radicadoTransacciones, Yii::$app->params['transaccionMostrarConfigManual'][$key]);
                        }
                    }
                }

                $radicadoTransaccionesNew = [];
                foreach(Yii::$app->params['deleteTransacciones']['procesoFirmaFisica'] as $deleteTransacciones) {
                    foreach($radicadoTransacciones as $key => $radiTransaccion) {
                        if($deleteTransacciones['route'] !== $radiTransaccion['route']) {
                            $radicadoTransaccionesNew[] = $radiTransaccion;
                        }
                    }
                }

                $radicadoTransaccionesEnd = $radicadoTransaccionesNew;
            }

            if($firmadoFisicamente) {
                $radicadoTransaccionesNew = [];
                foreach(Yii::$app->params['deleteTransacciones']['procesoFirmaFisica'] as $deleteTransacciones) {
                    foreach($radicadoTransacciones as $key => $radiTransaccion) {
                        if($deleteTransacciones['route'] !== $radiTransaccion['route']) {
                            $radicadoTransaccionesNew[] = $radiTransaccion;
                        }
                    }
                }

                $radicadoTransaccionesEnd = $radicadoTransaccionesNew;
            }

            // Valida el botón de incluir en expediente
            $responseValidateButtonIncluirExpediente = $this->validateButtonIncluirExpedienteAndAttach($dataRadicadosSelected, $radicadoTransaccionesEnd);
            
            $responseValidandoBotonDetalleResoluciones = $this->validandoBotonDetalleResoluciones($arrayRadiRadicado, $responseValidateButtonIncluirExpediente);

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'OK',
                'dataTransacciones' => $responseValidandoBotonDetalleResoluciones,
                'status' => 200,
                'messageButton' => $notificacion ?? [],  //Notificación cuando el radicado tiene 'Sin tipo documental'
                'firmaFisica' => ($procesoActivoFirmaFisica || $firmadoFisicamente) ? true : false
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function validandoBotonDetalleResoluciones($tipoRadicado, $transaccionesActivas) {
        if (count($tipoRadicado) === 1) {
            $modelCgGeneral = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();

            if ($tipoRadicado[0]['idCgTipoRadicado'] === $modelCgGeneral->resolucionesIdCgGeneral) {
                array_push($transaccionesActivas, Yii::$app->params['transaccionMostrarConfigManual']['detalleResolucion']);
            }
        }

        return $transaccionesActivas;
    }

    public function validateButtonIncluirExpedienteAndAttach($dataRadicados, $transaccionesActivas) {
        $retornarBoton = true;
        $actionAttachment = false;
        $transaccionesActivasEnd = [];
        foreach($dataRadicados as $dataRadicado) {
            if($dataRadicado['isRadicado'] == false || $dataRadicado['isRadicado'] == 0) {
                $retornarBoton = false;
            } else {
                $modelRadicadoHijo = RadiRadicados::find()->where(['idRadiRadicadoPadre' => $dataRadicado['idRadiRadicado']])->all();
                if($modelRadicadoHijo) {
                    $retornarBoton = false;
                    foreach($modelRadicadoHijo as $modelHijo) {
                        if($modelHijo->isRadicado == true || $modelHijo->isRadicado == 1) {
                            $retornarBoton = true;
                        }
                    }
                }
            }
        }

        foreach($transaccionesActivas as $key => $transaccionActiva) {
            if($transaccionActiva['action'] !== 'includeInFile') {
                $transaccionesActivasEnd[] = $transaccionActiva;
            }

            if($transaccionActiva['action'] === 'attachmentMain' || $transaccionActiva['action'] === 'attachment') {
                $actionAttachment = true;
            }
        }

        
        if($retornarBoton) {
            if($dataRadicado['user_idTramitador'] == Yii::$app->user->identity->id){
                array_push($transaccionesActivasEnd, Yii::$app->params['validateTransacciones']['incluirExpediente']);
            }
        }

        if(!$actionAttachment) {
            if($dataRadicado['user_idTramitador'] == Yii::$app->user->identity->id){
                array_push($transaccionesActivasEnd, Yii::$app->params['validateTransacciones']['cargarAnexos']);
            }
        }

        return $transaccionesActivasEnd;
    }

    // Esta función muestra la información de las transacciones que se pueden hacer por tipo de radicado
    // segun el tipo de radicado se consulta las transacciones y con las transacciones se valida en operaciones
    // si existe el regitro si es asi lo envia de lo contrario no.
    // $ignoreTransacions = Array de transacciones a ignorar (actionCgTransaccionRadicado)
    public static function radicadoTransacciones($arrayRadiRadicado,$multiple = false, $ignoreTransacions = [])
    {      

        $idCgTipoRadicado =  $arrayRadiRadicado;
        if($multiple){

            $arrayMenor = [];
            $arrayCgTiposRadicados = [];

            foreach($arrayRadiRadicado as $key => $value){

                $cgTiposRadiTrans = CgTiposRadicadosTransacciones::find()
                ->where(['idCgTipoRadicado' => $arrayRadiRadicado[$key]['idCgTipoRadicado']])->all();

                array_push($arrayCgTiposRadicados,$arrayRadiRadicado[$key]['idCgTipoRadicado']);
                array_push($arrayMenor,count($cgTiposRadiTrans));

            }

            $minimo = min($arrayMenor); 

                for($i=0;$i<count($arrayMenor);$i++){

                    if($arrayMenor[$i] == $minimo){
                        $idCgTipoRadicado =  $arrayCgTiposRadicados[$i];
                    }
                }
        }

        $transaccionMostrar = [];
        $posicion = 0;

        $operacionesRoles = RolesOperaciones::find();
            //->innerJoin('rolesTiposOperaciones', 'rolesTiposOperaciones.idRolOperacion = rolesOperaciones.idRolOperacion')
        $operacionesRoles = HelperQueryDb::getQuery('innerJoin', $operacionesRoles, 'rolesTiposOperaciones', ['rolesOperaciones' => 'idRolOperacion', 'rolesTiposOperaciones' => 'idRolOperacion']);
        $operacionesRoles = $operacionesRoles->where(['rolesTiposOperaciones.idRol' => Yii::$app->user->identity->idRol])
                    ->all();

            $transaccionesTipo = CgTransaccionesRadicados::find();
                //->innerJoin('cgTiposRadicadosTransacciones', 'cgTiposRadicadosTransacciones.idCgTransaccionRadicado = cgTransaccionesRadicados.idCgTransaccionRadicado');
            $transaccionesTipo = HelperQueryDb::getQuery('innerJoin', $transaccionesTipo, 'cgTiposRadicadosTransacciones', ['cgTransaccionesRadicados' => 'idCgTransaccionRadicado', 'cgTiposRadicadosTransacciones' => 'idCgTransaccionRadicado']);
                
            // if($multiple){
            //     $transaccionesTipo = $transaccionesTipo->where(['in','cgTiposRadicadosTransacciones.idCgTipoRadicado', $arrayCgTiposRadicados]);
            // }else{
                $transaccionesTipo = $transaccionesTipo->where(['cgTiposRadicadosTransacciones.idCgTipoRadicado' => $idCgTipoRadicado]);
            //}

            $transaccionesTipo = $transaccionesTipo->andWhere(['cgTransaccionesRadicados.mostrarBotonCgTransaccionRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            
            $transaccionesTipo = $transaccionesTipo->orderBy(['cgTiposRadicadosTransacciones.orderCgTipoRadicado' =>'SORT_ASC'])->all();

        $transaccionTemporal = [];
        //print_r($transaccionesTipo);die();

        if($multiple){
            foreach($transaccionesTipo as $key => $transaccionRadicado){

                    if(count($arrayRadiRadicado) >= 2){
                        // Vienen varios tipos de radicado     
                        $actionMultiTipoRadicado = Yii::$app->params['actionMultiTipoRadicado'];
                        
                        /** Recorre todas las transacciones que se pueden realizar para un radicado*/
                        foreach($actionMultiTipoRadicado as $valueAction){

                            if(strcmp($transaccionRadicado->actionCgTransaccionRadicado, $valueAction) == 0){ 

                                //unset($transaccionTemporal[$key]);
                                //$transaccionTemporal = array_values($transaccionTemporal);
                               
                                $transaccionTemporal[] = $transaccionesTipo[$key];

                            }
                        }

                    }else{
                        
                        // Solo viene un tipo de radicado
                        foreach($arrayRadiRadicado as $keyRadi => $valueRadi){
                            // Si solo viene un tipo Verificar cuantas veces se repite
                            if($arrayRadiRadicado[$keyRadi]['count'] >= 2){
                                
                                if($arrayRadiRadicado[$keyRadi]['codigoCgTipoRadicado'] == Yii::$app->params['CgTipoRadicado']['radiEntrada']){
                                    
                                    $actionMultiEntrada = Yii::$app->params['actionMultiTipoRadicadoEntrada'];

                                    foreach($actionMultiEntrada as $valueAction){
                                        if(strcmp($transaccionRadicado->actionCgTransaccionRadicado, $valueAction) == 0){ 

                                            // unset($transaccionesTipo[$key]);
                                            // $transaccionesTipo = array_values($transaccionesTipo);

                                            $transaccionTemporal[] = $transaccionesTipo[$key];
                                        }
                                    }
                        
                                }else{

                                    // Acciones para salida
                                    $actionMultiTipoRadicado = Yii::$app->params['actionMultiTipoRadicado'];

                                    foreach($actionMultiTipoRadicado as $valueAction){
                
                                        if(strcmp($transaccionRadicado->actionCgTransaccionRadicado, $valueAction) == 0){ 
                
                                            //unset($transaccionTemporal[$key]);
                                            //$transaccionTemporal = array_values($transaccionTemporal);
                
                                            $transaccionTemporal[] = $transaccionesTipo[$key];
                                        }
                                    }

                                }


                            }

                        }

                    }
        
            }  
            //print_r($transaccionTemporal);die();
        }

        if(count($transaccionTemporal) == 0){ $transaccionTemporal = $transaccionesTipo; } 

            foreach($operacionesRoles as $operacionRol){
                foreach($transaccionTemporal as $transaccionRadicado){
    
                    /** Verificar si la transaccion no se encuentra en el array de transacciones a ignorar */
                    if (!in_array($transaccionRadicado->actionCgTransaccionRadicado, $ignoreTransacions)) {

                        if($operacionRol->nombreRolOperacion == $transaccionRadicado->rutaAccionCgTransaccionRadicado){
                            $transaccionMostrar[$posicion]['route']  = $transaccionRadicado->rutaAccionCgTransaccionRadicado;
                            $transaccionMostrar[$posicion]['action'] = $transaccionRadicado->actionCgTransaccionRadicado;
                            $transaccionMostrar[$posicion]['title']  = $transaccionRadicado->titleCgTransaccionRadicado;
                            $transaccionMostrar[$posicion]['icon']   = $transaccionRadicado->iconCgTransaccionRadicado;
                            $transaccionMostrar[$posicion]['data']   = '';
                            $posicion++;
                        }

                    }
                }
            }

        return $transaccionMostrar;
    }
   
 
    public function actionGetRemitenteByEmail($request)
    {
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        //*** Fin desencriptación GET ***//

        $isMailRecordExists = false;
        $idMailRemitente = null;

        $email = $request['email'];
        // verificacion de facturas y extraccion de variables
        $supplier = [];
        if (isset($request['mailBox']) && isset($request['dataEmail']) && isset($request['dataUserEmail'])) {
            $mailBox = $request['mailBox'];
            $dataEmail = $request['dataEmail'];
            $dataUserEmail = $request['dataUserEmail'];

            if(isset($dataUserEmail['data'])){
                $dataEmailDecrypted = HelperEncryptAes::decrypt($dataUserEmail['data'], false);
                if ($dataEmailDecrypted['status'] == false) {
                    return [
                        'status' => false,
                        'message' => Yii::t('app', 'errorValidacion'),
                        'errors' => ['error' => [Yii::t('app', 'AuthenticationFailed')]], 
                    ];
                }
                    
                $authUser = [
                    'username' => $dataEmailDecrypted['request']['username'],
                    'password' => $dataEmailDecrypted['request']['password'],
                ];
            } else {
                return [
                    'status' => false,
                    'message' => Yii::t('app', 'emptyJsonSend'),
                    'errors' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                ];
            }

            /** Coneccion IMAP con el el servicio de correo */
            $imapConnection = new ImapConnection();
            $imapConnection->imapPath = $mailBox;
            $imapConnection->imapLogin = $authUser['username'];
            $imapConnection->imapPassword = $authUser['password'];
            $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
            $imapConnection->attachmentsDir = Yii::getAlias('@webroot') . "/tmp_mail";
            $attachments = [];
            $mailbox = new Mailbox($imapConnection);

            $countCorreosProcesados = 0;
            foreach ( $dataEmail as $rowDataMail) {
                $countCorreosProcesados++;
                $mailObject = $mailbox->getMail($rowDataMail['id']);
                $attachments = $mailObject->getAttachments();
                $emailBody = strtolower(strip_tags($mailObject->textHtml));

                foreach ($attachments as $attachment) {
                    if(pathinfo($attachment->name, PATHINFO_EXTENSION) == 'zip' || pathinfo($attachment->name, PATHINFO_EXTENSION) == 'ZIP'){
                        if (strpos($emailBody, 'factura') != false) {
                            $feModel = new FacturacionElectronica();
                            $supplier = $feModel->extractSupplierInfo($attachment);
                        }
                    }
                }
            }
        }
        $isMailRecordExists = false;
        if (!empty($supplier)){
            $correoCliente = ''; $modelUser = '';
            $modelClientes = Clientes::find()->select(['idCliente'])->where(['numeroDocumentoCliente' => $supplier['nit']])->one();
            if (array_key_exists('email', $supplier)) {
                $correoCliente = Clientes::find()->select(['idCliente'])->where(['correoElectronicoCliente' => $supplier['email']])->one();
                $modelUser = User::find()->select(['id'])->where(['email' => $supplier['email']])->one();
            }
            if ($modelClientes != null) {
                $isMailRecordExists = true;
                $idMailRemitente = array( 'cliente' => (int) $modelClientes->idCliente );
            }elseif ($correoCliente != null){
                $isMailRecordExists = true;
                $idMailRemitente = array( 'cliente' => (int) $modelClientes->idCliente );
            }else {
                if ($modelUser != null) {
                    $isMailRecordExists = true;
                    $idMailRemitente = array( 'user' => (int) $modelUser->id );
                }
            }
            if (array_key_exists('email', $supplier)) {
                $emailR = $supplier['email'];
            } else { $emailR = $email;}
            if (array_key_exists('contacto', $supplier) && is_numeric($supplier['contacto'])) {
                $contactoR = $supplier['contacto'];
            }else { $contactoR = ''; }

            if ($isMailRecordExists == false){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data'    => [
                        'isMailRecordExists' => $isMailRecordExists,
                        'facturacion' => true,
                        'nombreCliente' => $supplier['razon_social'],
                        'numeroDocumentoCliente' => $supplier['nit'],
                        'direccionCliente' => $supplier['direccion'],
                        'correoElectronicoCliente' => $emailR,
                        'telefonoCliente' => $contactoR
                    ],
                    'status'  => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            
        } else {
            $modelClientes = Clientes::find()->select(['idCliente'])->where(['correoElectronicoCliente' => $email])->one();
            if ($modelClientes != null) {
                $isMailRecordExists = true;
                $idMailRemitente = array( 'cliente' => (int) $modelClientes->idCliente );
            } else {
                $modelUser = User::find()->select(['id'])->where(['email' => $email])->one();
                if ($modelUser != null) {
                    $isMailRecordExists = true;
                    $idMailRemitente = array( 'user' => (int) $modelUser->id );
                }
            }

        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data'    => [
                'isMailRecordExists' => $isMailRecordExists,
                'idMailRemitente' => $idMailRemitente,
                'facturacion' => false,
            ],
            'status'  => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionGetDocumentVersing(){        

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');
            
            if (!empty($jsonSend)) {
                
               //*** Inicio desencriptación POST ***//
               $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];
                } else {
                   Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                   $response = [
                       'message' => Yii::t('app', 'errorDesencriptacion'),
                       'data' => Yii::t('app', 'errorDesencriptacion'),
                       'status' => Yii::$app->params['statusErrorEncrypt'],
                   ];
                   return HelperEncryptAes::encrypt($response, true);
                }
                //*** Fin desencriptación POST ***//

                //Recibe los pararmetros desde front
                $nombreDocumento = $request['nameFile'];
                $idRadicado = $request['id'];               

                $modelDocumentos = RadiDocumentosPrincipales::find()
                ->where(['idRadiRadicado' => $idRadicado])
                // ->andWhere(['nombreRadiDocumentoPrincipal' => $nombreDocumento])
                //->andWhere(['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                // variable que se define como true para los documentos que aun no estan formados o con combinación
                $estadoDocuGenerado = false;

                $hasCorrespondence = false;

                $isRadiSigned = false; // ¿Radicado con documento Firmado?
                $isRadiSignedOrInProcess = false; // ¿Radicado con documento Firmado o en proceso?

                $dataList = [];
                foreach($modelDocumentos as $documento){

                    if ($documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['Firmado']) {
                        $isRadiSigned = true;
                        $isRadiSignedOrInProcess = true;
                    } elseif (
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['procesandoFirma'] ||
                        $documento->estadoRadiDocumentoPrincipal === Yii::$app->params['statusDocsPrincipales']['procesoFirmaFisica'] ||
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']
                    ) {
                        $isRadiSignedOrInProcess = true;
                    }

                    // valida que los documentos que aun no esten formados o con combinación
                    if(
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['Firmado'] || 
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['Combinado'] ||
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['procesandoFirma'] ||
                        $documento->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']
                    ) {
                        // El radicado ya tiene un documento con numero de radicado
                        $estadoDocuGenerado = true;
                    }

                    // valida que solo permita agregar los que son documentos con el mismo nombre 
                    if( $documento['nombreRadiDocumentoPrincipal'] == $nombreDocumento ){

                        $dataBase64Params = "";
                        $dataBase64Params = array(
                            str_replace(array('/', '+'), array('_', '-'), base64_encode($documento->idradiDocumentoPrincipal))                
                        );

                        $idUser = $documento->idUser;

                        $modelUserDetalle = UserDetalles::find()
                            ->where(['idUser' => $idUser])
                            ->one()
                        ;

                        $dataList[] = [
                            'data' => $dataBase64Params,
                            'id' => $documento->idradiDocumentoPrincipal,
                            'nombreRadiDocumentoPrincipal' => $documento->nombreRadiDocumentoPrincipal,
                            'creacionRadiDocumentoPrincipal' =>  $documento->creacionRadiDocumentoPrincipal,
                            'user' => $modelUserDetalle->nombreUserDetalles . ' ' . $modelUserDetalle->apellidoUserDetalles,
                            'extensionRadiDocumentoPrincipal' => $documento->extensionRadiDocumentoPrincipal,
                            'imagenPrincipalRadiDocumento' => $documento->imagenPrincipalRadiDocumento,
                            'statusDocsPrincipales' => $documento->estadoRadiDocumentoPrincipal,
                            'statusIdPublic' => $documento->publicoPagina,
                            'statusTextPublic' => Yii::t('app', 'valuePageNumber')[$documento->publicoPagina],
                            'statusText' => Yii::$app->params['statusDocsPrincipalesNumber'][$documento->estadoRadiDocumentoPrincipal],
                        ];

                        /** Evaluar si existe un documento de la version consultada, que ya posea combinacion de correspondencia */
                        if ($documento->extensionRadiDocumentoPrincipal == 'pdf') {
                            $hasCorrespondence = true;
                        }

                    }

                }

                foreach ($dataList as &$value) {
                    $value['hasCorrespondence'] = $hasCorrespondence;
                }

                $radicadoModel = RadiRadicados::findOne($idRadicado);
                
                if(!empty($radicadoModel)){                

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['viewVersing'].'de documento principal del Radicado: '.$radicadoModel->numeroRadiRadicado, //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                }

                /**** Validar si el usuario logueado es tramitador o informado ****/
                $isUserTramitador = ($radicadoModel['user_idTramitador'] == Yii::$app->user->identity->id);
                $isUserInformado = false;

                if ($isUserTramitador == false) {
                    $radiInformados = RadiInformados::find()
                        ->select(['idRadiInformado']) // Para que la consulta pese menos
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->andWhere(['idUser' => Yii::$app->user->identity->id])
                        ->limit(1) // Para que la consulta pese menos
                    ->one();
                    if ($radiInformados != null) {
                        $isUserInformado = true;
                    }
                }

                /** Validar si el radicado ya posee una plantilla con combinación de correspondencia, o imagen principal */
                $radiHasImgPrincipal = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
                    ->where(['idRadiRadicado' => $idRadicado, 'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
                    ->limit(1)
                ->one();

                $radiHasImgPrincipal = ($radiHasImgPrincipal == null) ? false : true; // Indíca si el radicado tiene imagen principal

                /** Validar si el radicado está finalizado */
                $isRadiFinalizado = ($radicadoModel['estadoRadiRadicado'] == Yii::$app->params['statusTodoText']['Finalizado']);

                // obtener permisos 
                $permissionSigned = (array) Yii::$app->params['permissionSigned'];
                $buttonSigned = [];

                foreach($permissionSigned as $tipo => $operacion){

                    $userIdentity = Yii::$app->user->identity->rol->rolesTiposOperaciones;
                    
                    $validacion = false;
                    foreach($userIdentity as $key => $rolOperacionVal)
                    {

                        if($rolOperacionVal->rolOperacion->nombreRolOperacion == $operacion)
                        {
                            $validacion = true;
                            break;
                        }
                    }

                    if($validacion){
                        $buttonSigned[] = $tipo;
                    }
                    
                }

                $onlyDownloadStatus = ($radicadoModel->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Inactivo']) ? true : false;

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $dataList,
                    'isUserTramitador' => $isUserTramitador,
                    'isUserInformado' => $isUserInformado,
                    'isRadiFinalizado' => $isRadiFinalizado,
                    'isRadiSigned' => $isRadiSigned,
                    'isRadiSignedOrInProcess' => $isRadiSignedOrInProcess,
                    'onlyDownloadStatus' => $onlyDownloadStatus,
                    'radiHasImgPrincipal' => $radiHasImgPrincipal,
                    'estadoDocuGenerado' => $estadoDocuGenerado,
                    'tipoRadicado' => $radicadoModel->idCgTipoRadicado,
                    'buttonSigned' => $buttonSigned,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }

    public function actionDownloadDocumentPackage(){

        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');
            
            if (!empty($jsonSend)) {
                
               //*** Inicio desencriptación POST ***//
               $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
               if ($decrypted['status'] == true) {
                   $request = $decrypted['request'];
               } else {
                   Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                   $response = [
                       'message' => Yii::t('app', 'errorDesencriptacion'),
                       'data' => Yii::t('app', 'errorDesencriptacion'),
                       'status' => Yii::$app->params['statusErrorEncrypt'],
                   ];
                   return HelperEncryptAes::encrypt($response, true);
                }
                //*** Fin desencriptación POST ***// 

                $idRadicado = $request['id'];

                $radiRadicados = RadiRadicados::find()
                ->select(['numeroRadiRadicado', 'user_idTramitador','isRadicado'])
                    ->where(['idRadiRadicado' => $idRadicado])
                ->one();

                if ($radiRadicados == null) {
                    throw new NotFoundHttpException('The requested page does not exist.');
                }

                /**** Validar si el usuario logueado es tramitador o informado ****/
                $isUserTramitador = ($radiRadicados['user_idTramitador'] == Yii::$app->user->identity->id);
                $isUserInformado = false;

                if ($isUserTramitador == false) {
                    $radiInformados = RadiInformados::find()
                        ->select(['idRadiInformado']) // Para que la consulta pese menos
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->andWhere(['idUser' => Yii::$app->user->identity->id])
                        ->limit(1) // Para que la consulta pese menos
                    ->one();
                    if ($radiInformados != null) {
                        $isUserInformado = true;
                    }
                }

                if ($isUserTramitador == false && $isUserInformado == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'accessDenied')[1],
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorAccessDenied'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /** Validar si el usuario logueado es tramitador o informado */

                /*** Validar creación de la carpeta ***/
                $rutaOk = true;
                $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDownloads'] . '/';
                $filePath = $pathUploadFile . $radiRadicados['numeroRadiRadicado'] . '.zip';

                /** Eliminar archivo anterior si existe para que no den conflictos los archivos que se agregan al Zip */
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                // Verificar ruta de la carpeta y crearla en caso de que no exista
                if (!file_exists($pathUploadFile)) {
                    if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    }
                }

                $modelDocumento = RadiDocumentos::find()->select(['rutaRadiDocumento', 'nombreRadiDocumento'])
                    ->where(['idRadiRadicado' => $idRadicado, 'estadoRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']]);
                // Regla nueva: puedo descargar los dogumentos confidenciales si soy usuario informado
                // if ($isUserTramitador == false) {
                //     $modelDocumento->andWhere(['isPublicoRadiDocumento' => Yii::$app->params['SiNoText']['Si']]);
                // }
                $modelDocumento = $modelDocumento->all();

                $modelDocumentosPrincipales = RadiDocumentosPrincipales::find()->select(['rutaRadiDocumentoPrincipal', 'nombreRadiDocumentoPrincipal', 'extensionRadiDocumentoPrincipal'])
                    ->where(['idRadiRadicado' => $idRadicado, 'estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']])
                ->all();

                $arrayDocumentos = [];
                foreach ($modelDocumento as  $documento) {
                    $arrayDocumentos[] = [
                        'ruta'   => $documento['rutaRadiDocumento'] . $documento['nombreRadiDocumento'],
                        'nombre' => $documento['nombreRadiDocumento'],
                    ];
                }

                foreach ($modelDocumentosPrincipales as  $documento) {

                    /** Validar nombre para que se envien todas las versiones del documento (documentos con el mismo nombre) */
                    $nombreDocu = explode('/', $documento['rutaRadiDocumentoPrincipal']);
                    $nombreDocu = end($nombreDocu);
                    $nombreDocu = $nombreDocu ?? $documento['nombreRadiDocumentoPrincipal'] . '.' . $documento['extensionRadiDocumentoPrincipal'];

                    $arrayDocumentos[] = [
                        'ruta'   => $documento['rutaRadiDocumentoPrincipal'],
                        'nombre' => $nombreDocu,
                    ];
                }

                /** Validar El radicado no tiene documentos a procesar */
                if (count($arrayDocumentos) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'fileWithoutDocuments')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                /** Copiar archivo ZIP base en la ruta establecida para el nuevo archivo */
                if (!copy(Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeFileZipBase'], $filePath)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                        'info' => '',
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }            
                /** Fin Copiar archivo ZIP base en la ruta establecida para el nuevo archivo */

                // Añadir documentos al zip                
                $zip = new \ZipArchive();  

                if (!$zip->open($filePath, \ZipArchive::CREATE)) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }

                foreach($arrayDocumentos as $documento){
                    $zip->addFile($documento['ruta'], $documento['nombre']);
                }
                
                $zip->close();

                $dataFile = base64_encode(file_get_contents($filePath));

                /** Eliminando archivo temporal generado */
                unlink($filePath);

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => [],
                    'fileName' => $radiRadicados['numeroRadiRadicado'] . '.zip',
                    'status' => 200,
                ];

                /***    Log de Auditoria  ***/

                if ((boolean) $radiRadicados['isRadicado'] == true) {
                    $msgRadiOrTmp = ' del radicado: ';
                } else {
                    $msgRadiOrTmp = ' del consecutivo temporal: ';
                }

                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['DownloadFile'].' zip de los documentos' . $msgRadiOrTmp . $radiRadicados['numeroRadiRadicado'], //texto para almacenar en el evento
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $return = HelperEncryptAes::encrypt($response, true);
                // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                $return['datafile'] = $dataFile;

                return $return;
            
            }else{

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);

            }

        }else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /** 
     * Función que retorna la cantidad de días "hábiles" restantes de un radicado desde la fecha actual hasta la fecha de vencimiento
     * Si la fecha actual es mayor a la fecha de vencimiento, esta retorna un número negativo
     */
    public static function calcularDiasVencimiento($idRadicado)
    {
        $fechaActual = date("Y-m-d");

        $infoRadicado = new RadiRadicados();
        $modelRadicado = $infoRadicado->findOne(['idRadiRadicado' => $idRadicado]);
        $fechaDesde = date("Y-m-d H:i:s");
        $fechaHasta = date("Y-m-d",strtotime($modelRadicado->fechaVencimientoRadiRadicados));

        if ($modelRadicado->fechaVencimientoRadiRadicados > $fechaActual) {
            $fechaHasta = $fechaHasta . ' 23:59:59';
        } else {
            $fechaHasta = $fechaHasta . ' 00:00:00';
        }

        return HelperRadicacion::calcularDiasEntreFechas($fechaDesde, $fechaHasta);
    }

    public static function calcularfechaVencimiento($idGdTrdTipoDocumental,$diasRestantes, $fechaCreacionRadi=null)
    {

        
        $modelDocumentales = GdTrdTiposDocumentales::find()->where(['idGdTrdTipoDocumental' => $idGdTrdTipoDocumental])->one();

        if (isset($modelDocumentales)) {

            /* 
             * Se valida si desde el formulario llega los días de termino en blanco, si es 
             * asi se debe tomar los dias de termino definidos por el tipo documental 
             * en caso tal que llegen díasd de termino diferentes a los configurados en el 
             * tipo documental, se tiene en cuenta lo que llega por el formulario.
             */
            if($diasRestantes != ''){
                $fechaCreacionRadi = null;
                if($diasRestantes != $modelDocumentales['diasTramiteTipoDocumental']){
                    $modelDocumentales['diasTramiteTipoDocumental'] = $diasRestantes;
                }
            }

            if($fechaCreacionRadi==null){
                $fechaCreacion = date("Y-m-d");
            }else {                
                $fechaCreacion = $fechaCreacionRadi;
            }

            $fecha = self::formatoFechaVencimiento($fechaCreacion, $modelDocumentales['diasTramiteTipoDocumental']);

            if($fecha['restantes'] != 0){
                $modelDocumentales['diasTramiteTipoDocumental'] = $fecha['restantes'];
            }

            return [
                'fechaVencimientoRadiRadicados' => $fecha['formatoFrontend'] ?? Yii::t('app', 'specifyingDate'),
                'fechaFormatoDB' => $fecha['formatoBaseDatos'],
                'diasRestantes' => $modelDocumentales['diasTramiteTipoDocumental'],
                'status' => 200,
            ];

        }
    }
    
    public static function formatoFechaVencimiento($fecha,$add = 0)
    {
        //Variable para almacenar los dias No validos o NO laborados segun horario laboral activo en BD
        $arrayDiasNoValidos = [];
        
        //Consulta de los días no laborables
        $modelDiasNoLaborados = CgDiasNoLaborados::find()
            ->where(['estadoCgDiaNoLaborado' => Yii::$app->params['statusTodoText']['Activo']])
            ->all()
        ;
        
        //Arreglo de los días no laborables
        $noLaboradosArray = [];
        foreach($modelDiasNoLaborados as $diaNoLaboral){
            $noLaboradosArray[] = date("d-m-Y", strtotime($diaNoLaboral->fechaCgDiaNoLaborado));
        }

        $fechaPros =  date("d-m-Y", strtotime($fecha));
        $fechaFormatoDB =  $fechaPros;
        $contDias = 1;

        $numDia = date('w', strtotime($fechaFormatoDB));

        $HorarioValido = CgHorarioLaboral::find()->where(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']])->one();
        if(isset($HorarioValido)){
            $diaInicio = $HorarioValido['diaInicioCgHorarioLaboral'];
            $diaFin = $HorarioValido['diaFinCgHorarioLaboral'];

            $arrayDiasNoValidos = HelperRadicacion::getArrayDiasNoValidos($diaInicio, $diaFin);

            /** Validar dia inicío habil en base a la (hora actual) y la hora configurada en en horario laboral */
            $horaActual = date('H:i:s');
            $horaFin = $HorarioValido['horaFinCgHorarioLaboral'];
            if($horaActual > $horaFin || in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
                // $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . ""));
                $numDia = date('w', strtotime($fechaFormatoDB));
            }
            /** Fin Validar día inicio habil en base a la (hora actual) y la hora configurada en en horario laboral */
        }

        /**Contador de días restantes con respecto a la fecha actual */
        $diasRestantesFechaActual = 0;
        $fechaActual = date("d-m-Y");  

        /** Validar dia inicio habil antes de entrar al bucle que calcula el dia final */
        $variable = 0;
        if(in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
            while ($variable <= 500){
                
                if(in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
                    $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                    $numDia = date('w', strtotime($fechaFormatoDB));
                }else{
                    break;
                }
                $variable++;
            }
        }
        /** Fin Validar dia inicio habil antes de entrar al bucle que calcula el dia final */    

        /** Calcular dia final */       
        if($add != 0){

            $variable=0;
            while ($variable <= 500){

                $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                $numDia = date('w', strtotime($fechaFormatoDB));

                if(strtotime($fechaFormatoDB) > strtotime($fechaActual)){
                    $diasRestantesFechaActual++;                                
                }

                //si el dia iterado es un dia no laboral
                if( in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray) ){
                    $contDias = $contDias - 1;
                    
                    if(strtotime($fechaFormatoDB) > strtotime($fechaActual)){
                        $diasRestantesFechaActual--;                    
                    }
                }                

                if($add == $contDias){
                    $variable == 500;
                    break;
                }
                  
                $contDias = $contDias + 1;
                $variable++;          
            }
 
        }
        /** Fin Calcular dia final */

        /* Formato Fecha Frontend */
        $arrayDias = Yii::t('app', 'days');
        $dia = $arrayDias[date('w', strtotime($fechaFormatoDB))];
        $arrayMeses = Yii::t('app', 'months');
        $mes = $arrayMeses[date('n', strtotime($fechaFormatoDB))];
        $explode = explode("-", $fechaFormatoDB);

        return [
            'formatoFrontend' => $dia . ' ' . $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2],
            'formatoBaseDatos' => $fechaFormatoDB,
            'restantes' => $diasRestantesFechaActual
        ];

    }

    public static function formatoFechaVencimientoAgendas($fecha)
    {
        //Variable para almacenar los dias No validos o NO laborados segun horario laboral activo en BD
        $arrayDiasNoValidos = [];
        
        //Consulta de los días no laborables
        $modelDiasNoLaborados = CgDiasNoLaborados::find()
            ->where(['estadoCgDiaNoLaborado' => Yii::$app->params['statusTodoText']['Activo']])
            ->all()
        ;
        
        //Arreglo de los días no laborables
        $noLaboradosArray = [];
        foreach($modelDiasNoLaborados as $diaNoLaboral){
            $noLaboradosArray[] = date("d-m-Y", strtotime($diaNoLaboral->fechaCgDiaNoLaborado));
        }

        $fechaPros =  date("d-m-Y", strtotime($fecha));
        $fechaFormatoDB =  $fechaPros;
        $contDias = 1;

        $numDia = date('w', strtotime($fechaFormatoDB));

        $HorarioValido = CgHorarioLaboral::find()->where(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']])->one();
        if(isset($HorarioValido)){
            $diaInicio = $HorarioValido['diaInicioCgHorarioLaboral'];
            $diaFin = $HorarioValido['diaFinCgHorarioLaboral'];

            $arrayDiasNoValidos = HelperRadicacion::getArrayDiasNoValidos($diaInicio, $diaFin);

            /** Validar dia inicío habil en base a la (hora actual) y la hora configurada en en horario laboral */
            $horaActual = date('H:i:s');
            $horaFin = $HorarioValido['horaFinCgHorarioLaboral'];

            if($fecha == date("Y-m-d")) {

                if($horaActual > $horaFin || in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
                    $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                    $numDia = date('w', strtotime($fechaFormatoDB));
                }

            } else {

                if(in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
                    $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                    $numDia = date('w', strtotime($fechaFormatoDB));
                }

            }
            /** Fin Validar día inicio habil en base a la (hora actual) y la hora configurada en en horario laboral */
        }

        /** Validar dia inicio habil antes de entrar al bucle que calcula el dia final */
        $variable = 0;
        if(in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
            while ($variable <= 500){
                
                if(in_array($numDia, $arrayDiasNoValidos) || in_array($fechaFormatoDB, $noLaboradosArray)) {
                    $fechaFormatoDB = date("d-m-Y", strtotime($fechaFormatoDB . "+1 days"));
                    $numDia = date('w', strtotime($fechaFormatoDB));
                }else{
                    break;
                }
                $variable++;
            }
        }
        /** Fin Validar dia inicio habil antes de entrar al bucle que calcula el dia final */


        /* Formato Fecha Frontend */
        $arrayDias = Yii::t('app', 'days');
        $dia = $arrayDias[date('w', strtotime($fechaFormatoDB))];
        $arrayMeses = Yii::t('app', 'months');
        $mes = $arrayMeses[date('n', strtotime($fechaFormatoDB))];
        $explode = explode("-", $fechaFormatoDB);

        return [
            'formatoFrontend' => $dia . ' ' . $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2],
            'formatoBaseDatos' => $fechaFormatoDB,
            'formatoBaseDatosFin' => date("Y-m-d", strtotime($fechaFormatoDB))
        ];

    }

    /**
     * Función que genera un radicado de tipo salida para la combinacion de correspondencia 
     * si el radicado es de tipo PQRS o ENTRADA  
     * de lo contrario segun el RadiRadicado['idCgTipoRadicado'] que llegue a la funcion
     * 
     * @param $modelRadicado [array] [modelo del radicado]
     */

    public static function generarRadicado($RadiRadicado, $infoUserLogued, $idCgTipoRadicado = false)
    {
        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);

        $model = new RadiRadicados;

        # Se consulta la información de la configuración realizada para el cliente al que se le esta implementando ORFEO NG
        $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
        $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $infoUserLogued['idGdTrdDependencia']]);

        # Se agrega el tipo de radicado que trae el modelo de Radicados del padre.
        if($idCgTipoRadicado == false){
            $idCgTipoRadicado = $RadiRadicado['idCgTipoRadicado'];
        }

        // Se envia a la función de generar número de radicado la información de los modelos que se consultan
        $estructura = self::generateNumberFiling($idCgTipoRadicado, $modelCgNumeroRadicado, $modelDependencia);
        
        # valores al modelo
        $model->attributes = $RadiRadicado->attributes;
        $model->numeroRadiRadicado = $estructura['numeroRadiRadicado'];
        $model->creacionRadiRadicado = date("Y-m-d H:i:s");
        $model->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Activo'];

        # asignar el valor de idCgTipoRadicado si es pqrs-entrada o lo que viene en el modelo
        $model->idCgTipoRadicado = $idCgTipoRadicado == false ? $RadiRadicado['idCgTipoRadicado'] :  $idCgTipoRadicado;

        # Definir variable en 0: false por defecto, ya que en el módelo de radicado se modifica esta variable con la función beforeSave()
        $model->isRadicado = 0;

        $model->user_idCreador = $infoUserLogued['id']; //Id usuario logueado
        $model->idTrdDepeUserCreador = $infoUserLogued['idGdTrdDependencia']; // Id dependencia de usuario logueado
        $model->user_idTramitadorOld = null;
        $model->user_idTramitador = $infoUserLogued['id']; //Id usuario logueado
        $model->idTrdDepeUserTramitador = $infoUserLogued['idGdTrdDependencia']; // Id dependencia de usuario logueado

        //$model->descripcionAnexoRadiRadicado = null;
        $model->foliosRadiRadicado = '0';
        $model->cantidadCorreosRadicado = 0;

        $model->idRadiRadicadoPadre = $RadiRadicado['idRadiRadicado'];

        /** Guardar radicado */
        if (!$model->save()) {
            return [
                'status' => false,
                'message' => Yii::t('app','errorValidacion'),
                'errors' => $model->getErrors(),
                'data' => null,
            ];
        }
        /** Fin Guardar radicado */

        # Guarda la actualización del consecutivo del numero de radicado
        $estructura['modelCgTiposRadicados']->save();

        /** Asignar el remitente del radicado seleccionado al radicado que se está creando */
        $radiRemitentes = RadiRemitentes::findAll(['idRadiRadicado' => $RadiRadicado['idRadiRadicado']]);

        if(count($radiRemitentes) == 0) {
            return [
                'status' => false,
                'message' => Yii::t('app','errorValidacion'),
                'errors' => ['error' => [Yii::t('app', 'radiWithoutRemitente',['numRadi' => $RadiRadicado['numeroRadiRadicado']])]],
                'data' => null,
            ];
        } else {

            # Se procede a guardar en la tabla de remitentes la relación del remitente con el radicado NUEVO
            $nombreRemitente = [];
            foreach($radiRemitentes as $dataRemitente){

                $modelRadiRemitentes = new RadiRemitentes();
                $modelRadiRemitentes->idRadiRadicado = $model->idRadiRadicado;
                $modelRadiRemitentes->idRadiPersona = $dataRemitente->idRadiPersona;
                $modelRadiRemitentes->idTipoPersona = $dataRemitente->idTipoPersona;

                if (!$modelRadiRemitentes->save()) {
                    return [
                        'status' => false,
                        'message' => Yii::t('app','errorValidacion'),
                        'errors' => $modelRadiRemitentes->getErrors(),
                        'data' => null,
                    ];
                }

                /*** Logs de radicado y auditoría ***/
                if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                    # Se obtiene la información del cliente
                    $modelRemitente = Clientes::find()->select(['nombreCliente'])->where(['idCliente' => $dataRemitente->idRadiPersona])->one();
                    $nombreRemitente[] = $modelRemitente['nombreCliente'] .' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';

                } else {
                    # Se obtiene la información del usuario o funcionario
                    $modelRemitente = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => $dataRemitente->idRadiPersona])->one();
                    $nombreRemitente[] = $modelRemitente['nombreUserDetalles'].' '.$modelRemitente['apellidoUserDetalles'].' ('.Yii::$app->params['tipoPersonaNumber'][$dataRemitente->idTipoPersona].')';
                }
            } 
            
            $nombresRemitentes = implode(", ", $nombreRemitente);
        }       
        /** Fin Asignar el remitente del radicado seleccionado al radicado que se está creando*/

        # Se valida si el(los) id(s) del radicado son correctos, para agregarlo a la relacion con el id radicado creado.
        $idRadiAsociado = self::verificarRadicado($model->idRadiRadicado);

        if (isset($idRadiAsociado)) {

            #Asociar radicado padre a radicado hijo(nuevo)
            $modelRadiAsociado = new RadiRadicadosAsociados();
            $modelRadiAsociado->idRadiAsociado = $RadiRadicado['idRadiRadicado'];       // Radicado seleccionado para la combinacion
            $modelRadiAsociado->idRadiCreado = $model->idRadiRadicado;                  // Radicado que se genero
        
            if (!$modelRadiAsociado->save()) {
                return [
                    'status' => false,
                    'message' => Yii::t('app','errorValidacion'),
                    'errors' => $modelRadiAsociado->getErrors(),
                    'data' => null,
                ];
            }

            #Asociar radicado hijo(nuevo) a radicado padre
            $modelRadiAsociado = new RadiRadicadosAsociados();
            $modelRadiAsociado->idRadiAsociado = $model->idRadiRadicado;        // Radicado que se genero
            $modelRadiAsociado->idRadiCreado = $RadiRadicado['idRadiRadicado']; // Radicado seleccionado para la combinacion
        
            if (!$modelRadiAsociado->save()) {
                return [
                    'status' => false,
                    'message' => Yii::t('app','errorValidacion'),
                    'errors' => $modelRadiAsociado->getErrors(),
                    'data' => null,
                ];
            }
        }


        # Prioridad del radicado
        if($model->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

        $dataRadicados = 'Id Radicado: '.$model->idRadiRadicado;
        $dataRadicados .= ', Número Radicado: '.$model->numeroRadiRadicado;
        $dataRadicados .= ', Asunto Radicado: '.$model->asuntoRadiRadicado;
        $dataRadicados .= ', Tipo Radicación: '.$model->cgTipoRadicado->nombreCgTipoRadicado;
        $dataRadicados .= ', Tipo Documental: '.$model->trdTipoDocumental->nombreTipoDocumental;
        $dataRadicados .= ', Prioridad: '.$prioridad;
        $dataRadicados .= ', Remitente/Destinatario: '.$nombresRemitentes;
        $dataRadicados .= ', Usuario Tramitador: '.$infoUserLogued['nombreUserDetalles'].' '.$infoUserLogued['apellidoUserDetalles'];
        $dataRadicados .= ', Usuario Creador: '.$infoUserLogued['nombreUserDetalles'].' '.$infoUserLogued['apellidoUserDetalles'];
        $dataRadicados .= ', Cantidad envíos correos electrónicos del radicado: '.$model->cantidadCorreosRadicado;
        $dataRadicados .= ', Fecha creación: '.$model->creacionRadiRadicado;
        $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$model->estadoRadiRadicado];

        #Radicado asociado
        $dataRadicados .= ', Id Radicado Asociado: ' . $RadiRadicado['idRadiRadicado'];
        $dataRadicados .= ', Radicado Asociado: ' . $RadiRadicado['numeroRadiRadicado'];

        $observation = 'Se creó un nuevo radicado: ' . $model->numeroRadiRadicado . ' asociado al radicado creador ' . $RadiRadicado['numeroRadiRadicado'];

        /***    Log de Auditoria  ***/
        HelperLog::logAdd(
            true,
            $infoUserLogued['id'], //Id user
            $infoUserLogued['username'], //username
            Yii::$app->controller->route, //Modulo
            $observation, //texto para almacenar en el evento
            '',
            $dataRadicados, //Data
            array() //No validar estos campos
        );

        $observationFiling = Yii::$app->params['eventosLogTextRadicado']['New'] . ' asociado al radicado padre '. $RadiRadicado['numeroRadiRadicado'];

        /***    Log de Radicados  ***/
        HelperLog::logAddFiling(
            $infoUserLogued['id'], //Id user
            $infoUserLogued['idGdTrdDependencia'], // Id dependencia
            $model->idRadiRadicado, //Id radicado
            $idTransacion->idCgTransaccionRadicado,
            $observationFiling, //observación
            $model,
            array() //No validar estos campos
        );
        /*** Logs de radicado y auditoría ***/

        return [
            'status' => true,
            'message' => 'Ok',
            'errors' => [],
            'data' => $model,
        ];
    }

    /**
     * Función que valida si existe los ids de los radicados hijos en la D.B
     * @param $idRadicado [array] [Ids de los radicados hijos]
    */
    public static function verificarRadicado($idRadicado)
    {
        $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $idRadicado]);
        
        if(!is_null($modelRadicado)){
            return $modelRadicado->idRadiRadicado;

        } /* else{
            return 'false';
        }  */      
    }

    public function actionSignatureQr(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');
            
            if (!empty($jsonSend)) {
                
               //*** Inicio desencriptación POST ***//
               $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
               if ($decrypted['status'] == true) {
                   $request = $decrypted['request'];
               } else {
                   Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                   $response = [
                       'message' => Yii::t('app', 'errorDesencriptacion'),
                       'data' => Yii::t('app', 'errorDesencriptacion'),
                       'status' => Yii::$app->params['statusErrorEncrypt'],
                   ];
                   return HelperEncryptAes::encrypt($response, true);
                }
                //*** Fin desencriptación POST ***// 

                $dataList = [];
                $model = GdFirmasQr::find()->where(['idGdFirmasQr' => $request['idGdFirmasQr']])->one();
                
                    $fechaRadicacion = HelperRadicacion::getFormatosFecha(date("d-m-Y",strtotime($model->radiRadicado->creacionRadiRadicado)))['formatoFrontend'];
                    $fechaCreacion   = HelperRadicacion::getFormatosFecha(date("d-m-Y",strtotime($model['creacionGdFirmasQr'])))['formatoFrontend'];
                    $userDetalles = $model->user->userDetalles;

                $dataList = [
                    
                    'fechaFirma' => $fechaCreacion.' '.date("H:i a",strtotime($model['creacionGdFirmasQr'])),
                    'nombreUsuario' => $userDetalles['nombreUserDetalles'].' '. $userDetalles['apellidoUserDetalles'],
                    'dependencia' =>  $model->user->gdTrdDependencia['codigoGdTrdDependencia'].'-'.$model->user->gdTrdDependencia['nombreGdTrdDependencia'],
                    'permisoActual' => Yii::$app->params['statusTodoNumber'][$model->estadoGdFirmasQr],

                    'numeroRadicado' => $model->radiRadicado['numeroRadiRadicado'],
                    'fechaRadicado' => $fechaCreacion.' '.date("H:i a",strtotime($model->radiRadicado['creacionRadiRadicado'])),
                    'asuntoRadicado' => $model->radiRadicado['asuntoRadiRadicado'],
                ];

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $dataList,                         
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }


    /**
     * Función que actualiza los remitentes en crear radicado
     * @param $request que llega desde frontend
    */
    public static function updateRemitentes($request){

        if (is_array($request['remitentes']) && $request['remitentes'] != '') {

            $dataSave = []; //Array de id del remitente y tipo persona
            
            foreach ($request['remitentes'] as $dataSender) {

                // Si el tipo de persona es diferente a funcionario se realiza el proceso de creación del cliente
                // siempre y cuando este no exista.
                if($dataSender['idTipoPersona'] != Yii::$app->params['tipoPersonaText']['funcionario']){

                    // Se valida el correo y el documento que llegan por el formulario de radicación y si no existen se procede
                    // a validar si llega el campo id, para que asi se pueda buscar el cliente y poder actualizarlo, solo aplica
                    // para los clientes.
                    $verificarCliente = ClientesController::verificarCliente($dataSender['numeroDocumentoCliente'], $dataSender['correoElectronicoCliente']);

                    if($verificarCliente['status'] == 'false'){

                        if($verificarCliente['idCliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;

                            $modelcliente->attributes = $dataSender;

                        } else {
                            // Al modelo del cliente se le asigna el valor de lo que llega por POST
                            $modelcliente = new Clientes;
                            $modelcliente->attributes = $dataSender;
                        }
            
                        /** 
                         * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                         * no permitir guardar dicha información y debera arrojar error.
                        */
                        $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                        if($modelClienteDetalle){

                            if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                 $dataSender['nombreCliente'] != $modelClienteNombre or
                                 $dataSender['direccionCliente'] != $modelClienteDireccion
                                )
                            ){
                                
                                $validUpdate = false;  //Se guardo correctamente
                                return [
                                    'validUpdate' => $validUpdate,
                                    'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                ];
                            }
                        }

                        if($modelcliente->save()){
                            $dataSave[] = [
                                'idRemitente' => $modelcliente->idCliente,
                                'idTipoPersona' => $dataSender['idTipoPersona']
                            ];   

                        } else {
                            // $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelcliente->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                                'adasdasd' => '1asd'
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
    
                    } else {

                        if($dataSender['idCliente']['cliente'] != ''){
                            $modelcliente = Clientes::findOne(['idCliente' => $dataSender['idCliente']['cliente']]);
                            $modelClienteNombre = $modelcliente->nombreCliente;
                            $modelClienteEmail = $modelcliente->correoElectronicoCliente;
                            $modelClienteDireccion = $modelcliente->direccionCliente;
                            
                            $modelcliente->attributes = $dataSender;

                            /** 
                             * Si el cliente que llega hace referenca al anonimo y si los campos llegan diferentes a los existente
                             * no permitir guardar dicha información y debera arrojar error.
                            */
                            $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $modelcliente->idCliente]);
                            if($modelClienteDetalle){

                                if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username && 
                                    ($dataSender['correoElectronicoCliente'] != $modelClienteEmail or 
                                    $dataSender['nombreCliente'] != $modelClienteNombre or
                                    $dataSender['direccionCliente'] != $modelClienteDireccion
                                    )
                                ){
                                    $validUpdate = false;  //Se guardo correctamente
                                    return [
                                        'validUpdate' => $validUpdate,
                                        'model' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]]
                                    ];
                                }
                            }

                            if(!$modelcliente->save()) {

                                // $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelcliente->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                    'adasdasd' => '1asd'
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        
                       
                        $dataSave[] = [
                            'idRemitente' => $verificarCliente['idCliente'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];                     
                    }            

                } else {
                    if(isset($dataSender['idCliente']['user'])){
                        $dataSave[] = [
                            'idRemitente' => $dataSender['idCliente']['user'],
                            'idTipoPersona' => $dataSender['idTipoPersona']
                        ];   
                    }       
                }
            }
        }

    }

    public static function saveAnexUpload($modelRadicado, $fileUpload)
    {
        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'attachment']);
        $userLogin = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();

        $anio = date('Y');
        $idRadicado = $modelRadicado->idRadiRadicado;

        $modelDependencia = GdTrdDependencias::findOne($modelRadicado->idTrdDepeUserCreador);

        $codigoDependencia = $modelDependencia->codigoGdTrdDependencia;
        $numeroRadicado = $modelRadicado->numeroRadiRadicado;  
        //$idRadicado = $modelRadicado->idRadiRadicado;

        # Se va a asignar el tipo documental del radicado, ya no es del formulario.
        $tipoDocu = $modelRadicado->idTrdTipoDocumental;
       
        $pathUploadFile = Yii::getAlias('@webroot')                            
            . "/" .  Yii::$app->params['bodegaRadicados']
            . "/" . $anio                            
            . "/" . $codigoDependencia
            . "/"
        ;

        $modelDocumento = RadiDocumentos::find()
            ->where(['idRadiRadicado' => $idRadicado ])
            ->orderBy(['numeroRadiDocumento' => SORT_DESC])
            ->one();
       
        $numeroDocumento = 1;
        if(!empty($modelDocumento)){
            $numeroDocumento = (int) $modelDocumento->numeroRadiDocumento + 1;
        }

        $descripcion = 'Anexo cargado al radicar';

        $modelDocumento = new RadiDocumentos();
        $modelDocumento->nombreRadiDocumento = $fileUpload->name;
        $modelDocumento->rutaRadiDocumento = $pathUploadFile;
        $modelDocumento->extencionRadiDocumento =  $fileUpload->extension;
        $modelDocumento->idRadiRadicado = $idRadicado;
        $modelDocumento->idGdTrdTipoDocumental = $tipoDocu;
        $modelDocumento->descripcionRadiDocumento = $descripcion;
        $modelDocumento->idUser = Yii::$app->user->identity->id;
        $modelDocumento->numeroRadiDocumento = $numeroDocumento;
        
        $modelDocumento->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
        $modelDocumento->creacionRadiDocumento = date('Y-m-d H:i:s');

        $modelDocumento->isPublicoRadiDocumento = 0;

        $tamano = $fileUpload->size / 1000;
        $modelDocumento->tamanoRadiDocumento = '' . $tamano . ' KB';

        if(!$modelDocumento->save()){
            return [
                'status' => false,
                'data' => [],
                'errors' => $modelDocumento->getErrors(),
            ];
        }

        //Se actualiza el nombre de documento ya que se necesita el id que genera a insertar la tabal
        $nomArchivo = "$numeroRadicado-" . $numeroDocumento . ".$fileUpload->extension";
        
        $modelDocumento->nombreRadiDocumento = $nomArchivo;

        if(!$modelDocumento->save()) {
            return [
                'status' => false,
                'data' => [],
                'errors' => $modelDocumento->getErrors(),
            ];
        }

        #Si el radicado esta incluido en un expediente, se agrega el documento recién subido al índice

        /***    Log de Radicados  ***/
        HelperLog::logAddFiling(
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
            $modelDocumento->idRadiRadicado, //Id radicado
            $idTransacion->idCgTransaccionRadicado,
            Yii::$app->params['eventosLogTextRadicado']['FileUpload'] . $modelDocumento->nombreRadiDocumento . ', con el nombre de: ' .$fileUpload->name. ', y su descripción: '. $descripcion,
            $modelDocumento,
            array() //No validar estos campos
        );
        /***  Fin  Log de Radicados  ***/

        $rutaOk = true;

         // Verificar que la carpeta exista y crearla en caso de que no exista
        if (!file_exists($pathUploadFile)) {
    
            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                $rutaOk = false;
            }
        }
        
        /*** Validar creación de la carpeta***/
        if ($rutaOk == false) {
            return [
                'status' => false,
                'data' => [],
                'errors' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
            ];
        }
        /*** Fin Validar creación de la carpeta***/

        $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo, false);
        
        if ($uploadExecute) {

            ///////////////////////// EXTRAER TEXTO  ////////////////////////
            $helperExtraerTexto = new HelperExtraerTexto($pathUploadFile . $nomArchivo);

            $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                $modelDocumento->idRadiDocumento,    
                Yii::$app->params['tablaOcrDatos']['radiDocumentos']  
            );

            if($helperOcrDatos['status'] != true){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $helperOcrDatos['message'],
                    'data'    => $helperOcrDatos['data'],
                    'status'  => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            ///////////////////////// EXTRAER TEXTO  ////////////////////////

            $gdTrdTiposDocumentales = GdTrdTiposDocumentales::find()->select(['nombreTipoDocumental'])->where(['idGdTrdTipoDocumental' => $modelDocumento->idGdTrdTipoDocumental])->one();
            
            $RadiDocumentos = new RadiDocumentos();
            $attributeLabels = $RadiDocumentos->attributeLabels();
            
            $dataOld = '';
            $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDocumento->idRadiDocumento
                . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDocumento->nombreRadiDocumento
                . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDocumento->descripcionRadiDocumento
                . ', Tipo Documental: ' . $gdTrdTiposDocumentales['nombreTipoDocumental']
            ;

            if ($modelRadicado->isRadicado == true || $modelRadicado->isRadicado == 1) {
                $typeRadiOrConsecutivo = ', al radicado: ';
                $arrayTypeRadiOrConsecutive[] = 'radicado';
            } else {
                $typeRadiOrConsecutivo = ', al consecutivo: ';
                $arrayTypeRadiOrConsecutive[] = 'consecutivo';
            }

            /*** log Auditoria ***/
            HelperLog::logAdd(
                true,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['FileUpload'] . ' con el nombre: '.$fileUpload->name. $typeRadiOrConsecutivo .  $modelRadicado->numeroRadiRadicado . ' por el usuario: ' . $userLogin['nombreUserDetalles'] . ' ' . $userLogin['apellidoUserDetalles'], // texto para almacenar en el evento
                $dataOld,
                $dataNew, //Data
                array() //No validar estos campos
            );
            /***  Fin log Auditoria   ***/

            return [
                'status' => true,
                'data' => [],
                'errors' => [],
            ];

        } else {
            return [
                'status' => false,
                'data' => [],
                'errors' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
            ];
        }

        $radicadosProcesados[] = $modelRadicado->numeroRadiRadicado;
    }

    /** Función que se ejecuta cuando se utiliza el proceso de radicación, para saber la cantidad especifica de registros */
    public function actionComercial(){

        $jsonSend = Yii::$app->request->post('jsonSend'); 
        if (!empty($jsonSend)) {
                
            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //*** Fin desencriptación POST ***//

            $request = $request['data']; 
            $modelRadicados = RadiRadicados::find()
                ->where(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere([Yii::$app->params['like'], 'creacionRadiRadicado', date('Y-m')])
            ->all();

            if(count($modelRadicados) >= $request){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => [Yii::t('app', 'No se pueden crear mas registros en el sistema, comuniquese con el area comercial de SkinaTech.')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true); 
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', ''),
                    'data' => [Yii::t('app', '')],
                    'status' => Yii::$app->response->statusCode,
                ];
                return HelperEncryptAes::encrypt($response, true); 
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

}
