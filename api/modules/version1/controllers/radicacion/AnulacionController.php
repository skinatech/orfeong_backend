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

use api\components\HelperDynamicForms;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperConsecutivo;
use api\components\HelperLog;
use api\models\CgTransaccionesRadicados;
use api\models\Clientes;
use api\models\GdTrdDependencias;
use api\models\RadiRadicadoAnulado;
use api\models\RadiRadicados;
use api\models\User;
use api\models\UserDetalles;
use api\modules\version1\controllers\correo\CorreoController;
use api\modules\version1\controllers\pdf\PdfController;
use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;

use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use api\components\HelperQueryDb;

/**
 * AnulacionController implements the CRUD actions for RadiRadicadoAnulado model.
 */
class AnulacionController extends Controller
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
                    'acepta-anulacion' => ['PUT'],
                    'rechazo-anulacion' => ['PUT'],
                    'dowload-base64' => ['GET'],
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
     * Lists all RadiRadicadoAnulado models.
     * @return mixed
     */
    public function actionIndex($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            //El request obtiene 'filterOperation' => [["idRadicado"=>[10,14], "idEstado"=> [2], "idCgTipoRadicado"=>[4],
            // "idResponsable" => [6], ]],
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
            
            //Lista de roles
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
                                } elseif($info != '') {  // O un string
                                    $dataWhere[$key] = $info;
                                }                            
                            }
                        }
                    }
                }
            }

            // Consulta para relacionar la informacion de los radicados anulados y obtener 100 registros, a partir del filtro
            $radicadoAnulado = RadiRadicadoAnulado::find();
                //->leftJoin('radiRadicados', '`radiRadicados`.`idRadiRadicado` = `radiRadicadoAnulado`.`idRadicado`')
                $radicadoAnulado = HelperQueryDb::getQuery('leftJoin', $radicadoAnulado, 'radiRadicados', ['radiRadicadoAnulado' => 'idRadicado', 'radiRadicados' => 'idRadiRadicado']);
                //->leftJoin('user', '`user`.`id` = `radiRadicadoAnulado`.`idResponsable`');
                $radicadoAnulado = HelperQueryDb::getQuery('leftJoin', $radicadoAnulado, 'user', ['radiRadicadoAnulado' => 'idResponsable', 'user' => 'id']);

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){

                switch ($field) {
                    case 'idCgTipoRadicado':
                    case 'idGdTrdDependencia':
                    case 'idResponsable':
                    case 'idEstado':
                        $radicadoAnulado->andWhere(['IN', $field , $value]);
                    break;
                    case 'fechaInicial':
                        $radicadoAnulado->andWhere(['>=', 'radiRadicadoAnulado.fechaRadiRadicadoAnulado', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $radicadoAnulado->andWhere(['<=', 'radiRadicadoAnulado.fechaRadiRadicadoAnulado', trim($value) . Yii::$app->params['timeEnd']]);
                    break;
                    default:
                        $radicadoAnulado->andWhere([Yii::$app->params['like'], $field , $value]);
                    break;
                }
                
            }   
            //Limite de la consulta
            $radicadoAnulado->orderBy(['radiRadicadoAnulado.idRadiRadicadoAnulado' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $radicadoAnulado->limit($limitRecords);
            $modelRadicadoAnulado = $radicadoAnulado->all();   

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRadicadoAnulado = array_reverse($modelRadicadoAnulado);

            foreach ($modelRadicadoAnulado as $anulacion) {

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($anulacion->idRadiRadicadoAnulado))
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $anulacion->idRadiRadicadoAnulado,
                    'tipoRadicado' => $anulacion->idRadicado0->cgTipoRadicado->nombreCgTipoRadicado,
                    'numeroRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($anulacion->idRadicado0->numeroRadiRadicado, $anulacion->idRadicado0->idCgTipoRadicado,$anulacion->idRadicado0->isRadicado),
                    'fechaAnulacion' => $anulacion->fechaRadiRadicadoAnulado,
                    'observacionAnulacion' => $anulacion->observacionRadiRadicadoAnulado,
                    'usuario' => $anulacion->idResponsable0->userDetalles->nombreUserDetalles.' '.$anulacion->idResponsable0->userDetalles->apellidoUserDetalles,
                    'statusText' => $anulacion->idEstado0->nombreRadiEstadoAnulacion,
                    'status' => $anulacion->idEstado0->idRadiEstadoAnulacion,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );
            }

            // Valida que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexAnulacion');

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
     * Funcion que aprueba la anulación de un radicado
     **/ 
    public function actionAceptaAnulacion(){

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
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            if (!empty($jsonSend)) {

                # Inicialización de variables
                $saveDataValid = true;
                $errors = [];
                $createPdf = [];
                $currentPage = 1; //Inicio de pagina del acta
                $modelRadicados = [];
                $dataResponse = []; // Data donde se guardan los estados nuevos de los radicados anulados
                $idRadiAnulados = []; // Array de los radicados anulados     
                $log = [];           

                $transaction = Yii::$app->db->beginTransaction();

                # Se obtiene el número del Acta 
                /*$conteoDeActa =  (new \yii\db\Query())
                    ->select(['COUNT(codigoActaRadiRadicadoAnulado) AS count'])
                    ->from('radiRadicadoAnulado')
                ->all();*/

               
                # Esquema para generar el string del count antes del SELECT
                $count = HelperQueryDb::getColumnCount('radiRadicadoAnulado','codigoActaRadiRadicadoAnulado','count'); 
                
                # Se obtiene el número del Acta
                $conteoDeActa =  (new \yii\db\Query())
                    ->select([$count])
                    ->from('radiRadicadoAnulado')
                ->all();       

                $numeroActa = $conteoDeActa[0]['count'] + 1;

                # Consulta la accion de la transaccion del estado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'annulationApproval']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Obtener el número de actas para generar en pdf
                $countActas = count($request['ButtonSelectedData']);

                # Actualizacion de los radicados con el nuevo estado.
                foreach ($request['ButtonSelectedData'] as $key => $infoRadicadoAnulado) {
                    
                    # Id del radicado anulado
                    $id = $infoRadicadoAnulado['id'];
                    $observacionRadicadoAnulado = $request['data']['observacion'];

                    $modelRadicadoAnulado = $this->findModel($id);

                    # Se almacenan en un arreglo las observaciones que se realizaron en la solicitud ya que son las que van en el acta
                    $observacionesRadicado[$modelRadicadoAnulado->idRadicado] = $modelRadicadoAnulado->observacionRadiRadicadoAnulado;

                    # Valores del modelo se utiliza para el log anterior
                    $log[$id]['dataOld'] = self::dataLog($modelRadicadoAnulado);

                    # Datos para almacenar en el modelo de anulación
                    $modelRadicadoAnulado->observacionRadiRadicadoAnulado = $observacionRadicadoAnulado;
                    $modelRadicadoAnulado->idEstado = Yii::$app->params['statusAnnulationText']['AceptacionAnulacion'];
                    $modelRadicadoAnulado->codigoActaRadiRadicadoAnulado = $numeroActa; 

                    # Se agrupa todos los radicados seleccionados
                    $idRadiAnulados[] = $modelRadicadoAnulado->idRadicado;    

                    # Array donde contendrá del modelo del radicado anulado
                    $modelRadicados[] = $modelRadicadoAnulado;  

                    if (!$modelRadicadoAnulado->save()) {
                        // Valida false ya que no se guarda
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadicadoAnulado->getErrors());
                        break;
                    }        
                    
                    $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $modelRadicadoAnulado->idRadicado]);
                    $modelRadicado->estadoRadiRadicado = Yii::$app->params['statusTodoText']['Inactivo'];
                    $modelRadicado->save();

                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $modelRadicadoAnulado->idRadicado, //Id radicado
                        $idTransacion,
                        $observacionRadicadoAnulado, //observación 
                        $modelRadicadoAnulado,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/

                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'observacionAnulacion' => $observacionRadicadoAnulado,
                        'status' => $modelRadicadoAnulado->idEstado,
                        'statusText' => $modelRadicadoAnulado->idEstado0->nombreRadiEstadoAnulacion,
                        'idInitialList' => $infoRadicadoAnulado['idInitialList'] * 1
                    );
                                    
                    //$currentPage++;
                }    


                # Nombre del archivo del pdf
                $fileName = Yii::$app->params['baseNameFileActaAnulacion'].$numeroActa.'.pdf';

                # Creación del pdf
                if(isset($idRadiAnulados) && is_array($idRadiAnulados)){

                    $pdfStructure = $this->createPdf($idRadiAnulados, $numeroActa, $observacionesRadicado, $currentPage, $countActas);

                    # Concatena la estructura de todos los radicados que se veran en un acta
                    $createPdf[] = $pdfStructure['html'];       
                    $style = $pdfStructure['style'];
                }                              

                # Información de usuario logueado
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombresLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                # Consulta de datos del radicado

                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';
        
                /* $modelRadicado = (new \yii\db\Query())
                    ->select(['rd.numeroRadiRadicado','rd.user_idTramitador', 'us.email'])
                    ->from($tablaRadicado)
                    ->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    ->where(['rd.idRadiRadicado' => $idRadiAnulados])
                ->all(); */

                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado', 'RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email'])
                    ->from($tablaRadicado);
                    //->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    $modelRadicado = $modelRadicado->where(['RD.idRadiRadicado' => $idRadiAnulados])
                ->all();


                $arrayDatos = [];
                if(!is_null($modelRadicado)){
                    
                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado){               

                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'] = $infoRadicado['idRadiRadicado'];

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado']; 
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'] = $infoRadicado['idRadiRadicado']; 
                        }
                    }
                }
                
                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {
                       
                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if ( count($radicadosUsuario['radicados']) > 1) {
                            $headText = 'headMailTextAnulaciones';
                            $textStart = 'textBodyAceptaAnulaciones';
                            $subjectText = 'subjectAnnulationsRequest';

                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                        } else {
                            $headText = 'headMailTextAnulacion';
                            $textStart = 'textBodyAceptaAnulacion';
                            $subjectText = 'subjectAnnulationRequest';

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['idRadicado']));
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        }
                
                        $headMailText = Yii::t('app', $headText);    

                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                        $textBody  = Yii::t('app', $textStart, [
                            'NoRadicado'        => $numRadicado,
                            'Observacion'       => $observacionRadicadoAnulado,
                            'NoActa'            => $numeroActa,
                            'Username'          => $nombresLogged,
                            'NameDependencia'   => $dependenciaLogged,
                        ]);

                        $subject = Yii::t('app', $subjectText);
                        $bodyMail = 'radicacion-html';                    

                        # Envia la notificación de correo electronico al usuario de tramitar
                        $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);

                    }     
                    # Fin Iteración para el envio del correo, por usuario con sus radicados respectivos 
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
            
                if($generatePdf['status'] == false){

                    $transaction->rollBack();
                    $dataEstatus = $this->returnDataStatus($request['ButtonSelectedData']);

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $generatePdf['message'], 
                        'data' => $generatePdf['errors'],
                        'dataEstatus' => $dataEstatus,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['NewAnnulmentCertificate'] . ' con número: ' . $numeroActa, // texto para almacenar en el evento
                    [],
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                # Se obtiene la ruta del pdf para almacenarla en RadiRadicadoAnulado
                $rutaPdf = $generatePdf['rutaDocumento'];


                foreach ($modelRadicados as $key => &$radicado){

                    $radicado->rutaActaRadiRadicadoAnulado = $rutaPdf;
                    if (!$radicado->save()) {
                        $transaction->rollBack();
                        $dataEstatus = $this->returnDataStatus($request['ButtonSelectedData']);

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $radicado->getErrors(),
                            'dataEstatus' => $dataEstatus,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    #Consulta para obtener los datos del estado y fecha del radicado anulado actual
                    $anulacion = RadiRadicadoAnulado::findOne(['idRadiRadicadoAnulado' => $radicado->idRadiRadicadoAnulado]);

                    $idRadi = $radicado->idRadiRadicadoAnulado;

                    # Se obtiene la información del log actual
                    $log[$idRadi]['data'] = self::dataLog($anulacion);                   
                    
                }      

                # Evaluar respuesta de datos guardados #
                if ($saveDataValid == true) {                    
 
                    if(file_exists($rutaPdf)) {

                        $transaction->commit();

                        foreach($log as $info){

                            /***    Log de Auditoria  ***/
                            HelperLog::logAdd(
                                true,
                                Yii::$app->user->identity->id, //Id user
                                Yii::$app->user->identity->username, //username
                                Yii::$app->controller->route, //Modulo
                                Yii::$app->params['eventosLogText']['Edit'] . ", tabla radicadoAnulado", //texto para almacenar en el evento
                                $info['dataOld'], //data old string
                                $info['data'], //data string
                                array() //No validar estos campos
                            );
                            /***    Fin log Auditoria   ***/
                        }

                        //Lee el archivo dentro de una cadena en base 64
                        $dataFile = base64_encode(file_get_contents($rutaPdf));
                    
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','fileGenerated', [
                                'numActa' => $numeroActa,
                            ]), //Mensaje de acta generada
                            'data' => $dataResponse, // data
                            'fileName' => $fileName, //filename
                            'status' => 200,
                        ];                        
                    
                        $return = HelperEncryptAes::encrypt($response, true);
                        
                        // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                        $return['datafile'] = $dataFile;

                        return $return;

                    } else {
                        $transaction->rollBack();
                        $dataEstatus = $this->returnDataStatus($request['ButtonSelectedData']);

                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => Yii::t('app', 'fileWithoutInformationClasification') ],
                            'dataEstatus' => $dataEstatus,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                } else {
                    $transaction->rollBack();
                    $dataEstatus = $this->returnDataStatus($request['ButtonSelectedData']);

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => $errors,
                        'dataEstatus' => $dataEstatus,
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
            $dataEstatus = $this->returnDataStatus($request['ButtonSelectedData']);

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'dataEstatus' => $dataEstatus,
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }
   
    
    /**
     * Funcion que rechaza la anulación de un radicado
     **/ 
    public function actionRechazoAnulacion(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = ['idRadiRadicadoAnulado' => [4], 'observacionRadiRadicadoAnulado' => 'Rechazo de anulación radicado',
            //  'idEstado' => 1,];
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
                $dataResponse = []; // Data donde se guardan los estados nuevos de los radicados anulados
                $idRadiAnulados = []; // Array de los radicados anulados              
                
                $transaction = Yii::$app->db->beginTransaction();
                
                # Consulta la accion de la transaccion de estado rechazado para obtener su id
                $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'refusalAnnulation']);
                $idTransacion = $modelTransacion->idCgTransaccionRadicado;

                # Actualizacion de los radicados con el nuevo estado.
                foreach ($request['ButtonSelectedData'] as $infoRadicadoAnulado) {
                    
                    # Id del radicado anulado
                    $id = $infoRadicadoAnulado['id'];

                    # Observacion del rechazo de la anulación
                    $observacionRadicadoAnulado = $request['data']['observacion'];

                    $modelRadicadoAnulado = $this->findModel($id);

                    # Se obtiene la información del log anterior
                    $dataOld = self::dataLog($modelRadicadoAnulado);

                    $modelRadicadoAnulado->observacionRadiRadicadoAnulado = $observacionRadicadoAnulado;
                    $modelRadicadoAnulado->idEstado = Yii::$app->params['statusAnnulationText']['RechazoAnulacion'];

                    # Se agrupa todos los radicados seleccionados
                    $idRadiAnulados[] = $modelRadicadoAnulado->idRadicado;                    

                    if (!$modelRadicadoAnulado->save()) {
                        // Valida false ya que no se guarda
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelRadicadoAnulado->getErrors()); 
                        break;
                    }

                    #Consulta para obtener los datos del estado y fecha del radicado anulado actual
                    $anulacion = RadiRadicadoAnulado::findOne(['idRadiRadicadoAnulado' => $infoRadicadoAnulado['id']]);

                    # Se obtiene la información del log actual
                    $data = self::dataLog($anulacion);

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", tabla radicadoAnulado", //texto para almacenar en el evento
                        $dataOld,
                        $data,
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    /***    Log de Radicados  ***/
                     HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $modelRadicadoAnulado->idRadicado, //Id radicado
                        $idTransacion,
                        $observacionRadicadoAnulado, //observación 
                        $modelRadicadoAnulado,
                        array() //No validar estos campos
                    );
                    /***    Fin log Radicados   ***/ 
                    
                    // Se retorna el estado de cada registro
                    $dataResponse[] = array(
                        'id' => $id,
                        'observacionAnulacion' => $observacionRadicadoAnulado,
                        'status' => $modelRadicadoAnulado->idEstado,
                        'statusText' => $modelRadicadoAnulado->idEstado0->nombreRadiEstadoAnulacion,
                        'idInitialList' => $infoRadicadoAnulado['idInitialList'] * 1
                    );                  
                }

                # Información de usuario logueado
                $modelUserLogged = User::findOne(Yii::$app->user->identity->id);
                $userDetalles = $modelUserLogged->userDetalles;                           
                $nombresLogged = $userDetalles['nombreUserDetalles'] . ' ' . $userDetalles['apellidoUserDetalles'];
                $dependenciaLogged = $modelUserLogged->gdTrdDependencia->nombreGdTrdDependencia;

                # Consulta de datos del radicado
                $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
                $tablaUser = User::tableName() . ' AS US';            

                $modelRadicado = (new \yii\db\Query())
                    ->select(['RD.idRadiRadicado', 'RD.numeroRadiRadicado','RD.user_idTramitador', 'US.email'])
                    ->from($tablaRadicado);
                    //->innerJoin($tablaUser, '`us`.`id` = `rd`.`user_idTramitador`')
                    $modelRadicado = HelperQueryDb::getQuery('innerJoinAlias', $modelRadicado, $tablaUser, ['US' => 'id', 'RD' => 'user_idTramitador']);
                    $modelRadicado = $modelRadicado->where(['RD.idRadiRadicado' => $idRadiAnulados])
                ->all();

            
                $arrayDatos = [];
                if(!is_null($modelRadicado)){

                    # Iteración de la información agrupada del radicado
                    foreach($modelRadicado as $infoRadicado) {

                        if(isset($arrayDatos[$infoRadicado["user_idTramitador"]])){
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'] = $infoRadicado['idRadiRadicado'];

                        } else {
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['email'] = $infoRadicado['email'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['radicados'][] = $infoRadicado['numeroRadiRadicado'];
                            $arrayDatos[$infoRadicado["user_idTramitador"]]['idRadicado'] = $infoRadicado['idRadiRadicado'];
                        }
                    }
                }
                
                if (is_array($arrayDatos)) {

                    # Iteración para el envio del correo, por usuario con sus radicados respectivos
                    foreach($arrayDatos as $radicadosUsuario) {

                        # Envia la notificación de correo electronico al usuario de tramitar
                        // $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($modelRadicadoAnulado->idRadicado));   

                        # Modificación de mensajes del correo, cuando se realiza masivamente o se escoge un radicado
                        if ( count($radicadosUsuario['radicados']) > 1) {
                            $headText = 'headMailTextRechazos';
                            $textStart = 'textBodyRechazosAnulacion';
                            $subjectText = 'subjectAnnulationRefals';

                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['indexRadicado'];
                            $nameButtonLink = 'Ir al sistema'; // Variable que será traducida

                        } else {                            
                            $headText = 'headMailTextRechazo';
                            $textStart = 'textBodyRechazoAnulacion';
                            $subjectText = 'subjectAnnulationRefal';

                            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($radicadosUsuario['idRadicado']));
                            $link = Yii::$app->params['ipServer'] . Yii::$app->params['routesFront']['viewRadicado'] . $dataBase64Params;
                            $nameButtonLink = 'buttonLinkRadicado'; // Variable que será traducida
                        }
                
                        $headMailText = Yii::t('app', $headText);    

                        $numRadicado = implode(', ',$radicadosUsuario['radicados']);
                        $textBody  = Yii::t('app', $textStart, [
                            'NoRadicado'    => $numRadicado,
                            'Observacion'   => $observacionRadicadoAnulado,
                            'Username'      =>  $nombresLogged,
                            'NameDependencia'   => $dependenciaLogged,
                        ]);

                        $subject = Yii::t('app', $subjectText);
                        $bodyMail = 'radicacion-html';                    

                        $envioCorreo = CorreoController::sendEmail($radicadosUsuario['email'], $headMailText, $textBody, $bodyMail, [], $link, $subject, $nameButtonLink);
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

   
    /* Funcion que obtiene el modelo del log para la data actual y anterior */
    protected function dataLog($modelAnulation){

        if(!is_null($modelAnulation)){

            # Valores del modelo se utiliza para el log
            $labelModel = $modelAnulation->attributeLabels();
            $dataLog = '';

            foreach ($modelAnulation as $key => $value) {

                switch ($key) {
                    case 'idRadicado':
                        $dataLog .= 'Número radicado: '.$modelAnulation->idRadicado0->numeroRadiRadicado. ', ';
                    break;
                    case 'idResponsable':
                        $dataLog .= 'Nombre responsable: '.$modelAnulation->idResponsable0->userDetalles->nombreUserDetalles.' '.$modelAnulation->idResponsable0->userDetalles->apellidoUserDetalles.', ';
                    break;
                    case 'idEstado':
                        $dataLog .= 'Estado de anulación: '.$modelAnulation->idEstado0->nombreRadiEstadoAnulacion.', ';
                    break;  
                    case 'estadoRadiRadicadoAnulado':
                        $dataLog .= 'Estado: '.yii::$app->params['statusTodoNumber'][$modelAnulation->estadoRadiRadicadoAnulado].', ';
                    break; 
                    case 'rutaActaRadiRadicadoAnulado':
                        if(file_exists($value)) {
                            $dataLog .= $labelModel[$key].': '.$value.', ';
                        }
                    break;
                    case 'codigoActaRadiRadicadoAnulado':
                        if(!is_null($value)) {
                            $dataLog .= $labelModel[$key].': '.$value.', ';
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

    /**
     * Funcion para retornar data original de los status en caso de error
     */
    public function returnDataStatus($data) {
        $dataEstatus = []; 
        foreach ($data as $key => $infoRadicadoAnulado) {

            # Id del radicado anulado
            $id = $infoRadicadoAnulado['id'];

            $modelRadicadoAnulado = $this->findModel($id);
            // Se retorna el estado de cada registro
            $dataEstatus[] = array(
                'id' => $id,
                'observacionAnulacion' => $modelRadicadoAnulado->observacionRadiRadicadoAnulado,
                'status' => $modelRadicadoAnulado->idEstado,
                'statusText' => $modelRadicadoAnulado->idEstado0->nombreRadiEstadoAnulacion,
                'idInitialList' => $infoRadicadoAnulado['idInitialList'] * 1
            );
        }
        return $dataEstatus;
    }


    /**
     * Funcion para descargar el archivo pdf luego de la aceptacion de anulacion
     **/ 
    public function actionDownloadBase64($request)
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

            $id = $request['id'];
            $modelRadicadoAnulado = $this->findModel($id);

            $rutaFile = $modelRadicadoAnulado->rutaActaRadiRadicadoAnulado;
            $fileName = Yii::$app->params['baseNameFileActaAnulacion'] . $modelRadicadoAnulado->codigoActaRadiRadicadoAnulado . '.pdf';

            /* Enviar archivo en base 64 como respuesta de la petición **/
            if(file_exists($rutaFile)) {
                //Lee el archivo dentro de una cadena en base 64
                $dataFile = base64_encode(file_get_contents($rutaFile));
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'fileDownloaded') . ': ' . $fileName,
                    'data' => [], // data
                    'fileName' => $fileName, //filename
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
                    'data' => ['error' => Yii::t('app', 'dowloadDocuments') ],                        
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
     * Finds the RadiRadicadoAnulado model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return RadiRadicadoAnulado the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RadiRadicadoAnulado::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException(Yii::t('app', 'notFoundHttpException'));
    }


    /**
     * Funcion que construye la estructura del Acta en pdf
     * @param $idRadicado [Array] [Id del radicado] arreglo
     * @param $numActa [int] [Número del acta]
     * @param $observacion [Array] [Justificación de la aprobación] pero de la solicitud
     * @param $currentPage [int] [Número de pagina de inicio]
     * @param $pages [int] [Número de pagina final]
     **/ 
    public function createPdf($idRadicado, $numActa, $observacion, $currentPage, $pages, $fechaConsulta = null)
    {

        //$cantidadRadicados = implode(', ', $idRadicado);
        $nombreCliente = Yii::$app->params['cliente'];
        $nitCliente = Yii::$app->params['nit'];

        # Fecha de la solicitud de anulación
        //$modelRadicadoAnulado = RadiRadicadoAnulado::findOne(['idRadicado' => $idRadicado]);
        //$modelRadicadoAnulado = RadiRadicadoAnulado::find()->where('idRadicado IN ('.$cantidadRadicados.')')->all(); //Consulta mal hecha
        $modelRadicadoAnulado = RadiRadicadoAnulado::find()->where(['IN', 'idRadicado', $idRadicado])->all(); // Se debe pasar un array, no el implode

        # Ruta de la imagen
        $imagePath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogoPdf'];
       
        # Estilo de la tabla
        $stylesheet = '
            table {
                border-collapse: collapse;
            }
            
            table, th, td {
                border: 1px solid black;
            }';

            
        $html = 
            '<table>
                <tr>
                    <th rowspan="4">
                        <img src=":rutaLogo" style="width:150px;">
                    </th>
                    <th colspan="5">:nombre_cliente</th>
                </tr>  
                <tr>
                    <td colspan="5" style="text-align: center;">NIT: :nit</td>
                </tr>            
                <tr>
                    <td colspan="5" style="text-align: center;">:nombreEncabezado</td>
                </tr> 
                <tr>
                    <td colspan="5" style="text-align: center;">GESTIÓN DOCUMENTAL</td>
                </tr> 
                <tr>
                    <th colspan="6">ACTA No. :numActa</th>
                </tr> 
                <tr>  
                    <td colspan="4"></td>          
                    <th>PAGINA</th>
                    <td style="text-align: center;">:currentPage de :pages</td>
                </tr> 
                <tr>
                    <td colspan="6">
                        <p>&nbsp;</p>
                        <p style="text-align:justify;"> 
                            En cumplimiento a lo establecido en el Acuerdo No. 060 del 30 de octubre de 2001 expedido
                            por el Archivo General de la nación, en el cual se establecen pautas para la administración 
                            de las comunicaciones oficiales en las entidades públicas y privadas que cumplen funciones públicas. 
                        </p>
                        <p>&nbsp;</p>
                        <p style="text-align:justify;">
                            ARTICULO QUINTO: Procedimientos para la radicación de comunicaciones oficiales.
                            PARÁGRAFO: Cuando existan errores en la radicación y se anulen los números, se debe dejar
                            constancia por escrito, con la respectiva justificación y firma del Jefe de la unidad de
                            correspondencia.
                        </p>
                        <p>&nbsp;</p>
                        <p style="text-align:justify;">
                            Siendo así se deja constancia de anulación de el (los) siguiente(s) número(s) de radicación:
                        </p>
                        <p>&nbsp;</p>
                    </td>
                </tr>
                <tr>
                    <th>No. Radicado</th>
                    <th colspan="4">Justificación del Solicitante</th>
                    <th>Fecha solicitud de anulación</th>
                </tr>
            ';

            for($i = 0; $i < count($modelRadicadoAnulado); $i++) {
                $html .='
                    <tr>
                        <td>:numRadicado'.$i.'</td>
                        <td colspan="4" style="text-align: justify;">:observacion'.$i.'</td>
                        <td>:fechaSolicitud'.$i.'</td>
                    </tr>
                ';
            }            

            $html .='
                <tr></tr>
                <tr>
                    <td colspan="6">
                        <p>
                            Para constancia de la anulación, se firma el acta en Sesquilé, el día :fechaHoy
                        </p>
                    </td>
                </tr>
                <tr></tr>
                <tr>
                    <td colspan="6" style="text-align: center;">
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                        <p>
                            ______________________________________________
                        </p>
                        <p>
                            FIRMA ADMINISTRADOR DEL SISTEMA
                        </p>
                        <p>&nbsp;</p>
                        <p>&nbsp;</p>
                    </td>
                </tr>              
            </table>

            <p>&nbsp;</p>';        

        # Wildcard para el reemplazo en el html
        $token = array(
            ':rutaLogo',
            ':nombre_cliente',
            ':nit',
            ':numActa',
            ':currentPage',
            ':pages',
            ':nombreEncabezado',
            ':fechaHoy',
        );

        for($i = 0; $i < count($modelRadicadoAnulado); $i++) {
            array_push($token, ':numRadicado'.$i);
            array_push($token, ':observacion'.$i);
            array_push($token, ':fechaSolicitud'.$i);
        }

        # Valores para los wildcards
        $values = [
            $imagePath,
            $nombreCliente, 
            $nitCliente,
            $numActa,
            $currentPage,
            $pages,
            Yii::$app->params['encabezadoPdf'],
            (isset($fechaConsulta) && $fechaConsulta != null) ? date_format(date_create($fechaConsulta), 'd-m-Y') : date('d-m-Y'),         
        ];

        foreach ($modelRadicadoAnulado as $radicadoAnulado) {

            $modelRadicado = RadiRadicados::findOne(['idRadiRadicado' => $radicadoAnulado->idRadicado]);

            array_push($values, $modelRadicado->numeroRadiRadicado);
            array_push($values, $observacion[$radicadoAnulado->idRadicado]);
            array_push($values, $radicadoAnulado->fechaRadiRadicadoAnulado);
        }

        $pdf = str_replace($token, $values, $html);

        return [
            'html' => $pdf,
            'style' => $stylesheet,
            'status' => 'OK',
        ];

    }

}
