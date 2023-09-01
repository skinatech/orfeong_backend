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

use api\components\HelperLog;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperDynamicForms;
use api\components\HelperValidatePermits;
use api\models\GdTrdDependencias;
use api\models\RadiAgendaRadicados;
use unyii2\imap\ImapConnection;
use unyii2\imap\Mailbox;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

use api\components\HelperMailbox;

use Yii;
use yii\data\ActiveDataProvider;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\web\Controller;

use api\models\RadiRadicados;
use api\models\RadiDocumentos;
use api\models\RadiCorreosRadicados;
use api\models\User;
use api\models\Clientes;

use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\radicacion\RadicadosController;

/**
 * RadiAgendaController implements the CRUD actions for RadiAgendaRadicados model.
 */
class RadicacionEmailController extends Controller
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
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'delete' => ['PUT'],
                    'change-status' => ['PUT'],
                    'send-reply-mail' => ['PUT'],
                    'login-email' =>['POST']
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionLoginEmail()
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

                try {

                    /***** Validacion datos de conexion Imap y dominio *****/
                    $domainNamesAccepted = Yii::$app->params['domainNamesAccepted']; // Listado de dominios aceptados por el sistema y su valor clave para buscar la informacion de conexion imap
                    $arrayHostnameImap = Yii::$app->params['arrayHostnameImap']; // Informacion para la conexion imap segun el dominio

                    $dominio = trim(substr(strtolower($request['username']), strpos($request['username'],'@')+1));

                    /** Validar si el dominio es aceptado por el sistema */
                    if (array_key_exists($dominio, $domainNamesAccepted) && isset($arrayHostnameImap[ $domainNamesAccepted[$dominio] ])) {

                        $hostnameImap = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['hostnameImap'];
                        $imapPathDefault = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['imapPathDefault'];

                    } else {
                        $domainNamesAccepted = Yii::$app->params['domainNamesAccepted'];
                        $domainNamesAccepted = implode(', ', array_keys($domainNamesAccepted));

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [ Yii::t('app', 'domainNotAccepted') . '(' . $domainNamesAccepted . ')' ]],
                            'status'  => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Fin Validar si el dominio es aceptado por el sistema */
                    /***** Fin Validacion datos de conexion Imap y dominio *****/

                    $mbox = imap_open($hostnameImap, $request['username'], $request['password']);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['LoginMail'].' con el correo: '.$request['username'], // texto para almacenar en el evento
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $dataEmail = [
                        'username' => $request['username'],
                        'password' => $request['password'],
                    ];
                    $dataEmailEncrypt = HelperEncryptAes::encrypt($dataEmail, false);

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data'  => $dataEmailEncrypt['encrypted'],
                        'userLoginApp' => Yii::$app->user->identity->username,
                        'mailBox' => Yii::$app->params['bandejaPrincipal'],
                        'connection' =>  true,
                        'status'  => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } catch (\Throwable $th) {
                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'AuthenticationFailed')]], 
                        'connection' =>  false,
                        // 'imap_errors'=> imap_errors(),
                        // 'imap_alerts' => imap_alerts(),
                        // '$th' => $th->getMessage(),
                        'status'  => Yii::$app->params['statusErrorValidacion'],  
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
     * Action que muestra todos los correos electronicos de la cuanta con la que se autenticaron 
     * @param username = 'corro@correo';
     * @param password = 'password del correo';
    */
    public function actionReceivingMail($request)
    {
        $tiempoInicial = microtime(true);
        $maxExecutionTimeEmail = Yii::$app->params['maxExecutionTimeEmail'];
        ini_set('max_execution_time', $maxExecutionTimeEmail);
        //ini_set('memory_limit', '3073741824');
        //set_time_limit($maxExecutionTimeEmail);

        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsRadicacionEmail'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            if(isset($request['dataEmail'])){
                
                $dataEmailDecrypted = HelperEncryptAes::decrypt($request['dataEmail']['data'], false);
                if ($dataEmailDecrypted['status'] == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'AuthenticationFailed')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $authUser = [
                    'username' => $dataEmailDecrypted['request']['username'],
                    'password' => $dataEmailDecrypted['request']['password'],
                ];
            
            }else{
                
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'emptyJsonSend'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);

            }

            /***** Validacion datos de conexion Imap y dominio *****/
            $domainNamesAccepted = Yii::$app->params['domainNamesAccepted']; // Listado de dominios aceptados por el sistema y su valor clave para buscar la informacion de conexion imap
            $arrayHostnameImap = Yii::$app->params['arrayHostnameImap']; // Informacion para la conexion imap segun el dominio

            $dominio = trim(substr(strtolower($authUser['username']), strpos($authUser['username'],'@')+1));

            /** Validar si el dominio es aceptado por el sistema */
            if (array_key_exists($dominio, $domainNamesAccepted) && isset($arrayHostnameImap[ $domainNamesAccepted[$dominio] ])) {

                $hostnameImap = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['hostnameImap'];
                $imapPathDefault = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['imapPathDefault'];
                $isServerEncoding = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['isServerEncoding'];
                $imapPathExceptions = $arrayHostnameImap[ $domainNamesAccepted[$dominio] ]['imapPathExceptions'];

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => ['Dominio no aceptado']],
                    'status'  => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            /** Fin Validar si el dominio es aceptado por el sistema */
            /***** Fin Validacion datos de conexion Imap y dominio *****/

            $dataList = [];
            $adjuntos = [];

            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if (is_array($request) && isset($request['filterOperation'])) {
                foreach ($request['filterOperation'] as $field) {
                    foreach ($field as $key => $info) {
    
                        if ($key == 'mailBox') {
                            $request['mailBox'] = $info;
                        } else {
                            //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                            if ($key == 'SINCE' || $key == 'BEFORE') {
                                if( isset($info) && $info !== null && trim($info) !== ''){
                                    $dataWhere[$key] =  date( "d M Y", strToTime(trim($info)) );
                                }
                            } else {
                                if( isset($info) && !empty($info) ){
                                    $dataWhere[$key] = $info;
                                }
                            }
                        }

                    }
                }
            }

            /** Obtener el nombre de la bandeja consultada */
            $arrayMailBoxNames = Yii::t('app', 'arrayMailBoxNames');
            if (isset($request['mailBox']) && trim($request['mailBox']) != '') {
                $imapPath = $request['mailBox'];
                if ($imapPath == $imapPathDefault) {
                    $mailBoxName = 'Entrada';
                } else {
                    if (strpos($imapPath, $hostnameImap) === false) {
                        $mailBoxName = explode('/', $imapPath);
                        $mailBoxName = end($mailBoxName);
                        $mailBoxName = $arrayMailBoxNames[$mailBoxName] ?? $mailBoxName;
                    } else {
                        $mailBoxName = explode($hostnameImap, $imapPath);
                        $mailBoxName = end($mailBoxName);
                        $mailBoxName = $arrayMailBoxNames[$mailBoxName] ?? $mailBoxName;
                    }
                }
            } else {
                $imapPath = $imapPathDefault;
                $mailBoxName = $arrayMailBoxNames['Entrada'];
            }

            /** Conexion inicial para obtener el listado de bandejas */
            $mbox = imap_open($hostnameImap, $authUser['username'], $authUser['password']);            
            $folders = imap_listmailbox($mbox, $hostnameImap, '*');
            
            $foldersList = [];
            foreach ($folders as $folder) {
                if (!in_array($folder, $imapPathExceptions)) {
                    if ($folder == $imapPathDefault) {
                        $folderName = $arrayMailBoxNames['Entrada'];
                    } else {
                        if (strpos($folder, $hostnameImap) === false) {
                            $folderName = explode('/', $folder);
                            $folderName = end($folderName);
                            $folderName = $arrayMailBoxNames[$folderName] ?? $folderName;
                        } else {
                            $folderName = explode($hostnameImap, $folder);
                            $folderName = end($folderName);
                            $folderName = $arrayMailBoxNames[$folderName] ?? $folderName;
                        }
                    }
                    $foldersList[] = ['label' => $folderName, 'value' => $folder ];
                }
            }

            $formType = HelperDynamicForms::createDataForForm('indexRadicacionEmail');
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['templateOptions']['options'] = $foldersList;
            $formType['schema'][0]['fieldArray']['fieldGroup'][0]['defaultValue'] = $imapPathDefault;
            /** Fin Conexion inicial para obtener el listado de bandejas */

            /** Consulta de correos radicados con la bandeja y el usuario actual */
            $radiCorreosRadicados = RadiCorreosRadicados::find()->select(['idCorreo'])
                ->where(['bandeja' => $imapPath, 'email' => $authUser['username']])
                ->all();
            $arrayRadiCorreosRadicados = [];
            foreach ($radiCorreosRadicados as $row) {
                $arrayRadiCorreosRadicados[] = $row->idCorreo;
            }
            /** Fin Consulta de correos radicados con la bandeja y el usuario actual */
    
            $criterioBusqueda = '';

            // Si no llegan criterios de busqueda, por defecto se pasa SINCE para que muestre los correos que llegaron desde la fecha predeterminada.
            $dateInitDefault = date( "d M Y", strToTime( "-1 days" ) ); //Fecha inicial por defecto
            if(count($dataWhere) > 0){
                
                if (!array_key_exists('SINCE', $dataWhere)) {
                    $criterioBusqueda .= " SINCE \"$dateInitDefault\""; //Fecha inicial
                }

                //Se reitera $dataWhere criterios por los que se puede buscar en la bandeja de correos
                foreach ($dataWhere as $field => $value) {
                    if ($field == 'BEFORE') {
                        $value = date( "d M Y", strToTime( $value . " +2 days" ) ); // Sumar un dia a la fecha hasta porque el correo toma las fechas anteriores a la establecida
                    }
                    $criterioBusqueda .= $field .' "'.$value.'" ';
                }
                
            }else{
                $criterioBusqueda .= " SINCE \"$dateInitDefault\" "; //Fecha inicial
            }

            $email = imap_search($mbox, $criterioBusqueda);

            /** Rango de fecha consultadas */
            $fechaActual =date('d-m-Y H:i:s');
            $desde = ( isset($dataWhere['SINCE']) )  ? date( "d-m-Y", strToTime($dataWhere['SINCE']) )  : date( "d-m-Y", strToTime( $dateInitDefault) );
            $hasta = ( isset($dataWhere['BEFORE']) ) ? date( "d-m-Y", strToTime($dataWhere['BEFORE']) ) : date( "d-m-Y");
            $initCardHeaderTitle =  Yii::t('app', 'Listado de correos (Desde') . ': ' . $desde . ', ' . Yii::t('app', 'Hasta') . ': ' . $hasta . ')'; 
            $initCardHeaderTitle.= Yii::t('app', ', fecha y hora de consulta: (') . $fechaActual . ')';

            // lee el contenido de la bandeja de correo electronico
            $criterioBusqueda = trim($criterioBusqueda);

            if($email){

                $mails = imap_fetch_overview($mbox, implode(',', $email), 0);

                $countMailIds = count($email); // Cantidad de correos según criteria
                $countMailProcessed = 0; // Cantidad de correos procesados
                $countMailNoProcessed = 0; // Cantidad de correos NO procesados

                $mailsInfo = array_reverse($mails); // Orden descendente para comenzar por el mas reciente

                foreach ($mailsInfo as $mailInfo) {
                    $attachment = $this->getValidateAttachment($mbox, $mailInfo->uid);
                    if(stristr($mailInfo->from, '<') === false) {
                        $fromAddressExplode = explode('<', $mailInfo->from);
                        $fromAddress = $fromAddressExplode[0];
                    } else {
                        $fromAddressExplode = explode('<', $mailInfo->from);
                        $fromAddress = substr($fromAddressExplode[1], 0, -1);
                    }

                    if($fromAddress !== "" && !empty($fromAddress)) {
                        $countMailProcessed++;

                        $dataList[] = array(
                            'id' => $mailInfo->uid,
                            'fromName' => $mailInfo->from,
                            'fromAddress' => $fromAddress,
                            'subject' => (isset($mailInfo->subject) ? imap_utf8($mailInfo->subject) : ''),
                            'date' => date('Y-m-d H:i:s', isset($mailInfo->date) ? strtotime(preg_replace('/\(.*?\)/', '', $mailInfo->date)) : time()),
                            'attachment' => [],
                            'hasAttachment' => ($attachment) ? 'Si': 'No',
                            'isCorreoRadicado' => (in_array($mailInfo->uid, $arrayRadiCorreosRadicados)) ? 'Si' : 'No',
                            'NameAndAddress' => imap_utf8($mailInfo->from),
                            'status' => 10,
                            'statusText' => 'Activo',
                            'rowSelect' => false,
                            'idInitialList' => 0,
                            'mailBoxName' => $mailBoxName,
                        );
                    }
                }
            }

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $dataList = array_reverse($dataList);

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => $formType,
                'infoMailCount' => [
                    'countMailIds'         => $countMailIds ?? 0,
                    'countMailProcessed'   => $countMailProcessed ?? 0,
                    'countMailNoProcessed' => 0,
                ],
                //'folders' => $folders, // Solo para debug
                //'foldersList' => $foldersList, // Solo para debug
                'initCardHeaderTitle' => $initCardHeaderTitle,
                'fechaActual' => $fechaActual,
                'mailBox' => $imapPath,
                'status' => 200,
                'criterioBusqueda' => $criterioBusqueda
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

    public function getValidateAttachment($conexion, $mailId) {
        $mailStructure = imap_fetchstructure($conexion, $mailId, FT_UID);
        if(empty($mailStructure->parts)) {
            return false;
        } else {
            if($mailStructure->subtype === "MIXED") {
                return true;
            }

            return false;
        }
    }

    /** 
     * Action que lee el correo seleccionado y con el ID muestra la información de su contenido para pasarlo al 
     * formulario de radicación.
     * @param id = mailId; 
     * @param username = 'corro@correo';
     * @param password = 'password del correo';
     * */
    public function actionReadEmailContent($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsRadicacionEmail'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

            if(isset($request['dataEmail'])){
                $dataEmailDecrypted = HelperEncryptAes::decrypt($request['dataEmail']['data'], false);
                if ($dataEmailDecrypted['status'] == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'AuthenticationFailed')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $authUser = [
                    'username' => $dataEmailDecrypted['request']['username'],
                    'password' => $dataEmailDecrypted['request']['password'],
                ];
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'emptyJsonSend'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $mailBox = $request['mailBox'];

            $imapConnection = new ImapConnection();

            $imapConnection->imapPath = $mailBox;
            $imapConnection->imapLogin = $authUser['username'];
            $imapConnection->imapPassword = $authUser['password'];
            $imapConnection->serverEncoding = 'UTF-8'; // utf-8 default.
            $imapConnection->attachmentsDir = Yii::getAlias('@webroot') . "/tmp_mail";

            // Se establece la conexión
            $mailbox = new Mailbox($imapConnection);

            $mailId = $request['id'];
            $mail = $mailbox->getMail($mailId);

            $mailBoxName = explode('}', $mailBox);
            $mailBoxName = end($mailBoxName);

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                'Visualización de correo',
                Yii::$app->params['eventosLogText']['ViewMail'] . $mailId .  ' de la bandeja ' . $mailBoxName . ' del correo ' . $authUser['username'], //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            if ($mail->textHtml != null) {
                $isBodyHtml = true;
                $mailBody = $mail->textHtml;
                if ($mail->textPlain != null) {
                    $vistaDisponible = 'Texto y Html';
                } else {
                    $vistaDisponible = 'Html';
                }

            } else {
                $isBodyHtml = false;
                $mailBody = str_replace("\n", '<br>', $mail->textPlain);
                $vistaDisponible = 'Texto';
            }

            // Devuelve archivos adjuntos de correo si hay alguno o si no hay una matriz vacía
            $attachments = $mail->getAttachments();
            $adjuntos = [];
            foreach ($attachments as $attachment) {                

                //Si el archivo adjunto es una imagen del cuerpo del mensaje
                if(preg_match('/<img.*src="cid:('. $attachment->id . ')"/', $mailBody)){
                    
                    $imageBodyFile = file_get_contents($attachment->filePath);

                    $imageBodyBase64 = base64_encode($imageBodyFile);
                    
                    $mailBody = preg_replace('/<img src="(cid:'. $attachment->id . ')"/', '<img src=data:image/png;base64,' . $imageBodyBase64, $mailBody);
                  
                }else {
                    $adjuntos[] = $attachment->name;
                }
            }
            $attachmentString = implode(', ', $adjuntos);

            $data = [
                'mailId' => $mailId,
                'nombreCliente' => $mail->fromName,
                'correoElectronicoCliente' => $mail->fromAddress,
                'idCgMedioRecepcion' => Yii::$app->params['CgMedioRecepcionNumber']['correosElectronicos'],
                'asuntoRadiRadicado' => $mail->subject,
                'attachment' => $adjuntos,
                'attachmentString' => $attachmentString,
                'date' => $mail->date,
                'isBodyHtml' => $isBodyHtml,
                'mailBody' => $mailBody,
                'vistaDisponible' => $vistaDisponible,
            ];

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
            $mailbox->disconnect();

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
