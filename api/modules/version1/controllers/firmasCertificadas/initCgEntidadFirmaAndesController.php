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

namespace api\modules\version1\controllers\firmasCertificadas;

use Yii;
use api\components\HelperLanguageSelector;
use api\components\HelperEncryptAes;
use api\components\HelperLog;
use api\components\HelperValidatePermits;
use api\components\HelperDynamicForms;
use api\components\HelperPlantillas;

use api\models\InitCgConfigFirmas;
use api\models\UserDetalles;
use api\models\RadiDocumentosPrincipales;
use api\models\InitCgCordenadasFirma;
use api\models\RadiRadicados;
use api\models\GdTrdDependencias;
use api\models\User;
use api\models\CgTransaccionesRadicados;
use api\models\PDFInfo;
use api\models\CgNumeroRadicado;

use api\modules\version1\controllers\radicacion\RadicadosController;

use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\data\ActiveDataProvider;

use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;

use Imagine\Gd;
use Picqer\Barcode\BarcodeGeneratorPNG;
use PhpOffice\PhpWord\TemplateProcessor;


/**
 * initCgEntidadFirmaAndesController implements the CRUD actions for initCgEntidadesFirma model.
 */
class initCgEntidadFirmaAndesController extends Controller
{

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        $behaviors = parent::behaviors();
        unset($behaviors['authenticator']);
        return [
            [
                'class' => 'yii\filters\ContentNegotiator',
                'only' => ['create', 'index', 'update'],
            ],
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                ],
            ],
            'authenticator' => [
                'class' => CompositeAuth::className(),
                'authMethods' => [
                    HttpBasicAuth::className(),
                    HttpBearerAuth::className(),
                    QueryParamAuth::className(),
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'index'  => ['GET'],
                    'index-one'  => ['GET'],
                    'view'  => ['GET'],
                    'create'  => ['POST'],
                    'update'  => ['PUT'],
                    'change-status'  => ['PUT'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /** Se recibe como parametro el $documento, el cual es asociado al documento que se va a firmar **/
    static public function RutinaFirmarDocumento($request){

        $valores = [];
        $todasPaginas = true; 

        $modelUser = User::findOne(['accessToken' => $request['valorAuthorization']]);
        $modelUserDetalle = UserDetalles::findOne(['idUser' => $modelUser->id]);        
        $modelConfigFirmas = InitCgConfigFirmas::findAll(['idInitCgEntidadFirma' => $request['selectedoption']]);
        $modelDocumentos = RadiDocumentosPrincipales::findOne(['idradiDocumentoPrincipal'=>$request['ButtonSelectedData'][0]['id']]);        
        $modelCordenadas = $request['moduleForm']['coordenadasFirma'];
        $modelRadicado = RadiRadicados::findOne(['idRadiRadicado'=> $modelDocumentos->idRadiRadicado]);
        $gdTrdDependencias = GdTrdDependencias::findOne(['idGdTrdDependencia' => $modelRadicado->idTrdDepeUserTramitador]);
        $idTransacion = CgTransaccionesRadicados::findOne(['actionCgTransaccionRadicado' => 'signDocument']);
        $radiplantilla = RadiRadicados::find()->where(['idRadiRadicado' => $modelDocumentos->idRadiRespuesta])->one();

        // error_log(' #### ('.__LINE__.')'.$request['ButtonSelectedData'][0]['id']);

        // Se lee la configuración guardada
        foreach($modelConfigFirmas as $val){
            $valores[$val->initCgParamsFirma0->variableInitCgParamFirma] = $val->valorInitCgConfigFirma;
        }

        $pruebas = $valores['pruebas'];
        $usuario_conexion = $valores['usuario_conexion'];
        $password_conexion = $valores['password_conexion'];
        $mostrar_firma = $valores['mostrar_firma'];
        $ubicacion_jar = $valores['ubicacion_jar'];
        $imagenFirma = $modelUserDetalle->firma;
        $mostrar_imagen_firma = $valores['imagen_firma'];
        $documentoFirmar = $modelDocumentos->rutaRadiDocumentoPrincipal;
        $nombreValido = trim(strtoupper($modelDocumentos->nombreRadiDocumentoPrincipal));
        $nombreArchivo = 'doc-'.$modelRadicado->numeroRadiRadicado;
        $codigoGdTrdDependencia = $gdTrdDependencias->codigoGdTrdDependencia;
        $pagina = $request['moduleForm']['numeroPaginaFirma'];

        // Si el valor de (pruebas) es true tomar los usuarios de la configuración de lo contrario los datos que llegan
        if($pruebas === true){
            $usuarioFirma = $usuario_conexion;
            $passwordFirma = $password_conexion;
            $documento = '79436464';
	    }
        else{
            $usuarioFirma = $request['moduleForm']['user'];
            $passwordFirma = $request['moduleForm']['passUser'];
            $documento = $usuarioFirma;
        }

        // Ruta donde se va a guardar el archivo firmado
        $rutaPdfCombinacion = Yii::getAlias('@webroot')
            . "/" . Yii::$app->params['bodegaRadicados']
            . "/" . date("Y")
            . "/" . $codigoGdTrdDependencia
        . "/" ;

        $transaction = Yii::$app->db->beginTransaction();

        // Cuando el documento llega en word porque se hizo combinación de correspondencia pero sin firmas
        if($modelDocumentos->estadoRadiDocumentoPrincipal == Yii::$app->params['statusDocsPrincipales']['CombinadoSinFirmas']){
            // Se procesa el documento word para poner el codigo de barras y el radicado correspondiente al documento
            $HelperPlantillas= new HelperPlantillas($documentoFirmar);
            $documentoFirmar = $HelperPlantillas->TemplateProcessorSinVariables($HelperPlantillas, $radiplantilla, $documentoFirmar, $modelDocumentos, $gdTrdDependencias, $modelUser, $modelUserDetalle);
            // error_log(' ####  '.$documentoFirmar);

            $modelDocumentos->rutaRadiDocumentoPrincipal      = $documentoFirmar;
            $modelDocumentos->extensionRadiDocumentoPrincipal = 'pdf';
            $modelDocumentos->save();
        }

        // Se va a obtener el numero de hojas que tiene el documento para compararlo con el que llegue
        $infopdfDoc = $rutaPdfCombinacion.$nombreArchivo.'-'.$modelDocumentos->idradiDocumentoPrincipal.'.pdf';
        $infoPdfDocNum = $modelDocumentos->rutaRadiDocumentoPrincipal;

        $pdf = new PDFInfo($infoPdfDocNum);

        // Si el número de pagina que llegue es mayor a la totalidad de hojas del documento genera error
        if((int) $pagina > (int) $pdf->pages){
            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => '999|La pagina en la que desea firmar no existe, el documento solo tiene '.$pdf->pages.' paginas',
                'status' => 999,
                'log' => [],
            ];
            return $response;
        }

        $idradiDocumentoPrincipal = $modelDocumentos->idradiDocumentoPrincipal+1;

        $nombre_fichero = $rutaPdfCombinacion.$nombreArchivo.'-'.$idradiDocumentoPrincipal.'.pdf';

        // Si en la configuración de la base de datos dice que si mostrar la imagen de la firma hacer
        // este proceso de lo contrario no se pasan lo valores
        if($mostrar_imagen_firma === true){
            $ponerImagen = ' --imagenFirma '.$imagenFirma;
        }
        else{
            $imagenFirma = Yii::getAlias('@api/web/img/logo.png');
            $ponerImagen = '  ';
        }

        $array_medidas_img = self::redimensionar($imagenFirma, 120);
        $cordenadasImg = ','.$array_medidas_img[0].','.round($array_medidas_img[1]);

        // *** OJO .. tiene cableado el acceso a los webservices.. eventualmente toca formalizar
	    // esto .. pero esta para entrar en produccion  JaGo f24cUSSERe
        $firmado = shell_exec('/usr/local/java/jre1.8.0_341/bin/java -jar '.$ubicacion_jar.'AndesSCDFirmador.jar --metodofirma ws --formatofirma pdf --login JaGo --password f24cUSSERe --tipodocumento 1 --documento '.$documento.' --pinfirma '.$passwordFirma.' --entrada '.$documentoFirmar.' --formatoentrada archivo --salida '.$nombre_fichero.' --formatosalida archivo --visible '.$mostrar_firma.' '.$ponerImagen.' --ubicacion '.$modelCordenadas.''.$cordenadasImg.' --pagina '.$pagina.' --tamanofuentefirma 7 --proteger false --passpdf xxx --test '.$pruebas.' pause');
        // error_log(' #### ('.__LINE__.')'.' /usr/local/java/jre1.8.0_341/bin/java -jar '.$ubicacion_jar.'AndesSCDFirmador.jar --metodofirma ws --formatofirma pdf --login JaGo --password f24cUSSERe --tipodocumento 1 --documento '.$documento.' --pinfirma '.$passwordFirma.' --entrada '.$documentoFirmar.' --formatoentrada archivo --salida '.$nombre_fichero.' --formatosalida archivo --visible '.$mostrar_firma.' '.$ponerImagen.' --ubicacion '.$modelCordenadas->cordenadasInitCgCordenadaFirma.''.$cordenadasImg.' --pagina '.$pagina.' --tamanofuentefirma 7 --proteger false --passpdf xxx --test '.$pruebas.' pause');

        $firmado = json_decode($firmado);

        if(!$firmado){

            if(file_exists($nombre_fichero)){
                # Se crea un nuevo registro del documento con extensión docx
                $model = new RadiDocumentosPrincipales;
                $model->idRadiRadicado                  = $modelRadicado->idRadiRadicado;
                $model->idUser                          = $modelUserDetalle->idUser;
                $model->nombreRadiDocumentoPrincipal    = $nombreValido;
                $model->rutaRadiDocumentoPrincipal      = $nombre_fichero;
                $model->extensionRadiDocumentoPrincipal = 'pdf';
                $model->imagenPrincipalRadiDocumento    = Yii::$app->params['statusTodoText']['Activo'];
                $model->tamanoRadiDocumentoPrincipal    = "'".filesize($nombre_fichero)."'";
                $model->creacionRadiDocumentoPrincipal  = date("Y-m-d H:i:s");
                $model->estadoRadiDocumentoPrincipal    = Yii::$app->params['statusDocsPrincipales']['firmadoDigitalmente'];

                $observacion = 'El usuario '.$modelUserDetalle->nombreUserDetalles.' '.$modelUserDetalle->apellidoUserDetalles.' ha firmado digitalmente el documento '.$nombreValido;

                if(!$model->save()){
                    return $model->getErrors();
                }               

                /***    Log de Radicados  ***/
                $HelperLog = HelperLog::logAddFiling(
                    $modelUser->id, //Id user
                    $modelUser->idGdTrdDependencia, // Id dependencia
                    $modelRadicado->idRadiRadicado, //Id radicado
                    $idTransacion->idCgTransaccionRadicado,
                    $observacion, //texto para almacenar en el evento
                    $modelRadicado,
                    array() //No validar estos campos
                );
                /***  Fin  Log de Radicados  ***/ 

                $transaction->commit();

            }else{

                $transaction->rollBack();

                Yii::$app->response->statusCode = 200;
                $response = [
                    'message' => Yii::t('app', 'errorValidacionDocFirmado'),
                    'status' => Yii::$app->params['statusErrorValidacion'],
                    'log' => [],
                ];
                return $response;

            }

            Yii::$app->response->statusCode = 200;
            $response = [
                'message' => 'ok',
                'status' => Yii::$app->response->statusCode,
                'log' => $HelperLog,
            ];
            return $response;
            
        }else{

            switch($firmado->estado){

                case "27": 

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $firmado->mensaje,
                        'status' => $firmado->estado,
                        'log' => [],
                    ];
                    return $response;
    
                break;
                case "23": 

                    Yii::$app->response->statusCode = 200;
                    $response = [
                        'message' => $firmado->mensaje,
                        'status' => $firmado->estado,
                        'log' => [],
                    ];
                    return $response;
    
                break;
            }
        }  
    }

    static public function redimensionar($src, $ancho_forzado){
        if (file_exists($src)) {
           list($width, $height, $type, $attr)= getimagesize($src);
           if ($ancho_forzado > $width) {
              $max_width = $width;
           } else {
              $max_width = $ancho_forzado;
           }
           $proporcion = $width / $max_width;
           if ($proporcion == 0) {
              return -1;
           }
           $height_dyn = $height / $proporcion;
        } else {
           return -1;
        }
        return array($max_width, $height_dyn);
    }

}
