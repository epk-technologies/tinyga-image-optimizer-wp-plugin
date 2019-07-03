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
        $this->addAction('admin_enqueue_scripts', [&$this, 'enqueueScripts']);
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
            $this->enqueueScript('jquery');
            if (TINYGA_DEV_MODE === true) {
                $this->enqueueScript('tinyga-async-js', 'js/async.js');
                $this->enqueueScript('tinyga-tipsy-js', 'js/jquery.tipsy.js', ['jquery']);
                $this->enqueueScript('tinyga-modal-js', 'js/jquery.modal.min.js', ['jquery']);
                $this->enqueueScript('tinyga-ajax-js', 'js/ajax.js', ['jquery']);
                $this->enqueueStyle('tinyga-admin-css', 'css/admin.css');
                $this->enqueueStyle('tinyga-tipsy-css', 'css/tipsy.css');
                $this->enqueueStyle('tinyga-modal-css', 'css/jquery.modal.css');
                $localize_handle = 'tinyga-ajax-js';
            } else {
                $this->enqueueScript('tinyga-js', 'js/dist/tinyga.min.js', ['jquery']);
                $this->enqueueStyle('tinyga-css', 'css/dist/tinyga.min.css');
                $localize_handle = 'tinyga-js';
            }

            $this->localizeScript($localize_handle, 'ajax_object', ['ajax_url' => $this->adminUrl('admin-ajax.php')]);
            $this->localizeScript($localize_handle, 'tinyga_settings', $this->tinyga_options->toArray());
            $this->localizeScript($localize_handle, 'tinyga_bulk_modal', [
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
