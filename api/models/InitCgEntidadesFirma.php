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
 * This is the model class for table "initCgEntidadesFirma".
 *
 * @property int $idInitCgEntidadFirma Identificador unico de la tabla
 * @property string $nombreInitCgEntidadFirma Nombre de la entidad que ofrece el servicio de firmas cetificadas digitalmente
 * @property int $estadoInitCgEntidadFirma Estado de la entidad
 * @property string $creacionInitCgEntidadFirma Fecha de creación de la entidad que ofrece el servicio
 */
class InitCgEntidadesFirma extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'initCgEntidadesFirma';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['nombreInitCgEntidadFirma'], 'required'],
            [['creacionInitCgEntidadFirma'], 'safe'],
            [['estadoInitCgEntidadFirma'], 'integer'],
            [['nombreInitCgEntidadFirma'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idInitCgEntidadFirma' => 'Id Entidad firma',
            'nombreInitCgEntidadFirma' => 'Nombre entidad',
            'creacionInitCgEntidadFirma' => 'Fecha Creación',
            'estadoInitCgEntidadFirma' => 'Estado'
        ];
    }
}
