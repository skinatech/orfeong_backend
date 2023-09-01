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
 * This is the model class for table "gdTrd".
 *
 * @property int $idGdTrd Número único para identificar la tabla
 * @property int|null $idGdTrdDependencia
 * @property int|null $idGdTrdSerie
 * @property int|null $idGdTrdSubserie
 * @property int|null $idGdTrdTipoDocumental
 * @property int|null $estadoGdTrd 0 Inactivo - 10 Activo
 * @property string|null $creacionGdTrd Creación del registro
 *
 * @property GdTrdDependencias $gdTrdDependencia
 * @property GdTrdSeries $gdTrdSerie
 * @property GdTrdSubseries $gdTrdSubserie
 * @property GdTrdTiposDocumentales $gdTrdTipoDocumental
 * 
 * @property int $versionGdTrd
 * @property string $identificadorUnicoGdTrd
 */
class GdTrd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrd';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdTrdDependencia', 'idGdTrdSerie', 'idGdTrdSubserie', 'idGdTrdTipoDocumental', 'estadoGdTrd', 'versionGdTrd'], 'integer'],
            [['identificadorUnicoGdTrd'], 'string'],
            [['identificadorUnicoGdTrd'], 'string', 'max' => 13],
            [['creacionGdTrd'], 'safe'],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
            [['idGdTrdSerie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSeries::className(), 'targetAttribute' => ['idGdTrdSerie' => 'idGdTrdSerie']],
            [['idGdTrdSubserie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSubseries::className(), 'targetAttribute' => ['idGdTrdSubserie' => 'idGdTrdSubserie']],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrd' => 'Id Trd',
            'idGdTrdDependencia' => 'Id Trd dependencia',
            'idGdTrdSerie' => 'Id Trd serie',
            'idGdTrdSubserie' => 'Id Trd subserie',
            'idGdTrdTipoDocumental' => 'Id Trd tipo documental',
            'estadoGdTrd' => 'Estado Trd',
            'creacionGdTrd' => 'Creación Trd',
        ];
    }

    /**
     * Gets query for [[IdGdTrdDependencia0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependencia()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

    /**
     * Gets query for [[IdGdTrdSerie0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSerie()
    {
        return $this->hasOne(GdTrdSeries::className(), ['idGdTrdSerie' => 'idGdTrdSerie']);
    }

    /**
     * Gets query for [[IdGdTrdSubserie0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSubserie()
    {
        return $this->hasOne(GdTrdSubseries::className(), ['idGdTrdSubserie' => 'idGdTrdSubserie']);
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTipoDocumental()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }
}
