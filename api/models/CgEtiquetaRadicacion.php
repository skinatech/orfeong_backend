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
 * This is the model class for table "cgEtiquetaRadicacion".
 *
 * @property int $idCgEtiquetaRadicacion Id de la configuración de etiqueta radicación
 * @property string $etiquetaCgEtiquetaRadicacion Nombre de etiqueta
 * @property int $estadoCgEtiquetaRadicacion Estado de etiqueta
 * @property string $creacionCgEtiquetaRadicacion Fecha creación de la etiqueta
 * @property string $descripcionCgEtiquetaRadicacion Descripción de la variable etiqueta
 */
class CgEtiquetaRadicacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEtiquetaRadicacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['etiquetaCgEtiquetaRadicacion','descripcionCgEtiquetaRadicacion'], 'required'],
            [['estadoCgEtiquetaRadicacion'], 'integer'],
            [['creacionCgEtiquetaRadicacion'], 'safe'],
            [['etiquetaCgEtiquetaRadicacion'], 'string', 'max' => 80],
            [['descripcionCgEtiquetaRadicacion'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEtiquetaRadicacion' => 'Id de la configuración de etiqueta radicación',
            'etiquetaCgEtiquetaRadicacion' => 'Nombre de etiqueta',
            'estadoCgEtiquetaRadicacion' => 'Estado',
            'creacionCgEtiquetaRadicacion' => 'Fecha creación',
            'descripcionCgEtiquetaRadicacion' => 'Descripción de la variable etiqueta',
        ];
    }
}
