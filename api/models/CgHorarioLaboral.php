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
 * This is the model class for table "cgHorarioLaboral".
 *
 * @property int $idCgHorarioLaboral
 * @property int $diaInicioCgHorarioLaboral Representación numérica del día de la semana 0 (para domingo) hasta 6 (para sábado)
 * @property int $diaFinCgHorarioLaboral Representación numérica del día de la semana 0 (para domingo) hasta 6 (para sábado)
 * @property string $horaInicioCgHorarioLaboral Formato de 12 horas de una hora sin ceros iniciales
 * @property string $horaFinCgHorarioLaboral Formato de 12 horas de una hora sin ceros iniciales
 * @property int $estadoCgHorarioLaboral estado 10 para habilitado 0 para inhabilitado
 * @property string $fechaCgHorarioLaboral fecha creación
 */
class CgHorarioLaboral extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgHorarioLaboral';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['diaInicioCgHorarioLaboral', 'diaFinCgHorarioLaboral', 'horaInicioCgHorarioLaboral', 'horaFinCgHorarioLaboral', 'estadoCgHorarioLaboral', 'fechaCgHorarioLaboral'], 'required'],
            [['diaInicioCgHorarioLaboral', 'diaFinCgHorarioLaboral', 'estadoCgHorarioLaboral'], 'integer'],
            [['horaInicioCgHorarioLaboral', 'horaFinCgHorarioLaboral' ], 'string', 'max' => 10 ],
            [['fechaCgHorarioLaboral'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgHorarioLaboral' => 'Id horario laboral',
            'diaInicioCgHorarioLaboral' => 'Día de inicio del horario laboral',
            'diaFinCgHorarioLaboral' => 'Día final del horario laboral',
            'horaInicioCgHorarioLaboral' => 'Horario de inicio',
            'horaFinCgHorarioLaboral' => 'Horario de finalización',
            'estadoCgHorarioLaboral' => 'Estado',
            'fechaCgHorarioLaboral' => 'Fecha creación',
        ];
    }
}
