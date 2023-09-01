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
 * This is the model class for table "nivelGeografico3".
 *
 * @property int $nivelGeografico3 Número único de la tabla
 * @property int $idNivelGeografico2 Número del departmaneto al que pertenece
 * @property string $nomNivelGeografico3 Nombre del nivel geografico 3 ( Ciudad )
 * @property int $estadoNivelGeografico3 0 Inactivo 10 Activo
 *
 * @property NivelGeografico2 $nivelGeografico2
 * @property GaEdificio[] $gaEdificios
 */
class NivelGeografico3 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nivelGeografico3';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idNivelGeografico2', 'nomNivelGeografico3'], 'required'],
            [['idNivelGeografico2', 'estadoNivelGeografico3'], 'integer'],
            [['nomNivelGeografico3'], 'string', 'max' => 50],
            [['idNivelGeografico2'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico2::className(), 'targetAttribute' => ['idNivelGeografico2' => 'nivelGeografico2']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nivelGeografico3' => 'Nivel Geografico3',
            'idNivelGeografico2' => 'Id Nivel Geografico2',
            'nomNivelGeografico3' => 'Nom Nivel Geografico3',
            'estadoNivelGeografico3' => 'Estado Nivel Geografico3',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico2()
    {
        return $this->hasOne(NivelGeografico2::className(), ['nivelGeografico2' => 'idNivelGeografico2']);
    }

    /**
    * Gets query for [[GaEdificios]].
    *
    * @return \yii\db\ActiveQuery
    */
    public function getGaEdificios()
    {
        return $this->hasMany(GaEdificio::className(), ['idMunicipioGaEdificio' => 'nivelGeografico3']);
    }
}
