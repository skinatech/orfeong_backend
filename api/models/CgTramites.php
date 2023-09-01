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
 * This is the model class for table "cgTramites".
 *
 * @property int $idCgTramite
 * @property string $nombreCgTramite
 * @property int $estadoCgTramite
 * @property string $creacionCgTramite
 * @property int $tiempoRespuestaCgTramite
 * @property int $mostrarCgTramite
 *
 * @property RadiRadicados[] $radiRadicados
 */
class CgTramites extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTramites';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgTramite'], 'required'],
            [['estadoCgTramite'], 'integer'],
            [['creacionCgTramite'], 'safe'],
            [['nombreCgTramite'], 'string', 'max' => 200],
            [['tiempoRespuestaCgTramite'], 'integer'],
            [['mostrarCgTramite'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTramite' => 'Id trámite',
            'nombreCgTramite' => 'Nombre trámite',
            'estadoCgTramite' => 'Estado trámite',
            'creacionCgTramite' => 'Fecha creación trámite',
            'tiempoRespuestaCgTramite' => 'Tiempo de respuesta trámite',
            'mostrarCgTramite' => 'Mostrar el trámite',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicados()
    {
        return $this->hasMany(RadiRadicados::className(), ['idTramites' => 'idCgTramite']);
    }
}
