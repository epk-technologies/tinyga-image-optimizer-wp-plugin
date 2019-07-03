<?php

/**
 * Plugin Name:     Tinyga Image Optimizer
 * Plugin URI:      http://wordpress.org/plugins/tinyga-image-optimizer/
 * Description:     This plugin allows you to optimize your WordPress images through the Tinyga API.
 * Author:          EPK Technologies s.r.o.
 * Author URI:      https://tinyga.cz
 * License:         GPL2
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:     tinyga
 * Domain Path:     /languages
 * Version:         0.0.0
 *
 * @package         Tinyga
 */

use Tinyga\Tinyga;

//define('TINYGA_DEV_MODE', true);
//define('TINYGA_TEST_MODE', true);
//define('TINYGA_API_ENDPOINT', 'http://127.0.0.1:8000/api/v1/');

if (!defined('TINYGA_PLUGIN_FILE')) {
    define('TINYGA_PLUGIN_FILE', wp_normalize_path(__FILE__));
    define('TINYGA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once($autoload);
}

load_plugin_textdomain('tinyga', false, basename(__DIR__) . '/languages');

new Tinyga();
