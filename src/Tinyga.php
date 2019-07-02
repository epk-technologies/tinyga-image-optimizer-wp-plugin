<?php

namespace Tinyga;

use Tinyga\Action\AdminMenu;
use Tinyga\Action\EnqueueScripts;
use Tinyga\Action\MediaPage;
use Tinyga\Action\MediaPageUploader;

class Tinyga
{
    const SLUG = 'tinyga';

    public function __construct()
    {
        $this->registerActions();
    }

    private function registerActions()
    {
        $actions = [
            AdminMenu::class,
            MediaPage::class,
            MediaPageUploader::class,
            EnqueueScripts::class,
        ];

        foreach ($actions as $action) {
            if (class_exists($action)) {
                new $action;
            }
        }
    }
}
