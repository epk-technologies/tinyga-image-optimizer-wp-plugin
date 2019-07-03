<?php

use Tinyga\ImageOptimizer\OptimizationRequest;

// variables
$id = $id ?: 'quality';
$name = $name ?: $id;
$selected_quality = $selected_quality ?: null;
$quality_lossless = OptimizationRequest::LOSSLESS_QUALITY;
$quality_default = OptimizationRequest::DEFAULT_LOSSY_QUALITY;
$quality_range = range(OptimizationRequest::MAX_QUALITY, OptimizationRequest::MIN_QUALITY);

?>

<select id="<?php echo $name ?>"
        name="<?php echo $name ?>">
    <?php foreach ($quality_range as $number) { ?>
        <option value="<?php echo $number ?>" <?php selected($selected_quality, $number, true); ?>>
            <?php if ($number === $quality_lossless) { ?>
                <?php echo $number ?> - Lossless
            <?php } elseif ($number === $quality_default) { ?>
                <?php echo $number ?> - Recommended
            <?php } else { ?>
                <?php echo $number ?>
            <?php } ?>
        </option>
    <?php } ?>
</select>
