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

namespace api\modules\version1\controllers\configuracionApp;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;


use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use api\models\GdTrdTiposDocumentales;
use api\models\RolesTipoRadicado;
use api\models\CgTiposRadicados;
use api\models\RolesTipoDocumental;
use api\models\CgTransaccionesRadicados;
use api\models\CgHorarioLaboral;
use api\models\RadiRadicados;


/**
 * CgTiemposRespuestaController implements the CRUD actions for CgHorarioLaboral model.
 */
class CgTiemposRespuestaController extends Controller
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
                    'index'  => ['GET'],
                    'index-one'  => ['GET'],
                    'general-filing-lists' => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'update'  => ['PUT'],
                    'change-status'  => ['PUT']
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
     * Lists all CgHorarioLaboral models.
     * @return mixed
     */
    public function actionIndex($request){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

            //Lista de radicados
            $dataList = [];   
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];
            
            //Se reitera el $request y obtener la informacion retornada
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
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }
            
            // Consulta para relacionar la informacion de dependencias y obtener 100 registros, a partir del filtro
            $horarioLaboral = CgHorarioLaboral::find();
            //Se reitera $dataWhere para solo obtener los campos con datos


            foreach($dataWhere as $field => $value){
                switch ($field) {
                    case 'diaInicioCgHorarioLaboral':
                        $horarioLaboral->andWhere([$field => $value]);
                    break;
                    case 'diaFinCgHorarioLaboral':
                        $horarioLaboral->andWhere([$field => $value]);
                    break;
                    case 'horaInicioCgHorarioLaboral':
                        $horaInicial = date("H:i",strtotime($value));
                        $horarioLaboral->andWhere(['=', 'horaInicioCgHorarioLaboral', trim($horaInicial) . Yii::$app->params['hourStart']]);
                    break;
                    case 'horaFinCgHorarioLaboral':
                        $horaFinal = date("H:i",strtotime($value)); 
                        $horarioLaboral->andWhere(['=', 'horaFinCgHorarioLaboral', trim($horaFinal) . Yii::$app->params['hourEnd']]);
                    break;
                    case 'estadoCgHorarioLaboral':
                        $horarioLaboral->andWhere(['IN',  $field, intval($value) ]);
                    break;
                    default:
                        $horarioLaboral->andWhere([Yii::$app->params['like'], $field, $value]);
                    break;
                }
            }           

            //Limite de la consulta
            $arrayDias = Yii::t('app', 'days');
            $horarioLaboral->limit($limitRecords);

            $modelHorarioLaboral = $horarioLaboral->orderBy(['estadoCgHorarioLaboral' => SORT_DESC])->all(); // Orden descendente para ver los últimos registros creados

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelHorarioLaboral = array_reverse($modelHorarioLaboral);
            
            foreach($modelHorarioLaboral as $horarioLaboral){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($horarioLaboral->idCgHorarioLaboral ))
                );

                $horaInicial = date("g:i A",strtotime($horarioLaboral->horaInicioCgHorarioLaboral));
                $horaFinal = date("g:i A",strtotime($horarioLaboral->horaFinCgHorarioLaboral));

                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $horarioLaboral->idCgHorarioLaboral,
                    'diaInicio' => $arrayDias[$horarioLaboral->diaInicioCgHorarioLaboral],
                    'diaFin' => $arrayDias[$horarioLaboral->diaFinCgHorarioLaboral],
                    'fechaInicio' => $horaInicial,
                    'fechaFin' => $horaFinal,
                    'createdate' => $horarioLaboral->fechaCgHorarioLaboral,
                    'status' => $horarioLaboral->estadoCgHorarioLaboral,
                    'statusText' =>  Yii::t('app', 'statusTodoNumber')[$horarioLaboral->estadoCgHorarioLaboral], 
                    'rowSelect' => false,
                    'idInitialList' => 0
                ];
            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgHorarioLaboral');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
           
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Updates an existing CgHorarioLaboral model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionChangeStatus()
    {
        
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
            
            if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

               

                $saveDataValid = true;
                $transaction = Yii::$app->db->beginTransaction();

                foreach ($request as $value) {

                    $dataExplode = explode('|', $value);
                    $model = $this->findModel($dataExplode[0]);

                    //Validar si existen radicados creados con el tipo
                    $statusOld = $model->estadoCgHorarioLaboral;

                    if ($model->estadoCgHorarioLaboral == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoCgHorarioLaboral = yii::$app->params['statusTodoText']['Inactivo'];
                    } else {
                        $model->estadoCgHorarioLaboral = yii::$app->params['statusTodoText']['Activo'];
                    }
                    
                    $updateAllmodel = CgHorarioLaboral::updateAll(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Inactivo']]);

                    if ($model->save()) {

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgHorarioLaboral] . ' el ' . CgHorarioLaboral::instance()->getAttributeLabel('idCgHorarioLaboral') . ': ' . $model->idCgHorarioLaboral . ' de la tabla ' . CgHorarioLaboral::tableName(),                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array(
                            'id' => $model->idCgHorarioLaboral, 'idInitialList' => $dataExplode[1] * 1,
                            'status' => $model->estadoCgHorarioLaboral, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgHorarioLaboral],
                            'statusOld' => $statusOld, 'statusTextOld' => Yii::t('app', 'statusTodoNumber')[$statusOld]
                        );
                    } else {
                        $errs[] = $model->getErrors();
                        $saveDataValid = false;
                    }
                }

                if ($saveDataValid) {
                    /** Validar si existe al menos un registro activo  */
                    $valActive = CgHorarioLaboral::find()
                        ->where(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']])
                        ->count();
                    if ($valActive == 0) {
                        $transaction->rollBack();
                        $dataResponse = $this->returnSameDataChangeStatus($request);
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorMinActiveHorarioLaboral')]],
                            'dataStatus' => $dataResponse,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Validar si existe al menos un registro activo  */

                    $transaction->commit();

                    $data = [];
                    foreach ($dataResponse as $value) {
                        $model = CgHorarioLaboral::find()->select(['estadoCgHorarioLaboral'])
                            ->where(['idCgHorarioLaboral' => $value['id']])->one();
                        $data[] = array(
                            'id' => $value['id'],
                            'idInitialList' => $value['idInitialList'],
                            'status' => $model['estadoCgHorarioLaboral'],
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCgHorarioLaboral']],
                        );
                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $data,
                        'status' => 200,
                        'reloadInitialList' => true, // Indicar al front que recargue el initial list
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    foreach ($dataResponse as &$value) {
                        $value['status'] = $value['statusOld'];
                        $value['statusText'] = $value['statusTextOld'];
                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $errs,
                        'dataStatus' => $dataResponse,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else{
                $dataResponse = $this->returnSameDataChangeStatus($request);
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'accessDenied')[1],
                    'data' => [],
                    'dataStatus' => $dataResponse,
                    'status' => Yii::$app->params['statusErrorAccessDenied'],
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

    private function returnSameDataChangeStatus($data)
    {
        $dataResponse = [];
        foreach ($data as $value) {
            $dataExplode = explode('|', $value);
            $model = CgHorarioLaboral::find()->select(['estadoCgHorarioLaboral'])->where(['idCgHorarioLaboral' => $dataExplode[0] ])->one();
            if ($model != null) {
                $dataResponse[] = array(
                    'id' => $dataExplode[0], 'idInitialList' => $dataExplode[1] * 1,
                    'status' => $model['estadoCgHorarioLaboral'], 'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCgHorarioLaboral']]
                );
            } else {
                throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
            }
        }
        return $dataResponse;
    }

    /**
     * Listado Select Horario Laboral
     * @return mixed
     */
    public function actionGeneralFilingLists(){

        $modelDays=  Yii::t('app', 'days') ?? []; 

        foreach ($modelDays as $key => $row) {
            $dataDays[] = array(
                "id" => $key,
                "val" => $row,
            );
        }

        # validacion creacion de primer registro
        $cgHorarioLaboral = CgHorarioLaboral::find()->one();

        $primerRegistro = true;

        if(isset($cgHorarioLaboral)){
            $primerRegistro = false;
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataDays' => $dataDays, 
            'primerRegistro' => $primerRegistro,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists One CgHorarioLaboral models.
     * @return mixed
     */
    public function actionIndexOne($request){

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
        $modelCgHorarioLaboral= $this->findModel($id);

        $horaInicial = date("g:i A",strtotime($modelCgHorarioLaboral->horaInicioCgHorarioLaboral));
        $horaFinal = date("g:i A",strtotime($modelCgHorarioLaboral->horaFinCgHorarioLaboral));

            $data = [
                'id' => $modelCgHorarioLaboral->idCgHorarioLaboral,
                'diaInicioCgHorarioLaboral'  => $modelCgHorarioLaboral->diaInicioCgHorarioLaboral,
                'diaFinCgHorarioLaboral'     => $modelCgHorarioLaboral->diaFinCgHorarioLaboral,
                'horaInicioCgHorarioLaboral' => $horaInicial,
                'horaFinCgHorarioLaboral'    => $horaFinal,
                'estadoCgHorarioLaboral'     => $modelCgHorarioLaboral->estadoCgHorarioLaboral,
            ];


        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Displays a single CgHorarioLaboral model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($request)
    {    
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
        
            if (!empty($request)) {
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
                $arrayDias = Yii::t('app', 'days');
                $data = [];

                $model = $this->findModel($id);
                $horaInicial = date("g:i A",strtotime($model->horaInicioCgHorarioLaboral));
                $horaFinal = date("g:i A",strtotime($model->horaFinCgHorarioLaboral));

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['View'].' la tabla ' . CgHorarioLaboral::tableName() . ', ' . CgHorarioLaboral::instance()->attributeLabels()['idCgHorarioLaboral'] . ' ' . $id, //texto para almacenar en el evento
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                //Retorno tipo de resdicados
                $data[] = ['alias' => 'Día Inicio',           'value' => $arrayDias[$model->diaInicioCgHorarioLaboral]];
                $data[] = ['alias' => 'Día Fin',              'value' => $arrayDias[$model->diaFinCgHorarioLaboral]];
                $data[] = ['alias' => 'Horario Inicio',       'value' => $horaInicial];
                $data[] = ['alias' => 'Horario Finalizacion', 'value' => $horaFinal];
                $data[] = ['alias' => 'Estado',               'value' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgHorarioLaboral]];
                $data[] = ['alias' => 'Fecha de creación',    'value' => $model->fechaCgHorarioLaboral];

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $data,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            }else {
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
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Creates a new CgHorarioLaboral model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {

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

                $dataLog = '';

                $request['estadoCgHorarioLaboral'] = Yii::$app->params['statusTodoText']['Activo'];
         
                $request['horaInicioCgHorarioLaboral'] = date("H:i:s",strtotime($request['horaInicioCgHorarioLaboral']));
                $request['horaFinCgHorarioLaboral'] = date("H:i:s",strtotime($request['horaFinCgHorarioLaboral']));
                
                $model = new CgHorarioLaboral();
                $model->attributes = $request;
                $model->fechaCgHorarioLaboral = date("Y-m-d H:i:s");

                if( $request['estadoCgHorarioLaboral'] ==  Yii::$app->params['statusTodoText']['Activo']){
                    $updateAllmodel = CgHorarioLaboral::updateAll(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Inactivo']]);
                }

                if ($model->save()){

                    # Se obtiene la información para el log
                    $dataLog = self::dataLog($model);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['createHL'].", en la tabla CgHorarioLaboral", //texto para almacenar en el evento
                        '',
                        $dataLog, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                   
                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }
        
                return $this->render('create', [
                    'model' => $model,
                ]);



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
     * Updates an existing CgHorarioLaboral model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {

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

                $request['horaInicioCgHorarioLaboral'] = date("H:i:s",strtotime($request['horaInicioCgHorarioLaboral']));
                $request['horaFinCgHorarioLaboral'] = date("H:i:s",strtotime($request['horaFinCgHorarioLaboral']));
                
                $model = $this->findModel($request['id']);
                $modelOld = clone $model;

                # Se obtiene la información para el log
                $dataOldLog = self::dataLog($modelOld);

                if( $request['estadoCgHorarioLaboral'] ==  Yii::$app->params['statusTodoText']['Activo']){
                    $CgHorarioLaboral = CgHorarioLaboral::find()->count();

                    if($CgHorarioLaboral != 1){
                        $updateAllmodel = CgHorarioLaboral::updateAll(
                           ['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Inactivo']],
                           ['and', ['<>','idCgHorarioLaboral',$request['id']]] 
                        );
                    }
  
                }

                $model->attributes = $request;

                //print_r($model->attributes);die();

                if ($model->save()){

                    /** Validar si existe al menos un registro activo  */
                    $valActive = CgHorarioLaboral::find()
                        ->where(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']])
                        ->count();
                    if ($valActive == 0) {                       
                        
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorMinActiveHorarioLaboral')]],                           
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Validar si existe al menos un registro activo  */

                    # Se obtiene la información para el log
                    $dataLog = self::dataLog($model);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Update']. ' la tabla ' . CgHorarioLaboral::tableName() . ' del ' . CgHorarioLaboral::instance()->attributeLabels()['idCgHorarioLaboral'] . ': ' . $model->idCgHorarioLaboral, //texto para almacenar en el evento
                        $dataOldLog,
                        $dataLog, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
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
     * Deletes an existing CgHorarioLaboral model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete()
    {
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

                $model = $this->findModel($request['id']);

                if ($model->estadoCgHorarioLaboral == yii::$app->params['statusTodoText']['Activo']) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion').' '.Yii::t('app', 'errorActivo'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }
        
                if ($model->delete()){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successDelete'),
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
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
     * Finds the CgHorarioLaboral model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgHorarioLaboral the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgHorarioLaboral::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $modelHorario [Modelo de la tabla]
     * @return $dataLog [String] [Información del modelo]
     **/
    protected function dataLog($modelHorario){

        if(!is_null($modelHorario)){

            $modelDays=  Yii::t('app', 'days') ?? []; 
           
            # Valores del modelo se utiliza para el log
            $labelModel = $modelHorario->attributeLabels();
            $dataLog = '';

            foreach ($modelHorario as $key => $value) {

                switch ($key) {
                    case 'estadoCgHorarioLaboral':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                    break;  
                    case 'diaInicioCgHorarioLaboral':
                    case 'diaFinCgHorarioLaboral':
                        foreach ($modelDays as $idDay => $day) {
                            if($idDay == $value){
                                $dataLog .= $labelModel[$key].': '.$day.', ';
                            }
                        }
                    break;
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }

            return $dataLog;
        }
    } 
}
