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
<br>
<div style="width:50%; float:right; text-align:center;">
    <barcode code="<?= $pdf_data['numeroRadiRadicado'] ?>" type="C39" />
    <p style="margin-top:10px;">  <?= Yii::t('app', 'MsjRadiExterna')['radicado']; ?> <?= $pdf_data['numeroRadiRadicado'] ?> </p> 

<div style="width:380px; float:right; text-align:justyfi; font-size:12px; margin-top:10px; line-height: 0.75;" >

    <p> <?= Yii::t('app', 'MsjRadiExterna')['fechaGeneracion']; ?>  <?= date("y-m-d h:m:s") ?>,

        <?php
        if (isset($pdf_data['numeroAnexos']) && $pdf_data['numeroAnexos'] > 0) {
            echo Yii::t('app', 'MsjRadiExterna')['anexos'].' '.$pdf_data['numeroAnexos'];
        } else {
            echo Yii::t('app', 'MsjRadiExterna')['noneAnexos'];
        }
        ?>

    </p>

    <p> <?= Yii::t('app', 'MsjRadiExterna')['destino']; ?> <?= $pdf_data['dependencias'] ?>  <?= Yii::t('app', 'MsjRadiExterna')['dependencia']; ?>  <?= $pdf_data['cliente'] ?>  </p>
    <p> <?= Yii::t('app', 'MsjRadiExterna')['consulteTramite']; ?> </p> URL: https://pqr.fonprecon.gov.co/ng_backend/frontend/web/consulta-pqrs/index

</div>

</div>


<div style="width:80%; float:left; text-align:left; margin-top:100px;">
    <p> <span style="font-size:13px;"><?= $pdf_data['clienteUbicacion']; ?> <?=  $pdf_data['fecha']; ?></span></p>
    <p> <?=  Yii::$app->params['cliente'] ?></p>
    
    <p style="margin-top:30px;"> <strong><?=  Yii::t('app', 'MsjRadiExterna')['asunto'].':</strong> '.$pdf_data['asuntoTitulo']; ?> </p><br>
</div>

<div style="width:100%; text-align:justify;" class="">       
    <p style="line-height:30px; text-align:justyfi"> <?= $pdf_data['asunto'] ?>  </p>
</div>

<div style="width:80%; float:left; text-align:left; margin-top:10px;">

    <p> <?=  Yii::t('app', 'MsjRadiExterna')['radicaciones']; ?> </p> 

    <?php if (isset($attached_files)) { ?> 

        <p> <?=  sizeof($attached_files) > 0 ? Yii::t('app', 'MsjRadiExterna')['adjuntos'] : ''; ?> </p>

        <table class="col-12">
            <tbody>

                <?php
                    for ($i = 0; $i < sizeof($attached_files); $i++) {
                ?>
                    <tr>
                        <th scope="row"><?= ($i + 1) ?>.</th>
                        <td><?= $attached_files[$i]['nombreRadiDocumento'] ?></td>
                    </tr>
                <?php
                    }
                ?>

            </tbody>
        </table>

    <?php } else { ?>

        <p> <?=  Yii::t('app', 'MsjRadiExterna')['noneAdjuntos']; ?> </p>    

    <?php } ?>
</div>

<div style="width:80%; float:left; text-align:left; margin-top:10px;">
    <?php 
        if($pdf_data['autorizo'] != 'no'){
            switch($pdf_data['autorizacion']){
                case Yii::$app->params['seguimientoViaPQRS']['text']['DirecciónFísica']:
                    ?>
                    <p> <?=   Yii::t('app', 'MsjRadiExterna')['responderViaDireccion']; ?> : <?=$pdf_data['medioInformativo']?></p>
                    <?php
                break;
                case Yii::$app->params['seguimientoViaPQRS']['text']['CorreoElectrónico']:
                    ?>
                    <p>  <?=  Yii::t('app', 'MsjRadiExterna')['responderViaCorreo']; ?> : <?=$pdf_data['medioInformativo']?> </p>
                    <?php
                break;
            }
        }else{
            ?>
                <p> Teniendo en cuenta que no se autorizó notificación electrónica, se le enviará la respuesta a la dirección de correspondencia registrada. </p>
            <?php
        }
    ?>
</div>
