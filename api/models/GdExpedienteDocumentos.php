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
 * This is the model class for table "gdExpedienteDocumentos".
 *
 * @property int $idGdExpedienteDocumento
 * @property int $idGdExpediente
 * @property string $numeroGdExpedienteDocumento
 * @property string $rutaGdExpedienteDocumento
 * @property string $extensionGdExpedienteDocumento
 * @property string $tamanoGdExpedienteDocumento
 * @property int $idGdTrdTipoDocumental
 * @property int $isPublicoGdExpedienteDocumento
 * @property int $idUser
 * @property int $estadoGdExpedienteDocumento
 * @property string $creacionGdExpedienteDocumento
 * @property string $nombreGdExpedienteDocumento
 * @property string $observacionGdExpedienteDocumento
 * @property string|null $fechaDocGdExpedienteDocumento Fecha del documento cargado
 *
 * @property GdExpedientes $idGdExpediente0
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 * @property User $idUser0
 */
class GdExpedienteDocumentos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdExpedienteDocumentos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpediente', 'numeroGdExpedienteDocumento', 'rutaGdExpedienteDocumento', 'extensionGdExpedienteDocumento', 'tamanoGdExpedienteDocumento', 'idGdTrdTipoDocumental', 'isPublicoGdExpedienteDocumento', 'idUser', 'estadoGdExpedienteDocumento', 'creacionGdExpedienteDocumento', 'observacionGdExpedienteDocumento'], 'required'],
            [['idGdExpediente', 'idGdTrdTipoDocumental', 'isPublicoGdExpedienteDocumento', 'idUser', 'estadoGdExpedienteDocumento'], 'integer'],
            [['creacionGdExpedienteDocumento', 'fechaDocGdExpedienteDocumento'], 'safe'],
            [['observacionGdExpedienteDocumento'], 'string'],
            [['numeroGdExpedienteDocumento', 'extensionGdExpedienteDocumento'], 'string', 'max' => 80],
            [['rutaGdExpedienteDocumento', 'nombreGdExpedienteDocumento'], 'string', 'max' => 255],
            [['tamanoGdExpedienteDocumento'], 'string', 'max' => 20],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdExpedienteDocumento' => 'Id expediente documento',
            'idGdExpediente' => 'Id expediente',
            'numeroGdExpedienteDocumento' => 'Número expediente del documento',
            'rutaGdExpedienteDocumento' => 'Ruta del documento',
            'extensionGdExpedienteDocumento' => 'Extensión del documento',
            'tamanoGdExpedienteDocumento' => 'Tamaño expediente documento',
            'idGdTrdTipoDocumental' => 'Id tipo documental',
            'isPublicoGdExpedienteDocumento' => 'Es público el documento del expediente',
            'idUser' => 'Id usuario',
            'estadoGdExpedienteDocumento' => 'Estado',
            'creacionGdExpedienteDocumento' => 'Fecha creación del documento',
            'nombreGdExpedienteDocumento' => 'Nombre expediente documento',
            'observacionGdExpedienteDocumento' => 'Observación expediente documento',
            'fechaDocGdExpedienteDocumento' => 'Fecha del documento cargado',
        ];
    }

    /**
     * Gets query for [[IdGdExpediente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpediente()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTipoDocumental()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }


    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

}
