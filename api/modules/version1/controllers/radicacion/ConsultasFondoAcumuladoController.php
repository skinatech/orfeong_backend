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

namespace api\modules\version1\controllers\radicacion;

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use yii\helpers\FileHelper;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperOtherConnectionsDb;
use api\modules\version1\controllers\correo\CorreoController;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\data\ActiveDataProvider;

use api\models\CgGeneralBasesDatos;
use api\components\HelperQueryDb;

/**
 * Consultas Avanzada Controller
 */
class ConsultasFondoAcumuladoController extends Controller
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
                    'index'  => ['GET']
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
     * Lists all CgPlantillas models.
     * @return mixed
     */
    public function actionIndex($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            if (!empty($request)) {
                $decrypted = HelperEncryptAes::decryptGET($request, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];
                } else {
                    $request = '';
                }
            }

            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            $dataWhere = [];
            if (is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info) {
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
                            if(isset($info) && $info !== null && trim($info) != '' ) {
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            $where = "";
            foreach ($dataWhere as $field => $value) {
                switch ($field) {
                    case 'fechaInicial':
                        $where .= " AND fecha_inicial >= '". $value ." 00:00:00'";
                    break;
                    case 'fechaFinal':
                        $where .= " AND fecha_final	 <= '". $value ." 23:59:59'";
                    break;
                    case 'codigo':
                        $where .= " AND codigo = '". $value."'";
                    break;
                    case 'nombre':
                        $where .= " AND nombre ILIKE '%". $value ."%'";
                    break;
                    case 'observaciones':
                        $where .= " AND observaciones ILIKE '%". $value ."%'";
                    break;
                }
            }

            $consultaOrfeo38Index = Yii::$app->db->createCommand("SELECT * FROM fondo_acumulado WHERE 1 = 1 ". $where ." ORDER BY fecha_inicial DESC LIMIT ". $limitRecords ."");

            try {
                $resultConsultaOrfeo38Index = $consultaOrfeo38Index->queryAll();
            } catch (\Throwable $th) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'accessDenied')[2],
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorAccessDenied'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            foreach ($resultConsultaOrfeo38Index as $resultadoIndex) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($resultadoIndex['orden'])),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($resultadoIndex['orden']))
                );

                $dataList[] = array(
                    'data'            => $dataBase64Params,
                    'id'              => $resultadoIndex['orden'],
                    'codigo'          => $resultadoIndex['codigo'],
                    'nombre'          => $resultadoIndex['nombre'],
                    'fechaInicial'    => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoIndex['fecha_inicial'])),
                    'fechaFinal'      => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoIndex['fecha_final'])),
                    'caja'            => $resultadoIndex['caja'],
                    'carpeta'         => $resultadoIndex['carpeta'],
                    'tomo'            => $resultadoIndex['tomo'],
                    'serial'          => $resultadoIndex['serial'],
                    'cd'              => $resultadoIndex['cd'],
                    'folios'          => $resultadoIndex['folios'],
                    'soporte'         => $resultadoIndex['soporte'],
                    'frecuencia'      => $resultadoIndex['frecuencia'],
                    'observaciones'   => $resultadoIndex['observaciones'],
                    'url'             => $resultadoIndex['url'],
                    'rowSelect'       => false,
                    'idInitialList'   => 0,
                );
            }

            $formType = HelperDynamicForms::setListadoBD('indexConsultaFondoAcumulado');
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit100($formType, $limitRecords),
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

    public function actionDescargarDocumento() {
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
        if (!empty($jsonSend)) {
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

            $explodeNombreDoc = explode('/', $request['ButtonSelectedData'][0]['id']);
            $fileName = $explodeNombreDoc[count($explodeNombreDoc) - 1];
            $rutaFile = Yii::$app->params['rutaDocumentosOrfeo38AlmaArchivos'] ."". $request['ButtonSelectedData'][0]['id'];

            if(file_exists($rutaFile)) {
                $dataFile = base64_encode(file_get_contents($rutaFile));
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => [],
                    'fileName' => $fileName,
                    'status' => 200,
                ];

                $return = HelperEncryptAes::encrypt($response, true);
                $return['datafile'] = $dataFile;
                return $return;
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'dowloadDocuments') ],
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
    }
}
