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
use yii\web\UploadedFile;

use yii\helpers\FileHelper;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\data\ActiveDataProvider;
use api\models\CgPlantillaVariables;

/**
 * CgGestionPlantillasController implements the CRUD actions for CgPlantillas model.
 */
class CgPlantillaVariablesController extends Controller
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
                    'change-status'  => ['PUT'],
                    'create' => ['POST'],
                    'update' => ['POST']
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
     * Lists all CgPlantillas models.
     * @return mixed
     */
    public function actionIndex($request)
    {   
        //if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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
            
            // Consulta para relacionar la informacion de dependencias y obtener 100 registros, a partir del filtro
            $varPlantillas = CgPlantillaVariables::find();

            //$plantillas->limit(Yii::$app->params['limitRecords']);
            $modelplantillas= $varPlantillas->orderBy(['estadoCgPlantillaVariable' => SORT_DESC])->all(); // Orden descendente para ver los últimos registros creados

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelplantillas = array_reverse($modelplantillas);
            
            foreach($modelplantillas as $varplantilla){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($varplantilla->idCgPlantillaVariable ))
                );

                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $varplantilla->idCgPlantillaVariable,
                    'nombreCgPlantillaVariable' => $varplantilla->nombreCgPlantillaVariable,
                    'descripcionCgPlantillaVariable' => $varplantilla->descripcionCgPlantillaVariable,
                    'createdate' => $varplantilla->creacionCgPlantillaVariable,
                    'status' => $varplantilla->estadoCgPlantillaVariable,
                    'statusText' =>  Yii::t('app', 'statusTodoNumber')[$varplantilla->estadoCgPlantillaVariable], 
                    'rowSelect' => false,
                    'idInitialList' => 0
                ];
            }

            /** Validar que el formulario exista */
            // $formType = HelperDynamicForms::setListadoBD('indexVariablePlantillas');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
           
        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app', 'accessDenied')[0],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }   
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

                    // Valores anteriores del modelo
                    $modelOld = [];
                    foreach ($model as $key => $value) {
                        $modelOld[$key] = $value;
                    }

                    //Validar si existen radicados creados con el tipo
                    $statusOld = $model->estadoCgPlantillaVariable;

                    if ($model->estadoCgPlantillaVariable == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoCgPlantillaVariable = yii::$app->params['statusTodoText']['Inactivo'];
                    } else {
                        $model->estadoCgPlantillaVariable = yii::$app->params['statusTodoText']['Activo'];
                    }
                    
                    if ($model->save()) {

                        $labels = $model->attributeLabels();
                        $dataOld = $labels['estadoCgPlantillaVariable'] . ': ' . Yii::$app->params['statusTodoNumber'][$statusOld];
                        $dataNew = $labels['estadoCgPlantillaVariable'] . ': ' . Yii::$app->params['statusTodoNumber'][$model->estadoCgPlantillaVariable];

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus2'] . " de la variable " . $model->nombreCgPlantillaVariable . ', ahora está ' . Yii::$app->params['statusTodoNumber'][$model->estadoCgPlantillaVariable], //texto para almacenar en el evento
                            $dataOld, //DataOld
                            $dataNew, //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array(
                            'id' => $model->idCgPlantillaVariable, 
                            'idInitialList' => $dataExplode[1] * 1,
                            'status' => $model->estadoCgPlantillaVariable, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgPlantillaVariable],
                            'statusOld' => $statusOld, 'statusTextOld' => Yii::t('app', 'statusTodoNumber')[$statusOld]
                        );

                    } else {
                        $errs[] = $model->getErrors();
                        $saveDataValid = false;
                    }
                }

                if ($saveDataValid) {
                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $dataResponse,
                        'status' => 200,
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

                return HelperEncryptAes::encrypt($response, true);

            
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

    /**
     *  Retorna la estructura de los id del initial list con su estado
     */
    private function returnSameDataChangeStatus($data)
    {
        $dataResponse = [];
        foreach ($data as $value) {
            $dataExplode = explode('|', $value);
            $model = CgPlantillaVariables::find()->select(['estadoCgPlantillaVariable'])->where(['idCgPlantillaVariable' => $dataExplode[0] ])->one();
            if ($model != null) {
                $dataResponse[] = array(
                    'id' => $dataExplode[0], 
                    'idInitialList' => $dataExplode[1] * 1,
                    'status' => $model['estadoCgPlantillaVariable'], 
                    'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCgPlantillaVariable']]
                );
            } else {
                throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
            }
        }
        return $dataResponse;
    }

    /**
     * Lists One CgHorarioLaboral models.
     * @return mixed
     */
    public function actionIndexOne($request) {
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
        $modelCgPlantillas = $this->findModel($id);

        $data = [
            'idCgPlantilla' => $modelCgPlantillas->idCgPlantillaVariable,
            'nombreCgPlantillaVariable'  => $modelCgPlantillas->nombreCgPlantillaVariable,
            'descripcionCgPlantillaVariable' => $modelCgPlantillas->descripcionCgPlantillaVariable,
            'estadoCgPlantillaVariable' => $modelCgPlantillas->estadoCgPlantillaVariable,
            'creacionCgPlantillaVariable' => $modelCgPlantillas->creacionCgPlantillaVariable
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
     * Action para crear variables de correspondencia
     */
    public function actionCreate() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
            if (!empty($jsonSend)) {
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

                $modelCgPlantillaVariables = new CgPlantillaVariables();
                $modelCgPlantillaVariables->attributes = $request;
                if (!$modelCgPlantillaVariables->save()) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelCgPlantillaVariables->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'data' => [],
                    'status' => 200
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
     * Action para actualizar variables de correspondencia
     */
    public function actionUpdate() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
            if (!empty($jsonSend)) {
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

                $modelCgPlantillaVariables = $this->findModel($request['id']);
                $modelCgPlantillaVariables->attributes = $request['data'];
                if (!$modelCgPlantillaVariables->save()) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelCgPlantillaVariables->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'data' => [],
                    'status' => 200
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
     * Finds the CgPlantillas model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgPlantillaVariables the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgPlantillaVariables::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
