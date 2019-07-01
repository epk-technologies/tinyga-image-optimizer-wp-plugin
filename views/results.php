<?php
    // variables
    $main_image_tinyga_stats = $main_image_tinyga_stats ?: null;
    $thumbs_count = $thumbs_count ?: null;
    $thumbs_tinyga_stats = $thumbs_tinyga_stats ?: null;
    $type = $type ?: null;
    $show_reset = $show_reset ?: null;
?>
<div class="tinyga_detailed_results_wrap">
    <?php if ($main_image_tinyga_stats) { ?>
        <span class=""><strong>Main image savings:</strong></span>
        <br />
        <span style="display:inline-block;margin-bottom:5px">
            <?php echo $main_image_tinyga_stats['saved_bytes']; ?>
            (<?php echo $main_image_tinyga_stats['savings_percentage']; ?> saved)
        </span>
    <?php } ?>

    <?php if ($main_image_tinyga_stats && $thumbs_tinyga_stats) { ?>
        <br />
    <?php } ?>

    <?php if ($thumbs_tinyga_stats) { ?>
        <span><strong>Savings on <?php echo $thumbs_count; ?> thumbnails:</strong></span>
        <br />
        <span style="display:inline-block;margin-bottom:5px">
            <?php echo $thumbs_tinyga_stats['total_savings']; ?>
            (<?php echo $thumbs_tinyga_stats['savings_percentage']; ?> saved)
        </span>
    <?php } ?>

        <br />
        <span><strong>Optimization mode:</strong></span>
        <br />
        <span><?php echo ucfirst($type); ?></span>

    <?php if (!empty($show_reset)) { ?>
        <br />
        <small class="tinygaReset" data-id="<?php echo $id; ?>"
               title="Removes Tinyga metadata associated with this image">
            Reset
        </small>
        <span class="tinygaSpinner"></span>
    <?php } ?>
</div>
