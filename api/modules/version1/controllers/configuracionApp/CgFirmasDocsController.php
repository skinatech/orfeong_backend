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
use api\models\CgFirmasDocs;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;



/**
 * CgFirmasDocsController implements the CRUD actions for CgProveedores model.
 */
class CgFirmasDocsController extends Controller
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
                    'index-one'  => ['GET'],
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

    /**
     * Updates an existing Cg Firmas Doc model.
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

                $id = $request['idCgFirmaDoc'];
                $dataOld = '';
                $dataLog = '';

                $transaction = Yii::$app->db->beginTransaction();                

                # Consulta de relación de proveedores
                $model = CgFirmasDocs::find()
                ->where(['idCgFirmaDoc' => $id])->one();
                $labelModel = $model->attributeLabels();

                if ($model->estadoCgFirmaDoc == Yii::$app->params['statusTodoText']['Activo']) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app','errorFirmaActiva')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $modelUpdate = CgFirmasDocs::find()
                ->where(['estadoCgFirmaDoc' => Yii::$app->params['statusTodoText']['Activo'] ])->all();


                if(!is_null($model)){

                    if(!is_null($modelUpdate)){
                        foreach ($modelUpdate as $key => $modelUp) {
                            foreach ($modelUp as $key => $value) {
                                switch ($key) {
                                    case 'estadoCgFirmaDoc':
                                        $dataOld .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].'; ';
                                    break;
                                    case 'nombreCgFirmaDoc':
                                        $dataOld .= $labelModel[$key].': '.$value.', ';
                                    break;
                                }
                            }
                            $modelUp->estadoCgFirmaDoc = Yii::$app->params['statusTodoText']['Inactivo'];
                            if($modelUp->save()){
                                foreach ($modelUp as $key => $value) {
                            
                                    switch ($key) {
                                        case 'estadoCgFirmaDoc':
                                            $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].'; ';
                                        break;
                                        case 'nombreCgFirmaDoc':
                                            $dataLog .= $labelModel[$key].': '.$value.', ';
                                        break;
                                    }
                                }

                            }else{
                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $modelUp->getErrors(),
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);

                            }
                        }

                    }

                    foreach ($model as $key => $value) {

                        switch ($key) {
                            case 'estadoCgFirmaDoc':
                                $dataOld .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].'; ';
                            break;
                            case 'nombreCgFirmaDoc':
                                $dataOld .= $labelModel[$key].': '.$value.', ';
                            break;
                        }
                    }

                    $model->estadoCgFirmaDoc = Yii::$app->params['statusTodoText']['Activo'];

                    if ($model->save()) {

                        $transaction->commit();

                        foreach ($model as $key => $value) {
                        
                            switch ($key) {
                                case 'estadoCgFirmaDoc':
                                    $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].'; ';
                                break;
                                case 'nombreCgFirmaDoc':
                                    $dataLog .= $labelModel[$key].': '.$value.', ';
                                break;
                            }
                        }
                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            'Se cambió la firma configurada en el sistema, ahora esta activa: ' . $model->nombreCgFirmaDoc, //texto para almacenar en el evento
                            $dataOld, //DataOld
                            $dataLog, //Data
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

                    }else{
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
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => Yii::t('app','resourceNotFound'),
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
     * Finds the CgFirmasDocs model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgFirmasDocs the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgFirmasDocs::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }


    /* Listado de firmas */
    public function actionIndexList()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $dataList = [];
            $dataOne = [];
            $firmas = CgFirmasDocs::find()
            ->orderBy(['nombreCgFirmaDoc' => SORT_ASC])
            ->all();

            foreach ($firmas as $firma) {
                $dataList[] = array(
                    "id" => intval($firma->idCgFirmaDoc),
                    "val" => $firma->nombreCgFirmaDoc,
                );
                if( $firma->estadoCgFirmaDoc == Yii::$app->params['statusTodoText']['Activo'] ){
                    $dataOne['idCgFirmaDoc'] = $firma->idCgFirmaDoc;
                }
            }
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'dataOne' => $dataOne,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        
        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

}
