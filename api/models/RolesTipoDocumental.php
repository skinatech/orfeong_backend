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
 * This is the model class for table "rolesTipoDocumental".
 *
 * @property int $idRolTipoDocumental Número único de la tabla
 * @property int $idRol Número que indica el rol
 * @property int $idGdTrdTipoDocumental Número que indica el tipo documental
 *
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 * @property Roles $idRol0
 */
class RolesTipoDocumental extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'rolesTipoDocumental';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRol', 'idGdTrdTipoDocumental'], 'required'],
            [['idRol', 'idGdTrdTipoDocumental'], 'integer'],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idRol'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::className(), 'targetAttribute' => ['idRol' => 'idRol']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRolTipoDocumental' => 'Id rol tipo documental',
            'idRol' => 'Número que indica el rol',
            'idGdTrdTipoDocumental' => 'Número que indica el tipo documental',
        ];
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdTipoDocumental0()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[IdRol0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRol0()
    {
        return $this->hasOne(Roles::className(), ['idRol' => 'idRol']);
    }
}
