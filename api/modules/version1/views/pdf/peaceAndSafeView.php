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
$diassemanaN= array("Domingo","Lunes","Martes","Miércoles",
"Jueves","Viernes","Sábado");
$mesesN=array(1=>"Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio",
"Agosto","Septiembre","Octubre","Noviembre","Diciembre");
?>
<table class="table table-bordered" style="border: none !important;">
  <tbody>
    <tr>
      <td rowspan="3" width="25%" style="border: none !important;">
        <img style="margin-top:1px;" height="50" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
      </td>
      <td style="border: none !important;" width="60%">FONDO DE PREVISION SOCIAL DEL CONGRESO DE LA REPUBLICA</td>
      <td style="border: none !important;">Fecha <?= date("m/d/Y"); ?></td>
    </tr>
    <tr>
      <td style="border: none !important;" width="60%">Control de Expedientes</td>
      <td style="border: none !important;">Hora <?= date("g:i:sa"); ?></td>
    </tr>
    <tr>
      <td style="border: none !important;" width="60%">Paz y Salvo</td>
    </tr>
  </tbody>
</table>

<table class="table table-bordered" style="border: none !important;">
  <tbody>
    <tr>
      <td style="text-align: center; border: none !important;"><b>HACE CONSTAR</b></td>
    </tr>
  </tbody>
</table>

<table class="table table-bordered" style="border: none !important;">
  <tbody>
    <tr>
      <td style="border: none !important;">Que al(la) señor(a) <b><?= $nombreRemitente; ?></b> identificado(a) con la Cédula de Ciudadania No. <b><?= $numeroDocumentoCliente; ?></b>, a la fecha se le ha reconocido</td>
    </tr>
  </tbody>
</table>

<table class="table table-bordered">
  <tbody>
    <tr>
      <td colspan="3" style="text-align: center;">Radicación</td>
      <td colspan="5" style="text-align: center;">Resolución</td>
    </tr>
    <tr>
      <td><b>Clase</b></td>
      <td><b>No.</b></td>
      <td><b>Fecha</b></td>
      <td><b>No.</b></td>
      <td><b>Fecha</b></td>
      <td style="text-align: right;"><b>Valor</b></td>
      <td><b>Moneda</b></td>
      <td><b>Estado</b></td>
    </tr>
    <?php
    $totalGeneral = 0;
    foreach ($data as $key => $value) {
      $totalGeneral = $totalGeneral + $value->radiRadicadosResoluciones->valorRadiRadicadoResolucion;
    ?>
      <tr>
        <td><?= $value->trdTipoDocumental->nombreTipoDocumental; ?></td>
        <td><?= $value->numeroRadiRadicado; ?></td>
        <td><?= date("d/m/Y", strtotime($value->creacionRadiRadicado)); ?></td>
        <td><?= $value->radiRadicadosResoluciones->numeroRadiRadicadoResolucion; ?></td>
        <td><?= date("d/m/Y", strtotime($value->radiRadicadosResoluciones->fechaRadiRadicadoResolucion)); ?></td>
        <td style="text-align: right;"><?= number_format($value->radiRadicadosResoluciones->valorRadiRadicadoResolucion, 2, ',', '.'); ?></td>
        <td>PESO</td>
        <td><?= Yii::$app->params['statusTodoNumber'][$value->estadoRadiRadicado]; ?></td>
      </tr>
    <?php
    }
    ?>
    <tr>
      <td style="text-align: right;" colspan="5"><b>Total General</b></td>
      <td style="text-align: right;"><?= number_format($totalGeneral, 2, ',', '.'); ?></td>
    </tr>
  </tbody>
</table>

<table class="table table-bordered" style="border: none !important;">
  <tbody>
    <tr>
      <td style="border: none !important;">Para constancia se firma en Bogotá D.C. a <?= $diassemanaN[date("w")] ." ". date("d") ." ". $mesesN[date("n")] .", ". date("Y"); ?> por quienes ratifican la información:</td>
    </tr>
  </tbody>
</table>

<table>
  <tbody>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
    <tr>
      <td></td>
    </tr>
  </tbody>
</table>

<table class="table table-bordered" style="border: none !important;">
  <tbody>
    <tr>
      <td width="10%" style="border: none !important;"></td>
      <td width="35%" style="border: none !important; text-align: center;">MARTHA LILIANA LEYVA</td>
      <td width="10%" style="border: none !important;"></td>
      <td width="35%" style="border: none !important; text-align: center;"></td>
      <td width="10%" style="border: none !important;"></td>
    </tr>
    <tr>
      <td width="10%" style="border: none !important;"></td>
      <td width="35%" style="text-align: center; border-bottom: none !important; border-right: none !important; border-left: none !important;">PROFESIONAL UNIVERSITARIO</td>
      <td width="10%" style="border: none !important;"></td>
      <td width="35%" style=" text-align: center; border-bottom: none !important; border-right: none !important; border-left: none !important;">Liquidador</td>
      <td width="10%" style="border: none !important;"></td>
    </tr>
  </tbody>
</table>
