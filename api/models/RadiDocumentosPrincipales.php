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
 * This is the model class for table "RadiDocumentosPrincipales".
 *
 * @property int $idradiDocumentoPrincipal
 * @property int $idRadiRadicado identificador de la tabla radiRadicados
 * @property int $idUser Identificador del usuario que cargo el documento
 * @property string $nombreRadiDocumentoPrincipal Nombre que se le da al documento que se carga
 * @property string $rutaRadiDocumentoPrincipal Ruta donde se encuentra el documento que se cargo para el radicado
 * @property string $extensionRadiDocumentoPrincipal Guarda la extensión del documento que se esta cargando    
 * @property int $imagenPrincipalRadiDocumento
 * @property int $estadoRadiDocumentoPrincipal Estado que se le aplica al documento defecto activo = 10
 * @property string $creacionRadiDocumentoPrincipal Fecha en la que se crea el documento
 * @property string $tamanoRadiDocumentoPrincipal Tamaño del archivo del documento 
 * @property int|null $publicoPagina Público página 10 y 0 no público 
 * @property int|null $idRadiRespuesta Id del radicado de respuesta
 *
 * @property RadiRadicados $idRadiRadicado
 * @property User $idUser
 * @property RadiRadicados $idRadiRespuesta0
 */
class RadiDocumentosPrincipales extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiDocumentosPrincipales';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idUser', 'nombreRadiDocumentoPrincipal', 'rutaRadiDocumentoPrincipal', 'extensionRadiDocumentoPrincipal', 'tamanoRadiDocumentoPrincipal'], 'required'],
            [['idRadiRadicado', 'idUser', 'imagenPrincipalRadiDocumento', 'estadoRadiDocumentoPrincipal', 'publicoPagina', 'idRadiRespuesta', 'fechaEpochCreacionArchivo', 'paginas'], 'integer'],
            [['creacionRadiDocumentoPrincipal'], 'safe'],
            [['nombreRadiDocumentoPrincipal'], 'string', 'max' => 80],
            [['rutaRadiDocumentoPrincipal'], 'string', 'max' => 255],
            [['extensionRadiDocumentoPrincipal', 'tamanoRadiDocumentoPrincipal'], 'string', 'max' => 20],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idRadiRespuesta'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRespuesta' => 'idRadiRadicado']], 
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idradiDocumentoPrincipal' => 'Id documento principal',
            'idRadiRadicado' => 'Id radicado',
            'idUser' => 'Usuario creador',
            'nombreRadiDocumentoPrincipal' => 'Nombre',
            'rutaRadiDocumentoPrincipal' => 'Ruta',
            'extensionRadiDocumentoPrincipal' => 'Extensión',
            'imagenPrincipalRadiDocumento' => 'Imagen principal',
            'estadoRadiDocumentoPrincipal' => 'Estado',
            'creacionRadiDocumentoPrincipal' => 'Fecha de creación',
            'tamanoRadiDocumentoPrincipal' => 'Tamaño documento principal',
            'publicoPagina' => 'Público página',
            'idRadiRespuesta' => 'Id radicado de respuesta',
            'fechaEpochCreacionArchivo' => 'Fecha de creacion del archivo',
            'paginas' => 'Numero de paginas del archivo'
            
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
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /** 
    * Gets query for [[IdRadiRespuesta0]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getIdRadiRespuesta0() 
    { 
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRespuesta']); 
    } 
}
