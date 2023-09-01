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
 * This is the model class for table "gdTrdDependenciasTmp".
 *
 * @property int $idGdTrdDependenciaTmp idGdTrdDependencia
 * @property string $nombreGdTrdDependenciaTmp Nombre de la dependencia o area funcional según organigrama del cliente - Oficina productora
 * @property string $codigoGdTrdDependenciaTmp Código de la dependencia o centro de costos acorde al organigrama del cliente - Oficiona productora 
 * @property string $codigoGdTrdDepePadreTmp Código de la dependencia o área funcional, dependencia principal - Unidad administrativa
 * @property int $estadoGdTrdDependenciaTmp Estado de la dependencia o área funcional
 * @property string $creacionGdTrdDependenciaTmp Fecha de creación de la dependencia
 * @property int $idCgRegionalTmp Identificador de la tabla regional, relación entre la tabla
 *
 * @property GdTrdTmp[] $gdTrdTmps
 */
class GdTrdDependenciasTmp extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdDependenciasTmp';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdDependenciaTmp', 'codigoGdTrdDependenciaTmp', 'idCgRegionalTmp'], 'required'],
            [['estadoGdTrdDependenciaTmp', 'idCgRegionalTmp', 'codigoGdTrdDepePadreTmp'], 'integer'],
            [['creacionGdTrdDependenciaTmp'], 'safe'],
            [['nombreGdTrdDependenciaTmp'], 'string', 'max' => 255],
            [['codigoGdTrdDependenciaTmp'], 'string', 'max' => 6],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdDependenciaTmp' => 'Id Trd Dependencia temporal',
            'nombreGdTrdDependenciaTmp' => 'Nombre dependencia temporal',
            'codigoGdTrdDependenciaTmp' => 'Código dependencia temporal',
            'codigoGdTrdDepePadreTmp' => 'Id unidad administrativa temporal',
            'estadoGdTrdDependenciaTmp' => 'Estado dependencia temporal',
            'creacionGdTrdDependenciaTmp' => 'Creación dependencia temporal',
            'idCgRegionalTmp' => 'Id regional temporal',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdTmps()
    {
        return $this->hasMany(GdTrdTmp::className(), ['idGdTrdDependenciaTmp' => 'idGdTrdDependenciaTmp']);
    }
}
