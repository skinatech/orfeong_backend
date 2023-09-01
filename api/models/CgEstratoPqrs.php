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
 * This is the model class for table "cgEstratoPqrs".
 *
 * @property int $idCgEstratoPqrs
 * @property string $nombreCgEstratoPqrs
 * @property string $creacionCgEstratoPqrs
 * @property int $estadoCgEstratoPqrs
 */
class CgEstratoPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEstratoPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgEstratoPqrs'], 'required'],
            [['creacionCgEstratoPqrs'], 'safe'],
            [['estadoCgEstratoPqrs'], 'integer'],
            [['nombreCgEstratoPqrs'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEstratoPqrs' => '',
            'nombreCgEstratoPqrs' => '',
            'creacionCgEstratoPqrs' => '',
            'estadoCgEstratoPqrs' => '',
        ];
    }
}
