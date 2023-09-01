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
use api\components\HelperDynamicForms;
use api\models\CgProveedoresExternos;
use common\models\User;
use api\models\UserDetalles;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;



/**
 * CgProveedoresExternosController implements the CRUD actions for CgProveedoresExternos model.
 */
class CgProveedoresExternosController extends Controller
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
     * Lists all Cg Proveedores models.
     * @return mixed
     */
    public function actionIndex($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Consulta para relacionar la informacion de la cg proveedores y sus tablas de relaciones para obtener 100 registros, a partir del filtro
            $relationProveedores =  CgProveedoresExternos::find();

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {
                    case 'nombreCgProveedorExterno':
                        $relationProveedores->andWhere([Yii::$app->params['like'], $field, $value]);
                    break;

                    case 'tokenCgProveedorExterno':
                        $relationProveedores->andWhere(['IN', $field, $value ]);
                    break;

                    case 'estadoCgProveedorExterno':
                        $relationProveedores->andWhere(['IN', $field, intval($value) ]);
                    break;

                    case 'fechaInicial':
                        $relationProveedores->andWhere(['>=', 'creacionCgProveedorExterno', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationProveedores->andWhere(['<=', 'creacionCgProveedorExterno', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    
                    default:
                        $relationProveedores->andWhere([Yii::$app->params['like'], $field, $value]);
                    break;
                }

            }

            # Limite de la consulta
            $relationProveedores->orderBy(['idCgProveedorExterno' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $relationProveedores->limit($limitRecords);
            $modelRelation = $relationProveedores->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $infoRelation) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idCgProveedorExterno))
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'                     => $dataBase64Params,
                    'id'                       => $infoRelation->idCgProveedorExterno,
                    'nombreCgProveedorExterno' => $infoRelation->nombreCgProveedorExterno,
                    'tokenCgProveedorExterno'  => $infoRelation->tokenCgProveedorExterno,
                    'emailCgProveedorExterno'  => isset($infoRelation->userCgProveedorExterno0->email) ? $infoRelation->userCgProveedorExterno0->email : '',
                    'creacion'                 => $infoRelation->creacionCgProveedorExterno,
                    'statusText'               => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoCgProveedorExterno],
                    'status'                   => $infoRelation->estadoCgProveedorExterno,
                    'rowSelect'                => false,
                    'idInitialList'            => 0
                );
            }

            $formType = HelperDynamicForms::setListadoBD('indexCgProveedoresExternos');

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


    /* Funcion que retorna la data de todos los servicios y regionales que pertenecen a un proveedor */
    public function actionIndexOne($request)
    {

        /*** Inicio desencriptación GET ***/
        //$response = ['id' => 1];
        $decrypted = HelperEncryptAes::decryptGET($request, true);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];

        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt']
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        /*** Fin desencriptación GET ***/

        /*** Consulta de modelo	***/
        $id = $request['id'];

        $model = $this->findModel($id);

        # Información del proveedor externo
        $data['idCgProveedorExterno'] = $model->idCgProveedorExterno;
        $data['nombreCgProveedorExterno'] = $model->nombreCgProveedorExterno;
        $data['tokenCgProveedorExterno'] = $model->tokenCgProveedorExterno;
        $data['email'] = $model->userCgProveedorExterno0->email;
        $data['idGdTrdDependencia'] = $model->userCgProveedorExterno0->idGdTrdDependencia;
        $data['documento'] = $model->userCgProveedorExterno0->username;
        $data['idTipoIdentificacion'] = $model->userCgProveedorExterno0->userDetalles->idTipoIdentificacion;

        /*** Fin Consulta de modelo	***/

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);


    }


    /** Displays a single Cg Proveedores Externos model.
    * @param integer $id
    * @return mixed
    * @throws NotFoundHttpException if the model cannot be found
    */
    public function actionView($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = [ 'id' => 1];
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

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].' id: '.$id.' del proveedor externo: '.$model->nombreCgProveedorExterno, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            //Retorna toda la información de los proveedores
            $data[] = array('alias' => 'Nombre', 'value' => $model->nombreCgProveedorExterno);
            $data[] = array('alias' => 'Token', 'value' => $model->tokenCgProveedorExterno);
            $data[] = array('alias' => 'Correo electrónico', 'value' => $model->userCgProveedorExterno0->email);
            $data[] = array('alias' => 'Tipo de identificación', 'value' => $model->userCgProveedorExterno0->userDetalles->tipoIdentificacion->nombreTipoIdentificacion);
            $data[] = array('alias' => 'Documento', 'value' => $model->userCgProveedorExterno0->username);
            $data[] = array('alias' => 'Dependencia', 'value' => $model->userCgProveedorExterno0->gdTrdDependencia->nombreGdTrdDependencia);
            $data[] = array('alias' => 'Código dependencia', 'value' => $model->userCgProveedorExterno0->gdTrdDependencia->codigoGdTrdDependencia);
            $data[] = array('alias' => 'Fecha creación', 'value' => $model->creacionCgProveedorExterno);


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
     * Crea un nuevo registro en el modelo Cg Proveedores externos y las relaciones en las otras tablas de proveedores Externos
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

                $saveDataValid = true;
                $errors = [];
                $data = '';

                $transaction = Yii::$app->db->beginTransaction();

                $fecha_actual = date("Y-m-d");
                $user = new User();
                $user->email = (string) $request['email'];
                $user->username = (string) $request['documento'];
                $user->idGdTrdDependencia = $request['idGdTrdDependencia'];
                $password = $user->generatePassword();
                $user->setPassword($password);
                $user->generateAuthKey();
                $user->accessToken = $user->generateAccessToken();
                $user->fechaVenceToken = date("Y-m-d",strtotime($fecha_actual."+ ".Yii::$app->params['TimeExpireToken']." days"));
                $user->status = Yii::$app->params['statusTodoText']['Activo'];
                $user->idRol = Yii::$app->params['idRoles']['Externo'];
                $user->idUserTipo = Yii::$app->params['tipoUsuario']['Externo'];

                if($user->save()) {

                    $userDetalles = new UserDetalles();
                    $userDetalles->nombreUserDetalles = $request['nombreCgProveedorExterno'];
                    $userDetalles->apellidoUserDetalles = $request['nombreCgProveedorExterno'];
                    $userDetalles->cargoUserDetalles = Yii::$app->params['proveedoresExternosCargo'];
                    $userDetalles->idTipoIdentificacion = $request['idTipoIdentificacion'];
                    $userDetalles->documento = (string) $request['documento'];
                    $userDetalles->idUser = $user->id;
                    $userDetalles->creacionUserDetalles = date('Y-m-d H:i:s');

                    if($userDetalles->save()) {

                        # Se construye los modelos para el nuevo registro
                        $modelCgProveedoresExternos = new CgProveedoresExternos();
                        $modelCgProveedoresExternos->attributes = $request;
                        $modelCgProveedoresExternos->tokenCgProveedorExterno = User::generateAccessToken();
                        $modelCgProveedoresExternos->creacionCgProveedorExterno = date("Y-m-d");
                        $modelCgProveedoresExternos->userCgCreadorProveedorExterno = Yii::$app->user->identity->id;
                        $modelCgProveedoresExternos->userCgProveedorExterno = $user->id;

                        if (!$modelCgProveedoresExternos->save()) {
                            $saveDataValid = false;
                            $errors = $modelCgProveedoresExternos->getErrors();
                        }

                    } else {
                        $errors = $userDetalles->getErrors();
                        $saveDataValid = false;
                    }

                } else {
                    $errors = $user->getErrors();
                    $saveDataValid = false;
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataProveedorExterno($modelCgProveedoresExternos);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgProveedoresExternos", //texto para almacenar en el evento
                        '', // DataOld
                        $data, //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/


                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
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
                # Fin Evaluar respuesta de datos guardados #


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
     * Updates an existing Cg Proveedores model.
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
                $saveDataValid = true;
                $errors = [];
                $data = '';
                $dataOld = '';

                $transaction = Yii::$app->db->beginTransaction();

                $proveedorExterno = CgProveedoresExternos::findOne(['idCgProveedorExterno' => $id]);

                $proveedorExterno->estadoCgProveedorExterno == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

                $dataOld = 'Id proveedor: '.$proveedorExterno->idCgProveedorExterno;
                $dataOld .= ', Nombre del proveedor externo: '.$proveedorExterno->nombreCgProveedorExterno;
                $dataOld .= ', Token creación proveedor externo: '.$proveedorExterno->tokenCgProveedorExterno;
                $dataOld .= ', Fecha creación proveedor externo: '.$proveedorExterno->creacionCgProveedorExterno;
                $dataOld .= ', Estado proveedor externo: '.$estado;


                # Agrega nuevos datos al modelo
                $modelProveedoresExternos = $this->findModel($id);
                $modelUser = User::find()->where(['id' => $modelProveedoresExternos->userCgProveedorExterno])->one();
                $modelUser->username = (string) $request['documento'];
                $modelUser->email = (string) $request['email'];
                $modelUser->idGdTrdDependencia = $request['idGdTrdDependencia'];

                if($modelUser->save()) {

                    $modelUserDetalles = UserDetalles::find()->where(['idUser' => $modelProveedoresExternos->userCgProveedorExterno])->one();
                    $modelUserDetalles->nombreUserDetalles = $request['nombreCgProveedorExterno'];
                    $modelUserDetalles->apellidoUserDetalles = $request['nombreCgProveedorExterno'];
                    $modelUserDetalles->idTipoIdentificacion = $request['idTipoIdentificacion'];
                    $modelUserDetalles->documento = (string) $request['documento'];

                    if($modelUserDetalles->save()) {

                        $modelProveedoresExternos->attributes = $request;
                        if (!$modelProveedoresExternos->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelProveedoresExternos->getErrors());
                        }

                    } else {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelUserDetalles->getErrors());
                    }

                } else {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelUser->getErrors());
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataProveedorExterno($modelProveedoresExternos);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla CgProveedoresExternos", //texto para almacenar en el evento
                        $dataOld, //data old string
                        $data, //data string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion']
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados #

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


    /* Cambio de estado del proveedor */
    public function actionChangeStatus()
    {
        $errors = [];
        $dataResponse = [];
        $dataExplode = "";
        $data = '';
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

                if ($model->estadoCgProveedorExterno == yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoCgProveedorExterno = yii::$app->params['statusTodoText']['Inactivo'];

                } else {
                    $model->estadoCgProveedorExterno = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgProveedorExterno] . ', id: '. $model->idCgProveedorExterno.' del proveedor: '.$model->nombreCgProveedorExterno,// texto para almacenar en el evento
                        [], //DataOld
                        [], //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idCgProveedorExterno, 'status' => $model->estadoCgProveedorExterno, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgProveedorExterno], 'idInitialList' => $dataExplode[1] * 1);

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
     * Finds the CgProveedoresExternos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgProveedoresExternos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgProveedoresExternos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }



    /**
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que se almacena]
     */
    protected function dataProveedorExterno($request) {

        #Consulta para obtener los datos del estado y fecha del proveedor
        $proveedorExterno = CgProveedoresExternos::findOne(['idCgProveedorExterno' => $request['idCgProveedorExterno']]);

        $proveedorExterno->estadoCgProveedorExterno == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

        $data = 'Id proveedor: '.$proveedorExterno->idCgProveedorExterno;
        $data .= ', Nombre del proveedor externo: '.$proveedorExterno->nombreCgProveedorExterno;
        $data .= ', Token creación proveedor externo: '.$proveedorExterno->tokenCgProveedorExterno;
        $data .= ', Fecha creación proveedor externo: '.$proveedorExterno->creacionCgProveedorExterno;
        $data .= ', Estado proveedor externo: '.$estado;

        return $data;
    }
}
