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
use api\components\HelperQueryDb;

use api\models\CgTrd;
use Yii;
use yii\helpers\FileHelper;

use api\models\GdTrdDependencias;
use api\models\GdTrdSeries;
use api\models\GdTrdSubseries;
use api\models\TiposIdentificacion;
use api\models\UserTipo;
use api\models\Roles;
use api\models\GdTrd;
use api\models\GdIndices;
use api\models\GdExpedientes;
use api\models\GaArchivo;
use api\models\GdExpedientesInclusion;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;
use api\models\TiposPersonas;



class HelperLoads
{
    /* Construcción del formato de usuarios */
    public static function generateLoadUsers()
    {
        $sheets = [];    
        
        /* Hoja oculta para listar las opciones de los campos tipo select */
        $sheets[1]['sheetName'] = 'data';

        //Ocultar
        $sheets[1]['noVisible'] = true;

        $sheets[1]['cells']['A1']['value'] = 'Nombre dependencia';        
        $dependencias = GdTrdDependencias::find()
            ->where(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();
        
        $lonListaA =  0;
        foreach($dependencias as $key => $dependencia){
            $row = $key +2;
            $sheets[1]['cells']['A'.$row]['value'] = $dependencia->nombreGdTrdDependencia;
            $lonListaA = $row;
        }
        
        $sheets[1]['cells']['D1']['value'] = 'Tipo identificación';
        $tiposIdentificacion = TiposIdentificacion::find()
            ->where(['estadoTipoIdentificacion' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();
        
        $lonListaD =  0;
        foreach($tiposIdentificacion as $key => $tipoIdentificacion){
            $row = $key +2;
            $sheets[1]['cells']['D'.$row]['value'] = $tipoIdentificacion->nombreTipoIdentificacion;
            $lonListaD =  $row;
        }
        
        $sheets[1]['cells']['H1']['value'] = 'Tipo de usuario';        
        $userTipo = UserTipo::find()
            ->where(['estadoUserTipo' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();
        
        $lonListaH =  0;
        foreach($userTipo as $key => $userTipoItem){
            $row = $key +2;
            $sheets[1]['cells']['H'.$row]['value'] = $userTipoItem->nombreUserTipo;
            $lonListaH =  $row;
        }

        $sheets[1]['cells']['I1']['value'] = 'Perfil';
        $roles = Roles::find()
            ->where(['estadoRol' => Yii::$app->params['statusTodoText']['Activo']])
        ->all();
        
        $lonListaI = 0;
        foreach($roles as $key => $rol){
            $row = $key +2;
            $sheets[1]['cells']['I'.$row]['value'] = $rol->nombreRol;
            $lonListaI = $row;
        }
        
        $sheets[1]['cells']['K1']['value'] = 'Autenticación por LDAP';
        $sheets[1]['cells']['K2']['value'] = 'Si';
        $sheets[1]['cells']['K3']['value'] = 'No';       
        $lonListaK = 3;
        
        /* Hoja para ingresar datos de usuarios */
        $sheets[0]['sheetName'] = 'users';

        $sheets[0]['columnDimensions']['A'] = 20;
        $sheets[0]['columnDimensions']['B'] = 20;
        $sheets[0]['columnDimensions']['C'] = 20;
        $sheets[0]['columnDimensions']['D'] = 20;
        $sheets[0]['columnDimensions']['E'] = 20;
        $sheets[0]['columnDimensions']['F'] = 20;
        $sheets[0]['columnDimensions']['G'] = 20;
        $sheets[0]['columnDimensions']['H'] = 20;
        $sheets[0]['columnDimensions']['I'] = 20;
        $sheets[0]['columnDimensions']['J'] = 20;
        $sheets[0]['columnDimensions']['K'] = 20;
        $sheets[0]['columnDimensions']['L'] = 20;

        //Titulos de cabeceras
        $sheets[0]['arrayTitulo'][] = 'A1';
        $sheets[0]['arrayTitulo'][] = 'B1';
        $sheets[0]['arrayTitulo'][] = 'C1';
        $sheets[0]['arrayTitulo'][] = 'D1';
        $sheets[0]['arrayTitulo'][] = 'E1';
        $sheets[0]['arrayTitulo'][] = 'F1';
        $sheets[0]['arrayTitulo'][] = 'G1';
        $sheets[0]['arrayTitulo'][] = 'H1';
        $sheets[0]['arrayTitulo'][] = 'I1';
        $sheets[0]['arrayTitulo'][] = 'J1';
        $sheets[0]['arrayTitulo'][] = 'K1';


        $sheets[0]['cells']['A1']['value'] = 'Nombre dependencia';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Nombre dependencia';
            $row = $i;
            $sheets[0]['cells']['A'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['A'.$row]['validation']['formula1'] = 'data!$A$2:$A$'.$lonListaA;
            $sheets[0]['cells']['A'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'outListInput', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }     

        $sheets[0]['cells']['B1']['value'] = 'Nombre de Usuario';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Nombre del usuario';
            $row = $i;
            $sheets[0]['cells']['B'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['B'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['B'.$row]['validation']['formula2'] = 80;
            $sheets[0]['cells']['B'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitName', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }     

        $sheets[0]['cells']['C1']['value'] = 'Apellido de Usuario';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Apellido del usuario';
            $row = $i;
            $sheets[0]['cells']['C'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['C'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['C'.$row]['validation']['formula2'] = 80;
            $sheets[0]['cells']['C'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitSurname', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        } 
        
        $sheets[0]['cells']['D1']['value'] = 'Tipo identificación';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Tipo identificación';
            $row = $i;
            $sheets[0]['cells']['D'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['D'.$row]['validation']['formula1'] = 'data!$D$2:$D$'.$lonListaD;
            $sheets[0]['cells']['D'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'outListInput', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['E1']['value'] = 'Documento identidad';
        for($i=2; $i<Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Documento de identidad';
            $row = $i;
            $sheets[0]['cells']['E'.$row]['format'] = 'text';
            $sheets[0]['cells']['E'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['E'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['E'.$row]['validation']['formula2'] = 20;
            $sheets[0]['cells']['E'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitDocument', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['F1']['value'] = 'Cargo';
        for($i=2; $i<Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Cargo del usuario';
            $row = $i;
            $sheets[0]['cells']['F'.$row]['format'] = 'text';
            $sheets[0]['cells']['F'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['F'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['F'.$row]['validation']['formula2'] = 80; 
            $sheets[0]['cells']['F'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitPosition', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['G1']['value'] = 'Correo electrónico institucional';
        for($i=2; $i<Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Correo electrónico';
            $row = $i;
            $sheets[0]['cells']['G'.$row]['format'] = 'text';
            $sheets[0]['cells']['G'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['G'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['G'.$row]['validation']['formula2'] = 80;
            $sheets[0]['cells']['G'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitEmail', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }
        

        $sheets[0]['cells']['H1']['value'] = 'Tipo de usuario';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Tipo de usuario';
            $row = $i;
            $sheets[0]['cells']['H'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['H'.$row]['validation']['formula1'] = 'data!$H$2:$H$'.$lonListaH;
            $sheets[0]['cells']['H'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'outListInput', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['I1']['value'] = 'Perfil';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Perfil';
            $row = $i;
            $sheets[0]['cells']['I'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['I'.$row]['validation']['formula1'] = 'data!$I$2:$I$'.$lonListaI;
            $sheets[0]['cells']['I'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'outListInput', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['J1']['value'] = 'Usuario de autenticación';
        for($i=2; $i<Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Usuario de autenticación';
            $row = $i;

            $sheets[0]['cells']['J'.$row]['format'] = 'text';
            $sheets[0]['cells']['J'.$row]['validation']['type'] = 'TYPE_TEXTLENGTH';
            $sheets[0]['cells']['J'.$row]['validation']['formula1'] = 0;
            $sheets[0]['cells']['J'.$row]['validation']['formula2'] = 80;
            $sheets[0]['cells']['J'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'maxLimitPosition', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna                                
            ]));
        }

        $sheets[0]['cells']['K1']['value'] = 'Autenticación por LDAP';
        for($i=2; $i<=Yii::$app->params['rowsUploadExcel']; $i++){
            $nomColumna = 'Autenticación por LDAP';
            $row = $i;
            $sheets[0]['cells']['K'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['K'.$row]['validation']['formula1'] = 'data!$K$2:$K$'.$lonListaK;
            $sheets[0]['cells']['K'.$row]['validation']['message'] = strip_tags(Yii::t('app', 'outListInput', [
                'fila'      => $row,
                'nomColumna'=> $nomColumna 
            ]));
        }      
        
        return $sheets;
    }


    /**
     * Descarga de la TRD 
     * @param $idDependence [int][Id de la dependencia]
     **/
    public static function generateDownloadTrd($idDependence){

        $cells = [];
        $mergeCells = [];    // Combinación de celdas
        $borders = []; 
        $wrappedCells = [];  // Ajusta el texto
        $boldCells = [];
        $sheetName = '';     // Nombre de la hoja
        $centerCells = [];

        # Consulta de la TRD activa de la dependencia seleccionada
        if($idDependence != '0'){
            $modelGdTrd = GdTrd::find()
                ->where(['idGdTrdDependencia' => $idDependence])
                ->andWhere(['estadoGdTrd' => Yii::$app->params['statusTodoText']['Activo']])
                ->orderBy([
                    'idGdTrdDependencia' => SORT_ASC,
                    'idGdTrdSerie' => SORT_ASC,
                    'idGdTrdSubserie' => SORT_ASC,
                    //'idGdTrdTipoDocumental' => SORT_ASC,
                    'idGdTrd' => SORT_ASC
                ])
            ->all();
        }

        if(empty($modelGdTrd)){
            return [
                'arrayExcel' => [],
                'mergeCells' => [],
                'arrayTitulos' => [],
                'arrayCabeceras' => [],
                'status' => 'trdDoesntExist',
            ];
        }
        
        // Variable que indica la posición de los titulos principales (FILA)
        $configuration = CgTrd::findOne(['estadoCgTrd' => Yii::$app->params['statusTodoText']['Activo']]);        
        $rowHeaderTitle = (int) substr($configuration->cellDatosCgTrd,1) - 2; // Celda de inicio de datos - 2      
        $rowSecondHeader = (int) substr($configuration->cellDatosCgTrd,1) - 1;  // Celda de inicio de datos - 1   

        
        //Negrilla
        $boldCells[] = 'A'.$rowSecondHeader;
        $boldCells[] = 'B'.$rowSecondHeader;
        $boldCells[] = 'C'.$rowSecondHeader;
        $boldCells[] = 'D'.$rowSecondHeader;
        $boldCells[] = 'E'.$rowSecondHeader;
        $boldCells[] = 'F'.$rowSecondHeader;
        $boldCells[] = 'G'.$rowSecondHeader;
        $boldCells[] = 'H'.$rowSecondHeader;
        $boldCells[] = 'I'.$rowSecondHeader;
        $boldCells[] = 'J'.$rowSecondHeader;
        $boldCells[] = 'K'.$rowSecondHeader;
        $boldCells[] = 'L'.$rowSecondHeader;
        $boldCells[] = 'M'.$rowSecondHeader;
        $boldCells[] = 'N'.$rowSecondHeader;
        $boldCells[] = 'O'.$rowSecondHeader;
        
        # Separador de la máscara
        $separador = $configuration->idMascaraCgTrd0->separadorCgTrdMascara; 

        # Se obtiene la celda final del documento
        if(!is_null($configuration->columnTipoDocCgTrd)) {
            $numberColumnEnd = self::numberByLetter($configuration->columnProcessCgTrd, null) + 1;           
            $letterColumnEnd = self::numberByLetter(null, $numberColumnEnd); 
        } 
        
        if(!is_null($configuration->columnProcessCgTrd) && is_null($configuration->columnTipoDocCgTrd)) {  
            $numberColumnEnd = self::numberByLetter($configuration->columnProcessCgTrd, null) + 1;         
            $letterColumnEnd = $configuration->columnProcessCgTrd; 
        }
        

        /************************************** ENCABEZADO Y TITULOS **************************************/

        # Nombre del cliente y combinación de celdas en el encabezado dependiendo de la configuración
        $numberTitle = (int) substr($configuration->cellDependenciaPadreCgTrd,1) - 1;
        $cells['C'.$numberTitle] = Yii::$app->params['fondo'] . "\n" . 'TABLAS DE RETENCIÓN DOCUMENTAL';
        if($numberTitle != 1) {
            for ($i = 1; $i <= $numberTitle; $i++){     
                $mergeCells[] = 'A'.$i.':'.'B'.$numberTitle;    
                $mergeCells[] = 'C'.$i.':'.$letterColumnEnd.$i;           
            }     
            $numberTitle++; 

        } else {
            $mergeCells[] = 'A'.$numberTitle.':'.'B'.$numberTitle;
            $mergeCells[] = 'C'.$numberTitle.':'.$letterColumnEnd.$numberTitle;
            $numberTitle++;
        }
               
        
        # Ubicación merge de la dependencia padre
        if(!is_null($configuration->cellDependenciaPadreCgTrd)){
            $mergeCells[] = $configuration->cellDependenciaPadreCgTrd.':'.$letterColumnEnd.$numberTitle;
            $numberTitle++;            
        }   

        # Ubicación merge de la dependencia
        if(!is_null($configuration->cellTituloDependCgTrd)){
            $mergeCells[] = $configuration->cellTituloDependCgTrd.':'.$letterColumnEnd.$numberTitle;
            $numberTitle++;
        }           

        # Ubicación merge de la regional
        if(!is_null($configuration->cellRegionalCgTrd)){
            $mergeCells[] = $configuration->cellDependenciaCgTrd.':'.$letterColumnEnd.$numberTitle;
            $numberTitle++;
            $mergeCells[] = $configuration->cellRegionalCgTrd.':'.$letterColumnEnd.$numberTitle;            
        }

        # Ubicación de la palabra Código
        if(!is_null($configuration->column3CodigoCgTrd)){
            $mergeCells[] = $configuration->columnCodigoCgTrd.$rowHeaderTitle.':'.$configuration->column3CodigoCgTrd.$rowHeaderTitle;  
            $cells[$configuration->columnCodigoCgTrd.$rowHeaderTitle] = 'Código';
        } else {
            $mergeCells[] = $configuration->columnCodigoCgTrd.$rowHeaderTitle.':'.$configuration->columnCodigoCgTrd.$rowHeaderTitle;
            $cells[$configuration->columnCodigoCgTrd.$rowHeaderTitle] = 'Código';
        }
               
        
        # Cuando en una misma columna esta la dependencia, serie y subserie
        if(!is_null($configuration->columnCodigoCgTrd) && is_null($configuration->column2CodigoCgTrd) && is_null($configuration->column3CodigoCgTrd) ){
            $cells[$configuration->columnCodigoCgTrd.$rowSecondHeader] = 'D'.$separador.'S'.$separador.'Sb'; 
            $concat = $configuration->columnCodigoCgTrd; 
        }

        # Cuando el código de dependencia se ubica en una columna
        if(!is_null($configuration->columnCodigoCgTrd)){
            $cells[$configuration->columnCodigoCgTrd.$rowSecondHeader] = 'D';
            $codDepe = $configuration->columnCodigoCgTrd;
        } 
        
         # Cuando el código de serie se ubica en una columna
        if(!is_null($configuration->column2CodigoCgTrd)){
            $cells[$configuration->column2CodigoCgTrd.$rowSecondHeader] = 'S';
            $column2CodigoCgTrd = $configuration->column2CodigoCgTrd;            
        } 

         # Cuando el código de subserie se ubica en una columna
        if(!is_null($configuration->column3CodigoCgTrd)){
            $cells[$configuration->column3CodigoCgTrd.$rowSecondHeader] = 'Sb';
            $column3CodigoCgTrd = $configuration->column3CodigoCgTrd;
        } 
        
        # Ubicación de los nombres de la serie, subserie y tipo documental
        if(!is_null($configuration->columnNombreCgTrd)){
            $cells[$configuration->columnNombreCgTrd.$rowSecondHeader] = 'Series, subseries y tipología documental';
            $columnNombreCgTrd = $configuration->columnNombreCgTrd;
        }
                
        # Ubicación de la columna Retención
        $mergeCells[] = $configuration->columnAgCgTrd.$rowHeaderTitle.':'.$configuration->columnAcCgTrd.$rowHeaderTitle;
        $cells[$configuration->columnAgCgTrd.$rowHeaderTitle] = 'Retención';
        
        if(!is_null($configuration->columnAgCgTrd)){
            $cells[$configuration->columnAgCgTrd.$rowSecondHeader] = 'AG';
            $columnAgCgTrd = $configuration->columnAgCgTrd;
        }
        if(!is_null($configuration->columnAcCgTrd)){
            $cells[$configuration->columnAcCgTrd.$rowSecondHeader] = 'AC';
            $columnAcCgTrd = $configuration->columnAcCgTrd;
        }

        # Ubicación de la columna Disposición
        $numberColumn1 = self::numberByLetter($configuration->columnCtCgTrd, null) + 3;           
        $letterColumn1 = self::numberByLetter(null, $numberColumn1); 
        $mergeCells[] = $configuration->columnCtCgTrd.$rowHeaderTitle.':'.$letterColumn1.$rowHeaderTitle;
        $cells[$configuration->columnCtCgTrd.$rowHeaderTitle] = 'Disposición';
        
        if(!is_null($configuration->columnCtCgTrd)){
            $cells[$configuration->columnCtCgTrd.$rowSecondHeader] = 'CT';
            $columnCtCgTrd = $configuration->columnCtCgTrd;
        }
        if(!is_null($configuration->columnECgTrd)){
            $cells[$configuration->columnECgTrd.$rowSecondHeader] = 'E';
            $columnECgTrd = $configuration->columnECgTrd;
        }
        if(!is_null($configuration->columnMCgTrd)){
            $cells[$configuration->columnMCgTrd.$rowSecondHeader] = 'D/M';
            $columnMgTrd = $configuration->columnMCgTrd;
        }
        if(!is_null($configuration->columnSCgTrd)){
            $cells[$configuration->columnSCgTrd.$rowSecondHeader] = 'S';
            $columnSCgTrd = $configuration->columnSCgTrd;
        }
        
        # Ubicación de la columna del procedimiento
        if(!is_null($configuration->columnProcessCgTrd)){
            $cells[$configuration->columnProcessCgTrd.$rowSecondHeader] = 'Procedimiento';
            $columnProcessCgTrd = $configuration->columnProcessCgTrd;
        }
        
        # Ubicación de la columna de la Norma
        if(!is_null($configuration->columnNormaCgTrd)){
            $cells[$configuration->columnNormaCgTrd.$rowSecondHeader] = 'Norma / SIG';
            $columnNormaCgTrd = $configuration->columnNormaCgTrd;
        } else {
            $columnNormaCgTrd = null;
        }

        # Ubicación de la columna del soporte
        if(!is_null($configuration->columnPSoporteCgTrd) && !is_null($configuration->columnOsoporteCgTrd)){
            $mergeCells[] = $configuration->columnPSoporteCgTrd.$rowHeaderTitle.':'.$configuration->columnOsoporteCgTrd.$rowHeaderTitle;
            $cells[$configuration->columnPSoporteCgTrd.$rowHeaderTitle] = 'Soporte';
        }
        
        if(!is_null($configuration->columnPSoporteCgTrd)){
            $cells[$configuration->columnPSoporteCgTrd.$rowSecondHeader] = 'P';
            $columnPSoporteCgTrd = $configuration->columnPSoporteCgTrd;
        } else {
            $columnPSoporteCgTrd = null;
        }
        
        if(!is_null($configuration->columnESoporteCgTrd)){
            $cells[$configuration->columnESoporteCgTrd.$rowSecondHeader] = 'E';
            $columnESoporteCgTrd = $configuration->columnESoporteCgTrd;
        } else {
            $columnESoporteCgTrd = null;
        }

        if(!is_null($configuration->columnOsoporteCgTrd)){
            $cells[$configuration->columnOsoporteCgTrd.$rowSecondHeader] = 'O';
            $columnOsoporteCgTrd = $configuration->columnOsoporteCgTrd;
        } else {
            $columnOsoporteCgTrd = null;
        }

        # Dias tipo documental, obtengo la columna del procedimiento y le sumo una columna más para ubicar los dias  
        if(!is_null($configuration->columnProcessCgTrd) && !is_null($configuration->columnTipoDocCgTrd)){
            $cells[$letterColumnEnd.$rowSecondHeader] = 'Días tipo documental';
            $columnTipoDocCgTrd = $letterColumnEnd;
        } else {
            $columnTipoDocCgTrd = null;
        }


        # Inicialización variables
        // $codigoSerie = '';        
        $row = 1;
        $column = 0;        
        $idDependencia = 0;
        $idSerie = 0;
        $idSubserie = 0;
        $codigoPadreDependencia = '';          
        // $pSupport = 0;
        // $eSupport = 0;
        // $oSupport = 0;              


        /*************************************** CONTENIDO DEL EXCEL *****************************************/
        
        # Fila de inicio de datos
        $rowData = substr($configuration->cellDatosCgTrd,1);  

        # Impresión de la información de la TRD
        foreach($modelGdTrd as $trdItem){           
          
            # Informaciñon de la dependencia
            if($trdItem->idGdTrdDependencia != null && $idDependencia !== $trdItem->idGdTrdDependencia){

                $idDependencia = $trdItem->idGdTrdDependencia;  
                $dependencyCode = $trdItem->gdTrdDependencia->codigoGdTrdDependencia;
                $dependencyName = $trdItem->gdTrdDependencia->nombreGdTrdDependencia; 
                $codigoPadreDependencia = $trdItem->gdTrdDependencia->codigoGdTrdDepePadre;
                $regional = $trdItem->gdTrdDependencia->cgRegional->nombreCgRegional;               
                $dependencyNameParent = '';

                $dependenciaModel = GdTrdDependencias::find()
                    ->where(['idGdTrdDependencia' => $codigoPadreDependencia])
                    //->andWhere(['estadoGdTrdDependencia' => Yii::$app->params['statusTodoText']['Activo']])
                ->one();

                if(!empty($dependenciaModel)){
                    $dependencyNameParent = $dependenciaModel->nombreGdTrdDependencia;
                }                

                $cells[$configuration->cellDependenciaPadreCgTrd] = 'UNIDAD ADMINISTRATIVA: ' . $dependencyNameParent;                
                $cells[$configuration->cellTituloDependCgTrd] = 'OFICINA PRODUCTORA: ' .  $dependencyName;
                $cells[$configuration->cellDependenciaCgTrd] = $dependencyCode;

                if(!is_null($configuration->cellRegionalCgTrd)) {
                    $cells[$configuration->cellRegionalCgTrd] = 'REGIONAL: ' .  $regional;
                }

                # Nombre de la hoja
                $sheetName = $dependencyCode;                
            }
          
            # Información de la serie documental asignada a la dependencia
            if($trdItem->idGdTrdSerie != null && $idSerie !== $trdItem->idGdTrdSerie){

                $idSerie = $trdItem->idGdTrdSerie;                
                $serieCode = $trdItem->gdTrdSerie->codigoGdTrdSerie;
                $serieName = $trdItem->gdTrdSerie->nombreGdTrdSerie;

                # Información de la dependencia y serie, en una columna o separadas en columnas
                if(isset($concat)){
                    $cells[$concat. $rowData] = $dependencyCode.$separador.$serieCode;
                    $cells[$columnNombreCgTrd. $rowData] = $serieName;

                } else {
                    $cells[$codDepe. $rowData] = $dependencyCode;
                    $cells[$column2CodigoCgTrd . $rowData] = $serieCode;
                    $cells[$columnNombreCgTrd. $rowData] = $serieName;
                }
                                
                $rowData++;
            }

            // Información de la subserie documental asignada a la dependencia y serie
            if($trdItem->idGdTrdSubserie != null && $idSubserie !== $trdItem->idGdTrdSubserie){

                $idSubserie = $trdItem->idGdTrdSubserie;
                $subserieCode = $trdItem->gdTrdSubserie->codigoGdTrdSubserie;
                $subserieName = $trdItem->gdTrdSubserie->nombreGdTrdSubserie;

                # Retención
                $ag = $trdItem->gdTrdSubserie->tiempoGestionGdTrdSubserie;
                $ac = $trdItem->gdTrdSubserie->tiempoCentralGdTrdSubserie;  
                
                # Disposición
                $ct = $trdItem->gdTrdSubserie->ctDisposicionFinalGdTrdSubserie;
                $e = $trdItem->gdTrdSubserie->eDisposicionFinalGdTrdSubserie;
                $s = $trdItem->gdTrdSubserie->sDisposicionFinalGdTrdSubserie;
                $m = $trdItem->gdTrdSubserie->mDisposicionFinalGdTrdSubserie;
                
                # Soporte
                $pSupport = $trdItem->gdTrdSubserie->pSoporteGdTrdSubserie;
                $eSupport = $trdItem->gdTrdSubserie->eSoporteGdTrdSubserie;
                $oSupport = $trdItem->gdTrdSubserie->oSoporteGdTrdSubserie;   
                
                # Norma
                $norm = $trdItem->gdTrdSubserie->normaGdTrdSubserie;   

                # Procedimiento
                $process = $trdItem->gdTrdSubserie->procedimientoGdTrdSubserie;                

                # Información de la dependencia, serie y subserie, en una columna o separadas en columnas
                if(isset($concat)){
                    $cells[$concat. $rowData] = $dependencyCode.$separador.$serieCode.$separador.$subserieCode;
                    $cells[$columnNombreCgTrd. $rowData] = $subserieName;

                } else {
                    $cells[$codDepe. $rowData] = $dependencyCode;
                    $cells[$column2CodigoCgTrd. $rowData] = $serieCode;
                    $cells[$column3CodigoCgTrd. $rowData] = $subserieCode;
                    $cells[$columnNombreCgTrd. $rowData] = $subserieName;
                }

                # Retención
                $cells[$columnAgCgTrd. $rowData] = $ag;
                $cells[$columnAcCgTrd. $rowData] = $ac;                

                # Disposición
                if($ct == 10){
                    $cells[$columnCtCgTrd . $rowData] = 'X';
                }
                if($e == 10){
                    $cells[$columnECgTrd . $rowData] = 'X';
                }
                if($s == 10){
                    $cells[$columnSCgTrd. $rowData] = 'X';
                }                
                if($m == 10){
                    $cells[$columnMgTrd. $rowData] = 'X';
                }

                # Soporte             
                if(!is_null($columnPSoporteCgTrd) && $pSupport == 10){
                    $cells[$columnPSoporteCgTrd. $rowData] = 'X';
                }
                if(!is_null($columnESoporteCgTrd) &&  $eSupport == 10){
                    $cells[$columnESoporteCgTrd . $rowData] = 'X';
                }
                if(!is_null($columnOsoporteCgTrd) && $oSupport == 10){
                    $cells[$columnOsoporteCgTrd. $rowData] = 'X';
                }               

                # Procedimiento
                $cells[$columnProcessCgTrd. $rowData] = $process;
                $wrappedCells[] = $columnProcessCgTrd. $rowData;

                # Norma
                if(!is_null($columnNormaCgTrd) && !is_null($norm)){
                    $cells[$columnNormaCgTrd. $rowData] = $norm;
                    $wrappedCells[] = $columnNormaCgTrd. $rowData;
                }

                $rowData++;
            }
            
            // Información del tipo documental asignada a la dependencia, serie y subserie
            if($trdItem->idGdTrdTipoDocumental != null){

                # Tipo documental
                $documentaryType = $trdItem->gdTrdTipoDocumental->nombreTipoDocumental;
                $days = $trdItem->gdTrdTipoDocumental->diasTramiteTipoDocumental;

                $cells[$columnNombreCgTrd. $rowData] = $documentaryType;
                if(!is_null($columnTipoDocCgTrd))
                    $cells[$columnTipoDocCgTrd . $rowData] = $days;    
                
                $rowData++;
            }
        }

        # Para agregar la parte de las convenciones
        $filaConvenciones = $rowData + 1;
        $cells[$configuration->columnCodigoCgTrd.$rowData] = '';
        $cells[$configuration->columnCodigoCgTrd.$filaConvenciones] = 'Convenciones: AG: Archivo de Gestión. AC: Archivo Centra';

        //Centrado
        for($i=6; $i<=$row; $i++){
            $centerCells[] = 'A' . $i;
            $centerCells[] = 'B' . $i;
            $centerCells[] = 'C' . $i;
            $centerCells[] = 'E' . $i;
            $centerCells[] = 'F' . $i;
            $centerCells[] = 'G' . $i;
            $centerCells[] = 'H' . $i;
            $centerCells[] = 'I' . $i;
            $centerCells[] = 'J' . $i;
            $centerCells[] = 'K' . $i;
            $centerCells[] = 'L' . $i;
            $centerCells[] = 'M' . $i;
            $centerCells[] = 'N' . $i;
        }

        // Bordes
        # Se le suma uno más para que llegue al borde de la ultima columna cuando hay tipo documental
        if(!is_null($columnTipoDocCgTrd))  $numberColumnEnd++;  

        for($i = 1; $i <= $rowData; $i++){
            for($j = 0; $j < $numberColumnEnd; $j++){
                $borders[] = self::getColumn($j) . $i;
            }
        }
   
        return [
            'cells' => $cells,
            'mergeCells' => $mergeCells,
            'borders' => $borders,
            'wrappedCells' => $wrappedCells,
            'boldCells' => $boldCells,
            'sheetName' => $sheetName,
            'centerCells' => $centerCells,
            'status' => 'OK'
        ];        
    }


    /**
     * Función que permite convertir las letras en numeros y viceversa
     * @param $letter [String][Letras]
     * @param $number [Int][Números]
     **/
    public static function numberByLetter($letter , $number) { 

        if(!is_null($letter)) {
            $letterArray = array(
                'A' => 0,
                'B' => 1,
                'C' => 2,
                'D' => 3,
                'E' => 4,
                'F' => 5,
                'G' => 6,
                'H' => 7,
                'I' => 8,
                'J' => 9,
                'K' => 10,
                'L' => 11,
                'M' => 12,
                'N' => 13,
                'O' => 14,
                'P' => 15,
            );

            return $letterArray[$letter];

        } elseif (!is_null($number)) {
            $numberArray = array(
                0 => 'A',
                1 => 'B',
                2 => 'C',
                3 => 'D',
                4 => 'E',
                5 => 'F',
                6 => 'G',
                7 => 'H',
                8 => 'I',
                9 => 'J',
                10 => 'K',
                11 => 'L',
                12 => 'M',
                13 => 'N',
                14 => 'O',
                15 => 'P',
            );

            return $numberArray[$number];
        }
    }


    protected static function getColumn($index){
        $letras = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O',
            'P', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z'];

        return $letras[$index];        
    }


    /**
     * Construye el formato del cuadro de clasificacion documental
     * @param $idDependencia [int] [id de la dependencia]
     **/
    public static function getCuadroDocumental($idDependencia) {

        $arrayExcel = [];
        $titulo = [];
        $arrayTitulos = [];
        $cabecera = [];
        $cuerpo = []; // Filas de datos para el excel
        $mergeCells = [];
        $arrayCabeceras = [];
        $arrayCuerpo = [];


        # Se obtiene el arreglo de los ids de la dependencia
        // $idsDependencia = implode(',', $idDependencia); // Se deja el array para utilizar la funcion de Yii2 ('IN', 'trd.idGdTrdDependencia', $idDependencia)
           

        # Consulta de relacion de las tablas
        $tableNameGdTrd = GdTrd::tableName();
        $tableNameGdTrdDependencias = GdTrdDependencias::tableName();
        $tableNameGdTrdSeries = GdTrdSeries::tableName();
        $tableNameGdTrdSubseries = GdTrdSubseries::tableName();

        $modelGdTrdRelacionado = (new \yii\db\Query())
            ->select([
                'DP.idGdTrdDependencia','DP.nombreGdTrdDependencia','DP.codigoGdTrdDependencia', 'DP.codigoGdTrdDepePadre',
                'SS.idGdTrdSerie','SS.nombreGdTrdSerie','SS.codigoGdTrdSerie',
                'SB.idGdTrdSubserie', 'SB.nombreGdTrdSubserie', 'SB.codigoGdTrdSubserie'            
            ])
            ->from($tableNameGdTrd . ' AS TRD');
            // ->innerJoin($tableNameGdTrdDependencias .' AS dp', 'dp.idGdTrdDependencia = trd.idGdTrdDependencia')
            $modelGdTrdRelacionado = HelperQueryDb::getQuery('innerJoinAlias', $modelGdTrdRelacionado, $tableNameGdTrdDependencias .' AS DP', ['DP' => 'idGdTrdDependencia', 'TRD' => 'idGdTrdDependencia']);
            // ->innerJoin($tableNameGdTrdSeries .' AS ss', 'ss.idGdTrdSerie = trd.idGdTrdSerie')
            $modelGdTrdRelacionado = HelperQueryDb::getQuery('innerJoinAlias', $modelGdTrdRelacionado, $tableNameGdTrdSeries .' AS SS', ['SS' => 'idGdTrdSerie', 'TRD' => 'idGdTrdSerie']);
            // ->innerJoin($tableNameGdTrdSubseries .' AS sb', 'sb.idGdTrdSubserie = trd.idGdTrdSubserie')
            $modelGdTrdRelacionado = HelperQueryDb::getQuery('innerJoinAlias', $modelGdTrdRelacionado, $tableNameGdTrdSubseries .' AS SB', ['SB' => 'idGdTrdSubserie', 'TRD' => 'idGdTrdSubserie']);
            $modelGdTrdRelacionado = $modelGdTrdRelacionado->where(['IN', 'TRD.idGdTrdDependencia', $idDependencia])
            ->orderBy(['DP.codigoGdTrdDependencia' => SORT_ASC, 'SS.codigoGdTrdSerie' => SORT_ASC])
        ->all();

        if(empty($modelGdTrdRelacionado)){
            return [
                'arrayExcel' => [],
                'mergeCells' => [],
                'arrayTitulos' => [],
                'arrayCabeceras' => [],
                'status' => 'trdDoesntExist',
            ];
        }

        //Inicio de filas
        $fila = 2;

        $widthCells['A'] = 7;

        # LOGO
        #A
        $mergeCells[] = 'A'.$fila.':A5';
        
        # ENCABEZADO 
        #B2:H2        
        $mergeCells[] = 'B'.$fila.':H'.$fila;
        $titulo['B' . $fila] = 'CUADRO DE CLASIFICACIÓN DOCUMENTAL';
        $arrayTitulos[] = 'B' . $fila;
        #I2
        $cuerpo['I' . $fila] =  'Código: '. Yii::$app->params['codigo'];
        $fila++;

        #B3:H3
        $mergeCells[] = 'B'.$fila.':H'.$fila;
        $cuerpo['B' . $fila] = 'PROCESO GESTIÓN DOCUMENTAL';
        #I3
        $cuerpo['I' . $fila] = 'Versión: '. Yii::$app->params['version'];
        $fila++;

        #B4:H4
        $mergeCells[] = 'B'.$fila.':H'.$fila;
        $cuerpo['B' . $fila] = 'ELABORACIÓN DE TABLA DE RETENCIÓN DOCUMENTAL';
        #I4
        $cuerpo['I' . $fila] = 'Fecha: '.date("d/m/Y");
        $fila++;
        #I5
        $cuerpo['I' . $fila] = 'Página 1';
        $fila++;

        # TITULOS 
        #A6:A7 hasta I6:I7 
        $mergeCells[] = 'A'.$fila.':A7';
        $titulo['A' . $fila] = 'FONDO';
        $arrayTitulos[] = 'A' . $fila.':A7';

        $mergeCells[] = 'B'.$fila.':B7';
        $titulo['B' . $fila] = 'CÓDIGO UNIDAD ADMINISTRATIVA';
        $arrayTitulos[] = 'B' . $fila.':B7';
        
        $mergeCells[] = 'C'.$fila.':C7';
        $titulo['C' . $fila] = 'UNIDAD ADMINISTRATIVA';
        $arrayTitulos[] = 'C' . $fila.':C7';

        $mergeCells[] = 'D'.$fila.':D7';
        $titulo['D' . $fila] = 'CÓDIGO OFICINA PRODUCTORA';
        $arrayTitulos[] = 'D' . $fila.':D7';

        $mergeCells[] = 'E'.$fila.':E7';
        $titulo['E' . $fila] = 'OFICINA PRODUCTORA';
        $arrayTitulos[] = 'E' . $fila.':E7';

        $mergeCells[] = 'F'.$fila.':F7';
        $titulo['F' . $fila] = 'CÓDIGO SERIE';
        $arrayTitulos[] = 'F' . $fila.':F7';

        $mergeCells[] = 'G'.$fila.':G7';
        $titulo['G' . $fila] = 'SERIE';
        $arrayTitulos[] = 'G' . $fila.':G7';

        $mergeCells[] = 'H'.$fila.':H7';
        $titulo['H' . $fila] = 'CÓDIGO SUBSERIE';
        $arrayTitulos[] = 'H' . $fila.':H7';

        $mergeCells[] = 'I'.$fila.':I7';
        $titulo['I' . $fila] = 'SUBSERIE';
        $arrayTitulos[] = 'I' . $fila.':I7';
        $fila = $fila + 2;

        # CUERPO DEL EXCEL
        # Pintado del excel con la informacion de la dependencia
        $infoExcel = [];     
        foreach ($modelGdTrdRelacionado as $infoTrdRelacion) {
            
            if(isset($infoTrdRelacion['idGdTrdDependencia'])){

                $codigoPadre = GdTrdDependencias::findOne(['idGdTrdDependencia' => $infoTrdRelacion['codigoGdTrdDepePadre']]);

                if(!empty($codigoPadre)){
                    $codigoPadre = $codigoPadre->codigoGdTrdDependencia;
                }else{
                    $codigoPadre = '';
                }

                // Se construye el array $infoExcel que permite agrupa la informacion sin repetirla
                if(!isset($infoExcel[$infoTrdRelacion['idGdTrdDependencia']][$infoTrdRelacion['idGdTrdSerie']][$infoTrdRelacion['idGdTrdSubserie']])){
                    $infoExcel[$infoTrdRelacion['idGdTrdDependencia']][$infoTrdRelacion['idGdTrdSerie']][$infoTrdRelacion['idGdTrdSubserie']] = array(
                        'codigoPadreDepend' => $codigoPadre,
                        'nombrePadreDepend' => $infoTrdRelacion['nombreGdTrdDependencia'],
                        'codigoDepend' => $infoTrdRelacion['codigoGdTrdDependencia'],
                        'nombreDepend' => $infoTrdRelacion['nombreGdTrdDependencia'],
                        'codigoSerie' => $infoTrdRelacion['codigoGdTrdSerie'],
                        'nombreSerie' => $infoTrdRelacion['nombreGdTrdSerie'],    
                        'codigoSubserie' => $infoTrdRelacion['codigoGdTrdSubserie'],
                        'nombreSubserie' => $infoTrdRelacion['nombreGdTrdSubserie'],
                    );
                   
                }
            }           
        }
        
        // Se itera en la informacion de las relaciones de la dependencia
        foreach($infoExcel as $idDepend){
            foreach($idDepend as $idSerie){
                foreach($idSerie as $i => $infoTrd){

                    # Informacion del nombre de la columna Fondo
                    $cuerpo['A' . $fila] = Yii::$app->params['fondo'];
                    $arrayCuerpo[] = 'A' . $fila;

                    # Informacion de las columnas CÓDIGO SUBSECCIÓN 1, SUBSECCIÓN 1, CÓDIGO SUBSECCIÓN 2, SUBSECCIÓN 2
                    $cuerpo['B' . $fila] = $infoTrd['codigoPadreDepend'];
                    $arrayCuerpo[] = 'B' . $fila;

                    $cuerpo['C' . $fila] = $infoTrd['nombrePadreDepend'];
                    $arrayCuerpo[] = 'C' . $fila;

                    $cuerpo['D' . $fila] = $infoTrd['codigoDepend'];
                    $arrayCuerpo[] = 'D' . $fila;

                    $cuerpo['E' . $fila] = $infoTrd['nombreDepend'];
                    $arrayCuerpo[] = 'E' . $fila;

                    # Informacion de las columnas CÓDIGO SERIE,	SERIE
                    $cuerpo['F' . $fila] = $infoTrd['codigoSerie'];
                    $arrayCuerpo[] = 'F' . $fila;

                    $cuerpo['G' . $fila] = $infoTrd['nombreSerie'];
                    $arrayCuerpo[] = 'G' . $fila;

                    # Informacion de las columnas CÓDIGO SUBSERIE, SUBSERIE
                    $cuerpo['H' . $fila] = str_replace('.','. ',$infoTrd['codigoSubserie']);
                    $arrayCuerpo[] = 'H' . $fila;

                    $cuerpo['I' . $fila] = $infoTrd['nombreSubserie'];
                    $arrayCuerpo[] = 'I' . $fila;

                    $fila++;
                }
            }
        }
        
        $arrayExcel = array_merge($arrayExcel, $titulo, $cabecera, $cuerpo);

        return [
            'arrayExcel' => $arrayExcel,
            'mergeCells' => $mergeCells,
            'arrayTitulos' => $arrayTitulos,
            'arrayCabeceras' => $arrayCabeceras,
            'arrayCuerpo' => $arrayCuerpo,
            'status' => 'OK',
        ];
        

    }

    /**
     * Genera y exporta el excel del indice electronico 
     * @param $idExpediente [int] [id del expediente]
     **/
    public static function getIndiceElectronico($idExpediente){     

        $cells = [];
        $headers = [];

        $headers[] = 'A1';
        $cells['A1'] ='Índice contenido';

        $headers[] = 'B1';
        $cells['B1'] = 'Nombre documento';

        $headers[] = 'C1';
        $cells['C1'] = 'Tipo documental';

        $headers[] = 'D1';
        $cells['D1'] = 'Fecha documento';

        $headers[] = 'E1';
        $cells['E1'] = 'Fecha de inclusión';

        $headers[] = 'F1';
        $cells['F1'] = 'Valor de huella';

        $headers[] = 'G1';
        $cells['G1'] = 'Función resumen';

        $headers[] = 'H1';
        $cells['H1'] = 'Orden documento';

        $headers[] = 'I1';
        $cells['I1'] = 'Página inicio';

        $headers[] = 'J1';
        $cells['J1'] = 'Página final';

        $headers[] = 'K1';
        $cells['K1'] = 'Formato';

        $headers[] = 'L1';
        $cells['L1'] = 'Tamaño';

        $headers[] = 'M1';
        $cells['M1'] = 'Origen';

        $row = 2;
        
        $modelIndices = GdIndices::find();
            // ->innerJoin('gdExpedientesInclusion', 'gdIndices.idGdExpedienteInclusion=gdExpedientesInclusion.idGdExpedienteInclusion')
            $modelIndices = HelperQueryDb::getQuery('leftJoin', $modelIndices, 'gdExpedientesInclusion', ['gdIndices' => 'idGdExpedienteInclusion', 'gdExpedientesInclusion' => 'idGdExpedienteInclusion']);
            $modelIndices = HelperQueryDb::getQuery('leftJoin', $modelIndices, 'gdExpedienteDocumentos', ['gdIndices' => 'idGdExpedienteDocumento', 'gdExpedienteDocumentos' => 'idGdExpedienteDocumento']);
            $modelIndices = HelperQueryDb::getQuery('leftJoin', $modelIndices, 'gdReferenciasCruzadas',  ['gdIndices' => 'idGdReferenciaCruzada', 'gdReferenciasCruzadas' => 'idGdReferenciaCruzada']);
            $modelIndices = $modelIndices->where(['gdExpedientesInclusion.idGdExpediente' => $idExpediente]);
            //$modelIndices = $modelIndices->andWhere(['estadoGdExpedienteInclusion' => Yii::$app->params['statusTodoText']['Activo']])
            $modelIndices = $modelIndices->orWhere(['gdExpedienteDocumentos.idGdExpediente' => $idExpediente]);
            $modelIndices = $modelIndices->orWhere(['gdReferenciasCruzadas.idGdExpediente' => $idExpediente]);
            $modelIndices =  $modelIndices->all();              

        foreach($modelIndices as $indice){           
            
            $cell = 'A' . $row;
            $cells[$cell] = $indice->indiceContenidoGdIndice;

            $cell = 'B' . $row;
            $cells[$cell] = $indice->nombreDocumentoGdIndice;   

            $cell = 'C' . $row;
            $cells[$cell] = $indice->gdTrdTipoDocumental->nombreTipoDocumental;

            $cell = 'D' . $row;
            $cells[$cell] = $indice->creacionDocumentoGdIndice;            

            # Fecha inclusión
            if($indice->idGdExpedienteInclusion != null){

                $cell = 'E' . $row;
                $cells[$cell] = $indice->gdExpedienteInclusion->creacionGdExpedienteInclusion;

            }  else if($indice->idGdExpedienteDocumento) {
                
                $cell = 'E' . $row;
                $cells[$cell] = $indice->gdExpedienteDocumento->creacionGdExpedienteDocumento;
            }          

            $cell = 'F' . $row;
            $cells[$cell] = $indice->valorHuellaGdIndice;

            $cell = 'G' . $row;
            $cells[$cell] = $indice->funcionResumenGdIndice;

            $cell = 'H' . $row;
            $cells[$cell] = $indice->ordenDocumentoGdIndice;

            $cell = 'I' . $row;
            $cells[$cell] = $indice->paginaInicioGdIndice;

            $cell = 'J' . $row;
            $cells[$cell] = $indice->paginaFinalGdIndice;

            $cell = 'K' . $row;
            $cells[$cell] = $indice->formatoDocumentoGdIndice;

            $cell = 'L' . $row;
            $cells[$cell] = $indice->tamanoGdIndice;

            if(isset($indice->origenGdIndice)){
                $cell = 'M' . $row;
                $cells[$cell] = Yii::$app->params['origenNumber'][$indice->origenGdIndice];
            }
            
            $row ++;
        }

        return [$cells, $headers];        
    
    }  


    /**
     * Construye los folios
     * @param $idDependencia [int] [id de la dependencia]
     **/
    public function getFolio($idExpediente, $folio, $idsRadicados) {  
        
        $modelExpediente = GdExpedientes::find()
            ->where(['idGdExpediente' => $idExpediente])
            ->one()
        ; 
        
        
        if($folio=='Carpeta'){          

            $nombreEntidad = Yii::$app->params['entidad'];

            $nombreDependencia = $modelExpediente->gdTrdDependencia->nombreGdTrdDependencia;
            $numeroDependencia = $modelExpediente->gdTrdDependencia->codigoGdTrdDependencia;

            $nombreSerie = $modelExpediente->gdTrdSerie->nombreGdTrdSerie;
            $numeroSerie = $modelExpediente->gdTrdSerie->codigoGdTrdSerie;

            $nombreSubserie = $modelExpediente->gdTrdSubserie->nombreGdTrdSubserie;
            $numeroSubserie = $modelExpediente->gdTrdSubserie->codigoGdTrdSubserie;

            $descripcion = $modelExpediente->descripcionGdExpediente;


            //Obtener la fecha menor de inclusión
            $modelExpedienteInclusion = GdExpedientesInclusion::find()
                ->select(['creacionGdExpedienteInclusion'])
                ->where(['idGdExpediente' => $modelExpediente->idGdExpediente])
                ->orderBy(['creacionGdExpedienteInclusion' => SORT_ASC])
                ->one()
            ;

            $fechaInicial = $modelExpedienteInclusion->creacionGdExpedienteInclusion;
            $fechaInicialDD = date("d", strtotime($fechaInicial));
            $fechaInicialMMM = date("m", strtotime($fechaInicial));
            $fechaInicialAAAA = date("Y", strtotime($fechaInicial));

            //Obtener la fecha mayor de inclusión
            $modelExpedienteInclusion = GdExpedientesInclusion::find()
                ->select(['creacionGdExpedienteInclusion'])
                ->where(['idGdExpediente' => $modelExpediente->idGdExpediente])
                ->orderBy(['creacionGdExpedienteInclusion' => SORT_DESC])
                ->one()
            ;            

            $fechaFinal = $modelExpedienteInclusion->creacionGdExpedienteInclusion;
            $fechaFinalDD = date("d", strtotime($fechaFinal)); 
            $fechaFinalMMM = date("m", strtotime($fechaFinal));
            $fechaFinalAAA = date("Y", strtotime($fechaFinal));

            //Obetner cantidad de expedientes incluidos
            $numeroFolios = GdExpedientesInclusion::find()
                ->where(['idGdExpediente' => $idExpediente])
                ->count()
            ;
            
            $html = "<html>\n";
            $html .= "<head>\n";                     
            $html .= "</head>\n";
            
            $html .= '<table border="1">'."\n";
            $html .= '<tr><td colspan="8">'.$nombreEntidad.'</td></tr>'."\n";
            $html .= '<tr><td><strong>Código Dependencia</strong></td> <td colspan="7"><strong >Nombre Dependencia</strong></td></tr>'."\n";
            $html .= '<tr><td>'.$numeroDependencia.'</td> <td colspan="7">'.$nombreDependencia.'</td></tr>'."\n";
            $html .= '<tr><td><strong>Código Serie/subserie</strong></td> <td colspan="7"><strong>Nombre Serie/Subserie</strong></td></tr>'."\n";
            $html .= '<tr><td>'.$numeroSerie.'/'.$numeroSubserie.'</td><td colspan="7">'.$nombreSerie.'/'.$nombreSubserie.'</td></tr>'."\n";
            $html .= '<tr><td colspan="8">ASUNTO CARPETA</td></tr>'."\n";        
            $html .= '<tr><td colspan="8">'.$descripcion.'</td></tr>'."\n";
            $html .= '<tr><td colspan="8">Fechas extremas</td></tr>'."\n";
            $html .= "<tr><td>F. Inicial</td> <td>$fechaInicialDD</td> <td>$fechaInicialMMM</td> <td>$fechaInicialAAAA</td> <td>F. final</td>> <td>$fechaFinalDD</td> <td>$fechaFinalMMM</td> <td>$fechaFinalAAA</td></tr>\n";
            $html .= '<tr><td colspan="2">No. folio</td> <td colspan="2">'.$numeroFolios.'</td> <td colspan="2">No. Carpeta</td> <td colspan="2"><></td> </tr>'."\n";

            $html .= '</table>'."\n";
            $html .= "</html>";         

        }else if($folio=='Caja'){

            $nombreExpediente = $modelExpediente->nombreGdExpediente;

            $nombreEntidad = Yii::$app->params['entidad'];

            $nombreDependencia = $modelExpediente->gdTrdDependencia->nombreGdTrdDependencia;
            $numeroDependencia = $modelExpediente->gdTrdDependencia->codigoGdTrdDependencia;

            $numeroCaja = '';

            $modelExpedienteInclusion = GdExpedientesInclusion::find()              
                ->where(['idGdExpediente' => $modelExpediente->idGdExpediente])
                ->orderBy(['creacionGdExpedienteInclusion' => SORT_ASC])
                ->all()
            ;

            $numeroCaja = '';
            $unidades = [];
            $contadorRegistros = 0;
            $numeroInicial = '';
            $numeroFinal = '';
            $fechaInicial = '';
            $fechaFinal = '';
            foreach($modelExpedienteInclusion as $inclusion){               

                //Verificar que la inclusón tenga archivo y sea de los radxicados sekleccionados
                if($inclusion->gaArchivo != null && in_array($inclusion->idRadiRadicado, $idsRadicados)){
                    $contadorRegistros ++;
                    $unidades[] = $inclusion->gaArchivo->unidadConservacionGaArchivo;
                    $numeroCaja = $inclusion->gaArchivo->cajaGaArchivo; 

                    $fechaFinal = $inclusion->creacionGdExpedienteInclusion;
                    $numeroFinal = $inclusion->gaArchivo->unidadConservacionGaArchivo;

                    if($contadorRegistros==1){
                    
                        $fechaInicial = $inclusion->creacionGdExpedienteInclusion;
                        $numeroInicial = $inclusion->gaArchivo->unidadConservacionGaArchivo;
                    }
                }                             
            }

            if($fechaInicial!=''){
                $fechaInicialDD = date("d", strtotime($fechaInicial));
                $fechaInicialMMM = date("m", strtotime($fechaInicial));
                $fechaInicialAAAA = date("Y", strtotime($fechaInicial));
            }else {
                $fechaInicialDD = '';
                $fechaInicialMMM = '';
                $fechaInicialAAAA = '';
            }

            if($fechaFinal!=''){
                $fechaFinalDD = date("d", strtotime($fechaFinal)); 
                $fechaFinalMMM = date("m", strtotime($fechaFinal));
                $fechaFinalAAA = date("Y", strtotime($fechaFinal));
            }else {
                $fechaFinalDD = ''; 
                $fechaFinalMMM = '';
                $fechaFinalAAA = '';
            }         
            
            $html = '<table border="1">'."\n";
            $html .= '<tr><td colspan="8"><strong>'.$nombreEntidad.'</span></td></tr>'."\n";
            $html .= '<tr><td><strong>Código Dependencia</strong></td> <td colspan="7"><strong >Nombre Dependencia</strong></td></tr>'."\n";
            $html .= '<tr><td>'.$numeroDependencia.'</td> <td colspan="7">'.$nombreDependencia.'</td></tr>'."\n";            
            $html .= '<tr><td colspan="8">-</td></tr>';
            $html .= '<tr><td><strong>No. CAJA</strong></td> <td colspan="7">'.$numeroCaja.'</td></tr>';            
            $html .= '<tr><td colspan="8">-</td></tr>';
            $html .= '<tr><td>Código Serie/Subserie</td> <td colspan="7">NOMBRE SERIE/SUBSERIE</td></tr>';
            
            $html .= '<tr><td><></td> <td colspan="7"><></td></tr>';
            
            foreach($unidades as $unidad){
                $html .= '<tr><td>'.$unidad.'</td> <td colspan="7">'.$nombreExpediente.'</td></tr>';
            }
            
            $html .= '<tr><td colspan="8">-</td></tr>';
            $html .= '<tr><td colspan="4">No. TOTAL UNIDADES</td> <td colspan="4">'.$contadorRegistros.'</td></tr>';

            $html .= '<tr><td>1° No Unidad</td> <td colspan="3">'.$numeroInicial.'</td> <td>Últ N° Unidad</td> <td colspan="3">'.$numeroFinal.'</td></tr>';
            $html .= "<tr><td>F. Inicial</td> <td>$fechaInicialDD</td> <td>$fechaInicialMMM</td> <td>$fechaInicialAAAA</td> <td>F. final</td>> <td>$fechaFinalDD</td> <td>$fechaFinalMMM</td> <td>$fechaFinalAAA</td></tr>\n";
            
            $html .= '</table>'."\n";            

        }

        return $html;
    
    }    

    /**
     * Planilla del formato regional
    */
    public function getRegionalFormat(){

        $sheets = [];        
        
        # Nombre de la primera hoja
        $sheets[0]['sheetName'] = 'Regionales';

        # Si es true, oculta la hoja
        $sheets[1]['noVisible'] = true;

        # Nombre de la hoja que contiene los listados (ésta esta oculta) 
        $sheets[1]['sheetName'] = 'listRegion';                

        #Inicio de fila
        $fila = 1;

        # Encabezado
        $sheets[0]['cells']['A'. $fila]['value'] = 'Nombre completo'; 
        $sheets[0]['cells']['B'. $fila]['value'] = 'Sigla';       
        $sheets[0]['cells']['C'. $fila]['value'] = 'Municipio';
        
        # Límite de caracteres en el nombre de la regional  
        $sheets[0]['cells']['A'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['A'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['A'.$fila]['validation']['formula2'] = 80;

        # Límite de caracteres en la sigla de la regional  
        $sheets[0]['cells']['B'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['B'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['B'.$fila]['validation']['formula2'] = 5;

        $fila ++;        

        # Listado del municipio
        $municipality = NivelGeografico3::findAll(['estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo']]);

        $listColB =  0;
        foreach($municipality as $i => $dataMunicipal){
            $row = $i + 2;
            $sheets[1]['cells']['C'.$row]['value'] = $dataMunicipal->nomNivelGeografico3;
            $listColB = $row;
        }

        # Se define el tipo y cantidad de datos del listado del municipio
        for($i = 2; $i <= $listColB; $i++){
            $row = $i;
            $sheets[0]['cells']['C'.$i]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['C'.$i]['validation']['formula1'] = 'listRegion!$C$2:$C$'.$listColB;
        } 

        # Tamaño de la celda
        $sheets[0]['columnDimensions']['A'] = 20;
        $sheets[0]['columnDimensions']['B'] = 20;
        $sheets[0]['columnDimensions']['C'] = 20;      
         
        return $sheets;
    }


    public static function getTercerosFormat(){
        $sheets = [];        
        
        # Nombre de la primera hoja
        $sheets[0]['sheetName'] = 'Terceros';

        # Nombre de la hoja que contiene los listados (ésta esta oculta) 
        $sheets[1]['sheetName'] = 'listTerceros';        

        # Si es true, oculta la hoja
        $sheets[1]['noVisible'] = true;

        #Inicio de fila
        $fila = 1;

        # Encabezado
        $sheets[0]['cells']['A'. $fila]['value'] = 'Nombre completo';
        $sheets[0]['cells']['B'. $fila]['value'] = 'Tipo de persona';
        $sheets[0]['cells']['C'. $fila]['value'] = 'Documento de identidad';
        $sheets[0]['cells']['D'. $fila]['value'] = 'Correo electrónico';

        // $sheets[0]['cells']['E'. $fila]['value'] = 'País';
        // $sheets[0]['cells']['F'. $fila]['value'] = 'Departamento';
        $sheets[0]['cells']['E'. $fila]['value'] = 'Municipio';
        $sheets[0]['cells']['F'. $fila]['value'] = 'Dirección';
        $sheets[0]['cells']['G'. $fila]['value'] = 'Código SAP';
        $sheets[0]['cells']['H'. $fila]['value'] = 'Telefono';
        
        # --------------------- Validaciones de campos --------------------- # 

        # Nombre Completo
        $sheets[0]['cells']['A'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['A'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['A'.$fila]['validation']['formula2'] = 45;
        # Numero Identificacion
        $sheets[0]['cells']['C'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['C'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['C'.$fila]['validation']['formula2'] = 15;
        # Correo Electronico
        $sheets[0]['cells']['D'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['D'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['D'.$fila]['validation']['formula2'] = 100;
        # Direccion
        $sheets[0]['cells']['F'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['F'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['F'.$fila]['validation']['formula2'] = 150;
        # Código SAP
        $sheets[0]['cells']['G'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['G'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['G'.$fila]['validation']['formula2'] = 20;
        # Telefono
        $sheets[0]['cells']['H'.$fila]['validation']['type'] = 'TYPE_TEXTLENGTH';
        $sheets[0]['cells']['H'.$fila]['validation']['formula1'] = 0;
        $sheets[0]['cells']['H'.$fila]['validation']['formula2'] = 15;

         # --------------------- Listados --------------------- # 

        # Listado del Tipo de Identificacion
        $TiposPersonas = TiposPersonas::find()
        ->where(['estadoPersona' => Yii::$app->params['statusTodoText']['Activo']])
        ->andWhere(['<>', 'idTipoPersona', Yii::$app->params['tipoPersonaText']['funcionario'] ])
        ->all();

        $lonListaD =  0;
        foreach($TiposPersonas as $key => $tiposPersona){
            $row = $key +2;
            $sheets[1]['cells']['B'.$row]['value'] = $tiposPersona->tipoPersona;
            $lonListaD =  $row;
        }

        for($i=2; $i<=Yii::$app->params['limitCellFormatsList']; $i++){
            $row = $i;
            $sheets[0]['cells']['B'.$row]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['B'.$row]['validation']['formula1'] = 'listTerceros!$B$2:$B$'.$lonListaD;
        }
        
        $fila ++;
        
        # Listado del País
            // $country = NivelGeografico1::findAll(['estadoNivelGeografico1' => Yii::$app->params['statusTodoText']['Activo']]);

            // $listColE=  0;
            // foreach($country as $i => $dataCountry){
            //     $row = $i + 2;
            //     $sheets[1]['cells']['E'.$row]['value'] = $dataCountry->nomNivelGeografico1;
            //     $listColE = $row;
            // }

            # Se define el tipo y cantidad de datos del listado del país
            // for($i = 2; $i <=Yii::$app->params['limitCellFormatsList']; $i++){
            //     $row = $i;
            //     $sheets[0]['cells']['E'.$i]['validation']['type'] = 'TYPE_LIST';
            //     $sheets[0]['cells']['E'.$i]['validation']['formula1'] = 'listTerceros!$E$2:$E$'.$listColE;
            // } 
        # Listado del País

        # Listado del departamento
            // $department = NivelGeografico2::findAll(['estadoNivelGeografico2' => Yii::$app->params['statusTodoText']['Activo']]);

            // $listColF =  0;
            // foreach($department as $i => $dataDepartment){
            //     $row = $i + 2;
            //     $sheets[1]['cells']['F'.$row]['value'] = $dataDepartment->nomNivelGeografico2;
            //     $listColF = $row;
            // }

            # Se define el tipo y cantidad de datos del listado del departamento
            // for($i = 2; $i <=Yii::$app->params['limitCellFormatsList']; $i++){
            //     $row = $i;
            //     $sheets[0]['cells']['F'.$i]['validation']['type'] = 'TYPE_LIST';
            //     $sheets[0]['cells']['F'.$i]['validation']['formula1'] = 'listTerceros!$F$2:$F$'.$listColF;
            // } 
        # Listado del departamento

        # Listado del municipio
        $municipality = NivelGeografico3::findAll(['estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo']]);

        $listColG =  0;
        foreach($municipality as $i => $dataMunicipal){
            $row = $i + 2;
            $sheets[1]['cells']['E'.$row]['value'] = $dataMunicipal->nomNivelGeografico3;
            $listColG = $row;
        }

        # Se define el tipo y cantidad de datos del listado del municipio
        for($i = 2; $i <= Yii::$app->params['limitCellFormatsList']; $i++){
            $row = $i;
            $sheets[0]['cells']['E'.$i]['validation']['type'] = 'TYPE_LIST';
            $sheets[0]['cells']['E'.$i]['validation']['formula1'] = 'listTerceros!$E$2:$E$'.$listColG;
        } 

        # Tamaño de la celda
        $sheets[0]['columnDimensions']['A'] = 20;
        $sheets[0]['columnDimensions']['B'] = 20;
        $sheets[0]['columnDimensions']['C'] = 30;
        $sheets[0]['columnDimensions']['D'] = 20; 
        // $sheets[0]['columnDimensions']['E'] = 20; 
        // $sheets[0]['columnDimensions']['F'] = 20; 
        $sheets[0]['columnDimensions']['E'] = 20;   
        $sheets[0]['columnDimensions']['F'] = 40;       
        $sheets[0]['columnDimensions']['G'] = 20;
        $sheets[0]['columnDimensions']['H'] = 15;
         
        return $sheets;
    }

    /**
     * Formato de totales del flujo de caja detalle
     * @param $datapdf [array] [Array de la información del estructura pdf]
    */
    public static function getEstructureCorrespondenceTemplateExcel($datapdf, $nameUserAuth){

        // return $arrayCategory;
        $sheets = [];        
    
        # Nombre de la hoja
        $sheets[0]['sheetName'] = 'Planilla de correspondencia';

        # Reporte
        self::correspondenceTemplateExcel($datapdf, $sheets, 'A', 7, 0, $nameUserAuth); 
    
        return $sheets;    
    }

    /**
     * Función que estructura la información del flujo de caja detalle por las semanas.
     * @param $datapdf [array] [Array de la información estructurada]
     * @param $sheets [array] [Estructura del excel]
     * @param $initialColumn [string] [Columna de inicio]
     * @param $initialRow [number] [Numero de la fila de inicio]
     * @param $spaceBetweenTables [number] [Numero de espacios entre cada bloque]
     * */
    public static function correspondenceTemplateExcel($datapdf, &$sheets, $initialColumn = 'A', $initialRow, $spaceBetweenTables = 0, $nameUserAuth){
               
        $totalWeeks = $datapdf;
        $currentColumn = $initialColumn;
        $currentRow = $initialRow;

        # Construcción de CABECERA
        $sheets[0]['mergeCells'][] = 'A1:B5';

        $sheets[0]['mergeCells'][] = 'C1:H1';

        $sheets[0]['mergeCells'][] = 'C2:D2';
        $sheets[0]['mergeCells'][] = 'E2:H2';

        $sheets[0]['mergeCells'][] = 'C3:D3';
        $sheets[0]['mergeCells'][] = 'E3:H3';

        $sheets[0]['mergeCells'][] = 'C4:D4';
        $sheets[0]['mergeCells'][] = 'E4:H4';

        $sheets[0]['mergeCells'][] = 'C5:D5';
        $sheets[0]['mergeCells'][] = 'E5:H5';

        $sheets[0]['mergeCells'][] = 'C6:D6';
        $sheets[0]['mergeCells'][] = 'E6:H6';

        $sheets[0]['arrayBoldCells'] = [
            'C1', 'C2', 'C3', 'C4', 'E4'
        ];

        $sheets[0]['cells']['C1']['value'] = Yii::$app->params['cliente'];

        $sheets[0]['cells']['C2']['value'] = 'NIT';
        $sheets[0]['cells']['E2']['value'] = Yii::$app->params['nit'];

        $sheets[0]['cells']['C3']['value'] = 'NOMBRE FORMATO';
        $sheets[0]['cells']['E3']['value'] = Yii::t('app', 'formatoCorrespondencia');

        $sheets[0]['cells']['C4']['value'] = 'USUARIO RESPONSABLE';
        $sheets[0]['cells']['C5']['value'] = $nameUserAuth;

        $sheets[0]['cells']['E4']['value'] = 'FIRMA MENSAJERO';

        # Construcción de TITULOS
        $sheets[0]['arrayTitulo'][] = 'A'.$currentRow;
        $sheets[0]['cells']['A'.$currentRow]['value'] = 'N°';

        $sheets[0]['arrayTitulo'][] = 'B'.$currentRow;
        $sheets[0]['cells']['B'.$currentRow]['value'] = 'RADICADO';

        $sheets[0]['arrayTitulo'][] = 'C'.$currentRow;
        $sheets[0]['cells']['C'.$currentRow]['value'] = 'FECHA DE RADICACIÓN';

        $sheets[0]['arrayTitulo'][] = 'D'.$currentRow;
        $sheets[0]['cells']['D'.$currentRow]['value'] = 'DESTINATARIO / RESPONSABLE';

        $sheets[0]['arrayTitulo'][] = 'E'.$currentRow;
        $sheets[0]['cells']['E'.$currentRow]['value'] = 'DIRECCIÓN';

        $sheets[0]['arrayTitulo'][] = 'F'.$currentRow;
        $sheets[0]['cells']['F'.$currentRow]['value'] = 'CIUDAD DESTINO';

        $sheets[0]['arrayTitulo'][] = 'G'.$currentRow;
        $sheets[0]['cells']['G'.$currentRow]['value'] = 'FECHA DE RECIBIDO';

        $sheets[0]['arrayTitulo'][] = 'H'.$currentRow;
        $sheets[0]['cells']['H'.$currentRow]['value'] = 'NO. GUÍA';

        # Construcción de filas
        foreach ($datapdf as $key => $reg) {
            # Suma una celda
            $currentRow += 1;

            # Titulos
            $sheets[0]['cells']['A'.$currentRow]['value'] = strval($reg['NO']);
            $sheets[0]['cells']['B'.$currentRow]['value'] = strval($reg['RADICADO']);
            $sheets[0]['cells']['C'.$currentRow]['value'] = $reg['FECHA_RADICADO'];
            $sheets[0]['cells']['D'.$currentRow]['value'] = $reg['DESTINA_RESPO'];
            $sheets[0]['cells']['E'.$currentRow]['value'] = $reg['DIRECCION'];
            $sheets[0]['cells']['F'.$currentRow]['value'] = $reg['MUNICIPIO'];
            $sheets[0]['cells']['G'.$currentRow]['value'] = $reg['FECHA_DE_RECIBIDO'];
            $sheets[0]['cells']['H'.$currentRow]['value'] = $reg['NO_GUIA'];
        }

        # Tamaño de la celda
        $sheets[0]['columnDimensions']['A'] = 5;
        $sheets[0]['columnDimensions']['B'] = 20;
        $sheets[0]['columnDimensions']['C'] = 25;
        $sheets[0]['columnDimensions']['D'] = 30; 
        $sheets[0]['columnDimensions']['E'] = 40;
        $sheets[0]['columnDimensions']['F'] = 30; 
        $sheets[0]['columnDimensions']['G'] = 20;   
        $sheets[0]['columnDimensions']['H'] = 10;       
        

        /*for ($w=1; $w <= count($datapdf); $w++) {

            # Celda inicial
            $currentCell = $currentColumn . $currentRow;
            $week = "s$w";
            $tempValue = $currentColumn;

            # Título semana
            ++$currentColumn;
            ++$currentColumn;
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['mergeCells'][] = $currentCell.':'.$currentColumn.$currentRow; 
            $sheets[0]['cells'][$currentCell]['value'] = 'SEMANA ' . $w;
            $currentColumn = $tempValue;

            # Titulo ingresos
            $currentRow += 2;
            $currentCell = $currentColumn . $currentRow;
            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['mergeCells'][] = $currentCell.':'.$secondColumnLetter.$currentRow; 
            $sheets[0]['cells'][$currentCell]['value'] = 'INGRESOS';
            $currentColumn = $tempValue;
            
            // Dos columnas hacia la derecha
            ++$currentColumn;
            ++$currentColumn;

            # Titulo de la columna de porcentaje
            $currentCell = $currentColumn . $currentRow;
            $currentColumn = $tempValue;
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['cells'][$currentCell]['value'] = '%';            
            $currentRow++;

            # Se asigna la columna actual
            $tempValue = $currentColumn;

            # Columnas de nombre, valor y porcentaje            
            $firstColumnLetter = $currentColumn;
            $secondColumnLetter = ++$currentColumn;
            $thirdColumnLetter = ++$currentColumn;

            # Se itera la información de los valores y nombres de subcategoria con la operacion de suma
            foreach($arrayCategory['categories'] as $name => $category){
                if($category['operation'] === 10){
                    # Se valida que exista subcategorias
                    if( isset($category['subcategory']) ) {
                        foreach($category['subcategory'] as $nameSub => $subcategory){

                            # Calculo de porcentajes de subcategorias de ingresos                            
                            if($arrayCategory['total_ingress_col'][$week] !== 0)
                                $percent = ($subcategory[$week] / $arrayCategory['total_ingress_col'][$week]) * 100;
                            else $percent = 0;

                            # Nombre, valor y porcentaje
                            $sheets[0]['cells'][$firstColumnLetter. $currentRow]['value'] = $nameSub;
                            $sheets[0]['cells'][$secondColumnLetter. $currentRow]['value'] = number_format($subcategory[$week], 2,',','.');
                            $sheets[0]['cells'][$thirdColumnLetter. $currentRow]['value'] = round($percent, 0);
                            $currentRow++;
                        }
                    }
                }            
            }

            # Total de ingresos
            $currentColumn = $tempValue;
            $currentCell = $currentColumn . $currentRow;          
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['cells'][$currentColumn. $currentRow]['value'] = 'TOTAL INGRESO';

            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['cells'][$secondColumnLetter. $currentRow]['value'] = number_format($arrayCategory['total_ingress_col'][$week], 2,',','.');
            $currentColumn = $tempValue;

            # Titulo de egresos
            $currentRow += 3;
            $currentCell = $currentColumn . $currentRow;
            $sheets[0]['title'][] = $currentCell;

            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['mergeCells'][] = $currentCell.':'.$secondColumnLetter.$currentRow; 
            $sheets[0]['cells'][$currentCell]['value'] = 'EGRESOS'; 
            $currentColumn = $tempValue;

            // Dos columnas hacia la derecha
            ++$currentColumn;
            ++$currentColumn;

            # Titulo de la columna de porcentaje
            $currentCell = $currentColumn . $currentRow;
            $currentColumn = $tempValue;
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['cells'][$currentCell]['value'] = '%';
            $currentRow++;

            # Se itera la información de los valores y nombres de categoria con la operacion de resta
            $order = [];
            foreach($arrayCategory['categories'] as $name => $category){
                if($category['operation'] === 0 ) {
                    
                    # Se agrega la posición de name para agregar los nombres de las categorias
                    $category['name'] = $name;
        
                    # Se crea array para organizar las categorias
                    $order[$category['order']] = $category;
                }
            }

            ksort($order); // Organización de menor a mayor

            # Columnas de nombre, valor y porcentaje            
            $firstColumnLetter = $currentColumn;
            $secondColumnLetter = ++$currentColumn;
            $thirdColumnLetter = ++$currentColumn;
    
            # Se itera el orden de las categorias, para asignar el nombre y valor al excel
            foreach($order as $category){
        
                # Calculo de porcentajes de categorias de egresos
                if($arrayCategory['total_egress_col'][$week] !== 0)
                    $percent = ($category[$week] / $arrayCategory['total_egress_col'][$week]) * 100;
                else $percent = 0;
        
                # Nombre, valor y porcentaje
                $sheets[0]['cells'][$firstColumnLetter. $currentRow]['value'] = $category['name'];
                $sheets[0]['cells'][$secondColumnLetter. $currentRow]['value'] = number_format($category[$week], 2,',','.');
                $sheets[0]['cells'][$thirdColumnLetter. $currentRow]['value'] = round($percent, 0);
                $currentRow++;
            }

            $currentColumn = $tempValue;
            $currentCell = $currentColumn . $currentRow;

            # Total de egresos
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['cells'][$currentColumn. $currentRow]['value'] = 'TOTAL EGRESO';

            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['cells'][$secondColumnLetter. $currentRow]['value'] = number_format($arrayCategory['total_egress_col'][$week], 2,',','.');
            $currentColumn = $tempValue;

            # Totales (ingresos - egresos)
            $currentRow += 3;
            $currentColumn = $tempValue;
            $currentCell = $currentColumn . $currentRow;
            
            $sheets[0]['title'][] = $currentCell;
            $sheets[0]['cells'][$currentColumn. $currentRow]['value'] = 'TOTALES';  
            
            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['cells'][$secondColumnLetter. $currentRow]['value'] = number_format($arrayCategory['total_col'][$week], 2,',','.');
            $currentColumn = $tempValue;
            $currentRow++;

            # Tamaño de la celda
            $sheets[0]['columnDimensions'][$currentColumn] = 20;
            $secondColumnLetter = ++$currentColumn;
            $sheets[0]['columnDimensions'][$secondColumnLetter] = 15;

            # Se reinicia la fila
            $currentRow = $initialRow;

            # Se agregan dos columnas a la derecha despues de pintarse la semana
            for($i=1; $i <= ($spaceBetweenTables + 1); $i++){
                ++$currentColumn;
            }
        }*/
    }
    
}
