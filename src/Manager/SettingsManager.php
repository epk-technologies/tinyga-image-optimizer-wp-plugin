<?php

namespace Tinyga\Manager;

use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaOptions;
use Tinyga\Model\TinygaThumbMeta;

class SettingsManager
{
    /**
     * @return TinygaOptions
     */
    public function getOptions()
    {
        $tinyga_options = get_option(TinygaOptions::OPTION_NAME);

        return new TinygaOptions($tinyga_options ?: []);
    }

    /**
     * @param array|TinygaOptions $options
     *
     * @return bool
     */
    public function updateOptions($options)
    {
        if ($options instanceof TinygaOptions) {
            $options = $options->toArray();
        }

        return update_option(TinygaOptions::OPTION_NAME, $options);
    }

    /**
     * @param int $id
     *
     * @return TinygaImageMeta|null
     */
    public function getImageMeta($id)
    {
        $tinyga_image_meta = get_post_meta($id, TinygaImageMeta::OPTION_NAME, true);

        return $tinyga_image_meta ? new TinygaImageMeta($tinyga_image_meta) : null;
    }

    /**
     * @param int $id
     * @param array|TinygaImageMeta $data
     *
     * @return mixed
     */
    public function updateImageMeta($id, $data)
    {
        if ($data instanceof TinygaImageMeta) {
            $data = $data->toArray();
        }

        return update_post_meta($id, TinygaImageMeta::OPTION_NAME, $data);
    }

    /**
     * @param int $id
     * @param bool $array
     *
     * @return TinygaThumbMeta[]|array
     */
    public function getThumbsMeta($id, $array = false)
    {
        $tinyga_thumbs_meta = get_post_meta($id, TinygaThumbMeta::OPTION_NAME, true) ?: [];

        if ($array) {
            return $tinyga_thumbs_meta;
        }

        return array_map(static function($tinyga_thumb_meta){
            return new TinygaThumbMeta($tinyga_thumb_meta);
        }, $tinyga_thumbs_meta);
    }

    /**
     * @param int $id
     * @param array|TinygaThumbMeta $data
     *
     * @return mixed
     */
    public function updateThumbsMeta($id, $data)
    {
        if ($data instanceof TinygaThumbMeta) {
            $data = $data->toArray();
        }

        return update_post_meta($id, TinygaThumbMeta::OPTION_NAME, $data);
    }
}
