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
use common\models\User;

/**
 * This is the model class for table "roles".
 *
 * @property int $idRol Número único que identifica la cantida de roles en el sistema
 * @property string $nombreRol Nombre del rol
 * @property int $estadoRol 0 Inactivo 10 Activo
 * @property string $creacionRol Fecha de creación
 * @property int|null $idRolNivelBusqueda Id del nivel de búsqueda 
 *
 * @property RolesTipoDocumental[] $rolesTipoDocumentals
 * @property RolesTiposOperaciones[] $rolesTiposOperaciones
 * @property User[] $users
 * @property RolesNivelesBusqueda $idRolNivelBusqueda0
 */
class Roles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'roles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreRol', 'creacionRol'], 'required'],
            [['nombreRol'], 'unique'],
            [['estadoRol', 'idRolNivelBusqueda'], 'integer'],
            [['creacionRol'], 'safe'],
            [['nombreRol'], 'string', 'max' => 50],
            [['idRolNivelBusqueda'], 'exist', 'skipOnError' => true, 'targetClass' => RolesNivelesBusqueda::className(), 'targetAttribute' => ['idRolNivelBusqueda' => 'idRolNivelBusqueda']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRol' => 'Id Rol',
            'nombreRol' => 'Nombre Rol',
            'estadoRol' => 'Estado Rol',
            'creacionRol' => 'Fecha creación Rol',
            'idRolNivelBusqueda' => 'Id del nivel de búsqueda',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolesTiposOperaciones()
    {
        return $this->hasMany(RolesTiposOperaciones::className(), ['idRol' => 'idRol']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['idRol' => 'idRol']);
    }

    /**
     * Gets query for [[RolesTipoDocumentals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRolesTipoDocumentals()
    {
        return $this->hasMany(RolesTipoDocumental::className(), ['idRol' => 'idRol']);
    }
    

    /**
    * Gets query for [[IdRolNivelBusqueda0]].
    *
    * @return \yii\db\ActiveQuery
    */
    public function getIdRolNivelBusqueda0()
    {
        return $this->hasOne(RolesNivelesBusqueda::className(), ['idRolNivelBusqueda' => 'idRolNivelBusqueda']);
   }
}
