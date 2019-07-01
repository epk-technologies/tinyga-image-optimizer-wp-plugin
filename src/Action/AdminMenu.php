<?php

namespace Tinyga\Action;

use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\Settings;
use Tinyga\Tinyga;
use Tinyga\Utils;

class AdminMenu extends Settings
{
    const MENU_SLUG = 'wp-tinyga';

    /**
     * Register action to event.
     */
    public function __construct()
    {
        parent::__construct();
        add_action('admin_menu', [&$this, 'addSettingsMenuAction']);
        add_filter('plugin_action_links', [&$this, 'addPluginActionLinks'], 10, 2);
    }

    /**
     * Add menu item (link) to tinyga settings page in settings menu
     */
    public function addSettingsMenuAction()
    {
        add_options_page(
            translate('Tinyga Image Optimizer Settings', Tinyga::SLUG),
            'Tinyga',
            'manage_options',
            self::MENU_SLUG,
            [&$this, 'settingsPageAction']
        );
    }

    /**
     * Add settings link to plugin page
     *
     * @param $links
     * @param $file
     *
     * @return array
     */
    public function addPluginActionLinks($links, $file)
    {
        if (plugin_basename(TINYGA_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $menu_slug = self::MENU_SLUG;
        $settings_url = admin_url("options-general.php?page={$menu_slug}");
        $settings_url_name = translate('Settings', Tinyga::SLUG);

        return array_merge($links, [
            'settings' => "<a href='{$settings_url}'>{$settings_url_name}</a>",
        ]);
    }

    /**
     * Tinyga settings page
     */
    public function settingsPageAction()
    {
        $result = [];
        if (!empty($_POST)) {
            $settings = $_POST[Settings::OPTION_TINYGA_OPTIONS];
            $result = $this->validateSettings($settings);
            $this->updateSettings($result['valid']);
        }

        $api_key = isset($this->settings[self::TINYGA_OPTIONS_API_KEY])
            ? $this->settings[self::TINYGA_OPTIONS_API_KEY]
            : '';
        $auto_optimize = isset($this->settings[self::TINYGA_OPTIONS_AUTO_OPTIMIZE])
            ? $this->settings[self::TINYGA_OPTIONS_AUTO_OPTIMIZE]
            : 1;
        $optimize_main_image = isset($this->settings[self::TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE])
            ? $this->settings[self::TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE]
            : 1;
        $quality = isset($this->settings[self::TINYGA_OPTIONS_QUALITY])
            ? $this->settings[self::TINYGA_OPTIONS_QUALITY]
            : 1;
        $quality_range = range(OptimizationRequest::MAX_QUALITY, OptimizationRequest::MIN_QUALITY);

        $sizes = Utils::getImageSizes(true);
        $valid_sizes = [];
        foreach ($sizes as $size) {
            $include_size = self::TINYGA_OPTIONS_SIZES_PREFIX . $size;
            $valid_sizes[$include_size] = isset($this->settings[$include_size])
                ? $this->settings[$include_size]
                : 1;
        }

        Utils::view('settings', [
            'result' => $result,
            'api_key' => $api_key,
            'auto_optimize' => $auto_optimize,
            'optimize_main_image' => $optimize_main_image,
            'quality' => $quality,
            'quality_range' => $quality_range,
            'sizes' => $sizes,
            'valid_sizes' => $valid_sizes,
        ]);
    }

    /**
     * Validate settings from settings page
     *
     * @param $input
     *
     * @return array
     */
    private function validateSettings($input)
    {
        $valid = [];
        $error = [];

        if (empty($input['api_key'])) {
            $error[] = 'API Credentials must not be left blank.';
        }

        $valid['api_key'] = $input['api_key'];
        $valid['auto_optimize'] = isset($input['auto_optimize']) ? 1 : 0;
        $valid['optimize_main_image'] = isset($input['optimize_main_image']) ? 1 : 0;
        $valid['quality'] = isset($input['quality']) ? $input['quality'] : OptimizationRequest::DEFAULT_LOSSY_QUALITY;
        $valid['sizes'] = isset($input['quality']) ? $input['quality'] : OptimizationRequest::DEFAULT_LOSSY_QUALITY;

        $sizes = Utils::getImageSizes(true);
        foreach ($sizes as $size) {
            $include_size = self::TINYGA_OPTIONS_SIZES_PREFIX . $size;
            $valid[$include_size] = isset($input[$include_size]) ? 1 : 0;
        }

        if (!empty($error)) {
            return ['success' => false, 'error' => $error, 'valid' => $valid];
        }

        return ['success' => true, 'valid' => $valid];
    }
}
