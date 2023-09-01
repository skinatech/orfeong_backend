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
use vova07\imperavi\Widget;
use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

$this->title = 'Registro Peticiones, Quejas, Reclamos, Sugerencias, Solicitud de Información pública, Denuncias y Trámites Prestacionales';
$this->params['breadcrumbs'][] = $this->title;
?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">

<br />
<div style="margin-left: 12%;">
    Costos asociados a la respuesta :
    <a class="boxed-btn registropqr btn-primary-orfeo" href="https://www.fonprecon.gov.co/recursos_user/resolucion-costos.pdf" target="_black">Revise los costos asociados a la información solicitada</a>
</div><br>
<div class="wrapper" id="contenedor-form">
    <?php
    $form = ActiveForm::begin(['options' =>
    [
        'id' => 'formPqrsTipoTramite',
        'class' => 'formSolicitud',
        'enctype'=>'multipart/form-data',
        'action' => 'none'
    ]]); ?>
<div class="alert alert-primary" role="alert">
  Seleccione por favor el tramite que desea registrar en la entidad
</div>
        <section class="section1">
            <div class="row form-group">
                <div class="col-12 col-sm-12 form-holder form-tipo-tramite">
                    <label class="pb-3">Tipo de trámite <span style="color:red;">*</span></label>
                    <?= $form->field($model_registro_pqrs, 'tipoTramite')->widget(Select2::classname(), [
                            'data' => $list_formulario_pqrs,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un tipo de trámite.',
                                'id' => 'tipoTramite',
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>
            </div>
        </section>
    <?php ActiveForm::end(); ?>

    <?php
    $form = ActiveForm::begin(['options' =>
    [
        'id' => 'form-pqrs',
        'class' => 'formSolicitud',
        'enctype'=>'multipart/form-data',
        'action' => 'none'
    ]]); ?>

        <div class="section-default hide-section">

            <div class="row form-group">
                <div class="col-4 col-sm-4 form-holder">
                    <label class=" pb-3">Nombre del Remitente</label>
                    <input type="hidden" name="idRemitente" id="idRemitente" value="<?=Yii::$app->user->identity->id?>" />
                    <?= $form->field($model_radicado, 'nombreRemitente')->textInput(['readonly' => true, 'value' => $user_detalles])->label(''); ?>
                </div>
                <div class="col-4 col-sm-4 form-holder">
                    <label class=" pb-3">Tipo de Solicitud <span style="color:red;">*</span></label>
                    <?= $form->field($model_radicado, 'idTrdTipoDocumental')->widget(Select2::classname(), [
                            'data' => $list_tipos_documentales,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un tipo de solicitud.',
                                'id' => 'idTrdTipoDocumental'
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>
                <div class="col-4 col-sm-4 form-holder">
                        <label class="pb-3">Clasificación de la solicitud <span style="color:red;">*</span></label>
                        <?= $form->field($model_registro_pqrs, 'tipoClasificacion')->widget(Select2::classname(), [
                                'data' => $listCalsificacion,
                                'language' => 'es',
                                'options' => [
                                    'placeholder' => 'Selecciona una clasificación de solicitud.',
                                    'id' => 'tipoClasificacion'
                                ],
                                'pluginOptions' => [
                                    'allowClear' => false,
                                ],
                            ])->label('');
                        ?>
                    </div>
                <!-- asuntoRadiRadicado -->
                <div class="col-12 col-sm-12 form-holder"-->
                    <label class="pb-3">Asunto <span style="color:red;">*</span></label>
                    <?= $form->field($model_radicado, 'asuntoRadiRadicado')->textInput(['maxlength' => true, 'required' => true])->label(''); ?>
                </div>
            </div>

            <div class="row form-group pt-3 pb-3">
                <div class=" col-12 col-sm-12 form-holder pb-3">
                    <label for="">Descripción de los Hechos <span style="color:red;">*</span>
                        <span onclick="CuerpoDocu()">
                            <i class="fas fa-exclamation-circle"></i>
                        </span>
                    </label>
                </div>

                <div class="col-12 col-sm-12 form-holder  mt-4">
                    <?= $form->field($model_radicado, 'observacionRadiRadicado')->widget(Widget::className(), [
                            'settings' => [
                                'required' => true,
                                'lang' => 'es',
                                'minHeight' => 100,
                                'plugins' => [
                                    'clips',
                                    'fullscreen',
                                ],
                                'clips' => [
                                    ['Lorem ipsum...', 'Lorem...'],
                                    ['red', '<span class="label-red">red</span>'],
                                    ['green', '<span class="label-green">green</span>'],
                                    ['blue', '<span class="label-blue">blue</span>'],
                                ],
                            ],
                        ])->label('');
                    ?>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-12 col-sm-6 form-holder">
                    <label class="pb-3">Agregar Anexos</label>
                    <?= $form->field($model_anexos, 'agregar')->widget(Select2::classname(), [
                            'data' => [0 => 'NO', 10 => 'SI'],
                            'language' => 'es',
                            'options' => [
                                'id' => 'idAgregarAnexo', 
                                'onchange' => 'Anexos()',
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ]);
                    ?>
                </div>

                <!-- <div class="col-12 col-sm-6 form-holder">
                    <label class=" pb-3">Confidencialidad del documento</label>
                    <?php /*echo $form->field($model_anexos, 'publico')->widget(Select2::classname(), [
                            'data' => [0 => 'Privado', 10 => 'Publico'],
                            'value' => false,
                            'language' => 'es',
                            'options' => [
                                'readonly' => true,
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ]);*/
                    ?>
                </div> -->
            </div>

            <div id="AnexosList" class="row form-group  pb-3" style="display:none;">
                <div class=" col-12 col-sm-12 form-holder ">
                    <label for="">Anexos de la Solicitud</label>
                    <label for=""><b><h5 style="color: #bb0000;">Puedes anexar soportes hasta un tamaño maximo de 20 MB</h4></b></label>                    <br>
                    <h5 class="autoriza pb-5 " style="color: #bb0000;"> Señor usuario si usted va a seleccionar más de un archivo, estos deben ser seleccionados al mismo tiempo, de lo contrario se ira reemplazando a medida que vaya cargando los documentos</h5>                
                </div>
                <div class="col-12 col-sm-12 form-holder mt-4 required">
                    <?= FileInput::widget([
                            'model' => $model_anexos,
                            'attribute' => 'anexo[]',
                            'options' => ['id' => 'anexo','multiple' => true,'required'],
                            'pluginOptions' => [
                                'allowedFileExtensions' => $exceptionFile,
                                'uploadClass' => 'd-none',
                                'maxFileSize' => 20000
                            ]
                        ]);
                    ?>
                    <label id="messange-error" class="div-has-error-anex"> </label>
                </div>
            </div>

            <?php
            if (yii::$app->user->identity['username'] != Yii::$app->params['userAnonimoPQRS']) {
            ?>
                <div class="row form-group col-12 pt-4">
                    <h5 class="autoriza pb-4 "> Nota: Autorizo al MUNICIPIO DE SESQUILE para que me notifiquen electrónicamente al correo registrado, la respuesta a la presente PQR. En caso de seleccionar <b>NO</b> se enviará la respuesta a la dirección de correspondencia registrada.</h5><br>
                    <input type="radio" id="no" name="autorizanotificaciones" class="autorizanotificaciones mr-2" required value="no" ><label class="">No</label><br>
                    <input type="radio" id="si" name="autorizanotificaciones" class="autorizanotificaciones ml-5" required value="si" checked><label class="ml-2">Si</label><br> 
                </div>
            <?php
            }
            ?>

            <div id="NotificacionesCorreo" class="row form-group" style="display:none;">
                <?php
                if (yii::$app->user->identity['username'] == Yii::$app->params['userAnonimoPQRS']) {
                ?>
                    <div id="notificacionCorreo" class="col-12">
                        <div class="row form-group form-holder">
                            <!-- Pregunta Notificaciones -->
                            <div class="col-12 col-sm-4">
                                <input type="hidden" name="mediorespuesta" id="mediorespuesta" value="1"/>
                                <?= $form->field($model_radicado, 'autorizacionRadiRadicados')->widget(Select2::classname(), [
                                        'data' => ['10' => 'Correo Electrónico', '0' => 'Correo Físico'],
                                        'value' => '',
                                        'options' => [
                                            'id' => 'autorizacionRadiRadicados',
                                            'placeholder' => 'Seleccione una opción...'
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]);
                                ?>
                            </div>
                            <!-- Solicitar Correo Electronico si el usuario no registro uno -->
                            <div id="SolicitarCorreo" class="col-12 col-sm-8 form-holder" style="display:none;">
                                <label>
                                    <strong>Introduzca el correo electrónico por el que se enviaran de notificaciones</strong>
                                </label>
                                <input type="email" name="RadiDetallePqrsAnonimo[correoElectronicoCliente]" class="form-control" id="correoElectronicoCliente" value="">
                                <p id="messange-error-email" class="div-has-error-anex"></p>
                            </div>

                        </div>
                    </div>

                    <!-- Si el usuario indica que no autoriza el envio de correo se habilita para que se ingrese una dirección -->
                    <div id="notificacionFisica" style="display:none" class="col-12">
                        <hr />
                        <div class="row form-group form-holder">
                            <input type="hidden" name="mediorespuesta" id="mediorespuesta" value="0"/>
                            &nbsp;&nbsp;&nbsp;<h5 class="autoriza pb-5 ">A continuación introduzca la dirección de correspondencia a través de la cual autoriza el envío de notificaciones</h5>
                            <br />
                            <br />
                            <!-- País -->
                            <div class="col-12 col-sm-4 form-holder">
                                <label class=" pb-3">País</label>
                                <?= $form->field($model_anonima, 'idNivelGeografico1')->widget(Select2::classname(), [
                                        'data' => $list_paises,
                                        'value' => $model_anonima->idNivelGeografico1,
                                        'language' => 'es',
                                        'options' => [
                                            'placeholder' => 'Seleccione un país', 'id' => 'nivelGeografico1', 'value' => 1,
                                            'onchange' =>
                                            '
                                                $.post( "' . urldecode(Yii::$app->urlManager->createUrl('/site/nivel-geografico2&id=')) . '"+$(this).val(), function( data ) {
                                                $("select#Dep_id").html(data);
                                                $("#City_id option").remove();
                                                });
                                            '
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]);
                                ?>
                            </div>
                            <!-- Departamento -->
                            <div class="col-12 col-sm-4 form-holder">
                                <label class=" pb-3">Departamento</label>
                                <?= $form->field($model_anonima, 'idNivelGeografico2')->widget(Select2::classname(), [
                                        'data' => $list_departamentos,
                                        'value' => $model_anonima->idNivelGeografico2,
                                        'language' => 'es',
                                        'options' => [
                                            'placeholder' => 'Seleccione un departamento', 'id' => 'Dep_id', 'value' => 1,
                                            'onchange' =>
                                            '
                                                $.post( "' . urldecode(Yii::$app->urlManager->createUrl('/site/nivel-geografico3&id=')) . '"+$(this).val(), function( data ) {
                                                $( "select#City_id" ).html( data );
                                                });
                                            '
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]);
                                ?>
                            </div>
                            <!-- Ciudad - Municipio -->
                            <div class="col-12 col-sm-4 form-holder">
                                <label class=" pb-3">Ciudad - Municipio</label>
                                <?= $form->field($model_anonima, 'idNivelGeografico3')->widget(DepDrop::classname(), [
                                        'type' => DepDrop::TYPE_SELECT2,
                                        'data' => $list_ciudades,
                                        'value' => $model_anonima->idNivelGeografico3,
                                        'language' => 'es',
                                        'options' => [ 'id' => 'City_id', ],
                                        'pluginOptions'=> [
                                            'depends'=>['Dep_id'],
                                            'placeholder'=>'Seleccione una ciudad/municipio...',
                                            'url'=>Url::to(['/site/nivel-geografico-3'])
                                        ]
                                    ]);
                                ?>
                            </div>
                        </div>
                        <!--
                        Se relacionan campos del modelo de radicado a componentes del formulario exclusivamente
                        para poder referenciar los campos y asi poder insertar en la tabla de sgd_ciu_ciudadano los datos
                        de la dirección de correspondencia
                        -->
                        <div class="row form-group">
                            <div class="col-12 col-sm-12 form-holder">
                                <label for="radi_dire_corr">Dirección</label>
                            </div>
                            <div class="col-4">
                                <?= $form->field($model_anonima, 'dirCam1')->widget(Select2::classname(), [
                                        'data' => ['Calle' => 'Calle', 'Carrera' => 'Carrera', 'Diagonal' => 'Diagonal', 'Transversal' => 'Transversal', 'Avenida' => 'Avenida'],
                                        'value' => $model_anonima->dirCam1,
                                        'language' => 'es',
                                        'options' => [
                                            'id' => 'dirección',
                                            'placeholder' => 'Seleccione una',
                                            'class' => 'correspondencia'
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]);
                                ?>
                            </div>

                            <div class="col-2 p-0 correspondenia"> <?= $form->field($model_anonima, 'dirCam2')->textInput(['maxlength' => true, 'id'=>'radicado-radi_arch1']) ?> </div>
                            <div class="col-1 p-0 correspondenia"> <?= $form->field($model_anonima, 'dirCam3')->textInput(['readonly' => true, 'value' => '#']) ?></div>
                            <div class="col-1 p-0 correspondenia"> <?= $form->field($model_anonima, 'dirCam4')->textInput(['maxlength' => true]) ?> </div>
                            <div class="col-1 p-0 correspondenia"> <?= $form->field($model_anonima, 'dirCam5')->textInput(['maxlength' => true]) ?> </div>

                            <div class="col-3">
                                <?= $form->field($model_anonima, 'dirCam6')->widget(Select2::classname(), [
                                        'data' => ['Norte' => 'Norte', 'Sur' => 'Sur', 'Este' => 'Este', 'Oeste' => 'Oeste'],
                                        'language' => 'es',
                                        'options' => [
                                            'placeholder' => 'Seleccione una',
                                            'class' => 'correspondenia'
                                        ],
                                        'pluginOptions' => [
                                            'allowClear' => false,
                                        ],
                                    ]);
                                ?>
                            </div>
                        </div>
                    </div>
                <?php
                } else {
                ?>
                    <div class="col-6">
                        <input type="email" name="correoElectronicoCliente" class="form-control" id="correoElectronicoCliente" value="<?=$correoRemitente?>">
                        <p id="messange-error-email" class="div-has-error-anex"></p>
                    </div>
                <?php
                }
                ?>
            </div>

            <div class="pt-4 mt-3">
                <?php //echo  $form->field($model_radicado, 'reCaptcha')->widget(\himiklab\yii2\recaptcha\ReCaptcha2::className()) ?>
                <?php //echo $form->field($model_radicado, 'reCaptcha')->widget(\kekaadrenalin\recaptcha3\ReCaptchaWidget::class) ?>
            </div>

            <div class="row">
                <div class="col-6">
                    <h4 class="autoriza pb-4 "> El usuario acepta expresamente que la notificación de la decisión se hará vía electrónica de conformidad a la ley 1437 de 2011, la cual se realizará al correo electrónico suministrado por el solicitante **</h4><br>
                    <input type="checkbox" name="aceptar_terminos" required = "true"/>
                    <a href="https://www.sesquile-cundinamarca.gov.co/Style%20Library/PDFs/Tratamiento%20de%20Datos%20Personales%20-%20Habeas%20Data%20-%20Ley%201581%20de%202012.pdf" target="">Acepto la politica de tratamientos de datos personales</a>
                </div>
                <div class="col-6">
                    <h4 class="autoriza pb-4 "> A través de este documento, se le comunica al titular la información que se recaba respecto de su persona y la finalidad de su obtención, asi como la posibilidad de ejercer los derechos de acceso, rectificación, cancelación y oposición y la forma de ejercerlos **</h4><br>
                    <input type="checkbox" name="aceptar_terminos_privacidad" required = "true"/>
                    <a href="https://www.sesquile-cundinamarca.gov.co/Paginas/Politicas-de-Privacidad-y-Condiciones-de-Uso.aspx" target="">Acepto las condiciones de Uso y las Politicas de privacidad</a>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <?= Html::submitButton('Enviar Solicitud', ['id' => 'SubmitButton', 'style' => 'display: block;','class' => 'btn btn-primary-orfeo center-block p-3 mt-4']) ?>
                </div>
            </div>

        </div>

        <input type="hidden" name="envioData" value="true" />
        <input type="hidden" name="RegistroPqrs[tipoTramite]" value="<?= Yii::$app->params['formsRegistroPqrsText']['actionIndexDefault']; ?>" />

    <?php ActiveForm::end(); ?>
</div>

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

<script>
    $(document).ready(function () {

        $("#tipoTramite option[value='<?= Yii::$app->params['formsRegistroPqrsText']['actionIndexDefault']; ?>']").attr("selected", true);

        $('#tipoTramite').on('change', function() {
            $('#formPqrsTipoTramite').submit();
        });

        $('#anexo').change(function () {
            $('#AnexosList').removeClass('has-success');
            $('#AnexosList').removeClass('has-error');
            $('#messange-error').html('');
        });

        if($('input:radio[name=autorizanotificaciones]:checked').val() == 'si'){
            $('#NotificacionesCorreo').show();
            $('#NotificacionesCorreo').prop('required',true);
            $('#autorizacionRadiRadicados').prop('required',true);
            if ($('#autorizacionRadiRadicados').val() === '') {
                $('.field-autorizacionRadiRadicados').removeClass('has-success');
                $('.field-autorizacionRadiRadicados').addClass('has-error');
                $('#messange-error-autorizacionRadiRadicados').html('Este campo no puede estar vacío.');
            } 
            //$('#radi_dire_corr').prop('required',true);
        }

        $('#fileinput-remove-button').change(function () {
            $('#AnexosList').removeClass('has-success');
            $('#AnexosList').addClass('has-error');
            $('#messange-error').html('Este campo no puede estar vacío.');
        });

        $('.autorizanotificaciones').change(function (){
            if($('input:radio[name=autorizanotificaciones]:checked').val() == 'si'){
                $('#NotificacionesCorreo').show();
                $('#NotificacionesCorreo').prop('required',true);
                $('#autorizacionRadiRadicados').prop('required',true);
                if ($('#autorizacionRadiRadicados').val() === '') {
                        $('.field-autorizacionRadiRadicados').removeClass('has-success');
                        $('.field-autorizacionRadiRadicados').addClass('has-error');
                        $('#messange-error-autorizacionRadiRadicados').html('Este campo no puede estar vacío.');
                } 
                //$('#radi_dire_corr').prop('required',true);
            }else{
                $('#NotificacionesCorreo').hide();
                $('#correoElectronicoCliente').prop('required',false);
                $('#autorizacionRadiRadicados').val(null);
                $('#Dep_id').prop('required',false);
                $('#City_id').prop('required',false);
                $('#autorizacionRadiRadicados').prop('required',false);
                // $('#radi_dire_corr').prop('required',false);
            }
        });

        $('#autorizacionRadiRadicados').change(function () {

            // buscar medio para dar respuestas al usuario
            $.ajax({
                url: '<?= Yii::$app->urlManager->createUrl('/registro-pqrs/seguimiento-via') ?>',
                type: 'post',
                data: {'seguimientoVia': $('#autorizacionRadiRadicados').val()},
                success: function (response)
                {
                    if ($('#autorizacionRadiRadicados').val() == 10) {
                        $('#notificacionFisica').hide();
                        $('#SolicitarCorreo').show();
                        $('#correoElectronicoCliente').prop('required',true);
                    } else if($('#autorizacionRadiRadicados').val() == 0){
                        $('#SolicitarCorreo').hide();
                        $('#notificacionFisica').show();
                        $('#Dep_id').prop('required',true);
                        $('#City_id').prop('required',true);
                        if ($('#Dep_id').val() === '') {
                        $('.field-Dep_id').removeClass('has-success');
                        $('.field-Dep_id').addClass('has-error');
                        $('#messange-error-Dep_id').html('Este campo no puede estar vacío.');
                        } 
                        if ($('#City_id').val() === '') {
                        $('.field-City_id').removeClass('has-success');
                        $('.field-City_id').addClass('has-error');
                        $('#messange-error-City_id').html('Este campo no puede estar vacío.');
                        } 
                    } else {
                        $('#notificacionFisica').hide();
                        $('#SolicitarCorreo').hide();
                        $('#Dep_id').prop('required',false);
                        $('#City_id').prop('required',false);
                        $('#correoElectronicoCliente').prop('required',false);
                    }

                },
                error: function ()
                {
                    console.log('internal server error');
                }
            });
        });

        $('#contenedor-form').on('beforeSubmit', 'form#form-pqrs', function () {

            if ($('#idAgregarAnexo').val() == 10) {

                var Camposrequeridos = $(".file-input");

                if (Camposrequeridos.hasClass('file-input-ajax-new')) {

                    $('#AnexosList').removeClass('has-success');
                    $('#AnexosList').addClass('has-error');
                    $('#messange-error').html('Este campo no puede estar vacío.');

                    return false;

                }

                if (Camposrequeridos.hasClass('has-error')) {

                    $('#AnexosList').removeClass('has-success');
                    $('#AnexosList').addClass('has-error');
                    $('#messange-error').html('Formato de archivo incorrecto.');

                    return false;

                } 

                document.getElementById('form-pqrs').submit();
            }

            if ($('#idAgregarAnexo').val() == 0) {               
                document.getElementById('form-pqrs').submit();
            }

            return false;
        });

        //alertaInformativa();

        $('a[href*=\\#]').click(function() {
    
            if (location.pathname.replace(/^\//,'') == this.pathname.replace(/^\//,'')
                && location.hostname == this.hostname) {

                    var $target = $(this.hash);
                    $target = $target.length && $target || $('[name=' + this.hash.slice(1) +']');

                    if ($target.length) {

                        var targetOffset = $target.offset().top;
                        $('html,body').animate({scrollTop: targetOffset}, 1000);
                        return false;
                }
            }
        });

        $('#SubmitButton').click(function (){
            formValidacion();
        });
    });

    $('#email').on('change', function () {

        if ($('#email').val() === '') {

            $('.field-email').removeClass('has-success');
            $('.field-email').addClass('has-error');
            $('#messange-error-email').html('Este campo no puede estar vacío.');
        } else {

            $('.field-email').addClass('has-success');
            $('.field-email').removeClass('has-error');
            $('#messange-error-email').html('');
        }

    });

    function formValidacion() {

        setTimeout(() => {

            /* seleccionar las etiquetas "div" con clase="required" dentro de la clase "form1" */
            var Camposrequeridos = $(".form1 div.required");
            var verificador = 0;

            for (let index = 0; index < Camposrequeridos.length; index++) {
                // busca de los campos requeridos que se encuentre en estado has-success (class)
                if (Camposrequeridos[index].className.includes('has-success')) {
                    verificador = 1;
                } else {
                    verificador = 0;
                    break;
                }
            }

            if (verificador == 1) {
                // inactiva el boton de validacion del primer formulario
                $("#button-form1").hide();  
                // agrega el boton de completar formulario
                $("#SubmitButton").show();
                // muestra el apartado de notificaciones
                $('#NotificacionesCorreo').show();
                $("#nivelGeografico1 option[value=1]").attr("selected", true);
                $('#downScroll').click(); // scroll down
            }

        }, 500);

    }

    function Anexos() {
        if ($('#idAgregarAnexo').val() == 10) {
            $('#AnexosList').show();
        } else {
            $('#AnexosList').hide();
        }
    }

    function alertaInformativa(){

        var dialog = bootbox.dialog({
            title: '<b class="alerta-informativa" > Ley 1755 de 2015: </b>',
            message: "Ley 1755 de 2015: Por medio de la cual se regula el Derecho Fundamental de Petición y se sustituye un título del Código de Procedimiento Administrativo y de lo Contencioso Administrativo. ",
            size: 'large',
            buttons: {
                // cancel: {
                //     label: "NO ACEPTO",
                //     className: 'btn-primary-orfeo',
                //     callback: function(){
                //         window.location.href = '<= //  Yii::$app->urlManager->createUrl('site/index') ?>';
                //     }
                // },
                ok: {
                    label: "ACEPTO",
                    className: 'btn-primary-orfeo',
                    callback: function(){

                    }
                }
            }
        });
    }

    function CuerpoDocu() {

        var dialog = bootbox.dialog({
            title: '<b class="alerta-informativa"> Descripcion de los hechos </b>',
            message: "Solo se debe ingresar la información de su solicitud, sin encabezado ni datos del remitente.",
            size: 'small',
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

    function previsualizacion() {

        var data = {
            nombreremitente: $('#radiradicadosform-nombreremitente').val(),
            idTrdTipoDocumental: $('#idTrdTipoDocumental').val(),
            asuntoradiradicado: $('#radiradicadosform-asuntoradiradicado').val(),
            observacionradiradicado: $('#radiradicadosform-observacionradiradicado').val(),
            idAgregarAnexo: $('#idAgregarAnexo').val(),
            idRemitente: $('#idRemitente').val(),
            autorizanotificaciones: $('#autorizanotificaciones').val(),
            autorizacionRadiRadicados: $('#autorizacionRadiRadicados').val(),
            idNivelGeografico1: $('#nivelGeografico1').val(),
            idNivelGeografico2: $('#Dep_id').val(),
            idNivelGeografico3: $('#City_id').val(),
            direccion: $('#dirección').val(),
            radicado_radi_arch1: $('#radicado-radi_arch1').val(),
            radidetallepqrsanonimo_dircam4: $('#radidetallepqrsanonimo-dircam4').val(),
            radidetallepqrsanonimo_dircam5: $('#radidetallepqrsanonimo-dircam5').val(),
            radidetallepqrsanonimo_dircam6: $('#radidetallepqrsanonimo-dircam6').val(),
            correoElectronicoCliente: $('#correoElectronicoCliente').val(),
        };

        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/site/previsualizacion-pdf') ?>',
            type: 'post',
            data: data,
            success: function (response)
            {
                response = JSON.parse(response);

                if(response['status']){

                    var file = response['file'];

                    $('#button_pdf').click();
                    $('#radicado_id_modal').html(response['numeroRadiRadicado']);
                    loadPdf(file['datafile']);

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
