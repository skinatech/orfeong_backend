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
 * This is the model class for table "cgOperacionesTiposRadicados".
 *
 * @property int $idCgOperacionTipoRadicado
 * @property string $nombreCgOperacionTipoRadicado Nombre de la operación que se puede realizar con el radicado
 * @property int $estadoCgOperacionTipoRadicado estado de la operación asociado al radicado
 * @property string $creacionCgOperacionTipoRadicado fecha de creación de la operación del radicado.
 *
 * @property RadiTiposOperaciones[] $radiTiposOperaciones
 */
class CgOperacionesTiposRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgOperacionesTiposRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgOperacionTipoRadicado'], 'required'],
            [['estadoCgOperacionTipoRadicado'], 'integer'],
            [['creacionCgOperacionTipoRadicado'], 'safe'],
            [['nombreCgOperacionTipoRadicado'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgOperacionTipoRadicado' => 'Id Cg Operacion Tipo Radicado',
            'nombreCgOperacionTipoRadicado' => 'Nombre Cg Operacion Tipo Radicado',
            'estadoCgOperacionTipoRadicado' => 'Estado Cg Operacion Tipo Radicado',
            'creacionCgOperacionTipoRadicado' => 'Creacion Cg Operacion Tipo Radicado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiTiposOperaciones()
    {
        return $this->hasMany(RadiTiposOperaciones::className(), ['idCgOperacionTipoRadicado' => 'idCgOperacionTipoRadicado']);
    }
}
