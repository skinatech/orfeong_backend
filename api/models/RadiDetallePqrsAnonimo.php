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
 * This is the model class for table "radiDetallePqrsAnonimo".
 *
 * @property int $idRadiDetallePqrsAnonimo
 * @property int $idRadiRadicado relación tabla RadiRadicados
 * @property int $idNivelGeografico1 relación tabla nivelGeografico1
 * @property int $idNivelGeografico2 relación tabla nivelGeografico2
 * @property int $idNivelGeografico3 relación tabla nivelGeografico3
 * @property string $direccionRadiDetallePqrsAnonimo dirección especificada en el formulario de pqrs 
 * @property int $estadoRadiDetallePqrsAnonimo
 * @property string $creacionRadiDetallePqrsAnonimo
 * @property string $emailRadiDetallePqrsAnonimo
 *
 * @property RadiRadicados $idRadiRadicado0
 * @property NivelGeografico1 $idNivelGeografico10
 * @property NivelGeografico2 $idNivelGeografico20
 * @property NivelGeografico3 $idNivelGeografico30
 */
class RadiDetallePqrsAnonimo extends \yii\db\ActiveRecord
{   

    # Datos de corespondencia
    public  $dirCam1, $dirCam2, $dirCam3, $dirCam4, $dirCam5, $dirCam6;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'radiDetallePqrsAnonimo';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idRadiRadicado', 'idNivelGeografico1'], 'required', 'message' => 'Este campo no puede estar vacío.'],
            [['idRadiRadicado', 'idNivelGeografico1', 'idNivelGeografico2', 'idNivelGeografico3', 'estadoRadiDetallePqrsAnonimo'], 'integer' , 'message' => 'Este campo no puede estar vacío.'],
            [['creacionRadiDetallePqrsAnonimo'], 'safe'],
            [['direccionRadiDetallePqrsAnonimo','emailRadiDetallePqrsAnonimo'], 'string', 'max' => 80],

            [['dirCam1', 'dirCam2', 'dirCam4', 'dirCam5', 'dirCam6'], 'string'],
            [['dirCam2','dirCam4', 'dirCam5'], 'string', 'max' => 4,'message' => ''],

            [['idRadiRadicado'], 'exist', 'skipOnError' => true, 'targetClass' => RadiRadicados::className(), 'targetAttribute' => ['idRadiRadicado' => 'idRadiRadicado']],
            [['idNivelGeografico1'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico1::className(), 'targetAttribute' => ['idNivelGeografico1' => 'nivelGeografico1']],
            [['idNivelGeografico2'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico2::className(), 'targetAttribute' => ['idNivelGeografico2' => 'nivelGeografico2']],
            [['idNivelGeografico3'], 'exist', 'skipOnError' => true, 'targetClass' => NivelGeografico3::className(), 'targetAttribute' => ['idNivelGeografico3' => 'nivelGeografico3']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idRadiDetallePqrsAnonimo' => '',
            'idRadiRadicado' => '',
            'idNivelGeografico1' => '',
            'idNivelGeografico2' => '',
            'idNivelGeografico3' => '',
            'dirCam1' => "",
            'dirCam2' => "",
            'dirCam3' => "",
            'dirCam4' => "",
            'dirCam5' => "",
            'dirCam6' => "",
            'direccionRadiDetallePqrsAnonimo' => '',
            'estadoRadiDetallePqrsAnonimo' => '',
            'creacionRadiDetallePqrsAnonimo' => '',
        ];
    }

    /**
     * Gets query for [[IdRadiRadicado0]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getRadiRadicado()
    {
        return $this->hasOne(RadiRadicados::className(), ['idRadiRadicado' => 'idRadiRadicado']);
    }

    /**
     * Gets query for [[IdNivelGeografico10]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico1()
    {
        return $this->hasOne(NivelGeografico1::className(), ['nivelGeografico1' => 'idNivelGeografico1']);
    }

    /**
     * Gets query for [[IdNivelGeografico20]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico2()
    {
        return $this->hasOne(NivelGeografico2::className(), ['nivelGeografico2' => 'idNivelGeografico2']);
    }

    /**
     * Gets query for [[IdNivelGeografico30]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getNivelGeografico3()
    {
        return $this->hasOne(NivelGeografico3::className(), ['nivelGeografico3' => 'idNivelGeografico3']);
    }
}
