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
 * This is the model class for table "gaHistoricoPrestamoExpediente".
 *
 * @property int $idGaHistoricoPrestamoExpediente
 * @property int $idGaPrestamoExpediente
 * @property int $idUser
 * @property int $idGdTrdDependencia
 * @property string $fechaGaHistoricoPrestamoExpediente
 * @property string $observacionGaHistoricoPrestamoExpediente
 * @property int $estadoGaHistoricoPrestamoExpediente
 * @property string $creacionGaHistoricoPrestamoExpediente
 *
 * @property GaPrestamosExpedientes $idGaPrestamoExpediente0
 * @property User $idUser0
 * @property GdTrdDependencias $idGdTrdDependencia0
 */
class GaHistoricoPrestamoExpediente extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaHistoricoPrestamoExpediente';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGaPrestamoExpediente', 'idUser', 'idGdTrdDependencia', 'fechaGaHistoricoPrestamoExpediente', 'observacionGaHistoricoPrestamoExpediente', 'estadoGaHistoricoPrestamoExpediente', 'creacionGaHistoricoPrestamoExpediente'], 'required'],
            [['idGaPrestamoExpediente', 'idUser', 'idGdTrdDependencia', 'estadoGaHistoricoPrestamoExpediente'], 'integer'],
            [['fechaGaHistoricoPrestamoExpediente', 'creacionGaHistoricoPrestamoExpediente'], 'safe'],
            [['observacionGaHistoricoPrestamoExpediente'], 'string', 'max' => 500],
            [['idGaPrestamoExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GaPrestamosExpedientes::className(), 'targetAttribute' => ['idGaPrestamoExpediente' => 'idGaPrestamoExpediente']],
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
            'idGaHistoricoPrestamoExpediente' => 'Id Ga Historico Prestamo Expediente',
            'idGaPrestamoExpediente' => 'Id Ga Prestamo Expediente',
            'idUser' => 'Id User',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'fechaGaHistoricoPrestamoExpediente' => 'Fecha Ga Historico Prestamo Expediente',
            'observacionGaHistoricoPrestamoExpediente' => 'Observacion Ga Historico Prestamo Expediente',
            'estadoGaHistoricoPrestamoExpediente' => 'Estado Ga Historico Prestamo Expediente',
            'creacionGaHistoricoPrestamoExpediente' => 'Creacion Ga Historico Prestamo Expediente',
        ];
    }

    /**
     * Gets query for [[IdGaPrestamoExpediente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGaPrestamoExpediente0()
    {
        return $this->hasOne(GaPrestamosExpedientes::className(), ['idGaPrestamoExpediente' => 'idGaPrestamoExpediente']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
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
