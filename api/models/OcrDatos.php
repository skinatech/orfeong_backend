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
 * This is the model class for table "ocrDatos".
 *
 * @property int $idOcrDatos
 * @property int $idDocumentoOcrDatos id registro del documento segun la tabla
 * @property int $tablaAfectadaOcrDatos 1:radiDocumentos, 2:radiDocumentosPrincipales, 3:gdExpedienteDocumentos
 * @property string $textoExtraidoOcrDatos contenido del documento
 * @property string $creacionOcrDatos creación registro
 * @property int $estadoOcrDatos estado estandar
 */
class OcrDatos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ocrDatos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idDocumentoOcrDatos', 'tablaAfectadaOcrDatos', 'textoExtraidoOcrDatos'], 'required'],
            [['idDocumentoOcrDatos', 'tablaAfectadaOcrDatos', 'estadoOcrDatos'], 'integer'],
            [['textoExtraidoOcrDatos'], 'string'],
            [['creacionOcrDatos'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idOcrDatos' => 'Id Ocr Datos',
            'idDocumentoOcrDatos' => 'Id Documento Ocr Datos',
            'tablaAfectadaOcrDatos' => 'Tabla Afectada Ocr Datos',
            'textoExtraidoOcrDatos' => 'Texto Extraido Ocr Datos',
            'creacionOcrDatos' => 'Creacion Ocr Datos',
            'estadoOcrDatos' => 'Estado Ocr Datos',
        ];
    }

}
