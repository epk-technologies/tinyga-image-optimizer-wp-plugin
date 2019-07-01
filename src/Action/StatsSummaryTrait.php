<?php

namespace Tinyga\Action;

use Tinyga\Utils;

trait StatsSummaryTrait
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

        if (isset($image_meta['original_size'])) {
            $total_original_size += (int)$image_meta['original_size'];
        }

        if (isset($image_meta['saved_bytes'])) {
            $total_saved_bytes += (int)$image_meta['saved_bytes'];
        }

        if (!empty($thumbs_meta)) {
            foreach ($thumbs_meta as $k => $v) {
                $total_original_size += (int)$v['original_size'];
                $total_saved_bytes += (int)$v['original_size'] - (int)$v['optimized_size'];
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
    private function resultsHtml($id)
    {
        // get meta data for main post and thumbs
        $image_meta = $this->getImageMeta($id);
        $thumbs_meta = $this->getThumbsMeta($id);

        $main_image_optimized = !empty($image_meta) && isset($image_meta['type']);
        $thumbs_optimized = !empty($thumbs_meta) && count($thumbs_meta) && isset($thumbs_meta[0]['type']);

        $type = '';
        $main_image_tinyga_stats = [];
        $thumbs_tinyga_stats = [];
        $thumbs_count = 0;

        if ($main_image_optimized) {
            $type = $image_meta['type'];
            $main_image_tinyga_stats = $this->calculateSavings($image_meta);
        }

        if ($thumbs_optimized) {
            $type = $thumbs_meta[0]['type'];
            $thumbs_tinyga_stats = $this->calculateSavings($thumbs_meta);
            $thumbs_count = count($thumbs_meta);
        }

        return Utils::view('results', [
            'main_image_tinyga_stats' => $main_image_tinyga_stats,
            'thumbs_count' => $thumbs_count,
            'thumbs_tinyga_stats' => $thumbs_tinyga_stats,
            'type' => $type,
            'show_reset' => $this->settings['show_reset'],
        ], true);
    }

    /**
     * @param $meta
     *
     * @return array
     */
    private function calculateSavings($meta)
    {
        if (isset($meta['original_size'])) {
            $savings_percentage = $meta['savings_percent'];
            $saved_bytes = isset($meta['saved_bytes']) ? $meta['saved_bytes'] : 0;
            $saved_bytes = Utils::formatBytes($saved_bytes);

            return [
                'savings_percentage' => $savings_percentage,
                'saved_bytes' => $saved_bytes,
            ];
        }

        if (!empty($meta)) {
            $total_thumb_byte_savings = 0;
            $total_thumb_size = 0;

            foreach ($meta as $k => $v) {
                $total_thumb_size += $v['original_size'];
                $thumb_byte_savings = $v['original_size'] - $v['optimized_size'];
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
