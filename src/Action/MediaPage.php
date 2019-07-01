<?php

namespace Tinyga\Action;

use Tinyga\Settings;
use Tinyga\Utils;

class MediaPage extends Settings
{
    use StatsSummaryTrait;

    /**
     * Register action to event.
     */
    public function __construct()
    {
        parent::__construct();
        add_action('manage_media_custom_column', [&$this, 'fillMediaColumns'], 10, 2);
        add_filter('manage_media_columns', [&$this, 'addMediaColumns']);
    }

    /**
     * @param $column_name
     * @param $id
     */
    public function fillMediaColumns($column_name, $id)
    {
        $file = Utils::getAttachedFile($id);
        $original_size = filesize($file);

        // handle the case where file does not exist
        if ($original_size === 0 || $original_size === false) {
            echo '0 bytes';
        } else if (strcmp($column_name, 'original_size') === 0) {
            $this->fillOriginalSizeColumn($id, $original_size);
        } else if (strcmp($column_name, 'optimized_size') === 0) {
            $this->fillOptimizedSizeColumn($id);
        }
    }

    /**
     * @param $columns
     *
     * @return mixed
     */
    public function addMediaColumns($columns)
    {
        $columns['original_size'] = 'Original Size';
        $columns['optimized_size'] = 'Tinyga Stats';
        return $columns;
    }

    protected function fillOriginalSizeColumn($id, $original_size)
    {
        $original_size = Utils::formatBytes($original_size);

        if (wp_attachment_is_image($id)) {
            $meta = $this->getImageMeta($id);
            if (isset($meta['original_size'])) {
                $original_size = Utils::formatBytes($meta['original_size']);
            }
        }

        echo $original_size;
    }

    protected function fillOptimizedSizeColumn($id)
    {
        $is_image = wp_attachment_is_image($id);
        $image_url = wp_get_attachment_url($id);
        $filename = basename($image_url);
        $is_optimize_main_image = false;
        $is_optimize_this_image = false;
        $stats_summary = null;
        $meta = null;
        $type = isset($this->settings['api_lossy']) ? $this->settings['api_lossy'] : 'lossy';

        if ($is_image) {
            $meta = $this->getImageMeta($id);
            $thumbs_meta = $this->getThumbsMeta($id);
            $optimize_main_image = !empty($this->settings[self::TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE]);

            // Is it optimized? Show some stats
            if (!empty($thumbs_meta) || (isset($meta['optimized_size']) && empty($meta['no_savings']))) {
                $is_optimize_main_image = $optimize_main_image && !isset($meta['optimized_size']);
                $stats_summary = $this->generateStatsSummary($id);
            } else {
                // Were there no savings, or was there an error?
                $is_optimize_this_image = true;
            }
        }

        Utils::view('media_optimized_column', [
            'is_image' => $is_image,
            'is_optimize_main_image' => $is_optimize_main_image,
            'is_optimize_this_image' => $is_optimize_this_image,
            'type' => $type,
            'id' => $id,
            'filename' => $filename,
            'image_url' => $image_url,
            'stats_summary' => $stats_summary,
            'meta' => $meta,
        ]);
    }
}
