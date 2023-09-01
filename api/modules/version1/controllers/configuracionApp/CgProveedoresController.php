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
use api\models\CgEnvioServicios;
use api\models\CgProveedores;
use api\models\CgProveedoresRegional;
use api\models\CgProveedoresServicios;
use api\models\CgRegionales;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

use api\components\HelperQueryDb;


/**
 * CgProveedoresController implements the CRUD actions for CgProveedores model.
 */
class CgProveedoresController extends Controller
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
                    'index-list-servicio'  => ['GET'],
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
           
            // El $request obtiene $response = ['filterOperation' => [["nombreCgProveedor"=> ["Proveedor 1", "Proveedor24"],
            // "idCgRegional"=> [1,2] , "idCgEnvioServicios" => [2], ]] ];
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
            $relationProveedores =  CgProveedores::find();
                //->leftJoin('cgProveedoresRegional', ' cgProveedoresRegional . idCgProveedor  =  cgProveedores . idCgProveedor ')
                $relationProveedores = HelperQueryDb::getQuery('leftJoin', $relationProveedores, 'cgProveedoresRegional', ['cgProveedores' => 'idCgProveedor', 'cgProveedoresRegional' => 'idCgProveedor']); 
                //->leftJoin('cgProveedoresServicios', ' cgProveedoresServicios . idCgProveedor  =  cgProveedores . idCgProveedor ');
                $relationProveedores = HelperQueryDb::getQuery('leftJoin', $relationProveedores, 'cgProveedoresServicios', ['cgProveedores' => 'idCgProveedor', 'cgProveedoresServicios' => 'idCgProveedor']);     
                // ->where(["cgProveedores.estadoCgProveedor" => Yii::$app->params['statusTodoText']['Activo']]);
                
            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {
                    case 'idCgEnvioServicios':
                        $relationProveedores->andWhere(['IN', 'cgProveedoresServicios.'.$field, $value ]);
                    break;

                    case 'idCgRegional':
                        $relationProveedores->andWhere(['IN', 'cgProveedoresRegional.'.$field, $value ]);
                    break;

                    case 'estadoCgProveedor':
                        $relationProveedores->andWhere(['IN', 'cgProveedores.' . $field, intval($value)]);
                    break;

                    case 'fechaInicial':
                        $relationProveedores->andWhere(['>=', 'cgProveedores.creacionCgProveedor', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationProveedores->andWhere(['<=', 'cgProveedores.creacionCgProveedor', trim($value) . Yii::$app->params['timeEnd']]);
                    break;

                    default:
                        $relationProveedores->andWhere([Yii::$app->params['like'], 'cgProveedores.' . $field, $value]);
                    break;
                }
                
            }
            
            # Limite de la consulta
            $relationProveedores->orderBy(['cgProveedores.idCgProveedor' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $relationProveedores->limit($limitRecords);
            $modelRelation = $relationProveedores->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $infoRelation) {
               
                $nombreServicio = [];
                $nombreRegional = [];

                # Se itera la informacion para obtener el nombre de la regional
                foreach ($infoRelation->cgProveedoresRegionals as $data){
                    $nombreRegional[] = $data->idCgRegional0->nombreCgRegional;                    
                }

                # Se itera la informacion para obtener el nombre del servicio
                foreach ($infoRelation->cgProveedoresServicios as $data){
                    $nombreServicio[] = $data->idCgEnvioServicios0->nombreCgEnvioServicio;
                }

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idCgProveedor)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->nombreCgProveedor))
                );
                
                # Listado de informacion
                $dataList[] = array(
                    'data'                  => $dataBase64Params,
                    'id'                    => $infoRelation->idCgProveedor,
                    'nombreCgProveedor'     => $infoRelation->nombreCgProveedor,
                    'nombreServicio'        => $nombreServicio,
                    'nombreRegional'        => $nombreRegional,
                    'creacion'              => $infoRelation->creacionCgProveedor,
                    'statusText'            => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoCgProveedor],
                    'status'                => $infoRelation->estadoCgProveedor,
                    'rowSelect'             => false,
                    'idInitialList'         => 0
                );                
            }

          
            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexCgProveedores');
            
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
        
        # Información del proveedor
        $data['idCgProveedor'] = $model->idCgProveedor;
        $data['nombreCgProveedor'] = $model->nombreCgProveedor;
       
        # Ids de los servicios del proveedor
        $proveedorServicio = CgProveedoresServicios::findAll(['idCgProveedor' => $model->idCgProveedor]);
        foreach ($proveedorServicio as $infoProveedorServicio) {
            $data['idServicioCgProveedor'][] = $infoProveedorServicio->idCgEnvioServicios0->idCgEnvioServicio;   
        }

        # Ids de las regionales del proveedor
        $proveedorRegional = CgProveedoresRegional::findAll(['idCgProveedor' => $model->idCgProveedor]);
        foreach ($proveedorRegional as $infoProveedorRegional) {
            $data['idCgRegional'][] = $infoProveedorRegional->idCgRegional0->idCgRegional;                
        }

        /*** Fin Consulta de modelo	***/
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);

        
    }
    

    /** Displays a single Cg Proveedores model.
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
            $proveedorServicio = CgProveedoresServicios::findAll(['idCgProveedor' => $model->idCgProveedor]);
            $proveedorRegional = CgProveedoresRegional::findAll(['idCgProveedor' => $model->idCgProveedor]);

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].' id: '.$id.' del proveedor: '.$model->nombreCgProveedor, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            //Retorna toda la información de los proveedores
            $data[] = array('alias' => 'Nombre proveedor', 'value' => $model->nombreCgProveedor);
            $data[] = array('alias' => 'Fecha creación', 'value' => $model->creacionCgProveedor);

                foreach ($proveedorServicio as $infoProveedorServicio) {
                    $data[] = array('alias' => 'Servicio', 'value' => $infoProveedorServicio->idCgEnvioServicios0->nombreCgEnvioServicio);
                }

                foreach ($proveedorRegional as $infoProveedorRegional) {
                    $data[] = array('alias' => 'Regional', 'value' => $infoProveedorRegional->idCgRegional0->nombreCgRegional);
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
     * Crea un nuevo registro en el modelo Cg Proveedores y las relaciones en las otras tablas de proveedores
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            //$response = [ 'nombreCgProveedor' => 'Proveedor 2   4', 'idServicioCgProveedor' => 2, 'idCgRegional' => 1];
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
                $nameServicio = [];
                $nameRegional = [];
                $idServicios = [];
                $idRegionales = [];
                $data = '';
                $transaction = Yii::$app->db->beginTransaction();
                
                # Se construye los modelos para el nuevo registro
                $modelCgProveedor = new CgProveedores();
               
                $modelCgProveedor->attributes = $request;

                if ($modelCgProveedor->save()) {

                    # Creacion de registro tabla relacion proveedores-servicio
                    foreach($request['idServicioCgProveedor'] as $idServicio) {
                        $modelProveedorServicio = new CgProveedoresServicios();
                        $modelProveedorServicio->idCgProveedor = $modelCgProveedor->idCgProveedor;
                        $modelProveedorServicio->idCgEnvioServicios = $idServicio;

                        if (!$modelProveedorServicio->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelProveedorServicio->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actuales
                        $nameServicio[] = $modelProveedorServicio->idCgEnvioServicios0->nombreCgEnvioServicio;
                        $idServicios[] = $modelProveedorServicio->idCgEnvioServicios;
                    }

                    # Creacion de registro tabla relacion proveedores-regional
                    foreach($request['idCgRegional'] as $idRegional) {
                        $modelProveedorRegional = new CgProveedoresRegional();
                        $modelProveedorRegional->idCgProveedor = $modelCgProveedor->idCgProveedor;
                        $modelProveedorRegional->idCgRegional = $idRegional;                      

                        if(!$modelProveedorRegional->save()){
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelProveedorRegional->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actuales
                        $nameRegional[] = $modelProveedorRegional->idCgRegional0->nombreCgRegional;
                        $idRegionales[] = $modelProveedorRegional->idCgRegional;
                    }
                    
                } else {
                    $saveDataValid = false;
                    $errors = $modelCgProveedor->getErrors();
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataProveedor($request, $idServicios, $nameServicio, $idRegionales, $nameRegional);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgProveedores", //texto para almacenar en el evento
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

            //$response = [ 'id' => 1, 'nombreCgProveedor' => 'Proveedor 1', 
            //'idServicioCgProveedor' => [1, 2, 16], 'idCgRegional' => [1]];
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
                $nameServicio = [];
                $nameRegional = [];
                $idServicios = [];
                $idRegionales = [];
                $data = '';
                $dataOld = '';

                $transaction = Yii::$app->db->beginTransaction();                

                # Consulta de relación de proveedores
                $tablaProveedor = CgProveedores::tableName() . ' AS PR';
                $tablaProvRegional = CgProveedoresRegional::tableName() . ' AS RG';
                $tablaProvServicio = CgProveedoresServicios::tableName() . ' AS SER';

                /*$relationProveedores = (new \yii\db\Query())
                    ->from($tablaProveedor)
                    ->leftJoin($tablaProvRegional, ' rg . idCgProveedor  =  pr . idCgProveedor ')
                    ->leftJoin($tablaProvServicio, ' ser . idCgProveedor  =  pr . idCgProveedor ')
                    ->where(['pr.idCgProveedor' => $id])
                ->all();*/

                $relationProveedores = (new \yii\db\Query())
                    ->from($tablaProveedor);
                    $relationProveedores = HelperQueryDb::getQuery('leftJoinAlias', $relationProveedores, $tablaProvRegional, ['PR' => 'idCgProveedor', 'RG' => 'idCgProveedor']);
                    $relationProveedores = HelperQueryDb::getQuery('leftJoinAlias', $relationProveedores, $tablaProvServicio, ['PR' => 'idCgProveedor', 'SER' => 'idCgProveedor']); 
                    $relationProveedores = $relationProveedores->where(['PR.idCgProveedor' => $id])
                ->all();
                
                if(!is_null($relationProveedores)){

                    $idRegion = [];                    
                    $idServi = [];                    
                    foreach ($relationProveedores as $key => $value) {
                        $idProv = $value['idCgProveedor'];
                        $name = $value['nombreCgProveedor'];
                        $estado = $value['estadoCgProveedor'];

                        $modelRegional = CgProveedoresRegional::findOne(['idCgProveedor' => $id]);
                        $modelServicios = CgProveedoresServicios::findOne(['idCgProveedor' => $id]);

                        if(isset($idRegion['region'])) {
                            if(!in_array($modelRegional->idCgRegional, $idRegion['region'])){
                                $idRegion['region'][] = $modelRegional->idCgRegional;
                            }
                        } else {
                            $idRegion['region'][] = $modelRegional->idCgRegional;
                        }

                        if(isset($idServi['servicio'])) {
                            if(!in_array($modelServicios->idCgEnvioServicios, $idServi['servicio'])){
                                $idServi['servicio'][] = $modelServicios->idCgEnvioServicios;
                            }
                        } else {
                            $idServi['servicio'][] = $modelServicios->idCgEnvioServicios;
                        }                                  
                    }    
                    #Para obtener nombre servicio
                    $servicios = CgEnvioServicios::findAll(['idCgEnvioServicio' => $idServi['servicio']]);

                    if(!is_null($servicios)){
                        $nameServi = [];
                        foreach($servicios as $info){
                            $nameServi[] = $info->nombreCgEnvioServicio;
                        }
                    }

                    #Para obtener nombre regional
                    $regional = CgRegionales::findAll(['idCgRegional' => $idRegion['region']]);

                    if(!is_null($regional)){
                        $nameRegion = [];
                        foreach($regional as $info){
                            $nameRegion[] = $info->nombreCgRegional;
                        }
                    }

                    if( isset($idRegion['region']) && isset($idServi['servicio'])){
                        $estado == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

                        $dataOld = 'Id proveedor: '.$idProv; 
                        $dataOld .= ', Nombre de la empresa que hace el envío de correspondencia: '.$name;
                        $dataOld .= ', Estado proveedor: '.$estado;  
                        $dataOld .= ', Id de la regional: '.implode(', ',$idRegion['region']);
                        $dataOld .= ', Nombre de la regional: '.implode(', ', $nameRegion);
                        $dataOld .= ', Id del envío servicio: '.implode(', ',$idServi['servicio']);
                        $dataOld .= ', Nombre del envío servicio: '.implode(', ', $nameServi);
                    }  

                } else {
                    $dataOld = '';
                }

                # Agrega nuevos datos al modelo
                $modelProveedores = $this->findModel($id);
                $modelProveedores->attributes = $request;

                if ($modelProveedores->save()) {

                    # Se eliminan los registros anteriores de los servicios que pertenecen al idProveedor
                    $deleteProveedorServicio = CgProveedoresServicios::deleteAll(
                        [   
                            'AND', 
                            ['idCgProveedor' => (int) $modelProveedores->idCgProveedor]
                        ]
                    );
                   
                    foreach ($request['idServicioCgProveedor'] as $idServicio){
                        $proveedorServicio = new CgProveedoresServicios();
                        $proveedorServicio->idCgProveedor = $modelProveedores->idCgProveedor;
                        $proveedorServicio->idCgEnvioServicios = $idServicio;

                        if (!$proveedorServicio->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $proveedorServicio->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actuales
                        $nameServicio[] = $proveedorServicio->idCgEnvioServicios0->nombreCgEnvioServicio;
                        $idServicios[] = $proveedorServicio->idCgEnvioServicios;
                    }


                    # Se eliminan los registros anteriores de las regionales que pertenecen al idProveedor
                    $deleteProveedorRegional = CgProveedoresRegional::deleteAll(
                        [   
                            'AND', 
                            ['idCgProveedor' => (int) $modelProveedores->idCgProveedor]
                        ]
                    );
                   
                    foreach ($request['idCgRegional'] as $idRegional){
                        $proveedorRegional = new CgProveedoresRegional();
                        $proveedorRegional->idCgProveedor = $modelProveedores->idCgProveedor;
                        $proveedorRegional->idCgRegional = $idRegional;

                        if (!$proveedorRegional->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $proveedorRegional->getErrors());
                            break;
                        }

                        # Construcción de array para crear string de datos actuales
                        $nameRegional[] = $proveedorRegional->idCgRegional0->nombreCgRegional;
                        $idRegionales[] = $proveedorRegional->idCgRegional;
                    }

                } else {                    
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelProveedores->getErrors());                   
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataProveedor($request, $idServicios, $nameServicio, $idRegionales, $nameRegional);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla Cg Proveedores", //texto para almacenar en el evento
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
                
                if ($model->estadoCgProveedor == yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoCgProveedor = yii::$app->params['statusTodoText']['Inactivo'];

                } else {
                    $model->estadoCgProveedor = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgProveedor] . ', id: '. $model->idCgProveedor.' del proveedor: '.$model->nombreCgProveedor,// texto para almacenar en el evento
                        [], //DataOld
                        [], //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idCgProveedor, 'status' => $model->estadoCgProveedor, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgProveedor], 'idInitialList' => $dataExplode[1] * 1);

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
     * Finds the CgProveedores model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgProveedores the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgProveedores::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }


    /* Listado de Servicios */
    public function actionIndexListServicio()
    {
        $dataList = [];
        $servicios = CgEnvioServicios::find()
            ->where(['estadoCgEnvioServicio' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreCgEnvioServicio' => SORT_ASC])
            ->all();

        foreach ($servicios as $servicio) {
            $dataList[] = array(
                "id" => intval($servicio->idCgEnvioServicio),
                "val" => $servicio->nombreCgEnvioServicio,
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
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que recibe del servicio]
     * @param $idServicios [array] [Ids de los servicios]
     * @param $nameServicio [array] [Nombres de los servicios]
     * @param $idRegionales [array] [Ids de las regionales]
     * @param $nameRegional [array] [Nombres de las regionales]
     */
    protected function dataProveedor($request, $idServicios, $nameServicio, $idRegionales, $nameRegional){

        #Consulta para obtener los datos del estado y fecha del proveedor
        $proveedor = CgProveedores::findOne(['nombreCgProveedor' => $request['nombreCgProveedor']]);

        $proveedor->estadoCgProveedor == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

        $data = 'Id proveedor: '.$proveedor->idCgProveedor;
        $data .= ', Nombre de la empresa que hace el envío de correspondencia: '.$proveedor->nombreCgProveedor;
        $data .= ', Estado proveedor: '.$estado;
        $data .= ', Fecha creación proveedor: '.$proveedor->creacionCgProveedor;
        $data .= ', Id del envío servicio: '.implode(', ',$idServicios);                  
        $data .= ', Nombre del envío servicio: '.implode(', ', $nameServicio);
        $data .= ', Id de la regional: '.implode(', ',$idRegionales);                  
        $data .= ', Nombre de la regional: '.implode(', ', $nameRegional);        

        return $data;
    }
}
