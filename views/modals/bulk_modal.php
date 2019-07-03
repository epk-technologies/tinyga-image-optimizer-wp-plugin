<?php

use Tinyga\Utils;

$quality = $quality ?: null;
$quality_range = $quality_range ?: null;

?>

<div id="tinyga-bulk-modal" class="tinyga-modal">

    <p class="tinygaBulkHeader">Tinyga Bulk Image Optimization <span class="close-tinyga-bulk">&times;</span></p>

    <div class="radiosWrap">
        <p>Choose optimization quality:</p>
        <label>
            <?php Utils::view('parts/select_quality', [
                'id' => 'tinyga-bulk-quality',
                'selected_quality' => $quality,
            ]); ?>
        </label>
    </div>

    <p class="the-following">
        The following <strong class="tinyga-modal-image-count">0</strong> images will be optimized by Tinyga:
    </p>

    <table id="tinyga-bulk">
        <tr class="tinyga-bulk-header">
            <td>File Name</td>
            <td style="width:120px">Original Size</td>
            <td style="width:120px">Tinyga Stats</td>
        </tr>
    </table>

    <button class="tinyga_req_bulk">Tinyga - optimize all</button>
</div>
