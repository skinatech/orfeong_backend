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

namespace frontend\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for
 *
 * @property string $anexo
 */
class Anexos extends Model
{ 

    public $anexo, $agregar ,$publico, $observacion;

    /**
     * {@inheritdoc}
     */
    public function rules() 
    {
        return [
            [['agregar','publico','observacion'], 'required','message' => 'Este campo no puede estar vacío.'],
            [['anexo'], 'file', 'extensions' => 'pdf, jpg, png, docx, xlsx, doc, xls, mp3, mp4, tif, txt, odt, avi'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'anexo' => '',
            'observacion' => '',
            'agregar' => '',
            'publico' => '',

        ];
    }
}
