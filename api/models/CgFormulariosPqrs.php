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
 * This is the model class for table "cgFormulariosPqrs".
 *
 * @property int $idCgFormulariosPqrs
 * @property string $nombreCgFormulariosPqrs
 * @property int $estadoCgFormulariosPqrs
 * @property string $creacionCgFormulariosPqrs
 *
 * @property CgFormulariosPqrsDetalle[] $cgFormulariosPqrsDetalles
 */
class CgFormulariosPqrs extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgFormulariosPqrs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreCgFormulariosPqrs'], 'required'],
            [['estadoCgFormulariosPqrs'], 'integer'],
            [['creacionCgFormulariosPqrs'], 'safe'],
            [['nombreCgFormulariosPqrs'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgFormulariosPqrs' => 'Id Cg Formularios Pqrs',
            'nombreCgFormulariosPqrs' => 'Nombre Cg Formularios Pqrs',
            'estadoCgFormulariosPqrs' => 'Estado Cg Formularios Pqrs',
            'creacionCgFormulariosPqrs' => 'Creacion Cg Formularios Pqrs',
        ];
    }

    /**
     * Gets query for [[CgFormulariosPqrsDetalles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCgFormulariosPqrsDetalles()
    {
        return $this->hasMany(CgFormulariosPqrsDetalle::className(), ['idCgFormulariosPqrs' => 'idCgFormulariosPqrs']);
    }
}
