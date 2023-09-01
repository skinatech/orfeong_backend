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
namespace api\modules\version1\controllers;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperDynamicForms;
use api\components\HelperLog;
use api\components\HelperNotification;
use api\models\Notificacion;

use yii\web\Controller;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;
use yii\web\NotFoundHttpException;

/**
 * NotificacionController
 */
class NotificacionController extends Controller
{

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
                    'change-status'  => ['PUT'],        
                    'change-status-inactive'  => ['PUT'],        
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
     * Lists all model notificacion.
     * @return mixed
     */
    public function actionIndex($request)
    {    
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

        # Consulta todas las notificaciones activas
        $relationNotification =  Notificacion::find()
            ->where(['estadoNotificacion' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['idUserNotificado' => Yii::$app->user->identity->id]);

        # Orden descendente para ver los últimos registros creados
        $relationNotification->orderBy(['notificacion.idNotificacion' => SORT_ASC]);

        # Limite de la consulta
        // $relationNotification->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationNotification->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) { 

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idNotificacion)),
            );
            
            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idNotificacion,
                'creatorUser'       => $dataRelation->idUserCreador0->userDetalles->nombreUserDetalles.' '.$dataRelation->idUserCreador0->userDetalles->apellidoUserDetalles,
                'notification'      => $dataRelation->notificacion,
                'url'               => $dataRelation->urlNotificacion,                   
                'date'              => $dataRelation->creacionNotificacion,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexNotificacion');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,       
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }


    /* Cambio de estado de las notificaciones */
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
            $transaction = Yii::$app->db->beginTransaction();

            foreach ($request as $value) {

                $dataExplode = explode('|', $value);
                $model = $this->findModel($dataExplode[0]);

                if ($model->estadoNotificacion == Yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoNotificacion = Yii::$app->params['statusTodoText']['Inactivo'];

                } else {
                    $model->estadoNotificacion = Yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoNotificacion] . ', id: '. $model->idNotificacion.' de la notificación: '.$model->notificacion,// texto para almacenar en el evento
                        [], //Data old
                        [], //data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idNotificacion, 'status' => $model->estadoNotificacion, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoNotificacion], 'idInitialList' => $dataExplode[1] * 1);
                    
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


    /* Cambia el estado solo a inactivo */
    public function actionChangeStatusInactive()
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
            $transaction = Yii::$app->db->beginTransaction();

            foreach ($request as $value) {

                $dataExplode = explode('|', $value);
                $model = $this->findModel($dataExplode[0]);

                if ($model->estadoNotificacion == Yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoNotificacion = Yii::$app->params['statusTodoText']['Inactivo'];
                } 

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoNotificacion] . ', id: '. $model->idNotificacion.' de la notificación: '.$model->notificacion,// texto para almacenar en el evento
                        [], //Data old
                        [], //data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idNotificacion, 'status' => $model->estadoNotificacion, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoNotificacion], 'idInitialList' => $dataExplode[1] * 1);
                    
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
     * Finds the Notificacion model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Notificacion the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Notificacion::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
