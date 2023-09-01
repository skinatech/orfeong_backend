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
 * This is the model class for table "radiRadicadosTipos".
 *
 * @property int $idRadiRadicadoTipo identificador de la tabla
 * @property int $idCgTipoRadicado Este campo guarda el id del tipo de radicado que se esta generando, segun la tabla correspondiente
 * @property int $idRadiRadicado Este campo guarda el id del numero de radicado, segun la tabla correspondiente
 *
 * @property RadiRadicados $radiRadicado
 * @property CgTiposRadicados $cgTipoRadicado
 */
class RadiRadicadosTipos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicadosTipos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgTipoRadicado', 'idRadiRadicado'], 'required'],
            [['idCgTipoRadicado', 'idRadiRadicado'], 'integer'],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicadoTipo' => 'Id Radi Radicado Tipo',
            'idCgTipoRadicado' => 'Id Cg Tipo Radicado',
            'idRadiRadicado' => 'Id Radi Radicado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTipoRadicado()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}
