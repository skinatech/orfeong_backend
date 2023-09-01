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

namespace api\modules\version1\controllers\dynamicForms;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;

use Yii;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;

use api\components\HelperPucComercial;
/*** Tablas que se utilizan para consultas de listas ***/
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\ConceptosRetencionCdi;
use api\models\Anexos13AActivosVendidos;
use api\models\Anexos13BActivosVendidos;
use api\models\ConceptosAnexos15;
use api\models\RequisitosAnexos15;
use api\models\PucComercial;
use api\models\Anexos25AConceptos;

use api\components\HelperLog;
use api\models\GdTrdSubseries;
use api\models\User;
use api\models\GaPiso;
use api\models\GaBodega;

class DynamicFormsController extends Controller
{
    const PERMISSION_UPDATE = 'version1/dynamicForms/dynamic-forms/update';
    const LOAD_FORM = 'version1/dynamicForms/dynamic-forms/load-form';

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
                    'index-all' => ['GET'],
                    'create'  => ['POST'],
                    'update' => ['PUT'],
                    'update-array' => ['PUT'],
                    'load-form' => ['POST'],
                    'calculate-form' => ['POST']
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionIndexAll($request)
    {
        $metodo = 'POST'; // ['POST','GET, 'PUT']
        $needLogin = true; // [true, false] // Necesita estar logueado?
        $response = [
            'idRenta' => 90,
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

        if(HelperValidatePermits::validateUserPermits(self::LOAD_FORM, Yii::$app->user->identity->rol->rolesTiposOperaciones)){

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
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación GET ***//

            $dataList = [];
            $idRenta = $request['idRenta'];
            $formType = HelperDynamicForms::createDataForForm($request['formType']);
            if ($formType == null) {
                throw new NotFoundHttpException('The requested page does not exist.');
            }

            // $modelName = new $formType['configGlobal']['modelName'];
            // $model = $modelName::find()->where(['idRenta' => $idRenta])->all();

            // $attributes = $modelName::attributeLabels();
            // $i = 0;
            // foreach ($model as $value) {
            //     foreach ($attributes as $att => $valueAtt) {
            //         $dataList[$i][$att] = $value[$att];
            //     }
            //     $i++;
            // }

            // Yii::$app->response->statusCode = 200;
            // $response = [
            //     'message' => 'Ok',
            //     'data' => $dataList,
            //     'status' => 200,
            // ];
            // return HelperEncryptAes::encrypt($response, true);


            //Fin function

            //*** Validar que el modelo del formulario este configurado en los helpers  ***//
            if ($formType == null) {

                return [
                    'status' => false,
                    'msg' => 'No se puede procesar el formulario'
                ];

            }else{


                $model = new $formType['configGlobal']['modelName'];

                $anexos_all = $model::find()->where(['idRenta' => $request['idRenta']])->all();

                $dinamic_form = [];

                //return $anexos_all;

                if(!empty($anexos_all)){

                    foreach ($anexos_all as $anexo) {
                        
                        foreach ($formType['schema'] as $key => $model) {

                            $db_name = $formType['schema'][$key]['key'];

                                $formType['schema'][$key]['defaultValue'] =  $anexo[$db_name];
                            
                        }
                        
                        $dinamic_form[] = $formType['schema'];

                    }

                    $formType['schema'] = $dinamic_form;

                }else{

                    return [
                        'status' => false,
                        'msg' => 'No se puede procesar los datos del anexo'
                    ];

                }    


            }

            return $formType;

        }else{

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);

        }
    }

    /** Función para retorno de estructura de un formulario dinámico */
    public function actionLoadForm()
    {
     
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)){
            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
            if(!empty($jsonSend)) {

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

                $formType = self::setListadoBD($request['formType'], $request['idRenta'] );

                $formUpdate = self::loadFormData($formType, $request['idRenta'] );


                /** Validar si existe el formulario */
                if ($formType != null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data' => $formType,
                        'dataUpdate' => $formUpdate,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    throw new NotFoundHttpException('The requested page does not exist.');
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','emptyJsonSend'),
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

    /** Funcion que carga la información de los anexos */
    public function loadFormData($formType, $idRenta)
    {

        $dataUpdate =  [];
        $data = [];
        $statusUpdate = false;

        if( !empty($formType) ){
            foreach ($formType['configGlobal']['formTypeAll'] as $key => $value) {

                // Agrega la estructura vacia de los cuadros faltas del anexo7

                $nomAnexo = ucfirst($value);
                $nomAnexo = 'api\models\\'.$nomAnexo;
                $modelAnexo = new $nomAnexo();
                $modelAnexo = $nomAnexo::find()->where(['idRenta' => $idRenta ])->all();
                // $dataUpdate[$key] = $modelAnexo;

                if( is_array( $modelAnexo ) ){

                    foreach ($modelAnexo as $keyModel => $valModel) {

                        foreach ($valModel as $keyCampo => $valCampo) {
                            
                            $statusUpdate = true;
                            $dataUpdate[$key][$keyModel][$keyCampo] = $valCampo;
                        }
                        $dataUpdate[$key][$keyModel]['informativo'] = false;
                        $dataUpdate[$key][$keyModel]['campoModifiManual'] = true;
                    
                    }    
                }
            }
        }

        $data = array( 'dataUpdate' => $dataUpdate, 'statusUpdate' => $statusUpdate );
        return $data;

    }

    public function actionCreate()
    {
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');
            if(!empty($jsonSend)) {

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

                /** Validar datos recibidos */
                $validateRequest = $this->validateRequest($request, 'objeto');
                if ($validateRequest['status'] == false) {
                   Yii::$app->response->statusCode = 200;
                   $response = [
                       'message' => Yii::t('app','errorValidacion'),
                       'data' => ['error' => [$validateRequest['msg']]],
                       'status' => Yii::$app->params['statusErrorValidacion'],
                   ];
                   return HelperEncryptAes::encrypt($response, true);
                }

                $formType = HelperDynamicForms::createDataForForm($request['formType']);

                $modelName = $formType['configGlobal']['modelName'];
                $model = new $modelName; //Nombre del model

                /** Recorrer campos no actualizables para eliminarlos si se recibe alguno en la petición */
                foreach ($formType['configGlobal']['inputsNoCreate'] as $inputsNoCreate) {
                    unset($request[$inputsNoCreate]);
                }

                $model->attributes = $request;

                if($model->save()){

                    /***  log Auditoria ***/
                    HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", tabla {$modelName::getTableSchema()->name}", // texto para almacenar en el evento
                        [],
                        [$model], //Data
                        array() //No validar estos campos
                    );
                    /***   Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','emptyJsonSend'),
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

                /** Validar datos recibidos */
                $validateRequest = $this->validateRequest($request, 'objeto');
                if ($validateRequest['status'] == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [$validateRequest['msg']]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $transaction = Yii::$app->db->beginTransaction();
                $formType = HelperDynamicForms::createDataForForm($request['formType']);

                $modelName = $formType['configGlobal']['modelName']; //Nombre del modelo
                $pk = $modelName::primaryKey()[0]; //Nombre del campo primario del modelo
                $idRenta = $request['idRenta'];

                /** Recorrer campos que no deben ser procesados para retirarlos si se recibe alguno en la petición */
                foreach ($formType['configGlobal']['inputsNoUpdate'] as $inputNoUpdate) {
                    unset($request[$inputNoUpdate]);
                }

                $model = $modelName::find()->where([$pk => $request[$pk], 'idRenta' => $idRenta])->one();

                $model->attributes = $request;
                if ($model->save()) {

                    /***  log Auditoria ***/
                    HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla {$modelName::getTableSchema()->name}", // texto para almacenar en el evento
                        [],
                        [$model], //Data
                        array() //No validar estos campos
                    );
                    /***   Fin log Auditoria   ***/

                    $transaction->commit();
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
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','emptyJsonSend'),
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

    public function actionUpdateArray()
    {
        if(HelperValidatePermits::validateUserPermits(self::PERMISSION_UPDATE, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                /** Validar datos recibidos */
                
                $i = 0;
                $rowsCreados = 0; // Contador de registros creados
                $rowsActualizados = 0; // Contador de registros actualizados
                // $prueba = [];

                foreach ($request['formValue'] as $key => $cuadros) {
                
                    /*** ////////////////////////////////////////////   ****/                 

                    if ( !array_key_exists($key, $request['formTypeAll'] ) ) {
                       break;
                    }

                    $formType = HelperDynamicForms::createDataForForm($request['formTypeAll'][$key]);

                    $idRenta = $request['idRenta'];


                    /** Consultar modelo */
                    $modelName = $formType['configGlobal']['modelName']; //Nombre del modelo
                    
                    // Nombre del campo primario del modelo
                    $primaryKey = $modelName::primaryKey()[0];

                    /*try {
                       $primaryKey = $modelName::primaryKey()[0];
                       $prueba[] = $primaryKey;
                    } catch (\Throwable $e) {
                        
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','valMinimoRegistros')]],
                            'prueba' => $prueba,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }*/

                    /** Procesar array */
                    $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

                    /** Validar si no se reciben registros */
                    if (count($cuadros) == 0) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','valMinimoRegistros')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    foreach ($cuadros as $key_cuadros => $value) {


                        /****** Consultar si es un registro nuevo o actualizable ******/
                        if($key_cuadros == $primaryKey && $value == 0){

                            /*** Procesar registro nuevo ***/
                            
                            /** Recorrer campos que no deben ser procesados para retirarlos si se recibe alguno en la petición */
                            foreach ($formType['configGlobal']['inputsNoCreate'] as $inputsNoCreate) {
                                unset($cuadros[$key_cuadros][$inputsNoCreate]);
                            }
                           
                            $model = new $modelName();
                            $model->attributes = $value;
                            $model->idRenta = $idRenta;

                            /** Procesar el registro de la fila */
                            if (!$model->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $model->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                            $rowsCreados++;
    
                            /*** Fin Procesar registro nuevo ***/
                        }else{
    
                            /*** Procesar actualización de registro ***/
    
                            /** Recorrer campos no actualizables para retirarlos si se recibe alguno en la petición */
                            foreach ($formType['configGlobal']['inputsNoUpdate'] as $inputNoUpdate) {
                                unset($cuadros[$key_cuadros][$inputNoUpdate]);
                            }
    
                            $model = $modelName::find()->where([$primaryKey => $cuadros[$key_cuadros][$primaryKey], 'idRenta' => $idRenta])->one();

    
                            /** Validar si el registro no existe */
                            if ( $model == null) {
                                // $transaction->rollBack();
                                
                                /*Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app','recordNoFound'). ' ' . $i], 'model' => $cuadros[$key_cuadros][$primaryKey] ],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                                */
                                $model = new $modelName();
                                $model->attributes = $value;
                                $model->idRenta = $idRenta;

                                $rowsCreados++;

                            } else {
                                $model->attributes = $value;
                                  $model->idRenta = $idRenta;

                                  $rowsActualizados++;
                            }

                            /** Prosesar el update de la fila */
                            if (!$model->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $model->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
    
                            /*** Fin Procesar actualización de registro ***/
                        }
                        /******* Fin Consultar si es un registro nuevo o actualizable ******/
                        
                        $i++;
                    }

                    $transaction->commit();
                    
                }

                // Se carga la data 
                $formUpdate = self::loadFormData($formType, $request['idRenta'] );

                /** RETORNAR RESPUESTA SATISFACTORIA DEL SERVICIO */
                // $transaction->commit();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'seProcesaron') .$i. Yii::t('app', ' registro(s): ') .$rowsActualizados. Yii::t('app', ' actualizado(s) y ') .$rowsCreados. Yii::t('app', ' nuevo(s)'),
                    'data' => [],
                    'dataUpdate' => $formUpdate,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','emptyJsonSend'),
                    'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'dataUpdate' => [],
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
     * [Método para validar los datos recibidos en la peticion del cliente]
     * @param  [var] $request [Parámetros recibidos método por post o put]
     * @param  [string] $type    [tipo de validación [array, objeto]]
     * @return [
     *   [boolean] $status   [Estado de validación]
     *   [string]  $msg      [Mensaje de Error]
     * ]
     */
    public function validateRequest($request, $type){
        /** Validar tipo de formulario */
        if ( !isset($request['formType']) || !isset($request['idRenta'])  ) {
            return [
                'status' => false,
                'msg' => Yii::t('app','paramsRequired')
            ];
        } else {
            /** Validar variable data en array */
            if ($type == 'array' && !isset($request['data'])) {
                return [
                    'status' => false,
                    'msg' => Yii::t('app','paramsRequired')
                ];
            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::createDataForForm($request['formType']);
            if ($formType == null) {
                return [
                    'status' => false,
                    'msg' => Yii::t('app','unprocessableForm')
                ];
            } else {
                if ($type == 'array' && !is_array($request['data'])) {
                    return [
                        'status' => false,
                        'msg' => Yii::t('app','valDataArray')
                    ];
                } else {
                    return [
                        'status' => true,
                        'msg' => 'ok',
                    ];
                }
            }
        }
    }

    public function actionCalculateForm() {

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

            $formValuesPots = $request['formValue']['investments'];

            foreach ($formValuesPots as $kForms => $forms) {

                $configForm = HelperDynamicForms::createDataForForm($request['formType']);
                $operationsForm = $configForm['configGlobal']['operationsAnexos'];

                foreach ($forms as $kForm => $form) {
                    if(in_array($kForm, $operationsForm)) {
                        
                    }

                    /*if($operationsForm[$kForm]) {
                        $totalCampo = $operationsForm[$kForm]['calculo'];
                    }*/
                }



                /*foreach ($operationsForm as $kOperationForm => $operationForm) {
                    if() {

                    }
                }*/

                /*foreach ($forms as $kForm => $form) {
                    $totalCampo = $form;
                }*/
            }

            /*$configForm = HelperDynamicForms::createDataForForm($request['formType']);

            $operationsForm = $configForm['configGlobal']['operationsAnexos'];

            foreach ($operationsForm as $key => $value) {
                $totalCampo = $value['totalCampo'];
            }*/

            return array('fin' => $operationsForm['perdidaCompensacionRentaAnexo7A']);
        }
    }

    /*** Funcion que construye las listas desplegables ***/
    public static function setListadoBD($formType, $idRenta)
    {
        $nombreFormType = $formType;

        $formType = HelperDynamicForms::createDataForForm($formType);

        if ($nombreFormType == 'anexos11A') {
            $listas = NivelGeografico1::find()
                        ->select("nivelGeografico1 as value, nomNivelGeografico1 as label")
                        ->orderBy("nomNivelGeografico1")
                        ->asArray()->all();
            $listaOk = [];
            foreach ($listas as $key => $value) {
                $listaOk[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][4]['templateOptions']['options'] = $listaOk;
        }

        if ($nombreFormType == 'anexos11A') {
            $listas = ConceptosRetencionCdi::find()
                        ->select("idConcepto as value, concepto as label")
                        ->orderBy("idConcepto")
                        ->asArray()->all();
            $listaCon = [];
            foreach ($listas as $key => $value) {
                $listaCon[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][7]['templateOptions']['options'] = $listaCon;
        }

        if ($nombreFormType == 'anexos13A') {
            $listas = Anexos13AActivosVendidos::find()
                        ->select("descripcionAnexo13AActivoVendido as label, idAnexo13AActivoVendido as value")
                        ->orderBy("idAnexo13AActivoVendido")
                        ->asArray()->all();
            $listaAct = [];
            foreach ($listas as $key => $value) {
                $listaAct[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listaAct;
        }

        if ($nombreFormType == 'anexos13B') {
            $listas = Anexos13BActivosVendidos::find()
                        ->select("descripcionAnexo13BActivoVendido as label, idAnexo13BActivoVendido as value")
                        ->orderBy("idAnexo13BActivoVendido")
                        ->asArray()->all();
            $listaPais = [];
            foreach ($listas as $key => $value) {
                $listaPais[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listaPais;
        }

        if ($nombreFormType == 'anexos15A') {
            $listas = ConceptosAnexos15::find()
                        ->select("conceptoAnexo15C as label, idConceptoAnexo15 as value")
                        ->orderBy("idConceptoAnexo15")
                        ->asArray()->all();
            $listaCon = [];
            foreach ($listas as $key => $value) {
                $listaCon[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listaCon;
        }

        if ($nombreFormType == 'anexos15B') {
            $listas = RequisitosAnexos15::find()
                        ->select("requisitoAnexo15C as label, idRequisitosAnexos15 as value")
                        ->orderBy("idRequisitosAnexos15")
                        ->asArray()->all();
            $listaReq = [];
            foreach ($listas as $key => $value) {
                $listaReq[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }

            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listaReq;
        }

        if ($nombreFormType == 'anexos18A') {

            $query = new \yii\db\Query();
            $listas = $query->from(['r' => 'renta'])
                ->select(['pc.idPucComercial','pc.codigoCuentaPuc','pc.nombreCuentaPuc'])
                ->innerJoin(['cp' => 'clientesPuc'],'`r`.`idCliente` = `cp`.`idCliente`')
                ->innerJoin(['pc'=>'pucComercial'],'`cp`.`idPucComercial` = `pc`.`idPucComercial`')
                ->andWhere('r.`idRenta` = '.$idRenta )
                ->all();

            $listaReq = [];
            foreach ($listas as $key => $value) {
                $listaReq[] = array('label' => $value['codigoCuentaPuc'].' - '.$value['nombreCuentaPuc'] , 'value' => intval($value['idPucComercial']) );
            }

            $formType['schema'][0]['fieldArray']['fieldGroup'][1]['templateOptions']['options'] = $listaReq;
        }

        if ($nombreFormType == 'anexos25A') {

            $listas = Anexos25AConceptos::find()
                        ->select("nombreAnexo25AConcepto as label, idAnexo25AConcepto as value")
                        ->orderBy("idAnexo25AConcepto")
                        ->asArray()->all();
            $listaCon = [];
            foreach ($listas as $key => $value) {
                $listaCon[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
            }
            $formType['schema'][0]['fieldArray']['fieldGroup'][3]['templateOptions']['options'] = $listaCon;
        }

        return $formType;
    }


    /**
    * Función que filtra los municipios o departamentos pendendiendo un parametro que llegue
    */
    public function actionListGeneralNivelGeo($request)
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

        $dep = [];
        $id = $request['id'];

        if( is_array( $id ) ){
            $dep = $id;
        }else{
            $dep[] = $id;
        }

        $data = [];

        switch ($request['option']) {
            case 'Departamentos':
                $model = NivelGeografico2::find()
                    ->where([ 'estadoNivelGeografico2' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idNivelGeografico1', $dep  ])
                ->all();

                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nomNivelGeografico2,
                        "value" => (int) $row->nivelGeografico2,
                    );
                }
            break;
            
            case 'Municipios':
                $model = NivelGeografico3::find()
                    ->where([ 'estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idNivelGeografico2', $dep  ])
                ->all();

                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nomNivelGeografico3,
                        "value" => (int) $row->nivelGeografico3,
                    );
                }
            break;

            case 'Usuarios':
                $model = User::find()
                    ->where([ 'status' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGdTrdDependencia', $dep  ])
                ->all();

                foreach ($model as $row) {
                    # Usuarios internos
                    if($row->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                        $data[] = array(
                            "label" => $row->userDetalles->nombreUserDetalles. ' ' . $row->userDetalles->apellidoUserDetalles.' - '.$row->userDetalles->cargoUserDetalles,
                            "value" => (int) $row->id,
                        );
                    }                    
                }
            break;

            case 'Subseries':
                $model = GdTrdSubseries::find()
                    ->where([ 'estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGdTrdSerie', $dep ])
                ->all();

                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nombreGdTrdSubserie,
                        "value" => (int) $row->idGdTrdSubserie,
                    );
                }
            break;
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

    /**
    * Función que filtra alguna entidad pendendiendo un parametro que llegue
    */
    public function actionListGeneralMultiFunction($request)
    {

        if (!empty($request)) {
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GET***//
        }

        $dep = [];
        $id = $request['id'];

        if( is_array( $id ) ){
            $dep = $id;
        }else{
            $dep[] = $id;
        }

        $data = [];

        switch ($request['option']) {
            case 'Departamentos':
                $model = NivelGeografico2::find()
                    ->where([ 'estadoNivelGeografico2' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idNivelGeografico1', $dep  ])
                ->all();

                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nomNivelGeografico2,
                        "value" => (int) $row->nivelGeografico2,
                    );
                }
            break;
            
            case 'Municipios':
                $model = NivelGeografico3::find()
                    ->where([ 'estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idNivelGeografico2', $dep  ])
                ->all();

                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nomNivelGeografico3,
                        "value" => (int) $row->nivelGeografico3,
                    );
                }
            break;

            case 'Usuarios':
                $model = User::find()
                    ->where([ 'status' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGdTrdDependencia', $dep  ])
                ->all();

                foreach ($model as $row) {
                    # Usuarios internos
                    if($row->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                        $data[] = array(
                            "label" => $row->userDetalles->nombreUserDetalles. ' ' . $row->userDetalles->apellidoUserDetalles.' - '.$row->userDetalles->cargoUserDetalles,
                            "value" => (int) $row->id,
                        );
                    }
                }
            break;

            case 'Subseries':
                $model = GdTrdSubseries::find()
                    ->where([ 'estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGdTrdSerie', $dep ])
                ->all();

                $data[] = ['label' => '-- Seleccione una opción --', 'value' => null];
                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nombreGdTrdSubserie,
                        "value" => (int) $row->idGdTrdSubserie,
                    );
                }
            break;

            case 'Pisos':
                $model = GaPiso::find()
                    ->where([ 'estadoGaPiso' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGaEdificio', $dep ])
                ->all();

                $data[] = ['label' => '-- Seleccione una opción --', 'value' => null];
                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->numeroGaPiso,
                        "value" => (int) $row->idGaPiso,
                    );
                }
            break;

            case 'Bodegas':
                $model = GaBodega::find()
                    ->where([ 'estadoGaBodega' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->andWhere(['IN', 'idGaPiso', $dep ])
                ->all();

                $data[] = ['label' => '-- Seleccione una opción --', 'value' => null];
                foreach ($model as $row) {
                    $data[] = array(
                        "label" => $row->nombreGaBodega,
                        "value" => (int) $row->idGaBodega,
                    );
                }
            break;
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

}
