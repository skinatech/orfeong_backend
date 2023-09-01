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
 * This is the model class for table "gdTrdTiposDocumentalesTmp".
 *
 * @property int $idGdTrdTipoDocumentalTmp Numero único de tipo documental
 * @property string $nombreTipoDocumentalTmp nombreTipoDocumental
 * @property int|null $diasTramiteTipoDocumentalTmp Nombre único del tipo documental
 * @property int $estadoTipoDocumentalTmp Estado 0 Inactivo 10 Activo
 * @property string $creacionTipoDocumentalTmp Creación del tipo documental
 *
 * @property GdTrdTmp[] $gdTrdTmps
 */
class GdTrdTiposDocumentalesTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdTiposDocumentalesTmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreTipoDocumentalTmp'], 'required'],
            [['diasTramiteTipoDocumentalTmp', 'estadoTipoDocumentalTmp'], 'integer'],
            [['creacionTipoDocumentalTmp'], 'safe'],
            [['nombreTipoDocumentalTmp'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdTipoDocumentalTmp' => 'Id Trd tipo documental temporal',
            'nombreTipoDocumentalTmp' => 'Nombre tipo documental temporal',
            'diasTramiteTipoDocumentalTmp' => 'Días Trámite tipo documental temporal',
            'estadoTipoDocumentalTmp' => 'Estado tipo documental temporal',
            'creacionTipoDocumentalTmp' => 'Creación tipo documental temporal',
        ];
    }

    /**
     * Gets query for [[GdTrdTmps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTmps()
    {
        return $this->hasMany(GdTrdTmp::className(), ['idGdTrdTipoDocumentalTmp' => 'idGdTrdTipoDocumentalTmp']);
    }
}
