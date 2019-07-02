<?php

use Tinyga\Model\TinygaImageMeta;

// variables
$is_image = $is_image ?: false;
$is_optimize_main_image = $is_optimize_main_image ?: false;
$is_optimize_this_image = $is_optimize_this_image ?: false;
$optimization_quality = $optimization_quality ?: null;
$id = $id ?: null;
$filename = $filename ?: null;
$image_url = $image_url ?: null;
$stats_summary = $stats_summary ?: null;
$meta = $meta ?: null; /** @var TinygaImageMeta|null $meta */
?>

<div class="tinyga-wrap">
    <?php if($is_image) { ?>

        <?php if($is_optimize_main_image) { ?>
            <div class="buttonWrap">
                <button data-setting="<?php echo $optimization_quality ?>"
                        type="button"
                        class="tinyga_req"
                        data-id="<?php echo $id ?>"
                        id="tinygaid-<?php echo $id ?>"
                        data-filename="<?php echo $filename ?>"
                        data-url="<?php echo $image_url ?>">
                    Optimize Main Image
                </button>
                <span class="tinygaSpinner"></span>
            </div>
        <?php } ?>

        <?php echo $stats_summary ?>

        <?php if($is_optimize_this_image) { ?>
            <div class="buttonWrap">
                <button data-setting="<?php echo $optimization_quality ?>"
                        type="button"
                        class="tinyga_req"
                        data-id="<?php echo $id ?>"
                        id="tinygaid-<?php echo $id ?>"
                        data-filename="<?php echo $filename ?>"
                        data-url="<?php echo $image_url ?>">
                    Optimize This Image
                </button>
                <span class="tinygaSpinner"></span>
            </div>

            <?php if ($meta && !$meta->getSavedBytes()) { ?>
                <div class="noSavings">
                    <strong>No savings found</strong>
                    <br />
                    <small>Optimization quality:&nbsp;<?php echo $meta->getOptimizationQuality() ?></small>
                </div>
            <?php } elseif ($meta && $meta->getMessage()) { ?>
                <div class="tinygaErrorWrap">
                    <a class="tinygaError" title="<?php echo $meta->getMessage() ?>">Failed! Hover here</a>
                </div>
            <?php } ?>
        <?php } ?>

    <?php } else { ?>
        n/a
    <?php } ?>
</div>
