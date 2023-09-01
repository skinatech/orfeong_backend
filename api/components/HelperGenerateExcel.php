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
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\SHEETSTATE_HIDDEN;


use yii\helpers\FileHelper;

class HelperGenerateExcel
{
    /** 
     * Crea un excel con la libreria de phpOffice
     * @param $path [string] [Ruta de la carpeta donde se almacena el documento]
     * @param $fileName [string] [Nombre del documento de excel con su extension]
     * @param $mergeCells [array] [Array que contiene las celdas combinadas]
     * @param $arrayTitulos [array] [Array donde contiene los titulos generados]
     * @param $arrayCuerpo [array] [Array donde contiene el cuerpo del excel y requiera alinear el texto a la derecha]
     * @param $img [Se envia true si se necesita crear una imagen]*/  
    public static function generarExcel($path, $fileName, $array, $listSelect, $mergeCells = [], $arrayTitulos = [], $arrayCabeceras = [], $arrayCuerpo = [], $img = false, $maxLenCells = [])
    {
        // Crea un  nuevo archivo
        $file = new Spreadsheet();

        // Se indica en que hoja se va a modificar.
        $file->setActiveSheetIndex(0);

        //Se modifica las celdas
        $sheet = $file->getActiveSheet();

        // Se crea la imagen si se envia un true a la funcion
        if($img == true) {
            // Agrega imagen en el encabezado si hay información en la data
            if (!empty($arrayCuerpo)) {
                
                $imagePath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogo'];
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($imagePath);
                $drawing->setCoordinates('A2');
                $drawing->setWidthAndHeight(320,65);
                $drawing->setOffsetY(3);
                $drawing->setOffsetX(85);
                $drawing->setWorksheet($file->getActiveSheet());
            }
        }

        //Seccion de modificacion por celdas
        foreach ($array as $cell => $value) {
            $sheet->SetCellValue($cell, $value);

            //Autoajustar tamaño de la celda
            $column = self::getColumn($cell);
            $sheet->getColumnDimension('A')->setWidth(25);
            $sheet->getColumnDimension('B')->setWidth(13);
            $sheet->getColumnDimension('C')->setWidth(45);
            $sheet->getColumnDimension('D')->setWidth(13);
            $sheet->getColumnDimension('E')->setWidth(45);
            $sheet->getColumnDimension('F')->setWidth(9);
            $sheet->getColumnDimension('G')->setWidth(45);
            $sheet->getColumnDimension('H')->setWidth(10);
            $sheet->getColumnDimension('I')->setWidth(37);
            $sheet->getColumnDimension($column)->setAutoSize(true);            
        }

        //Columnas tipo select
        foreach($listSelect as $column => $value){
            for($row=2; $row<=101; $row++){
                $cell = $column . $row;                

                $objValidation = $sheet->getCell($cell)->getDataValidation();
                $objValidation->setType(DataValidation::TYPE_LIST);
                $objValidation->setShowDropDown(true);

                $objValidation->setFormula1('"' . $value . '"');    
                
            }         

        }
        
        //Columnas con texto con máxima longitud
        foreach($maxLenCells as $column => $value){
            for($row=2; $row<=101; $row++){
                $cell = $column .  $row;

                $objValidation = $sheet->getCell($cell)->getDataValidation();
                $objValidation->setType(DataValidation::TYPE_TEXTLENGTH);
                //$objValidation->setErrorStyle( PHPExcel_Cell_DataValidation::STYLE_STOP );
                $objValidation->setAllowBlank(true);
                $objValidation->setShowInputMessage(true);
                $objValidation->setShowErrorMessage(true);
                $objValidation->setErrorTitle('Input error');
                $objValidation->setError('Se ha excedido la cantidad máxima de caracteres');
                $objValidation->setPromptTitle('Allowed input');
                $objValidation->setPrompt('Máximo 80 caracteres');
                $objValidation->setFormula1(0);
                $objValidation->setFormula2($value);               
            }
        }

        foreach ($mergeCells as $key => $value) {
            $sheet->mergeCells($value);
           // $sheet->getStyle($value)->getFont()->setBold(true);
            $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($value)->getFont()->setName('Arial');
            $sheet->getStyle($value)->getFont()->setSize(10);
        }

       
        foreach ($arrayTitulos as $key => $value) {
            $sheet->getStyle($value)->getAlignment()->setHorizontal('left');
            $sheet->getStyle($value)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('22409A');
            $sheet->getStyle($value)->getFont()->getColor()->setARGB('FFFFFF');
            $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($value)->getAlignment()->setVertical('center');
            $sheet->getStyle($value)->getAlignment()->setWrapText(true); 
            $sheet->getStyle($value)->getFont()->setName('Arial');
            $sheet->getStyle($value)->getFont()->setSize(10);
        }
        
        foreach ($arrayCabeceras as $key => $value) {
            $sheet->getStyle($value)->getFont()->setBold(true);
            $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
            $sheet->getStyle($value)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('22409A');
            $sheet->getStyle($value)->getFont()->getColor()->setARGB('FFFFFF');
        }

        # Este estilo de cuerpo es solo cuando se requiera alinear la información a la derecha
        foreach ($arrayCuerpo as $key => $value) {

            $sheet->getStyle($value)->getFont()->setName('Arial');
            $sheet->getStyle($value)->getFont()->setSize(9);

            $columnaAfectada = substr($value, 0, 1);            
            if($columnaAfectada == 'B' or $columnaAfectada == 'D' or $columnaAfectada == 'F' or $columnaAfectada == 'H'){
                $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
            }else{
                $sheet->getStyle($value)->getAlignment()->setHorizontal('left');
                $sheet->getStyle($value)->getAlignment()->setWrapText(true);
            }
            
        }

        /*** Validar creación de la carpeta ***/
        $rutaOk = true;
        $pathUploadFile = Yii::getAlias('@webroot') . "/" . $path . '/';

        // Verificar ruta de la carpeta y crearla en caso de que no exista
        if (!file_exists($pathUploadFile)) {
            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                $rutaOk = false;
            }
        }

        if ($rutaOk == false) {
            return [
                'status'  => false,
                'message' => Yii::t('app', 'errorValidacion'),
                'errors' => ['error' => Yii::t('app', 'errorPathExcel') ],
                'rutaDocumento' => $pathUploadFile
            ];
        }
        /*** Validar creación de la carpeta ***/

        $rutaDocumento = $pathUploadFile . $fileName;        
            
        $writer = IOFactory::createWriter($file, 'Xlsx');

        $writer->save($rutaDocumento);

        return [
            'status'  => true,
            'message' => 'Ok',
            'errors'  => [],
            'rutaDocumento' => $rutaDocumento
        ];
    }

    /**
     * Función para generar el excel de las TRDs 
     * @param $path [String][Lugar de alojamiento del archivo de la TRD]
     * @param $fileName [String][Nombre del archivo]
     * @param $sheets [Array][Estructura del archivo de excel] 
     **/
    public static function generarExcelSheet($path, $fileName, $sheets, $img = false)
    {
        // Crea un  nuevo archivo
        $file = new Spreadsheet();

        //Recorre todas las hojas a crear en el arhicvo excel
        foreach($sheets as $sheetIndex => $sheetData){
            //Crear una nueva hoja
            if($sheetIndex > 0){
                $file->createSheet();
            }            

            // Se crea la imagen si se envia un true a la funcion
            // if($img == true) {
            //     // Agrega imagen en el encabezado si hay información en la data
            //         $imagePath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogo'];
            //         $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
            //         $drawing->setPath($imagePath);
            //         $drawing->setCoordinates('A1');
            //         // $drawing->setWidthAndHeight(320,65);
            //         // $drawing->setOffsetY(1);
            //         // $drawing->setOffsetX(85);
            //         $drawing->setWorksheet($file->getActiveSheet());
            // }    

            
            //Se modifica las celdas            
            $sheet = $file->setActiveSheetIndex($sheetIndex);
            $sheet->setTitle($sheetData['sheetName']); // Nombre de las hojas del excel
        
            //Seccion de modificacion por celdas
            foreach ($sheetData['cells'] as $cell => $value) {
                //Formato texto
                $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('@');   

                //Insertar valor en celda
                $sheet->setCellValueExplicit($cell, $value, 's');

                # Se obtiene la columna y fila de la celda
                $column = self::getColumn($cell);
                $row = self::getRow($cell);
               
                # Ajusta el texto
                $sheet->getStyle($cell)->getAlignment()->setWrapText(true); 

                # Dimensiones de ancho y alto de la celda    
                $sheet->getColumnDimension($column)->setAutoSize(true);
                $sheet->getRowDimension($row)->setRowHeight(16);

                //centrado vertical
                $sheet->getStyle($cell)->getAlignment()->setVertical('center');
                $sheet->getStyle($cell)->getFont()->setName('Arial');
                $sheet->getStyle($cell)->getFont()->setSize(10); 
            }

            //Columnas tipo select
            foreach($sheetData['listSelect'] as $column => $value){
                for($row=2; $row<=101; $row++){
                    $cell = $column . $row;                

                    $objValidation = $sheet->getCell($cell)->getDataValidation();
                    $objValidation->setType(DataValidation::TYPE_LIST);
                    $objValidation->setShowDropDown(true);

                    $objValidation->setFormula1('"' . $value . '"');           
                    
                }         

            }
            
            /** Aplicando estilos de borde */
            foreach ($sheetData['borders'] as $cell){
                
                $styleSet = [
                    // BORDER
                    'borders' => [
                        'outline' => [
                            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                            'color' => ['argb' => '000000']
                        ]
                    ],
                ];
                $style = $sheet->getStyle($cell);
                $style->applyFromArray($styleSet);
            }
            
            # Combinación de celdas
            foreach ($sheetData['mergeCells'] as $key => $value) {
                $sheet->mergeCells($value);
                $sheet->getStyle($value)->getFont()->setBold(true);
                //$sheet->getStyle($value)->getAlignment()->setWrapText(true);                
            }

            foreach ($sheetData['titles'] as $key => $value) {
                $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($value)->getFont()->getColor()->setARGB('FFFFFF');
                $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($value)->getAlignment()->setVertical('center');
                //$sheet->getStyle($value)->getAlignment()->setWrapText(true);   
            }
            
            foreach ($sheetData['headers'] as $key => $value) {
                $sheet->getStyle($value)->getFont()->setBold(true);
                $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
                $sheet->getStyle($value)->getFont()->getColor()->setARGB('FFFFFF');
                //$sheet->getStyle($value)->getAlignment()->setWrapText(true); 
            }

            # Ajusta el texto
            foreach($sheetData['wrappedCells'] as $cell){
                $sheet->getStyle($cell)->getAlignment()->setWrapText(true);
                $sheet->getStyle($cell)->getFont()->setSize(9);
                $column = self::getColumn($cell);  // Obtener la columna de la celda
                $sheet->getColumnDimension($column)->setAutoSize(false);
                $sheet->getColumnDimension($column)->setWidth(70);
            }

            //Negrilla
            foreach($sheetData['boldCells'] as $cell){
                $sheet->getStyle($cell)->getFont()->setBold(true);
                $sheet->getStyle($cell)->getAlignment()->setHorizontal('center');
            }

            //Center
            foreach($sheetData['centerCells'] as $cell){
                $sheet->getStyle($cell)->getAlignment()->setHorizontal('center');
                //$sheet->getStyle($cell)->getAlignment()->setWrapText(true);   
            }           
        }  

        /*** Validar creación de la carpeta ***/
        $rutaOk = true;
        $pathUploadFile = Yii::getAlias('@webroot') . "/" . $path . '/';

        // Verificar ruta de la carpeta y crearla en caso de que no exista
        if (!file_exists($pathUploadFile)) {
            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                $rutaOk = false;
            }
        }

        if ($rutaOk == false) {
            return [
                'status'  => false,
                'message' => Yii::t('app', 'errorValidacion'),
                'errors' => ['error' => Yii::t('app', 'errorPathExcel') ],
                'rutaDocumento' => $pathUploadFile
            ];
        }
        /*** Validar creación de la carpeta ***/

        $rutaDocumento = $pathUploadFile . $fileName;        
            
        $writer = IOFactory::createWriter($file, 'Xlsx');
        $writer->save($rutaDocumento);

        return [
            'status'  => true,
            'message' => 'Ok',
            'errors'  => [],
            'rutaDocumento' => $rutaDocumento
        ];
    }

    /* Genera excel en extesion .xlsx */
    public static function generarExcelV2($path, $fileName, $sheets, $img = false){

        // Crea un  nuevo archivo
        $file = new Spreadsheet();

        foreach($sheets as $sheetIndex => $sheetData){
            
            //Crear una nueva hoja
            if($sheetIndex > 0){
                $file->createSheet();
            }

            //Se modifica las celdas
            $sheet = $file->setActiveSheetIndex($sheetIndex);
            $sheet->setTitle($sheetData['sheetName']);

            // Se crea la imagen si se envia un true a la funcion
            if($img == true) {
                // Agrega imagen en el encabezado si hay información en la data
                $imagePath = Yii::getAlias('@webroot') . "/" .'img/'.Yii::$app->params['rutaLogo'];
                $drawing = new \PhpOffice\PhpSpreadsheet\Worksheet\Drawing();
                $drawing->setPath($imagePath);
                $drawing->setCoordinates('A2');
                $drawing->setWidthAndHeight(320,65);
                $drawing->setOffsetY(3);
                $drawing->setOffsetX(85);
                $drawing->setWorksheet($file->getActiveSheet());
            }

            if(isset($sheetData['columnDimensions'])){
                foreach($sheetData['columnDimensions'] as $column => $dimension){
                    $sheet->getColumnDimension($column)->setWidth($dimension);
                }
            }

            if(isset($sheetData['noVisible']) && $sheetData['noVisible'] == true){
                $sheet->getProtection()->setSheet(true);

                //Oculta la hoja del listado generado
                $sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_HIDDEN);
                //$sheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);
            }

            if(isset($sheetData['arrayTitulo'])){                
                foreach ($sheetData['arrayTitulo'] as $key => $value) {
                    $sheet->getStyle($value)->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setARGB('22409A');
                    $sheet->getStyle($value)->getFont()->getColor()->setARGB('FFFFFF');
                    $sheet->getStyle($value)->getAlignment()->setHorizontal('center');
                    $sheet->getStyle($value)->getAlignment()->setVertical('center');
                    $sheet->getStyle($value)->getAlignment()->setWrapText(true); 
                    $sheet->getStyle($value)->getFont()->setName('Arial');
                    $sheet->getStyle($value)->getFont()->setSize(10);
                }
            }

            # Combinación de celdas
            if(isset($sheetData['mergeCells'])){
                foreach ($sheetData['mergeCells'] as $key => $value) {
                    $sheet->mergeCells($value);
                }
            }

            //Negrilla
            if(isset($sheetData['arrayBoldCells'])){
                foreach($sheetData['arrayBoldCells'] as $cell){
                    $sheet->getStyle($cell)->getFont()->setBold(true);
                }
            }

            //Se recorre las celdas de la hoja actual
            foreach($sheetData['cells'] as $cell=> $cellData){

                if(isset($cellData['value'])){
                    $sheet->setCellValue($cell, $cellData['value']);
                }

                // if(isset($cellData['styles'])){
                //     foreach($cellData['styles'] as $style){                        
                //     }
                // }                
               
                if(isset($cellData['validation'])){

                    $validation = $cellData['validation'];
                    
                    $objValidation = $sheet->getCell($cell)->getDataValidation();                        
        
                    if($validation['type']=='TYPE_LIST'){
                        $objValidation->setType(DataValidation::TYPE_LIST);
                        $objValidation->setShowDropDown(true);
                   }                            
                     
                    elseif($validation['type']=='TYPE_TEXTLENGTH'){
                        $objValidation->setType(DataValidation::TYPE_TEXTLENGTH);                            
                    }

                    if(isset($validation['formula1'])){
                        $objValidation->setFormula1($validation['formula1']);
                    }

                    if(isset($validation['formula2'])){
                        $objValidation->setFormula2($validation['formula2']);
                    }

                    if(isset($validation['message'])){
                        $objValidation->setError($validation['message']);
                        $objValidation->setShowErrorMessage(true);
                    }
          
                }

                if(isset($cellData['format'])){                   
                    $format = $sheet->getStyle($cell)->getNumberFormat();

                    if($cellData['format'] == 'text'){                       
                        $format->setFormatCode('@');

                    }else if($cellData['format'] == 'number'){                        
                        $format->setFormatCode('0');
                    }                    
                    
                }

            }

        }

        /*** Validar creación de la carpeta ***/
        $rutaOk = true;
        $pathUploadFile = Yii::getAlias('@webroot') . "/" . $path . '/';

        // Verificar ruta de la carpeta y crearla en caso de que no exista
        if (!file_exists($pathUploadFile)) {
            if (!FileHelper::createDirectory($pathUploadFile, $mode = 0775, $recursive = true)) {
                $rutaOk = false;
            }
        }

        if ($rutaOk == false) {
            return [
                'status'  => false,
                'message' => Yii::t('app', 'errorValidacion'),
                'errors' => ['error' => Yii::t('app', 'errorPathExcel') ],
                'rutaDocumento' => $pathUploadFile
            ];
        }
        /*** Validar creación de la carpeta ***/

        $rutaDocumento = $pathUploadFile . $fileName;        
            
        $writer = IOFactory::createWriter($file, 'Xlsx');

        $writer->save($rutaDocumento);

        return [
            'status'  => true,
            'message' => 'Ok',
            'errors'  => [],
            'rutaDocumento' => $rutaDocumento
        ];
    }


    //Eliminar el valor numerico para dejar solo la letra
    private static function getColumn($cellKey)
    {
        $column = $cellKey;
        for($i=0; $i<10; $i++){
            $column = str_replace($i, '', $column); 
        }             
        return $column;
    }

    private static function getRow($cellKey){
        $row = $cellKey;
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
            'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];
        foreach($letras as $letra){
            $row = str_replace($letra, '', $row); 
        }             
        return $row;
    }

    public static function generateReporte($path, $fileName, $titles, $content, $type) {
        $document = new Spreadsheet();
        $sheetDocument = $document->getActiveSheet();
        $sheetDocument->setTitle($fileName);

        foreach($titles as $key => $title) {
            $headTitles[] = $titles[$key]['title'];
        }
        $sheetDocument->fromArray($headTitles, null, 'A1');

        $initRow = 2;
        foreach($content as $key => $data) {
            foreach($titles as $key2 => $title) {
                $sheetDocument->setCellValueByColumnAndRow($key2 + 1, $initRow, strval($content[$key][$titles[$key2]['data']]));
            }

            $initRow++;
        }

        $extFile =  ($type === 'Xlsx') ? '.xlsx' : '.csv';

        $routeDocument = $path . $fileName . $extFile;
        $writer = IOFactory::createWriter($document, $type);
        $writer->save($routeDocument);
        return [
            'status'  => true,
            'message' => 'Ok',
            'errors'  => [],
            'routeDocument' => $routeDocument
        ];
    }
}

