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
use api\components\HelperFiles;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\data\ActiveDataProvider;
use api\models\CgPlantillas;

/**
 * CgGestionPlantillasController implements the CRUD actions for CgPlantillas model.
 */
class CgGestionPlantillasController extends Controller
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
                    'create'  => ['POST'],
                    'update'  => ['POST'],
                    'descargar-plantillas' => ['POST'],
                    'encrypt' => ['POST']
                ],
            ],
        ];
    }

    public function actionEncrypt(){

        $metodo = 'GET'; // ['POST','GET, 'PUT']
        $needLogin = true; // [true, false] // Necesita estar logueado?

        $response = [  
            //'formCaracterizacion' => true,
            'id' => 105,
            // 'data' => [
            //     'id' => 105,
            //     'idTipoPersona' => 1,
            //     'nombreCliente' => 'Skina User Tercero ',
            //     'numeroDocumentoCliente' => '7776667777',
            //     'correoElectronicoCliente' => 'prueba@mail.com',
            //     'direccionCliente' => 'Carrera 80h #41-25 sur',
            //     'telefonoCliente' => '3023414629',
            //     'idNivelGeografico1' => 1,
            //     'idNivelGeografico3' => 1,
            //     'idNivelGeografico2' => 1,
            //     'idTipoIdentificacion' => 2,
            //     'generoClienteCiudadanoDetalle' => 1,
            //     'rangoEdadClienteCiudadanoDetalle' => 1,
            //     'vulnerabilidadClienteCiudadanoDetalle' => 6,
            //     'etniaClienteCiudadanoDetalle' =>  5,
            // ]
        ];

        $response = HelperEncryptAes::encrypt($response, $needLogin);
        if ($metodo == 'GET') {
            $response = base64_encode($response['encrypted']);
            return str_replace(array('/', '+'), array('_', '-'), $response);
        } elseif ($metodo == 'POST' || $metodo == 'PUT') {
            return $response;
        } else {
            return "Metodo no utilizado";
        }

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
                        if ($key == 'nombreCgPlantilla' || $key == 'extencionCgPlantilla' || $key == 'estadoCgPlantilla' || $key == 'fechaInicial' || $key == 'fechaFinal') {
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
            $plantillas = CgPlantillas::find();
            //Se reitera $dataWhere para solo obtener los campos con datos

            foreach($dataWhere as $field => $value){
                switch ($field) {
                    case 'nombreCgPlantilla':
                        $plantillas->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                    case 'extencionCgPlantilla':
                        $plantillas->andWhere([$field => $value]);
                    break;
                    case 'estadoCgPlantilla':
                        $plantillas->andWhere(['IN', 'clientes.' . $field, intval($value)]);
                    break; 
                    case 'fechaInicial':
                        $plantillas->andWhere(['>=', 'creacionCgPlantilla', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $plantillas->andWhere(['<=', 'creacionCgPlantilla', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $plantillas->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                }
            }                
            //Limite de la consulta
            $arrayDias = Yii::t('app', 'days');
            $plantillas->limit($limitRecords);
            $modelplantillas= $plantillas->orderBy(['estadoCgPlantilla' => SORT_DESC])->all(); // Orden descendente para ver los últimos registros creados

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelplantillas = array_reverse($modelplantillas);
            
            foreach($modelplantillas as $plantilla){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($plantilla->idCgPlantilla ))
                );

                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $plantilla->idCgPlantilla,
                    'nombreCgPlantilla' => $plantilla->nombreCgPlantilla,
                    'extencionCgPlantilla' =>  $plantilla->extencionCgPlantilla,
                    'createdate' => $plantilla->creacionCgPlantilla,
                    'status' => $plantilla->estadoCgPlantilla,
                    'statusText' =>  Yii::t('app', 'statusTodoNumber')[$plantilla->estadoCgPlantilla], 
                    'rowSelect' => false,
                    'idInitialList' => 0
                ];
            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgPlantillas');

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

                    // Valores anteriores del modelo
                    $modelOld = [];
                    foreach ($model as $key => $value) {
                        $modelOld[$key] = $value;
                    }

                    //Validar si existen radicados creados con el tipo
                    $statusOld = $model->estadoCgPlantilla;

                    if ($model->estadoCgPlantilla == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoCgPlantilla = yii::$app->params['statusTodoText']['Inactivo'];
                    } else {
                        $model->estadoCgPlantilla = yii::$app->params['statusTodoText']['Activo'];
                    }

                    if ($model->save()) {

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus2'] . " de la plantilla " . $model->nombreCgPlantilla . ', ahora está ' . Yii::$app->params['statusTodoNumber'][$model->estadoCgPlantilla], //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array(
                            'id' => $model->idCgPlantilla, 'idInitialList' => $dataExplode[1] * 1,
                            'status' => $model->estadoCgPlantilla, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgPlantilla],
                            'statusOld' => $statusOld, 'statusTextOld' => Yii::t('app', 'statusTodoNumber')[$statusOld]
                        );

                    } else {
                        $errs = $model->getErrors();
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
            $model = CgPlantillas::find()->select(['estadoCgPlantilla'])->where(['idCgPlantilla' => $dataExplode[0] ])->one();
            if ($model != null) {
                $dataResponse[] = array(
                    'id' => $dataExplode[0], 
                    'idInitialList' => $dataExplode[1] * 1,
                    'status' => $model['estadoCgPlantilla'], 
                    'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCgPlantilla']]
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
        $modelCgPlantillas = $this->findModel($id);

            $data = [
                'idCgPlantilla' => $modelCgPlantillas->idCgPlantilla,
                'nameFile'  => $modelCgPlantillas->nombreCgPlantilla,
                'estadoCgPlantilla' => $modelCgPlantillas->estadoCgPlantilla,
                'creacionCgPlantilla' => $modelCgPlantillas->creacionCgPlantilla
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
     * Creates a new CgHorarioLaboral model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            if (!empty($request)) {

                 /*** Inicio desencriptación GET ***/ //La solicitud es por metodo post pero se utilizan los parametros de la url

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
                 
                    if($fileUpload){

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
                        $validationExtension = ['docx','odt'];
                        if (!in_array($fileUpload->extension, $validationExtension)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /* Fin validar tipo de archivo */

                        $rutaOk = true; // ruta base de la carpeta existente
                        $pathUploadFile = Yii::getAlias('@webroot')."/".Yii::$app->params['CarpGestionPlantillas'];

                        // Verificar que la carpeta exista y crearla en caso de no exista
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
                                'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar creación de la carpeta***/

                        // validar signo '/' en el nombre para no crear nuevos directorios
                        $nombrePlantilla = str_replace("/", "", $request['nameFile']);
                        $nombreArchivo = $fileUpload->name;
                        $rutaCgPlantilla = $pathUploadFile.'/'.$nombreArchivo;     

                        /*** Validar si ya existe la plantilla  ***/
                        if(file_exists($rutaCgPlantilla)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app','fileExists')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar si ya existe la plantilla  ***/

                        # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                        $uploadExecute = $fileUpload->saveAs($rutaCgPlantilla);

                    }else{
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'enterFile')],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

                    /*** Guardar en base de datos la info del documento ***/
                    $model = new CgPlantillas();
                    // $model->attributes = $request;
                    $model->nombreCgPlantilla = $nombrePlantilla;
                    $model->idUser =  Yii::$app->user->identity->id;
                    $model->rutaCgPlantilla = $rutaCgPlantilla;
                    $model->extencionCgPlantilla = $fileUpload->extension ?? null;
                    $model->creacionCgPlantilla = date("Y-m-d H:i:s");   
                    $model->estadoCgPlantilla = Yii::$app->params['statusTodoText']['Activo'];
                            
                    /** Procesar el errores de la fila */
                    if (!$model->save()) {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $model->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Consultar si es un registro nuevo ***/
        
                    //  Inicio Log de auditoria 
                    $model->estadoCgPlantilla = Yii::$app->params['statusTodoNumber'][$model->estadoCgPlantilla]; //Solo para log

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla Cg Plantillas", //texto para almacenar en el evento
                        [], //DataOld
                        [$model], //Data
                        array('idUser') //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
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
     * Updates an existing CgPlantillas model.
     * If update is successful, the browser will be redirected to the 'view' page.
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

                    if($fileUpload){

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
                        $validationExtension = ['docx','odt'];
                        if (!in_array($fileUpload->extension, $validationExtension)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /* Fin validar tipo de archivo */

                        $rutaOk = true; // ruta base de la carpeta existente
                        $pathUploadFile = Yii::getAlias('@webroot')."/".Yii::$app->params['CarpGestionPlantillas'];
                      
                        // Verificar que la carpeta exista y crearla en caso de no exista
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
                                'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar creación de la carpeta***/

                        // validar signo '/' en el numero de guia para no crear nuevos directorios
                        $nombrePlantilla = str_replace("/", "",  $request['nameFile']);
                        $nombreArchivo = $fileUpload->name;
                        $rutaCgPlantilla = $pathUploadFile.'/'.$nombreArchivo;

                        # Validar que la ruta en base de datos no exista
                        $rutaCgPlantillaValida = CgPlantillas::find()->where(['rutaCgPlantilla' => $rutaCgPlantilla])
                        ->andWhere(['<>','idCgPlantilla', $request['id']])->one();
                        

                        if(isset($rutaCgPlantillaValida)){
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app','fileExists')]],
                                //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                        $uploadExecute = $fileUpload->saveAs($rutaCgPlantilla);
                    }

                    $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción
                    $model = $this->findModel($request['id']);

                    $modelOld = [];
                    foreach ($model as $key => $value) {
                        $modelOld[$key] = $value;
                    }

                    /*** Guardar en base de datos la info del documento ***/
                    // $model->attributes = $request;
                    $model->nombreCgPlantilla = $nombrePlantilla;
                    $model->idUser = Yii::$app->user->identity->id;
                    $model->extencionCgPlantilla = $fileUpload->extension ?? $modelOld['extencionCgPlantilla'];
                    $model->rutaCgPlantilla = $rutaCgPlantilla;
                    $model->creacionCgPlantilla = $modelOld['creacionCgPlantilla'] ?? date("Y-m-d H:i:s");   
                    $model->estadoCgPlantilla = $request['estadoCgPlantilla'] ?? Yii::$app->params['statusTodoText']['Activo'];
                            
                    /** Procesar el errores de la fila */
                    if (!$model->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $model->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Consultar si es un registro nuevo ***/
        
                    /***    Log de Auditoria  ***/ 
                    $modelOld['estadoCgPlantilla'] = Yii::$app->params['statusTodoNumber'][$modelOld['estadoCgPlantilla']]; //Solo para log
                    $model->estadoCgPlantilla = Yii::$app->params['statusTodoNumber'][$model->estadoCgPlantilla]; //Solo para log

                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Update'] . " Cg Plantillas", //texto para almacenar en el evento
                        [$modelOld], //DataOld
                        [$model], //Data
                        array('idUser') //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successUpdate'),
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
     * 
     * 
     */
    public function actionDescargarPlantillas(){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)){

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

                $arrayNotifications = [];
                $plantillas = [];

                    foreach($request['ButtonSelectedData'] as $key => $plantilla){

                        $modelCgPlantillas = CgPlantillas::find()->where(['idCgPlantilla' => $plantilla['id']])->one();



                        $fileName = $modelCgPlantillas->rutaCgPlantilla;
                        $fileName = explode('/', $fileName);
                        $fileName = end($fileName);

                        
                        if(isset($modelCgPlantillas)){

                            /*** Validar si ya existe la plantilla  ***/
                            if(file_exists($modelCgPlantillas->rutaCgPlantilla)){

                                // solo si el estado de la plantilla es activo
                                if ($modelCgPlantillas->estadoCgPlantilla == yii::$app->params['statusTodoText']['Activo']) {
                   
                                     // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                                     $plantillas[] = array(
                                         'datafile' => base64_encode(file_get_contents($modelCgPlantillas->rutaCgPlantilla)),
                                         //'nameFile' => $modelCgPlantillas->nombreCgPlantilla.'.'.$modelCgPlantillas->extencionCgPlantilla
                                         'nameFile' => $fileName
                                     );
     
                                     $arrayNotifications['success'][] =  [
                                         'nombreCgPlantilla' => $modelCgPlantillas->nombreCgPlantilla,
                                         'nameFile' => $fileName
                                     ];
                                }


                            } else {
                                $arrayNotifications['dangerFileExist'][] =  [
                                    'nombreCgPlantilla' => $modelCgPlantillas->nombreCgPlantilla,
                                    'nameFile' => $fileName
                                ];
                            }

                        }else{

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);

                        }

                    }


                    /** Iteracion de mensajes de notificación para la agrupación de los mismos */
                    $notifications = [];
                    foreach ($arrayNotifications as $key => $value) {
                        if ($key == 'success') {
                            if (count($value) > 1) {
                                $nombrePlantillas = [];
                                foreach ($value as $row) {                                    
                                    $nombrePlantillas[] = $row['nombreCgPlantilla'] ;
                                }
                                $nombrePlantillas = implode(', ', $nombrePlantillas);
                                $notifications[] =  [
                                    'message' => Yii::t('app', 'fileTemplateDownloadedGroup', [
                                        'nombrePlantillas' => $nombrePlantillas
                                    ]),
                                    'type' => 'success'
                                ];
                                $eventLogText = 'DownloadTemplates';
                            } else {
                                $nombrePlantillas = $value[0]['nombreCgPlantilla'];
                                $notifications[] =  [
                                    'message' => Yii::t('app', 'fileTemplateDownloaded', [
                                        'nombreArchivo' => $value[0]['nameFile'],
                                        'nombrePlantilla' => $value[0]['nombreCgPlantilla'],                                        
                                    ]),
                                    'type' => 'success'
                                ];
                                $eventLogText = 'DownloadTemplate';
                            }

                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText'][$eventLogText] . '(' . $nombrePlantillas . ')', //texto para almacenar en el evento
                                [],
                                [], //Data
                                array() //No validar estos campos
                            );
                            /***    Fin log Auditoria   ***/  

                        } elseif ($key == 'dangerFileExist') {
                            if (count($value) > 1) {
                                $nombrePlantillas = [];                               
                                foreach ($value as $row) {
                                    $nombrePlantillas[] = $row['nombreCgPlantilla'];                                                                        
                                }
                                $notifications[] =  [
                                    'message' => Yii::t('app', 'errorDownloadTemplateGroup', [
                                        'nombrePlantillas' => implode(', ', $nombrePlantillas)                                        
                                    ]),
                                    'type' => 'danger'
                                ];
                            } else { 

                                $notifications[] =  [
                                    'message' => Yii::t('app', 'errorDownloadTemplate', [
                                        'nombrePlantilla' => $value[0]['nombreCgPlantilla'],
                                        'nombreArchivo' => $value[0]['nameFile'] 
                                    ]),
                                    'type' => 'danger'
                                ];
                            }
                        }
                    }
                    /** Fin Iteracion de mensajes de notificación para la agrupación de los mismos */

                    if (count($plantillas) == 1) {
                        $message = Yii::t('app','fileDownloaded');
                    } elseif (count($plantillas) > 1) {
                        $message = Yii::t('app','fileDownloadeds');
                    } else {
                        $message = Yii::t('app','errorInDownload');
                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $message,
                        'status' => 200,
                        'notifications' => $notifications,
                    ];
                    $return = HelperEncryptAes::encrypt($response, true);

                    // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                    $return['datafile'] = $plantillas;

                    return $return;

            }else{

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
     * @return CgPlantillas the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgPlantillas::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
