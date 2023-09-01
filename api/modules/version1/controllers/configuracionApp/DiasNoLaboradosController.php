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
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\db\ExpressionInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\db\ActiveQuery;

use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperLog;
use api\components\HelperDynamicForms;

use api\models\CgDiasNoLaborados; 

use api\modules\version1\controllers\correo\CorreoController;
use yii\data\ActiveDataProvider;

/**
 * ClientesController implements the CRUD actions for Clientes model.
 */
class DiasNoLaboradosController extends Controller
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
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'change-status' => ['PUT']  
                ]                  
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

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
                        if ($key == 'fechaInicial' || $key == 'fechaFinal' || $key == 'fechaInicialDiaNoLaborado' || $key == 'fechaFinalDiaNoLaborado') {
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

            $ModelDiasNoLaborados = CgDiasNoLaborados::find();            

            foreach($dataWhere as $field => $value){

                switch ($field) {
                    case 'estadoCgDiaNoLaborado':
                        $ModelDiasNoLaborados->andWhere(['IN', 'cgDiasNoLaborados.' . $field, intval($value)]);
                    break;        
                    case 'fechaInicialDiaNoLaborado':
                        $ModelDiasNoLaborados->andWhere(['>=', 'cgDiasNoLaborados.fechaCgDiaNoLaborado', trim($value) ]);
                    break;
                    case 'fechaFinalDiaNoLaborado':
                        $ModelDiasNoLaborados->andWhere(['<=', 'cgDiasNoLaborados.fechaCgDiaNoLaborado', trim($value) ]);
                    break;  
                    case 'fechaInicial':
                        $ModelDiasNoLaborados->andWhere(['>=', 'cgDiasNoLaborados.creacionCgDiaNoLaborado', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $ModelDiasNoLaborados->andWhere(['<=', 'cgDiasNoLaborados.creacionCgDiaNoLaborado', trim($value) . Yii::$app->params['timeEnd']]);
                    break;  
                    default:
                        $ModelDiasNoLaborados->andWhere(['like', $field , $value]);
                    break;
                }                
            }

            //Limite de la consulta
            $ModelDiasNoLaborados->orderBy(['idCgDiaNoLaborado' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $ModelDiasNoLaborados->limit($limitRecords);
            $ModelDiasNoLaborados = $ModelDiasNoLaborados->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $ModelDiasNoLaborados = array_reverse($ModelDiasNoLaborados);
            
            //Lista de días no laborados
            $dataList = [];
            $dataBase64Params = "";            

            $dataList = [];

            foreach($ModelDiasNoLaborados as $diaNoLaboradoItem){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($diaNoLaboradoItem->idCgDiaNoLaborado))                
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $diaNoLaboradoItem->idCgDiaNoLaborado,
                    'fechaCgDiaNoLaborado' =>  date("Y-m-d", strtotime($diaNoLaboradoItem->fechaCgDiaNoLaborado)),                    
                    'creacionCgDiaNoLaborado' =>  $diaNoLaboradoItem->creacionCgDiaNoLaborado,
                    'statusText' => Yii::$app->params['statusTodoNumber'][$diaNoLaboradoItem->estadoCgDiaNoLaborado],
                    'status' => $diaNoLaboradoItem->estadoCgDiaNoLaborado,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );

            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgDiasNoLaborados');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

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
        $model = $this->findModel($id);

        $data = [
            'fechaCgDiaNoLaborado' => $model->fechaCgDiaNoLaborado.'T12:00:00',
            'estadoCgDiaNoLaborado' => $model->estadoCgDiaNoLaborado,
            'creacionCgDiaNoLaborado' => $model->creacionCgDiaNoLaborado,
        ];            

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
       
    }

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

                $arrayDate = $request['arrayDate'];
                $arrayDateExist = [];

                $arrayDataProcessed = [];

                $transaction = Yii::$app->db->beginTransaction();
                foreach ($arrayDate as $date) {

                    $fechaCgDiaNoLaborado = explode('T', $date)[0];
                    $modelExis = CgDiasNoLaborados::find()->where(['fechaCgDiaNoLaborado' => $fechaCgDiaNoLaborado])->one();

                    if ($modelExis != null) {
                        $arrayDateExist[] = $fechaCgDiaNoLaborado;
                    } else {
                        $modelDiaNoLaborado = new CgDiasNoLaborados();
                        $modelDiaNoLaborado->fechaCgDiaNoLaborado = $fechaCgDiaNoLaborado;
                        $modelDiaNoLaborado->estadoCgDiaNoLaborado = Yii::$app->params['statusTodoText']['Activo'];
                        $modelDiaNoLaborado->creacionCgDiaNoLaborado = Date('Y-m-d H:i:s');

                        if($modelDiaNoLaborado->save()){

                            $arrayDataProcessed[] = $modelDiaNoLaborado;

                            $modelDiaNoLaborado->estadoCgDiaNoLaborado = Yii::$app->params['statusTodoNumber'][$modelDiaNoLaborado->estadoCgDiaNoLaborado]; // Solo para log

                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['crear'] . " de día no laboral " . $modelDiaNoLaborado->fechaCgDiaNoLaborado, //texto para almacenar en el evento
                                [],
                                [$modelDiaNoLaborado], //Data
                                array() //No validar estos campos
                            );
                            /***    Fin log Auditoria   ***/

                        }else{
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelDiaNoLaborado->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }

                if (count($arrayDataProcessed) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ Yii::t('app', 'existeDiaNoLaboral') ],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    if (count($arrayDateExist) == 1) {
                        $message = Yii::t('app', 'successSaveDateWithExistOne', ['date' => $arrayDateExist[0]]);
                    } elseif (count($arrayDateExist) > 1) {
                        $message = Yii::t('app', 'successSaveDateWithExistMulti', ['dates' => implode(", ", $arrayDateExist)]);
                    } else {
                        $message = Yii::t('app', 'successSave');
                    }
                }

                $transaction->commit();

                # Respuesta satisfactoria del servicio
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $message,
                    'data' => $arrayDataProcessed,
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

    public function actionUpdate(){
        
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
                
                $id = $request['id'];

                $transaction = Yii::$app->db->beginTransaction();
                
                unset($request['creacionCgDiaNoLaborado']);
                $request['fechaCgDiaNoLaborado'] = explode('T', $request['fechaCgDiaNoLaborado'])[0];

                // Valida si encontró un registro
                $modelExis = CgDiasNoLaborados::find()->where([ 'fechaCgDiaNoLaborado' => $request['fechaCgDiaNoLaborado'] ])->all();
                if( $modelExis ){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ Yii::t('app', 'existeDiaNoLaboral') ],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $modelDiaNoLaborado = self::findModel($id);
                $clonedModelDiaNoLaborado = clone $modelDiaNoLaborado;
                $modelDiaNoLaborado->attributes = $request;

                if($modelDiaNoLaborado->save()){

                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Update'] . " el día no laboral " . $clonedModelDiaNoLaborado->fechaCgDiaNoLaborado, //texto para almacenar en el evento
                        [$clonedModelDiaNoLaborado],
                        [$modelDiaNoLaborado], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/

                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelDiaNoLaborado->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successUpdate'),
                    'data' => $modelDiaNoLaborado,
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

    public function actionChangeStatus()
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

                $saveDataValid = true;
                $transaction = Yii::$app->db->beginTransaction();

                foreach ($request as $value) {
                    $dataExplode = explode('|', $value);

                    $model = $this->findModel($dataExplode[0]);
                    $estadoOld = $model->estadoCgDiaNoLaborado;
                    if ($model->estadoCgDiaNoLaborado == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoCgDiaNoLaborado = yii::$app->params['statusTodoText']['Inactivo'];
                    } else {
                        $model->estadoCgDiaNoLaborado = yii::$app->params['statusTodoText']['Activo'];
                    }

                    if ($model->save()) {

                        $labels = $model->attributeLabels();
                        $dataOld = $labels['estadoCgDiaNoLaborado'] . ': ' . Yii::$app->params['statusTodoNumber'][$estadoOld];
                        $dataNew = $labels['estadoCgDiaNoLaborado'] . ': ' . Yii::$app->params['statusTodoNumber'][$model->estadoCgDiaNoLaborado];

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus2'] . " del día no laboral " . $model->fechaCgDiaNoLaborado . ', ahora está ' . Yii::$app->params['statusTodoNumber'][$model->estadoCgDiaNoLaborado], //texto para almacenar en el evento
                            $dataOld,
                            $dataNew, //Data
                            array() //No validar estos campos
                        );                  
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array('id' => $model->idCgDiaNoLaborado, 'status' => $model->estadoCgDiaNoLaborado, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgDiaNoLaborado], 'idInitialList' => $dataExplode[1] * 1);
                    } else {
                        $errors[] = $model->getErrors();
                        $saveDataValid = false;
                    }
                    
                }

            
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            if ($saveDataValid) {
                $transaction->commit();

                //Respuesta satisfactoria del servicio
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
                    'data' => $errors,
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

    protected function findModel($id)
    {
        if (($model = CgDiasNoLaborados::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
