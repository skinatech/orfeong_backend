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
 * This is the model class for table "cgTiposRadicadosTransacciones".
 *
 * @property int $idCgTiposRadicadosTransacciones
 * @property int $idCgTransaccionRadicado
 * @property int $idCgTipoRadicado
 * @property int $orderCgTipoRadicado
 *
 * @property CgTiposRadicados $cgTipoRadicado
 * @property CgTransaccionesRadicados $cgTransaccionRadicado
 */
class CgTiposRadicadosTransacciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTiposRadicadosTransacciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgTransaccionRadicado', 'idCgTipoRadicado', 'orderCgTipoRadicado'], 'required'],
            [['idCgTransaccionRadicado', 'idCgTipoRadicado', 'orderCgTipoRadicado'], 'integer'],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
            [['idCgTransaccionRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTransaccionesRadicados::className(), 'targetAttribute' => ['idCgTransaccionRadicado' => 'idCgTransaccionRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTiposRadicadosTransacciones' => 'Id Cg Tipos Radicados Transacciones',
            'idCgTransaccionRadicado' => 'Id Cg Transaccion Radicado',
            'idCgTipoRadicado' => 'Id Cg Tipo Radicado',
            'orderCgTipoRadicado' => 'Order Cg Tipo Radicado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTipoRadicado()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTransaccionRadicado()
    {
        return $this->hasOne(CgTransaccionesRadicados::className(), ['idCgTransaccionRadicado' => 'idCgTransaccionRadicado']);
    }
}
