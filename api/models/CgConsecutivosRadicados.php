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
 * This is the model class for table "cgConsecutivosRadicados".
 *
 * @property int $idCgConsecutivoRadicado Clave primaria de la tabla cgConsecutivosRadicados
 * @property int|null $idCgTipoRadicado Identificador único para la tabla cgTiposRadicados
 * @property int|null $idCgRegional Identificador único para la tabla cgReionales
 * @property int $anioCgConsecutivoRadicado Año del consecutivo 
 * @property int $cgConsecutivoRadicado Consecutivo de la combinación del radicado 
 * @property string $creacionCgConsecutivoRadicado Fecha en que se creó el consecutivo
 * @property int $estadoCgConsecutivoRadicado 10 activo, 0 Inactivo
 *
 * @property CgTiposRadicados $idCgTipoRadicado0
 */
class CgConsecutivosRadicados extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'cgConsecutivosRadicados';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idCgConsecutivoRadicado', 'idCgTipoRadicado', 'idCgRegional', 'anioCgConsecutivoRadicado', 'cgConsecutivoRadicado', 'estadoCgConsecutivoRadicado'], 'integer'],
            [['anioCgConsecutivoRadicado', 'cgConsecutivoRadicado'], 'required'],
            [['creacionCgConsecutivoRadicado'], 'safe'],
            [['idCgConsecutivoRadicado'], 'unique'],
            [['idCgTipoRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => CgTiposRadicados::className(), 'targetAttribute' => ['idCgTipoRadicado' => 'idCgTipoRadicado']],
            [['idCgRegional'], 'exist', 'skipOnError' => true, 'targetClass' => CgRegionales::className(), 'targetAttribute' => ['idCgRegional' => 'idCgRegional']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idCgConsecutivoRadicado' => 'Id Cg Consecutivo Radicado',
            'idCgTipoRadicado' => 'Tipo Radicado',
            'idCgRegional' => 'Regional',
            'anioCgConsecutivoRadicado' => 'Año del consecutivo del radicado',
            'cgConsecutivoRadicado' => 'Consecutivo',
            'creacionCgConsecutivoRadicado' => 'Fecha Creación Consecutivo Radicado',
            'estadoCgConsecutivoRadicado' => 'Estado Consecutivo Radicado',
        ];
    }

    /**
     * Gets query for [[IdCgTipoRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getIdCgTipoRadicado0()
    {
        return $this->hasOne(CgTiposRadicados::className(), ['idCgTipoRadicado' => 'idCgTipoRadicado']);
    }
}

