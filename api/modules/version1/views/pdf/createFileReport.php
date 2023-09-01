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
?>
<table class="table table-bordered">
    <thead>
        <tr>
            <th>#</th>
            <?php foreach($pdf_data['titles'] as $key => $value) { ?>
                <th><?= $pdf_data['titles'][$key]['title']; ?></th>
            <?php } ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach($pdf_data['data'] as $key => $value) { ?>
            <?php $item = $key + 1; ?>
            <tr>
                <td><?= $item ?></td>
                <?php foreach($pdf_data['titles'] as $key2 => $value2) { ?>
                    <td><?= $pdf_data['data'][$key][$pdf_data['titles'][$key2]['data']] ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
    </tbody>
</table>
