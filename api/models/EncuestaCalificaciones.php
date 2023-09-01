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
 * This is the model class for table "encuestaCalificaciones".
 *
 * @property int $idEncuestaCalificaciones Identificador único del registro
 * @property int $idCgEncuestaPregunta Identificador único de la pregunta de una encuesta
 * @property int $idClienteEncuesta Identificador único de la encuesta de un cliente
 * @property int $calificacionEncuestaPregunta 1: Deficiente - 2 : Regular - 3: Buena - 4: Excelente
 * @property int $estadoEncuestaPregunta 0 : Inactivo - 10 : Activo
 * @property string $creacionEncuestaPregunta Fecha de creación del registro
 *
 * @property CgEncuestaPreguntas $idCgEncuestaPregunta0
 * @property ClienteEncuestas $idClienteEncuesta0
 */
class EncuestaCalificaciones extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'encuestaCalificaciones';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgEncuestaPregunta', 'idClienteEncuesta', 'calificacionEncuestaPregunta'], 'required'],
            [['idCgEncuestaPregunta', 'idClienteEncuesta', 'calificacionEncuestaPregunta', 'estadoEncuestaPregunta'], 'integer'],
            [['creacionEncuestaPregunta'], 'safe'],
            [['idCgEncuestaPregunta'], 'exist', 'skipOnError' => true, 'targetClass' => CgEncuestaPreguntas::className(), 'targetAttribute' => ['idCgEncuestaPregunta' => 'idCgEncuestaPregunta']],
            [['idClienteEncuesta'], 'exist', 'skipOnError' => true, 'targetClass' => ClienteEncuestas::className(), 'targetAttribute' => ['idClienteEncuesta' => 'idClienteEncuesta']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idEncuestaCalificaciones' => 'Id Encuesta Calificaciones',
            'idCgEncuestaPregunta' => 'Id Cg Encuesta Pregunta',
            'idClienteEncuesta' => 'Id Cliente Encuesta',
            'calificacionEncuestaPregunta' => 'Calificacion Encuesta Pregunta',
            'estadoEncuestaPregunta' => 'Estado Encuesta Pregunta',
            'creacionEncuestaPregunta' => 'Creacion Encuesta Pregunta',
        ];
    }

    /**
     * Gets query for [[IdCgEncuestaPregunta0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgEncuestaPregunta0()
    {
        return $this->hasOne(CgEncuestaPreguntas::className(), ['idCgEncuestaPregunta' => 'idCgEncuestaPregunta']);
    }

    /**
     * Gets query for [[IdClienteEncuesta0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdClienteEncuesta0()
    {
        return $this->hasOne(ClienteEncuestas::className(), ['idClienteEncuesta' => 'idClienteEncuesta']);
    }
}
