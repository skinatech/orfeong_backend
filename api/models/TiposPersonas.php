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
 * This is the model class for table "tiposPersonas".
 *
 * @property int $idTipoPersona ID primario de la tabla tiposPersonas
 * @property string $tipoPersona Tipo de persona
 * @property int $estadoPersona 0 Inactivo 10 Activo
 * @property string $creacionPersona Creación del registro
 *
 * @property Clientes[] $clientes
 */
class TiposPersonas extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tiposPersonas';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tipoPersona'], 'required'],
            [['estadoPersona'], 'integer'],
            [['creacionPersona'], 'safe'],
            [['tipoPersona'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idTipoPersona' => 'Id Tipo Persona',
            'tipoPersona' => 'Tipo Persona',
            'estadoPersona' => 'Estado Persona',
            'creacionPersona' => 'Creacion Persona',
        ];
    }

     /**
     * Gets query for [[Clientes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getClientes()
    {
        return $this->hasMany(Clientes::className(), ['idTipoPersona' => 'idTipoPersona']);
    }

    /**
     * Consulta de listados de tipos de personas
     */
    public function getList($id) {
        $return = [];
        $model = TiposPersonas::find()
            ->select(['idTipoPersona', 'tipoPersona'])
            ->where(['estadoPersona' => yii::$app->params['statusTodoText']['Activo']]);

        if ($id != null) {
            $model->orWhere(['idTipoPersona' => $id]);
        }
        
        $model->orderBy(['tipoPersona'=>SORT_ASC]);

        foreach ($model->all() as $row) {
            $return[] = [
                'id' => $row['idTipoPersona'],
                'val' => $row['tipoPersona']
            ];
        }
        return $return;

    }
}
