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

namespace api\modules\version1\controllers\gestionDocumental;

use api\components\HelperEncryptAes;
use api\components\HelperGenerateExcel;
use api\components\HelperLanguageSelector;
use api\components\HelperLoads;
use api\components\HelperLog;
use api\components\HelperQueryDb;
use api\components\HelperValidatePermits;
use api\components\HelperFiles;
use api\models\CgNumeroRadicado;
use api\models\CgRegionales;
use api\models\CgTrd;
use api\models\CgTrdMascaras;
use api\models\GdTrd;
use api\models\GdTrdDependencias;
use api\models\GdTrdDependenciasTmp;
use api\models\GdTrdSeries;
use api\models\GdTrdSeriesTmp;
use api\models\GdTrdSubseries;
use api\models\GdTrdSubseriesTmp;
use api\models\GdTrdTiposDocumentales;
use api\models\GdTrdTiposDocumentalesTmp;
use api\models\GdTrdTmp;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;
use yii\web\UploadedFile;

class TrdController extends Controller
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
                    'index' => ['GET'],
                    'create' => ['POST'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

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

            //Lista de Trd
            $dataList = [];
            $dataBase64Params = "";

            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if (is_array($request)) {
                foreach ($request['filterOperation'] as $field) {
                    foreach ($field as $key => $info) {

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
            $GdTrd = GdTrd::find();

                //->innerJoin('gdTrdDependencias', 'gdTrd.idGdTrdDependencia=gdTrdDependencias.idGdTrdDependencia')
                $GdTrd = HelperQueryDb::getQuery('innerJoin', $GdTrd, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdTrd' => 'idGdTrdDependencia']);
                // ->innerJoin('gdTrdSeries', 'gdTrd.idGdTrdSerie=gdTrdSeries.idGdtrdSerie')
                $GdTrd = HelperQueryDb::getQuery('innerJoin', $GdTrd, 'gdTrdSeries', ['gdTrdSeries' => 'idGdTrdSerie', 'gdTrd' => 'idGdTrdSerie']);
                // ->innerJoin('gdTrdSubseries', 'gdTrd.idGdTrdSubserie=gdTrdSubseries.idGdTrdSubserie')
                $GdTrd = HelperQueryDb::getQuery('innerJoin', $GdTrd, 'gdTrdSubseries', ['gdTrdSubseries' => 'idGdTrdSubserie', 'gdTrd' => 'idGdTrdSubserie']);
                // ->innerJoin('gdTrdTiposDocumentales', 'gdTrd.idGdTrdTipoDocumental=gdTrdTiposDocumentales.idGdTrdTipoDocumental')
                $GdTrd = HelperQueryDb::getQuery('innerJoin', $GdTrd, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'gdTrd' => 'idGdTrdTipoDocumental']);

            $GdTrd = $GdTrd->where(["estadoGdTrd" => Yii::$app->params['statusTodoText']['Activo']]); 

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {
                $GdTrd->andWhere(['IN', $field, $value]);
            }

            //Limite de la consulta
            $GdTrd->limit(Yii::$app->params['limitRecords']);
            $modelRelation = $GdTrd->all();

            foreach ($GdTrd as $GdTrdItem) {
                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($GdTrdItem->idGdTrd)),
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'idGdTrd' => $GdTrdItem->idGdTrd,
                    'gdTrdDependencia' => $GdTrdItem->gdTrdDependencia,
                    'gdTrdSerie' => $GdTrdItem->gdTrdSerie,
                    'gdTrdSubseries' => $GdTrdItem->gdTrdSubserie,
                    'gdTrdTipoDocumental' => $GdTrdItem->gdTrdTipoDocumental,
                    'rowSelect' => false,
                    'idInitialList' => 0,
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
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
     * Creates a new Tipos de documentales model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            //Se envia $response = ['idGdTrdDependencia' => 2, 'idGdTrdDependencia' =>  2,
            //'idGdTrdSerie' => 1, 'idGdTrdSubserie' => 5, 'idGdTrdTipoDocumental' => 1 ];
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

                unset($request['id']);
                $gdTrd = new GdTrd();
                $gdTrd->attributes = $request;

                if ($gdTrd->save()) {

                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla Gd Trd", //texto para almacenar en el evento
                        [],
                        [$gdTrd], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $gdTrd,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $gdTrd->getErrors(),
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
     * Busca la versión de la TRD temporal de la dependencias
     *
     */
    public function actionGetVersion($request)
    {

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            //Desencriptar
            if (!empty($request)) {
                //*** Inicio desencriptación Get ***//
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
                //*** Fin desencriptación POST ***//
            }

            $codigo = $request['id'];
            $dependenciaCorrecta = GdTrdDependencias::findOne(['codigoGdTrdDependencia' => $codigo]);

            // Recorre los codigos que llegan para que se vean reflejados en el log 
            $codigosDepeLog = '';
            if(is_array($codigo) ){
                foreach ($codigo as $value) {
                    $codigosDepeLog = $value.', '.strval($codigosDepeLog);
                }
            }else{
                $codigosDepeLog = $codigo;
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['ViewVersionTRD'].$codigosDepeLog. ' de la dependencia: '.$dependenciaCorrecta->nombreGdTrdDependencia, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            $gdTrdModel = GdTrd::find();
                // ->innerJoin('gdTrdDependencias', 'gdTrd.idGdTrdDependencia=gdTrdDependencias.idGdTrdDependencia')
                $gdTrdModel = HelperQueryDb::getQuery('innerJoin', $gdTrdModel, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdTrd' => 'idGdTrdDependencia']);
                $gdTrdModel = $gdTrdModel->where(['gdTrdDependencias.codigoGdTrdDependencia' => $codigo])
                ->orderBy(['creacionGdTrd' => SORT_DESC])
                ->all();

            if (empty($gdTrdModel)) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'trdDoesntExist')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $dataDep = [];
            $dataSeries = [];
            $dataSubseries = [];
            $dataTipDoc = [];
            $data = []; // Data de la subserie

            foreach ($gdTrdModel as $gdTrdModelItem) {
                //Trd Activa

                // Se agregan los atributos a variables para que se puedan utilizar de mejor manera

                if (isset($gdTrdModelItem->idGdTrdSerie) && isset($gdTrdModelItem->idGdTrdSubserie) && isset($gdTrdModelItem->idGdTrdTipoDocumental)) {

                    // UUID
                    $uuid = $gdTrdModelItem->identificadorUnicoGdTrd;

                    // Dependencias
                    $idDep = $gdTrdModelItem->idGdTrdDependencia;
                    $codDep = $gdTrdModelItem->gdTrdDependencia->codigoGdTrdDependencia;
                    $nomDep = $gdTrdModelItem->gdTrdDependencia->nombreGdTrdDependencia;
                    $obsDep = $gdTrdModelItem->gdTrdDependencia->observacionGdTrdDependencia;
                    $idCgRegional = $gdTrdModelItem->gdTrdDependencia->idCgRegional;
                    /** Se llama la regional */
                    $modelRegional = CgRegionales::find()
                        ->where(['idCgRegional' => $idCgRegional])
                        ->one();
                    $regional = $modelRegional->nombreCgRegional;

                    // Dependencia padre
                    $depPadre = $gdTrdModelItem->gdTrdDependencia->gdTrdDependenciaPadre;
                    $codPadre = !empty($depPadre)? $depPadre->codigoGdTrdDependencia : '';
                    $nomPadre = !empty($depPadre)? $depPadre->nombreGdTrdDependencia : '';

                    // Se consulta la configuración del cliente para la lectura de la TRD
                    // se valida que llegue soporte ya que si llega se debe mandar al frontend para que muestre los datos
                    // de lo contrario no se deben enviar.
                    $modelCgTrd = CgTrd::findOne(['estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']]);

                    if(!is_null($modelCgTrd->columnPSoporteCgTrd) || !is_null($modelCgTrd->columnESoporteCgTrd)){
                        $soporte = true;
                    }else{
                        $soporte = false;
                    } 

                    if(!is_null($modelCgTrd->columnNormaCgTrd)){
                        $norma = true;
                    }else{
                        $norma = false;
                    }

                    $formatter = \Yii::$app->formatter;
                    $fechaAct = $formatter->asDate($gdTrdModelItem->gdTrdDependencia->creacionGdTrdDependencia, 'long');
                    
                    $nomDepe = $codPadre . ' - ' . $nomPadre;
                    $oficinaProd = $codDep . ' - ' . $nomDep;
                    $validaDepe = array('idDepe' => $idDep, 'codDepe' => $codDep, 'nomDepe' => $nomDepe, 'obsDep' => $obsDep, 'regional' => $regional, 'oficinaProd' => $oficinaProd, 'fechaAct' => $fechaAct, 'version' => $gdTrdModelItem->versionGdTrd ,'soporte' => $soporte, 'norma' => $norma, 'uuid' => $uuid);
                    // Series
                    $idSerie = $gdTrdModelItem->idGdTrdSerie;
                    $codSerie = $gdTrdModelItem->gdTrdSerie->codigoGdTrdSerie;
                    $nomSerie = $gdTrdModelItem->gdTrdSerie->nombreGdTrdSerie;
                    $nomSerie = $codSerie . ' - ' . $nomSerie;
                    $validaSerie = array('idSerie' => $idSerie, 'codSerie' => $codSerie, 'nomSerie' => $nomSerie);
                    // Subseries
                    $idSubserie = $gdTrdModelItem->idGdTrdSubserie;
                    $codSubserie = $gdTrdModelItem->gdTrdSubserie->codigoGdTrdSubserie;
                    $nomSubserie = $gdTrdModelItem->gdTrdSubserie->nombreGdTrdSubserie;
                    $nomSubserie = $codSubserie . ' - ' . $nomSubserie;
                    $validaSubserie = array('idSubserie' => $idSubserie, 'codSubserie' => $codSubserie, 'nomSubserie' => $nomSubserie);
                    // Tipos documentales
                    $idTipDocu = $gdTrdModelItem->idGdTrdTipoDocumental;
                    $validaTipDoc = array('idTipDocu' => $idTipDocu, 'diasTipDoc' => null, 'nomTipDoc' => null);
                    if ($gdTrdModelItem->idGdTrdTipoDocumental != null) {
                        $diasTipDoc = $gdTrdModelItem->gdTrdTipoDocumental->diasTramiteTipoDocumental;
                        $nomTipDoc = $gdTrdModelItem->gdTrdTipoDocumental->nombreTipoDocumental;
                        $validaTipDoc = array('idTipDocu' => $idTipDocu, 'diasTipDoc' => $diasTipDoc, 'nomTipDoc' => $nomTipDoc);
                    }

                    // Validacion para crear la estrucctura para enviarla al frontend

                    // Dependencias
                    if (!in_array($validaDepe, $dataDep)) {
                        $dataDep[] = $validaDepe;
                    }

                    // Series
                    if (isset($dataSeries[$idDep])) {

                        if (!in_array($validaSerie, $dataSeries[$idDep])) {
                            $dataSeries[$idDep][] = $validaSerie;
                        }

                    } else {
                        $dataSeries[$idDep][] = $validaSerie;
                    }

                    // Subseries
                    if (isset($dataSubseries[$idSerie])) {

                        if (!in_array($validaSubserie, $dataSubseries[$idSerie])) {

                            if($gdTrdModelItem->gdTrdSubserie->pSoporteGdTrdSubserie == '10') {$pSoporte = 'X';} else{ $pSoporte = '';}
                            if($gdTrdModelItem->gdTrdSubserie->eSoporteGdTrdSubserie == '10') {$eSoporte = 'X';} else{ $eSoporte = '';}
                            if($gdTrdModelItem->gdTrdSubserie->oSoporteGdTrdSubserie == '10') {$oSoporte = 'X';} else{ $oSoporte = '';}

                            if(!is_null($gdTrdModelItem->gdTrdSubserie->normaGdTrdSubserie)){ $normaSubserie = $gdTrdModelItem->gdTrdSubserie->normaGdTrdSubserie; }
                            else{ $normaSubserie = 'No tiene norma registrada';}

                            $dataSubseries[$idSerie][] = $validaSubserie;
                            $data[$idSubserie]['tiempoGestion'] = $gdTrdModelItem->gdTrdSubserie->tiempoGestionGdTrdSubserie;
                            $data[$idSubserie]['tiempoCentral'] = $gdTrdModelItem->gdTrdSubserie->tiempoCentralGdTrdSubserie;
                            $data[$idSubserie]['pSoporte'] = $pSoporte;
                            $data[$idSubserie]['eSoporte'] = $eSoporte;
                            $data[$idSubserie]['oSoporte'] = $oSoporte;
                            $data[$idSubserie]['procedimientos'] = $gdTrdModelItem->gdTrdSubserie->procedimientoGdTrdSubserie;
                            $data[$idSubserie]['disposicionCT'] = $gdTrdModelItem->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie;
                            $data[$idSubserie]['disposicionE'] = $gdTrdModelItem->gdTrdSubserie->eDisposicionFinalGdTrdSubserie;
                            $data[$idSubserie]['disposicionS'] = $gdTrdModelItem->gdTrdSubserie->sDisposicionFinalGdTrdSubserie;
                            $data[$idSubserie]['disposicionM'] = $gdTrdModelItem->gdTrdSubserie->mDisposicionFinalGdTrdSubserie;
                            $data[$idSubserie]['norma'] = $normaSubserie;

                        }

                    } else {

                        if($gdTrdModelItem->gdTrdSubserie->pSoporteGdTrdSubserie == '10') {$pSoporte = 'X';} else{ $pSoporte = '';}
                        if($gdTrdModelItem->gdTrdSubserie->eSoporteGdTrdSubserie == '10') {$eSoporte = 'X';} else{ $eSoporte = '';}
                        if($gdTrdModelItem->gdTrdSubserie->oSoporteGdTrdSubserie == '10') {$oSoporte = 'X';} else{ $oSoporte = '';}

                        if(!is_null($gdTrdModelItem->gdTrdSubserie->normaGdTrdSubserie)){ $normaSubserie = $gdTrdModelItem->gdTrdSubserie->normaGdTrdSubserie; }
                        else{ $normaSubserie = 'No tiene norma registrada';}

                        $dataSubseries[$idSerie][] = $validaSubserie;
                        $data[$idSubserie]['tiempoGestion'] = $gdTrdModelItem->gdTrdSubserie->tiempoGestionGdTrdSubserie;
                        $data[$idSubserie]['tiempoCentral'] = $gdTrdModelItem->gdTrdSubserie->tiempoCentralGdTrdSubserie;
                        $data[$idSubserie]['pSoporte'] = $pSoporte;
                        $data[$idSubserie]['eSoporte'] = $eSoporte;
                        $data[$idSubserie]['oSoporte'] = $oSoporte;
                        $data[$idSubserie]['procedimientos'] = $gdTrdModelItem->gdTrdSubserie->procedimientoGdTrdSubserie;
                        $data[$idSubserie]['disposicionCT'] = $gdTrdModelItem->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionE'] = $gdTrdModelItem->gdTrdSubserie->eDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionS'] = $gdTrdModelItem->gdTrdSubserie->sDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionM'] = $gdTrdModelItem->gdTrdSubserie->mDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['norma'] = $normaSubserie;
                    }

                    // Tipos documentales
                    if (isset($dataTipDoc[$idSubserie])) {

                        if (!in_array($validaTipDoc, $dataTipDoc[$idSubserie])) {
                            $dataTipDoc[$idSubserie][] = $validaTipDoc;
                        }

                    } else {
                        $dataTipDoc[$idSubserie][] = $validaTipDoc;
                    }

                }
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'dataDep' => $dataDep,
                'dataSeries' => $dataSeries,
                'dataSubseries' => $dataSubseries,
                'dataTipDoc' => $dataTipDoc,
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

    public function actionUpdateName()
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
                $nombre = $request['nombre'];
                $modulo = $request['modulo'];

                switch ($modulo) {
                    case 'Dependencias':
                        $model = GdTrdDependencias::find()
                            ->where(['idGdTrdDependencia' => $id])
                            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();
                        $nombreModel = 'nombreGdTrdDependencia';
                        break;

                    case 'Series':
                        $model = GdTrdSeries::find()
                            ->where(['idGdTrdSerie' => $id])
                            ->andWhere(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();
                        $nombreModel = 'nombreGdTrdSerie';
                        break;

                    case 'Subseries':
                        $model = GdTrdSubseries::find()
                            ->where(['idGdTrdSubserie' => $id])
                            ->andWhere(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();
                        $nombreModel = 'nombreGdTrdSubserie';
                        break;

                    case 'TiposDocumentales':
                        $model = GdTrdTiposDocumentales::find()
                            ->where(['idGdTrdTipoDocumental' => $id])
                            ->andWhere(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();
                        $nombreModel = 'nombreTipoDocumental';
                        break;
                    // Modifica los dias del tipo documental
                    case 'TiposDocumentalesDia':
                        $model = GdTrdTiposDocumentales::find()
                            ->where(['idGdTrdTipoDocumental' => $id])
                            ->andWhere(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();
                        $nombreModel = 'diasTramiteTipoDocumental';
                        break;
                }

                // Valores anteriores del modelo
                // $modelOld = [];
                // foreach ($model as $key => $value) {
                //    $modelOld[$key] = $value;
                // }

                $modelOld = '';
                if(!is_null($model)){
                    $modelOld = self::dataLog($model);                   
                }

                if (empty($model)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'inactiva' . $modulo)],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Actualiza el nombre del campo de la TRD
                $model[$nombreModel] = $nombre;

                if ($model->save()) {

                    $dataNew = self::dataLog($model);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla " . $modulo, //texto para almacenar en el evento
                        $modelOld,
                        $dataNew, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successUpdate'),
                    'data' => $model,
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
                    case 'estadoGdTrdDependencia':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$model->estadoGdTrdDependencia].', ';
                    break;  
                    case 'estadoGdTrdSerie':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$model->estadoGdTrdSerie].', ';
                    break;
                    case 'estadoGdTrdSubserie':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$model->estadoGdTrdSubserie].', ';
                    break;                
                    case 'estadoTipoDocumental':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$model->estadoTipoDocumental].', ';
                    break;                
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }

            return $dataLog;
        }
    } 


    /**
     * Busca la Version TRD activa de las tablas Originales de la dependencias
     *
     */
    public function actionActiveVersion($request)
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

            $id = $request['data'];

            $modelGdTrd = GdTrd::find()
            //->all();
            ->where(['idGdTrdDependencia' => $id])
            ->orderBy(['creacionGdTrd' => SORT_DESC])
            ->all();

            // Recorre los codigos que llegan para que se vean reflejados en el log 
            $idTrdActiva = '';
            if(is_array($id) ){
                foreach ($id as $value) {
                    $idTrdActiva = $value.', '.strval($idTrdActiva);
                }
            }else{
                $idTrdActiva = $id;
            }

            if (empty($modelGdTrd)) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'trdDoesntExist')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $dependencia = $modelGdTrd[0]->gdTrdDependencia;

            /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['ViewActiveTRD'].$dependencia->codigoGdTrdDependencia. ' de la dependencia: '.$dependencia->nombreGdTrdDependencia /* $idTrdActiva */, //texto para almacenar en el evento
                [], //DataOlds
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            if (!$dependencia->estadoGdTrdDependencia == Yii::$app->params['statusTodoText']['Activo']) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'depInactived')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $dataDep = [];
            $dataSeries = [];
            $dataSubseries = [];
            $dataTipDoc = [];
            $data = []; // Data de la subserie

            foreach ($modelGdTrd as $model) {

                // Se agregan los atributos a variables para que se puedan utilizar de mejor manera

                // Dependencias
                $idDep = $model->idGdTrdDependencia;
                $codDep = $model->gdTrdDependencia->codigoGdTrdDependencia;
                $nomDep = $model->gdTrdDependencia->nombreGdTrdDependencia;
                $idCgRegional = $model->gdTrdDependencia->idCgRegional;
                /** Se llama la regional */
                $modelRegional = CgRegionales::find()
                    ->where(['idCgRegional' => $idCgRegional])
                    ->one();
                $regional = $modelRegional->nombreCgRegional;

                // Dependencia padre
                $depPadre = $model->gdTrdDependencia->gdTrdDependenciaPadre;
                $codPadre = !empty($depPadre)? $depPadre->codigoGdTrdDependencia : '';
                $nomPadre = !empty($depPadre)? $depPadre->nombreGdTrdDependencia : '';

                // Se consulta la configuración del cliente para la lectura de la TRD
                // se valida que llegue soporte ya que si llega se debe mandar al frontend para que muestre los datos
                // de lo contrario no se deben enviar.
                $modelCgTrd = CgTrd::findOne(['estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']]);

                if(!is_null($modelCgTrd->columnPSoporteCgTrd) || !is_null($modelCgTrd->columnESoporteCgTrd)){
                    $soporte = true;
                }else{
                    $soporte = false;
                } 

                if(!is_null($modelCgTrd->columnNormaCgTrd)){
                    $norma = true;
                }else{
                    $norma = false;
                }

                $formatter = \Yii::$app->formatter;
                $fechaAct = $formatter->asDate($model->gdTrdDependencia->creacionGdTrdDependencia, 'long');

                $nomDepe = $codPadre . ' - ' . $nomPadre;                
                $oficinaProd = $codDep . ' - ' . $nomDep;
                $validaDepe = array('idDepe' => $idDep, 'codDepe' => $codDep, 'nomDepe' => $nomDepe, 'regional' => $regional, 'oficinaProd' => $oficinaProd, 'fechaAct' => $fechaAct, 'version' => $model->versionGdTrd, 'soporte' => $soporte, 'norma' => $norma);
                // Series
                $idSerie = $model->idGdTrdSerie;
                $codSerie = $model->gdTrdSerie->codigoGdTrdSerie;
                $nomSerie = $model->gdTrdSerie->nombreGdTrdSerie;
                // $nomSerie = $codSerie.' - '.$nomSerie;
                $validaSerie = array('idSerie' => $idSerie, 'codSerie' => $codSerie, 'nomSerie' => $nomSerie);
                // Subseries
                $idSubserie = $model->idGdTrdSubserie;
                $codSubserie = $model->gdTrdSubserie->codigoGdTrdSubserie;
                $nomSubserie = $model->gdTrdSubserie->nombreGdTrdSubserie;
                // $nomSubserie = $codSubserie.' - '.$nomSubserie;
                $validaSubserie = array('idSubserie' => $idSubserie, 'codSubserie' => $codSubserie, 'nomSubserie' => $nomSubserie);
                // Tipos documentales
                $idTipDocu = $model->idGdTrdTipoDocumental;
                $validaTipDoc = array('idTipDocu' => $idTipDocu, 'diasTipDoc' => '', 'nomTipDoc' => '');
                if ($model->idGdTrdTipoDocumental != null) {
                    $diasTipDoc = $model->gdTrdTipoDocumental->diasTramiteTipoDocumental;
                    $nomTipDoc = $model->gdTrdTipoDocumental->nombreTipoDocumental;
                    $validaTipDoc = array('idTipDocu' => $idTipDocu, 'diasTipDoc' => $diasTipDoc, 'nomTipDoc' => $nomTipDoc);
                }

                // Validacion para crear la estrucctura para enviarla al frontend

                // Dependencias
                if (!in_array($validaDepe, $dataDep)) {
                    $dataDep[] = $validaDepe;
                }

                // Series
                if (isset($dataSeries[$idDep])) {

                    if (!in_array($validaSerie, $dataSeries[$idDep])) {
                        $dataSeries[$idDep][] = $validaSerie;
                    }

                } else {
                    $dataSeries[$idDep][] = $validaSerie;
                }

                // Subseries
                if (isset($dataSubseries[$idSerie])) {

                    if (!in_array($validaSubserie, $dataSubseries[$idSerie])) {

                        if($model->gdTrdSubserie->pSoporteGdTrdSubserie == '10') {$pSoporte = 'X';} else{ $pSoporte = '';}
                        if($model->gdTrdSubserie->eSoporteGdTrdSubserie == '10') {$eSoporte = 'X';} else{ $eSoporte = '';}
                        if($model->gdTrdSubserie->oSoporteGdTrdSubserie == '10') {$oSoporte = 'X';} else{ $oSoporte = '';}

                        if(!is_null($model->gdTrdSubserie->normaGdTrdSubserie)){ $normaSubserie = $model->gdTrdSubserie->normaGdTrdSubserie; }
                        else{ $normaSubserie = 'No tiene norma registrada';}

                        $dataSubseries[$idSerie][] = $validaSubserie;
                        // Adjunta la data de la Subserie
                        $data[$idSubserie]['tiempoGestion'] = $model->gdTrdSubserie->tiempoGestionGdTrdSubserie;
                        $data[$idSubserie]['tiempoCentral'] = $model->gdTrdSubserie->tiempoCentralGdTrdSubserie;
                        $data[$idSubserie]['pSoporte'] = $pSoporte;
                        $data[$idSubserie]['eSoporte'] = $eSoporte;
                        $data[$idSubserie]['oSoporte'] = $oSoporte;
                        $data[$idSubserie]['procedimientos'] = $model->gdTrdSubserie->procedimientoGdTrdSubserie;
                        $data[$idSubserie]['disposicionCT'] = $model->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionE'] = $model->gdTrdSubserie->eDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionS'] = $model->gdTrdSubserie->sDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['disposicionM'] = $model->gdTrdSubserie->mDisposicionFinalGdTrdSubserie;
                        $data[$idSubserie]['norma'] = $normaSubserie;

                    }

                } else {

                    if($model->gdTrdSubserie->pSoporteGdTrdSubserie == '10') {$pSoporte = 'X';} else{ $pSoporte = '';}
                    if($model->gdTrdSubserie->eSoporteGdTrdSubserie == '10') {$eSoporte = 'X';} else{ $eSoporte = '';}
                    if($model->gdTrdSubserie->oSoporteGdTrdSubserie == '10') {$oSoporte = 'X';} else{ $oSoporte = '';}

                    if(!is_null($model->gdTrdSubserie->normaGdTrdSubserie)){ $normaSubserie = $model->gdTrdSubserie->normaGdTrdSubserie; }
                    else{ $normaSubserie = 'No tiene norma registrada';}

                    $dataSubseries[$idSerie][] = $validaSubserie;
                    // Adjunta la data de la Subserie
                    $data[$idSubserie]['tiempoGestion'] = $model->gdTrdSubserie->tiempoGestionGdTrdSubserie;
                    $data[$idSubserie]['tiempoCentral'] = $model->gdTrdSubserie->tiempoCentralGdTrdSubserie;
                    $data[$idSubserie]['pSoporte'] = $pSoporte;
                    $data[$idSubserie]['eSoporte'] = $eSoporte;
                    $data[$idSubserie]['oSoporte'] = $oSoporte;
                    $data[$idSubserie]['procedimientos'] = $model->gdTrdSubserie->procedimientoGdTrdSubserie;
                    $data[$idSubserie]['disposicionCT'] = $model->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie;
                    $data[$idSubserie]['disposicionE'] = $model->gdTrdSubserie->eDisposicionFinalGdTrdSubserie;
                    $data[$idSubserie]['disposicionS'] = $model->gdTrdSubserie->sDisposicionFinalGdTrdSubserie;
                    $data[$idSubserie]['disposicionM'] = $model->gdTrdSubserie->mDisposicionFinalGdTrdSubserie;
                    $data[$idSubserie]['norma'] = $normaSubserie;
                }

                // Tipos documentales
                if (isset($dataTipDoc[$idSubserie])) {

                    if (!in_array($validaTipDoc, $dataTipDoc[$idSubserie])) {
                        $dataTipDoc[$idSubserie][] = $validaTipDoc;
                    }

                } else {
                    $dataTipDoc[$idSubserie][] = $validaTipDoc;
                }

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'dataDep' => $dataDep,
                'dataSeries' => $dataSeries,
                'dataSubseries' => $dataSubseries,
                'dataTipDoc' => $dataTipDoc,
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

    public function actionDownloadTrdFile()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');
            if (!empty($jsonSend)) {

                /*** Inicio desencriptación POST ***/
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

                $sheets = [];

                # Nombre del archivo
                $fileName = 'formato_trd_' . Yii::$app->user->identity->id . '.xlsx';                

                # Validación para que descargue todas las TRDs activas, 
                # de lo contrario descarga las dependencias seleccionadas
                if( $request['id'][0] == "0" ){

                    $modelGdTrd = GdTrd::find()
                        ->where(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                        ->orderBy([
                            'idGdTrdDependencia' => SORT_ASC,
                            'idGdTrdSerie' => SORT_ASC,
                            'idGdTrdSubserie' => SORT_ASC,
                            'idGdTrd' => SORT_ASC
                        ])
                    ->all();
                    
                    foreach($modelGdTrd as $dataTrd){
                        if(!in_array($dataTrd->idGdTrdDependencia, $request['id']))
                            $request['id'][] = $dataTrd->idGdTrdDependencia; 
                    }                   
                } 
                
                # Se itera los ids de la dependencia
                foreach ($request['id'] as $id) {     

                    # Construcción del excel
                    $data = HelperLoads::generateDownloadTrd($id);

                    if ($data['status'] == 'OK') {
                        $sheets[] = [
                            'cells' => $data['cells'],
                            'listSelect' => [],
                            'mergeCells' => $data['mergeCells'],
                            'titles' => [],
                            'headers' => [],
                            'borders' => $data['borders'],
                            'wrappedCells' => $data['wrappedCells'],
                            'boldCells' => $data['boldCells'],
                            'centerCells' => $data['centerCells'],
                            'sheetName' => $data['sheetName'],
                        ];
                        //return HelperEncryptAes::encrypt($response, true);
                    }
                }

                if (count($sheets) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'trdDoesntExist')],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $generacion = HelperGenerateExcel::generarExcelSheet('trd_formats', $fileName, $sheets, true);

                $rutaDocumento = $generacion['rutaDocumento'];

                /* Enviar archivo en base 64 como respuesta de la petición **/
                if ($generacion['status'] && file_exists($rutaDocumento)) {

                    # Consulta para obtener el nombre de la dependencia descargada
                    $modelDepend = GdTrdDependencias::findAll(['idGdTrdDependencia' => $request['id']]);

                    if(!is_null($modelDepend)){

                        $namesDepend = [];
                        $nameDepend = '';
                        $idsDependence = [];
                        $idDependence = '';

                        foreach($modelDepend as $info){
                            $namesDepend[] = $info['nombreGdTrdDependencia'];
                            $idsDependence[] = $info->idGdTrdDependencia;
                        }

                        // Variables para el log
                        $idDependence = implode(", ", $idsDependence);
                        $nameDepend = implode(', ', $namesDepend);
                    }

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['DownloadFile'].' de TRD id: '.$idDependence. ' de la dependencia: '.$nameDepend, //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataFile = base64_encode(file_get_contents($rutaDocumento));
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data' => $fileName,
                        'status' => 200,
                    ];
                    $return = HelperEncryptAes::encrypt($response, true);

                    // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                    $return['datafile'] = $dataFile;

                    return $return;

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'fileDonotDownloaded')],
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
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Permite cargar el archivo .xlsx de las TRD subido por el cliente, se establece el directorio
     * donde se va a guardar el archivo.
     * @return UploadedFile el archivo cargado
     */
    public function actionLoadTrdFile()
    {
        //if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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
            if ($fileUpload->extension != 'xlsx') {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'fileDonesNotCorrectFormat')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            /* Fin validar tipo de archivo */

            $rutaOk = true; // ruta base de la carpeta existente
            //Ruta de ubicacion de la carpeta donde se almacena el excel
            $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomCarpetaTrd'] . '/';

            // Verificar que la carpeta exista y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {

                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            /*** Validar creación de la carpeta***/
            if ($rutaOk == false) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'notPermissionsDirectory')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            /*** Fin Validar creación de la carpeta***/

            $id = Yii::$app->user->identity->id;

            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar
            $nomArchivo = "tmp_carga_trd_" . $id . "." . $fileUpload->extension;

            $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo);

            /*** Validar si el archivo fue subido ***/
            if ($uploadExecute) {

                /*** log Auditoria ***/
                if (!Yii::$app->user->isGuest) {

                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['FileUpload'], // texto para almacenar en el evento
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );
                }
                /***  Fin log Auditoria   ***/

                return SELF::fileValidate($nomArchivo); //LLamado a la funcion de validacion de archivo

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'couldNotUploadFile')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => Yii::t('app', 'enterFile')],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /* Función que permite la validacion del archivo cargado */
    public function fileValidate($nomArchivo)
    {
        $rutaOk = true;
        $dataListErr = [];

        //ruta del archivo de excel
        $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeMassive'] . "/" . Yii::$app->params['nomCarpetaTrd'] . '/' . $nomArchivo;

        /** Validar si el archivo existe */
        if (file_exists($pathUploadFile)) {

            # Consulta del model de configuracion de la TRD que esta activa
            $modelCgTrd = CgTrd::find()->where(['estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']])->one();

            if ($modelCgTrd == null) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'unactiveTRDmaskConfig')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            # Consulta el tipo de mascara escogida por el cliente para saber como empezar a
            # leer la información del archivo que se carga
            $modelMascara = CgTrdMascaras::find()->where(['estadoCgTrdMascara' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['idCgTrdMascara' => $modelCgTrd->idMascaraCgTrd])
                ->one();

            $mascara = $modelMascara->nombreCgTrdMascara;

            // Se valida si la mascara indica que se debe leer columna por columna
            // si es asi se ejecuta otra acción para dicho proceso
            if ($mascara == Yii::$app->params['maskDefaul']) {
                return SELF::readFileNormal($modelMascara, $pathUploadFile, $modelCgTrd);
            } else {
                return SELF::readFileMask($modelMascara, $pathUploadFile, $modelCgTrd);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => Yii::t('app', 'fileNotProcessed')],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        /** Fin Validar si el archivo existe ***/

    }

    /**
     * Función que se encarga de leer el excel mediante una sola columna donde se encuentran los códigos correspondientes
     * se hace un explode para identificar como es la estrutura del código de trd (dependencia, serie, subserie)
     */
    public function readFileMask($modelMascara, $pathUploadFile, $modelCgTrd)
    {
        /****** Validación de archivo ******/
        $documento = IOFactory::load($pathUploadFile);

        //Se obtiene el nombre de cada hoja
        $sheetNames = $documento->getSheetNames();

        # Recordar que un documento puede tener múltiples hojas
        # obtener conteo e iterar
        $totalDeHojas = $documento->getSheetCount();

        $saveDataValid = true;
        $errosLoadTrd = [];

        # Iterar hoja por hoja
        $hojasNoProcesadas = [];
        for ($indiceHoja = 0; $indiceHoja < $totalDeHojas; $indiceHoja++) {

            /** Inicio de la transaccion */
            $transaction = Yii::$app->db->beginTransaction();

            # Obtener hoja en el índice que vaya del ciclo
            $hojaActual = $documento->getSheet($indiceHoja);

            # Permite manejar los datos como un array y no como un objeto
            $highestRow = $hojaActual->getHighestRow(); //Cantidad de filas de la hoja
            $highestColumn = Yii::$app->params['highestColumn']; // Columna hasta la que toca leer

            //$hojaActualArray = $hojaActual->toArray();
            $hojaActualArray = $hojaActual->rangeToArray("A1:$highestColumn$highestRow");

            //Calcula UUID
            $uniqid = uniqid();

            # Fila y columna de la ubicacion del titulo de la dependencia
            $filaNombreDepend = (int) substr($modelCgTrd->cellTituloDependCgTrd, 1);
            $colNombreDepend = $this->numberByLetter(substr($modelCgTrd->cellTituloDependCgTrd, 0, 1));
            $nombreDependencia = $hojaActualArray[$filaNombreDepend - 1][$colNombreDepend];

            # Validación del nombre de la dependencia
            if (!$nombreDependencia) {
                $hojasNoProcesadas[] = $indiceHoja;
                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdEncabezado') . '';

                $saveDataValid = false;
            } else {
                // Si el nombre de una dependencia viene con el caracter (:) se realiza
                // un explode del campo para sacar el dato solamente del nombre
                $buscaCaracter = strpos($nombreDependencia, ':');
                $longitudDependencia = strlen(trim($nombreDependencia));
                
                if ($buscaCaracter !== false) {
                    $explodedependencia = explode(":", $nombreDependencia);
                    $nombreDependencia = trim($explodedependencia[1]);
                    $longitudDependencia = strlen($nombreDependencia);

                    // Si luego de hacer explode del nombre de la dependencia el campo esta vacio se debe enviar error formato
                    if($nombreDependencia == '' || is_null($nombreDependencia)){
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdEncabezado') . '';

                        $saveDataValid = false;
                    }
                }

                /** SE AGREGO HOY */
                // Se valida si el nombre de la dependencia supera la longitud permitida
                if($longitudDependencia > Yii::$app->params['longitudColumnas']['nombreGdTrdDependencia']){
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLongitud') . '';

                    $saveDataValid = false;
                }
                /** FIN SE AGREGO HOY */
            }
            
            # Fila y columna de la ubicacion de la unidad administrativa
            $filaNombreDependPadre = (int) substr($modelCgTrd->cellDependenciaPadreCgTrd, 1);
            $colNombreDependPadre = $this->numberByLetter(substr($modelCgTrd->cellDependenciaPadreCgTrd, 0, 1));
            $nombreDependenciaPadre = $hojaActualArray[$filaNombreDependPadre - 1][$colNombreDependPadre];

            if (!$nombreDependenciaPadre) {
                $hojasNoProcesadas[] = $indiceHoja;
                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDepePadre') . '';

                $saveDataValid = false;
            } else {
                // Si el nombre de una dependencia viene con el caracter (:) se realiza
                // un explode del campo para sacar el dato solamente del nombre
                $buscaCaracter = strpos($nombreDependenciaPadre, ':');
                $longitudDependenciaPadre = strlen(trim($nombreDependenciaPadre));

                if ($buscaCaracter !== false) {
                    $explodedependenciaPadre = explode(":", $nombreDependenciaPadre);
                    $nombreDependenciaPadre = trim($explodedependenciaPadre[1]);
                    $longitudDependenciaPadre = strlen($nombreDependenciaPadre);

                    // Si luego de hacer explode del nombre de la unidad administrativa, el campo esta vacio se debe enviar error formato
                    if($nombreDependenciaPadre == '' || is_null($nombreDependenciaPadre)){
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDepePadre') . '';

                        $saveDataValid = false;
                    }
                }

                /** SE AGREGO HOY */
                // Se valida si el nombre de la dependencia supera la longitud permitida
                if($longitudDependenciaPadre > Yii::$app->params['longitudColumnas']['nombreGdTrdDependencia']){
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLongitud') . '';

                    $saveDataValid = false;
                }
                /** FIN SE AGREGO HOY */
            }

            # Fila y columna de la ubicacion del código dependencia
            $filaDependencia = (int) substr($modelCgTrd->cellDependenciaCgTrd, 1);
            $dependencia = $hojaActual->getCell($modelCgTrd->cellDependenciaCgTrd)->getValue();

            # Fila y columna de la ubicacion del nombre de la regional
            // Se valida si en la columna de regional hay algun valor si no lo hay se toma
            // la regional que por defecto se configura en params
            if ($modelCgTrd->cellRegionalCgTrd != '') {
                $filaRegional = (int) substr($modelCgTrd->cellRegionalCgTrd, 1);
                $colRegional = $this->numberByLetter(substr($modelCgTrd->cellRegionalCgTrd, 0, 1));
                $nombreRegional = $hojaActualArray[$filaRegional - 1][$colRegional];

                if ($nombreRegional == '') {
                    // Sumar error de regional no procesada
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdRegional') . '';

                    $saveDataValid = false;
                }
                // Si esta configurada la regional pero la misma viene en con un (:)
                else{

                    // un explode del campo para sacar el dato solamente del nombre
                    $buscaCaracter = strpos($nombreRegional, ':');

                    if ($buscaCaracter !== false) {
                        $explodedependenciaRegional = explode(":", $nombreRegional);
                        $nombreRegional = trim($explodedependenciaRegional[1]);

                        // Si luego de hacer explode del nombre de la dependencia el campo esta vacio se debe enviar error formato
                        if($nombreRegional == '' || is_null($nombreRegional)){
                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdRegional') . '';

                            $saveDataValid = false;
                        }
                    }
                }

            } else {
                $nombreRegional = Yii::$app->params['regionalDefaul'];
            }

            # Fila y columna donde inicia los datos del formato
            $filaDatos = (int) substr($modelCgTrd->cellDatosCgTrd, 1);
            $colDatos = $this->numberByLetter(substr($modelCgTrd->cellDatosCgTrd, 0, 1));
            $dato = $hojaActualArray[$filaDatos - 1][$colDatos];

            /** Se valida que la regional exista en la D.B */
            $modelRegional = CgRegionales::find()
                ->where(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['nombreCgRegional' => $nombreRegional])
                ->one();

            if (is_null($modelRegional)) {
                // Sumar error de regional no procesada
                $hojasNoProcesadas[] = $indiceHoja;
                $errosLoadTrd[$indiceHoja][] = Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdNombreReg');

                $saveDataValid = false;
            }
            /** Fin Se valida que la regional exista en la D.B */

            # Se consulta la longitud de la dependencia.
            $modelLengthDependence = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            
            $length = '';
            if(!is_null($modelLengthDependence)){
                $length = $modelLengthDependence->longitudDependenciaCgNumeroRadicado;
            } 
            
            // Se valida que exista numero de dependencia y la regional en cada iteración
            if (strlen($dependencia) == $length && $dependencia && isset($modelRegional)) {

                //Primero se valida que la dependencia no exista en la D.B
                $modelDependencia = GdTrdDependencias::find()
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['codigoGdTrdDependencia' => $dependencia])
                    ->one();

                //Se realiza consulta del nombre de la dependencia Unidad Administrativa
                //si existe el nombre se extrae el código y se guarda en la dependenciaPadre
                $modelDependenciaPadre = GdTrdDependencias::find()
                    ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['nombreGdTrdDependencia' => trim($nombreDependenciaPadre)])
                    ->orderBy(['idGdTrdDependencia' => SORT_DESC])
                    ->one();

                // En el código de la dependencia padre toca guardar el id de la dependencia unidad administrativa
                if (!is_null($modelDependenciaPadre)) {
                    $codigoDepePadre = $modelDependenciaPadre->idGdTrdDependencia;
                } else {
                    $codigoDepePadre = 0;
                }

                # Fila a leer
                $filaLeer = $filaDatos - 1;

                $mascara = $modelMascara->idCgTrdMascara;
                $separador = $modelMascara->separadorCgTrdMascara;
                $nombreSeparador = $modelMascara->nombreCgTrdMascara;
                $temporal = 'no'; // Estado que indica si la trd, que se esta cargando es temporal.
                $mensajeTrdTemporal = '';

                /*** Si no existe la dependencia se continua validando el formato ***/
                if (!$modelDependencia && !is_null($modelRegional)) {

                    if (!is_null($modelRegional)) {

                        // Se realiza un substr para asi poder sacar el código de la dependencia ya que llega en una sola columna
                        $dato = trim(substr($dato, 0, $length));

                        # Se valida inicialmente si la dependencia corresponde a la misma del primer dato del excel
                        if ($dato == $dependencia) {

                            // Se obtiene toda la informacion
                            $datos = array_slice($hojaActualArray, $filaLeer);

                            # Insertamos la dependencia acorde a los datos leidos del excel
                            $gdTrdDepend = new GdTrdDependencias();
                            $gdTrdDepend->codigoGdTrdDependencia = (string) $dependencia;
                            $gdTrdDepend->nombreGdTrdDependencia = trim($nombreDependencia);
                            $gdTrdDepend->codigoGdTrdDepePadre = (int) $codigoDepePadre;
                            $gdTrdDepend->idCgRegional = $modelRegional->idCgRegional;
                            $gdTrdDepend->save();

                            //Luego se verifica que el id de dependencia no exista en la D.B
                            $modelGdTrd = GdTrd::find()
                                ->where(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                                ->andWhere(['idGdTrdDependencia' => $gdTrdDepend->idGdTrdDependencia])
                                ->one();

                            $response = [];
                            $datosBloque = [];
                            $resultado = [];

                            $datosBloque['dependencia'] = $gdTrdDepend->idGdTrdDependencia;

                            foreach ($datos as $i => $row) {

                                /**
                                 * Se valida si en la configuración activa indica cual es el caracter de
                                 * separación para obtener la información de la dependencia, serie y subserie
                                 * si el separador es vacio y diferente a "Columnas separadas" se procede a
                                 * hacer un substring del códígo con la longitud de la dependencia.
                                 **/
                                $nombreSerie = trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
                                $codigoTrd = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
                                
                                // Se cuenta la cantidad de caracteres que hay en el código de la TRD y se le resta
                                // la longitud configurada para el cliente y asi se sabe cual es el código de la serie
                                $posicion = (int) strlen($codigoTrd) - (int) $length;
                                $codigoDependencia = trim(substr($codigoTrd, 0, $length));
                                $codigoSerie = trim(substr($codigoTrd, $length, $posicion));
                                $codigoSubserie = '';

                                if ($separador == '' && $mascara != Yii::$app->params['ConfiguracionMascara']) {
                                } else {

                                    ######### TRATAMIENTO DE LOS DATOS DE LA SERIE, SUBSERIE Y DEPENDNCIA UNA SOLA COLUMNA
                                    if (!is_null($codigoTrd) && $codigoTrd != '') {

                                        // Se realiza una busqueda e identificación del caracterer de separación configurado
                                        // contra lo que llegue en la columna que se leea del excel ya que el código de la TRD
                                        // viene en una sola columna.

                                        # Caracterer de separación en la configuración
                                        $buscaMascaraSeparacion = strpos($nombreSeparador, $separador);
                                        # Caracteres de separación en la columna leida
                                        $buscaCaracterSeparacion = strpos($codigoTrd, $separador);

                                        // Si en la busqueda de caracter de separación en la configuración
                                        // retorna true se sabe que se tiene que hacer tratamiento a la columna.
                                        if ($buscaCaracterSeparacion === false && $buscaMascaraSeparacion === false) {} else {

                                            // Se hace explode al valor configurado para compararlo con el explode del Codigo TRD
                                            $explodedeMascara = explode($separador, $nombreSeparador);
                                            // Se hace explode de la columna que se esta leyendo para identificar el código serie y dependencia
                                            $explodedeCodigoTrd = explode($separador, $codigoTrd);

                                            /**
                                             * Se cuenta las posiciones que salieron al ejecutar el explode segun el separador
                                             * configurado por el cliente para saber el código de la serie, subserie. Por lo menos
                                             * se debe encontrar una posición.
                                             **/
                                            if (count($explodedeCodigoTrd) > 0 && count($explodedeMascara) > 0) {

                                                // Si la separación del cóidgo es igual a dos solo se manejan dos posiciones
                                                // quiere decir que se esta manejando una fila de la serie
                                                // dd ss
                                                if (count($explodedeCodigoTrd) == 2) {
                                                    
                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia                                                    
                                                    $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }
                                                // Si la separación es igual a 3 quiere decir que se esta leyendo la fila
                                                // de una subserie dd ss sb 
                                                elseif(count($explodedeCodigoTrd) == 3){
                                                    // Como se sabe que si llegan 3 posiciones en la fila ya es una subserie
                                                    // se procede a convertir en tipo titulo.
                                                    $nombreSerie = ucfirst(strtolower($nombreSerie));

                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia
                                                    if (isset($explodedeCodigoTrd[1])) {
                                                        $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie
                                                        $codigoSubserie = trim($explodedeCodigoTrd[2]); # Código de subserie
                                                    }

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }                                                
                                                else {
                                                    
                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia
                                                    if (isset($explodedeCodigoTrd[1])) {
                                                        $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie
                                                        $codigoSubserie = trim($explodedeCodigoTrd[2]); # Código de subserie
                                                    }

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }
                                            }
                                            // Si la columna leida no tiene la misma cantidad de campos separados quiere decir
                                            // error de formato
                                            else {
                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdNoCumple') . ' (' . $nombreSeparador . ').';

                                                $saveDataValid = false;
                                            }
                                        }
                                    }
                                }

                                # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza 
                                // la TRD de la hoja y se frena el proceso.
                                $validateRow = $this->validateRow($row);

                                //Valida que la fila no venga vacia
                                if ($validateRow === true) {

                                    // Apartir de aqui realiza las correspondientes validaciones

                                    ##################################################
                                    ########## PROCESO DE SERIES DOCUMENTALES ########
                                    ##################################################

                                    // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                                    if ((is_null($codigoTrd) && $codigoTrd == '') && (strtoupper($nombreSerie) == $nombreSerie) || ($nombreSerie == '')) {

                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                                        $saveDataValid = false;
        
                                    } else {

                                        ##### Lectura de series #####
                                        if (strtoupper($nombreSerie) == $nombreSerie) {

                                            // Valida si la serie existe tanto por nombre como por código
                                            $serie = $this->validateSerieCodigo($codigoSerie, $nombreSerie);

                                            if ($serie) {
                                                # Si el código de la serie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($serie->nombreGdTrdSerie != $nombreSerie && !$modelGdTrd) {
        
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)-1) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $serie->nombreGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
        
                                                }
                                            }

                                            // Valida si la serie existe tanto por nombre genera error
                                            $serieNombre = $this->validateSerieNombre($codigoSerie, $nombreSerie);

                                            if ($serieNombre) {
                                                # Si el nombre de la serie existe pero con otro codigo y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($serieNombre->codigoGdTrdSerie != $codigoSerie && !$modelGdTrd) {
        
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') .' '.Yii::t('app', 'dataListErrTrdRegistroOtro') . $serieNombre->codigoGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
        
                                                }
                                            }
                                        }
                                    }

                                    ##################################################
                                    ######## PROCESO DE SUBSERIES DOCUMENTALES #######
                                    ##################################################
                                    ### Valida datos de la subserie, si en la configuración se agrego una norma
                                    if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                                        $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                                    }

                                    $codigoSubserie = $codigoSubserie;
                                    $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                                    $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                                    $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                                    $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                                    $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                                    $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
                                    $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                                    if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                                        $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                                        $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                                        // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                                        if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                                            
                                            if((!is_null($codigoTrd) && $codigoTrd != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                $saveDataValid = false;
                                            }
                                        }
                                    }

                                    if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                                        $disposicion = null;
                                    } else {
                                        $disposicion = 10;
                                    }

                                    /** SE AGREGO HOY */
                                    // Si alguno de los datos de estas columnas son vacios genera error de formato SUBSERIE
                                    if ((!is_null($codigoTrd) && $codigoTrd != '') && ($nombreSerie == "" || is_null($nombreSerie) ) ) {
                                        
                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;
                                    }
                                    /** FIN SE AGREGO HOY **/

                                    // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                                    // Si alguno de los datos de estas columnas son vacios genera error de formato
                                    if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {

                                        // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                                        // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                                        // si $codigoSubserie != '00' se descarta las series entre a esta validación
                                        if ((!is_null($codigoTrd) && $codigoTrd != '') && ($codigoSubserie == '00' || $codigoSubserie == '000')  && (!is_null($codigoSubserie) && $codigoSubserie != '')) {
                                            
                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;

                                        }

                                    }
                                    // Si llega el código de la dependencia y tambien el código de la subserie pero no la serie y tampoco el nombre error de formato
                                    elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSubserie) && $codigoSubserie != '') && (is_null($codigoSerie) && $codigoSerie == '') /*&& (is_null($nombreSerie) && $nombreSerie == '')*/) {

                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;
                                    } 
                                    // Si llega el código de la dependencia y tambien el cóidgo de la serie pero no la subserie  y tampoco el nombre error de formato
                                    elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSerie) && $codigoSerie != '') && (is_null($codigoSubserie) && $codigoSubserie == '') && ($codigoSubserie != '00' || $codigoSubserie != '000') /* && (is_null($nombreSerie) && $nombreSerie == '')*/) {

                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;

                                    }
                                    else {

                                        // Si el nombre de la serie viene en mayuscula no se tiene encuenta para la validación de la subserie
                                        if ((strtoupper($nombreSerie) != $nombreSerie)) {

                                            $nombreSerie = ucfirst(strtolower(trim($nombreSerie)));

                                            // Valida que el código de la subserie no exista ya para esa serie
                                            // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                            $subserie = $this->validateSubserieCodigo($codigoSubserie, $nombreSerie, $codigoSerie);

                                            if ($subserie) {
                                                # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($subserie->nombreGdTrdSubserie != $nombreSerie && !$modelGdTrd) {
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $subserie->nombreGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }

                                            // Valida que el código de la subserie no exista ya para esa serie
                                            // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                            $subserieNombre = $this->validateSubserieNombre($codigoSubserie, $nombreSerie, $codigoSerie);

                                            if ($subserieNombre) {
                                                # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($subserieNombre->codigoGdTrdSubserie != $codigoSubserie && !$modelGdTrd) {
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistroOtro') . ' ' . $subserieNombre->codigoGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }
                                        }
                                    }

                                    // Procesa la información para insertarla o consultarla, si es el caso
                                    // esto aplica para las SERIES y las SUBSERIES del archivo que se esta leyendo
                                    $processRow = $this->processRow($row, $datosBloque, $modelCgTrd, $modelMascara, $dependencia, $codigoDependencia, $codigoSerie, $codigoSubserie);

                                    ##################################################
                                    ### PROCESO DE TIPOS DOCUMENTALES
                                    // Se valida que es un tipo documental si la columna de código viene vacia
                                    if (is_null($codigoTrd) && $codigoTrd == '') {

                                        $tipos = $this->processNewTiposdocumentales($modelCgTrd, $row, $datosBloque);
                                        $datosBloque['tiposDocumental'] = $tipos;
                                        
                                    }

                                    if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                        $resultado = array_merge(
                                            array_unique(array('serie' => $datosBloque['serie'])),
                                            array_unique(array('subserie' => $datosBloque['subserie'])),
                                            array_unique(array('tiposDocumental' => $datosBloque['tiposDocumental'])),
                                            array_unique(array('dependencia' => $datosBloque['dependencia']))
                                        );

                                        //Almacena dependencia, serie, subserie y tipo documental
                                        $guarda = $this->guardarBloqueMask($resultado, $gdTrdDepend, $uniqid);
                                        if ($guarda['status'] == false) {
                                            $transaction->rollBack();
                                            $messagesError = [];                                             
                                            foreach($guarda['errors'] as $campo){
                                                foreach($campo as $error){
                                                    $messagesError[] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') . ' . $error;
                                                }                                                
                                            }
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $messagesError,
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                                'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                'model' => $guarda['model'], // Solo para debug
                                                'row' => $row, // Solo para debug
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }
                                    }
                                }
                                // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
                                elseif ($validateRow === 2) {

                                    if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                        //Almacena dependencia, serie, subserie y tipo documental
                                        $guarda = $this->guardarBloqueMask($datosBloque, $gdTrdDepend, $uniqid);
                                        if ($guarda['status'] == false) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $guarda['errors'],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                                'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                'model' => $guarda['model'], // Solo para debug
                                                'row' => $row, // Solo para debug
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }
                                    }
                                    break; // terminar con el foreach
                                }
                            }

                        } else {

                            if($dato == ''){
                                $hojasNoProcesadas[] = $indiceHoja;
                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'dataListErrTrdFaltaSerieColumn') . '';

                                $saveDataValid = false;
                            }else{
                                $hojasNoProcesadas[] = $indiceHoja;
                                $errosLoadTrd[$indiceHoja][] = Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDependencia') . ' ' . $dependencia . ' ' . Yii::t('app', 'dataListErrTrdFormato') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                $saveDataValid = false;
                            }
                        }
                    } else {
                        /** Hojas no procesadas */
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLogitud') . ' (' . $length . '), ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                        $saveDataValid = false;
                    }
                }

                /*** Si ya existe la dependencia se valida en dos procesos ***/
                elseif ($modelDependencia) {

                    //Luego se verifica que el id de dependencia no exista en la D.B
                    $modelGdTrd = GdTrd::find()
                        ->where(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                        ->andWhere(['idGdTrdDependencia' => $modelDependencia->idGdTrdDependencia])
                        ->one();
                    
                    /*** Si la dependencia no existe en la tabla de relacion se obtiene los datos desde el excel ***/
                    if (!$modelGdTrd) {

                        $dato = trim(substr($dato, 0, $length));

                        # Se valida inicialmente si la dependencia corresponde a la misma del primer dato del excel
                        if ($dato == $dependencia) {

                            // Se obtiene toda la informacion
                            $datos = array_slice($hojaActualArray, $filaLeer);

                            $response = [];
                            $datosBloque = [];
                            $resultado = [];

                            $datosBloque['dependencia'] = $modelDependencia->idGdTrdDependencia;

                            foreach ($datos as $i => $row) {

                                /**
                                 * Se valida si en la configuración activa indica cual es el caracter de
                                 * separación para obtener la información de la dependencia, serie y subserie
                                 * si el separador es vacio y diferente a "Columnas separadas" se procede a
                                 * hacer un substring del códígo con la longitud de la dependencia.
                                 **/
                                $nombreSerie = trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
                                $codigoTrd = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
                                $posicion = (int) strlen($codigoTrd) - (int) $length;
                                $codigoDependencia = trim(substr($codigoTrd, 0, $length));
                                $codigoSerie = trim(substr($codigoTrd, $length, $posicion));
                                $codigoSubserie = '';

                                if ($separador == '' && $mascara != Yii::$app->params['ConfiguracionMascara']) {
                                } else {

                                    ######### TRATAMIENTO DE LOS DATOS DE LA SERIE, SUBSERIE Y DEPENDNCIA UNA SOLA COLUMNA
                                    if (!is_null($codigoTrd)) {

                                        // Se realiza una busqueda e identificación del caracterer de separación configurado
                                        // contra lo que llegue en la columna que se leea del excel ya que el código de la TRD
                                        // viene en una sola columna.

                                        # Caracterer de separación en la configuración
                                        $buscaMascaraSeparacion = strpos($nombreSeparador, $separador);
                                        # Caracteres de separación en la columna leida
                                        $buscaCaracterSeparacion = strpos($codigoTrd, $separador);

                                        // Si en la busqueda de caracter de separación en la configuración
                                        // retorna true se sabe que se tiene que hacer tratamiento a la columna.
                                        if ($buscaCaracterSeparacion === false && $buscaMascaraSeparacion === false) {} else {

                                            // Se hace explode al valor configurado para compararlo con el explode del Codigo TRD
                                            $explodedeMascara = explode($separador, $nombreSeparador);
                                            // Se hace explode de la columna que se esta leyecto para identifica el código serie y dependencia
                                            $explodedeCodigoTrd = explode($separador, $codigoTrd);

                                            /**
                                             * Se cuenta las posiciones que salieron al ejecutar el explode segun el separador
                                             * configurado por el cliente para saber el código de la serie, subserie. Por lo menos
                                             * se debe encontrar una posición.
                                             **/
                                            if (count($explodedeCodigoTrd) > 0 && count($explodedeMascara) > 0) {

                                                // Si la separación del cóidgo es igual a dos solo se manejan dos posiciones
                                                // quiere decir que se esta manejando una fila de la serie
                                                // dd ss
                                                if (count($explodedeCodigoTrd) == 2) {
                                                    
                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia                                                    
                                                    $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }
                                                // Si la separación es igual a 3 quiere decir que se esta leyendo la fila
                                                // de una subserie dd ss sb 
                                                elseif(count($explodedeCodigoTrd) == 3){
                                                    // Como se sabe que si llegan 3 posiciones en la fila ya es una subserie
                                                    // se procede a convertir en tipo titulo.
                                                    $nombreSerie = ucfirst(strtolower($nombreSerie));

                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia
                                                    if (isset($explodedeCodigoTrd[1])) {
                                                        $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie
                                                        $codigoSubserie = trim($explodedeCodigoTrd[2]); # Código de subserie
                                                    }

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }                                                
                                                else {
                                                    
                                                    $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia
                                                    if (isset($explodedeCodigoTrd[1])) {
                                                        $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie
                                                        $codigoSubserie = trim($explodedeCodigoTrd[2]); # Código de subserie
                                                    }

                                                    // Se quita del arreglo general la ultima subserie y tipo documental
                                                    // para que no quedaran pegados o asociados a los siguientes datos.
                                                    unset($datosBloque['subserie']);
                                                    unset($datosBloque['tiposDocumental']);
                                                }

                                            }
                                            // Si la columna leida no tiene la misma cantidad de campos separados quiere decir
                                            // error de formato
                                            else {
                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdNoCumple') . ' (' . $nombreSeparador . ').';

                                                $saveDataValid = false;
                                            }
                                        }
                                    }
                                }

                                # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza la TRD de la hoja
                                $validateRow = $this->validateRow($row);

                                //Valida que la fila no venga vacia
                                if ($validateRow === true) {

                                    // Apartir de aqui realiza las correspondientes validaciones

                                    ##################################################
                                    ### PROCESO DE SERIES DOCUMENTALES
                                    // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                                    if ((is_null($codigoTrd) && $codigoTrd == '') && (strtoupper($nombreSerie) == $nombreSerie) || ($nombreSerie == '')) {

                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                                        $saveDataValid = false;
        
                                    } else {
        
                                        ##### Lectura de series #####
                                        if (strtoupper($nombreSerie) == $nombreSerie) {
        
                                            // Valida si la serie existe tanto por nombre como por código
                                            $serie = $this->validateSerieCodigo($codigoSerie, $nombreSerie);
        
                                            if ($serie) {
                                                # Si el código de la serie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($serie->nombreGdTrdSerie != $nombreSerie && !$modelGdTrd) {
        
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $serie->nombreGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
        
                                                }
                                            }

                                            // Valida si la serie existe tanto por nombre genera error
                                            $serieNombre = $this->validateSerieNombre($codigoSerie, $nombreSerie);

                                            if ($serieNombre) {
                                                # Si el nombre de la serie existe pero con otro codigo y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($serieNombre->codigoGdTrdSerie != $codigoSerie && !$modelGdTrd) {
        
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') .' '.Yii::t('app', 'dataListErrTrdRegistroOtro') . $serieNombre->codigoGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
        
                                                }
                                            }
                                        }
                                    }

                                    ##################################################
                                    ### PROCESO DE SUBSERIES DOCUMENTALES
                                    ### Valida datos de la subserie, si en la configuración se agrego una norma
                                    if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                                        $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                                    }

                                    $codigoSubserie = $codigoSubserie;
                                    $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                                    $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                                    $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                                    $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                                    $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                                    $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];

                                    if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                                        
                                        $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                                        $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                                        // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                                        if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                                            
                                            if((!is_null($codigoTrd) && $codigoTrd != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                $saveDataValid = false;
                                            }
                                        }
                                    }

                                    $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                                    if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                                        $disposicion = null;
                                    } else {
                                        $disposicion = 10;
                                    }

                                    /**  AGREGADO HOY **/
                                    // Si alguno de los datos de estas columnas son vacios genera error de formato SUBSERIE
                                    if ((!is_null($codigoTrd) && $codigoTrd != '') && ($nombreSerie == "" || is_null($nombreSerie) ) ) {
                                        
                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;
                                    }
                                    /** FIN AGREGADO HOY **/

                                    // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                                    // Si alguno de los datos de estas columnas son vacios genera error de formato
                                    if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {
                                        
                                        // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                                        // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                                        // si $codigoSubserie != '00' se descarta las series entre a esta validación
                                        if ((!is_null($codigoTrd) && $codigoTrd != '') && ($codigoSubserie == '00' || $codigoSubserie == '000')  && (!is_null($codigoSubserie) && $codigoSubserie != '')) {
                                            
                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;

                                        }

                                    }
                                    // Si llega el código de la dependencia y tambien el código de la subserie pero no la serie y tampoco el nombre error de formato
                                    elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSubserie) && $codigoSubserie != '') && (is_null($codigoSerie) && $codigoSerie == '') /*&& (is_null($nombreSerie) && $nombreSerie == '')*/) {

                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;
                                    } 
                                    // Si llega el código de la dependencia y tambien el cóidgo de la serie pero no la subserie  y tampoco el nombre error de formato
                                    elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSerie) && $codigoSerie != '') && (is_null($codigoSubserie) && $codigoSubserie == '') /*&& ($codigoSubserie != '00' || $codigoSubserie != '000')*/ /* && (is_null($nombreSerie) && $nombreSerie == '')*/) {
                                        
                                        $hojasNoProcesadas[] = $indiceHoja;
                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                        $saveDataValid = false;

                                    } 
                                    else {
                                        
                                        // Si el nombre de la serie viene en mayuscula no se tiene encuenta para la validación de la subserie
                                        if ((strtoupper($nombreSerie) != $nombreSerie)) {

                                            $nombreSerie = ucfirst(strtolower(trim($nombreSerie)));

                                            // Valida que el código de la subserie no exista ya para esa serie
                                            // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                            $subserie = $this->validateSubserieCodigo($codigoSubserie, $nombreSerie, $codigoSerie);

                                            if ($subserie) {
                                                # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($subserie->nombreGdTrdSubserie != $nombreSerie && !$modelGdTrd) {
                                                    
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $subserie->nombreGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }

                                            // Valida que el código de la subserie no exista ya para esa serie
                                            // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                            $subserieNombre = $this->validateSubserieNombre($codigoSubserie, $nombreSerie, $codigoSerie);

                                            if ($subserieNombre) {
                                                # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                # maestra (trd) no se procesa el archivo.
                                                if ($subserieNombre->codigoGdTrdSubserie != $codigoSubserie && !$modelGdTrd) {
                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistroOtro') . ' ' . $subserieNombre->codigoGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }
                                        }
                                    }

                                    // Procesa la información para insertarla o consultarla, si es el caso
                                    // esto aplica para las SERIES y las SUBSERIES del archivo que se esta leyendo
                                    $processRow = $this->processRow($row, $datosBloque, $modelCgTrd, $modelMascara, $dependencia, $codigoDependencia, $codigoSerie, $codigoSubserie);

                                    ##################################################
                                    ### PROCESO DE TIPOS DOCUMENTALES
                                    // Se valida que es un tipo documental si la columna de código viene vacia
                                    if (is_null($codigoTrd) && $codigoTrd == '') {

                                        $tipos = $this->processNewTiposdocumentales($modelCgTrd, $row, $datosBloque);
                                        $datosBloque['tiposDocumental'] = $tipos;
                                        
                                    }

                                    if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                        $resultado = array_merge(
                                            array_unique(array('serie' => $datosBloque['serie'])),
                                            array_unique(array('subserie' => $datosBloque['subserie'])),
                                            array_unique(array('tiposDocumental' => $datosBloque['tiposDocumental'])),
                                            array_unique(array('dependencia' => $datosBloque['dependencia']))
                                        );
                                        
                                        //Almacena dependencia, serie, subserie y tipo documental
                                        $guarda = $this->guardarBloqueMask($resultado, $modelDependencia, $uniqid );
                                        if ($guarda['status'] == false) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $guarda['errors'],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                                'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                'model' => $guarda['model'], // Solo para debug
                                                'row' => $row, // Solo para debug
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }

                                    }
                                }
                                // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
                                elseif ($validateRow === 2) {

                                    if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                        //Almacena dependencia, serie, subserie y tipo documental
                                        $guarda = $this->guardarBloqueMask($resultado, $modelDependencia, $uniqid);
                                        if ($guarda['status'] == false) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $guarda['errors'],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                                'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                'model' => $guarda['model'], // Solo para debug
                                                'row' => $row, // Solo para debug
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }
                                    }
                                    break; // terminar con el foreach
                                }
                            }

                        } else {
                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDependencia') . ' ' . $dependencia . ' ' . Yii::t('app', 'dataListErrTrdFormato') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                            $saveDataValid = false;
                        }
                    }

                    /*** Si la dependencia si existe en las tablas reales se inserta en las temporales ***/
                    else {

                        $resultadoTmpMask = $this->trdsTemporalesMask($dependencia, $nombreDependencia, $codigoDepePadre, $modelRegional, $modelDependencia, $hojaActualArray, $filaLeer, $modelCgTrd, $sheetNames, $indiceHoja, $filaDatos, $modelMascara, $length);

                        if (!is_null($resultadoTmpMask)  && !empty($resultadoTmpMask) && $resultadoTmpMask != []) {
                            $hojasNoProcesadas[] = $indiceHoja;

                            foreach ($resultadoTmpMask as $errorTmp => $err) {
                                $errosLoadTrd[$indiceHoja][] = $err;
                                $saveDataValid = false; 
                            }
                        } else {
                            $temporal = 'si';
                            $fechaTrd = $modelGdTrd->creacionGdTrd;
                            $mensajeTrdTemporal = Yii::t('app', 'mensajeTrdTemporal') . ' ' . date('F j, Y, g:i a', strtotime($fechaTrd));
                            $mensajeTrdTemporal .= Yii::t('app', 'mensajeTrdTemporalDos');
                        }
                    }
                }

            } else {
                /** Hojas no procesadas */
                $hojasNoProcesadas[] = $indiceHoja;
                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLogitud') . ' (' . $length . '), ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                $saveDataValid = false;
            }

            /****** Fin Validación del codigo de dependencia ******/
            if (!in_array($indiceHoja, $hojasNoProcesadas)) {
                $transaction->commit();
            } else {
                $transaction->rollBack();
            }
        }
        # Fin Iterar hoja por hoja

        $hojasNoProcesadas = array_unique($hojasNoProcesadas);

        $messageHojas = '' . Yii::t('app', 'dataListErrProcesado') . ' ' . ($totalDeHojas - count($hojasNoProcesadas)) . ' ' . Yii::t('app', 'dataListErrDe') . ' ' . $totalDeHojas . ' ' . Yii::t('app', 'dataListErrHojas') . '';
        $mensajesError = '';

        foreach ($errosLoadTrd as $key => $valor) {
            foreach ($valor as $mensaje) {
                $mensajesError .= ' ' . $mensaje;
            }
        }

        if (count($hojasNoProcesadas) > 0) {
            $message = '' . Yii::t('app', 'dataListErrArchivo') . ' ' . $messageHojas . '. ' . Yii::t('app', 'dataListErrHojasProcesadas') . ': ' . $mensajesError;

        } else {
            $message = '' . Yii::t('app', 'dataListErrArchivo') . ' ' . $messageHojas . '.';

            if ($temporal == 'si' && count($errosLoadTrd) == 0) {
                $message .= '# ' . $mensajeTrdTemporal;
            }
        }

        /**
         * Retornar respuesta del servicio
         */
        if ($saveDataValid == true) {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => $message,
                'data' => $errosLoadTrd,
                'status' => 200,
                'hojasNoProcesadas' => $hojasNoProcesadas,
            ];
            return HelperEncryptAes::encrypt($response, true);
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => $message,
                'data' => ['error' => $mensajesError],
                'status' => Yii::$app->params['statusErrorValidacion'],
                'hojasNoProcesadas' => $hojasNoProcesadas,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Función que lee el archivo sin expresiones regulares porque las columnas van separadas
     * Codigo dependencia, serie y subserie
     */
    public function readFileNormal($modelMascara, $pathUploadFile, $modelCgTrd)
    {

        /** Se lee de esta forma para fonprecon debido a problemas de compatibilidad para hacerlo con IOFactory */
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        $reader->setReadDataOnly(true);
        $documento = $reader->load($pathUploadFile);

        /****** Validación de archivo ******/
        // $documento = IOFactory::load($pathUploadFile);

        if(empty($documento)){

            Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => 'No se puedo leer el archivo de la trd'],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
            return HelperEncryptAes::encrypt($response, true);

        }else{

            //Se obtiene el nombre de cada hoja
            $sheetNames = $documento->getSheetNames();

            # Recordar que un documento puede tener múltiples hojas
            # obtener conteo e iterar
            $totalDeHojas = $documento->getSheetCount();

            $saveDataValid = true;
            $errosLoadTrd = [];

            # Iterar hoja por hoja
            $hojasNoProcesadas = [];
            for ($indiceHoja = 0; $indiceHoja < $totalDeHojas; $indiceHoja++) {

                /** Inicio de la transaccion */
                $transaction = Yii::$app->db->beginTransaction();

                # Obtener hoja en el índice que vaya del ciclo
                $hojaActual = $documento->getSheet($indiceHoja);

                # Permite manejar los datos como un array y no como un objeto
                $highestRow = $hojaActual->getHighestRow(); //Cantidad de filas de la hoja
                $highestColumn = Yii::$app->params['highestColumn']; // Columna hasta la que toca leer

                //$hojaActualArray = $hojaActual->toArray();
                $hojaActualArray = $hojaActual->rangeToArray("A1:$highestColumn$highestRow");

                //Calcula UUID
                $uniqid = uniqid();

                # Fila y columna de la ubicacion del titulo de la dependencia
                $filaNombreDepend = (int) substr($modelCgTrd->cellTituloDependCgTrd, 1);
                $colNombreDepend = $this->numberByLetter(substr($modelCgTrd->cellTituloDependCgTrd, 0, 1));
                $nombreDependencia = $hojaActualArray[$filaNombreDepend - 1][$colNombreDepend];

                # Validación del nombre de la dependencia
                if (!$nombreDependencia) {
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdEncabezado') . '';

                    $saveDataValid = false;

                } else {
                    // Si el nombre de una dependencia viene con el caracter (:) se realiza
                    // un explode del campo para sacar el dato solamente del nombre
                    $buscaCaracter = strpos($nombreDependencia, ':');

                    if ($buscaCaracter !== false) {
                        $explodedependencia = explode(":", $nombreDependencia);
                        $nombreDependencia = trim($explodedependencia[1]);

                        // Si luego de hacer explode del nombre de la dependencia el campo esta vacio se debe enviar error formato
                        if($nombreDependencia == '' || is_null($nombreDependencia)){
                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdEncabezado') . '';

                            $saveDataValid = false;
                        }
                    }
                }

                # Fila y columna de la ubicacion de la unidad administrativa
                $filaNombreDependPadre = (int) substr($modelCgTrd->cellDependenciaPadreCgTrd, 1);
                $colNombreDependPadre = $this->numberByLetter(substr($modelCgTrd->cellDependenciaPadreCgTrd, 0, 1));
                $nombreDependenciaPadre = $hojaActualArray[$filaNombreDependPadre - 1][$colNombreDependPadre];

                if (!$nombreDependenciaPadre) {
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDepePadre') . '';

                    $saveDataValid = false;

                } else {
                    // Si el nombre de la unidad administrativa viene con el caracter (:) se realiza
                    // un explode del campo para sacar el dato solamente del nombre
                    $buscaCaracter = strpos($nombreDependenciaPadre, ':');

                    if ($buscaCaracter !== false) {
                        $explodedependenciaPadre = explode(":", $nombreDependenciaPadre);
                        $nombreDependenciaPadre = trim($explodedependenciaPadre[1]);

                        // Si luego de hacer explode del nombre de la unidad administrativa, el campo esta vacio se debe enviar error formato
                        // if($nombreDependenciaPadre == '' || is_null($nombreDependenciaPadre)){
                        //     $hojasNoProcesadas[] = $indiceHoja;
                        //     $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDepePadre') . '';

                        //     $saveDataValid = false;
                        // }
                    }
                }

                # Fila y columna de la ubicacion del código dependencia
                $filaDependencia = (int) substr($modelCgTrd->cellDependenciaCgTrd, 1);
                $dependencia = $hojaActual->getCell($modelCgTrd->cellDependenciaCgTrd)->getValue();

                # Fila y columna de la ubicacion del nombre de la regional
                // Se valida si en la columna de regional hay algun valor si no lo hay se toma
                // la regional que por defecto se configura en params
                if ($modelCgTrd->cellRegionalCgTrd != '') {
                    $filaRegional = (int) substr($modelCgTrd->cellRegionalCgTrd, 1);
                    $colRegional = $this->numberByLetter(substr($modelCgTrd->cellRegionalCgTrd, 0, 1));
                    $nombreRegional = $hojaActualArray[$filaRegional - 1][$colRegional];

                    if ($nombreRegional == '') {
                        // Sumar error de regional no procesada
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdRegional') . '';

                        $saveDataValid = false;
                    }
                    // Si esta configurada la regional pero la misma viene en con un (:)
                    else{

                        // un explode del campo para sacar el dato solamente del nombre
                        $buscaCaracter = strpos($nombreRegional, ':');

                        if ($buscaCaracter !== false) {
                            $explodedependenciaRegional = explode(":", $nombreRegional);
                            $nombreRegional = trim($explodedependenciaRegional[1]);

                            // Si luego de hacer explode del nombre de la dependencia el campo esta vacio se debe enviar error formato
                            if($nombreRegional == '' || is_null($nombreRegional)){
                                $hojasNoProcesadas[] = $indiceHoja;
                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdRegional') . '';

                                $saveDataValid = false;
                            }
                        }
                    }

                } else {
                    $nombreRegional = Yii::$app->params['regionalDefaul'];
                }

                # Fila y columna donde inicia los datos del formato
                $filaDatos = (int) substr($modelCgTrd->cellDatosCgTrd, 1);
                $colDatos = $this->numberByLetter(substr($modelCgTrd->cellDatosCgTrd, 0, 1));
                $dato = $hojaActualArray[$filaDatos - 1][$colDatos];

                /** Se valida que la regional exista en la D.B */
                $modelRegional = CgRegionales::find()
                    ->where(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['nombreCgRegional' => $nombreRegional])
                    ->one();

                if (is_null($modelRegional)) {
                    // Sumar error de regional no procesada
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdNombreReg') . '';

                    $saveDataValid = false;
                }
                /** Fin Se valida que la regional exista en la D.B */

                # Se consulta la longitud de la dependencia.
                $modelLengthDependence = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);

                $length = '';
                if(!is_null($modelLengthDependence)){
                    $length = $modelLengthDependence->longitudDependenciaCgNumeroRadicado;
                } 

                // Se valida que exista numero de dependencia y la regional en cada iteración
                if (strlen($dependencia) == $length && $dependencia && isset($modelRegional)) {

                    //Primero se valida que la dependencia no exista en la D.B
                    $modelDependencia = GdTrdDependencias::find()
                        ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                        ->andWhere(['codigoGdTrdDependencia' => $dependencia])
                        ->one();

                    //Se realiza consulta del nombre de la dependencia Unidad Administrativa
                    //si existe el nombre se extrae el código y se guarda en la dependenciaPadre
                    $modelDependenciaPadre = GdTrdDependencias::find()
                        ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                        ->andWhere(['nombreGdTrdDependencia' => trim($nombreDependenciaPadre)])
                        ->orderBy(['idGdTrdDependencia' => SORT_DESC])
                        ->one();

                    // En el código de la dependencia padre toca guardar el id de la dependencia unidad administrativa
                    if (!is_null($modelDependenciaPadre)) {
                        $codigoDepePadre = $modelDependenciaPadre->idGdTrdDependencia;
                    } else {
                        $codigoDepePadre = 0;
                    }

                    # Fila a leer
                    $filaLeer = $filaDatos - 1;
                    $temporal = 'no'; // Estado que indica si la trd, que se esta cargando es temporal.
                    $mensajeTrdTemporal = '';

                    /*** Si no existe la dependencia se continua validando el formato ***/
                    if (!$modelDependencia && !is_null($modelRegional)) {

                        if (!is_null($modelRegional)) {

                            # Se valida inicialmente si la dependencia corresponde a la misma del primer dato del excel
                            if ($dato == $dependencia) {

                                // Se obtiene toda la informacion
                                $datos = array_slice($hojaActualArray, $filaLeer);

                                # Insertamos la dependencia acorde a los datos leidos del excel
                                $gdTrdDepend = new GdTrdDependencias();
                                $gdTrdDepend->codigoGdTrdDependencia = (string) $dependencia;
                                $gdTrdDepend->nombreGdTrdDependencia = trim($nombreDependencia);
                                $gdTrdDepend->codigoGdTrdDepePadre = (string) $codigoDepePadre;
                                $gdTrdDepend->idCgRegional = $modelRegional->idCgRegional;
                                $gdTrdDepend->save();

                                //Luego se verifica que el id de dependencia no exista en la D.B
                                $modelGdTrd = GdTrd::find()
                                    ->where(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                                    ->andWhere(['idGdTrdDependencia' => $gdTrdDepend->idGdTrdDependencia])
                                    ->one();

                                $response = [];
                                $datosBloque = [];
                                $resultado = [];

                                $datosBloque['dependencia'] = $gdTrdDepend->idGdTrdDependencia;

                                foreach ($datos as $i => $row) {

                                    # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza
                                    // la TRD de la hoja y se frena el proceso.
                                    $validateRow = $this->validateRow($row);

                                    //Valida que la fila no venga vacia
                                    if ($validateRow === true) {

                                        // Valida dato de la serie
                                        $codigoDependencia = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
                                        $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
                                        $nombreSerie = trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
                                        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];

                                        if($codigoDependencia != '' && $codigoSerie != ''){
                                            // Se quita del arreglo general la ultima subserie y tipo documental
                                            // para que no quedaran pegados o asociados a los siguientes datos.
                                            unset($datosBloque['subserie']);
                                            unset($datosBloque['tiposDocumental']); 
                                        }

                                        if($codigoDependencia != '' && $codigoSerie != '' && $codigoSubserie != ''){
                                            // Se quita del arreglo general la ultima subserie y tipo documental
                                            // para que no quedaran pegados o asociados a los siguientes datos.
                                            unset($datosBloque['subserie']);
                                            unset($datosBloque['tiposDocumental']); 
                                        }

                                        // Apartir de aqui realiza las correspondientes validaciones

                                        ##################################################
                                        ########## PROCESO DE SERIES DOCUMENTALES ########
                                        ##################################################

                                        // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                                        if (((is_null($codigoDependencia) || $codigoDependencia == '') || (is_null($codigoSerie) || $codigoSerie == '')) && (strtoupper($nombreSerie) == $nombreSerie)) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                                            $saveDataValid = false;

                                        } else {

                                            ##### Lectura de series #####
                                            if (strtoupper($nombreSerie) == $nombreSerie) {

                                                // Valida si la serie existe tanto por nombre y código
                                                $serie = $this->validateSerie($codigoSerie, $nombreSerie);

                                                /* Si la validación es correcta retorna el modelo de serie */
                                                if ($serie) {
                                                    # Si el código de la serie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($serie->nombreGdTrdSerie != $nombreSerie && !$modelGdTrd) {

                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $serie->nombreGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;

                                                    }
                                                }

                                                // Valida si la serie existe por nombre genera error
                                                $serieNombre = $this->validateSerieNombre($codigoSerie, $nombreSerie);

                                                if ($serieNombre) {
                                                    # Si el nombre de la serie existe pero con otro codigo y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($serieNombre->codigoGdTrdSerie != $codigoSerie && !$modelGdTrd) {
            
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .' '. ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') .' '.Yii::t('app', 'dataListErrTrdRegistroOtro') . $serieNombre->codigoGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
            
                                                    }
                                                }
                                            }
                                        }

                                        ##################################################
                                        ######## PROCESO DE SUBSERIES DOCUMENTALES #######
                                        ##################################################
                                        // Valida datos de la subserie, si en la configuración se agrego una norma
                                        if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                                            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                                        }

                                        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
                                        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                                        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                                        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                                        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                                        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                                        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
                                        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                                        if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                                            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                                            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                                            // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                                            if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                                                
                                                if($codigoSubserie != '00' && (!is_null($codigoSubserie) && $codigoSubserie != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' '. ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }
                                        }          


                                        if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                                            $disposicion = null;
                                        } else {
                                            $disposicion = 10;
                                        }
                                               
                                        // Si alguno de los datos de estas columnas son vacios genera error de formato SUBSERIE
                                        if ( $codigoSubserie != '00' && (!is_null($codigoSubserie) || $codigoSubserie != '') 
                                        && ($nombreSerie == "" || is_null($nombreSerie) ) ) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;
                                        }

                                        // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                                        // Si alguno de los datos de estas columnas son vacios genera error de formato
                                        if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {

                                            // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                                            // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                                            // si $codigoSubserie != '00' o $codigoSubserie != '000' se descarta las series entre a esta validación
                                            if (($codigoSubserie != '00' || $codigoSubserie != '000') && (!is_null($codigoSubserie) && $codigoSubserie != '')) {
                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                $saveDataValid = false;
                                            }
                                        }
                                        // Si llega el código de la dependencia y tambien el código de la subserie pero no la serie y tampoco el nombre error de formato
                                        elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSubserie) && $codigoSubserie != '') && (is_null($codigoSerie) && $codigoSerie == '') /*&& (is_null($nombreSerie) && $nombreSerie == '')*/) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;
                                        }
                                        // Si llega el código de la dependencia y tambien el cóidgo de la serie pero no la subserie  y tampoco el nombre error de formato
                                        elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSerie) && $codigoSerie != '') && (is_null($codigoSubserie) && $codigoSubserie == '') && ($codigoSubserie != '00' || $codigoSubserie != '000')  && (strtoupper($nombreSerie) != $nombreSerie)) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;

                                        } else {

                                            // Si el nombre de la serie viene en mayuscula no se tiene encuenta para la validación de la subserie
                                            if ((strtoupper($nombreSerie) != $nombreSerie)) {

                                                $nombreSerie = ucfirst(strtolower(trim($nombreSerie)));

                                                // Valida que el código de la subserie no exista ya para esa serie
                                                // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                                $subserie = $this->validateSubserieCodigo($codigoSubserie, $nombreSerie, $codigoSerie);
                                                if ($subserie) {
                                                    # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($subserie->nombreGdTrdSubserie != $nombreSerie && !$modelGdTrd) {
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $subserie->nombreGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
                                                    }
                                                }

                                                // Valida que el código de la subserie no exista ya para esa serie
                                                // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                                $subserieNombre = $this->validateSubserieNombre($codigoSubserie, $nombreSerie, $codigoSerie);

                                                if ($subserieNombre) {
                                                    # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($subserieNombre->codigoGdTrdSubserie != $codigoSubserie && !$modelGdTrd) {
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistroOtro') . ' ' . $subserieNombre->codigoGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
                                                    }
                                                }
                                            }
                                        }

                                        // Procesa la información para insertarla o consultarla, si es el caso
                                        // esto aplica para las SERIES y las SUBSERIES del archivo que se esta leyendo
                                        $processRow = $this->processRowNormal($row, $datosBloque, $modelCgTrd, $dependencia, $codigoSerie);

                                        ##################################################
                                        ########## PROCESO DE TIPOS DOCUMENTALES #########
                                        // Se valida que es un tipo documental si los campos de los códigos son vacios
                                        if ((is_null($codigoSerie) && $codigoSerie == '') && (is_null($codigoSubserie) && $codigoSubserie == '') && (is_null($codigoDependencia) && $codigoDependencia == '')) {

                                            $tipos = $this->processNewTiposdocumentales($modelCgTrd, $row, $datosBloque);
                                            $datosBloque['tiposDocumental'] = $tipos;
                                        }

                                        if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                            $resultado = array_merge(
                                                array_unique(array('serie' => $datosBloque['serie'])),
                                                array_unique(array('subserie' => $datosBloque['subserie'])),
                                                array_unique(array('tiposDocumental' => $datosBloque['tiposDocumental'])),
                                                array_unique(array('dependencia' => $datosBloque['dependencia']))
                                            );

                                            //Almacena dependencia, serie, subserie y tipo documental
                                            $guarda = self::guardarBloque($resultado, $gdTrdDepend, $uniqid);
                                            if ($guarda['status'] == false) {
                                                $transaction->rollBack();
                                                $messagesError = [];                                             
                                                foreach($guarda['errors'] as $campo){
                                                    foreach($campo as $error){
                                                        $messagesError[] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') . ' . $error;
                                                    }                                                
                                                }
                                                Yii::$app->response->statusCode = 200;
                                                $response = [
                                                    'message' => Yii::t('app', 'errorValidacion'),
                                                    'data' => $messagesError,
                                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                                    'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                    'model' => $guarda['model'], // Solo para debug
                                                    'row' => $row, // Solo para debug
                                                ];
                                                return HelperEncryptAes::encrypt($response, true);
                                            }

                                        }
                                        // }
                                    }
                                    // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
                                    elseif ($validateRow === 2) {

                                        if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                            //Almacena dependencia, serie, subserie y tipo documental
                                            $guarda = self::guardarBloque($datosBloque, $gdTrdDepend, $uniqid);
                                            if ($guarda['status'] == false) {
                                                $transaction->rollBack();
                                                Yii::$app->response->statusCode = 200;
                                                $response = [
                                                    'message' => Yii::t('app', 'errorValidacion'),
                                                    'data' => $guarda['errors'],
                                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                                    'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                    'model' => $guarda['model'], // Solo para debug
                                                    'row' => $row, // Solo para debug
                                                ];
                                                return HelperEncryptAes::encrypt($response, true);
                                            }
                                        }
                                        break; // terminar con el foreach
                                    }
                                }

                            } else {
                                $hojasNoProcesadas[] = $indiceHoja;
                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDependencia') . ' ' . $dependencia . ' ' . Yii::t('app', 'dataListErrTrdFormato') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                $saveDataValid = false;
                            }
                        } else {
                            /** Hojas no procesadas */
                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLogitud') . ' (' . $length . '), ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                            $saveDataValid = false;
                        }
                    }

                    /*** Si ya existe la dependencia se valida en dos procesos ***/
                    elseif ($modelDependencia) {

                        //Luego se verifica que el id de dependencia no exista en la D.B
                        $modelGdTrd = GdTrd::find()
                            ->where(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                            ->andWhere(['idGdTrdDependencia' => $modelDependencia->idGdTrdDependencia])
                            ->one();

                        /*** Si la dependencia no existe en la tabla de relacion se obtiene los datos desde el excel ***/
                        if (!$modelGdTrd) {

                            $dato = trim(substr($dato, 0, $length));

                            # Se valida inicialmente si la dependencia corresponde a la misma del primer dato del excel
                            if ($dato == $dependencia) {

                                // Se obtiene toda la informacion
                                $datos = array_slice($hojaActualArray, $filaLeer);

                                $response = [];
                                $datosBloque = [];
                                $resultado = [];

                                $datosBloque['dependencia'] = $modelDependencia->idGdTrdDependencia;

                                foreach ($datos as $i => $row) {
                                    
                                    # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza
                                    // la TRD de la hoja y se frena el proceso.
                                    $validateRow = $this->validateRow($row);

                                    //Valida que la fila no venga vacia
                                    if ($validateRow === true) {

                                        // Valida dato de la serie
                                        $codigoDependencia = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
                                        $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
                                        $nombreSerie = trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
                                        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];

                                        if($codigoDependencia != '' && $codigoSerie != ''){
                                            // Se quita del arreglo general la ultima subserie y tipo documental
                                            // para que no quedaran pegados o asociados a los siguientes datos.
                                            unset($datosBloque['subserie']);
                                            unset($datosBloque['tiposDocumental']); 
                                        }

                                        if($codigoDependencia != '' && $codigoSerie != '' && $codigoSubserie != ''){
                                            // Se quita del arreglo general la ultima subserie y tipo documental
                                            // para que no quedaran pegados o asociados a los siguientes datos.
                                            unset($datosBloque['subserie']);
                                            unset($datosBloque['tiposDocumental']); 
                                        }

                                        // Apartir de aqui realiza las correspondientes validaciones

                                        ##################################################
                                        ### PROCESO DE SERIES DOCUMENTALES
                                        // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                                        if (((is_null($codigoDependencia) || $codigoDependencia == '') || (is_null($codigoSerie) || $codigoSerie == '')) && (strtoupper($nombreSerie) == $nombreSerie)) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                                            $saveDataValid = false;

                                        } else {

                                            ##### Lectura de series #####
                                            if (strtoupper($nombreSerie) == $nombreSerie) {

                                                // Valida si la serie existe tanto por nombre como por código
                                                $serie = $this->validateSerieCodigo($codigoSerie, $nombreSerie);

                                                if ($serie) {
                                                    # Si el código de la serie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($serie->nombreGdTrdSerie != $nombreSerie && !$modelGdTrd) {

                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $serie->nombreGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;

                                                    }
                                                }

                                                // Valida si la serie existe tanto por nombre genera error
                                                $serieNombre = $this->validateSerieNombre($codigoSerie, $nombreSerie);

                                                if ($serieNombre) {
                                                    # Si el nombre de la serie existe pero con otro codigo y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($serieNombre->codigoGdTrdSerie != $codigoSerie && !$modelGdTrd) {
            
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdSerieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') .' '.Yii::t('app', 'dataListErrTrdRegistroOtro') . $serieNombre->codigoGdTrdSerie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
            
                                                    }
                                                }    
                                            }

                                        }

                                        ##################################################
                                        ### PROCESO DE SUBSERIES DOCUMENTALES
                                        ### Valida datos de la subserie, si en la configuración se agrego una norma
                                        if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                                            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                                        }

                                        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
                                        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                                        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                                        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                                        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                                        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                                        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];

                                        if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                                            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                                            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                                            // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                                            if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                                                
                                                if($codigoSubserie != '00' && (!is_null($codigoSubserie) && $codigoSubserie != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                                                    $hojasNoProcesadas[] = $indiceHoja;
                                                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                    $saveDataValid = false;
                                                }
                                            }
                                        }

                                        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                                        if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                                            $disposicion = null;
                                        } else {
                                            $disposicion = 10;
                                        }

                                        // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                                        // Si alguno de los datos de estas columnas son vacios genera error de formato
                                        if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {

                                            // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                                            // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                                            // si $codigoSubserie != '00' se descarta las series entre a esta validación
                                            if ($codigoSubserie != '00' && (!is_null($codigoSubserie) && $codigoSubserie != '')) {

                                                $hojasNoProcesadas[] = $indiceHoja;
                                                $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                $saveDataValid = false;

                                            }

                                        }
                                        // Si llega el código de la dependencia y tambien el cóidgo de la subserie pero no la serie error de formato
                                        elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSubserie) && $codigoSubserie != '') && (is_null($codigoSerie) && $codigoSerie == '')) {

                                            $hojasNoProcesadas[] = $indiceHoja;
                                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                            $saveDataValid = false;

                                        } else {

                                            // Si el nombre de la serie viene en mayuscula no se tiene encuenta para la validación de la subserie
                                            if ((strtoupper($nombreSerie) != $nombreSerie)) {

                                                $nombreSerie = ucfirst(strtolower($nombreSerie));

                                                // Valida que el código de la subserie no exista ya para esa serie
                                                // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                                $subserie = $this->validateSubserieCodigo($codigoSubserie, $nombreSerie, $codigoSerie);

                                                if ($subserie) {
                                                    # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($subserie->nombreGdTrdSubserie != $nombreSerie && !$modelGdTrd) {
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida1') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistro') . ' ' . $subserie->nombreGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
                                                    }
                                                }

                                                // Valida que el código de la subserie no exista ya para esa serie
                                                // validateSubserieCodigo valida la subserie con el código de la serie y obtiene el id
                                                $subserieNombre = $this->validateSubserieNombre($codigoSubserie, $nombreSerie, $codigoSerie);

                                                if ($subserieNombre) {
                                                    # Si el código de la subserie existe pero con otro nombre y no existe en la tabla
                                                    # maestra (trd) no se procesa el archivo.
                                                    if ($subserieNombre->codigoGdTrdSubserie != $codigoSubserie && !$modelGdTrd) {
                                                        $hojasNoProcesadas[] = $indiceHoja;
                                                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserieRepetida2') . ' ' . $nombreSerie . ' ' . Yii::t('app', 'dataListErrTrdRegistroOtro') . ' ' . $subserieNombre->codigoGdTrdSubserie . ', ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                                        $saveDataValid = false;
                                                    }
                                                }
                                            }
                                        }

                                        // Procesa la información para insertarla o consultarla, si es el caso
                                        // esto aplica para las SERIES y las SUBSERIES del archivo que se esta leyendo
                                        $processRow = $this->processRowNormal($row, $datosBloque, $modelCgTrd, $dependencia, $codigoSerie);

                                        ##################################################
                                        ### PROCESO DE TIPOS DOCUMENTALES
                                        // Se valida que es un tipo documental si los campos de los códigos son vacios
                                        if ((is_null($codigoSerie) && $codigoSerie == '') && (is_null($codigoSubserie) && $codigoSubserie == '') && (is_null($codigoDependencia) && $codigoDependencia == '')) {

                                            $tipos = $this->processNewTiposdocumentales($modelCgTrd, $row, $datosBloque);
                                            $datosBloque['tiposDocumental'] = $tipos;
                                        }

                                        if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                            $resultado = array_merge(
                                                array_unique(array('serie' => $datosBloque['serie'])),
                                                array_unique(array('subserie' => $datosBloque['subserie'])),
                                                array_unique(array('tiposDocumental' => $datosBloque['tiposDocumental'])),
                                                array_unique(array('dependencia' => $datosBloque['dependencia']))
                                            );

                                            //return $resultado;

                                            //Almacena dependencia, serie, subserie y tipo documental
                                            $guarda = $this->guardarBloque($resultado, $modelDependencia, $uniqid);                                        

                                            if ($guarda['status'] == false) {
                                                $transaction->rollBack();
                                                Yii::$app->response->statusCode = 200;
                                                $response = [
                                                    'message' => Yii::t('app', 'errorValidacion'),
                                                    'data' => $guarda['errors'],
                                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                                    'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                    'model' => $guarda['model'], // Solo para debug
                                                    'row' => $row, // Solo para debug
                                                ];
                                                return HelperEncryptAes::encrypt($response, true);
                                            }
                                            //return $guarda;

                                        }
                                        // }
                                    }
                                    // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
                                    elseif ($validateRow === 2) {

                                        if (isset($datosBloque['serie']) && isset($datosBloque['subserie']) && isset($datosBloque['tiposDocumental'])) {

                                            //Almacena dependencia, serie, subserie y tipo documental
                                            $guarda = $this->guardarBloque($datosBloque, $modelDependencia, $uniqid);

                                            if ($guarda['status'] == false) {
                                                $transaction->rollBack();
                                                Yii::$app->response->statusCode = 200;
                                                $response = [
                                                    'message' => Yii::t('app', 'errorValidacion'),
                                                    'data' => $guarda['errors'],
                                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                                    'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                                    'model' => $guarda['model'], // Solo para debug
                                                    'row' => $row, // Solo para debug
                                                ];
                                                return HelperEncryptAes::encrypt($response, true);
                                            }
                                        }
                                        break; // terminar con el foreach
                                    }
                                }

                            } else {
                                $hojasNoProcesadas[] = $indiceHoja;
                                $errosLoadTrd[$indiceHoja][] = Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdDependencia') . ' ' . $dependencia . ' ' . Yii::t('app', 'dataListErrTrdFormato') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                                $saveDataValid = false;
                            }
                        }

                        /*** Si la dependencia si existe en las tablas reales se inserta en las temporales ***/
                        else {

                            $resultadoTrd = $this->trdsTemporales($dependencia, $nombreDependencia, $codigoDepePadre, $modelRegional, $modelDependencia, $hojaActualArray, $filaLeer, $modelCgTrd, $sheetNames, $indiceHoja, $filaDatos);

                            if (!is_null($resultadoTrd) && !empty($resultadoTrd) && $resultadoTrd != [])  {                            
                                $hojasNoProcesadas[] = $indiceHoja;                            
                            
                                foreach ($resultadoTrd as $errorTmp => $err) {
                                    $errosLoadTrd[$indiceHoja][] = $err;
                                    $saveDataValid = false;                                                            
                                }
                            } else {
                                $temporal = 'si';
                                $fechaTrd = $modelGdTrd->creacionGdTrd;
                                $mensajeTrdTemporal = Yii::t('app', 'mensajeTrdTemporal') . ' ' . date('F j, Y, g:i a', strtotime($fechaTrd));
                                $mensajeTrdTemporal .= Yii::t('app', 'mensajeTrdTemporalDos');
                            }
                        }
                    }

                } else {
                    /** Hojas no procesadas */
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = Yii::t('app', 'Hoja') . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'dataListErrTrdLogitud') . ' (' . $length . '), ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                    $saveDataValid = false;
                }           

                /****** Fin Validación del codigo de dependencia ******/
                if (!in_array($indiceHoja, $hojasNoProcesadas)) {
                    $transaction->commit();
                    
                } else {
                    $transaction->rollBack();
                }

            }
            # Fin Iterar hoja por hoja

            $hojasNoProcesadas = array_unique($hojasNoProcesadas);

            $messageHojas = '' . Yii::t('app', 'dataListErrProcesado') . ' ' . ($totalDeHojas - count($hojasNoProcesadas)) . ' ' . Yii::t('app', 'dataListErrDe') . ' ' . $totalDeHojas . ' ' . Yii::t('app', 'dataListErrHojas') . '';
            $mensajesError = '';

            if(count($errosLoadTrd) > 0){
                foreach ($errosLoadTrd as $key => $valor) {
                    foreach ($valor as $mensaje) {
                        $mensajesError .= ' ' . $mensaje;
                    }
                }
            }
            

            if (count($hojasNoProcesadas) > 0) {
                $message = '' . Yii::t('app', 'dataListErrArchivo') . ' ' . $messageHojas . '. ' . Yii::t('app', 'dataListErrHojasProcesadas') . ': ' . $mensajesError;

            } else {
                $message = '' . Yii::t('app', 'dataListErrArchivo') . ' ' . $messageHojas . '.';

                if ($temporal == 'si' && count($errosLoadTrd) == 0) {
                    $message .= '# ' . $mensajeTrdTemporal;
                }
            }

            /**
             * Retornar respuesta del servicio
             */
            if ($saveDataValid == true) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $message,
                    'data' => $errosLoadTrd,
                    'status' => 200,
                    '$hojasNoProcesadas' => $hojasNoProcesadas,
                ];
                return HelperEncryptAes::encrypt($response, true);
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => $mensajesError],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        }
    }

    /**
     * Funcion que procesa la informacion de la serie, subserie con el fin de identificar si se debe crear la información o
     * solo consultarla y asignarla al arreglo para ser procesada.
     **/
    public function processRowNormal($row, &$datosBloque, $modelCgTrd, $dependencia, $numSerie)
    {

        ###############################################
        ############## PROCESO DE SERIE ###############

        // Valida dato de la serie con base a la columna configurada para la lectura
        $nombreSerie = strtoupper($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
        $codigoDependencia = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
        $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];

        // Se sabe que es serie porque tiene el código dependencia, serie y el nombre en MAYÚSCULA
        // se valida que las columnas correspondientes a la serie no lleguen vacias y que la columna de Código subserie esta vacia
        // o sea igual 00.
        if ((!is_null($nombreSerie) || $nombreSerie != '') && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (is_null($codigoSubserie) || $codigoSubserie == '' || $codigoSubserie == '00') && (strtoupper($nombreSerie) == $nombreSerie)) {

            //Pregunta si $numSerie ya existe en la base de datos
            $serie = $this->validateSerie($codigoSerie, $nombreSerie);

            if (!$serie) {

                $gdTrdSerie = new GdTrdSeries();
                $gdTrdSerie->codigoGdTrdSerie = trim($codigoSerie);
                $gdTrdSerie->nombreGdTrdSerie = utf8_encode(trim($nombreSerie));
                $datosBloque['serie'] = $gdTrdSerie;
                
                $idSerie = $datosBloque['serie']->idGdTrdSerie;

                // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
                $this->processNewSubserieNormal($modelCgTrd, $row, $idSerie, $datosBloque);
                
            } else {

                //Si ya existe el dato, se obtiene la informacion de la serie
                $datosBloque['serie'] = $serie;
                
                $idSerie = $datosBloque['serie']->idGdTrdSerie;

                // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
                $this->processNewSubserieNormal($modelCgTrd, $row, $idSerie, $datosBloque);
                //return $datosBloque;
            }
        }

        $idSerie = $datosBloque['serie']->idGdTrdSerie;

        ###############################################
        ########### PROCESO DE SUBSERIE  ##############

        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        if(isset($columnProcedimiento) && !empty($columnProcedimiento)){
            $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        }else{
            $procedimientoGdTrdSubserie = '';
        }
        if(isset($nombreSerie) && !empty($nombreSerie)){
            $nombreGdTrdSubserie = ucfirst(strtolower($nombreSerie));
        }else{
            $nombreGdTrdSubserie = '';
        }
        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Se valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Se valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        # Validación de datos de la subserie, codigo, nombre, dependencia y demas campos
        if ((!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (!is_null($codigoSubserie) || $codigoSubserie != '' && $codigoSubserie != '00') && (!is_null($columnAg) || $columnAg != '') && (!is_null($columnAc) || $columnAc != '') && (!is_null($columnProcedimiento) || $columnProcedimiento != '') && $disposicion == true  && $nombreGdTrdSubserie != '' ) {

            // Se realiza la validación de si la subserie que se esta leyendo ya existe en la tabla no se debe insertar
            $subserie = $this->validateSubserie($codigoSubserie, $nombreGdTrdSubserie, $idSerie);

            if (!$subserie) {
              
                $gdTrdSubserie = new GdTrdSubseries;
                $gdTrdSubserie->codigoGdTrdSubserie = $codigoSubserie;
                $gdTrdSubserie->nombreGdTrdSubserie = utf8_encode(trim($nombreGdTrdSubserie));
                $gdTrdSubserie->tiempoGestionGdTrdSubserie = $columnAg;
                $gdTrdSubserie->tiempoCentralGdTrdSubserie = $columnAc;
                $gdTrdSubserie->pSoporteGdTrdSubserie = $columnSoportePapel;
                $gdTrdSubserie->eSoporteGdTrdSubserie = $columnSoporteElectronico;
                $gdTrdSubserie->oSoporteGdTrdSubserie = $columnSoporteOtro;
                $gdTrdSubserie->ctDisposicionFinalGdTrdSubserie = $columnConsevacion;
                $gdTrdSubserie->eDisposicionFinalGdTrdSubserie = $columnEliminacion;
                $gdTrdSubserie->sDisposicionFinalGdTrdSubserie = $columnSeleccion;
                $gdTrdSubserie->mDisposicionFinalGdTrdSubserie = $columnMagnetico;
                $gdTrdSubserie->procedimientoGdTrdSubserie = utf8_encode($procedimientoGdTrdSubserie);
                $gdTrdSubserie->normaGdTrdSubserie = $columnNorma;
                $gdTrdSubserie->idGdTrdSerie = $idSerie;
                $datosBloque['subserie'] = $gdTrdSubserie;
                return true;
            } else {
                //Si ya existe el dato, solo se obtiene el id de la subserie                
                $datosBloque['subserie'] = $subserie;
                $datosBloque['subserie']->idGdTrdSerie = $idSerie;
                return true;
            }
            
        }
        return $datosBloque;
    }

    /**
     * Funcion que procesa la informacion de la creacion del tipo documental, o verifica la información
     * que viaja en el excel para esa subserie ya creada.
     **/
    public function processNewTiposdocumentales($modelCgTrd, $row, $datosBloque)
    {

        $nombre = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];

        // Cuando esta configurado la columna de días de termino quiere decir que se asignar el valor 
        // a todos los tipos documentales, si este no se configura se asigna 15 dias por defecto.
        if (!is_null($modelCgTrd->columnTipoDocCgTrd) && $modelCgTrd->columnTipoDocCgTrd != '') {
            $tipoDocumentalDias = $modelCgTrd->columnTipoDocCgTrd;
        } else {
            $tipoDocumentalDias = '15';
        }

        $nombre = ltrim($nombre, '0..9\.\*\-\●\,\°\+\#\"\:');
        $nombreTipoDoc = strtolower(trim($nombre));

        // Se valida si el nombre del tipo documental ya exxiste no se inserta
        $tipoDoc = $this->validateTipoDoc($nombreTipoDoc);

        // Sino existe el tipo documental, se crea
        if (!$tipoDoc) {

            $tiposDocumental = new GdTrdTiposDocumentales;
            $tiposDocumental->nombreTipoDocumental = utf8_encode(trim($nombreTipoDoc));
            $tiposDocumental->diasTramiteTipoDocumental = $tipoDocumentalDias;
            $datosBloque['tiposDocumental'] = $tiposDocumental;

            return $datosBloque['tiposDocumental'];

        } else {
            //Si ya existe el dato, solo se obtiene info del tipo documental
            $datosBloque['tiposDocumental'] = $tipoDoc;

            return $datosBloque['tiposDocumental'];
        }
    }

    /**
     * Funcion que procesa la informacion de la creacion de una nueva subserie, o verifica la información
     * que viaja en el excel para esa subserie ya creada.
     **/
    public function processNewSubserieNormal($modelCgTrd, $row, $numSerie, &$datosBloque, $codigoSubserie = null)
    {
        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        // Se valida si esta configurada la columna del código de la subserie, y que esta no llegue vacia
        if (!is_null($modelCgTrd->column3CodigoCgTrd) && $modelCgTrd->column3CodigoCgTrd != '') {
            $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
        } else {

            // Si esta información esta llegando directamente de la fila de la serie, quiere decir
            // que se debe crear la subserie con base al nombre de la serie. Y en el código asignarle 00
            if ($codigoSubserie == '' or $codigoSubserie == '00') {
                $codigoSubserie = '00';
            }

        }

        // Se lee el resto de los valores configurados para la subserie los mismos no pueden ser vacios para poder validar una subserie correctamente
        $nombreSubserie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        
        if(isset($columnProcedimiento) && !empty($columnProcedimiento)){
            $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        }else{
            $procedimientoGdTrdSubserie = '';
        }

        if(isset($nombreSubserie) && !empty($nombreSubserie)){
            $nombreGdTrdSubserie = ucfirst(strtolower(trim($nombreSubserie)));
        }else{
            $nombreGdTrdSubserie = '';
        }

        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Si valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Si valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        if ((!is_null($columnAg) || $columnAg) && (!is_null($columnAc) || $columnAc != '') && (!is_null($columnProcedimiento) || $columnProcedimiento != '') && $disposicion == true) {

            // Esto aplica para cuando llega la información de la subserie en la fila de la serie.
            // en este caso se envia el código de la serie para validarlo en la tabla subserie y asi guardarlo o validarlo
            // validateSubserie recibe el id de la serie
            $subserie = $this->validateSubserie($codigoSubserie, $nombreGdTrdSubserie, $numSerie);

            if (!$subserie) {

                ###################
                # Una vez ya esta toda la información validada y ajustada a como se necesita se proccede a insertar
                $gdTrdSubserie = new GdTrdSubseries;
                $gdTrdSubserie->codigoGdTrdSubserie = $codigoSubserie;
                $gdTrdSubserie->nombreGdTrdSubserie = $nombreGdTrdSubserie;
                $gdTrdSubserie->tiempoGestionGdTrdSubserie = $columnAg;
                $gdTrdSubserie->tiempoCentralGdTrdSubserie = $columnAc;
                $gdTrdSubserie->pSoporteGdTrdSubserie = $columnSoportePapel;
                $gdTrdSubserie->eSoporteGdTrdSubserie = $columnSoporteElectronico;
                $gdTrdSubserie->oSoporteGdTrdSubserie = $columnSoporteOtro;
                $gdTrdSubserie->ctDisposicionFinalGdTrdSubserie = $columnConsevacion;
                $gdTrdSubserie->eDisposicionFinalGdTrdSubserie = $columnEliminacion;
                $gdTrdSubserie->sDisposicionFinalGdTrdSubserie = $columnSeleccion;
                $gdTrdSubserie->mDisposicionFinalGdTrdSubserie = $columnMagnetico;
                $gdTrdSubserie->procedimientoGdTrdSubserie = $procedimientoGdTrdSubserie;
                $gdTrdSubserie->normaGdTrdSubserie = $columnNorma;
                $gdTrdSubserie->idGdTrdSerie = $numSerie;
                $datosBloque['subserie'] = $gdTrdSubserie;
                return true;
            } else {
                //Si ya existe el dato, solo se obtiene el id de la subserie
                $datosBloque['subserie'] = $subserie;
                $datosBloque['subserie']->idGdTrdSerie = $numSerie;
                return true;
            }
        }
        return false;
    }

    /**
     * TABLAS TEMPORALES
     * Funcion que procesa toda la información con las correspondientes validaciones de campos errados,
     * todo este proceso se realiza en el proceso de tablas Temporales
     **/
    public function trdsTemporales($dependencia, $nombreDependencia, $codigoDepePadre, $modelRegional, $modelDependencia, $hojaActualArray, $filaLeer, $modelCgTrd, $sheetNames, $indiceHoja, $filaDatos)
    {

        $cantRegistros = 0;
        $errosLoadTrd = [];

        // Se obtiene toda la informacion
        $datos = array_slice($hojaActualArray, $filaLeer);

        # Insertamos la dependencia acorde a los datos leidos del excel
        $gdTrdDependTmp = new GdTrdDependenciasTmp();
        $gdTrdDependTmp->codigoGdTrdDependenciaTmp = (string) $dependencia;
        $gdTrdDependTmp->nombreGdTrdDependenciaTmp = trim($nombreDependencia);
        $gdTrdDependTmp->codigoGdTrdDepePadreTmp = (string) $codigoDepePadre;
        $gdTrdDependTmp->idCgRegionalTmp = $modelRegional->idCgRegional;
        $gdTrdDependTmp->save();

        $response = [];
        $datosBloque = [];
        $resultadoTmp = [];

        $datosBloque['dependenciaTmp'] = $gdTrdDependTmp->idGdTrdDependenciaTmp;
        
        foreach ($datos as $i => $row) {

            # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza la TRD de la hoja
            $validateRow = $this->validateRow($row);

            //Valida que la fila no venga vacia
            if ($validateRow === true) {

                // Valida dato de la serie
                $codigoDependencia = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
                $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
                $nombreSerie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
                $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];

                if($codigoDependencia != '' && $codigoSerie != ''){
                    // Se quita del arreglo general la ultima subserie y tipo documental
                    // para que no quedaran pegados o asociados a los siguientes datos.
                    unset($datosBloque['subserieTmp']);
                    unset($datosBloque['tiposDocumentalTmp']); 
                }

                if($codigoDependencia != '' && $codigoSerie != '' && $codigoSubserie != ''){
                    // Se quita del arreglo general la ultima subserie y tipo documental
                    // para que no quedaran pegados o asociados a los siguientes datos.
                    unset($datosBloque['subserieTmp']);
                    unset($datosBloque['tiposDocumentalTmp']); 
                }

                // Apartir de aqui realiza las correspondientes validaciones

                ##################################################
                ### PROCESO DE SERIES DOCUMENTALES
                // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                if (((is_null($codigoDependencia) || $codigoDependencia == '') || (is_null($codigoSerie) || $codigoSerie == '')) && (strtoupper($nombreSerie) == $nombreSerie)) {
                    
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                    $saveDataValid = false;
                
                } 

                /** SE AGREGO HOY */
                // Se valida si el nombre de la serie supera la longitud permitida
                $longitudSerie = strlen($nombreSerie);

                if($longitudSerie > Yii::$app->params['longitudColumnas']['nombreGdTrdSerie']){
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') '. Yii::t('app', 'dataListErrTrdLongitudSr').' , ' . Yii::t('app', 'dataListErrTrdVerifique') . '' ;

                    $saveDataValid = false;
                }
                /** FIN SE AGREGO HOY */

                ##################################################
                ### PROCESO DE SUBSERIES DOCUMENTALES
                ### Valida datos de la subserie, si en la configuración se agrego una norma
                if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                    $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                }

                $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
                $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];

                if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                    $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                    $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                    // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                    if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                                            
                        if($codigoSubserie != '00' && (!is_null($codigoSubserie) && $codigoSubserie != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                            $saveDataValid = false;
                        }
                    }
                }

                $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                    $disposicion = null;
                } else {
                    $disposicion = 10;
                }

                // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                // Si alguno de los datos de estas columnas son vacios genera error de formato
                if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {

                    // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                    // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                    // si $codigoSubserie != '00' or $codigoSubserie != '000' se descarta las series entre a esta validación
                    if (($codigoSubserie != '00' || $codigoSubserie != '000') && (!is_null($codigoSubserie) && $codigoSubserie != '')) {

                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                        $saveDataValid = false;
                    }

                }
                // Si llega el código de la dependencia y tambien el cóidgo de la subserie pero no la serie error de formato
                elseif ((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSubserie) && $codigoSubserie != '') && (is_null($codigoSerie) && $codigoSerie == '')) {

                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                    $saveDataValid = false;
                } 
                
                /** SE AGREGO HOY */
                // Se valida si el nombre de la subserie supera la longitud permitida
                $longitudSubserie = strlen($nombreSerie);

                if($longitudSubserie > Yii::$app->params['longitudColumnas']['nombreGdTrdSubserie']){
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') '. Yii::t('app', 'dataListErrTrdLongitudSrSb').' , ' . Yii::t('app', 'dataListErrTrdVerifique') . '' ;

                    $saveDataValid = false;
                }
                /** FIN SE AGREGO HOY */

                //Procesa la información con base al archivo para crear los arreglos correspondientes
                // esto aplica para las SERIES y las SUBSERIES del archivo que se esta leyendo
                $processRow = $this->processRowNormalTmp($row, $datosBloque, $modelCgTrd, $dependencia, $codigoSerie);

                ##################################################
                ### PROCESO DE TIPOS DOCUMENTALES
                // Se valida que es un tipo documental si los campos de los códigos son vacios
                if ((is_null($codigoSerie) && $codigoSerie == '') && (is_null($codigoSubserie) && $codigoSubserie == '') && (is_null($codigoDependencia) && $codigoDependencia == '')) {

                    $tipos = $this->processNewTiposdocumentalesTmp($modelCgTrd, $row, $datosBloque);
 
                    /** SE AGREGO HOY */
                    if($tipos == false){
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') '. Yii::t('app', 'dataListErrTrdLongitudTP').' , ' . Yii::t('app', 'dataListErrTrdVerifique') . '' ;

                        $saveDataValid = false;
                    }else{
                    /** FIN SE AGREGO HOY */
                        $datosBloque['tiposDocumentalTmp'] = $tipos;
                    }
                    
                }

                if (isset($datosBloque['serieTmp']) && isset($datosBloque['subserieTmp']) && isset($datosBloque['tiposDocumentalTmp'])) {

                    $resultadoTmp = array_merge(
                        array_unique(array('serieTmp' => $datosBloque['serieTmp'])),
                        array_unique(array('subserieTmp' => $datosBloque['subserieTmp'])),
                        array_unique(array('tiposDocumentalTmp' => $datosBloque['tiposDocumentalTmp'])),
                        array_unique(array('dependenciaTmp' => $datosBloque['dependenciaTmp']))
                    );

                    //Almacena dependencia, serie, subserie y tipo documental
                    $guarda = $this->guardarBloqueTmp($resultadoTmp, $gdTrdDependTmp);
                    if(!$guarda == null){
                        if ($guarda['status'] == false) {
                            //$transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $guarda['errors'],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                                'datosBloque' => $guarda['datosBloque'], // Solo para debug
                                'model' => $guarda['model'], // Solo para debug
                                'row' => $row, // Solo para debug
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }
            }
            // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
            elseif ($validateRow === 2) {

                if (isset($datosBloque['serieTmp']) && isset($datosBloque['subserieTmp']) && isset($datosBloque['tiposDocumentalTmp'])) {

                    //Almacena dependencia, serie, subserie y tipo documental
                    //$guarda = $this->guardarBloqueTmp($datosBloque, $gdTrdDependTmp);
                }
                break; // terminar con el foreach
            }
        }

        if (isset($errosLoadTrd[$indiceHoja])) {
            return $errosLoadTrd[$indiceHoja];
        }
    }

    /**
     * TABLAS TEMPORALES
     * Funcion que procesa toda la información con las correspondientes validaciones de campos errados,
     * todo este proceso se realiza en el proceso de tablas Temporales
     * @param $length [Int][Longitud de la dependencia]
     **/
    public function trdsTemporalesMask($dependencia, $nombreDependencia, $codigoDepePadre, $modelRegional, $modelDependencia, $hojaActualArray, $filaLeer, $modelCgTrd, $sheetNames, $indiceHoja, $filaDatos, $modelMascara, $length)
    {

        $cantRegistros = 0;
        $errosLoadTrd = [];

        $mascara = $modelMascara->idCgTrdMascara;
        $separador = $modelMascara->separadorCgTrdMascara;
        $nombreSeparador = $modelMascara->nombreCgTrdMascara;

        // Se obtiene toda la informacion
        $datos = array_slice($hojaActualArray, $filaLeer);

        $errores = [];

        # Insertamos la dependencia acorde a los datos leidos del excel
        $gdTrdDependTmp = new GdTrdDependenciasTmp();
        $gdTrdDependTmp->codigoGdTrdDependenciaTmp = (string) $dependencia;
        $gdTrdDependTmp->nombreGdTrdDependenciaTmp = trim($nombreDependencia);
        $gdTrdDependTmp->codigoGdTrdDepePadreTmp = (int) $codigoDepePadre;
        $gdTrdDependTmp->idCgRegionalTmp = $modelRegional->idCgRegional;
        $gdTrdDependTmp->save();

        $response = [];
        $datosBloque = [];
        $resultadoTmp = [];

        $datosBloque['dependenciaTmp'] = $gdTrdDependTmp->idGdTrdDependenciaTmp;

        foreach ($datos as $i => $row) {

            /**
             * Se valida si en la configuración activa indica cual es el caracter de
             * separación para obtener la información de la dependencia, serie y subserie
             * si el separador es vacio y diferente a "Columnas separadas" se procede a
             * hacer un substring del códígo con la longitud de la dependencia.
             **/
            $nombreSerie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
            $codigoTrd = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
            $posicion = (int) strlen($codigoTrd) - (int) $length;
            $codigoDependencia = trim(substr($codigoTrd, 0, $length));
            $codigoSerie = trim(substr($codigoTrd, $length, $posicion));
            $codigoSubserie = '';

            if ($separador == '' && $mascara != Yii::$app->params['ConfiguracionMascara']) {
            } else {

                ######### TRATAMIENTO DE LOS DATOS DE LA SERIE, SUBSERIE Y DEPENDNCIA UNA SOLA COLUMNA
                if (!is_null($codigoTrd)) {

                    // Se realiza una busqueda e identificación del caracterer de separación configurado
                    // contra lo que llegue en la columna que se leea del excel ya que el código de la TRD
                    // viene en una sola columna.

                    # Caracterer de separación en la configuración
                    $buscaMascaraSeparacion = strpos($nombreSeparador, $separador);
                    # Caracteres de separación en la columna leida
                    $buscaCaracterSeparacion = strpos($codigoTrd, $separador);

                    // Si en la busqueda de caracter de separación en la configuración
                    // retorna true se sabe que se tiene que hacer tratamiento a la columna.
                    if ($buscaCaracterSeparacion === false && $buscaMascaraSeparacion === false) {} else {

                        // Se hace explode al valor configurado para compararlo con el explode del Codigo TRD
                        $explodedeMascara = explode($separador, $nombreSeparador);
                        // Se hace explode de la columna que se esta leyecto para identifica el código serie y dependencia
                        $explodedeCodigoTrd = explode($separador, $codigoTrd);

                        /**
                         * Se cuenta las posiciones que salieron al ejecutar el explode segun el separador
                         * configurado por el cliente para saber el código de la serie, subserie. Por lo menos
                         * se debe encontrar una posición.
                         **/
                        if (count($explodedeCodigoTrd) > 0 && count($explodedeMascara) > 0) {

                            // Si la separación del cóidgo es igual a dos solo se manejan dos posiciones
                            // quiere decir que se esta manejando una fila de la serie
                            // dd ss
                            if (count($explodedeCodigoTrd) == 2) {
                                
                                $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia                                                    
                                $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie

                                // Se quita del arreglo general la ultima subserie y tipo documental
                                // para que no quedaran pegados o asociados a los siguientes datos.
                                unset($datosBloque['subserieTmp']);
                                unset($datosBloque['tiposDocumentalTmp']);
                            }
                            // Si la separación es igual a 3 quiere decir que se esta leyendo la fila
                            // de una subserie dd ss sb 
                            else {
                                // Como se sabe que si llegan 3 posiciones en la fila ya es una subserie
                                // se procede a convertir en tipo titulo.
                                $nombreSerie = ucfirst(strtolower($nombreSerie));

                                $codigoDependencia = $explodedeCodigoTrd[0]; # Código de dependencia
                                if (isset($explodedeCodigoTrd[1])) {
                                    $codigoSerie = trim($explodedeCodigoTrd[1]); # Código de serie
                                    $codigoSubserie = trim($explodedeCodigoTrd[2]); # Código de subserie
                                }

                                // Se quita del arreglo general la ultima subserie y tipo documental
                                // para que no quedaran pegados o asociados a los siguientes datos.
                                unset($datosBloque['subserieTmp']);
                                unset($datosBloque['tiposDocumentalTmp']);
                            }

                        }
                        // Si la columna leida no tiene la misma cantidad de campos separados quiere decir
                        // error de formato
                        else {
                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdNoCumple') . ' (' . $nombreSeparador . ').';

                            $saveDataValid = false;
                        }
                    }
                }
            }

            # Valida cada fila para buscar la palabra Convenciones si la encuentra se finaliza la TRD de la hoja
            $validateRow = $this->validateRow($row);

            //Valida que la '.Yii::t('app', 'Fila') .' no venga vacia
            if ($validateRow === true) {

                // Apartir de aqui realiza las correspondientes validaciones

                ##################################################
                ### PROCESO DE SERIES DOCUMENTALES
                // Si alguno de los datos de estas columnas son vacios genera error de formato SERIE
                if ((is_null($codigoTrd) && $codigoTrd == '') && (strtoupper($nombreSerie) == $nombreSerie) || ($nombreSerie == '')) {

                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSerie') . '';

                    $saveDataValid = false;

                }

                /** SE AGREGO HOY */
                // Se valida si el nombre de la serie supera la longitud permitida
                $longitudSerie = strlen($nombreSerie);

                if($longitudSerie > Yii::$app->params['longitudColumnas']['nombreGdTrdSerie']){
                    $hojasNoProcesadas[] = $indiceHoja;
                    $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') . ' ' . ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . ') ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') '. Yii::t('app', 'dataListErrTrdLongitudSr').' , ' . Yii::t('app', 'dataListErrTrdVerifique') . '' ;

                    $saveDataValid = false;
                }
                /** FIN SE AGREGO HOY */
                
                ##################################################
                ### PROCESO DE SUBSERIES DOCUMENTALES
                ### Valida datos de la subserie, si en la configuración se agrego una norma
                if (!is_null($modelCgTrd->columnNormaCgTrd) || $modelCgTrd->columnNormaCgTrd != '') {
                    $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
                }

                $codigoSubserie = $codigoSubserie;
                $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
                $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
                $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
                $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

                if (!is_null($modelCgTrd->columnPSoporteCgTrd) || $modelCgTrd->columnPSoporteCgTrd != '') {
                    $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
                    $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

                    // Si hay configuración para la columna de soporte se valida que estas no esten vacias
                    if((is_null($columnSoportePapel) || $columnSoportePapel == '') && (is_null($columnSoporteElectronico) || $columnSoporteElectronico == '')){
                        
                        if((!is_null($codigoTrd) && $codigoTrd != '') && (strtoupper($nombreSerie) != $nombreSerie)){

                            $hojasNoProcesadas[] = $indiceHoja;
                            $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                            $saveDataValid = false;
                        }
                    }
                }                

                if (is_null($columnConsevacion) && is_null($columnEliminacion) && is_null($columnSeleccion) && is_null($columnMagnetico)) {
                    $disposicion = false;
                } else {
                    $disposicion = true;
                }

                // Primero se valida que las columnas de disposición, soporte, procedimiento y retención sean vacios todos
                // Si alguno de los datos de estas columnas son vacios genera error de formato
                if (((is_null($columnAg) && $columnAg == '') || (is_null($columnAc) || $columnAc == '') || (is_null($columnProcedimiento) || $columnProcedimiento == '') || is_null($disposicion)) && (strtoupper($nombreSerie) != $nombreSerie)) {

                    // Se descarta que sea la fila de una serie ya que tiene columna de subserie vacia o en 00
                    // si !is_null($codigoSubserie) se descarta los tipos documentales para esta validación
                    // si $codigoSubserie != '00' se descarta las series entre a esta validación
                    if ((!is_null($codigoTrd) && $codigoTrd != '') && ($codigoSubserie == '00' || $codigoSubserie == '000')  && (!is_null($codigoSubserie) && $codigoSubserie != '')) {
                                            
                        $hojasNoProcesadas[] = $indiceHoja;
                        $errosLoadTrd[$indiceHoja][] = '' . Yii::t('app', 'Hoja') .  ($indiceHoja + 1) . ': (' . $sheetNames[$indiceHoja] . '), ' . Yii::t('app', 'Fila') . ': (' . (($filaDatos + $i)) . ') ' . Yii::t('app', 'dataListErrTrdFaltaSubserie') . ' ' . Yii::t('app', 'dataListErrTrdVerifique') . '';

                        $saveDataValid = false;

                    }

                }

                //Procesa la información con base al archivo para crear los arreglos correspondientes
                $processRow = $this->processRowMaskTmp($row, $datosBloque, $modelCgTrd, $dependencia, $codigoSerie, $modelMascara, $codigoDependencia, $codigoSubserie);

                ##################################################
                ### PROCESO DE TIPOS DOCUMENTALES
                // Se valida que es un tipo documental si los campos de los códigos son vacios
                if (is_null($codigoTrd) && $codigoTrd == '') {

                    $tipos = $this->processNewTiposdocumentalesTmp($modelCgTrd, $row, $datosBloque);
                    $datosBloque['tiposDocumentalTmp'] = $tipos;
                    
                }

                if (isset($datosBloque['serieTmp']) && isset($datosBloque['subserieTmp']) && isset($datosBloque['tiposDocumentalTmp'])) {

                    $resultadoTmp = array_merge(
                        array_unique(array('serieTmp' => $datosBloque['serieTmp'])),
                        array_unique(array('subserieTmp' => $datosBloque['subserieTmp'])),
                        array_unique(array('tiposDocumentalTmp' => $datosBloque['tiposDocumentalTmp'])),
                        array_unique(array('dependenciaTmp' => $datosBloque['dependenciaTmp']))
                    );

                    //Almacena dependencia, serie, subserie y tipo documental
                    $guarda = $this->guardarBloqueTmp($resultadoTmp, $gdTrdDependTmp);

                }
            }
            // Al finalizar la lectura del formato, se valida si hay información en $datosBloque
            elseif ($validateRow === 2) {

                if (isset($datosBloque['serieTmp']) && isset($datosBloque['subserieTmp']) && isset($datosBloque['tiposDocumentalTmp'])) {

                    //Almacena dependencia, serie, subserie y tipo documental
                    $guarda = $this->guardarBloqueTmp($datosBloque, $gdTrdDependTmp);
                }
                break; // terminar con el foreach
            }
        }

        if (isset($errosLoadTrd[$indiceHoja])) {
            return $errosLoadTrd[$indiceHoja];
        }
    }

    /**
     * TABLAS TEMPORALES
     * Funcion que procesa la informacion de la serie, subserie con el fin de identificar si se debe crear la información o
     * solo consultarla y asignarla al arreglo para ser procesada.
     **/
    public function processRowNormalTmp($row, &$datosBloque, $modelCgTrd, $dependencia, $numSerie)
    {

        ###############################################
        # PROCESO DE SERIE

        // Valida dato de la serie con base a la columna configurada para la lectura
        $nombreSerie = trim(strtoupper($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]));
        $codigoDependencia = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
        $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];

        if (!is_null($modelCgTrd->column2CodigoCgTrd)) {
            $codigoSerie = $row[$this->numberByLetter($modelCgTrd->column2CodigoCgTrd)];
            $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
        }

        // Se sabe que es serie porque tiene el código dependencia, serie y el nombre en MAYÚSCULA
        // se valida que las columnas correspondientes a la serie no lleguen vacias y que la columna de Código subserie esta vacia
        // o sea igual 00.
        if ((!is_null($nombreSerie) || $nombreSerie != '') && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (is_null($codigoSubserie) || $codigoSubserie == '' || $codigoSubserie == '00') && (strtoupper($nombreSerie) == $nombreSerie)) {

            ######
            ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES

            $gdTrdSerieTmp = new GdTrdSeriesTmp();
            $gdTrdSerieTmp->codigoGdTrdSerieTmp = (string) trim($numSerie);
            $gdTrdSerieTmp->nombreGdTrdSerieTmp = (string) trim($nombreSerie);
            $datosBloque['serieTmp'] = $gdTrdSerieTmp;

            $idSerie = $datosBloque['serieTmp']->idGdTrdSerieTmp;

            // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
            $this->processNewSubserieNormalTmp($modelCgTrd, $row, $idSerie, $datosBloque);
            return true;
        }

        $idSerie = $datosBloque['serieTmp']->idGdTrdSerieTmp;

        ###############################################
        #PROCESO DE SUBSERIE

        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        $nombreGdTrdSubserie = ucfirst(strtolower(trim($nombreSerie)));

        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Se valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Se valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        //return true;
        
        if ((!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (!is_null($codigoSubserie) || $codigoSubserie != '' && $codigoSubserie != '00') && (!is_null($columnAg) || $columnAg != '') && (!is_null($columnAc) || $columnAc != '') && (!is_null($columnProcedimiento) || $columnProcedimiento != '') && $disposicion == true) {
            
            if ($codigoSubserie != '' && $codigoSubserie != '00') {

                ######
                ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES

                ###################
                # Una vez ya esta toda la información validada y ajustada a como se necesita se proccede a insertar
                $gdTrdSubserieTmp = new GdTrdSubseriesTmp;
                $gdTrdSubserieTmp->codigoGdTrdSubserieTmp = (string) trim($codigoSubserie);
                $gdTrdSubserieTmp->nombreGdTrdSubserieTmp = (string) trim($nombreGdTrdSubserie);
                $gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp = $columnAg;
                $gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp = $columnAc;
                $gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp = $columnSoportePapel;
                $gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp = $columnSoporteElectronico;
                $gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp = $columnSoporteOtro;
                $gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp = $columnConsevacion;
                $gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp = $columnEliminacion;
                $gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp = $columnSeleccion;
                $gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp = $columnMagnetico;
                $gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp = (string) trim($procedimientoGdTrdSubserie);
                $gdTrdSubserieTmp->normaGdTrdSubserieTmp = $columnNorma;
                $gdTrdSubserieTmp->idGdTrdSerieTmp = $idSerie;
                $datosBloque['subserieTmp'] = $gdTrdSubserieTmp;
                return true;
            }
        }

        return false;
    }

    /**º
     * TABLAS TEMPORALES
     * Funcion que procesa la informacion de la creacion del tipo documental, o verifica la información
     * que viaja en el excel para esa subserie ya creada. Todo este proceso se realiza en el proceso de tablas Temporales
     **/
    public function processNewTiposdocumentalesTmp($modelCgTrd, $row, $datosBloque)
    {

        $nombre = $row[$this->numberByLetter(trim($modelCgTrd->columnNombreCgTrd))];

        // Cuando esta configurado la columna de días de termino quiere decir que se asignar el valor 
        // a todos los tipos documentales, si este no se configura se asigna 15 dias por defecto.
        if (!is_null($modelCgTrd->columnTipoDocCgTrd) && $modelCgTrd->columnTipoDocCgTrd != '') {
            $tipoDocumentalDias = $modelCgTrd->columnTipoDocCgTrd;
        } else {
            $tipoDocumentalDias = '15';
        }

        $nombre = ltrim($nombre, '0..9\.\*\-\●\,\°\+\#\"\:');
        $nombreTipoDoc = strtolower(trim($nombre));

        ######
        ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES
        $tiposDocumentalTmp = new GdTrdTiposDocumentalesTmp;
        $tiposDocumentalTmp->nombreTipoDocumentalTmp = $nombreTipoDoc;
        $tiposDocumentalTmp->diasTramiteTipoDocumentalTmp = $tipoDocumentalDias;
        $datosBloque['tiposDocumentalTmp'] = $tiposDocumentalTmp;

        return $datosBloque['tiposDocumentalTmp'];
            
    }

    /**
     * TABLAS TEMPORALES
     * Funcion que procesa la informacion de la creacion de una nueva subserie, o verifica la información
     * que viaja en el excel para esa subserie ya creada. Todo este proceso se realiza en el proceso de tablas Temporales
     **/
    public function processNewSubserieNormalTmp($modelCgTrd, $row, $numSerie, &$datosBloque, $codigoSubserie = null)
    {
        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        // Se valida si esta configurada la columna del código de la subserie, y que esta no llegue vacia
        if (!is_null($modelCgTrd->column3CodigoCgTrd) && $modelCgTrd->column3CodigoCgTrd != '') {
            $codigoSubserie = $row[$this->numberByLetter($modelCgTrd->column3CodigoCgTrd)];
        } else {

            // Si esta información esta llegando directamente de la fila de la serie, quiere decir
            // que se debe crear la subserie con base al nombre de la serie. Y en el código asignarle 00
            if ($codigoSubserie == '' or $codigoSubserie == '00') {
                $codigoSubserie = '00';
            }

        }

        // Se lee el resto de los valores configurados para la subserie los mismos no pueden ser vacios para poder validar una subserie correctamente
        $nombreSubserie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        $nombreGdTrdSubserie = ucfirst(strtolower(trim($nombreSubserie)));

        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Si valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Si valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        if ((!is_null($columnAg) || $columnAg) && (!is_null($columnAc) || $columnAc != '') && (!is_null($columnProcedimiento) || $columnProcedimiento != '') && $disposicion == true) {

            if ($codigoSubserie == '') {$codigoSubserie = '00';}

            ######
            ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES

            ###################
            # Una vez ya esta toda la información validada y ajustada a como se necesita se proccede a insertar
            $gdTrdSubserieTmp = new GdTrdSubseriesTmp;
            $gdTrdSubserieTmp->codigoGdTrdSubserieTmp = $codigoSubserie;
            $gdTrdSubserieTmp->nombreGdTrdSubserieTmp = $nombreGdTrdSubserie;
            $gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp = $columnAg;
            $gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp = $columnAc;
            $gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp = $columnSoportePapel;
            $gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp = $columnSoporteElectronico;
            $gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp = $columnSoporteOtro;
            $gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp = $columnConsevacion;
            $gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp = $columnEliminacion;
            $gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp = $columnSeleccion;
            $gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp = $columnMagnetico;
            $gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp = $procedimientoGdTrdSubserie;
            $gdTrdSubserieTmp->normaGdTrdSubserieTmp = $columnNorma;
            $gdTrdSubserieTmp->idGdTrdSerieTmp = $numSerie;
            $datosBloque['subserieTmp'] = $gdTrdSubserieTmp;
            return true;

        }
        return false;
    }

    //Funcion para convertir las letras en numeros
    public function numberByLetter($letter)
    {

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
            'L' => 11,
            'M' => 12,
            'N' => 13,
            'O' => 14,
            'P' => 15,
        );

        return $letterArray[$letter];
    }

    /**
     * Funcion que procesa la informacion de la serie, subserie con el fin de identificar si se debe crear la información o
     * solo consultarla y asignarla al arreglo para ser procesada. Esta función se ejecuta cuando la mascara de separación
     * del código de la trd, es diferentes a columnas independientes.
     **/
    public function processRow($row, &$datosBloque, $modelCgTrd, $modelMascara, $dependencia, $codigoDependencia, $codigoSerie, $codigoSubserie)
    {

        ###############################################
        # PROCESO DE SERIE

        //Se obtiene el codigo de cada fila
        $codigoRow = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
        $nombreSerie = trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]);
        $codigoDependencia = $codigoDependencia;
        $codigoSerie = $codigoSerie;
        $codigoSubserie = $codigoSubserie;

        // Se valida que si llegan las tres valores de Serie, Subserie y Dependencia se convierte el texto 
        // a tipo oración con el fin de tratarlo como subserie y no como serie.
        if((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSerie) && $codigoSerie != '') && (!is_null($codigoSubserie) && $codigoSubserie != '')){
            $nombreSerie = ucfirst(strtolower($nombreSerie));
        }
        
        // Se sabe que es serie porque tiene el código dependencia, serie y el nombre en MAYÚSCULA
        // se valida que las columnas correspondientes a la serie no lleguen vacias y que la columna de Código subserie esta vacia
        if ((!is_null($nombreSerie) && $nombreSerie != '') && !is_null($codigoRow) && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (strtoupper($nombreSerie) == $nombreSerie)) {

            //Pregunta si $numSerie ya existe en la base de datos
            $serie = $this->validateSerieCodigo($codigoSerie, $nombreSerie);

            if (!$serie) {

                $gdTrdSerie = new GdTrdSeries();
                $gdTrdSerie->codigoGdTrdSerie = $codigoSerie;
                $gdTrdSerie->nombreGdTrdSerie = trim($nombreSerie);
                $datosBloque['serie'] = $gdTrdSerie;

                $idSerie = $datosBloque['serie']->idGdTrdSerie;

                // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
                $this->processNewSubserieNormal($modelCgTrd, $row, $idSerie, $datosBloque);
                return true;
            } else {

                //Si ya existe el dato, se obtiene la informacion de la serie
                $datosBloque['serie'] = $serie;
                
                $idSerie = $datosBloque['serie']->idGdTrdSerie;
                
                // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
                $this->processNewSubserieNormal($modelCgTrd, $row, $idSerie, $datosBloque);
                return true;
            }
            //    return $codigoRow;
        }

        $idSerie = $datosBloque['serie']->idGdTrdSerie;

        ###############################################
        #PROCESO DE SUBSERIE

        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        $nombreSerie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        $nombreGdTrdSubserie = ucfirst(strtolower(trim($nombreSerie)));

        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Se valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Se valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        if (!is_null($codigoRow) && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (!is_null($codigoSubserie) || $codigoSubserie != '') && !is_null($columnAg) && !is_null($columnAc) && !is_null($columnProcedimiento) && $disposicion == true) {

            // Se realiza la validación de si la subserie que se esta leyendo ya existe en la tabla no se debe insertar
            $subserie = $this->validateSubserie($codigoSubserie, $nombreGdTrdSubserie, $idSerie);

            if (!$subserie) {

                ###################
                # Una vez ya esta toda la información validada y ajustada a como se necesita se proccede a insertar
                $gdTrdSubserie = new GdTrdSubseries;
                $gdTrdSubserie->codigoGdTrdSubserie = $codigoSubserie;
                $gdTrdSubserie->nombreGdTrdSubserie = $nombreGdTrdSubserie;
                $gdTrdSubserie->tiempoGestionGdTrdSubserie = $columnAg;
                $gdTrdSubserie->tiempoCentralGdTrdSubserie = $columnAc;
                $gdTrdSubserie->pSoporteGdTrdSubserie = $columnSoportePapel;
                $gdTrdSubserie->eSoporteGdTrdSubserie = $columnSoporteElectronico;
                $gdTrdSubserie->oSoporteGdTrdSubserie = $columnSoporteOtro;
                $gdTrdSubserie->ctDisposicionFinalGdTrdSubserie = $columnConsevacion;
                $gdTrdSubserie->eDisposicionFinalGdTrdSubserie = $columnEliminacion;
                $gdTrdSubserie->sDisposicionFinalGdTrdSubserie = $columnSeleccion;
                $gdTrdSubserie->mDisposicionFinalGdTrdSubserie = $columnMagnetico;
                $gdTrdSubserie->procedimientoGdTrdSubserie = $procedimientoGdTrdSubserie;
                $gdTrdSubserie->normaGdTrdSubserie = $columnNorma;
                $gdTrdSubserie->idGdTrdSerie = $idSerie;
                $datosBloque['subserie'] = $gdTrdSubserie;
                return true;
            } else {

                //Si ya existe el dato, solo se obtiene el id de la subserie                
                $datosBloque['subserie'] = $subserie;
                $datosBloque['subserie']->idGdTrdSerie = $idSerie;
                return true;
            }
        }
        return $datosBloque;
    }

    /**
     * TABLAS TEMPORALES
     * Guarda la información que almaceno la variable $datosBloque. para las tablas temporales
     **/
    public function processRowMaskTmp($row, &$datosBloque, $modelCgTrd, $dependencia, $codigoSerie, $modelMascara, $codigoDependencia, $codigoSubserie)
    {

        ###############################################
        # PROCESO DE SERIE

        //Se obtiene el codigo de cada fila
        $codigoRow = $row[$this->numberByLetter($modelCgTrd->columnCodigoCgTrd)];
        $nombreSerie = trim(($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)]));
        $codigoDependencia = $codigoDependencia;
        $codigoSerie = $codigoSerie;
        $codigoSubserie = $codigoSubserie;

        // Se valida que si llegan las tres valores de Serie, Subserie y Dependencia se convierte el texto 
        // a tipo oración con el fin de tratarlo como subserie y no como serie.
        if((!is_null($codigoDependencia) && $codigoDependencia != '') && (!is_null($codigoSerie) && $codigoSerie != '') && (!is_null($codigoSubserie) && $codigoSubserie != '')){
            $nombreSerie = ucfirst(strtolower($nombreSerie));
        }

        // Valida dato de la serie
        // Se sabe que es una serie porque debe llegar el código de la dependencia, serie y el nombre en MAYÚSCULA
        if ((!is_null($nombreSerie) && $nombreSerie != '') && !is_null($codigoRow) && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (strtoupper($nombreSerie) == $nombreSerie)) {

            ######
            ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES

            $gdTrdSerieTmp = new GdTrdSeriesTmp();
            $gdTrdSerieTmp->codigoGdTrdSerieTmp = $codigoSerie;
            $gdTrdSerieTmp->nombreGdTrdSerieTmp = $nombreSerie;
            $datosBloque['serieTmp'] = $gdTrdSerieTmp;

            $idSerie = $datosBloque['serieTmp']->idGdTrdSerieTmp;

            // Proceso para crear la nueva subserie, cuando los datos se encuentran en la fila de la serie
            $this->processNewSubserieNormalTmp($modelCgTrd, $row, $idSerie, $datosBloque, $codigoSubserie);
            return true;
        }

        $idSerie = $datosBloque['serieTmp']->idGdTrdSerieTmp;

        ###############################################
        #PROCESO DE SUBSERIE

        // Valida datos de la subserie, valida si se ha configurado una norma a leer
        if (!is_null($modelCgTrd->columnNormaCgTrd) && $modelCgTrd->columnNormaCgTrd != '') {
            $columnNorma = $row[$this->numberByLetter($modelCgTrd->columnNormaCgTrd)];
        } else {
            $columnNorma = null;
        }

        // Se valida si se ha configurado el campo de soporte, este es configurado
        // se debe validar que es obligatorio indicar por lo menos soporte papel o
        // electronicó.
        if (!is_null($modelCgTrd->columnPSoporteCgTrd) && $modelCgTrd->columnPSoporteCgTrd != '') {
            $columnSoportePapel = $row[$this->numberByLetter($modelCgTrd->columnPSoporteCgTrd)];
            $columnSoporteElectronico = $row[$this->numberByLetter($modelCgTrd->columnESoporteCgTrd)];

            // Como solo es obligatorio que llegue por lo menos un los datos de soporte
            // y con esta validación se indica si la subserie es valida.
            if (!is_null($modelCgTrd->columnOsoporteCgTrd) && $modelCgTrd->columnOsoporteCgTrd != '') {
                $columnSoporteOtro = $row[$this->numberByLetter($modelCgTrd->columnOsoporteCgTrd)];
            } else {
                $columnSoporteOtro = null;
            }

        } else {
            $columnSoportePapel = null;
            $columnSoporteElectronico = null;
            $columnSoporteOtro = null;
        }

        $nombreSerie = $row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)];
        //$codigoSubserie = $codigoSubserie;
        $columnAg = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $columnAc = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $columnConsevacion = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
        $columnEliminacion = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
        $columnSeleccion = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
        $columnMagnetico = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
        $columnProcedimiento = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];
        $procedimientoGdTrdSubserie = ucfirst(strtolower($columnProcedimiento));
        $nombreGdTrdSubserie = ucfirst(strtolower(trim($nombreSerie)));

        # Se valida que por lo menos llegue una de las columnas asignadas a la disposición final
        if ((!is_null($columnEliminacion) && $columnEliminacion != '') || (!is_null($columnConsevacion) && $columnConsevacion != '') || (!is_null($columnSeleccion) && $columnSeleccion != '') || (!is_null($columnMagnetico) && $columnMagnetico != '')) {
            $disposicion = true;
        } else {
            $disposicion = false;
        }

        ###################
        # Se valida si en las celdas viaja una 'x' será igual a 10
        if ($columnConsevacion == null) {$columnConsevacion = 0;} else { $columnConsevacion = 10;}
        if ($columnEliminacion == null) {$columnEliminacion = 0;} else { $columnEliminacion = 10;}
        if ($columnSeleccion == null) {$columnSeleccion = 0;} else { $columnSeleccion = 10;}
        if ($columnMagnetico == null) {$columnMagnetico = 0;} else { $columnMagnetico = 10;}

        ###################
        # Se valida si en las celdas de soporte viaja una 'x' será igual a 10
        if ($columnSoportePapel == null || !isset($columnSoportePapel)) {$columnSoportePapel = 0;} else { $columnSoportePapel = 10;}
        if ($columnSoporteElectronico == null || !isset($columnSoporteElectronico)) {$columnSoporteElectronico = 0;} else { $columnSoporteElectronico = 10;}
        if ($columnSoporteOtro == null || !isset($columnSoporteOtro)) {$columnSoporteOtro = 0;} else { $columnSoporteOtro = 10;}

        if (!is_null($codigoRow) && (!is_null($codigoDependencia) || $codigoDependencia != '') && (!is_null($codigoSerie) || $codigoSerie != '') && (!is_null($codigoSubserie) || $codigoSubserie != '') && !is_null($columnAg) && !is_null($columnAc) && !is_null($columnProcedimiento) && $disposicion == true) {

            ######
            ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES

            ###################
            # Una vez ya esta toda la información validada y ajustada a como se necesita se proccede a insertar
            $gdTrdSubserieTmp = new GdTrdSubseriesTmp;
            $gdTrdSubserieTmp->codigoGdTrdSubserieTmp = $codigoSubserie;
            $gdTrdSubserieTmp->nombreGdTrdSubserieTmp = $nombreGdTrdSubserie;
            $gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp = $columnAg;
            $gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp = $columnAc;
            $gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp = $columnSoportePapel;
            $gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp = $columnSoporteElectronico;
            $gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp = $columnSoporteOtro;
            $gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp = $columnConsevacion;
            $gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp = $columnEliminacion;
            $gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp = $columnSeleccion;
            $gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp = $columnMagnetico;
            $gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp = $procedimientoGdTrdSubserie;
            $gdTrdSubserieTmp->normaGdTrdSubserieTmp = $columnNorma;
            $gdTrdSubserieTmp->idGdTrdSerieTmp = $idSerie;
            $datosBloque['subserieTmp'] = $gdTrdSubserieTmp;
            return true;
        }
        return $datosBloque;
    }

    /**
     * Funcion que procesa la informacion de la creacion de una nueva subserie, o verifica la información
     * que viaja en el excel para esa subserie ya creada.
     * */
    public function processNewSubserie($modelCgTrd, $row, $numSerie, $modelMascara, &$datosBloque, $numSubserie = null)
    {

        //Datos primordiales de la subserie
        $ag = $row[$this->numberByLetter($modelCgTrd->columnAgCgTrd)];
        $ac = $row[$this->numberByLetter($modelCgTrd->columnAcCgTrd)];
        $process = $row[$this->numberByLetter($modelCgTrd->columnProcessCgTrd)];

        if ($numSubserie == null) {
            $numSubserie = 99;
        }

        $nombreSubserie = ucfirst(strtolower(trim($row[$this->numberByLetter($modelCgTrd->columnNombreCgTrd)])));
        $newSubserie = $numSerie . $modelMascara->separadorCgTrdMascara . $numSubserie;

        //Pregunta si $numSubserie ya existe en la base de datos
        $subserie = $this->validateSubserie($newSubserie, $nombreSubserie, $numSerie);

        if (!$subserie) {

            // Se valida que haya informacion en las siguientes columnas
            if (!is_null($ag) && $ag != '' && !is_null($ac) && $ac != '' && !is_null($process) && $process != '') {

                $gdTrdSubserie = new GdTrdSubseries();

                $gdTrdSubserie->codigoGdTrdSubserie = $newSubserie;
                $gdTrdSubserie->nombreGdTrdSubserie = $nombreSubserie;
                $gdTrdSubserie->tiempoGestionGdTrdSubserie = $ag;
                $gdTrdSubserie->tiempoCentralGdTrdSubserie = $ac;

                // Si valida si en las celdas viaja una 'x' será igual a 10
                if ($row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)] == null) {
                    $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)] = 0;
                } else {
                    $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)] = 10;
                }

                if ($row[$this->numberByLetter($modelCgTrd->columnECgTrd)] == null) {
                    $row[$this->numberByLetter($modelCgTrd->columnECgTrd)] = 0;
                } else {
                    $row[$this->numberByLetter($modelCgTrd->columnECgTrd)] = 10;
                }

                if ($row[$this->numberByLetter($modelCgTrd->columnSCgTrd)] == null) {
                    $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)] = 0;
                } else {
                    $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)] = 10;
                }

                if ($row[$this->numberByLetter($modelCgTrd->columnMCgTrd)] == null) {
                    $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)] = 0;
                } else {
                    $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)] = 10;
                }

                $gdTrdSubserie->ctDisposicionFinalGdTrdSubserie = $row[$this->numberByLetter($modelCgTrd->columnCtCgTrd)];
                $gdTrdSubserie->eDisposicionFinalGdTrdSubserie = $row[$this->numberByLetter($modelCgTrd->columnECgTrd)];
                $gdTrdSubserie->sDisposicionFinalGdTrdSubserie = $row[$this->numberByLetter($modelCgTrd->columnSCgTrd)];
                $gdTrdSubserie->mDisposicionFinalGdTrdSubserie = $row[$this->numberByLetter($modelCgTrd->columnMCgTrd)];
                $gdTrdSubserie->procedimientoGdTrdSubserie = ucfirst(strtolower($process));

                $datosBloque['subserie'] = $gdTrdSubserie;

                if (!isset($datosBloque['datosSubserie'])) {
                    $datosBloque['datosSubserie'] = $gdTrdSubserie;
                }

                return true;

            } else {
                return false;
            }

        } else {

            //Si ya existe el dato, solo se obtiene el id de la subserie
            $datosBloque['subserie'] = $subserie;

            return true;
        }
    }

    /**
     * Guarda la información que almaceno la variable $datosBloque.
     **/
    public function guardarBloque($datosBloque, $gdTrdDepend ,$uniqid)
    {

        $errores = [];
        $modelsWithError = []; // Modelos que generan errores // Solo para debug
        $status = true;
        if (!is_null($datosBloque)) {

            //Se guarda los datos obtenidos en cada tabla
            $ids = [];

            /** Validación de serie */
            if (is_object($datosBloque['serie'])) {
                # Validar que se guarden los datos de la serie
                if (!$datosBloque['serie']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['serie']->getErrors());
                    $modelsWithError[] = 'Serie';
                }
            }
            $ids['series'] = $datosBloque['serie']->idGdTrdSerie;
            /** Fin Validación de serie */

            /** Validación de subserie */
            if (is_object($datosBloque['subserie'])) {
                if ($datosBloque['subserie']->idGdTrdSerie == null || $datosBloque['subserie']->idGdTrdSerie == false) {
                    # Se asigna el ID de la serie a la tabla subserie
                    $datosBloque['subserie']->idGdTrdSerie = $ids['series'];
                } else {
                    # Se valida si El Id de la serie es distinto al id de la subserie
                    if ($datosBloque['subserie']->idGdTrdSerie != $ids['series']) {
                        $status = false;
                        $errores = array_merge($errores, ['error' => [Yii::t('app', 'idSerieDistintoSudserie')]]);
                        $modelsWithError[] = 'Subserie';
                    }
                }

                # Validar que se guarden los datos de la subserie
                if (!$datosBloque['subserie']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['subserie']->getErrors());
                    $modelsWithError[] = 'Subserie';
                }
            }
            $ids['subseries'] = $datosBloque['subserie']->idGdTrdSubserie;
            /** Fin Validación de subserie */

            /** Validación de tipo documental */
            $tipoDocumento = $this->validateTipoDoc($datosBloque['tiposDocumental']->nombreTipoDocumental);

            if (!$tipoDocumento) {

                if (is_object($datosBloque['tiposDocumental'])) {

                    # Validar que se guarden los datos de tipo documental
                    if (!$datosBloque['tiposDocumental']->save()) {
                        $status = false;
                        $idTipoDocumental = null;
                        $errores = array_merge($errores, $datosBloque['tiposDocumental']->getErrors());
                        $modelsWithError[] = 'tiposDocumental';
                    } else {
                        $idTipoDocumental = $datosBloque['tiposDocumental']->idGdTrdTipoDocumental; // Tipo documental guardado
                    }

                } else {
                    $idTipoDocumental = $datosBloque['tiposDocumental']->idGdTrdTipoDocumental; // No era un objeto o modelo guardar (si no existe, responde null)
                }
            } else {
                $idTipoDocumental = $tipoDocumento->idGdTrdTipoDocumental; // Tipo documental existente
            }
            /** Fin Validación de tipo documental */

            /** Validar si hubo algún inconveniente al ingresar la serie, subserie o tipo documental, y retornar el array de errores */
            if ($status == false) {
                return [
                    'status' => false,
                    'errors' => $errores,
                    'datosBloque' => $datosBloque, // Solo para debug
                    'model' => 'tiposDocumental', // Solo para debug
                ];
            }
            /** Fin Validar si hubo algún inconveniente al ingresar la serie, subserie o tipo documental, y retornar el array de errores */

            // SI todos los ids de las series llegan se procede a insertar en las TRD de lo contrario no se guarda.
            if (
                !is_null($ids['series']) && !is_null($ids['subseries']) 
                && ((int) $datosBloque['dependencia'] != 0)
                && !is_null($idTipoDocumental)
            ) {

                $gdTrd = GdTrd::find()
                    ->where(['idGdTrdDependencia' => $datosBloque['dependencia']])
                    ->andWhere(['idGdTrdSerie' => $ids['series']])
                    ->andWhere(['idGdTrdSubserie' => $ids['subseries']])
                    ->andWhere(['idGdTrdTipoDocumental' => $idTipoDocumental])
                ->one();

                if ($gdTrd == null) {
                    $gdTrd = new GdTrd;
                    $gdTrd->versionGdTrd = 1;
                    $gdTrd->identificadorUnicoGdTrd = $uniqid;
                    $gdTrd->idGdTrdDependencia = $datosBloque['dependencia'];
                    $gdTrd->idGdTrdSerie = $ids['series'];
                    $gdTrd->idGdTrdSubserie = $ids['subseries'];
                    $gdTrd->idGdTrdTipoDocumental = $idTipoDocumental;

                    //Se almacena la relacion entre dependencia, serie, subserie y tipo documental
                    if (!$gdTrd->save()) {
                        return [
                            'status' => false,
                            'errors' => $gdTrd->getErrors(),
                            'datosBloque' => $datosBloque, // Solo para debug
                            'model' => 'GdTrd', // Solo para debug
                        ];
                    } else {
                        return [
                            'status' => true,
                            'errors' => [],
                            'datosBloque' => $datosBloque, // Solo para debug
                            'model' => 'gdTrd Se guardó correctamente', // Solo para debug
                        ];
                    }
                } else {
                    return [
                        'status' => true,
                        'errors' => [],
                        'datosBloque' => $datosBloque, // Solo para debug
                        'model' => 'Ya existe la TRD', // Solo para debug
                    ];
                }

            } else {
                return [
                    'status' => true,
                    'errors' => [],
                    'datosBloque' => $datosBloque, // Solo para debug
                    'model' => 'GdTrd sin guardar por datos incompletos', // Solo para debug
                ];
            }

        } else {
            return [
                'status' => false,
                'errors' => ['error' => [Yii::t('app', 'noDataToSave')]],
                'datosBloque' => $datosBloque, // Solo para debug
                'model' => 'No hay datos a guardar', // Solo para debug
            ];
        }
    }


    /**
     * Guarda la información que almaceno la variable $datosBloque.
     **/
    public function guardarBloqueMask($datosBloque, $gdTrdDepend ,$uniqid)
    {

        $errores = [];
        $modelsWithError = []; // Modelos que generan errores // Solo para debug
        $status = true;
        if (!is_null($datosBloque)) {

            //Se guarda los datos obtenidos en cada tabla
            $ids = [];

            /** Validación de serie */
            if (is_object($datosBloque['serie'])) {
                # Validar que se guarden los datos de la serie
                if (!$datosBloque['serie']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['serie']->getErrors());
                    $modelsWithError[] = 'Serie';
                }
            }
            $ids['series'] = $datosBloque['serie']->idGdTrdSerie;

            /** Validación de subserie */
            if (is_object($datosBloque['subserie'])) {
                if ($datosBloque['subserie']->idGdTrdSerie == null || $datosBloque['subserie']->idGdTrdSerie == false) {
                    # Se asigna el ID de la serie a la tabla subserie
                    $datosBloque['subserie']->idGdTrdSerie = $ids['series'];
                } else {
                    # Se valida si El Id de la serie es distinto al id de la subserie
                    if ($datosBloque['subserie']->idGdTrdSerie != $ids['series']) {
                        $status = false;
                        $errores = array_merge($errores, ['error' => [Yii::t('app', 'idSerieDistintoSudserie')]]);
                        $modelsWithError[] = 'Subserie';
                    }
                }

                # Validar que se guarden los datos de la subserie
                if (!$datosBloque['subserie']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['subserie']->getErrors());
                    $modelsWithError[] = 'Subserie';
                }
            }
            $ids['subseries'] = $datosBloque['subserie']->idGdTrdSubserie;

            /** Validación de tipo documental */
            $tipoDocumento = $this->validateTipoDoc($datosBloque['tiposDocumental']->nombreTipoDocumental);

            if (!$tipoDocumento) {

                if (is_object($datosBloque['tiposDocumental'])) {

                    # Validar que se guarden los datos de tipo documental
                    if (!$datosBloque['tiposDocumental']->save()) {
                        $status = false;
                        $idTipoDocumental = null;
                        $errores = array_merge($errores, $datosBloque['tiposDocumental']->getErrors());
                        $modelsWithError[] = 'tiposDocumental';
                    } else {
                        $idTipoDocumental = $datosBloque['tiposDocumental']->idGdTrdTipoDocumental; // Tipo documental guardado
                    }

                } else {
                    $idTipoDocumental = $datosBloque['tiposDocumental']->idGdTrdTipoDocumental; // No era un objeto o modelo guardar (si no existe, responde null)
                }
            } else {
                $idTipoDocumental = $tipoDocumento->idGdTrdTipoDocumental; // Tipo documental existente
            }

            /** Validar si hubo algún inconveniente al ingresar la serie, subserie o tipo documental, y retornar el array de errores */
            if ($status == false) {
                return [
                    'status' => false,
                    'errors' => $errores,
                    'datosBloque' => $datosBloque, // Solo para debug
                    'model' => 'tiposDocumental', // Solo para debug
                ];
            }
            /** Fin Validar si hubo algún inconveniente al ingresar la serie, subserie o tipo documental, y retornar el array de errores */

            // SI todos los ids de las series llegan se procede a insertar en las TRD de lo contrario no se guarda.
            if (
                !is_null($ids['series']) && !is_null($ids['subseries']) 
                && ((int) $datosBloque['dependencia'] != 0)
                && !is_null($idTipoDocumental)
            ) {

                $gdTrd = GdTrd::find()
                    ->where(['idGdTrdDependencia' => $datosBloque['dependencia']])
                    ->andWhere(['idGdTrdSerie' => $ids['series']])
                    ->andWhere(['idGdTrdSubserie' => $ids['subseries']])
                    ->andWhere(['idGdTrdTipoDocumental' => $idTipoDocumental])
                ->one();

                if ($gdTrd == null) {
                    $gdTrd = new GdTrd;
                    $gdTrd->versionGdTrd = 1;
                    $gdTrd->identificadorUnicoGdTrd = $uniqid;
                    $gdTrd->idGdTrdDependencia = $datosBloque['dependencia'];
                    $gdTrd->idGdTrdSerie = $ids['series'];
                    $gdTrd->idGdTrdSubserie = $ids['subseries'];
                    $gdTrd->idGdTrdTipoDocumental = $idTipoDocumental;

                    //Se almacena la relacion entre dependencia, serie, subserie y tipo documental
                    if (!$gdTrd->save()) {
                        return [
                            'status' => false,
                            'errors' => $gdTrd->getErrors(),
                            'datosBloque' => $datosBloque, // Solo para debug
                            'model' => 'GdTrd', // Solo para debug
                        ];
                    } else {
                        return [
                            'status' => true,
                            'errors' => [],
                            'datosBloque' => $datosBloque, // Solo para debug
                            'model' => 'gdTrd Se guardó correctamente', // Solo para debug
                        ];
                    }
                } else {
                    return [
                        'status' => true,
                        'errors' => [],
                        'datosBloque' => $datosBloque, // Solo para debug
                        'model' => 'Ya existe la TRD', // Solo para debug
                    ];
                }

            } else {
                return [
                    'status' => true,
                    'errors' => [],
                    'datosBloque' => $datosBloque, // Solo para debug
                    'model' => 'GdTrd sin guardar por datos incompletos', // Solo para debug
                ];
            }
        } else {
            return [
                'status' => false,
                'errors' => ['error' => [Yii::t('app', 'noDataToSave')]],
                'datosBloque' => $datosBloque, // Solo para debug
                'model' => 'No hay datos a guardar', // Solo para debug
            ];
        }

    }
    /**
     * TABLAS TEMPORALES
     * Guarda la información que almaceno la variable $datosBloque. para las tablas temporales
     **/
    public function guardarBloqueTmp($datosBloque, $gdTrdDependTmp)
    {

        $errores = [];
        $modelsWithError = []; // Modelos que generan errores // Solo para debug
        $status = true;

        if (!is_null($datosBloque)) {

            //Se guarda los datos obtenidos en cada tabla
            $ids = [];

            ######
            ### VALIDACIÓN O PROCESO EN LAS TABLAS TEMPORALES
            if (is_object($datosBloque['serieTmp'])) {
                # Validar que se guarden los datos de la serie
                if (!$datosBloque['serieTmp']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['serieTmp']->getErrors());
                    $modelsWithError[] = 'Serie';
                }
            }
            $ids['seriesTmp'] = $datosBloque['serieTmp']->idGdTrdSerieTmp;

            // Si la subserie llega sin el código de la serie se realiza la asociación con el dato que se esta creando
            /** Validación de subserie */
            if (is_object($datosBloque['subserieTmp'])) {
                if ($datosBloque['subserieTmp']->idGdTrdSerieTmp == null || $datosBloque['subserieTmp']->idGdTrdSerieTmp == false) {
                    # Se asigna el ID de la serie a la tabla subserie
                    $datosBloque['subserieTmp']->idGdTrdSerieTmp = $ids['seriesTmp'];
                } else {
                    # Se valida si El Id de la serie es distinto al id de la subserie
                    if ($datosBloque['subserieTmp']->idGdTrdSerieTmp != $ids['seriesTmp']) {
                        $status = false;
                        $errores = array_merge($errores, ['error' => [Yii::t('app', 'idSerieDistintoSudserie')]]);
                        $modelsWithError[] = 'Subserie';
                    }
                }

                # Validar que se guarden los datos de la subserie
                if (!$datosBloque['subserieTmp']->save()) {
                    $status = false;
                    $errores = array_merge($errores, $datosBloque['subserieTmp']->getErrors());
                    $modelsWithError[] = 'Subserie';
                }
            }
            $ids['subseriesTmp'] = $datosBloque['subserieTmp']->idGdTrdSubserieTmp;

            /** Validación de tipo documental */
                if (is_object($datosBloque['tiposDocumentalTmp'])) {

                    # Validar que se guarden los datos de tipo documental
                    if (!$datosBloque['tiposDocumentalTmp']->save()) {
                        $status = false;
                        $idTipoDocumentalTmp = null;
                        $errores = array_merge($errores, $datosBloque['tiposDocumentalTmp']->getErrors());
                        $modelsWithError[] = 'tiposDocumentalTmp';
                    } else {
                        $idTipoDocumentalTmp = $datosBloque['tiposDocumentalTmp']->idGdTrdTipoDocumentalTmp; // Tipo documental guardado
                    }

                } else {
                    $idTipoDocumentalTmp = $datosBloque['tiposDocumentalTmp']->idGdTrdTipoDocumentalTmp; // No era un objeto o modelo guardar (si no existe, responde null)
                }

            // if (!$datosBloque['tiposDocumentalTmp']->save()) {
            //     $errores = array_merge($errores, $datosBloque['tiposDocumentalTmp']->getErrors());
            // } else {
            //     $idTipoDocumentalTmp = $datosBloque['tiposDocumentalTmp']->idGdTrdTipoDocumentalTmp;
            // }

            // SI todos los ids de las series llegan se procede a insertar en las TRD de lo contrario no se guarda.
            if (!is_null($ids['seriesTmp']) && $datosBloque['dependenciaTmp'] != '' && isset($idTipoDocumentalTmp)) {

                $gdTrdTmp = new GdTrdTmp;
                $gdTrdTmp->idGdTrdDependenciaTmp = $datosBloque['dependenciaTmp'];
                $gdTrdTmp->idGdTrdSerieTmp = $ids['seriesTmp'];
                $gdTrdTmp->idGdTrdSubserieTmp = $ids['subseriesTmp'];
                $gdTrdTmp->idGdTrdTipoDocumentalTmp = $idTipoDocumentalTmp;

                if (!$gdTrdTmp->save()) {
                    $errores = array_merge($errores, $gdTrdTmp->getErrors());
                }
            }
        }
        if(!empty($errores)){
            return $errores;
        }        
    }

    /**
     * Valida que la serie ya exista en la base de datos y retorne su modelo
     */
    public function validateSerie($numSerie, $nombreSerie)
    {
        // $nombreSerie = strtoupper($nombreSerie);

        # Se valida si ya existe el código de serie y esta activa.
        $modelSeries = GdTrdSeries::find()
            ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['codigoGdTrdSerie' => $numSerie])
            ->one();

        # Se valida si ya existe el nombre de serie y esta activa.
        $modelSeriesNombre = GdTrdSeries::find()
            ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['nombreGdTrdSerie' => $nombreSerie])
            ->one();    

        if ($modelSeries) {
            return $modelSeries;
        }
        elseif($modelSeriesNombre) {
            return $modelSeries;
        }

        return false;
    }

    /**
     * Valida que la serie ya exista para el código, en la base de datos y retorne su modelo 
     */
    public function validateSerieCodigo($numSerie, $nombreSerie)
    {
        // $nombreSerie = strtoupper($nombreSerie);

        # Se valida si ya existe el código de serie y esta activa.
        $modelSeries = GdTrdSeries::find()
            ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['codigoGdTrdSerie' => $numSerie])
            ->one();

        if ($modelSeries) {
            return $modelSeries;
        }
        return false;
    }

    /**
     * Valida que la serie ya exista para el nombre, en la base de datos y retorne su modelo
     */
    public function validateSerieNombre($numSerie, $nombreSerie)
    {
        // $nombreSerie = strtoupper($nombreSerie);

        # Se valida si ya existe el código de serie y esta activa.
        $modelSeries = GdTrdSeries::find()
            ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['nombreGdTrdSerie' => $nombreSerie])
            ->one();

        if ($modelSeries) {
            return $modelSeries;
        }
        return false;
    }

    /**
     * Valida que la subserie ya exista en la base de datos y retorne su modelo
     * aqui se valida que el código de la serie que llega y la subserie no existan ya en la base de datos
     * simepre y cuando esten activas
     * $idGdTrdSeries = id de la serie
     */
    public function validateSubserie($numSubserie, $nombreSubserie, $idGdTrdSeries)
    {
        $nombreSubserie = ucfirst(strtolower($nombreSubserie));

        # Se valida si ya existe el código de subserie y el nombre, ya que si el nombre
        # existe y esta activo quiere decir que se esta haciendo una nueva versión
        $modelSubseries = GdTrdSubseries::find()
            ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['codigoGdTrdSubserie' => $numSubserie])
            ->andWhere(['idGdTrdSerie' => $idGdTrdSeries])
            ->one();

        if ($modelSubseries) {
            return $modelSubseries;
        }
        return false;
    }

    /**
     * Valida que la subserie ya exista en la base de datos y retorne su modelo
     * aqui se valida que el código de la serie que llega y la subserie no existan ya en la base de datos
     * simepre y cuando esten activas
     * $idGdTrdSeries = código de la serie
     */
    public function validateSubserieCodigo($numSubserie, $nombreSubserie, $idGdTrdSeries)
    {
        $nombreSubserie = ucfirst(strtolower($nombreSubserie));

        $modelSeries = GdTrdSeries::find()
            ->where(['codigoGdTrdSerie' => $idGdTrdSeries, 'estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();

        if (!is_null($modelSeries)) {$idSerie = $modelSeries->idGdTrdSerie;} else { $idSerie = 0;}

        # Se valida si ya existe el código de subserie y el nombre, ya que si el nombre
        # existe y esta activo quiere decir que se esta haciendo una nueva versión
        $modelSubseries = GdTrdSubseries::find()
            ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['codigoGdTrdSubserie' => $numSubserie])
            ->andWhere(['idGdTrdSerie' => $idSerie])
            ->one();

        if (!is_null($modelSubseries)) {
            return $modelSubseries;
        }
        return false;
    }

    /**
     * Valida que la subserie ya exista en la base de datos y retorne su modelo
     * aqui se valida que el nombre de la serie que llega y la subserie no existan ya en la base de datos
     * simepre y cuando esten activas
     * $nombreSubserie = nombre de la serie
     */
    public function validateSubserieNombre($numSubserie, $nombreSubserie, $idGdTrdSeries)
    {
        $nombreSubserie = ucfirst(strtolower($nombreSubserie));

        $modelSeries = GdTrdSeries::find()
            ->where(['nombreGdTrdSerie' => $nombreSubserie, 'estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();

        if ($modelSeries) {$idSerie = $modelSeries->idGdTrdSerie;} else { $idSerie = 0;}

        # Se valida si ya existe el código de subserie y el nombre, ya que si el nombre
        # existe y esta activo quiere decir que se esta haciendo una nueva versión
        $modelSubseries = GdTrdSubseries::find()
            ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['codigoGdTrdSubserie' => $numSubserie])
            ->andWhere(['idGdTrdSerie' => $idSerie])
            ->one();

        if ($modelSubseries) {
            return $modelSubseries;
        }
        return false;
    }

    /**
     * Valida que el tipo documental ya exista en la base de datos y retorne su modelo
     */
    public function validateTipoDoc($nombreTipoDoc)
    {

        # Se valida si ya existe el nombre de tipo documental
        $modelTipoDoc = GdTrdTiposDocumentales::find()
            ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['nombreTipoDocumental' => $nombreTipoDoc])
            ->one();

        if ($modelTipoDoc) {
            return $modelTipoDoc;
        }
        return false;
    }

    /**
     * Valida que la fila $row no viaje vacia, y si contiene la palabra "CONVENCIONES"
     */
    public function validateRow($row)
    {

        $validate = false;
        foreach ($row as $i => $value) {

            // Si el nombre de una dependncia viene con el caracter (:) se realiza
            // un explode del campo para sacar el dato solamente del nombre
            if(!isset($value) || is_numeric($value)){
                continue;
            }
            $buscaCaracter = strpos($value, ':');

            if ($buscaCaracter === false) {} else {
                $explodevalue = explode(":", $value);
                $value = trim($explodevalue[0]);
            }

            if ($value && !is_null($value)) {

                $word = array('CONVENCIONES', 'CONVENCIONES:', 'Convenciones', 'Convenciones:');

                if (in_array($value, $word)) {
                    // significa que se debe dejar de procesar el archivo
                    return 2;

                } else {
                    // Significa que podemos procesar la linea porque hay datos
                    $validate = true;

                    return $validate;
                }

            }
        }
        return $validate;
    }

    public function validateData($hojaActualArray, $filaDatos)
    {

        $encontrados = 0;
        foreach ($hojaActualArray[$filaDatos] as $i => $value) {

            if ($value && !is_null($value)) {

                $word = array('CÓDIGO', 'SERIES', 'SUBSERIES', 'TIPOS DOCUMENTALES', 'Código', 'Series, subseries y tipología documental', 'Series', 'Subseries', 'Series', 'tipología documental');
                $word2 = array_push($word, 'ARCHIVO DE GESTION', 'AG', 'ARCHIVO CENTRAL', 'AC', 'CT', 'E', 'D', 'M', 'S', 'CODIGO DE T.R.D.');

                if (in_array($value, $word)) {
                    // significa que se debe dejar de procesar el archivo

                    $encontrados + 1;

                } else {
                    // Significa que podemos procesar la linea porque hay datos
                    $encontrados = 0;
                }

            }
        }
        return $encontrados;
    }

}
