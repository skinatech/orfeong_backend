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

use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

use yii\helpers\FileHelper;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\data\ActiveDataProvider;
use api\models\CgPlantillas;
use api\models\GdExpedienteDocumentos;
use api\models\GdExpedientes;
use api\models\OcrDatos;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiRadicados;

require __DIR__ . '/../sphinx/sphinxapi.php';
use SphinxClient;

use api\components\HelperQueryDb;

/**
 * Consultas Avanzada Controller 
 */
class ConsultasController extends Controller
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
                    'change-status'  => ['PUT'],
                    'content-document' => ['POST']
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
     * Lists all CgPlantillas models.
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

            //Lista de radicados
            $dataList = []; $ocrDatosGroup = [];  
            $dataBase64Params = "";
            $idSphinx = []; 
            $limitRecords = Yii::$app->params['limitRecords'];
            
            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if ( is_array($request)) {
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
                                } elseif($info != '') { // O un string
                                    $dataWhere[$key] = $info;
                                }                            
                            }
                        }
                    }
                }
            }   

            if(isset($dataWhere['textoExtraidoOcrDatos'])){

                $cl = new SphinxClient; 
                $cl->SetServer(Yii::$app->params['SetServer'], 9312); 
                $cl->SetMatchMode(SPH_MATCH_ANY); 
                $cl->SetMaxQueryTime(Yii::$app->params['SetMaxQueryTime']); 
                $cl->SetLimits(Yii::$app->params['SetLimitsMin'], Yii::$app->params['SetLimitsMax']); 
                $SphinxClient = $cl->Query($dataWhere['textoExtraidoOcrDatos'], Yii::$app->params['IndexerSphinx']);
   
                if ($SphinxClient === false) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errSphixClient'),
                        'error'   => $cl->GetLastError(),
                        'status'  => Yii::$app->params['statusErrorValidacion']
                    ];
                    return HelperEncryptAes::encrypt($response, true); 
                }
                else{

                    if($cl->GetLastWarning()){
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errSphixClient'),
                            'error'   => $cl->GetLastWarning(),
                            'status'  => Yii::$app->params['statusErrorValidacion']
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    
                    // Almacena los Ids de la tabla OcrDatos
                    if (!empty($SphinxClient["matches"])) {

                        foreach ($SphinxClient["matches"] as $doc => $docinfo ) {
                            $idSphinx[] = $docinfo['attrs'][Yii::$app->params['idIndexerSphinx']];
                        }
                    }
                }
            }
            
            /** Submodulo Consulta Avanzada** 
             * 
             * En index debe mostrar las siguientes columnas 
             * 
             * Fecha Carga, 
             * Dependencia Radico, 
             * Usuario Radicado, 
             * Asunto Radicado, 
             * Número Radicado, 
             * Nombre Documento. 
             * 
             * Estos datos deben ser consultados de la tabla ocrDatos 
             * (como esta tabla no tiene relaciones creadas directamente se debe hacer la consulta manual, 
             *  es decir los datos relacionados del radicado salen con el id del documento(idDocumentoOcrDatos) 
             * y ¿como saben de que tabla sale ese id? del campo (tablaAfectadaOcrDatos)
             * 
             **/ 

            /** Para el caso de la tabla gdExpedienteDocumentos ---> 
             * 
             * Usuario y Dependencia radicado seria = a Usuario y Dependencia que lo cargo, 
             * Asunto seria nombreGdExpedienteDocumento , 
             * y numero de radicado seria numeroGdExpedienteDocumento. 
             * 
             **/

            # Buscar en la tabla ocrDatos
            $ocrDatos = OcrDatos::find(); // ->andWhere(['estadoOcrDatos' => Yii::$app->params['statusTodoText']['Activo']]);


             # Se reitera $dataWhere para solo obtener los campos con datos
             foreach ($dataWhere as $field => $value) {
                switch ($field) {

                    case 'fechaInicial':
                        $ocrDatos->andWhere(['>=', 'creacionOcrDatos', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $ocrDatos->andWhere(['<=', 'creacionOcrDatos', trim($value) . Yii::$app->params['timeEnd']]);
                    break;

                }
             }


            if(!empty($idSphinx)){
                $ocrDatos = $ocrDatos->where(['IN','idOcrDatos', $idSphinx]);
            }

            $modelocrDatos = $ocrDatos->limit($limitRecords)->all();
            
            # Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual
            $modelocrDatos = array_reverse($modelocrDatos);
            $prueba = [];

            # Agrupo ocrDatos como key como tabla afectada = array(idDocumentos, ...)
            foreach($modelocrDatos as $key => $valueOcrDatos){
                $ocrDatosGroup[$valueOcrDatos['tablaAfectadaOcrDatos']][] = $valueOcrDatos['idDocumentoOcrDatos'];
                $prueba[] = $valueOcrDatos['idDocumentoOcrDatos'];
            }

            foreach($ocrDatosGroup as $tableOcrDatos => $arrayIdDocumento){ 
                
                # Buscar por Expedientes
                if($tableOcrDatos == Yii::$app->params['tablaOcrDatos']['gdExpedienteDoc']){

                    // joinWith
                    $gdExpDocumento = GdExpedientes::find(); //->select(['gdExpedienteDocumentos.*']);

                        /*->joinWith('gdExpedientesInclusion', '`gdExpedientesInclusion`.`idGdExpediente` = `gdExpedientes`.`idGdExpediente`')
                        ->innerJoin('gdExpedienteDocumentos', '`gdExpedienteDocumentos`.`idGdExpediente` = `gdExpedientes`.`idGdExpediente`')
                        ->innerJoin('userDetalles', '`userDetalles`.`idUser` = `gdExpedientes`.`idUser`')*/

                    $gdExpDocumento = HelperQueryDb::getQuery('leftJoin', $gdExpDocumento, 'gdExpedientesInclusion', ['gdExpedientes' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);
                    $gdExpDocumento = HelperQueryDb::getQuery('leftJoin', $gdExpDocumento, 'gdExpedienteDocumentos', ['gdExpedientes' => 'idGdExpediente', 'gdExpedienteDocumentos' => 'idGdExpediente']);
                    $gdExpDocumento = HelperQueryDb::getQuery('innerJoin', $gdExpDocumento, 'userDetalles', ['gdExpedientes' => 'idUser', 'userDetalles' => 'idUser']);

                    $gdExpDocumento = $gdExpDocumento->where(['IN','gdExpedienteDocumentos.idGdExpedienteDocumento', $arrayIdDocumento]);
                        
                    # Se reitera $dataWhere para solo obtener los campos con datos
                    foreach ($dataWhere as $field => $value) {
                        switch ($field) {
                            case 'numero':
                                $gdExpDocumento->andWhere([Yii::$app->params['like'], 'gdExpedientes.numeroGdExpediente', $value]);
                            break;
                            case 'nombreGdExpediente':
                                $gdExpDocumento->andWhere([Yii::$app->params['like'], 'gdExpedientes.nombreGdExpediente' , $value]);
                            break;
                            case 'asunto':
                                $gdExpDocumento->andWhere([Yii::$app->params['like'], 'gdExpedientes.descripcionGdExpediente' , $value]);
                            break;
                            case 'dependenciaCrador':
                            break;
                            case 'creador': 
                                $gdExpDocumento->andWhere(['userDetalles.idUser' => $value]);
                            break;
                            case 'dependencia':
                                $gdExpDocumento->andWhere(['IN', 'gdExpedientes.idGdTrdDependencia', $value]);
                            break;
                            # filter radicados
                            case 'nombreDocumento':
                                $gdExpDocumento->andWhere([Yii::$app->params['like'], 'gdExpedienteDocumentos.' . 'nombreGdExpedienteDocumento', $value]);
                            break;
                            case 'extensionDocumento':
                                $gdExpDocumento->andWhere(['IN', 'gdExpedienteDocumentos.' . 'extensionGdExpedienteDocumento', $value]);
                            break;
                        }
                    } 
                    
                    $modelGdExpDocumento = $gdExpDocumento->all();//->groupBy(['gdExpedienteDocumentos.idGdExpedienteDocumento'])

                    foreach($modelGdExpDocumento as $key => $gdExpedientes){
                        if(isset($modelGdExpDocumento[$key]->gdExpedienteDocumentos)){
                            # Documentos tabla -> gdExpedienteDocumentos
                            foreach($modelGdExpDocumento[$key]->gdExpedienteDocumentos as $gdExpedienteDocumentos){

                            if (in_array($gdExpedienteDocumentos->idGdExpedienteDocumento, $prueba)) {
                                 
                                # Agregar datos a datalist
                                $dataBase64Params = array(
                                    str_replace(array('/', '+'), array('_', '-'), base64_encode($gdExpedienteDocumentos->idGdExpedienteDocumento))
                                );

                                if(isset($gdExpedientes->gdExpedientesInclusion) && !empty($gdExpedientes->gdExpedientesInclusion)){
    
                                    # Expedientes que estan asociados a radicados tabla -> gdExpedientesInclusion
                                    foreach($gdExpedientes->gdExpedientesInclusion as $gdExpedientesInclusion){
                
                                        $dataList[] = array(
                                            'tipo'                  => 'expediente-radicado',
                                            'data'                  => $dataBase64Params,
                                            'id'                    => $gdExpedienteDocumentos->idGdExpedienteDocumento,
                                            'fechaCarga'            => $gdExpedienteDocumentos->creacionGdExpedienteDocumento,
                                            'numero'                => $gdExpedientesInclusion->radiRadicado->numeroRadiRadicado,
                                            'asunto'                => $gdExpedientesInclusion->radiRadicado->asuntoRadiRadicado,
                                            'usuario'               => $gdExpedientesInclusion->radiRadicado->userIdTramitador->userDetalles->nombreUserDetalles.' '.$gdExpedientesInclusion->radiRadicado->userIdTramitador->userDetalles->apellidoUserDetalles,
                                            'dependencia'           => $gdExpedientesInclusion->radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                                            'tipodocumental'        => $gdExpedienteDocumentos->gdTrdTipoDocumental->nombreTipoDocumental,
                                            'status'                => $gdExpedientesInclusion->radiRadicado->estadoRadiRadicado,
                                            'statusText'            => Yii::t('app', 'statusTodoNumber')[$gdExpedientesInclusion->radiRadicado->estadoRadiRadicado], 
                                            'rowSelect'             => false,
                                            'idInitialList'         => 0,                    
                                            //Info Documento
                                            'routeDownload'         => 'radicacion/documentos/download-doc-expedientes',
                                            'nombreDocumento'       => $gdExpedienteDocumentos->nombreGdExpedienteDocumento,
                                            'extensionDocumento'    => $gdExpedienteDocumentos->extensionGdExpedienteDocumento,
                                            'tablaOcrDatos'         => $tableOcrDatos,
                                        );
            
                                    }

                                }else{
    
                                    # Expedientes que NO estan asociados a radicados tabla -> gdExpedientesInclusion
                                    $dataList[] = array(
                                        'tipo'                  => 'expediente',
                                        'data'                  => $dataBase64Params,
                                        'id'                    => $gdExpedienteDocumentos->idGdExpedienteDocumento,
                                        'fechaCarga'            => $gdExpedienteDocumentos->creacionGdExpedienteDocumento,
                                        'numero'                => $gdExpedientes->numeroGdExpediente,
                                        'asunto'                => $gdExpedientes->nombreGdExpediente,
                                        'usuario'               => $gdExpedientes->user->userDetalles->nombreUserDetalles.' '.$gdExpedientes->user->userDetalles->apellidoUserDetalles,
                                        'dependencia'           => $gdExpedientes->gdTrdDependencia->nombreGdTrdDependencia,
                                        'tipodocumental'        => $gdExpedienteDocumentos->gdTrdTipoDocumental->nombreTipoDocumental,
                                        'status'                => $gdExpedientes->estadoGdExpediente,
                                        'statusText'            => Yii::t('app', 'statusTodoNumber')[$gdExpedientes->estadoGdExpediente],      
                                        'rowSelect'             => false,
                                        'idInitialList'         => 0, 
                                        // Info Documento
                                        'routeDownload'         => 'radicacion/documentos/download-doc-expedientes',
                                        'nombreDocumento'       => $gdExpedienteDocumentos->nombreGdExpedienteDocumento,
                                        'extensionDocumento'    => $gdExpedienteDocumentos->extensionGdExpedienteDocumento,
                                        'tablaOcrDatos'         => $tableOcrDatos,                
                                    );
    
                                }
                            }

                            }
                        }
                    }

                # Buscar por RadiDocumentos
                }else if($tableOcrDatos == Yii::$app->params['tablaOcrDatos']['radiDocumentos']){

                    $relacionRadicados = RadiDocumentos::find();
                        //->innerJoin('radiRadicados', '`radiRadicados`.`idRadiRadicado` = `radiDocumentos`.`idRadiRadicado`')
                        $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRadicados', ['radiDocumentos' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
                        $relacionRadicados = $relacionRadicados->where(['IN','radiDocumentos.idRadiDocumento', $arrayIdDocumento]);

                        # Se reitera $dataWhere para solo obtener los campos con datos
                        foreach ($dataWhere as $field => $value) {
                            switch ($field) {
                                case 'numero':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.numeroRadiRadicado', $value]);
                                break;
                                case 'asuntoRadiRadicado':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.asuntoRadiRadicado', $value]);
                                break;
                                case 'idCgTipoRadicado':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . 'idCgTipoRadicado', $value]);
                                break;
                                case 'dependenciaCrador':
                                break;
                                case 'creador': 
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.user_idCreador' , $value]);
                                break;
                                case 'tramitador':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.user_idTramitador' , $value]);
                                break;
                                case 'dependencia':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.idTrdDepeUserTramitador', $value]);
                                break;
                                # filters radicados Documentos
                                case 'nombreDocumento':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiDocumentos.' . 'nombreRadiDocumento', $value]);
                                break;

                                case 'extensionDocumento':
                                    $relacionRadicados->andWhere(['IN', 'radiDocumentos.' . 'extencionRadiDocumento', $value]);
                                break;

                            }
                        } 
                        
                    $modelRadiRadicados = $relacionRadicados->all();
                    
                    foreach($modelRadiRadicados as $key => $radiDocumentos){ 

                        # Agregar datos a datalist
                        $dataBase64Params = array(
                            str_replace(array('/', '+'), array('_', '-'), base64_encode($radiDocumentos->idRadiDocumento))
                        );

                        $dataList[] = array(
                            'tipo'                  => 'radidocumentos',
                            'data'                  => $dataBase64Params,
                            'id'                    => $radiDocumentos->idRadiDocumento,
                            'numero'                => $radiDocumentos->idRadiRadicado0->numeroRadiRadicado,
                            'asunto'                => $radiDocumentos->idRadiRadicado0->asuntoRadiRadicado,
                            'usuario'               => $radiDocumentos->idRadiRadicado0->userIdTramitador->userDetalles->nombreUserDetalles.' '.$radiDocumentos->idRadiRadicado0->userIdTramitador->userDetalles->apellidoUserDetalles,
                            'dependencia'           => $radiDocumentos->idRadiRadicado0->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                            'tipodocumental'        => $radiDocumentos->idGdTrdTipoDocumental0->nombreTipoDocumental,
                            'status'                => $radiDocumentos->idRadiRadicado0->estadoRadiRadicado,
                            'statusText'            => Yii::t('app', 'statusTodoNumber')[$radiDocumentos->estadoRadiDocumento], 
                            'rowSelect'             => false,
                            'idInitialList'         => 0,                    
                            // Info Documento
                            'routeDownload'         => 'radicacion/documentos/download-document',  
                            'nombreDocumento'       => $radiDocumentos->nombreRadiDocumento,
                            'extensionDocumento'    => $radiDocumentos->extencionRadiDocumento,
                            'fechaCarga'            => $radiDocumentos->creacionRadiDocumento,
                            'tablaOcrDatos'         => $tableOcrDatos,
                        );
                        
                    }

                # Buscar por RadiDocumentosPrincipales
                }else if($tableOcrDatos == Yii::$app->params['tablaOcrDatos']['radiDocPrincipales']){

                    $relacionRadicados = RadiDocumentosPrincipales::find();
                        //->innerJoin('radiRadicados', '`radiRadicados`.`idRadiRadicado` = `radiDocumentosPrincipales`.`idRadiRadicado`')
                        $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'radiRadicados', ['radiDocumentosPrincipales' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
                        $relacionRadicados = $relacionRadicados->where(['IN','radiDocumentosPrincipales.idradiDocumentoPrincipal', $arrayIdDocumento]);

                        # Se reitera $dataWhere para solo obtener los campos con datos
                        foreach ($dataWhere as $field => $value) {
                            switch ($field) {
                                case 'numero':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.' . 'numeroRadiRadicado', $value]);
                                break;
                                case 'asuntoRadiRadicado':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiRadicados.asuntoRadiRadicado', $value]);
                                break;
                                case 'idCgTipoRadicado':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . 'idCgTipoRadicado', $value]);
                                break;
                                case 'dependenciaCrador':
                                break;
                                case 'creador': 
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.user_idCreador' , $value]);
                                break;
                                case 'tramitador':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.user_idTramitador' , $value]);
                                break;
                                case 'dependencia':
                                    $relacionRadicados->andWhere(['IN', 'radiRadicados.idTrdDepeUserTramitador', $value]);
                                break;
                                # filters radicados Documentos Principales
                                case 'nombreDocumento':
                                    $relacionRadicados->andWhere([Yii::$app->params['like'], 'radiDocumentosPrincipales.' . 'nombreRadiDocumentoPrincipal', $value]);
                                break;
                                case 'extensionDocumento':
                                    $relacionRadicados->andWhere(['IN', 'radiDocumentosPrincipales.' . 'extensionRadiDocumentoPrincipal', $value]);
                                break;

                            }
                        }

                    $modelRadiRadicados = $relacionRadicados->all(); 

                    foreach($modelRadiRadicados as $key => $radiDocPrincipales){ 
                      
                        # Agregar datos a datalist
                        $dataBase64Params = array(
                            str_replace(array('/', '+'), array('_', '-'), base64_encode($radiDocPrincipales->idradiDocumentoPrincipal))
                        );

                        $dataList[] = array(
                            'tipo'                  => 'radidocumentosPrincipales',
                            'data'                  => $dataBase64Params,
                            'id'                    => $radiDocPrincipales->idradiDocumentoPrincipal,
                            'numero'                => $radiDocPrincipales->idRadiRadicado0->numeroRadiRadicado,
                            'asunto'                => $radiDocPrincipales->idRadiRadicado0->asuntoRadiRadicado,
                            'usuario'               => $radiDocPrincipales->idRadiRadicado0->userIdTramitador->userDetalles->nombreUserDetalles.' '.$radiDocPrincipales->idRadiRadicado0->userIdTramitador->userDetalles->apellidoUserDetalles,
                            'dependencia'           => $radiDocPrincipales->idRadiRadicado0->idTrdDepeUserTramitador0->nombreGdTrdDependencia,
                            'tipodocumental'        => $radiDocPrincipales->idRadiRadicado0->trdTipoDocumental->nombreTipoDocumental,
                            'status'                => $radiDocPrincipales->idRadiRadicado0->estadoRadiRadicado,
                            'statusText'            => Yii::t('app', 'statusTodoNumber')[$radiDocPrincipales->estadoRadiDocumentoPrincipal], 
                            'rowSelect'             => false,
                            'idInitialList'         => 0,                    
                            // Info Documento
                            'routeDownload'         => 'radicacion/documentos/download-doc-principal',
                            'nombreDocumento'       => $radiDocPrincipales->nombreRadiDocumentoPrincipal,
                            'extensionDocumento'    => $radiDocPrincipales->extensionRadiDocumentoPrincipal,
                            'fechaCarga'            => $radiDocPrincipales->creacionRadiDocumentoPrincipal,
                            'tablaOcrDatos'         => $tableOcrDatos,
                        );
                    
                    }

                }

            }
            
            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexConsultaDocumentos');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                // 'ocrDatos' => $modelocrDatos,
                // 'ocrDatosGroup' => $ocrDatosGroup,
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'SphinxClient' => [

                    'data' => $idSphinx ?? false,
                    'error' => $err  ?? false
            
                ],
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
     *  Devolver contenido del documentop
     *  @param array $request
     */
    public function actionContentDocument(){ 

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            // $response = ['id' => [1], 'observacionRadiEnvio' => 'Devolución de correspondencia',
            //  'idCgMotivoDevolucion' => 4,];
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

                    $ocrDatos = OcrDatos::findOne(['idDocumentoOcrDatos' => $request['id'], 'tablaAfectadaOcrDatos' => $request['tablaOcrDatos']]);

                    if(isset($ocrDatos)){

                        $contenido = $ocrDatos['textoExtraidoOcrDatos'];

                        /***    Log de Auditoria  ***/
                        HelperLog::logAdd(
                            false,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['ViewDocument'].' Id: '.$ocrDatos->idOcrDatos.' de la tabla: '.Yii::$app->params['tablaOcrDatosNumber'][$ocrDatos['tablaAfectadaOcrDatos']], //texto para almacenar en el evento
                            [], //DataOld
                            [], //Data
                            array() //No validar estos campos
                        );
                        /***    Fin log Auditoria   ***/         

                    }

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' =>  'OK',
                        'data' => $contenido ?? false,
                        'status' => 200,
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



}
