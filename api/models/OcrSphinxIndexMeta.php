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
 * This is the model class for table "ocrSphinxIndexMeta".
 *
 * @property int $idOcrSphinxIndexMeta
 * @property string $nombreOcrSphinxIndexMeta
 * @property int $idMaxOcrSphinxIndexMeta
 * @property string $fechaActualizaOcrSphinxIndexMeta
 */
class OcrSphinxIndexMeta extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ocrSphinxIndexMeta';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreOcrSphinxIndexMeta', 'idMaxOcrSphinxIndexMeta', 'fechaActualizaOcrSphinxIndexMeta'], 'required'],
            [['nombreOcrSphinxIndexMeta'], 'string'],
            [['idMaxOcrSphinxIndexMeta'], 'integer'],
            [['fechaActualizaOcrSphinxIndexMeta'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idOcrSphinxIndexMeta' => 'Id Ocr Sphinx Index Meta',
            'nombreOcrSphinxIndexMeta' => 'Nombre Ocr Sphinx Index Meta',
            'idMaxOcrSphinxIndexMeta' => 'Id Max Ocr Sphinx Index Meta',
            'fechaActualizaOcrSphinxIndexMeta' => 'Fecha Actualiza Ocr Sphinx Index Meta',
        ];
    }
}
