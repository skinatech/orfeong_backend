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

namespace api\models\views;

use Yii;

/**
 * This is the model class for VIEW "viewRadiCountByUser".
 * Vista que consulta la cantidad de tipos de radicados por usuario 
 *
 * @property int $idUser ID del usuario
 * @property int $idGdTrdDependencia ID de la dependencia actual
 * @property string $nombreGdTrdDependencia Nombre de la dependencia actual
 * @property int|null $idUserDetalles ID de la tabla userDetalles
 * @property string|null $nombreUserDetalles Nombre del usuario
 * @property string|null $apellidoUserDetalles Apellido del usuario
 * @property float|null $countRadicados Cantidad general de radicados
 * @property float|null $countSalida Cantidad de radicados de salida
 * @property float|null $countEntrada Cantidad de radicados de entrada
 * @property float|null $countPqr Cantidad de radicados PQRs
 * @property float|null $countComunicacionInterna Cantidad de radicados de comunicación interna
 * @property float|null $countVencidos Cantidad de radicados vencidos con estado diferente a: Finalizado => 11, Archivado => 12
 */
class ViewRadiCountByUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'viewRadiCountByUser';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['idUser', 'idGdTrdDependencia', 'nombreGdTrdDependencia'], 'required'],
            [['idUser', 'idGdTrdDependencia', 'idUserDetalles'], 'integer'],
            [['countRadicados', 'countSalida', 'countEntrada', 'countPqr', 'countComunicacionInterna', 'countVencidos'], 'number'],
            [['nombreGdTrdDependencia'], 'string', 'max' => 255],
            [['nombreUserDetalles', 'apellidoUserDetalles'], 'string', 'max' => 80],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'idUser' => 'Id User',
            'idGdTrdDependencia' => 'Id Gd Trd Dependencia',
            'nombreGdTrdDependencia' => 'Nombre Gd Trd Dependencia',
            'idUserDetalles' => 'Id User Detalles',
            'nombreUserDetalles' => 'Nombre User Detalles',
            'apellidoUserDetalles' => 'Apellido User Detalles',
            'countRadicados' => 'Count Radicados',
            'countSalida' => 'Count Salida',
            'countEntrada' => 'Count Entrada',
            'countPqr' => 'Count Pqr',
            'countComunicacionInterna' => 'Count Comunicacion Interna',
            'countVencidos' => 'Count Vencidos',
        ];
    }
}
