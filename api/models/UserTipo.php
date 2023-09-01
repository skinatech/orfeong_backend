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
 * This is the model class for table "userTipo".
 *
 * @property int $idUserTipo Número único que indica la cantidad de tipos de usuario registrados en el sistema
 * @property string $nombreUserTipo Nombre del tipo de usuario
 * @property int $estadoUserTipo 0 Inactivo 10 Activo
 * @property string $creacionUserTipo Fecha de creación
 *
 * @property User[] $users
 */
class UserTipo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userTipo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreUserTipo', 'creacionUserTipo'], 'required'],
            [['estadoUserTipo'], 'integer'],
            [['creacionUserTipo'], 'safe'],
            [['nombreUserTipo'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idUserTipo' => 'Id User Tipo',
            'nombreUserTipo' => 'Nombre User Tipo',
            'estadoUserTipo' => 'Estado User Tipo',
            'creacionUserTipo' => 'Creacion User Tipo',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['idUserTipo' => 'idUserTipo']);
    }
}
