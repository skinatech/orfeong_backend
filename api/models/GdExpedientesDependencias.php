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
 * This is the model class for table "gdExpedientesDependencias".
 *
 * @property int $idGdExpedientesDependencias
 * @property int $idGdExpediente
 * @property int $idGdTrdDependencia
 * @property int $estadoGdExpedientesDependencias
 * @property string $creacionGdExpedientesDependencias
 *
 * @property GdExpedientes $idGdExpediente0
 * @property GdTrdDependencias $idGdTrdDependencia0
 */
class GdExpedientesDependencias extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdExpedientesDependencias';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpediente', 'idGdTrdDependencia'], 'required'],
            [['idGdExpediente', 'idGdTrdDependencia', 'estadoGdExpedientesDependencias'], 'integer'],
            [['creacionGdExpedientesDependencias'], 'safe'],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdExpedientesDependencias' => 'Id Gd Expedientes Dependencias',
            'idGdExpediente' => 'Id Gd Expediente',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'estadoGdExpedientesDependencias' => 'Estado Gd Expedientes Dependencias',
            'creacionGdExpedientesDependencias' => 'Creacion Gd Expedientes Dependencias',
        ];
    }

    /**
     * Gets query for [[IdGdExpediente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdExpediente0()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[IdGdTrdDependencia0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdDependencia0()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }
}
