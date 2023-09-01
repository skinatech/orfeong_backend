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
 * This is the model class for table "radiCorreosRadicados".
 *
 * @property int $idRadiCorreosRadicados Indice unico de la tabla
 * @property int $idRadiRadicado Id del radicado
 * @property string $bandeja Nombre de la bandeja
 * @property int $idCorreo Identificador del correo
 * @property string $email Correo
 *
 * @property RadiRadicados $idRadiRadicado0
 */
class RadiCorreosRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiCorreosRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'bandeja', 'idCorreo', 'email'], 'required'],
            [['idRadiRadicado', 'idCorreo'], 'integer'],
            [['bandeja'], 'string', 'max' => 100],
            [['email'], 'string', 'max' => 255],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiCorreosRadicados' => 'Indice unico de la tabla',
            'idRadiRadicado' => 'Id del radicado',
            'bandeja' => 'Nombre de la bandeja',
            'idCorreo' => 'Identificador del correo',
            'email' => 'Correo',
        ];
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdRadiRadicado0()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }
}

