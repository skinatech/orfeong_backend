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

$this->title = 'Registro Peticiones, Quejas, Reclamos, Sugerencias, Solicitud de Información pública, Denuncias y Trámites Prestacionales';
$this->params['breadcrumbs'][] = $this->title;
?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">

<br />

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
                <?php
                if ($id_tipo_tramite_actual !== "0") {
                ?>
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
                    <div class="col-12 col-sm-6 form-holder">
                        <label class="pb-3">Clasificación de la solicitud</label>
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
                <?php
                }
                ?>
            </div>
        </section>
    <?php ActiveForm::end(); ?>
</div>

<input type="hidden" id="idTipoTramitePqrs" value="<?= Yii::$app->params['formsRegistroPqrsText']['actionIndexDefault']; ?>" />
<input type="hidden" id="idTipoTramiteActual" value="<?= $id_tipo_tramite_actual; ?>" />

<script type="text/javascript">

    $(document).ready(function () {
        if ($('#idTipoTramiteActual').val() !== "0") {
            $("#tipoTramite option[value='<?= $id_tipo_tramite_actual; ?>']").attr("selected", true);
        }

        $('#tipoTramite').on('change', function() {
            $('#formPqrsTipoTramite').submit();
        });

        $('#tipoSolicitud').on('change', function() {
            $('#formPqrsTipoTramite').submit();
        });
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
