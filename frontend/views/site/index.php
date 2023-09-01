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

use kartik\form\ActiveForm;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Página Pública de Pqrs';
?>

<style>
    .bootbox {
        left: 0%; 
    }
    /* .toolbar-hide{
        background: #000;
        height: 50px;
        width: 31%;
        position: absolute;
        top: 0;
        left: 0;
        margin: 1rem 50rem 0;
        display: block;
    } */

    .slider-area .single-slider{
        z-index: 0;
    }

    .slider-area .single-slider .slider-content{
        z-index: 0;
    }
    .modal.show .modal-dialog {
        -webkit-transform: translate(0, 20%)!important;
        transform: translate(0, 20%)!important;
    }
</style>


<!-- Imagenes Carousel -->
<div class="DivPrincipal">
    <!-- Carousel -->
    <div id="carouselExampleIndicators" class="carousel slide" data-ride="carousel">

        <div class="carousel-inner">
            <div class="carousel-item active">
                <?php echo Html::img('@web/img/aero/foto1.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Primer slide']); ?>
            </div>
            <div class="carousel-item">
                <?php echo Html::img('@web/img/aero/foto2.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Segundo slide']); ?>
            </div>
            <div class="carousel-item">
                <?php echo Html::img('@web/img/aero/foto3.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Tercero slide']); ?>
            </div>
            <div class="carousel-item">
                <?php echo Html::img('@web/img/aero/foto4.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Cuarto slide']); ?>
            </div>
            <div class="carousel-item">
                <?php echo Html::img('@web/img/aero/foto5.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Quinto slide']); ?>
            </div>
            <div class="carousel-item">
                <?php echo Html::img('@web/img/aero/foto6.jpg', ['class' => 'd-block w-100 ImgCarousel', 'alt' => 'Sexto slide']); ?>
            </div>
        </div>

        <a class="carousel-control-prev" href="#carouselExampleIndicators" role="button" data-slide="prev">
            <span class="carousel-control-prev-icon"  aria-hidden="true" style="background-color: transparent;"></span>
            <span class="sr-only">Previous</span>
        </a>

        <a class="carousel-control-next" href="#carouselExampleIndicators" role="button" data-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true" style="background-color: transparent;"></span>
            <span class="sr-only">Next</span>
        </a>

    </div>

    <!-- slider-area-start -->
    <div class="slider-area">
        <div class="single-slider">
            <div class="container">
                <div class="row">
                    <div class="col-xl-5 offset-xl-1 col-lg-5">
                        <div class="slider-content">
                            <i class="fas fa-map-marker-alt IconLocation"></i> <?php echo Yii::$app->params['cliente']; ?>
                            <h3>Consulta pública de documentos y PQR's </h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- slider-area-end -->
</div>


<!-- Documentos Publicos -->
<!--
<div id="doc_publicos" class="service-area">

    <div class="container" style="max-width: 90%;">
        <div class="row align-items-center justify-content-center">
            <div class="section-title text-center mb-65">
                <span>CONSULTA PÚBLICA DE DOCUMENTOS</span>
                <h3>Últimos Documentos</h3>
            </div>
        </div>
        <div class="row">
            
            <?php foreach ($model as $key => $data) { ?>

                <div class="col-xl-3 col-md-3 col-lg-3">
                    <div class="single-service">
                        <small>
                            <?php // $Link = Yii::$app->urlManager->createAbsoluteUrl(['publica/publica', 'Radicado[radi_nume_radi]' => $Radi['NoRadicado']]); ?>

                            <div><h5><b>Radicado Nº: </b> <?= $data['numeroRadicado'] ?></h5></div>

                            Expediente: <?= $data['numeroExpediente'] ?>

                            <div class="service-thumb flex-center-div">
                                <a class="ver_radicado w-100 m-auto text-center" name="ver_radicado" data-extension="<?php echo $data['extension']; ?>" data-num-radicado="<?php echo $data['numeroRadicado']; ?>" data-contenido="<?php echo $data['data']; ?>" data-nombre-archivo="<?php echo $data['nombreArchivo']; ?>">   
                                    <?php echo Html::img('@web/img/aero/docs.png', ['class' => '', 'alt' => '']); ?>
                                    <i class="far fa-eye ver_doc d-none"></i>
                                </a>
                            </div>

                            <div><b> Tipo de Solicitud: </b><?= $data['tipoDocumental'] ?></div>
                            <div class="asunto_publico"><b>Asunto:</b> <?= $data['asunto'] ?> </div>
                            
                        </small>
                        <br/><br/>
                    </div>
                </div>

            <?php } ?>

        </div>
    </div>

</div>
-->
<!-- Fin Documentos Publicos -->




