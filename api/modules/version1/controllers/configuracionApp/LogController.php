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
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperLog;

use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

use api\models\Log;
use api\models\UserDetalles;
use api\models\RolesOperaciones;

/**
 * LogController implements the CRUD actions for Log model.
 */
class LogController extends Controller
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
                    'view'  => ['GET'],
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
     * Lists all Log models.
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

            # Consulta del log
            $relationModel =  Log::find();

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

            	switch ($field) {

            		case 'idUser':
            			$relationModel->andWhere(['IN', $field, $value]);
                    break;
                    case 'fechaInicial':
                        $relationModel->andWhere(['>=', 'log.fechaLog', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationModel->andWhere(['<=', 'log.fechaLog', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    case 'moduloLog':
                        $modelOperaciones = RolesOperaciones::find()->select(['nombreRolOperacion'])
                            ->where([Yii::$app->params['like'], 'aliasRolOperacion', $value])
                        ->all();
                        
                        $arrayModulos = [];
                        foreach($modelOperaciones as $operacion){
                            $arrayModulos[] = str_replace('%', '/', $operacion->nombreRolOperacion);
                        }
                        $relationModel->andWhere([ Yii::$app->params['orlike'],'log.moduloLog', $arrayModulos]);
                    break;
                    default:
            			$relationModel->andWhere([Yii::$app->params['like'], $field, $value]);
            		break;

            	}
            }
            
            # Limite de la consulta
            $relationModel->orderBy(['log.idLog' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $relationModel->limit($limitRecords);
            $modelRelation = $relationModel->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $infoRelation) {

                $tratamientoRuta = str_replace('/', '%', $infoRelation->moduloLog);

                $modulo = RolesOperaciones::findOne(['nombreRolOperacion' => $tratamientoRuta]);

                if(!is_null($modulo)) {
                    $nombreModulo = $modulo->aliasRolOperacion;
                }
                // Se realiza un tratamiento a las operaciones que no se guardan directamente en la tabla si no son por defecto
                else{
                    $nombreModulo = HelperLog::getDefaultModule($infoRelation->moduloLog);
                }

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idLog))
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'                  => $dataBase64Params,
                    'id'                    => $infoRelation->idLog,
                    'username'     			=> $infoRelation->userNameLog,
                    'usuario'        		=> ($infoRelation->user->userDetalles->nombreUserDetalles ?? 'id error:'.$infoRelation->idLog) .' '. ($infoRelation->user->userDetalles->apellidoUserDetalles ?? ''),
                    'fechaLog'        		=> $infoRelation->fechaLog,
                    'moduloLog'				=> $nombreModulo,
                    'eventoLog'				=> $infoRelation->eventoLog,
                    'statusText'            => Yii::t('app', 'statusTodoNumber')[10],
                    'status'                => 10,
                    'rowSelect'             => false,
                    'idInitialList'         => 0
                );
                
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexLogAuditoria');
            
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
    

    /** Displays a single Log model.
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

            # Se realiza un tratamiento a la ruta del modulo
            $tratamientoRuta = str_replace('/', '%', $model->moduloLog);

            $modulo = RolesOperaciones::findOne(['nombreRolOperacion' => $tratamientoRuta]);

            if(!is_null($modulo)) {
                $nombreModulo = $modulo->aliasRolOperacion;
            }
            // Se realiza un tratamiento a las operaciones que no se guardan directamente en la tabla si no son por defecto
            else {
                $nombreModulo = HelperLog::getDefaultModule($model->moduloLog);
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].$id, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            //Retorna toda la información del log
            $data[] = array('alias' => 'Usuario', 'value' => $model->userNameLog);
            $data[] = array('alias' => 'Usuario', 'value' => $model->user->userDetalles->nombreUserDetalles.' '.$model->user->userDetalles->apellidoUserDetalles);
            $data[] = array('alias' => 'Ip', 'value' => $model->ipLog);
            $data[] = array('alias' => 'Fecha', 'value' => $model->fechaLog);            
            $data[] = array('alias' => 'Módulo', 'value' => $nombreModulo ?? '');
            $data[] = array('alias' => 'Evento', 'value' => $model->eventoLog);
            $data[] = array('alias' => 'Antes', 'value' => $model->antesLog);
            $data[] = array('alias' => 'Después', 'value' => $model->despuesLog);
            
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
     * Finds the Log model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Log the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Log::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

}
