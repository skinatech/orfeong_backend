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
 * This is the model class for table "cgTransaccionesRadicados".
 *
 * @property int $idCgTransaccionRadicado
 * @property string $rutaAccionCgTransaccionRadicado Ruta de la acción que se ejecuta en backend
 * @property string $descripcionCgTransaccionRadicado Descripcion de la operación que se puede realizar con el radicado
 * @property string $titleCgTransaccionRadicado Titulo que se muestra en el boton flotante
 * @property string $iconCgTransaccionRadicado icono del boton flotante
 * @property string $actionCgTransaccionRadicado action del boton flotante
 * @property int $estadoCgTransaccionRadicado estado de la operación asociado al radicado
 * @property string $creacionCgTransaccionRadicado fecha de creación de la operación del radicado
 *
 * @property CgTiposRadicadosTransacciones[] $cgTiposRadicadosTransacciones
 * @property RadiTiposOperaciones[] $radiTiposOperaciones
 */
class CgTransaccionesRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTransaccionesRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['rutaAccionCgTransaccionRadicado', 'descripcionCgTransaccionRadicado', 'titleCgTransaccionRadicado', 'iconCgTransaccionRadicado', 'actionCgTransaccionRadicado'], 'required'],
            [['estadoCgTransaccionRadicado','mostrarBotonCgTransaccionRadicado'], 'integer'],
            [['creacionCgTransaccionRadicado'], 'safe'],
            [['rutaAccionCgTransaccionRadicado'], 'string', 'max' => 80],
            [['descripcionCgTransaccionRadicado'], 'string', 'max' => 45],
            [['titleCgTransaccionRadicado', 'iconCgTransaccionRadicado', 'actionCgTransaccionRadicado'], 'string', 'max' => 30],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTransaccionRadicado' => 'Id Cg Transaccion Radicado',
            'rutaAccionCgTransaccionRadicado' => 'Ruta Accion Cg Transaccion Radicado',
            'descripcionCgTransaccionRadicado' => 'Descripcion Cg Transaccion Radicado',
            'titleCgTransaccionRadicado' => 'Title Cg Transaccion Radicado',
            'iconCgTransaccionRadicado' => 'Icon Cg Transaccion Radicado',
            'actionCgTransaccionRadicado' => 'Action Cg Transaccion Radicado',
            'estadoCgTransaccionRadicado' => 'Estado Cg Transaccion Radicado',
            'creacionCgTransaccionRadicado' => 'Creacion Cg Transaccion Radicado',
            'mostrarBotonCgTransaccionRadicado' => 'Mostrar botón en la aplicación',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgTiposRadicadosTransacciones()
    {
        return $this->hasMany(CgTiposRadicadosTransacciones::className(), ['idCgTransaccionRadicado' => 'idCgTransaccionRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiTiposOperaciones()
    {
        return $this->hasMany(RadiTiposOperaciones::className(), ['idCgOperacionTipoRadicado' => 'idCgTransaccionRadicado']);
    }
}
