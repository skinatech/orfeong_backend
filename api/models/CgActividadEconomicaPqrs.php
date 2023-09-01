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

namespace api\models;

use Yii;

/**
 * This is the model class for table "cgActividadEconomicaPqrs".
 *
 * @property int $idCgActividadEconomicaPqrs
 * @property string $nombreCgActividadEconomicaPqrs
 * @property string $creacionCgActividadEconomicaPqrs
 * @property int $estadoCgActividadEconomicaPqrs
 */
class CgActividadEconomicaPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgActividadEconomicaPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgActividadEconomicaPqrs'], 'required'],
            [['creacionCgActividadEconomicaPqrs'], 'safe'],
            [['estadoCgActividadEconomicaPqrs'], 'integer'],
            [['nombreCgActividadEconomicaPqrs'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgActividadEconomicaPqrs' => '',
            'nombreCgActividadEconomicaPqrs' => '',
            'creacionCgActividadEconomicaPqrs' => '',
            'estadoCgActividadEconomicaPqrs' => '',
        ];
    }
}
