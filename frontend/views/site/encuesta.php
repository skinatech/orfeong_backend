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

use yii\helpers\Html;
use yii\bootstrap\ActiveForm;
use frontend\models\Calificacion;


$this->title = 'Encuesta';
$this->params['breadcrumbs'][] = $this->title;

?>

<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/request_password.css">
<link rel="stylesheet" href="<?= Yii::$app->request->baseUrl ?>/css/Anexos.css">
<br>

<div class="wrapper" id="contenedor-form" >

    <?php
    $form = ActiveForm::begin(['id' => 'form-signup']);
    echo Html::hiddenInput('idClienteEncuesta', $idClienteEncuesta);
    echo Html::hiddenInput('idCgEncuesta', $idCgEncuesta);
    ?>

    <h2 class="Registro">Encuesta de satisfacción</h2>
    <div class="col-12 col-sm-12">
        
    <p>A continuación responda una pequeña encuesta que nos ayudará a seguir mejorando nuestro servicio. Seleccione una de las opciones presentadas a continuación para cada una de las preguntas.</p>
       
    <table class="table table-striped">
    <thead>
        <tr class="text-center">
            <th scope="col"></th>
            <th scope="col">Deficiente</th>
            <th scope="col">Regular</th>
            <th scope="col">Bueno</th>
            <th scope="col">Excelente</th>      
        </tr>
    </thead>
    <tbody>    
    
        <?php foreach($preguntas as $pregunta){ ?>
            <tr>
                <td scope="col"><?php echo $pregunta; ?></td>

                <fieldset id="<?php echo $pregunta ?>" name="<?php echo $pregunta ?>">
                    <td scope="col" class="text-center">
                        <input type="radio" value="1" name="<?php echo $pregunta ?>"></td>
                    <td scope="col" class="text-center">
                    <input type="radio" value="2" name="<?php echo $pregunta ?>"></td>
                    </td>
                    <td scope="col" class="text-center">
                        <input type="radio" value="3" name="<?php echo $pregunta ?>"></td>
                    </td>
                    <td scope="col" class="text-center">
                        <input type="radio" value="4" name="<?php echo $pregunta ?>"></td>
                    </td>                    
                </fieldset>               
            </tr>
        <?php } ?>

    </tbody>
    
    </table>

    <div class="container-login100-form-btn">
        <?= Html::submitButton('Enviar encuesta', ['class' => 'btn btn-warning', 'name' => 'login-button']) ?>
    </div>  
    
    </div>

    <?php ActiveForm::end(); ?>
</div>
