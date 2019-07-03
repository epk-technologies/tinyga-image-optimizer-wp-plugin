<?php

use Tinyga\Manager\WPManager;
use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaOptions;
use Tinyga\Model\TinygaThumbMeta;

// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

$WPManager = new WPManager();
$WPManager->deletePostMeta($image_id, TinygaThumbMeta::OPTION_NAME);
$WPManager->deletePostMeta($image_id, TinygaImageMeta::OPTION_NAME);
$WPManager->deleteOption(TinygaOptions::OPTION_NAME);
