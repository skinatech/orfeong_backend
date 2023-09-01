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
<div style="width:50%; float:left; text-align:left; position:relative;">
    <div style="position:absolute; top:50px;">
        <img src="<?=  Yii::getAlias('@api/web/img/logoEncabezado.png') ?>" width="150px">
    </div>
</div>
        

<div style="width:95%;  margin-left:3%;  float:left; text-align:left; margin-top:50px; line-height:13px;">
    <div style="text-align: right; margin-left: 28%">
        <div style="font-size:10px; text-align: justify; line-height:5px;">
            <p> <?= Yii::t('app', 'MsjRadiEmailPdf')['radi'];  ?> </p> 
            <p><barcode code="<?= $pdf_data['numeroRadicado'] ?>" type="C39" /></p>
            <p> <?= Yii::t('app', 'MsjRadiEmailPdf')['numberfiled']; ?> <?php echo $pdf_data['numeroRadicado']; ?> - <?=  Yii::t('app', 'MsjRadiEmailPdf')['fechaRadicado']; ?> <?php echo $pdf_data['fechaRadicado']; ?></p> 
            <p style="font-size:8px;"> <?=  Yii::t('app', 'MsjRadiEmailPdf')['usuaRadicado']; ?> <?php echo $pdf_data['usuaRadicado']; ?> - <?=  Yii::t('app', 'MsjRadiEmailPdf')['depeRadicado']; ?> <?php echo $pdf_data['depeRadicado']; ?></p>
            <p> <?= Yii::t('app', 'MsjRadiEmailPdf')['descAnexos']; ?> <?php echo $pdf_data['descAnexos']; ?> - <?=  Yii::t('app', 'MsjRadiEmailPdf')['folioRadicado']; ?> <?php echo $pdf_data['folioRadicado']; ?></p>
            <p> <?= Yii::$app->params['cliente'] ?> </p>
            <br><br>
        </div>
    </div><br><br>
    <p> <b> <?=  Yii::t('app', 'MsjRadiEmailPdf')['from']; ?> </b> <?php  echo $pdf_data['From']; ?> &lt;<?php  echo $pdf_data['fromAddress']; ?>&gt;</p>

    <p> <b> <?=  Yii::t('app', 'MsjRadiEmailPdf')['to']; ?>  </b> <?php
         for ($i = 0; $i < sizeof($pdf_data['to']); $i++) {
                if($i>0){  echo "<br> RE:"; }   
                echo $pdf_data['to'][$i];    
         }
    ?> </p>

    <p> <b> <?=  Yii::t('app', 'MsjRadiEmailPdf')['affair']; ?>  </b> <?php  echo $pdf_data['subject']; ?>  </p>

    <p> <b> <?=  Yii::t('app', 'MsjRadiEmailPdf')['date']; ?>  </b> <?php   echo $pdf_data['date']; ?>      </p>

</div>

<div style="width:90%;  margin-left:5%; margin-right:5%; text-align:justify; float:left; margin-top:80px;" >       
    <p style="line-height:30px;"> <?php  echo $pdf_data['body']; ?>  </p>

</div>

<div style="width:90%; margin-left:5%; margin-right:5%; float:left;  margin-top:150px;">

    <p> <?=  Yii::t('app', 'MsjRadiEmailPdf')['following_files']; ?>  </p>    

    <?php if (isset($attached_files)) { 
            for ($i = 0; $i < sizeof($attached_files); $i++) {

                ?>
                    <label> <?= $attached_files[$i].',' ?></label>

    <?php
            }

    } else { ?>

        <p> <?=  Yii::t('app', 'MsjRadiEmailPdf')['no_file_was']; ?>  </p>    

    <?php } ?>

</div>
