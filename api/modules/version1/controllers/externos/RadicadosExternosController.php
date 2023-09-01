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

namespace api\modules\version1\controllers\externos;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;
use api\components\HelperExternos;
use api\components\HelperNotification;
use api\components\HelperRadicacion;
use api\components\HelperLog;
use api\components\HelperFiles;
use api\components\HelperEncryptAes;
use api\components\HelperIndiceElectronico;

/*** Modelos ***/
use api\models\CgTiposRadicados;
use api\models\CgTramites;
use api\models\GdTrdTiposDocumentales;
use api\models\CgMediosRecepcion;
use api\models\GdTrdDependencias;
use api\models\CgNumeroRadicado;
use common\models\User;
use api\models\UserDetalles;
use api\models\RadiRadicados;
use api\models\RadiRemitentes;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\Clientes;
use api\models\RadiDocumentos;
use api\models\CgTransaccionesRadicados;
use api\models\RadiRadicadosAsociados;
use api\models\GdExpedientesInclusion;
use api\modules\version1\controllers\radicacion\DocumentosController;

/*** Controladores ***/
use api\modules\version1\controllers\radicacion\RadicadosController;
use api\modules\version1\controllers\correo\CorreoController;

/**
 * radicados externos controller for the `version1` module
 */
class RadicadosExternosController extends Controller
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
                    'radicados-create' => ['POST'],
                    'tipos-radicados' => ['GET'],
                    'radicados-get' => ['GET'],
                    'tipos-tramites' => ['GET'],
                    'tipos-documentales' => ['GET'],
                    'medios-recepcion' => ['GET'],
                    'dependencias' => ['GET'],
                    'token-skina-scan' => ['POST'],
                ],
            ],
        ];
    }


    public function actionRadicadosCreate() {
        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $infoMailProcess = '';
        $errors = [];
        $saveValidate = true;
        $data = [];
        $nivelGeografico1 = [];
        $nivelGeografico2 = [];
        $nivelGeografico3 = [];
        $idAsociadosAlRadicado = [];
        $numAsociadosAlRadicado = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $request = Yii::$app->request->post('jsonData');
            // $request = json_decode($jsonData, true);

            $requestValidate = HelperExternos::validarJson($request, 'radicadosCreateExternos');
            if(count($requestValidate) > 0) {
                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => $requestValidate,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return $response;
            }

            $requestCamposValidatevsbd = HelperExternos::validarCampoVsbd($request, 'radicadosCreateExternosVsbd');
            if(count($requestCamposValidatevsbd['dataNull']) > 0) {
                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => $requestCamposValidatevsbd['dataNull'],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return $response;
            }

            /** Validación de usuario tramitador busca información */
            $userTramitador = UserDetalles::findOne(['documento' => $request['documentoIdentidadTramitador']]);

            /*** Si el idCreador es igual al idTramitador el radicano no se llevará a cabo ***/
            if($dataHelper['model']->userCgProveedorExterno0->id == $userTramitador->idUser ) {
                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => Yii::$app->params['errorProveedorExternoTextTramitadorCreadorSimilar'],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return $response;
            }

            /*** Valida si la dependencia que envian es la misma del usuario  ***/
            if( $userTramitador->user->gdTrdDependencia->codigoGdTrdDependencia !=  $request['codigoDependenciaTramitador'] ){
                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => Yii::$app->params['errorUserDependenciaExterno'],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return $response;
            }

            /*** Si existen los diasRestantes se toma si no el sistema validará de acuerdo al tipo documental ***/
            if(isset($request['diasRestantes'])) {
                $calcularfechaVencimiento = RadicadosController::calcularfechaVencimiento($request['idTipoDocumental'], $request['diasRestantes']);
            } else {
                $calcularfechaVencimiento = RadicadosController::calcularfechaVencimiento($request['idTipoDocumental'], '');
            }
            $request['fechaVencimientoRadiRadicados'] = date("Y-m-d H:i:s", strtotime($calcularfechaVencimiento['fechaFormatoDB']));

            /*** Se consulta el id del user para obtener su nombre ***/
            // $userTramitador = User::findOne(['id' => $request['idUserTramitador']]);


            $userDetallesCreador = UserDetalles::findOne(['idUser' => $dataHelper['model']->userCgProveedorExterno0->id]);

            /*** Se consulta la información de la configuración realizada para el cliente al que se le esta implementando ORFEO NG ***/
            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $dataHelper['model']->userCgProveedorExterno0->idGdTrdDependencia]);

            $transaction = Yii::$app->db->beginTransaction();

            /*** Número de radicado ***/
            $estructura = RadicadosController::generateNumberFiling($request['idTipoRadicado'], $modelCgNumeroRadicado, $modelDependencia);

            /*** Instancia del modelo para crear radicados ***/
            $modelRadicado = new RadiRadicados;

            /*** Campos calculados ***/
            $modelRadicado->numeroRadiRadicado            = $estructura['numeroRadiRadicado'];
            $modelRadicado->fechaVencimientoRadiRadicados = $request['fechaVencimientoRadiRadicados'];
            /*** Campos automaticos ***/
            $modelRadicado->user_idCreador                = $dataHelper['model']->userCgProveedorExterno0->id;
            $modelRadicado->idTrdDepeUserCreador          = $dataHelper['model']->userCgProveedorExterno0->idGdTrdDependencia;
            /*** Campos obligatorios ***/
            $modelRadicado->asuntoRadiRadicado            = $request['asuntoRadicado'];
            $modelRadicado->idTrdTipoDocumental           = $request['idTipoDocumental'];
            $modelRadicado->idTrdDepeUserTramitador       = $userTramitador->user->idGdTrdDependencia;
            $modelRadicado->user_idTramitador             = $userTramitador->idUser;            
            $modelRadicado->idCgTipoRadicado              = $request['idTipoRadicado'];
            $modelRadicado->idCgMedioRecepcion            = $request['idMedioRecepcion'];
            /*** Campos opcionales ***/
            if (isset($request['prioridadRadicado']) && $request['prioridadRadicado'] == 1) { $prioridad = 1; } else { $prioridad = 0; }
            $modelRadicado->PrioridadRadiRadicados        = $prioridad;
            $modelRadicado->foliosRadiRadicado            = (string) $request['foliosRadicado'];

            if($modelRadicado->save()) {

                /*** Info remitente ***/
                $modelRemitentes = new RadiRemitentes();
                $modelRemitentes->idRadiRadicado = $modelRadicado->idRadiRadicado;
                $modelRemitentes->idRadiPersona  = $requestCamposValidatevsbd['models']['idCliente']['idCliente'];
                $modelRemitentes->idTipoPersona  = $requestCamposValidatevsbd['models']['idCliente']['idTipoPersona'];
                if(!$modelRemitentes->save()) {
                    $saveValidate = false;
                    $errors[] = $modelRemitentes->getErrors();
                }

                // Guarda la actualización del consecutivo del numero de radicado
                $estructura['modelCgTiposRadicados']->save();

                /***  Notificacion  ***/
                HelperNotification::addNotification(
                    $dataHelper['model']->userCgProveedorExterno0->id, //Id user creador
                    $userTramitador->idUser, // Id user notificado
                    Yii::t('app','messageNotification')['createOrReassingFile'].$modelRadicado->numeroRadiRadicado, //Notificacion
                    Yii::$app->params['routesFront']['viewRadicado'], // url
                    $modelRadicado->idRadiRadicado // id radicado
                );
                /***  Fin Notificacion  ***/

                if ($request['idTipoRadicado'] == Yii::$app->params['idCgTipoRadicado']['radiEntrada']) {

                    $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelRadicado->idRadiRadicado));

                    // Envia la notificación de correo electronico al usuario de tramitar
                    $headMailText = Yii::t('app', 'headMailTextRadicado', [
                        'numRadicado' => $modelRadicado->numeroRadiRadicado,
                    ]);

                    $textBody = Yii::t('app', 'textBodyAsignacionRadicado', [
                        'numRadicado' => $modelRadicado->numeroRadiRadicado,
                        'asunto' => $modelRadicado->asuntoRadiRadicado,
                        'user' => $userDetallesCreador->nombreUserDetalles . ' ' . $userDetallesCreador->apellidoUserDetalles,
                        'nameDependencia' => $modelDependencia->nombreGdTrdDependencia,
                        'dias' => $calcularfechaVencimiento['diasRestantes'],
                    ]);

                    $bodyMail = 'radicacion-html';
                    $envioCorreo = CorreoController::radicacion($userTramitador->user->email, $headMailText, $textBody, $bodyMail, $dataBase64Params);
                }

            } else {
                $saveValidate = false;
                $errors[] = $modelRadicado->getErrors();
            }

            if($saveValidate) {

                $modelNivelGeografico1 = NivelGeografico1::find()->all();
                foreach ($modelNivelGeografico1 as $key => $nivel1) {
                    $nivelGeografico1[$nivel1->nivelGeografico1] = $nivel1->nomNivelGeografico1;
                }

                $modelNivelGeografico2 = NivelGeografico2::find()->all();
                foreach ($modelNivelGeografico2 as $key => $nivel2) {
                    $nivelGeografico2[$nivel2->nivelGeografico2] = $nivel2->nomNivelGeografico2;
                }

                $modelNivelGeografico3 = NivelGeografico3::find()->all();
                foreach ($modelNivelGeografico3 as $key => $nivel3) {
                    $nivelGeografico3[$nivel3->nivelGeografico3] = $nivel3->nomNivelGeografico3;
                }

                if($modelRadicado->PrioridadRadiRadicados == 1) {
                    $prioridadRadiData = "Alta";
                } else {
                    $prioridadRadiData = "Baja";
                }

                $data[] = array(
                    "Número radicado"               => $modelRadicado->numeroRadiRadicado,
                    "idCliente"                     => $requestCamposValidatevsbd['models']['idCliente']['idCliente'],
                    "Tipo de persona"               => Yii::$app->params['tipoPersonaNumber'][$requestCamposValidatevsbd['models']['idCliente']['idTipoPersona']],
                    "idTipoPersona"                 => $requestCamposValidatevsbd['models']['idCliente']['idTipoPersona'],
                    "Nombre"                        => $requestCamposValidatevsbd['models']['idCliente']['nombreCliente'],
                    "Documento Identificación"      => $requestCamposValidatevsbd['models']['idCliente']['numeroDocumentoCliente'],
                    "Dirección de Correspondencia"  => $requestCamposValidatevsbd['models']['idCliente']['direccionCliente'],
                    "Municipio"                     => $nivelGeografico3[$requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico3']],
                    "idNivelGeografico3"            => $requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico3'],
                    "Departamento"                  => $nivelGeografico2[$requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico2']],
                    "idNivelGeografico2"            => $requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico2'],
                    "País"                          => $nivelGeografico1[$requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico1']],
                    "idNivelGeografico1"            => $requestCamposValidatevsbd['models']['idCliente']['idNivelGeografico1'],
                    "Correo Electrónico"            => $requestCamposValidatevsbd['models']['idCliente']['correoElectronicoCliente'],
                    "Teléfono de Contacto"          => $requestCamposValidatevsbd['models']['idCliente']['telefonoCliente'],
                    "Tipo Documental"               => $requestCamposValidatevsbd['models']['idTipoDocumental']['nombreTipoDocumental'],
                    "idTipoDocumental"              => $requestCamposValidatevsbd['models']['idTipoDocumental']['idGdTrdTipoDocumental'],
                    "Tipo Radicado"                 => $requestCamposValidatevsbd['models']['idTipoRadicado']['nombreCgTipoRadicado'],
                    "idTipoRadicado"                => $requestCamposValidatevsbd['models']['idTipoRadicado']['idCgTipoRadicado'],
                    "Tipo de trámite"               => $requestCamposValidatevsbd['models']['idTramite']['nombreCgTramite'],
                    "idTramite"                     => $requestCamposValidatevsbd['models']['idTramite']['idCgTramite'],
                    "Prioridad"                     => $prioridadRadiData,
                    "Medio Recepción"               => $requestCamposValidatevsbd['models']['idMedioRecepcion']['nombreCgMedioRecepcion'],
                    "idMedioRecepcion"              => $requestCamposValidatevsbd['models']['idMedioRecepcion']['idCgMedioRecepcion'],
                    "Asunto"                        => $modelRadicado->asuntoRadiRadicado,
                    "Fecha de Vencimiento"          => $modelRadicado->fechaVencimientoRadiRadicados,
                    "Dependencia Tramitadora"       => $modelRadicado->userIdTramitador->gdTrdDependencia->nombreGdTrdDependencia,
                    "idDependenciaUserTramitador"   => $modelRadicado->userIdTramitador->gdTrdDependencia->idGdTrdDependencia,
                    "Usuario Tramitador"            => $modelRadicado->userIdTramitador->userDetalles->nombreUserDetalles ." ". $modelRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                    "documentoIdentidadTramitador"  => $userTramitador->documento,
                    "Folios"                        => $modelRadicado->foliosRadiRadicado,
                    "Cantidad de envíos por correo" => 0
                );

                $transaction->commit();

                if( array_key_exists( 'radicadoAsociado', $request ) ){
                    if( is_array($request['radicadoAsociado']) ){
                        // Busca los radicados para asociar
                        $modelAsociar = RadiRadicados::find()->where(['IN', 'numeroRadiRadicado', $request['radicadoAsociado'] ])->all();
                        foreach( $modelAsociar as $asociar ){
                            // Asigna los ids asociados al radicado
                            $modelRadiAsociado = new RadiRadicadosAsociados();
                            $idAsociadosAlRadicado[] = $asociar['idRadiRadicado'];
                            $numAsociadosAlRadicado[] = $asociar['numeroRadiRadicado'];
                            $modelRadiAsociado->idRadiAsociado = $asociar['idRadiRadicado'];
                            $modelRadiAsociado->idRadiCreado = $modelRadicado->idRadiRadicado;
                            $modelRadiAsociado->save();
                        }
     
                    }
                }

                $dataRadicados = '';
                $dataRadicados = 'Id Radicado: '.$modelRadicado->idRadiRadicado;
                $dataRadicados .= ', Número Radicado: '.$modelRadicado->numeroRadiRadicado;
                $dataRadicados .= ', Asunto Radicado: '.$modelRadicado->asuntoRadiRadicado;
                $dataRadicados .= ', Tipo Radicación: '.$modelRadicado->cgTipoRadicado->nombreCgTipoRadicado;
                $dataRadicados .= ', Tipo Documental: '.$modelRadicado->trdTipoDocumental->nombreTipoDocumental;
                $dataRadicados .= ', Prioridad: '.$prioridadRadiData;
                $dataRadicados .= ', Remitente/Destinatario: '.$requestCamposValidatevsbd['models']['idCliente']['nombreCliente'];
                $dataRadicados .= ', Usuario Tramitador: '.$modelRadicado->userIdTramitador->userDetalles->nombreUserDetalles ." ". $modelRadicado->userIdTramitador->userDetalles->apellidoUserDetalles;
                $dataRadicados .= ', Usuario Creador: '.$userDetallesCreador->nombreUserDetalles . ' ' . $userDetallesCreador->apellidoUserDetalles;
                $dataRadicados .= ', Fecha creación: '.$modelRadicado->creacionRadiRadicado;

                if(isset($idAsociadosAlRadicado)){
                    if(count($idAsociadosAlRadicado) > 0 ){
                        $dataRadicados .= ', Ids Radicados Asociados: '.implode(', ', $idAsociadosAlRadicado);
                        $dataRadicados .= ', Radicados Asociados: '.implode(', ', $numAsociadosAlRadicado);
                    }
                }

                // Observación del Log
                $observation = Yii::$app->params['eventosLogText']['crear'] . ', en la tabla radicados';
                // Transaccion de crear radicado
                $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'add']);

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    true,
                    $userDetallesCreador->idUser, //Id user
                    $userDetallesCreador->user->username, //username
                    Yii::$app->controller->route, //Modulo
                    $observation, //texto para almacenar en el evento
                    '',
                    $dataRadicados, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $observationFiling = Yii::$app->params['eventosLogTextRadicado']['New'];

                /***    Log de Radicados  ***/
                HelperLog::logAddFiling(
                    $userDetallesCreador->idUser, //Id user
                    $userDetallesCreador->user->username, //username
                    $modelRadicado->idRadiRadicado, //Id radicado
                    $idTransacion->idCgTransaccionRadicado,
                    $observationFiling, //observación
                    $modelRadicado,
                    array() //No validar estos campos
                );

                /***  Notificacion  ***/
                HelperNotification::addNotification(
                    $userDetallesCreador->idUser, //Id user creador
                    $userTramitador->idUser, // Id user notificado
                    Yii::t('app','messageNotification')['createOrReassingFile'].$modelRadicado->numeroRadiRadicado, //Notificacion
                    Yii::$app->params['routesFront']['viewRadicado'], // url
                    $modelRadicado->idRadiRadicado // id radicado
                );
                /***  Fin Notificacion  ***/

                $response = [
                    'message' => Yii::$app->params['successProveedorExterno'],
                    'data' => $data,
                    'status' => 200,
                ];

            } else {

                $transaction->rollBack();

                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => $errors,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
            }

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

        }

        return $response;
    }

    public function actionRadicadosGet($id) {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];
        $nivelGeografico1 = [];
        $nivelGeografico2 = [];
        $nivelGeografico3 = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $modelRadicado = RadiRadicados::find()->where(['numeroRadiRadicado' => $id])->one();
            $modelRemitentes = RadiRemitentes::find()->where(['idRadiRadicado' => $modelRadicado->idRadiRadicado])->one();
            $modelCliente = Clientes::find()->where(['idCliente' => $modelRemitentes->idRadiPersona])->one();

            $modelNivelGeografico1 = NivelGeografico1::find()->all();
            foreach ($modelNivelGeografico1 as $key => $nivel1) {
                $nivelGeografico1[$nivel1->nivelGeografico1] = $nivel1->nomNivelGeografico1;
            }

            $modelNivelGeografico2 = NivelGeografico2::find()->all();
            foreach ($modelNivelGeografico2 as $key => $nivel2) {
                $nivelGeografico2[$nivel2->nivelGeografico2] = $nivel2->nomNivelGeografico2;
            }

            $modelNivelGeografico3 = NivelGeografico3::find()->all();
            foreach ($modelNivelGeografico3 as $key => $nivel3) {
                $nivelGeografico3[$nivel3->nivelGeografico3] = $nivel3->nomNivelGeografico3;
            }

            if($modelRadicado->PrioridadRadiRadicados == 1) {
                $prioridadRadiData = "Alta";
            } else {
                $prioridadRadiData = "Baja";
            }

            $data[] = array(
                "Número radicado"               => $modelRadicado->numeroRadiRadicado,
                "idCliente"                     => $modelCliente->idCliente,
                "Tipo de persona"               => Yii::$app->params['tipoPersonaNumber'][$modelCliente->idTipoPersona],
                "idTipoPersona"                 => $modelCliente->idTipoPersona,
                "Nombre"                        => $modelCliente->nombreCliente,
                "Documento Identificación"      => $modelCliente->numeroDocumentoCliente,
                "Dirección de Correspondencia"  => $modelCliente->direccionCliente,
                "Municipio"                     => $nivelGeografico3[$modelCliente->idNivelGeografico3],
                "idNivelGeografico3"            => $modelCliente->idNivelGeografico3,
                "Departamento"                  => $nivelGeografico2[$modelCliente->idNivelGeografico2],
                "idNivelGeografico2"            => $modelCliente->idNivelGeografico2,
                "País"                          => $nivelGeografico1[$modelCliente->idNivelGeografico1],
                "idNivelGeografico1"            => $modelCliente->idNivelGeografico1,
                "Correo Electrónico"            => $modelCliente->correoElectronicoCliente,
                "Teléfono de Contacto"          => $modelCliente->telefonoCliente,
                "Tipo Documental"               => $modelRadicado->trdTipoDocumental->nombreTipoDocumental,
                "idTipoDocumental"              => $modelRadicado->trdTipoDocumental->idGdTrdTipoDocumental,
                "Tipo Radicado"                 => $modelRadicado->cgTipoRadicado->nombreCgTipoRadicado,
                "idTipoRadicado"                => $modelRadicado->cgTipoRadicado->idCgTipoRadicado,
                "Tipo de trámite"               => $modelRadicado->tramites->nombreCgTramite,
                "idTramite"                     => $modelRadicado->tramites->idCgTramite,
                "Prioridad"                     => $prioridadRadiData,
                "Medio Recepción"               => $modelRadicado->cgMedioRecepcion->nombreCgMedioRecepcion,
                "idMedioRecepcion"              => $modelRadicado->cgMedioRecepcion->idCgMedioRecepcion,
                "Asunto"                        => $modelRadicado->asuntoRadiRadicado,
                "Fecha de Vencimiento"          => $modelRadicado->fechaVencimientoRadiRadicados,
                "Dependencia Tramitadora"       => $modelRadicado->userIdTramitador->gdTrdDependencia->nombreGdTrdDependencia,
                "idDependenciaUserTramitador"   => $modelRadicado->userIdTramitador->gdTrdDependencia->idGdTrdDependencia,
                "Usuario Tramitador"            => $modelRadicado->userIdTramitador->userDetalles->nombreUserDetalles ." ". $modelRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                "documentoIdentidadTramitador"  => $modelRadicado->userIdTramitador->userDetalles->documento,
                "Folios"                        => $modelRadicado->foliosRadiRadicado,
                "Cantidad de envíos por correo" => $modelRadicado->cantidadCorreosRadicado
            );

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }

    }

    public function actionTiposRadicados() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $model = CgTiposRadicados::find()->where(['estadoCgTipoRadicado' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($model) {

                foreach ($model as $key => $tipoRadi) {
                    $data[] = array(
                        "idTipoRadicado"     => $tipoRadi->idCgTipoRadicado,
                        "nombreTipoRadicado" => $tipoRadi->nombreCgTipoRadicado,
                        "codigoTipoRadicado" => $tipoRadi->codigoCgTipoRadicado,
                    );
                }
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionTiposTramites() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $model = CgTramites::find()->where(['estadoCgTramite' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($model) {

                foreach ($model as $key => $val) {
                    $data[] = array(
                        "idTramite"     => $val->idCgTramite,
                        "nombreTramite" => $val->nombreCgTramite
                    );
                }
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionTiposDocumentales() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $model = GdTrdTiposDocumentales::find()->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($model) {

                foreach ($model as $key => $val) {
                    $data[] = array(
                        "idTipoDocumental"     => $val->idGdTrdTipoDocumental,
                        "nombreTipoDocumental"      => $val->nombreTipoDocumental,
                        "diasTramiteTipoDocumental" => $val->diasTramiteTipoDocumental,
                    );
                }
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionMediosRecepcion() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $model = CgMediosRecepcion::find()->where(['estadoCgMedioRecepcion' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($model) {

                foreach ($model as $key => $val) {
                    $data[] = array(
                        "idMedioRecepcion"     => $val->idCgMedioRecepcion,
                        "nombreMedioRecepcion" => $val->nombreCgMedioRecepcion
                    );
                }
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionDependencias() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $model = GdTrdDependencias::find()->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($model) {

                foreach ($model as $key => $val) {
                    $data[] = array(
                        "idTrdDependencia"     => $val->idGdTrdDependencia,
                        "nombreTrdDependencia" => $val->nombreGdTrdDependencia,
                        "codigoTrdDependencia" => $val->codigoGdTrdDependencia
                    );
                }
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionTokenSkinaScan(){
        $dataHelper = HelperExternos::tokenSkinaScan();

        if(!isset($dataHelper['model']['tokenCgProveedorExterno'])){
            $response = [
                'message' => Yii::$app->params['errorProveedorExterno'],
                'data' => ['error' => 'No existe la conexión con skinascan'],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];

            return HelperEncryptAes::encrypt($response, true);
        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => $dataHelper['model']['tokenCgProveedorExterno'],
                'data' => [],
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /** 
     * Se cargan los documentos al sistema ya sea como anexo para el caso de skinascan e imagen principal para el caso del documento firmado 
     * el request deberia tener @idRadicado @tipoTabla
     **/
    public function actionLoadDocumentRadicado() 
    {
        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $request = json_decode(Yii::$app->request->post('jsonData'));

            if(!isset($request->idRadicado) && !isset($request->tipoArchivo)){
                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => ['error' => 'Es obligatorio el id del radicado y el tipo de archivo'],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

                return $response;
            }
         
            ini_set('memory_limit', '3073741824');
            ini_set('max_execution_time', 900);

            /** Validar si cargo un archivo subido **/
            $fileUpload = UploadedFile::getInstanceByName('fileUpload'); //Archivo recibido

            // return $fileUpload;
            // die();

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
                if ($request->tipoArchivo == 2 or $request->tipoArchivo == 1) {

                    $descripcion = 'Carga del documento principal';
                    $isPublicoRadiDocumento = Yii::$app->params['SiNoText']['Si'];

                    if (Yii::$app->user->identity != null) {
                        # Consulta del usuario logueado.
                        $userLogin = UserDetalles::find()->select(['nombreUserDetalles', 'apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                        $idUser = Yii::$app->user->identity->id;
                    }

                    $arrayTypeRadiOrConsecutive = [];

                    $anio = date('Y');
                    $idRadicado = $request->idRadicado;

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
                }else{
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        //'data' => ['error' => [Yii::t('app','emptyJsonSend')]],
                        'data' => ['error' => [$request]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
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
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationText'],
                'data' => Yii::$app->params['statusErrorAuthorizationText'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }
}
