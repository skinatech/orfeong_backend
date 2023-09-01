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
use api\models\CgTiposRadicados;
use api\models\CgTransaccionesRadicados;
use api\models\CgTiposRadicadosTransacciones;
use api\models\RadiRadicados;
use api\models\CgNumeroRadicado;
use api\models\CgGeneral;
use api\models\CgTiposRadicadosResoluciones;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperDynamicForms;

use api\models\GdTrdTiposDocumentales;
use api\models\CgTipoRadicadoDocumental;
use api\models\RolesTipoRadicado;

/**
 * TiposDocumentalController implements the CRUD actions for Gd Trd Tipos documental model.
 */
class TiposRadicadosController extends Controller
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
                    'index-all'  => ['GET'],
                    'index-all-transactions' => ['GET'],
                    'index-all-documental-types' => ['GET'],
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

    public function actionIndex($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            //Lista de radicados
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];
            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if (is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info){

                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'estadoCgTipoRadicado' || $key == 'creacionCgTipoRadicadoDesde' || $key == 'creacionCgTipoRadicadoHasta') {
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

            $modelCgGeneral = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();
            $resolutionExists = false;

            // Consulta para relacionar la informacion de dependencias y obtener 100 registros, a partir del filtro
            $tiposRadicados = CgTiposRadicados::find();
            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value) {
                switch ($field) {
                    case 'estadoCgTipoRadicado':
                        $tiposRadicados->andWhere(['IN',  $field, intval($value)]);
                    break;
                    case 'creacionCgTipoRadicadoDesde':
                        $tiposRadicados->andWhere(['>=', 'creacionCgTipoRadicado', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'creacionCgTipoRadicadoHasta':
                        $tiposRadicados->andWhere(['<=', 'creacionCgTipoRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $tiposRadicados->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                }
            }
            //Limite de la consulta
            $tiposRadicados->orderBy(['idCgTipoRadicado' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $tiposRadicados->limit($limitRecords);
            $modelTiposRadicados = $tiposRadicados->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelTiposRadicados = array_reverse($modelTiposRadicados);

            foreach($modelTiposRadicados as $tipoRadicado) {
                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($tipoRadicado->idCgTipoRadicado)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($tipoRadicado->codigoCgTipoRadicado))
                );

                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $tipoRadicado->idCgTipoRadicado,
                    'codigoCgTipoRadicado' => $tipoRadicado->codigoCgTipoRadicado,
                    'nombreCgTipoRadicado' => $tipoRadicado->nombreCgTipoRadicado,
                    'estadoCgTipoRadicado' => $tipoRadicado->estadoCgTipoRadicado,
                    'creacionCgTipoRadicado' => $tipoRadicado->creacionCgTipoRadicado,
                    'status' => $tipoRadicado->estadoCgTipoRadicado,
                    'statusText' => Yii::$app->params['statusTodoNumber'][$tipoRadicado->estadoCgTipoRadicado],
                    'rowSelect' => false,
                    'idInitialList' => 0
                ];
                $nombreTipoRadicado = strtoupper($tipoRadicado->nombreCgTipoRadicado);
                if (isset($modelCgGeneral->resolucionesNameCgGeneral) && $nombreTipoRadicado === strtoupper($modelCgGeneral->resolucionesNameCgGeneral)) {
                    $resolutionExists = true;
                }
            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgTiposRadicados');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'resolucionesCgGeneral' => $modelCgGeneral->resolucionesCgGeneral,
                'resolutionExists' => $resolutionExists,
                'resolucionesIdCgGeneral' => $modelCgGeneral->resolucionesIdCgGeneral,
                'status' => 200
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

    public function actionIndexAll($request) {
        $permiso = Yii::$app->params['permissionsRolFilingTypes'];
        if(HelperValidatePermits::validateUserPermits($permiso, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

            if (isset($request['id'])) {
                $id = $request['id'];
            } else {
                $id = 0;
            }

            $modulos = [];
            $operaciones = [];
            $modulosFilter = [];
            $data = [];
            $rolesTiposRadicados = [];

            //Se consulta que el estado este activo
            $findCgTiposRadicados = CgTiposRadicados::find()->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])->all();
            $findRolesTipoRadicado = RolesTipoRadicado::find()->where(['idRol' => $id ])->all();

            foreach($findRolesTipoRadicado as $key => $rolTipoRadicado) {
                $rolesTiposRadicados[] = $rolTipoRadicado->idCgTipoRadicado;                    
            }

            //Pendiente seccion de modulos
            $modulosFilter = array('name' => 'Tipos de radicado', 'value' => false);
            
            //Lista de los tipos de radicado
            foreach($findCgTiposRadicados as $key => $infoTipoDoc) {
                
                if( isset($rolTipoRadicado)) {

                    if(in_array($infoTipoDoc->idCgTipoRadicado, $rolesTiposRadicados)) {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTipoRadicado, 'name' => $infoTipoDoc->nombreCgTipoRadicado, 'value' => true);
                    } else {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTipoRadicado, 'name' => $infoTipoDoc->nombreCgTipoRadicado, 'value' => false);
                    }

                } else {
                    $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTipoRadicado, 'name' => $infoTipoDoc->nombreCgTipoRadicado, 'value' => false);
                }                
            }            
            
            //Lista de modulos
            if($id > 0) {
                $countXmodulo = 0;
                $countXmodulo = count($operaciones[$modulosFilter['name']]);

                foreach($operaciones[$modulosFilter['name']] as $keyOpera => $operacion) {

                    if($operacion['value'] == true) {
                        $countXmodulo = $countXmodulo - 1;
                    }
                }

                if($countXmodulo > 0) {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
                } else {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => true);
                }

            } else {
                $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
            }
            
            $data = array( 'nombreRol' => '', 'modulos' => $modulos, 'operaciones' => $operaciones);

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
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionIndexAllTransactions($request) {
        //if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
            if (isset($request['id'])) {
                $id = $request['id'];
            } else {
                $id = 0;
            }
            $modulos = [];
            $operaciones = [];
            $modulosFilter = [];
            $data = [];
            $rolesTiposRadicados = [];
            
            //Se consulta que el estado este activo
            $findCgTiposRadicados = CgTransaccionesRadicados::find()
                ->where(['mostrarBotonCgTransaccionRadicado' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();
            $findRolesTipoRadicado = CgTiposRadicadosTransacciones::find()->where(['idCgTipoRadicado' => $id ])->all();

            foreach($findRolesTipoRadicado as $key => $rolTipoRadicado) {
                $rolesTiposRadicados[] = $rolTipoRadicado->idCgTransaccionRadicado;                    
            }

            //Pendiente seccion de modulos
            $modulosFilter = array('name' => 'Transacciones asociadas al tipo de radicado', 'value' => false);
            
            //Lista de transacciones
            foreach($findCgTiposRadicados as $key => $infoTipoDoc) {
                
                if( isset($rolTipoRadicado)) {

                    if(in_array($infoTipoDoc->idCgTransaccionRadicado, $rolesTiposRadicados)) {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTransaccionRadicado, 'name' => $infoTipoDoc->descripcionCgTransaccionRadicado, 'value' => true);
                    } else {
                        $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTransaccionRadicado, 'name' => $infoTipoDoc->descripcionCgTransaccionRadicado, 'value' => false);
                    }

                } else {
                    $operaciones[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idCgTransaccionRadicado, 'name' => $infoTipoDoc->descripcionCgTransaccionRadicado, 'value' => false);
                }                
            }            
            
            //Lista de modulos
            if($id > 0) {
                $countXmodulo = 0;
                $countXmodulo = count($operaciones[$modulosFilter['name']]);

                foreach($operaciones[$modulosFilter['name']] as $keyOpera => $operacion) {

                    if($operacion['value'] == true) {
                        $countXmodulo = $countXmodulo - 1;
                    }
                }

                if($countXmodulo > 0) {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
                } else {
                    $modulos[] = array('name' => $modulosFilter['name'], 'value' => true);
                }

            } else {
                $modulos[] = array('name' => $modulosFilter['name'], 'value' => false);
            }
            
            $data = array( 'nombreRol' => '', 'modulos' => $modulos, 'operaciones' => $operaciones);

            $mostrarCodigoradicado = (Yii::$app->params['codigoradicado'] == 'texto') ? true : false;

            $modelCgNumeroRadicado = CgNumeroRadicado::find()->select(['longitudConsecutivoCgNumeroRadicado'])
                ->where(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();
            if ($modelCgNumeroRadicado != null) {
                $longitudRadicado = $modelCgNumeroRadicado->longitudConsecutivoCgNumeroRadicado;
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'mostrarCodigoradicado' => $mostrarCodigoradicado,
                'codigoradicado' => Yii::$app->params['codigoradicado'],
                'longitudRadicado' => $longitudRadicado ?? 0,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        
        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app', 'accessDenied')[0],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }
    }

    public function actionIndexAllDocumentalTypes($request)
    {
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

        $modulosTiposDocumentales = [];
        $tiposDocumentales = [];
        $modulosFilter = [];
        $tiposDocumentalesRadicados = [];

        $documentTypesRadiStatus = false; // Define si se procesan tipos documentales al radicado

        if ($id == Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
            $documentTypesRadiStatus = true;

            //Se consulta que el estado este activo
            $findGdTrdTiposDocumentales = GdTrdTiposDocumentales::find()
                ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();
            $findCgTipoRadicadoDocumental = CgTipoRadicadoDocumental::find()->where(['idCgTipoRadicado' => $id])->all();

            foreach($findCgTipoRadicadoDocumental as $key => $rolTipoRadicado) {
                $tiposDocumentalesRadicados[] = $rolTipoRadicado->idGdTrdTipoDocumental;
            }

            $modulosFilter = array('name' => 'Tipos documentales asociados al tipo de radicado', 'value' => false);

            //Lista de transacciones
            foreach($findGdTrdTiposDocumentales as $key => $infoTipoDoc) {
                
                if( isset($rolTipoRadicado)) {

                    if(in_array($infoTipoDoc->idGdTrdTipoDocumental, $tiposDocumentalesRadicados)) {
                        $tiposDocumentales[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => true);
                    } else {
                        $tiposDocumentales[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => false);
                    }

                } else {
                    $tiposDocumentales[$modulosFilter['name']][] = array('id' => $infoTipoDoc->idGdTrdTipoDocumental, 'name' => $infoTipoDoc->nombreTipoDocumental, 'value' => false);
                }
            }

            //Lista de modulos de tipos documentales
            if($id > 0) {
                $countXmodulo = 0;
                $countXmodulo = count($tiposDocumentales[$modulosFilter['name']]);

                foreach($tiposDocumentales[$modulosFilter['name']] as $keyOpera => $operacion) {

                    if($operacion['value'] == true) {
                        $countXmodulo = $countXmodulo - 1;
                    }
                }

                if($countXmodulo > 0) {
                    $modulosTiposDocumentales[] = array('name' => $modulosFilter['name'], 'value' => false);
                } else {
                    $modulosTiposDocumentales[] = array('name' => $modulosFilter['name'], 'value' => true);
                }

            } else {
                $modulosTiposDocumentales[] = array('name' => $modulosFilter['name'], 'value' => false);
            }

        }

        $data = array('modulosTiposDocumentales' => $modulosTiposDocumentales, 'tiposDocumentales' => $tiposDocumentales);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'documentTypesRadiStatus' => $documentTypesRadiStatus,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionIndexOne($request){
        //if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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
            $modelTipoRadicado = $this->findModel($id);
            $modelCgTiposRadicadosResoluciones = CgTiposRadicadosResoluciones::find()
                ->where(['idCgTipoRadicado' => $id])
                ->one();

            $data = [
                'id' => $modelTipoRadicado->idCgTipoRadicado,
                'codigoCgTipoRadicado' => $modelTipoRadicado->codigoCgTipoRadicado,
                'nombreCgTipoRadicado' => $modelTipoRadicado->nombreCgTipoRadicado,
                'unicoRadiCgTipoRadicado' => $modelTipoRadicado->unicoRadiCgTipoRadicado,
                'numeracionCgTiposRadicadosResoluciones' => ($modelCgTiposRadicadosResoluciones !== null) ? $modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones : 0
            ];

            //Consultar si existen radicados creados con el tipo
            $radiRadicados = RadiRadicados::find()
                ->where(['idCgTipoRadicado' => $modelTipoRadicado->idCgTipoRadicado])
                ->limit(1)
                ->one();

            $existenRadicados = ($radiRadicados != null) ? true : false;

            /** Tipos de radicado a los que no se debe cambiar el código */
            $arrayTypeNoUpdate = [
                Yii::$app->params['idCgTipoRadicado']['radiSalida'],
                Yii::$app->params['idCgTipoRadicado']['radiEntrada'],
                Yii::$app->params['idCgTipoRadicado']['comunicacionInterna'],
                Yii::$app->params['idCgTipoRadicado']['radiPqrs'],
            ];

            $isCodigoFijo = (in_array($modelTipoRadicado->idCgTipoRadicado, $arrayTypeNoUpdate)) ? true : false;

            $modelCgGeneral = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'existenRadicados' => $existenRadicados,
                'isCodigoFijo' => $isCodigoFijo,
                'isTypeResolution' => ($modelCgGeneral->resolucionesIdCgGeneral === $modelTipoRadicado->idCgTipoRadicado) ? true : false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app', 'accessDenied')[0],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }
    }

    public function actionView($request){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
        
            if (!empty($request)) {
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

                $modelTiposRadicados = $this->findModel($id);

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['View'] .' nombre '. $modelTiposRadicados->nombreCgTipoRadicado, //texto para almacenar en el evento
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $modelCgTiposRadicadosResoluciones = CgTiposRadicadosResoluciones::find()
                    ->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])
                    ->one();

                //Retorno tipo de resdicados
                $data[] = ['alias' => 'Código', 'value' => $modelTiposRadicados->codigoCgTipoRadicado];
                $data[] = ['alias' => 'Estado', 'value' => Yii::$app->params['statusTodoNumber'][$modelTiposRadicados->estadoCgTipoRadicado]];
                $data[] = ['alias' => 'Fecha de creación', 'value' => $modelTiposRadicados->creacionCgTipoRadicado];
                $data[] = ['alias' => 'Nombre', 'value' => $modelTiposRadicados->nombreCgTipoRadicado];
                $data[] = ['alias' => 'Único radicado con múltiples remitentes', 'value' => Yii::$app->params['SiNoNumber'][$modelTiposRadicados->unicoRadiCgTipoRadicado]];

                if ($modelCgTiposRadicadosResoluciones) {
                    $data[] = ['alias' => 'Numeración resolución', 'value' => $modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones];
                }

                $cgTransaccionesRadicados = CgTransaccionesRadicados::find()->select(['idCgTransaccionRadicado', 'descripcionCgTransaccionRadicado'])->all();
                $cgTiposRadicadosTransacciones = CgTiposRadicadosTransacciones::find()->select(['idCgTransaccionRadicado'])
                    ->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])->all();

                $arrayRadicadosTransacciones = [];
                foreach ($cgTiposRadicadosTransacciones as $value) {
                    $arrayRadicadosTransacciones[] = $value->idCgTransaccionRadicado;
                }

                $transaccionesFull = true;
                $transacciones = [];
                foreach ($cgTransaccionesRadicados as $value) {
                    if (in_array($value->idCgTransaccionRadicado, $arrayRadicadosTransacciones)) {
                        $checked = true;
                    } else {
                        $checked = false;
                        $transaccionesFull = false;
                    }
                    $transacciones['Transacciones'][] = ['name' => $value->descripcionCgTransaccionRadicado, 'value' => $checked];
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $data,
                    'conjuntos' => [['name' => "Transacciones", 'value'=> $transaccionesFull]],
                    'elementos' => $transacciones,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            }else {
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
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionCreate() {
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

                unset($request['idCgTipoRadicado']);

                $modelTiposRadicados = new CgTiposRadicados();
                $modelTiposRadicados->attributes = $request;
                $modelTiposRadicados->nombreCgTipoRadicado = strtoupper($request['nombreCgTipoRadicado']);
                $modelTiposRadicados->estadoCgTipoRadicado = Yii::$app->params['statusTodoText']['Activo'];
                $modelTiposRadicados->creacionCgTipoRadicado = Date('Y-m-d H:i:s');

                if( $request['unicoRadiCgTipoRadicado'] === true ){
                    $modelTiposRadicados->unicoRadiCgTipoRadicado = Yii::$app->params['statusTodoText']['Activo'];
                } else {
                    $modelTiposRadicados->unicoRadiCgTipoRadicado = Yii::$app->params['statusTodoText']['Inactivo'];
                }

                if(Yii::$app->params['codigoradicado'] == 'numero') {
                    $codigoModel = CgTiposRadicados::find()
                        ->select('codigoCgTipoRadicado')
                        ->all();

                    $codigos = [];
                    foreach($codigoModel as $item) {
                        $codigos[] = (int) $item->codigoCgTipoRadicado;
                    }
                    rsort($codigos);

                    $modelTiposRadicados->codigoCgTipoRadicado = (string) ($codigos[0] + 1);

                } else {

                    //Verificar si el codigo ya existe
                    $modelExistente = CgTiposRadicados::find()
                        ->where(['codigoCgTipoRadicado' => $modelTiposRadicados->codigoCgTipoRadicado])
                        ->one();

                    if($modelExistente != null) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'codigoTipoRadicadoDupilcated') . " $modelExistente->codigoCgTipoRadicado"],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    //Fin Verificar si el codigo ya existe
                }

                //Verificar si el nombre ya existe
                $modelExistente = CgTiposRadicados::find()
                    ->where(['nombreCgTipoRadicado' => strtoupper($modelTiposRadicados->nombreCgTipoRadicado)])
                    ->one();

                $modelCgGeneral = CgGeneral::find()
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();

                if (isset($request['activatedResolutions']) && $request['activatedResolutions'] === "false") {
                    if (strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIONES" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCION" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIÓN" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIÓNE" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIONE" || strtoupper($request['nombreCgTipoRadicado']) === $modelCgGeneral->resolucionesNameCgGeneral || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIóN") {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'nombreTipoRadicaResolucion') ." - ". $request['nombreCgTipoRadicado'] ],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                if($modelExistente !== null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nombreTipoRadicaDuplicated') . " $modelExistente->nombreCgTipoRadicado"],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //fin de verificación de nombre existente

                if($modelTiposRadicados->save()) {
                    /** Pocesar transacciones */
                    $errors = [];
                    $saveDataValid = true;

                    $nombreTransacciones = [];
                    foreach ($request['operacionesRol'] as $key => $operacion) {
                        $modelCgTiposRadicadosTransacciones = new CgTiposRadicadosTransacciones();
                        $modelCgTiposRadicadosTransacciones->idCgTransaccionRadicado = $operacion;
                        $modelCgTiposRadicadosTransacciones->idCgTipoRadicado = $modelTiposRadicados->idCgTipoRadicado;
                        $modelCgTiposRadicadosTransacciones->orderCgTipoRadicado = ($key + 1);

                        if (!$modelCgTiposRadicadosTransacciones->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelCgTiposRadicadosTransacciones->getErrors());
                            break;
                        }
                    }

                    if (isset($request['activatedResolutions']) && $request['activatedResolutions'] === "true") {
                        $modelCgTiposRadicadosResoluciones = new CgTiposRadicadosResoluciones();
                        $modelCgTiposRadicadosResoluciones->idCgTipoRadicado = $modelTiposRadicados->idCgTipoRadicado;
                        $modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones = $request['numeracionCgTiposRadicadosResoluciones'];
                        if (!$modelCgTiposRadicadosResoluciones->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelCgTiposRadicadosResoluciones->getErrors());
                        }

                        $modelCgGeneral->resolucionesIdCgGeneral = $modelTiposRadicados->idCgTipoRadicado;
                        if (!$modelCgGeneral->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelCgGeneral->getErrors());
                        }
                    }

                    if ($saveDataValid) {
                        #Data actual para el log
                        $dataTiposRadicados = 'Código tipo radicado: '. $modelTiposRadicados->codigoCgTipoRadicado;
                        $dataTiposRadicados .= ', Nombre tipo radicado: '. $modelTiposRadicados->nombreCgTipoRadicado;
                        $dataTiposRadicados .= ', Único radicado con múltiples remitentes: '. Yii::$app->params['SiNoNumber'][$modelTiposRadicados->unicoRadiCgTipoRadicado];
                        $dataTiposRadicados .= ', Estado: '. Yii::$app->params['statusTodoNumber'][$modelTiposRadicados->estadoCgTipoRadicado];
                        $dataTiposRadicados .= ', Fecha de creación: '. $modelTiposRadicados->creacionCgTipoRadicado;
                        $dataTiposRadicados .= ', Transacciones: (';

                        $cgTransaccionesRadicados = CgTiposRadicadosTransacciones::find()->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])->all();

                        foreach ($cgTransaccionesRadicados as $key => $value) {
                            $dataTiposRadicados .= $value->cgTransaccionRadicado->descripcionCgTransaccionRadicado .", ";
                        }

                        $dataTiposRadicados = substr($dataTiposRadicados, 0, -2) . ')';

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['newTipoRadicado'] . $modelTiposRadicados->nombreCgTipoRadicado, //texto para almacenar en el evento
                            '',
                            $dataTiposRadicados, //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $transaction->commit();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successSave'),
                            'data' => $modelTiposRadicados,
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
                    /** Fin procesar transacciones */

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelTiposRadicados->getErrors(),
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

    public function actionUpdate() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->post('jsonSend');
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

                $transaction = Yii::$app->db->beginTransaction();
                $modelTiposRadicados = $this->findModel($id);

                // Valores anteriores del modelo
                //$dataTiposRadicadosOld = 'Id tipo radicado: '. $modelTiposRadicados->idCgTipoRadicado;
                $dataTiposRadicadosOld = 'Código tipo radicado: '. $modelTiposRadicados->codigoCgTipoRadicado;
                $dataTiposRadicadosOld .= ', Nombre tipo radicado: '. $modelTiposRadicados->nombreCgTipoRadicado;
                $dataTiposRadicadosOld .= ', Único radicado con múltiples remitentes: '. Yii::$app->params['SiNoNumber'][$modelTiposRadicados->unicoRadiCgTipoRadicado];
                $dataTiposRadicadosOld .= ', Estado: '. Yii::$app->params['statusTodoNumber'][$modelTiposRadicados->estadoCgTipoRadicado];
                $dataTiposRadicadosOld .= ', Fecha de creación: '. $modelTiposRadicados->creacionCgTipoRadicado;
                $dataTiposRadicadosOld .= ', Transacciones: (';

                $cgTransaccionesRadicados = CgTiposRadicadosTransacciones::find()->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])->all();

                foreach ($cgTransaccionesRadicados as $key => $value) {

                    if(!isset($value->cgTransaccionRadicado->descripcionCgTransaccionRadicado)){
                        $descripcionCgTransaccionRadicado = 'Sin descripción';
                    }else{
                        $descripcionCgTransaccionRadicado = $value->cgTransaccionRadicado->descripcionCgTransaccionRadicado;
                    }

                    $dataTiposRadicadosOld .= $descripcionCgTransaccionRadicado .", ";
                }

                $dataTiposRadicadosOld = substr($dataTiposRadicadosOld, 0, -2) . ')';

                $codigoCgTipoRadicadoOld = $modelTiposRadicados->codigoCgTipoRadicado;

                $modelTiposRadicados->attributes = $request;

                /** Validar si al tipo de radicado se le puede modificar su código */
                if ($codigoCgTipoRadicadoOld != $modelTiposRadicados->codigoCgTipoRadicado) {

                    /** Tipos de radicado a los que no se debe cambiar el código */
                    $arrayTypeNoUpdate = [
                        Yii::$app->params['idCgTipoRadicado']['radiSalida'],
                        Yii::$app->params['idCgTipoRadicado']['radiEntrada'],
                        Yii::$app->params['idCgTipoRadicado']['comunicacionInterna'],
                        Yii::$app->params['idCgTipoRadicado']['radiPqrs'],
                    ];
                    if (in_array($modelTiposRadicados->idCgTipoRadicado, $arrayTypeNoUpdate)) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => [ Yii::t('app', 'TipoRadicadosNoUpdateCodigo') ]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                //Verificar si el nombre ya existe
                $modelExistente = CgTiposRadicados::find()
                    ->where(['nombreCgTipoRadicado' => $modelTiposRadicados->nombreCgTipoRadicado])
                    ->andWhere(['<>' ,'idCgTipoRadicado', $modelTiposRadicados->idCgTipoRadicado])
                    ->one();

                $modelCgGeneral = CgGeneral::find()
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();
                if (isset($request['activatedResolutions']) && $request['activatedResolutions'] === "false" && $modelCgGeneral->resolucionesCgGeneral === Yii::$app->params['statusTodoText']['Activo']) {
                    if (strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIONES" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCION" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIÓN" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIÓNE" || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIONE" || strtoupper($request['nombreCgTipoRadicado']) === $modelCgGeneral->resolucionesNameCgGeneral || strtoupper($request['nombreCgTipoRadicado']) === "RESOLUCIóN") {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'nombreTipoRadicaResolucion') ." - ". $request['nombreCgTipoRadicado'] ],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                if($modelExistente !== null){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nombreTipoRadicaDuplicated') . " $modelExistente->nombreCgTipoRadicado"],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //fin de verificación de nombre existente

                //Verificar si el codigo ya existe
                $modelExistente = CgTiposRadicados::find()
                    ->where(['codigoCgTipoRadicado' => $modelTiposRadicados->codigoCgTipoRadicado])
                    ->andWhere(['<>' ,'idCgTipoRadicado', $modelTiposRadicados->idCgTipoRadicado])
                    ->one();

                if($modelExistente != null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'codigoTipoRadicadoDupilcated') . " $modelExistente->codigoCgTipoRadicado"],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //Fin Verificar si el codigo ya existe

                //Validar si existen radicados creados con el tipo
                $radiRadicados = RadiRadicados::find()
                    ->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])
                    ->limit(1)
                    ->one();

                if ($radiRadicados != null) {
                    /** Validar si el tipo de radicado ya posee radicados generados */
                    if (($codigoCgTipoRadicadoOld != $modelTiposRadicados->codigoCgTipoRadicado)) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => [ Yii::t('app', 'TipoRadicadosConRadicados') ]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
                //Validar si existen radicados creados con el tipo

                # Valida que el tipo de radicado sea diferente a Entrada, salida y pqrs para actualizar la opcion multiple
                if( $modelTiposRadicados->codigoCgTipoRadicado != Yii::$app->params['CgTipoRadicado']['radiSalida'] &&
                    $modelTiposRadicados->codigoCgTipoRadicado != Yii::$app->params['CgTipoRadicado']['radiEntrada'] &&
                    $modelTiposRadicados->codigoCgTipoRadicado != Yii::$app->params['CgTipoRadicado']['radiPqrs'] ) {

                    if( $request['unicoRadiCgTipoRadicado'] === true ){
                        $modelTiposRadicados->unicoRadiCgTipoRadicado = Yii::$app->params['statusTodoText']['Activo'];
                    } else {
                        $modelTiposRadicados->unicoRadiCgTipoRadicado = Yii::$app->params['statusTodoText']['Inactivo'];
                    }

                }

                if ($modelTiposRadicados->save()) {

                    // Tomar las transacciones anteriores para el log
                    $transaccionesOld = CgTiposRadicadosTransacciones::find()
                        ->where([ 'idCgTipoRadicado' => (int) $modelTiposRadicados->idCgTipoRadicado ])
                        ->all();

                    // Valores anteriores del modelo
                    $transaccionesAAA = [];
                    foreach ($transaccionesOld as $key => $value) {
                        $transaccionesAAA[$key] = $value;
                    }

                    /** Pocesar transacciones */
                    $deleteCgTiposRadicadosTransacciones = CgTiposRadicadosTransacciones::deleteAll(
                        [
                            'AND',
                            ['idCgTipoRadicado' => (int) $modelTiposRadicados->idCgTipoRadicado]
                        ]
                    );

                    $errors = [];
                    $saveDataValid = true;

                    foreach ($request['operacionesRol'] as $key => $operacion) {

                        $modelCgTiposRadicadosTransacciones = new CgTiposRadicadosTransacciones();
                        $modelCgTiposRadicadosTransacciones->idCgTransaccionRadicado = $operacion;
                        $modelCgTiposRadicadosTransacciones->idCgTipoRadicado = $modelTiposRadicados->idCgTipoRadicado;
                        $modelCgTiposRadicadosTransacciones->orderCgTipoRadicado = ($key + 1);

                        if (!$modelCgTiposRadicadosTransacciones->save()) {
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelCgTiposRadicadosTransacciones->getErrors());
                            break;
                        }
                    }

                    /** Procesar tipos documentales del radicado (Solo aplica para PQRs) */
                    if ($modelTiposRadicados->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                        $deleteCgTipoRadicadoDocumental = CgTipoRadicadoDocumental::deleteAll(
                            [
                                'AND',
                                ['idCgTipoRadicado' => (int) $modelTiposRadicados->idCgTipoRadicado]
                            ]
                        );

                        foreach ($request['tiposDocumentalesRadicado'] as $key => $tipoDoc) {

                            $modelCgTipoRadicadoDocumental = new CgTipoRadicadoDocumental();
                            $modelCgTipoRadicadoDocumental->idGdTrdTipoDocumental = $tipoDoc;
                            $modelCgTipoRadicadoDocumental->idCgTipoRadicado = $modelTiposRadicados->idCgTipoRadicado;

                            if (!$modelCgTipoRadicadoDocumental->save()) {
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelCgTipoRadicadoDocumental->getErrors());
                                break;
                            }
                        }
                    }
                    /** Procesar tipos documentales del radicado (Solo aplica para PQRs) */

                    if (isset($request['activatedResolutions']) && $request['activatedResolutions'] === "true") {
                        $fechaInicialRadicados = date("Y") . "-01-01 00:00:00";
                        $fechaFinalRadicados = date("Y") . "-12-31 23:59:59";

                        $modelCgTiposRadicadosResoluciones = CgTiposRadicadosResoluciones::find()->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])->one();
                        if (!$modelCgTiposRadicadosResoluciones) {
                            $modelCgTiposRadicadosResoluciones = new CgTiposRadicadosResoluciones();
                            $modelCgTiposRadicadosResoluciones->idCgTipoRadicado = $modelTiposRadicados->idCgTipoRadicado;
                            $modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones = $request['numeracionCgTiposRadicadosResoluciones'];
                            if (!$modelCgTiposRadicadosResoluciones->save()) {
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelCgTiposRadicadosResoluciones->getErrors());
                            }
                        }

                        $modelRadiRadicados = RadiRadicados::find()
                            ->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])
                            ->andWhere(['BETWEEN', 'creacionRadiRadicado', $fechaInicialRadicados, $fechaFinalRadicados])
                            ->limit(1)
                            ->one();

                        if ($modelRadiRadicados === null) {
                            $modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones = $request['numeracionCgTiposRadicadosResoluciones'];
                            if (!$modelCgTiposRadicadosResoluciones->save()) {
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelCgTiposRadicadosResoluciones->getErrors());
                            }
                        }
                    }

                    if ($saveDataValid) {

                        # Data actual para el log
                        $dataTiposRadicados = 'Código tipo radicado: '. $modelTiposRadicados->codigoCgTipoRadicado;
                        $dataTiposRadicados .= ', Nombre tipo radicado: '. $modelTiposRadicados->nombreCgTipoRadicado;
                        $dataTiposRadicados .= ', Único radicado con múltiples remitentes: '. Yii::$app->params['SiNoNumber'][$modelTiposRadicados->unicoRadiCgTipoRadicado];
                        $dataTiposRadicados .= ', Estado: '. Yii::$app->params['statusTodoNumber'][$modelTiposRadicados->estadoCgTipoRadicado];
                        $dataTiposRadicados .= ', Fecha de creación: '. $modelTiposRadicados->creacionCgTipoRadicado;
                        $dataTiposRadicados .= ', Transacciones: (';

                        $cgTransaccionesRadicados = CgTiposRadicadosTransacciones::find()->where(['idCgTipoRadicado' => $modelTiposRadicados->idCgTipoRadicado])->all();

                        foreach ($cgTransaccionesRadicados as $key => $value) {
                            $dataTiposRadicados .= $value->cgTransaccionRadicado->descripcionCgTransaccionRadicado .", ";
                        }

                        $dataTiposRadicados = substr($dataTiposRadicados, 0, -2) . ')';

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['updateTipoRadicado'] . $modelTiposRadicados->nombreCgTipoRadicado, //texto para almacenar en el evento
                            $dataTiposRadicadosOld, //DataOld
                            $dataTiposRadicados, //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $transaction->commit();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successUpdate'),
                            'data' => $modelTiposRadicados,
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
                    /** Fin procesar transacciones */

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelTiposRadicados->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionGetCodigoradicado(){
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => Yii::t('app', 'successSave'),
            'data' => Yii::$app->params['codigoradicado'],
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionChangeStatus(){
        
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

            if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

                $saveDataValid = true;
                $transaction = Yii::$app->db->beginTransaction();

                foreach ($request as $value) {
                    $dataExplode = explode('|', $value);

                    $model = $this->findModel($dataExplode[0]);

                    // Valores anteriores del modelo
                    $modelOld = [];
                    foreach ($model as $key => $value) {
                        $modelOld[$key] = $value;
                    }

                    //Validar si existen radicados creados con el tipo
                    $radiRadicados = RadiRadicados::find()->select(['idCgTipoRadicado'])
                        ->where(['idCgTipoRadicado' => $model->idCgTipoRadicado])
                        ->limit(1)
                    ->one();

                    if ($radiRadicados != null) {
                        if ($model->estadoCgTipoRadicado == yii::$app->params['statusTodoText']['Activo']) {
                        
                            $errs['errorRadiRadicados'] = [
                                Yii::t('app', 'errorChangeStatusTipoRadicado', [
                                    'tipoRadicado' => $model->nombreCgTipoRadicado,
                                ])
                            ];
                            $saveDataValid = false;
                        }
                    }
                    //Validar si existen radicados creados con el tipo
                    $statusOld = $model->estadoCgTipoRadicado;

                    if ($model->estadoCgTipoRadicado == yii::$app->params['statusTodoText']['Activo']) {
                        $model->estadoCgTipoRadicado = yii::$app->params['statusTodoText']['Inactivo'];
                        RolesTipoRadicado::deleteAll(['idCgTipoRadicado' => $model->idCgTipoRadicado]);
                    } else {
                        $model->estadoCgTipoRadicado = yii::$app->params['statusTodoText']['Activo'];
                    }

                    if ($model->save()) {

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ChangeStatus2'] . 'del tipo radicado: ' . $model->nombreCgTipoRadicado . ', ahora se encuentra en estado ' . Yii::t('app','statusTodoNumber')[$model->estadoCgTipoRadicado],// texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $dataResponse[] = array(
                            'id' => $model->idCgTipoRadicado, 'idInitialList' => $dataExplode[1] * 1,
                            'status' => $model->estadoCgTipoRadicado, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgTipoRadicado],
                            'statusOld' => $statusOld, 'statusTextOld' => Yii::t('app', 'statusTodoNumber')[$statusOld]
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
                        'message' => Yii::t('app', 'successChangeStatus'),
                        'data' => $dataResponse,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    foreach ($dataResponse as &$value) {
                        $value['status'] = $value['statusOld'];
                        $value['statusText'] = $value['statusTextOld'];
                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $errs,
                        'dataStatus' => $dataResponse,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            
            }else{
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

    private function returnSameDataChangeStatus($data)
    {
        $dataResponse = [];
        foreach ($data as $value) {
            $dataExplode = explode('|', $value);
            $model = CgTiposRadicados::find()->select(['estadoCgTipoRadicado'])->where(['idCgTipoRadicado' => $dataExplode[0] ])->one();
            if ($model != null) {
                $dataResponse[] = array(
                    'id' => $dataExplode[0], 'idInitialList' => $dataExplode[1] * 1,
                    'status' => $model['estadoCgTipoRadicado'], 'statusText' => Yii::t('app', 'statusTodoNumber')[$model['estadoCgTipoRadicado']]
                );
            } else {
                throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
            }
        }
        return $dataResponse;
    }

    protected function findModel($id)
    {
        if (($model = CgTiposRadicados::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }
}
