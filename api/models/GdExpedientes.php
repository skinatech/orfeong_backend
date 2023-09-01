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
 * This is the model class for table "gdExpedientes".
 *
 * @property int $idGdExpediente Identificador único del expediente
 * @property string $numeroGdExpediente Número del expediente asignado por el sistema
 * @property string $nombreGdExpediente Nombre del expediente
 * @property int $idUser Identificador único que se le asigna al usuario creador
 * @property int $idGdTrdDependencia Identificador único que se le asigna a la dependencia del usuario creador
 * @property int $idGdTrdSerie Identificador único que se le asigna a la serie
 * @property int $idGdTrdSubserie Identificador único que se le asigna a la subserie
 * @property int $estadoGdExpediente Estado del expediente  10 activo 0 Inactivo
 * @property int|null $ubicacionGdExpediente ubicacion actual del expediente (gestion/central/historico)
 * @property string $creacionGdExpediente Fecha de creación del expediente
 * @property string $tiempoGestionGdExpedientes fecha de vencimiento para la gestión asignada segun la subserie
 * @property string $tiempoCentralGdExpedientes fecha de vencimiento para la central asignada segun la subserie
 * @property string $fechaProcesoGdExpediente Fecha de proceso
 * @property string $descripcionGdExpediente Descripción de expediente
 * @property int $numeracionGdExpediente Número de documentos incluidos en el expediente
 * @property string|null $rutaCerrarGdExpediente Ruta del documento al cerrar expediente
 * @property int $existeFisicamenteGdExpediente Indica si el expediente existe físicamente 0: false, 1: true
 * 
 * @property GdTrdDependencias $idGdTrdDependencia0
 * @property GdTrdSeries $idGdTrdSerie0
 * @property GdTrdSubseries $idGdTrdSubserie0
 * @property User $idUser0
 * @property GdExpedientesInclusion[] $gdExpedientesInclusions
 * @property GdHistoricoExpedientes[] $gdHistoricoExpedientes
 * @property GaArchivo $gaArchivo
 * @property GaPrestamosExpedientes[] $gaPrestamosExpedientes
 */
class GdExpedientes extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdExpedientes';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['numeroGdExpediente', 'nombreGdExpediente', 'idUser', 'idGdTrdDependencia', 'idGdTrdSerie', 'idGdTrdSubserie', 'tiempoGestionGdExpedientes', 'tiempoCentralGdExpedientes', 'fechaProcesoGdExpediente', 'descripcionGdExpediente', 'existeFisicamenteGdExpediente'], 'required'],
            [['idUser', 'idGdTrdDependencia', 'idGdTrdSerie', 'idGdTrdSubserie', 'estadoGdExpediente', 'ubicacionGdExpediente', 'numeracionGdExpediente'], 'integer'],
            [['creacionGdExpediente', 'tiempoGestionGdExpedientes', 'tiempoCentralGdExpedientes', 'fechaProcesoGdExpediente'], 'safe'],
            [['descripcionGdExpediente'], 'string'],
            [['numeroGdExpediente'], 'string', 'max' => 20],
            [['nombreGdExpediente'], 'string', 'max' => 250],
            [['rutaCerrarGdExpediente'], 'string', 'max' => 255],
            [['numeroGdExpediente'], 'unique'],
            [['nombreGdExpediente', 'idGdTrdDependencia'], 'unique', 'targetAttribute' => ['nombreGdExpediente', 'idGdTrdDependencia']],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
            [['idGdTrdSerie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSeries::className(), 'targetAttribute' => ['idGdTrdSerie' => 'idGdTrdSerie']],
            [['idGdTrdSubserie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSubseries::className(), 'targetAttribute' => ['idGdTrdSubserie' => 'idGdTrdSubserie']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            // [['gaArchivo'], 'exist', 'skipOnError' => true, 'targetClass' => GaArchivo::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdExpediente' => 'Id expediente',
            'numeroGdExpediente' => 'Número expediente',
            'nombreGdExpediente' => 'Nombre expediente',
            'idUser' => 'Id user',
            'idGdTrdDependencia' => 'Id Trd dependencia',
            'idGdTrdSerie' => 'Id Trd serie',
            'idGdTrdSubserie' => 'Id Trd subserie',
            'estadoGdExpediente' => 'Estado expediente',
            'ubicacionGdExpediente' => 'Ubicación expediente',
            'creacionGdExpediente' => 'Creación expediente',
            'tiempoGestionGdExpedientes' => 'Tiempo gestión expediente',
            'tiempoCentralGdExpedientes' => 'Tiempo central expediente',
            'fechaProcesoGdExpediente' => 'Fecha proceso expediente',
            'descripcionGdExpediente' => 'Descripción expediente',
            'numeracionGdExpediente' => 'Numeración expediente',
            'rutaCerrarGdExpediente' => 'Ruta del documento de cierre del expediente',
            'existeFisicamenteGdExpediente' => '¿Existe físicamente?',
        ];
    }

    public function attributeLabelsReport()
    {
        $alias = 'GDE';

        $imputsPermitidos = [
            'numeroGdExpediente' => 'LIKE', 
            'nombreGdExpediente' => 'LIKE', 
            'idGdTrdDependencia' => 'IN', 
            'idGdTrdSerie' => 'IN', 
            'idGdTrdSubserie' => 'IN',
            'estadoGdExpediente' => 'IN', 
            'ubicacionGdExpediente' => 'IN', 
            'creacionGdExpediente' => 'DATE', 
            'fechaProcesoGdExpediente' => 'DATE', 
            'descripcionGdExpediente' => 'LIKE'
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $exceptionIds = [
            'idGdTrdDependencia' => 'Nombre dependencia',
            'idGdTrdSerie' => 'Nombre serie', 
            'idGdTrdSubserie' => 'Nombre subserie',
        ];

        $inputsRelation = [
            'idGdTrdDependencia' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdDependencias',
                'foreingAlias' => 'E_DEPE1',
                'foreignKey' => 'idGdTrdDependencia',
                'foreingColumn' => 'nombreGdTrdDependencia',
                'alias' => $alias,
                'key' => 'idGdTrdDependencia',
            ],
            'idGdTrdSerie' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdSeries',
                'foreingAlias' => 'SER1',
                'foreignKey' => 'idGdTrdSerie',
                'foreingColumn' => ['codigoGdTrdSerie', 'nombreGdTrdSerie'],
                'alias' => $alias,
                'key' => 'idGdTrdSerie',
            ],
            'idGdTrdSubserie' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdSubseries',
                'foreingAlias' => 'SUBS1',
                'foreignKey' => 'idGdTrdSubserie',
                'foreingColumn' => ['codigoGdTrdSubserie', 'nombreGdTrdSubserie'],
                'alias' => $alias,
                'key' => 'idGdTrdSubserie',
            ],
            'estadoGdExpediente' => [
                'type' => 'params',
                'foreingModel' => 'statusExpedienteNumber',
                'alias' => $alias,
                'key' => 'estadoGdExpediente',
            ],
            'ubicacionGdExpediente' => [
                'type' => 'params',
                'foreingModel' => 'ubicacionTransferenciaTRD',
                'alias' => $alias,
                'key' => 'ubicacionGdExpediente',
            ],
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $arrayAttributeLabelsReport = [];

        $attributeLabels = self::attributeLabels();
        foreach ($attributeLabels as $key => $value) {
            if (array_key_exists($key, $imputsPermitidos)) {

                if(array_key_exists($key, $exceptionIds)){
                    $value = $exceptionIds[$key];
                } 

                $input = [
                    'column' => $alias.'_'.$key, 
                    'label' => $value, 
                    'value' => false, 
                    'typeFilter' => $imputsPermitidos[$key], 
                    'infoRelation' => []
                ];

                if (array_key_exists($key, $inputsRelation)) {
                    $input['infoRelation'] = $inputsRelation[$key];
                }
                $arrayAttributeLabelsReport[] = $input;
            }
        }

        return $arrayAttributeLabelsReport;
    }

    /**
     * Gets query for [[gdTrdDependencia]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependencia()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

    /**
     * Gets query for [[gdTrdSerie]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSerie()
    {
        return $this->hasOne(GdTrdSeries::className(), ['idGdTrdSerie' => 'idGdTrdSerie']);
    }

    /**
     * Gets query for [[gdTrdSubserie]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdSubserie()
    {
        return $this->hasOne(GdTrdSubseries::className(), ['idGdTrdSubserie' => 'idGdTrdSubserie']);
    }

    /**
     * Gets query for [[user]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * Gets query for [[GdExpedientesInclusion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpedientesInclusion()
    {
        return $this->hasMany(GdExpedientesInclusion::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[GdExpedienteDocumentos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpedienteDocumentos()
    {
        return $this->hasMany(GdExpedienteDocumentos::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[radiRadicado]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaArchivo()
    {
        return $this->hasOne(GaArchivo::className(), ['idGdExpediente' => 'idGdExpediente']);
    
    }

    /** Gets query for [[GaPrestamos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaPrestamosExpediente()
    {
        return $this->hasMany(GaPrestamosExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }
}
