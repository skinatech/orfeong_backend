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
 * This is the model class for table "cgRegionales".
 *
 * @property int $idCgRegional
 * @property string $nombreCgRegional
 * @property int $estadoCgRegional
 * @property string $creacionCgRegional
 * @property int $idNivelGeografico3
 * @property string $siglaCgRegional 
 *
 * @property NivelGeografico3 $nivelGeografico3
 * @property GdTrdDependencias[] $gdTrdDependencias
 */
class CgRegionales extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgRegionales';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgRegional','siglaCgRegional'], 'required'],
            [['estadoCgRegional', 'idNivelGeografico3'], 'integer'],
            [['creacionCgRegional'], 'safe'],
            [['nombreCgRegional'], 'string', 'max' => 80],
            [['nombreCgRegional','siglaCgRegional'], 'unique'],
            [['idNivelGeografico3'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico3::className(), 'targetAttribute' => ['idNivelGeografico3' => 'nivelGeografico3']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgRegional' => 'Id regional',
            'nombreCgRegional' => 'Nombre regional',
            'estadoCgRegional' => 'Estado',
            'creacionCgRegional' => 'Fecha creación',
            'idNivelGeografico3' => 'Id del municipio',
            'siglaCgRegional' => 'sigla regional',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico3()
    {
        return $this->hasOne(NivelGeografico3::className(), ['nivelGeografico3' => 'idNivelGeografico3']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependencias()
    {
        return $this->hasMany(GdTrdDependencias::className(), ['idCgRegional' => 'idCgRegional']);
    }
}
