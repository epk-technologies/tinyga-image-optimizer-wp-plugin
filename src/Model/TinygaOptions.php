<?php

namespace Tinyga\Model;

use Tinyga\ImageOptimizer\OptimizationRequest;

class TinygaOptions extends BaseModel
{
    const OPTION_NAME = '_tinyga_options';

    const BULK_ASYNC_LIMIT_MIN = 1;
    const BULK_ASYNC_LIMIT_MAX = 10;
    const BULK_ASYNC_LIMIT_DEFAULT = 4;

    /**
     * @var string
     */
    protected $api_key = '';

    /**
     * @var bool
     */
    protected $auto_optimize = 1;

    /**
     * @var bool
     */
    protected $optimize_main_image = 1;

    /**
     * @var int
     */
    protected $quality = OptimizationRequest::DEFAULT_LOSSY_QUALITY;

    /**
     * @var int
     */
    protected $max_width = 0;

    /**
     * @var int
     */
    protected $max_height = 0;

    /**
     * @var array
     */
    protected $sizes;

    /**
     * @var bool
     */
    protected $show_reset = 0;

    /**
     * @var int
     */
    protected $bulk_async_limit = self::BULK_ASYNC_LIMIT_DEFAULT;

    /**
     * @return string
     */
    public function getApiKey()
    {
        return $this->api_key;
    }

    /**
     * @param string $api_key
     */
    public function setApiKey($api_key)
    {
        $this->api_key = $api_key;
    }

    /**
     * @return bool
     */
    public function isAutoOptimize()
    {
        return $this->auto_optimize;
    }

    /**
     * @param bool $auto_optimize
     */
    public function setAutoOptimize($auto_optimize)
    {
        $this->auto_optimize = $auto_optimize;
    }

    /**
     * @return bool
     */
    public function isOptimizeMainImage()
    {
        return $this->optimize_main_image;
    }

    /**
     * @param bool $optimize_main_image
     */
    public function setOptimizeMainImage($optimize_main_image)
    {
        $this->optimize_main_image = $optimize_main_image;
    }

    /**
     * @return int
     */
    public function getQuality()
    {
        return $this->quality;
    }

    /**
     * @param int $quality
     */
    public function setQuality($quality)
    {
        $this->quality = $quality;
    }

    /**
     * @return int
     */
    public function getMaxWidth()
    {
        return $this->max_width;
    }

    /**
     * @param int $max_width
     */
    public function setMaxWidth($max_width)
    {
        $this->max_width = $max_width;
    }

    /**
     * @return int
     */
    public function getMaxHeight()
    {
        return $this->max_height;
    }

    /**
     * @param int $max_height
     */
    public function setMaxHeight($max_height)
    {
        $this->max_height = $max_height;
    }

    /**
     * @return bool
     */
    public function isResize()
    {
        return $this->max_height || $this->max_width;
    }

    /**
     * @return array
     */
    public function getSizes()
    {
        return $this->sizes;
    }

    /**
     * @param array $sizes
     */
    public function setSizes($sizes)
    {
        $this->sizes = $sizes;
    }

    /**
     * @param string $size
     *
     * @return bool
     */
    public function isValidSize($size)
    {
        return $this->sizes[$size];
    }

    /**
     * @return bool
     */
    public function isShowReset()
    {
        return $this->show_reset;
    }

    /**
     * @param bool $show_reset
     */
    public function setShowReset($show_reset)
    {
        $this->show_reset = $show_reset;
    }

    /**
     * @return int
     */
    public function getBulkAsyncLimit()
    {
        return $this->bulk_async_limit;
    }

    /**
     * @param int $bulk_async_limit
     */
    public function setBulkAsyncLimit($bulk_async_limit)
    {
        $this->bulk_async_limit = $bulk_async_limit;
    }
}
