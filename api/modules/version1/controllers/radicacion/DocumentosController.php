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
use yii\data\ActiveDataProvider;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

use yii\web\NotFoundHttpException;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperGenerateExcel;
use api\components\HelperLoads;
use api\components\HelperExtraerTexto;
use api\components\HelperIndiceElectronico;
use api\components\HelperFiles;

use api\models\RadiRadicados;
use api\models\RadiRemitentes;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\CgTransaccionesRadicados;
use api\models\RadiEnvios;
use api\models\GdTrd;
use api\models\GdTrdDependencias;
use api\models\User;
use api\models\GdTrdTiposDocumentales;
use api\models\UserDetalles;
use api\models\Clientes;

use api\components\HelperQueryDb;
use api\models\GdExpedienteDocumentos;
use api\models\GdExpedientesInclusion;

use api\models\PDFInfo;
use api\models\RadiDetallePqrsAnonimo;
/**
 * DocumentosController.
 */
class DocumentosController extends Controller
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
                    'download-document' => ['PUT', 'POST'],
                    'download-doc-principal' => ['PUT', 'POST'],
                    'download-doc-principal-firma-digital' => ['PUT', 'POST'],
                    'download-document-cor' => ['PUT', 'POST'],
                    'upload-signed-document' => ['POST'],
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
     * Listado del tipo documental, 
     * 1) Se utiliza en la carga de documentos manuales y 
     *    depende de la dependencia, serie y subserie que tenga el expediente.
     * 2) Se utiliza en otros modulos.
     */
    public function actionIndexList($request){

        $data = [];  

        if (!empty($request)) {
            
            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GETS***//

            # Consulta los tipos documentales que tiene la combinación de la TRD del expediente
            $modelTipoDoc = GdTrd::find()
                ->where(['idGdTrdDependencia' => $request['idGdTrdDependencia']])
                ->andWhere(['idGdTrdSerie' => $request['idGdTrdSerie']])
                ->andWhere(['idGdTrdSubserie' => $request['idGdTrdSubserie']])
                ->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        } else {
            # Consulta los tipos documentales que tiene el perfil 
            $modelTipoDoc = GdTrdTiposDocumentales::find();
                //->innerJoin('rolesTipoDocumental', '`gdTrdTiposDocumentales`.`idGdTrdTipoDocumental` = `rolesTipoDocumental`.`idGdTrdTipoDocumental`')
                $modelTipoDoc = HelperQueryDb::getQuery('innerJoin', $modelTipoDoc, 'rolesTipoDocumental', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'rolesTipoDocumental' => 'idGdTrdTipoDocumental']);
                $modelTipoDoc = $modelTipoDoc->where(['rolesTipoDocumental.idRol' => Yii::$app->user->identity->idRol])
            ->all();
        }


        foreach($modelTipoDoc as $row){
            $data[] = array(
                "id" => (int) $row->idGdTrdTipoDocumental,
                "val" => $row->gdTrdTipoDocumental->nombreTipoDocumental,
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


    public function actionUploadDocument($request) {

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

                ini_set('memory_limit', '3073741824');
                ini_set('max_execution_time', 900);

                /** Validar si cargo un archivo subido **/
                $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

                if(isset($fileUpload->error) && $fileUpload->error === 1){    
                    
                    // return $fileUpload;

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


                    //Inicio de transacción en bd 
                    $transaction = Yii::$app->db->beginTransaction();

                    // Transaccion 
                    $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'attachment']);

                    // Data del formulario
                    //$tipoDocu = $request['dataForm']['idTipoDocumental'];
                    $descripcion = $request['dataForm']['observacion'];
                    $isPublicoRadiDocumento = ($request['dataForm']['isPublicoRadiDocumento'] === true) ? Yii::$app->params['SiNoText']['Si'] : Yii::$app->params['SiNoText']['No'];

                    # Consulta del usuario logueado.
                    $userLogin = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();

                    $arrayTypeRadiOrConsecutive = [];

                    foreach ($request['ButtonSelectedData'] as $key => $item) {

                        $anio = date('Y');
                        $idRadicado = $item['id']; 

                        $modelRadicado = self::findModelRadicado($idRadicado);

                        $modelDependencia = self::findModelDependencia($modelRadicado->idTrdDepeUserCreador);

                        $codigoDependencia = $modelDependencia->codigoGdTrdDependencia;
                        $numeroRadicado = $modelRadicado->numeroRadiRadicado;  
                        //$idRadicado = $modelRadicado->idRadiRadicado;

                        # Se va a asignar el tipo documental del radicado, ya no es del formulario.
                        $tipoDocu = $modelRadicado->idTrdTipoDocumental;
                       
                        $pathUploadFile = Yii::getAlias('@webroot')                            
                            . "/" .  Yii::$app->params['bodegaRadicados']
                            . "/" . $anio                            
                            . "/" . $codigoDependencia
                            . "/"
                        ;
                        
                        $modelDocumento = RadiDocumentos::find()
                            ->where(['idRadiRadicado' => $idRadicado ])
                            ->orderBy(['numeroRadiDocumento' => SORT_DESC])
                            ->one();
                       
                        $numeroDocumento = 1;
                        if(!empty($modelDocumento)){
                            $numeroDocumento = (int) $modelDocumento->numeroRadiDocumento + 1;
                        }

                        $modelDocumento = new RadiDocumentos();
                        $modelDocumento->nombreRadiDocumento = $fileUpload->name;                       
                        $modelDocumento->rutaRadiDocumento = $pathUploadFile;                   
                        $modelDocumento->extencionRadiDocumento =  $fileUpload->extension;
                        $modelDocumento->idRadiRadicado = $idRadicado;
                        $modelDocumento->idGdTrdTipoDocumental = $tipoDocu;
                        $modelDocumento->descripcionRadiDocumento = $descripcion;
                        $modelDocumento->idUser = Yii::$app->user->identity->id;
                        $modelDocumento->numeroRadiDocumento = $numeroDocumento;
                        
                        $modelDocumento->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                        $modelDocumento->creacionRadiDocumento = date('Y-m-d H:i:s');

                        $modelDocumento->isPublicoRadiDocumento = $isPublicoRadiDocumento;

                        $tamano = $fileUpload->size / 1000;
                        $modelDocumento->tamanoRadiDocumento = '' . $tamano . ' KB';

                        if(!$modelDocumento->save()){    

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

                        if(!$modelDocumento->save()) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelDocumento->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }


                        #Si el radicado esta incluido en un expediente, se agrega el documento recién subido al índice

                        $modelExpedienteInclusion = GdExpedientesInclusion::find([])
                            ->where(['idRadiRadicado' => $idRadicado])
                        ->one();

                        if($modelExpedienteInclusion != null){
                            $xml = HelperIndiceElectronico::getXmlParams($modelExpedienteInclusion->gdExpediente);
                               
                            $resultado = HelperIndiceElectronico::addDocumentToIndex($modelDocumento,  $modelExpedienteInclusion, $xml);

                            if(!$resultado['ok']){
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
                            $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'attachmentMain']);
                        } else {
                            $eventLogText = 'FileUpload';
                            $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'attachment']);
                        }

                        /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $modelDocumento->idRadiRadicado, //Id radicado
                            $idTransacion->idCgTransaccionRadicado,
                            Yii::$app->params['eventosLogTextRadicado'][$eventLogText] . $modelDocumento->nombreRadiDocumento . ', con el nombre de: ' .$fileUpload->name. ', y su descripción: '. $descripcion,
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
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar creación de la carpeta***/                       

                        $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo, false);
                        
                        if ($uploadExecute) {

                            // /**
                            //  * Se valida si de los archivos cargados alguno pertenece a archivo de texto aplicar OCR de lo contrario
                            //  * no aplicar OCR ya que es un formato no extraible con el metodo utilizado
                            //  **/

                            //     ///////////////////////// EXTRAER TEXTO  ////////////////////////
                            //     $helperExtraerTexto = new HelperExtraerTexto($pathUploadFile . $nomArchivo);

                            //     if(!empty($helperExtraerTexto)){

                            //         $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                            //             $modelDocumento->idRadiDocumento,    
                            //             Yii::$app->params['tablaOcrDatos']['radiDocumentos']  
                            //         );

                            //         if($helperOcrDatos['status'] != true){
                            //             Yii::$app->response->statusCode = 200;
                            //             $response = [
                            //                 'message' => $helperOcrDatos['message'],
                            //                 'data'    => $helperOcrDatos['data'],
                            //                 'status'  => Yii::$app->params['statusErrorValidacion'],
                            //             ];
                            //             return HelperEncryptAes::encrypt($response, true);
                            //         }
                            //     }
                            //     ///////////////////////// EXTRAER TEXTO  ////////////////////////

                            $gdTrdTiposDocumentales = GdTrdTiposDocumentales::find()->select(['nombreTipoDocumental'])->where(['idGdTrdTipoDocumental' => $modelDocumento->idGdTrdTipoDocumental])->one();
                            
                            $RadiDocumentos = new RadiDocumentos();
                            $attributeLabels = $RadiDocumentos->attributeLabels();
                            
                            $dataOld = '';
                            $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDocumento->idRadiDocumento
                                . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDocumento->nombreRadiDocumento
                                . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDocumento->descripcionRadiDocumento
                                . ', Tipo Documental: ' . $gdTrdTiposDocumentales['nombreTipoDocumental']
                            ;

                            if ($modelRadicado->isRadicado == true || $modelRadicado->isRadicado == 1) {
                                $typeRadiOrConsecutivo = ', al radicado: ';
                                $arrayTypeRadiOrConsecutive[] = 'radicado';
                            } else {
                                $typeRadiOrConsecutivo = ', al consecutivo: ';
                                $arrayTypeRadiOrConsecutive[] = 'consecutivo';
                            }

                            /*** log Auditoria ***/        
                            HelperLog::logAdd(
                                true,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText'][$eventLogText] . ' con el nombre: '.$fileUpload->name. $typeRadiOrConsecutivo .  $modelRadicado->numeroRadiRadicado . ' por el usuario: ' . $userLogin['nombreUserDetalles'] . ' ' . $userLogin['apellidoUserDetalles'], // texto para almacenar en el evento
                                $dataOld,
                                $dataNew, //Data
                                array() //No validar estos campos
                            );
                            /***  Fin log Auditoria   ***/                                               
                            
                        } else {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $radicadosProcesados[] = $modelRadicado->numeroRadiRadicado;

                    }

                    if (count($radicadosProcesados) > 1) {

                        if (in_array('radicado', $arrayTypeRadiOrConsecutive) && in_array('consecutivo', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMultiAll';
                        } elseif (in_array('consecutivo', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMultiConsecutivo';
                        } elseif (in_array('radicado', $arrayTypeRadiOrConsecutive)) {
                            $varI18n = 'successUpLoadFileRadiMulti';
                        }
                        $message = Yii::t('app',$varI18n, ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
                    } else {
                        if (in_array('radicado', $arrayTypeRadiOrConsecutive)) {
                            $message = Yii::t('app','successUpLoadFileRadiOne', ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
                        } else {
                            $message = Yii::t('app','successUpLoadFileRadiOneTmp', ['nombreArchivo' => $fileUpload->name, 'radiString' => implode(", ", $radicadosProcesados)]);
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
                                        
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
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

    protected function findModel($id)
    {
        if (($model = RadiDocumentos::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    public static function findModelRadicado($id){
        if (($model = RadiRadicados::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));    
    }

    protected function findModelTrd($id){
        if (($model = GdTrd::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));    
    }

    public static function findModelDependencia($id){
        if (($model = GdTrdDependencias::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    /** 
     * Funcion que permite descargar el anexo que o el documento del radicado
     **/
    public function actionDownloadDocument() 
    {
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);        
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            //$response = ['id' => [7,2] ];
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

                foreach ($request['ButtonSelectedData'] as $key => $item) {

                    $idDocumento = $item['id'];                    
                    $modelDocumento = RadiDocumentos::findOne(['idRadiDocumento' => $idDocumento]);

                    // Nombre del archivo para generar
                    $fileName = $modelDocumento->nombreRadiDocumento;
                    $rutaFile = $modelDocumento->rutaRadiDocumento.'/'.$fileName;

                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {
                        //Lee el archivo dentro de una cadena en base 64
                        $dataFile = base64_encode(file_get_contents($rutaFile));
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => [],
                            'data' => [], // data
                            'fileName' => $fileName, //filename
                            'status' => 200,
                        ];                        
                    
                        $return = HelperEncryptAes::encrypt($response, true);
                        
                        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
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
                }
            }
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

    /** 
     * Funcion que permite descargar el anexo que o el documento del radicado
     **/
    public function actionDownloadDocumentCor() 
    {
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);        
        
        if (HelperValidatePermits::validateUserPermits( Yii::$app->params['permissionsRadiCorresDownload'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                foreach ($request['ButtonSelectedData'] as $key => $item) {

                    $idDocumento = $item['id'];                    
                    $modelDocumento = RadiEnvios::findOne(['idRadiEnvio' => $idDocumento]);

                    // Nombre del archivo para generar
                    $rutaFile = $modelDocumento->rutaRadiEnvio;
                    $file = $modelDocumento['numeroGuiaRadiEnvio'].'.'.$modelDocumento['extensionRadiEnvio'];

                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {
                        //Lee el archivo dentro de una cadena en base 64
                        $dataFile = base64_encode(file_get_contents($rutaFile));
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => [],
                            'data' => [], // data
                            'fileName' => Yii::$app->params['guiaName'].$file,
                            'status' => 200,
                        ];                        
                        
                        $return = HelperEncryptAes::encrypt($response, true);
                        
                        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                        $return['datafile'] = $dataFile;
                        
                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['DownloadFile'].' de correspondencia con el nombre: '.Yii::$app->params['guiaName'].$file, //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        return $return;

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'downloadCorrespon') ],                        
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
            }
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

      /** 
     * Funcion que permite descargar el anexo que o el documento del radicado
     **/
    public function actionDownloadDocPrincipal() 
    {
  
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);   
        
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsRadiCorresDownload'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $dataFile = []; $consultaAvanzada = false;

                if(!isset($request['data'])){
                    $request['data'] = $request['ButtonSelectedData'];
                    $consultaAvanzada = true;
                }

                foreach ($request['data'] as $key => $item) {

                    $idDocumento = $item['id'];
                    $modelDocumento = RadiDocumentosPrincipales::find()->where(['idradiDocumentoPrincipal' => $idDocumento])->one();

                    $radicado = RadiRadicados::find()->select(['numeroRadiRadicado'])->where(['idRadiRadicado' => $modelDocumento->idRadiRadicado])->one();

                    // Nombre del archivo para generar
                    $rutaFile = $modelDocumento->rutaRadiDocumentoPrincipal;
                    $file = $modelDocumento['nombreRadiDocumentoPrincipal'].'.'.$modelDocumento['extensionRadiDocumentoPrincipal'];

                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {   
                        # En consulta avanzada se retorna un solo documento y no en forma de array
                        if($consultaAvanzada){

                            //$dataFile = base64_encode(file_get_contents($rutaFile));
                            $explode = explode('/var/www/html', $rutaFile);
                            //$dataFile = base64_encode($explode[1]);
                             $dataFile = base64_encode(file_get_contents($rutaFile));


                        }else{
                            
                            //Lee el archivo dentro de una cadena en base 64
                            $dataFile[] = array( 
                                'datafile' => base64_encode(file_get_contents($rutaFile)),
                                'fileName' => $file
                            );
                        }
                                        
                        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                        // $dataFile[] = $dataFile;
                        
                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['DownloadFile'].' de documentos principales  del radicado No: '.$radicado['numeroRadiRadicado'].' con el nombre: '.$file, //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'downloadCorrespon') ],                        
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => [], // data
                    'fileName' => $file,
                    'status' => 200,
                ];       

                $return = HelperEncryptAes::encrypt($response, true);

                $return['datafile'] = $dataFile;

                return $return;
 
            }
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
    /** 
     * sguarin Firma Digital Funcion que permite descargar el anexo que o el documento del radicado para poner firma digital
     **/
    public function actionDownloadDocPrincipalFirmaDigital() 
    {
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);   
        
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsRadiCorresDownload'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $dataFile = []; $consultaAvanzada = false;

                if(!isset($request['data'])){
                    $request['data'] = $request['ButtonSelectedData'];
                    $consultaAvanzada = true;
                }

                foreach ($request['data'] as $key => $item) {

                    $idDocumento = $item['id'];
                    $modelDocumento = RadiDocumentosPrincipales::find()->where(['idradiDocumentoPrincipal' => $idDocumento])->one();

                    $radicado = RadiRadicados::find()->select(['numeroRadiRadicado'])->where(['idRadiRadicado' => $modelDocumento->idRadiRadicado])->one();
                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $modelDocumento->idRadiRadicado])->one();


                    $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signDocument']);
                    $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                    // Nombre del archivo para generar
                    $rutaFile = $modelDocumento->rutaRadiDocumentoPrincipal;
                    $file = $modelDocumento['nombreRadiDocumentoPrincipal'].'.'.$modelDocumento['extensionRadiDocumentoPrincipal'];
                    // $rutaFirmaDigitalAlterna = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeFirmaDigitalOrfeo'].'/'.'prueba.p12';
                    // 'I234s678'


                    // $rutaFirmaDigital = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeFirmaDigitalOrfeo'].'/'.'orfeong-server.p12';
                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {   

                        //php_sign_generate(rutaEntrada, rutaSalida, firma, contraseña);
                        // podofosign_php($rutaFilePrueba, $rutaFilePrueba, $rutaFirmaDigital,'');

                        function format($fecha) {
                            $year = substr($fecha, 2, 4);
                            $month = substr($fecha, 6, 2);
                            $day = substr($fecha, 8, 2);
                            $hour = substr($fecha, 10, 2);
                            $min = substr($fecha, 12, 2);
                            $seg = substr($fecha, 14, 2);
                            return $year.'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$seg;
                        }
                        
                        function getOID($OID, $ssl) {
                            preg_match('/\/' . $OID  . '=([^\/]+)/', $ssl, $matches);
                            return $matches[1];
                        }

                        //Extrae la información del archivo pdf 
                        $pdf = new PDFInfo($rutaFile);
                        // $pdf = new PDFInfo("/home/sguarin/Descargas/MEMORANDO_RECOMENDACIÓN_DE_CONTRATACION_IMPRESORAS_ZEBRA_2021_3_.pdf");

                        //Fecha de creación del archivo
                        $fechaEpochCreacionArchivo = strtotime($pdf->creationDate);

                        //Número de páginas del archivo
                        $numeroPaginasArchivo =  (int) $pdf->pages;
            
                        // //extraigo informacion
                        // $infofirma= pdfsig_php($rutaFile);
        
                        // //Lo convierto json en un arreglo
                        // $json = json_decode($infofirma);

                        /**FirmaDigital validación con firma digital del orfeo (por si despues se reemplaza por la solución temporal)
                        // if(count($json)>0){

                        //     //Validar que todas las firmas sean validas, antes de proceder a guardarlas
                        //     for ($i = 0; $i < count($json); $i++) {
    
                        //         $certificado=$json[$i]->certificados[0]->certificado;
                        //         //    echo "Certificado: ".$certificado;
                        //         $infocert=openssl_x509_parse($certificado);
    
                        //         // echo "Estado: ".$json[$i]->estadoFirma."\n";
                        //         // echo "Empresa: ".$infocert['subject']['O']."\n";
                        //         // echo "Firmante: ".$infocert['subject']['CN']."\n";
                        //         // // echo "Cargo: ".$infocert['subject']['title']."\n";
                        //         // echo "El correo del firmante: ".$infocert['subject']['emailAddress']."\n";
                        //         // echo "";
                        //         // // echo "Direccion del emisor es: ".$infocert['issuer']['street']."\n";
                        //         // // echo "Servicios OCSP: ".$infocert['extensions']['authorityInfoAccess']."\n";
                        //         // // echo "Servicios CRL: ".$infocert['extensions']['crlDistributionPoints']."\n";
                        //         // echo "";
                        //         // echo "Fecha firmado:".format($json[$i]->fechaFirma)."\n";
                        //         // echo "Fecha vence:".date("Y-m-d H:i:s",$infocert['validTo_time_t'])."\n";
                        //         // echo "Fecha EPOCH: ".strtotime(format($json[$i]->fechaFirma))."\n";
                        //         // echo "HASH: ".$json[$i]->hash;
    
                        //         $estado=$json[$i]->estadoFirma;
                        //         $empresa=$infocert['subject']['O'];
                        //         $firmante=$infocert['subject']['CN'];
                        //         $hash = $json[$i]->hash;
                        //         $fechaEpochCreacion = strtotime(format($json[$i]->fechaFirma));
    
                        //     }

                        // } else {
                        //     Yii::$app->response->statusCode = 200;
                        //     $response = [
                        //         'message' => Yii::t('app','errorNoGeneracionFirmaDigital'),
                        //         'data' => ['error' => [Yii::t('app', 'errorNoGeneracionFirmaDigital')]],
                        //         'status' => Yii::$app->params['statusErrorValidacion'],
                        //     ];
                        //     return HelperEncryptAes::encrypt($response, true);
                        // }

                        */

                        # En consulta avanzada se retorna un solo documento y no en forma de array
                        if($consultaAvanzada){

                            $dataFile = base64_encode(file_get_contents($rutaFile));

                        }else{
                            
                            //Lee el archivo dentro de una cadena en base 64
                            $dataFile[] = array( 
                                'datafile' => base64_encode(file_get_contents($rutaFile)),
                                'fileName' => $file
                            );
                        }

                        //Cambio de estado a 'En proceso de firma digital'
                        $modelDocumento->estadoRadiDocumentoPrincipal    = Yii::$app->params['statusDocsPrincipales']['enProcesoFirmaDigital'];
                        // $modelDocumento->fechaEpochCreacion = $fechaEpochCreacion;
                        // $modelDocumento->hash = $hash;
                        $modelDocumento->fechaEpochCreacionArchivo = $fechaEpochCreacionArchivo;
                        $modelDocumento->paginas = $numeroPaginasArchivo;
                        $modelDocumento->save();
                                        
                        /***    Log de radicados en proceso de firma digital  ***/
                        $nombreValido = trim(strtoupper($modelDocumento->nombreRadiDocumentoPrincipal)); 
                        $observacion2 = 'El documento'.' '.$nombreValido.' con el radicado '.$radicado['numeroRadiRadicado'].', se encuentra en el proceso de firmado digital.';

                        /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $Radicados->idRadiRadicado, //Id radicado //check
                            $idTransacion, //
                            $observacion2, //texto para almacenar en el evento
                            $Radicados, //
                            array() //No validar estos campos
                        );
                        /***  Fin  Log de Radicados  ***/ 
                        
                        $rutaModulo = 'version1/radicacion/documentos/download-doc-principal';
                        //control de log del sistema
                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            $rutaModulo, //Modulo
                            Yii::$app->params['eventosLogText']['DownloadFile'].' de documentos principales  del radicado No: '.$radicado['numeroRadiRadicado'].' con el nombre: '.$file. ' para proceso de firma digital.', //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'downloadCorrespon') ],                        
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => [], // data
                    'fileName' => $file,
                    'status' => 200,
                ];       

                $return = HelperEncryptAes::encrypt($response, true);

                $return['datafile'] = $dataFile;

                return $return;
 
            }
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


    public function actionDownloadDocExpedientes() 
    {
  
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);   
        
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsRadiCorresDownload'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $dataFile = [];$consultaAvanzada = false;

                if(!isset($request['data'])){
                    $request['data'] = $request['ButtonSelectedData'];
                    $consultaAvanzada = true;
                }
                
                foreach ($request['data'] as $key => $item) {

                    $idDocumento = $item['id'];
                    $modelDocumento = GdExpedienteDocumentos::find()->where(['idGdExpedienteDocumento' => $idDocumento])->one();

                    // Nombre del archivo para generar
                    $file = $modelDocumento['nombreGdExpedienteDocumento'];
                    $rutaFile = $modelDocumento->rutaGdExpedienteDocumento.$file;

                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {   

                         # En consulta avanzada se retorna un solo documento y no en forma de array
                        if($consultaAvanzada){

                            $dataFile = base64_encode(file_get_contents($rutaFile));

                        }else{        
                            //Lee el archivo dentro de una cadena en base 64
                            $dataFile[] = array( 
                                'datafile' => base64_encode(file_get_contents($rutaFile)),
                                'fileName' => $file
                            );
                        }

                                        
                        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                        // $dataFile[] = $dataFile;
                        
                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['DownloadFile'].' de documentos  del expediente No: '.$modelDocumento['numeroGdExpedienteDocumento'].' con el nombre: '.$file, //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'downloadCorrespon') ],                        
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => [], // data
                    'fileName' => $file,
                    'status' => 200,
                ];       

                $return = HelperEncryptAes::encrypt($response, true);

                $return['datafile'] = $dataFile;

                return $return;
 
            }
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

    /**
     * Action que permite enviar la información del radicado, incluyendo los documentos
     * al remitente que se este relacionado en el radicado
     */
    public function actionGetDocumentsSendReplyMail($request)
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

        $correo = '';
        $idRadicado = $request[0]['id'];

        $dataRemitente = RadiRemitentes::findOne(['idRadiRadicado' => $idRadicado]);

        /*** Logs de radicado y auditoría ***/
        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

            # Se obtiene la información del cliente
            $modelRemitente = Clientes::find()->select(['correoElectronicoCliente'])->where(['idCliente' => $dataRemitente->idRadiPersona])->one();
            
            // Si el remitente es igual al usuario anonimo quiere decir que se debe consultar el detalle de al pqrs
            if(isset(Yii::$app->params['idRemitenteAnonimo']) && $dataRemitente->idRadiPersona == Yii::$app->params['idRemitenteAnonimo']){
                $radiDetallePqrAnonimo = RadiDetallePqrsAnonimo::findOne(['idRadiRadicado' => $idRadicado]);
                $modelRemitente->correoElectronicoCliente = $radiDetallePqrAnonimo->emailRadiDetallePqrsAnonimo;
            }

            $correo = $modelRemitente->correoElectronicoCliente;
        } else {
            # Se obtiene la información del usuario o funcionario
            $modelRemitente = User::find()->select(['email'])->where(['id' => $dataRemitente->idRadiPersona])->one();
            $correo = $modelRemitente->email;
        }

        if ($correo==''){

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorEmail'),
                'data' => ['error' => [Yii::t('app', 'errorEmail')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $idRadiRadicado = $request[0]['id'];

        $modelDocumentos = RadiDocumentos::find()   
            ->where(['idRadiRadicado' => $idRadiRadicado])
            ->all();

        $arrayDocumentos = [];

        foreach ($modelDocumentos as $radiDocumento) {

            $descripcion = (string) $radiDocumento->descripcionRadiDocumento;

            $arrayDocumentos[] = [
                'id'                => $radiDocumento->idRadiDocumento,
                'nombre'            => $radiDocumento->nombreRadiDocumento,
                'numDocumento'      => $radiDocumento->numeroRadiDocumento,
                'descripcion'       => (strlen($descripcion) > 100) ? substr($descripcion, 0, 100) . '...' : $descripcion,
                'descripcionTitle'  => $radiDocumento->descripcionRadiDocumento,
                'tipodocumento'     => $radiDocumento->idGdTrdTipoDocumental0->nombreTipoDocumental,
                'usuario'           => $radiDocumento->idUser0->nombreUserDetalles.' '.$radiDocumento->idUser0->apellidoUserDetalles,
                'estado'            => $radiDocumento->estadoRadiDocumento,
                'fecha'             => date("d-m-Y", strtotime($radiDocumento->creacionRadiDocumento)),
                'isPdf'             => (strtolower($radiDocumento->extencionRadiDocumento) == 'pdf') ? true : false,
                'isPublicoRadiDocumento' => Yii::$app->params['SiNoNumber'][$radiDocumento->isPublicoRadiDocumento],
                'statusIdPublic'    => $radiDocumento->publicoPagina,
                'statusTextPublic'  => Yii::t('app', 'valuePageNumber')[$radiDocumento->publicoPagina],
                'rowSelect'         => false,
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'OK',
            'data'    => $arrayDocumentos,
            'radiSendReplyEMail' => Yii::$app->params['radiSendReplyEMail'],
            'status'  => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Transacción para cargar documento firmado en el proceso físico
     */
    public function actionUploadSignedDocument($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            if (!empty($request)) {

                /*** Inicio desencriptación GET ***/ //La solicitud es por metodo post pero se utilizan los parametros de la url
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

                /** Validar si cargo un archivo subido **/
                $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

                if(isset($fileUpload->error) && $fileUpload->error === 1) {
                    
                    $uploadMaxFilesize = ini_get('upload_max_filesize');

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'uploadMaxFilesize', [
                            'uploadMaxFilesize' => $uploadMaxFilesize
                        ])]],
                        'status' => Yii::$app->params['statusErrorValidacion']
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                if($fileUpload) {

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

                    $rutaOk = true;
                    $idRadicado = $request['ButtonSelectedData'][0]['id'];

                    $validationExtension = ['pdf'];
                    /* validar tipo de archivo */
                    if (!in_array($fileUpload->extension, $validationExtension)) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /* Fin validar tipo de archivo */

                    $tamano = $fileUpload->size / 1000;

                    //Inicio de transacción en bd 
                    $transaction = Yii::$app->db->beginTransaction();

                    $modelRadicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadicado])->one();

                    $plantilla = RadiDocumentosPrincipales::find()
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->andWhere(['or', 
                                    ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesoFirmaFisica']],
                                    ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']]
                                ])
                        ->one();                    

                    if($plantilla){

                        $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente'];
                        $plantilla->tamanoRadiDocumentoPrincipal = ''.$tamano .'';

                        if(!$plantilla->save()) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $plantilla->getErrors(),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                                'asdas' => $tamano
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        // Consulta la accion de la transaccion de estado firmado físicamente para obtener su id
                        $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'physicallySigned']);
                        $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                        if($fileUpload->saveAs($plantilla->rutaRadiDocumentoPrincipal,false)){
                            $transaction->commit(); 
                        }else{
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $plantilla->getErrors(),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                                'asdas' => $tamano
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $userLogued = User::find()->select(['id', 'idGdTrdDependencia'])->where(['id' => Yii::$app->user->identity->id])->one();
                        $userDetallesLogued = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles', 'firma'])->where(['idUser' => $userLogued['id']])->one();
                        $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRadicados->idTrdDepeUserCreador]);

                        /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $modelRadicados->idRadiRadicado, //Id radicado
                            $idTransacion,
                            Yii::$app->params['eventosLogTextRadicado']['firmadoFisicamente'].' '.$plantilla->nombreRadiDocumentoPrincipal,
                            $modelRadicados,
                            array() //No validar estos campos
                        );
                        /***  Fin  Log de Radicados  ***/

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogTextRadicado']['firmadoFisicamente'].' '.$plantilla->nombreRadiDocumentoPrincipal,
                            [], //DataOld
                            [], //[$model], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' =>  Yii::t('app', 'sendEventRadicadoFirmadoFisicamente', [
                                'numRadi' => $modelRadicados->numeroRadiRadicado,
                                'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                                'depe' => $gdTrdDependencias->nombreGdTrdDependencia ?? Yii::t('app', 'depNotFound'),
                            ]),
                            'data' => [],
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response, true);  

                    } else {
                        $transaction->rollBack();
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
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => Yii::t('app', 'enterFile')],
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

}
