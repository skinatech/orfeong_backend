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

use kartik\file\FileInput;
use yii\widgets\LinkPager;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;

$this->title = 'Consulta Pqrs';
$this->params['breadcrumbs'][] = $this->title;

?>  

<style>
    #w2-error-0{
        display:none;
    }
    /* .bootbox {
        position: fixed;
        top: 40%;
        left: 0%; 
    } */
    .contenDocs {
        width: 100%;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        box-shadow: 0 1px 4px 0 rgba(0, 0, 0, 0.26);
        cursor: pointer;
        padding: 40px 25px 20px 25px!important;
    }
    .focusDiv {
        background-color: #007fff42;
    }

        .contenDocs.headDocs,
        .contenDocs.infoDocs,
        .contenDocs.accionesDocs {
            width: 100%;
        }

        .headDocs {
            height: auto;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .extIco {
            font-size: 70px;
        }

        .relativeDocs {
            position: absolute;
            top: 15px;
            right: 15px;
            color: #1213;
        }

        .accionesDocs {
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            align-items: center;
            margin-top: 30px;
        }

        .accionesDocs span {
            padding: 5px;
            border-radius: 5px;
        }

            .span1{
                background-color: #365c8a;
                color: #fff;
            }
            .span2{
                background-color: #365c8a;
                color: #fff;
            }
            .span3{
                background-color: #365c8a;
                color: #fff;
            }
            .span4{
                background-color: #365c8a;
                color: #fff;
            }

        .timeline {
            position: relative;
            padding: 21px 0px 10px;
            margin-top: 4px;
            margin-bottom: 30px;
        }

        .timeline .line {
            position: absolute;
            width: 3px;
            display: block;
            background: currentColor;
            top: 30px;
            bottom: 0px;
            margin-left: 10px;
        }

        .timeline:before {
            left: 10px !important;
        }

        .marginHistory {
            margin-left: 20px;
            margin-bottom: 0rem;
            text-align: justify;
        }

        .moreHistory {
            text-align: center !important;
            color: #365c8a
        }
            .style-none{
                display: none;
            }

            @media (max-width: 600px) {
                .style-none{
                    display: block;
                    
                }
                    .margin-left{
                        margin-left: 20px!important;
                    }
            }

            .colorStart{
                color:#ff9800;
            }

        .form-holder i {
            right: 0px!important;
            top: 9px!important;
            font-size: 16px!important;
        }

        .form-text-area{
            padding: 11px 11px;
            text-align: justify;
        }
            .form-holder i {
                right: 0px!important;
                top: 7px!important;
                font-size: 16px!important;
            }
                .AddComment{
                    font-size: 20px;
                    margin-left: 20px;
                }
                .anexosMin{
                    min-height: 320px;
                    display: flex;
                    flex-direction: column;
                    justify-content: space-around;
                }
        .div100 div{
            width: 100%!important;
        }

        .alert-floating{  
            position: fixed;
            width: 420px;
            height: 100px;
            z-index: 999;
            top: 33px;
            right: 16px;
        }
            .alert-msj{
                position: absolute;
                top: 30%;
            }
                .color-success{
                    color: #155724;
                    background-color: #d4edda;
                    border-color: #c3e6cb;
                }

                .color-danger{
                    color: #721c24;
                    background-color: #f8d7da;
                    border-color: #f5c6cb;
                }

        @media (max-width: 600px) {
            .alert-floating{
                position: fixed;
                width: 384px;
                height: 100px;
                z-index: 999;
                top: 75px;
                right: 16px;
            } 
        }

        .form-text-area-new{
            width: 100%;
            border: 1px solid #ddd;
            padding: 14px 10px;
            border-radius: 5px;
        }

</style>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">

<!--================ RADICADOS               =================-->
<section class="blog_area section-padding" >
    <div class="container">
        <div class="row column-reverse">
           
            <?php if(!empty($radicados)){ ?>
                <div class="col-lg-8 mb-5 mb-lg-0 Contenedor_Consultas">

                    <div id="alert-class" class="alert-floating" style="display:none;">
                        <p id="alert-msj" class="alert-msj"></p>
                        <button aria-hidden="true" data-dismiss="alert" style="right: 0px!important;" class="close" type="button">×</button>
                        <?= Yii::$app->session->getFlash('error') ?>
                    </div>
        
                    <div class="blog_left_sidebar">

                        <!--   Radicados   -->
                        <?php
                        foreach ($radicados as $key => $radicado) {
                            $img = 'https://picsum.photos/id/' . mt_rand(12,16) . '/800/300';
                            ?>

                            <article class="blog_item">
                                
                                <div class="blog_item_img">
                                    <div class="col-12 block-flex" >
                                        <div class="status_relative">
                                            <div class="cont_status" id="status<?= $radicado['id'] ?>" title="Estado"> <?= $radicado['statusText'] ?></div>
                                        </div>
                                    </div>

                                    <img class="card-img rounded-0 portada_radicado" src="<?= $img ?>" alt="">
                                    
                                    <a class="blog_item_date">
                                        <h3><?= date("d", strtotime($radicado['creacionRadiRadicado'])) ?></h3>
                                        <p>
                                            <?php   
                                        
                                                $mes = str_replace("0","",date("m", strtotime($radicado['creacionRadiRadicado'])));

                                                if(isset(Yii::t('app', 'months')[$mes])){
                                                    echo Yii::t('app', 'months')[$mes];
                                                }else{
                                                    echo Yii::t('app', 'months')[0];
                                                }
                                        
                                            ?>
                                        </p>
                                    </a>

                                    <!-- <a class="pdf-generado" onclick="viewFile('<?php // echo $radicado['imgPricipal'] ?>','<?php // echo Yii::$app->params['downloadType']['principal'] ?>')">
                                        <i class="far fa-file-pdf pdf-generado-icon"></i>    
                                        <h6 class="pdf-generado-title"> Imagen Principal</h6>
                                    </a> -->
                                </div>

                                <div class="blog_details">

                                    Tipo de Solicitud: <b><?= $radicado['nombreTipoDocumental'] ?></b>  <br>

                                    <div class="d-inline-block mt-3" >
                                        <h2>Radicado Nº: <b><?= $radicado['numeroRadiRadicado'] ?></b>
                                            <span onclick="reloadw('<?= $radicado['id'] ?>')">
                                                <i id="reload<?= $radicado['id'] ?>"  class="fas fa-sync-alt Ireload">  </i>
                                                <?php
                                                echo Html::img('@web/img/loading-plugin.gif', ['id' => 'loading' . $radicado['id'],
                                                    'class' => 'w-10', 'alt' => '',
                                                    'style' => 'display:none; width: 20px;']);
                                                ?> 
                                                <a id="mensajeLoad<?= $radicado['id'] ?>" class="mensajeLoad"> Actualizar  </a>

                                            </span>
                                            <span id="complete<?= $radicado['id'] ?>" style="display:none;">
                                                <i class="fas fa-clipboard-check IComplete"></i>
                                            </span>
                                            <a id="mensajeComplete<?= $radicado['id'] ?>" class="mensajeLoad">   </a>
                                        </h2>
                                    </div>
                                    <p><?= $radicado['asuntoRadiRadicado'] ?>...</p>

                                    <ul class="blog-info-link" id="OpcionesR<?= $radicado['id']; ?>">
                                        <li onclick="open_historico('<?= $radicado['id']; ?>', this)">
                                            <a><i  class="fas fa-history"></i> Trazabilidad</a>
                                        </li> 
                                        <li onclick="open_resultados('<?= $radicado['id']; ?>', this)">
                                            <a><i class="far fa-bell"></i> Respuestas </a>
                                        </li>
                                        <li onclick="open_anexos('<?= $radicado['id']; ?>', this)">
                                            <a><i  class="far fa-folder-open"></i> Anexos </a>
                                        </li>
                                        <?php if($radicado['status'] !=  Yii::$app->params['statusTodoText']['Finalizado']){?>
                                        <!-- <li id="li-open-comment" onclick="open_comentarios('<?= $radicado['id']; ?>', this)">
                                            <a><i class="far fa-comments"></i> Agregar comentario </a>
                                        </li> -->
                                        <?php  } ?>
                                        <?php if($radicado['status'] !=  Yii::$app->params['statusTodoText']['Finalizado']){?> 
                                        <li id="li-open-desist" onclick="open_desistimiento('<?= $radicado['id']; ?>', '<?= $radicado['numeroRadiRadicado']; ?>')">
                                            <a><i class="far fa-bell-slash"></i> Desistimiento </a> 
                                        </li> 
                                        <?php  } ?>
                                    </ul>

                                    <hr class="mb-3 mt-3">

                                </div>

                                <div class="Separador" id="close_bottom<?= $radicado['id']; ?>" style="display:none;"> 
                                    <div class="separadorlineT"></div>  
                                    <i class="fas fa-chevron-down Arrow2" onclick="CerrarTodo('<?= $radicado['id']; ?>')"></i>
                                    <div class="separadorlineT"></div>  
                                </div>

                                <!-- Historico -->
                                <div id="historico<?= $radicado['id']; ?>" style="display:none;">
                                    <?php include "content-historico.php"; ?>
                                </div>

                                <!-- Respuestas -->
                                <div id="respuesta<?= $radicado['id']; ?>" style="display:none;">
                                    <?php include "content-respuestas.php"; ?>
                                </div>
                            
                                <!-- Anexos --> 
                                <div id="anexos<?= $radicado['id']; ?>"  class="fadeIn" style="display:none;">
                                    <?php include "content-anexos.php"; ?>
                                </div>
                            
                                <!-- End Documents Card -->

                            </article>

                        <?php } ?>
                        <?=
                        LinkPager::widget([
                            'pagination' => $Pagination,
                        ]);
                        ?>
                    </div>
                </div>
            <?php } else { ?>
                <div class="col-lg-8 mb-5 mb-lg-0 Contenedor_Consultas" >                  
                    <?=Yii::$app->session->setFlash('info', 'Ingrese el criterio por el cual desea consultar el radicado y obtener información al respecto');?>
                </div>
            <?php } ?>

            <!-- Buscador -->
            <div class="col-lg-4 Absolute">
                <div class="blog_right_sidebar Fixed" style="top: 27%;">

                    <!-- Buscador -->
                    <h6 class="col-12 text-center busquedaT"><b> Búsqueda </b></h6>
                    <aside class="single_sidebar_widget search_widget">
                        <?php $form = ActiveForm::begin(['id' => 'form-search-radi', 'action' => Yii::$app->urlManager->createUrl('/consulta-pqrs/index'), 'method' => 'GET']); ?>
                        
                        <div class="form-group">
                            <div class="input-group mb-3 div100">
                               <?= 
                                $form->field($model_filter, 'numeroRadiRadicado')->textInput(['class' => 'input_busqueda','placeholder' => 'Búsqueda por Número de Radicado']); 
                                ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group mb-3 div100">
                               <?= 
                                $form->field($model_filter, 'asuntoRadiRadicado')->textInput(['class' => 'input_busqueda','placeholder' => 'Búscar por asunto']); 
                                ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="input-group mb-3"-->
                                <?= 
                                    $form->field($model_filter, 'idGdTrdTipoDocumental')->widget(Select2::classname(), [
                                        'data' => $list_tipos_documentales,
                                        'language' => 'es',
                                        'options' => [
                                            'placeholder' => 'Búsqueda por tipo de Solicitud.',
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]); 
                                ?>
                            </div>
                        </div>

                        <!-- <div class="form-group">
                            <div class="input-group mb-3">
                                <?php 
                                    // $form->field($model_filter, 'idCgClasificacionPqrs')->widget(Select2::classname(), [
                                    //     'data' => $list_tipos_clasificacion,
                                    //     'language' => 'es',
                                    //     'options' => [
                                    //         'placeholder' => 'Búsqueda por tipo de Clasificación.',
                                    //         'id' => 'idCgClasificacionPqrs',
                                    //     ],
                                    //     'pluginOptions' => [
                                    //         'allowClear' => false,
                                    //     ],
                                    // ]); 
                                ?>          
                            </div>
                        </div> -->

                        <div class="col-12 form-inline JustCenter">

                            <?=
                            Html::submitButton('Buscar', ['class' => ' button primary-bg text-white col-sm-4 col-12 btn-block buttonBusQ'])
                            ?>

                            <div class="col-0 col-sm-2 topM"></div>

                            <?php
                            //Html::a('Ver Todos', ['/consulta-pqrs/index'], ['class' => ' button  primary-bg text-white col-sm-4 col-12 btn-block buttonBusQ mt-0'], ['data' => ['method' => 'get',]])
                            ?>

                        </div>               


                        <?php ActiveForm::end(); ?>
                    </aside>

                </div>
            </div>

        </div>
    </div>

</section>

<!--================ VISOR DE DOCUMENTOS PDF =================-->

<!-- Visualizador Documentos-->
<button id="button_pdf" class="d-none" data-toggle="modal" data-target=".bd-example-modal-lg"></button>

<div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" >
    <div class="modal-dialog modal-lg" style="transform: translate(0, 0%);">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title">Radicado N°: <b id="radicado_id_modal"></b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <!-- <embed id="frameViewPdf" src="" frameborder = "0" width = "100%" height = "100%">          -->
            <iframe id='frameViewPdf' name="frameViewPdf" frameborder="0" style="width:100%; height:100%;" frameborder="0"></iframe>
        </div>
    </div>
</div>

<!--================ FORMULARIO DE COMENTARIOS =================-->
<div id="formularioComentarios" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style="transform: translate(0, 0%);">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title AddComment"> Agregar un nuevo comentario <b id="radicado_id_modal"></b></h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            
            <div class="col-12 form-group  pb-3 modal-body">
                <?php $form = ActiveForm::begin(['options' => ['id' => 'form-comentarios', 'class' => 'formSolicitud','enctype'=>'multipart/form-data']]); ?>

                    <!-- SECTION 1 -->
                    <section class="row form-group">
                   
                        <!-- Radicado -->
                        <?= $form->field($model_hidden, 'idRadiRadicado')->hiddenInput(['id' => 'hidden-idRadiRadicado'])->label(false); ?>

                        <!-- remitente -->
                        <div class="col-12 col-sm-12 form-holder">
                        <label class=" pb-3 mt-4">Nombre del Remitente</label>
                            <!-- $form->field($model, 'user_idCreador')->textInput(['readonly' => true, 'value' => $user_detalles]); -->
                            <input type="text" class="form-control" value="<?=$user_detalles?>" readonly="readonly">
                            <br>
                        </div>

                        <!-- Observacion -->
                        <div class="col-12 col-sm-12 form-holder">
                            <label class="pb-3">Ingrese una observación *</label>
                            <?= $form->field($model_anexos, 'observacion')->textarea(['class' => 'form-text-area-new','rows' => '4'])    ?>
                        </div>
                    </section>

                    <!-- SECTION 2 -->                         
                    <section class="row form-group">
                        <!-- Anexos -->
                        <div class=" col-12 col-sm-12 form-holder ">
                            <label for="">Anexos </label>
                        </div>
                        <div class="col-12 col-sm-12 form-holder  mt-4 required">
                            <?=
                                FileInput::widget([
                                    'model' => $model_anexos,
                                    'attribute' => 'anexo[]',
                                    'options' => ['multiple' => true],
                                    'pluginOptions' => [ 
                                        'allowedFileExtensions' => $exceptionFile,
                                        'uploadClass' => 'd-none',
                                        'maxFileSize' => 20000
                                    ]
                                ]);
                            ?> 
                            <label id="messange-error" class="div-has-error-anex"> </label> 
                        </div>
                    </section>

                    <div class="form-group col-12 text-center">
                        <?= Html::submitButton('Enviar Solicitud', ['id' => 'SubmitButton','class' => 'btn btn-primary-orfeo center-block p-3 mt-4']) ?>
                    </div>
                            
                <?php ActiveForm::end(); ?>
            </div>                          
        </div>
    </div>
</div>

<script>

    $(document).ready(function (){

        $('#formularioComentarios').on('beforeSubmit', 'form#form-comentarios', function () {

            console.log($(this).serialize());
            $('#formularioComentarios').modal('hide');

            $.ajax({
                url: '<?= Yii::$app->urlManager->createUrl('/consulta-pqrs/add-comment') ?>',
                type: 'post',
                data: new FormData(this),// $(this).serialize(),
                contentType: false,
                cache: false,
                processData:false,
                success: function (response)
                {
                    response = JSON.parse(response);

                    // Muestra la alerta flotante
                    $('#alert-class').show();
                    $('#alert-class').addClass(response['class']);
                     // Agrega estilos al texto
                    $('#alert-msj').addClass(response['class-msj']);
                    $('#alert-msj').html(response['msj']);

                    // Actualizar div del radicado
                    reloadw($('#hidden-idRadiRadicado').val());

                    setTimeout(() => {
                        $('#alert-class').hide();
                    }, 5000);

                },
                error: function ()
                {
                    console.log('internal server error');
                }
            });

            return false;
        });

    });
     
    function open_historico(id, element) {
        close(id);
        Focus(element)
        $("#historico"+id).show();
    }

    function open_resultados(id, element) {

        close(id);
        Focus(element)
        $("#respuesta" + id).show();
    }

    function open_anexos(id, element) {

        close(id);
        Focus(element)
        $("#anexos" + id).show();

    }

    function open_comentarios(id, element) {

        $("#form-comentarios")[0].reset();

        close(id);
        Focus(element)
        $('#hidden-idRadiRadicado').val(id);
        $('#formularioComentarios').modal('show');

    }

    function open_desistimiento(id, numeroradi) {

        var dialog = bootbox.dialog({
            title: '<b class="alerta-informativa" > Estimado usuario  </b>',
            message: $('<label>¿Esta seguro que desea desistir de la PQRS?. Esto implica que la entidad ya no tramitará o gestionará su trámite.<label><textarea class="form-control" placeholder="Ingrese la observacion" id="observacionDesistimiento" maxlength="500"></textarea><span id="messageDesistimiento" style="display: none;" class="text-danger">Este campo es obligatorio<span> '),
            size: 'large',
            buttons: {
                cancel: {
                    label: "Cerrar",
                    className: 'btn-primary-orfeo',
                    callback: function(){
                        
                    }
                },
                ok: {
                    label: "Confirmar",
                    className: 'btn-primary-orfeo',
                    callback: function(){

                        var observacionDesistimiento = $("#observacionDesistimiento").val();

                        if (observacionDesistimiento.trim() == '') {
                            $("#messageDesistimiento").show();
                            return false;
                        } else {
                            $.ajax({
                                url: '<?= Yii::$app->urlManager->createUrl('/consulta-pqrs/desistimiento-radicado') ?>',
                                type: 'post',
                                data: {idRadiRadicado: id, observacionDesistimiento: observacionDesistimiento},
                                success: function (response)
                                {
                                    response = JSON.parse(response);

                                    // Muestra la alerta flotante
                                    $('#alert-class').show();
                                    $('#alert-class').addClass(response['class']);
                                    // Agrega estilos al texto
                                    $('#alert-msj').addClass(response['class-msj']);
                                    $('#alert-msj').html(response['msj']);

                                    //Remover agregar comentario y desistir
                                    removeDesist(id);

                                    // Actualizar div del radicado
                                    reloadw(id);
                                    $('#status'+id).html('Finalizado');

                                    setTimeout(() => {
                                        $('#alert-class').hide();
                                    }, 5000);


                                },
                                error: function (err)
                                {
                                    console.log('internal server error'+err);
                                }
                            });
                        }

                    }
                }
            }
        });

    }

    function close(id) {

        $('#OpcionesR' + id).find("a").css({"color": "#666", "font-weight": "normal"});

        $('#close_bottom' + id).show();
        $('#historico' + id).hide();
        $('#respuesta' + id).hide();
        $('#anexos' + id).hide();
    }

    function Focus(element) {
        $(element).find("a").css({"color": "#365c8a", "font-weight": "bold"});
    }

    function CerrarTodo(id) {

        $('#OpcionesR' + id).find("a").css({"color": "#666", "font-weight": "normal"});
        $('#close_bottom' + id).hide();
        $('#historico' + id).hide();
        $('#respuesta' + id).hide();
        $('#anexos' + id).hide();
        $('#comentarios' + id).hide();
        $('#desistir' + id).hide();
    }

    function reloadw(id_radicado) {
        
        $('#reload' + id_radicado).hide();
        $('#loading' + id_radicado).show();

        $('#mensajeLoad' + id_radicado).html("Cargando...");
        

        reload_historico(id_radicado);
        reload_respuesta(id_radicado);
        reload_anexos(id_radicado);

    }

    function removeDesist(id){
        
        $('#OpcionesR' + id).find('#li-open-comment').remove();
        $('#OpcionesR' + id).find('#li-open-desist').remove();

    }

    function reload_historico(id) {

        $("#historico"+id).load("<?= Yii::$app->urlManager->createAbsoluteUrl(['consulta-pqrs/reload-historico', 'id' => '']) ?>" + id, 
            function (response, status, xhr) {  

                setTimeout(() => {

                    $('#loading' + id).hide();
                    $('#complete' + id).show();
                    $('#mensajeComplete' + id).html("Actualizado");

                    tiempo_espera(id);

                }, 2000);
            }
        );

    }

    function reload_respuesta(id) {

        $("#respuesta"+id).load("<?= Yii::$app->urlManager->createAbsoluteUrl(['consulta-pqrs/reload-respuestas', 'id' => '']) ?>" + id, 
            function (response, status, xhr) {
                setTimeout(() => {

                    $('#loading' + id).hide();
                    $('#complete' + id).show();
                    $('#mensajeComplete' + id).html("Actualizado");

                    tiempo_espera(id);

                }, 2000);
            }   
        );


    }

    function reload_anexos(id) {

        $("#anexos"+id).load("<?= Yii::$app->urlManager->createAbsoluteUrl(['consulta-pqrs/reload-anexos', 'id' => '']) ?>" + id, 
            function (response, status, xhr) {
                setTimeout(() => {
                    
                    $('#loading' + id).hide();
                    $('#complete' + id).show();
                    $('#mensajeComplete' + id).html("Actualizado");

                    tiempo_espera(id);

                }, 2000);
            }
        );
    }

    function tiempo_espera(id) {

        $('#mensajeLoad' + id).html("");

        setTimeout(() => {

            $('#mensajeLoad' + id).html("Actualizar");
            $('#reload' + id).show();
            $('#loading' + id).hide();

            $('#complete' + id).hide();
            $('#mensajeComplete' + id).html("");

        }, 5000);
    }

    function informacion_envio(id) {

        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/consulta/detallesenvio') ?>',
            type: 'post',
            data: {'radi_nume_radi': id},
            success: function (response){

                $('#open_modal_envio').click();
                // console.log(response);

                if (response.length == 0) {

                } else {
                    $('#Tipo_de_envio').html(response.tipo_envio);
                    $('#Guia_envio').html(response.guia_num);
                    $('#Observacion_envio').html(response.observacion);
                }
            },
            error: function (){
                console.log('internal server error');
            }
        });
    }

    /**
     * Descarga el archivo que llega en base64
     * @param file el  en base 64
     * @param nameDownload nombre del archivo
     */

    function downloadFile(id,typeDoc){

        var data = {
            'id': id,
            'downloadType': typeDoc
        };

        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/consulta-pqrs/download-file') ?>',
            type: 'post',
            data: data,
            success: function (response)
            {   
                response = JSON.parse(response);

                if(response['status']){

                    var file = response['file'];
                    var downloadLink = document.createElement('a');

                    downloadLink.href = `data:application/pdf;base64,${file['datafile']}`;
                    downloadLink.download = file['fileName'];
                    downloadLink.click();

                }else{

                    var dialog = bootbox.dialog({
                        title: '<b class="alerta-informativa"> Error de Validacion </b>',
                        message: response['msj'],
                        size: 'large',
                        buttons: {
                            // cancel: {
                            //     label: "NO ACEPTO",
                            //     className: 'btn-primary-orfeo',
                            //     callback: function(){
                            //         window.location.href = '<?= Yii::$app->urlManager->createUrl('site/index') ?>';
                            //     }
                            // },
                            ok: {
                                label: "OK",
                                className: 'btn-primary-orfeo',
                                callback: function(){

                                }
                            }
                        }
                    });

                }
             

            },
            error: function ()
            {
                console.log('internal server error');
            }
        });
        
    }

    function viewFile(id,typeDoc){

        var data = {
            'id': id,
            'downloadType': typeDoc
        };

        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/consulta-pqrs/download-file') ?>',
            type: 'post',
            data: data,
            success: function (response)
            {   
                response = JSON.parse(response);

                // console.log(response);

                if(response['status']){

                    var file = response['file'];

                    $('#button_pdf').click();
                    $('#radicado_id_modal').html(response['numeroRadiRadicado']);
                    
                    // document.getElementById('frameViewPdf').setAttribute('src', "data:application/pdf;base64,"+encodeURI(file['datafile']));
                    loadPdf(file['datafile']);

                }else{

                    var dialog = bootbox.dialog({
                        title: '<b class="alerta-informativa"> Error de Validacion </b>',
                        message: response['msj'],
                        size: 'large',
                        buttons: {
                            // cancel: {
                            //     label: "NO ACEPTO",
                            //     className: 'btn-primary-orfeo',
                            //     callback: function(){
                            //         window.location.href = '<?php //  Yii::$app->urlManager->createUrl('site/index') ?>';
                            //     }
                            // },
                            ok: {
                                label: "OK",
                                className: 'btn-primary-orfeo',
                                callback: function(){

                                }
                            }
                        }
                    });

                }
             

            },
            error: function ()
            {
                console.log('internal server error');
            }
        });
    }

    function convertDataURIToBinary(base64) {
        var raw = window.atob(base64);
        var rawLength = raw.length;
        var array = new Uint8Array(new ArrayBuffer(rawLength));

        for (var i = 0; i < rawLength; i++) {
            array[i] = raw.charCodeAt(i);
        }
        return array;
    }

    function loadPdf(base64Document) {
        var pdfAsDataUri = base64Document;
        var pdfAsArray = convertDataURIToBinary(pdfAsDataUri);
        var url = '<?= Yii::$app->request->baseUrl ?>' + '/pdfViewJs/viewer.html?file=';

        var binaryData = [];
        binaryData.push(pdfAsArray);
        var dataPdf = window.URL.createObjectURL(new Blob(binaryData, {type: "application/pdf"}))
        document.getElementById('frameViewPdf').setAttribute('src', url + encodeURIComponent(dataPdf));  
    }

</script>
