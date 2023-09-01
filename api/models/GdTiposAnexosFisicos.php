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
 * This is the model class for table "gdTiposAnexosFisicos".
 *
 * @property int $idGdTipoAnexoFisico Campo primario de la tabla
 * @property string $nombreGdTipoAnexoFisico Nombre tipo de anexo 
 * @property int $estadoGdTipoAnexoFisico estado del registro 0: Inactivo, 10: Activo
 * @property string $creacionGdTipoAnexoFisico Fecha de creación del registro
 *
 * @property GdReferenciaTiposAnexos[] $gdReferenciaTiposAnexos
 */
class GdTiposAnexosFisicos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdTiposAnexosFisicos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreGdTipoAnexoFisico'], 'required'],
            [['estadoGdTipoAnexoFisico'], 'integer'],
            [['creacionGdTipoAnexoFisico'], 'safe'],
            [['nombreGdTipoAnexoFisico'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdTipoAnexoFisico' => 'Id Gd Tipo Anexo Fisico',
            'nombreGdTipoAnexoFisico' => 'Nombre Gd Tipo Anexo Fisico',
            'estadoGdTipoAnexoFisico' => 'Estado Gd Tipo Anexo Fisico',
            'creacionGdTipoAnexoFisico' => 'Creacion Gd Tipo Anexo Fisico',
        ];
    }

    /**
     * Gets query for [[GdReferenciaTiposAnexos]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdReferenciaTiposAnexos()
    {
        return $this->hasMany(GdReferenciaTiposAnexos::className(), ['idGdTipoAnexoFisico' => 'idGdTipoAnexoFisico']);
    }
}

