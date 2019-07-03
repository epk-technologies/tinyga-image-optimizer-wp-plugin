<?php

namespace Tinyga\Action;

use Tinyga\Model\TinygaImageMeta;
use Tinyga\Model\TinygaThumbMeta;
use Tinyga\Utils;

class MediaReset extends BaseAction
{
    /**
     * @inheritDoc
     */
    protected function registerActions()
    {
        $this->addAction('wp_ajax_tinyga_reset', [&$this, 'tinygaMediaLibraryReset']);
        $this->addAction('wp_ajax_tinyga_reset_all', [&$this, 'tinygaMediaLibraryResetAll']);
    }

    /**
     * Delete all thumbnail & image metadata
     */
    public function tinygaMediaLibraryResetAll()
    {
        $this->deletePostMetaByKey(TinygaThumbMeta::OPTION_NAME);
        $this->deletePostMetaByKey(TinygaImageMeta::OPTION_NAME);

        echo json_encode(['success' => true]);
        $this->WPDie();
    }

    /**
     * Delete thumbnail & image metadata for specific image
     */
    public function tinygaMediaLibraryReset()
    {
        $image_id = (int) $_POST['id'];
        $original_size = Utils::formatBytes(filesize(get_attached_file($image_id)));

        $this->deletePostMeta($image_id, TinygaThumbMeta::OPTION_NAME);
        $this->deletePostMeta($image_id, TinygaImageMeta::OPTION_NAME);

        $optimization_quality = $this->tinyga_options->getQuality();
        $image_url = $this->getAttachmentUrl($image_id);
        $filename = basename($image_url);

        echo json_encode([
            'success' => true,
            'original_size' => $original_size,
            'html' => Utils::view('parts/button_optimize', [
                'is_optimize_this_image' => true,
                'optimization_quality' => $optimization_quality,
                'id' => $image_id,
                'filename' => $filename,
                'image_url' => $image_url,
            ], true),
        ]);
        $this->WPDie();
    }
}

