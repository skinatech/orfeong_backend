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
 * This is the model class for table "log".
 *
 * @property int $idLog
 * @property int $idUser
 * @property string $userNameLog
 * @property string $fechaLog
 * @property string $ipLog
 * @property string $moduloLog
 * @property string $eventoLog
 * @property string $antesLog
 * @property string $despuesLog
 *
 * @property User $user
 */
class Log extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'userNameLog', 'fechaLog', 'ipLog', 'moduloLog', 'eventoLog'], 'required'],
            [['idUser'], 'integer'],
            [['fechaLog'], 'safe'],
            [['eventoLog', 'antesLog', 'despuesLog'], 'string'],
            [['userNameLog', 'moduloLog'], 'string', 'max' => 80],
            [['ipLog'], 'string', 'max' => 40],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idLog' => 'Id Log',
            'idUser' => 'Id User',
            'userNameLog' => 'User Name Log',
            'fechaLog' => 'Fecha Log',
            'ipLog' => 'Ip Log',
            'moduloLog' => 'Módulo Log',
            'eventoLog' => 'Evento Log',
            'antesLog' => 'Antes Log',
            'despuesLog' => 'Después Log',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

}
