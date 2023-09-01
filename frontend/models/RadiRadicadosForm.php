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

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "radiRadicados".
 *
 * @property int $idRadiRadicado
 * @property string $numeroRadiRadicado numero de radicado asignado por el sistema para el tramite de ese documento
 * @property string $asuntoRadiRadicado asunto asignado al radicado que se esta ingresando al sistema
 * @property string|null $descripcionAnexoRadiRadicado Descripción del anexo
 * @property string|null $foliosRadiRadicado guarda la información de los folios que estan asociados al radicado que se esta generando
 * @property string $fechaVencimientoRadiRadicados
 * @property int $estadoRadiRadicado estado del radicado
 * @property string $creacionRadiRadicado fecha de creación del radicado
 * @property int $idTrdTipoDocumental identificador del tipo docuemntal que se asigna al radicado
 * @property int $user_idCreador Identificador de la tabla user pero asociado al usuario que esta creando el radicado
 * @property int $idTrdDepeUserTramitador Identificador de la tabla de dependencias haciendo referencia a la dependencia a la que pertenece el usuario en cargado de dar tramite al radicado
 * @property int $user_idTramitador Identificador de la tabla user pero haciedo referencia al usuario encargado de tramitar el radicado 
 * @property int $PrioridadRadiRadicados prioridad true = alta, false = baja
 * @property int $idCgTipoRadicado identificador de la tabla tipo de radicado el que nos identifica cual es el tipo de radicado.
 * @property int $idCgMedioRecepcion identificador de la tabla medio de recepción del radicado
 * @property int|null $idTrdDepeUserCreador identificador de la dependencia del usuario creador del radicado 
 * @property int $cantidadCorreosRadicado Conteo de correos del radicado
 * @property string|null $radicadoOrigen Número de radicado origen 
 * @property string|null $fechaDocumentoRadiRadicado Fecha del documento
 * @property string|null $observacionRadiRadicado Observación
 *
 * @property RadiAgendaRadicados[] $radiAgendaRadicados
 * @property RadiDocumentos[] $radiDocumentos
 * @property RadiDocumentosPrincipales[] $radiDocumentosPrincipales
 * @property RadiDocumentosPrincipales[] $radiDocumentosPrincipales0 
 * @property RadiEnvios[] $radiEnvios
 * @property RadiInformados[] $radiInformados
 * @property RadiLogRadicados[] $radiLogRadicados
 * @property CgMediosRecepcion $idCgMedioRecepcion
 * @property CgTiposRadicados $idCgTipoRadicado
 * @property GdTrdDependencias $idTrdDepeUserCreador0
 * @property GdTrdDependencias $idTrdDepeUserTramitador0
 * @property GdTrdTiposDocumentales $idTrdTipoDocumental
 * @property User $userIdCreador
 * @property idTrdDepeUserCreador $idTrdDepeUserCreador
 * @property RadiRadicadoAnulado[] $radiRadicadoAnulados
 * @property RadiRadicadosAsociados[] $radiRadicadosAsociados
 * @property RadiRemitentes[] $radiRemitentes
 * @property GdExpedientesInclusion $GdExpedienteInclusion
 * @property autorizacionRadiRadicados $autorizacionRadiRadicados
 * 
 */
class RadiRadicadosForm extends \yii\db\ActiveRecord
{
    public $nombreRemitente;
    public $autorizacionEnvio;
    public $reCaptcha;
    public $cgFormulariosPqrs;
    public $cgFormulariosPqrsDetalle;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['numeroRadiRadicado', 'asuntoRadiRadicado', 'fechaVencimientoRadiRadicados', 'idTrdTipoDocumental', 'user_idCreador', 'idTrdDepeUserTramitador', 'user_idTramitador', 'idCgTipoRadicado', 'idCgMedioRecepcion'], 'required'],
            [['observacionRadiRadicado'], 'required'],
            [['fechaVencimientoRadiRadicados', 'creacionRadiRadicado', 'fechaDocumentoRadiRadicado'], 'safe'],
            [['estadoRadiRadicado', 'idTrdTipoDocumental', 'user_idCreador', 'idTrdDepeUserTramitador', 'user_idTramitador', 'user_idTramitadorOld', 'PrioridadRadiRadicados', 'idCgTipoRadicado', 'idCgMedioRecepcion', 'idTrdDepeUserCreador', 'cantidadCorreosRadicado', 'autorizacionRadiRadicados'], 'integer'],
            [['numeroRadiRadicado', 'descripcionAnexoRadiRadicado', 'radicadoOrigen' ], 'string', 'max' => 80],
            [['asuntoRadiRadicado'], 'string', 'max' => 4000],
            [['foliosRadiRadicado'], 'string', 'max' => 20],
            [['observacionRadiRadicado'], 'string'],
            [['numeroRadiRadicado'], 'unique']       
            // [['reCaptcha'], \himiklab\yii2\recaptcha\ReCaptchaValidator2::className(), 'uncheckedMessage' => 'Por favor confirma que no eres un robot']   
            // [['reCaptcha'], \kekaadrenalin\recaptcha3\ReCaptchaValidator::className(), 'acceptance_score' => 0]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiRadicado' => 'Id radicado',
            'numeroRadiRadicado' => 'Número radicado',
            'asuntoRadiRadicado' => 'Asunto radicado',
            'descripcionAnexoRadiRadicado' => 'Descripción del anexo',
            'foliosRadiRadicado' => 'Folios radicado',
            'fechaVencimientoRadiRadicados' => 'Fecha vencimiento radicado',
            'estadoRadiRadicado' => 'Estado',
            'creacionRadiRadicado' => 'Creación radicado',
            'idTrdTipoDocumental' => 'Tipo documental',
            'user_idCreador' => 'Usuario creador',
            'idTrdDepeUserTramitador' => 'Dependencia del usuario tramitador',
            'user_idTramitador' => 'Usuario tramitador',
            'user_idTramitadorOld' => 'Usuario anteriormente encargado de tramitar el radicado',            
            'PrioridadRadiRadicados' => 'Prioridad',
            'idCgTipoRadicado' => 'Tipo radicado',
            'idCgMedioRecepcion' => 'Medio de recepción',
            'idTrdDepeUserCreador' => 'Dependencia del usuario creador del radicado',
            'cantidadCorreosRadicado' => 'Conteo de correos del radicado',
            'radicadoOrigen' => 'Radicado origen',
            'fechaDocumentoRadiRadicado' => 'Fecha del documento',
            'observacionRadiRadicado' => 'Observación',
            'autorizacionRadiRadicados' => 'Medio por el cual desea ser notificado',
        ];
    }

}
