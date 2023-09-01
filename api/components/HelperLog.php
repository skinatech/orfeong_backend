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
use api\models\Log;
use api\models\RadiLogRadicados;
use api\models\GdHistoricoExpedientes;
use api\models\RadiRadicados;

/**
* Clase para encriptar parametros
*/
class HelperLog
{

    public static function logAdd($type = false, $id, $username, $modulo, $evento, $dataOld, $data, $exceptions)
    {

        /***
            Ip del cliente
        ***/
        $ipCliente = "0.0.0.0";
        if(isset($_SERVER["HTTP_CLIENT_IP"]))
        {
            $ipCliente = $_SERVER["HTTP_CLIENT_IP"];
        }
        elseif(isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
        {
            $ipCliente = $_SERVER["HTTP_X_FORWARDED_FOR"];
        }
        elseif(isset($_SERVER["HTTP_X_FORWARDED"]))
        {
            $ipCliente = $_SERVER["HTTP_X_FORWARDED"];
        }
        elseif(isset($_SERVER["HTTP_FORWARDED_FOR"]))
        {
            $ipCliente = $_SERVER["HTTP_FORWARDED_FOR"];
        }
        elseif(isset($_SERVER["HTTP_FORWARDED"]))
        {
            $ipCliente = $_SERVER["HTTP_FORWARDED"];
        } else {
            $ipCliente = $_SERVER["REMOTE_ADDR"];
        }
        /***
            Fin ip del cliente
        ***/

        /***
            Valida cambios en el save de cada acción, si es manual se almacena el antes y el despues directamente sin validar atributos
        ***/
        $antes = "";
        $antesConcat = "";
        $despues = "";
        $despuesConcat = "";
        
        #Recibe el modelo $data y $dataOld como un string
        if($type){

            /***
            Guardamos la información
            ***/
            $modelLog = new Log();
            $modelLog->idUser = $id;
            $modelLog->userNameLog = $username;
            $modelLog->fechaLog = date("Y-m-d H:i:s");
            $modelLog->ipLog = $ipCliente;
            $modelLog->moduloLog = $modulo;
            $modelLog->eventoLog = $evento;
            $modelLog->antesLog = $dataOld;
            $modelLog->despuesLog = $data;

            if($modelLog->validate()) {
                $modelLog->save();

            } else {
                return array('data' => $modelLog->getErrors());
            }
            /***
                Fin
            ***/

        } else { // Sino $data y $dataOld se obtendra de los atributos del modelo

            for($i = 0; $i < count($data); $i++) {

                if(isset($data[$i])) {

                    for($j = 0; $j < count($data[$i]->Attributes()); $j++) {

                        if(!in_array($data[$i]->Attributes()[$j], $exceptions)) {

                            if(isset($dataOld[$i])) {

                                if(isset($dataOld[$i][$data[$i]->Attributes()[$j]])) {

                                    if(!strcmp($dataOld[$i][$data[$i]->Attributes()[$j]], $data[$i]->getAttributes()[$data[$i]->Attributes()[$j]]) == 0) {

                                        $antesConcat .= $data[$i]->attributeLabels()[$data[$i]->Attributes()[$j]] .": ". $dataOld[$i][$data[$i]->Attributes()[$j]] .", ";
                                        $despuesConcat .= $data[$i]->attributeLabels()[$data[$i]->Attributes()[$j]] .": ". $data[$i]->getAttributes()[$data[$i]->Attributes()[$j]] .", ";

                                    }

                                }

                            } else {

                                $despuesConcat .= $data[$i]->attributeLabels()[$data[$i]->Attributes()[$j]] .": ". $data[$i]->getAttributes()[$data[$i]->Attributes()[$j]] .", ";

                            }

                        }

                    }
                }
            }

            if(strlen($antesConcat) > 3) {
                $antes = substr($antesConcat, 0, -2);
            } else {
                $antes = $antesConcat;
            }

            if(strlen($despuesConcat) > 3) {
                $despues = substr($despuesConcat, 0, -2);
            } else {
                $despues = $despuesConcat;
            }

            /***
                Fin
            ***/

            /***
                Guardamos la información
            ***/
            $modelLog = new Log();
            $modelLog->idUser = $id;
            $modelLog->userNameLog = $username;
            $modelLog->fechaLog = date("Y-m-d H:i:s");
            $modelLog->ipLog = $ipCliente;
            $modelLog->moduloLog = $modulo;
            $modelLog->eventoLog = $evento;
            $modelLog->antesLog = $antes;
            $modelLog->despuesLog = $despues;

            if($modelLog->validate()) {
                $modelLog->save();

            } else {
                return array('data' => $modelLog->getErrors());
            }
            /***
                Fin
            ***/
        }
    }


    // Proceso de guardar el registro de la trazabilidad del radicado
    public static function logAddFiling($idUser, $idDependencia, $idRadiRadicado, $idTransaccion, $observaciones, $data, $exceptions)
    {
        /** Obtener los labels de los modelos utilizados */
        $RadiRadicados = new RadiRadicados();
        $attributeLabels = $RadiRadicados->attributeLabels();

        /***
            Valida cambios en el save de cada acción, si es manual se almacena el antes y el despues directamente sin validar atributos
        ***/
        $antes = "";
        $antesConcat = "";
        $despues = "";
        $despuesConcat = "";

        if (is_array($data)) {

            if (count($data) > 0) {

                if (isset($data[0]) && $data[0] == "Manual") {
                    $antes = $data[1];
                    $despues = $data[2];
                }
            }

        } else {

            for ($i = 0; $i < count($data->Attributes()); $i++) {

                if (!in_array($data->Attributes()[$i], $exceptions)) {

                    if (count($data->getOldAttributes()) > 0) {

                        if (isset($data->getOldAttributes()[$data->Attributes()[$i]], $data->getAttributes()[$data->Attributes()[$i]])) {

                            if (!strcmp($data->getOldAttributes()[$data->Attributes()[$i]], $data->getAttributes()[$data->Attributes()[$i]]) == 0) {

                                /** Asignar labels para la traza */
                                if ( array_key_exists($data->Attributes()[$i], $attributeLabels) ) {
                                    $label = $attributeLabels[$data->Attributes()[$i]];
                                } else {
                                    $label = $data->Attributes()[$i];
                                }
                                /** Fin Asignar labels para la traza */

                                $antesConcat .= $label .": ". $data->getOldAttributes()[$data->Attributes()[$i]] .", ";
                                $despuesConcat .= $label .": ". $data->getAttributes()[$data->Attributes()[$i]] .", ";
                            }
                        }

                    } else {
                        $despuesConcat .= $data->Attributes()[$i] .": ". $data->getAttributes()[$data->Attributes()[$i]] .", ";
                    }
                }
            }

            if(count($data->getOldAttributes()) > 0)  {
                // Elimina la coma y el espacio al final de la cadena
                $antes = substr($antesConcat, 0, -2);
                $despues = substr($despuesConcat, 0, -2);

                // $antes = str_replace(',',' ',$antesConcat);
                // $despues = str_replace(', ',' ',$despuesConcat);

            } else {
                // $antes = "-";
                // $despues = str_replace(',',' ',$despuesConcat);

                // Elimina la coma y el espacio al final de la cadena
                $despues = substr($despuesConcat, 0, -2);

            }
        }

        /***
            Guardamos la información
        ***/
        $modelLog = new RadiLogRadicados();
        $modelLog->idUser = $idUser;
        $modelLog->idDependencia = $idDependencia;
        $modelLog->idRadiRadicado = $idRadiRadicado;
        $modelLog->idTransaccion = $idTransaccion;
        $modelLog->observacionRadiLogRadicado = $observaciones. ' '.$despues;
        $modelLog->fechaRadiLogRadicado = date("Y-m-d H:i:s");

        if($modelLog->validate())  {
            $modelLog->save();            
            return true;
        } else {
            return $modelLog->getErrors();
        }
        /***
            Fin
        ***/
    }

    public static function logAddExpedient($idUser, $idDependencia, $idExpediente , $operacion, $observacion){

        
        /***
            Guardamos la información
        ***/

            $modelHistoricoExpedeinte = new GdHistoricoExpedientes();

            $modelHistoricoExpedeinte->idGdExpediente = $idExpediente;
            $modelHistoricoExpedeinte->idUser = $idUser;
            $modelHistoricoExpedeinte->idGdTrdDependencia = $idDependencia;
            $modelHistoricoExpedeinte->operacionGdHistoricoExpediente = $operacion;
            $modelHistoricoExpedeinte->observacionGdHistoricoExpediente = $observacion;

            if(!$modelHistoricoExpedeinte->save()){
                print_r($modelHistoricoExpedeinte->getErrors());die();
            }else{
                
                
            }

        /***
            Fin
        ***/


    }

    /**
     * Funcion para validar los nombres de los modulos que no estan registrados 
     * como permisos en la base de datos
    */
    public static function getDefaultModule($route)
    {
       
        if($route == 'version1/user/load-massive-file'){
            $nombreModulo = 'Carga Masiva de usuarios';

        } elseif($route == 'version1/site/login'){
            $nombreModulo = 'Inicio de sesión';

        } elseif($route == 'version1/user/logout'){
            $nombreModulo = 'Cerrar sesión';
            
        } elseif($route == 'version1/site/reset-password'){
            $nombreModulo = 'Cambio de contraseña';

        } elseif($route == 'version1/user/change-status'){
            $nombreModulo = 'Cambio de estado';

        } elseif($route == 'site/signup'  || $route == 'registro-pqrs/index' || $route == 'consulta-pqrs/index' || $route == 'consulta-pqrs/desistimiento-radicado'){
            $nombreModulo = 'Página Pública PQRSD';

        } else {
            $nombreModulo = $route;
        }

        return $nombreModulo;
    }

}
?>
