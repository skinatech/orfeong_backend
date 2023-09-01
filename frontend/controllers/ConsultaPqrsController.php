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
use api\components\HelperExtraerTexto;

use api\models\CgClasificacionPqrs;
use api\models\CgTransaccionesRadicados;
use api\models\Clientes;
use api\models\ClientesCiudadanosDetalles;
use api\models\GdTrdDependencias;
use api\models\GdTrdTiposDocumentales;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiLogRadicados;
use api\models\RadiRadicadoAnulado;

use api\models\RadiRadicados;
use frontend\models\RadiRadicadosForm;
use api\models\RadiRemitentes;
use api\models\User;
use api\models\UserDetalles;
use api\models\CgGeneral;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use api\modules\version1\controllers\correo\CorreoController;
use Yii;
use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\BadRequestHttpException;

use yii\helpers\ArrayHelper;
use yii\base\InvalidArgumentException;

use yii\filters\VerbFilter;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\data\Pagination;

use barcode\barcode\BarcodeGenerator;
use frontend\models\Anexos;
use frontend\models\filterOperation;
use kartik\mpdf\Pdf;
use PHPUnit\Util\Json;
use yii\helpers\FileHelper;
use yii\helpers\Json as HelpersJson;

class ConsultaPqrsController extends \yii\web\Controller {

    public function actionIndex() {

        /***
         * Apenas se ingrese a la sección de "Consultar PQRSD" 
         * 
         * Se debe mostrar todas los radicados que ha registrado el ciudadano estos radicados deben mostrar la siguiente información: 
         * 
         * Tipo documental, 
         * Tipo Clasificación, 
         * el número de radicado, 
         * el asunto que ingreso, 
         * el estado del radicado, 
         * adicional se debe agregar 4 opciones al radicado 
         * 
         *   - (Trazabilidad (consulta), 
         *   - Respuestas (consulta),     
         *   - Anexos (ciudadano y funcionario), 
         *   - Comentarios (ciudadano y funcionario), 
         *   - Desistimiento (ciudadano)). 
         */

        /**
         * Nueva solicitud:
         * En la consulta de pQRS se están mostrando los radicados que se generan automáticamente cuando se dan respuesta a una PQRS, en esta parte solo se deberian mostrar los radicados de PQRS.
         */

        # Validacion usuario logueado
        if (Yii::$app->user->isGuest) {
            Yii::$app->session->setFlash('error', 'Debes iniciar sesión para Consultar PQRS');
            return $this->goHome();      
        }       

        $request = Yii::$app->request->get();
        $errors = [];

        //Lista de usuarios
        $dataList = []; $historico = [];  $respuestas = [];  $documentos = [];

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = []; 
        $dataSender = '';

        if (is_array($request) && isset($request['filterOperation'])) {

            if($request['filterOperation']['numeroRadiRadicado'] != '' or $request['filterOperation']['asuntoRadiRadicado'] != '' or $request['filterOperation']['idGdTrdTipoDocumental'] != ''){

                foreach ($request['filterOperation'] as $key => $info) {
                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } elseif($key == 'idRadiPersona'){
                        if( isset($info) && !empty($info) ){
                            $dataWhere[$key] = $info;
                            $dataSender = $info;
                        }                       
                    } else {
                        if( isset($info) && $info !== null && trim($info) != '' ){
                            $dataWhere[$key] = $info;
                        }
                    }
                }

                # Nivel de busqueda del logueado
                $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;
                
                # Cliente id
                $idCliente = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);

                # Relacionamiento de los radicados
                $relacionRadicados = RadiRadicados::find();
                    
                    //->innerJoin('gdTrdTiposDocumentales', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = radiRadicados.idTrdTipoDocumental')
                    $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'radiRadicados' => 'idTrdTipoDocumental']);
                
                    // ->innerJoin('radiRemitentes', 'radiRemitentes.idRadiRadicado = radiRadicados.idRadiRadicado')
                    $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRemitentes', ['radiRemitentes' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);

                $relacionRadicados = $relacionRadicados->where(['radiRemitentes.idRadiPersona' => $idCliente['idCliente']]);
                $relacionRadicados->andWhere(['radiRadicados.idCgTipoRadicado' => Yii::$app->params['idCgTipoRadicado']['radiPqrs']]);

                //Se reitera $dataWhere para solo obtener los campos con datos
                foreach ($dataWhere as $field => $value) {

                    switch ($field) {
                        case 'idGdTrdTipoDocumental':
                            $relacionRadicados->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                        break;
            
                        case 'fechaInicial':
                            $relacionRadicados->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                        break;
                        case 'fechaFinal':
                            $relacionRadicados->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                        break;
                        default:
                            $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                        break;
                    }
                }
                # Orden descendente para ver los últimos registros creados
                $relacionRadicados->orderBy([ 
                    'radiRadicados.PrioridadRadiRadicados' => SORT_DESC, 
                    'radiRadicados.idRadiRadicado' => SORT_DESC
                ]); 

                # Paginacion 
                $countQuery = clone $relacionRadicados;
                $pages = new Pagination(['totalCount' => $countQuery->count(), 'pageSize' => 4]);
                
                # Limite de la consulta
                $relacionRadicados->offset($pages->offset)
                ->limit($pages->limit)->all();
            
                //->limit(Yii::$app->params['limitRecords']);

                $modelRelation = $relacionRadicados->all();

                /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
                // $modelRelation = array_reverse($modelRelation);
                
                foreach ($modelRelation as $infoRelation) {

                    /** Consultar si al radicado se le puede realizar una solicitud de anulacion */
                    $radiRadicadoAnulado = RadiRadicadoAnulado::find()->select(['idRadiRadicadoAnulado'])
                        ->where(['idRadicado' => $infoRelation->idRadiRadicado])
                        ->andWhere(['or',
                        ['idEstado' => Yii::$app->params['statusAnnulationText']['SolicitudAnulacion']],
                        ['idEstado' => Yii::$app->params['statusAnnulationText']['AceptacionAnulacion']]
                        ])->limit(1)
                    ->one();

                    if ($radiRadicadoAnulado == null) {
                        $canSolicitudAnulacion = true;
                    } else {
                        $canSolicitudAnulacion = false;
                    }
                    /** Fin Consultar si al radicado se le puede realizar una solicitud de anulacion */            

                    $remitentes = RadiRemitentes::findOne(['idRadiRadicado' => $infoRelation->idRadiRadicado]);    

                    if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);
                    
                        $senderName = $modelRemitente->nombreCliente;
                        $senderAddress = $modelRemitente->direccionCliente;
                        $senderDocument = $modelRemitente->numeroDocumentoCliente;

                    } else {
                        $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
                        $getDependency = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRemitente->idGdTrdDependencia]);
                    
                        $senderName = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                        $senderAddress = $getDependency->nombreGdTrdDependencia;
                        $senderDocument = $modelRemitente->userDetalles->documento;                
                    }            

                    //Se obtiene la traza del estado actual del radicado
                    $modelLogRadicados = RadiLogRadicados::find()
                        ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                        ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                    ->one();

                    $transaccion = '';
                    if(!empty($modelLogRadicados)){
                        $transaccion = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
                    }

                    # Buscar Imagen principal del radicado
                    $radiImgPrincipal = RadiDocumentosPrincipales::find()
                        ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado,'nombreRadiDocumentoPrincipal' =>  $infoRelation->numeroRadiRadicado])
                    ->one();
                    
                    if($radiImgPrincipal){
                        $idradiDocumentoPrincipal = $radiImgPrincipal['idradiDocumentoPrincipal'];
                    }else{
                        $idradiDocumentoPrincipal = '';
                    }

                    $dataList[] = array(
                        'id'                    => $infoRelation->idRadiRadicado,
                        'idEncrypt'             => str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
                        'imgPricipal'           => $idradiDocumentoPrincipal,
                        'tipoRadicado'          => $infoRelation->cgTipoRadicado->nombreCgTipoRadicado,
                        'numeroRadiRadicado'    => $infoRelation->numeroRadiRadicado,
                        'creacionRadiRadicado'  => $infoRelation->creacionRadiRadicado,
                        'asuntoRadiRadicado'    => $infoRelation->asuntoRadiRadicado,
                        'nombreCliente'         => $senderName,
                        'direccionCliente'      => $senderAddress,
                        'identificacionCliente' => $senderDocument,
                        'nombreTipoDocumental'  => $infoRelation->trdTipoDocumental->nombreTipoDocumental,
                        
                        'fechaVencimientoRadiRadicados' => $infoRelation->fechaVencimientoRadiRadicados,
                        'usuarioTramitador'     => $infoRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                        'dependenciaTramitador' => $infoRelation->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                        'statusText'            => Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoRadiRadicado],   // Yii::t('app', $transaccion) . ' - ' .               
                        'status'                => $infoRelation->estadoRadiRadicado,
                    );

                    # Trazabilidad   
                    $historicoRadicados = RadiLogRadicados::find()
                        ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                        ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                    ->all();

                    foreach($historicoRadicados as $historicoRadicado){
                        $historico[$infoRelation->idRadiRadicado][] = array(
                            'idRadiRadicado' => $historicoRadicado->idRadiRadicado,
                            'iconTrans'     => $historicoRadicado->transaccion->iconCgTransaccionRadicado,
                            'transaccion'   => $historicoRadicado->transaccion->descripcionCgTransaccionRadicado,  //acción
                            'fecha'         => $historicoRadicado->fechaRadiLogRadicado,                    
                            'usuario'       => $historicoRadicado->user->userDetalles->nombreUserDetalles.' '.$historicoRadicado->user->userDetalles->apellidoUserDetalles,
                            'dependencia'   => $historicoRadicado->dependencia->nombreGdTrdDependencia,   
                            'observacion'   => $historicoRadicado->observacionRadiLogRadicado,
                        );
                    }

                    # Respuestas / Documentos principales
                    $radiDocPrincipales = RadiDocumentosPrincipales::find()
                        ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado, 
                                'extensionRadiDocumentoPrincipal' => 'pdf'
                        ])
                        ->andWhere(['or',
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']],
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['FirmadoFisicamente']],
                                ['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente']]
                        ])
                        ->orderBy(['creacionRadiDocumentoPrincipal' => SORT_DESC])
                    ->all();

                    foreach($radiDocPrincipales as $docPrincipal){
                        $respuestas[$infoRelation->idRadiRadicado][] = array(
                            'id'             => $docPrincipal->idradiDocumentoPrincipal,
                            'idRadiRadicado' => $docPrincipal->idRadiRadicado,
                            'usuario'        => $docPrincipal->user->userDetalles->nombreUserDetalles.' '.$docPrincipal->user->userDetalles->apellidoUserDetalles,
                            'nombre'         => $docPrincipal->nombreRadiDocumentoPrincipal,  
                            'fecha'          => date("d-m-Y", strtotime($docPrincipal->creacionRadiDocumentoPrincipal)),                  
                            'imgPrincipal'   => $docPrincipal->imagenPrincipalRadiDocumento,   
                            'icon'           => $docPrincipal->extensionRadiDocumentoPrincipal,
                        );
                    }

                    # Documentos / Anexos    
                    $radiDocumentos = RadiDocumentos::find()
                        ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                        ->orderBy(['creacionRadiDocumento' => SORT_DESC])
                    ->all();

                    foreach($radiDocumentos as $key => $radiDocumento){

                        $descripcion = (string) $radiDocumento['descripcionRadiDocumento'];

                        $documentos[$infoRelation->idRadiRadicado][] = [
                            'id'            => $radiDocumento->idRadiDocumento,
                            'icon'          => $radiDocumento->extencionRadiDocumento,
                            'nombre'        => $radiDocumento->nombreRadiDocumento,
                            'numDocumento'  => $radiDocumento->numeroRadiDocumento,
                            'descripcion'   => (strlen($descripcion) > 100) ? substr($descripcion, 0, 100) . '...' : $descripcion,
                            'descripcionTitle' => $radiDocumento->descripcionRadiDocumento,
                            'tipodocumento' => $radiDocumento->idGdTrdTipoDocumental0->nombreTipoDocumental,
                            'usuario'       => $radiDocumento->idUser0->nombreUserDetalles.' '.$radiDocumento->idUser0->apellidoUserDetalles,
                            'estado'        => $radiDocumento->estadoRadiDocumento,
                            'fecha'         => date("d-m-Y", strtotime($radiDocumento->creacionRadiDocumento)),
                            'isPdf'         => (strtolower($radiDocumento->extencionRadiDocumento) == 'pdf') ? true : false,
                            'isPublicoRadiDocumento' => Yii::$app->params['SiNoNumber'][$radiDocumento->isPublicoRadiDocumento],
                        ];   
                    }
                }

            }else{
                $pages = 0;
            }

        }
        else{
            $pages = 0;
        }

        /* ======================= FILTROS ======================= */
        
        $gdTrdTiposDocumentales =  GdTrdTiposDocumentales::find();
            //->innerJoin('cgTipoRadicadoDocumental', 'cgTipoRadicadoDocumental.idGdTrdTipoDocumental = gdTrdTiposDocumentales.idGdTrdTipoDocumental')
            $gdTrdTiposDocumentales = HelperQueryDb::getQuery('innerJoin', $gdTrdTiposDocumentales, 'cgTipoRadicadoDocumental', ['cgTipoRadicadoDocumental' => 'idGdTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
            
        $gdTrdTiposDocumentales = $gdTrdTiposDocumentales->where(['cgTipoRadicadoDocumental.idCgTipoRadicado' => Yii::$app->params['idCgTipoRadicado']['radiPqrs']])
        ->where(['estadoTipoDocumental' => Yii::$app->params['statusTodoText']['Activo']])->all();

        # Listado gdTrdTiposDocumentales
        $list_tipos_documentales = ArrayHelper::map( $gdTrdTiposDocumentales, 'idGdTrdTipoDocumental', 'nombreTipoDocumental');
    
        # Listado cgClasificacionPqrs
        $list_tipos_clasificacion = ArrayHelper::map(
            CgClasificacionPqrs::find()->where(['estadoCgClasificacionPqrs' => Yii::$app->params['statusTodoText']['Activo']])->all(), 'idCgClasificacionPqrs', 'nombreCgClasificacionPqrs'
        );

        # List Respuesta para la Pqrs
        $list_autorizacion = Yii::$app->params['seguimientoViaPQRS']['number'];

        # Excepciones para anexos en comentarios
        $exceptionFile = Yii::$app->params['exceptiosFilePqrs'];

        # Nombre del remitente
        $clientes = ClientesCiudadanosDetalles::find()->where(['idUser' => Yii::$app->user->identity['id']])->one();
        $user_detalles = $clientes->cliente->nombreCliente;

        return $this->render('index', [
            'model_filter' => new filterOperation,
            'model_anexos' => new Anexos,
            'model_hidden' => new RadiRadicados,
            'model' => new RadiRadicadosForm,
            'radicados' => $dataList,
            'historicos' => $historico,
            'respuestas' => $respuestas,
            'documentos' => $documentos,   
            'user_detalles' => $user_detalles,
            'exceptionFile' => $exceptionFile,
            'list_tipos_documentales' => $list_tipos_documentales,
            'list_tipos_clasificacion' => $list_tipos_clasificacion,
            'list_autorizacion' => $list_autorizacion,
            'Pagination' => $pages,
        ]);
    }


    public function actionAddComment(){
    
        $request = Yii::$app->request->post();
        

        if(isset($request) && !empty($request)){

            #Usuario gestor expedientes PQRS
            $modelCgGeneral = CgGeneral::find()->select(['correoNotificadorPqrsCgGeneral'])
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])->one();                                    
            $user_log = User::find()->where(["email" => $modelCgGeneral->correoNotificadorPqrsCgGeneral])->one();
            $user_detalles_log = UserDetalles::find()->where(["idUser" => $user_log->id])->one();

            # Transacción de creación del radicado
            $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'attachment']);
            $idTransaccion = $modelTransaccion->idCgTransaccionRadicado; // addComment

            # Datos Cliente / Usuario logueado
            $idUser = Yii::$app->user->identity['id'];
            $clientesDetalles = ClientesCiudadanosDetalles::find()->where(['idUser' => $idUser])->one();

            # Documentos Cargados
            $numeroDocumento = 0;

            $errors = [];

            # Configuración general del id dependencia pqrs
            $configGeneral = ConfiguracionGeneralController::generalConfiguration();

            # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
            if($configGeneral['status']){
                $idDependenciaUserPqrs = $configGeneral['data']['idDependenciaPqrsCgGeneral'];

            } else {

                return HelpersJson::encode([
                    'type' => 'error',
                    'class' => 'alert alert-danger',
                    'class-msj' => 'color-danger',
                    'msj' => $configGeneral['message']
                ]);
            }

            foreach($request as $key => $modelos){

                $transaction = Yii::$app->db->beginTransaction();
                $idRadiRadicado = $request['RadiRadicados']['idRadiRadicado'];

                $model_anexos =  new Anexos;
                $model_anexos->attributes = $request['Anexos'];

                $radiRadicados = RadiRadicados::findOne(['idRadiRadicado' => $idRadiRadicado]);

                # ya esta en estado finalizado 
                if($radiRadicados->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']){
                    return HelpersJson::encode([
                        'type' => 'error',
                        'class' => 'alert alert-danger',
                        'class-msj' => 'color-danger',
                        'msj' =>  'El radicado: ' . $radiRadicados->numeroRadiRadicado. '<br> Se encuentra en estado finalizado.'
                    ]);
                }                

                # Gestion de Documentos/Anexos 
                $GdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $idDependenciaUserPqrs]);
                $anio = date("Y"); 
                $pdfFiles = [];

                $pathUploadFile = Yii::getAlias('@webroot')                   
                    . "/" . Yii::$app->params['bodegaRadicados'] 
                    . "/" . $anio                            
                    . "/" . $GdTrdDependencias['codigoGdTrdDependencia']
                    . "/"
                ;

                $pathUploadFile = str_replace('frontend/','api/',$pathUploadFile);


                if ($model_anexos->load(Yii::$app->request->post())) {

                    $files = UploadedFile::getInstances($model_anexos, 'anexo');
              
                    if(isset($files) && !empty($files)){
                    
                        // Verificar que la carpeta exista y crearla en caso de que no exista
                        if (!file_exists($pathUploadFile)) {
                            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                                $transaction->rollBack();    
                                Yii::$app->session->setFlash('error', 'El directorio no existe'); 
                                break;
                            }
                        }   

                        foreach($files as $key => $fileUpload){
        
                            $numeroDocumento = $key+1;

                            $modelDoc = new RadiDocumentos;
                            $modelDoc->nombreRadiDocumento       = $radiRadicados->numeroRadiRadicado.'-'.$numeroDocumento.'.'.$fileUpload->extension;                       
                            $modelDoc->rutaRadiDocumento         = $pathUploadFile;                   
                            $modelDoc->extencionRadiDocumento    = $fileUpload->extension;
                            $modelDoc->idRadiRadicado            = $radiRadicados->idRadiRadicado;
                            $modelDoc->idGdTrdTipoDocumental     = Yii::$app->params['idGdTrdTipoDocumentalPqrs']; 
                            $modelDoc->descripcionRadiDocumento  = Yii::$app->params['anexosPaginaPublica']; 
                            $modelDoc->idUser                    = Yii::$app->params['idUserTramitadorPqrs'];
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
                                }else{
                                    $gdTrdTiposDocumentales = GdTrdTiposDocumentales::find()->select(['nombreTipoDocumental'])->where(['idGdTrdTipoDocumental' => $modelDoc->idGdTrdTipoDocumental])->one();
                                    $attributeLabels = RadiDocumentos::attributeLabels();
                                    
                                    #Array de radiDocumento para insertar en el log de auditoria
                                    $dataOld = '';
                                    $dataNew = $attributeLabels['idRadiDocumento'] . ': ' . $modelDoc->idRadiDocumento
                                        . ', ' . $attributeLabels['nombreRadiDocumento'] . ': ' . $modelDoc->nombreRadiDocumento
                                        . ', ' . $attributeLabels['descripcionRadiDocumento'] . ': ' . $modelDoc->descripcionRadiDocumento
                                        . ', Tipo Documental: ' . $gdTrdTiposDocumentales['nombreTipoDocumental']
                                    ;
                                    
                                    /*** log Auditoria ***/        
                                    HelperLog::logAdd(
                                        true,
                                        $user_log->id, //Id user
                                        "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                                        Yii::$app->controller->route, //Modulo
                                        Yii::$app->params['eventosLogText']['FileUpload'] . ' al radicado ' .  $radiRadicados->numeroRadiRadicado . ' por el usuario ciudadano ' . $clientesDetalles->cliente->nombreCliente,// texto para almacenar en el evento
                                        $dataOld,
                                        $dataNew, //Data
                                        array() //No validar estos campos
                                    );
                                    /***  Fin log Auditoria   ***/

                                    # Observacion
                                    HelperLog::logAddFiling(
                                        Yii::$app->params['idUserTramitadorPqrs'],
                                        Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                                        $radiRadicados->idRadiRadicado, //Id radicado
                                        $idTransaccion,  // Id transaccion
                                        "Se cargó anexos a la pqrsd por parte del ciudadano ".$clientesDetalles->cliente->nombreCliente, //observación 
                                        [],
                                        array() //No validar estos campos
                                    );
                                }

                            # Nombre del archivo que se esta cargando al cual se le establece un nombre estandar + id sin eliminar el tempFile
                            $uploadExecute = $fileUpload->saveAs($modelDoc->rutaRadiDocumento.'/'.$modelDoc->nombreRadiDocumento);
                                
                            ///////////////////////// EXTRAER TEXTO  ////////////////////////
                                $helperExtraerTexto = new HelperExtraerTexto($modelDoc->rutaRadiDocumento.$modelDoc->nombreRadiDocumento);

                                $helperOcrDatos = $helperExtraerTexto->helperOcrDatos(
                                    $modelDoc->idRadiDocumento, 
                                    Yii::$app->params['tablaOcrDatos']['radiDocPrincipales']  
                                );
                                
                                if($helperOcrDatos['status'] != true){
                                    Yii::$app->session->setFlash('error', $helperOcrDatos['message']);
                                }
                            ///////////////////////// EXTRAER TEXTO  ////////////////////////

                            $pdfFiles[]  = [
                                'nombreRadiDocumento' => $modelDoc->nombreRadiDocumento,
                            ];
                        }
                    }
                }

                $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'addComment']);
                $idTransaccion = $modelTransaccion->idCgTransaccionRadicado; // addComment

                # Log Radicados
                HelperLog::logAddFiling(
                    Yii::$app->params['idUserTramitadorPqrs'],
                    Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                    $radiRadicados->idRadiRadicado, //Id radicado
                    $idTransaccion,  // Id transaccion
                    "Se realizó la transacción comentarios PQRSD al radicado:".$radiRadicados->numeroRadiRadicado." por el ciudadano: ".$clientesDetalles->cliente->nombreCliente . '. Comentario: ' . $request['Anexos']['observacion'], //observación  
                    [],
                    array() //No validar estos campos
                );

                /* ========================== NOTIFICACIONES CORREO ========================== */

                    # Se consulta la información del usuario tramitador
                    $modelUser = User::findOne(['id' => Yii::$app->params['idUserTramitadorPqrs']]);

                    # Envio de correo al usuario tramitador Pqrs
                    $headMailText  = Yii::t('app', 'headMailCommentPqrs',[
                    'numRadicado' => $radiRadicados->numeroRadiRadicado
                    ]);       

                    $textBody  = Yii::t('app', 'textBodyCommentrPqrs');

                    # Agregar numero de anexos si existen al bodyText del correo
                    if($numeroDocumento > 0){
                        $textBody  = $textBody.''.Yii::t('app', 'addAnexoCommentPqrs',[
                            'numAnexos' => $numeroDocumento
                        ]);
                    }
                
                    $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radiRadicados->idRadiRadicado));
                    $bodyMail = 'radicacion-html'; 
                    $subject = 'subjectCommentPqrs';    
        
                    $envioCorreo = CorreoController::radicacion($modelUser->email, $headMailText, $textBody, $bodyMail, $dataBase64Params, $subject);
                    # Fin de envio de correo al usuario tramitador Pqrs

                /* ========================== NOTIFICACIONES CORREO ========================== */
                    
                $transaction->commit();   

                /***  Log Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    $user_log->id, //Id user
                    "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username                                        
                    Yii::$app->controller->route, //Modulo
                    "Se realizó la transacción comentarios PQRSD al radicado:".$radiRadicados->numeroRadiRadicado." por el ciudadano: ".$clientesDetalles->cliente->nombreCliente . '. Comentario: ' . $request['Anexos']['observacion'], //observación  
                    [], // data old
                    [], //Data
                    array() //No validar estos campos
                );
                /*** Fin log Auditoria ***/

                break; 
            }

            # Validación de almacenamiento
            if(count($errors) >= 1){
                foreach($errors as $model){                
                    foreach($model as $key_msj => $key){                    
                        foreach($key as $message){
                            return HelpersJson::encode([
                                'type' => 'error',
                                'class' => 'alert alert-danger',
                                'class-msj' => 'color-danger',
                                'msj' => $key_msj.'<br>'.$message
                            ]);
                        }
                    }                  
                }
            }else{

                return HelpersJson::encode([
                    'type' => 'success',
                    'class' => 'alert alert-success',
                    'class-msj' => 'color-success',
                    'msj' => 'Comentario agregado con éxito, al radicado: ' . $radiRadicados->numeroRadiRadicado
                ]);
    
            }

        }
    }

    public function actionDesistimientoRadicado(){
        
        $request = Yii::$app->request->post();

        if(isset($request) && !empty($request)){

            #Usuario gestor expedientes PQRS
            $modelCgGeneral = CgGeneral::find()->select(['correoNotificadorPqrsCgGeneral'])
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])->one();                                    
            $user_log = User::find()->where(["email" => $modelCgGeneral->correoNotificadorPqrsCgGeneral])->one();
            $user_detalles_log = UserDetalles::find()->where(["idUser" => $user_log->id])->one();

            # Transacción de creación del radicado
            $modelTransaccion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'Desist']);
            $idTransaccion = $modelTransaccion->idCgTransaccionRadicado; // addComment

            # Datos Cliente / Usuario logueado
            $clientesDetalles = ClientesCiudadanosDetalles::find()->where(['idUser' => Yii::$app->user->identity['id']])->one();

            $errors = [];

            foreach($request as $key => $modelos){

                $idRadiRadicado = $request['idRadiRadicado'];
                $observacionDesistimiento = $request['observacionDesistimiento'];

                $transaction = Yii::$app->db->beginTransaction();
                $modelRadiRadicados = RadiRadicados::findOne(['idRadiRadicado' => $idRadiRadicado]);

                # ya esta en estado finalizado 
                if($modelRadiRadicados->estadoRadiRadicado == Yii::$app->params['statusTodoText']['Finalizado']){
                    return HelpersJson::encode([
                        'type' => 'error',
                        'class' => 'alert alert-danger',
                        'class-msj' => 'color-danger',
                        'msj' =>  'El radicado: ' . $modelRadiRadicados->numeroRadiRadicado. '<br> Ya se encuentra desistido.'
                    ]);
                }
                
                # Actualizar estado del radicado a finalizado
                if(isset($modelRadiRadicados)){

                    # buscar usuario dsalida 
                    $dsalida = User::findOne(['username' => Yii::$app->params['userNameDeSalida']]);

                    # actualizar estado | tramitadorOld | tramitador
                    $modelRadiRadicados->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Finalizado'];
                    $modelRadiRadicados->user_idTramitadorOld =  $modelRadiRadicados->user_idTramitador;
                    $modelRadiRadicados->user_idTramitador = $dsalida->id;
                    $modelRadiRadicados->idTrdDepeUserTramitador = $dsalida->idGdTrdDependencia;

                    if(!$modelRadiRadicados->save()){
                        $transaction->rollBack();    
                        $errors[] = $modelRadiRadicados->getErrors(); 
                        break;   
                    }
                }               

                # Log Radicados
                HelperLog::logAddFiling(
                    Yii::$app->params['idUserTramitadorPqrs'],
                    Yii::$app->params['idGdTrdTipoDocumentalPqrs'],
                    $modelRadiRadicados->idRadiRadicado, //Id radicado
                    $idTransaccion,  // Id transaccion
                    "El ciudadano: ".$clientesDetalles->cliente->nombreCliente.", desistió de la PQRSD con el número de radicado: ".$modelRadiRadicados->numeroRadiRadicado . " y la observación: " . $observacionDesistimiento, //observación 
                    [],
                    array() //No validar estos campos
                );

                #  Log Auditoria  
                HelperLog::logAdd(
                    false,
                    $user_log->id, //Id user
                    "$user_detalles_log->nombreUserDetalles $user_detalles_log->apellidoUserDetalles", //username
                    Yii::$app->controller->route, //Modulo
                    "El ciudadano: ".$clientesDetalles->cliente->nombreCliente.", desistió de la PQRSD con el número de radicado: ".$modelRadiRadicados->numeroRadiRadicado . " y la observación: " . $observacionDesistimiento, //observación 
                    [], // data old
                    [], //Data
                    array() //No validar estos campos
                );

                $transaction->commit();  

                /* ========================== NOTIFICACIONES CORREO ========================== */

                    # Se consulta la información del usuario tramitador
                    $modelUser = User::findOne(['id' => $modelRadiRadicados->user_idTramitadorOld]); 

                    # Envio de correo al usuario tramitador Pqrs
                    $headMailText  = Yii::t('app', 'headMailDisPqrs',[
                        'numRadicado' => $modelRadiRadicados->numeroRadiRadicado
                    ]);       

                    $textBody  = Yii::t('app', 'textBodyDisPqrs',[
                        'nomCiu' => $clientesDetalles->cliente->nombreCliente
                    ]); 

                    $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelRadiRadicados->idRadiRadicado));
                    $bodyMail = 'radicacion-html'; 
                    $subject = 'subjectDisPqrs';    
        
                    $envioCorreo = CorreoController::radicacion($modelUser->email, $headMailText, $textBody, $bodyMail, $dataBase64Params, $subject);
                    # Fin de envio de correo al usuario tramitador Pqrs
                    
                /* ========================== NOTIFICACIONES CORREO ========================== */

                break;
            }

            # Validación de almacenamiento
            if(count($errors) >= 1){
                foreach($errors as $model){              
                    foreach($model as $key_msj => $key){                      
                        foreach($key as  $message){
                            return HelpersJson::encode([
                                'type' => 'error',
                                'class' => 'alert alert-danger',
                                'class-msj' => 'color-danger',
                                'msj' => $key_msj.'<br>'.$message
                            ]);
                        }
                    }                  
                }
            }else{

                return HelpersJson::encode([
                    'type' => 'success',
                    'class' => 'alert alert-success',
                    'class-msj' => 'color-success',
                    'msj' => 'Desisitimiento generado con éxito, <br> al radicado: ' . $modelRadiRadicados->numeroRadiRadicado
                ]);
            }

        }

    }

    public function actionReloadHistorico($id) {

        $idRadiRadicado = $id;
        $historico = [];
        $modelRelation = RadiRadicados::findAll(['idRadiRadicado' => $idRadiRadicado]);

        foreach ($modelRelation as $infoRelation) {

            $radicado = array(
                'id'             => $infoRelation->idRadiRadicado,
                'idEncrypt'      => str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
            );

            # Trazabilidad   
            $historicoRadicados = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
            ->all();

            foreach($historicoRadicados as $historicoRadicado){
                $historico[$infoRelation->idRadiRadicado][] = array(
                    'idRadiRadicado' => $historicoRadicado->idRadiRadicado,
                    'iconTrans'     => $historicoRadicado->transaccion->iconCgTransaccionRadicado,
                    'transaccion'   => $historicoRadicado->transaccion->descripcionCgTransaccionRadicado,  //acción
                    'fecha'         => $historicoRadicado->fechaRadiLogRadicado,                    
                    'usuario'       => $historicoRadicado->user->userDetalles->nombreUserDetalles.' '.$historicoRadicado->user->userDetalles->apellidoUserDetalles,
                    'dependencia'   => $historicoRadicado->dependencia->nombreGdTrdDependencia,   
                    'observacion'   => $historicoRadicado->observacionRadiLogRadicado,
                );
            }
        }   

        return Yii::$app->controller->renderPartial('/consulta-pqrs/content-historico.php',[
            'historicos' => $historico,
            'radicado' => $radicado,
        ]);

    }

    public function actionReloadRespuestas($id) {

        $idRadiRadicado = $id;
        $modelRelation = RadiRadicados::findAll(['idRadiRadicado' => $idRadiRadicado]);
        $respuestas = [];

        foreach ($modelRelation as $infoRelation) {

            $radicado = array(
                'id'         => $infoRelation->idRadiRadicado,
                'idEncrypt'  => str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
                'numeroRadiRadicado' => $infoRelation->numeroRadiRadicado,
            );

            # Respuestas / Documentos principales
            $radiDocPrincipales = RadiDocumentosPrincipales::find()
              ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado, 'extensionRadiDocumentoPrincipal' => 'pdf','estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']])
              ->orderBy(['creacionRadiDocumentoPrincipal' => SORT_DESC])
            ->all();

            foreach($radiDocPrincipales as $docPrincipal){
                $respuestas[$infoRelation->idRadiRadicado][] = array(
                    'id'             => $docPrincipal->idradiDocumentoPrincipal,
                    'idRadiRadicado' => $docPrincipal->idRadiRadicado,
                    'usuario'        => $docPrincipal->user->userDetalles->nombreUserDetalles.' '.$docPrincipal->user->userDetalles->apellidoUserDetalles,
                    'nombre'         => $docPrincipal->nombreRadiDocumentoPrincipal,  
                    'fecha'          => date("d-m-Y", strtotime($docPrincipal->creacionRadiDocumentoPrincipal)),                  
                    'imgPrincipal'   => $docPrincipal->imagenPrincipalRadiDocumento,   
                    'icon'           => $docPrincipal->extensionRadiDocumentoPrincipal,
                );
            }
        }   

        return Yii::$app->controller->renderPartial('/consulta-pqrs/content-respuestas.php',[
            'respuestas' => $respuestas,
            'radicado' => $radicado,
        ]);

    }

    public function actionReloadAnexos($id) {

        $idRadiRadicado = $id;
        $modelRelation = RadiRadicados::findAll(['idRadiRadicado' => $idRadiRadicado]);
        $documentos = [];
        
        foreach ($modelRelation as $infoRelation) {

            $radicado = array(
                'id'         => $infoRelation->idRadiRadicado,
                'idEncrypt'  => str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
            );

            # Documentos / Anexos    
            $radiDocumentos = RadiDocumentos::find()
                ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                ->orderBy(['creacionRadiDocumento' => SORT_DESC])
            ->all();



            foreach($radiDocumentos as $key => $radiDocumento){

                $descripcion = (string) $radiDocumento['descripcionRadiDocumento'];

                $documentos[$infoRelation->idRadiRadicado][] = [
                    'id'            => $radiDocumento->idRadiDocumento,
                    'icon'          => $radiDocumento->extencionRadiDocumento,
                    'nombre'        => $radiDocumento->nombreRadiDocumento,
                    'numDocumento'  => $radiDocumento->numeroRadiDocumento,
                    'descripcion'   => (strlen($descripcion) > 100) ? substr($descripcion, 0, 100) . '...' : $descripcion,
                    'descripcionTitle' => $radiDocumento->descripcionRadiDocumento,
                    'tipodocumento' => $radiDocumento->idGdTrdTipoDocumental0->nombreTipoDocumental,
                    'usuario'       => $radiDocumento->idUser0->nombreUserDetalles.' '.$radiDocumento->idUser0->apellidoUserDetalles,
                    'estado'        => $radiDocumento->estadoRadiDocumento,
                    'fecha'         => date("d-m-Y", strtotime($radiDocumento->creacionRadiDocumento)),
                    'isPdf'         => (strtolower($radiDocumento->extencionRadiDocumento) == 'pdf') ? true : false,
                    'isPublicoRadiDocumento' => Yii::$app->params['SiNoNumber'][$radiDocumento->isPublicoRadiDocumento],
                ];   
            }
        }   

        return  Yii::$app->controller->renderPartial('/consulta-pqrs/content-anexos.php',[
            'documentos' => $documentos,
            'radicado' => $radicado,
        ]);

    }

    public function actionDownloadFile(){
        
        $request = Yii::$app->request->post(); $file = null;

        if(isset($request) && !empty($request)){

            #consultar tabla para buscar documento a descargar
            switch($request['downloadType']){ 
                case Yii::$app->params['downloadType']['principal']:
                    $documento = RadiDocumentosPrincipales::findOne(['idradiDocumentoPrincipal' => $request['id']]);
                    # validar 
                    if(isset($documento)){
                        $fileName = $documento['nombreRadiDocumentoPrincipal'].'.'.$documento['extensionRadiDocumentoPrincipal'];
                        $file = $documento['rutaRadiDocumentoPrincipal'];
                        $numeroRadiRadicado = $documento->idRadiRadicado0['numeroRadiRadicado'];
                    }
                break;
                case Yii::$app->params['downloadType']['anexo']:
                    $documento = RadiDocumentos::findOne(['idRadiDocumento' => $request['id']]);
                    # validar 
                    if(isset($documento)){
                        $fileName = $documento['nombreRadiDocumento'];
                        $file = $documento['rutaRadiDocumento'].''.$documento['nombreRadiDocumento'];
                        $numeroRadiRadicado = $documento->idRadiRadicado0['numeroRadiRadicado'];
                    }
                break;

                default: 
                    return HelpersJson::encode([
                        'status' => false,
                        'msj' => 'tipo de descarga no permitida'
                    ]);
                break;
            }

            /* Enviar archivo en base 64 como respuesta de la petición **/
            if(file_exists($file))
            {
                //Lee el archivo dentro de una cadena en base 64
                $dataFile = array( 
                    'datafile' => base64_encode(file_get_contents($file)),
                    'fileName' => $fileName
                );
                     
                return HelpersJson::encode([
                    'status' => true,
                    'file' => $dataFile,
                    'numeroRadiRadicado' => $numeroRadiRadicado
                ]);
              
            } 
        }

        return HelpersJson::encode([
            'status' => false,
            'msj' => 'archivo no encontrado'
        ]);
    }
}
