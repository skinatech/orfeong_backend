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
 * This is the model class for table "csInicial".
 *
 * @property int $idCsInicial
 * @property string $llaveCsInicial
 * @property string $valorCsInicial
 * @property string $creacionCsInicial
 * @property int $estadoCsInicial
 */
class CsInicial extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'csInicial';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['llaveCsInicial', 'valorCsInicial'], 'required'],
            [['creacionCsInicial'], 'safe'],
            [['estadoCsInicial'], 'integer'],
            [['llaveCsInicial'], 'string', 'max' => 50],
            [['valorCsInicial'], 'string', 'max' => 100],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCsInicial' => 'Id Cs Inicial',
            'llaveCsInicial' => 'Llave Cs Inicial',
            'valorCsInicial' => 'Valor Cs Inicial',
            'creacionCsInicial' => 'Creacion Cs Inicial',
            'estadoCsInicial' => 'Estado Cs Inicial',
        ];
    }
}
