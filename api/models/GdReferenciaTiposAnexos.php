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
 * This is the model class for table "gdReferenciaTiposAnexos".
 *
 * @property int $idGdReferenciaTipoAnexo Campo primario de la tabla
 * @property int $idGdReferenciaCruzada Identificador de la referencia cruzada
 * @property int $idGdTipoAnexoFisico Identificador del tipo de anexo
 * @property int $estadoGdReferenciaTipoAnexo estado del registro 0: Inactivo, 10: Activo
 * @property string $creacionGdReferenciaTipoAnexo Fecha de creación del registro
 *
 * @property GdReferenciasCruzadas $idGdReferenciaCruzada0
 * @property GdTiposAnexosFisicos $idGdTipoAnexoFisico0
 */
class GdReferenciaTiposAnexos extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'gdReferenciaTiposAnexos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idGdReferenciaCruzada', 'idGdTipoAnexoFisico'], 'required'],
            [['idGdReferenciaCruzada', 'idGdTipoAnexoFisico', 'estadoGdReferenciaTipoAnexo'], 'integer'],
            [['creacionGdReferenciaTipoAnexo'], 'safe'],
            [['idGdReferenciaCruzada'], 'exist', 'skipOnError' => true, 'targetClass' => GdReferenciasCruzadas::className(), 'targetAttribute' => ['idGdReferenciaCruzada' => 'idGdReferenciaCruzada']],
            [['idGdTipoAnexoFisico'], 'exist', 'skipOnError' => true, 'targetClass' => GdTiposAnexosFisicos::className(), 'targetAttribute' => ['idGdTipoAnexoFisico' => 'idGdTipoAnexoFisico']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idGdReferenciaTipoAnexo' => 'Id Gd Referencia Tipo Anexo',
            'idGdReferenciaCruzada' => 'Id Gd Referencia Cruzada',
            'idGdTipoAnexoFisico' => 'Id Gd Tipo Anexo Fisico',
            'estadoGdReferenciaTipoAnexo' => 'Estado Gd Referencia Tipo Anexo',
            'creacionGdReferenciaTipoAnexo' => 'Creacion Gd Referencia Tipo Anexo',
        ];
    }

    /**
     * Gets query for [[IdGdReferenciaCruzada0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdReferenciaCruzada()
    {
        return $this->hasOne(GdReferenciasCruzadas::className(), ['idGdReferenciaCruzada' => 'idGdReferenciaCruzada']);
    }

    /**
     * Gets query for [[IdGdTipoAnexoFisico0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getGdTipoAnexoFisico()
    {
        return $this->hasOne(GdTiposAnexosFisicos::className(), ['idGdTipoAnexoFisico' => 'idGdTipoAnexoFisico']);
    }
}

