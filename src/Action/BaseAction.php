<?php

namespace Tinyga\Action;

use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaOptions;
use Tinyga\Model\TinygaThumbMeta;
use Tinyga\Manager\SettingsManager;

abstract class BaseAction
{
    /**
     * @var SettingsManager
     */
    protected $settings_manager;

    /**
     * @var SettingsManager
     */
    protected $tinyga_options;

    /**
     * BaseAction constructor.
     */
    public function __construct()
    {
        $this->settings_manager = new SettingsManager();
        $this->tinyga_options = $this->settings_manager->getOptions();
        $this->registerActions();
    }

    /**
     * Register actions to events
     */
    abstract protected function registerActions();

    /**
     * @param array|TinygaOptions $options
     *
     * @return bool
     */
    protected function updateOptions($options)
    {
        $result = $this->settings_manager->updateOptions($options);

        if ($result) {
            $this->tinyga_options = $this->settings_manager->getOptions();
        }

        return $result;
    }

    /**
     * @param int $id
     *
     * @return TinygaImageMeta|null
     */
    protected function getImageMeta($id)
    {
        return $this->settings_manager->getImageMeta($id);
    }

    /**
     * @param int $id
     * @param array|TinygaImageMeta $data
     *
     * @return mixed
     */
    protected function updateImageMeta($id, $data)
    {
        return $this->settings_manager->updateImageMeta($id, $data);
    }

    /**
     * @param int $id
     * @param bool $array
     *
     * @return TinygaThumbMeta[]|array
     */
    protected function getThumbsMeta($id, $array = false)
    {
        return $this->settings_manager->getThumbsMeta($id, $array);
    }

    /**
     * @param int $id
     * @param array|TinygaThumbMeta $data
     *
     * @return mixed
     */
    protected function updateThumbsMeta($id, $data)
    {
        return $this->settings_manager->updateThumbsMeta($id, $data);
    }
}
