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


use Yii;
use yii\data\ActiveDataProvider;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

use yii\web\NotFoundHttpException;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperUserMenu;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperConsecutivo;

use api\models\RadiRadicados;
use api\models\RadiDocumentos;
use api\models\RadiAgendaRadicados;
use api\models\CgTransaccionesRadicados;
use api\models\CgProveedoresRegional;
use api\models\CgProveedores;
use api\models\CgEnvioServicios;
use api\models\RadiEnvios;

use api\models\UserDetalles;
use api\models\GdTrdDependencias;


use api\models\User;
use api\models\CgMotivosDevolucion;
use api\models\RadiRemitentes;
use api\models\Clientes;

use api\models\RadiLogRadicados;

use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\correo\CorreoController;

use api\components\HelperQueryDb;


/**
 * RadiAgendaController implements the CRUD actions for RadiAgendaRadicados model.
 */
class DistribucionyEnvioController extends Controller
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
                    'motivo-devolucion' => ['GET']
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
        /** Validar permisos del módulo */
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        /** Validar permisos del módulo */

        if (!empty($request)) {
            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación POST ***//
        }

        //Lista de usuarios
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if (is_array($request)) {
            foreach ($request['filterOperation'] as $field) {
                foreach ($field as $key => $info) {

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
                        if( isset($info) && $info !== null && trim($info) != '' ){
                            $dataWhere[$key] = $info;
                            //return $dataWhere;
                        }
                    }
                }
            }
        }

        $radiEnvios = RadiEnvios::find()->where(['<>','estadoRadiEnvio',Yii::$app->params['statusTodoText']['ListoEnvio']])->all();
        $arrayRadiEnvios = [];
        foreach($radiEnvios as $value){
            $arrayRadiEnvios[] = $value['idRadiRadicado'];
        }

        $relacionRadicados = RadiRadicados::find();
            /*->innerJoin('gdTrdTiposDocumentales', '`gdTrdTiposDocumentales`.`idGdTrdTipoDocumental` = `radiRadicados`.`idTrdTipoDocumental`')
            ->innerJoin('radiRemitentes', '`radiRemitentes`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            ->leftJoin('radiEnvios', '`radiEnvios`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            ;*/
        
        $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdTrdTiposDocumentales', ['radiRadicados' => 'idTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
        $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRemitentes', ['radiRadicados' => 'idRadiRadicado', 'radiRemitentes' => 'idRadiRadicado']);
        $relacionRadicados = HelperQueryDb::getQuery('leftJoin', $relacionRadicados, 'radiEnvios', ['radiRadicados' => 'idRadiRadicado', 'radiEnvios' => 'idRadiRadicado']);
                        
        //Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idRadiPersona':
                    $relacionRadicados->andWhere(['IN', 'radiRemitentes.' . $field, $value]);
                break;
                case 'idGdTrdTipoDocumental':
                    $relacionRadicados->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                break;
                case 'idCgProveedores':
                    $relacionRadicados->andWhere(['IN', 'radiEnvios.' . $field, $value]);
                break;
                case 'idCgEnvioServicio':
                    $relacionRadicados->andWhere(['IN', 'radiEnvios.' . $field, $value]);
                break;
                case 'idCgMotivoDevolucion':
                    //$relacionRadicados->innerJoin('radiEnviosDevolucion', '`radiEnviosDevolucion`.`idRadiEnvio` = `radiEnvios`.`idRadiEnvio`');
                    $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiEnviosDevolucion', ['radiEnvios' => 'idRadiEnvio', 'radiEnviosDevolucion' => 'idRadiEnvio']);
                    $relacionRadicados = $relacionRadicados->andWhere(['IN', 'radiEnviosDevolucion.' . $field, $value]);
                break;

                case 'fechaInicial':
                    $relacionRadicados->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relacionRadicados->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;

                case 'idCgMedioRecepcion':
                case 'idCgTipoRadicado':
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'estadoRadiEnvio':
                    if($value == Yii::$app->params['statusTodoText']['ListoEnvio']){

                        $relacionRadicados->andWhere(['radiRadicados.estadoRadiRadicado' => Yii::$app->params['statusTodoText']['ListoEnvio']])
                        ->andWhere(['NOT IN', 'radiRadicados.idRadiRadicado' , $arrayRadiEnvios]);
                        
                    }else{
                        $relacionRadicados->andWhere(['IN', 'radiEnvios.' . $field, $value]);
                    }
                break;
                default:
                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                break;
            }

        }
        $relacionRadicados->andWhere(['radiRadicados.estadoRadiRadicado' => Yii::$app->params['statusTodoText']['ListoEnvio']]);
        $relacionRadicados->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
        
        //Limite de la consulta
        $relacionRadicados->limit($limitRecords);
        $modelRelation = $relacionRadicados->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);
        
        foreach ($modelRelation as $infoRelation) {

            $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $infoRelation->idRadiRadicado]);  
            
            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($remitentes) > 1) {
                $nombreRemitente = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($remitentes as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelCliente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                       
                        
                        if($modelCliente){
                            $nombreRemitente = $modelCliente->nombreCliente;
                        }else{
                            $nombreRemitente = '';
                        }
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);                       
                        $nombreRemitente = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;               
                    }  
                }
            }

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->numeroRadiRadicado)),
            );

            
            $statusRadiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])->orderBy(['idRadiEnvio' => SORT_DESC])->one();
            if(isset($statusRadiEnvios)){
                $infoRelation->estadoRadiRadicado = $statusRadiEnvios->estadoRadiEnvio;
            }

            $dataList[] = array(
                'data' => $dataBase64Params,
                'id' => $infoRelation->idRadiRadicado,
                'TipoRadicado' => $infoRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'numeroRadiRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($infoRelation->numeroRadiRadicado, $infoRelation->idCgTipoRadicado, $infoRelation->isRadicado),
                'creacionRadiRadicado' => $infoRelation->creacionRadiRadicado,
                'asuntoRadiRadicado' => (strlen($infoRelation->asuntoRadiRadicado) > 150) ? substr($infoRelation->asuntoRadiRadicado, 0, 150) . '...' : $infoRelation->asuntoRadiRadicado,
                'nombreCliente' => $nombreRemitente,
                'nombreTipoDocumental' => $infoRelation->trdTipoDocumental->nombreTipoDocumental,
                'fechaVencimientoRadiRadicados' => $infoRelation->fechaVencimientoRadiRadicados,
                'prioridadRadicados' => Yii::t('app', 'statusPrioridadText')[$infoRelation->PrioridadRadiRadicados],   
                'statusText' => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoRadiRadicado],               
                'status' => $infoRelation->estadoRadiRadicado,
                'rowSelect' => false,
                'idInitialList' => 0,
            );

        }

        // Validar que el formulario exista
        $formType = HelperDynamicForms::setListadoBD('indexRadicadoCorrespondencia');

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
     *  Carga la lista de motivos de devolucion de gestion de correspondencia
     * 
     * @return array dataCgMotivosDevolucion 
     */
    public function actionMotivoDevolucion()
    {

        $dataCgMotivosDevolucion = [];

        $modelCgMotivosDevolucion = CgMotivosDevolucion::find()->where(['estadoCgMotivoDevolucion' => Yii::$app->params['statusTodoText']['Activo']])->all();

        foreach ($modelCgMotivosDevolucion as $row) {

            $dataCgMotivosDevolucion[] = array(
                "id" => (int) $row['idCgMotivoDevolucion'],
                "val" => $row['nombreCgMotivoDevolucion'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataCgMotivosDevolucion' => $dataCgMotivosDevolucion, // Tipos de devoluciones
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    // Esta función muestra la información de las transacciones que se pueden hacer por tipo de radicado
    // segun el tipo de radicado se consulta las transacciones y con las transacciones se valida en operaciones
    // si existe el regitro si es asi lo envia de lo contrario no.
    public function actionTransacciones()
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

            $transacciones = ['view','delivered','shipping','returnDelivery','correspondenceTemplate', 'correspondenceTemplateExcel'];
            $transaccionMostrar = [];

            foreach($request['dataIdRadicados'] as $key => $idRadiRadicado){
                
                // validacion en tabla RadiEnvios
                $RadiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $idRadiRadicado])->orderBy(['idRadiEnvio' => SORT_DESC])->one();

                if(isset($RadiEnvios)){ 

                    // si esta en estado [Devuelto] unset(devolucion y entregado)
                    if($RadiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['Devuelto']){ 
                        unset($transacciones[1],$transacciones[3],$transacciones[4], $transacciones[5]); 
                    }

                    // si esta en estado [listo envio] unset(devolucion y entregado)
                    if($RadiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['ListoEnvio']){ 
                        unset($transacciones[1],$transacciones[3],$transacciones[4], $transacciones[5]); 
                    }

                    // si esta en estado [pendiente entrega] unset(envio)
                    if($RadiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['PendienteEntrega']){ 
                        unset($transacciones[2]); 
                    }

                    // si esta en estado [entregado] unset(envio - entregado - devolucion)
                    if($RadiEnvios['estadoRadiEnvio'] == Yii::$app->params['statusTodoText']['Entregado']){ 
                        unset($transacciones[1],$transacciones[2],$transacciones[3],$transacciones[4], $transacciones[5]);
                    }
                
                }else{
                    // esta en estado listo para enviar
                    unset($transacciones[1],$transacciones[3],$transacciones[4], $transacciones[5]); 
                }

                /** Validar si el radicado está finalizado solo se puede ver */
                $modelRadiRadicados = RadiRadicados::find()->select(['estadoRadiRadicado'])->where(['idRadiRadicado' => $idRadiRadicado])->one();
                if ($modelRadiRadicados->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']) {
                    $transacciones = ['view'];
                }
            }

            // si estan seleccionados mas de 2 unset(view)
            if(count($request['dataIdRadicados']) > 1){
                unset($transacciones[0]); 
            }

            // consultar acciones segun transacciones salientes del fintro anterior
            $CgTransaccionesRadicados = CgTransaccionesRadicados::find()->where(
                ['in','actionCgTransaccionRadicado', $transacciones])
            ->all();

            $posicion = 0;

            foreach($CgTransaccionesRadicados as $key => $transaccionRadicado){

                $transaccionMostrar[$posicion]['route']  = $transaccionRadicado->rutaAccionCgTransaccionRadicado;
                $transaccionMostrar[$posicion]['action'] = $transaccionRadicado->actionCgTransaccionRadicado;
                $transaccionMostrar[$posicion]['title']  = $transaccionRadicado->titleCgTransaccionRadicado;
                $transaccionMostrar[$posicion]['icon']   = $transaccionRadicado->iconCgTransaccionRadicado;
                $transaccionMostrar[$posicion]['data']   = '';
                $posicion++;

            }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'OK',
                    'dataTransacciones' => $transaccionMostrar ?? [],
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
   



}
