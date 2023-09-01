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
 * This is the model class for table "cgTrd".
 *
 * @property int $idCgTrd Numero único de la cg TRD
 * @property int $idMascaraCgTrd Número del id de la máscara
 * @property string $cellDependenciaCgTrd Celda donde esta ubicada el código de la dependencia
 * @property string $cellTituloDependCgTrd Celda del nombre del titulo de la dependencia TRD
 * @property string|null $cellRegionalCgTrd Celda donde esta ubicado el nombre de la regional
 * @property string $cellDatosCgTrd Celda del inicio de los datos de la TRD
 * @property string $columnCodigoCgTrd Columna de ubicación del código de la trd, este código es (dependencia-serie-subserie) puede estar solo en 1 columna o puede estar en varios
 * @property string|null $column2CodigoCgTrd Columna de ubicación del código de la trd, ubicación de la serie
 * @property string|null $column3CodigoCgTrd Columna de ubicación del código de la trd, ubicación de la subserie
 * @property string $columnNombreCgTrd Columna de ubicación de los nombres de la serie, subserie y tipos documentales
 * @property string $columnAgCgTrd Columna de ubicación del ítem A.G
 * @property string $columnAcCgTrd Columna de ubicación del ítem A.C
 * @property string $columnCtCgTrd Columna de ubicación del ítem C.T
 * @property string $columnECgTrd Columna de ubicación del ítem E
 * @property string $columnSCgTrd Columna de ubicación del ítem S
 * @property string $columnMCgTrd Columna de ubicación del ítem M
 * @property string $columnProcessCgTrd Columna de ubicación del procedimiento
 * @property string|null $columnTipoDocCgTrd Días del tipo documental
 * @property int $estadoCgTrd Estado 0 Inactivo 10 Activo
 * @property string $creacionCgTrd Creación de la cgTrd
 * @property string|null $columnPSoporteCgTrd Columna de ubicación del soporte de la subserie (papel)
 * @property string|null $columnESoporteCgTrd Columna de ubicación del soporte de la subserie (electrónico)
 * @property string|null $columnOsoporteCgTrd Columna de ubicación del soporte de la subserie (otros)
 * @property string|null $columnNormaCgTrd Columna de ubicación de la norma
 * @property string|null $cellDependenciaPadreCgTrd Celda donde esta ubicada la unidad administrativa
 *
 * @property CgTrdMascaras $idMascaraCgTrd0 
 */
class CgTrd extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgTrd';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idMascaraCgTrd', 'cellDependenciaCgTrd', 'cellTituloDependCgTrd', 'cellDatosCgTrd', 'columnCodigoCgTrd', 'columnNombreCgTrd', 'columnAgCgTrd', 'columnAcCgTrd', 'columnCtCgTrd', 'columnECgTrd', 'columnSCgTrd', 'columnMCgTrd', 'columnProcessCgTrd'], 'required'],
            [['idMascaraCgTrd', 'estadoCgTrd'], 'integer'],
            [['creacionCgTrd'], 'safe'],
            [['cellDependenciaCgTrd', 'cellTituloDependCgTrd', 'cellRegionalCgTrd', 'cellDatosCgTrd', 'cellDependenciaPadreCgTrd'], 'string', 'max' => 5],
            [['columnCodigoCgTrd', 'column2CodigoCgTrd', 'column3CodigoCgTrd', 'columnNombreCgTrd', 'columnAgCgTrd', 'columnAcCgTrd', 'columnCtCgTrd', 'columnECgTrd', 'columnSCgTrd', 'columnMCgTrd', 'columnProcessCgTrd', 'columnPSoporteCgTrd', 'columnESoporteCgTrd', 'columnOsoporteCgTrd', 'columnNormaCgTrd'], 'string', 'max' => 2],
            [['columnTipoDocCgTrd'], 'string', 'max' => 4],
            [['idMascaraCgTrd'], 'exist', 'skipOnError' => true, 'targetClass' => CgTrdMascaras::className(), 'targetAttribute' => ['idMascaraCgTrd' => 'idCgTrdMascara']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgTrd' => 'Id TRD',
            'idMascaraCgTrd' => 'Id máscara TRD',
            'cellDependenciaCgTrd' => 'Celda del código de dependencia',
            'cellTituloDependCgTrd' => 'Celda del título de dependencia',
            'cellRegionalCgTrd' => 'Celda de la regional',
            'cellDatosCgTrd' => 'Celda de inicio de datos',
            'columnCodigoCgTrd' => 'Columna código TRD',
            'column2CodigoCgTrd' => 'Columna del código serie',
            'column3CodigoCgTrd' => 'Columna del código subserie',
            'columnNombreCgTrd' => 'Columna de nombres TRD',
            'columnAgCgTrd' => 'Columna AG',
            'columnAcCgTrd' => 'Columna AC',
            'columnCtCgTrd' => 'Columna CT',
            'columnECgTrd' => 'Columna E',
            'columnSCgTrd' => 'Columna S',
            'columnMCgTrd' => 'Columna M',
            'columnProcessCgTrd' => 'Columna procedimiento',
            'columnTipoDocCgTrd' => 'Días tipo documental',
            'estadoCgTrd' => 'Estado',
            'creacionCgTrd' => 'Fecha creación',
            'columnPSoporteCgTrd' => 'Columna P soporte',
            'columnESoporteCgTrd' => 'Columna E soporte',
            'columnOsoporteCgTrd' => 'Columna O soporte',
            'columnNormaCgTrd' => 'Columna Norma',
            'cellDependenciaPadreCgTrd' => 'Celda de la unidad administrativa',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getIdMascaraCgTrd0()
    {
        return $this->hasOne(CgTrdMascaras::className(), ['idCgTrdMascara' => 'idMascaraCgTrd']);
    }
}
