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
 * This is the model class for table "gaEdificio".
 *
 * @property int $idGaEdificio Id edificio
 * @property string $nombreGaEdificio Nombre del edificio
 * @property int $idDepartamentoGaEdificio Id departamento
 * @property int $idMunicipioGaEdificio Id municipio
 * @property int $estadoGaEdificio Estado del edificio
 * @property string $creacionGaEdificio Fecha creación
 *
 * @property NivelGeografico2 $idDepartamentoGaEdificio0
 * @property NivelGeografico3 $idMunicipioGaEdificio0
 * @property GaPiso[] $gaPisos
 */
class GaEdificio extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaEdificio';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGaEdificio', 'idDepartamentoGaEdificio', 'idMunicipioGaEdificio'], 'required'],
            [['idDepartamentoGaEdificio', 'idMunicipioGaEdificio', 'estadoGaEdificio'], 'integer'],
            [['creacionGaEdificio'], 'safe'],
            [['nombreGaEdificio'], 'string', 'max' => 80],
            [['idDepartamentoGaEdificio'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico2::className(), 'targetAttribute' => ['idDepartamentoGaEdificio' => 'nivelGeografico2']],
            [['idMunicipioGaEdificio'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico3::className(), 'targetAttribute' => ['idMunicipioGaEdificio' => 'nivelGeografico3']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaEdificio' => 'Id del edificio',
            'nombreGaEdificio' => 'Nombre del edificio',
            'idDepartamentoGaEdificio' => 'Id departamento',
            'idMunicipioGaEdificio' => 'Id municipio',
            'estadoGaEdificio' => 'Estado del edificio',
            'creacionGaEdificio' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[IdDepartamentoGaEdificio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdDepartamentoGaEdificio0()
    {
        return $this->hasOne(NivelGeografico2::className(), ['nivelGeografico2' => 'idDepartamentoGaEdificio']);
    }

    /**
     * Gets query for [[IdMunicipioGaEdificio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdMunicipioGaEdificio0()
    {
        return $this->hasOne(NivelGeografico3::className(), ['nivelGeografico3' => 'idMunicipioGaEdificio']);
    }

    /**
     * Gets query for [[GaPisos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaPisos()
    {
        return $this->hasMany(GaPiso::className(), ['idGaEdificio' => 'idGaEdificio']);
    }
}
