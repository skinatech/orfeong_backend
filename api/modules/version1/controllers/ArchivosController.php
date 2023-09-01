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

use api\components\HelperLog;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;

use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;
use api\components\HelperValidatePermits;
use yii\web\UploadedFile;


use api\models\Roles;
use api\models\RolesTiposOperaciones;

/**
 * ArchivosController implements the CRUD actions for Archivo model.
 */
class ArchivosController extends Controller
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

    public function init(){
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /**
     * Lists all Roles models.
     * @return mixed
     */
    public function actionIndex() {

        return 'OK';

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $data = array();
            $roles = Roles::find()->all();
            foreach($roles as $rol) {
                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($rol->idRol))
                );

                $data[] = array(
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
                'message' => Yii::t('app','accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionIndexOne($request)
    {
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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
                'creacionRol' => $model['creacionRol'],
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
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionIndexList() {
        $dataList = [];
        $roles = Roles::find()->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])->all();
        foreach($roles as $key => $rol)
        {
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
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
                'message' => Yii::t('app','accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionViewOperaciones($request) {

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
            foreach($findRolesTiposOperaciones as $key => $rolesTipoOperacion) {
                $modulosFilter[$rolesTipoOperacion->rolOperacion->moduloRolOperacion] = $rolesTipoOperacion->rolOperacion->moduloRolOperacion;
                $operaciones[$rolesTipoOperacion->rolOperacion->moduloRolOperacion][] = $rolesTipoOperacion->rolOperacion->aliasRolOperacion;
            }

            foreach($modulosFilter as $key => $moduloFilter) {
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
                'message' => Yii::t('app','accessDenied')[1],
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
    public function actionCreate($id) {

        // if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $saveDataValid = true;
            $errors = [];

            $jsonSend = Yii::$app->request->post('jsonSend');
            
            $fileUpload = UploadedFile::getInstanceByName('fileUpload');

            if(isset($fileUpload->error) && $fileUpload->error === 1){                        

                $uploadMaxFilesize = ini_get('upload_max_filesize');

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'uploadMaxFilesize', [
                        'uploadMaxFilesize' => $uploadMaxFilesize
                    ])]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);

            }

            return array('status' => true, 'data' => array('Ok llega backend'), 'pueba'=>$fileUpload , 'id'=> $id );

            return $jsonSend;

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

                unset($request['idRol']);
                $modelRoles = new Roles();
                $modelRoles->attributes = $request;
                $modelRoles->creacionRol = date("Y-m-d");

                if($modelRoles->save()) {

                    /***  log Auditoria ***/                             
                    if(!Yii::$app->user->isGuest){
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['New'],// texto para almacenar en el evento
                            [],
                            [$modelRoles], //Data
                            array() //No validar estos campos
                        );
                    } 
                    /***   Fin log Auditoria   ***/

                    foreach($request['operacionesRol'] as $key => $operacion) {

                        $request['operacionesRol'] = array_unique($request['operacionesRol']);
                        $modelRolesTiposOperaciones = new RolesTiposOperaciones();
                        $modelRolesTiposOperaciones->idRol = $modelRoles->idRol;
                        $modelRolesTiposOperaciones->idRolOperacion = $operacion;


                        if(!$modelRolesTiposOperaciones->save())
                        {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTiposOperaciones->getErrors());
                            break;
                        }


                        /***  log Auditoria ***/    
                        if(!Yii::$app->user->isGuest) {
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['New'],// texto para almacenar en el evento
                                [],
                                [$modelRolesTiposOperaciones], //Data
                                array() //No validar estos campos
                            );
                        } 
                        /***   Fin log Auditoria   ***/

                    }

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRoles->getErrors());
                }

                if($saveDataValid) {
                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => $modelRoles,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
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

        /* } else {
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
     * Updates an existing Roles model.
     * If update is successful, the browser will be redirected to the 'view' page.
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

                $saveDataValid = true;
                $errors = [];
                $id = $request['id'];

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idRol']);
                unset($request['creacionRol']);
                $modelRoles = $this->findModel($id);
                $modelRoles->attributes = $request;

                if($modelRoles->save()) {

                    $deleteTiposOperacionesRol = RolesTiposOperaciones::deleteAll(
                        [   
                            'AND', 
                            ['idRol' => (int) $modelRoles->idRol]
                        ]
                    );

                        /***  log Auditoria ***/   
                        if(!Yii::$app->user->isGuest) {
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['Update'].' modelRoles',// texto para almacenar en el evento
                                [],
                                [$modelRoles], //Data
                                array() //No validar estos campos
                            );
                        } 
                        /***   Fin log Auditoria   ***/

                    foreach($request['operacionesRol'] as $key => $operacion) {
                        $modelRolesTiposOperaciones = new RolesTiposOperaciones();
                        $modelRolesTiposOperaciones->idRol = $modelRoles->idRol;
                        $modelRolesTiposOperaciones->idRolOperacion = $operacion;
                        if(!$modelRolesTiposOperaciones->save())
                        {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRolesTiposOperaciones->getErrors());
                            break;
                        }

                        /***  log Auditoria ***/      
                        if(!Yii::$app->user->isGuest) {
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['Update'].' modelRolesTiposOperaciones',// texto para almacenar en el evento
                                [],
                                [$modelRolesTiposOperaciones], //Data
                                array() //No validar estos campos
                            );
                        } 
                        /***   Fin log Auditoria   ***/
                    }

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelRoles->getErrors());
                }

                if($saveDataValid) {
                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => $modelRoles,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
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

    public function actionChangeStatus() {
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
                if($model->estadoRol == yii::$app->params['statusTodoText']['Activo'])
                {
                    $model->estadoRol = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->estadoRol = yii::$app->params['statusTodoText']['Activo'];
                }

                if($model->save()) {
                    $dataResponse[] = array('id' => $model->idRol, 'status' => $model->estadoRol, 'statusText' => Yii::t('app','statusTodoNumber')[$model->estadoRol], 'idInitialList' => $dataExplode[1] * 1);
                    
                    /***  log Auditoria ***/       
                    if(!Yii::$app->user->isGuest) {
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['Update'],// texto para almacenar en el evento
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
                    'message' => Yii::t('app','successChangeStatus'),
                    'data' => $dataResponse,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $errs,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','emptyJsonSend'),
                'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
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
}
