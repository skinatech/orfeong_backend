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
 * This is the model class for table "gdTrdSubseriesTmp".
 *
 * @property int $idGdTrdSubserieTmp Número único para identificar la subserie
 * @property string $nombreGdTrdSubserieTmp Nombre de la subserie documental
 * @property string $codigoGdTrdSubserieTmp Código asociado a la subserie documetal este va a sociado a la dependencia
 * @property int $tiempoGestionGdTrdSubserieTmp Tiempo en años de gestión asignados a la subserie
 * @property int $tiempoCentralGdTrdSubserieTmp Tiempo en años de central asignados a la subserie
 * @property int|null $pSoporteGdTrdSubserieTmp Este campo guarda la información respecto al soporte de la subserie (papel)
 * @property int|null $ctDisposicionFinalGdTrdSubserieTmp
 * @property int|null $eDisposicionFinalGdTrdSubserieTmp
 * @property int|null $sDisposicionFinalGdTrdSubserieTmp
 * @property int|null $mDisposicionFinalGdTrdSubserieTmp
 * @property int|null $eSoporteGdTrdSubserieTmp Este campo guarda la información respecto al soporte de la subserie (electronico)
 * @property int|null $oSoporteGdTrdSubserieTmp Este campo guarda la información respecto al soporte de la subserie (otro)
 * @property string $procedimientoGdTrdSubserieTmp Procedimiento correspondiente a la subserie
 * @property int $estadoGdTrdSubserieTmp 0 Inactivo - 10 Activo
 * @property string $creacionGdTrdSubserieTmp Creación del registro
 * @property string|null $normaGdTrdSubserieTmp
 * @property int|null $idGdTrdSerieTmp Código que relaciona el id de la serie
 *
 * @property GdTrdTmp[] $gdTrdTmps
 */
class GdTrdSubseriesTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdSubseriesTmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdSubserieTmp', 'codigoGdTrdSubserieTmp', 'tiempoGestionGdTrdSubserieTmp', 'tiempoCentralGdTrdSubserieTmp', 'procedimientoGdTrdSubserieTmp'], 'required'],
            [['tiempoGestionGdTrdSubserieTmp', 'tiempoCentralGdTrdSubserieTmp', 'pSoporteGdTrdSubserieTmp', 'ctDisposicionFinalGdTrdSubserieTmp', 'eDisposicionFinalGdTrdSubserieTmp', 'sDisposicionFinalGdTrdSubserieTmp', 'mDisposicionFinalGdTrdSubserieTmp', 'eSoporteGdTrdSubserieTmp', 'oSoporteGdTrdSubserieTmp', 'estadoGdTrdSubserieTmp', 'idGdTrdSerieTmp'], 'integer'],
            [['procedimientoGdTrdSubserieTmp', 'normaGdTrdSubserieTmp'], 'string'],
            [['creacionGdTrdSubserieTmp'], 'safe'],
            [['nombreGdTrdSubserieTmp'], 'string', 'max' => 255],
            [['codigoGdTrdSubserieTmp'], 'string', 'max' => 45],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdSubserieTmp' => 'Id Trd subserie temporal',
            'nombreGdTrdSubserieTmp' => 'Nombre subserie temporal',
            'codigoGdTrdSubserieTmp' => 'Código subserie temporal',
            'tiempoGestionGdTrdSubserieTmp' => 'Tiempo gestión subserie temporal',
            'tiempoCentralGdTrdSubserieTmp' => 'Tiempo central subserie temporal',
            'pSoporteGdTrdSubserieTmp' => 'P. Soporte subserie temporal',
            'ctDisposicionFinalGdTrdSubserieTmp' => 'CT. disposición final subserie temporal',
            'eDisposicionFinalGdTrdSubserieTmp' => 'E. disposición final subserie temporal',
            'sDisposicionFinalGdTrdSubserieTmp' => 'S. disposición final subserie temporal',
            'mDisposicionFinalGdTrdSubserieTmp' => 'M. disposición final subserie temporal',
            'eSoporteGdTrdSubserieTmp' => 'E. Soporte subserie temporal',
            'oSoporteGdTrdSubserieTmp' => 'O. Soporte subserie temporal',
            'procedimientoGdTrdSubserieTmp' => 'Procedimiento subserie temporal',
            'estadoGdTrdSubserieTmp' => 'Estado subserie temporal',
            'creacionGdTrdSubserieTmp' => 'Creación subserie temporal',
            'normaGdTrdSubserieTmp' => 'Norma subserie temporal',
            'idGdTrdSerieTmp' => 'Id serie temporal',
        ];
    }

    /**
     * Gets query for [[GdTrdTmps]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTmps()
    {
        return $this->hasMany(GdTrdTmp::className(), ['idGdTrdSubserieTmp' => 'idGdTrdSubserieTmp']);
    }

}
