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
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\models\GdTrdTiposDocumentales;
use api\models\RolesTipoDocumental;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

/**
 * TiposDocumentalController implements the CRUD actions for Gd Trd Tipos documental model.
 */
class TiposDocumentalController extends Controller
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
                    'index-list'  => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'update'  => ['PUT'],
                    'change-status'  => ['PUT'],
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
     * Lists all Tipos Documentales models.
     * @return mixed
     */
    public function actionIndex($jsonSend = null)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            if (!empty($jsonSend)) {

                //*** Inicio desencriptación POST ***//
                    $decrypted = HelperEncryptAes::decryptGET($jsonSend, true);
                    if ($decrypted['status'] == true) {
                        $request = $decrypted['request'];
                    } 
                //*** Fin desencriptación POST ***//          
            } 
            
            $data = array();
            $tiposDocumentales = GdTrdTiposDocumentales::find();

            if(isset($request['filterData']) && isset($request['filterOperation'])){                

                self::filterTable($request, $tiposDocumentales, 'actionIndex', 'tiposDocumentales');
            }

            $tiposDocumentales->orderBy(['idGdTrdTipoDocumental' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $tiposDocumentales->limit(Yii::$app->params['limitRecords']);
            $tiposDocumentales = $tiposDocumentales->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $tiposDocumentales = array_reverse($tiposDocumentales);
    
            foreach ($tiposDocumentales as $tipoDocumental) {
                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($tipoDocumental->idGdTrdTipoDocumental))
                );
                
                $data[] = array(
                    'data' => $dataBase64Params,
                    'id' => $tipoDocumental->idGdTrdTipoDocumental,
                    'nombreTipoDocumental' => $tipoDocumental->nombreTipoDocumental,
                    'diasTramite' => $tipoDocumental->diasTramiteTipoDocumental,
                    'statusText' => Yii::$app->params['statusTodoNumber'][$tipoDocumental->estadoTipoDocumental],
                    'status' => $tipoDocumental->estadoTipoDocumental,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );
                return $data;
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'filtersData' => self::getFilters('actionIndex'),
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

    public function actionIndexAll($request) {
               
        //if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

            if (isset($request['id'])) {
                $id = $request['id'];
            } else {
                $id = 0;
            }
            
            $modulos = [];
            $operaciones = [];
            $modulosFilter = [];
            $data = [];
            $rolesTiposDocumentales = [];
            
            //Se consulta que el estado este activo
            $findTiposDocumentales = GdTrdTiposDocumentales::find()->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])->all();
            $findRolesTipoDocumental = RolesTipoDocumental::find()->where(['idRol' => $id ])->all();

            foreach($findRolesTipoDocumental as $key => $rolTipoDocumental) {
                $rolesTiposDocumentales[] = $rolTipoDocumental->idGdTrdTipoDocumental;                    
            }

            //Pendiente seccion de modulos
            $modulosFilter = array('name' => 'Tipos Documentales', 'value' => false);
            
            //Lista de los tipos de documentales
            foreach($findTiposDocumentales as $key => $infoTipoDoc) {

                if( count($rolesTiposDocumentales) > 0) {

                    if(in_array($infoTipoDoc->idGdTrdTipoDocumental, $rolesTiposDocumentales)) {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => true);
                    } else {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => false);
                    }

                } else {
                    $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => false);
                }                
            }            
            
            //Lista de modulos
            if($id > 0) {
                $countXmodulo = 0;
                $countXmodulo = count($operaciones[$modulosFilter['name']]);

                foreach($operaciones[$modulosFilter['name']] as $keyOpera => $operacion) {

                    if($operacion['value'] == true) {
                        $countXmodulo = $countXmodulo - 1;
                    }
                }

                if($countXmodulo > 0) {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
                } else {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => true);
                }

            } else {
                $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
            }
            # Se asigna la data para que sea organizada
            $new_order = $operaciones['Tipos Documentales'];
            # Se ejecuta función para organizar por nombre
            self::array_sort_by($new_order, 'name', $order = SORT_ASC);
            # Se asigna la data de nuevo
            $operaciones['Tipos Documentales'] = $new_order;
            $data = array( 'nombreRol' => '', 'modulos' => $modulos, 'operaciones' => $operaciones);


            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        /*} else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }*/
    }


    /**
     * Función que organiza arrays por valores
     * @param $arrIni array
     * @param $col columna a organizar
     * @param $order orden que se quiere organizar
     * retorna el array
     */
    public function array_sort_by(&$arrIni, $col, $order = SORT_ASC)
    {
        $arrAux = array();
        foreach ($arrIni as $key=> $row)
        {
            $arrAux[$key] = is_object($row) ? $arrAux[$key] = $row->$col : $row[$col];
            $arrAux[$key] = strtolower($arrAux[$key]);
        }
        array_multisort($arrAux, $order, $arrIni);
    }

    public function actionIndexOne($request)
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
            $model = $this->findModel($id);

            $data = [
                'idGdTrdTipoDocumental' => $model['idGdTrdTipoDocumental'],
                'nombreTipoDocumental' => $model['nombreTipoDocumental'],
                'diasTramite' => $model['diasTramiteTipoDocumental'],
                //'estadoTipoDocumental' => $model['estadoTipoDocumental'],
                //'creacionTipoDocumental' => $model['creacionTipoDocumental'],
            ];

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
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

    
    /**
     * Displays a single Roles model.
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
            $model = $this->findModel($id);

            //Retorna el nombre de tipo documental
            $data[] = array('alias' => 'Nombre', 'value' => $model->nombreTipoDocumental);

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
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
     * Creates a new Tipos de documentales model.
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

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['id']);
                $modelGdTiposDocumental = new GdTrdTiposDocumentales();
                $modelGdTiposDocumental->attributes = $request;           

                if ($modelGdTiposDocumental->save()) {

                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['crear'] . ", en la tabla Trd Tipos documentales", //texto para almacenar en el evento
                            [],
                            [$modelGdTiposDocumental], //Data
                            array() //No validar estos campos
                        );     
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelGdTiposDocumental,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelGdTiposDocumental->getErrors(),
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
     * Updates an existing Tipos documentales model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
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

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idGdTrdTipoDocumental']);
                unset($request['creacionTipoDocumental']);

                $modelTiposDocumentales = $this->findModel($id);
                $modelTiposDocumentales->attributes = $request;

                if ($modelTiposDocumentales->save()) {

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['Edit'] . ", tabla Gd Tipos de documentales", //texto para almacenar en el evento
                            [],
                            [$modelTiposDocumentales], //Data
                            array() //No validar estos campos
                        );                    
                    /***    Fin log Auditoria   ***/

                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $modelTiposDocumentales,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelTiposDocumentales->getErrors(),
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
                if ($model->estadoTipoDocumental == yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoTipoDocumental = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->estadoTipoDocumental = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {
                    $dataResponse[] = array('id' => $model->idRol, 'status' => $model->estadoTipoDocumental, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoTipoDocumental], 'idInitialList' => $dataExplode[1] * 1);
                } else {
                    $errors[] = $model->getErrors();
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
                    'data' => $errors,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            return HelperEncryptAes::encrypt($response, true);

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
     * Finds the GdTrdTiposDocumentales model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GdTrdTiposDocumentales the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GdTrdTiposDocumentales::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected static function filterTable($filterRequest, $table, $action, $tableName){   

        //Obtener campos para filtrar
        $filters = Yii::$app->params['filters']['rolesController'][$action][$tableName];        

        //Determina la operación a utilizar para la búsqueda
        if($filterRequest['filterOperation'] == 10){
            $where = 'andWhere';
        }else if($filterRequest['filterOperation' == 15]){
            $where = 'orWhere';
        }

        //Si desde el request ha llegado filtros se filtra para la tabla User                                  
        foreach($filterRequest['filterData'] as $filterRequest){       

            foreach($filters as $filter){

                if($filterRequest['name'] == $filter['name']){                                                                   
                    $table->$where([$filter['name'] => $filterRequest['value']]);                                
                }
            }                                 
            
        }
    }

    protected static function getFilters($action){
        //Obtener campos para filtrar
        $filtersParams = Yii::$app->params['filters']['rolesController'][$action];

        $filters = [];

        //Recorre las tablas para obtener un array de filter plano;
        foreach($filtersParams as $table){
            foreach($table as $filter){
                $filters[] = $filter;
            }
        }   
        return $filters;
    }

}
