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
use api\models\CgNumeroRadicado;
use api\models\RadiRadicados;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

/**
 * TiposDocumentalController implements the CRUD actions for Gd Trd Tipos documental model.
 */
class NumeroRadicadoController extends Controller
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
                    'index-list'  => ['GET'],
                    'update'  => ['PUT'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionUpdate(){      

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

                /** Validar si ya extiste un radicado creado */
                $modelRadiRadicados = RadiRadicados::find()->one();
                if ($modelRadiRadicados != null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errExistRadiConfigRadicado')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /** Fin Validar si ya extiste un radicado creado */

                $id = $request['id'];
                $estructura = $request['estructuraCgNumeroRadicado'];
                $lonDependencia = $request['longitudDependenciaCgNumeroRadicado'];
                $lonConsecutivo = $request['longitudConsecutivoCgNumeroRadicado'];             

                $modelNumeroRadicado =  self::findModel($id);

                # Data anterior para el log
                $dataNumeroRadicadoOld = self::dataLog($modelNumeroRadicado);

                //Comienza la transacción para cambiar el modelo
                $transaction = Yii::$app->db->beginTransaction();

                $modelNumeroRadicado =  self::findModel($id);
                $modelNumeroRadicadoOld = clone  $modelNumeroRadicado;                

                $modelNumeroRadicado->estructuraCgNumeroRadicado = $estructura;
                $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado = $lonDependencia;
                $modelNumeroRadicado->longitudConsecutivoCgNumeroRadicado = $lonConsecutivo;

                if($modelNumeroRadicado->estructuraCgNumeroRadicado != $modelNumeroRadicadoOld->estructuraCgNumeroRadicado
                    || $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado != $modelNumeroRadicadoOld->longitudDependenciaCgNumeroRadicado
                    || $modelNumeroRadicado->longitudConsecutivoCgNumeroRadicado != $modelNumeroRadicadoOld->longitudDependenciaCgNumeroRadicado 
                ){
                    if($modelNumeroRadicado->save()){

                        # Data para el log
                        $dataNumeroRadicado = self::dataLog($modelNumeroRadicado);

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla cg numero radicado", //texto para almacenar en el evento
                            $dataNumeroRadicadoOld, //DataOld
                            $dataNumeroRadicado, //Data
                            array() //No validar estos campos
                        );     
                        /***    Fin log Auditoria   ***/
                    }
                }

                $transaction->commit();

                //Respuesta success del action
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successUpdate'),
                    'data' => $modelNumeroRadicado,
                    //'dataTransacciones' => $transacciones,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }
        
    public function actionIndexOne(){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
                  
            $data = [];
            $dataList = [];            

            $modelNumeroRadicado = CgNumeroRadicado::find()
                ->where(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();

            $data = [
                'id' => $modelNumeroRadicado->idCgNumeroRadicado,
                'estructuraCgNumeroRadicado'=>  $modelNumeroRadicado->estructuraCgNumeroRadicado,
                'longitudDependenciaCgNumeroRadicado' => $modelNumeroRadicado->longitudDependenciaCgNumeroRadicado,
                'estadoCgNumeroRadicado' => $modelNumeroRadicado->estadoCgNumeroRadicado,
                'creacionCgNumeroRadicado' => $modelNumeroRadicado->creacionCgNumeroRadicado,
                'longitudConsecutivoCgNumeroRadicado' => $modelNumeroRadicado->longitudConsecutivoCgNumeroRadicado,
            ];

            $dataList = [];
            $estructuraRadicado = Yii::$app->params['estructuraRadicado'];
            
            foreach($estructuraRadicado as $key => $value){
                $dataList[] = ['id' => $key, 'val' => $value];
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'dataList' => $dataList                
            ];
            return HelperEncryptAes::encrypt($response, true);

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }
    
    protected function findModel($id){
        if (($model = CgNumeroRadicado::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function activateNumeroRadicado($id){
        
        $modelNumeroRadicado = CgNumeroRadicado::find()
            ->all()
        ;

        foreach($modelNumeroRadicado as $numeroRadicado){
            if ($numeroRadicado->estadoCgNumeroRadicado == yii::$app->params['statusTodoText']['Activo'] && $numeroRadicado->idCgNumeroRadicado != $id) {

                $numeroRadicado->estadoCgNumeroRadicado = yii::$app->params['statusTodoText']['Inactivo'];

                if($numeroRadicado->save()){

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$numeroRadicado->estadoCgNumeroRadicado] . ', id: '. $numeroRadicado->idCgNumeroRadicado.' con la estructura del número radicado: '.$numeroRadicado->estructuraCgNumeroRadicado.' de la tabla CgNumeroRadicado',
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/

                }
            }else if($numeroRadicado->estadoCgNumeroRadicado == yii::$app->params['statusTodoText']['Inactivo'] && $numeroRadicado->idCgNumeroRadicado == $id){

                $numeroRadicado->estadoCgNumeroRadicado = yii::$app->params['statusTodoText']['Activo'];

                if($numeroRadicado->save()){

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$numeroRadicado->estadoCgNumeroRadicado] . ', id: '. $numeroRadicado->idCgNumeroRadicado.' con la estructura del número radicado: '.$numeroRadicado->estructuraCgNumeroRadicado.' de la tabla CgNumeroRadicado',
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/

                }
            }
        }
    
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

                    case 'estadoCgNumeroRadicado':
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

}
