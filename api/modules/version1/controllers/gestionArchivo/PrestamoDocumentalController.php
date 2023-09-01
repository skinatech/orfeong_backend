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

namespace api\modules\version1\controllers\gestionArchivo;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperNotification;
use api\components\HelperQueryDb;
use api\components\HelperRadicacion;
use api\components\HelperConsecutivo;
use api\models\CgTransaccionesRadicados;
use api\models\GaArchivo;
use api\models\GaHistoricoPrestamo;
use api\models\GaPrestamos;
use api\models\GdExpedientesInclusion;
use api\models\GdTrdDependencias;
use api\models\GdExpedientes;
use api\models\GaPrestamosExpedientes;
use api\models\GaHistoricoPrestamoExpediente;
use api\models\User;
use common\models\User as UserValidate;  // Validación password
use api\modules\version1\controllers\correo\CorreoController;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;


/**
 * PrestamoDocumentalController implements the CRUD actions.
 */
class PrestamoDocumentalController extends Controller
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
                    'index'  => ['GET'],
                    'index-one'  => ['GET'],
                    'request-loan'  => ['POST'],
                    'index-manage-loan'  => ['GET'],
                    'approve-loan'  => ['POST'],
                    'index-list-type'  => ['GET'],
                    'index-list-request'  => ['GET'],
                    'index-loan-files'  => ['GET'],
                    'request-loan-files'  => ['POST'],
                    'historical-loan-files' => ['GET'],
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
     * Lists all Prestamo Documental models.
     * @return mixed
     */
    public function actionIndex($request)
    {    
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            // El $request obtiene $response = ['filterOperation' => [["idGdExpediente"=> "nombreExpediente",
            // "estadoGaPrestamo"=> 12, "idGdTrdTipoDocumental" =>1, ]] ];
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
  
            //Lista de campos de la configuracion
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            # Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if ( is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info){

                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        if ($key == 'idGdTrdTipoDocumental') {
                            if (is_array($info) && !empty($info)) {
                                foreach ($info as $rowInfo) {
                                    if (is_array($rowInfo)) {
                                        foreach ($rowInfo as $rowInfo2) {
                                            $dataWhere[$key][] =  $rowInfo2;
                                        }
                                    } else {
                                        $dataWhere[$key][] = $rowInfo;
                                    }
                                }
                            }
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if( isset($info) && $info !== null && trim($info) !== ''){
                                $dataWhere[$key] =  $info;
                            }

                        } else {
                             if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Ids de estados de prestamos
            $statusLoan = [
                Yii::$app->params['statusTodoText']['SolicitudPrestamo'],
                Yii::$app->params['statusTodoText']['PrestamoAprobado'],
                Yii::$app->params['statusTodoText']['PrestamoCancelado'],
                Yii::$app->params['statusTodoText']['PrestamoDevuelto']
            ];

            # Consulta para obtener los ids incluidos en expedientes que ya tienen un estado de prestamo.
            $modelLoan = GaPrestamos::findAll(['estadoGaPrestamo' => $statusLoan]);

            $arrayIds = [];
            foreach($modelLoan as $id) {
                $arrayIds[] = $id['idGdExpedienteInclusion'];
            }

            # Consulta la accion de la transaccion del estado para obtener su id
            $modelTransacion = CgTransaccionesRadicados::find()
                                ->where(['actionCgTransaccionRadicado' =>'finalizeFiling'])
                                ->orWhere(['actionCgTransaccionRadicado' =>'archiveFiling'])
                                ->one();
            $idTransacion = $modelTransacion->idCgTransaccionRadicado;

            # Relacionamiento de prestamos documentales
            $relationArchive = GdExpedientesInclusion::find();
                // ->innerJoin('radiRadicados', 'radiRadicados.idRadiRadicado = gdExpedientesInclusion.idRadiRadicado')
                $relationArchive = HelperQueryDb::getQuery('innerJoin', $relationArchive, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'gdExpedientesInclusion' => 'idRadiRadicado']);
                // ->innerJoin('radiLogRadicados', 'radiLogRadicados.idRadiRadicado = radiRadicados.idRadiRadicado')   
                $relationArchive = HelperQueryDb::getQuery('innerJoin', $relationArchive, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
                // ->innerJoin('gdExpedientes', 'gdExpedientes.idGdExpediente = gdExpedientesInclusion.idGdExpediente')
                $relationArchive = HelperQueryDb::getQuery('innerJoin', $relationArchive, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);
                // ->innerJoin('gaArchivo', 'gdExpedientesInclusion.idGdExpedienteInclusion = gaArchivo.idGdExpedienteInclusion')
                $relationArchive = HelperQueryDb::getQuery('innerJoin', $relationArchive, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gdExpedientes' => 'idGdExpediente']);
                // ->leftJoin('gaPrestamos', 'gaPrestamos.idGdExpedienteInclusion = gdExpedientesInclusion.idGdExpedienteInclusion')
                $relationArchive = HelperQueryDb::getQuery('leftJoin', $relationArchive, 'gaPrestamos', ['gaPrestamos' => 'idGdExpedienteInclusion', 'gdExpedientesInclusion' => 'idGdExpedienteInclusion']);
                // ->innerJoin('gdTrdTiposDocumentales', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = radiRadicados.idTrdTipoDocumental')
                $relationArchive = HelperQueryDb::getQuery('leftJoin', $relationArchive, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'radiRadicados' => 'idTrdTipoDocumental']);

                $relationArchive = $relationArchive->where(['radiRadicados.estadoRadiRadicado' => Yii::$app->params['statusTodoText']['Archivado']])
                //sguarin para mostrar todos los radicados archivados en solicitud de prestamos documental
                ->andWhere(['radiLogRadicados.idTransaccion' => $idTransacion]);

            # Se reitera $dataWhere para solo obtener los campos con datos
            $filterTrdDependencias = false;
            foreach ($dataWhere as $field => $value) {
                switch ($field) {
                    case 'idGdExpediente':
                        $relationArchive->andWhere([Yii::$app->params['like'], 'gdExpedientes.'. 'nombreGdExpediente', $value]);
                    break;
                    case 'estadoGaPrestamo':
                        if($value == 12){
                            $relationArchive->andWhere(['NOT IN', 'gdExpedientesInclusion.idGdExpedienteInclusion', $arrayIds ])
                           ->andWhere(['IN', 'radiRadicados.'. 'estadoRadiRadicado', intval($value) ]);
                        } else {
                            $relationArchive->andWhere(['IN', 'gaPrestamos.'. $field, intval($value) ]);
                        }
                    break; 
                    case 'fechaInicial':
                        $relationArchive->andWhere(['>=', 'radiLogRadicados.fechaRadiLogRadicado', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationArchive->andWhere(['<=', 'radiLogRadicados.fechaRadiLogRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    case 'idGdTrdTipoDocumental':
                        $relationArchive->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                    break;
                    case 'idGdTrdDependencia':
                        $relationArchive->andWhere(['IN', 'gdExpedientes.' . $field, $value]);
                        $filterTrdDependencias = true;
                    break;
                    default:
                        $relationArchive->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                    break;
                }
            }
            // Si se elije una dependecia se omite esta filtro del usuario
            if (!$filterTrdDependencias) {
                $relationArchive->andWhere(['radiLogRadicados.idUser' => Yii::$app->user->identity->id]);
            }

            # Orden descendente para ver los últimos registros creados
            $relationArchive->orderBy(['gdExpedientesInclusion.idGdExpedienteInclusion' => SORT_DESC]); 

            # Limite de la consulta
            $relationArchive->limit($limitRecords);
            $modelRelation = $relationArchive->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $dataRelation) {

                # Fecha en que finaliza tramite del radicado
                $finalDate = '';
                foreach($dataRelation->radiRadicado->radiLogRadicados as $radiLog) {
                    $finalDate = $radiLog->fechaRadiLogRadicado;
                }
                
                # Estado del préstamo documental
                $statusLoan = 100;
                foreach($dataRelation->gaPrestamos as $prestamo){
                    $statusLoan = $prestamo->estadoGaPrestamo;
                }

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGdExpedienteInclusion)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'                => $dataBase64Params,
                    'id'                  => $dataRelation->idGdExpedienteInclusion,
                    'filingNumber'        => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->radiRadicado->numeroRadiRadicado, $dataRelation->radiRadicado->idCgTipoRadicado, $dataRelation->radiRadicado->isRadicado),
                    'settledFinished'     => $finalDate, //fecha del log
                    'subject'             => (strlen($dataRelation->radiRadicado->asuntoRadiRadicado) > 150) ? substr($dataRelation->radiRadicado->asuntoRadiRadicado, 0, 150) . '...' : $dataRelation->radiRadicado->asuntoRadiRadicado,
                    'documentaryType'     => $dataRelation->radiRadicado->trdTipoDocumental->nombreTipoDocumental,
                    'fileName'            => $dataRelation->gdExpediente->nombreGdExpediente,
                    'warehouse'           => $dataRelation->gdExpediente->gaArchivo->gaBodega->nombreGaBodega,
                    'rack'                => $dataRelation->gdExpediente->gaArchivo->rackGaArchivo,
                    'shelf'               => $dataRelation->gdExpediente->gaArchivo->entrepanoGaArchivo,
                    'box'                 => $dataRelation->gdExpediente->gaArchivo->cajaGaArchivo,
                    'idStatusPrestamo'    => $statusLoan,
                    'statusText'          => Yii::t('app', 'statusTodoNumber')[$dataRelation->radiRadicado->estadoRadiRadicado].' - '. Yii::t('app', 'statusTodoNumber')[$statusLoan],
                    'status'              => $statusLoan,
                    'rowSelect'           => false,
                    'idInitialList'       => 0,
                    'idGdExpediente'      => $dataRelation->gdExpediente->idGdExpediente,
                );
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexPrestamoDocumental');
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Solicitar préstamos de expediente
     */
    public function actionRequestLoanFiles() {
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

                $saveDataValid = true;
                $errors = [];
                $numFiles = [];
                $transaction = Yii::$app->db->beginTransaction();

                # Consulta para obtener los ids y correos de los usuarios con rol "Administrador de Gestión Documental"
                $modelUser = User::find()->select(['email', 'id']);
                    // ->innerJoin('roles', 'roles.idRol = user.idRol')
                    $modelUser = HelperQueryDb::getQuery('innerJoin', $modelUser, 'roles', ['roles' => 'idRol', 'user' => 'idRol'])
                    ->where(['roles.nombreRol' => Yii::$app->params['tipoUsuarioText'][2]])
                    ->andWhere(['<>','user.id', Yii::$app->user->identity->id]);
                $modelUser = $modelUser->asArray()->all();

                $emails = [];
                $idsUser = []; //ids de los usuarios para la sección de notificaciones
                foreach($modelUser as $key => $infoUser) {
                    $emails[] = $infoUser['email'];
                    $idsUser[] = $infoUser['id'];
                }

                # Se agrega registros en prestamo documental
                foreach($request['ButtonSelectedData'] as $expediente) {
                    # Registros seleccionados por el usuario
                    $idExpediente = $expediente['id'];
                    $observacion = $request['data']['observacion'];
                    $idTipo = $request['data']['tipoPrestamo'];
                    $idRequerimiento = $request['data']['requerimiento'];

                    /** Valida si el radicado ya tiene una solicitud de préstamo 
                     * o tiene un prestamo aprobado que no se ha devuelto */
                    $validLoan = GaPrestamosExpedientes::find()
                        ->select(['idGaPrestamoExpediente', 'estadoGaPrestamoExpediente'])
                        ->where(['idGdExpediente' => $idExpediente])
                        ->andWhere(['or', ['estadoGaPrestamoExpediente' => Yii::$app->params['statusTodoText']['SolicitudPrestamo']], ['estadoGaPrestamoExpediente' => Yii::$app->params['statusTodoText']['PrestamoAprobado']], ['estadoGaPrestamoExpediente' => Yii::$app->params['statusTodoText']['PrestamoPorAutorizar']]])
                        ->limit(1)
                        ->one();

                    # Si cumple con la validación anterior, no permite realizar una solicitud de ese prestamo
                    if(!is_null($validLoan)) {
                        // Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $idExpediente,
                            'status' => $validLoan->estadoGaPrestamoExpediente,
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$validLoan->estadoGaPrestamoExpediente],
                            'idInitialList' => $expediente['idInitialList'] * 1
                        ); 

                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorApplyLoan')]],
                            'dataResponse' => $dataResponse,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Fin Valida si el radicado ya tiene una solicitud de préstamo */

                     # Crea la solicitud del prestamo
                    $modelLoan = new GaPrestamosExpedientes();
                    $modelLoan->idGdExpediente = $idExpediente;
                    $modelLoan->idUser = Yii::$app->user->identity->id;
                    $modelLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelLoan->fechaSolicitudGaPrestamoExpediente = date('Y-m-d');
                    $modelLoan->idTipoPrestamoGaPrestamoExpediente = $idTipo;
                    $modelLoan->idRequerimientoGaPrestamoExpediente = $idRequerimiento;
                    $modelLoan->observacionGaPrestamoExpediente = $observacion;
                    $modelLoan->creacionGaPrestamoExpediente = date('Y-m-d H:i:s');
                    
                    $modelGdExpedientes = GdExpedientes::find()->where(['idGdExpediente' => $idExpediente])->one();
                    if(($modelGdExpedientes->ubicacionGdExpediente === Yii::$app->params['ubicacionTransTRDNumber']['central']) && ($modelGdExpedientes->idGdTrdDependencia !== Yii::$app->user->identity->idGdTrdDependencia)) {
                        // Ajustando el estado
                        $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['PrestamoPorAutorizar'];
                        // Validando el mail y id del usuario dueño del expediente
                        $modelUserCreadorExpediente = User::find()->where(['id' => $modelGdExpedientes->idUser])->one();
                        $emails[] = $modelUserCreadorExpediente->email;
                        $idsUser[] = $modelUserCreadorExpediente->id;
                    } else {
                        $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['SolicitudPrestamo'];
                    }

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    // Guardar el historial préstamo de expediente
                    $modelHistoricalLoan = new GaHistoricoPrestamoExpediente();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamoExpediente = $modelLoan->idGaPrestamoExpediente;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamoExpediente = date('Y-m-d');
                    $modelHistoricalLoan->observacionGaHistoricoPrestamoExpediente = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamoExpediente = $modelLoan->estadoGaPrestamoExpediente;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamoExpediente = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    $userName = Yii::$app->user->identity->userDetalles->nombreUserDetalles.' '.Yii::$app->user->identity->userDetalles->apellidoUserDetalles;
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . " de solicitud de préstamo al expediente: ". $expediente['fileName'] .", realizada por el usuario: ".$userName." de la dependencia: ". $expediente['dependency'] . ", en la tabla de Gaprestamosexpedientes", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/
           
                    /***  Notificacion  ***/
                    foreach($idsUser as $idUser){
                        HelperNotification::addNotification(
                            Yii::$app->user->identity->id, //Id user creador
                            $idUser, // Id user notificado
                            Yii::t('app','messageNotification')['loanFile'] . $expediente['fileName'], //Notificacion
                            Yii::$app->params['routesFront']['indexAdminLoanFile'], // URL
                            '' // id radicado
                        );
                    }
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $idExpediente,
                        'status' => $modelLoan->estadoGaPrestamoExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamoExpediente],
                        'idInitialList' => $expediente['idInitialList'] * 1
                    );

                    $nameExpedientes[] = $expediente['fileName'];
                    $dependencia = $expediente['dependency'];
                }

                # Modificación de mensajes del correo
                $nameExpedientesImplode = implode(', ', $nameExpedientes);
                if (count($request['ButtonSelectedData']) > 1) {
                    $headMailText  = Yii::t('app', 'headMailsDocumentaryLoanFile');
                    $textBody  = Yii::t('app', 'textsBodyDocumentaryLoanFile', [
                        'expedientes'    => $nameExpedientesImplode,
                        'observacion'    => $observacion,
                        'user'           => $userName,
                        'dependencia'    => $dependencia,
                    ]);

                } else {
                    $headMailText  = Yii::t('app', 'headMailDocumentaryLoanFile', [
                        'expediente'    => $nameExpedientesImplode,
                    ]);

                    $textBody  = Yii::t('app', 'textBodyDocumentaryLoanFile', [
                        'requerimiento'  => Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamoExpediente],
                        'expediente'    => $nameExpedientesImplode,
                        'observacion'    => $observacion,
                        'user'           => $userName,
                        'dependencia'    => $dependencia,
                    ]);
                }
              
                $bodyMail = 'radicacion-html';
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexAdminLoanFile'];
                $subject = 'subjectDocumentaryLoanFile';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida

                $envioCorreo = [];
                foreach($emails as $email){
                    $envioCorreo[] = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                }
                # Fin de envio de correos masivamente 

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {
                    $transaction->commit();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al solicitar prestamo
     */
    public function actionRequestLoan()
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

                $saveDataValid = true;
                $errors = [];
                $numFiles = [];
                $transaction = Yii::$app->db->beginTransaction();

                # Consulta para obtener los ids y correos de los usuarios con rol "Administrador de Gestión Documental"
                $modelUser = User::find()->select(['email', 'id']);
                    // ->innerJoin('roles', 'roles.idRol = user.idRol')
                    $modelUser = HelperQueryDb::getQuery('innerJoin', $modelUser, 'roles', ['roles' => 'idRol', 'user' => 'idRol'])
                    ->where(['roles.nombreRol' => Yii::$app->params['tipoUsuarioText'][2]])
                    ->andWhere(['<>','user.id', Yii::$app->user->identity->id]);
                $modelUser = $modelUser->asArray()->all();
                
                $emails = [];
                $idsUser = []; //ids de los usuarios para la sección de notificaciones
                foreach($modelUser as $key => $infoUser) {
                    $emails[] = $infoUser['email'];
                    $idsUser[] = $infoUser['id'];
                }

                # Se agrega registros en prestamo documental
                foreach($request['ButtonSelectedData'] as $expediente){
                    
                    # Registros seleccionados por el usuario
                    $idExpediente = $expediente['id'];
                    $observacion = $request['data']['observacion'];
                    $idTipo = $request['data']['tipoPrestamo'];
                    $idRequerimiento = $request['data']['requerimiento'];
                    $idGdExpediente = $expediente['idGdExpediente'];

                    /** Valida si el radicado ya tiene una solicitud de préstamo 
                     * o tiene un prestamo aprobado que no se ha devuelto */
                    $validLoan = GaPrestamos::find()
                        ->select(['idGaPrestamo', 'estadoGaPrestamo'])
                        ->where(['idGdExpedienteInclusion' => $idExpediente])
                        ->andWhere(['or', ['estadoGaPrestamo' => Yii::$app->params['statusTodoText']['SolicitudPrestamo']], ['estadoGaPrestamo' => Yii::$app->params['statusTodoText']['PrestamoAprobado']], ['estadoGaPrestamo' => Yii::$app->params['statusTodoText']['PrestamoPorAutorizar']]])
                        ->limit(1)
                    ->one();

                    # Si cumple con la validación anterior, no permite realizar una solicitud de ese prestamo
                    if (!is_null($validLoan)) {

                        // Se retorna el estado de cada registro
                        $dataResponse[] = array(
                            'id' => $idExpediente,
                            'status' => $validLoan->estadoGaPrestamo,
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$validLoan->estadoGaPrestamo],
                            'idInitialList' => $expediente['idInitialList'] * 1
                        ); 

                        $transaction->rollBack();

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'errorApplyLoan')]],
                            'dataResponse' => $dataResponse,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /** Fin Valida si el radicado ya tiene una solicitud de préstamo */

                    # Crea la solicitud del prestamo
                    $modelLoan = new GaPrestamos();
                    $modelLoan->fechaSolicitudGaPrestamo = date('Y-m-d');
                    $modelLoan->idUser = Yii::$app->user->identity->id;
                    $modelLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelLoan->idGdExpedienteInclusion = $idExpediente;
                    $modelLoan->idTipoPrestamoGaPrestamo = $idTipo;
                    $modelLoan->idRequerimientoGaPrestamo = $idRequerimiento;
                    $modelLoan->observacionGaPrestamo = $observacion;
                    /** Consulta para validar el expediente */
                    $modelGdExpedientes = GdExpedientes::find()->where(['idGdExpediente' => $idGdExpediente])->one();
                    if(($modelGdExpedientes->ubicacionGdExpediente === Yii::$app->params['ubicacionTransTRDNumber']['central']) && ($modelGdExpedientes->idGdTrdDependencia !== Yii::$app->user->identity->idGdTrdDependencia)) {
                        // Ajustando el estado
                        $modelLoan->estadoGaPrestamo = Yii::$app->params['statusTodoText']['PrestamoPorAutorizar'];
                        // Validando el mail y id del usuario dueño del expediente
                        $modelUserCreadorExpediente = User::find()->where(['id' => $modelGdExpedientes->idUser])->one();
                        $emails[] = $modelUserCreadorExpediente->email;
                        $idsUser[] = $modelUserCreadorExpediente->id;
                    }

                    # Se agrupa todos los radicados seleccionados
                    $numFiles[] = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado; 

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se almacena el historico por cada idPrestamo.
                    $loanCreated = GaPrestamos::findOne(['idGaPrestamo' => $modelLoan->idGaPrestamo]);

                    $modelHistoricalLoan = new GaHistoricoPrestamo();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamo = $loanCreated->idGaPrestamo;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamo = date('Y-m-d');
                    $modelHistoricalLoan->observacionGaHistoricoPrestamo = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamo = $loanCreated->estadoGaPrestamo;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamo = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    # Numero radicado, usuario y dependencia para el log
                    $numberFile = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                    $userName = Yii::$app->user->identity->userDetalles->nombreUserDetalles.' '.Yii::$app->user->identity->userDetalles->apellidoUserDetalles;

                    $modelDependencia = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelLoan->idGdTrdDependencia]);
                    $dependencia = $modelDependencia->nombreGdTrdDependencia;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", de solicitud de préstamo al radicado: ".$numberFile." realizada por el usuario: ".$userName." de la dependencia: ".$dependencia. ", en la tabla de GaPrestamos", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/
           
                    /***  Notificacion  ***/
                    foreach($idsUser as $idUser){
                        HelperNotification::addNotification(
                            Yii::$app->user->identity->id, //Id user creador
                            $idUser, // Id user notificado
                            Yii::t('app','messageNotification')['loanRequest'].$numberFile, //Notificacion
                            Yii::$app->params['routesFront']['indexAdminLoan'], // URL
                            '' // id radicado
                        );
                    }
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $idExpediente,
                        'status' => $loanCreated->estadoGaPrestamo,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$loanCreated->estadoGaPrestamo],
                        'idInitialList' => $expediente['idInitialList'] * 1
                    ); 
                }
               
                # Envio de correos masivamente o cuando se escoge un radicado
                $numRadicado = implode(', ',$numFiles);

                # Modificación de mensajes del correo
                if ( count($numFiles) > 1) {
                    $headMailText  = Yii::t('app', 'headMailsDocumentaryLoan');
                    $textBody  = Yii::t('app', 'textsBodyDocumentaryLoan', [
                        'numRadicado'    => $numRadicado,
                        'observacion'    => $observacion,
                        'user'           => $userName,
                        'dependencia'    => $dependencia,
                    ]);

                } else {
                    $headMailText  = Yii::t('app', 'headMailDocumentaryLoan', [
                        'numRadicado'    => $numRadicado,
                    ]);

                    $textBody  = Yii::t('app', 'textBodyDocumentaryLoan', [
                        'requerimiento'  => Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamo],
                        'numRadicado'    => $numRadicado,
                        'observacion'    => $observacion,
                        'user'           => $userName,
                        'dependencia'    => $dependencia,
                    ]);
                }
              
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoan']; 
                $subject = 'subjectDocumentaryLoan';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida

                $envioCorreo = [];
                foreach($emails as $email){
                    $envioCorreo[] = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                }
                # Fin de envio de correos masivamente 

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Index para préstamo de expedientes
     */
    public function actionIndexManageLoanFiles($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            # Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if(is_array($request)) {
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
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            //Lista de campos de la configuracion
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            $relationPrestamos = GaPrestamosExpedientes::find();
            $relationPrestamos = HelperQueryDb::getQuery('innerJoin', $relationPrestamos, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gaPrestamosExpedientes' => 'idGdExpediente']);
            $relationPrestamos = $relationPrestamos->where(['>', 'gaPrestamosExpedientes.idGaPrestamoExpediente', 0]);

            # Se reitera $dataWhere para solo obtener los campos con datos
            $filterTrdDependencias = false;
            foreach($dataWhere as $field => $value) {
                switch ($field) {
                    case 'ubicacionGdExpediente':
                        $relationPrestamos->andWhere(['=', 'gdExpedientes.ubicacionGdExpediente', intval($value)]);
                    break;
                    case 'idTipoPrestamoGaPrestamoExpediente':
                        $relationPrestamos->andWhere(['IN', 'gaPrestamosExpedientes.'. $field, intval($value)]);
                    break;
                    case 'idGdTrdDependencia':
                        $relationPrestamos->andWhere(['IN', 'gaPrestamosExpedientes.'. $field, $value]);
                        $filterTrdDependencias = true;
                    break;
                    case 'estadoGaPrestamoExpediente':
                        $relationPrestamos->andWhere(['IN', 'gaPrestamosExpedientes.'. $field, intval($value)]);
                    break;
                    case 'fechaInicial':
                        $relationPrestamos->andWhere(['>=', 'gaPrestamosExpedientes.creacionGaPrestamoExpediente', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationPrestamos->andWhere(['<=', 'gaPrestamosExpedientes.creacionGaPrestamoExpediente', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $relationPrestamos->andWhere([Yii::$app->params['like'], 'gdExpedientes.' . $field, $value]);
                    break;
                }
            }

            if(!$filterTrdDependencias) {
                $relationPrestamos = $relationPrestamos->andWhere(['=', 'gaPrestamosExpedientes.idGdTrdDependencia', Yii::$app->user->identity->idGdTrdDependencia]);
            }

            if(count($dataWhere) === 0) {
                $relationPrestamos = $relationPrestamos->andWhere(['or', ['gaPrestamosExpedientes.estadoGaPrestamoExpediente' => Yii::$app->params['statusTodoText']['SolicitudPrestamo']], ['gaPrestamosExpedientes.estadoGaPrestamoExpediente' => Yii::$app->params['statusTodoText']['PrestamoPorAutorizar']]]);
            }

            # Orden descendente para ver los últimos registros creados
            $relationPrestamos->orderBy(['gaPrestamosExpedientes.idGaPrestamoExpediente' => SORT_DESC]);

            # Limite de la consulta
            $relationPrestamos->limit($limitRecords);
            $modelRelation = $relationPrestamos->all();

            $userPermisoArchivoCentral = false;
            if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsLoanOfFilesCentralFile'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
                $formType = HelperDynamicForms::setListadoBD('indexAdminPrestamoExpedientesCentral');
                $userPermisoArchivoCentral = true;
            } else {
                $formType = HelperDynamicForms::setListadoBD('indexAdminPrestamoExpedientes');
            }

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);
            foreach($modelRelation as $dataRelation) {
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaPrestamoExpediente)),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'                   => $dataBase64Params,
                    'id'                     => $dataRelation->idGaPrestamoExpediente,
                    'fileNumber'             => $dataRelation->idGdExpediente0->numeroGdExpediente,
                    'fileName'               => $dataRelation->idGdExpediente0->nombreGdExpediente,
                    'dependency'             => $dataRelation->idGdExpediente0->gdTrdDependencia->nombreGdTrdDependencia,
                    'loanType'               => Yii::t('app', 'statusLoanTypeNumber')[$dataRelation->idTipoPrestamoGaPrestamoExpediente],
                    'idLoanType'             => $dataRelation->idTipoPrestamoGaPrestamoExpediente,
                    'loanDate'               => $dataRelation->fechaSolicitudGaPrestamoExpediente,
                    'request'                => Yii::t('app', 'statusLoanRequirementText')[$dataRelation->idRequerimientoGaPrestamoExpediente],
                    'store'                  => $dataRelation->idGdExpediente0->gaArchivo->gaBodega->nombreGaBodega,
                    'rack'                   => $dataRelation->idGdExpediente0->gaArchivo->rackGaArchivo,
                    'shelf'                  => $dataRelation->idGdExpediente0->gaArchivo->entrepanoGaArchivo,
                    'box'                    => $dataRelation->idGdExpediente0->gaArchivo->cajaGaArchivo,
                    'idUser'                 => $dataRelation->idUser,
                    'user'                   => $dataRelation->idUser0->userDetalles->nombreUserDetalles.' '.$dataRelation->idUser0->userDetalles->apellidoUserDetalles,
                    'idUserLoginDependency'  => Yii::$app->user->identity->idGdTrdDependencia,
                    'idDependencyExpediente' => $dataRelation->idGdExpediente0->idGdTrdDependencia,
                    'statusText'             => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoGaPrestamoExpediente],
                    'status'                 => $dataRelation->estadoGaPrestamoExpediente,
                    'rowSelect'              => false,
                    'idInitialList'          => 0,
                    'isPrestamoFisico'       => ($dataRelation->idTipoPrestamoGaPrestamoExpediente === Yii::$app->params['statusLoanTypeText']['PrestamoFisico']) ? true : false,
                    'permisoArchivoCentral'  => $userPermisoArchivoCentral
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
                'route' => Yii::$app->controller->route
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Index para préstamo de expedientes
     */
    public function actionIndexLoanFiles($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            # Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if(is_array($request)) {
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
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            //Lista de campos de la configuracion
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            $relationExpediente = GdExpedientes::find();
            $relationExpediente = HelperQueryDb::getQuery('innerJoin', $relationExpediente, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gdExpedientes' => 'idGdExpediente']);
            $relationExpediente = HelperQueryDb::getQuery('leftJoin', $relationExpediente, 'gaPrestamosExpedientes', ['gaPrestamosExpedientes' => 'idGdExpediente', 'gdExpedientes' => 'idGdExpediente']);
            $relationExpediente = $relationExpediente->where(['>', 'gdExpedientes.idGdExpediente', 0]);

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value) {
                switch ($field) {
                    case 'idGdTrdDependencia':
                        $relationExpediente->andWhere(['IN', 'gdExpedientes.'. $field, $value]);
                    break;
                    case 'estadoGaPrestamoExpediente':
                        $relationExpediente->andWhere(['IN', 'gaPrestamosExpedientes.'. $field, intval($value)]);
                    break;
                    case 'fechaInicial':
                        $relationExpediente->andWhere(['>=', 'gaPrestamosExpedientes.creacionGaPrestamoExpediente', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationExpediente->andWhere(['<=', 'gaPrestamosExpedientes.creacionGaPrestamoExpediente', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $relationExpediente->andWhere([Yii::$app->params['like'], 'gdExpedientes.' . $field, $value]);
                    break;
                }
            }

            # Orden descendente para ver los últimos registros creados
            $relationExpediente->orderBy(['gdExpedientes.idGdExpediente' => SORT_DESC]);

            # Limite de la consulta
            $relationExpediente->limit($limitRecords);
            $modelRelation = $relationExpediente->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);
            foreach($modelRelation as $dataRelation) {
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGdExpediente)),
                );

                # Estado del préstamo documental
                $statusLoan = 100;
                foreach($dataRelation->gaPrestamosExpediente as $prestamo){
                    $statusLoan = $prestamo->estadoGaPrestamoExpediente;
                }

                # Listado de informacion
                $dataList[] = array(
                    'data'             => $dataBase64Params,
                    'id'               => $dataRelation->idGdExpediente,
                    'fileNumber'       => $dataRelation->numeroGdExpediente,
                    'fileName'         => $dataRelation->nombreGdExpediente,
                    'user'             => $dataRelation->user->userDetalles->nombreUserDetalles.' '.$dataRelation->user->userDetalles->apellidoUserDetalles,
                    'dependency'       => $dataRelation->gdTrdDependencia->nombreGdTrdDependencia,
                    'store'            => $dataRelation->gaArchivo->gaBodega->nombreGaBodega,
                    'rack'             => $dataRelation->gaArchivo->rackGaArchivo,
                    'shelf'            => $dataRelation->gaArchivo->entrepanoGaArchivo,
                    'box'              => $dataRelation->gaArchivo->cajaGaArchivo,
                    'idStatusPrestamo' => $statusLoan,
                    'statusText'       => Yii::t('app', 'statusTodoNumber')[Yii::$app->params['statusTodoText']['Archivado']] ." - ". Yii::t('app', 'statusTodoNumber')[$statusLoan],
                    'status'           => $dataRelation->estadoGdExpediente,
                    'rowSelect'        => false,
                    'idInitialList'    => 0,
                );
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexPrestamoExpedientes');
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Index para la acción de Administrar Prestamo
     */
    public function actionIndexManageLoan($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
           
            // El $request obtiene $response = ['filterOperation' => [["idGdExpediente"=> "expediente",
            // "estadoGaPrestamo"=> 12, "idGdTrdTipoDocumental" => 1, "idUser" => 123456999]] ];
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
  
            //Lista de campos de la configuracion
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            # Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if ( is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info){

                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        if ($key == 'idGdTrdTipoDocumental') {
                            if (is_array($info) && !empty($info)) {
                                foreach ($info as $rowInfo) {
                                    if (is_array($rowInfo)) {
                                        foreach ($rowInfo as $rowInfo2) {
                                            $dataWhere[$key][] =  $rowInfo2;
                                        }
                                    } else {
                                        $dataWhere[$key][] =  $rowInfo;
                                    }
                                }
                            }
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if( isset($info) && $info !== null && trim($info) !== ''){
                                $dataWhere[$key] =  $info;
                            }

                        } else {
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Relacionamiento de prestamos documentales
            $relationLoan =  GaPrestamos::find(); 

                //->innerJoin('gdExpedientesInclusion', 'gdExpedientesInclusion.idGdExpedienteInclusion = gaPrestamos.idGdExpedienteInclusion')
                $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpedienteInclusion', 'gaPrestamos' => 'idGdExpedienteInclusion']);

                //->innerJoin('gaArchivo', 'gaArchivo.idGdExpedienteInclusion = gaPrestamos.idGdExpedienteInclusion')
                $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']); // CORRECCION RELACION
                // $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gaPrestamos' => 'idGdExpedienteInclusion']);

                //->innerJoin('radiRadicados', 'radiRadicados.idRadiRadicado = gdExpedientesInclusion.idRadiRadicado') 
                $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'gdExpedientesInclusion' => 'idRadiRadicado']);
                
                //->innerJoin('gdTrdTiposDocumentales', 'gdTrdTiposDocumentales.idGdTrdTipoDocumental = radiRadicados.idTrdTipoDocumental')
                $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'radiRadicados' => 'idTrdTipoDocumental']);

                //->innerJoin('gdExpedientes', 'gdExpedientes.idGdExpediente = gdExpedientesInclusion.idGdExpediente'); 
                $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gdExpedientes', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);

            $relationLoan = $relationLoan;

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {

                    case 'idGdExpediente':
                        $relationLoan->andWhere([Yii::$app->params['like'], 'gdExpedientes.'. 'nombreGdExpediente', $value]);
                    break;
                    case 'idGdTrdTipoDocumental':
                        $relationLoan->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                    break;  
                    case 'idTipoPrestamoGaPrestamo':
                    case 'idGdTrdDependencia':
                        $relationLoan->andWhere(['IN', 'gaPrestamos.' . $field, $value]);
                    break;
                    case 'idUser':
                        // $relationLoan->leftJoin('userDetalles', 'userDetalles.idUser = gaPrestamos.idUser')
                        $relationLoan = HelperQueryDb::getQuery('leftJoin', $relationLoan, 'userDetalles', ['userDetalles' => 'idUser', 'gaPrestamos' => 'idUser']);
                        $relationLoan = $relationLoan->andWhere([ 'or', [ Yii::$app->params['like'],'userDetalles.nombreUserDetalles', $value ], [ Yii::$app->params['like'], 'userDetalles.apellidoUserDetalles', $value ],[ Yii::$app->params['like'], 'userDetalles.documento', $value ] ] );
                    break;  
                    case 'estadoGaPrestamo': 
                        $relationLoan->andWhere(['IN', 'gaPrestamos.'. $field, intval($value)]);
                    break; 
                    case 'fechaInicial':
                        $relationLoan->andWhere(['>=', 'gaPrestamos.creacionGaPrestamo', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $relationLoan->andWhere(['<=', 'gaPrestamos.creacionGaPrestamo', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    case 'ubicacionGdExpediente':
                        $relationLoan->andWhere(['=', 'gdExpedientes.ubicacionGdExpediente', intval($value)]);
                    break;
                    default:
                        $relationLoan->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);
                    break;
                }
            }

            # Condición para que en el index solo muestre los de solicitar prestamo cuando no haya filtros.
            if( count($dataWhere) == 0) {
                $relationLoan = $relationLoan->where(['gaPrestamos.estadoGaPrestamo' => Yii::$app->params['statusTodoText']['SolicitudPrestamo']]);
            }            

            # Orden descendente para ver los últimos registros creados
            $relationLoan->orderBy(['gaPrestamos.idGaPrestamo' => SORT_DESC]); 

            # Limite de la consulta
            $relationLoan->limit($limitRecords);
            $modelRelation = $relationLoan->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            $userPermisoArchivoCentral = false;
            if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsCentralFileLoans'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
                $formType = HelperDynamicForms::setListadoBD('indexAdministrarPrestamoCentral');
                $userPermisoArchivoCentral = true;
            } else {
                $formType = HelperDynamicForms::setListadoBD('indexAdministrarPrestamo');
            }

            foreach($modelRelation as $dataRelation) {  

                # Información del contenido de la bodega
                $gaArchivo = $dataRelation->idGdExpedienteInclusion0->gdExpediente->gaArchivo;
                if ($gaArchivo != null) {
                    $warehouse = $gaArchivo->gaBodega->nombreGaBodega;
                    $rack = $gaArchivo->rackGaArchivo;
                    $shelf = $gaArchivo->entrepanoGaArchivo;
                    $box = $gaArchivo->cajaGaArchivo;
                } else {
                    $warehouse = '';
                    $rack = '';
                    $shelf = '';
                    $box = '';
                }

                # Fecha de solicitud prestamo
                $applicationDate = date('Y-m-d', strtotime($dataRelation->fechaSolicitudGaPrestamo));
                
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaPrestamo)),
                );


                # Listado de informacion
                $dataList[] = array(
                    'data'                  => $dataBase64Params,
                    'id'                    => $dataRelation->idGaPrestamo,
                    'filingNumber'          => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($dataRelation->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado),
                    'subject'               => (strlen($dataRelation->idGdExpedienteInclusion0->radiRadicado->asuntoRadiRadicado) > 150) ? substr($dataRelation->idGdExpedienteInclusion0->radiRadicado->asuntoRadiRadicado, 0, 150) . '...' : $dataRelation->idGdExpedienteInclusion0->radiRadicado->asuntoRadiRadicado,
                    'documentaryType'       => $dataRelation->idGdExpedienteInclusion0->radiRadicado->trdTipoDocumental->nombreTipoDocumental,
                    'fileName'              => $dataRelation->idGdExpedienteInclusion0->gdExpediente->nombreGdExpediente,
                    'idLoanType'            => $dataRelation->idTipoPrestamoGaPrestamo,
                    'loanType'              => Yii::t('app', 'statusLoanTypeNumber')[$dataRelation->idTipoPrestamoGaPrestamo],
                    'request'               => Yii::t('app', 'statusLoanRequirementText')[$dataRelation->idRequerimientoGaPrestamo], 
                    'applicationDate'       => $applicationDate,
                    'warehouse'             => $warehouse,
                    'rack'                  => $rack,
                    'shelf'                 => $shelf,
                    'box'                   => $box,
                    'idUser'                => $dataRelation->idUser,
                    'idUserLoginDependency' => Yii::$app->user->identity->idGdTrdDependencia,
                    'user'                  => $dataRelation->idUser0->userDetalles->nombreUserDetalles.' '.$dataRelation->idUser0->userDetalles->apellidoUserDetalles,  
                    'dependency'            => $dataRelation->idGdTrdDependencia0->nombreGdTrdDependencia,
                    'idDependencyExpediente'=> $dataRelation->idGdExpedienteInclusion0->gdExpediente->idGdTrdDependencia,
                    'statusText'            => Yii::t('app', 'statusTodoNumber')[$dataRelation->estadoGaPrestamo],
                    'status'                => $dataRelation->estadoGaPrestamo,
                    'rowSelect'             => false,
                    'idInitialList'         => 0,
                    'isPrestamoFisico'      => ($dataRelation->idTipoPrestamoGaPrestamo == Yii::$app->params['statusLoanTypeText']['PrestamoFisico']) ? true : false,
                    'permisoArchivoCentral' => $userPermisoArchivoCentral
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[0],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Crea un nuevo registro al aprobar el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionApproveLoan()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $jsonSend = Yii::$app->request->post('jsonSend');
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
                $numFiles = [];
                $requirements = [];
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan){

                    # Id del préstamo
                    $id = $dataLoan['id'];

                    # Observacion y fecha de la aprobación del préstamo
                    $observacion = $request['data']['observacion'];

                    # Si el tipo prestamo es 'Prestamo Fisico o consulta sala' se  valida la contraseña del usuario solicitante
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico'] || $dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['ConsultaSala']){
                        
                        $modelUser = UserValidate::findOne(['id' => $dataLoan['idUser']]);

                        if(!is_null($modelUser)) {

                            # Se valida que la contraseña sea correcta
                            $validPass = $modelUser->validatePassword($request['data']['passUser']);

                            if(!$validPass) {

                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                    } 

                    # Si el tipo prestamo es 'Prestamo Fisico' se obtiene la fecha diligenciada por el usuario
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico']){
                        $date =  date("Y-m-d", strtotime($request['data']['fecha']));
                        $observacion .= ' - Fecha máxima de devolución: ' . $date;
                    } else {
                        $date = date("Y-m-d");
                    }

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = $this->findModel($id);
                    $modelLoan->estadoGaPrestamo = Yii::$app->params['statusTodoText']['PrestamoAprobado'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $numFiles[] = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;                     
                    $requirements[] = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamo];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamo();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamo = $modelLoan->idGaPrestamo;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamo = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamo = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamo = $modelLoan->estadoGaPrestamo;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamo = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    # numero del radicado y el usuario donde se aprobó el prestamo para el log
                    $numberFile = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                    $userName = $modelLoan->idUser0->userDetalles->nombreUserDetalles.' '.$modelLoan->idUser0->userDetalles->apellidoUserDetalles;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se aprobó el préstamo del radicado: ".$numberFile." al usuario: ".$userName.", en la tabla de GaPrestamos y GaHistoricoPrestamo", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/


                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['loanApproval'].$numberFile, //Notificacion
                        Yii::$app->params['routesFront']['viewRadicado'], // URL
                        $modelLoan->idGdExpedienteInclusion0->radiRadicado->idRadiRadicado // id radicado
                    ); 
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamo,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamo],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }

                # Envio de correos masivamente o cuando se escoge un radicado
                $numRadicado = implode(', ',$numFiles);  
                $requirement = implode(', ',$requirements); 

                # Modificación de mensajes del correo
                if ( count($numFiles) > 1) {
                    $headMailText  = Yii::t('app', 'headMailsApproveLoan');
                    $textStart = 'textsBodyApproveLoan';

                } else {
                    $headMailText  = Yii::t('app', 'headMailApproveLoan', [
                        'numRadicado'    => $numRadicado,
                    ]);

                    $textStart = 'textBodyApproveLoan';                    
                }

                $date = HelperRadicacion::getFormatosFecha($date)['formatoFrontend'];

                $textBody  = Yii::t('app', $textStart, [
                    'requerimiento'  => $requirement,
                    'numRadicado'    => $numRadicado,
                    'observacion'    => $observacion,
                    'fecha'          => $date,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoan']; 
                $subject = 'subjectApproveLoan';   
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al aprobar el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionAuthorizeLoan() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->post('jsonSend');
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
                $numFiles = [];
                $requirements = [];
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan) {
                    # Id del préstamo
                    $id = $dataLoan['id'];
                    # Observacion y fecha de la aprobación del préstamo
                    $observacion = $request['data']['observacion'];

                    # Si el tipo prestamo es 'Prestamo Fisico' se obtiene la fecha diligenciada por el usuario
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico']){
                        $date =  date("Y-m-d", strtotime($request['data']['fecha']));
                        $observacion .= ' - Fecha máxima de devolución: ' . $date;
                    } else {
                        $date = date("Y-m-d");
                    }

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = $this->findModel($id);
                    $modelLoan->estadoGaPrestamo = Yii::$app->params['statusTodoText']['PrestamoAutorizado'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $numFiles[] = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;                     
                    $requirements[] = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamo];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamo();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamo = $modelLoan->idGaPrestamo;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamo = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamo = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamo = $modelLoan->estadoGaPrestamo;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamo = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());                        
                        break;
                    }

                    # numero del radicado y el usuario donde se aprobó el prestamo para el log
                    $numberFile = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                    $userName = $modelLoan->idUser0->userDetalles->nombreUserDetalles.' '.$modelLoan->idUser0->userDetalles->apellidoUserDetalles;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se autorizó el préstamo del radicado: ".$numberFile." al usuario: ".$userName.", en la tabla de GaPrestamos y GaHistoricoPrestamo", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/


                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['authorizeLoan'].$numberFile, //Notificacion
                        Yii::$app->params['routesFront']['indexLoan'], // URL
                        '' // id radicado
                    ); 
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamo,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamo],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }                

                # Envio de correos masivamente o cuando se escoge un radicado
                $numRadicado = implode(', ',$numFiles);  
                $requirement = implode(', ',$requirements); 

                # Modificación de mensajes del correo
                if ( count($numFiles) > 1) {
                    $headMailText  = Yii::t('app', 'headMailsAuthorizeLoan');
                    $textStart = 'textsBodyAuthorizeLoan';

                } else {
                    $headMailText  = Yii::t('app', 'headMailAuthorizeLoan', [
                        'numRadicado' => $numRadicado,
                    ]);

                    $textStart = 'textBodyAuthorizeLoan';
                }

                $date = HelperRadicacion::getFormatosFecha($date)['formatoFrontend'];

                $textBody  = Yii::t('app', $textStart, [
                    'requerimiento'  => $requirement,
                    'numRadicado'    => $numRadicado,
                    'observacion'    => $observacion,
                    'fecha'          => $date,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoan']; 
                $subject = 'subjectAuthorizeLoan';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al devolver el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionReturnLoan()
    {
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

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan){

                    # Id del préstamo
                    $id = $dataLoan['id'];

                    # Observacion y fecha de la devolución del préstamo
                    $observacion = $request['data']['observacion'];
                    $date =  date("Y-m-d");

                    # Si el tipo prestamo es 'Prestamo Fisico o consulta sala' se  valida la contraseña del usuario solicitante
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico'] || $dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['ConsultaSala']){
                        
                        $modelUser = UserValidate::findOne(['id' => $dataLoan['idUser']]);

                        if(!is_null($modelUser)) {

                            # Se valida que la contraseña sea correcta
                            $validPass = $modelUser->validatePassword($request['data']['passUser']);

                            if(!$validPass) {

                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        
                    } 

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = $this->findModel($id);
                    $modelLoan->estadoGaPrestamo = Yii::$app->params['statusTodoText']['PrestamoDevuelto'];
                                       
                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }


                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamo();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamo = $modelLoan->idGaPrestamo;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamo = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamo = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamo = $modelLoan->estadoGaPrestamo;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamo = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());                        
                        break;
                    }

                    # Numero radicado que devolvio el prestamo para el log
                    $numberFile = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se realizó la devolución del préstamo del radicado: ".$numberFile.", en la tabla de GaPrestamos y GaHistoricoPrestamo", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamo,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamo],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    );                     
                }
                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al cancelar el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionCancelLoan()
    { 
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
                $numFiles = [];
                $requirements = [];
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan){

                    # Id del préstamo
                    $id = $dataLoan['id'];

                    # Observacion y fecha de la cancelación del préstamo
                    $observacion = $request['data']['observacion'];
                    $date =  date("Y-m-d");

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = $this->findModel($id);
                    $modelLoan->estadoGaPrestamo = Yii::$app->params['statusTodoText']['PrestamoCancelado'];
                                       
                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());                        
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $numFiles[] = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;                     
                    $requirements[] = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamo];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamo();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamo = $modelLoan->idGaPrestamo;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamo = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamo = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamo = $modelLoan->estadoGaPrestamo;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamo = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());                        
                        break;
                    }

                    # Numero radicado que fue cancelado el prestamo para el log
                    $numberFile = $modelLoan->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se canceló el préstamo del radicado: ".$numberFile.", en la tabla de GaPrestamos y GaHistoricoPrestamo", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['loanCancellation'].$numberFile, //Notificacion
                        Yii::$app->params['routesFront']['indexLoan'], // URL
                        '' // id radicado
                    ); 
                    /***  Fin Notificacion  ***/

                    //Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamo,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamo],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }                

                # Envio de correos masivamente o cuando se escoge un radicado
                $numRadicado = implode(', ',$numFiles);  
                $requirement = implode(', ',$requirements);

                # Modificación de mensajes del correo
                if ( count($numFiles) > 1) {
                    $headMailText  = Yii::t('app', 'headMailsCancelLoan');
                    $textStart = 'textsBodyCancelLoan';

                } else {
                    $headMailText  = Yii::t('app', 'headMailCancelLoan', [
                        'numRadicado'    => $numRadicado,
                    ]);

                    $textStart = 'textBodyCancelLoan';                    
                }

                $textBody  = Yii::t('app', $textStart, [
                    'requerimiento'  => $requirement,
                    'numRadicado'    => $numRadicado,
                    'observacion'    => $observacion,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoan'];
                $subject = 'subjectCancelLoan';   
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al aprobar el préstamo de expedientes en el histórico 
     * y actualiza el estado en gaPrestamosExpedientes
     */
    public function actionApproveLoanFiles() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->post('jsonSend');
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
                $requirements = "";
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan) {
                    # Id del préstamo
                    $id = $dataLoan['id'];
                    # Observacion y fecha de la aprobación del préstamo
                    $observacion = $request['data']['observacion'];

                    # Si el tipo prestamo es 'Prestamo Fisico o consulta sala' se  valida la contraseña del usuario solicitante
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico'] || $dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['ConsultaSala']) {
                        $modelUser = UserValidate::findOne(['id' => $dataLoan['idUser']]);

                        if(!is_null($modelUser)) {

                            # Se valida que la contraseña sea correcta
                            $validPass = $modelUser->validatePassword($request['data']['passUser']);

                            if(!$validPass) {

                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                    } 

                    # Si el tipo prestamo es 'Prestamo Fisico' se obtiene la fecha diligenciada por el usuario
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico']){
                        $date = date("Y-m-d", strtotime($request['data']['fecha']));
                        $observacion .= ' - Fecha máxima de devolución: ' . $date;
                    } else {
                        $date = date("Y-m-d");
                    }

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = GaPrestamosExpedientes::findOne($id);
                    $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['PrestamoAprobado'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $requirements = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamoExpediente];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamoExpediente();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamoExpediente = $modelLoan->idGaPrestamoExpediente;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamoExpediente = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamoExpediente = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamoExpediente = $modelLoan->estadoGaPrestamoExpediente;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamoExpediente = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    $nombreExpediente = $modelLoan->idGdExpediente0->nombreGdExpediente;
                    $userName = $modelLoan->idUser0->userDetalles->nombreUserDetalles.' '.$modelLoan->idUser0->userDetalles->apellidoUserDetalles;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se aprobó el préstamo del expediente: ". $nombreExpediente ." al usuario: ".$userName.", en la tabla de GaPrestamosExpedientes y GaHistoricoPrestamoExpedientes", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['loanApprovalFiles'].$nombreExpediente, //Notificacion
                        Yii::$app->params['routesFront']['viewExpediente'], // URL
                        $modelLoan->idGdExpediente
                    ); 
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamoExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamoExpediente],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }

                # Modificación de mensajes del correo
                $headMailText  = Yii::t('app', 'headMailApproveLoanFiles', [
                    'expediente' => $nombreExpediente,
                ]);

                $date = HelperRadicacion::getFormatosFecha($date)['formatoFrontend'];

                $textBody = Yii::t('app', 'textBodyApproveLoanFiles', [
                    'requerimiento' => $requirements,
                    'expediente'    => $nombreExpediente,
                    'observacion'   => $observacion,
                    'fecha'         => $date,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoanFiles']; 
                $subject = 'subjectApproveLoan';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al aprobar el préstamo de expedientes en el histórico 
     * y actualiza el estado en gaPrestamosExpedientes
     */
    public function actionAuthorizeLoanFiles() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->post('jsonSend');
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
                $requirements = "";
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan) {
                    # Id del préstamo
                    $id = $dataLoan['id'];
                    # Observacion y fecha de la aprobación del préstamo
                    $observacion = $request['data']['observacion'];

                    # Si el tipo prestamo es 'Prestamo Fisico' se obtiene la fecha diligenciada por el usuario
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico']){
                        $date = date("Y-m-d", strtotime($request['data']['fecha']));
                        $observacion .= ' - Fecha máxima de devolución: ' . $date;
                    } else {
                        $date = date("Y-m-d");
                    }

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = GaPrestamosExpedientes::findOne($id);
                    $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['PrestamoAutorizado'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $requirements = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamoExpediente];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamoExpediente();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamoExpediente = $modelLoan->idGaPrestamoExpediente;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamoExpediente = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamoExpediente = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamoExpediente = $modelLoan->estadoGaPrestamoExpediente;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamoExpediente = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    $nombreExpediente = $modelLoan->idGdExpediente0->nombreGdExpediente;
                    $userName = $modelLoan->idUser0->userDetalles->nombreUserDetalles.' '.$modelLoan->idUser0->userDetalles->apellidoUserDetalles;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se autorizó el préstamo del expediente: ". $nombreExpediente ." al usuario: ".$userName.", en la tabla de GaPrestamosExpedientes y GaHistoricoPrestamoExpedientes", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['authorizeLoanFiles'].$nombreExpediente, //Notificacion
                        Yii::$app->params['routesFront']['viewExpediente'], // URL
                        $modelLoan->idGdExpediente
                    ); 
                    /***  Fin Notificacion  ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamoExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamoExpediente],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }

                # Modificación de mensajes del correo
                $headMailText  = Yii::t('app', 'headMailAuthorizeLoanFiles', [
                    'expediente' => $nombreExpediente,
                ]);

                $date = HelperRadicacion::getFormatosFecha($date)['formatoFrontend'];

                $textBody = Yii::t('app', 'textBodyAuthorizeLoanFiles', [
                    'requerimiento' => $requirements,
                    'expediente'    => $nombreExpediente,
                    'observacion'   => $observacion,
                    'fecha'         => $date,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoanFiles']; 
                $subject = 'subjectAuthorizeLoan';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al cancelar el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionCancelLoanFiles() {
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
                $requirements = "";
                $transaction = Yii::$app->db->beginTransaction();

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan) {
                    # Id del préstamo
                    $id = $dataLoan['id'];
                    # Observacion y fecha de la cancelación del préstamo
                    $observacion = $request['data']['observacion'];
                    $date = date("Y-m-d");

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = GaPrestamosExpedientes::findOne($id);
                    $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['PrestamoCancelado'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }

                    # Se agrupa todos los radicados seleccionados, con su requerimiento y el correo del usuario solicitante
                    $requirements = Yii::t('app', 'statusLoanRequirementText')[$modelLoan->idRequerimientoGaPrestamoExpediente];
                    $email = $modelLoan->idUser0->email;
                    $idUser = $modelLoan->idUser0->id;

                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamoExpediente();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamoExpediente = $modelLoan->idGaPrestamoExpediente;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamoExpediente = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamoExpediente = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamoExpediente = $modelLoan->estadoGaPrestamoExpediente;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamoExpediente = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    $nombreExpediente = $modelLoan->idGdExpediente0->nombreGdExpediente;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se canceló el préstamo del expediente: ".$nombreExpediente.", en la tabla de GaPrestamosExpediente y GaHistoricoPrestamoExpediente", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    /***  Notificacion  ***/
                    HelperNotification::addNotification(
                        Yii::$app->user->identity->id, //Id user creador
                        $idUser, // Id user notificado
                        Yii::t('app','messageNotification')['loanCancellationFiles'].$nombreExpediente, //Notificacion
                        Yii::$app->params['routesFront']['indexLoanFiles'], // URL
                        '' // id radicado
                    ); 
                    /***  Fin Notificacion  ***/

                    //Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamoExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamoExpediente],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    ); 
                }

                # Modificación de mensajes del correo
                $headMailText  = Yii::t('app', 'headMailCancelLoanFiles', [
                    'expediente' => $nombreExpediente,
                ]);

                $textBody = Yii::t('app', 'textBodyCancelLoanFiles', [
                    'requerimiento' => $requirements,
                    'expediente'    => $nombreExpediente,
                    'observacion'   => $observacion,
                ]);
               
                $bodyMail = 'radicacion-html'; 
                $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexLoanFiles'];
                $subject = 'subjectCancelLoan';
                $nameButtonLink = 'buttonLinkLoan'; // Esta variable sera traducida 

                $envioCorreo = CorreoController::sendEmail($email, $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
                # Fin de envio de correos masivamente 

                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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
     * Crea un nuevo registro al devolver el prestamo en el histórico 
     * y actualiza el estado en gaPrestamos
     */
    public function actionReturnLoanFiles() {
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

                # Se agrega registros en prestamo documental y al historico
                foreach($request['ButtonSelectedData'] as $dataLoan) {
                    # Id del préstamo
                    $id = $dataLoan['id'];
                    # Observacion y fecha de la devolución del préstamo
                    $observacion = $request['data']['observacion'];
                    $date =  date("Y-m-d");

                    # Si el tipo prestamo es 'Prestamo Fisico o consulta sala' se  valida la contraseña del usuario solicitante
                    if($dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['PrestamoFisico'] || $dataLoan['idLoanType'] == Yii::$app->params['statusLoanTypeText']['ConsultaSala']){
                        
                        $modelUser = UserValidate::findOne(['id' => $dataLoan['idUser']]);

                        if(!is_null($modelUser)) {

                            # Se valida que la contraseña sea correcta
                            $validPass = $modelUser->validatePassword($request['data']['passUser']);

                            if(!$validPass) {

                                $transaction->rollBack();

                                Yii::$app->response->statusCode = 200;
                                $response = [
                                    'message' => Yii::t('app','errorValidacion'),
                                    'data' => ['error' => [Yii::t('app', 'errorValidPassLoan')]],
                                    'status' => Yii::$app->params['statusErrorValidacion'],
                                ];
                                return HelperEncryptAes::encrypt($response, true);
                            }
                        }
                        
                    } 

                    # Se actualiza el estado en gaPrestamos
                    $modelLoan = GaPrestamosExpedientes::findOne($id);
                    $modelLoan->estadoGaPrestamoExpediente = Yii::$app->params['statusTodoText']['PrestamoDevuelto'];

                    if (!$modelLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelLoan->getErrors());
                        break;
                    }


                    # Se almacena el historico por cada idPrestamo.
                    $modelHistoricalLoan = new GaHistoricoPrestamoExpediente();
                    $modelHistoricalLoan->idUser = Yii::$app->user->identity->id;
                    $modelHistoricalLoan->idGdTrdDependencia = Yii::$app->user->identity->idGdTrdDependencia;
                    $modelHistoricalLoan->idGaPrestamoExpediente = $modelLoan->idGaPrestamoExpediente;
                    $modelHistoricalLoan->fechaGaHistoricoPrestamoExpediente = $date;
                    $modelHistoricalLoan->observacionGaHistoricoPrestamoExpediente = $observacion;
                    $modelHistoricalLoan->estadoGaHistoricoPrestamoExpediente = $modelLoan->estadoGaPrestamoExpediente;
                    $modelHistoricalLoan->creacionGaHistoricoPrestamoExpediente = date('Y-m-d H:i:s');

                    if (!$modelHistoricalLoan->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelHistoricalLoan->getErrors());
                        break;
                    }

                    $nombreExpediente = $modelLoan->idGdExpediente0->nombreGdExpediente;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", con la observación: Se realizó la devolución del préstamo del expediente: ".$nombreExpediente.", en la tabla de GaPrestamosExpedientes y GaHistoricoPrestamoExpedientes", //texto para almacenar en el evento
                        [], // DataOld
                        [], //Data 
                        array() //No validar estos campos
                    ); 
                    /***    Fin log Auditoria   ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'status' => $modelLoan->estadoGaPrestamoExpediente,
                        'statusText' => Yii::t('app', 'statusTodoNumber')[$modelLoan->estadoGaPrestamoExpediente],
                        'idInitialList' => $dataLoan['idInitialList'] * 1
                    );
                }
                
                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successSave'),
                        'data' => [],
                        'dataResponse' => $dataResponse,
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

    /* Listado de Tipos de préstamo */
    public function actionIndexListType()
    {
        $dataList = [];

        foreach (Yii::t('app', 'statusLoanTypeNumber') as $i => $tipo){

            $dataList[] = array(
                "id" => intval($i),
                "val" => $tipo,
            );
        }
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /* Listado de Requerimiento */
    public function actionIndexListRequest()
    {
        $dataList = [];

        foreach (Yii::t('app', 'statusLoanRequirementText') as $i => $requerimiento){
            $dataList[] = array(
                "id" => intval($i),
                "val" => $requerimiento,
            );
        }
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Visualización del flujo histórico del pŕestamo de los radicados
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionHistoricalLoan($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

            $id = $request['id']; //idGaPrestamo

            # Consulta en gaPrestamos
            $model = $this->findModel($id);

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['viewHistoricalLoan'].'Id: '.$model->idGaPrestamo.' del radicado: '.$model->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            # Se realiza nuevamente la consulta para obtener todos los prestamos que pertenecen al expediente
            $modelLoan = GaPrestamos::findAll(['idGdExpedienteInclusion' => $model->idGdExpedienteInclusion]);

            $arrayIds = [];
            foreach($modelLoan as $loan){
                $arrayIds[] = $loan->idGaPrestamo;
            }

            # Flujo histórico del radicado
            $historical = GaHistoricoPrestamo::find()
                ->where(['idGaPrestamo' => $arrayIds])
                ->orderBy(['creacionGaHistoricoPrestamo' => SORT_DESC, 'idGaHistoricoPrestamo' => SORT_DESC])
            ->all();

            $dataHistorical = [];
            $dateHistorical = '';
            
            foreach($historical as $dataLoan) {
                $dateHistorical = $dataLoan->creacionGaHistoricoPrestamo;

                $dataHistorical[] = array(
                    'dependencia'   => $dataLoan->idGdTrdDependencia0->nombreGdTrdDependencia,
                    'usuario'       => $dataLoan->idUser0->userDetalles->nombreUserDetalles.' '.$dataLoan->idUser0->userDetalles->apellidoUserDetalles,
                    'fecha'         => $dateHistorical,
                    'transaccion'   => Yii::t('app', 'statusTodoNumber')[$dataLoan->estadoGaHistoricoPrestamo],
                    'observacion'   => $dataLoan->observacionGaHistoricoPrestamo,
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'title' => Yii::t('app', 'titleHistoricalLoan').$model->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado,
                'data' => $dataHistorical,
                'status' => 200,
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

    /**
     * Visualización del flujo histórico del pŕestamo para los expedientes
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionHistoricalLoanFiles($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
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

            $id = $request['id']; //idGaPrestamo

            # Consulta en gaPrestamos
            $model = GaPrestamosExpedientes::findOne($id);

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['viewHistoricalLoan'].'de expediente con Id: '.$model->idGaPrestamoExpediente, //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/

            # Se realiza nuevamente la consulta para obtener todos los prestamos que pertenecen al expediente
            $modelLoan = GaPrestamosExpedientes::findAll(['idGdExpediente' => $model->idGdExpediente]);

            $arrayIds = [];
            foreach($modelLoan as $loan){
                $arrayIds[] = $loan->idGaPrestamoExpediente;
            }

            # Flujo histórico del radicado
            $historical = Gahistoricoprestamoexpediente::find()
                ->where(['in', 'idGaPrestamoExpediente', $arrayIds])
                ->orderBy(['creacionGaHistoricoPrestamoExpediente' => SORT_DESC, 'idGaHistoricoPrestamoExpediente' => SORT_DESC])
                ->all();

            $dataHistorical = [];
            $dateHistorical = '';
            
            foreach($historical as $dataLoan) {
                $dateHistorical = $dataLoan->creacionGaHistoricoPrestamoExpediente;

                $dataHistorical[] = array(
                    'dependencia'   => $dataLoan->idGdTrdDependencia0->nombreGdTrdDependencia,
                    'usuario'       => $dataLoan->idUser0->userDetalles->nombreUserDetalles.' '.$dataLoan->idUser0->userDetalles->apellidoUserDetalles,
                    'fecha'         => $dateHistorical,
                    'transaccion'   => Yii::t('app', 'statusTodoNumber')[$dataLoan->estadoGaHistoricoPrestamoExpediente],
                    'observacion'   => $dataLoan->observacionGaHistoricoPrestamoExpediente,
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'title' => Yii::t('app', 'titleHistoricalLoanFiles').$model->idGdExpediente0->nombreGdExpediente,
                'data' => $dataHistorical,
                'status' => 200,
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

    /**
     * Finds the GaPrestamos model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return GaPrestamos the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = GaPrestamos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }

}
