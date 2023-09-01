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
 * This is the model class for table "radiRadicadoAnulado".
 *
 * @property int $idRadiRadicadoAnulado Numero unico de la tabla
 * @property string|null $rutaActaRadiRadicadoAnulado Ruta del archivo del acta
 * @property int|null $codigoActaRadiRadicadoAnulado Código del archivo del acta
 * @property string $observacionRadiRadicadoAnulado Observación de la anulación del radicado
 * @property int $idRadicado Id del radicado
 * @property string $fechaRadiRadicadoAnulado Fecha de solicitud de la anulación
 * @property int $idResponsable Id del usuario
 * @property int $idEstado Id del estado radicado
 * @property int $estadoRadiRadicadoAnulado Estado 0 Inactivo 10 Activo 
 * @property string $creacionRadiRadicadoAnulado Fecha de creación del radicado anulado 
 *
 * @property RadiEstadosAnulacion $idEstado0
 * @property RadiRadicados $idRadicado0
 * @property User $idResponsable0
 */
class RadiRadicadoAnulado extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicadoAnulado';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['codigoActaRadiRadicadoAnulado', 'idRadicado', 'idResponsable', 'idEstado', 'estadoRadiRadicadoAnulado'], 'integer'],
            [['idRadicado', 'idResponsable'], 'required'],
            [['fechaRadiRadicadoAnulado', 'creacionRadiRadicadoAnulado'], 'safe'],
            [['rutaActaRadiRadicadoAnulado'], 'string', 'max' => 255],
            [['observacionRadiRadicadoAnulado'], 'string', 'max' => 255],
            [['idEstado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiEstadosAnulacion::className(), 'targetAttribute' => ['idEstado' => 'idRadiEstadoAnulacion']],
            [['idRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadicado' => 'idRadiRadicado']],
            [['idResponsable'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idResponsable' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicadoAnulado' => 'Id radicado anulado',
            'rutaActaRadiRadicadoAnulado' => 'Ruta del archivo del acta',
            'codigoActaRadiRadicadoAnulado' => 'Código del archivo del acta',
            'observacionRadiRadicadoAnulado' => 'Observación de la anulación del radicado',
            'idRadicado' => 'Id radicado',
            'fechaRadiRadicadoAnulado' => 'Fecha de solicitud de la anulación',
            'idResponsable' => 'Id usuario',
            'idEstado' => 'Id estado radicado',
            'estadoRadiRadicadoAnulado' => 'Estado', 
            'creacionRadiRadicadoAnulado' => 'Fecha de creación de la anulación', 
        ];
    }

    /**
     * Gets query for [[IdEstado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdEstado0()
    {
        return $this->hasOne(RadiEstadosAnulacion::className(), ['idRadiEstadoAnulacion' => 'idEstado']);
    }

    /**
     * Gets query for [[IdRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadicado0()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadicado']);
    }

    /**
     * Gets query for [[IdResponsable0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdResponsable0()
    {
        return $this->hasOne(User::className(), ['id' => 'idResponsable']);
    }
}
