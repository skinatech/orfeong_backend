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
 * This is the model class for table "cgdiasnolaborados".
 *
 * @property int $idCgDiaNoLaborado Identificador único del día no laborado
 * @property string $fechaCgDiaNoLaborado Fecha del día no laborado
 * @property int $estadoCgDiaNoLaborado Estado del  del día no laborado 10 activo 0 Inactivo
 * @property string $creacionCgDiaNoLaborado Fecha de creación del expediente
 */
class CgDiasNoLaborados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgDiasNoLaborados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['fechaCgDiaNoLaborado'], 'required'],
            [['fechaCgDiaNoLaborado', 'creacionCgDiaNoLaborado'], 'safe'],
            [['estadoCgDiaNoLaborado'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgDiaNoLaborado' => 'Id día no laborado',
            'fechaCgDiaNoLaborado' => 'Fecha día no laborado',
            'estadoCgDiaNoLaborado' => 'Estado día no laborado',
            'creacionCgDiaNoLaborado' => 'Creacion día no laborado',
        ];
    }
}
