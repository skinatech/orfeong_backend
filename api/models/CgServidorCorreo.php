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
 * This is the model class for table "cgServidorCorreo".
 *
 * @property int $idCgServidorCorreo Id de la configuración del servidor
 * @property string $servidorCgServidorCorreo Nombre servidor
 * @property string $correoNotificadoCgServidorCorreo Correo del notificado
 * @property string $contrasenaCgServidorCorreo Contraseña del correo
 * @property int $puertoSmtpCgServidorCorreo Puerto Smtp
 * @property string $metodoConexionCgServidorCorreo Método de conexión
 * @property int $estadoCgServidorCorreo Estado del servidor
 * @property string $creacionCgServidorCorreo Fecha creación del servidor
 */
class CgServidorCorreo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgServidorCorreo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['servidorCgServidorCorreo', 'correoNotificadoCgServidorCorreo', 'contrasenaCgServidorCorreo', 'puertoSmtpCgServidorCorreo', 'metodoConexionCgServidorCorreo'], 'required'],
            [['puertoSmtpCgServidorCorreo', 'estadoCgServidorCorreo'], 'integer'],
            [['creacionCgServidorCorreo'], 'safe'],
            [['servidorCgServidorCorreo'], 'string', 'max' => 120],
            [['correoNotificadoCgServidorCorreo'], 'string', 'max' => 80],
            [['contrasenaCgServidorCorreo'], 'string', 'max' => 255],
            [['metodoConexionCgServidorCorreo'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgServidorCorreo' => 'Id configuración del servidor',
            'servidorCgServidorCorreo' => 'Nombre servidor',
            'correoNotificadoCgServidorCorreo' => 'Correo del notificado',
            'contrasenaCgServidorCorreo' => 'Contraseña del correo',
            'puertoSmtpCgServidorCorreo' => 'Puerto Smtp',
            'metodoConexionCgServidorCorreo' => 'Método de conexión',
            'estadoCgServidorCorreo' => 'Estado',
            'creacionCgServidorCorreo' => 'Fecha creación',
        ];
    }
}
