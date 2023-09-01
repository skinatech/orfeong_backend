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
 * This is the model class for table "cgGrupoEtnicoPqrs".
 *
 * @property int $idCgGrupoEtnicoPqrs
 * @property string $nombreCgGrupoEtnicoPqrs
 * @property string $creacionCgGrupoEtnicoPqrs
 * @property int $estadoCgGrupoEtnicoPqrs
 */
class CgGrupoEtnicoPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgGrupoEtnicoPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgGrupoEtnicoPqrs'], 'required'],
            [['creacionCgGrupoEtnicoPqrs'], 'safe'],
            [['estadoCgGrupoEtnicoPqrs'], 'integer'],
            [['nombreCgGrupoEtnicoPqrs'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgGrupoEtnicoPqrs' => '',
            'nombreCgGrupoEtnicoPqrs' => '',
            'creacionCgGrupoEtnicoPqrs' => '',
            'estadoCgGrupoEtnicoPqrs' => '',
        ];
    }
}
