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

class RegistroPqrs extends \yii\db\ActiveRecord
{
    public $tipoTramite; //Tipo de trámite
    public $tipoSolicitud; //Tipo de solicitud
    public $tipoClasificacion; //Tipo de Clasificación

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['tipoTramite', 'tipoSolicitud', 'tipoClasificacion'], 'integer'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'tipoTramite' => '',
            'tipoSolicitud' => '',
            'tipoClasificacion' => ''
        ];
    }

}
?>
