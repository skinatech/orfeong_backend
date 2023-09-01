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

namespace api\modules\version1\controllers\consultas;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use Exception;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;



/**
 * Consulta implements the CRUD actions for consultaAdi model.
 */
class ConsultaAdiController extends Controller
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
                    'view'  => ['GET'],
                    'download-document' => ['PUT'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /* Función que consulta la tabla mertRecibido de ADI */
    protected function mertRecibido(){

        $modelMertRecibido = (new \yii\db\Query())
            ->select('RAD.FECDOCUMENTO fechaRadicado, RAD.IDDOCUMENTO numeroRadicado, ASUNTO.NOMASUNTO asunto, DEP.IDDEPENDENCIA codDependencia, DEP.NOMDEPENDENCIA nomDependencia, USU.NMBRE_USRIO usuario, ENT.NOMENTIDAD proveedor, TIPDOC.NOMTIPO nomTipoDoc, EXP.IDENTIDAD numExpediente, TIPEXP.NOMTIPOEXP nomExpediente, SERIE.IDTESAURO codSerie, SERIE.NOMTESAURO nomSerie, SUBSERIE.IDSUBTESAURO codSubserie, SUBSERIE.NOMSUBTESAURO nomSubserie, RAD.DESDOCUMENTO referencia, RAD.OBSDOCUMENTO observacion, PRIO.DESCPRIORIDAD prioridad, DEST.NMBRE_USRIO destinatario')
            ->from('ADI.MERT_RECIBIDO RAD')
            ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
            ->innerJoin('ADI.MERT_ASUNTO ASUNTO', 'RAD.IDASUNTO = ASUNTO.IDASUNTO')
            ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
            ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'RAD.IDUSUARIO_RAD = USU.ID_USRIO')
            ->innerJoin('ADI.SST_USRIOS_SSTMA DEST', 'RAD.IDDESTINATARIO = DEST.ID_USRIO')
            ->innerJoin('ADI.MERT_ENTIDAD ENT', 'RAD.IDREMITENTE = ENT.IDENTIDAD')
            ->innerJoin('ADI.MERT_TIPODOC TIPDOC', 'RAD.IDTIPO = TIPDOC.IDTIPO')
            ->innerJoin('ADI.MERT_PRIORIDAD_DOC PRIO', 'RAD.IDPRIORIDAD = PRIO.IDPRIORIDAD')
            ->leftJoin('ADI.MERT_EXPEDIENTE_RELACIONADO EXPREL', 'RAD.IDDOCUMENTO = EXPREL.IDDOCUMENTO')
            ->leftJoin('ADI.MERT_EXPEDIENTE EXP', 'EXPREL.IDEXPEDIENTE = EXP.IDEXPEDIENTE')
            ->leftJoin('ADI.MERT_TIPOEXP TIPEXP', 'EXP.IDTIPOEXP = TIPEXP.IDTIPOEXP')
            ->leftJoin('ADI.MERT_TESAURO SERIE', 'EXP.IDTESAURO = SERIE.IDTESAURO')
            ->leftJoin('ADI.MERT_SUBTESAURO SUBSERIE', 'SERIE.IDTESAURO = SUBSERIE.IDTESAURO');

        return $modelMertRecibido;
    }

    /* Función que consulta la tabla mertInterno de ADI */
    protected function mertInterno(){

        $modelMertInterno = (new \yii\db\Query())
            ->select('RAD.FECDOCUMENTO fechaRadicado, RAD.IDDOCUMENTO numeroRadicado, ASUNTO.NOMASUNTO asunto, DEP.IDDEPENDENCIA codDependencia, DEP.NOMDEPENDENCIA nomDependencia, USU.NMBRE_USRIO usuario, REMIT.NMBRE_USRIO proveedor, TIPDOC.NOMTIPO nomTipoDoc, EXP.IDENTIDAD numExpediente, TIPEXP.NOMTIPOEXP nomExpediente, SERIE.IDTESAURO codSerie, SERIE.NOMTESAURO nomSerie, SUBSERIE.IDSUBTESAURO codSubserie, SUBSERIE.NOMSUBTESAURO nomSubserie, RAD.DESDOCUMENTO referencia, RAD.OBSDOCUMENTO observacion, PRIO.DESCPRIORIDAD prioridad, DEST.NMBRE_USRIO destinatario')
            ->from('ADI.MERT_INTERNO RAD')
            ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
            ->innerJoin('ADI.MERT_ASUNTO ASUNTO', 'RAD.IDASUNTO = ASUNTO.IDASUNTO')
            ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
            ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'RAD.IDUSUARIO_RAD = USU.ID_USRIO')
            ->innerJoin('ADI.SST_USRIOS_SSTMA DEST', 'RAD.IDDESTINATARIO = DEST.ID_USRIO')
            ->innerJoin('ADI.SST_USRIOS_SSTMA REMIT', 'RAD.IDREMITENTE = REMIT.ID_USRIO')
            //->innerJoin('ADI.MERT_ENTIDAD ENT', 'RAD.IDREMITENTE = ENT.IDENTIDAD')
            ->innerJoin('ADI.MERT_TIPODOC TIPDOC', 'RAD.IDTIPO = TIPDOC.IDTIPO')
            ->innerJoin('ADI.MERT_PRIORIDAD_DOC PRIO', 'RAD.IDPRIORIDAD = PRIO.IDPRIORIDAD')
            ->leftJoin('ADI.MERT_EXPEDIENTE_RELACIONADO EXPREL', 'RAD.IDDOCUMENTO = EXPREL.IDDOCUMENTO')
            ->leftJoin('ADI.MERT_EXPEDIENTE EXP', 'EXPREL.IDEXPEDIENTE = EXP.IDEXPEDIENTE')
            ->leftJoin('ADI.MERT_TIPOEXP TIPEXP', 'EXP.IDTIPOEXP = TIPEXP.IDTIPOEXP')
            ->leftJoin('ADI.MERT_TESAURO SERIE', 'EXP.IDTESAURO = SERIE.IDTESAURO')
            ->leftJoin('ADI.MERT_SUBTESAURO SUBSERIE', 'SERIE.IDTESAURO = SUBSERIE.IDTESAURO');

        return $modelMertInterno;
    }


    /* Función que consulta la tabla mertExterno de ADI */
    protected function mertExterno(){

        $modelMertExterno = (new \yii\db\Query())
            ->select('RAD.FECDOCUMENTO fechaRadicado, RAD.IDDOCUMENTO numeroRadicado, ASUNTO.NOMASUNTO asunto, DEP.IDDEPENDENCIA codDependencia, DEP.NOMDEPENDENCIA nomDependencia, USU.NMBRE_USRIO usuario, REMIT.NMBRE_USRIO proveedor, TIPDOC.NOMTIPO nomTipoDoc, EXP.IDENTIDAD numExpediente, TIPEXP.NOMTIPOEXP nomExpediente, SERIE.IDTESAURO codSerie, SERIE.NOMTESAURO nomSerie, SUBSERIE.IDSUBTESAURO codSubserie, SUBSERIE.NOMSUBTESAURO nomSubserie, RAD.DESDOCUMENTO referencia, RAD.OBSDOCUMENTO observacion, PRIO.DESCPRIORIDAD prioridad, ENT.NOMENTIDAD destinatario')
            ->from('ADI.MERT_EXTERNO RAD')
            ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
            ->innerJoin('ADI.MERT_ASUNTO ASUNTO', 'RAD.IDASUNTO = ASUNTO.IDASUNTO')
            ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
            ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'RAD.IDUSUARIO_RAD = USU.ID_USRIO')
            //->innerJoin('ADI.SST_USRIOS_SSTMA DEST', 'RAD.IDDESTINATARIO = DEST.ID_USRIO')
            ->innerJoin('ADI.SST_USRIOS_SSTMA REMIT', 'RAD.IDREMITENTE = REMIT.ID_USRIO')
            ->innerJoin('ADI.MERT_ENTIDAD ENT', 'RAD.IDREMITENTE = ENT.IDENTIDAD')
            ->innerJoin('ADI.MERT_TIPODOC TIPDOC', 'RAD.IDTIPO = TIPDOC.IDTIPO')
            ->innerJoin('ADI.MERT_PRIORIDAD_DOC PRIO', 'RAD.IDPRIORIDAD = PRIO.IDPRIORIDAD')
            ->leftJoin('ADI.MERT_EXPEDIENTE_RELACIONADO EXPREL', 'RAD.IDDOCUMENTO = EXPREL.IDDOCUMENTO')
            ->leftJoin('ADI.MERT_EXPEDIENTE EXP', 'EXPREL.IDEXPEDIENTE = EXP.IDEXPEDIENTE')
            ->leftJoin('ADI.MERT_TIPOEXP TIPEXP', 'EXP.IDTIPOEXP = TIPEXP.IDTIPOEXP')
            ->leftJoin('ADI.MERT_TESAURO SERIE', 'EXP.IDTESAURO = SERIE.IDTESAURO')
            ->leftJoin('ADI.MERT_SUBTESAURO SUBSERIE', 'SERIE.IDTESAURO = SUBSERIE.IDTESAURO');

        return $modelMertExterno;
    }


    /**
     * Lists all consulta adi tables.
     * @return mixed
     */
    public function actionIndex($request)
    {
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

            //Lista de campos de la configuracion
            $dataList = [];
            $dataBase64Params = "";

            # Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if ( is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info){

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if( isset($info) && $info !== null && trim($info) !== ''){
                                $dataWhere[$key] =  $info;
                            }

                        }  else {
                            if( isset($info) && !empty($info) ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Conexión a la base de datos de oracle aeronautica
            $connection = Yii::$app->get('dbOracleA');

            # Consultas de cada mert
            $modelMertRecibido = $this->mertRecibido();
            $modelMertInterno = $this->mertInterno();
            $modelMertExterno = $this->mertExterno();
            
            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {
                $value = strtoupper($value);

                switch ($field) {
                    case 'fechaInicial':
                        # Modificar el formato de fecha
                        $dateNew = date_create($value);
                        $date = date_format($dateNew,"d-M-y");

                        $modelMertRecibido->andWhere(['>=', 'RAD.FECDOCUMENTO', trim($date)]);
                        $modelMertInterno->andWhere(['>=', 'RAD.FECDOCUMENTO', trim($date)]);
                        $modelMertExterno->andWhere(['>=', 'RAD.FECDOCUMENTO', trim($date)]);
                    break;
                    case 'fechaFinal':
                        # Modificar el formato de fecha
                        $dateNew = date_create($value);
                        $date = date_format($dateNew,"d-M-y");
                        
                        $modelMertRecibido->andWhere(['<=', 'RAD.FECDOCUMENTO', trim($date)]);
                        $modelMertInterno->andWhere(['<=', 'RAD.FECDOCUMENTO', trim($date)]);
                        $modelMertExterno->andWhere(['<=', 'RAD.FECDOCUMENTO', trim($date)]);
                    break;
                    case 'IDDEPENDENCIA':
                    case 'NOMDEPENDENCIA':
                        $modelMertRecibido->andWhere([ Yii::$app->params['like'], 'DEP.' . $field, $value]);
                        $modelMertInterno->orWhere([ Yii::$app->params['like'], 'DEP.' . $field, $value]);
                        $modelMertExterno->orWhere([ Yii::$app->params['like'], 'DEP.' . $field, $value]);
                    break;
                    case 'FUNCIONARIO':
                        $modelMertRecibido->andWhere([ Yii::$app->params['like'], 'USU.'.'NMBRE_USRIO', $value ]);
                        $modelMertInterno->orWhere([ Yii::$app->params['like'], 'USU.'.'NMBRE_USRIO', $value ]);
                        $modelMertExterno->orWhere([ Yii::$app->params['like'], 'USU.'.'NMBRE_USRIO', $value ]);
                    break;
                    case 'DESTINATARIO':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'DEST.'.'NMBRE_USRIO', $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'DEST.'.'NMBRE_USRIO', $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'ENT.'.'NOMENTIDAD', $value]);
                    break;
                    case 'PROVEEDOR': //pendiente validar
                        $modelMertRecibido->andWhere([ 'or', [ Yii::$app->params['like'],'ENT.NOMENTIDAD', $value ], [ Yii::$app->params['like'], 'ENT.IDENTIDAD', $value ] ]);
                        $modelMertInterno->andWhere([ 'or', [ Yii::$app->params['like'],'REMIT.NMBRE_USRIO', $value ], [ Yii::$app->params['like'], 'REMIT.ID_USRIO', $value ] ]);
                        $modelMertExterno->andWhere([ 'or', [ Yii::$app->params['like'],'REMIT.NMBRE_USRIO', $value ], [ Yii::$app->params['like'], 'REMIT.ID_USRIO', $value ] ]);
                    break;
                    case 'NOMTIPO':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'TIPDOC.' . $field, $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'TIPDOC.' . $field, $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'TIPDOC.' . $field, $value]);
                    break;
                    case 'IDENTIDAD':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'EXP.' . $field, $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'EXP.' . $field, $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'EXP.' . $field, $value]);
                    break;
                    case 'NOMTIPOEXP':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'TIPEXP.' . $field, $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'TIPEXP.' . $field, $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'TIPEXP.' . $field, $value]);
                    break;
                    case 'IDTESAURO':
                    case 'NOMTESAURO':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'SERIE.' . $field, $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'SERIE.' . $field, $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'SERIE.' . $field, $value]);
                    break;
                    case 'IDSUBTESAURO':
                    case 'NOMSUBTESAURO':
                        $modelMertRecibido->andWhere([Yii::$app->params['like'], 'SUBSERIE.' . $field, $value]);
                        $modelMertInterno->orWhere([Yii::$app->params['like'], 'SUBSERIE.' . $field, $value]);
                        $modelMertExterno->orWhere([Yii::$app->params['like'], 'SUBSERIE.' . $field, $value]);
                    break;
                    case 'NOMASUNTO':
                        $modelMertRecibido->andWhere([ Yii::$app->params['like'], 'ASUNTO.' . $field, $value ]);
                        $modelMertInterno->orWhere([ Yii::$app->params['like'], 'ASUNTO.' . $field, $value ]);
                        $modelMertExterno->orWhere([ Yii::$app->params['like'], 'ASUNTO.' . $field, $value ]);
                    break;
                    default: //Numero radicado, observacion
                        $modelMertRecibido->andWhere([ Yii::$app->params['like'], 'RAD.' . $field, $value]);
                        $modelMertInterno->orWhere([ Yii::$app->params['like'] , 'RAD.' . $field, $value ]);
                        $modelMertExterno->orWhere([ Yii::$app->params['like'] , 'RAD.' . $field, $value ]);
                    break;  
                }                
            }
            //$relationCounter = (new \yii\db\Query()) ->select(['*']) ->from('gdTrdDependencias'); 
            //return $modelMertRecibido->createCommand()->getRawSql();

            # Orden descendente para ver los últimos registros creados
            //$modelMertRecibido->orderBy(['RAD.ROWNUMID' => SORT_DESC]); 

            # Limite de la consulta
            $modelMertRecibido->limit(Yii::$app->params['limitAdi']);
            $modelMertInterno->limit(Yii::$app->params['limitAdi']);
            $modelMertExterno->limit(Yii::$app->params['limitAdi']);
            
            $modelRelation = $modelMertRecibido->all($connection);
            $modelRelation2 = $modelMertInterno->all($connection);
            $modelRelation3 = $modelMertExterno->all($connection);

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);
            $modelRelation2 = array_reverse($modelRelation2);
            $modelRelation3 = array_reverse($modelRelation3);

            # Mert_Recibido
            foreach($modelRelation as $dataRelation) {              
    
                $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['numeroRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode('Recibido')), // Envia la tabla como parametro
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation['numeroRadicado'],
                    'numRadicado'       => $dataRelation['numeroRadicado'],
                    'fechaRadicado'     => $dataRelation['fechaRadicado'],
                    'asunto'            => $dataRelation['asunto'],
                    'dependencia'       => $dataRelation['codDependencia']. ' - '.  $dataRelation['nomDependencia'],
                    'funcionario'       => $dataRelation['usuario'],
                    'proveedor'         => $dataRelation['proveedor'],
                    'destinatario'      => $dataRelation['destinatario'],
                    'tipoDocumental'    => $dataRelation['nomTipoDoc'],
                    'numExpediente'     => $dataRelation['numExpediente'],
                    'nomExpediente'     => $dataRelation['nomExpediente'],
                    'serie'             => $dataRelation['codSerie']. ' - '.  $dataRelation['nomSerie'],
                    'subserie'          => $dataRelation['codSubserie']. ' - '.  $dataRelation['nomSubserie'],
                    'observacion'       => $dataRelation['observacion'],
                    'referencia'        => $dataRelation['referencia'],
                    'prioridad'         => $dataRelation['prioridad'],
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                
            }

            # Mert_Interno
            foreach($modelRelation2 as $dataRelation2) {              
    
                $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation2['numeroRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode('Interno')), // Envia la tabla como parametro
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation2['numeroRadicado'],
                    'numRadicado'       => $dataRelation2['numeroRadicado'],
                    'fechaRadicado'     => $dataRelation2['fechaRadicado'],
                    'asunto'            => $dataRelation2['asunto'],
                    'dependencia'       => $dataRelation2['codDependencia']. ' - '.  $dataRelation2['nomDependencia'],
                    'funcionario'       => $dataRelation2['usuario'],
                    'proveedor'         => $dataRelation2['proveedor'],
                    'destinatario'      => $dataRelation2['destinatario'],
                    'tipoDocumental'    => $dataRelation2['nomTipoDoc'],
                    'numExpediente'     => $dataRelation2['numExpediente'],
                    'nomExpediente'     => $dataRelation2['nomExpediente'],
                    'serie'             => $dataRelation2['codSerie']. ' - '.  $dataRelation2['nomSerie'],
                    'subserie'          => $dataRelation2['codSubserie']. ' - '.  $dataRelation2['nomSubserie'],
                    'observacion'       => $dataRelation2['observacion'],
                    'referencia'        => $dataRelation['referencia'],
                    'prioridad'         => $dataRelation2['prioridad'],
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                
            }

            # Mert_Externo
            foreach($modelRelation3 as $dataRelation3) {              

                $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation3['numeroRadicado'])),
                str_replace(array('/', '+'), array('_', '-'), base64_encode('Externo')), // Envia la tabla como parametro
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation3['numeroRadicado'],
                    'numRadicado'       => $dataRelation3['numeroRadicado'],
                    'fechaRadicado'     => $dataRelation3['fechaRadicado'],
                    'asunto'            => $dataRelation3['asunto'],
                    'dependencia'       => $dataRelation3['codDependencia']. ' - '.  $dataRelation3['nomDependencia'],
                    'funcionario'       => $dataRelation3['usuario'],
                    'proveedor'         => $dataRelation3['proveedor'],
                    'destinatario'      => $dataRelation3['destinatario'],
                    'tipoDocumental'    => $dataRelation3['nomTipoDoc'],
                    'numExpediente'     => $dataRelation3['numExpediente'],
                    'nomExpediente'     => $dataRelation3['nomExpediente'],
                    'serie'             => $dataRelation3['codSerie']. ' - '.  $dataRelation3['nomSerie'],
                    'subserie'          => $dataRelation3['codSubserie']. ' - '.  $dataRelation3['nomSubserie'],
                    'observacion'       => $dataRelation3['observacion'],
                    'referencia'        => $dataRelation['referencia'],
                    'prioridad'         => $dataRelation3['prioridad'],
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                
            }


            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexConsultaAdi');
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => $formType,        
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
   

     /** Displays a single Consultas ADI MERT_RECIBIDO model.
    * @param integer $id
    * @return mixed
    * @throws NotFoundHttpException if the model cannot be found
    */
    public function actionViewRecibido($request)
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
            $dataList = [];

            # Conexión a la base de datos de oracle aeronautica
            $connection = Yii::$app->get('dbOracleA');

            # Model de mert recibido
            $model = $this->mertRecibido();
            $modelRecibido = $model->where(['IN', 'RAD.IDDOCUMENTO', $id])->one($connection);
           
            # DETALLE RADICADO RECIBIDO
            $dataList = [
                ['alias' => 'Fecha radicado',       'value' => $modelRecibido['fechaRadicado']],
                ['alias' => 'Número radicado',      'value' => $modelRecibido['numeroRadicado']],
                ['alias' => 'Asunto',               'value' => $modelRecibido['asunto']],
                ['alias' => 'Dependencia',          'value' => $modelRecibido['codDependencia'].' - '.$modelRecibido['nomDependencia']],
                ['alias' => 'Funcionario',          'value' => $modelRecibido['usuario']],
                ['alias' => 'Proveedor o Tercero',  'value' => $modelRecibido['proveedor']],
                ['alias' => 'Destinatario',         'value' => $modelRecibido['destinatario']],
                ['alias' => 'Tipo Documental',      'value' => $modelRecibido['nomTipoDoc']],
                ['alias' => 'Número expediente',    'value' => $modelRecibido['numExpediente']],
                ['alias' => 'Nombre expediente',    'value' => $modelRecibido['nomExpediente']],
                ['alias' => 'Serie',                'value' => $modelRecibido['codSerie'].' - '.$modelRecibido['nomSerie']],
                ['alias' => 'Subserie',             'value' => $modelRecibido['codSubserie'].' - '.$modelRecibido['nomSubserie']],
                ['alias' => 'Observación',          'value' => $modelRecibido['observacion']],
                ['alias' => 'Referencia',           'value' => $modelRecibido['referencia']],
                ['alias' => 'Prioridad',            'value' => $modelRecibido['prioridad']],
            ];


            # DOCUMENTOS
            $modelDocumentos = (new \yii\db\Query())
                ->select('AD.IDANEXO idAnexo, EI.NOMARCHIVO nombreArchivo, EI.TIPDOCUMENTO tipoDocumento, USU.NMBRE_USRIO usuario, AD.DESCRIPCION descripcion')
                ->from('ADI.MERT_RECIBIDO RAD')
                ->innerJoin('ADI.MERT_ENRUTAMIENTO_IMAGEN EI', 'RAD.IDDOCUMENTO = EI.IDDOCUMENTO')
                ->innerJoin('ADI.MERT_RUTA_IMAGEN RI', 'EI.IDRUTAIMAGEN = RI.IDRUTAIMAGEN')
                ->innerJoin('ADI.MERT_ANEXODOCUMENTO AD', 'RAD.IDDOCUMENTO = AD.IDDOCUMENTO')
                ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'AD.IDUSUARIO_REGISTRO = USU.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
            ->all($connection);

            $dataDocumentos = [];

            foreach($modelDocumentos as $key => $documentos){

                $extArchivo = [];
                if($documentos['nombreArchivo']){
                    $extArchivo = explode(".",$documentos['nombreArchivo']);
                }
                
                $dataDocumentos[] = [
                    'id'                => $documentos['idAnexo'],
                    'nombre'            => $documentos['nombreArchivo'],
                    'isPdf'             => ($extArchivo[1] == 'pdf') ? true : false,
                    'rutaFile'          => $documentos['rutaImagen'],
                    'descripcion'       => $documentos['descripcion'],
                    'tipoDocumento'     => $documentos['tipoDocumento'],
                    'usuario'           => $documentos['usuario'],
                ];
            }            

            # TRAZABILIDAD DEL RADICADO
            /* Muestra el historico del radicado */
            $modelHistorico = (new \yii\db\Query())
                ->select('BIT.FECENTRADA fechaEntrada, ACTUAL.NMBRE_USRIO usuarioActual, DEP.NOMDEPENDENCIA nomDependencia, BIT.DESCOMENTARIO observacion, RI.DESRUTAIMAGEN rutaImagen')
                ->from('ADI.MERT_RECIBIDO RAD')
                ->innerJoin('ADI.MERT_BITACORA BIT', 'RAD.IDDOCUMENTO = BIT.IDDOCUMENTO')
                // ->innerJoin('ADI.MERT_FLUJO_RUTA FR', 'BIT.IDUBICACION = FR.IDUBICACION')
                ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
                ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
                ->innerJoin('ADI.SST_USRIOS_SSTMA ACTUAL', 'BIT.IDUBICACION = ACTUAL.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
                ->orderBy(['BIT.FECENTRADA' => SORT_DESC])
            ->all($connection);

            $dataHistorico = [];

            foreach($modelHistorico as $historico){
 
                $dataHistorico[] = [                    
                    'fecha'            => $historico['fechaEntrada'],
                    'usuario'     => $historico['usuarioActual'],
                    'dependencia'   => $historico['nomDependencia'],
                    'observacion'        => $historico['observacion'],
                ];
            }

            /*** Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'Número de radicado: '.$id.' de la consulta mert_recibido ADI', //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'dataDocumentos' => $dataDocumentos,
                'dataHistorico' => $dataHistorico,
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


    /** Displays a single Consultas ADI MERT_INTERNO model.
    * @param integer $id
    * @return mixed
    * @throws NotFoundHttpException if the model cannot be found
    */
    public function actionViewInterno($request)
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
            $dataList = [];

            # Conexión a la base de datos de oracle aeronautica
            $connection = Yii::$app->get('dbOracleA');

            # Model de mert interno
            $model = $this->mertInterno();
            $modelInterno = $model->where(['IN', 'RAD.IDDOCUMENTO', $id])->one($connection);
           
            # DETALLE RADICADO INTERNO
            $dataList = [
                ['alias' => 'Fecha radicado',       'value' => $modelInterno['fechaRadicado']],
                ['alias' => 'Número radicado',      'value' => $modelInterno['numeroRadicado']],
                ['alias' => 'Asunto',               'value' => $modelInterno['asunto']],
                ['alias' => 'Dependencia',          'value' => $modelInterno['codDependencia'].' - '.$modelInterno['nomDependencia']],
                ['alias' => 'Funcionario',          'value' => $modelInterno['usuario']],
                ['alias' => 'Proveedor o Tercero',  'value' => $modelInterno['proveedor']],
                ['alias' => 'Destinatario',         'value' => $modelInterno['destinatario']],
                ['alias' => 'Tipo Documental',      'value' => $modelInterno['nomTipoDoc']],
                ['alias' => 'Número expediente',    'value' => $modelInterno['numExpediente']],
                ['alias' => 'Nombre expediente',    'value' => $modelInterno['nomExpediente']],
                ['alias' => 'Serie',                'value' => $modelInterno['codSerie'].' - '.$modelInterno['nomSerie']],
                ['alias' => 'Subserie',             'value' => $modelInterno['codSubserie'].' - '.$modelInterno['nomSubserie']],
                ['alias' => 'Observación',          'value' => $modelInterno['observacion']],
                ['alias' => 'Referencia',           'value' => $modelInterno['referencia']],
                ['alias' => 'Prioridad',            'value' => $modelInterno['prioridad']],
            ];

            # DOCUMENTOS
            $modelDocumentos = (new \yii\db\Query())
                ->select('AD.IDANEXO idAnexo, EI.NOMARCHIVO nombreArchivo, EI.TIPDOCUMENTO tipoDocumento, USU.NMBRE_USRIO usuario, AD.DESCRIPCION descripcion, RI.DESRUTAIMAGEN rutaImagen')
                ->from('ADI.MERT_INTERNO RAD')
                ->innerJoin('ADI.MERT_ENRUTAMIENTO_IMAGEN EI', 'RAD.IDDOCUMENTO = EI.IDDOCUMENTO')
                ->innerJoin('ADI.MERT_RUTA_IMAGEN RI', 'EI.IDRUTAIMAGEN = RI.IDRUTAIMAGEN')
                ->innerJoin('ADI.MERT_ANEXODOCUMENTO AD', 'RAD.IDDOCUMENTO = AD.IDDOCUMENTO')
                ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'AD.IDUSUARIO_REGISTRO = USU.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
            ->all($connection);

            $dataDocumentos = [];

            foreach($modelDocumentos as $key => $documentos){
                $extArchivo = [];
                if($documentos['nombreArchivo']){
                    $extArchivo = explode(".",$documentos['nombreArchivo']);
                }

                $dataDocumentos[] = [
                    'id'                => $documentos['idAnexo'],
                    'nombre'            => $documentos['nombreArchivo'],
                    'descripcion'       => $documentos['descripcion'],
                    'isPdf'             => ($extArchivo[1] == 'pdf') ? true : false,
                    'rutaFile'          => $documentos['rutaImagen'],
                    'tipoDocumento'     => $documentos['tipoDocumento'],
                    'usuario'           => $documentos['usuario'],
                ];
            }         

            # TRAZABILIDAD DEL RADICADO
            /* Muestra el historico del radicado */
            $modelHistorico = (new \yii\db\Query())
                ->select('BIT.FECENTRADA fechaEntrada, ACTUAL.NMBRE_USRIO usuarioActual, DEP.NOMDEPENDENCIA nomDependencia, BIT.DESCOMENTARIO observacion')
                ->from('ADI.MERT_INTERNO RAD')
                ->innerJoin('ADI.MERT_BITACORA BIT', 'RAD.IDDOCUMENTO = BIT.IDDOCUMENTO')
                //->innerJoin('ADI.MERT_FLUJO_RUTA FR', 'BIT.IDUBICACION = FR.IDUBICACION')
                ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
                ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
                ->innerJoin('ADI.SST_USRIOS_SSTMA ACTUAL', 'BIT.IDUBICACION = ACTUAL.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
                ->orderBy(['BIT.FECENTRADA' => SORT_DESC])
            ->all($connection);

            $dataHistorico = [];

            foreach($modelHistorico as $historico){
 
                $dataHistorico[] = [                    
                    'fecha'             => $historico['fechaEntrada'],
                    'usuario'           => $historico['usuarioActual'],
                    'dependencia'       => $historico['nomDependencia'],
                    'observacion'       => $historico['observacion'],
                ];
            }

            /*** Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'Número de radicado: '.$id.' de la consulta mert_interno ADI', //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'dataDocumentos' => $dataDocumentos,
                'dataHistorico' => $dataHistorico,
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


    /** Displays a single Consultas ADI MERT_EXTERNO model.
    * @param integer $id
    * @return mixed
    * @throws NotFoundHttpException if the model cannot be found
    */
    public function actionViewExterno($request)
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
            $dataList = [];

            # Conexión a la base de datos de oracle aeronautica
            $connection = Yii::$app->get('dbOracleA');

            # Model de mert Externo
            $model = $this->mertExterno();
            $modelExterno = $model->where(['IN', 'RAD.IDDOCUMENTO', $id])->one($connection);
           
            # DETALLE RADICADO EXTERNO
            $dataList = [
                ['alias' => 'Fecha radicado',       'value' => $modelExterno['fechaRadicado']],
                ['alias' => 'Número radicado',      'value' => $modelExterno['numeroRadicado']],
                ['alias' => 'Asunto',               'value' => $modelExterno['asunto']],
                ['alias' => 'Dependencia',          'value' => $modelExterno['codDependencia'].' - '.$modelExterno['nomDependencia']],
                ['alias' => 'Funcionario',          'value' => $modelExterno['usuario']],
                ['alias' => 'Proveedor o Tercero',  'value' => $modelExterno['proveedor']],
                ['alias' => 'Destinatario',         'value' => $modelExterno['destinatario']],
                ['alias' => 'Tipo Documental',      'value' => $modelExterno['nomTipoDoc']],
                ['alias' => 'Número expediente',    'value' => $modelExterno['numExpediente']],
                ['alias' => 'Nombre expediente',    'value' => $modelExterno['nomExpediente']],
                ['alias' => 'Serie',                'value' => $modelExterno['codSerie'].' - '.$modelExterno['nomSerie']],
                ['alias' => 'Subserie',             'value' => $modelExterno['codSubserie'].' - '.$modelExterno['nomSubserie']],
                ['alias' => 'Observación',          'value' => $modelExterno['observacion']],
                ['alias' => 'Referencia',           'value' => $modelExterno['referencia']],
                ['alias' => 'Prioridad',            'value' => $modelExterno['prioridad']],
            ];


            # DOCUMENTOS
            $modelDocumentos = (new \yii\db\Query())
                ->select('AD.IDANEXO idAnexo, EI.NOMARCHIVO nombreArchivo, EI.TIPDOCUMENTO tipoDocumento, USU.NMBRE_USRIO usuario, AD.DESCRIPCION descripcion, RI.DESRUTAIMAGEN rutaImagen')
                ->from('ADI.MERT_EXTERNO RAD')
                ->innerJoin('ADI.MERT_ENRUTAMIENTO_IMAGEN EI', 'RAD.IDDOCUMENTO = EI.IDDOCUMENTO')
                ->innerJoin('ADI.MERT_RUTA_IMAGEN RI', 'EI.IDRUTAIMAGEN = RI.IDRUTAIMAGEN')
                ->innerJoin('ADI.MERT_ANEXODOCUMENTO AD', 'RAD.IDDOCUMENTO = AD.IDDOCUMENTO')
                ->innerJoin('ADI.SST_USRIOS_SSTMA USU', 'AD.IDUSUARIO_REGISTRO = USU.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
            ->all($connection);

            $dataDocumentos = [];

            foreach($modelDocumentos as $key => $documentos){
                $extArchivo = [];
                if($documentos['nombreArchivo']){
                    $extArchivo = explode(".",$documentos['nombreArchivo']);
                }

                $dataDocumentos[] = [
                    'id'                => $documentos['idAnexo'],
                    'nombre'            => $documentos['nombreArchivo'],
                    'descripcion'       => $documentos['descripcion'],
                    'isPdf'             => ($extArchivo[1] == 'pdf') ? true : false,
                    'rutaFile'          => $documentos['rutaImagen'],
                    'tipoDocumento'     => $documentos['tipoDocumento'],
                    'usuario'           => $documentos['usuario'],
                ];
            }         

            # TRAZABILIDAD DEL RADICADO
            /* Muestra el historico del radicado */
            $modelHistorico = (new \yii\db\Query())
                ->select('BIT.FECENTRADA fechaEntrada, ACTUAL.NMBRE_USRIO usuarioActual, DEP.NOMDEPENDENCIA nomDependencia, BIT.DESCOMENTARIO observacion')
                ->from('ADI.MERT_EXTERNO RAD')
                ->innerJoin('ADI.MERT_BITACORA BIT', 'RAD.IDDOCUMENTO = BIT.IDDOCUMENTO')
                //->innerJoin('ADI.MERT_FLUJO_RUTA FR', 'BIT.IDUBICACION = FR.IDUBICACION')
                ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
                ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
                ->innerJoin('ADI.SST_USRIOS_SSTMA ACTUAL', 'BIT.IDUBICACION = ACTUAL.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
                ->orderBy(['BIT.FECENTRADA' => SORT_DESC])
            ->all($connection);

            $dataHistorico = [];

            foreach($modelHistorico as $historico){
 
                $dataHistorico[] = [                    
                    'fecha'             => $historico['fechaEntrada'],
                    'usuario'           => $historico['usuarioActual'],
                    'dependencia'       => $historico['nomDependencia'],
                    'observacion'       => $historico['observacion'],
                ];
            }

            /*** Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'].'Número de radicado: '.$id.' de la consulta mert_externo ADI', //texto para almacenar en el evento
                [], //DataOld
                [], //Data
                array() //No validar estos campos
            );
            /***    Fin log Auditoria   ***/
            
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'dataDocumentos' => $dataDocumentos,
                'dataHistorico' => $dataHistorico,
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
    * Función que permite descargar documentos 
    */
    public function actionDownloadDocument() 
    {

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);
        
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                // Ruta de la carpeta compartida donde esta la bodega
                $rutaCompartido = Yii::$app->params['routeDocumentsAdi'];

                foreach ($request['ButtonSelectedData'] as $key => $item) {


                    $idDocumento = $item['id'];
                    // Nombre del archivo para generar
                    $fileName = $item['nombre'];
                    // $rutaFile = $item['rutaFile'].'/'.$fileName;

                    $rutaExp =  explode( '\\' , $item['rutaFile']);
                    $ruta = '';

                    foreach ($rutaExp as $key => $value) {
                        if( $key >= 3){
                            $ruta = $ruta.'/'.$value;
                        }
                    }
                    // Ruta final del archivo
                    $rutaFile = $rutaCompartido.$ruta.'/'.$fileName;

                    /* Enviar archivo en base 64 como respuesta de la petición **/
                    if(file_exists($rutaFile))
                    {
                        //Lee el archivo dentro de una cadena en base 64
                        $dataFile = base64_encode(file_get_contents($rutaFile));
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => [],
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
                }
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



}
