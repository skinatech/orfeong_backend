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
 * This is the model class for table "cgProveedores".
 *
 * @property int $idCgProveedor Numero unico de la tabla
 * @property string $nombreCgProveedor Nombre de la empresa que hace el envio de correspondencia
 * @property int $estadoCgProveedor Estado del proveedor  10 activo 0 Inactivo
 * @property string $creacionCgProveedor Creación del proveedor
 *
 * @property CgProveedoresRegional[] $cgProveedoresRegionals
 * @property CgProveedoresServicios[] $cgProveedoresServicios
 */
class CgProveedores extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgProveedores';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['estadoCgProveedor'], 'integer'],
            [['creacionCgProveedor'], 'safe'],
            [['nombreCgProveedor'], 'string', 'max' => 80],
            [['nombreCgProveedor'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgProveedor' => 'Id proveedor',
            'nombreCgProveedor' => 'Nombre de la empresa que hace el envío de correspondencia',
            'estadoCgProveedor' => 'Estado proveedor',
            'creacionCgProveedor' => 'Fecha creación del proveedor',
        ];
    }

    /**
     * Gets query for [[CgProveedoresRegionals]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgProveedoresRegionals()
    {
        return $this->hasMany(CgProveedoresRegional::className(), ['idCgProveedor' => 'idCgProveedor']);
    }

    /**
     * Gets query for [[CgProveedoresServicios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgProveedoresServicios()
    {
        return $this->hasMany(CgProveedoresServicios::className(), ['idCgProveedor' => 'idCgProveedor']);
    }
}
