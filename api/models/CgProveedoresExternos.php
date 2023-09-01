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
 * This is the model class for table "cgProveedoresExternos".
 *
 * @property int $idCgProveedorExterno
 * @property string $nombreCgProveedorExterno
 * @property string $tokenCgProveedorExterno
 * @property int $userCgCreadorProveedorExterno
 * @property int $userCgProveedorExterno
 * @property string $creacionCgProveedorExterno
 * @property int $estadoCgProveedorExterno
 *
 * @property User $userCgCreadorProveedorExterno0
 * @property User $userCgProveedorExterno0
 */
class CgProveedoresExternos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgProveedoresExternos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgProveedorExterno', 'tokenCgProveedorExterno', 'userCgCreadorProveedorExterno', 'userCgProveedorExterno', 'creacionCgProveedorExterno'], 'required'],
            [['userCgCreadorProveedorExterno', 'userCgProveedorExterno', 'estadoCgProveedorExterno'], 'integer'],
            [['creacionCgProveedorExterno'], 'safe'],
            [['nombreCgProveedorExterno'], 'string', 'max' => 50],
            [['tokenCgProveedorExterno'], 'string', 'max' => 255],
            [['userCgCreadorProveedorExterno'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userCgCreadorProveedorExterno' => 'id']],
            [['userCgProveedorExterno'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['userCgProveedorExterno' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgProveedorExterno' => 'Id Cg Proveedor Externo',
            'nombreCgProveedorExterno' => 'Nombre Cg Proveedor Externo',
            'tokenCgProveedorExterno' => 'Token Cg Proveedor Externo',
            'userCgCreadorProveedorExterno' => 'User Cg Creador Proveedor Externo',
            'userCgProveedorExterno' => 'User Cg Proveedor Externo',
            'creacionCgProveedorExterno' => 'Creacion Cg Proveedor Externo',
            'estadoCgProveedorExterno' => 'Estado Cg Proveedor Externo',
        ];
    }

    /**
     * Gets query for [[UserCgCreadorProveedorExterno0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCgCreadorProveedorExterno0()
    {
        return $this->hasOne(User::className(), ['id' => 'userCgCreadorProveedorExterno']);
    }

    /**
     * Gets query for [[UserCgProveedorExterno0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCgProveedorExterno0()
    {
        return $this->hasOne(User::className(), ['id' => 'userCgProveedorExterno']);
    }
}
