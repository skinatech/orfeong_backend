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

namespace api\modules\version1\controllers\gestionDocumental;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperUserMenu;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;

use api\modules\version1\controllers\correo\CorreoController;

use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\filters\auth\CompositeAuth;

use api\models\GdTrdDependencias;
use api\models\User;
use api\models\UserDetalles;
use api\models\GdTrd;
use api\models\CgRegionales;
use api\models\CgNumeroRadicado;

use api\components\HelperGenerateExcel;
use api\components\HelperLoads;
use api\components\HelperQueryDb;
use api\modules\version1\TrdDependenciaController;

class TrdDependenciasController extends Controller{
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
     * Lists all Dependencias models.
     * @return mixed
     */
    public function actionIndex($request) {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            // El $request obtiene 'filterOperation' => [["codigoGdTrdDependencia"=>"01010", "nombreGdTrdDependencia"=>"", "creacionGdTrdDependencia"=>"", "idCgRegional"=>2 ]]
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

            //Lista de dependencias
            $dataList = [];   
            $dataBase64Params = "";
            $dependenciasList = [];    
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
                        } elseif ($key == 'haveTrdActiva') {
                            if ($info !== null) {
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

            $camposSelect = [
                    'gdTrdDependencias.idGdTrdDependencia', 'gdTrdDependencias.codigoGdTrdDependencia', 'gdTrdDependencias.nombreGdTrdDependencia',
                    'gdTrdDependencias.creacionGdTrdDependencia', 'gdTrdDependencias.estadoGdTrdDependencia', 'gdTrdDependencias.estadoGdTrdDependencia',
                    'gdTrdDependencias.codigoGdTrdDepePadre', 
                    'cgRegionales.nombreCgRegional', 'cgRegionales.nombreCgRegional'
            ];

            $columnCount = HelperQueryDb::getColumnCount('gdTrd', 'idGdTrd', 'cantidad');

            // Consulta para relacionar la informacion de dependencias y obtener 100 registros, a partir del filtro
            $dependencias = (new \yii\db\Query())
                ->from('gdTrdDependencias')
                ->select(array_merge($camposSelect, [$columnCount]))
                ->groupBy($camposSelect);
                
                $dependencias = HelperQueryDb::getQuery('innerJoin', $dependencias, 'cgRegionales', ['cgRegionales' => 'idCgRegional', 'gdTrdDependencias' => 'idCgRegional']);
                $dependencias = HelperQueryDb::getQuery('leftJoin', $dependencias, 'gdTrd', ['gdTrd' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia'],
                    [
                        ['operador' => 'AND', 'type' => 'valueInput', 'tbl1' => 'gdTrd' , 'value1'=> 'estadoGdTrd', 'tbl2' => null,  'value2' => '10'],
                    ],
                );
            $dependencias = $dependencias; 
            
            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){

                switch ($field) {
                    case 'estadoGdTrdDependencia':
                        $dependencias->andWhere(['IN', 'gdTrdDependencias.'.$field , $value]);
                        break;
                    case 'codigoGdTrdDepePadre':
                        $dependencias->andWhere(['IN', 'gdTrdDependencias.'.$field , $value]);
                        break;
                    case 'idGdTrdDependencia':
                        $dependencias->andWhere(['IN', 'gdTrdDependencias.'.$field , $value]);
                        break;

                    case 'fechaInicial':
                        $dependencias->andWhere(['>=', 'gdTrdDependencias.creacionGdTrdDependencia', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $dependencias->andWhere(['<=', 'gdTrdDependencias.creacionGdTrdDependencia', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    case 'haveTrdActiva':
                        if ($value == 10) {
                            $dependencias->andWhere(['IS NOT', 'gdTrd.idGdTrdDependencia' , NULL]);
                        } else {
                            $dependencias->andWhere(['IS', 'gdTrd.idGdTrdDependencia' , NULL]);
                        }
                    break;
                    default:
                        $dependencias->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                }                
            }                
            //Limite de la consulta
            $dependencias->orderBy(['idGdTrdDependencia' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $dependencias->limit($limitRecords);
            $modelDependencias = $dependencias->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelDependencias = array_reverse($modelDependencias);

            foreach ($modelDependencias as $dependencia) {

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dependencia['idGdTrdDependencia'])),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dependencia['codigoGdTrdDependencia']))
                );

                if( is_null($dependencia['codigoGdTrdDepePadre']) || $dependencia['codigoGdTrdDepePadre'] == 0 ) { 
                    $data_padre = ''; 
                }else{ 

                    $depePadre = GdTrdDependencias::find()->where([ 'idGdTrdDependencia' => $dependencia['codigoGdTrdDepePadre']])->one();
                    
                    if(isset($depePadre['nombreGdTrdDependencia'])){
                        $data_padre = $depePadre['nombreGdTrdDependencia'];
                    }else{
                        $data_padre = '';
                    }
                    
                }

                $dataList[] = array(

                    'data' => $dataBase64Params,
                    'id' => $dependencia['idGdTrdDependencia'],
                    'codigoGdTrdDependencia' => $dependencia['codigoGdTrdDependencia'],
                    'nombreGdTrdDependencia' => $dependencia['nombreGdTrdDependencia'],
                    'creacionGdTrdDependencia' => $dependencia['creacionGdTrdDependencia'],
                    'nombreCgRegional' => $dependencia['nombreCgRegional'],
                    'codigoGdTrdDepePadre' => $data_padre,
                    'haveTrdActiva' => ((int) $dependencia['cantidad'] > 0) ? 'Sí' : 'No',
                    'statusText' => Yii::$app->params['statusTodoNumber'][$dependencia['estadoGdTrdDependencia']],
                    'status' => $dependencia['estadoGdTrdDependencia'],
                    'rowSelect' => false,
                    'idInitialList' => 0
                );


            }
            
            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexDependencias');

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
        
        // if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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
                'nombreGdTrdDependencia' => $model['nombreGdTrdDependencia'],
                'codigoGdTrdDependencia' => $model['codigoGdTrdDependencia'],
                'codigoGdTrdDepePadre' => $model['codigoGdTrdDepePadre'],
                'estadoGdTrdDependencia' => $model['estadoGdTrdDependencia'],
                'creacionGdTrdDependencia' => $model['creacionGdTrdDependencia'],
                'idCgRegional' => $model['idCgRegional'],
            ];

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app', 'accessDenied')[1],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }
    }

    public function actionIndexList()
    {
        $dataList = [];
        $dependencias = GdTrdDependencias::find()
            ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
            ->all();

        foreach ($dependencias as $key => $dependencia) {
            $dataList[] = array(
                "id" => $dependencia->idGdTrdDependencia,
                "val" => $dependencia->codigoGdTrdDependencia .' - '.$dependencia->nombreGdTrdDependencia,
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
    public function actionView($request){

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

            $trdActiva = GdTrd::find()->select(['versionGdTrd'])
                ->where([
                    'idGdTrdDependencia' => $model->idGdTrdDependencia,
                    'estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']
                ])->orderBy(['creacionGdTrd' => SORT_DESC])->limit(1)->one();


            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'la dependencia: '.$model->nombreGdTrdDependencia.' y su código: '.$model->codigoGdTrdDependencia, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            if( is_null($model->codigoGdTrdDepePadre) || $model->codigoGdTrdDepePadre == 0 ) { 
                $data_padre = ''; 
            }else{
                
                $depePadre = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $model->codigoGdTrdDepePadre]) ->one();
                $data_padre = $depePadre->nombreGdTrdDependencia;
            }

            //Retorna el nombre de tipo documental
            $data[] = array('alias' => 'Código dependencia', 'value' => $model->codigoGdTrdDependencia);
            $data[] = array('alias' => 'Nombre', 'value' => $model->nombreGdTrdDependencia);
            $data[] = array('alias' => 'Unidad administrativa', 'value' => $data_padre );
            $data[] = array('alias' => 'Regional', 'value' => $model->cgRegional->nombreCgRegional);
            $data[] = array('alias' => 'Observación', 'value' => $model->observacionGdTrdDependencia);
            $data[] = array('alias' => 'Consecutivo del expediente', 'value' => $model->consecExpedienteGdTrdDependencia);
            if ($trdActiva != null) {
                $data[] = array('alias' => 'Versión TRD activa', 'value' => $trdActiva->versionGdTrd);
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

                /** Validar longitud del código de dependencia */
                $modelNumeroRadicado = CgNumeroRadicado::find()->select(['longitudDependenciaCgNumeroRadicado'])
                    ->where(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();
                if ($modelNumeroRadicado == null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => [Yii::t('app', 'errConfigRadicado')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    $request['codigoGdTrdDependencia'] = trim((string) $request['codigoGdTrdDependencia']);
                    if ( 
                        strlen( $request['codigoGdTrdDependencia'] ) < 3 ||
                        strlen( $request['codigoGdTrdDependencia'] ) > $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado
                    ) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => [Yii::t('app', 'errLongitudDependencia', ['min' => 3, 'max'=> $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
                /** Fin Validar longitud del código de dependencia */

                $transaction = Yii::$app->db->beginTransaction();
                $data = '';
                unset($request['idGdTrDependencia']);            

                $modelGdDependecias = new GdTrdDependencias();
                $modelGdDependecias->attributes = $request;
                $modelGdDependecias->toUpperCaseCodigo();

                //Verificar si el nombre ya existe
                $modelExistente = GdTrdDependencias::find()
                    ->where(['nombreGdTrdDependencia' => $modelGdDependecias->nombreGdTrdDependencia])
                    ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->one();

                if(!empty($modelExistente)){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nomDepDuplicated').' '.$modelExistente->nombreGdTrdDependencia],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //fin de verificación de nombre existente
                
                //Verificar si el codigo ya existe
                $modelExistente = GdTrdDependencias::find()
                    ->where(['codigoGdTrdDependencia' => $modelGdDependecias->codigoGdTrdDependencia])
                    ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->all();

                if(!empty($modelExistente)){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'depDuplicated').' '.$modelGdDependecias->codigoGdTrdDependencia ],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //fin de verificación de codigo existente

                if ($modelGdDependecias->save()) {

                    $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelGdDependecias->idGdTrdDependencia]);
                    $unidadAdministrativa = GdTrdDependencias::findOne(['codigoGdTrdDepePadre' => $dependencia->codigoGdTrdDepePadre]);
                    $regional = CgRegionales::findOne(['idCgRegional' => $dependencia->idCgRegional]);
                    
                    if($unidadAdministrativa){
                        $nombre = $unidadAdministrativa->nombreGdTrdDependencia;
                    }else{
                        $nombre = 'Sin asignar';
                    }

                    $data = 'Id Dependencia: '.$dependencia->idGdTrdDependencia;
                    $data .= ', Código Dependencia: '.$dependencia->codigoGdTrdDependencia;
                    $data .= ', Nombre Dependencia: '.$dependencia->nombreGdTrdDependencia;
                    $data .= ', Unidad Administrativa: '.$nombre; 
                    $data .= ', Regional: '.$regional->nombreCgRegional;               
                    $data .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$dependencia->estadoGdTrdDependencia];
                    $data .= ', Fecha creación: '.date("Y-m-d H:i:s", strtotime($dependencia->creacionGdTrdDependencia));
                    
                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['crear'] . ", en la tabla gdTrdDependencias", //texto para almacenar en el evento
                            '', // data old
                            $data, //Data
                            array() //No validar estos campos
                        );     
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelGdDependecias,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelGdDependecias->getErrors(),
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
               
                $modelDependencias = $this->findModel($id);
                $unidadAdministrativa = GdTrdDependencias::findOne(['codigoGdTrdDepePadre' => $modelDependencias->codigoGdTrdDepePadre]);
                $regional = CgRegionales::findOne(['idCgRegional' => $modelDependencias->idCgRegional]);
                    
                if($unidadAdministrativa){
                    $nombre = $unidadAdministrativa->nombreGdTrdDependencia;
                }else{
                    $nombre = 'Sin asignar';
                }

                $modelDependenciasOld = 'Id Dependencia: '.$modelDependencias->idGdTrdDependencia;
                $modelDependenciasOld .= ', Código Dependencia: '.$modelDependencias->codigoGdTrdDependencia;
                $modelDependenciasOld .= ', Nombre Dependencia: '.$modelDependencias->nombreGdTrdDependencia;
                $modelDependenciasOld .= ', Unidad Administrativa: '.$nombre;
                $modelDependenciasOld .= ', Regional: '.$regional->nombreCgRegional;
                $modelDependenciasOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelDependencias->estadoGdTrdDependencia];

                $modelDependencias->attributes = $request;

                //Verificar si el nombre ya existe
                $modelExistente = GdTrdDependencias::find()
                    ->where(['nombreGdTrdDependencia' => $modelDependencias->nombreGdTrdDependencia])
                    ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->one();

                if(!empty($modelExistente) &&
                    $modelExistente->idGdTrdDependencia != $modelDependencias->idGdTrdDependencia &&
                    $modelDependencias->estadoGdTrdDependencia == Yii::$app->params['statusTodoText']['Activo']){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nomDepDuplicated').' '.$modelExistente->nombreGdTrdDependencia],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //fin de verificación de nombre existente
                
                //Verificar si el codigo ya existe
                $modelExistente = GdTrdDependencias::find()
                    ->where(['codigoGdTrdDependencia' => $modelDependencias->codigoGdTrdDependencia])
                    ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo'] ])
                    ->one();

                if(!empty($modelExistente) &&
                        $modelExistente->idGdTrdDependencia != $modelDependencias->idGdTrdDependencia &&
                        $modelDependencias->estadoGdTrdDependencia == Yii::$app->params['statusTodoText']['Activo']
                    ){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'depDuplicated').' '.$modelDependencias->codigoGdTrdDependencia ],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //Fin de la validacion  de nombre existe


                if ($modelDependencias->save()) {

                    $dependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelDependencias->idGdTrdDependencia]);
                    $unidadAdministrativa = GdTrdDependencias::findOne(['codigoGdTrdDepePadre' => $dependencia->codigoGdTrdDepePadre]);
                    $regional = CgRegionales::findOne(['idCgRegional' => $dependencia->idCgRegional]);
                    
                    if($unidadAdministrativa){
                        $nombre = $unidadAdministrativa->nombreGdTrdDependencia;
                    }else{
                        $nombre = 'Sin asignar';
                    }

                    $dataDependencias = 'Id Dependencia: '.$dependencia->idGdTrdDependencia;
                    $dataDependencias .= ', Código Dependencia: '.$dependencia->codigoGdTrdDependencia;
                    $dataDependencias .= ', Nombre Dependencia: '.$dependencia->nombreGdTrdDependencia;
                    $dataDependencias .= ', Unidad Administrativa: '.$nombre;
                    $dataDependencias .= ', Regional: '.$regional->nombreCgRegional;  
                    $dataDependencias .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$dependencia->estadoGdTrdDependencia];

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla gdTrdDependecias", //texto para almacenar en el evento
                            $modelDependenciasOld, //DataOld
                            $dataDependencias, //Data
                            array() //No validar estos campos
                        );                    
                    /***    Fin log Auditoria   ***/

                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $modelDependencias,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelDependencias->getErrors(),
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
            $dataResponseValidicion = [];
            $arrayDepeTrdInactivas = [];
            $transaction = Yii::$app->db->beginTransaction();


            foreach ($request as $value) {

                $dataExplode = explode('|', $value);
                $id = $dataExplode[0];
                // Busca el registro
                $model = self::findModel($id);
                $dataResponseValidicion[] = array('id' => $model->idGdTrdDependencia, 'status' => $model->estadoGdTrdDependencia, 'statusText' => Yii::t('app','statusTodoNumber')[$model->estadoGdTrdDependencia], 'idInitialList' => $dataExplode[1] * 1);

                $estadoModel = $model->estadoGdTrdDependencia;
                $estado = $estadoModel;

                if($estadoModel == yii::$app->params['statusTodoText']['Activo']){

                    # validacion en caso de dependencia activa y con usuarios
                    if($this->checkUsersStatus($id)){
                        $estado = Yii::$app->params['statusTodoText']['Inactivo'];
                    }else{

                        $saveDataValid = false;
                        $errors[] = $model->nombreGdTrdDependencia.' - '.Yii::t('app', 'usersActived');
                        $dataResponse[] = array('id' => $model->idGdTrdDependencia, 'status' => $model->estadoGdTrdDependencia, 'statusText' => Yii::t('app','statusTodoNumber')[$model->estadoGdTrdDependencia], 'idInitialList' => $dataExplode[1] * 1);
                    }

                }else{
                
                    # validacion si existen mas registros con el mismo codigo de dependencia
                    $dependencias = GdTrdDependencias::findAll(['codigoGdTrdDependencia' => $model->codigoGdTrdDependencia]);

                    foreach($dependencias as $depe){
                        if($id != $depe->idGdTrdDependencia && $depe->estadoGdTrdDependencia == yii::$app->params['statusTodoText']['Activo']){
                            $saveDataValid = false;   
                        }
                    }

                    if($saveDataValid == false){

                        $errors[] = Yii::t('app', 'depeActive',[ 'dependencia' => $model->codigoGdTrdDependencia."-".$model->nombreGdTrdDependencia]);
                        $dataResponse[] = array('id' => $model->idGdTrdDependencia, 'status' => $model->estadoGdTrdDependencia, 'statusText' => Yii::t('app','statusTodoNumber')[$model->estadoGdTrdDependencia], 'idInitialList' => $dataExplode[1] * 1);
                            
                    }else{
                        
                        $estado = yii::$app->params['statusTodoText']['Activo'];
                    }
                }               

                if($estado == yii::$app->params['statusTodoText']['Inactivo']){

                    $modelTRD = GdTrd::findOne(['idGdTrdDependencia' => $model->idGdTrdDependencia]);
                    
                    if($modelTRD){
                        TrdTmpController::desactivarTrd($model->idGdTrdDependencia);
                    }                   
                } else {
                    # Validar mensaje si la dependencia a activar no tiene TRD activa
                    $trdActiva = GdTrd::find()->select(['versionGdTrd'])
                        ->where([
                            'idGdTrdDependencia' => $model->idGdTrdDependencia,
                            'estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']
                        ])->orderBy(['creacionGdTrd' => SORT_DESC])->limit(1)->one();

                    if ($trdActiva == null) {
                        $arrayDepeTrdInactivas[] = $model->codigoGdTrdDependencia;
                    }

                }

                $model->estadoGdTrdDependencia = $estado;

                if($model->save()) {  
                    
                    $dataResponse[] = array('id' => $model->idGdTrdDependencia, 'status' => $model->estadoGdTrdDependencia, 'statusText' => Yii::t('app','statusTodoNumber')[$model->estadoGdTrdDependencia], 'idInitialList' => $dataExplode[1] * 1 );

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoGdTrdDependencia] . ', id: '. $model->idGdTrdDependencia.' de la dependencia: '.$model->nombreGdTrdDependencia,// texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                
                } else {
                    $errors[] = $model->getErrors();
                    $saveDataValid = false;
                }


            }

            // Valida si hay errores envia un error por cada dependencia
            if( $saveDataValid == true ){

                if (count($arrayDepeTrdInactivas) == 0) {
                    $message = Yii::t('app', 'successChangeStatus');
                } elseif (count($arrayDepeTrdInactivas) == 1) {
                    $message = Yii::t('app', 'successChangeStatusDepeWithoutTrdOne', ['codigoDependencia'  => implode(', ', $arrayDepeTrdInactivas)]);
                } else {
                    $message = Yii::t('app', 'successChangeStatusDepeWithoutTrdMulti', ['codigosDependencias'  => implode(', ', $arrayDepeTrdInactivas)]);
                }
                
                $transaction->commit();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $message,
                    'data' => $dataResponse,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
                
            }else{

                $transaction->commit();
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $errors,
                    'dataStatus' => $dataResponseValidicion,
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

    protected function checkUsersStatus($idDependencia)
    {       
        $user = User::find()->where(['idGdTrdDependencia' => $idDependencia])->all();
        foreach($user as $userItem){
            if($userItem->status == Yii::$app->params['statusTodoText']['Activo']){
                return false;
            }            
        }        
        return true;    
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
        if (($model = GdTrdDependencias::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    protected static function filterTable($filterRequest, $table, $action, $tableName)
    {       
        //Obtener campos para filtrar
        $filters = Yii::$app->params['filters']['gestionDocumental/TrdDependenciasController'][$action][$tableName];        

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
    
    /** Funcion que permite descargar el cuadro
     * de clasificacion documental
     **/
    public function actionDownloadClassification() 
    {

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);        
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            //$response = ['id' => [7,2] ];
            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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

                $idsDependencia = $request['id'];

                // Recorre los Ids que llegan para que se vean reflejados en el log 
                $idDepeLog = '';
                foreach ($idsDependencia as $value) {
                    $idDepeLog = $value.', '.strval($idDepeLog);
                }
                
                // Nombre del archivo para generar
                $fileName = 'cuadro-de-clasificacion-documental-' . Yii::$app->user->identity->id . '.xlsx';

                /** Llamado de función para obtener la data de la dependencia seleccionada */
                $cuadroDocumental = HelperLoads::getCuadroDocumental($idsDependencia);

                if($cuadroDocumental['status'] == 'OK'){
                    $generateExcel = HelperGenerateExcel::generarExcel('cuadro_documental', $fileName, $cuadroDocumental['arrayExcel'], $array = [], $cuadroDocumental['mergeCells'], $cuadroDocumental['arrayTitulos'], $cuadroDocumental['arrayCabeceras'], $cuadroDocumental['arrayCuerpo'], true);
                
                }else{
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'trdDoesntExist')],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                if($generateExcel['status'] == false){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $cuadroDocumental['message'], 
                        'data' => $cuadroDocumental['errors'],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $rutaCuadro = $generateExcel['rutaDocumento'];
                }
                
                /* Enviar archivo en base 64 como respuesta de la petición **/
                if(file_exists($rutaCuadro))
                {
                    $independienteInfoIds = [];
                    $independienteInfoNombres = [];

                    # Se obtiene el arreglo de los ids de la dependencia
                    $idsDependenciaLogs = implode(',', $idsDependencia); 

                    // $selectDependencia = GdTrdDependencias::find()->where(['IN','idGdTrdDependencia',$idsDependenciaLogs])->all();
                    $selectDependencia = GdTrdDependencias::find()->where(['IN', 'idGdTrdDependencia', $idsDependencia])->all(); // Se debe pasar un array, no el implode

                    // $selectDependencia = (new \yii\db\Query())
                    //     ->select(['dp.idGdTrdDependencia','dp.nombreGdTrdDependencia'])
                    //     ->from('gdTrdDependencias AS dp')
                    //     ->where('dp.idGdTrdDependencia IN ('. $idsDependenciaLogs .')')
                    //     ->all();

                    foreach ($selectDependencia as $key => $datoIndependiente) {

                        $independienteInfoIds[] = $datoIndependiente['idGdTrdDependencia'];
                        $independienteInfoNombres[] = $datoIndependiente['nombreGdTrdDependencia'];
                    }

                    $dataDependencias = 'Id dependencia: '.implode(', ',$independienteInfoIds);
                    $dataDependencias .= ', Nombre dependencia: '.implode(', ',$independienteInfoNombres);            

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['DownloadFile'].' de cuadro de clasificación documental (CCD): '.$dataDependencias, //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    //Lee el archivo dentro de una cadena en base 64
                    $dataFile = base64_encode(file_get_contents($rutaCuadro));
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data' => $fileName, //Se envia el nombre del archivo
                        'status' => 200,
                    ];
                    $return = HelperEncryptAes::encrypt($response, true);

                    // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                    $return['datafile'] = $dataFile;
                    return $return;

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'fileWithoutInformationClasification') ],                        
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
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

    public function getList(){
        $modelDependencias = GdTrdDependencias::find()->all();

        $dependenciasList = [];
        foreach($modelDependencias as $dependencia){
            $dependenciasList[] = [
                'label' => $dependencia->nombreGdTrdDependencia,
                'value' => $dependencia->idGdTrdDependencia
            ];
        }

        return $dependenciasList;
    }

    public function actionDependencias()
    {
        $dataDependencias = [];

        $modelDependencias = GdTrdDependencias::findAll(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']]);

        foreach ($modelDependencias as $row) {
            $dataDependencias[] = array(
                "id" => (int) $row['idGdTrdDependencia'],
                "val" => $row['codigoGdTrdDependencia'].' - '.$row['nombreGdTrdDependencia'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataDependencias' => $dataDependencias ?? [], // Array Informacion de la dependencia
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /** Obtener la configuración del sistema para límite de códigos... */
    public function actionGetConfig()
    {
        $data = [];

        $modelNumeroRadicado = CgNumeroRadicado::find()->select(['longitudDependenciaCgNumeroRadicado'])
            ->where(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']])
        ->one();

        $longitudDependencia = $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado;

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'longitudDependencia' => $longitudDependencia,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionFuncionarios()
    {

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
                
            $funcionarios = UserDetalles::find()->select(['`userDetalles`.`idUser`', '`userDetalles`.`nombreUserDetalles`', '`userDetalles`.`apellidoUserDetalles`', '`userDetalles`.`cargoUserDetalles`']);
                // ->innerJoin('user', '`user`.`id` = `userDetalles`.`idUser`')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'user', ['user' => 'id', 'userDetalles' => 'idUser']);
                // ->innerJoin('gdTrdDependencias', '`gdTrdDependencias`.`idGdTrdDependencia` = `user`.`idGdTrdDependencia`')
                $funcionarios = HelperQueryDb::getQuery('innerJoin', $funcionarios, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'user' => 'idGdTrdDependencia']);
                $funcionarios = $funcionarios->where(['`user`.`status`' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['`user`.`idGdTrdDependencia`' => $decrypted['dependencia']])
            ->all();

            if (isset($funcionarios)) {

                foreach ($funcionarios as $row) {
                    $datafuncionarios[] = array(
                        "id" => (int) $row['idUser'],
                        "val" => $row['nombreUserDetalles'] . ' ' . $row['apellidoUserDetalles'] . ' ' . $row['cargoUserDetalles'],
                    );
                }

            } else {

                $datafuncionarios[] = array(
                    "id" => "",
                    "val" => Yii::t('app', 'noDependenciesFound'),
                );

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'funcionarios' => $datafuncionarios ?? [],
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
    }

}
