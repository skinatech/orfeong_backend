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

namespace api\modules\version1\controllers\gestionDocumental;

use api\components\HelperDynamicForms;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperNotification;
use api\components\HelperQueryDb;
use api\models\CgRegionales;
use api\models\CgTrd;
use api\models\GaArchivo;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdHistoricoExpedientes;
use api\models\GdTrd;
use api\models\GdTrdDependencias;
use api\models\GdTrdDependenciasTmp;
use api\models\GdTrdSeries;
use api\models\GdTrdSeriesTmp;
use api\models\GdTrdSubseries;
use api\models\GdTrdSubseriesTmp;
use api\models\GdTrdTiposDocumentales;
use api\models\GdTrdTiposDocumentalesTmp;
use api\models\GdTrdTmp;
use api\models\RadiRadicados;
use api\models\RolesTipoDocumental;
use api\models\User;
use api\models\UserDetalles;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\pdf\PdfController;
use DateTime;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\helpers\FileHelper;
use yii\web\Controller;

class TrdTransferenciaController extends Controller
{
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
                    'pendiente-transferir' => ['POST'],
                    'transferencia-manual' => ['POST'],
                    'transferencia-aceptada' => ['POST'],
                    'transferencia-rechazada' => ['POST'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionIndex($request){  
    
        if (!empty($request)) {

            //*** Inicio desencriptación GET ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación GET ***//          
        }            
                    
        //Lista de expedientes
        $dataList = []; 
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if (is_array($request)) {
            foreach($request['filterOperation'] as $field) {
                foreach($field as $key => $info){

                  if ($key == 'inputFilterLimit') {
                      $limitRecords = $info;
                      continue;
                  }

                   //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                   if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                            $dataWhere[$key] =  $info;
                        }
                    } else {
                        if( isset($info) && $info !== null){
                            # Validacion cuando es un array
                            if(is_array($info) && !empty($info)){
                                $dataWhere[$key] = $info;
                            } elseif( trim($info) != '') { // O un string
                                $dataWhere[$key] = $info;
                            }                            
                        }
                    }
                }
            }
        }

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;            

        /***
         * Se debe hacer un index, el cual debe mostrar las siguientes columnas: 
         * 
         * Código Expediente, 
         * Nombre Expediente, 
         * Serie y Subserie, 
         * Fecha Inicio Proceso, 
         * Usuario Creador Expediente, 
         * Dependencia del usuario creador, 
         * 
         * Tipo Archivo (Gestión = Primera Ubicación, 
         *               Central = Segunda Ubicación, 
         *               Historico = Tercera Ubicación), 
         * 
         * Estado("Pendiente por Transferir = 12", 
         *        "Transferencia Aceptada = 13", 
         *        "Tranferencia Rechazada = 14"). 
         * 
         * Se deben mostrar todos los expedientes que ya cumplieron los tiempos en archivo de gestión 
         * y ahora deben estar disponibles para ser transferidos 
         * (Comparación entre la fecha actual contra la fecha de transferencia a gestion 
         * o entre la fecha actual contra la fecha de transferencia historico, 
         * si las fechas son iguales para alguno de los casos anteriores).
         */

        # Relacionamiento de los expedientes
        $relationFiles = GdExpedientes::find();

            // ->innerJoin('userDetalles', 'userDetalles.idUser = gdExpedientes.idUser')
            $relationFiles = HelperQueryDb::getQuery('innerJoin', $relationFiles, 'userDetalles', ['userDetalles' => 'idUser', 'gdExpedientes' => 'idUser']);
            // ->innerJoin('gdTrdDependencias', 'gdTrdDependencias.idGdTrdDependencia = gdExpedientes.idGdTrdDependencia')
            $relationFiles = HelperQueryDb::getQuery('innerJoin', $relationFiles, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'gdExpedientes' => 'idGdTrdDependencia']);
            // ->innerJoin('gdTrdSeries', 'gdTrdSeries.idGdTrdSerie = gdExpedientes.idGdTrdSerie')
            $relationFiles = HelperQueryDb::getQuery('innerJoin', $relationFiles, 'gdTrdSeries', ['gdTrdSeries' => 'idGdTrdSerie', 'gdExpedientes' => 'idGdTrdSerie']);
            // ->innerJoin('gdTrdSubseries', 'gdTrdSubseries.idGdTrdSubserie = gdExpedientes.idGdTrdSubserie')
            $relationFiles = HelperQueryDb::getQuery('innerJoin', $relationFiles, 'gdTrdSubseries', ['gdTrdSubseries' => 'idGdTrdSubserie', 'gdExpedientes' => 'idGdTrdSubserie']);

            $relationFiles = $relationFiles->where(['or',
                ['estadoGdExpediente' => Yii::$app->params['statusTodoText']['PendienteTransferir']],
                ['<=', 'tiempoGestionGdExpedientes', trim(date("Y-m-d")). Yii::$app->params['timeEnd']]
            ]);
        
        # Se visualiza todos los expedientes del logueado, cuando no haya ningun filtro.
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico']) {
            $relationFiles->andWhere(['gdExpedientes.idUser' => Yii::$app->user->identity->id]);
        }    

        # Cuando es nivel intermedio se busca todos los expedientes asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio']) {               
            $relationFiles->andWhere(['gdExpedientes.idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]); 
        } 

        //Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idUser': 
                    $relationFiles->andWhere(['userDetalles.idUser' => $value]);
                break;
                case 'fechaInicial':
                    $relationFiles->andWhere(['>=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relationFiles->andWhere(['<=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'idGdTrdSerie':
                case 'idGdTrdSubserie':
                case 'idGdTrdDependencia':
                case 'ubicacionGdExpediente':
                    $relationFiles->andWhere(['IN', 'gdExpedientes.' . $field, $value]);
                break;
                case 'status':
                    $relationFiles->andWhere(['IN', 'gdExpedientes.estadoGdExpediente', $value]);
                break;
                default:
                    $relationFiles->andWhere([ Yii::$app->params['like'], 'gdExpedientes.' . $field, $value ]);
                break;
            }
        } 

        # Orden descendente para ver los últimos registros creados
        $relationFiles->orderBy(['gdExpedientes.idGdExpediente' => SORT_DESC]); 

        # Limite de la consulta
        $relationFiles->limit($limitRecords);
        $modelRelation = $relationFiles->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);
        $hoy = new DateTime(date('Y-m-d'));

        foreach ($modelRelation as $expediente) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($expediente->idGdExpediente)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($expediente->numeroGdExpediente)),
            );

            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $expediente->idGdExpediente,
                'nombreExpediente'  => $expediente->nombreGdExpediente,
                'numeroExpediente'  => $expediente->numeroGdExpediente,
                'serie'             => $expediente->gdTrdSerie->codigoGdTrdSerie.'-'.$expediente->gdTrdSerie->nombreGdTrdSerie,
                'subserie'          => $expediente->gdTrdSubserie->codigoGdTrdSubserie.' - '.$expediente->gdTrdSubserie->nombreGdTrdSubserie,
                'fechaProceso'      => $expediente->fechaProcesoGdExpediente,
                'dependenciaCreador' => $expediente->gdTrdDependencia->nombreGdTrdDependencia,
                'idUserCreador'     => $expediente->idUser,
                'userCreador'       => $expediente->user->userDetalles->nombreUserDetalles . ' ' . $expediente->user->userDetalles->apellidoUserDetalles,
                'tipoArchivo'       => Yii::$app->params['ubicacionTransferenciaTRD'][$expediente->ubicacionGdExpediente],
                'ubicacionGdExpediente' => $expediente->ubicacionGdExpediente,
                'statusText'        => Yii::t('app', 'statusTodoNumber')[$expediente->estadoGdExpediente],
                'status'            => $expediente->estadoGdExpediente,
                'rowSelect'         => false,
                'idInitialList'     => 0,
            );
        }

        /**
         *  Los filtros del index deben ser los siguientes: 
         * 
         *  Dependencia (Listado), 
         *  Usuario (campo de texto -> Nombre o Documento de identidad), 
         *  Rango de Fecha en la que se archivo,
         *  Serie(Listado todas las series activas),
         *  Subserie (Listado todas las subseries que estan activas pero segun la serie seleccionada),
         *  Expediente (Campo texto -> Nombre o número de expediente),
         *  Tipo de Archivo (Listado).
         * 
         */
        $formType = HelperDynamicForms::setListadoBD('indexExpedienteTransferencia'); 

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
            'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Transferencia manual de expedientes 
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */

    public function actionTransferenciaManual(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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

                $dataTiempoCentral = [];
                foreach($request['ButtonSelectedData'] as $key => $expediente){

                    $transaction = Yii::$app->db->beginTransaction();
                    $gdExpediente = GdExpedientes::find()->where(['idGdExpediente' => $expediente['id']])->one();

                    if($gdExpediente['estadoGdExpediente'] == Yii::$app->params['statusTodoText']['PendienteTransferir']){

                        $notificacion[] =  [
                            'message' => Yii::t('app', 'failTransExp', [
                                'numExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                            ]),
                            'type' => 'danger'
                        ];

                        # Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $expediente['id'],
                            'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                            'status' =>  $gdExpediente->estadoGdExpediente,
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                        );

                    }else{

  
                    if(!isset($gdExpediente)){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'failExpediente')]], 
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $idExpeSeleccionados[] =  $gdExpediente['numeroGdExpediente'];
                    $fechasExtremas = '';
                    $numeroFolios = 0;
                    
                    # gdExpediente->attributes
                    $gdExpediente->estadoGdExpediente = Yii::$app->params['statusTodoText']['PendienteTransferir'];
                    $observacion = Yii::$app->params['eventosLogTextExpedientes']['pendienteTransferirManual'].$request['data']['observacion'];

                    /***    Log de Expedientes  ***/
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $gdExpediente->idGdExpediente,
                        Yii::$app->params['operacionExpedienteText']['CambiarEstado'], //Operación
                        str_replace('{numExp}', $gdExpediente->numeroGdExpediente, $observacion)
                    );
                    /***  Fin  Log de Expedientes  ***/    
    
                    if(!$gdExpediente->save()){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $gdExpediente->getErrors(),
                            'dataUpdate' => [],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    # Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $expediente['id'],
                        'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                        'status' =>  $gdExpediente->estadoGdExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                    );
    
                    # Encabezado del formato unico de inventario
                    $dependencia = $gdExpediente->gdTrdDependencia['nombreGdTrdDependencia'];
                    $oficina = $gdExpediente->gdTrdDependencia['codigoGdTrdDependencia'];
                    # Encabezado del formato unico de inventario
                    $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['oficinaProductora'] = $dependencia;   
                    $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['codigoOficina'] = $oficina;   
                
                    $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['idGdExpediente'][] = $gdExpediente['idGdExpediente'];

                    # Datos para almacenar en el log del sistema                   
                    $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['nombreGdExpediente'][] = $gdExpediente['nombreGdExpediente'].'-'.$gdExpediente['numeroGdExpediente'];

                    # Datos del expediente para el formato unico de inventario

                    $gaArchivo = GaArchivo::find();
                    $gaArchivo = HelperQueryDb::getQuery('innerJoin', $gaArchivo, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gaArchivo' => 'idGdExpediente']);
                    $gaArchivo = $gaArchivo->where(['gdExpedientesInclusion.idGdExpediente' => $gdExpediente->idGdExpediente])->one();

                    if(!isset($gaArchivo)){
                        $transaction->rollBack();
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

                        foreach($gdExpediente->gdExpedientesInclusion as $key => $expedientesInclusion){
                            # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                            $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $key;
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
                        'inicioProceso' =>  date("Y-m-d H:i A", strtotime($gdExpediente['fechaProcesoGdExpediente'])),
                        'idUser' => $gdExpediente->user->userDetalles['nombreUserDetalles'].' '.$gdExpediente->user->userDetalles['apellidoUserDetalles']
                    ];

                    $transaction->commit();

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'successTransExp', [
                            'nomExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                        ]),
                        'type' => 'success'
                    ];

                    }
                }
 
                $this->notificarPendienteTransferencia($dataTiempoCentral, false);

                $dataLog = ""; $nombreGdExpediente = "";
                $observacion = Yii::$app->params['eventosLogText']['transferenciaDocumentalManual'];
                
                # Guardar log de auditoria }
                foreach($dataTiempoCentral as $dependencia => $data){
                    
                    $nombreGdExpediente = implode(', ', $data['nombreGdExpediente']);

                    $oficinaProductora = $data['oficinaProductora'];
                    $codigoOficina = $data['codigoOficina'];

                    $dataLog  = '  Expedientes: '.$nombreGdExpediente;
                    $dataLog .= ', Dependencia: '.$codigoOficina.'-'.$oficinaProductora;
                    $dataLog .= ', observacion: '.$request['data']['observacion'];               
                    $dataLog .= ', Estado: '.'Pendiente por Transferir';

                }

                /***    Log de Auditoria  ***/
                   HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    str_replace('{nomExp}', $nombreGdExpediente, $observacion).' '.$data['oficinaProductora'],
                    "", //DataOld
                    $dataLog, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/


                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'notificacion' => $notificacion ?? [],
                    'dataResponse' => $dataResponse ?? [],
                    'data' => [],
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
     * Aceptar Transferencia
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */

    public function actionTransferenciaAceptada(){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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

                $dataTiempoCentral = [];

                foreach($request['ButtonSelectedData'] as $key => $expediente){

                    $transaction = Yii::$app->db->beginTransaction();
                    $gdExpediente = GdExpedientes::find()->where(['idGdExpediente' => $expediente['id']])->one();

                    if($gdExpediente['estadoGdExpediente'] == Yii::$app->params['statusTodoText']['TransferenciaAceptada']){

                        $notificacion[] =  [
                            'message' => Yii::t('app', 'failTransExpAcept', [
                                'numExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                            ]),
                            'type' => 'danger'
                        ];

                        # Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $expediente['id'],
                            'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                            'status' =>  $gdExpediente->estadoGdExpediente,
                            'statusText' =>  Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                        );

                    }else{

                    if(!isset($gdExpediente)){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'failExpediente')]], 
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $idExpeSeleccionados[] =  $gdExpediente['numeroGdExpediente'];
                    $fechasExtremas = '';
                    $numeroFolios = 0;
                    
                    # gdExpediente->attributes
                    $gdExpediente->estadoGdExpediente = Yii::$app->params['statusTodoText']['TransferenciaAceptada'];

                    $observacion = Yii::$app->params['eventosLogTextExpedientes']['transferenciaAceptada']; //$request['observacion'];

                    /***    Log de Expedientes  ***/
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $gdExpediente->idGdExpediente,
                        Yii::$app->params['operacionExpedienteText']['CambiarEstado'], //Operación
                        str_replace('{numExp}', $gdExpediente->numeroGdExpediente, $observacion)
                    );
                    /***  Fin  Log de Expedientes  ***/    
    
                    if(!$gdExpediente->save()){
                        $transaction->rollBack();
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

                    # Datos para almacenar en el log del sistema                   
                    $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['nombreGdExpediente'][] = $gdExpediente['nombreGdExpediente'].'-'.$gdExpediente['numeroGdExpediente'];

                    # Datos del expediente para el formato unico de inventario

                    $gaArchivo = GaArchivo::find();
                    $gaArchivo = HelperQueryDb::getQuery('innerJoin', $gaArchivo, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gaArchivo' => 'idGdExpediente']);
                    $gaArchivo = $gaArchivo->where(['gdExpedientesInclusion.idGdExpediente' => $gdExpediente->idGdExpediente])->one();

                    if(!isset($gaArchivo)){
                        $transaction->rollBack();
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

                        foreach($gdExpediente->gdExpedientesInclusion as $key => $expedientesInclusion){
                            # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                            $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $key;
                            $numeroFolios = $numeroFolios + $expedientesInclusion->radiRadicado['foliosRadiRadicado'];
                        }

                        if(count($idRadiRadicados) > 0){
                            $min = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[min(array_keys($idRadiRadicados))]]->radiRadicado;
                            $max = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[max(array_keys($idRadiRadicados))]]->radiRadicado;
                            $fechasExtremas = $min['creacionRadiRadicado'].'@'.$max['creacionRadiRadicado'];
                        }

                    }

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $gdExpediente->idUser, // Id user notificado
                        Yii::t('app','messageNotification')['transferAccepted'], //Notificacion
                        Yii::$app->params['routesFront']['viewExpediente'], // url
                        $gdExpediente->idGdExpediente // id expediente
                    );
                    /***  Fin Notificacion  ***/

                    /***1
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
                        'inicioProceso' =>  date("Y-m-d H:i A", strtotime($gdExpediente['fechaProcesoGdExpediente'])),
                        'idUser' => $gdExpediente->user->userDetalles['nombreUserDetalles'].' '.$gdExpediente->user->userDetalles['apellidoUserDetalles']
                    ];

                    $transaction->commit();

                    $notificacion[] =  [
                        'message' => Yii::t('app', 'successTransExp', [
                            'nomExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                        ]),
                        'type' => 'success'
                    ];

                    # Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $expediente['id'],
                        'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                        'status' =>  $gdExpediente->estadoGdExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                    );

                    }
                }
              
                # Se debe enviarse un solo correo con el consolidado de todas las dependencias al usuario creador
                foreach($dataTiempoCentral as $dependencia => $data){
                    
                    # Envia la notificación de correo electronico                 

                    /** 
                     * Cuando se cambie el estado del expediente a "Transferencia Aceptada" 
                     * se debe enviar una notificación de correo electrónico indicando lo siguiente: 
                     * 
                     * En el asunto "Transferencia Aceptada" en el titulo del correo "Se acepto la tranferecia documental" 
                     * 
                     * y en el Cuerpo debe decir "Señor usuario se recibio correctamente la información y se acepta la 
                     * tranferencia de los expedientes suministrados, una vez asignada una ubicación en el archivo central 
                     * se enviara una notificación de la nueva ubicación de los expedientes." 
                     * 
                     * Este mensaje se debe enviar al usuario creador del expediente.
                     */

                    $bodyMail = 'radicacion-html';
                    
                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    $headMailText = Yii::t('app', 'headMailTextExpAceptadoTitle'); 
                    $textBody  = Yii::t('app', 'mailEventExpAceptado');
                    $subject = Yii::t('app', 'headMailTextExpAceptado');
                    
                    # boton de redireccion en el correo
                    $buttonDisplay = false;

                    # Enviar una notificación de correo electrónico al usuario responsable o creador el expediente
                    foreach($data['idGdExpediente'] as $key => $idGdExpediente){
                        
                        #consultar en el log de espedientes la operacion crear expediente
                        $gdHistoricoExpedientes = GdHistoricoExpedientes::find()->where(['idGdExpediente' => $idGdExpediente, 
                        'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['CrearExpediente']])->one();
                        $emailCreador = $gdHistoricoExpedientes->userHistoricoExpediente['email'];
                        
                        # validar envio de notificacion dublicada para el usuario creador
                        $userNotificado[] = $emailCreador;
                        $envioCorreo[] = CorreoController::addFile($emailCreador, $headMailText, $textBody, $bodyMail, null, $subject, $buttonDisplay, false);
                        
                    }
                    
                }

                $observacion = Yii::$app->params['eventosLogText']['transferenciaDocumentalAceptada'];
                $dataLog = ""; $nombreGdExpediente = "";
                # Guardar log de auditoria }
                foreach($dataTiempoCentral as $dependencia => $data){
                    
                    $nombreGdExpediente = implode(', ', $data['nombreGdExpediente']);

                    $oficinaProductora = $data['oficinaProductora'];
                    $codigoOficina = $data['codigoOficina'];

                    $dataLog  = '  Expedientes: '.$nombreGdExpediente;
                    $dataLog .= ', Dependencia: '.$codigoOficina.'-'.$oficinaProductora;             
                    $dataLog .= ', Estado: '.'Transferencia Aceptada';

                }

                /***    Log de Auditoria  ***/
                   HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    str_replace('{nomExp}', $nombreGdExpediente, $observacion).' '.$data['oficinaProductora'],
                    "", //DataOld
                    $dataLog, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/


                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'notificacion' => $notificacion ?? [],
                    'dataResponse' => $dataResponse ?? [],
                    'data' => [],
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
     * Rechazar Transferencia
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */

    public function actionTransferenciaRechazada(){
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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

                $dataTiempoCentral = [];

                foreach($request['ButtonSelectedData'] as $key => $expediente){

                    $transaction = Yii::$app->db->beginTransaction();
                    $gdExpediente = GdExpedientes::find()->where(['idGdExpediente' => $expediente['id']])->one();

                    # Validar que es Expediente no este en estado Aceptado
                    if($gdExpediente['estadoGdExpediente'] == Yii::$app->params['statusTodoText']['TransferenciaAceptada']){

                        $notificacion[] =  [
                            'message' => Yii::t('app', 'failTransExpAcept', [
                                'numExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                            ]),
                            'type' => 'danger'
                        ];

                        # Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $expediente['id'],
                            'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                            'status' =>  $gdExpediente->estadoGdExpediente,
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                        );

                    }else{

                        # Validar que es Expediente ya este en estado Rechazado
                        if($gdExpediente['estadoGdExpediente'] == Yii::$app->params['statusTodoText']['TransferenciaRechazada']){
                            
                            $notificacion[] =  [
                                'message' => Yii::t('app', 'failTransExpRecha', [
                                    'numExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                                ]),
                                'type' => 'danger'
                            ];
    
                            # Se retorna el estado de cada registro
                            $dataResponse[] = array(
                                'id' => $expediente['id'],
                                'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                                'status' =>  $gdExpediente->estadoGdExpediente,
                                'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente],  
                            );

                        }else{

                            if(!isset($gdExpediente)){
                                $transaction->rollBack();
                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app', 'errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'failExpediente')]], 
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }

                            $idExpeSeleccionados[] =  $gdExpediente['numeroGdExpediente'];
                            $fechasExtremas = '';
                            $numeroFolios = 0;
                            
                            # gdExpediente->attributes
                            $gdExpediente->estadoGdExpediente = Yii::$app->params['statusTodoText']['TransferenciaRechazada'];
                            $observacion = Yii::$app->params['eventosLogTextExpedientes']['transferenciaRechazada'].$request['data']['observacion'];

                            /***    Log de Expedientes  ***/
                            HelperLog::logAddExpedient(
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                $gdExpediente->idGdExpediente,
                                Yii::$app->params['operacionExpedienteText']['CambiarEstado'], //Operación
                                str_replace('{numExp}', $gdExpediente->numeroGdExpediente, $observacion)
                            );
                            /***  Fin  Log de Expedientes  ***/    
            
                            if(!$gdExpediente->save()){
                                $transaction->rollBack();
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

                            # Datos para almacenar en el log del sistema                   
                            $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['nombreGdExpediente'][] = $gdExpediente['nombreGdExpediente'].'-'.$gdExpediente['numeroGdExpediente'];

                            # Datos del expediente para el formato unico de inventario

                            $gaArchivo = GaArchivo::find();
                            $gaArchivo = HelperQueryDb::getQuery('innerJoin', $gaArchivo, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gaArchivo' => 'idGdExpediente']);
                            $gaArchivo = $gaArchivo->where(['gdExpedientesInclusion.idGdExpediente' => $gdExpediente->idGdExpediente])->one();

                            if(!isset($gaArchivo)){
                                $transaction->rollBack();
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

                                foreach($gdExpediente->gdExpedientesInclusion as $key => $expedientesInclusion){
                                    # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                                    $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $key;
                                    $numeroFolios = $numeroFolios + $expedientesInclusion->radiRadicado['foliosRadiRadicado'];
                                }

                                if(count($idRadiRadicados) > 0){
                                    $min = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[min(array_keys($idRadiRadicados))]]->radiRadicado;
                                    $max = $gdExpediente->gdExpedientesInclusion[$idRadiRadicados[max(array_keys($idRadiRadicados))]]->radiRadicado;
                                    $fechasExtremas = $min['creacionRadiRadicado'].'@'.$max['creacionRadiRadicado'];
                                }

                            }

                            /***  Notificacion  ***/
                            HelperNotification::addNotification(
                                Yii::$app->user->identity->id, //Id user creador
                                $gdExpediente->idUser, // Id user notificado
                                Yii::t('app','messageNotification')['transferRejected'], //Notificacion
                                Yii::$app->params['routesFront']['viewExpediente'], // url
                                $gdExpediente->idGdExpediente // id expediente
                            );
                            /***  Fin Notificacion  ***/


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
                                'inicioProceso' =>  date("Y-m-d H:i A", strtotime($gdExpediente['fechaProcesoGdExpediente'])),
                                'idUser' => $gdExpediente->user->userDetalles['nombreUserDetalles'].' '.$gdExpediente->user->userDetalles['apellidoUserDetalles']
                            ];

                            $transaction->commit();

                            $notificacion[] =  [
                                'message' => Yii::t('app', 'successTransExp', [
                                    'nomExp' => $gdExpediente['numeroGdExpediente'].' - '.$gdExpediente['nombreGdExpediente']
                                ]),
                                'type' => 'success'
                            ];
        
                            # Se retorna el estado de cada registro
                            $dataResponse[] = array(
                                'id' => $expediente['id'],
                                'idInitialList' => ($expediente['idInitialList'] ?? 0) * 1,
                                'status' =>  $gdExpediente->estadoGdExpediente,
                                'statusText' => Yii::t('app', 'statusTodoNumber')[$gdExpediente->estadoGdExpediente], 
                            );
                        }

                    }
                }
              
                # Se debe enviarse un solo correo con el consolidado de todas las dependencias al usuario creador
                foreach($dataTiempoCentral as $dependencia => $data){
                    
                    # Envia la notificación de correo electronico                 

                    /** 
                     * Se debe enviar una notificación de correo electrónico indicando lo siguiente: 
                     * 
                     * En el asunto "Transferencia Rechazada" en el titulo del correo "Se rechazo la transferencia documental" 
                     * y en el cuerpo debe decir 
                     * 
                     * "Señor usuario se rechazo su tranferencia documental por la siguiente razón: 
                     * (Observación dilogenciada), 
                     * por favor verificar y hacer nuevamente la tranferencia."  
                     * 
                     * Este mensaje se debe enviar al usuario creador del expediente.
                     */

                    $bodyMail = 'radicacion-html';
                    
                    # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                    $headMailText = Yii::t('app', 'headMailTextExpRechazoTitle'); 
                    $textBody  = Yii::t('app', 'mailEventExpRechazo',[
                        'observa' => $request['data']['observacion']
                    ]);
                    $subject = Yii::t('app', 'headMailTextExpRechazo');
                    
                    # boton de redireccion en el correo
                    $buttonDisplay = false;

                    # Enviar una notificación de correo electrónico al usuario responsable o creador el expediente
                    foreach($data['idGdExpediente'] as $key => $idGdExpediente){
                        
                        #consultar en el log de espedientes la operacion crear expediente
                        $gdHistoricoExpedientes = GdHistoricoExpedientes::find()->where(['idGdExpediente' => $idGdExpediente, 
                        'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['CrearExpediente']])->one();
                        $emailCreador = $gdHistoricoExpedientes->userHistoricoExpediente['email'];
                        
                        # validar envio de notificacion dublicada para el usuario creador
                        $userNotificado[] = $emailCreador;
                        $envioCorreo[] = CorreoController::addFile($emailCreador, $headMailText, $textBody, $bodyMail, null, $subject, $buttonDisplay, false);
                        
                    }
                    
                }


                $observacion = Yii::$app->params['eventosLogText']['transferenciaDocumentalRechazada'].$request['data']['observacion'];
                $dataLog = ""; $nombreGdExpediente = "";

                # Guardar log de auditoria 
                foreach($dataTiempoCentral as $dependencia => $data){
                    
                    $nombreGdExpediente = implode(', ', $data['nombreGdExpediente']);

                    $oficinaProductora = $data['oficinaProductora'];
                    $codigoOficina = $data['codigoOficina'];

                    $dataLog  = '  Expedientes: '.$nombreGdExpediente;
                    $dataLog .= ', Dependencia: '.$codigoOficina.'-'.$oficinaProductora;
                    $dataLog .= ', observacion: '.$request['data']['observacion'];               
                    $dataLog .= ', Estado: '.'Transferencia Rechazada';

                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    str_replace('{nomExp}', $nombreGdExpediente, $observacion).' '.$data['oficinaProductora'],
                    "", //DataOld
                    $dataLog, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'notificacion' => $notificacion ?? [],
                    'dataResponse' => $dataResponse ?? [],
                    'data' => [],
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
     * Pendiente por Transferencia
     * Este servicio es un CRON 
     * @return array message success 
     */

    public function actionPendienteTransferir(){

        $gdExpedientes = [];
        $dataTiempoCentral = [];
        $logSistema = [];
        $hoy = new DateTime(date('Y-m-d'));

        ##############################  Inicio Gestion de archivo  ############################## 

        $gdExpedientes = GdExpedientes::find()
        ->where(['NOT IN','ubicacionGdExpediente', Yii::$app->params['ubicacionTransTRDNumber']['gestion']])
        ->andWhere(['estadoGdExpediente' => Yii::$app->params['statusTodoText']['Activo']])
        ->andWhere(['<=', 'tiempoGestionGdExpedientes', trim(date("Y-m-d"))])->all();

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
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
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
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
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
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
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
                    //$transaction->rollBack();
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

                    foreach($gdExpediente->gdExpedientesInclusion as $key => $expedientesInclusion){
                        # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                        $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $key;
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


        $envioCorreo = $this->notificarPendienteTransferencia($dataTiempoCentral, true);

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => Yii::t('app','successSave'),
            'correo' => $envioCorreo,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Descargar Formato unico de inventario PDF
     * 
     * @param array   ButtonSelectedData    
     * 
     * @return array message success 
     */

    public function actionDownloadFuit(){

      if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

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

            $dataTiempoCentral = [];

            foreach($request['ButtonSelectedData'] as $key => $expediente){

                $fechasExtremas = '';
                $numeroFolios = 0;

                $gdExpediente = GdExpedientes::find()->where(['idGdExpediente' => $expediente['id']])->one();

                # Encabezado del formato unico de inventario
                $dependencia = $gdExpediente->gdTrdDependencia['nombreGdTrdDependencia'];
                $oficina = $gdExpediente->gdTrdDependencia['codigoGdTrdDependencia'];
                # Encabezado del formato unico de inventario
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['oficinaProductora'] = $dependencia;   
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['codigoOficina'] = $oficina;   
            
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['idGdExpediente'][] = $gdExpediente['idGdExpediente'];

                # Datos para almacenar en el log del sistema                   
                $dataTiempoCentral[$gdExpediente->user['idGdTrdDependencia']]['nombreGdExpediente'][] = $gdExpediente['nombreGdExpediente'].'-'.$gdExpediente['numeroGdExpediente'];

                # Datos del expediente para el formato unico de inventario
                $gaArchivo = GaArchivo::find();
                $gaArchivo = HelperQueryDb::getQuery('innerJoin', $gaArchivo, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gaArchivo' => 'idGdExpediente']);
                $gaArchivo = $gaArchivo->where(['gdExpedientesInclusion.idGdExpediente' => $gdExpediente->idGdExpediente])->one();
                
                if(!isset($gaArchivo)){
                    //$transaction->rollBack();
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

                    foreach($gdExpediente->gdExpedientesInclusion as $key => $expedientesInclusion){
                        # obtengo el key de la relacion para obtener luego la inf del radicado sin agregar mas consulas
                        $idRadiRadicados[$expedientesInclusion['idRadiRadicado']] = $key;
                        # acumulador numero de folios
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

            }

            # Generar PDF 
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
                
                $userDetalles = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();
                $dataUser = [
                    'nombre' => $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'],
                    'cargo'  => $userDetalles['cargoUserDetalles'],
                    'fecha'  => date("Y-m-d H:i A")
                ];
                $footer = Yii::t('app','footerFormatoUnicoInv');

                PdfController::generar_pdf_formatoh('GestiondeCorrespondencia','formatoInventarioView', $filename, $pathUploadFile, $data, [], $dataUser, $footer);
            
                //Lee el archivo dentro de una cadena en base 64
                $dataFile[] = array( 
                    'datafile' => base64_encode(file_get_contents($pathUploadFile.''.$filename.'.pdf')),
                    'fileName' => Yii::$app->params['formUniInventario'].'.pdf'
                );

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','downloadFuit'),
                'data' => [], // data
                'fileName' => Yii::$app->params['formUniInventario'].'.pdf',
                'status' => 200,
            ];       

            $return = HelperEncryptAes::encrypt($response, true);

            $return['datafile'] = $dataFile;

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

    public function notificarPendienteTransferencia($dataTiempoCentral = [], $notificarCreador = true){

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
                
                $userDetalles = UserDetalles::find()->where(['idUser' => Yii::$app->user->identity->id])->one();
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
}
