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
 * This is the model class for table "nivelGeografico1".
 *
 * @property int $nivelGeografico1 Identificador único del país
 * @property string $nomNivelGeografico1 Nombre del país
 * @property int $estadoNivelGeografico1 0 Inactivo 10 Activo
 *
 * @property NivelGeografico2[] $nivelGeografico2s
 */
class NivelGeografico1 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nivelGeografico1';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nomNivelGeografico1'], 'required'],
            [['estadoNivelGeografico1'], 'integer'],
            [['nomNivelGeografico1'], 'string', 'max' => 50],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nivelGeografico1' => 'Nivel Geografico1',
            'nomNivelGeografico1' => 'Nom Nivel Geografico1',
            'estadoNivelGeografico1' => 'Estado Nivel Geografico1',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico2s()
    {
        return $this->hasMany(NivelGeografico2::className(), ['idNivelGeografico1' => 'nivelGeografico1']);
    }
}
