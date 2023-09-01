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
 * This is the model class for table "rolesOperaciones".
 *
 * @property int $idRolOperacion
 * @property string $nombreRolOperacion
 * @property string $aliasRolOperacion
 * @property string $moduloRolOperacion
 * @property int $estadoRolOperacion
 * @property string $creacionRolOperacion
 * @property int $idRolModuloOperacion
 *
 * @property RolesModulosOperaciones $rolModuloOperacion
 * @property RolesTiposOperaciones[] $rolesTiposOperaciones
 */
class RolesOperaciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rolesOperaciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreRolOperacion', 'aliasRolOperacion', 'moduloRolOperacion', 'creacionRolOperacion', 'idRolModuloOperacion'], 'required'],
            [['nombreRolOperacion'], 'unique'],
            [['estadoRolOperacion', 'idRolModuloOperacion'], 'integer'],
            [['creacionRolOperacion'], 'safe'],
            [['nombreRolOperacion', 'aliasRolOperacion', 'moduloRolOperacion'], 'string', 'max' => 80],
            [['idRolModuloOperacion'], 'exist', 'skipOnError' => true, 'targetClass' => RolesModulosOperaciones::className(), 'targetAttribute' => ['idRolModuloOperacion' => 'idRolModuloOperacion']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRolOperacion' => 'Id rol operación',
            'nombreRolOperacion' => 'Nombre rol operación',
            'aliasRolOperacion' => 'Alias rol operación',
            'moduloRolOperacion' => 'Módulo rol operación',
            'estadoRolOperacion' => 'Estado rol operación (0 inactivo, 10 activo)',
            'creacionRolOperacion' => 'Fecha creación rol operación',
            'idRolModuloOperacion' => 'Id rol módulo operación',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolModuloOperacion()
    {
        return $this->hasOne(RolesModulosOperaciones::className(), ['idRolModuloOperacion' => 'idRolModuloOperacion']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolesTiposOperaciones()
    {
        return $this->hasMany(RolesTiposOperaciones::className(), ['idRolOperacion' => 'idRolOperacion']);
    }
}
