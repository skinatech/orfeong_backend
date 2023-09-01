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
namespace frontend\models;
use Yii;

class FormsFonprecon extends \yii\db\ActiveRecord
{
    public $cargo;
    public $medioRespuesta;
    public $calidadCausante;
    public $tipoEmpleador;
    public $categoriaPrestacion;
    public $calidadBeneficiario;
    public $reCaptcha;

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['cargo', 'medioRespuesta', 'calidadCausante', 'tipoEmpleador', 'categoriaPrestacion', 'calidadBeneficiario'], 'string'],
            // [['reCaptcha'], \kekaadrenalin\recaptcha3\ReCaptchaValidator::className(), 'acceptance_score' => 0],
            [['cargo', 'medioRespuesta', 'calidadCausante', 'tipoEmpleador'], 'required'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'cargo' => '',
            'medioRespuesta' => '',
            'calidadCausante' => '',
            'tipoEmpleador' => '',
            'categoriaPrestacion' => '',
            'calidadBeneficiario' => ''
        ];
    }

}
?>
