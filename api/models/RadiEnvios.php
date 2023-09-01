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
 * This is the model class for table "radiEnvios".
 *
 * @property int $idRadiEnvio
 * @property string $numeroGuiaRadiEnvio
 * @property string $observacionRadiEnvio Observación ingresada en la correspondencia
 * @property int $idUser     Identificador del usuario que cargo el documento
 * @property int $idRadiRadicado identificador de la tabla radiRadicados
 * @property int $idCgRegional identificador de la tabla cgRegionales
 * @property int $idCgProveedores identificador de la tabla cgProveedores
 * @property int $idCgEnvioServicio identificador de la tabla cgEnviosServicios
 * @property string|null $rutaRadiEnvio     Ruta donde se encuentra el documento que se cargo para el radicado
 * @property string|null $extensionRadiEnvio     Guarda la extención del documento que se esta cargando
 * @property int $estadoRadiEnvio Estado que se le aplica al documento
 * @property string $creacionRadiEnvio Fecha en la que se crea el documento
 *
 * @property RadiRadicados $idRadiRadicado0
 * @property CgEnvioServicios $idCgEnvioServicio0
 * @property CgProveedores $idCgProveedores0
 * @property CgRegionales $idCgRegional0
 * @property UserDetalles $idUser0
 * @property RadiEnviosDevolucion[] $radiEnviosDevolucions
 */
class RadiEnvios extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiEnvios';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['observacionRadiEnvio', 'idUser', 'idRadiRadicado', 'idCgRegional', 'idCgProveedores', 'idCgEnvioServicio'], 'required'],
            [['idUser', 'idRadiRadicado', 'idCgRegional', 'idCgProveedores', 'idCgEnvioServicio', 'estadoRadiEnvio'], 'integer'],
            [['creacionRadiEnvio'], 'safe'],
            [['numeroGuiaRadiEnvio', 'extensionRadiEnvio'], 'string', 'max' => 20],
            [['observacionRadiEnvio'], 'string', 'max' => 500],
            [['rutaRadiEnvio'], 'string', 'max' => 250],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idCgEnvioServicio'], 'exist', 'skipOnError' => true, 'targetClass' => CgEnvioServicios::className(), 'targetAttribute' => ['idCgEnvioServicio' => 'idCgEnvioServicio']],
            [['idCgProveedores'], 'exist', 'skipOnError' => true, 'targetClass' => CgProveedores::className(), 'targetAttribute' => ['idCgProveedores' => 'idCgProveedor']],
            [['idCgRegional'], 'exist', 'skipOnError' => true, 'targetClass' => CgRegionales::className(), 'targetAttribute' => ['idCgRegional' => 'idCgRegional']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiEnvio' => 'Id envío',
            'numeroGuiaRadiEnvio' => 'Número guía de envío',
            'observacionRadiEnvio' => 'Observación',
            'idUser' => 'Usuario',
            'idRadiRadicado' => 'Radicado',
            'idCgRegional' => 'Regional',
            'idCgProveedores' => 'Proveedor',
            'idCgEnvioServicio' => 'Servicio',
            'rutaRadiEnvio' => 'Ruta archivo',
            'extensionRadiEnvio' => 'Extensión',
            'estadoRadiEnvio' => 'Estado envío',
            'creacionRadiEnvio' => 'Creación envío',
        ];
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
     * Gets query for [[IdCgEnvioServicio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgEnvioServicio0()
    {
        return $this->hasOne(CgEnvioServicios::className(), ['idCgEnvioServicio' => 'idCgEnvioServicio']);
    }

    /**
     * Gets query for [[IdCgProveedores0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgProveedores0()
    {
        return $this->hasOne(CgProveedores::className(), ['idCgProveedor' => 'idCgProveedores']);
    }

    /**
     * Gets query for [[IdCgRegional0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgRegional0()
    {
        return $this->hasOne(CgRegionales::className(), ['idCgRegional' => 'idCgRegional']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[RadiEnviosDevolucions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiEnviosDevolucions()
    {
        return $this->hasMany(RadiEnviosDevolucion::className(), ['idRadiEnvio' => 'idRadiEnvio']);
    }
}
