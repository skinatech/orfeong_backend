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
    <tbody>

        <tr>
            <td rowspan="3" style="text-align: center;" width="30%">
                <img style="margin-top:1px;" height="140" width="140" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
            </td>

            <td rowspan="3" style="text-align: center;" width="40%">
                <b><?=  Yii::$app->params['cliente'] ?></b> <br><br>
                <b>SISTEMA DE GESTIÓN DE CALIDAD</b> <br><br>
                <b>PROCESO: GESTIÓN DOCUMENTAL</b> <br>
                <b>FORMATO: REFERENCIA CRUZADA</b>
            </td>
            
            <td width="30%"> <b>FECHA: 16/10/2019</b> </td>
        </tr>
        <tr>
            <td> <b>VERSIÓN: 0</b> </td>
        </tr>
        <tr>
            <td> <b>CÓDIGO: FT-GD-06</b> </td>
        </tr>

    </tbody>
</table>

<table class="table">
    <tbody>

        <tr>
            <td class="table-bordered" style="text-align: center; vertical-align: middle;" width="5%"> <b>ITEM</b> </td>

            <td class="table-bordered" style="text-align: center; vertical-align: middle;" width="25%">
                <b>DOCUMENTO ANEXO</b> <br>
                (Marque con una X)
            </td>

            <?php foreach($pdf_data['arrayTiposAnexosFisicos'] as $row){  ?>

                <td style="text-align: right; vertical-align: middle; border-top: none;" >
                    <b> <?= strtoupper($row['key']); ?> </b>
                </td>

                <td class="table-bordered" style="text-align: center; vertical-align: middle;" >
                    <b> <?= ($row['value'] == true) ? 'X' : '';  ?> </b>
                </td>
            <?php } ?> 

        </tr>

    </tbody>
</table>

<table class="table table-bordered">
    <tbody>

        <tr>
            <td style="text-align: center; vertical-align: middle;" width="5%"> 1 </td>
            <td style="vertical-align: middle;" width="25%"> NOMBRE: (Del medio) </td>
            <td colspan="2" style="vertical-align: middle;">
                <?= $pdf_data['modelGdReferenciasCruzadas']->nombreGdReferenciaCruzada; ?>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;"> 2 </td>
            <td style="vertical-align: middle;"> CANTIDAD: </td>
            <td colspan="2" style="vertical-align: middle;">
                <?= $pdf_data['modelGdReferenciasCruzadas']->cantidadGdReferenciaCruzada; ?>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;"> 3 </td>
            <td style="vertical-align: middle;"> FECHA: </td>
            <td colspan="2" style="vertical-align: middle;">
                <?= $pdf_data['modelGdReferenciasCruzadas']->creacionGdReferenciaCruzada; ?>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;"> 4 </td>
            <td style="vertical-align: middle;"> UBICACIÓN: </td>
            <td colspan="2" style="vertical-align: middle;">
                <?= $pdf_data['modelGdReferenciasCruzadas']->ubicacionGdReferenciaCruzada; ?>
            </td>
        </tr>
        <tr>
            <td style="text-align: center; vertical-align: middle;"> 5 </td>
            <td style="vertical-align: middle;"> QUIEN ELABORA: </td>
            <td style="vertical-align: middle;">
                <?= $userAuth; ?>
            </td>
            <td style="vertical-align: middle;"> FIRMA: </td>
        </tr>

    </tbody>
<<<<<<< HEAD
</table>
=======
</table>
>>>>>>> origin
