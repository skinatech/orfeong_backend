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
 * This is the model class for table "initCgParamsFirma".
 *
 * @property int $idInitCgParamFirma Identificador unico de la tabla
 * @property string $variableInitCgParamFirma Nombre de la variable a ultilizar para la configuración
 * @property string $descripcionInitCgParamsFirma Descripción de para que se usa este campo	
 * @property int $estadoInitCgParamFirma Estado de la variable
 * @property string $creacionInitCgParamFirma Fecha de creación de la variable
 */
class InitCgParamsFirma extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'initCgParamsFirma';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['variableInitCgParamFirma'], 'required'],
            [['creacionInitCgParamFirma'], 'safe'],
            [['estadoInitCgParamFirma'], 'integer'],
            [['variableInitCgParamFirma' ], 'string', 'max' => 80],
            [['descripcionInitCgParamsFirma' ], 'string', 'max' => 250],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idInitCgParamFirma' => 'Id radicado',
            'variableInitCgParamFirma' => 'Número radicado',
            'estadoRadiRadicado' => 'Estado',
            'creacionRadiRadicado' => 'Creación radicado',
            'descripcionInitCgParamsFirma' => 'Descripción de la variable'
        ];
    }
}
