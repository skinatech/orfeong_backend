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
 * This is the model class for table "rolesModulosOperaciones".
 *
 * @property int $idRolModuloOperacion Número único de la tabla
 * @property string $nombreRolModuloOperacion Nombre del modulo o menu a visualizar
 * @property string $classRolModuloOperacion Icono del modulo en el menú
 * @property string $rutaRolModuloOperacion Ruta donde se dirije el modulo en frontend
 * @property int $ordenModuloOperacion Orden del menú del sistema
 * @property int $estadoRolModuloOperacion 0 Inactivo 10 Activo
 *
 * @property Notificaciones[] $notificaciones
 * @property RolesOperaciones[] $rolesOperaciones
 */
class RolesModulosOperaciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rolesModulosOperaciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRolModuloOperacion', 'nombreRolModuloOperacion', 'classRolModuloOperacion', 'rutaRolModuloOperacion'], 'required'],
            [['idRolModuloOperacion', 'ordenModuloOperacion', 'estadoRolModuloOperacion'], 'integer'],
            [['nombreRolModuloOperacion'], 'string', 'max' => 45],
            [['classRolModuloOperacion', 'rutaRolModuloOperacion'], 'string', 'max' => 40],
            [['idRolModuloOperacion'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRolModuloOperacion' => 'Id Rol Modulo Operacion',
            'nombreRolModuloOperacion' => 'Nombre Rol Modulo Operacion',
            'classRolModuloOperacion' => 'Class Rol Modulo Operacion',
            'rutaRolModuloOperacion' => 'Ruta Rol Modulo Operacion',
            'ordenModuloOperacion' => 'Orden Modulo Operacion',
            'estadoRolModuloOperacion' => 'Estado Rol Modulo Operacion',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getNotificaciones()
    {
        return $this->hasMany(Notificaciones::className(), ['idRolModuloOperacion' => 'idRolModuloOperacion']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolesOperaciones()
    {
        return $this->hasMany(RolesOperaciones::className(), ['idRolModuloOperacion' => 'idRolModuloOperacion']);
    }
}
