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
 * This is the model class for table "cgTiposRadicadosResoluciones".
 *
 * @property int $idCgTiposRadicadosResoluciones
 * @property int $idCgTipoRadicado
 * @property string $numeracionCgTiposRadicadosResoluciones
 * @property string $creacionCgTiposRadicadosResoluciones
 * @property int $estadoCgTiposRadicadosResoluciones
 *
 * @property CgTiposRadicados $idCgTipoRadicado0
 */
class CgTiposRadicadosResoluciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTiposRadicadosResoluciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgTipoRadicado', 'numeracionCgTiposRadicadosResoluciones'], 'required'],
            [['idCgTipoRadicado', 'numeracionCgTiposRadicadosResoluciones', 'estadoCgTiposRadicadosResoluciones'], 'integer'],
            [['creacionCgTiposRadicadosResoluciones'], 'safe'],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTiposRadicadosResoluciones' => 'Id Cg Tipos Radicados Resoluciones',
            'idCgTipoRadicado' => 'Id Cg Tipo Radicado',
            'numeracionCgTiposRadicadosResoluciones' => 'Numeracion Cg Tipos Radicados Resoluciones',
            'creacionCgTiposRadicadosResoluciones' => 'Creacion Cg Tipos Radicados Resoluciones',
            'estadoCgTiposRadicadosResoluciones' => 'Estado Cg Tipos Radicados Resoluciones',
        ];
    }

    /**
     * Gets query for [[IdCgTipoRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgTipoRadicado0()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}
