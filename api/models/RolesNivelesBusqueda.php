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
 * This is the model class for table "rolesNivelesBusqueda".
 *
 * @property int $idRolNivelBusqueda Id rol nivel de búsqueda
 * @property string $nombreRolNivelBusqueda Nombre nivel de búsqueda
 * @property int $estadoRolNivelBusqueda Estado 0 Inactivo - 10 Activo    
 * @property string $creacionRolNivelBusqueda Fecha creación
 *
 * @property Roles[] $roles
 */
class RolesNivelesBusqueda extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rolesNivelesBusqueda';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreRolNivelBusqueda'], 'required'],
            [['estadoRolNivelBusqueda'], 'integer'],
            [['creacionRolNivelBusqueda'], 'safe'],
            [['nombreRolNivelBusqueda'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRolNivelBusqueda' => 'Id rol nivel de búsqueda',
            'nombreRolNivelBusqueda' => 'Nombre nivel de búsqueda',
            'estadoRolNivelBusqueda' => 'Estado 0 Inactivo - 10 Activo',
            'creacionRolNivelBusqueda' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[Roles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRoles()
    {
        return $this->hasMany(Roles::className(), ['idRolNivelBusqueda' => 'idRolNivelBusqueda']);
    }
}
