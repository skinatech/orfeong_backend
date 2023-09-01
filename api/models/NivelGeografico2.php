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
 * This is the model class for table "nivelGeografico2".
 *
 * @property int $nivelGeografico2 Identificador único del nivel 2
 * @property int $idNivelGeografico1 Número que indica el país al que pertenece
 * @property string $nomNivelGeografico2 Nombre del nivel geografico 2 ( Departamento )
 * @property int $estadoNivelGeografico2 0 Inactivo 10 Activo
 *
 * @property NivelGeografico1 $nivelGeografico1
 * @property NivelGeografico3[] $nivelGeografico3s
 * @property GaEdificio[] $gaEdificios
 */
class NivelGeografico2 extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'nivelGeografico2';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idNivelGeografico1', 'nomNivelGeografico2'], 'required'],
            [['idNivelGeografico1', 'estadoNivelGeografico2'], 'integer'],
            [['nomNivelGeografico2'], 'string', 'max' => 50],
            [['idNivelGeografico1'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico1::className(), 'targetAttribute' => ['idNivelGeografico1' => 'nivelGeografico1']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nivelGeografico2' => 'Nivel Geografico2',
            'idNivelGeografico1' => 'Id Nivel Geografico1',
            'nomNivelGeografico2' => 'Nom Nivel Geografico2',
            'estadoNivelGeografico2' => 'Estado Nivel Geografico2',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico1()
    {
        return $this->hasOne(NivelGeografico1::className(), ['nivelGeografico1' => 'idNivelGeografico1']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico3s()
    {
        return $this->hasMany(NivelGeografico3::className(), ['idNivelGeografico2' => 'nivelGeografico2']);
    }

    /**
     * Gets query for [[GaEdificios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaEdificios()
    {
        return $this->hasMany(GaEdificio::className(), ['idDepartamentoGaEdificio' => 'nivelGeografico2']);
    }
}
