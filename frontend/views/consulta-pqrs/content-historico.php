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
<?php if(isset($historicos[$radicado['id']])) { ?>
    <?php foreach ($historicos[$radicado['id']] as $keyH => $radiHistorico) { ?>

    <div class="historico mt-2 mb-5">
        <div class="col-11 cont-historico m-auto">
            <ul class="blog-info-link">
                <li><a href="#"> <b> Estado </b>: <?= $radiHistorico['transaccion'] ?>   </a></li> 
                <li><a href="#"> <?= $radiHistorico['fecha'] ?> </a></li>
            </ul>
            <p> Usuario: <b> <?= $radiHistorico['usuario'] ?> </b></p>
            <p> Dependencia: <b> <?= $radiHistorico['dependencia'] ?> </b></p>
            <p> Observación: <?= $radiHistorico['observacion'] ?> </p>
        </div> 
        <?php if (count($historicos[$radicado['id']]) != ($keyH + 1) && count($historicos[$radicado['id']]) != 1) { ?>
            <div class="Separador"> 
                <div class="separadorline"></div>  
                <i class="fas fa-chevron-down Arrow"></i>
                <div class="separadorline"></div>  
            </div> 
        <?php } ?>
    </div>

    <?php } ?>
<?php } ?>
