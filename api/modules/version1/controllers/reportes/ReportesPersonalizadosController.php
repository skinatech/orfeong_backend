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

namespace api\modules\version1\controllers\reportes;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperEncryptAes;
use api\components\HelperQueryDb;
use api\components\HelperDynamicForms;
use api\components\HelperLog;
use api\components\HelperConsecutivo;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\VerbFilter;

use api\models\RadiRadicados;
use api\models\GaPrestamos;
use api\models\GdExpedientes;
use api\models\RadiLogRadicados;
use api\models\GaArchivo;

use api\models\GdExpedientesInclusion;
use api\models\ReportesPersonalizados;

use api\models\GdTrdTiposDocumentales;
use api\models\CgTiposRadicados;
use api\models\CgMediosRecepcion;
use api\models\CgTransaccionesRadicados;
use api\models\RadiInformados;
use api\models\GdTrd;
use api\models\GaEdificio;
use api\models\GaPiso;
use api\models\GaBodega;
use api\models\User;
use api\models\GdTrdDependencias;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\UserDetalles;
use api\models\RadiRemitentes;
use api\models\Clientes;
use api\models\TiposPersonas;
use api\models\NivelGeografico3;
use api\models\CgRegionales;

use api\modules\version1\controllers\pdf\PdfController;
use yii\helpers\FileHelper;
use api\components\HelperGenerateExcel;
/** Espacio para personalizacion del cliente */
/** Fin Espacio para personalizacion del cliente */

class ReportesPersonalizadosController extends Controller{

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
                    'save-report' => ['POST'],
                    'generate-report' => ['POST'],
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
     * Lists all tables in reports.
     * @return mixed
     */
    public function actionIndex($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsSearchReport'], Yii::$app->user->identity->rol->rolesTiposOperaciones))
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
            $filterOperation = [];

            /* Listado de reportes guardados */
            $reportesPersonalizados = ReportesPersonalizados::find()
                ->select(['idReportePersonalizado','nombreReportePersonalizado', 'descripcionReportePersonalizado'])
                ->where(['estadoReportePersonalizado' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['idUserCreadorReportePersonalizado' => Yii::$app->user->identity->id])
                ->all();

            $listCustomReports[] = ['id' => '', 'val' => '-- Nuevo reporte --'];
            foreach ($reportesPersonalizados as $value) {
                $listCustomReports[] = ['id' => $value->idReportePersonalizado, 'val' => $value->nombreReportePersonalizado, 'description' => $value->descripcionReportePersonalizado];
            }

            # Array de modelos disonibles para realizar reportes
            $arrayModels = self::getArrayModels();

            /* Validar si existe el reporte consultado */
            $reportModel = self::getReportModel($request['id']);
            if ($reportModel != null) {

                $arrayColumnsSelected = json_decode($reportModel->jsonReportePersonalizado)->arrayColumnsSelected;
                $filterOperation = json_decode($reportModel->jsonReportePersonalizado)->filterOperation;

                foreach ($arrayModels as &$model) {

                    foreach ($model['schema'] as &$row) {
                        if (in_array($row['column'], $arrayColumnsSelected)) {
                            $row['value'] = true;
                        }
                    }
                }
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => [
                    'listCustomReports' => $listCustomReports,
                    'arrayModels' => $arrayModels,
                    'filterOperation' => $filterOperation, //
                ],
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

    /** Guardar reporte */
    public function actionSaveReport()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsSearchReport'], Yii::$app->user->identity->rol->rolesTiposOperaciones))
        {
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

                $arrayColumnsSelected = [];

                foreach ($request['arrayModels'] as $key => $value) {
                    foreach ($value['schema'] as $schema) {
                        if ($schema['value'] == true) {
                            $arrayColumnsSelected[] = $schema['column'];
                        }
                    }
                }

                if (count($arrayColumnsSelected) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorCountColumnSelected')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $jsonReportePersonalizado = [
                    'arrayColumnsSelected' => $arrayColumnsSelected,
                    'filterOperation'      => isset($request['filterOperation']) ? $request['filterOperation'] : [],
                ];

                $id = $request['id'];
                $dataReportModal = $request['dataReportModal'];
                // Log
                $type = "updateReportCustomer";

                $model = self::getReportModel($id);

                if ($model == null || $dataReportModal['isNewReport'] == true) {
                
                    $model = new ReportesPersonalizados;
                    $model->estadoReportePersonalizado = Yii::$app->params['statusTodoText']['Activo'];
                    $model->creacionReportePersonalizado = date('Y-m-d H:i:s');
                    $model->idUserCreadorReportePersonalizado = Yii::$app->user->identity->id;

                    // Log
                    $type = "createReportCustomer";
                
                }

                $model->nombreReportePersonalizado = $dataReportModal['nombreReportePersonalizado'];
                $model->descripcionReportePersonalizado = $dataReportModal['observacion'];
                $model->jsonReportePersonalizado = json_encode($jsonReportePersonalizado);

                // print_r($model->jsonReportePersonalizado);die();

                if (!$model->save()) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                };

                ////////////// Log //////////////

                $user = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->params['permissionsSearchReport'], //Modulo
                    Yii::$app->params['eventosLogText'][$type] . $dataReportModal['nombreReportePersonalizado'] . ', al usuario: ' . $user->nombreUserDetalles.' '.$user->apellidoUserDetalles, ', con la descripción: '. $dataReportModal['observacion'], // texto para almacenar en el evento
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                /* Listado de reportes guardados */
                $reportesPersonalizados = ReportesPersonalizados::find()
                    ->select(['idReportePersonalizado','nombreReportePersonalizado', 'descripcionReportePersonalizado'])
                    ->where(['estadoReportePersonalizado' => Yii::$app->params['statusTodoText']['Activo']])
                    ->all();

                $listCustomReports[] = ['id' => '', 'val' => '-- Nuevo reporte --', 'description' => ''];
                foreach ($reportesPersonalizados as $value) {
                    $listCustomReports[] = ['id' => $value->idReportePersonalizado, 'val' => $value->nombreReportePersonalizado, 'description' => $value->descripcionReportePersonalizado];
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' =>  Yii::t('app', 'successReportCustom',['report' => $dataReportModal['nombreReportePersonalizado']]),
                    'data' => [
                        'listCustomReports' => $listCustomReports,
                        'idReportePersonalizado' => $model->idReportePersonalizado,
                    ],
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

    public static function createFileToDownload($dataTitles, $dataFile, $type, $nameFile) {

        $typeFile = "";
        $responseReturn = [];

        $path = Yii::getAlias('@webroot') .
            "/" . Yii::$app->params['routeDownloads'] .
            "/" . "reportes" .
            "/" . $type . "/";

        /*** Validar creación de la carpeta***/
        if (!file_exists($path)) {
            if (!FileHelper::createDirectory($path, $mode = 0775, $recursive = true)) {
                return false;
            }
        }
        /*** Fin Validar creación de la carpeta***/

        if ($type === 'pdf') {
            $typeFile = ".pdf";
            $dataUser = [
                'nombre' => '',
                'cargo'  => '',
                'fecha'  => date("Y-m-d H:i A")
            ];
            $footer = Yii::t('app','footerFormatoUnicoInv');
    
            $dataCreateFile = array('titles' => $dataTitles, 'data' => $dataFile);

            PdfController::generar_pdf_formatoh('GestiondeReportes','createFileReport', $nameFile, $path, $dataCreateFile, [], $dataUser, $footer);
        }

        if ($type === 'xlsx' || $type === 'csv') {
            $typeFile = "." .$type;
            $responseReturn = HelperGenerateExcel::generateReporte($path, $nameFile, $dataTitles, $dataFile, ($type === 'xlsx') ? 'Xlsx' : 'Csv');
        }

        if(file_exists($path . $nameFile . $typeFile)) {
            $pathFile = $path . $nameFile . $typeFile;
        } else {
            return false;
        }

        return array('return' => $responseReturn, 'path' => $pathFile);
    }

    public function actionGenerateReport()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsSearchReport'], Yii::$app->user->identity->rol->rolesTiposOperaciones))
        {
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

                ini_set('memory_limit', '3073741824');
                ini_set('max_execution_time', 900);

                $limitRecords = Yii::$app->params['limitRecordsReports'];

                # request adicional en initial list
                $dataParamsAdd = $request['dataParamsAdd'];

                $idReportePersonalizado = $dataParamsAdd['idReportePersonalizado'];
                $arrayModels = $dataParamsAdd['arrayModels'];

                # Consultar si hay un reporte seleccionado
                $reportModel = self::getReportModel($idReportePersonalizado);
                if ($reportModel != null) {
                    # Asignar filtros originales del reporte si el parámetro del initial-list viene vacio
                    if (!isset($request['filterOperation']) || !is_array($request['filterOperation'])) {
                        $request['filterOperation'] = json_decode($reportModel->jsonReportePersonalizado)->filterOperation;
                    }else{
                       // $request['filterOperation'] = $dataParamsAdd['filterOperation'] ?? [];
                    }
                }

                # Array de impus de tipo fecha
                $arrayImputsDate = [
                    'RAD_fechaVencimientoRadiRadicadosInicial', 'RAD_fechaVencimientoRadiRadicadosFinal',
                    'RAD_creacionRadiRadicadoInicial',          'RAD_creacionRadiRadicadoFinal',
                    'RAD_fechaDocumentoRadiRadicadoInicial',    'RAD_fechaDocumentoRadiRadicadoFinal',
                    'GDE_creacionGdExpedienteInicial',          'GDE_creacionGdExpedienteFinal',
                    'GDE_fechaProcesoGdExpedienteInicial',      'GDE_fechaProcesoGdExpedienteFinal',
                    'GAP_creacionGaPrestamoInicial',            'GAP_creacionGaPrestamoFinal',
                    'RLG_fechaRadiLogRadicadoInicial',          'RLG_fechaRadiLogRadicadoFinal',
                    'isRadicado',
                    /** Espacio para personalizacion del cliente */
                    /** Fin Espacio para personalizacion del cliente */
                ];

                // if (!is_array($request) && !isset($request['filterOperation'])) {
                //     $request = array('filterOperation' =>  array(0 => array('RAD_creacionRadiRadicadoInicial' => date("Y-m-d"), 'RAD_creacionRadiRadicadoFinal' => date("Y-m-d"))));
                // } else {
                //     if (!isset($request['filterOperation'][0]['RAD_creacionRadiRadicadoInicial']) || $request['filterOperation'][0]['RAD_creacionRadiRadicadoInicial'] = "") {
                //         $request['filterOperation'][0]['RAD_creacionRadiRadicadoInicial'] = date("Y-m-d");
                //     }

                //     if (!isset($request['filterOperation'][0]['RAD_creacionRadiRadicadoFinal']) || $request['filterOperation'][0]['RAD_creacionRadiRadicadoFinal'] = "") {
                //         $request['filterOperation'][0]['RAD_creacionRadiRadicadoFinal'] = date("Y-m-d");
                //     }
                // }

                # Iteración de los filtros seleccionados
                $dataWhere = [];
                if (isset($request['filterOperation']) && is_array($request['filterOperation'])) {
                    foreach ($request['filterOperation'] as $field) {
                        foreach ($field as $key => $info) {

                            if ($key == 'inputFilterLimit') {
                                $limitRecords = $info;
                                continue;
                            }

                            //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                            if (in_array($key, $arrayImputsDate)) {
                                if( isset($info) && $info !== null && trim($info) !== ''){
                                   $dataWhere[$key] = $info;
                                }

                            } elseif ($key == 'RAD_idGdTrdTipoDocumental') {
                                if ($info !== null) {
                                    $dataWhere[$key] = $info;
                                }
                            } elseif ($key == 'RAD_PrioridadRadiRadicados') {
                                if ($info !== null) {
                                    $dataWhere[$key] = $info;
                                }
                            } elseif ($key == 'RAD_isRadicado') {
                                if ($info !== null) {
                                    $dataWhere[$key] = $info;
                                }
                            /** Espacio para personalizacion del cliente */
                            /** Fin Espacio para personalizacion del cliente */
                            } else {
                                if( isset($info) && $info !== null && !empty($info)){
                                    # Validacion cuando es un array
                                    if(is_array($info)){
                                        $dataWhere[$key] = $info;
                                    }if($info != '') {  // O un string
                                        $dataWhere[$key] = $info;
                                    }
                                }
                            }
                        }
                    }
                }

                $modelsAlias = [];
                $modelsSelected = [];
                $modelsRelationSelected = [];
                $columnsSelectedAlias = [];
                $columnsSelected = [];
                $arraySeparadores = [];

                $arrayTypeFilter = [
                    'LIKE' => [],
                    'IN' => [],
                    'DATE' => [],
                ];

                foreach ($arrayModels as $key => $value) {
                    $modelsAlias[$value['model']] = $value['alias'];

                    foreach ($value['schema'] as $schema) {
                        if ($schema['value'] == true) {

                            $arraySeparadores[] = 'separador' . $value['model'];
                            $modelsSelected[]   = $value['model'];
                            $columnsSelected[]  = $schema['column'];

                            /** */
                            if ($value['alias'] == 'REM') {

                                switch ($schema['column']) {
                                    case 'REM_idTipoPersona':
                                        $columnsSelectedAlias[] = $schema['infoRelation']['foreingAlias'] .'.'. $schema['infoRelation']['foreingColumn'] . ' AS ' . $schema['column'];
                                        $modelsRelationSelected[] = $schema['infoRelation'];
                                    break;
                                    case 'REM_ciudad':
                                        $columnsSelectedAlias[] = 'CIUCLIREMIX' .'.'. 'nomNivelGeografico3' . ' AS ' . 'REM_ciudad_cli';
                                        $columnsSelectedAlias[] = 'CIUUSRREMIX' .'.'. 'nomNivelGeografico3' . ' AS ' . 'REM_ciudad_usr';
                                    break;
                                    case 'REM_direccion':
                                        $columnsSelectedAlias[] = 'CLIREMIX' .'.'. 'direccionCliente' . ' AS ' . 'REM_direccionCliente';
                                        $columnsSelectedAlias[] = 'DEPEREMIX' .'.'. 'codigoGdTrdDependencia' . ' AS ' . 'REM_codigoGdTrdDependencia';
                                        $columnsSelectedAlias[] = 'DEPEREMIX' .'.'. 'nombreGdTrdDependencia' . ' AS ' . 'REM_nombreGdTrdDependencia';
                                    break;
                                    case 'REM_documento':
                                        $columnsSelectedAlias[] = 'CLIREMIX' .'.'. 'numeroDocumentoCliente' . ' AS ' . 'REM_numeroDocumentoCliente';
                                        $columnsSelectedAlias[] = 'USRREMIX' .'.'. 'documento' . ' AS ' . 'REM_documentoUser';
                                    break;
                                    case 'REM_nombre':
                                        $columnsSelectedAlias[] = 'CLIREMIX' .'.'. 'nombreCliente' . ' AS ' . 'REM_nombreCliente';
                                        $columnsSelectedAlias[] = 'USRREMIX' .'.'. 'nombreUserDetalles' . ' AS ' . 'REM_nombreUserDetalles';
                                        $columnsSelectedAlias[] = 'USRREMIX' .'.'. 'apellidoUserDetalles' . ' AS ' . 'REM_apellidoUserDetalles';
                                    break;
                                    /** Espacio para personalizacion del cliente */
                                    case 'REM_codigoSap':
                                        $columnsSelectedAlias[] = 'CLIREMIX' .'.'. 'codigoSap' . ' AS ' . 'REM_codigoSap';
                                    break;
                                    /** Fin Espacio para personalizacion del cliente */
                                }

                                if (!in_array('REM.idTipoPersona AS REM_idTipoPersona_id', $columnsSelectedAlias)) {
                                    $columnsSelectedAlias[] = 'REM' .'.'. 'idTipoPersona' . ' AS ' . 'REM_idTipoPersona_id';
                                }
                                if (!in_array('REM.idRadiPersona AS REM_idRadiPersona', $columnsSelectedAlias)) {
                                    $columnsSelectedAlias[] = 'REM' .'.'. 'idRadiPersona' . ' AS ' . 'REM_idRadiPersona';
                                }

                            } else {

                                if (!empty($schema['infoRelation'])) {
                                    if ($schema['infoRelation']['type'] == 'model') {

                                        if (is_array($schema['infoRelation']['foreingColumn'])) {
                                            $i = 0;
                                            foreach ($schema['infoRelation']['foreingColumn'] as $column) {
                                                $i++;
                                                $columnsSelectedAlias[] = $schema['infoRelation']['foreingAlias'] .'.'. $column . ' AS ' . $schema['column'].'_'.$i;
                                            }
                                        } else {
                                            $columnsSelectedAlias[] = $schema['infoRelation']['foreingAlias'] .'.'. $schema['infoRelation']['foreingColumn'] . ' AS ' . $schema['column'];
                                        }

                                    } elseif ($schema['infoRelation']['type'] == 'modelRemitente') {

                                        $columnsSelectedAlias[] = 'REMIT' .'.'. 'idTipoPersona' . ' AS ' . 'RAD_idTipoPersona';
                                        $columnsSelectedAlias[] = 'CLIREMI' .'.'. 'nombreCliente' . ' AS ' . 'RAD_nombreCliente';
                                        $columnsSelectedAlias[] = 'USRREMI' .'.'. 'nombreUserDetalles' . ' AS ' . 'RAD_nombreUser';
                                        $columnsSelectedAlias[] = 'USRREMI' .'.'. 'apellidoUserDetalles' . ' AS ' . 'RAD_apellidoUser';

                                    } else {
                                        $columnsSelectedAlias[] = $value['alias'] .'.'. substr($schema['column'], 4) . ' AS ' . $schema['column'];
                                    }
                                    $modelsRelationSelected[] = $schema['infoRelation'];
                                } else {
                                    $columnsSelectedAlias[] = $value['alias'] .'.'. substr($schema['column'], 4) . ' AS ' . $schema['column'];
                                }

                            }
                            /** */

                            $arrayTypeFilter[$schema['typeFilter']][] = $schema['column'];
                        }
                    }
                }
                $modelsSelected = array_unique($modelsSelected);
                $arraySeparadores = array_unique($arraySeparadores);

                if (count($columnsSelectedAlias) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorCountColumnSelected')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Alias adicionales para tablas necesarias en la relación
                $modelsAlias['GdExpedientesInclusion'] = 'GDEI'; 
                
                $modelsAlias['ClientesRemi'] = 'CLIREMIX';
                $modelsAlias['UserRemi'] = 'UREMIX';
                $modelsAlias['UserDetallesRemi'] = 'USRREMIX';

                $modelsAlias['RadiInformados'] = 'RADIINFOR';

                $modelsAlias['NivelGeo3CliRemi'] = 'CIUCLIREMIX';
                $modelsAlias['NivelGeo3UsrRemi'] = 'CIUUSRREMIX';

                $modelsAlias['GdTrdDependenciasRemi'] = 'DEPEREMIX';
                $modelsAlias['CgRegionalesRemi'] = 'REGIOREMIX';


                $tables = [
                    # Tablas vistas por el usuario
                    'RadiRadicados'    => RadiRadicados::tableName()    . ' AS ' . $modelsAlias['RadiRadicados'],
                    'RadiRemitentes'   => RadiRemitentes::tableName()   . ' AS ' . $modelsAlias['RadiRemitentes'],
                    'RadiLogRadicados' => RadiLogRadicados::tableName() . ' AS ' . $modelsAlias['RadiLogRadicados'],
                    'GdExpedientes'    => GdExpedientes::tableName()    . ' AS ' . $modelsAlias['GdExpedientes'],
                    'GaArchivo'        => GaArchivo::tableName()        . ' AS ' . $modelsAlias['GaArchivo'],
                    'GaPrestamos'      => GaPrestamos::tableName()      . ' AS ' . $modelsAlias['GaPrestamos'],
                    # Tablas adicionales necesarias para la relación principal
                    'GdExpedientesInclusion' => GdExpedientesInclusion::tableName() . ' AS ' . $modelsAlias['GdExpedientesInclusion'],
                    'ClientesRemi'           => Clientes::tableName()               . ' AS ' . $modelsAlias['ClientesRemi'],
                    'UserRemi'               => User::tableName()                   . ' AS ' . $modelsAlias['UserRemi'],
                    'UserDetallesRemi'       => UserDetalles::tableName()           . ' AS ' . $modelsAlias['UserDetallesRemi'],

                    'RadiInformados'         => RadiInformados::tableName()         . ' AS ' . $modelsAlias['RadiInformados'],
                    
                    'NivelGeo3CliRemi'       => NivelGeografico3::tableName()           . ' AS ' . $modelsAlias['NivelGeo3CliRemi'],
                    'NivelGeo3UsrRemi'       => NivelGeografico3::tableName()           . ' AS ' . $modelsAlias['NivelGeo3UsrRemi'],

                    'GdTrdDependenciasRemi'  => GdTrdDependencias::tableName()           . ' AS ' . $modelsAlias['GdTrdDependenciasRemi'],
                    'CgRegionalesRemi'       => CgRegionales::tableName()           . ' AS ' . $modelsAlias['CgRegionalesRemi'],

                    # Tablas adicionales necesarias para la relación de campos dependientes
                    'User'                     => User::tableName(),
                    'UserDetalles'             => UserDetalles::tableName(),
                    'GdTrdTiposDocumentales'   => GdTrdTiposDocumentales::tableName(),
                    'GdTrdDependencias'        => GdTrdDependencias::tableName(),
                    'CgTiposRadicados'         => CgTiposRadicados::tableName(),
                    'CgMediosRecepcion'        => CgMediosRecepcion::tableName(),
                    'CgTransaccionesRadicados' => CgTransaccionesRadicados::tableName(),
                    'GdTrdSeries'              => GdTrdSeries::tableName(),
                    'GdTrdSubseries'           => GdTrdSubseries::tableName(),
                    'GaEdificio'               => GaEdificio::tableName(),
                    'GaPiso'                   => GaPiso::tableName(),
                    'GaBodega'                 => GaBodega::tableName(),
                    'Remitentes'               => RadiRemitentes::tableName(),
                    'Clientes'                 => Clientes::tableName(),
                    'RadiRadicadosPadre'       => RadiRadicados::tableName(),
                    'TiposPersonas'            => TiposPersonas::tableName(),
                    /** Espacio para personalizacion del cliente */
                    /** Fin Espacio para personalizacion del cliente */
                ];

                /** Definir filtros adicionales en join de clientes u usuario para los remitentes */
                $whereLeft_ClientesRemi = [
                    ['operador' => 'AND', 'type' => 'valueInput', 'tbl1' => 'REM' , 'value1'=> 'idTipoPersona', 'comparador2' => 'IN', 'tbl2' => null,  'value2' => [Yii::$app->params['tipoPersonaText']['personaJuridica'], Yii::$app->params['tipoPersonaText']['personaNatural']]],
                ];

                $whereLeft_UserRemi = [
                    ['operador' => 'AND', 'type' => 'valueInput', 'tbl1' => 'REM' , 'value1'=> 'idTipoPersona', 'comparador2' => '=', 'tbl2' => null,  'value2' => Yii::$app->params['tipoPersonaText']['funcionario']],
                ];
                /** Fin Definir filtros adicionales en join de clientes u usuario para los remitentes */

                if ( (in_array('RadiLogRadicados', $modelsSelected) || in_array('RadiRemitentes', $modelsSelected)) && !in_array('RadiRadicados', $modelsSelected) ) {
                    $modelsSelected[] = 'RadiRadicados';
                }

                if (count($modelsSelected) == 1) { // Un solo modelo

                    $modelRelation = (new \yii\db\Query())
                        ->select($columnsSelectedAlias)
                        ->from($tables[$modelsSelected[0]]);

                    if ($modelsSelected[0] == 'RadiRemitentes') {
                        $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['ClientesRemi'], [$modelsAlias['ClientesRemi'] => 'idCliente', $modelsAlias['RadiRemitentes'] => 'idRadiPersona'], $whereLeft_ClientesRemi);

                        $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['UserRemi'], [$modelsAlias['UserRemi'] => 'id', $modelsAlias['RadiRemitentes'] => 'idRadiPersona'], $whereLeft_UserRemi);
                        $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['UserDetallesRemi'], [$modelsAlias['UserDetallesRemi'] => 'idUser', $modelsAlias['UserRemi'] => 'id']);

                        if (in_array('REM_ciudad', $columnsSelected) || in_array('REM_direccion', $columnsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['NivelGeo3CliRemi'], [$modelsAlias['NivelGeo3CliRemi'] => 'nivelGeografico3', $modelsAlias['ClientesRemi'] => 'idNivelGeografico3']);
                            
                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['GdTrdDependenciasRemi'], [$modelsAlias['GdTrdDependenciasRemi'] => 'idGdTrdDependencia', $modelsAlias['UserRemi'] => 'idGdTrdDependencia']);
                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['CgRegionalesRemi'], [$modelsAlias['CgRegionalesRemi'] => 'idCgRegional', $modelsAlias['GdTrdDependenciasRemi'] => 'idCgRegional']);
                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['NivelGeo3UsrRemi'], [$modelsAlias['NivelGeo3UsrRemi'] => 'nivelGeografico3', $modelsAlias['CgRegionalesRemi'] => 'idNivelGeografico3']);
                        }
                    }

                } else { // Varios modelos

                    if (in_array('RadiRadicados', $modelsSelected)) { // Cuando contiene la tabla de radicados

                        $modelRelation = (new \yii\db\Query())
                            ->select($columnsSelectedAlias)
                            ->from($tables['RadiRadicados']);

                        if (in_array('RadiLogRadicados', $modelsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['RadiLogRadicados'], [$modelsAlias['RadiLogRadicados'] => 'idRadiRadicado', $modelsAlias['RadiRadicados'] => 'idRadiRadicado']);
                        }

                        if (in_array('GdExpedientes', $modelsSelected) || in_array('GaArchivo', $modelsSelected) || in_array('GaPrestamos', $modelsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GdExpedientesInclusion'], [$modelsAlias['GdExpedientesInclusion'] => 'idRadiRadicado', $modelsAlias['RadiRadicados'] => 'idRadiRadicado']);
                        }

                        if (in_array('GdExpedientes', $modelsSelected) || in_array('GaArchivo', $modelsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GdExpedientes'], [$modelsAlias['GdExpedientes'] => 'idGdExpediente', $modelsAlias['GdExpedientesInclusion'] => 'idGdExpediente']);
                        }

                        if (in_array('GaArchivo', $modelsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GaArchivo'], [$modelsAlias['GaArchivo'] => 'idGdExpediente', $modelsAlias['GdExpedientes'] => 'idGdExpediente']);
                        }

                        if (in_array('GaPrestamos', $modelsSelected)) {
                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GaPrestamos'], [$modelsAlias['GaPrestamos'] => 'idGdExpedienteInclusion', $modelsAlias['GdExpedientesInclusion'] => 'idGdExpedienteInclusion']);
                        }

                        //
                        if (in_array('RadiRemitentes', $modelsSelected)) {

                            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['RadiRemitentes'], [$modelsAlias['RadiRemitentes'] => 'idRadiRadicado', $modelsAlias['RadiRadicados'] => 'idRadiRadicado']);

                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['ClientesRemi'], [$modelsAlias['ClientesRemi'] => 'idCliente', $modelsAlias['RadiRemitentes'] => 'idRadiPersona'], $whereLeft_ClientesRemi);

                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['UserRemi'], [$modelsAlias['UserRemi'] => 'id', $modelsAlias['RadiRemitentes'] => 'idRadiPersona'], $whereLeft_UserRemi);
                            $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['UserDetallesRemi'], [$modelsAlias['UserDetallesRemi'] => 'idUser', $modelsAlias['UserRemi'] => 'id']);

                            if (in_array('REM_ciudad', $columnsSelected) || in_array('REM_direccion', $columnsSelected)) {
                                $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['NivelGeo3CliRemi'], [$modelsAlias['NivelGeo3CliRemi'] => 'nivelGeografico3', $modelsAlias['ClientesRemi'] => 'idNivelGeografico3']);
                                
                                $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['GdTrdDependenciasRemi'], [$modelsAlias['GdTrdDependenciasRemi'] => 'idGdTrdDependencia', $modelsAlias['UserRemi'] => 'idGdTrdDependencia']);
                                $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['CgRegionalesRemi'], [$modelsAlias['CgRegionalesRemi'] => 'idCgRegional', $modelsAlias['GdTrdDependenciasRemi'] => 'idCgRegional']);
                                $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['NivelGeo3UsrRemi'], [$modelsAlias['NivelGeo3UsrRemi'] => 'nivelGeografico3', $modelsAlias['CgRegionalesRemi'] => 'idNivelGeografico3']);
                            }
                        }

                    } else { // Cuando NO contiene la tabla de radicados

                        if (in_array('GdExpedientes', $modelsSelected) || in_array('GaArchivo', $modelsSelected) || in_array('GaPrestamos', $modelsSelected)) { // Cuando contiene la tabla de radicados

                            $modelRelation = (new \yii\db\Query())
                                ->select($columnsSelectedAlias)
                                ->from($tables['GdExpedientes']);

                            if (in_array('GaArchivo', $modelsSelected)) {
                                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GaArchivo'], [$modelsAlias['GaArchivo'] => 'idGdExpediente', $modelsAlias['GdExpedientes'] => 'idGdExpediente']);
                            }

                            if (in_array('GaPrestamos', $modelsSelected)) {
                                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GdExpedientesInclusion'], [$modelsAlias['GdExpedientesInclusion'] => 'idGdExpediente', $modelsAlias['GdExpedientes'] => 'idGdExpediente']);
                                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tables['GaPrestamos'], [$modelsAlias['GaPrestamos'] => 'idGdExpedienteInclusion', $modelsAlias['GdExpedientesInclusion'] => 'idGdExpedienteInclusion']);
                            }

                        }

                    }

                }

                /** */
                foreach ($modelsRelationSelected as $key => $value) {
                    if ($value['type'] == 'model') {
                        $tableRelation = $tables[$value['foreingModel']] . ' AS ' . $value['foreingAlias'];
                        $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tableRelation , [$value['foreingAlias'] => $value['foreignKey'], $value['alias'] => $value['key']]);

                    } elseif ($value['type'] == 'modelRemitente') {
                        $tableRelation1 = $tables[$value['foreingModel1']] . ' AS ' . $value['foreingAlias1'];
                        $modelRelation  = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tableRelation1 , [$value['foreingAlias1'] => $value['foreignKey1'], $value['alias1'] => $value['key1']]);

                        $tableRelation2 = $tables[$value['foreingModel2']] . ' AS ' . $value['foreingAlias2'];
                        $modelRelation  = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tableRelation2 , [$value['foreingAlias2'] => $value['foreignKey2'], $value['foreingAlias1'] => $value['key2']]);

                        $tableRelation3 = $tables[$value['foreingModel3']] . ' AS ' . $value['foreingAlias3'];
                        $modelRelation  = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tableRelation3 , [$value['foreingAlias3'] => $value['foreignKey3'], $value['foreingAlias1'] => $value['key3']]);
                    }
                }
                /** */
                
                /** Validar si se realizan filtros de radicado por dependencia o usuario  segun el nivel de búsqueda del usuario logueado */
                if (in_array('RadiRadicados', $modelsSelected)) { // Cuando contiene la tabla de radicados

                    # Nivel de busqueda del logueado
                    $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

                    if ($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico'] || $idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {

                        $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tables['RadiInformados'], [$modelsAlias['RadiInformados'] => 'idRadiRadicado', $modelsAlias['RadiRadicados'] => 'idRadiRadicado']);

                        # Se visualiza solo los radicados del usuario logueado si su nivel de búsqueda es básico
                        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
                            $modelRelation->andWhere(['or',
                                [$modelsAlias['RadiRadicados']  . '.user_idTramitador' => Yii::$app->user->identity->id],
                                [$modelsAlias['RadiInformados'] . '.idUser'            => Yii::$app->user->identity->id]
                            ]);
                        }

                        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
                        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {
                            $modelRelation->andWhere(['or',
                                [$modelsAlias['RadiRadicados']  . '.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia],
                                [$modelsAlias['RadiInformados'] . '.idUser'                  => Yii::$app->user->identity->id]
                            ]);
                        }
                    }
                }
                /** Fin Validar si se realizan filtros de radicado por dependencia o usuario  segun el nivel de búsqueda del usuario logueado */

                /** Inicio Asignar filtros a la consulta */
                foreach ($dataWhere as $field => $value) {
                    if (in_array($field, $arrayTypeFilter['IN'])) {
                        $modelRelation->andWhere(['IN', substr($field, 0, 3) . '.' . substr($field, 4), $value]);

                    } elseif (in_array($field, $arrayTypeFilter['LIKE'])) {

                        switch ($field) {
                            case 'RAD_idTipoPersona':
                                $modelRelation->andWhere(['or',
                                    [Yii::$app->params['like'], 'CLIREMI.nombreCliente', $value],
                                    [Yii::$app->params['like'], 'USRREMI.nombreUserDetalles', $value],
                                    [Yii::$app->params['like'], 'USRREMI.apellidoUserDetalles', $value],
                                ]);
                            break;
                            case 'RAD_idRadiRadicadoPadre':
                                $modelRelation->andWhere([Yii::$app->params['like'], 'RPADRE.numeroRadiRadicado', $value]);
                            break;
                            case 'REM_direccion':
                                $modelRelation->andWhere(['or',
                                    [Yii::$app->params['like'], 'CLIREMIX.direccionCliente', $value],
                                    [Yii::$app->params['like'], 'DEPEREMIX.codigoGdTrdDependencia', $value],
                                    [Yii::$app->params['like'], 'DEPEREMIX.nombreGdTrdDependencia', $value],
                                ]);
                            break;
                            case 'REM_documento':
                                $modelRelation->andWhere(['or',
                                    [Yii::$app->params['like'], 'CLIREMIX.numeroDocumentoCliente', $value],
                                    [Yii::$app->params['like'], 'USRREMIX.documento', $value],
                                ]);
                            break;
                            case 'REM_nombre':
                                $modelRelation->andWhere(['or',
                                    [Yii::$app->params['like'], 'CLIREMIX.nombreCliente', $value],
                                    [Yii::$app->params['like'], 'USRREMIX.nombreUserDetalles', $value],
                                    [Yii::$app->params['like'], 'USRREMIX.apellidoUserDetalles', $value],
                                ]);
                            break;
                            case 'REM_ciudad':
                                $modelRelation->andWhere(['or',
                                    [Yii::$app->params['like'], 'CIUCLIREMIX' .'.'. 'nomNivelGeografico3', $value],
                                    [Yii::$app->params['like'], 'CIUUSRREMIX' .'.'. 'nomNivelGeografico3', $value],
                                ]);
                            break;
                            /** Espacio para personalizacion del cliente */
                            case 'REM_codigoSap':
                                $modelRelation->andWhere([Yii::$app->params['like'], 'CLIREMIX' .'.'. 'codigoSap', $value]);
                            break;
                            /** Fin Espacio para personalizacion del cliente */
                            default:
                                $modelRelation->andWhere([Yii::$app->params['like'], substr($field, 0, 3) . '.' . substr($field, 4), $value]);
                            break;
                        }

                    } elseif (in_array(substr($field, 0, -7), $arrayTypeFilter['DATE']) || in_array(substr($field, 0, -5), $arrayTypeFilter['DATE'])) {
                        switch ($field) {
                            # Fechas Iniciales
                            case 'RAD_fechaVencimientoRadiRadicadosInicial': 
                            case 'RAD_creacionRadiRadicadoInicial': 
                            case 'RAD_fechaDocumentoRadiRadicadoInicial':
                            case 'GDE_creacionGdExpedienteInicial':
                            case 'GDE_fechaProcesoGdExpedienteInicial':
                            case 'GAP_creacionGaPrestamoInicial':
                            case 'RLG_fechaRadiLogRadicadoInicial';
                                $modelRelation->andWhere(['>=', substr($field, 0, 3) . '.' . substr($field, 4, -7), trim($value) . Yii::$app->params['timeStart']]);
                            break;
                            # Fechas Finales
                            case 'RAD_fechaVencimientoRadiRadicadosFinal': 
                            case 'RAD_creacionRadiRadicadoFinal': 
                            case 'RAD_fechaDocumentoRadiRadicadoFinal':
                            case 'GDE_creacionGdExpedienteFinal':
                            case 'GDE_fechaProcesoGdExpedienteFinal':
                            case 'GAP_creacionGaPrestamoFinal':
                            case 'RLG_fechaRadiLogRadicadoFinal';
                                $modelRelation->andWhere(['<=', substr($field, 0, 3) . '.' . substr($field, 4, -5), trim($value) . Yii::$app->params['timeEnd']]);
                            break;
                            /** Espacio para personalizacion del cliente */
                            /** Fin Espacio para personalizacion del cliente */
                        }
                    }
                }
                /** Fin Asignar filtros a la consulta */

                /** Definir consulta */

                if (is_array($request) && isset($request['typeDownload'])) {
                    $modelRelation = $modelRelation->all();
                    /** */
                    foreach ($modelRelation as &$row) {

                        /* Campos concatenados */
                        foreach ($row as $key => $value) {
                            if (substr($key, -2) == '_1') {
                                $row[substr($key, 0, -2)] = $row[substr($key, 0, -2).'_1'] .  ' ' . $row[substr($key, 0, -2).'_2'];
                            }
                        }
                        /* Fin Campos concatenados */

                        ////////////
                        if (array_key_exists('REM_nombreCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_nombre'] = $row['REM_nombreUserDetalles'].' '.$row['REM_apellidoUserDetalles'];
                            } else {
                                $row['REM_nombre'] = $row['REM_nombreCliente'];
                            }
                        }
                        if (array_key_exists('REM_numeroDocumentoCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_documento'] = $row['REM_documentoUser'];
                            } else {
                                $row['REM_documento'] = $row['REM_numeroDocumentoCliente'];
                            }
                        }
                        if (array_key_exists('REM_ciudad_cli', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_ciudad'] = $row['REM_ciudad_usr'];
                            } else {
                                $row['REM_ciudad'] = $row['REM_ciudad_cli'];
                            }
                        }
                        if (array_key_exists('REM_direccionCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_direccion'] = $row['REM_codigoGdTrdDependencia'] .' - '. $row['REM_nombreGdTrdDependencia'];
                            } else {
                                $row['REM_direccion'] = $row['REM_direccionCliente'];
                            }
                        }
                        ////////////

                        foreach ($modelsRelationSelected as $value) {

                            if ($value['type'] == 'modelRemitente') {

                                if ($row['RAD_idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                    $row['RAD_idTipoPersona'] = $row['RAD_nombreUser'].' '.$row['RAD_apellidoUser'];
                                } else {
                                    $row['RAD_idTipoPersona'] = $row['RAD_nombreCliente'];
                                }

                            } else {
                                $keyInput = $value['alias'] . '_' . $value['key'];
                                if ($value['type'] == 'params' && isset($row[$keyInput])) {
                                    $row[$keyInput] = Yii::$app->params[$value['foreingModel']][$row[$keyInput]];
                                } elseif ($value['type'] == 'i18n' && isset($row[$keyInput])) {
                                    $row[$keyInput] = Yii::t('app', $value['foreingModel'])[$row[$keyInput]];
                                }
                            }
                        }
                    }
                    /** */

                    $responseCreateFile = $this->createFileToDownload($request['dtTitles'], $modelRelation, $request['typeDownload'], 'personalizado_' . Yii::$app->user->identity->id);

                    Yii::$app->response->statusCode = 200;
                    if (!$responseCreateFile) {
                        $response = [
                            'typeMsg' => 'danger',
                            'message' => 'No fue posible crear el archivo',
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {
                        $response = [
                            'typeMsg' => 'success',
                            'message' => 'Archivo creado correctamente y listo para descargar',
                            'responseCreateFile' => $responseCreateFile
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                } else {
                    $modelInitial = $modelRelation->all();
                    $modelRelation->limit($limitRecords);
                    $modelRelation = $modelRelation->all();

                    /** */
                    foreach ($modelRelation as &$row) {

                        /* Campos concatenados */
                        foreach ($row as $key => $value) {
                            if (substr($key, -2) == '_1') {
                                $row[substr($key, 0, -2)] = $row[substr($key, 0, -2).'_1'] .  ' ' . $row[substr($key, 0, -2).'_2'];
                            }
                        }
                        /* Fin Campos concatenados */

                        ////////////
                        if (array_key_exists('REM_nombreCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_nombre'] = $row['REM_nombreUserDetalles'].' '.$row['REM_apellidoUserDetalles'];
                            } else {
                                $row['REM_nombre'] = $row['REM_nombreCliente'];
                            }
                        }
                        if (array_key_exists('REM_numeroDocumentoCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_documento'] = $row['REM_documentoUser'];
                            } else {
                                $row['REM_documento'] = $row['REM_numeroDocumentoCliente'];
                            }
                        }
                        if (array_key_exists('REM_ciudad_cli', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_ciudad'] = $row['REM_ciudad_usr'];
                            } else {
                                $row['REM_ciudad'] = $row['REM_ciudad_cli'];
                            }
                        }
                        if (array_key_exists('REM_direccionCliente', $row)) {
                            if ($row['REM_idTipoPersona_id'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                $row['REM_direccion'] = $row['REM_codigoGdTrdDependencia'] .' - '. $row['REM_nombreGdTrdDependencia'];
                            } else {
                                $row['REM_direccion'] = $row['REM_direccionCliente'];
                            }
                        }
                        ////////////

                        foreach ($modelsRelationSelected as $value) {

                            if ($value['type'] == 'modelRemitente') {

                                if ($row['RAD_idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario']) {
                                    $row['RAD_idTipoPersona'] = $row['RAD_nombreUser'].' '.$row['RAD_apellidoUser'];
                                } else {
                                    $row['RAD_idTipoPersona'] = $row['RAD_nombreCliente'];
                                }

                            } else {
                                $keyInput = $value['alias'] . '_' . $value['key'];
                                if ($value['type'] == 'params' && isset($row[$keyInput])) {
                                    $row[$keyInput] = Yii::$app->params[$value['foreingModel']][$row[$keyInput]];
                                } elseif ($value['type'] == 'i18n' && isset($row[$keyInput])) {
                                    $row[$keyInput] = Yii::t('app', $value['foreingModel'])[$row[$keyInput]];
                                }
                            }
                        }
                    }
                    /** */

                    $formType = HelperDynamicForms::setListadoBD('indexCustomReport');
                    $formType = self::proccesFormType($formType, $columnsSelected,  $dataWhere, $arraySeparadores);

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data' => $modelRelation,
                        // 'filtersData' => $formType,
                        // 'infoLimitRecords' => (count($modelRelation) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($modelInitial), 'limitRecords' => $limitRecords]) : false,
                        'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                        'infoLimitRecords' => (count($modelRelation) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($modelInitial), 'limitRecords' => $limitRecords]) : false,
                        'arrayModels' => $arrayModels,
                        'status' => 200,
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


    public static function proccesFormType($formType, $columnsSelected, $dataWhere, $arraySeparadores)
    {
        $fieldGroup = $formType['schema'][0]['fieldArray']['fieldGroup'];

        # where
        $fieldGroupProccesed = [];
        foreach ($fieldGroup as $column) {
            if (in_array($column['keyInput'], $columnsSelected) || ($column['keyInput'] == 'separador' && in_array($column['key'], $arraySeparadores) )) {

                # Procesar data para campos seleccionables [LISTAS]
                switch ($column['key']) {
                    case 'RAD_idTrdTipoDocumental':
                        # Asignación de valores para la lista de tipos documentales
                        $consulta = GdTrdTiposDocumentales::find()
                            ->select(["idGdTrdTipoDocumental as value", "nombreTipoDocumental as label"])
                            ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                            ->orderBy(['nombreTipoDocumental' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'RAD_idTrdDepeUserCreador':
                    case 'RAD_idTrdDepeUserTramitador':
                    case 'GDE_idGdTrdDependencia':
                    case 'GAP_idGdTrdDependenciaArchivo':
                        # Listado de dependencias activas
                        $consulta = GdTrdDependencias::find()
                            ->select(["idGdTrdDependencia as value", "nombreGdTrdDependencia as label", "codigoGdTrdDependencia as codigo"])
                            ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                            ->orderBy(['codigoGdTrdDependencia' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['codigo'].' - '.$value['label'], 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'RAD_idCgTipoRadicado':
                        # Asignación de valores para la lista de tipos radicados
                        $consulta = CgTiposRadicados::find()
                            ->select(["idCgTipoRadicado as value", "nombreCgTipoRadicado as label"])
                            ->orderBy(['nombreCgTipoRadicado' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'RAD_idCgMedioRecepcion':
                        # Asignación de valores para la lista de medios de recepción
                        $consulta = CgMediosRecepcion::find()
                            ->select(["idCgMedioRecepcion as value", "nombreCgMedioRecepcion as label"])
                            ->orderBy(['nombreCgMedioRecepcion' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'REM_idTipoPersona':
                        # Asignación de valores para la lista de medios de recepción
                        $consulta = TiposPersonas::find()
                            ->select(["idTipoPersona as value", "tipoPersona as label"])
                            ->orderBy(['tipoPersona' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [
                            ['label' => '-- Seleccione una opción --', 'value' => null],
                        ];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'RLG_idTransaccion':
                        # Asignación de valores para la lista de transacciones
                        $consulta = CgTransaccionesRadicados::find()
                            ->select(["idCgTransaccionRadicado as value", "titleCgTransaccionRadicado as label"])
                            ->orderBy(['titleCgTransaccionRadicado' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [
                            ['label' => '-- Seleccione una opción --', 'value' => null],
                        ];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'GDE_idGdTrdSerie':
                        $modelTrd = GdTrd::find()->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])->all();
                        $idsSerie = [];
                        $listData = [
                            ['label' => '-- Seleccione una opción --', 'value' => null],
                        ];
                        foreach($modelTrd as $trd){
                            //Solo incluir una serie ya que se repiten en las trd
                            if(!in_array($trd->idGdTrdSerie,  $idsSerie)){
                                $listData[] = [
                                    'value' => $trd->idGdTrdSerie,
                                    'label' => $trd->gdTrdSerie->codigoGdTrdSerie.' - '.$trd->gdTrdSerie->nombreGdTrdSerie.' - TRD Vr'.$trd->versionGdTrd
                                ];

                                $idsSerie[] =  $trd->idGdTrdSerie;
                            }
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'GDE_idGdTrdSubserie':
                        # Procesar solo si hay un valor seleccionado
                        if (array_key_exists($column['key'], $dataWhere) || array_key_exists('GDE_idGdTrdSerie', $dataWhere)) {
                            $valueFilter = isset($dataWhere[$column['key']]) ? $dataWhere[$column['key']] : [];
                            $valueFilterForean = isset($dataWhere['GDE_idGdTrdSerie']) ? $dataWhere['GDE_idGdTrdSerie'] : [];

                            # Asignacion de valores para la lista
                            $consulta = GdTrdSubseries::find()
                                ->select(["idGdTrdSubserie as value", "nombreGdTrdSubserie as label"])
                                ->where(['IN', 'idGdTrdSubserie', $valueFilter])
                                ->orWhere(['IN', 'idGdTrdSerie', $valueFilterForean])
                                ->asArray()->all();

                            $listData = [
                                ['label' => '-- Seleccione una opción --', 'value' => null],
                            ];
                            foreach ($consulta as $key => $value) {
                                $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                            }
                            $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                        }
                    break;
                    case 'GAP_idUser':
                        # Procesar solo si hay un valor seleccionado
                        if (!empty($dataWhere[$column['key']]) || !empty($dataWhere['GAP_idGdTrdDependenciaArchivo'])) {
                            $valueFilter = !empty($dataWhere[$column['key']]) ? $dataWhere[$column['key']] : [];
                            $valueFilterForean = !empty($dataWhere['GAP_idGdTrdDependenciaArchivo']) ? $dataWhere['GAP_idGdTrdDependenciaArchivo'] : [];

                            # Asignacion de valores para la lista de los GaPiso
                            $consulta = User::find()
                                ->where(['IN', 'id', $valueFilter])
                                ->orWhere(['IN', 'idGdTrdDependencia', $valueFilterForean])
                                ->all();

                            $listData = [];
                            foreach ($consulta as $key => $value) {
                                # Usuarios internos
                                if($value->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                                    $listData[] = array(
                                        'label' => $value->userDetalles->nombreUserDetalles. ' ' . $value->userDetalles->apellidoUserDetalles.' - '.$value->userDetalles->cargoUserDetalles,
                                        'value' => intval($value['id'])
                                    );
                                }
                            }
                            $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                        }
                    break;
                    case 'GAA_idGaEdificio':
                        # Asignación de valores para la lista de los edificios
                        $consulta = GaEdificio::find()
                            ->select(["idGaEdificio as value", "nombreGaEdificio as label"])
                            ->where(['estadoGaEdificio' => Yii::$app->params['statusTodoText']['Activo']])
                            ->orderBy(['nombreGaEdificio' => SORT_ASC])
                            ->asArray()->all();

                        $listData = [
                            ['label' => '-- Seleccione una opción --', 'value' => null],
                        ];
                        foreach ($consulta as $key => $value) {
                            $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    case 'GAA_idGaPiso':
                        # Procesar solo si hay un valor seleccionado
                        if (array_key_exists($column['key'], $dataWhere) || array_key_exists('GAA_idGaEdificio', $dataWhere)) {
                            $valueFilter = isset($dataWhere[$column['key']]) ? $dataWhere[$column['key']] : null;
                            $valueFilterForean = isset($dataWhere['GAA_idGaEdificio']) ? $dataWhere['GAA_idGaEdificio'] : null;

                            # Asignacion de valores para la lista de los GaPiso
                            $consulta = GaPiso::find()
                                ->select(["idGaPiso as value", "numeroGaPiso as label"])
                                ->where(['idGaPiso' => $valueFilter])
                                ->orWhere(['idGaEdificio' => $valueFilterForean])
                                ->orderBy(['numeroGaPiso' => SORT_ASC])
                                ->asArray()->all();

                            $listData = [
                                ['label' => '-- Seleccione una opción --', 'value' => null],
                            ];
                            foreach ($consulta as $key => $value) {
                                $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                            }
                            $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                        }
                    break;
                    case 'GAA_idGaBodega':
                        # Procesar solo si hay un valor seleccionado
                        if (array_key_exists($column['key'], $dataWhere) || array_key_exists('GAA_idGaPiso', $dataWhere)) {
                            $valueFilter = isset($dataWhere[$column['key']]) ? $dataWhere[$column['key']] : null;
                            $valueFilterForean = isset($dataWhere['GAA_idGaPiso']) ? $dataWhere['GAA_idGaPiso'] : null;

                            # Asignacion de valores para la lista de los GaBodega
                            $consulta = GaBodega::find()
                                ->select(["idGaBodega as value", "nombreGaBodega as label"])
                                ->where(['idGaBodega' => $valueFilter])
                                ->orWhere(['idGaPiso' => $valueFilterForean])
                                ->orderBy(['nombreGaBodega' => SORT_ASC])
                                ->asArray()->all();

                            $listData = [
                                ['label' => '-- Seleccione una opción --', 'value' => null],
                            ];
                            foreach ($consulta as $key => $value) {
                                $listData[] = array('label' => $value['label'] , 'value' => intval($value['value']) );
                            }
                            $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                        }
                    break;
                    case 'GAP_idTipoPrestamoGaPrestamo':
                        # Asignación de valores para la lista de tipos de prestamo
                        $listData = [
                            ['label' => '-- Seleccione una opción --', 'value' => null],
                        ];
                        foreach ((array) Yii::t('app', 'statusLoanTypeNumber') as $i => $tipo) {
                            $listData[] = array('label' => $tipo , 'value' => intval($i) );
                        }
                        $column['templateOptions']['options'] = $listData; // Se le asigna la variable al template
                    break;
                    /** Espacio para personalizacion del cliente */
                    /** Fin Espacio para personalizacion del cliente */
                    case '':
                    break;
                }

                # Procesar valores por defecto [listas]
                if (array_key_exists($column['key'], $dataWhere)) {
                    $column['defaultValue'] = $dataWhere[$column['key']]; // Se el valor default
                }

                $fieldGroupProccesed[] = $column;
            }else{

                // # Procesar valores por defecto 
                // if (array_key_exists($column['key'], $dataWhere)) {
                //     $column['defaultValue'] = $dataWhere[$column['key']]; // Se el valor default
                // }

                // $fieldGroupProccesed[] = $column;
            }
        }

        $formType['schema'][0]['fieldArray']['fieldGroup'] = $fieldGroupProccesed;
        return $formType;
    }

    public static function getReportModel($idReportePersonalizado) {
        if ($idReportePersonalizado != null || $idReportePersonalizado != '') {
            return ReportesPersonalizados::find()->where(['idReportePersonalizado' => $idReportePersonalizado])->one();
        } else {
            return null;
        }
    }

    /* Array de modelos disonibles para realizar reportes*/
    public static function getArrayModels()
    {   

        $RadiRadicados = new RadiRadicados();
        $RadiRemitentes = new RadiRemitentes();
        $RadiLogRadicados = new RadiLogRadicados();
        $GdExpedientes = new GdExpedientes();
        $GaArchivo = new GaArchivo();
        $GaPrestamos = new GaPrestamos();

        $arrayModels = [
            [
                'model'  => 'RadiRadicados',
                'alias'  => 'RAD',
                'name'   => 'Radicados',
                'value'  => false,
                'schema' => $RadiRadicados->attributeLabelsReport(),
            ],
            [
                'model'  => 'RadiRemitentes',
                'alias'  => 'REM',
                'name'   => 'Remitentes/Destinatarios del radicado',
                'value'  => false,
                'schema' => $RadiRemitentes->attributeLabelsReport(),
            ],
            [
                'model'  => 'RadiLogRadicados',
                'alias'  => 'RLG',
                'name'   => 'Transacciones radicado',
                'value'  => false,
                'schema' => $RadiLogRadicados->attributeLabelsReport(),
            ],
            [
                'model'  => 'GdExpedientes',
                'alias'  => 'GDE',
                'name'   => 'Expedientes',
                'value'  => false,
                'schema' => $GdExpedientes->attributeLabelsReport(),
            ],
            [
                'model'  => 'GaArchivo',
                'alias'  => 'GAA',
                'name'   => 'Archivo',
                'value'  => false,
                'schema' => $GaArchivo->attributeLabelsReport(),
            ],
            [
                'model'  => 'GaPrestamos',
                'alias'  => 'GAP',
                'name'   => 'Prestamos',
                'value'  => false,
                'schema' => $GaPrestamos->attributeLabelsReport(),
            ],
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];
        
        return $arrayModels;
    }
}
