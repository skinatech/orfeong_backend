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
 * This is the model class for table "cggruposusuarios".
 *
 * @property int $idCgGrupoUsuarios
 * @property string $nombreCgGrupoUsuarios
 * @property int $estadoCgGrupoUsuarios
 * @property string $creacionCgGrupoUsuarios
 *
 * @property Cgtiposgruposusuarios[] $cgtiposgruposusuarios
 */
class CgGruposUsuarios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgGruposUsuarios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgGrupoUsuarios'], 'required'],
            [['estadoCgGrupoUsuarios'], 'integer'],
            [['creacionCgGrupoUsuarios'], 'safe'],
            [['nombreCgGrupoUsuarios'], 'string', 'max' => 80],
            [['nombreCgGrupoUsuarios'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgGrupoUsuarios' => 'Id grupo usuarios',
            'nombreCgGrupoUsuarios' => 'Nombre grupo usuarios',
            'estadoCgGrupoUsuarios' => 'Estado',
            'creacionCgGrupoUsuarios' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[Cgtiposgruposusuarios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgtiposGruposUsuarios()
    {
        return $this->hasMany(CgTiposGruposUsuarios::className(), ['idGrupoUsuarios' => 'idCgGrupoUsuarios']);
    }
}
