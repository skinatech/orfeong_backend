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
 * This is the model class for table "clienteEncuestas".
 *
 * @property int $idClienteEncuesta
 * @property int $idCgEncuesta Identificador único de la encuesta
 * @property string $tokenClienteEncuesta
 * @property string|null $fechaClienteEncuesta Fecha en que el ciudadano realizo la encuesta
 * @property int $estadoClienteEncuesta
 * @property string $creacionClienteEncuesta
 * @property string $email email del cliente
 *
 * @property CgEncuestas $idCgEncuesta0
 * @property EncuestaCalificaciones[] $encuestaCalificaciones
 */
class ClienteEncuestas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'clienteEncuestas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idClienteEncuesta', 'idCgEncuesta', 'estadoClienteEncuesta'], 'integer'],
            [['idCgEncuesta', 'tokenClienteEncuesta', 'email'], 'required'],
            [['tokenClienteEncuesta', 'email'], 'string', 'max' => 255],
            [['fechaClienteEncuesta', 'creacionClienteEncuesta'], 'safe'],
            [['idClienteEncuesta'], 'unique'],
            [['idCgEncuesta'], 'exist', 'skipOnError' => true, 'targetClass' => CgEncuestas::className(), 'targetAttribute' => ['idCgEncuesta' => 'idCgEncuesta']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idClienteEncuesta' => 'Id Cliente Encuesta',
            'idCgEncuesta' => 'Id Cg Encuesta',
            'tokenClienteEncuesta' => 'Token Cliente Encuesta',
            'fechaClienteEncuesta' => 'Fecha Cliente Encuesta',
            'estadoClienteEncuesta' => 'Estado Cliente Encuesta',
            'creacionClienteEncuesta' => 'Creacion Cliente Encuesta',
            'email' => 'Email',
        ];
    }

    /**
     * Gets query for [[IdCgEncuesta0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgEncuesta0()
    {
        return $this->hasOne(CgEncuestas::className(), ['idCgEncuesta' => 'idCgEncuesta']);
    }

    /**
     * Gets query for [[EncuestaCalificaciones]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEncuestaCalificaciones()
    {
        return $this->hasMany(EncuestaCalificaciones::className(), ['idClienteEncuesta' => 'idClienteEncuesta']);
    }
}
