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
 * This is the model class for table "cgGeneral".
 *
 * @property int $idCgGeneral Id configuración general
 * @property string $tamanoArchivoCgGeneral Tamaño del archivo
 * @property int $diasLimiteCgGeneral Días límite de cambio contraseña
 * @property string $correoNotificadorAdminCgGeneral Correo de administrador de transferencias
 * @property string $correoNotificadorPqrsCgGeneral Correo de administrador Pqrs
 * @property int $diasNotificacionCgGeneral Días de notificación
 * @property string $terminoCondicionCgGeneral Términos y condiciones
 * @property int $diaRespuestaPqrsCgGeneral Días de respuesta del ciudadano Pqrs
 * @property int $idDependenciaPqrsCgGeneral Id dependencia del usuario Pqrs
 * @property int $estadoCgGeneral Estado configuración general
 * @property string $creacionCgGeneral Fecha creación
 * @property int|null $tiempoInactividadCgGeneral Tiempo de inactividad de sesión en minutos
 * @property string $iniConsClienteCgGeneral Consecutivo donde va a iniciar el nit de los clientes (remitentes) en caso de no tener el dato. inicia en 4000001251, por el cliente
 * @property int $resolucionesCgGeneral
 * @property int $resolucionesIdCgGeneral
 * @property string $resolucionesNameCgGeneral
 * @property string $codDepePrestaEconomicasCgGeneral
 * @property int $idPrestaPazYsalvoCgGeneral
 */
class CgGeneral extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgGeneral';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tamanoArchivoCgGeneral', 'diasLimiteCgGeneral', 'correoNotificadorAdminCgGeneral', 'correoNotificadorPqrsCgGeneral', 'diasNotificacionCgGeneral', 'terminoCondicionCgGeneral', 'diaRespuestaPqrsCgGeneral', 'idDependenciaPqrsCgGeneral', 'idPrestaPazYsalvoCgGeneral'], 'required'],
            [['diasLimiteCgGeneral', 'diasNotificacionCgGeneral', 'diaRespuestaPqrsCgGeneral', 'idDependenciaPqrsCgGeneral', 'estadoCgGeneral', 'tiempoInactividadCgGeneral', 'resolucionesCgGeneral', 'resolucionesIdCgGeneral', 'codDepePrestaEconomicasCgGeneral', 'idPrestaPazYsalvoCgGeneral'], 'integer'],
            [['terminoCondicionCgGeneral'], 'string'],
            [['creacionCgGeneral'], 'safe'],
            [['tamanoArchivoCgGeneral'], 'string', 'max' => 20],
            [['correoNotificadorAdminCgGeneral', 'correoNotificadorPqrsCgGeneral', 'resolucionesNameCgGeneral'], 'string', 'max' => 80],
            [['iniConsClienteCgGeneral'], 'string', 'max' => 15],
            [['codDepePrestaEconomicasCgGeneral'], 'string', 'max' => 6],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgGeneral' => 'Id Cg General',
            'tamanoArchivoCgGeneral' => 'Tamano Archivo Cg General',
            'diasLimiteCgGeneral' => 'Dias Limite Cg General',
            'correoNotificadorAdminCgGeneral' => 'Correo Notificador Admin Cg General',
            'correoNotificadorPqrsCgGeneral' => 'Correo Notificador Pqrs Cg General',
            'diasNotificacionCgGeneral' => 'Dias Notificacion Cg General',
            'terminoCondicionCgGeneral' => 'Termino Condicion Cg General',
            'diaRespuestaPqrsCgGeneral' => 'Dia Respuesta Pqrs Cg General',
            'idDependenciaPqrsCgGeneral' => 'Id Dependencia Pqrs Cg General',
            'estadoCgGeneral' => 'Estado Cg General',
            'creacionCgGeneral' => 'Creacion Cg General',
            'tiempoInactividadCgGeneral' => 'Tiempo Inactividad Cg General',
            'iniConsClienteCgGeneral' => 'Ini Cons Cliente Cg General',
            'resolucionesCgGeneral' => 'Resoluciones Cg General',
            'resolucionesIdCgGeneral' => 'Resoluciones Id Cg General',
            'resolucionesNameCgGeneral' => 'Resoluciones Name Cg General',
            'codDepePrestaEconomicasCgGeneral' => 'Cod Depe Presta Economicas Cg General',
            'idPrestaPazYsalvoCgGeneral' => 'Id Presta Paz Y salvo Cg General',
        ];
    }

    /**
     * Gets query for [[IdDependenciaPqrsCgGeneral0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdDependenciaPqrsCgGeneral0()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idDependenciaPqrsCgGeneral']);
    }
}
