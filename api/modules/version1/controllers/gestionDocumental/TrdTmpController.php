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

use api\components\HelperDynamicForms;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperLog;
use api\components\HelperQueryDb;
use api\components\HelperValidatePermits;
use api\models\CgRegionales;
use api\models\CgTrd;
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
use api\models\RolesTipoDocumental;
use api\models\GdExpedientes;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\web\Controller;

class TrdTmpController extends Controller
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
                    'index-one' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'delete' => ['PUT'],
                    'change-status' => ['PUT'],
                    'accept-version' => ['POST'],
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
            $limitRecords = Yii::$app->params['limitRecords'];

            //Se reitera el $request y obtener la informacion retornada
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
            $GdTrdTmp = GdTrdTmp::find();

                //->innerJoin('gdTrdDependenciasTmp', 'gdTrdTmp.idGdTrdDependenciaTmp=gdTrdDependenciasTmp.idGdTrdDependenciaTmp')
                $GdTrdTmp = HelperQueryDb::getQuery('innerJoin', $GdTrdTmp, 'gdTrdDependenciasTmp', ['gdTrdDependenciasTmp' => 'idGdTrdDependenciaTmp', 'gdTrdTmp' => 'idGdTrdDependenciaTmp']);
                //->innerJoin('gdTrdSeriesTmp', 'gdTrdTmp.idGdTrdSerieTmp=gdTrdSeriesTmp.idGdTrdSerieTmp')
                $GdTrdTmp = HelperQueryDb::getQuery('innerJoin', $GdTrdTmp, 'gdTrdSeriesTmp', ['gdTrdSeriesTmp' => 'idGdTrdSerieTmp', 'gdTrdTmp' => 'idGdTrdSerieTmp']);
                //->innerJoin('gdTrdSubseriesTmp', 'gdTrdTmp.idGdTrdSubserieTmp=gdTrdSubseriesTmp.idGdTrdSubserieTmp')
                $GdTrdTmp = HelperQueryDb::getQuery('innerJoin', $GdTrdTmp, 'gdTrdSubseriesTmp', ['gdTrdSubseriesTmp' => 'idGdTrdSubserieTmp', 'gdTrdTmp' => 'idGdTrdSubserieTmp']);
                //->innerJoin('gdTrdTiposDocumentalesTmp', 'gdTrdTmp.idGdTrdTipoDocumentalTmp=gdTrdTiposDocumentalesTmp.idGdTrdTipoDocumentalTmp')
                $GdTrdTmp = HelperQueryDb::getQuery('innerJoin', $GdTrdTmp, 'gdTrdTiposDocumentalesTmp', ['gdTrdTiposDocumentalesTmp' => 'idGdTrdTipoDocumentalTmp', 'gdTrdTmp' => 'idGdTrdTipoDocumentalTmp']);

            $GdTrdTmp = $GdTrdTmp->where(["estadoGdTrdTmp" => Yii::$app->params['statusTodoText']['Activo']]);

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {
                    case 'codigoGdTrdSerieTmp':
                        $GdTrdTmp->andWhere(['in', 'gdTrdSeriesTmp.' . $field, $value]);
                        break;
                    case 'codigoGdTrdDependenciaTmp':
                        $GdTrdTmp->andWhere(['in', 'gdTrdDependenciasTmp.' . $field, $value]);
                        break;
                    case 'codigoGdTrdSubserieTmp':
                        $GdTrdTmp->andWhere(['in', 'gdTrdSubseriesTmp.' . $field, $value]);
                        break;
                    case 'nombreTipoDocumentalTmp':
                        $GdTrdTmp->andWhere(['in', 'gdTrdTiposDocumentalesTmp.' . $field, $value]);
                    break;
                    case 'fechaInicial':
                        $GdTrdTmp->andWhere(['>=', 'gdTrdTmp.creacionGdTrdTmp', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $GdTrdTmp->andWhere(['<=', 'gdTrdTmp.creacionGdTrdTmp', trim($value) . Yii::$app->params['timeEnd']]);
                    break;

                }

            }

            //Limite de la consulta
            $GdTrdTmp->orderBy(['idGdTrdTmp' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $GdTrdTmp->limit($limitRecords);
            $modelRelation = $GdTrdTmp->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach ($modelRelation as $GdTrdTmpItem) {
                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($GdTrdTmpItem->idGdTrdTmp)),
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'idGdTrdTmp' => $GdTrdTmpItem->idGdTrdTmp,
                    'idGdTrdDependenciaTmp' => $GdTrdTmpItem->idGdTrdDependenciaTmp,
                    'nombreGdTrdDependenciaTmp' => $GdTrdTmpItem->gdTrdDependenciaTmp->codigoGdTrdDependenciaTmp . ' ' . $GdTrdTmpItem->gdTrdDependenciaTmp->nombreGdTrdDependenciaTmp,
                    'codigoGdTrdDependenciaTmp' => $GdTrdTmpItem->gdTrdDependenciaTmp->codigoGdTrdDependenciaTmp,

                    'nombreGdTrdSerieTmp' => $GdTrdTmpItem->gdTrdSerieTmp->codigoGdTrdSerieTmp . ' ' . $GdTrdTmpItem->gdTrdSerieTmp->nombreGdTrdSerieTmp,
                    'codigoGdTrdSerieTmp' => $GdTrdTmpItem->gdTrdSerieTmp->codigoGdTrdSerieTmp,

                    'nombreGdTrdSubserieTmp' => $GdTrdTmpItem->gdTrdSubserieTmp->codigoGdTrdSubserieTmp . ' ' . $GdTrdTmpItem->gdTrdSubserieTmp->nombreGdTrdSubserieTmp,
                    'codigoGdTrdSubserieTmp' => $GdTrdTmpItem->gdTrdSubserieTmp->codigoGdTrdSubserieTmp,

                    'nombreTipoDocumentalTmp' => $GdTrdTmpItem->gdTrdTipoDocumentalTmp->nombreTipoDocumentalTmp,
                    'creacionGdTrdTmp' => $GdTrdTmpItem->creacionGdTrdTmp,
                    'statusText' => Yii::t('app', 'statusTodoNumber')[$GdTrdTmpItem->estadoGdTrdTmp],
                    'status' => $GdTrdTmpItem->estadoGdTrdTmp,
                    'rowSelect' => false,
                    'idInitialList' => 0,
                );
            }

            // Validar que el formulario exista
            $formType = HelperDynamicForms::setListadoBD('indexGdTrdTmp');

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

    public function actionAcceptVersion()
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

                $observacion = $request['observacion'];
                $idDependeciaTmp = $request['id'];
                $cargaOK = false;

                // Se recorre cada uno de los ids que se recogen del index cuando se aprueba una versión de TRD
                foreach ($request['id'] as $key => $idDependeciaTmp) {

                    $modelDependeciaTmp = GdTrdDependenciasTmp::find()
                        ->where(['idGdTrdDependenciaTmp' => $idDependeciaTmp])
                        ->andWhere(['estadoGdTrdDependenciaTmp' => Yii::$app->params['statusTodoText']['Activo']])
                        ->one();

                    if(!is_null($modelDependeciaTmp)){

                        $codigoDependencia = $modelDependeciaTmp->codigoGdTrdDependenciaTmp;

                        $modelDependencia = GdTrdDependencias::find()
                            ->where(['codigoGdTrdDependencia' => $codigoDependencia])
                            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                            ->one();

                        // Si se encuentra información en la tabla real se procede a desactivar la TRD, de lo contrario no hace nada
                        // en las tablas reales.
                        if (!empty($modelDependencia)) {

                            $idDependencia = $modelDependencia->idGdTrdDependencia;

                            // Función que le cambia el estado a los datos de la TRd anterior.
                            $dependeciaData = self::desactivarTrd($idDependencia);                            

                            if (!$dependeciaData['ok']) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => [$dependeciaData['errors']],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                            $versionGdTrd = $dependeciaData['data']['versionGdTrd'] + 1;
                            $idsRoles = $dependeciaData['data']['idsRoles'];
                            $idRegional = $dependeciaData['data']['idRegional'];

                            //Calcula UUID
                            $uniqid = uniqid();
                            
                        }

                        // Busca con el id de la dependencia temporal los registros asociados a los mismos
                        $gdTrdTmp = GdTrdTmp::find()
                            ->where(['idGdTrdDependenciaTmp' => $idDependeciaTmp])
                            ->andWhere(['estadoGdTrdTmp' => Yii::$app->params['statusTodoText']['Activo']])
                            ->all();

                        $idSubseries = [];
                        $idSubseriesTmp = [];
                        $idSeries = [];
                        $idSeriesTmp = [];
                        $idDependencias = [];
                        $idDependenciasTmp = [];

                        $transaction = Yii::$app->db->beginTransaction();

                        if ($gdTrdTmp) {

                            if(!isset($versionGdTrd)){ $versionGdTrd = 0; }
                            if(!isset($uniqid)){ $uniqid = uniqid(); }

                            //Recorre la tabla maestra en donde esta almcenada los id de las tablas TrdTmp
                            foreach ($gdTrdTmp as $value) {

                                $gdTrd = new GdTrd();

                                //Inserta en la tabla gdTrdTiposDocuemntales
                                $gdTrd->versionGdTrd = $versionGdTrd;
                                $gdTrd->identificadorUnicoGdTrd = $uniqid;
                                $gdTrdTipoDocumentalTmp = $value->gdTrdTipoDocumentalTmp;
                                
                                $nombreTipoDoc = GdTrdTiposDocumentales::findOne(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo'], 'nombreTipoDocumental' => $gdTrdTipoDocumentalTmp->nombreTipoDocumentalTmp]);

                                if (!$nombreTipoDoc) {
                                    
                                    $gdTrdTiposDocumentales = new GdTrdTiposDocumentales();
                                    $gdTrdTiposDocumentales->nombreTipoDocumental = $gdTrdTipoDocumentalTmp->nombreTipoDocumentalTmp;
                                    $gdTrdTiposDocumentales->diasTramiteTipoDocumental = $gdTrdTipoDocumentalTmp->diasTramiteTipoDocumentalTmp;
                                    
                                    if (!$gdTrdTiposDocumentales->save()) {
                                        $transaction->rollBack();
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app', 'errorValidacion'),
                                            'data' => $gdTrdTiposDocumentales->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                        
                                    } 
                                    
                                    # Consulta para obtener el estado y fecha del tipo documental
                                    $gdTiposDoc = GdTrdTiposDocumentales::findOne(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo'], 'nombreTipoDocumental' => $gdTrdTipoDocumentalTmp->nombreTipoDocumentalTmp]);
                                    $gdTrd->idGdTrdTipoDocumental = $gdTrdTiposDocumentales->idGdTrdTipoDocumental;

                                } else {
                                    $gdTrd->idGdTrdTipoDocumental = $nombreTipoDoc->idGdTrdTipoDocumental;
                                }

                                $gdTrdSubserieTmp = $value->gdTrdSubserieTmp;

                                // Si alguno de los tipos documentales leidos tiene relación con los tipos documentales
                                if(isset($idsRoles)){
                                    foreach ($idsRoles as $idRol) {
                                        $RolesTipoDocumentalModel = new RolesTipoDocumental();
                                        $RolesTipoDocumentalModel->idRol = $idRol;
                                        $RolesTipoDocumentalModel->idGdTrdTipoDocumental = $gdTrdTiposDocumentales->idGdTrdTipoDocumental;
                                        $RolesTipoDocumentalModel->save();
                                    }
                                }
                                
                                //Inserta en la tabla gdTrdSeries si este regisro no ha sido insertado en esta action
                                $gdTrdSerieTmp = $value->gdTrdSerieTmp;

                                // Valida la información de las series documentales
                                $modelSeries = GdTrdSeries::find()
                                    ->where(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo']])
                                    ->andWhere(['codigoGdTrdSerie' => $gdTrdSerieTmp->codigoGdTrdSerieTmp])
                                    ->andWhere(['nombreGdTrdSerie' => $gdTrdSerieTmp->nombreGdTrdSerieTmp])
                                    ->one();

                                if (!$modelSeries) {

                                    $nombreSerie = GdTrdSeries::findOne(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo'], 'nombreGdTrdSerie' => $gdTrdSerieTmp->nombreGdTrdSerieTmp]);
                                    $codigoSerie = GdTrdSeries::findOne(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo'], 'codigoGdTrdSerie' => $gdTrdSerieTmp->codigoGdTrdSerieTmp]);

                                    // Si el nombre o el código de la serie que existe en la base de datos son diferentes a los que estan llegando por POST se generar error que ya existen los códigos
                                    if ($codigoSerie && $nombreSerie) {
                                        if (($codigoSerie->codigoGdTrdSerie != $gdTrdSerieTmp->codigoGdTrdSerieTmp) && ($nombreSerie->nombreGdTrdSerie != $gdTrdSerieTmp->nombreGdTrdSerieTmp)) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorTemporalTrdSerie') . ' ' . $codigoSerie->codigoGdTrdSerie,
                                                'data' => [],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }
                                    }

                                    if (!in_array($gdTrdSerieTmp->idGdTrdSerieTmp, $idSeriesTmp)) {
                                        $idSeriesTmp[] = $gdTrdSerieTmp->idGdTrdSerieTmp;
                                        
                                        $gdTrdSeries = new GdTrdSeries();
                                        $gdTrdSeries->nombreGdTrdSerie = $gdTrdSerieTmp->nombreGdTrdSerieTmp;
                                        $gdTrdSeries->codigoGdTrdSerie = $gdTrdSerieTmp->codigoGdTrdSerieTmp;

                                        if (!$gdTrdSeries->save()) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $gdTrdSeries->getErrors(),
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        } 

                                        # Consulta para obtener el estado y fecha de la serie
                                        $gdSerie = GdTrdSeries::findOne(['estadoGdTrdSerie' => Yii::$app->params['statusTodoText']['Activo'], 'nombreGdTrdSerie' => $gdTrdSerieTmp->nombreGdTrdSerieTmp]);
                                        $gdTrd->idGdTrdSerie = $gdTrdSeries->idGdTrdSerie;
                                        $idSeries[$gdTrdSerieTmp->idGdTrdSerieTmp] = $gdTrd->idGdTrdSerie;

                                    } else {
                                        $gdTrd->idGdTrdSerie = $idSeries[$gdTrdSerieTmp->idGdTrdSerieTmp];
                                    }
                                } else {
                                    $gdTrd->idGdTrdSerie = $modelSeries->idGdTrdSerie;
                                }

                                // Valida la información de las subseries documentales
                                $modelSubseries = GdTrdSubseries::find()
                                    ->where(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo']])
                                    ->andWhere(['codigoGdTrdSubserie' => $gdTrdSubserieTmp->codigoGdTrdSubserieTmp])
                                    ->andWhere(['nombreGdTrdSubserie' => $gdTrdSubserieTmp->nombreGdTrdSubserieTmp])
                                    ->andWhere(['idGdTrdSerie' => $gdTrd->idGdTrdSerie])
                                    ->one();

                                if (!$modelSubseries) {

                                    // Se valida que el nombre de la subserie no exista para la serie correspondiente
                                    $nombreSubserie = GdTrdSubseries::findOne(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'], 'nombreGdTrdSubserie' => $gdTrdSubserieTmp->nombreGdTrdSubserieTmp, 'idGdTrdSerie' => $gdTrd->idGdTrdSerie]);
                                    // Se valida que el codigo de la subserie no exista para la serie correspondiente
                                    $codigoSubserie = GdTrdSubseries::findOne(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'], 'codigoGdTrdSubserie' => $gdTrdSubserieTmp->codigoGdTrdSubserieTmp, 'idGdTrdSerie' => $gdTrd->idGdTrdSerie]);

                                    // Si el nombre o el código que existe en la base de datos son diferentes a lo que esta
                                    // llega por POST se generar error que ya existen los códigos
                                    if ($nombreSubserie && $codigoSubserie) {
                                        if (($codigoSubserie->codigoGdTrdSubserie != $gdTrdSubserieTmp->codigoGdTrdSubserieTmp) && ($nombreSubserie->nombreGdTrdSubserie != $gdTrdSubserieTmp->nombreGdTrdSubserieTmp)) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorTemporalTrdSubSerie') . ' ' . $codigoSubserie->codigoGdTrdSubserie,
                                                'data' => [],
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);
                                        }
                                    }

                                    //Inserta en la tabla gdTrdSubseries si este regisro no ha sido insertado en esta action
                                    // con base a la información de la serie se asocia el codigo de la serie con la subserie
                                    if (!in_array($gdTrdSubserieTmp->idGdTrdSubserieTmp, $idSubseriesTmp)) {
                                        $idSubseriesTmp[] = $gdTrdSubserieTmp->idGdTrdSubserieTmp;
                                        $gdTrdSubseries = new GdTrdSubseries();
                                        $gdTrdSubseries->nombreGdTrdSubserie = $gdTrdSubserieTmp->nombreGdTrdSubserieTmp;
                                        $gdTrdSubseries->codigoGdTrdSubserie = $gdTrdSubserieTmp->codigoGdTrdSubserieTmp;
                                        $gdTrdSubseries->tiempoGestionGdTrdSubserie = $gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp;
                                        $gdTrdSubseries->tiempoCentralGdTrdSubserie = $gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp;
                                        $gdTrdSubseries->ctDisposicionFinalGdTrdSubserie = $gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp;
                                        $gdTrdSubseries->eDisposicionFinalGdTrdSubserie = $gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp;
                                        $gdTrdSubseries->sDisposicionFinalGdTrdSubserie = $gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp;
                                        $gdTrdSubseries->mDisposicionFinalGdTrdSubserie = $gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp;
                                        $gdTrdSubseries->pSoporteGdTrdSubserie = $gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp;
                                        $gdTrdSubseries->eSoporteGdTrdSubserie = $gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp;
                                        $gdTrdSubseries->oSoporteGdTrdSubserie = $gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp;
                                        $gdTrdSubseries->procedimientoGdTrdSubserie = $gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp;
                                        $gdTrdSubseries->normaGdTrdSubserie = $gdTrdSubserieTmp->normaGdTrdSubserieTmp;
                                        $gdTrdSubseries->idGdTrdSerie = $gdTrd->idGdTrdSerie;

                                        if (!$gdTrdSubseries->save()) {
                                            $transaction->rollBack();
                                            Yii::$app->response->statusCode = 200;
                                            $response = [
                                                'message' => Yii::t('app', 'errorValidacion'),
                                                'data' => $gdTrdSubseries->getErrors(),
                                                'status' => Yii::$app->params['statusErrorValidacion'],
                                            ];
                                            return HelperEncryptAes::encrypt($response, true);

                                        }                                             
                                            # Consulta para obtener el estado y fecha de la subserie
                                            $gdSubserie = GdTrdSubseries::findOne(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'], 'nombreGdTrdSubserie' => $gdTrdSubserieTmp->nombreGdTrdSubserieTmp]);

                                        $gdTrd->idGdTrdSubserie = $gdTrdSubseries->idGdTrdSubserie;
                                        $idSubseries[$gdTrdSubserieTmp->idGdTrdSubserieTmp] = $gdTrdSubseries->idGdTrdSubserie;
                                    } else {
                                        $gdTrd->idGdTrdSubserie = $idSubseries[$gdTrdSubserieTmp->idGdTrdSubserieTmp];
                                    }
                                } else {
                                    $gdTrd->idGdTrdSubserie = $modelSubseries->idGdTrdSubserie;
                                }

                                //Inserta en la tabla gdTrdDependencias si este regisro no ha sido insertado en esta action
                                $gdTrdDependenciaTmp = $value->gdTrdDependenciaTmp;

                                // Se consulta el código el id de la dependencia padre con base en el código de la dependencia padre
                                $nombreDepePadre = GdTrdDependencias::findOne(['idGdTrdDependencia' => $gdTrdDependenciaTmp->codigoGdTrdDepePadreTmp]);
                                
                                # Se realiza la consulta con respecto al código de la dependencia  
                                # para obtener el último número del consecutivo que tenia anteriormente.
                                $modelDependence = GdTrdDependencias::find()
                                    ->where(['codigoGdTrdDependencia' => $gdTrdDependenciaTmp->codigoGdTrdDependenciaTmp])
                                    ->orderBy(['creacionGdTrdDependencia' => SORT_DESC])
                                ->all();
                                
                                if (!is_null($nombreDepePadre)) {$idPadre = $nombreDepePadre->idGdTrdDependencia;} else { $idPadre = 0;}

                                if (!in_array($gdTrdDependenciaTmp->idGdTrdDependenciaTmp, $idDependenciasTmp)) {
                                    $idDependenciasTmp[] = $gdTrdDependenciaTmp->idGdTrdDependenciaTmp;
                                    # Se crea el nuevo registro de la dependencia con la versión activa.
                                    $gdTrdDependencias = new GdTrdDependencias();
                                    $gdTrdDependencias->nombreGdTrdDependencia = $gdTrdDependenciaTmp->nombreGdTrdDependenciaTmp;
                                    $gdTrdDependencias->codigoGdTrdDependencia = $gdTrdDependenciaTmp->codigoGdTrdDependenciaTmp;
                                    $gdTrdDependencias->codigoGdTrdDepePadre = $idPadre;

                                    # Se toma la primera posición de la consulta de la dependencia, que seria el ultimo número
                                    # consecutivo que tendria actualmente, segun el orden que se le agrego a la consulta.
                                    if(count($modelDependence) > 0)
                                        $gdTrdDependencias->consecExpedienteGdTrdDependencia = $modelDependence[0]['consecExpedienteGdTrdDependencia'];

                                    if (!isset($gdTrdDependenciaTmp->idCgRegionalTmp) || $gdTrdDependenciaTmp->idCgRegionalTmp == null) {
                                        $gdTrdDependencias->idCgRegional = $idRegional;
                                    } else {
                                        $gdTrdDependencias->idCgRegional = $gdTrdDependenciaTmp->idCgRegionalTmp;
                                    }

                                    $gdTrdDependencias->observacionGdTrdDependencia = $observacion;
                                    if (!$gdTrdDependencias->save()) {
                                        $transaction->rollBack();
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app', 'errorValidacion'),
                                            'data' => $gdTrdDependencias->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }
                                        
                                    $gdTrd->idGdTrdDependencia = $gdTrdDependencias->idGdTrdDependencia;
                                    $idDependencias[$gdTrdDependenciaTmp->idGdTrdDependenciaTmp] = $gdTrdDependencias->idGdTrdDependencia;

                                    /**
                                     * Se actualiza la dependencia padre de las dependencias hijas relacionadas al 
                                     * id de la dependencia que se esta versionando
                                     **/
                                    $updteDependenciaHijaData = self::updteDependenciaHija($idDependencia, $gdTrd->idGdTrdDependencia);        

                                } else {
                                    $gdTrd->idGdTrdDependencia = $idDependencias[$gdTrdDependenciaTmp->idGdTrdDependenciaTmp];
                                }

                                if (!$gdTrd->save()) {

                                    $transaction->rollBack();
                                    Yii::$app->response->statusCode = 200;
                                    $response = [
                                        'message' => Yii::t('app', 'errorValidacion'),
                                        'data' => $gdTrd->getErrors(),
                                        'status' => Yii::$app->params['statusErrorValidacion'],
                                    ];
                                    return HelperEncryptAes::encrypt($response, true);
                                    
                                }           
                            }

                            // Si se desactiva una trd se cambia la dependencia de los usuarios que pertenecieran a la TRD anterior
                            if (isset($idDependencia) && $gdTrd->idGdTrdDependencia) {

                                $reasignacionData = self::reasingarUsuarios($idDependencia, $gdTrd->idGdTrdDependencia);
                                if (!$reasignacionData['ok']) {
                                    $transaction->rollBack();
                                    Yii::$app->response->statusCode = 200;
                                    $response = [
                                        'message' => Yii::t('app', 'errorValidacion'),
                                        'data' => $reasignacionData['errors'],
                                        'status' => Yii::$app->params['statusErrorValidacion'],
                                    ];
                                    return HelperEncryptAes::encrypt($response, true);

                                } else {

                                    if(!is_null($gdTrdSubseries)) {
                                        # Consulta para obtener el estado y fecha de la subserie
                                        $gdSubserie = GdTrdSubseries::findOne(['estadoGdTrdSubserie' => Yii::$app->params['statusTodoText']['Activo'], 'nombreGdTrdSubserie' => $gdTrdSubserieTmp->nombreGdTrdSubserieTmp]);

                                    }
                                }
                            }

                            // Se elimina de la tabla temporal los registros que ya fueron aprobados en las TRds reales
                            $deleteDataTemporal = self::deleteTemporal($idDependeciaTmp);

                            if (!$deleteDataTemporal['ok']) {
                                
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => $deleteDataTemporal['errors'],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);

                            } 

                            $gdTrd->idGdTrdDependencia = $gdTrdDependencias->idGdTrdDependencia;
                            $idDependencias[$gdTrdDependenciaTmp->idGdTrdDependenciaTmp] = $gdTrdDependencias->idGdTrdDependencia;
                        } else {
                            $gdTrd->idGdTrdDependencia = $idDependencias[$gdTrdDependenciaTmp->idGdTrdDependenciaTmp];
                        }
                        
                        if (!$gdTrd->save()) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $gdTrd->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        } else {

                            $transaction->commit();
                            $cargaOK = true;
                        }
                    }
                }

                if($cargaOK == true){

                    if($modelDependencia){
                        $nombreGdTrdDependencia = $modelDependencia->nombreGdTrdDependencia;
                    }else{
                        $nombreGdTrdDependencia = '';
                    }

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['acceptanceGdTrdDependencia'] . " ".$codigoDependencia." - ".$modelDependencia->nombreGdTrdDependencia.", con el siguiente motivo de aprobación: ".$observacion , //texto para almacenar en el evento
                        [],
                        [], //Data
                        array('creacionGdTrd') //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'acceptanceMessage'),
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
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


    public function actionDelete()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->post('jsonSend');

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

            $idDependeciaTmp = $request['id'];
            $cargaOK = false;
            $codigoDependencia = '';
            $nombreGdTrdDependencia = '';

            $GdTrdTmpModel = GdTrdTmp::find()
                ->where(['idGdTrdDependenciaTmp' => $idDependeciaTmp])
                ->all();

            $GdTrdTmpArray = [];
            foreach ($GdTrdTmpModel as $key => $gdTrdTmpIntem) {
                $GdTrdTmpArray[$key]['idGdTrdDependenciaTmp'] = $gdTrdTmpIntem->idGdTrdDependenciaTmp;
                $GdTrdTmpArray[$key]['idGdTrdSerieTmp'] = $gdTrdTmpIntem->idGdTrdSerieTmp;
                $GdTrdTmpArray[$key]['idGdTrdSubserieTmp'] = $gdTrdTmpIntem->idGdTrdSubserieTmp;
                $GdTrdTmpArray[$key]['idGdTrdTipoDocumentalTmp'] = $gdTrdTmpIntem->idGdTrdTipoDocumentalTmp;
                $gdTrdTmpIntem->delete();
            }

            foreach ($GdTrdTmpArray as $gdTrdTmpIntem) {

                # DEPENDENCIA TMP
                $gdTrdDependenciaTmpModel = GdTrdDependenciasTmp::findOne(['idGdTrdDependenciaTmp' => $gdTrdTmpIntem['idGdTrdDependenciaTmp']]);

                if (!empty($gdTrdDependenciaTmpModel)) {

                    $nombreGdTrdDependencia .= $gdTrdDependenciaTmpModel->nombreGdTrdDependenciaTmp;
                    $codigoDependencia .= $gdTrdDependenciaTmpModel->codigoGdTrdDependenciaTmp;

                    #Se obtiene el nombre de la regional
                    $regionals = CgRegionales::findOne(['idCgRegional' => $gdTrdDependenciaTmpModel->idCgRegionalTmp]);

                    $gdTrdDependenciaTmpModel->delete();


                    /***    Log de Auditoria  ***/
                    /*HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Delete'].' de id: '.$gdTrdTmpIntem['idGdTrdDependenciaTmp'].", estado: ". Yii::t('app','statusTodoNumber')[$gdTrdDependenciaTmpModel->estadoGdTrdDependenciaTmp]." y regional: ".$regionals->nombreCgRegional.", en la tabla GdTrdDependenciasTmp", //texto para almacenar en el evento
                        [],
                        [$gdTrdDependenciaTmpModel], //Data
                        array('estadoGdTrdDependenciaTmp', 'idCgRegionalTmp') //No validar estos campos
                    );*/
                    /***    Fin log Auditoria   ***/
                }

                # SERIE TMP
                $gdTrdSerieTmpModel = GdTrdSeriesTmp::findOne(['idGdTrdSerieTmp' => $gdTrdTmpIntem['idGdTrdSerieTmp']]);
                if (!empty($gdTrdSerieTmpModel)) {
                    $gdTrdSerieTmpModel->delete();

                    /***    Log de Auditoria  ***/
                    /*HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Delete'].' de id: '.$gdTrdTmpIntem['idGdTrdSerieTmp']." con estado: ". Yii::t('app','statusTodoNumber')[$gdTrdSerieTmpModel->estadoGdTrdSerieTmp].", en la tabla GdTrdSeriesTmp", //texto para almacenar en el evento
                        [],
                        [$gdTrdSerieTmpModel], //Data
                        array('estadoGdTrdSerieTmp') //No validar estos campos
                    );*/
                    /***    Fin log Auditoria   ***/
                }

                # SUBSERIE TMP
                $gdTrdSubserieTmpModel = GdTrdSubseriesTmp::findOne(['idGdTrdSubserieTmp' => $gdTrdTmpIntem['idGdTrdSubserieTmp']]);
                if (!empty($gdTrdSubserieTmpModel)) {
                    $gdTrdSubserieTmpModel->delete();
                    
                    /***    Log de Auditoria  ***/
                    /*HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Delete'].' con id: '.$gdTrdTmpIntem['idGdTrdSubserieTmp']." con estado: ". Yii::t('app','statusTodoNumber')[$gdTrdSubserieTmpModel->estadoGdTrdSubserieTmp].", en la tabla GdTrdSubseriesTmp", //texto para almacenar en el evento
                        [],
                        [$gdTrdSubserieTmpModel], //Data
                        array('estadoGdTrdSubserieTmp', 'idGdTrdSerieTmp') //No validar estos campos
                    );*/
                    /***    Fin log Auditoria   ***/
                }

                # TIPOS DOCUMENTALES TMP
                $gdTrdTipoDocumentalTmpModel = GdTrdTiposDocumentalesTmp::findOne(['idGdTrdTipoDocumentalTmp' => $gdTrdTmpIntem['idGdTrdTipoDocumentalTmp']]);
                if (!empty($gdTrdTipoDocumentalTmpModel)) {
                    $gdTrdTipoDocumentalTmpModel->delete();
                   
                    /***    Log de Auditoria  ***/
                    /*HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Delete'].' con id: '.$gdTrdTmpIntem['idGdTrdTipoDocumentalTmp']." con estado: ". Yii::t('app','statusTodoNumber')[$gdTrdTipoDocumentalTmpModel->estadoTipoDocumentalTmp].", en la tabla GdTrdTiposDocumentalesTmp", //texto para almacenar en el evento
                        [],
                        [$gdTrdTipoDocumentalTmpModel], //Data
                        array('estadoTipoDocumentalTmp') //No validar estos campos
                    );*/
                    /***    Fin log Auditoria   ***/
                }
            }

           // if($cargaOK == true){


                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['deleteGdTrdDependencia'] . " ".$codigoDependencia." - ".$nombreGdTrdDependencia , //texto para almacenar en el evento
                        [],
                        [], //Data
                        array('estadoGdTrdDependenciaTmp') //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                //}

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'deleteMessage'),
                'data' => [],
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
     * Función que se ejecuta cuando ya se ha aprobado una trd anteriormente una ves se aprueba
     * se debe eliminar de las tablas temporales los registros correspondientes.
     * $idDependeciaTmp = id de la dependencia temporal
     */
    public function deleteTemporal($idDependeciaTmp)
    {

        $GdTrdTmpModel = GdTrdTmp::find()->where(['idGdTrdDependenciaTmp' => $idDependeciaTmp])->all();

        if (!empty($GdTrdTmpModel)) {
            $GdTrdTmpArray = [];
            foreach ($GdTrdTmpModel as $key => $gdTrdTmpIntem) {
                $GdTrdTmpArray[$key]['idGdTrdDependenciaTmp'] = $gdTrdTmpIntem->idGdTrdDependenciaTmp;
                $GdTrdTmpArray[$key]['idGdTrdSerieTmp'] = $gdTrdTmpIntem->idGdTrdSerieTmp;
                $GdTrdTmpArray[$key]['idGdTrdSubserieTmp'] = $gdTrdTmpIntem->idGdTrdSubserieTmp;
                $GdTrdTmpArray[$key]['idGdTrdTipoDocumentalTmp'] = $gdTrdTmpIntem->idGdTrdTipoDocumentalTmp;
                $gdTrdTmpIntem->delete();
            }

            $erroresElimina = [];

            foreach ($GdTrdTmpArray as $gdTrdTmpIntem) {

                $gdTrdDependenciaTmpModel = GdTrdDependenciasTmp::findOne(['idGdTrdDependenciaTmp' => $gdTrdTmpIntem['idGdTrdDependenciaTmp']]);
                
                if (!empty($gdTrdDependenciaTmpModel)) {
                    // $gdTrdDependenciaTmpModel->delete();

                    if(!$gdTrdDependenciaTmpModel->delete()){
                        $erroresElimina = $gdTrdDependenciaTmpModel->getErrors();
                    }
                }

                $gdTrdSerieTmpModel = GdTrdSeriesTmp::findOne(['idGdTrdSerieTmp' => $gdTrdTmpIntem['idGdTrdSerieTmp']]);
                if (!empty($gdTrdSerieTmpModel)) {
                    // $gdTrdSerieTmpModel->delete();

                    if(!$gdTrdSerieTmpModel->delete()){
                        $erroresElimina = $gdTrdSerieTmpModel->getErrors();
                    }
                }

                $gdTrdSubserieTmpModel = GdTrdSubseriesTmp::findOne(['idGdTrdSubserieTmp' => $gdTrdTmpIntem['idGdTrdSubserieTmp']]);
                if (!empty($gdTrdSubserieTmpModel)) {
                    // $gdTrdSubserieTmpModel->delete();

                    if(!$gdTrdSubserieTmpModel->delete()){
                        $erroresElimina = $gdTrdSubserieTmpModel->getErrors();
                    }
                }

                $gdTrdTipoDocumentalTmpModel = GdTrdTiposDocumentalesTmp::findOne(['idGdTrdTipoDocumentalTmp' => $gdTrdTmpIntem['idGdTrdTipoDocumentalTmp']]);
                if (!empty($gdTrdTipoDocumentalTmpModel)) {
                    //$gdTrdTipoDocumentalTmpModel->delete();

                    if(!$gdTrdTipoDocumentalTmpModel->delete()){
                        $erroresElimina = $gdTrdTipoDocumentalTmpModel->getErrors();
                    }
                }
            }
        }

        if(count($erroresElimina) > 0){
            return [
                'ok' => false,
                'errors' => $erroresElimina,
                'data' => [],
            ];
        }else{
            return [
                'ok' => true,
                'errors' => [],
                'data' => [],
            ];   
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
                $modelGdTrdTmp = new GdTrdTmp();
                $modelGdTrdTmp->attributes = $request;

                if ($modelGdTrdTmp->save()) {

                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla TrdTmp", //texto para almacenar en el evento
                        [],
                        [$modelGdTrdTmp], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelGdTrdTmp,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelGdTrdTmp->getErrors(),
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

            $id = $request['data'];

            $modelGdTrdTmp = GdTrdTmp::find()
                ->where(['idGdTrdDependenciaTmp' => $id])
                ->all();

            // Recorre los Ids que llegan para que se vean reflejados en el log 
            $idDepeLog = '';
            foreach ($id as $value) {
                $idDepeLog = $value.', '.strval($idDepeLog);
                $modelTmpDepend = GdTrdDependenciasTmp::findOne(['idGdTrdDependenciaTmp' => $value]);                
            }

            $namesTmpDepend = '';
            if(!is_null($modelTmpDepend)){
                $nameTmpDepend[] = $modelTmpDepend->nombreGdTrdDependenciaTmp;
                $namesTmpDepend = implode(', ', $nameTmpDepend);
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'id: '.$idDepeLog.' de la dependencia: '.$namesTmpDepend, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            $dataDep = [];
            $dataSeries = [];
            $dataSubseries = [];
            $dataTipDoc = [];
            $data = []; // Data de la subserie

            foreach ($modelGdTrdTmp as $model) {

                // Se agregan los atributos a variables para que se puedan utilizar de mejor manera

                // Dependencias
                $idDepe = $model->idGdTrdDependenciaTmp;
                $codDepe = $model->gdTrdDependenciaTmp->codigoGdTrdDependenciaTmp;
                $idCgRegional = $model->gdTrdDependenciaTmp->idCgRegionalTmp;
                /** Se llama la regional */
                $modelRegional = CgRegionales::find()
                    ->where(['idCgRegional' => $idCgRegional])
                    ->one();
                $regional = $modelRegional->nombreCgRegional;

                // Se consulta la configuración del cliente para la lectura de la TRD
                // se valida que llegue soporte ya que si llega se debe mandar al frontend para que muestre los datos
                // de lo contrario no se deben enviar.
                $modelCgTrd = CgTrd::findOne(['estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']]);

                if (!is_null($modelCgTrd->columnPSoporteCgTrd) || !is_null($modelCgTrd->columnESoporteCgTrd)) {
                    $soporte = true;
                } else {
                    $soporte = false;
                }

                if (!is_null($modelCgTrd->columnNormaCgTrd)) {
                    $norma = true;
                } else {
                    $norma = false;
                }

                $formatter = \Yii::$app->formatter;
                $fechaAct = $formatter->asDate($model->gdTrdDependenciaTmp->creacionGdTrdDependenciaTmp, 'long');
                                            
                # Con el id del codigo del padre se consulta el nombre de la unidad administrativa en dependencias.
                $codUnidad = $model->gdTrdDependenciaTmp->codigoGdTrdDepePadreTmp;
                $modelDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => $codUnidad]);
                $unidadAdminist = '';
                if(!is_null($modelDependence)){
                    $unidadAdminist = $modelDependence->codigoGdTrdDependencia.' - '.$modelDependence->nombreGdTrdDependencia;
                }
                
                # Información de la dependencia
                $nomDepe = $model->gdTrdDependenciaTmp->nombreGdTrdDependenciaTmp;
                $oficinaProd = $codDepe . ' - ' . $nomDepe;

                $validaDepe = array('idDepe' => $idDepe, 'codDepe' => $codDepe, 'nomDepe' => $unidadAdminist, 'regional' => $regional, 'oficinaProd' => $oficinaProd, 'fechaAct' => $fechaAct, 'soporte' => $soporte, 'norma' => $norma);
                // Series
                $idSerie = $model->idGdTrdSerieTmp;
                $codSerie = $model->gdTrdSerieTmp->codigoGdTrdSerieTmp;
                $nomSerie = $model->gdTrdSerieTmp->nombreGdTrdSerieTmp;
                $nomSerie = $codSerie . ' - ' . $nomSerie;
                $validaSerie = array('idSerie' => $idSerie, 'codSerie' => $codSerie, 'nomSerie' => $nomSerie);
                // Subseries
                $idSubserie = $model->idGdTrdSubserieTmp;
                $codSubserie = $model->gdTrdSubserieTmp->codigoGdTrdSubserieTmp;
                $nomSubserie = $model->gdTrdSubserieTmp->nombreGdTrdSubserieTmp;
                $nomSubserie = $codSubserie . ' - ' . $nomSubserie;
                $validaSubserie = array('idSubserie' => $idSubserie, 'codSubserie' => $codSubserie, 'nomSubserie' => $nomSubserie);
                // Tipos documentales
                $idTipDocu = $model->idGdTrdTipoDocumentalTmp;
                $diasTipDoc = $model->gdTrdTipoDocumentalTmp->diasTramiteTipoDocumentalTmp;
                $nomTipDoc = $model->gdTrdTipoDocumentalTmp->nombreTipoDocumentalTmp;
                $validaTipDoc = array('idTipDocu' => $idTipDocu, 'diasTipDoc' => $diasTipDoc, 'nomTipDoc' => $nomTipDoc);

                // Validacion para crear la estrucctura para enviarla al frontend

                // Dependencias
                if (!in_array($validaDepe, $dataDep)) {
                    $dataDep[] = $validaDepe;
                }

                // Series
                if (isset($dataSeries[$idDepe])) {

                    if (!in_array($validaSerie, $dataSeries[$idDepe])) {
                        $dataSeries[$idDepe][] = $validaSerie;
                    }

                } else {
                    $dataSeries[$idDepe][] = $validaSerie;
                }

                // Subseries
                if (isset($dataSubseries[$idSerie])) {

                    if (!in_array($validaSubserie, $dataSubseries[$idSerie])) {

                        if ($model->gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp == '10') {$pSoporte = 'X';} else { $pSoporte = '';}
                        if ($model->gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp == '10') {$eSoporte = 'X';} else { $eSoporte = '';}
                        if ($model->gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp == '10') {$oSoporte = 'X';} else { $oSoporte = '';}

                        if (!is_null($model->gdTrdSubserieTmp->normaGdTrdSubserieTmp)) {$normaSubserie = $model->gdTrdSubserieTmp->normaGdTrdSubserieTmp;} else { $normaSubserie = 'No tiene norma registrada';}

                        $dataSubseries[$idSerie][] = $validaSubserie;
                        $data[$idSubserie]['tiempoGestion'] = $model->gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp;
                        $data[$idSubserie]['tiempoCentral'] = $model->gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp;
                        $data[$idSubserie]['pSoporte'] = $pSoporte;
                        $data[$idSubserie]['eSoporte'] = $eSoporte;
                        $data[$idSubserie]['oSoporte'] = $oSoporte;
                        $data[$idSubserie]['procedimientos'] = $model->gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp;
                        $data[$idSubserie]['disposicionCT'] = $model->gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp;
                        $data[$idSubserie]['disposicionE'] = $model->gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp;
                        $data[$idSubserie]['disposicionS'] = $model->gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp;
                        $data[$idSubserie]['disposicionM'] = $model->gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp;
                        $data[$idSubserie]['norma'] = $normaSubserie;

                    }

                } else {

                    if ($model->gdTrdSubserieTmp->pSoporteGdTrdSubserieTmp == '10') {$pSoporte = 'X';} else { $pSoporte = '';}
                    if ($model->gdTrdSubserieTmp->eSoporteGdTrdSubserieTmp == '10') {$eSoporte = 'X';} else { $eSoporte = '';}
                    if ($model->gdTrdSubserieTmp->oSoporteGdTrdSubserieTmp == '10') {$oSoporte = 'X';} else { $oSoporte = '';}

                    if (!is_null($model->gdTrdSubserieTmp->normaGdTrdSubserieTmp)) {$normaSubserie = $model->gdTrdSubserieTmp->normaGdTrdSubserieTmp;} else { $normaSubserie = 'No tiene norma registrada';}

                    $dataSubseries[$idSerie][] = $validaSubserie;
                    $data[$idSubserie]['tiempoGestion'] = $model->gdTrdSubserieTmp->tiempoGestionGdTrdSubserieTmp;
                    $data[$idSubserie]['tiempoCentral'] = $model->gdTrdSubserieTmp->tiempoCentralGdTrdSubserieTmp;
                    $data[$idSubserie]['pSoporte'] = $pSoporte;
                    $data[$idSubserie]['eSoporte'] = $eSoporte;
                    $data[$idSubserie]['oSoporte'] = $oSoporte;
                    $data[$idSubserie]['procedimientos'] = $model->gdTrdSubserieTmp->procedimientoGdTrdSubserieTmp;
                    $data[$idSubserie]['disposicionCT'] = $model->gdTrdSubserieTmp->ctDisposicionFinalGdTrdSubserieTmp;
                    $data[$idSubserie]['disposicionE'] = $model->gdTrdSubserieTmp->eDisposicionFinalGdTrdSubserieTmp;
                    $data[$idSubserie]['disposicionS'] = $model->gdTrdSubserieTmp->sDisposicionFinalGdTrdSubserieTmp;
                    $data[$idSubserie]['disposicionM'] = $model->gdTrdSubserieTmp->mDisposicionFinalGdTrdSubserieTmp;
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

    public static function desactivarTrd($idGdTrdDependencia)
    {
        $errors = [];
        $todoOk = false;
        $versionGdTrd = 0;

        $transaction = Yii::$app->db->beginTransaction();

        $gdTrd = GdTrd::find()
            ->where(['idGdTrdDependencia' => $idGdTrdDependencia])
            ->andWhere(["estadoGdTrd" => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        if (empty($gdTrd)) {
            $errors[] = 'La dependencia a actualizar no existe en las trd';
            return [
                'ok' => false,
                'errors' => $errors,
                'data' => [
                    'idsRoles' => [],
                    'idRegional' => [],
                ],
            ];

        } else {
            $idsRoles = [];

            foreach ($gdTrd as $gdTrdItem) {

                //Se valida si el gtrd Item es la versión mayor insertada
                if($gdTrdItem->versionGdTrd > $versionGdTrd){
                    $versionGdTrd = $gdTrdItem->versionGdTrd;
                }

                // Se procede a inactivar los tipos documentales asignados a la dependencia seleccionada
                $gdTrdItem->gdTrdTipoDocumental->estadoTipoDocumental = Yii::$app->params['statusTodoText']['Inactivo'];

                if ($gdTrdItem->gdTrdTipoDocumental->save()) {
                    $todoOk = true;
                } else {
                    $errors[] = $gdTrdItem->gdTrdTipoDocumental->getErrors();
                    $todoOk = false;
                }

                // Se realiza validación para determinar si los tipos documentales estan asignados a algun rol de lo contrario
                // no se debe eliminar nada
                if (!empty($gdTrdItem->gdTrdTipoDocumental->rolesTipoDocumentals)) {
                    foreach ($gdTrdItem->gdTrdTipoDocumental->rolesTipoDocumentals as $rolTipoDocumental) {
                        if (in_array($rolTipoDocumental->idRol, $idsRoles)) {
                            $idsRoles[] = $rolTipoDocumental->idRol;
                        }
                        if ($rolTipoDocumental->delete()) {
                            $todoOk = true;
                        } else {
                            $todoOk = false;
                        }
                    }
                }
                // Se procede a inactivar las subseries asignadas a la dependencia seleccionada en el listado
                if($gdTrdItem->gdTrdSubserie->estadoGdTrdSubserie != Yii::$app->params['statusTodoText']['Inactivo']){
                    $gdTrdItem->gdTrdSubserie->estadoGdTrdSubserie = Yii::$app->params['statusTodoText']['Inactivo'];
                    if ($gdTrdItem->gdTrdSubserie->save()) {
                        $todoOk = true;
                    } else {
                        $errors[] = $gdTrdItem->gdTrdSubserie->getErrors();
                        $todoOk = false;
                    }
                }
                

                // Se procede a inactivar las series asignadas a la dependencia seleccionada en el listado
                if($gdTrdItem->gdTrdSerie->estadoGdTrdSerie != Yii::$app->params['statusTodoText']['Inactivo']){
                    $gdTrdItem->gdTrdSerie->estadoGdTrdSerie = Yii::$app->params['statusTodoText']['Inactivo'];
                    if ($gdTrdItem->gdTrdSerie->save()) {
                        $todoOk = true;
                    } else {
                        $errors[] = $gdTrdItem->gdTrdSerie->getErrors();
                        $todoOk = false;
                    }
                }                

                // Se procede a inactivar la dependencia seleccionada
                if($gdTrdItem->gdTrdDependencia->estadoGdTrdDependencia != Yii::$app->params['statusTodoText']['Inactivo']){
                    $gdTrdItem->gdTrdDependencia->estadoGdTrdDependencia = Yii::$app->params['statusTodoText']['Inactivo'];
                    $idRegional = $gdTrdItem->gdTrdDependencia->idCgRegional;
                    if ($gdTrdItem->gdTrdDependencia->save()) {
                        $todoOk = true;
                    } else {
                        $errors[] = $gdTrdItem->gdTrdDependencia->getErrors();
                        $todoOk = false;
                    }
                }                

                // Se procede a inactivar los registros asignados a la trd
                if($gdTrdItem->estadoGdTrd != Yii::$app->params['statusTodoText']['Inactivo']){
                    $gdTrdItem->estadoGdTrd = Yii::$app->params['statusTodoText']['Inactivo'];
                    if ($gdTrdItem->save()) {
    
    
                        $todoOk = true;
                    } else {
                        $errors[] = $gdTrdItem->getErrors();
                        $todoOk = false;
                    }
                }
               
            }

            // Se procede a cambiar el estado del expediente a "Pendiente por Cerrar" por cambio de versión
            $expedientes = GdExpedientes::find();
            $expedientes = HelperQueryDb::getQuery('innerJoin', $expedientes, 'gdTrdDependencias', ['gdExpedientes' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
            $expedientes = $expedientes->where(['gdExpedientes.idGdTrdDependencia' => $idGdTrdDependencia, 'gdExpedientes.estadoGdExpediente' => Yii::$app->params['statusExpedienteText']['Abierto']])
                    ->all();

            foreach ($expedientes as $expedienteUnico) {

                $expedienteUnico->estadoGdExpediente = Yii::$app->params['statusExpedienteText']['PendienteCerrar'];
                if ($expedienteUnico->save()) {
                    $todoOk = true;
                } else {
                    $errors[] = $expedienteUnico->getErrors();
                    $todoOk = false;
                }
            }                    

            /**
             * Guargar log de la Trd
             */
            if (count($gdTrd) > 0) {
                $infoDependenca = GdTrdDependencias::find()->select('nombreGdTrdDependencia')->where(['idGdTrdDependencia' => $idGdTrdDependencia])->one();
            }

            if (count($errors) == 0) {
                $transaction->commit();

                return [
                    'ok' => true,
                    'errors' => [],
                    'data' => [
                        'idsRoles' => [],
                        'idRegional' => [],
                        'versionGdTrd' => $versionGdTrd
                    ],
                ];

            } else {
                $transaction->rollBack();

                return [
                    'ok' => false,
                    'errors' => $errors,
                    'data' => [
                        'idsRoles' => [],
                        'idRegional' => [],
                    ],
                ];
            }
        }
        // }
    }

    public function reasingarUsuarios($idDependenciaAntiguo, $idDependeciaNuevo)
    {

        $dependenciasModel = GdTrdDependencias::find()
            ->where(['idGdTrdDependencia' => $idDependenciaAntiguo])
            ->one();

        if (empty($dependenciasModel)) {
            return [
                'ok' => false,
                'errors' => 'No se encontró la dependecia',
                'data' => [],
            ];
        }

        foreach ($dependenciasModel->users as $userItem) {
            $userItem->idGdTrdDependencia = $idDependeciaNuevo;
            if ($userItem->save()) {
            }
        }

        return [
            'ok' => true,
            'errors' => '',
            'data' => [],
        ];
    }

    public function updteDependenciaHija($idDependenciaAntiguo, $idDependeciaNuevo)
    {

        $gdDependenciasHijas = GdTrdDependencias::find()
            ->where(['codigoGdTrdDepePadre' => $idDependenciaAntiguo])
            ->andWhere(["estadoGdTrdDependencia" => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        if (!empty($gdDependenciasHijas)) {

            foreach ($gdDependenciasHijas as $itemGdDependenciaHija) {
                $itemGdDependenciaHija->codigoGdTrdDepePadre = $idDependeciaNuevo;
                if ($itemGdDependenciaHija->save()) {  
                }else{
                    return [
                        'ok' => false,
                        'errors' => $itemGdDependenciaHija->getErrors(),
                        'data' => [],
                    ];
                }                
            }

            return [
                'ok' => true,
                'errors' => '',
                'data' => [],
            ];
        }
        
    }

}
