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
        <?php if (count($documentos[$radicado['id']] ?? []) == 0) { ?>
            <div class="Respuestas mt-2 mb-5 col-lg-12 col-md-12 col-sm-12 col-xs-12" >
                <div class="col-11 p-5 m-auto">
                    <p><i class="fas fa-exclamation-circle mr-3"></i>
                        No se adjuntaron anexos para este radicado.</p>
                </div>  
            </div>
        <?php } else { ?>
        <?php foreach ($documentos[$radicado['id']] as $keyD => $anexos) { ?>
            <div  class="col-lg-4 col-md-4 col-sm-12 col-xs-12 p-3">
                <div class="p-3 card contenDocs anexosMin">
                    <div class="headDocs">
                        <span class="material-icons extIco">
                            
                        </span>
                        <h6 class="relativeDocs"> <span> <?= $anexos['fecha']; ?>  </span> </h6>
                    </div>
                    <div class="infoDocs">
                        <small class="description mt-2 mb-2"> 
                            <span> </span>  
                        </small> <br>
                        <small class="description mt-2 mb-2"> 
                            Nombre:               <span> <?= $anexos['nombre']; ?>             </span> 
                        </small> <br>
                        <small class="description mt-2 mb-2"> 
                            Descripción:          <span> <?= $anexos['descripcionTitle']; ?>  </span> 
                        </small> <br>
                        <small class="description mt-2 mb-2"> 
                            Tipo de documento:    <span> <?= $anexos['tipodocumento']; ?>     </span> 
                        </small> <br>
                        <small class="description mt-2 mb-2"> 
                            Usuario:              <span> <?= $anexos['usuario']; ?>           </span> 
                        </small> <br>
                        <small class="description mt-2 mb-2">  
                            Documento público:    <span> <?= $anexos['isPublicoRadiDocumento']; ?>  </span> 
                        </small> <br>
                    </div>
                    <div class="accionesDocs">

                        <?php if($anexos['isPdf'] == 1){ ?>
                            <span class="span2">
                                <a class="btn btn-sm" style="font-size: 12px;" onclick="viewFile('<?= $anexos['id'] ?>','<?= Yii::$app->params['downloadType']['anexo'] ?>')">
                                    <i class="far fa-eye mr-3" title="Ver"> </i> Ver
                                </a>
                            </span>
                        <?php  } ?>

                        <span class="span3">
                            <a class="btn btn-sm" style="font-size: 12px;" onclick="downloadFile('<?= $anexos['id'] ?>','<?= Yii::$app->params['downloadType']['anexo'] ?>')">
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
