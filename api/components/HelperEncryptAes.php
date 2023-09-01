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

namespace api\components;

use Yii;
use Blocktrail\CryptoJSAES\CryptoJSAES;
use yii\web\NotFoundHttpException;

/**
 * Clase para Encriptar y desencriptar parametros por el método de encriptación AES
 */
class HelperEncryptAes {

    /**
     * Función que encripta una cadena de texto
     * @input array $data "Variable a encriptar"
     * @input string $loggedIn = "Define si el usuario esta logueado o no para encriptar con la llave por defecto o con el token de sesión"
     * @output string "cadena encriptada"
     */
    public static function encrypt($data, $loggedIn = false)
    {    
        if ($loggedIn == false) {
            $key = Yii::$app->params['llaveAES'];
        }
        elseif(isset(Yii::$app->request->headers['authorization'])) {
            $key = substr(Yii::$app->request->headers['authorization'], 7) . Yii::$app->params['llaveAES'];
        }
        else{
            $key = Yii::$app->params['llaveAES'];
        }
        
        $jsonData = json_encode($data);
        $encrypted = CryptoJSAES::encrypt($jsonData, $key);
        if (Yii::$app->params['debugAES'] == true) {
            return [
                'encrypted' => $encrypted,
                'decrypted' => $data,
                'loggedIn' => $loggedIn
            ];
        } else {
            return [
                'encrypted' => $encrypted,
            ];
        }
    }
    
    /**
     * Función que desencripta una cadena en formato AES
     * @input string $encrypted "Cadena a desencriptar"
     * @input boolean $loggedIn = "Define si el usuario esta logueado o no para encriptar con la llave por defecto o con el token de sesión"
     * @output array[
     *      request string "cadena desencriptada"
     *      status boolean "Define si la cadena se pudo desencriptar o no"
     * ]
     */
    public static function decrypt($encrypted, $loggedIn = false){
        try {
            if ($loggedIn == false) {
                $key = Yii::$app->params['llaveAES'];
            } else {
                //Validar si existe el header autorization
                if (!Yii::$app->request->headers['authorization']) {
                    throw new NotFoundHttpException('Sin autorización.');
                }
                $key = substr(Yii::$app->request->headers['authorization'], 7) . Yii::$app->params['llaveAES'];
            }
            $decrypted = CryptoJSAES::decrypt($encrypted, $key);
            $decrypted = json_decode($decrypted, true);
        } catch (\Throwable $th) {
            return [
                'request' => $th,
                'status' => false,
            ];
        }
        if ($decrypted == null) { // Cuando el formato es desencriptable pero la cadena no retorna resultados
            return [
                'request' => $decrypted,
                'status' => false
            ];
        } else {
            return [
                'request' => $decrypted,
                'status' => true
            ];
        }
    }

    /**
     * Función que desencripta una cadena en formato AES proveniente de una peticion get
     * @input string $encrypted "Cadena a desencriptar"
     * @input boolean $loggedIn = "Define si el usuario esta logueado o no para encriptar con la llave por defecto o con el token de sesión"
     * @output array[
     *      request string "cadena desencriptada"
     *      status boolean "Define si la cadena se pudo desencriptar o no"
     * ]
     */
    public static function decryptGET($encrypted, $loggedIn = false){
        try {
            if ($loggedIn == false) {
                $key = Yii::$app->params['llaveAES'];
            } else {
                //Validar si existe el header autorization
                if (!Yii::$app->request->headers['authorization']) {
                    throw new NotFoundHttpException('Sin autorización.');
                }
                $key = substr(Yii::$app->request->headers['authorization'], 7) . Yii::$app->params['llaveAES'];
            }
            $encrypted = str_replace(array('_', '-'), array('/', '+'), $encrypted);
            $encrypted = base64_decode($encrypted);
            $decrypted = CryptoJSAES::decrypt($encrypted, $key);
            $decrypted = json_decode($decrypted, true);
        } catch (\Throwable $th) {
            return [
                'request' => $th,
                'status' => false,
            ];
        }
        if ($decrypted == null) { // Cuando el formato es desencriptable pero la cadena no retorna resultados
            return [
                'request' => $decrypted,
                'status' => false
            ];
        } else {
            return [
                'request' => $decrypted,
                'status' => true
            ];
        }
        
    }
}
