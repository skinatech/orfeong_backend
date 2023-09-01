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
use api\models\User;

/**
 * Clase para enviar data al front que sirve para crear los menus de los usuarios
 */
class HelperUserMenu
{

	/**
     * Crea el menu de los usuarios que estan en el sistema
     * Crea las usuarios que se les modificará el menu
     * Crea las operaciones de los usuarios asociados
     * @param modelo con un usuarios o varios usuarios
     * @return array('idUsers' => $idUsers, 'menu' => $menu, 'operaciones' => $operacionesUsers, 'status' => true );
     */

    public static function createUserMenu($moduleUsers)
    {
        
    	$operacionesUsers = [];
      $operacionesData = [];
	    $menuFilter = [];
    	$menu = [];
    	$idUsers = [];
    	// Variable para que construya solo una vez el menu
    	$createMemu = false;

		// Verifica que si llega un modelo lo convierta en un arreglo para que se pueda ejecutar en un form para 1 o muchos
		// Si es un modelo  se debe agregar a un arreglo ARRAY
		// Si llevan varios modelos este ya viene convertido en arreglo ARRAY
    	if( !is_array($moduleUsers) ){
    		$modulosOK[] = $moduleUsers;
    	}else{
    		 $modulosOK = $moduleUsers;
    	}

    	// Verifica que la informacion que llega no este vacia
   		if( isset($modulosOK) ){

   			foreach ($modulosOK as $key => $moduleUser) {
		        
		        $idUsers[] = $moduleUser->id;
		        if( !$createMemu ){
   				
	   				foreach( $moduleUser->rol->rolesTiposOperaciones as $key => $rolTipoOperacion) {

			            $menuFilter[$rolTipoOperacion->rolOperacion->rolModuloOperacion->ordenModuloOperacion] = array(
			                'ruta' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->rutaRolModuloOperacion,
			                'idModulo' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->idRolModuloOperacion,
                      'nombre' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->nombreRolModuloOperacion,
			                'type' => 'link',
			                'icontype' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->classRolModuloOperacion,
			                'collapse' => 'collapse'.$rolTipoOperacion->rolOperacion->rolModuloOperacion->rutaRolModuloOperacion,
			                'children' => [],
			                'statusModulo' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->estadoRolModuloOperacion,
			            );
			            $operacionesUsers[] = $rolTipoOperacion->rolOperacion->nombreRolOperacion;
                  // $operacionesData Variable solo para validar si el existe algun index en las operaciones para mostrar modulo
                  $operacionesData[] = array( 'idModulo' => $rolTipoOperacion->rolOperacion->rolModuloOperacion->idRolModuloOperacion, 'ruta' => $rolTipoOperacion->rolOperacion->nombreRolOperacion );
		        	}
		        }
		        $createMemu = true;
   			}
   			// Ordena el menu
	        ksort($menuFilter);
	        // Agrega el estado a cada opcion del menu
	        foreach($menuFilter as $key => $menuFilterVal) {
	            if ($menuFilterVal['statusModulo'] == Yii::$app->params['statusTodoText']['Activo']) {
                // validaModulo Función que verifica si existe algún index para mostrar el modulo principal
                $statusValidaModulo = self::validaModulo( $menuFilterVal['idModulo'], $operacionesData );
                // Valida el retorno de la función Si existe el modulo
                if ( $statusValidaModulo == true){
                  // Agrega el modulo
	                $menu[] = $menuFilterVal;
                }
	            }
	        }

          array_push($menu, Yii::$app->params['entradasMenuFija']['about']);
          array_push($operacionesUsers, Yii::$app->params['entradasOperacionesFija']['about']['ruta']);
          array_push($operacionesData, Yii::$app->params['entradasOperacionesFija']['about']);

          // Valida si el menu esta vacio, si esta vacio debe retornar estatus false
          if ( !empty( $menu )) {
            return array('idUsers' => $idUsers, 'menu' => $menu, 'operaciones' => $operacionesUsers, 'operacionesData'=> $operacionesData, 'status' => true );
          }else{
            return array('idUsers' => $idUsers, 'menu' => $menu, 'operaciones' => $operacionesUsers, 'operacionesData'=> $operacionesData, 'status' => false );
          }

   		}else{

   			  return array('idUsers' => $idUsers, 'menu' => $menu, 'operaciones' => $operacionesUsers, 'status' => false );
   		}
    
    }
    
    /**
     * @param modelo de perfil a modificar
     * Se envia el modelo de usuarios a createUserMenu
     * @return array('idUsers' => $idUsers, 'menu' => $menu, 'operaciones' => $operacionesUsers, 'status' => true );
     */

    public static function createRolesMenu($moduleRoles)
    {

      // Consulta la tabla User por el idRol del moduleRoles para optener todos los usuarios
      $modeluser = User::find()->where(['idRol' => $moduleRoles->idRol ])->all();
      return self::createUserMenu($modeluser);

    }

    /**
    * Función que verifica si existe algún index para mostrar el modulo principal
    * @param $idModulo id del modulo
    * @param $operacionesUsers array con las operaciones
    */
    public static function validaModulo( $idModulo, $operacionesData ) {

      $existModule = false;
      // Recorre las operaciones
      foreach ($operacionesData as $key => $operacion) {
        if ( $operacion['idModulo'] == $idModulo ) {
          // Verifico su en la ruta existe la palabra index
          $pos = strpos($operacion['ruta'], 'index');
          if ($pos !== false) {
            $existModule = true;
            return $existModule;
          }
        }
      }
      return $existModule;
    }

}
