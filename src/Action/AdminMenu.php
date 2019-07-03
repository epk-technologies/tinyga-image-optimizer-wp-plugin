<?php

namespace Tinyga\Action;

use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\Model\TinygaOptions;
use Tinyga\Utils;

class AdminMenu extends BaseAction
{
    const MENU_SLUG = 'wp-tinyga';

    /**
     * @inheritDoc
     */
    protected function registerActions()
    {
        $this->addAction('admin_menu', [&$this, 'addSettingsMenuAction']);
        $this->addFilter('plugin_action_links', [&$this, 'addPluginActionLinks'], 10, 2);
    }

    /**
     * Add menu item (link) to tinyga settings page in settings menu
     */
    public function addSettingsMenuAction()
    {
        $this->addOptionsPage(
            $this->trans('Tinyga Image Optimizer Settings'),
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
        if ($this->pluginBasename(TINYGA_PLUGIN_FILE) !== $file) {
            return $links;
        }

        $menu_slug = self::MENU_SLUG;
        $settings_url = $this->adminUrl("options-general.php?page={$menu_slug}");
        $settings_url_name = $this->trans('Settings');

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
            $settings = $_POST[TinygaOptions::OPTION_NAME];
            $result = $this->validateSettings($settings);
            $new_tinyga_options = new TinygaOptions($result['valid']);
            $this->updateTinygaOptions($new_tinyga_options);
        }

        $api_key = $this->tinyga_options->getApiKey();
        $auto_optimize = $this->tinyga_options->isAutoOptimize();
        $optimize_main_image = $this->tinyga_options->isOptimizeMainImage();
        $quality = $this->tinyga_options->getQuality();
        $max_width = $this->tinyga_options->getMaxWidth();
        $max_height = $this->tinyga_options->getMaxHeight();
        $show_reset = $this->tinyga_options->isShowReset();
        $bulk_async_limit = $this->tinyga_options->getBulkAsyncLimit();

        $sizes = $this->getImageSizes(true);
        $valid_sizes = [];
        foreach ($sizes as $size) {
            $valid_sizes[$size] = $this->tinyga_options->isValidSize($size);
        }

        Utils::view('settings', [
            'result' => $result,
            'api_key' => $api_key,
            'auto_optimize' => $auto_optimize,
            'optimize_main_image' => $optimize_main_image,
            'quality' => $quality,
            'max_width' => $max_width,
            'max_height' => $max_height,
            'show_reset' => $show_reset,
            'sizes' => $sizes,
            'valid_sizes' => $valid_sizes,
            'bulk_async_limit' => $bulk_async_limit,
        ]);
    }

    /**
     * Validate settings from settings page
     *
     * @param $input
     *
     * @return array
     */
    protected function validateSettings($input)
    {
        $valid = [];
        $error = [];

        if (empty($input['api_key'])) {
            $error[] = 'API Credentials must not be left blank.';
        }

        $valid['api_key'] = $this->sanitizeTextField($input['api_key']);
        $valid['auto_optimize'] = isset($input['auto_optimize']) ? 1 : 0;
        $valid['optimize_main_image'] = isset($input['optimize_main_image']) ? 1 : 0;
        $valid['quality'] = isset($input['quality']) ? (int) $input['quality'] : OptimizationRequest::DEFAULT_LOSSY_QUALITY;
        $valid['max_width'] = isset($input['max_width']) ? (int) $input['max_width'] : 0;
        $valid['max_height'] = isset($input['max_height']) ? (int) $input['max_height'] : 0;
        $valid['show_reset'] = isset($input['show_reset']) ? 1 : 0;
        $valid['bulk_async_limit'] = isset($input['bulk_async_limit']) ? (int) $input['bulk_async_limit'] : null;

        $sizes = $this->getImageSizes(true);
        foreach ($sizes as $size) {
            $include_size = 'tinyga_size_' . $size;
            $valid['sizes'][$size] = isset($input[$include_size]) ? 1 : 0;
        }

        if (!empty($error)) {
            return ['success' => false, 'error' => $error, 'valid' => $valid];
        }

        return ['success' => true, 'valid' => $valid];
    }
}
