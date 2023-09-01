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
use yii\data\ActiveDataProvider;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\web\NotFoundHttpException;

use yii\helpers\FileHelper;

use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperConsecutivo;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperRadicacion;
use api\components\HelperUserMenu;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperExtraerTexto;
use api\models\CgTransaccionesRadicados;
use api\components\HelperLoads;
use api\components\HelperPlantillas;
use api\components\HelperQueryDb;
use api\models\RadiRadicados;
use api\models\Clientes;
use api\models\GaArchivo;
use api\models\GaBodega;
use api\models\GaBodegaContenido;
use api\models\GaEdificio;
use api\models\GaPiso;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdHistoricoExpedientes;
use api\models\GdIndices;
use api\models\GdTrdDependencias;
use api\models\RadiLogRadicados;
use api\models\RadiRadicadoAnulado;
use api\models\RadiRemitentes;
use api\models\User;
use api\models\UserDetalles;

use api\modules\version1\controllers\radicacion\RadicadosController;
use api\modules\version1\controllers\pdf\PdfController;
use api\modules\version1\controllers\correo\CorreoController;
use kartik\mpdf\Pdf;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpWord\TemplateProcessor;
use Picqer\Barcode\BarcodeGeneratorPNG;

/**
 * RadiAgendaController implements the CRUD actions for RadiAgendaRadicados model.
 */
class GestionArchivoController extends Controller
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
                    'get-general-list' => ['GET']
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
     * Lists all RadiRadicados models.
     * @return mixed
     */
    public function actionIndex($request)
    {
        /** Validar permisos del módulo */
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
        /** Validar permisos del módulo */

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

        //Lista de usuarios
        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        $dataSender = '';
        if (is_array($request)) {
            foreach ($request['filterOperation'] as $field) {
                foreach ($field as $key => $info) {

                    if ($key == 'inputFilterLimit') {
                        $limitRecords = $info;
                        continue;
                    }

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
          # Validación del nombre y documento de los remitentes
        $idUser = [];
        if (!empty($dataSender)){

            $modelCliente = Clientes::find()
                ->where([Yii::$app->params['like'],'nombreCliente', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'numeroDocumentoCliente', $dataSender])
            ->all();
        
            foreach($modelCliente as $infoCliente){
                $idUser[] = $infoCliente->idCliente; 
            }


            $modelUser = UserDetalles::find()
                ->where([Yii::$app->params['like'],'nombreUserDetalles', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'apellidoUserDetalles', $dataSender])
                ->orWhere([Yii::$app->params['like'], 'documento', $dataSender])
            ->all();

            foreach($modelUser as $infoUser){
                $idUser[] = $infoUser->idUser;
            }
        }

        # Los filtros deben tener los siguientes campos: 
        
        // Rango de Fechas (asociado a cuando se incluyo en el expediente), 
        // Número de Radicado,  
        // Nombre o Número de Expediente (Campo texto), 
        // Tipo documental (Listado), 
        // Dependencia (Todas las dependencias que se encuentren activas. Listado), 
        // Usuario (campo de texto, buscar por nombre o documento), 
        // Estado (Archivado o Por Archivar. Listado). 

        # Nivel de busqueda del logueado
        $idNivelBusqueda = Yii::$app->user->identity->rol->idRolNivelBusqueda;

        # User_tramitador dsalida 
        $dSalida  = User::findOne(['username' => Yii::$app->params['userNameDeSalida']]);

        # Relacionamiento de los radicados
        $relacionRadicados = GdExpedientes::find();
            # relacion expediente: expediente / inclusion
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpediente', 'gdExpedientes' => 'idGdExpediente']);
            # relacion expediente: inclusion / radicado
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'gdExpedientesInclusion' => 'idRadiRadicado']);
            # relacion expediente: inclusion / archivo
            $relacionRadicados = HelperQueryDb::getQuery('leftJoin', $relacionRadicados, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);

        $relacionRadicados = $relacionRadicados->where(['or',
            ['gdExpedientes.estadoGdExpediente' => Yii::$app->params['statusExpedienteText']['Cerrado']],
            ['radiRadicados.user_idTramitador' => $dSalida->id]
        ]);

        # Se visualiza todos los radicados del logueado, cuando no haya ningun filtro.
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Basico'] ) {
            $relacionRadicados->andWhere(['radiRadicados.user_idTramitador' => Yii::$app->user->identity->id]);
        }    

        # Cuando es nivel intermedio se busca todos los radicados asociados a la dependencia del usuario logueado
        if($idNivelBusqueda == Yii::$app->params['searchLevelText']['Intermedio'] ) {
            $relacionRadicados->andWhere(['radiRadicados.idTrdDepeUserTramitador' => Yii::$app->user->identity->idGdTrdDependencia]);
        } 

        //Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {
            switch ($field) {
                case 'idRadiPersona': //remitente
                    $relacionRadicados->andWhere(['IN', 'radiRemitentes.' . $field, $idUser]);
                break;
                case 'user_idTramitador': 

                    // $relacionRadicados->leftJoin('userDetalles', '`userDetalles`.`idUser` = `radiRadicados`.`user_idTramitador`')
                    $relacionRadicados = HelperQueryDb::getQuery('leftJoin', $relacionRadicados, 'userDetalles', ['userDetalles' => 'idUser', 'radiRadicados' => 'user_idTramitador']);
                    $relacionRadicados = $relacionRadicados->andWhere([ 'or', [ Yii::$app->params['like'],'userDetalles.nombreUserDetalles', $value ], [ Yii::$app->params['like'], 'userDetalles.apellidoUserDetalles', $value ],[ Yii::$app->params['like'], 'userDetalles.documento', $value ] ] );

                break;
                case 'numeroGdExpediente': 
                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'gdExpedientes.numeroGdExpediente', $value]);
                break;
                case 'nombreGdExpediente': 
                    $relacionRadicados->andWhere([ Yii::$app->params['like'], 'gdExpedientes.nombreGdExpediente', $value]);
                break;
                case 'idGdTrdTipoDocumental':
                    $relacionRadicados->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                break;

                case 'idGdTrdSerie':
                case 'idGdTrdSubserie':
                    $relacionRadicados->andWhere(['IN', 'gdExpedientes.' . $field, $value]);
                break;

                case 'idGaEdificio':
                case 'idGaPiso':
                case 'idGaBodega':
                case 'unidadConservacionGaArchivo':
                    $relacionRadicados->andWhere(['IN', 'gaArchivo.' . $field, $value]);
                break;

                case 'estanteGaArchivo':
                case 'rackGaArchivo':
                case 'entrepanoGaArchivo':
                case 'cajaGaArchivo':
                case 'cuerpoGaArchivo':
                case 'unidadCampoGaArchivo':
                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'gaArchivo.' . $field, $value]);
                break;

                case 'fechaInicial':
                    $relacionRadicados->andWhere(['>=', 'gdExpedientesInclusion.creacionGdExpedienteInclusion', trim($value) . Yii::$app->params['timeStart']]);
                break;
                case 'fechaFinal':
                    $relacionRadicados->andWhere(['<=', 'gdExpedientesInclusion.creacionGdExpedienteInclusion', trim($value) . Yii::$app->params['timeEnd']]);
                break;

                case 'idCgMedioRecepcion':
                case 'idCgTipoRadicado':
                case 'idTrdDepeUserTramitador':
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'estadoRadiRadicado':
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . $field, intval($value)]);
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

        if(!isset($dSalida)){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'errorUserDsalida')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        # Limite de la consulta
        $relacionRadicados->limit($limitRecords);
        $modelRelation = $relacionRadicados->all();


        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        foreach ($modelRelation as $infoRelation) {       

            # Se obtiene la traza del estado actual del expediente
            $GdHistoricoExpedientes = GdHistoricoExpedientes::find()->where(['idGdExpediente' => $infoRelation->idGdExpediente, 'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['ArchivarExpediente']])->one();

            if(isset($GdHistoricoExpedientes)){
                # estado archivado
                $statusText = Yii::t('app', 'statusTodoText')['Archivado'];
                $espacioFisicoText = Yii::t('app','allocatedSpace');
                $espacioFisicoStatus = true;
            }else{
                # estado pendiente por archivar
                $statusText = Yii::t('app', 'statusTodoText')['Pendiente por archivar'];
                $espacioFisicoText = Yii::t('app','unallocatedSpace');
                $espacioFisicoStatus = false;
            }
        
            $dataBase64Params = array(
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idGdExpediente)),
                str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->numeroGdExpediente)),
            );

            /***
             * El index debe mostrar todos los radicados que están en estado "Finalizado" y que le pertenezcan al usuario dsalida. 
             * 
             * Se debe mostrar las siguientes columnas: 
             *  
             *  Numero Expediente,
             *  Nombre Expediente,
             *  Descripcion del Expediente
             *  Serie-SubSerie,
             *  Fecha creacion del Expediente, 
             *  Tiempo en Gestion,
             *  Tiempo en Central
             *  Espacion fisico,
             *  Estado. 
             * 
             */

             $serie = $infoRelation->gdTrdSerie; 
             $sub_serie = $infoRelation->gdTrdSubserie;
            
            $dataList[] = array(
                'data'                  => $dataBase64Params,
                'id'                    => $infoRelation->idGdExpediente,
                'numeroExpediente'      => $infoRelation->numeroGdExpediente,
                'nombreExpediente'      => $infoRelation->nombreGdExpediente,
                'descripcionExpediente' => $infoRelation->descripcionGdExpediente,
                #
                'codigoSerie'           => $serie->codigoGdTrdSerie.'-'.$serie->nombreGdTrdSerie,
                'codigoSubSerie'        => $sub_serie->codigoGdTrdSubserie.'-'.$sub_serie->nombreGdTrdSubserie, 
                'creacionExpediente'    => $infoRelation->creacionGdExpediente,
                'tiempoGestion'         => $infoRelation->tiempoGestionGdExpedientes,
                'tiempoCentral'         => $infoRelation->tiempoCentralGdExpedientes,
                #
                'espacioFisicoStatus'   => $espacioFisicoStatus,
                'espacioFisicoText'     => $espacioFisicoText,
                'statusText'            => $statusText ?? "",       // Yii::t('app', $transaccion) . ' - ' .         
                'status'                => $infoRelation->estadoGdExpediente,
                'rowSelect'             => false,
                'idInitialList'         => 0,
            );

        }
      
        // Validar que el formulario exista
        $formType = HelperDynamicForms::setListadoBD('indexArchivoRadicado');

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
     *  Carga lista de edificios, pisos, bodegas, contenido de las bodegas
     * 
     * @param integer  $idDepartamentoGaEdificio
     * @param integer  $idMunicipioGaEdificio
     * @param integer  $idGaEdificio
     * @param integer  $idGaPiso
     * @param integer  $idGaBodega
     * 
     * @return array data
     */
    public function actionGetGeneralList($request)
    {

        if (!empty($request)) {

            //*** Inicio desencriptación GET ***//
            if (!empty($request)) {
                $decrypted = HelperEncryptAes::decryptGET($request, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];
                } else {
                    $request = '';
                }
            }
            //*** Fin desencriptación GET ***//

            $dataGaEdificio = [];
            $dataGaPiso = [];
            $dataGaBodega = [];
            $dataEstanteGaBodega = [];
            $dataRackGaBodega = [];
            $dataEntrepanoGaBodega = [];
            $dataCajaGaBodega = [];
            $dataCuerpoGaBodega = '';
            $idExpediente = '';

            if(isset($request['idExpediente'])){
                $idExpediente = $request['idExpediente'];

                $modelExpInclusion = GdExpedientesInclusion::findAll(['idGdExpediente' => $idExpediente]);
                foreach($modelExpInclusion as $dataInclusion){
                    if(isset($dataInclusion->gaArchivo))
                        $modelArchive = $dataInclusion->gaArchivo;
                }
            }
            
            # Cuando el expediente o radicado ya tiene asignado un espacio fisico
            $idDepartment = 0; 
            $idMunicipality = 0;
            $idBuilding = 0; $idFloor = 0; $idWarehouse = 0;
            $estante = 0; $rack = 0;  $shelf = 0; $box = 0; $body = '';
            $unidadConservacion = ''; $unidadCampo = '';

            if(isset($modelArchive)) {

                # Departamento
                $idDepartment = $modelArchive->gaEdificio->idDepartamentoGaEdificio;

                # Municipio
                $idMunicipality = $modelArchive->gaEdificio->idMunicipioGaEdificio;

                # Edificio
                $idBuilding = $modelArchive->gaEdificio->idGaEdificio;

                # Piso
                $idFloor = $modelArchive->idGaPiso;

                # Bodega
                $idWarehouse = $modelArchive->idGaBodega;

                # Contenido de la bodega
                $estante = $modelArchive->estanteGaArchivo;
                $rack =  $modelArchive->rackGaArchivo;            
                $shelf = $modelArchive->entrepanoGaArchivo;
                $box = $modelArchive->cajaGaArchivo;
                $body = $modelArchive->cuerpoGaArchivo;

                # Unidad de conservación
                $unidadConservacion = $modelArchive->unidadConservacionGaArchivo;

                # Unidad de campo
                $unidadCampo = $modelArchive->unidadCampoGaArchivo;

            } 
            /**** PROCESO DE ASIGNACIÓN ESPACIO YA CREADO O NO CREADO EN EL EXPEDIENTE ****/

            # listado de Edificios
            $GaEdificio = GaEdificio::find()
                ->where(['idDepartamentoGaEdificio' =>  ($request['idDepartamentoGaEdificio'] != '' ? $request['idDepartamentoGaEdificio'] : $idDepartment) ])
                ->andWhere(['idMunicipioGaEdificio' =>  ($request['idMunicipioGaEdificio'] != '' ? $request['idMunicipioGaEdificio'] : $idMunicipality) ])
                ->andWhere(['estadoGaEdificio' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

            foreach ($GaEdificio as $row) {
                $dataGaEdificio[] = array(
                    "id" => (int) $row['idGaEdificio'],
                    "val" => $row['nombreGaEdificio'],
                );
            }

            # listado de Pisos
            $GaPiso = GaPiso::find();
                // ->innerJoin('gaEdificio', '`gaEdificio`.`idGaEdificio` = `gaPiso`.`idGaEdificio`')
                $GaPiso = HelperQueryDb::getQuery('innerJoin', $GaPiso, 'gaEdificio', ['gaEdificio' => 'idGaEdificio', 'gaPiso' => 'idGaEdificio']);
                $GaPiso = $GaPiso->where(['gaEdificio.idGaEdificio' => ($request['idGaEdificio'] != '' ? $request['idGaEdificio'] : $idBuilding) ])
                ->andWhere(['estadoGaPiso' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

            foreach ($GaPiso as $row) {
                $dataGaPiso[] = array(
                    "id" => (int) $row['idGaPiso'],
                    "val" => $row['numeroGaPiso'],
                );
            }

            # listado de Bodega
            $GaBodega = GaBodega::find();
                // ->innerJoin('gaPiso', '`gaPiso`.`idGaPiso` = `gaBodega`.`idGaPiso`')
                $GaBodega = HelperQueryDb::getQuery('innerJoin', $GaBodega, 'gaPiso', ['gaPiso' => 'idGaPiso', 'gaBodega' => 'idGaPiso']);
                $GaBodega = $GaBodega->where(['gaPiso.idGaPiso' => ($request['idGaPiso'] != '' ? $request['idGaPiso'] : $idFloor) ])
                ->andWhere(['estadoGaBodega' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

            foreach ($GaBodega as $row) {
                $dataGaBodega[] = array(
                    "id" => (int) $row['idGaBodega'],
                    "val" => $row['nombreGaBodega'],
                );
            }

            # listado de Bodega Contenido
            $GaBodegaContenido = GaBodegaContenido::find();
                // ->innerJoin('gaBodega', '`gaBodega`.`idGaBodega` = `gaBodegaContenido`.`idGaBodega`')
                $GaBodegaContenido = HelperQueryDb::getQuery('innerJoin', $GaBodegaContenido, 'gaBodega', ['gaBodega' => 'idGaBodega', 'gaBodegaContenido' => 'idGaBodega']);
                $GaBodegaContenido = $GaBodegaContenido->where(['gaBodega.idGaBodega' => ($request['idGaBodega'] != '' ? $request['idGaBodega'] : $idWarehouse) ])
                ->andWhere(['estadoGaBodegaContenido' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

            foreach ($GaBodegaContenido as $row) {

                for ($index=0; $index < $row['cantidadEstanteGaBodegaContenido'] ; $index++) {  
                    $dataEstanteGaBodega[] = array(
                        "id" => ($index+1),
                        "val" =>  Yii::t('app', 'estanteNum',['num' => ($index+1)]),
                    );
                }

                for ($index=0; $index < $row['cantidadRackGaBodegaContenido'] ; $index++) {  
                    $dataRackGaBodega[] = array(
                        "id" => ($index+1),
                        "val" =>  Yii::t('app', 'rack',['num' => ($index+1)]),
                    );
                }

                for ($index=0; $index < $row['cantidadEntrepanoGaBodegaContenido'] ; $index++) {  
                    $dataEntrepanoGaBodega[] = array(
                        "id" => ($index+1),
                        "val" =>  Yii::t('app', 'entrepano',['num' => ($index+1)]),
                    );
                }

                for ($index=0; $index < $row['cantidadCajaGaBodegaContenido'] ; $index++) {  
                    $dataCajaGaBodega[] = array(
                        "id" => ($index+1),
                        "val" =>  Yii::t('app', 'caja',['num' => ($index+1)]),
                    );
                }

                $dataCuerpoGaBodega = $row['cuerpoGaBodegaContenido'];
            }
            
                

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'dataGaEdificio' => $dataGaEdificio ?? [],
                'dataGaPiso'     => $dataGaPiso ?? [],
                'dataGaBodega'   => $dataGaBodega ?? [],
                'dataEstanteGaBodega' => $dataEstanteGaBodega ?? [],
                'dataRackGaBodega' => $dataRackGaBodega ?? [],
                'dataEntrepanoGaBodega' => $dataEntrepanoGaBodega ?? [],
                'dataCajaGaBodega' => $dataCajaGaBodega ?? [],
                'dataCuerpoGaBodega' => $dataCuerpoGaBodega ?? $body,
                // 'dataUConservacionGaArchivo' => Yii::$app->params['unidadConservacionGaArchivo'],
                'dataUConservacionGaArchivo' => Yii::t('app', 'unidadConservacionGaArchivoNumber'), 
                'status' => 200,
                'dataArchivo' => [
                    'idDepartamentoGaEdificio' => $idDepartment,
                    'idMunicipioGaEdificio' => $idMunicipality,
                    'idGaEdificio' => $idBuilding,
                    'idGaPiso' => $idFloor,
                    'idGaBodega' => $idWarehouse,
                    'estanteGaArchivo' => $estante,
                    'rackGaArchivo' => $rack,
                    'entrepanoGaArchivo' => $shelf,
                    'cajaGaArchivo' => $box,
                    'cuerpoGaArchivo' => $body,
                    'unidadConservacionGaArchivo' => $unidadConservacion,
                    'unidadCampoGaArchivo' => $unidadCampo,
                ],  
            ];

            return HelperEncryptAes::encrypt($response, true);

        } else {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * action datos ubicacion del archivo
     * @param integer $idRadiRadicado
     * @return mixed
     */
    public function actionView($request){

        if (!empty($request)) {

            //*** Inicio desencriptación GET ***//
            if (!empty($request)) {
                $decrypted = HelperEncryptAes::decryptGET($request, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];
                } else {
                    $request = '';
                }
            }
            //*** Fin desencriptación GET ***//

            if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsViewLocationArchived'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {


                # Expediente 
                $model = GdExpedientes::find()->where(['idGdExpediente' => $request['id']])->one();

                # RADICADOS ASOCIADOS AL EXPEDIENTE  
                $GdExpedientesInclusion = GdExpedientesInclusion::find()->where(['idGdExpediente' => $request['id']])->all();

                /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
                $modelRelation = array_reverse($GdExpedientesInclusion);

                foreach ($modelRelation as $infoRelation) {  

                    /***
                     *  El view debe mostrar todos los radicados que pertenecen al expediente archivado Se debe mostrar las siguientes columnas: 
                     *  
                     *  Numero Radicado,
                     *  Asunto,
                     *  Fecha creacion,
                     *  Tipo documental
                     *  Estado. 
                     * 
                     */

                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->radiRadicado->idRadiRadicado)),
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->radiRadicado->numeroRadiRadicado)),
                    );

                    # formato fechas //Solo necesita el formato
                    $creacionRadi = HelperRadicacion::getFormatosFecha($infoRelation->radiRadicado->creacionRadiRadicado);
                    $HelperConsecutivo = new HelperConsecutivo();

                    $dataList[] = array(
                        'data'                  => $dataBase64Params,
                        'id'                    => $infoRelation->idRadiRadicado,
                        'numeroRadicado'        => $HelperConsecutivo->numeroRadicadoXTipoRadicado($infoRelation->radiRadicado->numeroRadiRadicado, $infoRelation->radiRadicado->idCgTipoRadicado,$infoRelation->radiRadicado->isRadicado),
                        'asunto'                => $infoRelation->radiRadicado->asuntoRadiRadicado,
                        'creacion'              => $creacionRadi['formatoFrontend'],
                        'tipoDocumental'        => $infoRelation->radiRadicado->trdTipoDocumental->nombreTipoDocumental,
                        'statusText'            => Yii::$app->params['statusTodoNumber'][$infoRelation->radiRadicado->estadoRadiRadicado] ?? "",     
                        'status'                => $infoRelation->radiRadicado->estadoRadiRadicado,
                        'rowSelect'             => false,
                        'idInitialList'         => 0,
                    );
                }

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['viewArchived'].' Id: '.$model->idGdExpediente.' del expediente: '.$model->numeroGdExpediente, //texto para almacenar en el evento
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $dataList ?? [],
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
              
            } else{
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'accessDenied')[1],
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorAccessDenied'],
                ];
                return HelperEncryptAes::encrypt($response, true); 
            }

        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Datos ubicacion del archivo
     * @param integer $idGdExpediente
     * @return mixed
     */
    public static function detallesArchivo($request){

        # Consulta en RadiRadicados
        $datarch = []; $creacionGaArchivo = Yii::t('app', 'sinEspecificarUbicacion'); $unCoGaArchivo = Yii::t('app', 'sinEspecificarUbicacion');

        # Expediente 
        $model = GdExpedientes::find()->where(['idGdExpediente' => $request['id']])->one();
    
        # Datos de archivo
        if(isset($model->gaArchivo)){
            $creacionGaArchivo = HelperRadicacion::getFormatosFecha($model->gaArchivo['creacionGaArchivo'])['formatoFrontend'];
            $unCoGaArchivo = Yii::$app->params['unidadConservacionGaArchivoNumber'][$model->gaArchivo->unidadConservacionGaArchivo];
        }

        # Se obtiene la traza del estado actual del expediente
        $gdHistoricoExpedientes = GdHistoricoExpedientes::find()->where([
            'idGdExpediente' => $model->idGdExpediente, 
            'operacionGdHistoricoExpediente' => Yii::$app->params['operacionExpedienteText']['ArchivarExpediente']]
        )->one();

        if(isset($gdHistoricoExpedientes)){
            $usuario = $gdHistoricoExpedientes->userHistoricoExpediente->userDetalles->nombreUserDetalles.' '.$gdHistoricoExpedientes->userHistoricoExpediente->userDetalles->apellidoUserDetalles;
        }
    
        # Detalles archivp
        $datarch[] = array('alias' => 'Edificio',                 'value' => $model->gaArchivo->gaEdificio->nombreGaEdificio ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Piso',                     'value' => $model->gaArchivo->gaPiso->numeroGaPiso ?? Yii::t('app', 'sinEspecificarUbicacion')) ;
        $datarch[] = array('alias' => 'Área de archivo',          'value' => $model->gaArchivo->gaBodega->nombreGaBodega ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Estante',                  'value' => $model->gaArchivo->estanteGaArchivo ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Módulo',                   'value' => $model->gaArchivo->rackGaArchivo ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Entrepaño',                 'value' => $model->gaArchivo->entrepanoGaArchivo ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Caja',                     'value' => $model->gaArchivo->cajaGaArchivo  ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Cuerpo',                   'value' => $model->gaArchivo->cuerpoGaArchivo ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Unidad de conservación',   'value' => $unCoGaArchivo);
        $datarch[] = array('alias' => 'Número de conservación',   'value' => $model->gaArchivo->unidadCampoGaArchivo ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Fecha archivo',            'value' => $creacionGaArchivo);
        $datarch[] = array('alias' => 'Usuario',                  'value' => $usuario ?? Yii::t('app', 'sinEspecificarUbicacion'));
        $datarch[] = array('alias' => 'Número de inventario',     'value' => $model->gaArchivo->consecutivoGaArchivo ?? "--");
        
        return $datarch;

    }


    /** 
     * Funcion que permite descargar el anexo que o el documento del radicado
     **/
    public function actionDownloadRotulos() 
    {

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);        
        
        if (HelperValidatePermits::validateUserPermits( Yii::$app->params['permissionsRadiCorresDownload'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $jsonSend = Yii::$app->request->getBodyParam('jsonSend');

            if (!empty($jsonSend)) {

                #Inicio desencriptación POST 
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
                # Fin desencriptación POST 

                # Array de Expedientes
                $idGdExpedientes = []; $data = []; $contenido = [];

                # $transaction = Yii::$app->db->beginTransaction();

                # Datos generales
                $data['entidad'] = Yii::$app->params['entidad'];
               
                foreach($request['ButtonSelectedData'] as $key => $expediente){
                    # agrupar expedientes
                    $idGdExpedientes[] = $expediente['id'];
                }
        
                switch($request['data']['typeRotulo']){
                    # Rotulos de Carpetas -> unico expediente
                    case 'Carpeta':
                        
                        $mesaje = Yii::t('app', 'successDownlaodRotuloCarpeta');
                        $nameFile = Yii::$app->params['rotulosGestionArchivo']['routeCarpeta'];
                        $template = Yii::$app->params['rotulosGestionArchivo']['viewPdfCarpeta'];

                        $data['rotulo_de_carpeta'] = "Rotulo de Carpeta";
                       
                        # obtener datos de expediente
                        $expedientes = GdExpedientes::find()->where(['IN', 'idGdExpediente', $idGdExpedientes])->one();

                        /**
                         * Inicio bar code
                         */
                        $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloadsBarCodeConsecutivo'] . '/barcode_'. $expedientes->gaArchivo->consecutivoGaArchivo .'.png';
                        $barcode = new BarcodeGeneratorPNG();
                        $barcode = $barcode->getBarcode($expedientes->gaArchivo->consecutivoGaArchivo, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                        file_put_contents($pathBarcode, $barcode);
                        /**
                         * Fin bar code
                         */
                        
                        $data['pathBarcode'] = Yii::$app->params['routeDownloadsBarCodeConsecutivo'] . '/barcode_'. $expedientes->gaArchivo->consecutivoGaArchivo .'.png';

                        # datos caracteristicos carpeta
                        $data['unidad_de_conservacion'] = Yii::$app->params['unidadConservacionGaArchivoNumber'][$expedientes->gaArchivo->unidadConservacionGaArchivo];
                        $data['valor_unidad'] = $expedientes->gaArchivo->unidadCampoGaArchivo;
        
                        # consultar relacion expediente/radicados
                        $numeroFolios = GdExpedientesInclusion::find()->where(['idGdExpediente' => $expedientes->idGdExpediente])->count();
                        $data['total_docs'] = $numeroFolios;

                        # codigo y dependencia
                        $data['codigo'] = $expedientes->gdTrdDependencia->codigoGdTrdDependencia;
                        $data['dependencia'] = $expedientes->gdTrdDependencia->nombreGdTrdDependencia;

                        # codigos/nombre serie y subserie
                        $data['codigo_serie_subserie'] = $expedientes->gdTrdSerie->codigoGdTrdSerie."/".$expedientes->gdTrdSubserie->codigoGdTrdSubserie;
                        $data['nombre_serie_subserie'] = $expedientes->gdTrdSerie->nombreGdTrdSerie."/".$expedientes->gdTrdSubserie->nombreGdTrdSubserie;

                        # expediente
                        $data['nombre_expediente'] = $expedientes->nombreGdExpediente; #." ".$expedientes->numeroGdExpediente;

                        # contenido (tabla)
                        $gdExpedientesInclusion =  GdExpedientesInclusion::find()->where(['idGdExpediente' => $expedientes->idGdExpediente])->all(); 

                        foreach($gdExpedientesInclusion as $key => $value){

                            $gdIndices =  GdIndices::find()->where(['idGdExpedienteInclusion' => $value['idGdExpedienteInclusion']])->all();

                            foreach($gdIndices as $documento){
                                $contenido[] =[
                                    'contenido' => $documento->indiceContenidoGdIndice,
                                    'tdocumental' =>  $documento->gdTrdTipoDocumental->nombreTipoDocumental
                                ];
                            }
                        }
                        $data['contenido'] = $contenido;
                    
                    break; 

                    # Rotulos de caja -> multiples expedientes 
                    case 'Caja':

                        # obtener datos de expediente
                        $expedientes = GdExpedientes::find()->where(['IN', 'idGdExpediente', $idGdExpedientes])->one();
                        $mesaje = Yii::t('app', 'successDownlaodRotuloCaja');
                        $nameFile = Yii::$app->params['rotulosGestionArchivo']['routeCaja'];
                        $template = Yii::$app->params['rotulosGestionArchivo']['viewPdfCaja'];

                        $data['rotulo_de_caja'] = "Rotulos de Caja";

                        # datos caracteristicos caja
                        $data['n_caja'] = $expedientes->gaArchivo->cajaGaArchivo;
                        $data['total_expedientes'] = count($request['ButtonSelectedData']);

                        # consultar relacion expediente/radicados
                        $numeroFolios = GdExpedientesInclusion::find()->where(['idGdExpediente' => $expedientes->idGdExpediente])->count();
                        $data['total_uni'] = $numeroFolios;

                        # codigo y dependencia
                        $data['codigo'] = $expedientes->gdTrdDependencia->codigoGdTrdDependencia;
                        $data['dependencia'] = $expedientes->gdTrdDependencia->nombreGdTrdDependencia;
                        
                        # contenido 
                        $gdExpedientes = GdExpedientes::find()->where(['IN', 'idGdExpediente', $idGdExpedientes])->all();
                        foreach($gdExpedientes as $key => $expediente){
                            $contenido[] =[
                                'codigo' => $expediente->gdTrdSerie->codigoGdTrdSerie."/".$expediente->gdTrdSubserie->codigoGdTrdSubserie,
                                'nombre' => $expediente->gdTrdSerie->nombreGdTrdSerie."/".$expediente->gdTrdSubserie->nombreGdTrdSubserie
                            ];
                        
                        }
                        $data['contenido'] = $contenido;
                    break;
                }
            
                # Datos generales 2 Carpeta/Caja

                # Obtener la fecha menor de inclusión
                $modelExpedienteInclusion = GdExpedientesInclusion::find()->select(['creacionGdExpedienteInclusion'])->where(['idGdExpediente' => $expedientes->idGdExpediente])->orderBy(['creacionGdExpedienteInclusion' => SORT_ASC])->one();
                $data['fecha_primer_documento'] = date("Y-m-d", strtotime($modelExpedienteInclusion->creacionGdExpedienteInclusion)); 

                # Obtener la fecha mayor de inclusión
                $modelExpedienteInclusion = GdExpedientesInclusion::find()->select(['creacionGdExpedienteInclusion'])->where(['idGdExpediente' => $expedientes->idGdExpediente])->orderBy(['creacionGdExpedienteInclusion' => SORT_DESC])->one();
                $data['fecha_ultimo_documento'] = date("Y-m-d", strtotime($modelExpedienteInclusion->creacionGdExpedienteInclusion)); 

                # Validar creación de la carpeta                           
                $gestion_archivo = Yii::getAlias('@webroot')."/"."gestion_archivo"."/"."generados"; 

                # print_r($data);die();

                # Verificar ruta de la carpeta y crearla en caso de que no exista
                if (!file_exists($gestion_archivo)) {
                    if (!FileHelper::createDirectory($gestion_archivo, $mode = 0775, $recursive = true)) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    } 
                }

                $pdf = new Pdf([
                    // set to use core fonts only
                    'mode'   => Pdf::MODE_CORE,
                    // A4 paper format
                    'format' => Pdf::FORMAT_A3,
                    // portrait orientation
                    'orientation' =>  Pdf::ORIENT_PORTRAIT,
                    // stream to browser inline
                    'destination' => Pdf::DEST_FILE,
                    // file name save serve
                    'filename'    => $gestion_archivo."/".$nameFile.".pdf",
                    // your html content input
                    'content'   => Yii::$app->controller->renderPartial("/".$template,['data' => $data]),
                    // format content from your own css file if needed or use the
                    // enhanced bootstrap css built by Krajee for mPDF formatting 
                    'cssFile'   => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                    // any css to be embedded if required
                    'cssInline' => '.kv-heading-1{font-size:18px}',
                    // set mPDF properties on the fly
                    'options'   => ['title' => 'Krajee Report Title','setAutoBottomMargin' => 'stretch'],
                    // call mPDF methods on the fly
                    'methods'   => [
                        'SetSubject'  =>  $mesaje,
                        'SetKeywords' => 'Export, PDF',
                        'SetFooter'   => [],
                    ]
                ]);
        
                $pdf->Render();

                //Lee el archivo dentro de una cadena en base 64
                $dataFilePdf = base64_encode(file_get_contents($gestion_archivo."/".$nameFile.".pdf"));

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => $mesaje,
                    'data' => [], // data
                    'fileName' => $nameFile,
                    'status' => 200,
                ];                        
                $return = HelperEncryptAes::encrypt($response, true);
                     
                // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                $return['datafile'] = $dataFilePdf;

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['DownloadFile'].' de gestion de archivo con el nombre: '.$nameFile, 
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                // Eliminar copia pdf y
                unlink($gestion_archivo."/".$nameFile.".pdf"); 
                
                return $return;
  
            } else{
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
                'message' => Yii::t('app','accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

    }
}
