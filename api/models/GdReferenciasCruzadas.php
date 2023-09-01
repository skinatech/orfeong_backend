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
 * This is the model class for table "gdReferenciasCruzadas".
 *
 * @property int $idGdReferenciaCruzada Campo primario de la tabla
 * @property string $nombreGdReferenciaCruzada Nombre del medio 
 * @property int $cantidadGdReferenciaCruzada Cantidad de medios de la referencia cruzada
 * @property string $ubicacionGdReferenciaCruzada Ubicación
 * @property int $idGdExpediente Identificador del expediente
 * @property int $idUserGdReferenciaCruzada Usuario que elabora la referencia cruzada
 * @property string|null $tipoAnexoGdReferenciaCruzada Descripción del tipo de anexo de la referencia cruzada
 * @property string $rutaGdReferenciasCruzada Ruta del archivo generado
 * @property string|null $nombreArchivoGdReferenciasCruzada Nombre del archivo generado
 * @property int $idGdTrdTipoDocumental Tipo documental
 * @property string $creacionGdReferenciaCruzada Fecha de creación del registro
 * @property int $estadoGdReferenciaCruzada Estado de la referencia cruzada 0: Inactivo, 10: Activo
 *
 * @property GdReferenciaTiposAnexos[] $gdReferenciaTiposAnexos
 * @property User $idUserGdReferenciaCruzada0
 * @property GdExpedientes $idGdExpediente0
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 */
class GdReferenciasCruzadas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdReferenciasCruzadas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdReferenciaCruzada', 'cantidadGdReferenciaCruzada', 'ubicacionGdReferenciaCruzada', 'idGdExpediente', 'idUserGdReferenciaCruzada', 'rutaGdReferenciasCruzada', 'idGdTrdTipoDocumental'], 'required'],
            [['cantidadGdReferenciaCruzada', 'idGdExpediente', 'idUserGdReferenciaCruzada', 'idGdTrdTipoDocumental', 'estadoGdReferenciaCruzada'], 'integer'],
            [['creacionGdReferenciaCruzada'], 'safe'],
            [['nombreGdReferenciaCruzada', 'tipoAnexoGdReferenciaCruzada', 'nombreArchivoGdReferenciasCruzada'], 'string', 'max' => 80],
            [['ubicacionGdReferenciaCruzada', 'rutaGdReferenciasCruzada'], 'string', 'max' => 255],
            [['idUserGdReferenciaCruzada'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUserGdReferenciaCruzada' => 'id']],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdReferenciaCruzada' => 'Id Gd Referencia Cruzada',
            'nombreGdReferenciaCruzada' => 'Nombre Gd Referencia Cruzada',
            'cantidadGdReferenciaCruzada' => 'Cantidad Gd Referencia Cruzada',
            'ubicacionGdReferenciaCruzada' => 'Ubicacion Gd Referencia Cruzada',
            'idGdExpediente' => 'Id Gd Expediente',
            'idUserGdReferenciaCruzada' => 'Id User Gd Referencia Cruzada',
            'tipoAnexoGdReferenciaCruzada' => 'Tipo Anexo Gd Referencia Cruzada',
            'rutaGdReferenciasCruzada' => 'Ruta Gd Referencias Cruzada',
            'nombreArchivoGdReferenciasCruzada' => 'Nombre Archivo Gd Referencias Cruzada',
            'idGdTrdTipoDocumental' => 'Id Gd Trd Tipo Documental',
            'creacionGdReferenciaCruzada' => 'Creacion Gd Referencia Cruzada',
            'estadoGdReferenciaCruzada' => 'Estado Gd Referencia Cruzada',
        ];
    }

    /**
     * Gets query for [[GdReferenciaTiposAnexos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdReferenciaTiposAnexos()
    {
        return $this->hasMany(GdReferenciaTiposAnexos::className(), ['idGdReferenciaCruzada' => 'idGdReferenciaCruzada']);
    }

    /**
     * Gets query for [[IdUserGdReferenciaCruzada0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUserGdReferenciaCruzada0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUserGdReferenciaCruzada']);
    }

    /**
     * Gets query for [[IdGdExpediente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdExpediente0()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdTipoDocumental0()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }
}
