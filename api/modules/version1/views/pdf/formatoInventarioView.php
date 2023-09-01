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
<?php $acum = 0;  ?> 
<table class="table table-bordered">
  <tbody>

    <tr>
        <td rowspan="3"  style="text-align: center;"> <img style="margin-top:15px;" height="100" width="100" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>"></td>
    </tr>

    <tr>
        <td colspan="3" style="text-align: center;" > <b>  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Formato']; ?>  </b> </td>
    </tr>
    
    <tr>  
        <td colspan="4"  style="text-align: center; line-height: 100px;">  <b> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['FormatoI']; ?>  </b>  </td>
    </tr>

    <tr>  <td colspan="4"> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Procedencia']; ?>   </td> </tr>

  </tbody>


</table>

<table class="table table-bordered">
    <tbody>
        <tr>
            <td colspan="3"> <b> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Oficina']; ?> </b>  <?= $pdf_data['oficinaProductora']   ?></td>
            <td colspan="1">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['FechaE']; ?>  <?= date("Y-m-d H:i A") ?> </td>
        </tr>

        <tr>
            <td colspan="3"><b> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Codigo']; ?>  <?= $pdf_data['codigoOficina']   ?> </b> </td>  
            <td colspan="1">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['NHoja']; ?>   </td> 
        </tr>
  </tbody>
</table>

<table class="table table-bordered">
    <thead>
        <tr>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['NoOrden']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['NExpediente']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['CodigoSerie']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['IdeCarpeta']; ?>     </th>
            <th colspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['fechaExtremas']; ?>     </th>
            <th colspan="4">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['UnidadConservacion']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['NumeroFolio']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['SoporteDoc']; ?>     </th>
            <th rowspan="2">  <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Observa']; ?>     </th>

        </tr>

        <tr>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Inicial']; ?> </td>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Final']; ?> </td>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Caja']; ?> </td>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Carpeta']; ?> </td>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Tomo']; ?> </td>
            <td> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['Otro']; ?> </td>
            
        </tr>
       
    </thead>

    <tbody>

        <?php  foreach($pdf_data['GdExpedientePdf'] as $key => $value){  $acum = $key; ?>

            <tr>
                <th> <?=  ($key+1)                                      ?> </th>
                <th> <?=  $value['no_expediente'];                       ?> </th> 
                <th> <?=  $value['codigo_serie'];                       ?> </th> 
                <th> <?=  $value['numero_carpeta'];                     ?> </th>
                <th> <?=  explode("@",$value['fechas_extremas'])[0];    ?> </th>
                <th> <?=  explode("@",$value['fechas_extremas'])[1];    ?> </th>

                  <?php $unidadConservacion = Yii::$app->params['unidadConservacionFormatoUnicoInventario'];

                   foreach($unidadConservacion as $key => $array){

                      if(in_array($value['unidad_conservacion'], $array)){
                        echo "<th> X </th>";
                      }else{
                        echo "<th>   </th>";
                      }

                   }  ?>

                <th> <?=  $value['numero_folios'];           ?> </th>
                <th> <?=  $value['soporte_documental'];      ?> </th>
                <th> <?=  $value['observaciones'];           ?> </th>
            </tr>

        <?php } ?> 

    </tbody>

  

</table>


<table class="table">
  <tbody>
    <tr>
      <th  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Elaborado']; ?></th>
      <th  style="border: none; padding-right:80px;"> <?=  Yii::t('app', 'MsjTransaferenciaPdf')['TotalCarpetas']; ?> <?= $acum+1 ?>  </th>
      <th  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Recibido']; ?></th>
    </tr>

    <tr>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Nombre']; ?>:  <?= $userAuth['nombre'] ?> </td> 
      <td  style="border: none;"></td>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Nombre']; ?>:  </td>
    </tr>
    <tr>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Cargo'];  ?>:  <?= $userAuth['cargo'] ?> </td>
      <td  style="border: none;"></td>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Cargo'];  ?>:  </td>
    </tr>
    <tr>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Fecha'];  ?>:  <?= $userAuth['fecha'] ?></td>
      <td  style="border: none;"></td>
      <td  style="border: none;"><?=  Yii::t('app', 'MsjTransaferenciaPdf')['Fecha'];  ?>:  </td>
    </tr>
  </tbody>
</table>
