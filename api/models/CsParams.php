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
 * This is the model class for table "csParams".
 *
 * @property int $idCsParams
 * @property string $llaveCsParams
 * @property string $valorCsParams
 * @property string $creacionCsParams
 * @property int $estadoCsParams
 */
class CsParams extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'csParams';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['llaveCsParams', 'valorCsParams'], 'required'],
            [['creacionCsParams'], 'safe'],
            [['estadoCsParams'], 'integer'],
            [['llaveCsParams'], 'string', 'max' => 50],
            [['valorCsParams'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCsParams' => 'Id Cs Params',
            'llaveCsParams' => 'Llave Cs Params',
            'valorCsParams' => 'Valor Cs Params',
            'creacionCsParams' => 'Creacion Cs Params',
            'estadoCsParams' => 'Estado Cs Params',
        ];
    }
}
