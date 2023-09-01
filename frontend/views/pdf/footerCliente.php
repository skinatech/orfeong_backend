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
?>
<div style="width:80%; font-weight: 100!important;  text-align:left; padding-top:10px; padding-bottom:50px;">
    <p style="font-size: 13px; padding-bottom:5px; font-weight: 100!important; font-style: normal;"> <b> <?=  Yii::t('app', 'MsjRadiExterna')['atentamente']?> </b> </p>
    <p style="font-size: 13px; padding-bottom:5px; font-weight: 100!important; font-style: normal;"> <?=  $pdf_data['cliente'] ?> - <?=  $pdf_data['numeroDocumento'] ?> </p>
    <p style="font-size: 13px; padding-bottom:5px; font-weight: 100!important; font-style: normal;"> <?=  $pdf_data['clienteUbicacion'] ?>  </p>
    <p style="font-size: 13px; padding-bottom:5px; font-weight: 100!important; font-style: normal;"> <?=  Yii::t('app', 'MsjRadiExterna')['telefono']?>  <?=  $pdf_data['clienteTelefono'] ?>  </p> 
</div>
