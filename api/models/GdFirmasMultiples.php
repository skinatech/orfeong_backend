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
 * This is the model class for table "gdFirmasMultiples".
 *
 * @property int $idGdFirmaMultiple
 * @property int $idradiDocPrincipal Id del documento a firmar
 * @property string $firmaGdFirmaMultiple Identificador {firma#}
 * @property int|null $userGdFirmaMultiple Usuario firmante
 * @property int $estadoGdFirmaMultiple Estado
 * @property string $creacionGdFirmaMultiple Creación 
 *
 * @property Radidocumentosprincipales $idradiDocPrincipal0
 */
class GdFirmasMultiples extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdFirmasMultiples';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idradiDocPrincipal', 'firmaGdFirmaMultiple'], 'required'],
            [['idradiDocPrincipal', 'userGdFirmaMultiple', 'estadoGdFirmaMultiple'], 'integer'],
            [['creacionGdFirmaMultiple'], 'safe'],
            [['firmaGdFirmaMultiple'], 'string', 'max' => 80],
            [['idradiDocPrincipal'], 'exist', 'skipOnError' => true, 'targetClass' => Radidocumentosprincipales::className(), 'targetAttribute' => ['idradiDocPrincipal' => 'idradiDocumentoPrincipal']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdFirmaMultiple' => 'Id Gd Firma Multiple',
            'idradiDocPrincipal' => 'Idradi Doc Principal',
            'firmaGdFirmaMultiple' => 'Firma Gd Firma Multiple',
            'userGdFirmaMultiple' => 'User Gd Firma Multiple',
            'estadoGdFirmaMultiple' => 'Estado Gd Firma Multiple',
            'creacionGdFirmaMultiple' => 'Creacion Gd Firma Multiple',
        ];
    }

    /**
     * Gets query for [[IdradiDocPrincipal0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdradiDocPrincipal()
    {
        return $this->hasOne(Radidocumentosprincipales::className(), ['idradiDocumentoPrincipal' => 'idradiDocPrincipal']);
    }
}
