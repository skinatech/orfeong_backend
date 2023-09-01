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

/**
 * Clase para configurar el Lenguaje la aplicación
 */
class HelperLanguageSelector {

    /**
     * Función que establece el lenguaje de respuesta de la aplicación según el parametro "language" recibido por el header
     */
    public static function getPreferredLanguage()
    {
        $lang = Yii::$app->request->headers['language'];
        if ( in_array($lang, Yii::$app->params['supportedLanguages']) ) {
            Yii::$app->language = $lang;
        } else {
            Yii::$app->language = 'es';
        }
    }

    /**
     * Función que retorna la traducción de una variable
     * Es utilizada desde los modelos ubicados en common ya que no tienen alcance a la instancia de internacionalización instalada en api
     */
    public static function getTranslation($variableTraduccion)
    {
        return Yii::t('app',$variableTraduccion);
    }
}
