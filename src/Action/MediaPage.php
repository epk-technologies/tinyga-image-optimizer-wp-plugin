<?php

namespace Tinyga\Action;

use Tinyga\Utils;

class MediaPage extends StatsSummary
{
    /**
     * @inheritDoc
     */
    protected function registerActions()
    {
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
        } else if (strcmp($column_name, 'tinyga_original_size') === 0) {
            $this->fillOriginalSizeColumn($id, $original_size);
        } else if (strcmp($column_name, 'tinyga_optimized_size') === 0) {
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
        $columns['tinyga_original_size'] = 'Original Size';
        $columns['tinyga_optimized_size'] = 'Tinyga Stats';
        return $columns;
    }

    /**
     * @param $id
     * @param $original_size
     */
    protected function fillOriginalSizeColumn($id, $original_size)
    {
        $original_size = Utils::formatBytes($original_size);

        if (wp_attachment_is_image($id)) {
            $meta = $this->getImageMeta($id);
            if ($meta && $meta->getOriginalSize()) {
                $original_size = Utils::formatBytes($meta->getOriginalSize());
            }
        }

        echo $original_size;
    }

    /**
     * @param $id
     */
    protected function fillOptimizedSizeColumn($id)
    {
        $is_image = wp_attachment_is_image($id);
        $image_url = wp_get_attachment_url($id);
        $filename = basename($image_url);
        $is_optimize_main_image = false;
        $is_optimize_this_image = false;
        $stats_summary = null;
        $meta = null;
        $optimization_quality = $this->tinyga_options->getQuality();

        if ($is_image) {
            $meta = $this->getImageMeta($id);
            $thumbs_meta = $this->getThumbsMeta($id);
            $optimize_main_image = $this->tinyga_options->isOptimizeMainImage();

            // Is it optimized? Show some stats
            if (!empty($thumbs_meta) || ($meta && $meta->getOptimizedSize() !== null)) {
                $is_optimize_main_image = $optimize_main_image && $meta&& $meta->getOptimizedSize() === null;
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
            'optimization_quality' => $optimization_quality,
            'id' => $id,
            'filename' => $filename,
            'image_url' => $image_url,
            'stats_summary' => $stats_summary,
            'meta' => $meta,
        ]);
    }
}
