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
    <table class="table table-bordered">
        <thead>
            <tr>
            <th>
                <!-- section1 -->    
                <table class="table table-bordered">
                    <tbody>
                        <tr style="text-align: center; font-size: 13px;">
                            <td rowspan="2" style="text-align: center;"> 
                                <img style="margin-top:0px;" height="120" width="270" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
                                <!-- <img style="margin-top:0px;" height="80" width="80" src="http://localhost/ng/ng_backend/api/web/img/logo.png">  -->
                            </td>
                            <td style="text-align: center;"> <?=  $data['entidad']  ?></td>
                        </tr>
                        <tr style="text-align: center; font-size: 13px;">
                            <td style="text-align: center;">Rotulo de Caja</td>
                        </tr>
                    </tbody>
                </table>
                <!-- / section1 -->
                <!-- section2 -->
                <table class="table table-bordered">
                    <tbody>
                        <tr style="text-align: center; font-size: 13px;">
                            <td colspan="2"> <b> CAJA NUMERO: </b> <?= $data['n_caja']; ?></td>
                            <td colspan="2"> <b> No. TOTAL UNIDADES: </b> <?= $data['total_expedientes']; ?> </td>
                        </tr>
                        <tr style="font-size: 13px;">
                            <td> <b> 1° No. UNIDAD </b></td>
                            <td>  1 </td>
                            <td> <b> ULT No. UNIDAD </b></td>
                            <td>  <?= $data['total_uni']; ?> </td>
                        </tr>
                    </tbody>
                </table>      
                <!-- / section2 -->
                <!-- section3 -->
                <table class="table table-bordered">
                    <tbody>
                        <tr style="font-size: 13px;">
                            <td><b> CODIGO </b></td>
                            <td> <?= $data['codigo']; ?>  </td>
                        </tr>
                        <tr style="font-size: 13px;">
                            <td><b> DEPENDENCIA </b></td>
                            <td> <?= $data['dependencia']; ?>  </td>
                        </tr>
                    </tbody>
                </table>      
                <!-- / section3 -->
                <!-- section4 -->
                <table class="table table-bordered">
                    <thead>
                        <tr style="font-size: 13px;">    
                            <td colspan="3" style="text-align: center;"> <b>  CONTENIDO </b></td>
                        </tr>
                        <tr style="font-size: 13px;">  
                            <td></td>
                            <td colspan="2" style="text-align: center;"> <b> SERIE / SUBSERIE </b></td>  
                        </tr>
                        <tr style="font-size: 11px;"> 
                            <td> # </td> 
                            <td> <b> CODIGO </b> </td>
                            <td> <b> NOMBRE  </b> </td>
                        </tr>
                    </thead>
                    <tbody>
                        <?php  foreach($data['contenido'] as $key => $value){ ?>
                            <tr style="font-size: 13px;">  
                                <td> <?= $key+1 ?></td>
                                <td> <?= $value['codigo'] ?> </td>
                                <td> <?= $value['nombre'] ?> </td> 
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>      
                <!-- / section4 -->
                <!-- section6 -->
                <table class="table table-bordered">
                    <thead>
                        <tr style="font-size: 13px;">    
                            <td colspan="2"> <b> FECHAS EXTREMAS </b> </td>
                        </tr>
                        <tr style="text-align: center; font-size: 13px;">
                            <td><?= $data['fecha_primer_documento'] ?></td>
                            <td><?= $data['fecha_ultimo_documento'] ?></td>
                        </tr>
                    </thead>
                </table>      
                <!-- / section6 -->
                <!-- section7 -->
                <table class="table table-bordered">
                    <thead>
                        <tr style="font-size: 13px;">
                            <td> <br> <b>  FIRMA RESPONSABLE </b></td>
                            <td style="text-align: center;">  <br> ______________________________________________</td>
                        </tr>
                    </thead>
                </table>      
                <!-- / section7 -->
            </th>
            </tr>
        </thead>
    </table>

