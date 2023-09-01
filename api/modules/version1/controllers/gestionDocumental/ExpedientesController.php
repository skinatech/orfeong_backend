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

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

use api\models\GdTrdDependencias;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdHistoricoExpedientes;
use api\models\GdExpedienteDocumentos;
use api\models\User;
use api\models\GdTrd;
use api\models\GdIndices;
use api\models\GaPrestamos;
use api\models\RolesOperaciones;
use api\models\RolesTiposOperaciones;
use api\models\GdReferenciasCruzadas;
use api\models\GdTiposAnexosFisicos;
use api\models\GdReferenciaTiposAnexos;
use api\models\RadiInformados;

use api\components\HelperConsecutivo;
use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperLog;
use api\components\HelperDynamicForms;
use api\components\HelperEncrypt;
use api\components\HelperLoads;
use api\components\HelperGenerateExcel;
use api\components\HelperQueryDb;
use api\components\HelperRadicacion;
use api\components\HelperExpedient;
use api\components\HelperIndiceElectronico;
use api\models\CgTransaccionesRadicados;
use api\models\GaArchivo;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\GdTrdTiposDocumentales;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\RadiLogRadicados;
use api\models\RadiRadicados;
use api\models\UserDetalles;
use api\models\GdExpedientesDependencias;
use api\models\CgGeneral;
use api\models\RadiRemitentes;
use common\models\User as UserValidate;  // Validación password
use api\modules\version1\controllers\gestionArchivo\GestionArchivoController;
use yii\helpers\FileHelper;
use api\modules\version1\controllers\pdf\PdfController;
use kartik\mpdf\Pdf;

class ExpedientesController extends Controller
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
                    'index'  => ['GET'],
                    'index-one'  => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'get-gd-tipos-anexos-fisicos' => ['GET'],
                    'cross-reference' => ['POST'],
                    'update'  => ['PUT'],
                    'change-status'  => ['PUT'],
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
     * Lists all Expedientes models.
     * @return mixed
     */
    public function actionIndex($request) {
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
                        } elseif ($key == 'existeFisicamenteGdExpediente') {
                            if ($info !== null) {
                                $dataWhere[$key] = $info;
                            }
                        } else {
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            # Relacionamiento de los expedientes
            $relationFiles = GdExpedientes::find();
                //->innerJoin('userDetalles', '`userDetalles`.`idUser` = `gdExpedientes`.`idUser`');
                $relationFiles = HelperQueryDb::getQuery('innerJoin', $relationFiles, 'userDetalles', ['userDetalles' => 'idUser', 'gdExpedientes' => 'idUser']);
            $relationFiles = $relationFiles;

            # Se eliminan los filtros por nivel de consulta anteriores, en este módulo el nivel de consulta depende del tipo de usuario
            # Si el tipo de usuario no es gestión documental solo puede ver los expedientes de los que es dueño
            if(Yii::$app->user->identity->idUserTipo != Yii::$app->params['tipoUsuario']['Administrador de Gestión Documental']) {
                $dataWhere['idUser'] = Yii::$app->user->identity->id;
            }

            if ($request !== "") {
                $dependenciaUserIdentity = Yii::$app->user->identity->idGdTrdDependencia;
                $modelGdExpedientesDependencias = GdExpedientesDependencias::find()
                    ->where(['idGdTrdDependencia' => $dependenciaUserIdentity])
                    ->all();
                $expedientesPependenciaUserIdentity = [];
                foreach ($modelGdExpedientesDependencias as $key => $modelGdExpedientesDependencia) {
                    $expedientesPependenciaUserIdentity[] = $modelGdExpedientesDependencia->idGdExpediente;
                }

                $relationFiles->where(['OR',
                    ['gdExpedientes.idUser' => Yii::$app->user->identity->id],
                    ['gdExpedientes.idGdTrdDependencia' => $dependenciaUserIdentity],
                    ['IN', 'gdExpedientes.idGdExpediente', $expedientesPependenciaUserIdentity]]);
            }

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {
                switch ($field) {
                    case 'idUser':
                        if ($request === "") {
                            $relationFiles->andWhere(['userDetalles.idUser' => $value]);
                        }
                    break;
                    case 'fechaInicial':
                        # Actualmente la fecha es un campo de tipo string en formato date(Y-m-d).. para poderla ordenar alfabéticamente (esto por problemas anteriores con Oracle)
                        $relationFiles->andWhere(['>=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value)]);
                    break;
                    case 'fechaFinal':
                        # Actualmente la fecha es un campo de tipo string en formato date(Y-m-d).. para poderla ordenar alfabéticamente (esto por problemas anteriores con Oracle)
                        $relationFiles->andWhere(['<=', 'gdExpedientes.fechaProcesoGdExpediente', trim($value)]);
                    break;
                    case 'idGdTrdSerie':
                    case 'idGdTrdSubserie':
                    case 'idGdTrdDependencia':
                        $relationFiles->andWhere(['IN', 'gdExpedientes.' . $field, $value]);
                    break;
                    case 'status':
                        $relationFiles->andWhere(['IN', 'gdExpedientes.estadoGdExpediente', $value]);
                    break;
                    case 'existeFisicamenteGdExpediente':
                        $relationFiles->andWhere(['gdExpedientes.' . $field => $value]);
                    break;
                    default:
                        $relationFiles->andWhere([Yii::$app->params['like'], 'gdExpedientes.' . $field, $value ]);
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

            /* Validar si el usuario posee permiso de referencia cruzada */
            $havePermissionCrossReference = false;
            $modelOperation = RolesOperaciones::findOne(['nombreRolOperacion' => 'version1%gestionDocumental%expedientes%cross-reference']);
            if(!is_null($modelOperation)){
                $modelTypeOperation = RolesTiposOperaciones::findOne(['idRolOperacion' => $modelOperation->idRolOperacion, 'idRol' => Yii::$app->user->identity->idRol]);
                if (!is_null($modelTypeOperation)) {
                    $havePermissionCrossReference = true;
                }
            }
            /* Fin Validar si el usuario posee permiso de referencia cruzada */

            $modelCgGeneral = CgGeneral::find()
                ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();

            foreach ($modelRelation as $expediente) {
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($expediente->idGdExpediente)),
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($expediente->numeroGdExpediente))
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
                    'userCreadorId'     => $expediente->idUser,
                    'userCreador'       => $expediente->user->userDetalles->nombreUserDetalles . ' ' . $expediente->user->userDetalles->apellidoUserDetalles,
                    'statusText'        => Yii::$app->params['statusExpedienteNumber'][$expediente->estadoGdExpediente],
                    'status'            => $expediente->estadoGdExpediente,
                    'idGdTrdDependencia' => $expediente->idGdTrdDependencia,
                    'idGdTrdSerie'      => $expediente->gdTrdSerie->idGdTrdSerie,
                    'idGdTrdSubserie'   => $expediente->gdTrdSubserie->idGdTrdSubserie,
                    'havePermissionCrossReference' => ($havePermissionCrossReference == true && $expediente->estadoGdExpediente != Yii::$app->params['statusExpedienteText']['Cerrado']),
                    'activarPazYsalvo' => ($modelCgGeneral->codDepePrestaEconomicasCgGeneral === $expediente->gdTrdDependencia->codigoGdTrdDependencia) ? true : false,
                    'rowSelect'         => false,
                    'idInitialList'     => 0,
                );
            }

            // Validar que el formulario exista
            $formType = HelperDynamicForms::setListadoBD('indexExpediente');

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
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    public function actionIndexOne($request){
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
        $model = $this->findModel($id);

        $modelGdExpedientesDependencias = GdExpedientesDependencias::find()
            ->where(['idGdExpediente' => $id])
            ->all();
        $gdExpedientesDependencias = [];
        foreach ($modelGdExpedientesDependencias as $key => $modelGdExpedientesDependencia) {
            $gdExpedientesDependencias[] = $modelGdExpedientesDependencia->idGdTrdDependencia;
        }

        $modelDependencia = GdTrdDependencias::find()
            ->where(['<>', 'idGdTrdDependencia', Yii::$app->user->identity->idGdTrdDependencia])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();
        $data = [];
        if(!is_null($modelDependencia)) {
            foreach($modelDependencia as $key => $dependencia) {
                $listService['gdExpedientesDependencias'][] = [
                    'id' => $dependencia->idGdTrdDependencia,
                    'val' => $dependencia->nombreGdTrdDependencia
                ];
            }
        }

        $data = [
            'numeroGdExpediente' => $model->numeroGdExpediente,
            'nombreGdExpediente' => $model->nombreGdExpediente,
            'idUser' => $model->idUser,
            'idGdTrdDependencia' => $model->idGdTrdDependencia,
            'idGdTrdSerie' => $model->idGdTrdSerie,
            'idGdTrdSubserie' => $model->idGdTrdSubserie,
            'estadoGdExpediente' => $model->estadoGdExpediente,
            'creacionGdExpediente' => $model->creacionGdExpediente,
            'fechaProcesoGdExpediente' => $model->fechaProcesoGdExpediente.'T05:00:00.000Z',
            'descripcionGdExpediente' => $model->descripcionGdExpediente,
            'existeFisicamenteGdExpediente' => (boolean) $model->existeFisicamenteGdExpediente,
            'idGdExpedientesDependencias' => $gdExpedientesDependencias
        ];

        /** Procesar listados a utilizar en el frontend */
        $subserie = GdTrdSeries::find()->select(['nombreGdTrdSerie'])->where(['idGdTrdSerie' => $model->idGdTrdSerie])->one();
        $listService['listSeries'] = [
            ['id' => $model->idGdTrdSerie, 'val' => $subserie->nombreGdTrdSerie]
        ];

        $subserie = GdTrdSubseries::find()->select(['nombreGdTrdSubserie'])->where(['idGdTrdSubserie' => $model->idGdTrdSubserie])->one();
        $listService['listSubseries'] = [
            ['id' => $model->idGdTrdSubserie, 'val' => $subserie->nombreGdTrdSubserie]
        ];

        $dependencia = GdTrdDependencias::find()->select(['nombreGdTrdDependencia'])->where(['idGdTrdDependencia' => $model->idGdTrdDependencia])->one();
        $listService['listDependencias'] = [
            ['id' => $model->idGdTrdDependencia, 'val' => $dependencia->nombreGdTrdDependencia]
        ];

        $usuario = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => $model->idUser])->one();
        $listService['listUsuarios'] = [
            ['id' => $model->idUser, 'val' => $usuario->nombreUserDetalles.' '.$usuario->apellidoUserDetalles]
        ];
        /** Fin Procesar listados a utilizar en el frontend */

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'listService' => $listService,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    //sguarin aca es donde se hace la consulta de los expedientes cuando voy a mandar un radicado a un expediente
    public function actionIndexList($request){
        
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

        $idUser = Yii::$app->user->identity->id;

        //$idDependencia = self::findModelUser($idUser)->gdTrdDependencia->idGdTrdDependencia;
        $idDependencia = Yii::$app->user->identity->idGdTrdDependencia; 

        /** Se busca el codigo de la dependencia de la trd activa, para obtener todos los id de las dependencias **/
        $codigoGdTrdDependencia = Yii::$app->user->identity->gdTrdDependencia->codigoGdTrdDependencia;
        $modelDepe = gdTrdDependencias::find()->select('idGdTrdDependencia')->where(['codigoGdTrdDependencia' => $codigoGdTrdDependencia])->all();
        
        //luego se recorre cada una de esas dependencias para asignarlo aun arreglo y luego poder pasar a consulta de expedientes
        foreach($modelDepe as $key => $depe){
            $dataDependencias[] = $depe['idGdTrdDependencia'];
        }
        $modelCodDepe = implode(' , ', $dataDependencias);

        $tablaArchivo =  GaArchivo::tableName() . ' AS AR';
        $tablaExpediente = GdExpedientes::tableName() . ' AS EX';
        $tablaExpedientesDependencias = GdExpedientesDependencias::tableName() . ' AS EXDP';

        $modelExpedientes = (new \yii\db\Query())
            ->from($tablaExpediente);
            
            $modelExpedientes = HelperQueryDb::getQuery('leftJoinAlias', $modelExpedientes, $tablaArchivo, ['EX' => 'idGdExpediente', 'AR' => 'idGdExpediente']);
            $modelExpedientes = HelperQueryDb::getQuery('leftJoinAlias', $modelExpedientes, $tablaExpedientesDependencias, ['EX' => 'idGdExpediente', 'EXDP' => 'idGdExpediente'])
                ->select(['EX.idGdExpediente','EX.nombreGdExpediente','EX.numeroGdExpediente', 'AR.idGaEdificio'])
                ->where(['EX.estadoGdExpediente' => Yii::$app->params['statusTodoText']['Activo']])
                ->orWhere(['EX.estadoGdExpediente' => Yii::$app->params['statusExpedienteText']['PendienteCerrar']])
                // ->andWhere(['EX.idGdTrdDependencia' => $idDependencia])
                // ->orWhere(['EXDP.idGdTrdDependencia' => $idDependencia]);
                ->andWhere('EX.idGdTrdDependencia IN ('.$modelCodDepe.')')
                ->orWhere('EXDP.idGdTrdDependencia IN ('.$modelCodDepe.')');
        
        if (isset($request['nombreExpediente']) && $request['nombreExpediente'] != '' ) {
            $modelExpedientes->andWhere([Yii::$app->params['like'], 'EX.nombreGdExpediente', $request['nombreExpediente']]);
        }

        if(isset($request['numeroExpediente']) && $request['numeroExpediente'] != '' ){
            $modelExpedientes->andWhere([Yii::$app->params['like'], 'EX.numeroGdExpediente', $request['numeroExpediente']]);
        }

        // return $modelExpedientes->createCommand()->getRawSql();
        $modelExpedientes = $modelExpedientes->all();
        $data = [];
        
        
        foreach($modelExpedientes as $key=>$expedienteItem){

            $data[] = array(
                'idGdExpediente' => $expedienteItem['idGdExpediente'],
                'nombreGdExpediente' => $expedienteItem['nombreGdExpediente'],
                'numeroGdExpediente' => $expedienteItem['numeroGdExpediente'],
                'archivado' => $expedienteItem['idGaEdificio']
            );
        }
        //sguarin esta data es cuando se da click en la lupa y la que necesito para hacer un condicional 
        // return $data;

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /* Muestra la dependencia del usuario logueado */
    public function actionDependenciasList() {

        $modelDependencia = GdTrdDependencias::find()
            ->where(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();
        $data = [];

        if(!is_null($modelDependencia)) {
            $data[] = [
                'id' => $modelDependencia->idGdTrdDependencia,
                'val' => $modelDependencia->nombreGdTrdDependencia
            ];
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionDependenciasListAllUpdate($request) {
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

        $modelDependencia = GdTrdDependencias::find()
            ->where(['<>', 'idGdTrdDependencia', $request['idGdTrdDependencia']])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        $data = [];
        if(!is_null($modelDependencia)) {
            foreach($modelDependencia as $key => $dependencia) {
                $data[] = [
                    'id' => $dependencia->idGdTrdDependencia,
                    'val' => $dependencia->codigoGdTrdDependencia ." ". $dependencia->nombreGdTrdDependencia
                ];
            }
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionDependenciasListAll() {
        $modelDependencia = GdTrdDependencias::find()
            ->where(['<>', 'idGdTrdDependencia', Yii::$app->user->identity->idGdTrdDependencia])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->all();

        $data = [];
        if(!is_null($modelDependencia)) {
            foreach($modelDependencia as $key => $dependencia) {
                $data[] = [
                    'id' => $dependencia->idGdTrdDependencia,
                    'val' => $dependencia->codigoGdTrdDependencia ." ". $dependencia->nombreGdTrdDependencia
                ];
            }
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionSeriesList(){

        $modelDependencia = GdTrdDependencias::find()
            ->where(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
        ->one();
        

        $modelTrd = GdTrd::find()
            ->where(['idGdTrdDependencia' => $modelDependencia->idGdTrdDependencia])
            ->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();

        # Si la consulta no tiene configurada series para la dependencia del logueado, muestra un mensaje de alerta.
        if(count($modelTrd) == 0) {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'noConfigTrd')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $data = [];
        $idsSerie = [];
        foreach($modelTrd as $trd){

            //Solo incluir una serie ya que se repiten en las trd
            if(!in_array($trd->idGdTrdSerie,  $idsSerie)){
                $data[] = [
                    'id' =>  $trd->idGdTrdSerie,
                    'val' => $trd->gdTrdSerie->nombreGdTrdSerie
                ];

                $idsSerie[] =  $trd->idGdTrdSerie;
            }           
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    public function actionSubseriesList($request){

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

        $idSerie = $request['id'];

        $modelUser = self::findModelUser(Yii::$app->user->identity->id);

        $modelDependencia = GdTrdDependencias::find()
            ->where(['idGdTrdDependencia' => $modelUser->idGdTrdDependencia])
            ->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
            ->one()
        ;

        $modelTrd = GdTrd::find()
            ->where(['idGdTrdDependencia' => $modelDependencia->idGdTrdDependencia])
            ->andWhere(['idGdTrdSerie' => $idSerie])
            ->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
            ->all()
        ;

        # Si la consulta no tiene configurada subseries para la dependencia del logueado, muestra un mensaje de alerta.
        if(count($modelTrd) == 0) {

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'noConfigTrd')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }

        $data = [];
        $idsSubseries = [];
        foreach($modelTrd as $trd){

            //Solo incluir una subSerie ya que se repiten en las trd
            if(!in_array($trd->idGdTrdSubserie, $idsSubseries)){

                $data[] = [
                    'id' =>  $trd->idGdTrdSubserie,
                    'val' => $trd->gdTrdSubserie->nombreGdTrdSubserie
                ];

                $idsSubseries[] =  $trd->idGdTrdSubserie;
            }
        }
        
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /* Listado de funcionarios dependiendo de la dependencia del usuario logueado */
    public function actionFuncionariosList(){

        $modelUser = User::findAll(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);

        $data = [];
        foreach($modelUser as $dataUser){
            if($dataUser->idUserTipo != Yii::$app->params['tipoUsuario']['Externo']){
                $data[] = [
                    'id' => $dataUser->id,
                    'val' => $dataUser->userDetalles->nombreUserDetalles. ' ' . $dataUser->userDetalles->apellidoUserDetalles
                ];
            }            
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    public function actionView($request) {
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
            $dataHistorico = [];
            $dataDocumenentos = [];
            $dataDepen = []; // Variable que se utiliza para que se vean los tipos documentales
            $dataExpArchivo = [];

            #Consulta datos de expediente
            $expediente = self::findModel($id);

            $modelUserDetalle = UserDetalles::find()
                ->where(['idUser' => $expediente->user->id])
                ->one();

            $user = $modelUserDetalle->nombreUserDetalles . ' ' . $modelUserDetalle->apellidoUserDetalles;

            # Consulta para obtener el ultimo prestamo realizado.
            $ultimoPrestamo = '---';

            $modelPrestamo = GaPrestamos::find()
                ->where(['idGdExpedienteInclusion' => $expediente->idGdExpediente])
                ->orderBy(['fechaSolicitudGaPrestamo' => SORT_DESC])
            ->one();

            if(!empty($modelPrestamo)){
                $ultimoPrestamo = $modelPrestamo->fechaSolicitudGaPrestamo;
            }

            # INFORMACIÓN DEL EXPEDIENTE
            $soporte = '';
            $retencion = '';
            $disposicion = '';

            # Información del Soporte TRD
            if($expediente->gdTrdSubserie->pSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
            $soporte .= Yii::$app->params['soporteP'].", ";

            if ($expediente->gdTrdSubserie->eSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
                $soporte .= Yii::$app->params['soporteE'].", ";

            if ($expediente->gdTrdSubserie->oSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
                $soporte .= Yii::$app->params['soporteO'];

            # Información de la disposición TRD
            if($expediente->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
                $disposicion .= Yii::$app->params['disposicionCt'].", ";

            if ($expediente->gdTrdSubserie->eDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
                $disposicion .= Yii::$app->params['disposicionE'].", ";

            if ($expediente->gdTrdSubserie->sDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
                $disposicion .= Yii::$app->params['disposicionS'].", ";

            if ($expediente->gdTrdSubserie->mDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
                $disposicion .= Yii::$app->params['disposicionM'];

            # Información de la retención TRD
            $retencion = 'Archivo Gestión: '.$expediente->gdTrdSubserie->tiempoGestionGdTrdSubserie.
                    ' - Archivo Central: '.$expediente->gdTrdSubserie->tiempoCentralGdTrdSubserie;

            // Asigna la información
            $dataDepen['idGdTrdDependencia'] = $expediente->idGdTrdDependencia;
            $dataDepen['idGdTrdSerie'] = $expediente->idGdTrdSerie;
            $dataDepen['idGdTrdSubserie'] = $expediente->idGdTrdSubserie;

            $modelGdExpedientesDependencias = GdExpedientesDependencias::find()
                ->where(['idGdExpediente' => $id])
                ->all();
            $gdExpedientesDependencias = [];
            foreach ($modelGdExpedientesDependencias as $key => $modelGdExpedientesDependencia) {
                $gdExpedientesDependencias[] = $modelGdExpedientesDependencia->idGdTrdDependencia0->nombreGdTrdDependencia;
            }

            if($dataList == false){
                $dataList = [
                    ['alias' => 'Código', 'value' => $expediente->numeroGdExpediente],
                    ['alias' => 'Nombre', 'value' => $expediente->nombreGdExpediente],
                    ['alias' => 'Descripción', 'value' => $expediente->descripcionGdExpediente],
                    ['alias' => 'Dependencia', 'value' => $expediente->gdTrdDependencia->nombreGdTrdDependencia],
                    ['alias' => 'Serie', 'value' => $expediente->gdTrdSerie->codigoGdTrdSerie.' - '.$expediente->gdTrdSerie->nombreGdTrdSerie], 
                    ['alias' => 'Subserie', 'value' => $expediente->gdTrdSubserie->codigoGdTrdSubserie.' - '.$expediente->gdTrdSubserie->nombreGdTrdSubserie],
                    ['alias' => 'Soporte', 'value' => $soporte],
                    ['alias' => 'Disposición Final', 'value' => $disposicion],
                    ['alias' => 'Años en Retención', 'value' => $retencion],
                    ['alias' => 'Fecha inicio del expediente', 'value' => $expediente->fechaProcesoGdExpediente],
                    ['alias' => 'Fecha de creación', 'value' => $expediente->creacionGdExpediente],
                    ['alias' => 'Responsable', 'value' =>  $user],
                    ['alias' => 'Estado', 'value' => Yii::t('app', 'statusExpedienteNumber')[$expediente->estadoGdExpediente]],
                    ['alias' => 'Ubicación', 'value' =>  Yii::$app->params['ubicacionTransferenciaTRD'][$expediente->ubicacionGdExpediente]],
                    ['alias' => 'Último préstamo', 'value' => $ultimoPrestamo],
                    ['alias' => '¿Existe físicamente?', 'value' => ((boolean) $expediente->existeFisicamenteGdExpediente == true) ? 'Sí, es un expediente híbrido' : 'No'],
                    ['alias' => 'Dependencias que hacen parte de la gestión del expediente', 'value' => implode(", ", $gdExpedientesDependencias)],
                ];
            }
            # TRAZABILIDAD DEL EXPEDIENTE
            $modelHistoricoExpediente = GdHistoricoExpedientes::find()
                ->where(['idGdExpediente' => $expediente->idGdExpediente])
                ->orderBy(['creacionGdHistoricoExpediente' => SORT_DESC])
            ->all();

            foreach($modelHistoricoExpediente as $historicoExpediente){
                $userAction = $historicoExpediente->userHistoricoExpediente->userDetalles->nombreUserDetalles.' '.$historicoExpediente->userHistoricoExpediente->userDetalles->apellidoUserDetalles;
                $dataHistorico[] = [
                    //'usuario' => $user,
                    'usuario' => $userAction,
                    'dependencia' => $historicoExpediente->gdTrdDependencia->nombreGdTrdDependencia,
                    'operacion' =>  Yii::$app->params['operacionExpedienteNumber'][$historicoExpediente->operacionGdHistoricoExpediente],
                    'observacion' => $historicoExpediente->observacionGdHistoricoExpediente,
                    'fecha' => $historicoExpediente->creacionGdHistoricoExpediente
                ];
            }

            # DATOS ARCHIVO DEL EXPEDIENTE
            $dataExpArchivo =  GestionArchivoController::detallesArchivo($request); 


            # DATOS DE LA TRD CON LA DEPENDENCIA DEL EXPEDIENTE
            $dataTrd = GdTrd::find()->where(['idGdTrdDependencia' => $expediente->gdTrdDependencia->idGdTrdDependencia])->one();
            if($dataTrd){
                $versionTrd = $dataTrd->versionGdTrd;
            }else{
                $versionTrd = 'Sin versión';
            }

            if($expediente->estadoGdExpediente == Yii::$app->params['statusExpedienteText']['PendienteCerrar']){
                $notificacion[] =  [
                    'message' => Yii::t('app', 'messagePendingClose',[
                        'numFile' => $expediente->numeroGdExpediente,
                        'numVersion' => $versionTrd,
                    ]),
                    'type' => 'danger'
                ];
            }

            /***    Log de Auditoria  ***/
            HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'] . "id: " . $expediente->idGdExpediente . " del expediente: " . $expediente->nombreGdExpediente. ", del usuario: ".$user,//texto para almacenar en el evento
                [],
                [], //Data
                array() //No validar estos campos
            );                  
            /***    Fin log Auditoria   ***/

            // Se consulta si el usuario del expediente es el mismo que inicio sesión
            $havePermissionUpdate = false;
            if($expediente->idUser == Yii::$app->user->identity->id){
                $havePermissionUpdate = true;
            }

            $havePermissionCrossReference = false; // Permiso de referencia cruzada

            /* Validar si el usuario posee permiso de referencia cruzada */
            $modelOperation = RolesOperaciones::findOne(['nombreRolOperacion' => 'version1%gestionDocumental%expedientes%cross-reference']);
            if(!is_null($modelOperation)){
                $modelTypeOperation = RolesTiposOperaciones::findOne(['idRolOperacion' => $modelOperation->idRolOperacion, 'idRol' => Yii::$app->user->identity->idRol]);
                if (!is_null($modelTypeOperation)) {
                    $havePermissionCrossReference = true;
                }
            }
            /* Fin Validar si el usuario posee permiso de referencia cruzada */

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'statusExpedient' => $expediente->estadoGdExpediente,
                'existeFisicamenteGdExpediente' => (boolean) $expediente->existeFisicamenteGdExpediente,
                'havePermissionCrossReference' => $havePermissionCrossReference,
                'havePermissionUpdate' => $havePermissionUpdate,
                'dataHistorico' => $dataHistorico,
                'dataDocumetos' => $dataDocumenentos,
                'dataDepen' => $dataDepen,
                'dataExpArchivo' => $dataExpArchivo,
                'status' => 200,
                'notificacion' => $notificacion ?? [],
            ];
            return HelperEncryptAes::encrypt($response, true);

        }else{
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
     * action datos ubicacion del archivo
     * @param integer $idRadiRadicado
     * @return mixed
     */
    public function  viewLocation($idExpediente, $ultimoPrestamo)
    {   

        # Consulta en RadiRadicados
        $data = [];

        $model = GdExpedientesInclusion::find();
        $model = HelperQueryDb::getQuery('innerJoin', $model, 'gaArchivo', ['gaArchivo' => 'idGdExpediente', 'gdExpedientesInclusion' => 'idGdExpediente']);
        $model = $model->where(['gdExpedientesInclusion.idGdExpediente' => $idExpediente])->one();
        
        # si no se encuentra en archivo lo busca en expediente inclusion
        if(empty($model)){
            $model = GdExpedientesInclusion::find()->where(['idGdExpediente' => $idExpediente])->one();
        }

        # si no esta en expediente inclusion se retorna para agregar los campos solo del expediente
        if(empty($model)){
           return false;
        }

        $radiArchivo = GaArchivo::find()->where([
            'idGdExpediente' => $model['idGdExpediente'], 
            'estadoGaArchivo' => Yii::$app->params['statusTodoText']['Activo']])
        ->one();

        # formato fechas //Solo necesita el formato
        $creacionGdExp = HelperRadicacion::getFormatosFecha($model->gdExpediente['creacionGdExpediente']);
        $procesoGdExp = HelperRadicacion::getFormatosFecha($model->gdExpediente['fechaProcesoGdExpediente']);

        if(isset($radiArchivo)){
            $creacionGaArchivo = HelperRadicacion::getFormatosFecha($radiArchivo['creacionGaArchivo']);
        }

        $modelTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'archiveFiling']);
        $archivadoLogRadicados = RadiLogRadicados::findOne(['idRadiRadicado' => $model->idRadiRadicado, 'idTransaccion' => $modelTransacion->idCgTransaccionRadicado]);

        # INFORMACIÓN DE LA SUBSERIE TRD
        $soporte = '';
        $retencion = '';
        $disposicion = '';

        # sesInformación del Soporte TRD
        if($model->gdExpediente->gdTrdSubserie->pSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
            $soporte .= Yii::$app->params['soporteP'].", ";

        if ($model->gdExpediente->gdTrdSubserie->eSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
            $soporte .= Yii::$app->params['soporteE'].", ";
        
        if ($model->gdExpediente->gdTrdSubserie->oSoporteGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
            $soporte .= Yii::$app->params['soporteO'];
        
        # Información de la disposición TRD
        if($model->gdExpediente->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
            $disposicion .= Yii::$app->params['disposicionCt'].", ";
        
        if ($model->gdExpediente->gdTrdSubserie->eDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo'])
            $disposicion .= Yii::$app->params['disposicionE'].", ";
        
        if ($model->gdExpediente->gdTrdSubserie->sDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
            $disposicion .= Yii::$app->params['disposicionS'].", ";

        if ($model->gdExpediente->gdTrdSubserie->mDisposicionFinalGdTrdSubserie == Yii::$app->params['statusTodoText']['Activo']) 
            $disposicion .= Yii::$app->params['disposicionM'];

        # Información de la retención TRD
        $retencion = 'Archivo Gestión: '.$model->gdExpediente->gdTrdSubserie->tiempoGestionGdTrdSubserie.
                    ' - Archivo Central: '.$model->gdExpediente->gdTrdSubserie->tiempoCentralGdTrdSubserie;


        # INFORMACIÓN DEL EXPEDIENTE  
        $data[] = array('alias' => 'Nombre',                'value' => $model->gdExpediente->nombreGdExpediente);
        $data[] = array('alias' => 'Código',                'value' => $model->gdExpediente->numeroGdExpediente);
        $data[] = array('alias' => 'Descripción',           'value' => $model->gdExpediente->descripcionGdExpediente);
        $data[] = array('alias' => 'Dependencia',           'value' => $model->gdExpediente->gdTrdDependencia->nombreGdTrdDependencia);
        $data[] = array('alias' => 'Serie',                 'value' => $model->gdExpediente->gdTrdSerie->codigoGdTrdSerie.' - '.$model->gdExpediente->gdTrdSerie->nombreGdTrdSerie);
        $data[] = array('alias' => 'Subserie',              'value' => $model->gdExpediente->gdTrdSubserie->codigoGdTrdSubserie.' - '.$model->gdExpediente->gdTrdSubserie->nombreGdTrdSubserie);
        $data[] = array('alias' => 'Soporte',               'value' => $soporte);
        $data[] = array('alias' => 'Disposición Final',     'value' => $disposicion);
        $data[] = array('alias' => 'Años en Retención',     'value' => $retencion);        
        $data[] = array('alias' => 'Fecha creación',        'value' => $creacionGdExp['formatoFrontend']);
        $data[] = array('alias' => 'Fecha inicio del expediente', 'value' => $procesoGdExp['formatoFrontend']);
            

        # INFORMACIÓN DEL ESPACIO FISICO 
        if(isset($archivadoLogRadicados)){
            $data[] = array('alias' => 'Responsable', 'value' => $archivadoLogRadicados->user->userDetalles->nombreUserDetalles.' '.$archivadoLogRadicados->user->userDetalles->apellidoUserDetalles);
        }

        $data[] = array('alias' => 'Estado',          'value' => Yii::$app->params['statusExpedienteNumber'][$model->gdExpediente->estadoGdExpediente]);
        $data[] = array('alias' => 'Ubicación',       'value' => Yii::$app->params['ubicacionTransferenciaTRD'][$model->gdExpediente->ubicacionGdExpediente]);
        $data[] = array('alias' => 'Último préstamo', 'value' => $ultimoPrestamo);

        if(isset($radiArchivo)){ 
            $data[] = array('alias' => 'Edificio',               'value' => $radiArchivo->gaEdificio->nombreGaEdificio);
            $data[] = array('alias' => 'Piso',                   'value' => $radiArchivo->gaPiso->numeroGaPiso);
            $data[] = array('alias' => 'Area de archivo',        'value' => $radiArchivo->gaBodega->nombreGaBodega);
            $data[] = array('alias' => 'Módulo',                 'value' => $radiArchivo->rackGaArchivo);
            $data[] = array('alias' => 'Entrepaño',              'value' => $radiArchivo->entrepanoGaArchivo);
            $data[] = array('alias' => 'Caja',                   'value' => $radiArchivo->cajaGaArchivo);
            $data[] = array('alias' => 'Cuerpo',                 'value' => $radiArchivo->cuerpoGaArchivo);
            $data[] = array('alias' => 'Unidad de conservación', 'value' => Yii::$app->params['unidadConservacionGaArchivoNumber'][$radiArchivo['unidadConservacionGaArchivo']]);
            $data[] = array('alias' => 'Número de conservación', 'value' => $radiArchivo->unidadCampoGaArchivo);
            $data[] = array('alias' => 'Fecha creación',         'value' => $creacionGaArchivo['formatoFrontend']);
        }

        return $data;

    }

    /**
     * Lists all Documents folder models.
     * @return mixed
     */
    public function actionIndexExpDocumentos($request)
    {
        // if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

            # Id expediente
            $id = $request['id'];

            //Lista de expedientes
            $dataList = []; 
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            #Consulta datos de expediente
            $expediente = self::findModel($id);
                
            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
           
            if (is_array($request)) {
                
                if( array_key_exists( 'filterOperation', $request)) {
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
            }

            # Variable que permite condicional al nùmero radicado cuando esta vacio.
            $valor = '';
            $validacion = true;

            # Variable que permite condicional la descripcion cuando pertenezca a un documento principal.
            $descripcionPrinc = '';
            $validDescrip = true;
            
            # Documentos individuales (manuales)
            $modelExpedienteDocumentos = GdExpedienteDocumentos::find()
                ->where(['idGdExpediente' => $id]);

            $modelGdReferenciasCruzadas = (new \yii\db\Query())
                ->select(['gdReferenciasCruzadas.*', 'gdTrdTiposDocumentales.nombreTipoDocumental'])
                ->from('gdReferenciasCruzadas');
                $modelGdReferenciasCruzadas = HelperQueryDb::getQuery('innerJoin', $modelGdReferenciasCruzadas, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'gdReferenciasCruzadas' => 'idGdTrdTipoDocumental']);
                $modelGdReferenciasCruzadas->where(['idGdExpediente' => $id]);

            # Consulta de documento del radicado 
            $modelRadiDocumento = (new \yii\db\Query())
                ->select(['radiDocumentos.*', 'gdExpedientesInclusion.creacionGdExpedienteInclusion', 'radiRadicados.numeroRadiRadicado', 'gdTrdTiposDocumentales.nombreTipoDocumental'])
                ->from('radiDocumentos');
                $modelRadiDocumento = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumento, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiDocumentos' => 'idRadiRadicado']);                
                $modelRadiDocumento = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumento, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
                $modelRadiDocumento = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumento, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'radiDocumentos' => 'idGdTrdTipoDocumental']);
                $modelRadiDocumento->where(['gdExpedientesInclusion.idGdExpediente' => $id]);  

            $modelRadiDocumento = $modelRadiDocumento;        
            
            # Consulta de documentos principales del radicado
            $modelRadiDocumenPrinc = (new \yii\db\Query())
                ->select(['radiDocumentosPrincipales.*', 'gdExpedientesInclusion.creacionGdExpedienteInclusion', 'radiRadicados.numeroRadiRadicado', 'gdTrdTiposDocumentales.nombreTipoDocumental'])
                ->from('radiDocumentosPrincipales');
                $modelRadiDocumenPrinc = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumenPrinc, 'radiRadicados', ['radiRadicados' => 'idRadiRadicado', 'radiDocumentosPrincipales' => 'idRadiRadicado']);                
                $modelRadiDocumenPrinc = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumenPrinc, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idRadiRadicado', 'radiRadicados' => 'idRadiRadicado']);
                $modelRadiDocumenPrinc = HelperQueryDb::getQuery('innerJoin', $modelRadiDocumenPrinc, 'gdTrdTiposDocumentales', ['gdTrdTiposDocumentales' => 'idGdTrdTipoDocumental', 'radiRadicados' => 'idTrdTipoDocumental'])
                ->where(['gdExpedientesInclusion.idGdExpediente' => $id]);   
                $modelRadiDocumenPrinc->andWhere([ 'or',
                    [ 'radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Combinado'] ],
                    [ 'radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesandoFirma'] ],
                    [ 'radiDocumentosPrincipales.estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado'] ]]
                );

            $modelRadiDocumenPrinc = $modelRadiDocumenPrinc;   

            # Se reitera $dataWhere para solo obtener los campos con datos
            foreach ($dataWhere as $field => $value) {

                switch ($field) {

                    case 'fechaInicial':
                        $modelRadiDocumento->andWhere(['>=', 'radiDocumentos.creacionRadiDocumento', trim($value) . Yii::$app->params['timeStart']]);

                        $modelRadiDocumenPrinc->andWhere(['>=', 'radiDocumentosPrincipales.creacionRadiDocumentoPrincipal', trim($value) . Yii::$app->params['timeStart']]);

                        $modelExpedienteDocumentos->andWhere(['>=', 'gdExpedienteDocumentos.fechaDocGdExpedienteDocumento', trim($value) . Yii::$app->params['timeStart']]);

                        $modelGdReferenciasCruzadas->andWhere(['>=', 'gdReferenciasCruzadas.creacionGdReferenciaCruzada', trim($value) . Yii::$app->params['timeStart']]);
                    break;

                    case 'fechaFinal':
                        $modelRadiDocumento->andWhere(['<=', 'radiDocumentos.creacionRadiDocumento', trim($value) . Yii::$app->params['timeEnd']]);

                        $modelRadiDocumenPrinc->andWhere(['<=', 'radiDocumentosPrincipales.creacionRadiDocumentoPrincipal', trim($value) . Yii::$app->params['timeEnd']]);

                        $modelExpedienteDocumentos->andWhere(['<=', 'gdExpedienteDocumentos.fechaDocGdExpedienteDocumento', trim($value) . Yii::$app->params['timeEnd']]);

                        $modelGdReferenciasCruzadas->andWhere(['>=', 'gdReferenciasCruzadas.creacionGdReferenciaCruzada', trim($value) . Yii::$app->params['timeStart']]);
                    break;

                    case 'descripcion':
                        $modelRadiDocumento->andWhere([Yii::$app->params['like'], 'radiDocumentos.' . 'descripcionRadiDocumento', $value]);

                        $descripcionPrinc = $value; // Se reasigna el texto de la descripcion para filtros
                        $validDescrip = false;

                        $modelExpedienteDocumentos->andWhere([Yii::$app->params['like'], 'gdExpedienteDocumentos.' . 'observacionGdExpedienteDocumento', $value]);
                        $modelGdReferenciasCruzadas->andWhere([Yii::$app->params['like'], 'gdReferenciasCruzadas.' . 'nombreGdReferenciaCruzada', $value]);
                    break;   

                    case 'tipoDocumental':  
                        $modelRadiDocumento->andWhere(['IN', 'radiDocumentos.' . 'idGdTrdTipoDocumental', $value]);

                        $modelRadiDocumenPrinc->andWhere(['IN', 'radiRadicados.' . 'idTrdTipoDocumental', $value]);

                        $modelExpedienteDocumentos->andWhere(['IN', 'gdExpedienteDocumentos.' . 'idGdTrdTipoDocumental', $value]);

                        $modelGdReferenciasCruzadas->andWhere(['IN', 'gdReferenciasCruzadas.' . 'idGdTrdTipoDocumental', $value]);
                    break;

                    case 'numeroRadiRadicado':
                        $modelRadiDocumento->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);

                        $modelRadiDocumenPrinc->andWhere([Yii::$app->params['like'], 'radiRadicados.' . $field, $value]);

                        $valor = $value; // Se reasigna el texto 'N/A' para filtros
                        $validacion = false; //Cuando el documento es manual
                      
                    break;             
                }                
            }            

            # Orden descendente para ver los últimos registros creados
            $modelRadiDocumento->orderBy(['radiDocumentos.idRadiDocumento' => SORT_DESC]); 

            # Limite de la consulta radiDocumento
            $modelRadiDocumento->limit($limitRecords);
            $modelDocumentos = $modelRadiDocumento->all();

            # Limite de la consulta radiDocumento
            $modelRadiDocumenPrinc->limit($limitRecords);
            $modelDocumentosPrinc = $modelRadiDocumenPrinc->all();

            # Limite de la consulta expedienteDocumento
            $modelExpedienteDocumentos->limit($limitRecords);
            $modelExpDocumentos = $modelExpedienteDocumentos->all();

            # Limite de la consulta gdReferenciasCruzadas
            $modelGdReferenciasCruzadas->limit($limitRecords);
            $modelReferenciasCruzadas = $modelGdReferenciasCruzadas->all();
            

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelDocumentos = array_reverse($modelDocumentos);
            $modelDocumentosPrinc = array_reverse($modelDocumentosPrinc);
            $modelExpDocumentos = array_reverse($modelExpDocumentos);
            $modelReferenciasCruzadas = array_reverse($modelReferenciasCruzadas);

            # Iteración de Radi Documentos
            foreach($modelDocumentos as $documento){  

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($documento['idRadiDocumento']))
                );

                $dataList[] = [
                    'data'                             => $dataBase64Params,
                    'id'                               => $documento['idRadiDocumento'],
                    'numeroRadiRadicado'               =>  HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($documento['numeroRadiRadicado']),
                    'nombreGdTrdTipoDocumental'        => $documento['nombreTipoDocumental'],
                    'nombreRadiDocumento'              => $documento['nombreRadiDocumento'],
                    'descripcionRadiDocumentoRadicado' => $documento['descripcionRadiDocumento'],
                    'creacionRadiDocumento'            => $documento['creacionRadiDocumento'],
                    //'creacionGdExpedienteInclusion'    => $documento['creacionGdExpedienteInclusion'],
                    'statusText'                       => Yii::$app->params['statusTodoNumber'][$documento['estadoRadiDocumento']],
                    'status'                           => $documento['estadoRadiDocumento'],
                    'rowSelect'                        => false,
                    'idInitialList'                    => 0,
                    'extension'                        => $documento['extencionRadiDocumento'],
                    'model'                            => 'RadiDocumentos',
                ];
            }           


            # Descripcion por defecto del documento principal
            $descripcion = 'Documento principal del radicado';

            # Cuando no hay ningun filtro muestra todos los documentos principales
            if($validDescrip) {

                # Iteración de Radi Documentos Principales  
                foreach($modelDocumentosPrinc as $documentoPrinc){ 

                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($documentoPrinc['idradiDocumentoPrincipal']))
                    );

                    $dataList[] = [
                        'data'                             => $dataBase64Params,
                        'id'                               => $documentoPrinc['idradiDocumentoPrincipal'],
                        'numeroRadiRadicado'               => $documentoPrinc['numeroRadiRadicado'],
                        'nombreGdTrdTipoDocumental'        => $documentoPrinc['nombreTipoDocumental'],
                        'nombreRadiDocumento'              => $documentoPrinc['nombreRadiDocumentoPrincipal'],
                        'descripcionRadiDocumentoRadicado' => $descripcion,    
                        'creacionRadiDocumento'            => $documentoPrinc['creacionRadiDocumentoPrincipal'],
                        //'creacionGdExpedienteInclusion'    => $documentoPrinc['creacionGdExpedienteInclusion'],
                        'statusText'                       => Yii::$app->params['statusTodoNumber'][$documentoPrinc['estadoRadiDocumentoPrincipal']],
                        'status'                           => $documentoPrinc['estadoRadiDocumentoPrincipal'],
                        'rowSelect'                        => false,
                        'idInitialList'                    => 0,
                        'extension'                        => $documentoPrinc['extensionRadiDocumentoPrincipal'],
                        'model'                            => 'RadiDocumentosPrincipales',
                    ];
                } 

            } else {

                foreach($modelDocumentosPrinc as $documentoPrinc){  

                    $coincidencia = strripos($descripcion, $descripcionPrinc);

                    #Si encuentra coincidencia en la descripción muestra los registros correspondientes al documento
                    if($coincidencia !== false) {

                        $dataBase64Params = array(
                            str_replace(array('/', '+'), array('_', '-'), base64_encode($documentoPrinc['idradiDocumentoPrincipal']))
                        );
    
                        $dataList[] = [
                            'data'                             => $dataBase64Params,
                            'id'                               => $documentoPrinc['idradiDocumentoPrincipal'],
                            'numeroRadiRadicado'               => $documentoPrinc['numeroRadiRadicado'],
                            'nombreGdTrdTipoDocumental'        => $documentoPrinc['nombreTipoDocumental'],
                            'nombreRadiDocumento'              => $documentoPrinc['nombreRadiDocumentoPrincipal'],
                            'descripcionRadiDocumentoRadicado' => $descripcion,    
                            'creacionRadiDocumento'            => $documentoPrinc['creacionRadiDocumentoPrincipal'],
                            //'creacionGdExpedienteInclusion'    => $documentoPrinc['creacionGdExpedienteInclusion'],
                            'statusText'                       => Yii::$app->params['statusTodoNumber'][$documentoPrinc['estadoRadiDocumentoPrincipal']],
                            'status'                           => $documentoPrinc['estadoRadiDocumentoPrincipal'],
                            'rowSelect'                        => false,
                            'idInitialList'                    => 0,
                            'extension'                        => $documentoPrinc['extensionRadiDocumentoPrincipal'],
                            'model'                            => 'RadiDocumentosPrincipales',
                        ];
                    }                     
                }                
            }


            # Numero vacio definido
            $numVacio = 'N/A';

            # Cuando no hay ningun filtro muestra todos los documentos del expediente
            if($validacion){

                # Iteración de Expediente Documentos
                foreach($modelExpDocumentos as $documentoExp){         

                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($documentoExp->idGdExpedienteDocumento))
                    );
    
                    $dataList[] = [
                        'data'                              => $dataBase64Params,
                        'id'                                => $documentoExp->idGdExpedienteDocumento,
                        'numeroRadiRadicado'                => $numVacio,
                        'nombreGdTrdTipoDocumental'         => $documentoExp->gdTrdTipoDocumental->nombreTipoDocumental,
                        'nombreRadiDocumento'               => $documentoExp->nombreGdExpedienteDocumento,
                        'descripcionRadiDocumentoRadicado'  => $documentoExp->observacionGdExpedienteDocumento, 
                        'creacionRadiDocumento'             => $documentoExp->fechaDocGdExpedienteDocumento,
                        //'creacionGdExpedienteInclusion'     => $documentoExp->creacionGdExpedienteDocumento,
                        'statusText'                        => Yii::$app->params['statusTodoNumber'][$documentoExp->estadoGdExpedienteDocumento],
                        'status'                            => $documentoExp->estadoGdExpedienteDocumento,
                        'rowSelect'                         => false,
                        'idInitialList'                     => 0,
                        'extension'                         => $documentoExp->extensionGdExpedienteDocumento,
                        'model'                             => 'GdExpedienteDocumentos',
                    ];
                }

            } else {

                # Iteración de Expediente Documentos
                foreach($modelExpDocumentos as $documentoExp){     
                    
                    $coincidencia = strripos($numVacio, $valor);

                    #Si encuentra coincidencia con el texto el número vacio, muestra los registros correspondientes del documento
                    if($coincidencia !== false) {

                        $dataBase64Params = array(
                            str_replace(array('/', '+'), array('_', '-'), base64_encode($documentoExp->idGdExpedienteDocumento))
                        );
        
                        $dataList[] = [
                            'data'                              => $dataBase64Params,
                            'id'                                => $documentoExp->idGdExpedienteDocumento,
                            'numeroRadiRadicado'                => $numVacio,
                            'nombreGdTrdTipoDocumental'         => $documentoExp->gdTrdTipoDocumental->nombreTipoDocumental,
                            'nombreRadiDocumento'               => $documentoExp->nombreGdExpedienteDocumento,
                            'descripcionRadiDocumentoRadicado'  => $documentoExp->observacionGdExpedienteDocumento, 
                            'creacionRadiDocumento'             => $documentoExp->fechaDocGdExpedienteDocumento,
                            //'creacionGdExpedienteInclusion'     => $documentoExp->creacionGdExpedienteDocumento,
                            'statusText'                        => Yii::$app->params['statusTodoNumber'][$documentoExp->estadoGdExpedienteDocumento],
                            'status'                            => $documentoExp->estadoGdExpedienteDocumento,
                            'rowSelect'                         => false,
                            'idInitialList'                     => 0,
                            'extension'                         => $documentoExp->extensionGdExpedienteDocumento,
                            'model'                             => 'GdExpedienteDocumentos',
                        ];
                    }
                }
            }

            # Iteración de Expediente Documentos
            foreach($modelReferenciasCruzadas as $documentoReferencia){

                # Cuando no hay ningun filtro muestra todos los documentos del expediente
                if ($validacion) {
                    $coincidencia = true;
                } else {
                    #Si encuentra coincidencia con el texto el número vacio, muestra los registros correspondientes del documento
                    $coincidencia = strripos($numVacio, $valor);
                }

                if($coincidencia !== false) {

                    $dataBase64Params = array(
                        str_replace(array('/', '+'), array('_', '-'), base64_encode($documentoReferencia['idGdReferenciaCruzada']))
                    );

                    $dataList[] = [
                        'data'                              => $dataBase64Params,
                        'id'                                => $documentoReferencia['idGdReferenciaCruzada'],
                        'numeroRadiRadicado'                => $numVacio,
                        'nombreGdTrdTipoDocumental'         => $documentoReferencia['nombreTipoDocumental'],
                        'nombreRadiDocumento'               => $documentoReferencia['nombreArchivoGdReferenciasCruzada'],
                        'descripcionRadiDocumentoRadicado'  => $documentoReferencia['nombreGdReferenciaCruzada'], 
                        'creacionRadiDocumento'             => $documentoReferencia['creacionGdReferenciaCruzada'],
                        //'creacionGdExpedienteInclusion'     => $documentoReferencia['creacionGdExpedienteDocumento'],
                        'statusText'                        => Yii::$app->params['statusTodoNumber'][$documentoReferencia['estadoGdReferenciaCruzada']],
                        'status'                            => $documentoReferencia['estadoGdReferenciaCruzada'],
                        'rowSelect'                         => false,
                        'idInitialList'                     => 0,
                        'extension'                         => 'pdf',
                        'model'                             => 'GdReferenciasCruzadas',
                    ];
                }
            }

            # Ordenamiento del index por fecha de creación del documento
            usort($dataList, function($a, $b){
                return strcmp($a['creacionRadiDocumento'],$b['creacionRadiDocumento']);
            });

            // Validar que el formulario exista
            $formType = HelperDynamicForms::setListadoBD('viewExpediente');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);

        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app', 'accessDenied')[1],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }
    }

    public function actionCreate() {
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

            $arrayMensajes = [];
            unset($request['data']['id']);
            $request['data']['fechaProcesoGdExpediente'] = explode('T', $request['data']['fechaProcesoGdExpediente'])[0];

            $transaction = Yii::$app->db->beginTransaction();

            $modelExpediente = new GdExpedientes();
            $modelExpediente->attributes = $request['data'];

            $gdExpTiempoRetencion = $this->gdExpTiempoRetencion($modelExpediente->idGdTrdSerie,$modelExpediente->idGdTrdSubserie, $modelExpediente->idGdTrdDependencia);

            if(!$gdExpTiempoRetencion){
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'errorTiempoRetencion')]],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $modelExpediente->tiempoGestionGdExpedientes = $gdExpTiempoRetencion['tiempoGestionGdExpedientes'];
            $modelExpediente->tiempoCentralGdExpedientes = $gdExpTiempoRetencion['tiempoCentralGdExpedientes'];

            //Datos para la construcción del numero Expediente (año+dependencia+serie+subserie+consecutivo)
            $anio =  date("Y", strtotime($request['data']['fechaProcesoGdExpediente']));
            $dependencia = $modelExpediente->gdTrdDependencia->codigoGdTrdDependencia;
            $serie = $modelExpediente->gdTrdSerie->codigoGdTrdSerie;
            $subserie = $modelExpediente->gdTrdSubserie->codigoGdTrdSubserie;
            $consecutivo = $modelExpediente->gdTrdDependencia->consecExpedienteGdTrdDependencia;

            # Numero de expediente
            $modelExpediente->numeroGdExpediente = $anio. $dependencia. $serie. $subserie. $consecutivo;

            # Se actualiza el consecutivo en la dependencia.
            $updateDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelExpediente->idGdTrdDependencia]);
            $updateDependence->consecExpedienteGdTrdDependencia = $consecutivo + 1;

            if (!$updateDependence->save()) {

                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $updateDependence->getErrors(),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            //Validación si el expediente ya existe
            $modelExistente = GdExpedientes::find()
                ->where(['nombreGdExpediente' => $modelExpediente->nombreGdExpediente])
                ->andWhere(['idGdTrdDependencia' => $modelExpediente->idGdTrdDependencia])
                ->one();

            if(!empty($modelExistente)){
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => [Yii::t('app', 'duplicatedExpedient', [
                        'nombreExpediente' => $modelExistente->nombreGdExpediente,
                        'nombreDependencia' => $modelExistente->gdTrdDependencia->nombreGdTrdDependencia
                    ])],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
            //Fin de la validación

            # Se guardan los datos del expediente
            if ($modelExpediente->save()) {
                $modelExpediente = GdExpedientes::find()->where(['idGdExpediente' => $modelExpediente->idGdExpediente])->one();
                $modelUserDetalle = UserDetalles::find()->where(['idUser' => $modelExpediente->idUser])->one();

                $dataExpediente = 'Id expediente: ' . $modelExpediente->idGdExpediente;
                $dataExpediente .= ', Número expediente: ' . $modelExpediente->numeroGdExpediente;
                $dataExpediente .= ', Nombre expediente: ' . $modelExpediente->nombreGdExpediente;
                $dataExpediente .= ', Usuario: ' . "$modelUserDetalle->nombreUserDetalles $modelUserDetalle->apellidoUserDetalles";
                $dataExpediente .= ', Dependencia: ' . $modelExpediente->gdTrdDependencia->nombreGdTrdDependencia;
                $dataExpediente .= ', Serie: ' . $modelExpediente->gdTrdSerie->nombreGdTrdSerie;
                $dataExpediente .= ', Subserie: ' . $modelExpediente->gdTrdSubserie->nombreGdTrdSubserie;
                $dataExpediente .= ', Ubicación: ' . $modelExpediente->ubicacionGdExpediente;
                $dataExpediente .= ', Tiempo de gestión: ' . $modelExpediente->tiempoGestionGdExpedientes;
                $dataExpediente .= ', Tiempo central: ' . $modelExpediente->tiempoCentralGdExpedientes;
                $dataExpediente .= ', Fecha de creación: ' . $modelExpediente->creacionGdExpediente;
                $dataExpediente .= ', Fecha de inicio del expediente: ' . $modelExpediente->fechaProcesoGdExpediente;
                $dataExpediente .= ((boolean) $modelExpediente->existeFisicamenteGdExpediente == true) ? ', ¿Existe físicamente?: Sí' : ', ¿Existe físicamente?: No';
                $dataExpediente .= ', Estado: ' . Yii::$app->params['statusExpedienteNumber'][$modelExpediente->estadoGdExpediente];

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['crear'] . " con el número de expediente: ". $modelExpediente->numeroGdExpediente. ", en la tabla GdExpedientes", //texto para almacenar en el evento
                    '',
                    $dataExpediente, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $numeroExpediente = $modelExpediente->numeroGdExpediente;
                $serie = $modelExpediente->gdTrdSerie->nombreGdTrdSerie;
                $subserie = $modelExpediente->gdTrdSubserie->nombreGdTrdSubserie;

                $idUserLogeado = Yii::$app->user->identity->id;
                $idDepeUserLogeado = self::findModelUser($idUserLogeado)->idGdTrdDependencia;

                $observacion = "Se creó el expediente asignándole el número ".$numeroExpediente.", aplicando la serie: ". $serie." y la subserie: ". $subserie;

                if ((boolean) $modelExpediente->existeFisicamenteGdExpediente == true) {
                    $observacion .= '. El expediente está conformado fisica y electrónicamente';
                } else {
                    $observacion .= '. El expediente está conformado electrónicamente';
                }

                $idExpediente = $modelExpediente->idGdExpediente;

                if (isset($request['data']['idGdExpedientesDependencias']) && count($request['data']['idGdExpedientesDependencias']) > 0) {
                    $saveDataValidGdExpedientesDependencias = true;
                    foreach ($request['data']['idGdExpedientesDependencias'] as $key => $gdExpedientesDependencia) {
                        $modelGdExpedientesDependencias = new GdExpedientesDependencias();
                        $modelGdExpedientesDependencias->idGdExpediente = $idExpediente;
                        $modelGdExpedientesDependencias->idGdTrdDependencia = $gdExpedientesDependencia;
                        if (!$modelGdExpedientesDependencias->save()) {
                            $saveDataValidGdExpedientesDependencias = false;
                        }
                    }

                    if (!$saveDataValidGdExpedientesDependencias) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelGdExpedientesDependencias->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $modelGdTrdDependencias = GdTrdDependencias::find()
                        ->where(['IN', 'idGdTrdDependencia', $request['data']['idGdExpedientesDependencias']])
                        ->select(['idGdTrdDependencia', 'nombreGdTrdDependencia'])
                        ->all();
                    foreach ($modelGdTrdDependencias as $key => $modelGdTrdDependencia) {
                        $nombresDependencias[] = $modelGdTrdDependencia->nombreGdTrdDependencia;
                    }
                    $observacion .= ", Dependencias que hacen parte de la gestión del expediente " . implode(", ", $nombresDependencias);
                }

                /***    Log expediente  ***/
                HelperLog::logAddExpedient(
                    $idUserLogeado, //Id user
                    $idDepeUserLogeado, // Id dependencia
                    $idExpediente, //Id expediente
                    Yii::$app->params['operacionExpedienteText']['CrearExpediente'], //Operación
                    $observacion //observación
                );
                /***    Log expediente    ***/

                /** Procesar inclusion de radicados al expediente creado */
                if (isset($request['dataRadicados']) && is_array($request['dataRadicados']) && !empty($request['dataRadicados'])) {
                    $includeInExpedient = HelperExpedient::includeInExpedient($request['dataRadicados'], $modelExpediente);
                                        
                    if ($includeInExpedient['status'] == false) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $includeInExpedient['errors'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    } else {
                        $arrayMensajes = $includeInExpedient['data']['arrayMensajes'];
                    }
                }
                /** Fin Procesar inclusion de radicados al expediente creado */
            } else {
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $modelExpediente->getErrors(),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }

            $transaction->commit();

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => array_merge([Yii::t('app', 'successSave')], $arrayMensajes),
                'data' => $modelExpediente,
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
    }

    public function actionGetGdTiposAnexosFisicos()
    {
        $dataList = [];

        $data = GdTiposAnexosFisicos::find()
            ->where(['estadoGdTipoAnexoFisico' => Yii::$app->params['statusTodoText']['Activo']])
            ->select(['idGdTipoAnexoFisico', 'nombreGdTipoAnexoFisico'])
            ->all();

        foreach ($data as $value) {
            $dataList[] = [
                'id' => $value['idGdTipoAnexoFisico'],
                'val' => $value['nombreGdTipoAnexoFisico'],
            ];
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
     * Referencia cruzada
     */
    public function actionCrossReference()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsCrossReference'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $idUserLogeado = Yii::$app->user->identity->id;
                $modelUserLogeado = self::findModelUser($idUserLogeado);
                $modelUserDetalles = UserDetalles::find()->where(['idUser' => $idUserLogeado])->one();
                $idDepeUserLogeado = $modelUserLogeado->idGdTrdDependencia;

                $nameUserAuth = $modelUserDetalles['nombreUserDetalles'].' '.$modelUserDetalles['apellidoUserDetalles'];

                $notificacion = [];
                $numerosExpedientes = [];

                $modelGdTiposAnexosFisicos= GdTiposAnexosFisicos::find()->all();

                $transaction = Yii::$app->db->beginTransaction();

                foreach ($request['eventSelectData'] as $expedient) {

                    $modelExpediente = self::findModel($expedient['id']);
                    $numerosExpedientes[] = $modelExpediente->numeroGdExpediente;

                    $idTrdDepeExpedient = $modelExpediente->idGdTrdDependencia;
                    $dependenciaExpedient = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $idTrdDepeExpedient])->one();
                    $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDocuments'] . "/" .$dependenciaExpedient->codigoGdTrdDependencia.'/';

                    $modelGdReferenciasCruzadas = new GdReferenciasCruzadas();
                    $modelGdReferenciasCruzadas->attributes = $request['data'];
                    $modelGdReferenciasCruzadas->idGdExpediente = $expedient['id'];
                    $modelGdReferenciasCruzadas->idGdTrdTipoDocumental = Yii::$app->params['tipoDocumentalText']['crossReference'];
                    $modelGdReferenciasCruzadas->idUserGdReferenciaCruzada = Yii::$app->user->identity->id;
                    $modelGdReferenciasCruzadas->creacionGdReferenciaCruzada = date('Y-m-d H:i:s');
                    $modelGdReferenciasCruzadas->estadoGdReferenciaCruzada = Yii::$app->params['statusTodoText']['Activo'];
                    $modelGdReferenciasCruzadas->rutaGdReferenciasCruzada = $pathUploadFile;

                    if (!$modelGdReferenciasCruzadas->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $modelGdReferenciasCruzadas->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    /* Guardar registro de ruta de archivo*/
                    $filename = 'referencia_cruzada_' . $modelGdReferenciasCruzadas->idGdReferenciaCruzada;
                    $modelGdReferenciasCruzadas->nombreArchivoGdReferenciasCruzada = $filename . '.pdf';

                    if (!$modelGdReferenciasCruzadas->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => $modelGdReferenciasCruzadas->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /* Fin Guardar registro de ruta de archivo*/

                    $arrayTiposAnexosFisicos = [];
                    $arrayIdsReferenciaTiposAnexos = [];
                    foreach ($request['data']['idGdTipoAnexoFisico'] as $tipoAnexoFisico) {
                        $modelGdReferenciaTiposAnexos = new GdReferenciaTiposAnexos();

                        $modelGdReferenciaTiposAnexos->idGdReferenciaCruzada = $modelGdReferenciasCruzadas->idGdReferenciaCruzada;
                        $modelGdReferenciaTiposAnexos->idGdTipoAnexoFisico = $tipoAnexoFisico;
                        $modelGdReferenciaTiposAnexos->creacionGdReferenciaTipoAnexo = date('Y-m-d H:i:s');
                        $modelGdReferenciaTiposAnexos->estadoGdReferenciaTipoAnexo = Yii::$app->params['statusTodoText']['Activo'];

                         if (!$modelGdReferenciaTiposAnexos->save()) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $modelGdReferenciaTiposAnexos->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        $arrayTiposAnexosFisicos[] = $modelGdReferenciaTiposAnexos->gdTipoAnexoFisico->nombreGdTipoAnexoFisico;
                        $arrayIdsReferenciaTiposAnexos[] = $modelGdReferenciaTiposAnexos->idGdTipoAnexoFisico;
                    }

                    /** GENERAR ARCHIVO PDF*/
                    $rutaOk = true;

                    // Verificar que la carpeta exista y crearla en caso de no exista
                    if (!file_exists($pathUploadFile)) {
                        if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                            $rutaOk = false;
                        }
                    }

                    /*** Validar creación de la carpeta***/
                    if ($rutaOk == false) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                            'datafile' => false,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Validar creación de la carpeta***/


                    $arrayTypesAnexosFisicos = [];
                    foreach ($modelGdTiposAnexosFisicos as $tipoAnexoFisico) {
                        $arrayTypesAnexosFisicos[] = [
                            'key' => $tipoAnexoFisico->nombreGdTipoAnexoFisico,
                            'value' => in_array($tipoAnexoFisico->idGdTipoAnexoFisico, $arrayIdsReferenciaTiposAnexos),
                        ];
                    }

                    $datapdf = [
                        'modelGdReferenciasCruzadas' => $modelGdReferenciasCruzadas,
                        'arrayTiposAnexosFisicos' => $arrayTypesAnexosFisicos,
                    ];

                    PdfController::generar_pdf_formatoh('GestionDocumental','crossReferenceView', $filename, $pathUploadFile, $datapdf, [], $nameUserAuth);
                    /** FIN GENERAR ARCHIVO PDF*/

                    /**
                     *  Validación de archivo PDF generado
                     */
                    $rutaArchivoPdfGenerado = $pathUploadFile . '/' . $filename . '.pdf';
                    if (!file_exists($rutaArchivoPdfGenerado)) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','couldNotGenerateFile')]],
                            'datafile' => false,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    
                    /* Generar índice electrónico */
                    $tamano = filesize($rutaArchivoPdfGenerado) / 1000;
                    $tamano = $tamano . ' KB';

                    $xml = HelperIndiceElectronico::getXmlParams($modelExpediente);
                    $resultado = HelperIndiceElectronico::addCrossReferenceToExpedient($modelGdReferenciasCruzadas, $modelExpediente, $xml, $tamano);

                    if(!$resultado['ok']){
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [$resultado['data']['response']['data']],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $dataLog = 'Id referencia cruzada: ' . $modelGdReferenciasCruzadas->idGdReferenciaCruzada;
                    $dataLog .= ', Nombre referencia cruzada: ' . $modelGdReferenciasCruzadas->nombreGdReferenciaCruzada;
                    $dataLog .= ', Cantidad: ' . $modelGdReferenciasCruzadas->cantidadGdReferenciaCruzada;
                    $dataLog .= ', Ubicación: ' . $modelGdReferenciasCruzadas->ubicacionGdReferenciaCruzada;
                    $dataLog .= ', Tipos Anexos físicos: ' . implode(', ', $arrayTiposAnexosFisicos) . ', ' . $modelGdReferenciasCruzadas->tipoAnexoGdReferenciaCruzada;

                    $dataLog .= ', Expediente: ' . $modelExpediente->numeroGdExpediente;
                    $dataLog .= ', Usuario: ' . $modelUserDetalles->nombreUserDetalles.' '.$modelUserDetalles->apellidoUserDetalles; 

                    $dataLog .= ', Fecha de creación: ' . $modelGdReferenciasCruzadas->creacionGdReferenciaCruzada;
                    $dataLog .= ', Estado: ' . Yii::$app->params['statusTodoNumber'][$modelGdReferenciasCruzadas->estadoGdReferenciaCruzada];

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla gdReferenciasCruzadas", //texto para almacenar en el evento
                        '',
                        $dataLog, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    $observacion = 'Se realizo Referencia Cruzada del documento ' . $modelGdReferenciasCruzadas->nombreGdReferenciaCruzada;
                    $observacion .= ', y tipos Anexos físicos: ' . implode(', ', $arrayTiposAnexosFisicos) . ', ' . $modelGdReferenciasCruzadas->tipoAnexoGdReferenciaCruzada;
                    /***    Log expediente  ***/
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        $idDepeUserLogeado, // Id dependencia
                        $modelExpediente->idGdExpediente, //Id expediente
                        Yii::$app->params['operacionExpedienteText']['crossReference'], //Operación
                        $observacion //observación
                    );                  
                    /***    Log expediente    ***/

                }

                if (count($numerosExpedientes) > 1) {
                    $notificacion[] = [
                        'message' => Yii::t('app', 'notiCrossReferences', [
                            'varExpedient' => implode(', ', $numerosExpedientes)
                        ]),
                        'type' => 'success'
                    ];
                } elseif (count($numerosExpedientes) == 1) {
                    $notificacion[] = [
                        'message' => Yii::t('app', 'notiCrossReference', [
                            'varExpedient' => implode(', ', $numerosExpedientes)
                        ]),
                        'type' => 'success'
                    ];
                }

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'notificacion' => $notificacion,
                    'data' => [],
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

    /**
     *  Descarga de la referencia cruzada - Generar Formato PDF
     */
    public function actionDownloadCrossReference()
    {
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsCrossReference'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $userAuth = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                $nameUserAuth = $userAuth['nombreUserDetalles'].' '.$userAuth['apellidoUserDetalles'];

                foreach ($request['ButtonSelectedData'] as $key => $item) {

                    $id = $item['id'];
                    $rutaOk = true;

                    $modelGdReferenciasCruzadas = GdReferenciasCruzadas::findOne(['idGdReferenciaCruzada' => $id]);
                    $modelExpediente = self::findModel($modelGdReferenciasCruzadas->idGdExpediente);

                    // Nombre del archivo para generar
                    $fileName = $modelGdReferenciasCruzadas->nombreArchivoGdReferenciasCruzada;
                    $rutaFile = $modelGdReferenciasCruzadas->rutaGdReferenciasCruzada.'/'.$fileName;

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->params['permissionsCrossReference'], //Modulo
                        Yii::$app->params['eventosLogText']['DownloadFile'].' PDF de la referencia cruzada: '. $modelGdReferenciasCruzadas->nombreArchivoGdReferenciasCruzada . ' del expediente: ' . $modelExpediente->nombreGdExpediente, //texto para almacenar en el evento
                        '',
                        '', //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

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

            } else {

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                    'datafile' => false,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
            }
        } else {
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'datafile' => false,
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true);
        }
    }

    /**
     * Hoja de control del expediente
     */
    public function actionDownloadControlSheet()
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->params['permissionsCrossReference'], Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                $userAuth = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => Yii::$app->user->identity->id])->one();
                $nameUserAuth = $userAuth['nombreUserDetalles'].' '.$userAuth['apellidoUserDetalles'];

                foreach ($request['ButtonSelectedData'] as $expedient) {

                    $modelExpediente = self::findModel($expedient['id']);
                    $numerosExpedientes[] = $modelExpediente->numeroGdExpediente;

                    $idTrdDepeExpedient = $modelExpediente->idGdTrdDependencia;
                    $modelDependenciaExpediente = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $idTrdDepeExpedient])->one();
                    $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDocuments'] . "/" .$modelDependenciaExpediente->codigoGdTrdDependencia.'/';
                    /* Guardar registro de ruta de archivo*/
                    $filename = 'hoja_control_' . $modelExpediente->idGdExpediente;
                    // $modelGdReferenciasCruzadas->nombreArchivoGdReferenciasCruzada = $filename . '.pdf';


                    $modelGdTrdDependenciaPadre = GdTrdDependencias::find()->where(['idGdTrdDependencia' => $modelDependenciaExpediente->codigoGdTrdDepePadre])->one();
                    $nombreDependenciaPadre = ($modelGdTrdDependenciaPadre != null) ? $modelGdTrdDependenciaPadre->nombreGdTrdDependencia : '';

                    $modelGdTrdSeries = GdTrdSeries::find()->where(['idGdTrdSerie' => $modelExpediente->idGdTrdSerie])->one();
                    $modelGdTrdSubseries = GdTrdSubseries::find()->where(['idGdTrdSubserie' => $modelExpediente->idGdTrdSubserie])->one();

                    $userExpedient = UserDetalles::find()->select(['nombreUserDetalles','apellidoUserDetalles'])->where(['idUser' => $modelExpediente->idUser])->one();
                    $nameUserExpedient = $userExpedient['nombreUserDetalles'].' '.$userExpedient['apellidoUserDetalles'];

                    /** Consultar Índice Electrónico */
                    $modelIndice = GdIndices::find();
                        $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpedienteInclusion', 'gdIndices' => 'idGdExpedienteInclusion']);
                        $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdExpedienteDocumentos', ['gdExpedienteDocumentos' => 'idGdExpedienteDocumento', 'gdIndices' => 'idGdExpedienteDocumento']);
                        $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdReferenciasCruzadas', ['gdReferenciasCruzadas' => 'idGdReferenciaCruzada', 'gdIndices' => 'idGdReferenciaCruzada']);
                        $modelIndice = $modelIndice->where(['gdExpedientesInclusion.idGdExpediente' => $modelExpediente->idGdExpediente])
                        ->orWhere(['gdExpedienteDocumentos.idGdExpediente' => $modelExpediente->idGdExpediente])
                        ->orWhere(['gdReferenciasCruzadas.idGdExpediente' => $modelExpediente->idGdExpediente])
                        ->andWhere(['gdIndices.estadoGdIndice' => Yii::$app->params['statusTodoText']['Activo']])
                        ->orderBy(['gdIndices.creacionDocumentoGdIndice' => SORT_ASC]);
                    $modelIndice = $modelIndice->all();

                    // Lista Indice electrónico
                    $dataList = [];
                    foreach($modelIndice as $indice){

                        # Fecha inclusión
                        if($indice->idGdExpedienteInclusion != null){
                            $fechaIncorporacion = $indice->gdExpedienteInclusion->creacionGdExpedienteInclusion;
                        } else {
                            $fechaIncorporacion = $indice->creacionDocumentoGdIndice;
                        }

                        if(is_null($indice->origenGdIndice)){
                            $indice->origenGdIndice = Yii::$app->params['origen']['Electronico'];
                        }

                        $dataList[] = [
                            'id'                            => $indice->idGdIndice,
                            'nombreTipoDocumental'          => $indice->gdTrdTipoDocumental->nombreTipoDocumental,
                            'folios'                        => 'PENDIENTE',
                            'origen'                        => Yii::$app->params['origenNumber'][$indice->origenGdIndice],
                            'creacionGdExpedienteInclusion' => $fechaIncorporacion,
                        ];
                    }
                    /** Fin Consultar Índice Electrónico */

                    $datapdf = [
                        'modelExpediente'            => $modelExpediente,
                        'modelDependenciaExpediente' => $modelDependenciaExpediente,
                        'nombreDependenciaPadre'     => $nombreDependenciaPadre,
                        'modelGdTrdSeries'           => $modelGdTrdSeries,
                        'modelGdTrdSubseries'        => $modelGdTrdSubseries,
                        'nameUserExpedient'          => $nameUserExpedient,
                        'dataList'                   => $dataList,
                    ];

                    /** GENERAR ARCHIVO PDF*/
                    $rutaOk = true;

                    // Verificar que la carpeta exista y crearla en caso de no exista
                    if (!file_exists($pathUploadFile)) {
                        if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                            $rutaOk = false;
                        }
                    }

                    /*** Validar creación de la carpeta***/
                    if ($rutaOk == false) {
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app','notPermissionsDirectory')]],
                            'datafile' => false,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }
                    /*** Fin Validar creación de la carpeta***/

                    PdfController::generar_pdf_formatoh('GestionDocumental','ControlSheetView', $filename, $pathUploadFile, $datapdf, [], $nameUserAuth);
                    /** FIN GENERAR ARCHIVO PDF*/
                }

                if (count($numerosExpedientes) > 1) {
                    $notificacion[] = [
                        'message' => Yii::t('app', 'notiCrossReferences', [
                            'varExpedient' => implode(', ', $numerosExpedientes)
                        ]),
                        'type' => 'success'
                    ];
                } elseif (count($numerosExpedientes) == 1) {
                    $notificacion[] = [
                        'message' => Yii::t('app', 'notiCrossReference', [
                            'varExpedient' => implode(', ', $numerosExpedientes)
                        ]),
                        'type' => 'success'
                    ];
                }


                $rutaFile = $pathUploadFile . $filename . '.pdf';
                $fileName = $filename . '.pdf';
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

                // $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'notificacion' => $notificacion,
                    'data' => [],
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

    public function actionUpdate() {
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

                $id = $request['id'];
                $request['fechaProcesoGdExpediente'] = explode('T', $request['fechaProcesoGdExpediente'])[0];

                $transaction = Yii::$app->db->beginTransaction();

                $modelExpediente = self::findModel($id);
                $existeFisicamenteOld = (boolean) $modelExpediente->existeFisicamenteGdExpediente;

                $modelUserDetalle = UserDetalles::find()->where(['idUser' => $modelExpediente->idUser])->one();

                $dataExpedienteOld = 'Id expediente: ' . $modelExpediente->idGdExpediente;
                $dataExpedienteOld .= ', Número expediente: ' . $modelExpediente->numeroGdExpediente;
                $dataExpedienteOld .= ', Nombre expediente: ' . $modelExpediente->nombreGdExpediente;    
                $dataExpedienteOld .= ', Usuario: ' . "$modelUserDetalle->nombreUserDetalles $modelUserDetalle->apellidoUserDetalles"; 
                $dataExpedienteOld .= ', Dependencia: ' . $modelExpediente->gdTrdDependencia->nombreGdTrdDependencia;    
                $dataExpedienteOld .= ', Serie: ' . $modelExpediente->gdTrdSerie->nombreGdTrdSerie;    
                $dataExpedienteOld .= ', Subserie: ' . $modelExpediente->gdTrdSubserie->nombreGdTrdSubserie;                   
                $dataExpedienteOld .= ', Ubicación: ' . $modelExpediente->ubicacionGdExpediente;                  
                $dataExpedienteOld .= ', Tiempo de gestión: ' . $modelExpediente->tiempoGestionGdExpedientes;
                $dataExpedienteOld .= ', Tiempo central: ' . $modelExpediente->tiempoCentralGdExpedientes;
                $dataExpedienteOld .= ', Fecha de creación: ' . $modelExpediente->creacionGdExpediente;
                $dataExpedienteOld .= ((boolean) $modelExpediente->existeFisicamenteGdExpediente == true) ? ', ¿Existe físicamente?: Sí' : ', ¿Existe físicamente?: No';
                $dataExpedienteOld .= ', Estado: ' . Yii::$app->params['statusExpedienteNumber'][$modelExpediente->estadoGdExpediente];

                $consecutivo = substr($modelExpediente->numeroGdExpediente, -5);

                #(validacion1) NO se debe permitir modificar datos específicos del expediente
                unset($request['idGdTrdSerie']);
                unset($request['idGdTrdSubserie']);
                unset($request['idGdTrdDependencia']);
                unset($request['idUser']);

                $modelExpediente->attributes = $request;

                # Se comenta este punto porque NO se debe permitir modificar datos específicos del expediente (validacion1)
                /*
                //Generacion de numeroExpediente
                $dependencia = $modelExpediente->gdTrdDependencia->codigoGdTrdDependencia;
                $serie = $modelExpediente->gdTrdSerie->codigoGdTrdSerie;
                $subserie = $modelExpediente->gdTrdSubserie->codigoGdTrdSubserie;         

                $modelExpediente->numeroGdExpediente = $dependencia . $serie . $subserie . $consecutivo;
                */

                //Validación si el expediente ya existe
                $modelExistente = GdExpedientes::find()
                    ->where(['nombreGdExpediente' => $modelExpediente->nombreGdExpediente])
                    ->andWhere(['idGdTrdDependencia' => $modelExpediente->idGdTrdDependencia])
                    ->andWhere(['<>', 'idGdExpediente', $modelExpediente->idGdExpediente])
                    ->one();

                if(!empty($modelExistente)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [Yii::t('app', 'duplicatedExpedient', [
                            'nombreExpediente' => $modelExistente->nombreGdExpediente,
                            'nombreDependencia' => $modelExistente->gdTrdDependencia->nombreGdTrdDependencia
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }
                //Fin de la validación

                if($modelExpediente->save()) {
                    $modelUserDetalle = UserDetalles::find()->where(['idUser' => $modelExpediente->idUser])->one();

                    $dataExpediente = 'Id expediente: ' . $modelExpediente->idGdExpediente;
                    $dataExpediente .= ', Número expediente: ' . $modelExpediente->numeroGdExpediente;
                    $dataExpediente .= ', Nombre expediente: ' . $modelExpediente->nombreGdExpediente;    
                    $dataExpediente .= ', Usuario: ' . "$modelUserDetalle->nombreUserDetalles $modelUserDetalle->apellidoUserDetalles"; 
                    $dataExpediente .= ', Dependencia: ' . $modelExpediente->gdTrdDependencia->nombreGdTrdDependencia;    
                    $dataExpediente .= ', Serie: ' . $modelExpediente->gdTrdSerie->nombreGdTrdSerie;    
                    $dataExpediente .= ', Subserie: ' . $modelExpediente->gdTrdSubserie->nombreGdTrdSubserie;                   
                    $dataExpediente .= ', Ubicación: ' . $modelExpediente->ubicacionGdExpediente;                  
                    $dataExpediente .= ', Tiempo de gestión: ' . $modelExpediente->tiempoGestionGdExpedientes;
                    $dataExpediente .= ', Tiempo central: ' . $modelExpediente->tiempoCentralGdExpedientes;
                    $dataExpediente .= ', Fecha de creación: ' . $modelExpediente->creacionGdExpediente;
                    $dataExpediente .= ((boolean) $modelExpediente->existeFisicamenteGdExpediente == true) ? ', ¿Existe físicamente?: Sí' : ', ¿Existe físicamente?: No';
                    $dataExpediente .= ', Estado: ' . Yii::$app->params['statusExpedienteNumber'][$modelExpediente->estadoGdExpediente];
    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla GdExpedientes con número de expediente: $modelExpediente->numeroGdExpediente", //texto para almacenar en el evento
                        $dataExpedienteOld,
                        $dataExpediente, //Data
                        array() //No validar estos campos
                    );
                    /***    Fin log Auditoria   ***/

                    # validar Log de auditoria del expediente
                    if ($existeFisicamenteOld != (boolean) $modelExpediente->existeFisicamenteGdExpediente) {
                        if ((boolean) $modelExpediente->existeFisicamenteGdExpediente == true) {
                            $observacion = 'Se estableció que el expediente está conformado fisica y electrónicamente (Expediente híbrido)';
                        } else {
                            $observacion = 'Se estableció que el expediente no existe físicamente';
                        }

                        $idUserLogeado = Yii::$app->user->identity->id;
                        $idDepeUserLogeado = self::findModelUser($idUserLogeado)->idGdTrdDependencia;

                        /***    Log expediente  ***/
                        HelperLog::logAddExpedient(
                            $idUserLogeado, //Id user
                            $idDepeUserLogeado, // Id dependencia
                            $modelExpediente->idGdExpediente, //Id expediente
                            Yii::$app->params['operacionExpedienteText']['cambioInformacionExpediente'], //Operación
                            $observacion //observación
                        );
                        /***    Log expediente    ***/
                    }

                    $modelGdExpedientesDependencias = GdExpedientesDependencias::find()
                        ->where(['idGdExpediente' => $modelExpediente->idGdExpediente])
                        ->all();
                    $nuevasDependencias = false;
                    $gdExpedientesDependencias = [];
                    foreach ($modelGdExpedientesDependencias as $key => $modelGdExpedientesDependencia) {
                        $gdExpedientesDependencias[] = $modelGdExpedientesDependencia->idGdTrdDependencia;
                    }
                    GdExpedientesDependencias::deleteAll('idGdExpediente = :idGdExpediente', [':idGdExpediente' => $modelExpediente->idGdExpediente]);

                    if (isset($request['idGdExpedientesDependencias']) && count($request['idGdExpedientesDependencias']) > 0) {
                        $saveDataValidGdExpedientesDependencias = true;
                        foreach ($request['idGdExpedientesDependencias'] as $key => $gdExpedientesDependencia) {
                            $modelGdExpedientesDependencias = new GdExpedientesDependencias();
                            $modelGdExpedientesDependencias->idGdExpediente = $modelExpediente->idGdExpediente;
                            $modelGdExpedientesDependencias->idGdTrdDependencia = $gdExpedientesDependencia;
                            if (!$modelGdExpedientesDependencias->save()) {
                                $saveDataValidGdExpedientesDependencias = false;
                            }

                            if (!in_array($gdExpedientesDependencia, $gdExpedientesDependencias)) {
                                $nuevasDependencias = true;
                            }
                        }

                        if (!$saveDataValidGdExpedientesDependencias) {
                            $transaction->rollBack();
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => $modelGdExpedientesDependencias->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

                        if ($nuevasDependencias || (count($request['idGdExpedientesDependencias']) !== count($gdExpedientesDependencias))) {
                            $modelGdTrdDependencias = GdTrdDependencias::find()
                                ->where(['IN', 'idGdTrdDependencia', $request['idGdExpedientesDependencias']])
                                ->select(['idGdTrdDependencia', 'nombreGdTrdDependencia'])
                                ->all();
                            foreach ($modelGdTrdDependencias as $key => $modelGdTrdDependencia) {
                                $nombresDependencias[] = $modelGdTrdDependencia->nombreGdTrdDependencia;
                            }

                            $observacion = "Dependencias que hacen parte de la gestión del expediente " . implode(", ", $nombresDependencias);

                            $idUserLogeado = Yii::$app->user->identity->id;
                            $idDepeUserLogeado = self::findModelUser($idUserLogeado)->idGdTrdDependencia;
                            HelperLog::logAddExpedient(
                                $idUserLogeado, //Id user
                                $idDepeUserLogeado, // Id dependencia
                                $modelExpediente->idGdExpediente, //Id expediente
                                Yii::$app->params['operacionExpedienteText']['cambioInformacionExpediente'], //Operación
                                $observacion //observación
                            );
                        }
                    }

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => [$modelExpediente->getErrors()['nombreGdExpediente']],
                    'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                $transaction->commit();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successSave'),
                    'data' => $modelExpediente,
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

    public function actionIndice($request){      

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

            $idExpediente = $request['id'];            
      
            $modelIndice = GdIndices::find();
                // ->innerJoin('gdExpedientesInclusion', 'gdIndices.idGdExpedienteInclusion=gdExpedientesInclusion.idGdExpedienteInclusion');
                $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdExpedientesInclusion', ['gdExpedientesInclusion' => 'idGdExpedienteInclusion', 'gdIndices' => 'idGdExpedienteInclusion']);
                $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdExpedienteDocumentos', ['gdExpedienteDocumentos' => 'idGdExpedienteDocumento', 'gdIndices' => 'idGdExpedienteDocumento']);
                $modelIndice = HelperQueryDb::getQuery('leftJoin', $modelIndice, 'gdReferenciasCruzadas', ['gdReferenciasCruzadas' => 'idGdReferenciaCruzada', 'gdIndices' => 'idGdReferenciaCruzada']);
                $modelIndice = $modelIndice->where(['gdExpedientesInclusion.idGdExpediente' => $idExpediente])
                ->orWhere(['gdExpedienteDocumentos.idGdExpediente' => $idExpediente])
                ->orWhere(['gdReferenciasCruzadas.idGdExpediente' => $idExpediente])
                ->andWhere(['gdIndices.estadoGdIndice' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['gdIndices.creacionDocumentoGdIndice' => SORT_ASC]);        
            
            # Limite de la consulta
            $modelIndice->limit(Yii::$app->params['limitRecords']);
            $modelIndice = $modelIndice->all();
            
            //Lista de expedientes
            $dataList = []; 
            $dataBase64Params = "";

            $dataList = [];
            foreach($modelIndice as $indice){

                # Fecha inclusión
                if($indice->idGdExpedienteInclusion != null){
                    $fechaIncorporacion = $indice->gdExpedienteInclusion->creacionGdExpedienteInclusion;
                } else {
                    $fechaIncorporacion = $indice->creacionDocumentoGdIndice;
                }
                if($indice->idGdExpedienteDocumento != null){
                    $creacionRadiDocumento = GdExpedienteDocumentos::find()->where(['idGdExpedienteDocumento' => $indice->idGdExpedienteDocumento])->one();
                    $creacionRadiDocumento = $creacionRadiDocumento->fechaDocGdExpedienteDocumento;
                } else {
                    $creacionRadiDocumento = $indice->creacionDocumentoGdIndice;
                }

                if(is_null($indice->origenGdIndice)){
                    $indice->origenGdIndice = Yii::$app->params['origen']['Electronico'];
                }
                       
                $dataList[] = [
                    'data'                      => $dataBase64Params,
                    'id'                        => $indice->idGdIndice,
                    'indiceContenido'           => $indice->indiceContenidoGdIndice,
                    'nombreRadiDocumento'       => $indice->nombreDocumentoGdIndice,
                    'nombreTipoDocumental'      => $indice->gdTrdTipoDocumental->nombreTipoDocumental,
                    'creacionRadiDocumento'     => $creacionRadiDocumento, 
                    'creacionGdExpedienteInclusion' => $fechaIncorporacion, // $indice->creacionDocumentoGdIndice,
                    'valorHuella'               => $indice->valorHuellaGdIndice,
                    'funcionResumen'            => $indice->funcionResumenGdIndice,
                    'ordenDocumento'            => $indice->ordenDocumentoGdIndice,
                    'paginaInicio'              => $indice->paginaInicioGdIndice,
                    'paginaFinal'               => $indice->paginaFinalGdIndice,
                    'formato'                   => $indice->formatoDocumentoGdIndice,
                    'tamanoRadiDocumento'       => $indice->tamanoGdIndice,
                    'origen'                    => Yii::$app->params['origenNumber'][$indice->origenGdIndice],
                    //'descripcionRadiDocumento' => $indice->descripcionGdIndice,                    
                    //'user' => $indice->usuarioGdIndice,                    
                ];
                           
            }
            
            /*$orden = [];
            //Ordenra por fecha ascendente
            foreach($dataList as $key => $value){
                $orden[] = $value['creacionRadiDocumento'];
            }

            array_multisort($orden, SORT_DESC, $dataList);*/

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'status' => 200,
            ];
            return HelperEncryptAes::encrypt($response, true);
    

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'accessDenied')[1],
                'data' => [],
                'status' => Yii::$app->params['statusErrorAccessDenied'],
            ];
            return HelperEncryptAes::encrypt($response, true); 
        }
    }

    protected function findModel($id)
    {
        if (($model = GdExpedientes::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function findModelDependencia($id)
    {
        if (($model = GdTrdDependencias::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function findModelUser($id)
    {
        if (($model = User::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    protected function findModelRadicado($id)
    {
        if (($model = RadiRadicados::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    /**
     * Función que permite crear el hash por cada radicado incluido en el expediente
     * @param $idExpediente [Int] [Id del expediente de inclusión]
     **/
    public function createHash($idExpediente){

        # Consulta de radicados incluidos en expedientes 
        $tablaInclusion = GdExpedientesInclusion::tableName() . ' AS EIN';
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        $tablaExpediente = GdExpedientes::tableName() . ' AS EX';
        $tablaTipoDoc = GdTrdTiposDocumentales::tableName() . ' AS TD';


        $modelRelation = (new \yii\db\Query())
            ->from($tablaInclusion);
            // ->innerJoin($tablaRadicado, '`rd`.`idRadiRadicado` = `in`.`idRadiRadicado`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaRadicado, ['RD' => 'idRadiRadicado', 'EIN' => 'idRadiRadicado']);
            // ->innerJoin($tablaExpediente, '`ex`.`idGdExpediente` = `in`.`idGdExpediente`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaExpediente, ['EX' => 'idGdExpediente', 'EIN' => 'idGdExpediente']);
            // ->innerJoin($tablaTipoDoc, '`td`.`idGdTrdTipoDocumental` = `rd`.`idTrdTipoDocumental`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaTipoDoc, ['TD' => 'idGdTrdTipoDocumental', 'RD' => 'idTrdTipoDocumental']);
            $modelRelation = $modelRelation->where([ 'EIN.idGdExpedienteInclusion' => $idExpediente])
        ->all();


        $data = [];
        foreach ($modelRelation as $dataRelation) {
            $data = [
                'nombreExpediente' => $dataRelation['nombreGdExpediente'],
                'fechaExpediente' => $dataRelation['creacionGdExpediente'],
                'fechaInclusion' => $dataRelation['creacionGdExpedienteInclusion'],
                'numeroRadicado' => $dataRelation['numeroRadiRadicado'],
                'nombreTipoDoc' => $dataRelation['nombreTipoDocumental'],
            ];
        }

        $encryptedData = HelperEncryptAes::encrypt($data, false);

        $response = [
            'message' => 'Hash creado',
            'encryptedData' => $encryptedData,
            'function' => 'Encrypt AES',  //Función resumen
            'status' => 200,
        ];

        return $response;
    }

    /** La sesrie y la subserie solo pueden ser ingresadas al momento de la creación del expediente
     * En la actualización de expediente no se deben poder modificar estos campos
     */
    public function gdExpTiempoRetencion($idGdTrdSerie, $idGdTrdSubserie, $idGdTrdDependencia)
    {
        $gdTrd = GdTrd::find()
            ->where(['idGdTrdSerie' => $idGdTrdSerie,'idGdTrdSubserie' => $idGdTrdSubserie, 'idGdTrdDependencia' => $idGdTrdDependencia])
            ->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
        ->one();

        if ($gdTrd == null) {
            return false;
        }

        $gdTrdSubserie = GdTrdSubseries::findOne(['idGdTrdSubserie' => $gdTrd->idGdTrdSubserie]);

        if(isset($gdTrdSubserie)){
            return [
                'tiempoGestionGdExpedientes' => date("Y-m-d H:i", strtotime(date("Y-m-d H:i") . "+{$gdTrdSubserie->tiempoGestionGdTrdSubserie} year")),
                'tiempoCentralGdExpedientes' => date("Y-m-d H:i", strtotime(date("Y-m-d H:i") . "+{$gdTrdSubserie->tiempoCentralGdTrdSubserie} year"))
            ];
        }

        return false;
    }

    /** 
     * Funcion que permite descargar un documento anexado al expediente
     **/
    public function actionDownloadDocument() 
    {
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);
        
        // if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

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

                foreach ($request['ButtonSelectedData'] as $key => $item) {

                    $idDocumento = $item['id'];
                    $modelExpedienteDocumento = GdExpedienteDocumentos::findOne(['idGdExpedienteDocumento' => $idDocumento]);

                    // Nombre del archivo para generar
                    $fileName = $modelExpedienteDocumento->nombreGdExpedienteDocumento;
                    $rutaFile = $modelExpedienteDocumento->rutaGdExpedienteDocumento.'/'.$fileName;

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
        // } else {
        //     Yii::$app->response->statusCode = 200;
        //     $response = [
        //         'message' => Yii::t('app','accessDenied')[1],
        //         'data' => [],
        //         'status' => Yii::$app->params['statusErrorAccessDenied'],
        //     ];
        //     return HelperEncryptAes::encrypt($response, true);
        // }
    }

    /**
     * Servicio para descargar todos los documentos que tiene un expediente
     **/
    public function actionDownloadFileDocuments(){


        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);

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

                $idExpediente = $request['id'];      

                $modelExpediente = $this->findModel($idExpediente);
                $nombreExpediente = $modelExpediente->nombreGdExpediente;

                /*** Validar creación de la carpeta ***/                             
                $pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDownloads'] . '/';
                $filePath = $pathUploadFile . $nombreExpediente.'.zip'; 
                 
                /** Eliminar archivo anterior si existe para que no den conflictos los archivos que se agregan al Zip */
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                  
                // Verificar ruta de la carpeta y crearla en caso de que no exista
                if (!file_exists($pathUploadFile)) {
                    if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                        
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app','errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    } 
                }

                //Generar indice en excel
                $excelData = HelperLoads::getIndiceElectronico($idExpediente);
                //$creacionExcel = HelperGenerateExcel::generarExcel(Yii::$app->params['routeDownloads'], 'indice_electronico_'.$modelExpediente->numeroGdExpediente.'.xlsx', $excelData[0], [], [], [], $excelData[1]);

                # Consulta todos los documentos de radicados que tenga un expediente
                $tablaRadicadoDoc = RadiDocumentos::tableName() . ' AS RD';
                $tablaDocumentoPrin = RadiDocumentosPrincipales::tableName() . ' AS DP';
                $tablaRadicado = RadiRadicados::tableName() . ' AS RR';
                $tablaExpInclusion = GdExpedientesInclusion::tableName() . ' AS EX';
                $tablaInformados = RadiInformados::tableName(). ' AS RI';

                #Consulta de documentos Pricipales                
                $modelDocumentosPrincipales = (new \yii\db\Query())
                    ->from($tablaDocumentoPrin);
                    // ->innerJoin($tablaExpediente, '`ex`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                    $modelDocumentosPrincipales = HelperQueryDb::getQuery('innerJoinAlias', $modelDocumentosPrincipales, $tablaExpInclusion, ['EX' => 'idRadiRadicado', 'DP' => 'idRadiRadicado']);                   
                    $modelDocumentosPrincipales = $modelDocumentosPrincipales                        
                        ->andWhere(['EX.idGdExpediente' => $idExpediente])
                        ->andWhere([ 'or',
                            [ 'estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Combinado'] ],
                            [ 'estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['procesandoFirma'] ],
                            [ 'estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado'] ]]
                        )
                ->all();
                

                # Consulta de documentos publicos, que sean del usuario logueado o que haya sido informado
                $modelRelation = (new \yii\db\Query())
                    ->from($tablaRadicadoDoc);
                    // ->innerJoin($tablaExpediente, '`ex`.`idRadiRadicado` = `rd`.`idRadiRadicado`')
                    $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaExpInclusion, ['EX' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                    $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaRadicado, ['RR' => 'idRadiRadicado', 'RD' => 'idRadiRadicado']);
                    $modelRelation = HelperQueryDb::getQuery('leftJoinAlias', $modelRelation, $tablaInformados, ['RI' => 'idRadiRadicado', 'RR' => 'idRadiRadicado']);
                    $modelRelation = $modelRelation
                        ->orWhere(['RD.isPublicoRadiDocumento' => Yii::$app->params['SiNoText']['Si']])
                        ->orWhere(['RR.user_idTramitador' => Yii::$app->user->identity->id])
                        ->orWhere(['RI.idUser' => Yii::$app->user->identity->id])
                        ->andWhere(['EX.idGdExpediente' => $idExpediente])
                ->all();

                # Consulta todos los documentos anexados individualmente al expediente
                $modelDocoumentoExpediente = GdExpedienteDocumentos::find()->where(['idGdExpediente' => $idExpediente]);

                //Si el usuario logueado es diferente al dueño del expediente, se restringe la consulta a los documentos públicos
                if(Yii::$app->user->identity->id != $modelExpediente->idUser){
                    $modelDocoumentoExpediente->andWhere(['isPublicoGdExpedienteDocumento' => Yii::$app->params['SiNoText']['Si']]); 
                }  
                   
                $modelDocoumentoExpediente = $modelDocoumentoExpediente->all();

                # Consulta todos las referencias cruzadas del expediente
                $modelGdReferenciasCruzadas = GdReferenciasCruzadas::find()->where(['idGdExpediente' => $idExpediente])->all();

                /** Validar el expediente no tiene documentos a procesar, o no son documentos publicos */
                if (count($modelRelation) == 0 && count($modelDocoumentoExpediente) == 0 && count($modelDocumentosPrincipales) == 0 && count($modelGdReferenciasCruzadas) == 0) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'withoutPermissionDocuments')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                /** Copiar archivo ZIP base en la ruta establecida para el nuevo archivo */
                if (!copy(Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeFileZipBase'], $filePath)) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                        'info' => '',
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }            
                /** Fin Copiar archivo ZIP base en la ruta establecida para el nuevo archivo */

                /*******  Añadir documentos al zip  ******/              
                $zip = new \ZipArchive();  

                if (!$zip->open($filePath, \ZipArchive::CREATE)) {

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } 
           
                # Se agrega las plantillas existentes                
                foreach($modelDocumentosPrincipales as $documento){             

                    # Nombre del documento para almacenarlo en el zip
                    $namePdf = $documento['rutaRadiDocumentoPrincipal']; 
                    $zip->addFile($namePdf, $documento['nombreRadiDocumentoPrincipal'] .'.'.$documento['extensionRadiDocumentoPrincipal']); 
                }

                # Se agrega los anexos existentes del radicado
                $idRadiDocumento = 0;
                foreach($modelRelation as $documento){

                    #Si documento actual es difrente al anterior, lo agrega al zip
                    if($documento['idRadiDocumento'] !== $idRadiDocumento){
                        # Nombre del documento para almacenarlo en el zip
                        $namePdf = $documento['rutaRadiDocumento'] . $documento['nombreRadiDocumento']; 
                        $zip->addFile($namePdf, $documento['nombreRadiDocumento']); 
                    }
                }

                # Se agrega los documentos manuales del expediente
                foreach($modelDocoumentoExpediente as $documento){
                    
                    # Nombre del documento para almacenarlo en el zip
                    $namePdf = $documento['rutaGdExpedienteDocumento'] . $documento['nombreGdExpedienteDocumento'];
                    $zip->addFile($namePdf, $documento['nombreGdExpedienteDocumento']); 
                }

                # Se agrega los documentos de referencia cruzada del expediente
                foreach($modelGdReferenciasCruzadas as $documento){
                    # Nombre del documento para almacenarlo en el zip
                    $namePdf = $documento['rutaGdReferenciasCruzada'] . $documento['nombreArchivoGdReferenciasCruzada'];
                    $zip->addFile($namePdf, $documento['nombreArchivoGdReferenciasCruzada']); 
                }

                # Agregar excel del indice electronico
                // if($creacionExcel['status']){                   
                //     $zip->addFile($creacionExcel['rutaDocumento'], 'indice_electronico_'.$modelExpediente->numeroGdExpediente.'.xlsx');
                // }
                
                # Agregar archivo indice xml
                //$modelExpediente = $this->findModel($idExpediente);
                $nombreXml = 'indice_electronico_'.$modelExpediente->numeroGdExpediente . '.xml';
                $pathXml =   Yii::getAlias('@webroot') . Yii::$app->params['routeElectronicIndex'];

                if (file_exists($pathXml . $nombreXml)) {                    
                    $zip->addFile($pathXml .  $nombreXml, $nombreXml);
                }

                # Se agrega pdf del indice electronico si el expediente esta cerrado.
                if($modelExpediente->estadoGdExpediente == Yii::$app->params['statusExpedienteText']['Cerrado']){

                    $route = $modelExpediente->rutaCerrarGdExpediente; 
                    $path = Yii::getAlias('@webroot').'/'.Yii::$app->params['routeClosedExpedient'].'/';
                    $fileName = str_replace($path,"",$route);

                    if (file_exists($path . $fileName)) 
                        $zip->addFile($route, $fileName);                     
                }

                # Cuando el archivo zip esta vacio 
                if ($zip->numFiles == 0) {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'filesWithoutDocuments')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                } 

                $zip->close(); 
                $dataFile = base64_encode(file_get_contents($filePath));
       
                /** Eliminando archivo temporal generado */
                unlink($filePath);
                // unlink($creacionExcel['rutaDocumento']);

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => [],
                    'fileName' => $nombreExpediente. '.zip',
                    'status' => 200,
                ];

                /***    Log de Auditoria  ***/
                HelperLog::logAdd(
                    false,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['DownloadFile'].' zip de los documentos del expediente: '.$modelExpediente->numeroGdExpediente, //texto para almacenar en el evento
                    [], //DataOld
                    [], //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria   ***/

                $return = HelperEncryptAes::encrypt($response, true);

                // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                $return['datafile'] = $dataFile;

                return $return;
            
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
     * Servicio que permite excluir un documento cargado de forma manual o un radicado del expediente.
     * Esta acción se encontrará en el módulo de expedientes,
     * con la condición de que el expediente este activo.
     */
    public function actionExcludeExpedient()
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

 
                $arrayFileNumber = [];      //Array de numero de radicados
                $arrayDocumentName = [];    //Array de los nombres del documento
                $year = date('Y');          //Año de la carpeta del documento          
                $nameDocument = [];         //Nombres de documentos en indices 
                $observationExpedient = '';        
                $emptyNumber = 'N/A';       //Numero radicado vacio
                
                $transaction = Yii::$app->db->beginTransaction();

                # Información del expediente
                $idExpedient = $request['idGdExpediente'];
                $modelExpedient = GdExpedientes::findOne(['idGdExpediente' => $idExpedient]);
                
                # Transaccion de excluir expediente
                $modelTransaction = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'excludeTheFile']);
                $idTransaction = $modelTransaction->idCgTransaccionRadicado;

                foreach($request['ButtonSelectedData'] as $dataRequest){

                    # Se valida el numero radicado
                    $coincidence = strripos($emptyNumber, $dataRequest['numeroRadiRadicado']);

                    # Si el numero radicado es 'N/A' entonces, se ha seleccionado un documento cargado de forma manual
                    // if($coincidence !== false) {                    

                    if ($dataRequest['model'] == 'GdReferenciasCruzadas') {

                        $idDocument = $dataRequest['id'];
                        $arrayDocumentName[] = $dataRequest['nombreRadiDocumento'];

                        # Carpeta donde se encuentra almacenado el documento cargado de forma manual
                        $pathFile = Yii::getAlias('@webroot')
                            . "/" . Yii::$app->params['bodegaRadicados']
                            . "/" . $year
                            . "/" . $modelExpedient->numeroGdExpediente 
                            . "/" ;

                        # Nombre del archivo
                        $file = $pathFile. $dataRequest['nombreRadiDocumento'];

                        # Verificar que la carpeta exista y elimina el documento
                        if (file_exists($file)) {
                            FileHelper::unlink($file);
                        }

                        # Elimina los registros de los documentos que se agregan en indice
                        $deleteIndex = GdIndices::deleteAll(
                            [
                                'AND',
                                ['idGdReferenciaCruzada' => (int) $idDocument]
                            ]
                        );

                        # Elimina la los tipos de anexos asociados a la referencia cruzada expediente
                        $deleteGdReferenciaTiposAnexos = GdReferenciaTiposAnexos::deleteAll(
                            [
                                'AND',
                                ['idGdReferenciaCruzada' => (int) $idDocument],
                            ]
                        );

                        # Elimina la referencia cruzada expediente
                        $deleteExpedient = GdReferenciasCruzadas::deleteAll(
                            [
                                'AND',
                                ['idGdReferenciaCruzada' => (int) $idDocument],
                                ['idGdExpediente' => (int) $idExpedient]
                            ]
                        );

                    } elseif ($dataRequest['model'] == 'GdExpedienteDocumentos') {

                        $idDocument = $dataRequest['id'];       
                        $arrayDocumentName[] = $dataRequest['nombreRadiDocumento'];      
                        
                        # Carpeta donde se encuentra almacenado el documento cargado de forma manual
                        $pathFile = Yii::getAlias('@webroot')                            
                            . "/" . Yii::$app->params['bodegaRadicados']
                            . "/" . $year                            
                            . "/" . $modelExpedient->numeroGdExpediente 
                            . "/" ;   
                        
                        # Nombre del archivo
                        $file = $pathFile. $dataRequest['nombreRadiDocumento'];

                        # Verificar que la carpeta exista y elimina el documento
                        if (file_exists($pathFile)) {
                            FileHelper::unlink($file);
                        }

                        # Elimina los registros de los documentos que se agregan en indice
                        $deleteIndex = GdIndices::deleteAll(
                            [   
                                'AND', 
                                ['idGdExpedienteDocumento' => (int) $idDocument]
                            ]
                        );

                        # Elimina el documento cargado al expediente
                        $deleteExpedient = GdExpedienteDocumentos::deleteAll(
                            [   
                                'AND', 
                                ['idGdExpedienteDocumento' => (int) $idDocument],
                                ['idGdExpediente' => (int) $idExpedient]
                            ]
                        );


                    } else { // Sino, significa que es un documento del radicado

                        # Se valida que no se repita el numero de radicado
                        if(!in_array($dataRequest['numeroRadiRadicado'], $arrayFileNumber)){
                            $arrayFileNumber[] = $dataRequest['numeroRadiRadicado'];
                        }
                    }                   
                }


                # Se realiza la consulta de los radicados seleccionados para excluirlos del expediente
                $modelFile = RadiRadicados::findAll(['numeroRadiRadicado' => $arrayFileNumber]);
                   
                foreach($modelFile as $dataFile) {

                    # Consulta los documentos que son del radicado en el indice
                    $modelIndex = GdIndices::findAll(['idGdExpedienteInclusion' => $dataFile->gdExpedienteInclusion->idGdExpedienteInclusion]);
                    
                    foreach($modelIndex as $dataIndex){
                        $nameDocument[] = $dataIndex->nombreDocumentoGdIndice;
                    }

                    # Elimina registros de los documentos que se agregan en indice
                    $deleteIndex = GdIndices::deleteAll(
                        [   
                            'AND', 
                            ['idGdExpedienteInclusion' => (int) $dataFile->gdExpedienteInclusion->idGdExpedienteInclusion]
                        ]
                    );

                    # Elimina la relación del radicado con el expediente
                    $deleteExpedient = GdExpedientesInclusion::deleteAll(
                        [   
                            'AND', 
                            ['idRadiRadicado' => (int) $dataFile->idRadiRadicado],
                            ['idGdExpediente' => (int) $idExpedient]
                        ]
                    );


                    /***    Log de Radicados  ***/
                    HelperLog::logAddFiling(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $dataFile->idRadiRadicado, //Id radicado
                        $idTransaction,
                        Yii::$app->params['eventosLogTextRadicado']['excludeExpedient'] .$dataFile->numeroRadiRadicado. " del expediente: ".$modelExpedient->nombreGdExpediente, //observación 
                        $dataFile,
                        array() //No validar estos campos
                    );
                    /***  Fin Log de Radicados  ***/
                }

                # Array de radicados
                if( count($arrayFileNumber) >= 1 ){   
                    
                    /***    Log de Auditoria para radicados ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['excludeFileExpedient'] . implode(", ", $arrayFileNumber)." del expediente: ".$modelExpedient->nombreGdExpediente. ", de la tabla gdExpedientesInclusion y se excluye el(los) documento(s): ".implode(", ", $nameDocument).", de la tabla gdIndices", //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );     
                    /***    Fin log Auditoria   ***/

                    # Observación para el log expediente
                    $observationExpedient = "Se excluyó (el)los siguiente(s) radicado(s): ".implode(", ",$arrayFileNumber)." del expediente: ". $modelExpedient->nombreGdExpediente.".";
                } 

                # Array de documentos
                if ( count($arrayDocumentName) >= 1 ) {

                    /***  Log de Auditoria para documentos ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['excludeDocumentExpedient'] . implode(", ", $arrayDocumentName)." del expediente: ".$modelExpedient->nombreGdExpediente. ", en la tabla gdExpedienteDocumentos, y se excluye el(los) documento(s): ".implode(", ", $arrayDocumentName).", de la tabla gdIndices", //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );     
                    /***    Fin log Auditoria   ***/

                    # Observación para el log expediente
                    $observationExpedient .= " Se excluyó (el)los siguiente(s) documento(s): ".implode(", ",$arrayDocumentName)." del expediente: ". $modelExpedient->nombreGdExpediente.".";
                }
                

                /***    Log de Expediente  ***/
                HelperLog::logAddExpedient(
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                    $idExpedient, //Id expediente
                    Yii::$app->params['operacionExpedienteText']['ExcluirExpediente'], //Operación
                    $observationExpedient //observación
                );
                /***  Fin  Log de Expediente  ***/  
                
               
                # Evaluar respuesta de datos guardados #
                if ($deleteExpedient == true) {

                    $transaction->commit();                    

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successExclude'),
                        'data' => [],
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {

                    $transaction->rollBack();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','errorValidacion'),
                        'data' => [],
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
     * Servicio que permite cerrar el expediente
     * con la condición de que el expediente este activo.
     * Y genera un pdf con el indice electrónico
     */
    public function actionClosedExpedient()
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
                $valid = true;
                $errors = [];
                $arrayModelExpedient = [];
                $observationExpedient = '';        
                $generateDate = date('Y-m-d H:i:s');  //Fecha de cierre del expediente     

                # Datos del usuario logueado
                $modelUserLogged = UserDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
                $user = $modelUserLogged->nombreUserDetalles.' '.$modelUserLogged->apellidoUserDetalles;

                $modelDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);
                $dependence = $modelDependence->nombreGdTrdDependencia;

                $transaction = Yii::$app->db->beginTransaction();

                # Información del expediente
                foreach($request['ButtonSelectedData'] as $dataExpedient){

                    # Id del expediente
                    $idExpedient = $dataExpedient['id'];

                    # Observacion del cierre del expediente
                    $observationForm = $request['data']['observacion'];

                    $modelExpedient = self::findModel($idExpedient);

                    # Se obtiene el tiempo central de la subserie
                    $timeSubserie = $modelExpedient->gdTrdSubserie->tiempoCentralGdTrdSubserie;

                    # Se suma al año de la fecha central el tiempo de la subserie
                    $year = date("Y",strtotime($modelExpedient->tiempoCentralGdExpedientes." + ". $timeSubserie ." year"));
                    $dateExpedient = date($year."-m-d H:i:s");

                    # Se actualiza la fecha central del expediente con el calculo y el estado cerrado.
                    $modelExpedient->tiempoCentralGdExpedientes = $dateExpedient;
                    $modelExpedient->estadoGdExpediente = Yii::$app->params['statusExpedienteText']['Cerrado'];

                    # Array donde contendrá del modelo del expediente cerrado
                    $arrayModelExpedient[] = $modelExpedient;  

                    if(!$modelExpedient->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelExpedient->getErrors());  
                    }

                    /***  Log de Auditoria ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['closedExpedient'] . $modelExpedient->numeroGdExpediente. ", se procederá a correr los tiempos de retención, con la siguiente observación: ".$observationForm.", en la tabla gdExpediente", //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );     
                    /***    Fin log Auditoria   ***/


                    # Información del usuario logueado.
                    $modelUser = UserValidate::findOne(['id' => Yii::$app->user->identity->id]);

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

                        } else {

                            # Se realiza la consulta para obtener los ids del expediente de inclusion
                            $modelInclusion = GdExpedientesInclusion::findAll(['idGdExpediente' => $idExpedient]);

                            $idsInclusion = [];
                            foreach($modelInclusion as $dataInclusion){
                                $idsInclusion[] = $dataInclusion->idGdExpedienteInclusion;
                            }

                            # Se realiza la consulta en documentos para obtener su id.
                            $modelDocument = GdExpedienteDocumentos::findAll(['idGdExpediente' => $idExpedient]);

                            $idsDocument = [];
                            foreach($modelDocument as $dataDocument){
                                $idsDocument[] = $dataDocument->idGdExpedienteDocumento;
                            }

                            # Se realiza la consulta en indices según el documento cargado
                            if(count($idsInclusion) > 0 || count($idsDocument) > 0)  {

                                $modelIndex = GdIndices::find()
                                    ->where(['idGdExpedienteInclusion' => $idsInclusion])
                                    ->orWhere(['idGdExpedienteDocumento' => $idsDocument]); 
                                $modelIndex = $modelIndex->all();

                                # Se procede a actualizar los campos en la tabla de de indice                            
                                foreach($modelIndex as $dataIndex){

                                    # Se inactiva todo los documentos del expediente y se actualiza la fecha firma en indices
                                    $dataIndex->fechaFirmaGdIndice = date('Y-m-d');
                                    $dataIndex->estadoGdIndice = Yii::$app->params['statusTodoText']['Inactivo'];

                                    /** Procesar el errores de la fila */
                                    if (!$dataIndex->save()) {

                                        $transaction->rollBack();                                    
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app','errorValidacion'),
                                            'data' => $dataIndex->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }
                                }

                                /***  Log de Auditoria ***/
                                HelperLog::logAdd(
                                    false,
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->username, //username
                                    Yii::$app->controller->route, //Modulo
                                    Yii::$app->params['eventosLogText']['closedExpedient'] . $modelExpedient->numeroGdExpediente. ", y firmado del índice electrónico, que indica que ya no puede incluir más documentos al expediente.", //texto para almacenar en el evento
                                    [], //DataOld
                                    [], //Data
                                    array() //No validar estos campos
                                );     
                                /***    Fin log Auditoria   ***/


                                $observationExpedient = Yii::$app->params['eventosLogText']['closedExpedient'] . $modelExpedient->numeroGdExpediente. ", se procederá a contar los tiempos de retención y a cerrar el indice electrónico, con la siguiente observación: ".$observationForm;
                                
                                /***    Log de Expediente  ***/
                                HelperLog::logAddExpedient(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $idExpedient, //Id expediente
                                    Yii::$app->params['operacionExpedienteText']['CerrarExpediente'], //Operación
                                    $observationExpedient //observación
                                );
                                /***  Fin  Log de Expediente  ***/ 

                                # Se genera el pdf al cerrar el expediente
                                $pdfStructure = $this->createPdfClosedExpedient($modelIndex, $modelExpedient, $user, $dependence, $generateDate);

                                $createPdf[] = $pdfStructure['html'];       
                                $style = $pdfStructure['style'];
                               

                            } else { // Sino significa que no hay información para el indice electrónico.

                                $valid = false;
                                $data = [Yii::t('app', 'errorIndexElectronic')];                               
                            }
                        }
                    }
                }

                # Si es falso significa que no genera un pdf del indice electrónico y cierra el expediente
                if($valid == false){

                    $transaction->commit();
                
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $data,
                        'status' => 200,
                    ];                        
                
                    return HelperEncryptAes::encrypt($response, true);

                } else { // Cierra el expediente y genera el pdf                

                    # Nombre del archivo del pdf
                    $fileName = 'indice_electronico_'.$generateDate.'.pdf';

                    # Se construye el pdf con mpdf
                    $generatePdf = PdfController::generatePdf($createPdf, Yii::$app->params['routeClosedExpedient'], $fileName, $style, 'L');

                    if($generatePdf['status'] == false){

                        $transaction->rollBack();
    
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => $generatePdf['message'], 
                            'data' => $generatePdf['errors'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
    
                    }
        
                    # Se obtiene la ruta del pdf para almacenarla en expedientes
                    $rutaPdf = $generatePdf['rutaDocumento'];
    
                    foreach($arrayModelExpedient as &$expedient){
    
                        $expedient->rutaCerrarGdExpediente = $rutaPdf;
    
                        if (!$expedient->save()) {
    
                            $transaction->rollBack();
    
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => $expedient->getErrors(),
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    # Evaluar respuesta de datos guardados #
                    if ($saveDataValid == true) {
                        
                        # Validación del archivo generado
                        if(file_exists($rutaPdf)) {

                            $transaction->commit();

                            //Lee el archivo dentro de una cadena en base 64
                            $dataFile = base64_encode(file_get_contents($rutaPdf));
                        
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','closedExpedientPdf'), //Mensaje del pdf generado
                                'fileName' => $fileName, 
                                'status' => 200,
                            ];                        
                        
                            $return = HelperEncryptAes::encrypt($response, true);
                            
                            // Anexando el archivo por fuera de la cadena encriptada para que no sea tan pesada la respuesta del servicio
                            $return['datafile'] = $dataFile;

                            return $return;

                        } else {

                            $transaction->rollBack();

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => Yii::t('app', 'fileWithoutInformationClasification') ],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }

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
     * Servicio que permite cerrar el expediente
     * con la condición de que el expediente este activo.
     * Y genera un pdf con el indice electrónico
     */
    public function actionOpenExpedient()
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
                $valid = true;
                $errors = [];
                $arrayModelExpedient = [];
                $observationExpedient = '';        
                $generateDate = date('Y-m-d H:i:s');  //Fecha de cierre del expediente     

                # Datos del usuario logueado
                // $modelUserLogged = UserDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
                // $user = $modelUserLogged->nombreUserDetalles.' '.$modelUserLogged->apellidoUserDetalles;

                // $modelDependence = GdTrdDependencias::findOne(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia]);
                // $dependence = $modelDependence->nombreGdTrdDependencia;

                $transaction = Yii::$app->db->beginTransaction();

                # Información del expediente
                foreach($request['ButtonSelectedData'] as $dataExpedient){

                    # Id del expediente
                    $idExpedient = $dataExpedient['id'];

                    $modelExpedient = self::findModel($idExpedient);

                    # Se obtiene el tiempo central de la subserie
                    $timeSubserie = $modelExpedient->gdTrdSubserie->tiempoCentralGdTrdSubserie;

                    # Se suma al año de la fecha central el tiempo de la subserie
                    $year = date("Y",strtotime($modelExpedient->tiempoCentralGdExpedientes." + ". $timeSubserie ." year"));
                    $dateExpedient = date($year."-m-d H:i:s");

                    # Se actualiza la fecha central del expediente con el calculo y el estado cerrado.
                    $modelExpedient->tiempoCentralGdExpedientes = $dateExpedient;
                    $modelExpedient->estadoGdExpediente = Yii::$app->params['statusExpedienteText']['Abierto'];

                    # Array donde contendrá del modelo del expediente cerrado
                    $arrayModelExpedient[] = $modelExpedient;  

                    if(!$modelExpedient->save()) {
                        $saveDataValid = false;
                        $errors = array_merge($errors, $modelExpedient->getErrors());  
                    }

                    /***  Log de Auditoria ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['openExpedient'] . $modelExpedient->numeroGdExpediente. ", por lo cual se procederá a detener los tiempos de retención", //texto para almacenar en el evento
                        [], //DataOld
                        [], //Data
                        array() //No validar estos campos
                    );     
                    /***    Fin log Auditoria   ***/

                    # Información del usuario logueado.
                    $modelUser = UserValidate::findOne(['id' => Yii::$app->user->identity->id]);

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

                        } else {

                            # Se realiza la consulta para obtener los ids del expediente de inclusion
                            $modelInclusion = GdExpedientesInclusion::findAll(['idGdExpediente' => $idExpedient]);

                            $idsInclusion = [];
                            foreach($modelInclusion as $dataInclusion){
                                $idsInclusion[] = $dataInclusion->idGdExpedienteInclusion;
                            }

                            # Se realiza la consulta en documentos para obtener su id.
                            $modelDocument = GdExpedienteDocumentos::findAll(['idGdExpediente' => $idExpedient]);

                            $idsDocument = [];
                            foreach($modelDocument as $dataDocument){
                                $idsDocument[] = $dataDocument->idGdExpedienteDocumento;
                            }

                            # Se realiza la consulta en indices según el documento cargado
                            if(count($idsInclusion) > 0 || count($idsDocument) > 0)  {

                                $modelIndex = GdIndices::find()
                                    ->where(['idGdExpedienteInclusion' => $idsInclusion])
                                    ->orWhere(['idGdExpedienteDocumento' => $idsDocument]); 
                                $modelIndex = $modelIndex->all();

                                # Se procede a actualizar los campos en la tabla de de indice                            
                                foreach($modelIndex as $dataIndex){

                                    # Se inactiva todo los documentos del expediente y se actualiza la fecha firma en indices
                                    $dataIndex->fechaFirmaGdIndice = date('Y-m-d');
                                    $dataIndex->estadoGdIndice = Yii::$app->params['statusTodoText']['Activo'];

                                    /** Procesar el errores de la fila */
                                    if (!$dataIndex->save()) {

                                        $transaction->rollBack();                                    
                                        Yii::$app->response->statusCode = 200;
                                        $response = [
                                            'message' => Yii::t('app','errorValidacion'),
                                            'data' => $dataIndex->getErrors(),
                                            'status' => Yii::$app->params['statusErrorValidacion'],
                                        ];
                                        return HelperEncryptAes::encrypt($response, true);
                                    }
                                }

                                $observationExpedient = Yii::$app->params['eventosLogText']['openExpedient'] . $modelExpedient->numeroGdExpediente;
                                
                                /***    Log de Expediente  ***/
                                HelperLog::logAddExpedient(
                                    Yii::$app->user->identity->id, //Id user
                                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                                    $idExpedient, //Id expediente
                                    Yii::$app->params['operacionExpedienteText']['AbrirExpediente'], //Operación
                                    $observationExpedient //observación
                                );
                                /***  Fin  Log de Expediente  ***/ 

                            }
                        }
                    }
                }

                # Si es falso significa que no genera un pdf del indice electrónico y cierra el expediente
                if($saveDataValid == true){

                    $transaction->commit();
                
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','openExpedientPdf'), //Mensaje del pdf generado
                        'status' => 200,
                    ];                        
                
                    return HelperEncryptAes::encrypt($response, true);

                }

                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app','errorValidacion'),
                    'data' => $errors,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);
                   
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
     * Funcion que construye la estructura del pdf del indice electrónico al momento de cerrar el expediente
     * @param $modelIndex [model] [Modelo del indice]
     * @param $modelExpedient [model] [Modelo del expediente]
     * @param $user [int] [Id del usuario que cerró el expediente]
     * @param $dependence [int] [Id de la dependencia del usuario que cerró el expediente]
     * @param $generateDate [date] [Fecha en que se cerró el expediente]
     **/ 
    public function createPdfClosedExpedient($modelIndex, $modelExpedient, $user, $dependence, $generateDate)
    {

        $customerName = Yii::$app->params['cliente'];

        # Ruta de la imagen
        $imagePath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogoPdf'];

        # Nombre y numero del expediente
        $expedient = $modelExpedient->nombreGdExpediente.' - '. $modelExpedient->numeroGdExpediente;

        # Nombre de serie y subserie
        $serieSubserie = $modelExpedient->gdTrdSerie->nombreGdTrdSerie.' - '.$modelExpedient->gdTrdSubserie->nombreGdTrdSubserie;

       
        # Estilo de la tabla
        $stylesheet = '
            table {
                border-collapse: collapse;
            }
            table, th, td {
                border: 1px solid black;
            }
            .wrap1{ 
                /* max-width: 200px; */
                white-space: nowrap;      /* CSS3 */  
                overflow:wrap; 
                font-size: 10px; 
            }
            footer {
                text-align: left;
                font-size: 10px;
            }
            ';

        $html = 
            '<table class="wrap1">
                <tr>
                    <th rowspan="5" colspan ="5">
                        <img src=":rutaLogo" style="width:150px;">
                    </th>
                    <th colspan="8">ÍNDICE ELECTRÓNICO</th>
                </tr>  
                <tr>
                    <td colspan="8" style="text-align: center;">PROCESO GESTIÓN DOCUMENTAL - ENTIDAD :nombreCliente</td>
                </tr>            
                <tr>
                    <td colspan="8" style="text-align: center;">EXPEDIENTE: :expediente</td>
                </tr> 
                <tr>
                    <td colspan="8" style="text-align: center;">SERIE-SUBSERIE: :serieSubserie</td>
                </tr> 
                <tr>
                    <td colspan="8" style="text-align: center;">FECHA GENERACIÓN: :fechaGeneracion</td>
                </tr> 

                <tr>
                    <th>Índice contenido</th>
                    <th>Nombre documento</th>
                    <th>Tipo documental</th>
                    <th>Fecha documento</th>
                    <th>Fecha de inclusión</th>
                    <th>Valor de huella</th>
                    <th>Función resumen</th>
                    <th>Orden documento</th>
                    <th>Pág. Inicio</th>
                    <th>Pág. Final</th>
                    <th>Formato</th>
                    <th>Tamaño</th>
                    <th>Origen</th>
                </tr> 
            ';

            for($i = 0; $i < count($modelIndex); $i++) {
                $html .='
                    <tr>
                        <td>:indice'.$i.'</td>
                        <td>:documento'.$i.'</td>
                        <td>:tipoDocumental'.$i.'</td>
                        <td>:fechaDocumento'.$i.'</td>
                        <td>:fechaInclusion'.$i.'</td>
                        <td>:valorHuella'.$i.'</td>
                        <td>:funcionResumen'.$i.'</td>
                        <td>:orden'.$i.'</td>
                        <td>:paginaInicio'.$i.'</td>
                        <td>:paginaFinal'.$i.'</td>
                        <td>:formato'.$i.'</td>
                        <td>:tamano'.$i.'</td>
                        <td>:origen'.$i.'</td>
                    </tr>
                ';
            }            

            $html .='
                </table>
                <p>&nbsp;</p>
              
                <footer>                    
                    <p>
                        <b>Persona que cerró el expediente:</b> :usuario
                    </p>
                    <p>
                        <b>Dependencia cerró expediente:</b> :dependencia
                    </p>
                    <p>
                        <b>Fecha en que se cerró el expediente:</b> :fechaCerrado
                    </p>
                </footer>
            <p>&nbsp;</p>';        

        # Wildcard para el reemplazo en el html
        $token = array(
            ':rutaLogo',
            ':nombreCliente',
            ':expediente',
            ':serieSubserie',
            ':fechaGeneracion',
            ':usuario',
            ':dependencia',
            ':fechaCerrado',
        );

        for($i = 0; $i < count($modelIndex); $i++) {
            array_push($token, ':indice'.$i);
            array_push($token, ':documento'.$i);
            array_push($token, ':tipoDocumental'.$i);
            array_push($token, ':fechaDocumento'.$i);
            array_push($token, ':fechaInclusion'.$i);
            array_push($token, ':valorHuella'.$i);
            array_push($token, ':funcionResumen'.$i);
            array_push($token, ':orden'.$i);
            array_push($token, ':paginaInicio'.$i);
            array_push($token, ':paginaFinal'.$i);
            array_push($token, ':formato'.$i);
            array_push($token, ':tamano'.$i);
            array_push($token, ':origen'.$i);
        }

        # Valores para los wildcards
        $values = [
            $imagePath,
            $customerName, 
            $expedient,
            $serieSubserie,
            $generateDate,
            $user,
            $dependence,
            $generateDate,           
        ];

        foreach ($modelIndex as $dataIndex) {

            array_push($values, $dataIndex->indiceContenidoGdIndice);
            array_push($values, $dataIndex->nombreDocumentoGdIndice);
            array_push($values, $dataIndex->gdTrdTipoDocumental->nombreTipoDocumental);
            array_push($values, $dataIndex->creacionDocumentoGdIndice);            

            # Fecha inclusión
            if($dataIndex->idGdExpedienteInclusion != null){
                array_push($values, $dataIndex->gdExpedienteInclusion->creacionGdExpedienteInclusion);

            }  elseif($dataIndex->idGdExpedienteDocumento) {
                array_push($values, $dataIndex->gdExpedienteDocumento->creacionGdExpedienteDocumento);
            } 
            
            array_push($values, $dataIndex->valorHuellaGdIndice);
            array_push($values, $dataIndex->funcionResumenGdIndice);
            array_push($values, $dataIndex->ordenDocumentoGdIndice);
            array_push($values, $dataIndex->paginaInicioGdIndice);
            array_push($values, $dataIndex->paginaFinalGdIndice);
            array_push($values, $dataIndex->formatoDocumentoGdIndice);
            array_push($values, $dataIndex->tamanoGdIndice);

            # Origen
            if(isset($dataIndex->origenGdIndice)){
                array_push($values, Yii::$app->params['origenNumber'][$dataIndex->origenGdIndice]);
            }
            
        }

        $pdf = str_replace($token, $values, $html);

        return [
            'html' => $pdf,
            'style' => $stylesheet,
            'status' => 'OK',
        ];

    }

    /** 
     * Función que valida el estado del expediente para saber si muestra notificación o no 
     **/
    public function actionNotificationStatus(){

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

            # Información del expediente
            foreach($request['ButtonSelectedData'] as $dataExpedient){

                # Id del expediente
                $idExpedient = $dataExpedient['id'];

                $expediente = self::findModel($idExpedient);

                # DATOS DE LA TRD CON LA DEPENDENCIA DEL EXPEDIENTE
                $dataTrd = GdTrd::find()->where(['idGdTrdDependencia' => $expediente->gdTrdDependencia->idGdTrdDependencia])->one();
                if($dataTrd){
                    $versionTrd = $dataTrd->versionGdTrd;
                }else{
                    $versionTrd = 'Sin versión';
                }

                if($expediente->estadoGdExpediente == Yii::$app->params['statusExpedienteText']['PendienteCerrar']){
                    $notificacion[] =  [
                        'message' => Yii::t('app', 'messagePendingClose',[
                        'numFile' => $expediente->numeroGdExpediente,
                        'numVersion' => $versionTrd,
                    ]),
                    'type' => 'danger'
                    ];
                }
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app','openExpedientPdf'), //Mensaje del pdf generado
                'status' => 200,
                'notificacion' => $notificacion ?? [],
            ];                        
                
            return HelperEncryptAes::encrypt($response, true);
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
    }

    public function actionPeaceAndSafe() {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            $jsonSend = Yii::$app->request->post('jsonSend');
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

                $modelGdExpedientesInclusion = GdExpedientesInclusion::find()
                    ->where(['idGdExpediente' => $request['data']['id']])
                    ->all();
                $idRadicadosExpedientesInclusion = [];
                foreach ($modelGdExpedientesInclusion as $key => $modelGdExpedienteInclusion) {
                    $idRadicadosExpedientesInclusion[] = $modelGdExpedienteInclusion->idRadiRadicado;
                }

                $modelCgGeneral = CgGeneral::find()
                    ->where(['estadoCgGeneral' => Yii::$app->params['statusTodoText']['Activo']])
                    ->one();
                $modelRadiRadicados = RadiRadicados::find()
                    ->where(['idCgTipoRadicado' => $modelCgGeneral->resolucionesIdCgGeneral])
                    ->andWhere(['IN', 'idRadiRadicado', $idRadicadosExpedientesInclusion])
                    ->all();
                $dataRadiRadicadosResoluciones = [];
                $idRadiRadicadoPrimero = 0;

                foreach ($modelRadiRadicados as $key => $modelRadiRadicado) {
                    if ($modelRadiRadicado->radiRadicadosResoluciones) {
                        $dataRadiRadicadosResoluciones[] = $modelRadiRadicado;
                        $idRadiRadicadoPrimero = $modelRadiRadicado->idRadiRadicado;
                    }
                }

                //Inicio de la transacción
                $transaction = Yii::$app->db->beginTransaction();

                if (count($dataRadiRadicadosResoluciones) > 0) {
                    $expendientesPazYalvo = Yii::getAlias('@webroot') ."/". "expendientes_paz_y_salvo" . "/". "generados";
                    if (!file_exists($expendientesPazYalvo)) {
                        if (!FileHelper::createDirectory($expendientesPazYalvo, $mode = 0775, $recursive = true)) {
                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app','errorValidacion'),
                                'data' => ['error' => [Yii::t('app', 'filecanNotBeDownloaded')]],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }

                    $modelExpedienteDocumento = new GdExpedienteDocumentos();
                    $modelExpedienteDocumento->idGdExpediente = $request['data']['id'];
                    $modelExpedienteDocumento->numeroGdExpedienteDocumento = $request['data']['numeroExpediente'];
                    $modelExpedienteDocumento->rutaGdExpedienteDocumento = $expendientesPazYalvo;
                    $modelExpedienteDocumento->extensionGdExpedienteDocumento = 'pdf';
                    $modelExpedienteDocumento->tamanoGdExpedienteDocumento = '0 KB';
                    $modelExpedienteDocumento->idGdTrdTipoDocumental = $modelCgGeneral->idPrestaPazYsalvoCgGeneral;
                    $modelExpedienteDocumento->isPublicoGdExpedienteDocumento = Yii::$app->params['statusTodoText']['Activo'];
                    $modelExpedienteDocumento->idUser = Yii::$app->user->identity->id;
                    $modelExpedienteDocumento->observacionGdExpedienteDocumento = 'Se generó paz y salvo de la prestación económica';
                    $modelExpedienteDocumento->estadoGdExpedienteDocumento = Yii::$app->params['statusTodoText']['Activo'];
                    $modelExpedienteDocumento->creacionGdExpedienteDocumento = date('Y-m-d H:i:s');
                    $modelExpedienteDocumento->fechaDocGdExpedienteDocumento = date('Y-m-d H:i:s');
                    if(!$modelExpedienteDocumento->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelExpedienteDocumento->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $modelExpedienteDocumento->nombreGdExpedienteDocumento = $request['data']['numeroExpediente'] ."-". $modelExpedienteDocumento->idGdExpedienteDocumento .".". $modelExpedienteDocumento->extensionGdExpedienteDocumento;
                    if(!$modelExpedienteDocumento->save()) {
                        $transaction->rollBack();
                        Yii::$app->response->statusCode = 200;
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $modelExpedienteDocumento->getErrors(),
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                    $remitentes = RadiRemitentes::findAll(['idRadiRadicado' => $idRadiRadicadoPrimero]);
                    $modelRemitente = User::findOne(['id' => $remitentes[0]->idRadiRemitente]);

                    if ($modelRemitente) {
                        $nombreRemitente = $modelRemitente->userDetalles->nombreUserDetalles.' '.$modelRemitente->userDetalles->apellidoUserDetalles;
                        $numeroDocumentoCliente = $modelRemitente->userDetalles->documento;
                    } else {
                        $nombreRemitente = '';
                        $numeroDocumentoCliente = '';
                    }

                    $pdf = new Pdf([
                        'mode'        => Pdf::MODE_CORE,
                        'format'      => Pdf::FORMAT_A3,
                        'orientation' => Pdf::ORIENT_LANDSCAPE,
                        'destination' => Pdf::DEST_FILE,
                        'filename'    => $expendientesPazYalvo ."/". $request['data']['numeroExpediente'] ."-". $modelExpedienteDocumento->idGdExpedienteDocumento .".". $modelExpedienteDocumento->extensionGdExpedienteDocumento,
                        'content'     => Yii::$app->controller->renderPartial("/pdf/peaceAndSafeView.php", ['data' => $dataRadiRadicadosResoluciones, 'nombreRemitente' => $nombreRemitente, 'numeroDocumentoCliente' => $numeroDocumentoCliente]),
                        'cssFile'     => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
                        'cssInline'   => '.kv-heading-1{font-size:18px}',
                        'options'     => ['title' => 'Krajee Report Title','setAutoBottomMargin' => 'stretch'],
                        'methods'     => [
                            'SetSubject'  =>  'GestionDocumental',
                            'SetKeywords' => 'Export, PDF',
                            'SetFooter'   => [],
                        ]
                    ]);
                    $pdf->Render();

                    $nombreTipoDocumental = $modelExpedienteDocumento->gdTrdTipoDocumental->nombreTipoDocumental;
                    $observacion = "Se cargó documento con el tipo documental: ". $nombreTipoDocumental ." de forma individual al expediente";
                    HelperLog::logAddExpedient(
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                        $request['data']['id'], //Id expediente
                        Yii::$app->params['operacionExpedienteText']['SubirDocumento'], //Operación
                        $observacion //observación
                    );

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app','successUpLoadFile'),
                        'data' => [],
                        'datafile' => base64_encode(file_get_contents($modelExpedienteDocumento->rutaGdExpedienteDocumento ."/". $modelExpedienteDocumento->nombreGdExpedienteDocumento)),
                        'fileName' => $modelExpedienteDocumento->nombreGdExpedienteDocumento,
                        'status' => 200
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => ['error' => [Yii::t('app', 'thereAreNoResolutionTypeFilings')]],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => 'Ok',
                    'data' => $modelRadiRadicados,
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
