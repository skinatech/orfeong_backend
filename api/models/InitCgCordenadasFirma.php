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
 * This is the model class for table "initCgCordenadasFirma".
 *
 * @property int $idInitCgCordenadaFirma
 * @property string $nombreInitCgCordenadaFirma
 * @property string $cordenadasInitCgCordenadaFirma
 * @property int $estadoInitCgCordenadaFirma
 * @property string $creacionInitCgCordenadaFirma
 */
class InitCgCordenadasFirma extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'initCgCordenadasFirma';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreInitCgCordenadaFirma', 'cordenadasInitCgCordenadaFirma'], 'required'],
            [['estadoInitCgCordenadaFirma'], 'integer'],
            [['creacionInitCgCordenadaFirma'], 'safe'],
            [['nombreInitCgCordenadaFirma'], 'string', 'max' => 250],
            [['cordenadasInitCgCordenadaFirma'], 'string', 'max' => 80],
            [['nombreInitCgCordenadaFirma'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idInitCgCordenadaFirma' => 'Id grupo usuarios',
            'nombreInitCgCordenadaFirma' => 'Nombre de la firma',
            'cordenadasInitCgCordenadaFirma' => 'Coordenadas de la firma x, y',
            'estadoInitCgCordenadaFirma' => 'Estado',
            'creacionInitCgCordenadaFirma' => 'Fecha creación',
        ];
    }
}
