<?php

namespace Tinyga\Action;

use Tinyga\ImageOptimizer\Image\ImageContent;
use Tinyga\ImageOptimizer\Image\ImageFile;
use Tinyga\ImageOptimizer\OptimizationException;
use Tinyga\ImageOptimizer\OptimizationResult;
use Tinyga\Manager\ImageOptimizationManager;
use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaThumbMeta;
use Tinyga\Model\WPAttachmentMeta;
use Tinyga\Utils;

class MediaUploader extends StatsSummary
{
    protected $image_optimizer;

    /**
     * MediaPageUploader constructor.
     */
    public function __construct()
    {
        $this->image_optimizer = new ImageOptimizationManager();
        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function registerActions()
    {
        $this->addAction('wp_ajax_tinyga_request', [&$this, 'tinygaMediaLibraryAjaxCallback']);

        if ($this->tinyga_options->isAutoOptimize()) {
            $this->addAction('add_attachment', [&$this, 'tinygaMediaUploaderCallback']);
            $this->addFilter('wp_generate_attachment_metadata', [&$this, 'optimizeThumbnailsFilter']);
        }
    }

    /**
     *  Handles optimizing already-uploaded images in the  Media Library
     */
    public function tinygaMediaLibraryAjaxCallback()
    {
        $image_id = (int) $_POST['id'];
        $quality = isset($_POST['quality']) ? (int) $_POST['quality'] : null;

        if ($this->attachmentIsImage($image_id)) {

            $image_path = $this->getAttachedFile($image_id);
            $optimize_main_image = $this->tinyga_options->isOptimizeMainImage();
            $api_key = $this->tinyga_options->getApiKey();

            if (empty($api_key)) {
                $message = 'There is a problem with your credentials. Please check them in the Tinyga settings section of Media Settings, and try again.';
                $this->updateImageMeta($image_id, [TinygaImageMeta::MESSAGE => $message]);
                echo json_encode(['error' => $message]);
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
                $attachment_meta = $this->getAttachmentMeta($image_id);

                if (!$thumbs_optimized) {
                    $this->optimizeThumbnails($attachment_meta, $image_id, $quality);
                } else {
                    // re-optimize thumbs if optimization quality has changed
                    $optimization_quality = $tinyga_thumbs_data[0]->getOptimizationQuality();
                    if ($optimization_quality !== $quality) {
                        $this->generateAttachmentMeta($image_id, $image_path);
                        $this->optimizeThumbnails($attachment_meta, $image_id, $quality);
                    }
                }

                $original_image = new ImageFile($image_path);

                try {
                    $optimization_result = $this->image_optimizer->optimizeImage($original_image, $quality);
                } catch (OptimizationException $e) {
                    // error or no optimization
                    $data[TinygaImageMeta::ERROR_CODE] = $e->getErrorCode();
                    $data[TinygaImageMeta::CODE] = $e->getCode();
                    $data[TinygaImageMeta::MESSAGE] = $e->getMessage();

                    if (file_exists($image_path)) {
                        $data[TinygaImageMeta::ORIGINAL_SIZE] = filesize($image_path);
                        $this->updateImageMeta($image_id, $data);
                    }

                    echo json_encode(['error' => $data, '']);

                    return;
                }

                $quality = $this->image_optimizer->getLastRequestQuality();
                $image_meta = $this->processResult($optimization_result, $original_image, $quality, $image_id);
                if ($this->replaceImage($image_path, $optimization_result->getOptimizedImage())) {

                    if ($image_meta->getWidth() && $image_meta->getHeight()) {
                        $attachment_meta = $this->getAttachmentMeta($image_id);
                        if ($attachment_meta) {
                            $attachment_meta->setWidth($image_meta->getWidth());
                            $attachment_meta->setHeight($image_meta->getHeight());
                            $this->updateAttachmentMeta($image_id, $attachment_meta);
                        }
                    }

                    // store tinyga info to DB
                    $this->updateImageMeta($image_id, $image_meta);
                    $data = $image_meta->toArray();

                    // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                    $tinyga_thumbs_data = $this->getThumbsMeta($image_id, true);
                    if (!empty($tinyga_thumbs_data)) {
                        $data['thumbs_data'] = $tinyga_thumbs_data;
                    }

                    $data['success'] = true;
                    $data['html'] = $this->generateStatsSummary($image_id);
                    echo json_encode($data);

                } else {
                    echo json_encode(['error' => 'Could not overwrite original file. Please ensure that your files are writable by plugins.']);
                    exit;
                }
            } else {
                // get metadata for thumbnails
                $attachment_meta = $this->getAttachmentMeta($image_id);
                $this->optimizeThumbnails($attachment_meta, $image_id, $quality);

                // optimize thumbnails, store that data too. This can be unset when there are no thumbs
                $tinyga_thumbs_data = $this->getThumbsMeta($image_id, true);

                if (!empty($tinyga_thumbs_data)) {
                    $data['thumbs_data'] = $tinyga_thumbs_data;
                    $data['success'] = true;
                }
                $data['html'] = $this->generateStatsSummary($image_id);

                echo json_encode($data);
            }
        }
        $this->WPDie();
    }

    /**
     * Handles optimizing images uploaded through any of the media uploaders.
     *
     * @param $image_id
     */
    public function tinygaMediaUploaderCallback($image_id)
    {
        if (!$this->tinyga_options->isOptimizeMainImage()) {
            return;
        }

        if ($this->attachmentIsImage($image_id)) {

            $image_path = $this->getAttachedFile($image_id);
            $image_backup_path = $image_path . '_tinyga_' . md5($image_path);
            $backup_created = copy($image_path, $image_backup_path);
            $original_image = new ImageFile($backup_created ? $image_backup_path : $image_path);

            try {
                $optimization_result = $this->image_optimizer->optimizeImage($original_image);
            } catch (OptimizationException $e) {
                // error or no optimization
                if (file_exists($image_path)) {
                    $image_meta = new TinygaImageMeta();
                    $image_meta->setOriginalSize(filesize($image_path));
                    $image_meta->setErrorCode($e->getErrorCode());
                    $image_meta->setCode($e->getCode());
                    $image_meta->setMessage($e->getMessage());
                    $this->updateImageMeta($image_id, $image_meta);
                }

                return;
            }

            $quality = $this->image_optimizer->getLastRequestQuality();
            $image_meta = $this->processResult($optimization_result, $original_image, $quality, $image_id);

            if ($image_meta->getSavedBytes() > 0) {
                if ($backup_created) {
                    // @todo ??? replacing backup won't affect anything in WP at this stage
                    $image_meta->setOptimizedBackupFile($image_backup_path);
                    $image_to_replace_path = $image_backup_path;
                } else {
                    $image_to_replace_path = $image_path;
                }

                if (!$this->replaceImage($image_to_replace_path, $optimization_result->getOptimizedImage())) {
                    error_log('Tinyga: Could not replace local image with optimized image.');
                }
            }

            $this->updateImageMeta($image_id, $image_meta);
        }
    }

    /**
     * @param array $image_data
     *
     * @return array
     */
    public function optimizeThumbnailsFilter($image_data)
    {
        $attachment_meta = new WPAttachmentMeta($image_data);
        return $this->optimizeThumbnails($attachment_meta)->toArray();
    }

    /**
     * @param WPAttachmentMeta $attachment_meta
     * @param null $image_id
     * @param null $quality
     *
     * @return WPAttachmentMeta
     */
    public function optimizeThumbnails($attachment_meta, $image_id = null, $quality = null)
    {
        if (empty($image_id)) {
            global $wpdb;
            $query = $wpdb->prepare(
                "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_value = %s LIMIT 1",
                $attachment_meta->getFile()
            );
            $post = $wpdb->get_row($query);
            $image_id = $post->post_id;
        }

        $tinyga_meta = $this->getImageMeta($image_id) ?: new TinygaImageMeta();
        $image_backup_path = $tinyga_meta->getOptimizedBackupFile();

        if ($image_backup_path) {
            $original_image_path = $this->getAttachedFile($image_id);
            if (copy($image_backup_path, $original_image_path)) {
                unlink($image_backup_path);
                $tinyga_meta->setOptimizedBackupFile(null);
                $this->updateImageMeta($image_id, $tinyga_meta);
            }
        }

        if (!$this->tinyga_options->getSizes()) {
            $sizes = $this->getImageSizes(true);
            $this->tinyga_options->setSizes($sizes);
        }

        // when resizing has taken place via API, update the post metadata accordingly
        if ($tinyga_meta->getWidth() && $tinyga_meta->getHeight()) {
            $attachment_meta->setWidth($tinyga_meta->getWidth());
            $attachment_meta->setHeight($tinyga_meta->getHeight());
        }

        $path_parts = pathinfo($attachment_meta->getFile());

        // e.g. 04/02, for use in getting correct path or URL
        $upload_subdir = $path_parts['dirname'];

        $upload_dir = $this->getUploadDir();

        // all the way up to /uploads
        $upload_base_path = $upload_dir['basedir'];
        $upload_full_path = $upload_base_path . '/' . $upload_subdir;

        $sizes = $attachment_meta->getSizes();

        if (!empty($sizes)) {
            $sizes_to_optimize = $this->getSizesToOptimize();
            $thumbs_optimized_store = [];

            foreach ($sizes as $key => $size) {
                if (!in_array($key, $sizes_to_optimize, false)) {
                    continue;
                }

                $thumb_path = $upload_full_path . '/' . $size['file'];

                if (file_exists($thumb_path) !== false) {
                    $thumb_image = new ImageFile($thumb_path);

                    try {
                        $optimization_result = $this->image_optimizer->optimizeImage($thumb_image, $quality, false);
                    } catch (OptimizationException $e) {
                        continue;
                    }

                    $quality = $this->image_optimizer->getLastRequestQuality();
                    $image_meta = $this->processResult($optimization_result, $thumb_image, $quality);

                    if (
                        $image_meta->getSavedBytes() > 0
                        && !$this->replaceImage($thumb_path, $optimization_result->getOptimizedImage())
                    ) {
                        continue;
                    }

                    $thumb_meta = new TinygaThumbMeta();
                    $thumb_meta->setThumb($key);
                    $thumb_meta->setFile($size['file']);
                    $thumb_meta->setOriginalSize($image_meta->getOriginalSize());
                    $thumb_meta->setOptimizedSize($image_meta->getOptimizedSize());
                    $thumb_meta->setOptimizationQuality($image_meta->getOptimizationQuality());

                    $thumbs_optimized_store[] = $thumb_meta;
                }
            }
        }
        if (!empty($thumbs_optimized_store)) {
            $this->updateThumbsMeta($image_id, $thumbs_optimized_store);
        }
        return $attachment_meta;
    }

    /**
     * Converts an deserialized API result array into an array which this plugin will consume
     *
     * @param OptimizationResult $result
     * @param ImageFile $original_image
     * @param int|null $optimization_quality
     * @param int|null $image_id
     *
     * @return TinygaImageMeta
     */
    protected function processResult(
        OptimizationResult $result,
        ImageFile $original_image,
        $optimization_quality = null,
        $image_id = null
    ) {
        $image_meta = new TinygaImageMeta();

        $image_meta->setTaskId($result->getTaskId());

        if ($image_id) {
            $attachment_meta = $this->getAttachmentMeta($image_id);
            $image_meta->setMeta($attachment_meta ? $attachment_meta->toArray() : null);
        }

        $optimized_image = $result->getOptimizedImage();
        if (!$optimized_image) {
            return $image_meta;
        }

        $optimized_image_params = $optimized_image->getImageParameters();
        $original_image_params = $original_image->getImageParameters();

        $image_meta->setOriginalSize($original_image_params->getFileSize());
        $image_meta->setOptimizedSize($optimized_image_params->getFileSize());
        $image_meta->setSavedBytes($image_meta->getOriginalSize() - $image_meta->getOptimizedSize());
        $savings_percentage = $image_meta->getSavedBytes() / $image_meta->getOriginalSize() * 100;
        $image_meta->setSavingsPercent(round($savings_percentage, 2) . '%');
        $image_meta->setOptimizationQuality($optimization_quality);

        if (!empty($original_image_params->getWidth()) && !empty($original_image_params->getHeight())) {
            $image_meta->setWidth($original_image_params->getWidth());
            $image_meta->setHeight($original_image_params->getHeight());
        }

        return $image_meta;
    }

    /**
     * @param string $image_path
     * @param $content
     *
     * @return bool
     */
    protected function replaceImage($image_path, ImageContent $content)
    {
        $rv = file_put_contents($image_path, $content->getContent());
        return $rv !== false;
    }

    /**
     * @return array
     */
    protected function getSizesToOptimize()
    {
        $rv = [];

        foreach($this->tinyga_options->getSizes() as $key => $value) {
            if (!empty($value)) {
                $rv[] = $key;
            }
        }
        return $rv;
    }
}
