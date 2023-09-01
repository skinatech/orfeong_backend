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

namespace api\modules\version1\controllers;

use yii\web\Controller;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;
use api\components\HelperEncryptAes;

/**
 * Default controller for the `version1` module
 */
class DefaultController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    //'index'  => ['GET'],
                    'authorization-user' => ['GET'],
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
        ];
    }
    
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
    }

    /**
     * Consulta de operaciones a las cuales tiene permisos el usuario logueado
     * @return [array]
     */
    public function actionAuthorizationUser() {
        $operacionesUsers = [];

        foreach(Yii::$app->user->identity->rol->rolesTiposOperaciones as $key => $rolOperacionVal)
        {
            $operacionesUsers[] = $rolOperacionVal->rolOperacion->nombreRolOperacion;            
        }
        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $operacionesUsers,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionMenuUser() {

        $menuFilter = [];
        $menu = [];

        foreach(Yii::$app->user->identity->rol->rolesTiposOperaciones as $key => $rolTipoOperacion) {
            $menuFilter[$rolTipoOperacion->rolOperacion->rolModuloOperacion->ordenModuloOperacion] = array(
                'ruta' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->rutaRolModuloOperacion,
                'nombre' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->nombreRolModuloOperacion,
                'type' => 'link',
                'icontype' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->classRolModuloOperacion,
                'collapse' => 'collapse'.$rolTipoOperacion->rolOperacion->rolModuloOperacion->rutaRolModuloOperacion,
                'children' => [],
                'statusModulo' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->estadoRolModuloOperacion,
            );
        }

        ksort($menuFilter);

        foreach($menuFilter as $key => $menuFilterVal) {
            if ($menuFilterVal['statusModulo'] == Yii::$app->params['statusTodoText']['Activo']) {
                $menu[] = $menuFilterVal;
            }
        }

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => $menu,
            'status' => 200
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

    public function actionAbout($request) {
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

        Yii::$app->response->statusCode = 200;
        $response = [
            'message' => 'Ok',
            'data' => [],
            'status' => 200,
        ];
        return HelperEncryptAes::encrypt($response, true);
    }

}
