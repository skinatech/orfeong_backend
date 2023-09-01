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
$fontSize10 = "14px";
$fontSize9 = "12px";
?>
<table class="table">
    <tbody>
        <tr>
            <td colspan="4" style="text-align: center; border: 2px solid #ddd;">
                <table class="table">
                    <tbody>
                        <tr>
                            <td colspan="3" class="table-bordered" rowspan="3" style="text-align: center;">
                                <img style="margin-top:1px;" height="50" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
                            </td>
                            <td colspan="12" class="table-bordered" rowspan="3" style="text-align: center; font-size: <?= $fontSize10; ?>;">
                                <b>FONDO DE PREVISIÓN SOCIAL DEL CONGRESO</b> <br>
                                <b>DE LA REPÚBLICA </b> <br><br>
                                <b>Establecimiento Público adscrito al Ministerio de Salud</b> <br>
                                <b>y Protección Social</b>
                            </td>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">CODIGO: F01-PRO-RPE-001</td>
                        </tr>
                        <tr>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">VERSIÓN: 4</td>
                        </tr>
                        <tr>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">FECHA DE APROBACION <br> 18/02/2019</td>
                        </tr>

                        <tr>
                            <td colspan="24" style="text-align: center; border-left: 1px solid #fff; border-right: 1px solid #fff; font-size: <?= $fontSize9; ?>;"><b>FORMULARIO ÚNICO PARA SOLICITUD DE PRESTACIONES ECONÓMICAS</b>
                        </tr>

                        <tr>
                            <td class="table-bordered" colspan="24" style="text-align: justify; font-size: <?= $fontSize9; ?>;">Los usuarios del Fondo de Previsión Social del Congreso de la República, podrán solicitar el reconocimiento de sus prestaciones, diligenciando este formulario acompañado de los documentos exigidos según el trámite e indicados en anexo, los cuales deben ser presentados personalmente o a través de un tercero debidamente autorizado.</td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>ESPACIO PARA USO DE FONPRECON</b></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><b>FECHA</b></td>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><b>RADICADO No.</b></td>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><b>EXPEDIENTE No.</b></td>
                        </tr>
                        <tr>
                            <td colspan="6" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['creacionRadiRadicado']; ?></td>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['numeroRadiRadicado']; ?></td>
                            <td colspan="9" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><br /></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>I. DATOS DEL CAUSANTE O SOLICITANTE</b></td>
                        </tr>
                        <tr>
                            <td colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>PRIMER APELLIDO</b></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>SEGUNDO APELLIDO</b></td>
                        </tr>
                        <tr>
                            <td class="table-bordered" colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['primerApellido']; ?></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td class="table-bordered" colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['segundoApellido']; ?></td>
                        </tr>
                        <tr>
                            <td colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>PRIMER NOMBRE</b></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>SEGUNDO NOMBRE</b></td>
                        </tr>
                        <tr>
                            <td class="table-bordered" colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['primerNombre']; ?></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td class="table-bordered" colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['segundoNombre']; ?></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>IDENTIFICACIÓN</b></td>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>CC</b></td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoIdentificación'] === 1) { echo 'X'; } ?></td>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>CEX</b></td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoIdentificación'] === 2) { echo 'X'; } ?></td>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>NÚMERO</b></td>
                            <td style="text-align: left; border-top: 1px solid #fff; font-size: <?= $fontSize9; ?>;"><br /></td>
                            <td style="text-align: left; border-top: 1px solid #fff; font-size: <?= $fontSize9; ?>;"><br /></td>
                            <?php if (count($pdf_data['numeroIdentificacion']) > 0) { ?>
                                <?php for ($i = 0; $i < 16; $i++) { ?>
                                    <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if (isset($pdf_data['numeroIdentificacion'][$i])) { echo $pdf_data['numeroIdentificacion'][$i]; } ?></td>
                                <?php } ?>
                            <?php } else { ?>
                                <?php for ($i = 0; $i < 16; $i++) { ?>
                                    <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><br /></td>
                                <?php } ?>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td colspan="13" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>DIRECCIÓN PARA CORRESPONDENCIA</b></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td colspan="10" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>TELÉFONO</b></td>
                        </tr>
                        <tr>
                            <td colspan="13" class="table-bordered" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['direccion']; ?></td>
                            <td style="border-top: 1px solid #fff; text-align: left; font-size: <?= $fontSize9; ?>;"><br /></td>
                            <?php if (count($pdf_data['telefono']) > 0) { ?>
                                <?php for ($i = 0; $i < 10; $i++) { ?>
                                    <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if (isset($pdf_data['telefono'][$i])) { echo $pdf_data['telefono'][$i]; } ?></td>
                                <?php } ?>
                            <?php } else { ?>
                                <?php for ($i = 0; $i < 10; $i++) { ?>
                                    <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><br /></td>
                                <?php } ?>
                            <?php } ?>
                        </tr>
                        <tr>
                            <td colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>;"><b>CIUDAD</b></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: none;"><b>DEPARTAMENTO</b></td>
                        </tr>
                        <tr>
                            <td class="table-bordered" colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['ciudad']; ?></td>
                            <td style="border-top: 1px solid #fff;"></td>
                            <td class="table-bordered" colspan="16" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['departamento']; ?></td>
                        </tr>
                            <tr>
                                <td></td>
                            </tr>
                        <tr>
                            <td colspan="3" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>CORREO ELECTRÓNICO</b></td>
                            <td class="table-bordered" colspan="21" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['email']; ?></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>EMPLEADOR:</b></td>
                        </tr>
                        <tr>
                            <td colspan="4" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>SENADO DE LA REPÚBLICA</b></td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoEmpleador'] === 'Senado de la república') { echo "X"; } ?></td>
                            <td colspan="10" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>CÁMARA DE REPRESENTANTES</b></td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoEmpleador'] === 'Cámara de representantes') { echo "X"; } ?></td>
                            <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>FONPRECON</b></td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoEmpleador'] === 'Fonprecon') { echo "X"; } ?></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>CARGO</b></td>
                        </tr>
                        <tr>
                            <td class="table-bordered" colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>;"><?= $pdf_data['cargo']; ?></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>; border-bottom: 1px solid #fff;"><b>II. PRESTACIÓN ECONÓMICA SOLICITADA (Marque con una X la opción correspondiente)</b></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="border-top: 1px solid #fff;">
                                <table class="table">
                                    <tr>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">PENSIONES</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"></td>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #ddd; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">CESANTÍAS</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 2) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Jubilación</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 4) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Reliquidación</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 14) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Definitivas - Retiro</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 14) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Traslado al FNA</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 2) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Vejez</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 9) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Acrecimiento</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 14) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Parciales</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Post-Mortem</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 1) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Invalidez</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Mesadas causadas</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">AUXILIOS FUNERARIOS</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Incapacidad</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Cuota Parte</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Afiliado</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Pensionado</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Indemnización</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 7) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Sustitución</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">BONOS PENSIONALES</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 3) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Pensión Familiar</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 6) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Sustitución Ley 44/80</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Bono A</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Bono B</td>
                                    </tr>
                                    <tr>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Pensión Post-Mortem</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 8) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Sobreviviente</td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd; border-right: 1px solid #ddd;">ACATAMIENTOS</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-left: 1px solid #ddd; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td style="text-align: center; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;"></td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 99) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Tutela</td>
                                        <td width="4%" class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['tipoSolicitud'] === 16) { echo 'X'; } ?></td>
                                        <td colspan="2" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd;">Sentencia</td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd; border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td>
                                        <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd; border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td>
                                        <td colspan="7" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-right: 1px solid #ddd; border-left: 1px solid #ddd; border-bottom: 1px solid #ddd;"></td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><b>III. PRESENTACIÓN DE LOS DOCUMENTOS</b></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: justify; font-size: <?= $fontSize9; ?>;">Manifiesto que los documentos presentados por mí, son auténticos y reunen las condiciones legales para adelantar el trámite solicitado.</td>
                        </tr>
                        <?php if (count($pdf_data['pdfFiles']) > 0) { ?>
                            <?php foreach ($pdf_data['pdfFiles'] as $key => $file) { ?>
                                <tr>
                                    <td colspan="24" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"><?= ($key + 1) .". ". $file; ?></td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        <tr>
                            <td colspan="2" style="text-align: right; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-bottom: 1px solid #fff;"><b>NOMBRES Y APELLIDOS:</b></td>
                            <td class="table-bordered" colspan="22" style="text-align: left; font-size: <?= $fontSize9; ?>;"><br /></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: right; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-bottom: 1px solid #fff;"><b>IDENTIFICACIÓN:</b></td>
                            <td class="table-bordered" colspan="22" style="text-align: left; font-size: <?= $fontSize9; ?>;"><br /></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: right; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-bottom: 1px solid #fff;"><b>FIRMA:</b></td>
                            <td colspan="22" style="text-align: left; font-size: <?= $fontSize9; ?>; border-bottom: 1px solid #ddd; border-top: 1px solid #fff;"><br /></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align: right; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff; border-bottom: 1px solid #fff;"></td>
                            <td colspan="9" style="text-align: left; font-size: <?= $fontSize9; ?>; border-bottom: 1px solid #fff; border-top: 1px solid #fff;">Autorizo el envío a mi correo electrónico para Citación a notificar</td>
                            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize9; ?>;"><?php if ($pdf_data['medioRespuesta'] === 'Correo electrónico') { echo "X"; } ?></td>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">Si</td>
                            <td class="table-bordered" style="text-align: left; font-size: <?= $fontSize9; ?>;"><br /></td>
                            <td style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;">No</td>
                            <td colspan="9" style="text-align: left; font-size: <?= $fontSize9; ?>; border-top: 1px solid #fff;"></td>
                        </tr>
                        <tr>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="24" style="text-align: center; font-size: <?= $fontSize9; ?>;">ORIGINAL PARA FONPRECON COPIA PARA EL SOLICITANTE F01-PRO-RPE-001</td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr>
            <td class="table-bordered" rowspan="3" style="text-align: center;">
                <img style="margin-top:1px;" height="50" src="<?=  Yii::getAlias('@api/web/img/logo.png') ?>">
            </td>
            <td colspan="2" class="table-bordered" rowspan="3" style="text-align: center; font-size: <?= $fontSize10; ?>;">
                <b>FONDO DE PREVISIÓN SOCIAL DEL CONGRESO</b> <br>
                <b>DE LA REPÚBLICA </b> <br><br>
                <b>Establecimiento Público adscrito al Ministerio de Salud</b> <br>
                <b>y Protección Social</b>
            </td>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">CODIGO: F01-PRO-RPE-001</td>
        </tr>
        <tr>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">VERSIÓN: 4</td>
        </tr>
        <tr>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">FECHA DE APROBACION <br> 18/02/2019</td>
        </tr>
        <tr>
            <td class="table-bordered" colspan="4" style="text-align: center; font-size: <?= $fontSize10; ?>;">RELACIÓN DE CERTIFICACIONES DE TIEMPO DE SERVICIO <br> (Solo para trámites de pensión)</td>
        </tr>
        <tr>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">NOMBRE ENTIDAD DONDE TRABAJÓ</td>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">NOMBRE ENTIDAD DE PREVISIÓN EN LA CUAL APORTÓ</td>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">FECHA DE INGRESO (Día/mes/año)</td>
            <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;">FECHA DE RETIRO (Día/mes/año)</td>
        </tr>
        <?php for ($i = 0; $i < 38; $i++) { ?>
            <tr>
                <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;"><br /></td>
                <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;"></td>
                <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;"></td>
                <td class="table-bordered" style="text-align: center; font-size: <?= $fontSize10; ?>;"></td>
            </tr>
        <?php } ?>
    </tbody>
</table>
