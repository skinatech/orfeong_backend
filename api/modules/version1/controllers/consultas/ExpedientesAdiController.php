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
class ExpedientesAdiController extends Controller
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
    protected function mertDataExpe(){

        $model = (new \yii\db\Query())
            ->select('EXP.IDEXPEDIENTE numeroExpediente, TIPEXP.NOMTIPOEXP nombreExpediente, EXP.FECINICIOEXP fechaInicioExp, EXP.DESNOMBRE desnombreExpediente,
            EXP.OBSEXPEDIENTE observacionExp, EXP.DESEXPEDIENTE desExpediente, EXP.DIFERENCIADOR diferenciador, ENT.IDENTIDAD numeroIdentidad, ENT.NOMENTIDAD nombreEntidad, SERIE.IDTESAURO codSerie, SERIE.NOMTESAURO nomSerie, SUBSERIE.IDSUBTESAURO codSubserie, SUBSERIE.NOMSUBTESAURO nomSubserie,')
            ->from('ADI.MERT_EXPEDIENTE EXP')
            ->innerJoin('ADI.MERT_TIPOEXP TIPEXP', 'EXP.IDTIPOEXP = TIPEXP.IDTIPOEXP')
            ->innerJoin('ADI.MERT_ENTIDAD ENT', 'EXP.IDENTIDAD = ENT.IDENTIDAD')
            ->leftJoin('ADI.MERT_TESAURO SERIE', 'EXP.IDTESAURO = SERIE.IDTESAURO')
            ->leftJoin('ADI.MERT_SUBTESAURO SUBSERIE', 'EXP.idsubserie = SUBSERIE.IDTESAURO');

        return $model;
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
            $model = $this->mertDataExpe();
            
            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {
                $value = strtoupper($value);

                switch ($field) {
                    case 'fechaInicial':
                        # Modificar el formato de fecha
                        $dateNew = date_create($value);
                        $date = date_format($dateNew,"d-M-y");

                        $model->andWhere(['>=', 'EXP.FECINICIOEXP', trim($date)]);
                    break;
                    case 'fechaFinal':
                        # Modificar el formato de fecha
                        $dateNew = date_create($value);
                        $date = date_format($dateNew,"d-M-y");
                        
                        $model->andWhere(['<=', 'EXP.FECINICIOEXP', trim($date)]);

                    break;
                    case 'IDEXPEDIENTE':
                        $model->andWhere([ 'IN', 'EXP.' . $field, $value]);
                    break;
                    case 'PROVEEDOR': 
                        $model->andWhere([ 'or', [ Yii::$app->params['like'],'ENT.NOMENTIDAD', $value ], [ Yii::$app->params['like'], 'ENT.IDENTIDAD', $value ] ]);
                    break;
                    case 'IDENTIDAD':
                        $model->andWhere([Yii::$app->params['like'], 'EXP.' . $field, $value]);
                    break;
                    case 'NOMTIPOEXP':
                        $model->andWhere([Yii::$app->params['like'], 'TIPEXP.' . $field, $value]);
                    break;
                    case 'IDTESAURO':
                    case 'NOMTESAURO':
                        $model->andWhere([Yii::$app->params['like'], 'SERIE.' . $field, $value]);
                    break;
                    case 'IDSUBTESAURO':
                    case 'NOMSUBTESAURO':
                        $model->andWhere([Yii::$app->params['like'], 'SUBSERIE.' . $field, $value]);
                    break;
                    default: //Numero radicado, observacion
                        $model->andWhere([ Yii::$app->params['like'], 'EXP.' . $field, $value]);
                    break; 
                }                
            }

            # Orden descendente para ver los últimos registros creados
            //$modelMertRecibido->orderBy(['RAD.ROWNUMID' => SORT_DESC]); 

            # Limite de la consulta
            $model->limit(Yii::$app->params['limitRecords']);
            $modelRelation = $model->all($connection);

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            # Mert_Recibido
            foreach($modelRelation as $dataRelation) {              
    
                $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($dataRelation['numeroExpediente'])),
                );

                # Listado de informacion
                $dataList[] = array(
                    'data'              => $dataBase64Params,
                    'id'                => $dataRelation['numeroExpediente'],
                    'numExpediente'     => $dataRelation['numeroExpediente'],
                    'nomExpediente'     => $dataRelation['nombreExpediente'],
                    'fechaExpediente'   => $dataRelation['fechaInicioExp'],
                    'desNomExp'         => $dataRelation['desnombreExpediente'],
                    'decripcion'        => $dataRelation['desExpediente'],
                    'diferenciador'     => $dataRelation['diferenciador'],
                    'numeroIdentidad'   => $dataRelation['numeroIdentidad'],
                    'nombreEntidad'     => $dataRelation['nombreEntidad'],
                    'serie'             => $dataRelation['codSerie']. ' - '.  $dataRelation['nomSerie'],
                    'subserie'          => $dataRelation['codSubserie']. ' - '.  $dataRelation['nomSubserie'],
                    'observacion'       => $dataRelation['observacionExp'],
                    'rowSelect'         => false,
                    'idInitialList'     => 0
                );                
            }

            // Validar que el formulario exista 
            $formType = HelperDynamicForms::setListadoBD('indexExpedientesAdi');
            
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
    public function actionView($request)
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
            $model = $this->mertDataExpe();
            $modelRecibido = $model->where(['IN', 'EXP.IDEXPEDIENTE', $id])->one($connection);

            # DETALLE RADICADO RECIBIDO
            $dataList = [
                ['alias' => 'Fecha proceso',         'value' => $modelRecibido['fechaInicioExp']],
                ['alias' => 'Número expediente',        'value' => $modelRecibido['numeroExpediente']],
                ['alias' => 'Nombre expediente',        'value' => $modelRecibido['nombreExpediente']],
                ['alias' => 'Descripción nombre expediente',          'value' => $modelRecibido['desnombreExpediente']],
                ['alias' => 'Descripción expediente',   'value' => $modelRecibido['desExpediente']],
                ['alias' => 'Diferenciador',            'value' => $modelRecibido['diferenciador']],
                ['alias' => 'Documento Identidad',      'value' => $modelRecibido['numeroIdentidad']],
                ['alias' => 'Proveedor o tercero',      'value' => $modelRecibido['nombreEntidad']],
                ['alias' => 'Serie',                    'value' => $modelRecibido['codSerie'].' - '.$modelRecibido['nomSerie']],
                ['alias' => 'Subserie',                 'value' => $modelRecibido['codSubserie'].' - '.$modelRecibido['nomSubserie']],
                ['alias' => 'Observación',              'value' => $modelRecibido['observacionExp']],
            ];


            # DOCUMENTOS
            $modelDocumentos = (new \yii\db\Query())
                ->select('DEX.NUMDOCUMENTO idDocumento, EI.NOMARCHIVO nombreArchivo, EI.TIPDOCUMENTO tipoDocumento, DEX.DESDOCUMENTO descripcion, RI.DESRUTAIMAGEN rutaImagen')
                ->from('ADI.MERT_DOCUMEXPEDIENTE DEX')
                ->innerJoin('ADI.MERT_ENRUTAMIENTO_IMAGEN EI', 'EI.IDDOCUMENTO = DEX.IDDOCUMENTO')
                ->innerJoin('ADI.MERT_RUTA_IMAGEN RI', 'EI.IDRUTAIMAGEN = RI.IDRUTAIMAGEN')
                ->where(['IN', 'DEX.IDEXPEDIENTE', $id])
            ->all($connection);

            $dataDocumentos = [];

            foreach($modelDocumentos as $key => $documentos){

                $extArchivo = [];
                if($documentos['nombreArchivo']){
                    $extArchivo = explode(".",$documentos['nombreArchivo']);
                }
                
                $dataDocumentos[] = [
                    'id'                => $documentos['idDocumento'],
                    'nombre'            => $documentos['nombreArchivo'],
                    'isPdf'             => ($extArchivo[1] == 'pdf') ? true : false,
                    'rutaFile'          => $documentos['rutaImagen'],
                    'descripcion'       => $documentos['descripcion'],
                    'tipoDocumento'     => $documentos['tipoDocumento'],
                    // 'usuario'           => $documentos['usuario'],
                ];
            }            

            # TRAZABILIDAD DEL RADICADO
            /* Muestra el historico del radicado */
            /*$modelHistorico = (new \yii\db\Query())
                ->select('BIT.FECENTRADA fechaEntrada, ACTUAL.NMBRE_USRIO usuarioActual, DEP.NOMDEPENDENCIA nomDependencia, BIT.DESCOMENTARIO observacion, RI.DESRUTAIMAGEN rutaImagen')
                ->from('ADI.MERT_RECIBIDO RAD')
                ->innerJoin('ADI.MERT_BITACORA BIT', 'RAD.IDDOCUMENTO = BIT.IDDOCUMENTO')
                // ->innerJoin('ADI.MERT_FLUJO_RUTA FR', 'BIT.IDUBICACION = FR.IDUBICACION')
                ->innerJoin('ADI.MERT_CONFIGUSUARIO CONFUSU', 'RAD.IDUSUARIO_RAD = CONFUSU.IDUSUARIO')
                ->innerJoin('ADI.MERT_DEPENDENCIA DEP', 'CONFUSU.IDDEPENDENCIA = DEP.IDDEPENDENCIA')
                ->innerJoin('ADI.SST_USRIOS_SSTMA ACTUAL', 'BIT.IDUBICACION = ACTUAL.ID_USRIO')
                ->where(['IN', 'RAD.IDDOCUMENTO', $id])
                ->orderBy(['BIT.FECENTRADA' => SORT_DESC])
            ->all($connection);*/

            $dataHistorico = [];

            /*foreach($modelHistorico as $historico){
 
                $dataHistorico[] = [                    
                    'fecha'            => $historico['fechaEntrada'],
                    'usuario'     => $historico['usuarioActual'],
                    'dependencia'   => $historico['nomDependencia'],
                    'observacion'        => $historico['observacion'],
                ];
            }*/

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
