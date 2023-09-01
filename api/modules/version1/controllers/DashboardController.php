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

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperDynamicForms;
use api\components\HelperQueryDb;
use api\components\HelperLog;
use api\components\HelperConsecutivo;
use api\models\CgDiasNoLaborados;
use api\models\CgHorarioLaboral;
use api\models\CgTransaccionesRadicados;
use api\models\Clientes;
use api\models\GaHistoricoPrestamo;
use api\models\GaPrestamos;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\Log;
use api\models\RadiLogRadicados;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiRadicados;
use api\models\RadiRemitentes;
use api\models\GdTrdDependencias;
use api\models\CgTiposRadicados;
use api\models\RolesOperaciones;
use api\models\User;
use api\models\UserDetalles;
use api\modules\version1\controllers\configuracionApp\ConfiguracionGeneralController;
use api\modules\version1\controllers\radicacion\RadicadosController;

use api\models\views\ViewUnsignedDocuments;

use DateTime;
use yii\web\Controller;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

/**
 * DashboardController
 */
class DashboardController extends Controller
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
                    'index-file-expired-official'  => ['GET'],          
                    'index-file-informed'  => ['GET'],          
                    'index-return-file-official'  => ['GET'],          
                    'index-file-expired-boss'  => ['GET'],          
                    'index-vobo-request-boss'  => ['GET'],
                    'index-unsigned-documents' => ['GET'],       
                    'index-assigned-filings' => ['GET'],
                    'index-approval-loan'  => ['GET'],          
                    // 'index-return-loan'  => ['GET'],          
                    'index-transfers-accepted'  => ['GET'],          
                    'index-transfers-ges-doc' => ['GET'],
                    'index-user-by-dependency'  => ['GET'],          
                    'index-user-by-profile'  => ['GET'],          
                    'index-last-log-entries'  => ['GET'],          
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
     * Lists all files expired official.
     * @return mixed
     */
    public function actionIndexFileExpiredOfficial($request)
    {    
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

        # Consulta la accion de la transaccion y obtener su id
        $idCargarPlantilla = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'loadFormat']);
        $idCombinar = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceMatch']);

        $arrayTransaction = [
            $idCargarPlantilla->idCgTransaccionRadicado,
            $idCombinar->idCgTransaccionRadicado
        ];

        # Consulta de radicados que tienen plantilla y combinacion de correspondencia en el log.
        $modelLog = RadiLogRadicados::findAll(['idTransaccion' => $arrayTransaction]);

        $arrayIds = [];
        foreach($modelLog as $dataLog){
            $arrayIds[] = $dataLog->idRadiRadicado;
        }

        $today = date('Y-m-d');

        # Relacionamiento de los radicados vencidos
        $relationFile =  RadiRadicados::find();
            // ->innerJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
            $relationFile = $relationFile->where(['<', 'radiRadicados.fechaVencimientoRadiRadicados', $today])
            ->andWhere(['NOT IN', 'radiLogRadicados.idRadiRadicado', $arrayIds]) //Filtra los radicados que no tienen carga plantilla ni correspondencia.           
            ->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);     
        $HelperConsecutivo = new HelperConsecutivo();   
        
        $expiredDays = '';
        foreach($modelRelation as $dataRelation) {  

            # Se calculo los días vencido de acuerdo a la fecha actual
            $expiredDays = - RadicadosController::calcularDiasVencimiento($dataRelation->idRadiRadicado);

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->numeroRadiRadicado)),
            );
            
            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idRadiRadicado,
                'filingType'        => $dataRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'filingNumber'      => $HelperConsecutivo->numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado,$dataRelation->isRadicado),
                'expiredDays'       => $expiredDays.' días',
                'subject'           => $dataRelation->asuntoRadiRadicado,
                'rowSelect'         => false,
                'rowClass'          => Yii::$app->params['color']['rojo'], //Todos los radicados son de color rojo  
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexFileExpiredOfficial');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all files informed official, boss, and user ventanilla.
     * @return mixed
     */
    public function actionIndexFileInformed($request)
    {
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
       
        # Relacionamiento de los radicados informados
        $relationFile =  RadiRadicados::find();
        // ->innerJoin('radiInformados', '`radiInformados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
        $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiInformados', ['radiInformados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
        $relationFile = $relationFile->where(['radiInformados.idUser' => Yii::$app->user->identity->id]);

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);      
        $HelperConsecutivo = new HelperConsecutivo();  

        foreach($modelRelation as $dataRelation) {  

            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation->fechaVencimientoRadiRadicados)); 

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->numeroRadiRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idRadiRadicado,
                'filingType'        => $dataRelation->cgTipoRadicado->nombreCgTipoRadicado,
                'filingNumber'      => $HelperConsecutivo->numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado, $dataRelation->isRadicado),
                'subject'           => $dataRelation->asuntoRadiRadicado,
                'dateExpired'       => $expiredDate,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexFileInformedOfficial');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,   
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all return files official and user ventanilla.
     * @return mixed
     */
    public function actionIndexReturnFileOfficial($request)
    {    
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


        # Consulta la accion de la transaccion y se obtener su id
        $returnFile = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'returnFiling']);

        # Relacionamiento de los radicados devueltos
        $relationFile =  RadiLogRadicados::find();
        // ->innerJoin('radiRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
        $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
        $relationFile = $relationFile->where(['radiLogRadicados.idTransaccion' => $returnFile->idCgTransaccionRadicado])
            ->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);
            //->andWhere(['radiLogRadicados.idUser' => Yii::$app->user->identity->id]);

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);
        $HelperConsecutivo = new HelperConsecutivo();        

        foreach($modelRelation as $dataRelation) {  

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->radiRadicado->numeroRadiRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idRadiRadicado,
                'filingType'        => $dataRelation->radiRadicado->cgTipoRadicado->nombreCgTipoRadicado,
                'filingNumber'      => $HelperConsecutivo->numeroRadicadoXTipoRadicado($dataRelation->radiRadicado->numeroRadiRadicado, $dataRelation->radiRadicado->idCgTipoRadicado, $dataRelation->radiRadicado->isRadicado),
                'observation'       => $dataRelation->observacionRadiLogRadicado,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexReturnFileOfficial');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    

    /**
     * Lists all files expired boss.
     * @return mixed
     */
    public function actionIndexFileExpiredBoss($request)
    {    
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

        # Consulta la accion de la transaccion y obtener su id
        $idCargarPlantilla = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'loadFormat']);
        $idCombinar = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'correspondenceMatch']);

        $arrayTransaction = [
            $idCargarPlantilla->idCgTransaccionRadicado,
            $idCombinar->idCgTransaccionRadicado
        ];

        # Consulta de radicados que tienen plantilla y combinacion de correspondencia en el log.
        $modelLog = RadiLogRadicados::findAll(['idTransaccion' => $arrayTransaction]);

        $arrayIds = [];
        foreach($modelLog as $dataLog){
            $arrayIds[] = $dataLog->idRadiRadicado;
        }

        $today = date('Y-m-d');


        # Relacionamiento de los radicados vencidos del jefe
        $relationFile =  RadiRadicados::find();
            // ->innerJoin('radiLogRadicados', '`radiLogRadicados`.`idRadiRadicado` = `radiRadicados`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'radiLogRadicados', ['radiLogRadicados' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
            // ->innerJoin('user', '`user`.`idGdTrdDependencia` = `radiRadicados`.`idTrdDepeUserTramitador`')
            $relationFile = HelperQueryDb::getQuery('innerJoin', $relationFile, 'user', ['user' => 'idGdTrdDependencia', 'radiRadicados' => 'idTrdDepeUserTramitador']);
            // ->leftJoin('radiRadicadoAnulado', '`radiRadicadoAnulado`.`idRadicado` = `radiRadicados`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('leftJoin', $relationFile, 'radiRadicadoAnulado', ['radiRadicadoAnulado' => 'idRadicado', 'radiRadicados' => 'idRadiRadicado']);
            $relationFile = $relationFile->where(['<', 'radiRadicados.fechaVencimientoRadiRadicados', $today])
            ->andWhere(['NOT IN', 'radiLogRadicados.idRadiRadicado', $arrayIds]) //Filtra los radicados que no tienen carga plantilla ni correspondencia.       
            ->andWhere(['<>', 'radiRadicados.estadoRadiRadicado', Yii::$app->params['statusTodoText']['Finalizado']])
            ->andWhere(['user.idUserTipo' => Yii::$app->params['tipoUsuario']['Usuario Jefe']])
            ->andWhere(['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia])
            ->andWhere(['IS', 'radiRadicadoAnulado.idEstado', null]);  //Y no esta incluido en anulacion

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        $expiredDays = '';
        foreach($modelRelation as $dataRelation) {  

            $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation->idRadiRadicado]);

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($modelRemitente) > 1) {

                $nombreRemitente = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($modelRemitente as $dataRemitente){

                    if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelCliente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);                       
                        $nombreRemitente = $modelCliente->nombreCliente;
        
                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);                       
                        $nombreRemitente = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;               
                    }  
                }
            }

            #Se calculo los días vencido de acuerdo a la fecha actual
            $expiredDays = - RadicadosController::calcularDiasVencimiento($dataRelation->idRadiRadicado);

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idRadiRadicado)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->numeroRadiRadicado)),
            );
            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idRadiRadicado,
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicado($dataRelation->numeroRadiRadicado, $dataRelation->idCgTipoRadicado, $dataRelation->isRadicado),
                'expiredDays'       => $expiredDays.' días',
                'subject'           => $dataRelation->asuntoRadiRadicado,
                'sender'            => $nombreRemitente,
                'responsable'       => $dataRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$dataRelation->userIdTramitador->userDetalles->apellidoUserDetalles,
                'rowSelect'         => false,
                'rowClass'          => Yii::$app->params['color']['rojo'], //Todos los radicados son de color rojo  
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexFileExpiredBoss'); 
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,       
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all VOBO boss.
     * @return mixed
     */
    public function actionIndexVoboRequestBoss($request)
    {    
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

        # Consulta la accion de la transaccion y se obtener su id
        $voboRequest = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'voboRequest']);
        $vobo = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'vobo']);

        $idTransaccion = [
            $voboRequest->idCgTransaccionRadicado,
            $vobo->idCgTransaccionRadicado
        ];

        # Relacionamiento de los radicados en solicitud VOBO y VOBO del jefe
        $tablaRadiLog = RadiLogRadicados::tableName() . ' AS RL';
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        $tablaUser = User::tableName() . ' AS U';
        $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
        
        $relationFile = (new \yii\db\Query())
            ->from($tablaRadiLog);
            // ->innerJoin($tablaRadicado, '`rl`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaRadicado, ['RL' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
            // ->innerJoin($tablaUser, '`u`.`idGdTrdDependencia` = `rd`.`idTrdDepeUserTramitador`')
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUser, ['U' => 'idGdTrdDependencia', 'RD' => 'idTrdDepeUserTramitador']);
            // ->innerJoin($tablaUserDetalles, '`ud`.`idUser` = `rd`.`user_idTramitador`')            
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserDetalles, ['UD' => 'idUser', 'RD' => 'user_idTramitador']);
            $relationFile = $relationFile->where(['U.idUserTipo' => Yii::$app->params['tipoUsuario']['Usuario Jefe']])
            ->andWhere(['RD.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia])
            ->andwhere(['IN', 'RL.idTransaccion', $idTransaccion]);

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['RL.idRadiLogRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        


        $applicationFiled = []; //Array de radicados en solicitud VOBO
        $filesVobo = [];        //Array de radicados en VOBO
        $dataFull = [];         //Data completa de radicados en solicitud VOBO
        
        foreach($modelRelation as $dataRelation) {             

            # Proceso para obtener los radicados que SOLO estan en solicitud VOBO
            if($dataRelation['idTransaccion'] == $voboRequest->idCgTransaccionRadicado){
                
                if(isset($applicationFiled[$dataRelation['idRadiRadicado']])){

                    if(!in_array($dataRelation['fechaRadiLogRadicado'], $applicationFiled[$dataRelation['idRadiRadicado']])){
                        $applicationFiled[$dataRelation['idRadiRadicado']][] = $dataRelation['fechaRadiLogRadicado']; 

                        # Se agrega la data de todos los radicados en Solicitud VOBO
                        $dataFull[$dataRelation['idRadiRadicado']] = $dataRelation;
                    }                        
                    
                } else {
                    $applicationFiled[$dataRelation['idRadiRadicado']][] = $dataRelation['fechaRadiLogRadicado']; 

                    # Se agrega la data de todos los radicados en Solicitud VOBO
                    $dataFull[$dataRelation['idRadiRadicado']] = $dataRelation;
                }      

            } elseif($dataRelation['idTransaccion'] == $vobo->idCgTransaccionRadicado){

                if(isset($filesVobo[$dataRelation['idRadiRadicado']])){
                    if(!in_array($dataRelation['fechaRadiLogRadicado'], $filesVobo[$dataRelation['idRadiRadicado']]))
                        $filesVobo[$dataRelation['idRadiRadicado']][] = $dataRelation['fechaRadiLogRadicado'];
                    
                } else {
                    $filesVobo[$dataRelation['idRadiRadicado']][] = $dataRelation['fechaRadiLogRadicado'];    
                }
            }    
        }

        # Se itera el array de los radicados en Solicitud para compararlo con los de VOBO
        $arrayValidate = [];
        foreach($applicationFiled as $idFile => $dataArray){

            # Si el radicado en solicitud existe en el array de VOBO, se compara que la fecha sea mayor,
            # para obtener los radicados pendientes por el VOBO.
            if(array_key_exists($idFile, $filesVobo)){

                if($dataArray[0] > $filesVobo[$idFile][0] ){                    
                    $arrayValidate[] = $dataFull[$idFile];
                }

            } else { // Sino existe significa que falta el VOBO

               $arrayValidate[] = $dataFull[$idFile];
            }
        }

        # Se itera el array de los radicados pendientes por VOBO
        foreach($arrayValidate as $data){

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($data['idRadiRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($data['numeroRadiRadicado'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $data['idRadiRadicado'], 
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicado($data['numeroRadiRadicado'], $data['idCgTipoRadicado'], $data['isRadicado']),
                'responsable'       => $data['nombreUserDetalles'].' '.$data['apellidoUserDetalles'],
                'rowSelect'         => false,
                'idInitialList'     => 0
            );    
        }
              
        
        //Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexVoboRequestBoss'); 
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * sguarin Lists all Unsigned Documents boss.
     * @return mixed
     */
    public function actionIndexUnsignedDocuments($request)
    {
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

        $relationFile = ViewUnsignedDocuments::find()->all();

        # Se consulta la vista que consulta los documentos pendientes de firma
        $relationFile = ViewUnsignedDocuments::find()
            ->where(['idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]);

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $relationFile->orderBy(['idRadiRadicado' => SORT_ASC]);
        $modelRelation = $relationFile->all();

        foreach($modelRelation as $dataRelation) {

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['idRadiRadicado'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation['idRadiRadicado'],
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($dataRelation['numeroRadiRadicado']),
                'subject'           => $dataRelation['asuntoRadiRadicado'],
                'rowSelect'         => false,
                'idInitialList'     => 0,
            );
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::setListadoBD('indexUnsignedDocumentsBoss');

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Lists all approval loan.
     * @return mixed
     */
    public function actionIndexApprovalLoan($request)
    {    
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

        $idLoanStatus = [
            Yii::$app->params['statusTodoText']['PrestamoAprobado'],
            Yii::$app->params['statusTodoText']['PrestamoDevuelto'],
        ];

        # Relacionamiento de los prestamos aprobados y devueltos
        $tablaHistorico = GaHistoricoPrestamo::tableName() . ' AS H';
        $tablaPrestamo = GaPrestamos::tableName() . ' AS PR';       
        $tablaExpInclusion = GdExpedientesInclusion::tableName() . ' AS EI';
        $tablaExpediente = GdExpedientes::tableName() . ' AS EX';
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';
        
        $relationLoan = (new \yii\db\Query())
            ->from($tablaHistorico);
            // ->innerJoin($tablaPrestamo, '`h`.`idGaPrestamo` = `pr`.`idGaPrestamo`')
            $relationLoan = HelperQueryDb::getQuery('innerJoinAlias', $relationLoan, $tablaPrestamo, ['H' => 'idGaPrestamo', 'PR' => 'idGaPrestamo']);
            // ->innerJoin($tablaExpInclusion, '`pr`.`idGdExpedienteInclusion` = `ei`.`idGdExpedienteInclusion`')
            $relationLoan = HelperQueryDb::getQuery('innerJoinAlias', $relationLoan, $tablaExpInclusion, ['PR' => 'idGdExpedienteInclusion', 'EI' => 'idGdExpedienteInclusion']);
            // ->innerJoin($tablaRadicado, '`ei`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
            $relationLoan = HelperQueryDb::getQuery('innerJoinAlias', $relationLoan, $tablaRadicado, ['EI' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
            // ->innerJoin($tablaExpediente, '`ex`.`idGdExpediente` = `ei`.`idGdExpediente`')
            $relationLoan = HelperQueryDb::getQuery('innerJoinAlias', $relationLoan, $tablaExpediente, ['EX' => 'idGdExpediente', 'EI' => 'idGdExpediente']);
            // ->innerJoin($tablaUserDetalles, '`ud`.`idUser` = `pr`.`idUser`')
            $relationLoan = HelperQueryDb::getQuery('innerJoinAlias', $relationLoan, $tablaUserDetalles, ['UD' => 'idUser', 'PR' => 'idUser']);
            $relationLoan = $relationLoan->where(['IN','H.estadoGaHistoricoPrestamo', $idLoanStatus]);

        # Orden descendente para ver los últimos registros creados
        $relationLoan->orderBy(['PR.idGaPrestamo' => SORT_DESC]); 

        # Limite de la consulta
        $relationLoan->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationLoan->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);  

        $validLoan = [];
        $checkedLoan = [];
        $validLoanFull = [];
        foreach($modelRelation as $dataRelation) {      

            # Proceso para obtener los prestamos aprobados sin devolverlo
            if($dataRelation['estadoGaHistoricoPrestamo'] == Yii::$app->params['statusTodoText']['PrestamoAprobado']){
                if(!in_array($dataRelation['idGaPrestamo'], $validLoan))
                    $validLoan[] = $dataRelation['idGaPrestamo'];
                    $validLoanFull[$dataRelation['idGaPrestamo']] = $dataRelation;

            } elseif($dataRelation['estadoGaHistoricoPrestamo'] == Yii::$app->params['statusTodoText']['PrestamoDevuelto']){
                if(!in_array($dataRelation['idGaPrestamo'], $checkedLoan))
                    $checkedLoan[] = $dataRelation['idGaPrestamo'];
            } 
        }
        
        # Comparación de los prestamos que SOLO son aprobados sin devolverlo
        $validos = array_diff($validLoan,$checkedLoan);        

        # Configuracion general de los dias de notificación
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();

        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
        if($configGeneral['status']){
            
            # Dias faltantes para obtener los préstamos proximos a vencer
            $daysParam = $configGeneral['data']['diasNotificacionCgGeneral'];

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [$configGeneral['message']]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
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

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'validErrorDays')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }


        # Horario laboral activo
        $horarioValido = CgHorarioLaboral::findOne(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']]);

        if($horarioValido != null) {
            $diaMax = $horarioValido->diaFinCgHorarioLaboral; 

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [ Yii::t('app','validErrorSchedule') ]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }


        $dateToday = new DateTime();
        $today = date('Y-m-d');

        foreach($validos as $i => $id){

            if ($id == $validLoan[$i]) {

                # Variables de la data del prestamo aprobado para el dataList
                $idLoan = $validLoanFull[$id]['idGaPrestamo'];
                $filingNumber = $validLoanFull[$id]['numeroRadiRadicado'];
                $dateExpired = date('Y-m-d', strtotime($validLoanFull[$id]['fechaGaHistoricoPrestamo']));
                $expedient = $validLoanFull[$id]['numeroGdExpediente'].' - '.$validLoanFull[$id]['nombreGdExpediente'];
                $responsable = $validLoanFull[$id]['nombreUserDetalles'].' '.$validLoanFull[$id]['apellidoUserDetalles'];

                # Fecha del prestamo aprobado
                $dateLoan = new DateTime($validLoanFull[$id]['fechaGaHistoricoPrestamo']);

                # Si cumple la condición, son los prestamos que estan próximos a vencer
                if( !in_array($today, $arrayNoLaborados) && date('w', strtotime($today)) <= $diaMax && $dateLoan >= $dateToday ){
                      
                    $diff = $dateLoan->diff($dateToday);

                    # Si la diferencia es menor o igual a '$daysParam' son proximos a vencer y quedan de color amarillo
                    if ($diff->days <= $daysParam && $diff->days >= 0) {

                        $rowClass = Yii::$app->params['color']['amarillo'];

                    } else { //Sino, los prestamos quedan en color normal

                        $rowClass = ''; //Ninguno
                    }

                } else { // Sino son los préstamos vencidos

                    //$diff = $dateLoan->diff($dateToday);
                    $rowClass = Yii::$app->params['color']['rojo'];
                }


                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($validLoanFull[$id]['idGaPrestamo'])),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($validLoanFull[$id]['numeroRadiRadicado'])),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $idLoan, 
                    'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($filingNumber),
                    'dateExpired'       => $dateExpired,
                    'expedient'         => $expedient,    
                    'responsable'       => $responsable, //solicitante
                    'rowSelect'         => false,
                    'rowClass'          => $rowClass, // color 
                    'idInitialList'     => 0
                );     
            }
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexApprovalLoan'); 
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,   
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all return loan. (NO SE UTILIZA POR AHORA PORQUE EL CLIENTE ACTUAL NO LO NECESITA)
     * @return mixed
     */
    public function actionIndexReturnLoan($request)
    {    
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
        
        # Relacionamiento de los radicados devueltos
        $relationLoan =  GaHistoricoPrestamo::find();
        // ->innerJoin('gaPrestamos', '`gaPrestamos`.`idGaPrestamo` = `gaHistoricoPrestamo`.`idGaPrestamo`')
        $relationLoan = HelperQueryDb::getQuery('innerJoin', $relationLoan, 'gaPrestamos', ['gaPrestamos' => 'idGaPrestamo', 'gaHistoricoPrestamo' => 'idGaPrestamo']);
        $relationLoan = $relationLoan->where(['gaHistoricoPrestamo.estadoGaHistoricoPrestamo' =>Yii::$app->params['statusTodoText']['PrestamoDevuelto']]);

        # Orden descendente para ver los últimos registros creados
        $relationLoan->orderBy(['gaPrestamos.idGaPrestamo' => SORT_DESC]); 

        # Limite de la consulta
        $relationLoan->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationLoan->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  

            $date = date('Y-m-d', strtotime($dataRelation->fechaGaHistoricoPrestamo));

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaPrestamo0->idGaPrestamo)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idGaPrestamo,
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->radiRadicado->numeroRadiRadicado),
                'date'              => $date,
                'expedient'         => $dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->gdExpediente->numeroGdExpediente.' - '.$dataRelation->idGaPrestamo0->idGdExpedienteInclusion0->gdExpediente->nombreGdExpediente,
                'responsable'       => $dataRelation->idGaPrestamo0->idUser0->userDetalles->nombreUserDetalles.' '.$dataRelation->idGaPrestamo0->idUser0->userDetalles->apellidoUserDetalles,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexReturnLoan');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all transfers accepted.
     * @return mixed
     */
    public function actionIndexTransfersAccepted($request)
    {    
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
        
        # Consulta de transferencias aceptadas
        $relationLoan =  GdExpedientes::find()
            ->where(['gdExpedientes.estadoGdExpediente' =>Yii::$app->params['statusTodoText']['TransferenciaAceptada']]);

        # Orden descendente para ver los últimos registros creados
        $relationLoan->orderBy(['gdExpedientes.idGdExpediente' => SORT_DESC]); 

        # Limite de la consulta
        $relationLoan->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationLoan->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach($modelRelation as $dataRelation) {  

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGdExpediente)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->nombreGdExpediente)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idGdExpediente,
                'expedientNumber'   => $dataRelation->numeroGdExpediente,
                'expedientName'     => $dataRelation->nombreGdExpediente,
                'fileType'          => Yii::$app->params['ubicacionTransferenciaTRD'][$dataRelation->ubicacionGdExpediente],
                'dependency'        => $dataRelation->gdTrdDependencia->nombreGdTrdDependencia,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexTransfersAccepted');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all transfers ges doc.
     * @return mixed
     */
    public function actionIndexTransfersGesDoc($request)
    {
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
        
        # Consulta de transferencias aceptadas
        $relationLoan =  GdExpedientes::find()
            ->where(['or',
                ['gdExpedientes.estadoGdExpediente' =>Yii::$app->params['statusTodoText']['PendienteTransferir']],
                ['gdExpedientes.estadoGdExpediente' =>Yii::$app->params['statusTodoText']['TransferenciaAceptada']],
                ['gdExpedientes.estadoGdExpediente' =>Yii::$app->params['statusTodoText']['TransferenciaRechazada']],
            ]);

        # Orden descendente para ver los últimos registros creados
        $relationLoan->orderBy(['gdExpedientes.idGdExpediente' => SORT_DESC]); 

        # Limite de la consulta
        $relationLoan->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationLoan->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idGdExpediente)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->nombreGdExpediente)),
            );

            /**
             * Validar Color de la fila en base a la fecha de vencimiento cuando el estado sea "PendienteTransferir"
             * Gestion, ->>> se utiliza la fecha "tiempoGestionGdExpedientes" para el cálculo
             * central ->>> se utiliza la fecha "tiempoCentralGdExpedientes" para el cálculo
             * histórico ->>> NO APLICA
             */
            $dateToday = new DateTime();

            # Configuracion general de los dias de notificación
            $configGeneral = ConfiguracionGeneralController::generalConfiguration();

            # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
            if($configGeneral['status']){
                # Dias faltantes para obtener los proximos a vencer
                $daysParam = $configGeneral['data']['diasNotificacionCgGeneral'];
            } else {
                $daysParam = 2;
            }

            if ($dataRelation->estadoGdExpediente == Yii::$app->params['statusTodoText']['PendienteTransferir']) {
                if ($dataRelation->ubicacionGdExpediente == Yii::$app->params['ubicacionTransTRDNumber']['gestion']) {

                    # Fecha de vencimiento
                    $fechaVence = new DateTime($dataRelation->tiempoGestionGdExpedientes);

                    # Si cumple la condición, son los prestamos que estan próximos a vencer
                    if($fechaVence >= $dateToday){

                        $diff = $fechaVence->diff($dateToday);

                        # Si la diferencia es menor o igual a '$daysParam' son proximos a vencer y quedan de color amarillo
                        if ($diff->days <= $daysParam && $diff->days >= 0) {
                            $rowClass = Yii::$app->params['color']['amarillo'];
                        } else {
                            $rowClass = ''; // Ningun color
                        }

                    } else {
                        $rowClass = Yii::$app->params['color']['rojo'];
                    }

                } elseif ($dataRelation->ubicacionGdExpediente == Yii::$app->params['ubicacionTransTRDNumber']['central']) {

                    # Fecha de vencimiento
                    $fechaVence = new DateTime($dataRelation->tiempoCentralGdExpedientes);

                    # Si cumple la condición, son los prestamos que estan próximos a vencer
                    if($fechaVence >= $dateToday){

                        $diff = $fechaVence->diff($dateToday);

                        # Si la diferencia es menor o igual a '$daysParam' son proximos a vencer y quedan de color amarillo
                        if ($diff->days <= $daysParam && $diff->days >= 0) {
                            $rowClass = Yii::$app->params['color']['amarillo'];
                        } else {
                            $rowClass = ''; // Ningun color
                        }

                    } else {
                        $rowClass = Yii::$app->params['color']['rojo'];
                    }

                } else {
                    $rowClass = ''; // Ningun color // historico (N/A)
                }

            } else {
                $rowClass = ''; // Ningun color
            }

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idGdExpediente,
                'expedientNumber'   => $dataRelation->numeroGdExpediente,
                'expedientName'     => $dataRelation->nombreGdExpediente,
                'serie'             => $dataRelation->gdTrdSerie->nombreGdTrdSerie,
                'subserie'          => $dataRelation->gdTrdSubserie->nombreGdTrdSubserie,
                'creacionGdExpediente'          => $dataRelation->creacionGdExpediente,
                'tiempoGestionGdExpedientes'    => $dataRelation->tiempoGestionGdExpedientes,
                'tiempoCentralGdExpedientes'    => $dataRelation->tiempoCentralGdExpedientes,
                'tipoArchivo'       => Yii::$app->params['ubicacionTransferenciaTRD'][$dataRelation->ubicacionGdExpediente],
                'rowClass'          => $rowClass,
                'statusText'        => Yii::$app->params['statusTodoNumber'][$dataRelation->estadoGdExpediente],
                'dependency'        => $dataRelation->gdTrdDependencia->nombreGdTrdDependencia,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexTransfersAccepted');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all Radicados asignados
     * @return mixed
     */
    public function actionIndexAssignedFilings($request)
    {
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

        # Relacionamiento de los radicados
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';

        $tablaUserTramitador = User::tableName() . ' AS UT';
        $tablaUserDetallesTramitador = UserDetalles::tableName() . ' AS UDT';
        $tablaDependenciaTramitador = GdTrdDependencias::tableName() . ' AS DT';
        
        $tablaTiposRadicados = CgTiposRadicados::tableName() . ' AS TR';

        $relationFile = (new \yii\db\Query())
            ->from($tablaRadicado)
            ->select([
                'RD.idRadiRadicado', 'RD.numeroRadiRadicado', 'RD.asuntoRadiRadicado', 'RD.creacionRadiRadicado', 'RD.fechaVencimientoRadiRadicados', 'RD.estadoRadiRadicado',
                'UDT.nombreUserDetalles AS nombreTramitador', 'UDT.apellidoUserDetalles AS apellidoTramitador',
                'TR.nombreCgTipoRadicado',
            ]);

            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserTramitador, ['UT' => 'id', 'RD' => 'user_idTramitador']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserDetallesTramitador, ['UDT' => 'idUser', 'UT' => 'id']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaTiposRadicados, ['TR' => 'idCgTipoRadicado', 'RD' => 'idCgTipoRadicado']);

        /**
         * En el caso de (Usuario funcional), se deben mostrar solo los radicados donde el tramitador actual sea el usuario logueado
         * En el caso de (Usuario Jefe) se deben mostrar los radicados perteneciantes a la dependencia del usuario logueado
         */
        if (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Usuario Jefe']) {
            // Radicados donde la columna dependencia tramitadora corresponda a la dependencia del usuario logueado
            $relationFile->where(['RD.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]);
        } else {
            // Radicados donde la columna usuario tramitador corresponda al usuario logueado
            $relationFile->where(['RD.user_idTramitador' => Yii::$app->user->identity->id]);
        }

        $relationFile->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['RD.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        # Configuracion general de los dias de notificación
        $configGeneral = ConfiguracionGeneralController::generalConfiguration();

        # Si la configuración esta activa devuelve el campo solicitado, sino emite un error
        if($configGeneral['status']){
            # Dias faltantes para obtener los proximos a vencer
            $daysParam = $configGeneral['data']['diasNotificacionCgGeneral'];
        } else {
            $daysParam = 2;
        }

        foreach($modelRelation as $dataRelation) {  

            # Fecha de vencimiento
            $creationDate = date('Y-m-d', strtotime($dataRelation['creacionRadiRadicado']));
            $expiredDate = date('Y-m-d', strtotime($dataRelation['fechaVencimientoRadiRadicados']));

            // Se obtiene la traza del estado actual del radicado
            $modelLogRadicados = RadiLogRadicados::find()
                ->where(['idRadiRadicado' => $dataRelation['idRadiRadicado']])
                ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                ->one();

            $transaccion = '';
            if(!is_null($modelLogRadicados)){
                $transaccion = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
            }

            # Fecha de vencimiento
            $fechaVence = new DateTime($expiredDate);
            $dateToday = new DateTime();

            # Si cumple la condición, son los prestamos que estan próximos a vencer
            if($fechaVence >= $dateToday){

                $diff = $fechaVence->diff($dateToday);

                # Si la diferencia es menor o igual a '$daysParam' son proximos a vencer y quedan de color amarillo
                if ($diff->days <= $daysParam && $diff->days >= 0) {
                    $rowClass = Yii::$app->params['color']['amarillo'];
                } else {
                    $rowClass = ''; // Ningun color
                }

            } else {
                $rowClass = Yii::$app->params['color']['rojo'];
            }

            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['idRadiRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['numeroRadiRadicado'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation['idRadiRadicado'],
                'filingType'        => $dataRelation['nombreCgTipoRadicado'],
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($dataRelation['numeroRadiRadicado']),
                'subject'           => $dataRelation['asuntoRadiRadicado'],
                'creationDate'      => $creationDate,
                'expiredDate'       => $expiredDate,
                'userTramitador'    => $dataRelation['nombreTramitador'] . ' ' . $dataRelation['apellidoTramitador'],
                'status'            => $dataRelation['estadoRadiRadicado'],
                'statusText'        => $dataRelation['estadoRadiRadicado'],
                'statusText'        => Yii::t('app', $transaccion) . ' - ' .  Yii::t('app', 'statusTodoNumber')[$dataRelation['estadoRadiRadicado']],
                'rowClass'          => $rowClass,
                'rowSelect'         => false,
                'idInitialList'     => 0
            );
        }

        // Validar que el formulario exista 
        $formType = [];

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }


    /* Servicio del conteo de usuarios */
    public function actionUserCounter(){

        # Conteo usuarios activos
        $activeUser =  User::find()
            ->where(['user.status' =>Yii::$app->params['statusTodoText']['Activo']])
            ->count();

         # Conteo usuarios inactivos
        $inactiveUser =  User::find()
            ->where(['user.status' =>Yii::$app->params['statusTodoText']['Inactivo']])
            ->count();

        
        $data = [
            'active' => $activeUser,
            'inactive' => $inactiveUser,
        ];

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    /**
     * Lists all user by dependency.
     * @return mixed
     */
    public function actionIndexUserByDependency($request){

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
        
        # Esquema para generar el string del count antes del SELECT
        $columnCountUser = HelperQueryDb::getColumnCount('user', 'id', 'user'); // Tabla , campo, alias // COUNT(user.id) AS user

        # Consulta de usuarios por dependencia
        $relationCounter =  (new \yii\db\Query())
            ->select([$columnCountUser,'gdTrdDependencias.idGdTrdDependencia', 'gdTrdDependencias.nombreGdTrdDependencia'])
            ->from('gdTrdDependencias');
            // ->innerJoin('user', '`user`.`idGdTrdDependencia` = `gdTrdDependencias`.`idGdTrdDependencia`')
            $relationCounter = HelperQueryDb::getQuery('innerJoin', $relationCounter, 'user', ['user' => 'idGdTrdDependencia', 'gdTrdDependencias' => 'idGdTrdDependencia']);
            $relationCounter = $relationCounter->where(['gdTrdDependencias.estadoGdTrdDependencia' =>Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['gdTrdDependencias.nombreGdTrdDependencia', 'gdTrdDependencias.idGdTrdDependencia']);

        # Orden descendente para ver los últimos registros creados
        $relationCounter->orderBy(['gdTrdDependencias.idGdTrdDependencia' => SORT_DESC]); 

        # Limite de la consulta
        $relationCounter->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationCounter->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  
           
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['idGdTrdDependencia'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['nombreGdTrdDependencia'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation['idGdTrdDependencia'],
                'dependency'        => $dataRelation['nombreGdTrdDependencia'],  
                'countUser'         => $dataRelation['user'],            
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexUserByDependency');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all user by profile.
     * @return mixed
     */
    public function actionIndexUserByProfile($request){

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
        
        # Esquema para generar el string del count antes del SELECT
        $columnCountUser = HelperQueryDb::getColumnCount('user', 'id', 'user'); // Tabla , campo, alias // COUNT(user.id) AS user

        # Consulta de usuarios por perfil
        $relationCounter =  (new \yii\db\Query())
            ->select([$columnCountUser,'roles.idRol', 'roles.nombreRol'])
            ->from('roles');
            // ->innerJoin('user', '`user`.`idRol` = `roles`.`idRol`')
            $relationCounter = HelperQueryDb::getQuery('innerJoin', $relationCounter, 'user', ['user' => 'idRol', 'roles' => 'idRol']);
            $relationCounter = $relationCounter->where(['roles.estadoRol' =>Yii::$app->params['statusTodoText']['Activo']])
            ->groupBy(['roles.nombreRol', 'roles.idRol']);

        # Orden descendente para ver los últimos registros creados
        $relationCounter->orderBy(['roles.idRol' => SORT_DESC]); 

        # Limite de la consulta
        $relationCounter->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationCounter->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  
           
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['idRol'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['nombreRol'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation['idRol'],
                'profile'           => $dataRelation['nombreRol'],  
                'countUser'         => $dataRelation['user'],            
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexUserByProfile');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /**
     * Lists all last log entries.
     * @return mixed
     */
    public function actionIndexLastLogEntries($request){

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
        

        # Consulta los últimos registros del log
        $relationLog =  Log::find();

        # Orden descendente para ver los últimos registros creados
        $relationLog->orderBy(['log.idLog' => SORT_DESC]); 

        # Limite de la consulta
        $relationLog->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationLog->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  

            $routeTreatment = str_replace('/', '%', $dataRelation->moduloLog);
            $module = RolesOperaciones::findOne(['nombreRolOperacion' => $routeTreatment]);

            if($module){
                $moduleName = $module->aliasRolOperacion;

            } else { // Se realiza un tratamiento a las operaciones que no se guardan directamente en la tabla si no son por defecto

                $moduleName = HelperLog::getDefaultModule($dataRelation->moduloLog);
            }
           
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation->idLog)),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation->idLog,
                'userName'          => $dataRelation->user->userDetalles->nombreUserDetalles.' '.$dataRelation->user->userDetalles->apellidoUserDetalles,  
                'dateLog'           => $dataRelation->fechaLog,            
                'event'             => $dataRelation->eventoLog,            
                'module'            => $moduleName,            
                'rowSelect'         => false,
                'idInitialList'     => 0
            );                
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexLastLogEntries');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,      
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }



    /**
     * Lists all files created by user window
     * @return mixed
     */
    public function actionIndexFilesCreatedWindow($request)
    {    
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

        # Relacionamiento de los radicados
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';

        $tablaUserCreador = User::tableName() . ' AS UC';
        $tablaUserDetallesCreador = UserDetalles::tableName() . ' AS UDC';
        $tablaDependenciaCreador = GdTrdDependencias::tableName() . ' AS DC';

        $tablaUserTramitador = User::tableName() . ' AS UT';
        $tablaUserDetallesTramitador = UserDetalles::tableName() . ' AS UDT';
        $tablaDependenciaTramitador = GdTrdDependencias::tableName() . ' AS DT';
        
        $tablaTiposRadicados = CgTiposRadicados::tableName() . ' AS TR';

        $relationFile = (new \yii\db\Query())
            ->from($tablaRadicado)
            ->select([
                'RD.idRadiRadicado', 'RD.numeroRadiRadicado', 'RD.asuntoRadiRadicado', 'RD.fechaVencimientoRadiRadicados', 'RD.estadoRadiRadicado',
                'UDC.nombreUserDetalles AS nombreCreador', 'UDC.apellidoUserDetalles AS apellidoCreador',
                'UDT.nombreUserDetalles AS nombreTramitador', 'UDT.apellidoUserDetalles AS apellidoTramitador',
                'DC.codigoGdTrdDependencia AS codDepeCreadora', 'DC.nombreGdTrdDependencia AS nombreDepeCreadora',
                'DT.codigoGdTrdDependencia AS codDepeTramitadora', 'DT.nombreGdTrdDependencia AS nombreDepeTramitadora',
                'TR.nombreCgTipoRadicado',
            ]);

            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserCreador, ['UC' => 'id', 'RD' => 'user_idCreador']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserDetallesCreador, ['UDC' => 'idUser', 'UC' => 'id']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaDependenciaCreador, ['DC' => 'idGdTrdDependencia', 'UC' => 'idGdTrdDependencia']);

            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserTramitador, ['UT' => 'id', 'RD' => 'user_idTramitador']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaUserDetallesTramitador, ['UDT' => 'idUser', 'UT' => 'id']);
            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaDependenciaTramitador, ['DT' => 'idGdTrdDependencia', 'UT' => 'idGdTrdDependencia']);

            $relationFile = HelperQueryDb::getQuery('innerJoinAlias', $relationFile, $tablaTiposRadicados, ['TR' => 'idCgTipoRadicado', 'RD' => 'idCgTipoRadicado']);

        
        /**
         * En el caso de (Ventanilla de radicación), se deben mostrar solo los radicados creados por el usuario logueado. Con título y el filtro (Radicados creados hoy)
         * En el caso de (administrador gestión documental) se deben mostrar todos los radicados en general del sistema Con título y el filtro (Radicados de entrada y salida creados hoy)
         */
        if (Yii::$app->user->identity->idUserTipo == Yii::$app->params['tipoUsuario']['Administrador de Gestión Documental']) {

            $idSRadiPermitidos = [
                Yii::$app->params['idCgTipoRadicado']['radiSalida'], 
                Yii::$app->params['idCgTipoRadicado']['radiEntrada']
            ];

            $relationFile->where(['RD.idTrdDepeUserCreador' => Yii::$app->user->identity->idGdTrdDependencia]);
            $relationFile->where(['IN', 'RD.idCgTipoRadicado', $idSRadiPermitidos]);

        } else {
            $relationFile->where(['RD.user_idCreador' => Yii::$app->user->identity->id]);
        }


        $relationFile->andWhere(['>=', 'RD.creacionRadiRadicado', date('Y-m-d').Yii::$app->params['timeStart']]);
        $relationFile->andWhere(['<=', 'RD.creacionRadiRadicado', date('Y-m-d').Yii::$app->params['timeEnd']]);

        # Orden descendente para ver los últimos registros creados
        $relationFile->orderBy(['RD.idRadiRadicado' => SORT_DESC]); 

        # Limite de la consulta
        $relationFile->limit(Yii::$app->params['limitDashboardRecords']);
        $modelRelation = $relationFile->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);        

        foreach($modelRelation as $dataRelation) {  

            $modelSender = RadiRemitentes::findAll(['idRadiRadicado' => $dataRelation['idRadiRadicado']]);

            # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
            if(count($modelSender) > 1) {

                $senderName = 'Múltiples Remitentes/Destinatarios';

            } else {
                foreach($modelSender as $dataSender){

                    // Se obtiene la traza del estado actual del radicado
                    $modelLogRadicados = RadiLogRadicados::find()
                        ->where(['idRadiRadicado' => $dataRelation['idRadiRadicado']])
                        ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                        ->one();

                    $transaccion = '';
                    if(!is_null($modelLogRadicados)){
                        $transaccion = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
                    }

                    if($dataSender->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                        # Se obtiene la información del cliente
                        $modelCliente = Clientes::findOne(['idCliente' => $dataSender->idRadiPersona]);
                        $senderName = $modelCliente->nombreCliente;

                    } else {
                        # Se obtiene la información del usuario o funcionario
                        $modelUser = User::findOne(['id' => $dataSender->idRadiPersona]);
                        $senderName = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;
                    }
                }
            }

            # Fecha de vencimiento
            $expiredDate = date('Y-m-d', strtotime($dataRelation['fechaVencimientoRadiRadicados'])); 


            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['idRadiRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['numeroRadiRadicado'])),
            );

            # Listado de informacion
            $dataList[] = array(
                'data'              => $dataBase64Params,
                'id'                => $dataRelation['idRadiRadicado'],
                'filingType'        => $dataRelation['nombreCgTipoRadicado'],
                'filingNumber'      => HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($dataRelation['numeroRadiRadicado']),
                'subject'           => $dataRelation['asuntoRadiRadicado'],
                'sender'            => $senderName,
                'dateExpired'       => $expiredDate,

                'depeCreadora'      => $dataRelation['codDepeCreadora'] . ' - ' . $dataRelation['nombreDepeCreadora'],
                'userCreador'       => $dataRelation['nombreCreador'] . ' ' . $dataRelation['apellidoCreador'],
                'depeTramitadora'   => $dataRelation['codDepeTramitadora'] . ' - ' . $dataRelation['nombreDepeTramitadora'],
                'userTramitador'    => $dataRelation['nombreTramitador'] . ' ' . $dataRelation['apellidoTramitador'],
                'statusText'        => $dataRelation['estadoRadiRadicado'],
                'statusText'        => Yii::t('app', $transaccion) . ' - ' . yii::t('app', 'statusTodoNumber')[$dataRelation['estadoRadiRadicado']],
                'rowSelect'         => false,
                'idInitialList'     => 0
            );
        }

        // Validar que el formulario exista 
        $formType = HelperDynamicForms::createDataForForm('indexFileCreateWindow');
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => $formType,   
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }


}
