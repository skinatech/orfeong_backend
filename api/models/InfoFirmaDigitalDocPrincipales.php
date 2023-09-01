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
 * @property int $idFirmaDigital int id de la firma digital
 * @property int $firmante Persona o entidad que firmo el documento digitalmente
 * @property int $fechaFirmado Fecha en la que se realizo la firma digital
 * @property string $fechaVencimiento Fecha en la que se vence la firma digital
 * @property string $idUser Usuario que cargo el archivo con las firmas digitales
 * @property string $idradiDocumentoPrincipal Id del documento proncipal al cual se le asocia la firma digital 

 *
 * @property RadiDocumentosPrincipales $idradiDocumentoPrincipal
 * @property User $idUser
 */
class InfoFirmaDigitalDocPrincipales extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'infoFirmaDigitalDocPrincipales';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['firmante', 'fechaFirmado', 'fechaVencimiento', 'idUser', 'idradiDocumentoPrincipal'], 'required'],
            [['idUser', 'idradiDocumentoPrincipal'], 'integer'],
            [['fechaFirmado','fechaVencimiento'], 'safe'],
            [['firmante'], 'string', 'max' => 100],
            [['idradiDocumentoPrincipal'], 'exist', 'skipOnError' => true, 'targetClass' => RadiDocumentosPrincipales::className(), 'targetAttribute' => ['idradiDocumentoPrincipal' => 'idradiDocumentoPrincipal']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idFirmaDigital' => 'Id firma digital',
            'firmante' => 'Firmante',
            'fechaFirmado' => 'Fecha firmado',
            'fechaVencimiento' => 'Fecha vencimineto',
            'idUser' => 'Usuario que carga el archivo',
            'idradiDocumentoPrincipal' => 'Id documento principal'
        ];
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiDocumentosPrincipales()
    {
        return $this->hasOne(RadiDocumentosPrincipales::className(), ['idradiDocumentoPrincipal' => 'idradiDocumentoPrincipal']);
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
