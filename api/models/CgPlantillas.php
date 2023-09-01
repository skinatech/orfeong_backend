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
 * This is the model class for table "cgPlantillas".
 *
 * @property int $idCgPlantilla
 * @property int $idUser Identificador del usuario que cargo el documento
 * @property string $nombreCgPlantilla Nombre que se le da al documento que se carga 
 * @property string $rutaCgPlantilla Ruta donde se encuentra el documento 
 * @property string $extencionCgPlantilla Guarda la extención del documento que se esta cargando
 * @property int $estadoCgPlantilla     Estado que se le aplica al documento
 * @property string $creacionCgPlantilla Fecha en la que se crea el documento
 *
 * @property UserDetalles $idUser
 */
class CgPlantillas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgPlantillas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'nombreCgPlantilla', 'rutaCgPlantilla', 'extencionCgPlantilla'], 'required'],
            [['idUser', 'estadoCgPlantilla'], 'integer'],
            [['creacionCgPlantilla'], 'safe'],
            [['nombreCgPlantilla'], 'string', 'max' => 80],
            [['extencionCgPlantilla'], 'string', 'max' => 20],
            [['nombreCgPlantilla'], 'unique'],
            [['rutaCgPlantilla'], 'string', 'max' => 255],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgPlantilla' => 'Id plantilla',
            'idUser' => 'Usuario',
            'nombreCgPlantilla' => 'Nombre de la plantilla',
            'rutaCgPlantilla' => 'Ruta de la plantilla',
            'extencionCgPlantilla' => 'Extensión de la plantilla',
            'estadoCgPlantilla' => 'Estado de la plantilla',
            'creacionCgPlantilla' => 'Creación de la plantilla',
        ];
    }

    /**
     * Gets query for [[IdUser]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }
}
