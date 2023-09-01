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
 * This is the model class for table "userPqrs".
 * 
 * @property string $nombreCliente
 * @property string $apellidoCliente
 * @property string $primerNombre
 * @property int $numeroDocumentoCliente
 * @property int $idTipoPersona
 * @property int $idTipoIdentificacion
 * 
 * @property int $generoClienteCiudadanoDetalle
 * @property int $rangoEdadClienteCiudadanoDetalle
 * @property int $vulnerabilidadClienteCiudadanoDetalle 
 * @property int $etniaClienteCiudadanoDetalle 
 * @property string  $barrioClientesCiudadanoDetalle
 * 
 * @property string $telefonoCliente
 * 
 * @property int $idNivelGeografico1
 * @property int $idNivelGeografico3
 * @property int $idNivelGeografico2
 * 
 * @property string $direccionCliente
 * @property string $dirCam1
 * @property string $dirCam2
 * @property string $dirCam3
 * @property string $dirCam4
 * @property string $dirCam5
 * @property string $dirCam6
 * 
 * @property string $email
 * @property string $username
 * @property string $password
 */


class UserPqrs extends \yii\db\ActiveRecord
{ 

    # Datos Personales
    public  $nombreCliente,
            $apellidoCliente,
            $numeroDocumentoCliente,
            $idTipoPersona,
            $idTipoIdentificacion;
    public $genero; //Genero
    public $rangoEdad; //Rango Edad
    public $vulnerabilidad; //Vulnerabilidad
    public $etnia; //Etnia
    public $actividadEconomica; //Actividad Economica
    public $condicionDiscapacidad; //Condición Discapacidad
    public $estrato; //Estrato
    public $grupoInteres; //Grupo Interes
    public $grupoSisben; //Grupo Sisben
    public $escolaridad; //Escolaridad
    public $barrioClientesCiudadanoDetalle;
    public $primerNombre;
    public $segundoNombre;
    public $primerApellido;
    public $segundoApellido;

    # Datos de corespondencia
    public  $telefonoCliente, $idNivelGeografico1, $idNivelGeografico2, $idNivelGeografico3;
    public  $direccionCliente, $dirCam1, $dirCam2, $dirCam3, $dirCam4, $dirCam5, $dirCam6;

    # Datos de Acceso
    public  $email;
    public  $username;
    public  $password;

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
            [['username','email','password','idTipoPersona', 'idTipoIdentificacion', 'numeroDocumentoCliente', 'telefonoCliente', 'idNivelGeografico1', 'idNivelGeografico2', 'idNivelGeografico3'], 'required', 'message' => 'Este campo no puede estar vacío.'],
            [['idTipoPersona', 'idTipoIdentificacion', 'estadoCliente', 'idNivelGeografico1', 'idNivelGeografico3'], 'integer', 'message' => 'Este campo no puede estar vacío.'],

            [['genero', 'rangoEdad', 'vulnerabilidad', 'etnia', 'actividadEconomica', 'condicionDiscapacidad', 'estrato', 'grupoInteres', 'grupoSisben', 'escolaridad', 'estadoClienteCiudadanoDetalle'], 'integer'],
            
            [['dirCam1', 'dirCam2', 'dirCam3', 'dirCam4', 'dirCam5', 'dirCam6'], 'required','message' => ''],
            [['dirCam1', 'dirCam2', 'dirCam3', 'dirCam4', 'dirCam5', 'dirCam6'], 'string'],

            [['email'], 'string', 'max' => 80 ,'tooLong' => 'Este campo no debe ser mayor que 80 dígitos.'],
            [['nombreCliente','apellidoCliente', 'username', 'primerNombre', 'segundoNombre', 'primerApellido', 'segundoApellido'], 'string', 'max' => 40 ,'tooLong' => 'Este campo no debe ser mayor que 40 dígitos.'],
            [['numeroDocumentoCliente'], 'string', 'max' => 15, 'tooLong' => 'Este campo no debe ser mayor que 15 dígitos.'],

            [['telefonoCliente'], 'string', 'max' => 15, 'min' => 7, 'tooLong' => 'Este campo no debe ser mayor que 15 dígitos.', 'tooShort' => 'Este campo debe ser mayor que 7 dígitos.'],

            [['direccionCliente','barrioClientesCiudadanoDetalle'], 'string', 'max' => 150],
            [['dirCam2','dirCam4', 'dirCam5'], 'string', 'max' => 4,'message' => ''],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'nombreCliente' => "",
            'apellidoCliente' => "",
            'numeroDocumentoCliente' => "",
            'idTipoPersona' => "",
            'idTipoIdentificacion' => "",
            'genero' => '',
            'rangoEdad' => '',
            'vulnerabilidad' => '',
            'etnia' => '',
            'actividadEconomica' => '',
            'condicionDiscapacidad' => '',
            'estrato' => '',
            'grupoInteres' => '',
            'grupoSisben' => '',
            'escolaridad' => '',
            'telefonoCliente' => "",
            'idNivelGeografico1' => "",
            'idNivelGeografico3' => "",
            'idNivelGeografico2' => "",
            'direccionCliente' => "",
            'dirCam1' => "",
            'dirCam2' => "",
            'dirCam3' => "",
            'dirCam4' => "",
            'dirCam5' => "",
            'dirCam6' => "",
            'email' => "",
            'username' => "",
            'password' => "",
            'barrioClientesCiudadanoDetalle' => "",
            'primerNombre' => '',
            'segundoNombre' => '',
            'primerApellido' => '',
            'segundoApellido' => '',
        ];

    }

}
