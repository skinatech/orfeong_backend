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
 * This is the model class for table "radiRadicadosAsociados".
 *
 * @property int $idRadicadoAsociado Numero unico de la tabla
 *
 * @property RadiRadicados $idRadiCreado0
 * @property int $idRadiAsociado Ids de los radicados asociados al radicado creado
 * @property int $idRadiCreado Id del radicado creado
 * @property int $estadoRadicadoAsociado Estado del radicado asociado
 * @property string $creacionRadicadoAsociado Creacion del radicado asociado
 *
 * @property RadiRadicados $idRadiAsociado0
 * @property RadiRadicados $idRadiCreado0 
 */
class RadiRadicadosAsociados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicadosAsociados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiAsociado', 'idRadiCreado'], 'required'],
            [['idRadiAsociado', 'idRadiCreado', 'estadoRadicadoAsociado'], 'integer'],
            [['creacionRadicadoAsociado'], 'safe'],
            [['idRadiCreado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiCreado' => 'idRadiRadicado']],
            [['idRadiAsociado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiAsociado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadicadoAsociado' => 'Numero unico de la tabla',
            'idRadiAsociado' => 'Id de los radicados asociados al radicado creado',
            'idRadiCreado' => 'Id del radicado creado',
            'estadoRadicadoAsociado' => 'Estado del radicado asociado',
            'creacionRadicadoAsociado' => 'Creacion del radicado asociado',
        ];
    }

    /**
     * Gets query for [[IdRadiAsociado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiAsociado0()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiAsociado']);
    }

    /** 
     * Gets query for [[IdRadiCreado0]]. 
     * 
     * @return \yii\db\ActiveQuery 
     */ 
    public function getIdRadiCreado0() 
    { 
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiCreado']); 
    }
}
