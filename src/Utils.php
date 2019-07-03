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
}
