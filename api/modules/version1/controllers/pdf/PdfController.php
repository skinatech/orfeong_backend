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

namespace api\modules\version1\controllers\pdf;

use common\models\User;
use api\components\HelperEncrypt;
use Yii;

use app\assets\AppAsset;

use kartik\mpdf\Pdf;
use yii\helpers\FileHelper;
use yii\web\Controller;

use \Mpdf\Mpdf;
/**
 * Clase única para generacion de pdf's por medio de la aplicación
 * Las respuestas emitidas por las funciones de esta clase solo son para debug de la aplicación
 */
class PdfController extends Controller
{   

    /**
     * Funcion para generarcion de pdf 
     */
    public static function generar_pdf($SetSubject, $template_html, $file_name ,$saved_path, $pdf_data, $attached_files = [], $footer = '|Page {PAGENO}|') {

        $renderParcial = '/pdf'.'/'.$template_html;

        $content = Yii::$app->controller->renderPartial($renderParcial,[
            'pdf_data' => $pdf_data,
            'attached_files' => $attached_files
        ]);

        if($footer != '|Page {PAGENO}|'){
            $content_footer = Yii::$app->controller->renderPartial('/pdf'.'/'.$footer,[
                'pdf_data' => $pdf_data,
            ]);
    
        } else {
            $content_footer = $footer;
        }

        $pdf = new Pdf([
            // set to use core fonts only
            'mode'   => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A4,
            // portrait orientation
            'orientation' =>  Pdf::ORIENT_PORTRAIT,
            // stream to browser inline
            'destination' => Pdf::DEST_FILE,
            // file name save serve
            'filename'    => $saved_path . '/' . $file_name . '.pdf',
            // your html content input
            'content'     => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting 
            'cssFile'   => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:18px}',
            // set mPDF properties on the fly
            'options'   => ['title' => 'Krajee Report Title','setAutoBottomMargin' => 'stretch'],
            // call mPDF methods on the fly
            'methods'   => [
                'SetSubject'  => $SetSubject,
                'SetKeywords' => 'Export, PDF',
                'SetFooter'   => [$content_footer],
            ]
        ]);

        $pdf->Render();
 
    }

    /**
     * Funcion para generarcion de pdf  formato horizontal expandido
     */
    public static function generar_pdf_formatoh($SetSubject, $template_html, $file_name ,$saved_path, $pdf_data, $attached_files = [], $userAuth = '', $footer = '|Page {PAGENO}|') {

        $renderParcial = '/pdf'.'/'.$template_html;
        
        $content = Yii::$app->controller->renderPartial($renderParcial,[
            'userAuth' => $userAuth,
            'pdf_data' => $pdf_data,
            'attached_files' => $attached_files
        ]);

        $pdf = new Pdf([
            // set to use core fonts only
            'mode'   => Pdf::MODE_CORE,
            // A4 paper format
            'format' => Pdf::FORMAT_A3,
            // portrait orientation
            'orientation' =>  Pdf::ORIENT_LANDSCAPE,
            // stream to browser inline
            'destination' => Pdf::DEST_FILE,
            // file name save serve
            'filename'    => $saved_path . '/' . $file_name . '.pdf',
            // your html content input
            'content'     => $content,
            // format content from your own css file if needed or use the
            // enhanced bootstrap css built by Krajee for mPDF formatting 
            'cssFile'   => '@vendor/kartik-v/yii2-mpdf/src/assets/kv-mpdf-bootstrap.min.css',
            // any css to be embedded if required
            'cssInline' => '.kv-heading-1{font-size:18px}',
            // set mPDF properties on the fly
            'options'   => ['title' => 'Krajee Report Title'],
            // call mPDF methods on the fly
            'methods'   => [
                'SetSubject'  => $SetSubject,
                'SetKeywords' => 'Export, PDF',
                'SetFooter'   => [$footer],  
            ]
        ]);

        $pdf->Render();
 
    }


    /** 
     * Generar pdf con la libreria mpdf
     * @param  $html [array] [Estructura en html del pdf]
     * @param  $carpeta [String] [Ruta de la ubicación del archivo]
     * @param  $nombrePdf [String] [Nombre del pdf]
     * @param  $style [String] [Estructura del style para el pdf]
     * @param  $orientation [String] [Orientación de la pagina 'P' = Vertical, 'L' = Horizontal]
     * @param  $margin [array] [Se define el mode, margin_left, margin_right, margin_top, margin_bottom, margin_header, margin_footer]
     * @param  $withMargin [boolean] [True = Con margenes, False = Sin margen y coloca las que tiene por defecto MPDF]
     */
    public static function generatePdf($html = [], $carpeta, $nombrePdf, $style = '', $orientation, $margin=[], $withMargin = false){

        if(count($html)> 0) {

            // Create an instance of the class:
            $mpdf = new Mpdf(['orientation' => $orientation]);

            # Margenes del archivo
            if($withMargin){
                $mpdf = new \Mpdf\Mpdf($margin);  
            }           

            $mpdf->SetDisplayMode('fullpage');

            # Se agregan estilos al pdf
            if ($style !== '') {                          
                $mpdf->WriteHTML($style,\Mpdf\HTMLParserMode::HEADER_CSS);
            }            

            # Write some HTML code:
            $mpdf->WriteHTML($html[0],2);
            unset($html[0]);

            foreach ($html as $page){
                $mpdf->AddPage();
                $mpdf->WriteHTML($page,2);
            }

            # Validar creación de la carpeta
            $rutaOk = true;
            $pathUploadFile = Yii::getAlias('@webroot') . "/" . $carpeta . '/';

            # Verificar ruta de la carpeta y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {
                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            if (!$rutaOk) {
                return [
                    'status'  => false,
                    'message' => Yii::t('app', 'errorValidacion'),
                    'errors' => ['error' => Yii::t('app', 'errorPathExcel') ], //Sirve el mismo error de path de excel
                    'rutaDocumento' => $pathUploadFile
                ];
            }
        
            $rutaDocumento = $pathUploadFile . $nombrePdf;     
            
            # Output a PDF file directly to the browser
            $mpdf->Output($rutaDocumento,\Mpdf\Output\Destination::FILE);

            return [
                'status'  => true,
                'message' => 'Ok',
                'errors'  => [],
                'rutaDocumento' => $rutaDocumento
            ];

        } 

        return false;       
         
    }

     /** 
     * Generar pdf con la libreria mpdf
     * @param  $html [array] [Estructura en html del pdf]
     * @param  $carpeta [String] [Ruta de la ubicación del archivo]
     * @param  $nombrePdf [String] [Nombre del pdf]
     * @param  $style [String] [Estructura del style para el pdf]
     */
    public static function generatePdfSinBordes($html = [], $carpeta, $nombrePdf, $style = ''){

        if(count($html)> 0) {

            // Create an instance of the class:
            $mpdf = new Mpdf();

            # Margenes del archivo
            $mpdf = new \Mpdf\Mpdf([
                'mode' => 'c',
                'margin_left' => 0,
                'margin_right' => 0,
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0
            ]);

            $mpdf->SetDisplayMode('fullpage');

            # Se agregan estilos al pdf
            if ($style !== '') {                          
                $mpdf->WriteHTML($style,\Mpdf\HTMLParserMode::HEADER_CSS);
            }            

            # Write some HTML code:
            $mpdf->WriteHTML($html[0],2);
            unset($html[0]);

            foreach ($html as $page){
                $mpdf->AddPage();
                $mpdf->WriteHTML($page,2);
            }

            
            # Validar creación de la carpeta
            $rutaOk = true;
            $pathUploadFile = Yii::getAlias('@webroot') . "/" . $carpeta . '/';

            # Verificar ruta de la carpeta y crearla en caso de que no exista
            if (!file_exists($pathUploadFile)) {
                if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            if (!$rutaOk) {
                return [
                    'status'  => false,
                    'message' => Yii::t('app', 'errorValidacion'),
                    'errors' => ['error' => Yii::t('app', 'errorPathExcel') ], //Sirve el mismo error de path de excel
                    'rutaDocumento' => $pathUploadFile
                ];
            }
        
            $rutaDocumento = $pathUploadFile . $nombrePdf;     
            
            # Output a PDF file directly to the browser
            $mpdf->Output($rutaDocumento,\Mpdf\Output\Destination::FILE);

            return [
                'status'  => true,
                'message' => 'Ok',
                'errors'  => [],
                'rutaDocumento' => $rutaDocumento
            ];

        } 

        return false;       
         
    }
}
