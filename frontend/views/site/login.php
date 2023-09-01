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
/* @var $model \common\models\LoginForm */

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use yii\helpers\Url;

$this->title = 'Login';
$this->params['breadcrumbs'][] = $this->title;

?>

<!--  Pendientes
<?= Html::a('reset it', ['site/request-password-reset']) ?> <br>
<?= Html::a('Resend', ['site/resend-verification-email']) ?> 

-->
<div class="limiter">
    <div class="container-login100">
        <div class="row form-group">
            <div class="login100-form validate-form">
                <?php $form = ActiveForm::begin(['id' => 'form-login', 'class' => 'login100-form validate-form']); ?>
           
                <span class="login100-form-title p-b-43">Consulta de PQRS </span>

                <div class="get-quote d-none d-lg-block" style="text-align: center;">
                    <a class="boxed-btn registropqr" href="<?php echo Url::toRoute('/site/login-automatico-consulta') ?>">
                        <label class="text-center pt-3" style="color: white; font-size: unset; margin: auto; margin-top: -11px;"> Consulta Anónimo </label>
                    </a>
                </div><br>

                <div class="wrap-input100 validate-input mb-5" data-validate = "Valid email is required: ex@abc.xyz">
                    <?= $form->field($model, 'username')->textInput(['class' => 'input100', 'placeholder' => 'Número Identificación']) ?>
                </div>
                <!-- <div class="wrap-input100 validate-input mb-5" data-validate="Password is required">
                    <?= $form->field($model, 'password')->hiddenInput(['class' => 'input100', 'placeholder' => 'Contraseña']) ?>
                </div> -->
                <div class="flex-sb-m w-full p-t-3 p-b-32">
                    <!-- <?= $form->field($model, 'rememberMe')->checkbox() ?> -->
                    
                    <!-- <div class="pt-4 mt-3">
                        <a href="#" class="">
                            <?= Html::a('¿Olvidaste la contraseña?', ['site/request-password-reset'], ['class' => 'txt1']) ?> <br>
                        </a>
                    </div>                    -->
                </div>               
                <div class="container-login100-form-btn">
                    <?= Html::submitButton('Consultar PQRS', ['class' => 'login100-form-btn', 'name' => 'login-button']) ?>
                </div>
                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>


