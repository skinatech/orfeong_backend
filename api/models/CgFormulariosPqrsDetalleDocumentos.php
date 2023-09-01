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
 * This is the model class for table "cgFormulariosPqrsDetalleDocumentos".
 *
 * @property int $idCgFormulariosPqrsDetalleDocumentos
 * @property int $idCgFormulariosPqrsDetalle
 * @property string $combiCgFormulariosPqrsDetalleDocumentos
 * @property string $nameFileCgFormulariosPqrsDetalleDocumentos
 * @property string $nombreCgFormulariosPqrsDetalleDocumentos
 * @property string $descripcionCgFormulariosPqrsDetalleDocumentos
 * @property int $requeridoCgFormulariosPqrsDetalleDocumentos
 * @property int $estadoCgFormulariosPqrsDetalleDocumentos
 * @property string $creacionCgFormulariosPqrsDetalleDocumentos
 *
 * @property CgFormulariosPqrsDetalle $idCgFormulariosPqrsDetalle0
 */
class CgFormulariosPqrsDetalleDocumentos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgFormulariosPqrsDetalleDocumentos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgFormulariosPqrsDetalle', 'combiCgFormulariosPqrsDetalleDocumentos', 'nameFileCgFormulariosPqrsDetalleDocumentos', 'nombreCgFormulariosPqrsDetalleDocumentos', 'descripcionCgFormulariosPqrsDetalleDocumentos'], 'required'],
            [['idCgFormulariosPqrsDetalle', 'requeridoCgFormulariosPqrsDetalleDocumentos', 'estadoCgFormulariosPqrsDetalleDocumentos'], 'integer'],
            [['descripcionCgFormulariosPqrsDetalleDocumentos'], 'string'],
            [['creacionCgFormulariosPqrsDetalleDocumentos'], 'safe'],
            [['combiCgFormulariosPqrsDetalleDocumentos', 'nombreCgFormulariosPqrsDetalleDocumentos'], 'string', 'max' => 500],
            [['nameFileCgFormulariosPqrsDetalleDocumentos'], 'string', 'max' => 255],
            [['idCgFormulariosPqrsDetalle'], 'exist', 'skipOnError' => true, 'targetClass' => CgFormulariosPqrsDetalle::className(), 'targetAttribute' => ['idCgFormulariosPqrsDetalle' => 'idCgFormulariosPqrsDetalle']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgFormulariosPqrsDetalleDocumentos' => 'Id Cg Formularios Pqrs Detalle Documentos',
            'idCgFormulariosPqrsDetalle' => 'Id Cg Formularios Pqrs Detalle',
            'combiCgFormulariosPqrsDetalleDocumentos' => 'Combi Cg Formularios Pqrs Detalle Documentos',
            'nameFileCgFormulariosPqrsDetalleDocumentos' => 'Name File Cg Formularios Pqrs Detalle Documentos',
            'nombreCgFormulariosPqrsDetalleDocumentos' => 'Nombre Cg Formularios Pqrs Detalle Documentos',
            'descripcionCgFormulariosPqrsDetalleDocumentos' => 'Descripcion Cg Formularios Pqrs Detalle Documentos',
            'requeridoCgFormulariosPqrsDetalleDocumentos' => 'Requerido Cg Formularios Pqrs Detalle Documentos',
            'estadoCgFormulariosPqrsDetalleDocumentos' => 'Estado Cg Formularios Pqrs Detalle Documentos',
            'creacionCgFormulariosPqrsDetalleDocumentos' => 'Creacion Cg Formularios Pqrs Detalle Documentos',
        ];
    }

    /**
     * Gets query for [[IdCgFormulariosPqrsDetalle0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgFormulariosPqrsDetalle0()
    {
        return $this->hasOne(CgFormulariosPqrsDetalle::className(), ['idCgFormulariosPqrsDetalle' => 'idCgFormulariosPqrsDetalle']);
    }
}
