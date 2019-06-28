<?php use Tinyga\ImageOptimizer\OptimizationRequest; ?>

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
                           name="_tinyga_options[api_key]"
                           type="text"
                           value="<?php echo esc_attr($api_key); ?>"
                           size="50">
                </td>
            </tr>
            <tr class="with-tip">
                <th scope="row">
                    <label for="auto_optimize">Automatically optimize uploads:</label>
                </th>
                <td>
                    <input type="checkbox"
                           id="auto_optimize"
                           name="_tinyga_options[auto_optimize]"
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
                           name="_tinyga_options[optimize_main_image]"
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
                <th scope="row">
                    <label for="optimize_main_image">Quality setting:</label>
                </th>
                <td>
                    <select name="_tinyga_options[quality]">
                        <option value="0">Intelligent lossy (recommended)</option>
			            <?php foreach (range(OptimizationRequest::MAX_QUALITY, OptimizationRequest::MIN_QUALITY) as $number) { ?>
                            <option value="<?php echo $number ?>" <?php selected($quality, $number, true); ?>>
					            <?php echo $number ?>
                            </option>
			            <?php } ?>
                    </select>
                </td>
            </tr>
            <tr class="tip">
                <td colspan="2">
                    <div>
                        Advanced users can force the quality of JPEG images to a discrete "q" value between 25 and 100 using this setting <br />
                        For example, forcing the quality to 60 or 70 might yield greater savings, but the resulting quality might be affected, depending on the image. <br />
                        We therefore recommend keeping the <strong>Intelligent Lossy</strong> setting, which will not allow a resulting image of unacceptable quality.<br />
                        This setting will be ignored when using the <strong>lossless</strong> optimization mode.
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
    <input type="submit" name="tinyga_save" id="tinyga_save" class="button button-primary" value="Save All"/>
</form>
