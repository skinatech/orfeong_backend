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
 * This is the model class for table "cgTrdMascaras".
 *
 * @property int $idCgTrdMascara Numero único de la TRD Mascara
 * @property string $nombreCgTrdMascara Nombre único de la TRD Mascara (dd = dependencia, ss = series, sb= subseries)
 * @property string $separadorCgTrdMascara Separador de la máscara
 * @property int $estadoCgTrdMascara Estado 0 Inactivo 10 Activo
 * @property string $creacionCgTrdMascara Creación de la TRD Mascara
 *
 * @property CgTrd[] $cgTrds
 */
class CgTrdMascaras extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTrdMascaras';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgTrdMascara'], 'required'],
            [['estadoCgTrdMascara'], 'integer'],
            [['creacionCgTrdMascara'], 'safe'],
            [['nombreCgTrdMascara'], 'string', 'max' => 20],
            [['separadorCgTrdMascara'], 'string', 'max' => 5],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTrdMascara' => 'Id máscara',
            'nombreCgTrdMascara' => 'Nombre máscara',
            'separadorCgTrdMascara' => 'Separador máscara',
            'estadoCgTrdMascara' => 'Estado máscara',
            'creacionCgTrdMascara' => 'Fecha creación máscara',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTrds()
    {
        return $this->hasMany(CgTrd::className(), ['idMascaraCgTrd' => 'idCgTrdMascara']);
    }
}
