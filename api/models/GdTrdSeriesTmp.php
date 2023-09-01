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
 * This is the model class for table "gdTrdSeriesTmp".
 *
 * @property int $idGdTrdSerieTmp Número único para identificar la serie
 * @property string $nombreGdTrdSerieTmp
 * @property string $codigoGdTrdSerieTmp
 * @property int $estadoGdTrdSerieTmp 0 Inactivo - 10 Activo
 * @property string $creacionGdTrdSerieTmp Creación del registro
 *
 * @property GdTrdTmp[] $gdTrdTmps
 */
class GdTrdSeriesTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdSeriesTmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdSerieTmp', 'codigoGdTrdSerieTmp'], 'required'],
            [['estadoGdTrdSerieTmp'], 'integer'],
            [['creacionGdTrdSerieTmp'], 'safe'],
            [['nombreGdTrdSerieTmp'], 'string', 'max' => 255],
            [['codigoGdTrdSerieTmp'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdSerieTmp' => 'Id Trd serie temporal',
            'nombreGdTrdSerieTmp' => 'Nombre serie temporal',
            'codigoGdTrdSerieTmp' => 'Código serie temporal',
            'estadoGdTrdSerieTmp' => 'Estado serie temporal',
            'creacionGdTrdSerieTmp' => 'Creación serie temporal',
        ];
    }

    /**
     * Gets query for [[GdTrdTmps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTmps()
    {
        return $this->hasMany(GdTrdTmp::className(), ['idGdTrdSerieTmp' => 'idGdTrdSerieTmp']);
    }
}
