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
 * This is the model class for table "radiRadicadosResoluciones".
 *
 * @property int $idRadiRadicadoResoluciones
 * @property int $idRadiRadicado
 * @property int $numeroRadiRadicadoResolucion
 * @property string $fechaRadiRadicadoResolucion
 * @property float $valorRadiRadicadoResolucion
 * @property string $creacionRadiRadicadoResolucion
 *
 * @property RadiRadicados $idRadiRadicado0
 */
class RadiRadicadosResoluciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicadosResoluciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'numeroRadiRadicadoResolucion', 'fechaRadiRadicadoResolucion'], 'required'],
            [['idRadiRadicado', 'numeroRadiRadicadoResolucion'], 'integer'],
            [['fechaRadiRadicadoResolucion', 'creacionRadiRadicadoResolucion'], 'safe'],
            [['valorRadiRadicadoResolucion'], 'number'],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicadoResoluciones' => 'Id Radi Radicado Resoluciones',
            'idRadiRadicado' => 'Id Radi Radicado',
            'numeroRadiRadicadoResolucion' => 'Numero Radi Radicado Resolucion',
            'fechaRadiRadicadoResolucion' => 'Fecha Radi Radicado Resolucion',
            'valorRadiRadicadoResolucion' => 'Valor Radi Radicado Resolucion',
            'creacionRadiRadicadoResolucion' => 'Creacion Radi Radicado Resolucion',
        ];
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiRadicado0()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }
}
