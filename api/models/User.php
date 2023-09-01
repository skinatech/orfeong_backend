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
 * This is the model class for table "user".
 *
 * @property int $id Número único que inca la cantidad de usuarios registrados en el sistema
 * @property string $username Usuario o correo con que el usuario se registra
 * @property string $auth_key auth_key
 * @property string $password_hash Contraseña definida por el usuario
 * @property string $password_reset_token almacena codigo de restauración de contraseña
 * @property string $email Correo que ingresan al sistema
 * @property int $status 0 Inactivo - 10 Activo
 * @property int $created_at Fecha de creación convertida en número
 * @property int $updated_at Fecha de modificación convertida en número
 * @property string $fechaVenceToken Fecha en la que se vence el token para el usuario
 * @property int $idRol Número que indica el rol del usuario
 * @property int $idUserTipo Número que indica el tipo de usuario
 * @property string $accessToken accessToken
 * @property int $intentos Intentos
 * @property int $ldap 0 false y 10 true
 * @property string $verification_token
 * @property int $idGdTrdDependencia
 * @property int $licenciaAceptada
 * 
 * @property Log[] $logs
 * @property GdTrdDependencias $gdTrdDependencia
 * @property Roles $rol
 * @property UserTipo $userTipo
 * @property UserDetalles $userDetalles
 * @property UserHistoryPassword[] $userHistoryPasswords
 * @property GaPrestamos[] $gaPrestamos 
 * @property RadiRadicadoAnulado[] $radiRadicadoAnulados
 * @property ClientesCiudadanosDetalles[] $clientesCiudadanosDetalles
 * @property Notificacion[] $notificacions
 * @property Notificacion[] $notificacions0
 */
class User extends \yii\db\ActiveRecord
{   
    
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username', 'auth_key', 'password_hash', 'email', 'created_at', 'updated_at', 'fechaVenceToken', 'idRol', 'idUserTipo', 'accessToken', 'idGdTrdDependencia'], 'required'],
            [['status', 'created_at', 'updated_at', 'idRol', 'idUserTipo', 'intentos', 'ldap', 'idGdTrdDependencia', 'licenciaAceptada'], 'integer'],
            [['fechaVenceToken'], 'safe'],
            [['username', 'password_hash', 'password_reset_token', 'email', 'accessToken', 'verification_token'], 'string', 'max' => 255],
            [['auth_key'], 'string', 'max' => 32],
            [['username'], 'unique'],
            [['email'], 'checkEmail'],
            [['password_reset_token'], 'unique'],
            [['idGdTrdDependencia'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idGdTrdDependencia' => 'idGdTrdDependencia']],
            [['idRol'], 'exist', 'skipOnError' => true, 'targetClass' => Roles::className(), 'targetAttribute' => ['idRol' => 'idRol']],
            [['idUserTipo'], 'exist', 'skipOnError' => true, 'targetClass' => UserTipo::className(), 'targetAttribute' => ['idUserTipo' => 'idUserTipo']],
        ];
    }

    public function checkEmail($attribute, $params, $validator)
    {
        if ($this->id != null) {
            $user = User::find()->where(['id' => $this->id])->one();
        }

        if ($this->id == null || $user->email != $this->email) {

            $user = User::find()->where(['email' => $this->email]);
            if ($this->idUserTipo == Yii::$app->params['tipoUsuario']['Externo']) {
                $user->andWhere(['<>', 'idUserTipo', Yii::$app->params['tipoUsuario']['Externo']]);
            }
            $user = $user->one();

            if ($user != null) {
                $this->addError($attribute, HelperLanguageSelector::getTranslation('correoExistente') . $this->id );
            }

        }
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'username' => '',
            'auth_key' => 'Auth Key',
            'password_hash' => 'Password Hash',
            'password_reset_token' => 'Password Reset Token',
            'email' => '',
            'status' => 'Status',
            'created_at' => 'Created At',
            'updated_at' => 'Updated At',
            'fechaVenceToken' => 'Fecha Vence Token',
            'idRol' => 'Id Rol',
            'idUserTipo' => 'Id User Tipo',
            'accessToken' => 'Access Token',
            'intentos' => 'Intentos',
            'ldap' => 'Ldap',
            'verification_token' => 'Verification Token',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'licenciaAceptada' => 'licencia Aceptada'
        ];
    }

     /**
     * Gets query for [[ClientesCiudadanosDetalles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientesCiudadanosDetalles()
    {
        return $this->hasMany(ClientesCiudadanosDetalles::className(), ['idUser' => 'id']);
    }

     /**
     * Gets query for [[Notificacions]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotificacions()
    {
        return $this->hasMany(Notificacion::className(), ['idUserCreador' => 'id']);
    }

    /**
     * Gets query for [[Notificacions0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNotificacions0()
    {
        return $this->hasMany(Notificacion::className(), ['idUserNotificado' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getLogs()
    {
        return $this->hasMany(Log::className(), ['idUser' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGdTrdDependencia()
    {
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idGdTrdDependencia']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getRol()
    {
        return $this->hasOne(Roles::className(), ['idRol' => 'idRol']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserTipo()
    {
        return $this->hasOne(UserTipo::className(), ['idUserTipo' => 'idUserTipo']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserDetalles()
    {
        return $this->hasOne(UserDetalles::className(), ['idUser' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUserHistoryPasswords()
    {
        return $this->hasMany(UserHistoryPassword::className(), ['idUser' => 'id']);
    }

    /**
    * Gets query for [[RadiRadicadoAnulados]].
    *
    * @return \yii\db\ActiveQuery
    */
    public function getRadiRadicadoAnulados()
    {
        return $this->hasMany(RadiRadicadoAnulado::className(), ['idResponsable' => 'id']);
    }

    /** 
    * Gets query for [[GaPrestamos]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getGaPrestamos() 
    { 
        return $this->hasMany(GaPrestamos::className(), ['idUser' => 'id']); 
    } 
}
