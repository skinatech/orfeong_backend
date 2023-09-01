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
 * This is the model class for table "cgMediosRecepcion".
 *
 * @property int $idCgMedioRecepcion identificación de la tabla este valor es unico
 * @property string $nombreCgMedioRecepcion nombre del medio de recepción para el radicado que se recibe
 * @property int $estadoCgMedioRecepcion esto del medio de recepción 10: activo o 0: inactivo
 * @property string $creacionCgMedioRecepcion fecha de creación del medio de recepción del radicado
 */
class CgMediosRecepcion extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgMediosRecepcion';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgMedioRecepcion'], 'required'],
            [['estadoCgMedioRecepcion'], 'integer'],
            [['creacionCgMedioRecepcion'], 'safe'],
            [['nombreCgMedioRecepcion'], 'string', 'max' => 45],
            [['nombreCgMedioRecepcion'], 'unique'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgMedioRecepcion' => 'Id Cg Medio Recepcion',
            'nombreCgMedioRecepcion' => 'Nombre Cg Medio Recepcion',
            'estadoCgMedioRecepcion' => 'Estado Cg Medio Recepcion',
            'creacionCgMedioRecepcion' => 'Creacion Cg Medio Recepcion',
        ];
    }
}
