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

use api\models\OcrDatos;

class HelperExtraerTexto
{

    private $filename;
    private $pathinfo;
    private $routetmp;
    private $deleteTmp;

    public function __construct($file) 
	{

        # Aumento de memoria para procesar la escritura del archivos
        ini_set('memory_limit', '3073741824');
        ini_set('max_execution_time', 900);  
        
        $this->filename = $file;
        $this->routetmp = Yii::getAlias('@webroot') . "/tmp_docs". "/";
        # por si se sube un documento desde la pagina publica encuentre la ruta.
        $this->routetmp = str_replace('frontend/','api/', $this->routetmp);
        $this->pathinfo = pathinfo($file);

    }

    /**
     *  Valida si la plantilal existe - la convierte a texto y agrega estructura segun el esquema del documento
     *  @param array $pathinfo
     */
    public function convertToText() 
    {

        if(isset($this->filename) && !file_exists($this->filename)) {
            return [
                'status'  => false,
                'data'    => ['error' => [Yii::t('app','fileNotProcessed')]],
                'message' => Yii::t('app','failFileProcessd')
            ];
        }

        $file_ext = strtolower($this->pathinfo['extension']);

        switch($file_ext){

            case 'ods':  case 'fods':  case 'xls':  case 'xlsb':  case 'xlsx': 
                
                # convertir archivo .ext a csv 
                $exec = exec("export HOME={$this->routetmp} && soffice --headless --convert-to csv --outdir {$this->routetmp} {$this->filename}", $output, $return);
                $striped_content = $this->read_csv();

            break;

            case 'odt':  case 'doc':  case 'docx':  case 'rtf': case 'htm': case 'html':  

                # convertir archivo .ext a txt 
                $exec = exec("export HOME={$this->routetmp} && soffice --headless --convert-to txt --outdir {$this->routetmp} {$this->filename}", $output, $return);
                $striped_content = $this->read_ext('.txt');
            break;

            case 'pdf':

                exec("pdfinfo {$this->filename}", $output, $return);

                $this->routetmp = $this->routetmp.'pdf.txt';
                # convertir archivo .ext a text 
                if(isset($output)){ # PDF/A
                    exec("/usr/bin/pdftotext  {$this->filename} {$this->routetmp}", $output, $return);
                    $striped_content = $this->read_pdf('.txt');
                }

                # Formato no compatible
                if(empty($striped_content)){
                    return 'pdf';
                }
                
            break;

            case 'tiff': // case 'pdfs':  case 'png': case 'jpg': case 'etc':

                 # convertir archivo .ext a text 
                 exec("tesseract {$this->filename} {$this->routetmp} -l spa");
                 $striped_content = $this->read_pdf('.txt');
 
            break;

            default:
                return [
                    'status'  => true,
                    'data'    => ['error' => [Yii::t('app','fileNotProcessed')]],
                    'message' => Yii::t('app','failFileProcessd')
                ];
            break;
        }

        if(empty($striped_content)){
            return [
                'status'  => false,
                'data'    => ['error' => [Yii::t('app','fileNotProcessed')]],
                'message' => Yii::t('app','fileNotProcessed')
            ];
        }
        return $striped_content;
    }

    /**
     *  Abre el documentos .ext  y remplaza caracteres especiales y añade saltos de linea
     *  @return string $content_file
     */
    public function read_ext($ext = false)
    {
        $filename = $this->pathinfo['filename'];

        if(file_exists($this->routetmp.$filename.$ext)) {
            $text = utf8_encode(file_get_contents("{$this->routetmp}{$filename}{$ext}")); 

            $text = preg_replace('/[^ ]{14}[^ ]*/', '', $text);
            $text = preg_replace('/[^a-zA-Z0-9\s]/', "", $text);
            $text = preg_replace('/\n[\s]*/',"\n",$text); // remove all leading blanks]
            $text = wordwrap($text,150);
            $text = str_replace("\n", "\n<br>", $text);
            $text = preg_replace('/<br>..?.?\n/',"",$text);

        }

        return $text ?? false;
    }


    /**
     *  Abre el documentos .ext  y remplaza caracteres especiales y añade saltos de linea
     *  @return string $content_file
     */
    public function read_pdf($ext = false)
    {   

        if(file_exists($this->routetmp)) {

            $text = file_get_contents ($this->routetmp);

            $text = preg_replace('/[^ ]{14}[^ ]*/', '', $text);
            $text = preg_replace('/[^a-zA-Z0-9\s]/', "", $text);
            $text = preg_replace('/\n[\s]*/',"\n",$text); // remove all leading blanks]
            $text = wordwrap($text,150);
            $text = str_replace("\n", "\n<br>", $text);
            $text = preg_replace('/<br>..?.?\n/',"",$text);

        }
    
        return $text ?? false;
    }

    /**
     *  Abre el documentos .ext  y remplaza caracteres especiales y añade saltos de linea
     *  @return string $content_file
     */
    public function read_csv($ext = '.csv')
    {

        $linea = 0; $text = '';
        //Abrimos nuestro archivo
        $file = $this->routetmp.$this->pathinfo['filename'].$ext;

        if(file_exists($file)) {

            $archivo = fopen($file, "r"); 

            while (($datos = fgetcsv($archivo, ",")) !== FALSE) {
                $numero = count($datos);
                $linea++;
                for ($c=0; $c < $numero; $c++) {
                    $text = $text . $datos[$c] . "<br/>";
                }
            }

            fclose($archivo);
        }

        return $text;
    }

    /**
     *  Añade la estructura y valores a la plantilla seleccionada
     *  @param array $structure_word
     *  @param string $saveAs
     */
    public function helperOcrDatos($idDocumento, $tablaOcr)
    {

        # convertir documento a formato legible 
        $content = $this->convertToText();

    
        if(isset($content['status'])){
            return $content;

        }else if($content == 'pdf'){
            return [
                'status' => true
            ];
        }

        $octDatos = new OcrDatos();

        $octDatos->idDocumentoOcrDatos   = $idDocumento;
        $octDatos->tablaAfectadaOcrDatos = $tablaOcr;
        $octDatos->textoExtraidoOcrDatos = self::detecteString($content);
        
        if(!$octDatos->save()){
            return [
                'status' => false,
                'data' => $octDatos->getErrors(),
                'message' => Yii::t('app','fileNotProcessed'),
            ];
        }
    
        return [
            'status' => true
        ];
    }   

    /**
     * Función que realiza la limpieza de una cadena solo con los caracteres permitidos
     * Esto se realiza para que no genere error al momento de procesar el .docx y xlsx
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
            if (in_array($cadena¨[$i], $permitidos)) {
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
