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

namespace api\modules\version1\controllers\roles;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperDynamicForms;
use Yii;
use api\models\RolesOperaciones;
use api\models\RolesTiposOperaciones;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;
use api\components\HelperValidatePermits;

/**
 * RolesOperacionesController implements the CRUD actions for RolesOperaciones model.
 */
class RolesOperacionesController extends Controller
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
                    'index-all'  => ['GET'],
                    'index-one'  => ['GET'],
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
     * Lists all RolesOperaciones models.
     * @return mixed
     */
    public function actionIndex($request)
    {   

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            //El $request obtiene 'filterOperation' => [["aliasRolOperacion"=>"Consultar usuarios", "moduloRolOperacion"=>""]],
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
            
            //Lista de roles de operacion
            $dataList = []; 
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if (is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info){

                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                        }
                    }
                }
            }
            
            // Consulta para relacionar la informacion de roles operaciones y obtener 100 registros, a partir del filtro
            $rolesOperaciones = RolesOperaciones::find();
            
            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){
                $rolesOperaciones->andWhere(['like', $field , $value]);
            }                
            //Limite de la consulta
            $rolesOperaciones->orderBy(['idRolOperacion' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $rolesOperaciones->limit($limitRecords);
            $modelOperaciones = $rolesOperaciones->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelOperaciones = array_reverse($modelOperaciones);

            foreach($modelOperaciones as $infoOperacion) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoOperacion->idRolOperacion))
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $infoOperacion->idRolOperacion,
                    'aliasRolOperacion' => $infoOperacion->aliasRolOperacion,
                    'moduloRolOperacion' => $infoOperacion->moduloRolOperacion,
                    'nombreRolOperacion' => $infoOperacion->nombreRolOperacion,
                    'statusText' => Yii::t('app','statusTodoNumber')[$infoOperacion->estadoRolOperacion],
                    'status' => $infoOperacion->estadoRolOperacion,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::createDataForForm('indexRolOperation');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList), 'limitRecords' => $limitRecords]) : false,                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','accessDenied')[0],
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
            $rolesTiposOperaciones = [];

            $findRolesOperaciones = RolesOperaciones::find()
                ->where(['estadoRolOperacion' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy(['moduloRolOperacion' => SORT_ASC])
            ->all();

            if($id > 0) {
                $findRolesTiposOperaciones = RolesTiposOperaciones::find()->where(['idRol' => $id])->all();
                foreach($findRolesTiposOperaciones as $key => $rolTipoOperacion) {
                    $rolesTiposOperaciones[] = $rolTipoOperacion->idRolOperacion;
                }
            }

            // SI el sistema esta configurado como orfeo express validar el arreglo de modulos a ocultar
            if(Yii::$app->params['orfeoNgExpress'] == true){
                $modulosOcultos[] = Yii::$app->params['modulosNgExpress'];
            }else{
                $modulosOcultos[] = array();
            }

            foreach($findRolesOperaciones as $keyRol => $rolOperacion) {
                if($rolOperacion->moduloRolOperacion != "Global") {

                    // SI orfeoNg es express se debe excluir de los modulos del sistema los parametrizados en params 
                    if(!empty($modulosOcultos)){

                        // SI el modulo no esta en el arreglo mostrarlo de lo contrario no
                        if(!in_array($rolOperacion->moduloRolOperacion, $modulosOcultos[0])){

                            $modulosFilter[$rolOperacion->moduloRolOperacion] = $rolOperacion->moduloRolOperacion;
                            if($id > 0) {
                                if(in_array($rolOperacion->idRolOperacion, $rolesTiposOperaciones)) {
                                    $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => true);
                                } else {
                                    $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => false);
                                }

                            } else {
                                $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => false);
                            }
                        }
                        
                    }else{

                        $modulosFilter[$rolOperacion->moduloRolOperacion] = $rolOperacion->moduloRolOperacion;
                        if($id > 0) {
                            if(in_array($rolOperacion->idRolOperacion, $rolesTiposOperaciones)) {
                                $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => true);
                            } else {
                                $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => false);
                            }

                        } else {
                            $operaciones[$rolOperacion->moduloRolOperacion][] = array('id' => $rolOperacion->idRolOperacion, 'name' => $rolOperacion->aliasRolOperacion, 'value' => false);
                        }
                    }
                }
            }

            foreach($modulosFilter as $key => $moduloFilter) {

                if($id > 0) {
                    $countOperacionesXmodulo = 0;

                    // SI orfeoNg es express se debe excluir de los modulos del sistema los parametrizados en params 
                    if(!empty($modulosOcultos)){

                        // SI el modulo no esta en el arreglo mostrarlo de lo contrario no
                        if(!in_array($key, $modulosOcultos[0])){

                            $countOperacionesXmodulo = count($operaciones[$key]);

                            foreach($operaciones[$key] as $keyOpera => $operacion) {
                                if($operacion['value'] == true) {
                                    $countOperacionesXmodulo = $countOperacionesXmodulo - 1;
                                }
                            }

                            if($countOperacionesXmodulo > 0) {
                                $modulos[] = array('name' => $key, 'value' => false);
                            } else {
                                $modulos[] = array('name' => $key, 'value' => true);
                            }
                        }

                    }else{

                        $countOperacionesXmodulo = count($operaciones[$key]);

                        foreach($operaciones[$key] as $keyOpera => $operacion) {
                            if($operacion['value'] == true) {
                                $countOperacionesXmodulo = $countOperacionesXmodulo - 1;
                            }
                        }

                        if($countOperacionesXmodulo > 0) {
                            $modulos[] = array('name' => $key, 'value' => false);
                        } else {
                            $modulos[] = array('name' => $key, 'value' => true);
                        }
                    }                    

                } else {
                    $modulos[] = array('name' => $key, 'value' => false);
                }


            }

            $data = array('modulos' => $modulos, 'operaciones' => $operaciones);

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

    public function actionIndexOne($request)
    {
        // if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            $data = [
                'idRolOperacion' => $model['idRolOperacion'],
                'nombreRolOperacion' => $model['nombreRolOperacion'],
                'aliasRolOperacion' => $model['aliasRolOperacion'],
                'moduloRolOperacion' => $model['moduloRolOperacion'],
                'idRolModuloOperacion' => $model['idRolModuloOperacion'],
            ];

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        // } else {
        //     return array('status' => false, 'data' => array(Yii::t('app','accessDenied')[0]));
        // }
    }

    /**
     * Creates a new RolesOperaciones model.
     * @return mixed
     */
    public function actionCreate()
    {
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
                
                $errors = [];
                unset($request['idRolOperacion']);

                $modelRolesOperaciones = new RolesOperaciones();
                $modelRolesOperaciones->attributes = $request;
                $modelRolesOperaciones->creacionRolOperacion = date("Y-m-d");

                if($modelRolesOperaciones->save())
                {

                    $rolesOperaciones = RolesOperaciones::findOne(['idRolOperacion' => $modelRolesOperaciones->idRolOperacion]);
                    
                    $dataOperaciones = 'Id Operación: '.$rolesOperaciones->idRolOperacion;
                    $dataOperaciones .= ', Nombre Operación: '.$rolesOperaciones->aliasRolOperacion;
                    $dataOperaciones .= ', Módulo Afectado: '.$rolesOperaciones->moduloRolOperacion;
                    $dataOperaciones .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$rolesOperaciones->estadoRolOperacion];
                    $dataOperaciones .= ', Fecha creación: '.date("Y-m-d H:i:s", strtotime($rolesOperaciones->creacionRolOperacion));
                    
                     /***    Log de Auditoria  ***/
                     HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", tabla rolesOperaciones", //texto para almacenar en el evento
                        '',
                        $dataOperaciones, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => $modelRolesOperaciones,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $modelRolesOperaciones->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Updates an existing RolesOperaciones model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate()
    {
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $errors = [];
                $id = $request['id'];

                unset($request['idRolOperacion']);
                $modelRolesOperaciones = $this->findModel($id);

                // Valores anteriores del modelo se utiliza para el log                
                $modelRolesOperacionesOld = 'Id Operación: '.$modelRolesOperaciones->idRolOperacion;
                $modelRolesOperacionesOld .= ', Nombre Operación: '.$modelRolesOperaciones->aliasRolOperacion;
                $modelRolesOperacionesOld .= ', Módulo Afectado: '.$modelRolesOperaciones->moduloRolOperacion;
                $modelRolesOperacionesOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelRolesOperaciones->estadoRolOperacion];

                $modelRolesOperaciones->attributes = $request;

                if($modelRolesOperaciones->save())
                {

                    $rolesOperaciones = RolesOperaciones::findOne(['idRolOperacion' => $modelRolesOperaciones->idRolOperacion]);
                    
                    $dataOperaciones = 'Id Operación: '.$rolesOperaciones->idRolOperacion;
                    $dataOperaciones .= ', Nombre Operación: '.$rolesOperaciones->aliasRolOperacion;
                    $dataOperaciones .= ', Módulo Afectado: '.$rolesOperaciones->moduloRolOperacion;
                    $dataOperaciones .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$rolesOperaciones->estadoRolOperacion];
                    $dataOperaciones .= ', Fecha actualización: '.date("Y-m-d H:i:s", strtotime($rolesOperaciones->creacionRolOperacion));

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla rolesOperaciones", //texto para almacenar en el evento
                        $modelRolesOperacionesOld, //DataOld
                        $dataOperaciones, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => $modelRolesOperaciones,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $modelRolesOperaciones->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionChangeStatus()
    {
        //if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $errors = [];
                $id = $request['id'];
                $model = $this->findModel($id);
                
                if($model->estadoRolOperacion == yii::$app->params['statusTodoText']['Activo'])
                {
                    $model->estadoRolOperacion = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->estadoRolOperacion = yii::$app->params['statusTodoText']['Activo'];
                }

                if($model->save())
                {

                    $rolesOperaciones = RolesOperaciones::findOne(['idRolOperacion' => $model->idRolOperacion]);
                    $dataOperaciones = 'Se cambia el estado a: '.Yii::$app->params['statusTodoNumber'][$rolesOperaciones->estadoRolOperacion];

                        /***  log Auditoria ***/
                        $helper = HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$rolesOperaciones->estadoRolOperacion],// texto para almacenar en el evento
                            '',
                            $dataOperaciones, //Data
                            array() //No validar estos campos
                        );
                        /***   Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => $model->aliasRolOperacion .", ". Yii::t('app','successChangeStatus'),
                        'status' => 200,
                        'changeStatus' => Yii::t('app','statusTodoNumber')[$model->estadoRolOperacion]
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        
        // } else {
        //     return array('status' => false, 'data' => array(Yii::t('app','accessDenied')[0]));
        // }
    }

    /**
     * Finds the RolesOperaciones model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RolesOperaciones the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RolesOperaciones::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
