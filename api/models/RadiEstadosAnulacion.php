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
 * This is the model class for table "radiEstadosAnulacion".
 *
 * @property int $idRadiEstadoAnulacion Numero unico de la tabla
 * @property string $nombreRadiEstadoAnulacion Nombre del estado de radicado anulado
 * @property int $estadoRadiEstadosAnulacion Estado 0 Inactivo 10 Activo 
 * @property string $creacionRadiEstadosAnulacion Fecha de creación del estado  
 *
 * @property RadiRadicadoAnulado[] $radiRadicadoAnulados
 */
class RadiEstadosAnulacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiEstadosAnulacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreRadiEstadoAnulacion'], 'required'],
            [['estadoRadiEstadosAnulacion'], 'integer'], 
            [['creacionRadiEstadosAnulacion'], 'safe'], 
            [['nombreRadiEstadoAnulacion'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiEstadoAnulacion' => 'Numero unico de la tabla',
            'nombreRadiEstadoAnulacion' => 'Nombre del estado de radicado anulado',
            'estadoRadiEstadosAnulacion' => 'Estado 0 Inactivo 10 Activo', 
            'creacionRadiEstadosAnulacion' => 'Fecha de creación del estado ', 
        ];
    }

    /**
     * Gets query for [[RadiRadicadoAnulados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicadoAnulados()
    {
        return $this->hasMany(RadiRadicadoAnulado::className(), ['idEstado' => 'idRadiEstadoAnulacion']);
    }
}
