<?php

// variables
$is_optimize_main_image = $is_optimize_main_image ?: false;
$is_optimize_this_image = $is_optimize_this_image ?: false;
$optimization_quality = $optimization_quality ?: null;
$id = $id ?: null;
$filename = $filename ?: null;
$image_url = $image_url ?: null;

?>

<div class="buttonWrap">
    <button data-setting="<?php echo $optimization_quality ?>"
            type="button"
            class="tinyga_req"
            data-id="<?php echo $id ?>"
            id="tinygaid-<?php echo $id ?>"
            data-filename="<?php echo $filename ?>"
            data-url="<?php echo $image_url ?>">
        <?php if ($is_optimize_main_image) { ?>
            Optimize Main Image
        <?php } elseif ($is_optimize_this_image)  { ?>
            Optimize This Image
        <?php } else { ?>
            Optimize
        <?php } ?>
    </button>
    <span class="tinygaSpinner"></span>
</div>
