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
 * This is the model class for table "radiPrioridades".
 *
 * @property int $idRadiPrioridad
 * @property string $nombreRadiPrioridad
 * @property int $estadoRadiPrioridad
 * @property string $creacionRadiPrioridad
 *
 * @property RadiRadicados[] $radiRadicados
 */
class RadiPrioridades extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiPrioridades';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreRadiPrioridad'], 'required'],
            [['estadoRadiPrioridad'], 'integer'],
            [['creacionRadiPrioridad'], 'safe'],
            [['nombreRadiPrioridad'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiPrioridad' => 'Id Radi Prioridad',
            'nombreRadiPrioridad' => 'Nombre Radi Prioridad',
            'estadoRadiPrioridad' => 'Estado Radi Prioridad',
            'creacionRadiPrioridad' => 'Creacion Radi Prioridad',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicados()
    {
        return $this->hasMany(RadiRadicados::className(), ['idPrioridadRadiRadicados' => 'idRadiPrioridad']);
    }
}
