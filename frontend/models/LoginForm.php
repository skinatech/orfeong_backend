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
use yii\base\Model;

/**
 * Login form
 */
class LoginForm extends Model
{
    public $username;
    public $password;
    public $rememberMe = true;
    public $ldap = false; // Variable para determinar si el logueo es por el directorio activo de ldap
    public $reCaptcha;

    private $_user;

    public $scenario = 'loginManual';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        /** Se valida el scenario para determinar si es un logueo manual o automático */
        if ($this->scenario == 'loginManual') {
            return [
                // username and password are both required
                [['username', 'password'], 'required'],
                // rememberMe must be a boolean value
                ['rememberMe', 'boolean'],
                // password is validated by validatePassword()
                ['password', 'validatePassword'],
                //  [['reCaptcha'], \himiklab\yii2\recaptcha\ReCaptchaValidator2::className(), 'uncheckedMessage' => 'Por favor confirma que no eres un robot'],
                // [['reCaptcha'], \kekaadrenalin\recaptcha3\ReCaptchaValidator::className(), 'acceptance_score' => 0]
            ];
        } else {
            return [
                // username and password are both required
                [['username', 'password'], 'required'],
                // rememberMe must be a boolean value
                ['rememberMe', 'boolean'],
                // password is validated by validatePassword()
                ['password', 'validatePassword'],
            ];
        }
    }

    /**
     * Validates the password.
     * This method serves as the inline validation for password.
     *
     * @param string $attribute the attribute currently being validated
     * @param array $params the additional name-value pairs given in the rule
     */
    public function validatePassword($attribute, $params)
    {
        if (!$this->hasErrors()) {
            $user = $this->getUser();

            if ($user) {
                if (!$user->validatePassword($this->password)  && $this->ldap == false) {
                    $this->addError($attribute, Yii::t('app', 'Incorrect username or password'));
                }

                else if ($user->status == Yii::$app->params['statusTodoText']['Inactivo']) {
                    $this->addError($attribute, Yii::t('app', 'Inactive User'));
                }

            } else {
                $this->addError($attribute, Yii::t('app', 'Incorrect username or password'));
            }
        }
    }

    /**
     * Logs in a user using the provided username and password.
     *
     * @return bool whether the user is logged in successfully
     */
    public function login()
    {
        if ($this->validate()) {

            /**
            * Proceso para actualizar el token y la fecha del vencimiento del mismo
            **/
            $user = $this->getUser();
            $fechaActual = date("Y-m-d");
            $user->fechaVenceToken = date("Y-m-d", strtotime($fechaActual ." + ". Yii::$app->params['TimeExpireToken'] .' days'));
            $infoUser = new User(); 
            $user->accessToken = $infoUser->generateAccessToken();
            $user->save();

            return Yii::$app->user->login($this->getUser(), $this->rememberMe ? 3600 * 24 * 30 : 0);
        }

        return false;
    }

    /**
     * Finds user by [[username]]
     *
     * @return User|null
     */
    protected function getUser()
    {
        if ($this->_user === null) {
            $this->_user = User::findByUsernameNoStatus($this->username);
        }

        return $this->_user;
    }

    public function attributeLabels()
    {
        return [
            'username' => '',
            'password' => '',
            'email'    => ''
        ];
    }
    
}
