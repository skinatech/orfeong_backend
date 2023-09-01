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

use api\components\HelperIndiceElectronico;
use api\components\HelperLog;
use api\components\HelperConsecutivo;

use api\models\CgTransaccionesRadicados;
use api\models\GdExpedientesInclusion;
use api\models\RadiRadicados;
use api\models\GaArchivo;
use api\models\User;

/**
 * Clase que contiene funciones utilizadas para expedientes
 */
class HelperExpedient
{
    /*
     * Función que incluye radicados a un expediente
     * @input [string] $dataRadicados "Array de radicados a incluir en el expediente"
     * @input [model] $modelExpediente "Modelo del expediente donde se van a inluir los radicados"
     * @output [
     *      'message' => Mensaje de salida,
     *      'data' => [array], Datos de respuesta
     *      'errors' => [array] Array de errores 
     *      'status' => [boolean] Estatus de respuesta de la función
     * ]
     */
    public static function includeInExpedient($dataRadicados, $modelExpediente)
    {
        $numerosRadicados = '';
        $arrayIncluidos = [];
        $idsRadiLog  = []; // Ids de los radicados incluidos

        # Consulta la transacción para el log de radicado
        $modelTransacionIncludeInFile = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'includeInFile']);
        $modelTransacionIArchive = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'archiveFiling']);
        $idTransacion = $modelTransacionIArchive->idCgTransaccionRadicado;

        // Consulta de transaccion
        $modelTransacionFinalize = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' =>'finalizeFiling']);
        $idTransacion2 = $modelTransacionFinalize->idCgTransaccionRadicado;

        $idTransacionIncludeInFile = $modelTransacionIncludeInFile->idCgTransaccionRadicado;

        $HelperConsecutivo = new HelperConsecutivo();

        foreach($dataRadicados as $radicado){

            $idRadicado = $radicado['id'];

            $modelExistente = GdExpedientesInclusion::find()
                ->where(['idRadiRadicado' => $idRadicado])
                ->one();

            //Pregunta si modelExistente existe en GdExpedientesInclusion
            if(empty($modelExistente)){     
                
                $modelRadicado = RadiRadicados::find()->where(['idRadiRadicado' => $idRadicado])->one();

                if ($modelRadicado->isRadicado == false || $modelRadicado->isRadicado == 0) {
                    return [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [],
                        'errors' => [ 'Error' => [ Yii::t('app', 'messageRadiTmpExpedient', ['numFile' => $modelRadicado->numeroRadiRadicado]) ]],
                        'status' => false,
                    ];
                }

                /**Validación si es de no tipo documental y entrada o PQRS */
                if (
                        $modelRadicado->idTrdTipoDocumental == '0' && 
                        (
                            $modelRadicado->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada']  || 
                            $modelRadicado->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']
                        )
                    )
                {
                    return [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [],
                        'errors' => [ 'Error' => [ Yii::t('app', 'messageSinTipoDoc', ['numFile' => $modelRadicado->numeroRadiRadicado]) ]],
                        'status' => false,
                    ];    
                } 
                /**Fin validación */

                /** sguarin Continuar solo si existe usuario dsalida configurado en el sistema */
                $useDeSalida = User::find()
                    ->select(['id','idGdTrdDependencia'])
                    ->where(['username' => Yii::$app->params['userNameDeSalida']])
                ->one();

                if(is_null($useDeSalida)){     
                    return [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [],
                        'errors' => ['error' => [Yii::t('app', 'notUserDeSalida')]],
                        'status' => false,
                    ];
                }
 
                $idExpediente = $modelExpediente->idGdExpediente;

                //consultar si en la tabla gaarchivo hay registros con el id del expediente procesado
                $gaArchivo = GaArchivo::find()->where(['idGdExpediente' => $idExpediente])->one();

                //Si trae registros entonces archivar el radicado iterado
                if(!is_null($gaArchivo)){

                    //cambiar el estado del radicado a archivado
                    $modelRadicado->estadoRadiRadicado =  Yii::$app->params['statusTodoText']['Archivado'];

                    //Adicionalmente se debe cambiar el usuario tramitador del radicado
                    // tramitador OLD
                    $modelRadicado->user_idTramitadorOld = $modelRadicado->user_idTramitador;
    
                    // Nuevo tramitador: usuario dsalida
                    $modelRadicado->user_idTramitador = $useDeSalida->id;
                    $modelRadicado->idTrdDepeUserTramitador = $useDeSalida->idGdTrdDependencia;

                    if(!$modelRadicado->save()){
                        return [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [],
                            'errors' => $modelRadicado->getErrors(),
                            'status' => false,
                        ];
                    }

                }

                $modelExpedienteInclusion = new GdExpedientesInclusion();

                $modelExpedienteInclusion->idRadiRadicado = $idRadicado;
                $modelExpedienteInclusion->idGdExpediente = $modelExpediente->idGdExpediente;
                
                if($modelExpedienteInclusion->save()){

                    $resultado = HelperIndiceElectronico::addFilingDocumentsToIndex($idRadicado, $modelExpedienteInclusion);                  
                    
                    if(!$resultado['ok']){
                        return [
                            'message' => Yii::t('app', 'errorValidacion'),
                            'data' => [],
                            'errors' => $resultado['data']['response']['data'],
                            'status' => false,
                        ];
                    }

                    // Asigna los id radicados para el log
                    $idsRadiLog[] = $idRadicado;
                    $numerosRadicados .= $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelRadicado->numeroRadiRadicado, $modelRadicado->idCgTipoRadicado, $modelRadicado->isRadicado) . ', ';
                }else{
                    return [
                        'message' => Yii::t('app', 'errorValidacion'),
                        'data' => [],
                        'errors' => $modelExpedienteInclusion->getErrors(),
                        'status' => false,
                    ];
                }
                
            }else{
                $arrayIncluidos[] = [
                    'numeroRadicado' => $modelExistente->radiRadicado->numeroRadiRadicado,
                    'numeroExpediente' => $modelExistente->gdExpediente->numeroGdExpediente,
                    'nombreExpediente' => $modelExistente->gdExpediente->nombreGdExpediente,
                ];
            }
        }

        $arrayMensajes = [];

        foreach($arrayIncluidos as $incluido){
            $arrayMensajes[] = Yii::t('app', 'alreadyIncludedExpedient', [
                'numeroRadicado' => $incluido['numeroRadicado'],
                'nombreExpediente' =>  $incluido['nombreExpediente'],
                'numeroExpediente' =>  $incluido['numeroExpediente']
            ]);
        }   

        if($numerosRadicados != '')
        {
            $arrayMensajes[] =  Yii::t('app','includeExpedienteSuccess', [
                'listadoRadicados' => $numerosRadicados,
                'nombreExpediente' => $modelExpediente->nombreGdExpediente
            ]);

            /***    Log de Expediente  ***/
            $observacion = 'Se incluyó los siguientes radicados al expediente: ' . substr($numerosRadicados,0, -2);
            HelperLog::logAddExpedient(
                Yii::$app->user->identity->id, //Id user
                Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                $modelExpediente->idGdExpediente, //Id expediente
                Yii::$app->params['operacionExpedienteText']['IncluirExpediente'], //Operación
                $observacion //observación
            );
            /***  Fin  Log de Expediente  ***/                   
        }
        // Recorre los ids que fueron guardados para luego consultar el estado y agregar lo al log de auditoria
        if(count($idsRadiLog) > 0){
            
            $labelModel = $modelExpedienteInclusion->attributeLabels();
            foreach ($idsRadiLog as $key => $idRadicadoLog ) {
                $dataLog = '';
                $modelExpedienteInclusion = GdExpedientesInclusion::find()->where(['idRadiRadicado' => $idRadicadoLog ])->one();
                $nombreGdExpediente = $modelExpedienteInclusion->gdExpediente->nombreGdExpediente;
                $numeroRadiRadicado = $HelperConsecutivo->numeroRadicadoXTipoRadicadoConsultando($modelExpedienteInclusion->radiRadicado->numeroRadiRadicado);

                foreach ($modelExpedienteInclusion as $key => $value) {
                
                    switch ($key) {
                        case 'idRadiRadicado':
                            $dataLog .= 'Número radicado: '. $numeroRadiRadicado . ', ';
                        break;
                        case 'idGdExpediente':
                            $dataLog .= 'Expediente: '. $nombreGdExpediente . ', ';
                        break;
                        case 'estadoGdExpedienteInclusion':
                            $dataLog .= $labelModel[$key].': '.Yii::$app->params['statusTodoNumber'][$modelExpedienteInclusion->estadoGdExpedienteInclusion].', ';
                        break;

                        default:
                            $dataLog .= $labelModel[$key].': '.$value.', ';
                        break;
                    }
                }

                /***    Log de Auditoria expediente  ***/
                HelperLog::logAdd(
                    true,
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->username, //username
                    Yii::$app->controller->route, //Modulo
                    Yii::$app->params['eventosLogText']['includeInFile'] . $numeroRadiRadicado . ' al expediente ' . $nombreGdExpediente, //texto para almacenar en el evento
                    '',
                    $dataLog, //Data
                    array() //No validar estos campos
                );
                /***    Fin log Auditoria  expediente ***/

                /*** Log de Radicados expediente ***/    
                HelperLog::logAddFiling(
                    Yii::$app->user->identity->id, //Id user
                    Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                    $idRadicadoLog, //Id radicado
                    $idTransacionIncludeInFile,
                    Yii::$app->params['eventosLogTextRadicado']['includeInFile'] . $nombreGdExpediente, //observación 
                    [],
                    array() //No validar estos campos
                );
                /*** Fin log Radicados expediente ***/

                if(!is_null($gaArchivo)){

                    $modelArchivado = RadiRadicados::find()->where(['idRadiRadicado' => $idRadicadoLog ])->one();

                    if ($modelArchivado->estadoRadiRadicado==Yii::$app->params['statusTodoText']['Archivado']){
    
                        /***    Log de Radicados finalizados  ***/
                        $observacion2 = "Se finalizó el radicado porque se incluyó a un expediente archivado";
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $modelArchivado->idRadiRadicado, //Id radicado
                            $idTransacion2  ,
                            $observacion2, //observación 
                            $modelArchivado,
                            array() //No validar estos campos
                        );
                        /***    Fin log Radicados finalizados   ***/  
            
                        /***    Log de Auditoria radicados finalizados ***/
                        HelperLog::logAdd(
                            false, //type
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            Yii::$app->params['eventosLogText']['FinalizeFile'] . " N°. ". $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelArchivado->numeroRadiRadicado, $modelArchivado->idCgTipoRadicado, $modelArchivado->isRadicado)." por el motivo de: ".$observacion2.", con el estado ". Yii::$app->params['statusTodoNumber'][$modelArchivado->estadoRadiRadicado].", en la tabla radiRadicados", //texto para almacenar en el evento
                            [],
                            [], //Data
                            array() //No validar estos campos
                        );      
                        /***    Fin log Auditoria radicados finalizados  ***/
            

                        /***    Log de Radicados archivados  ***/
                        $observacion1 = Yii::$app->params['eventosLogTextExpedientes']['archivado'].$modelExpediente->numeroGdExpediente;
                        HelperLog::logAddFiling(
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->idGdTrdDependencia, // Id dependencia
                            $modelArchivado->idRadiRadicado, //Id radicado
                            $idTransacion,
                            $observacion1,
                            [$gaArchivo], // model
                            array()  // No validar estos campos
                        );
                        /***  Fin  Log de Radicados archivados  ***/
            
                        /***    Log de Auditoria radicados archivados ***/
                        $dataLog = '';     
                        // Data para el log de auditoria
                        $dataLog .= 'Edificio: '. $gaArchivo->gaEdificio->nombreGaEdificio.', ';
                        $dataLog .= 'Piso: '. $gaArchivo->gaPiso->numeroGaPiso.', ';
                        $dataLog .= 'Área de archivo: '. $gaArchivo->gaBodega->nombreGaBodega.', ';
                        $dataLog .= 'Estante: '. $gaArchivo->estanteGaArchivo.', ';
                        $dataLog .= 'Módulo: '. $gaArchivo->rackGaArchivo.', ';
                        $dataLog .= 'Entrepaño: '. $gaArchivo->entrepanoGaArchivo.', ';
                        $dataLog .= 'Caja: '. $gaArchivo->cajaGaArchivo.', ';
                        $dataLog .= 'Cuerpo: '. $gaArchivo->cuerpoGaArchivo.', ';
                        $dataLog .= 'Número de conservación: '. $gaArchivo->unidadCampoGaArchivo.', ';
                        $dataLog .= 'Unidad de conservación: '. Yii::$app->params['unidadConservacionGaArchivoNumber'][$gaArchivo->unidadConservacionGaArchivo] ;
            
                        $idRadiSeleccionados[] = $modelArchivado->numeroRadiRadicado;
            
                        $implodeRadicados = implode(', ',$idRadiSeleccionados);
                    
                        $observacionFiling =  Yii::$app->params['eventosLogText']['asignacionEspacioFisico']; 
            
                        $observacionFiling = $observacionFiling.'a el radicado '. $HelperConsecutivo->numeroRadicadoXTipoRadicado($modelArchivado->numeroRadiRadicado, $modelArchivado->idCgTipoRadicado, $modelArchivado->isRadicado);

                        HelperLog::logAdd(
                            true,
                            Yii::$app->user->identity->id, //Id user
                            Yii::$app->user->identity->username, //username
                            Yii::$app->controller->route, //Modulo
                            $observacionFiling,
                            '',
                            $dataLog, //Data
                            array() //No validar estos campos
                        );
                        /***    Log de Auditoria radicados archivados  ***/
                            
                    }
                }
            }
        }

        return [
            'message' => 'Ok',
            'data' => [
                'arrayIncluidos' => $arrayIncluidos,
                'numerosRadicados' => $numerosRadicados,
                'arrayMensajes' => $arrayMensajes,
            ],
            'errors' => [],
            'status' => true,
        ];
    }

}
