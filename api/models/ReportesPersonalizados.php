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
 * This is the model class for table "reportesPersonalizados".
 *
 * @property int $idReportePersonalizado Campo primario de la tabla
 * @property string $nombreReportePersonalizado Nombre del reporte
 * @property string $descripcionReportePersonalizado Descripción del reporte
 * @property string $jsonReportePersonalizado Cadena de propiedades del reporte en formato json
 * @property int $estadoReportePersonalizado Estado 10 para habilitado 0 para inhabilitado
 * @property string $creacionReportePersonalizado Fecha de creación del reporte
 * @property int $idUserCreadorReportePersonalizado Usuario creador del reporte
 *
 * @property User $idUserCreadorReportePersonalizado0
 */
class ReportesPersonalizados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'reportesPersonalizados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreReportePersonalizado', 'descripcionReportePersonalizado', 'jsonReportePersonalizado', 'idUserCreadorReportePersonalizado'], 'required'],
            [['jsonReportePersonalizado', 'creacionReportePersonalizado'], 'safe'],
            [['estadoReportePersonalizado', 'idUserCreadorReportePersonalizado'], 'integer'],
            [['nombreReportePersonalizado'], 'string', 'max' => 100],
            [['descripcionReportePersonalizado'], 'string', 'max' => 500],
            [['nombreReportePersonalizado'], 'unique'],
            [['idUserCreadorReportePersonalizado'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUserCreadorReportePersonalizado' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idReportePersonalizado' => 'Id Reporte Personalizado',
            'nombreReportePersonalizado' => 'Nombre Reporte Personalizado',
            'descripcionReportePersonalizado' => 'Descripcion Reporte Personalizado',
            'jsonReportePersonalizado' => 'Json Reporte Personalizado',
            'estadoReportePersonalizado' => 'Estado Reporte Personalizado',
            'creacionReportePersonalizado' => 'Creacion Reporte Personalizado',
            'idUserCreadorReportePersonalizado' => 'User Creador Reporte Personalizado',
        ];
    }

    /**
     * Gets query for [[IdUserCreadorReportePersonalizado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUserCreadorReportePersonalizado0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUserCreadorReportePersonalizado']);
    }
}
