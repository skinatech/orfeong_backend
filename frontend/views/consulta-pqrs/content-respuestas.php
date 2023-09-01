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
<div class="card-body ">
    <div class="row">
        <!-- Row 1 -->
            <?php if (count($respuestas[$radicado['id']] ?? []) == 0) { ?>
                <div class="Respuestas mt-2 mb-5 col-lg-12 col-md-12 col-sm-12 col-xs-12" >
                    <div class="col-11 p-5 m-auto">
                        <p><i class="fas fa-exclamation-circle mr-3"></i>
                            No se adjuntaron respuestas para este radicado.</p>
                    </div>  
                </div>
            <?php } else { ?>
            <?php foreach ($respuestas[$radicado['id']] as $keyR => $respuesta) { ?>
                <div  class="col-lg-4 col-md-4 col-sm-12 col-xs-12 p-3">
                    <div class="p-3 card contenDocs anexosMin">
                        <div class="headDocs">
                            <span class="material-icons extIco">
                                <i class="far fa-file-pdf"></i>
                            </span>
                            <h6 class="relativeDocs"> 
                                <span> <?= $respuesta['fecha']; ?>  
                                    <?php   if($respuesta['imgPrincipal'] == 10){ ?> 
                                        <i class="far fa-star colorStart"></i>
                                    <?php }  ?> 
                                </span> 
                            </h6>
                        </div>
                        <div class="infoDocs">
                            <small class="description mt-2 mb-2"> 
                                <span> </span>  
                            </small> <br>
                            <small class="description mt-2 mb-2"> 
                                Radicado:    <span> <?= $radicado['numeroRadiRadicado']; ?>  </span> 
                            </small> <br>
                            <small class="description mt-2 mb-2"> 
                                Nombre:      <span> <?= $respuesta['nombre']; ?>             </span> 
                            </small> <br>
                            <small class="description mt-2 mb-2"> 
                                Usuario:     <span> <?= $respuesta['usuario']; ?>            </span> 
                            </small> <br>
                            <!-- <small class="description mt-2 mb-2">  
                                Generada:    <span> <?// $respuesta['isPublicoRadiDocumento']; ?>  </span> 
                            </small> <br> -->
                        </div>
                        <div class="accionesDocs">

                            <span class="span2">
                                <a class="btn btn-sm" style="font-size: 12px;" onclick="viewFile('<?= $respuesta['id'] ?>','<?= Yii::$app->params['downloadType']['principal'] ?>')">
                                    <i class="far fa-eye mr-3" title="Ver"> </i> Ver
                                </a>
                            </span>

                            <span class="span3">
                                <a class="btn btn-sm" style="font-size: 12px;" onclick="downloadFile('<?= $respuesta['id'] ?>','<?= Yii::$app->params['downloadType']['principal'] ?>')">
                                    <i class="fas fa-download mr-3" title="Descargar"></i> Descargar
                                </a>
                            </span>

                        </div> 
                    </div>
                </div>
            
                <?php } ?>
            <?php } ?>
        <!-- Row 1 -->
        </div> 
    </div>
