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

use api\models\Notificacion;
use Yii;

/**
* Clase para almacenar las notificaciones de todas las transacciones realizadas en los radicados
*/
class HelperNotification
{

    # Proceso que almacena los registro de las notificaciones por cada transacción del radicado
    public static function addNotification($idUserCreador, $idUserNotificado, $notificacion, $url, $idRadicado)
    {       
        # Se envia en base 64 el id Radicado
        $dataBase64Params = str_replace(array('/', '+'), array('_', '-'), base64_encode($idRadicado));
        $link = $url . $dataBase64Params;

        $modelNotification = new Notificacion();
        $modelNotification->idUserCreador = $idUserCreador;
        $modelNotification->idUserNotificado = $idUserNotificado;
        $modelNotification->notificacion = $notificacion;
        $modelNotification->urlNotificacion = $link;
        $modelNotification->creacionNotificacion = date("Y-m-d H:i:s");

        if($modelNotification->validate())  {
            $modelNotification->save();
            return true;

        } else {
            return $modelNotification->getErrors();
        }

    }

}
