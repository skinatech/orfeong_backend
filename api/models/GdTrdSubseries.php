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
 * This is the model class for table "gdTrdSubseries".
 *
 * @property int $idGdTrdSubserie Número único para identificar la subserie
 * @property string $nombreGdTrdSubserie Nombre de la subserie documental
 * @property string $codigoGdTrdSubserie Código asociado a la subserie documetal este va a sociado a la dependencia
 * @property int $tiempoGestionGdTrdSubserie Tiempo en años de gestión asignados a la subserie
 * @property int $tiempoCentralGdTrdSubserie Tiempo en años de central asignados a la subserie
 * @property int|null $pSoporteGdTrdSubserie Este campo guarda la información respecto al soporte de la subserie (papel)
 * @property int|null $eSoporteGdTrdSubserie Este campo guarda la información respecto al soporte de la subserie (electronico)
 * @property int|null $oSoporteGdTrdSubserie Este campo guarda la información respecto al soporte de la subserie (otro)
 * @property int|null $ctDisposicionFinalGdTrdSubserie
 * @property int|null $eDisposicionFinalGdTrdSubserie
 * @property int|null $sDisposicionFinalGdTrdSubserie
 * @property int|null $mDisposicionFinalGdTrdSubserie
 * @property string $procedimientoGdTrdSubserie Procedimiento correspondiente a la subserie
 * @property int $estadoGdTrdSubserie 0 Inactivo - 10 Activo
 * @property string $creacionGdTrdSubserie Este campo guarda la información respecto al soporte de la subserie (electronico)
 * @property string|null $normaGdTrdSubserie Guarda la norma que justifica la subserie
 * @property int $idGdTrdSerie Código que relaciona el id de la serie
 *
 * @property GdTrd[] $gdTrds
 */
class GdTrdSubseries extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdSubseries';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdSubserie', 'codigoGdTrdSubserie', 'tiempoGestionGdTrdSubserie', 'tiempoCentralGdTrdSubserie', 'procedimientoGdTrdSubserie', 'idGdTrdSerie'], 'required'],
            [['tiempoGestionGdTrdSubserie', 'tiempoCentralGdTrdSubserie', 'pSoporteGdTrdSubserie', 'eSoporteGdTrdSubserie', 'oSoporteGdTrdSubserie', 'ctDisposicionFinalGdTrdSubserie', 'eDisposicionFinalGdTrdSubserie', 'sDisposicionFinalGdTrdSubserie', 'mDisposicionFinalGdTrdSubserie', 'estadoGdTrdSubserie', 'idGdTrdSerie'], 'integer'],
            [['procedimientoGdTrdSubserie', 'normaGdTrdSubserie'], 'string'],
            [['creacionGdTrdSubserie'], 'safe'],
            [['nombreGdTrdSubserie'], 'string', 'max' => 255],
            [['codigoGdTrdSubserie'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdSubserie' => 'Id Trd subserie',
            'nombreGdTrdSubserie' => 'Nombre subserie',
            'codigoGdTrdSubserie' => 'Código subserie',
            'tiempoGestionGdTrdSubserie' => 'Tiempo gestión subserie',
            'tiempoCentralGdTrdSubserie' => 'Tiempo central subserie',
            'pSoporteGdTrdSubserie' => 'P. soporte subserie',
            'eSoporteGdTrdSubserie' => 'E. soporte subserie',
            'oSoporteGdTrdSubserie' => 'O. soporte subserie',
            'ctDisposicionFinalGdTrdSubserie' => 'CT. disposición final subserie',
            'eDisposicionFinalGdTrdSubserie' => 'E. disposición final subserie',
            'sDisposicionFinalGdTrdSubserie' => 'S. disposición final subserie',
            'mDisposicionFinalGdTrdSubserie' => 'M. disposición final subserie',
            'procedimientoGdTrdSubserie' => 'Procedimiento subserie',
            'estadoGdTrdSubserie' => 'Estado subserie',
            'creacionGdTrdSubserie' => 'Creación subserie',
            'normaGdTrdSubserie' => 'Norma subserie',
            'idGdTrdSerie' => 'Id serie',
        ];
    }

    /**
     * Gets query for [[GdTrds]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrds()
    {
        return $this->hasMany(GdTrd::className(), ['idGdTrdSubserie' => 'idGdTrdSubserie']);
    }

}
