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

namespace api\models\views;

use Yii;

/**
 * This is the model class for table "viewRadiResponseTime".
 *
 * @property int $idTrdDepeUserTramitador Id dependencia tramitadora
 * @property string $codigoGdTrdDependencia Código dependencia tramitadora
 * @property string $nombreGdTrdDependencia Nombre dependencia tramitadora
 * @property int $idRadiRadicado Id Radicado
 * @property string $creacionRadiRadicado Fecha creación radicado
 * @property string|null $fechaRespuesta Fecha respuesta (firma)
 */
class ViewRadiResponseTime extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'viewRadiResponseTime';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idTrdDepeUserTramitador', 'codigoGdTrdDependencia', 'nombreGdTrdDependencia', 'idRadiRadicado', 'creacionRadiRadicado'], 'required'],
            [['idTrdDepeUserTramitador', 'idRadiRadicado'], 'integer'],
            [['codigoGdTrdDependencia'], 'string', 'max' => 6],
            [['nombreGdTrdDependencia'], 'string', 'max' => 255],
            [['creacionRadiRadicado', 'fechaRespuesta'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTrdDepeUserTramitador' => 'Id Trd Depe User Tramitador',
            'codigoGdTrdDependencia' => 'Codigo Gd Trd Dependencia',
            'nombreGdTrdDependencia' => 'Nombre Gd Trd Dependencia',
            'idRadiRadicado' => 'Id Radi Radicado',
            'creacionRadiRadicado' => 'Creacion Radi Radicado',
            'fechaRespuesta' => 'Fecha Respuesta',
        ];
    }
}

