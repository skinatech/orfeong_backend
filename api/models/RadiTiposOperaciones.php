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
 * This is the model class for table "radiTiposOperaciones".
 *
 * @property int $idRadiTipoOperacion Identificador unido de la tabla 
 * @property int $idCgOperacionTipoRadicado Guarda el identificador de la tabla CgOperacionesTiposRadicados la cual guarda la acciones que se pueden realizar en un radicado o en un documento
 * @property int $idCgTipoRadicado Guarda el identificador de la tabla CgTiposRadicados la cual guarda la información de todos los tipos de radicados que puede utiulizar el cliente.
 *
 * @property CgOperacionesTiposRadicados $cgOperacionTipoRadicado
 * @property CgTiposRadicados $cgTipoRadicado
 */
class RadiTiposOperaciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiTiposOperaciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgOperacionTipoRadicado', 'idCgTipoRadicado'], 'required'],
            [['idCgOperacionTipoRadicado', 'idCgTipoRadicado'], 'integer'],
            [['idCgOperacionTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgOperacionesTiposRadicados::className(), 'targetAttribute' => ['idCgOperacionTipoRadicado' => 'idCgOperacionTipoRadicado']],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiTipoOperacion' => 'Id Radi Tipo Operacion',
            'idCgOperacionTipoRadicado' => 'Id Cg Operacion Tipo Radicado',
            'idCgTipoRadicado' => 'Id Cg Tipo Radicado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgOperacionTipoRadicado()
    {
        return $this->hasOne(CgOperacionesTiposRadicados::className(), ['idCgOperacionTipoRadicado' => 'idCgOperacionTipoRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTipoRadicado()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}
