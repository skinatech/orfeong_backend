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
 * This is the model class for table "radiAgendaRadicados".
 *
 * @property int $idRadiAgendaRadicados
 * @property int $idRadiRadicado id radicado
 * @property string $fechaProgramadaRadiAgenda fecha programada para el evento del radicado
 * @property string $descripcionRadiAgenda descripcion del evento programado
 * @property int $estadoRadiAgenda 0 // inactiva - 10 // Activa
 *
 * @property RadiRadicados $radiRadicado
 */
class RadiAgendaRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiAgendaRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'fechaProgramadaRadiAgenda', 'descripcionRadiAgenda', 'estadoRadiAgenda'], 'required'],
            [['idRadiRadicado', 'estadoRadiAgenda'], 'integer'],
            [['fechaProgramadaRadiAgenda'], 'safe'],
            [['descripcionRadiAgenda'], 'string', 'max' => 500],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiAgendaRadicados' => 'Id Radi Agenda Radicados',
            'idRadiRadicado' => 'Id Radi Radicado',
            'fechaProgramadaRadiAgenda' => 'Fecha Programada Radi Agenda',
            'descripcionRadiAgenda' => 'Descripcion Radi Agenda',
            'estadoRadiAgenda' => 'Estado Radi Agenda',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }
}
