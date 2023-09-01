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

namespace api\modules\version1\controllers\correo;

use yii\web\Controller;
use Yii;

use common\models\User;
use api\components\HelperEncrypt;
 
/**
 * Clase única para envío de correos electrónicos por medio de la aplicación
 * Las respuestas emitidas por las funciones de esta clase solo son para debug de la aplicación
 */
class CorreoController extends Controller
{   
    /**
     * Renders the index view for the module
     * @return string
     */
    public static function sendEmail($email, $headMailText, $textBody, $bodyMail, $modelAttach = [], $link = null, $setSubject = 'rootedAllocation', $nameButtonLink = 'buttonLinkRadicado')
    {

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero_mail.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => $bodyMail,
                    'nameButtonLink' => Yii::t('app', $nameButtonLink),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person_mail.png'),
                    'link' => (is_null($link) || $link == '') ? Yii::$app->params['ipServer'] . 'dashboard' : $link,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', $setSubject));
            
            if(is_array($modelAttach)){

                foreach ($modelAttach as $file) {
                    $envioCorreo->attach($file['rutaRadiDocumento'] .$file['nombreRadiDocumento']);                    
                }

            }

            $envioCorreo->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'motivo' => "No se pudo enviar el correo", // Utilizado solo para debug, no para dar respuesta al usuario
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(), // Utilizado solo para debug, no para dar respuesta al usuario
            ];
        }
    }

    /**
     * Funcion para envío de correo de registro en el sistema 
     */
    public static function registro($email, $headMailText, $textBody)
    {
        $user = User::findOne(['email' => $email]);

        if (!$user) {
            return [
                'status' => false,
                'response' => 'No existe El usuario'
            ];
        }

        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return [
                    'status' => false,
                    'response' => 'No se pudo generar token para recuperar password'
                ];
            }
        }

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => 'registroUsuario-html',
                    'nameButtonLink' => Yii::t('app', 'buttonLinkEstablecer'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => Yii::$app->params['ipServer'].'resettokenpass/'.HelperEncrypt::encrypt($user->password_reset_token),
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', 'BienvenidoA'))
            ->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'response' => $envioCorreo,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

     /**
     * Funcion para envío de correo de registro en el sistema 
     */
    public function registroPqrs($email, $headMailText, $textBody)
    {
        $user = User::findOne(['email' => $email]);

        if (!$user) {
            return [
                'status' => false,
                'response' => 'No existe El usuario'
            ];
        }

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        $login = Yii::$app->urlManager->createAbsoluteUrl(['site/login']);

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => 'registroUsuario-html',
                    'nameButtonLink' => Yii::t('app', 'buttonLinkCron'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => $login,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', 'BienvenidoA'))
            ->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'response' => $envioCorreo,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

    /**
     * Funcion para envío de correo de recuperación o reestablecimiento de contraseña
     */
    public static function resetPassword($email, $username, $userPqrs = false)
    {
        $user = User::findOne([
            'email' => $email,
            'username' => $username,
        ]);

        if ($user == null) {
            return [
                'status' => false,
                'response' => 'No existe El usuario'
            ];
        }

        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return [
                    'errors' => $user->getErrors(),
                    'status' => false,
                    'response' => 'No se pudo generar token para recuperar password'
                ];
            }
        }

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        if($userPqrs){
            $resetLink = Yii::$app->urlManager->createAbsoluteUrl(['site/reset-password', 'token' => $user->password_reset_token]);
        }else{
            $resetLink = Yii::$app->params['ipServer'].'resettokenpass/'.HelperEncrypt::encrypt($user->password_reset_token);
        }

        try {
            
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => Yii::t('app','headMailTextResetPassword'),
                    'textBody' => Yii::t('app','textBodyResetPassword'),
                    'bodyMail' => 'passwordResetToken-html',
                    'nameButtonLink' => Yii::t('app', 'buttonLinkRestablecer'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => $resetLink,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', 'subjectResetPassword'))
            ->send();

            // echo '<pre>';
            // print_r($resetLink);
            // echo '</pre>';
            // die();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                
                return [
                    'status' => false,
                    'response' => $envioCorreo,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

     /**
     * Funcion para envío de correo de establecer  contraseña para usuarios creados en gestion de terceros 
     */
    public static function personalizePassword($email, $headMailText, $textBody, $bodyMail, $subjectMail, $link = false)
    {
        $user = User::findOne([ 'email' => $email,]);

        if (!$user) {
            return [
                'status' => false,
                'response' => 'No existe El usuario'
            ];
        }

        if (!User::isPasswordResetTokenValid($user->password_reset_token)) {
            $user->generatePasswordResetToken();
            if (!$user->save()) {
                return [
                    'status' => false,
                    'response' => 'No se pudo generar token para recuperar password'
                ];
            }
        }

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => 'registroUsuario-html',
                    'nameButtonLink' => Yii::t('app', 'buttonLinkEstablecer'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => Yii::$app->params['ipServer'].'resettokenpass/'.HelperEncrypt::encrypt($user->password_reset_token),
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', 'BienvenidoA'))
            ->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'response' => $envioCorreo,
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

    public static function radicacion($email, $headMailText, $textBody, $bodyMail, $idRadiRadicado, $setSubject = 'rootedAllocation')
    {

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => $bodyMail,
                    'nameButtonLink' => Yii::t('app', 'buttonLinkRadicado'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => Yii::$app->params['ipServer'].'filing/filing-view/'.$idRadiRadicado,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', $setSubject))
            ->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'response' => $e->getMessage(),
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

    public static function envioAdjuntos($email, $headMailText, $textBody, $bodyMail, $modelAttach, $link = '', $setSubject = 'rootedAllocation', $nameButtonLink = 'buttonLinkRadicado', $buttonDisplay = true)
    {

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => $bodyMail,
                    'buttonDisplay' => $buttonDisplay,
                    'nameButtonLink' => Yii::t('app', $nameButtonLink),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => Yii::$app->params['ipServer'].'filing/filing-view/'.$link,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', $setSubject));
            
            if(is_array($modelAttach)){

                foreach ($modelAttach as $file) {
                    $envioCorreo->attach($file);
                }

            }

            $envioCorreo->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    //'response' => $e->getMessage(),
                    'motivo' => "No se pudo enviar el correo"
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }

    /** 
     * Envio de correo para diferentes radicaciones, con un asunto especifico
     * @param $email [string] [Correo al que se va a enviar]
     * @param $headMailText [String] [Encabezado del email]
     * @param $textBody [String] [Contenido del cuerpo del correo]
     * @param $bodyMail [Estructura de html del email]
     * @param $idRadiRadicado [int] [id del radicado en base 64]
     * @param $subject [String] [Texto del asunto del correo]
     * @param $radicadoUnico [String] [determina si hay un unico radicado]
     **/
    public static function differentFilings($email, $headMailText, $textBody, $bodyMail, $idRadiRadicado, $subject, $radicadoUnico=false)
    {

        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        if($radicadoUnico){
            $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($idRadiRadicado));
            $link = Yii::$app->params['ipServer'].'filing/filing-view/'.$dataBase64Params;
        }else{
            $link=  Yii::$app->params['ipServer'].'filing/filing-index/false';
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => $bodyMail,
                    'nameButtonLink' => Yii::t('app', 'buttonLinkRadicado'),
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => $link,                    
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject($subject)
            ->send();

            if ($envioCorreo == true){
                return [
                    'status' => true,
                    'response' => $envioCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    'response' => $envioCorreo,
                ];
            }
        } catch (\Exception $e) {            
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }
   
    /**
     * Envia correo con archivo adjunto si se requiere o con una tabla en el cuerpo del correo,
     * además se tiene la opción de que no haya un botón de redirección.
     * @param $email [string] [Correo al que se va a enviar]
     * @param $headMailText [String] [Encabezado del email]
     * @param $textBody [String] [Contenido del cuerpo del correo]
     * @param $bodyMail [Estructura de html del email]
     * @param $modelAttach [string] [ruta y nombre del archivo adjunto]
     * @param $setSubject [String] [Texto del asunto del correo] 
     * @param $buttonDisplay [boleano] [Si es true aparece el boton] (opcional) 
     * @param $params [array] [Array donde contiene la construccion de una tabla] (opcional) 
     * @param $link [string] [Ruta de redireccionamiento] (opcional) 
     * @param $nameButtonLink [string] [Nombre del botón]  (opcional) 
     **/
    public static function addFile($email, $headMailText, $textBody, $bodyMail, $modelAttach, $setSubject, $buttonDisplay = true, $params = null, $link = false, $nameButtonLink = false)
    {
        //Verificar variable de debug de correo para saber si se puede enviar el correo al destinatario o al correo de pruebas 
        if (Yii::$app->params['debugMailActive'] == true) {
            $email = Yii::$app->params['debugEmail'];
        }

        if($link){
            $link=  Yii::$app->params['ipServer'].'dashboard/';
        } 

        if($nameButtonLink){
            $nameButtonLink = Yii::t('app', 'buttonLinkCron');
        }

        try {
            $envioCorreo = Yii::$app->mailer->compose(
                ['html' => 'email_html'],
                [
                    'imgCabezado' => Yii::getAlias('@api/web/img/logo_header_correo.jpg'),
                    'imgLogo' => Yii::getAlias('@api/web/img/logo_correo.png'),
                    'imgLogoCliente' => Yii::getAlias('@api/web/img/vertical-color_aero.png'),
                    'headMailText' => $headMailText,
                    'textBody' => $textBody,
                    'bodyMail' => $bodyMail,
                    'buttonDisplay' => $buttonDisplay,
                    'nameButtonLink' => $nameButtonLink,
                    'iconButtonLink' => Yii::getAlias('@api/web/img/icon-person.png'),
                    'link' => $link,
                    'params' => $params,
                    'textFooter' => Yii::t('app', 'mailTextFooter'),
                ]
            )
            ->setTo($email)
            ->setFrom([Yii::$app->params['supportEmail'] => Yii::$app->name])
            ->setSubject(Yii::t('app', $setSubject));

            # Se adjunta archivo al correo
            if(!is_null($modelAttach)){
                $envioCorreo->attach($modelAttach); 
            }            

            $statusCorreo = $envioCorreo->send();

            if ($statusCorreo == true){
                return [
                    'status' => true,
                    'response' => $statusCorreo,
                ];
            } else {
                return [
                    'status' => false,
                    //'response' => $e->getMessage(),
                    'motivo' => "No se pudo enviar el correo", // Utilizado solo para debug, no para dar respuesta al usuario
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => false,
                'response' => $e->getMessage(),
            ];
        }
    }
}
