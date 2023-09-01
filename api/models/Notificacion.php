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
 * This is the model class for table "notificacion".
 *
 * @property int $idNotificacion Id de notificación
 * @property int $idUserCreador Id del usuario creador
 * @property int $idUserNotificado Id del usuario notificado
 * @property string $notificacion Texto de la notificación
 * @property string $urlNotificacion Url de redirección
 * @property int $estadoNotificacion Estado
 * @property string $creacionNotificacion Fecha creación
 *
 * @property User $idUserCreador0
 * @property User $idUserNotificado0
 */
class Notificacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'notificacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUserCreador', 'idUserNotificado', 'notificacion', 'urlNotificacion'], 'required'],
            [['idUserCreador', 'idUserNotificado', 'estadoNotificacion'], 'integer'],
            [['creacionNotificacion'], 'safe'],
            [['notificacion', 'urlNotificacion'], 'string', 'max' => 80],
            [['idUserCreador'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUserCreador' => 'id']],
            [['idUserNotificado'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUserNotificado' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idNotificacion' => 'Id de notificación',
            'idUserCreador' => 'Id del usuario creador',
            'idUserNotificado' => 'Id del usuario notificado',
            'notificacion' => 'Texto de la notificación',
            'urlNotificacion' => 'Url de redirección',
            'estadoNotificacion' => 'Estado',
            'creacionNotificacion' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[IdUserCreador0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUserCreador0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUserCreador']);
    }

    /**
     * Gets query for [[IdUserNotificado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUserNotificado0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUserNotificado']);
    }
}
