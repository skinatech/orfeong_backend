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
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperNotification;

use api\models\RadiRadicados;
use api\models\CgTransaccionesRadicados;

use api\modules\version1\controllers\correo\CorreoController;
use api\models\User;
use api\models\RadiInformados;
use api\models\UserDetalles;

use api\components\HelperQueryDb;
use api\models\Clientes;
use api\models\RadiInformadoCliente;

/**
 * InformadosController implements the CRUD actions for RadiInformados model.
 */
class InformadosController extends Controller
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
                    'copy' => ['POST'],                  
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }


    /* Copia correos masivamente a los usuarios seleccionados en el form */
    public function actionCopy() {

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

                $copiado = false;

                $saveDataValid = true;
                $errors = [];
                $arrayOldInformados = [];
                $arrayUserInformar = [];

                // Array de clientes informados
                $arrayClientInformar = [];
                $oldInformedClient = [];

                $transaction = Yii::$app->db->beginTransaction();

                # Usuarios internos a copiar informado
                $idUsuariosInfo = $request['data']['idUsuariosInfo'];
                if ($idUsuariosInfo == '' || $idUsuariosInfo == null) {
                    $idUsuariosInfo = [];
                }

                # Usuarios externos
                $idExternalUsers = [];

                if(isset($request['data']['idUsuariosExternos']))
                    $idExternalUsers = $request['data']['idUsuariosExternos'];

                # Observación del formulario
                $observacion = $request['data']['observacion'];

                /** Validar que no se pueda informar a si mismo */
                if (in_array(Yii::$app->user->identity->id, $idUsuariosInfo)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorInformarYourself')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                /** Validar que no se pueda informar a si mismo */
                                
                # Consulta la accion de la transaccion de copia informados para obtener su id
                $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'copyInformaded']);
                $idTransaccion = $modelTransaccion->idCgTransaccionRadicado;

                # 1 Consultar datos de los usuarios a informar
                $tableNameUser = User::tableName() . ' AS U';
                $tableNameUserDetalles = UserDetalles::tableName() . ' AS UD';

                $users = (new \yii\db\Query())
                    ->select(['U.id', 'U.email', 'UD.nombreUserDetalles', 'UD.apellidoUserDetalles'])
                    ->from($tableNameUser);
                    //->innerJoin($tableNameUserDetalles, '`u`.`id` = `ud`.`idUser`')
                    $users = HelperQueryDb::getQuery('innerJoinAlias', $users, $tableNameUserDetalles, ['U' => 'id', 'UD' => 'idUser']);
                    $users = $users->where(['IN', 'U.id', $idUsuariosInfo])
                ->all();  
                
                # 2 Consultar los datos del cliente a informar
                $modelClient = Clientes::find()
                    ->select(['idCliente', 'correoElectronicoCliente', 'nombreCliente'])
                    ->where(['IN', 'idCliente', $idExternalUsers])
                ->all();

                # 3 Obtiene los datos de los radicados a procesar
                $idsRadicados = []; // [idRadiRadicado]
                foreach($request['ButtonSelectedData'] as $radicadoItem){
                    $idsRadicados[] = $radicadoItem['id'];
                }

                $radiRadicados = RadiRadicados::find()
                    ->select(['idRadiRadicado', 'numeroRadiRadicado', 'asuntoRadiRadicado', 'isRadicado'])
                    ->where(['IN', 'idRadiRadicado', $idsRadicados])
                ->all();

                # 4 Armando array con ids de radicados con los usuarios que ya fueron informados
                $radiInformados = RadiInformados::find()
                    ->select(['idRadiRadicado', 'idUser'])
                    ->where(['IN', 'idRadiRadicado', $idsRadicados])
                    ->andWhere(['IN', 'idUser', $idUsuariosInfo])
                    ->andWhere(['estadoRadiInformado' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                $arrayRadiInformados = []; // idRadiRadicado => [idUserInformado, idUserInformado]
                foreach ($radiInformados as $value) {
                    $arrayRadiInformados[$value['idRadiRadicado']][] = $value['idUser'];
                }

                # 5 Armando array con ids de radicados con los clientes que ya fueron informados
                $modelInformedClient = RadiInformadoCliente::find()
                    ->select(['idRadiRadicado', 'idCliente'])
                    ->where(['IN', 'idRadiRadicado', $idsRadicados])
                    ->andWhere(['IN', 'idCliente', $idExternalUsers])
                    ->andWhere(['estadoRadiInformadoCliente' => Yii::$app->params['statusTodoText']['Activo']])
                ->all();

                $arrayInformedClient = []; 
                foreach ($modelInformedClient as $value) {
                    $arrayInformedClient[$value['idRadiRadicado']][] = $value['idCliente'];
                }

                # 6 Procesar Radicados
                foreach ($radiRadicados as $radicadoInfo) {

                    $idRadicado = $radicadoInfo['idRadiRadicado'];

                    #Recorrer los usuarios seleccionados
                    foreach ($users as $userInfo) {
                        if( isset($arrayRadiInformados[$idRadicado]) && in_array($userInfo['id'], $arrayRadiInformados[$idRadicado]) )  {

                            /* Ya fué informado anteriormente */
                            $arrayOldInformados[$userInfo['email']]['usuario'] = $userInfo['nombreUserDetalles'] . ' ' . $userInfo['apellidoUserDetalles'];
                            $arrayOldInformados[$userInfo['email']]['numerosRadicados'][$radicadoInfo['numeroRadiRadicado']] = $radicadoInfo['numeroRadiRadicado'];
                            $arrayOldInformados[$userInfo['email']]['typesRadiOrTmp'][] = ((boolean) $radicadoInfo['isRadicado'] == true) ? 'radicado' : 'temporal';

                        } else {

                            /** NO ha sido informado */
                            $modelInformado = new RadiInformados();
                            $modelInformado->idUser = $userInfo['id'];
                            $modelInformado->idRadiRadicado = $idRadicado;

                            if (!$modelInformado->save()) {
                                // Valida false ya que no se guarda
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelInformado->getErrors());
                                break;

                            } else {

                                if ((boolean) $radicadoInfo['isRadicado'] == true) {
                                    $eventLogTextNewInformado = Yii::$app->params['eventosLogText']['newInformado'];
                                    $msgNumoRadi = ', Número radicado: ';
                                    $informFiling = 'informFiling';
                                } else {
                                    $eventLogTextNewInformado = Yii::$app->params['eventosLogText']['newInformadoTmp'];
                                    $msgNumoRadi = ', Número de consecutivo temporal: ';
                                    $informFiling = 'informFilingTmp';
                                }

                                /** Log de Radicados */    
                                HelperLog::logAddFiling(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $idRadicado, //Id radicado
                                    $idTransaccion,
                                    //$observacion, //observación 
                                    $eventLogTextNewInformado . ' al usuario ' . $userInfo['nombreUserDetalles'] . ' ' . $userInfo['apellidoUserDetalles'] . ' - ' . $observacion, //observación 
                                    $modelInformado,
                                    array() //No validar estos campos
                                );
                                /*** Fin log Radicados */

                                $dataNew = 'Nombre de usuario informado: ' . $userInfo['nombreUserDetalles'] . ' ' . $userInfo['apellidoUserDetalles']
                                    . $msgNumoRadi . $radicadoInfo['numeroRadiRadicado']
                                ;

                                /** Log de auditoria */
                                HelperLog::logAdd(
                                    true,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    $eventLogTextNewInformado . $radicadoInfo['numeroRadiRadicado'] . ' al usuario ' . $userInfo['nombreUserDetalles'] . ' ' . $userInfo['apellidoUserDetalles'], //texto para almacenar en 
                                    '', //data old string
                                    $dataNew, //$info['data'], //data string
                                    array() //No validar estos campos
                                );
                                /** Fin Log de auditoria */

                                /***  Notificacion  ***/
                                HelperNotification::addNotification(
                                    Yii::$app->user->identity->id, //Id user creador
                                    $modelInformado->idUser, // Id user notificado
                                    Yii::t('app','messageNotification')[$informFiling].$radicadoInfo['numeroRadiRadicado'], //Notificacion
                                    Yii::$app->params['routesFront']['viewRadicado'], // url
                                    $idRadicado // id radicado
                                );
                                /***  Fin Notificacion  ***/

                                $arrayUserInformar[$userInfo['email']]['infoUser'] = $userInfo;
                                $arrayUserInformar[$userInfo['email']]['infoRadicados'][$radicadoInfo['numeroRadiRadicado']] = $radicadoInfo;
                                $arrayUserInformar[$userInfo['email']]['typesRadiOrTmp'][] = ((boolean) $radicadoInfo['isRadicado'] == true) ? 'radicado' : 'temporal';

                            }

                        }
                    }

                    # Recorrer los clientes seleccionados
                    foreach ($modelClient as $dataClient) {
                        if( isset($arrayInformedClient[$idRadicado]) && in_array($dataClient['idCliente'], $arrayInformedClient[$idRadicado]) )  {

                            /* Ya fué informado anteriormente */
                            $oldInformedClient[$dataClient['correoElectronicoCliente']]['cliente'] = $dataClient['nombreCliente'];
                            $oldInformedClient[$dataClient['correoElectronicoCliente']]['numerosRadicados'][$radicadoInfo['numeroRadiRadicado']] = $radicadoInfo['numeroRadiRadicado'];
                            $oldInformedClient[$dataClient['correoElectronicoCliente']]['typesRadiOrTmp'][] = ((boolean) $radicadoInfo['isRadicado'] == true) ? 'radicado' : 'temporal';

                        } else {

                            /** NO ha sido informado */
                            $modelInformedClients = new RadiInformadoCliente();
                            $modelInformedClients->idCliente = $dataClient['idCliente'];
                            $modelInformedClients->idRadiRadicado = $idRadicado;

                            if (!$modelInformedClients->save()) {
                                // Valida false ya que no se guarda
                                $saveDataValid = false;
                                $errors = array_merge($errors, $modelInformedClients->getErrors());
                                break;

                            } else {

                                if ((boolean) $radicadoInfo['isRadicado'] == true) {
                                    $eventLogTextNewInformado = Yii::$app->params['eventosLogText']['newInformado'];
                                    $msgNumoRadi = ', Número radicado: ';
                                } else {
                                    $eventLogTextNewInformado = Yii::$app->params['eventosLogText']['newInformadoTmp'];
                                    $msgNumoRadi = ', Número de consecutivo temporal: ';
                                }

                                /** Log de Radicados */    
                                HelperLog::logAddFiling(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $idRadicado, //Id radicado
                                    $idTransaccion, 
                                    $eventLogTextNewInformado . ' al cliente ' . $dataClient['nombreCliente']. ' - ' . $observacion, //observación 
                                    $modelInformedClients,
                                    array() //No validar estos campos
                                );
                                /*** Fin log Radicados */

                                $dataNew = 'Nombre del cliente informado: ' . $dataClient['nombreCliente'].
                                           $msgNumoRadi . $radicadoInfo['numeroRadiRadicado'];

                                /** Log de auditoria */
                                HelperLog::logAdd(
                                    true,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    $eventLogTextNewInformado .'Nº '. $radicadoInfo['numeroRadiRadicado'] . ' al cliente ' . $dataClient['nombreCliente'], //texto para almacenar en 
                                    '', //data old string
                                    $dataNew, //data string
                                    array() //No validar estos campos
                                );
                                /** Fin Log de auditoria */

                                $arrayClientInformar[$dataClient['correoElectronicoCliente']]['infoCliente'] = $dataClient;
                                $arrayClientInformar[$dataClient['correoElectronicoCliente']]['infoRadicados'][$radicadoInfo['numeroRadiRadicado']] = $radicadoInfo;
                                $arrayClientInformar[$dataClient['correoElectronicoCliente']]['typesRadiOrTmp'][] = ((boolean) $radicadoInfo['isRadicado'] == true) ? 'radicado' : 'temporal';
                            }

                        }
                    }
                }

                # Información de usuario logueado
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombresLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                # 7 Enviar correo a los usuarios que no se habia informado anteriormente
                foreach ($arrayUserInformar as $userInformar) {

                    $infoRadicados = $userInformar['infoRadicados'];
                    $email = $userInformar['infoUser']['email'];

                    $numRadicados = [];
                    $asuntos = [];

                    foreach ($infoRadicados as $infoRadicado) {
                        $numRadicados[] = $infoRadicado['numeroRadiRadicado'];
                        $asuntos[] = $infoRadicado['asuntoRadiRadicado'];
                    }

                    $numRadicado = implode(', ', $numRadicados);  
                    $asunto = implode(', ', $asuntos);  

                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    if ( count($infoRadicados) > 1) {

                        if (in_array('radicado', $userInformar['typesRadiOrTmp']) && in_array('temporal', $userInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformadosAll';
                            $textStart = 'textBodyNotificacionInformadosAll';
                            $subject = 'setSubjectNotificacionInformadoGroupAll'; //Ya esta traducida en la funcion sendEmail
                        } elseif (in_array('radicado', $userInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformados';
                            $textStart = 'textBodyNotificacionInformados';
                            $subject = 'setSubjectNotificacionInformadoGroup'; //Ya esta traducida en la funcion sendEmail
                        } else {
                            $headText = 'headMailTextNotificacionInformadosTmp';
                            $textStart = 'textBodyNotificacionInformadosTmp';
                            $subject = 'setSubjectNotificacionInformadoGroupTmp'; //Ya esta traducida en la funcion sendEmail
                        }

                        $nameButtonLink = 'Ir al sistema'; // Esta variable sera traducida
                        $link = Yii::$app->params['ipServer'] . 'dashboard';

                    } else {

                        if (in_array('radicado', $userInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformado';
                            $textStart = 'textBodyNotificacionInformado';
                            $nameButtonLink = 'buttonLinkRadicado'; // Esta variable sera traducida
                            $subject = 'subjectNotificacionInformado'; //Ya esta traducida en la funcion sendEmail
                        } else {
                            $headText = 'headMailTextNotificacionInformadoTmp';
                            $textStart = 'textBodyNotificacionInformadoTmp';
                            $nameButtonLink = 'buttonLinkRadicadoTmp'; // Esta variable sera traducida
                            $subject = 'subjectNotificacionInformadoTmp'; //Ya esta traducida en la funcion sendEmail
                        }

                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode( end($infoRadicados)['idRadiRadicado']) );
                        $link = Yii::$app->params['ipServer'] . 'filing/filing-view/' . $dataBase64Params;
                    }

                    $headMailText = Yii::t('app', $headText); 
                    
                    $textBody  = Yii::t('app', $textStart, [
                        'NoRadicado'    => $numRadicado,
                        'Asunto'        => $asunto,
                        'Username'      => $nombresLogged,
                        'NameDependencia'   => $dependenciaLogged,
                    ]);

                    $bodyMail = 'radicacion-html'; 

                    $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                }

                # 8 Enviar correo a los clientes que no se habia informado anteriormente
                foreach ($arrayClientInformar as $dataClientInformar) {

                    $infoRadicados = $dataClientInformar['infoRadicados'];
                    $email = $dataClientInformar['infoCliente']['correoElectronicoCliente'];

                    $numRadicados = [];
                    $asuntos = [];

                    foreach ($infoRadicados as $infoRadicado) {
                        $numRadicados[] = $infoRadicado['numeroRadiRadicado'];
                        $asuntos[] = $infoRadicado['asuntoRadiRadicado'];
                    }

                    $numRadicado = implode(', ', $numRadicados);  
                    $asunto = implode(', ', $asuntos);  

                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    if ( count($infoRadicados) > 1) {

                        if (in_array('radicado', $dataClientInformar['typesRadiOrTmp']) && in_array('temporal', $dataClientInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformadosAll';
                            $textStart = 'textBodyNotificacionInformadosAll';
                            $subject = 'setSubjectNotificacionInformadoGroupAll'; //Ya esta traducida en la funcion sendEmail
                        } elseif (in_array('radicado', $dataClientInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformados';
                            $textStart = 'textBodyNotificacionInformados';
                            $subject = 'setSubjectNotificacionInformadoGroup'; //Ya esta traducida en la funcion sendEmail
                        } else {
                            $headText = 'headMailTextNotificacionInformadosTmp';
                            $textStart = 'textBodyNotificacionInformadosTmp';
                            $subject = 'setSubjectNotificacionInformadoGroupTmp'; //Ya esta traducida en la funcion sendEmail
                        }

                    } else {

                        if (in_array('radicado', $dataClientInformar['typesRadiOrTmp'])) {
                            $headText = 'headMailTextNotificacionInformado';
                            $textStart = 'textBodyNotificacionInformado';
                            $subject = 'subjectNotificacionInformado'; //Ya esta traducida en la funcion sendEmail
                        } else {
                            $headText = 'headMailTextNotificacionInformadoTmp';
                            $textStart = 'textBodyNotificacionInformadoTmp';
                            $subject = 'subjectNotificacionInformadoTmp'; //Ya esta traducida en la funcion sendEmail
                        }

                    }

                    $headMailText = Yii::t('app', $headText); 
                    
                    $textBody  = Yii::t('app', $textStart, [
                        'NoRadicado'    => $numRadicado,
                        'Asunto'        => $asunto,
                        'Username'      => $nombresLogged,
                        'NameDependencia'   => $dependenciaLogged,
                    ]);

                    $bodyMail = 'radicacion-html'; 

                    $envioCorreo = CorreoController::addFile($email, $headMailText, $textBody, $bodyMail, null, $subject, false);
                }

                # 9 Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();
                    
                    $arrayMensajes = [];

                    # Notificación a los usuarios nuevos informados
                    if (count($arrayUserInformar) == 1) {
                        $arrayMensajes[] = Yii::t('app', 'userInformado', [
                            'usuario' => end($arrayUserInformar)['infoUser']['nombreUserDetalles'] . ' ' . end($arrayUserInformar)['infoUser']['apellidoUserDetalles']
                        ]);

                    } elseif(count($arrayUserInformar) > 1) {

                        $usuarios = [];
                        foreach ($arrayUserInformar as $informado) {
                            $usuarios[] = $informado['infoUser']['nombreUserDetalles'] . ' ' . $informado['infoUser']['apellidoUserDetalles'];
                        }
                        $usuario = implode(', ', $usuarios);
                        
                        $arrayMensajes[] = Yii::t('app', 'usersInformados', [
                            'usuarios' => $usuario
                        ]);
                    }

                    # Notificación a los clientes nuevos informados
                    if (count($arrayClientInformar) == 1) {
                        $arrayMensajes[] = Yii::t('app', 'clientInformed', [
                            'cliente' => end($arrayClientInformar)['infoCliente']['nombreCliente']
                        ]);

                    } elseif(count($arrayClientInformar) > 1) {

                        $nombreCliente = [];
                        foreach ($arrayClientInformar as $informado) {
                            $nombreCliente[] = $informado['infoCliente']['nombreCliente'];
                        }
                        $cliente = implode(', ', $nombreCliente);
                        
                        $arrayMensajes[] = Yii::t('app', 'clientsInformed', [
                            'clientes' => $cliente
                        ]);
                    }

                    # Notificación a los usuarios que ya habian sido informados
                    foreach($arrayOldInformados as $informado){

                        if ($informado['numerosRadicados'] > 1) {

                            if (in_array('radicado', $informado['typesRadiOrTmp']) && in_array('temporal', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyInformadedAll';
                            } elseif (in_array('radicado', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyInformaded';
                            } else {
                                $mensajeTraducir = 'alreadyInformadedTmp';
                            }

                        } else {

                            if (in_array('radicado', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyInformadeds';
                            } else {
                                $mensajeTraducir = 'alreadyInformadedsTmp';
                            }

                        }
                        $stringRadicados = implode(', ', $informado['numerosRadicados']);
                        
                        $arrayMensajes2[] = Yii::t('app', $mensajeTraducir, [
                            'usuario' => $informado['usuario'],
                            'numeroRadicado' =>  $stringRadicados
                        ]);
                    }

                    # Notificación a los clientes que ya habian sido informados
                    foreach($oldInformedClient as $informado){

                        if ($informado['numerosRadicados'] > 1) {

                            if (in_array('radicado', $informado['typesRadiOrTmp']) && in_array('temporal', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyClientInformedAll';
                            } elseif (in_array('radicado', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyClientInformed';
                            } else {
                                $mensajeTraducir = 'alreadyClientInformedTmp';
                            }

                        } else {

                            if (in_array('radicado', $informado['typesRadiOrTmp'])) {
                                $mensajeTraducir = 'alreadyClientsInformed';
                            } else {
                                $mensajeTraducir = 'alreadyClientsInformedTmp';
                            }

                        }
                        $stringRadicados = implode(', ', $informado['numerosRadicados']);
                        
                        $arrayMensajes2[] = Yii::t('app', $mensajeTraducir, [
                            'cliente' => $informado['cliente'],
                            'numeroRadicado' =>  $stringRadicados
                        ]);
                    }

                    if(isset($arrayMensajes2)){
                        $arrayMensajes[] = implode('<br> ', $arrayMensajes2);
                    }
    
                    //Respuesta satisfactoria 
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $arrayMensajes,
                        'data' => [],
                        'status' => 200
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


    /* Función que envia notificación a los usuarios informados 
     * en el momento de finalizar el radicado. */
    public static function enviarNotificacionInformado($headMailText, $textBody, $idRadicado){
        
        $modelRadicado = self::findRadicadoModel($idRadicado);

         //Se obtiene los email de los usuarios informados
        $modelInformados = RadiInformados::find()
            ->where(['idRadiRadicado' => $modelRadicado->idRadiRadicado])
            ->andWhere(['estadoRadiInformado' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();       
       
        $idEmailArray = [];
        foreach($modelInformados as $informado){
            $idEmailArray[] = $informado->idUser;

            /***  Notificacion  ***/
            HelperNotification::addNotification(
                Yii::$app->user->identity->id, //Id user creador
                $informado->idUser, // Id user notificado
                Yii::t('app','messageNotification')['finishFile'].$modelRadicado->numeroRadiRadicado, //Notificacion
                Yii::$app->params['routesFront']['viewRadicado'], // url
                $modelRadicado->idRadiRadicado // id radicado
            );
            /***  Fin Notificacion  ***/
        }

        $modelUser = User::find()
            ->where(['IN', 'id', $idEmailArray])
            ->andWhere(['status' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();

        $emailArray = [];
        $users = '';
        foreach($modelUser as $userInformado){

            $emailArray[] = $userInformado->email;
            $users .= $userInformado->userDetalles->nombreUserDetalles . ' ' . $userInformado->userDetalles->apellidoUserDetalles . ' - ' . $userInformado->gdTrdDependencia->nombreGdTrdDependencia . ', ';
        }

        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelRadicado->idRadiRadicado));

        $bodyMail = 'radicacion-html';
        $asunto = 'subjectMailNotificacionFinalizacion';
        
        $envioCorreo = [];
        $envioCorreo['status'] = false;

        # Se itera los correos por los usuarios informados
        foreach($emailArray as $email){
            $envioCorreo = CorreoController::radicacion($email, $headMailText, $textBody, $bodyMail, $dataBase64Params, $asunto);
        }
        
        $envioCorreo['usuarios'] = $users;

        return $envioCorreo;
    }


    /** Función que envia notificación a los clientes informados 
     * en el momento de finalizar el radicado.
     * @param $headMailText [String] [Titulo del correo]
     * @param $textBody [String] [Cuerpo del correo]
     * @param $idRadicado [int] [Id del o los radicados]
     **/
    public static function sendClientInformed($headMailText, $textBody, $idRadicado){

        //Se obtiene los datos del cliente informado
        $modelInformed = RadiInformadoCliente::find()
            ->where(['idRadiRadicado' => $idRadicado])
            ->andWhere(['estadoRadiInformadoCliente' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();       
       
        $clientName = [];
        $emails = [];
        foreach($modelInformed as $dataInformed){
            $clientName[] = $dataInformed->idCliente0->nombreCliente;
            $emails[] = $dataInformed->idCliente0->correoElectronicoCliente;
        }

        $bodyMail = 'radicacion-html';
        $subject = 'subjectMailNotificacionFinalizacion';
        
        $envioCorreo = [];
        $envioCorreo['status'] = false;

        # Se itera los correos por los clientes informados
        foreach($emails as $email){
            $envioCorreo = CorreoController::addFile($email, $headMailText, $textBody, $bodyMail, null, $subject, false);
        }
        
        $envioCorreo['usuarios'] = implode(", ", $clientName);

        return $envioCorreo;
    }

    
    protected static function findRadicadoModel($id){
        if (($model = RadiRadicados::findOne($id)) !== null) {
            return $model;
        }
        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

}   
