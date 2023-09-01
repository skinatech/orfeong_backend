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
 * This is the model class for table "cgProveedoresRegional".
 *
 * @property int $idCgProveedorRegional Numero unico de la tabla
 * @property int $idCgRegional Id de la Regional
 * @property int $idCgProveedor Id del Proveedor
 *
 * @property CgProveedores $idCgProveedor0
 * @property CgRegionales $idCgRegional0
 */
class CgProveedoresRegional extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgProveedoresRegional';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgRegional', 'idCgProveedor'], 'required'],
            [['idCgRegional', 'idCgProveedor'], 'integer'],
            [['idCgProveedor'], 'exist', 'skipOnError' => true, 'targetClass' => CgProveedores::className(), 'targetAttribute' => ['idCgProveedor' => 'idCgProveedor']],
            [['idCgRegional'], 'exist', 'skipOnError' => true, 'targetClass' => CgRegionales::className(), 'targetAttribute' => ['idCgRegional' => 'idCgRegional']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgProveedorRegional' => 'Id relación proveedor-regional',
            'idCgRegional' => 'Id de la regional',
            'idCgProveedor' => 'Id proveedor',
        ];
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

    /**
     * Gets query for [[IdCgRegional0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgRegional0()
    {
        return $this->hasOne(CgRegionales::className(), ['idCgRegional' => 'idCgRegional']);
    }
}
