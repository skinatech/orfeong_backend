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
            <td rowspan="4" style="text-align: center;" width="18%">
                <img style="margin-top:1px;" height="140" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
            </td>
            <td style="text-align: center;" width="64%">
                <b><?=  Yii::$app->params['cliente'] ?></b> <br><br>
            </td>
            <td width="9%"> <b>FECHA:</b> </td>
            <td width="9%"> <b>16/10/2019</b> </td>
        </tr>
        <tr>
            <td style="text-align: center;">
                <b>SISTEMA DE GESTIÓN DE CALIDAD</b> <br><br>
            </td>
            <td> <b>VERSIÓN:</b> </td>
            <td> <b>0</b> </td>
        </tr>
        <tr>
            <td></td>
            <td> <b>CÓDIGO:</b> </td>
            <td> <b>FT-GD-01</b> </td>
        </tr>
        <tr>
        	<td style="text-align: center;">
                <b>HOJA DE CONTROL</b>
            </td>
            <td></td>
            <td></td>
        </tr>

    </tbody>
</table>

<table class="table">
    <tbody>

        <tr class="table-bordered">
            <td width="18%"> <b>FECHA</b> </td>
            <td width="64%" colspan="2">
            	<?=	$pdf_data['modelExpediente']->creacionGdExpediente; ?>
            </td>
            <td width="18%"></td>
        </tr>
        <tr class="table-bordered">
            <td> <b>NOMBRE DE LA UNIDAD ADMINISTRATIVA</b> </td>
            <td colspan="2">
            	<?= $pdf_data['nombreDependenciaPadre']; ?>
            </td>
            <td></td>
        </tr>
        <tr class="table-bordered">
            <td> <b>NOMBRE DE LA OFICINA PRODUCTORA</b> </td>
            <td colspan="2">
            	<?=	$pdf_data['modelDependenciaExpediente']->nombreGdTrdDependencia; ?>
            </td>
            <td></td>
        </tr>
        <tr class="table-bordered">
            <td> <b>SERIE</b> </td>
            <td>
            	<?= $pdf_data['modelGdTrdSeries']->nombreGdTrdSerie; ?>
            </td>
            <td width="18%" style="text-align: center;"> <b>CÓDIGO</b> </td>
            <td>
            	<?= $pdf_data['modelGdTrdSeries']->codigoGdTrdSerie; ?>
            </td>
        </tr>
        <tr class="table-bordered">
            <td> <b>SUBSERIE</b> </td>
            <td>
            	<?= $pdf_data['modelGdTrdSubseries']->nombreGdTrdSubserie; ?>
            </td>
            <td style="text-align: center;"> <b>CÓDIGO</b> </td>
            <td>
            	<?= $pdf_data['modelGdTrdSubseries']->codigoGdTrdSubserie; ?>
            </td>
        </tr>
        <tr>
            <td class="table-bordered"> <b>TÍTULO</b> </td>
            <td class="table-bordered">
            	<?=	$pdf_data['modelExpediente']->nombreGdExpediente; ?>
            </td>
            <td></td>
            <td></td>
        </tr>

    </tbody>
</table>

<table class="table">
    <tbody>

        <tr class="table-bordered">
            <td style="text-align: center; vertical-align: middle" width="4%"> <b>NO.</b> </td>
            <td style="text-align: center; vertical-align: middle" width="14%"> <b>FECHA</b> </td>
            <td style="text-align: center; vertical-align: middle"> <b>TIPO DOCUMENTAL</b></td>
            <td style="text-align: center; vertical-align: middle" width="10%"> <b>FOLIOS</b></td>
            <td style="text-align: center; vertical-align: middle" width="18%">
            	<b>TIPO DE SOPORTE</b> <br> 
            	<b>(DIGITAL/FISICO)</b>
            </td>
            <td style="text-align: center; vertical-align: middle" width="18%"> <b>OBSERVACIONES</b></td>
        </tr>

		<?php 
			$i = 0;
			foreach($pdf_data['dataList'] as $row){  
			$i++;
		?>
	        <tr class="table-bordered">
	            <td style="text-align: center; vertical-align: middle" width="4%">
	            	<?= $i; ?>
	            </td>
	            <td style="text-align: center; vertical-align: middle" width="14%">
	            	<?= $row['creacionGdExpedienteInclusion']; ?>
	            </td>
	            <td style="text-align: center; vertical-align: middle">
	            	<?= $row['nombreTipoDocumental']; ?>
	            </td>
	            <td style="text-align: center; vertical-align: middle" width="10%">
					<?= $row['folios']; ?>
	            </td>
	            <td style="text-align: center; vertical-align: middle" width="18%">
	            	<?= $row['origen']; ?>
	            </td>
	            <td style="text-align: center; vertical-align: middle" width="18%">
	            	
	            </td>
	        </tr>
        <?php } ?> 

        <tr style="">
            <td colspan="2" style="vertical-align: middle; height: 100px; border-left: 1px solid #ddd"> <b>Nombre de quién elaboró:</b> </td>
            <td colspan="3" style="text-align: center; vertical-align: middle">
            	<u><b><?= $pdf_data['nameUserExpedient']?></b></u>
        	</td>
            <td class="table-bordered" style="vertical-align: middle" width="18%"> </td>
        </tr>

        <tr>
            <td colspan="2" style="vertical-align: middle; border-left: 1px solid #ddd; border-bottom: 1px solid #ddd"> <b>Nombre de quién revisó:</b> </td>
            <td colspan="3" style="border-bottom: 1px solid #ddd"> </td>
            <td class="table-bordered" style="vertical-align: middle;" width="18%"> </td>
        </tr>

    </tbody>
<<<<<<< HEAD
</table>
=======
</table>
>>>>>>> origin
