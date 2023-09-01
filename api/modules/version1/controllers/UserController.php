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
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperUserMenu;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperQueryDb;
use api\components\HelperFiles;

use api\modules\version1\controllers\correo\CorreoController;

use common\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\filters\auth\CompositeAuth;

use api\models\UserDetalles;
use api\models\UserTipo;
use api\models\Roles;
use api\models\TiposIdentificacion;
use api\models\GdTrdDependencias;
use api\models\RadiRadicados;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;


use api\components\HelperGenerateExcel;
use api\components\HelperLoads;
use api\models\CgGeneral;

/**
 * UserController implements the CRUD actions for User model.
 */
class UserController extends Controller
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
                    'index-list-by-depe'  => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'update'  => ['POST'],
                    'change-status'  => ['PUT'],
                    'load-massive-file'  => ['POST'],
                    'comercial'  => ['POST'],
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
     * Lists all User models.
     * @return mixed
     */
    public function actionIndex($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // El $request obtiene 'filterOperation' => [["nombreUserDetalles"=>"", "apellidoUserDetalles"=>"", "cargoUserDetalles"=>"Desarrollador"]]
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

            // Consulta para relacionar la informacion del usuario y obtener 100 registros, a partir del filtro
            $relationUser =  User::find();
                // ->leftJoin('userDetalles', '`user`.`id` = `userDetalles`.`idUser`')
                $relationUser = HelperQueryDb::getQuery('leftJoin', $relationUser, 'userDetalles', ['user' => 'id', 'userDetalles' => 'idUser']);
                $relationUser = $relationUser->where(["userDetalles.estadoUserDetalles" => Yii::$app->params['statusTodoText']['Activo']]);

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){
                switch ($field) {
                    case 'fechaInicial': 
                        $relationUser->andWhere(['>=', 'user.created_at',  strtotime($value . Yii::$app->params['timeStart'])]);
                    break;
                    case 'fechaFinal': 
                        $relationUser->andWhere(['<=', 'user.created_at', strtotime($value . Yii::$app->params['timeEnd'])]);
                    break;
                    case 'status':                        
                        $relationUser->andWhere(['IN', 'user.' . $field, intval($value) ]);                     
                    break;
                    case 'ldap': 
                    case 'idGdTrdDependencia': 
                    case 'idRol': 
                    case 'idUserTipo': 
                        $relationUser->andWhere(['IN', 'user.'.$field , $value]);
                    break;
                    case 'email': 
                        $relationUser->andWhere([Yii::$app->params['like'], 'user.'.$field , $value]);
                    break;
                    default:
                        $relationUser->andWhere([Yii::$app->params['like'], 'userDetalles.' . $field, $value]);
                    break;
                }              
            }
            //Limite de la consulta
            $relationUser->orderBy(['user.id' => SORT_DESC]);
            $relationUser->limit($limitRecords);
            $modelRelation = $relationUser->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $infoRelation) {

                $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $infoRelation->idGdTrdDependencia]);

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->id))
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $infoRelation->id,
                    'nombreUserDetalles' => $infoRelation->userDetalles->nombreUserDetalles,
                    'apellidoUserDetalles' => $infoRelation->userDetalles->apellidoUserDetalles,
                    'documento' => $infoRelation->userDetalles->documento,
                    'cargoUserDetalles' => $infoRelation->userDetalles->cargoUserDetalles,
                    'email' => $infoRelation->email,
                    'idUserTipo' => $infoRelation->userTipo->idUserTipo,
                    'nombreUserTipo' => $infoRelation->userTipo->nombreUserTipo,
                    'dependencia' => $modelDependencia->codigoGdTrdDependencia.' - '.$modelDependencia->nombreGdTrdDependencia,
                    'rol' => $infoRelation->rol->nombreRol,
                    'statusText' => Yii::t('app', 'statusTodoNumber')[$infoRelation->status],
                    'status' => $infoRelation->status,
                    'rowSelect' => false,
                    'idInitialList' => 0,
                );
            }

            // Validar que el formulario exista
            $formType = HelperDynamicForms::setListadoBD('indexUser');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200
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
        $userDetalles = UserDetalles::findOne(['idUser' => $id]);

        if(isset($userDetalles->firma)){
            $firma = str_replace(Yii::getAlias('@webroot')."/".Yii::$app->params['CarpFirmasUsuarios'],"",$userDetalles->firma);
        }else{
            $firma = '';
        }

        if ($model) {
            $data = array(
                'idRol' => $model->idRol,
                'idUserTipo' => $model->idUserTipo,
                'email' => $model->email,
                'nombreUserDetalles' => $userDetalles->nombreUserDetalles,
                'apellidoUserDetalles' => $userDetalles->apellidoUserDetalles,
                'cargoUserDetalles' => $userDetalles->cargoUserDetalles,
                'documento' => $userDetalles->documento,
                'fileUpload' => $firma,
                'idTipoIdentificacion' => $userDetalles->idTipoIdentificacion,
                'ldap' => $model->ldap,
                'idGdTrdDependencia' => $model->idGdTrdDependencia,
                'username' => $model->username,
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        $return = HelperEncryptAes::encrypt($response, true);

        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
        if (isset($userDetalles->firma) && file_exists($userDetalles->firma)) {
            $dataFile = base64_encode(file_get_contents($userDetalles->firma));
            $return['datafile'] = $dataFile;
        }
        return $return;
    }

    /**
     * Displays a single User model.
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
            $userDetalles = UserDetalles::findOne(['idUser' => $id]);
            $depedencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $model->idGdTrdDependencia]);
            ($model->ldap == 0) ? $ldap = 'No' : $ldap = 'Si';

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].' Id: '.$id.' del usuario: '.$userDetalles->nombreUserDetalles.' '.$userDetalles->apellidoUserDetalles, //texto para almacenar en el evento
                [],
                [], //Data
                array() //No validar estos campos
            );

            $data[] = array('alias' => 'Tipo de usuario', 'value' => $model->userTipo->nombreUserTipo);
            $data[] = array('alias' => 'Perfil', 'value' => $model->rol->nombreRol);
            $data[] = array('alias' => 'Nombres', 'value' => $userDetalles->nombreUserDetalles);
            $data[] = array('alias' => 'Apellidos', 'value' => $userDetalles->apellidoUserDetalles);
            $data[] = array('alias' => 'Tipo de identificación', 'value' => $userDetalles->tipoIdentificacion->nombreTipoIdentificacion);
            $data[] = array('alias' => 'Documento', 'value' => $userDetalles->documento);
            $data[] = array('alias' => 'Correo electrónico', 'value' => $model->email);
            $data[] = array('alias' => 'Autenticación por LDAP', 'value' => $ldap);
            $data[] = array('alias' => 'Dependencia', 'value' => $depedencia->codigoGdTrdDependencia.' - '.$depedencia->nombreGdTrdDependencia);
            $data[] = array('alias' => 'Cargo', 'value' => $userDetalles->cargoUserDetalles);
            $data[] = array('alias' => 'Firma mecánica', 'value' => ($userDetalles->firma == null || trim($userDetalles->firma) == '') ? 'No' : 'Si');

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

    public function reloadIndex() {

        $relationUser = User::find();
            // ->leftJoin('userDetalles', '`user`.`id` = `userDetalles`.`idUser`')
            $relationUser = HelperQueryDb::getQuery('leftJoin', $relationUser, 'userDetalles', ['user' => 'id', 'userDetalles' => 'idUser']);
            $relationUser = $relationUser->where(["userDetalles.estadoUserDetalles" => Yii::$app->params['statusTodoText']['Activo']]);

        //Limite de la consulta
        $relationUser->orderBy(['id' => SORT_ASC]);
        $relationUser->limit(Yii::$app->params['limitRecords']);
        $modelRelation = $relationUser->all();

        foreach($modelRelation as $infoRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->id))
            );

            $dataList[] = array(
                'data' => $dataBase64Params,
                'id' => $infoRelation->id,
                'nombreUserDetalles' => $infoRelation->userDetalles->nombreUserDetalles,
                'apellidoUserDetalles' => $infoRelation->userDetalles->apellidoUserDetalles,
                'cargoUserDetalles' => $infoRelation->userDetalles->cargoUserDetalles,
                'email' => $infoRelation->email,
                'idUserTipo' => $infoRelation->userTipo->idUserTipo,
                'nombreUserTipo' => $infoRelation->userTipo->nombreUserTipo,
                'statusText' => Yii::t('app', 'statusTodoNumber')[$infoRelation->status],
                'status' => $infoRelation->status,
                'rowSelect' => false,
                'idInitialList' => 0
            );
        }

        return $dataList;
    }

    /**
     * Creates a new User model.
     * @return mixed
     */
    public function actionCreate($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $decrypted = HelperEncryptAes::decryptGET($request, true);

            if (!empty($decrypted)) {

                //*** Inicio desencriptación POST ***//
                // $decrypted = HelperEncryptAes::decrypt($jsonSend, true);

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

                //*** Fin desencriptación POST ***//
                $saveDataValid = true;
                $user = new User();
                $userDetalles = new UserDetalles();
                $fecha_actual = date("Y-m-d");

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['id']);
                $user->attributes = $request;
                $user->idGdTrdDependencia = $request['idGdTrdDependencia'];
                // $password = $user->generatePassword();
                $password = $request['password'];
                $user->setPassword($password);
                $user->generateAuthKey();
                $user->accessToken = $user->generateAccessToken();
                $user->fechaVenceToken = date("Y-m-d",strtotime($fecha_actual."+ ".Yii::$app->params['TimeExpireToken']." days"));
                $user->status = Yii::$app->params['statusTodoText']['Activo'];

                if ($user->save()) {

                    $userDetalles->attributes = $request;
                    $userDetalles->documento = (string) $request['documento'];
                    $userDetalles->idUser = $user->id;
                    $userDetalles->creacionUserDetalles = date('Y-m-d H:i:s');

                    // Firma digital del usuario
                    /** Validar si cargo un archivo subido **/
                    $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

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

                    $firmaImagen = false;
                    if(isset($fileUpload)){

                        /**Valida el tamaño del archivo establecido en orfeo */
                        $resultado = HelperFiles::validateCgTamanoArchivo($fileUpload);

                        if(!$resultado['ok']){
                            $orfeoMaxFileSize = $resultado['data']['orfeoMaxFileSize'];

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'orfeoMaxFileSize', [
                                    'orfeoMaxFileSize' => $orfeoMaxFileSize
                                    ])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                        return HelperEncryptAes::encrypt($response, true);
                         }
                        /** Fin de validación */

                        $firmaImagen = true;

                        /* validar tipo de archivo */
                        $validationExtension = ['png'];
                        if (!in_array($fileUpload->extension, $validationExtension)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                                'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /* Fin validar tipo de archivo */
  
                        $pathUploadFile = Yii::getAlias('@webroot')."/".Yii::$app->params['CarpFirmasUsuarios'];

                        # Verificar que la carpeta exista y crearla en caso de no exista
                        if (!file_exists($pathUploadFile)) {
                            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                    //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        # Fin Verificar que la carpeta exista y crearla en caso de no exista

                         // validar signo '/' en el nombre para no crear nuevos directorios
                         $nombreArchivo = str_replace("/", "",  $userDetalles->documento);
                         $rutaFirma = $pathUploadFile.$nombreArchivo.'.'.$fileUpload->extension;      
 
                         /*** Validar si ya existe la plantilla  ***/
                         if(file_exists($rutaFirma)) {
                             Yii::$app->response->statusCode = 200;
                             $response = [
                                 'message' => Yii::t('app','errorValidacion'),
                                 'data' => ['error' => [Yii::t('app','fileExistsFirm')]],
                                 //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                 'status' => Yii::$app->params['statusErrorValidacion'],
                             ];
                             return HelperEncryptAes::encrypt($response, true);
                         }
                         /*** Fin Validar si ya existe la plantilla  ***/
 
                         # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                         $uploadExecute = $fileUpload->saveAs($rutaFirma);
                         # guardar campo
                         $userDetalles->firma = $rutaFirma;

                    }

                    if (!$userDetalles->save()) {
                        $saveDataValid = false;
                    }

                } else {
                    $saveDataValid = false;
                }

                if ($saveDataValid) {

                    $ususario = User::findOne(['id' => $user->id]);
                    $rol = Roles::findOne(['idRol' => $ususario->idRol]);
                    $ususarioDetalle = UserDetalles::findOne(['idUser' => $user->id]);
                    $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $ususario->idGdTrdDependencia]);
                    ($ususario->ldap) == 0 ? $autenticacion = 'Base de datos' : $autenticacion ='Directorio activo (LDAP)';
                    
                    $dataUsuarios = 'Id Usuario: '.$user->id;
                    $dataUsuarios .= ', Usuario: '.$ususario->username;
                    $dataUsuarios .= ', Nombre Usuario: '.$ususarioDetalle->nombreUserDetalles.' '.$ususarioDetalle->apellidoUserDetalles;
                    $dataUsuarios .= ', Id Tipo identificación: '.$ususarioDetalle->idTipoIdentificacion;
                    $dataUsuarios .= ', Tipo Identificación: '.$ususarioDetalle->tipoIdentificacion->nombreTipoIdentificacion;
                    $dataUsuarios .= ', Número de documento: '.$ususarioDetalle->documento;
                    $dataUsuarios .= ', Dependencia asignada: '.$dependencia->codigoGdTrdDependencia.' - '.$dependencia->nombreGdTrdDependencia;
                    $dataUsuarios .= ', Cargo: '.$ususarioDetalle->cargoUserDetalles;
                    $dataUsuarios .= ', Correo Electrónico: '.$ususario->email;
                    $dataUsuarios .= ', Id Rol: '.$ususario->idRol;
                    $dataUsuarios .= ', Nombre Rol: '.$rol->nombreRol;
                    $dataUsuarios .= ', Id Tipo de Usuario: '.$ususario->idUserTipo;
                    $dataUsuarios .= ', Nombre Tipo Usuario: '.Yii::$app->params['tipoUsuarioText'][$ususario->idUserTipo];
                    $dataUsuarios .= ', Método de Autenticación: '.$autenticacion;
                    $dataUsuarios .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$ususario->status];
                    $dataUsuarios .= ', Fecha creación: '.date("Y-m-d H:i:s", $ususario->created_at);

                    $asunto = Yii::$app->params['eventosLogText']['crear'] . ", tabla user";

                    if($firmaImagen){
                        $asunto .= ', se subió imagen de firma';
                    }

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        $asunto, //texto para almacenar en el evento
                        '',
                        $dataUsuarios, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $transaction->commit();
                    $headMailText = Yii::t('app', 'headMailTextRegistro');
                    $textBody = Yii::t('app', 'textBodyRegistro').$user->username;
                    $envioCorreo = CorreoController::registro($user->email, $headMailText, $textBody);

                    if ($envioCorreo['status'] == true) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successSave') . ", " . Yii::t('app', 'successMail'),
                            'status' => 200,
                            'dataListUser' => $this->reloadIndex(),
                            'dataModule' => 'Users',
                        ];

                        return HelperEncryptAes::encrypt($response, true);

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successSave') . ", " . Yii::t('app', 'failedMail'),
                            'status' => 200,
                            'dataListUser' => $this->reloadIndex(),
                            'dataModule' => 'Users',
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => array_merge($user->getErrors(), $userDetalles->getErrors()),
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
     * Updates an existing User model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $decrypted = HelperEncryptAes::decryptGET($request, true);

            if (!empty($decrypted)) {

                //*** Inicio desencriptación POST ***//
                // $decrypted = HelperEncryptAes::decrypt($jsonSend, true);

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
                $id = $request['id'];

                // Variable donde se guarda el menu
                $dataMenu = [];

                $user = $this->findModel($id);

                if ($user->idGdTrdDependencia != $request['idGdTrdDependencia']) {

                    # Se valida que el usuario no tenga radicados creados.
                    $countRadicados = (int) RadiRadicados::find()
                        ->where('"user_idTramitador" ='.$user->id.' and "estadoRadiRadicado" <>'.Yii::$app->params['statusTodoText']['Inactivo'])
                        ->count();     
                        
                    if ($countRadicados > 0) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'activeFiles')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                }

                $userDetalles = UserDetalles::findOne(['idUser' => $user['id']]);                
                $rol = Roles::findOne(['idRol' => $user->idRol]);
                $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $user->idGdTrdDependencia]);
                ($user->ldap) == 0 ? $autenticacion = 'Base de datos' : $autenticacion ='Directorio activo (LDAP)';
                    
                $dataUsuariosOld = 'Id Usuario: '.$user->id;
                $dataUsuariosOld .= ', Usuario: '.$user->username;
                $dataUsuariosOld .= ', Nombre usuario: '.$userDetalles->nombreUserDetalles.' '.$userDetalles->apellidoUserDetalles;
                $dataUsuariosOld .= ', Id tipo identificación: '.$userDetalles->idTipoIdentificacion;
                $dataUsuariosOld .= ', Tipo identificación: '.$userDetalles->tipoIdentificacion->nombreTipoIdentificacion;
                $dataUsuariosOld .= ', Número de documento: '.$userDetalles->documento;
                $dataUsuariosOld .= ', Dependencia asignada: '.$dependencia->codigoGdTrdDependencia.' - '.$dependencia->nombreGdTrdDependencia;
                $dataUsuariosOld .= ', Cargo: '.$userDetalles->cargoUserDetalles;
                $dataUsuariosOld .= ', Correo electrónico: '.$user->email;
                $dataUsuariosOld .= ', Id rol: '.$user->idRol;
                $dataUsuariosOld .= ', Nombre rol: '.$rol->nombreRol;
                $dataUsuariosOld .= ', Id tipo de Usuario: '.$user->idUserTipo;
                $dataUsuariosOld .= ', Nombre tipo usuario: '.Yii::$app->params['tipoUsuarioText'][$user->idUserTipo];
                $dataUsuariosOld .= ', Método de autenticación: '.$autenticacion;
                $dataUsuariosOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$user->status];

                $transaction = Yii::$app->db->beginTransaction();
                $user->idGdTrdDependencia = $request['idGdTrdDependencia'];

                //Para el caso de aeronautica el username es el docuemnto
                if ($userDetalles['documento'] == $request['documento']) {
                    $user->attributes = $request;
                    $password = $request['password'];
                    $user->setPassword($password);
                    $user->accessToken = $user->generateAccessToken();
                    $user->intentos = 0;
                    $user->removePasswordResetToken();
                    $userDetalles->attributes = $request;
                } else {
                    $user->attributes = $request;
                    // $user->username = (string) $request['documento']; //** */
                }

                $userDetalles->attributes = $request;
                $userDetalles->documento = (string) $request['documento'];

                    // Firma digital del usuario
                    /** Validar si cargo un archivo subido **/
                    $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

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
                    
                    $firmaImagen = false;
                    if(isset($fileUpload)){

                        /**Valida el tamaño del archivo establecido en orfeo */
                        $resultado = HelperFiles::validateCgTamanoArchivo($fileUpload);

                        if(!$resultado['ok']){
                            $orfeoMaxFileSize = $resultado['data']['orfeoMaxFileSize'];

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'orfeoMaxFileSize', [
                                    'orfeoMaxFileSize' => $orfeoMaxFileSize
                                    ])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                        return HelperEncryptAes::encrypt($response, true);
                        }
                        /** Fin de validación */

                        $firmaImagen = true;

                        /* validar tipo de archivo */
                        $validationExtension = ['png'];
                        if (!in_array($fileUpload->extension, $validationExtension)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                                'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /* Fin validar tipo de archivo */
  
                        $pathUploadFile = Yii::getAlias('@webroot')."/".Yii::$app->params['CarpFirmasUsuarios'];

                        # Verificar que la carpeta exista y crearla en caso de no exista
                        if (!file_exists($pathUploadFile)) {
                            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                    //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        # Fin Verificar que la carpeta exista y crearla en caso de no exista

                         // validar signo '/' en el nombre para no crear nuevos directorios
                         $nombreArchivo = str_replace("/", "",  $userDetalles->documento);
                         $rutaFirma = $pathUploadFile.$nombreArchivo.'.'.$fileUpload->extension;      
 
                        # Validar que la ruta en base de datos no exista
                        $rutaFirmaValida = UserDetalles::find()->where(['firma' => $rutaFirma])->andWhere(['<>','idUser', $user['id']])->one();

                        if(isset($rutaFirmaValida)){
                             Yii::$app->response->statusCode = 200;
                             $response = [
                                 'message' => Yii::t('app','errorValidacion'),
                                 'data' => ['error' => [Yii::t('app','fileExistsFirm')]],
                                 //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                 'status' => Yii::$app->params['statusErrorValidacion'],
                             ];
                             return HelperEncryptAes::encrypt($response, true);
                         }
                         /*** Fin Validar si ya existe la plantilla  ***/
 
                         # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                         $uploadExecute = $fileUpload->saveAs($rutaFirma);
                         # guardar campo
                         $userDetalles->firma = $rutaFirma;

                    }

                if (!$user->save()) {
                    $saveDataValid = false;
                }

                if (!$userDetalles->save()) {
                    $saveDataValid = false;
                }

                if ($saveDataValid) {

                    $ususario = User::findOne(['id' => $user->id]);
                    $rol = Roles::findOne(['idRol' => $ususario->idRol]);
                    $ususarioDetalle = UserDetalles::findOne(['idUser' => $user->id]);
                    $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $ususario->idGdTrdDependencia]);
                    ($ususario->ldap) == 0 ? $autenticacion = 'Base de datos' : $autenticacion ='Directorio activo (LDAP)';
                    
                    $dataUsuarios = 'Id Usuario: '.$user->id;
                    $dataUsuarios .= ', Usuario: '.$ususario->username;
                    $dataUsuarios .= ', Nombre Usuario: '.$ususarioDetalle->nombreUserDetalles.' '.$ususarioDetalle->apellidoUserDetalles;
                    $dataUsuarios .= ', Id Tipo identificación: '.$ususarioDetalle->idTipoIdentificacion;
                    $dataUsuarios .= ', Tipo Identificación: '.$ususarioDetalle->tipoIdentificacion->nombreTipoIdentificacion;
                    $dataUsuarios .= ', Número de documento: '.$ususarioDetalle->documento;
                    $dataUsuarios .= ', Dependencia asignada: '.$dependencia->codigoGdTrdDependencia.' - '.$dependencia->nombreGdTrdDependencia;
                    $dataUsuarios .= ', Cargo: '.$ususarioDetalle->cargoUserDetalles;
                    $dataUsuarios .= ', Correo Electrónico: '.$ususario->email;
                    $dataUsuarios .= ', Id Rol: '.$ususario->idRol;
                    $dataUsuarios .= ', Nombre Rol: '.$rol->nombreRol;
                    $dataUsuarios .= ', Id Tipo de Usuario: '.$ususario->idUserTipo;
                    $dataUsuarios .= ', Nombre Tipo Usuario: '.Yii::$app->params['tipoUsuarioText'][$ususario->idUserTipo];
                    $dataUsuarios .= ', Método de Autenticación: '.$autenticacion;
                    $dataUsuarios .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$ususario->status];

                    // dataMenu Contiene la estructura del menu, las operaciones de los usuarios enviados
                    $dataMenu = HelperUserMenu::createUserMenu($user);

                    $asunto = Yii::$app->params['eventosLogText']['Edit'] . ", tabla user";

                    if($firmaImagen){
                        $asunto .= ', se subió imagen de firma';
                    }

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        $asunto, //texto para almacenar en el evento
                        $dataUsuariosOld,
                        $dataUsuarios, //Data string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    /** Recuperando los datos del usuario para actualizar el storage **/
                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($user->id))
                    );

                    $data = array(
                        'data' => $dataBase64Params,
                        'idDataCliente' => $user->id,
                        'username' => $user->username,
                        'email' => $user->email,
                        'fechaVenceToken' => $user->fechaVenceToken,
                        'idRol' => $user->idRol,
                        'idUserTipo' => $user->idUserTipo,
                        'accessToken' => $user->accessToken,
                        'ldap' => $user->ldap,
                    );

                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $data,
                        'dataMenu' => $dataMenu,
                        'dataListUser' => $this->reloadIndex(),
                        'dataModule' => 'Users',
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => array_merge($user->getErrors(), $userDetalles->getErrors()),
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
        $dataUsuarios = '';
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

                # Validación de radicados asignados al usuario
                $radicados = RadiRadicados::find()->where('"user_idTramitador" ='.$model->id.' and "estadoRadiRadicado" <>'.Yii::$app->params['statusTodoText']['Inactivo'])->one();

                # Validación del correo de PQRS de la configuracion general.
                $modelGeneral = CgGeneral::findOne(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']]);

                if ($model->status == Yii::$app->params['statusTodoText']['Activo']) {                    

                    # Si el usuario tiene radicados asignados o tiene el correo de la PQRSD, no se puede inactivar.
                    if(is_null($radicados) && $modelGeneral->correoNotificadorPqrsCgGeneral != $model->email){
                        $model->status = Yii::$app->params['statusTodoText']['Inactivo'];

                    } elseif( !is_null($radicados) && $modelGeneral->correoNotificadorPqrsCgGeneral == $model->email ){

                        $saveDataValid = false;
                        $errors[] = $model->userDetalles->nombreUserDetalles.' '.$model->userDetalles->apellidoUserDetalles.' - '.Yii::t('app', 'fileAndEmailActived');

                    } elseif(!is_null($radicados)) {    

                        $saveDataValid = false;
                        $errors[] = $model->userDetalles->nombreUserDetalles.' '.$model->userDetalles->apellidoUserDetalles.' - '.Yii::t('app', 'filingActived');
                        //$dataResponse[] = array('id' => $model->id, 'status' => $model->status, 'statusText' => Yii::t('app','statusTodoNumber')[$model->status], 'idInitialList' => $dataExplode[1] * 1);

                    } elseif($modelGeneral->correoNotificadorPqrsCgGeneral == $model->email) {  

                        $saveDataValid = false;
                        $errors[] = Yii::t('app', 'errorEmailPqrsd');
                    }

                } else {
                    $model->status = Yii::$app->params['statusTodoText']['Activo'];
                }

                if($model->save()) {

                    $nombreUsuario = $model->userDetalles->nombreUserDetalles.' '.$model->userDetalles->apellidoUserDetalles; 

                    /***  log Auditoria ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->status] .', id: '.$model->id. ' del usuario: '. $nombreUsuario,// texto para almacenar en el evento
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***   Fin log Auditoria   ***/                        

                    $dataResponse[] = array('id' => $model->id, 'status' => $model->status, 'statusText' => Yii::t('app','statusTodoNumber')[$model->status], 'idInitialList' => $dataExplode[1] * 1);

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
                    'status' => 200
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $errors,
                    'dataStatus' => $dataResponse,
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

    public function actionIndexListByDepe($request)
    {

        if (!empty($request)) {
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GETS***//
        }

        $depes = [];
        $idDependencia = $request['idDependencia'];

        if( is_array( $idDependencia ) ){
            $depes = $idDependencia;
        }else{
            $depes[] = $idDependencia;
        }

        $data = [];
        $dataOrder = [];

        $modelUser = User::find()
        ->where([ 'status' => Yii::$app->params['statusTodoText']['Activo'] ])
        ->andWhere(['IN', 'idGdTrdDependencia', $depes  ])
        ->all();

        /** Definir si el llamado del front no necesita que se incluya el usuario logueado en la respuesta (por defecto si se muestra) */
        if (isset($request['includeUserLogin']) && $request['includeUserLogin'] == false) {
            $includeUserLogin = false;
        } else {
            $includeUserLogin = true;
        }         

        foreach ($modelUser as $row) {

            // Valida que solo permita usuarios internos
            if($row->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                if ($includeUserLogin == true || ($includeUserLogin == false && $row->id != Yii::$app->user->identity->id) ) {
                    $dataOrder[] = array(
                        "val" => $row->userDetalles->nombreUserDetalles. ' ' . $row->userDetalles->apellidoUserDetalles.' - '.$row->userDetalles->cargoUserDetalles,
                        "id" => (int) $row->id,
                    );
                }
            }

        }

        // Ordena para el array
        asort($dataOrder);
        // Recorre el arrengo adecuando las posiciones de manera correcta para que el Frontend lo entienda de manera correcta
        foreach ($dataOrder as $key => $value) {
            $data[] = $value;
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'request' => $request,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionIndexListByDepeFilter($request)
    {

        if (!empty($request)) {
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GETS***//
        }

        $depes = [];
        $idDependencia = $request['id'];

        if( is_array( $idDependencia ) ){
            $depes = $idDependencia;
        }else{
            $depes[] = $idDependencia;
        }

        $data = [];

        $modelUser = User::find()
            ->where([ 'status' => Yii::$app->params['statusTodoText']['Activo'] ])
            ->andWhere(['IN', 'idGdTrdDependencia', $depes  ])
        ->all();

        foreach ($modelUser as $row) {

            # Usuarios internos
            if($row->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                $data[] = array(
                    "label" => $row->userDetalles->nombreUserDetalles. ' ' . $row->userDetalles->apellidoUserDetalles.' - '.$row->userDetalles->cargoUserDetalles,
                    "value" => (int) $row->id,
                );
            }
        }

        // Ordena el array en forma decendente esto solo aplica para filtros de Formly
        sort($data);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'request' => $request,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /** Funcion que lista todos los usuarios que tienen la misma dependencia
     * del usuario logueado, y se organiza a partir del perfil Usuario Jefe.
     * El otro listado es cuando el usuario logueado es Jefe, se obtendrá la dependencia
     * 'Unidad Administrativa' y se mostrará todos los usuarios Jefes que pertenencen
     * a esa dependencia.
     **/
    public function actionIndexListUserByDepend()
    {
        # Id de la dependencia del usuario logueado
        $idDependencia = [Yii::$app->user->identity->idGdTrdDependencia];

        # Se valida si el tipo de usuario para obtener la dependencia es usario jefe
        if (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe'] ){
            
            # Se consulta por el id de la dependencia del usuario Jefe y se obtiene el codigo de la dependencia padre
            $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);

            # Se agrega la depdenecia padre además de la de usuario            
            if(!empty($modelDependencia && isset($modelDependencia->codigoGdTrdDepePadre))) {
                $idDependencia[] = $modelDependencia->codigoGdTrdDepePadre;
            }          
        }

        # El listado solo mostrará usuarios diferentes al usuario logueado
        $tablaUser = User::tableName() . ' AS U';
        $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
        $tablaUserTipo = UserTipo::tableName() . ' AS UT';

        $modelUsers = (new \yii\db\Query())
            ->select(['UD.apellidoUserDetalles', 'UD.nombreUserDetalles', 'UD.idUser', 'U.idUserTipo', 'UT.nombreUserTipo' ])
            ->from($tablaUser);
            // ->innerJoin($tablaUserDetalles, '`u`.`id` = `ud`.`idUser`')
            $modelUsers = HelperQueryDb::getQuery('innerJoinAlias', $modelUsers, $tablaUserDetalles, ['U' => 'id', 'UD' => 'idUser']);
            // ->innerJoin($tablaUserTipo, '`u`.`idUserTipo` = `ut`.`idUserTipo`')
            $modelUsers = HelperQueryDb::getQuery('innerJoinAlias', $modelUsers, $tablaUserTipo, ['U' => 'idUserTipo', 'UT' => 'idUserTipo']);
            $modelUsers = $modelUsers->where(['U.status' => Yii::$app->params['statusTodoText']['Activo'] ])
            ->andWhere(['IN', 'U.idGdTrdDependencia', $idDependencia])
            ->andWhere(['<>', 'U.id', Yii::$app->user->identity->id])
            ->andWhere(['U.idUserTipo' => Yii::$app->params['tipoUsuario']['Usuario Jefe']])
            ->orderBy(['UD.nombreUserDetalles' => SORT_ASC, 'UD.apellidoUserDetalles' => SORT_ASC])
        ->all();

        $users = [];
        $user1 = []; //Array de usuarios tipo jefe
        $user2 = []; //Array de usuarios diferentes al tipo jefe
        foreach ($modelUsers as $key => $infoUser){

            if($infoUser['idUserTipo'] == Yii::$app->params['tipoUsuario']['Usuario Jefe']) {
                $user1[] = $infoUser;
                $users = $user1;

            } else {
                $user2[] = $infoUser;
                $users = array_merge($user1,$user2);
            }
        }

        if(count($users) == 0){

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'noBossUsers')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $data = [];
        foreach ($users as $row) {
            $data[] = array(
                "val" =>  $row['nombreUserDetalles'].' '.$row['apellidoUserDetalles'].' ('.$row['nombreUserTipo'].')',
                "id" => (int) $row['idUser'],
            );
        }


        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];

        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Finds the User model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return User the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    /**
     * Permite cargar el archivo .xlsx para la carga masiva de usuarios, se establece el directorio
     * donde se va a guardar el archivo.
     * @return UploadedFile el archivo cargado
     */
    public function actionLoadMassiveFile()
    {

        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

            /** Validar si cargo un archivo subido **/
            $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

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
            
            if ($fileUpload) {

                   /**Valida el tamaño del archivo establecido en orfeo */
                   $resultado = HelperFiles::validateCgTamanoArchivo($fileUpload);

                   if(!$resultado['ok']){
                       $orfeoMaxFileSize = $resultado['data']['orfeoMaxFileSize'];

                       Yii::$app->response->statusCode = 200;
                       $response = [
                           'message' => Yii::t('app','errorValidacion'),
                           'data' => ['error' => [Yii::t('app', 'orfeoMaxFileSize', [
                               'orfeoMaxFileSize' => $orfeoMaxFileSize
                               ])]],
                           'status' => Yii::$app->params['statusErrorValidacion'],
                       ];
                   return HelperEncryptAes::encrypt($response, true);
                   }
                   /** Fin de validación */

                /* validar tipo de archivo */
                if (!in_array($fileUpload->extension, ['xlsx','xls'])) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'mailIncorrectFormat')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /* Fin validar tipo de archivo */

                $rutaOk = true; // ruta base de la carpeta existente
                //Ruta de ubicacion de la carpeta donde se almacena el excel
                $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomCarpetaUsuarios'] . '/';

                // Verificar que la carpeta exista y crearla en caso de que no exista
                if (!file_exists($pathUploadFile)) {

                    if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                        $rutaOk = false;
                    }
                }

                /*** Validar creación de la carpeta***/
                if ($rutaOk == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /*** Fin Validar creación de la carpeta***/

                //$id = $request['id'];
                $id = Yii::$app->user->identity->id;

                # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar
                $nomArchivo = "tmp_carga_masiva_usuarios_sistema_" . $id . "." . $fileUpload->extension;
                $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo);

                /*** Validar si el archivo fue subido ***/
                if ($uploadExecute) {

                    /*** log Auditoria ***/
                    if(!Yii::$app->user->isGuest) {

                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['FileUpload'], // texto para almacenar en el evento
                            [],
                            [], //Data
                            array() //No validar estos campos
                        );
                    }
                    /***  Fin log Auditoria   ***/

                    return SELF::fileValidate($nomArchivo); //LLamado a la funcion de validacion de archivo

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'data' => ['error' => [Yii::t('app', 'canNotUpFile')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
    }

    /* Función que permite la validacion del archivo cargado */
    public function fileValidate($nomArchivo)
    {
        $rutaOk = true;
        $dataListErr = [];
        //$nomArchivo = 'tmp_carga_masiva_usuarios_sistema_'. $id .'.xlsx';

        //ruta del archivo de excel
        $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomCarpetaUsuarios']. '/' .$nomArchivo;

        /** Validar si el archivo existe */
        if (file_exists($pathUploadFile)) {

            /****** Validación de archivo ******/
            $documento = IOFactory::load($pathUploadFile);

            # Recordar que un documento puede tener múltiples hojas
            # obtener conteo e iterar
            $indiceHoja = 0;

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);
            $maxCell = $hojaActual->getHighestRowAndColumn();
            $hojaActualArray = $hojaActual->rangeToArray("A1:K". $maxCell['row']);

            // Titulos de las columnas que se mostraran en los errores
            $title = array(
                'Nombre dependencia' => 'A', 'Nombre de Usuario' => 'B', 'Apellido de Usuario' => 'C',
                'Tipo identificación' => 'D', 'Documento identidad' => 'E', 'Cargo' => 'F',
                'Correo electrónico institucional' => 'G', 'Tipo de usuario' => 'H', 'Perfil' => 'I',
                'Usuario de autenticación' => 'J', 'Autenticación por LDAP' => 'K',
            );

            $titleText = array(
                'Nombre dependencia', 'Nombre de Usuario', 'Apellido de Usuario', 'Tipo identificación', 'Documento identidad', 'Cargo',
                'Correo electrónico institucional', 'Tipo de usuario', 'Perfil', 'Usuario de autenticación', 'Autenticación por LDAP',
            );

            // Cantidad de columnas en la cabecera
            $cantCabecera = count($title);
            $estadoServicio = false;
            $entregaExp = 0;
            $contraEntrega = false;
            $nombreServicio = '';


            /***
                Carga los nombres de usuarios (username = nº documento) en un array para validar si el dato en el excel es valido
            ***/
            $dependencias = [];
            $modelDependencia = GdTrdDependencias::find()->where(['estadoGdTrdDependencia' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelDependencia as $infoDepend){
                    $dependencias[$infoDepend['nombreGdTrdDependencia']] = $infoDepend['idGdTrdDependencia'];
                }

            /***
                Carga los nombres de usuarios (username = nº documento) en un array para validar si el dato en el excel es valido
            ***/
            $users = [];
            $usernameList = [];
            $user = User::find()->where(['status' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($user as $infoUser){
                    $users[$infoUser['username']] = array(
                        'id' => $infoUser['id'],
                        'idDependencia' => $infoUser['idGdTrdDependencia'],
                    );
                }

            /***
                Carga los tipos de documento de los usuarios en un array para validar si el dato en el excel es valido
            ***/
            $tipoIdentificacion = [];
            $modelTipos = TiposIdentificacion::find()->where(['estadoTipoIdentificacion' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelTipos as $infoTipos){
                    $tipoIdentificacion[$infoTipos['nombreTipoIdentificacion']] = $infoTipos['idTipoIdentificacion'];
                }

            /***
                Carga los tipos de usuario en un array para validar si el dato en el excel es valido
            ***/
            $tipoUser = [];
            $modelTipoUser = UserTipo::find()->where(['estadoUserTipo' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelTipoUser as $infoTipoUser){
                    $tipoUser[$infoTipoUser['nombreUserTipo']] = $infoTipoUser['idUserTipo'];
                }

            /***
                Carga los tipos de usuario en un array para validar si el dato en el excel es valido
            ***/
            $roles = [];
            $modelRoles = Roles::find()->where(['estadoRol' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelRoles as $infoRol){
                    $roles[$infoRol['nombreRol']] = $infoRol['idRol'];
                }

            $fila = 1;

            //Arreglo de filas ignoradas
            $ignorarFilas = [];

            foreach ($hojaActualArray as $i => $row) {

                if($i == 0){
                    // Se recorre las columnas correspondientes al titulo del archivo
                    foreach ($row as $celda) {

                        // Si el valor de la celda leida NO esta en el arreglo de los titulos se genera error de formato
                        if(!in_array($celda, $titleText)){
                            $dataListErr['Fila ' . $fila][] = Yii::t('app','dataListErrTitle');                            
                        }
                    }

                } elseif($i > 0){

                    $nombreDpendencia     = $row[$this->numberByLetter('A')];
                    $nombreUsuario        = $row[$this->numberByLetter('B')];
                    $apellidoUsuario      = $row[$this->numberByLetter('C')];
                    $tipoidentificacion   = $row[$this->numberByLetter('D')];
                    $documentoUsuario     = $row[$this->numberByLetter('E')];
                    $cargoUsuario         = $row[$this->numberByLetter('F')];
                    $correoUsuario        = $row[$this->numberByLetter('G')];
                    $tipoUsuario          = $row[$this->numberByLetter('H')];
                    $perfilUsuario        = $row[$this->numberByLetter('I')];
                    $autenticacionUsuario = $row[$this->numberByLetter('J')];
                    $autenticacion        = $row[$this->numberByLetter('K')];                

                    if(($nombreDpendencia == '')
                        && ($nombreUsuario == '')
                        && ($apellidoUsuario == '')
                        && ($tipoidentificacion == '')
                        && ($documentoUsuario == '')
                        && ($cargoUsuario == '')
                        && ($correoUsuario == '')
                        && ($tipoUsuario == '')
                        && ($perfilUsuario == '')
                    ){
                        $ignorarFilas[] = $i; 
                    } else {                    

                        if (!is_null($nombreDpendencia) && $nombreDpendencia != '') {

                            /*** Valida si el nombre de dependencia existe en la DB ***/
                            $nomColumna = 'Nombre dependencia';
                            if (!isset($dependencias[$nombreDpendencia])) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrDepe', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $nombreDpendencia
                                ]);
                            }
                        }
                        
                        if (is_null($nombreUsuario) || $nombreUsuario == '') {

                            $nomColumna = 'Nombre usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataNull', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna
                            ]);

                        } elseif (strlen($nombreUsuario) > 80){

                            $nomColumna = 'Nombre usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitName', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna                                
                            ]);
                        }
                        
                        if (is_null($apellidoUsuario) || $apellidoUsuario == '') {

                            $nomColumna = 'Apellido Usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataNull', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna
                            ]);

                        } elseif (strlen($apellidoUsuario) > 80){

                            $nomColumna = 'Apellido Usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitSurname', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna,                                
                            ]);
                        }
                        
                        if (!is_null($tipoIdentificacion) && $tipoidentificacion != '') {

                            /*** Valida si el tipo de documento existe en la DB ***/
                            $nomColumna = 'Tipo identificación';
                            if (!isset($tipoIdentificacion[$tipoidentificacion])) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrTipoDoc', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $tipoidentificacion
                                ]);
                            }
                        }
                        
                        /* Documento de identidad */
                        if (is_null($documentoUsuario) || $documentoUsuario == '') {

                            $nomColumna = 'Documento Usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataNull', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna
                            ]);

                        } else {
                            $nomColumna = 'Documento Usuario';

                            if (strlen($documentoUsuario) > 20){                                
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitDocument', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna                                
                                ]);
                            }
                        }
                        
                        
                        if (is_null($cargoUsuario) || $cargoUsuario == '') {

                            $nomColumna = 'Cargo usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataNull', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna
                            ]);

                        } elseif (strlen($cargoUsuario) > 80){

                            $nomColumna = 'Cargo usuario';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitPosition', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna                                
                            ]);
                        }
                        
                        if (is_null($correoUsuario) || $correoUsuario == '') {

                            $nomColumna = 'Correo Electrónico';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataNull', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna
                            ]);
                        } else {
                            //Valida que el correo sea correcto
                            $nomColumna = 'Correo Electrónico';
                            if (!filter_var($correoUsuario, FILTER_VALIDATE_EMAIL)) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrCorreo' , [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $correoUsuario
                                ]);
                            }

                            if (strlen($correoUsuario) > 80){
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitEmail', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna                                
                                ]);
                            }
                        }
                        
                        
                        if (!is_null($tipoUsuario) && $tipoUsuario != '') {

                            /*** Valida si el tipo de usuario existe en la DB ***/
                            $nomColumna = 'Tipo de Usuario';
                            if (!isset($tipoUser[$tipoUsuario])) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrorUsuaNull', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $tipoUsuario
                                ]);
                            }
                        }
                        
                        if (!is_null($perfilUsuario) && $perfilUsuario != '') {

                            /*** Valida si el perfil (rol) existe en la DB ***/
                            $nomColumna = 'Perfil';
                            if (!isset($roles[$perfilUsuario])) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrorPerfNull', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $perfilUsuario
                                ]);
                            }
                        }
                        
                        if (!is_null($autenticacionUsuario) && $autenticacionUsuario != '') {

                            /*** Valida si la username ya existe ***/
                            $nomColumna = 'Usuario Autenticación';
                            if (isset($users[$autenticacionUsuario]['id']) || in_array($autenticacionUsuario, $usernameList)) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListExistUsua', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $autenticacionUsuario
                                ]);
                            }

                            $usernameList[] = $autenticacionUsuario;

                            /*** Valida si la username ya existe ***/
                            if (isset($users[$autenticacionUsuario]['idDependencia'])) {

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListExistUsuaDepe', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $autenticacionUsuario
                                ]);
                            }

                            if (strlen($autenticacionUsuario) > 80){

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'maxLimitPosition', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna                                
                                ]);
                            }
                        }
                        
                        if ($autenticacion != 'Si' && $autenticacion != 'No') {

                            $nomColumna = 'Autenticación Externa';
                            $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrInvalida', [
                                'fila'      => $fila,
                                'nomColumna'=> $nomColumna,
                                'valorCell' => $autenticacion
                            ]);
                        }
                    }
                }

                $fila++;
            }
            /****** Fin Validación de archivo ******

            /****** Procesar archivo sin errores ******/
            if (count($dataListErr) == 0) {
                return SELF::fileUploadDataBase($pathUploadFile, $ignorarFilas); //LLamado a la funcion de subida a la base de datos

            } else {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $dataListErr,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            /****** Fin Procesar archivo sin errores ******/

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'fileCanNotBeProcessed')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        /** Fin Validar si el archivo existe */

    }

    /** Funcion que procesa la carga de datos a DB
     * (Se realiza luego de haber validado el archivo excel
     *  y se realiza el llamado desde el mismo controlador)
     * @param $pathUploadFile [ruta del archivo]
     **/
    public function fileUploadDataBase($pathUploadFile, $ignorarFilas = [])
    {        
        $documento = IOFactory::load($pathUploadFile);
        $indiceHoja = 0;
        $dataDetalles = []; //Informacion para la tabla userDetalles;
        $dataUser = []; //Informacion para la tabla User
        $dataListErr = []; //Listado de errores
        $errors = [];
        $cantRegistros = 0;

        if (is_file($pathUploadFile)) {

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);
            $maxCell = $hojaActual->getHighestRowAndColumn();
            $hojaActualArray = $hojaActual->rangeToArray("A1:K". $maxCell['row']);

            $transaction = Yii::$app->db->beginTransaction();

            /***
                Carga los tipos de documento de los usuarios en un array para validar si el dato en el excel es valido
            ***/
            $tipoIdentificacion = [];
            $modelTipos = TiposIdentificacion::find()->where(['estadoTipoIdentificacion' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelTipos as $infoTipos){
                    $tipoIdentificacion[$infoTipos['nombreTipoIdentificacion']] = $infoTipos['idTipoIdentificacion'];
                }
            
            /***
                Carga los tipos de documento de los usuarios en un array para validar si el dato en el excel es valido
            ***/
            $idRol = [];
            $Roles = Roles::find()->where(['estadoRol' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($Roles as $infoRol){
                    $idRol[$infoRol['nombreRol']] = $infoRol['idRol'];
                }
            
            /***
                Carga los nombres de usuarios (username = nº documento) en un array para validar si el dato en el excel es valido
            ***/
            $dependencias = [];
            $modelDependencia = GdTrdDependencias::find()->where(['estadoGdTrdDependencia' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelDependencia as $infoDepend){
                    $dependencias[$infoDepend['nombreGdTrdDependencia']] = $infoDepend['idGdTrdDependencia'];
                }

            /***
                Carga los tipos de usuario en un array para validar si el dato en el excel es valido
            ***/
            $tipoUser = [];
            $modelTipoUser = UserTipo::find()->where(['estadoUserTipo' =>  Yii::$app->params['statusTodoText']['Activo']])->all();
                foreach($modelTipoUser as $infoTipoUser){
                    $tipoUser[$infoTipoUser['nombreUserTipo']] = $infoTipoUser['idUserTipo'];
                }
            
            /***
                Se valida si el dato que llega en al columna del directorio activo
            ***/   
            $listsldapArray = [];
            $listsEmail = [];
            $listsldapArray['Si'] =  '10';
            $listsldapArray['No'] =  '0';

            # Iterar filas
            foreach ($hojaActualArray as $i => $row) {
                
                if(!in_array($i, $ignorarFilas)){                

                    if($i > 0 ){

                        $nombreDpendencia     = $row[$this->numberByLetter('A')];
                        $nombreUsuario        = $row[$this->numberByLetter('B')];
                        $apellidoUsuario      = $row[$this->numberByLetter('C')];
                        $tipoidentificacion   = $row[$this->numberByLetter('D')];
                        $documentoUsuario     = $row[$this->numberByLetter('E')];
                        $cargoUsuario         = $row[$this->numberByLetter('F')];
                        $correoUsuario        = $row[$this->numberByLetter('G')];
                        $tipoUsuario          = $row[$this->numberByLetter('H')];
                        $perfilUsuario        = $row[$this->numberByLetter('I')];
                        $autenticacionUsuario = $row[$this->numberByLetter('J')];
                        $autenticacion        = $row[$this->numberByLetter('K')]; 

                        if (!is_null($nombreDpendencia) && $nombreDpendencia != '') {
                            $dataUser['idGdTrdDependencia'] = $dependencias[$nombreDpendencia];
                        }
                        
                        if (!is_null($nombreUsuario)  && $nombreUsuario != '') {
                            $dataDetalles['nombreUserDetalles'] = $nombreUsuario;
                        } 
                        
                        if (!is_null($apellidoUsuario)  && $apellidoUsuario != '') {
                            $dataDetalles['apellidoUserDetalles'] = $apellidoUsuario;
                        }
                        
                        if (!is_null($tipoidentificacion)  && $tipoidentificacion != '') {
                            $dataDetalles['idTipoIdentificacion'] = $tipoIdentificacion[$tipoidentificacion];
                        }
                        
                        if (!is_null($documentoUsuario)  && $documentoUsuario != '') {
                            $dataDetalles['documento'] = $documentoUsuario;
                        }
                        
                        if (!is_null($cargoUsuario)  && $cargoUsuario != '') {
                            $dataDetalles['cargoUserDetalles'] = $cargoUsuario;
                        }
                        
                        if (!is_null($correoUsuario)  && $correoUsuario != '') {
                            $dataUser['email'] = $correoUsuario;
                        }
                        
                        if (!is_null($tipoUsuario)  && $tipoUsuario != '') {
                            $dataUser['idUserTipo'] = $tipoUser[$tipoUsuario];
                        }
                        
                        if (!is_null($perfilUsuario)  && $perfilUsuario != '') {
                            $dataUser['idRol'] = $idRol[$perfilUsuario];
                        }
                        
                        if (!is_null($autenticacionUsuario)  && $autenticacionUsuario != '') {
                            $dataUser['username'] = $autenticacionUsuario;
                        }
                        
                        if (!is_null($autenticacion) && $autenticacion != '') {
                            $dataUser['ldap'] = $listsldapArray[$autenticacion];
                        }
                    }

                    // Se agrega y actualiza los datos de la tabla USER
                    if (count($dataUser) > 1) {
                        
                        if(isset($dataUser['username']) && $dataUser['username'] != ''){

                            $modelUser = User::find()
                                ->where(['status' =>  Yii::$app->params['statusTodoText']['Activo'], 'username' => $dataUser['username']])->one();

                            if ($modelUser != null) { //Actualiza el registro
                                $modelUser->email = $dataUser['email'];
                                $modelUser->idRol = (int) $dataUser['idRol'];                        
                                $modelUser->idUserTipo = $dataUser['idUserTipo'];                        

                            } else { //Crea el registro
                                $modelUser = new User();
                                $modelUser->username = (string) $dataUser['username'];
                                $modelUser->email = $dataUser['email'];
                                $modelUser->idRol = (int) $dataUser['idRol']; 
                                $modelUser->fechaVenceToken = date('Y-m-d'); 
                                $modelUser->idUserTipo = $dataUser['idUserTipo'];                                       
                                $modelUser->idGdTrdDependencia = $dataUser['idGdTrdDependencia'];
                                $modelUser->ldap = $dataUser['ldap'];
                                $modelUser->status = Yii::$app->params['statusTodoText']['Activo'];

                                /***
                                 * Si el usuario indica que se autentica por base de datos, ldap=0
                                 * se genera un password temporal de lo contrario es el no.cedula
                                 */
                                if($dataUser['ldap'] == '0'){
                                    $password = $modelUser->generatePassword();
                                }else{
                                    $password = (string) $dataDetalles['documento'];
                                }

                                $modelUser->setPassword($password);
                                $modelUser->generateAuthKey();
                                $modelUser->accessToken = $modelUser->generateAccessToken();
                                $modelUser->fechaVenceToken = date("Y-m-d",strtotime(date('Y-m-d')."+ ".Yii::$app->params['TimeExpireToken']." days"));
                                
                            }                     

                            if ($modelUser->save()) {

                                /***  
                                 * Si el usuario indica que se autentica por base de datos una vez
                                 * insertado el usuario en la base de datos se envia notificación
                                 * de correo electronico para que establezca la contraseña
                                 */
                                if($dataUser['ldap'] == '0'){

                                    $headMailText = Yii::t('app', 'headMailTextRegistro');
                                    $textBody = Yii::t('app', 'textBodyRegistro').$modelUser->username;
                                    
                                    if (filter_var($modelUser->email, FILTER_VALIDATE_EMAIL)) {
                                        $envioCorreo = CorreoController::registro($modelUser->email, $headMailText, $textBody);
                                    }

                                   if ($envioCorreo['status'] == true) {
                                    $ok = 'ok';
                                   }

                                } 

                                /***  Fin log Auditoria   ***/
                                $cantRegistros++;

                            } else {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $modelUser->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                        }  else {

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app','errDataUser')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        
                    }

                    // Se agrega y actualiza los datos de la tabla USER DETALLES
                    if (count($dataDetalles) > 1) {

                        $modelUserDetalles = UserDetalles::find()
                            ->where(['estadoUserDetalles' =>  Yii::$app->params['statusTodoText']['Activo'], 'documento' => $dataDetalles['documento']])->one();

                        if ($modelUserDetalles != null) { //Actualiza el registro
                            $modelUserDetalles->nombreUserDetalles = (string) $dataDetalles['nombreUserDetalles'];
                            $modelUserDetalles->apellidoUserDetalles = (string) $dataDetalles['apellidoUserDetalles'];
                            $modelUserDetalles->idTipoIdentificacion = (int) $dataDetalles['idTipoIdentificacion'];
                            $modelUserDetalles->cargoUserDetalles = (string) $dataDetalles['cargoUserDetalles'];
                            $modelUserDetalles->documento = (string) $dataDetalles['documento'];  
                            $modelUserDetalles->creacionUserDetalles = date('Y-m-d');

                        } else { //Crea el registro
                            $modelUserDetalles = new UserDetalles();
                            $modelUserDetalles->nombreUserDetalles = (string) $dataDetalles['nombreUserDetalles'];
                            $modelUserDetalles->apellidoUserDetalles = (string) $dataDetalles['apellidoUserDetalles'];
                            $modelUserDetalles->idTipoIdentificacion = (int) $dataDetalles['idTipoIdentificacion'];
                            $modelUserDetalles->cargoUserDetalles = (string) $dataDetalles['cargoUserDetalles'];
                            $modelUserDetalles->documento = (string) $dataDetalles['documento'];      
                            $modelUserDetalles->creacionUserDetalles = date('Y-m-d');   
                            $modelUserDetalles->idUser = $modelUser->id;              
                        }                      

                        if ($modelUserDetalles->save()) {


                            /***  
                             * Si el usuario indica que se autentica por base de datos una vez
                             * insertado el usuario en la base de datos se envia notificación
                             * de correo electronico para que establesca la contraseña
                             */
                            if($dataUser['ldap'] == '0'){
                                
                                if (filter_var($modelUser->email, FILTER_VALIDATE_EMAIL)) {
                                    // Agrega la cabecera del correo
                                    $headMailText = Yii::t('app', 'headMailTextRegistro');
                                    $listsEmail[$modelUser->username]['headMailText'] = $headMailText;
                                    // Agrega el cuerpo del correo
                                    $textBody = Yii::t('app', 'textBodyRegistro').$modelUser->username;
                                    $listsEmail[$modelUser->username]['textBody'] = $textBody;
                                    // Agrega el correo a enviar
                                    $listsEmail[$modelUser->username]['email'] = $modelUser->email;

                                    $envioCorreo = CorreoController::registro($modelUser->email, $headMailText, $textBody);
                                }                         

                            }

                            $ususario = User::findOne(['id' => $modelUser->id]);
                            $rol = Roles::findOne(['idRol' => $ususario->idRol]);
                            $ususarioDetalle = UserDetalles::findOne(['idUser' => $modelUser->id]);
                            $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $ususario->idGdTrdDependencia]);
                            ($ususario->ldap) == 0 ? $autenticacion = 'Base de datos' : $autenticacion ='Directorio activo (LDAP)';
                            
                            $dataUsuarios = 'Id Usuario: '.$ususario->id;
                            $dataUsuarios .= ', Usuario: '.$ususario->username;
                            $dataUsuarios .= ', Nombre Usuario: '.$ususarioDetalle->nombreUserDetalles.' '.$ususarioDetalle->apellidoUserDetalles;
                            $dataUsuarios .= ', Id tipo identificación: '.$ususarioDetalle->idTipoIdentificacion;
                            $dataUsuarios .= ', Tipo identificación: '.$ususarioDetalle->tipoIdentificacion->nombreTipoIdentificacion;
                            $dataUsuarios .= ', Número de documento: '.$ususarioDetalle->documento;
                            $dataUsuarios .= ', Dependencia asignada: '.$dependencia->codigoGdTrdDependencia.' - '.$dependencia->nombreGdTrdDependencia;
                            $dataUsuarios .= ', Cargo: '.$ususarioDetalle->cargoUserDetalles;
                            $dataUsuarios .= ', Correo Electrónico: '.$ususario->email;
                            $dataUsuarios .= ', Id Rol: '.$ususario->idRol;
                            $dataUsuarios .= ', Nombre Rol: '.$rol->nombreRol;
                            $dataUsuarios .= ', Id Tipo de Usuario: '.$ususario->idUserTipo;
                            $dataUsuarios .= ', Nombre Tipo Usuario: '.Yii::$app->params['tipoUsuarioText'][$ususario->idUserTipo];
                            $dataUsuarios .= ', Método de Autenticación: '.$autenticacion;
                            $dataUsuarios .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$ususario->status];
                            $dataUsuarios .= ', Fecha creación: '.date("Y-m-d H:i:s", $ususario->created_at);

                            /*** log Auditoria ***/
                            if(!Yii::$app->user->isGuest) {
                                HelperLog::logAdd(
                                    true,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    Yii::$app->params['eventosLogText']['crear']." la tabla de User", // texto para almacenar en el evento
                                    '',
                                    $dataUsuarios, //Data
                                    array() //No validar estos campos
                                );
                            }
                            /***  Fin log Auditoria   ***/
                            //$cantRegistros++;

                        } else {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $modelUserDetalles->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }
            }

            /** Validar si se inserto al menos un registro **/
            if ($cantRegistros > 0) {
                $transaction->commit();


                // Se recorre la lista de correos validos y se les notifica
                foreach ($listsEmail as $key => $usuarioEmail ) {
                    $envioCorreo = CorreoController::registro($usuarioEmail['email'], $usuarioEmail['headMailText'], $usuarioEmail['textBody']);

                    if ($envioCorreo['status'] == true) {
                       $ok = 'ok';
                    }
                }
                
                if(isset($ok)){
                    $mensajeOk = ", " . Yii::t('app', 'successMail');
                }else{
                    $mensajeOk = "";
                }    
                
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'uploadDocument',[
                        'count' => $cantRegistros,
                        'msn' => $mensajeOk
                    ]),
                    // 'message' => 'Se cargo de forma correcta ' . $cantRegistros . ' registros'.$mensajeOk,
                    'data' => [],
                    'dataListUser' => $this->reloadIndex(),
                    'dataModule' => 'Users',
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                $transaction->rollback();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app','uploadedFileContainsNoData')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app','fileCanNotBeProcessed')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


    /** Funcion que permite descargar la plantilla
     * del formato de usuarios
     **/
    public function actionDownloadFormat() {

        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        $sheets = HelperLoads::generateLoadUsers();
        $fileName = 'formato-usuarios-sistema-' . Yii::$app->user->identity->id . '.xlsx';
        $generacion = HelperGenerateExcel::generarExcelV2('user_formats', $fileName, $sheets);
        $rutaDocumento = $generacion['rutaDocumento'];

        /* Enviar archivo en base 64 como respuesta de la petición **/
        if($generacion['status'] && file_exists($rutaDocumento)){

            $dataFile = base64_encode(file_get_contents($rutaDocumento));
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $fileName,
                'status' => 200,
                'ruta' => $rutaDocumento,
            ];
            $return = HelperEncryptAes::encrypt($response, true);
            // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
            $return['datafile'] = $dataFile;

            return $return;


        } else {
            Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function getList(){
        $modelUser = User::find()->all();

        $usersList = [];
        foreach($modelUser as $user){
            $usersList[] = [
                'label' => $user->username,
                'value' => $user->id
            ];
        }

        return $usersList;
    }

    //Funcion para convertir las letras en numeros
    public function numberByLetter($letter)
    {

        $letterArray = array(
            'A' => 0,
            'B' => 1,
            'C' => 2,
            'D' => 3,
            'E' => 4,
            'F' => 5,
            'G' => 6,
            'H' => 7,
            'I' => 8,
            'J' => 9,
            'K' => 10,
        );

        return $letterArray[$letter];
    }

    /**
     * cerrar sesión  de usuarios solo registra en el log
     * @return mixed
     */
    public function actionLogout()
    {

        $modelUser = User::findOne(['id' => Yii::$app->user->identity->id ]);

        if( isset($modelUser) ){

            // Se EncryptA primero la respuesta ya que si esta lleva la autentificación aun
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'successSave'),
                'data' => [],
                'status' => 200,
            ];
            $dataResponse = HelperEncryptAes::encrypt($response, true);

            // Limpia la variable 
            $modelUser->accessToken = '0';

            if ($modelUser->save()) {

                /***    Inicio log Auditoria   ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['Logout'], //texto para almacenar en el evento
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                return $dataResponse;

            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app','errDataUser')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        } else {
            Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }

    /** Función que se ejecuta cuando se utiliza el proceso de radicación, para saber la cantidad especifica de registros */
    public function actionComercial(){

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

            $request = $request['data']; 
            $modelUsuarios = User::findAll(['status' => Yii::$app->params['statusTodoText']['Activo']]);

            if(count($modelUsuarios) >= $request){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => [Yii::t('app', 'No se pueden crear mas registros en el sistema, comuniquese con el area comercial de SkinaTech.')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', ''),
                    'data' => [Yii::t('app', '')],
                    'status' => Yii::$app->response->statusCode,
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
    }

}
