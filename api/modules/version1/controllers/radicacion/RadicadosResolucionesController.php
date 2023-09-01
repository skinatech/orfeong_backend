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
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\models\RadiRadicadosResoluciones;
use api\models\CgTransaccionesRadicados;
use api\models\CgGeneral;
use api\models\CgTiposRadicadosResoluciones;

class RadicadosResolucionesController extends Controller
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

    public function actionIndexOne($request) {
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

        $fechaInicialResoluciones = date("Y") . "-01-01 00:00:00";
        $fechaFinalResoluciones = date("Y") . "-12-31 23:59:59";
        $countResoluciones = RadiRadicadosResoluciones::find()
            ->andWhere(['BETWEEN', 'creacionRadiRadicadoResolucion', $fechaInicialResoluciones, $fechaFinalResoluciones])
            ->count();
        $modelCgGeneral = CgGeneral::find()
            ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();
        $modelCgTiposRadicadosResoluciones = CgTiposRadicadosResoluciones::find()
            ->where(['idCgTipoRadicado' => $modelCgGeneral->resolucionesIdCgGeneral])
            ->one();
        if ($modelCgTiposRadicadosResoluciones) {
            $modelRadicadosResolucion = RadiRadicadosResoluciones::findOne(['idRadiRadicado' => $request['idRadiRadicado']]);
            if ($modelRadicadosResolucion) {
                $data = array(
                    'idResoluciones' => $modelRadicadosResolucion->idRadiRadicadoResoluciones,
                    'numeroResolucion' => $modelRadicadosResolucion->numeroRadiRadicadoResolucion,
                    'fechaResolucion' => $modelRadicadosResolucion->fechaRadiRadicadoResolucion . 'T01:00:00',
                    'valorResolucion' => $modelRadicadosResolucion->valorRadiRadicadoResolucion
                );
            } else {
                $data = array(
                    'idResoluciones' => 0,
                    'numeroResolucion' => ($modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones + $countResoluciones),
                    'fechaResolucion' => '',
                    'valorResolucion' => ''
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'theResolutionInformationIsNotConfiguredPleaseContactTheAdministrator'),
                'data' => ['error' => Yii::t('app', 'theResolutionInformationIsNotConfiguredPleaseContactTheAdministrator')],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionCreateUpdate() {
        $errors = [];

        $jsonSend = Yii::$app->request->post('jsonSend');
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

            $transaction = Yii::$app->db->beginTransaction();

            if ($request['idRadiRadicadoResoluciones'] > 0) {
                $modelRadicadosResoluciones = RadiRadicadosResoluciones::findOne(['idRadiRadicado' => $request['idRadiRadicado'], 'idRadiRadicadoResoluciones' => $request['idRadiRadicadoResoluciones']]);
                $modelRadicadosResoluciones->attributes = $request;
                $modelRadicadosResoluciones->fechaRadiRadicadoResolucion = date("Y-m-d", strtotime($request['fechaRadiRadicadoResolucion']));

            } else {
                $fechaInicialResoluciones = date("Y") . "-01-01 00:00:00";
                $fechaFinalResoluciones = date("Y") . "-12-31 23:59:59";

                $countResoluciones = RadiRadicadosResoluciones::find()
                    ->andWhere(['BETWEEN', 'creacionRadiRadicadoResolucion', $fechaInicialResoluciones, $fechaFinalResoluciones])
                    ->count();
                $modelCgGeneral = CgGeneral::find()
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();
                $modelCgTiposRadicadosResoluciones = CgTiposRadicadosResoluciones::find()
                    ->where(['idCgTipoRadicado' => $modelCgGeneral->resolucionesIdCgGeneral])
                    ->one();

                $modelRadicadosResoluciones = new RadiRadicadosResoluciones();
                $modelRadicadosResoluciones->attributes = $request;
                $modelRadicadosResoluciones->numeroRadiRadicadoResolucion = ($modelCgTiposRadicadosResoluciones->numeracionCgTiposRadicadosResoluciones + $countResoluciones);
                $modelRadicadosResoluciones->fechaRadiRadicadoResolucion = date("Y-m-d", strtotime($request['fechaRadiRadicadoResolucion']));
            }

            if ($modelRadicadosResoluciones->save()) {

                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'createResolutionDetail']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;
                $observacion = Yii::$app->params['eventosLogTextRadicado']['createDetailResolution'].' Número resolución: '.$modelRadicadosResoluciones->numeroRadiRadicadoResolucion .', Fecha resolución: '. $modelRadicadosResoluciones->fechaRadiRadicadoResolucion .', Valor resolución: '. $modelRadicadosResoluciones->valorRadiRadicadoResolucion;

                HelperLog::logAddFiling(
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                    $modelRadicadosResoluciones->idRadiRadicado, //Id radicado
                    $idTransacion,
                    $observacion, //texto para almacenar en el evento
                    $modelRadicadosResoluciones->idRadiRadicado0,
                    array() //No validar estos campos
                );

                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacion,
                    [],
                    [], //Data
                    array() //No validar estos campos
                );

                $transaction->commit();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'data' => $modelRadicadosResoluciones,
                    'status' => 200
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $modelRadicadosResoluciones->getErrors(),
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
