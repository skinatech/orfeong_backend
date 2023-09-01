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
 * This is the model class for table "gaHistoricoPrestamo".
 *
 * @property int $idGaHistoricoPrestamo Id del préstamo histórico
 * @property int $idGaPrestamo Id del préstamo documental
 * @property int $idUser
 * @property int $idGdTrdDependencia
 * @property string $fechaGaHistoricoPrestamo Fecha histórica
 * @property string $observacionGaHistoricoPrestamo Observación del préstamo
 * @property int $estadoGaHistoricoPrestamo Estado histórico
 * @property string $creacionGaHistoricoPrestamo Fecha de creación del registro
 *
 * @property GaPrestamos $idGaPrestamo0
 * @property User $idUser0
 * @property GdTrdDependencias $idGdTrdDependencia0
 */
class GaHistoricoPrestamo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaHistoricoPrestamo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGaPrestamo', 'idUser', 'idGdTrdDependencia', 'fechaGaHistoricoPrestamo', 'observacionGaHistoricoPrestamo'], 'required'],
            [['idGaPrestamo', 'idUser', 'idGdTrdDependencia', 'estadoGaHistoricoPrestamo'], 'integer'],
            [['fechaGaHistoricoPrestamo', 'creacionGaHistoricoPrestamo'], 'safe'],
            [['observacionGaHistoricoPrestamo'], 'string', 'max' => 500],
            [['idGaPrestamo'], 'exist', 'skipOnError' => true, 'targetClass' => GaPrestamos::className(), 'targetAttribute' => ['idGaPrestamo' => 'idGaPrestamo']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaHistoricoPrestamo' => 'Id del préstamo histórico',
            'idGaPrestamo' => 'Id del préstamo documental',
            'fechaGaHistoricoPrestamo' => 'Fecha histórica',
            'observacionGaHistoricoPrestamo' => 'Observación del préstamo',
            'estadoGaHistoricoPrestamo' => 'Estado histórico',
            'creacionGaHistoricoPrestamo' => 'Fecha de creación del registro',
            'idUser' => 'Usuario',
            'idGdTrdDependencia' => 'Dependencia',
        ];
    }

    /**
     * Gets query for [[IdGaPrestamo0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGaPrestamo0()
    {
        return $this->hasOne(GaPrestamos::className(), ['idGaPrestamo' => 'idGaPrestamo']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[IdGdTrdDependencia0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdDependencia0()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }
}
