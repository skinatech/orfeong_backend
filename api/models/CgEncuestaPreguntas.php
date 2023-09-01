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
 * This is the model class for table "cgEncuestaPreguntas".
 *
 * @property int $idCgEncuestaPregunta Identificador único de la tabla cgEncuestaPreguntas
 * @property int $idCgEncuesta Identificador único de la tabla cgEncuestas
 * @property string $preguntaCgEncuestaPregunta Pregunta de la encuesta
 * @property string $creacionEncuestaPregunta Estado de la pregunta de la encuesta 10 activo 0 Inactivo
 * @property int $estadoEncuestaPregunta Fecha de creación de la pregunta de la encuesta
 *
 * @property CgEncuestas $idCgEncuesta0
 */
class CgEncuestaPreguntas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEncuestaPreguntas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgEncuesta', 'preguntaCgEncuestaPregunta'], 'required'],
            [['idCgEncuesta', 'estadoEncuestaPregunta'], 'integer'],
            [['preguntaCgEncuestaPregunta'], 'string'],
            [['creacionEncuestaPregunta'], 'safe'],
            [['idCgEncuesta'], 'exist', 'skipOnError' => true, 'targetClass' => CgEncuestas::className(), 'targetAttribute' => ['idCgEncuesta' => 'idCgEncuesta']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEncuestaPregunta' => 'Id Cg Encuesta Pregunta',
            'idCgEncuesta' => 'Id Cg Encuesta',
            'preguntaCgEncuestaPregunta' => 'Pregunta Cg Encuesta Pregunta',
            'creacionEncuestaPregunta' => 'Fecha Creacion Encuesta Pregunta',
            'estadoEncuestaPregunta' => 'Estado Creacion Encuesta Pregunta',
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
}
