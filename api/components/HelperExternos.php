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
namespace api\components;

use Yii;
use api\models\CgProveedoresExternos;


/**
 * Clase para encriptar validaciones de los proveedores externos
 */
class HelperExternos {

    public static function validateToken($headers) {

        $responseHelper['Authorization'] = true;
        $responseHelper['token'] = true;

        if(isset($headers['Authorization'])) {

            $tokenBearer = explode('Bearer', $headers['Authorization']);

            $model = CgProveedoresExternos::find()->where(['tokenCgProveedorExterno' => trim($tokenBearer[1])])->one();
            if($model) {
                $responseHelper['model'] = $model;
            } else {
                $responseHelper['token'] = false;
            }

        } else {

            $responseHelper['Authorization'] = false;

        }

        return $responseHelper;

    }

    public static function validarJson($request, $params) {

        $validateParams = Yii::$app->params[$params];
        $dataNull = [];

        foreach (Yii::$app->params[$params] as $key => $val) {

            if(isset($request[$key])) {
                unset($validateParams[$key]);
            } else {
                $dataNull[] = 'El campo '. $key .' es obligatorio';
            }
        }

        return $dataNull;

    }

    public static function validarCampoVsbd($request, $params) {

        $validateParams = Yii::$app->params[$params];
        $dataNull = [];
        $models = [];

        foreach (Yii::$app->params[$params] as $key => $val) {

            if(isset($request[$key])) {

                $valExplode = explode('|', $val);
                $model = $valExplode[0]::find()->where([$valExplode[1] => $request[$key]])->one();
                if($model) {
                    $models[$key] = $model;
                } else {
                    $dataNull[] = 'El valor '. $request[$key] .' para el campo '. $key .' no es correcto';
                }

            } else {
                $dataNull[] = 'El campo '. $key .' es obligatorio';
            }
        }

        return array(
            'dataNull' => $dataNull,
            'models' => $models
        );

    }

    public static function tokenSkinaScan() {

            $model = CgProveedoresExternos::find()->where(['nombreCgProveedorExterno' => Yii::$app->params['usuarioExterno']['SkinaScan']])->one();
            if($model) {
                $responseHelper['model'] = $model;
            } else {
                $responseHelper['token'] = false;
            }

        return $responseHelper;

    }

}
