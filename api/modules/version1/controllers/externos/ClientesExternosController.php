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

namespace api\modules\version1\controllers\externos;

use yii\web\Controller;
use Yii;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\auth\CompositeAuth;
use api\components\HelperExternos;

/*** Modelos ***/
use api\models\Clientes;
use api\models\NivelGeografico1;
use api\models\NivelGeografico2;
use api\models\NivelGeografico3;

/**
 * Clientes externos controller for the `version1` module
 */
class ClientesExternosController extends Controller
{
    public function behaviors()
    {
        return [
            'corsFilter' => [
                'class' => \yii\filters\Cors::className(),
                'cors' => [
                    'Origin' => ['*'],
                    'Access-Control-Request-Method' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'],
                    'Access-Control-Request-Headers' => ['*'],
                ],
            ],
            'verbs' => [
                'class' => \yii\filters\VerbFilter::className(),
                'actions' => [
                    'clientes-get-all' => ['GET'],
                    'nivel-geografico' => ['GET'],
                    'tipo-persona' => ['GET'],
                    'clientes-create' => ['POST'],
                ],
            ],
        ];
    }


    public function actionClientesGet($documentoIdentidad) {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];
        $nivelGeografico1 = [];
        $nivelGeografico2 = [];
        $nivelGeografico3 = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $modelClientes = Clientes::find()->where(['numeroDocumentoCliente' => $documentoIdentidad])->andWhere(['estadoCliente' => Yii::$app->params['statusTodoText']['Activo']])->all();
            if($modelClientes) {

                $modelNivelGeografico1 = NivelGeografico1::find()->all();
                foreach ($modelNivelGeografico1 as $key => $nivel1) {
                    $nivelGeografico1[$nivel1->nivelGeografico1] = $nivel1->nomNivelGeografico1;
                }

                $modelNivelGeografico2 = NivelGeografico2::find()->all();
                foreach ($modelNivelGeografico2 as $key => $nivel2) {
                    $nivelGeografico2[$nivel2->nivelGeografico2] = $nivel2->nomNivelGeografico2;
                }

                $modelNivelGeografico3 = NivelGeografico3::find()->all();
                foreach ($modelNivelGeografico3 as $key => $nivel3) {
                    $nivelGeografico3[$nivel3->nivelGeografico3] = $nivel3->nomNivelGeografico3;
                }

                foreach ($modelClientes as $key => $cliente) {
                    $data[] = array(
                        "idCliente"                => $cliente->idCliente,
                        "nombreCliente"            => $cliente->nombreCliente,
                        "direccionCliente"         => $cliente->direccionCliente,
                        "documentoIdentidad"       => $cliente->numeroDocumentoCliente,
                        "idNivelGeografico1"       => $cliente->idNivelGeografico1,
                        "idNivelGeografico2"       => $cliente->idNivelGeografico2,
                        "idNivelGeografico3"       => $cliente->idNivelGeografico3,
                        "nivelGeografico1"         => $nivelGeografico1[$cliente->idNivelGeografico1],
                        "nivelGeografico2"         => $nivelGeografico2[$cliente->idNivelGeografico2],
                        "nivelGeografico3"         => $nivelGeografico3[$cliente->idNivelGeografico3],
                        "correoElectronicoCliente" => $cliente->correoElectronicoCliente
                    );
                }

            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'data' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionClientesCreate() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];
        $nivelGeografico1 = [];
        $nivelGeografico2 = [];
        $nivelGeografico3 = [];
        $errors = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $jsonData = Yii::$app->request->post('jsonData');
            $request = json_decode($jsonData, true);

            if(isset($request['correoElectronicoCliente'])) {
                $clienteCorreoValida = Clientes::find()->where(['correoElectronicoCliente' => $request['correoElectronicoCliente']])->one();
                if($clienteCorreoValida) {
                    $response = [
                        'message' => 'Correo cliente ya se encuentra registrado',
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];

                    return $response;
                }
            }

            if(isset($request['numeroDocumentoCliente'])) {
                $clienteDocumentoValida = Clientes::find()->where(['numeroDocumentoCliente' => $request['numeroDocumentoCliente']])->one();
                if($clienteDocumentoValida) {
                    $response = [
                        'message' => 'Documento cliente ya se encuentra registrado',
                        'data' => [],
                        'status' => Yii::$app->params['statusErrorValidacion'],
                    ];

                    return $response;
                }
            }

            $model = new Clientes();
            $model->attributes = $request;
            if(!$model->save()) {

                $errors = $model->getErrors();

                $response = [
                    'message' => Yii::$app->params['errorProveedorExterno'],
                    'data' => $errors,
                    'status' => Yii::$app->params['statusErrorValidacion'],
                ];

            } else {

                $modelNivelGeografico1 = NivelGeografico1::find()->all();
                foreach ($modelNivelGeografico1 as $key => $nivel1) {
                    $nivelGeografico1[$nivel1->nivelGeografico1] = $nivel1->nomNivelGeografico1;
                }

                $modelNivelGeografico2 = NivelGeografico2::find()->all();
                foreach ($modelNivelGeografico2 as $key => $nivel2) {
                    $nivelGeografico2[$nivel2->nivelGeografico2] = $nivel2->nomNivelGeografico2;
                }

                $modelNivelGeografico3 = NivelGeografico3::find()->all();
                foreach ($modelNivelGeografico3 as $key => $nivel3) {
                    $nivelGeografico3[$nivel3->nivelGeografico3] = $nivel3->nomNivelGeografico3;
                }

                $data[] = array(
                    "idCliente"                => $model->idCliente,
                    "nombreCliente"            => $model->nombreCliente,
                    "direccionCliente"         => $model->direccionCliente,
                    "idNivelGeografico1"       => $model->idNivelGeografico1,
                    "idNivelGeografico2"       => $model->idNivelGeografico2,
                    "idNivelGeografico3"       => $model->idNivelGeografico3,
                    "nivelGeografico1"         => $nivelGeografico1[$model->idNivelGeografico1],
                    "nivelGeografico2"         => $nivelGeografico2[$model->idNivelGeografico2],
                    "nivelGeografico3"         => $nivelGeografico3[$model->idNivelGeografico3],
                    "correoElectronicoCliente" => $model->correoElectronicoCliente
                );

                $response = [
                    'message' => Yii::$app->params['successProveedorExterno'],
                    'data' => $data,
                    'status' => 200
                ];

            }

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'data' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionNivelGeografico() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            $modelNivelGeografico3 = NivelGeografico3::find()->where(['estadoNivelGeografico3' => Yii::$app->params['statusTodoText']['Activo']])->all();
            foreach ($modelNivelGeografico3 as $key => $nivel3) {
                $data[] = array(
                    'idNivelGeografico3' => $nivel3->nivelGeografico3,
                    'nivelGeografico3' => $nivel3->nomNivelGeografico3,
                    'idNivelGeografico2' => $nivel3->nivelGeografico2->nivelGeografico2,
                    'nivelGeografico2' => $nivel3->nivelGeografico2->nomNivelGeografico2,
                    'idNivelGeografico1' => $nivel3->nivelGeografico2->nivelGeografico1->nivelGeografico1,
                    'nivelGeografico1' => $nivel3->nivelGeografico2->nivelGeografico1->nomNivelGeografico1,
                );
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'data' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

    public function actionTipoPersona() {

        /*** Recupera toda la información que viaja por las cabeceras ***/
        $headers = getallheaders();
        /*** Helper que valida si el token existe y es valido ***/
        $dataHelper = HelperExternos::validateToken($headers);

        /*** Declaraciones ***/
        $data = [];

        if($dataHelper['Authorization'] && $dataHelper['token']) {

            foreach (Yii::$app->params['tipoPersonaText'] as $key => $persona) {
                $data[$key] = $persona;
            }

            $response = [
                'message' => Yii::$app->params['successProveedorExterno'],
                'data' => $data,
                'status' => 200
            ];

            return $response;

        } else {
            $response = [
                'message' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'data' => Yii::$app->params['statusErrorAuthorizationTextProveedorExterno'],
                'status' => Yii::$app->params['statusErrorAuthorization']
            ];

            return $response;
        }
    }

}
