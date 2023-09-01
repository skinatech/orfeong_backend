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
 * This is the model class for table "viewUnsignedDocuments".
 *
 * @property int $idRadiRadicado
 * @property string $numeroRadiRadicado numero de radicado asignado por el sistema para el tramite de ese documento
 * @property string $asuntoRadiRadicado asunto asignado al radicado que se esta ingresando al sistema
 * @property int $idTrdDepeUserTramitador Identificador de la tabla de dependencias haciendo referencia a la dependencia a la que pertenece el usuario en cargado de dar tramite al radicado
 */
class ViewUnsignedDocuments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'viewUnsignedDocuments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idTrdDepeUserTramitador'], 'integer'],
            [['numeroRadiRadicado', 'asuntoRadiRadicado', 'idTrdDepeUserTramitador'], 'required'],
            [['numeroRadiRadicado'], 'string', 'max' => 80],
            [['asuntoRadiRadicado'], 'string', 'max' => 4000],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicado' => 'Id Radi Radicado',
            'numeroRadiRadicado' => 'Numero Radi Radicado',
            'asuntoRadiRadicado' => 'Asunto Radi Radicado',
            'idTrdDepeUserTramitador' => 'Id Trd Depe User Tramitador',
            'estadoRadiRadicado' => 'Estado del Radicado',
        ];
    }
}
