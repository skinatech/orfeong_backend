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

/**
 * Clase validar si un usuario tiene permiso de acceso a una ruta específica
 */
class HelperValidatePermits
{
    public static function validateUserPermits($route, $userIdentity)
    {
        $operacion = str_replace("/", "%", $route);

        $validacion = false;
        foreach($userIdentity as $key => $rolOperacionVal)
        {
            if($rolOperacionVal->rolOperacion->nombreRolOperacion == $operacion)
            {
                $validacion = true;
                break;
            }
        }

        return $validacion;
    }
}
