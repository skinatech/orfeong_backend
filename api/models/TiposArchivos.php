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
 * This is the model class for table "tiposArchivos".
 *
 * @property int $idTipoArchivo Campo primario de la tabla
 * @property string $tipoArchivo Nombre del tipo de archivo
 * @property int $estadoTipoArchivo status del registro
 * @property string $creacionTipoArchivo fecha de creación del registro
 */
class TiposArchivos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tiposArchivos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tipoArchivo'], 'required'],
            [['estadoTipoArchivo'], 'integer'],
            [['creacionTipoArchivo'], 'safe'],
            [['tipoArchivo'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTipoArchivo' => 'Id Tipo Archivo',
            'tipoArchivo' => 'Tipo Archivo',
            'estadoTipoArchivo' => 'Estado Tipo Archivo',
            'creacionTipoArchivo' => 'Creacion Tipo Archivo',
        ];
    }
}
