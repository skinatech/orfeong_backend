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

use api\models\GdIndices;
use api\models\User;
use api\models\UserDetalles;
use api\models\RadiRadicados;
use api\models\RadiDocumentos;
use api\models\RadiDocumentosPrincipales;
use api\models\GdExpedientes;
use api\models\GdExpedientesInclusion;
use api\models\GdTrdTiposDocumentales;


class HelperIndiceElectronico {    

    public static function addFilingDocumentsToIndex($idRadicado, $modelExpedienteInclusion){   
        
        $xml = self::getXmlParams($modelExpedienteInclusion->gdExpediente);

        $modelRadicado = RadiRadicados::find()
            ->where(['idRadiRadicado' => $idRadicado])
        ->one();

        $docuemntosPrincipales = RadiDocumentosPrincipales::find()
            ->where(['idRadiRadicado' => $idRadicado])
            //->andWhere(['imagenPrincipalRadiDocumento' => Yii::$app->params['statusTodoText']['Activo']])
            ->andWhere(['estadoRadiDocumentoPrincipal' => Yii::$app->params['statusDocsPrincipales']['Firmado']])
        ->asArray()->all();

        $docuemntos = RadiDocumentos::find()
            ->where(['idRadiRadicado' => $idRadicado])            
        ->asArray()->all();
       
        $documentosMerge = array_merge($docuemntosPrincipales, $docuemntos);

        foreach($documentosMerge as $documentoItem){            
            $result = self::addDocumentToIndex($documentoItem, $modelExpedienteInclusion, $xml, $modelRadicado);
            if(!$result['ok']){

                return [
                    'ok' => false,
                    'data' => [
                        'response' => $result['data']['response'],
                    ]
                ];
                
            }
        }
        
        return [
            'ok' => true
        ];

    }    

    /**
     * Función donde valida que se ha agregado un documento ya sea anexo o plantilla
     * @param $documento [Model de RadiDocumentos o RadiDocumentosPrincipales]
     * @param $modelExpedienteInclusion [Model de expediente inclusion]
     *  */
    public static function addDocumentToIndex($documento, $modelExpedienteInclusion, $xml, $radicado=null){        
        
        # Se obtiene la numeración del expediente
        $modelExpediente = $modelExpedienteInclusion->gdExpediente;
        $orden = $modelExpediente->numeracionGdExpediente;
        $orden++;

        # Datos del usuario
        $modelUser = User::find()
            ->where(['id' => $documento['idUser']])
        ->one();        
        
        $usuario = $modelUser->userDetalles->nombreUserDetalles . ' ' . $modelUser->userDetalles->apellidoUserDetalles;
        $userDependencia = $modelUser->gdTrdDependencia->nombreGdTrdDependencia;
        
        # Se almacena información a indices.
        $modelIndice = new GdIndices();

        # Si el estado es combinación de correspondencia se agrega la información de la planilla al indice
        if(isset($documento['estadoRadiDocumentoPrincipal'])){

            # Una ves se valida que es imagen principal para poderlo agregar al indece se valida que sea firmado
            if($documento['estadoRadiDocumentoPrincipal'] == Yii::$app->params['statusDocsPrincipales']['Firmado']){
                $modelIndice->indiceContenidoGdIndice = $modelExpediente->numeroGdExpediente .$orden .'TD';
                $modelIndice->idGdExpedienteInclusion = $modelExpedienteInclusion->idGdExpedienteInclusion;
                $modelIndice->valorHuellaGdIndice = $xml['hash']['encryptedData']['encrypted'];
                $modelIndice->funcionResumenGdIndice = $xml['hash']['function'];
                $modelIndice->ordenDocumentoGdIndice = $orden;
                $modelIndice->tamanoGdIndice = $documento['tamanoRadiDocumentoPrincipal'];
                $modelIndice->rutaXmlGdIndice = $xml['rutaXml'];
                $modelIndice->nombreDocumentoGdIndice = $documento['nombreRadiDocumentoPrincipal'];
                $modelIndice->idGdTrdTipoDocumental = $radicado['idTrdTipoDocumental'];
                $modelIndice->creacionDocumentoGdIndice = $documento['creacionRadiDocumentoPrincipal'];
                $modelIndice->formatoDocumentoGdIndice = $documento['extensionRadiDocumentoPrincipal'];
                $modelIndice->descripcionGdIndice =  'Documento principal';
                $modelIndice->usuarioGdIndice = $usuario . ' - ' .  $userDependencia;
                $modelIndice->origenGdIndice = Yii::$app->params['origen']['Electronico'];
            } else {
                // No se necesita indexar al expediente
                return [
                    'ok' => true,
                ];
            }


        } else { //Sino es un anexo y almacena la información de radiDocumentos.          

            $modelIndice->indiceContenidoGdIndice = $modelExpediente->numeroGdExpediente .$orden .'TD'; 
            $modelIndice->idGdExpedienteInclusion = $modelExpedienteInclusion->idGdExpedienteInclusion;
            $modelIndice->valorHuellaGdIndice = $xml['hash']['encryptedData']['encrypted'];
            $modelIndice->funcionResumenGdIndice = $xml['hash']['function'];
            $modelIndice->ordenDocumentoGdIndice = $orden;
            $modelIndice->tamanoGdIndice = $documento['tamanoRadiDocumento'];
            $modelIndice->rutaXmlGdIndice = $xml['rutaXml'];
            $modelIndice->nombreDocumentoGdIndice = $documento['nombreRadiDocumento'];
            $modelIndice->idGdTrdTipoDocumental = $documento['idGdTrdTipoDocumental'];
            $modelIndice->creacionDocumentoGdIndice = $documento['creacionRadiDocumento'];
            $modelIndice->formatoDocumentoGdIndice = $documento['extencionRadiDocumento'];
            $modelIndice->descripcionGdIndice =  $documento['descripcionRadiDocumento'];
            $modelIndice->usuarioGdIndice = $usuario . ' - ' .  $userDependencia;
            $modelIndice->origenGdIndice = Yii::$app->params['origen']['Digitalizado'];
        }  

        if($modelIndice->save()){

            $numeroExpediente = $modelExpediente->numeroGdExpediente;
            $fechaIncorporacion = $modelExpedienteInclusion->creacionGdExpedienteInclusion;

            # Genera el xml
            $generacionIndice = self::generateIndiceXml($modelIndice, $numeroExpediente, $fechaIncorporacion);        
            if(!$generacionIndice['ok']){                
                return [
                    'ok' => false,
                    'data' => [
                        'response' => [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $generacionIndice['data']['message'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ]
                    ]
                ];
            }
            $modelExpediente->numeracionGdExpediente = $orden;
            $modelExpediente->save();

        } else {            
            $return = [
                'ok' => false,
                'data' => [
                    'response' => [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelIndice->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ]
                ]
            ];

            return $return;           
        }
        
        return [
            'ok' => true            
        ];
    
    }

    /**
     * Función donde valida que se ha agregado un documento de forma manual al expediente
     * @param $documento [Model de GdExpedienteDocumentos]
     * @param $modelExpediente [Model de expediente]  
     * 
    */
    public static function addDocumentUploadToExpedient($documento, $modelExpediente, $xml){

        # Se obtiene la numeración del expediente
        $orden = $modelExpediente->numeracionGdExpediente;
        $orden++;       

        # Datos del usuario
        $modelUser = User::find()
            ->where(['id' => $documento->idUser])
        ->one();
        
        $usuario = $modelUser->userDetalles->nombreUserDetalles . ' ' . $modelUser->userDetalles->apellidoUserDetalles;
        $userDependencia = $modelUser->gdTrdDependencia->nombreGdTrdDependencia;

        # Se almacena información a indices.
        $modelIndice = new GdIndices();

        $modelIndice->indiceContenidoGdIndice = $modelExpediente->numeroGdExpediente .$orden .'TD';
        $modelIndice->idGdExpedienteInclusion = null;
        $modelIndice->idGdExpedienteDocumento = $documento->idGdExpedienteDocumento;
        $modelIndice->valorHuellaGdIndice = $xml['hash']['encryptedData']['encrypted'];
        $modelIndice->funcionResumenGdIndice = $xml['hash']['function'];
        $modelIndice->ordenDocumentoGdIndice = $orden;
        $modelIndice->tamanoGdIndice = $documento->tamanoGdExpedienteDocumento;
        $modelIndice->rutaXmlGdIndice = $xml['rutaXml'];
        $modelIndice->nombreDocumentoGdIndice = $documento->nombreGdExpedienteDocumento;
        $modelIndice->idGdTrdTipoDocumental = $documento->idGdTrdTipoDocumental;
        $modelIndice->creacionDocumentoGdIndice = $documento->creacionGdExpedienteDocumento;
        $modelIndice->formatoDocumentoGdIndice = $documento->extensionGdExpedienteDocumento;
        $modelIndice->descripcionGdIndice = $documento->observacionGdExpedienteDocumento;
        $modelIndice->usuarioGdIndice = $usuario . ' - ' .  $userDependencia;
        $modelIndice->origenGdIndice = Yii::$app->params['origen']['Digitalizado'];

        if($modelIndice->save()){

            $numeroExpediente = $modelExpediente->numeroGdExpediente;
            $fechaIncorporacion = $documento->creacionGdExpedienteDocumento;
            $generacionIndice = self::generateIndiceXml($modelIndice, $numeroExpediente, $fechaIncorporacion);            

            if(!$generacionIndice['ok']){                
                return  [
                    'ok' => false,
                    'data' => [
                        'response' => [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $generacionIndice['data']['message'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ]
                    ]
                ];
            }

            self::logIndice($modelIndice, $modelExpediente, $usuario);

            $modelExpediente->numeracionGdExpediente = $orden;
            $modelExpediente->save();

        } else {            
            $return = [
                'ok' => false,
                'data' => [
                    'response' => [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelIndice->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ]
                ]
            ];

            return $return;           
        }
        
        return [
            'ok' => true,
            'data' => [
                'orden' => $orden,
            ]
        ];

    }

    /**
     * Función donde valida que se ha agregado una referencia cruzada al expediente
     * @param $documento [Model de GdReferenciasCruzadas]
     * @param $modelExpediente [Model de expediente]  
     * 
    */
    public static function addCrossReferenceToExpedient($documento, $modelExpediente, $xml, $tamano){

        # Se obtiene la numeración del expediente
        $orden = $modelExpediente->numeracionGdExpediente;
        $orden++;

        # Datos del usuario
        $modelUser = User::find()
            ->where(['id' => $documento->idUserGdReferenciaCruzada])
            ->one();

        $usuario = $modelUser->userDetalles->nombreUserDetalles . ' ' . $modelUser->userDetalles->apellidoUserDetalles;
        $userDependencia = $modelUser->gdTrdDependencia->nombreGdTrdDependencia;

        # Se almacena información a indices.
        $modelIndice = new GdIndices();

        $modelIndice->indiceContenidoGdIndice = $modelExpediente->numeroGdExpediente .$orden .'TD';
        $modelIndice->idGdExpedienteInclusion = null;
        $modelIndice->idGdReferenciaCruzada = $documento->idGdReferenciaCruzada;
        $modelIndice->valorHuellaGdIndice = $xml['hash']['encryptedData']['encrypted'];
        $modelIndice->funcionResumenGdIndice = $xml['hash']['function'];
        $modelIndice->ordenDocumentoGdIndice = $orden;
        $modelIndice->tamanoGdIndice = $tamano;
        $modelIndice->rutaXmlGdIndice = $xml['rutaXml'];
        $modelIndice->nombreDocumentoGdIndice = $documento->nombreGdReferenciaCruzada;
        $modelIndice->idGdTrdTipoDocumental = $documento->idGdTrdTipoDocumental;
        $modelIndice->creacionDocumentoGdIndice = $documento->creacionGdReferenciaCruzada;
        $modelIndice->formatoDocumentoGdIndice = 'pdf';
        $modelIndice->descripcionGdIndice = 'Referencia cruzada';
        $modelIndice->usuarioGdIndice = $usuario . ' - ' .  $userDependencia;
        $modelIndice->origenGdIndice = Yii::$app->params['origen']['Fisico'];

        if($modelIndice->save()){

            $numeroExpediente = $modelExpediente->numeroGdExpediente;
            $fechaIncorporacion = $documento->creacionGdReferenciaCruzada;
            $generacionIndice = self::generateIndiceXml($modelIndice, $numeroExpediente, $fechaIncorporacion);

            if(!$generacionIndice['ok']){
                return  [
                    'ok' => false,
                    'data' => [
                        'response' => [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => $generacionIndice['data']['message'],
                            'status' => Yii::$app->params['statusErrorValidacion'],
                        ]
                    ]
                ];
            }

            self::logIndice($modelIndice, $modelExpediente, $usuario);

            $modelExpediente->numeracionGdExpediente = $orden;
            $modelExpediente->save();

        } else {
            $return = [
                'ok' => false,
                'data' => [
                    'response' => [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => $modelIndice->getErrors(),
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ]
                ]
            ];

            return $return;
        }
        
        return [
            'ok' => true,
            'data' => [
                'orden' => $orden,
            ]
        ];

    }

    public static function getXmlParams($gdExpediente){
        $xml['pathXml'] =   Yii::getAlias('@webroot') . '/bodega/indices_xml/';
        $xml['fileNameXml'] = 'indice_electronico_'.$gdExpediente->numeroGdExpediente . '.xml';                    
        $xml['rutaXml'] = $xml['pathXml'] .  $xml['fileNameXml'];
        $xml['hash'] = self::createHash($gdExpediente->idGdExpediente);
        
        return $xml;
    }
    
    public static function logIndice($modelIndice, $modelExpediente, $usuario){
        
        $numeroExpediente = $modelExpediente->numeroGdExpediente;
            
        $tipoDocuental = GdTrdTiposDocumentales::find()
            ->where(['idGdTrdTipoDocumental' => $modelIndice->idGdTrdTipoDocumental])
        ->one()->nombreTipoDocumental;           
        
        $dataIndice = 'Id: ' . $modelIndice->idGdIndice;
        $dataIndice .= ', Índice contenido: ' . $modelIndice->indiceContenidoGdIndice;
        $dataIndice .= ', Función resumen: ' . $modelIndice->funcionResumenGdIndice;
        $dataIndice .= ', Nombre documento: ' . $modelIndice->nombreDocumentoGdIndice;
        $dataIndice .= ', Orden documento: ' . $modelIndice->ordenDocumentoGdIndice;
        $dataIndice .= ', Tamaño documento: ' . $modelIndice->tamanoGdIndice;
        $dataIndice .= ', Fecha documento: ' . $modelIndice->creacionDocumentoGdIndice;
        $dataIndice .= ', Formato documento: '. $modelIndice->formatoDocumentoGdIndice;
        $dataIndice .= ', Tipo documental: ' .  $tipoDocuental;
        $dataIndice .= ', Descripción: ' . $modelIndice->descripcionGdIndice;
        $dataIndice .= ', Ruta xml: ' . $modelIndice->rutaXmlGdIndice;
        $dataIndice .= ', Origen: ' . Yii::$app->params['origenNumber'][$modelIndice->origenGdIndice];
        $dataIndice .= ', Fecha creación: ' . $modelIndice->CreacionGdIndice;        
        $dataIndice .= ', Usuario: ' . $usuario;

        /***    Log de Auditoria  ***/
        HelperLog::logAdd(
            true,
            Yii::$app->user->identity->id, //Id user
            Yii::$app->user->identity->username, //username
            Yii::$app->controller->route, //Modulo
            Yii::$app->params['eventosLogText']['crear'] . ", en la tabla gdIndices relacionado al expediente con número: $numeroExpediente", //texto para almacenar en el evento
            '',
            $dataIndice, //Data
            array() //No validar estos campos
        );                  
        /***    Fin log Auditoria   ***/
    }
    
      /**
     * Función que permite crear el hash por cada radicado incluido en el expediente
     * @param $idExpediente [Int] [Id del expediente de inclusión]
     **/
    public static function createHash($idExpediente){

        # Consulta de radicados incluidos en expedientes 
        $tablaInclusion = GdExpedientesInclusion::tableName() . ' AS EIN';
        $tablaRadicado = RadiRadicados::tableName() . ' AS RD';
        $tablaExpediente = GdExpedientes::tableName() . ' AS EX';
        $tablaTipoDoc = GdTrdTiposDocumentales::tableName() . ' AS TD';


        $modelRelation = (new \yii\db\Query())
            ->from($tablaInclusion);
            // ->innerJoin($tablaRadicado, '`rd`.`idRadiRadicado` = `in`.`idRadiRadicado`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaRadicado, ['RD' => 'idRadiRadicado', 'EIN' => 'idRadiRadicado']);
            // ->innerJoin($tablaExpediente, '`ex`.`idGdExpediente` = `in`.`idGdExpediente`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaExpediente, ['EX' => 'idGdExpediente', 'EIN' => 'idGdExpediente']);
            // ->innerJoin($tablaTipoDoc, '`td`.`idGdTrdTipoDocumental` = `rd`.`idTrdTipoDocumental`')
            $modelRelation = HelperQueryDb::getQuery('innerJoinAlias', $modelRelation, $tablaTipoDoc, ['TD' => 'idGdTrdTipoDocumental', 'RD' => 'idTrdTipoDocumental']);
            $modelRelation = $modelRelation->where([ 'EIN.idGdExpedienteInclusion' => $idExpediente])
        ->all();


        $data = [];
        foreach ($modelRelation as $dataRelation) {
            $data = [
                'nombreExpediente' => $dataRelation['nombreGdExpediente'],
                'fechaExpediente' => $dataRelation['creacionGdExpediente'],
                'fechaInclusion' => $dataRelation['creacionGdExpedienteInclusion'],
                'numeroRadicado' => $dataRelation['numeroRadiRadicado'],
                'nombreTipoDoc' => $dataRelation['nombreTipoDocumental'],
            ];
        }

        $encryptedData = HelperEncryptAes::encrypt($data, false);

        $response = [
            'message' => 'Hash creado',
            'encryptedData' => $encryptedData,
            'function' => 'Encrypt AES',  //Función resumen
            'status' => 200,
        ];

        return $response;
    }


    /**
        * Función para general Xml indice
    **/
    public static function generateIndiceXml($modelIndice, $numeroExpediente, $fechaIncorporacion){

        $bodegaXml = Yii::getAlias('@webroot') . Yii::$app->params['routeElectronicIndex'];

        $path = $bodegaXml;
        $fileName = 'indice_electronico_'.$numeroExpediente . '.xml';
        
        $rutaOk = true;

        # Origen de la tabla GdIndices
        $origen = Yii::$app->params['origenNumber'][$modelIndice->origenGdIndice];

        // Verificar que la carpeta exista y crearla en caso de no exista
        if (!file_exists($path)) {
            if (!FileHelper::createDirectory($path, $mode = 0775, $recursive = true)) {
                $rutaOk = false;
            }
        }

        /*** Validar creación de la carpeta***/
        if ($rutaOk == false) {            
            return  [
                'ok' => false,
                'data' => [                    
                    'message' => Yii::t('app', 'notPermissionsDirectory')                        
                ]                
            ];
        }
        /*** Fin Validar creación de la carpeta***/
        
        /** Verifica si ya existe el indice electronico en html */
        if (file_exists($path . $fileName)) {
            $archivoExistente = fopen($path . $fileName, 'r');

            $xml = '';

            while(!feof($archivoExistente)){
                $xml .= fgets($archivoExistente);
            }

            $xml = str_replace('</TipoDocumentoFoliado>' , '' ,$xml);            

        }else{
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
            $xml .= "\n";
            $xml .= '<TipoDocumentoFoliado xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">';
            $xml .= "\n";
        }           
            
        $xml .= " <DocumentoIndizado>\n";

        $xml .= " <Id>$modelIndice->indiceContenidoGdIndice></Id>\n ";
        $xml .= " <Nombre_Documento>$modelIndice->nombreDocumentoGdIndice</Nombre_Documento>\n ";
        $xml .= " <Tipologia_Documental>".$modelIndice->gdTrdTipoDocumental->nombreTipoDocumental."</Tipologia_Documental>\n ";
        $xml .= " <Fecha_Creacion_Documento>$modelIndice->creacionDocumentoGdIndice</Fecha_Creacion_Documento>\n ";
        $xml .= " <Fecha_Incorporacion_Expediente>".$fechaIncorporacion."</Fecha_Incorporacion_Expediente>\n ";
        $xml .= " <Valor_Huella>$modelIndice->valorHuellaGdIndice</Valor_Huella>\n ";
        $xml .= " <Funcion_Resumen>$modelIndice->funcionResumenGdIndice</Funcion_Resumen>\n ";
        $xml .= " <Orden_Documento_Expediente>$modelIndice->ordenDocumentoGdIndice</Orden_Documento_Expediente>\n ";
        $xml .= " <Pagina_Inicio>$modelIndice->paginaInicioGdIndice</Pagina_Inicio>\n ";
        $xml .= " <Pagina_Fin>$modelIndice->paginaFinalGdIndice</Pagina_Fin>\n ";
        $xml .= " <Formato>$modelIndice->formatoDocumentoGdIndice</Formato>\n ";
        $xml .= " <Tamano>$modelIndice->tamanoGdIndice</Tamano>\n ";             
        $xml .= " <Origen>$origen</Origen>\n ";             
            
        $xml .= " </DocumentoIndizado>\n";        
    
        $xml .= " </TipoDocumentoFoliado>\n";

        $archivo = fopen($path . $fileName, 'w+');

        fwrite($archivo,  $xml);    
        fclose($archivo);

        return [
            'ok' => true,
            'data' => []
        ];        
    }
}
