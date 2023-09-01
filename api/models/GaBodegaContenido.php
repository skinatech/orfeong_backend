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
 * This is the model class for table "gaBodegaContenido".
 *
 * @property int $idGaBodegaContenido Id del contenido de la bodega
 * @property int $cantidadRackGaBodegaContenido Cantidad del rack
 * @property int $cantidadEntrepanoGaBodegaContenido Cantidad del entrepaño
 * @property int $cantidadCajaGaBodegaContenido Cantidad de la caja
 * @property int $idGaBodega Id de la bodega
 * @property int $estadoGaBodegaContenido Estado del contenido de la bodega
 * @property string $creacionGaBodegaContenido Fecha creación
 * @property string|null $cuerpoGaBodegaContenido Cuerpo de la bodega
 * @property int $cantidadEstanteGaBodegaContenido Estante de la bodega
 *
 * @property GaBodega $idGaBodega0
 */
class GaBodegaContenido extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gaBodegaContenido';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cantidadRackGaBodegaContenido', 'cantidadEntrepanoGaBodegaContenido', 'cantidadCajaGaBodegaContenido', 'idGaBodega', 'cantidadEstanteGaBodegaContenido'], 'required'],
            [['cantidadRackGaBodegaContenido', 'cantidadEntrepanoGaBodegaContenido', 'cantidadCajaGaBodegaContenido', 'idGaBodega', 'estadoGaBodegaContenido', 'cantidadEstanteGaBodegaContenido'], 'integer'],
            [['creacionGaBodegaContenido'], 'safe'],
            [['cuerpoGaBodegaContenido'], 'string', 'max' => 2], 
            [['idGaBodega'], 'exist', 'skipOnError' => true, 'targetClass' => GaBodega::className(), 'targetAttribute' => ['idGaBodega' => 'idGaBodega']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGaBodegaContenido' => 'Id del contenido de la bodega',
            'cantidadRackGaBodegaContenido' => 'Cantidad del módulo',
            'cantidadEntrepanoGaBodegaContenido' => 'Cantidad de la entrepaño',
            'cantidadCajaGaBodegaContenido' => 'Cantidad de la caja',
            'cuerpoGaBodegaContenido' => 'Cuerpo',
            'cantidadEstanteGaBodegaContenido' => 'Cantidad de estante',
            'idGaBodega' => 'Id de la bodega',
            'estadoGaBodegaContenido' => 'Estado del contenido de la bodega',
            'creacionGaBodegaContenido' => 'Fecha creación',
        ];
    }

    /**
     * Gets query for [[IdGaBodega0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGaBodega0()
    {
        return $this->hasOne(GaBodega::className(), ['idGaBodega' => 'idGaBodega']);
    }
}
