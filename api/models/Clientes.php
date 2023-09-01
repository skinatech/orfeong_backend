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
 * This is the model class for table "clientes".
 *
 * @property int $idCliente
 * @property int $idTipoPersona
 * @property string $nombreCliente
 * @property string $numeroDocumentoCliente
 * @property string $correoElectronicoCliente
 * @property string $direccionCliente
 * @property string $telefonoCliente
 * @property int $estadoCliente
 * @property string $creacionCliente
 * @property int $idNivelGeografico1
 * @property int $idNivelGeografico3
 * @property int $idNivelGeografico2
 * @property string|null $codigoSap Código SAP
 *
 * @property TiposPersonas $tipoPersona
 * @property ClientesCiudadanosDetalles[] $clientesCiudadanosDetalles
 * @property RadiRadicados[] $radiRadicados
 * @property NivelGeografico2 $idNivelGeografico20
 * @property NivelGeografico1 $idNivelGeografico10
 * @property NivelGeografico3 $idNivelGeografico30
 * 
 * @property string $DirCam1
 * @property string $DirCam2
 * @property string $DirCam3
 * @property string $DirCam4
 * @property string $DirCam5
 * @property string $DirCam6
 * 
 */



class Clientes extends \yii\db\ActiveRecord
{
    

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'clientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idTipoPersona', 'nombreCliente', 'numeroDocumentoCliente','direccionCliente', 'idNivelGeografico1', 'idNivelGeografico3', 'idNivelGeografico2'], 'required'],
            [['idTipoPersona', 'estadoCliente', 'idNivelGeografico1', 'idNivelGeografico3', 'idNivelGeografico2'], 'integer'],
            [['creacionCliente'], 'safe'],
            [['nombreCliente'], 'string', 'max' => 150],
            [['numeroDocumentoCliente', 'telefonoCliente'], 'string', 'max' => 15],
            [['correoElectronicoCliente'], 'string', 'max' => 80],
            [['direccionCliente'], 'string', 'max' => 150],
            [['idNivelGeografico2'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico2::className(), 'targetAttribute' => ['idNivelGeografico2' => 'nivelGeografico2']],
            [['idNivelGeografico1'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico1::className(), 'targetAttribute' => ['idNivelGeografico1' => 'nivelGeografico1']],
            [['idNivelGeografico3'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico3::className(), 'targetAttribute' => ['idNivelGeografico3' => 'nivelGeografico3']],
            [['idTipoPersona'], 'exist', 'skipOnError' => true, 'targetClass' => TiposPersonas::className(), 'targetAttribute' => ['idTipoPersona' => 'idTipoPersona']],
            [['codigoSap'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCliente' => '',
            'idTipoPersona' => '',
            'nombreCliente' => 'Nombre Cliente',
            'numeroDocumentoCliente' => 'Número Documento Cliente',
            'correoElectronicoCliente' => 'Correo Electrónico Cliente',
            'direccionCliente' => 'Dirección Cliente',
            'telefonoCliente' => 'Telefono Cliente',
            'estadoCliente' => 'Estado Cliente',
            'creacionCliente' => 'Creación Cliente',
            'idNivelGeografico1' => '',
            'idNivelGeografico3' => '',
            'idNivelGeografico2' => '',
            'codigoSap' => 'Código Sap',
        ];
    }

      /**
     * Gets query for [[IdNivelGeografico20]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdNivelGeografico20()
    {
        return $this->hasOne(NivelGeografico2::className(), ['nivelGeografico2' => 'idNivelGeografico2']);
    }

    /**
     * Gets query for [[IdNivelGeografico10]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdNivelGeografico10()
    {
        return $this->hasOne(NivelGeografico1::className(), ['nivelGeografico1' => 'idNivelGeografico1']);
    }

    /**
     * Gets query for [[IdNivelGeografico30]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdNivelGeografico30()
    {
        return $this->hasOne(NivelGeografico3::className(), ['nivelGeografico3' => 'idNivelGeografico3']);
    }


    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTipoPersona()
    {
        return $this->hasOne(TiposPersonas::className(), ['idTipoPersona' => 'idTipoPersona']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getClientesCiudadanosDetalles()
    {
        return $this->hasOne(ClientesCiudadanosDetalles::className(), ['idCliente' => 'idCliente']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicados()
    {
        return $this->hasMany(RadiRadicados::className(), ['idCliente' => 'idCliente']);
    }
}
