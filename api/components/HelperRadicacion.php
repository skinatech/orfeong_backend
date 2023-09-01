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
use DateTime;

use yii\helpers\FileHelper;

use unyii2\imap\ImapConnection;
use unyii2\imap\Mailbox;
use Html2Text\Html2Text;

use api\modules\version1\controllers\radicacion\RadicadosController;
use api\modules\version1\controllers\pdf\PdfController;

use api\models\RadiDocumentos;
use api\models\RadiRemitentes;
use api\models\Clientes;
use api\models\User;
use api\models\GdTrdDependencias;
use api\models\RadiCorreosRadicados;
use api\models\CgDiasNoLaborados;
use api\models\CgHorarioLaboral;
use api\models\FacturacionElectronica;
/**
 * Clase que contiene funciones utilizadas para la radicacion
 */
class HelperRadicacion
{
    public static function generatePdfEmail($modelRadiRadicados, $dataEmail, $dataUserEmail, $mailBox){
   
        # Se realiza la consulta en la tabla remitentes con el id del radicado para obtener la información de un usuario
        # o de un cliente y asi mostrar la información
        $remitentes = RadiRemitentes::findOne(['idRadiRadicado' => $modelRadiRadicados->idRadiRadicado]);    

        if($remitentes->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
            $modelRemitente = Clientes::findOne(['idCliente' => $remitentes->idRadiPersona]);
            $idCliente = $modelRemitente->idCliente;
        }else{
            $modelRemitente = User::findOne(['id' => $remitentes->idRadiPersona]);
            $idCliente = $modelRemitente->id;
        }

        $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRadiRadicados->idTrdDepeUserCreador]);
        $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;

        $status = false;  $rutaOk = true;
        
        /** Recibir y decodificar datos del correo */
        if(isset($dataUserEmail['data'])){
            $dataEmailDecrypted = HelperEncryptAes::decrypt($dataUserEmail['data'], false);
            if ($dataEmailDecrypted['status'] == false) {
                return [
                    'status' => false,
                    'message' => Yii::t('app', 'errorValidacion'),
                    'errors' => ['error' => [Yii::t('app', 'AuthenticationFailed')]], 
                ];
            }

            $authUser = [
                'username' => $dataEmailDecrypted['request']['username'],
                'password' => $dataEmailDecrypted['request']['password'],
            ];
        } else {
            return [
                'status' => false,
                'message' => Yii::t('app', 'emptyJsonSend'),
                'errors' => ['error' => [Yii::t('app', 'emptyJsonSend')]],
            ];
        }
        /** Fin Recibir y decodificar datos del correo */

        /** Coneccion IMAP con el el servicio de correo */
        $imapConnection = new ImapConnection();

        $imapConnection->imapPath = $mailBox;
        $imapConnection->imapLogin = $authUser['username'];
        $imapConnection->imapPassword = $authUser['password'];
        $imapConnection->serverEncoding = 'utf-8'; // utf-8 default.
        $imapConnection->attachmentsDir = Yii::getAlias('@webroot') . "/tmp_mail";
        $attachments = [];
        
        $mailbox = new Mailbox($imapConnection);
        /** Fin Coneccion IMAP con el el servicio de correo */

        $mailBoxName = explode('}', $mailBox);
        $mailBoxName = end($mailBoxName);

        $infoMailProcess = 'Nombre de la bandeja: ' . $mailBoxName . ', correo: ' . $authUser['username'];
        $idsCorreosProcesados = [];
        // Variable que permite que se cree un archivo html
        $statusHTML = false;

        /** Procesar lista de correos recibidos */
        $countCorreosProcesados = 0;
        foreach ( $dataEmail as $rowDataMail) {
            $countCorreosProcesados++;
            
            $mailObject = $mailbox->getMail($rowDataMail['id']);

            $idsCorreosProcesados[] = $rowDataMail['id'];

            $emailData = [];
            // From
            $emailData['From'] = $emailData['From'] = self::detecteString($mailObject->fromName);
            $emailData['fromAddress'] = $mailObject->fromAddress;
            // to
            foreach($mailObject->to as $key => $value){
                $emailData['to'][] = $key;
            }
            // subject
            $emailData['subject'] = self::detecteString($mailObject->subject);
            
            // Date
            $formatoFrontend = self::getFormatosFecha($mailObject->date)['formatoFrontend'];
            $emailData['date'] = $formatoFrontend.' '.date('H:i:s', strtotime($mailObject->date));

            // Cuerpo Correo
            if ($mailObject->textPlain == null) {
                if ($mailObject->textHtml == null) {
                    $emailData['body'] = 'El correo no posee cuerpo';
                } else {
                    /** Procesar email HTML. Estraer solo el texto */
                    $html = new Html2Text($mailObject->textHtml);
                    $textoExtraido = $html->getText();
                    $textoExtraido = self::detecteString($textoExtraido);

                    if($textoExtraido == ' \n\n '){
                        $emailData['body'] = 'El correo no posee cuerpo';
                    }else{
                        $emailData['body'] = $textoExtraido;
                    }
                    $statusHTML = true;
                }

            } else {
                if ($mailObject->textHtml != null) {
                    $html = new Html2Text($mailObject->textHtml);
                    $textoExtraido = $html->getText();
                    $textoExtraido = self::detecteString($textoExtraido);
                    if($textoExtraido == ' \n\n '){
                        $emailData['body'] = 'El correo no posee cuerpo';
                    }else{
                        $emailData['body'] = $textoExtraido;
                    }
                }
                else{
                    $te1xtPlain = self::detecteString($mailObject->textPlain);
                    $emailData['body'] = str_replace("\n", '<br>', $te1xtPlain);
                }
            }
            
            // $emailData['body'] = self::detecteString($emailData['body']);

            // Adjuntos
            $adjuntos = [];
            $attachments = $mailObject->getAttachments();
            //$transaction = Yii::$app->db->beginTransaction(); //Inicio de la transacción

            //$pathUploadFile = Yii::getAlias('@webroot') . "/" . Yii::$app->params['routeDocuments'] . "/" . Yii::$app->params['nomCarpetaDocumentos']. $idcliente ."/". Yii::$app->params['nomRadicado']. $modelRadiRadicados['idRadiRadicado'] . '/RadiEmail'."/";
            $anio = date('Y');
            $pathUploadFile = Yii::getAlias('@webroot')                            
                . "/" .  Yii::$app->params['bodegaRadicados']
                . "/" . $anio                            
                . "/" . $codigoGdTrdDependencia
                . "/"
            ;

            $pathFileRadiDocumento = $pathUploadFile;

            // Verificar que la carpeta exista y crearla en caso de no exista
            if (!file_exists($pathFileRadiDocumento)) {
                if (!FileHelper::createDirectory($pathFileRadiDocumento, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            if(!$rutaOk){
                return [
                    'status' => false,
                    'message' => Yii::t('app', 'errorValidacion'),
                    'errors' => ['error' => ['Error Directorio']],
                ];
            }


            /** Procesar archivos adjuntos del correo iterado */
            $numeroRadiDocumento = 0;

            // Obtiene el cuerpo del mensaje
            if ($mailObject->textHtml != null) {
                $isBodyHtml = true;
                $mailBody = $mailObject->textHtml;
                if ($mailObject->textPlain != null) {
                    $vistaDisponible = 'Texto y Html';
                } else {
                    $vistaDisponible = 'Html';
                }

            } else {
                $isBodyHtml = false;
                $mailBody = str_replace("\n", '<br>', $mailObject->textPlain);
                $vistaDisponible = 'Texto';
            }
            /** Datos de la Etiqueta del Correo electrónico */
            $username = User::findOne(['id' => Yii::$app->user->identity->id]);
            $depename = GdTrdDependencias::findOne(['idGdTrdDependencia' => $username->idGdTrdDependencia]);
            $emailData['numeroRadicado'] = $modelRadiRadicados['numeroRadiRadicado'];
            $emailData['fechaRadicado']  = date("Y-m-d H:i:s");
            $emailData['usuaRadicado']   = $username->userDetalles->nombreUserDetalles.' '.$username->userDetalles->apellidoUserDetalles;
            $emailData['depeRadicado']   = $depename->nombreGdTrdDependencia;
            $emailData['folioRadicado']  = $modelRadiRadicados['foliosRadiRadicado'];
            $emailData['descAnexos']     = count($attachments);
            $emailData['body'] = strip_tags($emailData['body'], '<br>');

            foreach ($attachments as $attachment) {
                $supplier = '';
                if(pathinfo($attachment->name, PATHINFO_EXTENSION) == 'zip' || pathinfo($attachment->name, PATHINFO_EXTENSION) == 'ZIP'){
                    if (strpos(strtolower($emailData['body']), 'factura') != false){
                        $feModel = new FacturacionElectronica();
                        $supplier = $feModel->unzipDescAttachment($attachment);
                    }

                }
                //Si el archivo adjunto es una imagen del cuerpo del mensaje
                if(!preg_match('/<img.*src="cid:('. $attachment->id . ')"/', $mailBody) && empty($supplier)){

                    // $attachment->name = utf8_encode($attachment->name);

                    $adjuntos[] = $attachment->name;
                    $numeroRadiDocumento++;
                    $nombreRadiDocumento = $modelRadiRadicados['numeroRadiRadicado'];
                    $extension = explode('.', $attachment->name);

                    $model = new RadiDocumentos;
                    $model->idRadiRadicado =  $modelRadiRadicados['idRadiRadicado'];
                        
                    // valida si el nombre de los adjuntos llegan con caracteres especiales para reemplazarlos con ningun campo
                    $nombreAdjunto = preg_replace('([^A-Za-z0-9 \._\-@])', '', $attachment->name);

                    $model->numeroRadiDocumento = $numeroRadiDocumento;
                    // $model->nombreRadiDocumento =  $attachment->name;
                    $model->nombreRadiDocumento =  $nombreAdjunto;
                    $model->rutaRadiDocumento = $pathFileRadiDocumento;
                    $model->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                    $model->creacionRadiDocumento = date("Y-m-d H:i:s");
                    $model->extencionRadiDocumento = trim(end($extension));
                    $model->idGdTrdTipoDocumental = $modelRadiRadicados['idTrdTipoDocumental'];
                    $model->idUser = Yii::$app->user->id;
                    // $model->descripcionRadiDocumento = $attachment->name;
                    $model->descripcionRadiDocumento = $nombreAdjunto;
                    
                    $tamano = filesize($attachment->filePath) / 1000; 
                    $model->tamanoRadiDocumento = number_format($tamano, 2, '.', '') . ' KB';

                    if(!$model->save()){
                        return [
                            'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                            'errors'   => $model->getErrors(),
                            'status'  => false
                        ];
                    }

                    // $nomArchivo = $modelRadiRadicados['numeroRadiRadicado'] . "-" . $model->idRadiDocumento . '.' . trim(end($extension));
                    $nomArchivo = $modelRadiRadicados['numeroRadiRadicado'] . "-" . $numeroRadiDocumento . '.' . trim(end($extension));
                    if(!file_exists($pathFileRadiDocumento.''.$nomArchivo)) {
                        // copiar de la carpeta temp a la ruta asignada
                        copy($attachment->filePath, $pathFileRadiDocumento.''.$nomArchivo);
                    }else {
                        unlink($pathFileRadiDocumento.''.$nomArchivo);
                        copy($attachment->filePath, $pathFileRadiDocumento.''.$nomArchivo);
                    }
                    // Eliminar de temp
                    unlink($attachment->filePath);

                    $status = true;

                    /** Guardar de nuevo para actualizar nombre */
                    $model->nombreRadiDocumento = $nomArchivo;
                    if(!$model->save()){
                        return [
                            'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                            'errors'   => $model->getErrors(),
                            'status'  => false
                        ];
                    }
                } elseif(!empty($supplier)){
                    foreach ($supplier as $supp) {
                        $adjuntos[] = $supp['name'];
                        $numeroRadiDocumento++;
                        $nombreRadiDocumento = $modelRadiRadicados['numeroRadiRadicado'];
                        $extension = explode('.', $supp['name']);

                        $model = new RadiDocumentos;
                        $model->idRadiRadicado =  $modelRadiRadicados['idRadiRadicado'];
                        
                        // valida si el nombre de los adjuntos llegan con caracteres especiales para reemplazarlos con ningun campo
                        $nombreAdjunto = preg_replace('([^A-Za-z0-9 \._\-@])', '', $supp['name']);

                        $model->numeroRadiDocumento = $numeroRadiDocumento;
                        // $model->nombreRadiDocumento =  $attachment->name;
                        $model->nombreRadiDocumento =  $nombreAdjunto;
                        $model->rutaRadiDocumento = $pathFileRadiDocumento;
                        $model->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                        $model->creacionRadiDocumento = date("Y-m-d H:i:s");
                        $model->extencionRadiDocumento = trim(end($extension));
                        $model->idGdTrdTipoDocumental = $modelRadiRadicados['idTrdTipoDocumental'];
                        $model->idUser = Yii::$app->user->id;
                        // $model->descripcionRadiDocumento = $attachment->name;
                        $model->descripcionRadiDocumento = $nombreAdjunto;
                    
                        $tamano = filesize($supp['file']) / 1000; 
                        $model->tamanoRadiDocumento = number_format($tamano, 2, '.', '') . ' KB';

                        if(!$model->save()){
                            return [
                                'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                                'errors'   => $model->getErrors(),
                                'status'  => false
                            ];
                        }

                        // $nomArchivo = $modelRadiRadicados['numeroRadiRadicado'] . "-" . $model->idRadiDocumento . '.' . trim(end($extension));
                        $nomArchivo = $modelRadiRadicados['numeroRadiRadicado'] . "-" . $numeroRadiDocumento . '.' . trim(end($extension));
                        if(!file_exists($pathFileRadiDocumento.''.$nomArchivo)) {
                            // copiar de la carpeta temp a la ruta asignada
                            copy($supp['file'], $pathFileRadiDocumento.''.$nomArchivo);
                        }else {
                            unlink($pathFileRadiDocumento.''.$nomArchivo);
                            copy($supp['file'], $pathFileRadiDocumento.''.$nomArchivo);
                        }
                        // Eliminar de temp
                        unlink($supp['file']);

                        $status = true;

                        /** Guardar de nuevo para actualizar nombre */
                        $model->nombreRadiDocumento = $nomArchivo;
                        if(!$model->save()){
                            return [
                                'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                                'errors'   => $model->getErrors(),
                                'status'  => false
                            ];
                        }
                    }
                }
            }
            /** Fin Procesar archivos adjuntos del correo iterado */

            $filename = 'RadiMail'.$modelRadiRadicados['numeroRadiRadicado'];
            $descripcionRadiDocumento = 'Radicación email';
            if ($countCorreosProcesados > 1) {
                $filename = $filename . '_' . $countCorreosProcesados;
                $descripcionRadiDocumento = 'Radicación email ' . $countCorreosProcesados;
            }

            // return $mailObject;
            // die();

            if (($mailObject->textHtml != null && $mailObject->textHtml != "") or ($mailObject->textPlain != null && $mailObject->textPlain != "")) {
                $rutaHTML = $pathUploadFile.''.$filename.'.html';
                $nombreHTML = $filename.'.html';
                $archivo = fopen($rutaHTML , "w");

                if($mailObject->textHtml != null){
                    fwrite($archivo, $mailObject->textHtml);
                }
                else{
                    fwrite($archivo, $mailObject->textPlain);
                }
                
                fclose($archivo);
                
                if(!file_exists($rutaHTML)){
                    return [
                        'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                        'errors'   => ['error' => 'Error en la generacion del archivo html' ],
                        'status'  => false
                    ];
                }

                // Guarda el HTML del correo
                $model = new RadiDocumentos;
                $model->idRadiRadicado =  $modelRadiRadicados['idRadiRadicado'];
                
                // $model->numeroRadiDocumento = (int) count($attachments)+1;
                $model->numeroRadiDocumento = ++$numeroRadiDocumento;
                $model->nombreRadiDocumento = $nombreHTML;
                $model->rutaRadiDocumento   = $pathUploadFile;
                $model->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                $model->creacionRadiDocumento = date("Y-m-d H:i:s");
                $model->extencionRadiDocumento = 'html';
                $model->idGdTrdTipoDocumental = $modelRadiRadicados->idTrdTipoDocumental;
                $model->idUser = Yii::$app->user->id;
                $model->descripcionRadiDocumento = $descripcionRadiDocumento;

                $tamano = filesize($rutaHTML) / 1000;
                $model->tamanoRadiDocumento = number_format($tamano, 2, '.', '') . ' KB';

                if(!$model->save()){
                    return [
                        'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                        'errors'   => $model->getErrors(),
                        'status'  => false
                    ];
                }
            }
          
            PdfController::generar_pdf('RadicacionEmail','radiEmailContentView', $filename, $pathUploadFile, $emailData, $adjuntos);

            $model = new RadiDocumentos;
            $model->idRadiRadicado =  $modelRadiRadicados['idRadiRadicado'];
            
            // $model->numeroRadiDocumento = (int) count($attachments)+1;
            $model->numeroRadiDocumento = ++$numeroRadiDocumento;
            $model->nombreRadiDocumento = $filename . '.pdf';
            $model->rutaRadiDocumento   = $pathUploadFile;
            $model->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
            $model->creacionRadiDocumento = date("Y-m-d H:i:s");
            $model->extencionRadiDocumento = 'pdf';
            $model->idGdTrdTipoDocumental = $modelRadiRadicados->idTrdTipoDocumental;
            $model->idUser = Yii::$app->user->id;
            $model->descripcionRadiDocumento = $descripcionRadiDocumento;

            $tamano = filesize($model->rutaRadiDocumento . $model->nombreRadiDocumento) / 1000;
            $model->tamanoRadiDocumento = number_format($tamano, 2, '.', '') . ' KB';

            if($model->save()){

                /** Generar Xml */
                $filename = 'RadiMail'.$modelRadiRadicados['numeroRadiRadicado'] . '.xml';
                $descripcionRadiDocumento = 'Radicación email XML';
                if ($countCorreosProcesados > 1) {
                    $filename = $filename . '_' . $countCorreosProcesados;
                    $descripcionRadiDocumento = 'Radicación email XML ' . $countCorreosProcesados . '.xml';
                }

                $generateXml = self::generateXml($filename, $pathUploadFile, $emailData, $adjuntos);
                if($generateXml['status'] == false){
                    return [
                        'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                        'errors'   => ['error' => [Yii::t('app', 'errorProcessXml')]],
                        'status'  => false
                    ];
                }
                /** Fin Generar Xml */

                /** Guardar datos del XML */
                $model = new RadiDocumentos;
                $model->idRadiRadicado =  $modelRadiRadicados['idRadiRadicado'];
                
                // $model->numeroRadiDocumento = (int) count($attachments)+2;
                $model->numeroRadiDocumento = ++$numeroRadiDocumento;
                $model->nombreRadiDocumento = $filename;
                $model->rutaRadiDocumento   = $pathUploadFile;
                $model->estadoRadiDocumento = Yii::$app->params['statusTodoText']['Activo'];
                $model->creacionRadiDocumento = date("Y-m-d H:i:s");
                $model->extencionRadiDocumento = 'xml';
                $model->idGdTrdTipoDocumental = $modelRadiRadicados->idTrdTipoDocumental;
                $model->idUser = Yii::$app->user->id;
                $model->descripcionRadiDocumento = $descripcionRadiDocumento;

                $tamano = filesize($model->rutaRadiDocumento . $model->nombreRadiDocumento) / 1000;
                $model->tamanoRadiDocumento = number_format($tamano, 2, '.', '') . ' KB';
                /** Fin Guardar datos del XML */

                if ($model->save()) {

                    /** Guardar registro de coreo procesado con id de radicado */
                    $radiCorreosRadicados = new RadiCorreosRadicados;
                    $radiCorreosRadicados->idRadiRadicado = $modelRadiRadicados['idRadiRadicado'];
                    $radiCorreosRadicados->bandeja = $mailBox;
                    $radiCorreosRadicados->idCorreo = $rowDataMail['id'];
                    $radiCorreosRadicados->email = $authUser['username'];
                    /** Fin Guardar registro de coreo procesado con id de radicado */

                    if ($radiCorreosRadicados->save()) {

                        /***    Log de Auditoria  ***/
                        // HelperLog::logAdd(
                        //     false,
                        //     Yii::$app->user->identity->id, //Id user
                        //     Yii::$app->user->identity->username, //username
                        //     Yii::$app->controller->route, //Modulo
                        //     Yii::$app->params['eventosLogText']['NewRadiMail'], //texto para almacenar en el evento
                        //     [], //dataOld
                        //     [$radiCorreosRadicados], //Data
                        //     array() //No validar estos campos
                        // );
                        /***    Fin log Auditoria   ***/

                    } else {
                        return [
                            'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                            'errors'   => $radiCorreosRadicados->getErrors(),
                            'status'  => false
                        ];
                    }

                } else {
                    return [
                        'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                        'errors'   => $model->getErrors(),
                        'status'  => false
                    ];
                }

            } else {
                return [
                    'message' => Yii::t('app', 'errorValidacion'), //'error al guardar en base de datos',
                    'errors'   => $model->getErrors(),
                    'status'  => false
                ];
            }

        }
        /** Fin Procesar lista de correos recibidos */

        $infoMailProcess .= ', Identificador de correos procesados: (' . implode(', ', $idsCorreosProcesados) . ')';

        return [
            '$countCorreosProcesados' => $countCorreosProcesados,
            'infoMailProcess' => $infoMailProcess, // Información de correos procesados
            'message' => 'Ok',
            'errors'   => [],
            'status'  => true
        ];
        
    }

    public static function generateXml($file_name ,$pathUploadFile, $emailData, $adjuntos = [])
    {        
        $objetoXML = new \XMLWriter();

        /** Estructura básica del XML */
        $objetoXML->openURI($pathUploadFile . $file_name);
    
        $objetoXML->setIndent(true);
        $objetoXML->setIndentString("\t");
        $objetoXML->startDocument('1.0', 'utf-8');
            
        /** Inicio del nodo raíz */
        $objetoXML->startElement("RadicacionEmail");
            
            $objetoXML->startElement("Datos");
            $objetoXML->writeAttribute("NumeroRadicado", $emailData["numeroRadicado"]);
            $objetoXML->writeAttribute("De", $emailData["From"] . '<'. $emailData["fromAddress"] .'>');
            $objetoXML->writeAttribute("Asunto", $emailData["subject"]);
            $objetoXML->writeAttribute("Fecha", $emailData["date"]);

                /** Cuerpo del correo */
                $objetoXML->startElement("CuerpoCorreo");
                $objetoXML->text($emailData['body']);
                $objetoXML->endElement();
                /** Fin Cuerpo del correo */

                /** Destinatarios */
                $objetoXML->startElement("Destinatarios");
                for ($i = 0; $i < sizeof($emailData['to']); $i++) {
                    $objetoXML->startElement("Destinatario");
                    $objetoXML->writeAttribute("Correo", $emailData['to'][$i]);
                    $objetoXML->endElement();
                }
                $objetoXML->endElement();
                /** Fin Destinatarios */

                /** Archivos Adjuntos */
                $objetoXML->startElement("ArchivosAdjuntos");
                foreach ($adjuntos as $adjunto) {
                    $objetoXML->startElement("Adjunto");
                    $objetoXML->writeAttribute("Nombre", $adjunto);
                    $objetoXML->endElement();
                }
                $objetoXML->endElement();
                /** Fin Archivos Adjuntos */

            $objetoXML->endElement();
            $objetoXML->fullEndElement ();

        $objetoXML->endElement();
        $objetoXML->endDocument();
        /** Fin del nodo raíz */

        return [
            'status' => true,
        ];
    }

    /**
     * Funcion que retorna un array de dias de la semana no validos a partir del dia inicio y dia fin del horario laboral
     */
    public static function getArrayDiasNoValidos($diaInicio, $diaFin)
    {
        $arrayDiasSemana = [0,1,2,3,4,5,6]; // [D,L,M,M,J,V,S]
        $arrayDiasNoValidos = [];

        if($diaInicio == $diaFin) {
            foreach($arrayDiasSemana as $dia){
                if( $dia != $diaInicio){
                    $arrayDiasNoValidos[] = $dia;
                }
            }
            return $arrayDiasNoValidos;
        }else{
            if ($diaInicio < $diaFin) {
                foreach($arrayDiasSemana as $dia){
                    if( !in_array($dia, range($diaInicio, $diaFin)) ){
                        $arrayDiasNoValidos[] = $dia;
                    }
                }
            } else { // ($diaInicio > $diaFin
                foreach($arrayDiasSemana as $dia){
                    if( in_array($dia, range($diaFin, $diaInicio)) && !in_array($dia, [$diaFin, $diaInicio]) ){
                        $arrayDiasNoValidos[] = $dia;
                    }
                }
            }
            return $arrayDiasNoValidos;
        }
    }

    /** 
     * Funicion que retorna la cantidad de dias habiles entre dos fechas
     * Nota: Si la fecha Desde es mayor a la fecha Hasta, entonces devuelve un numero negativo
     * @param $fechaDesde [DateTime] Date('Y-m-d H:i:s')
     * @param $fechaHasta [DateTime] Date('Y-m-d H:i:s')
     * @return $diasRestantes [int]
     */
    public static function calcularDiasEntreFechas($fechaDesde, $fechaHasta)
    {
        $horaDesde = date("H:i:s",strtotime($fechaDesde));
        $horaHasta = date("H:i:s",strtotime($fechaHasta));

        $dateInicio = date("Y-m-d",strtotime($fechaDesde));
        $dateFin = date("Y-m-d",strtotime($fechaHasta));

        $dateTimeInicio = new DateTime($dateInicio); // Se inicializa con hora 00:00:00 para cálculo inicial
        $dateTimeFin = new DateTime($dateFin); // Se inicializa con hora 00:00:00 para cálculo inicial

        $diasRestantes = $dateTimeInicio->diff($dateTimeFin);
        $diasRestantes = (int) $diasRestantes->days;

        if ($diasRestantes == 0) {
            return $diasRestantes;
        }

        $isFechaExpirada = false;
        if ($dateFin < $dateInicio) {
            $isFechaExpirada = true;
            $diasRestantes = 0 - $diasRestantes;
        }

        /** Días no laborables */
        $modelDiasNoLaborados = CgDiasNoLaborados::find()->select(['fechaCgDiaNoLaborado']);
        if ($isFechaExpirada == true) {
            $modelDiasNoLaborados->where(['BETWEEN', 'fechaCgDiaNoLaborado', $dateFin, $dateInicio]);
        } else {
            $modelDiasNoLaborados->where(['BETWEEN', 'fechaCgDiaNoLaborado', $dateInicio, $dateFin]);
        }
        $modelDiasNoLaborados->andWhere(['estadoCgDiaNoLaborado' => Yii::$app->params['statusTodoText']['Activo']]);
        $modelDiasNoLaborados->groupBy(['fechaCgDiaNoLaborado']);
        $modelDiasNoLaborados = $modelDiasNoLaborados->all();

        $arrayDiasNoLaborados = [];
        foreach($modelDiasNoLaborados as $diaNoLaboral){
            $arrayDiasNoLaborados[] = date("Y-m-d", strtotime($diaNoLaboral->fechaCgDiaNoLaborado));
        }
        /** Fin días no laborables */

        /** Días de la semana NO válidos según horario laboral activo */
        $arrayDiasNoValidos = [];
        $cgHorarioLaboral = CgHorarioLaboral::find()->where(['estadoCgHorarioLaboral' => Yii::$app->params['statusTodoText']['Activo']])->one();

        $countDiasNoValidos = 0;
        if($cgHorarioLaboral != null) {
            $diaInicioHorarioLaboral = $cgHorarioLaboral->diaInicioCgHorarioLaboral;
            $diaFinHorarioLaboral = $cgHorarioLaboral->diaFinCgHorarioLaboral;
            $horaIniHorarioLaboral = $cgHorarioLaboral->horaInicioCgHorarioLaboral;
            $horaFinHorarioLaboral = $cgHorarioLaboral->horaFinCgHorarioLaboral;

            $arrayDiasNoValidos = self::getArrayDiasNoValidos($diaInicioHorarioLaboral, $diaFinHorarioLaboral);

            /** Validar hora inicio y hora fin de Horario laboral para armar array de fechas inválidas */
            $arrayFechasInvalidas = [];
            if ($isFechaExpirada == false) {
                if ($horaDesde > $horaFinHorarioLaboral) {
                    $arrayFechasInvalidas[] = $dateInicio;
                }
                if ($horaHasta < $horaIniHorarioLaboral) {
                    $arrayFechasInvalidas[] = $dateFin;
                }
            } elseif ($isFechaExpirada == true) {
                if ($horaDesde < $horaIniHorarioLaboral) {
                    $arrayFechasInvalidas[] = $dateInicio;
                }
                if ($horaHasta > $horaFinHorarioLaboral) {
                    $arrayFechasInvalidas[] = $dateFin;
                }
            }
            /** Fin validar hora inicio y hora fin de Horario laboral para armar array de fechas inválidas */

            foreach ($arrayFechasInvalidas as $fechaInvalida) {
                if (!in_array($fechaInvalida, $arrayDiasNoLaborados)) {
                    $arrayDiasNoLaborados[] = $fechaInvalida;
                }
            }

            if (count($arrayDiasNoValidos) > 0) {

                /** Validar cantidad de días de la semana no laborados, excluyendo a los días feriados o no laborados  */
                $fechaBucle = $dateInicio;
                $variable = 0;
                while($variable <= 500) {
                    $variable++;

                    $numDia = date('w', strtotime($fechaBucle));

                    if( in_array($numDia, $arrayDiasNoValidos) && !in_array($fechaBucle, $arrayDiasNoLaborados) ){
                        $countDiasNoValidos++;
                    }

                    if ($isFechaExpirada == true) {
                        $fechaBucle = date("Y-m-d", strtotime($fechaBucle . "-1 days"));
                    } else {
                        $fechaBucle = date("Y-m-d", strtotime($fechaBucle . "+1 days"));
                    }

                    if($dateFin == $fechaBucle){
                        /** Validar ultima fecha del bucle */
                        $numDia = date('w', strtotime($fechaBucle));
                        if( in_array($numDia, $arrayDiasNoValidos) && !in_array($fechaBucle, $arrayDiasNoLaborados) ){
                            $countDiasNoValidos++;
                        }
                        /** Validar ultima fecha del bucle */
                        break; // bucle de fecha actual igual a la fecha de vencimiento
                    }
                }
                /** Fin validar cantidad de días de la semana no laborados, excluyendo a los días feriados o no laborados  */
            }


        }
        /** Fin días de la semana NO válidos según horario laboral activo */

        if ($isFechaExpirada == true) {
            $resultado = $diasRestantes + count($arrayDiasNoLaborados) + $countDiasNoValidos;
            //
            if($resultado > 0) { // Sucede solamente cuando todos los días fueron inválidos
                $resultado = 0;
            }
        } else {
            $resultado = $diasRestantes - count($arrayDiasNoLaborados) - $countDiasNoValidos;
            //
            if($resultado < 0) { // Sucede solamente cuando todos los días fueron inválidos
                $resultado = 0;
            }
        }

        return $resultado;
    }

    /**
     * Funcion creada solo para retornar una fecha recibida formateada de dos maneras utilizadas en la aplicacion
     * @param $fecha [Date]
     * @return [
     *      $formatoFrontend: 'dia' de 'mes' del 'año',
     *      $formatoBaseDatos: "d-m-Y"
     * ]
     */
    public static function getFormatosFecha($fecha)
    {
        $fechaFormatoDB = date("d-m-Y", strtotime($fecha));

        /* Formato Fecha Frontend */
        $arrayDias = Yii::t('app', 'days');
        $dia = $arrayDias[date('w', strtotime($fechaFormatoDB))];
        $arrayMeses = Yii::t('app', 'months');
        $mes = $arrayMeses[date('n', strtotime($fechaFormatoDB))];
        $explode = explode("-", $fechaFormatoDB);

        return [
            'formatoFrontend' => $dia . ' ' . $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2],
            'formatoBaseDatos' => $fechaFormatoDB,
        ];
    }

    /**
     * Función que realiza la limpieza de una cadena solo con los caracteres permitidos
     * Esto se realiza para que no genere error al momento de procesar el pdf y el xml
     */
    public static function limpiarCadena($cadena)
    {
        $permitidos = [
            'A','B','C','D','E','F','G','H','I','J','K','L','M','N','Ñ','O','P','Q','R','S','T','U','V','W','X','Y','Z',
            'a','b','c','d','e','f','g','h','i','j','k','l','m','n','ñ','o','p','q','r','s','t','u','v','w','x','y','z',
            'Á','É','Í','Ó','Ú','á','é','í','ó','ú','1','2','3','4','5','6','7','8','9','0',
            '<','>',' ','[',']','(',')','.',',',':',';','°','|','=','¬',
            '#','$','%','&','/','¿','¡','!','~',
            '+','-','*','{','}','_','-',
            '\\',"'",'"','`'
        ];

        $cadena = str_replace("\n", '<br>', $cadena);
        $cadenaSalida = '';

        for($i=0; $i<strlen($cadena); $i++){
            if (in_array($cadena[$i], $permitidos)) {
                $cadenaSalida .= $cadena[$i];
            } else {
                $cadenaSalida .= ' ';
            }
        }
        return $cadenaSalida;
    }

    /*
    * Función que detecta el lenguaje y retorrna el valor codificado
    */
    public static function detecteString($cadena)
    {

        /* Verifica la codificación del texto  */
        if (json_encode($cadena) == false) {
            try {
                // Convierte la decodificación en UFT-8
                $text = iconv('iso-8859-9', 'UTF-8', $cadena);
            }catch (\Throwable $th) {
                // limpia caracteres extranos 
                $text = self::limpiarCadena($cadena);
            }

        } else {
            try {
                // Convierte la decodificación en UFT-8
                $text = iconv( 'UTF-8','UTF-8', $cadena );
            }catch (\Throwable $th) {
                // limpia caracteres extranos 
                $text = self::limpiarCadena($cadena);
            }
        }

        return $text;

    }

}
