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
 * This is the model class for table "cgReportes".
 *
 * @property int $idCgReporte Identificador único en el sistema de la tabla cgReportes
 * @property string $nombreCgReporte Nombre del reporte
 * @property string|null $descripcionCgReporte Descripción del reporte
 * @property string $actionCgReporte Action que existe para cada reporte
 * @property string $creacionCgReporte Fecha de creación del registro
 * @property int $estadoCgReporte 10: Activo; 0: Inactivo
 */
class CgReportes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgReportes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgReporte', 'actionCgReporte', 'creacionCgReporte', 'estadoCgReporte'], 'required'],
            [['creacionCgReporte'], 'safe'],
            [['estadoCgReporte'], 'integer'],
            [['nombreCgReporte', 'actionCgReporte'], 'string', 'max' => 255],
            [['descripcionCgReporte'], 'string', 'max' => 500],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgReporte' => 'Id reporte',
            'nombreCgReporte' => 'Nombre reporte',
            'descripcionCgReporte' => 'Descripción Reporte',
            'actionCgReporte' => 'Acción del reporte',
            'creacionCgReporte' => 'Fecha creación reporte',
            'estadoCgReporte' => 'Estado',
        ];
    }
}
