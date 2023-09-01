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
 * This is the model class for table "cgtiposgruposusuarios".
 *
 * @property int $idCgTipoGrupoUsuarios
 * @property int $idCgGrupoUsuarios
 * @property int $idUser
 *
 * @property CgGruposusuarios $idGrupoUsuarios
 * @property idUser $idUser
 */
class CgTiposGruposUsuarios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTiposGruposUsuarios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgGrupoUsuarios', 'idUser'], 'required'],
            [['idCgGrupoUsuarios', 'idUser'], 'integer'],
            [['idCgGrupoUsuarios'], 'exist', 'skipOnError' => true, 'targetClass' => CgGruposUsuarios::className(), 'targetAttribute' => ['idCgGrupoUsuarios' => 'idCgGrupoUsuarios']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTipoGrupoUsuarios' => 'Id tipo grupo usuarios',
            'idCgGrupoUsuarios' => 'Id grupo usuario',
            'idUser' => 'Id usuario',
        ];
    }

    /**
     * Gets query for [[IdGrupoUsuarios0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGrupoUsuarios()
    {
        return $this->hasOne(CgGruposUsuarios::className(), ['idCgGrupoUsuarios' => 'idCgGrupoUsuarios']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
