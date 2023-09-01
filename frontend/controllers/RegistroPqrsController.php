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

use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\CUploadedFile;
use yii\web\BadRequestHttpException;
use yii\base\InvalidArgumentException;

use common\models\User;

use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;

use api\components\HelperLog;
use api\components\HelperQueryDb;
use api\components\HelperRadicacion;
use api\components\HelperExtraerTexto;

use api\models\UserPqrs;
use api\models\Clientes;
use api\models\UserDetalles;
use api\models\TiposPersonas;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\TiposIdentificacion;
use api\models\CgClasificacionPqrs;
use api\models\CgNumeroRadicado;
use api\models\CgTransaccionesRadicados;
use api\models\ClientesCiudadanosDetalles;
use api\models\GdTrdDependencias;
use api\models\GdTrdTiposDocumentales;
use api\models\RadiDetallePqrsAnonimo;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\CgFormulariosPqrs;
use api\models\CgFormulariosPqrsDetalle;
use api\models\CgFormulariosPqrsDetalleDocumentos;
use api\models\RadiRadicados;
use frontend\models\RadiRadicadosForm;
use frontend\models\RegistroPqrs;
use frontend\models\FormsFonprecon;
use api\models\RadiRemitentes;
use api\models\CgGeneral;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use frontend\models\Anexos;
use api\models\RadiRadicadosDetallePqrs;


use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\radicacion\RadicadosController;

/**
 * Site controller
 */
class RegistroPqrsController extends Controller
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
                   // 'index' => ['GET']
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

    public function actionIndex() {
        if (Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash('error', 'Debes iniciar sesión para Registrar PQRS');
            return $this->goHome();
        }

        $request = Yii::$app->request->post();
        
        if(isset($request) && !empty($request)) {
            
            $tipoTramite = $request['RegistroPqrs']['tipoTramite'];
            //$formRegistroPqrs = Yii::$app->params['formsRegistroPqrs'][$tipoTramite];
            if ($tipoTramite === Yii::$app->params['formsRegistroPqrsIdDefault']) {
                return $this->actionIndexDefault();
            } else {
                if (isset($request['RegistroPqrs']['tipoSolicitud'])) {
                    //$formRegistroPqrsDetalle = Yii::$app->params['formsRegistroPqrs'][$tipoTramite ."-". $request['RegistroPqrs']['tipoSolicitud']];
                    return $this->actionIndexFormsForprecon();
                }

                if (Yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {
                    $listFormularioPqrs = ArrayHelper::map(
                        CgFormulariosPqrs::find()
                            ->where(['idCgFormulariosPqrs' => Yii::$app->params['cgFormulariosPqrsIdPqrs']])
                            ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
                } else {
                    $listFormularioPqrs = ArrayHelper::map(
                        CgFormulariosPqrs::find()
                            ->where(['estadoCgFormulariosPqrs' => Yii::$app->params['statusTodoText']['Activo']])
                            ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
                }

                $listFormularioPqrsDetalle = ArrayHelper::map(
                    CgFormulariosPqrsDetalle::find()
                    ->where(['estadoCgFormulariosPqrsDetalle' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idCgFormulariosPqrs' => $tipoTramite])
                    ->all(), 'idCgFormulariosPqrsDetalle', 'nombreCgFormulariosPqrsDetalle');

                return $this->render('index', [
                    'model_registro_pqrs'          => new RegistroPqrs,
                    'list_formulario_pqrs'         => $listFormularioPqrs ?? [],
                    'list_formulario_pqrs_detalle' => $listFormularioPqrsDetalle,
                    'id_tipo_tramite_actual'       => $tipoTramite,
                ]);
            }
        }

        if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {
            $listFormularioPqrs = ArrayHelper::map(
                CgFormulariosPqrs::find()
                    ->where(['idCgFormulariosPqrs' => Yii::$app->params['cgFormulariosPqrsIdPqrs']])
                    ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
        } else {
            $listFormularioPqrs = ArrayHelper::map(
                CgFormulariosPqrs::find()
                    ->where(['estadoCgFormulariosPqrs' => Yii::$app->params['statusTodoText']['Activo']])
                    ->orderBy('idCgFormulariosPqrs DESC')
                    ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
        }

        // clasificación de la pqrs
        $listClasificacionPqrs = ArrayHelper::map(
            CgClasificacionPqrs::find()
                ->where(['estadoCgClasificacionPqrs' => Yii::$app->params['statusTodoText']['Activo']])
                ->all(), 'idCgClasificacionPqrs', 'nombreCgClasificacionPqrs');

        return $this->render('index', [
            'model_registro_pqrs'          => new RegistroPqrs,
            'list_formulario_pqrs'         => $listFormularioPqrs ?? [],
            'list_formulario_pqrs_detalle' => [],
            'id_tipo_tramite_actual'       => "0",
            'listCalsificacion'            => $listClasificacionPqrs ?? [],
        ]);
    }

    public function actionIndexDefault() {
        $request = Yii::$app->request->post();
        $errors = [];
        $messageAlert = '';   //Mensajes de alerta durante el proceso de creación
        $numeroDocumento = 0; // numero de anexos cargados

        # Configuración general del id dependencia pqrs
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();
        # Se consulta la información de la configuración realizada para el cliente para la creación del numero radicado
        $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
        $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $configGeneral['data']['idDependenciaPqrsCgGeneral']]);

        # Se consulta la información del usuario tramitador
        $modelUser = User::findOne(['id' => Yii::$app->params['idUserTramitadorPqrs']]);

        # Transacción de creación del radicado
        $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);
        $idTransaccion = $modelTransaccion->idCgTransaccionRadicado;

        # Se obtiene la información del cliente que es el remitente - Datos del cliente-externo
        $modelCliente = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);

        # datos de ubicacion del cliente
        $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $modelCliente->cliente->idNivelGeografico2]); 
        $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $modelCliente->cliente->idNivelGeografico3]);

        if(isset($request) && !empty($request) && isset($request['envioData']) && $request['envioData'] === "true") {
            
            foreach($request as $key => $modelos) {
                $transaction = Yii::$app->db->beginTransaction();

                # Se el reasigna al $request el tipo documental definido en params
                $request['RadiRadicadoDetallePqrs']['idTrdTipoDocumental'] = Yii::$app->params['idGdTrdTipoDocumentalPqrs'];
                $model_anexos =  new Anexos;
                $model_anexos->attributes = $request['Anexos'];
                # Cuando es un usuario diferente al anonimo se asigna lo que venga del formulario
                if(isset($request['RadiRadicadosForm']['autorizacionRadiRadicados'])){
                    $notificarVia = $request['RadiRadicadosForm']['autorizacionRadiRadicados'];
                } // Si el usuario no es anonimo siempre se debe notificar a un correo = 10 
                else{
                    $notificarVia = Yii::$app->params['statusTodoText']['Activo'];
                }

                # Se valida que haya un tipo documental
                if(isset($request['RadiRadicadosForm']['idTrdTipoDocumental'])){
                    $idTrdTipoDocumental = $request['RadiRadicadosForm']['idTrdTipoDocumental'];
                } else { // Sino muestra un mensaje de alerta
                    $transaction->rollBack();
                    $messageAlert = Yii::$app->session->setFlash('error', 'No se ha seleccionado ningún tipo de documental'); 
                    break;
                }            

                # Almacenamiento del Radicado
                $numRadicado = RadicadosController::generateNumberFiling(Yii::$app->params['idCgTipoRadicado']['radiPqrs'], $modelCgNumeroRadicado, $modelDependencia);
                $calculoRadicado = RadicadosController::calcularfechaVencimiento($idTrdTipoDocumental ,'');

                # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
                if($configGeneral['status']){

                    $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];
                    $emailUserTramitadorPqrs = $configGeneral['data']['correoNotificadorPqrsCgGeneral'];

                    /*Con el correo configurado en PQRS se obtiene el id del usuario responsable de la TRD*/
                    $usuarioData = User::findOne(['email' => $emailUserTramitadorPqrs]);
                    
                    if(!is_null($usuarioData) && isset($usuarioData->userDetalles)){                        
                        $idUserPqrs = $usuarioData->id;

                    } else {
                        $transaction->rollBack();
                        $messageAlert .= Yii::$app->session->setFlash('error', 'No hay ningún usuario con el correo de administrador PQRSD');
                        break;
                    }

                } else {
                    $transaction->rollBack();
                    $messageAlert .= Yii::$app->session->setFlash('error', $configGeneral['message']);
                    break;
                }

                # Se crea registro en radicados
                $modelFile = new RadiRadicados();
                $modelFile->numeroRadiRadicado            = $numRadicado['numeroRadiRadicado'];
                $modelFile->asuntoRadiRadicado            = strip_tags($request['RadiRadicadosForm']['asuntoRadiRadicado']);
                $modelFile->foliosRadiRadicado            = (string) count($request['Anexos']['anexo']);
                $modelFile->fechaVencimientoRadiRadicados = date("Y-m-d", strtotime($calculoRadicado['fechaFormatoDB']));
                $modelFile->idTrdTipoDocumental           = $idTrdTipoDocumental;
                $modelFile->user_idCreador                = $idUserPqrs;
                $modelFile->idTrdDepeUserCreador          = $idDependenciaUserPqrs; 
                $modelFile->user_idTramitador             = $idUserPqrs;
                $modelFile->idTrdDepeUserTramitador       = $idDependenciaUserPqrs;                
                $modelFile->PrioridadRadiRadicados        = Yii::$app->params['prioridadRadicado']['baja']; 
                $modelFile->idCgTipoRadicado              = Yii::$app->params['idCgTipoRadicado']['radiPqrs']; 
                $modelFile->idCgMedioRecepcion            = Yii::$app->params['CgMedioRecepcionNumber']['sitiosWeb'];
                $modelFile->cantidadCorreosRadicado       = 0;
                $modelFile->estadoRadiRadicado            = Yii::$app->params['statusTodoText']['Activo'];
                $modelFile->autorizacionRadiRadicados     = $notificarVia;
                //$modelFile->numeroCuenta                  = $request['RadiRadicadosForm']['numeroCuenta'];

                if (!$modelFile->save()) {  
                    $transaction->rollBack();    
                    $errors[] = $modelFile->getErrors(); 
                    break;   

                } else {
                    # Información para el log de auditoria
                    if(!is_null($usuarioData)){

                        # Se obtiene la información del usuario PQRSD
                        $user_detalles_log = UserDetalles::find()->where(["idUser" => $usuarioData->id])->one();
                        if($modelFile->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

                        # Nombre remitente
                        $nombreRemitente =  $modelCliente->cliente->nombreCliente;                        

                        $dataRadicados = 'Id Radicado: '.$modelFile->idRadiRadicado;
                        $dataRadicados .= ', Número Radicado: '.$modelFile->numeroRadiRadicado;
                        $dataRadicados .= ', Asunto Radicado: '.$modelFile->asuntoRadiRadicado;
                        $dataRadicados .= ', Tipo Radicación: '.$modelFile->cgTipoRadicado->nombreCgTipoRadicado;
                        $dataRadicados .= ', Tipo Documental: '.$modelFile->trdTipoDocumental->nombreTipoDocumental;
                        $dataRadicados .= ', Prioridad: '.$prioridad;
                        $dataRadicados .= ', Remitente/Destinatario: '.$nombreRemitente;
                        $dataRadicados .= ', Usuario Tramitador: '.$user_detalles_log->nombreUserDetalles.' '.$user_detalles_log->apellidoUserDetalles;
                        $dataRadicados .= ', Usuario Creador: '.$user_detalles_log->nombreUserDetalles.' '.$user_detalles_log->apellidoUserDetalles;
                        $dataRadicados .= ', Cantidad envíos correos electrónicos del radicado: '.$modelFile->cantidadCorreosRadicado;
                        $dataRadicados .= ', Fecha creación: '.$modelFile->creacionRadiRadicado;                    
                        $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelFile->estadoRadiRadicado];

                    }
                }

                # Gestion PQRS Anonima 
                if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']){
                
                    if(isset($request['RadiDetallePqrsAnonimo'])){

                        $model_anonima = new RadiDetallePqrsAnonimo;
                        $model_anonima->attributes = $request['RadiDetallePqrsAnonimo'];
                        $model_anonima->idRadiRadicado = $modelFile->idRadiRadicado;                                

                        // Si la autorización de la notificación es 10 = a por correo
                        if($notificarVia == Yii::$app->params['statusTodoText']['Activo']){
                            $model_anonima->idNivelGeografico1 = Yii::$app->params['nivelGeografico']['nivelGeograficoPais'];
                            $model_anonima->idNivelGeografico3 = Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio'];
                            $model_anonima->idNivelGeografico2 = Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento'];

                            $model_anonima->direccionRadiDetallePqrsAnonimo = 'sin dirección';
                            $model_anonima->emailRadiDetallePqrsAnonimo = $request['RadiDetallePqrsAnonimo']['correoElectronicoCliente'];
                            $medioInformativo = $model_anonima->emailRadiDetallePqrsAnonimo;
                        }
                        else{
                            $model_anonima->idNivelGeografico1 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico1'];
                            $model_anonima->idNivelGeografico2 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico2'];
                            $model_anonima->idNivelGeografico3 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico3'];

                            $model_anonima->emailRadiDetallePqrsAnonimo = 'sin correo';
                            $model_anonima->direccionRadiDetallePqrsAnonimo = $request['RadiDetallePqrsAnonimo']['dirCam1'].' '.$request['RadiDetallePqrsAnonimo']['dirCam2'].' # '.$request['RadiDetallePqrsAnonimo']['dirCam4'].' '.$request['RadiDetallePqrsAnonimo']['dirCam5'].' '.$request['RadiDetallePqrsAnonimo']['dirCam6'].' ';
                            
                            $medioInformativo = $model_anonima->direccionRadiDetallePqrsAnonimo;
                        }

                        if (!$model_anonima->save()) { 
                            $transaction->rollBack();    
                            $errors[] = $model_anonima->getErrors(); 
                            break;   
                        }                        
                         
                        //$notificarVia = $model_anonima->direccionRadiDetallePqrsAnonimo; 
                        $request['RadiRadicadoDetallePqrs']['autorizacionRadiDetallePqrs'] = Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica'];

                    }
                }else{
                    $medioInformativo = $modelCliente->cliente->correoElectronicoCliente;
                }
                
                #Almacenamiento en radiRemitentes             
                $modelSender = new RadiRemitentes();
                $modelSender->idRadiRadicado = $modelFile->idRadiRadicado;
                $modelSender->idRadiPersona = $modelCliente->idCliente;
                $modelSender->idTipoPersona = $modelCliente->cliente->idTipoPersona;
    
                if (!$modelSender->save()) { 
                    $transaction->rollBack();    
                    $errors[] = $modelSender->getErrors(); 
                    break;   
                }

                #Almacenamiento en radiRadicadosDetallePqrs
                $modelRadiRadicadosDetallePqrs = new RadiRadicadosDetallePqrs();
                $modelRadiRadicadosDetallePqrs->idRadiRadicado = $modelFile->idRadiRadicado;
                $modelRadiRadicadosDetallePqrs->idCgClasificacionPqrs = $request['RegistroPqrs']['tipoClasificacion'];
    
                if (!$modelRadiRadicadosDetallePqrs->save()) { 
                    $transaction->rollBack();    
                    $errors[] = $modelRadiRadicadosDetallePqrs->getErrors(); 
                    break;   
                }

                # Observación para el log - Log de Auditoria 
                $observation =  Yii::$app->params['eventosLogText']['crear'] . ' de PQRSD de forma correcta por la página web.';
                HelperLog::logAdd(
                    true,
                    $idUserPqrs, //Id user
                    "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                    Yii::$app->controller->route, //Modulo
                    $observation, //texto para almacenar en el evento
                    '',
                    $dataRadicados, //Data
                    array() //No validar estos campos
                );

                /*** Log de Radicados  ***/
                $observationFiling = Yii::$app->params['eventosLogTextRadicado']['New']." por medio de página web.";
                HelperLog::logAddFiling(
                    $idUserPqrs,  
                    $modelFile->idTrdDepeUserTramitador,
                    $modelFile->idRadiRadicado, //Id radicado
                    $idTransaccion,
                    $observationFiling, //observación
                    $modelFile,
                    array() //No validar estos campos
                );

                # Gestion de Documentos de la solicitud PQRS
                $GdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelFile->idTrdDepeUserTramitador]);
                $anio = date("Y"); 
                $pdfFiles = [];

                $pathUploadFile = Yii::getAlias('@webroot')                   
                    . "/" . Yii::$app->params['bodegaRadicados'] 
                    . "/" . $anio                            
                    . "/" . $GdTrdDependencias['codigoGdTrdDependencia']
                    . "/"
                ;

                $pathUploadFile = str_replace('frontend/','api/',$pathUploadFile);

                // Verificar que la carpeta exista y crearla en caso de que no exista
                if (!file_exists($pathUploadFile)) {
                    if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                        $transaction->rollBack();   
                        $messageAlert .= Yii::$app->session->setFlash('error', 'El directorio no existe'); 
                        break;
                    }
                }
            
                if ($model_anexos->load(Yii::$app->request->post())) {

                    $files = UploadedFile::getInstances($model_anexos, 'anexo');
            
                    if(isset($files) && !empty($files)){                    
    
                        foreach($files as $key => $fileUpload){

                            # El documento nro 1 debe ser el pdf generado por el sistema con la información del radicado
                            $numeroDocumento = ($numeroDocumento==0) ? $numeroDocumento+2 : $numeroDocumento+1;
    
                            $modelDoc = new RadiDocumentos;
                            $modelDoc->nombreRadiDocumento       = $modelFile->numeroRadiRadicado.'-'.$numeroDocumento.'.'.$fileUpload->extension;                       
                            $modelDoc->rutaRadiDocumento         = $pathUploadFile;                   
                            $modelDoc->extencionRadiDocumento    = $fileUpload->extension;
                            $modelDoc->idRadiRadicado            = $modelFile->idRadiRadicado;
                            $modelDoc->idGdTrdTipoDocumental     = $modelFile->idTrdTipoDocumental;
                            $modelDoc->descripcionRadiDocumento  = Yii::$app->params['anexosPaginaPublica'];  
                            $modelDoc->idUser                    = $idUserPqrs; 
                            $modelDoc->numeroRadiDocumento       = $numeroDocumento;
                            $modelDoc->estadoRadiDocumento       = Yii::$app->params['statusTodoText']['Activo'];
                            $modelDoc->creacionRadiDocumento     = date('Y-m-d H:i:s');
                            $modelDoc->isPublicoRadiDocumento    = Yii::$app->params['statusTodoText']['Activo'];
                            $modelDoc->publicoPagina             = Yii::$app->params['statusTodoText']['Activo'];
                            $modelDoc->tamanoRadiDocumento       = '' . $fileUpload->size / 1000 . ' KB';
                                                        
                            if (!$modelDoc->save()) { 
                                $transaction->rollBack();
                                $errors[] = $modelDoc->getErrors(); 
                                break;

                            } else {
                                $RadiDocumentos = new RadiDocumentos();
                                $attributeLabels = $RadiDocumentos->attributeLabels();
                                    
                                #Array de radiDocumento para insertar en el log de auditoria
                                $dataOld = '';
                                $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDoc->idRadiDocumento
                                    . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDoc->nombreRadiDocumento
                                    . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDoc->descripcionRadiDocumento
                                    . ', Tipo Documental: ' . $modelDoc->idGdTrdTipoDocumental0->nombreTipoDocumental
                                ;

                                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachment']);   
                                $observation =  Yii::$app->params['eventosLogText']['FileUpload'] . ' al radicado ' .  $modelFile->numeroRadiRadicado . ' por el usuario ciudadano ' . $modelCliente->cliente->nombreCliente;// texto para almacenar en el evento

                                /*** log Auditoria ***/        
                                HelperLog::logAdd(
                                    true,
                                    $idUserPqrs, //Id user
                                    "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                                    Yii::$app->controller->route, //Modulo
                                    $observation,
                                    $dataOld,
                                    $dataNew, //Data
                                    array() //No validar estos campos
                                );

                                # Observacion
                                HelperLog::logAddFiling(
                                    $idUserPqrs, 
                                    $modelFile->idTrdDepeUserTramitador,
                                    $modelFile->idRadiRadicado, //Id radicado
                                    $idTransacion->idCgTransaccionRadicado,
                                    $observation,
                                    [],
                                    array() //No validar estos campos
                                );

                            }
                            
                            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id sin eliminar el tempFile
                            $uploadExecute = $fileUpload->saveAs($modelDoc->rutaRadiDocumento.$modelDoc->nombreRadiDocumento );

                            ///////////////////////// EXTRAER TEXTO  ////////////////////////
                                // $helperExtraerTexto = new HelperExtraerTexto($modelDoc->rutaRadiDocumento.$modelDoc->nombreRadiDocumento);

                                // $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                                //     $modelDoc->idRadiDocumento,
                                //     Yii::$app->params['tablaOcrDatos']['radiDocPrincipales']  
                                // );
                                
                                // if($helperOcrDatos['status'] != true){
                                //     Yii::$app->session->setFlash('error', $helperOcrDatos['message']);
                                // }

                            $pdfFiles[]  = [
                                'nombreRadiDocumento' => $modelDoc->nombreRadiDocumento,
                            ];
                        }
                    }
                }

                # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
                $filename = $modelFile->numeroRadiRadicado . '-1';
                $numeroAnexos = Yii::$app->db->createCommand("SELECT COUNT(idRadiDocumento) as conteo from radiDocumentos where idRadiRadicado=".$modelFile->idRadiRadicado." and numeroRadiDocumento > 1")->queryScalar();

                $pdfData = [
                    'numeroAnexos'       => $numeroAnexos ?? 0,
                    'numeroRadiRadicado' => $modelFile->numeroRadiRadicado,
                    'dependencias'       => $GdTrdDependencias['codigoGdTrdDependencia'], //'-'.$GdTrdDependencias['nombreGdTrdDependencia'],
                    'cliente'            => $modelCliente->cliente->nombreCliente,
                    'numeroDocumento'    => $modelCliente->cliente->numeroDocumentoCliente,
                    'clienteUbicacion'   => $nivelGeografico2['nomNivelGeografico2'].', '.$nivelGeografico3['nomNivelGeografico3'],
                    'clienteTelefono'    => $modelCliente->cliente->telefonoCliente,
                    'clienteEmail'       => $modelCliente->cliente->correoElectronicoCliente,
                    'fecha'              => HelperRadicacion::getFormatosFecha(date("Y-m-d"))['formatoFrontend'],
                    'autorizacion'       => $modelFile->autorizacionRadiRadicados,
                    'autorizacionVia'    => $notificarVia,
                    'asuntoTitulo'       => strip_tags($request['RadiRadicadosForm']['asuntoRadiRadicado']),           
                    'asunto'             => strip_tags($request['RadiRadicadosForm']['observacionRadiRadicado']),
                    'medioInformativo'   => $medioInformativo,
                    'autorizo'           => isset($request['autorizanotificaciones']) ? $request['autorizanotificaciones'] : '0'
                ];

                # Generar pdf de la radicacion externa
                PdfController::generar_pdf('RadicacionEmail','radiRadicadoPqrs', $filename, $pathUploadFile, $pdfData, $pdfFiles, $footer = 'footerCliente');

                $pathinfo = filesize($pathUploadFile.''.$filename.'.pdf');

                # Agregar PDF a la tabla radiDocumentos
                $modelDocPrincipal = new RadiDocumentos;
                $modelDocPrincipal->idRadiRadicado         = $modelFile->idRadiRadicado;
                $modelDocPrincipal->idUser                 = $idUserPqrs;// ORFEO
                $modelDocPrincipal->nombreRadiDocumento    = $filename.'.pdf';
                $modelDocPrincipal->rutaRadiDocumento      = $pathUploadFile;
                $modelDocPrincipal->extencionRadiDocumento = 'pdf';
                $modelDocPrincipal->idGdTrdTipoDocumental  = $idTrdTipoDocumental;
                $modelDocPrincipal->isPublicoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                $modelDocPrincipal->descripcionRadiDocumento = 'Anexo principal del documento';
                $modelDocPrincipal->numeroRadiDocumento    = 1;
                $modelDocPrincipal->tamanoRadiDocumento    = '' . $pathinfo / 1000 . ' KB';
                $modelDocPrincipal->publicoPagina = Yii::$app->params['statusTodoText']['Activo'];

                if (!$modelDocPrincipal->save()) {

                    $transaction->rollBack();
                    $errors[] = $modelDocPrincipal->getErrors();
                    break;

                }else{

                    ///////////////////////// NOTIFICACIONES CORREO ////////////////////////

                        # Envio de correo al usuario tramitador Pqrs
                        $headMailText  = Yii::t('app', 'headMailRegisterPqrs',[
                            'numRadicado' => $numRadicado['numeroRadiRadicado']
                        ]);              
            
                        $textBody  = Yii::t('app', 'textBodyRegisterPqrs', [
                            'numRadicado'  => $numRadicado['numeroRadiRadicado'],
                            'asunto'       => $modelFile->asuntoRadiRadicado,
                            'dias'         => $calculoRadicado['diasRestantes']
                        ]);
                    
                        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelFile->idRadiRadicado));
                        $bodyMail = 'radicacion-html'; 
                        $subject = 'subjectRegisterPqrs';
                        
                        # Agregar numero de anexos si existen al bodyText del correo
                        if($numeroDocumento > 0){
                            $textBody  = $textBody.' '.Yii::t('app', 'addAnexoCommentPqrs',[
                                'numAnexos' => $numeroAnexos
                            ]);
                        }
            
                        // $envioCorreo = CorreoController::radicacion($modelUser->email, $headMailText, $textBody, $bodyMail, $dataBase64Params, $subject);
                        # Fin de envio de correo al usuario tramitador Pqrs
    
                        # Notificacion via correo electrónico al usuario logueado
                        if($modelFile->autorizacionRadiRadicados == Yii::$app->params['seguimientoViaPQRS']['text']['CorreoElectrónico']){
                            
                            /***  Log Auditoria  ***/
                            HelperLog::logAdd(
                                true,
                                $idUserPqrs,
                                $modelFile->idTrdDepeUserTramitador,
                                Yii::$app->controller->route, //Modulo
                                "El ciudadano ".$modelCliente->cliente->nombreCliente." autorizó el envío de respuesta por correo electrónico.", //evento
                                '', // data old
                                '', //Data
                                [] //No validar estos campos
                            );
                            /*** Fin log Auditoria ***/
    
                            # Envio de correo al usuario registrador
                            $headMailText  = Yii::t('app', 'headMailPqrsRequest',[
                                'numRadicado' => $numRadicado['numeroRadiRadicado']
                            ]);              
    
                            $textBody  = Yii::t('app', 'textBodyPqrsRequest', [
                                'numRadicado'  => $numRadicado['numeroRadiRadicado'],
                                'asunto'       => $modelFile->asuntoRadiRadicado
                            ]);
                        
                            // $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelFile->idRadiRadicado));
                            $bodyMail = 'radicacion-html'; 
                            $subject = 'subjectPqrsRequest';
    
                            $file = [];
                            $file['rutaRadiDocumento'] = $pathUploadFile;
                            $file ['nombreRadiDocumento'] = $filename.'.pdf';
                            $buttonDisplay = 'true';
                            $link =  Yii::$app->urlManager->createAbsoluteUrl(['site/login']);
                            $nameButtonLink = Yii::t('app', 'sendCitizenEnter');
                            // $envioCorreo = CorreoController::sendEmail($notificarVia, $headMailText, $textBody, $bodyMail, [$file], $link, $subject, $nameButtonLink);
                            # Fin de envio de correo al usuario registrador
                        }
                }
                
                $transaction->commit();  
                break;
               
            }

            # Validación de almacenamiento
            if(count($errors) >= 1){
                foreach($errors as $model){                
                    foreach($model as $key){                    
                        foreach($key as $message){
                            Yii::$app->session->setFlash('error', $message);
                        }
                    }                  
                }
            }

            
            # Validación de errores
            if(isset($modelFile) && count($errors) == 0 ){

                Yii::$app->session->setFlash('success', 'PQRSD Registrada con éxito, número de radicado: ' . $modelFile->numeroRadiRadicado);
                return $this->goHome();
    
            } elseif (isset($messageAlert)){
                return $this->goHome();

            } else {
                Yii::$app->session->setFlash('error', 'Error al procesar solicitud PQRS');
                return $this->goHome();
            }
        }

        $user_detalles = $modelCliente->cliente->nombreCliente;
        $correoRemitente = $modelCliente->cliente->correoElectronicoCliente;

        $gdTrdTiposDocumentales = GdTrdTiposDocumentales::find();
        $gdTrdTiposDocumentales = HelperQueryDb::getQuery('innerJoin', $gdTrdTiposDocumentales, 'cgTipoRadicadoDocumental', ['cgTipoRadicadoDocumental' => 'idGdTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
        $gdTrdTiposDocumentales = $gdTrdTiposDocumentales->where(['cgTipoRadicadoDocumental.idCgTipoRadicado' => Yii::$app->params['CgTipoRadicado']['radiPqrs']])
        ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])->orderBy(['idGdTrdTipoDocumental' => SORT_DESC])->all();
        
        # Listado gdTrdTiposDocumentales
        $list_tipos_documentales = ArrayHelper::map($gdTrdTiposDocumentales, 'idGdTrdTipoDocumental', 'nombreTipoDocumental');

        # Listado cgClasificacionPqrs
        $list_tipos_clasificacion = ArrayHelper::map(CgClasificacionPqrs::find()->where(['estadoCgClasificacionPqrs' => Yii::$app->params['statusTodoText']['Activo']])->all(), 'idCgClasificacionPqrs', 'nombreCgClasificacionPqrs');

        # List Respuesta para la Pqrs
        $list_autorizacion = Yii::$app->params['seguimientoViaPQRS']['number'];

        # Gestion de PQRS Anonimas

        # List Paises
        $nivelGeografico1 = NivelGeografico1::find();
        $nivelGeografico1 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico1, 'nivelGeografico2', ['nivelGeografico2' => 'idNivelGeografico1', 'nivelGeografico1' => 'nivelGeografico1']);
        $nivelGeografico1 = $nivelGeografico1->orderBy('nomNivelGeografico1', 'ASC')->all();

        $list_paises = ArrayHelper::map($nivelGeografico1, 'nivelGeografico1', 'nomNivelGeografico1');

        # List Departamentos
        $list_departamentos =  ArrayHelper::map( NivelGeografico2::find()->where(['nivelGeografico2.idNivelGeografico1' => 1])->all(), 'nivelGeografico2', 'nomNivelGeografico2');

        # List Ciudades
        $nivelGeografico3 = NivelGeografico3::find();
        $nivelGeografico3 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico3, 'nivelGeografico2', ['nivelGeografico2' => 'nivelGeografico2', 'nivelGeografico3' => 'idNivelGeografico2']);
        $nivelGeografico3 = HelperQueryDb::getQuery('innerJoin', $nivelGeografico3, 'nivelGeografico1', ['nivelGeografico1' => 'nivelGeografico1', 'nivelGeografico2' => 'idNivelGeografico1']);
        $nivelGeografico3 = $nivelGeografico3->all();

        $list_ciudades = ArrayHelper::map($nivelGeografico3, 'nivelGeografico3', 'nomNivelGeografico3');

        $exceptionFile = Yii::$app->params['exceptiosFilePqrs'];

        $listFormularioPqrs = ArrayHelper::map(CgFormulariosPqrs::find()->where(['estadoCgFormulariosPqrs' => Yii::$app->params['statusTodoText']['Activo']])->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');

        // clasificación de la pqrs
        $listClasificacionPqrs = ArrayHelper::map(
            CgClasificacionPqrs::find()
                ->where(['estadoCgClasificacionPqrs' => Yii::$app->params['statusTodoText']['Activo']])
                ->all(), 'idCgClasificacionPqrs', 'nombreCgClasificacionPqrs');

        return $this->render('index-default', [
            'model_radicado'               => $model_radicado ?? new RadiRadicadosForm,
            'model_anexos'                 => $model_anexos ?? new Anexos,
            'model_anonima'                => $model_anonima ?? new RadiDetallePqrsAnonimo,
            'user_detalles'                => $user_detalles,
            'correoRemitente'              => $correoRemitente,
            'exceptionFile'                => $exceptionFile,
            'list_tipos_documentales'      => $list_tipos_documentales ?? [],
            'list_tipos_clasificacion'     => $list_tipos_clasificacion ?? [],
            'list_autorizacion'            => $list_autorizacion   ?? [],
            'list_paises'                  => $list_paises            ?? [],
            'list_departamentos'           => $list_departamentos     ?? [],
            'list_ciudades'                => $list_ciudades          ?? [],
            'model_registro_pqrs'          => new RegistroPqrs,
            'list_formulario_pqrs'         => $listFormularioPqrs ?? [],
            'list_formulario_pqrs_detalle' => [],
            'listCalsificacion'            => $listClasificacionPqrs ?? [],
        ]);

    }

    public function actionIndexFormsForprecon() {
        $request = Yii::$app->request->post();
        $tipoTramite = $request['RegistroPqrs']['tipoTramite'];
        $datosSelectorPrestacionCgFormulariosPqrsDetalle = [];
        $datosSelectorBeneficiarioCgFormulariosPqrsDetalle = [];
        $activarBeneficiarioCgFormulariosPqrsDetalle = "";
        $activarSelectBeneficiario = false;
        $mostrarDocs = false;
        $existenSelectoresAdicionales = false;
        $activarGuardar = false;

        if(isset($request) && !empty($request) && isset($request['envioData']) && $request['envioData'] === "true") {
            $errors = [];
            $messageAlert = '';
            $numeroDocumento = 0;
            $transaction = Yii::$app->db->beginTransaction();
            $prueba = $_FILES;

            $modelCliente = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
            $modelUser = User::findOne(['id' => Yii::$app->params['idUserTramitadorPqrs']]);

            $dataFormularioPqrsDetalle = CgFormulariosPqrsDetalle::find()
                ->where(['estadoCgFormulariosPqrsDetalle' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                ->one();

            $dataFormularioPqrsDetalle = CgFormulariosPqrsDetalle::find()
                ->where(['estadoCgFormulariosPqrsDetalle' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                ->one();

            $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);
            $idTransaccion = $modelTransaccion->idCgTransaccionRadicado;

            # Configuración general del id dependencia pqrs
            $configGeneral = ConfiguracionGeneralController::generalConfiguration();

            # Se consulta la información de la configuración realizada para el cliente para la creación del numero radicado
            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $configGeneral['data']['idDependenciaPqrsCgGeneral']]);

            # Almacenamiento del Radicado
            $numRadicado = RadicadosController::generateNumberFiling(Yii::$app->params['idCgTipoRadicado']['radiPqrs'], $modelCgNumeroRadicado, $modelDependencia);
            $calculoRadicado = RadicadosController::calcularfechaVencimiento($dataFormularioPqrsDetalle['idTipoDocumentalCgFormulariosPqrsDetalle'] ,'');
            $notificarVia = Yii::$app->params['statusTodoText']['Activo'];

            if($configGeneral['status']) {
                $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];
                $emailUserTramitadorPqrs = $configGeneral['data']['correoNotificadorPqrsCgGeneral'];
                /*Con el correo configurado en PQRS se obtiene el id del usuario responsable de la TRD*/
                $usuarioData = User::findOne(['email' => $emailUserTramitadorPqrs]);
                if(!is_null($usuarioData) && isset($usuarioData->userDetalles)) {
                    $idUserPqrs = $usuarioData->id;
                } else {
                    $transaction->rollBack();
                    $messageAlert .= Yii::$app->session->setFlash('error', 'No hay ningún usuario con el correo de administrador PQRSD');
                }

            } else {
                $transaction->rollBack();
                $messageAlert .= Yii::$app->session->setFlash('error', $configGeneral['message']);
            }

            $cantFiles = 0;
            foreach ($_FILES as $keyFile => $file) {
                if ($file['error'] === 0) {
                    $cantFiles++;
                }
            }

            $modelFile = new RadiRadicados();
            $modelFile->numeroRadiRadicado                             = $numRadicado['numeroRadiRadicado'];
            $modelFile->asuntoRadiRadicado                             = 'Radicación del formulario de solicitud de prestaciones económicas radicación de prestación: ' . $dataFormularioPqrsDetalle['nombreCgFormulariosPqrsDetalle'];
            $modelFile->foliosRadiRadicado                             = (string) $cantFiles;
            $modelFile->fechaVencimientoRadiRadicados                  = date("Y-m-d", strtotime($calculoRadicado['fechaFormatoDB']));
            $modelFile->idTrdTipoDocumental                            = $dataFormularioPqrsDetalle['idTipoDocumentalCgFormulariosPqrsDetalle'];
            $modelFile->idGdTrdSerie                                   = $dataFormularioPqrsDetalle['idSerieCgFormulariosPqrsDetalle'];
            $modelFile->idGdTrdSubserie                                = $dataFormularioPqrsDetalle['idSubserieCgFormulariosPqrsDetalle'];
            $modelFile->user_idCreador                                 = $idUserPqrs;
            $modelFile->idTrdDepeUserCreador                           = $idDependenciaUserPqrs;
            $modelFile->user_idTramitador                              = $idUserPqrs;
            $modelFile->idTrdDepeUserTramitador                        = $idDependenciaUserPqrs;
            $modelFile->PrioridadRadiRadicados                         = Yii::$app->params['prioridadRadicado']['baja'];
            $modelFile->idCgTipoRadicado                               = Yii::$app->params['idCgTipoRadicado']['radiPqrs'];
            $modelFile->idCgMedioRecepcion                             = Yii::$app->params['CgMedioRecepcionNumber']['sitiosWeb'];
            $modelFile->cantidadCorreosRadicado                        = 0;
            $modelFile->estadoRadiRadicado                             = Yii::$app->params['statusTodoText']['Activo'];
            $modelFile->autorizacionRadiRadicados                      = $notificarVia;
            $modelFile->cargoFonpreconFormRadiRadicado                 = $request['FormsFonprecon']['cargo'];
            $modelFile->medioRespuestaFonpreconFormRadiRadicado        = $request['FormsFonprecon']['medioRespuesta'];
            $modelFile->calidadCausanteFonpreconFormRadiRadicado       = $request['FormsFonprecon']['calidadCausante'];
            $modelFile->empleadorFonpreconFormRadiRadicado             = $request['FormsFonprecon']['tipoEmpleador'];
            $modelFile->categoriaPrestacionFonpreconFormRadiRadicado   = $request['FormsFonprecon']['categoriaPrestacion'] ?? '';
            $modelFile->categoriaBeneficiarioFonpreconFormRadiRadicado = $request['FormsFonprecon']['calidadBeneficiario'] ?? '';

            # Se obtiene la información del usuario PQRSD
            $user_detalles_log = UserDetalles::find()->where(["idUser" => $usuarioData->id])->one();

            if (!$modelFile->save()) {
                $transaction->rollBack();
                $errors[] = $modelFile->getErrors();
            } else {                
                                
                if(!is_null($usuarioData)) {

                    if($modelFile->PrioridadRadiRadicados == 0){ $prioridad = 'Baja'; } else { $prioridad = 'Alta'; }

                    # Nombre remitente
                    $nombreRemitente =  $modelCliente->cliente->nombreCliente;

                    $dataRadicados = 'Id Radicado: '.$modelFile->idRadiRadicado;
                    $dataRadicados .= ', Número Radicado: '.$modelFile->numeroRadiRadicado;
                    $dataRadicados .= ', Asunto Radicado: '.$modelFile->asuntoRadiRadicado;
                    $dataRadicados .= ', Tipo Radicación: '.$modelFile->cgTipoRadicado->nombreCgTipoRadicado;
                    $dataRadicados .= ', Tipo Documental: '.$modelFile->trdTipoDocumental->nombreTipoDocumental;
                    $dataRadicados .= ', Prioridad: '.$prioridad;
                    $dataRadicados .= ', Remitente/Destinatario: '.$nombreRemitente;
                    $dataRadicados .= ', Usuario Tramitador: ' . $user_detalles_log->nombreUserDetalles .' '. $user_detalles_log->apellidoUserDetalles;
                    $dataRadicados .= ', Usuario Creador: ' . $user_detalles_log->nombreUserDetalles .' '. $user_detalles_log->apellidoUserDetalles;
                    $dataRadicados .= ', Cantidad envíos correos electrónicos del radicado: '. $modelFile->cantidadCorreosRadicado;
                    $dataRadicados .= ', Fecha creación: '.$modelFile->creacionRadiRadicado;
                    $dataRadicados .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelFile->estadoRadiRadicado];
                }
            }

            if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {
                if(isset($request['RadiDetallePqrsAnonimo'])) {
                    $model_anonima = new RadiDetallePqrsAnonimo;
                    $model_anonima->attributes = $request['RadiDetallePqrsAnonimo'];
                    $model_anonima->idRadiRadicado = $modelFile->idRadiRadicado;
                    // Si la autorización de la notificación es 10 = a por correo
                    if($notificarVia == Yii::$app->params['statusTodoText']['Activo']) {
                        $model_anonima->idNivelGeografico1 = Yii::$app->params['nivelGeografico']['nivelGeograficoPais'];
                        $model_anonima->idNivelGeografico3 = Yii::$app->params['nivelGeografico']['nivelGeograficoMunicipio'];
                        $model_anonima->idNivelGeografico2 = Yii::$app->params['nivelGeografico']['nivelGeograficoDepartamento'];

                        $model_anonima->direccionRadiDetallePqrsAnonimo = 'sin dirección';
                        $model_anonima->emailRadiDetallePqrsAnonimo = $request['RadiDetallePqrsAnonimo']['correoElectronicoCliente'];
                        $medioInformativo = $model_anonima->emailRadiDetallePqrsAnonimo;
                    } else {
                        $model_anonima->idNivelGeografico1 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico1'];
                        $model_anonima->idNivelGeografico2 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico2'];
                        $model_anonima->idNivelGeografico3 = $request['RadiDetallePqrsAnonimo']['idNivelGeografico3'];

                        $model_anonima->emailRadiDetallePqrsAnonimo = 'sin correo';
                        $model_anonima->direccionRadiDetallePqrsAnonimo = $request['RadiDetallePqrsAnonimo']['dirCam1'].' '.$request['RadiDetallePqrsAnonimo']['dirCam2'].' # '.$request['RadiDetallePqrsAnonimo']['dirCam4'].' '.$request['RadiDetallePqrsAnonimo']['dirCam5'].' '.$request['RadiDetallePqrsAnonimo']['dirCam6'].' ';
                        $medioInformativo = $model_anonima->direccionRadiDetallePqrsAnonimo;
                    }

                    if (!$model_anonima->save()) {
                        $transaction->rollBack();
                        $errors[] = $model_anonima->getErrors();
                    }

                    $request['RadiRadicadoDetallePqrs']['autorizacionRadiDetallePqrs'] = Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica'];
                }

            } else {
                $medioInformativo = $modelCliente->cliente->correoElectronicoCliente;
            }

            #Almacenamiento en radiRemitentes
            $modelSender = new RadiRemitentes();
            $modelSender->idRadiRadicado = $modelFile->idRadiRadicado;
            $modelSender->idRadiPersona = $modelCliente->idCliente;
            $modelSender->idTipoPersona = $modelCliente->cliente->idTipoPersona;

            if (!$modelSender->save()) {
                $transaction->rollBack();
                $errors[] = $modelSender->getErrors();
            }

            $modelcgFormulariosPqrs = CgFormulariosPqrs::findOne(['idCgFormulariosPqrs'=> $request['RegistroPqrs']['tipoTramite']]);
            
            # Validación de almacenamiento
            if(count($errors) == 0){

                # Observación para el log - Log de Auditoria
                $observation =  Yii::$app->params['eventosLogText']['crear'] . ' de '.$modelcgFormulariosPqrs->nombreCgFormulariosPqrs.' de forma correcta por la página web.';
                HelperLog::logAdd(
                    true,
                    $idUserPqrs, //Id user
                    "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                    Yii::$app->controller->route, //Modulo
                    $observation, //texto para almacenar en el evento
                    '',
                    $dataRadicados, //Data
                    array() //No validar estos campos
                );

                /*** Log de Radicados  ***/
                $observationFiling = Yii::$app->params['eventosLogTextRadicado']['New']." por medio de página web.";
                HelperLog::logAddFiling(
                    $idUserPqrs,
                    $modelFile->idTrdDepeUserTramitador,
                    $modelFile->idRadiRadicado, //Id radicado
                    $idTransaccion,
                    $observationFiling, //observación
                    $modelFile,
                    array() //No validar estos campos
                );
            }            

            # Gestion de Documentos de la solicitud PQRS
            $GdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelFile->idTrdDepeUserTramitador]);
            $anio = date("Y");
            $pdfFiles = [];

            $pathUploadFile = Yii::getAlias('@webroot')
                . "/" . Yii::$app->params['bodegaRadicados']
                . "/" . $anio
                . "/" . $GdTrdDependencias['codigoGdTrdDependencia']
                . "/"
            ;

            $pathUploadFile = str_replace('frontend/','api/',$pathUploadFile);
            // Verificar que la carpeta exista y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {
                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $transaction->rollBack();
                    $messageAlert .= Yii::$app->session->setFlash('error', 'El directorio no existe');
                }
            }

            foreach ($_FILES as $keyFile => $file) {
                if ($file['error'] === 0) {

                    $fileUpload = UploadedFile::getInstanceByName($keyFile);
                    $numeroDocumento = ($numeroDocumento == 0) ? $numeroDocumento + 2 : $numeroDocumento + 1;
                
                    $modelDoc = new RadiDocumentos;
                    $modelDoc->nombreRadiDocumento       = $modelFile->numeroRadiRadicado .'-'. $numeroDocumento .'_'. $keyFile .'.'. $fileUpload->extension;
                    $modelDoc->rutaRadiDocumento         = $pathUploadFile;
                    $modelDoc->extencionRadiDocumento    = $fileUpload->extension;
                    $modelDoc->idRadiRadicado            = $modelFile->idRadiRadicado;
                    $modelDoc->idGdTrdTipoDocumental     = $modelFile->idTrdTipoDocumental;
                    $modelDoc->descripcionRadiDocumento  = $keyFile;
                    $modelDoc->idUser                    = $idUserPqrs;
                    $modelDoc->numeroRadiDocumento       = $numeroDocumento;
                    $modelDoc->estadoRadiDocumento       = Yii::$app->params['statusTodoText']['Activo'];
                    $modelDoc->creacionRadiDocumento     = date('Y-m-d H:i:s');
                    $modelDoc->isPublicoRadiDocumento    = Yii::$app->params['statusTodoText']['Inactivo'];
                    $modelDoc->publicoPagina             = Yii::$app->params['statusTodoText']['Inactivo'];
                    $modelDoc->tamanoRadiDocumento       = '' . $fileUpload->size / 1000 . ' KB';
                    
                    if (!$modelDoc->save()) {
                        $transaction->rollBack();
                        $errors[] = $modelDoc->getErrors();
                        break;

                    } else {
                        $RadiDocumentos = new RadiDocumentos();
                        $attributeLabels = $RadiDocumentos->attributeLabels();

                        #Array de radiDocumento para insertar en el log de auditoria
                        $dataOld = '';
                        $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDoc->idRadiDocumento
                            . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDoc->nombreRadiDocumento
                            . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDoc->descripcionRadiDocumento
                            . ', Tipo Documental: ' . $modelDoc->idGdTrdTipoDocumental0->nombreTipoDocumental
                        ;

                        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachment']);
                        $observation =  Yii::$app->params['eventosLogText']['FileUpload'] . ' al radicado ' .  $modelFile->numeroRadiRadicado . ' por el usuario ciudadano ' . $modelCliente->cliente->nombreCliente;// texto para almacenar en el evento

                        /*** log Auditoria ***/
                        HelperLog::logAdd(
                            true,
                            $idUserPqrs, //Id user
                            "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                            Yii::$app->controller->route, //Modulo
                            $observation,
                            $dataOld,
                            $dataNew, //Data
                            array() //No validar estos campos
                        );

                        HelperLog::logAddFiling(
                            $idUserPqrs,
                            $modelFile->idTrdDepeUserTramitador,
                            $modelFile->idRadiRadicado, //Id radicado
                            $idTransacion->idCgTransaccionRadicado,
                            $observation, //observación
                            [],
                            array() //No validar estos campos
                        );
                    }

                    $uploadExecute = $fileUpload->saveAs($modelDoc->rutaRadiDocumento.$modelDoc->nombreRadiDocumento);
                    // $helperExtraerTexto = new HelperExtraerTexto($modelDoc->rutaRadiDocumento.$modelDoc->nombreRadiDocumento);

                    // $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                    //     $modelDoc->idRadiDocumento,
                    //     Yii::$app->params['tablaOcrDatos']['radiDocPrincipales']
                    // );

                    // if($helperOcrDatos['status'] != true){
                    //     Yii::$app->session->setFlash('error', $helperOcrDatos['message']);
                    // }

                    $pdfFiles[] = [
                        'nombreRadiDocumento' => $modelDoc->nombreRadiDocumento
                    ];

                    $pdfFilesDos[] = $keyFile;
                }
            }

            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id
            $filename = $modelFile->numeroRadiRadicado . '-1';

            $nombreClienteCompleto = explode(' ', $modelCliente->cliente->nombreCliente);
            if (count($nombreClienteCompleto) === 4) {
                $primerNombre = $nombreClienteCompleto[0];
                $segundoNombre = $nombreClienteCompleto[1];
                $primerApellido = $nombreClienteCompleto[2];
                $segundoApellido = $nombreClienteCompleto[3];
            } else {
                $primerNombre = $nombreClienteCompleto[0] ?? '';
                $segundoNombre = '';
                $primerApellido = $nombreClienteCompleto[1] ?? '';
                $segundoApellido = $nombreClienteCompleto[2] ?? '';
            }

            $pdfData = [
                'numeroRadiRadicado'   => $modelFile->numeroRadiRadicado,
                'creacionRadiRadicado' => date("Y-m-d"),
                'primerApellido'       => $primerApellido,
                'segundoApellido'      => $segundoApellido,
                'primerNombre'         => $primerNombre,
                'segundoNombre'        => $segundoNombre,
                'tipoIdentificación'   => $modelCliente->tipoIdentificacion->idTipoIdentificacion,
                'numeroIdentificacion' => str_split($modelCliente->cliente->numeroDocumentoCliente),
                'direccion'            => $modelCliente->cliente->direccionCliente,
                'telefono'             => str_split($modelCliente->cliente->telefonoCliente),
                'ciudad'               => $modelCliente->cliente->idNivelGeografico30->nomNivelGeografico3,
                'departamento'         => $modelCliente->cliente->idNivelGeografico20->nomNivelGeografico2,
                'email'                => $modelCliente->cliente->correoElectronicoCliente,
                'tipoEmpleador'        => $request['FormsFonprecon']['tipoEmpleador'],
                'cargo'                => $request['FormsFonprecon']['cargo'],
                'tipoSolicitud'        => (int)$request['RegistroPqrs']['tipoSolicitud'],
                'medioRespuesta'       => $request['FormsFonprecon']['medioRespuesta'],
                'pdfFiles'             => $pdfFilesDos
            ];

            # Generar pdf de la radicacion externa
            PdfController::generar_pdf('RadicacionEmail','radiRadicadoPqrsFonprecon', $filename, $pathUploadFile, $pdfData, $pdfFiles, $footer = 'footerClienteFonprecon');

            $pathinfo = filesize($pathUploadFile.''.$filename.'.pdf');

            # Agregar PDF a la tabla radiDocumentos
            $modelDocPrincipal = new RadiDocumentos;
            $modelDocPrincipal->idRadiRadicado         = $modelFile->idRadiRadicado;
            $modelDocPrincipal->idUser                 = $idUserPqrs;// ORFEO
            $modelDocPrincipal->nombreRadiDocumento    = $filename.'.pdf';
            $modelDocPrincipal->rutaRadiDocumento      = $pathUploadFile;
            $modelDocPrincipal->extencionRadiDocumento = 'pdf';
            $modelDocPrincipal->idGdTrdTipoDocumental  = $modelFile->idTrdTipoDocumental;
            $modelDocPrincipal->isPublicoRadiDocumento = Yii::$app->params['statusTodoText']['Inactivo'];
            $modelDocPrincipal->descripcionRadiDocumento = 'Anexo principal del documento';
            $modelDocPrincipal->numeroRadiDocumento    = 1;
            $modelDocPrincipal->tamanoRadiDocumento    = '' . $pathinfo / 1000 . ' KB';
            $modelDocPrincipal->publicoPagina = $modelDoc->publicoPagina ?? Yii::$app->params['statusTodoText']['Inactivo'];

            if (!$modelDocPrincipal->save()) {
                $transaction->rollBack();
                $errors[] = $modelDocPrincipal->getErrors();
            } else {
                # Envio de correo al usuario tramitador Pqrs
                $headMailText  = Yii::t('app', 'headMailRegisterPqrs',[
                    'numRadicado' => $numRadicado['numeroRadiRadicado']
                ]);
                $textBody  = Yii::t('app', 'textBodyRegisterPqrs', [
                    'numRadicado'  => $numRadicado['numeroRadiRadicado'],
                    'asunto'       => $modelFile->asuntoRadiRadicado,
                    'dias'         => $calculoRadicado['diasRestantes']
                ]);

                $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelFile->idRadiRadicado));
                $bodyMail = 'radicacion-html';
                $subject = 'subjectRegisterPqrs';

                # Agregar numero de anexos si existen al bodyText del correo
                if($numeroDocumento > 0){
                    $textBody  = $textBody.' '.Yii::t('app', 'addAnexoCommentPqrs',[
                        'numAnexos' => $numeroDocumento
                    ]);
                }

                $documentosExist[] = $pathUploadFile . $filename .'.pdf';

                $envioCorreo = CorreoController::envioAdjuntos($modelUser->email, $headMailText, $textBody, $bodyMail, $documentosExist, $dataBase64Params, $subject, '', false);
                # Fin de envio de correo al usuario tramitador Pqrs

                # Notificacion via correo electrónico al usuario logueado
                if($modelFile->autorizacionRadiRadicados == Yii::$app->params['seguimientoViaPQRS']['text']['CorreoElectrónico']) {
                    /***  Log Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        $idUserPqrs,
                        $modelFile->idTrdTipoDocumental,
                        Yii::$app->controller->route, //Modulo
                        "El ciudadano ".$modelCliente->cliente->nombreCliente." autorizó el envío de respuesta por correo electrónico.", //evento
                        '', // data old
                        '', //Data
                        [] //No validar estos campos
                    );
                    /*** Fin log Auditoria ***/

                    # Envio de correo al usuario registrador
                    $headMailText  = Yii::t('app', 'headMailPqrsRequest',[
                        'numRadicado' => $numRadicado['numeroRadiRadicado']
                    ]);
                    $textBody  = Yii::t('app', 'textBodyPqrsRequest', [
                        'numRadicado'  => $numRadicado['numeroRadiRadicado'],
                        'asunto'       => $modelFile->asuntoRadiRadicado
                    ]);

                    $bodyMail = 'radicacion-html';
                    $subject = 'subjectPqrsRequest';

                    $file = [];
                    $file['rutaRadiDocumento'] = $pathUploadFile;
                    $file ['nombreRadiDocumento'] = $filename.'.pdf';
                    $buttonDisplay = 'true';
                    $link =  Yii::$app->urlManager->createAbsoluteUrl(['site/login']);
                    $nameButtonLink = Yii::t('app', 'sendCitizenEnter');
                    $envioCorreo = CorreoController::sendEmail($notificarVia, $headMailText, $textBody, $bodyMail, [$file], $link, $subject, $nameButtonLink);
                    # Fin de envio de correo al usuario registrador
                }
            }

            $transaction->commit();

           # Validación de almacenamiento
            if(count($errors) >= 1){
                foreach($errors as $model) {
                    foreach($model as $key) {
                        foreach($key as $message){
                            Yii::$app->session->setFlash('error', $message);
                        }
                    }
                }
            }

            # Validación de errores
            if(isset($modelFile) && count($errors) == 0 ) {
                Yii::$app->session->setFlash('success', $modelcgFormulariosPqrs->nombreCgFormulariosPqrs.' Registrada con éxito, número de radicado: ' . $modelFile->numeroRadiRadicado);
                return $this->goHome();

            } elseif (isset($messageAlert)) {
                return $this->goHome();

            } else {
                Yii::$app->session->setFlash('error', 'Error al procesar solicitud '.$modelcgFormulariosPqrs->nombreCgFormulariosPqrs);
                return $this->goHome();
            }
        }

        if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {
            $listFormularioPqrs = ArrayHelper::map(
                CgFormulariosPqrs::find()
                    ->where(['idCgFormulariosPqrs' => Yii::$app->params['cgFormulariosPqrsIdPqrs']])
                    ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
        } else {
            $listFormularioPqrs = ArrayHelper::map(
                CgFormulariosPqrs::find()
                    ->where(['estadoCgFormulariosPqrs' => Yii::$app->params['statusTodoText']['Activo']])
                    ->all(), 'idCgFormulariosPqrs', 'nombreCgFormulariosPqrs');
        }

        $listFormularioPqrsDetalle = ArrayHelper::map(
            CgFormulariosPqrsDetalle::find()
            ->where(['estadoCgFormulariosPqrsDetalle' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['idCgFormulariosPqrs' => $tipoTramite])
            ->all(), 'idCgFormulariosPqrsDetalle', 'nombreCgFormulariosPqrsDetalle');

        if (isset($request) && isset($request['RegistroPqrs']['tipoSolicitud']) && $request['RegistroPqrs']['tipoSolicitud'] !== "") {
            $dataFormularioPqrsDetalle = CgFormulariosPqrsDetalle::find()
                ->where(['estadoCgFormulariosPqrsDetalle' => Yii::$app->params['statusTodoText']['Activo']])
                ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                ->one();
            if ($dataFormularioPqrsDetalle['datosSelectorPrestacionCgFormulariosPqrsDetalle'] !== "No" && $dataFormularioPqrsDetalle['datosSelectorPrestacionCgFormulariosPqrsDetalle'] !== "") {
                $datosSelectorPrestacionCgFormulariosPqrsDetalle = Yii::$app->params[$dataFormularioPqrsDetalle['datosSelectorPrestacionCgFormulariosPqrsDetalle']];
                $activarBeneficiarioCgFormulariosPqrsDetalle = $dataFormularioPqrsDetalle['activarBeneficiarioCgFormulariosPqrsDetalle'];
                $existenSelectoresAdicionales = true;
            }

            if ($dataFormularioPqrsDetalle['datosSelectorBeneficiarioCgFormulariosPqrsDetalle'] !== "No" && $dataFormularioPqrsDetalle['datosSelectorBeneficiarioCgFormulariosPqrsDetalle'] !== "") {
                $datosSelectorBeneficiarioCgFormulariosPqrsDetalle = Yii::$app->params[$dataFormularioPqrsDetalle['datosSelectorBeneficiarioCgFormulariosPqrsDetalle']];
                $existenSelectoresAdicionales = true;
            }

            if (isset($request) && isset($request['FormsFonprecon']['categoriaPrestacion'])) {
                if ($request['FormsFonprecon']['categoriaPrestacion'] === $activarBeneficiarioCgFormulariosPqrsDetalle) {
                    $activarSelectBeneficiario = true;
                } else {
                    $docsFormularioPqrsDetalle = CgFormulariosPqrsDetalleDocumentos::find()
                        ->where(['estadoCgFormulariosPqrsDetalleDocumentos' => Yii::$app->params['statusTodoText']['Activo']])
                        ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                        ->andWhere(['combiCgFormulariosPqrsDetalleDocumentos' => $request['FormsFonprecon']['categoriaPrestacion']])
                        ->all();

                    $mostrarDocs = true;
                    $activarGuardar = true;
                }
            }

            if (isset($request) && isset($request['FormsFonprecon']['calidadBeneficiario']) && $activarSelectBeneficiario) {
                $docsFormularioPqrsDetalle = CgFormulariosPqrsDetalleDocumentos::find()
                    ->where(['estadoCgFormulariosPqrsDetalleDocumentos' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                    ->andWhere(['combiCgFormulariosPqrsDetalleDocumentos' => $request['FormsFonprecon']['calidadBeneficiario']])
                    ->all();

                $mostrarDocs = true;
                $activarGuardar = true;
            }

            if (!$existenSelectoresAdicionales) {
                $docsFormularioPqrsDetalle = CgFormulariosPqrsDetalleDocumentos::find()
                    ->where(['estadoCgFormulariosPqrsDetalleDocumentos' => Yii::$app->params['statusTodoText']['Activo']])
                    ->andWhere(['idCgFormulariosPqrsDetalle' => $request['RegistroPqrs']['tipoSolicitud']])
                    ->all();

                $mostrarDocs = true;
                $activarGuardar = true;
            }
        }

        if ((isset($request) && isset($request['reloadForm']) && $request['reloadForm'] === "true") || (isset($request) && isset($request['RegistroPqrs']['tipoSolicitud']) && $request['RegistroPqrs']['tipoSolicitud'] === "")) {
            return $this->render('index', [
                'model_registro_pqrs'          => new RegistroPqrs,
                'list_formulario_pqrs'         => $listFormularioPqrs ?? [],
                'list_formulario_pqrs_detalle' => $listFormularioPqrsDetalle,
                'id_tipo_tramite_actual'       => $tipoTramite,
            ]);
        }

        return $this->render('index-forms-fonprecon', [
            'model_registro_pqrs'          => new RegistroPqrs,
            'list_formulario_pqrs'         => $listFormularioPqrs ?? [],
            'list_formulario_pqrs_detalle' => $listFormularioPqrsDetalle,
            'form_fonprecon'               => new FormsFonprecon,
            'list_medio_respuesta'         => Yii::$app->params['medioRespuesta'],
            'list_calidad_causante'        => Yii::$app->params['calidadCausante'],
            'list_tipo_empleador'          => Yii::$app->params['tipoEmpleador'],
            'list_categoria_prestacion'    => $datosSelectorPrestacionCgFormulariosPqrsDetalle,
            'list_calidad_beneficiario'    => $datosSelectorBeneficiarioCgFormulariosPqrsDetalle,
            'data_activar_beneficiario'    => $activarBeneficiarioCgFormulariosPqrsDetalle,
            'data_formulario_pqrs_detalle' => $dataFormularioPqrsDetalle ?? [],
            'docs_formulario_pqrs_detalle' => $docsFormularioPqrsDetalle ?? [],
            'exceptios_file_pqrs'          => Yii::$app->params['exceptiosFilePqrsFonprecon'],

            'id_tipo_tramite_actual'       => $request['RegistroPqrs']['tipoTramite'],
            'id_tipo_solicitud_actual'     => $request['RegistroPqrs']['tipoSolicitud'],
            'cargo_actual'                 => $request['FormsFonprecon']['cargo'] ?? '',
            'medio_respuesta_actual'       => $request['FormsFonprecon']['medioRespuesta'] ?? '',
            'calidad_causante_actual'      => $request['FormsFonprecon']['calidadCausante'] ?? '',
            'tipo_empleador_actual'        => $request['FormsFonprecon']['tipoEmpleador'] ?? '',
            'categoria_prestacion_actual'  => $request['FormsFonprecon']['categoriaPrestacion'] ?? '',
            'calidad_beneficiario_actual'  => $request['FormsFonprecon']['calidadBeneficiario'] ?? '',
            'activar_select_beneficiario'  => $activarSelectBeneficiario,
            'envio_por_selectores'         => $request['envioPorSelectores'] ?? false,
            'mostrar_docs'                 => $mostrarDocs,
            'activar_guardar'              => $activarGuardar
        ]);
    }

    public function actionSeguimientoVia() {

        $request = Yii::$app->request->post();

        $modelCliente = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);

        if($request['seguimientoVia'] == Yii::$app->params['seguimientoViaPQRS']['text']['CorreoElectrónico']){
            return  Yii::$app->user->identity['email'];
        }

        if($request['seguimientoVia'] == Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica']){
            $clientes = Clientes::findOne(['idCliente' => $modelCliente->idCliente]);
            return $clientes['direccionCliente'];
        }

        return 0;
    }
}
