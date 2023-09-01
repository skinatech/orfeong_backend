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
 * This is the model class for table "gaBodega".
 *
 * @property int $idGaBodega Id de la bodega
 * @property string $nombreGaBodega Descripción de la bodega
 * @property int $idGaPiso Id del piso
 * @property int $estadoGaBodega Estado de la bodega
 * @property string $creacionGaBodega Fecha creación
 *
 * @property GaArchivo[] $gaArchivos
 * @property GaPiso $idGaPiso0
 * @property GaBodegaContenido[] $gaBodegaContenidos
 */
class GaBodega extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaBodega';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGaBodega', 'idGaPiso'], 'required'],
            [['idGaPiso', 'estadoGaBodega'], 'integer'],
            [['creacionGaBodega'], 'safe'],
            [['nombreGaBodega'], 'string', 'max' => 80],
            [['idGaPiso'], 'exist', 'skipOnError' => true, 'targetClass' => GaPiso::className(), 'targetAttribute' => ['idGaPiso' => 'idGaPiso']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaBodega' => 'Id del area de archivo',
            'nombreGaBodega' => 'Descripción del area de archivo',
            'idGaPiso' => 'Id del piso',
            'estadoGaBodega' => 'Estado de la bodega',
            'creacionGaBodega' => 'Fecha creación',
        ];
    }

    /** 
    * Gets query for [[GaArchivos]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getGaArchivos() 
    { 
        return $this->hasMany(GaArchivo::className(), ['idGaBodega' => 'idGaBodega']); 
    }

    /**
     * Gets query for [[IdGaPiso0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGaPiso0()
    {
        return $this->hasOne(GaPiso::className(), ['idGaPiso' => 'idGaPiso']);
    }

    /**
     * Gets query for [[GaBodegaContenidos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGaBodegaContenidos()
    {
        return $this->hasMany(GaBodegaContenido::className(), ['idGaBodega' => 'idGaBodega']);
    }
}
