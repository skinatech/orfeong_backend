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

namespace api\modules\version1\controllers\roles;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;

use Yii;
use api\models\RolesModulosOperaciones;
use yii\data\ActiveDataProvider;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\QueryParamAuth;

// use api\components\HelperValidatePermits;

class RolesModulosOperacionesController extends Controller
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
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    public function actionIndex() {

        //if(HelperValidatePermits::validateUserPermits(Yii::$app->controller->route, Yii::$app->user->identity->rol->rolesTiposOperaciones)) {

            $data = [];

            $findRolesModulosOperaciones = RolesModulosOperaciones::find()
            ->where(['estadoRolModuloOperacion' => Yii::$app->params['statusTodoText']['Activo']])
            ->orderBy(['nombreRolModuloOperacion'=>SORT_ASC])
            ->limit(Yii::$app->params['limitRecords'])
            ->all();

            foreach($findRolesModulosOperaciones as $key => $rolModuloOperacion)
            {
                $data[] = array(
                    "id" => $rolModuloOperacion->idRolModuloOperacion,
                    "val" => $rolModuloOperacion->nombreRolModuloOperacion,
                );
            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'Ok',
                'data' => $data,
                'status' => 200
            ];
            return HelperEncryptAes::encrypt($response, true);
        
        // } else {
        //     return array('status' => false, 'data' => array(Yii::$app->params['accessDenied'][0]));
        // }
    }


    /**
     * Finds the Roles model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Roles the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = RolesModulosOperaciones::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
