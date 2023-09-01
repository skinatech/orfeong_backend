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

namespace api\modules\version1\controllers;

use Yii;
use api\components\HelperDynamicForms;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperQueryDb;
use api\components\HelperRadicacion;
use api\components\HelperConsecutivo;
use api\models\Clientes;
use api\models\ClientesCiudadanosDetalles;
use api\models\GaHistoricoPrestamo;
use api\models\GdExpedientesInclusion;
use api\models\RadiEnvios;
use api\models\RadiLogRadicados;
use api\models\RadiRemitentes;
use api\models\CgReportes;

use DateTime;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\filters\auth\CompositeAuth;
use api\components\HelperValidatePermits;
use api\models\CgRegionales;
use api\models\CgTransaccionesRadicados;
use api\models\GdTrdDependencias;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiInformados;
use api\models\RadiRadicados;
use api\models\RadiRadicadosAsociados;
use api\models\RolesOperaciones;
use api\models\RolesTiposOperaciones;
use api\models\User;
use api\models\UserDetalles;

use api\models\views\ViewRadiCountByUser;
use api\models\views\ViewRadiResponseTime;

class ReportesController extends Controller{

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
                    'index-productivity'  => ['GET'],                
                    'index-pqrs'  => ['GET'],                
                    'index-mail-distribution'  => ['GET'],                
                    'index-electronic-records'  => ['GET'],                
                    'index-documentary-loans'  => ['GET'],                
                    'index-form'  => ['GET'],                
                    'index-radi-response-time' => ['GET'],
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
     * Lists all reporte productividad.
     * @return mixed
     */
    public function actionIndexProductivity($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecordsReports'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif ($key == 'isRadicado') {
                        $dataWhere[$key] = $info;
                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de productividad
        $relationFile =  RadiLogRadicados::find();
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']);
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':
                case 'idCgTipoRadicado':
                case 'idCgMedioRecepcion':
                case 'idTrdTipoDocumental': 
                case 'idTrdDepeUserCreador':
                case 'user_idCreador':
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'idTransaccion':
                    $relationFile->andWhere(['IN', 'radiLogRadicados.' . $field, $value]);
                break; 
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
            }
        }

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiLogRadicados.idRadiLogRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  

            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);   

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }
        
            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation->radiRadicado->fechaVencimientoRadiRadicados)); 

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiLogRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiLogRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->radiRadicado->numeroRadiRadicado, $dataRelation->radiRadicado->idCgTipoRadicado,$dataRelation->radiRadicado->isRadicado),
                'filingDate'            => $dataRelation->radiRadicado->creacionRadiRadicado,
                'expirationDate'        => $expiredDate, 
                'senderName'            => $senderName,
                'processingDependency'  => $dataRelation->radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->radiRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                'creatorDependency'     => $dataRelation->radiRadicado->idTrdDepeUserCreador0->nombreGdTrdDependencia,
                'creatorUser'           => $dataRelation->radiRadicado->userIdCreador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdCreador->userDetalles->apellidoUserDetalles,
                'filedType'             => $dataRelation->radiRadicado->cgTipoRadicado->nombreCgTipoRadicado,
                'documentaryType'       => $dataRelation->radiRadicado->trdTipoDocumental->nombreTipoDocumental,
                'receptionType'         => $dataRelation->radiRadicado->cgMedioRecepcion->nombreCgMedioRecepcion,
                'estado'                => Yii::t('app', 'statusTodoNumber')[$dataRelation->radiRadicado->estadoRadiRadicado].' - '.$dataRelation->transaccion->titleCgTransaccionRadicado,
                'status'                => $dataRelation->radiRadicado->estadoRadiRadicado,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteProductivo');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit1000($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de distribución de correspondencia.
     * @return mixed
     */
    public function actionIndexMailDistribution($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de distribución de correspondencia
        $relationSending =  RadiEnvios::find();
            //->leftJoin('radiRadicados', '`radiEnvios`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')     
            $relationSending = HelperQueryDb::getQuery('leftJoin', $relationSending, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiEnvios' => 'idRadiRadicado']);         
            //->leftJoin('radiEnviosDevolucion', '`radiEnvios`.`idRadiEnvio` = `radiEnviosDevolucion`.`idRadiEnvio`')
            $relationSending = HelperQueryDb::getQuery('leftJoin', $relationSending, 'radiEnviosDevolucion', ['radiEnviosDevolucion' => 'idRadiEnvio', 'radiEnvios' => 'idRadiEnvio']); 
            $relationSending = HelperQueryDb::getQuery('leftJoin', $relationSending, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);
            $relationSending = $relationSending->where(['radiRadicados.estadoRadiRadicado' => Yii::$app->params['statusTodoText']['ListoEnvio']]);               

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationSending->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationSending->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationSending->andWhere(['>=', 'radiEnvios.creacionRadiEnvio', trim($value) .  Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationSending->andWhere(['<=', 'radiEnvios.creacionRadiEnvio', trim($value) .  Yii::$app->params['timeEnd']]);
                break;
                case 'estadoRadiEnvio':
                case 'idCgProveedores':                 
                    $relationSending->andWhere(['IN', 'radiEnvios.'. $field, $value]);
                break;
                case 'idCgMotivoDevolucion':
                    $relationSending->andWhere(['IN', 'radiEnviosDevolucion.'.$field, $value]);
                break;
                case 'idCgTipoRadicado':                 
                    $relationSending->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                default: //numero guia
                    $relationSending->andWhere([Yii::$app->params['like'], 'radiEnvios.' . $field, $value]);
                break; 
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationSending->orderBy(['radiEnvios.idRadiEnvio' => SORT_DESC]); 

        # Limite de la consulta
        $relationSending->limit($limitRecords);
        $modelRelation = $relationSending->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

       
        foreach($modelRelation as $dataRelation) {  
            
            $reasonReturn = ''; //Motivo de devolución
          
            # Fechas segun el estado
            $dateSending = '';      //Fecha de pendiente por entregar
            $dateDeliver = '';      //Fecha de entrega
            $dateReturned = '';     //Fecha de devolución          

            switch($dataRelation->estadoRadiEnvio){
                case Yii::$app->params['statusTodoText']['PendienteEntrega']:
                    $dateSending = $dataRelation->creacionRadiEnvio;
                break;

                case Yii::$app->params['statusTodoText']['Entregado']:
                    $dateDeliver = $dataRelation->creacionRadiEnvio;
                break;
                
                case Yii::$app->params['statusTodoText']['Devuelto']:
                    $dateReturned = $dataRelation->creacionRadiEnvio;

                    # Se obtiene el nombre del motivo de devolución                   
                    foreach($dataRelation->radiEnviosDevolucions as $dataReason){
                        $reasonReturn = $dataReason->idCgMotivoDevolucion0->nombreCgMotivoDevolucion;               
                    }
                break;
            }

                               
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiEnvio)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiEnvio,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->idRadiRadicado0->numeroRadiRadicado, $dataRelation->idRadiRadicado0->idCgTipoRadicado,$dataRelation->idRadiRadicado0->isRadicado),
                'subject'               => $dataRelation->idRadiRadicado0->asuntoRadiRadicado, 
                'typeFiled'             => $dataRelation->idRadiRadicado0->cgTipoRadicado->nombreCgTipoRadicado, 
                'providerName'          => $dataRelation->idCgProveedores0->nombreCgProveedor,
                'filingDate'            => $dataRelation->idRadiRadicado0->creacionRadiRadicado,
                'dateSending'           => $dateSending,
                'dateDeliver'           => $dateDeliver,
                'dateReturned'          => $dateReturned,
                'deliveryNumber'        => $dataRelation->numeroGuiaRadiEnvio,
                'reasonReturn'          => $reasonReturn,
                'estado'                => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoRadiEnvio],
                'status'                => $dataRelation->estadoRadiEnvio,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }
        
        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteCorrespondencia');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de radicados PQRS.
     * @return mixed
     */
    public function actionIndexPqrs($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } elseif ($key == 'fechaVenciInicial' || $key == 'fechaVenciFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif( $info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de radicados PQRS
        $relationFile =  RadiLogRadicados::find();
            //->innerJoin('radiRadicados', '`radiRadicados`.`idRadiRadicado` = `radiLogRadicados`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']);
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);
            $relationFile = $relationFile->where(['idCgTipoRadicado' => Yii::$app->params['idCgTipoRadicado']['radiPqrs']]);               

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'fechaVenciInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.fechaVencimientoRadiRadicados', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaVenciFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.fechaVencimientoRadiRadicados', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':
                case 'idCgMedioRecepcion':
                case 'idTrdTipoDocumental':                           
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);                    
                break;
                case 'idTransaccion':
                    $relationFile->andWhere(['IN', 'radiLogRadicados.' . $field, $value]);
                break;          
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiLogRadicados.idRadiLogRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);       
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  

            $daysDifference = '';

            # Calculo de dias transcurridos entre la creación del radicado y cada transaccion
            $filingDate = new DateTime($dataRelation->radiRadicado->creacionRadiRadicado);
            $transactionDate = new DateTime($dataRelation->fechaRadiLogRadicado);

            $diff = $filingDate->diff($transactionDate);
            $daysDifference = $diff->days;


            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);   

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }     
        
            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation->radiRadicado->fechaVencimientoRadiRadicados));

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiLogRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiLogRadicado,
                'fileNumber'            => $dataRelation->radiRadicado->numeroRadiRadicado,
                'filingDate'            => $dataRelation->radiRadicado->creacionRadiRadicado,
                'expirationDate'        => $expiredDate, 
                'subject'               => $dataRelation->radiRadicado->asuntoRadiRadicado, 
                'senderName'            => $senderName,
                'processingDependency'  => $dataRelation->radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->radiRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                'documentaryType'       => $dataRelation->radiRadicado->trdTipoDocumental->nombreTipoDocumental,
                'transaction'           => $dataRelation->transaccion->titleCgTransaccionRadicado,              
                'daysDifference'        => $daysDifference,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReportePqrs');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de expedientes electrónicos.
     * @return mixed
     */
    public function actionIndexElectronicRecords($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif( trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }                          
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de expedientes electronicos
        $relationRecord =  GdExpedientesInclusion::find();
            //->innerJoin('gdExpedientes', '`gdExpedientes`.`idGdExpediente` = `gdExpedientesInclusion`.`idGdExpediente`'); 
            $relationRecord = HelperQueryDb::getQuery('innerJoin', $relationRecord, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);    

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationRecord->andWhere(['>=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationRecord->andWhere(['<=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value) . Yii::$app->params['timeEnd']]);
                break;                
                case 'idGdTrdDependencia':                 
                case 'idUser':                 
                case 'idGdTrdSerie':                 
                case 'idGdTrdSubserie':    
                case 'estadoGdExpediente':             
                    $relationRecord->andWhere(['IN', 'gdExpedientes.'. $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationRecord->orderBy(['gdExpedientesInclusion.idGdExpedienteInclusion' => SORT_DESC]); 

        # Limite de la consulta
        $relationRecord->limit($limitRecords);
        $modelRelation = $relationRecord->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  
                    
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGdExpedienteInclusion)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idGdExpedienteInclusion,
                'expedientNumber'   => $dataRelation->gdExpediente->numeroGdExpediente,
                'serieName'         => $dataRelation->gdExpediente->gdTrdSerie->nombreGdTrdSerie, 
                'subserieName'      => $dataRelation->gdExpediente->gdTrdSubserie->nombreGdTrdSubserie, 
                'processDate'       => $dataRelation->gdExpediente->fechaProcesoGdExpediente,
                'dependency'        => $dataRelation->gdExpediente->gdTrdDependencia->nombreGdTrdDependencia,
                'userDependency'    => $dataRelation->gdExpediente->user->userDetalles->nombreUserDetalles.' '.$dataRelation->gdExpediente->user->userDetalles->apellidoUserDetalles,
                'fileNumber'        => $dataRelation->radiRadicado->numeroRadiRadicado,
                'documentaryType'   => $dataRelation->radiRadicado->trdTipoDocumental->nombreTipoDocumental,                
                'estado'        => Yii::t('app', 'statusTodoNumber')[$dataRelation->gdExpediente->estadoGdExpediente],
                'status'            => $dataRelation->gdExpediente->estadoGdExpediente,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }
        
        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteExpediente');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de prestamo documental.
     * @return mixed
     */
    public function actionIndexDocumentaryLoans($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de prestamo documental
        $relationLoan =  GaHistoricoPrestamo::find();          
            //->innerJoin('gaPrestamos', '`gaHistoricoPrestamo`.`idGaPrestamo` = `gaPrestamos`.`idGaPrestamo`');  
            $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gaPrestamos', ['gaPrestamos' => 'idGaPrestamo', 'gaHistoricoPrestamo' => 'idGaPrestamo']);               

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idTipoPrestamoGaPrestamo':
                case 'idGdTrdDependencia':
                case 'idUser':
                    $relationLoan->andWhere(['IN', 'gaPrestamos.' . $field, $value]);
                break;
                case 'fechaInicial':
                    $relationLoan->andWhere(['>=', 'gaHistoricoPrestamo.fechaGaHistoricoPrestamo', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationLoan->andWhere(['<=', 'gaHistoricoPrestamo.fechaGaHistoricoPrestamo', trim($value) . Yii::$app->params['timeEnd']]);
                break;
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationLoan->orderBy(['gaHistoricoPrestamo.idGaHistoricoPrestamo' => SORT_DESC]); 

        # Limite de la consulta
        $relationLoan->limit($limitRecords);
        $modelRelation = $relationLoan->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        $arrayData = [];
        foreach($modelRelation as $i => $dataRelation) {  

            # Fechas de creación segun estado
            $requestDate = '';      //Fecha de solicitud
            $returnedDate = '';     //Fecha de devolución
            $approvedDate = '';     //Fecha de aprobación
            $canceledDate = '';     //Fecha de cancelado 

            switch($dataRelation->estadoGaHistoricoPrestamo){
                case Yii::$app->params['statusTodoText']['SolicitudPrestamo']:
                    # Fecha en solicitud
                    $requestDate = $dataRelation->fechaGaHistoricoPrestamo;

                    #Array con id Prestamo, id Estado = Fecha prestamo
                    $arrayData[$dataRelation->idGaPrestamo][$dataRelation->estadoGaHistoricoPrestamo] = $requestDate;
                break;                

                case Yii::$app->params['statusTodoText']['PrestamoAprobado']:
                    # Fecha de aprobación
                    $approvedDate = $dataRelation->fechaGaHistoricoPrestamo;
                   
                    #Array con id Prestamo, id Estado = Fecha prestamo
                    $arrayData[$dataRelation->idGaPrestamo][$dataRelation->estadoGaHistoricoPrestamo] = $approvedDate;
                break;

                case Yii::$app->params['statusTodoText']['PrestamoDevuelto']:
                    # Fecha de devolución
                    $returnedDate = $dataRelation->fechaGaHistoricoPrestamo;

                    #Array con id Prestamo, id Estado = Fecha prestamo
                    $arrayData[$dataRelation->idGaPrestamo][$dataRelation->estadoGaHistoricoPrestamo] = $returnedDate;
                break;

                case Yii::$app->params['statusTodoText']['PrestamoCancelado']:
                    # Fecha de cancelación
                    $canceledDate = $dataRelation->fechaGaHistoricoPrestamo;   
                    
                    #Array con id Prestamo, id Estado = Fecha prestamo
                    $arrayData[$dataRelation->idGaPrestamo][$dataRelation->estadoGaHistoricoPrestamo] = $canceledDate;
                break;
            }
        }

        $daysDifference = [];
       
        if(is_array($arrayData)) {

            # Iteración de array de fechas para el calculo de dias de diferencia entre estados
            foreach($arrayData as $idPrestamo => $value){

                if(isset($value[Yii::$app->params['statusTodoText']['SolicitudPrestamo']])){
                    $date1 = new DateTime($value[Yii::$app->params['statusTodoText']['SolicitudPrestamo']]);
    
                    if(isset($value[Yii::$app->params['statusTodoText']['PrestamoAprobado']])){                   
                        $date2 = new DateTime($value[Yii::$app->params['statusTodoText']['PrestamoAprobado']]);
    
                        $diff = $date1->diff($date2);
                        $daysDifference[$idPrestamo][] = 'Solicita - Aprueba: ('. $diff->days.')';
                    } 
    
                    if(isset($value[Yii::$app->params['statusTodoText']['PrestamoCancelado']])){
                        $date3 = new DateTime($value[Yii::$app->params['statusTodoText']['PrestamoCancelado']]);
    
                        $diff = $date1->diff($date3);
                        $daysDifference[$idPrestamo][] = 'Solicita - Cancela: ('.$diff->days.')';
                    }
    
                } else {
                    $daysDifference[$idPrestamo][] = '';
                }
    
                if(isset($value[Yii::$app->params['statusTodoText']['PrestamoAprobado']])){
                    $date2 = new DateTime($value[Yii::$app->params['statusTodoText']['PrestamoAprobado']]);
    
                    if(isset($value[Yii::$app->params['statusTodoText']['PrestamoDevuelto']])){                   
                        $date4 = new DateTime($value[Yii::$app->params['statusTodoText']['PrestamoDevuelto']]);
    
                        $diff = $date2->diff($date4);
                        $daysDifference[$idPrestamo][] = 'Aprueba - Devuelve: ('.$diff->days.')';
                    } 
    
                } else {
                    $daysDifference[$idPrestamo][] = '';
                }
            }
        }         

        $difference = '';

        # Iteración de la consulta principal
        foreach($modelRelation as $i => $dataRelation) {

            # Dias de diferencia entre fechas
            $difference = implode(", ", $daysDifference[$dataRelation->idGaPrestamo]);

            # Fechas de creación segun estado
            $requestDate = '';      //Fecha de solicitud
            $returnedDate = '';     //Fecha de devolución
            $approvedDate = '';     //Fecha de aprobación
            $canceledDate = '';     //Fecha de cancelado  

            switch($dataRelation->estadoGaHistoricoPrestamo){
                case Yii::$app->params['statusTodoText']['SolicitudPrestamo']:
                    # Fecha en solicitud
                    $requestDate = date('Y-m-d', strtotime($dataRelation->fechaGaHistoricoPrestamo));
                break;                

                case Yii::$app->params['statusTodoText']['PrestamoAprobado']:
                    # Fecha de aprobación
                    $approvedDate = date('Y-m-d', strtotime($dataRelation->fechaGaHistoricoPrestamo));
                break;

                case Yii::$app->params['statusTodoText']['PrestamoDevuelto']:
                    # Fecha de devolución
                    $returnedDate = date('Y-m-d', strtotime($dataRelation->fechaGaHistoricoPrestamo));
                break;

                case Yii::$app->params['statusTodoText']['PrestamoCancelado']:
                    # Fecha de cancelación
                    $canceledDate = date('Y-m-d', strtotime($dataRelation->fechaGaHistoricoPrestamo));
                break;
            }          
                    

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaHistoricoPrestamo)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idGaHistoricoPrestamo,    
                'fileNumber'        => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado, $dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->idCgTipoRadicado, $dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->isRadicado),
                'subject'           => $dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->asuntoRadiRadicado,
                'loanType'          => Yii::t('app', 'statusLoanTypeNumber')[$dataRelation->idGaPrestamo0->idTipoPrestamoGaPrestamo], 
                'requestDate'       => $requestDate,
                'returnedDate'      => $returnedDate,
                'approvedDate'      => $approvedDate,
                'canceledDate'      => $canceledDate,
                'daysDifference'    => $difference,      
                'dependencyLoan'    => $dataRelation->idGaPrestamo0->idGdTrdDependencia0->nombreGdTrdDependencia,
                'userLoan'          => $dataRelation->idGaPrestamo0->idUser0->userDetalles->nombreUserDetalles.' '.$dataRelation->idGaPrestamo0->idUser0->userDetalles->apellidoUserDetalles,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                  
        }       
        
        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReportePrestamos');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de formulario de caracterización
     * @return mixed
     */
    public function actionIndexForm($request) 
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte del formulario de caracterización
        $relationClient =  ClientesCiudadanosDetalles::find();
            //->innerJoin('clientes', '`clientes`.`idCliente` = `clientesCiudadanosDetalles`.`idCliente`');    
            $relationClient = HelperQueryDb::getQuery('innerJoin', $relationClient, 'clientes', ['clientes' => 'idCliente', 'clientesCiudadanosDetalles' => 'idCliente']);  

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationClient->andWhere(['>=', 'clientesCiudadanosDetalles.creacionClienteCiudadanoDetalle', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationClient->andWhere(['<=', 'clientesCiudadanosDetalles.creacionClienteCiudadanoDetalle', trim($value) . Yii::$app->params['timeEnd']]);
                break;                
                case 'generoClienteCiudadanoDetalle':                 
                case 'rangoEdadClienteCiudadanoDetalle':                 
                case 'vulnerabilidadClienteCiudadanoDetalle':                 
                case 'etniaClienteCiudadanoDetalle':            
                    $relationClient->andWhere(['IN', 'clientesCiudadanosDetalles.'. $field, $value]);
                break;   
                case 'idNivelGeografico3':
                case 'idNivelGeografico2':
                case 'idNivelGeografico1':
                    $relationClient->andWhere(['IN', 'clientes.'. $field, $value]);
                break;   
                default: // Nombre y correo del cliente
                    $relationClient->andWhere([Yii::$app->params['like'], 'clientes.' . $field, $value]);
                break;         
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationClient->orderBy(['clientesCiudadanosDetalles.idClienteCiudadanoDetalle' => SORT_DESC]); 

        # Limite de la consulta
        $relationClient->limit($limitRecords);
        $modelRelation = $relationClient->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  
                    
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idClienteCiudadanoDetalle)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idClienteCiudadanoDetalle,
                'clientName'        => $dataRelation->cliente->nombreCliente,
                'address'           => $dataRelation->cliente->direccionCliente, 
                'email'             => $dataRelation->cliente->correoElectronicoCliente, 
                'registrationDate'  => $dataRelation->creacionClienteCiudadanoDetalle,
                'gender'            => Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'][$dataRelation->generoClienteCiudadanoDetalle],
                'ageRange'          => Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'][$dataRelation->rangoEdadClienteCiudadanoDetalle],
                'vulnerability'     => Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'][$dataRelation->vulnerabilidadClienteCiudadanoDetalle],
                'ethnicity'         => Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'][$dataRelation->etniaClienteCiudadanoDetalle],
                'country'           => $dataRelation->cliente->idNivelGeografico10->nomNivelGeografico1,
                'department'        => $dataRelation->cliente->idNivelGeografico20->nomNivelGeografico2, 
                'municipality'      => $dataRelation->cliente->idNivelGeografico30->nomNivelGeografico3,  
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }
        
        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteCaracterizacion');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }


    /**
     * Lists all reporte general.
     * @return mixed
     */
    public function actionIndexGeneral($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif($key == 'isRadicado') {
                        $dataWhere[$key] =  $info;
                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
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

        # Consulta para el reporte general
        $relationFile =  RadiRadicados::find();
            //->innerJoin('radiRemitentes', '`radiRemitentes`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            //->innerJoin('gdTrdDependencias', '`gdTrdDependencias`.`idGdTrdDependencia` = `radiRadicados`.`idTrdDepeUserTramitador`')
            //->innerJoin('cgRegionales', '`cgRegionales`.`idCgRegional` = `gdTrdDependencias`.`idCgRegional`');  
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);               
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'radiRadicados' => 'idTrdDepeUserTramitador']);               
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'cgRegionales', ['cgRegionales' => 'idCgRegional', 'gdTrdDependencias' => 'idCgRegional']);               
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idRadiPersona': //remitente
                    $relationFile->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':
                case 'idTrdTipoDocumental':                
                case 'idCgMedioRecepcion':                   
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'idCgRegional':
                    $relationFile->andWhere(['IN', 'cgRegionales.' . $field, $value]);
                break; 
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
                 default: // numero radicado, asunto
                    $relationFile->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  
            
            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);   

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }
            
            #Se obtiene la última transacción del radicado
            $modelLogRadicados = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $dataRelation->idRadiRadicado])
                ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
            ->one();

            $transaction = '';
            if(!empty($modelLogRadicados)){
                $transaction = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
            }

            # Consultas para obtener la regional
            $modelDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => $dataRelation->idTrdDepeUserTramitador]);
            $modelRegional = CgRegionales::findOne(['idCgRegional' => $modelDependence->idCgRegional]);

           
            # Información de los informados
            $informedUser = [];
            $informedDependency = [];
            $modelInformed = RadiInformados::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);

            foreach($modelInformed as $dataInformed){
                $informedUser[] = $dataInformed->user->userDetalles->nombreUserDetalles. ' '. $dataInformed->user->userDetalles->apellidoUserDetalles;

                $informedDependency[] = $dataInformed->user->gdTrdDependencia->nombreGdTrdDependencia;
            }

            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation->fechaVencimientoRadiRadicados));

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
            );


            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado,$dataRelation->isRadicado),
                'filedType'             => $dataRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'filingDate'            => $dataRelation->creacionRadiRadicado,
                'expirationDate'        => $expiredDate, 
                'subject'               => $dataRelation->asuntoRadiRadicado, 
                'documentaryType'       => $dataRelation->trdTipoDocumental->nombreTipoDocumental,
                'receptionType'         => $dataRelation->cgMedioRecepcion->nombreCgMedioRecepcion,
                'senderName'            => $senderName,
                'regional'              => $modelRegional->nombreCgRegional,                
                'processingDependency'  => $dataRelation->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                'creatorDependency'     => $dataRelation->idTrdDepeUserCreador0->nombreGdTrdDependencia,
                'creatorUser'           => $dataRelation->userIdCreador->userDetalles->nombreUserDetalles.' '.$dataRelation->userIdCreador->userDetalles->apellidoUserDetalles,  
                'informedDependency'    => $informedDependency,                
                'informedUser'          => $informedUser,                              
                'estado'                => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoRadiRadicado].' - '.$transaction,
                'status'                => $dataRelation->estadoRadiRadicado,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteGeneral');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte trazabilidad del radicado.
     * @return mixed
     */
    public function actionIndexFilingTraceability($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';

        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } elseif($key == 'isRadicado'){
                        $dataWhere[$key] =  $info;
                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') {  
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

        # Consulta para el reporte de trazabilidad del radicado
        $relationFile =  RadiLogRadicados::find();
            // ->innerJoin('radiRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            // ->innerJoin('radiRemitentes', '`radiRemitentes`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`'); 
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']);     
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);     
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idRadiPersona': //remitente
                    $relationFile->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':                   
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
                default: //Numero radicado
                    $relationFile->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiLogRadicados.idRadiLogRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  

            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);   

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }      
        
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiLogRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiLogRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->radiRadicado->numeroRadiRadicado, $dataRelation->radiRadicado->idCgTipoRadicado,$dataRelation->radiRadicado->isRadicado),
                'filedType'             => $dataRelation->radiRadicado->cgTipoRadicado->nombreCgTipoRadicado,
                'filingDate'            => $dataRelation->radiRadicado->creacionRadiRadicado,
                'subject'               => $dataRelation->radiRadicado->asuntoRadiRadicado,
                'senderName'            => $senderName,
                'processingDependency'  => $dataRelation->radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->radiRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                'transactionDate'       => $dataRelation->fechaRadiLogRadicado,
                'observation'           => $dataRelation->observacionRadiLogRadicado,
                'userTransaction'       => $dataRelation->user->userDetalles->nombreUserDetalles.' '.$dataRelation->user->userDetalles->apellidoUserDetalles,
                'estado'                => Yii::t('app', 'statusTodoNumber')[$dataRelation->radiRadicado->estadoRadiRadicado].' - '.$dataRelation->transaccion->titleCgTransaccionRadicado,
                'status'                => $dataRelation->radiRadicado->estadoRadiRadicado,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteTrazabilidad');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte por usuario
     * @return mixed
     */
    public function actionIndexUsers($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        $data = []; // Array de conteos
        $today = date("Y-m-d");

        # Transacción crear radicado
        $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);
        $createFile = $modelTransaction->idCgTransaccionRadicado;

        # Transacción firmar documento
        $modelTransaction2 = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'signDocument']);
        $signDocument = $modelTransaction2->idCgTransaccionRadicado;

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];

        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Se consulta la vista que consulta la cantidad de tipos de radicados por usuario
        $relationModelView = ViewRadiCountByUser::find();

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idTrdDepeUserTramitador':
                    $relationModelView->andWhere(['IN', 'idGdTrdDependencia', $value]);
                break;
                case 'user_idTramitador':
                    $relationModelView->andWhere(['IN', 'idUser', $value]);
                break;
            }
        }

        $relationModelView->limit($limitRecords);
        $relationModelView = $relationModelView->all();

        foreach($relationModelView as $row) {


            # Esquema para generar el string del count antes del SELECT
            $count = HelperQueryDb::getColumnCount('radiRadicados','idRadiRadicado','count'); 

            # Cantidad de radicados del usuario iterado, con logs de firma de documento
            $relationLog = RadiLogRadicados::find()->select([$count]);
            $relationLog = HelperQueryDb::getQuery('innerJoin', $relationLog, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']);
            $relationLog->andWhere(['radiRadicados.user_idTramitador' => $row['idUser']]);
            $relationLog->andWhere(['radiRadicados.idTrdDepeUserTramitador' => $row['idGdTrdDependencia']]);
            $relationLog->andWhere(['radiLogRadicados.idTransaccion' => $signDocument]);
            $relationLog->groupBy(['radiRadicados.idRadiRadicado']);

            $totalFiledAnswer = $relationLog->count();

            # Cantidad de radicados del usuario iterado, con logs diferentes a "creación de radicado"
            $relationLog = RadiLogRadicados::find()->select([$count]);
            $relationLog = HelperQueryDb::getQuery('innerJoin', $relationLog, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']);
            $relationLog->andWhere(['radiRadicados.user_idTramitador' => $row['idUser']]);
            $relationLog->andWhere(['radiRadicados.idTrdDepeUserTramitador' => $row['idGdTrdDependencia']]);
            $relationLog->andWhere(['<>', 'radiLogRadicados.idTransaccion', $createFile]);
            $relationLog->groupBy(['radiRadicados.idRadiRadicado']);

            $totalRadiWithLogDiferentToNew = $relationLog->count();

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', ' -'), base64_encode($row['idUser'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                        => $dataBase64Params,
                'id'                          => $row['idUser'],
                'processingDependency'        => $row['nombreGdTrdDependencia'],
                'processingUser'              => $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'],
                'totalDocuments'              => $row['countRadicados'],
                'totalFiledTypeExit'          => $row['countSalida'],
                'totalFiledTypeEntry'         => $row['countEntrada'],
                'totalFiledTypePqrs'          => $row['countPqr'],
                'totalFiledTypeCommunication' => $row['countComunicacionInterna'],

                'totalNewFile'                => $row['countRadicados'] - $totalRadiWithLogDiferentToNew,
                'totalFiledAnswer'            => $totalFiledAnswer,
                'totalFiledExpired'           => $row['countVencidos'],
                'rowSelect'                   => false,
                'idInitialList'               => 0
            );
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteUsuarios');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de respuestas y tiempos de contestación
     * @return mixed
     */
    public function actionIndexResponseAndTimes($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];
        $associatedNumber =  [];  // Array de numeros de respuesta
        $associatedNumbers =  "";  // Numero radicado respuesta en string
        $differenceDays = 0;      // Tiempo de respuesta

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';

        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif($key == 'isRadicado'){
                        $dataWhere[$key] =  $info;
                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') {  
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

        # Transacción firmar documento
        $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'signDocument']);
        $idTransaction = $modelTransaction->idCgTransaccionRadicado;

        $modelTransaction2 = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'physicallySigned']);
        $idTransaction2 = $modelTransaction2->idCgTransaccionRadicado;

        # Transacción finalizar radicado
        $modelTransactionFinalizar = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'finalizeFiling']);
        $finalizeFiling = $modelTransactionFinalizar->idCgTransaccionRadicado;

        # Consulta para el reporte de respuesta y tiempos de constestación
        $relationFile =  RadiRadicados::find();
         $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiDocumentosPrincipales', ['radiDocumentosPrincipales' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']); 
         $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']); 

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idRadiPersona': //remitente
                    $relationFile->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':                   
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
                default: //Numero radicado
                    $relationFile->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);
        
        foreach($modelRelation as $dataRelation) {  

            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);       
            
            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }  

            # Consulta para obtener el número radicado de respuesta
            $modelDocument = RadiDocumentosPrincipales::find();
            $modelDocument = $modelDocument->where(['or',
                ['radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                ['radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']],
                // ['radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente']],
                ['radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['CombinadoSinFirmas']]
            ]);
            $modelDocument = $modelDocument->andWhere(['idRadiRadicado' => $dataRelation->idRadiRadicado]);
            $modelDocument = $modelDocument->all();

            if($modelDocument){

                foreach($modelDocument as $document){  
                    
                    if(isset($document->idRadiRespuesta)){
                        $associatedNumber[$dataRelation->idRadiRadicado][] = $document->idRadiRespuesta0->numeroRadiRadicado;
                    } else {
                        $associatedNumber[$dataRelation->idRadiRadicado] = [];
                    } 
                } 

                if(is_array($associatedNumber[$dataRelation->idRadiRadicado])){
                    $associatedNumbers = ltrim(implode(", ",$associatedNumber[$dataRelation->idRadiRadicado]), ",");
                } else {
                    $associatedNumbers = '';
                }

                # Consulta para obtener la fecha y tiempo de respuesta
                //$modelLog = RadiLogRadicados::findOne(['idTransaccion' => $idTransaction, 'idRadiRadicado' => $dataRelation->idRadiRadicado]);
                $modelLog = RadiLogRadicados::find();
                $modelLog = $modelLog->where(['or',['idTransaccion' => $idTransaction],['idTransaccion' => $idTransaction2]]);
                $modelLog = $modelLog->andWhere(['idRadiRadicado' => $dataRelation->idRadiRadicado]);
                $modelLog = $modelLog->one();

                if(!is_null($modelLog)){

                    $dateResponse = $modelLog->fechaRadiLogRadicado;

                    $date1 = new DateTime($dateResponse);
                    $date2 = new DateTime($dataRelation->creacionRadiRadicado);

                    $diff = $date2->diff($date1);
                    // $differenceDays = $diff->days;

                    $differenceDays = HelperRadicacion::calcularDiasEntreFechas($dateResponse, $dataRelation->creacionRadiRadicado);
                    $differenceDays = (int) $differenceDays * -1;
                }
            }
            else{
                $associatedNumbers = '';
                $dateResponse = '';
                $differenceDays = '';
            }
               
            # Consulta para obtener usuario que finalizo
            $modelLogFinalizado = RadiLogRadicados::find();
            $modelLogFinalizado = $modelLogFinalizado->where(['idTransaccion' => $finalizeFiling]);
            $modelLogFinalizado = $modelLogFinalizado->andWhere(['idRadiRadicado' => $dataRelation->idRadiRadicado]);
            $modelLogFinalizado = $modelLogFinalizado->one();

            if(!is_null($modelLogFinalizado)){
                $dependenciaResponsable = $modelLogFinalizado->dependencia->nombreGdTrdDependencia;
                $usuarioResponsable = $modelLogFinalizado->user->userDetalles->nombreUserDetalles.' '.$modelLogFinalizado->user->userDetalles->apellidoUserDetalles;
            }else{
                $dependenciaResponsable = '';
                $usuarioResponsable = '';
            }

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado,$dataRelation->isRadicado),
                'filedType'             => $dataRelation->cgTipoRadicado->nombreCgTipoRadicado,                
                'filingDate'            => $dataRelation->creacionRadiRadicado,
                'subject'               => $dataRelation->asuntoRadiRadicado,
                'senderName'            => $senderName,
                'processingDependency'  => $dataRelation->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                'numberResponse'        => $associatedNumbers,
                'dateResponse'          => $dateResponse,
                'dateExpiration'        => $dataRelation->fechaVencimientoRadiRadicados,
                'daysResponse'          => $differenceDays,
                'responsibleDependency' => $dependenciaResponsable,  
                'responsibleUser'       => $usuarioResponsable,               
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteRespuesta');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de documentos pendientes.
     * @return mixed
     */
    public function actionIndexPendingDocuments($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif ($key == 'isRadicado') {
                        $dataWhere[$key] = $info;
                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
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

        $idsStatus = [
            Yii::$app->params['statusTodoText']['Finalizado'],
            Yii::$app->params['statusTodoText']['Archivado']
        ];

        $today = date('Y-m-d');

        # Consulta para el reporte de docuemntos pendientes
        $relationFile =  RadiRadicados::find();
            //->innerJoin('radiRemitentes', '`radiRemitentes`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')   
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);      
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);
            $relationFile = $relationFile->where(['NOT IN', 'estadoRadiRadicado', $idsStatus])
            ->andWhere(['<=', 'creacionRadiRadicado', $today ]);      

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idRadiPersona': //remitente
                    $relationFile->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':                  
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
                default: // numero radicado
                    $relationFile->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;              
            }                
        }

        $relationFile->andWhere('estadoRadiRadicado <> '.Yii::$app->params['statusTodoText']['Inactivo']);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  

            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);
            
            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($sender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($sender as $dataSender){

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelSender = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);                       
                        
                        if($modelSender){
                            $senderName = $modelSender->nombreCliente;
                        }else{
                            $senderName = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelSender = User::findOne(['id' => $dataSender->idRadiPersona]);                       
                        $senderName = $modelSender->userDetalles->nombreUserDetalles.' '.$modelSender->userDetalles->apellidoUserDetalles;               
                    }
                }
            }
            
            # Tiempo transcurrido desde que se radico
            $filingDate = new DateTime($dataRelation->creacionRadiRadicado);
            $date = new DateTime();
    
            $diff = $filingDate->diff($date);

            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation->fechaVencimientoRadiRadicados)); 

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado,$dataRelation->isRadicado),
                'filedType'             => $dataRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'filingDate'            => $dataRelation->creacionRadiRadicado,
                'expirationDate'        => $expiredDate,
                'subject'               => $dataRelation->asuntoRadiRadicado, 
                'senderName'            => $senderName,
                'processingDependency'  => $dataRelation->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                'days'                  => $diff->days,    
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteDocumentos');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de usuarios activos.
     * @return mixed
     */
    public function actionIndexActiveUser($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    }  else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de usuarios activos
        $relationUser =  User::find();
            $relationUser = $relationUser->where(['status' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['<>', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo'] ]);               

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {               
                case 'idGdTrdDependencia':
                case 'id':
                case 'idRol':                  
                    $relationUser->andWhere(['IN', 'user.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationUser->orderBy(['user.id' => SORT_DESC]); 

        # Limite de la consulta
        $relationUser->limit($limitRecords);
        $modelRelation = $relationUser->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->id)),
            );
           
            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->id,
                'dependency'            => $dataRelation->gdTrdDependencia->nombreGdTrdDependencia,
                'userName'              => $dataRelation->userDetalles->nombreUserDetalles.' '.$dataRelation->userDetalles->apellidoUserDetalles,
                'job'                   => $dataRelation->userDetalles->cargoUserDetalles,
                'role'                  => $dataRelation->rol->nombreRol, 
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteUsuariosActivos');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de usuarios con permiso de firma.
     * @return mixed
     */
    public function actionIndexUserPermissionSignature($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    }  else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') {  
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Operación de firma de documento.
        $modelOperation = RolesOperaciones::findOne(['nombreRolOperacion' => 'version1%radicacion%transacciones%sign-document']);

        # Consulta para el reporte de usuarios con permiso de firma
        $relationUser =  User::find();
            //->innerJoin('roles', '`roles`.`idRol` = `user`.`idRol`')
            $relationUser = HelperQueryDb::getQuery('innerJoin', $relationUser, 'roles', ['roles' => 'idRol', 'user' => 'idRol']);   
            //->innerJoin('rolesTiposOperaciones', '`rolesTiposOperaciones`.`idRol` = `roles`.`idRol`')
            $relationUser = HelperQueryDb::getQuery('innerJoin', $relationUser, 'rolesTiposOperaciones', ['rolesTiposOperaciones' => 'idRol', 'roles' => 'idRol']);   
            $relationUser = $relationUser->where(['user.status' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['rolesTiposOperaciones.idRolOperacion' => $modelOperation->idRolOperacion ]);               

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {               
                case 'idGdTrdDependencia':
                case 'id':                         
                    $relationUser->andWhere(['IN', 'user.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationUser->orderBy(['user.id' => SORT_DESC]); 

        # Limite de la consulta
        $relationUser->limit($limitRecords);
        $modelRelation = $relationUser->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->id)),
            );
           
            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->id,
                'dependency'            => $dataRelation->gdTrdDependencia->nombreGdTrdDependencia,
                'userName'              => $dataRelation->userDetalles->nombreUserDetalles.' '.$dataRelation->userDetalles->apellidoUserDetalles,
                'identification'        => $dataRelation->userDetalles->documento,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteConPermiso');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de devolución
     * @return mixed
     */
    public function actionIndexReturns($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif ($key == 'isRadicado') {
                        $dataWhere[$key] = $info;
                    }  else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Transacción de devolución de radicado
        $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'returnFiling']);
        $idTransaction = $modelTransaction->idCgTransaccionRadicado;

        # Consulta para el reporte de devolución
        $relationFile =  RadiLogRadicados::find();
            //->innerJoin('radiRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiLogRadicados' => 'idRadiRadicado']); 
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiInformados', ['radiRadicados' => 'idRadiRadicado', 'radiInformados' => 'idRadiRadicado']);
            $relationFile = $relationFile->where(['radiLogRadicados.idTransaccion' => $idTransaction]);               

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
            $relationFile->andWhere(['or',
                ['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                ['radiInformados.idUser' => Yii::$app->user->identity->id]
            ]);
        }

        if (!array_key_exists('isRadicado', $dataWhere)) {
            $dataWhere['isRadicado'] = 1;
        }

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'fechaInicial':
                    $relationFile->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFile->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':                   
                    $relationFile->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'isRadicado':
                    $relationFile->andWhere(['radiRadicados.' . $field =>  $value]);
                break;
                default: //Numero radicado, asunto
                    $relationFile->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;              
            }                
        }
        
        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiLogRadicados.idRadiLogRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit($limitRecords);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiLogRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $dataRelation->idRadiLogRadicado,
                'fileNumber'            => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->radiRadicado->numeroRadiRadicado, $dataRelation->radiRadicado->idCgTipoRadicado,$dataRelation->radiRadicado->isRadicado),
                'filingDate'            => $dataRelation->radiRadicado->creacionRadiRadicado,
                'filedType'             => $dataRelation->radiRadicado->cgTipoRadicado->nombreCgTipoRadicado, 
                'subject'               => $dataRelation->radiRadicado->asuntoRadiRadicado,
                'creatorDependency'     => $dataRelation->radiRadicado->idTrdDepeUserCreador0->nombreGdTrdDependencia,
                'creatorUser'           => $dataRelation->radiRadicado->userIdCreador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdCreador->userDetalles->apellidoUserDetalles,
                'processingDependency'  => $dataRelation->radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                'processingUser'        => $dataRelation->radiRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->radiRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                'transactionDependency' => $dataRelation->dependencia->nombreGdTrdDependencia,
                'transactionUser'       => $dataRelation->user->userDetalles->nombreUserDetalles.' '.$dataRelation->user->userDetalles->apellidoUserDetalles,
                'observation'             => $dataRelation->observacionRadiLogRadicado,
                'estado'            => Yii::t('app', 'statusTodoNumber')[$dataRelation->radiRadicado->estadoRadiRadicado].' - '.$dataRelation->transaccion->titleCgTransaccionRadicado,
                'status'                => $dataRelation->radiRadicado->estadoRadiRadicado,
                'rowSelect'             => false,
                'idInitialList'         => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteDevolucion');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /**
     * Lists all reporte de remitente destinatario
     * @return mixed
     */
    public function actionIndexSenderRecipient($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';

        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    }  elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       

                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Validación del nombre, documento y correo de los remitentes
        $idUser = [];
        if (!empty($dataSender) || !empty($dataEmail)){

            $modelCliente = Clientes::find()
                ->where([Yii::$app->params['like'],'nombreCliente', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'numeroDocumentoCliente', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'correoElectronicoCliente', $dataSender])
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

            $modelUserDetalle = UserDetalles::find()
                ->where([Yii::$app->params['like'],'nombreUserDetalles', $name])
                ->orWhere([Yii::$app->params['like'], 'apellidoUserDetalles', $surname])
                ->orWhere([Yii::$app->params['like'], 'documento', $dataSender])
            ->all();

            foreach($modelUserDetalle as $infoUserDetalle){
                $idUser[] = $infoUserDetalle->idUser;
            }

            $modelUser = User::find()
                ->where([Yii::$app->params['like'],'email', $dataSender])
            ->all();

            foreach($modelUser as $infoUser){
                $idUser[] = $infoUser->id;
            }
        }


        # Consulta para el reporte de remitente destinatario
        $relationSender =  RadiRadicados::find();
            //->innerJoin('radiRemitentes', '`radiRemitentes`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`');
            $relationSender = HelperQueryDb::getQuery('innerJoin', $relationSender, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);                

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {  
                case 'idRadiPersona': //remitente, correo
                    $relationSender->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;             
                case 'fechaInicial':
                    $relationSender->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationSender->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;            
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationSender->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationSender->limit($limitRecords);
        $modelRelation = $relationSender->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        $arraySender = [];
        foreach($modelRelation as $dataRelation) {

            # Información del remitente
            $sender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]); 
            
            foreach($sender as $dataSender){

                # Se construye un array que contiene los ids del remitente sin repetirse
                if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                    
                    if (isset($arraySender['client'])) {
                        if(!in_array($dataSender->idRadiPersona, $arraySender['client'])){
                            $arraySender['client'][] = $dataSender->idRadiPersona;
                        }
                    } else {
                        $arraySender['client'][] = $dataSender->idRadiPersona;
                    }                    
    
                } else {
                    if (isset($arraySender['user'])) {
                        if(!in_array($dataSender->idRadiPersona, $arraySender['user'])){
                            $arraySender['user'][] = $dataSender->idRadiPersona;
                        }
                    } else {
                        $arraySender['user'][] = $dataSender->idRadiPersona;
                    }
                }
            }           
        }

        # Se valida que existe los ids de los clientes
        if(isset($arraySender['client'])){
            $modelSender = Clientes::findAll(['idCliente' => $arraySender['client']]);

            foreach($modelSender as $dataClient){
            
                if($dataClient){
                    $senderName = $dataClient->nombreCliente; 
                    $senderDocument= $dataClient->numeroDocumentoCliente;                   
                    $senderEmail = $dataClient->correoElectronicoCliente;                   
                    $senderAddress = $dataClient->direccionCliente; 
                }else{
                    $senderName = ''; 
                    $senderDocument= '';                   
                    $senderEmail = '';                   
                    $senderAddress = ''; 
                }                

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataClient->idCliente)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataClient->idCliente, //$dataRelation->idRadiRadicado,
                    'document'          => $senderDocument,
                    'senderName'        => $senderName,
                    'email'             => $senderEmail,
                    'address'           => $senderAddress,
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                ); 
            }
        } 

        # Se valida que existe los ids de los usuarios
        if(isset($arraySender['user'])) {

            $modelSender = User::findAll(['id' => $arraySender['user']]);

            foreach($modelSender as $dataUser){

                $senderName = $dataUser->userDetalles->nombreUserDetalles.' '.$dataUser->userDetalles->apellidoUserDetalles;
                $senderDocument= $dataUser->userDetalles->documento;      
                $senderEmail = $dataUser->email;                   
                $senderAddress = $dataUser->gdTrdDependencia->nombreGdTrdDependencia; 

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataUser->id)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataUser->id, //$dataRelation->idRadiRadicado,
                    'document'          => $senderDocument,
                    'senderName'        => $senderName,
                    'email'             => $senderEmail,
                    'address'           => $senderAddress,
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                ); 
            }
        }


        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexReporteRemitente');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }
   

    /**
     * Lists all reporte de permisos de operación segun el perfil
     * @return mixed
     */
    public function actionIndexPermissionRole($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    }  else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Consulta para el reporte de permisos de operaciones a los perfiles
        $relationPermission =  RolesTiposOperaciones::find();
            $relationPermission = HelperQueryDb::getQuery('innerJoin', $relationPermission, 'rolesOperaciones', ['rolesOperaciones' => 'idRolOperacion', 'rolesTiposOperaciones' => 'idRolOperacion']);                

        # Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {               
                case 'idRolModuloOperacion':                
                    $relationPermission->andWhere(['IN', 'rolesOperaciones.' . $field, $value]);
                break;   
                case 'idRol':                  
                    $relationPermission->andWhere(['IN', 'rolesTiposOperaciones.' . $field, $value]);
                break;     
                default: //Operacion, submodulo
                    $relationPermission->andWhere([Yii::$app->params['like'], 'rolesOperaciones.' . $field, $value]);
                break;              
            }                
        }

        # Orden descendente para ver los últimos registros creados
        $relationPermission->orderBy(['rolesTiposOperaciones.idRolTipoOperacion' => SORT_DESC]); 

        # Limite de la consulta
        $relationPermission->limit($limitRecords);
        $modelRelation = $relationPermission->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRolTipoOperacion)),
            );
           
            # Listado de informacion
            $dataList[] = array(
                'data'            => $dataBase64Params,
                'id'              => $dataRelation->idRolTipoOperacion,
                'role'            => $dataRelation->rol->nombreRol,
                'module'          => $dataRelation->rolOperacion->rolModuloOperacion->nombreRolModuloOperacion,
                'submodule'       => $dataRelation->rolOperacion->moduloRolOperacion,
                'operation'       => $dataRelation->rolOperacion->aliasRolOperacion, 
                'rowSelect'       => false,
                'idInitialList'   => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteRolPermiso');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Lists all reporte de radicados por tiempo de respuesta y dependencia
     * @return mixed
     */
    public function actionIndexRadiResponseTime($request)
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

        //Lista de campos de la configuracion
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        # Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if ( is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }

                    }  else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif($info != '') { 
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }
        }

        # Se consulta la vista que proporciona los radicados con la fecha de respuesta
        $relationModelView = ViewRadiResponseTime::find();

        # Se itera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idTrdDepeUserTramitador':
                case 'user_idTramitador':
                case 'idCgTipoRadicado':
                case 'idCgRegional':
                case 'idTrdTipoDocumental':
                    $relationModelView->andWhere(['IN', $field, $value]);
                break;
                case 'fechaInicial':
                    $relationModelView->andWhere(['>=', 'creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationModelView->andWhere(['<=', 'creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
            }
        }

        # Orden por dependencia
        $relationModelView->orderBy(['idTrdDepeUserTramitador' => SORT_DESC]); 

        # Limite de la consulta
        $relationModelView->limit($limitRecords);
        $modelRelation = $relationModelView->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        $idsRadicados = [];
        $arrayDataByDependence = [];
        /** Recorrer array para agrupar por radicado */
        foreach ($modelRelation as $value) {
            if (!in_array($value['idRadiRadicado'], $idsRadicados)) {

                /** Validar si la dependencia ya esta en el array */
                if (isset($arrayDataByDependence[$value['idTrdDepeUserTramitador']])) {

                    $arrayDataByDependence[$value['idTrdDepeUserTramitador']]['total']++;
                    if ($value['fechaRespuesta'] == null) {
                        $arrayDataByDependence[$value['idTrdDepeUserTramitador']]['pendienteRespuesta']++;
                    } else {
                        /** Validar dias de respuesta */
                        $diasRespuesta = HelperRadicacion::calcularDiasEntreFechas($value['creacionRadiRadicado'], $value['fechaRespuesta']);
                        if ($diasRespuesta <= 15) {
                            $columna = 'D' . $diasRespuesta; 
                            $arrayDataByDependence[$value['idTrdDepeUserTramitador']][$columna]++;
                        } else {
                            $arrayDataByDependence[$value['idTrdDepeUserTramitador']]['D15+']++;
                        }
                        /** Fin Validar dias de respuesta */
                    }

                } else {
                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($value['idTrdDepeUserTramitador'])),
                    );

                    $arrayDataByDependence[$value['idTrdDepeUserTramitador']] = [
                        'data' => $dataBase64Params,
                        'id'   => $value['idTrdDepeUserTramitador'],
                        'nombreGdTrdDependencia'  => $value['codigoGdTrdDependencia'] . ' - ' . $value['nombreGdTrdDependencia'],
                        'D0'  => 0,
                        'D1'  => 0,
                        'D2'  => 0,
                        'D3'  => 0,
                        'D4'  => 0,
                        'D5'  => 0,
                        'D6'  => 0,
                        'D7'  => 0,
                        'D8'  => 0,
                        'D9'  => 0,
                        'D10' => 0,
                        'D11' => 0,
                        'D12' => 0,
                        'D13' => 0,
                        'D14' => 0,
                        'D15' => 0,
                        'D15+' => 0,
                        'pendienteRespuesta' => ($value['fechaRespuesta'] == null) ? 1 : 0,
                        'total'              => 1,
                        'rowSelect'          => false,
                        'idInitialList'      => 0,
                    ];

                    if ($value['fechaRespuesta'] != null) {
                        /** Validar dias de respuesta */
                        $diasRespuesta = HelperRadicacion::calcularDiasEntreFechas($value['creacionRadiRadicado'], $value['fechaRespuesta']);
                        if ($diasRespuesta <= 15) {
                            $columna = 'D' . $diasRespuesta; 
                            $arrayDataByDependence[$value['idTrdDepeUserTramitador']][$columna]++;
                        } else {
                            $arrayDataByDependence[$value['idTrdDepeUserTramitador']]['D15+']++;
                        }
                        /** Fin Validar dias de respuesta */
                    }
                }

                $idsRadicados[] = $value['idRadiRadicado'];
            }
        }
        /** Fin Recorrer array para agrupar por radicado */

        foreach ($arrayDataByDependence as $row) {
            $dataList[] = $row;
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexReporteRadiResponseTime');

        Yii::$app->response->statusCode = 200;
        /*return*/ $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Lists all cgReportes
     * @return dataReporte
     */
    public function actionIndexList(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $modelReporte = CgReportes::find()
                ->where(['estadoCgReporte' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();
            
            $dataReporte = [];
            foreach($modelReporte as $reporte){
                $dataReporte[] = [
                    'ruta' => $reporte->actionCgReporte,
                    'label' => $reporte->nombreCgReporte,
                    'description' => $reporte->descripcionCgReporte,
                    'dtTitles' => Yii::$app->params['cabeceraReportes'][$reporte->idCgReporte]
                ];
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataReporte,
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

}
