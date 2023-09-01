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

namespace api\modules\version1\controllers\firmasCertificadas;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\models\CgFirmasCertificadas;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;



/**
 * CgFirmasCertificadasController implements the CRUD actions for CgFirmasCertificadas model.
 */
class CgFirmasCertificadasController extends Controller
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
     * Lists all Cg Firmas Certificadas.
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
            if (is_array($request)) {
                foreach ($request['filterOperation'] as $field) {
                    foreach ($field as $key => $info) {

                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if (isset($info) && $info !== null && trim($info) !== '') {
                                $dataWhere[$key] =  $info;
                            }
                        } else {
                            if (isset($info) && $info !== null && trim($info) != '') {
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Consulta para relacionar la informacion de la cg proveedores de firmas certificadas y sus tablas de relaciones para obtener 100 registros, a partir del filtro
            $relationFirmasCertificadas =  CgFirmasCertificadas::find();

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {
                    case 'nombreCgFirmaCertificada':
                        $relationFirmasCertificadas->andWhere([Yii::$app->params['like'], $field, $value]);
                        break;

                    case 'rutaCgFirmaCertificada':
                        $relationFirmasCertificadas->andWhere(['IN', $field, $value]);
                        break;

                    case 'autorizacionCgFirmaCertificada':
                        $relationFirmasCertificadas->andWhere(['IN', $field, intval($value)]);
                        break;

                    case 'fechaInicial':
                        $relationFirmasCertificadas->andWhere(['>=', 'creacionCgFirmaCertificada', trim($value) . Yii::$app->params['timeStart']]);
                        break;
                    case 'fechaFinal':
                        $relationFirmasCertificadas->andWhere(['<=', 'creacionCgFirmaCertificada', trim($value) . Yii::$app->params['timeEnd']]);
                        break;

                    default:
                        $relationFirmasCertificadas->andWhere([Yii::$app->params['like'], $field, $value]);
                        break;
                }
            }

            # Limite de la consulta
            $relationFirmasCertificadas->orderBy(['idCgFirmaCertificada' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $relationFirmasCertificadas->limit($limitRecords);
            $modelRelation = $relationFirmasCertificadas->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach ($modelRelation as $infoRelation) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idCgFirmaCertificada))
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'                     => $dataBase64Params,
                    'id'                       => $infoRelation->idCgFirmaCertificada,
                    'nombreCgFirmaCertificada' => $infoRelation->nombreCgFirmaCertificada,
                    'rutaCgFirmaCertificada'  => $infoRelation->rutaCgFirmaCertificada,
                    'autorizacionCgFirmaCertificada'  => $infoRelation->autorizacionCgFirmaCertificada,
                    'creacion'                 => $infoRelation->creacionCgFirmaCertificada,
                    'statusText'               => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoCgFirmaCertificada],
                    'status'                   => $infoRelation->estadoCgFirmaCertificada,
                    'rowSelect'                => false,
                    'idInitialList'            => 0
                );
            }

            $formType = HelperDynamicForms::setListadoBD('indexCgFirmasCertificadas');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => [],
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

        # Información del proveedor externo
        $data['idCgFirmaCertificada'] = $model->idCgFirmaCertificada;
        $data['nombreCgFirmaCertificada'] = $model->nombreCgFirmaCertificada;
        $data['rutaCgFirmaCertificada'] = $model->rutaCgFirmaCertificada;
        $data['autorizacionCgFirmaCertificada'] = $model->autorizacionCgFirmaCertificada;
        $data['estadoCgFirmaCertificada'] = $model->estadoCgFirmaCertificada;
        $data['creacionCgFirmaCertificada'] = $model->creacionCgFirmaCertificada;
        /*** Fin Consulta de modelo	***/

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /** Displays a single Cg Firmas Certificadas.
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
                Yii::$app->params['eventosLogText']['View'] . ' id: ' . $id . ' del proveedor de firma certificada: ' . $model->nombreCgFirmaCertificada, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            //Retorna toda la información de los proveedores de firmas certificadas
            $data[] = array('alias' => 'Nombre', 'value' => $model->nombreCgFirmaCertificada);
            $data[] = array('alias' => 'Ruta de Conexión', 'value' => $model->rutaCgFirmaCertificada);
            $data[] = array('alias' => 'Autorización de Conexión', 'value' => $model->autorizacionCgFirmaCertificada);
            $data[] = array('alias' => 'Estado', 'value' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgFirmaCertificada]);
            $data[] = array('alias' => 'Fecha creación', 'value' => $model->creacionCgFirmaCertificada);

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
     * Crea un nuevo registro en el modelo Cg Firmas Certificadas
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

                # Se construye los modelos para el nuevo registro
                $modelCgFirmasCertificadas = new CgFirmasCertificadas();
                $modelCgFirmasCertificadas->attributes = $request;

                if (!$modelCgFirmasCertificadas->save()) {
                    $saveDataValid = false;
                    $errors = $modelCgFirmasCertificadas->getErrors();
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataFirmaCertificada($modelCgFirmasCertificadas);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgFirmasCertificadas", //texto para almacenar en el evento
                        '', // DataOld
                        $data, //Data en string
                        array() //No validar estos campos
                    );
                    /***  Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => [],
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
     * Updates an existing Cg Firmas Certificadas.
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
                $data = '';
                $dataOld = '';

                $transaction = Yii::$app->db->beginTransaction();

                $firmaCertificada = CgFirmasCertificadas::findOne(['idCgFirmaCertificada' => $id]);
                $firmaCertificada->estadoCgFirmaCertificada == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

                $dataOld = 'Id proveedor: ' . $firmaCertificada->idCgFirmaCertificada;
                $dataOld .= ', Nombre Firma Certificada: ' . $firmaCertificada->nombreCgFirmaCertificada;
                $dataOld .= ', Ruta Conexión: ' . $firmaCertificada->rutaCgFirmaCertificada;
                $dataOld .= ', Autorización Conexión: ' . $firmaCertificada->autorizacionCgFirmaCertificada;
                $dataOld .= ', Fecha creación: ' . $firmaCertificada->creacionCgFirmaCertificada;
                $dataOld .= ', Estado proveedor externo: ' . $estado;

                # Agrega nuevos datos al modelo
                $modelFirmasCertificadas = $this->findModel($id);
                $modelFirmasCertificadas->attributes = $request;
                if (!$modelFirmasCertificadas->save()) {
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelFirmasCertificadas->getErrors());
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    # Información de la data actual en string
                    $data = self::dataFirmaCertificada($modelFirmasCertificadas);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla CgFirmasCertificadas", //texto para almacenar en el evento
                        $dataOld, //data old string
                        $data, //data string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion']
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

    /* Cambio de estado del proveedor de firmas certificadas */
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

                if ($model->estadoCgFirmaCertificada == yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoCgFirmaCertificada = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->estadoCgFirmaCertificada = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app', 'statusTodoNumber')[$model->estadoCgFirmaCertificada] . ', id: ' . $model->idCgFirmaCertificada . ' del proveedor: ' . $model->nombreCgFirmaCertificada, // texto para almacenar en el evento
                        [], //DataOld
                        [], //Data en string
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idCgFirmaCertificada, 'status' => $model->estadoCgFirmaCertificada, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgFirmaCertificada], 'idInitialList' => $dataExplode[1] * 1);
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
     * Finds the CgFirmasCetificadas model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return CgFirmasCetificadas the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = CgFirmasCertificadas::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    /**
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que se almacena]
     */
    protected function dataFirmaCertificada($request)
    {
        #Consulta para obtener los datos del estado y fecha del proveedor
        $firmaCertificada = CgFirmasCertificadas::findOne(['idCgFirmaCertificada' => $request['idCgFirmaCertificada']]);
        $firmaCertificada->estadoCgFirmaCertificada == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';

        $data = 'Id proveedor: ' . $firmaCertificada->idCgFirmaCertificada;
        $data .= ', Nombre Firma Certificada: ' . $firmaCertificada->nombreCgFirmaCertificada;
        $data .= ', Ruta Conexión: ' . $firmaCertificada->rutaCgFirmaCertificada;
        $data .= ', Autorización Conexión: ' . $firmaCertificada->autorizacionCgFirmaCertificada;
        $data .= ', Fecha creación: ' . $firmaCertificada->creacionCgFirmaCertificada;
        $data .= ', Estado: ' . $estado;

        return $data;
    }

    /** Se recibe como parametro el $documento, el cual es asociado al documento que se va a firmar **/
    public function RutinaFirmarDocumento($documento){
        echo 'java -jar AndesSCDFirmador.jar --metodofirma ws --formatofirma pdf --login JuMa --password DgCWtbCEYg --tipodocumento 1 --documento 79436464 --pinfirma DgCWtbCEYg --entrada Electronica.pdf --formatoentrada archivo --salida Viernes.pdf --formatosalida archivo --visible true --imagenFirma Nevado.png --ubicacion 200,100,100,50 --pagina 1 --tamanofuentefirma 8 --aplicaqr true --aplicaqr false --textoqr "Pruebas totales" --ubicacionqr 450,50,125,125 --proteger false --passpdf xxx --test true pause';
    }
}
