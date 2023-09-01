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
 * This is the model class for table "radiRemitentes".
 *
 * @property int $idRadiRemitente Identificador de la tabla
 * @property int $idRadiRadicado Este campo referencia el id del radicado que se esta creando y asociando a una persona (cliente o usuario)
 * @property int $idRadiPersona Este campo referencia el id del cliente o del usuarios segun sea el caso cuando se radica.
 * @property int $idTipoPersona Este campo identifica que tipo de persona es la que se esta seleccionando para el radicado
 * @property string|null $crearRadiRemitente Guarda la fecha en la que se ejecuta la operación
 *
 * @property RadiRadicados $idRadiRadicado0
 * @property TiposPersonas $idTipoPersona0
 */
class RadiRemitentes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRemitentes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idRadiPersona', 'idTipoPersona'], 'required'],
            [['idRadiRadicado', 'idRadiPersona', 'idTipoPersona'], 'integer'],
            [['crearRadiRemitente'], 'safe'],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idTipoPersona'], 'exist', 'skipOnError' => true, 'targetClass' => TiposPersonas::className(), 'targetAttribute' => ['idTipoPersona' => 'idTipoPersona']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRemitente' => 'Id Radi Remitente',
            'idRadiRadicado' => 'Id Radi Radicado',
            'idRadiPersona' => 'Id Radi Persona',
            'idTipoPersona' => 'Id Tipo Persona',
            'crearRadiRemitente' => 'Crear Radi Remitente',
        ];
    }

    public function attributeLabelsReport()
    {
        $alias = 'REM';
        $imputsPermitidos = [
            'idTipoPersona' => 'LIKE',
            'ciudad' => 'LIKE', 'direccion' => 'LIKE', 'documento' => 'LIKE', 'nombre' => 'LIKE',
            /** Espacio para personalizacion del cliente */
            'codigoSap' => 'LIKE',
            /** Fin Espacio para personalizacion del cliente */
        ];

        $inputsRelation = [
            'idTipoPersona' => [
                'type' => 'model',
                'foreingModel' => 'TiposPersonas',
                'foreingAlias' => 'REMTP',
                'foreignKey' => 'idTipoPersona',
                'foreingColumn' => 'tipoPersona',
                'alias' => $alias,
                'key' => 'idTipoPersona',
            ],
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        // Cliente
        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'idTipoPersona',
            'label'         => 'Tipo de persona',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['idTipoPersona'],
            'infoRelation'  => (array_key_exists('idTipoPersona', $inputsRelation)) ? $inputsRelation['idTipoPersona'] : false,
        ];

        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'ciudad',
            'label'         => 'Ciudad',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['ciudad'],
            'infoRelation'  => (array_key_exists('ciudad', $inputsRelation)) ? $inputsRelation['ciudad'] : false,
        ];

        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'direccion',
            'label'         => 'Dirección',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['direccion'],
            'infoRelation'  => (array_key_exists('direccion', $inputsRelation)) ? $inputsRelation['direccion'] : false,
        ];

        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'documento',
            'label'         => 'Documento de identidad (Nit/Cédula)',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['documento'],
            'infoRelation'  => (array_key_exists('documento', $inputsRelation)) ? $inputsRelation['documento'] : false,
        ];

        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'nombre',
            'label'         => 'Nombre',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['nombre'],
            'infoRelation'  => (array_key_exists('nombre', $inputsRelation)) ? $inputsRelation['nombre'] : false,
        ];

        /** Espacio para personalizacion del cliente */
        $arrayAttributeLabelsReport[] = [
            'column'        => $alias.'_'. 'codigoSap',
            'label'         => 'Código SAP',
            'value'         => false,
            'typeFilter'    => $imputsPermitidos['codigoSap'],
            'infoRelation'  => (array_key_exists('codigoSap', $inputsRelation)) ? $inputsRelation['codigoSap'] : false,
        ];
        /** Fin Espacio para personalizacion del cliente */

        return $arrayAttributeLabelsReport;
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiRadicado0()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[IdTipoPersona0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdTipoPersona0()
    {
        return $this->hasOne(TiposPersonas::className(), ['idTipoPersona' => 'idTipoPersona']);
    }
}
