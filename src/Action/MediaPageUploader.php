<?php

namespace Tinyga\Action;

use Tinyga\ImageOptimizer;
use Tinyga\ImageOptimizer\Image\ImageContent;
use Tinyga\ImageOptimizer\Image\ImageFile;
use Tinyga\ImageOptimizer\OptimizationResult;
use Tinyga\Settings;
use Tinyga\Utils;

class MediaPageUploader extends Settings
{
    use StatsSummaryTrait;

    protected $image_optimizer;

    /**
     * Register action to event.
     */
    public function __construct()
    {
        parent::__construct();

        $this->image_optimizer = new ImageOptimizer();

        add_action('wp_ajax_tinyga_request', [&$this, 'tinygaMediaLibraryAjaxCallback']);

        if (
            !empty($this->settings[self::TINYGA_OPTIONS_AUTO_OPTIMIZE])
            || !isset($this->settings[self::TINYGA_OPTIONS_AUTO_OPTIMIZE])
        ) {
            add_action('add_attachment', [&$this, 'tinygaMediaUploaderCallback']);
            add_filter('wp_generate_attachment_metadata', [&$this, 'optimizeThumbnails']);
        }
    }

    /**
     *  Handles optimizing already-uploaded images in the  Media Library
     */
    public function tinygaMediaLibraryAjaxCallback()
    {
        $image_id = (int) $_POST['id'];
        $type = isset($_POST['type']) ? $_POST['type'] : null;

        if (wp_attachment_is_image($image_id)) {

            $image_path = Utils::getAttachedFile($image_id);
            $optimize_main_image = !empty($this->settings[self::TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE]);
            $api_key = isset($this->settings[self::TINYGA_OPTIONS_API_KEY])
                ? $this->settings[self::TINYGA_OPTIONS_API_KEY]
                : '';

            $data = [];

            if (empty($api_key)) {
                $data['error'] = 'There is a problem with your credentials. Please check them in the Tinyga settings section of Media Settings, and try again.';
                $this->updateImageMeta($image_id, $data);
                echo json_encode(['error' => $data['error']]);
                exit;
            }

            if ($optimize_main_image) {

                // check if thumbs already optimized
                $thumbs_optimized = false;
                $tinyga_thumbs_data = $this->getThumbsMeta($image_id);

                if (!empty($tinyga_thumbs_data)) {
                    $thumbs_optimized = true;
                }

                // get metadata for thumbnails
                $image_data = wp_get_attachment_metadata($image_id);

                if (!$thumbs_optimized) {
                    $this->optimizeThumbnails($image_data, $image_id, $type);
                } else {
                    // re-optimize thumbs if mode has changed
                    $tinyga_thumbs_mode = $tinyga_thumbs_data[0][self::TINYGA_THUMBS_TYPE];
                    if (strcmp($tinyga_thumbs_mode, $type) !== 0) {
                        wp_generate_attachment_metadata($image_id, $image_path);
                        $this->optimizeThumbnails($image_data, $image_id, $type);
                    }
                }

                $original_image = new ImageFile($image_path);

                try {
                    $optimization_result = $this->image_optimizer->optimizeImage($original_image);
                } catch (ImageOptimizer\OptimizationException $e) {
                    // error or no optimization
                    $data[self::TINYGA_SIZE_ERROR_CODE] = $e->getErrorCode();
                    $data[self::TINYGA_SIZE_CODE] = $e->getCode();
                    $data[self::TINYGA_SIZE_MESSAGE] = $e->getMessage();

                    if (file_exists($image_path)) {
                        $data[self::TINYGA_SIZE_ORIGINAL_SIZE] = filesize($image_path);
                        $this->updateImageMeta($image_id, $data);
                    }

                    echo json_encode(['error' => $data, '']);

                    return;
                }

                $data = $this->getResultArr($optimization_result, $original_image, $image_id);
                if ($this->replaceImage($image_path, $optimization_result->getOptimizedImage())) {

                    if (!empty($data[self::TINYGA_SIZE_WIDTH]) && !empty($data[self::TINYGA_SIZE_HEIGHT])) {
                        /** @var array $image_data */
                        $image_data = wp_get_attachment_metadata($image_id);
                        $image_data['width'] = $data[self::TINYGA_SIZE_WIDTH];
                        $image_data['height'] = $data[self::TINYGA_SIZE_HEIGHT];
                        wp_update_attachment_metadata($image_id, $image_data);
                    }

                    // store tinyga info to DB
                    $this->updateImageMeta($image_id, $data);

                    // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                    $tinyga_thumbs_data = $this->getThumbsMeta($image_id);
                    if (!empty($tinyga_thumbs_data)) {
                        $data['thumbs_data'] = $tinyga_thumbs_data;
                        $data['success'] = true;
                    }

                    $data['html'] = $this->generateStatsSummary($image_id);
                    echo json_encode($data);

                } else {
                    echo json_encode(['error' => 'Could not overwrite original file. Please ensure that your files are writable by plugins.']);
                    exit;
                }
            } else {
                // get metadata for thumbnails
                $image_data = wp_get_attachment_metadata($image_id);
                $this->optimizeThumbnails($image_data, $image_id, $type);

                // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                $tinyga_thumbs_data = $this->getThumbsMeta($image_id);

                if (!empty($tinyga_thumbs_data)) {
                    $data['thumbs_data'] = $tinyga_thumbs_data;
                    $data['success'] = true;
                }
                $data['html'] = $this->generateStatsSummary($image_id);

                echo json_encode($data);
            }
        }
        wp_die();
    }

    /**
     * Handles optimizing images uploaded through any of the media uploaders.
     *
     * @param $image_id
     */
    public function tinygaMediaUploaderCallback($image_id)
    {
        if (empty($this->settings[self::TINYGA_OPTIONS_OPTIMIZE_MAIN_IMAGE])) {
            return;
        }

        if (wp_attachment_is_image($image_id)) {

            $image_path = Utils::getAttachedFile($image_id);
            $image_backup_path = $image_path . '_tinyga_' . md5($image_path);
            $backup_created = copy($image_path, $image_backup_path);
            $original_image = new ImageFile($backup_created ? $image_backup_path : $image_path);

            try {
                $optimization_result = $this->image_optimizer->optimizeImage($original_image);
            } catch (ImageOptimizer\OptimizationException $e) {
                // error or no optimization
                if (file_exists($image_path)) {
                    $data['original_size'] = filesize($image_path);
                    $data['error_code'] = $e->getErrorCode();
                    $data['code'] = $e->getCode();
                    $data['message'] = $e->getMessage();
                    $this->updateImageMeta($image_id, $data);
                }

                return;
            }

            $data = $this->getResultArr($optimization_result, $original_image, $image_id);

            if ($data[self::TINYGA_SIZE_SAVED_BYTES] > 0) {
                if ($backup_created) {
                    // @todo ??? replacing backup won't affect anything in WP at this stage
                    $data[self::TINYGA_SIZE_OPTIMIZED_BACKUP_FILE] = $image_backup_path;
                    $image_to_replace_path = $image_backup_path;
                } else {
                    $image_to_replace_path = $image_path;
                }

                if (!$this->replaceImage($image_to_replace_path, $optimization_result->getOptimizedImage())) {
                    error_log('Tinyga: Could not replace local image with optimized image.');
                }
            }

            $this->updateImageMeta($image_id, $data);
        }
    }

    /**
     * @param $image_data
     * @param null $image_id
     * @param null $type
     *
     * @return mixed
     */
    public function optimizeThumbnails($image_data, $image_id = null, $type = null)
    {
        if (empty($image_id)) {
            global $wpdb;
            $query = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s LIMIT 1",
                $image_data[self::TINYGA_THUMBS_FILE]
            );
            $post = $wpdb->get_row($query);
            $image_id = $post->post_id;
        }

        $tinyga_meta = $this->getImageMeta($image_id);
        $image_backup_path = isset($tinyga_meta[self::TINYGA_SIZE_OPTIMIZED_BACKUP_FILE])
            ? $tinyga_meta[self::TINYGA_SIZE_OPTIMIZED_BACKUP_FILE]
            : '';

        if ($image_backup_path) {
            $original_image_path = Utils::getAttachedFile($image_id);
            if (copy($image_backup_path, $original_image_path)) {
                unlink($image_backup_path);
                unset($tinyga_meta[self::TINYGA_SIZE_OPTIMIZED_BACKUP_FILE]);
                $this->updateImageMeta($image_id, $tinyga_meta);
            }
        }

        $include_size_prefix = self::TINYGA_OPTIONS_SIZES_PREFIX;
        if (!Utils::pregArrayKeyExists("/^{$include_size_prefix}/", $this->settings)) {
            $sizes = Utils::getImageSizes(true);
            foreach ($sizes as $size) {
                $this->settings[self::TINYGA_OPTIONS_SIZES_PREFIX . $size] = 1;
            }
        }

        // when resizing has taken place via API, update the post metadata accordingly
        if (!empty($tinyga_meta[self::TINYGA_SIZE_WIDTH]) && !empty($tinyga_meta[self::TINYGA_SIZE_HEIGHT])) {
            $image_data['width'] = $tinyga_meta[self::TINYGA_SIZE_WIDTH];
            $image_data['height'] = $tinyga_meta[self::TINYGA_SIZE_HEIGHT];
        }

        $path_parts = pathinfo($image_data['file']);

        // e.g. 04/02, for use in getting correct path or URL
        $upload_subdir = $path_parts['dirname'];

        $upload_dir = Utils::getWpUploadDir();

        // all the way up to /uploads
        $upload_base_path = $upload_dir['basedir'];
        $upload_full_path = $upload_base_path . '/' . $upload_subdir;

        $sizes = [];

        if (isset($image_data['sizes'])) {
            $sizes = $image_data['sizes'];
        }

        if (!empty($sizes)) {
            $sizes_to_optimize = $this->getSizesToOptimize();
            $thumbs_optimized_store = [];

            foreach ($sizes as $key => $size) {
                if (!in_array(self::TINYGA_OPTIONS_SIZES_PREFIX . $key, $sizes_to_optimize, false)) {
                    continue;
                }

                $thumb_path = $upload_full_path . '/' . $size['file'];

                if (file_exists($thumb_path) !== false) {
                    $thumb_image = new ImageFile($thumb_path);

                    try {
                        $optimization_result = $this->image_optimizer->optimizeImage($thumb_image);
                    } catch (ImageOptimizer\OptimizationException $e) {
                        continue;
                    }

                    $data = $this->getResultArr($optimization_result, $thumb_image);

                    if (
                        $data[self::TINYGA_SIZE_SAVED_BYTES] > 0
                        && !$this->replaceImage($thumb_path, $optimization_result->getOptimizedImage())
                    ) {
                        continue;
                    }

                    $thumbs_optimized_store[] = [
                        self::TINYGA_THUMBS_THUMB => $key,
                        self::TINYGA_THUMBS_FILE => $size['file'],
                        self::TINYGA_THUMBS_ORIGINAL_SIZE => $data[self::TINYGA_SIZE_ORIGINAL_SIZE],
                        self::TINYGA_THUMBS_OPTIMIZED_SIZE => $data[self::TINYGA_SIZE_OPTIMIZED_SIZE],
                        self::TINYGA_THUMBS_TYPE => $type
                    ];
                }
            }
        }
        if (!empty($thumbs_optimized_store)) {
            $this->updateThumbsMeta($image_id, $thumbs_optimized_store);
        }
        return $image_data;
    }

    /**
     * Converts an deserialized API result array into an array which this plugin will consume
     *
     * @param OptimizationResult $result
     * @param ImageFile $original_image
     * @param int|null $image_id
     *
     * @return array
     */
    private function getResultArr(OptimizationResult $result, ImageFile $original_image, $image_id = null)
    {
        $rv = [];

        if ($image_id) {
            $rv[self::TINYGA_SIZE_META] = wp_get_attachment_metadata($image_id);
        }

        $rv[self::TINYGA_SIZE_TASK_ID] = $result->getTaskId();
        $optimized_image = $result->getOptimizedImage();

        if (!$optimized_image) {
            return $rv;
        }

        $optimized_image_params = $optimized_image->getImageParameters();
        $original_image_params = $original_image->getImageParameters();

        $rv[self::TINYGA_SIZE_ORIGINAL_SIZE] = $original_image_params->getFileSize();
        $rv[self::TINYGA_SIZE_OPTIMIZED_SIZE] = $optimized_image_params->getFileSize();
        $rv[self::TINYGA_SIZE_SAVED_BYTES]
            = $rv[self::TINYGA_SIZE_ORIGINAL_SIZE] - $rv[self::TINYGA_SIZE_OPTIMIZED_SIZE];
        $savings_percentage = $rv[self::TINYGA_SIZE_SAVED_BYTES] / $rv[self::TINYGA_SIZE_ORIGINAL_SIZE] * 100;
        $rv[self::TINYGA_SIZE_SAVING_PERCENT] = round($savings_percentage, 2) . '%';
        $rv[self::TINYGA_SIZE_TYPE] = $optimized_image_params->getMimeType();

        if (!empty($original_image_params->getWidth()) && !empty($original_image_params->getHeight())) {
            $rv[self::TINYGA_SIZE_WIDTH] = $original_image_params->getWidth();
            $rv[self::TINYGA_SIZE_HEIGHT] = $original_image_params->getHeight();
        }

        return $rv;
    }

    /**
     * @param string $image_path
     * @param $content
     *
     * @return bool
     */
    private function replaceImage($image_path, ImageContent $content)
    {
        $rv = file_put_contents($image_path, $content->getContent());
        return $rv !== false;
    }

    /**
     * @return array
     */
    private function getSizesToOptimize()
    {
        $rv = [];

        foreach($this->settings as $key => $value) {
            if (!empty($value) && strpos($key, self::TINYGA_OPTIONS_SIZES_PREFIX) === 0) {
                $rv[] = $key;
            }
        }
        return $rv;
    }
}
