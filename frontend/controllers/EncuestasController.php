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

namespace frontend\controllers;

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

    public function actionIndex(){   

        $data = [];
            
        $modelEncuesta = CgEncuestas::find()
            ->where(['estadoCgEncuesta' => Yii::$app->params['statusTodoText']['Activo']])
            ->one()
        ;
            
       $modelPreguntas = CgEncuestaPreguntas::find()
            ->where(['idCgEncuesta' => $modelEncuesta->idCgEncuesta])
            ->all()
        ;

        $preguntasArray = [];
        foreach($modelPreguntas as $pregunta){
            $preguntas[] = $pregunta->preguntaCgEncuestaPregunta;
        }

        $data = [
            ['alias' => 'Nombre encuesta', 'value' => $modelEncuesta->nombreCgEncuesta],
            ['alias' => 'Fecha de creación', 'value' => $modelEncuesta->creacionCgEncuesta],            
            ['alias' => 'Preguntas', 'value' => $preguntasArray]
        ];          

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $data,
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
             
        
    }
}
