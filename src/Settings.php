<?php

namespace Tinyga;

abstract class Settings
{
    /**
     * @var string
     */
    const OPTION_TINYGA_OPTIONS = '_tinyga_options';
    const POSTMETA_TINYGA_SIZE = '_tinyga_size';
    const POSTMETA_TINYGA_THUMBS = '_tinyga_thumbs';

    CONST TINYGA_OPTIONS_API_KEY = 'api_key';
    CONST TINYGA_OPTIONS_AUTO_OPTIMIZE = 'auto_optimize';
    CONST TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE = 'optimize_main_image';
    CONST TINYGA_OPTIONS_QUALITY = 'quality';
    CONST TINYGA_OPTIONS_SIZES_PREFIX = 'include_size_';

    const TINYGA_SIZE_TASK_ID = 'task_id';
    const TINYGA_SIZE_ORIGINAL_SIZE = 'original_size';
    const TINYGA_SIZE_OPTIMIZED_SIZE = 'optimized_size';
    const TINYGA_SIZE_SAVED_BYTES = 'saved_bytes';
    const TINYGA_SIZE_SAVING_PERCENT = 'savings_percent';
    const TINYGA_SIZE_TYPE = 'type';
    const TINYGA_SIZE_WIDTH = 'width';
    const TINYGA_SIZE_HEIGHT = 'height';
    const TINYGA_SIZE_META = 'meta';
    const TINYGA_SIZE_OPTIMIZED_BACKUP_FILE = 'optimized_backup_file';
    const TINYGA_SIZE_ERROR_CODE = 'error_code';
    const TINYGA_SIZE_CODE = 'code';
    const TINYGA_SIZE_MESSAGE = 'message';

    const TINYGA_THUMBS_THUMB = 'thumb';
    const TINYGA_THUMBS_FILE = 'file';
    const TINYGA_THUMBS_ORIGINAL_SIZE = 'original_size';
    const TINYGA_THUMBS_OPTIMIZED_SIZE = 'optimized_size';
    const TINYGA_THUMBS_TYPE = 'type';

    /**
     * @var array
     */
    protected $settings;

    public function __construct()
    {
        $this->settings = $this->getSettings();
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings = get_option(self::OPTION_TINYGA_OPTIONS);
    }

    /**
     * @param $options
     *
     * @return bool
     */
    public function updateSettings($options)
    {
        $result = update_option(self::OPTION_TINYGA_OPTIONS, $options);

        if ($result) {
            $this->getSettings();
        }

        return $result;
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getImageMeta($id)
    {
        return get_post_meta($id, self::POSTMETA_TINYGA_SIZE, true);
    }

    /**
     * @param int $id
     *
     * @param $data
     *
     * @return mixed
     */
    public function updateImageMeta($id, $data)
    {
        return update_post_meta($id, self::POSTMETA_TINYGA_SIZE, $data);
    }

    /**
     * @param int $id
     *
     * @return mixed
     */
    public function getThumbsMeta($id)
    {
        return get_post_meta($id, self::POSTMETA_TINYGA_THUMBS, true);
    }

    /**
     * @param int $id
     *
     * @param $data
     *
     * @return mixed
     */
    public function updateThumbsMeta($id, $data)
    {
        return update_post_meta($id, self::POSTMETA_TINYGA_THUMBS, $data);
    }
}
