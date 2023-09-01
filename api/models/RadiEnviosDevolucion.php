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
 * This is the model class for table "radiEnviosDevolucion".
 *
 * @property int $idRadiEnvioDevolucion Número único de la tabla de relación
 * @property int $idCgMotivoDevolucion Id de los motivos de devolución
 * @property int $idRadiEnvio Id de los radi envios 
 * @property string|null $fechaDevolucion
 *
 * @property CgMotivosDevolucion $idCgMotivoDevolucion0
 * @property RadiEnvios $idRadiEnvio0
 */
class RadiEnviosDevolucion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiEnviosDevolucion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgMotivoDevolucion', 'idRadiEnvio'], 'required'],
            [['idCgMotivoDevolucion', 'idRadiEnvio'], 'integer'],
            [['fechaDevolucion'], 'string', 'max' => 7],
            [['idCgMotivoDevolucion'], 'exist', 'skipOnError' => true, 'targetClass' => CgMotivosDevolucion::className(), 'targetAttribute' => ['idCgMotivoDevolucion' => 'idCgMotivoDevolucion']],
            [['idRadiEnvio'], 'exist', 'skipOnError' => true, 'targetClass' => RadiEnvios::className(), 'targetAttribute' => ['idRadiEnvio' => 'idRadiEnvio']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiEnvioDevolucion' => 'Número único de la tabla de relación',
            'idCgMotivoDevolucion' => 'Motivo de devolución',
            'idRadiEnvio' => 'Id de los radi envios',
            'fechaDevolucion' => 'Fecha Devolucion',
        ];
    }

    /**
     * Gets query for [[IdCgMotivoDevolucion0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgMotivoDevolucion0()
    {
        return $this->hasOne(CgMotivosDevolucion::className(), ['idCgMotivoDevolucion' => 'idCgMotivoDevolucion']);
    }

    /**
     * Gets query for [[IdRadiEnvio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiEnvio0()
    {
        return $this->hasOne(RadiEnvios::className(), ['idRadiEnvio' => 'idRadiEnvio']);
    }
}
