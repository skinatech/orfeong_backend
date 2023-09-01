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
        <td rowspan="6" style="text-align: center;"> <img style="margin-top:20px;" height="160" width="160" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>"></td>
    </tr>

    <tr>
        <td colspan="4"> <b>  <?=  Yii::$app->params['cliente'] ?>  </b> </td>
    </tr>

    <tr>
        <td colspan="2"> <b> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['NIT']; ?>  </b>  </td>
        <td colspan="2"> <?= Yii::$app->params['nit'] ?> </td>
    </tr>

    <tr>
        <td colspan="2"> <b> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['NOMBRE_FORMATO']; ?>  </b> </td>
        <td colspan="2"> <?=  Yii::t('app', 'formatoCorrespondencia'); ?>  </td> 
    </tr>

    <tr>
        <td colspan="2"> <b>  <?=  Yii::t('app', 'MsjDistriEnvioPdf')['USUARIO_RESPONSABLE']; ?> </b> </td>
        <td colspan="2"> <b>  <?=  Yii::t('app', 'MsjDistriEnvioPdf')['FIRMA_MENSAJERO']; ?>    </b> </td>
    </tr>

    <tr>
        <td colspan="2"> <?= $userAuth ?>      </td>
        <td colspan="2">                       </td>
    </tr>
  </tbody>

</table>

<table class="table table-bordered">
    <thead>
        <tr>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['No']; ?></th>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['RADICADO']; ?></th>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['FECHA RADICADO']; ?></th>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['DESTINA_RESPO']; ?> </th>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['DIRECCION']; ?></th>
            <th> <?= Yii::t('app', 'MsjDistriEnvioPdf')['MUNICIPIO']; ?> </th>
            <!-- <th> <? // Yii::t('app', 'MsjDistriEnvioPdf')['ORIGEN']; ?> </th>
            <th> <? // Yii::t('app', 'MsjDistriEnvioPdf')['FIRMA']; ?> </th>-->
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['FECHA DE RECIBIDO']; ?> </th>
            <th> <?=  Yii::t('app', 'MsjDistriEnvioPdf')['NO_GUIA']; ?> </th>

        </tr>
    </thead>

    <tbody>

        <?php foreach($pdf_data as $key => $value){  ?>

            <tr>
                <th> <?=  $value['NO'];                ?> </th>
                <th> <?=  $value['RADICADO'];          ?> </th>
                <th> <?=  $value['FECHA_RADICADO'];    ?> </th>
                <th> <?=  $value['DESTINA_RESPO'];     ?> </th>
                <th> <?=  $value['DIRECCION'];         ?> </th>
                <th> <?=  $value['MUNICIPIO'];            ?> </th>
                <!-- <th> <? // $value['ORIGEN'];            ?> </th>
                <th> <? // $value['FIRMA']."<br> _______________________";             ?> </th>-->
                <th> <?=  $value['FECHA_DE_RECIBIDO']; ?> </th>
                <th> <?=  $value['NO_GUIA'];           ?> </th>
            </tr>

        <?php } ?> 

    </tbody>

</table>
