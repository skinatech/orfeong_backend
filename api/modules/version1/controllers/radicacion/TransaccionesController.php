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

use api\components\HelperEncrypt;
use api\components\HelperLdap;
use Yii;
use yii\data\ActiveDataProvider;
use Imagine\Gd;

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
use api\components\HelperPlantillas;
use api\components\HelperNotification;
use api\components\HelperRadicacion;
use api\components\HelperQueryDb;
use api\components\HelperExtraerTexto;
use api\components\HelperIndiceElectronico;
use api\components\HelperFiles;
use api\components\HelperExpedient;
use api\components\HelperLoads;
use api\components\HelperGenerateExcel;
use api\components\HelperConsecutivo;
use api\models\CgEncuestas;
use api\models\RadiRadicados;
use api\models\CgNumeroRadicado;
use api\models\RadiDocumentos;
use api\models\RadiAgendaRadicados;
use api\models\CgTransaccionesRadicados;
use api\models\CgProveedores;
use api\models\CgEnvioServicios;
use api\models\CgFirmasDocs;
use api\models\RadiEnvios;
use api\models\RadiRemitentes;
use api\models\RadiLogRadicados;
use api\models\CgRegionales;
use api\models\GdTrdDependencias;
use api\models\NivelGeografico3;
use api\models\RadiRadicadoAnulado;
use api\models\User;
use api\models\RadiInformados;
use api\models\UserDetalles;
use api\models\CgGeneral;
use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use api\models\Clientes;
use api\models\RadiEnviosDevolucion;
use api\models\RolesOperaciones;
use api\models\RolesTiposOperaciones;
use api\models\GdExpedientes;
use api\models\GdExpedienteDocumentos;
use api\models\GdExpedientesInclusion;

use api\models\ClientesCiudadanosDetalles;
use api\models\GaArchivo;
use api\models\GdFirmasQr;
use api\models\RadiDocumentosPrincipales;
use api\models\GdHistoricoExpedientes;

use api\models\CgEtiquetaRadicacion;

use Da\QrCode\QrCode;
use Da\QrCode\Format\MeCardFormat;
use Picqer\Barcode\BarcodeGeneratorSVG;
use Picqer\Barcode\BarcodeGeneratorPNG;

use api\modules\version1\controllers\radicacion\RadicadosController;
use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\radicacion\InformadosController;
use api\modules\version1\controllers\gestionDocumental\ExpedientesController;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use yii\helpers\Url;
use api\models\ClienteEncuestas;
use api\models\GdFirmasMultiples;
use api\models\InfoFirmaDigitalDocPrincipales;
use common\models\User as UserValidate;  // Validación password
use api\models\PDFInfo;
use api\models\RadiDetallePqrsAnonimo;
use api\models\CgFirmasCertificadas;

/**
 * TransaccionesController.
 */
class TransaccionesController extends Controller
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
                    'descartar-consecutivo' => ['POST'],
                    'schedule' => ['POST'],
                    'upload-file' => ['POST'],
                    'solicita-anulacion-radicado' => ['POST'],
                    'solicita-vobo' => ['POST'],
                    'vobo' => ['PUT'],
                    'finalize-filing' => ['POST'],
                    'return-delivery' => ['POST'],
                    'correspondence-template' => ['POST'],
                    'action-include-expedient' => ['POST'],
                    'correspondence-match' => ['POST'],
                    'sign-document' => ['POST'],
                    'sign-document-combi-sin-firmas' => ['POST'],
                    'shipping-Ready' => ['POST'],
                    'archive-filing' => ['POST'],
                    'archive-expedient' => ['POST'],
                    'upload-document-to-expedient' => ['POST'],
                    'print-external-sticker' => ['POST'],
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
     *  Transaccion Programacion de Agenda
     * 
     * @param string $fecha / Observacion
     * 
     * @return string notificacion  
     */
    public function actionSchedule()
    {   

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');
            $notificacion['status'] = false;

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

                $idRadicados = []; //Array de agrupación de radicados seleccionados
                $radicadosNum = [];

                //Inicio de la transacción
                $transaction = Yii::$app->db->beginTransaction();
                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'schedule']);
                $fechaEvento = date("Y-m-d", strtotime($request['data']['fecha']));
                $formatFecha = RadicadosController::formatoFechaVencimientoAgendas($fechaEvento);

                $arrayRadiOrConsecutive = [];

                foreach ($request['ButtonSelectedData'] as $key => $radicados) {

                    # Se agrupa todos los radicados seleccionados
                    $idRadicados[] = $radicados['id'];

                    /** Consultar datos del radicado */
                    $modelRadiRadicado = RadiRadicados::find()->select(['numeroRadiRadicado', 'isRadicado'])->where(['idRadiRadicado' => $radicados['id']])->one();
                    if(is_null($modelRadiRadicado)){
                        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
                    }

                    $radicadosNum[] = $modelRadiRadicado['numeroRadiRadicado'];

                    $model = new RadiAgendaRadicados();

                    $model->idRadiRadicado = $radicados['id'];
                    $model->fechaProgramadaRadiAgenda = $formatFecha['formatoBaseDatosFin'];
                    $model->descripcionRadiAgenda = $request['data']['observacion'];
                    $model->estadoRadiAgenda = Yii::$app->params['statusTodoText']['Activo'];


                    /** Procesar el errores de la fila */
                    if (!$model->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $model->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    //return $this->redirect(['view', 'id' => $model->idRadiAgendaRadicados]);

                
                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $model->idRadiRadicado, //Id radicado
                        $idTransacion->idCgTransaccionRadicado,
                        Yii::$app->params['eventosLogTextRadicado']['EventNew'] . $request['data']['observacion'].' con la fecha de agendamiento: '.$model->fechaProgramadaRadiAgenda, //observación
                        $model,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/

                    if ((boolean) $modelRadiRadicado['isRadicado'] == true) {
                        $arrayRadiOrConsecutive[] = 'radicado';
                    } else {
                        $arrayRadiOrConsecutive[] = 'consecutivo';
                    }

                }

                $numRadicados = implode(', ',$radicadosNum);

                if(count($radicadosNum) == 1){
                    if (in_array('radicado', $arrayRadiOrConsecutive)) {
                        $eventoLogTextNewSchedule = Yii::$app->params['eventosLogText']['newSchedule'];
                    } else {
                        $eventoLogTextNewSchedule = Yii::$app->params['eventosLogText']['newScheduleTmp'];
                    }

                    $observacion =  $eventoLogTextNewSchedule . $numRadicados. ' en estado ' . Yii::$app->params['statusTodoNumber'][$model->estadoRadiAgenda] . ' para la fecha ' .$formatFecha['formatoFrontend'].' con la observación '.$request['data']['observacion']; //texto para almacenar en el evento
                }else{
                    if (in_array('radicado', $arrayRadiOrConsecutive) && in_array('consecutivo', $arrayRadiOrConsecutive)) {
                        $eventoLogTextNewSchedule = Yii::$app->params['eventosLogText']['newScheduleMasiveAll'];
                    } elseif(in_array('radicado', $arrayRadiOrConsecutive)) {
                        $eventoLogTextNewSchedule = Yii::$app->params['eventosLogText']['newScheduleMasive'];
                    } elseif(in_array('consecutivo', $arrayRadiOrConsecutive)) {
                        $eventoLogTextNewSchedule = Yii::$app->params['eventosLogText']['newScheduleMasiveTmp'];
                    }

                    $observacion = $eventoLogTextNewSchedule . $numRadicados . ' en estado ' . Yii::$app->params['statusTodoNumber'][$model->estadoRadiAgenda] . ' para la fecha ' .$formatFecha['formatoFrontend'].' con la observación '.$request['data']['observacion']; //texto para almacenar en el evento
                }

                /***    Log de Auditoria  ***/
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
                /***    Fin log Auditoria   ***/


                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';
        
                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email', 'US.username','RD.idRadiRadicado','RD.isRadicado'])
                    ->from($tablaRadicado);
                    // ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    $modelRadicado = $modelRadicado->where(['RD.idRadiRadicado' => $idRadicados])
                ->all();

                $arrayDatos = [];
                if(!is_null($modelRadicado)){

                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado){

                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'][$infoRadicado['idRadiRadicado']] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['tramitador'] = $infoRadicado['user_idTramitador'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'][$infoRadicado['idRadiRadicado']] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';
          
                        }
                    }
                }

                $transaction->commit();

                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {

                        // Notificar al Usuario encargado si la fecha del evento es la actual
                        if ($fechaEvento == date("Y-m-d")) {
                            $notificacion = self::notificacionSchedule($radicadosUsuario['tramitador'], $fechaEvento, $radicadosUsuario['email'], $radicadosUsuario['radicados'], $radicadosUsuario['typesRadiOrTmp']);

                            /***  Notificacion  ***/
                            foreach($radicadosUsuario['idRadicado'] as $id => $numRadi){                                
                                HelperNotification::addNotification(
                                    Yii::$app->user->identity->id, //Id user creador
                                    $radicadosUsuario['tramitador'], // Id user notificado
                                    Yii::t('app','messageNotification')['scheduling'].$numRadi, //Notificacion
                                    Yii::$app->params['routesFront']['viewRadicado'], // url
                                    $id // id radicado
                                );                                
                            }
                            /***  Fin Notificacion  ***/
                            

                        }

                        // Envio Notificaciones
                        if ($notificacion['status']) {
                            if(isset($notificacion['Notificaciones'][0])){

                                $textMess = Yii::t('app', 'mailDestiRadicado', ['user' => $notificacion['Notificaciones'][0]['user']]);
        
                                $messages[] = [
                                    $textMess,
                                ];

                            } else {
                                $messages = Yii::t('app', 'mailMultiDestiRadicado', ['date' => $formatFecha['formatoFrontend']]);
                            }

                        } else {
                            $messages = Yii::t('app', 'mailMultiDestiRadicado', ['date' => $formatFecha['formatoFrontend']]);
                        }

                    }
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'notificacion' => $notificacion,
                    'message' => $messages,
                    'data' => $model,
                    'status' => 200
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
     *  Transaccion Programacion de Agenda 
     * @param integer user_idTramitador
     * @param string fecha evento 
     * @param $emailTramitador [String] [Correo del tramitado] 
     * @param $numRadicados [Array] [Radicados seleccionados en el form] 
     * @return string notificacion  
     */
    public static function notificacionSchedule($user_idTramitador = false, $fechaEventoParam = null, $emailTramitador = null, $numRadicados = null, $typesRadiOrTmp = [])
    {
        $obtenerNumRadis = false;
        $envioCorreo = false;
        $fechaEvento = $fechaEventoParam ?? date("Y-m-d", strtotime(date("Y-m-d") . "+ 1 days"));
        $response = [
            'status' => false,
            'Notificaciones' => [],
            'TotalEnvios' => 0,
        ];

        if($numRadicados == null){
            $obtenerNumRadis = true;
        }

        if($emailTramitador == null){
            $userDetalles = User::find()->where(['id' => $user_idTramitador])->one();
            $emailTramitador = $userDetalles['email'];
        }

        # Consulta de datos del radicado agendado
        $tablaAgenda = RadiAgendaRadicados::tableName() . ' AS A';
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';

        $RadiAgendaRadicados = (new \yii\db\Query())
            ->select(['RD.numeroRadiRadicado','RD.idRadiRadicado','RD.user_idTramitador','A.fechaProgramadaRadiAgenda', 'A.descripcionRadiAgenda', 'A.estadoRadiAgenda', 'A.idRadiAgendaRadicados','RD.isRadicado'])
            ->from($tablaAgenda);
            // ->innerJoin($tablaRadicado, '`a`.`idRadiRadicado` = `rd`.`idRadiRadicado`');
            $RadiAgendaRadicados = HelperQueryDb::getQuery('innerJoinAlias', $RadiAgendaRadicados, $tablaRadicado, ['A' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
            $RadiAgendaRadicados = $RadiAgendaRadicados->where(['fechaProgramadaRadiAgenda' => $fechaEvento, 'estadoRadiAgenda' => Yii::$app->params['statusTodoText']['Activo']]);

            if ($user_idTramitador != false) {
                $RadiAgendaRadicados = $RadiAgendaRadicados->andWhere(['RD.user_idTramitador' => $user_idTramitador]);
            }       
     
        $countQuery = $RadiAgendaRadicados->count();

        // Busqueda por la fecha actual
        if ($countQuery == 0) {

            $fechaEvento = date("Y-m-d");

            $RadiAgendaRadicados = (new \yii\db\Query())
                ->select(['RD.numeroRadiRadicado','RD.idRadiRadicado', 'RD.user_idTramitador','A.fechaProgramadaRadiAgenda', 'A.descripcionRadiAgenda', 'A.estadoRadiAgenda', 'A.idRadiAgendaRadicados','RD.isRadicado'])
                ->from($tablaAgenda);
                // ->innerJoin($tablaRadicado, '`a`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                $RadiAgendaRadicados = HelperQueryDb::getQuery('innerJoinAlias', $RadiAgendaRadicados, $tablaRadicado, ['A' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                $RadiAgendaRadicados = $RadiAgendaRadicados->where(['fechaProgramadaRadiAgenda' => $fechaEvento, 'estadoRadiAgenda' => Yii::$app->params['statusTodoText']['Activo']]);                

                if ($user_idTramitador != false) {
                    $RadiAgendaRadicados = $RadiAgendaRadicados->andWhere(['RD.user_idTramitador' => $user_idTramitador]);
                }

            $countQuery = $RadiAgendaRadicados->count();
        }

        //$countQuery = $RadiAgendaRadicados->count();
        $RadiAgendaRadicados = $RadiAgendaRadicados->all();
        

 
        if (isset($RadiAgendaRadicados)) {

            # Se construye el arrayDatos con la nueva informacion de la tabla radiAgenda
            $arrayDatos = [];
            foreach ($RadiAgendaRadicados as $radicadoEvent) {

                if($obtenerNumRadis){
                    $numRadicados[] = $radicadoEvent['numeroRadiRadicado'];
                    $typesRadiOrTmp[] = ((boolean) $radicadoEvent->isRadicado == true) ? 'radicado' : 'temporal';
                }

                if(isset($arrayDatos[$radicadoEvent["user_idTramitador"]])){
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['radicados'][] = $radicadoEvent['numeroRadiRadicado'];
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['idAgenda'][] = $radicadoEvent['idRadiAgendaRadicados'];
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['idRadicado'] = $radicadoEvent['idRadiRadicado'];

                } else {
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['radicados'][] = $radicadoEvent['numeroRadiRadicado'];
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['descripcion'] = $radicadoEvent['descripcionRadiAgenda'];
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['idAgenda'][] = $radicadoEvent['idRadiAgendaRadicados'];
                    $arrayDatos[$radicadoEvent["user_idTramitador"]]['idRadicado'] = $radicadoEvent['idRadiRadicado'];
                }
                
                $hora = (int) date('H');
                $tipo = date('A');
                $formatFecha = RadicadosController::formatoFechaVencimiento($radicadoEvent['fechaProgramadaRadiAgenda']);
            } 

            if (is_array($arrayDatos)) {

                # Iteración para el envio del correo, por usuario con sus radicados respectivos
                foreach($arrayDatos as $key => $infoAgenda) {
                    
                    // $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadoEvent->idRadiRadicado));
                    
                    // Envia la notificación de correo electronico al usuario de tramitar                    
                    $headMailText = Yii::t('app', 'headMailTextRegistro');                

                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    if ( count($numRadicados) > 1) {

                        if (in_array('radicado', $typesRadiOrTmp) && in_array('temporal', $typesRadiOrTmp)) {
                            $textStart = 'mailEventRadicadosAll';
                            $subjectText = 'headMailTextRecoradatoriosAll';
                        } elseif (in_array('radicado', $typesRadiOrTmp)) {
                            $textStart = 'mailEventRadicados';
                            $subjectText = 'headMailTextRecoradatorios';
                        } else {
                            $textStart = 'mailEventRadicadosTmp';
                            $subjectText = 'headMailTextRecoradatoriosTmp';
                        }

                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                        $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                    } else {

                        if (in_array('radicado', $typesRadiOrTmp)) {
                            $textStart = 'mailEventRadicado';
                            $subjectText = 'headMailTextRecoradatorio';
                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        } else {
                            $textStart = 'mailEventRadicadoTmp';
                            $subjectText = 'headMailTextRecoradatorioTmp';
                            $nameButtonLink = 'buttonLinkRadicadoTmp'; // Variable que será traducida
                        }

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($infoAgenda['idRadicado']));
                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                    }

                    $numRadicado = implode(', ',$numRadicados);

                    $textBody  = Yii::t('app', $textStart, [
                        'NoRadicado' =>  $numRadicado,
                        'FechaPro'   => $formatFecha['formatoFrontend'],
                        'Descripcion'   => $infoAgenda['descripcion'],
                    ]);
                    
                    
                    $bodyMail = 'radicacion-html';
                    $subject = Yii::t('app', $subjectText);
                    $envioCorreo = CorreoController::sendEmail($emailTramitador, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);

                    foreach($arrayDatos[$key]['idAgenda'] as $valueAgenda) {
    
                        # Se actualiza el estado a inactivo a todos los radicados notificados
                        if ($envioCorreo) {
                              
                          $updateAgendaRadicados = self::findRadiAgendaRadicadosModel($valueAgenda);
                          $updateAgendaRadicados->estadoRadiAgenda = Yii::$app->params['statusTodoText']['Inactivo'];
                          $updateAgendaRadicados->save();
                        }
    
                        $response['Notificaciones'][] = [
                            'user' => $emailTramitador,
                            'status' => $envioCorreo,
                            'estadoRadiAgenda' => $updateAgendaRadicados->estadoRadiAgenda ?? 10,
                        ];
                    }
                }
            }

            $response['status'] = true;
            $response['TotalEnvios'] = $countQuery;
        }

        return $response;
    }

    /**
     *  Transaccion Correspondencia/Envio de Documentos 
     *  @param array $request 
     *  @return string success UpLoadFile 
     */
    public function actionUploadFile($request)
    {

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

                $uploadExecute = false;
                $checkClientes = [];
                $idRadiEnvios = []; // Array de radicados seleccionados en el form
                $path = '';

                # Consulta la accion de la transaccion de estado rechazado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'shipping']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                $arrayNotificacions = [];

                foreach($request['eventSelectData'] as $key => $radicados){
                    
                    $idRadiRadicado = $radicados['id'];

                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();
                    $remitentes = RadiRemitentes::findOne(['idRadiRadicado' => $Radicados->idRadiRadicado]);  
                    
                    $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $Radicados->idTrdDepeUserCreador]);
                    $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

                        if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);
                            $idCliente = $modelRemitente->idCliente;
                        }else{
                            $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
                            $idCliente = $modelRemitente->id;
                        }
                   
                    $idcliente = $idCliente;

                    if($fileUpload){

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
                        $validationExtension = ['pdf','jpg','png'];

                        if (!in_array($fileUpload->extension, $validationExtension)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                                'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /* Fin validar tipo de archivo */

                        $rutaOk = true; // ruta base de la carpeta existente
                        $anio = date('Y');
                        
                        if(!in_array($idcliente, $checkClientes)) {   
                            $pathUploadFile = Yii::getAlias('@webroot')                            
                                . "/" .  Yii::$app->params['bodegaRadicados']
                                . "/" . $anio                            
                                . "/" . $codigoGdTrdDependencia
                                . "/"
                            ;
                        }

                        array_push($checkClientes, $idcliente);

                        // Verificar que la carpeta exista y crearla en caso de no exista
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
                                'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar creación de la carpeta***/

                        // validar signo '/' en el numero de guia para no crear nuevos directorios
                        $noGuia = str_replace("/", "",  $request['formValue']['numeroGuiaRadiEnvio']);

                        $nomArchivo = Yii::$app->params['guiaName'].$noGuia.'.'.$fileUpload->extension; //

                        $path = $pathUploadFile.''.$nomArchivo;      

                        if(!file_exists($path)) {
                            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                            $uploadExecute = $fileUpload->saveAs($pathUploadFile . $nomArchivo);
                        }

                    }

                    $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

                    $formValue = $request['formValue'];

                    /*** Guardar en base de datosla info del documento ***/
                    $model = new RadiEnvios;

                    $model->attributes = $formValue;
                    $model->observacionRadiEnvio =  $formValue['observacion'];
                    $model->idRadiRadicado =  $idRadiRadicado;
                    $model->idUser =  Yii::$app->user->identity->id;
                    $model->rutaRadiEnvio = $path ?? null;
                    $model->extensionRadiEnvio = $fileUpload->extension ?? null;
                    $model->estadoRadiEnvio = Yii::$app->params['statusTodoText']['PendienteEntrega'];
                            
                    /** Procesar el errores de la fila */
                    if (!$model->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $model->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    /******* Fin Consultar si es un registro nuevo o actualizable ******/

                    # Se agrupa todos los envios de radicados creados
                    $idRadiEnvios[] = $model->idRadiEnvio;

                    $regional = CgRegionales::find()->select(['nombreCgRegional'])->where(['idCgRegional' => $model->idCgRegional])->one();
                    $proveedor = CgProveedores::find()->select(['nombreCgProveedor'])->where(['idCgProveedor' => $model->idCgProveedores])->one();
                    $servicio = CgEnvioServicios::find()->select(['nombreCgEnvioServicio'])->where(['idCgEnvioServicio' => $model->idCgEnvioServicio])->one();

                    $modelAtri = new RadiEnvios();
                    $attributeLabels = $modelAtri->attributeLabels();
                    $observacionAdd = $attributeLabels['numeroGuiaRadiEnvio'] . ': ' . $model->numeroGuiaRadiEnvio
                        . ', ' . $attributeLabels['idCgRegional'] . ': ' . $regional['nombreCgRegional']
                        . ', ' . $attributeLabels['idCgProveedores'] . ': ' . $proveedor['nombreCgProveedor']
                        . ', ' . $attributeLabels['idCgEnvioServicio'] . ': ' . $servicio['nombreCgEnvioServicio']
                        . ', ' . $attributeLabels['observacionRadiEnvio'] . ': ' . $model->observacionRadiEnvio
                    ;

                    /***    Log de Radicados  ***/
                    $logAddFiling = HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $model->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['shipping'] . ': '. $observacionAdd, //observación
                        [],
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/ 

                    /*** Validar si el archivo fué subido ***/
                    if ($uploadExecute == false ){
                        if(!empty($request['formValue']['fileUpload'])){

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                                'data' => ['error' => ['No se pudo subir el archivo']],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    /***    Log de Auditoria  ***/
                    // if(file_exists($path)) {
                    //     HelperLog::logAdd(
                    //         false,
                    //         Yii::$app->user->identity->id, //Id user
                    //         Yii::$app->user->identity->username, //username
                    //         Yii::$app->controller->route, //Modulo
                    //         Yii::$app->params['eventosLogText']['FileUpload'], // texto para almacenar en el evento
                    //         [],
                    //         [], //Data
                    //         array() //No validar estos campos
                    //     );
                    // }
                    /***    Log de Auditoria  ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $model->idRadiRadicado0->user_idTramitador, // Id user notificado
                        Yii::t('app','messageNotification')['shiping'], //Notificacion
                        Yii::$app->params['routesFront']['viewEnvio'], // url
                        $model->idRadiRadicado // id radicado
                    );
                    /***  Fin Notificacion  ***/


                    $transaction->commit();

                    //  Inicio Log de auditoria
                    $modelLog = $model;

                    $dataLog = '';
                    $labelModel = $modelLog->attributeLabels();
                    foreach ($modelLog as $key => $value) {
                        
                        switch ($key) {
                            case 'idUser':
                                $dataLog .= 'Usuario: '.$modelLog->idUser0->userDetalles->nombreUserDetalles.' '.$model->idUser0->userDetalles->apellidoUserDetalles. ', ';
                            break;
                            case 'idCgRegional':
                                $dataLog .= 'Regional: '.$modelLog->idCgRegional0->nombreCgRegional.', ';
                            break;
                            case 'idCgProveedores':
                                $dataLog .= 'Proveedor: '.$modelLog->idCgProveedores0->nombreCgProveedor.', ';
                            break;
                            case 'idCgEnvioServicio':
                                $dataLog .= 'Servicio: '.$modelLog->idCgEnvioServicio0->nombreCgEnvioServicio.', ';
                            break;
                            case 'idRadiRadicado':
                                $dataLog .= 'Radicado: '.$modelLog->idRadiRadicado0->numeroRadiRadicado.', ';
                            break;
                            case 'estadoRadiEnvio':
                                $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                            case 'rutaRadiEnvio':
                                if(file_exists($path)) {
                                    $dataLog .= $labelModel[$key].': '.$path.', ';
                                }
                            break;
                            case 'extensionRadiEnvio':
                                if(!empty($value)) {
                                    $dataLog .= $labelModel[$key].': '.$value.', ';
                                }
                            break;
                            
                            default:
                                $dataLog .= $labelModel[$key].': '.$value.', ';
                            break;
                        }
                    }
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla Cg RadiEnvios", //texto para almacenar en el evento
                        '', //DataOld
                        $dataLog, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/
                
                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' =>  $radicados['id'],
                        'idInitialList' => $radicados['idInitialList'] * 1,
                        'status' =>  $model->estadoRadiEnvio,
                        'statusText' =>  Yii::t('app', 'statusTodoNumber')[$model->estadoRadiEnvio],  
                    );

                    $arrayNotificacions['success'][] =  [
                        'numeroRadiRadicado' => $Radicados->numeroRadiRadicado,
                    ];
                }

                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';
                $tablaEnvios = RadiEnvios::tableName() . ' AS EN';
        
                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado', 'RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email', 'EN.creacionRadiEnvio'])
                    ->from($tablaRadicado);
                    // ->innerJoin($tablaEnvios, '`en`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaEnvios, ['EN' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                    // ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    $modelRadicado = $modelRadicado->where(['EN.idRadiEnvio' => $idRadiEnvios])
                ->all();

                # Se obtiene la información de los radicados a partir del número de guia
                $radiEnvio = RadiEnvios::find()->where(['IN', 'idRadiEnvio', $idRadiEnvios])->one();

                $arrayDatos = [];
                if(!is_null($modelRadicado)){
                    
                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado){

                        # Conversión del formato de fecha
                        $fechaEvento = date("Y-m-d", strtotime($infoRadicado['creacionRadiEnvio']));
                        $fecha = HelperRadicacion::getFormatosFecha($fechaEvento);

                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                        }

                        # Se valida que la fecha no sea la mismo y solo se agregue una vez
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'])) {
                            
                            if (!in_array($fecha['formatoFrontend'], $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'])) {
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'][] = $fecha['formatoFrontend'];
                            }

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'] = [$fecha['formatoFrontend']];
                        }
                    }
                }

                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {

                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                        $fecha  = implode(', ',$radicadosUsuario['fecha']); // fecha de creacion

                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        // $dataBase64Params = 'filing/filing-view/'.str_replace(array('/', '+'), array('_', '-'), base64_encode($numRadicado)); 
                        if ( count($radicadosUsuario['radicados']) > 1) {
                            $headText = 'headMailTextEnviados';
                            $textStart = 'mailEventRadicadoEnviados';

                            # Index de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexEnvio'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                        } else {         
                            $headText = 'headMailTextEnviado';
                            $textStart = 'mailEventRadicadoEnviado';

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['lastIdRadiRadicado']));

                            # View de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewEnvio'] . $dataBase64Params;

                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        }

                        # Envia la notificación de correo electronico al usuario de tramitar                
                        $headMailText = Yii::t('app', $headText, [
                            'prov'    =>  $radiEnvio->idCgProveedores0->nombreCgProveedor,
                        ]);

                        $textBody  = Yii::t('app', $textStart, [
                            'numRadi' => $numRadicado,
                            'numGuia' => $request['formValue']['numeroGuiaRadiEnvio'],
                            'servi'   => $radiEnvio->idCgEnvioServicio0->nombreCgEnvioServicio,
                            'fecha'   => $fecha
                        ]);

                        $subject = Yii::t('app', 'sendFiling');

                        $bodyMail = 'radicacion-html';
                        $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                    }
                }   
  
                /** Iteracion de mensajes de notificación para la agrupación de los mismos */
                $message = Yii::t('app','successUpdate');
                foreach ($arrayNotificacions as $key => $value) {
                    if ($key == 'success') {
                        if (count($value) > 1) {
                            $numerosRadicados = [];
                            foreach ($value as $row) {
                                $numerosRadicados[] = $row['numeroRadiRadicado'];
                            }
                            $message = Yii::t('app', 'sendCorrespondenceSuccessGroup', [
                                'numRadi' => implode(', ', $numerosRadicados)
                            ]);
                        } else {
                            $message = Yii::t('app', 'sendCorrespondenceSuccess', [
                                'numRadi' => $value[0]['numeroRadiRadicado']
                            ]);
                        }
                    }
                }
                /** Fin Iteracion de mensajes de notificación para la agrupación de los mismos */

                if ($uploadExecute == true ){
                    $response = [
                        'message' => $message . '<br>' . Yii::t('app','successUpLoadFile'),
                        'data' => $dataResponse ?? false,
                        'logAddFiling' =>  $logAddFiling,
                        'status' => 200,
                    ];

                } else { 
                    $response = [
                        'message' => $message,
                        'data' => $dataResponse ?? false,
                        'logAddFiling' =>  $logAddFiling,
                        'status' => 200,
                    ];
                }

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
     *  Transaccion Correspondencia / Generar Formato Control de Envios 
     *  Descarga de la planilla de correspondencia.
     *  @param array $request
     */
    public function actionCorrespondenceTemplate()
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

                //try {

                    foreach($request['ButtonSelectedData'] as $key => $radicado){

                        $rutaOk = true;
                        $radiRadicados = RadiRadicados::find()->where(['idRadiRadicado' => $radicado['id']])->one();

                        if(isset($radiRadicados)){

                            $tramitador = UserDetalles::find()->where(['idUser' => $radiRadicados->user_idTramitador])->one();

                            // Nombre completo del tramitador
                            $responsable = $tramitador->nombreUserDetalles.' '.$tramitador->apellidoUserDetalles;

                            // Informacion segun el tipo de destinatario/remitente (Funcionario-Cliente)
                            $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $radiRadicados->idRadiRadicado]);
    
                            $destinatarios = [];
                            $direcciones = [];
                            $municipios = [];
                            foreach ($modelRemitente as $dataDestinatario){

                                if($dataDestinatario['idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario']){

                                    $userDetalles = UserDetalles::find()->where(['idUser' => $dataDestinatario['idRadiPersona']])->one();
                                    $destinatarios[] = $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'];
                                    $direcciones[] =  Yii::t('app','addressNotSpecified'); 

                                } else {

                                    $clientes = Clientes::find()->where(['idCliente' => $dataDestinatario['idRadiPersona']])->one();
                                    $municipios[] = $clientes->idNivelGeografico30->nomNivelGeografico3;
                                    $destinatarios[] = $clientes['nombreCliente'];
                                    $direcciones[] =  $clientes['direccionCliente'];
                                }
                            }
                           
                            $destinatario = implode(", ", $destinatarios);
                            $direccion = implode(", ", $direcciones);
                            $municipio = implode(", ", $municipios);
                            
                            // Obtener informacion de creacion del radicado
                            $logRadicadosOrigen = RadiLogRadicados::find();
                                //->innerJoin('cgTransaccionesRadicados', '`cgTransaccionesRadicados`.`idCgTransaccionRadicado` = `radiLogRadicados`.`idTransaccion`')
                                $logRadicadosOrigen = HelperQueryDb::getQuery('innerJoin', $logRadicadosOrigen, 'cgTransaccionesRadicados', ['cgTransaccionesRadicados' => 'idCgTransaccionRadicado', 'radiLogRadicados' => 'idTransaccion']);
                                $logRadicadosOrigen = $logRadicadosOrigen->where([
                                    'cgTransaccionesRadicados.actionCgTransaccionRadicado' => 'add',
                                    'radiLogRadicados.idRadiRadicado' =>  $radiRadicados->idRadiRadicado])
                                ->one();

                                if(!isset($logRadicadosOrigen)){
                                    Yii::$app->response->statusCode = 200;
                                    $response = [
                                        'message' => Yii::t('app','notCreateLogHistory').' '.$radiRadicados->numeroRadiRadicado,
                                        'data' => [],
                                        'datafile' => false,
                                        'status' => Yii::$app->params['statusErrorValidacion'],
                                    ];
                                    return HelperEncryptAes::encrypt($response, true);
                                }

                            // Obtener informacion de entrega del radicado
                            $logRadicadosEntrega = RadiLogRadicados::find();
                            //->innerJoin('cgTransaccionesRadicados', '`cgTransaccionesRadicados`.`idCgTransaccionRadicado` = `radiLogRadicados`.`idTransaccion`')
                            $logRadicadosEntrega = HelperQueryDb::getQuery('innerJoin', $logRadicadosEntrega, 'cgTransaccionesRadicados', ['cgTransaccionesRadicados' => 'idCgTransaccionRadicado', 'radiLogRadicados' => 'idTransaccion']);
                            $logRadicadosEntrega = $logRadicadosEntrega->where(['cgTransaccionesRadicados.actionCgTransaccionRadicado' => 'delivered',
                                    'radiLogRadicados.idRadiRadicado' =>  $radiRadicados->idRadiRadicado])->one();

                             
                            $dependencias = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $logRadicadosOrigen['idDependencia']])->one();   

                                if(!isset($dependencias)){
                                    Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app','depNotFound').' '.$radiRadicados->numeroRadiRadicado,  
                                            'data' => [],
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                    return HelperEncryptAes::encrypt($response, true);
                                }

                            $radiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $radiRadicados->idRadiRadicado])->orderBy(['idRadiEnvio' => SORT_DESC])->one();

                            $datapdf[] = [
                                'NO' => $key+1,
                                'USUARIO_RESPONSABLE' => $responsable,
                                'FIRMA_MENSAJERO'     => '',
                                'RADICADO'            => $radiRadicados->numeroRadiRadicado,
                                'FECHA_RADICADO'      => $radiRadicados->creacionRadiRadicado,
                                'DESTINA_RESPO'       => $destinatario,
                                'DIRECCION'           => $direccion,
                                'MUNICIPIO'           => $municipio,
                                // 'ASUNTO'              => $radiRadicados->asuntoRadiRadicado,
                                // 'ORIGEN'              => $dependencias->nombreGdTrdDependencia ?? Yii::t('app','depNotFound'),
                                // 'FIRMA'               => '',
                                'FECHA_DE_RECIBIDO'   => '', // $logRadicadosEntrega->fechaRadiLogRadicado ?? Yii::t('app','vigente'),
                                'NO_GUIA'             => $radiEnvios->numeroGuiaRadiEnvio
                            ];

                            $dependenciaCodigo = GdTrdDependencias::findOne(['idGdTrdDependencia' => $radiRadicados->idTrdDepeUserCreador]);
                            $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDocuments'] . "/" .$dependenciaCodigo->codigoGdTrdDependencia.'/';
                            $filename = 'planilla_de_correspondencia_guia_'.$radiEnvios['numeroGuiaRadiEnvio'];

                        }else{

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => [],
                                'datafile' => false,
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);

                        }

                    }   
                    
                    // Verificar que la carpeta exista y crearla en caso de no exista
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
                            'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                            'datafile' => false,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Validar creación de la carpeta***/

                    $userAuth = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                    $nameUserAuth = $userAuth['nombreUserDetalles'].' '.$userAuth['apellidoUserDetalles'];
                    PdfController::generar_pdf_formatoh('GestiondeCorrespondencia','correspondenciaView', $filename, $pathUploadFile, $datapdf, [], $nameUserAuth);

                    $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceTemplate']);
                    $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                    $modelAttach = [
                        'rutaRadiDocumento' => $pathUploadFile,
                        'nombreRadiDocumento' => $filename.'.pdf'
                    ];

                    $userDetalles = User::find()->where(['id' => Yii::$app->user->identity->id])->one();
                    $emailTramitador = $userDetalles['email'];
                    $headMailText = Yii::t('app', 'headMailTextRegistro');
                    $textBody = Yii::t('app', 'textBodyRespuestatemplate');
                    $bodyMail = 'radicacion-html';
                    $objAttach = (object) $modelAttach;
                    $subject = 'subjectResponseTemplate';
                    $file = $pathUploadFile.$filename.'.pdf'; //ruta y nombre de la planilla
                    $buttonDisplay = false;

                    $envioCorreo = CorreoController::addFile($emailTramitador, $headMailText, $textBody, $bodyMail, $file, $subject, $buttonDisplay);

                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['createTemplete'], //texto para almacenar en el evento
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/   

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $radiRadicados->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['createTemplete'], //observación 
                        [],
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/ 

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $radiRadicados->userIdTramitador['id'], // Id user notificado
                        Yii::t('app','messageNotification')['correspondenceForm'], //Notificacion
                        Yii::$app->params['routesFront']['indexEnvio'], // url
                        '' // id radicado
                    );
                    /***  Fin Notificacion  ***/

                    $dataFile = base64_encode(file_get_contents($objAttach->rutaRadiDocumento.$objAttach->nombreRadiDocumento));
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successGeneratePDF'),
                        'nombreDoc' => $objAttach->nombreRadiDocumento,
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
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'datafile' => false,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'datafile' => false,
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     *  Transaccion Correspondencia / Generar Formato Control de Envios en excel
     *  Descarga de la planilla de correspondencia.
     */
    public function actionCorrespondenceTemplateExcel()
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

                ini_set('memory_limit', '3073741824');
                ini_set('max_execution_time', 900);

                foreach($request['ButtonSelectedData'] as $key => $radicado){

                    $rutaOk = true;
                    $radiRadicados = RadiRadicados::find()->where(['idRadiRadicado' => $radicado['id']])->one();

                    if(isset($radiRadicados)){

                        $tramitador = UserDetalles::find()->where(['idUser' => $radiRadicados->user_idTramitador])->one();

                        // Nombre completo del tramitador
                        $responsable = $tramitador->nombreUserDetalles.' '.$tramitador->apellidoUserDetalles;

                        // Informacion segun el tipo de destinatario/remitente (Funcionario-Cliente)
                        $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $radiRadicados->idRadiRadicado]);

                        $destinatarios = [];
                        $direcciones = [];
                        $municipios = [];
                        foreach ($modelRemitente as $dataDestinatario){

                            if($dataDestinatario['idTipoPersona'] == Yii::$app->params['tipoPersonaText']['funcionario']){

                                $userDetalles = UserDetalles::find()->where(['idUser' => $dataDestinatario['idRadiPersona']])->one();
                                $destinatarios[] = $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'];
                                $direcciones[] =  Yii::t('app','addressNotSpecified'); 

                            } else {

                                $clientes = Clientes::find()->where(['idCliente' => $dataDestinatario['idRadiPersona']])->one();
                                $municipios[] = $clientes->idNivelGeografico30->nomNivelGeografico3;
                                $destinatarios[] = $clientes['nombreCliente'];
                                $direcciones[] =  $clientes['direccionCliente'];
                            }
                        }
                       
                        $destinatario = implode(", ", $destinatarios);
                        $direccion = implode(", ", $direcciones);
                        $municipio = implode(", ", $municipios);
                        
                        // Obtener informacion de creacion del radicado
                        $logRadicadosOrigen = RadiLogRadicados::find();
                            $logRadicadosOrigen = HelperQueryDb::getQuery('innerJoin', $logRadicadosOrigen, 'cgTransaccionesRadicados', ['cgTransaccionesRadicados' => 'idCgTransaccionRadicado', 'radiLogRadicados' => 'idTransaccion']);
                            $logRadicadosOrigen = $logRadicadosOrigen->where([
                                'cgTransaccionesRadicados.actionCgTransaccionRadicado' => 'add',
                                'radiLogRadicados.idRadiRadicado' =>  $radiRadicados->idRadiRadicado])
                            ->one();

                            if(!isset($logRadicadosOrigen)){
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','notCreateLogHistory').' '.$radiRadicados->numeroRadiRadicado,
                                    'data' => [],
                                    'datafile' => false,
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                        // Obtener informacion de entrega del radicado
                        $logRadicadosEntrega = RadiLogRadicados::find();
                        $logRadicadosEntrega = HelperQueryDb::getQuery('innerJoin', $logRadicadosEntrega, 'cgTransaccionesRadicados', ['cgTransaccionesRadicados' => 'idCgTransaccionRadicado', 'radiLogRadicados' => 'idTransaccion']);
                        $logRadicadosEntrega = $logRadicadosEntrega->where(['cgTransaccionesRadicados.actionCgTransaccionRadicado' => 'delivered',
                                'radiLogRadicados.idRadiRadicado' =>  $radiRadicados->idRadiRadicado])->one();

                         
                        $dependencias = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $logRadicadosOrigen['idDependencia']])->one();   

                            if(!isset($dependencias)){
                                Yii::$app->response->statusCode = 200;
                                    $response = [
                                        'message' => Yii::t('app','depNotFound').' '.$radiRadicados->numeroRadiRadicado,  
                                        'data' => [],
                                        'status' => Yii::$app->params['statusErrorValidacion'],
                                    ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                        $radiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $radiRadicados->idRadiRadicado])->orderBy(['idRadiEnvio' => SORT_DESC])->one();

                        $datapdf[] = [
                            'NO' => $key+1,
                            'USUARIO_RESPONSABLE' => $responsable,
                            'FIRMA_MENSAJERO'     => '',
                            'RADICADO'            => $radiRadicados->numeroRadiRadicado,
                            'FECHA_RADICADO'      => $radiRadicados->creacionRadiRadicado,
                            'DESTINA_RESPO'       => $destinatario,
                            'DIRECCION'           => $direccion,
                            'MUNICIPIO'           => $municipio,
                            'FECHA_DE_RECIBIDO'   => '',
                            'NO_GUIA'             => $radiEnvios->numeroGuiaRadiEnvio
                        ];

                        $dependenciaCodigo = GdTrdDependencias::findOne(['idGdTrdDependencia' => $radiRadicados->idTrdDepeUserCreador]);
                        $carpetaDestino = Yii::$app->params['routeDocuments'] . "/" .$dependenciaCodigo->codigoGdTrdDependencia;
                        $pathUploadFile = Yii::getAlias('@webroot') . "/" .$carpetaDestino.'/';
                        $filename = 'planilla_de_correspondencia_guia_'.$radiEnvios['numeroGuiaRadiEnvio'];

                    }else{

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => [],
                            'datafile' => false,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                }
                    
                // Verificar que la carpeta exista y crearla en caso de no exista
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
                        'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                        'datafile' => false,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /*** Fin Validar creación de la carpeta***/

                $userAuth = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                
                $nameUserAuth = $userAuth['nombreUserDetalles'].' '.$userAuth['apellidoUserDetalles'];

                $fileName = 'planilla_de_correspondencia.xlsx';

                // PdfController::generar_pdf_formatoh('GestiondeCorrespondencia','correspondenciaView', $filename, $pathUploadFile, $datapdf, [], $nameUserAuth);

                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceTemplate']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Estructura y generación del excel
                $sheets = HelperLoads::getEstructureCorrespondenceTemplateExcel($datapdf, $nameUserAuth);
                $generate = HelperGenerateExcel::generarExcelV2($carpetaDestino, $fileName, $sheets, true);
                
                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['createTemplete'], //texto para almacenar en el evento
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/   

                /***    Log de Radicados  ***/
                HelperLog::logAddFiling(
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                    $radiRadicados->idRadiRadicado, //Id radicado
                    $idTransacion,
                    Yii::$app->params['eventosLogTextRadicado']['createTemplete'], //observación 
                    [],
                    array() //No validar estos campos
                );
                /***    Fin log Radicados   ***/ 

                /***  Notificacion  ***/
                HelperNotification::addNotification(
                    Yii::$app->user->identity->id, //Id user creador
                    $radiRadicados->userIdTramitador['id'], // Id user notificado
                    Yii::t('app','messageNotification')['correspondenceForm'], //Notificacion
                    Yii::$app->params['routesFront']['indexEnvio'], // url
                    '' // id radicado
                );
                /***  Fin Notificacion  ***/

                $routeDocument = $generate['rutaDocumento'];

                /* Enviar archivo en base 64 como respuesta de la petición **/
                if($generate['status'] && file_exists($routeDocument)){

                    $dataFile = base64_encode(file_get_contents($routeDocument));
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successGeneratePDF'),
                        'nombreDoc' => $fileName,
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
                        'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                // $return['datafile'] = $dataFile;

                // return $return;

            } else {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'datafile' => false,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
         } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'datafile' => false,
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }


    /**
     *  Transaccion Correspondencia / Devolucion del envío de correspondencia (Devolución)
     *  @param array $request
     */
    public function actionReturnDelivery(){ 

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = ['id' => [1], 'observacionRadiEnvio' => 'Devolución de correspondencia',
            //  'idCgMotivoDevolucion' => 4,];
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

                $saveDataValid = true;
                $errors = [];
                $dataResponse = []; // Data donde se guardan los estados nuevos de los envios de radicados
                $idRadiEnvios = []; // Array de radicados seleccionados en el form
                $arrayNotificacions = [];

                $userLogued = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                $nombreUserLogued = $userLogued->nombreUserDetalles . ' ' . $userLogued->apellidoUserDetalles;
                
                $transaction = Yii::$app->db->beginTransaction();
                
                # Consulta la accion de la transaccion de estado rechazado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'returnDelivery']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Actualizacion de los radicados con el nuevo estado y observacion.
                foreach ($request['ButtonSelectedData'] as $infoRadicado) {
                    
                    # Id del radicado envio
                    $id = $infoRadicado['id'];

                    # Observacion de la devolución del envio
                    $observacion = $request['data']['observacion'];

                    # Id del motivo de envio
                    $idMotivo = $request['data']['idCgMotivoDevolucion'];                    

                    # Se actualiza los radicados su observacion y estado
                    $modelRadiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $id])->orderBy(['idRadiEnvio' => SORT_DESC])->one();

                    /** Validar si el radicado está finalizado y retornar como un error, pero continuando con el proceso */
                    $modelRadiRadicados = RadiRadicados::find()->select(['numeroRadiRadicado','estadoRadiRadicado'])->where(['idRadiRadicado' => $id])->one();

                    if ($modelRadiRadicados->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']) {
                        // Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $id,
                            'idInitialList' => $infoRadicado['idInitialList'] * 1,
                            'status' =>  $modelRadiEnvios->estadoRadiEnvio,
                            'statusText' =>  Yii::t('app', 'statusTodoNumber')[$modelRadiEnvios->estadoRadiEnvio],  
                        );

                        $arrayNotificacions['dangerFinalizado'][] =  [
                            'numeroRadiRadicado' => $modelRadiRadicados->numeroRadiRadicado,
                        ];
                        continue;
                    }
                    /** Fin Validar si el radicado está finalizado y retornar como un error, pero continuando con el proceso */

                    // Valores anteriores del modelo se utiliza para el log
                    $dataLogOld = '';
                    $dataLogNew = '';
                    $labelModel = $modelRadiEnvios->attributeLabels();
                    foreach ($modelRadiEnvios as $key => $value) {                        
                        switch ($key) {
                            case 'observacionRadiEnvio':
                                $dataLogOld .= $labelModel[$key].': '.$value.', ';
                            break;
                            case 'estadoRadiEnvio':
                                $dataLogOld .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                        }
                    }

                    $modelRadiEnvios->observacionRadiEnvio = $observacion;
                    $modelRadiEnvios->estadoRadiEnvio = Yii::$app->params['statusTodoText']['Devuelto'];

                    # Se agrupa todos los radicados seleccionados
                    $idRadiEnvios[] = $modelRadiEnvios->idRadiEnvio;                   

                    if (!$modelRadiEnvios->save()) {
                        // Valida false ya que no se guarda
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadiEnvios->getErrors());                        
                        break;
                    }

                    // Valores nuevos del modelo se utiliza para el log
                    foreach ($modelRadiEnvios as $key => $value) {                        
                        switch ($key) {
                            case 'observacionRadiEnvio':
                                $dataLogNew .= $labelModel[$key].': '.$value.', ';
                            break;
                            case 'estadoRadiEnvio':
                                $dataLogNew .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                        }
                    }

                    # Se agrega la relación entre el motivo de devolución y el envio
                    $modelRadiEnvioDevolucion = new RadiEnviosDevolucion();
                    $modelRadiEnvioDevolucion->idCgMotivoDevolucion = $idMotivo;
                    $modelRadiEnvioDevolucion->idRadiEnvio = $modelRadiEnvios->idRadiEnvio;

                    if (!$modelRadiEnvioDevolucion->save()) {
                        // Valida false ya que no se guarda
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadiEnvioDevolucion->getErrors());                        
                        break;
                    }

                    $nombreCgMotivoDevolucion = $modelRadiEnvioDevolucion->idCgMotivoDevolucion0->nombreCgMotivoDevolucion;

                    // Valores nuevos del modelo se utiliza para el log RadiEnvioDevolucion
                    $labelModelEnvioDevolucion = $modelRadiEnvioDevolucion->attributeLabels();
                    foreach ($modelRadiEnvioDevolucion as $key => $value) {                        
                        switch ($key) {
                            case 'idCgMotivoDevolucion':
                                $dataLogNew .= $labelModelEnvioDevolucion[$key].': '.$nombreCgMotivoDevolucion.', ';
                            break;
                            case 'idRadiEnvio':
                                $dataLogNew .= $labelModelEnvioDevolucion[$key].': '.$value.', ';
                            break;
                        }
                    }
                    
                    $observacionLogDelivery = Yii::$app->params['eventosLogText']['returnDelivery'] . $modelRadiRadicados->numeroRadiRadicado
                        . ' con el motivo ' . $nombreCgMotivoDevolucion 
                        . ' por el usuario ' . $nombreUserLogued
                    ;
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        $observacionLogDelivery,
                        $dataLogOld, //DataOld
                        $dataLogNew, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/                   

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $modelRadiEnvios->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['returnDelivery'] . $nombreCgMotivoDevolucion . ' con la siguiente descripción ' . $observacion, //observación 
                        $modelRadiEnvios,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/ 

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $modelRadiEnvios->idRadiRadicado0->user_idTramitador, // Id user notificado
                        Yii::t('app','messageNotification')['returnsDelivery'].$modelRadiEnvios->idRadiRadicado0->numeroRadiRadicado, //Notificacion
                        Yii::$app->params['routesFront']['viewEnvio'], // url
                        $modelRadiEnvios->idRadiRadicado // id radicado
                    );
                    /***  Fin Notificacion  ***/
                    
                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'idInitialList' => $infoRadicado['idInitialList'] * 1,
                        'status' =>  $modelRadiEnvios->estadoRadiEnvio,
                        'statusText' =>  Yii::t('app', 'statusTodoNumber')[$modelRadiEnvios->estadoRadiEnvio],  
                    );

                    $arrayNotificacions['success'][] =  [
                        'numeroRadiRadicado' => $modelRadiRadicados->numeroRadiRadicado,
                    ];
                  
                }

                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';
                $tablaEnvios = RadiEnvios::tableName() . ' AS EN';
                $tablaProveedor = CgProveedores::tableName() . ' AS PR';
        
                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado' ,'RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email','PR.nombreCgProveedor'])
                    ->from($tablaRadicado);
                    // ->innerJoin($tablaEnvios, '`en`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaEnvios, ['EN' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                    // ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`');
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    // ->innerJoin($tablaProveedor, '`pr`.`idCgProveedor` = `en`.`idCgProveedores`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaProveedor, ['PR' => 'idCgProveedor', 'EN' => 'idCgProveedores']);
                    $modelRadicado = $modelRadicado->where(['EN.idRadiEnvio' => $idRadiEnvios])
                ->all();

 
                $arrayDatos = [];
                if(!is_null($modelRadicado)){
                    
                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado){
                        
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicados'][] = $infoRadicado['idRadiRadicado'];

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicados'][] = $infoRadicado['idRadiRadicado'];
                        }

                        # Se valida que el proveedor no sea el mismo y solo se agregue una vez
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]]['proveedor'])) {
                            
                            if (!in_array($infoRadicado['nombreCgProveedor'], $arrayDatos[$infoRadicado["user_idTramitador"]]['proveedor'])) {
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['proveedor'][] = $infoRadicado['nombreCgProveedor'];
                            }

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['proveedor'] = [$infoRadicado['nombreCgProveedor']];
                        }
                    }
                }

                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {

                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);

                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if ( count($radicadosUsuario['radicados']) > 1) {

                            $headText = 'headMailTextReturns';
                            $textStart = 'textBodyReturnsDelivery';

                            # Index de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexEnvio'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                        } else {     
                   
                            $headText = 'headMailTextReturn';
                            $textStart = 'textBodyReturnDelivery';

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['idRadicados'][0]));

                            # View de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewEnvio'] . $dataBase64Params;

                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        }

                        # Envia la notificación de correo electronico al usuario de tramitar                
                        $proveedores = implode(', ',$radicadosUsuario['proveedor']);

                        $headMailText = Yii::t('app', $headText, [
                            'proveedor' => $proveedores,
                        ]);

                        $textBody  = Yii::t('app', $textStart, [
                            'NoRadicado'    => $numRadicado,
                            'Observacion'   => $observacion,
                            'fecha'         => date('Y-m-d'),
                        ]);

                        $subject = Yii::t('app', 'subjectReturnDelivery');
                        $bodyMail = 'radicacion-html';

                        $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                    }
                }              


                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {
                    
                    $transaction->commit();

                    /** Iteracion de mensajes de notificación para la agrupación de los mismos */
                    foreach ($arrayNotificacions as $key => $value) {
                        if ($key == 'success') {
                            if (count($value) > 1) {
                                $numerosRadicados = [];
                                foreach ($value as $row) {
                                    $numerosRadicados[] = $row['numeroRadiRadicado'];
                                }
                                $notificacion[] =  [
                                    'message' => Yii::t('app', 'returnFilingSuccessNotyTrueGroup', [
                                        'numRadi' => implode(', ', $numerosRadicados)
                                    ]),
                                    'type' => 'success'
                                ];
                            } else {
                                $notificacion[] =  [
                                    'message' => Yii::t('app', 'returnFilingSuccessNotyTrue', [
                                        'numRadi' => $value[0]['numeroRadiRadicado']
                                    ]),
                                    'type' => 'success'
                                ];
                            }
                        } elseif ($key == 'dangerFinalizado') {
                            if (count($value) > 1) {
                                $numerosRadicados = [];
                                foreach ($value as $row) {
                                    $numerosRadicados[] = $row['numeroRadiRadicado'];
                                }
                                $notificacion[] =  [
                                    'message' => Yii::t('app', 'errorRadicadoFinalizadoGroup', [
                                        'numRadi' => implode(', ', $numerosRadicados)
                                    ]),
                                    'type' => 'danger'
                                ];
                            } else {
                                $notificacion[] =  [
                                    'message' => Yii::t('app', 'errorRadicadoFinalizado', [
                                        'numRadi' => $value[0]['numeroRadiRadicado']
                                    ]),
                                    'type' => 'danger'
                                ];
                            }
                        }
                    }
                    /** Fin Iteracion de mensajes de notificación para la agrupación de los mismos */

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpdate'),
                        'data' => $dataResponse ?? false,
                        'notificacion' => $notificacion ?? [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados #      
               

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
     *  Transaccion Correspondencia / Devolucion del envío de correspondencia (Entrega)
     *  @param array $request
     */
    public function actionDelivered(){

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

                $idRadiEnvios = []; // Array de radicados seleccionados en el form
                $dataResponse = []; // Data donde se guardan los estados nuevos de los radicados
                $notificacion = [];

                $transaction = Yii::$app->db->beginTransaction();

                # Consulta la accion de la transaccion del estado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'delivered']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                $arrayNotificacions = [];

                # Actualizacion de los radicados con el nuevo estado y observacion.
                foreach ($request['ButtonSelectedData'] as $key => $infoRadicado) {
                    
                    # Id del radicado envio
                    $id = $infoRadicado['id'];
              
                    # Se actualiza los radicados su observacion y estado
                    $modelRadiEnvios = RadiEnvios::find()->where(['idRadiRadicado' => $id])->orderBy(['idRadiEnvio' => SORT_DESC])->one();

                    /** Validar si el radicado está finalizado y retornar como un error, pero continuando con el proceso */
                    $modelRadiRadicados = RadiRadicados::find()->select(['numeroRadiRadicado','estadoRadiRadicado'])->where(['idRadiRadicado' => $id])->one();
                    if ($modelRadiRadicados->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']) {
                        // Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $id,
                            'idInitialList' => $infoRadicado['idInitialList'] * 1,
                            'status' =>  $modelRadiEnvios->estadoRadiEnvio,
                            'statusText' =>  Yii::t('app', 'statusTodoNumber')[$modelRadiEnvios->estadoRadiEnvio],  
                        );

                        $arrayNotificacions['dangerFinalizado'][] =  [
                            'numeroRadiRadicado' => $modelRadiRadicados->numeroRadiRadicado,
                        ];
                        continue;
                    }
                    /** Fin Validar si el radicado está finalizado y retornar como un error, pero continuando con el proceso */

                    // Valores anteriores del modelo se utiliza para el log
                    $dataLogOld = '';
                    $dataLogNew = '';
                    $labelModel = $modelRadiEnvios->attributeLabels();
                    foreach ($modelRadiEnvios as $key => $value) {
                        
                        switch ($key) {
                            case 'estadoRadiEnvio':
                                $dataLogOld .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                        }
                    }

                    $modelRadiEnvios->estadoRadiEnvio = Yii::$app->params['statusTodoText']['Entregado'];

                    // Recore la nueva información del estado
                    foreach ($modelRadiEnvios as $key => $value) {
                        
                        switch ($key) {
                            case 'estadoRadiEnvio':
                                $dataLogNew .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                        }
                    }

                    # Se agrupa todos los radicados seleccionados
                    $idRadiEnvios[] = $modelRadiEnvios->idRadiEnvio;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", tabla radiEnvios del radicado: ".$modelRadiEnvios->idRadiRadicado0->numeroRadiRadicado , //texto para almacenar en el evento
                        $dataLogOld, //DataOld
                        $dataLogNew, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/   

                    /***    Log de Radicados  ***/
                    $logAddFiling = HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $modelRadiEnvios->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['delivered'], //observación
                        $modelRadiEnvios,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/

                    if (!$modelRadiEnvios->save()) {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $modelRadiEnvios->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $arrayNotificacions['success'][] =  [
                        'numeroRadiRadicado' => $modelRadiRadicados->numeroRadiRadicado,
                    ];

                     // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'idInitialList' => $infoRadicado['idInitialList'] * 1,
                        'status' =>  $modelRadiEnvios->estadoRadiEnvio,
                        'statusText' =>  Yii::t('app', 'statusTodoNumber')[$modelRadiEnvios->estadoRadiEnvio],  
                    );
                  
                }              
           
                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';
                $tablaEnvios = RadiEnvios::tableName() . ' AS EN';
                $tablaServicio = CgEnvioServicios::tableName() . ' AS SER';
        
                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado', 'RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email','SER.nombreCgEnvioServicio','EN.numeroGuiaRadiEnvio','EN.creacionRadiEnvio'])
                    ->from($tablaRadicado);
                    // ->innerJoin($tablaEnvios, '`en`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaEnvios, ['EN' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                    // ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    // ->innerJoin($tablaServicio, '`ser`.`idCgEnvioServicio` = `en`.`idCgEnvioServicio`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaServicio, ['SER' => 'idCgEnvioServicio', 'EN' => 'idCgEnvioServicio']);
                    $modelRadicado = $modelRadicado->where(['EN.idRadiEnvio' => $idRadiEnvios])
                ->all();


                $arrayDatos = [];
                if(!is_null($modelRadicado)){
                    
                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado) {

                        # Conversión del formato de fecha
                        $fechaEvento = date("Y-m-d", strtotime($infoRadicado['creacionRadiEnvio']));
                        $fecha = HelperRadicacion::getFormatosFecha($fechaEvento);
                        
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                        }

                        # Validación para agregar un nombre de servicio sin repetirse
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]]['servicio'])){

                            if(!in_array($infoRadicado['nombreCgEnvioServicio'], $arrayDatos[$infoRadicado["user_idTramitador"]]['servicio'] ))
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['servicio'][] = $infoRadicado['nombreCgEnvioServicio'];                        
                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['servicio'][] = $infoRadicado['nombreCgEnvioServicio']; 
                        }

                        # Validación para agregar una fecha creación sin repetirse
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'])){

                            if(!in_array($fecha['formatoFrontend'], $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'] ))
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'][] = $fecha['formatoFrontend'];
                            
                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['fecha'][] = $fecha['formatoFrontend']; 
                        }


                        # Validación para agregar número guia sin repetirse
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]]['guia'])){

                            if(!in_array($infoRadicado['numeroGuiaRadiEnvio'], $arrayDatos[$infoRadicado["user_idTramitador"]]['guia'] ))
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['guia'][] = $infoRadicado['numeroGuiaRadiEnvio'];
                            
                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['guia'][] = $infoRadicado['numeroGuiaRadiEnvio']; 
                        }
                        
                    }
                }

                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {

                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                        $servicios = implode(', ',$radicadosUsuario['servicio']);
                        $numGuia = implode(', ',$radicadosUsuario['guia']);
                        $fechas = implode(', ',$radicadosUsuario['fecha']);


                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if ( count($radicadosUsuario['radicados']) > 1) {
                            $headText = 'headMailTextsDelivered';
                            $textStart = 'mailEventRadicadosEntrega';

                            # Index de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexEnvio'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                        } else {                            
                            $headText = 'headMailTextDelivered';
                            $textStart = 'mailEventRadicadoEntrega';

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['lastIdRadiRadicado']));

                            # View de distribución y envio
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewEnvio'] . $dataBase64Params;
                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        }
                       
                        $headMailText = Yii::t('app', $headText);

                        $textBody  = Yii::t('app', $textStart, [
                            'numRadi' => $numRadicado,
                            'numGuia' => $numGuia,
                            'servi'   => $servicios,
                            'fecha'   => $fechas
                        ]);                        

                        $subject = Yii::t('app', 'subjectDelivery');
                        $bodyMail = 'radicacion-html';                    

                        $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                    } 
                    # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos    
                }

                $transaction->commit();

                /** Iteracion de mensajes de notificación para la agrupación de los mismos */
                foreach ($arrayNotificacions as $key => $value) {
                    if ($key == 'success') {
                        if (count($value) > 1) {
                            $numerosRadicados = [];
                            foreach ($value as $row) {
                                $numerosRadicados[] = $row['numeroRadiRadicado'];
                            }
                            $notificacion[] =  [
                                'message' => Yii::t('app', 'correspondenceDeliveredSuccessGroup', [
                                    'numRadi' => implode(', ', $numerosRadicados)
                                ]),
                                'type' => 'success'
                            ];
                        } else {
                            $notificacion[] =  [
                                'message' => Yii::t('app', 'correspondenceDeliveredSuccess', [
                                    'numRadi' => $value[0]['numeroRadiRadicado']
                                ]),
                                'type' => 'success'
                            ];
                        }
                    } elseif ($key == 'dangerFinalizado') {
                        if (count($value) > 1) {
                            $numerosRadicados = [];
                            foreach ($value as $row) {
                                $numerosRadicados[] = $row['numeroRadiRadicado'];
                            }
                            $notificacion[] =  [
                                'message' => Yii::t('app', 'errorRadicadoFinalizadoGroup', [
                                    'numRadi' => implode(', ', $numerosRadicados)
                                ]),
                                'type' => 'danger'
                            ];
                        } else {
                            $notificacion[] =  [
                                'message' => Yii::t('app', 'errorRadicadoFinalizado', [
                                    'numRadi' => $value[0]['numeroRadiRadicado']
                                ]),
                                'type' => 'danger'
                            ];
                        }
                    }
                }
                /** Fin Iteracion de mensajes de notificación para la agrupación de los mismos */

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','successUpdate'),
                    'notificacion' => $notificacion ?? [],
                    'data' => $dataResponse ?? false,
                    'logAddFiling' => $logAddFiling,
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
     *  validacion para la seleccion de multiples radicados que todos pertenezcan al mismo cliente.
     *  
     *  Transaccion Correspondencia/Envio de Documentos
     * 
     * @param array $id_radicados
     * @return boolean validatecliente 
     */
    public function actionValidarCliente(){

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
            $clientes = [];

                foreach($request['eventSelectData'] as $key => $radicado){

                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $radicado['id']])->one();
                    $remitentes = RadiRemitentes::findOne(['idRadiRadicado' => $Radicados->idRadiRadicado]);    

                    if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);
                        $nombreRemitente = $modelRemitente->nombreCliente;
                        $idCliente = $modelRemitente->idCliente;
                    }else{
                        $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
                        $nombreRemitente = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                        $idCliente = $modelRemitente->id;
                    }
                    $clientes[$idCliente] = 1;

                }

                // valida si existe mas de dos clintes 
                if(count($clientes) >= 2){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'validatecliente' => true,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);


                }else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'validatecliente' => false,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                    
                }

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


    /**
     *  Carga la lista de regionales de Transaccion Correspondencia/Envio de Documentos
     * 
     * @return array dataCgRegionales 
     */
    public function actionIndexGeneralFilingLists()
    {

        $dataCgRegionales = [];

        $modelCgRegionales  = CgRegionales::find()->where(['estadoCgRegional' => Yii::$app->params['statusTodoText']['Activo']])->all();

        foreach ($modelCgRegionales as $row) {

            $dataCgRegionales[] = array(
                "id" => (int) $row['idCgRegional'],
                "val" => $row['nombreCgRegional'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataCgRegionales' => $dataCgRegionales, // Tipos de medios de Recepcion
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }
    
    /**
     *  Carga la lista de providedores / servicios-activos de Transaccion Correspondencia/Envio de Documentos
     * 
     * @param integer  $idCgRegional
     * @param integer  $idCgProveedores
     * 
     * @return array dataCgProveedor /  dataCgEnvioServicio
     */
    public function actionListEnvioDocs()
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

                $CgProveedor = CgProveedores::find();
                // ->innerJoin('cgProveedoresRegional', '`cgProveedoresRegional`.`idCgProveedor` = `cgProveedores`.`idCgProveedor`')
                $CgProveedor = HelperQueryDb::getQuery('innerJoin', $CgProveedor, 'cgProveedoresRegional', ['cgProveedoresRegional' => 'idCgProveedor', 'cgProveedores' => 'idCgProveedor']);
                $CgProveedor = $CgProveedor->where(['cgProveedoresRegional.idCgRegional' =>  $request['idCgRegional'] ?? 0])
                ->andWhere(['cgProveedores.estadoCgProveedor' => Yii::$app->params['statusTodoText']['Activo']])->all();

                foreach ($CgProveedor as $row) {
                    $dataCgProveedor[] = array(
                        "id" => (int) $row['idCgProveedor'],
                        "val" => $row['nombreCgProveedor'],
                    );
                }

                $CgEnvioServicio = CgEnvioServicios::find();
                // ->innerJoin('cgProveedoresServicios', '`cgProveedoresServicios`.`idCgEnvioServicios` = `cgEnvioServicios`.`idCgEnvioServicio`')
                $CgEnvioServicio = HelperQueryDb::getQuery('innerJoin', $CgEnvioServicio, 'cgProveedoresServicios', ['cgProveedoresServicios' => 'idCgEnvioServicios', 'cgEnvioServicios' => 'idCgEnvioServicio']);
                $CgEnvioServicio = $CgEnvioServicio->where(['cgProveedoresServicios.idCgProveedor' =>  $request['idCgProveedores'] ?? 0])
                ->andWhere(['cgEnvioServicios.estadoCgEnvioServicio' => Yii::$app->params['statusTodoText']['Activo']])->all();

                foreach ($CgEnvioServicio as $row) {
                    $dataCgEnvioServicio[] = array(
                        "id" => (int) $row['idCgEnvioServicio'],
                        "val" => $row['nombreCgEnvioServicio'],
                    );
                }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'dataCgProveedor' => $dataCgProveedor ?? [],
                'dataCgEnvioServicio' =>  $dataCgEnvioServicio ?? [],
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


    }

    /**
     * Action que permite enviar la información del radicado, incluyendo los documentos
     * al remitente que se este relacionado en el radicado
     */
    public function actionSendReplyMail()
    {
        # Aumento de memoria para poder esperar el proceso del envio de correo con archivos pesados
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');

            // Valida el valor de la transacción correspondiente al envio de la respuesta
            $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'sendMail']);

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
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $msgError = [];
            $dataCorrecta = [];
            $errors = []; //Errores del modelo Radicado
            $correoElectronicoCliente = [];
            $userExternos = [];
            $usuarios = [];

            if($request['sendMail']['idUsuariosExternos'] && count($request['sendMail']['idUsuariosExternos']) > 0) {
                foreach($request['sendMail']['idUsuariosExternos'] as $idUserExternos) {
                    $userExternos[] = $idUserExternos;
                }
            }

            if($request['sendMail']['idUsuariosInfo'] && count($request['sendMail']['idUsuariosInfo']) > 0) {
                foreach($request['sendMail']['idUsuariosInfo'] as $idUserInfo) {
                    $usuarios[] = $idUserInfo;
                }
            }

            if(count($userExternos) > 0) {
                $modelClientes = Clientes::find()->where(['IN', 'idCliente', $userExternos])->all();
                foreach($modelClientes as $modelCliente) {
                    $correoElectronicoCliente[] = $modelCliente->correoElectronicoCliente;
                }
            }

            if(count($usuarios) > 0) {
                $modelUsers = User::find()->where(['IN', 'id', $usuarios])->all();
                foreach($modelUsers as $modelUser) {
                    $correoElectronicoCliente[] = $modelUser->email;
                }
            }

            foreach ($request['dataSend'] as $rowRadicado) {

                // Este campo trae el id del registro que se debe afectar o consultar
                $id = $rowRadicado['id'];

                // Número de radicado a enviar en el correo
                $numeroRadicadoParaEnvio = "";
                $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $id]);
                // Se toma el del padre inicialmente
                $numeroRadicadoParaEnvio = HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado, $modelRadicado->isRadicado);
                // Valida si tiene radicados hijos para reemplazar el numero de radicado del padre
                // por el radicado hijo
                $modelRadicadoHijo = RadiRadicados::find()->where(['idRadiRadicadoPadre' => $modelRadicado->idRadiRadicado])->one();
                if($modelRadicadoHijo) {
                    if($modelRadicadoHijo->isRadicado === 1) {
                        $numeroRadicadoParaEnvio = HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadicadoHijo->numeroRadiRadicado, $modelRadicadoHijo->idCgTipoRadicado, $modelRadicadoHijo->isRadicado);
                    }
                }

                // # Si llega la opción de notificar al cliente hacerlo de lo contrario solo al que se seleccione
                // if($request['sendMail']['userClient'] == true){
                    if ($modelRadicado->autorizacionRadiRadicados == 0 || $modelRadicado->autorizacionRadiRadicados == false) {
                        //consultar direccion del remitente del radicado
                        $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $modelRadicado->idRadiRadicado]);    

                        $direccion = '';
                        foreach($remitentes as $dataRemitente){
        
                            if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                                $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                                $direccion = $modelRemitente->direccionCliente;
            
                            } else {
                                $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                                $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelUser->idGdTrdDependencia]);
                                $modelRegional = CgRegionales::findOne(['idCgRegional' => $modelDependencia->idCgRegional]);
                                $modelNivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $modelRegional->idNivelGeografico3]);
                                $direccion = $modelNivelGeografico3->nomNivelGeografico3;
                            }
                        }
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [ Yii::t('app','noAutorizacionEmail', ['DirCorrespondencia' => $direccion ])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                // }

                # Validar que esté radicado y no sea un consecutivo temporal
                if ($modelRadicado->isRadicado == 0 || $modelRadicado->isRadicado == false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [ Yii::t('app','messageRadiTmpError', ['numFile' => $modelRadicado->numeroRadiRadicado])]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Si llega la opción de notificar al cliente hacerlo de lo contrario solo al que se seleccione
                // if($request['sendMail']['userClient'] == true){
                
                    # Se realiza la consulta en la tabla remitentes con el id del radicado para obtener la información de un usuario
                    # o de un cliente y asi mostrar la información
                    $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $modelRadicado->idRadiRadicado]);

                    foreach($remitentes as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        
                        $modelRemitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                        // Si el remitente es igual al usuario anonimo quiere decir que se debe consultar el detalle de al pqrs
                        if($dataRemitente->idRadiPersona == Yii::$app->params['idRemitenteAnonimo']){
                            $radiDetallePqrAnonimo = RadiDetallePqrsAnonimo::findOne(['idRadiRadicado' => $modelRadicado->idRadiRadicado]);
                            $modelRemitente->correoElectronicoCliente = $radiDetallePqrAnonimo->emailRadiDetallePqrsAnonimo;
                        }

                        $correoElectronicoCliente[] = $modelRemitente->correoElectronicoCliente;
    
                    } else {
                        $modelRemitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                        $correoElectronicoCliente[] = $modelRemitente->email;
                    }

                }
                
                $emailCliente = implode (", ", $correoElectronicoCliente);

                // Se deben enviar todos los anexos seleccionados, mas los documentos principales que esten firmados
                $idsDocuSelected = [];
                foreach ($request['selectedRows'] as $rorDocuSelected) {
                    $idsDocuSelected[] = $rorDocuSelected['id'];
                }

                $arrayDocumentos = [];

                $modelDocumentos = RadiDocumentos::find()->select(['rutaRadiDocumento','nombreRadiDocumento'])
                    ->where(['idRadiRadicado' => $id])
                    ->andWhere(['IN', 'idRadiDocumento', $idsDocuSelected])
                    ->all();
                foreach ($modelDocumentos as $rowDocumento) {
                    $arrayDocumentos[] = $rowDocumento['rutaRadiDocumento'] . $rowDocumento['nombreRadiDocumento'];
                }

                $modelDocumentosPrincipales = RadiDocumentosPrincipales::find()->select(['rutaRadiDocumentoPrincipal'])
                    ->where(['idRadiRadicado' => $id])
                    ->andWhere(['or',
                        ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                        ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']],
                        ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente']]
                    ])
                    ->orderBy(['idradiDocumentoPrincipal' => SORT_DESC])
                ->all();
                foreach ($modelDocumentosPrincipales as $key => $rowDocumento) {

                    // Para obtener solamente el ultimo documento asociado como documento firmado en el radicado
                    if($key == 0){
                        $arrayDocumentos[] = $rowDocumento['rutaRadiDocumentoPrincipal'];
                    }
                    
                }
                
                /** Validar los documentos que existen en la bodega para evitar error 500 */
                $arrayDocumentosExist = [];
                foreach ($arrayDocumentos as $rowDocumento) {
                    if (file_exists($rowDocumento)) {
                        $arrayDocumentosExist[] = $rowDocumento;
                    }
                }

                if (!is_null($modelRadicado)) {

                    # Obtiene la cantidad actual del radicado
                    $conteo = $modelRadicado->cantidadCorreosRadicado;
                    
                    if (filter_var_array($correoElectronicoCliente, FILTER_VALIDATE_EMAIL)) {
                        // Envia la notificación de correo electronico al usuario de tramitar
                        $headMailText = Yii::t('app', 'headMailTextReplyMail');
                        $textBody = Yii::t('app', 'textBodyRespuestaRadicado') . $numeroRadicadoParaEnvio . Yii::t('app', 'textBodyRespuestaRadicado1'). Yii::$app->params['cliente'] . Yii::t('app', 'textBodyRespuestaRadicado2');
                        $bodyMail = 'radicacion-html';
                        $subject = 'subjectReplyMail';

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelRadicado->idRadiRadicado));

                        foreach($correoElectronicoCliente as $email){
                            if (Yii::$app->params['radiSendReplyEMail'] != '') {
                                $to = [$email, Yii::$app->params['radiSendReplyEMail']];
                            } else {
                                $to = $email;
                            }
                            $envioCorreo = CorreoController::envioAdjuntos($to, $headMailText, $textBody, $bodyMail, $arrayDocumentosExist, $dataBase64Params, $subject, 'buttonLinkRadicado', false);
                        }

                        if ($envioCorreo['status'] == true) {

                            # Se actualiza la cantidad de envio de correo
                            $modelRadicado->cantidadCorreosRadicado = $conteo + 1;
                            
                            if($modelRadicado->save()){

                                /***  log Auditoria ***/
                                HelperLog::logAdd(
                                    false,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    Yii::$app->params['eventosLogText']['sendMail'] . $modelRadicado->numeroRadiRadicado . ' al (los) cliente(s) con el correo registrado ' . $emailCliente,// texto para almacenar en el evento
                                    [],
                                    [$modelRadicado], //Data
                                    array() //No validar estos campos
                                );
                                /***   Fin log Auditoria   ***/

                                /***    Log de Radicados  ***/
                                HelperLog::logAddFiling(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $modelRadicado->idRadiRadicado, //Id radicado
                                    $idTransacion->idCgTransaccionRadicado,
                                    Yii::$app->params['eventosLogTextRadicado']['sendMail'] . ' ' . $emailCliente, //observación
                                    $modelRadicado,
                                    array() //No validar estos campos
                                );

                                /***  Notificacion  ***/
                                HelperNotification::addNotification(
                                    Yii::$app->user->identity->id, //Id user creador
                                    $modelRadicado->user_idTramitador, // Id user notificado
                                    Yii::t('app','messageNotification')['responseToFiling'].$modelRadicado->numeroRadiRadicado, //Notificacion
                                    Yii::$app->params['routesFront']['viewRadicado'], // url
                                    $modelRadicado->idRadiRadicado // id radicado
                                );
                                /***  Fin Notificacion  ***/

                                $dataCorrecta[] = $emailCliente;

                            } else {
                                $errors = array_merge($errors, $modelRadicado->getErrors());
                            }
                        }

                    } else {
                        $msgError[] = $emailCliente;
                    }
                }
            }

            if (count($msgError) > 0) {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $msgError,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'confimacionEnvio'),
                    'data' => [],
                    'status' => 200
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            # Cuando genera error en la actualización del modelo Radicado
            if(count($errors) > 0){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $errors,
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
     * Servicio que realiza el proceso de descartar un radicado temporal o consecutivo
     **/
    public function actionDescartarConsecutivo(){

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

                $observacion = $request['data']['observacion'];
                $arrayRadicados = [];

                # Consulta la accion de la transaccion  de descartar consecutivo y se obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'discardConsecutive']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                $transaction = Yii::$app->db->beginTransaction();

                foreach($request['ButtonSelectedData'] as $radicado){

                    $idRadicado = $radicado['id'];

                    $modelRadicado = RadiRadicados::find()
                        ->where(['idRadiRadicado' => $idRadicado])
                        ->one();

                    $idEstadoRadicadoOld = $modelRadicado->estadoRadiRadicado;

                    /** Validar si al radicado se le puede realizar el proceso de descartar */
                    if( in_array($modelRadicado->idCgTipoRadicado, [Yii::$app->params['idCgTipoRadicado']['radiEntrada'], Yii::$app->params['idCgTipoRadicado']['radiPqrs']]) ) {

                        $plantillaCorrespondencia = RadiDocumentosPrincipales::find()
                            ->where(['idRadiRadicado' => $modelRadicado->idRadiRadicado])
                            ->andwhere(['not in', 'idRadiRespuesta', [$modelRadicado->idRadiRadicado, null]])
                            ->all();

                        $haveTemporalCor = false;
                        $haveRadicadoCor = false;
                        foreach ($plantillaCorrespondencia as $row) {
                            if ((boolean) $row->idRadiRespuesta0->isRadicado) {
                                $haveRadicadoCor = true;
                            } else {
                                if ($row->idRadiRespuesta0->estadoRadiRadicado != Yii::$app->params['statusTodoText']['Inactivo']) {
                                    $haveTemporalCor = true;
                                }
                            }
                        }

                        if ($haveRadicadoCor) {

                            # Validar si tiene documentos radicados firmados
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'errorRadiHaveFirma', ['numFile' => $modelRadicado->numeroRadiRadicado])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);

                        } elseif (!$haveTemporalCor) {

                            # Validar si no tiene tiene documentos temporales
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'errorRadiWithoutDocuTmp', ['numFile' => $modelRadicado->numeroRadiRadicado])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);

                        }

                    } else {

                        if ((boolean) $modelRadicado->isRadicado == true || $modelRadicado->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Inactivo']) {
                            $transaction->rollBack();

                            if ($modelRadicado->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Inactivo']) {
                                $errors = ['error' => [Yii::t('app', 'errorRadiAlreadyIsDiscard', ['numFile' => $modelRadicado->numeroRadiRadicado])]];
                            } else {
                                $errors = ['error' => [Yii::t('app', 'errorRadiDiscardIsNotTmp', ['numFile' => $modelRadicado->numeroRadiRadicado])]];
                            }

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $errors,
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                    }
                    /** Fin Validar si al radicado se le puede realizar el proceso de descartar */

                    # Validar tipo de radicado para realizar el proceso de descartar
                    if( !in_array($modelRadicado->idCgTipoRadicado, [Yii::$app->params['idCgTipoRadicado']['radiEntrada'], Yii::$app->params['idCgTipoRadicado']['radiPqrs']]) ) {
                        /** Descartar el radicado seleccionado */
                        $descartarRadicado = self::descartarRadicado($modelRadicado, $observacion, $idTransacion);
                        if ($descartarRadicado['status'] == false) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $descartarRadicado['errors'],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        } else {
                            $arrayRadicados[] = $modelRadicado->numeroRadiRadicado;
                        }
                        /** Fin Descartar el radicado seleccionado */
                    }
                 
                    /** Descartar todos los documentos principales con radicado temporal asciados al radicado seleccionado */
                    $radiDocumentosPrincipales = RadiDocumentosPrincipales::find()
                        ->where(['idRadiRadicado' => $modelRadicado->idRadiRadicado])
                        ->andwhere(['not in', 'idRadiRespuesta', [$modelRadicado->idRadiRadicado, null]])
                        ->all();

                    foreach ($radiDocumentosPrincipales as $radiDocumentosPrincipal) {
                        $modelRadiPrincipal = $radiDocumentosPrincipal->idRadiRespuesta0;

                        if ($modelRadiPrincipal->estadoRadiRadicado != Yii::$app->params['statusTodoText']['Inactivo']) {
                            $descartarRadicado = self::descartarRadicado($modelRadiPrincipal, $observacion, $idTransacion);
                            if ($descartarRadicado['status'] == false) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $descartarRadicado['errors'],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            } else {
                                $arrayRadicados[] = $modelRadiPrincipal->numeroRadiRadicado;
                            }
                        }

                    }
                    /** Fin Descartar todos los documentos principales con radicado temporal asciados al radicado seleccionado */
                }


                if (count($arrayRadicados) > 1) {
                    $message = Yii::t('app', 'successDiscardConsecutives', ['numFile' => implode(', ', $arrayRadicados)]);
                } else {
                    $message = Yii::t('app', 'successDiscardConsecutive', ['numFile' => implode(', ', $arrayRadicados)]);
                }
// Yii::$app->response->statusCode = 500;
// return 'PASO';
                $transaction->commit();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $message,
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
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public static function descartarRadicado($modelRadicado, $observacion, $idTransacion)
    {
        $idRadicado = $modelRadicado->idRadiRadicado;
        $idEstadoRadicadoOld = $modelRadicado->estadoRadiRadicado;

        $modelRadicado->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Inactivo'];
        if (!$modelRadicado->save()) {
            return [
                'status' => false,
                'message' => 'Error al guardar radicado', // Solo para debug
                'errors' => $modelRadicado->getErrors(),
            ];
        }

        $data = 'Id radicado: '.$modelRadicado->idRadiRadicado . ', Número radicado: '.$modelRadicado->numeroRadiRadicado;

        $dataOld = $data . ', Estado anterior del consecutivo: '. Yii::$app->params['statusTodoNumber'][$idEstadoRadicadoOld];
        $dataNew = $data . ', Nuevo estado del consecutivo: '. Yii::$app->params['statusTodoNumber'][$modelRadicado->estadoRadiRadicado];

        /***    Log de Auditoria  ***/
        HelperLog::logAdd(
            true,
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->username, //username
            Yii::$app->controller->route, //Modulo
            Yii::$app->params['eventosLogText']['discardConsecutive'] . $modelRadicado->numeroRadiRadicado, //texto para almacenar en el evento
            $dataOld,
            $dataNew,
            []
        );
        /***    Fin log Auditoria   ***/

        $observacionTransaccion = Yii::$app->params['eventosLogText']['discardConsecutive'] . $modelRadicado->numeroRadiRadicado . ' con  la observación ' . $observacion;

        /***    Log de Radicados  ***/
        HelperLog::logAddFiling(
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
            $idRadicado, //Id radicado
            $idTransacion,
            $observacionTransaccion, //observación 
            $modelRadicado,
            array() //No validar estos campos
        );
        /***    Fin log Radicados   ***/

        return [
            'status' => true,
            'message' => 'Consecutivo descartado', // Solo para debug
            'errors' => [],
        ];
    }

    /**
     * Servicio que solicita la anulación de los radicados según su observación
     **/ 
    public function actionSolicitaAnulacionRadicado(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = ['idRadicado' => [13, 10, 14], 'observacionRadiRadicadoAnulado' => 'Prueba de anulación radicado',
            //  'idEstado' => 2,];
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

                $saveDataValid = true;
                $errors = [];
                $numRadiAnulados = []; // Array de los radicados anulados seleccionados
                $data = ''; // Data en string para el logAdd

                $transaction = Yii::$app->db->beginTransaction();
                
                # Consulta de rolesOperaciones para obtener los usuarios que tienen permiso en el modulo de anulación.
                $modelRolOperacion = RolesOperaciones::findOne(['nombreRolOperacion' => 'version1%radicacion%anulacion%index']);
                $modelTiposOperac = RolesTiposOperaciones::findAll(['idRolOperacion' => $modelRolOperacion->idRolOperacion]);
                
                $rolesTipos = [];
                if(!is_null($modelTiposOperac)) {
                    foreach($modelTiposOperac as $key => $infoTipos) {
                        $rolesTipos[] = $infoTipos['idRol'];                        
                    }
                }

                # Consulta para obtener los correos de los usuarios con permiso.
                $modelUser = User::find()->select(['email', 'id'])
                    ->where(['in', 'idRol', $rolesTipos ])
                    ->andWhere(['<>','id', Yii::$app->user->identity->id])
                    ->asArray()->all();

                $emails = [];
                $idsUser = []; // ids de los usuarios para la seccion de notificación
                if(!is_null($modelUser)) {
                    foreach($modelUser as $key => $infoUser) {                     
                        $emails[] = $infoUser['email'];                        
                        $idsUser[] = $infoUser['id'];                        
                    }
                }

                # Consulta la accion de la transaccion  de solicitud de anulación y se obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'annulationRequest']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                foreach($request['ButtonSelectedData'] as $radicado){

                    $modelRadicado = RadiRadicados::find()->select(['numeroRadiRadicado', 'idRadiRadicado', 'isRadicado'])
                        ->where(['idRadiRadicado' => $radicado['id']])
                    ->one();

                    /** Validar si al radicado se le puede realizar una solicitud de anulacion */
                    $radiRadicadoAnulado = RadiRadicadoAnulado::find()->select(['idRadiRadicadoAnulado'])
                        ->where(['idRadicado' => $radicado['id']])
                        ->andWhere(['or',
                           ['idEstado' => Yii::$app->params['statusAnnulationText']['SolicitudAnulacion']],
                           ['idEstado' => Yii::$app->params['statusAnnulationText']['AceptacionAnulacion']]
                        ])->limit(1)
                    ->one();

                    if ($radiRadicadoAnulado != null) {
                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorCanSolicitudAnulacion', ['NoRadicado' => $modelRadicado->numeroRadiRadicado])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Fin Validar si al radicado se le puede realizar una solicitud de anulacion */

                    /** Inicio Validar si es un radicado temporal */
                    if($modelRadicado->isRadicado == 0 || $modelRadicado->isRadicado == false) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [ Yii::t('app','messageRadiTmpError', ['numFile' => $modelRadicado->numeroRadiRadicado])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Fin Validar si es un radicado temporal */
                    
                    # Se agrega los registros de los radicados solicitados
                    $idRadicado = $radicado['id'];
                    $observacionRadiRadicadoAnulado = $request['data']['observacion'];
                    
                    $modelRadicadoAnulado = new RadiRadicadoAnulado();
                    $modelRadicadoAnulado->observacionRadiRadicadoAnulado = $observacionRadiRadicadoAnulado;
                    $modelRadicadoAnulado->idRadicado = $idRadicado;
                    $modelRadicadoAnulado->fechaRadiRadicadoAnulado = date("Y-m-d H:i:s");
                    $modelRadicadoAnulado->idResponsable = Yii::$app->user->identity->id;

                    # Se agrupa todos los radicados seleccionados
                    $numRadiAnulados[] = $modelRadicado->numeroRadiRadicado;                    
                    $idRadiAnulados[] = $modelRadicado->idRadiRadicado;                    

                    if (!$modelRadicadoAnulado->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadicadoAnulado->getErrors());                        
                        break;
                    }

                    #Consulta para obtener los datos del estado y fecha del radicado anulado
                    $anulacion = RadiRadicadoAnulado::findOne(['idRadicado' => $radicado['id']]);

                    $anulacion->estadoRadiRadicadoAnulado == yii::$app->params['statusTodoText']['Activo'] ? $estado = 'Activo' : $estado = 'Inactivo';                    
                
                    $data = 'Id radicado anulado: '.$anulacion->idRadiRadicadoAnulado;
                    $data .= ', Observación de la anulación: '.$anulacion->observacionRadiRadicadoAnulado;
                    $data .= ', Número radicado: '.$anulacion->idRadicado0->numeroRadiRadicado;
                    $data .= ', Fecha de solicitud de anulación: '.$anulacion->fechaRadiRadicadoAnulado;
                    $data .= ', Nombre responsable: '.$anulacion->idResponsable0->userDetalles->nombreUserDetalles.' '.$anulacion->idResponsable0->userDetalles->apellidoUserDetalles;
                    $data .= ', Estado de anulación: '.$anulacion->idEstado0->nombreRadiEstadoAnulacion;
                    $data .= ', Estado: '.$estado;
                    $data .= ', Fecha creación de la anulación: '.$anulacion->creacionRadiRadicadoAnulado;
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", tabla radicadoAnulado", //texto para almacenar en el evento
                        '',
                        $data, //Data
                        array('rutaActaRadiRadicadoAnulado', 'codigoActaRadiRadicadoAnulado') //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/  
                    
                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $idRadicado, //Id radicado
                        $idTransacion,
                        $observacionRadiRadicadoAnulado, //observación 
                        $modelRadicadoAnulado,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/

                    /***  Notificacion  ***/
                    foreach($idsUser as $idUser){  

                        HelperNotification::addNotification(
                            Yii::$app->user->identity->id, //Id user creador
                            $idUser, // Id user notificado
                            Yii::t('app','messageNotification')['annulationRequest'].$modelRadicadoAnulado->idRadicado0->numeroRadiRadicado, //Notificacion
                            Yii::$app->params['routesFront']['viewRadicado'], // url
                            $modelRadicadoAnulado->idRadicado // id radicado
                        );                        
                    }
                    /***  Fin Notificacion  ***/

                }

                # Información de usuario logueado
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombresLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                # Envia la notificación de correo electronico al usuario de tramitar      
                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    if ( count($numRadiAnulados) > 1) {
                        $textStart = 'textBodySolicitudAnulaciones';

                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                        $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                    } else {
                        $textStart = 'textBodySolicitudAnulacion';

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($idRadiAnulados[0]));

                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                        $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                    }
            
                    $headMailText = Yii::t('app', 'headMailTextSolicitud'); 

                    $numRadicado = implode(', ',$numRadiAnulados);  
                    $textBody  = Yii::t('app', $textStart, [
                        'NoRadicado'    => $numRadicado,
                        'Observacion'   => $observacionRadiRadicadoAnulado,
                        'Username'      => $nombresLogged,
                        'NameDependencia'   => $dependenciaLogged,
                    ]);

                    $subject = Yii::t('app', 'subjectRequest');
                    $bodyMail = 'radicacion-html';                    

                    #Correos de usuarios con permiso del modulo anulación
                    $envioCorreo = [];
                    foreach($emails as $email){
                       $envioCorreo[] = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                    }
                # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos    


                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {                    

                    $transaction->commit();   

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSolicitudAnulacion'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados #      
               

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
     *  Transaccion Devolver Radicado
     * 
     * @param array dataIdRadicados
     * @param string  observacion
     * 
     * @return array message success 
     */

    public function actionReturnFiling(){        
        
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

                $notificacion = []; 
                $observacion = $request['observacion'];
                $radicados = []; $arrayDatos = [];

                $modelTransaccion =  CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'returnFiling']);
                $idTransacion = $modelTransaccion->idCgTransaccionRadicado;

                if (count($request['ButtonSelectedData']) == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorCountDataSelected')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $arrayNewUserTramitador = []; // Array de datos de nuevos tramitadores
                $arrayNotificacions = []; // Array de notificaciones de salida

                $transaction = Yii::$app->db->beginTransaction();

                foreach($request['ButtonSelectedData'] as $key => $radicados){
                    
                    $idRadicado = (int) $radicados['id'];  
                    
                    $modelRadicado = self::findRadiRadicadosModel($idRadicado);
                    $idUser = $modelRadicado->user_idTramitador;
    
                    if($modelRadicado->user_idTramitadorOld != null || $modelRadicado->user_idTramitador != $modelRadicado->user_idCreador){
                        
                        if($modelRadicado->user_idTramitadorOld != null){
                            $changeUserIdTramitador = $modelRadicado->user_idTramitadorOld; // Tramitador anterior pasara a ser el actual
                            $changeUserIdTramitadorOld = $modelRadicado->user_idTramitador; // Tramitador nuevo pasara a ser el anterior
                        }else{
                            $changeUserIdTramitador = $modelRadicado->user_idCreador; // Tramitador anterior pasara a ser el actual
                            $changeUserIdTramitadorOld = $modelRadicado->user_idTramitador; // Tramitador nuevo pasara a ser el anterior
                        }                       

                        /** Consultar dados del nuevo tramitador */
                        if (array_key_exists($changeUserIdTramitador, $arrayNewUserTramitador)) {
                            $newUserTramitador = $arrayNewUserTramitador[$changeUserIdTramitador];
                        } else {
                            $tablaUser = User::tableName() . ' AS U';
                            $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
                            $newUserTramitador = (new \yii\db\Query())
                                ->select(['U.id','U.idGdTrdDependencia','U.email','UD.nombreUserDetalles','UD.apellidoUserDetalles'])
                                ->from($tablaUser);
                                // ->innerJoin($tablaUserDetalles, '`u`.`id` = `ud`.`idUser`')
                                $newUserTramitador = HelperQueryDb::getQuery('innerJoinAlias', $newUserTramitador, $tablaUserDetalles, ['U' => 'id', 'UD' => 'idUser']);
                                $newUserTramitador = $newUserTramitador->where(['U.id' => $changeUserIdTramitador])
                            ->one();
                            $arrayNewUserTramitador[$changeUserIdTramitador] = $newUserTramitador; // Agregando consulta de datos de usuario al array para no volver a consultar durante el bucle
                        }
                        /** Consultar dados del nuevo tramitador */

                        $modelRadicado->idTrdDepeUserTramitador = $newUserTramitador['idGdTrdDependencia']; // Asignar al modelo: Dependencia del nuevo tramitador
                        $modelRadicado->user_idTramitador = $changeUserIdTramitador; // Asignar al modelo: Nuevo tramitador
                        $modelRadicado->user_idTramitadorOld = $changeUserIdTramitadorOld; // Asignar al modelo: Tramitador anterior

                        
                        if($modelRadicado->save()){

                            $dataLogNew = '';

                            #Data para el log
                            $dataLogNew = self::dataLog($modelRadicado);

                            if ((boolean) $modelRadicado->isRadicado == true) {
                                $eventLogTextReturnFiling = Yii::$app->params['eventosLogText']['returnFiling'];
                                $eventLogTextRadicadoDevolucion = Yii::$app->params['eventosLogTextRadicado']['devolucion'];
                                $messageNotificationReturnFile = Yii::t('app','messageNotification')['returnFile'];
                            } else {
                                $eventLogTextReturnFiling = Yii::$app->params['eventosLogText']['returnFilingTmp'];
                                $eventLogTextRadicadoDevolucion = Yii::$app->params['eventosLogTextRadicado']['devolucionTmp'];
                                $messageNotificationReturnFile = Yii::t('app','messageNotification')['returnFileTmp'];
                            }

                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                true,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                $eventLogTextReturnFiling . $modelRadicado->numeroRadiRadicado . ', al usuario: ' . $newUserTramitador['nombreUserDetalles'] . ' ' . $newUserTramitador['apellidoUserDetalles'],
                                '',
                                $dataLogNew, //Data
                                array() //No validar estos campos
                            );                    
                            /***    Fin log Auditoria   ***/                    

                            $arrayDatos[$newUserTramitador['id']]['emailUsuario'] = $newUserTramitador['email'];
                            $arrayDatos[$newUserTramitador['id']]['numRadicados'][] = $modelRadicado->numeroRadiRadicado;
                            $arrayDatos[$newUserTramitador['id']]['lastIdRadiRadicado'] = $modelRadicado->idRadiRadicado;
                            $arrayDatos[$newUserTramitador['id']]['typesRadiOrTmp'][] = ((boolean) $modelRadicado->isRadicado == true) ? 'radicado' : 'temporal';

                            /***    Log de Radicados  ***/
                            $logAddFiling =  HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $modelRadicado->idRadiRadicado, //Id radicado 
                                $idTransacion,
                                $eventLogTextRadicadoDevolucion . $newUserTramitador['nombreUserDetalles'] . ' ' . $newUserTramitador['apellidoUserDetalles'] . ", con la siguiente descripción: ".$observacion, //observación 
                                $modelRadicado,
                                array() //No validar estos campos
                            );
                            /***    Fin log Radicados   ***/

                            /***  Notificacion  ***/
                            HelperNotification::addNotification(
                                Yii::$app->user->identity->id, //Id user creador
                                $newUserTramitador['id'], // Id user notificado
                                $messageNotificationReturnFile.$modelRadicado->numeroRadiRadicado, //Notificacion
                                Yii::$app->params['routesFront']['viewRadicado'], // url
                                $idRadicado // id radicado
                            );
                            /***  Fin Notificacion  ***/

                            // Se retorna la información al initial List
                            $dataResponse[] = array(
                                'id' =>  $idRadicado,
                                'idInitialList' => $radicados['idInitialList'] * 1
                            );

                            /** Radicado exitoso */
                            $arrayNotificacions['success']['numerosRadicados'][] = $modelRadicado->numeroRadiRadicado;
                            $arrayNotificacions['success']['typesRadiOrTmp'][] = ((boolean) $modelRadicado->isRadicado == true) ? 'radicado' : 'temporal';

                        }else {

                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelRadicado->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);

                        }

                    }else{
                        $arrayNotificacions['dangerTramitadorOld']['numerosRadicados'][] = $modelRadicado->numeroRadiRadicado;
                        $arrayNotificacions['dangerTramitadorOld']['typesRadiOrTmp'][] = ((boolean) $modelRadicado->isRadicado == true) ? 'radicado' : 'temporal';
                    }
                }

                $transaction->commit();
           
                // Datos del usuario logueado
                $userModel = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();
                $gdTrdDependencias = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $userModel->user->idGdTrdDependencia])->one();
                
                // Envio Notificaciones de correo
                foreach($arrayDatos as $key => $value){

                    /** Validar mensajes a traducir y enlace a la aplicación */
                    if (count($value['numRadicados']) > 1) {

                        // Variables que se van a traducir
                        if (in_array('radicado', $value['typesRadiOrTmp']) && in_array('temporal', $value['typesRadiOrTmp'])) {
                            $headMailTextDevuelto = 'headMailTextDevueltosAll';
                            $mailEventRadicadoDevuelto = 'mailEventRadicadoDevueltosAll';
                            $setSubject = 'returnFilingsAll';
                        } elseif (in_array('radicado', $value['typesRadiOrTmp'])) {
                            $headMailTextDevuelto = 'headMailTextDevueltos';
                            $mailEventRadicadoDevuelto = 'mailEventRadicadoDevueltos';
                            $setSubject = 'returnFilings';
                        } else {
                            $headMailTextDevuelto = 'headMailTextDevueltosTmp';
                            $mailEventRadicadoDevuelto = 'mailEventRadicadoDevueltosTmp';
                            $setSubject = 'returnFilingsTmp';
                        }

                        $nameButtonLink = 'Ir al sistema'; // Variable que será traducida
                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                    } else {

                        // Variables que se van a traducir
                        if (in_array('radicado', $value['typesRadiOrTmp'])) {
                            $headMailTextDevuelto = 'headMailTextDevuelto';
                            $mailEventRadicadoDevuelto = 'mailEventRadicadoDevuelto';
                            $setSubject = 'returnFiling';
                            $nameButtonLink = 'buttonLinkRadicado';
                        } else {
                            $headMailTextDevuelto = 'headMailTextDevueltoTmp';
                            $mailEventRadicadoDevuelto = 'mailEventRadicadoDevueltoTmp';
                            $setSubject = 'returnFilingTmp';
                            $nameButtonLink = 'buttonLinkRadicadoTmp';
                        }

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($value['lastIdRadiRadicado']));
                        $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                    }
                    /** Fin Validar mensajes a traducir y enlace a la aplicación */
                    
                    $implodeRadicados = implode(", ", $value['numRadicados']);

                    // Envia la notificación de correo electronico al usuario de tramitar
                    $headMailText = Yii::t('app', $headMailTextDevuelto, [
                        'numRadi' =>  $implodeRadicados,
                    ]);

                    $textBody  = Yii::t('app', $mailEventRadicadoDevuelto, [
                        'numRadi' => $implodeRadicados,
                        'asunto'  => $observacion,
                        'usuario' => $userModel['nombreUserDetalles'].' '.$userModel['apellidoUserDetalles'],
                        'depe'    => $gdTrdDependencias['nombreGdTrdDependencia'] ??  Yii::t('app', 'depNotFound')
                    ]);

                    $bodyMail = 'radicacion-html';
                    $subject = Yii::t('app', $setSubject);

                    $envioCorreo = CorreoController::sendEmail($value['emailUsuario'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                }

                /** Iteracion de mensajes de notificación para la agrupación de los mismos */
                foreach ($arrayNotificacions as $key => $value) {
                    if ($key == 'success') {
                        $numerosRadicados = $value['numerosRadicados'];
                        if (count($numerosRadicados) > 1) {

                            if (in_array('radicado', $value['typesRadiOrTmp']) && in_array('temporal', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingSuccessNotyTrueGroupAll';
                            } elseif (in_array('radicado', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingSuccessNotyTrueGroup';
                            } else {
                                $msgI18n = 'returnFilingSuccessNotyTrueGroupTmp';
                            }

                            $notificacion[] =  [
                                'message' => Yii::t('app', $msgI18n, [
                                    'numRadi' => implode(', ', $numerosRadicados)
                                ]),
                                'type' => 'success'
                            ];
                        } else {

                            if (in_array('radicado', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingSuccessNotyTrue';
                            } else {
                                $msgI18n = 'returnFilingSuccessNotyTrueTmp';
                            }

                            $notificacion[] =  [
                                'message' => Yii::t('app', $msgI18n, [
                                    'numRadi' => $numerosRadicados[0]
                                ]),
                                'type' => 'success'
                            ];
                        }
                    } elseif ($key == 'dangerTramitadorOld') {
                        $numerosRadicados = $value['numerosRadicados'];
                        if (count($numerosRadicados) > 1) {

                            if (in_array('radicado', $value['typesRadiOrTmp']) && in_array('temporal', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingsNotFondsAll';
                            } elseif (in_array('radicado', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingsNotFonds';
                            } else {
                                $msgI18n = 'returnFilingsNotFondsTmp';
                            }

                            $notificacion[] =  [
                                'message' => Yii::t('app', $msgI18n, [
                                    'numRadi' => implode(', ', $numerosRadicados)
                                ]),
                                'type' => 'danger'
                            ];
                        } else {

                            if (in_array('radicado', $value['typesRadiOrTmp'])) {
                                $msgI18n = 'returnFilingNotFond';
                            } else {
                                $msgI18n = 'returnFilingNotFondTmp';
                            }

                            $notificacion[] =  [
                                'message' => Yii::t('app', $msgI18n, [
                                    'numRadi' => $numerosRadicados[0]
                                ]),
                                'type' => 'danger'
                            ];
                        }
                    }
                }
                /** Fin Iteracion de mensajes de notificación para la agrupación de los mismos */
            
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'OK',
                    'notificacion' => $notificacion,
                    'data' => $dataResponse ?? false,
                    'logRadicados' => $logAddFiling ?? false,
                    'arrayDatos' => $arrayDatos,
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
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Servicio que solicita el visto bueno de los radicados con su observación 
     * y modificación del usuario tramitador.
     **/ 
    public function actionSolicitaVobo(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = ['idRadicado' => [13, 10, 14], 'observacionRadiRadicadoAnulado' => 'Prueba de solicitud VoBo',
            //  'idUser' => 2,];
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

                $saveDataValid = true;
                $errors = [];
                $dataResponse = [];
                $idRadicados = []; //Array de los radicados seleccionados

                $transaction = Yii::$app->db->beginTransaction();                

                # Consulta la accion de la transaccion  de solicitud de VoBo y se obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'voboRequest']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Id del usuario seleccionado
                $idUserTramitador = $request['data']['idUsuarioTramitador'];

                $tablaUser = User::tableName() . ' AS U';
                $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
                $newUsuarioTramitador = (new \yii\db\Query())
                    ->select(['UD.nombreUserDetalles', 'UD.apellidoUserDetalles', 'U.idGdTrdDependencia'])
                    ->from($tablaUser);
                    //->innerJoin($tablaUserDetalles, '`u`.`id` = `ud`.`idUser`')
                    $newUsuarioTramitador = HelperQueryDb::getQuery('innerJoinAlias', $newUsuarioTramitador, $tablaUserDetalles, ['U' => 'id', 'UD' => 'idUser']);
                    $newUsuarioTramitador = $newUsuarioTramitador->where(['U.id' => $idUserTramitador])
                ->one();
                
                foreach($request['ButtonSelectedData'] as $radicado){

                    $idRadicado = $radicado['id'];
                    $observacionRadicado = $request['data']['observacion'];                    

                    $modelRadicado = $this->findRadiRadicadosModel($idRadicado);

                    # Asignar tramitador actual como tramitador anterior del radicado, antes de guardar el registro del usuario seleccionado desde front
                    $modelRadicado->user_idTramitadorOld = $modelRadicado->user_idTramitador;

                    # Se actualiza el usuario tramitador en radiRadicados
                    $modelRadicado->user_idTramitador = $idUserTramitador;

                    # Se actualiza la dependencia del nuevo tramitador
                    $modelRadicado->idTrdDepeUserTramitador = $newUsuarioTramitador['idGdTrdDependencia'];

                    # Se agrupa todos los radicados seleccionados
                    $idRadicados[] = $modelRadicado->idRadiRadicado;

                    // Se retorna la información al initial List
                    $dataResponse[] = array(
                        'id' => $idRadicado,
                        'idInitialList' => $radicado['idInitialList'] * 1
                    );                    

                    if (!$modelRadicado->save()) {        
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadicado->getErrors());                        
                        break;
                    }

                    if ((boolean) $modelRadicado->isRadicado == true) {
                        $eventLogTextVoboRequest = Yii::$app->params['eventosLogText']['voboRequest'];
                        $msgRadiOrTmp = 'Número radicado';
                        $msgNotificationVoboRequest = Yii::t('app','messageNotification')['voboRequest'];
                    } else {
                        $eventLogTextVoboRequest = Yii::$app->params['eventosLogText']['voboRequestTmp'];
                        $msgRadiOrTmp = 'Número consecutivo temporal';
                        $msgNotificationVoboRequest = Yii::t('app','messageNotification')['voboRequestTmp'];
                    }

                    $dataLog = '';
                    $radiInfo = new RadiRadicados();
                    $labelModel = $radiInfo->attributeLabels();
                    foreach ($modelRadicado as $key => $value) {
                        switch ($key) {
                            case 'user_idTramitador':
                                $dataLog .= 'Nuevo usuario tramitador: '.$newUsuarioTramitador['nombreUserDetalles'].' '.$newUsuarioTramitador['apellidoUserDetalles']. ', ';
                            break;
                            case 'idCgTipoRadicado':
                                $dataLog .= 'Tipo radicado: '.$modelRadicado->cgTipoRadicado->nombreCgTipoRadicado.', ';
                            break;
                            case 'estadoRadiRadicado':
                                $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                            break;
                            case 'numeroRadiRadicado':
                                $dataLog .= $msgRadiOrTmp.': '.$value.', ';
                            break;
                            case 'isRadicado':
                                $value = ((boolean) $value == true) ? 'Sí' : 'No';
                                $dataLog .= $msgRadiOrTmp.': '.$value.', ';
                            break;
                            case 'descripcionAnexoRadiRadicado':
                            case 'idTrdTipoDocumental':
                            case 'user_idCreador':
                            case 'idTrdDepeUserTramitador':                            
                            case 'PrioridadRadiRadicados':
                            case 'idCgMedioRecepcion':
                            case 'idTrdDepeUserCreador':
                            case 'cantidadCorreosRadicado':                            
                            case 'foliosRadiRadicado': 
                            case 'firmaDigital':                           
                            break;                            
                            default:
                                $dataLog .= $labelModel[$key].': '.$value.', ';
                            break;
                        }
                    }

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        $eventLogTextVoboRequest . $modelRadicado->numeroRadiRadicado . " con la observación: ".$observacionRadicado, //texto para almacenar en el evento
                        '',
                        $dataLog, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $modelRadicado->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['voboRequest'] . $newUsuarioTramitador['nombreUserDetalles'].' '.$newUsuarioTramitador['apellidoUserDetalles'] . ' con la observación: '.$observacionRadicado,
                        $modelRadicado,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $modelRadicado->user_idTramitador, // Id user notificado
                        $msgNotificationVoboRequest.$modelRadicado->numeroRadiRadicado, //Notificacion
                        Yii::$app->params['routesFront']['viewRadicado'], // url
                        $modelRadicado->idRadiRadicado // id radicado
                    );
                    /***  Fin Notificacion  ***/
                }

                # Información de usuario logueado
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombresLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                # Correo de los tramitadores seleccionados en el form
                $emailTramitador = User::findOne(['id' => $idUserTramitador]);

                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        
                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado', 'RD.numeroRadiRadicado','RD.user_idTramitador', 'RD.asuntoRadiRadicado', 'RD.isRadicado'])
                    ->from($tablaRadicado)
                    ->where(['RD.idRadiRadicado' => $idRadicados])
                ->all();

                $arrayDatos = [];
                if (!is_null($modelRadicado)){
                    
                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado){
                    
                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['asunto'][] = $infoRadicado['asuntoRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';
                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['asunto'][] = $infoRadicado['asuntoRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';
                        }
                    }
                }

                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {
                        
                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if ( count($radicadosUsuario['radicados']) > 1) {

                            if (in_array('radicado', $radicadosUsuario['typesRadiOrTmp']) && in_array('temporal', $radicadosUsuario['typesRadiOrTmp'])) {
                                $headMailText = Yii::t('app', 'headMailTextSolicitudVoBosAll');
                                $textStart = 'textBodySolicitudVoBosAll';
                            } elseif (in_array('radicado', $radicadosUsuario['typesRadiOrTmp'])) {
                                $headMailText = Yii::t('app', 'headMailTextSolicitudVoBos');
                                $textStart = 'textBodySolicitudVoBos';
                            } else {
                                $headMailText = Yii::t('app', 'headMailTextSolicitudVoBosTmp');
                                $textStart = 'textBodySolicitudVoBosTmp';
                            }

                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida
                        } else {

                            if (in_array('radicado', $radicadosUsuario['typesRadiOrTmp'])) {
                                $headMailText = Yii::t('app', 'headMailTextSolicitudVoBo') . ' ' . $radicadosUsuario['radicados'][0];
                                $textStart = 'textBodySolicitudVoBo';
                                $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                            } else {
                                $headMailText = Yii::t('app', 'headMailTextSolicitudVoBoTmp') . ' ' . $radicadosUsuario['radicados'][0];
                                $textStart = 'textBodySolicitudVoBoTmp';
                                $nameButtonLink = 'buttonLinkRadicadoTmp'; // Variable que será traducida
                            }

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['lastIdRadiRadicado']));
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                        }
                
                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                        $asunto = implode(', ',$radicadosUsuario['asunto']);
                        
                        $textBody  = Yii::t('app', $textStart, [
                            'NoRadicado'    => $numRadicado,
                            'Asunto'        => $asunto,
                            'Username'      => $nombresLogged,
                            'NameDependencia'   => $dependenciaLogged,
                            'Observacion'   => $observacionRadicado,
                        ]);

                        $subject = Yii::t('app', 'subjectVoBoRequest');
                        $bodyMail = 'radicacion-html';                    
                                                
                        # Envia la notificación de correo electronico al usuario de tramitar
                        $envioCorreo = CorreoController::sendEmail($emailTramitador->email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                    } 
                    # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos    
                }

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {  

                    $transaction->commit();   

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => $dataResponse,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados #        
               

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
     * Servicio que aprueba el visto bueno de los radicados, 
     * cuando el usuario es Jefe.
     **/ 
    public function actionVobo(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            // $response = ['idRadicado' => [13, 10, 14],];
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

                $idRadicados = []; // Array de los radicados 
                $saveDataValid = true;
                $errors = [];

                # Información de usuario logueado
                $modelUserDetallesLogged = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                $nombresLogged = $modelUserDetallesLogged['nombreUserDetalles'] . ' ' . $modelUserDetallesLogged['apellidoUserDetalles'];
                $dependenciaLogged = GdTrdDependencias::find()->select(['nombreGdTrdDependencia'])->where(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])->one();
                $dependenciaLogged = $dependenciaLogged->nombreGdTrdDependencia;

                $transaction = Yii::$app->db->beginTransaction();

                # Consulta la accion de la transaccion de VoBo y se obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'vobo']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;
                
                # Se valida que el usuario logueado sea de tipo JEFE
                if(Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']){
                 
                    foreach($request['ButtonSelectedData'] as $radicado){

                        $idRadicado = $radicado['id'];

                        # Se consulta la información del radicado seleccionado
                        $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $idRadicado]);

                        # Se agrupa todos los radicados seleccionados
                        $idRadicados[] = $radicado['id'];

                        if (!$modelRadicado->save()) {
                            // Valida false ya que no se guarda
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelRadicado->getErrors());
                            break;
                        }

                        if ((boolean) $modelRadicado->isRadicado == true) {
                            $eventLogTextVobo = Yii::$app->params['eventosLogText']['vobo'];
                            $messageNotification = Yii::t('app','messageNotification')['vobo'];
                        } else {
                            $eventLogTextVobo = Yii::$app->params['eventosLogText']['voboTmp'];
                            $messageNotification = Yii::t('app','messageNotification')['voboTmp'];
                        }

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            $eventLogTextVobo . $modelRadicado->numeroRadiRadicado . ' por el usuario ' . $nombresLogged . ' de la dependencia ' . $dependenciaLogged, //texto para almacenar en el evento
                            [],
                            [$modelRadicado], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/   
                        
                        /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $modelRadicado->idRadiRadicado, //Id radicado
                            $idTransacion,
                            Yii::$app->params['eventosLogTextRadicado']['vobo'], //observación
                            $modelRadicado,
                            array() //No validar estos campos
                        );
                        /***    Fin log Radicados   ***/


                        /***  Notificacion  ***/
                        HelperNotification::addNotification(
                            Yii::$app->user->identity->id, //Id user creador
                            $modelRadicado->user_idTramitador, // Id user notificado
                            $messageNotification.$modelRadicado->numeroRadiRadicado, //Notificacion
                            Yii::$app->params['routesFront']['viewRadicado'], // url
                            $modelRadicado->idRadiRadicado // id radicado
                        );
                        /***  Fin Notificacion  ***/
                    }  


                    # Consulta de datos de los radicados
                    $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                    $tablaUser = User::tableName() . ' AS US';
            
                    $modelRadicado = (new \yii\db\Query())
                        ->select(['RD.idRadiRadicado','RD.numeroRadiRadicado','RD.user_idTramitador', 'RD.asuntoRadiRadicado', 'US.email', 'RD.isRadicado'])
                        ->from($tablaRadicado);
                        // ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                        $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                        $modelRadicado = $modelRadicado->where(['RD.idRadiRadicado' => $idRadicados])
                    ->all();
                

                    $arrayDatos = [];
                    if (!is_null($modelRadicado)) {

                        # Iteración de la información agrupada del radicado
                        foreach($modelRadicado as $infoRadicado){                            

                            if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['asunto'][] = $infoRadicado['asuntoRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';

                            } else {                               
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['asunto'][] = $infoRadicado['asuntoRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['lastIdRadiRadicado'] = $infoRadicado['idRadiRadicado'];
                                $arrayDatos[$infoRadicado["user_idTramitador"]]['typesRadiOrTmp'][] = ((boolean) $infoRadicado['isRadicado'] == true) ? 'radicado' : 'temporal';
                            }
                        }
                    }
         
                    if (is_array($arrayDatos)){

                        # Iteración para el envio del correo, por usuario con sus radicados respectivos
                        foreach($arrayDatos as $radicadosUsuario) {

                            # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                            if ( count($radicadosUsuario['radicados']) > 1) {

                                if (in_array('radicado', $radicadosUsuario['typesRadiOrTmp']) && in_array('temporal', $radicadosUsuario['typesRadiOrTmp'])) {
                                    $headText = Yii::t('app', 'headMailTextVoBosAll');
                                    $textStart = 'textBodyVoBosAll';
                                } elseif (in_array('radicado', $radicadosUsuario['typesRadiOrTmp'])) {
                                    $headText = Yii::t('app', 'headMailTextVoBos');
                                    $textStart = 'textBodyVoBos';
                                } else {
                                    $headText = Yii::t('app', 'headMailTextVoBosTmp');
                                    $textStart = 'textBodyVoBosTmp';
                                }

                                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                                $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                            } else {

                                if (in_array('radicado', $radicadosUsuario['typesRadiOrTmp'])) {
                                    $headText = Yii::t('app', 'headMailTextVoBo');
                                    $textStart = 'textBodyVoBo';
                                    $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                                } else {
                                    $headText = Yii::t('app', 'headMailTextVoBoTmp');
                                    $textStart = 'textBodyVoBoTmp';
                                    $nameButtonLink = 'buttonLinkRadicadoTmp'; // Variable que será traducida
                                }

                                $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['lastIdRadiRadicado']));
                                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                            }  

                            $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                            $asunto = implode(', ',$radicadosUsuario['asunto']);

                            $headMailText = Yii::t('app', $headText);  

                            $textBody  = Yii::t('app', $textStart, [
                                'NoRadicado'        => $numRadicado,
                                'Asunto'            => $asunto,
                                'Username'          => $nombresLogged,
                                'NameDependencia'   => $dependenciaLogged,                     
                            ]);

                            $subject = Yii::t('app', 'subjectVoBo');
                            $bodyMail = 'radicacion-html';                    

                            # Envia la notificación de correo electronico al usuario de tramitar
                            $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);

                        } 
                        # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos 
                    }

                    # Evaluar respuesta de datos guardados #
                    if ($saveDataValid == true) {                   
                        
                        $transaction->commit();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','successSaveVobo'),
                            'data' => [],
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {

                        $transaction->rollBack();
    
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $modelRadicado->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    # Fin Evaluar respuesta de datos guardados # 


                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'accesoDenegado'),
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorAccessDenied'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                #Fin validacion de usuario Jefe

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
    
    /* Servicio para finalizar el radicado */
    public function actionFinalizeFiling(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

                /** Continuar solo si existe usuario dsalida configurado en el sistema */
                $useDeSalida = User::find()
                    ->select(['id','idGdTrdDependencia'])
                    ->where(['username' => Yii::$app->params['userNameDeSalida']])
                ->one();

                if ($useDeSalida != null) {

                    $observacion = $request['data']['observacion'];
                    $radicadosArray = [];
                    $dataResponse = []; // Data para retornar al frontend
                    $radicadosFinalizados = []; // Radicados a enviar al frontend como mensaje

                    $transaction = Yii::$app->db->beginTransaction();

                    // Consulta de transaccion
                    $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'finalizeFiling']);

                    # Se valida que haya encuesta activa
                    $modelEncuesta = CgEncuestas::findOne(['estadoCgEncuesta' => Yii::$app->params['statusTodoText']['Activo']]);

                    $radicadosFinalizadosHijos = array();

                    $transaction->commit();
                    
                    foreach($request['ButtonSelectedData'] as $radicado){

                        //Obtener radicado
                        $idRadicado = $radicado['id'];
                        $modelRadicado = self::findRadiRadicadosModel($idRadicado);
                        $modelUserDetalle = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();

                        //Cambiar estado
                        $modelRadicado->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Finalizado'];
                        
                        // tramitador OLD
                        $modelRadicado->user_idTramitadorOld = $modelRadicado->user_idTramitador;

                        // Nuevo tramitador: usuario dsalida
                        $modelRadicado->user_idTramitador = $useDeSalida->id;
                        $modelRadicado->idTrdDepeUserTramitador = $useDeSalida->idGdTrdDependencia;

                        if($modelRadicado->save()){

                            // Consulta los radicados hijos para finalizarlos tambien
                            $radicadosHijos = RadiRadicados::findAll(['idRadiRadicadoPadre' => $modelRadicado->idRadiRadicado]);                            
                            $radicadosFinalizadosHijos[] = $radicadosHijos;

                            // Agrega los números de radicados en un array
                            $radicadosFinalizados[] = $modelRadicado->numeroRadiRadicado;                                               

                            $emailSender = null;
                            $idSender = null;
                            

                            # Si el radicado es ENTRADA y PQRS, se valida si tiene autorización de envio de correo.
                            # Si es otro tipo de radicado, por defecto el campo autorizacionRadiRadicados = '0' o 'NO'
                            if( $modelRadicado->autorizacionRadiRadicados == Yii::$app->params['autorizacionRadiRadicado']['text']['correo'] )
                            {
                                if ($modelRadicado->idCgTipoRadicado == Yii::$app->params['CgTiposRadicadosText']['Entrada'] || $modelRadicado->idCgTipoRadicado == Yii::$app->params['CgTiposRadicadosText']['Pqrsd']) {
                                    $envioNotificacionCiudadano = true;
                                } else {
                                    $envioNotificacionCiudadano = false;
                                }
                            } else {
                                $envioNotificacionCiudadano = false;
                            }

                            # Si tiene autorización se notifica al remitente ya sea un cliente o funcionario.
                            if($envioNotificacionCiudadano) {

                                $idSender = $modelRadicado->radiRemitentes->idRadiPersona;
                                $idTypePerson = $modelRadicado->radiRemitentes->idTipoPersona;

                                if($idTypePerson != Yii::$app->params['tipoPersonaText']['funcionario'] ){

                                    $modelCliente = Clientes::find()
                                        ->select(['correoElectronicoCliente'])
                                        ->where(['idCliente' => $idSender])
                                    ->one();                                

                                    $emailSender = $modelCliente->correoElectronicoCliente;

                                } else {

                                    $modelUsers = User::find()
                                        ->select(['email'])
                                        ->where(['id' => $idSender])
                                    ->one();

                                    $emailSender = $modelUsers->email;
                                }
                            }

                            $radicadosArray[$modelRadicado->userIdTramitador->email][] = [
                                'id' => $modelRadicado->idRadiRadicado,
                                'numeroRadicado' => $modelRadicado->numeroRadiRadicado,
                                'usuario' => $modelUserDetalle->nombreUserDetalles . ' ' . $modelUserDetalle->apellidoUserDetalles,
                                'dependenciaUsuario' => $modelRadicado->userIdTramitador->gdTrdDependencia->nombreGdTrdDependencia,
                                'envioNotificacionCiudadano' => $envioNotificacionCiudadano,
                                'mailCliente' => $emailSender,
                                'idCliente' => $idSender
                            ];

                            /***    Log de Radicados  ***/
                            HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $modelRadicado->idRadiRadicado, //Id radicado
                                $idTransacion->idCgTransaccionRadicado,
                                $observacion, //observación 
                                $modelRadicado,
                                array() //No validar estos campos
                            );
                            /***    Fin log Radicados   ***/  
                
                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                false, //type
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['FinalizeFile'] . " N°. ".$modelRadicado->numeroRadiRadicado." por el motivo de: ".$observacion.", con el estado ". Yii::$app->params['statusTodoNumber'][$modelRadicado->estadoRadiRadicado].", en la tabla radiRadicados", //texto para almacenar en el evento
                                [],
                                [], //Data
                                array() //No validar estos campos
                            );                  
                            /***    Fin log Auditoria   ***/

                            /***  Notificacion  ***/
                            HelperNotification::addNotification(
                                Yii::$app->user->identity->id, //Id user creador
                                $modelRadicado->user_idTramitador, // Id user notificado
                                Yii::t('app','messageNotification')['finishFile'].$modelRadicado->numeroRadiRadicado, //Notificacion
                                Yii::$app->params['routesFront']['viewRadicado'], // url
                                $modelRadicado->idRadiRadicado // id radicado
                            );
                            /***  Fin Notificacion  ***/
                           
                        }

                        // Se retorna la información al initial List "Data actualizada"
                        $dataResponse[] = array(
                            'id' => $idRadicado,
                            'statusText' => $idTransacion->titleCgTransaccionRadicado . ' - ' .  Yii::t('app', 'statusTodoNumber')[$modelRadicado->estadoRadiRadicado],               
                            'status' => $modelRadicado->estadoRadiRadicado,
                            'idInitialList' => $radicado['idInitialList'] * 1
                        );
                    }

                    // Va a recorre cada uno de los radicados hijos para cambiar el estado a finalizaco
                    if($radicadosFinalizadosHijos[0]){

                        foreach($radicadosFinalizadosHijos[0] as $radicadoHijo){
                            //Cambiar estado
                            $radicadoHijo->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Finalizado'];
                            $radicadoHijo->user_idTramitadorOld = $modelRadicado->user_idTramitador;
    
                            // Nuevo tramitador: usuario dsalida
                            $radicadoHijo->user_idTramitador = $useDeSalida->id;
                            $radicadoHijo->idTrdDepeUserTramitador = $useDeSalida->idGdTrdDependencia;
                            $radicadoHijo->save();

                            /***    Log de Radicados  ***/
                            HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $radicadoHijo->idRadiRadicado, //Id radicado
                                $idTransacion->idCgTransaccionRadicado,
                                $observacion, //observación 
                                $radicadoHijo,
                                array() //No validar estos campos
                            );
                            /***    Fin log Radicados   ***/  
                        }
                    }
                    
                    //Datos de usuario logueado
                    $userLogeadoModel = self::findUserModel(Yii::$app->user->identity->id);
                    $userDetallesLogeadoModel = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();  
                     
                    $arrayMensaje = [];

                    /** Enviar de notificaciones por correo */
                    foreach ($radicadosArray as $emailTramitante => $arrayRadicados) {
                       
                        # Envio correo masivo tanto al finalizar como a los informados.
                        if (count($arrayRadicados) > 1) {
                            
                            $asunto = 'subjectMailNotificacionFinalizacionArray';
                            $headMailText = Yii::t('app', 'headMailNotificacionFinalizacionArray');

                            $numerosRadicados = '';
                            foreach ($arrayRadicados as $value) {

                                $numerosRadicados .= $value['numeroRadicado'] . ', ';

                                $headMailTextInfo = Yii::t('app', 'headMailNotificacionFinalizacion', [
                                    'numeroRadicado' => $value['numeroRadicado']
                                ]);

                                $textBodyInfo = Yii::t('app', 'textBodyNotificacionFinalizacion', [
                                    'numeroRadicado' => $value['numeroRadicado'],
                                    'observacion' => $observacion,
                                    'usuario' => $userDetallesLogeadoModel->nombreUserDetalles . ' ' . $userDetallesLogeadoModel->apellidoUserDetalles,
                                    'dependenciaUsuario' => $userLogeadoModel->gdTrdDependencia->nombreGdTrdDependencia
                                ]); 

                                //Envio copia de notificaciones a usuarios informados    
                                $informadosNotificacion = InformadosController::enviarNotificacionInformado($headMailTextInfo, $textBodyInfo, $value['id']);

                                //Envio copia de notificaciones a clientes informados    
                                $clientInformed = InformadosController::sendClientInformed($headMailTextInfo, $textBodyInfo, $value['id']);
                            }

                            $numerosRadicados = substr($numerosRadicados, 0 , -2);
                            
                            $textBody = Yii::t('app', 'textBodyNotificacionFinalizacionArray', [
                                'numeroRadicado' => $numerosRadicados,
                                'usuario' => $userDetallesLogeadoModel->nombreUserDetalles . ' ' . $userDetallesLogeadoModel->apellidoUserDetalles,
                                'dependenciaUsuario' => $userLogeadoModel->gdTrdDependencia->nombreGdTrdDependencia
                            ]);

                            //Envio de notificacion a tramitador                    
                            $bodyMail = 'radicacion-html';        
                            $tramitenteNotificacion = CorreoController::sendEmail($emailTramitante, $headMailText, $textBody, $bodyMail, [], null, $asunto, 'Ir al sistema');

                        } else { // Envio de correo cuando es un solo radicado finalizado y a los informados.

                            $asunto = 'subjectMailNotificacionFinalizacion';
                            $headMailText = Yii::t('app', 'headMailNotificacionFinalizacion', [
                                'numeroRadicado' => $arrayRadicados[0]['numeroRadicado']
                            ]);
                            $textBody = Yii::t('app', 'textBodyNotificacionFinalizacion', [
                                'numeroRadicado' => $arrayRadicados[0]['numeroRadicado'],
                                'observacion' => $observacion,
                                'usuario' => $userDetallesLogeadoModel->nombreUserDetalles . ' ' . $userDetallesLogeadoModel->apellidoUserDetalles,
                                'dependenciaUsuario' => $userLogeadoModel->gdTrdDependencia->nombreGdTrdDependencia
                            ]);   

                            //Envio de notificacion a tramitador                    
                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($arrayRadicados[0]['id']));
                            $bodyMail = 'radicacion-html'; 

                            $tramitenteNotificacion = CorreoController::radicacion($emailTramitante, $headMailText, $textBody, $bodyMail, $dataBase64Params, $asunto);

                            //Envio copia de notificaciones a usuarios informados    
                            $informadosNotificacion = InformadosController::enviarNotificacionInformado($headMailText, $textBody, $arrayRadicados[0]['id']); 

                            //Envio copia de notificaciones a clientes informados    
                            $clientInformed = InformadosController::sendClientInformed($headMailText, $textBody, $arrayRadicados[0]['id']); 
                        }

                        //Envio de notificación al ciudadano para tipo de radicado PQRSD
                        foreach($arrayRadicados as $value){
                            if($value['envioNotificacionCiudadano']){

                                # Se valida que haya una encuesta activa, de lo contrario no enviará ninguna notificación
                                if(!is_null($modelEncuesta)){

                                    $modelClienteEncuesta = new ClienteEncuestas();
                                    $modelClienteEncuesta->email = $value['mailCliente'];
                                    $modelClienteEncuesta->idCgEncuesta = $modelEncuesta->idCgEncuesta;
                                    $modelClienteEncuesta->tokenClienteEncuesta = Yii::$app->security->generateRandomString() . '_' . time();

                                    if ($modelClienteEncuesta->save()) {

                                        $mail = $value['mailCliente'];
                                        $mailSubject = Yii::t('app', 'sendCitizenSurveySubject');
                                        $textHead = Yii::t('app', 'sendCitizenSurveyHead');
                                        $textBody = Yii::t('app', 'sendCitizenSurveyBody');
                                        $bodyMail = 'radicacion-html';
                                        $buttonLink =  $buttonLink = Yii::$app->params['urlBaseApiPublic'] . 'site/encuesta?token=' . $modelClienteEncuesta->tokenClienteEncuesta ;
                                        $nameButton = 'Ir a la encuesta';

                                        $envioCorreo = CorreoController::sendEmail($mail, $textHead, $textBody, $bodyMail, [], $buttonLink, $mailSubject, $nameButton);
                                    } else {
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app', 'errorValidacion'),
                                            'data' => $modelClienteEncuesta->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }

                                }
                            }
                        }

                    }
                    /** Enviar de notificaciones por correo */

                    // Separa los números de radicado por comas
                    $numRadicado = implode(', ', $radicadosFinalizados );
                    if( count($radicadosFinalizados) > 1 ){
                        $arrayMensaje[] =  Yii::t('app','finalizeSuccessMasive').' '.$numRadicado;
                    }else{
                        $arrayMensaje[] =  Yii::t('app','finalizeSuccess').' '.$numRadicado;
                    }


                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $arrayMensaje,
                        'data' => $dataResponse,
                        'status' => 200
                    ];

                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    /** No existe usuario dsalida */
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'notUserDeSalida')]],
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

    /** 
     * Servicio para desistir del radicado PQRS, 
     * esta acción la realiza cualquier funcionario o cliente interno.
     * Y se realiza notificacion al usuario tramitador 
     **/
    public function actionWithdrawal(){

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

                $saveDataValid = true;
                $errors = [];
                $dataFiled = []; //Array de la información del radicado para las notificaciones

                $transaction = Yii::$app->db->beginTransaction();       
            
                # Usuario administrador de Atención al Ciudadano de PQRSD
                $modelGeneral = CgGeneral::find()
                    ->select(['correoNotificadorPqrsCgGeneral'])
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();   

                if(!is_null($modelGeneral)) {
                
                    if($modelGeneral->correoNotificadorPqrsCgGeneral){
                        $modelUser = User::findOne(["email" => $modelGeneral->correoNotificadorPqrsCgGeneral]);
                        $userName = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;

                    } else {
                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'validUserWithdrawal')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [ Yii::t('app', 'noEmailUserWithdrawal')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Transacción de desistimiento del radicado
                $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'withdrawal']);
                $idTransaction = $modelTransaction->idCgTransaccionRadicado;

                # Se consulta el usuario dsalida 
                $exitUser = User::findOne(['username' => Yii::$app->params['userNameDeSalida']]);

                $observacionDesistimiento = $request['observacion'];

                foreach($request['ButtonSelectedData'] as $radicado){                   

                    # Numero radicado
                    $idFile = $radicado['id'];

                    # Se consulta el remitente para enviar la notificacion
                    $modelSender = RadiRemitentes::findOne(['idRadiRadicado' => $idFile]);    

                    if(!is_null($modelSender)) {

                        if($modelSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                            $modelCustomer = Clientes::findOne(['idCliente' => $modelSender->idRadiPersona]);                    
                            $senderName = $modelCustomer->nombreCliente;
    
                        } else {
                            $modelUserSender = User::findOne(['id' => $modelSender->idRadiPersona]);                    
                            $senderName = $modelUserSender->userDetalles->nombreUserDetalles.' '.$modelUserSender->userDetalles->apellidoUserDetalles;             
                        } 
                    }
                     

                    # Se realiza consulta para validar su estado y el tipo radicado que sea PQRS
                    $modelFile = RadiRadicados::findOne(['idRadiRadicado' => $idFile]);

                    if(!is_null($modelFile)){

                        # Si cumple con la validación no continua con el proceso
                        if($modelFile->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado'] && $modelFile->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']){

                            $transaction->rollBack();

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'errorFileWithdrawal', [
                                    'numRadicado' => $modelFile->numeroRadiRadicado
                                ])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        # Actualizar estado del radicado PQRS a finalizado
                        if($modelFile->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']){

                            # Se actualizar estado | tramitadorOld | tramitador
                            $modelFile->estadoRadiRadicado   = Yii::$app->params['statusTodoText']['Finalizado'];
                            $modelFile->user_idTramitadorOld = $modelFile->user_idTramitador;
                            $modelFile->user_idTramitador    = $exitUser->id;
                            $modelFile->idTrdDepeUserTramitador    = $exitUser->idGdTrdDependencia;
                            $modelFile->observacionRadiRadicado = $modelFile->observacionRadiRadicado . ' - '. "El ciudadano: ".$senderName.", desistió de la PQRSD con el número de radicado: ".$modelFile->numeroRadiRadicado;

                            if(!$modelFile->save()){
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelFile->getErrors());
                                break;
                            }

                        } else { //Error cuando el radicado no es una PQRS

                            $transaction->rollBack();

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'errorNoFileWithdrawal')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        } 
                    }

                    # Log Radicados
                    HelperLog::logAddFiling(
                        Yii::$app->params['idUserTramitadorPqrs'],
                        Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                        $modelFile->idRadiRadicado, //Id radicado
                        $idTransaction,  // Id transaccion
                        "El ciudadano: ".$senderName.", desistió de la PQRSD con el número de radicado: ".$modelFile->numeroRadiRadicado . ' y la observación: ' . $observacionDesistimiento, //observación 
                        [],
                        array() //No validar estos campos
                    );

                    #  Log Auditoria  
                    HelperLog::logAdd(
                        false,
                        $modelUser->id, //Id user
                        $userName, //username
                        Yii::$app->controller->route, //Modulo
                        "El ciudadano: ".$senderName.", desistió de la PQRSD con el número de radicado: ".$modelFile->numeroRadiRadicado . ' y la observación: ' . $observacionDesistimiento, //observación 
                        [], // data old
                        [], //Data
                        array() //No validar estos campos
                    );
                  
                    $dataFiled[$modelFile->userIdTramitadorOld->email][] = [
                        'id' => $modelFile->idRadiRadicado,
                        'numeroRadicado' => $modelFile->numeroRadiRadicado,
                        'cliente' => $senderName
                    ];
                }

                /** Enviar de notificaciones por correo **/
                foreach ($dataFiled as $emailTramitante => $dataFile) {

                    # Envio correo masivo
                    if (count($dataFile) > 1) {

                        $bodyMail = 'radicacion-html'; 
                        $subject = 'subjectDisPqrs';
                        $numerosRadicados = '';

                        foreach ($dataFile as $value) {

                            $numerosRadicados .= $value['numeroRadicado'] . ', ';

                            $headMailText  = Yii::t('app', 'headMailDisPqrs',[
                                'numRadicado' => $value['numeroRadicado']
                            ]);    

                            $textBody  = Yii::t('app', 'textBodyDisPqrs',[
                                'nomCiu' => $value['cliente']
                            ]); 
        
                            $envioCorreo = CorreoController::differentFilings($emailTramitante, $headMailText, $textBody, $bodyMail, '', $subject, $radicadoUnico=false);

                        }

                    }  else { // Envio de correo cuando es un solo radicado Pqrs

                        $bodyMail = 'radicacion-html'; 
                        $subject = 'subjectDisPqrs';

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($dataFile[0]['id']));

                        $headMailText  = Yii::t('app', 'headMailDisPqrs',[
                            'numRadicado' => $dataFile[0]['numeroRadicado']
                        ]);    

                        $textBody  = Yii::t('app', 'textBodyDisPqrs',[
                            'nomCiu' => $dataFile[0]['cliente']
                        ]); 

                        $envioCorreo = CorreoController::radicacion($emailTramitante, $headMailText, $textBody, $bodyMail, $dataBase64Params, $subject);
                    }
                }
               

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {  

                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSaveWithdrawal'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados # 

                
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
     * Finds the RadiRadicados model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RadiRadicados the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findRadiRadicadosModel($id)
    {
        if (($model = RadiRadicados::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    /**
     * Finds the RadiAgendaRadicados model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RadiAgendaRadicados the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected static function findRadiAgendaRadicadosModel($id)
    {
        if (($model = RadiAgendaRadicados::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }


    protected function findUserModel($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    protected function findUserDetallesModel($id)
    {
        if (($model = UserDetalles::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    protected function findExpedienteModel($id)
    {
        if (($model = GdExpedientes::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

    public function actionReAsign() 
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

                //Obtener valores del request
                $idUser = $request['data']['idUsuarioTramitador']; // Usuario destino de la reasignacion
                $observacion = $request['data']['observacion'];
                $usersInformados = $request['data']['idUsuariosInfo']; // Listado de usuarios seleccionados para informar
                $dataResponse = []; // Data donde se guardan los nuevos datos del radicado

                if($usersInformados == ''){
                    $usersInformados = [];
                }                

                $strFechaActual = date('Y-m-d');
                $fechaActual = new DateTime($strFechaActual);

                $userModel = User::find()
                    ->where(['id' => $idUser])
                    ->one();

                $idDependencia = $userModel->idGdTrdDependencia;
                $dependenciaTramitador = $userModel->gdTrdDependencia->nombreGdTrdDependencia;
                $nombreUsuarioTramitador = $userModel->userDetalles->nombreUserDetalles.' '.$userModel->userDetalles->apellidoUserDetalles;
                
                # Modelo para obtener el id de la transacción
                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'send']);

                $arrayRadicadosNoProcesados = [];
                $arrayRadicadosProcesados = [];
                //$arrayRadicadosByUser = []; // Array de radicados procesados a agrupar por usuario // $arrayRadicadosByUser[$idUser][$idRadicado] = $radiRadicadosModel;
                
                /** Foreach de radicados seleccionados */
                foreach ($request['ButtonSelectedData'] as $key => $radicado) {

                    //Inicio de transacción en bd 
                    $transaction = Yii::$app->db->beginTransaction();

                    // Se le asigna el id del radicado a la variable
                    $idRadicado = $radicado['id'];

                    // Busca los radicados por el ID
                    $radiRadicadosModel = self::findRadiRadicadosModel($idRadicado);

                    // Valores anteriores del modelo se utiliza para el log
                    $userModelOld = UserDetalles::find()->where(['idUser' => $radiRadicadosModel->user_idTramitador])->one();
                    $dataOld = 'Id usuario tramitador: ' . $radiRadicadosModel->user_idTramitador 
                        . ', nombre: ' . $userModelOld->nombreUserDetalles.' '.$userModelOld->apellidoUserDetalles
                        . ', dependencia: ' . $userModelOld->user->gdTrdDependencia->nombreGdTrdDependencia;

                    /** Validar si el radicado pertenece al usuario seleccionado */
                    if ($radiRadicadosModel->user_idTramitador == $idUser) {
                        $arrayRadicadosNoProcesados[$idRadicado] = $radicado;

                    } else {

                        // Asignar tramitador actual como tramitador anterior del radicado, antes de guardar el registro del usuario seleccionado desde front
                        $radiRadicadosModel->user_idTramitadorOld = $radiRadicadosModel->user_idTramitador;

                        // Asignar nuevo tramitador del radicado
                        $radiRadicadosModel->idTrdDepeUserTramitador = $idDependencia;
                        $radiRadicadosModel->user_idTramitador = $idUser;

                        /** Actualizar datos del radicado */
                        if($radiRadicadosModel->save()){

                            $transaction->commit();

                            // Luego de la reasignación del radicado padre se reasigna los hijos acorde a la información del radicado padre
                            $radiRadicadosHijosModel = self::findRadiRadicadosHijosModel($idRadicado);

                            // Se retorna la información al initial List "Data actualizada"
                            $dataResponse[] = array(
                                'id' => $idRadicado,
                                'usuarioTramitador' => $nombreUsuarioTramitador,
                                'dependenciaTramitador' => $dependenciaTramitador,
                                'idInitialList' => $radicado['idInitialList'] * 1
                            );

                            //Obtener fecha vencimiento de radiRadicaos
                            $strFechaVencimiento = $radiRadicadosModel->fechaVencimientoRadiRadicados;
                            $fechavencimiento = new DateTime($strFechaVencimiento);

                            //Calcular diferencias en días entre la fecha de vencimiento
                            $diferenciaFechas = RadicadosController::calcularDiasVencimiento($radiRadicadosModel->idRadiRadicado);

                            //variables a usar en mensajes de correo
                            $numeroRadicado = $radiRadicadosModel->numeroRadiRadicado;
                            $asuntoRadicado = $radiRadicadosModel->asuntoRadiRadicado;
                            $maximoDias = $diferenciaFechas;

                            // Agregar radicado al array de radicados procesados
                            $arrayRadicadosProcesados[$idRadicado] = [
                                'dataFront' => $radicado,
                                'dataModel' => $radiRadicadosModel,
                                //Obtener fecha vencimiento de radiRadicaos
                                'strFechaVencimiento' => $strFechaVencimiento,
                                'fechavencimiento' => $fechavencimiento,
                                //Calcular diferencias en días entre la fecha de vencimiento
                                'diferenciaFechas' => $diferenciaFechas,
                                //variables a usar en mensajes de correo
                                'numeroRadicado' => $numeroRadicado,
                                'asuntoRadicado' => $asuntoRadicado,
                                'maximoDias' => $maximoDias,
                            ];

                            $dataNew = 'Id usuario tramitador: ' . $radiRadicadosModel->idRadiRadicado . ', nombre: ' . $nombreUsuarioTramitador . ', dependencia: ' . $dependenciaTramitador;

                            $eventosLogTextNewReasign = Yii::$app->params['eventosLogText']['newReasign'];
                            # Validar si es radicado o temporal (consecutivo)
                            if ($radiRadicadosModel->isRadicado == false || $radiRadicadosModel->isRadicado == 0) {
                                $eventosLogTextNewReasign = str_replace('radicado' ,'consecutivo', $eventosLogTextNewReasign);
                                $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFileTmp'];
                            } else {
                                $msgCreateOrReassingFile = Yii::t('app','messageNotification')['createOrReassingFile'];
                            }

                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                true,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                $eventosLogTextNewReasign . $radiRadicadosModel->numeroRadiRadicado . ", al usuario: " . $nombreUsuarioTramitador, //texto para almacenar en el evento
                                $dataOld, //DataOld
                                $dataNew, //Data
                                array() //No validar estos campos
                            );
                            /***    Fin log Auditoria   ***/

                            /***    Log de Radicados  ***/  
                            HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $radiRadicadosModel->idRadiRadicado, //Id radicado
                                $idTransacion->idCgTransaccionRadicado,
                                //$observacion, //observación 
                                $eventosLogTextNewReasign . 'al usuario: ' . $nombreUsuarioTramitador . ' con la siguiente observación: ' . $observacion, //observación 
                                $radiRadicadosModel,
                                array() //No validar estos campos
                            );
                            /***    Fin log Radicados   ***/


                            /***  Notificacion  ***/
                            HelperNotification::addNotification(
                                Yii::$app->user->identity->id, //Id user creador
                                $idUser, // Id user notificado
                                $msgCreateOrReassingFile . $radiRadicadosModel->numeroRadiRadicado, //Notificacion
                                Yii::$app->params['routesFront']['viewRadicado'], // url
                                $radiRadicadosModel->idRadiRadicado // id radicado
                            );
                            /***  Fin Notificacion  ***/
                                            
                        } else {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $radiRadicadosModel->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                    /** Fin Validar si el radicado pertenece al usuario seleccionado */    

                }
                /** Fin Foreach de radicados seleccionados */

                /** Envio de correo al usuario seleccionado */
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombreUserLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaUserLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                $countRadicadosProcesados = count($arrayRadicadosProcesados);
                if ($countRadicadosProcesados > 0) {

                    # Envia la notificación de correo electronico al usuario de tramitar
                    $emailTramitante = $userModel->email;

                    if ($countRadicadosProcesados > 1) {

                        $textBody = '';
                        $link = Yii::$app->params['ipServer'] . 'login';
                        $nameButtonLink = 'Ir al sistema'; // Esta variable sera traducida

                        $arrayIsRadicado = [];
                        foreach ($arrayRadicadosProcesados as $radi) {
                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radi['dataModel']['idRadiRadicado']));
                            $href = Yii::$app->params['ipServer'] . 'filing/filing-view/' . $dataBase64Params;
                            $textBody .= '<a href="'.$href.'">' . $radi['dataModel']['numeroRadiRadicado'] . '</a>, ';

                            $arrayIsRadicado[] = ($radi['dataModel']['isRadicado'] == 0 || $radi['dataModel']['isRadicado'] == false) ? 'consecutivo' : 'radicado';
                        }

                        # Validar si se están procesando consecutivos, radicados o los dos
                        if (in_array('consecutivo', $arrayIsRadicado) && in_array('radicado', $arrayIsRadicado)) {
                            $messageResponse = Yii::t('app', 'successReassignmentGroupAll');
                            $setSubject = 'setSubjectNotificacionTramitanteGroupAll'; // Esta variable sera traducida
                            $headMailText = Yii::t('app', 'headMailTextNotificacionTramitanteGroupAll');
                        } elseif (in_array('consecutivo', $arrayIsRadicado)) {
                            $messageResponse = Yii::t('app', 'successReassignmentGroupConsecutivo');
                            $setSubject = 'setSubjectNotificacionTramitanteGroupConsecutivo'; // Esta variable sera traducida
                            $headMailText = Yii::t('app', 'headMailTextNotificacionTramitanteGroupConsecutivo');
                        } elseif (in_array('radicado', $arrayIsRadicado)) {
                            $messageResponse = Yii::t('app', 'successReassignmentGroupRadicado');
                            $setSubject = 'setSubjectNotificacionTramitanteGroupRadicado'; // Esta variable sera traducida
                            $headMailText = Yii::t('app', 'headMailTextNotificacionTramitanteGroupRadicado');
                        }

                        $textBody = substr($textBody, 0, -2);
                        $textBody .= '<br><br> ' . Yii::t('app', 'textBodyNotificacionTramitanteGroup', [
                            'usuario' => $nombreUserLogged,
                            'dependencia' => $dependenciaUserLogged
                        ]);
                    } else {

                        if (end($arrayRadicadosProcesados)['dataModel']['isRadicado'] == false || end($arrayRadicadosProcesados)['dataModel']['isRadicado'] == 0) {
                            $messageResponse = Yii::t('app', 'successReassignmentTmp');

                            $textBodyNotificacionTramitante = 'textBodyNotificacionTramitanteTmp'; // Esta variable sera traducida
                            $headMailTextNotificacionTramitante = 'headMailTextNotificacionTramitanteTmp'; // Esta variable sera traducida
                            $nameButtonLink = 'buttonLinkRadicadoTmp'; // Esta variable sera traducida
                            $setSubject = 'rootedAllocationTmp'; // Esta variable sera traducida
                        } else {
                            $messageResponse = Yii::t('app', 'successReassignment');

                            $textBodyNotificacionTramitante = 'textBodyNotificacionTramitante'; // Esta variable sera traducida
                            $headMailTextNotificacionTramitante = 'headMailTextNotificacionTramitante'; // Esta variable sera traducida
                            $nameButtonLink = 'buttonLinkRadicado'; // Esta variable sera traducida
                            $setSubject = 'rootedAllocation'; // Esta variable sera traducida
                        }

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode(end($arrayRadicadosProcesados)['dataModel']['idRadiRadicado']));
                        $link = Yii::$app->params['ipServer'] . 'filing/filing-view/' . $dataBase64Params;
                        $headMailText = Yii::t('app', $headMailTextNotificacionTramitante, ['numRadi' => $numeroRadicado]);
                        $textBody = Yii::t('app', $textBodyNotificacionTramitante, [
                            'numeroRadicado' => $numeroRadicado,
                            'asunto'         => $asuntoRadicado,
                            'usuario'        => $nombreUserLogged,
                            'dependencia'    => $dependenciaUserLogged,
                            'dias'           => $maximoDias,
                        ]);
                    }
                    $bodyMail = 'radicacion-html';

                    $envioCorreo = CorreoController::sendEmail($emailTramitante, $headMailText, $textBody, $bodyMail, [], $link, $setSubject, $nameButtonLink);
                    if (count($usersInformados) > 0) {
                        $informarReasignacion = $this->informarReasignacion($usersInformados, $arrayRadicadosProcesados, $nombreUserLogged, $dependenciaUserLogged, $userModel->id);
                    }

                    
                    /** Evaluar mensajes de respuesta */
                    $radicadosNoProcesados = '';
                    foreach ($arrayRadicadosNoProcesados as $value) {
                        $radicadosNoProcesados .= $value['numeroRadiRadicado'] . ', ';
                    }
                    $radicadosNoProcesados =  substr($radicadosNoProcesados, 0, -2);

                    $countRadicadosNoProcesados = count($arrayRadicadosNoProcesados);
                    if ($countRadicadosNoProcesados > 1) {
                        $messageRadicadosNoProcesados = Yii::t('app', 'errorRadicadosNoProcesados', [
                            'radicadosNoProcesados' => $radicadosNoProcesados,
                        ]);

                    } elseif ($countRadicadosNoProcesados == 1) {
                        $messageRadicadosNoProcesados = Yii::t('app', 'errorRadicadoNoProcesado', [
                            'radicadoNoProcesado' => $radicadosNoProcesados,
                        ]);
                    } else {
                        $messageRadicadosNoProcesados = '';
                    }
                    /** Fin Evaluar mensajes de respuesta */

                    /**
                     * Respuesta de exito del servicio
                     */
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $messageResponse,
                        'data' => $dataResponse,
                        'messageRadicadosNoProcesados' => $messageRadicadosNoProcesados,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    /** Ningun radicado presenta cambio de remitente */
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorReasignacionSameUser')]],
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

    //sguarin Cuando incluyo un radicado a un expediente
    public function actionIncludeExpedient()
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
                // set_time_limit(10000);

                $transaction = Yii::$app->db->beginTransaction();

                $idExpediente = $request['data']['idExpediente'];

                $idUser = Yii::$app->user->identity->id;
                // $idDependencia = self::findUserModel($idUser)->gdTrdDependencia->idGdTrdDependencia; 
                $idDependencia = Yii::$app->user->identity->idGdTrdDependencia; 
                
                //consulta del expediente
                $modelExpediente = self::findExpedienteModel( $idExpediente);
                
                $numerosRadicados = false;
                $arrayIncluidos = [];
                $idRadiLog  = []; // Ids de los radicados incluidos                          

                # Consulta la transacción para el log de radicado
                $modelTransacionIncludeInFile = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'includeInFile']);
                $idTransacionIncludeInFile = $modelTransacionIncludeInFile->idCgTransaccionRadicado;

                //sguarin data de los radicados seleccionados
                //return $request['ButtonSelectedData'];
                /** Procesar inclusion de radicados al expediente creado */
                $includeInExpedient = HelperExpedient::includeInExpedient($request['ButtonSelectedData'], $modelExpediente);
                if ($includeInExpedient['status'] == false) {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $includeInExpedient['errors'],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } else {
                    $arrayMensajes = $includeInExpedient['data']['arrayMensajes'];
                }
                /** Fin Procesar inclusion de radicados al expediente creado */ 
                
                /** Respuesta del servicio */
                $transaction->commit();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $arrayMensajes,
                    'data' => [],
                    'status' => 200
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

    /**
     * Servicio que permite cargar documentos de forma manual al Expediente 
     **/
    public function actionUploadDocumentToExpedient($request){
                
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            if (!empty($request)) {

                //*** Inicio desencriptación POST ***//
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
                //*** Fin desencriptación POST ***//
        
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

                if($fileUpload){

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

                    // Data del formulario
                    $tipoDocu = $request['dataForm']['idTipoDocumental'];             
                    $descripcion = $request['dataForm']['observacion'];
                    
                    if($request['dataForm']['fechaDocumento'] != "") {
                        $fechaDocumento = explode('T', $request['dataForm']['fechaDocumento'])[0].' '.date('H:i:s');

                    } else {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app','errorDateDocument')]],                            
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $isPublicoRadiDocumento = ($request['dataForm']['isPublicoRadiDocumento'] === true) ? Yii::$app->params['SiNoText']['Si'] : Yii::$app->params['SiNoText']['No'];

                    //Data Usuario logueado
                    $idUser = Yii::$app->user->identity->id;
                    $idDependencia = self::findUserModel($idUser)->gdTrdDependencia->idGdTrdDependencia;
                    
                    foreach($request['ButtonSelectedData'] as $key => $item){ 
                        
                        $anio = date('Y');
                        $id = $item['id'];                        
                        
                        $modelExpediente = self::findExpedienteModel($id);                       

                        $numerosRadicados = false;
                        $arrayIncluidos = [];
                        $idRadiLog  = []; // Ids de los radicados incluidos

                        $pathUploadFile = Yii::getAlias('@webroot')                            
                            . "/" . Yii::$app->params['bodegaRadicados']
                            . "/" . $anio                            
                            . "/" . $modelExpediente->numeroGdExpediente . "/" 
                        ;                    

                        /*$modelExpediente = GdExpedientes::find()
                            ->where(['idGdExpediente' => $item['id']])
                        ->one();*/
                        
                        $orden = $modelExpediente->numeracionGdExpediente;

                        $modelExpedienteDocumento = new GdExpedienteDocumentos();

                        $modelExpedienteDocumento->idGdExpediente = $modelExpediente->idGdExpediente;
                        $modelExpedienteDocumento->numeroGdExpedienteDocumento = $modelExpediente->numeroGdExpediente;
                        $modelExpedienteDocumento->rutaGdExpedienteDocumento = $pathUploadFile;
                        $modelExpedienteDocumento->extensionGdExpedienteDocumento = $fileUpload->extension;
                        $tamano = $fileUpload->size / 1000;
                        $modelExpedienteDocumento->tamanoGdExpedienteDocumento = '' . $tamano . ' KB';
                        $modelExpedienteDocumento->idGdTrdTipoDocumental = $tipoDocu;
                        $modelExpedienteDocumento->isPublicoGdExpedienteDocumento = $isPublicoRadiDocumento;                        
                        $modelExpedienteDocumento->idUser = $idUser;
                        $modelExpedienteDocumento->observacionGdExpedienteDocumento = $descripcion;
                        $modelExpedienteDocumento->estadoGdExpedienteDocumento = Yii::$app->params['statusTodoText']['Activo'];
                        $modelExpedienteDocumento->creacionGdExpedienteDocumento = date('Y-m-d H:i:s');
                        $modelExpedienteDocumento->fechaDocGdExpedienteDocumento = $fechaDocumento;
                        
                        if(!$modelExpedienteDocumento->save()){  
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelExpedienteDocumento->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $nomArchivo = "$modelExpediente->numeroGdExpediente-" . $modelExpedienteDocumento->idGdExpedienteDocumento . ".$fileUpload->extension";
                        $modelExpedienteDocumento->nombreGdExpedienteDocumento =  $nomArchivo;

                        if(!$modelExpedienteDocumento->save()){                            
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelExpedienteDocumento->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $xml = HelperIndiceElectronico::getXmlParams( $modelExpediente);
                        
                        $resultado = HelperIndiceElectronico::addDocumentUploadToExpedient($modelExpedienteDocumento, $modelExpediente, $xml);                      
                        
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

                        $nombreTipoDocumental = $modelExpedienteDocumento->gdTrdTipoDocumental->nombreTipoDocumental;                    

                        $observacion = "Se cargó documento con el tipo documental: ".$nombreTipoDocumental." de forma individual al expediente";                       

                        if($modelExpedienteDocumento->save()){

                            /***    Log de Radicados  ***/
                            HelperLog::logAddExpedient(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $item['id'], //Id expediente
                                Yii::$app->params['operacionExpedienteText']['SubirDocumento'], //Operación
                                $observacion //observación
                            );
                            /***  Fin  Log de Radicados  ***/ 

                        }else{
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelExpedienteDocumento->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $rutaOk = true;

                        // Verificar que la carpeta exista y crearla en caso de no exista
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
                                'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /*** Fin Validar creación de la carpeta***/
                        $file = $pathUploadFile . $nomArchivo;
                        $uploadExecute = $fileUpload->saveAs($file, false);

                        ///////////////////////// EXTRAER TEXTO  ////////////////////////
                           $helperExtraerTexto = new HelperExtraerTexto($file);

                           $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                               $modelExpedienteDocumento->idGdExpedienteDocumento,     
                               Yii::$app->params['tablaOcrDatos']['gdExpedienteDoc']  
                           );

                           if($helperOcrDatos['status'] != true){
                               Yii::$app->response->statusCode = 200;
                               $response = [
                                   'message' => $helperOcrDatos['message'],
                                   'data'    => $helperOcrDatos['data'],
                                   'status'  => Yii::$app->params['statusErrorValidacion'],
                               ];
                               return HelperEncryptAes::encrypt($response, true);
                           }
                        ///////////////////////// EXTRAER TEXTO  ////////////////////////

                        
                        if($uploadExecute){

                            $userLogin = UserDetalles::find()
                                ->where(['idUser' => Yii::$app->user->identity->id])
                            ->one();

                            $infoGdExpedienteDocumentos = new GdExpedienteDocumentos();
                            $attributeLabels = $infoGdExpedienteDocumentos->attributeLabels();

                            $dataNew = $attributeLabels['idGdExpedienteDocumento'] . ': ' . $modelExpedienteDocumento->idGdExpedienteDocumento
                                . ', ' . $attributeLabels['nombreGdExpedienteDocumento'] . ': ' . $modelExpedienteDocumento->nombreGdExpedienteDocumento
                                . ', ' . $attributeLabels['observacionGdExpedienteDocumento'] . ': ' . $modelExpedienteDocumento->observacionGdExpedienteDocumento
                                . ', Tipo Documental: ' . $nombreTipoDocumental . ', ' . $attributeLabels['fechaDocGdExpedienteDocumento'] . ': ' . $modelExpedienteDocumento->fechaDocGdExpedienteDocumento;

                            /*** log Auditoria ***/        
                            HelperLog::logAdd(
                                true,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['operacionExpedienteNumber'][5] . ' al N° expediente: ' .  $modelExpedienteDocumento->numeroGdExpedienteDocumento .', nombre expediente: '.$modelExpedienteDocumento->nombreGdExpedienteDocumento.' por el usuario ' . $userLogin['nombreUserDetalles'] . ' ' . $userLogin['apellidoUserDetalles'], // texto para almacenar en el evento
                                '',
                                $dataNew, //Data
                                array() //No validar estos campos
                            );
                            /***  Fin log Auditoria   ***/     

                        }else{
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $orden++;

                        $modelExpediente->numeracionGdExpediente = $orden;                        
                        $modelExpediente->save();

                        $transaction->commit();

                        //Repuesta success                        
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','successUpLoadFile'),
                            'data' => [],
                            'status' => 200
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    }

                }else{
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                        'data' => ['error' => [Yii::t('app', 'canNotUpFile')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                
            }else{
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /** Función para informar la reasignacion de radicados a los usuarios seleccionados */
    protected function informarReasignacion($usersInformados, $arrayRadicadosProcesados, $nombreUserLogged, $dependenciaUserLogged, $idUserTramitador)
    {
        if (count($usersInformados) == 0) {
            return [
                'status' => false,
                'message' => 'No hay usuarios a quien informar', // Mensaje solo para debug
            ];
        } else {
            //return $usersInformados;

            /** Construccion de datos del correo */
            if (count($arrayRadicadosProcesados) > 1) {
                $link = Yii::$app->params['ipServer'] . 'dashboard';
                $nameButtonLink = 'Ir al sistema'; // Esta variable sera traducida
                $setSubject = 'setSubjectNotificacionInformadoGroup'; // Esta variable sera traducida
                $headMailText = Yii::t('app', 'headMailTextNotificacionInformadoGroup');
                $textBody = '';
                foreach ($arrayRadicadosProcesados as $radi) {
                    $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radi['dataModel']['idRadiRadicado']));
                    $href = Yii::$app->params['ipServer'] . 'filing/filing-view/' . $dataBase64Params;
                    $textBody .= '<a href="'.$href.'">' . $radi['dataModel']['numeroRadiRadicado'] . '</a>, ';
                }
                $textBody = substr($textBody, 0, -2);
                $textBody .= '<br><br> ' . Yii::t('app', 'textBodyNotificacionInformadoGroup', [
                    'usuario' => $nombreUserLogged,    
                    'dependencia' => $dependenciaUserLogged,    
                ]);

            } else {
                $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode(end($arrayRadicadosProcesados)['dataModel']['idRadiRadicado']));
                $link = Yii::$app->params['ipServer'] . 'filing/filing-view/' . $dataBase64Params;
                $nameButtonLink = 'buttonLinkRadicado'; // Esta variable sera traducida
                $setSubject = 'subjectNotificacionInformado'; // Esta variable sera traducida
    
                $headMailText = Yii::t('app', 'headMailTextNotificacionInformado', [
                    'numRadicado' => end($arrayRadicadosProcesados)['numeroRadicado']
                ]);
                $textBody = Yii::t('app', 'textBodyNotificacionInformado', [
                    'NoRadicado' => end($arrayRadicadosProcesados)['numeroRadicado'],
                    'Asunto' => end($arrayRadicadosProcesados)['asuntoRadicado'],
                    'Username' => $nombreUserLogged,
                    'NameDependencia' => $dependenciaUserLogged,
                ]);
            }
            $bodyMail = 'radicacion-html';
            /** Fin Construccion de datos del correo */

            /** consultar usiarios a informar */
            $usersInformadosModel = User::find()
                ->select(['id','email'])
                ->where(['IN','id', $usersInformados])
            ->all();

            # Consulta la accion de la transaccion de estado rechazado para obtener su id
            $modelTransacionInformado = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'copyInformaded']);
            $idTransacionInformado = $modelTransacionInformado->idCgTransaccionRadicado;

            /** Foreach de usuarios a informar */
            foreach ($usersInformadosModel as $userInformado) {

                if ($idUserTramitador != $userInformado['id']) {
                    
                    $userInformadoDetalles = UserDetalles::find()
                        ->select(['nombreUserDetalles','apellidoUserDetalles'])
                        ->where(['idUser' => $userInformado['id']])
                    ->one();

                    $nombreUsuarioInformado = $userInformadoDetalles['nombreUserDetalles'] .  ' ' . $userInformadoDetalles['apellidoUserDetalles'];
                
                    /** Enviar correo */
                    $envioCorreo = CorreoController::sendEmail($userInformado['email'], $headMailText, $textBody, $bodyMail, [], $link, $setSubject, $nameButtonLink);

                    if ($envioCorreo['status'] == true) {
                        
                        foreach ($arrayRadicadosProcesados as $radicadoProcesado) {
                            $modelRadiInformados = new RadiInformados();
                            $modelRadiInformados->idUser = $userInformado['id'];
                            $modelRadiInformados->idRadiRadicado = $radicadoProcesado['dataModel']['idRadiRadicado'];

                            if($modelRadiInformados->save()){

                                /***    Log de Radicados  ***/    
                                HelperLog::logAddFiling(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $modelRadiInformados->idRadiRadicado, //Id radicado
                                    $idTransacionInformado,
                                    Yii::$app->params['eventosLogText']['newInformado'] . ' al usuario ' . $nombreUsuarioInformado, //observación 
                                    $modelRadiInformados,
                                    array() //No validar estos campos
                                );
                                /***    Fin log Radicados   ***/

                                $dataNew = 'Id radicado informado: ' . $modelRadiInformados->idRadiInformado
                                    . ', Nombre de usuario informado: ' . $nombreUsuarioInformado
                                    . ', Número radicado: ' . $radicadoProcesado['numeroRadicado']
                                ;

                                /***    Log de Auditoria  ***/
                                HelperLog::logAdd(
                                    true,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    Yii::$app->params['eventosLogText']['newInformado'] . $radicadoProcesado['numeroRadicado'] . " al usuario " . $nombreUsuarioInformado, //texto para almacenar en 
                                    '',
                                    $dataNew, //Data
                                    array() //No validar estos campos
                                );                    
                                /***    Fin log Auditoria   ***/
                            }
                        }

                    }
                }
                
            }
            /** Fin Foreach de usuarios a informar */

            return $usersInformadosModel;
            return [
                'status' => true,
                'message' => 'ok',
            ];

        }
    }

    /**
     *  Transaccion Cargar Plantillas
     * 
     * @param array   ButtonSelectedData
     * @param string  dataForm[nameFile]
     * 
     * @return array message success 
     */
    public function actionLoadFormat($request)
    {

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
              
                    if($fileUpload){

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
                        $idRadiSeleccionados = []; // Array de radicados seleccionados en el form

                        # Consulta la accion de la transaccion de estado rechazado para obtener su id
                        $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'loadFormat']);
                        $idTransacion = $modelTransacion->idCgTransaccionRadicado;
        
                            $validationExtension = ['docx','doc','odt'];
                    
                            /* validar tipo de archivo */
                            if (!in_array($fileUpload->extension, $validationExtension)) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                                    'data' => ['error' => [ Yii::t('app','fileDonesNotCorrectFormat')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                            /* Fin validar tipo de archivo */

                        $nombreRadiDocumentoPrincipal = $request['dataForm']['nameFile'];


                        /** Inicio Validar si el radicado ya tiene un documento firmado */
                        $idsRadis = [];
                        foreach ($request['ButtonSelectedData'] as $radi) {
                            $idsRadis[] = $radi['id'];
                        }

                        $plantillaFirmada = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal', 'idRadiRadicado'])
                            ->where(['IN', 'idRadiRadicado', $idsRadis])
                            ->andWhere(['or',
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesandoFirma']]
                            ])
                            ->limit(1)
                            ->one();

                        if($plantillaFirmada != null) {
                            $modelRadicado = RadiRadicados::find()->where(['idRadiRadicado' => $plantillaFirmada->idRadiRadicado])->one();

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [ Yii::t('app','errUploadRadiWithSing', ['numRadi' => $modelRadicado->numeroRadiRadicado])]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                        /** Fin Validar si el radicado ya tiene un documento firmado */

                        $arrayRadiOrConsecutive = [];

                        foreach($request['ButtonSelectedData'] as $key => $radicados){


                            $idRadiRadicado = $radicados['id'];
                            # Se agrupa todos los radicados seleccionados para notificacion
                            $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();
                            
                            $idRadiSeleccionados[] = $Radicados['numeroRadiRadicado'];
        
                            $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $Radicados->idTrdDepeUserCreador]);
                            $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

                            $rutaOk = true; // ruta base de la carpeta existente

                            $nombreArchivo = 'doc-'.$Radicados['numeroRadiRadicado'];

                            $pathPlantilla = Yii::getAlias('@webroot')                            
                                . "/" . Yii::$app->params['bodegaRadicados']
                                . "/" . date("Y")                         
                                . "/" . $codigoGdTrdDependencia
                                . "/" . "tmp/" 
                            ;

                            // Verificar que la carpeta exista y crearla en caso de no exista
                            if (!file_exists($pathPlantilla)) {
                                if (!FileHelper::createDirectory($pathPlantilla, $mode = 0775, $recursive = true)) {
                                    $rutaOk = false;
                                }
                            }

                            /*** Validar creación de la carpeta***/
                            if ($rutaOk == false) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                                    //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                            /*** Fin Validar creación de la carpeta***/

                            $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

                            $nombreValido = trim(strtoupper($nombreRadiDocumentoPrincipal));

                            /*** Guardar en base de datos la info del documento ***/
                            $model = new RadiDocumentosPrincipales;

                            $model->idRadiRadicado                  = $idRadiRadicado;
                            $model->idUser                          = Yii::$app->user->identity->id;
                            $model->nombreRadiDocumentoPrincipal    = $nombreValido;
                            $model->rutaRadiDocumentoPrincipal      = $pathPlantilla;
                            $model->extensionRadiDocumentoPrincipal = $fileUpload->extension;
                            $model->imagenPrincipalRadiDocumento    = Yii::$app->params['statusTodoText']['Inactivo'];
                                    
                            $tamano = $fileUpload->size / 1000;
                            $model->tamanoRadiDocumentoPrincipal = '' . $tamano . ' KB';

                            
                            /** Procesar el errores de la fila */
                            if (!$model->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $model->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                            /*** Fin Consultar si es un registro nuevo ***/

                            $nombreArchivo = $nombreArchivo.'-'.$model->idradiDocumentoPrincipal;

                            // validar signo '/' en el nombre para no crear nuevos directorios
                            $rutaCgPlantilla = $pathPlantilla . $nombreArchivo.'.'.$fileUpload->extension;      
        
                            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id sin eliminar el tempFile
                            $uploadExecute = $fileUpload->saveAs($rutaCgPlantilla, false);

                            ///////////////////////// EXTRAER TEXTO  ////////////////////////
                                // $helperExtraerTexto = new HelperExtraerTexto($rutaCgPlantilla);
                                
                                // if(!is_null($helperExtraerTexto)){
                                //     $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                                //         $model->idradiDocumentoPrincipal,     
                                //         Yii::$app->params['tablaOcrDatos']['radiDocPrincipales']  
                                //     );
    
                                //     if($helperOcrDatos['status'] != true){
                                //         Yii::$app->response->statusCode = 200;
                                //         $response = [
                                //             'message' => $helperOcrDatos['message'],
                                //             'data'    => $helperOcrDatos['data'],
                                //             'status'  => Yii::$app->params['statusErrorValidacion'],
                                //         ];
                                //         return HelperEncryptAes::encrypt($response, true);
                                //     }
                                // }else{

                                //     $observacion = 'No se puedo hacer el proceso de extracción de texto al documento que esta cargando';
                                //     /***    Log de Auditoria  ***/
                                //     HelperLog::logAdd(
                                //         false,
                                //         Yii::$app->user->identity->id, //Id user
                                //         Yii::$app->user->identity->username, //username
                                //         Yii::$app->controller->route, //Modulo
                                //         $observacion,
                                //         [], //DataOld
                                //         [], //[$model], //Data
                                //         array() //No validar estos campos
                                //     );
                                // }
                                
                            ///////////////////////// EXTRAER TEXTO  ////////////////////////

                            $transaction->commit();

                            $upModel = RadiDocumentosPrincipales::findOne(['idradiDocumentoPrincipal' => $model->idradiDocumentoPrincipal]);
                            $upModel->rutaRadiDocumentoPrincipal   = $rutaCgPlantilla;

                            /** Procesar el errores de la fila */
                            if (!$upModel->save()) {
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $upModel->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                            // Se retorna el estado de cada registro

                            $idInitialList = $radicados['idInitialList'] ?? 0;
                            $dataResponse[] = array(
                                'id' =>  $radicados['id'],
                                'idInitialList' => $idInitialList * 1,
                                'status' => Yii::$app->params['statusTodoText']['Activo'],
                                'statusText' =>  Yii::t('app', 'statusTodoNumber')[Yii::$app->params['statusTodoText']['Activo']],  
                            );

                            if ((boolean) $Radicados->isRadicado == true) {
                                $radiOrConsecutive = ' al radicado ';
                                $arrayRadiOrConsecutive[] = 'radicado';
                            } else {
                                $radiOrConsecutive = ' al consecutivo temporal ';
                                $arrayRadiOrConsecutive[] = 'consecutivo';
                            }

                            /***    Log de Radicados  ***/
                            HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $model->idRadiRadicado, //Id radicado
                                $idTransacion,
                                Yii::$app->params['eventosLogTextRadicado']['cargaPlantilla'].' '.$nombreValido. $radiOrConsecutive .$Radicados['numeroRadiRadicado'], //texto para almacenar en el evento
                                $model,
                                array() //No validar estos campos
                            );
                            /***  Fin  Log de Radicados  ***/ 

                        }

                    }else{
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'enterFile')],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                $implodeRadi = implode(', ',$idRadiSeleccionados);
                
                if(count($idRadiSeleccionados) > 1){
                    if (in_array('radicado', $arrayRadiOrConsecutive) && in_array('consecutivo', $arrayRadiOrConsecutive)) {
                        $msjRadicados =  Yii::t('app','MasiveRadiAll', ['numRadi' => $implodeRadi]);
                    } elseif (in_array('radicado', $arrayRadiOrConsecutive)) {
                        $msjRadicados =  Yii::t('app','MasiveRadi', ['numRadi' => $implodeRadi]);
                    } elseif (in_array('consecutivo', $arrayRadiOrConsecutive)) {
                        $msjRadicados =  Yii::t('app','MasiveRadiTmp', ['numRadi' => $implodeRadi]);
                    }
                }else{
                    if (in_array('radicado', $arrayRadiOrConsecutive)) {
                        $msjRadicados =  Yii::t('app','OneRadi', ['numRadi' => $implodeRadi]);
                    } else {
                        $msjRadicados =  Yii::t('app','OneRadiTmp', ['numRadi' => $implodeRadi]);
                    }
                }

                $observacion = Yii::t('app','cargaPlantilla', [
                    'nombrePlantilla' => $nombreValido,
                    'msjRadicados' => $msjRadicados
                ]);

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacion,
                    [], //DataOld
                    [], //[$model], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' =>  $observacion,
                    'dataResponse' => $dataResponse ?? false,
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
     *  Transaccion Combinación de Correspondencia
     * 
     * @param array   ButtonSelectedData
     * @param int     data[id]
     * 
     * @return array message success 
     */
    public function actionCorrespondenceMatch() {
        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

                /** Consultar información del usuario logueado */
                $tablaUser = User::tableName() . ' AS U';
                $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';

                $infoUserLogued = (new \yii\db\Query())
                    ->select(['U.id', 'U.username', 'U.idGdTrdDependencia', 'UD.nombreUserDetalles', 'UD.apellidoUserDetalles', ])
                    ->from($tablaUser);
                    // ->innerJoin($tablaUserDetalles, '`u`.`id` = `ud`.`idUser`')
                    $infoUserLogued = HelperQueryDb::getQuery('innerJoinAlias', $infoUserLogued, $tablaUserDetalles, ['U' => 'id', 'UD' => 'idUser']);
                    $infoUserLogued = $infoUserLogued->where(['U.id' => Yii::$app->user->identity->id])
                ->one();

                /** Validar archivo**/
                $rutaOk = true;
                $idRadiSeleccionados = []; // Array de radicados seleccionados en el form
                $dataResponse = [];

                # Consulta la accion de la transaccion de combinación de correspondencia
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceMatch']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                $transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

                $isRadiTemporal = false;

                /** Consulta a la configuración general */
                $modelCgGeneral = CgGeneral::find()
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();

                foreach($request['ButtonSelectedData'] as $key => $radicado) {

                    $idRadiRadicado = $radicado['id'];
                    # Se agrupa todos los radicados seleccionados para notificacion
                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();

                    if ($Radicados->isRadicado == false || $Radicados->isRadicado == 0) {
                        $isRadiTemporal = true;
                    }
                    $idRadiSeleccionados[] = $Radicados['numeroRadiRadicado'];

                    //$generarNuevoRadicado = false;

                    # Se valida el tipo de radicado segun el flujo del proceso, (Si el radicado es de entrada o pqrs se debe generar el Radicado de salida)
                    # Si 'isNewFiling' es TRUE va a generar el nuevo numero de radicado, de lo contrario se asigna el radicado padre
                    switch($Radicados->idCgTipoRadicado) {
                        # Entrada
                        case Yii::$app->params['idCgTipoRadicado']['radiEntrada']:
                            //$generarNuevoRadicado = true;

                            if($request['isNewFiling']){
                                $generarRadicado = RadicadosController::generarRadicado($Radicados, $infoUserLogued, Yii::$app->params['idCgTipoRadicado']['radiSalida']);
                            }

                        break;

                        # Pqrsd
                        case Yii::$app->params['idCgTipoRadicado']['radiPqrs']:
                            //$generarNuevoRadicado = true;

                            if($request['isNewFiling']){
                                $generarRadicado = RadicadosController::generarRadicado($Radicados, $infoUserLogued, Yii::$app->params['idCgTipoRadicado']['radiSalida']);
                            }
                        break;

                        // Resoluciones
                        case $modelCgGeneral->resolucionesIdCgGeneral:
                            if (!$Radicados->radiRadicadosResoluciones) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app','errorSinDetalleResoluciones')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);

                            } else {
                                /** Validar si el radicado ya posee una plantilla con combinación de correspondencia, en este caso se debe generar un nuevo número de radicado */
                                $plantillaCorrespondencia = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
                                    ->where(['idRadiRadicado' => $Radicados['idRadiRadicado'], 'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
                                    ->limit(1)
                                    ->one();
                                if ($plantillaCorrespondencia != null) {
                                    if($request['isNewFiling']){
                                        $generarRadicado = RadicadosController::generarRadicado($Radicados, $infoUserLogued);
                                    }
                                }
                            }
                        break;

                        # Salida, Comunicaciones Internas .....
                        default:
                            /** Validar si el radicado ya posee una plantilla con combinación de correspondencia, en este caso se debe generar un nuevo número de radicado */
                            $plantillaCorrespondencia = RadiDocumentosPrincipales::find()->select(['idradiDocumentoPrincipal'])
                                ->where(['idRadiRadicado' => $Radicados['idRadiRadicado'], 'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
                                ->limit(1)
                                ->one();
                            if ($plantillaCorrespondencia != null) {
                                //$generarNuevoRadicado = true;

                                if($request['isNewFiling']){
                                    // $generarRadicado = RadicadosController::generarRadicado($Radicados, $infoUserLogued);
                                    $generarRadicado = ['status' => true,'message' => 'Ok','errors' => [],'data' => $Radicados,];
                                }
                            }
                        break;
                    }

                    $modelRadicadoGenerado = null;
                    //if ($generarNuevoRadicado == true) {
                    if($request['isNewFiling']) {

                        if ($generarRadicado['status'] == false) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $generarRadicado['errors'],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                            
                        } else {
                            $modelRadicadoGenerado = $generarRadicado['data'];
                        }
                    }

                    # Se consulta la plantilla cargada por medio de su id
                    $modelPlantilla = RadiDocumentosPrincipales::find()->where([
                        'idradiDocumentoPrincipal' => $request['data']['id']
                    ])->one();

                    if(!isset($modelPlantilla)){
                        $transaction->rollBack();
                        throw new NotFoundHttpException('The requested page does not exist.');
                    }

                    $HelperPlantillas= new HelperPlantillas($modelPlantilla->rutaRadiDocumentoPrincipal);
                    $structure_word= $HelperPlantillas->convertToText();

                    if(isset($structure_word['status'])){
                        $transaction->rollBack();
                        if(!$structure_word['status']){
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [$structure_word['message']]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    # Valida las etiquetas que se reemplazarán al hacer combinación
                    $match_correspondence = $HelperPlantillas->match_template($structure_word, $idRadiRadicado, $modelRadicadoGenerado);
    
                    # Se consulta el codigo de la dependencia para obtener la ruta del archivo
                    $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $Radicados->idTrdDepeUserCreador]);
                    $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

                    $nombreArchivo = 'doc-'.$Radicados['numeroRadiRadicado'];
    
                    # Se almacena el archivo en la carpeta temporal
                    $rutaPlantilla = Yii::getAlias('@webroot')                            
                        . "/" . Yii::$app->params['bodegaRadicados']
                        . "/" . date("Y")                            
                        . "/" . $codigoGdTrdDependencia
                        . "/tmp" 
                    . "/" ;

                    // Verificar que la carpeta exista y crearla en caso de no exista
                    if (!file_exists($rutaPlantilla)) {
                        if (!FileHelper::createDirectory($rutaPlantilla, $mode = 0775, $recursive = true)) {
                            $rutaOk = false;
                        }
                    }

                    /*** Validar creación de la carpeta***/
                    if ($rutaOk == false) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                            //'data' => ['error' => ['No tiene permisos para ingresar al directorio']],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Validar creación de la carpeta***/
  
                    # Verificar si no existe mas documentos por ese radicado
                    $RadiDocumentosPrincipales = RadiDocumentosPrincipales::find()->where([
                        'idRadiRadicado' => $Radicados['idRadiRadicado'],
                        'imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();

                    # El nombre del archivo cargado queda en mayuscula.
                    $nombreValido = trim(strtoupper($modelPlantilla->nombreRadiDocumentoPrincipal));

                    # Se crea un nuevo registro del documento con extensión docx
                    $model = new RadiDocumentosPrincipales;

                    $model->idRadiRadicado                  = $idRadiRadicado;
                    $model->idUser                          = Yii::$app->user->identity->id;
                    $model->nombreRadiDocumentoPrincipal    = $nombreValido;
                    $model->rutaRadiDocumentoPrincipal      = $rutaPlantilla;
                    $model->extensionRadiDocumentoPrincipal = 'docx';
                    $model->imagenPrincipalRadiDocumento    = Yii::$app->params['statusTodoText']['Activo'];
                    $model->tamanoRadiDocumentoPrincipal    = $modelPlantilla->tamanoRadiDocumentoPrincipal;
                    $model->creacionRadiDocumentoPrincipal  = date("Y-m-d H:i:s");
                    $model->estadoRadiDocumentoPrincipal    = Yii::$app->params['statusDocsPrincipales']['Combinado'];

                    # Si se genera nuevo numero radicado será agregado como el numero asociado(respuesta).
                    if(isset($modelRadicadoGenerado) && $modelRadicadoGenerado != null && $modelRadicadoGenerado != ''){
                        $model->idRadiRespuesta = $modelRadicadoGenerado->idRadiRadicado;                        
                    } else { // Sino se agrega el id del radicado inicial
                        $model->idRadiRespuesta = $idRadiRadicado;
                    }

                    # Si ya hay un documento con imagen principal, los demas registros quedarán inactivos
                    if(isset($RadiDocumentosPrincipales)){
                        $model->imagenPrincipalRadiDocumento = Yii::$app->params['statusTodoText']['Inactivo'];
                    }

                    /** Procesar el errores de la fila */
                    if ($model->save()) {

                        $i = 1;
                        # almacenar numero de firmas
                        foreach($structure_word as $campo => $value){
                            if($campo == "firma#{$i}"){

                                $idradiDocPrincipal = $model->getPrimaryKey();

                                $firmasMultiples = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocPrincipal, 'firmaGdFirmaMultiple' => $campo])->one();

                                if(!isset($firmasMultiples)){
                                    $firmasMultiples = new GdFirmasMultiples();
                                }

                                $firmasMultiples->idradiDocPrincipal = $idradiDocPrincipal;
                                $firmasMultiples->firmaGdFirmaMultiple = $campo.", "."nombreFirma#{$i}";
                                $firmasMultiples->estadoGdFirmaMultiple = Yii::$app->params['statusDocsPrincipales']['Combinado'];
                                $firmasMultiples->creacionGdFirmaMultiple = date("Y-m-d");

                                if(!$firmasMultiples->save()){

                                    $transaction->rollBack();
                                    Yii::$app->response->statusCode = 200;
                                    $response = [
                                        'message' => Yii::t('app','errorValidacion'),
                                        'data' => $firmasMultiples->getErrors(),
                                        'status' => Yii::$app->params['statusErrorValidacion'],
                                    ];
                                    return HelperEncryptAes::encrypt($response, true);
                                }

                                
                                $i++;
                            }
                        }

                    }else{ 

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $model->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /******* Fin Procesar el errores de la fila  ******/

                    #Nombre del archivo
                    $nombreArchivo = $nombreArchivo.'-'.$model->idradiDocumentoPrincipal;

                    # Id del documento principal
                    $idDoc =  $model->idradiDocumentoPrincipal;
                    
                    # Nueva ubicación para la plantilla combinada
                    $rutaPdfCombinacion = Yii::getAlias('@webroot')                            
                        . "/" . Yii::$app->params['bodegaRadicados']
                        . "/" . date("Y")                            
                        . "/" . $codigoGdTrdDependencia
                        . "/" ;

                    /** Validar si se generó un nuevo radicado en el proceso */
                    if ($modelRadicadoGenerado != null) {
                        $numRadiBarcode = $modelRadicadoGenerado->numeroRadiRadicado;
                    } else {
                        $numRadiBarcode = $Radicados['numeroRadiRadicado'];
                    }

                    # Realiza el reemplazo de las etiquetas con la plantilla cargada.
                    $TemplateProcessor = $HelperPlantillas->TemplateProcessor($match_correspondence, $nombreArchivo, $rutaPlantilla, $rutaPdfCombinacion, false, null, $numRadiBarcode);
                    if($TemplateProcessor == false){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','fileNotProcessed')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Se actualiza la ruta con la combinación del archivo
                    $upModel = RadiDocumentosPrincipales::findOne(['idradiDocumentoPrincipal' => $idDoc]);
                    $upModel->rutaRadiDocumentoPrincipal   = $rutaPdfCombinacion.''.$nombreArchivo.'.docx';

                    /** Procesar el errores de la fila */
                    if (!$upModel->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $upModel->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    if ($isRadiTemporal) {
                        $msgRadiPrincipal = ', al consecutivo temporal principal ';
                    } else {
                        $msgRadiPrincipal = ', al radicado principal ';
                    }

                    $observacion = Yii::$app->params['eventosLogTextRadicado']['correspondenceMatch']. $nombreValido. " con id $model->idradiDocumentoPrincipal" . $msgRadiPrincipal .$Radicados['numeroRadiRadicado'];

                    if(isset($modelRadicadoGenerado) && $modelRadicadoGenerado != null & $modelRadicadoGenerado != ''){
                        $observacion .= ' y al consecutivo temporal asociado ' . $modelRadicadoGenerado->numeroRadiRadicado;
                    }

                    /**
                     * sguarin Cambios para diferenciar estado combinacion de correspondencia sin firmas
                    */

                    //Consulta del id del radiDocprincipal para hacer busqueda
                    $idradiDocPrincipal2 = $model->idradiDocumentoPrincipal;

                    //Contar si el documento tiene firmas
                    $busquedaFirmas = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocPrincipal2])->count();
                
                    //Si no tiene firmas hacer lo siguiente
                    if ($busquedaFirmas < 1 ) {
                        
                        //Cambio de estado y guardar
                        $model->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['CombinadoSinFirmas'];
                        $model->save();
                    }

                    /**
                     * sguarin Cambios para diferenciar estado combinacion de correspondencia sin firmas
                    */

                    //Consulta del id del radiDocprincipal para hacer busqueda
                    $idradiDocPrincipal2 = $model->idradiDocumentoPrincipal;
                    // return $idradiDocPrincipal;

                    //Contar si el documento tiene firmas
                    $busquedaFirmas = GdFirmasMultiples::find()
                    ->where(['idradiDocPrincipal' => $idradiDocPrincipal2])
                    ->count();
                
                    //Si no tiene firmas hacer lo siguiente
                    if ($busquedaFirmas < 1 ) {
                        
                        //Cambio de estado y guardar
                        $model->estadoRadiDocumentoPrincipal    = Yii::$app->params['statusDocsPrincipales']['CombinadoSinFirmas'];
                        $model->save();
                    }

                    /**
                     * sguarin fin de cambios
                    */

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $model->idRadiRadicado, //Id radicado
                        $idTransacion,
                        $observacion, //texto para almacenar en el evento
                        $model,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/ 

                }

                $implodeRadicados = implode(', ',$idRadiSeleccionados);
                $observacionFiling =  Yii::t('app', 'correspondenceMatch').' '.$nombreValido; 

                if(count($idRadiSeleccionados) > 1){
                    if ($isRadiTemporal) {
                        $observacionFiling = $observacionFiling.' '.Yii::t('app', 'MatchAllTmp').' '.$implodeRadicados . Yii::t('app', 'msgRememberSing');
                    } else {
                        $observacionFiling = $observacionFiling.' '.Yii::t('app', 'MatchAll').' '.$implodeRadicados;
                    }
                }else{
                    if ($isRadiTemporal) {
                        $observacionFiling = $observacionFiling.' '.Yii::t('app', 'MatchOneTmp').' '.$implodeRadicados . Yii::t('app', 'msgRememberSing');
                    } else {
                        $observacionFiling = $observacionFiling.' '.Yii::t('app', 'MatchOne').' '.$implodeRadicados;
                    }
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacionFiling,
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Log de Auditoria  ***/

                $transaction->commit();
                $response = [
                    'message' => $observacionFiling,
                    'data' => $dataResponse ?? false,
                    'status' => 200,
                ];

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
     * Firma física de documento
     * @param {$id} id del radicado principal
     */
    protected function signDocumentPhysical($id) {
        $plantilla = RadiDocumentosPrincipales::find()->where(['idradiDocumentoPrincipal' => $id])->one();

        // Documento principal
        $file_temp_docx = $plantilla->rutaRadiDocumentoPrincipal;
        // Ejecuta el constructor del helper de plantillas
        $helperPlantillas = new HelperPlantillas($file_temp_docx);
        // Estructura entregada por el helper de plantillas
        $structure_word = $helperPlantillas->convertToText();

        // Si hay un error al ejecutar el contructor del helper de plantillas
        // retorna un error de ejecución
        if(isset($structure_word['status'])) {
            if(!$structure_word['status']) {
                return [
                    'status' => false,
                    'dataFile' => '',
                    'depe' => '',
                    'dataError' => $structure_word['message'],
                    'fileName' => ''
                ];
            }
        }

        $transaction = Yii::$app->db->beginTransaction();
        $radiplantilla = RadiRadicados::find()->where(['idRadiRadicado' => $plantilla->idRadiRespuesta])->one();
        $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $radiplantilla->idTrdDepeUserCreador]);

        if ((boolean) $radiplantilla->isRadicado == false) {
            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            $generateNumberFiling = RadicadosController::generateNumberFiling($radiplantilla->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);

            $radiplantilla->isRadicado = 1;
            $radiplantilla->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
            $radiplantilla->creacionRadiRadicado = date("Y-m-d H:i:s");
            $radiplantilla->user_idTramitador = Yii::$app->user->identity->id;

            if(!$radiplantilla->save()){
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $radiplantilla->getErrors(),
                    'dataUpdate' => [],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        }

        // Ejecuta combinación de correspondencia para agregar el
        // número de correspondencia temporal
        $match_correspondence = $helperPlantillas->match_template($structure_word, $radiplantilla->idRadiRadicado);
        // Consulta de la información del radicado
        $radiplantilla = RadiRadicados::find()->where(['idRadiRadicado' => $radiplantilla->idRadiRadicado])->one();
        // Dependencia del usuario creador
        $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $radiplantilla->idTrdDepeUserCreador]);
        // Nombre del archivo
        $nombreArchivo = 'doc-' . $radiplantilla->numeroRadiRadicado .'-'. $id;
        // Ruta pdf combinación
        $rutaPdfCombinacion = Yii::getAlias('@webroot'). "/" . Yii::$app->params['bodegaRadicados']. "/" . date("Y"). "/" . $gdTrdDependencias->codigoGdTrdDependencia. "/";
        // Procesa el archivo para convertirlo en pdf
        $templateProcessor = $helperPlantillas->TemplateProcessor($match_correspondence, $nombreArchivo, $plantilla->rutaRadiDocumentoPrincipal, $rutaPdfCombinacion, false, null, $radiplantilla->numeroRadiRadicado, $id ,false, true);
        if (!$templateProcessor) {
            return [
                'status' => false,
                'dataFile' => '',
                'depe' => '',
                'dataError' => Yii::t('app','fileNotProcessed'),
                'fileName' => ''
            ];
        }
        // Guarda los datos de la plantilla
        $plantilla->extensionRadiDocumentoPrincipal = 'pdf';
        $plantilla->rutaRadiDocumentoPrincipal   = $rutaPdfCombinacion .''. $nombreArchivo.'.pdf';
        $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['procesoFirmaFisica'];
        if(!$plantilla->save()) {
            return [
                'status' => false,
                'dataFile' => '',
                'depe' => '',
                'dataError' => $plantilla->getErrors(),
                'fileName' => ''
            ];
        }

        $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signingProcess']);
        $idTransacion = $modelTransacion->idCgTransaccionRadicado;
        // Nombre documento
        $nombreValido = trim(strtoupper($plantilla->nombreRadiDocumentoPrincipal));
        $observacion = Yii::$app->params['eventosLogTextRadicado']['procesoFirmaFisica'].' '.$nombreValido;

        //Consulta la el log de radicado para encontrar el numero de radicado asociado
        $modelLogRadicados = RadiLogRadicados::find()
            ->where(['idRadiRadicado' => $plantilla->idRadiRadicado])
            ->andWhere([Yii::$app->params['like'], 'observacionRadiLogRadicado', 'Se realizó la combinación de correspondencia al documento'])
        ->all();

        foreach($modelLogRadicados as $log) {
            
            $observacionLog = explode(',', $log->observacionRadiLogRadicado);
            $observacionLog = array_reverse($observacionLog);

            $observacionDocumento = $observacionLog[1];
            $observacionRadicados = $observacionLog[0];

            $observacionDocumento = explode(' con id ', $observacionDocumento);
            $idDocumento =  $observacionDocumento[1];

            //Determina si radicado asociado está en la observaci+on
            if(strpos($observacionRadicados, 'y al radicado asociado') !== false){
            
                $observacionRadicados = explode(' ', $observacionRadicados);

                //Determina si es el id del documento del este registro del log
                if($idDocumento == $id){

                    //Limpiar de los string vacíos
                    unset($observacionRadicados[0]);
                    $elementoVacio = array_search('', $observacionRadicados);
                    unset($observacionRadicados[$elementoVacio]);

                    $numeroRadicado = end($observacionRadicados);

                    $observacion .= ' y el radicado asociado ' . $numeroRadicado;
                }
                
            }
        }

        /*** Log de Radicados ***/
        HelperLog::logAddFiling(
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
            $plantilla->idRadiRadicado, //Id radicado
            $idTransacion,
            $observacion, //texto para almacenar en el evento
            $radiplantilla,
            array() //No validar estos campos
        );
        /*** Fin Log de Radicados ***/

        $observacionFiling = Yii::$app->params['eventosLogTextRadicado']['procesoFirmaFisica'].' '.$nombreValido;

        /***    Log de Auditoria  ***/
        HelperLog::logAdd(
            false,
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->username, //username
            Yii::$app->controller->route, //Modulo
            $observacionFiling,
            [],
            [], //Data
            array() //No validar estos campos
        );
        /***    Log de Auditoria  ***/

        $dataFile = base64_encode(file_get_contents($rutaPdfCombinacion .''. $nombreArchivo.'.pdf'));
        $transaction->commit();

        return [
            'status' => true,
            'dataFile' => $dataFile,
            'depe' => $gdTrdDependencias->nombreGdTrdDependencia,
            'dataError' => '',
            'fileName' => $nombreValido .'' . '.'. $plantilla->extensionRadiDocumentoPrincipal
        ];
    }

    /**
     *  Transaccion  Firmar Documento
     * 
     * @param array   ButtonSelectedData 
     * 
     * @return array message success 
     */
    public function actionSignDocument(){

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');

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

                # Información del usuario logueado.
                $modelUser = UserValidate::findOne(['id' => Yii::$app->user->identity->id]);

                // Validacion de contraseña de usuario
                if ($modelUser->ldap) {

                    // Validacion usuario LDAP
                    $loginLdap = HelperLdap::loginLdap($modelUser->username, $request['moduleForm']['passUser']);

                    // Valida el usuario en el directorio activo
                    if ($loginLdap['status'] == true) {

                        $validPass = true;

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'Incorrect username or password ldap')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response);
                    }

                } else {
                    # Se valida que la contraseña del usuario en BD sea correcta
                    $validPass = $modelUser->validatePassword($request['moduleForm']['passUser']);
                }

                if(!$validPass) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                /**
                 * Inicia el proceso para la firma física
                 */
                if(Yii::$app->params['permissionSignedText']['physical'] === $request['selectedoption']) {
                    $idradiDocumentoPrincipal = $request['ButtonSelectedData'][0]['id'];

                    $resSignPhysical = $this->signDocumentPhysical($idradiDocumentoPrincipal);
                    if($resSignPhysical['status']) {
                        $userLogued = User::find()->select(['id', 'idGdTrdDependencia'])->where(['id' => Yii::$app->user->identity->id])->one();
                        $userDetallesLogued = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles', 'firma'])->where(['idUser' => $userLogued['id']])->one();

                        $response = [
                            'message' => Yii::t('app', 'sendEventProcesoFirmaFisica', [
                                'numRadi' => $idradiDocumentoPrincipal,
                                'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                                'depe' => $resSignPhysical['depe'] ?? Yii::t('app', 'depNotFound')
                            ]),
                            'data' => [],
                            'dataFile' => $resSignPhysical['dataFile'],
                            'fileName' => $resSignPhysical['fileName'],
                            'status' => 200
                        ];
                    } else {
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [$resSignPhysical['dataError']]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                            'id' => $idradiDocumentoPrincipal
                        ];
                    }

                    Yii::$app->response->statusCode = 200;
                    return HelperEncryptAes::encrypt($response, true);
                }
                /**
                 * Fin del proceso para la firma física
                 */

                $userLogued = User::find()->select(['id', 'idGdTrdDependencia'])->where(['id' => Yii::$app->user->identity->id])->one();
                $userDetallesLogued = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles', 'firma'])->where(['idUser' => $userLogued['id']])->one();

                if(Yii::$app->params['permissionSignedText']['mechanical'] == $request['selectedoption']) {

                    $firmaDocumento = $userDetallesLogued['firma'];

                    if(!isset($firmaDocumento) || !file_exists($firmaDocumento)){
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notHaveSignature')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                $transaction = Yii::$app->db->beginTransaction();
                # Consulta la accion de la transaccion de estado rechazado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signDocument']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;
                $idRadiSeleccionados = [];

                foreach($request['ButtonSelectedData'] as $key => $documentoPrincipal){
                    
                    /** Consultar datos del Documento principal */
                    $idradiDocumentoPrincipal = $documentoPrincipal['id'];

                    # validar que el mismo usuario no firme 2 veces
                    $firmado = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal, 'userGdFirmaMultiple' => Yii::$app->user->identity->id])->one();

                    if(isset($firmado)){

                        $userDetalles = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','signedDocument', [
                                'doc' => $documentoPrincipal['nombreRadiDocumentoPrincipal'],
                                'user' => $userDetalles->nombreUserDetalles.' '.$userDetalles->apellidoUserDetalles
                            ])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }


                    $plantilla = RadiDocumentosPrincipales::find()->where([
                        'idradiDocumentoPrincipal' => $idradiDocumentoPrincipal,
                    ])->one();
                    /** Fin Generar radicado de plantilla actual */
                    $radiplantilla = RadiRadicados::find()->where(['idRadiRadicado' => $plantilla->idRadiRespuesta])->one();

                    $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $radiplantilla->idTrdDepeUserCreador]);

                    if ((boolean) $radiplantilla->isRadicado == false) {
                        
                        $countFirmasFaltantes = GdFirmasMultiples::find()
                            ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                            ->andWhere(['<>', 'estadoGdFirmaMultiple', Yii::$app->params['statusDocsPrincipales']['Firmado']])
                            ->count();

                        if ($countFirmasFaltantes == 1 || $countFirmasFaltantes == 0) {
                            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
                            $generateNumberFiling = RadicadosController::generateNumberFiling($radiplantilla->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);

                            $radiplantilla->isRadicado = 1;
                            $radiplantilla->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
                            $radiplantilla->creacionRadiRadicado = date("Y-m-d H:i:s");
                            $radiplantilla->user_idTramitador = Yii::$app->user->identity->id;
                            if(!$radiplantilla->save()){
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $radiplantilla->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                        }
                    }
                    /** Fin Generar radicado de plantilla actual */

                    if ($plantilla == null) {
                        $transaction->rollBack();
                        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
                    }
                    /** Fin Consultar Datos del Documento principal */

                    $idRadiRadicado = $plantilla->idRadiRadicado;

                    # Se agrupa todos los radicados seleccionados para notificacion
                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();
                    $idRadiSeleccionados[] = $Radicados['numeroRadiRadicado'];
                    
                    /** Validar si el documento ya está firmado */
                    if ($plantilla->estadoRadiDocumentoPrincipal ==  Yii::$app->params['statusDocsPrincipales']['Firmado']) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','errorDocumentFirmado')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    /** Validar si es una plantilla con cominación de correspondencia o está en proceso de firma */
                    if ( $plantilla->estadoRadiDocumentoPrincipal != Yii::$app->params['statusDocsPrincipales']['Combinado'] && $plantilla->estadoRadiDocumentoPrincipal !=  Yii::$app->params['statusDocsPrincipales']['procesandoFirma']) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notfoundCorrespondence',['numRadi' => $Radicados['numeroRadiRadicado']])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    /** Validar si es el usuario tramitador  */
                    if($Radicados['user_idTramitador'] != Yii::$app->user->identity->id){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notfoundTramitador',['numRadi' => $Radicados['numeroRadiRadicado']])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # remplaza la ruta de la combinacion de corespondencia (pdf) por la copia temporal en (docx)
                   // $file_temp_docx = str_replace("/doc-","/tmp/doc-",$plantilla->rutaRadiDocumentoPrincipal);
                    $file_temp_docx = $plantilla->rutaRadiDocumentoPrincipal; //Cambio
                    //$file_temp_docx = str_replace(".pdf",".docx",$file_temp_docx);

                    $HelperPlantillas = new HelperPlantillas($file_temp_docx);
                    $structure_word = $HelperPlantillas->convertToText();

                    if(isset($structure_word['status'])){
                        if(!$structure_word['status']){
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [$structure_word['message']]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }   

                    # Obtiene el codigo de dependencia para consulta la ruta del archivo
                    $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $Radicados->idTrdDepeUserCreador]);
                    $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

                    $numRadiTmp = $Radicados->numeroRadiRadicado;

                    /** Inicio Generar Número de radicado al padre */
                    if ((boolean) $Radicados->isRadicado == false) {

                        $countFirmasFaltantes = GdFirmasMultiples::find()
                            ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                            ->andWhere(['<>', 'estadoGdFirmaMultiple', Yii::$app->params['statusDocsPrincipales']['Firmado']])
                            ->count();

                        if ($countFirmasFaltantes == 1 || $countFirmasFaltantes == 0) {

                            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
                            $generateNumberFiling = RadicadosController::generateNumberFiling($Radicados->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);

                            $Radicados->isRadicado = 1;
                            $Radicados->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
                            $Radicados->creacionRadiRadicado = date("Y-m-d H:i:s");
                            $Radicados->user_idTramitador = Yii::$app->user->identity->id;
                            if(!$Radicados->save()){
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $Radicados->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }

                    }
                    /** Fin Generar Número de radicado al padre */

                    $rutaPlantilla = Yii::getAlias('@webroot'). "/" . Yii::$app->params['bodegaRadicados']. "/" . date("Y"). "/" . $codigoGdTrdDependencia. "/tmp" . "/";

                    # Validar Tipo de Firma QR o MANUAL
                    if(Yii::$app->params['permissionSignedText']['qr'] == $request['selectedoption']){
                        # if($CgFirmas['nombreCgFirmaDoc'] == Yii::$app->params['cgTiposFirmas']['QR']){

                        $model = new GdFirmasQr();

                        $model->idRadiRadicado =  $idRadiRadicado;
                        $model->idUser = Yii::$app->user->identity->id;
                        $model->idDocumento = $idradiDocumentoPrincipal;
                        $model->estadoGdFirmasQr = Yii::$app->params['statusTodoText']['Activo'];
                        $model->creacionGdFirmasQr = date("Y-m-d H:i:s");

                        if(!$model->save()){
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $model->getErrors(),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $rutaElectronica = Yii::$app->params['ipServer'] . 'qrview/' . str_replace(array('/', '+'), array('_', '-'), base64_encode(HelperEncryptAes::encrypt($model->idGdFirmasQr)['encrypted']));
                        $QRCode = (new QrCode($rutaElectronica))->setSize(250)->setMargin(5);
                        $firmaDocumento = $rutaPlantilla.''.Yii::$app->params['nameFileQR'];
                        $QRCode->writeFile($firmaDocumento);

                    }else{
                        $rutaDocumento = $plantilla->rutaRadiDocumentoPrincipal;
                        $rutaDocumento = explode('/', $rutaDocumento);
                        $nombreDocumento = end($rutaDocumento);

                        $rutaElectronica = Url::base(true)               
                        . "/" . Yii::$app->params['bodegaRadicados']
                        . "/" . date("Y")                            
                        . "/" . $codigoGdTrdDependencia
                        . "/" . $nombreDocumento;
                        $firmaDocumento = $userDetallesLogued['firma'];

                        
                    }

                    $nombreArchivo = 'doc-'.$numRadiTmp.'-'.$plantilla['idradiDocumentoPrincipal'];
                    // $nombreArchivo = 'doc-'.$Radicados['numeroRadiRadicado'].'-'.$plantilla['idradiDocumentoPrincipal'];
                    $rutaPdfCombinacion = Yii::getAlias('@webroot'). "/" . Yii::$app->params['bodegaRadicados']. "/" . date("Y"). "/" . $codigoGdTrdDependencia. "/";

                    /** Validar si se generó un nuevo radicado en el proceso */
                    if ($radiplantilla != null) {
                        $numRadiBarcode = $radiplantilla->numeroRadiRadicado;
                    } else {
                        $numRadiBarcode = $Radicados['numeroRadiRadicado'];
                    }

                    $TemplateProcessor = $HelperPlantillas->TemplateProcessor($structure_word, $nombreArchivo, $rutaPlantilla, $rutaPdfCombinacion, $firmaDocumento, $rutaElectronica, $numRadiBarcode, $idradiDocumentoPrincipal, false);

                    # Nombre del archivo
                    $nombreValido = trim(strtoupper($plantilla->nombreRadiDocumentoPrincipal));
                   
                    $firmasMultiples = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])->all();

                    $statusCombinado = false;

                    foreach ($firmasMultiples as $key => $value) { 
                        if($value->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){
                            $statusCombinado = true; break;
                        }
                    }

                    /**  Se actualiza la plantilla a pdf y estado firmado o procesando firma */
                    if(!$statusCombinado){
                        $plantilla->extensionRadiDocumentoPrincipal = 'pdf';
                        $plantilla->rutaRadiDocumentoPrincipal   = $rutaPdfCombinacion.''.$nombreArchivo.'.pdf';
                        $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['Firmado'];
    
                    } else {
                        $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['procesandoFirma'];
                    }
                    if(!$plantilla->save()){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $Radicados->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /**  Fin Se actualiza la plantilla a pdf y estado firmado o procesando firma */

                    $observacion = Yii::$app->params['eventosLogTextRadicado']['firmaRadicados'].' '.$nombreValido.', y se le asignó el radicado principal '.$Radicados['numeroRadiRadicado']; 

                    //Consulta la el log de radicado para encontrar el numero de radicado asociado
                    $modelLogRadicados = RadiLogRadicados::find()
                        ->where(['idRadiRadicado' => $plantilla->idRadiRadicado])
                        ->andWhere([Yii::$app->params['like'], 'observacionRadiLogRadicado', 'Se realizó la combinación de correspondencia al documento'])
                    ->all();

                    foreach($modelLogRadicados as $log){
                        
                        $observacionLog = explode(',', $log->observacionRadiLogRadicado);
                        $observacionLog = array_reverse($observacionLog);                       

                        $observacionDocumento = $observacionLog[1];
                        $observacionRadicados = $observacionLog[0];

                        $observacionDocumento = explode(' con id ', $observacionDocumento);
                        $idDocumento =  $observacionDocumento[1];                        

                        //Determina si radicado asociado está en la observaci+on
                        if(strpos($observacionRadicados, 'y al radicado asociado') !== false){
                        
                            $observacionRadicados = explode(' ', $observacionRadicados);                          

                            //Determina si es el id del documento del este registro del log
                            if($idDocumento == $documentoPrincipal['id']){

                                //Limpiar de los string vacíos
                                unset($observacionRadicados[0]);
                                $elementoVacio = array_search('', $observacionRadicados);
                                unset($observacionRadicados[$elementoVacio]);                        

                                $numeroRadicado = end($observacionRadicados);                               

                                $observacion .= ' y el radicado asociado ' . $numeroRadicado;
                                                              
                            }
                            
                        }
                    }

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $Radicados->idRadiRadicado, //Id radicado
                        $idTransacion,
                        $observacion, //texto para almacenar en el evento
                        $Radicados,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/ 

                    // $transaction->commit();

                    $tablaRadiInformados = RadiInformados::tableName() . ' AS RI';
                    $tablaUser = User::tableName() . ' AS U';

                    $radiInformados = (new \yii\db\Query())
                        ->select(['U.email'])
                        ->from($tablaRadiInformados);
                        // ->innerJoin($tablaUser, '`u`.`id` = `ri`.`idUser`')
                        $radiInformados = HelperQueryDb::getQuery('innerJoinAlias', $radiInformados, $tablaUser, ['U' => 'id', 'RI' => 'idUser']);
                        $radiInformados = $radiInformados->where(['RI.idRadiRadicado' => $idRadiRadicado])
                    ->all();

                    // Envia la notificación de correo electronico a los usuarios informados
                    $mailUsersInformados = [];
                    foreach($radiInformados as $notificar){
                        $mailUsersInformados[] = $notificar['email'];
                    }

                    // Se realiza el envío de correo de esta manera porque el contenido es el mismo para todos los usuarios a informar
                    if (count($mailUsersInformados) > 0) {

                        $headMailText = Yii::t('app', 'headMailTextFirm', [
                            'numRadi' =>  $Radicados['numeroRadiRadicado'],
                        ]);

                        $gdTrdDependencias = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $userLogued['idGdTrdDependencia']])->one();

                        $textBody  = Yii::t('app', 'mailEventRadicadoFirmado', [
                            'numRadi' => $Radicados['numeroRadiRadicado'],
                            'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                            'depe' => $gdTrdDependencias['nombreGdTrdDependencia'] ??  Yii::t('app', 'depNotFound')
                        ]);

                        $bodyMail = 'radicacion-html';
                        $setSubject = Yii::t('app', 'signedFiling',[ 'numRadi' => $Radicados['numeroRadiRadicado']]);
                        $statusCorreo = CorreoController::differentFilings($mailUsersInformados, $headMailText, $textBody, $bodyMail, $idRadiRadicado, $setSubject, true);

                    }

                    #Inicio de índice electronico
                    #Si el radicado está incluido a un expediente, se agrega el documento recien creado al indice
                    $modelExpedienteInclusion = GdExpedientesInclusion::find()
                        ->where(['idRadiRadicado' => $idRadiRadicado])
                    ->one();

                    if($modelExpedienteInclusion != null){

                        $xml = HelperIndiceElectronico::getXmlParams($modelExpedienteInclusion->gdExpediente);
                        //Se agrega el documento solo cuando se realiza combinación
                        $resultado = HelperIndiceElectronico::addDocumentToIndex($plantilla, $modelExpedienteInclusion, $xml, $Radicados);

                        if(!$resultado['ok']){
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $resultado['data']['response']['data'],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                    #Fin de índice electronico

                }

                $implodeRadicados = implode(', ',$idRadiSeleccionados);
                $observacionFiling =  Yii::$app->params['eventosLogTextRadicado']['firmaRadicados'].' '.$nombreValido; 

                if(count($idRadiSeleccionados) > 1){
                    $observacionFiling = $observacionFiling.' '.Yii::$app->params['eventosLogTextRadicado']['MatchAll'].' '.$implodeRadicados;
                }else{
                    $observacionFiling = $observacionFiling.' '.Yii::$app->params['eventosLogTextRadicado']['MatchOne'].' '.$implodeRadicados;
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacionFiling,
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Log de Auditoria  ***/

                $transaction->commit();

                $response = [
                    'message' => Yii::t('app', 'sendEventRadicadoFirmado', [
                        'numRadi' => $Radicados['numeroRadiRadicado'],
                        'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                        'depe' => $gdTrdDependencias['nombreGdTrdDependencia'] ??  Yii::t('app', 'depNotFound')
                    ]),
                    'data' => $dataResponse ?? [],
                    'status' => 200,
                ];

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
     *  Transaccion  para pasar un documento combinado sin firmas a firmado, asignación de radicado y conversión a pdf
     * 
     * @param array   ButtonSelectedData 
     * 
     * @return array message success 
     */
    public function actionSignDocumentCombiSinFirmas(){
            
        $jsonSend = Yii::$app->request->post('jsonSend');

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

            # Información del usuario logueado.
            $modelUser = UserValidate::findOne(['id' => Yii::$app->user->identity->id]);

            $userLogued = User::find()->select(['id', 'idGdTrdDependencia'])->where(['id' => Yii::$app->user->identity->id])->one();
            $userDetallesLogued = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles', 'firma'])->where(['idUser' => $userLogued['id']])->one();

            $transaction = Yii::$app->db->beginTransaction();
            # Consulta la accion de la transaccion de estado rechazado para obtener su id
            $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signDocument']);
            $idTransacion = $modelTransacion->idCgTransaccionRadicado;
            $idRadiSeleccionados = [];

            foreach($request['ButtonSelectedData'] as $key => $documentoPrincipal){
                
                /** Consultar datos del Documento principal */
                $idradiDocumentoPrincipal = $documentoPrincipal['id'];

                $plantilla = RadiDocumentosPrincipales::find()->where([
                    'idradiDocumentoPrincipal' => $idradiDocumentoPrincipal,
                ])->one();

                if ($plantilla == null) {
                    $transaction->rollBack();
                    throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
                }

                /** Fin Generar radicado de plantilla actual */
                $radiplantilla = RadiRadicados::find()->where(['idRadiRadicado' => $plantilla->idRadiRespuesta])->one();
                $idRadiRadicado = $plantilla->idRadiRadicado;

                # Se agrupa todos los radicados seleccionados para notificacion
                $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();
                $idRadiSeleccionados[] = $Radicados['numeroRadiRadicado'];
                
                /** Validar si el documento ya está firmado */
                if ($plantilla->estadoRadiDocumentoPrincipal ==  Yii::$app->params['statusDocsPrincipales']['Firmado']) {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app','errorDocumentFirmado')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                /** Validar si es el usuario tramitador  */
                if($Radicados['user_idTramitador'] != Yii::$app->user->identity->id){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app','notfoundTramitador',['numRadi' => $Radicados['numeroRadiRadicado']])]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # remplaza la ruta de la combinacion de corespondencia (pdf) por la copia temporal en (docx)
                $file_temp_docx = $plantilla->rutaRadiDocumentoPrincipal; //Cambio

                $HelperPlantillas = new HelperPlantillas($file_temp_docx);
                $structure_word = $HelperPlantillas->convertToText();

                if(isset($structure_word['status'])){
                    if(!$structure_word['status']){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [$structure_word['message']]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }

                # Obtiene el codigo de dependencia para consulta la ruta del archivo
                $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $Radicados->idTrdDepeUserCreador]);
                $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

                $numRadiTmp = $Radicados->numeroRadiRadicado;

                $rutaPlantilla = Yii::getAlias('@webroot'). "/" . Yii::$app->params['bodegaRadicados']. "/" . date("Y"). "/" . $codigoGdTrdDependencia. "/tmp" . "/";

                $rutaDocumento = $plantilla->rutaRadiDocumentoPrincipal;
                $rutaDocumento = explode('/', $rutaDocumento);
                $nombreDocumento = end($rutaDocumento);

                $rutaElectronica = Url::base(true)
                . "/" . Yii::$app->params['bodegaRadicados']
                . "/" . date("Y")
                . "/" . $codigoGdTrdDependencia
                . "/" . $nombreDocumento;
                $firmaDocumento = $userDetallesLogued['firma'];

                $nombreArchivo = 'doc-'.$numRadiTmp.'-'.$plantilla['idradiDocumentoPrincipal'];
                $rutaPdfCombinacion = Yii::getAlias('@webroot'). "/" . Yii::$app->params['bodegaRadicados']. "/" . date("Y"). "/" . $codigoGdTrdDependencia. "/";

                /** Validar si se generó un nuevo radicado en el proceso */
                //sguarin aca es necesario el numRadiBarCode
                if ($radiplantilla != null) {
                    $numRadiBarcode = $radiplantilla->numeroRadiRadicado;
                } else {
                    $numRadiBarcode = $Radicados['numeroRadiRadicado'];
                }

                $TemplateProcessor = $HelperPlantillas->TemplateProcessorFirmaDigital($structure_word, $nombreArchivo, $rutaPlantilla, $rutaPdfCombinacion);
                if($TemplateProcessor === false) {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app','fileNotProcessed')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Nombre del archivo
                $nombreValido = trim(strtoupper($plantilla->nombreRadiDocumentoPrincipal));
                
                $firmasMultiples = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])->all();

                $statusCombinado = false;

                foreach ($firmasMultiples as $key => $value) { 
                    if($value->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){
                        $statusCombinado = true; break;
                    }
                }

                /**  Se actualiza la plantilla a pdf y estado firmado o procesando firma */
                if(!$statusCombinado){
                    $plantilla->extensionRadiDocumentoPrincipal = 'pdf';
                    $plantilla->rutaRadiDocumentoPrincipal   = $rutaPdfCombinacion.''.$nombreArchivo.'.pdf';
                    $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['enProcesoFirmaDigital'];

                } else {
                    $plantilla->estadoRadiDocumentoPrincipal = Yii::$app->params['statusDocsPrincipales']['procesandoFirma'];
                }
                if(!$plantilla->save()){
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $Radicados->getErrors(),
                        'dataUpdate' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /**  Fin Se actualiza la plantilla a pdf y estado firmado o procesando firma */

                //Consulta la el log de radicado para encontrar el numero de radicado asociado
                $modelLogRadicados = RadiLogRadicados::find()
                    ->where(['idRadiRadicado' => $plantilla->idRadiRadicado])
                    ->andWhere([Yii::$app->params['like'], 'observacionRadiLogRadicado', 'Se realizó la combinación de correspondencia al documento'])
                    ->all();

                foreach($modelLogRadicados as $log){
                    
                    $observacionLog = explode(',', $log->observacionRadiLogRadicado);
                    $observacionLog = array_reverse($observacionLog);

                    $observacionDocumento = $observacionLog[1];
                    $observacionRadicados = $observacionLog[0];

                    $observacionDocumento = explode(' con id ', $observacionDocumento);
                    $idDocumento =  $observacionDocumento[1];

                    //Determina si radicado asociado está en la observaci+on
                    if(strpos($observacionRadicados, 'y al radicado asociado') !== false){
                    
                        $observacionRadicados = explode(' ', $observacionRadicados);
                        $observacion = '';

                        //Determina si es el id del documento del este registro del log
                        if($idDocumento == $documentoPrincipal['id']){

                            //Limpiar de los string vacíos
                            unset($observacionRadicados[0]);
                            $elementoVacio = array_search('', $observacionRadicados);
                            unset($observacionRadicados[$elementoVacio]);

                            $numeroRadicado = end($observacionRadicados);

                            $observacion .= ' y el radicado asociado ' . $numeroRadicado;
                        }
                        
                    }
                }

                $tablaRadiInformados = RadiInformados::tableName() . ' AS RI';
                $tablaUser = User::tableName() . ' AS U';

                $radiInformados = (new \yii\db\Query())
                    ->select(['U.email'])
                    ->from($tablaRadiInformados);
                    // ->innerJoin($tablaUser, '`u`.`id` = `ri`.`idUser`')
                    $radiInformados = HelperQueryDb::getQuery('innerJoinAlias', $radiInformados, $tablaUser, ['U' => 'id', 'RI' => 'idUser']);
                    $radiInformados = $radiInformados->where(['RI.idRadiRadicado' => $idRadiRadicado])
                    ->all();

                // Envia la notificación de correo electronico a los usuarios informados
                $mailUsersInformados = [];
                foreach($radiInformados as $notificar){
                    $mailUsersInformados[] = $notificar['email'];
                }

                // Se realiza el envío de correo de esta manera porque el contenido es el mismo para todos los usuarios a informar
                if (count($mailUsersInformados) > 0) {

                    $headMailText = Yii::t('app', 'headMailTextFirm', [
                        'numRadi' =>  $Radicados['numeroRadiRadicado'],
                    ]);

                    $gdTrdDependencias = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $userLogued['idGdTrdDependencia']])->one();

                    $textBody  = Yii::t('app', 'mailEventRadicadoFirmado', [
                        'numRadi' => $Radicados['numeroRadiRadicado'],
                        'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                        'depe' => $gdTrdDependencias['nombreGdTrdDependencia'] ??  Yii::t('app', 'depNotFound')
                    ]);

                    $bodyMail = 'radicacion-html';
                    $setSubject = Yii::t('app', 'signedFiling',[ 'numRadi' => $Radicados['numeroRadiRadicado']]);
                    $statusCorreo = CorreoController::differentFilings($mailUsersInformados, $headMailText, $textBody, $bodyMail, $idRadiRadicado, $setSubject, true);
                }

                #Inicio de índice electronico
                #Si el radicado está incluido a un expediente, se agrega el documento recien creado al indice
                $modelExpedienteInclusion = GdExpedientesInclusion::find()
                    ->where(['idRadiRadicado' => $idRadiRadicado])
                    ->one();

                if($modelExpedienteInclusion != null){

                    $xml = HelperIndiceElectronico::getXmlParams($modelExpedienteInclusion->gdExpediente);
                    //Se agrega el documento solo cuando se realiza combinación
                    $resultado = HelperIndiceElectronico::addDocumentToIndex($plantilla, $modelExpedienteInclusion, $xml, $Radicados);

                    if(!$resultado['ok']){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $resultado['data']['response']['data'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                }
                #Fin de índice electroctino
            }

            $implodeRadicados = implode(', ',$idRadiSeleccionados);
            $observacionFiling =  Yii::$app->params['eventosLogTextRadicado']['firmaRadicados'].' '.$nombreValido; 

            if(count($idRadiSeleccionados) > 1){
                $observacionFiling = $observacionFiling.' '.Yii::$app->params['eventosLogTextRadicado']['MatchAll'].' '.$implodeRadicados;
            }else{
                $observacionFiling = $observacionFiling.' '.Yii::$app->params['eventosLogTextRadicado']['MatchOne'].' '.$implodeRadicados;
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                $observacionFiling,
                [],
                [], //Data
                array() //No validar estos campos
            );
            /***    Log de Auditoria  ***/

            $transaction->commit();

            $response = [
                'message' => Yii::t('app', 'sendEventRadicadoFirmado', [
                    'numRadi' => $Radicados['numeroRadiRadicado'],
                    'user' => $userDetallesLogued['nombreUserDetalles'].' '.$userDetallesLogued['apellidoUserDetalles'],
                    'depe' => $gdTrdDependencias['nombreGdTrdDependencia'] ??  Yii::t('app', 'depNotFound')
                ]),
                'data' => $dataResponse ?? [],
                'status' => 200,
            ];

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

    /**
     *  Transaccion Cargar Firmar Digital Documento
     */
    public function actionDigitalSignDocument($request){

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

            # Información del usuario logueado.
            $modelUser = UserValidate::findOne(['id' => Yii::$app->user->identity->id]);

            // Validacion de contraseña de usuario
            if ($modelUser->ldap) {

                // Validacion usuario LDAP
                $loginLdap = HelperLdap::loginLdap($modelUser->username, $request['dataForm']['passUser']);

                // Valida el usuario en el directorio activo
                if ($loginLdap['status'] == true) {

                    $validPass = true;

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'Incorrect username or password ldap')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }

            } else {
                # Se valida que la contraseña del usuario en BD sea correcta
                $validPass = $modelUser->validatePassword($request['dataForm']['passUser']);
            }

            if(!$validPass) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

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

            //Data Usuario logueado
            $idUser = Yii::$app->user->identity->id;
            $idDependencia = self::findUserModel($idUser)->gdTrdDependencia->idGdTrdDependencia;

            $ButtonSelectedData0 = $request['ButtonSelectedData'][0];
            $id = $ButtonSelectedData0['id'];

            $modelRadiDocumentosPrincipales = RadiDocumentosPrincipales::find()->where(['idradiDocumentoPrincipal' => $id])->one();

            $rutaFile = $modelRadiDocumentosPrincipales->rutaRadiDocumentoPrincipal;
            $epochDocPrincipalArchivo = $modelRadiDocumentosPrincipales->fechaEpochCreacionArchivo;
            $paginasDocPrincipal = $modelRadiDocumentosPrincipales->paginas;
            // $hashDocPrincipal = $modelRadiDocumentosPrincipales->hash;

            //Variables para el log de auditoria en caso de cargarse una archivo inválido
            $nombreValido4 = trim(strtoupper($modelRadiDocumentosPrincipales->nombreRadiDocumentoPrincipal));  
            $idRadiRadicado4 = $modelRadiDocumentosPrincipales->idRadiRadicado;
            $Radicados4 = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado4])->one();
            $rutaModulo4 = 'version1/configuracionApp/cg-firmas-docs/digital';
            $observacionlogAuditoria4 = 'Se cargo un archivo inválido para el documento principal ' .$nombreValido4.' con el radicado No: '.$Radicados4['numeroRadiRadicado'].' que se encuentra en proceso de firmado digital.';

            $rutaOk = true;
            
            #Nombre del documento temporal
            $nombreArchivo = $fileUpload->name;
            // $division = explode('/',$nombreArchivo);
            // $nombreTemporal = $division[2];

            $pathDescargas = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDownloads'].'/' ;

            $filePathTemporal = $pathDescargas . $nombreArchivo;

            $uploadExecuteTemporal = $fileUpload->saveAs($filePathTemporal, false);
                // Verificar que el archivo exista
            if (!file_exists($rutaFile)) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            //Aca es donde se válida el documento que cargo el usuario con las firmas digitales
            if ($uploadExecuteTemporal) {

                #fecha
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

                //extraigo informacion
                $infofirma= pdfsig_php($filePathTemporal);

                //Lo convierto json en un arreglo
                $json = json_decode($infofirma);

                $existeFirmaOrfeo=false;

                $mismoDocumentoDescarga = false;
                //echo "Nro de firmas: ".count($json)."\n";

                //Validación documento sin firmas 
                if(count($json)<0){

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        $rutaModulo4, //Modulo
                        $observacionlogAuditoria4, //Mensaje 
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorFirmaDigital'),
                        'data' => ['error' => [Yii::t('app', 'errorFirmaDigital')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                //Extrae la información del archivo pdf 
                $pdf = new PDFInfo($filePathTemporal);
                // $pdf = new PDFInfo("/home/sguarin/Descargas/MEMORANDO_RECOMENDACIÓN_DE_CONTRATACION_IMPRESORAS_ZEBRA_2021_3_.pdf");

                //Fecha de creación del archivo
                $fechaEpochCreacionArchivo = strtotime($pdf->creationDate);

                //Número de páginas del archivo
                $numeroPaginasArchivo =  (int) $pdf->pages;

                // return $fechaEpochCreacionArchivo;

                //Validar que tenga la firma de orfeo
                if($epochDocPrincipalArchivo == $fechaEpochCreacionArchivo && $paginasDocPrincipal==$numeroPaginasArchivo){
                    $mismoDocumentoDescarga = true;
                }

                //Validar que todas las firmas sean validas, antes de proceder a guardarlas
                for ($i = 0; $i < count($json); $i++) {

                    $certificado=$json[$i]->certificados[0]->certificado;
                    //    echo "Certificado: ".$certificado;
                    $infocert=openssl_x509_parse($certificado);

                    // echo "Estado: ".$json[$i]->estadoFirma."\n";
                    // echo "Empresa: ".$infocert['subject']['O']."\n";
                    // echo "Firmante: ".$infocert['subject']['CN']."\n";
                    // // echo "Cargo: ".$infocert['subject']['title']."\n";
                    // echo "El correo del firmante: ".$infocert['subject']['emailAddress']."\n";
                    // echo "";
                    // // echo "Direccion del emisor es: ".$infocert['issuer']['street']."\n";
                    // // echo "Servicios OCSP: ".$infocert['extensions']['authorityInfoAccess']."\n";
                    // // echo "Servicios CRL: ".$infocert['extensions']['crlDistributionPoints']."\n";
                    // echo "";
                    // echo "Fecha firmado:".format($json[$i]->fechaFirma)."\n";
                    // echo "Fecha vence:".date("Y-m-d H:i:s",$infocert['validTo_time_t'])."\n";
                    // echo "Fecha EPOCH: ".strtotime(format($json[$i]->fechaFirma))."\n";
                    // echo "HASH: ".$json[$i]->hash;

                    $estado=$json[$i]->estadoFirma;
                    $empresa=$infocert['subject']['O'];
                    $firmante=$infocert['subject']['CN'];
                    $hash = $json[$i]->hash;
                    $fechaEpochCreacion = strtotime(format($json[$i]->fechaFirma));

                    // echo "Estado: ".$json[$i]->estadoFirma."\n";

                    if($estado!='valido'){

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            $rutaModulo4, //Modulo
                            $observacionlogAuditoria4, //Mensaje 
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorFirmaDigitalValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorFirmaDigitalValidacion')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    // //Validar que tenga la firma de orfeo
                    // if($epochDocPrincipal == $fechaEpochCreacion && $hashDocPrincipal==$hash){
                    //     $mismoDocumentoDescarga = true;
                    // }
                }

                if($mismoDocumentoDescarga && count($json)==0){

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        $rutaModulo4, //Modulo
                        $observacionlogAuditoria4, //Mensaje 
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorFirmasDigitalesAdicionales'),
                        'data' => ['error' => [Yii::t('app', 'errorFirmasDigitalesAdicionales')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                //Validación si se cargo el mismo documento que se decargo 
                if($mismoDocumentoDescarga==false){
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        $rutaModulo4, //Modulo
                        $observacionlogAuditoria4, //Mensaje 
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorFirmaDigital'),
                        'data' => ['error' => [Yii::t('app', 'errorFirmaDigital')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
            }

            // Actualizar Archivo de radiDocumentosPrincipales si se pasan todas las validaciones del archivo cargado por el usuario
            $uploadExecute = $fileUpload->saveAs($rutaFile, false);

            #Metodo para extraer nuevamente la información del archivo ya cargado y almacenar la información de las firmas en la base de datos
            if ($uploadExecute) {

                //extraigo informacion
                $infofirma2= pdfsig_php($rutaFile);

                //Lo convierto json en un arreglo
                $json2 = json_decode($infofirma2);

                $existeFirmaOrfeo=false;

                for ($i = 0; $i < count($json2); $i++) {

                    $certificado=$json2[$i]->certificados[0]->certificado;
                    //    echo "Certificado: ".$certificado;
                    $infocert=openssl_x509_parse($certificado);
                    
                    //sguarin información que recibo del metodo
                    //    var_dump($infocert) ;
                    // echo "Estado: ".$json2[$i]->estadoFirma."\n";
                    // echo "Empresa: ".$infocert['subject']['O']."\n";
                    // echo "Firmante: ".$infocert['subject']['CN']."\n";
                    // echo "Cargo: ".$infocert['subject']['title']."\n";
                    // echo "El correo del firmante: ".$infocert['subject']['emailAddress']."\n";
                    // echo "";
                    // echo "Direccion del emisor es: ".$infocert['issuer']['street']."\n";
                    // echo "Servicios OCSP: ".$infocert['extensions']['authorityInfoAccess']."\n";
                    // echo "Servicios CRL: ".$infocert['extensions']['crlDistributionPoints']."\n";
                    // echo "";
                    // echo "Fecha firmado:".format($json2[$i]->fechaFirma)."\n";
                    // echo "Fecha vence:".date("Y-m-d H:i:s",$infocert['validTo_time_t'])."\n";
                    // echo "Fecha EPOCH".strtotime(format($json2[$i]->fechaFirma)."\n";
                    // echo "HASH".$json2[$i]->hash;
                    
                    //Creación del modelo que almacena mis firmas
                    $modelFirmaDigital = new InfoFirmaDigitalDocPrincipales();

                    //Obtención de data
                    $estado=$json2[$i]->estadoFirma;
                    // $empresa=$infocert['subject']['O'];
                    $firmante=$infocert['subject']['CN'];
                    // $cargo=$infocert['subject']['title'];
                    // $correoFirmante=$infocert['subject']['emailAddress'];
                    //$direccionEmisor=$infocert['issuer']['street'];
                    //$seviciosOCSP=$infocert['extensions']['authorityInfoAccess'];
                    //$serviciosCRL=$infocert['extensions']['crlDistributionPoints'];
                    $fechaFirmado=format($json2[$i]->fechaFirma);
                    $fechaVencimiento=date("Y-m-d H:i:s",$infocert['validTo_time_t']);
                    $hash = $json2[$i]->hash;
                    $fechaEpochCreacion = strtotime(format($json2[$i]->fechaFirma));

                    // if($firmante=='orfeo.ebsa.com.co' && $epochDocPrincipal == $fechaEpochCreacion && $hashDocPrincipal==$hash){
                    //     continue;
                    // }

                    //Asignar valores al modelo
                    $modelFirmaDigital->firmante=$firmante;
                    $modelFirmaDigital->fechaFirmado=$fechaFirmado;
                    $modelFirmaDigital->fechaVencimiento=$fechaVencimiento;
                    $modelFirmaDigital->idUser=$idUser;
                    $modelFirmaDigital->idradiDocumentoPrincipal=$id;

                    //guardar modelo en la base de datos
                    if(!$modelFirmaDigital->save()){  
                        // $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelFirmaDigital->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                }
            
                $idRadiRadicado = $modelRadiDocumentosPrincipales->idRadiRadicado;
                    
                # Se agrupa todos los radicados seleccionados para notificacion
                $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();

                # Consulta la accion de la transaccion de estado rechazado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signDocument']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Nombre del archivo
                $nombreValido = trim(strtoupper($modelRadiDocumentosPrincipales->nombreRadiDocumentoPrincipal));    

                //Aca actualizamos el estado del documento a firmado digitalmente
                $modelRadiDocumentosPrincipales->estadoRadiDocumentoPrincipal  = Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente'];
                $modelRadiDocumentosPrincipales->save();

                //Actualización de detalles del radicado, donde se evidencia que el radicado se firmó digitalmente
                $Radicados->firmaDigital  = Yii::$app->params['statusTodoText']['Activo'];
                $Radicados->save();

                $observacion = Yii::$app->params['eventosLogTextRadicado']['firmaRadicados'].' '.$nombreValido.' digitalmente'.' y se le asignó el radicado principal '.$Radicados['numeroRadiRadicado'] . ' observación del usuario: '. $request['dataForm']['observacion'];

                /***  Comienzo  Log de Radicados  ***/ 
                HelperLog::logAddFiling(
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                    $Radicados->idRadiRadicado, //Id radicado
                    $idTransacion,
                    $observacion, //texto para almacenar en el evento
                    $Radicados,
                    array() //No validar estos campos
                );
                /***  Fin  Log de Radicados  ***/ 

                $rutaModulo = 'version1/configuracionApp/cg-firmas-docs/digital';
                $observacionlogAuditoria = 'El documento principal ' .$nombreValido.' con el radicado No: '.$Radicados['numeroRadiRadicado'].' se firmó digitalmente.';
                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    $rutaModulo, //Modulo
                    $observacionlogAuditoria, //Mensaje 
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );


                                        

                if ($estado == 'valido') {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => 'Ok',
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'noPermissionsToAccessDirectory')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

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

    /**
     *  Transaccion  cambiar estado Listo para envio
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */
    public function actionShippingReady(){

        if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');

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

                
                # Consulta la accion de la transaccion del estado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'shippingReady']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;
                
                $idRadiSeleccionados = [];
                
                foreach($request['ButtonSelectedData'] as $key => $radicado){
                    
                    $transaction = Yii::$app->db->beginTransaction();
                    
                    $idRadiRadicado = $radicado['id'];
                    # Se agrupa todos los radicados seleccionados para notificacion
                    $Radicados = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();
                    $idRadiSeleccionados[] = $Radicados['numeroRadiRadicado'];

                    $Radicados->estadoRadiRadicado = Yii::$app->params['statusTodoText']['ListoEnvio'];

                    if(!$Radicados->save()){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $Radicados->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                  
                     /***    Log de Radicados  ***/
                     HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $Radicados->idRadiRadicado, //Id radicado
                        $idTransacion,
                        Yii::$app->params['eventosLogTextRadicado']['listoEnvio'].$Radicados['numeroRadiRadicado'].' como listo para enviar', //texto para almacenar en el evento
                        $Radicados,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/ 

                    $transaction->commit();

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'successUpdateRadi', [
                            'numeRadi' => $Radicados['numeroRadiRadicado']
                        ]),
                        'type' => 'success'
                    ];

                     // Se retorna el estado de cada registro
                     $dataResponse[] = array(
                        'id' => $idRadiRadicado,
                        'idInitialList' => ($radicado['idInitialList'] ?? 0) * 1,
                        'status' =>  $Radicados->estadoRadiRadicado,
                        'statusText' =>  Yii::t('app', 'statusTodoNumber')[$Radicados->estadoRadiRadicado],  
                    );
                }

                $implodeRadicados = implode(', ',$idRadiSeleccionados);

                $observacionFiling =  Yii::$app->params['eventosLogText']['listoEnvio']; 

                if(count($idRadiSeleccionados) > 1){
                    $observacionFiling = $observacionFiling.'a los radicados '.$implodeRadicados;
                }else{
                    $observacionFiling = $observacionFiling.'a el radicado '.$implodeRadicados;
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacionFiling,
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Log de Auditoria  ***/

                    $response = [
                        'message' => Yii::t('app','sendShippingReady'),
                        'notificacion' => $notificacion ?? [],
                        'data' => $dataResponse ?? false,
                        'status' => 200,
                    ];

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
     * Transaccion archivar radicados de un expediente y asignación de espacio físico
     * @param array   ButtonSelectedData 
     * @return array message success 
     */
     public function actionArchiveFiling(){

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

                # Consulta la accion de la transaccion del estado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'archiveFiling']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Buscar Usuario que Finalizo el tramite
                $finalizarTramite = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'finalizeFiling']);

                $idRadiSeleccionados = [];
                $dataLog = '';

                foreach($request['ButtonSelectedData'] as $key => $expediente){

                    $transaction = Yii::$app->db->beginTransaction();
                    $idGdExpediente = $expediente['id'];

                    # Se verifica que sea valida la unidad de conservación
                    if(isset($request['data']['unidadConservacionGaArchivo'])){
                        $uniGaArchivo = $request['data']['unidadConservacionGaArchivo'];
                        } else {

                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [Yii::t('app','errorUConservacionInvalida')],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Se consultan todos los radicados que pertenecen a un expedientew
                    $gdExpedientesInclusion = GdExpedientesInclusion::find()->where(['idGdExpediente' => $idGdExpediente])->all();

                    # Datos del expediente
                    $expediente =  GdExpedientes::find()->where(['idGdExpediente' => $idGdExpediente])->one();

                    # Almacenar ubicacion del expediente
                    $gaArchivo =  GaArchivo::find()->where(['idGdExpediente' => $idGdExpediente])->one();

                    if(!isset($gaArchivo)){
                        $gaArchivo = new GaArchivo;
                    }
                    $gaArchivo->attributes = $request['data'];

                    $gaArchivo->unidadConservacionGaArchivo = $uniGaArchivo;
                    $gaArchivo->unidadCampoGaArchivo = (string) $request['data']['unidadCampoGaArchivo'];
                    $gaArchivo->idGdExpediente = $expediente->idGdExpediente;
                    $gaArchivo->estadoGaArchivo = Yii::$app->params['statusTodoText']['Activo'];

                    if(!$gaArchivo->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $gaArchivo->getErrors(), 
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    } else {
                        $gaArchivoConsecutivo = GaArchivo::find()->where(['idgaArchivo' => $gaArchivo->idgaArchivo])->one();
                        $gaArchivoConsecutivo->consecutivoGaArchivo = (Yii::$app->params['consecutivoGaArchivo'] + $gaArchivo->idgaArchivo) - 1;
                        if(!$gaArchivoConsecutivo->save()) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $gaArchivoConsecutivo->getErrors(), 
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    // Data para el log de auditoria
                    $dataLog .= 'Edificio: '. $gaArchivo->gaEdificio->nombreGaEdificio.', ';
                    $dataLog .= 'Piso: '. $gaArchivo->gaPiso->numeroGaPiso.', ';
                    $dataLog .= 'Área de archivo: '. $gaArchivo->gaBodega->nombreGaBodega.', ';
                    $dataLog .= 'Estante: '. $gaArchivo->estanteGaArchivo.', ';
                    $dataLog .= 'Módulo: '. $gaArchivo->rackGaArchivo.', ';
                    $dataLog .= 'Entrepaño: '. $gaArchivo->entrepanoGaArchivo.', ';
                    $dataLog .= 'Caja: '. $gaArchivo->cajaGaArchivo.', ';
                    $dataLog .= 'Cuerpo: '. $gaArchivo->cuerpoGaArchivo.', ';
                    $dataLog .= 'Número de conservación: '. $gaArchivo->unidadCampoGaArchivo.', ';
                    $dataLog .= 'Unidad de conservación: '. Yii::$app->params['unidadConservacionGaArchivoNumber'][$gaArchivo->unidadConservacionGaArchivo] ;

                    $observacion = Yii::$app->params['eventosLogTextExpedientes']['archivado'].$expediente->numeroGdExpediente;
                    
                    /***    Log de Expedientes  ***/
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $expediente['idGdExpediente'],
                        Yii::$app->params['operacionExpedienteText']['ArchivarExpediente'], //Operación
                        $observacion
                    );
                    /***  Fin  Log de Expedientes  ***/ 

                    foreach($gdExpedientesInclusion as $radicados){

                        # Actualizar estado de los radicados asociados a archivado   
                        $radicado = RadiRadicados::find()->where(['idRadiRadicado' => $radicados->idRadiRadicado])->one();     
                   
                        $radiLogRadicados = RadiLogRadicados::find()->where([
                            'idRadiRadicado' => $radicado->idRadiRadicado,
                            'idTransaccion' => $finalizarTramite->idCgTransaccionRadicado
                        ])->one();

                        if(!isset($radiLogRadicados)){
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [                      
                                'data'  => ['error' => [ Yii::t('app','failStatusFinalizeRadi',[
                                    'numeRadi' => $radicado->numeroRadiRadicado])
                                ]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                            
                        $idRadiSeleccionados[] = $radicado->numeroRadiRadicado;
                        
                        # El estado del radicado pasa a archivado.
                        $radicado->estadoRadiRadicado =  Yii::$app->params['statusTodoText']['Archivado'];

                        if(!$radicado->save()){
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' =>  $radicado->getErrors(),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        } 


                          /***    Log de Radicados  ***/
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $radicado->idRadiRadicado, //Id radicado
                            $idTransacion,
                            $observacion,
                            [$gaArchivo], // model
                            array()  // No validar estos campos
                        );
                        /***  Fin  Log de Radicados  ***/ 
                    }
 
                    //////////////////////////  Notificar Email //////////////////////////////// 

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $radiLogRadicados->idUser, // Id user notificado
                        Yii::t('app','messageNotification')['spaceAllocation'].$radiLogRadicados->radiRadicado->numeroRadiRadicado, //Notificacion
                        Yii::$app->params['routesFront']['viewLocationArchive'], // URL
                        $radiLogRadicados->idRadiRadicado // id radicado
                    );
                    /***  Fin Notificacion  ***/

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'successArchiveRadi', [
                            'numeRadi' => $radicado->numeroRadiRadicado
                        ]),
                        'type' => 'success'
                    ];
                    
                    $transaction->commit();

                    # verificar cambio de estado y asignacion de espacio fisico
                    if(isset($gaArchivo->gdExpediente)){
                        $espacioFisicoStatus = true;    
                        $espacioFisicoText = Yii::t('app','allocatedSpace');

                    } else {
                        $espacioFisicoStatus = false;   
                        $espacioFisicoText = Yii::t('app','unallocatedSpace');
                    }

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $radicado->idRadiRadicado,
                        'idInitialList' => ($radicado['idInitialList'] ?? 0) * 1,
                        'espacioFisicoStatus' => $espacioFisicoStatus,
                        'espacioFisicoText'     => $espacioFisicoText,
                        'status' =>  $radicado->estadoRadiRadicado,
                        'statusText' => Yii::t('app', 'statusTodoText')['Archivado'],  
                    );

                    $notificarCorreo[$radiLogRadicados['idUser']]['idRadicado'][] = [
                        'idRadiRadicado' => $radicado->idRadiRadicado,
                        'numeroRadiRadicado' => $radicado->numeroRadiRadicado
                    ];

                }

                if (isset($notificarCorreo) && is_array($notificarCorreo)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($notificarCorreo as $key => $idRadicado) {
                        
                        $idRadiSeleccionados = [];
                        // Envia la notificación de correo electronico al usuario de tramitar                    
                        foreach($idRadicado['idRadicado'] as $i => $value){
                            $idRadi = $notificarCorreo[$key]['idRadicado'][$i]['idRadiRadicado'];
                            $idRadiSeleccionados[] = $value['numeroRadiRadicado'];
                        }
                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if (count($idRadicado['idRadicado']) > 1) {
                            $textStart = 'mailEventArchiAll';
                            $subject = Yii::t('app', 'headMailTextFirmaAll');
                        } else {
                             
                            $textStart = 'mailEventArchiOne';  
                            $subject = Yii::t('app', 'headMailTextFirmaOne',[
                                'numRadi' => $notificarCorreo[$key]['idRadicado'][$i]['numeroRadiRadicado']
                            ]);
                        }

                        $user = User::find()->where(['id' => $key])->one(); 
                        $numRadicado = implode(', ',$idRadiSeleccionados);

                        $textBody  = Yii::t('app', $textStart, [
                            'estante'   => $request['data']['estanteGaArchivo'],
                            'rack'      => $request['data']['rackGaArchivo'],
                            'entrepano' => $request['data']['entrepanoGaArchivo'],
                            'caja'      => $request['data']['cajaGaArchivo'],
                            'cuerpo'    => $request['data']['cuerpoGaArchivo'],
                            'numRadi'   => $numRadicado,
                            'user'      => $user->userDetalles['nombreUserDetalles'].' '. $user->userDetalles['apellidoUserDetalles'],
                            'depe'      => $user->gdTrdDependencia['nombreGdTrdDependencia']
                        ]);

                        $headMailText = $subject;   
                        $bodyMail = 'radicacion-html';
     
                        if(count($idRadiSeleccionados) > 1){
                            $envioCorreo = CorreoController::differentFilings($user['email'], $headMailText, $textBody, $bodyMail, '', $subject);
                        }else{
                            $envioCorreo = CorreoController::differentFilings($user['email'], $headMailText, $textBody, $bodyMail, $idRadi, $subject, true);
                        }
                    }
                }

                $implodeRadicados = implode(', ',$idRadiSeleccionados);

                $observacionFiling =  Yii::$app->params['eventosLogText']['asignacionEspacioFisico']; 

                if(count($idRadiSeleccionados) > 1){
                    $observacionFiling = $observacionFiling.'a los radicados '.$implodeRadicados;
                }else{
                    $observacionFiling = $observacionFiling.'a el radicado '.$implodeRadicados;
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacionFiling,
                    '',
                    $dataLog, //Data
                    array() //No validar estos campos
                );
                /***    Log de Auditoria  ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'notificacion' => $notificacion ?? [],
                    'dataResponse' => $dataResponse ?? [],
                    'data' => $dataList ?? [],                         
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
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
     *  Transaccion  archivar radicado
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */
    public function actionArchiveExpedient(){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsArchiveExpedient'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

                # Consulta la accion de la transaccion del estado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'archiveFiling']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                $idExpSeleccionados = [];

                foreach($request['ButtonSelectedData'] as $key => $expediente){

                    $transaction = Yii::$app->db->beginTransaction();
                    $idExpediente = $expediente['id'];
                    $idInitialList = $expediente['idInitialList'];
                    # Se agrupa todos los radicados seleccionados para notificacion
                    $expediente = GdExpedientes::find()->where(['idGdExpediente' => $idExpediente])->one();

                    if(!isset($expediente)){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','registernoFound'),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    $gdExpedientesInclusion = GdExpedientesInclusion::findOne(['idGdExpediente' => $idExpediente]);  //esta mal
                    $gaArchivoValido = GaArchivo::find()->where(['idGdExpediente' => $gdExpedientesInclusion['idGdExpediente']])->all();

                    if(isset($gaArchivoValido)){ 

                         # update status archivo
                         foreach($gaArchivoValido as $gaArchivos){
                            
                            $gaArchivos->estadoGaArchivo = Yii::$app->params['statusTodoText']['Inactivo']; 

                            if(!$gaArchivos->save()){
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $gaArchivos->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        
                        $idExpSeleccionados[] = $expediente['numeroGdExpediente'];

                        $gaArchivo = new GaArchivo;
                        $gaArchivo->attributes = $request['data'];

                        if(isset($request['data']['unidadConservacionGaArchivo'])){

                            $uniGaArchivo = $request['data']['unidadConservacionGaArchivo'];
                            $gaArchivo->unidadConservacionGaArchivo = $uniGaArchivo;
                            $gaArchivo->unidadCampoGaArchivo = (string) $request['data']['unidadCampoGaArchivo'];
                        
                        }else{

                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorUConservacionInvalida'),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        // $notificacion[] =  [
                        //     'message' => Yii::t('app', 'failArchiveRadi', [
                        //         'numeRadi' => $Radicados['numeroRadiRadicado']
                        //     ]),
                        //     'type' => 'danger'
                        // ];

                        // // Se retorna el estado de cada registro
                        // $dataResponse[] = array(
                        //     'id' => $idRadiRadicado,
                        //     'idInitialList' => ($radicado['idInitialList'] ?? 0) * 1,
                        //     'status' =>  $Radicados->estadoRadiRadicado,
                        //     'statusText' =>  Yii::t('app', 'statusTodoText')['Pendiente por archivar'],  
                        // );

                        switch($expediente['ubicacionGdExpediente']){
                            case 1: # Gestion a Central
                                $expediente->ubicacionGdExpediente = Yii::$app->params['ubicacionTransTRDNumber']['central'];
                            break;
                            case 2: # Central a Historico
                                $expediente->ubicacionGdExpediente = Yii::$app->params['ubicacionTransTRDNumber']['historico'];
                            break;
                            case 3: # Historico a ...
                               // $gdExpediente->ubicacionGdExpediente = Yii::$app->params['ubicacionTransTRDNumber'][''];
                            break;
                        }

                        if($expediente['estadoGdExpediente'] == Yii::$app->params['statusTodoText']['TransferenciaAceptada']){

                            $expediente['estadoGdExpediente'] = Yii::$app->params['statusTodoText']['Archivado'];

                            if(!$expediente->save()){ 
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $gaArchivo->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }else{

                            $expediente = $expediente['numeroGdExpediente'].'-'.$expediente['nombreGdExpediente'];

                            $error = Yii::t('app', 'failTransExpediente',[
                                'numExp' => $expediente
                            ]);

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'data' => ['error' => [$error]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                    }else{

                        $idExpSeleccionados[] = $expediente['numeroGdExpediente'];

                        $gaArchivo = new GaArchivo;
                        $gaArchivo->attributes = $request['data'];

                        if(isset($request['data']['unidadConservacionGaArchivo'])){

                            $uniGaArchivo = $request['data']['unidadConservacionGaArchivo'];
                            $gaArchivo->unidadConservacionGaArchivo = $uniGaArchivo;
                            $gaArchivo->unidadCampoGaArchivo = (string) $request['data']['unidadCampoGaArchivo'];
                        
                        }else{

                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorUConservacionInvalida'),
                                'dataUpdate' => [],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                
                
                    $gaArchivo->idGdExpediente = $gdExpedientesInclusion['idGdExpediente'];
                    $gaArchivo->estadoGaArchivo = Yii::$app->params['statusTodoText']['Activo'];

                    $observacion = Yii::$app->params['eventosLogTextExpedientes']['archivado'].$expediente['numeroGdExpediente'];
                    
                    /***    Log de Expedientes  ***/
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $expediente['idGdExpediente'],
                        Yii::$app->params['operacionExpedienteText']['ArchivarExpediente'], //Operación
                        $observacion
                    );
                    /***  Fin  Log de Expedientes  ***/ 

                    if(!$gaArchivo->save()){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $gaArchivo->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    # //////////////////////////  Notificar Email ////////////////////////////////

                    # Buscar Usuario que Finalizo el tramite
                    $GdHistoricoExp= GdHistoricoExpedientes::find()
                    ->where([
                        'idGdExpediente'  => $idExpediente,
                        'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['CrearExpediente']
                    ])->one();

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'successArchiveExp', [
                            'numExp' => $expediente['numeroGdExpediente']
                        ]),
                        'type' => 'success'
                    ];
                    
                    $transaction->commit();

                    if(isset($gdExpedientesInclusion->gaArchivo)){
                        $espacioFisicoStatus = true;    $espacioFisicoText = Yii::t('app','allocatedSpace');
                    }else{
                        $espacioFisicoStatus = false;   $espacioFisicoText = Yii::t('app','unallocatedSpace');
                    }

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $idExpediente,
                        'idInitialList' => ($idInitialList ?? 0) * 1,
                        'espacioFisicoStatus' => $espacioFisicoStatus,
                        'espacioFisicoText'   => $espacioFisicoText,
                        'status'              => $expediente->estadoGdExpediente,
                        'statusText'          => Yii::t('app', 'statusTodoText')['Archivado'],  
                    );

                    $notificarCorreo[$GdHistoricoExp['idUser']]['idExpediente'][] = [
                        'idExpediente' => $idExpediente,
                        'numeroExpediente' => $expediente['numeroGdExpediente']
                    ];

                    $gaArchivoValido = null;
                    
                }

                if (isset($notificarCorreo) && is_array($notificarCorreo)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($notificarCorreo as $key => $idRadicado) {

                        $idExpSeleccionados = [];

                        // Envia la notificación de correo electronico al usuario de tramitar                    
                        foreach($idRadicado['idExpediente'] as $i => $value){

                            $idExp = $value['idExpediente'];
                            $gdExpedientesInclusion = GdExpedientesInclusion::findOne(['idGdExpediente' => $value['idExpediente']]);
                            $idExpSeleccionados[] = $gdExpedientesInclusion->radiRadicado['numeroRadiRadicado'];
                        }

                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if (count($idExpSeleccionados) > 1) {

                            $textStart = 'mailEventArchiAll';
                            $subject = Yii::t('app', 'headMailTextFirmaAll');
    
                        } else {
                             
                            $textStart = 'mailEventArchiOne';  
                            $subject = Yii::t('app', 'headMailTextFirmaOne',[
                                'numRadi' => implode(', ',$idExpSeleccionados)
                            ]);

                            // $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadoEvent->idRadiRadicado));
                        }

                        $user = User::find()->where(['id' => $key])->one(); 
                        $numRadicado = implode(', ',$idExpSeleccionados);

                        $textBody  = Yii::t('app', $textStart, [
                            'estante'   => isset($request['data']['estanteGaArchivo']) ? $request['data']['estanteGaArchivo'] : 0, // PENDIENTE VALIDAR FUNCION
                            'rack'      => $request['data']['rackGaArchivo'],
                            'entrepano' => $request['data']['entrepanoGaArchivo'],
                            'caja'      => $request['data']['cajaGaArchivo'],
                            'numRadi'   => $numRadicado,
                            'user'      => $user->userDetalles['nombreUserDetalles'].' '. $user->userDetalles['apellidoUserDetalles'],
                            'depe'      => $user->gdTrdDependencia['nombreGdTrdDependencia']
                        ]);
                      
                        $i++;
                        $headMailText = $subject;   
                        $bodyMail = 'radicacion-html';
     
                        if(count($idExpSeleccionados) > 1){
                            $envioCorreo = CorreoController::differentFilings($user['email'], $headMailText, $textBody, $bodyMail, '', $subject);
                        }else{
                            $envioCorreo = CorreoController::differentFilings($user['email'], $headMailText, $textBody, $bodyMail, $idExp, $subject, true);
                        }
    
                    }
                }

                $implodeRadicados = implode(', ',$idExpSeleccionados);

                $observacionFiling =  Yii::$app->params['eventosLogText']['asignacionEspacioFisico']; 

                if(count($idExpSeleccionados) > 1){
                    $observacionFiling = $observacionFiling.'a los radicados '.$implodeRadicados;
                }else{
                    $observacionFiling = $observacionFiling.'a el radicado '.$implodeRadicados;
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observacionFiling,
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Log de Auditoria  ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'notificacion' => $notificacion ?? [],
                    'dataResponse' => $dataResponse ?? [],
                    'data' => $dataList ?? [],                         
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
            
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
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
     *  Transaccion  Devolver radicado PQR al cliente
     * 
     * @param array   ButtonSelectedData   
     * 
     * @return array message success 
     */

    public function actionReturnPqrToCitizen(){     

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

                #Obtener valores del request                
                $observacion = $request['data']['observacion'];

                $trasaccionModel = CgTransaccionesRadicados::find()
                    ->select(['idCgTransaccionRadicado'])
                    ->where(['actionCgTransaccionRadicado' => 'returnPqrToCitizen'])
                    ->one()
                ;

                $transaction = Yii::$app->db->beginTransaction();                

                foreach($request['ButtonSelectedData'] as $key => $radicado){  
                    
                    $idRadicado = $radicado['id'];

                    $modelRadicado = RadiRadicados::find()
                        ->where(['idRadiRadicado' =>  $idRadicado])
                        ->one()
                    ;                    

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $idRadicado, //Id radicado
                        $trasaccionModel->idCgTransaccionRadicado,
                        Yii::$app->params['eventosLogTextRadicado']['returnPqrToCitizen'] . $observacion, //observación 
                        $modelRadicado,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/

                    $dataRadicadoOld = 'EstadoRadiRadicado: ' . Yii::$app->params['statusTodoNumber'][$modelRadicado->estadoRadiRadicado];

                    $modelRadicado->estadoRadiRadicado = Yii::$app->params['statusTodoText']['DevueltoAlCiudadano'];                    

                    $numRadicados = [];

                    if($modelRadicado->save()){

                        $dataRadicado = 'EstadoRadiRadicado: ' . Yii::$app->params['statusTodoNumber'][$modelRadicado->estadoRadiRadicado]; 

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogTextRadicado']['returnPqrToCitizen'] . " $observacion",//texto para almacenar en el evento
                            $dataRadicadoOld,
                            $dataRadicado, //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                        $numRadicados[] = $modelRadicado->numeroRadiRadicado;

                    }else{
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelRadicado->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                                                     
                    }                

                    //Valida si se tiene autorización para responder por correo electronico al ciudadano
                    if($modelRadicado->autorizacionRadiRadicados == Yii::$app->params['autorizacionRadiRadicado']['text']['correo']){

                        # Configuracion general de los días habiles para que el Ciudadano de su respuesta
                        $configGeneral = ConfiguracionGeneralController::generalConfiguration();

                        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                        if($configGeneral['status']){            
                            $diasRespuestaPqrs = $configGeneral['data']['diaRespuestaPqrsCgGeneral'];

                        } else {

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [$configGeneral['message']]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    
                        $idCliente = $modelRadicado->radiRemitentes->idRadiPersona;

                        $modelCliente = Clientes::find()
                            ->where(['idCliente' => $idCliente])
                        ->one();

                        $email =  $modelCliente->correoElectronicoCliente;
                        $headMailText = Yii::t('app', 'returnPqrToCitizenHead', [
                            'numeroRadicado' => $modelRadicado->numeroRadiRadicado
                        ]);
                        $textBody = Yii::t('app', 'returnPqrToCitizenBody', [
                            'diasRespuestas' => $diasRespuestaPqrs
                        ]);
                        $subject = Yii::t('app', 'returnPqrToCitizenSubject', [
                            'numeroRadicado' => $modelRadicado->numeroRadiRadicado
                        ]);              
    
                        $bodyMail = 'radicacion-html';

                        $buttonLink = Yii::$app->params['urlBaseApiPublic'] . '/site/login';
                        $nameButton = Yii::t('app', 'getIn');

                        $evioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $buttonLink, $subject, $nameButton);
                                               
                    }                    
                   
                }

                $transaction->commit();
                
                if(count($numRadicados) > 1){
                    $message = Yii::t('app', 'successReturnPqrToCitizenMany'). implode(',', $numRadicados);
                }else{
                    $message = Yii::t('app', 'successReturnPqrToCitizenOne', [
                        'numeroRadicado' => $numRadicados[0]
                    ]);
                }              

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' =>  $message,
                    'data' => [],
                    'status' => 200
                ];
                return HelperEncryptAes::encrypt($response, true);

              
            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
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
     * Servicio que valida si, es un usuario tramitador actualiza
     * el campo 'público documento' a estado true o false.
     **/ 
    public function actionPublishDocument() {

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

                $saveDataValid = true;
                $errors = [];
                $transaction = Yii::$app->db->beginTransaction();

                $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'publishDocument']);
                $idTransaccion = $modelTransaccion->idCgTransaccionRadicado;
                
                # Se actualiza estado del campo 'publico documento'
                foreach($request['ButtonSelectedData'] as $dataDocument){

                    # Id dependiendo del tipo de documento
                    $id = $dataDocument['id'];

                    # Se valida que tipo de id se recibe para obtener su modelo.
                    if($request['data']['type'] == 'documento') {
                        $model = RadiDocumentos::findOne(['idRadiDocumento' => $id]);

                    } elseif ($request['data']['type'] == 'principal'){

                        #Se valida que el documento este combinado o firmado.
                        $model = RadiDocumentosPrincipales::find()
                            ->where(['idradiDocumentoPrincipal' => $id])
                            ->andWhere(['or', 
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Combinado']], 
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesandoFirma']],
                            ])
                        ->one();
                    }                    

                    if(!is_null($model)){

                        $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado]);

                        if(!is_null($modelRadicado)){

                            # Si el usuario logueado es el tramitador puede activar o inactivar el documento público
                            if(Yii::$app->user->identity->id == $modelRadicado->user_idTramitador){

                                if ($model->publicoPagina == Yii::$app->params['valuePageText']['Publico']) {

                                    $model->publicoPagina = Yii::$app->params['valuePageText']['NoPublico'];
                                    $message = 'messageNotPostDocument';
            
                                } else {
                                    $model->publicoPagina = Yii::$app->params['valuePageText']['Publico'];
                                    $message = 'messagePostDocument';
                                }


                                // print_r($model);die();

                                if ($model->save()) {

                                    /***    Log de Auditoria  ***/
                                    HelperLog::logAdd(
                                        false,
                                        Yii::$app->user->identity->id, //Id user
                                        Yii::$app->user->identity->username, //username
                                        Yii::$app->controller->route, //Modulo
                                        Yii::$app->params['eventosLogText']['ChangeStatusPublish'] . Yii::t('app','valuePageNumber')[$model->publicoPagina],// texto para almacenar en el evento
                                        [], //Data Old
                                        [], //Data
                                        array() //No validar estos campos
                                    );
                                    /***    Fin log Auditoria   ***/


                                    /***  Log de Radicados  ***/
                                    HelperLog::logAddFiling(
                                        Yii::$app->user->identity->id, //Id user
                                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                        $modelRadicado->idRadiRadicado, //Id radicado
                                        $idTransaccion,
                                        Yii::$app->params['eventosLogText']['ChangeStatusPublish'] . Yii::t('app','valuePageNumber')[$model->publicoPagina], //observación
                                        $model,
                                        array() //No validar estos campos
                                    );
                                    /*** Fin log radicados ***/
            
                                } else {
                                    $errors[] = $model->getErrors();
                                    $saveDataValid = false;
                                }

                                
                            } else {

                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'accesoDenegado')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }

                    } else {

                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorMainDocuments')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }                                     
                }
             
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app',$message),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                # Fin Evaluar respuesta de datos guardados #


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
     *  Transaccion imprimit sticker 
     *  La acción es para radicados de tipo ENTRADA y PQRSD sin estar en estado finalizado
     * @param array   ButtonSelectedData 
     * 
     * @return array message success 
     */
    public function actionPrintSticker(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');

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

                $modelEtiqueta = CgEtiquetaRadicacion::find()
                    ->where(['estadoCgEtiquetaRadicacion' => Yii::$app->params['statusTodoText']['Activo']])->all();
                
                if(empty($modelEtiqueta)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),                                   
                        'data' => ['error' => [ Yii::t('app','fileDonotDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $items = 0;
                $etiquetasArray = [];
                foreach($modelEtiqueta as $etiqueta){
                    if($etiqueta->estadoCgEtiquetaRadicacion == Yii::$app->params['statusTodoText']['Activo']){
                        $etiquetasArray[] = $etiqueta->etiquetaCgEtiquetaRadicacion;

                        if( $etiqueta->etiquetaCgEtiquetaRadicacion == 'idTrdDepeUserTramitador'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'user_idCreador'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'fechaRadicacion'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'nombreCliente'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'asuntoRadiRadicado'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'direccionCliente'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'numeroRadiRad'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'descripcionAnexos'                                
                        ){
                            $items++;
                        }
                    }                   
                }           

                //Imagen del sticker              
                $sizeX = 465;
                $sizeY = 128;
                $font =  Yii::getAlias('@webroot') . '/fonts/arial.ttf';
                $fontSize = 20;  //Tamaño de fuente dado en puntos (12pt = 16px)
                $rotation = 0;

                $itemSize = 12;  // Tamaño de espaciado
                if($items >= 6){
                    $itemSize = 100 / $items;
                    $fontSize = $itemSize * 0.6;
                }else{
                    $itemSize = 70 / $items;
                    $fontSize = $itemSize * 0.6;
                }

                $fontSize = floor($fontSize);

                $stickersArray = []; 
                foreach($request['ButtonSelectedData'] as $key => $radicado){
                    
                    //Coordenadas de texto en la imagen png
                    $x = 33;
                    $y = 19;

                    $modelRadicado = RadiRadicados::find()
                        ->where(['idRadiRadicado' => $radicado['id']])
                    ->one();

                    if($modelRadicado->isRadicado == 0 || $modelRadicado->isRadicado == false) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [ Yii::t('app','messageRadiTmpError', ['numFile' => $modelRadicado->numeroRadiRadicado])]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $path =  Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/sticker' . $modelRadicado->numeroRadiRadicado . '.png';
                    $stickersArray[] = ['path' => $path, 'numeroRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado, $modelRadicado->isRadicado)];

                    //Imagen
                    $image = imagecreate($sizeX, $sizeY);
                 
                    //Colores del codigo de barras
                    imagecolorallocate($image, 255, 255, 255);
                    $black = imagecolorallocate($image, 0, 0, 0);
                    
                    if(in_array('rutaLogo', $etiquetasArray)){
                        //El logo debe ser de 300 x 300px
                        $logoPath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogoPrint'];                     
                        $logo = imagecreatefrompng($logoPath);                       
                        
                        $w = 40; //ancho
                        $h = 150; //alto
                        
                        //Crear una nueva imagen de color verdadero
                        $backgroundLogo = imagecreatetruecolor($w, $h);

                        imagecopyresampled($backgroundLogo, $logo, 0, 0, 0, 0, 30, 145, 60, 350);
                        $white = imagecolorallocate($backgroundLogo, 255, 255, 255);
                        $negro = imagecolorallocate($backgroundLogo, 0, 0, 0);
                        imagecolortransparent($backgroundLogo, $negro); 
                        imagefill($backgroundLogo, 0, 0, $white);
                        imagecopy($image, $backgroundLogo, 5, 3, 0, 0, $w, $h);                       
                        
                    }

                    // Esta etiqueta fue eliminada
                    // if(in_array('cliente', $etiquetasArray)){
                    //     $text = Yii::$app->params['etiquetaCliente'];
                    //     imagettftext($image, 15, $rotation ,585, 170, $black, $font, $text);
                    // }
                    
                    if(in_array('codigoBarras', $etiquetasArray)){
                        
                        $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcode.png';

                        $barcode = new BarcodeGeneratorPNG();
                        // $barcode = $barcode->getBarcode($modelRadicado->numeroRadiRadicado, BarcodeGeneratorSVG::TYPE_CODE_39);
                        /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                        $barcode = $barcode->getBarcode($modelRadicado->numeroRadiRadicado, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                        file_put_contents($pathBarcode  , $barcode);
                        
                        $barcodeImage = imagecreatefrompng($pathBarcode);   
                        imagecopy($image, $barcodeImage, 30, 5, 0, 0, 542, 30);
                        
                        $y += 50;
                    }                    

                    if(in_array('numeroRadiRadicado', $etiquetasArray)){

                        $text = "Rad N°: ". HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado, $modelRadicado->isRadicado);

                        if(in_array('fechaRadicacion', $etiquetasArray)){
                            $text .= " - Fecha rad: ". $modelRadicado->creacionRadiRadicado;
                        }

                        imagettftext($image, '11', $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;
                        
                    } elseif(in_array('fechaRadicacion', $etiquetasArray)){
                        $text = "Fecha rad: ". $modelRadicado->creacionRadiRadicado;
                        imagettftext($image, '11', $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;
                    }


                    if(in_array('user_idCreador', $etiquetasArray)){
                        $modelUserDetalle = UserDetalles::find()
                            ->where(['idUser' => $modelRadicado->user_idCreador])
                        ->one();                            

                        $text = "Usu Radicador: ".$modelUserDetalle->nombreUserDetalles. " ".$modelUserDetalle->apellidoUserDetalles;
                        
                        if(in_array('idTrdDepeUserTramitador', $etiquetasArray)){
                            $text .=  " - Dep: ". $modelRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;  
                        }

                        if($items > 6){
                            $text = substr($text, 0, 72).'.';
                        } else {
                            $text = substr($text, 0, 64).'.';
                        }

                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;

                    } elseif (in_array('idTrdDepeUserTramitador', $etiquetasArray)){
                        $text =  "Dep: ". $modelRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;

                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);                        
                        $y += $itemSize;
                    }


                    if(in_array('descripcionAnexos', $etiquetasArray)){
                        $text = "Descripción Anexos: ".$modelRadicado->descripcionAnexoRadiRadicado;
                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;
                    }

                    //Determinar si es cliente o usuario
                    $modelRemitente = RadiRemitentes::find()
                        ->where(['idRadiRadicado' => $radicado['id']])
                    ->one();

                    if($modelRemitente->idTipoPersona == Yii::$app->params['tipoPersonaText']['funcionario']){

                        $modelUser = User::find()
                            ->where(['id' => $modelRemitente->idRadiPersona])
                        ->one();

                        $dependenciaModel = GdTrdDependencias::find()
                            ->where(['idGdTrdDependencia' => $modelUser->idGdTrdDependencia])
                        ->one();

                        # Datos del funcionario
                        $nombreRemitente = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;

                        $direccionRemitente = $dependenciaModel->nombreGdTrdDependencia;

                    } else {

                        $modelCliente = Clientes::find()
                            ->where(['idCliente' => $modelRemitente->idRadiPersona])
                        ->one();

                        #Datos del cliente
                        $nombreRemitente = $modelCliente->nombreCliente;
                        $direccionRemitente = $modelCliente->direccionCliente;
                        
                    }

                    if(in_array('nombreCliente', $etiquetasArray)){
                        $text = "Remitente: ".$nombreRemitente;
                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y-2), $black, $font, $text);
                        $y += $itemSize;

                    }/* else if(in_array('direccionCliente', $etiquetasArray)){
                        $text = "Rem/dest Dirección: $direccionRemitente ";
                        imagettftext($image, $fontSize, $rotation ,$x, $y, $black, $font, $text);
                        $y += $itemSize;
                    } */

                    if(in_array('direccionCliente', $etiquetasArray)){
                        $text = "Dir. Remi: $direccionRemitente ";
                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;
                    } 
                   

                    if(in_array('asuntoRadiRadicado', $etiquetasArray)){
                        $text = substr( "Asunto: $modelRadicado->asuntoRadiRadicado", 0, 68);
                        imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                        $y += $itemSize;
                    }                                      
                    
                    // $text = substr( "Dirección: Alcaldia Municipal de Sesquile", 0, 68);
                    // imagettftext($image, '8', $rotation ,floor($x), floor($y), $black, $font, $text);
                    // $y += $itemSize;

                    if(!imagepng($image, $path)){
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),                                   
                            'data' => ['error' => [ Yii::t('app','filecanNotBeDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);                        
                    }
                    
                    //Eliminar imagen del codigo de barras
                    if(isset($pathBarcode) && $pathBarcode=!null){                        
                        unlink(Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcode.png');
                    }
                   
                }

                $arrayArchivos = [];
                foreach($stickersArray as $sticker){
                    if(file_exists($sticker['path'])){

                        $nombreArchivo = explode('/', $sticker['path']);
                        $nombreArchivo = end($nombreArchivo);

                        $arrayArchivos[] =  [
                            'dataFile' => base64_encode(file_get_contents($sticker['path'])),
                            'fileName' =>  $nombreArchivo
                        ];
                        
                        //Eliminar archivo en el servidor
                        unlink($sticker['path']);

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['printSticker'] . $sticker['numeroRadicado'], 
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/
                    }else{
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),                                   
                            'data' => ['error' => [ Yii::t('app','fileDonotDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        $return = HelperEncryptAes::encrypt($response, true);
                    }              
                }

                if(count($arrayArchivos) >1){                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successPrintStickerMany'),
                        'data' => [], // data                        
                        'status' => 200,
                    ];                        
                    $return = HelperEncryptAes::encrypt($response, true);
                }else{
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successPrintStickerOne'),
                        'data' => [], // data                       
                        'status' => 200,
                    ];                        
                    $return = HelperEncryptAes::encrypt($response, true);
                }

                // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                $return['datafile'] = $arrayArchivos;              

                return $return;

            }else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

        }else{
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
     * Transacción para imprimir la etiqueta externa
     */
    public function actionPrintExternalSticker() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');

            if(!empty($jsonSend)) {

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

                $modelEtiqueta = CgEtiquetaRadicacion::find()
                    ->where(['estadoCgEtiquetaRadicacion' => Yii::$app->params['statusTodoText']['Activo']])->all();
                
                if(empty($modelEtiqueta)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),                                   
                        'data' => ['error' => [ Yii::t('app','fileDonotDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $items = 0;
                $etiquetasArray = [];
                foreach($modelEtiqueta as $etiqueta) {
                    if($etiqueta->estadoCgEtiquetaRadicacion == Yii::$app->params['statusTodoText']['Activo']) {
                        $etiquetasArray[] = $etiqueta->etiquetaCgEtiquetaRadicacion;

                        if( $etiqueta->etiquetaCgEtiquetaRadicacion == 'idTrdDepeUserTramitador'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'user_idCreador'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'fechaRadicacion'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'nombreCliente'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'asuntoRadiRadicado'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'direccionCliente'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'numeroRadiRad'
                            || $etiqueta->etiquetaCgEtiquetaRadicacion == 'descripcionAnexos'
                        ) {
                            $items++;
                        }
                    }
                }

                //Imagen del sticker              
                $sizeX = 465;
                $sizeY = 128;
                $font =  Yii::getAlias('@webroot') . '/fonts/arial.ttf';
                $fontSize = 15;  //Tamaño de fuente dado en puntos (12pt = 16px)
                $rotation = 0;

                $itemSize = 12;  // Tamaño de espaciado
                if($items >= 6){
                    $itemSize = 100 / $items;
                    $fontSize = $itemSize * 0.6;
                }else{
                    $itemSize = 70 / $items;
                    $fontSize = $itemSize * 0.6;
                }

                $stickersArray = [];

                //Coordenadas de texto en la imagen png
                $x = 30;
                $y = 6;

                // Id del radicado del request
                $idRadiRadicadoRequest = $request['ButtonSelectedData'];
                // Número de radicado
                $numeroRadicado = "";
                // Fecha en la que se genera el número de radicado
                $fechaCreacionRadicado = "";
                // Modelo del radicado 
                $modelRadicado = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicadoRequest])->one();
                $HelperConsecutivo = new HelperConsecutivo();

                if($modelRadicado->isRadicado === 0 || $modelRadicado->isRadicado === false) {
                    $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRadicado->idTrdDepeUserCreador]);
                    $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
                    $generateNumberFiling = RadicadosController::generateNumberFiling($modelRadicado->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);
                    
                    /** 
                    * Se busca la dependencia y usuario actual del radicado padre para asignar esos mismos valores 
                    * en el usuario y depedendecia tramitadora del radicado nuevo que se esta generando
                    **/
                    $modelRadicado->user_idTramitador = $modelRadicado->user_idTramitador;
                    $modelRadicado->idTrdDepeUserTramitador = $modelRadicado->idTrdDepeUserTramitador;

                    $modelRadicado->isRadicado = 1;
                    $modelRadicado->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
                    $modelRadicado->creacionRadiRadicado = date("Y-m-d H:i:s");
                    
                    /** 
                    * En los campos de usuario creador y dependencia creador van a quedar los datos del usuario que genero el radicado
                    * y el stiker de firma fisica.
                    **/
                    $usuarioDepe = User::findOne(['id'=>Yii::$app->user->identity->id]);
                    $modelRadicado->user_idCreador = Yii::$app->user->identity->id;
                    $modelRadicado->idTrdDepeUserCreador = $usuarioDepe->idGdTrdDependencia;

                    if(!$modelRadicado->save()) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $modelRadicado->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signingProcess']);
                    $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                    $observacion = Yii::$app->params['eventosLogTextRadicado']['procesoFirmaFisicaDescargaEtiqueta'].' '.$modelRadicado->numeroRadiRadicado;

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $idRadiRadicadoRequest, //Id radicado
                        $idTransacion,
                        $observacion, //texto para almacenar en el evento
                        $modelRadicado,
                        array() //No validar estos campos
                    );
                    /***  Fin  Log de Radicados  ***/

                    $modelUseEnd = $modelRadicado;
                    $numeroRadicado = $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado,$modelRadicado->isRadicado);
                    $fechaCreacionRadicado = $modelRadicado->creacionRadiRadicado;
                } else {
                    
                    $modelRadicadoHijo = RadiRadicados::find()->where(['idRadiRadicadoPadre' => $modelRadicado->idRadiRadicado])->one();
                    if($modelRadicadoHijo) {
                        if($modelRadicadoHijo->isRadicado === 0 || $modelRadicadoHijo->isRadicado === false) {
                            $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRadicadoHijo->idTrdDepeUserCreador]);
                            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
                            $generateNumberFiling = RadicadosController::generateNumberFiling($modelRadicadoHijo->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);
                            
                            /** 
                             * Se busca la dependencia y usuario actual del radicado padre para asignar esos mismos valores 
                             * en el usuario y depedendecia tramitadora del radicado nuevo que se esta generando
                             **/
                            $modelRadicadoHijo->user_idTramitador = $modelRadicadoHijo->user_idTramitador;
                            $modelRadicadoHijo->idTrdDepeUserTramitador = $modelRadicadoHijo->idTrdDepeUserTramitador;                            
                            $modelRadicadoHijo->isRadicado = 1;
                            $modelRadicadoHijo->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
                            $modelRadicadoHijo->creacionRadiRadicado = date("Y-m-d H:i:s");

                            /** 
                             * En los campos de usuario creador y dependencia creador van a quedar los datos del usuario que genero el radicado
                             * y el stiker de firma fisica.
                            */
                            $usuarioDepe = User::findOne(['id'=>Yii::$app->user->identity->id]);
                            $modelRadicadoHijo->user_idCreador = Yii::$app->user->identity->id;
                            $modelRadicadoHijo->idTrdDepeUserCreador = $usuarioDepe->idGdTrdDependencia;                           

                            if(!$modelRadicadoHijo->save()) {
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => $modelRadicadoHijo->getErrors(),
                                    'dataUpdate' => [],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                            $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'signingProcess']);
                            $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                            $observacion = Yii::$app->params['eventosLogTextRadicado']['procesoFirmaFisicaDescargaEtiqueta'].' '.$modelRadicadoHijo->numeroRadiRadicado;

                            /***    Log de Radicados  ***/
                            HelperLog::logAddFiling(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $idRadiRadicadoRequest, //Id radicado
                                $idTransacion,
                                $observacion, //texto para almacenar en el evento
                                $modelRadicado,
                                array() //No validar estos campos
                            );
                            /***  Fin  Log de Radicados  ***/
                        }

                        $modelUseEnd = $modelRadicadoHijo;
                        $numeroRadicado = $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelRadicadoHijo->numeroRadiRadicado, $modelRadicadoHijo->idCgTipoRadicado,$modelRadicadoHijo->isRadicado);
                        $fechaCreacionRadicado = $modelRadicadoHijo->creacionRadiRadicado;

                    } else {
                        $modelUseEnd = $modelRadicado;
                        $numeroRadicado = $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado,$modelRadicado->isRadicado);
                        $fechaCreacionRadicado = $modelRadicado->creacionRadiRadicado;
                    }
                }

                $path =  Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/sticker' . $numeroRadicado . '.png';
                $stickersArray[] = ['path' => $path, 'numeroRadicado' => $numeroRadicado];

                //Imagen
                $image = imagecreate($sizeX, $sizeY);
             
                //Colores del codigo de barras
                imagecolorallocate($image, 255, 255, 255);
                $black = imagecolorallocate($image, 0, 0, 0);
                
                if(in_array('rutaLogo', $etiquetasArray)) {
                    // //El logo debe ser de 300 x 300px
                    $logoPath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogoPrint'];                     
                    $logo = imagecreatefrompng($logoPath);                       
                        
                        $w = 40; //ancho
                        $h = 150; //alto
                        
                        //Crear una nueva imagen de color verdadero
                        $backgroundLogo = imagecreatetruecolor($w, $h);

                        imagecopyresampled($backgroundLogo, $logo, 0, 0, 0, 0, 30, 145, 60, 350);
                        $white = imagecolorallocate($backgroundLogo, 255, 255, 255);
                        $negro = imagecolorallocate($backgroundLogo, 0, 0, 0);
                        imagecolortransparent($backgroundLogo, $negro); 
                        imagefill($backgroundLogo, 0, 0, $white);
                        imagecopy($image, $backgroundLogo, 5, 3, 0, 0, $w, $h);              
                }

                if(in_array('codigoBarras', $etiquetasArray)) {
                    
                    $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcode.png';

                    $barcode = new BarcodeGeneratorPNG();
                    /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                    $barcode = $barcode->getBarcode($modelRadicado->numeroRadiRadicado, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                    file_put_contents($pathBarcode  , $barcode);                     
                        
                    $barcodeImage = imagecreatefrompng($pathBarcode);   
                    imagecopy($image, $barcodeImage, 45, 5, 0, 0, 542, 30);
                        
                    $y += 50;
                }

                if(in_array('numeroRadiRadicado', $etiquetasArray)) {

                    $text = "Rad N°: ". $numeroRadicado;

                    if(in_array('fechaRadicacion', $etiquetasArray)) {
                        $text .= " - Fecha rad: ". $fechaCreacionRadicado;
                    }

                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                    
                } elseif(in_array('fechaRadicacion', $etiquetasArray)) {
                    $text = "Fecha rad: ". $fechaCreacionRadicado;
                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                }

                if(in_array('user_idCreador', $etiquetasArray)) {
                    $modelUserDetalle = UserDetalles::find()
                        ->where(['idUser' => $modelUseEnd->user_idCreador])
                        ->one();

                    $text = "Usu Radicador: ".$modelUserDetalle->nombreUserDetalles. " ".$modelUserDetalle->apellidoUserDetalles;
                    
                    if(in_array('idTrdDepeUserTramitador', $etiquetasArray)){
                        $text .=  " - Dep: ". $modelUseEnd->idTrdDepeUserCreador0->nombreGdTrdDependencia;
                    }

                    if($items > 6){
                        $text = substr($text, 0, 72).'.';
                    } else {
                        $text = substr($text, 0, 64).'.';
                    }

                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;

                } elseif (in_array('idTrdDepeUserTramitador', $etiquetasArray)){
                    $text =  "Dep: ". $modelUseEnd->idTrdDepeUserCreador0->nombreGdTrdDependencia;

                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                }

                if(in_array('descripcionAnexos', $etiquetasArray)) {
                    $text = "Descripción Anexos: ".$modelRadicado->descripcionAnexoRadiRadicado;
                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                }

                //Determinar si es cliente o usuario
                $modelRemitente = RadiRemitentes::find()
                    ->where(['idRadiRadicado' => $idRadiRadicadoRequest])
                    ->one();

                if($modelRemitente->idTipoPersona == Yii::$app->params['tipoPersonaText']['funcionario']){

                    $modelUser = User::find()
                        ->where(['id' => $modelRemitente->idRadiPersona])
                        ->one();
                    $dependenciaModel = GdTrdDependencias::find()
                        ->where(['idGdTrdDependencia' => $modelUser->idGdTrdDependencia])
                        ->one();
                    # Datos del funcionario
                    $nombreRemitente = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;

                    $direccionRemitente = $dependenciaModel->nombreGdTrdDependencia;

                } else {

                    $modelCliente = Clientes::find()
                        ->where(['idCliente' => $modelRemitente->idRadiPersona])
                        ->one();
                    #Datos del cliente
                    $nombreRemitente = $modelCliente->nombreCliente;
                    $direccionRemitente = $modelCliente->direccionCliente;
                }

                if(in_array('nombreCliente', $etiquetasArray)) {
                    $text = "Remitente: ".$nombreRemitente;
                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                }

                if(in_array('direccionCliente', $etiquetasArray)) {
                    $text = "Dirección: ". $direccionRemitente;
                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                } 
               

                if(in_array('asuntoRadiRadicado', $etiquetasArray)) {
                    $text = substr("Asunto: ". $modelRadicado->asuntoRadiRadicado, 0, 68);
                    imagettftext($image, floor($fontSize), $rotation ,floor($x), floor($y), $black, $font, $text);
                    $y += $itemSize;
                }

                $text = substr( "Dirección: Carrera 10 No. 24-55 Tel: (57) 601 3415566", 0, 68);
                imagettftext($image, '8', $rotation ,floor($x), floor($y), $black, $font, $text);
                $y += $itemSize;

                if(!imagepng($image, $path)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [ Yii::t('app','filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                
                //Eliminar imagen del codigo de barras
                if(isset($pathBarcode) && $pathBarcode=!null) {
                    unlink(Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcode.png');
                }

                $arrayArchivos = [];
                foreach($stickersArray as $sticker) {
                    if(file_exists($sticker['path'])) {
                        $nombreArchivo = explode('/', $sticker['path']);
                        $nombreArchivo = end($nombreArchivo);

                        $arrayArchivos =  [
                            'dataFile' => base64_encode(file_get_contents($sticker['path'])),
                            'fileName' =>  $nombreArchivo
                        ];
                        
                        //Eliminar archivo en el servidor
                        unlink($sticker['path']);

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['printSticker'] . $sticker['numeroRadicado'],
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/

                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [ Yii::t('app','fileDonotDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        $return = HelperEncryptAes::encrypt($response, true);
                    }
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successPrintStickerOne'),
                    'data' => [], // data
                    'dataFile' => $arrayArchivos['dataFile'],
                    'fileName' => $arrayArchivos['fileName'],
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
                    case 'estadoRadiRadicado':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                    break;
                    case 'idTrdTipoDocumental':                                        
                        $dataLog .= $labelModel[$key].': '.$model->trdTipoDocumental->nombreTipoDocumental.', ';
                    break; 
                    case 'user_idCreador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->userIdCreador->userDetalles->nombreUserDetalles." ".$model->userIdCreador->userDetalles->apellidoUserDetalles.', ';
                    break; 
                    case 'idTrdDepeUserCreador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->idTrdDepeUserCreador0->nombreGdTrdDependencia.', ';
                    break; 
                    case 'user_idTramitador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->userIdTramitador->userDetalles->nombreUserDetalles." ".$model->userIdTramitador->userDetalles->apellidoUserDetalles.', ';
                    break; 
                    case 'idTrdDepeUserTramitador':                                        
                        $dataLog .= $labelModel[$key].': '.$model->idTrdDepeUserTramitador0->nombreGdTrdDependencia.', ';
                    break; 
                    case 'user_idTramitadorOld':                                        
                        $dataLog .= $labelModel[$key].': '.$model->userIdTramitadorOld->userDetalles->nombreUserDetalles." ".$model->userIdTramitadorOld->userDetalles->apellidoUserDetalles.', ';
                    break;                    
                    case 'idCgTipoRadicado':                                        
                        $dataLog .= $labelModel[$key].': '.$model->cgTipoRadicado->nombreCgTipoRadicado.', ';
                    break; 
                    case 'idCgMedioRecepcion':                                        
                        $dataLog .= $labelModel[$key].': '.$model->cgMedioRecepcion->nombreCgMedioRecepcion.', ';
                    break;                     
                    case 'numeroRadiRadicado':
                        if ((boolean) $model->isRadicado == true) {
                            $dataLog .= $labelModel[$key].': '.$value.', ';
                        } else {
                            $dataLog .= 'Número de consecutivo temporal: '.$value.', ';
                        }
                    break;
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }

            return $dataLog;
        }
    } 

    protected function findRadiRadicadosHijosModel($id)
    {
        //$modelsRadicados = [];

        if (($model = RadiRadicados::findOne($id)) !== null) {
    
            //$modelsRadicados[] = $model;

            if($model->idRadiRadicadoPadre !== null){

                // Si lo que llega es el id de un radicado hijo, se extrae el id del radicado padre saber el modelo.
                $modelPadre = RadiRadicados::findOne($model->idRadiRadicadoPadre);
                //$modelsRadicados[] = $modelPadre;

            }else{

                //Cuando lleva el id del radicado padre, se consulta los radicados hijos que existan para pocesar el modelo
                $modelHijos = RadiRadicados::findAll(['idRadiRadicadoPadre' => $id]);
                
                if($modelHijos){
                    foreach($modelHijos as $valor => $modelHijosUno){

                        //Inicio de transacción en bd 
                        $transaction = Yii::$app->db->beginTransaction();

                        // Asignar tramitador actual como tramitador anterior del radicado, antes de guardar el registro del usuario seleccionado desde front
                        $modelHijosUno->user_idTramitadorOld = $model->user_idTramitadorOld;

                        // Asignar nuevo tramitador del radicado
                        $modelHijosUno->idTrdDepeUserTramitador = $model->idTrdDepeUserTramitador;
                        $modelHijosUno->user_idTramitador = $model->user_idTramitador;

                        if($modelHijosUno->save()){
                            $transaction->commit();
                            return true;
                        }else{
                            $transaction->rollBack();
                            return false;
                        }
                        // $modelsRadicados[] = $modelHijosUno;
                    }
                }
            }            

            //return $modelsRadicados;
        }
    }

}

