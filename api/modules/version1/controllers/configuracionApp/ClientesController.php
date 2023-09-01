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

namespace api\modules\version1\controllers\configuracionApp;

use Yii;
use yii\filters\auth\CompositeAuth;
use yii\filters\auth\HttpBasicAuth;
use yii\filters\auth\HttpBearerAuth;
use yii\filters\auth\QueryParamAuth;
use yii\filters\VerbFilter;
use yii\db\ExpressionInterface;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\db\ActiveQuery;

use api\components\HelperEncryptAes;
use api\components\HelperLanguageSelector;
use api\components\HelperValidatePermits;
use api\models\TiposPersonas;
use api\models\Clientes;

use api\modules\version1\controllers\correo\CorreoController;
use yii\data\ActiveDataProvider;

/**
 * ClientesController implements the CRUD actions for Clientes model.
 */
class ClientesController extends Controller
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
                    'index' => ['GET'],
                    'index-one' => ['GET'],
                    'view' => ['GET'],
                    'create' => ['POST'],
                    'update' => ['PUT'],
                    'change-status' => ['PUT'],
                    'verificar-correo-cliente' => ['GET'],
                    'verificar-identificacion-cliente' => ['GET'],
                ],
            ],
        ];
    }

    public function init()
    {
        parent::init();
        HelperLanguageSelector::getPreferredLanguage();
    }

    /**
     * Lists all Clientes models.
     * @return mixed
     */
    public function actionIndex()
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Clientes::find(),
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Clientes model.
     * @param integer $idCliente
     * @param integer $idTipoPersona
     * @param integer $idNivelGeografico3
     * @param integer $idNivelGeografico2
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2)
    {
        return $this->render('view', [
            'model' => $this->findModel($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2),
        ]);
    }

    /**
     * Creates a new Clientes model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Clientes();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'idCliente' => $model->idCliente, 'idTipoPersona' => $model->idTipoPersona, 'idNivelGeografico3' => $model->idNivelGeografico3, 'idNivelGeografico2' => $model->idNivelGeografico2]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Clientes model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $idCliente
     * @param integer $idTipoPersona
     * @param integer $idNivelGeografico3
     * @param integer $idNivelGeografico2
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2)
    {
        $model = $this->findModel($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'idCliente' => $model->idCliente, 'idTipoPersona' => $model->idTipoPersona, 'idNivelGeografico3' => $model->idNivelGeografico3, 'idNivelGeografico2' => $model->idNivelGeografico2]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Clientes model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $idCliente
     * @param integer $idTipoPersona
     * @param integer $idNivelGeografico3
     * @param integer $idNivelGeografico2
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2)
    {
        $this->findModel($idCliente, $idTipoPersona, $idNivelGeografico3, $idNivelGeografico2)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Clientes model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $idCliente
     * @return Clientes the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function findModel($idCliente)
    {
        if (($model = Clientes::findOne($idCliente)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }

    public static function verificarCliente($numeroDocumentoCliente, $correoElectronicoCliente, $validateCorreo = true)
    {
        $cliente = Clientes::find()->where(['numeroDocumentoCliente' => $numeroDocumentoCliente]);
        
        if ($validateCorreo && $correoElectronicoCliente != '') {
            $cliente->orWhere(['correoElectronicoCliente' => $correoElectronicoCliente]);
        }

        $cliente = $cliente->one();

        if ($cliente == null) {
            return [
                'status' => 'false',
                'response' => 'No existe el cliente',
                'idCliente' => ''
            ];
        }else{
            return [
                'status' => 'true',
                'response' => 'Si existe el cliente',
                'idCliente' => $cliente->idCliente
            ];
        }       
    }


}
