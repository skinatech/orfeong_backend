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
use api\models\InitCgConfigFirmas;
use api\models\InitCgEntidadesFirma;
use api\models\InitCgParamsFirma;
use api\models\CgFirmasCertificadas;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;



/**
 * InitPrincipalFirmaController controlador principal que redirecciona al controlador correspondiente segun entidad de firma
 * digital que se utilice.
 */
class InitPrincipalFirmaController extends Controller
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
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index'  => ['GET'],
                    'index-one'  => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'update'  => ['PUT'],
                    'change-status'  => ['PUT'],
                    'process-sign'  => ['POST'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /** Lista los nombres de las entidades de firmas certificadas */
    public function actionListCertifyingEntities()
    {

        $dataEntidades = [];
        $modelEntidades = InitCgEntidadesFirma::findAll(['estadoInitCgEntidadFirma' => Yii::$app->params['statusTodoText']['Activo']]);

        foreach ($modelEntidades as $row) {
            $dataEntidades[] = array(
                "id" => $row['idInitCgEntidadFirma'],
                "val" => $row['nombreInitCgEntidadFirma'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataClientes' => $dataEntidades, // Listado de clientes
            'status' => 200,
        ];
        return $response;
    }

    /** Lista las variables a utilizar para realizar la configuración */
    public function actionListCertifyingParams()
    {

        $modelParams = [];
        $modelParams = InitCgParamsFirma::findAll(['estadoInitCgParamFirma' => Yii::$app->params['statusTodoText']['Activo']]);

        foreach ($modelParams as $row) {
            $modelParams[] = array(
                "id" => $row['idInitCgParamFirma'],
                "val" => $row['variableInitCgParamFirma'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataClientes' => $modelParams, // Listado de clientes
            'status' => 200,
        ];
        return $response;
    }

    public function actionCreateConfigFirmas()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');

        /** 
         * Se debe crear la estructura de json que se va a solicitar, esta estructura debe tener la clave y el valor de los parametros a ingresar
         * idInitCgParamFirma es un arreglo y valorInitCgConfigFirma otro arreglo... ambos deben tener la misma cantidad de registros y se debe
         * recorrer primero el de idInitCgParamFirma y luego valorInitCgConfigFirma para asignar el valor
         */

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

            # Creacion de registro tabla motivos de devolucion 
            $modelCgConfigFirmas = new initCgConfigFirmas();
            $modelCgConfigFirmas->attributes = $request;

            if ($modelCgConfigFirmas->save()) {

                $transaction->commit();

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgConfigFirmas", //texto para almacenar en el evento
                    [],
                    [$modelCgConfigFirmas], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

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
                    'data' => $modelCgConfigFirmas->getErrors(),
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


    /** Transacción para mostrar las diferentes opciones de firmado digital que tiene el sistema */
        /**
     *  Transaccion  para pasar un documento combinado sin firmas a firmado, asignación de radicado y conversión a pdf
     * 
     * @param array   ButtonSelectedData 
     * 
     * @return array message success 
     */
    public function actionSignDocumentOptions(){
            
        $jsonSend = Yii::$app->request->post('jsonSend');
        $opcionesFirmaDisponibles = [];
        $opcionesAdicionalesfirmas = [];

        if(!empty($jsonSend)){ 

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

            $modelFirmasCertificadas = InitCgEntidadesFirma::findAll(['estadoInitCgEntidadFirma' => Yii::$app->params['statusTodoText']['Activo']]);
            
            foreach($modelFirmasCertificadas as $opcionFirma){

                $opcionesFirmaDisponibles[] = $opcionFirma->nombreCgFirmaCertificada;
                
                // Consulta la información de configuración de cada entidad de firma
                $initCgConfigFirmas = InitCgConfigFirmas::findAll(['idInitCgEntidadFirma' => $opcionFirma->idInitCgEntidadFirma]);

                foreach($initCgConfigFirmas as $valorParams){

                    // Consulta el valor de cada tipo de parametro
                    $initCgParamsFirma = InitCgParamsFirma::findOne(['idInitCgParamFirma' => $valorParams->idInitCgParamFirma]);
                    $opcionesAdicionalesfirmas[$opcionFirma->nombreCgFirmaCertificada] = array($initCgParamsFirma->variableInitCgParamFirma => $valorParams->valorInitCgConfigFirma);
                }
            }
            
            $response = [
                'message' => [],
                'data' => $opcionesFirmaDisponibles ?? [],
                'dataAdicional' => $opcionesAdicionalesfirmas ?? [],
                'status' => 200,
            ];

            return $response;

            Yii::$app->response->statusCode = 200;
            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }     
    }

    public function actionProcessSign(){
        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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
                return $request;
            }
    }
}
