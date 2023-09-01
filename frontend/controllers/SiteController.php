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

namespace frontend\controllers;

use api\components\HelperLog;
use api\components\HelperQueryDb;
use common\models\User;

use api\models\Clientes;
use api\models\ClientesCiudadanosDetalles;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdTrdTiposDocumentales;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiRadicados;
use api\models\Roles;
use api\models\TiposIdentificacion;
use api\models\TiposPersonas;
use api\models\UserDetalles;
use api\models\UserPqrs;
use api\models\CgGeneral;
use api\models\CgFormulariosPqrsDetalle;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use api\modules\version1\controllers\correo\CorreoController;
use frontend\models\ResendVerificationEmailForm;
use frontend\models\VerifyEmailForm;
use Yii;
use yii\base\InvalidArgumentException;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use frontend\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\helpers\ArrayHelper;
use api\models\CgEncuestas;
use api\models\CgEncuestaPreguntas;
use api\models\ClienteEncuestas;
use api\models\EncuestaCalificaciones;

use api\models\CgClasificacionPqrs;
use api\models\CgGeneroPqrs;
use api\models\CgRangoEdadPqrs;
use api\models\CgVulnerabilidadPqrs;
use api\models\CgGrupoEtnicoPqrs;
use api\models\CgActividadEconomicaPqrs;
use api\models\CgCondicionDiscapacidadPqrs;
use api\models\CgEstratoPqrs;
use api\models\CgGrupoInteresPqrs;
use api\models\CgGrupoSisbenPqrs;
use api\models\CgEscolaridadPqrs;

use frontend\controllers\RegistroPqrsController;
use frontend\models\RadiRadicadosForm;
use frontend\models\Anexos;
use api\models\RadiDetallePqrsAnonimo;
use api\modules\version1\controllers\pdf\PdfController;
use api\models\GdTrdDependencias;
use yii\helpers\FileHelper;
use kartik\mpdf\Pdf;
use PHPUnit\Util\Json;
use yii\helpers\Json as HelpersJson;

use Blocktrail\CryptoJSAES\CryptoJSAES;
use yii\helpers\Url;

/**
 * Site controller
 */
class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['GET'],
                    'request-password' => ['POST']
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return mixed
     */
    public function actionIndex()
    {
        // Consulta la configuración general del sistema para tomar los terminos y condiciones configurados para que se muestren el frontend
        $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);

        # Filtro de búsqueda
        $request = Yii::$app->request;
        $key = $request->get('key');
        $documentos = [];

        # Relacionamiento de radicados publicos pqrs     
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        $tablaExpInclusion = GdExpedientesInclusion::tableName() . ' AS EI';
        $tablaExpediente = GdExpedientes::tableName() . ' AS EX';
        $tablaDoc = RadiDocumentos::tableName() . ' AS DOC';
        $tablaDocPrincipal = RadiDocumentosPrincipales::tableName() . ' AS PR';
        $tablaTipoDoc = GdTrdTiposDocumentales::tableName() . ' AS TD';

        $queryDoc = (new \yii\db\Query())
            ->from($tablaRadicado);
        // ->leftJoin($tablaExpInclusion, '`RD`.`idRadiRadicado` = `EI`.`idRadiRadicado`')
        $queryDoc = HelperQueryDb::getQuery('leftJoinAlias', $queryDoc, $tablaExpInclusion, ['RD' => 'idRadiRadicado', 'EI' => 'idRadiRadicado']);
        // ->leftJoin($tablaExpediente, '`EX`.`idGdExpediente` = `EI`.`idGdExpediente`')           
        $queryDoc = HelperQueryDb::getQuery('leftJoinAlias', $queryDoc, $tablaExpediente, ['EX' => 'idGdExpediente', 'EI' => 'idGdExpediente']);
        // ->innerJoin($tablaDoc, '`DOC`.`idRadiRadicado` = `RD`.`idRadiRadicado`') 
        $queryDoc = HelperQueryDb::getQuery('innerJoinAlias', $queryDoc, $tablaDoc, ['DOC' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
        // ->innerJoin($tablaTipoDoc, '`TD`.`idGdTrdTipoDocumental` = `DOC`.`idGdTrdTipoDocumental`')
        $queryDoc = HelperQueryDb::getQuery('innerJoinAlias', $queryDoc, $tablaTipoDoc, ['TD' => 'idGdTrdTipoDocumental', 'DOC' => 'idGdTrdTipoDocumental']);
        $queryDoc = $queryDoc->andWhere(['DOC.publicoPagina' => Yii::$app->params['valuePageText']['Publico']])
            ->orderBy(['DOC.idRadiRadicado' => SORT_DESC])
            ->limit(Yii::$app->params['limitPagePublicRecords']);

        #Texto a filtrar
        if (!is_null($key)) {

            $queryDoc->andWhere([
                'or',
                [Yii::$app->params['like'], 'RD.numeroRadiRadicado', $key],
                [Yii::$app->params['like'], 'EX.numeroGdExpediente', $key],
                [Yii::$app->params['like'], 'RD.asuntoRadiRadicado', $key],
                [Yii::$app->params['like'], 'TD.nombreTipoDocumental', $key]
            ]);
        }
        $modelDoc = $queryDoc->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelDoc = array_reverse($modelDoc);

        foreach ($modelDoc as $dataDoc) {

            if ($dataDoc['numeroGdExpediente'] == null) {
                $expediente = 'Sin expediente';
            } else {
                $expediente = $dataDoc['numeroGdExpediente'];
            }

            if (file_exists($dataDoc['rutaRadiDocumento'] . $dataDoc['nombreRadiDocumento'])) {
                $data = base64_encode(file_get_contents($dataDoc['rutaRadiDocumento'] . $dataDoc['nombreRadiDocumento']));
            } else {
                $data = false;
            }

            $documentos[] = [
                'idRadicado' => $dataDoc['idRadiRadicado'],
                'numeroRadicado' => $dataDoc['numeroRadiRadicado'],
                'numeroExpediente' => $expediente,
                'tipoDocumental' => $dataDoc['nombreTipoDocumental'],
                'asunto' => $dataDoc['asuntoRadiRadicado'],
                'extension' => $dataDoc['extencionRadiDocumento'],  //puede ser diferente a pdf 'validarlo'
                'url' => $dataDoc['rutaRadiDocumento'] . $dataDoc['nombreRadiDocumento'],
                'data' => $data,
                'nombreArchivo' => $dataDoc['nombreRadiDocumento'],
            ];
        }

        $queryDocPrincipal = (new \yii\db\Query())
            ->from($tablaRadicado);

        // ->leftJoin($tablaExpInclusion, '`RD`.`idRadiRadicado` = `EI`.`idRadiRadicado`')
        $queryDocPrincipal = HelperQueryDb::getQuery('leftJoinAlias', $queryDocPrincipal, $tablaExpInclusion, ['RD' => 'idRadiRadicado', 'EI' => 'idRadiRadicado']);
        // ->leftJoin($tablaExpediente, '`EX`.`idGdExpediente` = `EI`.`idGdExpediente`')
        $queryDocPrincipal = HelperQueryDb::getQuery('leftJoinAlias', $queryDocPrincipal, $tablaExpediente, ['EX' => 'idGdExpediente', 'EI' => 'idGdExpediente']);
        // ->innerJoin($tablaDocPrincipal, '`PR`.`idRadiRadicado` = `RD`.`idRadiRadicado`')
        $queryDocPrincipal = HelperQueryDb::getQuery('innerJoinAlias', $queryDocPrincipal, $tablaDocPrincipal, ['PR' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
        // ->innerJoin($tablaTipoDoc, '`TD`.`idGdTrdTipoDocumental` = `RD`.`idTrdTipoDocumental`') 
        $queryDocPrincipal = HelperQueryDb::getQuery('innerJoinAlias', $queryDocPrincipal, $tablaTipoDoc, ['TD' => 'idGdTrdTipoDocumental', 'RD' => 'idTrdTipoDocumental']);

        $queryDocPrincipal = $queryDocPrincipal->andWhere(['PR.publicoPagina' => Yii::$app->params['valuePageText']['Publico']])
            ->orderBy(['RD.idRadiRadicado' =>  SORT_DESC])
            ->limit(Yii::$app->params['limitPagePublicRecords']);

        #Texto a filtrar
        if (!is_null($key)) {

            $queryDocPrincipal->andWhere([
                'or',
                [Yii::$app->params['like'], 'RD.numeroRadiRadicado', $key],
                [Yii::$app->params['like'], 'EX.numeroGdExpediente', $key],
                [Yii::$app->params['like'], 'RD.asuntoRadiRadicado', $key],
                [Yii::$app->params['like'], 'TD.nombreTipoDocumental', $key]
            ]);
        }
        $modelDocPrincipal = $queryDocPrincipal->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelDocPrincipal = array_reverse($modelDocPrincipal);

        # Permite obtener el nombre del documento
        $regex = '/[A-Za-z0-9\-\_]+\.[a-zA-Z]{2,4}/';

        foreach ($modelDocPrincipal as $dataDocPrincipal) {

            if ($dataDocPrincipal['numeroGdExpediente'] == null) {
                $expediente = 'Sin expediente';
            } else {
                $expediente = $dataDocPrincipal['numeroGdExpediente'];
            }

            # Comparación de ruta del archivo con el regex y obtiene el nombre del archivo
            preg_match($regex, $dataDocPrincipal['rutaRadiDocumentoPrincipal'], $matches);
            if (count($matches)) {
                $nombreArchivo = reset($matches); //devuelve el primer valor
            }

            if (isset($dataDocPrincipal['rutaRadiDocumentoPrincipal'])) {
                if (file_exists($dataDocPrincipal['rutaRadiDocumentoPrincipal'])) {
                    $data = base64_encode(file_get_contents($dataDocPrincipal['rutaRadiDocumentoPrincipal']));
                } else {
                    $data = false;
                }
            }


            $documentos[] = [
                'idRadicado' => $dataDocPrincipal['idRadiRadicado'],
                'numeroRadicado' => $dataDocPrincipal['numeroRadiRadicado'],
                'numeroExpediente' => $expediente,
                'tipoDocumental' => $dataDocPrincipal['nombreTipoDocumental'],
                'asunto' => $dataDocPrincipal['asuntoRadiRadicado'],
                'extension' => $dataDocPrincipal['extensionRadiDocumentoPrincipal'],
                'url' => $dataDocPrincipal['rutaRadiDocumentoPrincipal'],
                'data' => $data,
                'nombreArchivo' => $nombreArchivo,
            ];
        }
        
        return $this->render('index', [
            'model' => $documentos
        ]);

    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        $request = Yii::$app->request->post();

        if (isset($request) && !empty($request)) {

            # Validar usuario externo
            $user = User::find()->where(['username' => $request['LoginForm']['username']])->one();

            if (isset($user)) {
                if ($user['idUserTipo'] != Yii::$app->params['tipoUsuario']['Externo']) {
                    Yii::$app->session->setFlash('error', 'El usuario no existe');
                    return $this->render('login', [
                        'model' => $model,
                    ]);
                }

                $request['LoginForm']['password'] = $request['LoginForm']['username'];
            }
        }

        if ($model->load($request) && $model->login()) {
            return $this->redirect(['ingreso-automatico', 'opcion' => '2']);
            //return $this->goBack();
        } else {
            $model->password = '';

            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLoginautomatico()
    {

        $login_auto = [
            "LoginForm" => [
                "username" => Yii::$app->params['userPublicPage'],
                "password" => Yii::$app->params['passwordPublicPage'],
                "rememberMe" => 1
            ],
            "login-button" => null
        ];

        $model_login = new LoginForm();
        $model_login->scenario = 'loginAutomatico';

        if ($model_login->load($login_auto) && $model_login->login()) {
            //Yii::$app->session->setFlash('success', 'Ahora Puedes Registrar y Consultar PQR.');
            // return $this->goBack();

            return $this->redirect(['ingreso-automatico', 'opcion' => '1']);
        }

        return $this->goHome();
    }

    public function actionLoginAutomaticoConsulta()
    {

        $login_auto = [
            "LoginForm" => [
                "username" => Yii::$app->params['userPublicPage'],
                "password" => Yii::$app->params['passwordPublicPage'],
                "rememberMe" => 1
            ],
            "login-button" => null
        ];

        $model_login = new LoginForm();
        $model_login->scenario = 'loginAutomatico';

        if ($model_login->load($login_auto) && $model_login->login()) {
            Yii::$app->session->setFlash('success', 'Ahora Puedes Registrar y Consultar PQR.');
            // return $this->goBack();

            return $this->redirect(['ingreso-automatico', 'opcion' => '2']);
        }

        return $this->goHome();
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return mixed
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail(Yii::$app->params['adminEmail'])) {
                Yii::$app->session->setFlash('success', 'Thank you for contacting us. We will respond to you as soon as possible.');
            } else {
                Yii::$app->session->setFlash('error', 'There was an error sending your message.');
            }

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Displays about page.
     *
     * @return mixed
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $request = Yii::$app->request->post();

        if (isset($request)) {

            # model errores 
            $model_errores = [];
            $modelCgGeneral = [];

            // Consulta la configuración general del sistema para tomar los terminos y condiciones configurados para que se muestren el frontend
            $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);

            

            if (isset($request['actualizar']) && $request['actualizar'] == "true") {               

                $this->actionUpdateCliente($request);
            } else {

                foreach ($request as $key => $value) {

                    $model_user = new User;
                    $model_userPqrs = new UserPqrs;
                    $model_user->attributes = $request['UserPqrs'];
                    $model_userPqrs->attributes = $request['UserPqrs'];

                    $transaction = Yii::$app->db->beginTransaction();

                    # Datos para la tabla clientes
                    $model_clientes = new Clientes;
                    $model_clientes->attributes = $request['UserPqrs'];
                    // Si es persona juridica solo se guarda el nombre y en el detalle se guarda el representante
                    if ($model_clientes->idTipoPersona == Yii::$app->params['tipoPersonaText']['personaJuridica']) {
                        $model_clientes->nombreCliente = $model_clientes->nombreCliente;
                    } else {
                        $model_clientes->nombreCliente = $request['UserPqrs']['primerNombre'] .' '. $request['UserPqrs']['segundoNombre'] .' '. $request['UserPqrs']['primerApellido'] .' '. $request['UserPqrs']['segundoApellido'];
                    }
                    $model_clientes->correoElectronicoCliente = $request['UserPqrs']['email'];
                    $model_clientes->direccionCliente = $request['UserPqrs']['dirCam1'] . ' ' . $request['UserPqrs']['dirCam2'] . ' ' . $request['UserPqrs']['dirCam3'] . ' ' . $request['UserPqrs']['dirCam4'] . ' ' . $request['UserPqrs']['dirCam5'] . ' ' . $request['UserPqrs']['dirCam6'];

                    if (!$model_clientes->save()) {
                        $transaction->rollBack();
                        $model_errores[] = $model_clientes->getErrors();
                        break;
                    }

                    # Datos para la tabla user
                    $model_user->username = (string) $model_clientes->numeroDocumentoCliente;
                    $request['UserPqrs']['password'] = (string) $model_clientes->numeroDocumentoCliente;

                    $roles = Roles::find()->where([Yii::$app->params['like'], 'nombreRol', Yii::$app->params['RolUserPqrs']])->one();

                    if (!isset($roles)) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'No hemos encontrado un rol válido para tu usuario');
                        break;
                    }

                    # Configuración general del id dependencia pqrs
                    $configGeneral = ConfiguracionGeneralController::generalConfiguration();

                    # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                    if ($configGeneral['status']) {
                        $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];
                    } else {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', $configGeneral['message']);
                        break;
                    }

                    $model_user->idRol = $roles['idRol'];
                    $model_user->idGdTrdDependencia = $idDependenciaUserPqrs;
                    $model_user->idUserTipo = Yii::$app->params['tipoUsuario']['Externo'];
                    $model_user->setPassword($request['UserPqrs']['password']);
                    $model_user->generateAuthKey();
                    $model_user->accessToken = $model_user->generateAccessToken();
                    $model_user->intentos = Yii::$app->params['maxIntentosLogueo'];
                    $model_user->fechaVenceToken = date("Y-m-d", strtotime(date("Y-m-d") . "+ " . Yii::$app->params['TimeExpireToken'] . " days"));
                    $model_user->ldap = false;
                    $model_user->status = Yii::$app->params['statusTodoText']['Activo'];

                    if (!$model_user->save()) {
                        $transaction->rollBack();
                        $model_errores[] = $model_user->getErrors();
                        break;
                    }

                    # Datos para la tabla clientesCiudadanosDetalles
                    $model_clientes_detalles = new ClientesCiudadanosDetalles;
                    $model_clientes_detalles->idCliente = $model_clientes->idCliente;
                    $model_clientes_detalles->idUser = $model_user->id;
                    $model_clientes_detalles->attributes = $request['UserPqrs'];
                    $model_clientes_detalles->barrioClientesCiudadanoDetalle = $request['UserPqrs']['barrioClientesCiudadanoDetalle'];
                    $model_clientes_detalles->telefonoFijoClienteCiudadanoDetalle = $request['ClientesCiudadanosDetalles']['telefonoFijoClienteCiudadanoDetalle'];

                    # Datos de PQRS caracterización de usuarios
                    $model_clientes_detalles->generoClienteCiudadanoDetalle	 = $request['UserPqrs']['genero'];
                    $model_clientes_detalles->rangoEdadClienteCiudadanoDetalle	 = $request['UserPqrs']['rangoEdad'];
                    $model_clientes_detalles->vulnerabilidadClienteCiudadanoDetalle	 = $request['UserPqrs']['vulnerabilidad'];
                    $model_clientes_detalles->etniaClienteCiudadanoDetalle	 = $request['UserPqrs']['etnia'];
                    $model_clientes_detalles->actEcomicaClienteCiudadanoDetalle = $request['UserPqrs']['actividadEconomica'];
                    $model_clientes_detalles->condDiscapacidadClienteCiudadanoDetalle = $request['UserPqrs']['condicionDiscapacidad'];
                    $model_clientes_detalles->estratoClienteCiudadanoDetalle = $request['UserPqrs']['estrato'];
                    $model_clientes_detalles->grupoInteresClienteCiudadanoDetalle = $request['UserPqrs']['grupoInteres'];
                    $model_clientes_detalles->grupoSisbenClienteCiudadanoDetalle = $request['UserPqrs']['grupoSisben'];
                    $model_clientes_detalles->escolaridadClienteCiudadanoDetalle = $request['UserPqrs']['escolaridad'];

                    // Se guarda el apellido o el representante legal, pero solo se utiliza para mostrar info en PQRS
                    $model_clientes_detalles->representanteCliente = $model_userPqrs->apellidoCliente;

                    if (!$model_clientes_detalles->save()) {
                        $transaction->rollBack();
                        $model_errores[] = $model_clientes->getErrors();
                        break;
                    }

                    # Mensaje cuando se ha creado un nuevo ciudadano
                    if ($model_user->save() && $model_clientes_detalles->save() && $model_clientes->save()) {
                        Yii::$app->session->setFlash('success', 'Gracias por registrarse en nuestra plataforma, ahora podrá registrar su PQRSD (Petición, Queja, Reclamo, Sugerencia, Denuncia).');
                    }

                    /***   Log de Auditoria  ***/
                    $user_log = User::find()->where(["id" => Yii::$app->params['idUserTramitadorPqrs']])->one();
                    $user_detalles_log = UserDetalles::find()->where(["idUser" => Yii::$app->params['idUserTramitadorPqrs']])->one();

                    if (!isset($user_log)) {
                        $transaction->rollBack();
                        Yii::$app->session->setFlash('error', 'El usuario tramitador no existe');
                        break;
                    }

                    $observacion = " " . $model_clientes->nombreCliente . "," . " con el documento de identidad " . $model_clientes->numeroDocumentoCliente;
                    HelperLog::logAdd(
                        false,
                        $user_log->id, //Id user
                        "$user_detalles_log->nombreUserDetalles . $user_detalles_log->apellidoUserDetalles",
                        Yii::$app->controller->route, //Modulo
                        $observacion . ", aceptó los términos del tratamiento de datos personales.", //texto para almacenar en el evento
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );

                    $model_log = " user: " . $model_user->username . " | email: " . $model_user->email . " | nombre: " . $model_clientes->nombreCliente . " | rol: " . $model_user->rol['nombreRol'];
                    HelperLog::logAdd(
                        true,
                        $user_log->id, //Id user
                        "$user_detalles_log->nombreUserDetalles . $user_detalles_log->apellidoUserDetalles",
                        Yii::$app->controller->route, //Modulo
                        str_replace("{observacion}", $observacion, Yii::$app->params['eventosLogText']['SignupPqrs']), //texto para almacenar en el evento
                        '',
                        $model_log, //Data
                        array() //No validar estos campos
                    );

                    $transaction->commit();

                    $headMailText = Yii::t('app', 'headMailTextRegistro');
                    $textBody = Yii::t('app', 'textBodyResgitroExt', [
                        'user' => $model_user->username
                    ]);

                    //$envioCorreo = CorreoController::registroPqrs($model_user->email, $headMailText, $textBody);

                    $login_auto = [
                        "LoginForm" => [
                            "username" => $model_user->username,
                            "password" => $request['UserPqrs']['password'],
                            "rememberMe" => 1
                        ],
                        "login-button" => null
                    ];

                    $model_login = new LoginForm();
                    $model_login->scenario = 'loginAutomatico';

                    if ($model_login->load($login_auto) && $model_login->login()) {
                        return $this->redirect(['ingreso-automatico', 'opcion' => '1']);
                    }
                    // return $this->goHome();
                }

                if (count($model_errores) >= 1) {

                    foreach ($model_errores as $model) {
                        foreach ($model as $error) {
                            foreach ($error as $message) {
                                Yii::$app->session->setFlash('error', $message);
                            }
                        }
                    }
                }
            }
        }

        $list_tipos_persona = ArrayHelper::map(TiposPersonas::find()->where(['<>', 'idTipoPersona', Yii::$app->params['tipoPersonaText']['funcionario']])->all(), 'idTipoPersona', 'tipoPersona');
        $list_tipos_identificacion = ArrayHelper::map(TiposIdentificacion::find()->all(), 'idTipoIdentificacion', 'nombreTipoIdentificacion');
        $list_genero = ArrayHelper::map(CgGeneroPqrs::find()->all(), 'idCgGeneroPqrs', 'nombreCgGeneroPqrs');
        $list_rango_edad = ArrayHelper::map(CgRangoEdadPqrs::find()->all(), 'idCgRangoEdadPqrs', 'nombreCgRangoEdadPqrs');
        $list_vulnerabilidad = ArrayHelper::map(CgVulnerabilidadPqrs::find()->all(), 'idCgVulnerabilidadPqrs', 'nombreCgVulnerabilidadPqrs');
        $list_etnia = ArrayHelper::map(CgGrupoEtnicoPqrs::find()->all(), 'idCgGrupoEtnicoPqrs', 'nombreCgGrupoEtnicoPqrs');
        $list_actividad_economica = ArrayHelper::map(cgActividadEconomicaPqrs::find()->all(), 'idCgActividadEconomicaPqrs', 'nombreCgActividadEconomicaPqrs');
        $list_condicion_discapacidad = ArrayHelper::map(CgCondicionDiscapacidadPqrs::find()->all(), 'idCgCondicionDiscapacidadPqrs', 'nombreCgCondicionDiscapacidadPqrs');
        $list_estrato = ArrayHelper::map(CgEstratoPqrs::find()->all(), 'idCgEstratoPqrs', 'nombreCgEstratoPqrs');
        $list_grupo_interes = ArrayHelper::map(CgGrupoInteresPqrs::find()->all(), 'idCgGrupoInteresPqrs', 'nombreCgGrupoInteresPqrs');
        $list_grupo_sisben = ArrayHelper::map(CgGrupoSisbenPqrs::find()->where(['estadoCgGrupoSisbenPqrs' => Yii::$app->params['statusTodoText']['Activo']])->all(), 'idCgGrupoSisbenPqrs', 'nombreCgGrupoSisbenPqrs');
        $list_escolaridad = ArrayHelper::map(CgEscolaridadPqrs::find()->all(), 'idCgEscolaridadPqrs', 'nombreCgEscolaridadPqrs');
        $list_clasificacion = ArrayHelper::map(CgClasificacionPqrs::find()->all(), 'idCgClasificacionPqrs', 'nombreCgClasificacionPqrs');

        $nivelGeografico1 = NivelGeografico1::find();
        $nivelGeografico1 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico1, 'nivelGeografico2', ['nivelGeografico2' => 'idNivelGeografico1', 'nivelGeografico1' => 'nivelGeografico1']);
        $nivelGeografico1 = $nivelGeografico1->orderBy(['nomNivelGeografico1' => SORT_ASC])->all();

        $list_paises = ArrayHelper::map($nivelGeografico1, 'nivelGeografico1', 'nomNivelGeografico1');
        $list_departamentos = [];
        $list_departamentos =  ArrayHelper::map(NivelGeografico2::find()->where(['nivelGeografico2.idNivelGeografico1' => 1])->all(), 'nivelGeografico2', 'nomNivelGeografico2');
        // $nivelGeografico3 = NivelGeografico3::find();
        // $nivelGeografico3 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico3, 'nivelGeografico2', ['nivelGeografico2' => 'nivelGeografico2', 'nivelGeografico3' => 'idNivelGeografico2']);
        // $nivelGeografico3 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico3, 'nivelGeografico1', ['nivelGeografico1' => 'nivelGeografico1', 'nivelGeografico2' => 'idNivelGeografico1']);
        // $nivelGeografico3 = $nivelGeografico3
        //     ->all();
        // $list_ciudades = ArrayHelper::map($nivelGeografico3, 'nivelGeografico3', 'nomNivelGeografico3');
        $list_ciudades = [];
        

        return $this->render('signup', [
            'list_tipos_persona'          => $list_tipos_persona ?? [],
            'list_tipos_identificacion'   => $list_tipos_identificacion ?? [],
            'list_genero'                 => $list_genero ?? [],
            'list_rango_edad'             => $list_rango_edad ?? [],
            'list_vulnerabilidad'         => $list_vulnerabilidad ?? [],
            'list_etnia'                  => $list_etnia ?? [],
            'list_actividad_economica'    => $list_actividad_economica ?? [],
            'list_condicion_discapacidad' => $list_condicion_discapacidad ?? [],
            'list_estrato'                => $list_estrato ?? [],
            'list_clasificacion'          => $list_clasificacion ?? [],
            'list_grupo_interes'          => $list_grupo_interes ?? [],
            'list_grupo_sisben'           => $list_grupo_sisben ?? [],
            'list_escolaridad'            => $list_escolaridad ?? [],
            'list_paises'                 => $list_paises ?? [],
            'list_departamentos'          => $list_departamentos ?? [],
            'list_ciudades'               => $list_ciudades ?? [],

            'model_form_signup'         => $model_userPqrs ?? new UserPqrs,
            'model_clientes'            => new Clientes,
            'model_paises'              => new NivelGeografico1,
            'model_clientes_detalles'   => new ClientesCiudadanosDetalles,
            'model_identificacion'      => new TiposIdentificacion,
            'model_users'               => $model_user ?? new User,
            'model_users_detalles'      => new UserDetalles,
            'model_errores'             => $model_errores ?? [],
            'terminos_condiciones'      => $modelCgGeneral->terminoCondicionCgGeneral
        ]);
    }

    public function actionUpdateCliente($request)
    {

        $model_errores = [];
        // $transaction = Yii::$app->db->beginTransaction();

        $model_clientes = Clientes::findOne(['idCliente' => $request['UserPqrs']['idcliente']]);

        if ($model_clientes) {

            $model_clientes->attributes = $request['UserPqrs'];
            // Si es persona juridica solo se guarda el nombre y en el detalle se guarda el representante
            if ($model_clientes->idTipoPersona == Yii::$app->params['tipoPersonaText']['personaJuridica']) {
                $model_clientes->nombreCliente = $request['UserPqrs']['nombreCliente'];
            } else {
                $model_clientes->nombreCliente = $request['UserPqrs']['nombreCliente'] . ' ' . $request['UserPqrs']['apellidoCliente'];
            }
            $model_clientes->correoElectronicoCliente = $request['UserPqrs']['email'];
            $model_clientes->direccionCliente = $request['UserPqrs']['dirCam1'] . ' ' . $request['UserPqrs']['dirCam2'] . ' ' . $request['UserPqrs']['dirCam3'] . ' ' . $request['UserPqrs']['dirCam4'] . ' ' . $request['UserPqrs']['dirCam5'] . ' ' . $request['UserPqrs']['dirCam6'];

            if (!$model_clientes->save()) {
                // $transaction->rollBack();
                $model_errores[] = $model_clientes->getErrors();
            }

            $model_clientes_detalles = ClientesCiudadanosDetalles::findOne(['idCliente' => $model_clientes->idCliente]);
            $model_clientes_detalles->attributes = $request['UserPqrs'];
            $model_clientes_detalles->barrioClientesCiudadanoDetalle = $request['UserPqrs']['barrioClientesCiudadanoDetalle'];
            $model_clientes_detalles->telefonoFijoClienteCiudadanoDetalle = $request['ClientesCiudadanosDetalles']['telefonoFijoClienteCiudadanoDetalle'];

            # Datos de PQRS caracterización de usuarios
            $model_clientes_detalles->generoClienteCiudadanoDetalle	 = $request['UserPqrs']['genero'];
            $model_clientes_detalles->rangoEdadClienteCiudadanoDetalle	 = $request['UserPqrs']['rangoEdad'];
            $model_clientes_detalles->vulnerabilidadClienteCiudadanoDetalle	 = $request['UserPqrs']['vulnerabilidad'];
            $model_clientes_detalles->etniaClienteCiudadanoDetalle	 = $request['UserPqrs']['etnia'];
            $model_clientes_detalles->actEcomicaClienteCiudadanoDetalle = $request['UserPqrs']['actividadEconomica'];
            $model_clientes_detalles->condDiscapacidadClienteCiudadanoDetalle = $request['UserPqrs']['condicionDiscapacidad'];
            $model_clientes_detalles->estratoClienteCiudadanoDetalle = $request['UserPqrs']['estrato'];
            $model_clientes_detalles->grupoInteresClienteCiudadanoDetalle = $request['UserPqrs']['grupoInteres'];
            $model_clientes_detalles->grupoSisbenClienteCiudadanoDetalle = $request['UserPqrs']['grupoSisben'];
            $model_clientes_detalles->escolaridadClienteCiudadanoDetalle = $request['UserPqrs']['escolaridad'];

            // Se guarda el apellido o el representante legal, pero solo se utiliza para mostrar info en PQRS
            $model_clientes_detalles->representanteCliente = $request['UserPqrs']['apellidoCliente'];

            if (!$model_clientes_detalles->save()) {
                // $transaction->rollBack();
                $model_errores[] = $model_clientes_detalles->getErrors();
            }

            $model_user = User::findOne(['id' => $model_clientes_detalles->idUser]);
            $model_user->email = $request['UserPqrs']['email'];

            if (!$model_user->save()) {
                // $transaction->rollBack();
                $model_errores[] = $model_user->getErrors();
            }

            /***   Log de Auditoria  ***/
            $user_log = User::find()->where(["id" => Yii::$app->params['idUserTramitadorPqrs']])->one();
            $user_detalles_log = UserDetalles::find()->where(["idUser" => Yii::$app->params['idUserTramitadorPqrs']])->one();

            if (!isset($user_log)) {
                // $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'El usuario tramitador no existe');
            }

            $observacion = " " . $model_clientes->nombreCliente . "," . " con el documento de identidad " . $model_clientes->numeroDocumentoCliente;
            HelperLog::logAdd(
                false,
                $user_log->id, //Id user
                "$user_detalles_log->nombreUserDetalles . $user_detalles_log->apellidoUserDetalles",
                Yii::$app->controller->route, //Modulo
                $observacion . ", aceptó los términos del tratamiento de datos personales.", //texto para almacenar en el evento
                [],
                [], //Data
                array() //No validar estos campos
            );

            $model_log = " user: " . $model_user->username . " | email: " . $model_user->email . " | nombre: " . $model_clientes->nombreCliente . " | rol: " . $model_user->rol['nombreRol'];
            HelperLog::logAdd(
                true,
                $user_log->id, //Id user
                "$user_detalles_log->nombreUserDetalles . $user_detalles_log->apellidoUserDetalles",
                Yii::$app->controller->route, //Modulo
                str_replace("{observacion}", $observacion, Yii::$app->params['eventosLogText']['SignupPqrsUpdate']), //texto para almacenar en el evento
                '',
                $model_log, //Data
                array() //No validar estos campos
            );

            // $transaction->commit();

            if (count($model_errores) >= 1) {

                foreach ($model_errores as $model) {
                    foreach ($model as $error) {
                        foreach ($error as $message) {
                            Yii::$app->session->setFlash('error', $message);
                        }
                    }
                }
            }

            # Datos para la tabla user
            $request['UserPqrs']['password'] = (string) $model_clientes->numeroDocumentoCliente;

            $login_auto = [
                "LoginForm" => [
                    "username" => $request['UserPqrs']['username'],
                    "password" => $request['UserPqrs']['password'],
                    "rememberMe" => 1
                ],
                "login-button" => null
            ];

            $model_login = new LoginForm();
            $model_login->scenario = 'loginAutomatico';

            if ($model_login->load($login_auto) && $model_login->login()) {
                return $this->redirect(['ingreso-automatico', 'opcion' => '1']);
            }
        }
    }

    /**
     * Requests password reset.
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $request = Yii::$app->request->post();
        $model = new PasswordResetRequestForm();

        if (isset($request) && !empty($request)) {

            $email = $request['PasswordResetRequestForm']['email'];
            $username = $request['PasswordResetRequestForm']['username'];
            $user = User::find()
                ->select(['status', 'ldap'])
                ->where(['email' => $email])
                ->andWhere(['username' => $username])
                ->one();

            if (isset($user)) {

                //Se valida el status del usuario
                if ($user['status'] != User::STATUS_ACTIVE) {
                    Yii::$app->session->setFlash('error', Yii::t('app', 'usuarioInactivo'));
                }

                //Se valida el ldap del usuario en el directorio activo
                if ($user['ldap'] == User::STATUS_ACTIVE) {
                    Yii::$app->session->setFlash('error', Yii::t('app', 'usuarioLdap'));
                }


                if ($model->load(Yii::$app->request->post()) && $model->validate()) {

                    $envioCorreo = CorreoController::resetPassword($model->email, $username, true);
                    if ($envioCorreo['status'] == true) {

                        /***  log Auditoria ***/
                        if (!Yii::$app->user->isGuest) {
                            HelperLog::logAdd(
                                false,
                                Yii::$app->params['idUserTramitadorPqrs'],
                                Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['NuevaContrasena'] . " " . $user->username, //texto para almacenar en el evento
                                [],
                                [], //Data
                                array() //No validar estos campos
                            );
                        }
                        /***   Fin log Auditoria   ***/
                        Yii::$app->session->setFlash('success', Yii::t('app', 'RequestPasswordReset'));
                        return $this->goHome();
                    }
                }
            }

            Yii::$app->session->setFlash('error', Yii::t('app', 'RequestPasswordResetFail'));
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException(Yii::t('app', 'errorTokenIncorrecto'));
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', Yii::t('app', 'successPassword'));
            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Verify email address
     * @param string $token
     * @throws BadRequestHttpException
     * @return yii\web\Response
     */
    public function actionVerifyEmail($token)
    {
        try {
            $model = new VerifyEmailForm($token);
        } catch (InvalidArgumentException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }
        if ($user = $model->verifyEmail()) {
            if (Yii::$app->user->login($user)) {
                Yii::$app->session->setFlash('success', 'Your email has been confirmed!');
                return $this->goHome();
            }
        }

        Yii::$app->session->setFlash('error', 'Sorry, we are unable to verify your account with provided token.');
        return $this->goHome();
    }

    /**
     * Resend verification email
     * @return mixed
     */
    public function actionResendVerificationEmail()
    {
        $model = new ResendVerificationEmailForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');
                return $this->goHome();
            }
            Yii::$app->session->setFlash('error', 'Sorry, we are unable to resend verification email for the provided email address.');
        }

        return $this->render('resendVerificationEmail', [
            'model' => $model
        ]);
    }

    public function actionNivelGeografico2($id)
    {

        $Departamentos = NivelGeografico2::find()->select('*')->where(['idNivelGeografico1' => $id])->orderBy(['nomNivelGeografico2' => SORT_ASC])->all();

        if ($Departamentos == []) {
            echo "<option  selected value = ''> Otro </option>";
        } else {
            echo "<option  selected> Selecciona un Departamento...</option>";
            foreach ($Departamentos as $model) {
                echo "<option value='" . $model['nivelGeografico2'] . "'>" . $model['nomNivelGeografico2'] . "</option>";
            }
        }
    }

    public function actionNivelGeografico3()
    {
        $request = Yii::$app->request->post();
        if (isset($request) && !empty($request)) {

            Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
            $out = [];
            $id = $request['depdrop_all_params']['Dep_id'];

            if ($id != 1) {
                $Municipio = NivelGeografico3::find()->select('*')->where(['idNivelGeografico2' => $id])->orderBy(['nomNivelGeografico3' => SORT_ASC])->all();
            } else {
                $Municipio = NivelGeografico3::find()->select('*')->orderBy(['nomNivelGeografico3' => SORT_ASC])->all();
            }

            foreach ($Municipio as $model) {
                $out[] = ['id' => $model['nivelGeografico3'], 'name' => $model['nomNivelGeografico3']];
            }

            return ['output' => $out, 'selected' => ''];
        }
    }

    public function actionVerificarEmail()
    {

        $datos = Yii::$app->request->post();
        $user = User::find()
            ->where(['email' => $datos['email']])
            ->andWhere(['<>', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo']])
            ->one();

        if (isset($user)) {
            return 2; // Se modifica el true por el 2, ya que no realizaba correctamente la validación.
        } else {

            $user = User::find()
                ->where(['email' => $datos['email']])
                ->andWhere(['=', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo']])
                ->one();

            if ($user != null) {
                return 3;
            } else {
                return false;
            }
        }
    }

    public function actionVerificarUsuario()
    {

        $datos = Yii::$app->request->post();
        $user = User::findOne(['username' => $datos['numeroDocumentoCliente']]);

        if (isset($user)) {
            return 2;  // Se modifica el true por el 2, ya que no realizaba correctamente la validación.
        } else {
            return false;
        }
    }

    public function actionEncuesta()
    {

        $requestGet = Yii::$app->request->get();
        $requestPost = Yii::$app->request->post();

        if (isset($requestPost)  && count($requestPost) > 0) {

            $transaction = Yii::$app->db->beginTransaction();

            $idClienteEncuesta = $requestPost['idClienteEncuesta'];
            $idCgEncuesta = $requestPost['idCgEncuesta'];

            $modelClienteEncuesta = ClienteEncuestas::findOne(['idClienteEncuesta' => $idClienteEncuesta]);
            $modelClienteEncuesta->idCgEncuesta = $idCgEncuesta;
            $modelClienteEncuesta->fechaClienteEncuesta = date("Y-m-d H:i:s");
            $modelClienteEncuesta->estadoClienteEncuesta = Yii::$app->params['statusTodoText']['Finalizado'];
            if (!$modelClienteEncuesta->save()) {
                Yii::$app->session->setFlash('error', 'Error al enviar encuesta');
                return $this->goHome();
            }

            $modelPreguntas = CgEncuestaPreguntas::find()
                ->where(['idCgEncuesta' => $idCgEncuesta])->all();


            foreach ($modelPreguntas as $pregunta) {

                $preguntaCalificacion = str_replace(' ', '_', $pregunta->preguntaCgEncuestaPregunta);

                if (isset($requestPost[$preguntaCalificacion])) {

                    $calificaciones = new EncuestaCalificaciones();

                    $calificaciones->idCgEncuestaPregunta = $pregunta->idCgEncuestaPregunta;
                    $calificaciones->calificacionEncuestaPregunta = $requestPost[$preguntaCalificacion];
                    $calificaciones->idClienteEncuesta = $idClienteEncuesta;
                    if (!$calificaciones->save()) {
                        Yii::$app->session->setFlash('error', 'Error al enviar encuesta');
                        return $this->goHome();
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Error al enviar encuesta');
                    return $this->goHome();
                }
            }

            $transaction->commit();

            Yii::$app->session->setFlash('success', 'Muchas gracias por completar la encuesta');

            return $this->goHome();
        } else {

            $modelClientesEncuesta = ClienteEncuestas::find()->where(['tokenClienteEncuesta' => $requestGet['token']])->one();

            if (!empty($modelClientesEncuesta)) {
                if ($modelClientesEncuesta->estadoClienteEncuesta == Yii::$app->params['statusTodoText']['Activo']) {
                    $modelEncuesta = CgEncuestas::find()
                        ->where(['estadoCgEncuesta' => Yii::$app->params['statusTodoText']['Activo']])
                        ->one();

                    # Se valida que haya una encuesta activa
                    if (!is_null($modelEncuesta)) {

                        $modelPreguntas = CgEncuestaPreguntas::find()
                            ->where(['idCgEncuesta' => $modelEncuesta->idCgEncuesta])
                            ->all();

                        $preguntas = [];
                        foreach ($modelPreguntas as $pregunta) {
                            $preguntas[] = $pregunta->preguntaCgEncuestaPregunta;
                        }

                        return $this->render('encuesta', [
                            'idCgEncuesta' => $modelEncuesta->idCgEncuesta,
                            'idClienteEncuesta' => $modelClientesEncuesta->idClienteEncuesta,
                            'preguntas' => $preguntas
                        ]);
                    }
                } else if ($modelClientesEncuesta->estadoClienteEncuesta == Yii::$app->params['statusTodoText']['Finalizado']) {
                    Yii::$app->session->setFlash('success', 'Usted previamente completó la encuesta');
                    return $this->goHome();
                } else if ($modelClientesEncuesta->estadoClienteEncuesta == Yii::$app->params['statusTodoText']['Inactivo']) {
                    Yii::$app->session->setFlash('error', 'Su participación en la encuesta ha sido desactivada');
                    return $this->goHome();
                }
            }
        }
    }

    public function actionInfoCliente()
    {

        $datos = Yii::$app->request->post();
        $cliente = array();

        if (isset($datos)) {

            $model_clientes = Clientes::find()->where(['numeroDocumentoCliente' => $datos['documentodatos']])->one();

            if (isset($model_clientes)) {

                $model_clientes_detalles = $model_clientes->clientesCiudadanosDetalles;

                if ($model_clientes_detalles) {
                    $modelTipoIdentificacion = TiposIdentificacion::findOne(['idTipoIdentificacion' => $model_clientes_detalles->idTipoIdentificacion]);
                    $usuario = User::findOne(['id' => $model_clientes_detalles->idUser]);

                    if($model_clientes_detalles->generoClienteCiudadanoDetalle != 0) {
                        $nombreCgGeneroPqrs = Yii::$app->db->createCommand("SELECT nombreCgGeneroPqrs from cgGeneroPqrs where idCgGeneroPqrs=".$model_clientes_detalles->generoClienteCiudadanoDetalle)->queryScalar();
                    }else{ $nombreCgGeneroPqrs = ''; }
                    
                    if($model_clientes_detalles->rangoEdadClienteCiudadanoDetalle != 0){
                        $nombreCgRangoEdadPqrs = Yii::$app->db->createCommand("SELECT nombreCgRangoEdadPqrs from cgRangoEdadPqrs where idCgRangoEdadPqrs=".$model_clientes_detalles->rangoEdadClienteCiudadanoDetalle)->queryScalar();
                    }else{ $nombreCgRangoEdadPqrs = ''; }

                    if($model_clientes_detalles->vulnerabilidadClienteCiudadanoDetalle != 0){
                        $nombreCgVulnerabilidadPqrs = Yii::$app->db->createCommand("SELECT nombreCgVulnerabilidadPqrs from cgVulnerabilidadPqrs where idCgVulnerabilidadPqrs=".$model_clientes_detalles->vulnerabilidadClienteCiudadanoDetalle)->queryScalar(); 
                    }else{ $nombreCgVulnerabilidadPqrs = ''; }

                    if($model_clientes_detalles->etniaClienteCiudadanoDetalle != 0){
                        $nombreCgGrupoEtnicoPqrs = Yii::$app->db->createCommand("SELECT nombreCgGrupoEtnicoPqrs from cgGrupoEtnicoPqrs where idCgGrupoEtnicoPqrs=".$model_clientes_detalles->etniaClienteCiudadanoDetalle)->queryScalar(); 
                    }else{ $nombreCgGrupoEtnicoPqrs = ''; }

                    if($model_clientes_detalles->actEcomicaClienteCiudadanoDetalle != 0){
                        $nombreCgActividadEconomicaPqrs = Yii::$app->db->createCommand("SELECT nombreCgActividadEconomicaPqrs from cgActividadEconomicaPqrs where idCgActividadEconomicaPqrs=".$model_clientes_detalles->actEcomicaClienteCiudadanoDetalle)->queryScalar(); 
                    }else{ $nombreCgActividadEconomicaPqrs = ''; }
                    
                    if($model_clientes_detalles->condDiscapacidadClienteCiudadanoDetalle != 0){
                        $nombreCgCondicionDiscapacidadPqrs = Yii::$app->db->createCommand("SELECT nombreCgCondicionDiscapacidadPqrs from cgCondicionDiscapacidadPqrs where idCgCondicionDiscapacidadPqrs=".$model_clientes_detalles->condDiscapacidadClienteCiudadanoDetalle)->queryScalar(); 
                    }else{ $nombreCgCondicionDiscapacidadPqrs = ''; }

                    if($model_clientes_detalles->estratoClienteCiudadanoDetalle != 0){
                        $nombreCgEstratoPqrs = Yii::$app->db->createCommand("SELECT nombreCgEstratoPqrs from cgEstratoPqrs where idCgEstratoPqrs=".$model_clientes_detalles->estratoClienteCiudadanoDetalle)->queryScalar();
                    }else{ $nombreCgEstratoPqrs = ''; }

                    if($model_clientes_detalles->grupoInteresClienteCiudadanoDetalle != 0){
                        $nombreCgGrupoInteresPqrs = Yii::$app->db->createCommand("SELECT nombreCgGrupoInteresPqrs from cgGrupoInteresPqrs where idCgGrupoInteresPqrs=".$model_clientes_detalles->grupoInteresClienteCiudadanoDetalle)->queryScalar(); 
                    }else{ $nombreCgGrupoInteresPqrs = ''; }

                    if($model_clientes_detalles->grupoSisbenClienteCiudadanoDetalle != 0){
                        $nombreCgGrupoSisbenPqrs = Yii::$app->db->createCommand("SELECT nombreCgGrupoSisbenPqrs from cgGrupoSisbenPqrs where idCgGrupoSisbenPqrs=".$model_clientes_detalles->grupoSisbenClienteCiudadanoDetalle." and estadoCgGrupoSisbenPqrs =".Yii::$app->params['statusTodoText']['Activo'])->queryScalar();
                    }else{ $nombreCgGrupoSisbenPqrs = ''; }

                    if($model_clientes_detalles->escolaridadClienteCiudadanoDetalle != 0){
                        $nombreCgEscolaridadPqrs = Yii::$app->db->createCommand("SELECT nombreCgEscolaridadPqrs from cgEscolaridadPqrs where idCgEscolaridadPqrs=".$model_clientes_detalles->escolaridadClienteCiudadanoDetalle)->queryScalar();
                    }else{ $nombreCgEscolaridadPqrs = ''; }

                    $cliente = [
                        'datos' => [
                            'idTipoPersona' => $model_clientes->idTipoPersona,
                            'nombreTipoPersona' => Yii::$app->params['tipoPersonaNumber'][$model_clientes->idTipoPersona],
                            'nombreCliente' => $model_clientes->nombreCliente,
                            'correoElectronico' => $model_clientes->correoElectronicoCliente,
                            'direccion' => $model_clientes->direccionCliente,
                            'telefono' => $model_clientes->telefonoCliente,
                            'idNivelGeografico1' => $model_clientes->idNivelGeografico10->nivelGeografico1,
                            'nombreNivelGeografico1' => $model_clientes->idNivelGeografico10->nomNivelGeografico1,
                            'idNivelGeografico3' => $model_clientes->idNivelGeografico30->nivelGeografico3,
                            'nombreNivelGeografico3' => $model_clientes->idNivelGeografico30->nomNivelGeografico3,
                            'idNivelGeografico22' => $model_clientes->idNivelGeografico20->nivelGeografico2,
                            'nombreNivelGeografico2' => $model_clientes->idNivelGeografico20->nomNivelGeografico2,
                            'idTipoIdentificacion' => $model_clientes_detalles->idTipoIdentificacion,
                            'nombreTipoIdentificacion' => $modelTipoIdentificacion->nombreTipoIdentificacion,
                            'genero' => $model_clientes_detalles->generoClienteCiudadanoDetalle,
                            'nombreGenero' =>  $nombreCgGeneroPqrs ?? '',                                   
                            'rangoEdad' => $model_clientes_detalles->rangoEdadClienteCiudadanoDetalle,
                            'nombreRangoEdad' => $nombreCgRangoEdadPqrs ?? '',                            
                            'vulnerabilidad' => $model_clientes_detalles->vulnerabilidadClienteCiudadanoDetalle,
                            'nombreVulnerabilidad' => $nombreCgVulnerabilidadPqrs ?? '',                            
                            'etnia' => $model_clientes_detalles->etniaClienteCiudadanoDetalle,
                            'nombreEtnia' => $nombreCgGrupoEtnicoPqrs ?? '',                            
                            'actividadEconomica' => $model_clientes_detalles->actEcomicaClienteCiudadanoDetalle,
                            'nombreActividadEconomica' => $nombreCgActividadEconomicaPqrs ?? '',
                            'condicionDiscapacidad' => $model_clientes_detalles->condDiscapacidadClienteCiudadanoDetalle,
                            'nombreCondicionDiscapacidad' => $nombreCgCondicionDiscapacidadPqrs ?? '',
                            'estrato' => $model_clientes_detalles->estratoClienteCiudadanoDetalle,
                            'nombreEstrato' => $nombreCgEstratoPqrs ?? '',
                            'grupoInteres' => $model_clientes_detalles->grupoInteresClienteCiudadanoDetalle,
                            'nombreGrupoInteres' => $nombreCgGrupoInteresPqrs ?? '',
                            'grupoSisben' => $model_clientes_detalles->grupoSisbenClienteCiudadanoDetalle,
                            'nombreGrupoSisben' => $nombreCgGrupoSisbenPqrs ?? '',
                            'escolaridad' => $model_clientes_detalles->escolaridadClienteCiudadanoDetalle,
                            'nombreEscolaridad' => $nombreCgEscolaridadPqrs ?? '',
                            'barrio' => $model_clientes_detalles->barrioClientesCiudadanoDetalle,
                            'telefonoFijo' => $model_clientes_detalles->telefonoFijoClienteCiudadanoDetalle,
                            'username' => $usuario->username,
                            'idcliente' => $model_clientes->idCliente,
                            'represntanteLegal' => $model_clientes_detalles->representanteCliente,
                        ],
                        'status' => true
                    ];
                } else {
                    $cliente = ['data' => '', 'status' => false];
                }
            } else {
                $cliente = ['data' => '', 'status' => false];
            }
        }
        return json_encode($cliente);
    }

    public function actionIngresoAutomatico($opcion)
    {

        if ($opcion == '2') {
            return $this->redirect('../consulta-pqrs/index');
        } else {
            return $this->redirect('../registro-pqrs/index');
        }
    }

    public function actionPrevisualizacionPdf()
    {

        $request = Yii::$app->request->post();
        $errors = [];
        $messageAlert = '';   //Mensajes de alerta durante el proceso de creación
        $numeroDocumento = 0; // numero de anexos cargados

        # Configuración general del id dependencia pqrs
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();
        $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $configGeneral['data']['idDependenciaPqrsCgGeneral']]);

        # Se consulta la información del usuario tramitador
        $modelUser = User::findOne(['id' => Yii::$app->params['idUserTramitadorPqrs']]);
        # Se obtiene la información del cliente que es el remitente - Datos del cliente-externo
        $modelCliente = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);

        # datos de ubicacion del cliente
        $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $modelCliente->cliente->idNivelGeografico2]);
        $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $modelCliente->cliente->idNivelGeografico3]);

        if (isset($request) && !empty($request)) {

            # Cuando es un usuario diferente al anonimo se asigna lo que venga del formulario
            if (isset($request['autorizacionRadiRadicados'])) {
                $notificarVia = $request['autorizacionRadiRadicados'];
            } // Si el usuario no es anonimo siempre se debe notificar a un correo = 10 
            else {
                $notificarVia = Yii::$app->params['statusTodoText']['Activo'];
            }

            # Se valida que haya un tipo documental
            if (isset($request['idTrdTipoDocumental'])) {
                $idTrdTipoDocumental = $request['idTrdTipoDocumental'];
            } else { // Sino muestra un mensaje de alerta
                $messageAlert = Yii::$app->session->setFlash('error', 'No se ha seleccionado ningún tipo de documental');
            }

            # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
            if ($configGeneral['status']) {

                $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];
                $emailUserTramitadorPqrs = $configGeneral['data']['correoNotificadorPqrsCgGeneral'];

                /*Con el correo configurado en PQRS se obtiene el id del usuario responsable de la TRD*/
                $usuarioData = User::findOne(['email' => $emailUserTramitadorPqrs]);

                if (!is_null($usuarioData) && isset($usuarioData->userDetalles)) {
                    $idUserPqrs = $usuarioData->id;
                } else {
                    $messageAlert .= Yii::$app->session->setFlash('error', 'No hay ningún usuario con el correo de administrador PQRSD');
                }
            } else {
                $messageAlert .= Yii::$app->session->setFlash('error', $configGeneral['message']);
            }

            # Gestion PQRS Anonima 
            if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {

                if (isset($request['radicado_radi_arch1'])) {

                    $model_anonima = new RadiDetallePqrsAnonimo;

                    // Si la autorización de la notificación es 10 = a por correo
                    if ($notificarVia == Yii::$app->params['statusTodoText']['Activo']) {
                        $model_anonima->idNivelGeografico1 = Yii::$app->params['nivelGeografico']['nivelGeograficoPais'];
                        $model_anonima->idNivelGeografico3 = Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio'];
                        $model_anonima->idNivelGeografico2 = Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento'];

                        $model_anonima->direccionRadiDetallePqrsAnonimo = 'sin dirección';
                        $model_anonima->emailRadiDetallePqrsAnonimo = $request['correoElectronicoCliente'];
                        $medioInformativo = $model_anonima->emailRadiDetallePqrsAnonimo;
                    } else {
                        $model_anonima->idNivelGeografico1 = $request['idNivelGeografico1'];
                        $model_anonima->idNivelGeografico2 = $request['idNivelGeografico2'];
                        $model_anonima->idNivelGeografico3 = $request['idNivelGeografico3'];

                        $model_anonima->emailRadiDetallePqrsAnonimo = 'sin correo';
                        $model_anonima->direccionRadiDetallePqrsAnonimo = $request['direccion'] . ' ' . $request['radicado_radi_arch1'] . ' # ' . $request['radidetallepqrsanonimo_dircam4'] . ' ' . $request['radidetallepqrsanonimo_dircam5'] . ' ' . $request['radidetallepqrsanonimo_dircam6'] . ' ';

                        $medioInformativo = $model_anonima->direccionRadiDetallePqrsAnonimo;
                    }

                    $request['RadiRadicadoDetallePqrs']['autorizacionRadiDetallePqrs'] = Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica'];
                }
            } else {
                $medioInformativo = $modelCliente->cliente->correoElectronicoCliente;
            }

            # Gestion de Documentos de la solicitud PQRS
            $GdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $idDependenciaUserPqrs]);
            $anio = date("Y");
            $pdfFiles = [];

            $pathUploadFile = Yii::getAlias('@webroot')
                . "/" . Yii::$app->params['bodegaRadicados']
                . "/" . $anio
                . "/" . $GdTrdDependencias['codigoGdTrdDependencia']
                . "/tmp";

            $pathUploadFile = str_replace('frontend/', 'api/', $pathUploadFile);

            // Verificar que la carpeta exista y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {
                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $messageAlert .= Yii::$app->session->setFlash('error', 'El directorio no existe');
                }
            }

            $pdfFiles[]  = ['nombreRadiDocumento' => '',];
            $filename = '/tmp-1';

            $pdfData = [
                'numeroAnexos'       => $numeroDocumento ?? 0,
                'numeroRadiRadicado' => 'tmp',
                'dependencias'       => $GdTrdDependencias['codigoGdTrdDependencia'], //'-'.$GdTrdDependencias['nombreGdTrdDependencia'],
                'cliente'            => $modelCliente->cliente->nombreCliente,
                'numeroDocumento'    => $modelCliente->cliente->numeroDocumentoCliente,
                'clienteUbicacion'   => $nivelGeografico2['nomNivelGeografico2'] . ', ' . $nivelGeografico3['nomNivelGeografico3'],
                'clienteTelefono'    => $modelCliente->cliente->telefonoCliente,
                'clienteEmail'       => $modelCliente->cliente->correoElectronicoCliente,
                'fecha'              => date("Y-m-d"),
                'autorizacion'       => '',
                'autorizacionVia'    => $notificarVia,
                'asuntoTitulo'       => strip_tags($request['asuntoradiradicado']),
                'asunto'             => strip_tags($request['observacionradiradicado']),
                'medioInformativo'   => $medioInformativo,
                'autorizo'           => '',
            ];

            # Generar pdf de la radicacion externa
            PdfController::generar_pdf('RadicacionEmail', 'radiRadicadoPqrs', $filename, $pathUploadFile, $pdfData, $pdfFiles, $footer = 'footerCliente');

            $pathinfo = filesize($pathUploadFile . '' . $filename . '.pdf');
            $file = $pathUploadFile . '' . $filename . '.pdf';
            /* Enviar archivo en base 64 como respuesta de la petición **/
            if (file_exists($file)) {
                //Lee el archivo dentro de una cadena en base 64
                $dataFile = array(
                    'datafile' => base64_encode(file_get_contents($file)),
                    'fileName' => $filename . '.pdf'
                );

                return HelpersJson::encode([
                    'status' => true,
                    'file' => $dataFile,
                    'numeroRadiRadicado' => ''
                ]);
            }
        }
    }

    public function actionFormularioPqrs($id) {
        $cgFormulariosPqrsDetalle = CgFormulariosPqrsDetalle::find()->select('*')->where(['idCgFormulariosPqrs' => $id])->all();
        if ($cgFormulariosPqrsDetalle === []) {
            echo "<option selected value=''>''</option>";
        } else {
            echo "<option selected>Selecciona un tipo de solicitud...</option>";
            foreach ($cgFormulariosPqrsDetalle as $model) {
                echo "<option value='" . $model['idCgFormulariosPqrsDetalle'] . "'>" . $model['nombreCgFormulariosPqrsDetalle'] . "</option>";
            }
        }
    }
}
