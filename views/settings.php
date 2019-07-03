<?php

use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\Model\TinygaOptions;
use Tinyga\Utils;

// variables
$result = $result ?: null;
$api_key = $api_key ?: null;
$auto_optimize = $auto_optimize ?: null;
$optimize_main_image = $optimize_main_image ?: null;
$quality = $quality ?: null;
$quality_max = OptimizationRequest::MAX_QUALITY;
$quality_min = OptimizationRequest::MIN_QUALITY;
$quality_range = range(OptimizationRequest::MAX_QUALITY, OptimizationRequest::MIN_QUALITY);
$quality_lossless = OptimizationRequest::LOSSLESS_QUALITY;
$quality_default = OptimizationRequest::DEFAULT_LOSSY_QUALITY;
$max_width = $max_width ?: 0;
$max_height = $max_height ?: 0;
$show_reset = $show_reset ?: false;
$sizes = $sizes ?: null;
$valid_sizes = $valid_sizes ?: null;
$bulk_async_limit = $bulk_async_limit ?: TinygaOptions::BULK_ASYNC_LIMIT_DEFAULT;
$bulk_async_limit_min = TinygaOptions::BULK_ASYNC_LIMIT_MIN;
$bulk_async_limit_max = TinygaOptions::BULK_ASYNC_LIMIT_MAX;


$option_name = static function($value) {
    return TinygaOptions::OPTION_NAME . '[' . $value . ']';
}

?>

<h1 class="tinyga-admin-section-title">Tinyga Settings</h1>
<?php if (isset($result['error'])) { ?>
    <div class="tinyga error settings-error">
        <?php foreach ($result['error'] as $error) { ?>
            <p><?php echo $error; ?></p>
        <?php } ?>
    </div>
<?php } elseif (isset($result['success'])) { ?>
    <div class="tinyga updated settings-error">
        <p>Settings saved.</p>
    </div>
<?php } ?>

<?php if (!function_exists('curl_init')) { ?>
    <p class="curl-warning"><strong>Warning: </strong>CURL is not available. Please install CURL before using this plugin</p>
<?php } ?>

<form id="tinygaSettings" method="post">
    <a href="https://tinyga.cz/" target="_blank" title="Log in to your Tinyga account">Tinyga</a> API settings
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row">
                    <label for="tinyga_api_key">API Key:</label>
                </th>
                <td>
                    <input id="tinyga_api_key"
                           name="<?php echo $option_name('api_key') ?>"
                           type="text"
                           value="<?php echo esc_attr($api_key); ?>"
                           size="50">
                </td>
            </tr>
            <tr class="with-tip">
                <th scope="row">
                    <label for="quality">Quality setting:</label>
                </th>
                <td>
                    <?php Utils::view('parts/select_quality', [
                        'name' => $option_name('quality'),
                        'selected_quality' => $quality,
                    ]); ?>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        Advanced users can force the quality to a value between <?php echo $quality_min ?> and <?php echo $quality_max ?> with <?php echo $quality_lossless ?> being lossless <br />
                        For example, forcing the quality to 60 or 70 might yield greater savings, but the resulting quality might be affected, depending on the image. <br />
                        We therefore recommend keeping the default <strong><?php echo $quality_default ?></strong> setting, which will not allow a resulting image of unacceptable quality.<br />
                    </div>
                </td>
            </tr>
            <tr class="with-tip">
                <th scope="row">
                    <label for="auto_optimize">Automatically optimize uploads:</label>
                </th>
                <td>
                    <input type="checkbox"
                           id="auto_optimize"
                           name="<?php echo $option_name('auto_optimize') ?>"
                           value="1"
                           <?php checked(1, $auto_optimize, true); ?>/>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        Enabled by default. This setting causes images uploaded through the Media Uploader to be optimized on-the-fly.<br />
                        If you do not wish to do this, or wish to optimize images later, disable this setting by unchecking the box.
                    </div>
                </td>
            </tr>
            <tr class="with-tip">
                <th scope="row">
                    <label for="optimize_main_image">Optimize main image:</label>
                </th>
                <td>
                    <input type="checkbox"
                           id="optimize_main_image"
                           name="<?php echo $option_name('optimize_main_image') ?>"
                           value="1"
                           <?php checked(1, $optimize_main_image, true); ?>/>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        Enabled by default. This option causes the image uploaded by the user to get optimized, as well as all sizes generated by WordPress.<br />
                        Disabling this option results in faster uploading, since the main image is not sent to our system for optimization.<br />
                        Disable this option if you never use the "main" image upload in your posts, or speed of image uploading is an issue.
                    </div>
                </td>
            </tr>
            <tr class="with-tip">
                <th scope="row">Resize main image:</th>
                <td>
                    <label for="tinyga_max_width">Max Width (px):</label>&nbsp;&nbsp;
                    <input type="text"
                           id="tinyga_max_width"
                           name="<?php echo $option_name('max_width') ?>"
                           value="<?php echo esc_attr($max_width); ?>"
                           style="width:50px;" />&nbsp;&nbsp;&nbsp;
                    <label for="tinyga_max_height">Max Height (px):</label>&nbsp;&nbsp;
                    <input type="text"
                           id="tinyga_max_height"
                           name="<?php echo $option_name('max_height') ?>"
                           value="<?php echo esc_attr($max_height); ?>"
                           style="width:50px;" />
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        You can restrict the maximum dimensions of image uploads by width and/or height.<br />
                        It is especially useful if you wish to prevent unnecessarily large photos with extremely high resolutions from being uploaded, for example, <br />
                        photos shot with a recent-model iPhone. Note: you can restrict the dimensions by width, height, or both. A value of zero disables.
                    </div>
                </td>
            </tr>
            <tr class="no-border">
                <td class="tinygaAdvancedSettings">
                    <h3><span class="tinyga-advanced-settings-label">Advanced Settings</span></h3>
                </td>
            </tr>
            <tr class="tinyga-advanced-settings">
                <td colspan="2" class="tinygaAdvancedSettingsDescription">
                    <small>We recommend that you leave these settings at their default values</small>
                </td>
            </tr>
            <tr class="tinyga-advanced-settings">
                <th scope="row">Image Sizes to optimize:</th>
                <td>
                    <?php $i = 0; ?>
                    <?php foreach($sizes as $size) { ?>
                        <?php $size_checked = isset($valid_sizes[$size]) ? $valid_sizes[$size] : 1; ?>
                        <?php $name = "tinyga_size_$size" ?>
                        <label for="<?php echo $name ?>">
                            <input type="checkbox"
                                   id="<?php echo $name ?>"
                                   name="<?php echo $option_name($name) ?>"
                                   value="1"
                                   <?php checked(1, $size_checked, true); ?>/>
                            &nbsp;<?php echo $size ?>
                        </label>&nbsp;&nbsp;&nbsp;&nbsp;
                        <?php $i++ ?>
                        <?php if ($i % 3 === 0) { ?>
                            <br />
                        <?php } ?>
                    <?php } ?>
                </td>
            </tr>
            <tr class="tinyga-advanced-settings with-tip">
                <th scope="row">
                    <label for="tinyga_show_reset">Show metadata reset per image:</label>
                </th>
                <td>
                    <input type="checkbox"
                           id="tinyga_show_reset"
                           name="<?php echo $option_name('show_reset') ?>"
                           value="1"
                           <?php checked(1, $show_reset, true); ?>/>
                    &nbsp;&nbsp;&nbsp;&nbsp;<span class="tinyga-reset-all enabled">Reset All Images</span>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        Checking this option will add a Reset button in the "Show Details" popup in the Tinyga Stats column for each optimized image.<br />
                        Resetting an image will remove the Tinyga metadata associated with it, effectively making your blog forget that it had been optimized in the first place, allowing further optimization in some cases.<br />
                        If an image has been optimized using the lossless setting, lossless optimization will not yield any greater savings. If in doubt, please contact support@tinyga.cz
                    </div>
                </td>
            </tr>
            <tr class="tinyga-advanced-settings with-tip">
                <th scope="row">
                    <label for="tinyga_bulk_async_limit">Bulk Concurrency:</label>
                </th>
                <td>
                    <select id="tinyga_bulk_async_limit"
                            name="<?php echo $option_name('bulk_async_limit') ?>">
                        <?php foreach (range($bulk_async_limit_min, $bulk_async_limit_max) as $number) { ?>
                            <option value="<?php echo $number ?>" <?php selected($bulk_async_limit, $number, true); ?>>
                                <?php echo $number ?>
                            </option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        This settings defines how many images can be processed at the same time using the bulk optimizer. The recommended (and default) value is 4. <br />
                        For blogs on very small hosting plans, or with reduced connectivity, a lower number might be necessary to avoid hitting request limits.
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <input type="submit" name="tinyga_save" id="tinyga_save" class="button button-primary" value="Save All"/>
</form>
