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
 * This is the model class for table "userHistoryPassword".
 *
 * @property int $idUserHistoryPassword número único de la tabla
 * @property string $hashUserHistoryPassword Contraseña definida por el usuario
 * @property int $idUser ID del usuario
 * @property string $creacionUserHistoryPassword Fecha de creación
 *
 * @property User $idUser0
 */
class UserHistoryPassword extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userHistoryPassword';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['hashUserHistoryPassword', 'idUser', 'creacionUserHistoryPassword'], 'required'],
            [['idUser'], 'integer'],
            [['creacionUserHistoryPassword'], 'safe'],
            [['hashUserHistoryPassword'], 'string', 'max' => 255],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idUserHistoryPassword' => 'número único de la tabla',
            'hashUserHistoryPassword' => 'Contraseña definida por el usuario',
            'idUser' => 'ID del usuario',
            'creacionUserHistoryPassword' => 'Fecha de creación',
        ];
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
