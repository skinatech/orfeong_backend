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
 * This is the model class for table "radiInformados".
 *
 * @property int $idRadiInformado Número único que identifica la radiInformados
 * @property int $idUser Identificador único para la tabla radiRadicados
 * @property int $idRadiRadicado Identificador único de usuario que es informado
 * @property int $estadoRadiInformado 0 Inactivo - 10 Activo
 * @property string $creacionRadiInformado Fecha de creación
 *
 * @property User $user
 * @property RadiRadicados $radiRadicado
 */
class RadiInformados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiInformados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'idRadiRadicado', 'estadoRadiInformado'], 'integer'],
            [['creacionRadiInformado'], 'safe'],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiInformado' => 'Id radicado informado',
            'idUser' => 'Id user',
            'idRadiRadicado' => 'Id radicado',
            'estadoRadiInformado' => 'Estado radicado informado',
            'creacionRadiInformado' => 'Fecha creación del radicado informado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }
}
