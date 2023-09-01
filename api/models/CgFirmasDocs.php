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
 * This is the model class for table "cgFirmasDocs".
 *
 * @property int $idCgFirmaDoc Número unico de la tabla
 * @property string $nombreCgFirmaDoc Nombre firma
 * @property int $estadoCgFirmaDoc Estado firma
 * @property string $creacionCgFirmaDoc Fecha creación firma
 */
class CgFirmasDocs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgFirmasDocs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgFirmaDoc'], 'required'],
            [['estadoCgFirmaDoc'], 'integer'],
            [['creacionCgFirmaDoc'], 'safe'],
            [['nombreCgFirmaDoc'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgFirmaDoc' => 'Id firma',
            'nombreCgFirmaDoc' => 'Nombre firma',
            'estadoCgFirmaDoc' => 'Estado firma',
            'creacionCgFirmaDoc' => 'Creacion firma',
        ];
    }
}
