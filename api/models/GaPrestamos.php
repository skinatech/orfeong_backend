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
 * This is the model class for table "gaPrestamos".
 *
 * @property int $idGaPrestamo Id préstamo documental
 * @property int $idGdExpedienteInclusion Id expediente inclusión
 * @property int $idUser Id usuario logueado
 * @property int $idGdTrdDependencia Id dependencia del usuario logueado
 * @property string $fechaSolicitudGaPrestamo Fecha de solicitud del préstamo
 * @property int $idTipoPrestamoGaPrestamo Tipo préstamo
 * @property int $idRequerimientoGaPrestamo Requerimiento
 * @property string $observacionGaPrestamo Observación
 * @property int $estadoGaPrestamo Estado del préstamo
 * @property string $creacionGaPrestamo Fecha creación
 *
 * @property GaHistoricoPrestamo[] $gaHistoricoPrestamos 
 * @property GdExpedientesInclusion $idGdExpedienteInclusion0
 * @property GdTrdDependencias $idGdTrdDependencia0
 * @property User $idUser0
 */
class GaPrestamos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaPrestamos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpedienteInclusion', 'idUser', 'idGdTrdDependencia', 'fechaSolicitudGaPrestamo', 'idTipoPrestamoGaPrestamo', 'idRequerimientoGaPrestamo', 'observacionGaPrestamo'], 'required'],
            [['idGdExpedienteInclusion', 'idUser', 'idGdTrdDependencia', 'idTipoPrestamoGaPrestamo', 'idRequerimientoGaPrestamo', 'estadoGaPrestamo'], 'integer'],
            [['fechaSolicitudGaPrestamo', 'creacionGaPrestamo'], 'safe'],
            [['observacionGaPrestamo'], 'string', 'max' => 255],
            [['idGdExpedienteInclusion'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientesInclusion::className(), 'targetAttribute' => ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaPrestamo' => 'Id préstamo documental',
            'idGdExpedienteInclusion' => 'Id expediente inclusión',
            'idUser' => 'Id usuario logueado',
            'idGdTrdDependencia' => 'Id dependencia del usuario logueado',
            'fechaSolicitudGaPrestamo' => 'Fecha de solicitud del préstamo',
            'idTipoPrestamoGaPrestamo' => 'Tipo préstamo',
            'idRequerimientoGaPrestamo' => 'Requerimiento',
            'observacionGaPrestamo' => 'Observación',
            'estadoGaPrestamo' => 'Estado del préstamo',
            'creacionGaPrestamo' => 'Fecha creación',
        ];
    }

    public function attributeLabelsReport()
    {
        $alias = 'GAP';

        $imputsPermitidos = [
            'idUser' => 'IN', 
            'idGdTrdDependencia' => 'IN', 
            'creacionGaPrestamo' => 'DATE',
            'idTipoPrestamoGaPrestamo' => 'IN',
            'idRequerimientoGaPrestamo' => 'IN', 
            'observacionGaPrestamo' => 'LIKE', 
            'estadoGaPrestamo' => 'IN'
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $exceptionIds = [
            'idGdTrdDependencia' => 'Dependencia realizo operación', 
            'idUser' => 'Usuario realizo operación'
        ];

        $inputsRelation = [
            'idUser' => [
                'type' => 'model',
                'foreingModel' => 'UserDetalles',
                'foreingAlias' => 'GAUS1',
                'foreignKey' => 'idUser',
                'foreingColumn' => ['nombreUserDetalles', 'apellidoUserDetalles'],
                'alias' => $alias,
                'key' => 'idUser',
            ],
            'idGdTrdDependencia' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdDependencias',
                'foreingAlias' => 'GADEPE1',
                'foreignKey' => 'idGdTrdDependencia',
                'foreingColumn' => 'nombreGdTrdDependencia',
                'alias' => $alias,
                'key' => 'idGdTrdDependencia',
            ],
            'idTipoPrestamoGaPrestamo' => [
                'type' => 'i18n',
                'foreingModel' => 'statusLoanTypeNumber',
                'alias' => $alias,
                'key' => 'idTipoPrestamoGaPrestamo',
            ],
            'idRequerimientoGaPrestamo' => [
                'type' => 'i18n',
                'foreingModel' => 'statusLoanRequirementText',
                'alias' => $alias,
                'key' => 'idRequerimientoGaPrestamo',
            ],
            'estadoGaPrestamo' => [
                'type' => 'i18n',
                'foreingModel' => 'statusTodoNumber',
                'alias' => $alias,
                'key' => 'estadoGaPrestamo',
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
    * Gets query for [[GaHistoricoPrestamos]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getGaHistoricoPrestamos() 
    { 
        return $this->hasMany(GaHistoricoPrestamo::className(), ['idGaPrestamo' => 'idGaPrestamo']); 
    } 

    /**
     * Gets query for [[IdGdExpedienteInclusion0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdExpedienteInclusion0()
    {
        return $this->hasOne(GdExpedientesInclusion::className(), ['idGdExpedienteInclusion' => 'idGdExpedienteInclusion']);
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

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser0()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
