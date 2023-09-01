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
 * This is the model class for table "initCgConfigFirmas".
 *
 * @property int $idInitCgConfigFirma
 * @property string $valorInitCgConfigFirma Valor de lo que contendra dicho campo
 * @property int $estadoInitCgConfigFirma Estado de la configuración
 * @property string $creacionInitCgConfigFirma Fecha creción de la configuración firma
 * @property int|null $idInitCgEntidadFirma Relación con tabla initCgEntidadFirma
 * @property int|null $idInitCgParamFirma 	Relación con tabla initCgParamFirma
 */
class InitCgConfigFirmas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'initCgConfigFirmas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['valorInitCgConfigFirma'], 'required'],
            [['creacionInitCgConfigFirma'], 'safe'],
            [['estadoInitCgConfigFirma'], 'integer'],
            [['valorInitCgConfigFirma'], 'string', 'max' => 250],
            [['idInitCgEntidadFirma'], 'exist', 'skipOnError' => true, 'targetClass' => InitCgEntidadesFirma::className(), 'targetAttribute' => ['idInitCgEntidadFirma' => 'idInitCgEntidadFirma']],
            [['idInitCgParamFirma'], 'exist', 'skipOnError' => true, 'targetClass' => InitCgParamsFirma	::className(), 'targetAttribute' => ['idInitCgParamFirma' => 'idInitCgParamFirma']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idInitCgConfigFirma' => 'Id variable',
            'valorInitCgConfigFirma' => 'Valor de la variable',
            'estadoInitCgConfigFirma' => 'Estado',
            'creacionInitCgConfigFirma' => 'Creación radicado',
            'idInitCgEntidadFirma' => 'Entidad Certificadora',
            'idInitCgParamFirma' => 'Parametros'
        ];
    }

    /**
     * Gets query for [[InitCgParamsFirma0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInitCgParamsFirma0()
    {
        return $this->hasOne(InitCgParamsFirma::className(), ['idInitCgParamFirma' => 'idInitCgParamFirma']);
    }

    /**
     * Gets query for [[IdInitCgEntidadesFirma0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdInitCgEntidadesFirma0()
    {
        return $this->hasOne(InitCgEntidadesFirma	::className(), ['idInitCgEntidadFirma' => 'idInitCgEntidadFirma']);
    }
}
