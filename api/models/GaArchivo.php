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
 * This is the model class for table "gaArchivo".
 *
 * @property int $idgaArchivo
 * @property int $idGdExpediente identificador de la tabla GdExpedientes
 * @property int $idGaEdificio identificador de la tabla gaEdificio
 * @property int $idGaPiso identificador de la tabla gaPiso
 * @property int $idGaBodega identificador de la tabla gaBodega
 * @property int $rackGaArchivo describe el numero del rack donde se encuentra el documento
 * @property int $entrepanoGaArchivo describe el numero de entrepaño donde se encuentra el documento
 * @property int $cajaGaArchivo describe el numero de la caja donde se encuentra el documento
 * @property int $unidadConservacionGaArchivo Unidad de conservación  del documentos o clasificación del documento
 * @property string $unidadCampoGaArchivo indica el identificador de la unidad de conservación 
 * @property int $estadoGaArchivo estado del archivo activo por defecto
 * @property string $creacionGaArchivo creación del archivo
 * @property string|null $cuerpoGaArchivo Cuerpo de la bodega
 *
 * @property GdExpedientes $GdExpediente
 * @property GaEdificio $GaEdificio
 * @property GaPiso $GaPiso
 * @property GaBodega $GaBodega
 */
class GaArchivo extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaArchivo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdExpediente', 'idGaEdificio', 'idGaPiso', 'idGaBodega', 'estanteGaArchivo', 'rackGaArchivo', 'entrepanoGaArchivo', 'cajaGaArchivo', 'unidadConservacionGaArchivo', 'unidadCampoGaArchivo'], 'required'],
            [['idGdExpediente', 'idGaEdificio', 'idGaPiso', 'idGaBodega', 'estanteGaArchivo', 'rackGaArchivo', 'entrepanoGaArchivo', 'cajaGaArchivo', 'unidadConservacionGaArchivo', 'estadoGaArchivo', 'consecutivoGaArchivo'], 'integer'],
            [['creacionGaArchivo'], 'safe'],
            [['unidadCampoGaArchivo'], 'string', 'max' => 20],
            [['cuerpoGaArchivo'], 'string', 'max' => 2],
            [['idGdExpediente'], 'exist', 'skipOnError' => true, 'targetClass' => GdExpedientes::className(), 'targetAttribute' => ['idGdExpediente' => 'idGdExpediente']],
            [['idGaEdificio'], 'exist', 'skipOnError' => true, 'targetClass' => GaEdificio::className(), 'targetAttribute' => ['idGaEdificio' => 'idGaEdificio']],
            [['idGaPiso'], 'exist', 'skipOnError' => true, 'targetClass' => GaPiso::className(), 'targetAttribute' => ['idGaPiso' => 'idGaPiso']],
            [['idGaBodega'], 'exist', 'skipOnError' => true, 'targetClass' => GaBodega::className(), 'targetAttribute' => ['idGaBodega' => 'idGaBodega']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idgaArchivo' => 'Id archivo',
            'idGdExpediente' => 'Expediente',
            'idGaEdificio' => 'Edificio',
            'idGaPiso' => 'Piso',
            'idGaBodega' => 'Area de archivo',
            'estanteGaArchivo' => 'Estante',
            'rackGaArchivo' => 'Cantidad de módulo',
            'entrepanoGaArchivo' => 'Cantidad de entrepaño',
            'cajaGaArchivo' => 'Cantidad de caja',
            'cuerpoGaArchivo' => 'Cuerpo',
            'unidadConservacionGaArchivo' => 'Unidad conversación',
            'consecutivoGaArchivo' => 'Consecutivo archivo',
            'unidadCampoGaArchivo' => 'Unidad de campo',
            'estadoGaArchivo' => 'Estado',
            'creacionGaArchivo' => 'Fecha creación',
        ];
    }

    public function attributeLabelsReport()
    {
        $alias = 'GAA';
        $imputsPermitidos = [
            'idGaEdificio' => 'IN', 'idGaPiso' => 'IN', 'idGaBodega' => 'IN', 'estadoGaArchivo' => 'IN'
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $inputsRelation = [
            'idGaEdificio' => [
                'type' => 'model',
                'foreingModel' => 'GaEdificio',
                'foreingAlias' => 'EDIF',
                'foreignKey' => 'idGaEdificio',
                'foreingColumn' => 'nombreGaEdificio',
                'alias' => $alias,
                'key' => 'idGaEdificio',
            ],
            'idGaPiso' => [
                'type' => 'model',
                'foreingModel' => 'GaPiso',
                'foreingAlias' => 'PISO',
                'foreignKey' => 'idGaPiso',
                'foreingColumn' => 'numeroGaPiso',
                'alias' => $alias,
                'key' => 'idGaPiso',
            ],
            'idGaBodega' => [
                'type' => 'model',
                'foreingModel' => 'GaBodega',
                'foreingAlias' => 'BOD',
                'foreignKey' => 'idGaBodega',
                'foreingColumn' => 'nombreGaBodega',
                'alias' => $alias,
                'key' => 'idGaBodega',
            ],
            'estadoGaArchivo' => [
                'type' => 'params',
                'foreingModel' => 'statusTodoNumber',
                'alias' => $alias,
                'key' => 'estadoGaArchivo',
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
     * Gets query for [[idGdExpediente0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpediente()
    {
        return $this->hasOne(GdExpedientes::className(), ['idGdExpediente' => 'idGdExpediente']);
    }

    /**
     * Gets query for [[IdGaEdificio0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaEdificio()
    {
        return $this->hasOne(GaEdificio::className(), ['idGaEdificio' => 'idGaEdificio']);
    }

    /**
     * Gets query for [[IdGaPiso0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaPiso()
    {
        return $this->hasOne(GaPiso::className(), ['idGaPiso' => 'idGaPiso']);
    }

    /**
     * Gets query for [[IdGaBodega0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaBodega()
    {
        return $this->hasOne(GaBodega::className(), ['idGaBodega' => 'idGaBodega']);
    }
}
