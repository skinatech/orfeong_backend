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

namespace frontend\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for
 *
 * @property string $filterOperation
 */
class filterOperation extends Model
{ 

    public $idCgClasificacionPqrs, $idGdTrdTipoDocumental, $numeroRadiRadicado, $asuntoRadiRadicado;

    /**
     * {@inheritdoc}
     */
    public function rules() 
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'numeroRadiRadicado' => '',
            'asuntoRadiRadicado' => '',
            'idCgClasificacionPqrs' => '',
            'idGdTrdTipoDocumental' => '',
        ];
    }
}
