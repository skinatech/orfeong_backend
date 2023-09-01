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
use api\models\CgTrd;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

use api\components\HelperQueryDb;

/**
 * CgTrdController implements the CRUD actions for CgTrd model.
 */
class CgTrdController extends Controller
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
                     'index-list'  => ['GET'],
                     'view'  => ['GET'],
                     'create'  => ['POST'],
                     //'update'  => ['PUT'],                    
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
           
            // El $request obtiene 'filterOperation' => [["cellDependenciaCgTrd"=>"", "idMascaraCgTrd"=>"1"]]
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
  
            //Lista de campos de la configuracion
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
                        if( isset($info) && $info !== null && trim($info) != '' ){
                            $dataWhere[$key] = $info;
                        }
                    }
                }
            }

            // Consulta para relacionar la informacion de la cg trd y las mascaras para obtener 100 registros, a partir del filtro
            $relationTrd =  CgTrd::find();
                //->leftJoin('cgTrdMascaras', ' cgTrdMascaras . idCgTrdMascara  =  cgTrd . idMascaraCgTrd ')
            $relationTrd = HelperQueryDb::getQuery('leftJoin', $relationTrd, 'cgTrdMascaras', ['cgTrd' => 'idMascaraCgTrd', 'cgTrdMascaras' => 'idCgTrdMascara']);

            $relationTrd = $relationTrd->where(['cgTrd.estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']]);
                
            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){
                $relationTrd->andWhere([Yii::$app->params['like'], 'cgTrd.'.$field , $value]);
            }                
            //Limite de la consulta
            $relationTrd->orderBy(['idCgTrd' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $relationTrd->limit($limitRecords);
            $modelRelation = $relationTrd->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);
            
            
            foreach($modelRelation as $infoRelation) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idCgTrd))
                );

                $dataList[] = array(
                    'data'                  => $dataBase64Params,
                    'id'                    => $infoRelation->idCgTrd,
                    'nombreCgTrdMascara'    => $infoRelation->idMascaraCgTrd0->nombreCgTrdMascara,
                    'cellDependenciaCgTrd'  => $infoRelation->cellDependenciaCgTrd,
                    'cellDependenciaPadreCgTrd'  => $infoRelation->cellDependenciaPadreCgTrd,
                    'cellTituloDependCgTrd' => $infoRelation->cellTituloDependCgTrd,
                    'cellRegionalCgTrd'     => ($infoRelation->cellRegionalCgTrd) ? $infoRelation->cellRegionalCgTrd : 'N/A',
                    'cellDatosCgTrd'        => $infoRelation->cellDatosCgTrd,
                    'columnCodigoCgTrd'     => $infoRelation->columnCodigoCgTrd,
                    'column2CodigoCgTrd'    => ($infoRelation->column2CodigoCgTrd) ? $infoRelation->column2CodigoCgTrd : 'N/A',
                    'column3CodigoCgTrd'    => ($infoRelation->column3CodigoCgTrd) ? $infoRelation->column3CodigoCgTrd : 'N/A',
                    'columnNombreCgTrd'     => $infoRelation->columnNombreCgTrd,
                    'columnAgCgTrd'         => $infoRelation->columnAgCgTrd,
                    'columnAcCgTrd'         => $infoRelation->columnAcCgTrd,
                    'columnCtCgTrd'         => $infoRelation->columnCtCgTrd,
                    'columnECgTrd'          => $infoRelation->columnECgTrd,
                    'columnSCgTrd'          => $infoRelation->columnSCgTrd,
                    'columnMCgTrd'          => $infoRelation->columnMCgTrd,
                    'columnProcessCgTrd'    => $infoRelation->columnProcessCgTrd,      
                    'columnTipoDocCgTrd'    => ($infoRelation->columnTipoDocCgTrd) ? $infoRelation->columnTipoDocCgTrd : 'N/A',      
                    'columnPSoporteCgTrd'   => ($infoRelation->columnPSoporteCgTrd) ? $infoRelation->columnPSoporteCgTrd : 'N/A' ,      
                    'columnESoporteCgTrd'   => ($infoRelation->columnESoporteCgTrd) ? $infoRelation->columnESoporteCgTrd : 'N/A',      
                    'columnOsoporteCgTrd'   => ($infoRelation->columnOsoporteCgTrd) ? $infoRelation->columnOsoporteCgTrd : 'N/A',      
                    'columnNormaCgTrd'      => ($infoRelation->columnNormaCgTrd) ? $infoRelation->columnNormaCgTrd : 'N/A',      
                    'statusText'            => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoCgTrd],
                    'status'                => $infoRelation->estadoCgTrd,
                    'rowSelect'             => false,
                    'idInitialList'         => 0
                );                
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::createDataForForm('indexCgTrd');
            
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


    /** Displays a single Cg Trd model.
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

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'] .' tipo máscara '. $model->idMascaraCgTrd0->nombreCgTrdMascara .' y id '. $id, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/
        
            //Retorna toda la informacion de la tabla cgTrd
            $data[] = array('alias' => 'Máscara', 'value' => $model->idMascaraCgTrd0->nombreCgTrdMascara);
            $data[] = array('alias' => 'settingModule.ingreseceldaDepen', 'value' => $model->cellDependenciaCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseDependenciaPadreCgTrd', 'value' => $model->cellDependenciaPadreCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseTituloDepen', 'value' => $model->cellTituloDependCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseRegional', 'value' => $model->cellRegionalCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseDatoscg', 'value' => $model->cellDatosCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseCodigocg', 'value' => $model->columnCodigoCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseCodigoSerie', 'value' => $model->column2CodigoCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseCodigoSubserie', 'value' => $model->column3CodigoCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseNombrecg', 'value' => $model->columnNombreCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseNormaCgTrd', 'value' => $model->columnNormaCgTrd);
            $data[] = array('alias' => 'settingModule.ingresePSoporteCgTrd', 'value' => $model->columnPSoporteCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseESoporteCgTrd', 'value' => $model->columnESoporteCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseOSoporteCgTrd', 'value' => $model->columnOsoporteCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemAg', 'value' => $model->columnAgCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemAc', 'value' => $model->columnAcCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemCT', 'value' => $model->columnCtCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemE', 'value' => $model->columnECgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemS', 'value' => $model->columnSCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseItemM', 'value' => $model->columnMCgTrd);
            $data[] = array('alias' => 'settingModule.ingreseProcedimiento', 'value' => $model->columnProcessCgTrd);
            $data[] = array('alias' => 'settingModule.tipoDoc', 'value' => $model->columnTipoDocCgTrd);
            
            
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
     * Crea un nuevo registro en el modelo Cg Trd e inactiva los registros creados anteriormente
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
                $transaction = Yii::$app->db->beginTransaction();
                
                unset($request['id']);
               
                // Elimina el registro activo, almacenado anteriormente
                $deleteCgTrd = CgTrd::deleteAll(
                    [   
                        'AND', 
                        ['estadoCgTrd' => (int) Yii::$app->params['statusTodoText']['Activo']]
                    ]
                );
  
                //Se construye el modelo para el nuevo registro, que quedará activo
                $modelCgTrd = new CgTrd();
  
                if($request['tieneNorma'] == true){
                    $modelCgTrd->columnNormaCgTrd = strtoupper(trim($request['columnNormaCgTrd']));
                }

                if($request['tieneRegional'] == true){
                    $modelCgTrd->cellRegionalCgTrd = strtoupper(trim($request['cellRegionalCgTrd']));
                }

                if($request['tieneSoporte'] == true){
                    $modelCgTrd->columnPSoporteCgTrd = strtoupper(trim($request['columnPSoporteCgTrd']));
                    $modelCgTrd->columnESoporteCgTrd = strtoupper(trim($request['columnESoporteCgTrd']));
                    
                    if(isset($request['columnOSoporteCgTrd']) && $request['columnOSoporteCgTrd'] != ''){
                        $modelCgTrd->columnOsoporteCgTrd = strtoupper(trim($request['columnOSoporteCgTrd']));
                    } else {
                        $modelCgTrd->columnOsoporteCgTrd = null;
                    }                    
                }
  
                if($request['idMascaraCgTrd'] == Yii::$app->params['ConfiguracionMascara']){
                    $modelCgTrd->column2CodigoCgTrd = strtoupper(trim($request['column2CodigoCgTrd']));
                    $modelCgTrd->column3CodigoCgTrd = strtoupper(trim($request['column3CodigoCgTrd']));
                }

                if($request['tieneDias'] == true){
                    $modelCgTrd->columnTipoDocCgTrd = strtoupper(trim($request['columnTipoDocCgTrd']));
                }

                $modelCgTrd->idMascaraCgTrd = $request['idMascaraCgTrd'];
                $modelCgTrd->cellDependenciaCgTrd = strtoupper(trim($request['cellDependenciaCgTrd']));
                $modelCgTrd->cellTituloDependCgTrd = strtoupper(trim($request['cellTituloDependCgTrd']));
                $modelCgTrd->cellDatosCgTrd = strtoupper(trim($request['cellDatosCgTrd']));
                $modelCgTrd->columnCodigoCgTrd = strtoupper(trim($request['columnCodigoCgTrd']));
                $modelCgTrd->columnNombreCgTrd = strtoupper(trim($request['columnNombreCgTrd']));
                $modelCgTrd->columnAgCgTrd = strtoupper(trim($request['columnAgCgTrd']));
                $modelCgTrd->columnAcCgTrd = strtoupper(trim($request['columnAcCgTrd']));
                $modelCgTrd->columnCtCgTrd = strtoupper(trim($request['columnCtCgTrd']));
                $modelCgTrd->columnECgTrd = strtoupper(trim($request['columnECgTrd']));
                $modelCgTrd->columnSCgTrd = strtoupper(trim($request['columnSCgTrd']));
                $modelCgTrd->columnMCgTrd = strtoupper(trim($request['columnMCgTrd']));
                $modelCgTrd->columnProcessCgTrd = strtoupper(trim($request['columnProcessCgTrd']));
                $modelCgTrd->cellDependenciaPadreCgTrd = strtoupper(trim($request['cellDependenciaPadreCgTrd']));
                 
                if ($modelCgTrd->save()) {
                  
                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla Cg Trd", //texto para almacenar en el evento
                        [], //DataOld
                        [$modelCgTrd], //Data
                        array() //No validar estos campos
                    );  
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelCgTrd,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelCgTrd->getErrors(),
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
     * Finds the cgTrd model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgTrd the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgTrd::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
