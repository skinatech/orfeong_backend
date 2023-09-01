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
use api\components\HelperGenerateExcel;
use api\components\HelperLoads;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperFiles;

use api\models\CgProveedoresRegional;

use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\filters\auth\CompositeAuth;

use api\models\CgRegionales;
use api\models\GdTrdDependencias;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use PhpOffice\PhpSpreadsheet\IOFactory;
use yii\helpers\FileHelper;
use yii\web\UploadedFile;

use api\components\HelperQueryDb;

class RegionalesController extends Controller{

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
     * Lists all regionals.
     * @return mixed
     */
    public function actionIndex($request)
    {    
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            // El $request obtiene $response = ['filterOperation' => [["nivelGeografico2"=> 2,
            // "nivelGeografico1"=> 1, "idNivelGeografico3" =>1, ]] ];
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
        
            # Relacionamiento de las regionales
            $relationRegional =  CgRegionales::find();
                //->innerJoin('nivelGeografico3', ' nivelGeografico3 . nivelGeografico3  =  cgRegionales . idNivelGeografico3 ')
            $relationRegional = HelperQueryDb::getQuery('innerJoin', $relationRegional, 'nivelGeografico3', ['cgRegionales' => 'idNivelGeografico3', 'nivelGeografico3' => 'nivelGeografico3']);
                //->innerJoin('nivelGeografico2', ' nivelGeografico2 . nivelGeografico2  =  nivelGeografico3 . idNivelGeografico2 ')
            $relationRegional = HelperQueryDb::getQuery('innerJoin', $relationRegional, 'nivelGeografico2', ['nivelGeografico3' => 'idNivelGeografico2', 'nivelGeografico2' => 'nivelGeografico2']);    
                //->innerJoin('nivelGeografico1', ' nivelGeografico1 . nivelGeografico1  =  nivelGeografico2 . idNivelGeografico1 ');
            $relationRegional = HelperQueryDb::getQuery('innerJoin', $relationRegional, 'nivelGeografico1', ['nivelGeografico1' => 'nivelGeografico1', 'nivelGeografico2' => 'idNivelGeografico1']);

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {                   
                    case 'estadoCgRegional':
                        $relationRegional->andWhere(['IN', 'cgRegionales.'.$field, intval($value)]);                    
                    break; 
                    case 'fechaInicial':
                        $relationRegional->andWhere(['>=', 'creacionCgRegional', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationRegional->andWhere(['<=', 'creacionCgRegional', trim($value) . Yii::$app->params['timeEnd']]);
                    break;    
                    case 'nivelGeografico2':
                        $relationRegional->andWhere(['IN', 'nivelGeografico2.' . $field, $value]);
                    break; 
                    case 'nivelGeografico1':
                        $relationRegional->andWhere(['IN', 'nivelGeografico1.'. $field, $value]);
                    break;  
                    case 'idNivelGeografico3':
                        $relationRegional->andWhere(['IN', 'cgRegionales.'. $field, $value]);
                    break;  
                    default:
                        $relationRegional->andWhere([Yii::$app->params['like'], 'cgRegionales.' . $field, $value]);
                    break;              
                }                
            }

            # Orden descendente para ver los últimos registros creados
            $relationRegional->orderBy(['cgRegionales.idCgRegional' => SORT_DESC]); 

            # Limite de la consulta
            $relationRegional->limit($limitRecords);
            $modelRelation = $relationRegional->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $dataRelation) {  

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idCgRegional)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation->idCgRegional,
                    'regionalName'      => $dataRelation->nombreCgRegional,
                    'regionalInitials'  => $dataRelation->siglaCgRegional,
                    'country'           => $dataRelation->nivelGeografico3->nivelGeografico2->nivelGeografico1->nomNivelGeografico1,
                    'department'        => $dataRelation->nivelGeografico3->nivelGeografico2->nomNivelGeografico2, 
                    'municipality'      => $dataRelation->nivelGeografico3->nomNivelGeografico3,
                    'creacion'          => $dataRelation->creacionCgRegional,
                    'statusText'        => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoCgRegional],
                    'status'            => $dataRelation->estadoCgRegional,
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexRegional');
            
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


    /* Funcion que retorna la data de la regional */
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

        $id = $request['id'];
        $model = $this->findModel($id);
        
        # Información de la regional
        $data = [
            'idCgRegional' => $model->idCgRegional,
            'nombreCgRegional' => $model->nombreCgRegional,
            'siglaCgRegional'  => $model->siglaCgRegional,
            'idNivelGeografico1' => $model->nivelGeografico3->nivelGeografico2->nivelGeografico1->nivelGeografico1,
            'idNivelGeografico2' => $model->nivelGeografico3->nivelGeografico2->nivelGeografico2,
            'idNivelGeografico3' => $model->idNivelGeografico3,
        ];
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];

        return HelperEncryptAes::encrypt($response, true);       
    }


    /** Displays a single Cg Regionales model.
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
                Yii::$app->params['eventosLogText']['View'].' id: '.$id.' de la regional: '.$model->nombreCgRegional, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            //Retorna toda la información de la regional
            $data = [
                ['alias' => 'Nombre regional', 'value' => $model->nombreCgRegional],
                ['alias' => 'Sigla regional', 'value' => $model->siglaCgRegional],
                ['alias' => 'País', 'value' => $model->nivelGeografico3->nivelGeografico2->nivelGeografico1->nomNivelGeografico1],
                ['alias' => 'Departamento', 'value' => $model->nivelGeografico3->nivelGeografico2->nomNivelGeografico2],
                ['alias' => 'Municipio', 'value' => $model->nivelGeografico3->nomNivelGeografico3],
                ['alias' => 'Fecha creación', 'value' => $model->creacionCgRegional]
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
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


    /**
     * Crea un nuevo registro en el modelo Cg Regionales
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
                
                # Registro de la regional
                $modelRegional = new CgRegionales();
                $modelRegional->attributes = $request;

                # Se valida que el nombre de la regional no exista
                $validRegional = CgRegionales::find()
                    ->where(['nombreCgRegional' => $request['nombreCgRegional']])
                    ->andWhere(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo'] ])
                ->one();

                # Sino existe se agrega el registro
                if (empty($validRegional)){
                        
                    if(!$modelRegional->save()){
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRegional->getErrors());                        
                    }

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'duplicateRegional',[
                            'name' => $validRegional->nombreCgRegional
                        ])],                                            
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    #Consulta para obtener los datos para el log
                    $regional = CgRegionales::findOne(['nombreCgRegional' => $request['nombreCgRegional']]);

                    # Información de la data actual en string
                    $data = self::dataLog($regional);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgRegionales", //texto para almacenar en el evento
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
     * Updates an existing CgRegionales model.
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
                $dataOld = '';
                $dataNew = '';

                $transaction = Yii::$app->db->beginTransaction();                
                
                $modelRegional = $this->findModel($id);

                # Se obtiene la información del log anterior
                $dataOld = self::dataLog($modelRegional);

                # Atributos de la regional
                $modelRegional->attributes = $request;

                # Verificar si el nombre ya existe
                $validRegional = CgRegionales::find()
                    ->where(['nombreCgRegional' => $modelRegional->nombreCgRegional])                    
                    ->andWhere(['idNivelGeografico3' => $modelRegional->idNivelGeografico3])     
                    ->andWhere(['<>','idCgRegional', $id])               
                    ->andWhere(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo'] ])
                ->one();

                if(!is_null($validRegional)){

                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'duplicateUpdateRegional',[
                            'name' => $validRegional->nombreCgRegional,
                            'municipio' => $validRegional->nivelGeografico3->nomNivelGeografico3
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    if (!$modelRegional->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRegional->getErrors());
                    }                          

                    # Consulta para obtener la información actualizada de la regional para el log
                    $modelNew = CgRegionales::findOne(['nombreCgRegional' => $modelRegional->nombreCgRegional]);
                    $dataNew = self::dataLog($modelNew);  

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", de la tabla cgRegionales", //texto para almacenar en el evento
                        $dataOld, //data old string
                        $dataNew, //data string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

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


    /* Cambio de estado de la regional, cuando la dependencia no este activa
    * ni tenga usuarios activos, y cuando no tenga un proveedor asociado.
    */
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
            $valid = true;
            $regionalErr = '';
            $transaction = Yii::$app->db->beginTransaction();
            foreach ($request as $value) {
                
                
                $dataExplode = explode('|', $value);

                # Consulta de la regional
                $model = $this->findModel($dataExplode[0]);

                # Se valida si la regional esta activa en alguna dependencia y usuario
                $dependenceUser = GdTrdDependencias::find();
                    //->innerJoin('user', ' user . idGdTrdDependencia  =  gdTrdDependencias . idGdTrdDependencia ')
                    $dependenceUser = HelperQueryDb::getQuery('innerJoin', $dependenceUser, 'user', ['gdTrdDependencias' => 'idGdTrdDependencia', 'user' => 'idGdTrdDependencia']);
                    $dependenceUser = $dependenceUser->where(['gdTrdDependencias.idCgRegional' => $model->idCgRegional])
                    ->andWhere(['gdTrdDependencias.estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    //->andWhere(['user.status' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                # Se valida si la regional esta activa con la relación de proveedores
                $regionalProvider = CgProveedoresRegional::find();
                    //->innerJoin('cgProveedores', ' cgProveedores . idCgProveedor  =  cgProveedoresRegional . idCgProveedor ')
                    $regionalProvider = HelperQueryDb::getQuery('innerJoin', $regionalProvider, 'cgProveedores', ['cgProveedoresRegional' => 'idCgProveedor', 'cgProveedores' => 'idCgProveedor']);    
                    $regionalProvider = $regionalProvider->where(['cgProveedoresRegional.idCgRegional' => $model->idCgRegional, 'cgProveedores.estadoCgProveedor' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                # Si la regional no tiene ninguna relación anterior, se activará o inactivará
                if(count($dependenceUser) == 0 && count($regionalProvider) == 0){

                    if ($model->save()) {

                        if ($model->estadoCgRegional == Yii::$app->params['statusTodoText']['Activo']) {
                            $model->estadoCgRegional = Yii::$app->params['statusTodoText']['Inactivo'];
                        } else {
                            $model->estadoCgRegional = Yii::$app->params['statusTodoText']['Activo'];
                        }

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgRegional] . ', id: '. $model->idCgRegional.' de la regional: '.$model->nombreCgRegional,// texto para almacenar en el evento
                            [], //DataOld
                            [], //Data en string
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        
                        $dataResponse[] = array(
                            'id' => $model->idCgRegional, 
                            'status' => $model->estadoCgRegional, 
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgRegional], 
                            'idInitialList' => $dataExplode[1] * 1
                        );

                        if (!$model->save()) {

                            $transaction->rollBack();
            
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $model->getErrors(),
                                'dataEstatus' => $dataResponse,
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                    } 
                    //else {   
                    //     // $errors[] = $model->getErrors();                    
                    //     // $saveDataValid = false;   
                    // }

                } else {// Si existe información genera error y no inactiva la regional

                    # Si el cambio de estado es a Activo se salta la validacion
                    if($model->estadoCgRegional == Yii::$app->params['statusTodoText']['Inactivo']){                            

                        if ($model->estadoCgRegional == Yii::$app->params['statusTodoText']['Activo']) {
                            $model->estadoCgRegional = Yii::$app->params['statusTodoText']['Inactivo'];
                        } else {
                            $model->estadoCgRegional = Yii::$app->params['statusTodoText']['Activo'];
                        }
                        
                        if ($model->save()) {
                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                false,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgRegional] . ', id: '. $model->idCgRegional.' de la regional: '.$model->nombreCgRegional,// texto para almacenar en el evento
                                [], //DataOld
                                [], //Data en string
                                array() //No validar estos campos
                            );
                            /***    Fin log Auditoria   ***/
    
                            $dataResponse[] = array(
                                'id' => $model->idCgRegional, 
                                'status' => $model->estadoCgRegional, 
                                'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgRegional], 
                                'idInitialList' => $dataExplode[1] * 1
                            );
    
                        } else {
                            $errors[] = $model->getErrors();
                            $saveDataValid = false;
                        }

                    } else {

                        $dataResponse[] = array(
                            'id' => $model->idCgRegional, 
                            'status' => $model->estadoCgRegional, 
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgRegional], 
                            'idInitialList' => $dataExplode[1] * 1
                        );  
    
                        $valid = false;
                        $regionalErr = $model['nombreCgRegional'];
                    }

                }
            }
            
            # Error cuando la regional ya esta asignada
            if($valid == false){

                $transaction->rollBack();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorSaveStatusRegional',['regional' => $regionalErr])]],
                    'dataStatus' => $dataResponse,
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
                    'dataStatus' => $dataResponse,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
             
            }
            # Fin de evaluar respuesta de datos guardados #


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


    public function actionIndexList()
    {
        $dataList = [];
        $regionales = CgRegionales::find()->where(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo']])->orderBy(['nombreCgRegional' => SORT_ASC])->all();

        foreach ($regionales as $key => $region) {
            $dataList[] = array(
                "id" => $region->idCgRegional,
                "val" => $region->nombreCgRegional,
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
     * Finds the CgRegionales model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgRegionales the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgRegionales::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

     /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $model [Modelo de la tabla]
     * @return $dataLog [String] [Información del modelo]
     **/
    protected function dataLog($model){

        if(!is_null($model)){

            # Valores del modelo se utiliza para el log
            $labelModel = $model->attributeLabels();
            $dataLog = '';

            foreach ($model as $key => $value) {           
            
                switch ($key) {

                    case 'idNivelGeografico3':
                        $dataLog .= 'Id del municipio: '.$model->idNivelGeografico3.', ';
                        $dataLog .= 'Municipio: '.$model->nivelGeografico3->nomNivelGeografico3.', ';
                    break;  
                    case 'estadoCgRegional':
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


    /** Funcion que permite descargar la plantilla
     * del formato de carga masiva de regionales
     **/
    public function actionDownloadFormatRegional() {

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);   
            
        // Nombre del archivo para generar
        $fileName = 'formato-regionales-' . Yii::$app->user->identity->id . '.xlsx';

        # Planilla del formato regional
        $regionalFormat = HelperLoads::getRegionalFormat();    

        $generateExcel = HelperGenerateExcel::generarExcelV2('documentos', $fileName, $regionalFormat);
        $rutaDocumento = $generateExcel['rutaDocumento'];

        /* Enviar archivo en base 64 como respuesta de la petición **/
        if($generateExcel['status'] && file_exists($rutaDocumento)){

            $dataFile = base64_encode(file_get_contents($rutaDocumento));

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['DownloadFile'].' formato regionales', //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $fileName,
                'status' => 200,
                //'ruta' => $rutaDocumento,
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


    /**
     * Permite cargar el archivo .xlsx para la carga masiva de regionales, se establece el directorio
     * donde se va a guardar el archivo.
     * @return UploadedFile el archivo cargado
     */
    public function actionLoadMassiveFileRegional()
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

            //Ruta de ubicacion de la carpeta donde se almacena el excel
            $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomRegionalFolder'] . '/';

            // Verificar que la carpeta exista y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {

                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            }          

            //$id = $request['id'];
            $id = Yii::$app->user->identity->id;

            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar
            $nomArchivo = "tmp_carga_masiva_regionales_" . $id . "." . $fileUpload->extension;
            $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo);

            /*** Validar si el archivo fue subido ***/
            if ($uploadExecute) {

                return SELF::fileValidate($nomArchivo, $pathUploadFile); //LLamado a la funcion de validacion de archivo

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
                'data' => ['error' => [Yii::t('app', 'canNotUpFile')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }        
    }

    /** Función que permite la validacion del archivo cargado 
     * @param $nomArchivo [String] [Nombre del archivo temporal]
     * @param $ruta [string] [ruta del archivo]
     * @return [Retorna los errores de la validación del excel]
    */
    public function fileValidate($nomArchivo, $ruta)
    {
        $dataListErr = [];  //Listado de errores

        //ruta del archivo de excel
        $pathUploadFile = $ruta.$nomArchivo;

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
            $hojaActualArray = $hojaActual->rangeToArray("A1:C". $maxCell['row']);

            # Titulos de las columnas que se mostraran en los errores
            $title = array(
                'Nombre completo' => 'A', 'Sigla' => 'B', 'Municipio' => 'C',
            );

            $titleText = array(
                'Nombre completo', 'Sigla', 'Municipio'
            );

            // Cantidad de columnas en la cabecera
            $cantCabecera = count($title);

            /***
               Array de datos existentes en base de datos
            ***/
            # Listado de regionales
            $regional = [];
            $modelRegional = CgRegionales::findAll(['estadoCgRegional' =>  Yii::$app->params['statusTodoText']['Activo']]);
                foreach($modelRegional as $dataRegional){
                    $regional[$dataRegional['nombreCgRegional']] = $dataRegional['idCgRegional'];
                }
           
            # Listado de municipios
            $municipalities = [];
            $modelMunicipal = NivelGeografico3::findAll(['estadoNivelGeografico3' =>  Yii::$app->params['statusTodoText']['Activo']]);
                foreach($modelMunicipal as $dataMunicipal){
                    $municipalities[$dataMunicipal['nomNivelGeografico3']] = $dataMunicipal['nivelGeografico3'];
                }

            $fila = 1;

            //Arreglo de filas ignoradas
            $ignorarFilas = [];
            
            #Iteración de la hoja actual
            foreach ($hojaActualArray as $i => $row) {

                if($i == 0){

                    // Se recorre las columnas correspondientes al titulo del archivo
                    foreach ($row as $celda) {
                       
                        // Si el valor de la celda leida NO esta en el arreglo de los titulos se genera error de formato
                        if(!in_array($celda, $titleText)){                           
                            $dataListErr['Fila ' . $fila][] = Yii::t('app','dataListErrTitle');                            
                        }
                    }  

                } elseif($i > 0) {
                    
                    $nameRegionalCell   = $row[$this->numberByLetter('A')];
                    $siglaRegionalCell   = $row[$this->numberByLetter('B')]; 
                    $municipalityCell   = $row[$this->numberByLetter('C')];   

                    if(($nameRegionalCell == '') && ($siglaRegionalCell == '') && ($municipalityCell == '')
                    ){
                        $ignorarFilas[] = $i; 
                       // $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCells');

                    } else {  

                        # Se valida que el nombre de la regional no exista en D.B y cumpla su longitud
                        if (!is_null($nameRegionalCell) && $nameRegionalCell != '') {
                        
                            $nomColumna = $titleText[0];
                            $length = 80;

                            if (isset($regional[$nameRegionalCell])) {
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrNameExists', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $nameRegionalCell
                                ]);

                            } elseif(strlen($nameRegionalCell) > $length ){
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'fila'          => $fila,
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }

                        } else {
                            $nomColumna = $titleText[0];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna,
                            ]);
                        }

                        # Se valida que la sigla cumpla su longitud
                        if (!is_null($siglaRegionalCell) && $siglaRegionalCell != '') {
                        
                            $nomColumna = $titleText[1];
                            $length = 5;

                            if (isset($regional[$siglaRegionalCell])) {
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrNameExists', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $siglaRegionalCell
                                ]);

                            } elseif(strlen($siglaRegionalCell) > $length ){
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'fila'          => $fila,
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }

                        } else {
                            $nomColumna = $titleText[1];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna,
                            ]);
                        }

                        # Se valida que el nombre del municipio exista en D.B
                        if (!is_null($municipalityCell) && $municipalityCell != '') {

                            $nomColumna = $titleText[2];

                            if (!isset($municipalities[$municipalityCell])) {                                
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrNameNotExist', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $municipalityCell
                                ]);
                            } 

                        } else {
                            $nomColumna = $titleText[2];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }
                    }
                }

                $fila++;

            }
            /****** Fin Validación de archivo ******/

            /****** Procesar archivo sin errores ******/
            if (count($dataListErr) == 0) {

                return SELF::fileUploadDataBase($pathUploadFile, $ignorarFilas, $documento, $municipalities); //LLamado a la funcion de subida a la base de datos

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
     * @param $documento [Estructura del documento de excel]
     * @param $municipalities [Modelo del listado valido de municipios]
     * @return [Validaciones, errores y registros agregados a la D.B]
     **/
    public function fileUploadDataBase($pathUploadFile, $ignorarFilas = [], $documento, $municipalities)
    {  
        $indiceHoja = 0;
        $dataRegional = []; //Informacion para la tabla cgRegionales;

        $dataListErr = []; //Listado de errores
        $errors = [];
        $cantRegistros = 0;

        if (is_file($pathUploadFile)) {

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);
            $maxCell = $hojaActual->getHighestRowAndColumn();
            $hojaActualArray = $hojaActual->rangeToArray("A1:C". $maxCell['row']);

            $dataLogRegional = [];
            $transaction = Yii::$app->db->beginTransaction();

            # Iterar filas
            foreach ($hojaActualArray as $i => $row) {
                
                if(!in_array($i, $ignorarFilas)){                

                    if($i > 0 ){

                        $nameRegionalCell   = $row[$this->numberByLetter('A')];
                        $siglaRegionalCell   = $row[$this->numberByLetter('B')];  
                        $municipalityCell   = $row[$this->numberByLetter('C')];     

                        # Se construye el array para insertar los datos del excel
                        if (!is_null($nameRegionalCell) && $nameRegionalCell != '') {
                            $dataRegional['nombreCgRegional'] = $nameRegionalCell;
                        }

                        # Se construye el array para insertar los datos del excel
                        if (!is_null($siglaRegionalCell) && $siglaRegionalCell != '') {
                            $dataRegional['siglaCgRegional'] = $siglaRegionalCell;
                        }
                        
                        if (!is_null($municipalityCell)  && $municipalityCell != '') {
                            $dataRegional['idNivelGeografico3'] = $municipalities[$municipalityCell];
                        }                                         
                    }

                    // Se agregan los datos del excel
                    if (count($dataRegional) > 1) {
                        
                        #Crea el registro                        
                        $modelRegional = new CgRegionales();
                        $modelRegional->nombreCgRegional = (string) $dataRegional['nombreCgRegional'];
                        $modelRegional->siglaCgRegional  = (string) $dataRegional['siglaCgRegional'];
                        $modelRegional->idNivelGeografico3 = (int) $dataRegional['idNivelGeografico3'];

                        $dataLogRegional[] = $modelRegional;

                        if ($modelRegional->save()) {
                            $cantRegistros++;

                        } else {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $modelRegional->getErrors(),
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
                $dataOld = '';

                foreach ($dataLogRegional as $dataLog) {   
                    $dataOld = $dataOld.'-'.self::logDataRegional($dataLog);
                }
                
                /***    log Auditoria ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['LoadMassive'] .' en la tabla cgRegionales', // texto para almacenar en el evento
                    '', //data
                    $dataOld, //Dataold
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','messageNumberOfRecords',[
                        'cantidad' => $cantRegistros
                    ]),
                    'data' => [],
                    //'dataModule' => 'cgRegionales',
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
                'data' => ['error' => [Yii::t('app', 'fileCanNotBeProcessed')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    //Funcion para convertir las letras en numeros
    public function numberByLetter($letter) {

        $letterArray = array(
            'A' => 0,
            'B' => 1,
            'C' => 2,
        );

        return $letterArray[$letter];
    }

    protected static function filterTable($filterRequest, $table, $action, $tableName){       
        //Obtener campos para filtrar
        $filters = Yii::$app->params['filters']['CgRegionales'][$action][$tableName];        

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
        $filtersParams = Yii::$app->params['filters']['CgRegionales'][$action];

        $filters = [];

        //Recorre las tablas para obtener un array de filter plano;
        foreach($filtersParams as $table){
            foreach($table as $filter){
                $filters[] = $filter;
            }
        }   
        return $filters;
    }

        /**
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que recibe del servicio]
     */

    public static function logDataRegional($request){

        $data  = ' Nombre: '.$request['nombreCgRegional'];
        $data  = ' Sigla: '.$request['siglaCgRegional'];             
        $data .= ', Estado: '. 'Activo';
        $data .= ', Fecha creación: '. date('Y-m-d');

        return $data;
    }


}
