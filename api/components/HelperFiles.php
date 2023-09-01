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
use yii\helpers\FileHelper;

use api\models\CgGeneral;


class HelperFiles {

    public static function validateCgTamanoArchivo($fileUpload){

        $modelCgGeneral = CgGeneral::findOne(['estadoCgGeneral' =>  Yii::$app->params['statusTodoText']['Activo']]);

        if(!is_null($modelCgGeneral)){
                    
            $tamanoArchivoOrfeo = (int) $modelCgGeneral->tamanoArchivoCgGeneral;
            $tamañoArchivoSubido =   $fileUpload->size /  1000000;         
            
            if($tamañoArchivoSubido > $tamanoArchivoOrfeo){                        

                return [
                    'ok' => false,
                    'data' => [
                        'orfeoMaxFileSize' =>  $tamanoArchivoOrfeo
                    ]
                ];

            } else{
                return [
                    'ok' => true,
                    'data' => [
                        'orfeoMaxFileSize' =>  $tamanoArchivoOrfeo
                    ]
                ];
            }                 

        }

    }

    public static function validateSignDocument($fileUpload)
    {
        try {

            $json = json_decode(pdfsig_php($fileUpload));

        } catch (\Throwable $th) {
            $return = [
                'request' => $th,
                'status'  => false,
                'error'   => 'No se pudo comprobar la firma',
                'data'    => [],
            ];
        }

        $arraySigns = [];

        for ($i = 0; $i < count($json); $i++) {

            $str = $json[$i]->certificados[0]->certificado;
            $ssl = openssl_x509_parse($str);

            $arraySigns[] = [
                'estadoFirma'   => $json[$i]->estadoFirma,
                'fechaFirma'    => $json[$i]->fechaFirma,
                'fechaFirmaF'   => self::format($json[$i]->fechaFirma),
                'usuarioFirma'  => $json[$i]->certificados[0]->nombre,
                'sslName'       => $ssl['name'],
                'CI'            => self::getOID('1.3.6.1.4.1.23267.2.3', $ssl['name']),
                'certificado'   => $json[$i]->certificados[0]->certificado,
            ];
        }

        return [
            'status'     => true,
            'data' => $arraySigns,
            'countSign'  => count($json),
        ];

    }

    public static function format($fecha)
    {
        $year = substr($fecha, 2, 4);
        $month = substr($fecha, 6, 2);
        $day = substr($fecha, 8, 2);
        $hour = substr($fecha, 10, 2);
        $min = substr($fecha, 12, 2);
        $seg = substr($fecha, 14, 2);
        return $year.'-'.$month.'-'.$day.' '.$hour.':'.$min.':'.$seg;
    }

    public static function getOID($OID, $ssl)
    {
        preg_match('/\/' . $OID  . '=([^\/]+)/', $ssl, $matches);
        return $matches[1];
    }
}
