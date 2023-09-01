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

use api\models\CgEncuestas; 
use api\models\CgEncuestaPreguntas;
use api\models\Log;
use api\models\UserDetalles;

use api\modules\version1\controllers\correo\CorreoController;
use yii\data\ActiveDataProvider;

/**
 * ClientesController implements the CRUD actions for Clientes model.
 */
class EncuestasController extends Controller
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
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'change-status' => ['PUT']  
                ]                  
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

            $modelEncuestas = CgEncuestas::find();

            foreach($dataWhere as $field => $value){

                switch ($field) {
                    case 'nombreCgEncuesta':
                        $modelEncuestas->andWhere([Yii::$app->params['like'], 'cgEncuestas.'.$field , $value]);
                    break;
                    case 'status':
                        $modelEncuestas->andWhere(['IN', 'cgEncuestas.estadoCgEncuesta', intval($value)]);
                    break;
                    case 'fechaInicial':
                        $modelEncuestas->andWhere(['>=', 'creacionCgEncuesta', trim($value) . Yii::$app->params['timeStart']]);
                    break;
                    case 'fechaFinal':
                        $modelEncuestas->andWhere(['<=', 'creacionCgEncuesta', trim($value) . Yii::$app->params['timeEnd']]);
                    break;    
                    default:
                        $modelEncuestas->andWhere(['like', $field , $value]);
                    break;                   
                }
            }

            //Limite de la consulta
            //$modelEncuestas->orderBy(['idCgEncuesta' => SORT_DESC]); // Orden descendente para ver los últimos registros creados
            $modelEncuestas->limit($limitRecords);
            $modelEncuestas = $modelEncuestas->all();         

            $dataList = [];

            foreach($modelEncuestas as $encuesta){

                $numPregutas = CgEncuestaPreguntas::find()
                    ->where(['idCgEncuesta' => $encuesta->idCgEncuesta])
                    ->count()
                ;

                $dataBase64Params = "";
                $dataBase64Params = array(
                    str_replace(array('/', '+'), array('_', '-'), base64_encode($encuesta->idCgEncuesta))               
                );
                
                $dataList[] = [
                    'data' => $dataBase64Params,
                    'id' => $encuesta->idCgEncuesta,
                    'nombreCgEncuesta' => $encuesta->nombreCgEncuesta,                    
                    'creacionCgEncuesta' => $encuesta->creacionCgEncuesta,
                    'numeroPreguntas' => $numPregutas,
                    'statusText' => Yii::$app->params['statusTodoNumber'][$encuesta->estadoCgEncuesta],
                    'status' => $encuesta->estadoCgEncuesta,
                    'rowSelect' => false,
                    'idInitialList' => 0
                    
                ];               

            }

            /** Validar que el formulario exista */
            $formType = HelperDynamicForms::createDataForForm('indexCgEncuestas');

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $dataList,
                'filtersData' => HelperDynamicForms::setInputLimit($formType, $limitRecords),
                'infoLimitRecords' => (count($dataList) === $limitRecords) ? Yii::t('app', 'messageInputFilterLimit', ['limitRecords' => $limitRecords]) : false,
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

    public function actionIndexOne($request){

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

        $modelEncuestasPreguntas = CgEncuestaPreguntas::find()
        ->where(['idCgEncuesta' => $id])->all();

        $preguntas = [];
        foreach($modelEncuestasPreguntas as $preguntaItem){
            $preguntas[] = $preguntaItem->preguntaCgEncuestaPregunta;
        }

        $data = [
            'nombreCgEncuesta' => $model->nombreCgEncuesta,
            'creacionCgEncuesta' => $model->creacionCgEncuesta,
            'estadoCgEncuesta' => $model->estadoCgEncuesta,
            'preguntas' =>  $preguntas,
        ];

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
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
                $modelEncuesta = new CgEncuestas();
                $modelEncuesta->attributes = $request;
                $modelEncuesta->idUserCreador = Yii::$app->user->identity->id;
                
                self::inactiveEncuesta();

                //Validar si el nombre esta repetido
                $modelExistente = CgEncuestas::find()
                ->where(['nombreCgEncuesta' => $request['nombreCgEncuesta']])
                ->one();

                if(!empty($modelExistente)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'duplicatedPoll', [
                            'nombreEncuesta' => $modelExistente->nombreCgEncuesta,
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                if($modelEncuesta->save()){

                    $dataPreguntaArray = [];                   

                    $preguntas = [];
                    foreach($request['preguntas'] as $pregunta){

                        $modelPregunta = new CgEncuestaPreguntas();
                        $modelPregunta->idCgEncuesta = $modelEncuesta->idCgEncuesta;
                        $modelPregunta->preguntaCgEncuestaPregunta = $pregunta['preguntaCgEncuestaPregunta'];
                        $modelPregunta->creacionEncuestaPregunta = date('Y-m-d H:i:s');
                        $modelPregunta->estadoEncuestaPregunta = Yii::$app->params['statusTodoText']['Activo'];

                        if($modelPregunta->save()){

                            //$dataPreguntaArray[]['id']  = $modelPregunta->idCgEncuestaPregunta;
                            $dataPreguntaArray[] = $modelPregunta->preguntaCgEncuestaPregunta;
                        
                        }else{
                            
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelPregunta->getErrors());
                            break;

                        }

                        $preguntas[] = $modelPregunta->preguntaCgEncuestaPregunta; 
                    }
                    
                    if(count($preguntas) > count(array_unique($preguntas))){
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'duplicatedQuestion')],
                            'status' => Yii::$app->params['statusErrorValidacion']
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                }else{

                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelEncuesta->getErrors());                    
                  
                }

                if($saveDataValid){

                    #Consulta para obtener los datos para el log
                    $encuesta = CgEncuestas::findOne(['idCgEncuesta' => $modelEncuesta->idCgEncuesta]);

                    $dataEncuesta = self::dataLog($encuesta, $dataPreguntaArray);  

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['crear'] . ", de la encuesta de satisfacción", //texto para almacenar en el evento
                        '',
                        $dataEncuesta, //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/    

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelEncuesta,
                        'status' => 200,                        
                    ];
                    return HelperEncryptAes::encrypt($response, true);

                }else{
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
    
    public function actionUpdate(){      
        if (HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {
            
            $saveDataValid = true;
            $errors = [];
            
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

                $id = $request['id'];

                $transaction = Yii::$app->db->beginTransaction();

                unset($request['idCgEncuesta']);
                unset($request['creacionCgEncuesta']);

                $modelEncuesta = self::findModel($id);
                
                $preguntasArrayOld = [];
                foreach($modelEncuesta->cgEncuestaPreguntas as $pregunta){
                    $preguntasArrayOld[] = $pregunta->preguntaCgEncuestaPregunta;
                }

                #Consulta para obtener los datos para el log
                $dataEncuestaOld = self::dataLog($modelEncuesta, $preguntasArrayOld);  
                
                $modelEncuesta->attributes = $request;

                //Validar si el nombre esta repetido
                $modelExistente = CgEncuestas::find()
                ->where(['nombreCgEncuesta' => $request['nombreCgEncuesta']])                   
                ->one();

                if(!empty($modelExistente && $modelExistente->idCgEncuesta != $id)){
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [ 'error' => Yii::t('app', 'duplicatedPoll', [
                            'nombreEncuesta' => $modelExistente->nombreCgEncuesta,
                        ])],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

                if($modelEncuesta->save()){
                    cgEncuestaPreguntas::deleteAll(['idCgEncuesta' => $request['id']]);

                    $dataPreguntaArray = [];
                    $preguntas = [];
                    foreach($request['preguntas'] as $pregunta){
                        $modelPregunta = new cgEncuestaPreguntas();                        

                        $modelPregunta->idCgEncuesta = $modelEncuesta->idCgEncuesta;
                        $modelPregunta->preguntaCgEncuestaPregunta = $pregunta['preguntaCgEncuestaPregunta'];
                        $modelPregunta->creacionEncuestaPregunta = date('Y-m-d H:i:s');
                        $modelPregunta->estadoEncuestaPregunta = Yii::$app->params['statusTodoText']['Activo'];                        

                        if($modelPregunta->save()){

                            $dataPreguntaArray[] = $modelPregunta->preguntaCgEncuestaPregunta;

                        }else{
                            $saveDataValid = false;
                            $errors = array_merge($errors, $modelPregunta->getErrors());                           
                            break;
                        }

                        $preguntas[] = $modelPregunta->preguntaCgEncuestaPregunta; 
                    }
                    
                    if(count($preguntas) > count(array_unique($preguntas))){
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [ 'error' => Yii::t('app', 'duplicatedQuestion')],
                            'status' => Yii::$app->params['statusErrorValidacion']
                        ];
                        return HelperEncryptAes::encrypt($response, true);
                    }

                }else{
                    $saveDataValid = false;
                    $errors = array_merge($errors, $modelEncuesta->getErrors());                    
                }

                if($saveDataValid){

                    #Consulta para obtener los datos para el log
                    $encuesta = CgEncuestas::findOne(['idCgEncuesta' => $modelEncuesta->idCgEncuesta]);
                    $dataEncuesta = self::dataLog($encuesta, $dataPreguntaArray);  
                    
                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        true,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['Edit'] . ", de la encuesta de satisfacción", //texto para almacenar en el evento
                        $dataEncuestaOld,
                        $dataEncuesta, //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/    

                    $transaction->commit();

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successSave'),
                        'data' => $modelEncuesta,
                        'status' => 200,                        
                    ];

                    return HelperEncryptAes::encrypt($response, true);

                }else{
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

    public function actionView($request){
    
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

            $data = [];

            $preguntas = [];
            
            $modelEncuesta = $this->findModel($id);
            $modelPreguntasEncuesta = CgEncuestaPreguntas::find()
                ->select(['preguntaCgEncuestaPregunta'])
                ->where(['idCgEncuesta' => $id])
            ->all();           
            
            $modelUserDetalle = UserDetalles::find()
                ->where(['idUser' => $modelEncuesta->idUserCreador])
            ->one();           

            $data = [
                ['alias' => 'Nombre de la encuesta', 'value' => $modelEncuesta->nombreCgEncuesta],                
                ['alias' => 'Fecha de creación', 'value' => $modelEncuesta->creacionCgEncuesta],
                ['alias' => 'Usuario creador' , 'value' => "$modelUserDetalle->nombreUserDetalles $modelUserDetalle->apellidoUserDetalles"],
                ['alias' => 'Estado', 'value'=> Yii::$app->params['statusTodoNumber'][$modelEncuesta->estadoCgEncuesta]]
            ];

            foreach($modelPreguntasEncuesta as $pregunta){
                $data[] = ['alias' => 'Pregunta' , 'value' => $pregunta->preguntaCgEncuestaPregunta];
            }

             /***    Log de Auditoria  ***/
             HelperLog::logAdd(
                false,
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->username, //username
                Yii::$app->controller->route, //Modulo
                Yii::$app->params['eventosLogText']['View'] . "id: ".$id." de la encuesta: ".$modelEncuesta->nombreCgEncuesta, //texto para almacenar en el evento
                [],
                [], //Data
                array() //No validar estos campos
            );                  
            /***    Fin log Auditoria   ***/ 

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
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

                if ($model->estadoCgEncuesta == yii::$app->params['statusTodoText']['Activo']) {
                    if(self::isActiveEncuesta($dataExplode[0])){
                        $model->estadoCgEncuesta = yii::$app->params['statusTodoText']['Inactivo'];
                    }else{

                        $dataResponse[] = array(
                            'status' => $model->estadoCgEncuesta,
                            'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgEncuesta], 
                            'idInitialList' => $dataExplode[1] * 1);

                        Yii::$app->response->statusCode = 200;
                        $dataResponse[] = array('id' => $model->idCgEncuesta, 'status' => $model->estadoCgEncuesta, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgEncuesta], 'idInitialList' => $dataExplode[1] * 1);
                        
                        $response = [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => ['error' => [Yii::t('app', 'inactivedAllPoll')]],
                            'dataResponse' => $dataResponse,
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ];
                        return HelperEncryptAes::encrypt($response, true); 
                    }
                    
                } else {    
                    self::inactiveEncuesta();
                    $model->estadoCgEncuesta = yii::$app->params['statusTodoText']['Activo'];
                }

                if ($model->save()) {

                    $transaction->commit();

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$model->estadoCgEncuesta] . ', id: '. $model->idCgEncuesta.' de la encuesta: '.$model->nombreCgEncuesta,
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/

                    $dataResponse[] = array('id' => $model->idCgEncuesta, 'status' => $model->estadoCgEncuesta, 'statusText' => Yii::t('app', 'statusTodoNumber')[$model->estadoCgEncuesta], 'idInitialList' => $dataExplode[1] * 1);

                    //Respeusta success                    
                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => Yii::t('app', 'successChangeStatus'),
                        'data' => $dataResponse,
                        'status' => 200,
                    ];
                    return HelperEncryptAes::encrypt($response, true);
                }

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

    protected function inactiveEncuesta(){
        
        $modelEncuestas = CgEncuestas::find()
            ->all()
        ;

        foreach($modelEncuestas as $encuesta){
            if ($encuesta->estadoCgEncuesta == yii::$app->params['statusTodoText']['Activo']) {

                $encuesta->estadoCgEncuesta = yii::$app->params['statusTodoText']['Inactivo'];

                if($encuesta->save()){

                    /***    Log de Auditoria  ***/
                    HelperLog::logAdd(
                        false,
                        Yii::$app->user->identity->id, //Id user
                        Yii::$app->user->identity->username, //username
                        Yii::$app->controller->route, //Modulo
                        Yii::$app->params['eventosLogText']['ChangeStatus'] . Yii::t('app','statusTodoNumber')[$encuesta->estadoCgEncuesta] . ', id: '. $encuesta->idCgEncuesta.' de la encuesta: '.$encuesta->nombreCgEncuesta,
                        [],
                        [], //Data
                        array() //No validar estos campos
                    );                  
                    /***    Fin log Auditoria   ***/

                }
            }
        }
    
    }

    protected function isActiveEncuesta($id){
        $modelEncuestas = CgEncuestas::find()
            ->where(['<>', 'idCgEncuesta', $id])
            ->all()
        ;

        $activa = true;

        foreach($modelEncuestas as $encuesta){
            if($encuesta->estadoCgEncuesta == yii::$app->params['statusTodoText']['Inactivo']){
                $activa = false;
                break;
            }
        }

        return $activa;
    }

    protected function findModel($id)
    {
        if (($model = CgEncuestas::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

     /**
     * Funcion que obtiene el modelo del log para la data actual y anterior.
     * @param $model [Modelo de la tabla]
     * @param $preguntas [Array con las preguntas]
     * @return $dataLog [String] [Información del modelo]
     **/
    protected function dataLog($model, $preguntas){

        if(!is_null($model)){
            
            # Valores del modelo se utiliza para el log
            $labelModel = $model->attributeLabels();
            $dataLog = '';

            foreach ($model as $key => $value) {           
            
                switch ($key) {

                    case 'idCgEncuesta':
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                        $dataLog .= 'Preguntas: '.implode(", ", $preguntas).', ';
                    break; 
                    case 'idUserCreador':
                        $dataLog .= 'Usuario creador: '.$model->userCreador->userDetalles->nombreUserDetalles." ".$model->userCreador->userDetalles->apellidoUserDetalles.', ';
                    break; 
                    case 'estadoCgEncuesta':
                        $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$value].', ';
                    break; 
                    default:
                        $dataLog .= $labelModel[$key].': '.$value.', ';
                    break;
                }
            }
            return $dataLog;
        }
    } 
}
