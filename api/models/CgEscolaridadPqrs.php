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
 * This is the model class for table "cgEscolaridadPqrs".
 *
 * @property int $idCgEscolaridadPqrs
 * @property string $nombreCgEscolaridadPqrs
 * @property string $creacionCgEscolaridadPqrs
 * @property int $estadoCgEscolaridadPqrs
 */
class CgEscolaridadPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEscolaridadPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgEscolaridadPqrs'], 'required'],
            [['creacionCgEscolaridadPqrs'], 'safe'],
            [['estadoCgEscolaridadPqrs'], 'integer'],
            [['nombreCgEscolaridadPqrs'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEscolaridadPqrs' => '',
            'nombreCgEscolaridadPqrs' => '',
            'creacionCgEscolaridadPqrs' => '',
            'estadoCgEscolaridadPqrs' => '',
        ];
    }
}
