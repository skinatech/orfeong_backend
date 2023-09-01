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
 * This is the model class for table "cgTiposRadicados".
 *
 * @property int $idCgTipoRadicado
 * @property string $codigoCgTipoRadicado Código que identifica el tipo de radicado que se esta realizando, es varchar porque pueden ser letras, esto es sgun el cliente lo defina.
 * @property string $nombreCgTipoRadicado Nombre del tipo de radicado que se esta generando ej: Entrada, Salida, Pqrs, Resoluciones, Memorandos, esto es segun el cliente
 * @property int $estadoCgTipoRadicado Estado del tipo de radicado 10: activo 0: inactivo
 * @property string $creacionCgTipoRadicado Fecha de creación del tipo de radicado.
 * @property int $unicoRadiCgTipoRadicado Único radicado = 10, múltiple radicado = 0
 *
 * @property RadiRadicados[] $radiRadicados
 * @property RadiTiposOperaciones[] $radiTiposOperaciones
 * @property RolesTipoRadicado[] $rolesTipoRadicados
 */
class CgTiposRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTiposRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigoCgTipoRadicado', 'nombreCgTipoRadicado'], 'required'],
            [['estadoCgTipoRadicado','unicoRadiCgTipoRadicado'], 'integer'],
            [['creacionCgTipoRadicado'], 'safe'],
            [['codigoCgTipoRadicado'], 'string', 'max' => 8],
            [['nombreCgTipoRadicado'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTipoRadicado' => 'Id Tipo Radicado',
            'codigoCgTipoRadicado' => 'Código Tipo Radicado',
            'nombreCgTipoRadicado' => 'Nombre Tipo Radicado',
            'estadoCgTipoRadicado' => 'Estado',
            'creacionCgTipoRadicado' => 'Fecha creación',
            'unicoRadiCgTipoRadicado' => 'Único radicado con múltiples remitentes',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicados()
    {
        return $this->hasMany(RadiRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiTiposOperaciones()
    {
        return $this->hasMany(RadiTiposOperaciones::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRolesTipoRadicados()
    {
        return $this->hasMany(RolesTipoRadicado::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    public function getCgTiposRadicadosTransacciones()
    {
        return $this->hasMany(CgTiposRadicadosTransacciones::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    /**
     * Gets query for [[CgTiposRadicadosResoluciones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgTiposRadicadosResoluciones()
    {
        return $this->hasOne(CgTiposRadicadosResoluciones::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}
