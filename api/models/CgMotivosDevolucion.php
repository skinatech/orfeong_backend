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
 * This is the model class for table "cgMotivosDevolucion".
 *
 * @property int $idCgMotivoDevolucion Numero unico de la tabla
 * @property string $nombreCgMotivoDevolucion Nombre de los motivos de devolución
 * @property int $estadoCgMotivoDevolucion Estado de motivos de devolución 0 inactivo 10 activo
 * @property string $creacionCgMotivoDevolucion Creación del motivo de devolución
 */
class CgMotivosDevolucion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgMotivosDevolucion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgMotivoDevolucion'], 'required'],
            [['estadoCgMotivoDevolucion'], 'integer'],
            [['creacionCgMotivoDevolucion'], 'safe'],
            [['nombreCgMotivoDevolucion'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgMotivoDevolucion' => 'Id motivo de devolución',
            'nombreCgMotivoDevolucion' => 'Nombre de los motivos de devolución',
            'estadoCgMotivoDevolucion' => 'Estado de motivos de devolución (0 inactivo 10 activo)',
            'creacionCgMotivoDevolucion' => 'Fecha de creación del motivo de devolución',
        ];
    }
}
