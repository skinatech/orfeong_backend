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
 * This is the model class for table "cgNumeroRadicado".
 *
 * @property int $idCgNumeroRadicado
 * @property string $estructuraCgNumeroRadicado
 * @property int $longitudDependenciaCgNumeroRadicado
 * @property int $estadoCgNumeroRadicado
 * @property string $creacionCgNumeroRadicado
 * @property int $longitudConsecutivoCgNumeroRadicado
 */
class CgNumeroRadicado extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgNumeroRadicado';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['estructuraCgNumeroRadicado', 'longitudDependenciaCgNumeroRadicado', 'longitudConsecutivoCgNumeroRadicado'], 'required'],
            [['longitudDependenciaCgNumeroRadicado', 'estadoCgNumeroRadicado', 'longitudConsecutivoCgNumeroRadicado'], 'integer'],
            [['creacionCgNumeroRadicado'], 'safe'],
            [['estructuraCgNumeroRadicado'], 'string', 'max' => 255],
            [['estructuraCgNumeroRadicado'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgNumeroRadicado' => 'Id número radicado',
            'estructuraCgNumeroRadicado' => 'Estructura del número radicado',
            'longitudDependenciaCgNumeroRadicado' => 'Longitud de la dependencia',
            'estadoCgNumeroRadicado' => 'Estado',
            'creacionCgNumeroRadicado' => 'Fecha creación',
            'longitudConsecutivoCgNumeroRadicado' => 'Longitud del consecutivo',
        ];
    }
}
