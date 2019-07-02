<?php

namespace Tinyga;

class Utils
{
    /**
     * @param string $name
     * @param array $args
     * @param bool $return
     *
     * @return false|string|void
     */
    public static function view($name, array $args = [], $return = false)
    {
        $file = TINYGA_PLUGIN_DIR . "views/{$name}.php";

        $content = self::renderView($file, $args);

        if ($return) {
            return $content;
        }

        echo $content;
    }

    /**
     * @param $file
     * @param array $args
     *
     * @return false|string
     */
    public static function renderView($file, array $args = [])
    {
        foreach ($args AS $key => $val) {
            $$key = $val;
        }

        ob_start();

        /** @noinspection PhpIncludeInspection */
        include $file;

        return ob_get_clean();
    }

    /**
     * @param $asset
     *
     * @return string
     */
    public static function asset($asset)
    {
        return plugins_url('public/' . ltrim($asset, '/\\'), TINYGA_PLUGIN_FILE);
    }

    /**
     * @param $attachment_id
     * @param bool $unfiltered
     *
     * @return string
     */
    public static function getAttachedFile($attachment_id, $unfiltered = false)
    {
        return wp_normalize_path(get_attached_file($attachment_id, $unfiltered));
    }

    /**
     * @param null $time
     *
     * @return array
     */
    public static function getWpUploadDir($time = null)
    {
        $wp_upload_dir = wp_upload_dir($time);

        foreach (['basedir', 'path'] as $key) {
            if (isset($wp_upload_dir[$key])) {
                $wp_upload_dir[$key] = wp_normalize_path($wp_upload_dir[$key]);
            }
        }

        return $wp_upload_dir;
    }

    /**
     * @param $size
     * @param int $precision
     *
     * @return string
     */
    public static function formatBytes($size, $precision = 2)
    {
        $suffixes = [' bytes', 'KB', 'MB', 'GB', 'TB'];

        if ($size === 0) {
            return $size . $suffixes[$size];
        }

        $base = log($size, 1024);
        return round(1024 ** ($base - floor($base)), $precision) . $suffixes[(int)floor($base)];
    }

    /**
     * @param $pattern
     * @param $array
     *
     * @return int
     */
    public static function pregArrayKeyExists($pattern, $array)
    {
        $keys = array_keys($array);
        return (int) preg_grep($pattern, $keys);
    }

    public static function getImageSizes($keys_only = false)
    {
        global $_wp_additional_image_sizes;

        $sizes = [];

        foreach (get_intermediate_image_sizes() as $_size) {
            if (isset($_wp_additional_image_sizes[$_size])) {
                $width = $_wp_additional_image_sizes[$_size]['width'];
                $height = $_wp_additional_image_sizes[$_size]['height'];
                $crop = $_wp_additional_image_sizes[$_size]['crop'];
            } else {
                $width = get_option("{$_size}_size_w");
                $height = get_option("{$_size}_size_h");
                $crop = (bool) get_option("{$_size}_crop");
            }
            $sizes[$_size] = [
                'width' => $width,
                'height' => $height,
                'crop' => $crop,
            ];
        }

        return $keys_only ? array_keys($sizes) : $sizes;
    }
}
