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

/* @var $this \yii\web\View */
/* @var $content string */

use api\models\ClientesCiudadanosDetalles;
use yii\helpers\Html;
use yii\bootstrap\Nav;
use yii\bootstrap\NavBar;
use yii\widgets\Breadcrumbs;
use frontend\assets\AppAsset;
use common\widgets\Alert;
use yii\helpers\Url;
use yii\bootstrap\ActiveForm;

AppAsset::register($this);
if (!Yii::$app->user->isGuest) {
    $model_clientes = ClientesCiudadanosDetalles::findOne(['idUser' => Yii::$app->user->identity->id]);
}
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
    <head>
        <meta charset="<?= Yii::$app->charset ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <?php $this->registerCsrfMetaTags() ?>
        <title><?= Html::encode($this->title) ?></title>
	
        <link rel="shortcut icon" href="<?php echo Yii::$app->request->baseUrl; ?>/img/logo-aeronautica.png" type="image/x-icon" />
        <?php $this->head() ?>
    </head>
    <body>
        <?php $this->beginBody() ?>


        <!-- header-start -->
        <header>
            <div class="header-area ">
                <div class="bg-primary">
                    <div class="container-xl logo_menu_superior"><!--pos=1--><!--begin-box:MT_tr_linkGovCo::11665:Caja en blanco--><!--loc('* Código HTML libre dentro de la página.')--><div class="header-govco">
                        <a href="https://www.gov.co/" target="blank" title="Ir al portal GOV.CO">
                            <img src="<?php echo Yii::$app->request->baseUrl; ?>/img/header_govco.png" alt="Imagen logo GovCo">                                
                            <span class="sr-only">Logo Gobierno de Colombia</span>
                        </a>
                    </div><!--end-box-->
                </div>
                <div id="sticky-header" class="main-header-area white-bg">
                    <div class="container">
                        <div class="row align-items-center" style="height: 105px;">

                            <div class="col-xl-2 col-lg-2">
                                <div class="logo-img" style="margin-top: -27px; margin-left: -40px;">
                                    <a href="<?= Yii::$app->homeUrl ?>">
                                        <img src="<?php echo Yii::$app->request->baseUrl; ?>/img/logo.png" class="logoApartado" alt="">
                                    </a>
                                </div>
                            </div>

                            <div class="col-xl-7 col-lg-5">
                                <div class="main-menu d-none d-lg-block">
                                    <nav>
                                        <ul id="navigation">
                                            <!-- <li><a class="<?php //echo Url::current()==Url::toRoute('site/index')? 'active':''?>"  href="<?php echo Url::toRoute('site/index'); ?>">Inicio</a></li> -->
                                            <?php
                                            if (Yii::$app->user->isGuest) {
                                                ?>
                                                <li><a class="<?php echo Url::current()==Url::toRoute('site/login')? 'active':''?>" href="<?php echo Url::toRoute('/site/login'); ?>">Consultar PQRSD</a></li>
                                                <li><a class="<?php echo Url::current()==Url::toRoute('site/signup')? 'active':''?>" href="<?php echo Url::toRoute('/site/signup'); ?>" style="background: #365c8a; color: aliceblue;">Regístrar PQRSD</a></li>
                                                <!-- <li><a href="<?php echo Url::toRoute('/usuarios/index'); ?>">Usuarios</a></li> -->
                                                <?php
                                            } else {
                                                ?>
                                                <!--li><a href="<?php echo Url::toRoute('/consulta/index'); ?>">  </a></li-->
                                                <li><a class="<?php echo Url::current()==Url::toRoute('consulta-pqrs/index')? 'active':'' ?>" href="<?php echo Url::toRoute('/consulta-pqrs/index') ?>">Consultar PQRSD</a></li>
                                                <li><a class="<?php echo Url::current()==Url::toRoute('registro-pqrs/index')? 'active':''?>" href="<?php echo Url::toRoute('/registro-pqrs/index') ?>" style="background: #365c8a; color: aliceblue;">Regístrar PQRSD</a></li>
                                                <li class="responsive">  
                                                    <?=
                                                    Html::a('<i class="fas fa-sign-out-alt"></i> Cerrar Sesión', ['/site/logout'], ['class' => 'btn btn-link logout'], //optional* -if you need to add style
                                                            ['data' => ['method' => 'post',]])
                                                    ?> 
                                                </li> 
                                                <?php
                                            }
                                            ?>
                                        </ul> 
                                    </nav>
                                </div>
                            </div>

                            <!-- <div class="col-xl-2 col-lg-2">
                                <div id="help">
                                    <i class="fas fa-chalkboard-teacher help" style="font-size: x-large;float: right; cursor: pointer;" title="Ayuda PQR'S"></i>
                                </div>
                            </div> -->

                            <div class="col-xl-3 col-lg-5">
                                <div class="quote-area">
                                    
                                    <!-- <div class="search-bar">
                                        <a id="search_1" href="javascript:void(0)"><i class="fa fa-search"></i></a>
                                    </div> -->
                                    
                                    <?php if (!Yii::$app->user->isGuest) { ?>

                                        <div class="flex-user d-none d-lg-block ">
                                            <i class="far fa-user-circle icon-user"></i>
                                            <div class="cont-inf-user d-none d-lg-block">
                                                <p class="name-user">
                                                    <?= $model_clientes->cliente->nombreCliente ?? '' ?>
                                                </p>


                                                <button type="button" class="dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                    <span class="sr-only">Toggle Dropdown</span>
                                                </button>

                                                <div class="dropdown-menu fixed-user">
                                                    <?=
                                                    Html::a('<i class="fas fa-sign-out-alt cerrar-session"></i> cerrar sesión', ['/site/logout'], ['class' => ''], //optional* -if you need to add style
                                                            ['data' => ['method' => 'post',]])
                                                    ?> 
                                                </div>
                                            </div>
                                        </div>

                                    <?php } else { ?>

                                        <!-- <div class="get-quote d-none d-lg-block">
                                            <button class="boxed-btn" name="botonregistropqrs" id='botonregistropqrs'>Registrar PQR'S</button>
                                        </div> -->

                                    <?php } ?>

                                </div>
                            </div>

                            <div class="col-12">
                                <div class="mobile_menu d-block d-lg-none"></div>
                            </div>

                        </div>
                        <div class="search_input" id="search_input_box" style="display:none">

                            <div class="container radius-buscador">
                                <div class="row">
                                    <?php $form = ActiveForm::begin(['id' => 'form-search-radi', 'action' => Yii::$app->urlManager->createUrl('/site/index'), 'method' => 'GET']); ?>

                                    <div class="col-12 col-sm-12 col-xl-12" id="search">
                                        <input type="hidden" name="<?= urlencode("%23") ?>" value="doc_publicos">
                                        <input type="text" name="key" id="search_input" class="form-control" placeholder="Búsqueda de documentos públicos" autofocus="false">
                                        <br/>
                                    </div>
                                    <?php ActiveForm::end(); ?>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </header>
        
        <script
            src="https://code.jquery.com/jquery-3.4.1.min.js"
            integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo="
            crossorigin="anonymous">
        </script>
        <!-- header-end -->

        <div class="wrap">
            <div class="container">
                <?=
                Breadcrumbs::widget([
                    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
                ])
                ?>
                <?= Alert::widget() ?>
                <?= $content ?>
            </div>
        </div>

        <!-- footer-start -->
        <footer class="footer-area ">
            <div class="container-fluid">
                <!--<div class="row justify-content-center">-->
                    <div class="col-lg-12">
                        <div class="copyright_part_text text-center">
                            <p class="footer-text m-0">
                                Copyright &copy;<script>document.write(new Date().getFullYear());</script> 
                            </p>
                        </div>
                    </div>
                <!--</div>-->
            </div>
        </footer>
        <!-- footer-end -->
        <?php $this->endBody() ?>
    </body>
</html>
<?php $this->endPage() ?>
<script>
    $('#botonregistropqrs').on('click', function () {
        $("#registropqrs").modal("show");
    });
</script>
