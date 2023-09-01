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
 * This is the model class for table "cgProveedoresServicios".
 *
 * @property int $idCgProveedoresServicios Numero unico de la tabla
 * @property int $idCgProveedor Id del Proveedor
 * @property int $idCgEnvioServicios Id del envío servicio
 *
 * @property CgEnvioServicios $idCgEnvioServicios0
 * @property CgProveedores $idCgProveedor0
 */
class CgProveedoresServicios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgProveedoresServicios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgProveedor', 'idCgEnvioServicios'], 'required'],
            [['idCgProveedor', 'idCgEnvioServicios'], 'integer'],
            [['idCgEnvioServicios'], 'exist', 'skipOnError' => true, 'targetClass' => CgEnvioServicios::className(), 'targetAttribute' => ['idCgEnvioServicios' => 'idCgEnvioServicio']],
            [['idCgProveedor'], 'exist', 'skipOnError' => true, 'targetClass' => CgProveedores::className(), 'targetAttribute' => ['idCgProveedor' => 'idCgProveedor']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgProveedoresServicios' => 'Id relación proveedor-servicio',
            'idCgProveedor' => 'Id proveedor',
            'idCgEnvioServicios' => 'Id del envío servicio',
        ];
    }

    /**
     * Gets query for [[IdCgEnvioServicios0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgEnvioServicios0()
    {
        return $this->hasOne(CgEnvioServicios::className(), ['idCgEnvioServicio' => 'idCgEnvioServicios']);
    }

    /**
     * Gets query for [[IdCgProveedor0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgProveedor0()
    {
        return $this->hasOne(CgProveedores::className(), ['idCgProveedor' => 'idCgProveedor']);
    }
}
