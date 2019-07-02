<?php

namespace Tinyga\Action;

use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaThumbMeta;
use Tinyga\Utils;

abstract class StatsSummary extends BaseAction
{
    /**
     * @param $id
     *
     * @return string
     */
    public function generateStatsSummary($id)
    {
        $image_meta = $this->getImageMeta($id);
        $thumbs_meta = $this->getThumbsMeta($id);

        $total_original_size = 0;
        $total_saved_bytes = 0;

        if ($image_meta && $image_meta->getOriginalSize()) {
            $total_original_size += $image_meta->getOriginalSize();
        }

        if ($image_meta && $image_meta->getSavedBytes()) {
            $total_saved_bytes += $image_meta->getSavedBytes();
        }

        if (!empty($thumbs_meta)) {
            foreach ($thumbs_meta as $thumb_meta) {
                $total_original_size += $thumb_meta->getOriginalSize();
                $total_saved_bytes += $thumb_meta->getOriginalSize() - $thumb_meta->getOptimizedSize();
            }

        }

        $total_savings_percentage = round($total_saved_bytes / $total_original_size * 100, 2) . '%';
        $detailed_results_html = '';
        $total_savings = null;

        if ($total_saved_bytes) {
            $total_savings = Utils::formatBytes($total_saved_bytes);
            $detailed_results_html = $this->resultsHtml($id);
            $detailed_results_html = htmlspecialchars($detailed_results_html);
        }

        return Utils::view('summary', [
            'id' => $id,
            'total_saved_bytes' => $total_saved_bytes,
            'total_savings_percentage' => $total_savings_percentage,
            'total_savings' => $total_savings,
            'detailed_results_html' => $detailed_results_html,
        ], true);
    }

    /**
     * @param $id
     *
     * @return false|string
     */
    protected function resultsHtml($id)
    {
        // get meta data for main post and thumbs
        $image_meta = $this->getImageMeta($id);
        $thumbs_meta = $this->getThumbsMeta($id);

        $main_image_optimized = $image_meta && $image_meta->getOptimizationQuality();
        $thumbs_optimized = !empty($thumbs_meta) && count($thumbs_meta) && $thumbs_meta[0]->getOptimizationQuality();

        $optimization_quality = 'N/A';
        $main_image_tinyga_stats = [];
        $thumbs_tinyga_stats = [];
        $thumbs_count = 0;

        if ($main_image_optimized) {
            $optimization_quality = $image_meta->getOptimizationQuality();
            $main_image_tinyga_stats = $this->calculateImageMetaSavings($image_meta);
        }

        if ($thumbs_optimized) {
            $optimization_quality = $thumbs_meta[0]->getOptimizationQuality();
            $thumbs_tinyga_stats = $this->calculateThumbsMetaSavings($thumbs_meta);
            $thumbs_count = count($thumbs_meta);
        }

        $show_reset = $this->tinyga_options->isShowReset();

        return Utils::view('results', [
            'main_image_tinyga_stats' => $main_image_tinyga_stats,
            'thumbs_count' => $thumbs_count,
            'thumbs_tinyga_stats' => $thumbs_tinyga_stats,
            'optimization_quality' => $optimization_quality,
            'show_reset' => $show_reset,
        ], true);
    }

    /**
     * @param TinygaImageMeta $meta
     *
     * @return array
     */
    protected function calculateImageMetaSavings($meta)
    {
        if ($meta->getOriginalSize() !== null) {
            $savings_percentage = $meta->getSavingsPercent();
            $saved_bytes = Utils::formatBytes($meta->getSavedBytes());

            return [
                'savings_percentage' => $savings_percentage,
                'saved_bytes' => $saved_bytes,
            ];
        }

        return [];
    }

    /**
     * @param TinygaThumbMeta[] $thumbs_meta
     *
     * @return array
     */
    protected function calculateThumbsMetaSavings($thumbs_meta)
    {
        if (!empty($thumbs_meta)) {
            $total_thumb_byte_savings = 0;
            $total_thumb_size = 0;

            foreach ($thumbs_meta as $meta) {
                $total_thumb_size += $meta->getOriginalSize();
                $thumb_byte_savings = $meta->getOriginalSize() - $meta->getOptimizedSize();
                $total_thumb_byte_savings += $thumb_byte_savings;
            }

            $thumbs_savings_percentage = round($total_thumb_byte_savings / $total_thumb_size * 100, 2) . '%';
            $total_thumbs_savings = Utils::formatBytes($total_thumb_byte_savings);

            return [
                'savings_percentage' => $thumbs_savings_percentage,
                'total_savings' => $total_thumbs_savings
            ];
        }

        return [];
    }
}
