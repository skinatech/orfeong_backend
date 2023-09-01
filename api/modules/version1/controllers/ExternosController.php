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

namespace api\modules\version1\controllers;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

use api\components\HelperExternos;
use api\components\HelperFiles;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperIndiceElectronico;

use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\CgTransaccionesRadicados;
use api\models\UserDetalles;
use api\models\User;
use api\models\GdExpedienteDocumentos;
use api\models\GdExpedientesInclusion;
use api\models\GdTrdTiposDocumentales;

use api\modules\version1\controllers\radicacion\DocumentosController;

/**
 * externos controller for the `version1` module
 */
class ExternosController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'authorization-user' => ['GET'],
                    'radicado-create' => ['POST'],
                    'load-document-radicado' => ['POST'],
                ],
            ],
        ];
    }


    public function actionRadicadoCreate()
    {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        if ($dataHelper['Authorization'] && $dataHelper['token']) {

            return $dataHelper['model']->userCgProveedorExterno0;
        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    /** 
     * Se cargan los documentos al sistema ya sea como anexo para el caso de skinascan e imagen principal para el caso del documento firmado 
     * el request deberia tener @idRadicado @tipoTabla
     **/
    public function actionLoadDocumentRadicado($request)
    {

        // $request = HelperEncryptAes::encrypt($request, true);

        if (!empty($request)) {

            // return $request;

            $request = json_decode($request, true);

            // return $decrypted;

            //*** Inicio desencriptación GET ***//
            // $decrypted = HelperEncryptAes::decryptGET($request, true);


            //  if ($decrypted['status'] == true) {
            //  $request = $decrypted['request'];

            //  } else {
            //      Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            //      $response = [
            //          'message' => Yii::t('app', 'errorDesencriptacion'),
            //          'data' => Yii::t('app', 'errorDesencriptacion'),
            //          'status' => Yii::$app->params['statusErrorEncrypt'],
            //      ];
            //      return HelperEncryptAes::encrypt($response, true);
            //  }
            //*** Fin desencriptación GET ***//

            ini_set('memory_limit', '3073741824');
            ini_set('max_execution_time', 900);

            /** Validar si cargo un archivo subido **/
            $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

            if (isset($fileUpload->error) && $fileUpload->error === 1) {

                $uploadMaxFilesize = ini_get('upload_max_filesize');

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
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

                if (!$resultado['ok']) {
                    $orfeoMaxFileSize = $resultado['data']['orfeoMaxFileSize'];

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'orfeoMaxFileSize', [
                            'orfeoMaxFileSize' => $orfeoMaxFileSize
                        ])]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /** Fin de validación */


                //Inicio de transacción en bd 
                $transaction = Yii::$app->db->beginTransaction();

                // Transaccion 
                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachment']);

                // Data del formulario
                // Se valida si $request['dataForm']['tipoArchivo'] es igual a dos para saber que se carga desde skinascan
                if ($request['tipoArchivo'] == 2 or $request['tipoArchivo'] == 1) {

                    $descripcion = 'Carga del documento principal';
                    $isPublicoRadiDocumento = Yii::$app->params['SiNoText']['Si'];

                    if (Yii::$app->user->identity != null) {
                        # Consulta del usuario logueado.
                        $userLogin = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                        $idUser = Yii::$app->user->identity->id;
                    }

                    $arrayTypeRadiOrConsecutive = [];

                    $anio = date('Y');
                    $idRadicado = $request['idRadicado'];

                    $modelRadicado = DocumentosController::findModelRadicado($idRadicado);
                    // return $modelRadicado;

                    if (Yii::$app->user->identity == null) {
                        $userLogin = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => $modelRadicado->idTrdDepeUserCreador])->one();
                        $idUser = $modelRadicado->user_idCreador;
                    }

                    $modelDependencia = DocumentosController::findModelDependencia($modelRadicado->idTrdDepeUserCreador);

                    $codigoDependencia = $modelDependencia->codigoGdTrdDependencia;
                    $numeroRadicado = $modelRadicado->numeroRadiRadicado;

                    # Se va a asignar el tipo documental del radicado, ya no es del formulario.
                    $tipoDocu = $modelRadicado->idTrdTipoDocumental;

                    $pathUploadFile = Yii::getAlias('@webroot')
                        . "/" .  Yii::$app->params['bodegaRadicados']
                        . "/" . $anio
                        . "/" . $codigoDependencia
                        . "/";

                    $modelDocumento = RadiDocumentos::find()
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->orderBy(['numeroRadiDocumento' => SORT_DESC])
                        ->one();

                    $numeroDocumento = 1;
                    if (!empty($modelDocumento)) {
                        $numeroDocumento = (int) $modelDocumento->numeroRadiDocumento + 1;
                    }

                    $modelDocumento = new RadiDocumentos();
                    $modelDocumento->nombreRadiDocumento = $fileUpload->name;
                    $modelDocumento->rutaRadiDocumento = $pathUploadFile;
                    $modelDocumento->extencionRadiDocumento =  $fileUpload->extension;
                    $modelDocumento->idRadiRadicado = $idRadicado;
                    $modelDocumento->idGdTrdTipoDocumental = $tipoDocu;
                    $modelDocumento->descripcionRadiDocumento = $descripcion;
                    $modelDocumento->idUser = $idUser;
                    $modelDocumento->numeroRadiDocumento = $numeroDocumento;

                    $modelDocumento->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                    $modelDocumento->creacionRadiDocumento = date('Y-m-d H:i:s');

                    $modelDocumento->isPublicoRadiDocumento = $isPublicoRadiDocumento;

                    $tamano = $fileUpload->size / 1000;
                    $modelDocumento->tamanoRadiDocumento = '' . $tamano . ' KB';

                    if (!$modelDocumento->save()) {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelDocumento->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    //Se actualiza el nombre de documento ya que se necesita el id que genera a insertar la tabal
                    $nomArchivo = "$numeroRadicado-" . $modelDocumento->idRadiDocumento . ".$fileUpload->extension";
                    $nomArchivo = "$numeroRadicado-" . $numeroDocumento . ".$fileUpload->extension";

                    $modelDocumento->nombreRadiDocumento = $nomArchivo;

                    if (!$modelDocumento->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelDocumento->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    // return $modelDocumento;
                    #Si el radicado esta incluido en un expediente, se agrega el documento recién subido al índice

                    $modelExpedienteInclusion = GdExpedientesInclusion::find([])
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->one();

                    if ($modelExpedienteInclusion != null) {
                        $xml = HelperIndiceElectronico::getXmlParams($modelExpedienteInclusion->gdExpediente);

                        $resultado = HelperIndiceElectronico::addDocumentToIndex($modelDocumento,  $modelExpedienteInclusion, $xml);

                        if (!$resultado['ok']) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => [$resultado['data']['response']['data']],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    if ($numeroDocumento == 1) {
                        $eventLogText = 'FileUploadMain';
                        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachmentMain']);
                    } else {
                        $eventLogText = 'FileUpload';
                        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachment']);
                    }

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        $idUser, //Id user
                        $modelRadicado->idTrdDepeUserCreador, // Id dependencia
                        $modelDocumento->idRadiRadicado, //Id radicado
                        $idTransacion->idCgTransaccionRadicado,
                        Yii::$app->params['eventosLogTextRadicado'][$eventLogText] . $modelDocumento->nombreRadiDocumento . ', con el nombre de: ' . $fileUpload->name . ', y su descripción: ' . $descripcion,
                        $modelDocumento,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/

                    $rutaOk = true;

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
                            'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Validar creación de la carpeta***/

                    $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo, false);

                    if ($uploadExecute) {

                        $gdTrdTiposDocumentales = GdTrdTiposDocumentales::find()->select(['nombreTipoDocumental'])->where(['idGdTrdTipoDocumental' => $modelDocumento->idGdTrdTipoDocumental])->one();

                        $RadiDocumentos = new RadiDocumentos();
                        $attributeLabels = $RadiDocumentos->attributeLabels();

                        $dataOld = '';
                        $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDocumento->idRadiDocumento
                            . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDocumento->nombreRadiDocumento
                            . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDocumento->descripcionRadiDocumento
                            . ', Tipo Documental: ' . $gdTrdTiposDocumentales['nombreTipoDocumental'];

                        if ($modelRadicado->isRadicado == true || $modelRadicado->isRadicado == 1) {
                            $typeRadiOrConsecutivo = ', al radicado: ';
                            $arrayTypeRadiOrConsecutive[] = 'radicado';
                        } else {
                            $typeRadiOrConsecutivo = ', al consecutivo: ';
                            $arrayTypeRadiOrConsecutive[] = 'consecutivo';
                        }

                        $modelUsuario = User::findOne(['id' => $idUser]);
                        $userLogin = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => $modelUsuario->id])->one();
                        // return $userLogin;

                        /*** log Auditoria ***/
                        HelperLog::logAdd(
                            true,
                            $modelUsuario->id, //Id user
                            $modelUsuario->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText'][$eventLogText] . ' con el nombre: ' . $fileUpload->name . $typeRadiOrConsecutivo .  $modelRadicado->numeroRadiRadicado . ' por el usuario: ' . $userLogin['nombreUserDetalles'] . ' ' . $userLogin['apellidoUserDetalles'], // texto para almacenar en el evento
                            $dataOld,
                            $dataNew, //Data
                            array() //No validar estos campos
                        );
                        /***  Fin log Auditoria   ***/
                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $radicadosProcesados[] = $modelRadicado->numeroRadiRadicado;

                    if (count($radicadosProcesados) > 1) {

                        if (in_array('radicado', $arrayTypeRadiOrConsecutive) && in_array('consecutivo', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMultiAll';
                        } elseif (in_array('consecutivo', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMultiConsecutivo';
                        } elseif (in_array('radicado', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMulti';
                        }
                        $message = Yii::t('app', $varI18n, ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
                    } else {
                        if (in_array('radicado', $arrayTypeRadiOrConsecutive)) {
                            $message = Yii::t('app', 'successUpLoadFileRadiOne', ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
                        } else {
                            $message = Yii::t('app', 'successUpLoadFileRadiOneTmp', ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
                        }
                    }

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $message,
                        'data' => [],
                        'status' => 200
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                    'data' => ['error' => [Yii::t('app', 'canNotUpFile')]],
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
