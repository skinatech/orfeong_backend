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

namespace api\modules\version1\controllers\configuracionApp;

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\db\ExpressionInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\db\ActiveQuery;

use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\components\HelperLog;
use api\components\HelperDynamicForms;
use api\components\HelperQueryDb;

use api\models\CgGruposUsuarios;
use api\models\CgTiposGruposUsuarios;
use api\models\User;
use api\models\UserDetalles;
use api\models\GdTrdDependencias;

use api\modules\version1\controllers\correo\CorreoController;
use yii\data\ActiveDataProvider;

/**
 * ClientesController implements the CRUD actions for Clientes model.
 */
class GruposUsuariosController extends Controller
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
                    'index-one' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'change-status' => ['PUT'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionIndex($request){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

            //Lista de grupos de usuarios
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
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
                        } else {
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            $modelGruposUsuarios = CgGruposUsuarios::find();
                //->where(['estadoCgGrupoUsuarios' => Yii::$app->params['statusTodoText']['Activo']]);
            

            //Se reitera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){

                switch ($field) {
                    
                    case 'fechaInicial':
                        $modelGruposUsuarios->andWhere(['>=', 'creacionCgGrupoUsuarios', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $modelGruposUsuarios->andWhere(['<=', 'creacionCgGrupoUsuarios', trim($value) . Yii::$app->params['timeEnd']]);
                    break;    
                    case 'estadoCgGrupoUsuarios':
                        $modelGruposUsuarios->andWhere(['IN', 'cgGruposUsuarios.' . $field, intval($value)]);
                    break; 
                    default:
                        $modelGruposUsuarios->andWhere([Yii::$app->params['like'], 'cgGruposUsuarios.'.$field , $value]);
                    break;
                }                
            }        
            
            //Limite de la consulta
            $modelGruposUsuarios->orderBy(['idCgGrupoUsuarios' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $modelGruposUsuarios->limit($limitRecords);
            $modelGruposUsuarios = $modelGruposUsuarios->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelGruposUsuarios = array_reverse($modelGruposUsuarios);

            foreach($modelGruposUsuarios as $grupoUsuarios){

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($grupoUsuarios->idCgGrupoUsuarios))                
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $grupoUsuarios->idCgGrupoUsuarios,                
                    'nombreCgGrupoUsuarios' => $grupoUsuarios->nombreCgGrupoUsuarios,
                    'creacion' => $grupoUsuarios->creacionCgGrupoUsuarios,               
                    'statusText' => Yii::$app->params['statusTodoNumber'][$grupoUsuarios->estadoCgGrupoUsuarios],
                    'status' => $grupoUsuarios->estadoCgGrupoUsuarios,
                    'rowSelect' => false,
                    'idInitialList' => 0
                );

            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgGuposUsuarios');

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

        $modelTiposGrupoUsuarios = CgTiposGruposUsuarios::find()
            ->where(['idCgGrupoUsuarios' =>  $model->idCgGrupoUsuarios])
            ->all();

        $dependenciasArray = [];
        $usuariosArray = [];
        
        foreach($modelTiposGrupoUsuarios as $tipoGrupoUsuarios){

            $idDependencia = $tipoGrupoUsuarios->user->gdTrdDependencia->idGdTrdDependencia;
            if(!in_array($idDependencia, $dependenciasArray)){
                $dependenciasArray[] =  $idDependencia;
            }            
            $usuariosArray[] = $tipoGrupoUsuarios->user->id;        
        }

        $data = [
            'nombreCgGrupoUsuarios' => $model->nombreCgGrupoUsuarios,            
            'estadoCgGrupoUsuarios' => $model->estadoCgGrupoUsuarios,
            'creacionCgGrupoUsuarios' => $model->creacionCgGrupoUsuarios,
            'idDependencia' =>  $dependenciasArray,
            'idUsers' =>  $usuariosArray
        ];                

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);

    }

    /** Este view es diferente al resto porque se solicitó utilizar la misma metodología del initial list */
    public function actionView($request)
    {
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
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

            $id = $request['id']; // ID del grupo de usuarios

            //Lista de grupos de usuarios
            $dataList = [];
            $dataBase64Params = "";
            $limitRecords = Yii::$app->params['limitRecords'];

            //Se reitera el $request y obtener la informacion retornada
            $dataWhere = [];
            if (is_array($request) && isset($request['filterOperation'])) {
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
                        } else {
                            if( isset($info) && $info !== null && trim($info) != '' ){
                                $dataWhere[$key] = $info;
                            }
                        }
                    }
                }
            }

            $tablaCgTiposGruposUsuarios = CgTiposGruposUsuarios::tableName() . ' AS GTU';
            $tablaCgGruposUsuarios = CgGruposUsuarios::tableName() . ' AS GU';;
            $tablaUser = User::tableName() . ' AS U';
            $tablaGdTrdDependencias = GdTrdDependencias::tableName() . ' AS D';
            $tablaUserDetalles = UserDetalles::tableName() . ' AS UD';

            $modelRelation = (new \yii\db\Query())
                 ->select(['U.id', 'UD.nombreUserDetalles', 'UD.apellidoUserDetalles', 'D.codigoGdTrdDependencia', 'D.nombreGdTrdDependencia', 'GU.nombreCgGrupoUsuarios'])
                ->from($tablaCgTiposGruposUsuarios);
                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaCgGruposUsuarios, ['GU' => 'idCgGrupoUsuarios', 'GTU' => 'idCgGrupoUsuarios']);
                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaUser, ['U' => 'id', 'GTU' => 'idUser']);
                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaGdTrdDependencias, ['D' => 'idGdTrdDependencia', 'U' => 'idGdTrdDependencia']);
                $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaUserDetalles, ['UD' => 'idUser', 'U' => 'id']);

                $modelRelation = $modelRelation->where(["GTU.idCgGrupoUsuarios" => $id]);

            //Se itera $dataWhere para solo obtener los campos con datos
            foreach($dataWhere as $field => $value){

                switch ($field) {
                    case 'idGdTrdDependencia':
                        $modelRelation->andWhere(['IN', 'D.idGdTrdDependencia', $value]);
                    break;
                }
            }

            //Limite de la consulta
            $modelRelation->orderBy(['D.idGdTrdDependencia' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $modelRelation->limit($limitRecords);
            $modelRelation = $modelRelation->all();

            /** Ordenando array de forma reversa para que el initial-list lo maneje con el mismo estandar actual */
            $modelRelation = array_reverse($modelRelation);

            foreach($modelRelation as $usuario){

                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($usuario['id']))
                );

                $dataList[] = array(
                    'data' => $dataBase64Params,
                    'id' => $usuario['id'],
                    'nombreGrupo' => $usuario['nombreCgGrupoUsuarios'],
                    'dependencia' => $usuario['codigoGdTrdDependencia'] . ' - ' . $usuario['nombreGdTrdDependencia'],
                    'nombreUsuario' => $usuario['nombreUserDetalles'] . ' ' . $usuario['apellidoUserDetalles'],
                    'rowSelect' => false,
                    'idInitialList' => 0
                );

            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::setListadoBD('indexCgGuposUsuariosView');

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

    public function actionCreate(){

        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $saveDataValid = true;
            $errors = [];
            
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

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['id']);
                $request['nombreCgGrupoUsuarios'] = trim($request['nombreCgGrupoUsuarios']);

                $modelGrupoUsuarios = new CgGruposUsuarios();
                $modelGrupoUsuarios->attributes = $request;
                $modelGrupoUsuarios->estadoCgGrupoUsuarios = Yii::$app->params['statusTodoText']['Activo'];
                $modelGrupoUsuarios->creacionCgGrupoUsuarios = date('Y-m-d H:i:s');

                //Validar si el nombre esta repetido
                /*$modelExistente = CgGruposUsuarios::find()
                    ->where(['nombreCgGrupoUsuarios' => $request['nombreCgGrupoUsuarios']])
                    ->one();*/

                # Valida que el grupo de usuarios no exista busca en todos los registros activos e inactivos
                $modGrUser = CgGruposUsuarios::find()->all();

                if(count($modGrUser)>0){
                    foreach ($modGrUser as $key => $reg) {
                        $nameGrUser = strtoupper($reg->nombreCgGrupoUsuarios);
                        $nameGrUser = trim($nameGrUser);
                        if($nameGrUser == $request['nombreCgGrupoUsuarios'] ){

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => [ 'error' => Yii::t('app', 'nombreGrupoUsuariosDuplicated', [
                                    'nombreGrupoUsuarios' => $reg->nombreCgGrupoUsuarios,
                                ])],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }
                

                /*if(!empty($modelExistente)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nombreGrupoUsuariosDuplicated', [
                            'nombreGrupoUsuarios' => $modelExistente->nombreCgGrupoUsuarios,
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }*/
                
                if ($modelGrupoUsuarios->save()) {

                    $datoIdGrupoArray = []; // Guarda los ids de los usuarios que se asocian al grupo
                    
                    foreach ($request['idUsers'] as $key => $idUser) {
                        $modelTipoGrupoUsuarios = new CgTiposGruposUsuarios();

                        $modelTipoGrupoUsuarios->idCgGrupoUsuarios = $modelGrupoUsuarios->idCgGrupoUsuarios;
                        $modelTipoGrupoUsuarios->idUser = $idUser;

                        if($modelTipoGrupoUsuarios->save()){

                            $datoIdGrupoArray[] = $modelTipoGrupoUsuarios->idUser;

                        }else{
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelTipoGrupoUsuarios->getErrors());
                            break;
                        }
                    }
                }else{
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelGrupoUsuarios->getErrors());
                }

                if ($saveDataValid) {

                    $idUsuarios = implode(', ', $datoIdGrupoArray); $nombresUsuariosArray = [];

                    $dataUsuarios = UserDetalles::find()->where(['IN', 'idUser', $datoIdGrupoArray])->all(); // where in de Yii necesita un Array
                    foreach ($dataUsuarios as $value) {
                        $nombresUsuariosArray[] = $value->nombreUserDetalles.' '.$value->apellidoUserDetalles ?? [];
                    }

                    $dataGrupoUsuarios = 'Id Grupo: '.$modelGrupoUsuarios->idCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Nombre Grupo: '.$modelGrupoUsuarios->nombreCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Ids Usuarios: '.implode(', ', $datoIdGrupoArray);
                    $dataGrupoUsuarios .= ', Nombres Usuarios: '.implode(', ', $nombresUsuariosArray);
                    $dataGrupoUsuarios .= ', Fecha creación: '.$modelGrupoUsuarios->creacionCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelGrupoUsuarios->estadoCgGrupoUsuarios];

                    $transaction->commit();  

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", en la tabla CgGruposUsuarios", //texto para almacenar en el evento
                        '',
                        $dataGrupoUsuarios, //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/                  

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelGrupoUsuarios,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                } else {
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $errors,
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else{
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

    public function actionUpdate(){

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
                $datoIdGrupoArrayOld = [];

                $id = $request['id'];
                $request['nombreCgGrupoUsuarios'] = trim($request['nombreCgGrupoUsuarios']);

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idCgGrupoUsuarios']);
                unset($request['creacionCgGrupoUsuarios']);

                $modelGrupoUsuarios = $this->findModel($id);
               
                $IdUsuariosGrupos = CgTiposGruposUsuarios::findAll(['idCgGrupoUsuarios' => $id]);

                $datoIdGrupoArrayOld = [];
                foreach ($IdUsuariosGrupos as $value) {
                    $datoIdGrupoArrayOld[] = $value->idUser;
                }

                $idUsuarios = implode(', ', $datoIdGrupoArrayOld);

                $dataUsuarios = UserDetalles::find()->where(['IN', 'idUser', $datoIdGrupoArrayOld])->all(); // where in de Yii necesita un Array

                $nombresUsuariosArray = [];
                foreach ($dataUsuarios as $value) {
                    $nombresUsuariosArray[] = $value->nombreUserDetalles.' '.$value->apellidoUserDetalles;
                }

                $dataGrupoUsuariosOld = 'Id Grupo: '.$modelGrupoUsuarios->idCgGrupoUsuarios;
                $dataGrupoUsuariosOld .= ', Nombre Grupo: '.$modelGrupoUsuarios->nombreCgGrupoUsuarios;
                $dataGrupoUsuariosOld .= ', Ids Usuarios: '.$idUsuarios;
                $dataGrupoUsuariosOld .= ', Nombres Usuarios: '.implode(', ', $nombresUsuariosArray);
                $dataGrupoUsuariosOld .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelGrupoUsuarios->estadoCgGrupoUsuarios];

                $modelGrupoUsuariosOld = clone $modelGrupoUsuarios;
                $modelGrupoUsuarios->attributes = $request;

                //Verificar si el codigo ya existe
                //Validar si el nombre esta repetido
                /*$modelExistente = CgGruposUsuarios::find()
                    ->where(['nombreCgGrupoUsuarios' => $request['nombreCgGrupoUsuarios']])                    
                    ->one();*/

                # Valida que el grupo de usuarios no exista busca en todos los registros activos e inactivos
                $modGrUser = CgGruposUsuarios::find()->all();

                if(count($modGrUser)>0){
                    foreach ($modGrUser as $key => $reg) {
                        $nameGrUser = strtoupper($reg->nombreCgGrupoUsuarios);
                        $nameGrUser = trim($nameGrUser);
                        if($nameGrUser == $request['nombreCgGrupoUsuarios'] ){

                            Yii::$app->response->statusCode = 200;
                            $response = [
                                'message' => Yii::t('app', 'errorValidacion'),
                                'data' => [ 'error' => Yii::t('app', 'nombreGrupoUsuariosDuplicated', [
                                    'nombreGrupoUsuarios' => $reg->nombreCgGrupoUsuarios,
                                ])],
                                'status' => Yii::$app->params['statusErrorValidacion'],
                            ];
                            return HelperEncryptAes::encrypt($response, true);
                        }
                    }
                }
                

                /*if(!empty($modelExistente) && $modelExistente->idCgGrupoUsuarios != $modelGrupoUsuarios->idCgGrupoUsuarios){
                   
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'nombreGrupoUsuariosDuplicated', [
                            'nombreGrupoUsuarios' => $modelExistente->nombreCgGrupoUsuarios,
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);                   
                }*/
                // Fin de verificación de codigo existente

                if($modelGrupoUsuarios->save()){
                    
                    $datoIdGrupoArray = [];

                    $deleteTiposGrupoUsuarios = CgTiposGruposUsuarios::deleteAll(['idCgGrupoUsuarios' => $modelGrupoUsuarios->idCgGrupoUsuarios]);

                    foreach ($request['idUsers'] as $key => $idUser) {
                        $modelTipoGrupoUsuarios = new CgTiposGruposUsuarios();

                        $modelTipoGrupoUsuarios->idCgGrupoUsuarios = $modelGrupoUsuarios->idCgGrupoUsuarios;
                        $modelTipoGrupoUsuarios->idUser = $idUser;

                        if($modelTipoGrupoUsuarios->save()){

                            $datoIdGrupoArray[] = $modelTipoGrupoUsuarios->idUser;
                            
                        }else{
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelTipoGrupoUsuarios->getErrors());
                            break;
                        }
                    }
                }else{
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelGrupoUsuarios->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }

                if ($saveDataValid) {

                    $idUsuarios = implode(', ', $datoIdGrupoArray);

                    $dataUsuarios = UserDetalles::find()->where(['IN', 'idUser', $datoIdGrupoArray])->all(); // where in de Yii necesita un Array
                    foreach ($dataUsuarios as $value) {
                        $nombresUsuariosArray[] = $value->nombreUserDetalles.' '.$value->apellidoUserDetalles;
                    }

                    $dataGrupoUsuarios = 'Id Grupo: '.$modelGrupoUsuarios->idCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Nombre Grupo: '.$modelGrupoUsuarios->nombreCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Ids usuarios: '.implode(', ', $datoIdGrupoArray);
                    $dataGrupoUsuarios .= ', Nombres de usuarios: '.implode(', ', $nombresUsuariosArray);
                    $dataGrupoUsuarios .= ', Fecha creación: '.$modelGrupoUsuarios->creacionCgGrupoUsuarios;
                    $dataGrupoUsuarios .= ', Estado: '.Yii::$app->params['statusTodoNumber'][$modelGrupoUsuarios->estadoCgGrupoUsuarios];

                    $transaction->commit(); 

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", en la tabla CgGruposUsuarios", //texto para almacenar en el evento
                        $dataGrupoUsuariosOld,
                        $dataGrupoUsuarios, //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/  
                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successUpdate'),
                        'data' => $modelGrupoUsuarios,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }else{
                    $transaction->rollBack();
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelGrupoUsuarios->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

            }else{
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

    public function actionChangeStatus(){

        $errors = [];
        $dataResponse = [];
        $dataExplode = "";
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
            $transaction = Yii::$app->db->beginTransaction();

            foreach ($request as $value) {
                $dataExplode = explode('|', $value);

                $model = $this->findModel($dataExplode[0]);
                $modelOld = clone $model;
                if ($model->estadoCgGrupoUsuarios == yii::$app->params['statusTodoText']['Activo']) {
                    $model->estadoCgGrupoUsuarios = yii::$app->params['statusTodoText']['Inactivo'];
                } else {
                    $model->estadoCgGrupoUsuarios = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgGrupoUsuarios] . ', id: '. $model->idCgGrupoUsuarios.' de Grupo usuarios: '.$model->nombreCgGrupoUsuarios,
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/   

                    $dataResponse[] = array('id' => $model->idCgGrupoUsuarios, 'status' => $model->estadoCgGrupoUsuarios, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgGrupoUsuarios], 'idInitialList' => $dataExplode[1] * 1);
                } else {
                    $errors[] = $model->getErrors();
                    $saveDataValid = false;
                }
            }

            if ($saveDataValid) {
                $transaction->commit();
    
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'successChangeStatus'),
                    'data' => $dataResponse,
                    'status' => 200,
                ];
                return HelperEncryptAes::encrypt($response, true);
    
            } else {
                $transaction->rollBack();
                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacion'),
                    'data' => $errors,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];
                return HelperEncryptAes::encrypt($response, true);  
            }   

        }else{
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => Yii::t('app', 'errorValidacion'),
                'data' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
                'status' => Yii::$app->params['statusErrorValidacion'],
            ];
            return HelperEncryptAes::encrypt($response, true); 
        }
    }

    protected function findModel($id)
    {
        if (($model = CgGruposUsuarios::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
