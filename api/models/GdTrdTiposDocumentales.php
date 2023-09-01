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
 * This is the model class for table "gdTrdTiposDocumentales".
 *
 * @property int $idGdTrdTipoDocumental Numero único de tipo documental
 * @property string $nombreTipoDocumental
 * @property int $diasTramiteTipoDocumental Son los días de tramite
 * @property int $estadoTipoDocumental Estado 0 Inactivo 10 Activo
 * @property string $creacionTipoDocumental Creación del tipo documental
 *
 * @property GdTrd[] $gdTrds
 * @property RadiRadicados[] $radiRadicados
 * @property RolesTipoDocumental[] $rolesTipoDocumentals
 * @property CgTipoRadicadoDocumental[] $cgTipoRadicadoDocumental
 */
class GdTrdTiposDocumentales extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdTiposDocumentales';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreTipoDocumental'], 'required'],
            [['diasTramiteTipoDocumental', 'estadoTipoDocumental'], 'integer'],
            [['creacionTipoDocumental'], 'safe'],
            [['nombreTipoDocumental'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdTipoDocumental' => 'Id Trd tipo documental',
            'nombreTipoDocumental' => 'Nombre tipo documental',
            'diasTramiteTipoDocumental' => 'Días Trámite tipo documental',
            'estadoTipoDocumental' => 'Estado tipo documental',
            'creacionTipoDocumental' => 'Creación tipo documental',
        ];
    }

    /**
     * Gets query for [[GdTrds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrds()
    {
        return $this->hasMany(GdTrd::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[RadiRadicados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicados()
    {
        return $this->hasMany(RadiRadicados::className(), ['idTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[CgTipoRadicadoDocumental]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgTipoRadicadoDocumental()
    {
        return $this->hasMany(CgTipoRadicadoDocumental::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[RolesTipoDocumentals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRolesTipoDocumentals()
    {
        return $this->hasMany(RolesTipoDocumental::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }
}
