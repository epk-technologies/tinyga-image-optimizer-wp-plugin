<?php

namespace Tinyga\Action;

use Tinyga\Model\TinygaOptions;
use Tinyga\Manager\WPManager;

abstract class BaseAction extends WPManager
{
    /**
     * @var TinygaOptions
     */
    protected $tinyga_options;

    /**
     * BaseAction constructor.
     */
    public function __construct()
    {
        $this->tinyga_options = $this->getTinygaOptions();
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
    public function updateTinygaOptions($options)
    {
        $result = parent::updateTinygaOptions($options);

        if ($result) {
            $this->tinyga_options = $this->getTinygaOptions();
        }

        return $result;
    }
}
