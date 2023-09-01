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
 * This is the model class for table "userDetalles".
 *
 *
 * @property int $idUserDetalles Número único que identifica la cantidad de usuarios registrados en el sistema
 * @property int $idUser Número que indica el usuario
 * @property string $nombreUserDetalles Nombres del usuario
 * @property string $apellidoUserDetalles Apellidos del usuario
 * @property string $cargoUserDetalles Cargo del usuario
 * @property int $creacionUserDetalles Fecha en que se creo el usuario
 * @property int $idTipoIdentificacion Número que indica el tipo de identificación
 * @property string $documento Número de identificación o documeto
 * @property string|null $firma ruta de la firma
 * @property int $estadoUserDetalles 0 Inactivo - 10 Activo
 *
 * @property User $user
 * @property TiposIdentificacion $tipoIdentificacion
 */
class UserDetalles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'userDetalles';
    }
  
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'nombreUserDetalles', 'apellidoUserDetalles', 'cargoUserDetalles', 'creacionUserDetalles', 'idTipoIdentificacion', 'documento'], 'required'],
            [['idUser', 'idTipoIdentificacion', 'estadoUserDetalles'], 'integer'],
            [['creacionUserDetalles'], 'safe'],
            [['documento'], 'unique'], 
            [['nombreUserDetalles', 'apellidoUserDetalles', 'cargoUserDetalles'], 'string', 'max' => 80,            
            'tooLong'=> Yii::t('app', 'tooLong', [
                'attribute' =>  '{attribute}',
                'max'   => 80,
            ])],
            [['documento'], 'string', 'max' => 20,
            'tooLong'=> Yii::t('app', 'tooLong', [
                'attribute' =>  '{attribute}',
                'max'   => 20,
            ])], 
            [['firma'], 'string', 'max' => 255], 
            [['idUser'], 'unique'],
            [['documento'], 'unique', 'message' => Yii::t('app','documentoUserExistente') , 'targetAttribute' => ['idTipoIdentificacion', 'documento']],
            [['idUser'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['idUser' => 'id']],
            [['idTipoIdentificacion'], 'exist', 'skipOnError' => true, 'targetClass' => TiposIdentificacion::className(), 'targetAttribute' => ['idTipoIdentificacion' => 'idTipoIdentificacion']],
        ];
    }


    


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idUserDetalles' => 'Id usuario detalles',
            'idUser' => 'Id usuario',
            'nombreUserDetalles' => 'Nombre del usuario',
            'apellidoUserDetalles' => 'Apellido del usuario',
            'cargoUserDetalles' => 'Cargo',
            'creacionUserDetalles' => 'Fecha creación usuario',
            //'idTipoIdentificacion' => 'Id Tipo Identificacion',
            'idTipoIdentificacion' => '',
            'documento' => 'Documento',
            'firma' => 'Firma',
            'estadoUserDetalles' => 'Estado',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::className(), ['id' => 'idUser']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getTipoIdentificacion()
    {
        return $this->hasOne(TiposIdentificacion::className(), ['idTipoIdentificacion' => 'idTipoIdentificacion']);
    }
}

