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
use api\models\RadiRadicados;

use Yii;

class HelperConsecutivo
{
    public function __construct() {}

    /**
     * Extrae el consecutivo del número de radicado
     */
    public static function extraerConsecutivo($numeroRadicado) {
        $numeroRadicadoExplode = explode('-', $numeroRadicado);
        return $numeroRadicadoExplode[2];
    }

    public static function numeroRadicadoXTipoRadicado($numeroRadicado, $tipoRadicado, $isRadicado) {
        $resNumeroRadicado = $numeroRadicado;

        if ((boolean) $isRadicado === true) {
            if (in_array($tipoRadicado, Yii::$app->params['extraerConsecutivoXtipoRadicado'])) {
                $resNumeroRadicado = self::extraerConsecutivo($numeroRadicado);
            }
        }

        return $resNumeroRadicado;
    }

    public static function numeroRadicadoXTipoRadicadoConsultando($numeroRadicado) {
        $modelRadiRadicados = RadiRadicados::find()->where(['numeroRadiRadicado' => $numeroRadicado])->one();
        return self::numeroRadicadoXTipoRadicado($modelRadiRadicados->numeroRadiRadicado, $modelRadiRadicados->idCgTipoRadicado, $modelRadiRadicados->isRadicado);
    }
}
