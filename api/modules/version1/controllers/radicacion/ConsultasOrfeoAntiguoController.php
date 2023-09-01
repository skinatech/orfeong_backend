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
use api\components\HelperOtherConnectionsDb;
use api\modules\version1\controllers\correo\CorreoController;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

use yii\data\ActiveDataProvider;

use api\models\CgGeneralBasesDatos;
use api\components\HelperQueryDb;

/**
 * Consultas Avanzada Controller
 */
class ConsultasOrfeoAntiguoController extends Controller
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
                    'index'  => ['GET']
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
    public function actionIndex($request) {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            if (!empty($request)) {
                $decrypted = HelperEncryptAes::decryptGET($request, true);
                if ($decrypted['status'] == true) {
                    $request = $decrypted['request'];
                } else {
                    $request = '';
                }
            }

            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            $dataWhere = [];
            if (is_array($request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info) {
                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if (isset($info) && $info !== null && trim($info) !== '') {
                                $dataWhere[$key] =  $info;
                            }
                        } else {
                            if(isset($info) && $info !== null && trim($info) != '' ) {
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            $where = "";
            foreach ($dataWhere as $field => $value) {
                switch ($field) {
                    case 'fechaInicial':
                        $where .= " AND ra.radi_fech_radi >= '". $value ." 00:00:00'";
                    break;
                    case 'fechaFinal':
                        $where .= " AND ra.radi_fech_radi <= '". $value ." 23:59:59'";
                    break;
                    case 'dependencia_radicadora':
                        $where .= " AND de.depe_codi = ". $value;
                    break;
                    case 'usuario_radicador':
                        $where .= " AND us.usua_codi = ". $value;
                    break;
                    case 'dependencia_actual':
                        $where .= " AND de2.depe_codi = ". $value;
                    break;
                    case 'usuario_actual':
                        $where .= " AND us2.usua_codi = ". $value;
                    break;
                    case 'radi_nume_radi':
                        $where .= " AND ra.radi_nume_radi = ". $value ."";
                    break;
                    case 'ra_asun':
                        $where .= " AND ra.ra_asun ILIKE '%". $value ."%'";
                    break;
                    case 'sgd_exp_numero':
                        $where .= " AND sexp.sgd_exp_numero = '". $value ."'";
                    break;
                    case 'sgd_sexp_parexp1':
                        $where .= " AND sexp.sgd_sexp_parexp1 ILIKE '%". $value ."%'";
                    break;
                    case 'radi_nume_guia':
                        $where .= " AND ra.radi_nume_guia ILIKE '%". $value ."%'";
                    break;
                    case 'sgd_dir_nomremdes':
                        $where .= " AND dir.sgd_dir_nomremdes ILIKE '%". $value ."%'";
                    break;
                    case 'sgd_tpr_descrip':
                        $where .= " AND tpr.sgd_tpr_codigo = ". $value ."";
                    break;
                    case 'documento':
                        $where .= " AND (ciu.sgd_ciu_cedula = '". $value ."' or oem.sgd_oem_nit = '". $value ."' or us3.usua_doc = '". $value ."')";
                    break;
                }
            }
           
            $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
            $consultaOrfeo38Index = $conexionOrfeo38->createCommand("SELECT ra.radi_nume_radi, ra.radi_fech_radi, ra.ra_asun, sexp.sgd_exp_numero, sexp.sgd_sexp_parexp1, dir.sgd_dir_nomremdes, tpr.sgd_tpr_descrip, de.depe_nomb AS dependencia_radicadora, us.usua_nomb AS usuario_radicador, de2.depe_nomb AS dependencia_actual, us2.usua_nomb AS usuario_actual, radi_nume_guia, CASE WHEN dir.sgd_oem_codigo <> 0 THEN oem.sgd_oem_nit  WHEN dir.sgd_ciu_codigo <> 0 THEN ciu.sgd_ciu_cedula WHEN dir.sgd_doc_fun <> '0' THEN us3.usua_doc END as documento FROM radicado ra LEFT JOIN sgd_exp_expediente exp ON ra.radi_nume_radi = exp.radi_nume_radi LEFT JOIN sgd_sexp_secexpedientes  sexp ON exp.sgd_exp_numero = sexp.sgd_exp_numero INNER JOIN sgd_dir_drecciones dir ON ra.radi_nume_radi = dir.radi_nume_radi INNER JOIN sgd_tpr_tpdcumento tpr ON tpr.sgd_tpr_codigo = ra.tdoc_codi INNER JOIN dependencia de ON de.depe_codi = ra.radi_depe_radi INNER JOIN dependencia de2 ON de2.depe_codi = ra.radi_depe_actu INNER JOIN usuario us ON us.usua_codi = ra.radi_usua_radi INNER JOIN usuario us2 ON us2.usua_codi = ra.radi_usua_actu LEFT JOIN sgd_ciu_ciudadano ciu ON dir.sgd_ciu_codigo=ciu.sgd_ciu_codigo LEFT JOIN sgd_oem_oempresas oem ON dir.sgd_oem_codigo=oem.sgd_oem_codigo LEFT JOIN usuario us3 ON dir.sgd_doc_fun=us3.usua_doc WHERE 1 = 1 ". $where ." ORDER BY radi_fech_radi DESC LIMIT ". $limitRecords ."");
            //error_log(" ##### "."SELECT ra.radi_nume_radi, ra.radi_fech_radi, ra.ra_asun, sexp.sgd_exp_numero, sexp.sgd_sexp_parexp1, dir.sgd_dir_nomremdes, tpr.sgd_tpr_descrip, de.depe_nomb AS dependencia_radicadora, us.usua_nomb AS usuario_radicador, de2.depe_nomb AS dependencia_actual, us2.usua_nomb AS usuario_actual, radi_nume_guia, CASE WHEN dir.sgd_oem_codigo <> 0 THEN oem.sgd_oem_nit  WHEN dir.sgd_ciu_codigo <> 0 THEN ciu.sgd_ciu_cedula WHEN dir.sgd_doc_fun <> '0' THEN us3.usua_doc END as documento FROM radicado ra LEFT JOIN sgd_exp_expediente exp ON ra.radi_nume_radi = exp.radi_nume_radi LEFT JOIN sgd_sexp_secexpedientes  sexp ON exp.sgd_exp_numero = sexp.sgd_exp_numero INNER JOIN sgd_dir_drecciones dir ON ra.radi_nume_radi = dir.radi_nume_radi INNER JOIN sgd_tpr_tpdcumento tpr ON tpr.sgd_tpr_codigo = ra.tdoc_codi INNER JOIN dependencia de ON de.depe_codi = ra.radi_depe_radi INNER JOIN dependencia de2 ON de2.depe_codi = ra.radi_depe_actu INNER JOIN usuario us ON us.usua_codi = ra.radi_usua_radi INNER JOIN usuario us2 ON us2.usua_codi = ra.radi_usua_actu LEFT JOIN sgd_ciu_ciudadano ciu ON dir.sgd_ciu_codigo=ciu.sgd_ciu_codigo LEFT JOIN sgd_oem_oempresas oem ON dir.sgd_oem_codigo=oem.sgd_oem_codigo LEFT JOIN usuario us3 ON dir.sgd_doc_fun=us3.usua_doc WHERE 1 = 1 ". $where ." ORDER BY radi_fech_radi DESC LIMIT ". $limitRecords ."");
                        
            try {
                $resultConsultaOrfeo38Index = $consultaOrfeo38Index->queryAll();
            } catch (\Throwable $th) {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'accessDenied')[2],
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorAccessDenied'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            foreach ($resultConsultaOrfeo38Index as $resultadoIndex) {  

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($resultadoIndex['radi_nume_radi'])),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($resultadoIndex['radi_nume_radi']))
                );

                $dataList[] = array(
                    'data'                                => $dataBase64Params,
                    'id'                                  => $resultadoIndex['radi_nume_radi'],
                    'numeroRadicado'                      => $resultadoIndex['radi_nume_radi'],
                    'fechaRadicacion'                     => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoIndex['radi_fech_radi'])),
                    'asuntoRadicado'                      => $resultadoIndex['ra_asun'],
                    'radiNumeGuia'                        => ($resultadoIndex['radi_nume_guia'] != '') ? $resultadoIndex['radi_nume_guia'] : 'sin guia',
                    'numeroExpediente'                    => ($resultadoIndex['sgd_exp_numero'] != '') ? $resultadoIndex['sgd_exp_numero'] : 'sin expediente',
                    'nombreExpediente'                    => ($resultadoIndex['sgd_sexp_parexp1'] != '') ? $resultadoIndex['sgd_sexp_parexp1'] : 'sin nombre',
                    'remitenteDestinatario'               => $resultadoIndex['sgd_dir_nomremdes'],
                    'documentoIdentidad'                  => ($resultadoIndex['documento'] != '') ? $resultadoIndex['documento'] : 'sin documento',
                    'identificacionDependenciaRadicadora' => $resultadoIndex['dependencia_radicadora'],
                    'usuarioRadicador'                    => $resultadoIndex['usuario_radicador'],
                    'dependenciaActual'                   => $resultadoIndex['dependencia_actual'],
                    'usuarioActual'                       => $resultadoIndex['usuario_actual'],
                    'tipoDocumental'                      => $resultadoIndex['sgd_tpr_descrip'],
                    'rowSelect'                           => false,
                    'idInitialList'                       => 0,
                );
            }

            $formType = HelperDynamicForms::setListadoBD('indexConsultaOrfeoAntiguo');
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit100($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['cantRecords' => count($dataList),'limitRecords' => $limitRecords]) : false,
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

    public function actionView($request) {
        if (!empty($request)) {
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
        }

        $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
        $consultaOrfeo38View = $conexionOrfeo38->createCommand("SELECT ra.radi_nume_radi, ra.radi_fech_radi, ra.ra_asun, sexp.sgd_exp_numero, sexp.sgd_sexp_parexp1, dir.sgd_dir_nomremdes, tpr.sgd_tpr_descrip, de.depe_nomb AS dependencia_radicadora, us.usua_nomb AS usuario_radicador, de2.depe_nomb AS dependencia_actual, us2.usua_nomb AS usuario_actual, radi_nume_guia, CASE  WHEN dir.sgd_oem_codigo <> 0 THEN oem.sgd_oem_nit  WHEN dir.sgd_ciu_codigo <> 0 THEN ciu.sgd_ciu_cedula END as documento FROM radicado ra LEFT JOIN sgd_exp_expediente exp ON ra.radi_nume_radi = exp.radi_nume_radi LEFT JOIN sgd_sexp_secexpedientes  sexp ON exp.sgd_exp_numero = sexp.sgd_exp_numero INNER JOIN sgd_dir_drecciones dir ON ra.radi_nume_radi = dir.radi_nume_radi LEFT JOIN sgd_tpr_tpdcumento tpr ON ra.tdoc_codi = tpr.sgd_tpr_codigo INNER JOIN dependencia de ON de.depe_codi = ra.radi_depe_radi INNER JOIN dependencia de2 ON de2.depe_codi = ra.radi_depe_actu INNER JOIN usuario us ON us.usua_codi = ra.radi_usua_radi INNER JOIN usuario us2 ON us2.usua_codi = ra.radi_usua_actu LEFT JOIN sgd_ciu_ciudadano ciu ON dir.sgd_ciu_codigo=ciu.sgd_ciu_codigo LEFT JOIN sgd_oem_oempresas oem ON dir.sgd_oem_codigo=oem.sgd_oem_codigo WHERE 1 = 1 AND ra.radi_nume_radi = '". $request['id'] ."'");
        //error_log("SELECT ra.radi_nume_radi, ra.radi_fech_radi, ra.ra_asun, sexp.sgd_exp_numero, sexp.sgd_sexp_parexp1, dir.sgd_dir_nomremdes, tpr.sgd_tpr_descrip, de.depe_nomb AS dependencia_radicadora, us.usua_nomb AS usuario_radicador, de2.depe_nomb AS dependencia_actual, us2.usua_nomb AS usuario_actual, radi_nume_guia, CASE  WHEN dir.sgd_oem_codigo <> 0 THEN oem.sgd_oem_nit  WHEN dir.sgd_ciu_codigo <> 0 THEN ciu.sgd_ciu_cedula END as documento FROM radicado ra LEFT JOIN sgd_exp_expediente exp ON ra.radi_nume_radi = exp.radi_nume_radi LEFT JOIN sgd_sexp_secexpedientes  sexp ON exp.sgd_exp_numero = sexp.sgd_exp_numero INNER JOIN sgd_dir_drecciones dir ON ra.radi_nume_radi = dir.radi_nume_radi INNER JOIN sgd_tpr_tpdcumento tpr ON tpr.sgd_tpr_codigo = ra.tdoc_codi INNER JOIN dependencia de ON de.depe_codi = ra.radi_depe_radi INNER JOIN dependencia de2 ON de2.depe_codi = ra.radi_depe_actu INNER JOIN usuario us ON us.usua_codi = ra.radi_usua_radi INNER JOIN usuario us2 ON us2.usua_codi = ra.radi_usua_actu INNER JOIN sgd_ciu_ciudadano ciu ON dir.sgd_ciu_codigo=ciu.sgd_ciu_codigo INNER JOIN sgd_oem_oempresas oem ON dir.sgd_oem_codigo=oem.sgd_oem_codigo WHERE 1 = 1 AND ra.radi_nume_radi = '". $request['id'] ."'");
        $resultadoView = $consultaOrfeo38View->queryOne();

        $data = [
            ['alias' => 'Número radicado', 'value'                       => $resultadoView['radi_nume_radi']],
            ['alias' => 'Fecha radicacion', 'value'                      => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoView['radi_fech_radi']))],
            ['alias' => 'Asunto radicado', 'value'                       => $resultadoView['ra_asun']],
            ['alias' => 'Número Guia', 'value'                           => $resultadoView['radi_nume_guia']],
            ['alias' => 'Número expediente', 'value'                     => $resultadoView['sgd_exp_numero']],
            ['alias' => 'Nombre expediente', 'value'                     => $resultadoView['sgd_sexp_parexp1']],
            ['alias' => 'Remitente/destinatario', 'value'                => $resultadoView['sgd_dir_nomremdes']],
            ['alias' => 'Documento de Identidad', 'value'                => $resultadoView['documento']],
            ['alias' => 'Identificación dependencia radicadora', 'value' => $resultadoView['dependencia_radicadora']],
            ['alias' => 'Usuario radicador', 'value'                     => $resultadoView['usuario_radicador']],
            ['alias' => 'Dependencia actual', 'value'                    => $resultadoView['dependencia_actual']],
            ['alias' => 'Usuario actual', 'value'                        => $resultadoView['usuario_actual']],
            ['alias' => 'Tipo documental', 'value'                       => $resultadoView['sgd_tpr_descrip']],
        ];

        $consultaOrfeo38ViewHistorial = $conexionOrfeo38->createCommand("SELECT hist.hist_fech, hist.hist_obse, us.usua_nomb, ttr.sgd_ttr_descrip, radi_nume_radi, de.depe_nomb FROM  hist_eventos hist INNER JOIN sgd_ttr_transaccion ttr ON hist.sgd_ttr_codigo=ttr.sgd_ttr_codigo INNER JOIN usuario us ON us.usua_doc=hist.usua_doc INNER JOIN dependencia de ON hist.depe_codi=de.depe_codi WHERE radi_nume_radi = '". $request['id'] ."'");
        $resultadoViewHistorial = $consultaOrfeo38ViewHistorial->queryAll();
        foreach ($resultadoViewHistorial as $resultadoHistorial) {
            $dataHistorial[] = array(
                'usuario' => $resultadoHistorial['usua_nomb'],
                'dependencia' => $resultadoHistorial['depe_nomb'],
                'fecha' => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoHistorial['hist_fech'])),
                'transaccion' => $resultadoHistorial['sgd_ttr_descrip'],
                'activiad' => $resultadoHistorial['hist_obse'],
            );
        }

        $dataLogRadicado = ' Número radicado:' .' '. $resultadoView['radi_nume_radi'];
        $dataLogRadicado .= ' Fecha radicacion:' .' '. strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoView['radi_fech_radi']));
        $dataLogRadicado .= ' Asunto radicado:' .' '. $resultadoView['ra_asun'];
        $dataLogRadicado .= ' Número expediente:' .' '. $resultadoView['sgd_exp_numero'];
        $dataLogRadicado .= ' Nombre expediente:' .' '. $resultadoView['sgd_sexp_parexp1'];
        $dataLogRadicado .= ' Remitente/destinatario:' .' '. $resultadoView['sgd_dir_nomremdes'];
        $dataLogRadicado .= ' Identificación dependencia radicadora:' .' '. $resultadoView['dependencia_radicadora'];
        $dataLogRadicado .= ' Usuario radicador:' .' '. $resultadoView['usuario_radicador'];
        $dataLogRadicado .= ' Dependencia actual:' .' '. $resultadoView['dependencia_actual'];
        $dataLogRadicado .= ' Usuario actual:' .' '. $resultadoView['usuario_actual'];
        $dataLogRadicado .= ' Tipo documental:' .' '. $resultadoView['sgd_tpr_descrip'];

        HelperLog::logAdd(
            true,
            Yii::$app->user->identity->id,
            Yii::$app->user->identity->username,
            Yii::$app->controller->route,
            Yii::$app->params['eventosLogText']['View'] . " radicado: ". $request['id'],
            '',
            $dataLogRadicado,
            array() //No validar estos campos
        );

        if(empty($dataHistorial)){
            $dataHistorial[] = array('No hay registros');
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'dataHistorial' => $dataHistorial,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionIndexDocumentos($request) {
        if (!empty($request)) {
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
        }

        $dataList = [];
        $dataBase64Params = "";
        $limitRecords = Yii::$app->params['limitRecords'];

        $dataWhere = [];
        if (is_array($request)) {
            if(array_key_exists('filterOperation', $request)) {
                foreach($request['filterOperation'] as $field) {
                    foreach($field as $key => $info) {
                        if ($key == 'inputFilterLimit') {
                            $limitRecords = $info;
                            continue;
                        }

                        if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                            if (isset($info) && $info !== null && trim($info) !== '') {
                                $dataWhere[$key] =  $info;
                            }
                        } else {
                            if(isset($info) && $info !== null && trim($info) != '' ) {
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }
        }

        $wherePrincipales = "";
        $where = "";
        foreach ($dataWhere as $field => $value) {
            switch ($field) {
                case 'fechaInicial':
                    $wherePrincipales .= " AND radi_fech_radi >= '". $value ." 00:00:00'";
                    $where .= " AND anexos.anex_fech_anex >= '". $value ." 00:00:00'";
                break;
                case 'fechaFinal':
                    $wherePrincipales .= " AND radi_fech_radi <= '". $value ." 23:59:59'";
                    $where .= " AND anexos.anex_fech_anex <= '". $value ." 23:59:59'";
                break;
                case 'descripcion':
                    $wherePrincipales .= " AND ra_asun LIKE '%". $value ."%'";
                    $where .= " AND anexos.anex_desc LIKE '%". $value ."%'";
                break;
                case 'usuario':
                    $wherePrincipales .= " AND u.usua_codi = '". $value ."'";
                    $where .= " AND us.usua_codi = '". $value ."'";
                break;
            }
        }

        $conexionOrfeo38 = HelperOtherConnectionsDb::otherConnectionsDB(Yii::$app->params['nombreCgGeneralBasesDatos']['orfeo38']);
        $consultaOrfeo38IndexDocumentosPrincipales = $conexionOrfeo38->createCommand("SELECT radi_path AS documento, radi_fech_radi AS fecha, ra_asun AS descripcion, u.usua_nomb AS usuario FROM radicado INNER JOIN usuario u ON u.usua_codi = radicado.radi_usua_actu WHERE radi_nume_radi = '". $request['id'] ."' AND radi_path != '' ". $wherePrincipales ."");
                
        $resultadoIndexDocumentosPrincipales = $consultaOrfeo38IndexDocumentosPrincipales->queryAll();
        foreach ($resultadoIndexDocumentosPrincipales as $resultadoDocPrincipal) {
            $explodeDoc = explode('.', $resultadoDocPrincipal['documento']);
            $explodeData = "";
            if (count($explodeDoc) > 0) {
                $explodeData = $explodeDoc[count($explodeDoc) - 1];
            }

            $dataList[] = array(
                'usuario' => $resultadoDocPrincipal['usuario'],
                'descripcion' => $resultadoDocPrincipal['descripcion'],
                'fecha' => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoDocPrincipal['fecha'])),
                'documento' => $resultadoDocPrincipal['documento'],
                'extension' => strtolower($explodeData),
                'rowSelect' => false,
            );
        }

        $consultaOrfeo38IndexDocumentos = $conexionOrfeo38->createCommand("SELECT us.usua_nomb AS usuario, anexos.anex_desc AS descripcion, anexos.anex_fech_anex AS fecha, anexos.anex_nomb_archivo AS documento, anex_radi_nume AS numanexo FROM anexos INNER JOIN usuario us on us.usua_login = anexos.anex_creador WHERE anex_radi_nume = '". $request['id'] ."' OR radi_nume_salida = '". $request['id'] ."' ". $where ."");
        $resultadoIndexDocumentos = $consultaOrfeo38IndexDocumentos->queryAll();
        foreach ($resultadoIndexDocumentos as $resultadoDocumento) {
            $explodeDoc = explode('.', $resultadoDocumento['documento']);
            $explodeData = "";
            if (count($explodeDoc) > 0) {
                $explodeData = $explodeDoc[count($explodeDoc) - 1];
            }

            $primerDato = substr($resultadoDocumento['documento'], 0, 1);
            $nombreDocumento = "";
            if ($primerDato == 1) {
                $nombreDocumento = substr($resultadoDocumento['numanexo'], 0, 4) ."/". substr($resultadoDocumento['numanexo'], 4, 3);
            } else {
                $nombreDocumento = substr($resultadoDocumento['numanexo'], 0, 4) ."/". substr($resultadoDocumento['numanexo'], 4, 3);
            }

            $dataList[] = array(
                'usuario' => $resultadoDocumento['usuario'],
                'descripcion' => $resultadoDocumento['descripcion'],
                'fecha' => strftime("%Y-%m-%d %H:%M:%S", strtotime($resultadoDocumento['fecha'])),
                'documento' => "/". $nombreDocumento ."/". Yii::$app->params['rutaDocsOrfeo38'] ."/". $resultadoDocumento['documento'],
                'extension' => strtolower($explodeData),
                'rowSelect' => false,
            );
        }

        $formType = HelperDynamicForms::setListadoBD('indexDocumentosConsultaOrfeoAntiguo');
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $dataList,
            'filtersData' => HelperDynamicForms::setInputLimit100($formType, $limitRecords),
            'infoLimitRecords' => false,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionDescargarDocumento() {
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
        if (!empty($jsonSend)) {
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

            $explodeNombreDoc = explode('/', $request['ButtonSelectedData'][0]['id']);
            $fileName = $explodeNombreDoc[count($explodeNombreDoc) - 1];
            $rutaFile = Yii::$app->params['rutaDocumentosOrfeo38'] ."". $request['ButtonSelectedData'][0]['id'];

            // Se identifica si es anexo o imagen principal, para agregar el /
            $pos = strpos($request['ButtonSelectedData'][0]['id'], '/docs\/');
            if ($pos === false) {
                $rutaFile = Yii::$app->params['rutaDocumentosOrfeo38'] ."/". $request['ButtonSelectedData'][0]['id'];
            }

            if(file_exists($rutaFile)) {
                $dataFile = base64_encode(file_get_contents($rutaFile));
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => [],
                    'data' => [],
                    'fileName' => $fileName,
                    'status' => 200,
                ];

                $return = HelperEncryptAes::encrypt($response, true);
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
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionEnviarCorreo() {
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

        $jsonSend = Yii::$app->request->getBodyParam('jsonSend');
        if (!empty($jsonSend)) {
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

            $documentosExist = [];
            foreach ($request['documents'] as $document) {
                $rutaFile = Yii::$app->params['rutaDocumentosOrfeo38'] ."". $document['documento'];
                if (file_exists($rutaFile)) {
                    $documentosExist[] = $rutaFile;
                }
            }

            $bodyMail = 'radicacion-html';
            $subject = 'submissionOfFiled';
            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($request['id']));

            $envioCorreo = CorreoController::envioAdjuntos($request['emails'], 'Envío de radicado', $request['bodyMail'], $bodyMail, $documentosExist, $dataBase64Params, $subject, '', false);

            if ($envioCorreo['status']) {
                $dataLogRadicado = ' Número radicado:' .' '. $request['id'];
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id,
                    Yii::$app->user->identity->username,
                    Yii::$app->controller->route,
                    Yii::$app->params['eventosLogText']['sendMailRadicado'] . " radicado: ". $request['id'],
                    '',
                    $dataLogRadicado,
                    array() //No validar estos campos
                );

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'confimacionEnvioCorreo'),
                    'data' => [],
                    'status' => 200
                ];
                return HelperEncryptAes::encrypt($response, true);

            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => [],
                    'status' => Yii::$app->params['statusErrorValidacion'],
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
}
