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
 * This is the model class for table "gdhistoricoexpedientes".
 *
 * @property int $idGdHistoricoExpediente
 * @property int $idGdExpediente
 * @property int $idUser
 * @property int $idGdTrdDependencia
 * @property int $operacionGdHistoricoExpediente
 * @property string $observacionGdHistoricoExpediente
 * @property int $estadoGdHistoricoExpediente
 * @property string $creacionGdHistoricoExpediente
 *
 * @property GdexpedientesInclusion $idGdExpediente
 * @property User $idUser
 * @property GdTrdDependencias $idGdTrdDependencia
 */
class GdHistoricoExpedientes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdHistoricoExpedientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpediente', 'idUser', 'idGdTrdDependencia', 'operacionGdHistoricoExpediente', 'observacionGdHistoricoExpediente'], 'required'],
            [['idGdExpediente', 'idUser', 'idGdTrdDependencia', 'operacionGdHistoricoExpediente', 'estadoGdHistoricoExpediente'], 'integer'],
            [['observacionGdHistoricoExpediente'], 'string', 'max' => 500],
            [['creacionGdHistoricoExpediente'], 'safe'],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']], 
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdHistoricoExpediente' => 'Id Gd Historico Expediente',
            'idGdExpediente' => 'Id Gd Expediente',
            'idUser' => 'Id User',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'operacionGdHistoricoExpediente' => 'Operacion Gd Historico Expediente',
            'observacionGdHistoricoExpediente' => 'Observacion Gd Historico Expediente',
            'estadoGdHistoricoExpediente' => 'Estado Gd Historico Expediente',
            'creacionGdHistoricoExpediente' => 'Creacion Gd Historico Expediente',
        ];
    }

    /**
     * Gets query for [[gdExpedienteInclusion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpediente()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[userHistoricoExpediente]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserHistoricoExpediente()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[gdTrdDependencia0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependencia()
    {
        return $this->hasOne(GdtrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

}
