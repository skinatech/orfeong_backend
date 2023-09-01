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
 * This is the model class for table "cgGrupoSisbenPqrs".
 *
 * @property int $idCgGrupoSisbenPqrs
 * @property string $nombreCgGrupoSisbenPqrs
 * @property string $creacionCgGrupoSisbenPqrs
 * @property int $estadoCgGrupoSisbenPqrs
 */
class CgGrupoSisbenPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgGrupoSisbenPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgGrupoSisbenPqrs'], 'required'],
            [['creacionCgGrupoSisbenPqrs'], 'safe'],
            [['estadoCgGrupoSisbenPqrs'], 'integer'],
            [['nombreCgGrupoSisbenPqrs'], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgGrupoSisbenPqrs' => '',
            'nombreCgGrupoSisbenPqrs' => '',
            'estadoCgGrupoSisbenPqrs' => '',
            'creacionCgGrupoSisbenPqrs' => '',
        ];
    }
}
