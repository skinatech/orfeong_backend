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
 * This is the model class for table "gdTrdDependencias".
 *
 * @property int $idGdTrdDependencia Identificador de la tabla
 * @property string $nombreGdTrdDependencia Nombre de la dependencia o area funcional según organigrama del cliente - Oficina productora
 * @property string $codigoGdTrdDependencia Código de la dependencia o centro de costos acorde al organigrama del cliente - Oficiona productora 
 * @property string $codigoGdTrdDepePadre Código de la dependencia o área funcional, dependencia principal - Unidad administrativa
 * @property int $estadoGdTrdDependencia Estado de la dependencia o área funcional
 * @property string $creacionGdTrdDependencia Fecha de creación de la dependencia
 * @property int $idCgRegional Idenntificador de la tabla regional, relación entre la tabla
 * @property string $observacionGdTrdDependencia Observación que se indica cuando se aprueba una Versión de la TRD
 * @property int $consecExpedienteGdTrdDependencia Consecutivo para el expediente
 *
 * @property GdTrd[] $gdTrds
 * @property CgRegionales $cgRegional
 * @property RadiLogRadicados[] $radiLogRadicados
 * @property User[] $users
 * @property GaPrestamos[] $gaPrestamos 
 */
class GdTrdDependencias extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTrdDependencias';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTrdDependencia', 'codigoGdTrdDependencia', 'idCgRegional'], 'required'],
            [['estadoGdTrdDependencia', 'idCgRegional', 'codigoGdTrdDepePadre', 'consecExpedienteGdTrdDependencia'], 'integer'],
            [['creacionGdTrdDependencia'], 'safe'],
            [['nombreGdTrdDependencia'], 'string', 'max' => 255],
            [['codigoGdTrdDependencia'], 'string', 'max' => 6],
            [['observacionGdTrdDependencia'], 'string', 'max' => 500],
            [['idCgRegional'], 'exist', 'skipOnError' => true, 'targetClass' => CgRegionales::className(), 'targetAttribute' => ['idCgRegional' => 'idCgRegional']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTrdDependencia' => 'Id Trd dependencia',
            'nombreGdTrdDependencia' => 'Nombre dependencia',
            'codigoGdTrdDependencia' => 'Código dependencia',
            'codigoGdTrdDepePadre' => 'Id unidad administrativa',
            'estadoGdTrdDependencia' => 'Estado dependencia',
            'creacionGdTrdDependencia' => 'Creación dependencia',
            'idCgRegional' => 'Id regional',
            'observacionGdTrdDependencia' => 'Observación dependencia',
            'consecExpedienteGdTrdDependencia' => 'Consecutivo para el expediente',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrds()
    {
        return $this->hasMany(GdTrd::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

    /**
    * NO BORRAR SE UTILIZA EN CONTROLADORES
    */
    public function toUpperCaseCodigo(){
        $this->codigoGdTrdDependencia = strtoupper($this->codigoGdTrdDependencia);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCgRegional()
    {
        return $this->hasOne(CgRegionales::className(), ['idCgRegional' => 'idCgRegional']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiLogRadicados()
    {
        return $this->hasMany(RadiLogRadicados::className(), ['idDependencia' => 'idGdTrdDependencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers()
    {
        return $this->hasMany(User::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

    /**
    * Gets query for [[GaPrestamos]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getGaPrestamos() 
    { 
        return $this->hasMany(GaPrestamos::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']); 
    }
    
     /**
    * Gets query for [[GdTrdDependencia]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */

    public function getGdTrdDependenciaPadre()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'codigoGdTrdDepePadre']);
    }
}
