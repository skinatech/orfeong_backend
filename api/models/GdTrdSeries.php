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
 * This is the model class for table "gdTrdSeries".
 *
 * @property int $idGdTrdSerie Número único para identificar la serie
 * @property string $nombreGdTrdSerie
 * @property string $codigoGdTrdSerie
 * @property int $estadoGdTrdSerie 0 Inactivo - 10 Activo
 * @property string $creacionGdTrdSerie Creación del registro
 *
 * @property GdTrd[] $gdTrds
 */
class GdTrdSeries extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdSeries';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdSerie', 'codigoGdTrdSerie'], 'required'],
            [['estadoGdTrdSerie'], 'integer'],
            [['creacionGdTrdSerie'], 'safe'],
            [['nombreGdTrdSerie'], 'string', 'max' => 255],
            [['codigoGdTrdSerie'], 'string', 'max' => 20]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdSerie' => 'Id Trd serie',
            'nombreGdTrdSerie' => 'Nombre serie',
            'codigoGdTrdSerie' => 'Código serie',
            'estadoGdTrdSerie' => 'Estado serie',
            'creacionGdTrdSerie' => 'Creación serie',
        ];
    }

    /**
     * Gets query for [[GdTrds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrds()
    {
        return $this->hasMany(GdTrd::className(), ['idGdTrdSerie' => 'idGdTrdSerie']);
    }
}
