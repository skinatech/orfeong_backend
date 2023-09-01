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
namespace frontend\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use api\models\Roles;
use api\models\UserTipo;
use api\models\UserDetalles;

use api\components\HelperLanguageSelector;
use api\models\ClientesCiudadanosDetalles;
use api\models\RadiLogRadicados;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $verification_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 * @property datetime $fechaVenceToken
 * @property integer intentos
 */
class User extends ActiveRecord implements IdentityInterface
{
    //const STATUS_DELETED = 0;
    const STATUS_INACTIVE = 0;
    const STATUS_ACTIVE = 10;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['username','email','idRol','idUserTipo'], 'required'],
            ['status', 'default', 'value' => self::STATUS_INACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]],
            ['ldap', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_INACTIVE]], //agregado
            [['created_at', 'updated_at', 'idRol', 'idUserTipo', 'intentos'], 'integer'],
            [['fechaVenceToken'], 'safe'],
            [['username'], 'string', 'max' => 80, 'tooLong'=> Yii::t('app', 'tooLong', [
                'attribute' =>  '{attribute}',
                'max'   => 80,
            ]) ],
            [['username'], 'unique', 'message' => HelperLanguageSelector::getTranslation('userExistente')],
            // [['email'], 'unique', 'message' => HelperLanguageSelector::getTranslation('correoExistente')],
            [['email'], 'checkEmail'],
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

    public function attributeLabels()
    {
        return [
            'id' => 'id Usuario',
            'username' => 'Usuario',
            'auth_key' => 'Auth',
            'password_hash' => 'Contraseña',
            'password_reset_token' => 'Token contraseña',
            'email' => 'Correo Electrónico',
            'status' => 'Estado',
            'created_at' => 'Fecha creación',
            'updated_at' => 'Fecha actualización',
            'fechaVenceToken' => 'Fecha vencimiento token',
            'idRol' => 'Tipo de rol',
            'idUserTipo' => 'Tipo de usuario',
            'accessToken' => 'Token de acceso',
            'intentos' => 'Intentos',
            'ldap' => 'Autenticación externa',
            'verification_token' => 'Verificación token',
            'idGdTrdDependencia' => 'Dependencia',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        $user = static::find()->where(['accessToken' => $token, 'status' => self::STATUS_ACTIVE])->one();
        if(!$user) {
            return false;
        }

        if($user->fechaVenceToken < date("Y-m-d")) {
            return false;
        } else {
            return $user;
        }

        return static::findOne(['accessToken' => $token, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {   
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by username sin verificar status
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsernameNoStatus($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds user by verification email token
     *
     * @param string $token verify email token
     * @return static|null
     */
    public static function findByVerificationToken($token) {
        return static::findOne([
            'verification_token' => $token,
            'status' => self::STATUS_INACTIVE
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int) substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
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
    public function getRadiLogRadicados()
    {
        return $this->hasMany(RadiLogRadicados::className(), ['idUser' => 'id']);
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    public function generateEmailVerificationToken()
    {
        $this->verification_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    /**
     * genera el password incial (temporal)
     * **/
    public function generatePassword()
    {
        return rand(5, 15);
    }

    /**
    *   Función que genera un token para el usuario
    **/

    public function generateAccessToken()
    {
        return Yii::$app->security->generateRandomString() ."_". time();
    }
}
