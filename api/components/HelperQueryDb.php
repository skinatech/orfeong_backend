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
 * Clase para complementar los querys, adaptandolos al motor de base de datos que se esté manejando
 */
class HelperQueryDb
{
    public static function getQuery($metodo, $query, $tabla = '', $params = [], $paramsAdd = [])
    {
        $motorDB = Yii::$app->params['motorDB'];

        switch ($metodo) {
			case 'innerJoinAlias': case 'leftJoinAlias': case 'rightJoinAlias' :

				$nombreMetodo = substr($metodo, 0, -5);

    			$code = '';
        		if ($motorDB == 'MYSQL' || $motorDB == 'MSSQL') {
        			foreach ($params as $key => $value) {
        				$code .= $key . '.' . $value . ' = ';
        			}
        		} elseif ($motorDB == 'ORACLE') {
        			$code = '';
        			foreach ($params as $key => $value) {
        				$code .= $key . '."' . $value . '" = ';
        			}
                } elseif ($motorDB == 'POSTGRESQL') {
                    $code = '';
                    foreach ($params as $key => $value) {
                        $code .= '"' . $key . '"."' . $value . '" = ';
                    }
                }
    			$code = substr($code, 0, -3);
                $code = self::proccesAndJoin($motorDB, $metodo, $code, $paramsAdd);
    			$query->$nombreMetodo($tabla, $code);

        	break;

        	case 'innerJoin': case 'leftJoin': case 'rightJoin' :

    			$code = '';
        		if ($motorDB == 'MYSQL' || $motorDB == 'MSSQL') {
        			foreach ($params as $key => $value) {
        				$code .= $key . '.' . $value . ' = ';
        			}
        		} elseif ($motorDB == 'POSTGRESQL' || $motorDB == 'ORACLE') {
        			$code = '';
        			foreach ($params as $key => $value) {
        				$code .= '"' . $key . '"."' . $value . '" = ';
        			}
        		}
    			$code = substr($code, 0, -3);
                $code = self::proccesAndJoin($motorDB, $metodo, $code, $paramsAdd);
    			$query->$metodo($tabla, $code);

        	break;
        }

        return $query;
    }

    /** Función para procesar los filtros adicionales en el join */
    public static function proccesAndJoin($motorDB, $metodo, $code, $paramsAdd)
    {
        if (!empty($paramsAdd)) {
            switch ($metodo) {
                case 'innerJoinAlias': case 'leftJoinAlias': case 'rightJoinAlias' :
                    if ($motorDB == 'MYSQL' || $motorDB == 'MSSQL') {
                        $delimiterTbl = '';
                        $delimiterInput = '';
                    } elseif ($motorDB == 'ORACLE') {
                        $delimiterTbl = '';
                        $delimiterInput = '"';
                    } elseif ($motorDB == 'POSTGRESQL') {
                        $delimiterTbl = '"';
                        $delimiterInput = '"';
                    }
                break;
                case 'innerJoin': case 'leftJoin': case 'rightJoin' :
                    if ($motorDB == 'MYSQL' || $motorDB == 'MSSQL') {
                        $delimiterTbl = '';
                        $delimiterInput = '';
                    } elseif ($motorDB == 'POSTGRESQL' || $motorDB == 'ORACLE') {
                        $delimiterTbl = '"';
                        $delimiterInput = '"';
                    }
                break;
            }

            $codeAdd = '';
            foreach ($paramsAdd as $key => $value) {
                $codeAdd .= ' '.$value['operador'].' ';

                $codeAdd .= $delimiterTbl .$value['tbl1']. $delimiterTbl .'.'. $delimiterInput .$value['value1']. $delimiterInput;

                if ($value['type'] == 'relationInput') {
                    $codeAdd .= '=' . $delimiterTbl .$value['tbl2']. $delimiterTbl .'.'. $delimiterInput .$value['value2']. $delimiterInput;
                } elseif ($value['type'] == 'valueInput') {
                    if (isset($value['comparador2']) && $value['comparador2'] == 'IN') {
                        $codeAdd .= " IN (" . implode(', ', $value['value2']) . ")";
                    } else {
                        $codeAdd .= " = '" . $value['value2'] . "'";
                    }
                }
            }

            return $code . $codeAdd;
        } else {
            return $code;
        }
    }

    /** Función para generar el campo select count para el apartado "SELECT" */
    public static function getColumnCount($tabla = null, $campo, $alias = null)
    {
        $motorDB = Yii::$app->params['motorDB'];

        if ($motorDB == 'MYSQL' || $motorDB == 'MSSQL') {
        	$tabla = ($tabla != null) ? $tabla.'.' : '';
        	$alias = ($alias != null) ? ' AS ' . $alias : '';
        	return 'COUNT('.$tabla.$campo.')' . $alias;
        } elseif ($motorDB == 'POSTGRESQL' || $motorDB == 'ORACLE') {
        	$tabla = ($tabla != null) ? '"'.$tabla.'".' : '';
        	$alias = ($alias != null) ? ' AS ' . $alias : '';
        	return 'COUNT('.$tabla. '"'.$campo.'"' .')' . $alias;
        }
	}

	public static function getContentStream($campo)
    {
        if (Yii::$app->params['motorDB'] == 'ORACLE') {
            if ($campo != null)  {
                $text = stream_get_contents($campo);
            } else {
                $text = $campo;
            }
        }else{
            $text = $campo;
        }
        
        return $text;
    }	

}
