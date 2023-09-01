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

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperUserMenu;

use common\models\User;
use api\models\Roles;
use api\models\RolesTiposOperaciones;
use api\models\RolesTipoDocumental;
use api\models\RolesTipoRadicado;
use api\models\RolesNivelesBusqueda;
use api\components\HelperDynamicForms;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

/**
 * RolesController implements the CRUD actions for Roles model.
 */
class RolesController extends Controller
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
                    'create-rol-tipo-radicado'  => ['PUT'],
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
     * Lists all Roles models.
     * @return mixed
     */
    public function actionIndex($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            //El request obtiene 'filterOperation' => [["nombreRol"=>"Administrador del Sistema", "creacionRol"=>""]]
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
            
            //Lista de roles
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

            // Consulta para relacionar la informacion de roles y obtener 100 registros, a partir del filtro
            $roles = Roles::find();

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){
                $roles->andWhere([Yii::$app->params['like'], $field , $value]);
            }                
            //Limite de la consulta
            $roles->orderBy(['idRol' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $roles->limit($limitRecords);
            $modelRoles = $roles->all();   

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRoles = array_reverse($modelRoles);

            foreach ($modelRoles as $rol) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($rol->idRol)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($rol->nombreRol))
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $rol->idRol,
                    'nombreRol' => $rol->nombreRol,
                    'creacionRol' => $rol->creacionRol,
                    'statusText' => Yii::$app->params['statusTodoNumber'][$rol->estadoRol],
                    'status' => $rol->estadoRol,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::createDataForForm('indexRol');

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
                'idRol' => $model['idRol'],
                'nombreRol' => $model['nombreRol'],
                'estadoRol' => $model['estadoRol'],
                'idRolNivelBusqueda' => $model['idRolNivelBusqueda'],
                'creacionRol' => $model['creacionRol'],
            ];

            if (isset($request['isView']) && $request['isView'] == true){                

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    'version1/roles/roles/view', //Modulo
                    Yii::$app->params['eventosLogText']['View']. ' Id: '.$id.' del rol: ' .$model->nombreRol, //texto para almacenar en el evento, //texto para almacenar en el evento
                    [],
                    [$model], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

            }

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

    public function actionIndexList()
    {
        $dataList = [];
        $roles = Roles::find()->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])->orderBy(['nombreRol' => SORT_ASC])->all();

        foreach ($roles as $key => $rol) {
            $dataList[] = array(
                "id" => $rol->idRol,
                "val" => $rol->nombreRol,
            );
        }
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
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
            $data[] = array('alias' => 'Nombre', 'value' => $model->nombreRol);

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'], //texto para almacenar en el evento
                [],
                [$model], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

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

    public function actionViewOperaciones($request)
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

            $operaciones = [];
            $modulosFilter = [];
            $modulos = [];

            $findRoles = $this->findModel($id);
            $findRolesTiposOperaciones = RolesTiposOperaciones::find()->where(['idRol' => $findRoles->idRol])->all();
            foreach ($findRolesTiposOperaciones as $key => $rolesTipoOperacion) {
                $modulosFilter[$rolesTipoOperacion->rolOperacion->moduloRolOperacion] = $rolesTipoOperacion->rolOperacion->moduloRolOperacion;
                $operaciones[$rolesTipoOperacion->rolOperacion->moduloRolOperacion][] = $rolesTipoOperacion->rolOperacion->aliasRolOperacion;
            }

            foreach ($modulosFilter as $key => $moduloFilter) {
                $modulos[] = $key;
            }

            $data = array('modulos' => $modulos, 'operaciones' => $operaciones);

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
     * Creates a new Roles model.
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

                $dataRoles = '';
                $nameRolOperacion = []; 
                $idRolTiposOperacion = [];
                $saveDataValid = true;
                $errors = [];

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idRol']);
                $modelRoles = new Roles();
                $modelRoles->attributes = $request;
                $modelRoles->creacionRol = date("Y-m-d H:i:s");

                if ($modelRoles->save()) {

                    foreach ($request['operacionesRol'] as $key => $operacion) {

                        $request['operacionesRol'] = array_unique($request['operacionesRol']);
                        $modelRolesTiposOperaciones = new RolesTiposOperaciones();
                        $modelRolesTiposOperaciones->idRol = $modelRoles->idRol;
                        $modelRolesTiposOperaciones->idRolOperacion = $operacion;

                        if (!$modelRolesTiposOperaciones->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTiposOperaciones->getErrors());
                            break;
                        } 
                    
                        # Construcción de array para crear string de datos actuales
                        $nameRolOperacion[] = $modelRolesTiposOperaciones->rolOperacion->aliasRolOperacion;
                        $idRolTiposOperacion[] = $modelRolesTiposOperaciones->idRolOperacion;
                    }

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRoles->getErrors());
                }
                
                if ($saveDataValid) {                   

                    $transaction->commit();

                    # Información de la data actual en string
                    $dataRoles = self::dataRol($request, $idRolTiposOperacion, $nameRolOperacion);

                    HelperLog::logAdd(
                        true, //tipo en string
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", tabla roles", //texto para almacenar en el evento
                        '',
                        $dataRoles, //Data en string  --$dataPermisos
                        array() //No validar estos campos
                    );

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelRoles,
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
     * Updates an existing Roles model.
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
                
                $saveDataValid = true;
                $errors = [];
                $nameRolOperacion = [];                
                $idRolTiposOperacion = [];
                $id = $request['id'];

                // Variable donde se guarda el menu
                $dataMenu = [];

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idRol']);
                unset($request['creacionRol']);

                # Buscar los datos del modelo
                $modelRoles = $this->findModel($id);

                # Consulta la data anterior de rolesTipoOperaciones
                $rolTipoOperac = RolesTiposOperaciones::findAll(['idRol' => $id]);

                if(!is_null($rolTipoOperac)){

                    $idRolTiposOperaciones = [];
                    $nameTipoOperacion = [];
                    foreach ($rolTipoOperac as $key => $value) {
                        $nameTipoOperacion[] = $value->rolOperacion->aliasRolOperacion;   
                        $idRolTiposOperaciones[] = $value->idRolOperacion;           
                    }                    

                    if( isset($idRolTiposOperaciones) && isset($nameTipoOperacion)){
                        $dataOld = 'Id rol: '.$modelRoles->idRol; 
                        $dataOld .= ', Nombre rol: '.$modelRoles->nombreRol;
                        $dataOld .= ', Estado rol: '.yii::$app->params['statusTodoNumber'][$modelRoles->estadoRol];
                        $dataOld .= ', Nivel de búsqueda: '.$modelRoles->idRolNivelBusqueda0->nombreRolNivelBusqueda;
                        $dataOld .= ', Id tipo operación: '.implode(', ',$idRolTiposOperaciones);
                        $dataOld .= ', Nombre tipo operación: '.implode(', ', $nameTipoOperacion);
                    }  

                } else {
                    $dataOld = '';
                }
                
                // Agregar la información que llega de fronted
                $modelRoles->attributes = $request;

                if ($modelRoles->save()) {

                    $deleteTiposOperacionesRol = RolesTiposOperaciones::deleteAll(
                        [   
                            'AND', 
                            ['idRol' => (int) $modelRoles->idRol]
                        ]
                    );

                    foreach ($request['operacionesRol'] as $key => $operacion) {
                        $modelRolesTiposOperaciones = new RolesTiposOperaciones();
                        $modelRolesTiposOperaciones->idRol = $modelRoles->idRol;
                        $modelRolesTiposOperaciones->idRolOperacion = $operacion;

                        if (!$modelRolesTiposOperaciones->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTiposOperaciones->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actuales
                        $nameRolOperacion[] = $modelRolesTiposOperaciones->rolOperacion->aliasRolOperacion;
                        $idRolTiposOperacion[] = $modelRolesTiposOperaciones->idRolOperacion;
                    }

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRoles->getErrors());
                }

                if ($saveDataValid) {

                    // dataMenu Contiene la estructura del menu, las operaciones de los usuarios enviados
                    $dataMenu = HelperUserMenu::createRolesMenu($modelRoles);

                    $transaction->commit();

                    # Información de la data actual en string
                    $dataRoles = self::dataRol($request, $idRolTiposOperacion, $nameRolOperacion);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true, // Tipo de data
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla roles", //texto para almacenar en el evento
                        $dataOld, //Data old en string
                        $dataRoles, //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $modelRoles,
                        'dataMenu' => $dataMenu,
                        'idRol' => $modelRoles->idRol,
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
            $data = '';
            $transaction = Yii::$app->db->beginTransaction();
            $dataNotifi = [];

            foreach ($request as $value) {
                $dataExplode = explode('|', $value);

                $model = $this->findModel($dataExplode[0]);

                # Valida si el rol tiene usuarios activos en el sistema
                $modUser = User::findAll([ 'idRol' => $dataExplode[0], 'status' => yii::$app->params['statusTodoText']['Activo'] ]);
                # Si encuentra algun usuario agrega mensaje de error
                if( count($modUser) > 0 ){
                    $dataNotifi[] = array('type' => 'danger', 'message' => Yii::t('app', 'userRolActived', [
                    'profile' => $model->nombreRol,
                    ]) ) ;
                }else{

                    // Cambia el estado del rol
                    if ($model->estadoRol == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoRol = yii::$app->params['statusTodoText']['Inactivo'];

                    } else {
                        $model->estadoRol = yii::$app->params['statusTodoText']['Activo'];
                    }

                    if ($model->save()) {

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoRol] . ', id: '. $model->idRol.' del rol: '.$model->nombreRol,// texto para almacenar en el evento
                            [], //Data old
                            [], //data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/
                        $dataNotifi[] = array('type' => 'success', 'message' => Yii::t('app', 'successChangeStatus').' '.$model->nombreRol);
                        
                    } else {
                        $errors[] = $model->getErrors();
                        $saveDataValid = false;
                    }

                }

                $dataResponse[] = array('id' => $model->idRol, 'status' => $model->estadoRol, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoRol], 'idInitialList' => $dataExplode[1] * 1);
            }

            if ($saveDataValid) {
                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => $dataResponse,
                    'dataNotification' =>$dataNotifi,
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
     * Finds the Roles model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Roles the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Roles::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }


    /**
     * Funcion que crea y elimina registros de la tabla roles tipo documental
     **/
    public function actionCreateRolTipoDocumental()
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
                $errors = [];
                $dataOld = '';
                $data = '';                
                $tiposDocumental = [];
                $idRolTiposDocumental = [];

                $idRol = $request['idRol'];

                # Consulta la data anterior de rolesTipoRadicado
                $rolTipoDocumental = RolesTipoDocumental::findAll(['idRol' => $idRol]);
                $datosRoles = Roles::findOne(['idRol' => $idRol]);

                if(!is_null($rolTipoDocumental)){

                    $nameTipoDocumental = [];
                    $idRolTiposDocumentales = [];

                    foreach ($rolTipoDocumental as $key => $value) {

                        $nameTipoDocumental[] = $value->idGdTrdTipoDocumental0->nombreTipoDocumental;   
                        $idRolTiposDocumentales[] = $value->idGdTrdTipoDocumental;
                    }

                    if( isset($idRolTiposDocumentales) && isset($nameTipoDocumental)){
                        $dataOld = 'Id rol: '.$datosRoles->idRol;
                        $dataOld .= ', Nombre rol: '.$datosRoles->nombreRol;
                        $dataOld .= ', Nombre tipo documental: '.implode(', ', $nameTipoDocumental);      
                    } 

                } else {
                    $dataOld = '';
                }

                $transaction = Yii::$app->db->beginTransaction();

                if ($idRol > 0) {

                    $deleteRolTipoDocumental = RolesTipoDocumental::deleteAll(
                        [   
                            'AND', 
                            ['idRol' => (int) $idRol]
                        ]
                    );
                                
                    foreach ($request['idGdTrdTipoDocumental'] as $key => $tipoDoc) {                        
                        $modelRolesTipoDocumental = new RolesTipoDocumental();
                        $modelRolesTipoDocumental->idRol = $idRol;
                        $modelRolesTipoDocumental->idGdTrdTipoDocumental = $tipoDoc;

                        if (!$modelRolesTipoDocumental->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTipoDocumental->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actualizados
                        $tiposDocumental[] = $modelRolesTipoDocumental->idGdTrdTipoDocumental0->nombreTipoDocumental;
                        $idRolTiposDocumental[] = $modelRolesTipoDocumental->idGdTrdTipoDocumental;
                        $namesRol = $modelRolesTipoDocumental->idRol0->nombreRol;
                    }

                    # Información modificada 
                    $data .= 'Id rol: '.$idRol;
                    $data .= ', Nombre rol: '.$namesRol;
                    $data .= ', Nombre tipo documental: '.implode(', ', $tiposDocumental);

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRolesTipoDocumental->getErrors());
                }

                if ($saveDataValid) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true, //tipo en string
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla rolesTipoDocumental", //texto para almacenar en el evento
                        $dataOld, // Data old en string
                        $data, //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => [],
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
     * Funcion que crea y elimina registros de la tabla roles tipo radicado
     **/
    public function actionCreateRolTipoRadicado()
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
                $errors = [];
                $dataOld = '';
                $data = '';
                $tiposRadicado = [];                
                $idRolTipoRadicados = [];

                $idRol = $request['idRol'];
                $idRoles = '';
                $nameRol = '';

                # Consulta la data anterior de rolesTipoRadicado
                $rolTipoRadicado = RolesTipoRadicado::findAll(['idRol' => $idRol]);
                $datosRoles = Roles::findOne(['idRol' => $idRol]);

                if(!is_null($rolTipoRadicado)){

                    $nameCgTipoRadicado = [];
                    $idRolTiposRadicados = [];
                    foreach ($rolTipoRadicado as $key => $value) {
                        $nameCgTipoRadicado[] = $value->cgTipoRadicado->nombreCgTipoRadicado;   
                        $idRolTiposRadicados[] = $value->idCgTipoRadicado;           
                    }

                    if( isset($idRolTiposRadicados) && isset($nameCgTipoRadicado)){
                        $dataOld = 'Id rol: '.$datosRoles->idRol;
                        $dataOld .= ', Nombre del rol: '.$datosRoles->nombreRol;
                        $dataOld .= ', Id tipo radicado: '.implode(', ',$idRolTiposRadicados);
                        $dataOld .= ', Nombre tipo radicado: '.implode(', ', $nameCgTipoRadicado);                              
                    }  

                } else {
                    $dataOld = '';
                }

                $transaction = Yii::$app->db->beginTransaction();

                if ($idRol > 0) {

                    $deleteRolTipoRadicado = RolesTipoRadicado::deleteAll(
                        [   
                            'AND', 
                            ['idRol' => (int) $idRol]
                        ]
                    );
                                
                    foreach ($request['idCgTipoRadicado'] as $key => $tipoRadicado) {                        
                        $modelRolesTipoRadicado = new RolesTipoRadicado();
                        $modelRolesTipoRadicado->idRol = $idRol;
                        $modelRolesTipoRadicado->idCgTipoRadicado = $tipoRadicado;                        

                        if (!$modelRolesTipoRadicado->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTipoRadicado->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actualizados
                        $tiposRadicado[] = $modelRolesTipoRadicado->cgTipoRadicado->nombreCgTipoRadicado;
                        $idRolTipoRadicados[] = $modelRolesTipoRadicado->idCgTipoRadicado;
                        $namesRol = $modelRolesTipoRadicado->rol->nombreRol;
                    }

                    # Información modificada
                    $data = 'Id rol: '.$idRol;
                    $data .= ', Nombre rol: '.$namesRol;
                    $data .= ', Id tipo radicado: '.implode(', ',$idRolTipoRadicados);
                    $data .= ', Nombre tipo radicado: '.implode(', ', $tiposRadicado);
                    
                    

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRolesTipoRadicado->getErrors());
                }

                if ($saveDataValid) {

                    $transaction->commit();
                
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true, //tipo en string
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla rolesTipoRadicado", //texto para almacenar en el evento
                        $dataOld, //Data old en string
                        $data, //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => [],
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
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que recibe del servicio]
     * @param $idRolTiposOperacion [array] [Ids de los tipos de operación]
     * @param $nameRolOperacion [array] [Nombres de los tipos de operación]
     */
    protected function dataRol($request, $idRolTiposOperacion, $nameRolOperacion){

        #Consulta para obtener los datos del estado y fecha
        $roles = Roles::findOne(['nombreRol' => $request['nombreRol']]);
        
        if(!is_null($roles)){
            
            $roles->estadoRol == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

            $dataRoles = 'Id rol: '.$roles->idRol;
            $dataRoles .= ', Nombre rol: '.$roles->nombreRol;
            $dataRoles .= ', Estado rol: '.$estado;
            $dataRoles .= ', Nivel de búsqueda: '.$roles->idRolNivelBusqueda0->nombreRolNivelBusqueda;
            $dataRoles .= ', Fecha creación rol: '.$roles->creacionRol;
            $dataRoles .= ', Id tipo operación: '.implode(', ',$idRolTiposOperacion);                  
            $dataRoles .= ', Nombre tipo operación: '.implode(', ', $nameRolOperacion);        

            return $dataRoles;
        }        
    }


    /**
    * Funcion que consulta los niveles debusqueda para el frontend tener una lista
    */
    public function actionIndexListNivelBusqueda()
    {
        $dataList = [];
        $models = RolesNivelesBusqueda::find()->where(['estadoRolNivelBusqueda' => Yii::$app->params['statusTodoText']['Activo']])->orderBy(['nombreRolNivelBusqueda' => SORT_ASC])->all();

        foreach ($models as $key => $model) {
            $dataList[] = array(
                "id" => $model->idRolNivelBusqueda,
                "val" => $model->nombreRolNivelBusqueda,
            );
        }
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }
}
