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
 * This is the model class for table "gaPrestamosExpedientes".
 *
 * @property int $idGaPrestamoExpediente
 * @property int $idGdExpediente
 * @property int $idUser
 * @property int $idGdTrdDependencia
 * @property string $fechaSolicitudGaPrestamoExpediente
 * @property int $idTipoPrestamoGaPrestamoExpediente
 * @property int $idRequerimientoGaPrestamoExpediente
 * @property string $observacionGaPrestamoExpediente
 * @property int $estadoGaPrestamoExpediente
 * @property string $creacionGaPrestamoExpediente
 *
 * @property GaHistoricoPrestamoExpediente[] $gaHistoricoPrestamoExpedientes
 * @property GdExpedientes $idGdExpediente0
 * @property GdTrdDependencias $idGdTrdDependencia0
 * @property User $idUser0
 */
class GaPrestamosExpedientes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaPrestamosExpedientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpediente', 'idUser', 'idGdTrdDependencia', 'fechaSolicitudGaPrestamoExpediente', 'idTipoPrestamoGaPrestamoExpediente', 'idRequerimientoGaPrestamoExpediente', 'observacionGaPrestamoExpediente', 'estadoGaPrestamoExpediente', 'creacionGaPrestamoExpediente'], 'required'],
            [['idGdExpediente', 'idUser', 'idGdTrdDependencia', 'idTipoPrestamoGaPrestamoExpediente', 'idRequerimientoGaPrestamoExpediente', 'estadoGaPrestamoExpediente'], 'integer'],
            [['fechaSolicitudGaPrestamoExpediente', 'creacionGaPrestamoExpediente'], 'safe'],
            [['observacionGaPrestamoExpediente'], 'string', 'max' => 255],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaPrestamoExpediente' => 'Id Ga Prestamo Expediente',
            'idGdExpediente' => 'Id Gd Expediente',
            'idUser' => 'Id User',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'fechaSolicitudGaPrestamoExpediente' => 'Fecha Solicitud Ga Prestamo Expediente',
            'idTipoPrestamoGaPrestamoExpediente' => 'Id Tipo Prestamo Ga Prestamo Expediente',
            'idRequerimientoGaPrestamoExpediente' => 'Id Requerimiento Ga Prestamo Expediente',
            'observacionGaPrestamoExpediente' => 'Observacion Ga Prestamo Expediente',
            'estadoGaPrestamoExpediente' => 'Estado Ga Prestamo Expediente',
            'creacionGaPrestamoExpediente' => 'Creacion Ga Prestamo Expediente',
        ];
    }

    /**
     * Gets query for [[GaHistoricoPrestamoExpedientes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaHistoricoPrestamoExpedientes()
    {
        return $this->hasMany(GaHistoricoPrestamoExpediente::className(), ['idGaPrestamoExpediente' => 'idGaPrestamoExpediente']);
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

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
