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
 * This is the model class for table "cgEnvioServicios".
 *
 * @property int $idCgEnvioServicio Numero unico de la tabla
 * @property string $nombreCgEnvioServicio Nombre del envió del servicio
 * @property int $estadoCgEnvioServicio Estado de servicios 0 inactivo 10 activo
 * @property string $creacionCgEnvioServicio Creación del servicio
 *
 * @property CgProveedoresServicios[] $cgProveedoresServicios
 */
class CgEnvioServicios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEnvioServicios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['estadoCgEnvioServicio'], 'integer'],
            [['creacionCgEnvioServicio'], 'safe'],
            [['nombreCgEnvioServicio'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEnvioServicio' => 'Id servicio',
            'nombreCgEnvioServicio' => 'Nombre del envió del servicio',
            'estadoCgEnvioServicio' => 'Estado servicio',
            'creacionCgEnvioServicio' => 'Fecha creación del servicio',
        ];
    }

    /**
     * Gets query for [[CgProveedoresServicios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgProveedoresServicios()
    {
        return $this->hasMany(CgProveedoresServicios::className(), ['idCgEnvioServicios' => 'idCgEnvioServicio']);
    }
}
