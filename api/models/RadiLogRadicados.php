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
 * This is the model class for table "radiLogRadicados".
 *
 * @property int $idRadiLogRadicado identificador unico de la tabla
 * @property int $idUser identificador del usuario que esta realizando la transacción con el radicado
 * @property int $idDependencia identificador de la dependencia perteneciente al usuario que esta realizando la transacción con el radicado
 * @property int $idRadiRadicado identificador de la tabla de radicados para saber cual radicado se vio afectado con  la transacción realizada
 * @property int $idTransaccion identificador de la tabla de transacciones de los radicados, 
 * @property string $fechaRadiLogRadicado fecha en la que se realizo la transacción del radicado
 * @property string $observacionRadiLogRadicado observación de la transacciones que se efectuan con el radicado
 *
 * @property GdTrdDependencias $dependencia
 * @property RadiRadicados $radiRadicado
 * @property CgTransaccionesRadicados $transaccion
 * @property UserDetalles $user
 */
class RadiLogRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiLogRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'idDependencia', 'idRadiRadicado', 'idTransaccion', 'observacionRadiLogRadicado'], 'required'],
            [['idUser', 'idDependencia', 'idRadiRadicado', 'idTransaccion'], 'integer'],
            [['fechaRadiLogRadicado'], 'safe'],
            [['observacionRadiLogRadicado'], 'string', 'max' => 500],
            [['idDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idDependencia' => 'idGdTrdDependencia']],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idTransaccion'], 'exist', 'skipOnError' => true, 'targetClass' => CgTransaccionesRadicados::className(), 'targetAttribute' => ['idTransaccion' => 'idCgTransaccionRadicado']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiLogRadicado' => 'Id Radi Log Radicado',
            'idUser' => 'Id User',
            'idDependencia' => 'Id Dependencia',
            'idRadiRadicado' => 'Id Radi Radicado',
            'idTransaccion' => 'Transaccion',
            'fechaRadiLogRadicado' => 'Fecha Log Radicado',
            'observacionRadiLogRadicado' => 'Observacion Radi Log Radicado',
        ];
    }

    public function attributeLabelsReport()
    {
        $alias = 'RLG';
        $imputsPermitidos = [
            'idTransaccion' => 'IN', 'fechaRadiLogRadicado' => 'DATE'
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $inputsRelation = [
            'idTransaccion' => [
                'type' => 'model',
                'foreingModel' => 'CgTransaccionesRadicados',
                'foreingAlias' => 'TRAN1',
                'foreignKey' => 'idCgTransaccionRadicado',
                'foreingColumn' => 'titleCgTransaccionRadicado',
                'alias' => $alias,
                'key' => 'idTransaccion',
            ],
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $arrayAttributeLabelsReport = [];

        $attributeLabels = self::attributeLabels();
        foreach ($attributeLabels as $key => $value) {
            if (array_key_exists($key, $imputsPermitidos)) {
                $input = ['column' => $alias.'_'.$key, 'label' => $value, 'value' => false, 'typeFilter' => $imputsPermitidos[$key], 'infoRelation' => []];
                if (array_key_exists($key, $inputsRelation)) {
                    $input['infoRelation'] = $inputsRelation[$key];
                }
                $arrayAttributeLabelsReport[] = $input;
            }
        }

        return $arrayAttributeLabelsReport;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDependencia()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idDependencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTransaccion()
    {
        return $this->hasOne(CgTransaccionesRadicados::className(), ['idCgTransaccionRadicado' => 'idTransaccion']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
