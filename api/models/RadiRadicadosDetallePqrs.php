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
 * This is the model class for table "radiRadicadosDetallePqrs".
 *
 * @property int $idRadiRadicadoDetallePqrs Identificador de la tabla
 * @property int $idRadiRadicado Este campo referencia el id del radicado que se esta creando y asociando a una persona (cliente o usuario)
 * @property int $idCgClasificacionPqrs Este campo referencia el id del cliente o del usuarios segun sea el caso cuando se radica.
 *
 * @property RadiRadicados $idRadiRadicado0
 * @property CgClasificacionPqrs $idCgClasificacionPqrs0
 */
class RadiRadicadosDetallePqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicadosDetallePqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idCgClasificacionPqrs'], 'integer'],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idCgClasificacionPqrs'], 'exist', 'skipOnError' => true, 'targetClass' => CgClasificacionPqrs::className(), 'targetAttribute' => ['idCgClasificacionPqrs' => 'idCgClasificacionPqrs']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicadoDetallePqrs' => 'Id Radi Remitente',
            'idRadiRadicado' => 'Id Radi Radicado',
            'idCgClasificacionPqrs' => 'Id Clasificación',
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

    /**
     * Gets query for [[IdCgClasificacionPqrs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgClasificacionPqrs0()
    {
        return $this->hasOne(CgClasificacionPqrs::className(), ['idCgClasificacionPqrs' => 'idCgClasificacionPqrs']);
    }
}
