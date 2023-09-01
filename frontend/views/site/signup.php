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
/* @var $this yii\web\View */
/* @var $form yii\bootstrap\ActiveForm */
/* @var $model \frontend\models\SignupForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\select2\Select2;
use kartik\date\DatePicker;
use kartik\depdrop\DepDrop;
use yii\helpers\Url;

// use yii\jui\DatePicker;
$this->title = 'Regístrate';
$this->params['breadcrumbs'][] = $this->title;

$alertaBienvenida = 'Por medio de este sistema de la Alcaldia del Municipio de Sésquile, usted podrá presentar peticiones, quejas, reclamos, denuncias, sugerencias o felicitaciones por motivos de interés general o particular. Como usuario registrado o usuario anónimo, la entidad atenderá su solicitud, por lo que requerimos tenga en cuenta los siguientes puntos antes de registrarla:
<br><br>
<b>Petición:</b>
Es la solicitud que se presenta en forma respetuosa ante un servidor público o ante ciertos particulares con el fin de requerir su intervención en un asunto concreto. Es la solicitud o requerimiento de una acción. Se debe resolver en 15 días siguientes a la fecha de recibo.<br />
<br><b>Quejas:</b>
Cuando en virtud de ellas se ponen en conocimiento las conductas irregulares de empleados o particulares a quienes se ha atribuido o adjudicado la prestación de un servicio público.<br />
<br><b>Reclamos:</b>
Cuando se da a las autoridades noticia de la suspensión injustificada o de la prestación deficiente de un servicio público.<br />
<br><b>Sugerencias:</b>
Recomendación hecha para mejorar.<br/>
<br><b>Felicitación:</b>
Reconocimiento al servicio que cumple y supera las expectativas.<br />
<br><b>Denuncia:</b>
Manifestación mediante la cual se pone en conocimiento a la entidad conductas posiblemente irregulares por parte de sus funcionarios, relacionadas con extralimitación de funciones, toma de decisiones prohibidas en el ejercicio de su cargo o el interés directo en una
decisión tomada. <br>
<br />
<h4>Importante: La petición anónima se regirá por la siguiente normativa Ley 962 de 2005 artículo 81, Ley 734 de 2002 artículo 69, Ley 190 de 1995 artículo 38 y Ley 24 de 1992 artículo 27 numeral 1.</h4>
<br />
';


//<br />
//<div class='.'subtitle'.'></div>
?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">
<br>

<div class="wrapper" id="contenedor-form" >

    <?php $form = ActiveForm::begin(['id' => 'form-signup']); ?>

    <!-- Formulario -->

    <span class="login100-form-title p-b-43">Registro de Peticiones, Quejas, Reclamos, Sugerencias, Solicitud de Información pública, Denuncias y Trámites Prestacionales </span>

        <div class="get-quote  d-lg-block" style="text-align: center;">
            <a class="boxed-btn registropqr btn-primary-orfeo" href="<?php echo Url::toRoute('/site/loginautomatico') ?>">
                 Registro Anónimo
            </a>
        </div><br>

        <section class="section1" >
            <div class="row form-group">
                <div class=" col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3"> Tipo de Solicitante <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'idTipoPersona')->widget(Select2::classname(), [
                            'data' => $list_tipos_persona,
                            'language' => 'es',
                            'options' => ['placeholder' => 'Seleccione un tipo de Solicitante'],
                            'pluginOptions' => [
                                'allowClear' => true,
                            ],
                        ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3">Tipo de Identificación</label>
                    <?= $form->field($model_form_signup, 'idTipoIdentificacion')->widget(Select2::classname(), [
                            'data' => $list_tipos_identificacion,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Seleccione un tipo de identificación'
                            ],
                            'pluginOptions' => [
                                'allowClear' => true,
                            ],
                        ]);
                    ?> 
                </div>	
                <div class="col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3">Número de Identificación <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'numeroDocumentoCliente')->textInput(['maxlength' => false, 'onchange' => 'setIdentificacion()', 'id' => 'numeroDocumentoCliente',]) ?>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-12 col-sm-6 form-holder razonSocial" style="display: none;">
                    <label for="" class="mb-3">Nombres/Razón Social <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'nombreCliente')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                </div>
                <div class="col-12 col-sm-6 form-holder razonSocial" style="display: none;">
                    <label for="" class="mb-3">Apellidos/Representante Legal <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'apellidoCliente')->textInput(['maxlength' => true]) ?>
                </div>
                <div class="col-12 col-sm-3 form-holder nombresCompletos" style="display: none;">
                    <label for="" class="mb-3">Primer nombre <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'primerNombre')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                </div>
                <div class="col-12 col-sm-3 form-holder nombresCompletos" style="display: none;">
                    <label for="" class="mb-3">Segundo nombre <span style="color:red;"></span></label>
                    <?= $form->field($model_form_signup, 'segundoNombre')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                </div>
                <div class="col-12 col-sm-3 form-holder nombresCompletos" style="display: none;">
                    <label for="" class="mb-3">Primer apellido <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'primerApellido')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                </div>
                <div class="col-12 col-sm-3 form-holde nombresCompletos" style="display: none;">
                    <label for="" class="mb-3">Segundo apellido <span style="color:red;"></span></label>
                    <?= $form->field($model_form_signup, 'segundoApellido')->textInput(['maxlength' => true, 'autofocus' => true]) ?>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-12 col-sm-12 form-holder">
                    <label for="" class="mb-3">
                    <?= $form->field($model_form_signup, 'username')->hiddenInput(['id' => 'username', 'readonly' => true]) ?>
                </div>

                <div class="col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Correo <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'email')->textInput(['type' => 'email', 'id' => 'email','onchange' =>'emailUsuario()']) ?>
                </div>

                <div class="col-12 col-sm-3 form-holder">
                    <div class="form-group field-confirm-email required">
                        <label for="" class="mb-3">Confirmación de Correo <span style="color:red;">*</span></label>
                        <input type="email" id="confirmEmail"  class="form-control"> 
                        <label id="messange-error" class="help-block help-block-error"></label>
                    </div>
                </div>
                <!-- <input type="hidden" id="password" class="form-control" name="UserPqrs[password]" aria-required="true" aria-invalid="true">
                <input type="text" id="confirmEmail" class="form-control"> -->

                <div class="col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Celular <span style="color:red;">*</span></label>
                    <?= $form->field($model_form_signup, 'telefonoCliente')->textInput() ?>
                </div>

                <div class="col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Telefono Fijo </label>
                    <?= $form->field($model_clientes_detalles, 'telefonoFijoClienteCiudadanoDetalle')->textInput()->label(false) ?>
                </div>
            </div>

            <br><hr> <label><b>Caracterización del Ciudadano</b></label><br>
            <div class="row form-group">
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Grupo de Interes <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'grupoInteres')->widget(Select2::classname(), [
                        'data' => $list_grupo_interes,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione un grupo interes'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Condición de Discapacidad <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'condicionDiscapacidad')->widget(Select2::classname(), [
                        'data' => $list_condicion_discapacidad,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione condición discapacidad'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Grupo Etnico <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'etnia')->widget(Select2::classname(), [
                        'data' => $list_etnia,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione un grupo etnico'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Genero <span style="color:red;">*</span></label>
                    <?php
                    echo $form->field($model_form_signup, 'genero')->widget(Select2::classname(), [
                        'data' => $list_genero,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione un Género'],
                        'pluginOptions' => [
                            'allowClear' => true,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Rango de Edad <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'rangoEdad')->widget(Select2::classname(), [
                        'data' => $list_rango_edad,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione Rango de Edad'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Actividad Economica <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'actividadEconomica')->widget(Select2::classname(), [
                        'data' => $list_actividad_economica,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione una actividad economica'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Nivel Estrato <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'estrato')->widget(Select2::classname(), [
                        'data' => $list_estrato,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione estrato'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Grupo Sisben <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'grupoSisben')->widget(Select2::classname(), [
                        'data' => $list_grupo_sisben,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione grupo sisben'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Escolaridad <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'escolaridad')->widget(Select2::classname(), [
                        'data' => $list_escolaridad,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione nivel escolaridad'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
                <div class=" col-12 col-sm-3 form-holder">
                    <label for="" class="mb-3">Vulnerabilidad <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'vulnerabilidad')->widget(Select2::classname(), [
                        'data' => $list_vulnerabilidad,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione nivel Vulnerabilidad'],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>
            </div>

            <br><hr> <label><b>Información de Residencia</b></label><br>
            <div class="row form-group">
                <div class=" col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3">País <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'idNivelGeografico1')->widget(Select2::classname(), [
                        'data' => $list_paises,
                        'value' => $model_form_signup->idNivelGeografico1,
                        'language' => 'es',
                        'options' => ['placeholder' => 'Seleccione un país', 'id' => 'nivelGeografico11',
                            'onchange' =>
                            '
                                $.post( "' . urldecode(Yii::$app->urlManager->createUrl('/site/nivel-geografico-2?id=')) . '"+$(this).val(), function( data ) {
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

                <div class="col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3">Departamento <span style="color:red;">*</span></label>
                    <?=
                    $form->field($model_form_signup, 'idNivelGeografico2')->widget(Select2::classname(), [
                        'data' => $list_departamentos,
                        'value' => $model_form_signup->idNivelGeografico2,
                        'language' => 'es',
                        'options' => [
                            'placeholder' => 'Seleccione un departamento',
                            'id' => 'Dep_id',
                            'onchange' =>
                            '
                                $.post( "' . urldecode(Yii::$app->urlManager->createUrl('/site/nivel-geografico-3?id=')) . '"+$(this).val(), function( data ) {
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

                <div class=" col-12 col-sm-4 form-holder">
                    <label for="" class="mb-3">Ciudad - Municipio <span style="color:red;">*</span></label>
                    <?php

                    echo $form->field($model_form_signup, 'idNivelGeografico3')->widget(DepDrop::classname(), [
                        'type' => DepDrop::TYPE_SELECT2,
                        'data' => $list_ciudades,
                        'value' => $model_form_signup->idNivelGeografico3,
                        'language' => 'es', 
                        'options' => [ 'id' => 'City_id', ],
                        'pluginOptions'=>[
                            'depends'=>['Dep_id'],
                            'placeholder'=>'Seleccione una ciudad/municipio...',
                            'url'=>Url::to(['/site/nivel-geografico-3'])
                        ]
                    ]);
                    ?>
                </div>	

                

            </div>

            <div class="row form-group">
                <div class="col-12 col-sm-12 form-holder">
                    <label for="" class="mb-3">
                        Dirección <span style="color:red;">*</span>
                        <?= $form->field($model_form_signup, 'direccionCliente')->hiddenInput(['maxlength' => true]) ?>
                    </label>
                </div>

                <div class="col-4">
                    <?php
                    echo $form->field($model_form_signup, 'dirCam1')->widget(Select2::classname(), [
                        'data' => ['Vereda' => 'Vereda', 'Calle' => 'Calle', 'Carrera' => 'Carrera', 'Diagonal' => 'Diagonal', 'Transversal' => 'Transversal', 'Avenida' => 'Avenida'],
                        'value' => $model_form_signup->dirCam1,
                        'language' => 'es',
                        'options' => [
                            'placeholder' => 'Seleccione una ...',
                        ],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>

                <div class="col-2 p-0"> <?= $form->field($model_form_signup, 'dirCam2')->textInput(['maxlength' => true]) ?> </div>
                <div class="col-1 p-0"> <?= $form->field($model_form_signup, 'dirCam3')->textInput(['readonly' => true, 'value' => '#']) ?></div>
                <div class="col-1 p-0"> <?= $form->field($model_form_signup, 'dirCam4')->textInput(['maxlength' => true]) ?> </div>
                <div class="col-1 p-0"> <?= $form->field($model_form_signup, 'dirCam5')->textInput(['maxlength' => true]) ?> </div>

                <div class="col-3"> 
                    <?php
                    echo $form->field($model_form_signup, 'dirCam6')->widget(Select2::classname(), [
                        'data' => ['N/A' => 'N/A','Norte' => 'Norte', 'Sur' => 'Sur', 'Este' => 'Este', 'Oeste' => 'Oeste'],
                        'language' => 'es',
                        'options' => [
                            'placeholder' => 'Seleccione una ...',
                        ],
                        'pluginOptions' => [
                            'allowClear' => false,
                        ],
                    ]);
                    ?>
                </div>

                <div class="col-12 col-sm-12 form-holder">
                    <label for="" class="mb-3">
                        Barrio de Residencia
                        <?= $form->field($model_form_signup, 'barrioClientesCiudadanoDetalle')->textInput(['maxlength' => true]) ?>
                    </label>
                </div>


            </div>
            
            <input type="hidden" name="actualizar" id="actualizar" />
            <input type="hidden" name="UserPqrs[idcliente]" id="idcliente" />

            <div class="form-group">
                <?= Html::submitButton('Continuar', ['class' => 'btn btn-block btn-primary-orfeo p-3 col-6 col-sm-3 float-right', 'name' => 'signup-button', 'id' => 'signup-button']) ?>  
            </div> 
            <input type="hidden" name="terminos" id="terminos" value="<?=$terminos_condiciones?>" />
            <input type="hidden" name="terminosBienvenida" id="terminosBienvenida" value="<?= $alertaBienvenida ?>" />
        </section>  

    <?php ActiveForm::end(); ?>

</div>

<script>

    var wizard_global = "#wizard-t-0";
    var section_global = "section1";
    var submit = false;

    $(document).ready(function () {

        $('a[href="#next"]').hide();
        $('a[href="#finish"]').hide();
        $("#nivelGeografico1 option[value=1]").attr("selected", true);

        $('#form-signup input').change(function () {
            wizardValidacion();
        });

        $('#confirmEmail').on('change', function () {

            $('.field-email').removeClass('has-error');
            $('.field-email').addClass('has-success');
            $('#messange-error-pass').html('');
               
            if ($('#confirmEmail').val() != '') {
                if ($('#confirmEmail').val() == $('#email').val()) {
    
                    $('.field-confirm-email').removeClass('has-error');
                    $('.field-confirm-email').addClass('has-success');
                    $('#messange-error').html('');
                    submit = true;
                } else {
    
                    $('.field-confirm-email').removeClass('has-success');
                    $('.field-confirm-email').addClass('has-error');
                    $('#messange-error').html('El correo electrónico no coincide');
                    submit = false;
                }
            }else{

                $('.field-confirm-email').removeClass('has-success');
                $('.field-confirm-email').addClass('has-error');
                $('#messange-error').html('Este campo no puede estar vacío.');
                submit = false;
            }           
        });

        $('#contenedor-form').on('beforeSubmit', 'form#form-signup', function () {      
            
            var form = $(this);
            var cargando;

            // return false if form still have some validation errors
            if (form.find('.has-error').length) {
                return false;
            }

            document.getElementById('form-signup').submit();
            return false;
        });

        $("#userpqrs-idtipopersona").change(function () {
            // Persona Juridica
            if($("#userpqrs-idtipopersona").val() == 1){

                $('.claseOcualta').hide();
                $('#userpqrs-rangoedadclienteciudadanodetalle').val('1');
                $('#userpqrs-vulnerabilidadclienteciudadanodetalle').val('6');
                $('#userpqrs-generoclienteciudadanodetalle').val('1');
                $('#userpqrs-etniaclienteciudadanodetalle').val('6');
                $('#barrio').val('');
                $('.razonSocial').show();
                $('.nombresCompletos').hide();
            }
            // Persona Natural
            else{

                $('.claseOcualta').show();
                $('#userpqrs-rangoedadclienteciudadanodetalle').val('1');
                $('#userpqrs-vulnerabilidadclienteciudadanodetalle').val('6');
                $('#userpqrs-generoclienteciudadanodetalle').val('1');
                $('#userpqrs-etniaclienteciudadanodetalle').val('6');
                $('#barrio').val('');
                $('.razonSocial').hide();
                $('.nombresCompletos').show();
            }

        });

        AlertaInformativa($('#terminos').val());

        $('#signup-button').on("click", function(event) {
            event.preventDefault();
            if ($("#userpqrs-idtipopersona").val() == 1) {
                if ($('#userpqrs-nombrecliente').val() == '' || $('#userpqrs-apellidocliente').val() == '') {
                    $('.field-userpqrs-nombrecliente').removeClass('has-success');
                    $('.field-userpqrs-apellidocliente').removeClass('has-success');
                    $('.field-userpqrs-nombrecliente').addClass('has-error');
                    $('.field-userpqrs-apellidocliente').addClass('has-error');
                } else {
                    $('.field-userpqrs-nombrecliente').removeClass('has-error');
                    $('.field-userpqrs-apellidocliente').removeClass('has-error');
                    $('.field-userpqrs-nombrecliente').addClass('has-success');
                    $('.field-userpqrs-apellidocliente').addClass('has-success');

                    $(this).submit();
                }

            } else {
                if ($('#userpqrs-primernombre').val() == '' || $('#userpqrs-primerapellido').val() == '') {                    $('.field-userpqrs-primernombre').removeClass('has-success');
                    $('.field-userpqrs-primerapellido').removeClass('has-success');
                    $('.field-userpqrs-segundoapellido').removeClass('has-success');
                    $('.field-userpqrs-primernombre').addClass('has-error');
                    $('.field-userpqrs-primerapellido').addClass('has-error');
                    $('.field-userpqrs-segundoapellido').addClass('has-error');

                } else {
                    $('.field-userpqrs-primernombre').removeClass('has-error');
                    $('.field-userpqrs-primerapellido').removeClass('has-error');
                    $('.field-userpqrs-segundoapellido').removeClass('has-error');
                    $('.field-userpqrs-primernombre').addClass('has-success');
                    $('.field-userpqrs-primerapellido').addClass('has-success');
                    $('.field-userpqrs-segundoapellido').addClass('has-success');

                    $(this).submit();
                }
            }
        })

    });

    function AlertaInformativa(terminos){

        var dialog = bootbox.dialog({
            position: 'center',
            title: '<b class="alerta-informativa" > Ley 1581 del 2012 y Decreto 1377 de 2013: Protección de datos personales </b>',
            message: "<p> "+terminos+"</p><br>",
            size: 'large',
            centerVertical: true,
            closeButton: false,
            buttons: {
                cancel: {
                    label: "NO ACEPTO",
                    className: 'btn-primary-orfeo',
                    callback: function(){
                        window.location.href = '<?= Yii::$app->urlManager->createUrl('site/index') ?>';
                    }
                },
                ok: {
                    label: "ACEPTO",
                    className: 'btn-primary-orfeo',
                    callback: function(){
                        AlertaInformativaHojaBienvenida();
                    }
                }
            }
        })
        .find('.modal-dialog-centered').css({
            'position': 'relative',
        })
        .find('.modal-content').css({
            'position': 'absolute',
            'top': '193px',
            'height': '400px',
            'overflow': 'auto'
        })
        .find('.subtitle').css({
            'font-weight': 'bold',
            'color': 'black'
        });
    }

    function AlertaInformativaHojaBienvenida(){
        var dialog = bootbox.dialog({
            position: 'center',
            title: '<b class="alerta-informativa" > Apreciado ciudadano tenga en cuenta: </b>',
            message: "<div style='text-align: justify;'> "+$('#terminosBienvenida').val()+"</div>",
            size: 'large',
            centerVertical: true,
            closeButton: false,
            buttons: {
                cancel: {
                    label: "NO ACEPTO",
                    className: 'btn-primary-orfeo',
                    callback: function(){
                        window.location.href = '<?= Yii::$app->urlManager->createUrl('site/index') ?>';
                    }
                },
                ok: {
                    label: "ACEPTO",
                    className: 'btn-primary-orfeo',
                    callback: function(){
 
                    }
                }
            }
        })
        .find('.modal-dialog-centered').css({
            'position': 'relative',
        })
        .find('.modal-content').css({
            'position': 'absolute',
            'top': '193px',
            'height': '600px',
            'overflow': 'auto'
        })
        .find('.subtitle').css({
            'font-weight': 'bold',
            'color': 'black'
        });
    }

    function PasswordValidacion(value){

        //Validators.pattern('(?=\\D*\\d)(?=[^a-z]*[a-z])(?=[^A-Z]*[A-Z])(?=.*[$@$!%*?&¿¡!~#]).{1,30}')
        let hasNumber = /\d/.test(value);
            let hasLower = (/[áéíóúàèìòù]/.test(value)) || (/[a-z]/.test(value));
            let hasUpper = (/[ÁÉÍÓÚÀÈÌÒÙ]/.test(value)) || (/[A-Z]/.test(value));
            let hasCharacter = /[$@$!%*?&¿¡~#_-]/.test(value);
            //console.log('Num, Upp, Low, specialCharacter', hasNumber, hasUpper, hasLower, hasCharacter);
            const valid1 = hasNumber && hasUpper && hasLower;
            const valid2 = hasNumber && hasUpper && hasCharacter;
            const valid3 = hasNumber && hasLower && hasCharacter;
            const valid4 = hasLower && hasUpper && hasCharacter;
            
        if (valid1) {
            return true;
        } else if (valid2) {
            return true;
        } else if (valid3) {
            return true;
        } else if (valid4) {
            return true;
        } else {
            // return what´s not valid
            return false;
        }
    }

    function wizardValidacion() {

        setTimeout(() => {

            /* seleccionar las etiquetas "div" con clase="required" dentro de la clase "section1" */
            var Camposrequeridos = $("." + section_global + " div.required");
            var verificador = 0;

            for (let index = 0; index < Camposrequeridos.length; index++) {

                if (Camposrequeridos[index].className.includes('has-success')) {
                    verificador = 1;
                } else {
                    verificador = 0;
                    $(wizard_global).removeClass('checkedEstado');
                    break;
                }
            }

            if (verificador == 1) {
                $(wizard_global).addClass('checkedEstado');
            }

        }, 500);
    }

    function setIdentificacion() {
        $('#username').val($('#numeroDocumentoCliente').val());
        validateClient();
    }

    function sectionValidacion(section, wizard) {

        wizard_global = wizard;
        section_global = section;

        /* seleccionar las etiquetas "div" con clase="required" dentro de la clase "section1" */
        var Camposrequeridos = $("." + section + " div.required");
        var verificador = 0;

        for (let index = 0; index < Camposrequeridos.length; index++) {

            if (Camposrequeridos[index].className.includes('has-success')) {
                verificador = 1;
            } else {
                verificador = 0;
                break;
            }
        }

        if (verificador == 1) {

            $('a[href="#next"]').click();

            setTimeout(() => {

                $("#wizard div.required").removeClass("has-error");
                $("#wizard p.help-block-error").html("");
                $("#" + wizard).addClass('checkedEstado');

            }, 500);
        }
    }

    function identificacionUsuario() {

        var data = {
            numeroDocumentoCliente: $('#numeroDocumentoCliente').val()
        };

        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/site/verificar-usuario') ?>',
            type: 'post',
            data: data,
            success: function (response)
            {
                if (response == 2) {
                    var dialog = bootbox.dialog({
                        title: '<b class="alerta-informativa" > Usuario no disponible</b>',
                        message: "<p> Este usuario ya se encuentra registrado, si la cuenta te pertenece y olvidaste la contraseña ingresa a ¿Cómo recuperar mi cuenta? </p>",
                        size: 'large',
                        closeButton: false,
                        buttons: {
                            cancel: {
                                label: "Reintentar",
                                className: 'btn-primary-orfeo',
                                callback: function() {
                                    $('#numeroDocumentoCliente').val('');
                                    $("#numeroDocumentoCliente").change();

                                    $('.field-sgd_ciu_cedula').removeClass('has-success');
                                    $('.field-sgd_ciu_cedula').removeClass('has-error');
                                    $('.field-sgd_ciu_cedula p').html('');

                                    $("#numeroDocumentoCliente").focus();

                                    $('#username').val('');
                                    $('#username').change();
                                }
                            },
                            ok: {
                                label: "¿Cómo recuperar mi cuenta?",
                                className: 'btn-primary-orfeo',
                                callback: function(){
                                    window.location.href = '<?= Yii::$app->urlManager->createUrl('site/request-password-reset') ?>';
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

    function emailUsuario(){

        // submit form
        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/site/verificar-email') ?>',
            type: 'post',
            data: {'email': $('#email').val()},
            success: function (response)
            {
                if (response == 2) {
                    var dialog = bootbox.dialog({
                        title: '<b class="alerta-informativa" > Usuario no disponible</b>',
                        message: "<p> El correo electrónico se encuentra registrado y asociado a un funcionario, por favor utilice una cuenta de correo diferente </p>",
                        size: 'large',
                        closeButton: false,
                        buttons: {
                            cancel: {
                                label: "Reintentar",
                                className: 'btn-primary-orfeo',
                                callback: function() {

                                    $('.field-email').removeClass('has-success');
                                    $('.field-email').addClass('has-error');


                                    $('#email').val('');
                                    $('#email').change();
                                }
                            }
                        }
                    });
                } else if (response == 3) {
                    var dialog = bootbox.dialog({
                        title: '<b class="alerta-informativa" > Correo registrado</b>',
                        message: "<p> El correo electrónico ya se encuentra registrado, ¿Está seguro que desea continuar y así compartir la misma cuenta de correo con otro usuario? </p>",
                        size: 'large',
                        closeButton: false,
                        buttons: {
                            cancel: {
                                label: "No, tengo otra cuenta de correo",
                                className: 'btn-primary-orfeo',
                                callback: function(){  
                                    $('.field-email').removeClass('has-success');
                                    $('.field-email').addClass('has-error');

                                    $('#email').val('');
                                    $('#email').change();
                                }
                            },
                            noclose: {
                                label: "¿Cómo recuperar mi cuenta?",
                                className: 'btn-primary-orfeo',
                                callback: function(){
                                    window.location.href = '<?= Yii::$app->urlManager->createUrl('site/request-password-reset') ?>';
                                }
                            },
                            ok: {
                                label: "Sí, continuar",
                                className: 'btn-primary-orfeo',
                                callback: function(){
                                }
                            }
                        }
                    });
                } else{

                    return true;
                }
            },
            error: function (error)
            {
                console.log('internal server error ' + error);
            }
        });
    }

    function validateClient(){

        // submit form
        $.ajax({
            url: '<?= Yii::$app->urlManager->createUrl('/site/info-cliente') ?>',
            type: 'post',
            data: {"documentodatos": $('#numeroDocumentoCliente').val()},
            success: function (response)
            {
                var obj = jQuery.parseJSON(response);
                if (obj.status == true) { 

                    var nombre = '';
                    var camposecundario = '';
                    var primerNombre = '';
                    var segundoNombre = '';
                    var primerApellido = '';
                    var segundoApellido = '';

                    // Persona Juridica
                    if(obj.datos.idTipoPersona == 1){
                        nombre = obj.datos.nombreCliente;
                        camposecundario = obj.datos.represntanteLegal;
                        $('.razonSocial').show();
                        $('.nombresCompletos').hide();
                    }
                    // Persona Natural
                    else{

                        var split = obj.datos.nombreCliente.split(' ');

                        if(split.length === 3) {
                            primerNombre = split[0];
                            primerApellido = split[1];
                            segundoApellido = split[2];
                        } else if(split.length === 4) {
                            primerNombre = split[0];
                            segundoNombre = split[1];
                            primerApellido = split[2];
                            segundoApellido = split[3];
                        }

                        $('.razonSocial').hide();
                        $('.nombresCompletos').show();
                    }

                    var splitDireccion = obj.datos.direccion.split(' ');
                    // console.log(obj);
                    // console.log(obj.datos.barrio);


                    $('#actualizar').val(true);  //represntanteLegal
                    $('#idcliente').val(obj.datos.idcliente);

                    $('#userpqrs-primernombre').val(primerNombre);
                    $('#userpqrs-segundonombre').val(segundoNombre);
                    $('#userpqrs-primerapellido').val(primerApellido);
                    $('#userpqrs-segundoapellido').val(segundoApellido);

                    $('#userpqrs-nombrecliente').val(nombre);
                    $('.field-userpqrs-nombrecliente').removeClass('has-error');
                    $('.field-userpqrs-nombrecliente').addClass('has-success');

                    $('#userpqrs-apellidocliente').val(camposecundario);
                    $('.field-userpqrs-apellidocliente').removeClass('has-error');
                    $('.field-userpqrs-apellidocliente').addClass('has-success');

                    $('#userpqrs-idtipoidentificacion').val(obj.datos.idTipoIdentificacion);
                    $('.field-userpqrs-idtipoidentificacion').removeClass('has-error');
                    $('.field-userpqrs-idtipoidentificacion').addClass('has-success');
                    $('#select2-userpqrs-idtipoidentificacion-container').html(obj.datos.nombreTipoIdentificacion);

                    $('#userpqrs-idtipopersona').val(obj.datos.idTipoPersona);
                    $('.field-userpqrs-idtipopersona').removeClass('has-error');
                    $('.field-userpqrs-idtipopersona').addClass('has-success');
                    $('#select2-userpqrs-idtipopersona-container').html(obj.datos.nombreTipoPersona);

                    $('#username').val(obj.datos.username);
                    $('.field-username').removeClass('has-error');
                    $('.field-username').addClass('has-success');

                    $('#email').val(obj.datos.correoElectronico);
                    $('.field-email').removeClass('has-error');
                    $('.field-email').addClass('has-success');
                    $('#confirmEmail').val(obj.datos.correoElectronico);
                    $('.field-confirm-email').removeClass('has-error');
                    $('.field-confirm-email').addClass('has-success');

                    $('#userpqrs-telefonocliente').val(obj.datos.telefono);
                    $('.field-userpqrs-telefonocliente').removeClass('has-error');
                    $('.field-userpqrs-telefonocliente').addClass('has-success');

                    $('#userpqrs-grupointeres').val(obj.datos.grupoInteres);
                    $('.field-userpqrs-grupointeres').removeClass('has-error');
                    $('.field-userpqrs-grupointeres').addClass('has-success');
                    $('#select2-userpqrs-grupointeres-container').html(obj.datos.nombreGrupoInteres);

                    $('#userpqrs-condiciondiscapacidad').val(obj.datos.condicionDiscapacidad);
                    $('.field-userpqrs-condiciondiscapacidad').removeClass('has-error');
                    $('.field-userpqrs-condiciondiscapacidad').addClass('has-success');
                    $('#select2-userpqrs-condiciondiscapacidad-container').html(obj.datos.nombreCondicionDiscapacidad);

                    $('#userpqrs-etnia').val(obj.datos.etnia);
                    $('.field-userpqrs-etnia').removeClass('has-error');
                    $('.field-userpqrs-etnia').addClass('has-success');
                    $('#select2-userpqrs-etnia-container').html(obj.datos.nombreEtnia);

                    $('#userpqrs-genero').val(obj.datos.genero);
                    $('.field-userpqrs-genero').removeClass('has-error');
                    $('.field-userpqrs-genero').addClass('has-success');
                    $('#select2-userpqrs-genero-container').html(obj.datos.nombreGenero);

                    $('#userpqrs-rangoedad').val(obj.datos.rangoEdad);
                    $('.field-userpqrs-rangoedad').removeClass('has-error');
                    $('.field-userpqrs-rangoedad').addClass('has-success');
                    $('#select2-userpqrs-rangoedad-container').html(obj.datos.nombreRangoEdad);

                    $('#userpqrs-actividadeconomica').val(obj.datos.actividadEconomica);
                    $('.field-userpqrs-actividadeconomica').removeClass('has-error');
                    $('.field-userpqrs-actividadeconomica').addClass('has-success');
                    $('#select2-userpqrs-actividadeconomica-container').html(obj.datos.nombreActividadEconomica);

                    $('#userpqrs-estrato').val(obj.datos.estrato);
                    $('.field-userpqrs-estrato').removeClass('has-error');
                    $('.field-userpqrs-estrato').addClass('has-success');
                    $('#select2-userpqrs-estrato-container').html(obj.datos.nombreEstrato);

                    $('#userpqrs-gruposisben').val(obj.datos.grupoSisben);
                    $('.field-userpqrs-gruposisben').removeClass('has-error');
                    $('.field-userpqrs-gruposisben').addClass('has-success');
                    $('#select2-userpqrs-gruposisben-container').html(obj.datos.nombreGrupoSisben);

                    $('#userpqrs-escolaridad').val(obj.datos.escolaridad);
                    $('.field-userpqrs-escolaridad').removeClass('has-error');
                    $('.field-userpqrs-escolaridad').addClass('has-success');
                    $('#select2-userpqrs-escolaridad-container').html(obj.datos.nombreEscolaridad);

                    $('#userpqrs-vulnerabilidad').val(obj.datos.vulnerabilidad);
                    $('.field-userpqrs-vulnerabilidad').removeClass('has-error');
                    $('.field-userpqrs-vulnerabilidad').addClass('has-success');
                    $('#select2-userpqrs-vulnerabilidad-container').html(obj.datos.nombreVulnerabilidad);

                    $('#nivelGeografico11').val(obj.datos.idNivelGeografico1);
                    $('.field-nivelGeografico1').removeClass('has-error');
                    $('.field-nivelGeografico1').addClass('has-success');
                    $('#select2-nivelGeografico11-container').html(obj.datos.nombreNivelGeografico1);
                    $('#Dep_id').val(obj.datos.idNivelGeografico2);
                    $('.field-Dep_id').removeClass('has-error');
                    $('.field-Dep_id').addClass('has-success');
                    $('#select2-Dep_id-container').html(obj.datos.nombreNivelGeografico2);
                    $('#City_id').val(obj.datos.idNivelGeografico3);
                    $('.field-City_id').removeClass('has-error');
                    $('.field-City_id').addClass('has-success');
                    $('#select2-City_id-container').html(obj.datos.nombreNivelGeografico3);

                    $('#userpqrs-dircam1').val(splitDireccion[0]);
                    $('#userpqrs-dircam1').change();
                    $('#userpqrs-dircam2').val(splitDireccion[1]);
                    $('#userpqrs-dircam4').val(splitDireccion[3]);
                    $('#userpqrs-dircam5').val(splitDireccion[4]);
                    $('#userpqrs-dircam6').val(splitDireccion[5]);
                    $('#userpqrs-dircam6').change();
                    $('#userpqrs-barrioclientesciudadanodetalle').val(obj.datos.barrio);
                    $('#clientesciudadanosdetalles-telefonofijoclienteciudadanodetalle').val(obj.datos.telefonoFijo);

                } else{
                    $('#username').val($('#numeroDocumentoCliente').val());
                    $('#actualizar').val(false);
                    $('#idcliente').val('');
                    $('#userpqrs-nombrecliente').val('');
                    $('#userpqrs-apellidocliente').val('');
                    $('#userpqrs-idtipopersona').val('');
                    $('#select2-userpqrs-idtipopersona-container').html('');
                    $('#email').val('');
                    $('#confirmEmail').val('');
                    $('#userpqrs-telefonocliente').val('');

                    $('#userpqrs-generoclienteciudadanodetalle').val('');
                    $('#select2-userpqrs-vulnerabilidadclienteciudadanodetalle-container').html('');
                    $('#userpqrs-rangoedadclienteciudadanodetalle').val('');
                    $('#select2-userpqrs-rangoedadclienteciudadanodetalle-container').html('');
                    $('#userpqrs-vulnerabilidadclienteciudadanodetalle').val('');
                    $('#select2-userpqrs-vulnerabilidadclienteciudadanodetalle-container').html('');
                    $('#userpqrs-etniaclienteciudadanodetalle').val('');
                    $('#select2-userpqrs-etniaclienteciudadanodetalle-container').html('');

                    $('#nivelGeografico1').val('');
                    $('#Dep_id').val('');
                    $('#select2-Dep_id-container').html('');
                    $('#City_id').val('');
                    $('#select2-City_id-container').html('');
                    $('#userpqrs-barrioclientesciudadanodetalle').val('');

                }
            },
            error: function (error)
            {
                console.log('internal server error ' + error);
            }
        });
    }

</script>
