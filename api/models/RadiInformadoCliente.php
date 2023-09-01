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
 * This is the model class for table "radiInformadoCliente".
 *
 * @property int $idRadiInformadoCliente Id de radi informado cliente
 * @property int $idCliente Id cliente
 * @property int $idRadiRadicado Id radicado
 * @property int $estadoRadiInformadoCliente Estado informado cliente
 * @property string $creacionRadiInformadoCliente Fecha creación
 *
 * @property Clientes $idCliente0
 * @property RadiRadicados $idRadiRadicado0
 */
class RadiInformadoCliente extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiInformadoCliente';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCliente', 'idRadiRadicado'], 'required'],
            [['idCliente', 'idRadiRadicado', 'estadoRadiInformadoCliente'], 'integer'],
            [['creacionRadiInformadoCliente'], 'safe'],
            [['idCliente'], 'exist', 'skipOnError' => true, 'targetClass' => Clientes::className(), 'targetAttribute' => ['idCliente' => 'idCliente']],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiInformadoCliente' => 'Id de radi informado cliente',
            'idCliente' => 'Id cliente',
            'idRadiRadicado' => 'Id radicado',
            'estadoRadiInformadoCliente' => 'Estado informado cliente',
            'creacionRadiInformadoCliente' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[IdCliente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCliente0()
    {
        return $this->hasOne(Clientes::className(), ['idCliente' => 'idCliente']);
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
}
