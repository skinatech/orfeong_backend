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
namespace api\components;

use Yii;

use api\models\CgGeneralBasesDatos;

/**
 * Helper para validar conexiones a bases de datos
 */
class HelperOtherConnectionsDb {

    /**
     * Funcion de conexion con otras bases de datos
     */
    public static function otherConnectionsDB($newConnection) {
        $modelCgGeneralBasesDatos = CgGeneralBasesDatos::find()
            ->where(['nombreCgGeneralBasesDatos' => $newConnection])
            ->andWhere(['estadoCgGeneralBasesDatos' => Yii::$app->params['statusTodoText']['Activo']])
            ->one();
        $otherConnection = new \yii\db\Connection([
            'dsn' => ''. $modelCgGeneralBasesDatos->dnsCgGeneralBasesDatos .':host='. $modelCgGeneralBasesDatos->hostCgGeneralBasesDatos .';port='. $modelCgGeneralBasesDatos->portCgGeneralBasesDatos .';dbname='. $modelCgGeneralBasesDatos->dbnameCgGeneralBasesDatos .'',
            'username' => $modelCgGeneralBasesDatos->usernameCgGeneralBasesDatos,
            'password' => $modelCgGeneralBasesDatos->passCgGeneralBasesDatos,
        ]);
        return $otherConnection;
    }
}
