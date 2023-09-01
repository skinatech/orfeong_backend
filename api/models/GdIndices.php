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
 * This is the model class for table "gdIndices".
 *
 * @property int $idGdIndice Identificador único en el sisitema
 * @property string $indiceContenidoGdIndice Indice del contenido
 * @property int|null $idGdExpedienteInclusion Identificador único de la tabla expedienteInclusion
 * @property string $valorHuellaGdIndice Valor huella
 * @property string $funcionResumenGdIndice Función resumen
 * @property int $ordenDocumentoGdIndice Ordel del documento en el indice
 * @property int|null $paginaInicioGdIndice Página inicial
 * @property int|null $paginaFinalGdIndice Página final
 * @property string $tamanoGdIndice Tamaño del documento
 * @property string $rutaXmlGdIndice Ruta en donde se carga el XML del indice
 * @property int $estadoGdIndice Estado de gdIndice 10: Activo | 0: Inactivo
 * @property string $CreacionGdIndice Creacion de gdIndice
 * @property string $nombreDocumentoGdIndice Nombre del documento
 * @property int $idGdTrdTipoDocumental Identificador de Gd TRd Tipo Documental
 * @property string $creacionDocumentoGdIndice Fecha de creación del documento
 * @property string $formatoDocumentoGdIndice Extenxión del documento
 * @property string $descripcionGdIndice Descripción del documento
 * @property string $usuarioGdIndice Usuario que anexó el documento
 * @property int|null $idGdExpedienteDocumento Identificador de la tabla gdExpedienteDocumento
 * @property int|null $origenGdIndice Número correspondiente del tipo de documento
 * @property int|null $idGdReferenciaCruzada Identificador de la tabla gdReferenciaCruzada
 *
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 * @property GdExpedientesInclusion $idGdExpedienteInclusion0
 * @property GdReferenciasCruzadas $idGdReferenciaCruzada0
 */
class GdIndices extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdIndices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['indiceContenidoGdIndice', 'valorHuellaGdIndice', 'funcionResumenGdIndice', 'ordenDocumentoGdIndice', 'tamanoGdIndice', 'rutaXmlGdIndice', 'nombreDocumentoGdIndice', 'idGdTrdTipoDocumental', 'creacionDocumentoGdIndice', 'formatoDocumentoGdIndice', 'descripcionGdIndice', 'usuarioGdIndice'], 'required'],
            [['idGdExpedienteInclusion', 'ordenDocumentoGdIndice', 'paginaInicioGdIndice', 'paginaFinalGdIndice', 'estadoGdIndice', 'idGdTrdTipoDocumental', 'idGdExpedienteDocumento', 'origenGdIndice', 'idGdReferenciaCruzada'], 'integer'],
            [['CreacionGdIndice', 'creacionDocumentoGdIndice', 'fechaFirmaGdIndice'], 'safe'],
            [['funcionResumenGdIndice', 'tamanoGdIndice', 'formatoDocumentoGdIndice'], 'string', 'max' => 20],
            [['indiceContenidoGdIndice'], 'string', 'max' => 80],
            [['nombreDocumentoGdIndice', 'usuarioGdIndice'], 'string', 'max' => 255],
            [['valorHuellaGdIndice'], 'string', 'max' => 2000],
            [['rutaXmlGdIndice', 'descripcionGdIndice'], 'string', 'max' => 500],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idGdExpedienteInclusion'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientesInclusion::className(), 'targetAttribute' => ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']],
            [['idGdReferenciaCruzada'], 'exist', 'skipOnError' => true, 'targetClass' => GdReferenciasCruzadas::className(), 'targetAttribute' => ['idGdReferenciaCruzada' => 'idGdReferenciaCruzada']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdIndice' => 'Id índice',
            'indiceContenidoGdIndice' => 'Contenido del índice',
            'idGdExpedienteInclusion' => 'Id expediente inclusión',
            'valorHuellaGdIndice' => 'Valor huella',
            'funcionResumenGdIndice' => 'Función resumen',
            'ordenDocumentoGdIndice' => 'Orden documento',
            'paginaInicioGdIndice' => 'Página inicio',
            'paginaFinalGdIndice' => 'Página final',
            'tamanoGdIndice' => 'Tamaño índice',
            'rutaXmlGdIndice' => 'Ruta xml',
            'estadoGdIndice' => 'Estado',
            'CreacionGdIndice' => 'Fecha creación índice',
            'nombreDocumentoGdIndice' => 'Nombre Documento',
            'idGdTrdTipoDocumental' => 'Id tipo documental',
            'creacionDocumentoGdIndice' => 'Fecha creación documento',
            'formatoDocumentoGdIndice' => 'Formato documento',
            'descripcionGdIndice' => 'Descripción',
            'usuarioGdIndice' => 'Usuario',
            'idGdExpedienteDocumento' => 'Id expediente documento',
            'origenGdIndice' => 'Número correspondiente del tipo de documento',
            'fechaFirmaGdIndice' => 'Fecha de la firma',
            'idGdReferenciaCruzada' => 'Id referencia cruzada',
        ];
    }

    /**
     * Gets query for [[expedienteInclusion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpedienteInclusion()
    {
        return $this->hasOne(GdExpedientesInclusion::className(), ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']);
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
     * Gets query for [[idGdExpedienteDocumento]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpedienteDocumento()
    {
        return $this->hasOne(GdExpedienteDocumentos::className(), ['idGdExpedienteDocumento' => 'idGdExpedienteDocumento']);
    }

    /**
     * Gets query for [[IdGdReferenciaCruzada0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdReferenciaCruzada0()
    {
        return $this->hasOne(GdReferenciasCruzadas::className(), ['idGdReferenciaCruzada' => 'idGdReferenciaCruzada']);
    }
}
