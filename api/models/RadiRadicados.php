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

namespace api\models;

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
 * @property string|null $numeroFactura Número de factura
 * @property string|null $numeroContrato Número de contrato
 * @property float|null $valorFactura Valor de la factura
 * @property int $numeroCuenta nuemero de cuenta
 * @property int|null $idGdTrdSerie Serie
 * @property int|null $idGdTrdSubserie Subserie
 * @property string $cargoFonpreconFormRadiRadicado
 * @property string medioRespuestaFonpreconFormRadiRadicado
 * @property string $calidadCausanteFonpreconFormRadiRadicado
 * @property string $empleadorFonpreconFormRadiRadicado
 * @property string $categoriaPrestacionFonpreconFormRadiRadicado
 * @property string $categoriaBeneficiarioFonpreconFormRadiRadicado
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
 * @property int|null $idRadiRadicadoPadre Radicado padre en la combinación de correspondencia
 */
class RadiRadicados extends \yii\db\ActiveRecord
{
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
            [['fechaVencimientoRadiRadicados', 'creacionRadiRadicado', 'fechaDocumentoRadiRadicado'], 'safe'],
            [['estadoRadiRadicado', 'idTrdTipoDocumental', 'user_idCreador', 'idTrdDepeUserTramitador', 'user_idTramitador', 'user_idTramitadorOld', 'PrioridadRadiRadicados', 'idCgTipoRadicado', 'idCgMedioRecepcion', 'idTrdDepeUserCreador', 'cantidadCorreosRadicado', 'idGdTrdSerie', 'idGdTrdSubserie', 'autorizacionRadiRadicados', 'idRadiRadicadoPadre'], 'integer'],
            [['isRadicado', 'firmaDigital'], 'integer'],
            [['numeroRadiRadicado', 'descripcionAnexoRadiRadicado', 'radicadoOrigen' ], 'string', 'max' => 80],
            [['asuntoRadiRadicado'], 'string', 'max' => 4000],
            [['foliosRadiRadicado'], 'string', 'max' => 20],
            [['observacionRadiRadicado', 'cargoFonpreconFormRadiRadicado', 'calidadCausanteFonpreconFormRadiRadicado', 'empleadorFonpreconFormRadiRadicado', 'categoriaPrestacionFonpreconFormRadiRadicado', 'categoriaBeneficiarioFonpreconFormRadiRadicado', 'medioRespuestaFonpreconFormRadiRadicado'], 'string'],
            [['numeroRadiRadicado'], 'unique'],
            [['idGdTrdSerie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSeries::className(), 'targetAttribute' => ['idGdTrdSerie' => 'idGdTrdSerie']],
            [['idGdTrdSubserie'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdSubseries::className(), 'targetAttribute' => ['idGdTrdSubserie' => 'idGdTrdSubserie']],
            [['idCgMedioRecepcion'], 'exist', 'skipOnError' => true, 'targetClass' => CgMediosRecepcion::className(), 'targetAttribute' => ['idCgMedioRecepcion' => 'idCgMedioRecepcion']],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
            [['idTrdDepeUserCreador'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idTrdDepeUserCreador' => 'idGdTrdDependencia']], 
            [['idTrdDepeUserTramitador'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdDependencias::className(), 'targetAttribute' => ['idTrdDepeUserTramitador' => 'idGdTrdDependencia']],
            [['idTrdTipoDocumental'], 'exist', 'skipOnError' => true, 'targetClass' => GdTrdTiposDocumentales::className(), 'targetAttribute' => ['idTrdTipoDocumental' => 'idGdTrdTipoDocumental']],
            [['user_idCreador'], 'exist', 'skipOnError' => true, 'targetClass' => User::className(), 'targetAttribute' => ['user_idCreador' => 'id']],
            [['numeroFactura', 'numeroContrato'], 'string', 'max' => 100],
            [['valorFactura','numeroCuenta'], 'number'],
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
            'idGdTrdSerie' => 'Serie',
            'idGdTrdSubserie' => 'Subserie',
            'idTrdTipoDocumental' => 'Tipo documental',
            'user_idCreador' => 'Usuario creador',
            'idTrdDepeUserTramitador' => 'Dependencia del usuario tramitador',
            'user_idTramitador' => 'Usuario tramitador',
            'user_idTramitadorOld' => 'Usuario anteriormente encargado de tramitar el radicado',
            'PrioridadRadiRadicados' => 'Prioridad',
            'isRadicado' => '¿Está radicado?',
            'firmaDigital' => '¿Está firmado digitalmente?',
            'idCgTipoRadicado' => 'Tipo radicado',
            'idCgMedioRecepcion' => 'Medio de recepción',
            'idTrdDepeUserCreador' => 'Dependencia del usuario creador del radicado',
            'cantidadCorreosRadicado' => 'Conteo de correos del radicado',
            'radicadoOrigen' => 'Radicado origen',
            'fechaDocumentoRadiRadicado' => 'Fecha del documento',
            'observacionRadiRadicado' => 'Observación',
            'autorizacionRadiRadicados' => 'Medio por el cual desea ser notificado',
            'idRadiRadicadoPadre' => 'Radicado Padre en combinación de correspondencia',
            'numeroFactura' => 'Número de factura',
            'numeroContrato' => 'Número de contrato',
            'valorFactura' => 'Valor factura',
            'numeroCuenta' => 'Numero Cuenta',
            'cargoFonpreconFormRadiRadicado' => 'Cargo',
            'medioRespuestaFonpreconFormRadiRadicado' => 'Medio Respuesta',
            'calidadCausanteFonpreconFormRadiRadicado' => 'Calidad Causante',
            'empleadorFonpreconFormRadiRadicado' => 'Empleador',
            'categoriaPrestacionFonpreconFormRadiRadicado' => 'Categoria Prestacion',
            'categoriaBeneficiarioFonpreconFormRadiRadicado' => 'Categoria Beneficiario',
        ];
    }

    /** Función que ejecuta Yii antes de realizar el insert o update del modelo
     * @param $insert [boolean] true: indica que es un nuevo registro, false: indica que es una actualización
     */
    public function beforeSave($insert)
    {
        if ($insert) {
            # Se valida si el parametro "activateRadiTmp" está inactivo o el tipo de radicado es de entrada o pqrs para guardar el registro como un radicado y no un temporal
            if (Yii::$app->params['activateRadiTmp'] == false || $this->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiEntrada'] || $this->idCgTipoRadicado == Yii::$app->params['idCgTipoRadicado']['radiPqrs']) {
                $this->isRadicado = 1; // 0 (false): temporal, 1 (true): radicado
            }
        }
        return true;
    }

    public function attributeLabelsReport()
    {
        $alias = 'RAD';
        $imputsPermitidos = [
            'numeroRadiRadicado' => 'LIKE', 'asuntoRadiRadicado' => 'LIKE', 'descripcionAnexoRadiRadicado' => 'LIKE', 'foliosRadiRadicado' => 'LIKE', 'fechaVencimientoRadiRadicados' => 'DATE',
            'estadoRadiRadicado' => 'IN', 'creacionRadiRadicado' => 'DATE', 'idTrdTipoDocumental' => 'IN', 'user_idCreador' => 'IN', 'idTrdDepeUserTramitador' => 'IN', 'user_idTramitador' => 'IN',
            'user_idTramitadorOld' => 'IN', 'PrioridadRadiRadicados' => 'IN', 'idCgTipoRadicado' => 'IN', 'idCgMedioRecepcion' => 'IN', 'idTrdDepeUserCreador' => 'LIKE', 'cantidadCorreosRadicado' => 'IN',
            'radicadoOrigen' => 'LIKE', 'fechaDocumentoRadiRadicado' => 'DATE', 'observacionRadiRadicado' => 'LIKE', 'autorizacionRadiRadicados' => 'IN', 'isRadicado' => 'IN',
            'idRadiRadicadoPadre' => 'LIKE',
            // 'idTipoPersona' => 'LIKE', 
            /** Espacio para personalizacion del cliente */
            'numeroFactura' => 'LIKE', 'numeroContrato' => 'LIKE', 'valorFactura' => 'LIKE', 'numeroCuenta' => 'LIKE',
            /** Fin Espacio para personalizacion del cliente */
        ];

        $inputsRelation = [
            'estadoRadiRadicado' => [
                'type' => 'params',
                'foreingModel' => 'statusTodoNumber',
                'alias' => $alias,
                'key' => 'estadoRadiRadicado',
            ],
            'idTrdTipoDocumental' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdTiposDocumentales',
                'foreingAlias' => 'TDOC',
                'foreignKey' => 'idGdTrdTipoDocumental',
                'foreingColumn' => 'nombreTipoDocumental',
                'alias' => $alias,
                'key' => 'idTrdTipoDocumental',
            ],
            'user_idCreador' => [
                'type' => 'model',
                'foreingModel' => 'UserDetalles',
                'foreingAlias' => 'US1',
                'foreignKey' => 'idUser',
                'foreingColumn' => ['nombreUserDetalles', 'apellidoUserDetalles'],
                'alias' => $alias,
                'key' => 'user_idCreador',
            ],
            'idTrdDepeUserTramitador' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdDependencias',
                'foreingAlias' => 'DEPE1',
                'foreignKey' => 'idGdTrdDependencia',
                'foreingColumn' => 'nombreGdTrdDependencia',
                'alias' => $alias,
                'key' => 'idTrdDepeUserTramitador',
            ],
            'user_idTramitador' => [
                'type' => 'model',
                'foreingModel' => 'UserDetalles',
                'foreingAlias' => 'US2',
                'foreignKey' => 'idUser',
                'foreingColumn' => ['nombreUserDetalles', 'apellidoUserDetalles'],
                'alias' => $alias,
                'key' => 'user_idTramitador',
            ],
            'user_idTramitadorOld' => [
                'type' => 'model',
                'foreingModel' => 'UserDetalles',
                'foreingAlias' => 'US3',
                'foreignKey' => 'idUser',
                'foreingColumn' => ['nombreUserDetalles', 'apellidoUserDetalles'],
                'alias' => $alias,
                'key' => 'user_idTramitadorOld',
            ],
            'idCgTipoRadicado' => [
                'type' => 'model',
                'foreingModel' => 'CgTiposRadicados',
                'foreingAlias' => 'TPRAD',
                'foreignKey' => 'idCgTipoRadicado',
                'foreingColumn' => 'nombreCgTipoRadicado',
                'alias' => $alias,
                'key' => 'idCgTipoRadicado',
            ],
            'PrioridadRadiRadicados' => [
                'type' => 'params',
                'foreingModel' => 'statusPrioridadText',
                'alias' => $alias,
                'key' => 'PrioridadRadiRadicados',
            ],
            'idCgMedioRecepcion' => [
                'type' => 'model',
                'foreingModel' => 'CgMediosRecepcion',
                'foreingAlias' => 'MEDIO',
                'foreignKey' => 'idCgMedioRecepcion',
                'foreingColumn' => 'nombreCgMedioRecepcion',
                'alias' => $alias,
                'key' => 'idCgMedioRecepcion',
            ],
            'idTrdDepeUserCreador' => [
                'type' => 'model',
                'foreingModel' => 'GdTrdDependencias',
                'foreingAlias' => 'DEPE2',
                'foreignKey' => 'idGdTrdDependencia',
                'foreingColumn' => 'nombreGdTrdDependencia',
                'alias' => $alias,
                'key' => 'idTrdDepeUserCreador',
            ],
            'autorizacionRadiRadicados' => [
                'type' => 'params',
                'foreingModel' => 'autorizacionText',
                'alias' => $alias,
                'key' => 'autorizacionRadiRadicados',
            ],
            'isRadicado' => [
                'type' => 'params',
                'foreingModel' => 'SiNoBooleanNumber',
                'alias' => $alias,
                'key' => 'isRadicado',
            ],
            // 'idTipoPersona' => [
            //     'type' => 'modelRemitente',
            //     // Remitentes
            //     'foreingModel1' => 'Remitentes',
            //     'foreingAlias1' => 'REMIT',
            //     'foreignKey1' => 'idRadiRadicado',
            //     'alias1' => $alias,
            //     'key1' => 'idRadiRadicado',
            //     // Clientes
            //     'foreingModel2' => 'Clientes',
            //     'foreingAlias2' => 'CLIREMI',
            //     'foreignKey2' => 'idCliente',
            //     'key2' => 'idRadiPersona',
            //     // Usuarios
            //     'foreingModel3' => 'UserDetalles',
            //     'foreingAlias3' => 'USRREMI',
            //     'foreignKey3' => 'idUser',
            //     'key3' => 'idRadiPersona',
            // ],
            'idRadiRadicadoPadre' => [
                'type' => 'model',
                'foreingModel' => 'RadiRadicadosPadre',
                'foreingAlias' => 'RPADRE',
                'foreignKey' => 'idRadiRadicado',
                'foreingColumn' => 'numeroRadiRadicado',
                'alias' => $alias,
                'key' => 'idRadiRadicadoPadre',
            ],
            /** Espacio para personalizacion del cliente */
            /** Fin Espacio para personalizacion del cliente */
        ];

        $arrayAttributeLabelsReport = [];

        $attributeLabels = self::attributeLabels();
        foreach ($attributeLabels as $key => $value) {
            if (array_key_exists($key, $imputsPermitidos)) {
                $input = ['column' => $alias.'_'.$key, 'label' => $value, 'value' => false, 'typeFilter' => $imputsPermitidos[$key], 'infoRelation' => []];
                if (array_key_exists($key, $inputsRelation)) {
                    $input['infoRelation'] = $inputsRelation[$key];
                }
                $arrayAttributeLabelsReport[] = $input;
            }
        }

        // Cliente
        // $arrayAttributeLabelsReport[] = [
        //     'column'        => $alias.'_'. 'idTipoPersona',
        //     'label'         => 'Remitente/Destinatario',
        //     'value'         => false,
        //     'typeFilter'    => $imputsPermitidos['idTipoPersona'],
        //     'infoRelation'  => $inputsRelation['idTipoPersona'],
        // ];

        return $arrayAttributeLabelsReport;
    }

    /**
     * Gets query for [[RadiAgendaRadicados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiAgendaRadicados()
    {
        return $this->hasMany(RadiAgendaRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[RadiDocumentos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiDocumentos()
    {
        return $this->hasMany(RadiDocumentos::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

     /**
     * Gets query for [[RadiEnvios]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiEnvios()
    {
        return $this->hasMany(RadiEnvios::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[RadiInformados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiInformados()
    {
        return $this->hasMany(RadiInformados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[RadiLogRadicados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiLogRadicados()
    {
        return $this->hasMany(RadiLogRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[IdCgMedioRecepcion0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgMedioRecepcion()
    {
        return $this->hasOne(CgMediosRecepcion::className(), ['idCgMedioRecepcion' => 'idCgMedioRecepcion']);
    }

    /**
     * Gets query for [[IdCgTipoRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgTipoRadicado()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }

    /**
     * Gets query for [[IdTrdTipoDocumental0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getTrdTipoDocumental()
    {
        return $this->hasOne(GdTrdTiposDocumentales::className(), ['idGdTrdTipoDocumental' => 'idTrdTipoDocumental']);
    }

    /**
     * Gets query for [[UserIdCreador]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserIdCreador()
    {
        return $this->hasOne(User::className(), ['id' => 'user_idCreador']);
    }

        /**
     * Gets query for [[UserIdTramitador]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUserIdTramitador()
    {
        return $this->hasOne(User::className(), ['id' => 'user_idTramitador']);
    }

    /** 
    * Gets query for [[UserIdTramitadorOld]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getUserIdTramitadorOld() 
    { 
       return $this->hasOne(User::className(), ['id' => 'user_idTramitadorOld']); 
    }

    /**
     * Gets query for [[IdGdTrdSerie0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdSerie0()
    {
        return $this->hasOne(GdTrdSeries::className(), ['idGdTrdSerie' => 'idGdTrdSerie']);
    }

    /**
     * Gets query for [[IdGdTrdSubserie0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdGdTrdSubserie0()
    {
        return $this->hasOne(GdTrdSubseries::className(), ['idGdTrdSubserie' => 'idGdTrdSubserie']);
    }


    /**
     * Gets query for [[RadiRemitentes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRemitentes()
    {
        return $this->hasOne(RadiRemitentes::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }


    /** 
     * Gets query for [[RadiRadicadoAnulados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicadoAnulados()
    {
        return $this->hasMany(RadiRadicadoAnulado::className(), ['idRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[IdTrdDepeUserCreador0]]. 
     * 
     * @return \yii\db\ActiveQuery 
     */ 
    public function getIdTrdDepeUserCreador0() 
    { 
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idTrdDepeUserCreador']); 
    } 

    /** 
    * Gets query for [[IdTrdDepeUserTramitador0]]. 
    * 
    * @return \yii\db\ActiveQuery 
    */ 
    public function getIdTrdDepeUserTramitador0() 
    { 
        return $this->hasOne(GdTrdDependencias::className(), ['idGdTrdDependencia' => 'idTrdDepeUserTramitador']); 
    } 

    /**
     * Gets query for [[RadiRadicadosAsociados]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicadosAsociados()
    {
        return $this->hasMany(RadiRadicadosAsociados::className(), ['idRadiAsociado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[RadiDocumentosPrincipales]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiDocumentosPrincipales()
    {
        return $this->hasMany(RadiDocumentosPrincipales::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    
    }

    /**
    * Gets query for [[RadiDocumentosPrincipales0]].
    *
    * @return \yii\db\ActiveQuery
    */
    public function getRadiDocumentosPrincipales0()
    {
        return $this->hasMany(RadiDocumentosPrincipales::className(), ['idRadiRespuesta' => 'idRadiRadicado']);
    }
    
    /** Gets query for [[GdExpedienteInclusion]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdExpedienteInclusion()
    {
        return $this->hasOne(GdExpedientesInclusion::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    public function getRadiRadicadosResoluciones()
    {
        return $this->hasOne(RadiRadicadosResoluciones::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }
}
