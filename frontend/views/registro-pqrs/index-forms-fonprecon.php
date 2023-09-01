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
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
use kartik\file\FileInput;
use yii\helpers\Html;

$this->title = 'Registro Peticiones, Quejas, Reclamos, Sugerencias, Solicitud de Información pública, Denuncias y Trámites Prestacionales';
$this->params['breadcrumbs'][] = $this->title;
?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/form-fonprecon.css">

<br />

<div class="wrapper" id="contenedor-form">
    <?php
    $form = ActiveForm::begin(['options' =>
    [
        'id' => 'formPqrsTipoTramite',
        'class' => 'formSolicitud',
        'enctype'=>'multipart/form-data',
        'action' => 'none'
    ]]);
    ?>
<div class="alert alert-primary" role="alert">
  Seleccione por favor el tramite que desea registrar en la entidad
</div>
        <section class="section1">
            <div class="row form-group">
                <div class="col-12 col-sm-6 form-holder">
                    <label class="pb-3">Tipo de trámite</label>
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
                <div class="col-12 col-sm-6 form-holder">
                    <label class="pb-3">Tipo de solicitud</label>
                    <?= $form->field($model_registro_pqrs, 'tipoSolicitud')->widget(Select2::classname(), [
                            'data' => $list_formulario_pqrs_detalle,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un tipo de solicitud.',
                                'id' => 'tipoSolicitud'
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>
            </div>
        </section>

        <input type="hidden" id="idTipoTramitePqrs" value="<?= Yii::$app->params['formsRegistroPqrsText']['actionIndexPension']; ?>" />
        <input type="hidden" id="idTipoSolicitudPqrs" value="<?= $id_tipo_solicitud_actual ?>" />
        <input id="reloadForm" type="hidden" name="reloadForm" value="<?= false; ?>" />
    <?php
    ActiveForm::end();
    ?>

    <?php
    $form = ActiveForm::begin(['options' =>
    [
        'id' => 'form-pqrs',
        'class' => 'formSolicitud',
        'enctype'=>'multipart/form-data',
        'action' => 'none'
    ]]);
    ?>
        <section class="section1">
            <div class="row">
                <div class="col-12 col-sm-12">
                    <div class="panel panel-default">
                        <div class="panel-heading"><b>Descripción de la prestación</b></div>
                        <div class="panel-body">
                            <?= $data_formulario_pqrs_detalle['descripcionCgFormulariosPqrsDetalle'] ?? ''; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row form-group">
                <div class="col-12 col-sm-4 form-holder">
                    <label class="pb-3">Cargo</label>
                    <?= $form->field($form_fonprecon, 'cargo')->textInput(['maxlength' => true, 'class' => 'form-control send_form_validate'])->label(''); ?>
                </div>
                <div class="col-12 col-sm-4 form-holder">
                    <label class="pb-3">Seleccione el medio de envío de respuesta</label>
                    <?= $form->field($form_fonprecon, 'medioRespuesta')->widget(Select2::classname(), [
                            'data' => $list_medio_respuesta,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un medio de respuesta.',
                                'id' => 'medioRespuesta',
                                'class' => 'send_form_validate'
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>
                <div class="col-12 col-sm-4 form-holder">
                    <label class="pb-3">Calidad del causante</label>
                    <?= $form->field($form_fonprecon, 'calidadCausante')->widget(Select2::classname(), [
                            'data' => $list_calidad_causante,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un tipo de calidad causante.',
                                'id' => 'calidadCausante',
                                'class' => 'send_form_validate'
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>
                <div class="col-12 col-sm-4 form-holder">
                    <label class="pb-4">Tipo de empleador</label>
                    <?= $form->field($form_fonprecon, 'tipoEmpleador')->widget(Select2::classname(), [
                            'data' => $list_tipo_empleador,
                            'language' => 'es',
                            'options' => [
                                'placeholder' => 'Selecciona un tipo de empleador.',
                                'id' => 'tipoEmpleador',
                                'class' => 'send_form_validate'
                            ],
                            'pluginOptions' => [
                                'allowClear' => false,
                            ],
                        ])->label('');
                    ?>
                </div>

                <?php if (count($list_categoria_prestacion) > 0) { ?>
                    <div id="categoriaPrestacionContainer" class="col-12 col-sm-4 form-holder">
                        <label class="pb-3">Seleccione categoria de prestación</label>
                        <?= $form->field($form_fonprecon, 'categoriaPrestacion')->widget(Select2::classname(), [
                                'data' => $list_categoria_prestacion,
                                'language' => 'es',
                                'options' => [
                                    'placeholder' => 'Selecciona una categoria de prestación.',
                                    'id' => 'categoriaPrestacion',
                                    'class' => 'send_form_validate'
                                ],
                                'pluginOptions' => [
                                    'allowClear' => false,
                                ],
                            ])->label('');
                        ?>
                        <p id="categoriaPrestacionError" class="help-block help-block-error" style="color: #a94442; display: none;">no puede estar vacío.</p>
                    </div>
                    <?php if ($activar_select_beneficiario) { ?>
                        <div id="calidadBeneficiarioContainer" class="col-12 col-sm-4 form-holder">
                            <label class="pb-3">Calidad del beneficiario que solicita</label>
                            <?= $form->field($form_fonprecon, 'calidadBeneficiario')->widget(Select2::classname(), [
                                    'data' => $list_calidad_beneficiario,
                                    'language' => 'es',
                                    'options' => [
                                        'placeholder' => 'Selecciona calidad del beneficiario.',
                                        'id' => 'calidadBeneficiario',
                                        'class' => 'send_form_validate'
                                    ],
                                    'pluginOptions' => [
                                        'allowClear' => false,
                                    ],
                                ])->label('');
                            ?>
                        </div>
                    <?php } ?>
                <?php } ?>
            </div>

            <?php if ($mostrar_docs) { ?>
                <div class="row">
                    <div class="col-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading"><b>Adjuntar documentos</b></div>
                            <div class="panel-body">
                                <?= $data_formulario_pqrs_detalle['adjuntarDocsCgFormulariosPqrsDetalle'] ?? ''; ?>
                            </div>
                        </div>
                    </div>

                    <?php foreach ($docs_formulario_pqrs_detalle as $key => $doc_formulario_pqrs_detalle) { ?>
                        <div class="col-12 col-sm-4">
                            <div id="<?= $doc_formulario_pqrs_detalle['nameFileCgFormulariosPqrsDetalleDocumentos']; ?>_container" class="panel panel-default">
                                <div class="panel-heading"><b><?= $doc_formulario_pqrs_detalle['nombreCgFormulariosPqrsDetalleDocumentos'] ?> </b><span class="glyphicon glyphicon-info-sign" aria-hidden="true" title="<?= $doc_formulario_pqrs_detalle['descripcionCgFormulariosPqrsDetalleDocumentos']; ?>"></span></div>
                                <div class="panel-body">
                                    <?= FileInput::widget([
                                            'name' => $doc_formulario_pqrs_detalle['nameFileCgFormulariosPqrsDetalleDocumentos'],
                                            'options' => ['id' => $doc_formulario_pqrs_detalle['nameFileCgFormulariosPqrsDetalleDocumentos'], 'class' => 'docs_form_fonprecon', 'requerido' => $doc_formulario_pqrs_detalle['requeridoCgFormulariosPqrsDetalleDocumentos']],
                                            'pluginOptions' => [
                                                'allowedFileExtensions' => $exceptios_file_pqrs,
                                                'uploadClass' => 'd-none',
                                                'maxFileSize' => 20000
                                            ]
                                        ]);
                                    ?>
                                </div>
                            </div>
                            <p id="<?= $doc_formulario_pqrs_detalle['nameFileCgFormulariosPqrsDetalleDocumentos']; ?>_error" class="help-block help-block-error" style="color: #a94442; display: none;">no puede estar vacío.</p>
                        </div>
                    <?php } ?>

                    <div class="col-12 col-sm-12">
                        <div class="panel panel-default">
                            <div class="panel-heading"><b>Terminos y Condiciones de la Prestación</b></div>
                            <div class="panel-body">
                                <?= $data_formulario_pqrs_detalle['terminosCgFormulariosPqrsDetalle']; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php } ?>

            <?php if ($activar_guardar) { ?>
                <!-- <div id="reCaptchaContainer" class="pt-4 mt-3"> -->
                    <?php //echo  $form->field($form_fonprecon, 'reCaptcha')->widget(\himiklab\yii2\recaptcha\ReCaptcha2::className()) ?>
                    <?php //echo $form->field($form_fonprecon, 'reCaptcha')->widget(\kekaadrenalin\recaptcha3\ReCaptchaWidget::class) ?>
                <!-- </div> -->

                <div class="row">
                    <div class="col-12">
                        <?= Html::submitButton('Enviar Solicitud', ['id' => 'SubmitButton', 'style' => 'display: block;','class' => 'btn btn-primary-orfeo center-block p-3 mt-4']) ?>
                    </div>
                </div>
            <?php } ?>
        </section>

        <input type="hidden" name="RegistroPqrs[tipoTramite]" value="<?= $id_tipo_tramite_actual; ?>" />
        <input type="hidden" name="RegistroPqrs[tipoSolicitud]" value="<?= $id_tipo_solicitud_actual; ?>" />
        <input type="hidden" id="envioPorSelectores" name="envioPorSelectores" value="<?= $envio_por_selectores; ?>" />
        <input type="hidden" id="envioData" name="envioData" value="false" />
    <?php
    ActiveForm::end();
    ?>
</div>

<script type="text/javascript">
    $(document).ready(function () {
        $("#tipoTramite option[value='<?= $id_tipo_tramite_actual; ?>']").attr("selected", true);
        $("#tipoSolicitud option[value='<?= $id_tipo_solicitud_actual; ?>']").attr("selected", true);
        if ($('#envioPorSelectores').val() === 'true') {
            $('#formsfonprecon-cargo').val('<?= $cargo_actual; ?>');
            $("#medioRespuesta option[value='<?= $medio_respuesta_actual; ?>']").attr("selected", true);
            $("#calidadCausante option[value='<?= $calidad_causante_actual; ?>']").attr("selected", true);
            $("#tipoEmpleador option[value='<?= $tipo_empleador_actual; ?>']").attr("selected", true);
            $("#categoriaPrestacion option[value='<?= $categoria_prestacion_actual; ?>']").attr("selected", true);
            $("#calidadBeneficiario option[value='<?= $calidad_beneficiario_actual; ?>']").attr("selected", true);
        }

        $('#tipoTramite').on('change', function() {
            $('#reloadForm').val(true);
            $('#formPqrsTipoTramite').submit();
        });

        $('#tipoSolicitud').on('change', function() {
            $('#reloadForm').val(false);
            $('#formPqrsTipoTramite').submit();
        });

        $('.send_form_validate').on('change', function() {
            if($('#categoriaPrestacionContainer').length) {
                if ($('#categoriaPrestacion').val() === '') {
                    $('#categoriaPrestacionContainer > div > span > span > span').addClass('border-red-fonprecon');
                    $('#categoriaPrestacionError').show();

                    return false;
                }

                $('#reCaptchaContainer').remove();
                $('#envioPorSelectores').val('true');
                $('#form-pqrs').submit();
            }
        });

        $('#categoriaPrestacion').on('change', function() {
            $('#categoriaPrestacionContainer > div > span > span > span').removeClass('border-red-fonprecon');
            $('#categoriaPrestacionError').hide();
        });

        $('.docs_form_fonprecon').on('change', function() {
            $('#' + $(this).attr('id') + '_container').removeClass('border-red-fonprecon');
            $('#' + $(this).attr('id') + '_error').hide();
        });

        $('#SubmitButton').click(function (event) {
            event.preventDefault();

            if($('#categoriaPrestacionContainer').length) {
                if ($('#categoriaPrestacion').val() === '') {
                    $('#categoriaPrestacionContainer > div > span > span > span').addClass('border-red-fonprecon');
                    $('#categoriaPrestacionError').show();

                    return false;
                }
            }

            var docsValid = 0;
            $('.docs_form_fonprecon').each(function(index, value) {
                if ($('#' + $(this).attr('id')).val() === "" && $('#' + $(this).attr('id')).attr('requerido') === '10') {
                    $('#' + $(this).attr('id') + '_container').addClass('border-red-fonprecon');
                    $('#' + $(this).attr('id') + '_error').show();
                    docsValid++;
                }
            });

            if (docsValid > 0) {
                return false;
            }

            $('#envioData').val('true');

            $(this).submit();
        });
    });
</script>
