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
 * This is the model class for table "gdFirmasQr".
 *
 * @property int $idGdFirmasQr
 * @property int $idRadiRadicado id de la tabla radiRadicados
 * @property int $idUser id de la tabla user
 * @property int $estadoGdFirmasQr indica el permiso que tenia el usuario al momento de firmar
 * @property string $creacionGdFirmasQr Fecha de generación de la firma
 *
 * @property RadiRadicados $idRadiRadicado
 * @property User $idUser
 */
class GdFirmasQr extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdFirmasQr';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idUser'], 'required'],
            [['idRadiRadicado', 'idUser', 'estadoGdFirmasQr'], 'integer'],
            [['creacionGdFirmasQr'], 'safe'],
            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdFirmasQr' => 'Id Firma',
            'idRadiRadicado' => 'Número de radicado',
            'idUser' => 'Id usuario',
            'idDocumento' => 'Documento principal',
            'estadoGdFirmasQr' => 'Estado',
            'creacionGdFirmasQr' => 'Fecha de creación',
        ];
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[IdUser0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
