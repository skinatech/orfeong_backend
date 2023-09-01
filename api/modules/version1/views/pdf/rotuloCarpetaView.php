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
<table class="table table-bordered" cellpadding="10">
    <!-- section1 -->
    <tr style="text-align: center; font-size: 13px;">
        <td rowspan="3" style="text-align: center;"> 
            <img style="margin-top:0px;" height="120" width="270" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
        </td>
        <td colspan="2" style="text-align: center;"> <?=  $data['entidad']  ?></td>
    </tr>
    <tr style="text-align: center; font-size: 13px;">
        <td colspan="2" style="text-align: center;"> Rotulo de Carpeta</td>
    </tr>
    <tr style="text-align: center; font-size: 13px;">
        <td colspan="2" style="text-align: center;">
            <img style="margin-top:0px;" height="80" width="300" src="<?=  Yii::getAlias('@api/web/' . $data['pathBarcode']) ?>">
        </td>
    </tr>
    <!-- section1 -->

    <!-- section2 -->
    <tr style="text-align: center; font-size: 13px;">
        <td><?=  $data['unidad_de_conservacion']  ?></td>
        <td colspan="2"><?=  $data['valor_unidad']  ?></td>
    </tr>
    <tr style="font-size: 13px;">
        <td colspan="3"> <b> NUMERO DE FOLIOS:  <?= $data['total_docs'] ?>  </b></td>
    </tr>
    <!-- / section2 -->
    <!-- section 3 -->
    <tr style="font-size: 13px;">
        <td><b> CODIGO </b></td>
        <td colspan="2">  <?= $data['codigo'] ?>  </td>
    </tr>
    <tr style="font-size: 13px;">
        <td><b> DEPENDENCIA </b></td>
        <td colspan="2">  <?= $data['dependencia'] ?>  </td>
    </tr>
    <!-- section4 -->
    <tr style="font-size: 13px;">
        <td colspan="3"> <b> SERIE/SUBSERIE </b> </td>
    </tr>
    <tr style="font-size: 13px;">
        <td><b>CODIGO </b></td>
        <td colspan="2"><b>NOMBRE </b></td>
    </tr>
    <tr style="text-align: center; font-size: 13px;">
        <td><?= $data['codigo_serie_subserie'] ?></td>
        <td colspan="2"><?= $data['nombre_serie_subserie'] ?></td>
    </tr>
    <!-- section5 -->
    <tr style="font-size: 13px;">    
        <td colspan="3"> <b> ASUNTO:</b> <?= $data['nombre_expediente'] ?></td>
    </tr>
    <tr style="font-size: 11px;">   
        <td>#</td>
        <td> <b> CONTENIDO </b> </td>
        <td> <b> TIPO DOCUMENTAL  </b> </td>
    </tr>
    <?php  foreach($data['contenido'] as $key => $value){ ?>
        <tr style="font-size: 13px;">  
            <td> <?= $key+1 ?></td>
            <td> <?= $value['contenido'] ?> </td>
            <td> <?= $value['tdocumental'] ?> </td> 
        </tr>
    <?php } ?>
    <!-- section6 -->
    <tr style="font-size: 13px;">    
        <td colspan="3"> <b> FECHAS EXTREMAS </b> </td>
    </tr>
    <tr style="text-align: center; font-size: 13px;">
        <td><?= $data['fecha_primer_documento'] ?></td>
        <td colspan="2"><?= $data['fecha_ultimo_documento'] ?></td>
    </tr>
    <!-- section7 -->
    <tr style="font-size: 13px;">
        <td><b>FIRMA RESPONSABLE</b></td>
        <td colspan="2"></td>
    </tr>
</table>
