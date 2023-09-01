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
    /* @var $model \frontend\models\PasswordResetRequestForm */

    use yii\helpers\Html;
    use yii\helpers\Url;
    use yii\bootstrap\ActiveForm;
    use kartik\select2\Select2;

    $this->title = 'Recuperación de contraseña';
    $this->params['breadcrumbs'][] = $this->title;
?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">

<div class="">
        
        <!-- Formulario -->
        <h2 class="Recuperacion pt-5 pb-5 text-center"> Petición para la recuperación de contraseña </h2>

        <div id="Peticion">
            <?php $form = ActiveForm::begin(['id' => 'form-usuario','action' => Yii::$app->urlManager->createUrl('/site/request-password-reset')]); ?>
                <div class="row form-group contenedor">

                    <div class="col-12 col-sm-10 form-holder">
                        <label class="mt-3 mb-3">Por favor ingrese su correo electrónico y número de documento para restablecer la contraseña.</label>

                        <?= $form->field($model, 'email')->textInput(['maxlength' => true, 'placeholder' => 'correo electrónico']) ?>         
                        <?= $form->field($model, 'username')->textInput(['maxlength' => true, 'placeholder' => 'Número de documento']) ?>
                    </div>

                </div>

                <div class="col-3 Send">
                    <?= Html::submitButton('Enviar', ['class' => 'btn btn-block btn-primary-orfeo p-3']) ?>
                </div>
        
            <?php ActiveForm::end(); ?>      
        </div>
</div>

