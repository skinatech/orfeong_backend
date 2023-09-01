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
 * This is the model class for table "clientesCiudadanosDetalles".
 *
 * @property int $idClienteCiudadanoDetalle Identificador único de la tabla ClientesCiudadanosDetalles
 * @property int $idCliente Identificador único de la tabla clientes asociada
 * @property int $idUser relación con la tabla user
 * @property int $idTipoIdentificacion
 * @property int $generoClienteCiudadanoDetalle 1: Hombre | 2: Mujer | 3: Otro
 * @property int $rangoEdadClienteCiudadanoDetalle 1: 7-18 | 2: 19-29 | 3: 30-58 | 4: 59-
 * @property int $vulnerabilidadClienteCiudadanoDetalle 1: Niño-Niña-Adolecente | 2: Desplazado | 3:  VictimaConflictoArmado | 4: Discapacidad | 5: Migrante | 6: Ninguno
 * @property int $etniaClienteCiudadanoDetalle 1: Indigina | 2: Negro-Mulato-Afro | 3: Palanquero | 4: ROM | 5: Ninguno
 * @property int $etniaClienteCiudadanoDetalle
 * @property int $actEcomicaClienteCiudadanoDetalle
 * @property int $condDiscapacidadClienteCiudadanoDetall
 * @property int $estratoClienteCiudadanoDetalle
 * @property int $grupoInteresClienteCiudadanoDetalle
 * @property int $grupoSisbenClienteCiudadanoDetalle
 * @property int $escolaridadClienteCiudadanoDetalle
 * @property int $estadoClienteCiudadanoDetalle 0: Inactivo | 10: Activo
 * @property string $creacionClienteCiudadanoDetalle Fecha de creación de registro
 * @property string $barrioClientesCiudadanoDetalle
 * @property string $representanteCliente Guarda información del representate legal apra persona juridica o apellido para persona natural
 * @property string $telefonoFijoClienteCiudadanoDetalle // Segundo numero para el registro de la pqrs
 * 
 * @property User $idUser
 * @property TiposIdentificacion $idTipoIdentificacion
 * @property Clientes $idCliente
 */
class ClientesCiudadanosDetalles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'clientesCiudadanosDetalles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            // [['idCliente', 'idUser', 'idTipoIdentificacion', 'generoClienteCiudadanoDetalle', 'rangoEdadClienteCiudadanoDetalle', 'vulnerabilidadClienteCiudadanoDetalle', 'etniaClienteCiudadanoDetalle'], 'required'],
            [['idCliente', 'idUser', 'idTipoIdentificacion', 'generoClienteCiudadanoDetalle', 'rangoEdadClienteCiudadanoDetalle', 'vulnerabilidadClienteCiudadanoDetalle', 'etniaClienteCiudadanoDetalle', 'estadoClienteCiudadanoDetalle'], 'integer'],
            [['actEcomicaClienteCiudadanoDetalle', 'condDiscapacidadClienteCiudadanoDetalle', 'estratoClienteCiudadanoDetalle', 'grupoInteresClienteCiudadanoDetalle', 'grupoSisbenClienteCiudadanoDetalle', 'escolaridadClienteCiudadanoDetalle'], 'integer'],
            [['creacionClienteCiudadanoDetalle'], 'safe'],
            [['representanteCliente'], 'string', 'max' => 120],
            [['barrioClientesCiudadanoDetalle'], 'string', 'max' => 80],   
            [['telefonoFijoClienteCiudadanoDetalle'], 'string', 'max' => 25],  
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idTipoIdentificacion'], 'exist', 'skipOnError' => true, 'targetClass' => TiposIdentificacion::className(), 'targetAttribute' => ['idTipoIdentificacion' => 'idTipoIdentificacion']],
            [['idCliente'], 'exist', 'skipOnError' => true, 'targetClass' => Clientes::className(), 'targetAttribute' => ['idCliente' => 'idCliente']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idClienteCiudadanoDetalle' => 'Id Cliente Ciudadano Detalle',
            'idCliente' => 'Id Cliente',
            'idUser' => '',
            'idTipoIdentificacion' => 'Id tipo identificación',
            'generoClienteCiudadanoDetalle' => 'Género del cliente ciudadano',
            'rangoEdadClienteCiudadanoDetalle' => 'Rango de edad del cliente ciudadano',
            'vulnerabilidadClienteCiudadanoDetalle' => 'Vulnerabilidad del cliente ciudadano',
            'etniaClienteCiudadanoDetalle' => 'Etnia del cliente ciudadano',
            'actEcomicaClienteCiudadanoDetalle' => '',
            'condDiscapacidadClienteCiudadanoDetall' => '',
            'estratoClienteCiudadanoDetalle' => '',
            'grupoInteresClienteCiudadanoDetalle' => '',
            'grupoSisbenClienteCiudadanoDetalle' => '',
            'escolaridadClienteCiudadanoDetalle' => '',
            'estadoClienteCiudadanoDetalle' => 'Estado',
            'creacionClienteCiudadanoDetalle' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[IdUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[IdTipoIdentificacion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTipoIdentificacion()
    {
        return $this->hasOne(TiposIdentificacion::className(), ['idTipoIdentificacion' => 'idTipoIdentificacion']);
    }

    /**
     * Gets query for [[IdCliente]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCliente()
    {
        return $this->hasOne(Clientes::className(), ['idCliente' => 'idCliente']);
    }
}
