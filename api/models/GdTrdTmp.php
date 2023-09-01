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
 * This is the model class for table "gdTrdTmp".
 *
 * @property int $idGdTrdTmp Número único para identificar la tabla
 * @property int|null $idGdTrdDependenciaTmp
 * @property int|null $idGdTrdSerieTmp
 * @property int|null $idGdTrdSubserieTmp
 * @property int|null $idGdTrdTipoDocumentalTmp
 * @property int|null $estadoGdTrdTmp 0 Inactivo - 10 Activo
 * @property string|null $creacionGdTrdTmp Creación del registro
 *
 * @property GdTrdDependenciasTmp $idGdTrdDependenciaTmp
 * @property GdTrdSeriesTmp $idGdTrdSerieTmp
 * @property GdTrdSubseriesTmp $idGdTrdSubserieTmp
 * @property GdTrdTiposDocumentalesTmp $idGdTrdTipoDocumentalTmp
 */
class GdTrdTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdTmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdTrdDependenciaTmp', 'idGdTrdSerieTmp', 'idGdTrdSubserieTmp', 'idGdTrdTipoDocumentalTmp', 'estadoGdTrdTmp'], 'integer'],
            [['creacionGdTrdTmp'], 'safe'],
            [['idGdTrdDependenciaTmp'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependenciasTmp::className(), 'targetAttribute' => ['idGdTrdDependenciaTmp' => 'idGdTrdDependenciaTmp']],
            [['idGdTrdSerieTmp'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSeriesTmp::className(), 'targetAttribute' => ['idGdTrdSerieTmp' => 'idGdTrdSerieTmp']],
            [['idGdTrdSubserieTmp'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSubseriesTmp::className(), 'targetAttribute' => ['idGdTrdSubserieTmp' => 'idGdTrdSubserieTmp']],
            [['idGdTrdTipoDocumentalTmp'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentalesTmp::className(), 'targetAttribute' => ['idGdTrdTipoDocumentalTmp' => 'idGdTrdTipoDocumentalTmp']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdTmp' => 'Id Gd Trd temporal',
            'idGdTrdDependenciaTmp' => 'Id Trd dependencia temporal',
            'idGdTrdSerieTmp' => 'Id Trd serie temporal',
            'idGdTrdSubserieTmp' => 'Id Trd subserie temporal',
            'idGdTrdTipoDocumentalTmp' => 'Id Trd tipo documental temporal',
            'estadoGdTrdTmp' => 'Estado Trd temporal',
            'creacionGdTrdTmp' => 'Creación Trd temporal',
        ];
    }

    /**
     * Gets query for [[IdGdTrdDependenciaTmp0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependenciaTmp()
    {
        return $this->hasOne(GdTrdDependenciasTmp::className(), ['idGdTrdDependenciaTmp' => 'idGdTrdDependenciaTmp']);
    }

    /**
     * Gets query for [[IdGdTrdSerieTmp0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSerieTmp()
    {
        return $this->hasOne(GdTrdSeriesTmp::className(), ['idGdTrdSerieTmp' => 'idGdTrdSerieTmp']);
    }

    /**
     * Gets query for [[IdGdTrdSubserieTmp0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSubserieTmp()
    {
        return $this->hasOne(GdTrdSubseriesTmp::className(), ['idGdTrdSubserieTmp' => 'idGdTrdSubserieTmp']);
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumentalTmp0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTipoDocumentalTmp()
    {
        return $this->hasOne(GdTrdTiposDocumentalesTmp::className(), ['idGdTrdTipoDocumentalTmp' => 'idGdTrdTipoDocumentalTmp']);
    }

}
