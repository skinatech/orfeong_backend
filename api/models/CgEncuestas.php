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
 * This is the model class for table "cgEncuestas".
 *
 * @property int $idCgEncuesta Identificador único de la tabla cgEncuestas
 * @property string $nombreCgEncuesta Nombre de la encuesta
 * @property string $creacionCgEncuesta Fecha de creación la encuesta
 * @property int $estadoCgEncuesta Estado de la encuesta del expediente 10 activo 0 Inactivo
 * @property int $idUserCreador Identificador de usuario creador
 *
 * @property CgEncuestaPreguntas[] $cgEncuestaPreguntas
 * @property User $idUserCreador0
 */
class CgEncuestas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgEncuestas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgEncuesta', 'idUserCreador'], 'required'],
            [['creacionCgEncuesta'], 'safe'],
            [['estadoCgEncuesta', 'idUserCreador'], 'integer'],
            [['nombreCgEncuesta'], 'string', 'max' => 80],
            [['idUserCreador'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUserCreador' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgEncuesta' => 'Id encuesta',
            'nombreCgEncuesta' => 'Nombre encuesta',
            'creacionCgEncuesta' => 'Fecha creación',
            'estadoCgEncuesta' => 'Estado',
            'idUserCreador' => 'Id user creador',
        ];
    }

    /**
     * Gets query for [[CgEncuestaPreguntas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgEncuestaPreguntas()
    {
        return $this->hasMany(CgEncuestaPreguntas::className(), ['idCgEncuesta' => 'idCgEncuesta']);
    }

    /**
     * Gets query for [[IdUserCreador]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserCreador()
    {
        return $this->hasOne(User::className(), ['id' => 'idUserCreador']);
    }
}
