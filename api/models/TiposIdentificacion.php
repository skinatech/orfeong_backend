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
 * This is the model class for table "tiposIdentificacion".
 *
 * @property int $idTipoIdentificacion Número único para identificar los tipos de identificación
 * @property string $nombreTipoIdentificacion Nombre del tipo de identificación
 * @property int $estadoTipoIdentificacion 0 Inactivo 10 Activo
 *
 * @property UserDetalles[] $userDetalles
 */
class TiposIdentificacion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tiposIdentificacion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreTipoIdentificacion'], 'required'],
            [['estadoTipoIdentificacion'], 'integer'],
            [['nombreTipoIdentificacion'], 'string', 'max' => 50],
            [['nombreTipoIdentificacion'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTipoIdentificacion' => 'Id Tipo Identificacion',
            'nombreTipoIdentificacion' => 'Nombre Tipo Identificacion',
            'estadoTipoIdentificacion' => 'Estado Tipo Identificacion',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserDetalles()
    {
        return $this->hasMany(UserDetalles::className(), ['idTipoIdentificacion' => 'idTipoIdentificacion']);
    }
}
