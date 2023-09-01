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
 * This is the model class for table "cgGeneralBasesDatos".
 *
 * @property int $idCgGeneralBasesDatos
 * @property string $nombreCgGeneralBasesDatos
 * @property string $dnsCgGeneralBasesDatos
 * @property string $hostCgGeneralBasesDatos
 * @property string $portCgGeneralBasesDatos
 * @property string $dbnameCgGeneralBasesDatos
 * @property string $usernameCgGeneralBasesDatos
 * @property string $passCgGeneralBasesDatos
 * @property string $creacionCgGeneralBasesDatos
 * @property int $estadoCgGeneralBasesDatos
 */
class CgGeneralBasesDatos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgGeneralBasesDatos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgGeneralBasesDatos', 'dnsCgGeneralBasesDatos', 'hostCgGeneralBasesDatos', 'portCgGeneralBasesDatos', 'dbnameCgGeneralBasesDatos', 'usernameCgGeneralBasesDatos', 'passCgGeneralBasesDatos'], 'required'],
            [['creacionCgGeneralBasesDatos'], 'safe'],
            [['estadoCgGeneralBasesDatos'], 'integer'],
            [['nombreCgGeneralBasesDatos', 'usernameCgGeneralBasesDatos', 'passCgGeneralBasesDatos'], 'string', 'max' => 80],
            [['dnsCgGeneralBasesDatos'], 'string', 'max' => 10],
            [['hostCgGeneralBasesDatos', 'portCgGeneralBasesDatos', 'dbnameCgGeneralBasesDatos'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgGeneralBasesDatos' => 'Id Cg General Bases Datos',
            'nombreCgGeneralBasesDatos' => 'Nombre Cg General Bases Datos',
            'dnsCgGeneralBasesDatos' => 'Dns Cg General Bases Datos',
            'hostCgGeneralBasesDatos' => 'Host Cg General Bases Datos',
            'portCgGeneralBasesDatos' => 'Port Cg General Bases Datos',
            'dbnameCgGeneralBasesDatos' => 'Dbname Cg General Bases Datos',
            'usernameCgGeneralBasesDatos' => 'Username Cg General Bases Datos',
            'passCgGeneralBasesDatos' => 'Pass Cg General Bases Datos',
            'creacionCgGeneralBasesDatos' => 'Creacion Cg General Bases Datos',
            'estadoCgGeneralBasesDatos' => 'Estado Cg General Bases Datos',
        ];
    }
}
