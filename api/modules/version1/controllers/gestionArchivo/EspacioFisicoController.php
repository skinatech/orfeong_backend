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

namespace api\modules\version1\controllers\gestionArchivo;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperQueryDb;
use api\models\GaArchivo;
use api\models\GaBodega;
use api\models\GaBodegaContenido;
use api\models\GaEdificio;
use api\models\GaPiso;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;


/**
 * EspacioFisicoController implements the CRUD actions.
 */
class EspacioFisicoController extends Controller
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
                    'list-edificios' => ['GET'],                  
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
     * Lists all Espacio fisico models.
     * @return mixed
     */
    public function actionIndex($request)
    {    

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            // El $request obtiene $response = ['filterOperation' => [["idGaEdificio"=> 1,
            // "idGaPiso"=> [1,2] , ]] ];
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

            # Relacionamiento de espacios fisicos
            $relationSpace =  GaBodega::find();
                // ->innerJoin('gaPiso', 'gaPiso.idGaPiso = gaBodega.idGaPiso')
                $relationSpace = HelperQueryDb::getQuery('innerJoin', $relationSpace, 'gaPiso', ['gaPiso' => 'idGaPiso', 'gaBodega' => 'idGaPiso']);
                // ->innerJoin('gaEdificio', 'gaEdificio.idGaEdificio = gaPiso.idGaEdificio')
                $relationSpace = HelperQueryDb::getQuery('innerJoin', $relationSpace, 'gaEdificio', ['gaEdificio' => 'idGaEdificio', 'gaPiso' => 'idGaEdificio']);

                $relationSpace = HelperQueryDb::getQuery('innerJoin', $relationSpace, 'gaBodegaContenido', ['gaBodegaContenido' => 'idGaBodega', 'gaBodega' => 'idGaBodega']);

            
            $relationSpace = $relationSpace;

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {

                    case 'idGaEdificio':
                        $relationSpace->andWhere(['IN', 'gaEdificio.' . $field, $value]);
                    break;
                    case 'numeroGaPiso':
                        $relationSpace->andWhere(['IN', 'gaPiso.' . $field, intval($value)]);
                    break;
                    case 'nombreGaBodega':
                        $relationSpace->andWhere(['like', 'gaBodega.' . $field, $value]);
                    break;
                    case 'estadoGaBodega':
                        $relationSpace->andWhere(['IN', 'gaBodega.' . $field, intval($value) ]);
                    break;
                    case 'cantidadEstanteGaBodegaContenido':
                        $relationSpace->andWhere(['IN', 'gaBodegaContenido.' . $field, intval($value)]);
                    break;
                    case 'cuerpoGaBodegaContenido':
                        $relationSpace->andWhere(['like', 'gaBodegaContenido.' . $field, $value]);
                    break; 
                    case 'fechaInicial':
                        $relationSpace->andWhere(['>=', 'gaBodega.creacionGaBodega', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationSpace->andWhere(['<=', 'gaBodega.creacionGaBodega', trim($value) . Yii::$app->params['timeEnd']]);
                    break;                 
                }                
            }

            # Orden descendente para ver los últimos registros creados
            $relationSpace->orderBy(['gaBodega.idGaBodega' => SORT_DESC]); 

            # Limite de la consulta
            $relationSpace->limit($limitRecords);
            $modelRelation = $relationSpace->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);       

            foreach($modelRelation as $dataRelation) {  

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaBodega)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->nombreGaBodega))
                );

                $modelContent = GaBodegaContenido::findOne(['idGaBodega' => $dataRelation->idGaBodega]);

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation->idGaBodega,  
                    'buildingName'      => $dataRelation->idGaPiso0->idGaEdificio0->nombreGaEdificio,
                    'floorNumber'       => $dataRelation->idGaPiso0->numeroGaPiso,
                    'warehouseLocation' => $dataRelation->nombreGaBodega,
                    'shelf'             => ($modelContent != null) ? $modelContent->cantidadEstanteGaBodegaContenido :'',
                    'body'              => ($modelContent != null) ? $modelContent->cuerpoGaBodegaContenido :'',

                    'cantidadRackGaBodegaContenido' => ($modelContent != null) ? $modelContent->cantidadRackGaBodegaContenido : '',

                    'buildingCreation'  => $dataRelation->creacionGaBodega,
                    'statusText'        => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoGaBodega],
                    'status'            => $dataRelation->estadoGaBodega,
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                                             
            }
          
            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexEspacioFisico');
            
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


    /* Funcion que retorna la data del espacio fisico de un edificio */
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
        
        $modelWarehouse = $this->findModelBodega($id);
        $model = $this->findModel($modelWarehouse->idGaPiso0->idGaEdificio);
        $modelContent = GaBodegaContenido::findOne(['idGaBodega' => $id]);
        
        # Información del edificio
        $data = [
            'idGaEdificio' => $model->idGaEdificio,
            'nombreGaEdificio' => $model->nombreGaEdificio,
            'idDepartamentoGaEdificio' => $model->idDepartamentoGaEdificio,
            'idMunicipioGaEdificio' => $model->idMunicipioGaEdificio,
            'idGaPiso' => $modelWarehouse->idGaPiso0->idGaPiso,
            'numeroGaPiso' => $modelWarehouse->idGaPiso0->numeroGaPiso,
            'idGaBodega' => $modelWarehouse->idGaBodega,
            'nombreGaBodega' => $modelWarehouse->nombreGaBodega,
            'idGaBodegaContenido' => $modelContent->idGaBodegaContenido,
            'cantidadRackGaBodegaContenido' => $modelContent->cantidadRackGaBodegaContenido,
            'cantidadEstanteGaBodegaContenido' => $modelContent->cantidadEstanteGaBodegaContenido,
            'cantidadEntrepanoGaBodegaContenido' => $modelContent->cantidadEntrepanoGaBodegaContenido,
            'cantidadCajaGaBodegaContenido' => $modelContent->cantidadCajaGaBodegaContenido,
            'cuerpoGaBodegaContenido' => $modelContent->cuerpoGaBodegaContenido,
        ];
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];

        return HelperEncryptAes::encrypt($response, true);       
    }
    

    /** Displays a single Espacio fisico model.
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
            $dataList = [];

            # Model de la bodega
            $model = $this->findModelBodega($id);
            
            # Consulta el edificio y contenido de esa bodega
            $modelBuilding = GaEdificio::findOne(['idGaEdificio' => $model->idGaPiso0->idGaEdificio]);   
            $modelContent = GaBodegaContenido::findOne(['idGaBodega' => $model->idGaBodega]);         

            //Retorna toda la información del espacio fisico
            $dataList = [
                ['alias' => 'Nombre edificio', 'value' => $modelBuilding->nombreGaEdificio],
                ['alias' => 'Departamento', 'value' => $modelBuilding->idDepartamentoGaEdificio0->nomNivelGeografico2],
                ['alias' => 'Municipio', 'value' => $modelBuilding->idMunicipioGaEdificio0->nomNivelGeografico3],
                ['alias' => 'Fecha creación del edificio', 'value' => $modelBuilding->creacionGaEdificio],
                ['alias' => 'Piso', 'value' => $model->idGaPiso0->numeroGaPiso],
                ['alias' => 'Área de archivo', 'value' => $model->nombreGaBodega],
                ['alias' => 'Módulo', 'value' => $modelContent->cantidadRackGaBodegaContenido],
                ['alias' => 'Estante', 'value' => $modelContent->cantidadEstanteGaBodegaContenido],
                ['alias' => 'Entrepaño', 'value' => $modelContent->cantidadEntrepanoGaBodegaContenido],
                ['alias' => 'Caja', 'value' => $modelContent->cantidadCajaGaBodegaContenido],
                ['alias' => 'Cuerpo', 'value' => $modelContent->cuerpoGaBodegaContenido]
            ];

            /*** Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'id: '.$modelBuilding->idGaEdificio.' del edificio: '.$modelBuilding->nombreGaEdificio, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
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
     * Crea un nuevo registro en los modelos de Espacio fisico.
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
                $valid['statusSave'] = true;
                $valid2['statusSave'] = true;
                $errors = [];
                $dataNew = '';
                $idsWarehouse = [];

                $transaction = Yii::$app->db->beginTransaction();
                
                # Se agrega registro del edificio
                $modelBuilding = new GaEdificio();
                $modelBuilding->attributes = $request;
              
                # Se valida que el edificio no exista
                $validBuilding = GaEdificio::find()
                    ->where(['nombreGaEdificio' => $request['nombreGaEdificio']])
                    ->andWhere(['estadoGaEdificio' => Yii::$app->params['statusTodoText']['Activo'] ])
                ->one();
               
                # Sino existe se agrega el registro del edificio
                if (empty($validBuilding)){

                    # Crea el edificio
                    $valid = $this->createBuilding($saveDataValid, $errors, $modelBuilding);

                    # Registros del piso
                    foreach($request['numeroGaPiso'] as $floor => $dataWarehouse) {

                        # Valida que el array de la posicion del piso no venga vacio 
                        if ( is_array($dataWarehouse)) {

                            # Verifica si el piso ya existe
                            $validFloor = GaPiso::find()
                                ->where(['idGaEdificio' => $modelBuilding->idGaEdificio])
                                ->andWhere(['numeroGaPiso' => $floor])
                                ->andWhere(['estadoGaPiso' => Yii::$app->params['statusTodoText']['Activo'] ])
                            ->one();  

                            # Sino existe se agrega el piso, la bodega y su contenido
                            if(empty($validFloor)){

                                # Crea el piso
                                $valid = $this->createFloor($saveDataValid, $errors, $modelBuilding, $floor);

                                # Registros de bodega y su contenido
                                foreach($dataWarehouse['nombreGaBodega'] as $nameWarehouse => $dataContent){

                                    # Crea la bodega y su contenido
                                    $valid2 = $this->createWarehouse($saveDataValid, $errors, $valid['modelFloor'], $nameWarehouse, $dataContent);

                                    # ids de la bodega
                                    $idsWarehouse[] = $valid2['idWarehouse'];
                                }

                            } else { // Si existe el piso valida la bodega

                                # Registros de bodega y su contenido
                                foreach($dataWarehouse['nombreGaBodega'] as $nameWarehouse => $dataContent){

                                    $validSpace = $this->errorSpace($transaction, $nameWarehouse, $validFloor, $modelBuilding);

                                    # Si es true retorna el error
                                    if($validSpace){
                                        return $validSpace;

                                    } else {

                                        # Sino existe la bodega la crea junto con su contenido
                                        $valid2 = $this->createWarehouse($saveDataValid, $errors, $validFloor, $nameWarehouse, $dataContent);

                                        # ids de la bodega
                                        $idsWarehouse[] = $valid2['idWarehouse'];
                                    }                                 
                                }    
                            }
                        }
                    }

                } else { // Si existe el edificio se valida la relación con el piso

                    # Registros del piso
                    foreach($request['numeroGaPiso'] as $floor => $dataWarehouse) {

                        # Valida que el array de la posicion del piso no venga vacio 
                        if ( is_array($dataWarehouse)) {
                          
                            # Verifica si el piso ya existe
                            $validFloor = GaPiso::find()
                                ->where(['idGaEdificio' => $validBuilding->idGaEdificio])
                                ->andWhere(['numeroGaPiso' => $floor])
                                ->andWhere(['estadoGaPiso' => Yii::$app->params['statusTodoText']['Activo'] ])
                            ->one();                           
                           
                            # Sino existe se agrega el piso, la bodega y su contenido
                            if(empty($validFloor)){

                                # Crea el piso
                                $valid = $this->createFloor($saveDataValid, $errors, $validBuilding, $floor);

                                # Registros de bodega y su contenido
                                foreach($dataWarehouse['nombreGaBodega'] as $nameWarehouse => $dataContent){

                                    # Crea la bodega y su contenido
                                    $valid2 = $this->createWarehouse($saveDataValid, $errors, $valid['modelFloor'], $nameWarehouse, $dataContent);

                                    #ids de la bodega
                                    $idsWarehouse[] = $valid2['idWarehouse'];
                                }

                            } else { // Si existe el piso se valida la relación con la bodega
                                
                                # Registros de bodega y su contenido
                                foreach($dataWarehouse['nombreGaBodega'] as $nameWarehouse => $dataContent){

                                    $validSpace = $this->errorSpace($transaction, $nameWarehouse, $validFloor, $validBuilding);

                                    # Si es true retorna el error
                                    if($validSpace){
                                        return $validSpace;

                                    } else {
                                        
                                        # Sino existe la bodega la crea junto con su contenido
                                        $valid2 = $this->createWarehouse($saveDataValid, $errors, $validFloor, $nameWarehouse, $dataContent);                         

                                        #ids de la bodega
                                        $idsWarehouse[] = $valid2['idWarehouse'];
                                    }
                                }
                            }
                        }
                    }                 
                }                

                # Evaluar respuesta de datos guardados #
                if ($valid['statusSave'] == true || $valid2['statusSave'] == true) {

                    $transaction->commit();

                    # Consulta el modelo de la bodega para el log
                    $modelNew = GaBodega::findAll(['idGaBodega' => $idsWarehouse]);

                    foreach($modelNew as $data){

                        # Consulta para obtener la información creada para el log
                        $dataNew = self::dataLog($data); 

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['crear'] . ", en la tabla de GaEdificio, GaPiso, GaBodega y GaBodegaContenido", //texto para almacenar en el evento
                            '', // DataOld
                            $dataNew, //Data en string
                            array() //No validar estos campos
                        ); 
                        /***    Fin log Auditoria   ***/
                    }

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
     * Función que crea el edificio
     * @param $saveDataValid [boolean] [true]
     * @param $errors [array]
     * @param $modelBuilding [model] [Modelo del edificio] 
     * @return $valid [boolean] [Verifica si se almaceno correctamente el edificio]
     **/
    protected function createBuilding($saveDataValid, $errors, $modelBuilding){

        # Se almacena el edificio
        if (!$modelBuilding->save()) {
            $saveDataValid = false;
            $errors = array_merge($errors, $modelBuilding->getErrors());
        }

        $valid = [
            'statusSave' => $saveDataValid,
        ];

        return $valid;
    }

    /**
     * Función que crea el piso
     * @param $saveDataValid [boolean] [true]
     * @param $errors [array]
     * @param $modelBuilding [model] [Modelo del edificio] 
     * @param $floor [int] [Número de piso] 
     * @return $valid [boolean, model] [Verifica si se almaceno correctamente el piso y retorna modelo del piso]
     **/
    protected function createFloor($saveDataValid, $errors, $modelBuilding, $floor){

        # Se almacena el piso
        $modelFloor = new GaPiso();
        $modelFloor->idGaEdificio = $modelBuilding->idGaEdificio;
        $modelFloor->numeroGaPiso = $floor;

        if (!$modelFloor->save()) {
            $saveDataValid = false;
            $errors = array_merge($errors, $modelFloor->getErrors());            
        }

        $valid = [
            'modelFloor' => $modelFloor,
            'statusSave' => $saveDataValid,
        ];

        return $valid;
    }

    /**
     * Función que crea la bodega y su contenido
     * @param $saveDataValid [boolean] [true]
     * @param $errors [array]
     * @param $modelFloor [model] [Modelo del piso] 
     * @param $nameWarehouse [string] [Nombre de la bodega] 
     * @param $dataContent [array] [Contenido de la bodega] 
     * @return $valid [boolean, idWarehouse] [Verifica si se almaceno correctamente el bodega y retorna id de la bodega]
     **/
    protected function createWarehouse($saveDataValid, $errors, $modelFloor, $nameWarehouse, $dataContent){

        $idWarehouse = ''; 

        # Creación de registro de la bodega
        $modelWarehouse = new GaBodega();
        $modelWarehouse->idGaPiso = $modelFloor->idGaPiso;
        $modelWarehouse->nombreGaBodega = (String) $nameWarehouse;
        
        if(!$modelWarehouse->save()){
            $saveDataValid = false;
            $errors = array_merge($errors, $modelWarehouse->getErrors());
        }

        # id de la bodega que fueron creados
        $idWarehouse =  $modelWarehouse->idGaBodega;

        # Creación de registro del contenido de la bodega
        $modelContent = new GaBodegaContenido();
        $modelContent->attributes = $dataContent;
        $modelContent->idGaBodega = $modelWarehouse->idGaBodega;

        if(!$modelContent->save()){
            $saveDataValid = false;
            $errors = array_merge($errors, $modelContent->getErrors());
        }

        $valid = [
            'idWarehouse' => $idWarehouse,
            'statusSave' => $saveDataValid,
        ];

        return $valid;
    }

    /**
     * Función que valida la existencia de la bodega
     * @param $transaction [Declaración de variable]
     * @param $nameWarehouse [string] [Nombre de la bodega] 
     * @param $validFloor [model] [Modelo del piso] 
     * @param $modelBuilding [model] [Modelo del edificio] 
     * @return [boolean] [Si es true retorna error, sino retorna 'false']
     **/
    public function errorSpace($transaction, $nameWarehouse, $validFloor, $modelBuilding){

        # Verifica si la bodega existe
        $validWarehouse = GaBodega::find()
            ->where(['gaBodega.nombreGaBodega' => $nameWarehouse])
            ->andWhere(['gaBodega.idGaPiso' => $validFloor->idGaPiso])
            ->andWhere(['gaBodega.estadoGaBodega' => Yii::$app->params['statusTodoText']['Activo']])
        ->one();

        if(!empty($validWarehouse)) {

            $transaction->rollBack();

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => [ 'error' => Yii::t('app', 'duplicateAll',[
                    'name' => $validWarehouse->nombreGaBodega,
                    'number' => $validFloor->numeroGaPiso,
                    'edificio' => $modelBuilding->nombreGaEdificio 
                ])],                                            
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            return false;
        }
        # Fin de verificación de bodega existente
    }


    /**
     * Updates an existing Espacio fisico model.
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

                $idChangePiso = null;
                $idPisoDelete = null;

                $transaction = Yii::$app->db->beginTransaction();                
                
                $modelWarehouse = $this->findModelBodega($id);

                # Se obtiene la información del log anterior
                $dataOld = self::dataLog($modelWarehouse);

                $modelGaPiso = GaPiso::find()->where(['idGaPiso' => $modelWarehouse->idGaPiso])->one(); // Piso actual
                $modelGaEdificio = GaEdificio::find()->where(['idGaEdificio' => $modelGaPiso->idGaEdificio])->one(); // Edificio actual

                /** Validar que la bodega no tenga un expediente asignado al espacio físico */
                if (
                    $modelGaEdificio->idDepartamentoGaEdificio != $request['idDepartamentoGaEdificio'] ||
                    $modelGaEdificio->idMunicipioGaEdificio != $request['idMunicipioGaEdificio'] ||
                    $modelGaEdificio->nombreGaEdificio != $request['nombreGaEdificio'] ||
                    $modelGaPiso->numeroGaPiso != $request['numeroGaPiso'] ||
                    $modelWarehouse->nombreGaBodega != $request['nombreGaBodega']
                ) {
                    $countGaArchivo = (int) GaArchivo::find()->where(['idGaBodega' => $id])->count();
                    if ($countGaArchivo > 0) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['errors' => ['Ya existe un expediente asociado al espacio físico']],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
                /** Fin Validar que la bodega no tenga un expediente asignado al espacio físico */

                /** Validar Edificio */
                if (
                    $modelGaEdificio->idDepartamentoGaEdificio != $request['idDepartamentoGaEdificio'] ||
                    $modelGaEdificio->idMunicipioGaEdificio != $request['idMunicipioGaEdificio'] ||
                    $modelGaEdificio->nombreGaEdificio != $request['nombreGaEdificio']
                ) {
                    # Validar que el nombre nuevo del edificio no exista
                    if ($modelGaEdificio->nombreGaEdificio != $request['nombreGaEdificio']) {
                        $validateEdificio1 = GaEdificio::find()->where(['nombreGaEdificio' => $request['nombreGaEdificio']])->count();
                        if ($validateEdificio1 > 0) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => ['errors' => ['El nombre del edificio que desea actualizar ya existe']],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    # Validar que el edificio no tenga mas de una bodega creada
                    $validateEdificio2 = GaBodega::find();
                    $validateEdificio2 = HelperQueryDb::getQuery('innerJoin', $validateEdificio2, 'gaPiso', ['gaPiso' => 'idGaPiso', 'gaBodega' => 'idGaPiso']);
                    $validateEdificio2->where(['gaPiso.idGaEdificio' => $modelGaEdificio->idGaEdificio]);
                    $validateEdificio2->andwhere(['NOT IN', 'gaBodega.idGaBodega', [$modelWarehouse->idGaBodega]]);
                    $validateEdificio2 = $validateEdificio2->count();

                    if ((int) $validateEdificio2 > 0) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['errors' => ['No se puede modificar la información del edificio ya que posee mas de una bodega asignada']],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Validar que todas las bodegas que pertenezcan al edificio no tengan un expediente asignado al espacio físico
                    $validateGaArchivo = GaArchivo::find();
                    $validateGaArchivo = HelperQueryDb::getQuery('innerJoin', $validateGaArchivo, 'gaBodega', ['gaBodega' => 'idGaBodega', 'gaArchivo' => 'idGaBodega']);
                    $validateGaArchivo = HelperQueryDb::getQuery('innerJoin', $validateGaArchivo, 'gaPiso', ['gaPiso' => 'idGaPiso', 'gaBodega' => 'idGaPiso']);
                    $validateGaArchivo->where(['gaPiso.idGaEdificio' => $modelGaEdificio->idGaEdificio]);
                    $validateGaArchivo = $validateGaArchivo->count();

                    if ((int) $validateGaArchivo > 0) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['errors' => ['Ya existe un expediente asociado a una bodega perteneciente al edificio que quiere actualizar']],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Actualizar datos del edificio
                    $modelGaEdificio->idDepartamentoGaEdificio = $request['idDepartamentoGaEdificio'];
                    $modelGaEdificio->idMunicipioGaEdificio = $request['idMunicipioGaEdificio'];
                    $modelGaEdificio->nombreGaEdificio = $request['nombreGaEdificio'];
                    if (!$modelGaEdificio->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelGaEdificio->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                /** Fin Validar Edificio */

                /** Validar Piso */
                if (
                    $modelGaPiso->numeroGaPiso != $request['numeroGaPiso']
                ) {
                    # Validar si el nuevo piso ya existe
                    $modelGaPisoChange = GaPiso::find()->where(['numeroGaPiso' => $request['numeroGaPiso'], 'idGaEdificio' => $modelGaPiso->idGaEdificio])->one();

                    if ($modelGaPisoChange != null) {
                        # Validar que no exista una bodega con el mismo nombre en el nuevo piso
                        $validateBodega = GaBodega::find()->where(['nombreGaBodega' => $request['nombreGaBodega']])->andWhere(['idGaPiso' => $modelGaPisoChange->idGaPiso])->one();
                        if ($validateBodega != null) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => [ 'error' => Yii::t('app', 'duplicateWarehouse',[
                                    'name' => $validateBodega->nombreGaBodega,
                                    'number' => $request['numeroGaPiso']
                                ])],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        } else {
                             $idChangePiso = $modelGaPisoChange->idGaPiso;
                        }

                        # validar si en la tabla piso en id queda sin hijos para eliminar el registro mas adelente
                        $idPisoDelete = $modelGaPiso->idGaPiso;

                    } else {
                        # Validar si el piso actual (al que se le cambia el nombre) tiene mas de una bodega registrada
                        $validateBodega = GaBodega::find()->where(['idGaPiso' => $modelGaPiso->idGaPiso])->andWhere(['<>', 'idGaBodega', $modelWarehouse->idGaBodega])->count();
                        if ($validateBodega > 0) {
                            $modelGaPisoNew = new GaPiso();
                            $modelGaPisoNew->numeroGaPiso = $request['numeroGaPiso'];
                            $modelGaPisoNew->idGaEdificio = $modelGaPiso->idGaEdificio;
                            $modelGaPisoNew->estadoGaPiso = Yii::$app->params['statusTodoText']['Activo'];
                            $modelGaPisoNew->creacionGaPiso = date('Y-m-d H:i:s');

                            if (!$modelGaPisoNew->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelGaPisoNew->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            } else {
                                $idChangePiso = $modelGaPisoNew->idGaPiso;
                            }

                        } else {
                            # Modificar el nombre del piso ACTUAL ya que no tiene otras bodegas relacionadas
                            $modelGaPiso->numeroGaPiso = $request['numeroGaPiso'];
                            if (!$modelGaPiso->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $modelGaPiso->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                        }


                    }

                }
                /** Fin Validar Piso */

                # Atributos de la bodega
                $modelWarehouse->attributes = $request;
                if ($idChangePiso != null) {
                    $modelWarehouse->idGaPiso = $idChangePiso;
                }

                # Verificar si el nombre, con el id del piso ya existe
                $validWarehouse = GaBodega::find()
                    ->where(['nombreGaBodega' => $modelWarehouse->nombreGaBodega])
                    ->andWhere(['idGaPiso' => $modelWarehouse->idGaPiso])
                    ->andWhere(['<>','idGaBodega', $id])
                    ->andWhere(['estadoGaBodega' => Yii::$app->params['statusTodoText']['Activo'] ])
                ->one();


                if(!is_null($validWarehouse)){

                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'duplicateWarehouse',[
                            'name' => $validWarehouse->nombreGaBodega,
                            'number' => $request['numeroGaPiso']
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    if (!$modelWarehouse->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelWarehouse->getErrors());
                    }

                    if ($idPisoDelete != null) {
                        # Eliminacion del piso utilizado anteriormente.. SOLO SI el piso anterior no tiene mas registros de bodega
                        $modelGaPiso->delete();
                    }

                    # Se valida que el contenido de la bodega no tenga menor cantidad al actualizarse
                    $validContent = GaBodegaContenido::findOne(["idGaBodega" => $modelWarehouse->idGaBodega]);

                    if(
                        $request['cantidadRackGaBodegaContenido'] >= $validContent->cantidadRackGaBodegaContenido  &&
                        $request['cantidadEstanteGaBodegaContenido'] >= $validContent->cantidadEstanteGaBodegaContenido  &&
                        $request['cantidadEntrepanoGaBodegaContenido'] >= $validContent->cantidadEntrepanoGaBodegaContenido && 
                        $request['cantidadCajaGaBodegaContenido'] >= $validContent->cantidadCajaGaBodegaContenido 
                    ){

                        # Se elimina el contenido de la bodega
                        $deleteContent = GaBodegaContenido::deleteAll(
                            [   
                                'AND', 
                                ['idGaBodega' => (int) $modelWarehouse->idGaBodega]
                            ]
                        );

                        # Se crea el registro del contenido de la bodega
                        $content = new GaBodegaContenido();
                        $content->attributes = $request;
                        $content->idGaBodega = $modelWarehouse->idGaBodega;

                        if (!$content->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $content->getErrors());
                        }

                    } else {

                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'errorValidContent')],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }                   

                    # Consulta para obtener la información actualizada de la bodega para el log
                    $modelNew = GaBodega::findOne(['idGaBodega' => $modelWarehouse->idGaBodega]);
                    $dataNew = self::dataLog($modelNew);  

                    /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", de las tablas GaBodega y GaBodegaContenido", //texto para almacenar en el evento
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


    /* Cambio de estado de la bodega */
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
            $transaction = Yii::$app->db->beginTransaction();
            
            foreach ($request as $value) {

                $dataExplode = explode('|', $value);

                # Consulta de la bodega para obtener el nombre del edificio
                $model = $this->findModelBodega($dataExplode[0]);
                $modelBuilding = $this->findModel($model->idGaPiso0->idGaEdificio);

                $modelArchive = GaArchivo::findOne(['idGaBodega' => $model->idGaBodega, 'estadoGaArchivo' => yii::$app->params['statusTodoText']['Activo']]);

                # Se valida que el espacio fisico no este asignado a un radicado o expediente
                if(is_null($modelArchive)) {

                    if ($model->estadoGaBodega == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoGaBodega = yii::$app->params['statusTodoText']['Inactivo'];

                    } else {
                        $model->estadoGaBodega = yii::$app->params['statusTodoText']['Activo'];
                    }

                    if ($model->save()) {

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoGaBodega] . ', id: '. $model->idGaBodega.' de la bodega: '.$model->nombreGaBodega.' que pertenece al edificio: '.$modelBuilding->nombreGaEdificio,// texto para almacenar en el evento
                            [], //DataOld
                            [], //Data en string
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array('id' => $model->idGaBodega, 'status' => $model->estadoGaBodega, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoGaBodega], 'idInitialList' => $dataExplode[1] * 1);

                    } else {
                        $errors[] = $model->getErrors();
                        $saveDataValid = false;
                    }

                } else { // Si existe información genera error y no inactiva

                    $dataResponse[] = array('id' => $model->idGaBodega, 'status' => $model->estadoGaBodega, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoGaBodega], 'idInitialList' => $dataExplode[1] * 1);  
                    $valid = false;
                }
            }
            
            # Error cuando el espacio fisico ya esta asignado
            if($valid == false){

                $transaction->rollBack();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorSaveSpace')]],
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


    /**
     * Finds the GaBodega model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GaBodega the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModelBodega($id)
    {
        if (($model = GaBodega::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }


    /**
     * Finds the GaEdificio model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GaEdificio the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GaEdificio::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    public function actionListEdificios()
    {
        $dataidGaEdificio = [];
        $modelidGaEdificio= GaEdificio::find()
        ->where(["estadoGaEdificio" => yii::$app->params['statusTodoText']['Activo'] ])
        ->orderBy(['nombreGaEdificio' => SORT_ASC])
        ->all();

        foreach ($modelidGaEdificio as $row) {
            $dataidGaEdificio[] = array(
                "id" => (int) $row['idGaEdificio'],
                "val" => $row['nombreGaEdificio'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataidGaEdificio' => $dataidGaEdificio ?? [],
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }



    /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $model [Modelo de la bodega]
     * @return $dataLog [String] [Información del modelo]
     **/
    protected function dataLog($model){

        if(!is_null($model)){
            
            # Valores del modelo se utiliza para el log
            $labelModel = $model->attributeLabels();
            $dataLog = '';

            # Obtiene la información de cada bodega
            $modelContent = GaBodegaContenido::findOne(['idGaBodega' => $model->idGaBodega]);

            foreach ($model as $key => $value) {           
            
                switch ($key) {

                    case 'idGaPiso':
                        $dataLog .= 'Id del edificio: '.$model->idGaPiso0->idGaEdificio.', ';
                        $dataLog .= 'Nombre del edificio: '.$model->idGaPiso0->idGaEdificio0->nombreGaEdificio.', ';
                        $dataLog .= 'Departamento: '.$model->idGaPiso0->idGaEdificio0->idDepartamentoGaEdificio0->nomNivelGeografico2.', ';
                        $dataLog .= 'Municipio: '.$model->idGaPiso0->idGaEdificio0->idMunicipioGaEdificio0->nomNivelGeografico3.', ';
                        $dataLog .= 'Estado: '. yii::$app->params['statusTodoNumber'][$model->idGaPiso0->idGaEdificio0->estadoGaEdificio].', ';
                        $dataLog .= 'Fecha creación del edificio: '.$model->idGaPiso0->idGaEdificio0->creacionGaEdificio.', ';
                        $dataLog .= $labelModel[$key].': '.$model->idGaPiso.', ';
                        $dataLog .= 'Número del piso: '.$model->idGaPiso0->numeroGaPiso.', ';
                    break;  
                    case 'idGaBodega':                   
                        $dataLog .= $labelModel[$key].': '.$model->idGaBodega.', ';
                        $dataLog .= 'Cantidad del módulo: '.$modelContent->cantidadRackGaBodegaContenido.', ';
                        $dataLog .= 'Cantidad del estante: '.$modelContent->cantidadEstanteGaBodegaContenido.', ';
                        $dataLog .= 'Cantidad de entrepaños: '.$modelContent->cantidadEntrepanoGaBodegaContenido.', ';
                        $dataLog .= 'Cantidad de la caja: '.$modelContent->cantidadCajaGaBodegaContenido.', ';
                        $dataLog .= 'Cuerpo: '.$modelContent->cuerpoGaBodegaContenido.', ';
                    break;
                    case 'estadoGaBodega':
                    case 'creacionGaBodega':
                    break; 
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }
            return $dataLog;
        }
    } 

}
