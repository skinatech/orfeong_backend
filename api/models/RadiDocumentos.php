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
 * This is the model class for table "radiDocumentos".
 *
 * @property int $idRadiDocumento
 * @property int $numeroRadiDocumento Este campo hace referencia a la cantidad de documentos que hay para un radicado.
 * @property string $nombreRadiDocumento Nombre que se le da al documento que se carga al radicado este nombre debe ser el número de radicado + la columna numeroRadiDocumento
 * @property string $rutaRadiDocumento Ruta donde se encuentra el documento que se cargo para el radicado
 * @property string $extencionRadiDocumento Guarda la extención del documento que se esta cargando
 * @property string $descripcionRadiDocumento Descripción u obsevación de los que es el documento
 * @property int $estadoRadiDocumento Estado que se le aplica al documento
 * @property string $creacionRadiDocumento Fecha en la que se crea el documento
 * @property int $idRadiRadicado
 * @property int $idGdTrdTipoDocumental
 * @property int $idUser Identificador del usuario que cargo el documento
 * @property int $isPublicoRadiDocumento Indica si el anexo está marcado como público 0: No, 10: Si 
 * @property string $tamanoRadiDocumento Tamaño del archivo del documento 
 * @property int|null $publicoPagina Público página 10 y 0 no público
 *
 * @property RadiRadicados $idRadiRadicado0
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 * @property User $idUser0
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental1
 * @property UserDetalles $idUser1
 */
class RadiDocumentos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiDocumentos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['numeroRadiDocumento', 'nombreRadiDocumento', 'rutaRadiDocumento', 'extencionRadiDocumento', 'descripcionRadiDocumento', 'idRadiRadicado', 'idGdTrdTipoDocumental', 'idUser', 'tamanoRadiDocumento'], 'required'],
            [['numeroRadiDocumento', 'estadoRadiDocumento', 'idRadiRadicado', 'idGdTrdTipoDocumental', 'idUser', 'isPublicoRadiDocumento', 'publicoPagina'], 'integer'],
            [['creacionRadiDocumento'], 'safe'],
            [['nombreRadiDocumento', 'rutaRadiDocumento'], 'string', 'max' => 255],
            [['extencionRadiDocumento', 'tamanoRadiDocumento'], 'string', 'max' => 20],
            [['descripcionRadiDocumento'], 'string', 'max' => 500],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => UserDetalles::className(), 'targetAttribute' => ['idUser' => 'idUser']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiDocumento' => 'Id documento',
            'numeroRadiDocumento' => 'Número documento',
            'nombreRadiDocumento' => 'Nombre documento',
            'rutaRadiDocumento' => 'Ruta documento',
            'extencionRadiDocumento' => 'Extensión documento',
            'descripcionRadiDocumento' => 'Descripción documento',
            'estadoRadiDocumento' => 'Estado documento',
            'creacionRadiDocumento' => 'Fecha creación documento',
            'idRadiRadicado' => 'Id radicado',
            'idGdTrdTipoDocumental' => 'Id tipo documental',
            'idUser' => 'Id usuario',
            'isPublicoRadiDocumento' => 'Es público el documento',
            'tamanoRadiDocumento' => 'Tamaño documento',
            'publicoPagina' => 'Público página',
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
     * Gets query for [[IdGdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdTipoDocumental0()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser1()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[IdGdTrdTipoDocumental1]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdTipoDocumental1()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']);
    }

    /**
     * Gets query for [[IdUser1]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(UserDetalles::className(), ['idUser' => 'idUser']);
    }
}
