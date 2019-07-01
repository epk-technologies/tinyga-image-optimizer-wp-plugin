<?php

namespace Tinyga\Action;

use Tinyga\Settings;
use Tinyga\Utils;

class EnqueueScripts extends Settings
{
    public function __construct()
    {
        parent::__construct();
        add_action('admin_enqueue_scripts', [&$this, 'enqueueScripts']);
    }

    /**
     * @param $hook
     */
    public function enqueueScripts($hook)
    {
        if (
            $hook === 'options-media.php'
            || $hook === 'upload.php'
            || $hook === 'settings_page_' . AdminMenu::MENU_SLUG
        ) {
            wp_enqueue_script('jquery');
            if (TINYGA_DEV_MODE === true) {
                wp_enqueue_script('tinyga-async-js', Utils::asset('js/async.js'));
                wp_enqueue_script('tinyga-tipsy-js', Utils::asset('js/jquery.tipsy.js'), ['jquery']);
                wp_enqueue_script('tinyga-modal-js', Utils::asset('js/jquery.modal.min.js'), ['jquery']);
                wp_enqueue_script('tinyga-ajax-js', Utils::asset('js/ajax.js'), ['jquery']);
                wp_localize_script('tinyga-ajax-js', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
                wp_localize_script('tinyga-ajax-js', 'tinyga_settings', $this->settings);
                wp_enqueue_style('tinyga-admin-css', Utils::asset('css/admin.css'));
                wp_enqueue_style('tinyga-tipsy-css', Utils::asset('css/tipsy.css'));
                wp_enqueue_style('tinyga-modal-css', Utils::asset('css/jquery.modal.css'));
            } else {
                wp_enqueue_script('tinyga-js', Utils::asset('js/dist/tinyga.min.js'), ['jquery']);
                wp_localize_script('tinyga-js', 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
                wp_localize_script('tinyga-js', 'tinyga_settings', $this->settings);
                wp_enqueue_style('tinyga-css', Utils::asset('css/dist/tinyga.min.css'));
            }
        }
    }
}
