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
 * This is the model class for table "gdExpedientesInclusion".
 *
 * @property int $idGdExpedienteInclusion Identificador único de la inclusión del expediente
 * @property int $idRadiRadicado Identificador único de la inclusión del radicado
 * @property int $idGdExpediente Identificador único de la inclusión del expediente
 * @property int $estadoGdExpedienteInclusion Estado de la inclusión del expediente 10 activo 0 Inactivo
 * @property string $creacionGdExpedienteInclusion Fecha de creación de la inclusión del expediente
 *
 * @property GaArchivo[] $gaArchivos 
 * @property GaPrestamos[] $gaPrestamos
 * @property GdExpedientes $idGdExpediente
 * @property RadiRadicados $idRadiRadicado
 * @property GdHistoricoExpedientes[] $gdHistoricoExpedientes
 * @property GaArchivo $gaArchivo
 */
class GdExpedientesInclusion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdExpedientesInclusion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idGdExpediente'], 'required'],
            [['idRadiRadicado', 'idGdExpediente', 'estadoGdExpedienteInclusion'], 'integer'],
            [['creacionGdExpedienteInclusion'], 'safe'],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdExpedienteInclusion' => 'Id expediente inclusión',
            'idRadiRadicado' => 'Id radicado',
            'idGdExpediente' => 'Id expediente',
            'estadoGdExpedienteInclusion' => 'Estado',
            'creacionGdExpedienteInclusion' => 'Fecha creación expediente inclusión',
        ];
    }

    /**
     * Gets query for [[expediente]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpediente()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[radiRadicado]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[GdHistoricoExpedientes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdHistoricoExpedientes()
    {
        return $this->hasMany(GdHistoricoExpedientes::className(), ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']);
    }


    /** Gets query for [[GaPrestamos]].   
    *
    * @return \yii\db\ActiveQuery
    */
    public function getGaPrestamos()
    {
        return $this->hasMany(GaPrestamos::className(), ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']);
    }
}
