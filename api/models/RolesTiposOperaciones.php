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
 * This is the model class for table "rolesTiposOperaciones".
 *
 * @property int $idRolTipoOperacion
 * @property int $idRol
 * @property int $idRolOperacion
 *
 * @property Roles $rol
 * @property RolesOperaciones $rolOperacion
 */
class RolesTiposOperaciones extends \yii\db\ActiveRecord {

    /**
     * {@inheritdoc}
     */
    public static function tableName() {
        return 'rolesTiposOperaciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules() {
        return [
            [['idRol', 'idRolOperacion'], 'required'],
            [['idRol', 'idRolOperacion'], 'integer'],
            [['idRol'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::className(), 'targetAttribute' => ['idRol' => 'idRol']],
            [['idRolOperacion'], 'exist', 'skipOnError' => true, 'targetClass' => RolesOperaciones::className(), 'targetAttribute' => ['idRolOperacion' => 'idRolOperacion']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels() {
        return [
            'idRolTipoOperacion' => 'Id rol tipo operación',
            'idRol' => 'Id rol',
            'idRolOperacion' => 'Id tipo operación',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRol() {
        return $this->hasOne(Roles::className(), ['idRol' => 'idRol']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolOperacion() {
        return $this->hasOne(RolesOperaciones::className(), ['idRolOperacion' => 'idRolOperacion']);
    }

}
