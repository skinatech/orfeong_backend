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
 * This is the model class for table "cgTipoRadicadoDocumental".
 *
 * @property int $idCgTipoRadicadoDocumental Id relación tipo radicado y documental
 * @property int $idCgTipoRadicado Id tipo radicado
 * @property int $idGdTrdTipoDocumental Id tipo documental
 * @property int $estadoCgTipoRadicadoDocumental Estado
 * @property string $creacionCgTipoRadicadoDocumental Fecha creación
 *
 * @property GdTrdTiposDocumentales $idGdTrdTipoDocumental0
 * @property CgTiposRadicados $idCgTipoRadicado0
 */
class CgTipoRadicadoDocumental extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTipoRadicadoDocumental';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgTipoRadicado', 'idGdTrdTipoDocumental'], 'required'],
            [['idCgTipoRadicado', 'idGdTrdTipoDocumental', 'estadoCgTipoRadicadoDocumental'], 'integer'],
            [['creacionCgTipoRadicadoDocumental'], 'safe'],
            [['idGdTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idGdTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTipoRadicadoDocumental' => 'Id relación tipo radicado y documental',
            'idCgTipoRadicado' => 'Id tipo radicado',
            'idGdTrdTipoDocumental' => 'Id tipo documental',
            'estadoCgTipoRadicadoDocumental' => 'Estado',
            'creacionCgTipoRadicadoDocumental' => 'Fecha creación',
        ];
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
     * Gets query for [[IdCgTipoRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgTipoRadicado0()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}
