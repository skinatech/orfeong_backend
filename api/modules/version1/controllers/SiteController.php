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

use api\components\HelperConsecutivo;
use api\components\HelperEncrypt;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperLog;
use api\components\HelperQueryDb;
use api\components\HelperUserMenu;
use api\components\HelperRadicacion;
use api\models\CgDiasNoLaborados;
use api\components\HelperNotification;
use api\models\CgGeneral;
use api\models\CgHorarioLaboral;
use api\models\CgTransaccionesRadicados;
use api\models\Clientes;
use api\models\GaHistoricoPrestamo;
use api\models\GdTrdDependencias;
use api\models\GdExpedientes;
use api\models\GaArchivo;
use api\models\GdHistoricoExpedientes;
use api\models\GdTrdTiposDocumentales;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\PasswordResetRequestForm;
use api\models\RadiRadicados;
use api\models\RadiRemitentes;
use api\models\TiposIdentificacion;
use api\models\UserDetalles;
use api\models\UserHistoryPassword;
use api\models\UserTipo;
use api\models\RadiRadicadosAsociados;
use api\models\RadiLogRadicados;
use api\models\RadiDocumentosPrincipales;
use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\radicacion\TransaccionesController;
use api\modules\version1\controllers\radicacion\RadicadosController;
use api\models\GdFirmasQr;
use api\models\CgTiposRadicados;
use api\models\CgConsecutivosRadicados;
use api\models\CsInicial;
use api\models\CsParams;
use common\models\LoginForm;
use common\models\User;
use api\models\PDFInfo;
use DateTime;
use Yii;
use yii\base\ErrorException;
use yii\base\InvalidArgumentException;
use yii\filters\VerbFilter;
use yii\web\Controller;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use yii\helpers\FileHelper;
use api\components\HelperPlantillas;
use Da\QrCode\QrCode;
use api\models\RadiRadicadoAnulado;
use api\models\InitCgEntidadesFirma;
use api\models\InitCgParamsFirma;
use api\models\InitCgConfigFirmas;
use api\models\InitCgCordenadasFirma;
use api\modules\version1\controllers\radicacion\AnulacionController;
use api\modules\version1\controllers\firmasCertificadas\initCgEntidadFirmaAndesController;
use api\modules\version1\controllers\firmasCertificadas\initCgEntidadFirmaOrfeoController;


/**** Pruebas Oracle ****/
/************************/
use api\models\TestOciJaime;

/**
 * Site controller
 */
class SiteController extends Controller
{

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'only' => ['login'],
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
                    'validate-access-token' => ['POST'],
                    'user-status-licensing' => ['POST'],
                    'cs-inicial' => ['POST'],
                    'cs-params' => ['POST'],
                    'login' => ['POST'],
                    'logout' => ['GET'],
                    'signup' => ['POST'],
                    'request-password-reset' => ['POST'],
                    'validate-token-password' => ['POST'],
                    'reset-password' => ['POST'],
                    'nivel-geografico2-index' => ['GET'],
                    'nivel-geografico3-index' => ['GET'],
                    'next-to-expire' => ['GET'],
                    'pqrs-next-expire' => ['GET'],
                    'expired-pqrs' => ['GET'],
                    'loans-next-to-expire' => ['GET'],
                    'view-qr' => ['POST'],

                    /**** Pruebas Oracle ****/
                    /************************/
                    'test-list' => ['GET'],
                    'test-insert' => ['POST'],
                    'test-update' => ['POST'],
                    'test-delete' => ['GET'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionValidateAccessToken()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');
        $decrypted = HelperEncryptAes::decrypt($jsonSend);

        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];

        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];

            return HelperEncryptAes::encrypt($response);
        }

        $model = User::findIdentityByAccessToken($request['data']['accessToken']);
        if ($model) {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'sesionIniciada'),
                'data' => '',
                'status' => 200,
            ];

            return HelperEncryptAes::encrypt($response);
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => '',
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];

            return HelperEncryptAes::encrypt($response);
        }
    }

    public function actionCsInicial() {
        //*** Inicio desencriptación GET ***//
        $jsonSend = Yii::$app->request->post('jsonSend');
        $response = [];
        $decrypted = HelperEncryptAes::decrypt($jsonSend);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
        //*** Fin desencriptación GET ***//

        $modelCsInicial = CsInicial::find()
            ->where(['llaveCsInicial' => $request['csInicial']])
            ->andWhere(['estadoCsInicial' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();

        if ($modelCsInicial) {
            $response = [
                'message'           => 'Ok',
                'dataStatus'        => true,
                'llaveCsInicial'    => $modelCsInicial->llaveCsInicial,
                'valorCsInicial'    => $modelCsInicial->valorCsInicial,
                'creacionCsInicial' => $modelCsInicial->creacionCsInicial,
                'estadoCsInicial'   => $modelCsInicial->estadoCsInicial,
                'status'            => 200,
            ];

        } else {
            $response = [
                'message' => 'Ok',
                'dataStatus'    => false,
            ];
        }

        Yii::$app->response->statusCode = 200;
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionCsParams() {
        //*** Inicio desencriptación GET ***//
        $jsonSend = Yii::$app->request->post('jsonSend');
        $decrypted = HelperEncryptAes::decrypt($jsonSend);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
        //*** Fin desencriptación GET ***//

        $dataParams = [];
        $modelCsParams = CsParams::find()
            ->where(['IN','llaveCsParams', $request])
            ->andWhere(['estadoCsParams' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();
        if ($modelCsParams) {
            foreach($modelCsParams as $key => $value) {
                $dataParams[] = [
                    'llaveCsParams'    => $value->llaveCsParams,
                    'valorCsParams'    => $value->valorCsParams,
                    'creacionCsParams' => $value->creacionCsParams,
                    'estadoCsParams'   => $value->estadoCsParams,
                ];
            }
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message'    => 'Ok',
            'dataStatus' => $modelCsParams ? true : false,
            'data'       => $dataParams,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    public function actionUserStatusLicensing() {
        $jsonSend = Yii::$app->request->post('jsonSend');
        $decrypted = HelperEncryptAes::decrypt($jsonSend);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response);
        }

        $model = User::find()
            ->where(['username' => $request['username']])
            ->one();

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => Yii::t('app', 'successTokenValido'),
            'data' => $model ? $model->licenciaAceptada : 0,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response);
    }

    /**
     * Logs in a user.
     * Logueo de usuarios
     * @return mixed
     */
    public function actionLogin()
    {

        if (!Yii::$app->user->isGuest) {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'errorSesionYaIniciada')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];

            return HelperEncryptAes::encrypt($response);
        }

        $jsonSend = Yii::$app->request->post('jsonSend');
        $decrypted = HelperEncryptAes::decrypt($jsonSend);

        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];

        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];

            return HelperEncryptAes::encrypt($response);
        }

        // Consultar si el usuario ya excedio el limite de intentos de logueo
        $modelUser = User::find()
            ->where(['username' => $request['username']])
        ->one();

        if ($modelUser != null) {
            
            if ($modelUser->intentos >= Yii::$app->params['maxIntentosLogueo']) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'errorMaxIntentosLogueo')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

            if($modelUser['idUserTipo'] == Yii::$app->params['tipoUsuario']['Externo']){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => Yii::t('app', 'userNoExistente')],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

        }
        //

        $model = new LoginForm();
        $model->attributes = $request;
        //$model->username = $request['email'];
        $model->username = $request['username'];

        // Valida si el modelo no es vacio
        if (isset($modelUser)) {
            //Validacion usuario LDAP
            if ($modelUser->ldap) {

                // Se conecta a ldap
                //require 'autenticaLDAP.php';
                $configLdap = $this->configLdap($model);

                //Valida el usuario en el directorio activo
                if ($configLdap['status'] == true) {
                    $model->ldap = true; // Estableciendo la variable de validación de login por directorio activo de ldap en true

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'Incorrect username or password ldap')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }

            }

            //Inicia sesión verificando con la DB de catalina
            if ($model->login()) {

                # Configuracion general de los días limites de cambio de contraseña
                $configGeneral = ConfiguracionGeneralController::generalConfiguration();
                # Tiempo de inactividad de sesión en minutos
                $tiempoInactividadCgGeneral = $configGeneral['data']['tiempoInactividadCgGeneral'];

                //Solo puede modificar la contraseña los usuarios que no pertenecen a ldap.
                if (!$modelUser->ldap) {

                    /// Validar que el último cambio de contraseña sea menor a la cantidad de días configurados: 90 Días
                    $created_at = date("Y-m-d", Yii::$app->user->identity->created_at); // Transformando fecha (int) a formato Y-m-d para poder sumar días con la función

                    $userHistoryPassword = UserHistoryPassword::find()
                        ->select(['creacionUserHistoryPassword'])
                        ->where(['idUser' => Yii::$app->user->identity->id])
                        ->orderBy(['idUserHistoryPassword' => SORT_DESC])
                        ->limit(1)
                        ->one();

                    # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                    if($configGeneral['status']){

                        # Dias límite para el cambio de contraseña
                        $daysChangePassword = $configGeneral['data']['diasLimiteCgGeneral'];

                    } else {

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [$configGeneral['message']]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    if ($userHistoryPassword != null) {
                        $fechaCambioClave = date("Y-m-d", strtotime($userHistoryPassword->creacionUserHistoryPassword . " + " . $daysChangePassword . ' days'));
                    } else {
                        $fechaCambioClave = date("Y-m-d", strtotime($created_at . " + " . $daysChangePassword . ' days'));
                    }

                    //Compara la fecha de caducación de la contraseña con la actual
                    $fechaActual = date('Y-m-d');
                    if ($fechaCambioClave <= $fechaActual) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'valChangePassword')],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                            'statusChangePassword' => true,
                        ];
                        return HelperEncryptAes::encrypt($response);
                    }
                }

                // reinicia el contador de intentos de logueo a 0
                $modelUser->intentos = 0;
                $modelUser->licenciaAceptada = 1;
                $modelUser->save();

                $dataUser = Yii::$app->user->identity;
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataUser->id)),
                );

                $data = array(
                    'data' => $dataBase64Params,
                    'idDataCliente' => $dataUser->id,
                    'username' => $dataUser->username,
                    'email' => $dataUser->email,
                    'fechaVenceToken' => $dataUser->fechaVenceToken,
                    'idRol' => $dataUser->idRol,
                    'idUserTipo' => $dataUser->idUserTipo,
                    'accessToken' => $dataUser->accessToken,
                    'ldap' => $dataUser->ldap,
                    'tiempoInactividadCgGeneral' => $tiempoInactividadCgGeneral,
                    'radiSendReplyEMail' => Yii::$app->params['radiSendReplyEMail'],
                );

                // modelo que se genera para Log de Auditoria
                $user_log = User::find()->where(["email" => $dataUser->email])->one();

                // dataMenu Contiene la estructura del menu, las operaciones de los usuarios enviados
                $dataMenu = HelperUserMenu::createUserMenu($user_log);

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'sesionIniciada'),
                    'data' => $data,
                    'dataMenu' => $dataMenu,
                    'status' => 200,
                ];

                /***    Inicio log Auditoria   ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['Login'], //texto para almacenar en el evento
                    [],
                    [$user_log], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                /* Validar si el usuario logueado tiene Notificaciones Pendientes */
                TransaccionesController::notificacionSchedule(Yii::$app->user->identity->id);

                return HelperEncryptAes::encrypt($response);

            } else {

                //Sumar un intento de logueo al usuario
                $sumarIntento = User::find()->where(['username' => $request['username']])
                    ->one();
                if ($sumarIntento != null) {
                    $sumarIntento->intentos = (int) $sumarIntento->intentos + 1;
                    $sumarIntento->save();
                }
                //

                $model->password = '';
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $model->getErrors(),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return HelperEncryptAes::encrypt($response);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => Yii::t('app', 'Incorrect username or password')],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    /**
     * Signs user up.
     * Registro de usuario
     * @return mixed
     */
    public function actionSignup()
    {
        $jsonSend = Yii::$app->request->post('jsonSend');
        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];

            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación POST ***//

            $saveDataValid = true;
            $user = new User();
            $userDetalles = new UserDetalles();

            $transaction = Yii::$app->db->beginTransaction();
            $user->attributes = $request;
            //$user->username = $request['email'];
            $user->username = $request['username'];
            $user->idRol = Yii::$app->params['idRolDefault'];
            $user->idUserTipo = Yii::$app->params['idUserTipoDefault'];
            $user->status = yii::$app->params['statusTodoText']['Activo'];
            $password = $user->generatePassword();
            $user->setPassword($password);
            $user->generateAuthKey();
            $user->accessToken = $user->generateAccessToken();

            if ($user->save()) {
                // $userDetalles->attributes = $request;
                // $userDetalles->idUser = $user->id;
                // $userDetalles->creacionUserDetalles = date('Y-m-d H:i:s');

                // if (!$userDetalles->save()) {
                //     $saveDataValid = false;
                // }
                $saveDataValid = true;
            } else {
                $saveDataValid = false;
            }

            if ($saveDataValid) {

                $transaction->commit();

                /***   Log de Auditoria  ***/
                $user_log = User::find()->where(["email" => $user->email])->one();

                HelperLog::logAdd(
                    false,
                    $user_log->id, //Id user
                    $user_log->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['Signup'] . " " . $user_log->username, //texto para almacenar en el evento
                    [],
                    [$user_log], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $headMailText = Yii::t('app', 'headMailTextRegistro');
                $textBody = Yii::t('app', 'textBodyRegistro');
                $envioCorreo = CorreoController::registro($user->email, $headMailText, $textBody);
                if ($envioCorreo['status'] == true) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave') . ", " . Yii::t('app', 'successMail'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response);
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave') . ", " . Yii::t('app', 'failedMail'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response);
                }
            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => array_merge($user->getErrors(), $userDetalles->getErrors()),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    /**
     * Requests password reset.
     * Solicitud de restablecimiento de contraseña
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');
        if (!empty($jsonSend)) {
            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación POST ***//

            $email = $request['email'];
            $username = $request['username'];
            $user = User::find()
                ->select(['status', 'ldap'])
                ->where(['email' => $email])
                ->andWhere(['username' => $username])
            ->one();

            if ($user != null) {

                //Se valida el status del usuario
                if ($user['status'] != User::STATUS_ACTIVE) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'usuarioInactivo')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }

                //Se valida el ldap del usuario en el directorio activo
                if ($user['ldap'] == User::STATUS_ACTIVE) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'usuarioLdap')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'correoOUserNoExiste')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

            $model = new PasswordResetRequestForm();
            $model->attributes = $request;

            if ($model->validate()) {
                $envioCorreo = CorreoController::resetPassword($model->email, $model->username);
                // var_dump($envioCorreo);die();
                if ($envioCorreo['status'] == true) {

                    /***  log Auditoria ***/
                    if (!Yii::$app->user->isGuest) {
                        HelperLog::logAdd(
                            false,
                            $user->id, //Id user
                            $user->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['NuevaContrasena'] . " " . $user->username, //texto para almacenar en el evento
                            [],
                            [], //Data
                            array() //No validar estos campos
                        );
                    } /***   Fin log Auditoria   ***/

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successMailResetPassword'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response);
                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [ Yii::t('app', 'failedMail') ]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    // 'data' => ['error' => [Yii::t('app', 'incorrectEmail')]],
                    'data' => $model->getErrors(),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }

    }

    /**
     * Validar token de restablecimiento de contraseña
     */
    public function actionValidateTokenPassword()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');
        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación POST ***//

            $token = $request['token'];
            $token = HelperEncrypt::decrypt($token);

            //if ($token == true) {
            if ($token == true && is_string($token)) {
                try {
                    $model = User::findByPasswordResetToken($token);
                    if ($model) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successTokenValido'),
                            'data' => $token,
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response);
                    } else {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorTokenIncorrecto')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response);

                    }
                } catch (InvalidArgumentException $e) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorTokenInvalido')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorTokenInvalido')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    /**
     * Resets password.
     * Restaurar contraseña
     */

    public function actionResetPassword()
    {

        $jsonSend = Yii::$app->request->post('jsonSend');
        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación POST ***//

            $token = $request['token']; // En este punto ya el token debe llegar desencriptado
            $password = $request['password'];
            $password_confirma = $request['passwordConfirma'];

            if (!is_string($token) || trim($token) == '') {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorTokenIncorrecto')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }

            /** */
            if (trim($password) != '' && $password == $password_confirma) {

                $model = User::findOne(['password_reset_token' => $token]);
                if ($model == null) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'errorTokenIncorrecto')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }

                /** Validar que la clave no sea igual a las últimas 6 anteriores */
                $userHistoryPassword = UserHistoryPassword::find()
                    ->select(['idUserHistoryPassword', 'hashUserHistoryPassword'])
                    ->where(['idUser' => $model->id])
                    ->orderBy(['idUserHistoryPassword' => SORT_DESC])
                    ->limit(Yii::$app->params['cantDiferentsPasswords'])
                    ->all();

                $listUserHistoryPassword = []; // Variable para el listado del histórico de hash
                $listUserHistoryPassword[] = $model->password_hash; // Clave actual
                foreach ($userHistoryPassword as $value) {
                    $listUserHistoryPassword[] = $value['hashUserHistoryPassword'];
                }

                foreach ($listUserHistoryPassword as $hash) {
                    /** Validar coincidencia de clave con la función de Yii2 */
                    if ($hash != null && $hash != '') {
                        if (Yii::$app->getSecurity()->validatePassword($password, $hash)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => ['error' => [
                                    Yii::t('app', 'valHistoryPasswords', ['cantDiferentsPasswords' => Yii::$app->params['cantDiferentsPasswords']]),
                                ]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response);
                        }
                    }
                }
                /** Fin Validar que la clave no sea igual a las últimas 6 anteriores */

                /** Inicio de la transacción */
                $transaction = Yii::$app->db->beginTransaction();

                $model->setPassword($password);
                $model->status = yii::$app->params['statusTodoText']['Activo'];
                $model->intentos = 0;
                $model->removePasswordResetToken();

                if ($model->save()) {

                    /** Eliminar el 6to registro y los anteriores del histórico de contraseñas */
                    if (count($userHistoryPassword) == Yii::$app->params['cantDiferentsPasswords']) {
                        $deleteUserHistoryPassword = UserHistoryPassword::deleteAll(
                            [   
                                'AND', 
                                ['idUser' => (int) $model->id],
                                ['<=', 'idUserHistoryPassword', $userHistoryPassword[Yii::$app->params['cantDiferentsPasswords'] - 1]['idUserHistoryPassword'] ]
                            ]
                        );
                    }
                    /** Fin Eliminar el 6to registro y los anteriores del histórico de contraseñas */

                    /** Almacenar cambio de contraseña en la tabla de histórico */
                    $modelUserHistoryPassword = new UserHistoryPassword();
                    $modelUserHistoryPassword->hashUserHistoryPassword = $model->password_hash;
                    $modelUserHistoryPassword->idUser = $model->id;
                    $modelUserHistoryPassword->creacionUserHistoryPassword = date('Y-m-d H:i:s');
                    $modelUserHistoryPassword->save();

                    if ($modelUserHistoryPassword->save()) {

                        /***  log Auditoria ***/
                        HelperLog::logAdd(
                            false,
                            $model->id, //Id user
                            $model->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['Contrasena'] . " " . $model->username, //texto para almacenar en el evento
                            [],
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***   Fin log Auditoria   ***/

                        $transaction->commit();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'successPassword'),
                            'data' => [],
                            'status' => 200,
                        ];
                        return HelperEncryptAes::encrypt($response);

                    } else {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'unprocessableForm')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response);
                    }

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $model->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response);
                }

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorDifferentPasswords')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            /** */

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    public function actionNivelGeografico2Index($request)
    {
        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
        //*** Fin desencriptación GET ***//

        $dataList = [];
        $id = $request['id'];
        $data = NivelGeografico2::find()
            ->where(['idNivelGeografico1' => $id])
            ->andWhere(['estadoNivelGeografico2' => Yii::$app->params['statusTodoText']['Activo']])
            ->select(['nivelGeografico2', 'nomNivelGeografico2'])->all();

        foreach ($data as $value) {
            $dataList[] = [
                'nivelGeografico2' => $value['nivelGeografico2'],
                'nomNivelGeografico2' => $value['nomNivelGeografico2'],
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response);
    }

    public function actionNivelGeografico3Index($request)
    {
        //*** Inicio desencriptación GET ***//
        $decrypted = HelperEncryptAes::decryptGET($request);
        if ($decrypted['status'] == true) {
            $request = $decrypted['request'];
        } else {
            Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
            $response = [
                'message' => Yii::t('app', 'errorDesencriptacion'),
                'data' => Yii::t('app', 'errorDesencriptacion'),
                'status' => Yii::$app->params['statusErrorEncrypt'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
        //*** Fin desencriptación GET ***//

        $dataList = [];
        $id = 5; //$request['id'];
        $data = NivelGeografico3::find()
            ->where(['idNivelGeografico2' => $id])
            ->andWhere(['estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo']])
            ->select(['nivelGeografico3', 'nomNivelGeografico3'])->all();

        foreach ($data as $value) {
            $dataList[] = [
                'nivelGeografico3' => $value['nivelGeografico3'],
                'nomNivelGeografico3' => $value['nomNivelGeografico3'],
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /*** Consulta de tipos de identificación sin token ***/
    /*****************************************************/
    public function actionIdTypeIndex()
    {
        $dataList = [];
        $tiposIdentificaciones = TiposIdentificacion::find()->where(['estadoTipoIdentificacion' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreTipoIdentificacion' => SORT_ASC])->all();

        foreach ($tiposIdentificaciones as $key => $tipoIdentificacion) {
            $data[] = array(
                "id" => $tipoIdentificacion->idTipoIdentificacion,
                "val" => $tipoIdentificacion->nombreTipoIdentificacion,
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }
    
    /** Servicio CRON que envia una notificación a los usuarios tramitadores,
     * y a los usuarios jefes de los radicados que esten próximos a vencer.
     **/
    public function actionNextToExpire(){

        # Se obtiene la información de los radicados proximos a vencer
        $filtroResultado = $this->filterFileNextToExpire();

        if(!$filtroResultado['ok']){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [ $filtroResultado['data']['message'] ]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $filtro = $filtroResultado['data'];
    
        if($filtro != false){

            # Se obtiene los radicados filtrados
            $modelRelation = RadiRadicados::find();
                // ->leftJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
                $modelRelation = HelperQueryDb::getQuery('leftJoin', $modelRelation, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado'])
                ->where(["IN", "radiRadicados.numeroRadiRadicado", $filtro['numeroRadicado']]);
            $modelRelation = $modelRelation->all();

            $arrayDatos = [];
            if (!empty($modelRelation)){            

                foreach($modelRelation as $infoRadicado){

                    # Se obtiene el nombre del remitente/Destinatario del radicado
                    $nombreRemitente = self::remitentes($infoRadicado);

                    # Dias faltantes para el vencimiento del radicado    
                    $diff = $filtro['diferenciaDias'][$infoRadicado->numeroRadiRadicado];          

                    # Nombre de la dependencia y el tramitador
                    $nameDependencia = $infoRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;

                    $nameTramitador = $infoRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRadicado->userIdTramitador->userDetalles->apellidoUserDetalles;

                    # Se construye el array por tramitador y radicado
                    if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){

                        if(!isset ($arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][$infoRadicado['numeroRadiRadicado']]) ){

                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][$infoRadicado['numeroRadiRadicado']] = [
                                'radicado' => $infoRadicado->numeroRadiRadicado,
                                'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                                'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                                'fechaVencido' => $infoRadicado->fechaVencimientoRadiRadicados,
                                'asunto'=> $infoRadicado->asuntoRadiRadicado,
                                'remitente' => $nombreRemitente,
                                'dias' => $diff, // diferencia en dias
                                'nombreTramitador' => $nameTramitador
                            ];
                        }

                    } else {
                        $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][$infoRadicado['numeroRadiRadicado']] = [
                            'radicado' => $infoRadicado->numeroRadiRadicado,
                            'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                            'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                            'fechaVencido' => $infoRadicado->fechaVencimientoRadiRadicados,
                            'asunto'=> $infoRadicado->asuntoRadiRadicado,
                            'remitente' => $nombreRemitente,
                            'dias' => $diff, // diferencia en dias
                            'nombreTramitador' => $nameTramitador
                        ];

                        $arrayDatos[$infoRadicado["user_idTramitador"]]['dependencia'] = $nameDependencia;
                        $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado->userIdTramitador->email;
                        $arrayDatos[$infoRadicado["user_idTramitador"]]['name'] = $nameTramitador;                
                    }
                }
            } 
    
            if (is_array($arrayDatos) && !empty($arrayDatos)) {

                # Iteración para el envio del correo, por tramitador con sus radicados respectivos
                foreach($arrayDatos as $radicadosUsuario) {

                    # Modificación de mensajes del correo, cuando se realiza masivamente o es un radicado
                    if ( count($radicadosUsuario['radicados']) > 1) {
                        $headText = 'headMailTextNextExpires';
                        $subjectText = 'subjectNextExpires';

                    } else {
                        $headText = 'headMailTextNextExpire';
                        $subjectText = 'subjectNextExpire';
                    }

                    # Envia la notificación de correo electronico al usuario de tramitar
                    $headMailText = Yii::t('app', $headText, [
                        'userTramitador' => $radicadosUsuario['name'],
                        'dependencia' => $radicadosUsuario['dependencia']
                    ]);    

                    $textBody  = Yii::t('app', 'textBodyNextExpire');
                    $bodyMail = 'radicacion-html'; 

                    # Títulos de la tabla
                    $title = [
                        'radicado' => Yii::t('app', 'titleRadicado'),
                        'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                        'tipoDoc' => Yii::t('app', 'titleTipoDoc'),
                        'fechaVencimiento' => Yii::t('app', 'titleFechaVencimiento'),
                        'asunto' => Yii::t('app', 'titleAsunto'),
                        'remitente' => Yii::t('app', 'titleRemitente'),
                        'dias' => Yii::t('app', 'titleDiasVencer'),
                    ];

                    # Params que contiene la información de la tabla
                    $params = [
                        'table' => true,
                        'tableData' => $radicadosUsuario['radicados'],
                        'adminSend' => false,
                        'titleTable' => $title,
                    ];                             

                    $envioCorreo = CorreoController::addFile($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, null, $subjectText, true, $params, true, true); 
                } 
                # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos    
            }
        
            # ------------------------------------------------------------------------------------------------------------------------ #

            # Se consulta los jefes que le correspondan los radicados próximos a vencer
            $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
            $tablaUser = User::tableName() . ' AS US';
            $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
            $tablaTipoDoc = GdTrdTiposDocumentales::tableName() . ' AS TD';
            $tablaDepend = GdTrdDependencias::tableName() . ' AS DP';

            $modelRelation2 = (new \yii\db\Query())
                ->from($tablaRadicado);
                //->innerJoin($tablaUser, '`us`.`idGdTrdDependencia` = `rd`.`idTrdDepeUserTramitador`')
                $modelRelation2 = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation2, $tablaUser, ['US' => 'idGdTrdDependencia', 'RD' => 'idTrdDepeUserTramitador']);
                //->innerJoin($tablaUserDetalles, '`ud`.`idUser` = `rd`.`user_idTramitador`')
                $modelRelation2 = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation2, $tablaUserDetalles, ['UD' => 'idUser', 'RD' => 'user_idTramitador']);
                //->innerJoin($tablaTipoDoc, '`td`.`idGdTrdTipoDocumental` = `rd`.`idTrdTipoDocumental`')
                $modelRelation2 = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation2, $tablaTipoDoc, ['TD' => 'idGdTrdTipoDocumental', 'RD' => 'idTrdTipoDocumental']);
                //->innerJoin($tablaDepend, '`dp`.`idGdTrdDependencia` = `rd`.`idTrdDepeUserTramitador`')
                $modelRelation2 = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation2, $tablaDepend, ['DP' => 'idGdTrdDependencia', 'RD' => 'idTrdDepeUserTramitador'])
                ->where(['IN', 'RD.numeroRadiRadicado', $filtro['numeroRadicado']])
                ->andWhere(['IN', 'US.idGdTrdDependencia', $filtro['idDependencia']])
                ->andWhere(['US.idUserTipo' => Yii::$app->params['tipoUsuario']['Usuario Jefe']]);
            $modelRelation2 = $modelRelation2->all();


            $arrayJefe = [];
            if(!is_null($modelRelation2)){

                foreach($modelRelation2 as $infoJefe) {

                    # Se obtiene el nombre del remitente/Destinatario del radicado
                    $nombreRemitente = self::remitentes($infoJefe);

                    # Dias faltantes para el vencimiento del radicado
                    $diff = $filtro['diferenciaDias'][$infoJefe['numeroRadiRadicado']];

                    # Nombre del tramitador
                    $nameTramitador = $infoJefe['nombreUserDetalles'].' '.$infoJefe['apellidoUserDetalles'];

                    if(isset($arrayJefe[$infoJefe["id"]])){

                        if(!isset ($arrayJefe[$infoJefe["id"]]['radicados'][$infoJefe['numeroRadiRadicado']]) ){

                            $arrayJefe[$infoJefe["id"]]['radicados'][$infoJefe['numeroRadiRadicado']] = [
                                'radicado' => $infoJefe['numeroRadiRadicado'],
                                'fechaCreacion' => $infoJefe['creacionRadiRadicado'],
                                'tipoDocumental' => $infoJefe['nombreTipoDocumental'],
                                'fechaVencido' => $infoJefe['fechaVencimientoRadiRadicados'],
                                'asunto'=> $infoJefe['asuntoRadiRadicado'],
                                'remitente' => $nombreRemitente,
                                'dias' => $diff, // diferencia en dias
                                'nombreTramitador' => $nameTramitador
                            ];
                        }

                    } else {

                        $arrayJefe[$infoJefe["id"]]['radicados'][$infoJefe['numeroRadiRadicado']] = [
                            'radicado' => $infoJefe['numeroRadiRadicado'],
                            'fechaCreacion' => $infoJefe['creacionRadiRadicado'],
                            'tipoDocumental' => $infoJefe['nombreTipoDocumental'],
                            'fechaVencido' => $infoJefe['fechaVencimientoRadiRadicados'],
                            'asunto'=> $infoJefe['asuntoRadiRadicado'],
                            'remitente' => $nombreRemitente,
                            'dias' => $diff, // diferencia en dias
                            'nombreTramitador' => $nameTramitador
                        ];

                        $arrayJefe[$infoJefe["id"]]['email'] = $infoJefe['email'];
                        $arrayJefe[$infoJefe["id"]]['dependencia'] = $infoJefe['nombreGdTrdDependencia']; 
                    }
                }
            }

            if (is_array($arrayJefe) && !empty($arrayJefe)) {

                # Iteración para el envio del correo, por usuario jefe de sus radicados
                foreach($arrayJefe as $radicadosJefe) {

                    # Envia la notificación de correo electronico al usuario jefe
                    $headMailText = Yii::t('app', 'headMailTextNextExpireBoss', [
                        'dependencia' => $radicadosJefe['dependencia']
                    ]);    

                    $textBody  = Yii::t('app', 'textBodyNextExpireBoss');
                    $subject = 'subjectNextExpires';
                    $bodyMail = 'radicacion-html'; 

                    # Títulos de la tabla
                    $title = [
                        'radicado' => Yii::t('app', 'titleRadicado'),
                        'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                        'tipoDoc' => Yii::t('app', 'titleTipoDoc'),
                        'fechaVencimiento' => Yii::t('app', 'titleFechaVencimiento'),
                        'asunto' => Yii::t('app', 'titleAsunto'),
                        'remitente' => Yii::t('app', 'titleRemitente'),
                        'dias' => Yii::t('app', 'titleDiasVencer'),
                    ];

                    $params = [
                        'table' => true,
                        'tableData' => $radicadosJefe['radicados'],
                        'adminSend' => true, // Permite mostrar la columna del nombre tramitador
                        'titleTable' => $title,
                        'titleColTramitador' => Yii::t('app', 'titleTramitador'), //Columna del tramitador
                    ];     
                    
                    $envioCorreo = CorreoController::addFile($radicadosJefe['email'], $headMailText, $textBody, $bodyMail, null, $subject, true, $params, true, true);                
                } 
                # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos 

             
                }

                # Validación de correo
                if($envioCorreo){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSaveNextExpire'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
            }  


        } else {

            # Mensaje cuando no hay radicados próximos a vencer
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'emptyDataNextExpire'), 
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }       
    }

    /** Servicio CRON que envia una notificación al administrador de los radicados
     * PQRS próximos a vencer.
     **/
    public function actionPqrsNextExpire(){

        # Se obtiene la información de los radicados proximos a vencer 
        $filtroResultado = $this->filterFileNextToExpire();

        if(!$filtroResultado['ok']){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [ $filtroResultado['data']['message'] ]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $filtro = $filtroResultado['data'];

        if($filtro != false){

            # Se obtiene los radicados PQRS 
            $modelRelation = RadiRadicados::find();
                //->leftJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
                $modelRelation = HelperQueryDb::getQuery('leftJoin', $modelRelation, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado'])
                ->where(['IN', 'radiRadicados.numeroRadiRadicado', $filtro['numeroRadicado']])
                ->andWhere(['=', 'radiRadicados.idCgTipoRadicado', Yii::$app->params['idCgTipoRadicado']['radiPqrs']]);
            $modelRelation = $modelRelation->all();

            $arrayPqrs = [];       
            if (!empty($modelRelation)){            

                foreach($modelRelation as $infoRadicado){

                    # Se obtiene el nombre del remitente/Destinatario del radicado
                    $nombreRemitente = self::remitentes($infoRadicado);

                    # Dias faltantes para el vencimiento del radicado
                    $diff = $filtro['diferenciaDias'][$infoRadicado->numeroRadiRadicado];       

                    # Nombre de la dependencia y el tramitador
                    $nameDependencia = $infoRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;

                    $nameTramitador = $infoRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRadicado->userIdTramitador->userDetalles->apellidoUserDetalles;

                    # Se construye el array de los radicados PQRS
                    $arrayPqrs['radicados'][] = [
                        'radicado' => $infoRadicado->numeroRadiRadicado,
                        'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                        'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                        'asunto'=> $infoRadicado->asuntoRadiRadicado,
                        'remitente' => $nombreRemitente,                                               
                        'nombreTramitador' => $nameTramitador,
                        'dependenciaTramitador' => $nameDependencia,
                        'dias' => $diff, // diferencia en dias
                        'fechaVencido' => $infoRadicado->fechaVencimientoRadiRadicados,
                    ];
                } 

            } 

            if (is_array($arrayPqrs) && !empty($arrayPqrs)) {

                #Configuracion general del correo del administrador notificado PQRS
                $configGeneral = ConfiguracionGeneralController::generalConfiguration();   

                # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                if($configGeneral['status']){
                    $usuarioNotificadorPqrs = $configGeneral['data']['correoNotificadorPqrsCgGeneral'];

                } else {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [$configGeneral['message']]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Iteración para el envio del correo, de los radicados pqrs
                foreach($arrayPqrs as $key => $radicadosPqrs) {

                    # Modificación de mensajes del correo, cuando se realiza masivamente o es un radicado
                    if ( $radicadosPqrs > 1) {
                        $headText = 'headMailTextPqrsExpires';
                        $subjectText = 'subjectPqrsExpires';

                    } else {
                        $headText = 'headMailTextPqrsExpire';
                        $subjectText = 'subjectPqrsExpire';
                    }
                    
                    # Envia la notificación de correo electronico al administrador 
                    $headMailText = Yii::t('app', $headText);    

                    $textBody  = Yii::t('app', 'textBodyPqrsExpire');
                    $bodyMail = 'pqrs-html'; 

                    # Títulos de la tabla
                    $title = [
                        'radicado' => Yii::t('app', 'titleRadicado'),
                        'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                        'tipoDoc' => Yii::t('app', 'titleTipoDoc'),                    
                        'asunto' => Yii::t('app', 'titleAsunto'),
                        'remitente' => Yii::t('app', 'titleRemitente'),                        
                        'usuarioTramitador' => Yii::t('app', 'titleTramitador'),
                        'dependTramitador' => Yii::t('app', 'titleDependTramitador'),
                        'dias' => Yii::t('app', 'titleDiasVencer'),
                    ];

                    # Params que contiene la información de la tabla
                    $params = [
                        'table' => true,
                        'tableData' => $radicadosPqrs,
                        'adminSend' => true, // Permite mostrar la columna de la fecha vencida
                        'titleTable' => $title,  
                        'titleColFechaVencido' => Yii::t('app', 'titleFechaVencimiento'),
                    ];   

                    $envioCorreo = CorreoController::addFile($usuarioNotificadorPqrs, $headMailText, $textBody, $bodyMail, null, $subjectText, false, $params);
                } 
                # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos    

                # Validación de correo
                if($envioCorreo){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSavePqrsExpire'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            }

        } else {

            # Mensaje cuando no hay radicados PQRS próximos a vencer
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'emptyDataPqrsExpire'), 
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /** Servicio CRON que envia una notificación al administrador de los radicados
     * PQRS vencidos.
     **/
    public function actionExpiredPqrs(){

        $today = date('Y-m-d');

        # Consulta la accion de la transaccion y se obtener su id
        $idCargarPlantilla = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'loadFormat']);
        $idCombinar = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceMatch']);

        # Se obtiene los radicados PQRS que esten en el rango de los dias de notificacion
        $modelRelation = RadiRadicados::find();
            //->leftJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            $modelRelation = HelperQueryDb::getQuery('leftJoin', $modelRelation, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado'])
            ->where(['<', 'radiRadicados.fechaVencimientoRadiRadicados', $today])
            ->andWhere(['=', 'radiRadicados.idCgTipoRadicado', Yii::$app->params['idCgTipoRadicado']['radiPqrs']])
            ->andWhere(['<>', 'radiLogRadicados.idTransaccion', $idCargarPlantilla->idCgTransaccionRadicado])
            ->andWhere(['<>', 'radiLogRadicados.idTransaccion', $idCombinar->idCgTransaccionRadicado]);
        $modelRelation = $modelRelation->all();

        $arrayPqrs = [];

        if (!empty($modelRelation)){            

            foreach($modelRelation as $infoRadicado){

                # Se obtiene el nombre del remitente/Destinatario del radicado
                $nombreRemitente = self::remitentes($infoRadicado);

                #Se calculo los días vencido de acuerdo a la fecha actual
                $diasVencidos = - RadicadosController::calcularDiasVencimiento($infoRadicado->idRadiRadicado);

                # Nombre de la dependencia y el tramitador
                $nameDependencia = $infoRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;

                $nameTramitador = $infoRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRadicado->userIdTramitador->userDetalles->apellidoUserDetalles;

                # Se construye el array de los radicados PQRS
                $arrayPqrs['radicados'][] = [
                    'radicado' => $infoRadicado->numeroRadiRadicado,
                    'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                    'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                    'asunto'=> $infoRadicado->asuntoRadiRadicado,
                    'remitente' => $nombreRemitente,                                         
                    'nombreTramitador' => $nameTramitador,
                    'dependenciaTramitador' => $nameDependencia,
                    'dias' => $diasVencidos, // diferencia en dias
                ];
            } 

            $dataValidation = false;

        } else {
            $dataValidation = true;
            $message = Yii::t('app', 'emptyDataPqrsExpired');
        }

        if (is_array($arrayPqrs) && !empty($arrayPqrs)) {

            # Configuracion general del correo del administrador notificado PQRS
            $configGeneral = ConfiguracionGeneralController::generalConfiguration();

            # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
            if($configGeneral['status']){
                $usuarioNotificadorPqrs = $configGeneral['data']['correoNotificadorPqrsCgGeneral'];

            } else {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [$configGeneral['message']]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            # Iteración para el envio del correo, de los radicados pqrs
            foreach($arrayPqrs as $key => $radicadosPqrs) {

                # Modificación de mensajes del correo, cuando se realiza masivamente o es un radicado
                if ( $radicadosPqrs > 1) {
                    $headText = 'headMailTextsPqrsExpired';
                    $subjectText = 'subjectPqrsExpiredMany';

                } else {
                    $headText = 'headMailTextPqrsExpired';
                    $subjectText = 'subjectPqrsExpiredOne';
                }
                
                # Envia la notificación de correo electronico al administrador 
                $headMailText = Yii::t('app', $headText);    

                $textBody  = Yii::t('app', 'textBodyPqrsExpired');
                $bodyMail = 'pqrs-html'; 

                # Títulos de la tabla
                $title = [
                    'radicado' => Yii::t('app', 'titleRadicado'),
                    'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                    'tipoDoc' => Yii::t('app', 'titleTipoDoc'),                    
                    'asunto' => Yii::t('app', 'titleAsunto'),
                    'remitente' => Yii::t('app', 'titleRemitente'),                   
                    'usuarioTramitador' => Yii::t('app', 'titleTramitador'),
                    'dependTramitador' => Yii::t('app', 'titleDependTramitador'),
                    'dias' => Yii::t('app', 'titleDiasVencido'),
                ];

                # Params que contiene la información de la tabla
                $params = [
                    'table' => true,
                    'tableData' => $radicadosPqrs,
                    'adminSend' => false,  
                    'titleTable' => $title,  
                ];

                $envioCorreo = CorreoController::addFile($usuarioNotificadorPqrs, $headMailText, $textBody, $bodyMail, null, $subjectText, false, $params);
                
            } 
            # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos   
            
            # Validación de correo
            if($envioCorreo){

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','successSavePqrsExpired'),
                    'data' => [],
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        }        

        # Mensaje cuando no hay radicados PQRS próximos a vencer
        if($dataValidation){

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => $message,
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Función que consulta el nombre del remitente/Destinatario del radicado.
     * @param $infoRadicado [array] [Array donde contiene toda la información del radicado]
     **/
    protected function remitentes( $infoRadicado ) {

        $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $infoRadicado['idRadiRadicado']]);

        $nombresRemitentes = [];
        foreach($modelRemitente as $dataRemitente){

            if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                # Se obtiene la información del cliente
                $remitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                $nombresRemitentes[] = $remitente->nombreCliente;
    
            } else {
                # Se obtiene la información del usuario o funcionario
                $remitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                $nombresRemitentes[] = $remitente->userDetalles->nombreUserDetalles.' '.$remitente->userDetalles->apellidoUserDetalles;
            }    
        }
        
        $nombreRemitente = implode(", ", $nombresRemitentes);

        return $nombreRemitente;
    }

    /** Función que calcula los radicados proximos a vencer, 
    * dependiendo de los días configurados en params.
    **/
    public function filterFileNextToExpire(){

        $today = date('Y-m-d');

        # Dias configurados para notificar los radicados proximos a vencer
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();
        //$daysParam = Yii::$app->params['diasNotificacion'];

        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
        if($configGeneral['status']){
            $daysParam = $configGeneral['data']['diasNotificacionCgGeneral'];

        } else {

            return [
                'ok' => false,
                'data' => [
                    'message' => $configGeneral['message']
                ]            
            ];          
        }


        # Consulta de los días no laborables
        $modelDiasNoLaborados = CgDiasNoLaborados::find()
            ->where(['estadoCgDiaNoLaborado' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();

        # Arreglo de los días no laborables
        $arrayNoLaborados = [];

        if(count($modelDiasNoLaborados) > 0 ){
            foreach($modelDiasNoLaborados as $diaNoLaboral){
                $arrayNoLaborados[] = date("Y-m-d", strtotime($diaNoLaboral->fechaCgDiaNoLaborado));
            }

        } else { // Si no hay una configuración activa, muestra mensaje de alerta

            return [
                'ok' => false,
                'data' => [
                    'message' => Yii::t('app', 'validErrorDays')
                ]            
            ];           
        }

        # Horario laboral activo
        $horarioValido = CgHorarioLaboral::findOne(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']]);

        if($horarioValido != null) {
            $diaMax = $horarioValido->diaFinCgHorarioLaboral;
            $diaInicio = $horarioValido->diaInicioCgHorarioLaboral;

        } else {

            return [
                'ok' => false,
                'data' => [
                    'message' => Yii::t('app','validErrorSchedule')
                ]            
            ];
            
        }

        # Consulta la accion de la transaccion y se obtener su id
        $idCargarPlantilla = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'loadFormat']);
        $idCombinar = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceMatch']);

        # Se obtiene los radicados igual o mayor a la fecha actual
        $modelRelation = RadiRadicados::find();
            //->leftJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            $modelRelation = HelperQueryDb::getQuery('leftJoin', $modelRelation, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado'])
            ->where(['>=', 'radiRadicados.fechaVencimientoRadiRadicados', $today])
            ->andWhere(['<>', 'radiLogRadicados.idTransaccion', $idCargarPlantilla->idCgTransaccionRadicado])
            ->andWhere(['<>', 'radiLogRadicados.idTransaccion', $idCombinar->idCgTransaccionRadicado]);
        $modelRelation = $modelRelation->all();
       
        $dateToday = new DateTime();
        $diffDays = [];
        $numRadicado = [];
        $idDependencia = [];
        $return = []; //Array que contiene la información del radicado
        
        if(!empty($modelRelation) && !in_array($today, $arrayNoLaborados) && date('w', strtotime($today)) <= $diaMax ){

            foreach($modelRelation as $infoRadicado){

                # Calculo para obtener los días faltantes para que se venza el radicado
                $dateRadi = new DateTime($infoRadicado->fechaVencimientoRadiRadicados);  
                $diff = $dateRadi->diff($dateToday);

                if($diff->days <= $daysParam && $diff->days >= 0  ){
                    
                    $diffDays[$infoRadicado->numeroRadiRadicado] = $diff->days;
                    $numRadicado[] = $infoRadicado->numeroRadiRadicado;
                    $idDependencia[] = $infoRadicado->idTrdDepeUserTramitador;
                   
                    $return = [
                        'numeroRadicado' => $numRadicado,
                        'idDependencia' => $idDependencia,
                        'diferenciaDias' => $diffDays,
                    ];
                }                 
            } 
        }  

        if(!empty($return)){
            return [
                'ok' => true,
                'data' => $return
            ];

        } else {
            return [
                'ok' => true,
                'data' => false// Retorna false, cuando no haya radicados proximos a vencer
            ];
        }   
    }

    /** Funcion de conexion con LDAP
     * @param $model [Obtiene la informacion del loginForm]
     */
    public static function configLdap($model)
    {

        //Conexion de skinatech
        $ldapServer = Yii::$app->params['ldapServer'];
        $cadenaBusqLDAP = Yii::$app->params['cadenaBusqLDAP'];
        $campoBusqLDAP = Yii::$app->params['campoBusqLDAP'];
        $adminLDAP = Yii::$app->params['adminLDAP'];
        $paswLDAP = Yii::$app->params['paswLDAP'];

        $status = false;
        $error = '';

        //Valida la conexión
        if ($connect = ldap_connect($ldapServer)) {

            // ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            // ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);

            // Realiza la autenticación con un servidor LDAP
            // if ((ldap_bind($connect, $model->username, $model->password)) == true) {

                // busca el usuario
                // if (($res_id = ldap_search($connect, "$cadenaBusqLDAP", "($campoBusqLDAP=$model->username)")) == true) {
                    
                    // Solo un usuario encontrado
                    // if (ldap_count_entries($connect, $res_id) == 1) {

                        //if (($entry_id = ldap_first_entry($connect, $res_id)) == true) {

                            //DN del usuario encontrado
                            // if (($user_dn = ldap_get_dn($connect, $entry_id)) == true) {

                                // Valida que la contraseña corresponda a la de LDAP y conecta al usuario
                                try {
                                    $authenticated = ldap_bind($connect, $model->username, $model->password);
                                    $status = true;
                                } catch (ErrorException $e) {

                                    return [
                                        'status' => $status,
                                        'data' => Yii::t('app', 'Incorrect username or password ldap'),
                                    ];
                                }

                                //if ((ldap_bind($connect, $user_dn, $model->password) ) == true) {
                                    // $status = true;
                                    // $error = '';
                                //}
                            // }
                        // }
                    // }
                // } else {
                    // return [
                        // 'status' => $status,
                        // 'data' => 'error usuario',
                    // ];
                // }
            // } else {
            //     return [
            //         'status' => $status,
            //         'data' => Yii::t('app', 'Incorrect username or password ldap'),
            //         // 'data' => 'Error en la autentificación del servidor',
            //     ];
            // }

            ldap_close($connect);
            return [
                'status' => $status,
                'data' => $error,
            ];

        } else {
            return [
                'status' => $status,
                'data' => 'No hay conexión',
            ];
        }
    }
    
    public function actionExpired(){       
        
        $modelTransacciones = CgTransaccionesRadicados::find()
            ->where(['IN', 'actionCgTransaccionRadicado', ['loadFormat', 'correspondenceMatch']]) //Se modifica consulta con el action y no con title
        ->all();       

        $idTransaciones = [];
        foreach($modelTransacciones as $transaccion){
            $idTransaciones[] = $transaccion->idCgTransaccionRadicado;            
        }

        $fechaActual = date("Y-m-d");        
        
        $modelRadicados = RadiRadicados::find();
            //->leftJoin('radiLogRadicados',  'radiRadicados.idRadiRadicado = radiLogRadicados.idRadiRadicado')
            $modelRadicados = HelperQueryDb::getQuery('leftJoin', $modelRadicados, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado'])
            ->where(['<>', 'radiLogRadicados.idTransaccion', $idTransaciones[0]])
            ->andWhere(['<>', 'radiLogRadicados.idTransaccion', $idTransaciones[1]])
            ->andWhere(['<', 'radiRadicados.fechaVencimientoRadiRadicados',  $fechaActual]);
        $modelRadicados = $modelRadicados->all();
       

        $arrayDatos = [];

        //Detreminar el id del tipo usuario jefe
        $modelUserTipo = UserTipo::find()
            ->where(['nombreUserTipo' => 'Usuario Jefe'])
        ->one();

        if(!empty($modelUserTipo)){
            $idUserTipo = $modelUserTipo->idUserTipo;
        }      

        if(!empty($modelRadicados)){

            $validacionEnvio = true;

            foreach($modelRadicados as $infoRadicado){                

                # Se consulta el nombre del remitente/Destinatario del radicado
                $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $infoRadicado->idRadiRadicado]);

                $nombresRemitentes = [];
                foreach($modelRemitente as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $remitente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                        
                        $nombresRemitentes[$infoRadicado->idRadiRadicado][] = $remitente->nombreCliente;
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $remitente = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                        $nombresRemitentes[$infoRadicado->idRadiRadicado][] = $remitente->userDetalles->nombreUserDetalles.' '.$remitente->userDetalles->apellidoUserDetalles;
                    }
                }
                
                # Nombre de los remitentes
                $nombreRemitente = implode(", ", $nombresRemitentes[$infoRadicado->idRadiRadicado]);            

                #Se calculo los días vencido de acuerdo a la fecha actual
                $diasVencidos = - RadicadosController::calcularDiasVencimiento($infoRadicado->idRadiRadicado);
    
                #Llena el array con los datos del radicado
                $arrayDatos['radicadosUsuario']["$infoRadicado->user_idTramitador"]['radicados'][$infoRadicado->numeroRadiRadicado] = [
                    'radicado' => $infoRadicado->numeroRadiRadicado,
                    'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                    'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                    'fechaVencido' => $infoRadicado->fechaVencimientoRadiRadicados,
                    'asunto'=> $infoRadicado->asuntoRadiRadicado,
                    'remitente' => $nombreRemitente,
                    'diasVencido' => $diasVencidos // diferencia en dias
                ];

                if(isset($infoRadicado->user_idTramitador)){                  

                    //Obtener usuario tramitador
                    $modelUserDetalleTramitador = UserDetalles::find()
                        ->where(['idUser' => $infoRadicado->user_idTramitador])
                    ->one();
                    

                    #Llena el array con los datos del usuario tramitador
                    $arrayDatos['radicadosUsuario']["$infoRadicado->user_idTramitador"]['usuarioTramitador'] = [
                        'email' => $infoRadicado->userIdTramitador->email,
                        'nombre' => $modelUserDetalleTramitador->nombreUserDetalles . ' ' . $modelUserDetalleTramitador->apellidoUserDetalles,
                        'dependencia' => $infoRadicado->idTrdDepeUserCreador0->nombreGdTrdDependencia                        
                    ];                  

                }
                
                if(isset($infoRadicado->idTrdDepeUserTramitador)){

                    #Llena el array con los datos del radicado
                    $arrayDatos['radicadosDependencia']["$infoRadicado->idTrdDepeUserTramitador"]['radicados'][$infoRadicado->numeroRadiRadicado] = [
                        'radicado' => $infoRadicado->numeroRadiRadicado,
                        'fechaCreacion' => $infoRadicado->creacionRadiRadicado,
                        'tipoDocumental' => $infoRadicado->trdTipoDocumental->nombreTipoDocumental,
                        'fechaVencido' => $infoRadicado->fechaVencimientoRadiRadicados,
                        'asunto'=> $infoRadicado->asuntoRadiRadicado,
                        'remitente' => $nombreRemitente,
                        'diasVencido' => $diasVencidos // diferencia en dias
                    ];

                    #Llena el array con el nombre de la dependencia
                    $arrayDatos['radicadosDependencia']["$infoRadicado->idTrdDepeUserTramitador"]['nombreDependencia'] = $infoRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia;
                    
                    //Obtener usuario jefe
                    if(isset($idUserTipo)){                        

                        $modelUserJefe = User::find()
                            ->where(['idUserTipo' => $idUserTipo])
                            ->andWhere(['idGdTrdDependencia' => $infoRadicado->idTrdDepeUserTramitador])
                        ->all();
                    }
                    if(!empty($modelUserJefe)){                        

                        foreach($modelUserJefe as $userJefe){
                            $modelUserDetalleJefe = UserDetalles::find()
                                ->where(['idUser' => $userJefe->id])
                            ->one();
                            

                            #Llena el array con los datos de los usuarios jefes pertenecientes a la dependencia del tramitador
                            $arrayDatos['radicadosDependencia']["$infoRadicado->idTrdDepeUserTramitador"]['usuariosJefes'][$userJefe->username] = [
                                'email' => $userJefe->email,
                                'nombre' => $modelUserDetalleJefe->nombreUserDetalles . ' ' . $modelUserDetalleJefe->apellidoUserDetalles,
                            ];

                        }
                    }
                    
                    if(isset($infoRadicado->user_idTramitador)){
                        $arrayDatos['radicadosDependencia']["$infoRadicado->idTrdDepeUserTramitador"]['radicados'][$infoRadicado->numeroRadiRadicado]['nombreTramitador'] 
                            =  $modelUserDetalleTramitador->nombreUserDetalles . ' ' . $modelUserDetalleTramitador->apellidoUserDetalles 
                        ;
                    }

                }

            }            

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app' ,'emptyDataExpired'), 
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }         

        # Iteración para el envio del correo, por usuario con sus radicados respectivos
        foreach($arrayDatos['radicadosUsuario'] as $radicadosUsuario){

            //Dias vencido
            $maxDiasVencidos = 0;
            foreach($radicadosUsuario['radicados'] as $radicado){
                if($radicado['diasVencido'] > $maxDiasVencidos){
                    $maxDiasVencidos = $radicado['diasVencido'];
                }
            }

            # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
            if ( count($radicadosUsuario['radicados']) > 1) {
                $headText = 'headMailTextExpiredMany';
                $subjectText = 'subjectExpiredMany';
            } else {
                $headText = 'headMailTextExpiredOne';
                $subjectText = 'subjectExpiredOne';
            }

            # Envia la notificación de correo electronico al usuario de tramitar
            $headMailText = Yii::t('app', $headText, [
                'userTramitador' => $radicadosUsuario['usuarioTramitador']['nombre'],
                'dependencia' => $radicadosUsuario['usuarioTramitador']['dependencia']
            ]);    

            $textBody  = Yii::t('app', 'textBodyExpired', [
                'diasVencido' => $maxDiasVencidos
            ]);
            $subject = Yii::t('app', $subjectText);
            $bodyMail = 'radicacion-html';

            # Params que contiene la información de la tabla
            $params = [
                'table' => true,
                'tableData' => $radicadosUsuario['radicados'],
                'adminSend' => false,
                'titleTable' => [
                    'radicado' => Yii::t('app', 'titleRadicado'),
                    'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                    'tipoDoc' => Yii::t('app', 'titleTipoDoc'),
                    'fechaVencimiento' => Yii::t('app', 'titleFechaVencimiento'),
                    'asunto' => Yii::t('app', 'titleAsunto'),
                    'remitente' => Yii::t('app', 'titleRemitente'),
                    'dias' => Yii::t('app', 'titleDiasVencido'),
                ]
            ];  

            $envioCorreo = CorreoController::addFile($radicadosUsuario['usuarioTramitador']['email'], $headMailText, $textBody, $bodyMail, null, $subject, false, $params);
            
            if(!$envioCorreo['status']){
                $validacionEnvio = false;
            }
        
        }
        
        # Iteración para el envio del correo, por dependencia con sus radicados respectivos
        foreach($arrayDatos['radicadosDependencia'] as $radicadosDependencia){

            # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
            if ( count($radicadosUsuario['radicados']) > 1) {               
                $subjectText = 'subjectExpiredMany';            
            } else {               
                $subjectText = 'subjectExpiredOne';
            }
            
            #Envia notificación al usuario jefe de dependencia                        
            $headMailText = Yii::t('app', 'headMailTextExpiredBoss', [                
                'dependencia' => $radicadosDependencia['nombreDependencia']
            ]);

            $textBody  = Yii::t('app', 'textBodyExpiredBoss'); 
            $subject = Yii::t('app', $subjectText);
            $bodyMail = 'radicacion-html';            

            /** Validar si existen usuarios jefe en la dependencia para enviar el correo */
            if (!isset($radicadosDependencia['usuariosJefes'])) {
                break;
            } elseif (count($radicadosDependencia['usuariosJefes']) == 0) {
                break;
            }
            /** Fin Validar si existen usuarios jefe en la dependencia para enviar el correo */

            $emailsUsuriosJefes = [];
            foreach($radicadosDependencia['usuariosJefes'] as $usuariosJefes){               
                $emailsUsuriosJefes[] = $usuariosJefes['email'];
            }

            # Params que contiene la información de la tabla
            $params = [
                'table' => true,
                'tableData' => $radicadosDependencia['radicados'],
                'adminSend' => true,
                'titleColTramitador' => Yii::t('app', 'titleTramitador'),
                'titleTable' => [
                    'radicado' => Yii::t('app', 'titleRadicado'),
                    'fechaCreacion' => Yii::t('app', 'titleFechaCreacion'),
                    'tipoDoc' => Yii::t('app', 'titleTipoDoc'),
                    'fechaVencimiento' => Yii::t('app', 'titleFechaVencimiento'),
                    'asunto' => Yii::t('app', 'titleAsunto'),
                    'remitente' => Yii::t('app', 'titleRemitente'),
                    'dias' => Yii::t('app', 'titleDiasVencido'),                    
                ]
            ];  

            $envioCorreo = CorreoController::addFile($emailsUsuriosJefes, $headMailText, $textBody, $bodyMail, null, $subject, false, $params);

            if(!$envioCorreo['status']){
                $validacionEnvio = false;
            }
        }

        if($validacionEnvio){

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','successSaveExpired'),
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorExpired'),
                'data' => [],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        } 
    }

    /** Servicio CRON que envia una notificación a los usuarios solicitantes,
     * del prestamo aprobado que están próximos a vencer.
    **/
    public function actionLoansNextToExpire(){

        # Se obtiene la información de los préstamos aprobados proximos a vencer
        $filtroResultado = $this->filterLoansNextToExpire();

        if(!$filtroResultado['ok']){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [ $filtroResultado['data']['message'] ]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $filtro = $filtroResultado['data'];

        if($filtro != false){

            $arrayDatos = [];     
            foreach($filtro['data'] as $i => $dataLoan){

                # Se obtiene la relación del id usuario, número radicado, asunto, tipo documental y expediente.
                $idUser = $dataLoan->idGaPrestamo0->idUser;
                $numeroRadicado = $dataLoan->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;
                $asunto = $dataLoan->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->asuntoRadiRadicado;
                $tipoDocumental = $dataLoan->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->trdTipoDocumental->nombreTipoDocumental;
                $expediente = $dataLoan->idGaPrestamo0->idGdExpedienteInclusion0->gdExpediente->nombreGdExpediente;

                # Dias faltantes para el vencimiento del radicado    
                $diff = $filtro['diferenciaDias'][$numeroRadicado];          
                            
                # Nombre de la dependencia y el usuario solicitante
                $dependencia = $dataLoan->idGaPrestamo0->idGdTrdDependencia0->nombreGdTrdDependencia;
                $usuario = $dataLoan->idGaPrestamo0->idUser0->userDetalles->nombreUserDetalles.' '.$dataLoan->idGaPrestamo0->idUser0->userDetalles->apellidoUserDetalles;

                # Se construye el array por usuario y id préstamo
                if(isset($arrayDatos[$idUser])){

                    if(!isset ($arrayDatos[$idUser]['prestamo'][$dataLoan->idGaPrestamo]) ){

                        $arrayDatos[$idUser]['prestamo'][$dataLoan->idGaPrestamo] = [
                            'radicado' => $numeroRadicado,                                
                            'asunto'=> $asunto,
                            'tipoDocumental' => $tipoDocumental,
                            'expediente' => $expediente,
                            'tipoPrestamo' => Yii::$app->params['statusLoanTypeNumber'][$dataLoan->idGaPrestamo0->idTipoPrestamoGaPrestamo],
                            'requerimiento' => Yii::$app->params['statusLoanRequirementNumber'][$dataLoan->idGaPrestamo0->idRequerimientoGaPrestamo],                               
                            'fechaDevolucion' => $dataLoan->fechaGaHistoricoPrestamo,
                            'dias' => $diff, // diferencia en dias
                        ];
                    }

                } else {
                    $arrayDatos[$idUser]['prestamo'][$dataLoan->idGaPrestamo] = [
                        'radicado' => $numeroRadicado,                         
                        'asunto'=> $asunto,
                        'tipoDocumental' => $tipoDocumental,
                        'expediente' => $expediente,
                        'tipoPrestamo' => Yii::$app->params['statusLoanTypeNumber'][$dataLoan->idGaPrestamo0->idTipoPrestamoGaPrestamo],
                        'requerimiento' => Yii::$app->params['statusLoanRequirementNumber'][$dataLoan->idGaPrestamo0->idRequerimientoGaPrestamo],  
                        'fechaDevolucion' => $dataLoan->fechaGaHistoricoPrestamo,
                        'dias' => $diff, // diferencia en dias
                    ];

                    $arrayDatos[$idUser]['dependencia'] = $dependencia;
                    $arrayDatos[$idUser]['email'] = $dataLoan->idGaPrestamo0->idUser0->email;
                    $arrayDatos[$idUser]['name'] = $usuario;                
                }
            } 
    
            if (is_array($arrayDatos) && !empty($arrayDatos)) {

                # Iteración para el envio del correo, por usuario solicitante con su información del préstamo
                foreach($arrayDatos as $prestamos) {

                    # Modificación de mensajes del correo, cuando se realiza masivamente o es un radicado
                    if ( count($prestamos['prestamo']) > 1) {
                        $headText = 'headMailTextLoansNextExpire';
                        $subject = 'subjectLoansNextExpire';

                    } else {
                        $headText = 'headMailTextLoanNextExpire';
                        $subject = 'subjectLoanNextExpire';
                    }

                    $headMailText = Yii::t('app', $headText, [
                        'user' => $prestamos['name'],
                        'dependencia' => $prestamos['dependencia']
                    ]);    

                    $textBody  = Yii::t('app', 'textBodyLoanNextExpire');
                    $bodyMail = 'radicacion-html';

                    # Params que contiene la información de la tabla
                    $params = [
                        'table' => true,
                        'tableData' => $prestamos['prestamo'],
                        'adminSend' => false,
                        'titleTable' => [
                            'radicado' => Yii::t('app', 'titleRadicado'),
                            'asunto' => Yii::t('app', 'titleAsunto'),
                            'tipoDoc' => Yii::t('app', 'titleTipoDoc'),
                            'expediente' => Yii::t('app', 'titleExpediente'),
                            'tipoPrestamo' => Yii::t('app', 'titleTipoPrestamo'),
                            'requerimiento' => Yii::t('app', 'titleRequerimiento'),
                            'fechaDevolucion' => Yii::t('app', 'titleFechaDevolucion'),
                            'dias' => Yii::t('app', 'titleDiasVencer'),
                        ],
                    ];                             

                    $envioCorreo = CorreoController::addFile($prestamos['email'], $headMailText, $textBody, $bodyMail, null, $subject, true, $params, true, true); 
                } 
                # Fin Iteración para el envio del correo, por usuario con sus préstamos respectivos 
                
                # Validación de correo
                if($envioCorreo){

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSaveLoanNextExpire'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            }

        } else {

            # Mensaje cuando no hay préstamos próximos a vencer
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'emptyDataLoanNextExpire'), 
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        }            
    }  

    /** Función que calcula los préstamos proximos a vencer, 
    * dependiendo de los días configurados en params.
    **/
    public function filterLoansNextToExpire(){

        $today = date('Y-m-d');

        # Dias configurados para notificar los préstamos proximos a vencer
        //$daysParam = Yii::$app->params['diasNotificacion'];
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();

        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
        if($configGeneral['status']){
            $daysParam = $configGeneral['data']['diasNotificacionCgGeneral'];

        } else {

            return [
                'ok' => false,
                'data' => [
                    'message' => $configGeneral['message']
                ]            
            ];            
        }


        # Consulta de los días no laborables
        $modelDiasNoLaborados = CgDiasNoLaborados::find()
            ->where(['estadoCgDiaNoLaborado' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();

        # Arreglo de los días no laborables
        $arrayNoLaborados = [];

        if(count($modelDiasNoLaborados) > 0 ){
            foreach($modelDiasNoLaborados as $diaNoLaboral){
                $arrayNoLaborados[] = date("Y-m-d", strtotime($diaNoLaboral->fechaCgDiaNoLaborado));
            }
            
        } else { // Si no hay una configuración activa, muestra mensaje de alerta

            return [
                'ok' => false,
                'data' => [
                    'message' => Yii::t('app', 'validErrorDays')
                ]            
            ];            
        }

        # Horario laboral activo
        $horarioValido = CgHorarioLaboral::findOne(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']]);
        
        if($horarioValido != null) {
            $diaMax = $horarioValido->diaFinCgHorarioLaboral;
            $diaInicio = $horarioValido->diaInicioCgHorarioLaboral;

        } else {

            return [
                'ok' => false,
                'data' => [
                    'message' => Yii::t('app','validErrorSchedule')
                ]            
            ];
        }
        
        # Se obtiene los prestamos aprobados igual o mayor a la fecha actual
        $modelRelation = GaHistoricoPrestamo::find();
            //->innerJoin('gaPrestamos', '`gaPrestamos`.`idGaPrestamo` = `gaHistoricoPrestamo`.`idGaPrestamo`')
            $modelRelation = HelperQueryDb::getQuery('innerJoin', $modelRelation, 'gaPrestamos', ['gaPrestamos' => 'idGaPrestamo', 'gaHistoricoPrestamo' => 'idGaPrestamo'])
            ->where(['>=', 'gaHistoricoPrestamo.fechaGaHistoricoPrestamo', $today])
            ->andWhere(['gaHistoricoPrestamo.estadoGaHistoricoPrestamo' => Yii::$app->params['statusTodoText']['PrestamoAprobado']]);
        $modelRelation = $modelRelation->all();

        $dateToday = new DateTime();
        $diffDays = [];
        $data = [];
        $return = []; //Array que contiene la información del prestamo

        if(!empty($modelRelation) && !in_array($today, $arrayNoLaborados) && date('w', strtotime($today)) <= $diaMax ){

            foreach($modelRelation as $dataLoan){

                $numeroRadicado = $dataLoan->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                # Calculo para obtener los días faltantes para que se venza el prestamo
                $dateRadi = new DateTime($dataLoan->fechaGaHistoricoPrestamo);  
                $diff = $dateRadi->diff($dateToday);

                if ($diff->days <= $daysParam && $diff->days >= 0) {

                    $diffDays[$numeroRadicado] = $diff->days;
                    $data[] = $dataLoan;
                   
                    $return = [
                        'data' => $data,
                        'diferenciaDias' => $diffDays,
                    ];                    
                }                 
            } 
        }  

        if(!empty($return)){
            return[
                'ok' => true,
                'data' => $return
            ]; 

        } else {
            return[
                'ok' => true,
                'data' => false,
            ];           
        }
    }

    /**
     * Pendiente por Transferencia
     * Este servicio es un CRON 
     * @return array message success 
     */

    public function actionPendienteTransferir()
    {
        $gdExpedientes = [];
        $dataTiempoCentral = [];
        $logSistema = [];
        $hoy = new DateTime(date('Y-m-d'));

        $userLog = User::findOne(Yii::$app->params['idUserLogCron']); ////////////////////


        ##############################  Inicio Gestion de archivo  ############################## 

        $gdExpedientes = GdExpedientes::find()
            ->where(['NOT IN','ubicacionGdExpediente', Yii::$app->params['ubicacionTransTRDNumber']['gestion']])
            ->andWhere(['estadoGdExpediente' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['<=', 'tiempoGestionGdExpedientes', trim(date("Y-m-d"))])
            ->all();

        foreach($gdExpedientes as $key => $gdExpediente){

            ##############################  Gestion de archivo  ############################## 

            $tiempoGestion = new DateTime(date("Y-m-d", strtotime($gdExpediente['tiempoGestionGdExpedientes'])));
            $intervalo = $hoy->diff($tiempoGestion);

            # si es igual a 0 el dia de vencimiento es hoy, si es igual a 1 invert es una fecha posterior al vencimiento
            if($intervalo->days == 0 || $intervalo->invert == 1){

                $gdExpediente->estadoGdExpediente = Yii::$app->params['statusTodoText']['PendienteTransferir'];
                $observacion = Yii::$app->params['eventosLogTextExpedientes']['pendienteTransferir'];

                /***    Log de Expedientes  ***/
                HelperLog::logAddExpedient(
                    $userLog->id, //Id user
                    $userLog->idGdTrdDependencia, // Id dependencia
                    $gdExpediente->idGdExpediente,
                    Yii::$app->params['operacionExpedienteText']['CambiarEstado'], //Operación
                    str_replace('{numExp}', $gdExpediente->numeroGdExpediente, $observacion)
                );
                /***  Fin  Log de Expedientes  ***/

                if(!$gdExpediente->save()){ 
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $gdExpediente->getErrors(),
                        'dataUpdate' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $dependencia = $gdExpediente->user->gdTrdDependencia['nombreGdTrdDependencia'];

                $logSistema[$gdExpediente->user['idGdTrdDependencia']]['dependencia'] = $dependencia;
                $logSistema[$gdExpediente->user['idGdTrdDependencia']]['nombreGdExpediente'][] = $gdExpediente['nombreGdExpediente'];
           
            }

        }

        # Guardar log de auditoria }
        foreach($logSistema as $dependencia => $data){
            foreach($data as $key => $value){

                $nombreGdExpediente = implode(", ",$data['nombreGdExpediente']);
                $observacion = Yii::$app->params['eventosLogText']['transferenciaDocumental'];

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    $userLog->id, //Id user
                    $userLog->username, //username
                    Yii::$app->controller->route, //Modulo
                    str_replace('{nomExp}', $nombreGdExpediente, $observacion).' '.$data['dependencia'],
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/
            
            }
        }

        ##############################  Fin Gestion de archivo  ############################## 

        ##############################  Inicio Central de archivo ########################################

        $gdExpedientes = GdExpedientes::find()
            ->where(['IN','ubicacionGdExpediente', Yii::$app->params['ubicacionTransTRDNumber']['gestion']])
            ->andWhere(['estadoGdExpediente' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['<=', 'tiempoCentralGdExpedientes', trim(date("Y-m-d"))])
            ->all();

        foreach($gdExpedientes as $key => $gdExpediente){

            ##############################  Gestion de archivo  ############################## 

            $tiempoCentral= new DateTime(date("Y-m-d", strtotime($gdExpediente['tiempoCentralGdExpedientes'])));
            $intervalo = $hoy->diff($tiempoCentral);
            $idRadiRadicados = [];
            $fechasExtremas = '';
            $numeroFolios = 0;

            # si es igual a 0 el dia de vencimiento es hoy, si es igual a 1 invert es una fecha posterior al vencimiento
            if($intervalo->days == 0 || $intervalo->invert == 1){  

                $gdExpediente->estadoGdExpediente = Yii::$app->params['statusTodoText']['PendienteTransferir'];
                $observacion = Yii::$app->params['eventosLogTextExpedientes']['pendienteTransferir'];

                /***    Log de Expedientes  ***/
                HelperLog::logAddExpedient(
                    $userLog->id, //Id user
                    $userLog->idGdTrdDependencia, // Id dependencia
                    $gdExpediente->idGdExpediente,
                    Yii::$app->params['operacionExpedienteText']['CambiarEstado'], //Operación
                    str_replace('{numExp}', $gdExpediente->numeroGdExpediente, $observacion)
                );
                /***  Fin  Log de Expedientes  ***/

                if(!$gdExpediente->save()){ 
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $gdExpediente->getErrors(),
                        'dataUpdate' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Encabezado del formato unico de inventario
                $dependencia = $gdExpediente->gdTrdDependencia['nombreGdTrdDependencia'];
                $oficina = $gdExpediente->gdTrdDependencia['codigoGdTrdDependencia'];
                # Encabezado del formato unico de inventario
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['oficinaProductora'] = $dependencia;
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['codigoOficina'] = $oficina;
                
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['idGdExpediente'][] = $gdExpediente['idGdExpediente'];

                # Datos del expediente para el formato unico de inventario

                $gaArchivo = GaArchivo::find();
                $gaArchivo = HelperQueryDb::getQuery('innerJoin', $gaArchivo, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gaArchivo' => 'idGdExpediente']);
                $gaArchivo = $gaArchivo->where(['gdExpedientesInclusion.idGdExpediente' => $gdExpediente->idGdExpediente])->one();

                if(!isset($gaArchivo)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'failArchivo',[
                            'numExp' => $gdExpediente['numeroGdExpediente']
                        ])]], 
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                # Calcular fechas extremas
                if(isset($gdExpediente->gdExpedientesInclusion)){ 

                    foreach($gdExpediente->gdExpedientesInclusion as $keyExpInclusion => $expedientesInclusion){
                        # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                        $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $keyExpInclusion;
                        $numeroFolios = $numeroFolios + $expedientesInclusion->radiRadicado['foliosRadiRadicado'];
                    }

                    if(count($idRadiRadicados) > 0){
                        $min = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[min(array_keys($idRadiRadicados))]]->radiRadicado;
                        $max = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[max(array_keys($idRadiRadicados))]]->radiRadicado;
                        $fechasExtremas = $min['creacionRadiRadicado'].'@'.$max['creacionRadiRadicado'];
                    }
                }

                /***
                 *  no_orden => Consecutivo
                 *  codigo_serie => Nombre serie + Nombre subSerie
                 *  numero_carpeta  => Numero carpeta asignada cuando se archivo el expediente
                 *  fechas_extremas => Inicio (fecha del primer radicado) Fin (fecha del ultimo radicado)
                 *  unidad_conservacion => (carpeta,caja,tomo,otro) marca x
                 *  numero_folios => suma de todos los folios configurados  de los radicados
                 *  soporte_documental => electronico
                 *  observaciones => formato realizado automaticamente por el sistema de gestion documental
                 */
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['GdExpedientePdf'][] = [
                    'no_orden' => ($key+1),
                    'no_expediente' => $gdExpediente['numeroGdExpediente'], # NUEVO
                    'codigo_serie' => $gdExpediente->gdTrdSerie['nombreGdTrdSerie'].' - '.$gdExpediente->gdTrdSubserie['nombreGdTrdSubserie'],
                    'numero_carpeta' => $gaArchivo['unidadCampoGaArchivo'],
                    'fechas_extremas' =>  $fechasExtremas,
                    'unidad_conservacion' =>  Yii::$app->params['unidadConservacionGaArchivoNumber'][$gaArchivo['unidadConservacionGaArchivo']],
                    'numero_folios' => $numeroFolios,
                    'soporte_documental' => Yii::t('app', 'soporteDocumental')['electronico'],
                    'observaciones' =>  Yii::t('app', 'transferenciaSegunTrd')['observaciones'],
                ];

                # Datos del expediente para la tabla de detalles que se enviara por correo
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['GdExpedienteMail'][] = [   
                    'numeroGdExpediente'  => $gdExpediente['numeroGdExpediente'],
                    'nombreGdExpediente'  => $gdExpediente['nombreGdExpediente'],
                    'nombreGdTrdSerie'    => $gdExpediente->gdTrdSerie['nombreGdTrdSerie'].'-'.$gdExpediente->gdTrdSerie['codigoGdTrdSerie'], 
                    'nombreGdTrdSubserie' => $gdExpediente->gdTrdSubserie['nombreGdTrdSubserie'].'-'.$gdExpediente->gdTrdSubserie['codigoGdTrdSubserie'], 
                    'inicioProceso' => date("Y-m-d H:i A", strtotime($gdExpediente['fechaProcesoGdExpediente'])),
                    'idUser' => $gdExpediente->user->userDetalles['nombreUserDetalles'].' '.$gdExpediente->user->userDetalles['apellidoUserDetalles']
                ];
             
            }
        }

        $envioCorreo = $this->notificarPendienteTransferencia($dataTiempoCentral, true, $userLog);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => Yii::t('app','successSave'),
            'correo' => $envioCorreo,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    public function notificarPendienteTransferencia($dataTiempoCentral = [], $notificarCreador = true, $userLog = null){

        # Consulta tipo de usuario "Administrador de Gestión documental"
        $userGestionDocumental = User::find()->where(['idUserTipo' => Yii::$app->params['tipoUsuario']['Administrador de Gestión Documental']])->all();

        $userNotificado = [];
        $idUserNotificado = [];
        $envioCorreo = [];

        # Se debe enviarse un solo correo con el consolidado de todas las dependencias
        foreach($dataTiempoCentral as $dependencia => $data){

            $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $dependencia]);

            $pathUploadFile = Yii::getAlias('@webroot') . 
            "/" . Yii::$app->params['routeDocuments'] . 
            "/" .$gdTrdDependencias->codigoGdTrdDependencia.
            "/" . "tmp" . "/";

            /*** Validar creación de la carpeta***/
            if (!file_exists($pathUploadFile)) {
                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                        'datafile' => false,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            }
            /*** Fin Validar creación de la carpeta***/


            $filename = Yii::$app->params['formUniInventario'].'-depe-'.$dependencia;
            if(isset($user_log)){
                $userDetalles = UserDetalles::find()->where(['idUser' => $userLog->id])->one();
            }else{
                $userDetalles = [];
            }
            
            $dataUser = [
                'nombre' => $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'],
                'cargo'  => $userDetalles['cargoUserDetalles'],
                'fecha'  => date("Y-m-d H:i A")
            ];
            $footer = Yii::t('app','footerFormatoUnicoInv');

            PdfController::generar_pdf_formatoh('GestiondeCorrespondencia','formatoInventarioView', $filename, $pathUploadFile, $data, [], $dataUser, $footer);

            # Envia la notificación de correo electronico
            //$nombreGdExpediente = implode(", ",$data['nombreGdExpediente']);

            // La tabla debe tener una fila de titulo la cual va a mostrar el nombre de la dependencia, 
            // y las columnas son las siguientes: Código Expediente, Nombre Expediente, Serie y Subserie, 
            // Fecha Inicio Proceso, Usuario Creador Expediente. 

            $bodyMail = 'radicacion-html';
            
            # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
            $headMailText = Yii::t('app', 'headMailTextExpedienteTitle'); 
            $textBody  = Yii::t('app', 'mailEventExpediente');
            $subject = Yii::t('app', 'headMailTextExpediente');

            $file = $pathUploadFile.$filename.'.pdf'; //ruta y nombre de la planilla 
            $buttonDisplay = false;

            # Títulos de la tabla
            $title = [
                'numeroGdExpediente'    => Yii::t('app', 'numeroGdExpediente'),
                'nombreGdExpediente'    => Yii::t('app', 'nombreGdExpediente'),
                'nombreGdTrdSerie'      => Yii::t('app', 'nombreGdTrdSerie'),
                'nombreGdTrdSubserie'   => Yii::t('app', 'nombreGdTrdSubserie'),
                'inicioProceso'         => Yii::t('app', 'inicioProceso'),
                'idUser'                => Yii::t('app', 'UserGdExpediente'),
            ];

            $params = [
                'table' => true,
                'tableData' => $data['GdExpedienteMail'],
                'adminSend' => false, // Permite mostrar la columna del nombre tramitador
                'titleTable' => $title,
                'titleColTramitador' => Yii::t('app', 'titleTramitador'), //Columna del tramitador
            ];     

            # Enviar una notificación de correo electrónico al perfil "Administrador de Gestión documental"
            foreach($userGestionDocumental as $user){
                $userNotificado[] = $user['email'];
                $idUserNotificado[] = $user['id'];
                $envioCorreo[] = CorreoController::addFile($user['email'], $headMailText, $textBody, $bodyMail, $file, $subject, $buttonDisplay, $params);
            }

            # Configuracion general del correo de administrador de transferencias
            $configGeneral = ConfiguracionGeneralController::generalConfiguration();

            # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
            if($configGeneral['status']){
                
                $usuarioNotificadorTransferencia = $configGeneral['data']['correoNotificadorAdminCgGeneral'];

                # Notificación al correo configurado del administrador de transferencias
                $envioCorreo[] = CorreoController::addFile($usuarioNotificadorTransferencia, $headMailText, $textBody, $bodyMail, $file, $subject, $buttonDisplay, $params); 

            } else {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => ['error' => [$configGeneral['message']]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            if($notificarCreador){

                # Enviar una notificación de correo electrónico al usuario responsable o creador el expediente
                foreach($data['idGdExpediente'] as $key => $idGdExpediente){
                    
                    $textBody  = Yii::t('app', 'mailEventExpeUserCreador',[
                     'depe' => $data['codigoOficina'].'-'.$data['oficinaProductora']
                    ]);
                    
                    #consultar en el log de espedientes la operacion crear expediente
                    $gdHistoricoExpedientes = GdHistoricoExpedientes::find()
                        ->where(['idGdExpediente' => $idGdExpediente, 
                        'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['CrearExpediente']])
                    ->one();

                    if(!is_null($gdHistoricoExpedientes)) {
                    
                        $emailCreador = $gdHistoricoExpedientes->userHistoricoExpediente->email;
                        $idCreador = $gdHistoricoExpedientes->idUser;

                        # validar envio de notificacion duplicada para el usuario creador
                        if(!in_array($emailCreador, $userNotificado)){

                            $userNotificado[] = $emailCreador;

                            # Se itera el usuario notificado para crear la notificación.
                            foreach($idUserNotificado as $id){

                                /***  Notificacion  ***/
                                HelperNotification::addNotification(
                                    $idCreador, //Id user creador  'el historico'
                                    $id, // Id user notificado 'rol administrador'
                                    Yii::t('app','messageNotification')['pendingTransfer'], //Notificacion
                                    Yii::$app->params['routesFront']['indexTransferencia'], // url
                                    '' // id radicado
                                );
                                /***  Fin Notificacion  ***/
                            }

                            $envioCorreo[] = CorreoController::addFile($emailCreador, $headMailText, $textBody, $bodyMail, $file, $subject, $buttonDisplay, $params);
                        }
                    }
                }
            }
        }

        return [
            'campana' => $userNotificado,
            'email' => $envioCorreo
        ];

    }

    public function actionExpiredCitizenResponse(){    
       
        //Transacción que identifica la devolución de un radicado al ciudadano en las tablas radiLogs
        $transaccion = CgTransaccionesRadicados::find()->select(['idCgTransaccionRadicado'])
            ->where(['actionCgTransaccionRadicado' => 'returnPqrToCitizen'])
            ->one()->idCgTransaccionRadicado
        ;

        //Consultas los radicados que esten en estado devuelto al ciudadano
        $modelRadicados = RadiRadicados::find()            
            ->where(['estadoRadiRadicado' => Yii::$app->params['statusTodoText']['DevueltoAlCiudadano']])            
        ->all();

        //Obtener usuario que gestiona los radicados finalizado para asignarselo a los radicados sin respuesta del cliente vencidos
        $useDeSalida = User::findOne(['username' => Yii::$app->params['userNameDeSalida']]);

        if(!isset($useDeSalida)){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'userNoExistente'),
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    
        // Consulta de transaccion
        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'finalizeFiling']);
        $isData = false;
                  
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

        $transaction = Yii::$app->db->beginTransaction();

        foreach($modelRadicados as $value){

            $modelRadiLog = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $value->idRadiRadicado])
                ->andWhere(['idTransaccion' => $transaccion])
                ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
            ->one();

            $fechaDevolucion = $modelRadiLog->fechaRadiLogRadicado;  
            $fechaDevolucion = date("Y-m-d H:i:s",strtotime($fechaDevolucion));
            
            $fechaActual = date("Y-m-d");            
            $fechaDesde = $fechaDevolucion;
            $fechaHasta = Date('Y-m-d H:i:s');

            /*if ($fechaDevolucion > $fechaActual) {
                $fechaHasta = $fechaHasta . ' 23:59:59';
            } else {
                $fechaHasta = $fechaHasta . ' 00:00:00';
            }*/            

            $diasTranscurridos = HelperRadicacion::calcularDiasEntreFechas($fechaDesde, $fechaHasta);            

            if($diasTranscurridos > $diasRespuestaPqrs){

                $isData = true;
                
                $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $value->user_idTramitador]);
                $usuarioInfoOldTramitador = UserDetalles::findOne(['idUser'=> $value->user_idTramitadorOld]);
    
                $dataRadicadosOld = 'Id Radicado: '.$value->idRadiRadicado;
                $dataRadicadosOld .= ', Número Radicado: '.$value->numeroRadiRadicado;              
                $dataRadicadosOld .= ', Tipo Radicación: '.$value->cgTipoRadicado->nombreCgTipoRadicado;      
                $dataRadicadosOld .= ', Usuario Tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                
                if(!is_null($usuarioInfoOldTramitador)){
                    $dataRadicadosOld .= ', Old Usuario Tramitador: '.$usuarioInfoOldTramitador->nombreUserDetalles.' '.$usuarioInfoOldTramitador->apellidoUserDetalles;
                }               
                
                $dataRadicadosOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$value->estadoRadiRadicado];

                $value->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Finalizado'];
                $value->user_idTramitadorOld = $value->user_idTramitador;
                $value->user_idTramitador = $useDeSalida['id'];
                $value->idTrdDepeUserTramitador = $useDeSalida['idGdTrdDependencia'];
                
                if(!$value->save()){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $value->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }else{
                    $usuarioInfoTramitador = UserDetalles::findOne(['idUser'=> $value->user_idTramitador]);
                    $usuarioInfoOldTramitador = UserDetalles::findOne(['idUser'=> $value->user_idTramitadorOld]);

                    $dataRadicados = 'Id Radicado: '.$value->idRadiRadicado;
                    $dataRadicados .= ', Número Radicado: '.$value->numeroRadiRadicado;                  
                    $dataRadicados .= ', Tipo Radicación: '.$value->cgTipoRadicado->nombreCgTipoRadicado;                    
                    $dataRadicados .= ', Usuario Tramitador: '.$usuarioInfoTramitador->nombreUserDetalles.' '.$usuarioInfoTramitador->apellidoUserDetalles;
                    $dataRadicados .= ', Old Usuario Tramitador: '. $usuarioInfoOldTramitador->nombreUserDetalles.' '. $usuarioInfoOldTramitador->apellidoUserDetalles;                    
                    $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$value->estadoRadiRadicado];
                }

                $observacion = 'Se marcó automáticamente el radicado de PQRSD No. {radiNum} como desistimiento, ya que el ciudadano no respondió en el tiempo indicado la solicitud hecha por la entidad.';

                /***    Log de Radicados  ***/
                HelperLog::logAddFiling(
                    Yii::$app->params['idUserTramitadorPqrs'],
                    Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                    $value->idRadiRadicado, //Id radicado
                    $idTransacion->idCgTransaccionRadicado,
                    str_replace('{radiNum}',$value['numeroRadiRadicado'],$observacion),
                    $value,
                    array() //No validar estos campos
                );
                /***    Fin log Radicados   ***/ 
                
                $userPqrs = UserDetalles::findOne(['idUser' => Yii::$app->params['idUserTramitadorPqrs']]);
        
                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    true, //type
                    Yii::$app->params['idUserTramitadorPqrs'],
                    "$userPqrs->nombreUserDetalles $userPqrs->apellidoUserDetalles",
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['FinalizeFile'] . " N°. ".$value->numeroRadiRadicado." por el motivo de: ". str_replace('{radiNum}',$value['numeroRadiRadicado'],$observacion) ." Con el estado ". Yii::$app->params['statusTodoNumber'][$value->estadoRadiRadicado].", en la tabla radiRadicados", //texto para almacenar en el evento
                    $dataRadicados, //Data
                    $dataRadicadosOld,
                    array() //No validar estos campos
                );                    
                           
            }
        }

        $transaction->commit();

        if($isData){

            /* Mensaje de retorno success*/
            Yii::$app->response->statusCode = 200;            
            $response = [
                'message' => Yii::t('app','successExpiredCitizenResponse'),
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
        } else{

            Yii::$app->response->statusCode = 200;            
            $response = [
                'message' => Yii::t('app','empetyExpiredCitizenResponse'),
                'data' => [],
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        }       
    }

    /***** Acción para el manejo de servicios para la visualización del QR en el front *****/
    /***************************************************************************************/
    public function actionViewQr() {

        $jsonSend = Yii::$app->request->post('jsonSend');
        if (!empty($jsonSend)) {

            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decrypt($jsonSend);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                Yii::$app->response->statusCode = Yii::$app->params['statusErrorEncrypt'];
                $response = [
                    'message' => Yii::t('app', 'errorDesencriptacion'),
                    'data' => Yii::t('app', 'errorDesencriptacion'),
                    'status' => Yii::$app->params['statusErrorEncrypt'],
                ];
                return HelperEncryptAes::encrypt($response);
            }
            //*** Fin desencriptación POST ***//

            /*** Reemplzado de las variables para encontrar el crypto ***/
            $idQrBaseDecodeReplace = str_replace(array('_', '-'), array('/', '+'), $request['idQr']);
            $idQrBaseDecode = base64_decode($idQrBaseDecodeReplace);
            $idQrDecrypt = HelperEncryptAes::decrypt($idQrBaseDecode);

            $model = GdFirmasQr::find()->where(['idGdFirmasQr' => $idQrDecrypt['request']])->one();

            /** Radicado Padre */
            $numeroRadiPadre = '';
            $modelDocumentoPrincipal = RadiDocumentosPrincipales::find()->where(['idradiDocumentoPrincipal' => $model->idDocumento])->one();

            if ($modelDocumentoPrincipal->idRadiRespuesta != null && $modelDocumentoPrincipal->idRadiRespuesta != $modelDocumentoPrincipal->idRadiRadicado) {
                $statusRadiPadre = true;
                $modelRadicadoPadre = RadiRadicados::find()->where(['idRadiRadicado' => $modelDocumentoPrincipal->idRadiRadicado])->one();
                $numeroRadiPadre = HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadicadoPadre->numeroRadiRadicado, $modelRadicadoPadre->idCgTipoRadicado, $modelRadicadoPadre->isRadicado);
                $titleRadiPadre = ((boolean) $modelRadicadoPadre->isRadicado == true) ? 'Información radicado principal' : 'Información consecutivo temporal principal';
                $labelRadiPadre = ((boolean) $modelRadicadoPadre->isRadicado == true) ? 'Número radicado principal' : 'Número consecutivo temporal principal';
                
                $idRadiRadicadoFirma = $modelDocumentoPrincipal->idRadiRespuesta;
            } else {
                $statusRadiPadre = false;
                $modelRadicadoPadre = null;
                $numeroRadiPadre = '';
                $titleRadiPadre = '';
                $labelRadiPadre = '';

                $idRadiRadicadoFirma = $modelDocumentoPrincipal->idRadiRadicado;
            }
            /** Radicado Padre */

            $userDetallesFirma = UserDetalles::find()->where(['idUser' => $model->idUser])->one();

            $modelRadiRadicadoFirma = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicadoFirma])->one();

            $dataList = array(
                'fechaFirma' => substr($model->creacionGdFirmasQr, 0, 10),
                'horaFirma' => substr($model->creacionGdFirmasQr, 11, 8),
                'codigoDependencia' => $model->user->gdTrdDependencia->codigoGdTrdDependencia,
                'nombreDependencia' => $model->user->gdTrdDependencia->nombreGdTrdDependencia,
                'nombreUserDetallesFirma' => $userDetallesFirma->nombreUserDetalles,
                'apellidoUserDetallesFirma' => $userDetallesFirma->apellidoUserDetalles,
                'labelRadiFirma' => ((boolean)$modelRadiRadicadoFirma->isRadicado == true) ? 'Radicado' : 'Consecutivo temporal',
                'numeroRadicadoFirma' => HelperConsecutivo::numeroRadicadoXTipoRadicado($modelRadiRadicadoFirma->numeroRadiRadicado, $modelRadiRadicadoFirma->idCgTipoRadicado, $modelRadiRadicadoFirma->isRadicado),
                'creacionRadiFirma' => $modelRadiRadicadoFirma->creacionRadiRadicado,
                'asuntoRadiFirma' => $modelRadiRadicadoFirma->asuntoRadiRadicado,

                'nombreDocumento' => $modelDocumentoPrincipal->nombreRadiDocumentoPrincipal,
                'fechaDocumento' => $modelDocumentoPrincipal->creacionRadiDocumentoPrincipal,

                'statusRadiPadre' => $statusRadiPadre,
                'numeroRadiPadre' => $numeroRadiPadre,
                'titleRadiPadre' => $titleRadiPadre,
                'labelRadiPadre' => $labelRadiPadre
            );

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'ok',
                'data' => $dataList,
                'status' => 200
            ];

            return HelperEncryptAes::encrypt($response);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    /**** Pruebas Oracle ****/
    /************************/
    public function actionTestList() {

        $dataList = [];

        $modelFind = TestOciJaime::find()->all();

        foreach ($modelFind as $model) {
            $dataList[] = array(
                'id' => $model->id,
                'nombre' => $model->nombre,
                'creacion' => $model->creacion,
                'estado' => $model->estado,
            );
        }

        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200
        ];

        return $response;
    }

    public function actionTestInsert() {

        $dataList = [];

        $jsonSend = Yii::$app->request->post('jsonSend');
        $dataPost = json_decode($jsonSend, true);

        $model = new TestOciJaime();
        $model->nombre = $dataPost['nombre'];
        $model->creacion = $dataPost['creacion'];
        $model->estado = $dataPost['estado'];

        if($model->save()) {
            $modelFind = TestOciJaime::find()->all();

            foreach ($modelFind as $model) {
                $dataList[] = array(
                    'id' => $model->id,
                    'nombre' => $model->nombre,
                    'creacion' => $model->creacion,
                    'estado' => $model->estado,
                );
            }
        }

        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200
        ];

        return $response;
    }

    public function actionTestUpdate($id) {

        $dataList = [];

        $jsonSend = Yii::$app->request->post('jsonSend');
        $dataPost = json_decode($jsonSend, true);

        $model = TestOciJaime::find()->where(['id' => $id])->one();
        $model->nombre = $dataPost['nombre'];
        $model->creacion = $dataPost['creacion'];
        $model->estado = $dataPost['estado'];

        if($model->save()) {
            $modelFind = TestOciJaime::find()->all();

            foreach ($modelFind as $model) {
                $dataList[] = array(
                    'id' => $model->id,
                    'nombre' => $model->nombre,
                    'creacion' => $model->creacion,
                    'estado' => $model->estado,
                );
            }
        }

        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200
        ];

        return $response;
    }

    public function actionTestDelete($id) {

        $dataList = [];

        $modelDelete = TestOciJaime::findOne($id);

        if($modelDelete->delete()) {
            $modelFind = TestOciJaime::find()->all();

            foreach ($modelFind as $model) {
                $dataList[] = array(
                    'id' => $model->id,
                    'nombre' => $model->nombre,
                    'creacion' => $model->creacion,
                    'estado' => $model->estado,
                );
            }
        }

        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200
        ];

        return $response;
    }
    /**** Fin Pruebas Oracle ****/
    /****************************/

    /** 
     * Reinicio de secuencias por tipo de radicado, esto lo que hace el inicializar el consecutivo
     * de cada tipo de radicado en 0 para que cuando se inicie año mediante cron se ejecute este servicio,
     * esto debe aplicar para todos los tipos de radicados es decir no me debe afectar si el tipo de radicado 
     * este Activo o Inactivo.
     * Tambien reiniciará el consecutivo para el número del expediente
     **/
    public function actionRebootSequences(){

        #Reinicio del consecutivo del radicado (Ya no es necesario)
        // $anioActual = date('Y');
        // $modelCgConsecutivosRadicados = CgConsecutivosRadicados::find()->where(['anioCgConsecutivoRadicado' => $anioActual])->all();

        // foreach ($modelCgConsecutivosRadicados as $consecutivo) {
        //      $cgConsecutivosRadicados = CgConsecutivosRadicados::find()->where(['idCgConsecutivoRadicado' => $consecutivo->idCgConsecutivoRadicado])->one();
        //      $cgConsecutivosRadicados->cgConsecutivoRadicado = 0;
        //      $cgConsecutivosRadicados->save();
        // }

        #Reinicio del consecutivo del expediente
        $modelDependence = GdTrdDependencias::find()->all();

        foreach ($modelDependence as $dataDependence) {
                
            $updateDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => $dataDependence->idGdTrdDependencia]);
            $updateDependence->consecExpedienteGdTrdDependencia = 0;
                
            $updateDependence->save();
        }
    }

    /** 
     * Creación de las carpetas de las dependencias en la bodega segun como corresponde para cada año.
     * esto sera ejecutado mendiante cron que se ejecutara todos los 1 de enero del año nuevo. 
     **/
    public function actionBodegaCreate(){

        $rutaOk = false;
        $modelDependencia = GdTrdDependencias::findAll(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']]);

        // Por cada una de las dependencias activas se genera un directorio
        foreach ($modelDependencia as $dependenciaDato) {

            //Ruta donde se almacenan los documentos relacionados a los radicados por dependencia
            $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDocuments'] . "/" . $dependenciaDato->codigoGdTrdDependencia . '/tmp/';

            // Verificar que la carpeta exista y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {

                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }else{
                    $rutaOk = true;
                }
            }

            // Verifica si existe carpeta de Downloads en caso de no existir se crea
            $pathUploadFileDownloads = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDownloads'];
            if(!file_exists($pathUploadFileDownloads)){
                
                if (!FileHelper::createDirectory($pathUploadFileDownloads, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }else{
                    $rutaOk = true;
                }
            }
        }

        if($rutaOk){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => '',
                'data' => $rutaOk,
                'status' => '',
            ];
            return HelperEncryptAes::encrypt($response);
        }
    }

    /**
     * Servicio que permite comprobar la conexión contra los dastos del directorio activo
     **/
    public function actionTestConnection(){
        // Se conecta a ldap
        $model = [];
        $model['username'] = 'orfeo';
        $model['password'] = 'orfeo';

        return $configLdap = $this->configLdap($model);

        //Valida el usuario en el directorio activo
        if ($configLdap['status'] == true) {
           $model['ldap'] = true; // Estableciendo la variable de validación de login por directorio activo de ldap en true
        } else {
           Yii::$app->response->statusCode = 200;
           $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'Incorrect username or password ldap')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
           ];
           return HelperEncryptAes::encrypt($response);
        }
    }

    public function actionGeneraActa(){

        $currentPage = 1; //Inicio de pagina del acta
        $numeroActa = 18; // codigo del acta generada

        $modelRadicadoAnulado = RadiRadicadoAnulado::findAll(['codigoActaRadiRadicadoAnulado' => $numeroActa]);

        foreach ($modelRadicadoAnulado as $key => $infoRadicadoAnulado) {

            # Se almacenan en un arreglo las observaciones que se realizaron en la solicitud ya que son las que van en el acta
            $observacionesRadicado[$infoRadicadoAnulado['idRadicado']] = $infoRadicadoAnulado['observacionRadiRadicadoAnulado'];

            # Se agrupa todos los radicados seleccionados
            $idRadiAnulados[] = $infoRadicadoAnulado['idRadicado']; 

            # Array donde contendrá del modelo del radicado anulado
            $modelRadicados[] = $infoRadicadoAnulado; 
            
            $fechaConsulta = $infoRadicadoAnulado['fechaRadiRadicadoAnulado'];

        }

        # Nombre del archivo del pdf
        $fileName = Yii::$app->params['baseNameFileActaAnulacion'].$numeroActa.'.pdf';

        # Creación del pdf
        if(isset($idRadiAnulados) && is_array($idRadiAnulados)){

            $pdfStructure = AnulacionController::createPdf($idRadiAnulados, $numeroActa, $observacionesRadicado, $currentPage, 1, $fechaConsulta);

            # Concatena la estructura de todos los radicados que se veran en un acta
            $createPdf[] = $pdfStructure['html'];       
            $style = $pdfStructure['style'];
        }  


        # Margenes del pdf
        $margin = [
            'mode' => 'c',
            'margin_left' => 25,
            'margin_right' => 25,
            'margin_top' => 25,
            'margin_bottom' => 20,
            'margin_header' => 16,
            'margin_footer' => 13
        ];

        # Se construye el pdf con mpdf
        $generatePdf = PdfController::generatePdf($createPdf, 'actas', $fileName, $style, 'P', $margin, TRUE);
        return $generatePdf;

    }

    public function actionSignDocumentOptions() {
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
        $opcionesFirmaDisponibles = [];
        $opcionesAdicionalesfirmas = [];

        $modelDocumentos = RadiDocumentosPrincipales::findOne(['idradiDocumentoPrincipal'=> $request['ButtonSelectedData'][0]['id']]);
        $documentoFirmar = $modelDocumentos->rutaRadiDocumentoPrincipal;
        $rutaDocumentoFirmar = explode('/', $documentoFirmar);
        $rutaDocumentoFirmarTemp = "";
        for ($i = 0; $i < count($rutaDocumentoFirmar) - 1; $i++) {
            $rutaDocumentoFirmarTemp .= $rutaDocumentoFirmar[$i] . "/";
        }
        $rutaDocumentoFirmarTemp .= "tmpCertificada/";
        $nameFileExtension = $rutaDocumentoFirmar[count($rutaDocumentoFirmar) - 1];
        $nameFile = explode('.', $nameFileExtension);

        $documentoFinal = "";
        if($modelDocumentos->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['CombinadoSinFirmas']) {
            //exec("\"C:\\Program Files\\LibreOffice\\program\\soffice.com\" --headless --convert-to pdf:writer_pdf_Export $documentoFirmar --outdir $rutaDocumentoFirmarTemp");
            exec("unoconvert --convert-to pdf {$documentoFirmar} ". $rutaDocumentoFirmarTemp . $nameFile[0] .'.pdf');
            $documentoFinal = $rutaDocumentoFirmarTemp . $nameFile[0] . '.pdf';
            $documentoInfo = $rutaDocumentoFirmarTemp . $nameFile[0] . '.pdf';
            
            $infoDocumento = new PDFInfo($documentoInfo);
            $tamanoXpagina = [];
            if ($infoDocumento) {
                $pageSize = preg_match_all('!\d+!', $infoDocumento->pageSize, $tamanoXpagina);
            }

        } else {
            if (!file_exists($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '/img')) {
                mkdir($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '/img', 0777, true);
            }

            $dirImgFirmarTemp = $rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal ."/img";
            exec("pdftoppm -png ". $documentoFirmar ." ". $dirImgFirmarTemp ."/imagen");

            $imageFiles = glob($dirImgFirmarTemp . '/*.png');
            sort($imageFiles);

            $infoDocumento = new PDFInfo($documentoFirmar);
            $tamanoXpagina = [];
            if ($infoDocumento) {
                $pageSize = preg_match_all('!\d+!', $infoDocumento->pageSize, $tamanoXpagina);
            }

            $tamanoPaginaWidth = (count($tamanoXpagina[0]) > 2) ? $tamanoXpagina[0][0] : $tamanoXpagina[0][0];
            $tamanoPaginaHeight = (count($tamanoXpagina[0]) > 2) ? $tamanoXpagina[0][2] : $tamanoXpagina[0][1];
            $mpdfWidth = number_format($tamanoPaginaWidth / 3.77, 0);
            $mpdfHeight = number_format($tamanoPaginaHeight / 3.77, 0);

            $mpdf = new \Mpdf\Mpdf(['format' => [$mpdfWidth, $mpdfHeight]]);
            foreach ($imageFiles as $imageFile) {
                $mpdf->AddPage();
                $mpdf->SetXY(0, 0);
                $mpdf->Image($imageFile, 0, 0, 0, 0, 'PNG', '', '', true, 300, '', false, false, 0, 'C');
                unlink($imageFile);
            }
            $mpdf->Output($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '.pdf');
            $documentoFinal = $rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '.pdf';
            rmdir($dirImgFirmarTemp);
            rmdir($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal);
        }

        $dataFile = base64_encode(file_get_contents($documentoFinal));

        $modelFirmasCertificadas = InitCgEntidadesFirma::findAll(['estadoInitCgEntidadFirma' => Yii::$app->params['statusTodoText']['Activo']]);
        foreach($modelFirmasCertificadas as $opcionFirma) {
            $opcionesFirmaDisponibles[] = $opcionFirma['nombreInitCgEntidadFirma'];
            // Consulta la información de configuración de cada entidad de firma
            $initCgConfigFirmas = InitCgConfigFirmas::findAll(['idInitCgEntidadFirma' => $opcionFirma['idInitCgEntidadFirma']]);
            foreach($initCgConfigFirmas as $valorParams) {
                // Consulta el valor de cada tipo de parametro
                $initCgParamsFirma = InitCgParamsFirma::findOne(['idInitCgParamFirma' => $valorParams['idInitCgParamFirma']]);
                $opcionesAdicionalesfirmas[$opcionFirma->nombreInitCgEntidadFirma][] = array($initCgParamsFirma->variableInitCgParamFirma => $valorParams['valorInitCgConfigFirma']);
            }
        }

        if (file_exists($rutaDocumentoFirmarTemp . $nameFile[0] . '.pdf')) {
            unlink($rutaDocumentoFirmarTemp . $nameFile[0] . '.pdf');
        }

        if (file_exists($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '.pdf')) {
            unlink($rutaDocumentoFirmarTemp . $modelDocumentos->idradiDocumentoPrincipal . '.pdf');
        }

        $response = [
            'message' => [],
            'data' => $opcionesFirmaDisponibles ?? [],
            'dataAdicional' => $opcionesAdicionalesfirmas ?? [],
            'infoDocumento' => $infoDocumento ?? [],
            'tamanoPaginaWidth' => (count($tamanoXpagina[0]) > 2) ? $tamanoXpagina[0][0] : $tamanoXpagina[0][0],
            'tamanoPaginaHeight' => (count($tamanoXpagina[0]) > 2) ? $tamanoXpagina[0][2] : $tamanoXpagina[0][1],
            'status' => 200,
        ];

        Yii::$app->response->statusCode = 200;
        $return = HelperEncryptAes::encrypt($response, true);
        $return['datafile'] = $dataFile;
        return $return;
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

                $initCgEntidadesFirma = InitCgEntidadesFirma::findOne(['nombreInitCgEntidadFirma' => $request['selectedoption']]);

                if($request['selectedoption'] == 'Andes'){

                    $request['selectedoption'] = $initCgEntidadesFirma->idInitCgEntidadFirma;
                    $respuesta = initCgEntidadFirmaAndesController::RutinaFirmarDocumento($request);
                }

                if(isset($respuesta['message'])){

                    $explodeEstado = explode('|',$respuesta['message']);

                    if(isset($explodeEstado) && count($explodeEstado) > 1){

                        switch($explodeEstado[0]){
                            case 201:
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'notHaveSignature')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                            case 2:
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'notHaveSignature')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                            case 999:
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [$explodeEstado[1]]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                        }
                    } else {

                        switch($respuesta['status']){
                            case 27:
    
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [$respuesta['message']]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                            case 23:
    
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [$respuesta['message']]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                            case 322:
    
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [$respuesta['message']]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            break;
                        }


                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => 'Se firmo correctamente el documento.',
                            'data' => [],
                            'request' => $request,
                            'status' => 200
                        ];
                        return HelperEncryptAes::encrypt($response, true);

                    }
                    
                }
                else{

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
            }
            else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return HelperEncryptAes::encrypt($response, true);
            }
    }

    // Listado de cordenadas de firmas
    public function actionIndexSignatureCoordinates()
    {
        $dataCordenadasFirma = [];
        
        $modelCordenadasFirma = InitCgCordenadasFirma::find()
            ->where(['estadoInitCgCordenadaFirma' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreInitCgCordenadaFirma' => SORT_ASC])
            ->all();            

        foreach ($modelCordenadasFirma as $row) {
            $dataCordenadasFirma[] = array(
                "id" => (int) $row['idInitCgCordenadaFirma'],
                "val" => $row['nombreInitCgCordenadaFirma'],
            );
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'dataCordenadasFirma' => $dataCordenadasFirma, // Tipos de medios de Recepcion
            'status' => 200,
        ];
        return $response;
    }
    
}
