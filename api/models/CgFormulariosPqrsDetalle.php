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
 * This is the model class for table "cgFormulariosPqrsDetalle".
 *
 * @property int $idCgFormulariosPqrsDetalle
 * @property int $idCgFormulariosPqrs
 * @property string $nombreCgFormulariosPqrsDetalle
 * @property string $descripcionCgFormulariosPqrsDetalle
 * @property string $adjuntarDocsCgFormulariosPqrsDetalle
 * @property string $terminosCgFormulariosPqrsDetalle
 * @property string $datosSelectorPrestacionCgFormulariosPqrsDetalle
 * @property string $datosSelectorBeneficiarioCgFormulariosPqrsDetalle
 * @property string $activarBeneficiarioCgFormulariosPqrsDetalle
 * @property int $idTipoDocumentalCgFormulariosPqrsDetalle
 * @property int $idSerieCgFormulariosPqrsDetalle
 * @property int $idSubserieCgFormulariosPqrsDetalle
 * @property int $estadoCgFormulariosPqrsDetalle
 * @property string $creacionCgFormulariosPqrsDetalle
 *
 * @property CgFormulariosPqrs $idCgFormulariosPqrs0
 * @property CgFormulariosPqrsDetalleDocumentos[] $cgFormulariosPqrsDetalleDocumentos
 */
class CgFormulariosPqrsDetalle extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgFormulariosPqrsDetalle';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgFormulariosPqrs', 'nombreCgFormulariosPqrsDetalle', 'descripcionCgFormulariosPqrsDetalle', 'adjuntarDocsCgFormulariosPqrsDetalle', 'terminosCgFormulariosPqrsDetalle', 'datosSelectorPrestacionCgFormulariosPqrsDetalle', 'datosSelectorBeneficiarioCgFormulariosPqrsDetalle', 'activarBeneficiarioCgFormulariosPqrsDetalle', 'idTipoDocumentalCgFormulariosPqrsDetalle', 'idSerieCgFormulariosPqrsDetalle', 'idSubserieCgFormulariosPqrsDetalle'], 'required'],
            [['idCgFormulariosPqrs', 'idTipoDocumentalCgFormulariosPqrsDetalle', 'idSerieCgFormulariosPqrsDetalle', 'idSubserieCgFormulariosPqrsDetalle', 'estadoCgFormulariosPqrsDetalle'], 'integer'],
            [['descripcionCgFormulariosPqrsDetalle', 'adjuntarDocsCgFormulariosPqrsDetalle', 'terminosCgFormulariosPqrsDetalle'], 'string'],
            [['creacionCgFormulariosPqrsDetalle'], 'safe'],
            [['nombreCgFormulariosPqrsDetalle', 'datosSelectorPrestacionCgFormulariosPqrsDetalle', 'datosSelectorBeneficiarioCgFormulariosPqrsDetalle'], 'string', 'max' => 80],
            [['activarBeneficiarioCgFormulariosPqrsDetalle'], 'string', 'max' => 500],
            [['idCgFormulariosPqrs'], 'exist', 'skipOnError' => true, 'targetClass' => CgFormulariosPqrs::className(), 'targetAttribute' => ['idCgFormulariosPqrs' => 'idCgFormulariosPqrs']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgFormulariosPqrsDetalle' => 'Id Cg Formularios Pqrs Detalle',
            'idCgFormulariosPqrs' => 'Id Cg Formularios Pqrs',
            'nombreCgFormulariosPqrsDetalle' => 'Nombre Cg Formularios Pqrs Detalle',
            'descripcionCgFormulariosPqrsDetalle' => 'Descripcion Cg Formularios Pqrs Detalle',
            'adjuntarDocsCgFormulariosPqrsDetalle' => 'Adjuntar Docs Cg Formularios Pqrs Detalle',
            'terminosCgFormulariosPqrsDetalle' => 'Terminos Cg Formularios Pqrs Detalle',
            'datosSelectorPrestacionCgFormulariosPqrsDetalle' => 'Datos Selector Prestacion Cg Formularios Pqrs Detalle',
            'datosSelectorBeneficiarioCgFormulariosPqrsDetalle' => 'Datos Selector Beneficiario Cg Formularios Pqrs Detalle',
            'activarBeneficiarioCgFormulariosPqrsDetalle' => 'Activar Beneficiario Cg Formularios Pqrs Detalle',
            'idTipoDocumentalCgFormulariosPqrsDetalle' => 'Id Tipo Documental Cg Formularios Pqrs Detalle',
            'idSerieCgFormulariosPqrsDetalle' => 'Id Serie Cg Formularios Pqrs Detalle',
            'idSubserieCgFormulariosPqrsDetalle' => 'Id Subserie Cg Formularios Pqrs Detalle',
            'estadoCgFormulariosPqrsDetalle' => 'Estado Cg Formularios Pqrs Detalle',
            'creacionCgFormulariosPqrsDetalle' => 'Creacion Cg Formularios Pqrs Detalle',
        ];
    }

    /**
     * Gets query for [[IdCgFormulariosPqrs0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgFormulariosPqrs0()
    {
        return $this->hasOne(CgFormulariosPqrs::className(), ['idCgFormulariosPqrs' => 'idCgFormulariosPqrs']);
    }

    /**
     * Gets query for [[CgFormulariosPqrsDetalleDocumentos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgFormulariosPqrsDetalleDocumentos()
    {
        return $this->hasMany(CgFormulariosPqrsDetalleDocumentos::className(), ['idCgFormulariosPqrsDetalle' => 'idCgFormulariosPqrsDetalle']);
    }
}
