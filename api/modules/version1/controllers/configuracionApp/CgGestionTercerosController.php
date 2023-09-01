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

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;

use yii\helpers\FileHelper;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperRadicacion;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperGenerateExcel;
use api\components\HelperLoads;
use api\components\HelperFiles;

use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use api\models\CgGeneral;

use common\models\User;
use api\models\Clientes;
use api\models\ClientesCiudadanosDetalles;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\Roles;
use api\models\TiposIdentificacion;
use api\models\TiposPersonas;

use api\components\HelperQueryDb;


use PhpOffice\PhpSpreadsheet\IOFactory;
use api\modules\version1\controllers\correo\CorreoController;

/**
 * RadiAgendaController implements the CRUD actions for RadiAgendaRadicados model.
 */
class CgGestionTercerosController extends Controller
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
                    'index' => ['GET'],
                    'index-one'  => ['GET'],
                    'view'  => ['GET'],
                    'change-status'  => ['PUT'],
                    'create'  => ['POST'],
                    'update'  => ['PUT'],
                    'get-general-list' => ['GET']
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
     * Lists all RadiRadicados models.
     * @return mixed
     */
    public function actionIndex($request)
    {
        /** Validar permisos del módulo */
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

            //Lista de radicados
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
            
            // Consulta para relacionar la informacion de dependencias y obtener 100 registros, a partir del filtro
            $clientes = Clientes::find();
            //->leftJoin('clientesCiudadanosDetalles', ' clientesCiudadanosDetalles . idCliente  =  clientes . idCliente ');
            $clientes = HelperQueryDb::getQuery('leftJoin', $clientes, 'clientesCiudadanosDetalles', ['clientes' => 'idCliente', 'clientesCiudadanosDetalles' => 'idCliente']);
            //Se reitera $dataWhere para solo obtener los campos con datos

            foreach($dataWhere as $field => $value){
                switch ($field) {

                    case 'idTipoPersona': 
                        $clientes->andWhere(['IN', 'clientes.' . $field, $value]);
                    break;
                    case 'idNivelGeografico1': 
                        $clientes->andWhere(['IN', 'clientes.' . $field, $value]);
                    break;
                    case 'idNivelGeografico2': 
                        $clientes->andWhere(['IN', 'clientes.' . $field, $value]);
                    break;
                    case 'idNivelGeografico3': 
                        $clientes->andWhere(['IN', 'clientes.' . $field, $value]);
                    break;
                    case 'estadoCliente': 
                        $clientes->andWhere(['IN', 'clientes.' . $field, intval($value)]);
                    break;
                    case 'fechaInicial':
                        $clientes->andWhere(['>=', 'clientes.creacionCliente', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $clientes->andWhere(['<=', 'clientes.creacionCliente', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $clientes->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                }
            }                
            //Limite de la consulta

            $clientes->limit($limitRecords);
            $modelClientes= $clientes->orderBy(['clientes.idCliente' => SORT_DESC])->all(); // Orden descendente para ver los últimos registros creados

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelClientes = array_reverse($modelClientes);
            
            foreach($modelClientes as $cliente){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($cliente->idCliente ))
                );

                $nivelGeografico1 = NivelGeografico1::findOne(['nivelGeografico1' => $cliente->idNivelGeografico1]);
                $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $cliente->idNivelGeografico2]);
                $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $cliente->idNivelGeografico3]);

                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $cliente->idCliente,
                    'nombreCliente' => $cliente->nombreCliente,
                    'identificacion' =>  $cliente->numeroDocumentoCliente,
                    'tipoPersona' => $cliente->tipoPersona->tipoPersona,
                    'correoElectronico' => $cliente->correoElectronicoCliente,
                    'direccionCliente' => $cliente->direccionCliente,
                    'telefonoCliente' => $cliente->telefonoCliente ?? 'N/A',
                    'codigoSap' => $cliente->codigoSap,
                    'creacionCliente' => date("Y-m-d", strtotime($cliente->creacionCliente)),
                    'nivelGeografico1' => $nivelGeografico1->nomNivelGeografico1,
                    'nivelGeografico2' => $nivelGeografico2->nomNivelGeografico2,
                    'nivelGeografico3' => $nivelGeografico3->nomNivelGeografico3,
                    'status' => $cliente->estadoCliente,
                    'statusText' =>  Yii::t('app', 'statusTodoNumber')[$cliente->estadoCliente], 
                    'rowSelect' => false,
                    'idInitialList' => 0
                ];
            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgTerceros'); 

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
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     *  Carga lista para crear Clientes / ClientesCiudadanos
     * 
     * @return array data
     */
    public function actionGetGeneralList($request)
    {

        //*** Inicio desencriptación GET ***//
        if (!empty($request)) {
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
        }
        //*** Fin desencriptación GET ***//

        # Listado Tipo identificacion
        $listTiposIdentificacion = TiposIdentificacion::find()->where(['estadoTipoIdentificacion' => Yii::$app->params['statusTodoText']['Activo']])->all();
        foreach ($listTiposIdentificacion as $row) {
            $dataTiposIdentificacion[] = array(
                "id" => (int) $row['idTipoIdentificacion'],
                "val" => $row['nombreTipoIdentificacion'],
            );
        }

        # Listado TiposPersonas
        $listPersonas = TiposPersonas::find()->where(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']])->all();
        foreach ($listPersonas as $row) {
            $dataTiposPersonas[] = array(
                "id" => (int) $row['idTipoPersona'],
                "val" => $row['tipoPersona'],
            );
        }
        # Listado Generos 
        $generoClienteCiudadanoDetalle = Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'];
        foreach($generoClienteCiudadanoDetalle as $key => $value){
            $dataGenero[] = [
                'id' => $key,
                'val' => $value
            ];
        }
        # Listado Rango de edad 
        $rangoEdadClienteCiudadanoDetalle = Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'];
        foreach($rangoEdadClienteCiudadanoDetalle as $key => $value){
            $dataRangoEdad[] = [
                'id' => $key,
                'val' => $value
            ];
        }
        # Listado Vulnerabilidad 
        $vulnerabilidadClienteCiudadanoDetalle = Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'];
        foreach($vulnerabilidadClienteCiudadanoDetalle as $key => $value){
            $dataVulnerabilidad[] = [
                'id' => $key,
                'val' => $value
            ];
        }
        # Listado Etnia 
        $etniaClienteCiudadanoDetalle = Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'];
        foreach($etniaClienteCiudadanoDetalle as $key => $value){
            $dataEtnia[] = [
                'id' => $key,
                'val' => $value
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataTiposIdentificacion' => $dataTiposIdentificacion ?? [],
            'dataTiposPersonas'     => $dataTiposPersonas ?? [],
            'dataGenero'            => $dataGenero ?? [],
            'dataRangoEdad'         => $dataRangoEdad ?? [],
            'dataVulnerabilidad'    => $dataVulnerabilidad ?? [],
            'dataEtnia'             => $dataEtnia ?? [],
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Updates an existing Gestion de Terceros model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionChangeStatus()
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
                    $model = Clientes::findOne(['idCliente' => $dataExplode[0]]);

                    # Valores anteriores del modelo
                    $labels = $model->attributeLabels();
                    $dataOld = $labels['estadoCliente'] . ': ' . Yii::$app->params['statusTodoNumber'][$model->estadoCliente];

                    /** Definir el nuevo estado según el stado actual del cliente */
                    if ($model->estadoCliente == yii::$app->params['statusTodoText']['Activo']) {
                        $statusNew = Yii::$app->params['statusTodoText']['Inactivo'];
                    } else {
                        $statusNew = Yii::$app->params['statusTodoText']['Activo'];
                    }
                    
                    $model->estadoCliente = $statusNew;

                    if ($model->save()) {

                        # Valores nuevos del modelo
                        $labels = $model->attributeLabels();
                        $dataNew = $labels['estadoCliente'] . ': ' . Yii::$app->params['statusTodoNumber'][$model->estadoCliente];

                        /***  Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCliente] . ', id: '. $model->idCliente.' del cliente: '.$model->nombreCliente,// texto para almacenar en el evento
                            $dataOld, //DataOld
                            $dataNew, //Data
                            array() //No validar estos campos
                        );
                        /***  Fin log Auditoria   ***/

                        # Inactivar / Activar usuario externo
                        if(isset($model->clientesCiudadanosDetalles)){

                            $clietesDetalles = ClientesCiudadanosDetalles::findOne(['idCliente' => $model->idCliente]);
                            $userModel = User::findOne($clietesDetalles->idUser);

                            # Valores nuevos del modelo
                            $labels = $clietesDetalles->attributeLabels();
                            $dataOld = $labels['estadoClienteCiudadanoDetalle'] . ': ' . Yii::$app->params['statusTodoNumber'][$clietesDetalles->estadoClienteCiudadanoDetalle];

                            if ($statusNew == yii::$app->params['statusTodoText']['Inactivo']) {
                                $clietesDetalles->estadoClienteCiudadanoDetalle = $statusNew;
                                $userModel->status = $statusNew;
                                
                                $subject = 'subjectInactivacion';
                                $headMailText = Yii::t('app', 'headMailInactivacion', [ 
                                    'username'  =>  $clietesDetalles->user->username,
                                ]);
                                $textBody  = Yii::t('app', 'textBodyInactivacion', [
                                    'username' =>  $clietesDetalles->user->username,
                                ]);

                            } else {
                                $clietesDetalles->estadoClienteCiudadanoDetalle = $statusNew;
                                $userModel->status = $statusNew;
                                
                                $subject = 'subjectActivacion';
                                $headMailText = Yii::t('app', 'headMailActivacion', [ 
                                    'username'  =>  $clietesDetalles->user->username,
                                ]); 
                                $textBody  = Yii::t('app', 'textBodyActivacion', [
                                    'username' =>  $clietesDetalles->user->username,
                                ]);
                            }

                            if ($clietesDetalles->save()) {

                                if (!$userModel->save()) {
                                    $errs[] = $userModel->getErrors();
                                    $saveDataValid = false;
                                } else {
                                    $labels = $model->attributeLabels();
                                    $dataNew = $labels['estadoCliente'].': '.Yii::$app->params['statusTodoNumber'][$model->estadoCliente];
    
                                    # Envia la notificación de correo electronico al usuario de tramitar
                                    $bodyMail = 'radicacion-html';    
                                    $envioCorreo = CorreoController::envioAdjuntos($clietesDetalles->user->email, $headMailText, $textBody, $bodyMail, $modelAttach = null, $link = '', $subject, $nameButtonLink = 'buttonLinkRadicado', $buttonDisplay = false);
                                }

                            }else {
                                $errs[] = $clietesDetalles->getErrors();
                                $saveDataValid = false;
                            }

                        }

                        $dataResponse[] = array(
                            'id' => $model->idCliente, 'idInitialList' => $dataExplode[1] * 1,
                            'status' => $model->estadoCliente, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCliente],
                        );

                    } else {
                        $errs[] = $model->getErrors();
                        $saveDataValid = false;
                    }
                }

                if ($saveDataValid) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $dataResponse,
                        'errs' => $errs ?? [],
                        'envioCorreo' => $envioCorreo ?? 'null',
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $errs,
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
        }else{
            
            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
            //*** Inicio desencriptación POST, PUT ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend, true);
            $request = $decrypted['request'];

            $dataResponse = $this->returnSameDataChangeStatus($request);
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'dataStatus' => $dataResponse,
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Lists One Gestion de Terceros models.
     * @return mixed
     */
    private function returnSameDataChangeStatus($data)
    {
        $dataResponse = [];
        foreach ($data as $value) {
            $dataExplode = explode('|', $value);
            $model = Clientes::find()->select(['estadoCliente'])->where(['idCliente' => $dataExplode[0] ])->one();
            if ($model != null) {
                $dataResponse[] = array(
                    'id' => $dataExplode[0], 
                    'idInitialList' => $dataExplode[1] * 1,
                    'status' => $model['estadoCliente'], 
                    'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCliente']]
                );
            } else {
                throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
            }
        }
        return $dataResponse;
    }

    /**
     * Lists One Gestion de Terceros models.
     * @return mixed
     */
    public function actionIndexOne($request){

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

        $modelClientes = Clientes::findOne(['idCliente' => $id]);

        if(isset($modelClientes)){
            
            $data['id'] = $modelClientes->idCliente;
            $data['idTipoPersona'] = $modelClientes->idTipoPersona;
            $data['nombreCliente'] = $modelClientes->nombreCliente;
            $data['numeroDocumentoCliente'] = $modelClientes->numeroDocumentoCliente;
            $data['correoElectronicoCliente'] = $modelClientes->correoElectronicoCliente;
            $data['direccionCliente'] = $modelClientes->direccionCliente;
            $data['telefonoCliente'] = $modelClientes->telefonoCliente;
            $data['codigoSap'] = $modelClientes->codigoSap;
            $data['estadoCliente'] = $modelClientes->estadoCliente;
            $data['creacionCliente'] = $modelClientes->creacionCliente;
            $data['idNivelGeografico3'] = $modelClientes->idNivelGeografico3;
            $data['idNivelGeografico2'] = $modelClientes->idNivelGeografico2;
            $data['idNivelGeografico1'] = $modelClientes->idNivelGeografico1;

            // Valida si tiene genero para que el formulario de caracterización se muestre
            if( $modelClientes->clientesCiudadanosDetalles ){
                $data['caracterizacion'] = 1;
                $data['idUser'] = $modelClientes->clientesCiudadanosDetalles->idUser;
                $data['idTipoIdentificacion'] = $modelClientes->clientesCiudadanosDetalles->idTipoIdentificacion;
                $data['generoClienteCiudadanoDetalle'] = $modelClientes->clientesCiudadanosDetalles->generoClienteCiudadanoDetalle;
                $data['rangoEdadClienteCiudadanoDetalle'] = $modelClientes->clientesCiudadanosDetalles->rangoEdadClienteCiudadanoDetalle;
                $data['vulnerabilidadClienteCiudadanoDetalle'] = $modelClientes->clientesCiudadanosDetalles->vulnerabilidadClienteCiudadanoDetalle;
                $data['etniaClienteCiudadanoDetalle'] = $modelClientes->clientesCiudadanosDetalles->etniaClienteCiudadanoDetalle;
                $data['estadoClienteCiudadanoDetalle'] = $modelClientes->clientesCiudadanosDetalles->estadoClienteCiudadanoDetalle;
            }
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists One Gestion de Terceros models.
     * @return mixed
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
            $dataList = [];
            $modelClientes = Clientes::findOne(['idCliente' => $id]);

            if(isset($modelClientes)){

                $nivelGeografico1 = NivelGeografico1::findOne(['nivelGeografico1' => $modelClientes->idNivelGeografico1]);
                $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $modelClientes->idNivelGeografico2]);
                $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $modelClientes->idNivelGeografico3]);

                $terceros = $modelClientes->clientesCiudadanosDetalles;

                $dataList[] = ['alias' => 'filingModule.NombreRazonSocial', 'value' => $modelClientes->nombreCliente];
                # Agrego tipo de identidad por "orden del view" si existen en clientesCiudadanosDetalles 

                if(isset($modelClientes->clientesCiudadanosDetalles)){
                    $dataList[] = ['alias' => 'Tipo de identificación', 'value' => $terceros->tipoIdentificacion['nombreTipoIdentificacion']];
                }
                $dataList[] = ['alias' => 'Documento Identificación',  'value' => $modelClientes->numeroDocumentoCliente];
                $dataList[] = ['alias' => 'Tipo de persona',      'value' => $modelClientes->tipoPersona->tipoPersona];
                $dataList[] = ['alias' => 'Direccion',            'value' => $modelClientes->direccionCliente];
                $dataList[] = ['alias' => 'Correo electrónico',   'value' => $modelClientes->correoElectronicoCliente];
                $dataList[] = ['alias' => 'Telefono',             'value' => $modelClientes->telefonoCliente];
                $dataList[] = ['alias' => 'País',                 'value' => $nivelGeografico1->nomNivelGeografico1];
                $dataList[] = ['alias' => 'Departamento',         'value' => $nivelGeografico2->nomNivelGeografico2];
                $dataList[] = ['alias' => 'Municipio',            'value' => $nivelGeografico3->nomNivelGeografico3];
                $dataList[] = ['alias' => 'Código SAP',           'value' => $modelClientes->codigoSap];
                # Agrego datos de caracterizacion si existen
                if(isset($modelClientes->clientesCiudadanosDetalles)){
                    $dataList[] = ['alias' => 'Género',          'value' => Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'][$terceros->generoClienteCiudadanoDetalle]];
                    $dataList[] = ['alias' => 'Rango de edad',   'value' => Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'][$terceros->rangoEdadClienteCiudadanoDetalle]];
                    $dataList[] = ['alias' => 'Vulnerabilidad',  'value' => Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'][$terceros->vulnerabilidadClienteCiudadanoDetalle]]; 
                    $dataList[] = ['alias' => 'Etnia',           'value' => Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'][$terceros->etniaClienteCiudadanoDetalle]]; 
                }
                $dataList[] = ['alias' => 'Estado', 'value' => Yii::t('app', 'statusTodoNumber')[$modelClientes->estadoCliente]];
            }

            $dataLog = self::logDataCliente($modelClientes);
            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                true,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'] . " la tabla gestion de terceros, Id: " . $modelClientes->idCliente.' del usuario: '.$modelClientes->nombreCliente,//texto para almacenar en el evento
                '',
                $dataLog, //Data
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

    /**
     * Creates a new Gestion de Terceros model.
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

                $transaction = Yii::$app->db->beginTransaction();
                $envioCorreo = '';

                # Se valida que si llega "0000" se debe asignar un consecutivo al documento de identidad
                if($request['numeroDocumentoCliente'] == '0000'){
                    # Configuración general para obtener el consecutivo a asignar
                    $configGeneral = ConfiguracionGeneralController::generalConfiguration();
                    $numeroDocConsecutivo = $configGeneral['data']['iniConsClienteCgGeneral'] + 1;
                    $numeroDocConsecutivo = str_pad($numeroDocConsecutivo,11,'0', STR_PAD_LEFT);
                    $request['numeroDocumentoCliente'] = (string) $numeroDocConsecutivo;

                    # Una vez asignado el numero se actualiza el valor de la tabla
                    $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);
                    $modelCgGeneral->iniConsClienteCgGeneral = $request['numeroDocumentoCliente'];
                    $modelCgGeneral->save();
                }

                # Comprobación si el documento de identidad ya existe, no lo permite guardar
                $modelClienteExiste = Clientes::findOne(['numeroDocumentoCliente' => $request['numeroDocumentoCliente']]);

                if($modelClienteExiste){
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'data' => ['error' => [Yii::t('app', 'ClienteExiste',[
                            'nombCliente' => $modelClienteExiste['nombreCliente']
                        ])]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);  
                }
                
                # Datos para la tabla clientes 830129050-5
                $model_clientes = new Clientes;
                $model_clientes->attributes = $request;

                if ($model_clientes->correoElectronicoCliente == null || trim($model_clientes->correoElectronicoCliente) == '') {
                    $model_clientes->correoElectronicoCliente = '';
                }

                if(!$model_clientes->save()){                    
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'data' => $model_clientes->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);  
                }

                # Data para el log del cliente
                $data = self::logDataCliente($request);

                # Con datos con el formulario de caracterización
                if($request['caracterizacion']){

                    # Datos para la tabla user
                    $model_user = new User;   
                    $model_user->username = (string) $model_clientes->numeroDocumentoCliente;
                    $model_user->email = $model_clientes->correoElectronicoCliente;
    
                    $roles = (object) Roles::find()->where([Yii::$app->params['like'],'nombreRol', Yii::$app->params['RolUserPqrs']])->one();
    
                        if(!isset($roles)){
                            $transaction->rollBack();  
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','validExternalRole'),
                                'data' => $roles->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);   
                        }

                    $password = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    
                    # Configuración general del id dependencia pqrs
                    $configGeneral = ConfiguracionGeneralController::generalConfiguration();

                    # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                    if($configGeneral['status']){
                        $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];
                    } else {

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [$configGeneral['message']]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }


                    $model_user->idRol = $roles['idRol'];
                    $model_user->idGdTrdDependencia = $idDependenciaUserPqrs;
                    $model_user->idUserTipo = Yii::$app->params['tipoUsuario']['Externo'];
                    $model_user->setPassword($password);
                    $model_user->generateAuthKey();
                    $model_user->accessToken = $model_user->generateAccessToken();
                    $model_user->intentos = 0; // Yii::$app->params['maxIntentosLogueo'];
                    $model_user->fechaVenceToken = date("Y-m-d",strtotime(date("Y-m-d")."+ ".Yii::$app->params['TimeExpireToken']." days"));
                    $model_user->ldap = false;
                    $model_user->status = Yii::$app->params['statusTodoText']['Activo'];
    
                        if (!$model_user->save()) { 
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'data' => $model_user->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);  
                        }
    
                    # Datos para la tabla clientesCiudadanosDetalles
                    $model_clientes_detalles = new ClientesCiudadanosDetalles;  
    
                    $model_clientes_detalles->idCliente = $model_clientes->idCliente;
                    $model_clientes_detalles->idUser = $model_user->id;
                    $model_clientes_detalles->idTipoIdentificacion =  Yii::$app->params['clientesciudadanosdetalles']['idTipoIdentificacion'];
                    $model_clientes_detalles->attributes = $request;
                    $model_clientes_detalles->telefonoFijoClienteCiudadanoDetalle = $request->telefonoCliente ?? '';
    
                        if(!$model_clientes_detalles->save()){ 
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'data' => $model_clientes_detalles->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);                     
                        }

                    $datalog = self::logDataClienteCiudadano($model_clientes_detalles, $model_clientes);
                    // data log clientes caracterizacion + clientes 
                    $datalog = $data.'/'.$datalog;

                    /***  Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        "Se creó la información del ciudadano: ".$model_clientes['nombreCliente']." de forma correcta.", 
                        '', // DataOld
                        $datalog, //Data en string
                        array() //No validar estos campos
                    ); 
                    /***  Fin log Auditoria   ***/

                    /* =========================== ENVIO CORREO BIENVENIDA =========================== */
                    # Envio de correo al usuario tramitador Pqrs
                    $headMailText  = Yii::t('app', 'headMailRegistroTerceros',[
                        'nombreCiudadano' => $model_clientes->nombreCliente
                    ]);       

                    $textBody  = Yii::t('app', 'textBodyRegistroTerceros',[
                        'username' => $model_user->username,
                        'password' => $password
                    ]);

                    $bodyMail = 'radicacion-html'; 
                    $subject =  Yii::t('app', 'BienvenidoA'); 
        
                    $envioCorreo = CorreoController::personalizePassword($model_user->email, $headMailText, $textBody, $bodyMail, $subject);
                    /* =========================== ENVIO CORREO BIENVENIDA =========================== */


                } else {  // Sino se almacena la información del cliente en el log

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        "Se creó el cliente: " . $model_clientes->nombreCliente, 
                        '', // DataOld
                        $data, //Data en string
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                }
                
                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','successSave'),
                    'data' => [],
                    'correo' => $envioCorreo,
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
     * Creates a new Gestion de Terceros model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionUpdate()
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

                $transaction = Yii::$app->db->beginTransaction();

                # Se valida que si llega "0000" se debe asignar un consecutivo al documento de identidad
                if($request['numeroDocumentoCliente'] == '0000'){
                    # Configuración general para obtener el consecutivo a asignar
                    $configGeneral = ConfiguracionGeneralController::generalConfiguration();
                    $numeroDocConsecutivo = $configGeneral['data']['iniConsClienteCgGeneral'] + 1;
                    $numeroDocConsecutivo = str_pad($numeroDocConsecutivo,11,'0', STR_PAD_LEFT);
                    $request['numeroDocumentoCliente'] = (string) $numeroDocConsecutivo;

                    # Una vez asignado el numero se actualiza el valor de la tabla
                    $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);
                    $modelCgGeneral->iniConsClienteCgGeneral = $request['numeroDocumentoCliente'];
                    $modelCgGeneral->save();
                }

                # Datos para la tabla clientes
                $model_clientes = Clientes::findOne(['idCliente' => $request['id']]);
                $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $request['id']]);
                
                if($modelClienteDetalle){

                    if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username){
                        $transaction->rollBack();
    
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'data' => ['error' => [Yii::t('app', 'noUpdateUserPqrs')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);  
                    }
                }

                # Comprobación si el documento de identidad ya existe no lo permite guardar
                $modelClienteExiste = Clientes::findOne(['numeroDocumentoCliente' => $request['numeroDocumentoCliente']]);
                if($modelClienteExiste){

                    if($model_clientes->idCliente != $modelClienteExiste->idCliente){
                        $transaction->rollBack();

                       Yii::$app->response->statusCode = 200;
                        $response = [
                            'data' => ['error' => [Yii::t('app', 'ClienteExiste',[
                                'nombCliente' => $modelClienteExiste['nombreCliente']
                            ])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);  
                    }

                    $modelClienteDetalle = ClientesCiudadanosDetalles::findOne(['idCliente' => $request['id']]);
                    if($modelClienteDetalle){

                        if(Yii::$app->params['userPublicPage'] == $modelClienteDetalle->user->username){
                            $transaction->rollBack();
            
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'data' => ['error' => [Yii::t('app', 'errorUpdateAnonimo')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }

                // dataOld para el log
                $dataOld = self::logDataCliente($model_clientes);

                $model_clientes->attributes = $request;
                
                    if(!$model_clientes->save()){ 
                        
                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'data' => $model_clientes->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);  
                    }

                # Data actual del cliente para el log
                $data = self::logDataCliente($request);

                        
                if($request['caracterizacion']){

                    # Datos para la tabla clientesCiudadanosDetalles
                    $model_clientes_detalles = ClientesCiudadanosDetalles::findOne(['idCliente' => $model_clientes->idCliente]);

                    // dataOld para el log
                    $datalogOld = self::logDataClienteCiudadano($model_clientes_detalles, $model_clientes);

                    $model_clientes_detalles->attributes = $request;
    
                        if(!$model_clientes_detalles->save()){ 
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'data' => $model_clientes_detalles->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);                     
                        }

                    $datalog = self::logDataClienteCiudadano($model_clientes_detalles, $model_clientes);

                    // data log clientes caracterizacion + clientes 
                    $datalogOld = $dataOld.'/'.$datalogOld;
                    $datalog = $data.'/'.$datalog;
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        "Se actualizó la información del formulario de caracterización del ciudadano: ".$model_clientes['nombreCliente'], //texto para almacenar en el evento
                        $datalogOld, // DataOld
                        $datalog, //Data en string
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                } else {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        "Se actualizó la información del cliente: ".$model_clientes->nombreCliente, //texto para almacenar en el evento
                        $dataOld, // DataOld
                        $data, //Data en string
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/
                }

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','successUpdate'),
                    'data' => [],
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


    /** Funcion que permite descargar la plantilla
     * del formato de carga masiva de terceros
     **/
    public function actionDownloadFormatTerceros() {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);   
            
        // Nombre del archivo para generar
        $fileName = 'formato-terceros-' . Yii::$app->user->identity->id . '.xlsx';

        # Planilla del formato regional
        $regionalFormat = HelperLoads::getTercerosFormat();    

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
                    Yii::$app->params['eventosLogText']['DownloadFile'].' formato masivo de terceros', //texto para almacenar en el evento
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
     * Permite cargar el archivo .xlsx para la carga masiva de regionales, se establece el directorio
     * donde se va a guardar el archivo.
     * @return UploadedFile el archivo cargado
     */
    public function actionLoadMassiveFileTerceros()
    {    

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $rutaOk = true; // ruta base de la carpeta existente

                //Ruta de ubicacion de la carpeta donde se almacena el excel
                $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomTercerosFolder'] . '/';

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

                $id = Yii::$app->user->identity->id;

                # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar
                $nomArchivo = "tmp_carga_masiva_terceros_" . $id . "." . $fileUpload->extension;
                $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo);

                /*** Validar si el archivo fue subido ***/
                if ($uploadExecute) {

                    /*** log Auditoria ***/
                    // if(!Yii::$app->user->isGuest) {
                        //     HelperLog::logAdd(
                        //         false,
                        //         Yii::$app->user->identity->id, //Id user
                        //         Yii::$app->user->identity->username, //username
                        //         Yii::$app->controller->route, //Modulo
                        //         Yii::$app->params['eventosLogText']['FileUpload']. ' del formato terceros', // texto para almacenar en el evento
                        //         [],
                        //         [], //Data
                        //         array() //No validar estos campos
                        //     );
                        // }
                    /***  Fin log Auditoria   ***/

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

            # Listados idS
            $dataListIds = [];
            # Recordar que un documento puede tener múltiples hojas
            # obtener conteo e iterar
            $indiceHoja = 0;

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);
            $maxCell = $hojaActual->getHighestRowAndColumn();
            $hojaActualArray = $hojaActual->rangeToArray("A1:H". $maxCell['row']);

            # Titulos de las columnas que se mostraran en los errores
            $title = array(
                'Nombre completo'        => 'A', 
                'Tipo de persona'        => 'B', 
                'Documento de identidad' => 'C', 
                'Correo electrónico'     => 'D',
                'Municipio '             => 'E',
                'Dirección'              => 'F',
                'Código SAP'             => 'G',
                'Telefono'               => 'H',
            );

            $titleText = array(
                'Nombre completo', 'Tipo de persona', 'Documento de identidad',  'Correo electrónico' , 'Municipio' , 'Dirección', 'Código SAP', 'Telefono'
            ); 

            // Cantidad de columnas en la cabecera
            $cantCabecera = count($title);

            /***
               Array de datos existentes en base de datos
            ***/
            # Listado de clientes
            $clientes = []; $emailsClientes = []; $document = []; $address = [];
            $modelClientes = Clientes::findAll(['estadoCliente' =>  Yii::$app->params['statusTodoText']['Activo']]);
                foreach($modelClientes as $dataClientes){
                    $clientes[$dataClientes['nombreCliente']] = $dataClientes['idCliente'];
                    $document[$dataClientes['numeroDocumentoCliente']] = $dataClientes['idCliente'];
                    $address[$dataClientes['direccionCliente']] = $dataClientes['idCliente'];
                    $emailsClientes[$dataClientes['correoElectronicoCliente']] = $dataClientes['idCliente'];
                }
            # Listado Tipo de Persona
            $tiposPersonas = [];
            $modelTiposP = TiposPersonas::findAll(['estadoPersona' =>  Yii::$app->params['statusTodoText']['Activo']]);
            foreach($modelTiposP as $dataTipoPersona){
                $tiposPersonas[$dataTipoPersona['tipoPersona']] = $dataTipoPersona['idTipoPersona'];
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

                    $nameClienteCell    = $row[$this->numberByLetter('A')];
                    $tipoPersonaCell    = $row[$this->numberByLetter('B')];
                    $docIdentidadCell   = $row[$this->numberByLetter('C')];
                    $correoClienteCell  = $row[$this->numberByLetter('D')];
                    $municipalityCell   = $row[$this->numberByLetter('E')];   
                    $direccionCell      = $row[$this->numberByLetter('F')];
                    $codigoSapCell      = $row[$this->numberByLetter('G')];
                    $telefonoCell       = $row[$this->numberByLetter('H')];

                    if(($nameClienteCell == '') && ($tipoPersonaCell == '') && ($docIdentidadCell == '') && ($correoClienteCell == '')  && ($municipalityCell == '')  && ($direccionCell == '') && ($telefonoCell == '')
                    ){
                        $ignorarFilas[] = $i; 
                        //$dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCells');

                    } else {  

                        # Se valida que el nombre del cliente no exista en D.B y cumpla su longitud y no este vacio
                        if (!is_null($nameClienteCell) && $nameClienteCell != '') {
                                       
                            # Nombre del cliente
                            if (isset($clientes[$nameClienteCell])) {

                                $nomColumna = $titleText[0];
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrNameExists', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $nameClienteCell
                                ]);
                            }

                            $length = 150;
                            if(strlen($nameClienteCell) > $length ){
                                $nomColumna = $titleText[0];
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }

                        } else {
                            $nomColumna = $titleText[0];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                        # Se valida que el tamaño del documento y no este vacia
                        if (!is_null($docIdentidadCell) && $docIdentidadCell != '') {

                            if (isset($document[$docIdentidadCell])) {

                                $nomColumna = $titleText[2];
                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrDocumentExists', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $docIdentidadCell
                                ]);
                            }

                            $length = 15;
                            if(strlen($docIdentidadCell) > $length ){
                                
                                $nomColumna = $titleText[2];
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }   

                        } else { 
                            $nomColumna = $titleText[2];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                       
                        # Se valida que el tipo persona exista en D.B y no este vacia
                        if (!is_null($tipoPersonaCell) && $tipoPersonaCell != '') {
              
                            $nomColumna = $titleText[1];
                            if (!isset($tiposPersonas[$tipoPersonaCell])) {  

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrTypeNotExist', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $tipoPersonaCell
                                ]);
                            } 

                        } else {
                            $nomColumna = $titleText[1];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                        # Se valida que el correo del cliente que no exista en D.B, que cumpla su longitud y no este vacio
                        if (!is_null($correoClienteCell) && $correoClienteCell != '') {
                        
                            $nomColumna = $titleText[3];
                            $length = 80;

                            // Valida si el correo es (--) quiere decir que viene sin correo y no se debe validar
                            if($correoClienteCell != '--'){

                                if (isset($emailsClientes[$correoClienteCell])) {
                                    $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrEmailExists', [
                                        'fila'      => $fila,
                                        'nomColumna'=> $nomColumna,
                                        'valorCell' => $correoClienteCell
                                    ]);

                                } elseif(strlen($correoClienteCell) > $length ){
                                    $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                        'nomColumna'    => $nomColumna,
                                        'length'        => $length
                                    ]);
                                }
                            }

                        } else {
                            $nomColumna = $titleText[3];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                        # Se valida que el nombre del municipio exista en D.B y no este vacio
                        if (!is_null($municipalityCell) && $municipalityCell != '') {
              
                            $nomColumna = $titleText[4];
                            if (!isset($municipalities[$municipalityCell])) {  

                                $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrNameNotExist', [
                                    'fila'      => $fila,
                                    'nomColumna'=> $nomColumna,
                                    'valorCell' => $municipalityCell
                                ]);

                            } 

                        } else {
                            $nomColumna = $titleText[4];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                        # Se valida la direccion, que cumpla su longitud y no este vacio
                        if (!is_null($direccionCell) && $direccionCell != '') {
              
                            $nomColumna = $titleText[5];
                            $length = 150;

                            // if (isset($address[$direccionCell])) {
                            //     $dataListErr['Fila ' . $fila][] =  Yii::t('app', 'dataListErrAddressExists', [
                            //         'fila'      => $fila,
                            //         'nomColumna'=> $nomColumna,
                            //         'valorCell' => $direccionCell
                            //     ]);

                            // } else
                            if(strlen($direccionCell) > $length ){
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }
                             
                        } else {
                            $nomColumna = $titleText[5];
                            $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrEmptyCell',[
                                'fila'       => $fila,
                                'nomColumna' => $nomColumna
                            ]);
                        }

                        # Se valida que el código SAP no supere el limite de longitud
                        if (!is_null($codigoSapCell) && $codigoSapCell != '') {

                            $nomColumna = $titleText[6];
                            $length = 20;

                            if (strlen($codigoSapCell) > $length ){
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }
                        }

                        # Se valida que el telefono no supere el limite de longitud
                        if (!is_null($telefonoCell) && $telefonoCell != '') {

                            $nomColumna = $titleText[7];
                            $length = 15;

                            if (strlen($telefonoCell) > $length ){
                                $dataListErr['Fila ' . $fila][] = Yii::t('app', 'dataListErrLength', [
                                    'nomColumna'    => $nomColumna,
                                    'length'        => $length
                                ]);
                            }
                        }

                        $dataListIds = [
                            'idTipoPersona'  => $tiposPersonas,
                            'idNivelGeografico3' => $municipalities
                        ];

                    }
                }

                $fila++;

            }
            /****** Fin Validación de archivo ******/       


            /****** Procesar archivo sin errores ******/
            if (count($dataListErr) == 0) { 

                # LLamado a la funcion de subida a la base de datos
                return SELF::fileUploadDataBase($pathUploadFile, $ignorarFilas, $documento, $dataListIds); 
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
     * @param $ignorarFilas []
     * @param $ListIds []
     * @return [Validaciones, errores y registros agregados a la D.B]
     **/
    public function fileUploadDataBase($pathUploadFile, $ignorarFilas = [], $documento, $ListIds)
    {  
        $indiceHoja = 0;
        $dataTerceros = []; //Informacion para la tabla cgRegionales;

        $dataListErr = []; //Listado de errores
        $errors = [];
        $cantRegistros = 0;

        if (is_file($pathUploadFile)) {

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);
            $maxCell = $hojaActual->getHighestRowAndColumn();
            $hojaActualArray = $hojaActual->rangeToArray("A1:H". $maxCell['row']);

            $transaction = Yii::$app->db->beginTransaction();
            $dataLogTerceros = [];

            # Iterar filas
            foreach ($hojaActualArray as $i => $row) {
                
                if(!in_array($i, $ignorarFilas)){                

                    if($i > 0 ){

                        $dataTerceros['nombreCliente'] =  $row[$this->numberByLetter('A')];                         // Nombre Completo
                        $dataTerceros['idTipoPersona'] =  $row[$this->numberByLetter('B')];                         // Tipo de Persona                       
                        $dataTerceros['numeroDocumentoCliente']   = (string) $row[$this->numberByLetter('C')];      // Numero
                        $dataTerceros['correoElectronicoCliente'] = $row[$this->numberByLetter('D')];               // Correo
                        $dataTerceros['idNivelGeografico3'] =  $row[$this->numberByLetter('E')];                    // Municipio
                        $dataTerceros['direccionCliente']   =  (string) $row[$this->numberByLetter('F')];           // Direccion
                        $dataTerceros['codigoSap']          =  (string) $row[$this->numberByLetter('G')];           // Código SAP 
                        $dataTerceros['telefonoCliente']    =  (string) $row[$this->numberByLetter('H')];           // Teléfono

                        $dataLogTerceros[] = $dataTerceros;   
                    }

                    // Se agregan los datos del excel
                    if (count($dataTerceros) > 1) {

                        #Crea el registro                        
                        $modelClientes = new Clientes();
                        $modelClientes->attributes =  $dataTerceros;
                        $modelClientes->numeroDocumentoCliente = (string) $dataTerceros['numeroDocumentoCliente'];

                        if(!is_null($dataTerceros['idTipoPersona'])){
                            $modelClientes->idTipoPersona      = $ListIds['idTipoPersona'][$dataTerceros['idTipoPersona']];
                        }

                        if(!is_null($dataTerceros['idNivelGeografico3'])){

                            # Listado de municipios
                            $modelClientes->idNivelGeografico3 = $ListIds['idNivelGeografico3'][$dataTerceros['idNivelGeografico3']];
                            $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' =>  $modelClientes->idNivelGeografico3]);

                            # Listado de departamentos
                            $modelDepartment = NivelGeografico2::findOne(['nivelGeografico2' => $nivelGeografico3->idNivelGeografico2]);
                            $modelClientes->idNivelGeografico2 = $modelDepartment->nivelGeografico2;

                            # Listado de paises
                            $modelPaises = NivelGeografico1::findOne(['nivelGeografico1' => $modelDepartment->idNivelGeografico1]);
                            $modelClientes->idNivelGeografico1 = $modelPaises->nivelGeografico1;
                        }
                        

                        if ($modelClientes->save()) {
                            $cantRegistros++;

                        } else {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $modelClientes->getErrors(),
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

                foreach ($dataLogTerceros as $dataLog) { 
                    $dataOld = $dataOld.'-'.self::logDataCliente($dataLog);
                }
                
                /***    log Auditoria ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['crear'] .' en la tabla Clientes', // texto para almacenar en el evento
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
            'D' => 3,
            'E' => 4,
            'F' => 5,
            'G' => 6,
            'H' => 7,
            'I' => 8,
            'J' => 9,
            'K' => 10,
        );

        return $letterArray[$letter];
    }

    /**
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que recibe del servicio]
     */

    public static function logDataCliente($request){

        # verificar si el telefono es NULL
        $telefono =  $request['telefonoCliente'] ?? 'N/A';

        $data  = 'Nombre: '.$request['nombreCliente'];
        $data .= ', Tipo de persona: '.$request['idTipoPersona'];
        $data .= ', Número de identidad: '.$request['numeroDocumentoCliente'];
        $data .= ', Correo: '. $request['correoElectronicoCliente'];               
        $data .= ', Dirección: '.  $request['direccionCliente'];
        $data .= ', Teléfono: '. $telefono;                
        $data .= ', Estado: '. 'Activo';

        return $data;
    }

    /**
     * Funcion que obtiene la data actual, es utilizado para el create y update
     * @param $request [Array] [data que recibe del servicio]
     */

    public static  function logDataClienteCiudadano($request, $modelCliente){

        #Consulta para obtener los datos del tipo de persona
        $TiposIdentificacion = TiposIdentificacion::findOne(['idTipoIdentificacion' => $request['idTipoIdentificacion']]);

        $data  = '  Id cliente: '.$modelCliente->idCliente;
        $data .= ', Tipo identificación: '.$TiposIdentificacion['nombreTipoIdentificacion'];
        $data .= ', Género: '. Yii::$app->params['generoClienteCiudadanoDetalle'][0]['number'][$request['generoClienteCiudadanoDetalle']];
        $data .= ', Edad (Rango): '. Yii::$app->params['rangoEdadClienteCiudadanoDetalle'][0]['number'][$request['rangoEdadClienteCiudadanoDetalle']];            
        $data .= ', Vulnerabilidad: '. Yii::$app->params['vulnerabilidadClienteCiudadanoDetalle']['number'][$request['vulnerabilidadClienteCiudadanoDetalle']];
        $data .= ', Etnia: '. Yii::$app->params['etniaClienteCiudadanoDetalle'][0]['number'][$request['etniaClienteCiudadanoDetalle']];             
        $data .= ', Estado: '. 'Activo';  
        $data .= ', Tabla clientesCiudadanosDetalles' ;  

        return $data;
    }
   

    /** Listado de clientes activos múltiples **/
    public function actionIndexListClient(){

        $modelClient = Clientes::find()
            ->where(['estadoCliente' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreCliente' => SORT_ASC])
        ->all();

        foreach ($modelClient as $row) {
            $data[] = array(
                "val" => $row->nombreCliente,
                "id" => (int) $row->idCliente,
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }
}
