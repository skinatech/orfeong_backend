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
 * This is the model class for table "gaPiso".
 *
 * @property int $idGaPiso Id del piso
 * @property int $numeroGaPiso Número del piso
 * @property int $idGaEdificio Id del edificio
 * @property int $estadoGaPiso Estado del piso
 * @property string $creacionGaPiso Fecha creación
 *
 * @property GaBodega[] $gaBodegas
 * @property GaEdificio $idGaEdificio0
 */
class GaPiso extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaPiso';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['numeroGaPiso', 'idGaEdificio'], 'required'],
            [['numeroGaPiso', 'idGaEdificio', 'estadoGaPiso'], 'integer'],
            [['creacionGaPiso'], 'safe'],
            [['idGaEdificio'], 'exist', 'skipOnError' => true, 'targetClass' => GaEdificio::className(), 'targetAttribute' => ['idGaEdificio' => 'idGaEdificio']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaPiso' => 'Id del piso',
            'numeroGaPiso' => 'Número del piso',
            'idGaEdificio' => 'Id del edificio',
            'estadoGaPiso' => 'Estado del piso',
            'creacionGaPiso' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[GaBodegas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaBodegas()
    {
        return $this->hasMany(GaBodega::className(), ['idGaPiso' => 'idGaPiso']);
    }

    /**
     * Gets query for [[IdGaEdificio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGaEdificio0()
    {
        return $this->hasOne(GaEdificio::className(), ['idGaEdificio' => 'idGaEdificio']);
    }
}
