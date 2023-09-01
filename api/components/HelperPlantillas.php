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

use api\components\HelperConsecutivo;
use PhpOffice\PhpWord\TemplateProcessor;
use Picqer\Barcode\BarcodeGeneratorPNG;
use api\models\CgTransaccionesRadicados;
use api\models\Clientes;
use api\models\GdExpedientesInclusion;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\RadiEnvios;
use api\models\RadiInformados;
use api\models\RadiInformadoCliente;
use api\models\RadiLogRadicados;
use api\models\RadiRadicados;
use api\models\RadiRadicadosAsociados;
use api\models\User;
use api\models\UserDetalles;
use api\models\CgFirmasDocs;
use api\models\GdFirmasMultiples;
use api\models\GdTrdDependencias;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\CgRegionales;
use api\models\RadiRemitentes;
use api\models\RadiDocumentos;
use api\models\CgNumeroRadicado;
use api\modules\version1\controllers\radicacion\RadicadosController;

class HelperPlantillas
{

    private $filename;
    private $pathinfo;
    private $deleteTmp;

    
    public function __construct($filePath) 
	{
        
        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);  
        
        $this->filename = $filePath;
    }

    /**
     *  Valida si la plantilal existe - la convierte a texto y agrega estructura segun el esquema del documento
     *  @param array $pathinfo
     */
    public function convertToText() 
    {

        if(isset($this->filename) && !file_exists($this->filename)) {
            return [
                'status' => false,
                'message' => Yii::t('app','failTemplate'),
            ];
        }

        $this->pathinfo = pathinfo($this->filename);
        $file_ext  =  $this->pathinfo['extension'];

        switch($file_ext){

            case 'docx':
                $striped_content = $this->read_docx();
            break;

            case 'doc':

                # convertir archivo .doc a docx 
                // exec("unoconv  -f docx   {$this->filename}");
                exec("unoconvert --convert-to docx {$this->filename} ".$rutaDocx[0] .'.pdf');

                $file_temp_docx = str_replace(".doc",".docx",$this->filename);
                $striped_content = $this->read_docx($file_temp_docx);
                $this->filename = $file_temp_docx;
                # eliminar .docx temporal 
                //unlink($file_temp_docx);

            break;

            case 'odt':

                # convertir archivo .odt a docx 
                exec("unoconv  -f docx  {$this->filename}");

                $file_temp_docx = str_replace(".odt",".docx",$this->filename);
                $striped_content = $this->read_docx($file_temp_docx);
                $this->filename = $file_temp_docx;
                # eliminar .docx temporal 
                //unlink($file_temp_docx);

            break;

            default:
                return [
                    'status' => false,
                    'message' => Yii::t('app','mailIncorrectFormat'),
                ];
            break;
        }

        if(empty($striped_content)){
            return [
                'status' => false,
                'message' => Yii::t('app','fileNotProcessed'),
            ];
        }
        
        $structure_word = $this->structure_word($striped_content);
   
        if(!is_array($structure_word)){
            return [
                'status' => false,
                'message' => Yii::t('app','mailIncorrectFormat'),
            ];
        }

        return $structure_word;
    }

    /**
     *  Abre el documentos .docx y remplaza caracteres especiales y añade saltos de linea
     *  @param string $filename
     */
    public function read_docx($file_temp_docx = false)
    {

        if(!$file_temp_docx){
            $filename = $this->filename;
        }else{
            $filename = $file_temp_docx;
        }

        $striped_content = '';
        $content = '';

        if (!$filename || !file_exists($filename)) {
            return false;
        }
        
        $zipArchive = new \ZipArchive();
        $zip = $zipArchive->open($filename);
        
        if (!$zip || is_numeric($zip)) {
            return false;
        }
        
        for($i = 0; $i < $zipArchive->numFiles; $i++){
            $stat = $zipArchive->statIndex($i);
            
            if($stat['name'] != "word/document.xml"){
                continue;
            }
            
            $content = $zipArchive->getFromIndex($stat['index']);
        }
        $zipArchive->close();
        
        $content = str_replace('</w:r></w:p></w:tc><w:tc>', " ", $content);
        $content = str_replace('</w:r></w:p>', "\r\n", $content);
        $striped_content = strip_tags($content);

        return $striped_content;
    }

    /**
     *  Limpia el formato en texto busca y remplaza segun la esctructura configurada en params
     *  @param string $striped_content
     */
    public function structure_word($striped_content)
    {

        /** 
         * str_word_count — Devuelve información sobre las palabras utilizadas en un string
         * 
         *  @param 1 - devuelve un array que contiene todas las palabras encontradas dentro del string
         *  @param charlist una lista de caracteres adicionales los cuales serán considerados como de 'palabra'.
        */ 
        $str_word_count = str_word_count($striped_content, 1, Yii::$app->params['charlist']);
        $structure_word = [];

            foreach($str_word_count as $key => $text){

                // strpos — Encuentra la posición de la primera ocurrencia de un substring en un string
                
                $posFirst = strpos(trim($text), Yii::$app->params['word_string_first']); // Caracter especial Inicio [ { ]
                $posSecond = strpos(trim($text), Yii::$app->params['word_string_last']); // Caracter especial Fin    [ } ]

                // Si encuentra los dos caracteres en una palabra los remplaza por un espacio vacio para agregarlos a la parte key de un array
                if($posFirst !== false && $posSecond !== false){

                    $key_replace = str_replace([Yii::$app->params['word_string_first'],Yii::$app->params['word_string_last']],"",$text);

                    if(!empty($key_replace)){
                        $structure_word[trim($key_replace)] = '';   
                    }

                }
            }   

        return $structure_word; 

    }

    public function match_template($structure_word, $idRadiRadicado, $modelRadicadoGenerado = null){

        //$variablesPlantilla = CgPlantillaVariables::find()->where(['estadoCgPlantillaVariable' => Yii::$app->params['statusTodoText']['activo']])->all();
        $radiRadicado = RadiRadicados::find()->where(['idRadiRadicado' => $idRadiRadicado])->one();

        # Se realiza la consulta para obtener todos los remitentes que tiene un radicado.
        $modelRemitente = RadiRemitentes::findAll(['idRadiRadicado' => $radiRadicado->idRadiRadicado]);

        $HelperConsecutivo = new HelperConsecutivo();


        foreach($structure_word as $key => $value){

            switch ($key) {
                case 'numeroRadicado':
                    /** Validar si se generó un nuevo radicado en el proceso */
                    if ($modelRadicadoGenerado != null) {
                        $structure_word[$key] = $HelperConsecutivo->numeroRadicadoXTipoRadicadoConsultando($modelRadicadoGenerado->numeroRadiRadicado);
                    } else {
                        $structure_word[$key] = $HelperConsecutivo->numeroRadicadoXTipoRadicadoConsultando($radiRadicado->numeroRadiRadicado);
                    }
                break;

                case 'consecutivo':
                    /** Validar si se generó un nuevo radicado en el proceso */
                    if ($modelRadicadoGenerado != null) {
                        $structure_word[$key] = $HelperConsecutivo->extraerConsecutivo($modelRadicadoGenerado->numeroRadiRadicado);
                    } else {
                        $structure_word[$key] = $HelperConsecutivo->extraerConsecutivo($radiRadicado->numeroRadiRadicado);
                    }
                break;

                case 'fechaRadicacion':
                    $fechaFormatoDB =  date("d-m-Y", strtotime($radiRadicado->creacionRadiRadicado));

                    # formato fecha 
                    $dia = Yii::t('app', 'days')[date('w', strtotime($fechaFormatoDB))];
                    $mes = Yii::t('app', 'months')[date('n', strtotime($fechaFormatoDB))];

                    $explode = explode("-", $fechaFormatoDB);

                    $structure_word[$key] = $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2];
                break;

                case 'numeroresolucion':
                    if ($radiRadicado->radiRadicadosResoluciones) {
                        $structure_word[$key] = $radiRadicado->radiRadicadosResoluciones->numeroRadiRadicadoResolucion;
                    }
                break;

                case 'fecharesolucion':
                    if ($radiRadicado->radiRadicadosResoluciones) {
                        $structure_word[$key] = $radiRadicado->radiRadicadosResoluciones->fechaRadiRadicadoResolucion;
                    }
                break;

                case 'valorresolucion':
                    if ($radiRadicado->radiRadicadosResoluciones) {
                        $structure_word[$key] = $radiRadicado->radiRadicadosResoluciones->valorRadiRadicadoResolucion;
                    }
                break;

                case 'departamentoMunicipio':
                    $dataGeografico = [];
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){

                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);

                            # Departamento
                            $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $clientes->idNivelGeografico2]);

                            # Municipio
                            $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $clientes->idNivelGeografico3]);

                            # Array del departamento con el municipio del cliente
                            $dataGeografico[] = $nivelGeografico3->nomNivelGeografico3.' - '. $nivelGeografico2->nomNivelGeografico2;

                        } else {

                            # Se obtiene la información del usuario o funcionario
                            $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                            $idMunicipio = $modelUser->gdTrdDependencia->cgRegional->idNivelGeografico3;

                            # Municipio
                            $nivelGeografico3 = NivelGeografico3::findOne(['nivelGeografico3' => $idMunicipio]);

                            # Departamento
                            $nivelGeografico2 = NivelGeografico2::findOne(['nivelGeografico2' => $nivelGeografico3->idNivelGeografico2]);

                            # Array del departamento con el municipio del funcionario
                            $dataGeografico[] = $nivelGeografico3->nomNivelGeografico3.' - '. $nivelGeografico2->nomNivelGeografico2;
                        }
                    }

                    $structure_word[$key] = implode(", ",$dataGeografico);

                break;

                case 'remitente': 
                    $nombreRemitente = [];                  
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                            $nombreRemitente[] = $clientes->nombreCliente;
    
                        } else {                            
                            $funcionario = UserDetalles::findOne(['idUser' => $dataRemitente->idRadiPersona]);     
                            $nombreRemitente[] = $funcionario->nombreUserDetalles.' '.$funcionario->apellidoUserDetalles;
                        }
                    }

                    $strNombreRemitente = implode (", ", $nombreRemitente);
                    $strNombreRemitente = strtoupper($strNombreRemitente);

                    $structure_word[$key] = $strNombreRemitente;
                break;

                case 'direccion':
                    $direccion = [];
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                            $direccion[] = $clientes->direccionCliente;

                        } else {

                            # Se obtiene la información del usuario o funcionario
                            $modelUser = User::findOne(['id' => $dataRemitente->idRadiPersona]);
                            $direccion[] = $modelUser->gdTrdDependencia->nombreGdTrdDependencia;
                        }
                    }

                    $structure_word[$key] = implode(", ", $direccion);
                break;

                case 'asunto':
                    $structure_word[$key] = $radiRadicado->asuntoRadiRadicado;
                break;

                case 'fechaRespuesta': 

                    $fechaFormatoDB =  date("d-m-Y");
                    # formato fecha 
                    $dia = Yii::t('app', 'days')[date('w', strtotime($fechaFormatoDB))];
                    $mes = Yii::t('app', 'months')[date('n', strtotime($fechaFormatoDB))];

                    $explode = explode("-", $fechaFormatoDB);

                    $structure_word[$key] = $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2];
                break;

                case 'cargo': 
                    $userDetalles = UserDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
                    $structure_word[$key] = $userDetalles['cargoUserDetalles'];  
                break;

                case 'usuarioCreador':
                    $userDetalles = $radiRadicado->userIdTramitador->userDetalles;
                    $structure_word[$key] = $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'];  
                break;

                case 'voBoRadicado':

                    $transaccionVoBo = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'vobo']);
                    $voBoRadicado = RadiLogRadicados::findOne([
                        'idRadiRadicado' => $idRadiRadicado, 
                        'idTransaccion' => $transaccionVoBo['idCgTransaccionRadicado']]);

                        if(isset($voBoRadicado)){
                            $responsable = $voBoRadicado->user->userDetalles;
                            $structure_word[$key] = $responsable['nombreUserDetalles'].' '.$responsable['apellidoUserDetalles'];  
                        }
                
                break;

                case 'folios':
                    $structure_word[$key] = $radiRadicado['foliosRadiRadicado'];  
                break;

                case 'copia': 
                    
                    $informados_array = [];
                    $RadiInformados = RadiInformados::findAll(['idRadiRadicado' => $radiRadicado['idRadiRadicado']]);

                    foreach($RadiInformados as $value){

                        $userDetalles = $value->user->userDetalles;
                        $informados_array[] = $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles'];
                    }

                    $radiInformadoCliente = RadiInformadoCliente::find()
                        ->select(['idCliente'])
                        ->where(['idRadiRadicado' => $radiRadicado['idRadiRadicado']])
                        ->groupBy(['idCliente'])
                        ->all();

                    foreach($radiInformadoCliente as $value){

                        $clienteInfo = Clientes::find()->where(['idCliente' => $value['idCliente']])->one();
                        $informados_array[] = $clienteInfo['nombreCliente'];
                    }

                    $structure_word[$key] =  implode(", ",$informados_array);

                break;

                case 'email': 
                    $User = User::findOne(['id' => Yii::$app->user->identity->id]);
                    $structure_word[$key] = $User['email'];
                break;

                /*case 'tramite':
                    $structure_word[$key] = $radiRadicado->tramites['nombreCgTramite'];  
                break;*/

                case 'dependenciaCreador':
                    $structure_word[$key] = $radiRadicado->idTrdDepeUserTramitador0->nombreGdTrdDependencia; 
                break;

                case 'codDependenciaCreador':
                    $structure_word[$key] = $radiRadicado->idTrdDepeUserTramitador0->codigoGdTrdDependencia; 
                break;

                case 'documento':
                    $documento = [];
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                            $documento[] = $clientes->numeroDocumentoCliente;

                        } else {
                            $userDetalles = UserDetalles::findOne(['idUser' => $dataRemitente->idRadiPersona]);
                            $documento[] = $userDetalles->documento;
                        }
                    }

                    $structure_word[$key] = implode(", ", $documento);
                break;
                
                case 'username':
                    $userDetalles = User::findOne(['id' => Yii::$app->user->identity->id]);
                    $structure_word[$key] = $userDetalles['username'];
                break;

                case 'telefono':
                    $telefono = [];                  
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            
                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                            $telefono[] =  $clientes['telefonoCliente'];
                        }
                    }

                    $structure_word[$key] = implode(", ", $telefono);
                break;

                case 'tipoPersona':
                    $structure_word[$key] =  $radiRadicado->radiRemitentes->idTipoPersona0['tipoPersona'];
                break;

                case 'tipoDocumental':
                    $structure_word[$key] =  $radiRadicado->trdTipoDocumental['nombreTipoDocumental']; 
                break;

                case 'numeroExpediente':

                    $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                    if(isset($GdExpedientesInclusion)){
                        
                        $structure_word[$key] = $GdExpedientesInclusion->gdExpediente['numeroGdExpediente'];
                    }                    
                break;

                case 'nombreExpediente':
                    
                    $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                    if(isset($GdExpedientesInclusion)){      
                        $structure_word[$key] = $GdExpedientesInclusion->gdExpediente['nombreGdExpediente'];
                    }
                break;

                case 'serie':

                    if ($radiRadicado->idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                        $serie = GdTrdSeries::find()->where(['idGdTrdSerie' => $radiRadicado->idGdTrdSerie])->one();
                        if ($serie != null) {
                            $structure_word[$key] = $serie->nombreGdTrdSerie;
                        }
                    } else {
                        $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                        if(isset($GdExpedientesInclusion)){
                            $structure_word[$key] = $GdExpedientesInclusion->gdExpediente->gdTrdSerie['nombreGdTrdSerie'];
                        }
                    }

                break;
                
                case 'codSerie':

                    if ($radiRadicado->idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                        $serie = GdTrdSeries::find()->where(['idGdTrdSerie' => $radiRadicado->idGdTrdSerie])->one();
                        if ($serie != null) {
                            $structure_word[$key] = $serie->codigoGdTrdSerie;
                        }
                    } else {
                        $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                        if(isset($GdExpedientesInclusion)){
                            $structure_word[$key] = $GdExpedientesInclusion->gdExpediente->gdTrdSerie['codigoGdTrdSerie'];
                        }
                    }

                break;

                case 'subserie':

                    if ($radiRadicado->idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                        $subserie = GdTrdSubseries::find()->where(['idGdTrdSubserie' => $radiRadicado->idGdTrdSubserie])->one();
                        if ($subserie != null) {
                            $structure_word[$key] = $subserie->nombreGdTrdSubserie;
                        }
                    } else {
                        $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                        if(isset($GdExpedientesInclusion)){
                            $structure_word[$key] = $GdExpedientesInclusion->gdExpediente->gdTrdSubserie['nombreGdTrdSubserie'];
                        }
                    }

                break;

                case 'codSubserie':

                    if ($radiRadicado->idCgTipoRadicado != Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                        $subserie = GdTrdSubseries::find()->where(['idGdTrdSubserie' => $radiRadicado->idGdTrdSubserie])->one();
                        if ($subserie != null) {
                            $structure_word[$key] = $subserie->codigoGdTrdSubserie;
                        }
                    } else {
                        $GdExpedientesInclusion = GdExpedientesInclusion::findOne(['idRadiRadicado' => $idRadiRadicado]);
                        if(isset($GdExpedientesInclusion)){
                            $structure_word[$key] = $GdExpedientesInclusion->gdExpediente->gdTrdSubserie['codigoGdTrdSubserie'];
                        }
                    }

                break;

                case 'radicadoAsociado':
                    $asociados_array = [];
                    $radiRadicadosAsociados = RadiRadicadosAsociados::findAll(['idRadiCreado' => $radiRadicado['idRadiRadicado']]);

                    foreach($radiRadicadosAsociados as $value){
                        $asociados_array[] =  $value->idRadiAsociado0['numeroRadiRadicado'].'-'.$value->idRadiAsociado0['asuntoRadiRadicado'];
                    }

                    $structure_word[$key] =  implode(", ",$asociados_array); 
                break;

                case 'medioRecepcion':
                    $structure_word[$key] =  $radiRadicado->cgMedioRecepcion['nombreCgMedioRecepcion'];  
                break;

                case 'fechaVencimiento':
                    $fechaFormatoDB =  date("d-m-Y", strtotime($radiRadicado->fechaVencimientoRadiRadicados));

                    # formato fecha 
                    $dia = Yii::t('app', 'days')[date('w', strtotime($fechaFormatoDB))];
                    $mes = Yii::t('app', 'months')[date('n', strtotime($fechaFormatoDB))];

                    $explode = explode("-", $fechaFormatoDB);

                    $structure_word[$key] = $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2];
                break;

                case 'usuarioTramitador':
                    $usuarioTramitador =   $radiRadicado->userIdTramitador->userDetalles; 
                    $structure_word[$key] = $usuarioTramitador['nombreUserDetalles'].' '.$usuarioTramitador['apellidoUserDetalles'];  
                break;

                case 'dependenciaTramitador':
                    $structure_word[$key] = $radiRadicado->idTrdDepeUserTramitador0['nombreGdTrdDependencia'];
                break;

                case 'codDependenciaTramitador':
                    $structure_word[$key] = $radiRadicado->idTrdDepeUserTramitador0['codigoGdTrdDependencia'];
                break;

                case 'numeroGuia':
                    $numeroGuia = RadiEnvios::findOne(['idRadiRadicado' => $radiRadicado['idRadiRadicado']]);
                    if(isset($numeroGuia)){
                    $structure_word[$key] = $numeroGuia['numeroGuiaRadiEnvio'];
                    }
                break;

                case 'empresaMensajeria':
                    $RadiEnvios = RadiEnvios::findOne(['idRadiRadicado' => $radiRadicado['idRadiRadicado']]);
                    if(isset($RadiEnvios)){
                        $structure_word[$key] = $RadiEnvios->idCgProveedores0['nombreCgProveedor'];
                    }
                break;

                case 'usuarioSolicitudAnulacion':

                    $transaccionVoBo = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'annulationRequest']);
                    $annulationRequest = RadiLogRadicados::findOne([
                        'idRadiRadicado' => $idRadiRadicado, 
                        'idTransaccion' => $transaccionVoBo['idCgTransaccionRadicado']]);

                        if(isset($annulationRequest)){
                            $responsable = $annulationRequest->user->userDetalles;
                            $structure_word[$key] = $responsable['nombreUserDetalles'].' '.$responsable['apellidoUserDetalles'];  
                        }

                break;

                case 'usuarioAnulacion':

                    $transaccionVoBo = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'anulation']);
                    $anulation = RadiLogRadicados::findOne([
                        'idRadiRadicado' => $idRadiRadicado, 
                        'idTransaccion' => $transaccionVoBo['idCgTransaccionRadicado']]);

                        if(isset($anulation)){
                            $responsable = $anulation->user->userDetalles;
                            $structure_word[$key] = $responsable['nombreUserDetalles'].' '.$responsable['apellidoUserDetalles'];  
                        } 
                break;

                case 'usuarioEnvioCorrespondencia':
                    $RadiEnvios = RadiEnvios::findOne(['idRadiRadicado' => $radiRadicado['idRadiRadicado']]);
                    if(isset($RadiEnvios)){
                    $structure_word[$key] = $RadiEnvios->idUser0->userDetalles['nombreUserDetalles'].' '.$RadiEnvios->idUser0->userDetalles['apellidoUserDetalles']; 
                    } 
                break;

                case 'tipoRadicado':
                    $structure_word[$key] =  $radiRadicado->cgTipoRadicado['nombreCgTipoRadicado'];   
                break;

                case 'rutaElectronica':
                    $structure_word[$key] =  null;
                break;

                case 'cantidadAnexos':
                    /** Validar si se generó un nuevo radicado en el proceso */
                    if ($modelRadicadoGenerado != null) {
                        $structure_word[$key] = (int) RadiDocumentos::find()->where(['idRadiRadicado' => $modelRadicadoGenerado->idRadiRadicado])->count();
                    } else {
                        $structure_word[$key] = (int) RadiDocumentos::find()->where(['idRadiRadicado' => $radiRadicado->idRadiRadicado])->count();
                    }
                break;

                case 'ciudadRegionalDepeUser':

                    $ciudadUserLogued = '';
                    $deteUserLogued = GdTrdDependencias::find()->select(['idCgRegional'])->where(['idGdTrdDependencia' => Yii::$app->user->identity->idGdTrdDependencia])->one();
                    if ($deteUserLogued != null) {
                        $regionalUserLogued = CgRegionales::find()->select(['idNivelGeografico3'])->where(['idCgRegional' => $deteUserLogued->idCgRegional])->one();
                        if ($regionalUserLogued != null) {
                            $regionalUserLogued = NivelGeografico3::find()->select(['nomNivelGeografico3'])->where(['nivelGeografico3' => $regionalUserLogued->idNivelGeografico3])->one();
                            $ciudadUserLogued = $regionalUserLogued->nomNivelGeografico3;
                        }
                    }

                    $structure_word[$key] = $ciudadUserLogued;

                break;
                /** Espacio para la personalización del cliente */
                case 'numeroFactura':
                    $structure_word[$key] = $radiRadicado->numeroFactura;
                break;
                case 'fechaDocumentoOriginal':
                    $structure_word[$key] = $radiRadicado->fechaDocumentoRadiRadicado;
                break;
                case 'valorFactura':
                    $structure_word[$key] = $radiRadicado->valorFactura;
                break;
                case 'numeroContrato':
                    $structure_word[$key] = $radiRadicado->numeroContrato;
                break;
                /* Correo del remitente destinatario SSB*/
                case 'emailClientDest':
                    $emailClientDest = [];                  
                    foreach($modelRemitente as $dataRemitente){

                        if($dataRemitente->idTipoPersona != Yii::$app->params['tipoPersonaText']['funcionario']){
                            
                            $clientes = Clientes::findOne(['idCliente' => $dataRemitente->idRadiPersona]);
                            $emailClientDest [] =  $clientes['correoElectronicoCliente'];
                        }
                    }

                    $structure_word[$key] = implode(", ", $emailClientDest);
                break;
                /** Fin Espacio para la personalización del cliente */
                default:
                    //$structure_word['indefinida'][] = $key;
                break;
            }
        }  

        return $structure_word;

    }

    /**
     *  Añade la estructura y valores a la plantilla seleccionada
     *  @param array $structure_word
     *  @param string $saveAs
     */
    public function TemplateProcessor($structure_word, $nombreArchivo, $rutaPlantilla, $rutaPdfCombinacion, $firmaDocumento = false, $rutaElectronica = null, $numRadiBarcode = '', $idradiDocumentoPrincipal = '', $combinacion = true, $soloConvertirDocumento = false){

        if($soloConvertirDocumento) {
            $templateProcessor = new TemplateProcessor($rutaPlantilla);
            $file_temp_docx = $rutaPlantilla; //Actualmente se transforma todo lo que llegue a docx
            $file_pdf = $nombreArchivo .'.pdf';
            $rutaDocx = explode('.', $rutaPlantilla);

            // foreach($structure_word as $key => $value){
            //     $templateProcessor->setValue($key, $value);
            // }
            foreach($structure_word as $key => $value){
                if ($key == 'codigoBarrasRadicado') {
                    $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcodeCorrespondence_'. $numRadiBarcode .'.png';
                    $barcode = new BarcodeGeneratorPNG();
                    /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                    $barcode = $barcode->getBarcode($numRadiBarcode, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                    file_put_contents($pathBarcode  , $barcode);
                    $dimensionesImg = list($widthBarcode, $heightBarcode) = getimagesize($pathBarcode);
                    $templateProcessor->setImageValue('codigoBarrasRadicado', array('path' => $pathBarcode, 'width' => $widthBarcode, 'height' => $heightBarcode, 'ratio' => false));
                    unlink($pathBarcode);
                } else {
                    $templateProcessor->setValue($key,$value);
                }
            }

            $templateProcessor->saveAs($file_temp_docx);

            # Convertir la plantilla generada en un archivo pdf
            // exec("unoconv -f pdf  {$file_temp_docx}");
            exec("unoconvert --convert-to pdf {$file_temp_docx} ".$rutaDocx[0] .'.pdf');
        
            if(file_exists($rutaDocx[0] .'.pdf')) {
                copy($rutaDocx[0] .'.pdf', $rutaPdfCombinacion .''. $file_pdf);
            } else {
                return false;
            }

            return true;

        }
        else {

            $templateProcessor = new TemplateProcessor ($this->filename);

            # solo si es combinacion de correspondecia
            if($combinacion) {
                # Son los items que no deben reemplazarse al hacer la combinación de correspondencia
                $exceptions = ['rutaElectronica','firma','nombreFirma', 'numeroRadicado', 'codigoBarrasRadicado', 'fechaRadicacion', 'usuarioCreador', 'dependenciaCreador', 'consecutivo']; $i = 1; $j = 1;

                foreach($structure_word as $campo => $value){
                    if($campo == "firma#{$i}"){
                        array_push($exceptions, (string) $campo);
                        $i++; 
                    }

                    if($campo == "nombreFirma#{$j}"){
                        array_push($exceptions, (string) $campo);
                        $j++; 
                    }
                }

                foreach($structure_word as $key => $value){
                    if(!in_array($key, $exceptions)) {
        
                        if ($key == 'codigoBarrasRadicado') {
                            $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcodeCorrespondence_'. $numRadiBarcode .'.png';
                            $barcode = new BarcodeGeneratorPNG();
                            /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                            $barcode = $barcode->getBarcode($numRadiBarcode, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                            file_put_contents($pathBarcode  , $barcode);
                            $dimensionesImg = list($widthBarcode, $heightBarcode) = getimagesize($pathBarcode);
                            $templateProcessor->setImageValue('codigoBarrasRadicado', array('path' => $pathBarcode, 'width' => $widthBarcode, 'height' => $heightBarcode, 'ratio' => false));
                            unlink($pathBarcode);
                        }
                        elseif ($key == 'remitente') {
                            $value = preg_replace('/&/', 'Y', $value);
                            $templateProcessor->setValue($key,$value);
                        }
                        elseif ($key == 'asunto') {
                            $value = preg_replace('/&/', 'Y', $value);
                            $templateProcessor->setValue($key,$value);
                        } else {
                            $templateProcessor->setValue($key,$value);
                        }
                    }
                }
            }

            # Agregar Link
            $file_temp_docx = $rutaPlantilla.''.$nombreArchivo.'.docx'; //Actualmente se transforma todo lo que llegue a docx

            # Agregar Firma
            if($firmaDocumento != false && $combinacion == false){
                
                $user = User::findOne(['id' => Yii::$app->user->identity->id]);
                $UserDetalles = $user->userDetalles->nombreUserDetalles.' '.$user->userDetalles->apellidoUserDetalles;

                /** Definir ancho y alto de la imagen con respecto a sus dimensiones originales */
                $dimensionesImg = list($widthFirma, $heightFirma) = getimagesize($firmaDocumento);

                $height = 100;
                $width = (int) (($widthFirma * 100) / $heightFirma);

                /** */
                $firmaAsignada = false; $firmaNormal = false;

                $countFirmasFaltantes = GdFirmasMultiples::find()
                    ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                    ->andWhere(['<>', 'estadoGdFirmaMultiple', Yii::$app->params['statusDocsPrincipales']['Firmado']])
                    ->count(); 

                # almacenar numero de firmas
                foreach($structure_word as $campo => $value){
                
                    $firmasMultiples = GdFirmasMultiples::find()
                        ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                        ->andWhere([Yii::$app->params['like'], 'firmaGdFirmaMultiple', $campo])
                    ->one();

                    if(isset($firmasMultiples)){

                        if($firmasMultiples->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){

                            $firmaAsignada = true;
                            $variablesArray = explode(", ", $firmasMultiples->firmaGdFirmaMultiple);
            
                            foreach ($variablesArray as $key => $value) {
                                
                                if (strpos($value, 'firma') !== false) {
                                    /** Fin Definir ancho y alto de la imagen con respecto a sus dimensiones originales */
                                    $templateProcessor->setImageValue((string) $value , array('path' => $firmaDocumento, 'width' => $width, 'height' => $height, 'ratio' => false));
                                }

                                if (strpos($value, 'nombreFirma') !== false) {
                                    $templateProcessor->setValue((string) $value , $UserDetalles);
                                }
                            }
                           
                            $firmasMultiples->estadoGdFirmaMultiple = Yii::$app->params['statusDocsPrincipales']['Firmado'];
                            $firmasMultiples->userGdFirmaMultiple = Yii::$app->user->identity->id;
                            $firmasMultiples->save();
                            break;
                        }

                    }

                    if ($countFirmasFaltantes == 1) {
                        if ($campo == 'numeroRadicado') {
                            $templateProcessor->setValue($campo, HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($numRadiBarcode));
                        }

                        if ($campo == 'consecutivo') {
                            $templateProcessor->setValue($campo, HelperConsecutivo::extraerConsecutivo($numRadiBarcode));
                        }

                        if ($campo == 'fechaRadicacion') {
                            $fechaFormatoDB = date("Y-m-d H:i:s");
                            # formato fecha 
                            $dia = Yii::t('app', 'days')[date('w', strtotime($fechaFormatoDB))];
                            $mes = Yii::t('app', 'months')[date('n', strtotime($fechaFormatoDB))];
                            $explode = explode("-", $fechaFormatoDB);
                            $templateProcessor->setValue($campo, $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2]);
                        }

                        if ($campo == 'usuarioCreador') {
                            $userDetalles = Yii::$app->user->identity->userDetalles;
                            $templateProcessor->setValue($campo, $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles']);
                        }

                        if ($campo == 'dependenciaCreador') {
                            $templateProcessor->setValue($campo, Yii::$app->user->identity->gdTrdDependencia->nombreGdTrdDependencia);
                        }

                        if ($campo == 'codigoBarrasRadicado') {
                            $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcodeCorrespondence_'. $numRadiBarcode .'.png';
                            $barcode = new BarcodeGeneratorPNG();
                            /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                            $barcode = $barcode->getBarcode($numRadiBarcode, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                            file_put_contents($pathBarcode  , $barcode);
                            $dimensionesImg = list($widthBarcode, $heightBarcode) = getimagesize($pathBarcode);
                            $templateProcessor->setImageValue('codigoBarrasRadicado', array('path' => $pathBarcode, 'width' => $widthBarcode, 'height' => $heightBarcode, 'ratio' => false));
                            unlink($pathBarcode);
                        }
                    } 

                }

                if(!$firmaAsignada){
                    $templateProcessor->setImageValue("firma", array('path' => $firmaDocumento, 'width' => $width, 'height' => $height, 'ratio' => false));
                    $firmaNormal = true;
                }

                if ($rutaElectronica != null) {
                    $templateProcessor->setValue('rutaElectronica', $rutaElectronica);
                }
            }

            $templateProcessor->saveAs($file_temp_docx);  

            if(isset($file_docx) && !file_exists($file_temp_docx)) {
                return [
                    'status' => false,
                    'message' => Yii::t('app','failTemplate'),
                ];
            }

            # Si se firma el documento el archivo se convierte en .pdf
            if($firmaDocumento != false){
            
                $templateProcessor = new TemplateProcessor ($file_temp_docx);
                $templateProcessor->saveAs($rutaPdfCombinacion.''.$nombreArchivo.'.docx'); 

                $firmasMultiples = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])->all();

                $statusCombinado = false;

                foreach ($firmasMultiples as $key => $value) { 
                    if($value->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){
                        $statusCombinado = true; break;
                    }
                }

                if(!$statusCombinado || $firmaNormal){

                    $file_pdf = $nombreArchivo.'.pdf';

                    # Convertir la plantilla generada en un archivo pdf
                    // exec("unoconv -f pdf  {$file_temp_docx}");
                    exec("unoconvert --convert-to pdf {$file_temp_docx} ".$rutaPlantilla.''.$file_pdf);
        
                    if(file_exists($rutaPlantilla.''.$file_pdf)) {
                        copy($rutaPlantilla.''.$file_pdf, $rutaPdfCombinacion.''.$file_pdf);
                    }
                }   

            } else { // Sino esta en combinación y queda en .docx

                $file_pdf = $nombreArchivo.'.docx';
                copy($rutaPlantilla.''.$file_pdf, $rutaPdfCombinacion.''.$file_pdf);
            }

            // copiar de la carpeta temp a la ruta asignada
            // Eliminar copia pdf y docx de temp
            // unlink($rutaPlantilla.''.$file_pdf); 
            // unlink($file_temp_docx);

            # Elimina png QR
            if(file_exists($rutaPlantilla.''.Yii::$app->params['nameFileQR'])) {
                unlink($rutaPlantilla.Yii::$app->params['nameFileQR']); 
            }
        }

        return true;
        
    }

        /**
     *  Añade la estructura y valores a la plantilla seleccionada
     *  @param array $structure_word
     *  @param string $saveAs
     */
    public function TemplateProcessorFirmaDigital($structure_word, $nombreArchivo, $rutaPlantilla, $rutaPdfCombinacion, $firmaDocumento = false, $rutaElectronica = null, $numRadiBarcode = '', $idradiDocumentoPrincipal = '', $combinacion = true)
    {
        $templateProcessor = new TemplateProcessor ($this->filename);

        # Agregar Link
        $file_temp_docx = $rutaPlantilla.''.$nombreArchivo.'.docx'; //Actualmente se transforma todo lo que llegue a docx

        //sguarin No esta entrando al if de agregar firma, no se si es necesario
        # Agregar Firma
        if($firmaDocumento != false && $combinacion == false){
            
            $user = User::findOne(['id' => Yii::$app->user->identity->id]);
            $UserDetalles = $user->userDetalles->nombreUserDetalles.' '.$user->userDetalles->apellidoUserDetalles;

            $width = 100;
            $height = 100;

            /** */
            $firmaAsignada = false; $firmaNormal = false;

            $countFirmasFaltantes = GdFirmasMultiples::find()
                ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                ->andWhere(['<>', 'estadoGdFirmaMultiple', Yii::$app->params['statusDocsPrincipales']['Firmado']])
                ->count();

            # almacenar numero de firmas
            foreach($structure_word as $campo => $value){
            
                $firmasMultiples = GdFirmasMultiples::find()
                    ->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])
                    ->andWhere([Yii::$app->params['like'], 'firmaGdFirmaMultiple', $campo])
                ->one();

                if(isset($firmasMultiples)){

                    if($firmasMultiples->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){

                        $firmaAsignada = true;
                        $variablesArray = explode(", ", $firmasMultiples->firmaGdFirmaMultiple);
        
                        foreach ($variablesArray as $key => $value) {
                            
                            if (strpos($value, 'firma') !== false) {
                                /** Fin Definir ancho y alto de la imagen con respecto a sus dimensiones originales */
                                $templateProcessor->setImageValue((string) $value , array('path' => $firmaDocumento, 'width' => $width, 'height' => $height, 'ratio' => false));
                            }

                            if (strpos($value, 'nombreFirma') !== false) {
                                $templateProcessor->setValue((string) $value , $UserDetalles);
                            }
                        }
                       
                        $firmasMultiples->estadoGdFirmaMultiple = Yii::$app->params['statusDocsPrincipales']['Firmado'];
                        $firmasMultiples->userGdFirmaMultiple = Yii::$app->user->identity->id;
                        $firmasMultiples->save();
                        break;
                    }

                }

                $HelperConsecutivo = new HelperConsecutivo();

                if ($campo == 'numeroRadicado') {
                    // return 'entra a numero radicado';
                    $templateProcessor->setValue($campo, $HelperConsecutivo->numeroRadicadoXTipoRadicadoConsultando($numRadiBarcode));
                }

                if ($campo == 'consecutivo') {
                    $templateProcessor->setValue($campo, $HelperConsecutivo->extraerConsecutivo($numRadiBarcode));
                }

                if ($campo == 'codigoBarrasRadicado') {
                    // return 'barcode';
                    $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcodeCorrespondence_'. $numRadiBarcode .'.png';
                    $barcode = new BarcodeGeneratorPNG();
                    /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                    $barcode = $barcode->getBarcode($numRadiBarcode, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                    file_put_contents($pathBarcode  , $barcode);
                    $dimensionesImg = list($widthBarcode, $heightBarcode) = getimagesize($pathBarcode);
                    $templateProcessor->setImageValue('codigoBarrasRadicado', array('path' => $pathBarcode, 'width' => $widthBarcode, 'height' => $heightBarcode, 'ratio' => false));
                    unlink($pathBarcode);

                }

            }

            if ($rutaElectronica != null) {
                $templateProcessor->setValue('rutaElectronica', $rutaElectronica);
            }
        }

        $templateProcessor->saveAs($file_temp_docx);  

        if(isset($file_docx) && !file_exists($file_temp_docx)) {
            return [
                'status' => false,
                'message' => Yii::t('app','failTemplate'),
            ];
        }

        # Si se firma el documento el archivo se convierte en .pdf
        if($firmaDocumento != false){
        
            $templateProcessor = new TemplateProcessor ($file_temp_docx);
            $templateProcessor->saveAs($rutaPdfCombinacion.''.$nombreArchivo.'.docx'); 

            $firmasMultiples = GdFirmasMultiples::find()->where(['idradiDocPrincipal' => $idradiDocumentoPrincipal])->all();

            $statusCombinado = false;

            //sguarin cambiar el estado para que pueda entrar al conv
            $statusCombinadoSinFirmas = false;

            foreach ($firmasMultiples as $key => $value) { 
                if($value->estadoGdFirmaMultiple == Yii::$app->params['statusDocsPrincipales']['Combinado']){
                    $statusCombinado = true; break;
                }
            }

            //aca es donde el archivo se convierte a pdf
            if(!$statusCombinadoSinFirmas){

                $file_pdf = $nombreArchivo.'.pdf';

                # Convertir la plantilla generada en un archivo pdf
                // exec("unoconv -f pdf  {$file_temp_docx}");
                exec("unoconvert --convert-to pdf {$file_temp_docx} ".$rutaPlantilla.''.$file_pdf);
    
                if(file_exists($rutaPlantilla.''.$file_pdf)) {
                    copy($rutaPlantilla.''.$file_pdf, $rutaPdfCombinacion.''.$file_pdf);
                }
            }   

        } else { // Sino esta en combinación y queda en .docx

            $file_pdf = $nombreArchivo.'.docx';
            copy($rutaPlantilla.''.$file_pdf, $rutaPdfCombinacion.''.$file_pdf);
        }

        # Elimina png QR
        if(file_exists($rutaPlantilla.''.Yii::$app->params['nameFileQR'])) {
            unlink($rutaPlantilla.Yii::$app->params['nameFileQR']); 
        }

        return true;
        
    }

    /**
     *  Añade la estructura y valores a la plantilla seleccionada
     *  @param array $structure_word
     *  @param string $saveAs
     */
    public function TemplateProcessorSinVariables($HelperPlantillas, $radiplantilla, $documentoFirmar, $modelDocumentos, $gdTrdDependencias, $modelUser, $modelUserDetalle){

        // Se valida si el radicado que se esta procesando es un temporal o ya es uno definitivo
        if ((boolean) $radiplantilla->isRadicado == false) {

            $modelCgNumeroRadicado = CgNumeroRadicado::findOne(['estadoCgNumeroRadicado' => Yii::$app->params['statusTodoText']['Activo']]);
            $generateNumberFiling = RadicadosController::generateNumberFiling($radiplantilla->idCgTipoRadicado, $modelCgNumeroRadicado, $gdTrdDependencias, true);
        
            $radiplantilla->isRadicado = 1;
            $radiplantilla->numeroRadiRadicado = $generateNumberFiling['numeroRadiRadicado'];
            $radiplantilla->creacionRadiRadicado = date("Y-m-d H:i:s");
            $radiplantilla->user_idTramitador = $modelUser->id;

            if(!$radiplantilla->save()){ return false; }            
            $numRadiBarcode = $radiplantilla->numeroRadiRadicado;
        }

        // Se procesa el documento word para poner el codigo de barras y el radicado correspondiente al documento
        // $HelperPlantillas= new HelperPlantillas($documentoFirmar);
        $templateProcessor = new TemplateProcessor($documentoFirmar);
        $structure_word= $HelperPlantillas->convertToText();
        
        foreach($structure_word as $key => $value){
    
            if ($key == 'numeroRadicado') {
                $templateProcessor->setValue($key, HelperConsecutivo::numeroRadicadoXTipoRadicadoConsultando($numRadiBarcode));
            }

            if ($key == 'consecutivo') {
                $templateProcessor->setValue($key, HelperConsecutivo::extraerConsecutivo($numRadiBarcode));
            }

            if ($key == 'fechaRadicacion') {
                $fechaFormatoDB = date("Y-m-d H:i:s");
                # formato fecha 
                $dia = Yii::t('app', 'days')[date('w', strtotime($fechaFormatoDB))];
                $mes = Yii::t('app', 'months')[date('n', strtotime($fechaFormatoDB))];
                $explode = explode("-", $fechaFormatoDB);
                $templateProcessor->setValue($key, $explode[0] . ' ' . Yii::t('app', 'from') . ' ' . $mes . ' ' . Yii::t('app', 'of') . ' ' . $explode[2]);
            }

            if ($key == 'usuarioCreador') {
                $userDetalles = $modelUser->userDetalles;
                $templateProcessor->setValue($key, $userDetalles['nombreUserDetalles'].' '.$userDetalles['apellidoUserDetalles']);
            }

            if ($key == 'dependenciaCreador') {
                $templateProcessor->setValue($key, $modelUser->gdTrdDependencia->nombreGdTrdDependencia);
            }

            if ($key == 'codigoBarrasRadicado') {
                $pathBarcode = Yii::getAlias('@webroot') .'/' . Yii::$app->params['routeDownloads'] . '/barcodeCorrespondence_'. $numRadiBarcode .'.png';
                $barcode = new BarcodeGeneratorPNG();
                /** Se modifica tipo, medidas estandar y color para que se genere corectamente */
                $barcode = $barcode->getBarcode($numRadiBarcode, $barcode::TYPE_CODE_128, 2, 30, [1,0,0]);
                file_put_contents($pathBarcode  , $barcode);
                $dimensionesImg = list($widthBarcode, $heightBarcode) = getimagesize($pathBarcode);
                $templateProcessor->setImageValue('codigoBarrasRadicado', array('path' => $pathBarcode, 'width' => $widthBarcode, 'height' => $heightBarcode, 'ratio' => false));
                unlink($pathBarcode);
            } 

        }

        $file_temp_docx = $documentoFirmar; //Actualmente se transforma todo lo que llegue a docx
            // Se va a obtener el numero de hojas que tiene el documento para compararlo con el que llegue
            
            $rutaDocx = explode('.', $file_temp_docx);

            foreach($structure_word as $key => $value){
                $templateProcessor->setValue($key, $value);
            }

            $templateProcessor->saveAs($file_temp_docx);

            # Convertir la plantilla generada en un archivo pdf
            exec("unoconvert --convert-to pdf {$file_temp_docx} ".$rutaDocx[0] .'.pdf');
            // error_log(' #### '."unoconvert --convert-to pdf {$file_temp_docx} ".$rutaDocx[0] .'.pdf');
        
            if(file_exists($rutaDocx[0] .'.pdf')) {

                // error_log(' #### '.$rutaDocx[0] .'.pdf');
                $documentoFirmar = $rutaDocx[0] .'.pdf';
                //copy($rutaDocx[0] .'.pdf', $rutaPdfCombinacion .''. $infopdfDoc);
            }

        return $documentoFirmar;
        
    }
    
}
