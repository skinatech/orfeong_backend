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
 * This is the model class for table "ocrSphinxIndexRemove".
 *
 * @property int $idOcrSphinxIndexRemove
 * @property int $indiceOcrSphinxIndexRemove
 * @property int $estadoOcrSphinxIndexRemove estado estandar
 * @property string $creacionOcrSphinxIndexRemove fecha de creacion
 * @property int $ejecucionOcrSphinxIndexRemove
 * @property int $identiOcrSphinxIndexRemove
 */
class OcrSphinxIndexRemove extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ocrSphinxIndexRemove';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['indiceOcrSphinxIndexRemove', 'ejecucionOcrSphinxIndexRemove', 'identiOcrSphinxIndexRemove'], 'required'],
            [['indiceOcrSphinxIndexRemove', 'estadoOcrSphinxIndexRemove', 'ejecucionOcrSphinxIndexRemove', 'identiOcrSphinxIndexRemove'], 'integer'],
            [['creacionOcrSphinxIndexRemove'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idOcrSphinxIndexRemove' => 'Id Ocr Sphinx Index Remove',
            'indiceOcrSphinxIndexRemove' => 'Indice Ocr Sphinx Index Remove',
            'estadoOcrSphinxIndexRemove' => 'Estado Ocr Sphinx Index Remove',
            'creacionOcrSphinxIndexRemove' => 'Creacion Ocr Sphinx Index Remove',
            'ejecucionOcrSphinxIndexRemove' => 'Ejecucion Ocr Sphinx Index Remove',
            'identiOcrSphinxIndexRemove' => 'Identi Ocr Sphinx Index Remove',
        ];
    }
}
