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

use api\components\HelperDynamicForms;
use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\models\CgEtiquetaRadicacion;
use api\models\CgGeneral;
use api\models\CgServidorCorreo;
use api\models\GdTrdDependencias;
use api\models\CgTiposRadicados;
use common\models\User;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\filters\auth\CompositeAuth;

class ConfiguracionGeneralController extends Controller{

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
                    'index-one'  => ['GET'],
                    'update'  => ['PUT'],
                    'index-label'  => ['GET'],
                    'change-status-label'  => ['PUT'],
                    'create-mail-server'  => ['POST'],
                    'update-mail-server'  => ['PUT'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }


    /* Funcion que retorna la data de la configuración general*/
    public function actionIndexOne()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $model = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();

            # Información de la configuración general
            $data = [
                'id' => $model->idCgGeneral,
                'tamanoArchivoCgGeneral' => $model->tamanoArchivoCgGeneral,
                'diasLimiteCgGeneral' => $model->diasLimiteCgGeneral,
                'correoNotificadorAdminCgGeneral' => $model->correoNotificadorAdminCgGeneral,
                'correoNotificadorPqrsCgGeneral' => $model->correoNotificadorPqrsCgGeneral,
                'diasNotificacionCgGeneral' => $model->diasNotificacionCgGeneral,
                'terminoCondicionCgGeneral' => $model->terminoCondicionCgGeneral,
                'diaRespuestaPqrsCgGeneral' => $model->diaRespuestaPqrsCgGeneral,
                'idDependenciaPqrsCgGeneral' => $model->idDependenciaPqrsCgGeneral,
                'tiempoInactividadCgGeneral' => $model->tiempoInactividadCgGeneral,
                'resolucionesCgGeneral' => $model->resolucionesCgGeneral,
                'resolucionesIdCgGeneral' => $model->resolucionesIdCgGeneral,
                'resolucionesNameCgGeneral' => $model->resolucionesNameCgGeneral,
                'codDepePrestaEconomicasCgGeneral' => $model->codDepePrestaEconomicasCgGeneral,
                'idPrestaPazYsalvoCgGeneral' => $model->idPrestaPazYsalvoCgGeneral
            ];

            // Lista para las dependencias
            $dependency = GdTrdDependencias::findAll(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo'] ]);
            foreach($dependency as $value){
                $dataList[] = ['id' => $value->idGdTrdDependencia, 'val' =>$value->codigoGdTrdDependencia.' - '.$value->nombreGdTrdDependencia];
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'dataList' => $dataList,
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


     /**
     * Updates an existing CgGeneral model.
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
                $dataOld = '';
                $dataNew = '';

                //**Valida los valores limite de tamaño parametrizado en php.ini*/
                $max_file = str_replace('M', '', ini_get('upload_max_filesize'));
                $max_post = str_replace('M', '', ini_get('post_max_size'));

                if((int)$max_file < (int)$max_post){
                    $uploadMaxFilesize = (int) ($max_file);
                }else{
                    $uploadMaxFilesize = (int) ($max_post);
                }

                if((int) $request['tamanoArchivoCgGeneral'] >  $uploadMaxFilesize){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'setMaxFileSize', [
                            'uploadMaxFilesize' => $uploadMaxFilesize.'MB'
                        ])]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //** Fin validación */

                $transaction = Yii::$app->db->beginTransaction();

                $modelCgGeneral = $this->findModel($id);

                # Se obtiene la información del log anterior
                $dataOld = self::dataLog($modelCgGeneral);

                # Atributos de la configuración general
                $modelCgGeneral->attributes = $request;
                $modelCgGeneral->resolucionesCgGeneral = $request['resolucionesCgGeneral'] ? 10 : 0;
                $modelCgGeneral->resolucionesNameCgGeneral = strtoupper($request['resolucionesNameCgGeneral']);

                if($modelCgGeneral->save()){

                    # Consulta para obtener la información actualizada del nuevo registro para el log
                    $modelNew = CgGeneral::findOne(['idCgGeneral' => $modelCgGeneral->idCgGeneral]);
                    $dataNew = self::dataLog($modelNew);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['updateConfigGeneral'] . ", de la tabla CgGeneral", //texto para almacenar en el evento
                        $dataOld, //data old string
                        $dataNew, //data string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }  else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelCgGeneral->getErrors(),
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

    public function actionDatosResoluciones() {
        $modelCgTiposRadicados = CgTiposRadicados::find()
            ->where(['nombreCgTipoRadicado' => strtoupper(Yii::$app->params['nombreResoluciones'])])
            ->one();
        if($modelCgTiposRadicados) {
            $data = [
                'idCgTipoRadicado' => $modelCgTiposRadicados->idCgTipoRadicado,
                'nombreCgTipoRadicado' => $modelCgTiposRadicados->nombreCgTipoRadicado
            ];

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'resolveData' => true,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'theTypeOfFilingResolutionsMustBeCreated'),
                'data' => [],
                'resolveData' => false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


     /**
     * Finds the CgGeneral model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgGeneral the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgGeneral::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

     /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $model [Modelo de la tabla]
     * @return $dataLog [String] [Información del modelo]
     **/
    protected function dataLog($model) {
        if(!is_null($model)) {
            # Valores del modelo se utiliza para el log
            $labelModel = $model->attributeLabels();
            $dataLog = '';

            foreach ($model as $key => $value) {
                switch ($key) {
                    case 'idDependenciaPqrsCgGeneral':
                        $dataLog .= 'Id de la dependencia: '.$model->idDependenciaPqrsCgGeneral.', ';
                        $dataLog .= 'Dependencia: '.$model->idDependenciaPqrsCgGeneral0->nombreGdTrdDependencia.', ';
                    break;
                    case 'estadoCgGeneral':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                    break;
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }
            return $dataLog;
        }
    }


    /**
     * Función que consulta la configuración general del sistema activa
     * @return [array] [Retorna los datos si esta activa la configuración,
     * sino retorna el error] */
    public static function generalConfiguration(){

        $data = [];

        # Consulta de la configuración general del sistema activa
        $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);

        if(!is_null($modelCgGeneral)){

            $data = [
                'tamanoArchivoCgGeneral'          => $modelCgGeneral->tamanoArchivoCgGeneral,
                'diasLimiteCgGeneral'             => $modelCgGeneral->diasLimiteCgGeneral,
                'correoNotificadorAdminCgGeneral' => $modelCgGeneral->correoNotificadorAdminCgGeneral,
                'correoNotificadorPqrsCgGeneral'  => $modelCgGeneral->correoNotificadorPqrsCgGeneral,
                'diasNotificacionCgGeneral'       => $modelCgGeneral->diasNotificacionCgGeneral,
                'terminoCondicionCgGeneral'       => $modelCgGeneral->terminoCondicionCgGeneral,
                'diaRespuestaPqrsCgGeneral'       => $modelCgGeneral->diaRespuestaPqrsCgGeneral,
                'idDependenciaPqrsCgGeneral'      => $modelCgGeneral->idDependenciaPqrsCgGeneral,
                'tiempoInactividadCgGeneral'      => $modelCgGeneral->tiempoInactividadCgGeneral,
                'iniConsClienteCgGeneral'         => $modelCgGeneral->iniConsClienteCgGeneral,
            ];    

            return [
                'status'  => true,
                'data' => $data
            ];        

        } else {
   
            return [
                'status'  => false,
                'message' => Yii::t('app', 'messageConfigGeneral'),
            ];     
        }        
    }



    /**
     * Lists all config etiqueta de radicacion.
     * @return mixed
     */
    public function actionIndexLabel($request)
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

            # Consulta de etiqueta
            $modelLabel =  CgEtiquetaRadicacion::find();           

            # Orden descendente para ver los últimos registros creados
            $modelLabel->orderBy(['cgEtiquetaRadicacion.idCgEtiquetaRadicacion' => SORT_DESC]); 

            # Limite de la consulta
            $modelLabel->limit(Yii::$app->params['limitRecords']);
            $modelRelation = $modelLabel->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $dataRelation) {  

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idCgEtiquetaRadicacion)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'           => $dataBase64Params,
                    'id'             => $dataRelation->idCgEtiquetaRadicacion,
                    'label'          => $dataRelation->etiquetaCgEtiquetaRadicacion,
                    'description'    => $dataRelation->descripcionCgEtiquetaRadicacion,
                    'date'           => $dataRelation->creacionCgEtiquetaRadicacion,
                    'statusText'     => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoCgEtiquetaRadicacion],
                    'status'         => $dataRelation->estadoCgEtiquetaRadicacion,
                    'rowSelect'      => false,
                    'idInitialList'  => 0
                );                
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::createDataForForm('indexEtiquetaRadicacion');
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => $formType,        
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
    * Lists tamanoArchivoCgGeneral config etiqueta de radicacion.
    * @return mixed
    */
    public function actionGetTamanoArchivo(){
        
        $data = [];

        # Consulta de la configuración general del sistema activa
        $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);

        if(!is_null($modelCgGeneral)){

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => ['tamanoArchivoCgGeneral' => $modelCgGeneral->tamanoArchivoCgGeneral],
                'status' => 200,          
            ];            
            return HelperEncryptAes::encrypt($response, true);              

        } else {
   
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => [],
                'status' => 200,          
            ];            
            return HelperEncryptAes::encrypt($response, true);   
        }   

    }  


    /* Cambio de estado de la configuracion de etiqueta de radicacion, 
    * no puede haber ningún registro que no este activo.
    */
    public function actionChangeStatusLabel()
    {
        $errors = [];
        $dataResponse = [];
        $dataResponseOld = [];
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
            $valid = true;
            $transaction = Yii::$app->db->beginTransaction();
            
            foreach ($request as $value) {

                $dataExplode = explode('|', $value);

                # Consulta de la configuración de etiqueta
                $model = $this->findModelLabel($dataExplode[0]);

                # Se valida cuantos registros activos se encuentran
                $countLabel = CgEtiquetaRadicacion::find()
                    ->where(['cgEtiquetaRadicacion.estadoCgEtiquetaRadicacion' => Yii::$app->params['statusTodoText']['Activo']])
                ->count();


                if ($model->estadoCgEtiquetaRadicacion == Yii::$app->params['statusTodoText']['Activo'] && $countLabel >= 2) {
                    $model->estadoCgEtiquetaRadicacion = Yii::$app->params['statusTodoText']['Inactivo'];
                    $estadoOLD = Yii::$app->params['statusTodoText']['Activo'];

                }  elseif ($model->estadoCgEtiquetaRadicacion == Yii::$app->params['statusTodoText']['Activo'] && $countLabel <= 2) {

                    # Retorna error para que no desactive el único registro
                    $valid = false;                 
                    $estadoOLD = Yii::$app->params['statusTodoText']['Activo'];

                } else {
                    $model->estadoCgEtiquetaRadicacion = Yii::$app->params['statusTodoText']['Activo'];
                    $estadoOLD = Yii::$app->params['statusTodoText']['Inactivo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatusLabel'] . $model->etiquetaCgEtiquetaRadicacion. ' con el estado: '. Yii::t('app','statusTodoNumber')[$model->estadoCgEtiquetaRadicacion],// texto para almacenar en el evento
                        [], //DataOld
                        [], //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponseOld[] = [
                        'id' => $model->idCgEtiquetaRadicacion,
                        'status' => $estadoOLD,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$estadoOLD],
                        'idInitialList' => $dataExplode[1] * 1
                    ];
                    $dataResponse[] = [
                        'id' => $model->idCgEtiquetaRadicacion,
                        'status' => $model->estadoCgEtiquetaRadicacion,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgEtiquetaRadicacion], 
                        'idInitialList' => $dataExplode[1] * 1
                    ];

                } else {
                    $errors[] = $model->getErrors();
                    $saveDataValid = false;
                }
               
            }

            # Error cuando se quiere desactivar el unico registro activo
            if($valid == false){

                $transaction->rollBack();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorSaveStatusLabel')]],
                    'dataStatus' => $dataResponseOld,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            
            # Evaluar respuesta de datos guardados #
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
                    'dataStatus' => $dataResponseOld,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            # Fin de evaluar respuesta de datos guardados #


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
     * Finds the CgEtiquetaRadicacion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgEtiquetaRadicacion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModelLabel($id)
    {
        if (($model = CgEtiquetaRadicacion::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }
}
