<?php

namespace Tinyga\Action;

use Tinyga\ImageOptimizer\OptimizationRequest;
use Tinyga\Utils;

class EnqueueScripts extends BaseAction
{
    /**
     * @inheritDoc
     */
    protected function registerActions()
    {
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
                wp_enqueue_style('tinyga-admin-css', Utils::asset('css/admin.css'));
                wp_enqueue_style('tinyga-tipsy-css', Utils::asset('css/tipsy.css'));
                wp_enqueue_style('tinyga-modal-css', Utils::asset('css/jquery.modal.css'));
                $localize_script_handle = 'tinyga-ajax-js';
            } else {
                wp_enqueue_script('tinyga-js', Utils::asset('js/dist/tinyga.min.js'), ['jquery']);
                wp_enqueue_style('tinyga-css', Utils::asset('css/dist/tinyga.min.css'));
                $localize_script_handle = 'tinyga-js';
            }

            wp_localize_script($localize_script_handle, 'ajax_object', ['ajax_url' => admin_url('admin-ajax.php')]);
            wp_localize_script($localize_script_handle, 'tinyga_settings', $this->tinyga_options->toArray());
            wp_localize_script($localize_script_handle, 'tinyga_bulk_modal', [
                'modal' => $this->renderModalView(),
                'modal_row' => $this->renderModalRowView(),
            ]);
        }
    }

    /**
     * @return false|string|void
     */
    protected function renderModalView()
    {
        return Utils::view('modals/bulk_modal', [
            'quality' => $this->tinyga_options->getQuality(),
            'quality_range' => range(OptimizationRequest::MAX_QUALITY, OptimizationRequest::MIN_QUALITY),
        ], true);
    }

    /**
     * @return false|string|void
     */
    protected function renderModalRowView()
    {
        return Utils::view('modals/bulk_modal_row', [], true);
    }
}
