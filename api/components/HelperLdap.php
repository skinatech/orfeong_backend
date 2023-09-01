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

/**
 * Helper para validar conexiones LDAP
 */
class HelperLdap {

    /** 
     * Funcion de conexion con LDAP
     * @param $username [Usuario LDAP]
     * @param $password [Password LDAP]
     */
    public static function loginLdap($username, $password)
    {
        // Variables de conexion LDAP
        $ldapServer = Yii::$app->params['ldapServer'];
        $cadenaBusqLDAP = Yii::$app->params['cadenaBusqLDAP'];
        $campoBusqLDAP = Yii::$app->params['campoBusqLDAP'];
        $adminLDAP = Yii::$app->params['adminLDAP'];
        $paswLDAP = Yii::$app->params['paswLDAP'];

        $status = false;
        $error = '';

        //Valida la conexión
        if ($connect = ldap_connect($ldapServer)) {

            ldap_set_option($connect, LDAP_OPT_PROTOCOL_VERSION, 3);
            ldap_set_option($connect, LDAP_OPT_REFERRALS, 0);

            // Realiza la autenticación con un servidor LDAP
            if ((ldap_bind($connect, $adminLDAP, $paswLDAP)) == true) {

                // busca el usuario
                if (($res_id = ldap_search($connect, "$cadenaBusqLDAP", "($campoBusqLDAP=$username)")) == true) {
                    //Solo un usuario encontrado
                    if (ldap_count_entries($connect, $res_id) == 1) {

                        if (($entry_id = ldap_first_entry($connect, $res_id)) == true) {

                            //DN del usuario encontrado
                            if (($user_dn = ldap_get_dn($connect, $entry_id)) == true) {

                                //Valida que la contraseña corresponda a la de LDAP y conecta al usuario
                                try {
                                    $authenticated = ldap_bind($connect, $user_dn, $password);

                                } catch (ErrorException $e) {

                                    return [
                                        'status' => $status,
                                        'data' => Yii::t('app', 'Incorrect username or password ldap'),
                                    ];
                                }

                                //if ((ldap_bind($connect, $user_dn, $password) ) == true) {
                                $status = true;
                                $error = '';
                                //}
                            }
                        }
                    }
                } else {
                    return [
                        'status' => $status,
                        'data' => 'error usuario',
                    ];
                }
            } else {
                return [
                    'status' => $status,
                    'data' => 'Error en la autentificación del servidor',
                ];
            }

            ldap_close($connect);
            return [
                'status' => $status,
                'data' => $error,
            ];

        } else {
            return [
                'status' => $status,
                'data' => 'No hay conexión',
            ];
        }
    }

}
