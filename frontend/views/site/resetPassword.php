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
/* @var $model \frontend\models\ResetPasswordForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;

$this->title = 'Restablecer la contraseña';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="contenedor-reset-password">
     <!-- Formulario -->
     <h2 class="Recuperacion pt-5 pb-5 text-center"> Restablecer la contraseña </h2>

        <div class="wrapper" id="contenedor-form" >
            <?php $form = ActiveForm::begin(['id' => 'reset-password-form']); ?>
                <div class="row form-group contenedor">

                    <div class="col-12 col-sm-12 text-center ">
                        <label class="mt-3 mb-3">Por favor ingrese su nueva contraseña:</label>
                    </div>  

                    <div class=" col-12 col-sm-12 form-holder">
                        <div class="form-group field-password required">
                            <label for="" class="mb-3">
                                Contraseña
                            </label>
                            <?php // $form->field($model, 'password')->passwordInput(['id' => 'password', 'maxlength' => true]) ?>

                            <input type="password" id="password" class="form-control" name="ResetPasswordForm[password]" aria-required="true" aria-invalid="true">

                            <label id="messange-error-pass" class="help-block help-block-error"></label>
                        </div>
                    </div>	

                    <div class="col-12 col-sm-12 form-holder">
                        <div class="form-group field-confirm-password required">

                            <label for="" class="mb-3">
                                Confirmar Contraseña
                            </label>
                            <input type="password" id="confirmPass" class="form-control">

                            <label id="messange-error" class="help-block help-block-error"></label>

                        </div>
                    </div>

                    <div class="col-3 Send">
                        <?= Html::submitButton('Enviar', ['class' => 'btn btn-block btn-primary-orfeo p-3']) ?>
                    </div>
                </div>  
            <?php ActiveForm::end(); ?>
        </div>



<script>
    
    var submit = false;

    $(document).ready(function () {

        $('#confirmPass').on('change', function () {

            if(PasswordValidacion($('#password').val())){

                $('.field-password').removeClass('has-error');
                $('.field-password').addClass('has-success');
                $('#messange-error-pass').html('');

                if($('#password').val().length < 6){

                    $('.field-password').removeClass('has-success');
                    $('.field-password').addClass('has-error');
                    $('#messange-error-pass').html('Mínimo 6 caracteres');
                    submit = false;

                }else{

                    if ($('#confirmPass').val() != '') {
                        if ($('#confirmPass').val() == $('#password').val()) {
    
                            $('.field-confirm-password').removeClass('has-error');
                            $('.field-confirm-password').addClass('has-success');
                            $('#messange-error').html('');
                            submit = true;
    
                        } else {
    
                            $('.field-confirm-password').removeClass('has-success');
                            $('.field-confirm-password').addClass('has-error');
                            $('#messange-error').html('La contraseña de verificación no coincide');
                            submit = false;
                        }
                    }else{

                        $('.field-confirm-password').removeClass('has-success');
                        $('.field-confirm-password').addClass('has-error');
                        $('#messange-error').html('Este campo no puede estar vacío.');
                        submit = false;

                    }
                }
                
            }else{
                
                $('.field-password').removeClass('has-success');
                $('.field-password').addClass('has-error');
                $('#messange-error-pass').html('La contraseña debe contener al menos 3 de las siguientes opciones: una minúscula, una mayúscula, un número y un caracter especial $@!%*?&¿¡~#_-');
                submit = false;
            }
        });

        $('#password').on('change', function () {

            if(PasswordValidacion($('#password').val())){

                $('.field-password').removeClass('has-error');
                $('.field-password').addClass('has-success');
                $('#messange-error-pass').html('');

                  if($('#password').val().length < 6){

                    $('.field-password').removeClass('has-success');
                    $('.field-password').addClass('has-error');
                    $('#messange-error-pass').html('Mínimo 6 caracteres');
                    submit = false;
                    
                }else{
                
                    if ($('#confirmPass').val() != '') {
                        if ($('#confirmPass').val() == $('#password').val()) {

                            $('.field-confirm-password').removeClass('has-error');
                            $('.field-confirm-password').addClass('has-success');
                            $('#messange-error').html('');
                            submit = true;

                        } else {

                            $('.field-confirm-password').removeClass('has-success');
                            $('.field-confirm-password').addClass('has-error');
                            $('#messange-error').html('La contraseña de verificación no coincide');
                            submit = false;
                        }

                    }else{

                        $('.field-confirm-password').removeClass('has-success');
                        $('.field-confirm-password').addClass('has-error');
                        $('#messange-error').html('Este campo no puede estar vacío.');
                        submit = false;

                    }
                }

            }else{
                
                $('.field-password').removeClass('has-success');
                $('.field-password').addClass('has-error');
                $('#messange-error-pass').html('La contraseña debe contener al menos 3 de las siguientes opciones: una minúscula, una mayúscula, un número y un caracter especial $@!%*?&¿¡~#_-');
                submit = false;
            }

        });

        $('#contenedor-form').on('beforeSubmit', 'form#reset-password-form', function () {

        var form = $(this);
        var cargando;
        var email = $('#email').val();

        if (!$('#password').val()) {
            $('.field-password').removeClass('has-success');
            $('.field-password').addClass('has-error');
            $('#messange-error-pass').html('Este campo no puede estar vacío.');
            
            return false;
        } 
        // return false if form still have some validation errors
        if (form.find('.has-error').length) {
            return false;
        }

        if (submit) {
            document.getElementById('reset-password-form').submit();
        }

        return false;

    });

    });

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

</script>
