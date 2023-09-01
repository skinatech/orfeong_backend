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

namespace api\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;

class FacturacionElectronica extends Model
{
    private $xml;
    private $xmlArray = [];
    private $reader;

    /**
     * Extraer la información del proveedor
     */
    public function extractSupplierInfo($attachment){

        $urlUnzip = $this->unzipAttachment($attachment);
        $this->xml = file_get_contents($urlUnzip);
        $supplier = $this->readInfoCdata($urlUnzip);
        return $supplier;
    }

    /**
     * Obtener la informacion del proveedor del XML
     */
    private function readInfoCdata($urlUnzip)
    {
        if(!stristr(strtolower($urlUnzip), '.xml')){
            return '';
        }

        $reader = new \XMLReader();
        $Cdata = $this->getCdata($urlUnzip);
        if (!empty($Cdata)){ $reader->xml($Cdata[0]); }
        else { return ''; }
        
        $xmlArray = [];
        $strArray = '';
        $stack = [];

        while($reader->read()){
            
            if($reader->nodeType == \XMLReader::ELEMENT){ 
                $strArray .= $reader->localName .'_';
                $stack[] = $reader->localName;
            }

            if($reader->nodeType == \XMLReader::TEXT){
               $xmlArray[$strArray] = $reader->value;
            }

            if($reader->nodeType == \XMLReader::END_ELEMENT){
              $lastString = array_pop($stack);
              $strArray = str_replace($lastString.'_', '', $strArray);
            }
            if($reader->nodeType == \XMLReader::CDATA){
                $xmlArray[$strArray] = $reader->value;
            }
        }
        //var_dump($xmlArray);
        $keys = array_keys($xmlArray);
        $supplier = [];

        foreach($keys as $key){

            $posNit = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyTaxScheme_CompanyID_');
            $posRazonSocial = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyTaxScheme_RegistrationName_');
            $posciudad = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyTaxScheme_RegistrationAddress_CityName_');
            $posdireccion = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyTaxScheme_RegistrationAddress_AddressLine_Line_');
            $poscontacto = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyAddressContact_Telephone_');
            $posemail = strpos($key, 'AccountingSupplierParty') && strpos($key, 'AddressPartyAddressContact_ElectronicMail_');

            if($posNit && !array_key_exists('nit', $supplier)){
                $supplier['nit'] = $xmlArray[$key];
            }

            if($posRazonSocial && !array_key_exists('razon_social', $supplier)){
                $supplier['razon_social'] = $xmlArray[$key];
            }

            if($posciudad  && !array_key_exists('ciudad', $supplier)){
                $supplier['ciudad'] = $xmlArray[$key]; 
            }

            if($posdireccion && !array_key_exists('direccion', $supplier)){
               $supplier['direccion'] = $xmlArray[$key];
            }
            
            if($poscontacto  && !array_key_exists('contacto', $supplier)){
                $supplier['contacto'] = $xmlArray[$key];
            }

            if($posemail   && !array_key_exists('email', $supplier)){
                $supplier['email'] = $xmlArray[$key];
            }
        }
        return $supplier;
    }

    /**
     * Obtener los datos del CDATA en el xml
     */
    private function getCdata($urlUnzip)
    {
        $reader = new \XMLReader();
        $reader->open($urlUnzip);
        $cdata = [];

        while($reader->read()){
            
            if($reader->nodeType == \XMLReader::CDATA){
                $cdata[] = $reader->value;
            }
        }
        return $cdata;

    }
    /**
     * Descomprimir el archivo zip de la facturación electrónica
     */
    private function unzipAttachment($attachment)
    {
        $dirFile = Yii::getAlias('@webroot') . "/tmp_mail";
        $filename = $attachment->id;
        $dirTemp = time();
        $rutaOk = true;
        $zip = new \ZipArchive();
        if($zip->open($attachment->filePath)){
            $dirExtract = $dirFile . '/' . $dirTemp . '/' . $filename;
            // Verificar que la carpeta exista y crearla en caso de no exista
            if (!file_exists($dirExtract)) {
                if (!FileHelper::createDirectory($dirExtract, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }else {
                unlink($dirExtract);
                if (!FileHelper::createDirectory($dirExtract, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            try{
                $zip->extractTo($dirExtract);
            }catch (\Throwable $th) {
                var_dump($th);
            }
            $zip->close();
            $filesExtracted = scandir($dirExtract);

            //buscar si en la carpeta existe un XML
            $fileXMLRequired = '';
            foreach($filesExtracted as $key => $value){
                if(stristr(strtolower($value), '.xml')){
                    $fileXMLRequired = $value;
                    break;
                }
            }
            return $dirExtract . '/' . $fileXMLRequired ;
        }else{
            //die('no se encontró un archivo XML válido');
            return null;
        }
    }
    
    public function unzipDescAttachment($attachment) {
        $dirFile = Yii::getAlias('@webroot') . "/tmp_mail";
        $dirTemp = time();
        $filename = $attachment->id;
        $content = file_get_contents($attachment->filePath);
        $rutaOk = true;
        $zip = new \ZipArchive();
        if($zip->open($attachment->filePath)){
            $dirExtract = $dirFile .'/'. $dirTemp . '/' . $filename;
            // Verificar que la carpeta exista y crearla en caso de no exista
            if (!file_exists($dirExtract)) {
                if (!FileHelper::createDirectory($dirExtract, $mode = 0775, $recursive = true)) {
                    $rutaOk = false;
                }
            }

            try{
                $zip->extractTo($dirExtract);
            }catch (\Throwable $th) {
                var_dump($th);
            }

            $zip->close();
            $filesExtracted = scandir($dirExtract);

            //buscar si en la carpeta existe un XML
            $filePDFRequired = [];
            foreach($filesExtracted as $key => $value){
                if(stristr(strtolower($value), '.pdf') || stristr(strtolower($value), '.xml')){
                    $filePDFRequired[pathinfo($value, PATHINFO_EXTENSION)]['file'] = $dirExtract . '/' . $value;
                    $filePDFRequired[pathinfo($value, PATHINFO_EXTENSION)]['name'] = $value;
                }
            }
            if (!empty($filePDFRequired)){
                return $filePDFRequired ;
            }
            else {
                return '';
            }
        }else{
            //die('no se encontró un archivo XML válido');
            return null;
        }
    }
}
