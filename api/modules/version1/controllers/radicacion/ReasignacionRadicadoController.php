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
use yii\data\ActiveDataProvider;

use yii\web\Controller;
use yii\web\UploadedFile;
use yii\helpers\FileHelper;

use yii\web\NotFoundHttpException;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;

use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperUserMenu;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperConsecutivo;

use api\models\RadiRadicados;
use api\models\RadiDocumentos;
use api\models\RadiAgendaRadicados;
use api\models\CgTransaccionesRadicados;
use api\models\CgProveedoresRegional;
use api\models\CgProveedores;
use api\models\CgEnvioServicios;
use api\models\RadiEnvios;
use api\models\RadiLogRadicados;

use api\models\CgRegionales;
use api\models\CgTiposRadicadosTransacciones;
use api\models\RadiRadicadoAnulado;
use api\modules\version1\controllers\radicacion\RadicadosController;
use api\modules\version1\controllers\correo\CorreoController;
use api\models\User;
use api\models\RadiInformados;
use api\models\RadiRemitentes;
use api\models\Clientes;

use DateTime;
use PhpOffice\PhpSpreadsheet\Shared\Date;

use api\components\HelperQueryDb;

/**
 * RadiAgendaController implements the CRUD actions for RadiAgendaRadicados model.
 */
class ReasignacionRadicadoController extends Controller
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
                    'create' => ['POST'],
                    'schedule' => ['POST'],
                    'upload-file' => ['POST'],
                    'anulacion-radicado' => ['POST'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

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
            //*** Inicio desencriptación POST ***//
            $decrypted = HelperEncryptAes::decryptGET($request, true);
            if ($decrypted['status'] == true) {
                $request = $decrypted['request'];
            } else {
                $request = '';
            }
            //*** Fin desencriptación POST ***//
        }

        //Lista de radiRadicados
        $dataList = [];
        $dataBase64Params = "";

        //Se reitera el $request y obtener la informacion retornada
        $dataWhere = [];
        if (is_array($request)) {
            foreach ($request['filterOperation'] as $field) {
                foreach ($field as $key => $info) {
                    //Se valida que los campos del filtro no esten vacios y se construye $dataWhere
                    if ($key == 'fechaInicial' || $key == 'fechaFinal') {
                        if( isset($info) && $info !== null && trim($info) !== ''){
                           $dataWhere[$key] =  $info;
                        }
                    } else {
                        if( isset($info) && $info !== null && trim($info) != '' ){
                            $dataWhere[$key] = $info;
                            //return $dataWhere;
                        }
                    }
                }
            }
        }

           
        /**
         * Esta es una manera práctica para consultar los radicados que no estén en la tabla de radicados anulados, pero aun no se puede utilizar el left join
         * Se realiza para solucionar el item "Se puede reasignar en todos los estados menos en Finalizado, Aprobar Solicitud, Rechazar  de anulación, Solicitud de anulación"
         */

        $relacionRadicados = RadiRadicados::find();
            /* ->innerJoin('gdTrdTiposDocumentales', '`gdTrdTiposDocumentales`.`idGdTrdTipoDocumental` = `radiRadicados`.`idTrdTipoDocumental`')
            ->innerJoin('gdTrdDependencias', '`gdTrdDependencias`.`idGdTrdDependencia` = `radiRadicados`.`idTrdDepeUserTramitador`')
            ->innerJoin('user', 'user.id = `radiRadicados`.`user_idTramitador`') */
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdTrdTiposDocumentales', ['radiRadicados' => 'idTrdTipoDocumental', 'gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental']);
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'gdTrdDependencias', ['gdTrdDependencias' => 'idGdTrdDependencia', 'radiRadicados' => 'idTrdDepeUserTramitador']);
            $relacionRadicados = HelperQueryDb::getQuery('innerJoin', $relacionRadicados, 'user', ['radiRadicados' => 'user_idTramitador', 'user' => 'id']);            
            $relacionRadicados = $relacionRadicados->andWhere(['NOT IN', 'radiRadicados.estadoRadiRadicado', Yii::$app->params['statusTodoText']['Finalizado']]);


        /**
         * **Gestión de correspondencia - Reasignación masiva** Agregar un permiso que se llame "Reasignación de PQRS" y con base a este permiso validar que en el initiallist solo se muestren los radicados de PQRS  y en los filtros en el listado de tipos de radicados, mostrar solamente el tipo de PQRS. SI no se tiene el permiso de "Reasignación de PQRS" no se debe hacer esta restricción.
         */
        $viewFilterIdCgTipoRadicado = true;
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsReasignOnlyPqrsd'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $relacionRadicados->andWhere(['radiRadicados.idCgTipoRadicado' => Yii::$app->params['idCgTipoRadicado']['radiPqrs'] ]);
            $viewFilterIdCgTipoRadicado = false;
        }

        //Se reitera $dataWhere para solo obtener los campos con datos
        foreach ($dataWhere as $field => $value) {

            switch ($field) {
                case 'idGdTrdDependencia': 
                    $relacionRadicados->andWhere(['IN', 'gdTrdDependencias.' . $field, $value]);
                break;
                case 'id': // Se identifica si llega el usuario
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.user_idTramitador', $value]);
                break;
                case 'idGdTrdTipoDocumental':
                    $relacionRadicados->andWhere(['IN', 'gdTrdTiposDocumentales.' . $field, $value]);
                break;
                case 'idCgTipoRadicado':
                    $relacionRadicados->andWhere(['IN', 'radiRadicados.' . $field, $value]);
                break;
                case 'fechaInicial':
                    $relacionRadicados->andWhere(['>=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeStart']]);
                break;

                case 'fechaFinal':
                    $relacionRadicados->andWhere(['<=', 'radiRadicados.creacionRadiRadicado', trim($value) . Yii::$app->params['timeEnd']]);
                break;
                case 'numeroRadiRadicado':
                    $relacionRadicados->andWhere([Yii::$app->params['like'], $field , $value]);
                break;
            }
        }

        $relacionRadicados->andWhere(['<>', 'estadoRadiRadicado', Yii::$app->params['statusTodoText']['Inactivo']]);

        //Limite de la consulta       
        $relacionRadicados->orderBy(['radiRadicados.idRadiRadicado' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
        $modelRelation = $relacionRadicados->all();

        /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
        $modelRelation = array_reverse($modelRelation);

        /** Consultar ids de radicados anulados */
        $radiRadicadoAnulado = RadiRadicadoAnulado::find()->select(['idRadicado'])->groupBy(['idRadicado'])->all();
        $arrayRadiRadicadoAnulado = [];
        foreach ($radiRadicadoAnulado as $value) {
            $arrayRadiRadicadoAnulado[] = $value['idRadicado'];
        }

        foreach ($modelRelation as $infoRelation) {

            /** Permitir solo radicados que no estén en proceso de anulación */
            if (!in_array($infoRelation->idRadiRadicado, $arrayRadiRadicadoAnulado)) {

                $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $infoRelation->idRadiRadicado]);  
            
                # Si hay más de un registro mostrará el texto de múltiple sino muestra el nombre del remitente
                if(count($remitentes) > 1) {
                    $nombreRemitente = 'Múltiples Remitentes/Destinatarios';

                } else {
                    foreach($remitentes as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            # Se obtiene la información del cliente
                            $modelCliente = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);       
                            
                            if($modelCliente){
                                $nombreRemitente = $modelCliente->nombreCliente;
                            }else{
                                $nombreRemitente = '';
                            }
                
                        } else {
                            # Se obtiene la información del usuario o funcionario
                            $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);                       
                            $nombreRemitente = $modelUser->userDetalles->nombreUserDetalles.' '.$modelUser->userDetalles->apellidoUserDetalles;               
                        }  
                    }
                }

                # Fecha de vencimiento
                $expiredDate = date('Y-m-d', strtotime($infoRelation->fechaVencimientoRadiRadicados)); 

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->idRadiRadicado)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($infoRelation->numeroRadiRadicado)),
                );

                //Se obtiene la traza del estado actual del radicado
                $modelLogRadicados = RadiLogRadicados::find()
                    ->where(['idRadiRadicado' => $infoRelation->idRadiRadicado])
                    ->orderBy(['fechaRadiLogRadicado' => SORT_DESC])
                    ->one();

                $transaccion = '';
                if(!empty($modelLogRadicados)){
                    $transaccion = $modelLogRadicados->transaccion->titleCgTransaccionRadicado;
                }

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $infoRelation->idRadiRadicado,
                    'dependenciaTramitador' => $infoRelation->userIdTramitador->gdTrdDependencia->nombreGdTrdDependencia,
                    'userTramitador' => $infoRelation->userIdTramitador->username,
                    'TipoRadicado' => $infoRelation->cgTipoRadicado->nombreCgTipoRadicado,
                    'numeroRadiRadicado' => HelperConsecutivo::numeroRadicadoXTipoRadicado($infoRelation->numeroRadiRadicado, $infoRelation->idCgTipoRadicado,$infoRelation->isRadicado),
                    'creacionRadiRadicado' => $infoRelation->creacionRadiRadicado,
                    'asuntoRadiRadicado' => (strlen($infoRelation->asuntoRadiRadicado) > 150) ? substr($infoRelation->asuntoRadiRadicado, 0, 150) . '...' : $infoRelation->asuntoRadiRadicado,
                    'nombreCliente' => $nombreRemitente,
                    'nombreTipoDocumental' => $infoRelation->trdTipoDocumental->nombreTipoDocumental,
                    'fechaVencimientoRadiRadicados' => $expiredDate,
                    'usuarioTramitador' => $infoRelation->userIdTramitador->userDetalles->nombreUserDetalles.' '.$infoRelation->userIdTramitador->userDetalles->apellidoUserDetalles ,
                    'statusText' => Yii::t('app', $transaccion) . ' - ' .  Yii::t('app', 'statusTodoNumber')[$infoRelation->estadoRadiRadicado],
                    'status' => $infoRelation->estadoRadiRadicado,
                    'rowSelect' => false,
                    'idInitialList' => 0,
                );

            }

        }

        // Validar que el formulario exista
        $formType = HelperDynamicForms::setListadoBD('indexReasignacionRadicado');

        $fieldGroup = $formType['schema'][0]['fieldArray']['fieldGroup'];
        $fieldGroupNew = [];
        foreach ($fieldGroup as $value) {
            if ($viewFilterIdCgTipoRadicado == true || ($viewFilterIdCgTipoRadicado == false && $value['key'] != 'idCgTipoRadicado')) {
                $fieldGroupNew[] = $value;
            }
        }
        $formType['schema'][0]['fieldArray']['fieldGroup'] = $fieldGroupNew;

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
