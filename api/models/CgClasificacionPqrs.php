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
 * This is the model class for table "cgClasificacionPqrs".
 *
 * @property int $idCgClasificacionPqrs
 * @property string $nombreCgClasificacionPqrs
 * @property string $creacionCgClasificacionPqrs
 * @property int $estadoCgClasificacionPqrs
 */
class CgClasificacionPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgClasificacionPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgClasificacionPqrs'], 'required'],
            [['creacionCgClasificacionPqrs'], 'safe'],
            [['estadoCgClasificacionPqrs'], 'integer'],
            [['nombreCgClasificacionPqrs'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgClasificacionPqrs' => '',
            'nombreCgClasificacionPqrs' => '',
            'creacionCgClasificacionPqrs' => '',
            'estadoCgClasificacionPqrs' => '',
        ];
    }
}
